# SASD-API-Server

**Scientific and Software Development — lightweight, configurable REST data APIs for multiple applications.**

SASD API Server is a planned open-source PHP service that will expose **explicitly approved** data resources through a consistent HTTP/JSON API. The same codebase is intended to run as **independent installations** under different URLs, each with its own database, resources, and credentials.

> **Current project status (2026-11-01): Design and documentation.**  
> The V0 and V1 requirements, technical specifications, architecture, and roadmap are documented as **drafts**. **No working V0/V1 implementation, passed acceptance tests, or published software release is claimed yet.** Do not deploy this repository as a production-ready API server solely based on its documentation.

## Why this project?

Several applications need similar basic data-access operations. Rather than recreate the same CRUD endpoints for every project, the goal is to provide a small reusable API layer with a **configurable resource contract**, strict access boundaries, and interchangeable persistence adapters over time.

~~~mermaid
flowchart LR
    A["SASD Client A"] -->|"REST/JSON"| APIA["API Instance A"]
    B["SASD Client B"] -->|"REST/JSON"| APIB["API Instance B"]
    APIA --> DB1[("MariaDB A")]
    APIB --> DB2[("MariaDB B / later SQLite")]
~~~

Each API instance has independent configuration and permissions. The server is **not** intended to replace application-specific domain services, a full Java/Jakarta application server, or a generic SQL proxy.

## Planned release scope

| Target | Intended functionality | Status |
|---|---|---|
| **V0.1.0** | PHP 8.4, MariaDB/MySQL, single-record CRUD, simple filtering/sorting/paging, explicit resource and field allowlists, API-key authentication, HTTPS, consistent JSON errors, logs, health, and independent installations. | **Planned** |
| **V1.0.0** | Stable versioned contract, MariaDB/SQLite adapters, field aliases, bounded joins and read-only views, multiple technical keys and roles, write audit, rate limiting, OpenAPI, and migration tooling. | **Planned** |
| **Later** | Additional databases, advanced identity, specialized integrations, or an admin UI only when explicitly specified. | **Not committed** |

V1 also includes five **SHOULD** capabilities, such as read-only named queries and optimistic locking, which are optional and are not automatically release blockers.

## Project roadmap and documentation

**Start here:** [ROADMAP.md](ROADMAP.md) and the [complete documentation index](docs/README.md).

| Documentation | V0 | V1 |
|---|---|---|
| Requirements (German) | [Lastenheft V0](docs/lastenheft-v0.md) | [Lastenheft V1](docs/lastenheft-v1.md) |
| Requirements (English) | [Requirements V0](docs/requirements-v0.en.md) | [Requirements V1](docs/requirements-v1.en.md) |
| Technical specification (German) | [Pflichtenheft V0](docs/pflichtenheft-v0.md) | [Pflichtenheft V1](docs/pflichtenheft-v1.md) |
| Software architecture (German) | [Architektur V0](docs/architektur-v0.md) | [Architektur V1](docs/architektur-v1.md) |

**Development and operations:** [V0 implementation plan](docs/entwicklungsplan-v0.md), [test strategy](docs/teststrategie.md), [deployment/backup/recovery](docs/betrieb-und-deployment.md), [release acceptance](docs/release-und-abnahme.md), [risk register](docs/risikoregister.md), and [ADR process](docs/adr/README.md).

**Reference products:** [German feature catalog](docs/referenzprodukte-funktionskatalog.md) and [English feature catalog](docs/reference-products-feature-catalog.en.md), comparing PHP-CRUD-API, DreamFactory, and DB2Rest. Their capabilities are research material, **not automatically SASD requirements**.

## Technical principles

- **Minimal V0:** one actively implemented MariaDB/MySQL adapter, with a small repository interface that can later support SQLite.
- **Explicit exposure:** a new table or column must not become accessible merely because it exists in the database.
- **Clear layers:** HTTP/Router → Controller → Service → Repository Port → Storage Adapter.
- **Secure deployment:** PHP shared hosting without a mandatory background process or Docker. A webroot `index.php` entry point is supported, while secrets and private files **must remain outside the publicly served document root**.
- **No public raw SQL:** clients use a validated resource API; identifiers come from server-side allowlists and SQL values are parameterized.
- **Readable engineering:** clear architecture, useful comments, reproducible tests, and negative security cases before release.

## Installing and running

**A runnable API server has not yet been released.** The installation procedures in [deployment and operations](docs/betrieb-und-deployment.md) are design/runbook drafts, not evidence of a tested build. The initial implementation work will follow the [V0 implementation plan](docs/entwicklungsplan-v0.md). Please do not assume documented future endpoints exist.

## Contributing, security, and license

- [CONTRIBUTING.md](CONTRIBUTING.md) — project scope, contribution guidelines, review and tests.
- [SECURITY.md](SECURITY.md) — how to report vulnerabilities privately and how security is designed.
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) — community expectations.
- [CHANGELOG.md](CHANGELOG.md) — documented changes and future release notes.
- [LICENSE](LICENSE) — MIT License.

For vulnerabilities or exposed credentials, do **not** post sensitive material in public issues. Follow the [security policy](SECURITY.md).

---

**Next planned milestone:** **V0-G0 – hosting and security architecture review**, followed by the first small V0 implementation slice. Progress should be updated only with evidence from code, tests, and reviews.
