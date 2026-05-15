# MCP Protocol

MCP Portal implements the [Model Context Protocol](https://modelcontextprotocol.io) over **Streamable HTTP** transport with synchronous **JSON-RPC 2.0** responses.

---

## Endpoint

```
POST /api/mcp/{token}
Content-Type: application/json
```

The `{token}` is the unique access token displayed on the project page. It serves as authentication - no additional `Authorization` header is required.

---

## Available methods

### `initialize`

Initial handshake, returns server metadata.

**Request**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {}
}
```

**Response**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2024-11-05",
    "capabilities": { "tools": {} },
    "serverInfo": {
      "name": "My Matomo server",
      "version": "1.0.0"
    }
  }
}
```

---

### `tools/list`

Returns the list of tools exposed by this MCP server.

**Request**
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/list",
  "params": {}
}
```

**Response** (example for the Matomo connector)
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "result": {
    "tools": [
      {
        "name": "get_visit_stats",
        "description": "Global visit statistics over a period.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "startDate": { "type": "string" },
            "endDate":   { "type": "string" },
            "period":    { "type": "string", "enum": ["day","week","month","year","range"] }
          },
          "required": ["startDate", "endDate", "period"]
        }
      }
    ]
  }
}
```

---

### `tools/call`

Calls a tool and returns the result.

**Request**
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "get_top_queries",
    "arguments": {
      "startDate": "2025-04-01",
      "endDate":   "2025-04-30",
      "limit":     5
    }
  }
}
```

**Response**
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "result": {
    "content": [
      {
        "type": "text",
        "text": "{ \"rows\": [ … ] }"
      }
    ]
  }
}
```

---

## Error codes

| Code | Meaning |
|---|---|
| `-32001` | Invalid token (HTTP 401) |
| `-32600` | Malformed JSON-RPC request |
| `-32601` | Unknown method or tool |
| `-32602` | Missing or invalid parameters |
| `-32000` | Connector error (third-party API unreachable, invalid credentials…) |

**Example error response**
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "error": {
    "code": -32601,
    "message": "Unknown tool: foo"
  }
}
```

---

## curl example

```bash
TOKEN="your_token_here"

curl -s -X POST "https://your-domain.com/api/mcp/$TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "get_visit_stats",
      "arguments": {
        "startDate": "2025-04-01",
        "endDate":   "2025-04-30",
        "period":    "range"
      }
    }
  }' | jq .
```
