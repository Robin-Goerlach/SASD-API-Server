# SASD API Server – Risk Register

> **Language:** **English (default)** | [Deutsch](risk-register.de.md)  
> **Updated:** November 1, 2026 · **Status:** preliminary architecture and planning assessment, not measured probabilities.  
> Related: [Roadmap](../ROADMAP.md) · [Security](../SECURITY.md) · [V0 architecture](architecture-v0.md) · [V1 architecture](architecture-v1.md) · [Test strategy](test-strategy.md) · [ADRs](adr/README.md)

## 1. Assessment method

This register identifies security, technical, hosting, data-integrity and process risks **before** they become expensive implementation failures.

- **P0 — blocking:** production release is not permitted while the risk remains unresolved.
- **P1 — high:** a tested solution or explicit review decision is required before the affected feature's gate.
- **P2 — normal:** track and verify the risk as development progresses.

Priorities are **qualitative judgments**, not mathematically calculated likelihoods.

| Tracking status | Meaning |
|---|---|
| Open | Risk and countermeasure identified, not proven |
| Under review | Real-world test, ADR or review in progress |
| Mitigated | Measure and retest documented |
| Accepted | Residual risk explicitly reviewed and approved |
| No longer applicable | Feature removed or risk no longer relevant |

**All risks are initially open** until actual evidence says otherwise. When available, add a date, responsible party, issue/ADR and verification record.

## 2. Detailed risk register

| ID | Phase | Priority | Risk and impact | Mitigation / release gate | Status |
|---|---|---|---|---|---|
| R-001 | V0 | **P0** | Config, passwords, keys or logs may become publicly accessible, especially if .htaccess is ignored. | Physically separate private files from document root and perform direct HTTP negative tests; otherwise **no production deployment** at V0-G0. | Open |
| R-002 | V0 | **P0** | HTTPS may be falsely inferred from spoofable proxy headers, leaking API keys. | Enforce TLS in verified host configuration; identify trusted proxies; reject HTTP/forged forwarding. | Open |
| R-003 | V0 | **P0** | Accidental auto-publication of every database table. | Explicit resource, method and field allowlists; default-deny tests. | Open |
| R-004 | V0 | **P0** | SQL injection through filter, table, ID or sort parameters. | Bind all values; take SQL identifiers only from server-side validated definitions; negative tests. | Open |
| R-005 | V0 | **P0** | A static key embedded in browser or publicly distributed binary is stolen. | Trusted server-side clients or backend proxy; separate end-user identity design. | Open |
| R-006 | V0 | **P1** | mod_rewrite/PATH_INFO unavailable or unreliable, yielding 404s. | Tested `index.php?route=...` fallback and provider integration tests. | Open |
| R-007 | V0 | **P1** | Database account has broader GRANTs than necessary, increasing breach impact. | Dedicated least-privilege account; document any provider exceptions; retain API policy. | Open |
| R-008 | V0 | **P1** | Ambiguous PUT/PATCH semantics overwrite unintended fields. | Complete-writable-PUT versus partial-PATCH contract; NULL/default tests. | Open |
| R-009 | V0 | **P1** | An UPDATE or DELETE omits its unique target condition. | Mandatory primary-key predicate, SQL review, negative tests for missing WHERE clause. | Open |
| R-010 | V0 | **P1** | API keys and passwords leak in logs, exceptions, CI or error bodies. | Centralized error and log redaction; test for secret exposure. | Open |
| R-011 | V0 | **P1** | Database outage is reported as HTTP 200/empty data. | Typed adapter errors, proper 500/503 response, failure tests. | Open |
| R-012 | V0 | **P1** | Backup is missing or restoration fails. | Operator-runbook and actual restore into a separate test installation. | Open |
| R-013 | V0 | **P1** | Two installations accidentally share data or credentials. | Separate config, DB, keys and logs; cross-instance denial tests. | Open |
| R-014 | V0 | **P2** | Overly elaborate architecture delays the small first release. | One repository port, one MariaDB adapter, avoid premature SSO/connectors. | Open |
| R-015 | V0 | **P2** | Oversized requests or pages exhaust shared-hosting resources. | Body, paging and offset limits; load measurements; controlled errors. | Open |
| R-016 | V1 | **P0** | RBAC is enforced on responses but not filters, counts, joins, views or OpenAPI, allowing data exfiltration. | Apply policy before query plans and schema generation; comprehensive negative leak tests. | Open |
| R-017 | V1 | **P0** | Data write succeeds despite failed required audit event. | Store data and audit in **one local database transaction**; failure triggers rollback. | Open |
| R-018 | V1 | **P1** | SQLite file locks/WAL fail on the actual provider or network filesystem. | Real concurrency, FK, busy-timeout, journal and private-file testing; no universal WAL assumption. | Open |
| R-019 | V1 | **P1** | MariaDB/SQLite disagree about Boolean, decimal, UTC time or text comparison. | Typed normalization and shared contract fixtures. | Open |
| R-020 | V1 | **P1** | File-based rate limiter is non-atomic or unavailable. | Verify flock/database transactions, fail closed, tested 429/503 paths. | Open |
| R-021 | V1 | **P1** | Configuration migration silently enables broad resources or roles. | Schema-v2 validation, default-deny, protected operator process and migration tests. | Open |
| R-022 | V1 | **P1** | V1 silently breaks existing V0 clients. | Separate versioned routes, compatibility and migration tests. | Open |
| R-023 | V1 | **P1** | Revocation/key rotation is delayed or inconsistent across processes. | Persistent protected key store and request-level revocation tests. | Open |
| R-024 | V1 | **P2** | Relationship expansion creates N+1 queries or excessive results. | Depth, child-count and query-budget limits; profiling. | Open |
| R-025 | All | **P1** | Documentation is mistaken for running/accepted functionality. | Honest status, real implementation and acceptance records, changelog evidence. | Open |
| R-026 | All | **P2** | Unplanned additions increase scope and attack surface. | Issue explaining use case, priority, exclusions, tests and ADR when appropriate. | Open |

## 3. Blocking decisions before V0

**V0-G0 must answer:**

1. Is there a **verifiably private filesystem path outside the document root** on the actual target hosting account?
2. Can HTTPS be **reliably enforced**, and which proxy-generated metadata, if any, can be trusted?
3. Which PHP/PDO/MariaDB versions and database privileges are actually available?
4. What is the final versioned resource configuration and V0 REST/JSON contract?
5. Are all consuming applications trusted technical clients, or does any require a backend proxy/user-authentication layer?

**Do not work around these blockers** by placing a key in `index.php` or claiming CORS is authentication. If a blocker cannot be resolved, explicitly revise the deployment model or product scope before release.

## 4. Additional gate for V1

Only after V0 acceptance should V1 implementation decisions cover:

- Real SQLite support, filesystem permissions, journaling and concurrency on the intended hosting.
- Protected key storage, multiple identities and role-policy configuration.
- Internal audit schema and same-database transaction guarantees.
- Atomic rate-limiter persistence without mandatory Redis.
- Exact query grammar, data-type and text-collation semantics.
- V0-to-V1 configuration migration, data backup and rollback.

Optional row-level visibility and named SQL queries remain disabled until their specific isolation and authorization tests are passed.

## 5. Maintenance and escalation

Each implementation slice references relevant R-IDs, then updates them with actual tests, reviewers and the date. **Unmitigated P0 blocks the release.** P1 needs a tested solution or explicitly accepted residual risk; a casual “low risk” comment is not enough. Reassess priority as new evidence appears.

**Document status:** risk identification only; no mitigation or hosting test is claimed complete.
