<?php

namespace App\Service;

use App\ValueObject\CastopodCredentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CastopodClient
{
    private const OP3_API = 'https://op3.dev/api/1';

    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function testConnection(CastopodCredentials $credentials): array
    {
        try {
            $data  = $this->get($credentials, '/podcasts');
            $count = count($data);
            $first = $data[0]['title'] ?? null;
            $msg   = $first
                ? "Connected - {$count} podcast(s), first: \"{$first}\""
                : "Connected - {$count} podcast(s)";

            return ['success' => true, 'message' => $msg];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Podcasts ──────────────────────────────────────────────────────────────

    public function listPodcasts(CastopodCredentials $credentials): array
    {
        return $this->get($credentials, '/podcasts');
    }

    public function getPodcast(CastopodCredentials $credentials, int $podcastId): array
    {
        return $this->get($credentials, "/podcasts/{$podcastId}");
    }

    // ── Episodes ──────────────────────────────────────────────────────────────

    public function listEpisodes(CastopodCredentials $credentials, ?int $podcastId = null, int $page = 1): array
    {
        $params = ['page' => $page];

        if ($podcastId !== null) {
            $params['podcast_id'] = $podcastId;
        }

        return $this->get($credentials, '/episodes', $params);
    }

    public function getEpisode(CastopodCredentials $credentials, int $episodeId): array
    {
        return $this->get($credentials, "/episodes/{$episodeId}");
    }

    // ── Analytics OP3 ────────────────────────────────────────────────────────

    public function getDownloadStats(CastopodCredentials $credentials): array
    {
        return $this->getOp3($credentials, '/queries/show-download-counts', [
            'showUuid' => $credentials->op3ShowUuid,
        ]);
    }

    public function getDownloadsOverTime(CastopodCredentials $credentials): array
    {
        return $this->getOp3($credentials, '/queries/aggregate-downloads', [
            'showUuid' => $credentials->op3ShowUuid,
            'format'   => 'json',
        ]);
    }

    public function getTopApps(CastopodCredentials $credentials): array
    {
        return $this->getOp3($credentials, '/queries/top-apps-for-show', [
            'showUuid' => $credentials->op3ShowUuid,
        ]);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function get(CastopodCredentials $credentials, string $path, array $query = []): array
    {
        $url     = rtrim($credentials->url, '/').'/api/v1'.$path;
        $options = [
            'auth_basic' => [$credentials->username, $credentials->password],
            'timeout'    => 10,
        ];

        if ($query) {
            $options['query'] = $query;
        }

        return $this->httpClient->request('GET', $url, $options)->toArray();
    }

    private function getOp3(CastopodCredentials $credentials, string $path, array $query = []): array
    {
        if (!$credentials->hasOp3()) {
            throw new \RuntimeException(
                'OP3 credentials (API key + Show UUID) are not configured on this connector.'
            );
        }

        return $this->httpClient->request('GET', self::OP3_API.$path, [
            'headers' => ['Authorization' => 'Bearer '.$credentials->op3ApiKey],
            'query'   => $query,
            'timeout' => 15,
        ])->toArray();
    }
}
