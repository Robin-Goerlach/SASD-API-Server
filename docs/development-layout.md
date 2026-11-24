# Implementation layout and staged plugin development

> **Language:** **English (default)** | [Deutsch](development-layout.de.md)  
> **Date:** 2026-11-24 · **Status:** first PHP bootstrap slice only; design does not imply released features.  
> **Related:** [V0 plan](development-plan-v0.md) · [V0 architecture](architecture-v0.md) · [Service boundaries](service-boundaries-and-integrations.md) · [ADR-0021](adr/0021-pluggable-database-adapters.md)

## 1. Why the repository is organized this way

The product has a single externally visible **REST/JSON** purpose, but the PHP, Java and C# servers run separately. They share **versioned HTTP contracts and black-box fixtures**, not source-level classes or dynamically loaded binaries. One install runs one chosen implementation with its own configuration, credentials, resources, database and technical logs. There is no mixed-language executable or general application-hosting runtime in V0.

```text
SASD-API-Server/
  docs/                     English-default architecture + German companions
  contracts/                Future language-neutral HTTP/JSON test fixtures
  implementations/
    php/                    ACTIVE: PHP 8.4 reference implementation
      webroot/index.php     Actual HTTP document root (must be isolated)
      autoload.php
      src/
        Bootstrap.php
        Http/
        Config/
        Contracts/
        Core/Plugin/
        Infrastructure/Logging/
      config/               Private operator configuration; example committed
      plugins/
        logging/            Future replaceable optional logging sink
        identity/           Future replaceable identity verification
        storage/            Future database-specific adapters
        application/        Future approved application modules; deferred
      tests/
    java/                   PLANNED ONLY; own core + own plugin folders
      core/
      plugins/{logging,identity,storage,application}/
    csharp/                 PLANNED ONLY; own .NET core + plugin folders
      core/
      plugins/{logging,identity,storage,application}/
```

The PHP core's minimal always-on technical logger lives under `src/Infrastructure/Logging/` deliberately: it is the **bootstrap safety mechanism**, not an optional plugin. Plugins belong under `plugins/` and implement **runtime-specific contracts**, never silently expanding core permissions. The future logging/identity plugin directories contain **documentation only** today.

## 2. Ordered implementation milestones

1. **First slice (now):** V0-M0/M1 foundations, trusted factory registration, strict startup config, minimal health, per-request sanitized local JSONL logs and isolated language/plugin directories. **No data CRUD**, TLS/security-policy claims or external dependencies.
2. **Next: logging extension plugin:** create a real PHP `logging` plugin under `implementations/php/plugins/logging/`, with a tested factory/capabilities and a sanitized event contract. Consider LogSink, MustelaLogAPI, Mustela and NimbusCore as **ideas**, not code or required network dependencies. Local core events must survive failures of an optional remote sink. A future V1 write-audit record must commit in the *same local DB transaction* as the data change; it is **not** an asynchronous logging plugin feature.
3. **Then: identity extension plugin:** create a PHP `identity` plugin under `implementations/php/plugins/identity/` for a carefully versioned verifier. Maintain V0 local API-key baseline and default-deny; no mandatory remote Auth Service and no user registration yet. **Authorization always stays in core.**
4. **Protected CRUD slices:** implement server-owned resource/method/field allowlists, TLS/proxy/IP policy decisions, validated HTTP bodies, `RecordRepository` port and **one** V0 MariaDB adapter. Only then publish protected data routes and complete V0-F01–F17 plus AT-V0-01–10, backed by verified IONOS/shared-hosting safety checks.
5. **Later:** accept V0 and then V1 SQLite/roles/audit/OpenAPI; only after relevant contract tests consider PostgreSQL, Oracle, Db2 and Firebird. Java second, C# later; each is a separate project milestone, not another language in one PHP process.

The requested plugin manager is introduced **earlier than the older V0 ADR proposal anticipated**, but in a deliberately narrow, static, reviewable form: no file scanning, marketplace or untrusted third-party code. ADR-0021 still treats additional databases as deferred and unimplemented. Formal acceptance of this sequencing and service boundaries remains an architecture governance task.

## 3. Security invariants and tests

- **Fail closed:** missing/invalid config, unknown/duplicate plugin IDs, incompatible factory version, mismatched instance and startup errors are rejected before the server exposes any data route. No hot reload.
- **Private paths:** document root is `implementations/php/webroot/` in the repository and the corresponding deployed HTTP-root; source, JSON manifest, log, credentials and backups physically reside elsewhere. No `.htaccess` dependence. Real shared-hosting layout/TLS must be verified and production deployment blocked until then.
- **Log minimization:** UTC, request ID, bounded method, status, fixed event and timing. Do not log passwords, tokens, raw headers, URLs/queries, DB values or IP addresses. Operator controls file permissions, rotation and retention. Startup fallback logging destination needs a host-specific review.
- **No plugin privilege escalation:** no direct plugin routes, no client-supplied class names, no adapter-selected SQL/schema, no bypass of security pipeline, no network loading. Do not load unknown PHP packages into a security-sensitive web process.
- **Verification:** `php -l`, `php implementations/php/tests/run.php`, local HTTP health and forbidden route smoke tests. These do **not** replace actual MariaDB, provider TLS, two-instance isolation, deployment penetration testing or release acceptance.

**Open gates:** V0-G0 private-directory/TLS checks on IONOS or each deployment provider; actual resource/key/TLS/access-policy implementation; protected DB behavior; backup/restore; optional logging-plugin security and identity contract review. This document records a development slice, **not** acceptance of V0 or V1.
