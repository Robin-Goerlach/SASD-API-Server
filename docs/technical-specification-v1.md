# SASD API Server – Technical Specification V1

> **Language:** **English (default)** | [Deutsch](technical-specification-v1.de.md)  
> **Status:** technical design proposal for future V1.0; no implementation, sign-off or acceptance claimed. **Version:** 0.1 · **Date:** November 1, 2026.  
> **Requirements:** [V1](requirements-v1.md) · **Mandatory foundation:** [V0 requirements](requirements-v0.md), [V0 technical specification](technical-specification-v0.md), [V0 architecture](architecture-v0.md)  
> **Architecture:** [V1 architecture](architecture-v1.md) · **Research:** [Reference-product catalog](reference-products-feature-catalog.md)

## 1. Purpose and prerequisites

This document details an implementable technical solution for **all 27 V1 functional requirements**. The **22 MUST** items are mandatory objectives **after formal scope approval**. The **five SHOULD** items are intentionally limited, separately enabled extensions; they become release blockers only after explicit approval.

The project is **not** recreating all 157 features researched from DreamFactory, PHP-CRUD-API and DB2Rest.

**Fundamental prerequisite:** a functioning, fully accepted V0. V1 **extends the existing layered codebase** instead of replacing it with a new application server. PHP 8.4 and ordinary shared hosting remain valid deployment targets **without mandatory persistent workers, Redis or Docker**.

### 1.1 V0 guarantees to preserve

- External HTTPS/JSON, API keys and explicitly approved resources.
- Small root `index.php` front controller and documented query-routing fallback.
- No automatic table exposure, unknown SQL identifiers, client-supplied raw SQL or HTTP DDL.
- Writes affect only the explicitly addressed records and respect DB constraints.
- Separation of HTTP, business-independent service, resource definitions, security and persistence.
- Per-installation isolation for credentials, configuration, data and logs.

### 1.2 Additional goals in V1

Stable versioned APIs; field aliases; read-only views; bounded filters, projection and relations; **SQLite as the second supported database**; multiple technical client keys and roles; controlled audit and rate limits; automatically derived OpenAPI; and tested use by clients in different programming languages.

## 2. Solution architecture

### 2.1 High-level component flow

~~~text
                         HTTPS / REST / JSON
                                  |
                  index.php / Router / Request ID
                                  |
                Middleware: TLS, body size, key,
                    role, rate limits, audit
                                  |
                         ResourceController
                                  |
                 QueryParser / Resource DTO / Validator
                                  |
                             CrudService
                                  |
               ResourceRegistry + AuthorizationPolicy
                                  |
                RecordRepository + TransactionPort
                       /                 \
               MariaDbPdoAdapter     SqlitePdoAdapter
                       \                 /
                       configured instance data source

Cross-cutting: Configuration/schema upgrade, error mapping,
OpenAPI, health/readiness, logs, audit store, extension registry
~~~

### 2.2 Responsibilities and dependency rules

**HTTP/Controller** translates public routes, methods and JSON into typed requests, without SQL.  
**Application/Service** coordinates allowed CRUD use cases and transaction boundaries.  
**Domain/ResourceRegistry** declares external field names, source mappings, validation and permissions.  
**Contracts/Ports** define common data operations independently from PDO/dialects.  
**Infrastructure/Adapters** compile trusted QueryPlans into parameterized SQL for MariaDB or SQLite.  
**Security** authenticates and authorizes **before** data access, including filters and relation targets.  
**Diagnostics/Audit** observe safe technical events and persist mandatory write audit atomically.

Avoid heavy mandatory frameworks, process-global DB state and a client-side SQL interpreter. A PHP shared-hosting request is generally a separate execution context; state that must survive requests requires a **persistent, protected** store.

### 2.3 Proposed V1 project structure

