# Connectors

A connector is the bridge between an MCP server and a third-party API. It declares the available **tools** and handles calling the remote API whenever the AI client invokes one of them.

## Available connectors

| Connector | Authentication | Tools |
|---|---|---|
| [Castopod](castopod.md) | URL + Basic Auth (username / password) | 4 (+ 3 with OP3) |
| [Google Search Console](google-search-console.md) | JSON service account (OAuth2) | 8 |
| [Matomo](matomo.md) | URL + API Token + Site ID | 11 |

## Adding a connector

Each connector requires specific **credentials**, entered when creating the MCP server. These credentials are encrypted at rest (AES-256 via libsodium) and are never stored or displayed in plain text.

See each connector's page for exact prerequisites and configuration steps.
