# SASD API Server – Dokumentationsübersicht

> **Sprache:** [English (Standard)](README.md) | **Deutsch**  
> **Stand:** 01.11.2026 · **Status:** Dokumentation und Architekturplanung; noch keine implementierte oder abgenommene V0/V1.  
> [Repository-README](../README.de.md) · [Roadmap](../ROADMAP.de.md) · [Änderungsprotokoll](../CHANGELOG.de.md)

## 1. Aufbau und Lesereihenfolge

Die Dokumentation trennt bewusst:

- **Lastenheft:** *Was* benötigt wird und wie sich die fachliche Erfüllung prüfen lässt.
- **Pflichtenheft:** *Wie* diese Anforderungen konkret technisch umgesetzt werden sollen.
- **Softwarearchitektur:** *Warum* Bausteine, Abhängigkeiten, Datenflüsse und Sicherheitsgrenzen so gestaltet sind.
- **Roadmap/Entwicklungsplan:** *In welcher Reihenfolge* kleine, prüfbare Arbeitsschritte entstehen sollen.
- **Tests und Abnahme:** *Welche tatsächlichen Nachweise* Funktion, Sicherheit und Hosting belegen.
- **Betrieb und Recovery:** *Wie* eine Installation sicher betrieben und wiederhergestellt werden soll.
- **ADRs:** *Welche technischen Entscheidungen* mit Alternativen und Konsequenzen bewusst beschlossen wurden.

~~~mermaid
flowchart LR
    L["Lastenheft<br/>Was?"] --> P["Pflichtenheft<br/>Wie?"]
    P --> A["Architektur<br/>Warum?"]
    A --> R["Roadmap und Entwicklungs-Slices"]
    R --> T["Tests und Abnahme"]
    A --> ADR["Architekturentscheidungen"]
    T --> B["Betrieb und Recovery"]
~~~

**V0** ist der geplante kleine PHP/MariaDB-Start. **V1** ist der spätere Ausbau mit SQLite, technischen Clientrollen, OpenAPI, kontrollierten Abfragen/Relationen und Audit. **Ein umfangreiches Dokument ist noch kein implementiertes Produkt.**

## 2. Sprach- und Dateikonvention

**Englisch ist die Standardsprache** für den Repository-Einstieg und die kanonischen Dokumentationspfade. Deutsche Fassungen haben in der Regel denselben Namen mit `.de.md` und sind gegenseitig verlinkt.

| Konvention | Beispiel |
|---|---|
| Englische Standarddatei | `docs/architecture-v0.md` |
| Deutsche Fassung | `docs/architecture-v0.de.md` |
| Root-README Englisch | `README.md` |
| Root-README Deutsch | `README.de.md` |
| Rechtlich maßgebliche MIT-Lizenz | `LICENSE` (englisches Original) |
| Deutsche Leseübersetzung | `LICENSE.de.md` (unverbindlich) |

Die älteren Dateinamen wie `lastenheft-v0.md`, `pflichtenheft-v0.md` und `architektur-v0.md` bleiben **aus Kompatibilitätsgründen weiterhin erreichbar**, damit alte GitHub-Verweise nicht ins Leere führen. Für neue Links verwenden wir nach Möglichkeit die **englische Standarddatei beziehungsweise deren deutsche `.de.md`-Fassung**.

Alle Sprachfassungen behalten dieselben **V0-F-/V1-F-, T-V0-/T-V1- und AT-V0-/AT-V1-Kennungen**, denselben technischen Inhalt, dieselben Sicherheitsanforderungen und denselben Entwurfs-/Abnahmestatus. Eine Übersetzung darf keine Funktionen unbemerkt hinzunehmen oder streichen.

## 3. Lastenhefte und Vorbilder

| Inhalt | Englisch (Standard) | Deutsch |
|---|---|---|
| Lastenheft V0: 17 MUSS, 10 Abnahmeszenarien | [Requirements V0](requirements-v0.md) | [Lastenheft V0](requirements-v0.de.md) |
| Lastenheft V1: 22 MUSS, fünf SOLL, 16 Abnahmeszenarien | [Requirements V1](requirements-v1.md) | [Lastenheft V1](requirements-v1.de.md) |
| PHP-CRUD-API, DreamFactory und DB2Rest im Vergleich | [Feature Catalog](reference-products-feature-catalog.md) | [Funktionskatalog](reference-products-feature-catalog.de.md) |

Die Referenzproduktsammlung ist eine **Ideen- und Analysegrundlage**, kein vollständiger Entwicklungsauftrag.

## 4. Pflichtenhefte und Softwarearchitektur

| Inhalt | Englisch (Standard) | Deutsch |
|---|---|---|
| V0: PHP/MariaDB, HTTP/JSON, Ressourcen, Sicherheit, Konfiguration und Tests | [Technical Specification V0](technical-specification-v0.md) | [Pflichtenheft V0](technical-specification-v0.de.md) |
| V1: zwei Adapter, Rollen, Filter, Audit, OpenAPI und Migration | [Technical Specification V1](technical-specification-v1.md) | [Pflichtenheft V1](technical-specification-v1.de.md) |
| V0: Komponenten, Schichten, Abläufe, Trust Boundaries und ADRs | [Architecture V0](architecture-v0.md) | [Softwarearchitektur V0](architecture-v0.de.md) |
| V1: Adapter-, Policy-, Query-, Audit- und Deploymentarchitektur | [Architecture V1](architecture-v1.md) | [Softwarearchitektur V1](architecture-v1.de.md) |