~~~text
index.php
src/Http/{Request,Response,Router,MiddlewareStack}.php
src/Http/Controller/{ResourceController,OpenApiController,HealthController}.php
src/Http/Parser/{ListQueryParser,JsonBodyParser}.php
src/Application/{CrudService,RelationReadService,NamedReadService}.php
src/Application/Query/{FilterExpression,SortSpec,PageSpec,QueryPlan}.php
src/Domain/{ResourceRegistry,ResourceDefinition,FieldDefinition}.php
src/Domain/{RelationDefinition,ViewDefinition,ApiError}.php
src/Contracts/{RecordRepository,TransactionPort,AuditEventWriter}.php
src/Infrastructure/Database/{MariaDbPdoAdapter,SqlitePdoAdapter}.php
src/Infrastructure/Database/{SqlDialect,ConnectionFactory}.php
src/Security/{ApiKeyStore,ApiKeyAuthenticator,RolePolicy,RequestRateLimiter}.php
src/Security/{RecordVisibilityPolicy,KeyManagementService}.php
src/Infrastructure/Audit/{SqlAuditWriter,SecurityEventLogger}.php
src/Infrastructure/Diagnostics/{ReadinessService,MetricsCollector}.php
src/OpenApi/{SchemaBuilder,OpenApiGenerator}.php
src/Config/{ConfigV1Loader,ConfigV1Validator,ConfigMigrator}.php
bin/{config-check,keys-create,keys-revoke,config-migrate}
config/{example.v1.json,example.keys.json}         # secret-free
migrations/{mariadb,sqlite}/
tests/{Unit,Integration,Contract,Security,Migration,Deployment}/
~~~

**This is a proposed design, not a file inventory.** A smaller structure with the same tested responsibilities is preferable if it avoids unnecessary classes. Optional SHOULD components are not implemented without approval.

### 2.4 V1 request lifecycle

1. Bootstrap loads **the current instance's** configuration, schema version and resource/role policies; invalid config fails closed.
2. Generate a server-side request/correlation identifier.
3. Normalize route/parameters and check HTTP method, reliable HTTPS and body limits.
4. Authenticate the key through the protected key store; failed auth reveals no schema or roles.
5. Consult the persistent rate-limit budget; if safety-critical limiter infrastructure fails, **fail closed**.
6. Authorize client role for resource, method, fields, filter/sort columns, relations, views and optional record scope.
7. Parse and type-check a bounded **QueryPlan**, never raw SQL fragments.
8. Adapter executes parameterized SQL; write requests operate within one local transaction.
9. Required audit event for successful writes is stored **inside the same transaction** before COMMIT.
10. Return standardized JSON and safe headers; record sanitized status, timing and request ID.

## 3. Stable V1 HTTP contract

### 3.1 Routes

| Method | Path | Outcome |
|---|---|---|
| GET | `/api/v1/{resource}` | Collection with `data` and `meta` |
| GET | `/api/v1/{resource}/{id}` | Single `data` object |
| POST | `/api/v1/{resource}` | New record, 201 and Location |
| PUT | `/api/v1/{resource}/{id}` | Complete writable representation, 200 |
| PATCH | `/api/v1/{resource}/{id}` | Partial update, 200 |
| DELETE | `/api/v1/{resource}/{id}` | Single record deletion, 204 |
| GET | `/api/v1/openapi.json` | Protected, role-aware OpenAPI |
| GET | `/api/v1/health` | Minimal non-sensitive status |
| GET | `/api/v1/ready` | Authenticated operator readiness |
| GET | `/api/v1/queries/{name}` | **SHOULD:** configured read-only named query |

Equivalent `/index.php?route=/api/v1/...` routing must behave identically.

**Never silently redirect `/api/v0` clients to `/api/v1`.** Concurrent support for both contracts requires an explicitly planned compatibility/deprecation policy; document any changed field types or error behavior.

### 3.2 Uniform JSON envelopes

Single record:

~~~json
{"data": {"id": 42, "title": "Architecture", "active": true}}
~~~

Collection:

~~~json
{
  "data": [{"id": 42, "title": "Architecture"}],
  "meta": {"limit": 25, "offset": 0, "count": 1, "hasMore": false}
}
~~~

Error:

~~~json
{
  "error": {
    "code": "forbidden",
    "message": "Access is not permitted.",
    "details": []
  },
  "requestId": "d23f20a5304d4bcb"
}
~~~

