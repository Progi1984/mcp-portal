<?php

namespace App\Service;

use App\Enum\McpServerType;
use App\ValueObject\CastopodCredentials;
use App\ValueObject\GoogleSearchConsoleCredentials;
use App\ValueObject\MatomoCredentials;

class ConnectorTestService
{
    public function __construct(
        private readonly CastopodClient $castopodClient,
        private readonly GoogleSearchConsoleClient $gscClient,
        private readonly MatomoClient $matomoClient,
    ) {}

    /**
     * Test a connector with raw credentials.
     * Throws \InvalidArgumentException if required fields are missing.
     *
     * @return array{success: bool, message: string}
     */
    public function test(McpServerType $type, array $credentials): array
    {
        return match ($type) {
            McpServerType::Castopod            => $this->testCastopod($credentials),
            McpServerType::GoogleSearchConsole => $this->testGsc($credentials),
            McpServerType::Matomo              => $this->testMatomo($credentials),
        };
    }

    private function testCastopod(array $data): array
    {
        if (empty($data['url']) || empty($data['username']) || empty($data['password'])) {
            throw new \InvalidArgumentException('Missing parameters.');
        }

        return $this->castopodClient->testConnection(new CastopodCredentials(
            url:      $data['url'],
            username: $data['username'],
            password: $data['password'],
        ));
    }

    private function testGsc(array $data): array
    {
        if (empty($data['serviceAccountJson']) || empty($data['siteUrl'])) {
            throw new \InvalidArgumentException('Missing parameters.');
        }

        return $this->gscClient->testConnection(new GoogleSearchConsoleCredentials(
            serviceAccountJson: $data['serviceAccountJson'],
            siteUrl:            $data['siteUrl'],
        ));
    }

    private function testMatomo(array $data): array
    {
        if (empty($data['url']) || empty($data['apiToken']) || empty($data['siteId'])) {
            throw new \InvalidArgumentException('Missing parameters.');
        }

        return $this->matomoClient->testConnection(new MatomoCredentials(
            url:      $data['url'],
            apiToken: $data['apiToken'],
            siteId:   (int) $data['siteId'],
        ));
    }
}
