# Changelog – SASD API Server

> **Language:** **English (default)** | [Deutsch](CHANGELOG.de.md)

All notable project changes will be recorded here. The project intends to follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) principles and uses version identifiers inspired by Semantic Versioning. This file is not a substitute for Git history, tags, or release acceptance records.

## [Unreleased]

### Added – documentation and project planning (2026-11-01)

- Research catalog comparing PHP-CRUD-API, DreamFactory, and DB2Rest, in German and English.
- V0 and V1 requirements specifications (German and English).
- Detailed technical specifications and software architecture documents for V0 and V1 (German).
- V0-first [roadmap](ROADMAP.md), detailed V0 development plan, test strategy, deployment and recovery runbook, risk register, release/acceptance process, and ADR register.
- Contributor and security guidance, plus documentation navigation.

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
