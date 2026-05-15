<?php

namespace App\Tests\Unit\Service;

use App\Service\CredentialsEncryptor;
use PHPUnit\Framework\TestCase;

class CredentialsEncryptorTest extends TestCase
{
    private CredentialsEncryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new CredentialsEncryptor('test-secret-for-unit-tests-only');
    }

    public function testRoundtrip(): void
    {
        $data = ['url' => 'https://example.com', 'apiToken' => 'abc123', 'siteId' => 42];

        $this->assertSame($data, $this->encryptor->decrypt($this->encryptor->encrypt($data)));
    }

    public function testEmptyArrayRoundtrip(): void
    {
        $this->assertSame([], $this->encryptor->decrypt($this->encryptor->encrypt([])));
    }

    public function testEachEncryptionProducesUniqueOutput(): void
    {
        $data = ['key' => 'value'];

        $this->assertNotSame($this->encryptor->encrypt($data), $this->encryptor->encrypt($data));
    }

    public function testDecryptWithWrongKeyThrowsRuntimeException(): void
    {
        $encrypted = $this->encryptor->encrypt(['key' => 'value']);
        $other = new CredentialsEncryptor('completely-different-secret');

        $this->expectException(\RuntimeException::class);
        $other->decrypt($encrypted);
    }
}
