# SASD API Server implementations

> **Language:** **English (default)** | [Deutsch](README.de.md)

Three **independent** implementations share the product's external, versioned HTTP/JSON contract. They do **not** share internal runtime classes or binary plugins.

| Directory | Runtime | Current status |
|---|---|---|
| [`php/`](php/README.md) | PHP **8.4** on shared hosting | First, incomplete bootstrap and trusted plugin manager |
| [`java/`](java/README.md) | Java (version/stack not yet fixed) | Planned directories only |
| [`csharp/`](csharp/README.md) | C# / .NET (version not yet fixed) | Planned directories only |

Each implementation owns `core/` or `src/Core/` and **separate** `plugins/`. Plugin binaries and APIs are **language-specific**; only the public REST/JSON behavior is intended to be compatible.

The common API contract and future cross-language fixture rules live in [`../contracts/`](../contracts/README.md). Versioned V0 and V1 requirements stay under [`../docs/`](../docs/README.md). No runtime is production-ready yet.