`count` is page count, not automatically a total count. Reuse the V0 HTTP/error semantics and reserve `precondition_failed`/412 and 428 for optional optimistic concurrency. For 429 include a safe `Retry-After` value when possible. Authenticated data responses default to `Cache-Control: no-store`; do not assume a shared cache protects sensitive data.

### 3.3 Data types and cross-adapter format

| External type | V1 JSON representation | Backend contract |
|---|---|---|
| String | UTF-8 JSON string | Configured length; do not equate SQL collations without testing |
| Integer ID | Positive integer within JSON-safe range (up to 2^53−1) | Reject unsafe integer representations rather than silently rounding |
| UUID | Canonical lowercase string | Convert to/from supported DB storage |
| Boolean | `true` / `false` | Normalize MariaDB TINYINT(1) and SQLite INTEGER |
| Decimal | Decimal **string** | Avoid PHP floating-point rounding of exact values |
| Date/time | ISO-8601 UTC e.g. `2026-11-01T14:30:00Z` | Controlled DB conversion, no implicit local time |
| Null | JSON `null` | Only when field definition permits null |

These conventions are part of the intended **V1** contract. Large binary upload endpoints, unrestricted JSON document fields and SQL-dependent expressions are not automatically included. A differing V0 client type convention must be handled as an intentional versioned migration.

### 3.4 Bounded filter and sort grammar

~~~text
filter[field][eq]=value
filter[year][gte]=2020
filter[state][in][]=draft
filter[state][in][]=published
filter[title][contains]=API
filter[reviewedAt][isNull]=true
or[0][state][eq]=draft
or[1][state][eq]=published
sort=title,-id
fields=id,title,state
limit=25&offset=0
include=author
~~~

All ordinary `filter` conditions combine by **AND**. One optional `or[]` group forms a parenthesized OR expression that is itself ANDed with other filters. **No unlimited recursive query language**.

Initial proposed budgets: at most **8 AND conditions**, **8 OR alternatives**, **20 values per IN list**, **3 sort fields**, **100 results/page**, offset cap **10,000**, and **one relationship level**. These are **design defaults** to validate in security and performance testing, not already confirmed hosting values.

Operators: `eq`, `ne`, `lt`, `lte`, `gt`, `gte`, `in`, `isNull`, `contains`, `startsWith` — only where the configured field type supports them.

Every field and operator must pass ResourceRegistry and RolePolicy checks. QueryPlan contains **typed AST nodes**, not direct client SQL. The SQL adapter selects fixed operators and binds every value. Identifiers, sort clauses and relation names are never copied unvalidated from requests.

**Text-search limitation:** MariaDB and SQLite differ in collation and LIKE rules. Document a **small, testable** common case/collation behavior. Do **not** promise broad Unicode full-text equivalence without implementation and tests.

### 3.5 Relationships and data-exfiltration protection

Relations are declared by name, source/target resource, foreign-key columns, cardinality and visible fields. For example `include=author` expands one permitted relation level for a collection of books.

**Authorize source and target separately**, including fields, filters and roles. Limit child records per parent and total results, and avoid unbounded N+1 query patterns through controlled batch/join reads. The adapter chooses an implementation within the common contract.

If an expansion is not safe or would exceed limits, **reject it** instead of silently exposing private data or loading an unrestricted result. No cross-database joins, recursively expanded relationships or arbitrary client JOIN expressions in V1.

## 4. Resource and configuration model

### 4.1 Configuration schema v2 and migration

Introduce `schemaVersion: 2`. ConfigV1Validator verifies version, resources, aliases, methods, fields, roles, relation targets, security limits and secret references.

A documented **schemaVersion 1 → 2** migration transfers the V0 resource model and introduces proposed key/role bindings. Ambiguous rules require explicit operator review. **Never open previously private resources by default.**

Illustrative (abbreviated) configuration:

