<?php

namespace App\Service\Mcp;

abstract class AbstractMcpServer implements McpServerInterface
{
    protected function jsonResponse(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
