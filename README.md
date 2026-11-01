# SASD API Server

**A lightweight, configurable, open-source REST/JSON data API server for multiple independent applications.**

SASD API Server is a project by **SASD — Scientific and Software Development**. Its goal is to avoid repeatedly implementing the same database-access infrastructure in different applications. Instead, a reusable server core will expose **explicitly approved data resources** over a predictable, language-independent HTTP interface.

**One codebase. Multiple independent installations. Explicit configuration. Controlled access.**

The initial release is intentionally small: **PHP 8.4, MariaDB/MySQL, single-record CRUD, simple queries, API-key authentication, and ordinary PHP shared hosting**. The future V1 extends this foundation with SQLite, more sophisticated queries, role-based access, audit logging, and OpenAPI documentation.

> [!IMPORTANT]
> **Project status — November 1, 2026: planning and architecture.** This repository currently contains requirements, technical specifications, architecture, development plans, and operating procedures. **There is no working V0 or V1 application, released installation package, confirmed passing CI pipeline, or production deployment yet.** All API paths, JSON and configuration examples, source structures, and diagrams below describe the **planned design**. They are **not currently executable endpoints or existing code**.
>
> Do **not** consider this a production-ready API server based solely on its documentation. Implementation, tests, real shared-hosting verification, security review, and release approval are still required.

[**Roadmap**](ROADMAP.md) · [**Documentation index**](docs/README.md) · [**V0 requirements (English)**](docs/requirements-v0.en.md) · [**V1 requirements (English)**](docs/requirements-v1.en.md) · [**Contributing**](CONTRIBUTING.md)

---

## Contents

