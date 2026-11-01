# SASD API Server – Entwicklungsplan V0.1.0

> **Sprache:** **Deutsch (bisheriger Dateipfad)** · [English (Standard)](development-plan-v0.md) · [Deutsch (kanonisch)](development-plan-v0.de.md)  

> **Status:** geplanter Ablauf, kein Code-/Testerfolg behauptet · **Stand:** 01.11.2026  
> Grundlage: [Lastenheft V0](lastenheft-v0.md) · [Pflichtenheft V0](pflichtenheft-v0.md) · [Architektur V0](architektur-v0.md) · [Roadmap](../ROADMAP.md)  
> Testvorgehen: [Teststrategie](teststrategie.md) · [Abnahme](release-und-abnahme.md)

## 1. Ziel und Entwicklungsdisziplin

V0.1.0 implementiert eine kleine **REST/JSON CRUD-API über PHP 8.4 und MariaDB/MySQL**, die pro Installation über geschützte Konfiguration dieselbe Software mit anderen Ressourcen und Datenbanken betreiben kann. Statt eine vermeintlich universelle Plattform zu bauen, werden **die 17 V0-MUSS-Anforderungen** umgesetzt und durch **zehn Abnahmeszenarien** geprüft. Das hier skizzierte Arbeitspaketmodell konkretisiert die [Roadmap](../ROADMAP.md).

**Jeder Slice ist unabhängig prüfbar:** Architektur-/Risikoanalyse → kleine Implementierung → positive und negative Tests → Kommentare/Docs → Review. Keine Implementierung vor der Analyse ihrer Sicherheitsgrenze. Komponenten dürfen noch nicht vorhandene Anforderungen nicht „auf Vorrat“ vorwegnehmen.

## 2. Definition of Ready für jeden Slice

- Ziel mit V0-F-Kennungen und überprüfbarer Akzeptanz formuliert.
- Betroffene Schichten (HTTP, Service, Repository, Config, Security) bekannt.
- Extern sichtbare API-Änderungen und Error-Codes benannt.
- Ein positiver Test, mindestens ein negativer Test und ggf. DB-/Deployment-Test beschrieben.
- Keine unaufgelöste Entscheidung zur Sicherheit oder Secret-Ablage.
- Umsetzung erfordert weder produktive Daten noch fremde APIs oder credentials im Repository.

## 3. Globale Querschnittsregeln

**Code:** PHP 8.4, UTF-8, lesbare PSR-orientierte Formatierung, klare Namespaces. PHPDoc an Verträgen und nicht offensichtlichen Entscheidungen, Kommentare erklären das **Warum**, nicht nur eine Zeile nach. Keine nur für Erweiterungsphantasien eingeführten Framework-Abstraktionen.

**Schichten:** Front Controller ist klein; HTTP-Schicht übersetzt Requests; Service orchestriert; ResourceRegistry legt Allowlist fest; Repository-Port kapselt Datenzugriff; MariaDB-Adapter führt SQL aus. Keine rohe PDO-Nutzung in HTTP-Controllern.

**Sicherheit:** Bei fehlerhafter Konfiguration fail-closed. Werte werden gebunden, SQL-Bezeichner ausschließlich aus einer serverseitigen Allowlist erzeugt. Keine ganze Tabelle per freiem Benutzerwert. Kein API-Key als Queryparameter. Secrets außerhalb des Document Root und niemals im Log.

**Arbeitsverzeichnis:** Änderungen auf einem thematischen Branch oder nachvollziehbar kleinem PR; Commitbeispiele: `feat(v0): add request router` oder `test(v0): cover unauthorized resource access`. Vor dem Merge PHP-Syntax-/Testprüfung; kein „Fix später“ bei sicherheitskritischen Fehlern.

## 4. Slice 00 – Hosting und Sicherheitsvoraussetzungen

**Ausgang:** Die Architektur sieht ein Root-`index.php` vor, der Provider kann `.htaccess` aber teilweise ignorieren.

**Umfang:** Testhosting mit PHP 8.4, PDO MySQL, MariaDB, HTTPS und realem Routing prüfen. Private Verzeichnisse physisch außerhalb des Webroots nachweisen. Konfiguration/Key/Log/Testdateien per direktem HTTP-GET absichtlich anfragen; jeder Abruf muss scheitern. TLS-Terminierung und Proxy-Header-Vertrauensgrenze dokumentieren.

**Lieferobjekte:** dokumentierte Hostmatrix (Versionen, PDO, Rewrite, PATH_INFO, private Files), eine Negativtestliste für Secretpfade, Entscheidung über kanonische URL und Query-Fallback.

**Abbruchbedingung:** Kann das Hosting Secrets nicht physisch schützen oder TLS nicht verbindlich erzwingen, keine öffentliche Produktivinstallation und zunächst kein „unsicherer Demo-Fallback“.

**Gate:** Hosting-Gate AT-V0-01 vorbereitet; Architekturentscheidungen für private Dateien/TLS/URL notiert.

## 5. Slice 01 – Projektgerüst und Tests

