<?php

namespace App\Service;

use App\ValueObject\GoogleSearchConsoleCredentials;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleSearchConsoleClient
{
    private const API_V3 = 'https://searchconsole.googleapis.com/webmasters/v3';
    private const API_V1 = 'https://searchconsole.googleapis.com/v1';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    // Full scope required for the URL Inspection API (not just .readonly)
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters';
    private const TOKEN_TTL = 3300; // 55 min - tokens expire after 60 min

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    // ── Connection ───────────────────────────────────────────────────────────

    public function testConnection(GoogleSearchConsoleCredentials $credentials): array
    {
        try {
            $token = $this->getAccessToken($credentials);
            $encoded = rawurlencode($credentials->siteUrl);

            $response = $this->httpClient->request('GET', self::API_V3."/sites/{$encoded}", [
                'headers' => ['Authorization' => "Bearer {$token}"],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $siteUrl = $data['siteUrl'] ?? $credentials->siteUrl;

            return ['success' => true, 'message' => "Connected - property: \"{$siteUrl}\""];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Global performance ────────────────────────────────────────────────────

    public function getSearchPerformance(GoogleSearchConsoleCredentials $credentials, string $startDate, string $endDate): array
    {
        return $this->querySearchAnalytics($credentials, $startDate, $endDate, []);
    }

    public function getTopQueries(GoogleSearchConsoleCredentials $credentials, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->querySearchAnalytics($credentials, $startDate, $endDate, ['query'], $limit);
    }

    public function getTopPages(GoogleSearchConsoleCredentials $credentials, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->querySearchAnalytics($credentials, $startDate, $endDate, ['page'], $limit);
    }

    // ── Audit SEO ─────────────────────────────────────────────────────────────

    public function getPerformanceByDevice(GoogleSearchConsoleCredentials $credentials, string $startDate, string $endDate): array
    {
        return $this->querySearchAnalytics($credentials, $startDate, $endDate, ['device'], 10);
    }

    public function getPerformanceByCountry(GoogleSearchConsoleCredentials $credentials, string $startDate, string $endDate, int $limit = 25): array
    {
        return $this->querySearchAnalytics($credentials, $startDate, $endDate, ['country'], $limit);
    }

    /**
     * Returns all queries with clicks, impressions, CTR and position.
     * The GSC API does not support server-side filtering on numeric metrics;
     * sorting and low-CTR identification are handled by the AI client.
     */
    public function getLowCtrQueries(GoogleSearchConsoleCredentials $credentials, string $startDate, string $endDate, int $limit = 500): array
    {
        return $this->querySearchAnalytics($credentials, $startDate, $endDate, ['query'], $limit);
    }

    /**
     * Inspects a URL via the URL Inspection API (v1).
     * Requires the Owner role on the GSC property.
     */
    public function inspectUrl(GoogleSearchConsoleCredentials $credentials, string $url): array
    {
        $token = $this->getAccessToken($credentials);

        $response = $this->httpClient->request('POST', self::API_V1.'/urlInspection/index:inspect', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'inspectionUrl' => $url,
                'siteUrl' => $credentials->siteUrl,
            ],
            'timeout' => 15,
        ]);

        return $response->toArray();
    }

    // ── Sitemaps ──────────────────────────────────────────────────────────────

    public function getSitemaps(GoogleSearchConsoleCredentials $credentials): array
    {
        $token = $this->getAccessToken($credentials);
        $encoded = rawurlencode($credentials->siteUrl);

        $response = $this->httpClient->request('GET', self::API_V3."/sites/{$encoded}/sitemaps", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'timeout' => 10,
        ]);

        return $response->toArray();
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function querySearchAnalytics(
        GoogleSearchConsoleCredentials $credentials,
        string $startDate,
        string $endDate,
        array $dimensions,
        int $rowLimit = 25,
    ): array {
        $token = $this->getAccessToken($credentials);
        $encoded = rawurlencode($credentials->siteUrl);

        $body = ['startDate' => $startDate, 'endDate' => $endDate, 'rowLimit' => $rowLimit];

        if ($dimensions) {
            $body['dimensions'] = $dimensions;
        }

        $response = $this->httpClient->request('POST', self::API_V3."/sites/{$encoded}/searchAnalytics/query", [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => 15,
        ]);

        return $response->toArray();
    }

    private function getAccessToken(GoogleSearchConsoleCredentials $credentials): string
    {
        $serviceAccount = json_decode($credentials->serviceAccountJson, true, 512, \JSON_THROW_ON_ERROR);
        $cacheKey = 'gsc_token_'.sha1($serviceAccount['client_email']);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($serviceAccount): string {
            $item->expiresAfter(self::TOKEN_TTL);

            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->buildJwt($serviceAccount),
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token'])) {
                throw new \RuntimeException('Failed to obtain a Google access token: '.($data['error_description'] ?? 'unknown error'));
            }

            return $data['access_token'];
        });
    }

    private function buildJwt(array $serviceAccount): string
    {
        $privateKey = $serviceAccount['private_key'] ?? '';
        if (!is_string($privateKey) || '' === $privateKey) {
            throw new \InvalidArgumentException('Invalid service account: missing or empty private_key.');
        }

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKey),
            InMemory::plainText($privateKey),
        );

        $now = new \DateTimeImmutable();

        $token = $config->builder()
            ->issuedBy($serviceAccount['client_email'])
            ->permittedFor(self::TOKEN_URL)
            ->withClaim('scope', self::SCOPE)
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }
}
