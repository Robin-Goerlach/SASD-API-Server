# SASD API Server – Dokumentationsübersicht

> **Stand:** 01.11.2026 · **Projektphase:** Dokumentation und Planung; keine implementierte oder abgenommene API-Version wird behauptet.  
> [Repository](../README.md) · [Roadmap](../ROADMAP.md) · [CHANGELOG](../CHANGELOG.md)

## 1. Lesereihenfolge und Dokumentationssystem

Die Dokumentation folgt einem klaren Aufbau. Das **Lastenheft** definiert den fachlichen Bedarf (*Was?*). Das **Pflichtenheft** konkretisiert die technische Umsetzung (*Wie?*). Die **Softwarearchitektur** erklärt die Bausteine, Schnittstellen, Datenflüsse, Sicherheitsgrenzen und technischen Entscheidungen (*Warum diese Aufteilung?*). **Roadmap und Entwicklungsplan** ordnen die Umsetzung in kleine Schritte, die **Teststrategie und Abnahme** legen fest, wie der Erfolg tatsächlich nachgewiesen wird.

~~~mermaid
flowchart LR
    L["Lastenheft<br/>Was?"] --> P["Pflichtenheft<br/>Wie?"]
    P --> A["Architektur<br/>Bausteine/Warum?"]
    A --> R["Roadmap + Entwicklungsplan"]
    R --> T["Tests + Abnahme"]
    A --> ADR["ADRs<br/>bewusste Entscheidungen"]
    T --> Op["Betrieb/Recovery"]
~~~

**Versionen:** V0 ist unsere **kleine erste Umsetzungsstufe** auf PHP/MariaDB. V1 ist eine **spätere, stabile Ausbauversion** mit SQLite, Rollen, OpenAPI, Audit und weiteren klar begrenzten Funktionen. Die Dokumentation von V1 bedeutet nicht, dass V1 schon begonnen wurde.

## 2. Lastenhefte / Requirements

| Dokument | Sprache | Zweck |
|---|---|---|
| [Lastenheft V0](lastenheft-v0.md) | Deutsch | 17 funktionale MUST-/MUSS-Anforderungen und 10 Abnahmeszenarien |
| [Requirements V0](requirements-v0.en.md) | English | vollständige englische V0-Fassung mit identischen IDs |
| [Lastenheft V1](lastenheft-v1.md) | Deutsch | 27 Anforderungen, davon 22 MUSS und 5 SOLL; 16 Abnahmeszenarien |
| [Requirements V1](requirements-v1.en.md) | English | vollständige englische V1-Fassung mit identischen IDs |

## 3. Pflichtenhefte und Architektur

| Dokument | Inhalt |
|---|---|
| [Pflichtenheft V0](pflichtenheft-v0.md) | PHP/MariaDB, HTTP-/JSON-Vertrag, Ressourcen, API-Key, PDO, Sicherheits- und Testregeln |
| [Architektur V0](architektur-v0.md) | C4-/Mermaid-Sichten, Komponenten, Datenflüsse, Trust Boundaries, ADR-Kandidaten |
| [Pflichtenheft V1](pflichtenheft-v1.md) | Versionierter Vertrag, SQLite, QueryPlan, Rollen, Transaktionsaudit, OpenAPI |
| [Architektur V1](architektur-v1.md) | Adapter-/Policy-/Auditarchitektur, Migration, optionale V1-Module, technische Tests |

**Status:** Alle vier Dokumente sind technische Entwürfe, keine Aussage über ausgeführten Code. Der Schwerpunkt liegt zunächst auf V0, nicht auf der vollständigen Implementierung der V1-Zielarchitektur.

## 4. Umsetzung, Tests, Betrieb und Governance

