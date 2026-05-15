<p align="center">
  <img src="../public/images/logo.svg" alt="MCP Portal" width="80">
</p>

# MCP Portal

MCP Portal is a web application that exposes your analytics and data tools as **MCP servers** (Model Context Protocol), ready to be consumed by AI clients such as Claude Desktop or any MCP-compatible client.

## How it works

```
AI Client (Claude, Cursor…)
        │  JSON-RPC 2.0
        ▼
  /api/mcp/{token}          ← MCP endpoint per server
        │
        ▼
  Connector (Matomo, GSC…)   ← calls the third-party API
        │
        ▼
  Structured response
```

Each **project** groups one or more **MCP servers**. Each server:

- is linked to a connector (Matomo, Google Search Console, …)
- has a unique access token used to authenticate requests to the endpoint
- exposes a set of ready-to-use tools for the AI client

## Available connectors

| Connector | What it exposes |
|---|---|
| [Matomo](connectors/matomo.md) | Visits, page views, traffic sources, real-time data |
| [Google Search Console](connectors/google-search-console.md) | Search performance, queries, pages, sitemaps |
| [Castopod](connectors/castopod.md) | Podcasts, episodes, OP3 download analytics |

## Documentation setup

```bash
pip install mkdocs-material
mkdocs serve        # local documentation at http://127.0.0.1:8000
mkdocs build        # generates the static site in site/
```
