# SASD API Server – Conversation-to-Repository Documentation Review

> **Language:** **English (default)** | [Deutsch](documentation-review-2026-11-24.de.md)  
> **Review date:** 2026-11-24 · **Scope:** topics discussed in the SASD API Server project conversation against the then-current repository Markdown.  
> **Status:** review of requirements, implementation designs, architecture and governance. **Not** a software implementation, code security audit, executed HTTP test, validated production environment or approval of new requirements.

## 1. Executive assessment

The project has **extensive and well-structured documentation**, with English-default and German companion files for V0/V1 requirements, technical specifications, architectures, roadmap, testing, operations, security and governance. It already describes a small PHP 8.4/MariaDB V0, a later SQLite/RBAC/audit/OpenAPI V1, private configuration outside the document root, a shared-host-compatible front controller, and independent deployments.

The conversation nevertheless exposed **three substantive missing or weakly connected topics**:

1. **Service boundaries:** a shared SASD Auth Service, the data API and the existing experimental LogSink were discussed, but lacked a combined, testable responsibility and integration/failure contract.
2. **IP admission:** country/network denylists **and** company-only allowlists had been specified in an addendum, but remained detached from the numbered V0 requirements and release gates. The policy is correctly marked as **proposed**, not an approved release requirement.
3. **Three meanings of “logging”:** V1 requirements referred to successful and denied writes as “audit,” while technical designs treated only successful writes as same-transaction DB audit. This needed a precise distinction between **committed write audit**, **denied-request security events** and **ordinary operational logging**, particularly before reusing the unprotected experimental LogSink.

The review also found **documentation-maintenance defects**: some old compatibility filenames did not contain recent additions, certain V0/V1 traceability tables contained malformed Markdown backticks, and older inserted IP-policy notes broke headings/paragraph layout.

## 2. Topic-by-topic traceability

| Conversation topic | Existing documentation / evidence | Result and required treatment |
|---|---|---|
| Universal reusable CRUD API | V0/V1 requirements/specs and architecture | **Covered**; preserve small V0 scope and explicit field/method allowlists |
| Separate application installs | V0-F10, V1-F11, deployment docs | **Covered**; same code, distinct URL/config/DB/keys/logs |
| PHP 8.4 and IONOS/shared hosting | Architecture, runbooks, roadmap | **Covered, unverified on actual host**; keep private-file/TLS provider checks blocking |
| No mandatory Docker/Redis/worker | V0/V1 specs | **Covered**; remote integration/spooling cannot silently introduce one |
| Separate Auth Service | Previously only future mentions of OIDC/SSO | **Gap addressed in SEC-INT-001 and proposed ADR-0020**; not a V0/V1 dependency |
| Authentication versus authorization | V0 API key, V1 local roles | **Clarified**: Auth validates identity, API owns data/resource/field policy |
| Machine clients versus human accounts | V0/V1 exclusions and key risk | **Covered, clarified**: static distributed key is not secure user login |
| Authentication outages and token trust | No detailed prior cross-service contract | **Design now specified**; signed vs opaque token model remains a future decision |
| Existing LogSink service | External LogSink repo; V0/V1 logging docs | **Important security blocker**: experimental service is intentionally unauthenticated and stores raw POST bodies |
| Remote logging availability | General local logging and audit | **Clarified**: optional bounded best-effort delivery does not block normal authorized CRUD |
| Transactional V1 audit | V1-F19, technical spec, architecture | **Preserved**: data mutation + required audit INSERT share one DB transaction |
| Denied-write events | V1-F19 acceptance vs implementation wording | **Clarified in both languages**: separate protected rejection/security event; exact durability still open |
| Embargo/country IP blocking | SEC-GEO-001 and ADR-0019 | **Documented as proposed**, not automatically legal sanctions compliance |
| Company-only IP allowlist | SEC-GEO-001, ADR-0019 | **Documented as proposed**: default-deny, permit explicit company/VPN networks, deny overrides allow |
| Rule files versus DB table | SEC-GEO-001 | **Covered**: private file first; database provider later, no early business-DB dependency |
| Reverse proxy and IPv6 | SEC-GEO-001 and hosting docs | **Covered in design, not provider-tested**: forwarded headers, IPv4/IPv6, mapped addresses, fail-closed |
| Admin lockout after IP-policy changes | SEC-GEO-001/ADR-0019 | **Covered in design**: validate egress and use protected out-of-band rollback |
| README, ROADMAP, AGENTS and bilingual docs | Repository root and docs index | **Present**; English default maintained |
| Screenshot of API Server | README/architecture diagrams, planned API endpoints | **Not a software requirement**; screenshots become useful only once actual HTTP/OpenAPI/diagnostic UI exists, and must be redacted |

## 3. Changes made during this review

