# SASD API Server – Technical Specification V0

> **Language:** **English (default)** | [Deutsch](technical-specification-v0.de.md)  
> **Status:** proposed technical design, subject to review before implementation. **Document version:** 0.1 · **Date:** November 1, 2026.  
> **Target:** V0.1.0 · **Requirements:** [V0 requirements](requirements-v0.md) · **Architecture:** [V0 architecture](architecture-v0.md)  
> **Future work:** [V1 technical specification](technical-specification-v1.md) · [V1 requirements](requirements-v1.md)  
> **Research:** [Reference-product catalog](reference-products-feature-catalog.md)

## 1. Purpose and applicability

This **technical specification** describes a concrete implementable solution for the requirements in the [V0 requirements specification](requirements-v0.md). The user-facing requirements remain authoritative. Technical choices proposed here become binding **only after review**. **No software implementation or acceptance test is claimed as a result of publishing this document.**

**Guiding idea:** a small, maintainable PHP REST server exposing CRUD against **explicitly approved** MariaDB/MySQL tables. Each installation uses separate configuration, secure defaults, and a clearly constrained extension path. The server is **not** an SQL interpreter and does **not** implement application-specific business workflows.

### 1.1 Priorities and release scope

- **Implementation target:** V0.1.0, all mandatory V0 requirements including basic security, tests and installation documentation.
- **Excluded:** SQLite/PostgreSQL, dynamic schema changes, user management, RBAC, generic joins, bulk operations, free SQL expressions, mandatory Redis/Docker, admin UI and integration services.
- **Development order:** secure initialization/routing/configuration → reading resources → individual writes → bounded list queries → comprehensive testing. Negative security and error tests accompany **every** slice.
- **Design principle:** no complicated abstraction without an actual V0 need; nevertheless preserve a small repository port to support future adapters, roles and OpenAPI.

## 2. Runtime and deployment

| Area | Proposed V0 solution |
|---|---|
| Runtime | PHP 8.4; no required framework; Composer optional for autoloading/development |
| Primary database | MariaDB 10.11 through PDO MySQL; compatible MySQL versions only after testing |
| Webserver | Apache/PHP or PHP-capable shared hosting, no mandatory root or permanent background process |
| Entry point | Small public `index.php` invoking private bootstrap and router |
| Routes | Prefer tested rewrite-based `/api/v0/...`; reliable `/index.php?route=/api/v0/...` fallback |
| Transport | HTTPS for all externally accessed data routes; TLS may terminate at verified trusted host/proxy |
| Configuration | JSON for non-secret resource settings; secrets via protected environment/private file **outside document root** |
| Observability | Minimal health, request ID, redacted technical logs without required new infrastructure |

### 2.1 Document root, protected paths and failure behavior

**The hosting account must provide physical separation between publicly delivered files and private settings, credentials, logs, and PHP dependencies.** Do not rely exclusively on `.htaccess`, which may be ignored or behave differently on the target host.

A practical example is a small public `index.php` loading the application and configuration from private paths outside the document root. If the provider cannot guarantee this separation, V0 is **not approved for production** even if the PHP routing works. Secret locations, SQL credentials and full filesystem paths must never appear in client error responses.

### 2.2 Routing fallback as mandatory hosting profile

The intended preferred URL is `/api/v0/books`, but on hosting with unreliable rewriting the same operation must be available through `/index.php?route=/api/v0/books`. Both use the **same route parser, authentication, authorization, validation and error mapping**.

Normalize and validate the logical route once. Reject traversal, null bytes, double encoding, ambiguous or duplicate query components and invalid segments. Never treat an arbitrary route as a filesystem path. A canonical external base URL is operator configuration, not an untrusted Host header.

## 3. Layer model and responsibilities

~~~text
             HTTPS / JSON / Client
                      |
                      v
             index.php / Router
                      |
                      v
            Security and AccessPolicy
                      |
                      v
               ResourceController
                      |
                      v
                  CrudService
                      |
                      v
        ResourceDefinition + Validator
                      |
                      v
           RecordRepository (Port)
                      |
                      v
          MariaDbPdoRepository (Adapter)
                      |
                      v
                    MariaDB

