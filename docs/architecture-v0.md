# SASD API Server – Software Architecture V0

> **Language:** **English (default)** | [Deutsch](architecture-v0.de.md)  
> **Status:** architecture proposal, neither implemented nor accepted. **Version:** 0.1 · **Date:** November 1, 2026 · **Target:** V0.1.0.  
> Based on [V0 requirements](requirements-v0.md), [V0 technical specification](technical-specification-v0.md), [reference research](reference-products-feature-catalog.md).  
> Future: [Architecture V1](architecture-v1.md).

## 1. Purpose, scope and design principles

This document explains the **internal architecture, dependencies, security boundaries, operating profiles and architectural decisions** of SASD API Server. The requirements define **what** must be achieved; the technical specification describes the proposed **how**; this architecture explains **why the design separates responsibilities this way**, including trade-offs, observable data flows, risk controls and tests.

**Keep V0 intentionally small:** PHP 8.4, MariaDB/MySQL via PDO, standardized HTTPS/JSON CRUD endpoints and simple bounded lists. Only **explicitly approved** tables, fields and HTTP methods are exposed. The same source revision can run as multiple isolated installations at different URLs, each with its own DB credentials, secrets, resources and logs.

V0 is **not** a clone of DreamFactory, a JBoss application server, nor a domain server implementing library loans or health workflows. All class names, path layouts, limits and ADR candidates in this document are **design proposals**, not evidence of existing executable files.

### 1.1 Architecture goals

| Quality goal | Strategy | Verification |
|---|---|---|
| Easy deployment | A normal PHP request, no permanent worker, Docker, Redis or root | Fresh shared-host installation |
| Safe default | Explicit deny-by-default policies, API key and HTTPS | Negative security tests |
| Understandable design | Clear layers, small interfaces and documented choices | Code review and unit tests |
| Independent deployments | Instance-specific data in private configuration | Two test installations with distinct keys/DBs |
| Maintainable expansion | Repository port instead of direct PDO in controllers | Fake-repository tests and future V1 adapter |
| Observability | Request ID, sanitized logs, health and protected diagnostics | Failure/log tests |
| Predictable HTTP | Versioned routes, uniform JSON/errors | Blackbox HTTP contract tests |

### 1.2 Explicit non-goals

No account registration, SSO, RBAC, full tenancy, GraphQL, arbitrary SQL, automatic exposure of all tables, cross-database queries, joins, bulk writes/deletes, schema editing over HTTP, workflow engine, file/email services or administration UI. Business table migrations, application-specific invariants and backups are responsibilities of the application/operator, **not generic CRUD features**.

## 2. System context and trust boundaries (C4 Level 1)

~~~mermaid
flowchart LR
    Users["Trusted technical clients<br/>.NET / Java / curl / backend"]
    Operator["Operator<br/>Deployment / Config / Keys"]
    API["SASD API Server<br/>V0 instance"]
    DB[("MariaDB / MySQL")]
    Users -->|"HTTPS + JSON + X-API-Key"| API
    Operator -->|"Protected maintenance channel"| API
    API -->|"PDO + least-privilege SQL account"| DB
~~~

**Outside the API Server:** UI, application domain workflows, database schemas/backups and the hosting provider's TLS termination. **Inside:** request validation, permissions, generic CRUD orchestration, safe SQL, error translation and technical diagnosis.

### 2.1 Five relevant trust boundaries

1. **Internet → webserver:** every URL, body, query and HTTP header is untrusted.
2. **Webserver/proxy → PHP:** TLS/`Forwarded` metadata can be trusted **only** when proven to originate from a controlled provider/proxy.
3. **PHP → private files:** configuration, keys, logs and credentials must be physically separated from document root.
4. **PHP → database:** constrained SQL account and prepared statements; API permissions are an additional layer.
5. **Instance A ↔ instance B:** no cross-sharing of keys, application records, configs or logs simply because code is identical.

### 2.2 Client identity limitation

A static key embedded in public JavaScript or a widely distributed binary is not a reliably protected secret. V0 is designed for **trusted technical clients** or clients using a trusted backend proxy, **not** direct public multi-user registration/authorization.

## 3. Deployment and runtime containers (C4 Level 2)

