<?php

namespace App\Service\Mcp;

use App\Enum\McpServerType;
use App\Service\CastopodClient;
use App\ValueObject\CastopodCredentials;

class CastopodMcpServer extends AbstractMcpServer
{
    public function __construct(private readonly CastopodClient $castopodClient)
    {
    }

    public function getSupportedType(): McpServerType
    {
        return McpServerType::Castopod;
    }

    public function getToolDefinitions(array $rawCredentials = []): array
    {
        $tools = $this->baseTools();

        $credentials = CastopodCredentials::fromArray($rawCredentials + [
            'url' => '', 'username' => '', 'password' => '',
        ]);

        if ($credentials->hasOp3()) {
            $tools = array_merge($tools, $this->op3Tools());
        }

        return $tools;
    }

    public function callTool(string $toolName, array $arguments, array $rawCredentials): string
    {
        $credentials = CastopodCredentials::fromArray($rawCredentials);

        $data = match ($toolName) {
            'list_podcasts' => $this->castopodClient->listPodcasts($credentials),
            'get_podcast' => $this->castopodClient->getPodcast(
                $credentials,
                (int) $arguments['podcastId'],
            ),
            'list_episodes' => $this->castopodClient->listEpisodes(
                $credentials,
                isset($arguments['podcastId']) ? (int) $arguments['podcastId'] : null,
                (int) ($arguments['page'] ?? 1),
            ),
            'get_episode' => $this->castopodClient->getEpisode(
                $credentials,
                (int) $arguments['episodeId'],
            ),
            'get_download_stats' => $this->castopodClient->getDownloadStats($credentials),
            'get_downloads_over_time' => $this->castopodClient->getDownloadsOverTime($credentials),
            'get_top_apps' => $this->castopodClient->getTopApps($credentials),
            default => throw new \InvalidArgumentException("Unknown tool: {$toolName}"),
        };

        return $this->jsonResponse($data);
    }

    // ── Tool definitions ──────────────────────────────────────────────────────

    private function baseTools(): array
    {
        return [
            [
                'name' => 'list_podcasts',
                'description' => 'Lists all podcasts hosted on the Castopod instance with their metadata (title, author, description, language).',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name' => 'get_podcast',
                'description' => 'Returns the full details of a podcast: title, description, author, artwork, categories, social links.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'podcastId' => ['type' => 'integer', 'description' => 'Numeric podcast identifier'],
                    ],
                    'required' => ['podcastId'],
                ],
            ],
            [
                'name' => 'list_episodes',
                'description' => 'Lists episodes of a podcast with title, short description, duration, publication date and status. Supports pagination.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'podcastId' => ['type' => 'integer', 'description' => 'Filter by podcast identifier (optional)'],
                        'page' => ['type' => 'integer', 'description' => 'Page number (default: 1)'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_episode',
                'description' => 'Returns the full details of an episode: long description, notes, audio URL, chapters, season, number.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'episodeId' => ['type' => 'integer', 'description' => 'Numeric episode identifier'],
                    ],
                    'required' => ['episodeId'],
                ],
            ],
        ];
    }

    private function op3Tools(): array
    {
        return [
            [
                'name' => 'get_download_stats',
                'description' => 'OP3 download statistics for this podcast: totals over 1, 7, 30 days and all-time. Bot-filtered data, IAB v2 compliant.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name' => 'get_downloads_over_time',
                'description' => 'OP3 download time series (monthly and weekly) - visualises audience growth over time.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name' => 'get_top_apps',
                'description' => 'Breakdown of downloads by listening app (Apple Podcasts, Spotify, AntennaPod, Pocket Casts…) over the last 3 months.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
        ];
    }
}
