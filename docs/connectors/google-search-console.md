# Google Search Console Connector

This connector queries the **Google Search Console** API via a Google Cloud service account to expose your site's organic search performance.

---

## Prerequisites

| Field | Description |
|---|---|
| **Property URL** | The exact URL of the property as it appears in GSC |
| **Service account JSON key** | JSON file downloaded from Google Cloud Console |

---

## Property URL format

Google Search Console distinguishes two property types:

| Type | Format | Example |
|---|---|---|
| **URL prefix** | Full URL with trailing slash | `https://www.example.com/` |
| **Domain** | `sc-domain:` prefix | `sc-domain:example.com` |

!!! warning
    The URL must **exactly** match the one shown in your GSC property list,
    including `www.`, the protocol (`http` vs `https`), and the trailing slash.

---

## Creating a Google Cloud service account

### Step 1 - Create or select a Google Cloud project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project or select an existing one

### Step 2 - Enable the Search Console API

1. In the menu, go to **APIs & Services → Library**
2. Search for **Google Search Console API**
3. Click **Enable**

### Step 3 - Create the service account

1. Go to **APIs & Services → Credentials**
2. Click **Create credentials → Service account**
3. Give it a name (e.g. `mcp-portal-gsc`)
4. Leave the role blank (permissions are managed in GSC, not in Cloud IAM)
5. Click **Done**

### Step 4 - Download the JSON key

1. In the service account list, click the one you just created
2. **Keys** tab → **Add key → Create new key**
3. Select **JSON** format and click **Create**
4. The JSON file downloads automatically - keep it in a safe place

The file looks like this:

```json
{
  "type": "service_account",
  "project_id": "my-project-123",
  "private_key_id": "abc123…",
  "private_key": "-----BEGIN RSA PRIVATE KEY-----\n…\n-----END RSA PRIVATE KEY-----\n",
  "client_email": "mcp-portal-gsc@my-project-123.iam.gserviceaccount.com",
  "client_id": "…",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token"
}
```

### Step 5 - Grant access to the GSC property

1. Open [Google Search Console](https://search.google.com/search-console)
2. Select the target property
3. Go to **Settings → Users and permissions**
4. Click **Add user**
5. Paste the service account email address (`client_email` from the JSON)
6. Choose the **Full owner** or **Restricted owner** role

!!! info "Minimum role required per tool"
    | GSC Role | Accessible tools |
    |---|---|
    | **Reader** | `get_search_performance`, `get_top_queries`, `get_top_pages`, `get_performance_by_device`, `get_performance_by_country`, `get_low_ctr_queries`, `get_sitemaps` |
    | **Owner** | All of the above + `inspect_url` |

    The `inspect_url` tool uses the URL Inspection API which requires the **Owner** role in GSC.

---

## Configuration in MCP Portal

1. Open your project → **Connect a server** → **Google Search Console**
2. Enter the **Property URL** (exactly as it appears in GSC)
3. Paste the entire content of the JSON file into the **Service account JSON key** field
4. Click **Test connection** - the property name is displayed if everything is correct
5. Save

!!! tip
    The JSON key is encrypted (AES-256) before being stored in the database.
    It is never stored in plain text.

---

## Exposed MCP tools

### Search performance

#### `get_search_performance`

Aggregated global metrics over the period (no dimension breakdown).

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |

**Returns**: `clicks`, `impressions`, `ctr`, `position`

---

#### `get_top_queries`

Most-clicked queries over a period.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `10`) |

**Returns**: array of `{ keys: [query], clicks, impressions, ctr, position }`

---

#### `get_top_pages`

Most-clicked pages from Google Search over a period.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of results (default: `10`) |

**Returns**: array of `{ keys: [page_url], clicks, impressions, ctr, position }`

---

### SEO audit

#### `get_performance_by_device`

Performance breakdown by device type - useful for detecting mobile/desktop gaps (Mobile First Indexing).

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |

**Returns**: array of `{ keys: [DESKTOP|MOBILE|TABLET], clicks, impressions, ctr, position }`

---

#### `get_performance_by_country`

Performance breakdown by country - identifies under-exploited markets.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of countries (default: `25`) |

**Returns**: array of `{ keys: [ISO3_country_code], clicks, impressions, ctr, position }`

---

#### `get_low_ctr_queries`

Returns up to 500 queries with all their metrics. Designed to identify queries with **high impression volume but low CTR** - opportunities for rewriting `<title>` tags and meta descriptions.

!!! info
    The GSC API does not support server-side filtering on numeric metrics.
    Sorting and selection of low-CTR queries is performed by the AI client from the returned data.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `startDate` | `string` | yes | Start date - `YYYY-MM-DD` format |
| `endDate` | `string` | yes | End date - `YYYY-MM-DD` format |
| `limit` | `integer` | no | Max number of queries (default: `500`, max: `500`) |

**Returns**: array of `{ keys: [query], clicks, impressions, ctr, position }`

---

#### `inspect_url` *(requires Owner role in GSC)*

Inspects a URL via Google's **URL Inspection API**: indexing status, last crawl date, canonical URL detected by Google, coverage, structured data, mobile-friendliness.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `url` | `string` | yes | Full URL to inspect (must belong to the configured property) |

**Returns**: `inspectionResult` object including:

- `indexStatusResult.coverageState` - `Submitted and indexed`, `Crawled - currently not indexed`, `Discovered - currently not indexed`, etc.
- `indexStatusResult.robotsTxtState` - whether the page is blocked by `robots.txt`
- `indexStatusResult.indexingState` - whether the page is excluded via a `noindex` tag
- `indexStatusResult.googleCanonical` - the canonical URL retained by Google
- `mobileUsabilityResult` - mobile compatibility issues
- `richResultsResult` - validity of detected structured data

---

### Sitemaps

#### `get_sitemaps`

List of sitemaps submitted to Google Search Console.

No parameters required.

**Returns**: list of sitemaps with `path`, `lastSubmitted`, `isPending`, `isSitemapsIndex`, `type`, `warnings`, `errors`

---

## Troubleshooting

| Error | Likely cause |
|---|---|
| `Invalid service account private key` | The JSON is malformed or truncated |
| `Unable to obtain access token` | The Search Console API is not enabled on the GCP project, or the key has been revoked |
| `403 Forbidden` on `inspect_url` | The service account does not have the **Owner** role on the GSC property |
| `403 Forbidden` on other tools | The service account does not have access to the GSC property (check step 5) |
| `404 Not Found` | The property URL does not match any property accessible by this service account |

!!! tip "Property URL not found"
    If you get a 404, verify that the URL copied into MCP Portal is **identical**
    to the one shown in GSC (case-sensitive to `www.`, protocol, and trailing slash).
