# ADR-0019: Configurable IP Admission Policy (Allowlist, Denylist and Geo-IP)

> **Language:** **English (default)** | [Deutsch](0019-ip-geoblocking.de.md)  
> **Status:** **Proposed — not accepted** · **Proposed:** 2026-11-23  
> **Target:** V0-G0 design / V0-M1 HTTP security pipeline, subject to formal scope decision  
> **Requirement:** [SEC-GEO-001](../ip-geoblocking.md) · **Risks:** spoofed IP headers, false geolocation, stale data, sanctions interpretation and availability.

## Context

The operator wants to reject network requests associated with geographically restricted countries or individually blocked IP ranges. The deployment must remain compatible with **PHP 8.4 on shared hosting**, without mandatory Redis, background workers, an extra DB connection or external geolocation HTTP requests per incoming request.

Client IP addresses can be spoofed through untrusted proxy headers; IPv4/IPv6 and VPNs complicate matching. **Sanctions are not automatically blanket network bans**, and a country's real geographical boundaries cannot be inferred reliably from an ISO country name alone.

## Decision drivers

1. **Early enforcement** before API authentication, SQL or application processing.
2. **Configurable and reviewable** country/network restrictions without PHP code changes.
3. **No dependency** on the business MariaDB or a future external Auth Service.
4. **Default deny on policy failure** when enforcement is explicitly required.
5. Both IPv4 and IPv6, safe reverse-proxy detection and testable atomic updates.
6. No live Internet GeoIP request per incoming request.
7. No inflated legal-compliance claims; operator has to review sanction jurisdiction and service scope.
8. Keep V0 small and maintainable; database-backed administration may follow later.

## Alternatives

| Option | Advantages | Drawbacks |
|---|---|---|
| **A. Protected local CIDR snapshot (recommended initially)** | Easy to audit, file-first, no DB dependency, works with shared hosting | IP mappings require managed updates; whole-country snapshots may be very large |
| **B. Business-database blocked-ranges table** | Central query/update tooling, structured history | DB needed before early admission decision; outage and SQL cost; complicates boot |
| **C. External per-request GeoIP service** | Vendor handles geo datasets | Latency, outages, personal-IP transfer, cost and third-party dependency |
| **D. Firewall/WAF/reverse-proxy rules** | Blocks earlier and uses fewer PHP resources | Not necessarily available on IONOS shared hosting; still needs policy/data governance |
| **E. No geoblocking** | Simplest design | Does not meet the proposed territorial network-control requirement |

## Proposed decision

**Propose Option A**, with an independent `ClientIpResolver`, `IpAccessPolicy` and `NetworkRuleSource`. Enforce an operator-approved private CIDR policy **before the normal API data pipeline**. Read client IP from a verified peer/trusted proxy chain, never an arbitrary forwarded header. A blocked match returns a neutral **403**. If a required enforced policy is missing, corrupt or stale, **fail closed (controlled 503)**.

Keep GeoIP datasets and legal country/territory selections **separate**. For full-country coverage use a versioned, licensed local country dataset or compiled indexed prefix snapshot, **not a linear scan of hundreds of thousands of CIDRs per request**. A future DB-backed provider may implement the same interface; the initial admission check must not depend on application persistence.

If a trusted WAF/edge can enforce this earlier, use it as an additional layer while retaining explicitly documented policy responsibility. Centralized Auth and LogSink must not be mandatory dependencies. An Auth Service deployed separately must apply its own ingress policy.

The policy shall operate in `disabled`, `monitor` or `enforce` modes, but **only enforce meets a required access restriction**. No real country/network is selected by this ADR.

### Additional requirement: allowlist-only company access (2026-11-24)

The same ingress mechanism must support **company-only allowlists** as well as denial of specific countries/networks. Introduce an explicitly configured **`defaultAction`** and two independent, validated rule sources:

| Policy | Default | Required file | Outcome |
|---|---|---|---|
| Denylist | `allow` | `blocked-networks.tsv` | Deny matching IPs; other IPs continue to authentication |
| Allowlist | **`deny`** | `allowed-networks.tsv`, nonempty | Only explicitly permitted company/VPN addresses continue to authentication |
| Combined | `allow` or `deny` | Configured allow/deny files | **Explicit deny > explicit allow > default action** |

**Decision precedence is fixed; longest-prefix match must not silently override an explicit deny.** A denied subrange inside a permitted company network remains denied. An address outside all allow rules is denied if `defaultAction=deny`. A matched allow does **not** bypass authentication, token validity or resource/field permissions.

When `defaultAction=deny`, an unexpectedly empty/missing allowlist is a **configuration error (503, fail closed)**, not a trigger to allow all. Planned policy changes must be previewed against real corporate/VPN IPv4/IPv6 egress addresses and rolled back through a **separate trusted administration channel**, not an API bypass route. Files reside outside document root and update atomically; a configured missing optional deny-file is invalid, while an unconfigured deny-file is simply omitted.

Separate ingress restrictions may be configured per instance. Auth, API, route fallback, OpenAPI and admin operations must not bypass the effective policy by using another path or server. Corporate IP addresses can change and may be shared (NAT); this is **network admission control**, never user identity.

**New proposed acceptance tests:** `AT-GEO-13` to `AT-GEO-18`: company-only allows, unknown-IP deny, allow/deny overlap precedence, missing/empty allowlist fail-closed, IPv4/IPv6/VPN/proxy behavior, and rollback/out-of-band recovery. This keeps all previous `AT-GEO-01`–`AT-GEO-12` cases intact.

**Status:** Additional design requirement only. This ADR remains **Proposed**, and incorporation into the numbered 17 V0-F MUST requirements still needs formal project scope approval.

## Consequences

**Benefits:** defense in depth, early rejection, repeatable local matching, operator-controlled rules, reusable policy component and clear failure behavior.

**Costs/risks:** updating licensed geolocation data, IP/VPN false assignments, IPv6/mapped-IP complexity, proxy trust configuration, possible false positives, and reduced availability when a required policy is unusable.

**Security:** administrative rules and snapshots must be private, validated and atomically replaced. A single valid rule match denies; a nonmatch **does not authorize** access. No bypass via alternate routes or trusted proxy assumptions.

**Privacy:** IP address handling is subject to data minimization, access control and retention review; LogSink may receive only sanitized, minimal events **after hardening**.

**Compliance:** applicable EU/German/export/sanctions requirements require separate legal assessment. IP geoblocking is **not proof** of the user's residence, citizenship or sanctions-list status.

## Verification and outstanding decisions

- [ ] Resolve V0 vs later release scope and maintain numbered requirement/acceptance counts consistently.
- [ ] Verify real IONOS client-IP/proxy/TLS behavior and whether edge blocking is possible.
- [ ] Agree legal jurisdiction, services, countries/territories and responsible approval role.
- [ ] Choose feed/source, validation, expiry, update frequency and fail-closed behavior.
- [ ] Test IPv4, IPv6, mapped addresses, CIDR boundaries, header spoofing, two-instance isolation, invalid policies and atomic rollback.
- [ ] Measure memory/latency for an entire-country policy, if needed.
- [ ] Add relevant tests `AT-GEO-01` through `AT-GEO-12`, then record actual results.
- [ ] Review and **accept/reject** this ADR before implementing a permanent public contract.

**This proposed ADR does not modify the existing 17 V0-F requirements or declare any deployed country block.**
