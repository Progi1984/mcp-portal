<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

class CspNonceProvider
{
    private const ATTR = '_csp_nonce';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getNonce(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return '';
        }
        if (!$request->attributes->has(self::ATTR)) {
            $request->attributes->set(self::ATTR, base64_encode(random_bytes(16)));
        }

        return $request->attributes->get(self::ATTR);
    }
}
