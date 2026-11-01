# AGENTS.md – Arbeitsanweisungen für Coding-Agenten

> **Sprache:** [English (Standard)](AGENTS.md) | **Deutsch**  
> **Gültigkeit:** gesamtes Repository `Robin-Goerlach/SASD-API-Server`. Weitere Anweisungen in Unterverzeichnissen können ergänzen, dürfen Sicherheitsregeln nicht abschwächen.  
> **Stand:** 01.11.2026 · **Status:** Entwicklungsrichtlinien; kein Nachweis eines bereits implementierten oder abgenommenen Softwarestands.

## 1. Projektziel und Prioritäten

Der **SASD API Server** soll als kleiner, offener, konfigurierbarer REST/JSON-Datendienst mehrere Anwendungen unterstützen. Die gleiche Softwarebasis kann getrennt unter verschiedenen URLs mit je eigener Datenquelle, Konfiguration, Zugangsdaten, Ressourcenliste und Logs eingesetzt werden.

Die verbindlichen Arbeitsgrundsätze:
1. **V0 zuerst fertigstellen und abnehmen.** V1 ist ein späteres Ausbauziel, keine sofortige Featureliste.
2. **Analyse vor Code:** Anforderungen, Architektur, Sicherheit, Datenintegrität und Tests prüfen.
3. **Verständlicher Code vor Optimierung:** klare Schichten, lesbare Methoden, gut begründete und ausführliche Kommentare.
4. **Default-Deny:** nicht konfigurierte Ressourcen, Felder, Methoden, Filter, Rollen und Relationen bleiben gesperrt.
5. **Tests tatsächlich ausführen und korrekt berichten.** Nicht ausgeführte Prüfungen deutlich kennzeichnen.
6. **Kleine, nachprüfbare Slices** statt umfassender ungetesteter Umbauten.

## 2. Maßgebliche Projektdokumentation

Vor einer Änderung die relevanten Abschnitte lesen:

| Thema | Dokument |
|---|---|
| Status und Produktziel | [README Deutsch](README.de.md) / [README Englisch](README.md) |
| Zeitliche Reihenfolge und Gates | [Roadmap Deutsch](ROADMAP.de.md) |
| Alle Dokumente | [Dokumentationsindex Deutsch](docs/README.de.md) |
| Fachliche V0-Anforderungen | [Lastenheft V0](docs/lastenheft-v0.md) |
| V0-Umsetzung | [Pflichtenheft V0](docs/pflichtenheft-v0.md) |
| Verantwortlichkeiten, Sicherheit, Datenflüsse | [Architektur V0](docs/architektur-v0.md) |
| V0-Slices | [Entwicklungsplan](docs/entwicklungsplan-v0.md) |
| Tests/Freigabe | [Teststrategie](docs/teststrategie.md) und [Release/Abnahme](docs/release-und-abnahme.md) |
| Hosting und Restore | [Betrieb und Deployment](docs/betrieb-und-deployment.md) |
| Risiken | [Risikoregister](docs/risikoregister.md) |
| Vertrauliche Sicherheitsmeldungen | [Sicherheitsrichtlinie](SECURITY.de.md) |
| Mitwirkung | [CONTRIBUTING Deutsch](CONTRIBUTING.de.md) |
| Architekturbeschlüsse | [ADR-Register](docs/adr/README.md) |
| V1-Zukunft | [Lastenheft](docs/lastenheft-v1.md), [Pflichtenheft](docs/pflichtenheft-v1.md), [Architektur](docs/architektur-v1.md) |

**Bei Widersprüchen:** ausdrückliche aktuelle Aufgabenstellung und Sicherheitsregeln → MUSS-Anforderungen des zuständigen Lastenhefts → tatsächlich angenommene ADRs → Pflichtenheft/Architektur → Roadmap und Beispiele. Da viele Unterlagen **Entwürfe** sind, unklare Punkte offen benennen und keine strittige Semantik ungefragt verbindlich machen.

Projektkommunikation und primäre technische Entwürfe sind in deutscher Sprache möglich; die **englische Fassung ist der standardmäßige Repository-Einstieg**. Im Programm werden verständliche englische Namen für Typen, APIs und Klassen verwendet. Deutsche und englische Lastenhefte behalten identische Anforderungs-IDs.

## 3. Tatsächlichen Repositoryzustand feststellen

Vor jeder Entwicklungsaufgabe Git-Branch, HEAD, Arbeitsbaum, Dateien, Composer-Konfiguration, existierende Tests und CI-Workflows prüfen. Dokumentierte Klassen oder Skripte sind **nicht automatisch implementiert**. Keine nicht existierenden Testkommandos, Build-Erfolge oder Releases behaupten.

Vorhandene uncommittete Änderungen nicht überschreiben. Nur auftragsbezogene Änderungen vornehmen; keine fremde Arbeit oder große Formatierungsänderungen ohne Notwendigkeit.

## 4. Versions- und Scope-Grenzen

### V0.1.0 – erster Entwicklungsauftrag

