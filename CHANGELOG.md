# Changelog – SASD API Server

> **Language:** **English (default)** | [Deutsch](CHANGELOG.de.md)

All notable project changes will be recorded here. The project intends to follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) principles and uses version identifiers inspired by Semantic Versioning. This file is not a substitute for Git history, tags, or release acceptance records.

## [Unreleased]

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
