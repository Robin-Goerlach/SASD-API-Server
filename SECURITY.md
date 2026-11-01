# Security Policy – SASD API Server

> **Language:** **English (default)** | [Deutsch](SECURITY.de.md)

> **Project status:** As of 2026-11-01, V0 and V1 are documented designs, not accepted releases. This document describes intended security handling; it is **not** evidence of a completed security audit or supported production service.

## 1. Supported versions

| Version | Status | Security support |
|---|---|---|
| V0.1.0 | Planned | Not released; no production support claim |
| V1.0.0 | Planned | Not released; no production support claim |
| Future tagged releases | To be specified | Support period and policy will be documented per release |

Do not treat an untagged draft or architecture document as a tested, production-ready API server.

## 2. Reporting a vulnerability privately

**Please do not publish exploitable vulnerabilities, credentials, private keys, or personal data in a public GitHub issue or pull request.**

If the repository enables GitHub's private vulnerability reporting, use the repository **Security → Report a vulnerability** workflow. If that option is not enabled, request a private communication channel from the repository maintainers **without including the exploit details in a public message**. Once a private channel is established, provide the affected version/commit, sanitized reproduction steps, possible impact, and a proposed fix if available.

The maintainers will evaluate reports as project capacity allows. **No response time or security-support SLA is promised.** Coordinated disclosure should avoid exposing users before a mitigation or suitable disclosure plan has been agreed.

## 3. Security architecture and expected controls

### 3.1 Data-exposure prevention

The API must use explicit allowlists for published resources, HTTP methods, readable/writable fields, filter/sort columns, and later role/relationship rules. **Deny by default** is mandatory. Merely adding a table to a database must not automatically create a public API endpoint.

### 3.2 Safe SQL processing

SQL values must use parameter binding; SQL identifiers and operators must be selected only from trusted, validated server-side definitions. HTTP clients must never be permitted to submit free SQL, DDL, arbitrary filesystem paths, or server script code. Write routes must have correctly scoped target selection and transaction handling.

### 3.3 API credentials and end-user boundaries

V0 uses an installation-specific high-entropy API key sent through an HTTP header; V1 plans multiple key identities with revocation and roles. Keys are stored in protected sources, never in URLs, public configuration, logs, or issue descriptions.

**Important:** An API key built into public browser JavaScript, a distributed mobile app, or a widely distributed desktop client is **not a secure hidden secret**. Such clients require a trusted backend intermediary or an explicitly designed user-authentication layer. CORS is not authentication.

### 3.4 TLS and proxy trust

Production endpoints must require HTTPS. Only verified server/provider proxy information may be trusted; a freely spoofed `X-Forwarded-Proto` value does not prove that a request used TLS. A redirect alone does not protect credentials already transmitted over HTTP.

### 3.5 Webroot and private data

Configuration, DB credentials, keys, logs, internal SQLite files, and sensitive backups must reside **outside the publicly served document root**. Protection must not depend exclusively on `.htaccess` rules, which may be ineffective on some shared hosts. If the host cannot provide this isolation, the deployment must not be approved for production.

### 3.6 Safe errors and operational visibility

Clients receive consistent error codes without internal SQL, stack traces, credentials, file paths, or private metadata. Technical logs minimize personal data and redact secrets. A public health endpoint returns only non-sensitive state; protected diagnostics require operator authorization.

### 3.7 V1-specific controls

- Role-based access applies to resource, field, filter, sorting, view, relations, OpenAPI, and optional record scope.
- Data changes and required audit entries must commit together in the same local database transaction.
- Rate-limiting must not silently fail open when its safe persistent store becomes unavailable.
- SQLite files require controlled paths, permissions, foreign-key enforcement, and tested lock semantics.
- Configuration migrations must not generate permissive default roles or broaden resource exposure.

## 4. Handling incidents

1. Contain the affected installation, disable compromised keys, and restrict exposure.
2. Preserve only necessary, properly secured evidence such as sanitized request IDs and logs.
3. Identify affected resources and potential impact without publishing secret material.
4. Prepare and test a fix, including negative regression tests.
5. Restore from a verified backup if needed; rotate secrets before restoring normal access.
6. Coordinate relevant notifications and any legal obligations with the responsible operator.

The [deployment and recovery runbook](docs/deployment-operations.md), [risk register](docs/risk-register.md), and [test strategy](docs/test-strategy.md) provide the intended verification approach.

## 5. Security release criteria

A release is blocked by any unresolved critical issue that can expose private files, bypass authentication/authorization, execute arbitrary SQL, leak keys, or cause unaudited protected writes. Release evidence must include actual tests rather than solely static document descriptions. See [Release and acceptance](docs/release-acceptance.md).

---
This policy is updated when the project's actual released version and maintainer reporting options become known.
