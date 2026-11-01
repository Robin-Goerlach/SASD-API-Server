# AGENTS.md – Arbeitsanweisungen für Coding-Agenten

> **Language:** **English (default)** | [Deutsch](AGENTS.de.md)

> **Gültigkeit:** gesamtes Repository `Robin-Goerlach/SASD-API-Server`. Spezifischere Anweisungen in Unterverzeichnissen dürfen diese Regeln ergänzen, nicht Sicherheitsanforderungen abschwächen.  
> **Stand:** 01.11.2026 · **Status:** Entwicklungsrichtlinien, keine Aussage über einen bereits implementierten oder getesteten Produktstand.

## 1. Projektziel und Prioritäten

Der **SASD API Server** ist als schlanker, konfigurierbarer **REST/JSON-Datendienst** für mehrere SASD-Anwendungen geplant. Dieselbe Codebasis wird künftig mehrfach unter unterschiedlichen URLs eingesetzt; jede Installation besitzt eigene Ressourcen, Datenbank, Zugangsdaten und Betriebsdateien.

Die wichtigsten Grundsätze:
1. **V0 zuerst**: V0.1.0 vollständig und sicher implementieren und abnehmen, erst danach den V1-Ausbau beginnen.
2. **Analyse vor Programmierung**: Anforderungen, Auswirkungen, Sicherheitsgrenzen und relevante Tests prüfen.
3. **Saubere Architektur vor Optimierung**: lesbarer, wartbarer und ausführlich kommentierter Code; keine unnötigen Frameworks und Abstraktionen.
4. **Deny by default**: unbekannte Datenressourcen, Felder, Methoden, Filter oder Rollen niemals automatisch freigeben.
5. **Tests tatsächlich ausführen** und nicht ausgeführte Prüfungen als solche kennzeichnen.
6. **Kleine Entwicklungs-Slices** statt großer ungetesteter Implementierungsblöcke.

## 2. Normative Projektunterlagen

Vor der Bearbeitung einer Aufgabe die passenden Dokumente lesen:

| Zweck | Datei |
|---|---|
| Projektüberblick und tatsächlicher Status | [README.md](README.md) |
| Meilensteine, V0 vor V1 | [ROADMAP.md](ROADMAP.md) |
| Dokumentationsnavigation | [docs/README.md](docs/README.md) |
| Fachlicher V0-Vertrag | [docs/requirements-v0.de.md](docs/requirements-v0.de.md) |
| Technische V0-Anforderungen | [docs/technical-specification-v0.md](docs/technical-specification-v0.md) |
| V0-Komponenten, Datenflüsse, Sicherheitsgrenzen | [docs/architecture-v0.md](docs/architecture-v0.md) |
| Kleine V0-Entwicklungsschritte | [docs/development-plan-v0.md](docs/development-plan-v0.md) |
| Tests und tatsächliche Release-Abnahme | [docs/test-strategy.md](docs/test-strategy.md), [docs/release-acceptance.md](docs/release-acceptance.md) |
| Betrieb, Hosting, Recovery | [docs/deployment-operations.md](docs/deployment-operations.md) |
| Risiken und blockierende Fragen | [docs/risk-register.md](docs/risk-register.md) |
| Sicherheit und Offenlegung | [SECURITY.md](SECURITY.md) |
| Mitwirkungsregeln | [CONTRIBUTING.md](CONTRIBUTING.md) |
| Architekturentscheidungen | [docs/adr/README.md](docs/adr/README.md) |
| Spätere V1-Anforderungen | [docs/requirements-v1.de.md](docs/requirements-v1.de.md), [docs/technical-specification-v1.md](docs/technical-specification-v1.md), [docs/architecture-v1.md](docs/architecture-v1.md) |

**Bei Widersprüchen:** aktuelle ausdrückliche Aufgabenstellung und unverzichtbare Sicherheitsregeln → fachliche MUSS-Anforderungen des Lastenhefts → tatsächlich angenommene Architekturentscheidungen → Pflichtenheft/Architektur → Roadmap und Beispiele. Viele Dokumente haben derzeit **Entwurfsstatus**. Nicht so tun, als seien sie formell genehmigt: Widersprüche benennen, bevor eine irreversible technische Entscheidung getroffen wird.

