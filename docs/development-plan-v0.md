# SASD API Server – V0.1.0 Development Plan

> **Language:** **English (default)** | [Deutsch](development-plan-v0.de.md)  
> **Status:** planned work, no coding/testing success claimed · **Updated:** November 1, 2026.  
> Based on [V0 requirements](requirements-v0.md), [V0 technical specification](technical-specification-v0.md), [V0 architecture](architecture-v0.md), and [Roadmap](../ROADMAP.md).  
> Verification: [Test strategy](test-strategy.md) · [Release and acceptance](release-acceptance.md).

## 1. Objective and engineering discipline

Implement a small **REST/JSON CRUD server using PHP 8.4 and MariaDB/MySQL**. One reviewed codebase can be deployed repeatedly with independent credentials, data stores and approved resources. The first release contains **17 V0 mandatory functions** and must pass **10 acceptance scenarios**.

Each development slice is independently reviewable: **analyze requirements and security → implement a small change → write positive and negative tests → update documentation → review results**. Do not develop speculative V1 features simply because future requirements are already documented.

## 2. Definition of Ready for each slice

- A precise goal, V0-F requirement IDs and observable acceptance conditions.
- Known affected boundaries: HTTP, services, domain, repository, config and security.
- Public API/error-format changes explicitly described.
- At least one positive test and one meaningful negative test; integration/deployment checks where relevant.
- No unresolved blocker around credentials, TLS or data exposure.
- No need to commit production records, actual keys or confidential configuration.

## 3. Shared technical rules

**Source:** PHP 8.4, UTF-8, understandable PSR-oriented conventions and clearly named namespaces. Add PHPDoc for contracts and useful comments for non-obvious behavior and security/architecture decisions. Explain **why**, not every obvious line.

**Layers:** a small front controller; HTTP/Controller translates requests; the Service orchestrates operations; ResourceRegistry owns the allowlist; Repository Port abstracts persistence; MariaDB Adapter executes safe SQL. Controllers must not call PDO directly.

**Security:** invalid configuration must **fail closed**. Bind values in SQL and select identifiers only from validated server-defined resources. Never treat any database table as publicly accessible by default. Keep secrets outside the document root and out of responses/logs.

**Workflow:** small thematic branch or PR, requirement and risk references, syntax checks and actual tests before merge. No silent security compromises, unrelated refactoring or speculative abstractions.

## 4. Slice 00 — Hosting and security prerequisites

**Problem:** The hosting target can restrict URL rewriting and ignore some `.htaccess` settings. The deployment must not depend on them to protect secrets.

**Work:** determine real PHP 8.4/PDO MySQL/MariaDB availability, TLS enforcement and routing. Verify that **private directories are physically outside the public document root**. Attempt direct HTTP GETs to test config/key/log paths; each must fail. Document the trusted reverse-proxy boundary, URL canonicalization and `index.php?route=...` fallback.

**Deliverables:** host capability matrix (versions, PDO, rewrite, PATH_INFO, private storage); direct-secret-access negative tests; approved initial schema, public base path and security decision records.

**Stop condition:** no production deployment if private files or TLS cannot be protected reliably. Do not introduce an unsafe demonstration fallback.

**Gate:** hosting prerequisites reviewed and architecture decisions recorded; preparation for AT-V0-01.

## 5. Slice 01 — Project skeleton and repeatable tests

**Objective:** Build a minimal testable starting point **without** a database-facing public API.

**Work:** Composer/autoloading or a justified equivalent; `src/Http`, `Application`, `Domain`, `Contracts`, `Security`, `Infrastructure`, and `tests`; secret-free example configuration; ignore local secrets, logs and database files; isolated test fixture.

**Evidence:** PHP syntax check, initial real unit test, documented local bootstrap; fresh developer can reproduce the setup. Do not assume a composer.json exists until it is created and validated.

## 6. Slice 02 — Configuration, routing, and HTTP basics

**Requirements:** V0-F01, F06, F09, plus relevant V0-F10/F12/F13.

**Work:** bootstrap, request/response objects, JSON parsing and error envelopes, request ID, `/api/v0` router and query-fallback. ConfigLoader validates schema version 1, required settings, reserved paths, resources, methods and fields. No route may expose unconfigured tables.

**Example:** `GET /api/v0/health` returns non-sensitive status, while all data endpoints remain unavailable unless security configuration and key verification succeed.

**Negative tests:** traversal, double encoding, unknown resource, invalid JSON/content type, spoofed Host/Forwarded headers, prohibited methods and invalid configuration.

**Gate:** both routing modes have identical HTTP/policy behavior; failed config does not publish data.

## 7. Slice 03 — API key, TLS and access policy

**Requirements:** V0-F06, F11–F13.

**Work:** `X-API-Key` authentication with installation-specific high-entropy secret, safe digest comparison, protected key source; HTTPS enforcement and trusted proxy checks; method/resource/field/filter policy; request-size limits; optional explicitly allowed CORS origins.