Cross-cutting: ConfigLoader, ErrorMapper, JsonResponder,
               SanitizedLogger, Request ID, Diagnostics
~~~

### 3.1 Proposed source-code organization

~~~text
index.php
composer.json
src/Bootstrap.php
src/Http/{Request,Response,Router}.php
src/Http/{JsonParser,JsonResponder}.php
src/Http/Controller/ResourceController.php
src/Application/CrudService.php
src/Application/Query/ListQuery.php
src/Domain/{ResourceDefinition,FieldDefinition}.php
src/Domain/{ValidationError,ApiError}.php
src/Contracts/RecordRepository.php
src/Security/{ApiKeyAuthenticator,AccessPolicy}.php
src/Config/{ConfigLoader,ConfigValidator}.php
src/Infrastructure/Database/{PdoFactory,MariaDbPdoRepository}.php
src/Infrastructure/Logging/SanitizedLogger.php
src/Infrastructure/Diagnostics/HealthService.php
config/example.resources.json
tests/{Unit,Integration,Security,Contract}/
docs/{installation,api,operations}.md
~~~

**This is a proposed target layout, not an existing file inventory.** The entry point contains no SQL or complex input validation. The Controller translates HTTP into typed use cases; Service applies common workflow and validation; Repository describes storage operations without PDO; MariaDB Adapter owns SQL and vendor-specific behavior. **Only one productive V0 adapter is implemented**, even though the port prepares for a second.

### 3.2 Fixed data-request processing order

1. Generate server-side request ID; check HTTP method, path and body-size limit.
2. Load and validate instance configuration; a failure **blocks data access**.
3. Verify HTTPS based only on trustworthy proxy/host evidence.
4. Authenticate the API key **before** revealing resources, schema or data.
5. Normalize and match the route and method.
6. Authorize the resource, operation and relevant fields using AccessPolicy.
7. Parse/validate JSON and query parameters, field types and budgets.
8. CrudService executes exactly the approved repository operation; writes are transactional.
9. Produce a defined HTTP/JSON result and a sanitized structured technical log event.

An unknown/unapproved resource should generally appear as **404** to avoid table enumeration. Missing/wrong authentication yields **401**. A recognized but forbidden operation yields **403** or **405** according to a precise method/authorization precedence **fixed by contract tests**, not guessed in each Controller.

## 4. V0 HTTP/JSON contract

### 4.1 Routes and methods

| Method | Preferred URL | Semantics | Success |
|---|---|---|---|
| GET | `/api/v0/{resource}` | Read collection | 200 |
| GET | `/api/v0/{resource}/{id}` | Read one record | 200 |
| POST | `/api/v0/{resource}` | Create exactly one record | 201 + Location |
| PUT | `/api/v0/{resource}/{id}` | Replace complete client-writable representation | 200 |
| PATCH | `/api/v0/{resource}/{id}` | Update only provided writable fields | 200 |
| DELETE | `/api/v0/{resource}/{id}` | Delete exactly one record | 204, no body |
| GET | `/api/v0/health` | Minimal non-sensitive status | 200/503 |

`health` is a reserved resource name and cannot be configured as a data table. These routes are **proposed contracts, not implemented endpoints**. V0 has no general `/sql`, `/tables` or `/admin` route.

### 4.2 Headers, media types and identifiers

- Authentication: `X-API-Key: <secret>` on every data route. No key in URL or query string.
- JSON write requests: `Content-Type: application/json`, UTF-8; wrong media type → 415, invalid JSON → 400.
- Response: `application/json; charset=utf-8` except 204 No Content; error responses use JSON when technically possible.
- Correlation: random server-generated request ID in `X-Request-ID` and optionally error body. Do not treat arbitrary untrusted client-provided IDs as safe log text.
- Record ID: a single percent-encoded primary-key path component; positive integer or configured canonical UUID. Composite keys and other types are excluded.
- POST Location: canonical approved single-resource route; avoid deriving absolute URLs from an untrusted Host/proxy header.

### 4.3 Data responses

~~~json
{
  "data": [
    {"id": 42, "title": "Example", "published": true}
  ],
  "meta": {
    "limit": 25,
    "offset": 0,
    "count": 1,
    "hasMore": false
  }
}
~~~

