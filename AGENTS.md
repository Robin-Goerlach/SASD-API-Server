# AGENTS.md – Instructions for Coding Agents

> **Language:** **English (default)** | [Deutsch](AGENTS.de.md)  
> **Scope:** Entire `Robin-Goerlach/SASD-API-Server` repository. More specific nested AGENTS.md files may add instructions but must not weaken security constraints.  
> **Updated:** November 1, 2026 · **Status:** working guidelines, **not** evidence of implemented software or accepted releases.

## 1. Project goals and priorities

**SASD API Server** is planned as a small, configurable open-source **REST/JSON data service** usable by several independently deployed applications. The same code revision should support different URLs and instance-specific databases, credentials, resource allowlists and logs.

**Core priorities:**

1. **Finish and accept V0.1.0 first.** V1 is a later goal, not permission to implement the entire future catalog immediately.
2. **Analyze before coding.** Check requirements, architecture, API compatibility, data integrity, security boundaries and tests.
3. **Prioritize clear architecture over premature optimization.** Write readable, maintainable, thoroughly explained source code.
4. **Deny by default.** Unknown resources, fields, methods, filters, relations and roles are never made public automatically.
5. **Execute and report tests accurately.** Say which tests really ran and identify checks that were not executed.
6. **Use small reviewable development slices**, not large unverified rewrites.
7. **Do not silently decide important security or compatibility trade-offs.** Record reviews and ADRs where applicable.

## 2. Relevant project documents

Before starting a task, read the **pertinent sections** of the following documents:

| Purpose | Document |
|---|---|
| Overview and actual project status | [README.md](README.md) |
| Priorities, V0-before-V1 and milestones | [ROADMAP.md](ROADMAP.md) |
| Documentation navigation | [docs/README.md](docs/README.md) |
| V0 functional requirements | [docs/requirements-v0.md](docs/requirements-v0.md) |
| V0 implementation contract | [docs/technical-specification-v0.md](docs/technical-specification-v0.md) |
| V0 component and security design | [docs/architecture-v0.md](docs/architecture-v0.md) |
| V0 development slices | [docs/development-plan-v0.md](docs/development-plan-v0.md) |
| Testing and acceptance | [docs/test-strategy.md](docs/test-strategy.md), [docs/release-acceptance.md](docs/release-acceptance.md) |
| Shared hosting, backups and recovery | [docs/deployment-operations.md](docs/deployment-operations.md) |
| Risks and blockers | [docs/risk-register.md](docs/risk-register.md) |
| Vulnerability disclosure | [SECURITY.md](SECURITY.md) |
| Contribution and reviews | [CONTRIBUTING.md](CONTRIBUTING.md) |
| Architectural decisions | [docs/adr/README.md](docs/adr/README.md) |
| Future V1 | [V1 requirements](docs/requirements-v1.md), [technical specification](docs/technical-specification-v1.md), [architecture](docs/architecture-v1.md) |

**When documents conflict:** explicit current task and non-negotiable security rules → MUST requirements of the relevant requirements specification → actually **accepted** ADRs → technical specification and architecture → roadmap, implementation plan and examples. Many documents are still **drafts**; do not present a disputed implementation choice as already approved. Do not silently rewrite a functional requirement to match convenient code.

**Language:** English is the **repository default**, and parallel German documents should remain available. Public API fields, types and code identifiers should use clear English; team discussions can be conducted in German as appropriate. Keep the **same requirement IDs** across English and German texts. If a requirement changes, update both versions consistently.

## 3. Verify the actual repository state

Current requirements, specifications, diagrams and roadmaps describe **planned** functionality; a written class name is not evidence of existing PHP code.

**Before every coding task check actual facts:** branch, HEAD commit, working-tree status, files, Composer configuration, unit/integration tests, CI workflows, dependency versions and any relevant uncommitted changes. Never invent a source file, script, available tool, build result or successful release.

Do not overwrite someone else's uncommitted work. Limit changes to files relevant to the task. Avoid unrelated mass-formatting, structural rewrites or speculative improvements.

## 4. Version boundaries and immediate scope

### V0.1.0 — first implementation target

- **Runtime:** PHP 8.4, preferably without a mandatory heavyweight framework.
- **Persistence:** MariaDB 10.11 / compatible MySQL using PDO MySQL; **exactly one production V0 database adapter**.
- **Hosting:** conventional PHP shared hosting without mandatory Docker, Redis, root permissions, background workers or queues.
- **HTTP:** a small public root `index.php` front controller and `index.php?route=/api/v0/...` fallback where URL rewriting fails.
- **Core functions:** single-record GET/POST/PUT/PATCH/DELETE for explicitly approved tables and fields.
- **Queries:** single-column primary keys, equality/AND filters, approved sorting and bounded limit/offset.
- **Security:** installation-specific API key, HTTPS, strict data/resource/method allowlists, body/query limits and safe error mapping.
- **Operations:** private configuration, sanitized logging, minimal health, independent instances and documented backup/restore.
- **Acceptance:** `V0-F01` through `V0-F17` and `AT-V0-01` through `AT-V0-10`.

