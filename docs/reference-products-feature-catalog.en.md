# API Server Reference Products — Feature Catalog

> **SASD-API-Server — technical reference study, as of November 1, 2026**
>
> This is the English counterpart of [the original German reference study](./referenzprodukte-funktionskatalog.md). It documents features of the three selected reference products: **PHP-CRUD-API**, **DreamFactory**, and **DB2Rest**. It is a research and idea catalog, **not a product specification or implementation commitment** for SASD V0.1.0.

## 1. Purpose, scope, and how to read this document

SASD deliberately starts small: a lightweight, configurable REST server for MariaDB/MySQL, with a clean data-access architecture and multiple independently deployable instances. We examine existing products to understand proven features and architectural techniques. Whether SASD should adopt any feature will be decided separately.

**Terminology:** CRUD = Create/Read/Update/Delete; database introspection = discovering tables, columns, keys, and relations from metadata; middleware = reusable processing around an HTTP endpoint; RBAC = role-based access control; tenant = a logically isolated data domain.

**Evidence level:** A feature described below as available is documented by a product's official README or documentation. That does **not** mean it has been executed or verified by tests in this study. DreamFactory's documentation mixes community and commercially licensed capabilities; some DB2Rest descriptions are broad feature claims rather than detailed demonstrations. Edition, version, and capability limitations are noted.

**Coverage:** This study inventories the documented feature families relevant to data APIs and operations, including individual middleware components and deployment constraints. It does **not** certify that every internal class, plugin, version-specific option, or commercial add-on has been exhaustively audited. A complete source-level audit and reproducible testing would be required for that.

## 2. Product comparison at a glance

| Dimension | PHP-CRUD-API | DreamFactory | DB2Rest |
|---|---|---|---|
| Core concept | Lightweight PHP script exposing SQL as REST | Comprehensive API and integration platform | Java/Spring-based data API over SQL |
| Language / stack | PHP, PDO, PSR middleware | PHP/Laravel plus connector packages | Java/Spring Boot and SQL dialects |
| MariaDB/MySQL | Yes | Yes | Yes |
| Other SQL backends | PostgreSQL, SQL Server, SQLite | Numerous connectors; check edition | PostgreSQL, SQLite, SQL Server, Oracle, IBM Db2, etc. |
| NoSQL / third-party services | Not the main goal | NoSQL, files, email, SOAP, etc. | Documented database matrix centers on SQL; broader claims require verification |
| Generated REST CRUD | Yes | Yes | Yes |
| Advanced queries / relations | Filters and nested relational reads | Filters, relationships, procedures | RSQL, joins, named SQL, procedures |
| Authentication | Several optional middleware approaches | API keys, users, SSO depending on edition | Configurable Basic and API-key authentication |
| Extension approach | Custom controllers/middleware | Scripts, events, custom services | SQL templates and Spring integrations |
| Fit for basic SASD PHP shared hosting | Relatively strong, subject to tests | Low for the full platform | Low; requires a Java process |
| Core license | MIT | Apache-2.0 (core only; not every package) | Apache-2.0 |

The *hosting fit* assessment is an **SASD architectural judgment**, not a vendor compatibility promise.

## 3. PHP-CRUD-API — detailed feature catalog

**Project:** https://github.com/mevdschee/php-crud-api  
**Primary documentation:** https://github.com/mevdschee/php-crud-api/blob/main/README.md  
**License:** MIT (check the precise repository and dependency licenses before reusing code).

PHP-CRUD-API can be deployed as a single PHP script. It maps an existing relational schema into HTTP resources. It is the closest immediate architectural comparison for our small PHP/MariaDB-oriented first version.

### 3.1 Deployment, configuration, and data sources

**Primary sources:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md)

#### Single-file deployment

A bundled api.php file can be uploaded to an existing PHP web host. This removes the need for a persistent application process or heavyweight installation stack. The web server still must correctly route API paths into the PHP entry point.

#### Composer integration

Besides the stand-alone file, the library can be installed with Composer. A larger PHP application can integrate the API server without mixing all of its internals into application code.

#### Database drivers and PDO

Configuration selects MySQL/MariaDB, PostgreSQL, SQL Server, or SQLite. A matching PDO extension, valid connection credentials, and compatible database versions remain prerequisites.

#### Connection parameters

Host or database filename, port, database name, user, password, and optional initialization SQL are configurable. Identical server code can therefore serve independently configured deployments.

#### Environment variables

Configuration values can be overridden with PHP_CRUD_API_ environment variables. This makes it easier to separate deployment-specific settings and secrets from committed source code.

#### Table exposure control

Configuration can restrict which database tables are exposed. The documented defaults are relatively permissive; SASD should instead require an explicit allowlist for every published resource.

#### Table and column mapping

Physical SQL table and column names can be mapped to public API names. This helps maintain a stable external contract even when a legacy database uses inconvenient naming conventions.

#### Configurable controllers

Record, GeoJSON, OpenAPI, and status controllers are available, and custom controllers can also be registered. The routing layer can thus grow without replacing the entire server.

#### Metadata caching