Single-record response: `{"data":{"id":42,"title":"Example","published":true}}`. POST/PUT/PATCH return the persisted single record in the same envelope. `count` is **the page element count**, not the total database count. `hasMore` may use a bounded read of `limit+1` rows. A costly full count is not required by V0.

Unconfigured columns, including password hashes and other technical columns, **must never** appear in JSON merely because they are physically present.

### 4.4 Filtering, sorting and pagination

- Equality: `filter[field]=value`. Multiple approved filters combine with **AND**. V0 names are approved external resource field names.
- Sorting: `sort=field` or `sort=-field`; exactly **one** approved sort field. Without explicit sorting, order by primary key for deterministic pages.
- `limit`: positive integer 1–100, default 25. `offset`: integer ≥0, default 0, first proposed maximum 10,000; configurable and subject to actual hosting checks.
- `count` and `hasMore` describe the visible snapshot of each individual request; **no cross-page snapshot guarantee** during concurrent writes.
- Only configured filterable/sortable columns may be referenced. Unknown query parameters/operators produce **400**, never an unrestricted fallback query.
- Text equality uses the SQL column's collation in V0; cross-database Unicode/case semantics are a V1 concern.

Example: `GET /api/v0/books?filter[published]=true&sort=-id&limit=20&offset=0`. Query fallback: `/index.php?route=/api/v0/books&filter[published]=true&sort=-id&limit=20`. Clients must perform appropriate URL encoding.

### 4.5 Input validation and PUT/PATCH semantics

**POST:** only configured creatable fields; missing required fields yield **422**; server-generated IDs cannot be overridden.

**PUT:** client supplies **all fields** marked `clientWritable` (including optional fields represented explicitly as values or `null` if nullable). This replaces the *client-managed writable representation*, leaving readonly/system fields unchanged. Requiring a complete representation avoids accidentally clearing omitted fields.

**PATCH:** changes only provided and permitted `clientWritable` fields. Omitted fields remain unchanged. Explicit `null` is legal only when `nullable=true`. An empty PATCH object produces **422**.

**DELETE:** deletes only the single route-addressed record; no bulk delete.

**Transaction:** each POST/PUT/PATCH/DELETE uses one local PDO begin/commit/rollback context. No transaction survives across HTTP requests.

The strict PUT completeness rule is a proposed technical interpretation of the requirements. If consuming applications need different PUT semantics, resolve it as an API-contract decision **before implementation**.

### 4.6 Standard errors and HTTP statuses

~~~json
{
  "error": {
    "code": "validation_failed",
    "message": "Invalid request body.",
    "details": [{"field": "title", "code": "required"}]
  },
  "requestId": "9fc085c3b2a043d3"
}
~~~

| Status | Example code | Cause |
|---|---|---|
| 400 | `invalid_request` | Invalid query syntax, JSON, path or parameter format |
| 401 | `unauthenticated` | Missing, invalid or revoked key |
| 403 | `forbidden` | Explicitly denied operation, without revealing sensitive details |
| 404 | `not_found` | Unknown/unapproved resource or missing record |
| 405 | `method_not_allowed` | Unsupported method on route; `Allow` only when safe |
| 409 | `conflict` | Unique/foreign-key constraint conflict |
| 413 | `payload_too_large` | Request size exceeds limit |
| 415 | `unsupported_media_type` | Invalid write Content-Type |
| 422 | `validation_failed` | Semantically invalid but syntactically well-formed data |
| 429 | `too_many_requests` | Optional host/proxy limit |
| 500 | `internal_error` | Unexpected internal exception without details |
| 503 | `service_unavailable` | Configuration/database not ready |

**Precedence:** basic security/authentication → route visibility and method authorization → syntax and validation → database. Final precedence must be a contract-tested decision. Raw PDO errors/SQLSTATEs stay internal. Use `Cache-Control: no-store` for authenticated data until a precise caching policy is designed.

## 5. Configuration model

### 5.1 Principles

Configuration belongs **to one instance** and is not remotely writable through the public API. ConfigLoader reads a non-secret JSON resource definition, validates it on each PHP bootstrap (no required persistent memory cache), and combines it with protected secrets. **Bad configuration fails closed**; there is no automatic registration of all database tables.

