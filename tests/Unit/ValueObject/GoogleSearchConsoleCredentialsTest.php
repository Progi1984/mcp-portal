<?php

namespace App\Tests\Unit\ValueObject;

use App\ValueObject\GoogleSearchConsoleCredentials;
use PHPUnit\Framework\TestCase;

class GoogleSearchConsoleCredentialsTest extends TestCase
{
    public function testFromArray(): void
    {
        $credentials = GoogleSearchConsoleCredentials::fromArray([
            'serviceAccountJson' => '{"type":"service_account"}',
            'siteUrl'            => 'https://example.com/',
        ]);

        $this->assertSame('{"type":"service_account"}', $credentials->serviceAccountJson);
        $this->assertSame('https://example.com/', $credentials->siteUrl);
    }

    public function testToArray(): void
    {
        $credentials = new GoogleSearchConsoleCredentials(
            serviceAccountJson: '{"foo":"bar"}',
            siteUrl:            'sc-domain:example.com',
        );

        $this->assertSame([
            'serviceAccountJson' => '{"foo":"bar"}',
            'siteUrl'            => 'sc-domain:example.com',
        ], $credentials->toArray());
    }

    public function testRoundtrip(): void
    {
        $data = ['serviceAccountJson' => '{}', 'siteUrl' => 'https://x.com/'];
        $this->assertSame($data, GoogleSearchConsoleCredentials::fromArray($data)->toArray());
    }
}
