<?php

namespace App\Service\Mcp;

use App\Enum\McpServerType;
use App\Service\MatomoClient;
use App\ValueObject\MatomoCredentials;

class MatomoMcpServer extends AbstractMcpServer
{
    public function __construct(private readonly MatomoClient $matomoClient)
    {
    }

    public function getSupportedType(): McpServerType
    {
        return McpServerType::Matomo;
    }

    public function getToolDefinitions(array $rawCredentials = []): array
    {
        return [
            // ── General statistics ────────────────────────────────────────────
            [
                'name' => 'get_visit_stats',
                'description' => 'Visit statistics (visits, page views, bounce rate, average duration) over a period.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD). Only used when period="range"; ignored for day/week/month/year.'],
                        'period' => [
                            'type' => 'string',
                            'enum' => ['day', 'week', 'month', 'year', 'range'],
                            'description' => 'Granularity. Use "range" to aggregate the entire startDate→endDate interval; for other values only startDate is used.',
                        ],
                    ],
                    'required' => ['startDate', 'period'],
                ],
            ],
            [
                'name' => 'get_realtime_data',
                'description' => 'Active visitors on the site over the last N minutes (real-time data).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'lastMinutes' => ['type' => 'integer', 'description' => 'Time window in minutes (default: 30)'],
                    ],
                    'required' => [],
                ],
            ],

            // ── Pages ─────────────────────────────────────────────────────────
            [
                'name' => 'get_top_pages',
                'description' => 'Most visited pages over a period, with page views, unique visitors and average time.',
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
                'name' => 'get_entry_pages',
                'description' => 'Landing pages - first pages seen by visitors. Identifies which URLs capture incoming traffic (especially organic).',
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
                'name' => 'get_exit_pages',
                'description' => 'Exit pages - last pages seen before visitors leave the site. Useful for detecting pages with high drop-off rates.',
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
                'name' => 'get_page_load_times',
                'description' => 'Pages sorted by average server generation time (avg_time_generation) descending - identifies the slowest pages, a server-side proxy for Core Web Vitals.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of results (default: 20)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],

            // ── Traffic sources ────────────────────────────────────────────────
            [
                'name' => 'get_traffic_sources',
                'description' => 'Aggregated view of traffic sources: direct, search engines, external referrers, social networks.',
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
                'name' => 'get_search_keywords',
                'description' => 'Organic keywords that drove traffic from search engines. Note: Google hides most queries as "(not provided)".',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of results (default: 20)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'get_traffic_by_search_engine',
                'description' => 'Breakdown of organic search traffic by search engine (Google, Bing, DuckDuckGo, etc.).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],

            // ── Internal search & outlinks ─────────────────────────────────────
            [
                'name' => 'get_site_search',
                'description' => 'Internal search queries typed by visitors on the site (if internal search tracking is configured in Matomo). A strong signal of missing or hard-to-find content.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of results (default: 20)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
            [
                'name' => 'get_outlinks',
                'description' => 'Outbound links clicked by visitors - allows auditing which external sites traffic is sent to.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                        'endDate' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                        'limit' => ['type' => 'integer', 'description' => 'Max number of results (default: 20)'],
                    ],
                    'required' => ['startDate', 'endDate'],
                ],
            ],
        ];
    }

    public function callTool(string $toolName, array $arguments, array $rawCredentials): string
    {
        $credentials = MatomoCredentials::fromArray($rawCredentials);

        $data = match ($toolName) {
            'get_visit_stats' => $this->matomoClient->getVisitStats(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'] ?? null,
                $arguments['period'] ?? 'range',
            ),
            'get_realtime_data' => $this->matomoClient->getRealtimeData(
                $credentials,
                $arguments['lastMinutes'] ?? 30,
            ),
            'get_top_pages' => $this->matomoClient->getTopPages(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 10,
            ),
            'get_entry_pages' => $this->matomoClient->getEntryPages(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 10,
            ),
            'get_exit_pages' => $this->matomoClient->getExitPages(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 10,
            ),
            'get_page_load_times' => $this->matomoClient->getPageLoadTimes(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 20,
            ),
            'get_traffic_sources' => $this->matomoClient->getTrafficSources(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
            ),
            'get_search_keywords' => $this->matomoClient->getSearchKeywords(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 20,
            ),
            'get_traffic_by_search_engine' => $this->matomoClient->getTrafficBySearchEngine(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
            ),
            'get_site_search' => $this->matomoClient->getSiteSearch(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 20,
            ),
            'get_outlinks' => $this->matomoClient->getOutlinks(
                $credentials,
                $arguments['startDate'],
                $arguments['endDate'],
                $arguments['limit'] ?? 20,
            ),
            default => throw new \InvalidArgumentException("Unknown tool: {$toolName}"),
        };

        return $this->jsonResponse($data);
    }
}