### 5.2 Example (illustrative contract, not a runnable file)

~~~json
{
  "schemaVersion": 1,
  "apiPrefix": "/api/v0",
  "instanceId": "library-test",
  "database": {
    "driver": "pdo_mysql",
    "hostEnv": "SASD_DB_HOST",
    "nameEnv": "SASD_DB_NAME",
    "userEnv": "SASD_DB_USER",
    "passwordEnv": "SASD_DB_PASSWORD"
  },
  "security": {"apiKeyEnv": "SASD_API_KEY", "requireHttps": true},
  "limits": {
    "defaultPageSize": 25, "maxPageSize": 100,
    "maxOffset": 10000, "maxBodyBytes": 65536
  },
  "resources": {
    "books": {
      "table": "books",
      "primaryKey": {"field": "id", "type": "integer"},
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {
          "type": "integer", "readable": true,
          "clientWritable": false, "filterable": true, "sortable": true
        },
        "title": {
          "type": "string", "maxLength": 200, "required": true,
          "nullable": false, "readable": true, "clientWritable": true,
          "filterable": true, "sortable": true
        },
        "published": {
          "type": "boolean", "required": true, "nullable": false,
          "readable": true, "clientWritable": true,
          "filterable": true, "sortable": false
        }
      }
    }
  }
}
~~~

The `hostEnv` etc. entries reference protected environment variables, **not their secret values**. If hosting lacks trusted secret environment facilities, a server-only readable file outside the document root may be used. Missing secrets must not fall back to default credentials.

### 5.3 Resource validation rules

- Public names match `[a-z][a-z0-9_-]*` and exclude reserved routes such as `health`.
- SQL table/column identifiers come only from operator-configured, validated allowlists and are quoted safely by the adapter.
- Primary key is one unique column, positive integer or explicitly allowed UUID. It cannot be modified by PUT/PATCH.
- A GET resource needs ≥1 readable field. Each write route needs defined writable fields and clear key behavior.
- Filterable and sortable fields must also be readable in V0; do **not** enable exceptions that could leak hidden data.
- Resource configs and secrets must not be downloadable over HTTP; deployment negative tests prove this.

## 6. Persistence and SQL execution

### 6.1 Repository port

The application uses a small storage-neutral conceptual interface:

~~~text
list(ResourceDefinition, ListQuery) -> PageResult
find(ResourceDefinition, RecordId) -> Record | null
insert(ResourceDefinition, RecordInput) -> Record
replace(ResourceDefinition, RecordId, RecordInput) -> Record
patch(ResourceDefinition, RecordId, RecordInput) -> Record
delete(ResourceDefinition, RecordId) -> void
~~~

Implement suitable PHP types/errors when coding. **These signatures do not assert existing classes.**

### 6.2 PDO adapter and safe query construction

1. Use PDO exceptions, disable emulated prepared statements where supported, and configure `utf8mb4`.
2. Table, column and ORDER BY identifiers come **only** from validated resource definitions, correctly quoted for MariaDB.
3. Bind every client-supplied value as a positional or named parameter. Strictly validate integer LIMIT/OFFSET.
4. Avoid unrestricted `SELECT *`. Query only approved readable fields; UPDATE only approved writable fields.
5. UPDATE/DELETE **must** contain the complete primary-key predicate. A missing WHERE clause is never permitted.
6. After an unchanged UPDATE, `rowCount()===0` does **not** by itself prove the ID is absent; distinguish these cases.
7. Individual writes use begin/commit/rollback; exceptions roll back. No transaction spans HTTP requests.
8. Do not create business primary/foreign keys or mutate application schema through generic HTTP endpoints.

### 6.3 Acceptance test database

Create a reproducible **synthetic** MariaDB fixture with at least `books(id,title,published)`, an optional `authors(id,name)` resource, appropriate unique/FK constraints and at least **101 rows** for paging. This is **not** a production library-management schema. Tests may reset dedicated test databases; they must **never** delete production records.

## 7. Security by default

### 7.1 API key handling

The trusted client supplies the key through `X-API-Key`. V0 should verify a protected expected secret with a **constant-time** comparison, for example fixed SHA-256 digests and PHP `hash_equals()`. Bound header length. Never include the key in URLs, cookies, logs, stack traces or error objects.

