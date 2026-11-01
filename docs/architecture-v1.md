# SASD API Server – Software Architecture V1

> **Language:** **English (default)** | [Deutsch](architecture-v1.de.md)  
> **Status:** architecture proposal for future V1.0, not implemented or accepted. **Document version:** 0.1 · **Date:** November 1, 2026.  
> **Requirements:** [V1](requirements-v1.md) · **Technical specification:** [V1](technical-specification-v1.md)  
> **Mandatory foundation:** [V0 architecture](architecture-v0.md) · [V0 technical specification](technical-specification-v0.md)  
> **Research:** [Reference-product feature catalog](reference-products-feature-catalog.md)

## 1. Target design, scope and relationship to V0

V1 is a **controlled extension of an already implemented and accepted V0**, not the replacement of the small server with a new general-purpose application-hosting platform.

The architecture emphasizes three objectives:

1. **Stable, explicitly versioned REST/JSON data APIs** shared across independent SASD applications.
2. **Two genuinely interchangeable storage adapters**, MariaDB/MySQL and SQLite, implementing the same required external contract.
3. **Secure multi-client technical access** with multiple keys, role-based policies, controlled queries, bounded relations and auditable writes.

The V1 requirements contain **22 MUST and five SHOULD** functions. Named read-only queries, record-level visibility, optimistic locking, controlled extensions and operational metrics are **optional independently approved modules**. No commitment is made to reproduce all 157 researched reference-product capabilities.

Preserve V0's PHP 8.4/shared-hosting deployment, root `index.php` and query-routing fallback, private secrets outside document root, default-deny, and layered design without mandatory persistent workers, Redis or Docker.

A deliberately changed API contract is introduced under `/api/v1`. Existing `/api/v0` clients must **not** silently receive incompatible semantics.

### 1.1 Quality and architecture goals

| Goal | Architectural mechanism | Verification |
|---|---|---|
| API stability | Separate versioned HTTP contract | Contract tests and OpenAPI comparison |
| Database portability | QueryPlan, repository port, SQL dialect adapters | Same required tests on MariaDB and SQLite |
| Security isolation | Key identity, role, resource/field policies, default-deny | Negative authorization and leakage tests |
| Traceability | Request IDs, sanitized technical logs, same-transaction write audit | Audit/failure tests |
| Shared-host operation | Per-request PHP execution, simple file/DB stores | Real shared-host deployment |
| Controlled extensibility | Explicitly registered optional modules, no free SQL/script API | Independent security gates |
| Recoverability | Protected keys/config, migration and rollback | Backup/restore and upgrade rehearsal |

## 2. System context and instance model (C4 Context)

~~~mermaid
flowchart LR
    Apps["SASD clients<br/>HARALD / other applications"]
    Operator["Operator / Deployment"]
    A["SASD API V1<br/>Instance A"]
    B["SASD API V1<br/>Instance B"]
    Maria[("MariaDB<br/>Data A + Audit A")]
    SQLite[("SQLite file<br/>Data B + Audit B")]
    Apps -->|"HTTPS/JSON, Key A"| A
    Apps -->|"HTTPS/JSON, Key B"| B
    Operator -->|"private config and keys"| A
    Operator -->|"private config and keys"| B
    A --> Maria
    B --> SQLite
~~~

**“Universal” means a shared API contract on independently configured backends**, not an unlimited client-chosen database connection and not automatic joins across arbitrary database engines.

Each installation has **one configured primary data source** for its core operations. This boundary keeps credentials, audit transactions and application records isolated.

**Domain-specific workflows stay outside generic CRUD.** Library loans/returns, symptom validation or business-specific multi-step processes belong in their applications/domain services. Technical API keys are **not** end-user identities.

## 3. Container, deployment and trust-boundary view

~~~mermaid
flowchart TB
    Request["HTTPS client"] --> Web["TLS Webserver / PHP Handler"]
    subgraph Public["Public Document Root"]
      Entry["index.php"]
    end
    Web --> Entry
    subgraph Private["Protected, outside Document Root"]
      Code["src/ + PHP Dependencies"]
      Resources["Version 2 resource config"]
      Keys["Key store / Secrets / Role policies"]
      Logs["Sanitized logs + Rate-limit store"]
      File["SQLite database if selected"]
    end
    Entry --> Code
    Code --> Resources
    Code --> Keys
    Code --> Logs
    Code --> File
    Code --> Maria[("MariaDB if selected")]
