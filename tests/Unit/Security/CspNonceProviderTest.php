<?php

namespace App\Tests\Unit\Security;

use App\Security\CspNonceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CspNonceProviderTest extends TestCase
{
    public function testReturnsEmptyStringWithoutCurrentRequest(): void
    {
        $provider = new CspNonceProvider(new RequestStack());
        $this->assertSame('', $provider->getNonce());
    }

    public function testGeneratesValidBase64Nonce(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));
        $provider = new CspNonceProvider($stack);

        $nonce = $provider->getNonce();
        $this->assertNotEmpty($nonce);
        $this->assertNotFalse(base64_decode($nonce, true));
    }

    public function testNonceIsStableWithinSameRequest(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));
        $provider = new CspNonceProvider($stack);

        $this->assertSame($provider->getNonce(), $provider->getNonce());
    }

    public function testDifferentRequestsProduceDifferentNonces(): void
    {
        $stack1 = new RequestStack();
        $stack1->push(Request::create('/'));

        $stack2 = new RequestStack();
        $stack2->push(Request::create('/'));

        $nonce1 = (new CspNonceProvider($stack1))->getNonce();
        $nonce2 = (new CspNonceProvider($stack2))->getNonce();

        // Two 16-byte random nonces collide with probability 2^-128
        $this->assertNotSame($nonce1, $nonce2);
    }
}
