<?php

namespace App\EventSubscriber;

use App\Security\CspNonceProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContentSecurityPolicySubscriber implements EventSubscriberInterface
{
    private const CDN = 'https://cdn.jsdelivr.net';

    public function __construct(private readonly CspNonceProvider $nonceProvider) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip Symfony profiler toolbar responses
        if (str_starts_with($event->getRequest()->getPathInfo(), '/_')) {
            return;
        }

        $nonce = $this->nonceProvider->getNonce();

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' " . self::CDN . " 'nonce-{$nonce}'",
            // jsDelivr CDN for Bootstrap & Bootstrap Icons CSS
            "style-src 'self' " . self::CDN . " 'unsafe-inline'",
            // Bootstrap Icons web font
            "font-src 'self' " . self::CDN,
            // fetch() calls to /api/* routes
            "connect-src 'self'",
            // SVG data URI used for the favicon
            "img-src 'self' data:",
        ]);

        $event->getResponse()->headers->set('Content-Security-Policy', $csp);
    }
}