- **PHP 8.4**, **MariaDB/MySQL über PDO**; genau ein V0-Datenbankadapter.
- Gewöhnliches PHP-Shared-Hosting ohne Pflicht zu Docker, Redis, Root, Queue oder permanenten Workern.
- Root-`index.php` als Front Controller und `index.php?route=/api/v0/...` als Routing-Fallback.
- Einzelnes GET/POST/PUT/PATCH/DELETE für explizit erlaubte Tabellen und Spalten.
- Einspaltige Primärschlüssel, einfache Gleichheitsfilter (AND), Sortierung, begrenzte Pagination, standardisierte HTTP-/JSON-Fehler.
- Ein installationsspezifischer API-Key, HTTPS, Input-Limits, sanierte Logs, Health und Instanzisolation.
- **Abnahme:** V0-F01 bis V0-F17 sowie AT-V0-01 bis AT-V0-10.

### V1.0 – erst nach abgenommener V0

SQLite als zweiter Adapter, versionierter API-Vertrag, Aliasse, begrenzte Filter/Relationen/Views, mehrere technische Schlüssel mit Rollen, OpenAPI, Audit, Rate-Limits und Config-Migration. Die fünf V1-SOLL-Funktionen bleiben gesondert freizugebende Optionen.

**Nicht eigenmächtig in V0 einbauen:** SQLite/PostgreSQL/JSON/Remote-API-Adapter, Endbenutzer-Login, OAuth/SSO, vollständige Mandantenfähigkeit, SQL-Schemaeditor, GraphQL, beliebige SQL-Abfragen, Massenschreiben, umfangreiche Joins, Web-Admin oder eine DreamFactory-Kopie.

## 5. Architektur und Schichtengrenzen

~~~text
Client (HTTPS, REST, JSON)
  → HTTP: Router, Request, Controller
  → Application: CrudService, Validatoren
  → Domain: ResourceDefinition, FieldDefinition
  → Contracts: RecordRepository
  ← Infrastructure: MariaDbPdoRepository
  → MariaDB

Querschnitt: Security, Config, Errors, Logging, Health
Zusammenführung: Bootstrap / index.php
~~~

- `index.php` und HTTP-Controller enthalten kein SQL und keine Zugangsdaten.
- Anwendungsservices koordinieren CRUD, Validierung und Transaktionen; sie importieren weder PDO noch `$_SERVER`.
- Domain und Repository-Vertrag enthalten keine HTTP-Response-Objekte oder konkreten SQL-Treiber.
- Die ResourceRegistry erlaubt nur bewusst freigegebene Ressourcen/Felder/Methoden.
- Neue Adapter oder Controller müssen die zentrale Security-/Validation-/Logging-Pipeline verwenden.
- Abstraktionen nur für echte Anwendungsfälle, nicht auf Vorrat.

**Quellcodequalität:** saubere Namespaces, präzise Typen, PHPDoc für Verträge; ausführliche `//`-Kommentare bei Architekturfolgen, nicht offensichtlichen Fehlerfällen und Sicherheitsinvarianten. Kommentare erklären das **Warum**; verständlicher Code vor Mikrooptimierung.

## 6. Öffentlichen HTTP-/JSON-Vertrag einhalten

| Methode | Geplanter V0-Pfad | Funktion |
|---|---|---|
| GET | `/api/v0/{resource}` | Liste |
| GET | `/api/v0/{resource}/{id}` | Einzelobjekt |
| POST | `/api/v0/{resource}` | Einzelne Neuanlage |
| PUT | `/api/v0/{resource}/{id}` | vollständige klientseitig schreibbare Repräsentation ersetzen |
| PATCH | `/api/v0/{resource}/{id}` | nur gesendete Felder ändern |
| DELETE | `/api/v0/{resource}/{id}` | genau einen Datensatz löschen |
| GET | `/api/v0/health` | knapper nicht sensitiver Status |

Der Fallback `/index.php?route=...` unterliegt denselben Sicherheitsregeln. Routen-/Query-Eingaben normalisieren und auf ungültige/doppelt kodierte Pfade prüfen.

**Antworten:** `data`/`meta`, maschinenlesbares Fehlerobjekt, `requestId` und HTTP-Status. Keine SQL-Details, Secrets, private Pfade oder Stacktraces. Ungültige Filter **nicht** stillschweigend verwerfen, um anschließend alle Datensätze zurückzugeben.

**Schreiben:** Ein Request, ein gezielter Datensatz, eine lokale Transaktion. PUT ist Vollersatz clientseitig schreibbarer Felder, PATCH ist Teiländerung. `rowCount() = 0` beweist nicht automatisch, dass der Datensatz fehlt. V0 garantiert noch kein Optimistic Locking.

## 7. Persistenz- und Konfigurationsregeln

1. Standardmäßig **kein** Tabellen- oder Spaltenzugriff ohne ausdrückliche Konfiguration.
2. SQL-**Werte** über PDO Prepared Statements binden; **SQL-Identifier** aus geprüften serverseitigen Allowlists.
3. Nur lesbare Spalten selektieren; UPDATE/DELETE stets mit konkretem Schlüsselprädikat.
4. DB-Rechte pro Instanz minimal; API-Policy als zusätzliche Schicht.
5. Transaktionen, Constraints, NULL/UTC/UUID/Boolean und Fehlermapping gezielt testen.
6. Fehlkonfiguration wird **fail closed** behandelt; kein automatisches Öffnen aller Ressourcen.
7. Keine DDL-/Schemaänderung durch öffentliche CRUD-Routen; keine Backend-Unterstützung ohne echten Adapter und Tests behaupten.

