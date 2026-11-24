# Änderungsprotokoll – SASD API Server

> **Sprache:** [English (Standard)](CHANGELOG.md) | **Deutsch**

Wesentliche Projektänderungen werden hier dokumentiert. Der Aufbau orientiert sich an [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) und nutzt Versionsangaben in Anlehnung an Semantic Versioning. Dieses Dokument ersetzt weder die Git-Historie noch Tags und Abnahmeprotokolle.

## [Unreleased]

### Erstes PHP-Core-Grundgerüst und Sprachstruktur – 24.11.2026

- Datenbankadapter-Dokumentationsbranch unter Erhalt beider Git-Historien gemergt. Doppelte **ADR-0020**-Nummer aufgelöst: Auth-/Logging-Dienstgrenzen bleiben **ADR-0020**, Datenbankadapter heißen nun **[ADR-0021 (Vorschlag)](docs/adr/0021-pluggable-database-adapters.de.md)**.
- `implementations/php`, `implementations/java`, `implementations/csharp` voneinander getrennt sowie sprachspezifische `plugins/{logging,identity,storage,application}`-Verzeichnisse und künftige gemeinsame `contracts/`-Dokumentation angelegt.
- Erstes **PHP-8.4**-Bootstrap/Health-Grundgerüst: geprüftes privates nicht ausführbares Plugin-JSON, ausschließlich registrierte Factories, Plugin-API `1.0`, geschütztes technisches UTC-JSONL-Logging, Fehlerhüllen und Tests. Dokumentiert in [Entwicklungsstruktur](docs/development-layout.de.md) und [ADR-0022 (Vorschlag)](docs/adr/0022-trusted-php-plugin-lifecycle.de.md).
- Lokal mit **PHP 8.4.24**, PHP-Lint, **43 Assertions** und HTTP-Smoke-Tests (200/404/405) geprüft. **Keine echte Datenbank, API-Key-/TLS-Durchsetzung, IONOS-Prüfung, vollständige V0-Abnahme, bestätigte CI oder Produktivfreigabe.**

### Dokumentations- und Architekturprüfung – 24.11.2026

- Projektgespräch gegen zweisprachige V0-/V1-Lastenhefte, Pflichtenhefte, Architektur, Risiko-/Test-/Betriebsunterlagen und Roadmap geprüft; Ergebnisse im [deutschen Prüfbericht](docs/documentation-review-2026-11-24.de.md) und [English report](docs/documentation-review-2026-11-24.md) dokumentiert.
- **SEC-INT-001** als zweisprachigen [Vertrag der Auth-/API-/Logging-Dienstgrenzen](docs/service-boundaries-and-integrations.de.md) sowie **[ADR-0020 (Vorschlag)](docs/adr/0020-auth-api-logging-service-boundaries.de.md)** ergänzt. V0 bleibt eigenständig, V1 behält technische Keys/Rollen lokal; ein externer Auth Service und der ungeschützte LogSink sind **keine** Pflichtabhängigkeiten.
- **V1-F19 präzisiert:** Erfolgreiche Datenänderung und Pflicht-Audit gehören zur selben lokalen DB-Transaktion; abgewiesene Versuche liefern gesonderte geschützte Sicherheitsereignisse mit noch festzulegender Haltbarkeit.
- Vorschläge **AT-INT-01–12** und Risiken **R-028/R-029** ergänzt; Lasten-/Pflichtenhefte, Architekturen, Roadmap, Tests, Betrieb und Abnahme verknüpft.
- Fehlerhafte Traceability-Markdown-Tabellen und beschädigte Geo-IP-Notizen bereinigt sowie **15 alte deutsche/englische Dokumentpfade** mit aktuellen kanonischen Sprachfassungen synchronisiert.
- **Status:** ausschließlich Planung/Dokumentation; SEC-INT-001 und SEC-GEO-001 weiterhin nicht abgenommen, V0-/V1-Pflichtumfang und Teststatus unverändert.



### Vorgeschlagene Sicherheitsarchitektur – 23.11.2026