**Sprache:** Projektkommunikation und primäre Planung auf Deutsch; Codebezeichner, API-Felder und technische Klassen in verständlichem Englisch. Die englischen Lastenhefte in `docs/requirements-v0.md` und `docs/requirements-v1.md` sind bei Änderungen der Anforderungen anhand derselben IDs konsistent zu halten.

## 3. Verifiziere den tatsächlichen Repositoryzustand

Die vorhandenen Lasten-/Pflichtenhefte, Architekturen und Pläne beschreiben **beabsichtigte Funktionen**, keine nachgewiesene Implementierung. Vor **jeder** Coding-Aufgabe prüfen: aktuellen Branch/Commit, Git-Status, Dateien, Composer-Konfiguration, tatsächlich vorhandene Tests und Workflows. Keine nicht existierenden Klassen, Skripte, CI-Gates oder erfolgreich ausgeführten Tests behaupten.

Bestehende uncommittete Änderungen nicht überschreiben. Aufgabenfremde Reformatierungen, strukturelle Großumbauten und Änderungen an fremder Arbeit vermeiden. Wenn eine Funktion noch nicht implementiert ist, das ausdrücklich kennzeichnen.

## 4. Versions- und Scope-Grenzen

### V0.1.0 – unmittelbarer Entwicklungsauftrag

- **PHP 8.4** als Runtime, **MariaDB 10.11/MySQL über PDO** als erster und einziger V0-Produktivadapter.
- Einsatz auf gewöhnlichem **PHP Shared Hosting**, ohne Pflicht für Docker, Redis, Root, Queue oder dauerhaft laufende Prozesse.
- Ein kleiner `index.php`-Front-Controller im öffentlichen Webroot. Bei unzuverlässigem URL-Rewriting zusätzlich `index.php?route=/api/v0/...`.
- **Einzelne** GET-/POST-/PUT-/PATCH-/DELETE-Operationen auf **ausdrücklich** freigegebene Tabellen/Felder.
- Einspaltige Primärschlüssel, einfache Gleichheitsfilter (AND), erlaubte Sortierung, begrenzte Pagination, einheitliches HTTP/JSON-Fehlerformat.
- Installationsspezifischer API-Key, HTTPS, requestbegrenzende Schutzregeln, redigiertes Logging und Basis-Health.
- Zwei unabhängige Installationen aus derselben Codebasis als Abnahmefall.
- **Abnahme:** alle `V0-F01` bis `V0-F17` und `AT-V0-01` bis `AT-V0-10`.

### V1.0 – spätere Ausbaustufe

Nach abgenommener V0 folgen eine stabilere API-Version, SQLite als zweiter Pflichtadapter, Feldaliase, kontrollierte Relationen/Views, Rollen und mehrere technische Schlüssel, OpenAPI, Audit und Rate-Limits. Fünf V1-SOLL-Funktionen bleiben **optional**, bis sie gesondert freigegeben werden.

**In V0 nicht eigenmächtig entwickeln:** SQLite/PostgreSQL/JSON-Datei-Adapter, Endbenutzer-Login, OAuth/SSO, umfassende Mandantenfähigkeit, vollständige Datenbank-Schemafreigabe, GraphQL, beliebige SQL-Abfragen, generische Join-/Massenschreibsysteme, HTTP-DDL, Web-Admin oder eine DreamFactory-Nachbildung. Referenzprodukte sind Ideenquellen, **keine** 1:1-Featureliste.

## 5. Architektur und Abhängigkeiten

Schichten und Fluss:

~~~text
Client (HTTPS / REST / JSON)
  → HTTP: Router, Request, ResourceController
  → Application: CrudService, Validierung
  → Domain: ResourceDefinition, FieldDefinition
  → Contracts: RecordRepository (Port)
  ← Infrastructure: MariaDbPdoRepository (Adapter)
  → MariaDB