The API supports configurable caches, including temporary files and optional Redis, Memcache, or Memcached. Caching reduces repeated schema/metadata overhead; file-based caching best fits basic shared hosting.

#### Base path and JSON options

The API's base URL path and several JSON serialization options can be configured. This enables deployments below different subdomains or paths, but does not by itself establish an API versioning policy.

### 3.2 CRUD and record representation

**Primary sources:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md)

#### List records

GET /records/{table} retrieves multiple rows as a JSON list. Filters, sorting, and paging determine which subset the server actually returns.

#### Read one record

GET /records/{table}/{id} addresses a single record by its primary key. The physical key strategy therefore becomes relevant to the REST resource contract.

#### Create a record

POST /records/{table} accepts a JSON record or supported form data and inserts a row. Database constraints and optional validation middleware determine whether values are accepted.

#### Create multiple records

A JSON array can represent several rows for a batch insert. This saves HTTP round trips, but it should not be mistaken for guaranteed multi-step business transactions.

#### Update a record

PUT /records/{table}/{id} modifies fields of an existing row. SASD would still need to specify and test the exact behavior of fields omitted from an update request.

#### Delete a record

DELETE /records/{table}/{id} removes a targeted row if authorization and database rules permit it. Soft deletion, audit trails, and deletion safeguards are separate application decisions.

#### Atomically increment a counter

PATCH /records/{table}/{id} can increment a numeric database field directly. This avoids the race-prone sequence of separately reading, calculating, and writing the new value.

#### JSON objects, arrays, and forms

Inputs may consist of single JSON objects, JSON arrays, or URL-form-encoded POST data. SASD could still choose a single preferred JSON request format for consistency.

#### Binary data as Base64

Binary database fields can be transported using Base64 encoding. Useful for small BLOBs, but large file transfer generally warrants a dedicated file service.

#### JSON-valued database columns

Optional JSON middleware can expose serialized JSON values as native JSON objects or arrays in API responses. This is a representation feature, not a substitute for a document database.

#### XML output

Optional XML middleware translates JSON-style requests and responses into XML. This could support older clients, although it is not a priority for a small SASD V1.

#### Status endpoint

A built-in status controller offers an endpoint for checking basic service availability. Production readiness checks should separately verify critical database dependencies.

### 3.3 Search, relationships, and spatial data

**Primary sources:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md#filters) · [P2](https://github.com/mevdschee/php-crud-api/blob/main/README.md#joins)

#### Comparison filters

A filter parameter supports operations such as equality, inequality, greater/less than, ranges, set membership, and text prefix/suffix matching. Filtering happens in the SQL query rather than after transmitting all rows.

#### Combined conditions

Multiple filters can be combined using AND and OR patterns, including indexed and sometimes lettered parameters. This is more expressive than one search string, but expensive expressions require limits.

#### Filters across related tables

Conditions can inspect linked records, including relationships several steps away. Their use must still honor authorization and tenant restrictions on all involved tables.

#### Column inclusion and exclusion

include and exclude parameters choose which fields are returned. They help reduce payload size but never replace server-side permission checks for sensitive columns.

#### Multi-column sorting

Clients can specify multiple ascending or descending sort keys. Sorting on related columns has documented limitations.

#### Top-N queries

A result limit combined with ordering can return, for example, the latest ten items. This is a common pattern for list views and dashboards.

#### Page navigation

The page parameter supports page numbers and sizes; paginated responses can include the total number of matching rows. Unspecific ordering is stabilized using the primary key.

#### Automatic relationship detection

Foreign keys can be used to discover belongs-to, one-to-many, and many-to-many relationships. Read responses may then embed nested objects rather than return only flat rows.

#### Multi-level joins

Several relationship paths can be requested in one operation. Convenient for clients, but deeply nested responses can sharply increase query cost and response size.

#### Simple cross-text-column search

Optional textSearch middleware searches for a term across textual fields of a resource. It is a basic search convenience, not a full indexed search engine with relevance ranking.

#### GIS geometry fields

Spatial geometry types and filters are supported subject to database capabilities. An SRID option controls how geographic coordinate references are interpreted.

#### Read-only GeoJSON API

The optional GeoJSON controller exposes features or feature collections, including bounding-box and tile-related query parameters. It is documented primarily as a read-only view.

### 3.4 Security, users, and tenants — middleware by middleware

**Primary sources:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md#middleware) · [P3](https://github.com/mevdschee/php-crud-api/blob/main/README.md#authentication)

#### firewall

Restricts requests by IP address and can account for reverse proxies. An IP allowlist adds defense in depth but is not equivalent to individual user authentication.

#### sslRedirect

Redirects unencrypted requests to HTTPS. Behind proxies, forwarded protocol headers must be interpreted only when trustworthy.

#### cors

Controls browser cross-origin behavior using allowed origins, headers, methods, and credentials. CORS is not authentication and does not protect against non-browser HTTP clients.

#### xsrf

Uses a double-submit-cookie approach to defend cookie-authenticated interactions against cross-site request forgery. Primarily relevant to session-driven browser applications.

#### ajaxOnly

Can restrict calls to requests recognizable as AJAX-style requests. This is an auxiliary restriction and not a robust security identity mechanism.

