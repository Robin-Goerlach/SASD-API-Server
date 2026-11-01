# SASD API Server – Roadmap

> **Language:** **English (default)** | [Deutsch](ROADMAP.de.md)  
> **Updated:** November 1, 2026 · **Status:** planning; no implemented or accepted milestones are claimed.  
> **Requirements:** [V0 (English)](docs/requirements-v0.md) · [V1 (English)](docs/requirements-v1.md)  
> **Technical designs (German):** [V0](docs/technical-specification-v0.md) · [V1](docs/technical-specification-v1.md) · [Architecture V0](docs/architecture-v0.md) · [Architecture V1](docs/architecture-v1.md)  
> **Implementation:** [V0 development plan](docs/development-plan-v0.md) · [Test strategy](docs/test-strategy.md) · [Release and acceptance](docs/release-acceptance.md)

## 1. Product vision and principles

Build **one lightweight, reusable open-source REST/JSON server** that can be deployed independently for multiple applications. The same reviewed source code should support different base URLs, configured resources, database credentials and per-instance security settings.

SASD API Server is **not** a plan to reproduce all of DreamFactory, PHP-CRUD-API or DB2Rest. Those systems offer [research examples](docs/reference-products-feature-catalog.md) and possible later ideas, not an automatically committed implementation backlog.

Our priorities are explicit:

1. **Finish and accept V0 before starting V1 development.**
2. **Secure by design:** explicit allowlists, no automatic database exposure, protected secrets, and no arbitrary client SQL.
3. **Shared-hosting compatible:** PHP 8.4 and MariaDB/MySQL first; no mandatory Redis, Docker, root, worker, or queue.
4. **Architecture before optimization:** HTTP/controller → service → repository port → database adapter, with readable, well-commented code.
5. **Small testable slices:** each milestone has a scope, negative security tests, and a verifiable completion gate.
6. **One codebase, independent instances:** no per-application fork and no cross-instance sharing of credentials or data.

## 2. Status model

A written roadmap is not evidence of implementation.

| Status | Meaning | Required evidence |
|---|---|---|
| **Planned** | Scope and acceptance criteria described | Specifications and roadmap |
| **In progress** | Work actually begun | Issue, branch, PR or commit |
| **Ready for acceptance** | Code and relevant tests are available | CI/test records and review |
| **Accepted** | Mandatory requirements and gates passed | Signed-off acceptance record and release |
| **Deferred** | Deliberately moved out of the release | Documented scope decision |

**Current position (November 1, 2026):** the design/documentation foundation exists. **No working V0 or V1 implementation, completed hosting check or accepted software release** is claimed. Estimated delivery dates, percentages and hours are intentionally not invented.

## 3. Overall development sequence

~~~mermaid
flowchart LR
    Docs["Requirements / Technical specs / Architecture"] --> G0["V0-G0<br/>Hosting and security review"]
    G0 --> Core["V0.1.0<br/>Small secure CRUD API"]
    Core --> Acceptance["V0 acceptance<br/>17 MUST + 10 scenarios"]
    Acceptance --> Stabilize["Stabilization / necessary fixes"]
    Stabilize --> V1["V1.0<br/>SQLite / policies / OpenAPI / audit"]
    V1 --> V1A["V1 acceptance<br/>22 MUST + 16 scenarios"]
    V1A --> Later["Future ideas<br/>separate approval"]
~~~

| Target | Content | Current status | Release gate |
|---|---|---|---|
| D0 — planning | Requirements, technical designs, architecture, roadmap, testing/operations plans | Documentation in progress | Consistency review; unresolved decisions recorded |
| **V0.1.0** | PHP/MariaDB, explicit single-record CRUD, simple queries, API key, independent instances | Planned | All 17 V0 functions and AT-V0-01–10 |
| V0.1.x | Correctness, security fixes and documentation; avoid feature creep | Planned | Regression and release checks |
| **V1.0** | Versioned API contract, SQLite, roles/keys, controlled joins/views, OpenAPI, audit, rate limits | Planned | 22 V1 MUST requirements, AT-V1-01–16 and both adapters |
| Later | Additional adapters, identity, specialty services | Not approved | Separate scope, ADR and test criteria |

Version numbers represent **planned targets**, not Git tags already published.

## 4. V0.1.0 — detailed milestones

### V0-G0 — Architecture, security and hosting decisions

**Goal:** Establish a safe deployment architecture before implementing public data endpoints.

**Tasks:** inspect real PHP/PDO/MariaDB versions on the target host; verify private files **outside the document root**; demonstrate reliable HTTPS enforcement and trusted proxy behavior; verify URL rewriting or the `index.php?route=...` fallback; decide key/credential storage, test schema, primary-key types and PUT/PATCH semantics. Track every consequential choice in the [ADR register](docs/adr/README.md).

**Deliverables:** reviewed deployment profile, key architecture decisions, actual HTTP negative checks for private files, validated database access.

