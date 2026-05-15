<?php

namespace App\Service\Mcp;

use App\Enum\McpServerType;
use App\Service\GoogleSearchConsoleClient;
use App\ValueObject\GoogleSearchConsoleCredentials;

class GoogleSearchConsoleMcpServer extends AbstractMcpServer
{
    public function __construct(private readonly GoogleSearchConsoleClient $gscClient)
    {
    }

    public function getSupportedType(): McpServerType
    {
        return McpServerType::GoogleSearchConsole;
    }

    public function getToolDefinitions(array $rawCredentials = []): array
    {
        return [
            // ── Performance ───────────────────────────────────────────────────
            [
                'name' => 'get_search_performance',
                'description' => 'Overall search performance (clicks, impressions, CTR, average position) over a period.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'get_top_queries',
                'description' => 'Most clicked search queries over a period.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of results (default: 10)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'get_top_pages',
                'description' => 'Most clicked pages from Google Search over a period.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of results (default: 10)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],

            // ── SEO Audit ─────────────────────────────────────────────────────
            [
                'name' => 'get_performance_by_device',
                'description' => 'Clicks, impressions, CTR and average position broken down by device type (DESKTOP, MOBILE, TABLET) over a period.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'get_performance_by_country',
                'description' => 'Clicks, impressions, CTR and average position broken down by country over a period.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of countries returned (default: 25)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'get_low_ctr_queries',
                'description' => 'Returns up to 500 queries with their full metrics (clicks, impressions, CTR, position). Identifies queries with high impression volume but low CTR - opportunities to rewrite titles and meta descriptions.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of queries (default: 500, max: 500)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'inspect_url',
                'description' => 'Inspects a URL via Google\'s URL Inspection API: indexing status, crawl errors, detected canonical URL, structured data, mobile-friendliness. Requires Owner role on the GSC property.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => ['type' => 'string', 'description' => 'Full URL to inspect (must belong to the configured GSC property)'],
                    ],
                    'required' => ['url'],
                ],
            ],

            // ── Sitemaps ──────────────────────────────────────────────────────
            [
                'name' => 'get_sitemaps',
                'description' => 'List of sitemaps submitted to Google Search Console for this property.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ],
        ];
    }

    public function callTool(string $toolName, array $arguments, array $rawCredentials): string
    {
        $credentials = GoogleSearchConsoleCredentials::fromArray($rawCredentials);

        $data = match ($toolName) {
            'get_search_performance' => $this->gscClient->getSearchPerformance(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
            ),
            'get_top_queries' => $this->gscClient->getTopQueries(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 10,
            ),
            'get_top_pages' => $this->gscClient->getTopPages(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 10,
            ),
            'get_performance_by_device' => $this->gscClient->getPerformanceByDevice(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
            ),
            'get_performance_by_country' => $this->gscClient->getPerformanceByCountry(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 25,
            ),
            'get_low_ctr_queries' => $this->gscClient->getLowCtrQueries(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                min($arguments['limit'] ?? 500, 500),
            ),
            'inspect_url' => $this->gscClient->inspectUrl(
                $credentials,
                $arguments['url'],
            ),
            'get_sitemaps' => $this->gscClient->getSitemaps($credentials),
            default => throw new \InvalidArgumentException("Unknown tool: {$toolName}"),
        };

        return $this->jsonResponse($data);
    }
}
