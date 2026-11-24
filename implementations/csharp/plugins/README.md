# C#/.NET plugins – separate from core

> **Language:** **English (default)** | [Deutsch](README.de.md)

The following category directories are reserved for runtime-specific, explicitly trusted extensions: `logging/`, `identity/`, `storage/`, `application/`. This separation is **structural**, not evidence that any provider is implemented or safe to install. No file scanning, untrusted plugins or unguarded HTTP routes.