| Dokument | Verantwortungsbereich |
|---|---|
| [ROADMAP.md](../ROADMAP.md) | geplante Meilensteine und Freigabegates V0→V1 |
| [Entwicklungsplan V0](entwicklungsplan-v0.md) | kleine technische Slices inklusive Positiv-/Negativtests und Voraussetzungen |
| [Teststrategie](teststrategie.md) | Unit-, DB-Integration-, HTTP-Contract-, Security-, Hosting- und V1-Adaptertests |
| [Betrieb und Deployment](betrieb-und-deployment.md) | sicherer Webroot, Hosting, Installation, Logging, Backup/Restore, Störungs-Runbook |
| [Release und Abnahme](release-und-abnahme.md) | Releasechecklisten, tatsächlich erforderliche Testnachweise und Abnahmevorlage |
| [Risikoregister](risikoregister.md) | Risiken und blockierende Hosting-/Security-Entscheidungen |
| [ADR-Register](adr/README.md) und [ADR-Vorlage](adr/0000-template.md) | vorgeschlagene, überprüfbare Architekturentscheidungen |
| [CONTRIBUTING.md](../CONTRIBUTING.md) | Beiträge, Codequalität und PR-Konventionen |
| [SECURITY.md](../SECURITY.md) | verantwortliche Schwachstellenmeldungen und Sicherheitspflichten |
| [CHANGELOG.md](../CHANGELOG.md) | Dokumentationsänderungen, spätere echte Release-Notes |
| [LICENSE](../LICENSE) | vorhandene MIT-Lizenz |

## 5. Referenzprojekte

- [Funktionskatalog der Referenzprodukte – Deutsch](referenzprodukte-funktionskatalog.md): PHP-CRUD-API, DreamFactory und DB2Rest, Funktionen, Grenzen, Quellen.
- [Feature Catalog – English](reference-products-feature-catalog.en.md): englische Fassung derselben Recherche.

Die Referenzsammlung ist **ein Ideenpool**. Aus der Existenz einer Funktion in DreamFactory, PHP-CRUD-API oder DB2Rest folgt **keine** Verpflichtung zur SASD-Implementierung. Editions- und Lizenzgrenzen der Vorbilder werden beachtet.

## 6. Anwendungsfall: „Ich möchte mit der Entwicklung beginnen“

1. [Roadmap](../ROADMAP.md) lesen: **V0-G0 und V0-M0** als nächster Fokus, nicht V1.
2. [V0-Lastenheft](lastenheft-v0.md) und [V0-Pflichtenheft](pflichtenheft-v0.md) als fachlichen und technischen Vertrag heranziehen.
3. [Architektur V0](architektur-v0.md) für Komponenten, Requestfolge und Sicherheitsgrenzen prüfen.
4. [Risiko R-001/R-002](risikoregister.md) im tatsächlichen Hosting klären: private Dateien und TLS.
5. Kleine Aufgaben aus dem [V0-Entwicklungsplan](entwicklungsplan-v0.md) auswählen.
6. Tests und Negativfälle aus der [Teststrategie](teststrategie.md) für jeden Slice definieren.
7. Ergebnis, Abweichungen, offene Risiken und ggf. ADR dokumentieren; erst nach bestandener [Abnahme](release-und-abnahme.md) einen Release markieren.

## 7. Dokumentenpflege und Statussprache

**„Geplant“** bedeutet dokumentiert, **„in Arbeit“** bedeutet belegbar begonnene Umsetzung, **„abgenommen“** bedeutet vorhandene Test- und Freigabenachweise. Kapitel oder Checkboxen ersetzen keine ausgeführten Tests. Ausdrückliche Änderungen an funktionalen Anforderungen werden im Lastenheft vorgenommen und dann in Pflichtenheft, Architektur, Roadmap, Tests und gegebenenfalls Übersetzungen übernommen.

Ein zunächst auf Deutsch gepflegtes Dokument wird durch englische Übersetzungen nicht automatisch normativ ersetzt. Anforderungen verwenden dieselben IDs über Sprachversionen hinweg. Im Konfliktfall entscheidet die formal freigegebene Fassung; aktuell gibt es **noch keine** förmlich freigegebenen Anforderungsversionen.

**Dokumentstatus:** Navigationshilfe, keine Implementierung.