#### apiKeyAuth

Checks an API key passed in a request header against configuration values. Suitable for simple technical clients if secrets are securely held, rotated, and revocable.

#### apiKeyDbAuth

Validates API keys against records stored in a database table. This makes per-user or per-application credential administration more flexible than hard-coded configuration.

#### dbAuth

Provides registration, login, password change, logout, and current-user routes. Users are stored in a database table and sessions manage authentication state.

#### basicAuth

Applies HTTP Basic Authentication with a configured identity source. End-to-end HTTPS is mandatory because the transmitted credentials are otherwise exposed.

#### jwtAuth

Accepts JSON Web Tokens via the Authorization header and makes claims available to authorization rules. Correct signature, expiry, issuer, and key checks are essential.

#### wpAuth

Integrates authentication from WordPress. Useful as an integration example, but not a core SASD requirement.

#### authorization

Controls access by path, table, column, operation, and individual record. Crucially, the README indicates that without added rules the server can expose extensive resources.

#### validation

Checks input types and custom validation callbacks before writing data. Invalid values or application-restricted states can be rejected before SQL is executed.

#### sanitation

Applies customizable input normalization or sanitization handlers. Sanitization must not be mistaken for parameterized SQL, formal validation, or context-safe output encoding.

#### ipAddress

Can populate a dedicated field using the detected caller IP and prevent the client from supplying it. Proxy-header trust is important for the accuracy of the result.

#### multiTenancy

Restricts visible rows using a tenant identifier, allowing multiple customers to share one database. Tenant identity must never be accepted blindly from a user-editable filter.

#### reconnect

Can change database connection settings according to authenticated context, such as selecting a separate tenant database. This is a stronger isolation strategy but increases operational complexity.

#### pageLimits

Restricts maximum pages or returned record counts. These limits protect predictable response sizes and reduce accidental or abusive high-cost requests.

#### joinLimits

Restricts join depth, table count, and related-record expansion. This limits unexpectedly expensive nested queries.

#### Authentication is optional — security is not automatic

Authentication middleware must be enabled and paired with authorization in the right sequence. In particular, any API access to a users table must be explicitly denied unless deliberately required.

### 3.5 Extensions, schema, standards, and limitations

**Primary sources:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md)

#### Customization middleware

Before/after handlers can customize request and response processing. They can support normalization, lightweight validations, and additional output fields.

#### Custom controllers

Additional routes can be implemented with custom controllers that use the project's router and data services. This is a natural place for operations beyond generic table CRUD.

#### Extensible OpenAPI builders

The generated OpenAPI description can be augmented with custom builders, allowing additional endpoints to appear in machine-readable API documentation.

#### Permission-sensitive OpenAPI output

Schema documentation can reflect the current session's allowed tables and actions. Hiding documentation reduces confusion but does not replace backend access enforcement.

#### Read database schema

A schema controller can expose table and column metadata as JSON. Useful for administrative tools, diagnostics, and possibly metadata-driven user interfaces.

#### Change database schema

An optional columns controller can modify the database structure. It is disabled by default and should never be made available on a small public SASD data API.

#### PSR standards

The project lists PSR-4, PSR-7, PSR-12, PSR-15, and PSR-17 support. These standards help with modular HTTP code, middleware interoperability, and consistent implementation conventions.

#### Documented key limitations

Composite primary or foreign keys are not supported; suitable auto-incrementing or UUID-based keys are expected. This matters when automatically exposing legacy schemas.

#### No complex write transactions

The project explicitly limits complex transactional writes. Multi-step business workflows should be implemented with dedicated services and carefully controlled transaction boundaries.

#### No unrestricted SQL expression language

Arbitrary computed SQL expressions and aggregations are not the central purpose of the generic CRUD endpoints. Database views or purpose-built controllers are often preferable.

## 4. DreamFactory — detailed feature catalog

**Project:** https://github.com/dreamfactorysoftware/dreamfactory  
**Primary documentation:** https://docs.dreamfactory.com/ and https://github.com/dreamfactorysoftware/dreamfactory#key-features  
**License:** Apache-2.0 for the open core repository. **Not all advertised capabilities are free or available in the Community edition.** The official README discusses extra database connectors, LDAP/SAML, audit logging, rate limiting, and multi-tenancy in connection with commercial licensing. Verify the exact edition/version matrix before relying on any of them.

DreamFactory is more than a CRUD library: it combines data access, integrations, security management, administration, and additional runtime services.

### 4.1 Automatically generated database APIs

**Primary sources:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#key-features) · [D2](https://www.dreamfactory.com/features/)

#### Connect a database and discover its structure

After a data source is configured, tables, views, keys, and relationships are inspected. REST resources can then be generated without writing a separate CRUD controller for each table.

#### CRUD routes

Read, create, update, and delete operations are exposed as HTTP endpoints. Platform security policies govern who can use them independently of the database structure.

#### SQL and NoSQL connectors

Product information lists MySQL/MariaDB, PostgreSQL, SQLite, SQL Server, Oracle, and additional SQL systems, plus MongoDB, Cassandra, Couchbase, and CouchDB. Check whether each connector is actually available in the open-source edition.