~~~

### 3.1 Security boundaries

- **Untrusted Internet → HTTPS server:** route, query, headers, JSON and IP-related data are attacker-controlled until validated.
- **Webserver → PHP:** TLS/proxy metadata must come from a **verified** provider configuration; do not trust raw `X-Forwarded-Proto` or `Forwarded` headers from clients.
- **PHP → private filesystem:** resource config, keys, logs, SQLite, rate state and backups cannot be served by HTTP.
- **PHP → database:** only a selected least-privilege connection, parameterized SQL, approved resources.
- **Technical client → role → resource:** policy is evaluated for every requested operation and data field.
- **Instance A ↔ B:** code may be shared, but data, identities and private paths are **not**.

If the provider cannot guarantee that private files are outside the public document root, **production release is blocked**. An uncertain `.htaccess` rule is insufficient.

### 3.2 Hosting profiles

**Shared hosting/Linux:** per-request PHP execution, no persistent process assumptions; verify PDO drivers, private filesystem paths, atomic file operations and locks on the real provider.

**Windows/WSL development:** identical HTTP/data contracts against isolated MariaDB and SQLite test fixtures; no Windows-specific runtime semantics in core services.

**Independent application instances:** separate URLs, databases, resources, keys, logs, health context and permissions.

**Offline operator tools:** prepare/validate configuration, create/revoke keys, install controlled DB migrations. The host may lack an interactive CLI; trusted administrator workstations are a valid place to prepare changes. No public administration API is required.

### 3.3 Shared-hosting trade-offs

Without Redis and persistent workers, key state and rate limits need **persistent and atomically updated stores**. A single-instance rate limiter can use a protected file with verified locks or a suitable database table. No global rate guarantee is claimed across multiple nodes without a common atomic store.

SQLite is appropriate only when the hosting filesystem, permissions, journal mode and concurrent-writer behavior have been tested. **Do not recommend WAL unconditionally on network filesystems.**

## 4. Component architecture (C4 Components)

~~~mermaid
flowchart LR
    Router["HTTP Router<br/>Version / Query / JSON"]
    Auth["KeyAuthenticator"]
    Policy["RolePolicy / AccessGuard"]
    Ctrl["ResourceController"]
    Crud["CrudService"]
    Registry["ResourceRegistry"]
    Parser["QueryParser / Validator"]
    Port["RecordRepository<br/>TransactionPort"]
    Dialect["SQL Dialect Compiler"]
    Maria["MariaDbPdoAdapter"]
    SQLite["SqlitePdoAdapter"]
    Audit["AuditEventWriter"]
    Docs["OpenApiGenerator"]
    Router --> Auth --> Policy --> Ctrl --> Crud
    Crud --> Registry
    Crud --> Parser
    Crud --> Port
    Port --> Dialect
    Dialect --> Maria
    Dialect --> SQLite
    Crud --> Audit
    Registry --> Docs
    Policy --> Docs
~~~

### 4.1 Dependency direction

**HTTP → Application → Domain / Contracts ← Infrastructure.** The composition root/bootstrap selects concrete adapter implementations. A Controller does **not** construct PDO queries; a repository contract does **not** return HTTP-specific status codes.

The SQL compiler accepts **only typed, policy-validated QueryPlan nodes**. It must not receive free SQL fragments, raw client-selected identifiers, arbitrary filesystem paths or unknown database sources.

### 4.2 Responsibilities and invariants

