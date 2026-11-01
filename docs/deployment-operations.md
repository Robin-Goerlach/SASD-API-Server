# SASD API Server – Operations, Deployment, Backup and Recovery

> **Language:** **English (default)** | [Deutsch](deployment-operations.de.md)  
> **Status:** proposed operating runbook for V0/V1, not evidence of production deployment. **Updated:** November 1, 2026.  
> References: [V0 architecture](architecture-v0.md), [V0 technical specification](technical-specification-v0.md), [V1 architecture](architecture-v1.md), [Security](../SECURITY.md), [Release and acceptance](release-acceptance.md).

## 1. Purpose and scope

This runbook describes **how a safe SASD API Server instance should be prepared, deployed, monitored, upgraded, backed up and restored**. The procedures are **designs**; provider-dependent paths and commands become operational instructions only after an actual test on the target IONOS/PHP account.

**V0:** PHP 8.4, MariaDB 10.11, one configured database per instance, API-key access and explicitly approved resources.  
**V1:** same operating principles, plus SQLite alternative, protected technical keys/roles, atomic rate limiting, transactional write audit and protected readiness.

**The operator owns application database backups, restore tests and business schema lifecycle.** Simply installing the API does not provide an automatic backup scheduler or a generic schema migration engine.

## 2. Deployment and filesystem isolation

~~~text
PRIVATE_ROOT/                         # physically outside document root
  releases/<version>/src/             # PHP source
  releases/<version>/vendor/          # optional Composer dependencies
  instances/<instance-id>/config/     # resource configuration
  instances/<instance-id>/secrets/    # DB credentials, V0 API key / V1 key store
  instances/<instance-id>/logs/       # protected sanitized logs
  instances/<instance-id>/data/       # V1 SQLite database if used
  instances/<instance-id>/backups/    # protected configuration snapshots

DOCUMENT_ROOT/
  index.php                           # public front controller
~~~

This is an **illustrative target layout**, not a verified IONOS directory tree. Private PHP files may be loaded via a trusted include path. Publicly accessible `vendor/`, `config/` or `logs/` directories are not acceptable.

**Mandatory security gate:** If the hosting account cannot guarantee that private configuration, keys and logs are physically outside publicly served directories, **do not approve production deployment**. Protection through possibly ignored `.htaccess` rules is not an equivalent security boundary.

### 2.1 Predeployment checks

| Check | Required outcome | Blocks production? |
|---|---|---|
| PHP 8.4 | Compatible PHP runtime/handler | Yes |
| PDO MySQL | Successful isolated **test** database connection | Yes |
| Mandatory HTTPS | Protected data cannot be retrieved via HTTP | Yes |
| Routing/fallback | Defined V0 routes work using supported host mode | Yes |
| Secret/config/log storage | Direct HTTP requests to private files fail | Yes |
| Database permissions | Least-privilege reviewed; documented provider exceptions | Yes |
| Error rendering | No SQL, stack traces, credentials or internal paths | Yes |
| V1 SQLite | Available driver and proven private-file/locking behavior | Yes, if using SQLite |
| V1 rate/audit persistence | Atomic, permission-protected and fail-safe | Yes |

## 3. Configuration and secret management

Each instance has private, schema-versioned JSON resources (V0 `schemaVersion=1`, V1 `schemaVersion=2`). Passwords and API keys are **not public resource definitions**; they come from private environment variables or protected secret files.

Do not place actual secrets in Git, public issues, README examples, CI logs, URLs or screenshots. Keys are generated with cryptographically secure randomness. V0 has one installation-specific technical key; V1 plans multiple identities and roles. **Hardcoding secrets into publicly distributed clients is not a safe identity strategy.**

### 3.1 Initial configuration procedure

1. Choose unique instance ID, public base URL, private storage and TLS settings.
2. Set up a separate database and minimally privileged DB account.
3. Create application tables **outside the generic API**, then allowlist required resources and fields.
4. Install credentials and keys outside the document root with restrictive file permissions.
5. Validate the configuration schema, access policy and database readiness.
6. Test both clean URLs and `index.php?route=...` fallback, if supported.
7. Explicitly request config/key/log paths by HTTP; each direct request must fail.
8. Record host name **without secrets**, PHP/PDO/DB versions, TLS/proxy model and results.

## 4. Planned safe V0 installation flow

**Prepare:** identify approved source revision, package integrity, target private paths, isolated SQL account and test instance.

**Deploy:** upload the reviewed release, dependencies and small public `index.php`; configure its trusted private bootstrap location. Place resource config and secrets separately. **Never fix an inaccessible secret by moving it under the public webroot.**

**Smoke-test:** health with no internal leakage; unauthorized requests rejected; authorized GET on an explicitly allowed test resource; forbidden table/field denied. Then test POST/PATCH/DELETE, constraints and rollback with **synthetic data**.

**Approve:** only if all mandatory V0/hosting/security gates are actually demonstrated. A second instance can use the same release commit but needs **different** private configuration, DB, key and logs.

## 5. Reverse proxies, routing and HTTP boundaries