#### Multiple sources behind one server

A DreamFactory instance can register several services or databases. Applications access their distinct backends through a relatively consistent REST interface.

#### Filters

Server-side query parameters narrow result sets. Standard database queries can therefore often be expressed without custom SQL endpoints.

#### Sorting and pagination

Endpoints can sort data and limit result sizes. This prevents clients from fetching entire large tables for simple list views.

#### Relationships and nested responses

Related parent and child information can be embedded in a single response. This avoids having to issue separate requests for every related record.

#### Bulk insert/update/delete

Multiple records can be changed in batch operations. This improves throughput, but partial-failure and transaction behavior need careful examination.

#### Views

Existing database views can be exposed as API resources. Views may help decouple a stable public data model from internal table layouts.

#### Stored procedures and functions

Existing database logic can be invoked as an API service. This can preserve mature legacy business logic instead of reimplementing it in a web layer.

#### Virtual foreign keys

The product documentation describes declaring relationships when suitable database foreign-key constraints do not exist. This helps legacy integration, while shifting more consistency responsibility to the API configuration.

#### Automatic OpenAPI/Swagger

Generated APIs also receive machine-readable descriptions. These can support developer references, interactive API explorers, or client generation.

#### Manage database schema

Administrative features and management APIs can create or modify parts of a database schema. This is a privileged operation that must be isolated from public production APIs.

### 4.2 Security and API governance

**Primary sources:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#security-and-access-control) · [D3](https://github.com/dreamfactorysoftware/dreamfactory#commercial-licenses)

#### Per-application API keys

Calling applications can receive individually managed keys associated with access policies. Keys should not be embedded as unrestricted secrets into publicly distributed applications.

#### User and session management

The platform manages users and authenticated sessions, going beyond a database account or a simple shared API key.

#### Role-based access control (RBAC)

Roles can limit permitted HTTP methods, routes, and database resources. For instance, a read-only client can be separated from an administrative client with write permissions.

#### Column and field permissions

Sensitive columns can be excluded or masked by access policy. Server-side enforcement is essential for password hashes, personal information, and internal identifiers.

#### SSO and external identity providers

Product material lists SAML 2.0, OAuth 2.0, OpenID Connect, Azure AD, LDAP, and Active Directory. Some enterprise identity capabilities are commercial; availability must not be inferred from marketing lists.

#### Rate limiting

Request frequency can be limited to reduce abuse and traffic bursts. The official licensing information also associates some rate-limit capabilities with commercial packages.

#### Audit logging

Access can be recorded with caller identity, time, and API context. Scope and availability vary by edition; request payloads and secrets must not be logged indiscriminately.

#### Identity passthrough

Current vendor material emphasizes passing the caller's identity through to some data sources rather than relying exclusively on one privileged service account. This can preserve database-side permissions but is highly connector- and edition-dependent.

#### Tenant isolation

Product material describes multiple API environments within a platform. Separate services, roles, and genuinely isolated tenant data are distinct concepts; commercial edition boundaries must be checked.

#### Data masking

Fields may be hidden or transformed according to audience or policy. For SASD, explicit response DTOs and allowed-field lists would be the simpler initial approach.

### 4.3 Extensibility and integration services

**Primary sources:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#extensibility) · [D4](https://github.com/dreamfactorysoftware/dreamfactory#supported-data-sources)

#### Pre-process scripts

Scripts can run before the underlying API action to validate, normalize, or reject a request. This allows custom logic without forking the core.

#### Post-process scripts

Scripts can modify or enrich an API response after the main action. Validation, authorization, transaction, and error-processing order still needs careful design.

#### Scripting with PHP, Python, and Node.js

Multiple scripting runtimes are documented. They provide flexibility while increasing dependencies, attack surface, and sandboxing complexity.

#### Custom services

Entirely custom API services can be registered alongside generated CRUD routes. This provides a home for domain operations beyond straightforward table access.

#### Webhooks and event forwarding

Data changes can trigger external notifications or workflows. Dependable delivery requires retry, idempotency, and failure handling; exact guarantees are version-dependent.

#### Local file storage

Files and directories can be administered through REST. Binary object storage is a distinct service rather than just another relational table.

#### Object storage such as Amazon S3 and Azure Blob

Remote buckets or containers can be made accessible via an API service. The platform then serves as an integration layer across storage providers.

#### FTP/SFTP file access

Existing remote file systems can be bridged through REST services. Useful for legacy integration but not required for a new minimal data API.

#### SMTP email sending

An API action can send mail through a configured SMTP provider. This is better treated as a separate integration service than as a core database API feature.

#### Push notifications

Mobile push delivery is offered through provider-specific integrations. These require external credentials and explicit delivery/failure contracts.

#### SOAP-to-REST integration

Existing SOAP/WSDL services can be wrapped in REST-facing endpoints. This helps modernize legacy services but belongs to a more advanced gateway role and may depend on edition.

### 4.4 Operations, administration, and AI integration

