# SEC-GEO-001 — IP Network and Country-Based Access Restrictions

> **Language:** **English (default)** | [Deutsch](ip-geoblocking.de.md)  
> **Status:** **Proposed V0 security requirement and architecture addendum** — documented, not implemented, tested, legally approved or deployed.  
> **Date:** November 23, 2026 · **Scope:** SASD API Server, later SASD Auth Service; integration with LogSink is optional.  
> **Decision:** [ADR-0019 (proposed)](adr/0019-ip-geoblocking.md) · **Design:** [V0 technical specification](technical-specification-v0.md) and [V0 architecture](architecture-v0.md).  
> **Requirement identifier:** **SEC-GEO-001** (new cross-cutting security requirement; formal inclusion into the numbered V0-F baseline and release totals remains an explicit scope decision).

## 1. Problem and expected outcome

The operator must be able to **reject HTTP requests whose reliably established client IP belongs to a configured blocked IP network**, including networks associated with countries subject to an operator-approved territorial access restriction. The policy must support **IPv4 and IPv6**, be reproducible, locally verifiable, and administrable **without changing PHP source code**.

The initial management mechanism should be a **protected text/configuration file**. An **optional database-backed rule source** may be introduced later behind the same policy/provider interface; a database lookup is not a prerequisite for the earliest request-security check.

**A geographic block is a risk-control measure, not a complete legal sanctions-compliance system.** EU restrictive measures may concern individuals, entities, sectors, products or specific territories and do **not** universally prohibit every website connection from an entire country. The applicable jurisdiction, restricted service, legal basis and territorial rules require **human review** before activating or expanding a block. Never infer the official “embargo countries” list automatically from country headlines.