~~~mermaid
flowchart TB
    Client["Client via HTTPS"] --> Web["Webserver / PHP Handler"]
    subgraph Public["Public document root"]
        Index["index.php<br/>Front Controller"]
    end
    Web --> Index
    subgraph Private["Private paths outside document root"]
        Code["PHP src/ + Dependencies"]
        Config["Resource configuration"]
        Secrets["DB credentials / API key"]
        Logs["Protected JSONL logs"]
    end
    Index --> Code
    Code --> Config
    Code --> Secrets
    Code --> Logs
    Code --> DB[("MariaDB")]
~~~

### 3.1 Public vs private deployment boundaries

The **physical** deployment must serve only intended public HTTP entry files. Resource config, API keys, database passwords, logs and ideally PHP application classes are **outside the document root**, even if source control uses one repository. A potentially ignored `.htaccess` rule is **not sufficient** protection.

If the provider cannot verify these boundaries, **do not approve production deployment**. Source-tree directory names and physical public serving paths are not the same concept.

Baseline test combination: PHP 8.4 and MariaDB 10.11. No required long-lived service, Redis, Docker, queue or superuser permissions. PHP extensions, writable paths and driver behavior must be checked on the actual provider.

### 3.2 URL rewriting and mandatory fallback

Preferred: `/api/v0/books`. Fallback on restricted hosts: `/index.php?route=/api/v0/books`. Both map to the **same internal router and policy**.

Treat `route` as a logical API route, **never** a local path. Reject traversal, null bytes, double encoding, duplicate/ambiguous parameters and invalid route segments. Choose the public base URL from **trusted instance configuration**, not arbitrary Host headers.

For two deployments on the same provider, give each unique URL, private filesystem scope, keys, database and logs; do not allow one installation to resolve another's credentials or files.

## 4. Components (C4 Level 3)

~~~mermaid
flowchart LR
    Entry["index.php / Bootstrap"] --> Router["HTTP Router / Request / Response"]
    Router --> Guard["TLS + API Key + Policy + Limits"]
    Guard --> Ctrl["ResourceController"]
    Ctrl --> Service["CrudService"]
    Service --> Registry["ResourceRegistry / Validator"]
    Service --> Port["RecordRepository (contract)"]
    Port --> Adapter["MariaDbPdoRepository"]
    Adapter --> DB[("MariaDB")]
    Ctrl -.-> Result["JsonResponder / ErrorMapper"]
    Guard -.-> Log["SanitizedLogger / Request ID"]
~~~

### 4.1 Responsibilities

| Component | Owns | Must not do |
|---|---|---|
| `index.php` / bootstrap | Safe setup, object composition, outer exception boundary | Execute business SQL itself |
| Request/Router | Decode/validate method, path, headers, body, route selection | Treat URL as permission for arbitrary DB table |
| Security pipeline | HTTPS, key, size budget, resource/method access | Grant all tables for a valid key |
| ResourceController | Translate HTTP to typed CRUD request and back | Direct PDO access |
| CrudService | Validate permissions/input, coordinate transactions and record operations | Read raw server globals or SQL dialects |
| ResourceRegistry | Allowed table, PK, fields, methods and query rules | Auto-register unknown database tables |
| Validator/ListQueryParser | JSON types, equality filters, sort, paging and budgets | Insert user-supplied SQL syntax into queries |
| RecordRepository port | Storage-neutral list/find/insert/replace/patch/delete | HTTP status codes or PDO-specific handles |
| MariaDbPdoRepository | Safe SQL compilation, binding, transactions and DB error mapping | Handle client authentication or headers |
| ErrorMapper/JsonResponder | Predictable statuses, JSON and safe errors | Leak SQL, stack traces and credentials |
| SanitizedLogger/Health | Minimal status, private UTC technical logs | Store complete sensitive payloads/secrets |

### 4.2 Enforced dependency direction

~~~text
HTTP → Application → Domain / Contracts ← Database Infrastructure
  │         │             ↑
  └→ Security / Policy ────┘
  └→ Response / Errors

Bootstrap (composition root) knows concrete types.
Domain and services know only explicit ports/definitions.
~~~

Controllers do not import PDO; domain types do not depend on `$_SERVER`. Interfaces remain **small** and are introduced for actual use cases/tests, not imaginary databases. This is a deliberate balance between future adapter support and V0 simplicity.

### 4.3 Proposed source organization

