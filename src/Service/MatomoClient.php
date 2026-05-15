<?php

namespace App\Service;

use App\ValueObject\MatomoCredentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MatomoClient
{
    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function testConnection(MatomoCredentials $credentials): array
    {
        try {
            $data = $this->call($credentials, 'SitesManager.getSiteFromId', [
                'idSite' => $credentials->siteId,
            ]);

            if (isset($data['result']) && $data['result'] === 'error') {
                return ['success' => false, 'message' => $data['message'] ?? 'Matomo error'];
            }

            $siteName = $data['name'] ?? 'Site #'.$credentials->siteId;

            return ['success' => true, 'message' => "Connected - site: «{$siteName}»"];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── General statistics ────────────────────────────────────────────────────

    public function getVisitStats(MatomoCredentials $credentials, string $startDate, ?string $endDate, string $period): array
    {
        return $this->call($credentials, 'VisitsSummary.get', [
            'period' => $period,
            'date'   => $period === 'range' ? "{$startDate},{$endDate}" : $startDate,
        ]);
    }

    public function getRealtimeData(MatomoCredentials $credentials, int $lastMinutes = 30): array
    {
        return $this->call($credentials, 'Live.getCounters', [
            'lastMinutes' => $lastMinutes,
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function getTopPages(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->call($credentials, 'Actions.getPageUrls', [
            'period'       => 'range',
            'date'         => "{$startDate},{$endDate}",
            'filter_limit' => $limit,
        ]);
    }

    public function getEntryPages(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->call($credentials, 'Actions.getEntryPageUrls', [
            'period'       => 'range',
            'date'         => "{$startDate},{$endDate}",
            'filter_limit' => $limit,
        ]);
    }

    public function getExitPages(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->call($credentials, 'Actions.getExitPageUrls', [
            'period'       => 'range',
            'date'         => "{$startDate},{$endDate}",
            'filter_limit' => $limit,
        ]);
    }

    public function getPageLoadTimes(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 20): array
    {
        return $this->call($credentials, 'Actions.getPageUrls', [
            'period'                  => 'range',
            'date'                    => "{$startDate},{$endDate}",
            'filter_limit'            => $limit,
            'filter_sort_column'      => 'avg_time_generation',
            'filter_sort_order'       => 'desc',
            'hideColumns'             => 'logo',
        ]);
    }

    // ── Traffic sources ───────────────────────────────────────────────────────

    public function getTrafficSources(MatomoCredentials $credentials, string $startDate, string $endDate): array
    {
        return $this->call($credentials, 'Referrers.get', [
            'period' => 'range',
            'date'   => "{$startDate},{$endDate}",
        ]);
    }

    public function getSearchKeywords(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 20): array
    {
        return $this->call($credentials, 'Referrers.getKeywords', [
            'period'       => 'range',
            'date'         => "{$startDate},{$endDate}",
            'filter_limit' => $limit,
        ]);
    }

    public function getTrafficBySearchEngine(MatomoCredentials $credentials, string $startDate, string $endDate): array
    {
        return $this->call($credentials, 'Referrers.getSearchEngines', [
            'period' => 'range',
            'date'   => "{$startDate},{$endDate}",
        ]);
    }

    // ── Internal search & outlinks ────────────────────────────────────────────

    public function getSiteSearch(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 20): array
    {
        return $this->call($credentials, 'Actions.getSiteSearchKeywords', [
            'period'       => 'range',
            'date'         => "{$startDate},{$endDate}",
            'filter_limit' => $limit,
        ]);
    }

    public function getOutlinks(MatomoCredentials $credentials, string $startDate, string $endDate, int $limit = 20): array
    {
        return $this->call($credentials, 'Actions.getOutlinks', [
            'period'       => 'range',
            'date'         => "{$startDate},{$endDate}",
            'filter_limit' => $limit,
        ]);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function call(MatomoCredentials $credentials, string $method, array $params = []): array
    {
        $url = rtrim($credentials->url, '/').'/index.php';

        // token_auth sent via POST to avoid it appearing in server logs.
        // Some Matomo instances reject the token as a query string parameter.
        $response = $this->httpClient->request('POST', $url, [
            'body' => array_merge([
                'module'     => 'API',
                'method'     => $method,
                'idSite'     => $credentials->siteId,
                'token_auth' => $credentials->apiToken,
                'format'     => 'JSON',
            ], $params),
            'timeout' => 10,
        ]);

        return $response->toArray();
    }
}