~~~json
{
  "schemaVersion": 2,
  "instanceId": "harald-test",
  "apiPrefix": "/api/v1",
  "database": {"adapter": "mariadb", "connectionRef": "MAIN_DB"},
  "resources": {
    "books": {
      "source": {"kind": "table", "name": "library_books"},
      "primaryKey": {"field": "id", "column": "id", "type": "integer"},
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {"column": "id", "type": "integer", "read": true, "write": false},
        "title": {
          "column": "book_title", "type": "string",
          "maxLength": 200, "read": true, "write": true, "required": true
        },
        "active": {
          "column": "is_active", "type": "boolean",
          "read": true, "write": true, "required": true
        }
      },
      "relations": {
        "author": {
          "target": "authors",
          "localColumn": "author_id",
          "remoteColumn": "id",
          "cardinality": "many-to-one",
          "maxDepth": 1
        }
      }
    },
    "book_summary": {
      "source": {"kind": "view", "name": "v_book_summary"},
      "methods": ["GET"]
    }
  }
}
~~~

**No secret value belongs in the JSON.** `connectionRef` identifies a protected operator-owned connection, not a URL or a client-selected DB server. Roles and key store are held in separate protected versioned files. Field presence does **not** confer access by itself: role, resource, method and field flags must all authorize the operation.

### 4.2 Field aliases and schema compatibility

External `title` can map to physical `book_title`. On read, the adapter normalizes database values to API types; on write, it maps only approved external field names back to known DB columns. Clients may not choose unregistered tables or SQL column names.

### 4.3 Read-only view resources

For `source.kind=view`, **GET only** is allowed even if the SQL engine considers the underlying view updatable. Apply the same filter, sort, field and role policies as for tables. A view is **not** a replacement for API-level authorization.

### 4.4 Config validation and atomic replacement

An offline `config-check` or equivalent operator tool validates schemas, key/role references, resource names, relation targets and file permissions. Prepare a new config separately, validate it completely, then **atomically replace** the active file, for example using same-volume rename, after verifying the actual shared-host filesystem semantics. No partially active configuration and no permissive fallback.

## 5. Database adapters and persistence

### 5.1 Shared repository contract

The existing V0 `RecordRepository` remains the basis. V1 adds defined capabilities for `QueryPlan`, `RelationSpec`, `ReadOnlyView` and a protected transaction port. No PDO object or SQL dialect string should cross the Application/Contracts boundary.

Run a **single common blackbox contract suite** against both adapters. Additional database engines may be considered later only after their equivalent features are genuinely implemented/tested.

### 5.2 MariaDB/MySQL adapter

Continue PDO with `utf8mb4`, true parameter binding, correct SQL identifier quoting and connection lifecycle per PHP request. Client SQL text, arbitrary ORDER BY expressions and public DDL remain forbidden.

### 5.3 SQLite adapter

Use `pdo_sqlite` with a **canonical validated path outside document root**, restrictive filesystem permissions, and no client-supplied DB file paths. Execute `PRAGMA foreign_keys=ON` on **each** connection; configure an appropriate `busy_timeout` and bounded handling of `SQLITE_BUSY`. Journal/WAL settings depend on the **actual hosting filesystem**; WAL must not be assumed safe on arbitrary network filesystems.

Boolean, UTC date/time and exact decimal representation are normalized according to the declared schema. The same logical resource should produce the same promised V1 HTTP output on MariaDB and SQLite. **Switching adapters does not migrate records.**

### 5.4 Common integrity and SQL rules

- Single-column integer/UUID keys; **no implicit composite-key support**.
- SELECT only approved projection columns; UPDATE/DELETE only the fully addressed ID and optional version condition.
- Define consistent missing/NULL/default behavior for POST, PUT and PATCH.
- Map duplicate keys, foreign keys, invalid values, locks, missing rows and DB failure to common safe categories.
- **Data write + required audit event commit atomically** in the same instance database.
- If a dialect cannot implement a guaranteed shared feature, **reject it explicitly**, not return misleading data.

## 6. Security and authorization design

### 6.1 Technical clients, not interactive end users

V1 authenticates **technical clients**, usually using the `X-API-Key` header. Each key maps to an identity and a role. It is **not** an end-user registration/login framework. A hardcoded key inside public browser JavaScript is not a protected identity source. Public end-user apps require a trusted proxy or a separately specified user identity solution.