Querschnitt: Security, Config, Errors, Logging, Health
Zusammenführung der Komponenten: Bootstrap / index.php
~~~

**Nicht verhandelbare Schichtregeln:**
- `index.php` und HTTP-Controller enthalten **kein SQL** und keine DB-Zugangsdaten.
- Anwendungsservices steuern CRUD und Transaktionen; sie hängen nicht direkt von `PDO` oder `$_SERVER` ab.
- Domänen-/Ressourcendefinitionen enthalten keine HTTP-Response- oder DB-Verbindungsobjekte.
- Der Repository-Port ist klein und unabhängig vom SQL-Dialekt; konkrete PDO-Implementierung nur in Infrastructure.
- Die ResourceRegistry/Policy ist die Autorität für veröffentlichte Ressourcen, Felder und Methoden.
- Ein neuer Adapter oder Controller darf nie Authentifizierung, Validierung, Fehlerabbildung oder Logging umgehen.
- Schnittstellen nur einführen, wenn ein konkreter Use Case oder Test sie benötigt; kein Overengineering.

**Codequalität:** gut lesbares PHP, klare Namespaces, passende PHP-Typen und PHPDoc. Fachlich relevante und öffentliche Methoden, ungewöhnliche Fehlerfälle, sicherheitskritische Entscheidungen und Abhängigkeitsgrenzen **ausführlich kommentieren**. Kommentare erklären vor allem das *Warum*. Verständlichkeit und Korrektheit haben Vorrang vor Mikrooptimierungen.

## 6. HTTP-Verträge respektieren

Geplante V0-Routen (noch nicht zwingend implementiert):

| Methode | Route | Bedeutung |
|---|---|---|
| GET | `/api/v0/{resource}` | Liste |
| GET | `/api/v0/{resource}/{id}` | Einzelobjekt |
| POST | `/api/v0/{resource}` | einzelner Insert |
| PUT | `/api/v0/{resource}/{id}` | kompletter Ersatz der **clientseitig schreibbaren** Felder |
| PATCH | `/api/v0/{resource}/{id}` | nur gelieferte Felder ändern |
| DELETE | `/api/v0/{resource}/{id}` | genau einen Datensatz entfernen |
| GET | `/api/v0/health` | nicht sensitiver Grundstatus |

Der Fallback `/index.php?route=/api/v0/{resource}` muss identische Regeln ausführen. Keine URI-Parameter zu Dateipfaden umdeuten. Unzulässige, doppelt kodierte oder mehrdeutige Eingaben kontrolliert ablehnen.

**Response-/Fehlerregeln:** dokumentierte `data`-/`meta`-Hüllen, Fehler mit maschinenlesbarem Code und `requestId`, konsistente HTTP-Codes. Keine SQL-Statements, Hostpfade, Stacktraces oder Credentials in Antworten. Bei ungültigen Filtern **nicht** unbemerkt alle Datensätze zurückgeben.

**Datenänderungen:** Transaktion pro Schreibrequest, eindeutiger Primärschlüssel, strenger PUT-/PATCH-Unterschied, Datenbank-Constraints respektieren. `rowCount()===0` beweist bei einem UPDATE **nicht**, dass ein Datensatz fehlt. V0 besitzt kein Optimistic Locking. Public API-/Config-Vertragsänderungen nur nachvollziehbar dokumentieren und mit Contract-Tests absichern.

## 7. SQL, Ressourcen und Konfiguration

