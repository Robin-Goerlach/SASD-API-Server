# ADR-0020: Keep Authentication, API Authorization and Logging Separate

> **Language:** **English (default)** | [Deutsch](0020-auth-api-logging-service-boundaries.de.md)  
> **Status:** **Proposed**, not accepted · **Date:** 2026-11-24 · **Target:** V0/V1 boundaries and future integrations  
> **Specification:** [SEC-INT-001](../service-boundaries-and-integrations.md) · **Related:** [IP access ADR-0019](0019-ip-geoblocking.md), [V1 requirements](../requirements-v1.md).

## Context

We discussed splitting SASD API Server into **an independent authentication/identity service**, a **data API server** and a **separate logging service**. A [LogSink prototype already exists](https://github.com/Robin-Goerlach/LogSink), but its public README describes unprotected HTTP readers/writers and unchanged raw request-body storage. The V0/V1 API specifications already include **local technical keys**, **V1 role checks**, **protected local logs**, and **V1 same-transaction write audit**.

The architectural question is not merely where code runs. It is **who owns identity, who decides resource-level permissions, and whether remote service outages can block CRUD or compromise mandatory audit evidence**.

## Options considered

| Option | Advantage | Cost / risk |
|---|---|---|
| A. Put identity, authorization and logs into one API process forever | Simple V0 | Coupling and duplicated identity logic later |
| B. Require three network servers already for V0 | Centralized responsibility | Outages, latency, insecure current LogSink, violates small shared-host V0 |
| **C. Keep V0/V1 independent with small integration ports; add separate services when justified** | V0 remains releasable, service boundaries testable | Needs explicit future contracts and careful data/event mapping |
| D. Delegate **all** business permissions to central Auth | One central policy store | Resource/field/SQL context leaks, tightly coupled runtime and failure |
| E. Make remote LogSink the only write-audit store | Central reports | No atomic commit with business DB; remote outage may corrupt audit integrity |

## Proposed decision

**Propose C**, with these non-negotiable boundaries:

1. V0 uses **its own protected technical API key**, resource/method/field checks and local logs. A narrow credential verifier is a design seam, **not** an obligation to implement remote Auth/JWT/SSO.
2. V1 implements **its own technical key identities and RBAC** as currently specified. A central human-account/SSO service is **later separately commissioned scope**.
3. A future Auth Service **issues/verifies identities**, but **each API Server** controls resource, role, field, relation and optional row-level data permissions. Signed tokens can be locally verified where correctly configured; opaque introspection creates an explicit fail-closed remote dependency.
4. **LogSink** receives **only minimized authorized operational/security events** after a security hardening gate. A LogSink outage must **not stop ordinary authorized CRUD**; any local spool/retry must be bounded and cannot imply an unprovided background worker.
5. **Mandatory V1 successful-write audit stays in the same local database transaction** as the business write. Audit failure must roll back the write. Rejected attempts use separately protected **security events**, and any stronger denied-attempt durability requirement must be explicitly agreed.
6. **IP/network admission policies are per ingress**; allowlisted company IPs are not identities, and the Auth/API/LogSink services must not trust each other's unverified proxy headers or IP filtering.
7. The individual services may share a physical host but use separate credentials, private config, attack surfaces, logs and permission scopes.

## Consequences

**Advantages:** clear security ownership, no dangerous network dependency in V0, no invalid “remote log equals transaction audit” guarantee, future shared SASD login without rewriting all CRUD/data policy, and smaller attack surface for LogSink until hardened.

**Costs:** later compatibility work, identity-claim/role mapping, token key rotation, event schema/versioning, retry/deduplication, service-to-service authorization and cross-service monitoring. Local-only V0 technical keys do not magically become end-user authentication.

**Security:** no raw passwords, tokens, headers or personal record bodies in aggregated events; TLS, private storage, controlled keys and independent per-instance data policies remain mandatory.

## Validation before acceptance

- [ ] Decide whether **the proposed service boundary** is accepted without expanding V0 functional scope.
- [ ] Confirm local Auth API/key behavior in V0 and multi-key/RBAC behavior in V1.
- [ ] Resolve V1-F19 wording for **denied** versus **committed** write evidence.
- [ ] Document and test `AT-INT-01` through `AT-INT-12` **only for approved integrations**.
- [ ] Harden LogSink (producer authentication, separated reader permissions, data minimization, limits and retention) before sending sensitive production events.
- [ ] Evaluate central Auth token standard, audience, issuer, revocation, key caching and outage handling in a **future** identity project.
- [ ] Align IP admission (ADR-0019) and independent deployment paths.
- [ ] Update both languages of requirements, technical specifications, architecture, roadmap and acceptance documents **after** any scope decision.

**Status: Proposed.** This ADR is not evidence that a separate Auth Service is deployed, LogSink is hardened or any external integration is implemented.
