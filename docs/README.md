# SASD API Server – Documentation

> **Language:** **English (default)** | [Deutsch](README.de.md)  
> **Updated:** November 1, 2026 · **Status:** documentation and architectural design; no running or accepted V0/V1 implementation is claimed.  
> [Repository README](../README.md) · [Roadmap](../ROADMAP.md) · [Changelog](../CHANGELOG.md)

## 1. How to read this documentation

The documentation intentionally distinguishes:

- **Requirements:** *What* functionality must be delivered and how it will be accepted.
- **Technical specifications:** *How* the server is planned to satisfy those requirements.
- **Software architecture:** *Why* components, dependencies, data flows and security boundaries are arranged as designed.
- **Roadmap/development plans:** *In which order* work should be implemented in small reviewable stages.
- **Testing and acceptance:** *What evidence* proves behavior, security and deployment actually work.
- **Operations and recovery:** *How* instances should be protected, maintained and restored.
- **ADRs:** *Which choices* were considered, approved or replaced, with explicit trade-offs.

~~~mermaid
flowchart LR
    R["Requirements<br/>What?"] --> S["Technical specification<br/>How?"]
    S --> A["Architecture<br/>Why?"]
    A --> P["Roadmap and development slices"]
    P --> T["Test evidence and acceptance"]
    A --> ADR["Architecture decisions"]
    T --> O["Operations and recovery"]
~~~

**V0** is the small initial PHP/MariaDB release, not yet implemented. **V1** is the later expansion including SQLite, technical-client RBAC, authorized OpenAPI, controlled queries/relations and write audit. Detailed design documentation **does not mean either release already exists**.

## 2. Language convention

**English is the default for entry-point and canonical documentation paths.** German versions use a corresponding `.de.md` path and are linked near the top of each canonical document.

| Canonical rule | Example |
|---|---|
| English default | `docs/architecture-v0.md` |
| German translation | `docs/architecture-v0.de.md` |
| Root English entry | `README.md` |
| Root German entry | `README.de.md` |
| Legally authoritative license | `LICENSE` (English MIT) |
| German explanatory license | `LICENSE.de.md` (non-authoritative translation) |

Earlier German paths such as `lastenheft-v0.md`, `pflichtenheft-v0.md` and `architektur-v0.md` remain accessible as **compatibility aliases/copies** so older links continue working. For new documentation links, prefer the canonical **English default / German .de.md pair**.

The **same V0-F / V1-F, T-V0 / T-V1 and AT-V0 / AT-V1 identifiers**, technical meaning, security requirements and draft/accepted status must be maintained in both languages. Neither translation may silently add or remove mandatory product requirements.

## 3. Requirements and reference research

| Content | English (default) | Deutsch |
|---|---|---|
| Requirements V0: 17 MUST, 10 acceptance scenarios | [Requirements V0](requirements-v0.md) | [Lastenheft V0](requirements-v0.de.md) |
| Requirements V1: 22 MUST, 5 SHOULD, 16 acceptance scenarios | [Requirements V1](requirements-v1.md) | [Lastenheft V1](requirements-v1.de.md) |
| Detailed reference products: PHP-CRUD-API, DreamFactory, DB2Rest | [Feature Catalog](reference-products-feature-catalog.md) | [Funktionskatalog](reference-products-feature-catalog.de.md) |

The external reference catalog provides **ideas and comparisons**, not automatic implementation requirements.

## 4. Technical specifications and architecture

| Content | English (default) | Deutsch |
|---|---|---|
| V0 detailed technical requirements, HTTP/JSON, configuration, security and testing | [Technical Specification V0](technical-specification-v0.md) | [Pflichtenheft V0](technical-specification-v0.de.md) |
| V1 detailed technical design, two adapters, RBAC, audit, OpenAPI and migration | [Technical Specification V1](technical-specification-v1.md) | [Pflichtenheft V1](technical-specification-v1.de.md) |
| V0 static/dynamic views, component responsibilities, boundaries, ADR candidates | [Software Architecture V0](architecture-v0.md) | [Softwarearchitektur V0](architecture-v0.de.md) |
| V1 adapter, query, policy, transaction and deployment architecture | [Software Architecture V1](architecture-v1.md) | [Softwarearchitektur V1](architecture-v1.de.md) |

These are **detailed design documents** and do not claim that the classes, files, test workflows or endpoints described have been created.

## 5. Development, testing and operations

