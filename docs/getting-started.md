# Getting started

## 1. Create an account

Go to `/register` and create an account with your email address and a password.

## 2. Create a project

A project is the logical container for your MCP servers.

1. From the home page, click **New project**
2. Give it a name (e.g. `My e-commerce site`)
3. A UUID is automatically generated - it uniquely identifies the project

## 3. Connect an MCP server

1. Open the project page
2. Click **Connect a server**
3. Choose a connector from the list
4. Fill in the required credentials (see each connector's documentation)
5. Click **Test connection** to verify before saving
6. Save

## 4. Configure the AI client

Once the server is created, the **MCP endpoint URL** is displayed on the project page:

```
https://your-domain.com/api/mcp/{token}
```

### Example - Claude Desktop (`claude_desktop_config.json`)

```json
{
  "mcpServers": {
    "my-matomo": {
      "url": "https://your-domain.com/api/mcp/YOUR_TOKEN"
    }
  }
}
```

!!! warning "Token security"
    The access token is the only secret protecting the endpoint. Do not share it.
    If compromised, regenerate it from the project page - the old endpoint will
    immediately stop working.

## 5. Use the tools from the AI client

The AI client can now call the tools exposed by the MCP server. Example with Claude:

> "Give me the 10 most-clicked Google queries on my site last week."

Claude will automatically call the `get_top_queries` tool from the configured Google Search Console connector.
