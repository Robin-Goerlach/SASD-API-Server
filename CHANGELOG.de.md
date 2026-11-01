# Änderungsprotokoll – SASD API Server

> **Sprache:** [English (Standard)](CHANGELOG.md) | **Deutsch**

Wesentliche Projektänderungen werden hier dokumentiert. Der Aufbau orientiert sich an [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) und nutzt Versionsangaben in Anlehnung an Semantic Versioning. Dieses Dokument ersetzt weder die Git-Historie noch Tags und Abnahmeprotokolle.

## [Unreleased] – Noch nicht veröffentlicht

### Hinzugefügt – Dokumentation und Projektplanung (01.11.2026)

- Ausführlicher Funktionskatalog zu PHP-CRUD-API, DreamFactory und DB2Rest in Deutsch und Englisch.
- Lastenhefte V0 und V1 in Deutsch und Englisch.
- Technische Pflichtenhefte und Architekturdokumente V0 und V1.
- V0-orientierte [Roadmap](ROADMAP.de.md), detaillierter Entwicklungsplan, Teststrategie, Deployment-/Recovery-Runbook, Risikoregister, Release-/Abnahmeprozess und ADR-Register.
- Mitwirkungsregeln, Sicherheitsrichtlinie und Dokumentationsnavigation.
- Zweisprachige Einstiegspunkte und Sprachumschaltung (soweit verfügbar).

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