- **Ergänzung vom 24.11.2026:** SEC-GEO-001 / ADR-0019 um **Firmen-/VPN-Allowlist mit Standard sperren**, Denylist mit Standard erlauben und kombinierte Regeln mit Sperr-Priorität erweitert. Zusätzliche Testfälle **AT-GEO-13–18**, Schutz vor Admin-Aussperrung/Änderungen der Firmen-IP und IPv4/IPv6 berücksichtigt; deutsche/englische Unterlagen angepasst. Weiterhin nur ein Entwurf, keine implementierte Sperre.


- [SEC-GEO-001](docs/ip-geoblocking.de.md) / [English](docs/ip-geoblocking.md) für frühe konfigurierbare IPv4-/IPv6-CIDR-Sperren ergänzt, mit verifizierter Client-IP, Fail-Closed, privater Regeldatei und zwölf vorgeschlagenen Tests.
- [ADR-0019 (Vorschlag)](docs/adr/0019-ip-geoblocking.de.md) / [English](docs/adr/0019-ip-geoblocking.md) angelegt; Lastenheft, Pflichtenheft, Architektur, Risiko-/Testdokumentation, Roadmap und Navigation verknüpft.
- **Status:** reine Planung, keine implementierte Sperre und keine freigegebenen Länder/IP-Netze. Die bisherige Zählung von 17 V0-F-Anforderungen und zehn Abnahmeszenarien bleibt bis zur formalen Scope-Freigabe unverändert.
 – Noch nicht veröffentlicht

### Hinzugefügt – Dokumentation und Projektplanung (01.11.2026)

- Ausführlicher Funktionskatalog zu PHP-CRUD-API, DreamFactory und DB2Rest in Deutsch und Englisch.
- Lastenhefte V0 und V1 in Deutsch und Englisch.
- Technische Pflichtenhefte und Architekturdokumente V0 und V1.
- V0-orientierte [Roadmap](ROADMAP.de.md), detaillierter Entwicklungsplan, Teststrategie, Deployment-/Recovery-Runbook, Risikoregister, Release-/Abnahmeprozess und ADR-Register.
- Mitwirkungsregeln, Sicherheitsrichtlinie und Dokumentationsnavigation.
- Zweisprachige Einstiegspunkte und Sprachumschaltung (soweit verfügbar).

- **Englisch als Standardsprache** für kanonische Markdown-Dateien eingeführt; die **deutschen Fassungen unter `.de.md`** sind für Lastenhefte, Pflichtenhefte, Architektur, Roadmap, Betrieb, Tests, Risiken, Projektregeln und GitHub-Vorlagen verfügbar.
- Bisherige deutsche und englische Dateipfade mit Sprachverweisen für bestehende Links beibehalten; unverbindliche deutsche MIT-Lizenzübersetzung ergänzt, rechtlich maßgeblich bleibt `LICENSE`.

### Wichtige Statushinweise

- **Es wird keine implementierte oder abgenommene V0.1.0 oder V1.0.0 behauptet.**
- Funktionen aus Lasten- und Pflichtenheften sind Anforderungen beziehungsweise Entwürfe, bis Code und Tests vorliegen.
- Aus diesen Einträgen folgen **keine** Release-Tags, Benchmarkzahlen, Providerabnahmen, Sicherheitsaudits oder erfolgreich durchgelaufenen CI-Prüfungen.

## Vorlage für spätere Release-Einträge

Wenn eine Version tatsächlich freigegeben ist, einen neuen Abschnitt anlegen:

~~~markdown
## [X.Y.Z] – JJJJ-MM-TT

### Hinzugefügt
- Implementierte und getestete Funktion (Link zu Anforderung/PR).

### Geändert
- Änderungen an öffentlicher API, Konfiguration oder Verhalten.

### Behoben
- Nachprüfbare Fehlerkorrekturen.

### Sicherheit
- Bereinigte Beschreibung der Sicherheitskorrektur ohne Exploit/Secrets.

### Kompatibilität und Betrieb
- Unterstützte PHP-/DB-Versionen, Migration, Rollback und bekannte Grenzen.

### Verifikation
- Git-Tag/Commit, Contract-/Security-Testnachweise, Hostingprüfung, Restore-Test.
~~~

**Einen veröffentlichten Versionsabschnitt erst nach echtem Git-Tag, Prüfung und dokumentierter Freigabe eintragen.**