| Topic | English (default) | Deutsch |
|---|---|---|
| Release milestones and priorities | [ROADMAP.md](../ROADMAP.md) | [ROADMAP.de.md](../ROADMAP.de.md) |
| Detailed V0 implementation slices and gates | [Development Plan V0](development-plan-v0.md) | [Entwicklungsplan V0](development-plan-v0.de.md) |
| Unit, SQL, HTTP, security, hosting and recovery testing | [Test Strategy](test-strategy.md) | [Teststrategie](test-strategy.de.md) |
| Deployment, shared hosting, credentials, backup and recovery | [Deployment and Operations](deployment-operations.md) | [Betrieb und Deployment](deployment-operations.de.md) |
| Acceptance, checklist and release evidence | [Release and Acceptance](release-acceptance.md) | [Release und Abnahme](release-acceptance.de.md) |
| Hosting/security blockers and risk tracking | [Risk Register](risk-register.md) | [Risikoregister](risk-register.de.md) |
| Architecture Decision Record register | [ADR Register](adr/README.md) | [ADR-Register](adr/README.de.md) |
| ADR template | [ADR Template](adr/0000-template.md) | [ADR-Vorlage](adr/0000-template.de.md) |

## 6. Repository governance and contribution documents

| Topic | English (default) | Deutsch |
|---|---|---|
| Main project description | [README](../README.md) | [README](../README.de.md) |
| Coding-agent guidance | [AGENTS](../AGENTS.md) | [AGENTS](../AGENTS.de.md) |
| Contributions and coding standards | [CONTRIBUTING](../CONTRIBUTING.md) | [Mitwirken](../CONTRIBUTING.de.md) |
| Vulnerability reporting and security controls | [SECURITY](../SECURITY.md) | [Sicherheitsrichtlinie](../SECURITY.de.md) |
| Community conduct | [CODE_OF_CONDUCT](../CODE_OF_CONDUCT.md) | [Verhaltenskodex](../CODE_OF_CONDUCT.de.md) |
| Change tracking and release notes | [CHANGELOG](../CHANGELOG.md) | [Änderungsprotokoll](../CHANGELOG.de.md) |
| MIT license | [LICENSE (authoritative)](../LICENSE) | [LICENSE (informal translation)](../LICENSE.de.md) |
| Bug report issue template | [Bug Report](../.github/ISSUE_TEMPLATE/bug_report.md) | [Fehlerbericht](../.github/ISSUE_TEMPLATE/bug_report.de.md) |
| Feature request issue template | [Feature Request](../.github/ISSUE_TEMPLATE/feature_request.md) | [Funktionswunsch](../.github/ISSUE_TEMPLATE/feature_request.de.md) |
| Pull request template | [Pull Request](../.github/PULL_REQUEST_TEMPLATE.md) | [Pull Request](../.github/PULL_REQUEST_TEMPLATE.de.md) |

## 7. Recommended development reading order

1. Read the [Roadmap](../ROADMAP.md): the first priority is **V0-G0** (security/hosting review), then **V0-M0** (small repository skeleton).
2. Understand the functional scope in [V0 Requirements](requirements-v0.md).
3. Read [V0 Technical Specification](technical-specification-v0.md) for concrete contracts.
4. Review the [V0 Architecture](architecture-v0.md) for the structure and trust boundaries.
5. Resolve risk **R-001/R-002** (private files and TLS) with real provider checks in [Risk Register](risk-register.md).
6. Choose a small task from [Development Plan V0](development-plan-v0.md).
7. Define positive and negative tests using [Test Strategy](test-strategy.md).
8. Record architecture decisions through [ADRs](adr/README.md).
9. Update documentation and evidence; only mark a release completed after the real [Release and Acceptance](release-acceptance.md) gate.

## 8. Maintaining both languages and project status

- **English is canonical for navigation**, but German translations must preserve technical equivalence.
- Functional requirement changes must be updated in **both versions**, then reflected in technical specifications, architecture, roadmap, tests and ADRs.
- Retain stable IDs across languages; do not change semantics under translation.
- **Planned** means specified, **in progress** means development can be verified, and **accepted** means real test and approval evidence exists.
- Never report a feature as implemented, CI as passing or a provider deployment as safe merely because documentation describes it.
- Legacy filenames can remain until a deliberate link-migration strategy makes removal safe.

**Document status:** English/German navigation and design references; no software implementation or release acceptance claimed.
