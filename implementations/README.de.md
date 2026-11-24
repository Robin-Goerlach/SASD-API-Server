# SASD API Server – Implementierungen

> **Sprache:** [English (Standard)](README.md) | **Deutsch**

Drei **voneinander unabhängige** Implementierungen sollen denselben versionierten HTTP/JSON-Vertrag erfüllen. Interne Klassen oder binäre Plugins werden nicht sprachübergreifend geteilt.

| Verzeichnis | Laufzeit | Stand |
|---|---|---|
| [`php/`](php/README.de.md) | PHP **8.4**, Shared Hosting | Erstes, unvollständiges Bootstrap- und Plugin-Manager-Grundgerüst |
| [`java/`](java/README.de.md) | Java (Version/Stack noch offen) | Nur geplante Verzeichnisse |
| [`csharp/`](csharp/README.de.md) | C# / .NET (Version noch offen) | Nur geplante Verzeichnisse |

Jede Sprache besitzt einen Core (`core/` oder `src/Core/`) und eigene **getrennte** `plugins/`. Plugins und ihre Binärschnittstellen sind sprachspezifisch. Gemeinsame spätere Vertrags- und Testvorgaben liegen unter [`../contracts/`](../contracts/README.de.md); Anforderungen unter [`../docs/`](../docs/README.de.md). Noch keine Implementierung ist produktionsreif.