1. **Default-Deny:** Eine physisch vorhandene Tabelle wird erst nach expliziter Ressourcenfreigabe über die API sichtbar.
2. **SQL-Parameter:** Werte werden mit PDO Prepared Statements gebunden. Tabellen-, Spalten-, ORDER-BY- und Relationsbezeichner werden ausschließlich aus validierten serverseitigen Allowlists übernommen.
3. **Nur erlaubte Daten:** Keine unkontrollierte `SELECT *`-Antwort; keine Änderung nicht schreibbarer Felder; `UPDATE` und `DELETE` nur mit vollständiger, eindeutig adressierender Schlüsselbedingung.
4. **Least Privilege:** Eigene DB-Zugangsdaten je Instanz und minimal nötige SQL-GRANTs; API-Policy ist ein **zusätzlicher** Schutz.
5. **Transaktionen und Fehler:** Einzelne Schreiboperationen atomar. DB-Ausfälle nicht als erfolgreiche leere Listen ausgeben. SQL-Constraints in neutrale API-Fehler übersetzen.
6. **Konfiguration:** versioniertes, validiertes Ressourcenformat; bei Fehlern **fail closed**. Keine HTTP-Routen, die beliebige Konfiguration, SQL-Schema oder DB-Verbindungen ändern.
7. **Kein fingierter Backend-Support:** Erst wenn ein zweiter Adapter implementiert und mit derselben Suite getestet wurde, darf dessen Unterstützung behauptet werden.

## 8. Hosting- und Sicherheitsregeln

**Produktiv zwingend:** Nur der erforderliche HTTP-Einstieg liegt im Document Root. **Secrets, DB-Credentials, Ressourcen-Konfiguration, Logs, Backups und spätere SQLite-Dateien liegen außerhalb öffentlich ausgelieferter Pfade.** Ein möglicherweise ignoriertes `.htaccess` ist **keine** ausreichende Schutzgrenze.

- Externe Datenrouten nur über **HTTPS**; Proxy-/`X-Forwarded-Proto`-Informationen nur nach nachweisbar vertrauenswürdigem Hostingweg akzeptieren.
- API-Key ausschließlich im HTTP-Header; niemals in URL, Querystring, Git, Tests, Logs, Screenshots oder öffentlich zugänglicher Konfiguration.
- **CORS ist keine Authentifizierung**. Ein API-Key in Browser-JavaScript oder einer breit verteilten App ist **kein zuverlässig geheimes Credential**; dafür wäre ein zusätzlicher Vertrauens-/Auth-Dienst nötig.
- Bodygrößen, Query-Komplexität, Filter, Pagination und Ausgabemengen durchsetzen. Hostabhängige Grenzwerte wirklich messen.
- Technische Logs mit UTC, Request-ID und neutralen Fehlercodes; **keine** ganzen Payloads, Passwörter, Tokens oder SQL-Bindwerte.
- Öffentlicher Health-Endpunkt ohne Interna; tiefere Diagnose nur geschützt.
- Sicherheitskritische Fragen über Hosting, TLS, Dateipfade und Berechtigungen **vor** öffentlicher Freigabe klären. Wenn ein Provider die Bedingungen nicht erfüllen kann, **keinen unsicheren Workaround einbauen**.

Security-Incidents nach [SECURITY.md](SECURITY.md), Architektur-/Hosting-Risiken nach [Risikoregister](docs/risk-register.md) behandeln.

## 9. Tests und Nachweise

Für jede relevante Änderung einen **positiven** Test und die wesentlichen **negativen** Fälle liefern:

- **Unit:** Router, Konfiguration, API-Key-/Policy, Filter-/Datentypvalidierung, ErrorMapper.
- **Integration:** eigene MariaDB-Testdatenbank, CRUD, Constraints, Commit/Rollback, nicht vorhandene ID, unverändertes Update.
- **Contract:** HTTP-Methoden, JSON, Status, Header, API-Version und Routing-Fallback.
- **Security:** nicht erlaubte Tabellen/Felder/Methoden, falscher/fremder Key, SQL-Injection in ID/Filter/Sort, Bodylimits, Secret- und TLS-Leaks.
- **Deployment:** echte PHP-Hostingumgebung, direkte HTTP-Anfragen auf private Dateien müssen scheitern.
- **Isolation:** zwei Instanzen mit unterschiedlichen URLs, DBs, API-Keys, Logs und Ressourcen.
- **Recovery:** Backup und Restore auf einer separaten Testinstallation.
- **V1 später:** zusätzliche MariaDB/SQLite-Parität, rollenabhängige Projektion/Relationen, Audit-Rollback, Rate-Fail-Closed und OpenAPI.