**Primary sources:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#administration) · [D5](https://github.com/dreamfactorysoftware/dreamfactory#mcp-server-for-ai-and-llm-integration)

#### Web-based administration console

Administrators manage sources, users, roles, and API keys through a browser UI. It reduces manual configuration but creates an additional privileged interface to secure.

#### Management API

Configuration can also be automated over REST, supporting reproducible environments and infrastructure-as-code workflows. Administrative endpoints need strict access controls.

#### Configuration packages and migration

Configuration can be moved between environments. This helps keep development, staging, and production deployments aligned without re-entering every setting manually.

#### API usage reporting

The platform advertises usage, error, and performance dashboards. Licensing should be checked for commercial telemetry and auditing capabilities.

#### Self-hosting

Installations are documented for Linux/Windows, Docker, and Kubernetes. This works with suitable dedicated infrastructure but is less natural on restricted PHP shared hosting.

#### MCP integration for AI clients

Current documentation describes exposing structured database actions as MCP tools for AI-powered clients. The aim is to use controlled operations instead of permitting arbitrary generated SQL.

#### Controlled data access for AI

AI clients should remain subject to the same identity and authorization rules as ordinary clients. The actual guarantees depend on the tools made available and the caller context.

#### Air-gapped/on-premises operation

Vendor material promotes deployment under an organization's control without mandatory external data flow. That is an operational architecture, not a guarantee: external services, telemetry, and licensing must be evaluated.

## 5. DB2Rest — detailed feature catalog

**Project:** https://github.com/9tigerio/db2rest  
**Documentation:** https://db2rest.com/docs/  
**License:** Apache-2.0 (repository).

DB2Rest uses Java and Spring Boot to publish database REST APIs without requiring ORM models or application classes for every table. Particularly relevant to SASD are its query language, multi-database support, and separation of generic CRUD queries from named SQL.

### 5.1 Connections, metadata, and deployment

