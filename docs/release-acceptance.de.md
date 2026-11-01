> **Sprache:** [English (Standard)](release-acceptance.md) | **Deutsch**

# SASD API Server – Releaseprozess, Abnahme und Checklisten

> **Status:** Verfahrensentwurf, kein Release oder bestandener Test wird behauptet · **Stand:** 01.11.2026  
> Referenzen: [Roadmap](../ROADMAP.md), [Teststrategie](teststrategie.md), [Entwicklungsplan V0](entwicklungsplan-v0.md), [Lastenheft V0](lastenheft-v0.md), [Lastenheft V1](lastenheft-v1.md)

## 1. Warum ein verbindliches Release-Gate?

Ein API-Server verwaltet Datenzugriff und Zugangsdaten. Eine funktionierende Demonstration von GET oder POST ist daher **keine vollständige Abnahme**. Jede freigegebene Version braucht nachvollziehbare Nachweise für funktionale Anforderungen, Sicherheit, Betrieb, Datenintegrität und Wiederherstellung. Diese Dokumentation trennt technische Entwicklung, Release-Kandidat und **tatsächlich abgenommene Veröffentlichung**.

**Wichtig:** Die GitHub-Dokumente sind derzeit Entwürfe; die nachstehenden Checklisten sind **nicht abgehakt**, solange kein Test mit Beleg stattgefunden hat.

## 2. Versions- und Branchmodell

- Versionen wie `0.1.0` und `1.0.0` orientieren sich an Semantic Versioning, soweit sinnvoll für die öffentlichen API- und Konfigurationsverträge.
- `main` ist die gemeinsame Ausgangsbasis. Arbeit kann über kleine Feature-/Fix-Branches und Pull Requests erfolgen.
- Bis zur ersten Tag-Freigabe werden keine funktionsfähigen Releases behauptet.
- Die technische Programmversion und der HTTP-Pfad `/api/v0/` bzw. `/api/v1/` sind **getrennte** Begriffe.
- Nach einer stabilen V1-Schnittstelle sind Breaking Changes bewusst zu versionieren und bei Bedarf mit einer zeitlich dokumentierten Übergangsphase zu versehen.
- Release-Notes nennen keine Funktion als vorhanden, wenn sie nur im Lasten- oder Pflichtenheft beschrieben ist.

## 3. Rollen im Verfahren

| Aufgabe | Zuständigkeit | Ergebnis |
|---|---|---|
| Entwicklungsverantwortliche | Code, Tests, technische Dokumentation | reproduzierbare Änderung |
| Reviewer/Architekturverantwortliche | Schichtgrenzen, Security, API-Verträge, ADRs | Review/Entscheid |
| Betreiber | Hosting- und Restore-Nachweis | Deployment-/Recovery-Protokoll |
| Projektverantwortliche | fachliche Freigabe anhand Lastenheft | dokumentierte Abnahme |

Eine Person kann mehrere Rollen innehaben. Die Rollen dienen der **Prüftrennung**, nicht der Behauptung eines größeren Teams.

## 4. Version 0.1.0 – Abnahme-Gate

### 4.1 Funktionale Vollständigkeit

- [ ] Alle **V0-F01 bis V0-F17** erfüllt.
- [ ] Alle **AT-V0-01 bis AT-V0-10** mit dokumentiertem Ergebnis.
- [ ] CRUD: Listen/Einzelobjekt, Create, Replace, Partial Update, Delete.
- [ ] Filter: eq/AND, Sortierung, Limit/Offset nach freigegebenen Feldern.
- [ ] JSON-Verträge und Fehlercodes über echte HTTP-Requests geprüft.
- [ ] Identische Semantik mit Rewrite und Query-Fallback oder dokumentierter Host-Fallback.
- [ ] Keine einzige nicht freigegebene Tabelle/Spalte/Methode erreichbar.

### 4.2 Sicherheitsfreigabe

