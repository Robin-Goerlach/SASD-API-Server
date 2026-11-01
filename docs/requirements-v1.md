> **Language:** **English (default)** | [Deutsch](requirements-v1.de.md)

# SASD-API-Server — Requirements Specification V1

> **Status:** Draft for the future 1.0 product release · **Document version:** 0.1 · **Created:** 2026-11-01  
> **Repository:** [Robin-Goerlach/SASD-API-Server](https://github.com/Robin-Goerlach/SASD-API-Server)  
> **German source:** [Lastenheft V1](./lastenheft-v1.md) · **Foundation:** [Requirements Specification V0](./requirements-v0.en.md) · [Reference catalog (DE)](./referenzprodukte-funktionskatalog.md) · [Feature catalog (EN)](./reference-products-feature-catalog.en.md)

## 1. Position of the target product

This requirements specification defines the **functionality expected** of a stable **SASD-API-Server V1.0**. It is a **future target after V0**, not an instruction to implement all 157 features from the reference-product research. It describes the **what and why**, boundaries, and verifiable expectations. The **how**—concrete classes, libraries, SQL generation, frameworks, and caching technologies—belongs in a later technical implementation specification (German: *Pflichtenheft*).

### 1.1 Why V1 is needed

Following a small and verifiable V0, multiple SASD applications should be able to reuse the same API server core over the long term. This requires stable API contracts, more granular authorization, consistent data representations, an additional database adapter, controlled queries, and improved diagnostics and documentation. Despite this broader scope, the product shall remain **substantially smaller than DreamFactory, JBoss, or a universal integration gateway**.

### 1.2 Product definition

The SASD API Server is a **configurable REST/JSON data-service platform** that maps explicitly defined resources to configured data sources, protects access to those resources, and presents a consistent HTTP interface to different clients. Each installation has separate URLs, settings, credentials, and resource definitions. Domain rules for HARALD, health-related applications, or other products shall not simply be moved into a generic CRUD service.

### 1.3 Dependency on V0

**V1 builds upon a fully accepted V0 release.** All MUST requirements from the [V0 requirements specification](./requirements-v0.en.md) remain in force unless explicitly extended or deliberately changed under the versioned V1 contract. Fixing V0 defects, preserving security guarantees, and maintaining testability take priority over adding V1 features.

### 1.4 Stakeholders and target users

- **SASD development team:** requires a reusable core and stable contracts/tests.
- **Application developers:** need documented and secure endpoints without depending on internal SQL table layouts.
- **Operators:** must be able to independently configure, deploy, monitor, back up, and recover instances and perform updates.
- **Client applications:** should consume the same service through HTTP/JSON regardless of programming language.
- **Security stakeholders:** need least-privilege permissions, explicitly exposed resources, traceability, and controlled treatment of personal data.

## 2. Strategic objectives and measures of success

| Objective | Product goal | Verifiable result |
|---|---|---|
| Z-01 | Uniform API | Two different sample applications independently use the same API conventions. |
| Z-02 | Configuration, not code duplication | Adding an API instance requires no change to the shared source code. |
| Z-03 | Pluggable persistence | A defined resource/CRUD contract works with MariaDB/MySQL and SQLite. |
| Z-04 | Secure operation | Resources remain invisible unless explicitly approved; roles and permitted actions are enforced server-side. |
| Z-05 | Stable V1 interface | Clients can build against a versioned and documented HTTP/JSON contract. |
| Z-06 | Practical operation | Deployment, configuration changes, diagnosis, recovery, and updates are documented and testable. |
| Z-07 | Limited product scope | V1 is a data-API platform, not an arbitrary SQL proxy, ESB, or universal application host. |

## 3. Priorities and release boundaries

**MUST:** Mandatory for V1.0 acceptance. **SHOULD:** Important and preferably included in V1.0, but it may be postponed by an explicit recorded decision and a follow-up plan. **MAY:** Optional, not mandatory for V1.0. MUST requirements shall not be replaced with prototypes without formal scope revision.

**V0 versus V1:** V0 uses a clearly provisional interface, recommended as /api/v0/. V1 introduces its **own stable versioned contract space**, recommended as /api/v1/. Existing V0 clients must not unexpectedly receive incompatible V1 responses. The software release number 1.0 and versioning in the HTTP path are separate concepts; exact paths will be specified in the implementation specification.

**A database adapter implements a common resource contract.** It is not a means for forwarding arbitrary client-provided SQL. The second required adapter must satisfy **the same supported and documented API contract**.

## 4. V1 functional requirements

### V1-F01 – Stable versioned REST/JSON contracts

**Priority:** MUST  
**Business need:** Clients need a long-term reliable interface independent of programming language or database vendor.

**Required functionality and scope:** V1 defines consistent resource paths, methods, status codes, JSON response envelopes, date/time formats, key behavior, and error documents. Public contract changes shall be versioned, documented, and tested for backward compatibility before release. Breaking changes require a new contract space; ordinary internal refactoring must not silently change public behavior.

**Acceptance criterion:** A contract suite executes the same requests against two V1 installations; a deliberately introduced breaking change fails compatibility verification.

**Origin / inspiration:** V0-F01/F09; REST conventions from all reference projects.

### V1-F02 – Resource model and alias names

**Priority:** MUST  
**Business need:** Applications must use stable externally visible resource names without knowledge of underlying SQL tables and columns.

**Required functionality and scope:** A resource definition associates a public name with an approved data source and permitted fields. Public field aliases are decoupled from actual database column names. Required fields, data types, nullability, keys, default behavior, and read-only flags can be described. Newly created database tables are not automatically published. V1 remains **configuration-driven**: schema introspection may suggest resources but may never publish them on its own.

**Acceptance criterion:** Renaming an internal SQL column can leave the external API unchanged when its meaning remains the same; unknown columns and resource names remain inaccessible.

**Origin / inspiration:** PHP-CRUD-API §3.1: table/column mapping; DreamFactory §4.1: schema introspection.

### V1-F03 – CRUD with explicit write semantics

**Priority:** MUST  
**Business need:** The V0 CRUD operations must have production-ready, consistent semantics.

**Required functionality and scope:** List, read, create, replace, partial-update, and delete operations have explicit contracts covering missing IDs, prohibited fields, NULL and defaults, database constraints, successful status codes, and repeat requests. A single write request is atomic; errors cannot leave one logical record partially modified. V1 must not allow arbitrary update or delete operations without an unambiguously identified target.

**Acceptance criterion:** Standard CRUD contract tests pass on both required database adapters and demonstrate consistent results in failure cases.

**Origin / inspiration:** V0-F02–F05; PHP-CRUD-API §3.2 and DB2Rest §5.2.

### V1-F04 – Validation and normalized data types

**Priority:** MUST  
**Business need:** Invalid client input should be rejected before a permanent database change and explained in a useful way.

**Required functionality and scope:** Resources may declare elementary rules for required values, type, range, format, maximum length, NULL, and read-only fields. Error responses identify the relevant field and error category without exposing SQL implementation details. Where an alternative database adapter uses a different native type, the external JSON semantics remain consistent. A universal advanced schema language is not required.

**Acceptance criterion:** The same invalid input produces comparable validation errors and no persisted changes on either database backend.

**Origin / inspiration:** PHP-CRUD-API §3.4: validation; DB2Rest §5.1: dialect abstraction.

### V1-F05 – Advanced but bounded filters

**Priority:** MUST  
**Business need:** User interfaces need more than equality filters without being allowed to send SQL statements.

**Required functionality and scope:** Explicitly authorized fields support at least eq, ne, lt, lte, gt, gte, IN, NULL tests, and basic substring/prefix searches where appropriate for the type. Multiple conditions may be combined with AND and controlled OR groups. Condition count, OR nesting, permissible operators, and filter length have enforceable limits. Invalid or unauthorized expressions are rejected. The filter syntax is documented; the exact DSL is chosen in the implementation specification.

**Acceptance criterion:** Number, text, NULL, AND, and OR tests give semantically comparable results across both adapters; prohibited fields and operators are rejected.

**Origin / inspiration:** PHP-CRUD-API §3.3: filters; DB2Rest §5.3: RSQL.

### V1-F06 – Pagination, sorting, and field projection

**Priority:** MUST  
**Business need:** Clients must be able to display large collections securely without unnecessary bandwidth.

**Required functionality and scope:** Use stable default ordering, multi-column sorting with an unambiguous tie-breaker, enforced maximum result counts, and documented pagination parameters. Clients may select **only fields they are authorized to read**. If needed, the server offers a defined total count or clearly documents a `hasMore` flag instead. The chosen offset/cursor paging method shall not make misleading guarantees when records change concurrently.

**Acceptance criterion:** Multi-page fixture data remains predictably ordered, malicious page-size parameters cannot bypass limits, and prohibited columns are never returned.

**Origin / inspiration:** PHP-CRUD-API §3.3; DreamFactory §4.1; DB2Rest §5.3.

### V1-F07 – Declared relationships and bounded related reads

**Priority:** MUST  
**Business need:** Clients need to read common relationships without gaining access to an unrestricted SQL join language.

**Required functionality and scope:** Relationships among approved resources can be declared in configuration or adopted from foreign keys under explicit controls. At least **one level** of read-only related data—such as book → author or author → books—can optionally be expanded. Expansion depth, result size, and query cost are limited. Authorization must be enforced on **every** participating resource. Joins across different databases are not mandatory.

**Acceptance criterion:** A declared relation contains authorized fields only; unauthorized relations or excessive expansions are rejected or constrained under a documented rule.

**Origin / inspiration:** PHP-CRUD-API §3.3: joins/join limits; DreamFactory §4.1: relationships; DB2Rest §5.3: joins.

### V1-F08 – Read-only views as API resources

**Priority:** MUST  
**Business need:** Operators should be able to expose database views that decouple public data models from internal tables.

**Required functionality and scope:** SQL views may be configured as **read-only** API resources with the same reading, filtering, and sorting rules as ordinary resources where supported. Write operations on views remain outside V1 even if a specific SQL engine supports updatable views.

**Acceptance criterion:** An approved view can be read, but POST, PATCH, PUT, and DELETE are denied; unapproved views remain inaccessible.

**Origin / inspiration:** DreamFactory §4.1 and DB2Rest §5.1: database views.

### V1-F09 – Controlled named read queries

**Priority:** SHOULD  
**Business need:** Some application reports cannot be expressed adequately through ordinary CRUD filters.

**Required functionality and scope:** An operator may expose **predefined, named, read-only** queries with typed parameters as separate resources. Clients submit values and an approved query name only, never arbitrary SQL text. Queries remain subject to permissions, response-size limits, and tests. If the feature is deferred, it must not be replaced by unsafe client-driven raw SQL.

**Acceptance criterion:** A sample report accepts parameters and paging; injecting SQL syntax as a parameter cannot alter the query structure.

**Origin / inspiration:** DB2Rest §5.4: named SQL; PHP-CRUD-API §3.5: custom controllers.

### V1-F10 – Two interchangeable database adapters

**Priority:** MUST  
**Business need:** The server should not permanently depend on a single database product family.

**Required functionality and scope:** **MariaDB/MySQL** remains the first mandatory adapter, with **SQLite** added as a second adapter for smaller independent deployments. Both implement the same **well-defined common V1 feature set** for CRUD, validation, filters, pagination, identifiers, and errors. Operators select the adapter by instance configuration; individual requests cannot supply arbitrary connection strings. PostgreSQL, Oracle, SQL Server, JSON files, and third-party REST APIs are not V1 mandatory backends.

**Acceptance criterion:** One shared contract test suite passes unchanged on MariaDB/MySQL and SQLite. Switching configuration requires no change to external resource contracts if the same logical schema is available.

**Origin / inspiration:** DB2Rest §5.1: SQL dialect adapters; PHP-CRUD-API §3.1: PDO/database support.

### V1-F11 – Independent installations and reproducible configuration

**Priority:** MUST  
**Business need:** Different SASD applications must safely share one codebase while maintaining independent environments.

**Required functionality and scope:** URLs, database connections, access keys, resources, policies, logs, and caches are isolated by installation. Secrets reside outside public delivery; configuration is schema-versioned and validated. The same code version can run across independent installations. One primary configured data source per instance is sufficient; **switching among multiple concurrent databases inside one instance** is not mandatory.

**Acceptance criterion:** Two instances using different databases (MariaDB and SQLite), configurations, and permissions are reproducibly deployed; tests show there are no cross-instance data or key leaks.

**Origin / inspiration:** V0-F10; DreamFactory §4.1: multiple sources as a future reference.

### V1-F12 – API-key lifecycle and technical identities

**Priority:** MUST  
**Business need:** Operators must be able to distinguish technical clients and revoke their access independently.

**Required functionality and scope:** Each instance supports multiple identifiable clients and keys. Each key has at least an identifier, a securely managed secret, enabled/disabled state, and a role assignment. Keys can be rotated or revoked without editing server source code. Secrets must not be stored as unprotected plaintext in publicly served configurations or logs. The chosen secure storage scheme is an operational/technical decision. A key embedded in publicly distributed browser code remains **not a hidden secret**.

**Acceptance criterion:** Two keys receive different roles; revoking one blocks it while the other remains active; no responses or logs reveal keys.

**Origin / inspiration:** PHP-CRUD-API §3.4: apiKeyAuth/apiKeyDbAuth; DreamFactory §4.2.

### V1-F13 – Role-based permissions on resources and methods

**Priority:** MUST  
**Business need:** Not every technical client should be authorized to read and modify all data.

**Required functionality and scope:** Configurable **roles** control permitted methods per API resource and, where configured, allowed columns and the use of declared relationships or named queries. **Deny by default** remains mandatory: unassigned roles have no access. Roles apply to authenticated technical clients. Open self-registration, password reset, and social login are not V1 prerequisites.

**Acceptance criterion:** A read-only key can read but cannot write; an editor may update only approved fields; unknown clients and unauthorized routes return no data.

**Origin / inspiration:** DreamFactory §4.2: RBAC; PHP-CRUD-API §3.4: authorization.

### V1-F14 – Configurable record-level visibility limits

**Priority:** SHOULD  
**Business need:** Some clients should see only their own subset when data is stored together.

**Required functionality and scope:** Approved resources may apply simple, **server-enforced** record restrictions based on the authenticated client identity or assigned context, such as `owner_id` or a configured organization ID. These restrictions must also govern reads, updates, deletes, filtering, joins, and counts. A client cannot expand its own access by altering a query parameter. **Full multi-tenancy with dynamic user administration, database-per-tenant routing, and cross-tenant management** is excluded.

**Acceptance criterion:** Client A cannot read or change client B's records, including through counts, joins, or manipulated filter parameters; missing trusted context causes access denial.

**Origin / inspiration:** PHP-CRUD-API §3.4: multiTenancy; DreamFactory §4.2.

### V1-F15 – Consistent write, conflict, and transaction behavior

**Priority:** MUST  
**Business need:** Write operations must not silently lose data during errors or concurrent updates.

**Required functionality and scope:** Every individual write request has a defined transaction boundary and respects database constraints. The API distinguishes at least not found, validation error, conflict, and internal failure. Success must not be reported until a database operation has successfully completed. Arbitrary transactions spanning several HTTP requests and distributed transactions are outside V1.

**Acceptance criterion:** An intentionally interrupted multi-field update leaves no half-updated record; a referential-integrity conflict produces a controlled 4xx response rather than a false success.

**Origin / inspiration:** V0-F03–F05; DB2Rest §5.2: transaction infrastructure.

### V1-F16 – Optimistic conflict detection

**Priority:** SHOULD  
**Business need:** Concurrent clients should not unknowingly overwrite one another's changes.

**Required functionality and scope:** Where enabled for suitable resources, clients provide a version identifier or modification marker with updates. If this does not match the persisted current value, the write is rejected with a clear concurrency-conflict response. This is only available when the record's version field is reliably maintained.

**Acceptance criterion:** Two clients read the same version; after client A commits a change, client B's stale update is detected and rejected.

**Origin / inspiration:** SASD-derived quality need; V0 conflict handling.

### V1-F17 – Request/response limits and abuse protection

**Priority:** MUST  
**Business need:** A public API must remain controllable when clients are faulty or hostile.

**Required functionality and scope:** Define limits for request-body size, pagination, sort/filter complexity, relation expansion depth, and maximum result size. Additionally provide configurable request-rate controls for technical client identities that can work on the primary hosting target without requiring Redis. The exact split between webserver and application controls will be selected in the implementation specification.

**Acceptance criterion:** Excessive payloads and query workloads are rejected in a controlled manner. One client cannot request unlimited output or overload server resources with manipulated query parameters.

**Origin / inspiration:** PHP-CRUD-API §3.4: page/join limits; DreamFactory §4.2: rate limiting.

### V1-F18 – HTTPS, browser boundaries, and secure operating defaults

**Priority:** MUST  
**Business need:** Authenticated data transmission must remain protected in production.

**Required functionality and scope:** Production operation requires HTTPS and trustworthy proxy configuration; debug details are suppressed. Browser CORS is limited to configured origins. V1 core authentication uses technical client headers; cookie sessions and corresponding CSRF defenses are not automatically included. Security-sensitive configuration errors should be detected early and reported without disclosing secrets.

**Acceptance criterion:** Plain HTTP does not yield protected data; an unauthorized Origin receives no CORS permission; stack traces and debug details remain hidden.

**Origin / inspiration:** PHP-CRUD-API §3.4: sslRedirect/cors; DreamFactory §4.2.

### V1-F19 – Structured technical logging and targeted audit

**Priority:** MUST  
**Business need:** Operators need to understand service behavior and trace significant write activities.

**Required functionality and scope:** Technical logs contain at least UTC timestamp, request ID, instance, resource, operation, status, error category, and technical client identifier where legally appropriate. **Write operations** additionally produce audit events indicating who, when, what operation, which logical record, and the outcome. Complete personal data, bearer tokens, passwords, and full request bodies are not logged by default. Log rotation, retention, and access protection are documented. This does not claim to be a tamper-proof enterprise audit vault.

**Acceptance criterion:** Successful and denied writes can be matched to protected audit events without finding plaintext keys or full records in the logs.

**Origin / inspiration:** DreamFactory §4.2: audit logging (may be commercial); DB2Rest §5.5: observability.

### V1-F20 – Health, readiness, and diagnostics

**Priority:** MUST  
**Business need:** Operators must be able to recognize and localize failures quickly.

**Required functionality and scope:** Provide a minimal public (or otherwise protected) health endpoint plus a **protected** readiness/diagnostic view of the configured database and essential dependencies. Public status data is limited to non-sensitive facts. Versions, schema names, file paths, and connection details must not be exposed casually. The checks remain usable on shared hosting without Prometheus or Grafana.

**Acceptance criterion:** When the database is intentionally disconnected, protected readiness reports a failure without exposing credentials; public health reveals no internal topology.

**Origin / inspiration:** DB2Rest §5.5: Actuator health; PHP-CRUD-API §3.2: status.

### V1-F21 – Derivable OpenAPI documentation

**Priority:** MUST  
**Business need:** Client developers need a verifiable machine-readable specification of the published API.

**Required functionality and scope:** The server can generate or serve an OpenAPI contract covering allowed resources, paths, methods, parameters, JSON schemas, authentication, and typical errors. The document must not advertise unauthorized resources as publicly callable endpoints. The published API version matches the release contract. A separate Swagger web interface is optional, not mandatory.

**Acceptance criterion:** An OpenAPI validator accepts the output. Generated endpoints agree with contract tests, and the description reveals no restricted resource information.

**Origin / inspiration:** PHP-CRUD-API §3.5: OpenAPI; DreamFactory §4.1: Swagger/OpenAPI.

### V1-F22 – Controlled extension points

**Priority:** SHOULD  
**Business need:** Some client applications require additional domain-specific HTTP operations.

**Required functionality and scope:** Provide a documented mechanism to add **explicitly registered** controllers/handlers or read queries without duplicating routing, authentication, and logging. Extensions are governed by the same access and error rules. The aim is a small, controlled extension interface—not arbitrary execution of uploaded scripts, public SQL interpreters, or a broad plugin marketplace.

**Acceptance criterion:** A sample extension is accessible only through its configured path and role, uses standard error handling, and remains isolated from baseline CRUD.

**Origin / inspiration:** PHP-CRUD-API §3.5: custom controllers/middleware; DreamFactory §4.3: custom services.

### V1-F23 – Validate, export, and safely upgrade configuration

**Priority:** MUST  
**Business need:** Configuration mistakes must not silently grant data access or destroy deployment settings.

**Required functionality and scope:** Validate the configuration schema version, supported options, required values, and unknown options before or during startup. Invalid mappings or access policies cause a secure and understandable failure rather than a permissive fallback. Non-secret configuration can be exported and versioned; secrets are managed separately. Older V0 settings receive documented migration procedures.

**Acceptance criterion:** A deliberately invalid configuration is not silently accepted; a documented migration can be performed and rolled back if it fails.

**Origin / inspiration:** DreamFactory §4.4: configuration migration; PHP-CRUD-API §3.1: configuration.

### V1-F24 – Installation, upgrade, operation, and recovery

**Priority:** MUST  
**Business need:** Open-source users must be able to install, update, and restore V1 safely.

**Required functionality and scope:** Provide documented deployment on supported PHP hosting without mandatory Docker or Redis; using index.php in the webroot remains supported. Instructions cover PHP extensions, database privileges, HTTPS, routing fallback, log locations, configuration backup, upgrade and rollback order, plus operator responsibilities for database backup and restore testing. The server need not implement a universal database-backup scheduler.

**Acceptance criterion:** A fresh V1 installation and a documented V0→V1 upgrade can be demonstrated; configuration backup and database recovery succeed in a test environment.

**Origin / inspiration:** V0: shared-hosting constraints; DreamFactory §4.4: operations/migration.

### V1-F25 – Language-independent client access

**Priority:** MUST  
**Business need:** Desktop, web, and server programs should be able to consume the same API.

**Required functionality and scope:** All public behavior relies on standard HTTP/JSON, UTF-8, well-defined data types, and documented error formats. Clients do not need knowledge of PHP sessions or the server's programming language. Examples for at least **C#/.NET**, **Java**, and a simple **curl** client show the same resource operations. These are documentation samples, not mandatory full SDKs.

**Acceptance criterion:** A C#, Java, and curl example each read the same test resource through the same authentication scheme; responses conform to the documented contract.

**Origin / inspiration:** SASD product requirement: use across different applications.

### V1-F26 – Automated tests and security/compatibility release gates

**Priority:** MUST  
**Business need:** Changes to V1 must not quietly introduce vulnerabilities or database-specific regressions.

**Required functionality and scope:** Require repeatable CRUD, filter, relation, role, rate-limit, audit, error, configuration, and health tests, plus **shared contract tests** for MariaDB/MySQL and SQLite. Security negative tests cover unauthorized resources, missing authentication, SQL injection, record-visibility constraints (when enabled), oversized requests, and unsafe routing variants. Release evidence records test outcomes, outstanding risks, and exceptions.

**Acceptance criterion:** The required V1 MUST test suite passes on both adapters; a deliberately introduced authorization defect causes at least one security test to fail.

**Origin / inspiration:** Reference catalog §§9–10: verification procedures; V0-F17.

### V1-F27 – Operational metrics and performance evidence

**Priority:** SHOULD  
**Business need:** Operators should be able to assess load, response behavior, and database costs.

**Required functionality and scope:** Collect privacy-conscious metrics, such as request counts, error categories, and response times, without capturing sensitive request payloads. Record representative measurements and dataset sizes. Prometheus-format export, Grafana, and Datadog integrations are optional; the core server must work without them.

**Acceptance criterion:** A repeatable performance report documents at least one small and one larger sample dataset, accompanied by basic operating measurements.

**Origin / inspiration:** DB2Rest §5.5: metrics; DreamFactory §4.4: usage reporting.

## 5. V1 interfaces and expected usage scenarios

### 5.1 Scenario S1 — several independent SASD products

HARALD uses its own installation exposing approved resources such as media and physical copies. Another SASD application uses a separate URL, another database backend, and distinct API-key/role rules. Both installations run the same server software and share API conventions, but **do not** share access-control or data boundaries.

### 5.2 Scenario S2 — controlled read and edit

A read-only client accesses approved resources and filters results on the server. An authorized editor can update defined columns. An invalid API key, disallowed method, or prohibited column causes a controlled rejection. Security enforcement belongs in the API server, never exclusively in the user interface.

### 5.3 Scenario S3 — change databases without changing the API contract

A small local application uses SQLite while a hosted application uses MariaDB. Resources have the same logical names and data types; the HTTP client need not understand the database-specific SQL differences. **Switching adapters does not automatically migrate existing data between database engines.**

### 5.4 Scenario S4 — controlled relationships

A resource with a known foreign key may embed an explicitly approved related resource. Relation depth and result size are bounded. No caller can request arbitrary joins against all tables in the database.

### 5.5 Scenario S5 — operation and troubleshooting

An installation reports database failure through its protected readiness check. Technical logs record the timestamp, error category, and request ID but no credentials. The operator can follow documented steps to verify database connectivity, restore configuration, and reactivate the service.

## 6. Non-functional requirements

| ID | Area | Expected behavior / evidence |
|---|---|---|
| V1-N01 | Usability | Documented examples and understandable errors; consistent resource naming; no unexpected API side effects. |
| V1-N02 | Security | Deny by default, least-privilege database accounts, separated instances, secure key rotation, parameterized SQL values, allowlisted identifiers, HTTPS, abuse limits, and protected diagnostics. |
| V1-N03 | Maintainability | Clear boundaries between HTTP, API contracts, authorization, business rules, repositories, and storage adapters. Extension hooks may not bypass the core architecture. |
| V1-N04 | Portability | PHP-8.4-based deployment on documented Linux web hosting and Windows/WSL development/test environments. Exact minimum PHP versions and required extensions are specified for each release. |
| V1-N05 | Data integrity | Atomic individual writes, effective constraints, and controlled write conflicts. No promise of distributed transaction support. |
| V1-N06 | Availability | Failures are observable without exposing internals; one client cannot trigger unlimited expensive queries. No unverified high-availability or SLA claims. |
| V1-N07 | Performance | Reproducible profiles for specified record counts and data volumes; response-time and memory measurements; enforced query/result limits. Concrete thresholds are chosen before acceptance in the technical implementation specification and measured. |
| V1-N08 | Testability | Automated contract and security tests on both database backends; reproducible installs with fixtures rather than production data. |
| V1-N09 | Documentation | German primary product documentation; API contracts, examples, installation, and operating guidance understandable to outside open-source contributors. English translations are desirable but subject to separate approval. |
| V1-N10 | Data protection | Data minimization, targeted record/column controls, restrained logging/auditing, and documented retention/deletion responsibilities. Domain-specific legal review remains with application and operator. |
| V1-N11 | Openness | The core product should operate without a proprietary runtime dependency. Licenses of third-party modules must be examined; commercial DreamFactory features cannot be assumed to be freely reusable. |
| V1-N12 | Configuration security | Invalid settings cause a secure failure or prevent resource publication instead of falling back to broad authorization. |

## 7. Architectural principles without prescribing implementation

1. **Separate the public contract from SQL and individual dialects.** Clients address resources, not tables or connection metadata.
2. **Configuration precedes exposure.** Database metadata may inform suggested mappings but never creates permissions.
3. **Uniform security enforcement.** Extensions, views, and relations must obey authentication, roles, limits, and audit controls.
4. **Application domain logic remains application logic.** The API provides data operations; a library loan, return, or healthcare workflow needs its own domain services rather than generic PUT or DELETE.
5. **Small but real adapter model.** Exactly two V1 mandatory adapters—MariaDB/MySQL and SQLite. Everything else is optional expansion.
6. **Shared hosting remains the primary target.** Redis, Docker, cron, queue services, and persistent Java processes are not mandatory. Other deployment models may be supported but are not release requirements.
7. **No execution of arbitrary client-supplied SQL or scripts.** Named queries and extensions are accessible only through controlled operator configuration.

## 8. Explicit exclusions from V1.0

| Excluded feature family | Why not in V1? | Future consideration |
|---|---|---|
| Java/Jakarta EE application hosting like WildFly/WebLogic | Different product category from a data REST API. | Not a product goal. |
| Fully featured no-code web administration console | Significant UI, authentication, and maintenance work. | Separate later component. |
| OAuth2/OIDC/SAML/LDAP SSO, user self-service, password reset | Requires a comprehensive identity model. | After concrete integration needs. |
| Full multi-tenancy using database-per-tenant or runtime tenant DB switching | Complex isolation, pooling, and operations security. | Separate architectural study. |
| Multiple simultaneous database sources within a single instance and cross-database joins | Too much complexity for the first stable API release. | Later if needed. |
| PostgreSQL, Oracle, SQL Server, and NoSQL adapters | V1 thoroughly tests two engines instead of superficially claiming many. | Later releases. |
| JSON file storage as a universal transaction backend | Different locking, concurrency, and recovery semantics from SQL. | Separate adapter research. |
| GraphQL, generic RSQL against arbitrary dialects, arbitrary SQL expressions | Increased attack surface and query-planning burden. | Only if justified. |
| Unrestricted mass writes/deletes and transactions across multiple HTTP requests | High complexity in protection and error handling. | Based on specified use cases. |
| Database-schema mutation over a public HTTP API | Severe security risks. | Explicitly outside the core. |
| SOAP-to-REST, FTP/SFTP, S3, email, push, and general webhooks | Integrations rather than core data-API tasks. | Separate services/modules. |
| GIS/GeoJSON and AI/MCP tool-server functionality | Specialized needs; not required for core CRUD. | Separate requirement/research. |
| Native desktop GUI and fully supported .NET/Java SDKs | V1 focuses on the server and protocol. | Client libraries as needed. |
| Proprietary cross-platform backup engine | Database/operator responsibility; recovery runbooks still required. | External tools and runbooks. |
| Grafana/Prometheus servers, Datadog, and cluster orchestration | Operational options, not prerequisites for PHP shared hosting. | Optional monitoring package. |

## 9. V1.0 acceptance catalog

**Release rule:** The V0 MUST baseline remains satisfied **and** every V1 MUST requirement has verifiable acceptance evidence. SHOULD items may be deferred only with an explicit approval and follow-up decision. Database-related tests must execute on the two mandatory adapters.

| Test | Scope and assertion | Relevant requirements |
|---|---|---|
| AT-V1-01 | Stable V1 paths, schemas, HTTP statuses, and versioned errors validated by contract tests | F01, F03, F04 |
| AT-V1-02 | Resource/field aliases and allowlists; new database tables are not auto-published | F02 |
| AT-V1-03 | Identical CRUD and validation semantics on MariaDB/MySQL **and** SQLite | F03, F04, F10 |
| AT-V1-04 | Operators, AND/OR, field projection, and bounded pagination, including rejected queries | F05, F06 |
| AT-V1-05 | One-level relation expansion, limits, and authorization on related resources | F07 |
| AT-V1-06 | Approved view can be read, never written | F08 |
| AT-V1-07 | Parallel separated instances with distinct databases, configuration, and keys | F10, F11, F12 |
| AT-V1-08 | Key rotation/revocation; read/write roles; unknown clients rejected | F12, F13 |
| AT-V1-09 | Atomic write, clear constraint conflict, controlled failure | F03, F15 |
| AT-V1-10 | SQL-injection, oversized body, excessive query complexity, request flooding | F04, F05, F17, F18 |
| AT-V1-11 | Technical logs and audit for authorized/denied writes without secrets | F19 |
| AT-V1-12 | Protected readiness with database failure; safe public health status | F20 |
| AT-V1-13 | Generated OpenAPI describes actual routes and access model | F21 |
| AT-V1-14 | Reject/migrate invalid or older configuration; backup and restore trial | F23, F24 |
| AT-V1-15 | curl, Java, and C# examples demonstrate the same HTTP contract | F25 |
| AT-V1-16 | Complete local/CI verification on both adapters; negative security tests detect injected defects | F26 |

### 9.1 Additional functional approval checks

- A new application scenario can publish its approved resources **through configuration**, without copying or modifying core server source code.
- No default endpoint exposes all database schema information, private tables, or technical access credentials.
- No public browser client is incorrectly described as secure solely because it embeds a hard-coded API key.
- V0→V1 adoption includes documented URL/configuration changes; existing V0 clients are not silently broken.
- Known per-adapter limitations are described in a comprehensible compatibility reference.

## 10. Selection from the reference catalog — deliberately limited

| Feature from reference products | V1 decision | Rationale | Catalog source |
|---|---|---|---|
| Table/column mapping and explicit approval | MUST | Stable, protected public contract | PHP-CRUD-API §3.1 |
| Filters, sorting, pagination, projection | MUST | Practical queries with bounded cost | PHP-CRUD-API §3.3; DB2Rest §5.3 |
| Read-only relations and views | MUST | Common relational read scenarios | DreamFactory §4.1; DB2Rest §§5.1/5.3 |
| Multiple database dialects | MUST, only MariaDB/MySQL + SQLite | Genuine adapter verification within limited scope | DB2Rest §5.1 |
| API keys and role-based access | MUST | Safe multi-client technical operation | DreamFactory §4.2; PHP-CRUD-API §3.4 |
| OpenAPI | MUST | Clear contract for third-party clients | PHP-CRUD-API §3.5; DreamFactory §4.1 |
| Health, logs, audit | MUST | Observable, accountable operations | DB2Rest §5.5; DreamFactory §4.2 |
| Named SQL | SHOULD (configured read-only queries) | Useful for reports, not fundamental CRUD | DB2Rest §5.4 |
| Extensible controllers | SHOULD (controlled) | Domain functions without forking core | PHP-CRUD-API §3.5 |
| Row-level visibility | SHOULD (narrow use) | Useful but security-sensitive | PHP-CRUD-API §3.4 |
| RSQL, bulk mutations, arbitrary SQL | EXCLUDED from V1 | Excessive scope and/or risk | DB2Rest §§5.3/5.4 |
| SOAP, email, files, S3, MCP, no-code admin | EXCLUDED from V1 | Outside the focused data-API boundary | DreamFactory §§4.3–4.4 |

## 11. Risks and unresolved decisions

### 11.1 Security risks

**R-01 Data exfiltration via resource discovery:** Database metadata must never become public automatically. Every published resource requires an explicit approval.  
**R-02 Untrusted SQL identifiers:** SQL parameters protect values; table names and ORDER BY identifiers require their own server-side allowlists.  
**R-03 Hidden leaks through filters and joins:** Role and optional record-level limits must apply to response bodies, counts, joins, errors, and search.  
**R-04 Secret handling:** API keys must not be stored in readable browser bundles, publicly served configuration, or logs.  
**R-05 Access logging:** Audit logs must minimize personal data and obey purpose-specific retention decisions.

### 11.2 Technical and operational risks

**R-06 Heterogeneous SQL types:** SQLite and MariaDB differ in syntax, NULL ordering, boolean/date representations, and transactions. Define the supported common contract and test on both.  
**R-07 Shared hosting:** Test rewrite rules, PATH_INFO, and filesystem access on the target host; do not introduce implicit Redis/Docker requirements.  
**R-08 Scope creep:** 157 researched capabilities do not equal 157 product requirements. Any new feature requires prioritization and explicit approval.  
**R-09 Compatibility:** The V0→V1 transition must not silently invalidate running V0 clients or configuration.  
**R-10 Business transactions:** CRUD does not implement a complete domain operation for lending, returning, healthcare workflows, or similar business rules.

### 11.3 Topics for the implementation specification

- Exact URL conventions and the meaning of /api/v0/ versus /api/v1/.
- Final JSON data types and mappings for date/time, decimal, boolean, and UUID across MariaDB/SQLite.
- Structure, versioning, and validation rules of installation and resource configuration.
- Filter grammar, OR limits, field projection, and default page sizes.
- Key/policy storage, secure secret comparison, and rotation.
- OpenAPI generation approach and visibility of diagnostic endpoints.
- Load-test profiles and SLO/threshold choices; rate-limiting approach suitable for shared hosting.

## 12. Documentation, change control, and approval

1. **[V0 requirements](./requirements-v0.en.md):** the small initial release; its mandatory requirements remain part of the foundation.
2. **This V1 requirements specification:** planned scope for a stable future V1.0, not evidence of an existing implementation.
3. **[Reference catalog DE](./referenzprodukte-funktionskatalog.md) / [EN](./reference-products-feature-catalog.en.md):** external research and possible inspiration, not proof that all upstream features were tested.
4. **[V1 technical implementation specification (German)](./pflichtenheft-v1.md) (draft):** architecture, actual formats, REST examples, and test design.
5. **Change management:** Every additional feature receives an ID, justification, priority, and verifiable acceptance criterion. Deferred SHOULD requirements are recorded.

---

**Document status:** Thorough functional requirements **draft**. It does not claim that the functionality has already been implemented, tested, accepted, or formally approved.
