<?php

namespace App\Tests\Unit\ValueObject;

use App\ValueObject\MatomoCredentials;
use PHPUnit\Framework\TestCase;

class MatomoCredentialsTest extends TestCase
{
    public function testFromArray(): void
    {
        $credentials = MatomoCredentials::fromArray([
            'url'      => 'https://analytics.example.com',
            'apiToken' => 'tok123',
            'siteId'   => 42,
        ]);

        $this->assertSame('https://analytics.example.com', $credentials->url);
        $this->assertSame('tok123', $credentials->apiToken);
        $this->assertSame(42, $credentials->siteId);
    }

    public function testFromArrayAppliesDefaults(): void
    {
        $credentials = MatomoCredentials::fromArray([]);

        $this->assertSame('', $credentials->url);
        $this->assertSame('', $credentials->apiToken);
        $this->assertSame(1, $credentials->siteId);
    }

    public function testToArray(): void
    {
        $credentials = new MatomoCredentials('https://x.com', 'tok', 7);

        $this->assertSame([
            'url'      => 'https://x.com',
            'apiToken' => 'tok',
            'siteId'   => 7,
        ], $credentials->toArray());
    }

    public function testRoundtrip(): void
    {
        $data = ['url' => 'https://x.com', 'apiToken' => 'abc', 'siteId' => 3];
        $this->assertSame($data, MatomoCredentials::fromArray($data)->toArray());
    }
}