**Vor dem Testen prüfen, welche Kommandos tatsächlich existieren.** `composer test`, GitHub-Actions-Workflows, Datenbankcontainer oder Release-Tags nicht erfinden. Nicht ausführbare Checks explizit als **„nicht ausgeführt“** mit Grund berichten. Niemals Produktionsdaten für Tests verändern.

Ein grüner Unit-Test ersetzt keinen echten Shared-Hosting-/Sicherheitstest. Ein Dokument oder ein erfolgreicher Build beweist **keine Abnahme**; dazu sind die vorgeschriebenen `AT-V0`-/`AT-V1`-Tests und Freigaben erforderlich.

## 10. Agenten-Workflow bei jedem Auftrag

### Vor der Änderung

1. Aufgabe, Zielversion, betroffene Anforderungen und Nicht-Ziele benennen.
2. Git-Branch, Commit und Arbeitsbaum prüfen; kein Überschreiben fremder Änderungen.
3. Passende Lasten-/Pflichtenheft-/Architekturabschnitte und Risiken lesen.
4. Kleinsten sinnvollen Slice wählen; bei verbindlichen Architekturentscheidungen ein [ADR](docs/adr/README.md) vorschlagen.

### Bei der Änderung

1. Code nach Schichtenverantwortung entwickeln; die jeweiligen Entscheidungen erklären und sinnvoll kommentieren.
2. API-Verträge und Sicherheitsvorgaben respektieren; keine „temporär offene“ Produktionsroute einführen.
3. Positive/negative Tests hinzufügen und tatsächlich ausführen, soweit die Umgebung es erlaubt.
4. Betroffene Dokumente und gegebenenfalls [CHANGELOG.md](CHANGELOG.md) aktualisieren.
5. Nur beauftragte Dateien ändern, keine unaufgeforderten großen Refactorings.
6. Keine destruktiven Git-Operationen, Force Pushes, Releases, Produktionsmigrationen oder Secret-Uploads ohne ausdrückliche Genehmigung.

### Im Abschlussbericht

- **Änderungen:** welche Dateien/Funktionen/Anforderungs-IDs tatsächlich bearbeitet wurden.
- **Tests:** exakt ausgeführte Kommandos und Ergebnisse oder **„nicht ausgeführt“ mit Begründung**.
- **Sicherheit und Risiken:** offene Punkte, insbesondere Provider-, Auth- und Datenintegritätsgrenzen.
- **Dokumentation:** geänderte API-/Config-/Betriebsunterlagen und ggf. ADRs.
- **Nächster Schritt:** ein kleiner realistischer Folgeslice; keine unbeauftragte Ausweitung auf V1.

## 11. GitHub, Reviews und Definition of Done

Kleine, nachvollziehbare Git-Änderungen und aussagekräftige Commitnachrichten bevorzugen, etwa `docs: add AGENTS guidance`, `feat(v0): add resource registry` oder `test(v0): reject unknown fields`. Pull Requests nennen Anforderungs-IDs, Tests, Nicht-Ziele und Risiken. [CONTRIBUTING.md](CONTRIBUTING.md) und die vorhandenen GitHub-Issue-/PR-Vorlagen beachten.

**Fertig ist ein Slice erst**, wenn der vereinbarte Umfang umgesetzt, die Schichtentrennung bewahrt, positive und negative Fälle belegt, Geheimnisse geschützt und Dokumentation/Vertrag konsistent sind. Fehlende Host- oder Recovery-Tests bleiben **offen**; keinen Tag, Release, Audit oder erfolgreichen CI-Lauf behaupten, bevor er tatsächlich existiert.

**Erster geplanter Schritt:** `V0-G0` (Hosting-/Security-Grenzen), danach `V0-M0` (kleines Projektgerüst). **V0 sicher und nachvollziehbar fertigstellen – anschließend gezielt V1.**

---
**Agentenhinweis:** Diese Datei beschreibt Arbeitsrichtlinien. Sie erteilt **keine** Erlaubnis für ungefragte Aktionen in Produktivsystemen oder für das Abschwächen von Sicherheitsvorgaben.
