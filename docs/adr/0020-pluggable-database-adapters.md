# ADR-0020 – Pluggable Database Adapters, Stable Data API

> **Language:** **English (default)** | [Deutsch](0020-pluggable-database-adapters.de.md)  
> **Status:** Proposed — **not accepted or implemented**  
> **Date:** 2026-11-24  
> **Scope:** Future extensibility of the storage layer; no change to V0/V1 mandatory scope.  
> **Related:** [V0 architecture](../architecture-v0.md) · [V1 architecture](../architecture-v1.md) · [Roadmap](../../ROADMAP.md) · [ADR index](README.md)

## Context

The SASD API Server provides a small, configuration-driven REST/JSON API over explicitly approved data resources. The same server release serves independently configured installations. V0 targets PHP 8.4 and MariaDB/MySQL via PDO. V1 additionally targets SQLite. PostgreSQL, Oracle Database, IBM Db2 and Firebird are desirable future databases; the list must remain open without implying that every driver is compatible, available on shared hosting, or approved for any specific release.

PDO standardizes connection and statement APIs, **not** SQL dialects, type systems, generated keys, pagination, transaction behavior, or constraint semantics. A single configurable DSN without a database-specific adapter is therefore insufficient.

## Proposed decision

1. Keep the **HTTP/JSON contract, authentication, authorization, validation, resource policy, error mapping and CRUD orchestration in the core**. They must not depend on a particular SQL driver or dialect.
2. Maintain a small **storage-neutral RecordRepository port** and typed, validated query/operation descriptions. Database-specific SQL, identifier quoting, parameter binding, key generation, type normalization and error translation belong to an adapter.
3. Provide an **explicit adapter registration point** at the composition root/bootstrap. The configured provider ID is resolved only from a trusted allowlist of installed, approved adapter factories. No client-supplied driver names, arbitrary class loading, remote plug-in downloads, eval or dynamically executed configuration.
4. Treat each database adapter as an **optional, trusted deployment-time component**. A future implementation may package adapters separately using Composer, but V0 does not need a generic plug-in manager, runtime discovery, or a publicly accessible administration UI.
5. Require a **capability declaration and startup configuration validation**. The API never silently weakens required behavior when the selected adapter lacks a feature. Unsupported mandatory operations/configuration fail closed with actionable operator diagnostics (not public connection details).
6. A deployment has **one primary configured data source**. Multiple server installations may each select a different adapter; no cross-database joins or distributed transactions are implied.
7. Keep **application-specific API modules** separate from storage adapters. Both may become extension points, but storage adapters must never define public HTTP routes or bypass the core's security pipeline.

This is an architectural **proposal**, not a decision to implement all adapters. The specific interface names below are illustrative until a concrete review and compatibility tests approve them.

## Illustrative contracts

- `RecordRepository`: read/list/insert/replace/patch/delete for a configured resource; handles typed identifiers, defined not-found and conflict semantics.
- `StorageAdapterFactory`: builds a repository and transaction support using validated private instance settings.
- `QueryCompiler` or `SqlDialect`: maps a restricted internal query plan to the backend's supported SQL, including identifier quoting, parameterization, bounded filtering, deterministic sorting and pagination.
- `AdapterCapabilities`: records supported features and limitations, such as transactional writes, audit atomicity, read-only views, generated keys, optimistic concurrency, relation expansion, or identifier/type constraints.
- `DatabaseErrorMapper`: maps driver exceptions to stable internal categories without exposing SQL text, credentials or connection strings.

The core consumes these contracts rather than PDO. An adapter may use PDO, OCI8, ODBC or a vendor client internally if a future review justifies it. No adapter may widen the resource or field allowlist, interpret arbitrary client SQL, or silently disable audits/transactions.

## Release boundary and candidate adapters

| Stage | Database / candidate driver | Commitment |
|---|---|---|
| V0.1 | MariaDB/MySQL — PDO_MYSQL | The only required V0 adapter |
| V1.0 | SQLite — PDO_SQLITE | Second required adapter **after accepted V0** |
| Post-V1 candidate | PostgreSQL — PDO_PGSQL | Evaluate and implement separately |
| Post-V1 candidate | Oracle — PDO_OCI or OCI8 | Evaluate driver installation, client libraries, Oracle-specific SQL and compatibility |
| Future candidate | IBM Db2 — PDO_IBM or PDO_ODBC | Evaluate client/driver availability, supported Db2 editions and SQL dialect |
| Future candidate | Firebird — PDO_FIREBIRD | Evaluate native client availability, SQL dialect and concurrency |
| Other future candidates | Additional relational databases | Individual ADR/scope and tests required |

A listed driver is **not** proof of runtime support. In particular, Oracle/Db2 client libraries and PHP extensions may not be installable on restricted shared hosting. The selected deployment must verify supported PHP/driver/database versions and licenses/operating conditions.

## Non-negotiable compatibility and security gates

Every accepted adapter must pass the **same external HTTP contract suite** on its own isolated database fixture for applicable features, including:

1. Deny-by-default resource, method and field policy; SQL-injection attempts through names, filters, sorting and values.
2. CRUD, not-found behavior, validation, nullability, Unicode, dates/timestamps with explicit timezone rules, booleans, decimal precision, binary values where permitted, and key generation.
3. Bounded deterministic filtering, ordering and paging; no unbounded queries or arbitrary SQL expressions.
4. Transaction commit/rollback, unique/foreign-key constraint errors, concurrent changes and partial failures; **no data write without its mandatory same-transaction audit record in V1**.
5. Schema/identifier compatibility, database account least privilege, secret isolation, connection failure handling and sanitized logs.
6. Adapter missing/disabled/unsupported version or unsupported required capability causes a controlled deployment/health failure, not a fallback to broader permissions.
7. Reproducible tests, documented migrations and backup/restore against the **actual** target database version. A passing PDO connection test alone is insufficient.

Changes to public contract behavior require deliberate versioning or demonstrably compatible normalization. Storage schema migrations are a separate explicit operator/application responsibility; this ADR does **not** promise automatic migration between MariaDB, PostgreSQL, Oracle, Db2, Firebird or SQLite.

## Alternatives considered

- **Hard-code MariaDB everywhere:** fastest initially but makes future adapters disproportionately expensive; rejected as a long-term design.
- **Treat PDO as a complete cross-database abstraction:** insufficient because PDO does not translate SQL dialects or harmonize all semantics.
- **Implement every database and a full plug-in manager in V0:** costly, hard to test and unsafe for shared-hosting constraints; explicitly deferred.
- **Small repository port now, trusted adapter registry when a second adapter is real:** preferred incremental direction.

## Consequences, work and decision status

**For V0:** keep the already planned small storage-neutral port and MariaDB adapter, avoid spreading PDO/MySQL behavior into controllers/services; do not add speculative plug-in infrastructure.

**For V1:** implement and test SQLite as the second adapter; only then finalize the adapter factory/registry and capability contract if needed. Do not treat PostgreSQL or Oracle as V1 MUST requirements.

**After V1:** prioritize PostgreSQL and Oracle for feasibility spikes; treat Db2 and Firebird as additional independent candidates. Every adapter needs separate acceptance evidence and a hosting profile.

**Decision owner/reviewer:** pending. **Accepted on:** not applicable. **Implementation evidence:** none. Do not label any candidate adapter supported merely because this ADR mentions it.
