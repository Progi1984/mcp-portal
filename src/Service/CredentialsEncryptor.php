<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CredentialsEncryptor
{
    private string $key;

    public function __construct(#[Autowire('%kernel.secret%')] string $appSecret)
    {
        // Derive a 32-byte key from APP_SECRET
        $this->key = hash('sha256', $appSecret, true);
    }

    public function encrypt(array $data): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox(json_encode($data), $nonce, $this->key);

        return base64_encode($nonce.$ciphertext);
    }

    public function decrypt(string $encoded): array
    {
        $decoded = base64_decode($encoded, strict: true);

        if (false === $decoded) {
            throw new \RuntimeException('Failed to decrypt credentials.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

        if (false === $plain) {
            throw new \RuntimeException('Failed to decrypt credentials.');
        }

        try {
            return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException('Decrypted payload is not valid JSON.');
        }
    }
}
