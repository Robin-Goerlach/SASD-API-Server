# ADR-0022 – Trusted PHP plugin lifecycle, separated from the server core

> **Language:** **English (default)** | [Deutsch](0022-trusted-php-plugin-lifecycle.de.md)  
> **Date:** 2026-11-24  
> **Decision status:** Proposed; first bootstrap implementation exists, **not formally accepted** and **not production certified**.  
> **Related:** [Development layout](../development-layout.md) · [ADR-0020](0020-auth-api-logging-service-boundaries.md) · [ADR-0021](0021-pluggable-database-adapters.md)

## Problem

The SASD API Server will have separately replaceable logging, identity, storage and possibly approved application modules. Each future PHP, Java and C# implementation must keep plugin code **outside its core directory**. A general plugin marketplace, untrusted code loader or public plugin administration surface would greatly enlarge the attack surface on restricted shared hosting.

## Proposed decision, implemented only as a small PHP slice

1. PHP 8.4 exposes a **narrow factory/runtime contract** (current plugin API version `1.0`). Each factory is registered *explicitly in reviewed bootstrap source code*; JSON may select only previously registered IDs. There is no directory scan, arbitrary class-name resolution from config, dynamic downloading, `eval`, public upload endpoint or hot reload.
2. An operator-supplied, **non-executable JSON manifest physically outside the document root** selects enabled IDs. Missing/malformed config, unregistered/duplicated IDs, unsupported API versions, wrong instance identity/category or failed plugin initialization block startup. No silent fallback to an unexpected provider.
3. Distinct categories are `logging`, `identity`, `storage`, `application`. The first three are exclusive (at most one selected); application modules are **deferred** and never get automatic HTTP route access. Initialization order is logging → identity → storage → application, after validating all selections.
4. The PHP core retains a tiny **mandatory private technical diagnostic logger** independently of any optional logging plugin. Local write audit, where required later, remains in the *same database transaction* as the relevant data modification. A remote logging sink must never be mistaken for that transaction.
5. A future identity plugin may authenticate or validate claims, but **the core authorizes** resources, methods, fields and rows. A storage plugin cannot define public HTTP routes or bypass the resource registry and validation pipeline.
6. Java and C# have their own future plugin contracts. A shared **HTTP/JSON external contract** does not imply ABI-compatible plugin binaries.

## Alternatives and trade-offs

- Hard-code every optional service into the PHP core: less setup now, difficult to replace and violates the desired separation.
- Auto-discover arbitrary PHP packages from `plugins/`: convenient but unsafe unless every extension is trusted, signed, reviewed, sandboxed and operationally supportable; **rejected for the first stage**.
- Mandatory remote Auth/LogSink at startup: introduces availability and trust dependencies; **rejected for V0**.
- A small static trusted registry, independently scoped by language: chosen first increment.

## Security consequences and open gates

The first implementation has **no installed production plugins**, no data CRUD, no public plugin administration and no remote integrations. An in-process PHP extension executes with the same privileges as PHP; this is **not a sandbox**, so arbitrary third-party code remains excluded. The example manifest enables nothing. Plugin boot has no automatic rollback of external side effects; factory authors must be trusted and tested, and any failed startup is unavailable.

Before accepting this decision for production, review plugin supply chain and lifecycle, category/version semantics, secured service integration, provider TLS/private-path guarantees, logging durability/retention and end-to-end authorization tests. Existing V0/V1 required feature and acceptance counts **do not change automatically** because this ADR or its experimental code exists.
