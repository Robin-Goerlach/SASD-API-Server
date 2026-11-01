# SASD API Server

> **Sprache:** [English (Standard)](README.md) | **Deutsch**

**Ein schlanker, konfigurierbarer Open-Source-REST/JSON-Datenserver – eine gemeinsame Codebasis für mehrere unabhängige Anwendungen.**

SASD API Server ist ein Projekt von **SASD – Scientific and Software Development**. Ziel ist es, häufig wiederkehrende Datenzugriffsfunktionen nicht für jede Anwendung erneut zu entwickeln, sondern über einen **einheitlichen, ausdrücklich konfigurierten HTTP-Vertrag** bereitzustellen.

**Eine Codebasis. Mehrere unabhängige Installationen. Klare Konfiguration. Kontrollierte Zugriffe.**

Die erste Version bleibt bewusst klein: **PHP 8.4, MariaDB/MySQL, Einzel-CRUD, einfache Abfragen, API-Schlüssel und normales PHP-Shared-Hosting**. Die spätere V1 ergänzt SQLite, erweiterte Filter, Rollen, Audit und OpenAPI.

> [!IMPORTANT]
> **Projektstatus – 01.11.2026: Planung und Architektur.**
>
> Dieses Repository enthält derzeit Lasten- und Pflichtenhefte, Architekturdokumente, Roadmap, Entwicklungspläne und Betriebsrichtlinien. **Es existiert noch kein implementierter, abgenommener V0- oder V1-API-Server, kein freigegebenes Installationspaket und keine nachgewiesene erfolgreiche CI- oder Produktivinstallation.** Alle Endpunkte, JSON-/Konfigurationsbeispiele, Dateibäume und Diagramme beschreiben **geplantes Verhalten**, nicht bereits ausführbaren Code.
>
> Die bloße Existenz der Dokumentation ist **keine Produktivfreigabe**. Vor einem Einsatz sind Implementierung, Tests, echte Hostingprüfung, Sicherheitsreview und formale Abnahme erforderlich.

**Einstieg:** [Roadmap Deutsch](ROADMAP.de.md) · [Dokumentationsindex Deutsch](docs/README.de.md) · [Lastenheft V0](docs/requirements-v0.de.md) · [Lastenheft V1](docs/requirements-v1.de.md) · [Mitwirken](CONTRIBUTING.de.md)

---

## Inhalt