~~~text
index.php
composer.json
src/
  Bootstrap.php
  Http/{Request,Response,Router,JsonParser,JsonResponder}.php
  Http/Controller/ResourceController.php
  Application/{CrudService,ListQuery}.php
  Domain/{ResourceDefinition,FieldDefinition,ApiError}.php
  Contracts/RecordRepository.php
  Security/{ApiKeyAuthenticator,AccessPolicy}.php
  Config/{ConfigLoader,ConfigValidator}.php
  Infrastructure/Database/{PdoFactory,MariaDbPdoRepository}.php
  Infrastructure/Logging/SanitizedLogger.php
  Infrastructure/Diagnostics/HealthService.php
config/example.resources.json
tests/{Unit,Integration,Contract,Security,Deployment}/
docs/{installation,api,operations}.md
~~~

This is **not** an inventory of current files. Package and class names must be finalized during the first development slice. Avoid redundant abstractions when a simpler structure satisfies the same architectural tests.

## 5. Dynamic request views

### 5.1 GET list

~~~mermaid
sequenceDiagram
    participant C as Client
    participant R as Router
    participant G as Security
    participant S as CrudService
    participant D as Repository
    C->>R: GET /api/v0/books?limit=25 + API Key
    R->>G: Normalized request
    G->>G: HTTPS / Key / Resource / Method
    G->>S: ListQuery + approved ResourceDefinition
    S->>S: Field/filter/limit validation
    S->>D: list(definition, query)
    D-->>S: PageResult
    S-->>C: 200 JSON data/meta + Request ID
~~~

**Invariant:** If a filter is invalid, do **not** ignore it and execute an unrestricted SELECT. The repository selects only configured readable columns. Authentication and permissions precede all data queries.

### 5.2 PATCH one record and rollback

~~~mermaid
sequenceDiagram
    participant C as Client
    participant G as Security
    participant V as Validator
    participant D as MariaDbPdoRepository
    participant DB as MariaDB
    C->>G: PATCH /api/v0/books/42 + JSON + Key
    G->>V: Authorized request
    V->>V: Validate types and writable fields
    V->>D: patch(id=42, approved values)
    D->>DB: BEGIN
    D->>DB: UPDATE ... WHERE id = :id
    alt Successful operation
      D->>DB: SELECT readable fields, COMMIT
      D-->>C: 200 + persisted record
    else Constraint or DB failure
      D->>DB: ROLLBACK
      D-->>C: Typed error -> safe 4xx/5xx
    end
~~~

**Concurrency caveat:** `rowCount()=0` may indicate an unchanged record, **not necessarily a missing ID**. Detect the distinction safely. V0 does not provide optional optimistic locking; concurrent clients may overwrite each other's latest committed values.

## 6. Public API and resource boundaries

### 6.1 V0 route matrix

| Method | Path | Behavior | Success |
|---|---|---|---|
| GET | `/api/v0/{resource}` | List with equality/AND, sort and paging | 200 |
| GET | `/api/v0/{resource}/{id}` | Read by single primary key | 200 / 404 |
| POST | `/api/v0/{resource}` | Create one record | 201 + Location |
| PUT | `/api/v0/{resource}/{id}` | Replace all client-writable fields | 200 |
| PATCH | `/api/v0/{resource}/{id}` | Change selected client-writable fields | 200 |
| DELETE | `/api/v0/{resource}/{id}` | Delete one record | 204, no body |
| GET | `/api/v0/health` | Minimal public status | 200/503 |

All data routes require `X-API-Key`, and write requests use `Content-Type: application/json`. `health` is reserved. Full header/response details are defined in the technical specification.

### 6.2 Response envelopes

~~~json
{
  "data": [{"id": 42, "title": "Example"}],
  "meta": {
    "limit": 25,
    "offset": 0,
    "count": 1,
    "hasMore": false
  }
}
~~~

`meta.count` counts only elements on **this page**, not all database matches. `hasMore` can use a bounded `limit+1` fetch. Single records are wrapped in `{"data":{...}}`. DELETE 204 has no body.

### 6.3 Input and query constraints

- `filter[field]=value` for equality; approved conditions combine with AND; no OR trees or arbitrary joins.
- `sort=field` / `sort=-field` with **one** approved sort field; default stable primary key.
- `limit` initial default 25 and max 100; `offset` initial default 0 and proposed max 10,000. Validate against the actual host before release.
- Initial suggested JSON body max **64 KiB**; enforce actual read length, not Content-Length alone.
- Validate duplicate/unknown/invalid fields and query parameters using one typed `ListQuery` model.

**PUT** requires all client-writable fields; **PATCH** only changes submitted allowed fields and rejects an empty object. System-owned fields remain unchanged. Integer or explicitly configured UUID IDs are single-column only.

