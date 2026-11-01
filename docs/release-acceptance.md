# SASD API Server – Release Process, Acceptance, and Checklists

> **Language:** **English (default)** | [Deutsch](release-acceptance.de.md)  
> **Status:** procedure proposal; no release or passed test is claimed. **Updated:** November 1, 2026.  
> References: [Roadmap](../ROADMAP.md) · [Test strategy](test-strategy.md) · [V0 development plan](development-plan-v0.md) · [V0 requirements](requirements-v0.md) · [V1 requirements](requirements-v1.md)

## 1. Why acceptance gates matter

A data API controls access to database records and credentials. A successful GET/POST demonstration is **not** a complete software acceptance. Each release needs verifiable evidence of functionality, security, data integrity, shared-hosting compatibility, backup/recovery, and review.

These procedures distinguish **development**, **release candidate**, and **genuinely accepted release**. The checkboxes below are **not checked** until corresponding tests have actually been run and reviewed.

## 2. Versioning and branches

- Software version numbers such as `0.1.0` and `1.0.0` follow Semantic Versioning principles where appropriate.
- The `main` branch is the common baseline. Changes should be small, traceable branches and PRs when feasible.
- Do not claim published releases before a real version tag and approval exist.
- Software versions and HTTP path versions such as `/api/v0/` / `/api/v1/` are **different concepts**.
- Breaking API or configuration changes require explicit versioning and a documented compatibility/deprecation policy.
- Release notes may announce only **implemented, verified** behavior — not features merely described in requirements.

## 3. Responsibilities

| Responsibility | Role | Evidence |
|---|---|---|
| Code, tests and technical documentation | Developer | Reproducible implementation change |
| Layer boundaries, security, API contract and ADR review | Technical/architecture reviewer | Review record and decisions |
| Hosting and restoration verification | Operator | Deployment and restore test report |
| Acceptance against functional requirements | Project owner | Explicit recorded sign-off |

One person may fulfill several roles. These role names support a **clear review structure**, not a claim of a large staffed team.

## 4. V0.1.0 acceptance gate

### 4.1 Functional completeness

- [ ] All **V0-F01–V0-F17** requirements fulfilled.
- [ ] All **AT-V0-01–AT-V0-10** with recorded results.
- [ ] Collection and single-record GET, create, replace, partial update and delete verified.
- [ ] Equality/AND filters, sorting, bounded limit/offset verified.
- [ ] HTTP response codes, JSON envelopes and error contract tested through real requests.
- [ ] Clean routing and `index.php?route=...` fallback shown equivalent, or the supported hosting mode explicitly documented.
- [ ] Unknown/unapproved tables, fields and methods inaccessible.

### 4.2 Security approval

- [ ] HTTPS mandatory and negative-tested on the target host.
- [ ] Resource configuration, DB credentials, API keys and logs physically **outside the document root**.
- [ ] Direct HTTP requests to secret/config/log files fail.
- [ ] Missing, invalid, foreign-instance or revoked keys cannot access data.
- [ ] SQL injection attempts through IDs, filters, sort and bodies cannot execute SQL code.
- [ ] Body and JSON limits and strict field validation apply.
- [ ] Exceptions and technical logs do not expose passwords, SQL, internal paths or stack traces.
- [ ] No secret API key embedded in publicly distributed browser code.
- [ ] Two deployments have separate credentials and data.

### 4.3 Operations, quality and recovery

- [ ] Actual PHP/PDO/DB versions and test environments recorded.
- [ ] Unit, integration, contract and security test outcomes documented.
- [ ] Database backup and **restoration into a separate test system** demonstrated.
- [ ] Health/log behavior remains controlled during an intentional DB outage.
- [ ] README, installation/operations and test examples match the released code.
- [ ] Risk register updated, with no unresolved critical/P0 issue.
- [ ] Known limitations clearly described in release notes.

**If any mandatory gate is incomplete, V0 remains a candidate or development build, not an accepted release.**

## 5. Additional V1.0 acceptance gate

An **accepted V0 baseline must remain valid**. In addition:

