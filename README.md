<p align="center">
  <img src="public/images/logo.svg" alt="MCP Portal" width="80">
</p>

# MCP Portal

MCP Portal is a self-hosted web application that exposes your analytics and data tools as **MCP servers** ([Model Context Protocol](https://modelcontextprotocol.io)), ready to be consumed by AI clients such as Claude Desktop or any MCP-compatible client.

## How it works

```
AI Client (Claude, Cursor…)
        │  JSON-RPC 2.0 over HTTP
        ▼
  /api/mcp/{token}           ← one endpoint per MCP server
        │
        ▼
  Connector (Matomo, GSC…)   ← calls the third-party API
        │
        ▼
  Structured response
```

Each **project** groups one or more **MCP servers**. Each server is linked to a connector, has a unique access token, and exposes a set of ready-to-use tools for the AI client.

## Available connectors

| Connector | Exposed tools |
|---|---|
| **Castopod** | Podcasts & episodes metadata, OP3 download stats, downloads over time, top listening apps |
| **Google Search Console** | Search performance, top queries & pages, performance by device/country, low-CTR queries, URL inspection, sitemaps |
| **Matomo** | Visit stats, page views, bounce rate, real-time visitors, traffic sources, search keywords, site search, outlinks |

## Requirements

- PHP 8.2+
- SQLite (default) or PostgreSQL / MySQL / MariaDB
- Composer

## Installation

```bash
git clone https://github.com/Progi1984/mcp-portal.git
cd mcp-portal

composer install

# Copy and edit the environment file
cp .env .env.local
# Set APP_SECRET and DATABASE_URL in .env.local

php bin/console doctrine:migrations:migrate
php bin/console server:start
```

## Security

Each MCP endpoint is protected by two independent secrets:

- **Access token** - part of the URL (`/api/mcp/{token}`). Regenerating it immediately invalidates the old endpoint URL.
- **Client secret** - sent as an `Authorization: Bearer {secret}` header. Independently regenerable.

Both are displayed on the project page and can be rotated at any time without touching the connector credentials.

## Documentation

Full documentation is available in the `docs/` directory and can be served locally with [MkDocs Material](https://squidfunk.github.io/mkdocs-material/):

```bash
pip install mkdocs-material
mkdocs serve   # http://127.0.0.1:8000
```

Topics covered: getting started, connector setup guides, MCP protocol reference.

## Tech stack

- [Symfony](https://symfony.com/) 7.4
- [Doctrine ORM](https://www.doctrine-project.org/) with UUID primary keys
- [Twig](https://twig.symfony.com/) templates
- SQLite / PostgreSQL

## License

[MIT](LICENSE) - Copyright © 2026 Progi1984
