# SEC-INT-001 — SASD Service Boundaries and Integration Contracts

> **Language:** **English (default)** | [Deutsch](service-boundaries-and-integrations.de.md)  
> **Date:** 2026-11-24 · **Status:** architecture and requirements **proposal**, not implemented or formally accepted.  
> **Related:** [V0 requirements](requirements-v0.md), [V1 requirements](requirements-v1.md), [V0 technical specification](technical-specification-v0.md), [V1 technical specification](technical-specification-v1.md), [IP admission policy](ip-geoblocking.md), [ADR-0020](adr/0020-auth-api-logging-service-boundaries.md).  
> **Code/availability:** V0 and V1 API designs remain unimplemented; an external centralized identity service is **not a released requirement**. The existing [SASD LogSink repository](https://github.com/Robin-Goerlach/LogSink) is an experimental development implementation whose README explicitly states that its current HTTP read/write API is **unauthenticated** and stores the incoming POST body unchanged. It must not be treated as a production-ready security logging destination.

## 1. Rationale and intended architecture

The conversation identified three reusable SASD service responsibilities:

1. **Authentication and identity:** establish *who* a user or technical client is; provision credentials, sessions and (if eventually approved) signed or introspectable access tokens.
2. **Business data authorization and persistence:** establish *what that identity is allowed to do* to a specific resource, method, field, filter, relationship or record; enforce SQL/data correctness.
3. **Technical logging and security-event analysis:** record appropriately minimized operational/security events in an optionally centralized store.

**Do not combine these responsibilities simply because they all involve authentication or HTTP.** Equally, do not force V0 to depend on three running servers before the first CRUD endpoint can work.

~~~mermaid
flowchart LR
    Client["Trusted app / backend client"] -->|"Authenticate, later optional"| Auth["SASD Auth Service"]
    Auth -->|"Time-limited token, if deployed"| Client
    Client -->|"HTTPS + credential"| API["SASD API Server"]
    API -->|"Local credential check and resource policy"| Policy["Authorization Policy"]
    Policy --> DB[("Instance business DB")]
    Auth -.->|"Sanitized security events only"| Log["Hardened LogSink (optional)"]
    API -.->|"Sanitized technical events only"| Log
    API -->|"Mandatory write-audit in SAME DB transaction (V1)"| DB
~~~

**Physical deployment:** services may run at different URLs on one hosting platform or on separate machines; **logical service separation does not require three physical hosts**.

## 2. Ownership and explicit boundaries

| Responsibility | Auth Service (future optional) | API Server (V0/V1) | LogSink (optional after hardening) |
|---|---|---|---|
| Passwords, identity verification, user sessions, login/logout, refresh/recovery | **Owns** if commissioned | No interactive login in V0/V1 MUST scope | Never owns |
| Technical service credentials | Could issue future tokens | **Owns V0 API key and V1 client keys/roles** under existing requirements | Owns its **own**, separate producer/reader credentials |
| Token issuance, issuer metadata, refresh and revocation | Owns when separate token service is approved | Validates tokens locally or by a controlled verifier port **only if enabled** | Does not issue data API tokens |
| Business resource/method/field/relation/record permissions | Does **not** decide SQL access | **Always owns** resource authorization | No authority over application data |
| SQL execution, data integrity, local transactions | No | **Always owns** | No |
| Transactional audit of successful writes (V1) | Not for API business data | **Mandatory same-database transaction** | Receives optional *copy*, never authoritative transaction participant |
| Debug/operational logs | Local protected and optionally forwarded | Local protected and optionally forwarded | Optional aggregation/search after hardening |
| Security events (invalid login/key, IP denial, forbidden operations) | Emits relevant events | Emits relevant events | Aggregates sanitized approved events if authorized |
| IP/CIDR/Geo policy on public ingress | **Own local ingress protection** if separately deployed | **Own local ingress protection** when enabled | Own ingress policy and service authentication if exposed |
| OpenAPI or operator diagnostics | Documents own interfaces | Publishes only authorized API data model | Documents and protects own log-read endpoints |

**Security invariant:** an authenticated person is not automatically entitled to see all resources. The data API's allowlists and, in V1, roles remain authoritative. Neither a valid token nor a permitted company IP is a blanket data grant.

## 3. Versions and requirement allocation

### V0.1.0 — explicitly independent

- Keep the already specified **installation-specific technical API key**, protected configuration, HTTPS, resource allowlist and small `ApiKeyAuthenticator`. The client is a **trusted technical caller or trusted backend intermediary**, not an anonymous browser user with an embedded permanent secret.
- A **small authentication-verifier boundary** is desirable (`CredentialVerifier` or similarly narrow port). It can wrap the local V0 key without implementing OAuth/OIDC/JWT or an external network dependency.
- Technical logging remains **private local logging** with request IDs and redacted fields. No requirement to call LogSink for every request.
- An early [IP access policy](ip-geoblocking.md), when formally approved, checks trusted peer/proxy IP before authentication; company allowlist/denylist policies stay local to the instance.
- No server-to-server login call, mandatory central user database, untested remote LogSink or forced additional infrastructure in the V0 acceptance path.

### V1.0 — technical identities, roles and local audit

- Fulfill **V1-F12/F13** with protected per-instance technical-key identities, rotation/revocation and resource/field/method roles. This does **not** make an external Auth Service a mandatory V1 component.
- Fulfill **V1-F19** with *two distinct guarantees*: (a) transactionally persisted **audit for successful data changes** in the **same application database** and transaction, and (b) sanitized security events for **rejected/denied** requests where no business transaction exists. Define their storage and delivery obligations separately.
- LogSink integration is **an optional follow-up** only after producer authentication, separate read/write authorization, TLS, safe event schema, input/body limits, retention controls and negative tests exist in the logging service. Do not claim the current raw-message POST/GET API satisfies these protections.
- Rate limiting must use reliable persistent state as the V1 specification requires; LogSink connectivity or a central Auth Service must not become a hidden dependency.

### Later, separately approved identity integration

If SASD applications need **shared human accounts and SSO**, initiate separate identity-service requirements, threat modeling, deployment and acceptance. An OAuth2/OIDC-based flow may be considered; prefer established, correctly implemented standards and components to a proprietary password/session/token protocol.

The API may then accept a separately configured identity mechanism through a verifier interface. **It must still enforce its own local resource policies.** A centralized Auth Service should not be contacted for every field or SQL permission check.

**New scope identifier:** `SEC-INT-001` is a cross-cutting proposal. It **does not increase** the existing 17 V0-F or 27 V1-F counts, nor redefine an existing release's acceptance gate without a deliberate decision.

## 4. Authentication-verifier contract (design, not code)

~~~text
CredentialVerifier.verify(credential, requestContext) -> VerifiedPrincipal | AuthenticationFailure

VerifiedPrincipal:
  issuer             # verified authority / configured local key source
  subject            # stable opaque technical or human identity reference
  kind               # technical_client | human_user
  audience           # validated target API/instance
  scopes_or_roles     # untrusted for resource access until mapped to local policy
  expiresAt?          # mandatory for time-limited external tokens
  authenticationMethod
~~~

**V0** can supply a local key verifier producing a technical principal. **V1** adds revocable technical keys and local roles. **External token verification is future scope, not silently promised by this interface.**

If approved later:

- **Asymmetrically signed tokens:** verify the signature with operator-trusted public keys and a strict algorithm allowlist; verify issuer, audience, signature, `exp`, `nbf`, instance/application binding and allowed skew. Never accept `alg=none`, unverifiable keys, untrusted remote key URLs or token claims as authorization to arbitrary SQL resources. Bound token lifetime and define key/JWKS caching and retirement.
- **Opaque tokens with introspection:** explicitly define secure service-to-service authentication, response caching, revocation timeliness, timeouts and **failure closed** behavior when the verifier cannot reach the authority; no automatic acceptance on timeout.
- **Browser/desktop clients:** a widely distributed binary or public JavaScript cannot hide a static high-privilege server API key; use a trusted backend or appropriate user authentication architecture.
- **No automatic admin mapping:** a successfully verified central principal must be mapped into a **local instance-specific policy**, with unknown principals/roles denied by default.
- **No silent credentials upgrade:** a V0 installation key must not become an SSO administrator credential during migration.

## 5. Three different kinds of logging — deliberately not interchangeable

| Kind | Example | Durability/availability | Intended destination |
|---|---|---|---|
| **Technical operational event** | Request ID, method, response status, duration, safe error class | Protected local log; noncritical central delivery is best-effort | Local JSONL, **later** hardened LogSink |
| **Security event** | Failed key, rejected IP, denied resource, token verification failure | Minimized and protected evidence with defined retention; define fail/availability policy per deployment | Protected local security log; **later** hardened LogSink |
| **Mandatory business write audit (V1)** | Authorized client identity, operation, instance, resource ID and UTC timestamp of a committed write | **Atomic with the business data change**; audit insertion failure rolls back the write | Internal `sasd_api_audit_events` in **the same instance database** |

**Important apparent mismatch to resolve:** The V1 requirements' acceptance text refers to successful and denied writes and “audit events,” while the implementation spec limits **transactional** audit to successful writes. A denied write has **no committed business mutation to join to a DB transaction**. Document it as a **separate protected denial/security event**, with testable event category and correlation ID, unless the operator explicitly requires a stronger rejected-attempt audit store. Do not accidentally treat raw LogSink POST as the required local audit.

### 5.1 Proposed sanitized event envelope

~~~json
{
  "schemaVersion": 1,
  "eventId": "test-event-0001",
  "occurredAtUtc": "2026-11-24T11:00:00Z",
  "sourceService": "sasd-api-server",
  "instanceId": "test-instance",
  "requestId": "test-request-0001",
  "category": "security",
  "action": "ip_access_denied",
  "outcome": "denied",
  "actor": {"type": "anonymous", "id": null},
  "resource": {"name": null, "id": null},
  "reasonCode": "ip_policy_denied",
  "policyRuleId": "RULE-TEST-01"
}
~~~

Illustrative schema only. Define validation, maximum event/body size, schema version, UTC, provenance, deduplication/event ID, bounded retries and consistent privacy controls before integrating with an actual sink. Do **not** include password, API key, `Authorization` or cookie headers, bearer token, raw SQL, SQL bound values, complete request bodies, sensitive record data or an unrestricted client IP address by default.

### 5.2 LogSink hardening gates

The current LogSink README documents `POST /api/logs` and `GET /api/logs`, **unrestricted access** and a `raw_message` persisted as the incoming payload. Before any sensitive production use:

1. Require HTTPS, producer authentication and authorization. Reader and writer privileges **must differ**.
2. Validate an explicit structured, minimized payload; stop treating arbitrary raw client messages as safe security records.
3. Apply request-size, ingestion-rate, read/query and retention limits; protect logs and backup files.
4. Prevent public listing of security events and minimize personal data and IP retention.
5. Test missing/wrong/revoked producer keys, unauthorized reads, malformed events, duplicates, log injection and failure/timeout.
6. Version the integration contract; review the alternative more protected logging service only after confirming its **actual** implementation and security guarantees.

No API Server code should assume the existing experimental LogSink is safe merely because the repository exists.

## 6. Failure and availability matrix

| Event | Correct API behavior | Notes |
|---|---|---|
| **V0 local key store unavailable** | Reject protected requests / safe 503; no data access | Do not fall back to unauthenticated mode |
| **Future central Auth Service down — locally verifiable, still-valid signed token** | May continue if **all** signature, issuer, audience, lifetime, key-cache and revocation policy checks pass | Existing tokens only; no new login; no unconditional acceptance of stale keys |
| **Future central Auth Service down — opaque introspection required** | **Fail closed** on verification; no automatic allow | Timeouts/cache expiry explicitly bounded |
| **LogSink down/slow** | Core authorized data operations continue | Local protected log; optional **bounded** spool/delivery, no mandatory worker or unlimited blocking retries |
| **Mandatory local V1 write-audit fails** | **Rollback data mutation**, return controlled failure | Cannot substitute a remote log POST |
| **Mandatory enabled IP/CIDR policy invalid/unavailable** | **Fail closed**, controlled 503 | Denied rule match yields 403; allowlisted IP still authenticates |
| **Instance DB down** | Controlled 503/500 as defined | Never return successful empty data |
| **Failed/denied authentication or authorization** | 401/403/404 per policy; sanitized local security event | Does not create a successful business write-audit entry |

Delivery semantics: the proposed central log forwarding is **best-effort** unless an explicitly approved future requirement demands durable delivery. Under PHP shared hosting, “asynchronous” does **not** imply a guaranteed background worker: a size-limited private local spool and operator-controlled or bounded opportunistic retry must be evaluated, including ordering, duplication and cleanup.

## 7. Early IP policy must apply per service ingress

- The [SEC-GEO-001](ip-geoblocking.md) policy is **network admission control**, not login and not a substitute for business authorization.
- Data API and future Auth Service must each protect **their own** publicly reachable entry points. A LogSink exposed to the network must authenticate producers/readers even on an IP allowlist.
- Corporate-only mode: `defaultAction=deny`, only validated allow CIDRs proceed, **deny rules override allow**, invalid/missing mandatory list fails closed.
- Include HTTPS, trusted reverse-proxy client IP and both IPv4/IPv6; do not blindly trust forwarded headers. Do not invent country-IP mappings or treat geo-IP as legal proof.
- Separate private installation-specific policies must not accidentally share log/event credentials, config or data.

## 8. Proposed verification matrix (not currently in release acceptance)

| ID | Planned integration/security acceptance |
|---|---|
| AT-INT-01 | V0 CRUD works without any Auth Service, LogSink, worker or external network request |
| AT-INT-02 | V0 local API key still requires independent resource/method/field authorization |
| AT-INT-03 | Future external token/principal mapped to instance-specific roles; no privilege from mere token validity |
| AT-INT-04 | Invalid token signature/issuer/audience/expiry/algorithm denied; unsigned tokens not accepted |
| AT-INT-05 | Auth outage: signed token and opaque introspection modes obey separately documented failure policies |
| AT-INT-06 | LogSink outage/slow network does not block ordinary authorized CRUD; bounded fallback prevents memory/disk exhaustion |
| AT-INT-07 | A required V1 write-audit failure **rolls back** its associated business DB change |
| AT-INT-08 | Denied/rejected writes produce sanitized, correlated **security event**, not a fictitious committed audit row |
| AT-INT-09 | Hardened LogSink rejects unauthenticated producers/readers and never ingests secrets/full bodies |
| AT-INT-10 | No secret/token/full request body passes through logging, errors or public diagnostic endpoints |
| AT-INT-11 | API/Auth/LogSink ingress and IP allow/deny policies cannot be bypassed through alternate routes/proxies |
| AT-INT-12 | Two API instances preserve key, role, DB, audit, log and IP-policy isolation |

**These are design test candidates.** V0 and V1 acceptance counts must not be silently changed. Select only tests relevant to an approved delivery scope; include all relevant negatives when an integration is enabled.

## 9. Decisions and documentation tasks

| Decision | Status | Required before |
|---|---|---|
| Keep V0 local API key + independent CRUD server | **Existing V0 requirement**, reinforced here | V0 implementation |
| Keep V1 technical client keys/RBAC local in data API | **Existing V1 requirement**, reinforced here | V1 implementation |
| Introduce independent Auth Service for shared human accounts | **Future option, undecided** | Human SSO integration |
| Choose signed self-verifiable tokens vs opaque introspection | **Undecided** | External token acceptance |
| Use LogSink for aggregated sanitized events | **Optional, blocked by current LogSink security status** | Any real sensitive event integration |
| Keep mandatory write audit in local database transaction | **Existing V1 contract; must remain** | V1 writes |
| Decide whether denied attempts need stronger separate audit durability | **Open inconsistency/decision** | V1-F19 acceptance |
| Adopt company allowlist/geo policy as a numbered V0 MUST | **SEC-GEO-001 proposed, not approved** | V0-G0 scope decision |
| Specify data-retention, authorized readers, spool and incident policy | **Operator decision** | Any production logging integration |

**Recommended next task:** review/approve [ADR-0020](adr/0020-auth-api-logging-service-boundaries.md) **as a design boundary**, without automatically implementing the future Auth Service or deploying LogSink. Resolve the V1-F19 denied-event wording and SEC-GEO-001 V0 scope separately. Update the bilingual requirement and test matrices after those decisions.