Diese Dokumente sind **detaillierte Entwürfe**. Die erwähnten PHP-Klassen, Routen, Tests und Workflows existieren nicht allein aufgrund ihrer Beschreibung.

## 5. Entwicklungs-, Test- und Betriebsdokumente

| Thema | Englisch (Standard) | Deutsch |
|---|---|---|
| Roadmap und Release-Gates | [ROADMAP.md](../ROADMAP.md) | [ROADMAP.de.md](../ROADMAP.de.md) |
| Kleine V0-Slices und Abnahmebedingungen | [Development Plan V0](development-plan-v0.md) | [Entwicklungsplan V0](development-plan-v0.de.md) |
| Unit-/SQL-/HTTP-/Security-/Hosting-/Recovery-Tests | [Test Strategy](test-strategy.md) | [Teststrategie](test-strategy.de.md) |
| Deployment, Shared Hosting, Konfiguration, Backup und Recovery | [Deployment/Operations](deployment-operations.md) | [Betrieb und Deployment](deployment-operations.de.md) |
| Freigabechecklisten und konkrete Testnachweise | [Release/Acceptance](release-acceptance.md) | [Release und Abnahme](release-acceptance.de.md) |
| Risiken, Blocker und Sicherheitsmaßnahmen | [Risk Register](risk-register.md) | [Risikoregister](risk-register.de.md) |
| ADR-Register | [ADR Register](adr/README.md) | [ADR-Register](adr/README.de.md) |
| ADR-Vorlage | [ADR Template](adr/0000-template.md) | [ADR-Vorlage](adr/0000-template.de.md) |

## 6. Projektregeln, Open Source und GitHub

| Bereich | Englisch (Standard) | Deutsch |
|---|---|---|
| Projektbeschreibung | [README](../README.md) | [README](../README.de.md) |
| Agentenregeln | [AGENTS](../AGENTS.md) | [AGENTS](../AGENTS.de.md) |
| Mitwirkung und Codestandards | [CONTRIBUTING](../CONTRIBUTING.md) | [Mitwirken](../CONTRIBUTING.de.md) |
| Sicherheitsmeldungen | [SECURITY](../SECURITY.md) | [Sicherheitsrichtlinie](../SECURITY.de.md) |
| Zusammenarbeit | [CODE_OF_CONDUCT](../CODE_OF_CONDUCT.md) | [Verhaltenskodex](../CODE_OF_CONDUCT.de.md) |
| Änderungshistorie | [CHANGELOG](../CHANGELOG.md) | [Änderungsprotokoll](../CHANGELOG.de.md) |
| MIT-Lizenz | [LICENSE (maßgeblich)](../LICENSE) | [Leseübersetzung](../LICENSE.de.md) |
| GitHub-Fehlervorlage | [Bug Report](../.github/ISSUE_TEMPLATE/bug_report.md) | [Fehlerbericht](../.github/ISSUE_TEMPLATE/bug_report.de.md) |
| GitHub-Funktionswunsch | [Feature Request](../.github/ISSUE_TEMPLATE/feature_request.md) | [Funktionswunsch](../.github/ISSUE_TEMPLATE/feature_request.de.md) |
| GitHub-Pull-Request-Vorlage | [Pull Request](../.github/PULL_REQUEST_TEMPLATE.md) | [Pull Request](../.github/PULL_REQUEST_TEMPLATE.de.md) |

## 7. Wie wir mit der Implementierung beginnen

1. [Roadmap](../ROADMAP.de.md) lesen: zuerst **V0-G0** (Hosting-/Security-Review), danach **V0-M0** (kleines Projektgerüst).
2. Fachlichen Umfang im [Lastenheft V0](requirements-v0.de.md) nachlesen.
3. Konkrete API-, Ressourcen- und Datenregeln im [Pflichtenheft V0](technical-specification-v0.de.md) prüfen.
4. Komponenten und Sicherheitsgrenzen in [Architektur V0](architecture-v0.de.md) verstehen.
5. Kritische Risiken **R-001/R-002** zu privaten Dateien und TLS auf dem realen Hosting prüfen.
6. Einen kleinen Schritt aus dem [Entwicklungsplan V0](development-plan-v0.de.md) wählen.
7. Positive und negative Fälle aus der [Teststrategie](test-strategy.de.md) ableiten.
8. Grundsatzentscheidungen im [ADR-Register](adr/README.de.md) dokumentieren.
9. Umsetzung und echte Prüfergebnisse festhalten; ein Release erst nach [Abnahme](release-acceptance.de.md) als fertig erklären.

## 8. Zweisprachige Pflege und ehrlicher Projektstatus

- **Englisch ist für die Navigation der Standard**, die deutsche Fassung soll fachlich gleichwertig bleiben.
- Änderungen an MUSS-/SOLL-Anforderungen in **beiden Sprachen** nachführen und anschließend Pflichtenhefte, Architektur, Roadmap und Tests anpassen.
- Kennungen in allen Sprachen stabil halten.
- **Geplant** heißt spezifiziert, **in Arbeit** heißt belegbar entwickelt, **abgenommen** heißt mit echten Prüfungen und Freigaben nachgewiesen.
- Keine Funktion, Pipeline oder Hostingkonfiguration allein wegen eines Dokuments als fertig beziehungsweise sicher bezeichnen.
- Alte Links so lange bestehen lassen, bis ein bewusster Migrationsschritt ihre Entfernung ohne Schaden ermöglicht.

**Status:** Zweisprachige Dokumentationsnavigation; keine Implementierung und keine Softwareabnahme behauptet.