### 6.4 Central error contract

~~~json
{
  "error": {
    "code": "validation_failed",
    "message": "Invalid request body.",
    "details": [{"field":"title","code":"required"}]
  },
  "requestId":"9fc085c3b2a043d3"
}
~~~

Typical error categories: 400 malformed request/query; 401 key; 403 access denied; 404 resource/record; 405 method; 409 unique/FK constraint; 413 payload; 415 content type; 422 field validation; 500 unexpected error; 503 unavailable. Final 403/405/404 precedence is specified and contract-tested. **Never** expose SQL, passwords, PDO stack traces or internal paths.

## 7. Configuration as a declarative API model

~~~mermaid
flowchart LR
    File["Private resources JSON"] --> Loader["ConfigLoader"]
    Keys["Protected secrets/env"] --> Loader
    Loader --> Check{"Schema/allowlist valid?"}
    Check -->|"yes"| Registry["Immutable ResourceRegistry per request"]
    Check -->|"no"| Closed["Fail closed: no data routes"]
~~~

Configuration defines `schemaVersion`, `instanceId`, API prefix, connection references, security/size budgets and authorized resources. Each resource declares physical table, single primary key, allowed methods and field rules (type, readable/writable, filterable/sortable).

Secrets are **referenced, not embedded** in source-controlled example JSON. Invalid configuration fails closed: no automatic exposure of all database tables.

### 7.1 Illustrative resource definition

~~~json
{
  "schemaVersion": 1,
  "instanceId": "books-test",
  "apiPrefix": "/api/v0",
  "database": {"driver": "pdo_mysql", "passwordEnv": "SASD_DB_PASSWORD"},
  "security": {"apiKeyEnv": "SASD_API_KEY", "requireHttps": true},
  "resources": {
    "books": {
      "table": "books",
      "primaryKey": {"field": "id", "type": "integer"},
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {"type": "integer", "readable": true, "clientWritable": false},
        "title": {
          "type": "string", "maxLength": 200,
          "readable": true, "clientWritable": true,
          "filterable": true, "sortable": true
        }
      }
    }
  }
}
~~~

This is an **abbreviated conceptual example**, not a runnable configuration file. Complete schema validation and defaults must be implemented/tested before release.

### 7.2 Field and type rules

**Readable is not writable.** IDs may be visible but never client-editable. Each write field can be required/optional and nullable/non-nullable. PUT provides the full client-writable set (including explicit nulls where allowed). PATCH changes only supplied fields. The repository must not select non-readable columns just because SQL permits them.

Supported primary keys: positive integer or explicitly approved canonical UUID; no composite keys or arbitrary identifier expressions. Check integer range and JSON serialization for interoperability.

## 8. Persistence architecture and transactions

### 8.1 Storage-independent repository contract

Conceptual operations: `list(resource, query)`, `find(resource, id)`, `insert(resource, input)`, `replace(resource, id, input)`, `patch(resource, id, input)` and `delete(resource, id)`. Methods exchange typed records/errors, **never PDO handles or raw SQL**.

A narrow transaction method/port may be added if necessary. V0 does not need a distributed unit-of-work abstraction.

### 8.2 Safe MariaDB SQL and failures

1. Physical SQL identifiers are derived solely from validated resource/field definitions, then quoted according to MariaDB rules.
2. Values and filters use PDO binding; no raw concatenation of user data.
3. Sort identifier comes from the allowlist; limit/offset are strictly validated integers.
4. SELECT projects only approved columns. UPDATE/DELETE require complete PK conditions.
5. Single HTTP write uses a real transaction; exceptions trigger rollback.
6. Unique/FK/missing-row/DB outages use typed neutral errors; HTTP status is assigned by ErrorMapper, **not** the PDO adapter.
7. MariaDB schema must support required constraints, transactional engine and `utf8mb4`; verify actual driver behavior.

### 8.3 Database permissions

Each instance should have a dedicated SQL account with only necessary SELECT and, for writable resources, INSERT/UPDATE/DELETE privileges. Shared-hosting providers may restrict grant granularity; document and review such exceptions. **API allowlists remain mandatory even when SQL privileges are broader.**

### 8.4 Data responsibility

The server does not automatically create production business tables, migrate domain schemas or schedule backups. Operator/application owns schema lifecycle, consistency and restoration. V0 should **not** be approved for sensitive production health or similarly protected data before relevant additional application- and regulatory-specific security requirements are satisfied.

