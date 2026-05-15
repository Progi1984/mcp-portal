<?php

namespace App\Tests\Unit\Service;

use App\Enum\McpServerType;
use App\Service\CastopodClient;
use App\Service\ConnectorTestService;
use App\Service\GoogleSearchConsoleClient;
use App\Service\MatomoClient;
use App\ValueObject\CastopodCredentials;
use App\ValueObject\GoogleSearchConsoleCredentials;
use App\ValueObject\MatomoCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConnectorTestServiceTest extends TestCase
{
    private ConnectorTestService $service;
    private CastopodClient $castopod;
    private GoogleSearchConsoleClient $gsc;
    private MatomoClient $matomo;

    protected function setUp(): void
    {
        $this->castopod = $this->createMock(CastopodClient::class);
        $this->gsc      = $this->createMock(GoogleSearchConsoleClient::class);
        $this->matomo   = $this->createMock(MatomoClient::class);
        $this->service  = new ConnectorTestService($this->castopod, $this->gsc, $this->matomo);
    }

    // ── Matomo ────────────────────────────────────────────────────────────────

    #[DataProvider('matomoMissingParamsProvider')]
    public function testMatomoThrowsOnMissingParams(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameters.');
        $this->service->test(McpServerType::Matomo, $data);
    }

    public static function matomoMissingParamsProvider(): iterable
    {
        yield 'missing url'      => [['apiToken' => 'tok', 'siteId' => 1]];
        yield 'missing apiToken' => [['url' => 'https://x.com', 'siteId' => 1]];
        yield 'missing siteId'   => [['url' => 'https://x.com', 'apiToken' => 'tok']];
    }

    public function testMatomoCallsClientAndReturnsResult(): void
    {
        $expected = ['success' => true, 'message' => 'OK'];
        $this->matomo
            ->expects($this->once())
            ->method('testConnection')
            ->with($this->callback(fn($c) => $c instanceof MatomoCredentials
                && $c->url === 'https://x.com'
                && $c->apiToken === 'tok'
                && $c->siteId === 1))
            ->willReturn($expected);

        $result = $this->service->test(McpServerType::Matomo, [
            'url' => 'https://x.com', 'apiToken' => 'tok', 'siteId' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    // ── Castopod ──────────────────────────────────────────────────────────────

    #[DataProvider('castopodMissingParamsProvider')]
    public function testCastopodThrowsOnMissingParams(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameters.');
        $this->service->test(McpServerType::Castopod, $data);
    }

    public static function castopodMissingParamsProvider(): iterable
    {
        yield 'missing url'      => [['username' => 'u', 'password' => 'p']];
        yield 'missing username' => [['url' => 'https://x.com', 'password' => 'p']];
        yield 'missing password' => [['url' => 'https://x.com', 'username' => 'u']];
    }

    public function testCastopodCallsClientAndReturnsResult(): void
    {
        $expected = ['success' => true, 'message' => 'OK'];
        $this->castopod
            ->expects($this->once())
            ->method('testConnection')
            ->with($this->callback(fn($c) => $c instanceof CastopodCredentials
                && $c->url === 'https://pod.com'
                && $c->username === 'admin'))
            ->willReturn($expected);

        $result = $this->service->test(McpServerType::Castopod, [
            'url' => 'https://pod.com', 'username' => 'admin', 'password' => 'secret',
        ]);

        $this->assertSame($expected, $result);
    }

    // ── Google Search Console ─────────────────────────────────────────────────

    #[DataProvider('gscMissingParamsProvider')]
    public function testGscThrowsOnMissingParams(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameters.');
        $this->service->test(McpServerType::GoogleSearchConsole, $data);
    }

    public static function gscMissingParamsProvider(): iterable
    {
        yield 'missing serviceAccountJson' => [['siteUrl' => 'https://x.com']];
        yield 'missing siteUrl'            => [['serviceAccountJson' => '{}']];
    }

    public function testGscCallsClientAndReturnsResult(): void
    {
        $expected = ['success' => true, 'message' => 'OK'];
        $this->gsc
            ->expects($this->once())
            ->method('testConnection')
            ->with($this->callback(fn($c) => $c instanceof GoogleSearchConsoleCredentials
                && $c->siteUrl === 'https://x.com'
                && $c->serviceAccountJson === '{}'))
            ->willReturn($expected);

        $result = $this->service->test(McpServerType::GoogleSearchConsole, [
            'serviceAccountJson' => '{}', 'siteUrl' => 'https://x.com',
        ]);

        $this->assertSame($expected, $result);
    }
}
