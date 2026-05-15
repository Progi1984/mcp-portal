<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ContentSecurityPolicySubscriber;
use App\Security\CspNonceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentSecurityPolicySubscriberTest extends TestCase
{
    private function makeEvent(Request $request, Response $response, int $type = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $type,
            $response,
        );
    }

    public function testSkipsSubRequests(): void
    {
        $subscriber = new ContentSecurityPolicySubscriber(new CspNonceProvider(new RequestStack()));
        $response   = new Response();

        $subscriber->onKernelResponse($this->makeEvent(
            Request::create('/'),
            $response,
            HttpKernelInterface::SUB_REQUEST,
        ));

        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function testSkipsSymfonyProfilerRoutes(): void
    {
        $subscriber = new ContentSecurityPolicySubscriber(new CspNonceProvider(new RequestStack()));
        $response   = new Response();

        $subscriber->onKernelResponse($this->makeEvent(Request::create('/_profiler/abc123'), $response));

        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function testSetsCspHeaderForNormalRequests(): void
    {
        $stack   = new RequestStack();
        $request = Request::create('/some/page');
        $stack->push($request);

        $provider   = new CspNonceProvider($stack);
        $subscriber = new ContentSecurityPolicySubscriber($provider);
        $response   = new Response();

        $subscriber->onKernelResponse($this->makeEvent($request, $response));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("nonce-{$provider->getNonce()}", $csp);
        $this->assertStringContainsString('cdn.jsdelivr.net', $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
    }

    public function testCspHeaderNotSetForWildcardProfilerPath(): void
    {
        $subscriber = new ContentSecurityPolicySubscriber(new CspNonceProvider(new RequestStack()));
        $response   = new Response();

        $subscriber->onKernelResponse($this->makeEvent(Request::create('/_wdt/abc'), $response));

        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }
}
