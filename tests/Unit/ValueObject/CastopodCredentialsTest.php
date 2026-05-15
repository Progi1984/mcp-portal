<?php

namespace App\Tests\Unit\ValueObject;

use App\ValueObject\CastopodCredentials;
use PHPUnit\Framework\TestCase;

class CastopodCredentialsTest extends TestCase
{
    public function testFromArrayWithAllFields(): void
    {
        $credentials = CastopodCredentials::fromArray([
            'url'         => 'https://podcasts.example.com',
            'username'    => 'admin',
            'password'    => 'secret',
            'op3ApiKey'   => 'key123',
            'op3ShowUuid' => 'uuid-abc',
        ]);

        $this->assertSame('https://podcasts.example.com', $credentials->url);
        $this->assertSame('admin', $credentials->username);
        $this->assertSame('secret', $credentials->password);
        $this->assertSame('key123', $credentials->op3ApiKey);
        $this->assertSame('uuid-abc', $credentials->op3ShowUuid);
    }

    public function testFromArrayWithoutOp3Fields(): void
    {
        $credentials = CastopodCredentials::fromArray([
            'url'      => 'https://example.com',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertNull($credentials->op3ApiKey);
        $this->assertNull($credentials->op3ShowUuid);
    }

    public function testToArray(): void
    {
        $credentials = new CastopodCredentials(
            url:         'https://example.com',
            username:    'user',
            password:    'pass',
            op3ApiKey:   'key',
            op3ShowUuid: 'uuid',
        );

        $this->assertSame([
            'url'         => 'https://example.com',
            'username'    => 'user',
            'password'    => 'pass',
            'op3ApiKey'   => 'key',
            'op3ShowUuid' => 'uuid',
        ], $credentials->toArray());
    }

    public function testRoundtrip(): void
    {
        $data = [
            'url'         => 'https://example.com',
            'username'    => 'user',
            'password'    => 'pass',
            'op3ApiKey'   => 'k',
            'op3ShowUuid' => 'u',
        ];

        $this->assertSame($data, CastopodCredentials::fromArray($data)->toArray());
    }

    public function testHasOp3ReturnsTrueWhenBothFieldsSet(): void
    {
        $credentials = new CastopodCredentials('u', 'l', 'p', 'key', 'uuid');
        $this->assertTrue($credentials->hasOp3());
    }

    public function testHasOp3ReturnsFalseWhenApiKeyMissing(): void
    {
        $credentials = new CastopodCredentials('u', 'l', 'p', null, 'uuid');
        $this->assertFalse($credentials->hasOp3());
    }

    public function testHasOp3ReturnsFalseWhenShowUuidMissing(): void
    {
        $credentials = new CastopodCredentials('u', 'l', 'p', 'key', null);
        $this->assertFalse($credentials->hasOp3());
    }

    public function testHasOp3ReturnsFalseWhenNeitherSet(): void
    {
        $credentials = new CastopodCredentials('u', 'l', 'p');
        $this->assertFalse($credentials->hasOp3());
    }
}