| Component | Responsibility | Required invariant |
|---|---|---|
| Router/RequestParser | V1 routes, method, headers, JSON and bounded query syntax | Never choose database identifiers from raw input |
| ApiKeyAuthenticator | Verify key ID, digest and active/revoked status | Never log or return secrets |
| RolePolicy/AccessGuard | Method/resource/field/filter/view/relation/query rules | Default-deny across every data path |
| ResourceRegistry | External field names, physical mappings, types and constraints | Schema introspection cannot publish a table |
| CrudService | Authorized use cases, transaction and audit orchestration | No successful data write without required audit |
| QueryParser/QueryPlan | Typed operators, AND/OR, sort, page, projection, include | Bounded AST, no SQL fragments |
| Repository/TransactionPort | Storage-neutral persistence and local transaction control | No PDO objects exposed to services |
| MariaDbPdoAdapter | MariaDB-specific SQL and conversions | Passes common contract tests |
| SqlitePdoAdapter | SQLite SQL, PRAGMAs, file permissions and busy handling | Same required API semantics |
| AuditEventWriter | Required write event **in the same local commit** | Audit failure prevents successful data commit |
| OpenApiGenerator | Authorized paths and schemas | No exposure of forbidden resources |
| ConfigMigrator | Schema v1→v2, role/key/config checks | No permissive migration defaults |
| Health/Readiness/Logger | Minimal public status and protected diagnostics | No secrets/private metadata in responses |
| RateLimiter | Persistent bounded requests and abuse counters | Atomic state and fail-closed on state failure |

### 4.3 Proposed V1 structure

~~~text
src/
  Http/{Router,MiddlewareStack}.php
  Http/Controller/{ResourceController,OpenApiController,HealthController}.php
  Http/Parser/{ListQueryParser,JsonBodyParser}.php
  Application/{CrudService,RelationReadService,NamedReadService}.php
  Application/Query/{FilterExpression,SortSpec,PageSpec,QueryPlan}.php
  Domain/{ResourceRegistry,ResourceDefinition,FieldDefinition,RelationDefinition}.php
  Contracts/{RecordRepository,TransactionPort,AuditEventWriter}.php
  Infrastructure/Database/{MariaDbPdoAdapter,SqlitePdoAdapter,SqlDialect}.php
  Infrastructure/Audit/SqlAuditWriter.php
  Infrastructure/Diagnostics/{ReadinessService,MetricsCollector}.php
  Security/{ApiKeyStore,RolePolicy,RequestRateLimiter,RecordVisibilityPolicy}.php
  OpenApi/{SchemaBuilder,OpenApiGenerator}.php
  Config/{ConfigV1Loader,ConfigValidator,ConfigMigrator}.php
bin/{config-check,keys-create,keys-revoke,config-migrate}
tests/{Unit,Integration,Contract,Security,Migration,Deployment}/
~~~

This is a **proposed layout**, not an inventory of existing code. No requirement says every conceptual function needs a separate class. **Optional SHOULD components remain unimplemented unless approved.**

## 5. Versioned HTTP contract and data types

### 5.1 Routes

| HTTP | Path | Responsibility |
|---|---|---|
| GET | `/api/v1/{resource}` | List with approved filters, fields, ordering, paging and optional include |
| GET | `/api/v1/{resource}/{id}` | One record |
| POST | `/api/v1/{resource}` | Insert one record |
| PUT/PATCH | `/api/v1/{resource}/{id}` | Complete/partial change |
| DELETE | `/api/v1/{resource}/{id}` | Delete one record |
| GET | `/api/v1/openapi.json` | Protected role-specific OpenAPI |
| GET | `/api/v1/health` | Minimal public status |
| GET | `/api/v1/ready` | Authenticated operator readiness |
| GET | `/api/v1/queries/{name}` | **Optional** predefined read-only query |

Query-routing fallback via `index.php?route=/api/v1/...` must enforce the same behavior. No silent redirection from V0 to V1. Public type/field/error changes are **versioned**, documented and contract-tested.

### 5.2 JSON envelopes and errors

List:

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

Only authorized fields can appear. `count` means **this page's record count**, not total database matches. A total-count feature would need its own authorization/cost assessment. Central error mapping covers 401/403/404/409/422/429/500/503 and optional optimistic-locking 412/428. No SQL or private schema/file data in responses.

### 5.3 Cross-database data representation

| API type | Wire representation | Adapter obligation |
|---|---|---|
| String | UTF-8 JSON string | Validate length and character semantics |
| Integer | JSON integer within safe interoperability range | Avoid unsafe values greater than 2^53−1 as JSON numbers |
| UUID | Canonical lowercase string | Dedicated normalization |
| Boolean | `true` / `false` | Normalize MariaDB TINYINT(1) and SQLite INTEGER |
| Decimal | Decimal **string** | Avoid accidental rounding through PHP floating point |
| Date/time | ISO 8601 UTC, e.g. `2026-11-01T14:30:00Z` | No implicit local-time conversion |
| Null | JSON null | Only for nullable configured fields |

