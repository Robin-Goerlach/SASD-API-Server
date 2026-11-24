# Changelog – SASD API Server

> **Language:** **English (default)** | [Deutsch](CHANGELOG.de.md)

All notable project changes will be recorded here. The project intends to follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) principles and uses version identifiers inspired by Semantic Versioning. This file is not a substitute for Git history, tags, or release acceptance records.

## [Unreleased]

### First PHP core bootstrap and language layout — 2026-11-24

- Merged the database-adapter documentation branch without losing either Git history; resolved the duplicate **ADR-0020** identifier by preserving the Auth/LogSink decision as **ADR-0020** and importing database portability as **[proposed ADR-0021](docs/adr/0021-pluggable-database-adapters.md)**.
- Introduced separate `implementations/php`, `implementations/java`, `implementations/csharp` trees, runtime-specific `plugins/{logging,identity,storage,application}` categories and shared future `contracts/` documentation.
- Added the first **PHP 8.4** bootstrapping/HTTP-health slice: strict non-executable private plugin configuration, explicitly trusted factory registry, PHP plugin API `1.0`, minimal core UTC JSONL technical logging, error envelopes and tests. Documented in [implementation layout](docs/development-layout.md) and [proposed ADR-0022](docs/adr/0022-trusted-php-plugin-lifecycle.md).
- Verified locally with **PHP 8.4.24**, syntax lint, **45 PHP assertions**, and local HTTP 200/404/405 smoke checks. **No actual database, key/TLS enforcement, IONOS test, complete V0 functional acceptance, CI result or production release is claimed.**

### Documentation and architecture consistency review — 2026-11-24

- Reviewed the project conversation against the bilingual V0/V1 requirements, technical specifications, architecture, risk/test/deployment documents and roadmap; recorded findings in [the English review](docs/documentation-review-2026-11-24.md) and [German review](docs/documentation-review-2026-11-24.de.md).
- Added **SEC-INT-001**, the bilingual [Auth/API/LogSink integration contract](docs/service-boundaries-and-integrations.md), and **[ADR-0020 (proposed)](docs/adr/0020-auth-api-logging-service-boundaries.md)**. Preserved independent V0 operation and local V1 technical keys/RBAC; a separate Auth Service and unprotected LogSink are **not** mandatory runtime dependencies.
- Clarified **V1-F19**: a successful business write and required audit are one local database transaction; denied attempts are separate protected security events whose exact durability remains to be approved.
- Added proposed **AT-INT-01–12** tests and risks **R-028/R-029** for unsafe external identity/logging dependencies; updated specifications, architectures, roadmap, testing, operations and acceptance.
- Repaired malformed Markdown in requirement traceability tables, fixed some broken geo-IP review notes, and **synchronized 15 legacy German/English document filenames** to their current canonical language versions.
- **Status:** design and documentation changes only. SEC-INT-001 and SEC-GEO-001 remain proposed; V0/V1 release scopes and test-pass status are unchanged.



### Proposed security design — 2026-11-23

- **2026-11-24 refinement:** expanded SEC-GEO-001 / proposed ADR-0019 to support **default-deny company/VPN IP allowlists**, default-allow denylists and combined policies with explicit-deny precedence. Added proposed tests **AT-GEO-13–18**, corporate IP-change/lockout recovery, and IPv4/IPv6 cases; English/German plans and architecture updated. Still a proposed design, not running code.


- Added bilingual [SEC-GEO-001](docs/ip-geoblocking.md) / [Deutsch](docs/ip-geoblocking.de.md): early configurable IPv4/IPv6 CIDR restriction with trusted client-IP resolution, safe fail-closed policy behavior, protected file-first rules and 12 proposed tests.
- Added [ADR-0019 (proposed)](docs/adr/0019-ip-geoblocking.md) / [Deutsch](docs/adr/0019-ip-geoblocking.de.md); updated requirements, architecture, technical specification, risk/test documents, roadmap and navigation.
- **Status:** design-only, not implemented; no real countries/networks selected and no change to the existing 17 V0-F requirements or ten acceptance scenarios pending formal scope approval.


### Added – documentation and project planning (2026-11-01)

- Research catalog comparing PHP-CRUD-API, DreamFactory, and DB2Rest, in German and English.
- V0 and V1 requirements specifications (German and English).
- Detailed technical specifications and software architecture documents for V0 and V1 (German).
- V0-first [roadmap](ROADMAP.md), detailed V0 development plan, test strategy, deployment and recovery runbook, risk register, release/acceptance process, and ADR register.
- Contributor and security guidance, plus documentation navigation.

- Introduced **English-default canonical Markdown paths** with separate **German `.de.md` companions** for requirements, technical specifications, architecture, roadmap, operations, testing, risk, governance, and GitHub templates.
- Preserved legacy German and English filenames with language-navigation links for backward compatibility; provided an unofficial German translation of the MIT license while keeping `LICENSE` authoritative.

### Important status notes

- **No V0.1.0 or V1.0.0 implementation or successful acceptance is claimed here.**
- All planned features in the specifications remain requirements/design proposals until code and corresponding tests are delivered.
- No release tags, benchmarks, provider deployment checks, security audits, or passed CI runs are implied by these entries.

## Release entry template

When a release is actually approved, add a new section:

~~~markdown
## [X.Y.Z] – YYYY-MM-DD

### Added
- Feature implemented and tested (link to requirement/PR).

### Changed
- Public API, configuration, or behavior changes.

### Fixed
- Verified corrections.

### Security
- Sanitized security fix summary (no exploit or secret data).

### Compatibility and operations
- Supported PHP/DB versions, migration/rollback notes, and known limits.

### Verification
- Release tag/commit, contract/security test evidence, hosting check and restore test.
~~~

**Only create a released-version heading after a real tag, verification, and documented approval.**
