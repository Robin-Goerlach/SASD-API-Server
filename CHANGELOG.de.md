# Änderungsprotokoll – SASD API Server

> **Sprache:** [English (Standard)](CHANGELOG.md) | **Deutsch**

Wesentliche Projektänderungen werden hier dokumentiert. Der Aufbau orientiert sich an [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) und nutzt Versionsangaben in Anlehnung an Semantic Versioning. Dieses Dokument ersetzt weder die Git-Historie noch Tags und Abnahmeprotokolle.

## [Unreleased]

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