**Blocking gate:** if private configuration and keys cannot be kept inaccessible to HTTP clients, or TLS cannot be enforced safely, **do not approve a production deployment**.

### V0-M0 — Repository and development environment

**Goal:** Establish a reproducible, understandable project skeleton.

**Tasks:** Composer/autoloading if appropriate; clear `src/` and `tests/` organization; secret-free example configuration; ignored private files; test fixtures; initial lint/unit checks. Avoid installing frameworks or dependencies without a real need.

**Gate:** another developer can reproduce a local baseline; no credentials committed; syntax and first tests are actually executed.

### V0-M1 — Protected HTTP core

**Goal:** Handle an HTTP request safely before database operations are exposed.

**Tasks:** `index.php`, bootstrap, route normalization, query-fallback, request ID, HTTPS policy, header-based API key, request-body limits, JSON parser and response/error model, configuration and resource/method allowlists, minimal public health.

**Negative tests:** missing/wrong key, invalid route, path traversal, double encoding, disallowed methods, malformed JSON, unsupported media types, errors leaking sensitive details.

**Gate:** unauthorized data requests cannot reach persistence and both routing modes apply identical checks.

### V0-M2 — Read-only MariaDB API

**Goal:** Retrieve exactly the approved records and fields.

**Tasks:** PDO connection, resource/field definitions, narrow repository contract, MariaDB adapter, GET list and GET by ID, consistent JSON envelopes, typed keys.

**Tests:** empty/nonempty list, missing ID, unauthorized table/field, SQL-injection attempt, database failure and neutral errors.

**Gate:** approved reads work, unapproved data stays inaccessible, and HTTP controllers contain no SQL.

### V0-M3 — Individual write operations

**Goal:** Safely create, replace, patch and delete one record.

**Tasks:** POST, PUT, PATCH, DELETE; required fields, nullable values, readonly IDs, explicit complete PUT vs partial PATCH, transaction per request, unique/FK constraint handling.

**Tests:** valid operations, empty PATCH, missing/wrong fields, unchanged UPDATE, missing record, FK/unique conflict and rollback.

**Gate:** every write is correctly scoped and returns the documented state or safe error.

### V0-M4 — Bounded list queries

**Goal:** Make everyday list queries useful without introducing a SQL interpreter.

**Tasks:** equality filters combined with AND, one approved sort column, limit/offset, deterministic default order, bounded `hasMore` computation.

**Negative tests:** unknown filter/sort, SQL-fragment input, invalid/oversized pagination, unrestricted fallbacks.

**Gate:** approved queries produce predictable results and cannot accidentally expand data access.

### V0-M5 — Operations and independent deployments

**Goal:** Prove the same server can serve separate applications safely.

**Tasks:** sanitized JSONL logs with UTC/request ID, minimal health and protected readiness design, deployment guide, two instances with different URLs/DBs/keys/logs/resource maps, backup and tested restore.

**Tests:** cross-instance keys fail; data is isolated; private files inaccessible by HTTP; database outage not misreported as an empty successful list.

**Gate:** two independent instances tested and operator procedures reproducible.

### V0-M6 — Security review and final V0 acceptance

**Goal:** Approve a complete, safely deployable V0.1.0.

**Required:** all **V0-F01–V0-F17**, all **AT-V0-01–AT-V0-10**, the security test matrix, an actual hosting check, a restore test, documentation review and a release report recording versions, commit, environment, limitations, and sign-off.

**Gate:** no critical security findings; only then tag/release the accepted revision. A working CRUD demo alone is not sufficient.

### 4.1 Milestone to acceptance mapping

| Milestone | Key V0 requirements | Acceptance focus |
|---|---|---|
| G0/M0 | hosting and nonfunctional constraints | private paths, PHP/PDO/TLS prerequisites |
| M1 | F01, F06, F09, F11–F13 | AT-V0-05–07 |
| M2 | F02, F06, F12 | AT-V0-02, 05 |
| M3 | F03–F05 | AT-V0-03 |
| M4 | F07–F08 | AT-V0-04 |
| M5 | F10, F14–F16 | AT-V0-01, 08, 09 |
| M6 | F17 plus all preceding requirements | AT-V0-01–10 |

## 5. V1.0 — expansion after V0 acceptance

The [V1 requirements](docs/requirements-v1.md), [technical specification](docs/technical-specification-v1.md) and [architecture](docs/architecture-v1.md) describe the intended end state. Features are ordered by dependency, not appearance.

