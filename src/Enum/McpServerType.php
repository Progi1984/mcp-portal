<?php

namespace App\Enum;

enum McpServerType: string
{
    case Castopod = 'castopod';
    case GoogleSearchConsole = 'google_search_console';
    case Matomo = 'matomo';

    public function label(): string
    {
        return match ($this) {
            self::Castopod => 'Castopod',
            self::GoogleSearchConsole => 'Google Search Console',
            self::Matomo => 'Matomo',
        };
    }

    public function descriptionKey(): string
    {
        return match ($this) {
            self::Castopod => 'mcp_type.castopod.description',
            self::GoogleSearchConsole => 'mcp_type.google_search_console.description',
            self::Matomo => 'mcp_type.matomo.description',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Castopod => 'castopod.svg',
            self::GoogleSearchConsole => 'googlesearchconsole.svg',
            self::Matomo => 'matomo.svg',
        };
    }
}
