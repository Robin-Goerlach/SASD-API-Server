# SASD API Server – Architecture Decision Records (ADRs)

> **Language:** **English (default)** | [Deutsch](README.de.md)  
> **Status:** This is a registry of **proposed decisions**, not a list of already approved architecture changes.  
> References: [V0 architecture](../architecture-v0.md) · [V1 architecture](../architecture-v1.md) · [Risk register](../risk-register.md) · [Roadmap](../../ROADMAP.md)

## 1. Purpose

An **Architecture Decision Record (ADR)** preserves the context, alternatives, justification, consequences and verification of an important technical decision. Technical specifications describe the system as a whole; an ADR answers a narrower long-lived question: **Why this solution rather than another?**

Do not create paperwork for every insignificant edit. An ADR is useful when a choice affects security, public API behavior, database portability, deployment, config-versioning, system boundaries or long-term maintainability.

## 2. Decision status

| Status | Meaning |
|---|---|
| **Proposed** | Suggested solution; not yet accepted |
| **Accepted** | Decision explicitly approved after review |
| **Superseded** | Replaced by an identified newer ADR |
| **Rejected** | Evaluated, deliberately not selected |
| **Withdrawn** | No longer necessary |

**Listing a candidate here does not make it accepted.** Draft architecture documentation does not constitute approval.

## 3. Candidate decisions for V0

| Proposed ID | Question | Related requirement / risk |
|---|---|---|
| ADR-0001 | PHP 8.4 and PDO without a required heavyweight framework | V0 deployment and layer boundaries |
| ADR-0002 | Explicit resource/field/method allowlists | V0-F01/F06; R-003 |
| ADR-0003 | Root index.php with private files physically outside the document root | R-001/R-006 |
| ADR-0004 | One technical API key per V0 instance, no end-user identity system | V0-F11; R-005 |
| ADR-0005 | Small repository port and one MariaDB adapter | V0-F02–F05 |
| ADR-0006 | Defined PUT replacement vs PATCH partial-update semantics | V0-F04 |
| ADR-0007 | Bounded equality/AND filters instead of raw SQL | V0-F07/F08 |
| ADR-0008 | Query-parameter routing fallback when rewrite is restricted | V0 hosting constraint |
| ADR-0009 | HTTPS and trusted reverse-proxy boundary | R-002 |

## 4. Candidate decisions for V1

| Proposed ID | Question | Related requirement / risk |
|---|---|---|
| ADR-0010 | Separate V1 HTTP contract namespace | V1-F01 |
| ADR-0011 | SQLite as the second mandatory adapter | V1-F10; R-018/R-019 |
| ADR-0012 | Typed query AST/QueryPlan before SQL compilation | V1-F05/F07 |
| ADR-0013 | Multiple technical keys and role-based access control | V1-F12/F13 |
| ADR-0014 | Write-audit record and data change in the same local DB transaction | V1-F19; R-017 |
| ADR-0015 | Persistent, atomic rate limiting without mandatory Redis | V1-F17; R-020 |
| ADR-0016 | OpenAPI generated from authorized resources | V1-F21 |
| ADR-0017 | Versioned resource configuration schema v2 and migration | V1-F23 |
| ADR-0018 | Five SHOULD modules as separate optional slices | V1-F09/F14/F16/F22/F27 |

The IDs indicate *candidates*, not existing accepted ADR files.

## 5. ADR workflow

1. Identify a specific architectural problem or long-term trade-off.
2. Copy [0000-template.md](0000-template.md) to a uniquely numbered, descriptive filename.
3. Record context, mandatory constraints, meaningful alternatives, security/operational consequences and verifiable tests.
4. Mark the ADR **Proposed** and seek a technical review against requirements, specifications and risks.
5. On explicit approval, mark **Accepted** and document the decision date and evidence.
6. Link the ADR from the relevant architecture chapter, code PR, roadmap and tests.
7. When reversing a decision, write a new ADR and mark the old one **Superseded** rather than rewriting history.

## 6. SASD review questions

- Are secrets/config/logs **physically outside the public document root**?
- Are unknown tables and fields **denied by default**?
- Does the proposed change break the V0 client contract or its hosting target?
- Does the adapter boundary support V1 without premature V0 complexity?
- Is there a negative security test and, where relevant, a real provider test?
- Can the change be safely reversed through a documented restore/recovery process?

**Current status:** The register and template exist; no individual approved ADR is claimed by this document.
