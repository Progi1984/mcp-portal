<?php

namespace App\Service\Mcp;

use App\Enum\McpServerType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.mcp_server')]
interface McpServerInterface
{
    public function getSupportedType(): McpServerType;

    /**
     * @param array<string, mixed> $rawCredentials Credentials déchiffrés - permet d'adapter
     *                                              la liste d'outils aux capacités configurées.
     */
    public function getToolDefinitions(array $rawCredentials = []): array;

    /** @param array<string, mixed> $rawCredentials Credentials déchiffrés (tableau brut). */
    public function callTool(string $toolName, array $arguments, array $rawCredentials): string;
}