| Slice | Planned outcome | Completion gate |
|---|---|---|
| **V1-P0** | Reproducible accepted V0 baseline | V0 functionality and security remain green |
| **V1-P1** | `/api/v1` contract, field aliases, configuration schema v2 | Contract tests and migration checks |
| **V1-P2** | Real SQLite adapter and common data-type normalization | Same CRUD/query/transaction contracts on MariaDB and SQLite |
| **V1-P3** | Controlled comparison/OR filters, projection, sorting, read-only views | Query limits, SQL injection and permission checks |
| **V1-P4** | Declared one-level read-only relationships | No field/count/relation leaks; bounded expansion |
| **V1-P5** | Several technical API keys, roles and revocation | Security, RBAC, and instance-isolation tests |
| **V1-P6** | Shared-hosting-compatible rate limits, transactional write audit, readiness | Fail-closed limiter and same-commit audit rollback |
| **V1-P7** | Authorized OpenAPI, curl/Java/.NET samples, configuration migration | Real endpoints and generated contract agree |
| **V1-P8** | Full release, security, hosting and recovery acceptance | 22 V1 MUST, AT-V1-01–16, both DB adapters |

### V1 SHOULD features — not default release blockers

| ID | Optional function | Decision rule |
|---|---|---|
| V1-F09 | Preconfigured read-only named SQL queries | Include only for a specific reporting need |
| V1-F14 | Server-enforced record-visibility constraints | Require comprehensive authorization/isolation tests |
| V1-F16 | Optimistic locking | Only for explicitly versioned resources with concurrent-writer tests |
| V1-F22 | Controlled controller extension points | Must preserve the same auth/policy/error/audit path |
| V1-F27 | Lightweight metrics | No mandatory Prometheus/Grafana/worker dependency |

Each SHOULD item becomes mandatory only if separately approved for a release. Deferred items must be disclosed in the release notes.

## 6. Future possibilities — not committed

The comparative feature catalog suggests many ideas, but **none is automatically included** in V0 or V1:

- PostgreSQL, JSON-file persistence, remote REST adapters and additional databases after explicit consistency/security evaluation.
- Custom domain services for workflows that cannot be implemented safely as generic CRUD calls.
- OIDC/OAuth and individual end-user identities if public clients genuinely require them.
- Advanced tenancy, cross-database queries and more complex transactions.
- A separate operator UI, data import/export, analytics or specialized integration services.

**Deliberate exclusions:** recreating a Java/Jakarta application server, a general arbitrary-client-SQL endpoint, unapproved schema mutations via HTTP or the entire DreamFactory feature list.

## 7. Engineering workflow and release gates

Each implementation slice is tracked in a small issue or PR with milestone/requirement IDs. Define the goal, **out-of-scope** items, security impact, positive and negative tests, and documentation changes.

### Definition of Ready

- Requirements and acceptance behavior are clear and cross-referenced.
- Hosting/security assumptions affecting this slice have been resolved.
- The task fits one focused implementation change.
- Relevant positive and negative verification can be performed.
- Any public API/architecture change has an agreed review path or ADR.

### Definition of Done

- Readable, layered, well-commented implementation.
- Tests relevant to the changed code **actually executed**; results recorded.
- No credentials, private data, or full sensitive request bodies in source, test artifacts or logs.
- Security boundaries and database constraints remain intact.
- Relevant API/config, operations and CHANGELOG documentation updated.
- No unresolved critical findings; open limitations clearly disclosed.

### Release gate

**Do not create a release tag solely because code or documentation appears complete.** Record the commit, environment, test results, actual provider checks, backup/restore evidence, open risks, and explicit project approval. See [Release and acceptance](docs/release-acceptance.md).

## 8. Dependencies and risks

| Risk | Required mitigation | Blocks |
|---|---|---|
| Private credentials/paths cannot be protected on hosting | Move sensitive data outside webroot or change deployment profile; no unsafe fallback | V0-G0 |
| Incorrect HTTPS/proxy trust | Verify provider TLS settings and reject spoofed forwarded headers | V0-M1 |
| Shared API key embedded in public client | Require a trusted backend or separate end-user authentication | Production public-client use |
| Accidental table/field exposure | Deny by default; allowlist and negative tests | All data routes |
| SQL injection via filters/identifiers | Validated query parameters, bound SQL values | V0-M2/M4, V1-P3 |
| SQLite locking and WAL assumptions | Test real host filesystem and concurrency | V1-P2 |
| Audit write fails | Roll back data write in the same DB transaction | V1-P6 |
| Rate-limit store loses atomicity | Tested atomic persistence and fail-closed behavior | V1-P6 |
| V1 breaks V0 clients | Separate versioned routes and migration/contract testing | V1-P8 |

Detailed tracking: [Risk register](docs/risk-register.md) and [Architecture decision records](docs/adr/README.md).

## 9. Keeping the roadmap honest

Progress belongs in actual issues, pull requests, commits and verifiable test records. Avoid invented completion percentages, dates, security results or feature availability. The roadmap should change when milestones are achieved or formally reprioritized. Significant design decisions require an ADR; changes to MUST requirements need an intentional specification update.

**Next planned step:** **V0-G0 — verify hosting, security and configuration boundaries**, then start V0-M0 (a small repository/bootstrap skeleton).

---

**No V0 or V1 implementation, passing test suite, or approved release is claimed by this roadmap.**