**Primary sources:** [B1](https://github.com/9tigerio/db2rest) · [B2](https://db2rest.com/docs/data-api-features) · [B3](https://db2rest.com/docs/multidb/connect-multiple-db)

#### Automatic SQL REST resources

Tables and views are published as REST resources based on a configured database connection. Standard operations do not require hand-written Java entity classes or an ORM mapping.

#### Multiple relational databases

The project lists PostgreSQL, MySQL/MariaDB, SQLite, SQL Server, Oracle, IBM Db2, and further compatible or managed SQL offerings. Some cloud entries represent hosting variants of existing SQL dialects, not independent database engines.

#### One instance, multiple databases

Several database connections can be managed in one DB2Rest process, with endpoints identifying the respective database. This differs from SASD's initial model of independent deployments with separate configuration.

#### SQL dialect abstraction

Dialect modules accommodate vendor-specific SQL differences under a broadly consistent REST API. This is an especially instructive pattern for future SASD storage adapters.

#### Schema overview as REST endpoint

A schema resource can return tables, views, and optional column details including types and key metadata. This can aid administrators, diagnostics, and metadata-driven clients.

#### Restrict included schemas

INCLUDED_SCHEMAS selects which database schemas are discovered and exposed. Narrowing visibility also reduces metadata overhead and the available attack surface.

#### Views and materialized views

The documentation describes reading regular and materialized views when the target database supports them. Views can offer stable, purpose-built API projections.

#### Java process/JAR and Docker

DB2Rest runs as a Java application or container. Unlike a PHP CRUD script, it requires a supported application runtime and is not suited to PHP-only shared hosting.

#### SQL pooling and transaction infrastructure

The project describes connection pooling and transaction management as built-in infrastructure. Specific isolation levels, pool capacities, and failure guarantees require deployment-specific tests.

### 5.2 Writing and modifying data

**Primary sources:** [B2](https://db2rest.com/docs/data-api-features) · [B4](https://db2rest.com/docs/category/insert) · [B5](https://db2rest.com/docs/category/update) · [B6](https://db2rest.com/docs/category/delete)

#### Insert one record

An HTTP request carrying record fields creates a new database row. Accepted values are constrained by data types and database integrity rules.

#### Insert several records

Bulk insert can submit multiple records in one call, reducing network overhead. Atomicity and behavior after partial failure must be verified for the exact version.

#### Bulk file import

The feature summary mentions loading records from files. Supported formats, size limits, and the actual API endpoints should be verified before planning this as a production feature.

#### Update/Patch

Write endpoints modify existing records. Their contract must define precisely how the affected rows are identified and how omitted fields are handled.

#### Filter-driven update

The documentation describes updating all rows matching a filter. Powerful but dangerous, because a malformed filter may change unexpectedly many records; SASD should not expose this early.

#### Filter-driven delete

Deletion operations can target sets of rows matching conditions. Strong authorization, selection constraints, and optional row limits are crucial.

#### Safe Delete

DB2Rest advertises a special safer-deletion approach. Without a version-specific description, one must not assume it means soft deletion, automatic backups, or undo support.

#### Write transactions

The feature summary names transactions for save, edit, and purge operations. That alone does not establish arbitrary multi-request or cross-database distributed transactions.

#### Automatic key generation

Insert documentation discusses auto-generated identifiers and vendor-specific key strategies. An eventual SASD adapter contract should separate ID generation from HTTP handling.

### 5.3 Query language and analytical functions

**Primary sources:** [B2](https://db2rest.com/docs/data-api-features) · [B7](https://db2rest.com/docs/category/query) · [B8](https://db2rest.com/docs/read/supported-rsql-operators)

#### Read records

GET endpoints retrieve table and view content. This is the central API functionality that the product makes available without writing per-table application code.

#### Single-row queries

A filter can identify one record and produce a single-object response. This differs semantically from list-returning operations.

#### RSQL filters

DB2Rest employs a URI-friendly RSQL/FIQL-inspired syntax. Declarative expressions become server-generated database queries rather than raw SQL supplied by clients.

#### Comparison operators

Documented operators include equals, not equals, greater/less than, and greater-or-equal/less-or-equal. These handle many common numeric, date, and state filters.

#### Set-membership operators

IN and NOT IN select or exclude records based on a list of values. This replaces a succession of similar individual requests.

#### Pattern matching and null checks

LIKE, NOT LIKE, starts-with/ends-with patterns, IS NULL, and IS NOT NULL are part of the documented operator family. Wildcards and text matching may influence query performance.

#### Logical AND/OR groups

RSQL supports multiple conditions and grouped expressions. Clients can build advanced filters without receiving permission to execute arbitrary SQL.

#### Column projection

Callers can request specific result columns. Back-end authorization must prevent this from revealing fields that the caller is not allowed to access.

#### Column aliases

Selected fields can be renamed in output. Especially useful when joins involve tables that have identically named columns.

#### Sorting

Results can be ordered ascending or descending by selected fields. Sort keys must be validated against known allowed metadata.

#### Limit and offset

Pagination restricts response sizes and enables navigation across datasets. Large offsets and frequently changing data can make offset-based paging inefficient or unstable.

#### Count records

Total row counts for full or filtered result sets can be calculated server-side. Clients no longer need to download every row merely to show a total.

#### Grouping and aggregation options

The feature overview mentions GROUP BY. Exact aggregations and database-specific behavior should be proven with detailed API examples and tests.

#### CSV and Excel export

Both export formats appear in the feature overview. Before adoption, confirm the real endpoints, file format, streaming behavior, and resource consumption.

#### JSON and JSONB operators

The RSQL reference includes operations on JSON/JSONB fields such as key presence and containment. Support is not uniform across all database dialects.

#### Join different tables

Structured join queries are described for INNER, LEFT, RIGHT, SELF, and CROSS joins. The join documentation explicitly states that FULL JOIN is not supported.

#### Cross-database joins

The join documentation mentions joins across configured databases. Practical implementation, restrictions, and consistency must be verified for the chosen DB2Rest version and dialects.

### 5.4 Advanced SQL and security

**Primary sources:** [B9](https://db2rest.com/docs/namedsql/introduction-to-named-queries) · [B10](https://db2rest.com/docs/remoteprocedurecall/stored-procedure) · [B11](https://db2rest.com/docs/security/basic_auth) · [B12](https://db2rest.com/docs/security/api_key)

#### Named SQL

Predefined SQL files or named queries can be exposed as REST endpoints. This is a controlled extension for more complex queries without allowing clients to send arbitrary SQL.

#### Stored procedures

Database procedures with defined parameters can be called through REST. Mature database-side transactional and business logic can remain in place while clients avoid direct SQL connections.

#### Stored functions

The documentation also lists callable database functions. Response formats and syntax differences vary between database engines.

#### HTTP Basic Auth

A configurable authentication provider protects endpoints with user credentials. Documentation mentions ENABLE_AUTH=true; authentication is disabled by default.

#### API-key authentication

The server can validate keys from an X-API-KEY header. It also needs explicit activation and selection of the appropriate authentication provider.

#### File-based authentication configuration

The documentation describes a separate authentication configuration file or file location. This separates users and API keys from core database connection settings.

#### Authorization

Project material mentions authorization and entitlements. Comprehensive per-row, per-column, per-table, or role-based access models must not be assumed without checking actual configuration examples.

#### Request-rate and row limits

The general benefits mention rate limiting and row limiting. Their exact semantics, availability, and configuration require version-specific validation.

#### Masking, encryption, and tokenization

These appear in security-related product descriptions. High-level claims do not establish a universally usable field-masking feature without verifying associated modules and configuration.

#### Tenant ID, schema, and database tenancy

The feature summary names row-level tenant IDs, separate database schemas, and separate databases. Each strategy requires independent verification of implementation and isolation safeguards.

### 5.5 Operations, diagnostics, and observability

**Primary sources:** [B13](https://db2rest.com/docs/actuator/heath-check) · [B14](https://db2rest.com/docs/observability/grafana) · [B15](https://db2rest.com/docs/category/monitoring)

#### Health check

/actuator/health reports whether the service is functioning, for example with status UP. Production checks should also verify whether required database connections are usable.

#### Metrics endpoints

Spring Actuator metrics are available for diagnosing load and failures. This helps operators identify bottlenecks and error patterns.

#### Prometheus export

With PROMETHEUS_EXPORT_ENABLED, the /actuator/prometheus endpoint can be enabled for Prometheus scraping and alerting.

#### Grafana integration

Grafana can visualize metrics collected through Prometheus. Grafana is a monitoring presentation layer, not part of the API's CRUD request processing.

#### Datadog integration

The project documents exporting telemetry to Datadog. This concerns monitoring operations rather than the public application data interface.

#### Container and VM deployment

The repository describes container images and deployments to virtual machines and clouds. A dedicated Linux host works for this runtime; it cannot offer the single-file simplicity of a PHP script on restricted hosting.

## 6. Cross-product feature matrix

Legend: **Y** = documented; **O** = optional or edition-dependent; **L** = limited or special considerations; **—** = not established as a generic standard feature by the reviewed sources. This is **not** a certification for all editions, versions, or databases.

| Feature family | PHP-CRUD-API | DreamFactory | DB2Rest | SASD consideration |
|---|:---:|:---:|:---:|---|
| REST CRUD | Y | Y | Y | V0.1 |
| JSON input/output | Y | Y | Y | V0.1 |
| Per-installation configuration | Y | Y | Y | V0.1 |
| MariaDB/MySQL | Y | Y | Y | V0.1 |
| Explicit table exposure | Y | Y | Y | V0.1 |
| Authentication | O | Y/O | O | Minimal V0.1 |
| Role/record-level permissions | Y, with rules | Y/O | L | After V0.1 |
| Pre-write validation | Y | Via scripts/logic | L | Basic V0.1 |
| Filtering | Y | Y | Y | Simple V0.1 |
| Sorting | Y | Y | Y | Simple V0.1 |
| Pagination | Y | Y | Y | Simple V0.1 |
| Field projection | Y | Y | Y | Later |
| Joins / relationships | Y | Y | Y | Later |
| Batch operations | Y for insert | Y | Y | Later |
| Concurrent multiple data sources | L | Y | Y | Later |
| Multi-tenancy | Y | O | L | Much later |
| SQL views | Database-dependent | Y | Y | Later |
| Stored procedures | — | Y/O | Y | Later |
| Named SQL | Via custom controller | Via scripts/services | Y | Later |
| User/session management | O | Y/O | L | Later |
| API keys | O | Y | O | Optional V0.1 |
| JWT / SSO | O, JWT | O, SSO | — | Later |
| OpenAPI | Y | Y | Verify separately | Later |
| Administration UI | Companion projects | Y | — | Later |
| Health / monitoring | Y, status | Y/O | Y | Health in V0.1 |
| Read database schema | Y | Y | Y | Internal, later |
| Modify database schema | O, high-risk | Y | — | Not in V0.1 |
| GIS / GeoJSON | Y | Connector-dependent | — | Only if needed |
| Files / email services | — | Y/O | — | Outside core |
| AI via MCP | — | Y/O | — | Not in V0.1 |

## 7. Lessons for SASD

### 7.1 PHP-CRUD-API — simplicity and middleware

**Lesson:** A small server with clear component boundaries can provide a great deal of functionality. Useful ideas include separate controllers and middleware, a consistent CRUD router, mapping of SQL identifiers, and pluggable backends. **Do not copy:** the unsafe assumption that missing access rules may leave all database tables exposed. SASD should expose nothing until explicitly configured.

### 7.2 DreamFactory — configuration model and API governance

**Lesson:** Data sources, API resources, users, permissions, and extensions can be managed independently. This is a helpful long-term reference architecture. **Do not copy into V0.1:** the whole administration console, connector ecosystem, SSO, AI gateway, and file/email services.

### 7.3 DB2Rest — SQL dialects, named SQL, and observability

**Lesson:** A well-defined adapter boundary allows different database engines to be supported without redesigning the public REST contract. Named queries offer a controlled extension mechanism; health and metrics are valuable operational patterns. **Do not copy:** immediate support for every database, unconstrained client queries, or mass mutations without safeguards.

## 8. Suggested direction: start small, use references later

This section is **guidance only**, not an approved implementation plan or binding roadmap.

### V0.1.0 — deliberately small

1. PHP 8.4 and MariaDB/MySQL as the initial, explicitly configured deployment target (including shared-hosting considerations).
2. HTTP routing through index.php in the repository root; no mandatory /public path or Docker process.
3. Clear layers: HTTP/Router → Controller → Service → Repository → Storage Adapter.
4. Configuration in non-public files and/or secured environment variables; no secrets in source control or logs.
5. Configurable and **explicitly allowed** resources; never publish every database table automatically.
6. GET list, GET single record, POST, PUT/PATCH according to a defined contract, and DELETE, with coherent HTTP status codes.
7. Simple filters, sorting, bounded pagination, and allowlisted fields; no caller-supplied raw SQL.
8. Parameterized SQL, basic type/value validation, and consistent JSON error responses.
9. Enforced HTTPS, minimal authentication or clearly blocked write access, and least-privilege database accounts.
10. A simple health endpoint, structured logs without tokens or passwords, and automated core CRUD/security tests.

### V0.2.x — if justified by actual use

- API-key management, finer-grained role/resource permissions, and per-column/per-row access controls.
- Better-defined filter operators, field projections, and predictable pagination.
- Configurable public aliases for tables/columns and read-only database views.
- Versioned API paths, configuration migrations, and OpenAPI documentation.

### Later, only with an identified use case

- SQLite/PostgreSQL adapters, multiple simultaneous databases, tenant isolation.
- Relationship joins, named queries, strongly limited batch operations, domain transactions.
- Extensions/hooks, operational metrics, and possibly an admin interface.
- Import/export, GIS, file storage, SSO, MCP, and integration services — **none are automatic commitments**.

## 9. Architecture risks and unanswered questions

1. **Accidental data exposure:** SASD must require an allowlist for tables, columns, and operations. A least-privilege repository/database account provides further defense.
2. **Generic CRUD is not a domain API:** Activities such as library loans and returns, or recording sensitive health information, often require atomic, domain-validated operations in dedicated services.
3. **Primary and foreign keys:** API generators differ in support for UUIDs, composite keys, views, and absent foreign-key constraints. The adapter contract must specify these issues before database support expands.
4. **Unsafe filtering:** Queries should accept only known tables, fields, and operators, and must use parameter binding. Query complexity and response sizes need explicit caps.
5. **IONOS shared-hosting constraints:** Test PATH_INFO, mod_rewrite, .htaccess handling, installed PDO drivers, error handling, filesystem permissions, and resource limits using actual HTTP requests. Do not implicitly require /public or a long-lived process.
6. **Migrations and backup/recovery:** An API server is not a substitute for database migration control, backups, tested restoration, rollback plans, or audit logs.
7. **License boundaries:** MIT, Apache-2.0, and commercial DreamFactory licensing are not interchangeable. This catalog does not authorize copying third-party code; per-component copyright, dependency, and NOTICE obligations must be reviewed.
8. **Version and edition differences:** README and marketing features may differ from installed packages, or change over time. Run a focused proof of concept before committing a feature to SASD.

## 10. Verification and maintenance procedure

For every reference feature proposed for an SASD milestone: (1) record the precise upstream version/commit, (2) verify Community vs. commercial licensing, (3) compare documentation with the relevant implementation or API reference, (4) run a reproducible demo, (5) test negative security cases, and (6) record the decision in a separate SASD architecture decision record (ADR). This catalog is a research overview; **none of the products have been installed or integration-tested** as part of this study.

## 11. Primary sources and entry points

### PHP-CRUD-API

- [P1] https://github.com/mevdschee/php-crud-api/blob/main/README.md — setup, configuration, endpoints, middleware, authentication, limitations.
- [P2] https://github.com/mevdschee/php-crud-api/blob/main/README.md#joins — relationship and join queries.
- [P3] https://github.com/mevdschee/php-crud-api/blob/main/README.md#authentication — authentication methods.

### DreamFactory

- [D1] https://github.com/dreamfactorysoftware/dreamfactory — current README and feature overview.
- [D2] https://www.dreamfactory.com/features/ — product and enterprise capabilities.
- [D3] https://github.com/dreamfactorysoftware/dreamfactory#commercial-licenses — edition/licensing note.
- [D4] https://github.com/dreamfactorysoftware/dreamfactory#supported-data-sources — data sources and services.
- [D5] https://github.com/dreamfactorysoftware/dreamfactory#mcp-server-for-ai-and-llm-integration — MCP/AI features.
- Further documentation: https://docs.dreamfactory.com/ and https://guide.dreamfactory.com/.

### DB2Rest

- [B1] https://github.com/9tigerio/db2rest — architecture, databases, and releases.
- [B2] https://db2rest.com/docs/data-api-features — feature list; some statements are high-level claims.
- [B3] https://db2rest.com/docs/multidb/connect-multiple-db — multiple connections.
- [B4] https://db2rest.com/docs/category/insert — inserts.
- [B5] https://db2rest.com/docs/category/update — updates.
- [B6] https://db2rest.com/docs/category/delete — deletes.
- [B7] https://db2rest.com/docs/category/query — reading, filtering, pagination.
- [B8] https://db2rest.com/docs/read/supported-rsql-operators — filter operators.
- [B9] https://db2rest.com/docs/namedsql/introduction-to-named-queries — named SQL.
- [B10] https://db2rest.com/docs/remoteprocedurecall/stored-procedure — stored procedures.
- [B11] https://db2rest.com/docs/security/basic_auth — Basic Auth (disabled by default).
- [B12] https://db2rest.com/docs/security/api_key — API-key authentication.
- [B13] https://db2rest.com/docs/actuator/heath-check — health checks.
- [B14] https://db2rest.com/docs/observability/grafana — Prometheus/Grafana.
- [B15] https://db2rest.com/docs/category/monitoring — monitoring.
- Additional join details: https://db2rest.com/docs/join/how-to-join-multiple-tables — supported join types and limitations.

---

**Document status:** Research on external reference projects, not a normative SASD requirement. **Maintenance note:** Revisit linked upstream sources and edition/version availability before making implementation decisions.