- **Added:** [SEC-INT-001 – Service Boundaries and Integration Contracts](service-boundaries-and-integrations.md) · [Deutsch](service-boundaries-and-integrations.de.md). Includes service ownership, scoped version policy, authentication verifier proposal, token trust, LogSink hardening, three logging categories, failures, twelve integration test candidates and open decisions.
- **Added:** [ADR-0020 (proposed)](adr/0020-auth-api-logging-service-boundaries.md) · [Deutsch](adr/0020-auth-api-logging-service-boundaries.de.md). Explicit alternatives, benefits/risks and the decision not to make V0 dependent on remote Auth/LogSink.
- **Clarified:** V1-F19 in the V1 requirements and technical specifications in **both languages**; successful commits need same-DB atomic audit and denied attempts need correlated protected security events.
- **Referenced:** service boundaries from V0/V1 requirements/specs/architectures, roadmap, documentation indexes, testing, operations, release acceptance and risk tracking; no additional mandatory features asserted.
- **Tracked:** risks R-028 (authentication/token coupling) and R-029 (unsafe LogSink/audit confusion).
- **Repaired:** malformed Markdown requirement-traceability tables in the canonical technical specifications/architectures; selected corrupted geo-policy headings and outdated test-count descriptions.
- **Preserved:** SEC-GEO-001/ADR-0019 remains a **proposed** V0 extension with AT-GEO-01–18, not silently counted as V0-F18 or a completed security feature.

## 4. What is still open — prioritized decision backlog

| Priority | Decision / task | Owner/release consequence |
|---|---|---|
| **P0 before enabling real external logging** | Authenticate LogSink producers, isolate reader rights, apply TLS, minimize schema, retention, size/rate controls and negative security tests; or **do not integrate** | LogSink owner/operator; blocks sensitive production events |
| **P0 for every future production instance** | Prove private files outside document root, reliable HTTPS/proxy identity, minimal DB rights and recovery | V0-G0/hosting release gate |
| **P0 when IP blocking is required** | Formally accept SEC-GEO-001 into numbered V0 scope, confirm IPv4/IPv6/proxy behavior, permit/deny precedence, operator/legal policy, reliable feed and out-of-band rollback | Security/owner; blocks production installations requiring this policy |
| **P1 before V0 implementation architecture freeze** | Decide whether a tiny local CredentialVerifier port is useful **without** remote Auth/SSO | V0 HTTP/security architecture |
| **P1 before V1-F19 acceptance** | Define denial-event local durability, allowed content, retention and what happens if event persistence fails | V1 security/operations review |
| **P1 before V1 external integration** | Define versioned sanitized log event schema and transport/retry/dedup contract; no mandatory background worker | Event contract and LogSink integration |
| **P1 before future central Auth project** | Decide human identity/SSO scope, issuer/audience, signed vs opaque tokens, local policy mapping, key rotation, revocation, clock/cache and availability | Separate Auth requirements/ADR |
| **P1 documentation quality** | Keep legacy filename aliases consistent or replace with an explicitly maintained migration scheme; periodically verify links, requirement IDs, language parity and Markdown | Documentation/CI maintenance |
| **P2 future README visuals** | Once real code exists, show safe curl output, a diagram or generated OpenAPI example; avoid screenshots of secrets | Documentation, **not** a functional requirement |
| **P2 implementation** | Build and test code in ordered slices; the current documentation does not prove working code, CI, IONOS hosting or released V0/V1 | Release readiness |

## 5. Proposed scope register (do not silently increase release counts)

| Proposed ID | Purpose | Current release disposition | Evidence to review |
|---|---|---|---|
| **SEC-GEO-001** | IP/CIDR access policy with geo-deny, corporate-allow and combined modes | **Proposed** for V0-G0 decision / possible V0-M1 implementation; *not formally V0-F18 yet* | AT-GEO-01–18 |
| **SEC-INT-001** | Independent Auth/API/LogSink boundaries, future verifiers and safe logging/audit contracts | **Proposed cross-cutting architecture**; local API keys and V1 audit remain existing baseline | AT-INT-01–12, only where applicable |
| **ADR-0019** | File-first IP rules, operator policy, fail-closed, proxy trust and admin recovery | **Proposed**, not accepted | Geo/IP reviews, tests and provider evidence |
| **ADR-0020** | Separate identity, authorization, technical logs and committed-write audit | **Proposed**, not accepted | Security/availability review and eventual acceptance |

**Counts remain as before:** V0 **17 numbered functional requirements / 10 acceptance scenarios**; V1 **27 functional requirements (22 MUST, 5 SHOULD) / 16 acceptance scenarios**. Extra candidates are **not** green tests and do not imply released functionality.

## 6. Suggested next architecture decisions

**Decision A — V0 IP policy:** Is SEC-GEO-001 mandatory for **every** V0 production instance or only **when the instance operator explicitly enables/requires it**? These have different release-gate and default-config implications. Confirm the exact scope before renumbering requirements.

**Decision B — Auth/LogSink:** Accept the **service boundaries** in ADR-0020 without making external Auth Service or LogSink part of the V0/V1 MUST implementation. This allows independently testable APIs and avoids premature distributed-system dependencies.

**Decision C — V1 denied-event assurance:** Decide whether a protected local security log with defined retention is sufficient, or if denied writes need independently persistent, auditable storage. **Do not** pretend a remote LogSink write is part of an atomic business transaction.

**Decision D — bilingual maintenance:** Retain canonical English and German companions as the authoritative editable documents. Legacy filenames must remain either synchronized copies or controlled pointers with preserved anchors; document an automated divergence/link check before making further major changes.

## 7. Review limitations and honest status

This review used the repository documents and the project conversation. It is a **documentation consistency review**, not a line-by-line professional translation certification, an application penetration test, a legal export-control opinion, or a check of the actual hosting account. Source text and future endpoint examples may still require technical implementation review. Real approvals belong in accepted ADRs and signed-off requirements; executed tests belong in test reports.

**Next milestone stays V0-G0.** Finalize the network-policy scope and service boundaries, verify provider/security assumptions, then build V0-M0/M1 in small testable slices. Do not infer software release from the completeness of this document.