### 6.2 Key generation, storage and revocation

Generate keys **offline** using cryptographically secure randomness, with a proposed minimum of **256 bits of secret entropy**. Display the secret **once**; store a non-secret ID and secure verifier digest (for random high-entropy tokens, e.g. SHA-256), not plaintext. Lookup by key ID, compare digests in constant time, and check active/revoked status for each request.

Revocation may set `disabled=true`; rotation replaces the secret without code changes. Because the target hosting may lack a safe interactive shell, tools should also run on a trusted administrator/developer machine and transfer protected configuration securely.

**No internet-facing key-management/admin API is required.**

### 6.3 Role-based access policies

Example roles `reader`, `editor` and `operator` illustrate operator-configured policies, **not hardcoded universal grants**. Each role lists approved resources, methods, visible/writable fields, relations, views and optional named queries.

Default-deny evaluation order: **authenticated identity → role → resource → method → field/filter/sort/relation rights → optional row visibility → repository access**. An operator can receive protected diagnostics **without** automatic access to all business records. Hidden fields must not leak through filter error messages, counts or OpenAPI.

### 6.4 Optional record-level visibility (SHOULD V1-F14)

A `RecordVisibilityPolicy` may impose a server-derived constraint such as `owner_id = serverAssignedClientOwnerId`. The identity value must come from authenticated server context, **not a free client query parameter**. Apply it consistently to reads, counts, joins and PUT/PATCH/DELETE; writes must enforce it atomically in SQL or an equivalently safe transaction.

Do **not** enable/advertise tenant-safe behavior until exhaustive tests pass on both mandatory DB adapters.

### 6.5 HTTPS, CORS, safe response headers

Continue HTTPS and verified reverse-proxy constraints. CORS uses an explicit installation-specific origin allowlist. Authenticated responses default to `Cache-Control: no-store` and correct content type; set `X-Content-Type-Options: nosniff`. Never trust arbitrary client `Forwarded` headers. A redirect alone does not protect credentials already sent by plaintext HTTP.

### 6.6 Rate limiting without required Redis

For a **single** PHP shared-hosting instance, a protected persistent **file-based token-bucket/sliding-window store** may work if exclusive locks and atomic updates are verified on the target filesystem. Rate-limit failed key attempts using reliable client address signals as well.

Initial test budget example: **60 data requests per minute per key**; real limits require load/security testing. If safe persistent updates are unavailable, protected data routes must **fail closed** (controlled **503**), not continue without rate limits. With multiple web nodes lacking a shared atomic store, this solution is **not** a global distributed guarantee; a distributed limiter would be later work.

## 7. Audit, diagnostics and error handling

### 7.1 Required write-audit events

Store a limited event for each **successful POST/PUT/PATCH/DELETE** in an internal per-instance table `sasd_api_audit_events` using **the same local DB transaction** as the data change. Operator-side MariaDB and SQLite DDL/migrations create the schema **outside HTTP**, not through an exposed schema API.

Minimal fields: `event_id`, `occurred_at_utc`, `instance_id`, `request_id`, `client_id`, `resource_name`, `method`, `record_key`, `outcome`. Do **not** include API secrets or full record bodies. The table is not automatically published as a generic data resource.

If audit insertion fails, **rollback the underlying write** and do not return success. This design does **not** claim certified tamper-proof or regulation-grade audit storage.

Denied authentication/policy attempts do not create business transactions; record only sanitized technical security events in protected logs. Unwritable log paths require controlled operational error behavior.

### 7.2 Health and readiness

`GET /api/v1/health` returns only `ok` or `unavailable` without host/database metadata. `GET /api/v1/ready` requires operator authentication/authorization and checks config, selected adapter, DB connectivity and required audit schema, returning a safe predefined response without PDO paths or credentials.

### 7.3 Technical logs and metrics

UTC, request ID, instance, route, status, sanitized client identity where lawful, error category and coarse duration. Keep logs outside document root, with retention/rotation; never record unredacted Auth headers, SQL, full payloads or passwords.

