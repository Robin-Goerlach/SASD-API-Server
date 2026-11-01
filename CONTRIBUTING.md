# Contributing to SASD API Server

> **Language:** **English (default)** | [Deutsch](CONTRIBUTING.de.md)

Thank you for considering a contribution to **SASD API Server**. This repository currently focuses on planning a small, configurable, PHP-based REST data API. The project welcomes constructive architecture discussions, documentation corrections, reproducible bug reports, tests, and carefully scoped code contributions.

> **Project status:** V0.1.0 and V1.0 are planned targets. The specifications and diagrams do **not** mean that the described server has already been implemented, tested, or released.

## 1. Start here

- [Roadmap](ROADMAP.md): current V0-first sequence and release gates.
- [Documentation index](docs/README.md): requirements, technical specifications, architecture, operations, and tests.
- [V0 requirements](docs/requirements-v0.de.md) and [V0 technical specification](docs/technical-specification-v0.md): immediate scope.
- [Architecture V0](docs/architecture-v0.md): responsibilities, boundaries, and layering.
- [Test strategy](docs/test-strategy.md) and [security policy](SECURITY.md): mandatory negative tests and responsible disclosure.
- [ADR register](docs/adr/README.md): how to propose durable architectural decisions.

The primary planning documents are in German. English requirements and reference-product catalogs are also available. Please preserve requirement identifiers across translations and updates.

## 2. What contributions are helpful?

**Especially useful before V0 code exists:** correct contradictions between documents; clarify testable acceptance criteria; verify actual PHP/MariaDB/shared-hosting constraints; propose reproducible security tests; review the resource configuration and REST contract; submit focused issues on deployment risks.

**Code contributions after architecture review:** small V0 implementation slices, security tests, neutral fixtures, readable PHP code, and relevant documentation. Larger V1 changes should not be introduced into V0 merely because the roadmap includes them.

## 3. Before starting work

1. Check for a related GitHub issue/discussion or create one identifying the problem.
2. Name the target milestone (e.g. V0-M2) and requirements (e.g. V0-F02).
3. Describe the **expected behavior**, what is **not** included, and acceptance tests.
4. If changing authentication, SQL, routing, public APIs, adapter ports, configuration formats, or secret handling, propose an [ADR](docs/adr/README.md) where appropriate.
5. Confirm the planned change does not require unavailable deployment services (Docker, Redis, persistent background worker) for the baseline shared-hosting target.

A clear small change with tests is preferred to a broad refactor that tries to implement several future releases simultaneously.

## 4. Coding and architectural guidelines

- **PHP 8.4** as the initial language/runtime; use readable PSR-oriented formatting and sensible namespaces.
- Keep HTTP parsing/response handling, application services, resource definitions, security, repositories, and infrastructure separate.
- A controller must not construct SQL or directly depend on PDO; the application service should consume a small repository contract.
- SQL **values must be parameterized**. Database identifiers must be selected from server-side, validated resource allowlists; do not accept raw SQL from HTTP parameters.
- New resources, fields, methods, relations, or roles must be **denied unless explicitly authorized**.
- Code should be well documented, especially public interfaces, unusual failure paths, transaction boundaries, security decisions, and design trade-offs. Prefer explaining *why*, not commenting every obvious statement.
- Optimize after measuring. Readability, correctness, maintainability, and security come first.
- Preserve the safe `index.php` front-controller deployment with private configuration **outside the document root**.
- Do not claim that unimplemented features exist, and do not introduce optional V1 modules without an explicit scope decision.

## 5. Testing expectations

For every behavior change, include a positive case and relevant **negative/security** tests. The [test strategy](docs/test-strategy.md) specifies unit, integration, HTTP contract, database, security, hosting, and migration checks.

Examples of essential negative cases: unknown tables, prohibited columns, missing or revoked API keys, SQL injection through sorting/filters/IDs, requests over body limits, accidental exposure of configuration files, role bypass, and transaction rollback.

Use only local/test databases and synthetic fixtures. Never run test reset/DELETE scripts on production infrastructure. When an external provider-specific condition matters (e.g. IONOS rewriting, SQLite locking), include the target environment and note that a local test alone cannot prove it.

**CI note:** A GitHub Actions workflow is not assumed to exist until one has actually been implemented and tested. Do not mark checks as passed without execution evidence.

## 6. Pull request checklist

- [ ] The problem and milestone/requirement IDs are stated.
- [ ] Scope and out-of-scope items are clear.
- [ ] Public API/JSON/configuration changes are documented.
- [ ] Relevant unit/integration/contract tests exist and were actually run.
- [ ] Security negative cases were reviewed.
- [ ] No production data, credentials, API keys, or sensitive logs were committed.
- [ ] Documentation, [ROADMAP.md](ROADMAP.md) if applicable, and [CHANGELOG.md](CHANGELOG.md) were updated.
- [ ] A durable architecture decision is documented when warranted.
- [ ] New dependencies and their licenses were reviewed.
- [ ] The change does not weaken the documented shared-hosting safety rules.

When filing an issue about a **security vulnerability**, do **not** include exploit instructions or private data in a public report. Follow [SECURITY.md](SECURITY.md) instead.

## 7. Reporting bugs and requesting features

For a bug, provide: affected commit/version, environment (PHP, OS/host, DB version), sanitized reproduction steps, expected/actual result, relevant HTTP status/request ID, and whether it affects V0 or V1 planning. Remove tokens, database passwords, personal records, and internal host paths.

For a feature, provide: application scenario, target version, why existing CRUD capabilities are insufficient, data/security implications, suggested acceptance tests, and a small implementation boundary. Requests outside the roadmap may be considered later but are not automatically scheduled.

## 8. License and attribution

The repository includes an [MIT LICENSE](LICENSE). Contributions to the project should be compatible with that license. Do not copy third-party implementations, documentation, images, or libraries into the project without verifying license and attribution/NOTICE obligations; vendor marketing claims do not confer reuse rights.

## 9. Project conduct

Discussion should remain respectful, technical, and evidence-based. Different approaches are welcome when their trade-offs are explained. For privacy and security reasons, public issues and pull requests must not contain credentials or sensitive user records.

---
**Contribution guidance is an invitation to collaborate, not a promise that every feature or pull request will be accepted.**