A key can be rotated through controlled private configuration without changing application code. V0 does not offer a user identity platform or a public key administration API.

**Untrusted clients:** keys embedded in distributed browser, desktop or mobile application code cannot be assumed secret. Production V0 use is limited to controlled technical clients or a trusted backend intermediary; public registration/multi-user systems need additional identity controls.

### 7.2 HTTPS, CORS and authorization

- Verify HTTPS before accessing data. Trust `X-Forwarded-Proto` only from an explicitly verified provider/proxy chain. If transport cannot be proved safe, **do not approve production deployment**.
- Default CORS policy grants **nothing**. Explicit allowed origins; never confuse preflight OPTIONS handling with data authorization. No arbitrary origins with credentials.
- Apply resource/method/field policy separately from SQL grants. Give the DB account least privilege as an additional layer.
- No HTTP DDL, arbitrary client filesystem paths or unrestricted SQL interpreter.
- Failed authentication must not disclose keys, database users or available table names.

### 7.3 Abuse and data-protection bounds

- Initial suggested maximum JSON request body: **64 KiB**. Check Content-Length **and** actual stream bytes; never trust the header alone.
- Maximum page: **100**; proposed offset ceiling: **10,000**; make values configurable and validate them in the real hosting profile.
- Bound JSON nesting, field count and query complexity; reject unknown fields/parameters.
- Store sanitized logs outside document root without raw authorization headers or full payloads.
- One technical API key is **not** end-user identity; V0 must not advertise user-specific audit or tenancy isolation.

## 8. Diagnostics, logging and failures

### 8.1 Health and readiness

`GET /api/v0/health` can respond without a key with no more than `{"status":"ok"}` or `{"status":"unavailable"}` (200/503). The public status must reveal **no database metadata** and generally checks basic bootstrap readiness. Detailed DB readiness is restricted to an authenticated operator path or internal diagnostic mode; the exact V0 operator route requires a final design review.

### 8.2 Technical logging

Write one structured JSON Line per request with UTC timestamp, random request ID, normalized method/resource, response status, coarse timing category and neutral error class. Exclude secrets/sensitive bodies **before** passing data to the logger, not only by later redaction.

Configure a private log path and document rotation/retention. A failed log write must not dump sensitive values to standard output; treat serious operational errors safely.

### 8.3 Error mapping

Map known validation, authorization, unique/FK and DB failures centrally. Unexpected exceptions are logged internally with a request ID, then returned as neutral **500** or **503**. Client responses must never include SQLSTATE, PDO stack traces, passwords or private paths.

## 9. Test specification

### 9.1 Levels

1. **Unit:** ResourceDefinition, field validator, request/query parser, authenticator, ErrorMapper.
2. **Integration:** dedicated MariaDB test database; all CRUD, transactions, constraints, filters and paging.
3. **HTTP contract:** exact statuses, headers and JSON; clean URL/query fallback parity.
4. **Security:** unknown resource/field, SQL injection through ID/filter/sort, wrong key, forbidden operation, oversized input, HTTP/TLS bypass.
5. **Deployment:** two instances with separate URLs/DBs/keys; restricted rewrite hosting and intentional DB outage.

### 9.2 Test data and execution quality

Use isolated test credentials and recreatable fixtures. Every group documents setup/teardown. The actual test invocation is documented in README **after** it exists; a dedicated CI MariaDB service is possible, but not currently assumed. PHPUnit is an option, **not a mandatory requirement of the functional specification**. Never reset production databases or capture real secrets in test logs.

### 9.3 Concrete technical tests

| ID | Expected result | Requirements |
|---|---|---|
| T-V0-01 | GET list/item, empty list, absent ID → 404 | F01, F02, F09 |
| T-V0-02 | POST inserts exactly one row; 201 and Location | F03, F09 |
| T-V0-03 | PUT requires full writable representation; PATCH only supplied fields | F04, F12 |
| T-V0-04 | DELETE exact ID; FK violation becomes 409 | F05, F09 |
| T-V0-05 | New table not in allowlist and hidden column inaccessible | F01, F06 |
| T-V0-06 | Equality/AND filter correct; SQL injection blocked | F07, F12 |
| T-V0-07 | Sort, limit, offset and hasMore correct; maxima enforced | F08, F09 |
| T-V0-08 | Missing/wrong/revoked key → 401; valid key works | F11, F13 |
| T-V0-09 | Invalid JSON/types/unknown fields safely rejected | F09, F12 |
| T-V0-10 | HTTPS/CORS, logs and health; no secret exposure | F13, F14, F15 |
| T-V0-11 | Distinct instances/keys/data are isolated | F10 |
| T-V0-12 | Documented install, fallback routing and automated test suite | F16, F17 |