Regulatory sources:
- [Council of the EU — Sanctions: types and purposes](https://www.consilium.europa.eu/en/policies/sanctions-different-types/)
- [European Commission — Sanctions and official EU resources](https://finance.ec.europa.eu/eu-and-world/sanctions-restrictive-measures/overview-sanctions-and-related-resources_en)

## 2. Functional requirement SEC-GEO-001

### 2.1 MUST behavior for an enabled, approved policy

1. **Early enforcement:** inspect the real client IP **before** authentication, expensive request parsing, SQL access or business endpoint processing. This policy applies consistently to data and identity endpoints where configured.
2. **CIDR networks:** support both single-host addresses (/32 IPv4, /128 IPv6) and network prefixes, including /0, with strict canonical validation.
3. **Operator configuration:** load a versioned, private, read-only blocklist file and an independent policy configuration; no public CRUD API to modify rules.
4. **Blocked match:** respond with a controlled **HTTP 403 Forbidden**, no application data, no additional resource details and no sensitive explanation. **HTTP 451** is not an automatic choice: use it only if a specific legally required restriction and response contract justify that status.
5. **Unblocked match:** continue through the **normal** authentication, rate limiting, resource authorization, validation and SQL pipeline. A nonmatching network is **not** an automatic authentication success.
6. **IPv4 + IPv6:** normalize and test IPv4, IPv6, IPv4-mapped IPv6, reverse-proxy deployment and malformed addresses to prevent address-family bypass.
7. **Trusted source:** read the peer's server-provided remote address (e.g. `REMOTE_ADDR`) or a client address established by a **verified** reverse-proxy trust chain. Never trust arbitrary `X-Forwarded-For`, `X-Real-IP`, `CF-Connecting-IP` or `Forwarded` received from the Internet.
8. **Consistent state:** reload rules only after a complete schema/network/signature-or-checksum/provenance validation; publish new policy snapshots atomically. A partially written or invalid file must not silently create an empty allow-all set.
9. **Failure behavior:** an explicitly **enforced** policy fails **closed** when its required list/snapshot is missing, unreadable, invalid or expired under its configured freshness policy. A controlled **503** may distinguish an unusable policy from a valid blocked-IP **403**.
10. **Privacy and evidence:** record only the minimum necessary sanitized security event (e.g. request ID, policy version, match rule ID/country code and outcome). Exact client IPs are personal data in many contexts; retention, purpose and access permissions require review.
11. **Testability:** deterministic unit/contract/security tests with documented IP ranges, invalid headers, forbidden clients and allowed clients. No reliance on live external geolocation during a request.

### 2.2 Operator policy choices to resolve

The operator must explicitly decide **which countries or networks are blocked and why**, for which applications and deployment environments, and whether the restriction is a regulatory necessity or an additional internal security measure. The initial configuration should default to **disabled outside approved production policy** to avoid accidentally blocking development and tests. **A production profile requiring geoblocking must enable it and provide a validated nonempty dataset before accepting protected traffic.**

A separate `monitor` mode may be useful for accuracy evaluation in controlled environments, but must **never** be described as enforcement or accepted for a legally required block.

## 3. Proposed runtime placement

~~~mermaid
flowchart TD
    Request["HTTPS request"] --> IP["TrustedClientIpResolver"]
    IP -->|Invalid / untrusted| Bad["Reject safely; no data access"]
    IP -->|Verified address| Gate["IpAccessPolicy"]
    Rules["Validated local policy snapshot<br/>IPv4 + IPv6 CIDRs"] --> Gate
    Gate -->|Blocked match| Deny["403 Forbidden<br/>Sanitized security event"]
    Gate -->|Missing / invalid required policy| Unavailable["503 Fail Closed"]
    Gate -->|No blocked match| Auth["Authentication / API Key"]
    Auth --> Policy["Resource and Field Authorization"]
    Policy --> API["CRUD Service / Database"]
    Gate -.->|"Sanitized event; not blocking"| Log["Local protected log<br/>Later optional LogSink"]
~~~

**Placement:** at the HTTP boundary, after trusted peer/proxy identification and sufficiently safe transport handling, **before** expensive processing and authentication. It is a **shared cross-cutting module**, not SQL logic in `ResourceController` or a responsibility of `RecordRepository`.

A possible port-based design:

~~~text
ClientIpResolver.resolve(ServerRequest, TrustedProxyPolicy): ValidatedIp
NetworkRuleSource.loadValidatedSnapshot(): IpPolicySnapshot
IpAccessPolicy.evaluate(ValidatedIp, IpPolicySnapshot): Allow | Deny | Unavailable
~~~

Names are illustrative; these interfaces are **not currently implemented**. An API Server can run independently of a centralized Auth Service. If a separate Auth Service is deployed, **it must enforce its own applicable policy at its own ingress**; the data API must not rely solely on another server's filtering.

### 3.1 Edge blocking and defense in depth

When a trusted **reverse proxy / WAF / firewall** can apply the same country/IP policy, blocking **before PHP** is usually more resource-efficient. The PHP policy remains a second layer where appropriate, with clear responsibility and shared rule revisions. On shared hosting where a network firewall is unavailable, enforce as early as safely possible in the front controller.

## 4. Recommended V0 storage: private file, not a database prerequisite

### 4.1 Policy settings

Example **for testing only**, not a real sanctions-country configuration:

~~~json
{
  "schemaVersion": 1,
  "ipAccess": {
    "mode": "enforce",
    "source": "file",
    "cidrFile": "PRIVATE_CONFIG_DIR/blocked-networks.tsv",
    "required": true,
    "rejectWhenUnusable": true,
    "maxSnapshotAgeHours": 168
  }
}
~~~

`PRIVATE_CONFIG_DIR` is an operator-defined **private** location outside the public document root, not a literal fixed system path. The final configuration loader must validate the path, file ownership/permissions, schema, snapshot age and file size before accepting the policy.

**Do not assume the above JSON is already supported by code.** It is the proposed configuration extension for V0 scope review.

### 4.2 Text file format

Proposed **tab-separated, UTF-8** file: one **canonical CIDR** and one **ISO 3166-1 alpha-2 country code** per line, plus optional metadata columns. Only the first two fields are needed for matching and attribution. The operator's generated **effective blocklist** contains only **networks that the operator has decided to block**.

~~~text
# blocked-networks.tsv — EXAMPLE DATA ONLY, NOT REAL GEOGRAPHY
# policy-version: test-001
# generated-at: 2026-11-23T12:00:00Z
# valid-until: 2026-11-30T12:00:00Z
# source: synthetic-test-fixture
# format: cidr<TAB>country_iso2<TAB>rule_id
198.51.100.0/24	ZZ	TEST-V4-001
2001:db8::/32	ZZ	TEST-V6-001
~~~

**Important:** `198.51.100.0/24` and `2001:db8::/32` are documentation/test networks; `ZZ` is a **test-only placeholder**, not an embargoed country. Do not populate production lists from invented ranges. A rule may also be a manually approved individual deny range, in which case the policy must define a structured non-geographic reason code rather than falsely labeling it as a sanctioned country.

For **production**, build a reviewed snapshot from a licensed, versioned and dated IP-to-country data feed and a **separately approved list of targeted territories/countries**. Validate data provenance, licensing, mapping and freshness. No unauthenticated, per-request external GeoIP HTTP lookup.

### 4.3 Country mapping versus ready-to-match CIDR list

**A country name or ISO code does not by itself tell PHP which network belongs to the country.** Two separate inputs are required:

1. **Policy:** countries/regions and/or specific IP ranges the operator intends to restrict.
2. **Data:** a reliable and regularly refreshed IP-to-country mapping, converted to a local lookup snapshot or a compiled effective blocklist.

Start with a **small static CIDR file** if manually maintained networks are sufficient. If **whole-country coverage** is required, do not perform a linear scan across hundreds of thousands of ranges on every PHP request. Prefer **a locally loaded country-level GeoIP database (e.g. a suitable MMDB reader)** or an **indexed/compiled prefix map** generated offline, subject to license and hosting measurements. The operator still owns a separate country-policy list; the local GeoIP data is not the legal policy.

A future `NetworkRuleSource` may be backed by a database table containing `cidr`, `country_code`, `reason`, `enabled`, `source_version`, `updated_at_utc` and approval metadata. The early IP gate **must not require** the business MariaDB connection merely to determine whether the request may proceed.

### 4.4 Atomic policy updates and operations

- Obtain updates from a **trusted, documented source** under its license; do not ingest unreviewed public “embargo IP lists.”
- Review applicable legal/policy changes and geo database freshness separately.
- Generate to a **new private temp file**, validate every IPv4/IPv6 prefix, duplicate/overlap, metadata and expiry, and compare new rule counts to expectations.
- Validate checksums/signatures when supported; hash alone is an integrity check, not proof of a trusted publisher.
- Atomically switch a complete policy snapshot, retaining the previous good snapshot for rollback.
- Record rule source/version/time, reviewer, diff, approval and test results.
- Reload policy with a bounded cache/snapshot strategy appropriate to PHP shared hosting. Measure memory and latency before release.
- On unexpected snapshot failure in `enforce + required` mode, **deny protected requests** rather than silently switching to empty rules.
- Explicitly define what to do if data is older than the approved freshness limit; default to fail-closed for a required enforced policy.

**No automatic cron, Docker, Redis, permanent worker or external GeoIP request is required in V0.** Controlled manual updates are acceptable until a safe automation mechanism is implemented.

## 5. Matching semantics and trust checks

- Parse IPs with PHP `inet_pton()` and enforce CIDR prefix length per address family; do not use lexical string prefix comparisons or loosely interpreted IPv4 abbreviations.
- Normalize network addresses and support /0, host routes, IPv4-mapped IPv6, and IPv6 compressed/full forms consistently.
- Consider overlapping ranges: explicit deny rules win; invalid or conflicting rule metadata should not cause accidental allowance.
- Special/reserved/internal addresses must be treated according to a **documented** development and reverse-proxy policy; never implicitly bypass blocking just because an address is `127.0.0.1`, `::1`, or a private IP.
- Treat `REMOTE_ADDR` as the immediate network peer, **not automatically the original end user** if a proxy is present.
- Only process forwarded IP headers from **explicitly configured, verified trusted proxy addresses** and only when direct public access bypassing that proxy is prevented.
- The address country may correspond to a VPN, hosting/cloud provider or proxy rather than a user's physical residence. Avoid country-based access promises that are stronger than the underlying data.

## 6. HTTP behavior and safe observability

| Case | Expected result |
|---|---|
| IP belongs to blocked prefix and policy is valid/enforced | **403**, neutral JSON error `access_restricted`, no protected data |
| IP not blocked, valid policy | Continue to the existing authentication/authorization pipeline |
| Invalid/untrusted client IP with a required enforced policy | Reject safely; no default allow |
| Missing/corrupt/stale required policy | **503**, neutral `ip_policy_unavailable`, fail-closed |
| Mode disabled in local development | No network restriction; ordinary auth and resource checks still apply |
| Test monitor mode | Event only, not enforcement; never qualifies as an effective regulatory restriction |
| Request tries to spoof forwarding headers | Spoofed address **ignored**; trusted peer/proxy logic applies |
| Other-origin request to Auth Service | Auth Service applies **its own** local ingress policy |

The endpoint should avoid returning the alleged country, matched CIDR, provider data version or sanctions explanation to an untrusted requester. A generic 403 is normally suitable. **451** may be selected by a reviewed legal/API policy if the restriction is expressly based on an applicable legal demand.

**LogSink integration:** write a small sanitized event to local protected logs; optionally forward asynchronously/best-effort to a **hardened** LogSink. A remote logging service must not become a blocking dependency. Do not send full request bodies or secret-bearing headers. LogSink's presently documented raw, unauthenticated interface is **not** appropriate for unredacted security events without hardening.

**GDPR/privacy:** IP addresses can be personal data. Document processing purpose/legal basis, access controls, retention/deletion and any data transfer to LogSink. Prefer minimal identifiers, prefix/generalized data and rule IDs where appropriate, while preserving sufficient evidence to investigate false blocks.

## 7. Acceptance and regression tests

Proposed IDs **AT-GEO-01–AT-GEO-12** (not yet part of the numbered V0 AT-V0-01–10 baseline):

| Test | Evidence / expected behavior |
|---|---|
| AT-GEO-01 | A blocked IPv4 CIDR is denied before authentication/SQL |
| AT-GEO-02 | A blocked IPv6 CIDR is denied |
| AT-GEO-03 | An unblocked IP continues into ordinary API-key/role checks |
| AT-GEO-04 | Exact network boundaries, /0, /32, /128 and overlapping prefixes |
| AT-GEO-05 | IPv4-mapped IPv6, canonicalization and malformed IPs |
| AT-GEO-06 | Arbitrary X-Forwarded-For, X-Real-IP, CF-Connecting-IP do not bypass rules |
| AT-GEO-07 | Verified trusted proxy chain; origin not publicly bypassable |
| AT-GEO-08 | Missing/corrupt/stale policy **fails closed** when enforce+required |
| AT-GEO-09 | Atomic update and rollback; no partially active policy |
| AT-GEO-10 | Two API instances apply independent policies |
| AT-GEO-11 | Denial response and security logs disclose no keys, internals or raw bodies |
| AT-GEO-12 | Whole-country dataset (if selected) satisfies measured resource bounds and update/freshness requirements |

Test the same ingress control on a separately deployed **Auth Service**, if the service becomes part of the SASD environment. Validate that blocked traffic never reaches sensitive authorization or data operations.

## 8. Risks and decisions before coding

| Risk | Consequence | Mitigation |
|---|---|---|
| Incorrect country-IP assignment | False block or unauthorized access | Use a maintained feed, approve policy, provide correction workflow |
| VPN/proxy/Tor and shared hosting networks | Geo-IP identifies exit server, not user | Treat as an additional control, not proof of identity/legal location |
| Spoofed client IP headers | Access bypass | Verified proxy chain and block direct origin bypass |
| IPv6 or mapped-address mismatch | Rule bypass | Family-complete binary CIDR tests |
| Stale feeds | Unrecognized/newly reassigned networks | Snapshot expiry and controlled updates |
| Whole-country CIDR list too large | Shared-host latency and memory exhaustion | Local indexed GeoIP/prefix lookup; measure performance |
| Rule update partially written | Temporary fail-open | Atomic validated snapshots, last-known-good rollback |
| Remote logging failure | Denied requests become slow/unavailable | Protected local log, best-effort external forwarding |
| Sanctions are not universally country bans | Overblocking and compliance errors | Jurisdiction/sector/territory review by responsible operator/legal counsel |
| IP logging and sharing | Privacy/data minimization exposure | GDPR processing assessment and retention policy |

### Decisions required at V0-G0

1. Confirm this as a mandatory V0 security capability or an explicitly approved later release scope, then add it consistently to the **numbered** V0 requirements/acceptance baseline.
2. Approve the applicable **jurisdiction and restricted services**, **not a guessed universal embargo-country list**.
3. Decide whether a manually managed CIDR deny list is adequate, or whether full country coverage requires a trusted GeoIP data source/compiled lookup.
4. Verify how the real hosting provider exposes the actual client IP and prevents direct proxy bypass.
5. Define maximum tolerated feed age, policy expiry/fail-closed behavior and a tested update/rollback procedure.
6. Confirm whether blocking applies to every API/health/auth route or to approved protected route groups; no sensitive endpoint may inadvertently bypass enforcement.

**Current decision:** file-first, database-optional, trust-aware, fail-closed when enforced is the proposed design. No real blocked countries or IP ranges are selected, and no live traffic is filtered yet.