- Preferred URL: `/api/v0/{resource}` or, later, `/api/v1/{resource}`.
- Mandatory fallback in the design: `/index.php?route=/api/v0/{resource}` when clean rewrite cannot work.
- The route is a **logical resource path**, never a local filename or directory.
- Only the intended HTTP entry may be public; private directories must not be served.
- HTTPS must be enforced at a trustworthy hosting boundary; a redirect alone does not secure a key already sent through plaintext.
- Do not trust arbitrary `Forwarded` or `X-Forwarded-Proto` client headers.
- CORS origins are explicitly allowlisted if browser use is relevant; **CORS is not authentication**.
- Generate external `Location` values from operator-approved base URLs, not untrusted Host headers.

## 6. Backup, restoration and consistency

### 6.1 What should be backed up?

| Artifact | Backup expectation | Important detail |
|---|---|---|
| MariaDB application data | **Yes**, operator responsibility | Consistent DB backup and tested restore |
| Resource configuration | **Yes** | Restrict access; avoid unnecessary secret copies |
| API keys and DB credentials | **Yes**, encrypted and particularly restricted | Loss or compromise may require rotation |
| Server revision | Reproducible Git/release reference | Match binaries/code and schema version |
| V1 SQLite | **Yes**, SQLite-consistent backup method | Do not casually copy a live DB/WAL file |
| V1 internal audit table | **Yes**, in the same DB backup | Keep write/audit consistency |
| Logs | Per data-retention policy | Avoid uncontrolled personal-data retention |
| Rate-limit short-lived store | Usually not required | Reset can alter burst/rate behavior |

A generic MariaDB dump is not automatically consistent if tables use nontransactional engines. Choose and verify the appropriate backup mode, for example a consistent InnoDB-compatible procedure. For SQLite, prefer a DB-supported online backup API or controlled quiescence; copying an active file while WAL transactions are running can be unsafe.

### 6.2 Mandatory restore rehearsal

1. Create a separate **test** installation with its own URL and private paths.
2. Identify backup source, timestamp and matching software/configuration versions; never overwrite production by default.
3. Restore database and protected resource/key material in a documented order.
4. Verify tables, primary/foreign keys, DB grants, instance identity and (V1) audit table.
5. Execute authorized GET/write smoke tests, plus negative secret-path and cross-instance key tests.
6. Record actual restored data state, elapsed time, failures and corrective actions.

**RTO/RPO must be based on tested restoration and operator requirements**, not invented values.

### 6.3 Upgrade and rollback

Before upgrade, record software commit/tag, configuration schema, database schema, and verified backup. Test on staging first. V1 may require config/audit schema migrations, so simply reverting old PHP files **may not** be a safe rollback. Document the point at which a database restore is necessary. Legacy API keys must not automatically acquire administrator rights during migration.

## 7. Recurring operations and observability

**Technical logging:** structured JSON Lines with UTC timestamp, request ID, instance/resource, method, response status and sanitized error category. Never log passwords, complete authorization headers, raw SQL bindings or entire sensitive request bodies. Store/rotate logs privately per instance.

**Health/readiness:** public status exposes only minimal non-sensitive state. Detailed DB and audit checks require a protected operator route in V1; database outage must not be misrepresented as successful empty results.

**Regular checks:** PHP/DB security updates, TLS validity, private-path isolation, DB privileges, API-key lifecycle, error logs, backup success, most recent restore rehearsal, approved resource policies and unresolved risks.

## 8. Troubleshooting and incident response

| Symptom | Safe first check | Action / escalation |
|---|---|---|
| 401 for all requests | Key source, header and rotation | Do not paste keys into chat/logs; inspect private config |
| 403/404 for an intended route | Resource/method/role policy and API version | Repair explicit allowlist; never enable global all-access |
| 500/503 | Request ID, private logs, DB/config readiness | Diagnose internally; avoid public stack traces |
| 404 for all clean routes | Webserver routing and front controller | Test `index.php?route=...` and correct host routing |
| MariaDB unavailable | SQL service, host, account, GRANTs | Resolve via protected operator access |
| V1 SQLite busy | Concurrent writers, timeouts, filesystem and journal mode | Bounded retry/locking review; never loop forever |
| V1 rate limit 429 | Client identity, budget and Retry-After | Do not disable throttling for every client |
| V1 rate state store error | Locking, file/DB write permissions | Preserve **fail-closed** behavior |
| V1 audit insert fails | Internal audit table, permissions and transaction | Roll back data write; never continue without required audit |
| Secrets accessible through HTTP | Emergency access restriction and key rotation | Treat as critical security incident |

### 8.1 Security incident steps

First **contain** the problem: restrict the affected instance, revoke/rotate exposed keys and secure private files. Preserve essential, protected and redacted evidence. Evaluate impact using request IDs, private logs and backups, and coordinate any legal/user notifications with the responsible operator. Restore service only after the fix and negative regression tests pass.

See [SECURITY.md](../SECURITY.md) and [Risk register](risk-register.md).

## 9. Operational checklists

**New installation:** compatible PHP/PDO; minimal DB privileges; valid config; enforced HTTPS; private paths inaccessible; strong key; approved CRUD resources; routing fallback; logs and backup.

**Upgrade:** reviewed release; backup and tested restore; config migration; unchanged API semantics unless deliberately versioned; security/contract tests; rollback plan.

**Recovery:** separate destination; restore DB/config/keys consistently; correct software and audit schema; authenticated smoke and isolation tests; record results.

**Status:** a proposed operational runbook. Host-specific commands and production claims are deferred until actually verified.