## 10. V0 implementation sequence and milestones

| Stage | Deliverable | Gate |
|---|---|---|
| M0 — Repository foundation | Autoloading, folders, example config, basic tests | Clean local setup and protected private files |
| M1 — Infrastructure | index.php, bootstrap, router, errors, HTTPS, API key | Unauthorized clients cannot reach DB |
| M2 — Secure reads | Resource model, PDO, GET list and item | Authorized reads + denial tests |
| M3 — Writes | POST, PUT, PATCH, DELETE, validation, transactions | CRUD/constraint tests pass |
| M4 — List functions | Equality filters, sorting, bounded pagination | Safe queries and injection tests |
| M5 — Operations/acceptance | Host routing fallback, logs, health, second instance, documentation | AT-V0-01–AT-V0-10 verified |

Milestones describe a **logical dependency order**, not a claimed delivery schedule or effort estimate. Optimize only after a readable, layered, tested baseline exists.

## 11. Traceability to V0 requirements

| Requirement | Technical implementation | Tests |
|---|---|---|
| V0-F01 | ``3–5 resource registry and router | T-V0-01, 05 |
| V0-F02 | `4 GET list/item | T-V0-01 |
| V0-F03 | ``4/6 POST | T-V0-02 |
| V0-F04 | `4.5 PUT/PATCH | T-V0-03 |
| V0-F05 | ``4/6 DELETE | T-V0-04 |
| V0-F06 | `5 resource/method allowlist | T-V0-05 |
| V0-F07 | `4.4 bound equality filters | T-V0-06 |
| V0-F08 | `4.4 sorting/paging | T-V0-07 |
| V0-F09 | `4 status/JSON errors | T-V0-01–04/09 |
| V0-F10 | ``2/5/7 separate instance config | T-V0-11 |
| V0-F11 | `7.1 key authentication | T-V0-08 |
| V0-F12 | ``4–7 validation/SQL safety | T-V0-06/09 |
| V0-F13 | ``2/7 HTTPS/CORS | T-V0-10 |
| V0-F14 | `8.1 health/diagnostics | T-V0-10 |
| V0-F15 | `8.2–8.3 sanitized logging | T-V0-10 |
| V0-F16 | ``2/4/5/10 installation examples | T-V0-12 |
| V0-F17 | `9 automated tests and CI concepts | T-V0-12 |

## 12. Acceptance, open decisions and risks

**Acceptance:** all mandatory V0 requirements and **AT-V0-01 through AT-V0-10** require real, repeatable test evidence, including tested PHP/MariaDB versions, hosting mode, HTTP examples, actual logs and known limitations.

**Review before the first code slice:**
1. Can the actual IONOS account physically store config/logs/secrets **outside the public document root**?
2. Can HTTPS be reliably detected/enforced with TLS termination by the host/proxy?
3. How should PUT handle nullable, optional and DB-generated fields? The strict full-writable-representation contract is proposed.
4. Is a single technical API key sufficient for the consuming clients? Untrusted browser/desktop clients require a trusted intermediary.
5. Are both integer and UUID IDs necessary in the first acceptance fixture? The model may allow both but tests must cover approved types.

**Risks:** unreliable rewriting, inadequate secret storage, spoofable TLS headers, accidental table exposure, SQL collation/type differences and exposed client keys. Each requires a test or explicit approval decision before production.

## 13. Change management and document status

Version this specification with the functional requirements. Any observable HTTP API change requires contract tests and an explicit decision; changed mandatory user requirements must first update the requirements document. Future V1 functionality does **not** retroactively become a V0 acceptance condition.

**Status: technical proposal, not implemented, not tested, not formally accepted.**