**Portability pitfall:** the same SQL statement does not imply the same semantics in MariaDB and SQLite. Differences in NULL, Boolean, timestamps, decimals, collation and LIKE must be tested with shared fixtures. Unsupported special types are rejected or explicitly excluded, **not silently misconverted**.

## 6. Dynamic views and critical data flows

### 6.1 Authorized read with related data

~~~mermaid
sequenceDiagram
    participant C as Client
    participant G as Auth/RolePolicy
    participant Q as QueryParser
    participant S as CrudService
    participant R as Repository
    C->>G: GET /api/v1/books?include=author
    G->>G: Key, role, resource and method
    G->>Q: Authorized fields/relations
    Q->>Q: Typed QueryPlan + budgets
    Q->>S: Approved query
    S->>R: list(QueryPlan)
    R-->>S: Records + allowed relation
    S-->>C: 200 data/meta without protected fields
~~~

Authorize **filter and sort fields and relation targets** as well as returned projection fields. A client must not infer hidden fields through counts, filter error details, join behavior or OpenAPI.

### 6.2 Data write and same-transaction audit

~~~mermaid
sequenceDiagram
    participant Client
    participant Policy
    participant Service as CrudService
    participant DB as SQL Adapter / DB
    Client->>Policy: POST/PUT/PATCH/DELETE + Key
    Policy->>Policy: Role, fields, limits
    Policy->>Service: Validated write
    Service->>DB: BEGIN
    Service->>DB: Change one allowed record
    Service->>DB: INSERT required audit event
    alt Both succeed
        Service->>DB: COMMIT
        Service-->>Client: 2xx persisted state
    else Constraint / audit failure
        Service->>DB: ROLLBACK
        Service-->>Client: Safe 4xx / 5xx
    end
~~~

The required audit event **must be within the same local commit boundary as the application data change**. If audit persistence fails, roll back the write. Denied requests without a business DB transaction are recorded as sanitized protected technical security events. **No distributed transaction or certified revision-proof audit log** is promised.

### 6.3 Persistent rate-limit decision

~~~mermaid
flowchart TD
    Request["Request with technical key and client context"] --> Auth{"Authenticated?"}
    Auth -->|"No"| Abuse["Bounded invalid-key abuse counter<br/>401 / 429"]
    Auth -->|"Yes"| Store{"Atomic rate store available?"}
    Store -->|"No"| Closed["503 Fail Closed"]
    Store -->|"Yes"| Budget{"Budget available?"}
    Budget -->|"No"| Limit["429, optional Retry-After"]
    Budget -->|"Yes"| Permit["Role policy and business request"]
~~~

A PHP static variable does not reliably survive a new HTTP request. File-based state needs tested atomic update/`flock` behavior; a database-backed alternative must also preserve atomicity. Client IP headers are trustworthy only when sourced from a verified proxy. A local limiter is not automatically a distributed/global cluster limiter.

## 7. Configuration and resource schema V2

### 7.1 Resource contract

The resource configuration uses `schemaVersion: 2`. Each `ResourceDefinition` declares:

1. Public resource name.
2. Physical table or approved **read-only** view.
3. Primary-key mapping.
4. External field aliases, database columns, types, nullable and read/write flags.
5. Allowed HTTP methods.
6. Optional declared relationship with target, key and cardinality.
7. Associated role policies from a **separately protected** configuration.

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
        "title": {"column": "book_title", "type": "string", "read": true, "write": true}
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
    }
  }
}
~~~

This is a **short illustrative schema**, not a deployable configuration file. The relation requires a separately authorized `authors` resource. `connectionRef` is resolved only from protected operator settings and can **never** be replaced by a request parameter.

### 7.2 Aliases, views, relationships and introspection

An external field `title` may map to physical SQL column `book_title`, keeping client contracts independent from storage naming. A resource with `source.kind=view` is **always GET-only** even if its SQL view could technically be updated.

Schema introspection may help validate or generate drafts for the operator, but must **never** automatically expose a new table, view or column to clients.

`include=author` may expand **one** explicitly allowed relationship level. Validate the target resource, cardinality, public fields and authorization before SQL. Prevent unbounded N+1 loads and enforce maximum related records/total result size. If expansion cannot safely fit the contract or query budget, reject it.