**Ziel:** Ein startfähiger, aber noch nicht an Daten angebundener Kern.

**Umfang:** Composer-Autoloading oder gleichwertige nachvollziehbare Klassenladung; vorgeschlagene Struktur `src/Http`, `Application`, `Domain`, `Contracts`, `Security`, `Infrastructure`; isolierte `tests/` und ein Secret-freies Beispiel; `.gitignore` erst beim Implementieren mit lokalen Keys, Logs und DB-Dateien. PHP-Fehler werden in Prod nicht öffentlich angezeigt.

**Tests:** PHP-Lint, erster Unit-Test, Testfixture und Testumgebung; README enthält lokal reproduzierbare Schritte ohne Produktionsdaten.

**Gate:** Ein neuer Entwickler kann das leere Programm und Tests lokal starten; keinerlei Secrets in Git.

## 6. Slice 02 – Config, Router und HTTP-Grundlagen

**Funktionen:** V0-F01, V0-F06, V0-F09, Teile V0-F10/F12/F13.

**Umfang:** Bootstrap, Request/Response-Objekte, einheitliche JSON-/Fehlerantworten, Request-ID, `/api/v0/`-Router und `index.php?route=...`-Fallback. ConfigLoader lädt Schema Version 1, prüft Pflichtwerte, reservierte Routennamen, erlaubte Methoden/Felder und API-Präfix. Kein Controller darf Datenbanktabellen allein aus dem Pfad publizieren.

**Beispielvertrag:** `GET /api/v0/health` oder Fallback kann einen **nicht sensitiven** Status melden; jeder Datenpfad ist ohne Schlüssel/Allowlist gesperrt.

**Negative Tests:** path traversal, doppeltes Encoding, unbekannte Ressourcen, fehlerhaftes JSON, nicht unterstützter Content-Type, nicht vertraute Host-/Proxy-Header, unzulässige Methoden.

**Gate:** gleiche HTTP-Semantik mit Rewrite und Query-Fallback; Konfigurationsfehler aktivieren keine API.

## 7. Slice 03 – API-Key, HTTPS, Field-/Method-Policy

**Funktionen:** V0-F06, F11–F13.

**Umfang:** `X-API-Key`-Authentifizierung; pro Instanz ein zufälliger Key aus geschützter Quelle, sicherer Hash-/Digestvergleich, Header-/Bodygrenzen. TLS ist Pflicht; Proxyvertrauen wird hostseitig nachgewiesen. ResourcePolicy blockiert unbekannte Tabellen, Spalten, Methoden und nicht freigegebene Filter. Eine feste öffentliche CORS-Allowliste kann eingerichtet werden, ist aber kein Benutzer-Login.

**Positive Tests:** valider Key erlaubt ausschließlich konfigurierten Zugriff.

**Negative Tests:** kein/falscher/widerrufener Key, fremder Instanzkey, unfreigegebene Ressource/Spalte, HTTP statt HTTPS, gefälschter Forwarded-Header. Fehler und Logs enthalten nie den Key.

**Gate:** Es existiert **kein** Weg von einem unberechtigten HTTP-Request zur Datenbankabfrage.

## 8. Slice 04 – Lesende CRUD-Routen mit MariaDB

**Funktionen:** V0-F02 und F12.

**Umfang:** `RecordRepository`-Port, `MariaDbPdoRepository`, sicherer Connection-Factory, typisierte Datensatz-IDs, `GET collection` und `GET by id`, nur freigegebene Spalten. Eine testbare MariaDB-Tabelle mit einem Primärschlüssel und reproduzierbarem Datensatz; keine Schemaänderung aus HTTP.

**Verträge:** Liste mit `data[]` und `meta`; Einzelwert mit `data{}`, fehlende ID 404, keine DB-Interna.

**Negative Tests:** unbekannter PK, SQL-Injection in ID, DB nicht erreichbar, verbotene Spalte, ungeplante Datentabelle.

**Gate:** Erfolgreiche GET-Tests, kontrollierter DB-Ausfall, PDO nicht im Controller.

## 9. Slice 05 – Schreibende CRUD-Routen

**Funktionen:** V0-F03 bis F05, F09, F12.

**Umfang:** `POST`, `PUT`, `PATCH`, `DELETE`; typisierte Input-Validatoren; Transaktion pro Request; bekannte Constraint-/Unique-/Foreign-Key-Fehler auf neutrale 4xx-Kategorien abbilden. PUT verlangt vollständige, klientseitig schreibbare Repräsentation; PATCH ändert nur vorhandene Felder. ID- und Systemfelder sind unveränderlich.

**Negative Tests:** Pflichtfeld fehlt, read-only Feld gesendet, PATCH leer, nicht existente ID, FK-Verletzung, Rollback nach Fehler, unverändertes UPDATE wird nicht als fehlender Datensatz fehlinterpretiert.

**Gate:** AT-V0-03; alle einzelnen CRUD-Wege bestehen ihren sicheren Commit-/Rollback-Test.