## 9. Security architecture and threat model

| Threat | Control | Negative check |
|---|---|---|
| Unapproved table/column | Explicit resource/field allowlist | Unknown table/column returns safe error |
| SQL injection | Bound values, server-selected identifiers | SQL special input is treated as data |
| Key disclosure | Private storage, header only, sanitized logs | No key in response/log |
| TLS/proxy bypass | Verified HTTPS and trusted proxy boundary | Plain HTTP/forged forwarding gets no data |
| Illegal modification | Separate method and writable field policies | Readonly ID cannot be changed |
| Resource exhaustion | Body, pagination and query limits | Controlled 413/4xx |
| Error-detail leakage | Central ErrorMapper/Health | No SQL, stack trace or private path |
| Cross-instance access | Separate config, DB, keys and logs | Key A cannot access data B |
| Public private files | Physical separation from document root | Direct HTTP config/key access fails |
| CORS confusion | Explicit origin rules, no cookie-based login | Forbidden origin not granted browser access |

### 9.1 API key verification

V0 uses **one high-entropy API key per instance**. Bound key-header length, compare secure digests in constant time, e.g. SHA-256 plus `hash_equals()`, and support private rotation without editing application source. No roles or per-user accounts in V0.

### 9.2 HTTPS and reverse proxies

Accept protected data only when secure transport is proven. If a host proxy terminates TLS, trust forwarding metadata only when provider configuration guarantees its provenance. If this cannot be proven, enforce HTTPS at the hosting boundary or **reject productive deployment**. A redirect alone cannot undo plaintext key exposure.

### 9.3 Pull-request security review

Any change to routing, query grammar, SQL identifiers, authorization, exceptions or private paths needs **negative tests**. Every new capability remains disabled until explicitly configured. Never add credentials or confidential fixtures to CI artifacts.

## 10. Operations, failures and recovery

### 10.1 Health and readiness

`GET /api/v0/health` exposes minimal `ok` or `unavailable` without schema or host metadata. Public health is **not** a schema browser or necessarily a full DB readiness check. A more detailed operator-only readiness mechanism must be reviewed separately.

### 10.2 Sanitized technical logging

One UTC JSONL event per request containing random request ID, method, approved resource, HTTP status and error class. Log rotation/retention is operator configuration. **Never** log API key, database password, full bodies, authorization headers or bound SQL values.

### 10.3 Failure behavior

- Invalid config: fail closed, no data routes.
- Database unavailable: controlled 500/503, never a falsely successful empty collection.
- Logging unavailable: safe operational failure without dumping sensitive data.
- Timeouts: host and DB limits documented; no high-availability or asynchronous-work guarantee.
- Backup/recovery: protect DB and configuration separately, perform an actual test restore.

## 11. Verification and architecture fitness

| Level | Technical proof |
|---|---|
| Unit | Route normalization, config, policy, query parsing, key compare, errors |
| Integration | Real MariaDB CRUD/constraints/transactions/unchanged updates |
| Contract | HTTP/JSON methods/errors and `index.php?route=...` parity |
| Security | Unknown resources/fields, injection, key/TLS/CORS, secret-path tests |
| Deployment | Shared-host runtime and protected private files |
| Isolation | Two installations with different DBs/keys/logs |
| Documentation | Reproducible install, API/curl examples and recovery |

**Architecture fitness rules:** No PDO import in Controller; no HTTP details in Repository Port; no arbitrary SQL identifier from client input; no route to unconfigured resources; no fake second backend; positive/negative verification for V0-F01 through V0-F17.

### 11.1 V0 acceptance scenario mapping

| Acceptance scenario | Architecture test |
|---|---|
| AT-V0-01 | Hosting install, routing and private filesystem |
| AT-V0-02 | GET collections and single items |
| AT-V0-03 | Individual CRUD writes and transaction boundaries |
| AT-V0-04 | Filters, sorting and paging |
| AT-V0-05 | Resource/field/method allowlists |
| AT-V0-06 | API key and HTTPS/revocation |
| AT-V0-07 | SQL injection, malformed input and body limits |
| AT-V0-08 | Two independent deployments |
| AT-V0-09 | Health, logs and DB outages |
| AT-V0-10 | Documentation and automated tests |

## 12. Significant architecture decisions (ADR candidates)