### 7.3 Typed QueryPlan, not arbitrary SQL

~~~text
HTTP query parameters
  → Parse + reject invalid grammar
  → ResourceRegistry field/operator validation
  → RolePolicy, types and budget checks
  → FilterExpression / SortSpec / PageSpec / RelationSpec
  → Authorized QueryPlan (typed AST only)
  → Adapter dialect compiler
  → Prepared SQL and bound values
~~~

Proposed operators: `eq`, `ne`, `lt`, `lte`, `gt`, `gte`, `in`, `isNull`, `contains`, `startsWith`. Combine allowed AND conditions and at most **one controlled OR group**, not unlimited recursive RSQL.

Field projection `fields=id,title`, multi-field sort `sort=title,-id`, `limit`/`offset` and `include` remain bounded. Proposed defaults (to be measured) include at most eight AND, eight OR options, three sort columns, 20 IN values and one relation level.

**Text comparison:** MariaDB and SQLite may differ in Unicode/collation and LIKE behavior. The shared guaranteed semantics must be **defined and demonstrated**, not assumed to equal comprehensive Unicode full-text search.

## 8. Two adapters behind one required contract

~~~mermaid
flowchart TB
    App["CrudService / QueryPlan"]
    Port["RecordRepository + TransactionPort"]
    App --> Port
    Port --> Maria["MariaDbPdoAdapter / PDO mysql"]
    Port --> SQLite["SqlitePdoAdapter / PDO sqlite"]
    Maria --> DB1[("MariaDB")]
    SQLite --> DB2[("SQLite")]
    Tests["Shared Contract Test Suite"] -.-> Maria
    Tests -.-> SQLite
~~~

### 8.1 MariaDB/MySQL adapter

Continue PDO, `utf8mb4`, prepared statements, safe quoting, typed conversions and neutral database error mapping. No client SQL interpreter or public DDL.

### 8.2 SQLite adapter

Use `pdo_sqlite` and a verified protected DB file outside the document root. For every connection apply `PRAGMA foreign_keys=ON` and a suitable `busy_timeout`. Handle `SQLITE_BUSY` with finite, controlled behavior. Verify journal/WAL mode on the **real filesystem**, particularly if network storage is involved. Never permit a client-selected database path.

### 8.3 Common invariants

- Equivalent logical resources and fixtures produce equivalent promised **HTTP/JSON schemas and error categories** on both adapters.
- Switching adapters **does not migrate records**.
- Composite primary keys, arbitrary cross-database joins and extra engines are not V1 MUST scope.
- Audit and a business data change must share **one local transaction** and DB context; SQLite audit cannot silently be written through an unrelated second connection.
- Constraints, NULL/Boolean/decimal/time types, filters, sorting and collations require shared blackbox tests.

## 9. Identity and security

### 9.1 Technical API-key lifecycle

Offline-generated high-entropy keys (proposed minimum **256 bits**) are shown once. Store non-secret key ID, cryptographic verifier/digest, enabled/revoked state and operator-assigned role in protected configuration. Verify by ID, constant-time digest comparison and active status. Key rotation/revocation is possible without changing program source.

**No public key administration endpoint** is required. A technical key does not become a safe hidden secret inside public JavaScript or a widely distributed app; those cases need another trust/auth layer.

### 9.2 Role policy applied everywhere

~~~text
Authenticated client
  └─ RolePolicy
      ├─ Allowed resource?
      ├─ Allowed HTTP method?
      ├─ Allowed read/write/projected fields?
      ├─ Allowed filter and sort fields?
      ├─ Allowed view/relation targets?
      ├─ Allowed optional named query?
      └─ Optional trusted record-visibility constraint?
           └─ Authorized QueryPlan → Repository
~~~

**Default-deny** for any new resource, field or method. Operator diagnostics permissions do **not** automatically give access to all business data. Policies must be checked before SQL and, for OpenAPI, before publishing documentation schemas.

### 9.3 Optional record visibility (SHOULD V1-F14)

A `RecordVisibilityPolicy` may bind server-derived constraints such as `owner_id = authenticatedOwnerId`. The owner value must not be freely supplied by the client. Apply the same constraint to read, count, relation and write operations using atomically safe SQL conditions. Do not advertise multi-tenancy unless both adapters pass comprehensive isolation tests.

### 9.4 V1 threat model