## 10. Slice 06 – Filter, Sortierung, Pagination

**Funktionen:** V0-F07 und F08.

**Umfang:** `filter[field]=value`, mehrere erlaubte Gleichheitsfilter mit AND; `sort=field` / `sort=-field`; `limit`/`offset` mit konfigurierten Schranken, stabile Sortierung nach PK. `hasMore` ggf. mit internem `limit+1` ohne Vollzählung. Alle Feldnamen gegen ResourceRegistry, Werte bindbar; keine freien SQL-Operatoren.

**Negative Tests:** unbekannter Filter/Sort, hohe oder negative Limits, SQL-Sonderzeichen, übergroße Filter, Paging ohne eindeutige Sortierung.

**Gate:** AT-V0-04, Schutz vor ungefilterter Ersatzabfrage, korrekte Antwortmetadaten.

## 11. Slice 07 – Logs, Health, Instanzisolation und Dokumentation

**Funktionen:** V0-F10, F14–F17.

**Umfang:** technische JSONL-Logs mit UTC/Request-ID/Status; kein Passwort, Key, PII-Body oder SQL-Bindwert. Health ohne private Interna; DB-Readiness nur geschützt. Zwei Testinstallationen aus demselben Codecommit mit unterschiedlichen URLs, DBs, Keys, Resource-Listen und Logpfaden. Dokumentierte PHP-Installation, Backup-/Restore-Aufgaben, Fallback und Beispiele.

**Positive Tests:** beide getrennten Instanzen nutzbar, Operator kann Fehler über Request-ID nachvollziehen.

**Negative Tests:** Schlüssel von A wird auf B abgewiesen; versehentlicher Abruf privater Log-/Config-Dateien scheitert; DB-Ausfall wird nicht als leeres Ergebnis maskiert.

**Gate:** AT-V0-08 bis AT-V0-10.

## 12. Slice 08 – Vollständige Release-Abnahme

Die Implementierung ist **erst fertig**, wenn alle 17 Anforderungen V0-F01–F17 erfüllt sind. [Teststrategie](teststrategie.md) und [Release-/Abnahmeverfahren](release-und-abnahme.md) enthalten die Gate-Bedingungen.

| Abnahmetest | Nachweis |
|---|---|
| AT-V0-01 | frischer Deployment- und Routingtest |
| AT-V0-02 | GET collection/record inkl. Leerliste/404 |
| AT-V0-03 | POST/PUT/PATCH/DELETE inkl. Datenbankpersistenz |
| AT-V0-04 | gleichheitsbasierte Filter, Sortierung, Paging |
| AT-V0-05 | Resource-/Field-/Method-Allowlists |
| AT-V0-06 | korrekter, falscher, fremder, widerrufener Key |
| AT-V0-07 | SQL-Injection, Datentypen, Request-Limits |
| AT-V0-08 | zwei vollständig getrennte Installationen |
| AT-V0-09 | Health, Logs, DB-Ausfall, keine Secrets |
| AT-V0-10 | dokumentierte Installation, Tests reproduzierbar |

**Freigabepaket:** Commit/Tag-Kandidat, Testumgebung, PHP-/MariaDB-Versionen, Testprotokoll, Security-Negativtests, Checkliste, dokumentierte Abweichungen, Backup-/Restore-Probelauf. Die Freigabe wird nicht simuliert.

## 13. Traceability und Weiterentwicklung

| Entwicklungs-Slice | Hauptanforderungen | Testschwerpunkte |
|---|---|---|
| 00–03 | F01, F06, F09–F13 | Router, Policy, TLS/Key, Config |
| 04 | F02, F12 | Read, DB und SQL-Schutz |
| 05 | F03–F05, F09 | Writes, Validation, Constraints |
| 06 | F07–F08 | Query-Budget, Paging |
| 07–08 | F10, F14–F17 | Betrieb, Isolation, vollständige Abnahme |

V1-Funktionen wie SQLite, Rollen, OpenAPI oder Joins **nicht** vorwegnehmen. Die V0-Schichten bleiben so klein wie möglich, die definierte Repository-Grenze verhindert jedoch einen späteren kompletten Neubau. Die V1-Slices werden erst nach bestandenem V0-Release gestartet.

## 14. Arbeitsweise in GitHub

Jeder Slice wird als Issue oder PR-Paket mit Ziel, Anforderungscodes, **Nicht-Zielen**, Checkliste, Tests und Dokumentationsänderung formuliert. PR-Review fragt: **(a)** werden Ressourcen nur konfiguriert freigegeben? **(b)** gibt es neue Angriffswege? **(c)** ist die Domain-/HTTP-/SQL-Trennung intakt? **(d)** ist der Code lesbar und kommentiert? **(e)** ist der Schritt reproduzierbar? Entwicklungsreihenfolge und Fortschritt stehen in Issues bzw. PRs; die Roadmap bleibt auf Ziele und Gates beschränkt.

**Aktueller Status:** Dieser Plan ist dokumentiert. Es wird weder ein fertiger Slice noch ein bestandener Test behauptet.