**SHOULD V1-F27** optionally records aggregated request counts, failures and latency; Prometheus, Grafana and Datadog remain **nonmandatory**.

### 7.4 Failure categories

Preserve V0 statuses. Add role denial 403, rate limiting 429, optional `If-Match` failures 412/428, unsupported query feature 400/422 as defined by the public contract, SQLite locks/timeouts as controlled 503, and unsafe invalid configuration as 503. Clients never receive SQLSTATE, internal paths, stack traces or secrets.

## 8. Deliberately optional V1 modules

### 8.1 Named read-only queries (SHOULD V1-F09)

`NamedReadService` maps an **operator-approved query name** to a predefined **read-only SQL template** with typed parameters, role restrictions, projected fields, row limits and timeouts. **Never** accept SQL text as an HTTP parameter. Different dialects may require separate operator-defined SQL templates. Unsupported queries stay disabled.

### 8.2 Optimistic concurrency (SHOULD V1-F16)

A resource with an explicit monotonic version field may return a version token, for example an `ETag`. When enabled, PUT/PATCH requires `If-Match`; an atomic UPDATE checks **both ID and expected version** and advances the version on success. Missing precondition → 428; mismatched → 412; ambiguous `rowCount()=0` cases need a safe existence/version check. Only enable on reliably versioned tables.

### 8.3 Controlled extensions (SHOULD V1-F22)

An operator registry may enable explicitly identified additional Controllers or read services. Every extension goes through central authentication, role policy, budgets, error handling, logging and audit. No HTTP upload of executable PHP, dynamic eval or unrestricted plugin installation.

### 8.4 Row scope (SHOULD V1-F14)

See `6.4. Treat as a separate, security-sensitive module, not a ready-made multitenancy claim. Test records, counts, related data, write routes and prohibited owner parameters on both databases.

### 8.5 Operational metrics (SHOULD V1-F27)

See `7.3. Keep aggregated metrics privacy-preserving and compatible with hosting without mandatory worker services. Measurements must specify host, data volume, concurrency and route type.

## 9. OpenAPI and client integration

### 9.1 Generated OpenAPI

`OpenApiGenerator` derives paths, query parameters, data schemas, errors, API-key authentication and versioning from **the same authorized ResourceRegistry** used at runtime.

Target **OpenAPI 3.1**, provided the selected tooling correctly supports it; otherwise explicitly document a compatible 3.0.x baseline. Deliver `apiKey` authentication using `X-API-Key`.

`/api/v1/openapi.json` requires appropriate authentication; generate a **role-specific view** exposing only permitted resources/fields. OpenAPI does **not** replace runtime policy checks. Validate the result using an OpenAPI validator **and** HTTP contract tests.

### 9.2 curl, C# and Java examples

Provide working examples using HTTPS, JSON and `X-API-Key` for:
- curl CLI
- .NET `HttpClient`
- Java `HttpClient`

All clients handle 401/403/404 and the standard error schema. **Custom SDKs and desktop UI packages are not release requirements.**

## 10. V0-to-V1 configuration/deployment migration

1. Back up the database and **prove restore in a test environment** before updating.
2. Copy V0 instance to staging and convert `schemaVersion=1` to `2`, using **closed** defaults for new permissions.
3. Qualify SQLite with a **separate test instance**, not by unexpectedly replacing production MariaDB.
4. Install internal audit tables using protected operator migration (MariaDB/SQLite); no HTTP DDL.
5. Create V1 key store and assign roles deliberately. Legacy V0 keys must **not** silently become admin/owner identities.
6. Validate the new config offline; test readiness, OpenAPI, SQL adapters and security.
7. Enable `/api/v1` explicitly. No silent redirect of existing V0 clients.
8. For rollback, restore a compatible code/config version; if schema changed, a tested database restore may be required. **No automatic lossless rollback guarantee.**

### 10.1 Shared-hosting confidentiality

Active config, key store and logs **must** be outside document root and verified through direct HTTP-negative checks. Otherwise do not approve production. No mandatory permanent worker. Offline key creation and migration may run on a trusted workstation when the provider offers no CLI; carefully reviewed manual SQL setup may be acceptable.

