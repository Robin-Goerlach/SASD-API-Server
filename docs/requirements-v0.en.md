# SASD-API-Server — Requirements Specification V0

> **Status:** Draft for functional review · **Document version:** 0.1 · **Created:** 2026-11-01  
> **Repository:** [Robin-Goerlach/SASD-API-Server](https://github.com/Robin-Goerlach/SASD-API-Server)  
> **German source:** [Lastenheft V0](./lastenheft-v0.md) · **Next release:** [Requirements Specification V1](./requirements-v1.en.md) · **Research:** [Reference Products (DE)](./referenzprodukte-funktionskatalog.md) · [Reference Products (EN)](./reference-products-feature-catalog.en.md)

## 1. Subject and purpose of this document

This **requirements specification (German: Lastenheft)** states **what** the initial SASD API Server is expected to accomplish and how the customer side can assess its fitness for purpose. It defines business goals, boundaries, quality requirements, and acceptance criteria. The selection of concrete classes, libraries, and implementation approaches belongs in a subsequent **technical implementation specification (German: Pflichtenheft)**. Environment constraints that directly affect deployment are stated explicitly.

**V0 is intended as a small, safely operable first development stage, not a feature-rich API construction kit.** V0.1.0 is the initial release target in the V0 series. V1 is described separately as the intended product expansion; it is not a prerequisite for V0 and is not automatically approved for implementation.

### 1.1 Background

Several SASD applications need essentially the same persistence functions: creating, finding, reading, updating, and deleting records. Building this CRUD layer independently in each project wastes effort and produces inconsistent interfaces. The proposed server should be reusable as a separately deployed service. Different SASD applications must be able to use **separate installations of the same code**, each with its own URL, configuration, credentials, resources, and database connection. Individual applications remain responsible for their own domain-specific workflows.

### 1.2 Objectives

1. Provide a small, consistent REST/JSON interface for explicitly approved MariaDB/MySQL resources.
2. Deploy new independent instances primarily by configuration rather than by copying and modifying PHP source code.
3. Define ordinary data operations and error handling predictably and in terms understandable to callers.
4. **Never** expose database data automatically or without protection; a minimum security baseline applies from V0 onward.
5. Establish a straightforward technical foundation that permits future extensions without prematurely implementing them.

### 1.3 Non-goals

V0 is **not** a comprehensive Java/Jakarta application server, **not** a general API gateway comparable with DreamFactory, and **not** a standalone workflow or business-process platform. It does not replace database backup tools, enterprise identity systems, or complex application services.

## 2. Users, usage scenarios, and system boundary

**Target users:** (a) SASD developers who require a coherent data API; (b) operators of individual installations; (c) web, desktop, and server applications that consume HTTP and JSON.

**Scenario A — independent installations:** One instance hosted at api.sasd.de accesses database A; another installation at a different URL accesses database B. Both use the same approved software version but have independent configuration and credentials. No installation may act as an unrestricted gateway to another installation's resources.

**Scenario B — simple CRUD client:** An authenticated client lists books, reads a book, creates a record, updates permitted fields, and removes a record if the resource configuration authorizes deletion. This example is neutral. HARALD library lending and other business transactions remain outside the generic CRUD server.

**System boundary:** The API Server receives HTTP requests and interacts with a configured SQL database. Application user interfaces, database administration, application table migrations, backup scheduling, email delivery, and domain workflows remain external responsibilities.

## 3. Target environment and constraints

| ID | Mandatory constraint |
|---|---|
| RB-01 | Initial target environment: PHP 8.4 and MariaDB 10.11 or a compatible MySQL database. Other version combinations require compatibility testing before approval. |
| RB-02 | Deployment must be possible on ordinary PHP shared hosting without Docker, Redis, root privileges, or a permanently running application process. |
| RB-03 | The deployment must **not** require a /public directory; where necessary, the HTTP entry point is index.php in the web root. |
| RB-04 | URL routing must be tested on hosting platforms where .htaccess or mod_rewrite may be limited or ineffective. A documented, compatible fallback is required if clean paths do not work. |
| RB-05 | HTTP(S), JSON, and standard HTTP methods and status codes form the external communication interface. |
| RB-06 | Every installation has separate configuration. Centralized instance management and arbitrary switching between installations are out of scope for V0. |
| RB-07 | The source is to evolve as an independent MIT-licensed open-source project; third-party code may be incorporated only after checking applicable license obligations. |

## 4. Functional requirements

All requirements marked **MUST** are mandatory for V0.1.0. **SHOULD** indicates an important feature that may be deferred only with a recorded reason; **MAY** is optional and not part of mandatory acceptance. The release acceptance matrix below refers to MUST requirements.

### V0-F01 – Publish REST resources

**Priority:** MUST  
**Requirement:** The server shall expose clearly named HTTP endpoints for explicitly allowed database resources.

**Scope and expected behavior:** Each resource has an external name associated with exactly one authorized database table. At least two different resources can be handled within one installation. Invalid or unconfigured resource names are inaccessible. Public API conventions are documented without inadvertently disclosing internal database structures.

**Acceptance criteria:** Configured resources can be read; a request naming an arbitrary, unconfigured table returns no data and receives an appropriate rejection, such as HTTP 404.

**Reference:** PHP-CRUD-API: table exposure and generated REST resources; DreamFactory/DB2Rest: CRUD resources.

### V0-F02 – Read lists and individual records

**Priority:** MUST  
**Requirement:** An authorized client shall be able to retrieve visible records as a collection and retrieve a single record by primary key.

**Scope and expected behavior:** GET on a collection returns a JSON list using a documented response envelope; GET on an identified record returns a JSON object. V0 supports simple tables with **one** primary-key column; composite primary keys are deliberately excluded. Empty collections and missing single records have distinct response semantics.

**Acceptance criteria:** An existing record is returned correctly, an empty collection succeeds with an empty array, and a nonexistent ID results in HTTP 404.

**Reference:** PHP-CRUD-API / DB2Rest: record retrieval.

### V0-F03 – Create records

**Priority:** MUST  
**Requirement:** An authorized client shall be able to create a record by POST on a writable resource.

**Scope and expected behavior:** Allowed input fields are determined by the resource configuration. Unknown or non-writable fields are rejected. The database may supply default values or auto-generated identifiers. Missing required values cause understandable validation errors. The response identifies the newly created record or its key.

**Acceptance criteria:** A valid POST creates one record and yields an appropriate success status, generally 201; omitting a required field creates nothing and returns a structured error.

**Reference:** PHP-CRUD-API / DB2Rest: single-record insertion.

### V0-F04 – Update records

**Priority:** MUST  
**Requirement:** An authorized client shall be able to update existing records.

**Scope and expected behavior:** PUT and PATCH have **distinct, explicitly documented semantics**: PUT replaces the configured mutable representation according to a defined full-replacement contract; PATCH modifies only explicitly supplied fields. Immutable keys, unauthorized columns, and invalid values remain protected. Updating a nonexistent record must not implicitly create it.

**Acceptance criteria:** A PATCH containing one mutable field leaves all other fields unchanged; attempts to modify read-only fields fail; updates to nonexistent keys return 404.

**Reference:** PHP-CRUD-API: update; DB2Rest: update/patch.

### V0-F05 – Delete records

**Priority:** MUST  
**Requirement:** An authorized client may delete an existing record only if the resource permits deletion.

**Scope and expected behavior:** V0 DELETE targets a **single fully identified record**. There is no conditional bulk delete. The server preserves database referential integrity and does not bypass constraints.

**Acceptance criteria:** Only the specified record is deleted. Attempts to delete through a read-only resource fail. A foreign-key conflict produces a meaningful failure without data loss.

**Reference:** PHP-CRUD-API / DB2Rest: delete; explicit omission of bulk delete.

### V0-F06 – Configure resources and permitted operations

**Priority:** MUST  
**Requirement:** Each operator shall determine the resources and methods available within an installation.

**Scope and expected behavior:** Documented configuration lists allowed resource names and their underlying SQL table, readable/writable columns, primary-key definition, and permitted HTTP methods. **Deny by default** applies to every resource, column, or operation that has not explicitly been authorized. V0 exposes no public endpoint that modifies this configuration at runtime.

**Acceptance criteria:** Creating a new table in the database does not make it automatically accessible. Disallowed HTTP methods fail even if the database account itself would technically allow the operation.

**Reference:** PHP-CRUD-API: mapping/authorization; DreamFactory: explicit service and resource exposure.

### V0-F07 – Simple filters

**Priority:** MUST  
**Requirement:** A client may constrain a list using defined and safe filter conditions.

**Scope and expected behavior:** V0 supports equality filtering on explicitly permitted columns and multiple equality comparisons joined by AND. Field names must never be copied unchecked into SQL, and values must always be parameterized. Invalid filters and unknown columns yield defined client errors rather than raw SQL messages. Complex OR trees, free-form SQL, and joins are out of scope.

**Acceptance criteria:** Permitted equality filters return matching rows, prohibited fields are rejected, and SQL-injection input cannot execute attacker-supplied SQL.

**Reference:** PHP-CRUD-API: filters; DB2Rest: RSQL as a deliberately simplified reference.

### V0-F08 – Sorting and page size

**Priority:** MUST  
**Requirement:** List endpoints shall provide predictable ordering and bounded result sets.

**Scope and expected behavior:** At minimum, one allowed sort field supports ascending and descending order. Pagination has configurable enforced maximum sizes. Without explicit sorting, records are returned in a stable primary-key order. Client-supplied parameters cannot override the maximum page size. Numeric defaults and limits are defined in the implementation specification.

**Acceptance criteria:** Consecutive pages return correctly ordered records and respect the size limit. An excessive requested page size is rejected or capped under a clearly documented rule.

**Reference:** PHP-CRUD-API: pagination and page limits; DreamFactory/DB2Rest: sorting/pagination.

### V0-F09 – Uniform JSON and error format

**Priority:** MUST  
**Requirement:** Clients shall be able to interpret successes and failures consistently.

**Scope and expected behavior:** V0 defines predictable JSON representations for lists, individual resources, and errors, including a machine-readable error code, a readable message, and optional field-level details. The relevant standard HTTP status codes include at least 200/201/204, 400, 401, 403, 404, 405, 409, 413, 415, 422, and 500, as applicable. Raw SQL, server file paths, passwords, and stack traces must never reach the client.

**Acceptance criteria:** Invalid field input produces a documented 4xx response and JSON error object; a simulated internal failure does not expose confidential implementation details.

**Reference:** All reference products: consistent REST responses.

### V0-F10 – Configuration and independent installations

**Priority:** MUST  
**Requirement:** The same program shall run for different applications without modifications to its source.

**Scope and expected behavior:** At least two installations must run independently using **different URLs, databases, API keys, and resource lists**. Credentials and other secrets are kept outside the repository and publicly served files. Configuration is documented; switching to a new installation requires no edits to program source. Log and cache configuration must not mingle private information between separate installations.

**Acceptance criteria:** Two test installations use the same code revision, serve different data, accept only their own keys, and prevent cross-instance access.

**Reference:** PHP-CRUD-API: connection and environment settings; DreamFactory: data-source configuration.

### V0-F11 – Minimal API-key authentication

**Priority:** MUST  
**Requirement:** Non-public data endpoints shall be protected from anonymous access from the first version.

**Scope and expected behavior:** Each installation requires at least one strong, installation-specific API key supplied through a documented HTTP header. **All data operations require authentication by default**, including GET. Key comparison is secure; values are not included in logs or errors. A configuration change supports rotation or revocation. Roles, user accounts, and interactive browser login are excluded from V0. A hard-coded key in a publicly distributed web or mobile client **is not a secure secret**: use a trusted backend proxy or a later user-authentication design.

**Acceptance criteria:** Missing or incorrect keys never grant data access. A valid key can reach only configured resources, and a revoked or replaced key stops working.

**Reference:** PHP-CRUD-API: apiKeyAuth; DreamFactory/DB2Rest: API keys.

### V0-F12 – Input validation and SQL security

**Priority:** MUST  
**Requirement:** Malformed or hostile input must not compromise database integrity or system security.

**Scope and expected behavior:** V0 checks JSON syntax, permitted field names, mandatory fields, elementary data types, maximum lengths, and configured allowed values. SQL values are bound as parameters. SQL **identifiers**—including table names, column names, and sort fields—must come exclusively from server-side allowlists. Request body sizes and result sizes are limited. Unexpected database exceptions produce controlled error responses.

**Acceptance criteria:** Tests using unknown attributes, invalid types, excessively large payloads, and SQL-injection strings leave schema and unrelated data intact.

**Reference:** PHP-CRUD-API: validation/sanitation/page limits; DB2Rest: query limits.

### V0-F13 – HTTPS and operational security

**Priority:** MUST  
**Requirement:** Public installations shall not transmit access credentials or protected data over unencrypted channels.

**Scope and expected behavior:** Operators must enforce HTTPS for external requests and document TLS configuration. When a proxy terminates TLS, only trusted forwarded protocol information may influence security decisions. CORS must not unintentionally allow credentialed browser requests from every origin; CORS itself is not authentication. Debug modes and error displays must be disabled for production.

**Acceptance criteria:** Plain HTTP cannot retrieve protected data. Unauthorized origins are handled under the documented CORS policy, and responses never reveal access keys.

**Reference:** PHP-CRUD-API: sslRedirect, cors, firewall; DreamFactory: security governance.

### V0-F14 – Health and diagnostic endpoint

**Priority:** MUST  
**Requirement:** An operator shall be able to check basic server status without exposing confidential details.

**Scope and expected behavior:** A minimal status endpoint reports service availability; a database-readiness check may be provided under explicitly defined access controls. Public responses disclose no database credentials, schema names, filesystem paths, or sensitive version details. Detailed failures are available only through protected operational logging.

**Acceptance criteria:** Health returns a defined result. A simulated database outage is detected or represented as degraded readiness without revealing secrets.

**Reference:** PHP-CRUD-API: status; DB2Rest: Actuator health.

### V0-F15 – Traceable technical logging

**Priority:** MUST  
**Requirement:** Operators need enough information to investigate failures and denied requests.

**Scope and expected behavior:** Logs should contain the request timestamp, route or resource identifier, HTTP method, status, correlation/request ID, and error category as appropriate. They must **not** contain plaintext API keys, passwords, complete authorization headers, or blanket copies of request bodies. Logging targets and maximum log volumes must suit shared hosting and be documented.

**Acceptance criteria:** A deliberately triggered error can be traced by its request ID, and the resulting log contains no API key.

**Reference:** DreamFactory: audit as a later reference; DB2Rest: monitoring; SASD: minimal technical logging.

### V0-F16 – Documentation, examples, and installation

**Priority:** MUST  
**Requirement:** Developers shall be able to install and use the server without knowing its internal classes.

**Scope and expected behavior:** Provide at least a README, a secret-free example configuration, shared-hosting installation instructions, and a working first REST request. Document every V0 route and HTTP method, response and error format, filter and pagination parameters, and security-sensitive defaults. Distinguish development from production settings.

**Acceptance criteria:** An independent tester can follow the instructions to establish a second installation with a separate test database and execute the documented CRUD operations.

**Reference:** PHP-CRUD-API: simple deployment; all projects: API documentation.

### V0-F17 – Automated core verification

**Priority:** MUST  
**Requirement:** The first development release shall be reproducibly testable for correctness and basic security.

**Scope and expected behavior:** Provide tests for all CRUD methods, filtering, sorting, paging, resource restrictions, API keys, validation, SQL-injection prevention, and isolation of two installations. A small sample database with reproducible test fixtures is included. Tests must not rely on production data.

**Acceptance criteria:** A documented test run succeeds. Security negative tests and database failure handling are included in the release acceptance record.

**Reference:** SASD engineering quality requirement; reference catalog §§9–10.

## 5. Non-functional quality requirements

| ID | Category | V0 minimum and evidence |
|---|---|---|
| V0-N01 | Simplicity | A first working deployment on PHP shared hosting without auxiliary background services, verified using the installation guide and a fresh instance. |
| V0-N02 | Maintainability | HTTP, controller, service, repository, security, and infrastructure responsibilities remain clearly separated, with readable and sufficiently commented public interfaces. This sets an architectural quality bar without prescribing individual classes. |
| V0-N03 | Security | Deny by default, least-privilege SQL account, parameterized queries, no secrets in Git/logs/responses, centralized error handling, and documented body-size limits. |
| V0-N04 | Compatibility | PHP 8.4 and MariaDB 10.11 are the initial target combination; provide test notes for development on Windows/WSL and deployment to Linux shared hosting. |
| V0-N05 | Reliability | Completed individual writes are durable; failed individual operations must not result in partial updates; all database constraints remain effective. |
| V0-N06 | Performance | Typical small records shall be processed without unreasonable memory or query overhead. Record representative measurements and data volumes during acceptance; do not invent strict millisecond limits in this document. |
| V0-N07 | Portability | Deployment differences are expressed in documented configuration. Verify a webroot index.php setup and a routing fallback if URL rewriting is restricted. |
| V0-N08 | Testability | Repeatable tests using independent test databases, protected test credentials, and reproducible sample records. |
| V0-N09 | Data protection | Minimize returned/logged data, protect access, and support authorized deletion. Domain-specific privacy/legal responsibilities remain with the application and operator. |

## 6. Intended API model and example sequences

The **precise endpoint syntax** below is provisional and must be finalized in the implementation specification. For V0 an explicitly provisional API path, such as **/api/v0/**, is recommended, keeping room for a stable later V1 contract. The software release number V0.1.0 and the version embedded in an HTTP path are different concepts.

**Example `books` resource:** GET /api/v0/books; GET /api/v0/books/42; POST /api/v0/books; PATCH /api/v0/books/42; PUT /api/v0/books/42; DELETE /api/v0/books/42. Database identifiers must **never** be derived from arbitrary client-supplied parameters: only resource configuration can define the mapping.

**Secure request flow:** (1) Client sends HTTPS request with API key. (2) Server verifies authentication and allowed resource/method. (3) Input is validated and restricted to configured fields. (4) A parameterized database operation runs. (5) The server returns a documented JSON response with request ID. (6) Logs record the operation without exposing secrets.

**Transaction boundary:** A single write request should be atomic to the extent supported by the database. Multi-resource business transactions and transactions spanning multiple HTTP requests are not part of V0.

## 7. Data storage and responsibilities

- V0 does not automatically migrate an application's tables or silently create new application tables. Required tables must exist before deployment.
- Only configured columns may be exposed. Password hashes, security values, and other sensitive columns are not published.
- A separate, minimally privileged database account per installation is recommended; production deployments must document and limit the required SQL permissions.
- **Backup and recovery:** Operators are responsible for database backups and restore. Product instructions describe prerequisites and recommend regular restoration tests. A proprietary backup engine is not part of V0.
- Dates and times must be unambiguous: use UTC for timestamps generated and stored by V0, where applicable. User-local timezone conversion remains the client's responsibility.

## 8. Explicit exclusions and deferred features

| Not included in V0 | Rationale / destination |
|---|---|
| User registration, password login, SSO, JWT, LDAP/SAML | Full identity management; consider only after establishing an appropriate user model. |
| Fine-grained RBAC, row policies, tenant isolation | V0 relies on installation isolation and resource/method/column allowlists. V1 addresses more granular authorization. |
| Production PostgreSQL, SQLite, SQL Server, or Oracle adapters | Verify one SQL driver and a clean adapter boundary first. |
| JSON-file storage and remote APIs as storage backends | They require distinct consistency, locking, and error models. |
| Automated joins, advanced RSQL, arbitrary aggregations | More query-cost and authorization complexity. |
| Bulk insert/update/delete; cross-request transactions | More complex partial failures, retries, and atomicity semantics. |
| Database-schema mutation over HTTP and client-supplied raw SQL | Disproportionately risky for an initial public API. |
| OpenAPI generator, web administration UI, GraphQL | Not required for a documented first CRUD API; revisit OpenAPI in V1. |
| File upload, S3, FTP/SFTP, SMTP, SOAP, push, webhooks | Integrations platform rather than lightweight data API. |
| GIS/GeoJSON, MCP/AI interface, Prometheus/Grafana | No immediate benefit to core V0; evaluate separately when needed. |

## 9. Acceptance scenarios and release gate

| Test case | Evidence | Requirement links |
|---|---|---|
| AT-V0-01 | Clean installation on target hosting, including documented URL routing | F01, F10, F16, N01, N07 |
| AT-V0-02 | Authorized GET list and single-object retrieval, including missing record/empty list | F02, F09 |
| AT-V0-03 | POST, PUT, PATCH, DELETE with database verification and allowed fields | F03, F04, F05, F06 |
| AT-V0-04 | Equality filters, allowed sorting, and bounded pages | F07, F08 |
| AT-V0-05 | Unknown table, column, and HTTP method cannot be accessed | F01, F06, F12 |
| AT-V0-06 | Anonymous, incorrect, or revoked API key cannot access data | F11, F13 |
| AT-V0-07 | SQL injection, invalid JSON types, and oversized request rejected safely | F09, F12 |
| AT-V0-08 | Two independent installations with distinct data and credentials | F10 |
| AT-V0-09 | Health and logging test with reachable/unreachable database | F14, F15 |
| AT-V0-10 | README examples and automated tests reproducible | F16, F17 |

**V0.1.0 acceptance:** All MUST requirements and AT-V0-01 through AT-V0-10 are demonstrably satisfied. Failed tests must be recorded as open issues; any changed requirement requires an explicit decision. A functionality demonstration without negative security tests does not constitute complete acceptance.

## 10. Risks, assumptions, and unresolved decisions

1. **Shared-hosting routing:** Test the actual behavior of index.php, PATH_INFO, and rewriting. Provide a workable fallback in the installation documentation.
2. **Secrets in untrusted clients:** An API key included in publicly delivered browser code or other unprotected client software is not a durable secret. The first supported use is limited to trusted clients, backend-to-backend requests, or an appropriate authentication proxy.
3. **Primary keys:** V0 supports single-column keys only; determine exact numeric/UUID support and URL encoding in the technical specification.
4. **PUT vs. PATCH:** Full replacement and partial update semantics must be documented and tested with nullable fields and defaults.
5. **Configuration rather than discovery endpoints:** Resource exposure is explicit; schema introspection must not silently publish tables.
6. **Application security:** Without additional application-level protection, V0 is not intended for openly registered multi-user public web systems or particularly sensitive domain records.

## 11. Rationale and provenance of selected features

| Selected V0 building block | Reference catalog | Why include it immediately? |
|---|---|---|
| Basic CRUD and resource allowlists | PHP-CRUD-API §§3.1–3.2; DreamFactory §4.1 | Fundamental product value |
| Simple filtering, pagination, limits | PHP-CRUD-API §§3.3–3.4; DB2Rest §5.3 | Usable client lists without unbounded queries |
| API key and allowlist | PHP-CRUD-API §3.4; DreamFactory §4.2 | Protection must precede production use |
| Validation and safe errors | PHP-CRUD-API §3.4; DB2Rest §5.4 | Data integrity and safe HTTP surface |
| Health and logs | PHP-CRUD-API §3.2; DB2Rest §5.5 | Useful diagnostics without a monitoring platform |
| Future-friendly data-access separation | DB2Rest §5.1 | Allow extensions later without implementing other adapters now |

## 12. Maintenance, sign-off, and related documents

- **V0 requirements (this document):** intended mandatory scope **once approved** by the project owner.
- [V1 requirements](./requirements-v1.en.md): separate planned scope for a later stable product release. V1 expands upon an accepted V0 baseline.
- [German reference catalog](./referenzprodukte-funktionskatalog.md): external research and inspiration, not automatically included requirements.
- **V0 technical implementation specification (not yet written):** concrete implementation, data formats, configuration, keys, tests, and HTTP error contracts.
- **Change control:** New requirements require a stable identifier, a rationale, and an explicit assignment to V0, V1, or a later release.

---

**Document status:** Detailed requirements **draft**. It does not claim completed implementation, successful test acceptance, or formal approval by the project owner.
