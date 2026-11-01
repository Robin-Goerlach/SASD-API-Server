# SASD API Server – Testing Strategy and Quality Assurance

> **Language:** **English (default)** | [Deutsch](test-strategy.de.md)  
> **Status:** test design; no test execution claimed. **Updated:** November 1, 2026.  
> Requirements: [V0](requirements-v0.md), [V1](requirements-v1.md) · Technical specifications: [V0](technical-specification-v0.md), [V1](technical-specification-v1.md)  
> Process: [V0 development plan](development-plan-v0.md) · [Release and acceptance](release-acceptance.md) · [Roadmap](../ROADMAP.md)

## 1. Goals and scope

Tests must demonstrate **both** that approved CRUD operations behave correctly **and** that forbidden access fails. SASD API Server is intended as a potentially internet-accessible layer in front of databases. **Authorization, input validation, SQL injection prevention, deployment boundaries, and instance isolation are therefore first-class acceptance criteria.**

**V0 scope:** PHP 8.4, MariaDB 10.11/compatible MySQL, simple API contract, and two independent instances. **V1 adds:** two mandatory adapters (MariaDB and SQLite), technical-client roles, transactional write audit, rate limiting, read-only views/relations and OpenAPI. V1 SHOULD modules require their own tests **only when explicitly approved for a release**.

## 2. Testing pyramid and execution environments

| Level | What to test | Where | Purpose |
|---|---|---|---|
| Static | PHP syntax, style, absence of secrets, valid Markdown/JSON examples | Local + CI | Catch errors early |
| Unit | Router, parser, ConfigLoader, key/policy validator, QueryPlan | No real database | Deterministic business-independent rules |
| Integration | PDO adapters, SQL schema fixtures, constraints and transactions | Isolated test database | Verify actual persistence semantics |
| HTTP contract | Routes, methods, headers, JSON, errors and query-routing fallback | Test HTTP service/host | Stable external API |
| Security | Unauthorized resources, injection, key/TLS/secret-path failures | Local + actual hosting | Prevent exploitation |
| Deployment | Installation, private paths, URL rewriting, PHP extensions | Actual target provider | Validate host-specific assumptions |
| Isolation | Two installations with separate databases, keys and configs | Integration/hosting | No cross-instance leakage |
| Performance | Defined data volumes, page/query bounds, SQLite locking | Repeatable load profile | Ground operational limits |
| Migration/recovery | Config v0→v1, database backup/restore | Staging | Safe upgrades and recovery |

A local success **does not prove** provider-specific rewrite, TLS proxy behavior, file-lock semantics or secret-file isolation. These require actual hosting checks.

## 3. Test fixtures, environments and data safety

### 3.1 Repeatable synthetic fixtures

Prepare **nonproduction** database fixtures such as `books` and `authors` with:

- A primary key, foreign key, unique constraint and nullable/required fields.
- More than 100 rows to test pagination.
- Existing, missing, authorized and forbidden records.
- UTF-8/special characters, duplicate keys, invalid types, null/default cases.
- At least two instances A and B with **different** databases and API keys.

V1 adds a logically equivalent **SQLite** fixture. Different physical SQL definitions may be required, but the promised HTTP/JSON semantics remain identical. Never put production or patient data into fixtures, CI artifacts or public issues.

### 3.2 Execution environments

- **Unit:** no network or database required; use small fakes.
- **MariaDB integration:** independent SQL user and dedicated disposable test schema with documented reset process.
- **HTTP contract:** test clean routes and `index.php?route=...`; explicitly document restricted-host fallback behavior.
- **IONOS or actual provider test:** HTTPS, direct attempts to fetch private config/log/key files, real PDO access, routing.
- **V1 SQLite:** database file outside the document root, foreign keys enabled, timeout/locking/concurrency cases.
- **Staging:** a **test** environment for migration and restore, never a live production database.

## 4. Test case and evidence conventions

Use the technical-specification identifiers `T-V0-xx` and `T-V1-xx` for implementation tests; acceptance identifiers are `AT-V0-01` through `AT-V0-10` and `AT-V1-01` through `AT-V1-16`.

Every test case should record:

1. Initial state, fixture, environment and source commit.
2. HTTP method, sanitized URL/body/headers or direct program input.
3. Expected HTTP status, schema, headers and persistent database result.
4. At least one meaningful negative case (access denied, invalid input, conflict or dependency failure).
5. Actual results, request ID and sanitized diagnostics.
6. **Passed / failed / not executed / not applicable** with reason.

**Not executed does not mean passed.** Do not fill in a green status for a test that cannot run because the actual database, hosting, credentials or code is not available.

## 5. V0 functional test matrix