## 11. Testing, common contracts and release gates

### 11.1 Test levels

- **Unit:** QueryParser, type converters, config/role validation, rate budgets, OpenAPI structure.
- **Integration:** CRUD, constraints, transactions, audit, views/relations for MariaDB **and** SQLite.
- **Contract:** identical external HTTP/JSON tests over each mandatory adapter; any unsupported specialized behavior must be explicit and **cannot** exclude a MUST feature.
- **Security:** role escalation, SQL injection, hidden field/filter/relation/count/OpenAPI leaks, other-instance keys, CORS/TLS and rate-store fail closed.
- **Migration:** V0 configuration/data fixture → V1 proposal → validated transition and rollback/error.
- **Deployment:** PHP shared hosting, query-route fallback, filesystem locks and SQLite concurrency where used.

### 11.2 V1 technical test cases

| Test ID | Technical verification | Requirement IDs |
|---|---|---|
| T-V1-01 | Versioned HTTP/JSON/OpenAPI contract and compatibility | F01/F21 |
| T-V1-02 | Aliases, hidden tables/columns and views inaccessible unless allowed | F02/F08 |
| T-V1-03 | CRUD, validation and error categories on MariaDB and SQLite | F03/F04/F10/F15 |
| T-V1-04 | Comparison/NULL/IN/AND/OR filters, projection, sorting, paging | F05/F06 |
| T-V1-05 | One-level relation; no unauthorized expansion or field leak | F07/F13 |
| T-V1-06 | Read-only view accepts GET; rejects writes | F08 |
| T-V1-07 | Two instances, separate DB/config/keys and no cross-access | F10/F11 |
| T-V1-08 | Key rotation/revocation and role-based GET/write rights | F12/F13 |
| T-V1-09 | Atomic data+audit commit, unique/FK conflicts, rollback on audit failure | F15/F19 |
| T-V1-10 | Body/query/rate budgets, limiter failure, TLS/CORS | F17/F18 |
| T-V1-11 | Protected readiness, minimal public health, no data leak | F20 |
| T-V1-12 | Config errors rejected; migration/backup/recovery verified | F23/F24 |
| T-V1-13 | curl, .NET and Java use same V1 GET/error contract | F25 |
| T-V1-14 | Full unit/integration/contract/security suite locally and in CI | F26 |
| T-V1-15 | **Optional** named SQL read-only and SQL-injection negative checks | SHOULD F09 |
| T-V1-16 | **Optional** row scope, optimistic lock, extensions, metrics | SHOULD F14/F16/F22/F27 |

### 11.3 Release approval

**V1.0 release must not be approved** before V0 acceptance remains valid, all **22 V1 MUST** items pass, both DB adapters pass common contract tests, no critical security defect remains, and real deployment/restore is documented. Unimplemented SHOULD items must be declared as deferred. Any proposed budgets require confirmation from actual measurements before final release configuration.

## 12. Traceability: V1 requirements → technical solution → tests

| Requirement | Implementation section | Test |
|---|---|---|
| V1-F01 | `3 versioned HTTP contract | T-V1-01 |
| V1-F02 | `4 registry and aliases | T-V1-02 |
| V1-F03 | ``3/5 CRUD | T-V1-03 |
| V1-F04 | ``3/5 type conversion and validation | T-V1-03 |
| V1-F05 | `3.4 typed query expressions | T-V1-04 |
| V1-F06 | `3.4 projection, sorting and paging | T-V1-04 |
| V1-F07 | `3.5 bounded relationships | T-V1-05 |
| V1-F08 | `4.3 read-only views | T-V1-02/06 |
| V1-F09 | `8.1 named queries (**SHOULD**) | T-V1-15 optional |
| V1-F10 | `5 two adapters | T-V1-03/07 |
| V1-F11 | ``4/10 independent installs | T-V1-07 |
| V1-F12 | `6 key lifecycle | T-V1-08 |
| V1-F13 | `6 RBAC | T-V1-05/08 |
| V1-F14 | `6.4 row visibility (**SHOULD**) | T-V1-16 optional |
| V1-F15 | ``5/7 consistent write/transaction | T-V1-09 |
| V1-F16 | `8.2 optimistic locking (**SHOULD**) | T-V1-16 optional |
| V1-F17 | ``3/6 rate/query limits | T-V1-10 |
| V1-F18 | `6.5 TLS/CORS/defaults | T-V1-10 |
| V1-F19 | `7 audit and sanitized logs | T-V1-09 |
| V1-F20 | `7.2 health/readiness | T-V1-11 |
| V1-F21 | `9 OpenAPI | T-V1-01 |
| V1-F22 | `8.3 extensions (**SHOULD**) | T-V1-16 optional |
| V1-F23 | ``4/10 versioned config | T-V1-12 |
| V1-F24 | `10 installation/backup/restore | T-V1-12 |
| V1-F25 | `9.2 curl/Java/.NET | T-V1-13 |
| V1-F26 | `11 tests/gates | T-V1-14 |
| V1-F27 | ``7/8 metrics (**SHOULD**) | T-V1-16 optional |