| Risk | Protection | Required negative test |
|---|---|---|
| Stolen/reassigned key | Protected store, revocation, roles | Revoked key becomes unusable |
| Role escalation | Resource/method/field policy | Reader cannot write |
| Hidden data via filter/count/expand/OpenAPI | Policy applied before query/schema | Forbidden field remains undiscoverable |
| SQL injection | Typed AST and bound values | Input is data, not SQL code |
| Cross-instance access | Per-instance keys/DB/config/logs | Key A cannot access B |
| Public SQLite file | Physical private path and permissions | HTTP file retrieval fails |
| Request exhaustion | Rate, body/query/expand budgets | Bounded 429/413/4xx |
| Audit bypass | Write and audit same COMMIT | Simulated audit failure rolls back |
| Permissive migration | Strict schema/role validation | Unknown roles do not grant access |
| TLS/header bypass | HTTPS with verified proxy trust | Forged forwarding not accepted |

## 10. Audit, health, logging and privacy

### 10.1 Internal audit table

Install `sasd_api_audit_events` per **instance database** through a protected **offline operator migration**. Proposed fields: `event_id`, `occurred_at_utc`, `instance_id`, `request_id`, `client_id`, `resource_name`, `method`, `record_key`, `outcome`.

No full record bodies or API keys. No generic public API read access to the audit table.

Write path: **BEGIN → data mutation → audit INSERT → COMMIT**. If audit fails, roll back. This does **not** claim administrator-proof/tamper-proof certified audit storage.

### 10.2 Health and readiness

`/api/v1/health` returns only simple non-sensitive availability. `/api/v1/ready` requires an operator role and checks configuration, DB adapter/connection and internal audit structure without exposing credentials, paths or physical table details.

### 10.3 Logging and optional metrics

Protected JSONL logs: UTC, request ID, instance, route, status, error type, safe client ID and coarse duration. No raw Auth headers, SQL or personal payloads. Operator defines log rotation and retention.

**SHOULD V1-F27:** small aggregate counters for requests, failures and latency without a mandatory Prometheus/Grafana/Datadog integration or persistent worker.

## 11. OpenAPI and language-independent clients

`OpenApiGenerator` derives routes, parameter schemas, error models, data types and `X-API-Key` authentication from **the same authorized resource definitions** used at runtime.

OpenAPI is protected and **role-aware**; a role may only discover its approved resources/fields. Runtime permission checks still apply. OpenAPI 3.1 is preferred if the generator and validator correctly support it; otherwise document an explicit compatible 3.0.x version before release.

Provide usable GET/error examples for **curl, C#/.NET HttpClient and Java HttpClient**. They must not depend on PHP-specific session internals. Dedicated SDKs or desktop components are not mandatory.

## 12. Configuration and release migration V0 → V1

~~~mermaid
flowchart TD
    Backup["V0 DB + Config backup / Test restore"] --> Copy["V0 staging copy"]
    Copy --> Convert["schemaVersion 1 -> 2<br/>Resources / Fields / Roles"]
    Convert --> Valid{"Config and policies valid?"}
    Valid -->|"No"| Fix["Manual correction, no production cutover"]
    Valid -->|"Yes"| Install["Offline internal audit schema migration"]
    Install --> Tests["Contract / Security / Readiness tests"]
    Tests --> Gate{"Release gates passed?"}
    Gate -->|"No"| Fix
    Gate -->|"Yes"| Enable["Explicitly enable V1 routes"]
~~~

**Migration rules:**

1. Backup both database and private configuration and demonstrate actual restore before changes.
2. Convert resource and field definitions using **deny-by-default** for new roles and options.
3. Do not automatically assign old V0 keys administrator or owner rights.
4. Prepare internal audit tables through the protected operator path; no HTTP DDL endpoint.
5. Validate new config **completely**, then activate atomically; never permit partly active config.
6. Preserve V0 routes or migrate clients intentionally; do not silently rewrite contract semantics.
7. Rollback needs matching code/config/schema and possibly a DB restore; do not promise automatic lossless reversal.
8. Switching to SQLite is a **separate data-migration** project, not an implicit adapter feature.

## 13. Optional V1 modules