- [ ] All **22 mandatory V1 requirements** verified.
- [ ] All **AT-V1-01–AT-V1-16** recorded.
- [ ] Both MariaDB/MySQL and SQLite pass the **same required database/HTTP contract suite**.
- [ ] Field aliases, read-only views and bounded relations satisfy positive and negative tests.
- [ ] Multiple client keys, revocation and role-based authorization tested.
- [ ] Rate limits work and a missing/unusable persistent rate store causes a **fail-closed** response.
- [ ] Required audit event and data write are persisted **in the same local DB transaction**; audit failure rolls back the write.
- [ ] Generated OpenAPI matches the actual authorized endpoints and fields.
- [ ] V0→V1 configuration migration and restore/rollback tested in staging.
- [ ] curl, .NET and Java examples implement the same external contract.
- [ ] SQLite private-path permissions, lock handling, busy timeouts and target host environment tested.
- [ ] Each of the five **SHOULD** capabilities (`V1-F09/F14/F16/F22/F27`) is identified as implemented/tested or **explicitly deferred**.
- [ ] No unsafe HTTP DDL or arbitrary client SQL interface introduced.

## 6. From release candidate to accepted release

1. **Freeze scope:** identify commit, documentation revisions, included features, exclusions and configuration schema.
2. **Static review:** source syntax, formatting, dependencies, secrets, valid JSON examples and documentation references.
3. **Automated verification:** unit, integration, HTTP contract, security and migration tests as relevant; record results.
4. **Security review:** allowlists, query construction, key handling, TLS/proxies, instance isolation, error/log sanitization.
5. **Real hosting check:** install a fresh test instance on the intended shared host; a local PHP test server alone does not prove the hosting assumptions.
6. **Recovery test:** back up and restore a test DB into **another test instance**.
7. **Smoke tests:** authorized and forbidden reads, a write, an error, health and logs; both adapters in V1.
8. **Documentation review:** README, changelog, API contract, operator runbook, known limitations and ADRs.
9. **Acceptance decision:** identify reviewed evidence, outstanding issues and project approval.
10. **Tag and release:** only after the decision; release notes identify the real commit and test records.

## 7. Acceptance report template

~~~markdown
# SASD API Server acceptance: <Version>

- Date / reviewer:
- Git commit / release-tag candidate:
- Target hosting and URL (no secrets):
- PHP / PDO / MariaDB / SQLite versions:
- Configuration schema version:
- Test fixture identifier (synthetic data only):
- Unit tests: passed / failed / not run, evidence:
- Integration and HTTP contract tests: status, evidence:
- Security negative tests: status, evidence:
- Routing/TLS/secret-path negative tests: status, evidence:
- Two-instance isolation: status, evidence:
- Backup + restore: status, date, measured recovery time:
- V0 AT-V0-01..10 or V1 AT-V1-01..16: results per ID:
- Open bugs and risk IDs:
- Excluded features and known limitations:
- Decision: ACCEPTED / NOT ACCEPTED
- Rationale and approved exceptions:
~~~

The template intentionally contains **no completed results**. A check that was not performed must be recorded as **not run**, never silently treated as passed.

## 8. Rollback and hotfixes

A **hotfix** should contain the smallest possible reviewed correction without enabling unrelated features. Include appropriate negative regression tests and a changelog entry.

A **rollback** must consider code and configuration-schema compatibility. If database tables/audit structures were migrated, restoring an older PHP release may be insufficient. Backups and their tested restoration order are prerequisites. Never silently discard already confirmed production writes.

For a **security incident**, isolate the affected instance, revoke and rotate compromised keys, preserve protected evidence, investigate the impact, and avoid public disclosure of exploitable details. Follow [SECURITY.md](../SECURITY.md) and [operations and recovery](deployment-operations.md).

## 9. Honest project status

Only mark a gate complete after actual checks. Functional scope changes require a documented requirements change; technical specifications and tests must follow. Optional SHOULD deferrals must appear in the roadmap and release notes.

**Current status:** the acceptance process is documented; no released/approved software or passing test suite is claimed.