- [ ] HTTPS auf Zielhost verpflichtend und mit Negativtest geprüft.
- [ ] Konfiguration, DB-Credentials, API-Schlüssel und Logs **physisch außerhalb** Document Root.
- [ ] Direkter HTTP-Abruf sensibler Dateien scheitert.
- [ ] Fehlender, falscher oder widerrufener API-Key kann keine Daten abrufen.
- [ ] SQL-Injection über ID, Filter, Sortierung und Body führt nicht zur Ausführung von SQL-Code.
- [ ] JSON-/Bodygrößen-Limits und unbekannte Felder greifen.
- [ ] Clientfehler/Serverfehler veröffentlichen weder SQL noch Stacktrace/Secrets.
- [ ] Kein ungeschützter API-Key in öffentlichem Browser-Client eingeplant.
- [ ] Zwei Installationen besitzen voneinander getrennte Credentials und Daten.

### 4.3 Betrieb, Tests und Recovery

- [ ] PHP-, PDO- und DB-Versionen der Testumgebung dokumentiert.
- [ ] Unit/Integration/Contract/Security mit tatsächlichem Ergebnis protokolliert.
- [ ] DB-Backup und **Restore auf Testsystem** erfolgreich nachgewiesen.
- [ ] Status/Logging bei absichtlichem DB-Ausfall kontrolliert.
- [ ] README, Installations-/Betriebsdokumentation und Beispieldaten konsistent.
- [ ] Risiko-Register aktualisiert; keine offenen kritischen P0-Befunde.
- [ ] Bekannte Einschränkungen transparent in Release-Notes dokumentiert.

**Release-Gate:** Fehlt einer dieser zwingenden Punkte, bleibt die Version **Release-Kandidat/Entwicklung**, nicht „V0 abgenommen“.

## 5. Version 1.0.0 – zusätzliches Abnahme-Gate

V1 setzt **eine weiterhin bestandene V0-Basis** voraus. Darauf kommen hinzu:

- [ ] Alle **22 V1-MUSS-Anforderungen** erfolgreich verifiziert.
- [ ] Alle **AT-V1-01 bis AT-V1-16** mit dokumentiertem Ergebnis.
- [ ] MariaDB/MySQL und SQLite bestehen denselben **gemeinsamen** CRUD-/Query-/Security-Contract.
- [ ] Feldalias-/View-/Relation-Policies inkl. Negativtests.
- [ ] Mehrere technische Keys, Widerruf, Rollen und sichere Permission-Defaults.
- [ ] Rate-Limit und sichere Fehlerreaktion bei nicht verfügbarem Rate-Store.
- [ ] Schreibender Auditdatensatz wird **im selben DB-Commit** gespeichert; Auditfehler rollt Änderung zurück.
- [ ] OpenAPI-Dokument stimmt mit veröffentlichten Routen und Rollenberechtigungen überein.
- [ ] V0→V1-Konfigurationsmigration und separates Rollback-/Restore-Verfahren sind getestet.
- [ ] Beispielclients curl, .NET und Java verwenden denselben API-Vertrag.
- [ ] SQLite-Dateirechte, Locking, Busy-Verhalten und echtes Hostingprofil geprüft.
- [ ] Die fünf **SOLL**-Funktionen (V1-F09/F14/F16/F22/F27) sind als umgesetzt+getestet oder **ausdrücklich verschoben** dokumentiert.
- [ ] Kein öffentlicher Client-SQL-Interpreter oder HTTP-DDL-Endpunkt eingeschleppt.

## 6. Release-Kandidat: Arbeitsfolge