| Area | Successful case | Required negative case | Requirement |
|---|---|---|---|
| GET collection | Normal list and empty list | Unknown/unapproved table does not appear | V0-F01/F02/F06 |
| GET record | Correct primary-key lookup | Missing/malformed ID; injection | V0-F02/F12 |
| POST | Single approved record, 201/Location | Missing required value, forbidden field | V0-F03/F09/F12 |
| PUT | Complete writable representation replaced | Incomplete representation or readonly ID | V0-F04/F12 |
| PATCH | Only submitted fields changed | Empty object or forbidden field | V0-F04/F12 |
| DELETE | Exactly one approved row removed | Prohibited method and FK conflict | V0-F05/F06 |
| Filters | Equality and AND over approved columns | SQL injection, hidden field | V0-F07/F12 |
| Pagination | Limit, offset, stable ordering | Negative or excessive values | V0-F08 |
| Authentication | Valid technical key for permitted route | Missing, invalid, other-instance or revoked key | V0-F11 |
| HTTP/JSON | Defined contract and errors | Invalid JSON, content type, method | V0-F09 |
| Hosting | Clean URL and query fallback | Direct public URL for secret file must fail | V0-F10/F13 |
| Operations | Health and sanitized logs | Database offline; no secret or SQL leakage | V0-F14/F15 |
| Documentation | Second deployment reproducible | Undocumented mandatory step | V0-F16/F17 |

### 5.1 Minimum V0 acceptance package

**All ten AT-V0-01–AT-V0-10 scenarios** must be recorded as passed (see requirements specification, acceptance section). That includes successful CRUD, negative security tests, a real host installation, two-instance isolation and repeatable documentation. Unit tests alone cannot fulfill those gates.

## 6. V1 functional and security extensions

| Component | Required positive tests | Required negative tests |
|---|---|---|
| SQLite adapter | Same common CRUD/HTTP contract as MariaDB | Missing FK enforcement, SQLite busy, unsafe file path |
| Field aliases and views | Independent external field names, read-only views | Hidden columns and attempted view writes |
| QueryPlan and filters | eq/ne/lt/lte/gt/gte/in/isNull/contains/startsWith, bounded AND/OR | Unknown operator and OR/sort/IN limits |
| Relations | One allowed expansion level | Hidden fields, unknown relation, too-deep expansion |
| Key lifecycle | Multiple technical identities, revocation, rotation | Disabled key and forged role |
| RBAC | Reader, editor and operator policies | Reader write; unauthorized projection, filter or count |
| Write audit | Data and audit commit together | Audit persistence error rolls back data |
| Rate limiting | Documented 429/Retry-After behavior | Missing state store fails closed; spoofed IP cannot bypass |
| Health/readiness | Operator-protected database checks | Public status contains no internal data |
| OpenAPI | Schema matches authorized actual routes | Hidden resources absent from role-specific schema |
| V0→V1 config | Validated migration and staging rollback | Unknown roles/config fields never broaden rights |
| Clients | curl, Java and .NET agree on API contract | No PHP-specific session assumptions |

**V1 SHOULD modules:** named SQL, row scope, optimistic lock, extension registry and metrics each receive tests **only if implemented and explicitly released**; deferred features must not be advertised as available.

## 7. Security testing catalog

1. SQL injection in ID, filter value, sort/field name, request body and route parameter.
2. Database-resource enumeration through invalid fields, OpenAPI, errors or sort parameters.
3. Authorization bypass through PUT/PATCH, expansion, count and views.
4. API-key leakage in errors, logs, redirects, CI artifacts or examples.
5. Plain HTTP access, spoofed forwarding headers, direct HTTP access to config/keys/logs.
6. Double-encoded paths, duplicate query parameters, invalid methods and path traversal.
7. Oversized/nested JSON, extreme pagination/IN lists, unusual UTF-8 or special characters.
8. Database, audit and rate-store failures: no fail-open or false successful response.
9. Cross-instance key/data mix-ups.
10. V1 concurrent writers, SQLite busy/timeout, optional version checks.
11. Unauthorized browser CORS origins — **CORS is not authentication**.

**A critical issue permitting unauthorized data access or secret disclosure blocks release** until fixed and negatively retested.

## 8. Performance and resource tests

Do not invent a generic millisecond SLO. For each candidate release report the actual hardware/host, PHP/DB versions, data volume, number of requests, concurrency, latency distribution, memory and failure rate.

Relevant profiles include single GET/POST, initial/large-offset pages, 100-plus-record lists and equality filters. V1 adds relation reads and a controlled test of multiple simultaneous SQLite writers. Use results to justify body limits, paging defaults, filter budgets and rate-limiting thresholds.

## 9. CI design, not an unsupported success claim

**No working CI workflow is asserted by this document.** During implementation, configure appropriate GitHub Actions for PHP lint, unit tests, static/security checks, MariaDB integration and HTTP contract verification. V1 adds SQLite adapter parity and migration tests.

CI should use synthetic data, test secrets, minimal artifacts and no production access. Hosting-specific manual checks remain **additional** release gates. Failure output should identify the test and sanitized error, not the API key.

## 10. Exit criteria

**V0:** V0-F01–F17, AT-V0-01–10, SQL/API negative tests, isolated second instance, shared-host deployment and tested restore.  
**V1:** accepted V0 plus 22 mandatory V1 functions, AT-V1-01–16, two adapters, audit rollback, rate fail-closed, OpenAPI and migration evidence. Optional SHOULD features block a release **only if explicitly included**.

**Document status:** test strategy only; no actual test run, CI success or release is claimed.
