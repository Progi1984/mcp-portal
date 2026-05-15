# Matomo Connector

This connector queries the API of your **Matomo** instance (self-hosted or cloud) to expose visit statistics to your AI client.

---

## Prerequisites

You need three pieces of information:

| Field | Description | Example |
|---|---|---|
| **Server URL** | Root URL of your instance | `https://analytics.example.com` |
| **API Token** | Matomo authentication token (32 hex characters) | `abc123def456…` |
| **Site ID** | Numeric identifier of the site in Matomo | `1` |

---

## Getting the API token

1. Log in to your Matomo instance as an administrator
2. Go to **Administration → Security → API Access**  
   *(or Personal menu → Security depending on the version)*
3. Copy the displayed **Authentication token**

!!! info "Minimum permissions"
    The token must belong to a user with at least the **View** role on the target site.
    A **View** token is sufficient - administrator access is not required.

!!! warning "Token sent via POST"
    The connector always sends the token in the **HTTP request body**
    (not in the URL), following Matomo's recommendations to prevent it from
    appearing in web server logs.

---

## Configuration in MCP Portal

1. Open your project → **Connect a server** → **Matomo**
2. Fill in the three fields
3. Click **Test connection** - the site name is displayed if everything is correct
4. Save

---

## Exposed MCP tools

### `get_visit_stats`

Global visit statistics over a period.

**Parameters**

| Name | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `period` | `string` | yes | Granularity: `day`, `week`, `month`, `year`, `range` |

!!! tip
    Use `period: range` to aggregate the entire `startDate → endDate` period into a single row.

**Returned data** (example Matomo keys): `nb_visits`, `nb_actions`, `nb_uniq_visitors`, `bounce_rate`, `avg_time_on_site`

---

### Pages

#### `get_top_pages`

Most visited pages over a period.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `10`) |

---

#### `get_entry_pages`

Landing pages - the first pages seen by visitors during their session. Identifies which URLs capture incoming traffic (particularly organic).

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `10`) |

**Returns**: URLs with `entry_nb_visits`, `entry_bounce_count`, `avg_time_on_page`

---

#### `get_exit_pages`

Exit pages - the last pages seen before visitors leave the site. Useful for detecting pages with a high drop-off rate.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `10`) |

**Returns**: URLs with `exit_nb_visits`, `avg_time_on_page`

---

#### `get_page_load_times`

Pages sorted by descending **average server generation time** (`avg_time_generation`). A proxy for server-side Core Web Vitals - identifies the slowest pages to serve.

!!! info
    `avg_time_generation` measures server response time (TTFB), not browser render time.
    For full Core Web Vitals (LCP, CLS, INP), combine with Google Search Console data.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `20`) |

---

### Traffic sources

#### `get_traffic_sources`

Aggregated view of traffic sources: direct, search engines, external referrers, social networks.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |

---

#### `get_search_keywords`

Organic keywords that brought traffic from search engines.

!!! warning "Google limitation"
    Google hides almost all search queries under `(not provided)` for logged-in users.
    Data is more complete for Bing, DuckDuckGo, and other engines.
    For Google keywords, use the **Google Search Console** connector instead.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `20`) |

---

#### `get_traffic_by_search_engine`

Breakdown of organic search traffic by engine (Google, Bing, DuckDuckGo, Ecosia, etc.).

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |

---

### Site search & outlinks

#### `get_site_search`

Queries typed by visitors in the site's internal search engine.

!!! info "Prerequisite"
    Internal search tracking must be configured in Matomo
    (**Administration → Measurables → Sites → Site settings → Site search**).

A strong signal of missing or hard-to-find content: if visitors search for "pricing" on the site, that information is not easily navigable.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `20`) |

---

#### `get_outlinks`

Outbound links clicked by visitors - useful for auditing which external sites receive traffic from yours.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `20`) |

---

### Real-time

#### `get_realtime_data`

Active visitors on the site over the last N minutes.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `lastMinutes` | `integer` | no | Time window in minutes (default: `30`) |

---

## Troubleshooting

| Error | Likely cause |
|---|---|
| `Unable to authenticate with the provided token` | Invalid token, or the instance requires the token via POST (already handled by this connector) |
| `Website id=X not found` | The site ID does not match any site accessible with this token |
| `Timeout` | The Matomo instance is slow or unreachable from the server |