1. **Scope einfrieren:** Git-Commit, Lasten-/Pflichtenheft-Versionen, Featureliste und bewusst ausgeschlossene Funktionen benennen.
2. **Statische Prüfung:** Syntax, Style, Abhängigkeiten, Geheimnis-Suche, Beispielkonfigurationen, Markdown-Links/JSON-Beispiele.
3. **Automatisierte Tests:** Unit, Integration, Contract, Security, Migration soweit im Releaseumfang; Ergebnisse dokumentieren.
4. **Security Review:** insbesondere Allowlist, QueryParser, Schlüsselhandling, TLS/Proxy, Instanzisolation, ErrorMapper.
5. **Provider-Test:** frische Shared-Hosting-Instanz mit realem Routing und Privatdateien. Kein Ersatz durch lokalen `php -S`-Test.
6. **Backup/Restore:** Test-DB sichern und auf einer **anderen** Testinstanz vollständig wiederherstellen.
7. **Smoke-Test:** gültiger GET, verbotener GET, Write, Fehlerfall, Status und Logs; ggf. zwei DB-Adapter.
8. **Dokumentationsreview:** README, CHANGELOG, API-Vertrag, Runbook, bekannte Grenzen und eventuell ADRs angleichen.
9. **Freigabeentscheidung:** Projektverantwortliche dokumentieren, was konkret geprüft wurde; offene Befunde bewerten.
10. **Tag/Release:** erst nach Freigabe erstellen; Release-Notes referenzieren tatsächlichen Commit und Checks.

## 7. Abnahmeprotokoll – Vorlage

~~~markdown
# Abnahme: SASD API Server <Version>

- Datum / Prüfer:
- Git-Commit / Tag-Kandidat:
- Zielhosting und URL (ohne Secret):
- PHP / PDO / MariaDB / SQLite Versionen:
- Konfigurationsschema:
- Testfixture (Name, keine produktiven Daten):
- Unit-Tests: bestanden / fehlgeschlagen / nicht ausgeführt, Link:
- Integrations-/Contract-Tests: Ergebnis, Link:
- Security-Negativtests: Ergebnis, Link:
- Routing und Secret-Datei-Negativtest: Ergebnis, Link:
- Zwei unabhängige Instanzen: Ergebnis, Link:
- Backup/Restore: Ergebnis, Datum, Wiederherstellungsdauer:
- V0 AT-V0-01..10 / V1 AT-V1-01..16: Einzelstatus:
- Offene Probleme / Risikokennungen:
- Nicht enthaltene Funktionen und bekannte Grenzen:
- Entscheidung: ABGENOMMEN / NICHT ABGENOMMEN
- Begründung und ggf. genehmigte Abweichungen:
~~~

Dieses Template ist absichtlich **leer**. Ein fehlender Test erhält den Status `nicht ausgeführt` und niemals automatisch `bestanden`.

## 8. Rollback und Hotfix

**Hotfix:** Ein minimaler, überprüfbarer Fix darf einen schweren Fehler korrigieren, ohne neue Funktionalität zu aktivieren. Negative Regressionstests und ein nachvollziehbarer Changelog-Eintrag sind Pflicht.

**Rollback:** Codeversion und Konfigurationsschema müssen miteinander kompatibel sein. Sind Datenbankschemata/Audit-Tabellen migriert worden, kann reines Zurückspielen von PHP-Dateien unzureichend sein. Vor dem Update müssen Backup und eine tatsächlich getestete Wiederherstellungsfolge vorliegen. Ein Rollback darf keine bereits erfolgreich bestätigten Schreibvorgänge ohne bewusste Betreiberentscheidung vernichten.

**Sicherheitsvorfall:** Gefährdete Instanz einschränken, Keys rotieren, Ursache und Auswirkungen prüfen, keine Exploitdetails öffentlich teilen. Siehe [SECURITY.md](../SECURITY.md) und [Betriebs-Runbook](betrieb-und-deployment.md).

## 9. Projektstatus und Pflege

Gates werden erst nach erfüllten Prüfungen als bestanden markiert. Für V0/V1 muss die Abnahme mit den entsprechenden Lastenheften übereinstimmen. Ändert sich der fachliche Umfang, ist zunächst eine nachvollziehbare Spezifikationsänderung notwendig. Falls ein Soll-Feature verschoben wird, gehören Entscheidung und späterer Prüfbedarf in die Roadmap.

**Derzeit:** Ein Release-/Abnahmeverfahren ist dokumentiert; ein freigegebener Softwarestand wird nicht behauptet.