## 8. Hosting und Sicherheitsgrenzen

**Produktiv zwingend:** Der HTTP-Einstieg `index.php` darf im Webroot liegen, **Secrets, Konfiguration, Logs, Backups und spätere SQLite-Dateien jedoch nicht**. Ein ausschließlich auf `.htaccess` gestützter Schutz genügt nicht.

- HTTPS zwingend; `X-Forwarded-Proto` nur aus nachweislich vertrauenswürdiger Proxykette.
- API-Key ausschließlich über Header, nie in URL/Query/Git/Log/öffentlicher Testdatei.
- Ein Key im öffentlichen Browser-JavaScript oder weit verbreiteten Client ist kein geheimes Credential.
- CORS ersetzt **keine** Authentifizierung.
- Body-, Query-, Paging- und Ergebnisgrenzen durchsetzen und unter realem Hosting prüfen.
- Logs mit UTC/Request-ID ohne komplette Nutzdaten, Auth-Header oder SQL-Werte.
- Öffentlicher Health ohne Interna; Readiness nur geschützt.
- Ist ein sicherer Betrieb auf dem Zielhost nicht möglich, **keinen unsicheren Workaround freigeben**.

Sicherheitsmeldungen über [SECURITY.de.md](SECURITY.de.md), offene Risiken im [Risikoregister](docs/risikoregister.md).

## 9. Tests und Nachweise

**Unit:** Router, Config, Ressourcen- und Policyvalidator, QueryParser, ErrorMapper.  
**Integration:** MariaDB, CRUD, Constraints, Commit/Rollback, fehlende ID, unverändertes Update.  
**HTTP-Contract:** Methoden, JSON, Header, Status, beide Routingmodi.  
**Security:** unbekannte Tabellen/Felder, falscher Key, Injection, Body-Limits, TLS und Secret-Dateien.  
**Hosting:** tatsächliche PHP-Provider-Eigenschaften und gescheiterter direkter Abruf privater Dateien.  
**Isolation:** zwei unabhängige Instanzen aus demselben Code mit unterschiedlichen DBs/Keys/Logs.  
**Recovery:** Backup und Restore auf gesondertem Testsystem.  
**V1:** zusätzlich beide DB-Adapter, RBAC-/Relation-/Count-Leaks, Audit-Rollback, Rate-Fail-Closed, OpenAPI.

**Testkommandos erst ausführen, wenn sie im Repository tatsächlich existieren.** Nicht getestete Features deutlich als „nicht ausgeführt“ kennzeichnen. Keine Produktionstests mit destruktiver Datenänderung. Erfolgreiche Unit-Tests ersetzen kein reales Hosting-/Security-Gate.

## 10. Agenten-Workflow

**Vorher:** Aufgabe und Zielversion prüfen; Branch/HEAD/Status ansehen; Lastenheft/Pflichtenheft/Architektur lesen; Risiko- und Testfolgen festhalten; nötige ADR vorschlagen.

**Währenddessen:** kleinen Slice implementieren; Schichtentrennung und Default-Deny sichern; ausführlich kommentieren; positive/negative Tests schreiben und ausführen; API-/Konfigurations-/Betriebsdokumentation aktualisieren; keine auftragsfremden Umbauten.

**Danach:** Dateien/Anforderungs-IDs, durchgeführte Testbefehle und Ergebnisse, verbleibende Sicherheits- und Hostingfragen, aktualisierte Dokumente und **einen** kleinen nächsten Schritt benennen.

Keine destruktiven Git-Aktionen, Force Pushes, Releases, produktiven Migrationen oder Secret-Uploads ohne ausdrückliche Berechtigung.

## 11. GitHub und Definition of Done

Kleine Commits/PRs mit aussagekräftigen Nachrichten verwenden, z. B. `feat(v0): add resource registry` oder `test(v0): reject unauthorized fields`. Reviews benötigen Ziel/Nicht-Ziele, Anforderungen, echte Testnachweise und relevante Risiken. [CONTRIBUTING.de.md](CONTRIBUTING.de.md) und Issue-/PR-Vorlagen beachten.

Ein Slice ist erst fertig, wenn Umfang, Architektur, positive und negative Tests, Geheimnisschutz und Dokumentation zusammenpassen. Ein fehlender Host-/Restore-Test bleibt offen. Kein Tag, kein Audit und kein „alle Tests grün“ ohne Beleg.

**Nächster geplanter Schritt:** **V0-G0** – reale Hosting-/Sicherheitsgrenzen prüfen, dann V0-M0 für das Projektgerüst. **Zuerst V0, danach V1.**

---
Diese Regeln berechtigen Agenten nicht dazu, ungefragt produktive Systeme zu ändern oder Schutzvorgaben zu umgehen.