| Requirement | Optional module | Limited behavior | Security gate |
|---|---|---|---|
| **V1-F09** | NamedReadService | Operator-defined, read-only SQL templates with bound typed params | No raw SQL from client; roles/limits |
| **V1-F14** | RecordVisibilityPolicy | Trusted server-derived row predicate | Same isolation on reads/writes/counts/relations |
| **V1-F16** | OptimisticLock | Version/ETag and atomic If-Match conditional update | Concurrent stale writes rejected with 412/428 |
| **V1-F22** | Controlled extension registry | Explicitly registered controllers using common pipeline | No authentication/policy/audit bypass |
| **V1-F27** | MetricsCollector | Privacy-preserving aggregate counters | No full payloads or mandatory worker |

Disabled optional modules must **not** appear in OpenAPI as available. They must not expand the required V1 core or bypass central security.

## 14. Test architecture and acceptance

| Level | Required verification |
|---|---|
| Unit | Config/role validators, typed query AST, normalization, key and rate logic |
| Integration | Both DB adapters, CRUD/constraints/audit, views and relationships |
| Contract | Same V1 HTTP/JSON response/error contract per adapter; V0 separately versioned |
| Security | Escalation, SQL injection, field/count/join leaks, private paths, TLS, rate-fail-closed |
| Migration | V0 config/schema → V1 staging, negative validation and rollback |
| Deployment | PHP shared hosting, PDO SQLite, locking and protected filesystem |
| Documentation | OpenAPI vs actual routes, curl/Java/.NET sample correctness |

**Fitness rules:** Controllers do not depend directly on PDO; repository port has no HTTP status codes; query compiler accepts no untrusted SQL identifiers; unknown resources stay private; both adapters pass shared required tests; data write cannot commit if audit fails.

### 14.1 V1 acceptance scenarios

| Scenario | Focus |
|---|---|
| AT-V1-01 | Versioned API contract |
| AT-V1-02 | Field aliases/allowlist and no automatic exposure |
| AT-V1-03 | CRUD and type parity between MariaDB and SQLite |
| AT-V1-04 | Filter/AND/OR/projection/paging |
| AT-V1-05 | Bounded authorized relationships |
| AT-V1-06 | View is read-only |
| AT-V1-07 | Instance/database/key isolation |
| AT-V1-08 | Technical-key rotation and roles |
| AT-V1-09 | Atomic writes and conflicts |
| AT-V1-10 | Injection, size/rate, TLS and CORS |
| AT-V1-11 | Sanitized logging and write audit |
| AT-V1-12 | Health/readiness during DB outage |
| AT-V1-13 | OpenAPI corresponds to real routes |
| AT-V1-14 | Config migration, backup and restore |
| AT-V1-15 | curl, Java and .NET contracts |
| AT-V1-16 | Unit, integration, contract and security checks in CI |

**Release gate:** accepted V0 still works; **all 22 V1 MUST** items pass; both adapters pass the shared required contract; hosting, security and restore are verified. SHOULD items are blocking only when explicitly approved as included.

## 15. Architecture Decision Record candidates

| Candidate | Proposal | Rationale |
|---|---|---|
| ADR-V1-001 | Separate V1 contract namespace | Avoid silent V0 breaking changes |
| ADR-V1-002 | Exactly two mandatory adapters | Genuine portability tests, not superficial connector list |
| ADR-V1-003 | Typed QueryPlan/AST | Avoid client SQL interpreter |
| ADR-V1-004 | Configured field aliases and read-only views | Stable external names despite SQL schema changes |
| ADR-V1-005 | Technical key identities plus RBAC | Avoid building a full end-user identity platform |
| ADR-V1-006 | Audit and write in same local transaction | Audit failure causes rollback |
| ADR-V1-007 | Persistent rate store with fail-closed behavior | Security on shared hosting without Redis |
| ADR-V1-008 | Role-aware OpenAPI generated from registry | No divergent hand-maintained schema |
| ADR-V1-009 | Config schema v2 and protected migration | Never open previously private permissions |
| ADR-V1-010 | SHOULD modules remain separate | Avoid uncontrolled scope expansion |
| ADR-V1-011 | Shared-host compatibility is mandatory | No hidden Docker/worker dependency |

These are **proposed** decisions, not accepted individual ADR files. Acceptance requires context, alternatives, consequences and tests documented in [ADR register](adr/README.md).

## 16. Risks, open decisions and boundaries