- [Motivation und Ziele](#motivation-und-ziele)
- [Grundidee des Servers](#grundidee-des-servers)
- [Projektstatus und Versionen](#projektstatus-und-versionen)
- [Funktionsumfang V0](#funktionsumfang-v0)
- [Geplanter Ausbau V1](#geplanter-ausbau-v1)
- [Architektur und Entwurf](#architektur-und-entwurf)
- [Geplante REST-API](#geplante-rest-api)
- [Beispielanfragen und JSON-Antworten](#beispielanfragen-und-json-antworten)
- [Konfigurierbare Ressourcen](#konfigurierbare-ressourcen)
- [Sicherheitsmodell](#sicherheitsmodell)
- [Persistenz und Datenintegrität](#persistenz-und-datenintegrität)
- [Hosting und Deployment](#hosting-und-deployment)
- [Vorgeschlagene Projektstruktur](#vorgeschlagene-projektstruktur)
- [Tests und Abnahme](#tests-und-abnahme)
- [Roadmap und Meilensteine](#roadmap-und-meilensteine)
- [Dokumentationsbibliothek](#dokumentationsbibliothek)
- [Referenzprodukte und Nicht-Ziele](#referenzprodukte-und-nicht-ziele)
- [Mitwirkung und Entwicklungsregeln](#mitwirkung-und-entwicklungsregeln)
- [Häufige Fragen](#häufige-fragen)
- [Lizenz und Projektlinks](#lizenz-und-projektlinks)

## Motivation und Ziele

Viele Anwendungen müssen ähnliche Datenoperationen unterstützen: Datensätze lesen, anlegen, ändern und löschen, Listen filtern, sortieren und seitenweise bereitstellen, Eingaben validieren, Berechtigungen prüfen und Fehler verlässlich melden.

Wenn jede Anwendung diese Infrastruktur separat entwickelt, entstehen doppelte Implementierungen, unterschiedliche API-Verträge und zusätzliche Stellen für Sicherheitsfehler.

**SASD API Server soll die gemeinsamen Datenzugriffsfunktionen zentral im wiederverwendbaren Programmcode kapseln.** Die Geschäftslogik verbleibt dort, wo sie hingehört: in der jeweiligen Fachanwendung.

### Mehrere Installationen statt einer zwingenden zentralen Datenbank

~~~mermaid
flowchart LR
    ClientA["Client-Anwendung A"] -->|"HTTPS/JSON"| ApiA["SASD API Server<br/>Instanz A"]
    ClientB["Client-Anwendung B"] -->|"HTTPS/JSON"| ApiB["SASD API Server<br/>Instanz B"]
    ApiA --> DbA[("MariaDB A")]
    ApiB --> DbB[("MariaDB B<br/>oder SQLite ab V1")]
    Shared["Gleicher freigegebener Programmstand"] -.-> ApiA
    Shared -.-> ApiB
~~~

Jede Instanz erhält **ihre eigene URL, Datenbankverbindung, Ressourcenkonfiguration, Schlüssel, Berechtigungen und Logs**. Es handelt sich also ausdrücklich nicht um einen freien zentralen SQL-Zugriff für alle Produkte.

### Zielgruppen

| Zielgruppe | Nutzen |
|---|---|
| Entwickler von SASD-Desktop- und Backend-Anwendungen | Gemeinsame, vorhersehbare CRUD-Schnittstellen |
| Clients in .NET, Java, PHP oder anderen Sprachen | HTTP/JSON statt direkter Kopplung an eine spezielle Datenbankbibliothek |
| Betreiber kleiner Installationen | Verschiedene APIs durch Konfiguration statt durch Code-Forks |
| Open-Source-Mitwirkende | Kleiner, modularer Code mit Lastenheften, Architektur, Test- und Entscheidungsnachweisen |

Ein allgemeiner API-Server ersetzt **keine fachliche Geschäftslogik**. Beispielsweise kann eine Bibliotheksausleihe nicht ohne Weiteres als Folge unabhängiger CRUD-Aufrufe abgebildet werden: fachliche Konsistenz und Geschäftsregeln bleiben in der Bibliotheksanwendung.

## Grundidee des Servers

Der Betreiber legt Ressourcen explizit fest. Eine Ressource verweist auf eine bestimmte Tabelle und definiert zulässige Methoden und Datenfelder.

Die Architektur folgt acht Prinzipien:

1. **Explizite Freigabe.** Nicht registrierte Tabellen bleiben unsichtbar.
2. **Datenminimierung.** Nur erlaubte Spalten werden gelesen oder geschrieben.
3. **Einheitliche HTTP-Semantik.** Routen, JSON-Objekte, Fehler und CRUD-Operationen sind eindeutig beschrieben.
4. **SQL bleibt serverintern.** Clients senden Ressourcen und Werte, niemals beliebige SQL-Anweisungen.
5. **Trennung von HTTP und Persistenz.** Ein Repository-Vertrag verhindert direkte Datenbankabhängigkeiten in Controllern.
6. **Sicherheit ab V0.** API-Key, HTTPS, Validierung, begrenzte Berechtigungen und sichere Fehler sind Pflicht.
7. **Unabhängige Installationen.** Gleicher Programmcode, getrennte Umgebungen.
8. **Schrittweiser Ausbau.** Zunächst kleine, getestete V0; später V1.

Wir wollen **keine möglichst große Plattform**, sondern eine kleine, zuverlässige, nachvollziehbar wartbare Grundlage.

## Projektstatus und Versionen

| Entwicklungsziel | Geplante Funktionen | Status |
|---|---|---|
| **V0.1.0** | PHP/MariaDB, CRUD, Allowlists, API-Key, Filter/Pagination, Health, Logging, Instanzisolation | **Noch nicht implementiert** |
| **V0.1.x** | Stabilisierung, Sicherheits- und Fehlerkorrekturen | Später |
| **V1.0.0** | Stabile V1-API, SQLite, Rollen, Views/Relationen, OpenAPI, Audit, Rate-Limits | **Noch nicht implementiert** |
| Danach | Weitere Datenadapter und Integrationen nur bei begründetem Bedarf | Ideen ohne Zusage |

Die Versionsnummer des Programms (`V0.1.0`) ist **nicht** die API-Pfadversion (`/api/v0/`). V0-Clients dürfen nicht stillschweigend ein inkompatibles V1-Verhalten erhalten.

Das V0-Lastenheft enthält **17 MUSS-Funktionen und 10 Abnahmeszenarien**, V1 zusätzlich **22 MUSS- und fünf SOLL-Funktionen sowie 16 Abnahmeszenarien**. Diese Zahlen beschreiben die Planung, **nicht** schon bestandene Tests.

[Roadmap Deutsch](ROADMAP.de.md) · [Lastenheft V0](docs/requirements-v0.de.md) · [Lastenheft V1](docs/requirements-v1.de.md)

## Funktionsumfang V0

| Funktion | Vorgesehenes Verhalten |
|---|---|
| Ressourcen | Nur explizit freigegebene Tabellen, Felder und HTTP-Methoden |
| Liste lesen | GET auf eine konfigurierte Sammlung |
| Einzelobjekt | GET anhand eines einspaltigen Primärschlüssels |
| Anlegen | POST genau eines validierten Datensatzes |
| Vollständiges Ändern | PUT ersetzt die klientseitig schreibbare Repräsentation |
| Partielles Ändern | PATCH verändert ausschließlich übertragene, erlaubte Felder |
| Löschen | DELETE genau eines adressierten Datensatzes |
| Filtern | Gleichheitsbedingungen mit AND-Verknüpfung |
| Sortieren | Eine erlaubte Spalte auf- oder absteigend |
| Paging | Begrenztes `limit` und `offset` |
| Validieren | Pflichtwerte, Datentypen, Länge, NULL, Feldfreigaben |
| Authentifizieren | Ein hochentropischer API-Key je Installation |
| Transport | HTTPS für geschützte produktive Endpunkte |
| SQL-Sicherheit | Prepared Statements und geprüfte SQL-Identifier |
| Fehler | Einheitliche HTTP-Codes, JSON-Fehler und Request-ID |
| Betrieb | Nicht sensitiver Health-Check, sanierte technische Logs |
| Deployment | Zwei voneinander isolierte Installationen aus gleichem Code |
| Tests | Unit, Integration, Contract, Security, Hosting und Recovery |

Erster Persistenzadapter ist **MariaDB/MySQL über PDO MySQL**. PostgreSQL, SQLite, JSON-Dateien, beliebige Remote-APIs und NoSQL gehören **nicht** zu V0.

**Explizite Nicht-Ziele:** frei ausführbares SQL, öffentliche DDL-/Schemabearbeitung, Massenänderungen, beliebige Joins, GraphQL, Web-Administrationsoberfläche, Benutzerregistrierung, SSO, verteilte Transaktionen und eine komplette Integrationsplattform.

## Geplanter Ausbau V1

V1 ergänzt die abgenommene V0 gezielt:

| Bereich | V1-Ziel |
|---|---|
| API-Versionierung | Stabiler V1-Vertrag und Kompatibilitätsprüfungen |
| Ressourcenmodell | Öffentliche Feldaliasse unabhängig von physischen Spalten |
| Datenhaltung | Zwei geprüfte Adapter: MariaDB/MySQL **und SQLite** |
| Queries | Vergleiche, IN, NULL, begrenzte AND-/OR-Gruppen und Textsuche |
| Ergebnisse | Feldprojektion, Sortierung, begrenzte Pagination |
| Relationen | Explizit definierte lesende Beziehungen mit maximal einer Ebene |
| Views | Konfigurierte SQL-Views nur zum Lesen |
| Authentifizierung | Mehrere technische Schlüssel mit Widerruf und Rotation |
| Autorisierung | Rollenrechte auf Ressourcen, Methoden, Felder und Relationen |
| Missbrauchsschutz | Rate-Limits und Query-Budgets ohne zwingendes Redis |
| Audit | Schreib-Audit im **gleichen lokalen DB-Commit** wie die Änderung |
| Dokumentation | Automatisch ableitbares OpenAPI |
| Betrieb | Geschützte Readiness, Config-Migration und Restore-Anleitungen |
| Clients | Beispiele für curl, Java und C#/.NET |

**Fünf V1-SOLL-Funktionen** bleiben zunächst optional: benannte lesende SQL-Abfragen, serverseitige Datensatzsicht, Optimistic Locking, kontrollierte Erweiterungscontroller und Betriebsmetriken. Sie werden nur bei ausdrücklicher Freigabe releaseverbindlich.

Ein Wechsel des Datenbankadapters **migriert keine Bestandsdaten**. Datenübernahme und fachliche Konsistenz müssen separat geplant werden.

[Pflichtenheft V1](docs/pflichtenheft-v1.md) · [Architektur V1](docs/architektur-v1.md)

## Architektur und Entwurf

### Komponentenübersicht

~~~mermaid
flowchart TB
    Client["Clients über HTTPS / JSON"]
    subgraph Server["SASD API Server – geplante V0"]
      Entry["index.php / Bootstrap"]
      Router["HTTP Router / Request / Response"]
      Guard["HTTPS / API-Key / Policy / Limits"]
      Ctrl["ResourceController"]
      Service["CrudService"]
      Registry["Ressourcenmodell und Validierung"]
      Port["RecordRepository"]
      Adapter["MariaDbPdoRepository"]
      Log["Fehlerabbildung / Health / sanierte Logs"]
      Entry --> Router --> Guard --> Ctrl --> Service --> Port --> Adapter
      Service --> Registry
      Guard -.-> Log
    end
    Client --> Entry
    Adapter --> DB[("MariaDB")]
~~~

Abhängigkeitsrichtung: **HTTP → Application → Domain/Contracts ← Infrastructure**. Nur Bootstrap muss die konkreten Komponenten zusammenführen.

| Baustein | Verantwortlichkeit | Darf nicht |
|---|---|---|
| Front Controller | Einstieg, Initialisierung | SQL/Fachlogik enthalten |
| HTTP-Router | Pfad, Methode, JSON, Antworten | beliebige Tabellennamen aus URL freigeben |
| Security/Policy | TLS, API-Key, Feld-/Methodenregeln | jeden gültigen Key als Administrator behandeln |
| Controller | HTTP-Auftrag in Anwendungsoperation übersetzen | PDO direkt aufrufen |
| CrudService | Validierung, CRUD, Transaktionen | SQL-Dialekt oder Server-Globals verwenden |
| ResourceRegistry | Tabellen, Spalten, Datentypen und erlaubte Funktionen | gesamtes Schema veröffentlichen |
| Repository-Port | Datenbankneutraler Operationsvertrag | HTTP-Status-/PDO-Typen transportieren |
| MariaDB-Adapter | Prepared SQL und Transaktionen | Authentifizierung aus HTTP interpretieren |
| Diagnose/Logs | Sichere Fehler, Request-ID, Health | Secrets oder ganze Bodies ausgeben |

### Geplanter Requestablauf

1. HTTPS-Request entgegennehmen und Request-ID erzeugen.
2. Pfad, Methode, Größe, Header und vertrauenswürdige TLS-Information prüfen.
3. Installationsspezifischen API-Key authentifizieren.
4. Ressource, Methode, Felder und Filter gegen eine **explizite Allowlist** prüfen.
5. JSON, IDs, Typen und Pagination validieren.
6. Freigegebene Operation über Repository-Port und Datenbankadapter ausführen.
7. Bei Schreibvorgängen committen oder bei Fehler rollbacken.
8. Standardisierte Antwort liefern, nur sanierte technische Logs erfassen.

V1 ergänzt Rollen, Query-Pläne, OpenAPI, Rate-Limits und transaktionales Audit.

## Geplante REST-API

> Alle Endpunkte sind **geplant und derzeit nicht ausführbar**. Die Ressource `books` ist ein rein illustratives Beispiel, kein integrierter Standard-Endpunkt.

| HTTP | Pfad | Bedeutung | Erfolg |
|---|---|---|---|
| GET | `/api/v0/books` | Liste lesen | 200 |
| GET | `/api/v0/books/42` | Einzelobjekt lesen | 200 |
| POST | `/api/v0/books` | Datensatz anlegen | 201 + Location |
| PUT | `/api/v0/books/42` | Vollersatz schreibbarer Felder | 200 |
| PATCH | `/api/v0/books/42` | Teiländerung | 200 |
| DELETE | `/api/v0/books/42` | Einzelobjekt löschen | 204 ohne Body |
| GET | `/api/v0/health` | knapper Status | 200/503 |

### Filter, Sortierung, Pagination

| Parameter | Beispiel | V0-Verhalten |
|---|---|---|
| Filter | `filter[published]=true` | Gleichheit auf freigegebenem Feld |
| Mehrere Filter | `filter[published]=true&filter[title]=Example` | AND-Verknüpfung |
| Sortierung | `sort=title` oder `sort=-title` | Erlaubte Spalte, auf-/absteigend |
| Seitengröße | `limit=25` | Konfigurierbare Obergrenze |
| Offset | `offset=0` | Beginn der zurückgegebenen Seite |

Vorgeschlagene Defaults: 25 Einträge pro Seite, höchstens 100; Offset zunächst maximal 10.000. Diese Werte sind **noch unter echtem Hosting zu prüfen**. Ohne Sortierwunsch wird ein stabiler Primärschlüssel verwendet. Fehlerhafte Filter dürfen nicht still ignoriert werden, sodass plötzlich alle Datensätze zurückgegeben werden.

### Unterschied zwischen PUT und PATCH

**PUT** fordert alle als klientseitig schreibbar konfigurierten Felder und ersetzt deren vollständige Repräsentation. Systemfelder bleiben unangetastet. **PATCH** verändert ausschließlich gesendete Felder. `null` ist nur erlaubt, wenn das Feld nullable ist. Ein Update darf nicht stillschweigend einen neuen Datensatz anlegen.

Eine Schreibanforderung entspricht einer lokalen Transaktion. V0 garantiert **noch keinen** Schutz vor Lost Updates konkurrierender Clients; optionales Optimistic Locking ist für V1 vorgesehen.

## Beispielanfragen und JSON-Antworten

Diese Befehle demonstrieren den **künftigen Vertrag**. Die Domain `api.example.test` ist eine Beispieldomain, kein laufender SASD-Endpunkt. `$SASD_API_KEY` steht für einen **geschützten Testschlüssel**.

### Liste lesen

~~~bash
curl --fail-with-body \
  --header "Accept: application/json" \
  --header "X-API-Key: $SASD_API_KEY" \
  "https://api.example.test/api/v0/books?limit=25&offset=0"
~~~

### Einzelobjekt lesen

~~~bash
curl --fail-with-body \
  --header "X-API-Key: $SASD_API_KEY" \
  "https://api.example.test/api/v0/books/42"
~~~

### Datensatz anlegen

~~~bash
curl --fail-with-body \
  --request POST \
  --header "Content-Type: application/json" \
  --header "X-API-Key: $SASD_API_KEY" \
  --data '{"title":"Example book","published":true}' \
  "https://api.example.test/api/v0/books"
~~~

### Einzelne Felder ändern

~~~bash
curl --fail-with-body \
  --request PATCH \
  --header "Content-Type: application/json" \
  --header "X-API-Key: $SASD_API_KEY" \
  --data '{"published":false}' \
  "https://api.example.test/api/v0/books/42"
~~~

### Routing-Fallback ohne funktionierendes URL-Rewriting

~~~bash
curl --fail-with-body \
  --header "X-API-Key: $SASD_API_KEY" \
  "https://api.example.test/index.php?route=/api/v0/books&limit=25"
~~~

Beide Routingformen müssen durch **dieselbe Authentifizierungs-, Policy-, Validierungs- und CRUD-Logik** laufen. Der Parameter `route` ist kein Dateisystempfad.

### Geplante Listenantwort

~~~json
{
  "data": [
    {"id":42,"title":"Example book","published":true}
  ],
  "meta": {
    "limit":25,
    "offset":0,
    "count":1,
    "hasMore":false
  }
}
~~~

`count` gibt die **Anzahl der Einträge auf dieser Seite** an, nicht die Gesamttrefferzahl. `hasMore` lässt sich durch maximal einen zusätzlichen internen Datensatz ermitteln.

### Geplante Einzelantwort

~~~json
{
  "data": {"id":42,"title":"Example book","published":true}
}
~~~

### Geplante Fehlerantwort

~~~json
{
  "error": {
    "code":"validation_failed",
    "message":"Invalid request body.",
    "details":[{"field":"title","code":"required"}]
  },
  "requestId":"9fc085c3b2a043d3"
}
~~~

Typische HTTP-Statuscodes: **200/201/204** Erfolg, **400** fehlerhafte Anfrage, **401** ungültiger Schlüssel, **403/405** verboten/unzulässige Methode, **404** nicht vorhanden, **409** DB-Konflikt, **413** Body zu groß, **415** falscher Content-Type, **422** fachlich ungültiger Feldwert, **429** Rate-Limit (V1/Hosting), **500/503** interner Fehler oder nicht verfügbar.

Keine SQL-Texte, Hostpfade, Kennwörter oder Stacktraces in Antworten. Mit der Request-ID kann der Betreiber technische Logs prüfen.

## Konfigurierbare Ressourcen

Die Konfiguration definiert, unter welchem externen Ressourcennamen eine Tabelle erreichbar ist und welche Felder und Operationen erlaubt sind. **Eine vorhandene SQL-Tabelle ist nicht automatisch öffentlich.**

### Beispiel einer geplanten V0-Konfiguration

~~~json
{
  "schemaVersion":1,
  "apiPrefix":"/api/v0",
  "instanceId":"books-test",
  "database":{
    "driver":"pdo_mysql",
    "hostEnv":"SASD_DB_HOST",
    "nameEnv":"SASD_DB_NAME",
    "userEnv":"SASD_DB_USER",
    "passwordEnv":"SASD_DB_PASSWORD"
  },
  "security":{"apiKeyEnv":"SASD_API_KEY","requireHttps":true},
  "limits":{
    "defaultPageSize":25,
    "maxPageSize":100,
    "maxOffset":10000,
    "maxBodyBytes":65536
  },
  "resources":{
    "books":{
      "table":"books",
      "primaryKey":{"field":"id","type":"integer"},
      "methods":["GET","POST","PUT","PATCH","DELETE"],
      "fields":{
        "id":{
          "type":"integer","readable":true,"clientWritable":false,
          "filterable":true,"sortable":true
        },
        "title":{
          "type":"string","maxLength":200,"required":true,
          "nullable":false,"readable":true,"clientWritable":true,
          "filterable":true,"sortable":true
        },
        "published":{
          "type":"boolean","required":true,"nullable":false,
          "readable":true,"clientWritable":true,
          "filterable":true,"sortable":false
        }
      }
    }
  }
}
~~~

**Das JSON ist ein Entwurf und noch keine lauffähige Konfigurationsdatei.** Es verweist lediglich auf Namen von Umgebungsvariablen. Echte DB-Zugangsdaten und Schlüssel liegen **getrennt und geschützt außerhalb des Document Root**. Die physische Tabelle wird nicht über öffentliche HTTP-DDL erstellt.

**Invarianten:**
- Eine unbekannte Ressource wird nicht automatisch freigeschaltet.
- Unbekannte oder gesperrte Felder dürfen weder gelesen, verändert, sortiert noch gefiltert werden.
- Eine Ressource kann nur GET erlauben.
- Read-only IDs sind möglicherweise sichtbar, aber nicht veränderbar.
- Ungültige Konfiguration führt zu **fail closed**, nicht zu All-access.
- Jede Installation besitzt ihre eigene DB, Konfiguration, Schlüssel und Logs.

V1 ergänzt Feldaliase, Views, Relationen und Rollen sowie eine versionierte Migration des Konfigurationsschemas.

## Sicherheitsmodell

Der API Server bildet eine Schutzgrenze vor persistenten Daten. Die Sicherheit muss deshalb **bereits zur Grundarchitektur gehören**.

| Risiko | Gegenmaßnahme |
|---|---|
| Versehentliche Veröffentlichung privater Tabellen | Explizite Allowlist / deny-by-default |
| Unerlaubte Spaltenzugriffe | Getrennte Read/Write/Filter/Sort-Rechte |
| SQL-Injection | Parametrisierte Werte, nur geprüfte Bezeichner |
| Geheimnisverlust | Keine Zugangsdaten in Git, URL, Logs, Fehlern oder öffentlichen Dateien |
| Unsicherer Transport | HTTPS und geprüfte Reverse-Proxy-Vertrauensgrenze |
| Überlast | Begrenzte Bodies, Filter, Seiten und Ergebnisgrößen |
| Interne Details in Fehlern | Zentraler ErrorMapper ohne SQL/Stacktrace |
| Cross-Instanz-Zugriff | Verschiedene DB-Konten, Konfigurationen, Keys und Logs |
| Falsche Browserannahme | CORS ist **keine** Authentifizierung |
| Falsch geschützte Dateien | Physische Ablage außerhalb des Webroots |

### API-Schlüssel ersetzen keine Benutzeridentität

V0 sieht einen hochentropischen Schlüssel je technischer Installation vor, übertragen im Header `X-API-Key`. Dieser authentifiziert **einen vertrauenswürdigen Client**, nicht einzelne Endbenutzer.

Ein in öffentlich ausgeliefertem JavaScript, einer weit verbreiteten mobilen Anwendung oder einem allgemein verfügbaren Desktopprogramm enthaltener Schlüssel ist **kein zuverlässig geschütztes Geheimnis**. Solche Anwendungsfälle benötigen einen sicheren Backend-Proxy oder eine ausdrücklich entwickelte Benutzeridentität. V1-Rollen allein machen das System nicht zu einem vollwertigen Identity Provider.

### HTTPS und Proxyvertrauen

`X-Forwarded-Proto` oder `Forwarded` dürfen nur dann zur TLS-Erkennung dienen, wenn ihre Herkunft im konkreten Hosting **nachweisbar vertrauenswürdig** ist. Ein Redirect schützt keinen schon über Klartext-HTTP gesendeten Key. Ungültige/mehrdeutige Pfade und doppeltes Encoding müssen sicher abgewiesen werden.

### Private Dateien als Release-Sperre

> **Konfiguration, Datenbankpasswörter, API-Schlüssel, Logs, Backups und spätere SQLite-Dateien müssen außerhalb des öffentlich auslieferbaren Document Root liegen.**
>
> Ein möglicherweise ignoriertes `.htaccess` genügt nicht. Ist die Trennung beim Provider nicht möglich, ist **keine produktive Freigabe zulässig**.

Sicherheitslücken bitte **vertraulich** melden, siehe [SECURITY.de.md](SECURITY.de.md).

## Persistenz und Datenintegrität

### V0: MariaDB/MySQL mit PDO

Der Datenbankadapter muss:

1. Clientwerte als Prepared-Statement-Parameter binden.
2. Tabellen-/Spaltennamen aus validierter Betreiberkonfiguration übernehmen.
3. Ausschließlich lesbare Felder projizieren; kein unbeschränktes `SELECT *`.
4. UPDATE/DELETE auf einen eindeutigen Primärschlüssel begrenzen.
5. Constraints und Transaktionsgrenzen beachten.
6. Unique-/FK-/Missing-/DB-Ausfälle als neutrale API-Fehler abbilden.
7. Unverändertes Update und fehlenden Datensatz unterscheiden; `rowCount()=0` allein reicht nicht.
8. Für jede Installation ein möglichst gering berechtigtes DB-Konto nutzen.

Die API übernimmt keine allgemeine fachliche Datenbankschemamigration per HTTP und ist auch keine automatische Backup-Software.

### V1: SQLite als zweiter Adapter

SQLite benötigt `pdo_sqlite`, eine private Datenbankdatei, `PRAGMA foreign_keys=ON` je Verbindung sowie getestete Busy-/Locking-/Journaling-Verfahren. WAL darf nicht pauschal auf jedem Netzdateisystem als sicher vorausgesetzt werden.

V1 verlangt zudem, dass **Datenänderung und verpflichtendes Auditereignis in derselben lokalen DB-Transaktion** committet werden. Ein fehlgeschlagenes Audit muss die Datenänderung zurückrollen. Das ist weder eine verteilte Transaktion noch eine zertifizierte, manipulationssichere Auditplattform.

Boolean, NULL, UUID, präzise Decimalwerte, UTC-Zeitstempel und Textvergleich müssen unter beiden Datenbanken durch gemeinsame Tests dieselbe zugesagte externe Semantik erhalten.

## Hosting und Deployment

### Zielumgebung

| Komponente | Geplante Basis |
|---|---|
| PHP | **8.4** |
| Datenbank | **MariaDB 10.11**, kompatibles MySQL nach Prüfung |
| Treiber | PDO MySQL |
| Hosting | Gewöhnliches PHP-Shared-Hosting, insbesondere IONOS als Zielumgebung |
| Öffentlicher Einstieg | Root-`index.php` |
| Routing | REST-Pfade bevorzugt, Query-Fallback bei eingeschränktem Rewrite |
| Verschlüsselung | HTTPS mit sicherer TLS-/Proxyprüfung |
| Private Konfiguration | Geschützte Umgebungsvariablen/Dateien außerhalb Webroot |
| Zusätzliche Dienste | Kein zwingender Docker-, Redis-, Root-, Queue- oder Dauerworkerbedarf |
| Spätere V1 | SQLite über PDO SQLite nach Dateisystem-/Lock-Test |

### Geplante physische Anordnung

~~~text
PRIVATE_ROOT/
  releases/<version>/
    src/
    vendor/
  instances/<id>/
    config/
    secrets/
    logs/
    backups/
    data/                  # erst V1 SQLite

DOCUMENT_ROOT/
  index.php                # öffentlicher Front Controller
~~~

Dieser Dateibaum ist ein **Architekturvorschlag**, keine vorhandene Serverstruktur. Die tatsächliche sichere Aufteilung ist beim Provider zu prüfen.

### Geplanter Installationsablauf (noch nicht ausführbar)

1. Tatsächliche PHP-/PDO-/DB-Version, HTTPS und private Verzeichnisse prüfen.
2. Eigenständige Datenbank und minimal berechtigten SQL-Benutzer bereitstellen.
3. Später freigegebene Software und Abhängigkeiten installieren.
4. Ressourcen-Konfiguration und Secrets getrennt und geschützt anlegen.
5. Konfiguration, Routing und Query-Fallback prüfen.
6. Positive CRUD-Tests mit reinen Testdaten ausführen.
7. Negative Tests: anonyme Zugriffe, fremde Felder, HTTP-Abruf privater Dateien.
8. Zweite Instanz mit abweichenden Credentials/Daten testen.
9. Backup mit tatsächlichem Restore auf eine separate Testumgebung nachweisen.
10. Commit, Versionen, Tests, Risiken und Freigabe dokumentieren.

**Es gibt derzeit weder einen lauffähigen Installer noch einen nachgewiesenen `composer install`-/`composer test`-Ablauf.** Details: [Betriebs- und Recovery-Runbook](docs/betrieb-und-deployment.md).

## Vorgeschlagene Projektstruktur

Dieser Quellcodebaum ist **ein Zielbild**, nicht der aktuelle Repository-Inhalt:

~~~text
index.php
composer.json

src/
  Bootstrap.php
  Http/
    Request.php
    Response.php
    Router.php
    JsonParser.php
    JsonResponder.php
    Controller/
      ResourceController.php
  Application/
    CrudService.php
    ListQuery.php
  Domain/
    ResourceDefinition.php
    FieldDefinition.php
    ApiError.php
  Contracts/
    RecordRepository.php
  Security/
    ApiKeyAuthenticator.php
    AccessPolicy.php
  Config/
    ConfigLoader.php
    ConfigValidator.php
  Infrastructure/
    Database/
      PdoFactory.php
      MariaDbPdoRepository.php
    Logging/
      SanitizedLogger.php
    Diagnostics/
      HealthService.php

config/
  example.resources.json

tests/
  Unit/
  Integration/
  Contract/
  Security/
  Deployment/
~~~

Die Struktur im Git-Repository ist von der **sicheren öffentlichen Deploymentstruktur** zu unterscheiden. Nicht jeder Quellordner darf über HTTP ausgeliefert werden. V1 ergänzt später SQLite, Query-Compiler, Rollen, Audit, Relationen und OpenAPI – **nicht bereits ungefragt in V0**.

## Tests und Abnahme

Ein normaler erfolgreicher CRUD-Aufruf reicht für einen Daten-API-Server nicht aus. **Negative Sicherheitstests und tatsächliche Hostingprüfungen sind Pflicht.**

| Testebene | Zu überprüfender Sachverhalt |
|---|---|
| Unit/Static | Syntax, Konfiguration, Router, Ressourcen-/Feldregeln, API-Key |
| MariaDB-Integration | CRUD, Constraints, Transaktionen, Rollback, Fehlermapping |
| HTTP-Contract | Methoden, Pfade, Header, JSON, Statuscodes, Routing-Fallback |
| Security | Unbekannte Tabellen/Felder, fremde Keys, Injection, übergroße Bodies, Secret-Leak |
| Deployment | Echte HTTPS-/PHP-Providerprüfung, kein direkter Abruf privater Dateien |
| Instanzisolation | Zwei Installationen, getrennte DBs/URLs/Keys/Logs |
| Recovery | Backup **und** erfolgreiches Restore auf Testsystem |
| V1-Adapter | Identischer zugesagter HTTP-/JSON-Vertrag unter MariaDB und SQLite |
| V1-Sicherheit | Rollen-/Relations-/Count-Leaks, Audit-Rollback, Rate-Fail-Closed |
| Dokumentation | Beispiele und OpenAPI stimmen mit tatsächlich implementierten Routen überein |

**V0-Gate:** V0-F01–F17 und AT-V0-01–10 nachweisbar bestanden.  
**V1-Gate:** Abgenommene V0 plus 22 verpflichtende V1-Funktionen, AT-V1-01–16 und beide Datenbankadapter.

Die fünf V1-SOLL-Funktionen dürfen nur nach dokumentierter Entscheidung verschoben werden. Jeder Testnachweis benötigt Commit, Umgebung, echte Befehle/Ergebnisse und bekannte Probleme. Ein schönes README, ein Diagramm oder bestandener Syntaxcheck sind **keine Produktabnahme**.

[Teststrategie](docs/teststrategie.md) · [Release und Abnahme](docs/release-und-abnahme.md)

## Roadmap und Meilensteine

| Meilenstein | Inhalt | Abnahmegate |
|---|---|---|
| **V0-G0** | Hosting, private Dateien, HTTPS und Proxy prüfen | Sicherheitskritische Randbedingungen nachgewiesen |
| **V0-M0** | Projektgerüst und Testumgebung | Reproduzierbares lokales Setup |
| **V0-M1** | Bootstrap, Routing, Fehler, API-Key und Transport | Unberechtigte Requests erreichen keine Daten |
| **V0-M2** | MariaDB-Repository und GET-Liste/Einzelobjekt | Allowlist-/SQL-Negativtests |
| **V0-M3** | POST, PUT, PATCH, DELETE mit Transaktionen | Integritäts-/Rollbacktests |
| **V0-M4** | Filter, Sortierung und Pagination | Query-/Limit-/Injectiontests |
| **V0-M5** | Logging, Health, zwei Instanzen und Betreiberanleitung | Sichere Trennung nachgewiesen |
| **V0-M6** | Gesamte V0-Sicherheits-, Hosting- und Fachabnahme | Alle V0-MUSS-Anforderungen und Tests grün |
| **V1-P0–P8** | Vertrag, SQLite, Query-Ausbau, Rollen, Audit, OpenAPI und Recovery | V1-Pflichtumfang auf beiden Backends geprüft |

**Nächster geplanter Schritt ist V0-G0**, danach V0-M0. Es gibt **keinen erfundenen verbindlichen Fertigstellungstermin**.

[Ausführliche Roadmap (Deutsch)](ROADMAP.de.md) · [Entwicklungsplan V0](docs/entwicklungsplan-v0.md)

## Dokumentationsbibliothek

| Dokumentart | V0 | V1 |
|---|---|---|
| Lastenheft Deutsch | [V0](docs/requirements-v0.de.md) | [V1](docs/requirements-v1.de.md) |
| Requirements Englisch | [V0](docs/requirements-v0.md) | [V1](docs/requirements-v1.md) |
| Pflichtenheft | [V0](docs/pflichtenheft-v0.md) | [V1](docs/pflichtenheft-v1.md) |
| Architektur | [V0](docs/architektur-v0.md) | [V1](docs/architektur-v1.md) |

Lastenhefte beantworten das **Was**, Pflichtenhefte das **Wie**, Architekturdokumente erläutern Bausteine, Schnittstellen, Sicherheitsgrenzen und das **Warum der Aufteilung**.

Weitere Dokumente:

| Datei | Inhalt |
|---|---|
| [Dokumentationsindex Deutsch](docs/README.de.md) | Navigation und Lesereihenfolge |
| [Roadmap Deutsch](ROADMAP.de.md) | Planung und Meilensteine |
| [Entwicklungsplan V0](docs/entwicklungsplan-v0.md) | Kleine technische Slices |
| [Teststrategie](docs/teststrategie.md) | Tests und Sicherheits-Gates |
| [Betrieb/Recovery](docs/betrieb-und-deployment.md) | Hosting, Konfiguration, Backup, Incident |
| [Release/Abnahme](docs/release-und-abnahme.md) | Nachweis und Freigabe |
| [Risikoregister](docs/risikoregister.md) | Hosting-/Security-/Portabilitätsrisiken |
| [ADR-Register](docs/adr/README.md) | Begründete Entscheidungen |
| [AGENTS Deutsch](AGENTS.de.md) | Regeln für KI-Coding-Agenten |
| [Mitwirken](CONTRIBUTING.de.md) | Projekt- und Codekonventionen |
| [Security Deutsch](SECURITY.de.md) | Vertrauliche Schwachstellenmeldungen |
| [Verhaltenskodex](CODE_OF_CONDUCT.de.md) | Zusammenarbeit |
| [Changelog Deutsch](CHANGELOG.de.md) | Änderungshistorie und spätere Releases |

Die funktionalen Anforderungen und der Produktvergleich sind bereits deutsch/englisch verfügbar. Die technische Dokumentation wird ebenfalls sprachweise ergänzt; **beide Fassungen müssen dieselben fachlichen Anforderungen und IDs behalten**.

## Referenzprodukte und Nicht-Ziele

| Vorbild | Relevante Anregung | Bewusste Grenze |
|---|---|---|
| [PHP-CRUD-API](https://github.com/mevdschee/php-crud-api) | Schlanker PHP-CRUD, Filter, Middleware, Konfiguration | Keine ungeprüfte automatische Tabellenfreigabe |
| [DreamFactory](https://github.com/dreamfactorysoftware/dreamfactory) | Services, Rechte, Konfiguration und Dokumentation | Keine komplette Integrations- und Verwaltungsplattform |
| [DB2Rest](https://github.com/9tigerio/db2rest) | SQL-Dialekte, kontrollierte Abfragen und Diagnose | Kein beliebiges SQL/RSQL oder unbegrenzte Connector-Vielfalt |

Die Vorbilder sind **Ideengeber**, nicht eine Verpflichtung, 157 Funktionen nachzubauen. Lizenzen, Editionen und konkrete Produkteigenschaften müssen bei Übernahme gesondert geprüft werden.

[Funktionskatalog Deutsch](docs/reference-products-feature-catalog.de.md) · [Feature Catalog English](docs/reference-products-feature-catalog.md)

**Ausdrückliche Nicht-Ziele:** Ersatz für WildFly/JBoss/WebLogic, öffentliche SQL-Konsole, Datenbank-Schemaeditor, vollständiger Identity Provider, Message Broker, Workflow-Engine oder Komplettpaket für FTP/S3/Mail/SOAP. Ein Admin-Dashboard ist keine V0- oder V1-MUSS-Funktion.

## Mitwirkung und Entwicklungsregeln

Beiträge in Form von Dokumentationsreviews, nachvollziehbaren Fehlerberichten, Testvorschlägen und später kleinen Codeänderungen sind willkommen.

1. Anforderung und Meilenstein benennen (z. B. `V0-F06` / `V0-M2`).
2. Kleinen Umfang und Nicht-Ziele festlegen.
3. HTTP, Service, Domain, Repository und Adapter getrennt halten.
4. Wichtige Architektur-/Sicherheitsentscheidungen verständlich und ausführlich kommentieren.
5. Positive **und negative** Tests durchführen; nur synthetische Testdaten verwenden.
6. Keine Zugangsdaten in Git, Issues, CI-Ausgabe oder Dokumentationsbeispielen.
7. API-/Config-Dokumentation bei Vertragsänderungen aktualisieren.
8. Längerfristige Architekturentscheidungen in [ADRs](docs/adr/README.md) festhalten.
9. Tatsächlich ausgeführte und **nicht ausgeführte** Tests korrekt angeben.
10. Keine vorzeitige Optimierung oder ungefragte V1-Ausweitung.

[CONTRIBUTING Deutsch](CONTRIBUTING.de.md) · [AGENTS Deutsch](AGENTS.de.md) · [Sicherheitsrichtlinie](SECURITY.de.md)

## Häufige Fragen

### Kann ich das Repository jetzt klonen und einen API Server starten?

**Noch nicht.** Aktuell enthält das Repository Pläne und Dokumentation. Ein laufender `index.php`-Server, `composer.json`, automatisierte Tests und ein freigegebenes Installationspaket existieren noch nicht.

### Warum PHP statt .NET oder Java?

Erstes Ziel ist normales PHP-Shared-Hosting, auf dem dauerhafte .NET-/Java-Prozesse oder Docker nicht garantiert werden können. Die externe HTTP-/JSON-Schnittstelle bleibt trotzdem aus .NET, Java und anderen Sprachen nutzbar.

### Brauche ich für jede Anwendung eine eigene Quellcodekopie?

**Keinen eigenen Code-Fork.** Mehrere Installationen verwenden denselben freigegebenen Code, aber eigene URL, DB, Konfiguration, Schlüssel und Logs.

### Werden alle vorhandenen Datenbanktabellen automatisch erreichbar?

**Nein.** Jede Ressource, Methode und jedes Feld wird ausdrücklich freigegeben.

### Kann ich lokale JSON-Dateien oder einen entfernten REST-Dienst als Backend verwenden?

Nicht in V0. Zuerst MariaDB/MySQL, in V1 SQLite. Weitere Adapter benötigen gesonderte Konsistenz-, Sperr-, Sicherheits- und Recoveryanforderungen.

### Ersetzt CRUD die Fachlogik meiner Anwendung?

Nein. Mehrstufige Geschäftsregeln und fachliche Transaktionen bleiben in den zuständigen Fachservices.

### Warum entwickeln wir nicht sofort eine grafische Administrationsoberfläche?

Die erste Version soll eine **kleine, sichere API** werden. Ein Admin-Dashboard erzeugt eigene Anforderungen für UI, Benutzerverwaltung und Sicherheit und kann später separat bewertet werden.

### Ist der API-Key in Browser- oder Desktopcode sicher?

Nicht zuverlässig, wenn der Client öffentlich verteilt wird. Ein vertrauenswürdiger Backenddienst oder eine separate Endbenutzer-Authentifizierung ist erforderlich. CORS ersetzt diese nicht.

### Wann erscheinen V0 und V1?

Es gibt **keinen zugesagten Termin**. Zuerst muss V0 entwickelt und vollständig geprüft werden. V1 folgt danach in kleineren, testbaren Schritten.

## Lizenz und Projektlinks

SASD API Server ist als Open-Source-Projekt unter der **[MIT-Lizenz](LICENSE)** vorgesehen. Bei Beiträgen und externen Bibliotheken gelten deren eigene Lizenz- und Hinweisbedingungen.

- **Projekt:** SASD API Server
- **Organisation:** SASD – Scientific and Software Development
- **GitHub:** [Robin-Goerlach/SASD-API-Server](https://github.com/Robin-Goerlach/SASD-API-Server)
- **Roadmap:** [ROADMAP.de.md](ROADMAP.de.md)
- **Dokumentation:** [docs/README.de.md](docs/README.de.md)
- **Änderungen:** [CHANGELOG.de.md](CHANGELOG.de.md)
- **Sicherheit:** [SECURITY.de.md](SECURITY.de.md)

---

### Nächster Schritt

**V0-G0 – die tatsächlichen Hosting- und Sicherheitsgrenzen prüfen:** private Dateien außerhalb des Webroots, belastbare HTTPS-Erzwingung, PDO-/MariaDB-Zugriff und funktionierender Routing-Fallback. Anschließend das kleine V0-Projektgerüst aufbauen und die Meilensteine mit echter Abnahme durcharbeiten.

**Unser Ziel ist nicht die größte API-Plattform, sondern ein kleiner, sicherer, verständlicher und wiederverwendbarer Server.**