### V1.0 — only after accepted V0

V1 introduces a stable versioned contract, SQLite as second adapter, external field aliases, bounded advanced filters/relations/views, multiple technical keys, RBAC, OpenAPI, write audit, rate limits and configuration migration. Five V1 SHOULD capabilities remain **optional** until explicitly approved.

**Do not introduce into V0 without scope approval:** SQLite/PostgreSQL/JSON-file/remote REST adapters, user registration, OAuth/SSO, full multitenancy, GraphQL, arbitrary SQL, generic joins/bulk updates, database DDL over HTTP, web admin UI or a complete DreamFactory equivalent.

Reference-product documentation is a source of ideas, **not** a requirement to implement every researched feature.

## 5. Architecture and dependency rules

The intended layering is:

~~~text
Client (HTTPS, REST, JSON)
  → HTTP: Router, Request, ResourceController
  → Application: CrudService, Validation
  → Domain: ResourceDefinition, FieldDefinition
  → Contracts: RecordRepository (Port)
  ← Infrastructure: MariaDbPdoRepository (Adapter)
  → MariaDB

Cross-cutting: Security, Config, Error Mapping, Logging, Health
Composition root: Bootstrap / index.php
~~~

**Mandatory architectural constraints:**

- `index.php` and Controllers **must not contain SQL** or database credentials.
- Application services coordinate CRUD, validation and transaction boundaries. They must not depend directly on `PDO` or `$_SERVER`.
- Domain/resource definitions should not depend on HTTP response or SQL driver types.
- Repository ports must remain **small, storage-independent contracts**; PDO details stay in Infrastructure.
- ResourceRegistry/AccessPolicy is authoritative for published resources, methods, readable/writable fields and queries.
- New adapters, controllers or extensions must not bypass common security, validation, error mapping or logs.
- Introduce interfaces for **actual use cases**, not hypothetical future databases.

**Code conventions:** readable PHP, clear namespaces, appropriate type declarations and PHPDoc. Explain public interfaces, non-obvious failures, transaction boundaries, security invariants and architecture decisions with useful, sometimes detailed comments. Explain **why**, not every self-evident line. Correctness and maintainability matter more than micro-optimization.

## 6. Respect public HTTP/JSON contracts

The following **planned V0 contract** is not evidence of existing endpoints:

| Method | Path | Meaning |
|---|---|---|
| GET | `/api/v0/{resource}` | List |
| GET | `/api/v0/{resource}/{id}` | Single record |
| POST | `/api/v0/{resource}` | Create exactly one record |
| PUT | `/api/v0/{resource}/{id}` | Replace complete client-writable representation |
| PATCH | `/api/v0/{resource}/{id}` | Update only provided writable fields |
| DELETE | `/api/v0/{resource}/{id}` | Delete exactly one record |
| GET | `/api/v0/health` | Minimal non-sensitive health status |

`/index.php?route=/api/v0/{resource}` must use the **same** routing and authorization pipeline. Normalize routes, reject traversal and double encoding; never interpret a client route as a local filesystem path.

**Responses:** standard `data` / `meta` envelopes, machine-readable error codes, `requestId` and defined HTTP status codes. Do not expose SQL statements, passwords, stack traces, secret file paths or internal DB details.

**Never ignore an invalid filter and accidentally return every record.**

**Write semantics:** one request addresses one record and uses a local transaction. PUT replaces the **complete client-writable representation**, PATCH only submitted fields; system-controlled fields remain protected. For updates, `rowCount()===0` does **not** necessarily mean the record does not exist. V0 does **not** guarantee optimistic concurrency.

An observable contract change needs documentation, HTTP contract tests and a conscious version/architecture decision when incompatible.

## 7. Data access and configuration standards

1. **Explicit allowlists:** physical database tables are invisible until their public resources are approved.
2. **Safe SQL:** PDO prepared/bound client values; only server-validated table/column/sort/operator identifiers are permitted in SQL text.
3. **Data minimization:** do not expose unrestricted `SELECT *`; select approved readable columns only. UPDATE/DELETE must include the full target key condition.
4. **Least privilege:** use a dedicated, minimally privileged DB account per instance; API permissions are an **additional** defense.
5. **Integrity:** test transactions, constraints, null/default values, IDs, Boolean/UTC/decimal behavior and neutral error categories.
6. **Configuration:** versioned and validated; invalid settings **fail closed**. No freely writable public API for config, DB schema or credentials.
7. **No fake portability:** do not claim an adapter exists until it is implemented and tested with the common contract.

## 8. Hosting, secrets and security by default

**Production-critical rule:** public root `index.php` may be inside document root; **resource configuration, API keys, DB credentials, logs, backups, and future SQLite files must be outside** all publicly served paths. A possibly ignored `.htaccess` does **not** provide sufficient protection.