- [Motivation and goals](#motivation-and-goals)
- [What the server will do](#what-the-server-will-do)
- [Status and version strategy](#status-and-version-strategy)
- [V0 functional scope](#v0-functional-scope)
- [V1 planned expansion](#v1-planned-expansion)
- [Architecture and design](#architecture-and-design)
- [Planned REST API](#planned-rest-api)
- [Example API requests and responses](#example-api-requests-and-responses)
- [Configuration-driven resources](#configuration-driven-resources)
- [Security model](#security-model)
- [Persistence and data integrity](#persistence-and-data-integrity)
- [Deployment and hosting](#deployment-and-hosting)
- [Proposed project structure](#proposed-project-structure)
- [Testing and acceptance](#testing-and-acceptance)
- [Roadmap and milestones](#roadmap-and-milestones)
- [Documentation library](#documentation-library)
- [Reference products and non-goals](#reference-products-and-non-goals)
- [Contribution guidelines](#contribution-guidelines)
- [Frequently asked questions](#frequently-asked-questions)
- [License and project links](#license-and-project-links)

## Motivation and goals

Software applications frequently need similar persistence operations: retrieve records, create entries, change allowed fields, delete a record, filter lists, paginate large results, validate input, protect access, and translate errors into a client-friendly interface.

Reimplementing these functions independently in every project increases duplicated code, inconsistent API contracts, and the number of locations where security mistakes can occur.

**SASD API Server aims to move the shared data-access concerns into one maintainable service**, while leaving domain-specific functionality inside the consuming applications.

### Multiple deployments, not a mandatory central database

~~~mermaid
flowchart LR
    Client1["Client application A"] -->|"HTTPS / JSON"| API1["SASD API Server<br/>Instance A"]
    Client2["Client application B"] -->|"HTTPS / JSON"| API2["SASD API Server<br/>Instance B"]
    API1 --> SQL1[("MariaDB A")]
    API2 --> SQL2[("MariaDB B<br/>or SQLite in V1")]
    Shared["Same approved server source"] -.-> API1
    Shared -.-> API2
~~~

Each instance has its **own URL, database connection, resource definitions, API keys, permissions, and logs**. This is different from granting every application access to all data through one unrestricted shared endpoint.

### Intended users

| Audience | Expected value |
|---|---|
| SASD desktop and backend developers | Shared, predictable CRUD operations for data-centric applications |
| Applications written in .NET, Java, PHP, or other languages | Use HTTP/JSON instead of depending on a database-specific client implementation |
| Operators of small deployments | Configure each API instance without maintaining a different fork of the server |
| Open-source contributors | A small, modular codebase with explicit specifications, tests, and architecture decisions |

The generic server does not replace the business logic of a library system, health application, or other domain-specific software. For example, a library loan is more than several unrelated CRUD requests and must remain governed by its own application rules.

## What the server will do

The primary model is a **configured resource API**. Operators define which table represents a public resource, which columns can be used, and which HTTP methods are allowed.

Planned core principles:

1. **Resources are approved explicitly.** A database table that has not been configured remains inaccessible.
2. **Only permitted data is returned.** Internal columns and read-only fields cannot be freely exposed or modified.
3. **Uniform HTTP behavior.** Routes, JSON envelopes, error codes, and CRUD semantics are documented and tested.
4. **SQL remains internal.** Clients submit resource names and values, not raw SQL queries or database identifiers.
5. **Storage is separated from HTTP.** A repository interface keeps database-specific details out of controllers and services.
6. **Security is built into V0.** API-key access, HTTPS, strict validation, least-privilege database permissions, and safe error messages are mandatory.
7. **Deployments are independent.** One version of the program can serve multiple different applications using separate configurations.
8. **Development stays incremental.** V0 first; only then the larger V1 design.

A larger feature set is not automatically better. The goal is a **small and reliable foundation that is understandable, testable, and reusable**.

## Status and version strategy

| Stage | Planned content | Current status |
|---|---|---|
| **V0.1.0** | PHP/MariaDB REST CRUD API; allowlists, API key, filters, pagination, health, logging, independent installations | **Not implemented** |
| **V0.1.x** | Corrections, tests, security fixes, documentation updates | Future |
| **V1.0.0** | Stable V1 contract, SQLite adapter, roles, views/relations, OpenAPI, transactional audit and rate limiting | **Not implemented** |
| Later | Additional SQL/storage adapters and special integrations only after explicit scope review | Ideas only |

**Two kinds of versioning must not be confused:**

- `V0.1.0` and `V1.0.0` identify planned **software releases**.
- `/api/v0/` and `/api/v1/` are proposed **public HTTP contract namespaces**.
- Existing V0 clients must not silently start receiving incompatible V1 behavior.
- All published behavior requires contract tests and explicit documentation.

V0 includes **17 functional MUST requirements** and **10 acceptance scenarios**. V1 adds **22 mandatory requirements**, **five optional SHOULD requirements**, and **16 acceptance scenarios**. These are design requirements, not test results.

Further details: [ROADMAP.md](ROADMAP.md), [V0 requirements](docs/requirements-v0.en.md), [V1 requirements](docs/requirements-v1.en.md).

## V0 functional scope

The first release is intentionally designed around the shared-hosting environment and a single relational database adapter.

| Capability | V0 design |
|---|---|
| Resource discovery/exposure | Only configured resources are published; database introspection must never automatically expose tables |
| List | Read authorized records of a configured resource |
| Single read | Fetch a record through one configured primary key |
| Create | POST exactly one valid record |
| Replace | PUT the complete client-writable representation |
| Partial update | PATCH only supplied writable fields |
| Delete | DELETE one explicitly addressed record if allowed |
| Filtering | Safe equality filters combined with AND |
| Ordering | One approved ascending/descending sort field, deterministic fallback order |
| Pagination | Bounded `limit` and `offset` parameters |
| Validation | Required fields, types, nullability, lengths, permitted fields |
| API authentication | One strong installation-specific technical API key |
| Transport | HTTPS for protected production access |
| SQL security | Parameterized values, allowlisted table/field/sort names, scoped update/delete |
| Error handling | Consistent JSON error codes, HTTP status mapping, request identifier |
| Operations | Minimal health, sanitized technical logging, graceful database-failure handling |
| Deployment | Multiple isolated instances using the same program revision |
| Verification | Unit, integration, HTTP-contract, security, hosting and recovery tests |

The first database adapter is **MariaDB/MySQL via PDO**. There is no requirement to support PostgreSQL, SQLite, JSON files, arbitrary web APIs, or NoSQL in V0.

### Explicit V0 limitations

No automatic SQL schema modification, arbitrary SQL endpoint, multi-row bulk writes, joins, GraphQL, web-based administration, interactive user accounts, SSO, distributed transactions, data-import engine, or framework-style application hosting.

V0 is a **data API**, not an enterprise integration platform or a business-process engine. See [V0 requirements](docs/requirements-v0.en.md) for precise constraints and acceptance criteria.

## V1 planned expansion

After V0 passes acceptance, the server is intended to grow into a more capable but still carefully bounded data service.

| Area | Mandatory V1 addition |
|---|---|
| Stable API contracts | Versioned HTTP/JSON behavior and compatibility verification |
| Resource configuration | External field aliases decoupled from physical SQL columns |
| Storage | **Two** tested adapters: MariaDB/MySQL and SQLite |
| Queries | Comparison operators, IN, NULL checks, bounded AND/OR groups, simple text matching |
| Result control | Authorized field projection, bounded pagination, multi-field sorting |
| Relationships | Declared, read-only, one-level relationship expansion with access checks |
| Views | Configured SQL views exposed as read-only resources |
| Authentication | Multiple technical client keys with revocation/rotation |
| Authorization | Role-based resource, operation, field and relationship permissions |
| Rate limiting | Configurable abuse protection without mandatory Redis |
| Audit | Required write audit event committed atomically with a data change |
| API documentation | OpenAPI derived from authorized resources and contracts |
| Operations | Protected readiness, safe config migration, installation/restore procedures |
| Interoperability | curl, Java and C#/.NET examples |

**V1 SHOULD features** are intentionally separate and may be postponed after a documented decision: (1) named read-only SQL queries, (2) simple server-enforced record visibility, (3) optimistic locking, (4) controlled custom controllers, and (5) operational metrics.

**Important:** A second storage adapter is not a data-migration tool. Moving an existing application from MariaDB to SQLite requires an independent data-migration procedure and compatibility checks.

Read the [V1 technical specification](docs/pflichtenheft-v1.md) and [architecture](docs/architektur-v1.md) for detailed design decisions.

## Architecture and design

### High-level component view

~~~mermaid
flowchart TB
    C["Clients<br/>HTTPS + JSON"]
    subgraph API["SASD API Server — planned V0"]
        E["index.php / Bootstrap"]
        H["HTTP Router / Request / Response"]
        S["HTTPS / API Key / Resource Policy / Limits"]
        Controller["ResourceController"]
        Service["CrudService"]
        Registry["Resource Definitions + Validation"]
        Port["RecordRepository interface"]
        Adapter["MariaDbPdoRepository"]
        Log["ErrorMapper / Health / Sanitized Logs"]
        E --> H --> S --> Controller --> Service --> Port --> Adapter
        Service --> Registry
        S -.-> Log
        Controller -.-> Log
    end
    C --> E
    Adapter --> DB[("MariaDB")]
~~~

The planned dependency direction is **HTTP → Application → Domain/Contracts ← Infrastructure**. The composition root/bootstrap wires concrete dependencies together. Application code should not be tied directly to PDO or webserver globals.

### Responsibilities and boundaries

| Component | Owns | Must not |
|---|---|---|
| Front controller | PHP entry point and bootstrap | Contain SQL or business logic |
| HTTP router | Routes, methods, parsing, response handling | Turn arbitrary URL segments into database identifiers |
| Authentication/policy | HTTPS, API key, resource/method/field access | Grant universal access just because a key is valid |
| Resource controller | Map HTTP operations to application use cases | Depend directly on PDO |
| CRUD service | Coordinate validation, writes, errors and transactions | Construct SQL or read global PHP server variables |
| Resource registry | Public resource definitions, fields, types and allowlists | Auto-publish all tables |
| Repository contract | Describe database-neutral record operations | Contain HTTP statuses or PDO-specific types |
| MariaDB adapter | Prepared SQL, transaction control, database error mapping | Handle client authentication itself |
| Diagnostics/logging | Sanitized errors, request IDs, minimal health | Expose secrets, SQL details or internal paths |

### Proposed request lifecycle

1. Accept the HTTP request and generate a request/correlation ID.
2. Verify applicable HTTPS/proxy rules, size limits and route syntax.
3. Authenticate the technical client using its installation-specific API key.
4. Check the requested resource, method, fields and query options against approved configuration.
5. Parse and validate JSON, primary keys, filters, pagination and allowed types.
6. Execute one permitted operation through the repository interface and the active database adapter.
7. For writes, commit or roll back the local database transaction.
8. Return a stable JSON/HTTP response; write only sanitized technical logs.

V1 extends this flow with per-client roles, richer query plans, protected OpenAPI, rate limits, and transactional audit.

## Planned REST API

> The endpoints below are **design examples**. They are not currently available from the repository.

Assume an explicitly configured `books` resource. The planned V0 contract looks like this:

| HTTP method | Resource URL | Meaning | Successful status |
|---|---|---|---|
| GET | `/api/v0/books` | Read a collection | 200 |
| GET | `/api/v0/books/42` | Read one record | 200 |
| POST | `/api/v0/books` | Create one record | 201 + Location |
| PUT | `/api/v0/books/42` | Replace the client-writable representation | 200 |
| PATCH | `/api/v0/books/42` | Change supplied writable fields | 200 |
| DELETE | `/api/v0/books/42` | Remove one permitted record | 204 without response body |
| GET | `/api/v0/health` | Minimal, non-sensitive service status | 200/503 |

The name `books` is not a built-in resource. It only exists if a future operator has configured it and the backing database table is prepared.

### Filters, sorting and pagination

| Parameter | Example | V0 behavior |
|---|---|---|
| Filter | `filter[published]=true` | Equality on a configured filterable field |
| Several filters | `filter[published]=true&filter[title]=Example` | Conditions combined with AND |
| Sorting | `sort=title` or `sort=-title` | Ascending or descending approved field |
| Page size | `limit=25` | Configurable maximum number of results |
| Offset | `offset=0` | Start of returned page |

**Proposed defaults:** 25 rows per page, up to 100; maximum offset initially planned as 10,000. Actual values remain subject to security and performance testing on the target hosting environment. Without an explicit sort, the primary key provides a deterministic order.

Invalid or unapproved filters must be **rejected**, not silently ignored in a way that returns all data.

### PUT and PATCH are deliberately different

- `PUT` requires a **complete representation of the fields declared client-writable**; system-owned and read-only fields are not overwritten.
- `PATCH` only changes the explicitly provided writable fields. Omitted fields keep their existing values.
- An explicit JSON `null` is legal only for nullable fields.
- The requested record must already exist for an update. It must not be silently created by PUT/PATCH.
- All individual write requests use a database transaction. No transaction is held across several HTTP requests.

The V0 design does **not** promise prevention of lost updates caused by concurrent clients. V1 plans optional optimistic conflict detection.

## Example API requests and responses

The following code blocks illustrate the **proposed future protocol**, not currently runnable commands against this repository. The example address `api.example.test` is not a real SASD deployment. The variable `$SASD_API_KEY` must contain a valid **test** key in a trusted client environment; never commit or print actual secrets.

### Read a collection

~~~bash
curl --fail-with-body \
  --header "Accept: application/json" \
  --header "X-API-Key: $SASD_API_KEY" \
  "https://api.example.test/api/v0/books?limit=25&offset=0"
~~~

### Read an individual record

~~~bash
curl --fail-with-body \
  --header "X-API-Key: $SASD_API_KEY" \
  "https://api.example.test/api/v0/books/42"
~~~

### Create a record

~~~bash
curl --fail-with-body \
  --request POST \
  --header "Content-Type: application/json" \
  --header "X-API-Key: $SASD_API_KEY" \
  --data '{"title":"Example book","published":true}' \
  "https://api.example.test/api/v0/books"
~~~

### Update selected fields

~~~bash
curl --fail-with-body \
  --request PATCH \
  --header "Content-Type: application/json" \
  --header "X-API-Key: $SASD_API_KEY" \
  --data '{"published":false}' \
  "https://api.example.test/api/v0/books/42"
~~~

### Fallback routing on restricted hosting

If URL rewriting is unreliable, the planned API must allow the equivalent logical route through the root front controller:

~~~bash
curl --fail-with-body \
  --header "X-API-Key: $SASD_API_KEY" \
  "https://api.example.test/index.php?route=/api/v0/books&limit=25"
~~~

Both routing modes must use **the same authentication, resource policies, validation and application service**. A client-provided route must never become an arbitrary local filesystem path.

### Proposed successful list response

~~~json
{
  "data": [
    {
      "id": 42,
      "title": "Example book",
      "published": true
    }
  ],
  "meta": {
    "limit": 25,
    "offset": 0,
    "count": 1,
    "hasMore": false
  }
}
~~~

`count` is the **number of returned records in this page**, not the total database result count. The `hasMore` flag can be obtained by fetching at most one additional row beyond the page limit.

### Proposed single-record response

~~~json
{
  "data": {
    "id": 42,
    "title": "Example book",
    "published": true
  }
}
~~~

### Proposed error response

~~~json
{
  "error": {
    "code": "validation_failed",
    "message": "Invalid request body.",
    "details": [
      {
        "field": "title",
        "code": "required"
      }
    ]
  },
  "requestId": "9fc085c3b2a043d3"
}
~~~

The request ID allows support and operations teams to correlate a client-visible error with protected technical logs, **without** including database SQL, passwords, stack traces or private filesystem paths in the API response.

| HTTP status | Intended category |
|---|---|
| 200 / 201 / 204 | Successful read/update, creation, deletion |
| 400 | Malformed request, JSON, route or query |
| 401 | Missing/invalid/revoked API key |
| 403 / 405 | Forbidden operation or unsupported method, subject to finalized policy order |
| 404 | Unknown or inaccessible resource, or missing record |
| 409 | Uniqueness or foreign-key conflict |
| 413 | Request body limit exceeded |
| 415 | Unsupported content type |
| 422 | Semantically invalid resource values |
| 429 | Rate limit exceeded (primarily V1/infrastructure) |
| 500 / 503 | Internal failure or unavailable service |

## Configuration-driven resources

A resource definition controls which **public API name** maps to a database table and the list of visible or mutable fields. **The configuration is the authorization allowlist; the database schema is not.**

### Illustrative V0 configuration

The following JSON reflects the **proposed V0 format** from the technical specification. It is **not a live or validated file in this repository**.

~~~json
{
  "schemaVersion": 1,
  "apiPrefix": "/api/v0",
  "instanceId": "books-test",
  "database": {
    "driver": "pdo_mysql",
    "hostEnv": "SASD_DB_HOST",
    "nameEnv": "SASD_DB_NAME",
    "userEnv": "SASD_DB_USER",
    "passwordEnv": "SASD_DB_PASSWORD"
  },
  "security": {
    "apiKeyEnv": "SASD_API_KEY",
    "requireHttps": true
  },
  "limits": {
    "defaultPageSize": 25,
    "maxPageSize": 100,
    "maxOffset": 10000,
    "maxBodyBytes": 65536
  },
  "resources": {
    "books": {
      "table": "books",
      "primaryKey": { "field": "id", "type": "integer" },
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {
          "type": "integer",
          "readable": true,
          "clientWritable": false,
          "filterable": true,
          "sortable": true
        },
        "title": {
          "type": "string",
          "maxLength": 200,
          "required": true,
          "nullable": false,
          "readable": true,
          "clientWritable": true,
          "filterable": true,
          "sortable": true
        },
        "published": {
          "type": "boolean",
          "required": true,
          "nullable": false,
          "readable": true,
          "clientWritable": true,
          "filterable": true,
          "sortable": false
        }
      }
    }
  }
}
~~~

The referenced environment variables do **not** contain their values inside the resource JSON. Actual database credentials and keys require a separately protected secret source outside the public document root. The operator must create and secure the underlying `books` table independently; the API must not expose database schema creation through public HTTP routes.

### Expected configuration invariants

- An unknown resource is **not** mapped to a table automatically.
- Unknown or protected fields cannot be read, sorted, filtered or updated without explicit permission.
- A resource may permit GET but reject writes.
- Read-only primary keys may appear in responses but cannot be changed through PUT/PATCH.
- Invalid configuration causes a **safe failure**, not fallback to universal access.
- Each installation has its own database name, account, key and resource definitions.
- Configuration changes and secrets must not be modifiable through anonymous/public API endpoints.

V1 adds public field aliases, configured views/relationships, roles, and schema-versioned migrations. It does **not** allow arbitrary client-selected database connection strings.

## Security model

**The server sits in front of application data. Unauthorized access must be prevented by architecture and implementation, not by convention alone.**

| Threat or mistake | Required protection |
|---|---|
| Accidental publication of private tables | Explicit ResourceRegistry allowlist and deny-by-default |
| Unauthorized column access | Separate readable/writable/filterable/sortable field permissions |
| SQL injection through IDs/filters/sorts | Bound SQL values, trusted identifiers from validated server definitions |
| Credential leakage | No keys/passwords in Git, URLs, logs, errors or public config files |
| Insecure transport | HTTPS for protected endpoints and trusted reverse-proxy/TLS determination |
| Oversized/expensive requests | Body, page, query complexity and result limits |
| Sensitive error disclosure | Central error mapping without SQL statements or stack traces |
| Cross-instance access | Separate key, configuration, DB account, data and logs per installation |
| Browser-origin confusion | CORS controls browser origins but **is not authentication** |
| Broken deployment barriers | Private files physically outside the document root |

### API keys and end users

V0 plans one strong, installation-specific key passed via the `X-API-Key` header. It authenticates a **technical client/installation**, not an individual end user.

**Do not place a supposedly secret API key in publicly distributed JavaScript, an untrusted mobile application, or a downloadable desktop binary and assume it stays hidden.** For such clients, a trusted backend or a separately specified user-authentication system is required. V1 adds multiple key identities and role-based permissions but does not automatically become a full identity provider.

### HTTPS, proxy headers and routing

The intended hosting setup may terminate TLS at a proxy. The server must trust only provider-verified information about the original connection, **not arbitrary client-controlled** `X-Forwarded-Proto` or `Forwarded` headers. Redirecting an already transmitted plaintext credential is insufficient protection.

URI parsing must reject malicious or ambiguous paths, traversal, double encoding and invalid query values. The query-routing fallback must not bypass authentication or policies.

### Private storage is a production release gate

> Configuration, database credentials, API keys, protected logs, backups, and later SQLite files **must be physically outside the publicly accessible document root**. An uncertain `.htaccess` restriction is not a sufficient security barrier. If the hosting account cannot guarantee this isolation, the deployment is **not approved for production**.

### Responsible disclosure

Do not publish exploit details or confidential records in GitHub issues. Use the private reporting process in [SECURITY.md](SECURITY.md), where available. No response-time or security-audit guarantee is implied by the existence of a security policy.

## Persistence and data integrity

### V0: MariaDB/MySQL through PDO

The repository abstraction separates application services from the first SQL adapter. The adapter must:

1. Bind client values using prepared statements.
2. Select table and column identifiers only from prevalidated operator configuration.
3. Explicitly project approved fields; never use unconstrained `SELECT *` to expose sensitive database columns.
4. Scope UPDATE/DELETE to a verified single primary key.
5. Respect database constraints and transaction boundaries.
6. Map duplicate-key, foreign-key, missing-record and database-failure cases into neutral API errors.
7. Correctly distinguish an unchanged update from a nonexistent record; `rowCount() = 0` alone is not enough.
8. Use a least-privileged database account appropriate to the installation.

V0 does **not** manage application SQL-schema migrations through HTTP and does not promise a universal backup engine. Database backup, schema alignment and restore are operator responsibilities.

### V1: SQLite and a shared adapter contract

SQLite adds a second mandatory implementation behind the same record/query contract. The proposed adapter uses `pdo_sqlite`, validates private file paths, enforces foreign keys for each connection, and handles busy/locking states in a bounded manner. WAL/journal settings require actual deployment tests; they cannot be assumed safe for every filesystem.

V1 additionally requires **write-audit and data changes in one local transaction**. If the audit insert fails, the corresponding data write must roll back. This does not claim distributed transaction support or regulatory-grade, tamper-proof audit storage.

### Data format compatibility

V1 must specify common external representations for booleans, nullable fields, UUIDs, exact decimals and UTC timestamps. MariaDB and SQLite have different native types and collation behavior; identical SQL snippets are not enough to guarantee identical client-visible semantics. A shared contract test suite is therefore a release requirement.

## Deployment and hosting

### Target environment

| Component | Planned baseline |
|---|---|
| Runtime | PHP **8.4** |
| Database | MariaDB **10.11** / compatible MySQL after verification |
| Driver | PDO MySQL |
| Webserver | Conventional PHP shared hosting; intended IONOS deployment |
| Public entry | Root `index.php`, without requiring a public subdirectory |
| URL handling | Clean REST paths if available; query-routing fallback if rewrite is restricted |
| Transport | HTTPS, with verifiable TLS/proxy trust |
| Private configuration | Environment variables or private files outside the document root |
| Additional processes | No mandatory Docker, Redis, root account, queue, cron or persistent worker |
| Future V1 | SQLite via PDO SQLite, if filesystem/locking assumptions are met |

### Proposed deployment layout — **not an existing filesystem tree**

~~~text
PRIVATE_ROOT/
  releases/<release-id>/
    src/
    vendor/
  instances/<instance-id>/
    config/
    secrets/
    logs/
    backups/
    data/                  # V1 SQLite only

DOCUMENT_ROOT/
  index.php               # public front controller
~~~

If the hosting platform cannot safely separate public and private paths, the installation is not production-ready. A root `index.php` should be small and load its private bootstrap code via a controlled, trusted location.

### Planned installation workflow — not yet executable

1. Verify the real hosting environment: PHP, PDO driver, TLS enforcement and private directories.
2. Create an independent database and a minimally privileged SQL account.
3. Install an approved server release with its dependencies.
4. Place resource configuration and secrets in **protected** operator-managed storage.
5. Verify the configuration and test the routing fallback.
6. Perform positive CRUD tests using nonproduction data.
7. Perform negative tests for anonymous access, forbidden fields and direct HTTP access to private files.
8. Confirm that a second instance cannot use the first instance's credentials or read its data.
9. Perform a **backup followed by a restore on a separate test installation**.
10. Record the release commit, tested versions, actual results and deployment approval.

**There is no runnable installer or verified `composer install` / `composer test` workflow yet.** Those tools and commands must be implemented and verified during development. The full [operations and recovery runbook](docs/betrieb-und-deployment.md) describes the proposed process.

## Proposed project structure

The intended V0 PHP layout keeps HTTP, application services, domain definitions, contracts and infrastructure separate. The following is a **design target**, not a claim that these files currently exist:

~~~text
index.php
composer.json

src/
  Bootstrap.php
  Http/
    Request.php
    Response.php
    Router.php
    JsonParser.php
    JsonResponder.php
    Controller/
      ResourceController.php
  Application/
    CrudService.php
    ListQuery.php
  Domain/
    ResourceDefinition.php
    FieldDefinition.php
    ApiError.php
  Contracts/
    RecordRepository.php
  Security/
    ApiKeyAuthenticator.php
    AccessPolicy.php
  Config/
    ConfigLoader.php
    ConfigValidator.php
  Infrastructure/
    Database/
      PdoFactory.php
      MariaDbPdoRepository.php
    Logging/
      SanitizedLogger.php
    Diagnostics/
      HealthService.php

config/
  example.resources.json

tests/
  Unit/
  Integration/
  Contract/
  Security/
  Deployment/
~~~

This source-tree organization is **distinct** from the runtime directory structure. Code and configuration that are safe to keep in source control are not necessarily safe to serve directly over HTTP. The deployment must protect private files even when the provider's document root layout is restrictive.

V1 may add a SQLite adapter, query AST/compiler, role/key store, audit writer, relationships and OpenAPI generator. Do **not** implement those prematurely simply because their class names appear in a design document.

## Testing and acceptance

Functional success is not enough for a server protecting persistent data. **Negative security tests and real hosting checks are required**, not optional polish.

### Test coverage model

| Test level | Evidence |
|---|---|
| Static/Unit | PHP syntax, configuration schema, resource validation, router, key handling |
| MariaDB integration | CRUD, constraints, transactions, rollback, error mapping |
| HTTP contract | Exact routes, methods, headers, JSON envelopes, errors, fallback routing |
| Security | Unknown tables/fields, unauthenticated requests, SQL injection, oversized input, credential leakage |
| Deployment | Protected private files, provider-specific HTTPS and routing, correct PDO setup |
| Instance isolation | Two deployments from one code revision, but separate URLs/DBs/keys/logs |
| Recovery | Verified database backup and **restore** into a test environment |
| V1 adapter parity | Same supported HTTP/JSON contract on MariaDB and SQLite |
| V1 security and audit | RBAC on filters/views/relations/OpenAPI; audit rollback; rate-limiter fail-closed |
| Documentation | Example clients, configuration, and API contract match actual implementation |

### Release gates

**V0.1.0:** `V0-F01`–`V0-F17` and `AT-V0-01`–`AT-V0-10`, with documented results.  
**V1.0.0:** Accepted V0 baseline plus 22 V1 MUST requirements and `AT-V1-01`–`AT-V1-16`; both database adapters must pass common contract tests.

The five V1 SHOULD items may be postponed only through an explicit recorded scope decision.

**Test evidence must be real:** record the commit, environment, exact commands, result, known issues, and release authorization. A README, a passing syntax check, or an architecture drawing is not proof of successful acceptance.

See [test strategy](docs/teststrategie.md) and [release/acceptance checklists](docs/release-und-abnahme.md).

## Roadmap and milestones

Development follows small, documented increments rather than speculative timelines:

| Milestone | Work package | Gate |
|---|---|---|
| **V0-G0** | Review real hosting, secrets, private paths and HTTPS | Security-critical hosting assumptions verified |
| **V0-M0** | Establish project skeleton and test environment | Repeatable local setup |
| **V0-M1** | HTTP core, routing, JSON errors, key/transport protection | Unauthorized requests cannot reach data |
| **V0-M2** | MariaDB repository and secure GET list/record | Allowlist and SQL-negative tests pass |
| **V0-M3** | POST, PUT, PATCH, DELETE with validation and transactions | Integrity and rollback tests pass |
| **V0-M4** | Safe equality filters, sorting and pagination | Query/limit/injection tests pass |
| **V0-M5** | Health/logging, two independent installs and operator documentation | Operational isolation verified |
| **V0-M6** | Security review, hosting and complete V0 acceptance | All V0 mandatory gates passed |
| **V1-P0–P8** | Stable V1 API, SQLite, query expansion, RBAC, audit, OpenAPI and recovery | All V1 mandatory gates and both adapters verified |

The current **next planned milestone** is **V0-G0: hosting/security architecture review**, followed by the initial repository/bootstrap slice.

There is **no committed completion date**. Only changes evidenced by source code, tests, review and release decisions can be marked completed. For the full sequence, see [ROADMAP.md](ROADMAP.md) and [V0 development plan](docs/entwicklungsplan-v0.md).

## Documentation library

The planning work intentionally keeps the user requirements, implementation specification, architecture, and test/release evidence separate.

### Functional specifications and architecture

| Document family | V0 | V1 |
|---|---|---|
| Requirements (English) | [Requirements V0](docs/requirements-v0.en.md) | [Requirements V1](docs/requirements-v1.en.md) |
| Requirements (German) | [Lastenheft V0](docs/lastenheft-v0.md) | [Lastenheft V1](docs/lastenheft-v1.md) |
| Technical specification (German) | [Pflichtenheft V0](docs/pflichtenheft-v0.md) | [Pflichtenheft V1](docs/pflichtenheft-v1.md) |
| Software architecture (German) | [Architektur V0](docs/architektur-v0.md) | [Architektur V1](docs/architektur-v1.md) |

The requirements specify **what** is needed. The technical specification details **how** the solution should satisfy it. The architecture documents explain the **components, boundaries, trade-offs and rationale**.

### Project, quality and operations documentation

| File | Purpose |
|---|---|
| [Documentation index](docs/README.md) | Reading order and document navigation |
| [Roadmap](ROADMAP.md) | Feature/milestone scope, priorities and release gates |
| [V0 development plan](docs/entwicklungsplan-v0.md) | Small, testable implementation slices |
| [Test strategy](docs/teststrategie.md) | Unit, integration, contract, security, deployment and recovery checks |
| [Deployment and recovery](docs/betrieb-und-deployment.md) | Private-file boundaries, configuration, hosting, backups and incidents |
| [Release and acceptance](docs/release-und-abnahme.md) | Checklists, evidence and formal acceptance process |
| [Risk register](docs/risikoregister.md) | Unresolved safety and operational risks |
| [ADR index](docs/adr/README.md) · [ADR template](docs/adr/0000-template.md) | Architecture decision recording |
| [AGENTS.md](AGENTS.md) | Instructions for coding agents |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Contribution and engineering guidelines |
| [SECURITY.md](SECURITY.md) | Private vulnerability reporting and security policy |
| [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) | Community expectations |
| [CHANGELOG.md](CHANGELOG.md) | Unreleased work and future genuine release notes |

The requirements and feature catalog are available in **English and German**. Most technical and operational documents are currently maintained in **German**. Their draft status is preserved; translating text does not constitute product approval.

## Reference products and non-goals

SASD API Server was informed by the study of several existing API platforms:

| Reference | Relevant inspiration | What we are **not** copying |
|---|---|---|
| [PHP-CRUD-API](https://github.com/mevdschee/php-crud-api) | Lightweight PHP CRUD, filtering, middleware, configuration | Automatic, unrestricted exposure of database tables |
| [DreamFactory](https://github.com/dreamfactorysoftware/dreamfactory) | Configurable services, policy, documentation and administration concepts | The entire integration suite, all editions, or a no-code management product |
| [DB2Rest](https://github.com/kdhrubo/db2rest) | Relational API queries, SQL dialect abstractions, observability | Unbounded RSQL, arbitrary raw SQL, bulk features or every supported backend |

The reference products are **sources of design ideas**, not a 157-item delivery checklist. Their licensing, deployment requirements and free/commercial feature boundaries must be verified independently before reuse.

The complete research is in the [English reference catalog](docs/reference-products-feature-catalog.en.md) and [German catalog](docs/referenzprodukte-funktionskatalog.md).

### Explicit non-goals

The project is not intended to be a substitute for **WildFly/JBoss or WebLogic**, a SQL database administrator, a full identity provider, a universal workflow engine, a message broker, a SOAP/S3/email/FTP integration suite, or an arbitrary web-exposed SQL interpreter. A graphical admin dashboard, broad multi-tenancy, more database backends and other integrations require separate use cases and scope approval.

## Contribution guidelines

We welcome constructive documentation reviews, architecture discussion, reproducible test proposals, and later tightly scoped code contributions.

The engineering approach is:

1. Identify the requirement and target milestone (for example `V0-F06` and `V0-M2`).
2. Define the smallest useful change and what is **out of scope**.
3. Respect the separation between HTTP, services, resource definitions, repository ports and adapters.
4. Write clear, appropriately detailed comments explaining important design and security decisions.
5. Include positive and **negative** tests; use synthetic fixtures, not production datasets.
6. Keep credentials out of Git, issue reports, CI output and documentation examples.
7. Update the API/configuration documentation when a contract changes.
8. Record significant alternatives and consequences in an [architecture decision record](docs/adr/README.md).
9. Report which checks were **actually executed**, and which were not.
10. Avoid premature optimizations and feature expansion unrelated to the chosen release.

Read [CONTRIBUTING.md](CONTRIBUTING.md) and [AGENTS.md](AGENTS.md) before starting implementation work. The repository also provides templates for [bug reports](.github/ISSUE_TEMPLATE/bug_report.md), [feature requests](.github/ISSUE_TEMPLATE/feature_request.md), and [pull requests](.github/PULL_REQUEST_TEMPLATE.md).

Use [SECURITY.md](SECURITY.md) for private security reports, not public bug tickets.

## Frequently asked questions

### Can I clone the repository and start the API server now?

**No.** The repository currently contains designs and project documentation. A working `index.php` entrypoint, `composer.json`, code, automated tests, and a tested installation package have not yet been created. The sample commands above document intended future behavior.

### Why PHP rather than .NET or Java?

The first target environment is ordinary PHP shared hosting, including an IONOS-oriented deployment. Persistent Java/.NET processes and Docker cannot be assumed available. HTTP/JSON keeps client access independent from the server language.

### Will I need a different copy of the source for each application?

**No code fork should be required.** Independent installations will share the same source revision but have separate URLs, databases, configuration, credentials and operational files.

### Will the API publish every table in my database?

**No.** Public resources, operations and fields must be explicitly approved. A table's existence does not itself create an API endpoint.

### Can it use local JSON storage or a remote REST backend?

Not in V0. The initial implementation is MariaDB/MySQL. V1 adds SQLite. JSON-file or remote-API adapters need separate locking, consistency, recovery and security specifications.

### Does generic CRUD replace application business logic?

No. The server handles generic authorized record operations. Workflows such as borrowing and returning library items or domain-specific medical rules remain the responsibility of the application.

### Why not add a web administration interface now?

A graphical control panel requires new UI, authentication, operational and security designs. The current scope prioritizes a **small secure API core**. A dashboard could be evaluated as a later separate feature.

### Can a browser or public desktop client store the API key safely?

Not reliably as a hidden secret. A publicly distributed client should use a trusted backend or another properly designed user-authentication solution. CORS does not solve this.

### When will V0 or V1 be released?

No release date has been promised. V0 must first pass all required functional, security, hosting and recovery gates; V1 follows only after the V0 foundation is accepted.

## License and project links

The project is open source under the repository's **[MIT License](LICENSE)**. Contributions and external dependencies must respect their own licensing and attribution requirements.

- **Project:** SASD API Server
- **Organization:** SASD — Scientific and Software Development
- **GitHub repository:** [Robin-Goerlach/SASD-API-Server](https://github.com/Robin-Goerlach/SASD-API-Server)
- **Planning and milestones:** [ROADMAP.md](ROADMAP.md)
- **Documentation index:** [docs/README.md](docs/README.md)
- **Project history:** [CHANGELOG.md](CHANGELOG.md)
- **Security:** [SECURITY.md](SECURITY.md)
- **Community standards:** [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

---

### Next step

**V0-G0 — verify the actual hosting and security boundaries:** private files outside the document root, trustworthy HTTPS enforcement, PDO/MariaDB access, and a working routing fallback. Then create the smallest documented V0 bootstrap and proceed through the individual acceptance-gated milestones.

**Our goal is not the largest API platform. It is a small, reliable API server we can understand, test, operate, and safely reuse.**