**Positive test:** valid technical key reaches only allowed resources and operations.

**Negative tests:** missing/wrong/revoked/foreign-instance key, forbidden table/column, HTTP instead of HTTPS, spoofed proxy metadata. Ensure responses and logs never contain the key.

**Gate:** no unauthenticated or unauthorized request can reach data access.

## 8. Slice 04 — Read-only MariaDB CRUD

**Requirements:** V0-F02 and F12.

**Work:** narrow `RecordRepository` interface, `MariaDbPdoRepository` and PDO factory; typed record ID, `GET collection` and `GET by ID`; allowlisted SELECT projection; consistent list/record envelopes. Use a reproducible MariaDB test schema, not production records.

**Tests:** existing/missing ID, empty/nonempty list, unknown table/field, injection in ID and controlled DB unavailability.

**Gate:** read contracts pass, unapproved fields never appear, and HTTP code contains no direct SQL.

## 9. Slice 05 — Write CRUD

**Requirements:** V0-F03–F05, F09 and F12.

**Work:** POST, PUT, PATCH, DELETE; validators for required/nullable/readonly fields and type limits; local transaction per request; neutral unique-/foreign-key conflict categories. PUT replaces the complete client-writable representation; PATCH only supplied fields; IDs/system fields stay protected.

**Negative tests:** missing required field, unknown/readonly field, empty PATCH, missing record, foreign-key failure, rollback and unchanged update (`rowCount() = 0` is not automatically a missing record).

**Gate:** AT-V0-03 and related integrity checks pass.

## 10. Slice 06 — Filters, sorting, pagination

**Requirements:** V0-F07–F08.

**Work:** `filter[field]=value`, multiple equality filters joined with AND; `sort=field` and `sort=-field`, bound `limit`/`offset`, deterministic key order; optional bounded `limit+1` approach for `hasMore`.

**Negative tests:** unknown/private filter, unknown sort, negative/too-large page values, SQL-like input, oversized query and absence of accidental unfiltered fallback.

**Gate:** AT-V0-04 passes, including query-budget and injection checks.

## 11. Slice 07 — Logging, health, isolated instances, documentation

**Requirements:** V0-F10, F14–F17.

**Work:** UTC JSONL logs with request IDs, status and safe error categories; no credentials, bodies or bound SQL data. Minimal public health, operator-only detailed diagnostics by review. Deploy two instances from the same commit with different URLs, credentials, DBs, logs and resources; document backups, restores and hosting instructions.

**Tests:** valid operations on both installations; cross-instance keys denied; direct private file URLs blocked; DB failure produces safe failure rather than empty success.

**Gate:** AT-V0-08–AT-V0-10.

## 12. Slice 08 — Final release acceptance

Code is **not complete** until all V0-F01–F17 requirements are fulfilled and all AT-V0-01–AT-V0-10 cases pass.

| Acceptance ID | Evidence |
|---|---|
| AT-V0-01 | Fresh install and routing modes |
| AT-V0-02 | Collection/record GET; empty list and 404 |
| AT-V0-03 | POST/PUT/PATCH/DELETE with persistence |
| AT-V0-04 | Equality filters, sorting and pagination |
| AT-V0-05 | Resource, field and method allowlists |
| AT-V0-06 | Correct, wrong, revoked and foreign instance API keys |
| AT-V0-07 | SQL injection, types and request limits |
| AT-V0-08 | Two completely isolated installations |
| AT-V0-09 | Health, sanitized logging and DB failures |
| AT-V0-10 | Reproducible documentation and automated tests |

**Acceptance package:** source commit/tag candidate, test environment and version matrix, actual test logs, negative security results, real hosting test, restore proof, known limitations and approval. Do not invent passed tests or accept V0 solely from a CRUD demo.

## 13. Requirement traceability and separation from V1

| Slices | Primary scope | Verification |
|---|---|---|
| 00–03 | F01, F06, F09–F13 | Routing, config, policies, key and TLS |
| 04 | F02, F12 | Read, SQL and field allowlist |
| 05 | F03–F05, F09 | Writes, validation, constraints |
| 06 | F07–F08 | Paging, filters and query limits |
| 07–08 | F10, F14–F17 | Operations, isolation and full acceptance |

Do **not** move SQLite, RBAC, OpenAPI or joins into V0 prematurely. The V0 repository-port boundary prepares for V1 without pretending those features already exist.

## 14. GitHub working method

Represent each slice as a small issue/PR package with goals, requirement IDs, **non-goals**, test cases and necessary documentation updates. Review whether (a) resource access is still explicitly controlled, (b) new attack paths exist, (c) HTTP/domain/SQL layers remain separate, (d) code is readable and documented, and (e) the result can be reproduced.

Progress belongs to real issues, commits, tests and acceptance reports; the roadmap contains planning and gates, not invented completion percentages.

**Current status:** a development plan, not implemented or tested slices.
