# SASD API Server – Documentation

> **Language:** **English (default)** | [Deutsch](README.de.md)  
> **Updated:** November 1, 2026 · **Status:** planning and architecture; no implemented or approved API release is claimed.  
> [Repository](../README.md) · [Roadmap](../ROADMAP.md) · [Changelog](../CHANGELOG.md)

## 1. Reading order and documentation model

This repository distinguishes between **what** the product should do, **how** it should be implemented, **why** the architecture is organized as it is, **when** development is planned, and **how successful implementation will be demonstrated**.

~~~mermaid
flowchart LR
    R["Requirements<br/>What?"] --> S["Technical specifications<br/>How?"]
    S --> A["Architecture<br/>Why these components?"]
    A --> Road["Roadmap and development plan"]
    Road --> Tests["Tests and acceptance"]
    A --> ADR["Architecture decisions"]
    Tests --> Ops["Operations and recovery"]
~~~

The **requirements specification** defines user-oriented scope. The **technical specification** defines implementation requirements and API contracts. The **architecture** describes layers, components, data flows, trust boundaries and trade-offs. The **roadmap and development plan** schedule small deliverables; the **test strategy and acceptance documentation** define how to prove they are really complete.

**V0** is the first intentionally small PHP/MariaDB implementation. **V1** is the later expansion with SQLite, technical-client roles, OpenAPI, transactional audit, bounded relations and other reviewed capabilities. Documentation for V1 is **not evidence that V1 has started**.

## 2. Requirements documents

| Document | Language | Purpose |
|---|---|---|
| [Requirements V0](requirements-v0.en.md) | **English** | 17 mandatory functional requirements and 10 acceptance scenarios for the first version |
| [Lastenheft V0](lastenheft-v0.md) | German | German equivalent; same V0-F requirement IDs |
| [Requirements V1](requirements-v1.en.md) | **English** | 27 requirements: 22 MUST, 5 SHOULD; 16 acceptance scenarios |
| [Lastenheft V1](lastenheft-v1.md) | German | German equivalent; same V1-F requirement IDs |

## 3. Technical specifications and software architectures

| V0 document | V1 document | Purpose |
|---|---|---|
| [Technical Specification V0 (German)](pflichtenheft-v0.md) | [Technical Specification V1 (German)](pflichtenheft-v1.md) | Implementation, API/JSON contracts, security, configuration, database behavior and test cases |
| [Architecture V0 (German)](architektur-v0.md) | [Architecture V1 (German)](architektur-v1.md) | C4/Mermaid views, components, requests, dependencies, trust boundaries, technical decisions |

**Status:** Both versions are still technical proposals. English versions will be cross-linked here as they are added; the absence of a translation must not be confused with a missing technical requirement.

## 4. Planning, testing, operations and governance

| Document | Responsibility |
|---|---|
| [Roadmap (English default)](../ROADMAP.md) · [Deutsch](../ROADMAP.de.md) | Milestones, dependencies, V0-first policy and release gates |
| [V0 implementation plan](entwicklungsplan-v0.md) | Small slices with prerequisites and acceptance checks (currently German) |
| [Test strategy](teststrategie.md) | Unit, SQL integration, HTTP contract, security and hosting tests (currently German) |
| [Deployment, backup and recovery](betrieb-und-deployment.md) | Hosting boundaries, protected configuration, operations, incident handling (currently German) |
| [Release and acceptance](release-und-abnahme.md) | Approval gates, checklists, test evidence (currently German) |
| [Risk register](risikoregister.md) | Safety, shared-hosting and data-integrity blockers (currently German) |
| [Architecture decision register](adr/README.md) · [Template](adr/0000-template.md) | How design alternatives are proposed and accepted (currently German) |
| [AGENTS.md](../AGENTS.md) | Coding-agent instructions (English default) |
| [CONTRIBUTING.md](../CONTRIBUTING.md) | Engineering and contribution guidelines |
| [SECURITY.md](../SECURITY.md) | Vulnerability reporting and mandatory security boundaries |
| [CODE_OF_CONDUCT.md](../CODE_OF_CONDUCT.md) | Community standards |
| [CHANGELOG.md](../CHANGELOG.md) | Unreleased planning updates and actual future releases |
| [LICENSE](../LICENSE) | Authoritative MIT license text |

## 5. Reference-product research

- [API Server Reference Products — Feature Catalog (English)](reference-products-feature-catalog.en.md) — PHP-CRUD-API, DreamFactory and DB2Rest: detailed functions, edition constraints, source references, and architectural implications.
- [Referenzprodukte-Funktionskatalog (Deutsch)](referenzprodukte-funktionskatalog.md) — the German companion.

The catalog is **research**, not an obligation to implement every feature from reference products.

## 6. How to begin implementation

1. Read the [roadmap](../ROADMAP.md): current planned focus is **V0-G0**, then **V0-M0**, not V1.
2. Read the [V0 requirements](requirements-v0.en.md) for the functional scope and the [technical specification](pflichtenheft-v0.md) for implementation details.
3. Review the [V0 architecture](architektur-v0.md) for components, SQL, configuration and trust boundaries.
4. Resolve the most important [hosting/security risks](risikoregister.md), especially private files **outside the document root** and trustworthy TLS enforcement.
5. Select a small task from the [V0 development plan](entwicklungsplan-v0.md).
6. Derive positive and negative cases from the [test strategy](teststrategie.md).
7. Document the implementation, actual test results, risks and any accepted [ADR](adr/README.md). Only a fully verified and approved release may be marked complete under [release and acceptance](release-und-abnahme.md).

## 7. Language, versions and maintenance

**English is the default language for repository entry points and navigation.** German versions are provided through language switches/parallel files. Both versions should retain the **same requirement IDs, technical meaning, status, safety constraints, and revision scope**. Translation must not introduce extra mandatory features or inadvertently claim a feature is implemented.

**Planned** means specified; **in progress** means code work can be substantiated; **accepted** requires real tests, reviews and approval. Headings, checkboxes and translations are not test records.

Changes to functional requirements should be made consistently across the two languages and then propagated to technical specifications, architecture, roadmap, tests and any affected ADR. Neither language becomes a formal release contract merely by being published as a draft.

**Current status:** documentation/navigation only; no implementation or acceptance is claimed.
