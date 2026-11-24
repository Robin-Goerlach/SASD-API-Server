# PHP 8.4 reference implementation – first bootstrap slice

> **Language:** **English (default)** | [Deutsch](README.de.md)

**Status:** incomplete development skeleton, **not a production service** or a completed V0.1.0 release. It contains an explicitly configured plugin manager, private minimal technical logging and a public health endpoint. **It does not implement data CRUD, API-key validation, TLS enforcement, trusted-proxy policy or the promised hosting acceptance gates.** Do not deploy it for real data.

## Layout and deployment boundary

```text
implementations/php/
  autoload.php                Own-namespace PSR-4-style loader (no Composer required)
  webroot/index.php           Sole HTTP entry point; THIS is the HTTP document root
  src/Bootstrap.php           Composition root; constructed once per PHP request
  src/Config/                  Non-executable JSON configuration loader
  src/Contracts/               Small plugin factory, plugin and technical log ports
  src/Core/Plugin/             Trusted plugin registry and startup validation
  src/Http/                    Request normalization, router and JSON response
  src/Infrastructure/Logging/ Protected UTC JSONL technical log implementation
  config/plugins.example.json Example ONLY; no live secrets
  plugins/                     Language-specific optional extensions, not core
  tests/run.php                Dependency-free positive and negative tests
```

Only `webroot/` may be served to HTTP clients. The **actual** provider document root must point to that directory; its `index.php` is at the root of the served path. It does not rely on `.htaccess`, URL rewriting, Docker, Redis, a background worker, or a running Java/C# service. Secrets, instance config and logs must be **physically outside** the served document root; do not upload the entire source tree to a public webroot.

## Run a safe local test

```bash
cd implementations/php
php -v               # Tested using PHP 8.4.24
php tests/run.php     # Pure PHP assertions; temporary files outside webroot
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

For **local development only**, explicitly set private configuration and log paths (the example manifest contains no plugins or secrets):

```bash
cd implementations/php
export SASD_PLUGIN_CONFIG="$(pwd)/config/plugins.example.json"
export SASD_TECHNICAL_LOG="/tmp/sasd-api-development.jsonl"
php -S 127.0.0.1:8080 -t webroot webroot/index.php
# In another terminal: curl -i http://127.0.0.1:8080/api/v0/health
# Or: curl -i 'http://127.0.0.1:8080/index.php?route=/api/v0/health'
```

These local HTTP examples **are not a production TLS configuration**. The example JSON is deliberately explicit: `{"schemaVersion":1,"enabled":[]}`. Invalid/unreadable/missing configuration and unknown plugin IDs fail closed with HTTP 503; any data-resource route returns 404 in this incomplete slice. A failed protected log write forces 503. The emergency startup error channel uses PHP's configured `error_log`, whose hosting destination must be verified before production. Technical records contain only UTC time, request ID, method, HTTP status, event category and duration; no bodies, credentials, query strings or client IP addresses.

## Plugin safety

A plugin is enabled by **two independent acts**: an operator selects its ID in a **private non-executable JSON** configuration AND reviewed code registers its factory in `Bootstrap::create()` through the `trustedFactories` composition argument. **No factory is registered in the production entry point yet**, so operators cannot enable arbitrary packages by editing JSON. Only the trusted in-process PHP factory contract `1.0` exists. There is no auto-scanning, auto-loading from plugin directories, dynamic route registration, uploads or remote plugin installs. Each exclusive category (`logging`, `identity`, `storage`) permits at most one enabled provider; application-category modules are future work.

The core always retains its own minimal diagnostic log. A future logging plugin can provide advanced sinks, but must not suppress local boot-failure visibility. An identity plugin may verify identity later, but **the API core must continue to own resource, method, field and record authorization**. SQL/storage plugins will be a separate category and may not expose their own unsecured endpoints.

See [`docs/development-layout.md`](../../docs/development-layout.md) for cross-language structure and milestones. Before any real hosting deployment, complete V0-G0 security and hosting prerequisites and the V0 acceptance tests in the canonical specifications.