## 13. Implementation slices

| Phase | Work | Required gate |
|---|---|---|
| P0 — baseline | Accepted V0 with reproducible CI/deployment/security | V0 gates green |
| P1 — contract/config | V1 paths, aliases, types and config migration | V0/V1 contract compatibility |
| P2 — SQLite | PDO SQLite, shared repository, type normalization | Both adapters pass core tests |
| P3 — query/views | Bounded filters/projection/sorting and read-only views | Injection and budget negative tests |
| P4 — relationships/policies | One-level relations, client roles, keys, rate limiting | Security tests on all data paths |
| P5 — audit/operations | Same-commit write audit, readiness, migration | Audit rollback, restore and failure checks |
| P6 — OpenAPI/clients | Role-specific OpenAPI and curl/Java/.NET examples | Schema matches real routes |
| P7 — SHOULD extensions | Each optional item only when approved | Independent contract/security acceptance |
| P8 — release | Full security, hosting, restore, compatibility and docs | Formal project acceptance |

This is a **dependency-based plan**, not a fabricated schedule or staffing estimate. Readable, well-commented architecture and reproducible tests matter more than early optimization.

## 14. Risks and unresolved approval questions

1. **Shared hosting:** Are `pdo_sqlite`, private paths, `flock`, atomic file renames and necessary DB privileges actually available at the intended provider?
2. **SQLite:** Test journal mode, network filesystems, write concurrency and busy handling. Do not promise unlimited parallelism.
3. **Audit:** Internal audit tables and privileges must exist on each adapter; audit and data writes must share a real transaction.
4. **Limiter:** Fail-closed protects data but can reduce availability; document this operational trade-off.
5. **Type fidelity:** Normalize Decimal, Boolean, UUID, UTC and text comparison in tested contract fixtures.
6. **Public client keys:** A technical key is not an end-user identity; CORS is not authentication.
7. **Policy leaks:** Guard fields through filters, sort, joins, counts, errors and OpenAPI, not only the final JSON body.
8. **SHOULD scope:** The five optional modules must not destabilize the mandatory V1 core.

### 14.1 Explicit decisions before implementation

- Approve version-2 resource schema and V0→V1 configuration migration.
- Approve query grammar, OR grouping, page budgets and documented text/case behavior.
- Validate SQLite locking and file storage on target hosting.
- Choose protected key-store format and atomic offline rotation procedure.
- Define the operator-side internal audit-schema installation method.
- Confirm role-specific OpenAPI and permission requirements for `/api/v1/ready`.
- Select which SHOULD slices are included or postponed.

## 15. Acceptance and document maintenance

The [V1 requirements](requirements-v1.md) define the functional scope. This document describes a proposed implementation. Any deviation from a **MUST** requirement requires explicit approval and corresponding requirements update **before** changing the promised contract. Both adapters, common tests, security negatives and real restore checks form part of eventual release acceptance.

External reference-project features are **not** automatically SASD requirements.

**Document status: detailed technical proposal; no implemented V1 code, passed tests, or formal approval is claimed.**
