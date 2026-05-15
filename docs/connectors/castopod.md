# Castopod Connector

This connector queries the REST API of your **Castopod** instance (open-source podcast hosting platform) to expose your podcasts and episodes to your AI client.

---

## Prerequisites

| Field | Description | Example |
|---|---|---|
| **Instance URL** | Root URL of your Castopod instance | `https://podcasts.example.com` |
| **Username** | Administrator account username | `admin` |
| **Password** | Account password | `…` |

---

## Enabling the REST API in Castopod

The REST API is disabled by default. You must enable it in your instance's `.env` file:

```ini
restapi.enabled=true
restapi.basicAuth=true
restapi.basicAuthUsername=YOUR_USERNAME
restapi.basicAuthPassword=YOUR_PASSWORD
```

!!! warning
    The username and password configured in `.env` can differ from your Castopod account credentials.
    This is a dedicated API credential pair.

After modifying `.env`, restart your Castopod instance.

---

## Configuration in MCP Portal

1. Open your project → **Connect a server** → **Castopod**
2. Enter the URL of your Castopod instance
3. Enter the username and password configured in `.env`
4. Click **Test connection** - the number of podcasts is displayed if everything is correct
5. Save

!!! tip
    Credentials are encrypted (AES-256) before being stored in the database.

---

## Analytics with OP3 (optional)

The Castopod REST API is focused on **content management** (podcasts and episodes). For analytics, Castopod integrates with **OP3** (Open Podcast Prefix Project), a third-party service that collects download statistics and exposes them via a separate API.

### OP3 prerequisites

| Field | Description | Where to find it |
|---|---|---|
| **OP3 API Key** | Bearer token for the op3.dev API | [op3.dev/api/keys](https://op3.dev/api/keys) (account required) |
| **OP3 Show UUID** | Your podcast's identifier on OP3 | OP3 page URL for your show: `op3.dev/show/{uuid}` |

### Enabling OP3 in Castopod

1. In the Castopod admin, open your podcast
2. Go to **Settings** → **Analytics**
3. Enable the **OP3** integration
4. Once enabled, downloads will be routed through the `op3.dev/e/` prefix and counted

### Configuration in MCP Portal

When creating or editing a Castopod MCP server, the **OP3 Analytics** section is optional. If you provide the API key and Show UUID, three additional tools will automatically be exposed in your MCP endpoint.

!!! tip
    Leave the OP3 fields empty if you do not need analytics - the content management tools remain available.

---

## Exposed MCP tools

### `list_podcasts`

Lists all podcasts hosted on the instance with their metadata.

No parameters required.

**Returns**: array of podcasts with `id`, `title`, `description`, `author`, `language`, `image`

---

### `get_podcast`

Returns the full details of a specific podcast.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `podcastId` | `integer` | yes | Numeric podcast identifier (visible in the admin URL) |

**Returns**: podcast object with all metadata, categories, social links

---

### `list_episodes`

Lists episodes with their metadata (title, short description, duration, publication date, status).

| Parameter | Type | Required | Description |
|---|---|---|---|
| `podcastId` | `integer` | no | Filter by podcast - all podcasts if omitted |
| `page` | `integer` | no | Page number for pagination (default: `1`) |

**Returns**: paginated episode array with `id`, `title`, `description`, `duration`, `published_at`, `guid`

---

### `get_episode`

Returns the full details of an episode: long description, episode notes, chapters, audio URL, season and episode number.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `episodeId` | `integer` | yes | Numeric episode identifier |

---

### OP3 tools (available only when OP3 is configured)

#### `get_download_stats`

Returns the total number of downloads over a period, with a breakdown by episode.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `days` | `integer` | no | Number of days to analyse (default: 30) |

**Returns**: total downloads, per-episode breakdown

---

#### `get_downloads_over_time`

Returns the evolution of downloads day by day over a period.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `days` | `integer` | no | Number of days to analyse (default: 30) |

**Returns**: array of `{ date, downloads }` data points for plotting an audience curve

---

#### `get_top_apps`

Returns a ranking of the podcast apps most used by listeners to download episodes.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `days` | `integer` | no | Number of days to analyse (default: 30) |

**Returns**: ranking of `{ app, downloads, percentage }` - useful for targeting the most popular platforms

---

## Finding numeric IDs

Numeric identifiers (`podcastId`, `episodeId`) are visible in the Castopod admin interface URLs:

- Podcast: `/cp-admin/podcasts/**{id}**/episodes`
- Episode: `/cp-admin/podcasts/{podcastId}/episodes/**{id}**/edit`

---

## Troubleshooting

| Error | Likely cause |
|---|---|
| `401 Unauthorized` | The REST API is not enabled, or the `basicAuth` credentials in `.env` are incorrect |
| `404 Not Found` | The instance URL is incorrect, or the API is not accessible |
| `Connection refused` | The Castopod instance is unreachable from the MCP Portal server |