1. **Provider:** Availability of PDO SQLite, private paths, `flock`, safe file replacement and required DB rights must be verified.
2. **SQLite:** Busy/locks, journal mode, network storage and write concurrency require actual tests; no limitless concurrency promise.
3. **Audit:** Internal table/DB rights and **same transaction** must be proven before allowing writes.
4. **Limiter:** Fail-closed protects data at potential cost to availability; this must be an explicit operator decision.
5. **Query semantics:** Collation, Unicode, NULL, boolean, decimals and UTC require fixed, common tested guarantees.
6. **Public clients:** Technical keys are not end-user identities; browser CORS is not a substitute for authentication.
7. **OpenAPI and query leaks:** Never disclose hidden fields through filters, counts, sorting, relations or schema.
8. **Upgrade/restore:** Backups, real restore tests, V0→V1 config migration and contract compatibility are mandatory.
9. **SHOULD scope:** Optional modules must not destabilize the MUST API.
10. **Performance:** Do not invent latency SLOs; always record host, data volume, concurrency and routing mode for measurements.

## 17. Traceability: V1 requirements → architecture → tests

| V1 requirement | Architecture | Technical test |
|---|---|---|
| V1-F01 | `5 versioned HTTP contract | T-V1-01 |
| V1-F02 | `7 registry/field aliases | T-V1-02 |
| V1-F03 | ``5/8 CRUD | T-V1-03 |
| V1-F04 | ``5/8 type validation | T-V1-03 |
| V1-F05 | `7 typed query filters | T-V1-04 |
| V1-F06 | ``5/7 sort/page/projection | T-V1-04 |
| V1-F07 | `7 one-level relations | T-V1-05 |
| V1-F08 | `7 read-only views | T-V1-06 |
| V1-F09 | `13 named queries (**SHOULD**) | T-V1-15 optional |
| V1-F10 | `8 two adapters | T-V1-03/07 |
| V1-F11 | ``2–3 instance isolation | T-V1-07 |
| V1-F12 | `9 key lifecycle | T-V1-08 |
| V1-F13 | `9 RBAC | T-V1-08 |
| V1-F14 | `13 row visibility (**SHOULD**) | T-V1-16 optional |
| V1-F15 | ``6/8/10 transactions/errors | T-V1-09 |
| V1-F16 | `13 optimistic lock (**SHOULD**) | T-V1-16 optional |
| V1-F17 | ``6/9 rate and query limits | T-V1-10 |
| V1-F18 | ``3/9 TLS/CORS | T-V1-10 |
| V1-F19 | `10 write audit/logging | T-V1-09 |
| V1-F20 | `10 health/readiness | T-V1-11 |
| V1-F21 | `11 OpenAPI | T-V1-01 |
| V1-F22 | `13 extensions (**SHOULD**) | T-V1-16 optional |
| V1-F23 | `12 configuration versioning | T-V1-12 |
| V1-F24 | ``3/12 deployment/restore | T-V1-12 |
| V1-F25 | `11 curl/Java/.NET | T-V1-13 |
| V1-F26 | `14 test gates | T-V1-14 |
| V1-F27 | ``10/13 metrics (**SHOULD**) | T-V1-16 optional |

## 18. Implementation stages, review and Definition of Done

| Stage | Technical goal | Gate |
|---|---|---|
| P0 | Stabilize the accepted V0 baseline | V0 security/acceptance tests green |
| P1 | V1 paths, aliases, config schema v2 | Contract and migration tests |
| P2 | SQLite adapter and normalized types | Common two-adapter CRUD/query tests |
| P3 | QueryPlan, views and relationships | Query/SQL injection/authorization checks |
| P4 | Key store, roles and rate limits | Security and fail-closed checks |
| P5 | Audited writes, readiness, operations | Audit rollback and restore tests |
| P6 | OpenAPI and three client languages | Generated schema matches real API |
| P7 | Approved optional SHOULD modules | Independent security/contract gates |
| P8 | Formal V1 acceptance | All MUST items, both adapters and real hosting |

Do not opportunistically implement all researched reference features. Security, authentication, authorization, configuration, SQL dialect and audit changes need a written architecture/ADR review and verified tests.

**Status: detailed proposed V1 architecture. No implemented V1 code, completed test, or release approval is claimed.**