| ADR | Proposed decision | Alternative / consequence |
|---|---|---|
| ADR-V0-001 | PHP 8.4/PDO without a mandatory framework | Better shared-host compatibility, internal HTTP responsibility |
| ADR-V0-002 | Explicit resource/field/method allowlist | Avoid unsafe schema auto-exposure |
| ADR-V0-003 | Root index.php, private files physically outside webroot | Do not rely on .htaccess alone |
| ADR-V0-004 | One technical API key per instance | Small V0, not suitable as end-user identity |
| ADR-V0-005 | Narrow repository port and one MariaDB adapter | Extend later without overengineering |
| ADR-V0-006 | Consistent PUT/PATCH semantics | Stable client contract |
| ADR-V0-007 | Bounded equality/AND query and limit/offset | No arbitrary SQL/RSQL |
| ADR-V0-008 | JSONL + request ID | No mandatory observability platform |
| ADR-V0-009 | HTTPS-only and verified proxy boundary | Secure key transport |

These are **proposals** for approval, not accepted ADR records. An actual ADR should document context, alternatives, consequences and tests.

## 13. Known risks and decisions before implementation

1. Can IONOS provide truly private config/key/log directories **outside public document root**?
2. Can TLS be enforced and verified even when a reverse proxy terminates HTTPS?
3. Are the final JSON config schema, API prefix and routing semantics approved?
4. Should both integer and UUID IDs be covered by the first reference schema?
5. How do strict PUT semantics handle nullable and DB-generated fields?
6. Are proposed 64 KiB body, max 100 page size and offset 10,000 suitable under real hosting load?
7. Do intended clients require a backend proxy to protect API keys?
8. Which PHP/MariaDB versions and SQL GRANT limitations exist on the actual hosting account?
9. V0 has no optimistic concurrency; consuming applications must not assume lost-update prevention.

## 14. Implementation slices and gates

| Slice | Work | Verification |
|---|---|---|
| A0 | Private deployment boundary and synthetic test DB | No public secrets |
| A1 | Bootstrap, router, JSON error contract, request ID | HTTP contract tests |
| A2 | ConfigLoader, ResourceRegistry, API key and HTTPS | Negative security tests |
| A3 | MariaDB repository and authorized GET | SQL/field allowlist |
| A4 | POST/PUT/PATCH/DELETE and local transactions | Constraint/rollback tests |
| A5 | Filters, sorting, paging, budgets | Query/injection tests |
| A6 | Health, logs, isolated installs, operations guide | AT-V0-01–AT-V0-10 passed |

No fabricated schedule or cost estimate is implied. V1 architecture becomes the next implementation baseline **only after V0 acceptance**.

## 15. Traceability: V0 requirements → architecture → tests

| V0 requirement | Architecture | Verification |
|---|---|---|
| V0-F01 | ``4–7 Router/Registry | T-V0-01/05 |
| V0-F02 | ``5–6, 8 GET | T-V0-01 |
| V0-F03 | ``5/8 POST | T-V0-02 |
| V0-F04 | ``5–6 PUT/PATCH | T-V0-03 |
| V0-F05 | ``5/8 DELETE | T-V0-04 |
| V0-F06 | `7 Config allowlist | T-V0-05 |
| V0-F07 | `6 Equality filters | T-V0-06 |
| V0-F08 | `6 Sorting/paging | T-V0-07 |
| V0-F09 | `6 ErrorMapper and responses | T-V0-09 |
| V0-F10 | ``2–3 instance isolation | T-V0-11 |
| V0-F11 | `9 API key | T-V0-08 |
| V0-F12 | ``6–9 validation/SQL | T-V0-06/09 |
| V0-F13 | ``3/9 HTTPS/CORS | T-V0-10 |
| V0-F14 | `10 Health | T-V0-10 |
| V0-F15 | `10 sanitized logging | T-V0-10 |
| V0-F16 | ``3/10/14 documentation | T-V0-12 |
| V0-F17 | `11 tests and fitness | T-V0-12 |

## 16. Change control and Definition of Done

Any change to the HTTP contract, resource allowlist, key model, repository interface, SQL building, transport/config boundaries or error format is an **architecture-relevant change** requiring review and appropriate contract/security tests. Change mandatory functional requirements in the requirements specification **before** changing their technical meaning here.

**Architecture V0 Definition of Done (future implementation):** all 17 V0 mandatory requirements and AT-V0-01–10 verified; private file/TLS boundaries proven; real shared-host deployment and recovery tested; security negatives passed. Until then, this document remains a **planned architecture**, not a working implementation.