- Never commit keys, tokens, real passwords, production records or confidential logs to Git, chat, screenshots, CI output or issues.
- API keys belong in a protected secret store and an HTTP **header**, not in URLs or query parameters.
- A key embedded in public JavaScript or downloadable application binaries is **not** a reliably hidden secret. A trusted backend or separately specified user-auth system is needed.
- Protected data routes require **HTTPS**. Trust `X-Forwarded-Proto` only when the provider/proxy trust path is verified. A redirect alone cannot protect a plaintext key already transmitted.
- CORS is **not authentication**.
- Enforce request-body, JSON nesting, query/filter complexity, pagination and result-size budgets; test limits on the real host.
- Log UTC, request ID and safe error status **without** full payloads, Auth headers, passwords, SQL bindings or secrets.
- Public health returns minimal information. Detailed database readiness is protected.
- When hosting cannot meet mandatory TLS/private-path conditions, **do not build an unsafe workaround or approve production**.

Follow [SECURITY.md](SECURITY.md) for vulnerabilities and [Risk register](docs/risk-register.md) for blockers.

## 9. Tests: positive and negative cases

Every meaningful feature needs successful and prohibited/invalid cases:

- **Unit:** route/config/resource/query parsers, key/policy checks, ErrorMapper.
- **Integration:** real isolated MariaDB, CRUD, constraints, rollback and unchanged UPDATE.
- **HTTP contract:** methods, routes, JSON, headers, status/error structure and query-routing fallback.
- **Security:** unknown table/field/method, invalid or foreign key, SQL injection in IDs/filters/sorts, size/TLS/secret-path failures.
- **Deployment:** real PHP hosting and direct HTTP requests to private files (must fail).
- **Instance isolation:** two instances from the same release but with distinct URLs/DBs/keys/logs.
- **Recovery:** backup **and restore** on a separate test system.
- **V1 later:** shared MariaDB/SQLite contracts, RBAC/field/count/relation leaks, audit rollback, limiter fail-closed and OpenAPI.

Check **actual available commands and workflows** before attempting them. Do not assume `composer test`, GitHub Actions or DB containers exist merely because a specification mentions them.

If a check cannot run due to missing DB, hosting, credentials, test tool or implementation, report **“not executed” with a reason**, never “passed”. Never reset or delete production data for tests.

Unit tests alone are **not** substitutes for real hosting, security and recovery evidence. V0 acceptance requires all required functions/scenarios; V1 adds mandatory adapter tests and the V1 gates.

## 10. Workflow for each coding task

### Before editing

1. Identify the exact user task, target version, related requirement IDs and **non-goals**.
2. Check branch/HEAD, git status, real source files, dependencies, tests and workflows.
3. Read relevant requirements, technical spec, architecture, risks and prior decisions.
4. Identify impact on permissions, SQL, secrets, transport, public contracts and data migration.
5. Propose an [ADR](docs/adr/README.md) for a long-lived architecture trade-off.

### During implementation

1. Implement the **smallest coherent slice** and preserve layer boundaries.
2. Write understandable, complete and well-commented code.
3. Add **positive and negative** verification for new routes, SQL paths, fields and permissions.
4. Keep README, configuration/API docs and [CHANGELOG.md](CHANGELOG.md) aligned as relevant.
5. Do not perform unrelated refactors or premature V1 work.
6. No destructive Git operations, force pushes, unrelated branch changes, production migrations or secret uploads without explicit authorization.

### Final task report

State, accurately and concisely:

- **Changes actually made:** files, requirement IDs and material design choices.
- **Tests actually executed:** exact commands, environments and results; separately list anything **not run** and why.
- **Risks/open decisions:** especially shared-hosting, authorization and data-integrity blockers.
- **Documentation updated:** API/config/runbook/changelog/ADR as appropriate.
- **Smallest useful next step**, without performing unrelated work.

A completed design document is **not** a passing build or an accepted product.

## 11. GitHub reviews and Definition of Done

Prefer small thematic branches and PRs with meaningful commit messages such as `docs: add agent guidance`, `feat(v0): add resource registry` or `test(v0): reject unauthorized fields`. If committing directly to main is explicitly requested, verify state first and change only the intended files.

Use [CONTRIBUTING.md](CONTRIBUTING.md) and the repository's issue/PR templates. Do not import third-party code/docs without license and attribution review.

An implementation slice is **done** only when agreed scope, architecture, positive/negative tests, confidentiality and documentation agree. Unverified provider/security/recovery gates must remain **open**.

**First planned work:** **V0-G0** — verify actual shared-hosting/private-file/TLS constraints — followed by **V0-M0** (minimal project skeleton). **Build and accept V0 safely before expanding to V1.**

---

**Agent safety boundary:** This file provides development guidelines, **not authorization** for unsolicited production changes, public releases or bypassing protective controls.
