# SASD-API-Server – Softwarearchitektur V1

> **Sprache:** **Deutsch (bisheriger Dateipfad)** · [English (Standard)](architecture-v1.md) · [Deutsch (kanonisch)](architecture-v1.de.md)  

> **Status:** Architekturentwurf für ein späteres V1.0, nicht implementiert oder abgenommen.  
> **Dokumentversion:** 0.1 · **Stand:** 01.11.2026  
> **Anforderungen:** [Lastenheft V1](./lastenheft-v1.md) · [Pflichtenheft V1](./pflichtenheft-v1.md)  
> **Zwingende Basis:** [Architektur V0](./architektur-v0.md) · [Pflichtenheft V0](./pflichtenheft-v0.md)  
> **Recherche:** [Funktionskatalog der Referenzprodukte](./referenzprodukte-funktionskatalog.md)

## 1. Zielbild, Gültigkeit und Verhältnis zu V0

V1 ist die **kontrollierte Erweiterung** einer vollständig abgenommenen V0, nicht der Neubau eines großen Application Servers. Die Architektur verfolgt drei Ziele: (1) stabile, versionierte Daten-APIs für unterschiedliche SASD-Anwendungen, (2) zwei tatsächlich austauschbare Datenbankadapter mit gemeinsamem Vertrag und (3) sichere Mehrclient-Nutzung durch technische Identitäten und Rollen. Hinzu kommen kontrollierte Filter/Beziehungen, lesende Views, OpenAPI, Betriebssicherheit und Audit.

**Umfang:** Das V1-Lastenheft enthält **22 MUSS- und fünf SOLL-Funktionen**. Die SOLL-Bausteine (benannte Leseabfragen, datensatzbezogene Sichtgrenzen, optimistische Konflikterkennung, Erweiterungsmechanismen, Betriebsmetriken) sind als **optionale, separat freizugebende Erweiterungen** entworfen. Der Entwurf übernimmt nicht alle 157 recherchierten Produktfunktionen von PHP-CRUD-API, DreamFactory und DB2Rest.

**Erhaltung der V0-Eigenschaften:** PHP 8.4, Shared-Hosting-Tauglichkeit, Root-`index.php` mit Query-Fallback, private Secrets außerhalb des Document Root, keine obligatorischen Hintergrundprozesse/Redis/Docker, deny-by-default und klare Schichten. Wo ein V1-Contract bewusst anders wird, wird er unter `/api/v1/` eingeführt; V0-Clients dürfen keine stillschweigend inkompatiblen Antworten bekommen.

### 1.1 Qualitäts- und Architekturziele

| Ziel | Architekturprinzip | Abnahme |
|---|---|---|
| API-Stabilität | unabhängig versionierter REST/JSON-Vertrag | Contract-Tests, OpenAPI-Diff |
| Datenbank-Portabilität | QueryPlan, RecordRepository, Dialekt-Adapter | identische Tests für MariaDB und SQLite |
| Sicherheitsisolierung | Client-Key, Rolle, Ressourcen-/Feldpolicy; Default-Deny | negative Rollen-/Datenlecktests |
| Transparenz | Request-ID, technischer Log, transaktionales Write-Audit | Audit- und Fehlerprüfungen |
| Betrieb ohne Zusatzserver | PHP-Request-Prozess, Datei-/DB-basierte kleine Stores | Shared-Hosting-Deploy |
| Erweiterbarkeit ohne Entgrenzung | optionale registrierte Module; keine freie SQL-/Script-API | Security-Gates pro Erweiterung |
| Wiederherstellbarkeit | kontrollierte Migrations-/Key-/Config-Schritte | Restore- und Rollback-Probelauf |

## 2. Systemkontext und Instanzmodell (C4 Context)

~~~mermaid
flowchart LR
    Apps["SASD Clients<br/>HARALD / andere Anwendungen"]
    Admin["Betreiber / Deployment"]
    A["SASD API V1<br/>Instanz A"]
    B["SASD API V1<br/>Instanz B"]
    Maria[("MariaDB<br/>Daten A + Audit A")]
    SQLite[("SQLite-Datei<br/>Daten B + Audit B")]
    Apps -->|"HTTPS/JSON, eigener Key"| A
    Apps -->|"HTTPS/JSON, anderer Key"| B
    Admin -->|"private Config/Keys"| A
    Admin -->|"private Config/Keys"| B
    A --> Maria
    B --> SQLite
~~~

**Entscheidend:** „Universell“ bedeutet hier: **derselbe freigegebene API-Vertrag auf unterschiedlichen konfigurierten Backends**. Es bedeutet **nicht**, dass jeder Request dynamisch den Datenbankserver bestimmen darf, oder dass ein V1-Prozess gleichzeitig beliebig viele Datenbanken und Cross-DB-Joins verbindet. Pro Instanz genau eine konfigurierte primäre Datenquelle als verpflichtendes Betriebsmodell.

**Grenzen zu anderen SASD-Projekten:** Fachliche Transaktionen wie Buchausleihe, Rückgabe, Symptomeingabe mit Fachvalidierung oder komplexe Arbeitsabläufe bleiben in den jeweiligen Anwendungen/Domain Services. Der API-Server stellt allgemeine, gesicherte Datenressourcen bereit. Er ist keine Identity-Plattform für Endnutzer.

## 3. Container-, Deployment- und Trust-Boundary-Sicht

~~~mermaid
flowchart TB
    Request["HTTPS-Client"] --> Web["Webserver / TLS / PHP Handler"]
    subgraph Pub["Document Root"]
      Entry["index.php"]
    end
    Web --> Entry
    subgraph Priv["Private Pfade außerhalb des Document Root"]
      Code["src/ + PHP-Dependencies"]
      Resources["Resource Config V2"]
      Keys["Key-Store / Secrets / Role Policies"]
      Log["sanierte Logs + Rate-Limit-Store"]
      DBFile["SQLite-Datei (falls SQLite)"]
    end
    Entry --> Code
    Code --> Resources
    Code --> Keys
    Code --> Log
    Code --> DBFile
    Code --> Maria[("MariaDB (falls MariaDB)")]
~~~

**Sicherheitsgrenzen:** Client/Proxy, PHP/Dateisystem, PHP/Key-Store, PHP/Datenbank, Rolle/Ressource, Instanz/Instanz. TLS-/Proxyvertrauen wird **nicht** durch beliebige `X-Forwarded-Proto`- oder `Forwarded`-Header begründet. Datenbank-/Key-/Config-/Rate-/Audit-Dateien sind nicht per HTTP abrufbar. Die SQLite-Datei darf keinesfalls im Webroot liegen. Verlangt der Provider die Offenlegung privater Dateien, gilt die Installation als nicht produktionsfähig.

### 3.1 Betriebsprofile

- **Linux/Shared Hosting:** Webserver führt PHP-Skripte je Request aus; keine langlebige Prozesszustandsannahme. PHP- und PDO-Extensions, geschützte private Pfade und Sperrsemantik werden geprüft.
- **Windows/WSL Entwicklung:** dieselben Contract-Tests gegen MariaDB sowie ein isoliertes SQLite-Testschema; keine Abhängigkeit von Windows-spezifischen API-Verhalten.
- **Instanz A und B:** gleiche Codebasis, aber getrennte Ressourcendefinition, Schlüssel, Berechtigungen, DB-Verbindung, Logs und Health-Kontext.
- **Offline-Betriebswerkzeuge:** Konfigurationsprüfung, Key-Erzeugung/Widerruf, DB-Setup/Migration. Sie dürfen lokal auf einem Admin-Rechner laufen; ein öffentlicher Admin-HTTP-Endpunkt ist nicht vorgesehen.

### 3.2 Shared-Hosting-Kompromisse

Ohne Redis/Worker müssen Rate-Limits und konfigurierbare Schlüsselzustände in **requestübergreifend persistenten, atomar aktualisierbaren Stores** liegen. Für eine einzelne Hostinstanz können Dateisystem-Sperren oder die lokale Datenbank dienen, wenn nachgewiesene Schreibsemantik vorhanden ist. Eine „globale“ Rate-Garantie über mehrere separate Webserver ohne gemeinsamen Store wird nicht behauptet.

SQLite ist auf dem Zielhosting nur geeignet, wenn Datei-/Journaling-/Sperrrechte und tatsächliche parallele Schreiblast geprüft sind. WAL ist nicht pauschal für Netzdateisysteme zu empfehlen.

## 4. Komponentenarchitektur (C4 Components)

~~~mermaid
flowchart LR
    Router["HTTP Router<br/>Version / Query / JSON"]
    Auth["KeyAuthenticator"]
    Policy["RolePolicy / AccessGuard"]
    Ctrl["ResourceController"]
    Crud["CrudService"]
    Registry["ResourceRegistry"]
    Parser["QueryParser / Validator"]
    Port["RecordRepository<br/>TransactionPort"]
    SQL["QueryCompiler / Dialect"]
    Maria["MariaDbPdoAdapter"]
    Lite["SqlitePdoAdapter"]
    Audit["AuditEventWriter"]
    Docs["OpenApiGenerator"]
    Router --> Auth --> Policy --> Ctrl --> Crud
    Crud --> Registry
    Crud --> Parser
    Crud --> Port
    Port --> SQL
    SQL --> Maria
    SQL --> Lite
    Crud --> Audit
    Registry --> Docs
    Policy --> Docs
~~~

### 4.1 Regel der Abhängigkeitsrichtung

`HTTP → Application → Domain/Contracts ← Infrastructure`. Der Composition Root kennt konkrete Adapter. Controller dürfen niemals PDO instanziieren oder SQL zusammenbauen; Repository-Ports enthalten keine HTTP-Statuscodes. Der Query-Compiler nimmt **nur typisierte, vorvalidierte** Filter-/Sort-/Relationsobjekte, niemals rohe SQL-Fragmente entgegen. Die Policies sind serverseitig autoritativ.

### 4.2 Verantwortlichkeiten

| Baustein | Verantwortung | Wichtige Invariante |
|---|---|---|
| Router/RequestParser | V1-Pfade, Header, JSON, Queryparameter, begrenzte Syntax | keine DB-Bindings aus Request |
| ApiKeyAuthenticator | Key-ID-/Digest-Prüfung, Active/Revoked-Status | Secret niemals im Log |
| RolePolicy/AccessGuard | Methode, Ressource, Feld, View, Relation, Query | default-deny auf allen Zugriffspfaden |
| ResourceRegistry | öffentliche Aliasnamen, physische Spalten/Typen, Constraints | Schema-Introspektion ist niemals Freigabe |
| CrudService | einzelne CRUD-Anwendungsfälle, Transaktions- und Auditregeln | kein erfolgreiches Write ohne erfolgreiches Audit |
| QueryParser/QueryPlan | Operatoren, AND/OR, Sort, Paging, Projection, Expand | validierte AST- und Budgetgrenzen |
| RecordRepository/TransactionPort | datenbankneutrale Persistenz inkl. Unit-of-Work | keine PDO-Objekte im Application-Layer |
| MariaDbPdoAdapter | MariaDB-SQL, Typkonvertierung, DB-Fehler | gemeinsame Contract-Tests |
| SqlitePdoAdapter | SQLite-SQL, PRAGMA, Busy-Handling, Datei-Rechte | gleiche API-Semantik |
| AuditEventWriter | append-only-in-fachlicher-Hinsicht Write-Audit **im selben lokalen DB-Commit** | kein stilles Audit-Fehlschlagen |
| OpenApiGenerator | rollenabhängige Routen/Felder/Schemas dokumentieren | kein unberechtigtes Schema-Exposure |
| ConfigMigrator | V0→V1, Config-Schema-Check, gesicherter Wechsel | unbekannte Rechte bleiben geschlossen |
| Health/Readiness/Logger | minimale öffentliche Gesundheit, geschützte Diagnosen, redigierte Logs | keine Secret-/Schema-Leaks |
| RateLimiter | bounded Requests je technischem Client und gesperrte unbekannte Key-Versuche | sicherer persistenter Zustand, Fail-Closed |

### 4.3 Vorgeschlagene Strukturänderungen gegenüber V0

~~~text
src/
  Http/{Router,MiddlewareStack}.php
  Http/Controller/{ResourceController,OpenApiController,HealthController}.php
  Http/Parser/{ListQueryParser,JsonBodyParser}.php
  Application/{CrudService,RelationReadService,NamedReadService}.php
  Application/Query/{FilterExpression,SortSpec,PageSpec,QueryPlan}.php
  Domain/{ResourceRegistry,ResourceDefinition,FieldDefinition,RelationDefinition}.php
  Contracts/{RecordRepository,TransactionPort,AuditEventWriter}.php
  Infrastructure/Database/{MariaDbPdoAdapter,SqlitePdoAdapter,SqlDialect}.php
  Infrastructure/Audit/SqlAuditWriter.php
  Infrastructure/Diagnostics/{ReadinessService,MetricsCollector}.php
  Security/{ApiKeyStore,RolePolicy,RequestRateLimiter,RecordVisibilityPolicy}.php
  OpenApi/{SchemaBuilder,OpenApiGenerator}.php
  Config/{ConfigV1Loader,ConfigValidator,ConfigMigrator}.php
bin/{config-check,keys-create,keys-revoke,config-migrate}
tests/{Unit,Integration,Contract,Security,Migration,Deployment}/
~~~

Die Gliederung ist eine Architekturhilfe, kein Nachweis vorhandener Dateien und keine Anforderung, für jedes Konzept eine Klasse zu schreiben. Besonders die fünf optionalen SOLL-Komponenten werden **nur** implementiert, wenn sie priorisiert und getestet werden.

## 5. Versionierter HTTP- und Datentypvertrag

### 5.1 V1-Endpunkte

| Methode | Pfad | Funktion |
|---|---|---|
| GET | `/api/v1/{resource}` | Liste, Filter, Sort, Projektion, Paging, optional Include |
| GET | `/api/v1/{resource}/{id}` | Einzelressource |
| POST | `/api/v1/{resource}` | einzelner Insert |
| PUT/PATCH | `/api/v1/{resource}/{id}` | Vollersatz / partielles Update |
| DELETE | `/api/v1/{resource}/{id}` | einzelner Delete |
| GET | `/api/v1/openapi.json` | authentifizierte, autorisierte OpenAPI-Sicht |
| GET | `/api/v1/health` | minimaler Status |
| GET | `/api/v1/ready` | Operator-Readiness mit Auth/Policy |
| GET | `/api/v1/queries/{name}` | **SOLL**: konfigurierter read-only Report |

Das Query-Routing über `index.php?route=/api/v1/...` bleibt funktionsgleich. Der V0-Pfad wird nicht heimlich auf V1 umgeschrieben. Jede Veränderung öffentlicher Feldnamen, Fehler-/HTTP-Semantik oder Query-Grammatik wird über Contract-Tests und ggf. eine neue Version abgesichert.

### 5.2 Konsistente JSON-Ausgabe und Fehler

~~~json
{
  "data": [{"id": 42, "title": "Architecture"}],
  "meta": {"limit": 25, "offset": 0, "count": 1, "hasMore": false}
}
~~~

~~~json
{
  "error": {"code": "forbidden", "message": "Access is not permitted.", "details": []},
  "requestId": "d23f20a5304d4bcb"
}
~~~

Nur erlaubte Felder erscheinen in `data` und OpenAPI. `count` beschreibt standardmäßig **Seitengröße**, nicht das Gesamttrefferresultat; eine explizite Total-Count-Funktion bedarf eigener Kosten- und Rechteprüfung. 401/403/404/409/422/429/500/503 sowie ggf. 412/428 bei optionalem Optimistic Locking werden zentral gemappt. Weder SQL-Statement noch private Schema- oder Dateidetails dürfen in Antworten stehen.

### 5.3 Typabbildung über Datenbanken

| API-Typ | Drahtformat | Implementierungsregel |
|---|---|---|
| string | UTF-8 JSON-String | Feldlänge, sichere Zeichenverarbeitung |
| integer | JSON-Integer innerhalb sicherer Interoperabilitätsgrenze | IDs größer als 2^53−1 nicht ungeprüft als JSON-Zahl |
| UUID | kanonischer, kleingeschriebener String | dedizierte Konvertierung |
| boolean | `true` / `false` | MariaDB TINYINT(1), SQLite INTEGER kontrolliert normalisiert |
| decimal | dezimaler String | präzise Werte nicht unbemerkt durch PHP float runden |
| datetime | ISO 8601 UTC, z. B. `2026-11-01T14:30:00Z` | keine implizite lokale Zeitzonenänderung |
| null | JSON null | nur bei entsprechender Felddefinition |

**Qualitätsfalle:** MariaDB und SQLite besitzen verschiedene Typ-/Kollationsregeln. Auch ein identisches SQL-Statement garantiert nicht die gleiche fachliche JSON-Semantik. Die Adaptertests müssen Boolean, NULL, Zeit, Decimal, Textvergleich und Sonderzeichen mit gemeinsamen Beispieldaten prüfen. Inkompatible Spezialtypen sind zu **verbieten oder explizit zu dokumentieren**, nicht still falsch zu konvertieren.

## 6. Dynamische Sichten und kritische Datenflüsse

### 6.1 Autorisierte Leseanfrage

~~~mermaid
sequenceDiagram
    participant C as Client
    participant G as Auth/RolePolicy
    participant Q as QueryParser
    participant S as CrudService
    participant R as RecordRepository
    C->>G: GET /api/v1/books?include=author
    G->>G: Key, Rolle, Ressource/Methode
    G->>Q: erlaubte Felder und Relationen
    Q->>Q: typsicherer QueryPlan + Budget
    Q->>S: erlaubter QueryPlan
    S->>R: list(plan)
    R-->>S: rows + freigegebene Relation
    S-->>C: 200 data/meta ohne private Felder
~~~

Die Rolle wird auch auf Filter-, Sortier- und Relationenziele angewendet, nicht nur auf den abschließenden Response-Body. Ohne Rechte darf ein Client weder durch `count`, fehlerhafte Relation noch durch Filter auf Geheimspalten deren Existenz oder Werte erfahren.

### 6.2 Transaktionaler Write mit Audit

~~~mermaid
sequenceDiagram
    participant Client
    participant Policy
    participant Service as CrudService
    participant DB as SQL-Adapter/DB
    Client->>Policy: PATCH/PUT/POST/DELETE + Key
    Policy->>Policy: Rolle/Felder/Limit prüfen
    Policy->>Service: validierter Write-Befehl
    Service->>DB: BEGIN
    Service->>DB: Änderung unter Constraint/ID-Prüfung
    Service->>DB: INSERT internes AuditEvent
    alt beide erfolgreich
      Service->>DB: COMMIT
      Service-->>Client: 2xx + persistierter Zustand
    else Fehler, Audit nicht möglich
      Service->>DB: ROLLBACK
      Service-->>Client: 4xx/5xx ohne Secret
    end
~~~

Das **Write-Audit ist Teil der lokalen Commit-Grenze**. Ein erfolgreiches Update ohne erfolgreich persistierten notwendigen Auditeintrag gilt als Fehler und wird zurückgerollt. Abgewiesene Zugriffsversuche besitzen keinen Fachdaten-Commit und werden stattdessen datensparsam als geschütztes technisches Sicherheitsereignis geloggt. **Keine verteilte Transaktion** und keine Audit-Zertifizierung werden zugesagt.

### 6.3 Rate-Limit-Entscheidung

~~~mermaid
flowchart TD
    H["Request mit Key-ID / IP-Kontext"] --> Auth{"Authentifiziert?"}
    Auth -->|"nein"| Invalid["begrenzter Missbrauchszähler<br/>401/429"]
    Auth -->|"ja"| Store{"Atomarer Rate-Store nutzbar?"}
    Store -->|"nein"| Closed["503 Fail Closed"]
    Store -->|"ja"| Budget{"Budget verfügbar?"}
    Budget -->|"nein"| Limit["429 + ggf. Retry-After"]
    Budget -->|"ja"| Next["Policy + Business Request"]
~~~

Bei Shared Hosting dürfen Rate-Kennzahlen **nicht** bloß in PHP-Static-Variablen stehen; diese überleben den nächsten Request nicht zuverlässig. Ein Dateistore benötigt nachweislich sichere `flock`-/Atomizitätssemantik oder eine geeignete Datenbanktabelle. Client-IP-Header gelten nur bei vertrauenswürdiger Herkunft. Der gewählte Ansatz ist auf eine Instanz zugeschnitten, nicht automatisch ein verteilter Cluster-Limiter.

## 7. Konfiguration und Ressourcenmodell V2

### 7.1 Vertragsmodell

Die Ressourcen-Konfiguration wechselt auf `schemaVersion: 2`. `ResourceRegistry` definiert für jede Ressource (a) öffentlichen Namen, (b) physische Tabelle oder lesende View, (c) Primärschlüssel, (d) sichtbare Feld-Aliasse mit Typ/Nullable/Write/Read, (e) erlaubte Methoden, (f) eventuell Relationsdefinition, (g) rollenabhängige Freigaben. Detaillierte Schlüssel/RolePolicies liegen separat in geschützter Konfiguration.

~~~json
{
  "schemaVersion": 2,
  "instanceId": "harald-test",
  "apiPrefix": "/api/v1",
  "database": {"adapter": "mariadb", "connectionRef": "MAIN_DB"},
  "resources": {
    "books": {
      "source": {"kind": "table", "name": "library_books"},
      "primaryKey": {"field": "id", "column": "id", "type": "integer"},
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {"column": "id", "type": "integer", "read": true, "write": false},
        "title": {"column": "book_title", "type": "string", "read": true, "write": true}
      },
      "relations": {
        "author": {
          "target": "authors",
          "localColumn": "author_id",
          "remoteColumn": "id",
          "cardinality": "many-to-one",
          "maxDepth": 1
        }
      }
    }
  }
}
~~~

Dies ist ein **verkürztes Schemaexempel**, keine einsatzfähige Konfiguration. Die Relation setzt eine getrennt freigegebene Zielressource `authors` voraus. `connectionRef` verweist auf vertrauenswürdige private Credentials und kann **nicht** per Request überschrieben werden.

### 7.2 Aliase, Views, Relation und Schema Discovery

Ein öffentliches Feld `title` kann auf die DB-Spalte `book_title` zeigen. Das verringert Kopplung von Client und physischem SQL-Schema. Eine View mit `source.kind=view` ist in V1 **immer read-only**, auch wenn die Datenbank updatable Views unterstützt. Schema-Introspektion darf zur Prüfung und als Entwicklerhilfe dienen, aber **nie** neue REST-Ressourcen automatisch freigeben.

Eine deklarierte Relation mit `include=author` erweitert nur **eine** Ebene. Menge, Kardinalität, Spalten und Berechtigung des Ziels werden vor SQL-Ausführung validiert. Join-Strategien sind adapterintern; ein unbounded N+1-Request pro Kind ist zu vermeiden. Übermäßige Expansion wird kontrolliert abgewiesen und nicht still durch unvollständige Sicht ersetzt.

### 7.3 Filter als typisierter AST statt SQL

~~~text
HTTP-Parameter
    -> Parse + Syntaxvalidierung
    -> erlaubte Felder/Operatoren aus ResourceRegistry
    -> RolePolicy + Typprüfung
    -> FilterExpression / SortSpec / PageSpec / RelationSpec
    -> QueryPlan (nur validierte AST-Knoten)
    -> SQL-Dialektcompiler
    -> Prepared SQL und Parameterbinding
~~~

Vorgeschlagene Operatoren: `eq`, `ne`, `lt`, `lte`, `gt`, `gte`, `in`, `isNull`, `contains` und `startsWith`. Mehrere AND-Bedingungen und **eine** kontrollierte OR-Gruppe; keine rekursive, frei mächtige RSQL-Sprache. Projektion: `fields=id,title`. Sortierung: `sort=title,-id` mit stabiler Schlüssel-Tie-Breaker-Regel. Paging: `limit/offset`. Die im Pflichtenheft genannten Budgets (beispielsweise acht AND-Bedingungen, acht OR-Glieder, maximal drei Sortierspalten und eine Relationstiefe) sind **zu messende Entwurfsdefaults**, keine bereits geprüften Betriebswerte.

**Textvergleich:** Unicode-/LIKE-/Kollationsunterschiede zwischen MariaDB und SQLite werden **nicht** mit einem unbegründeten Anspruch auf identische Volltextsuche überdeckt. Der gemeinsame garantierte Suchumfang wird anhand konkreter Testfixtures definiert. Betreiber können darüber hinaus datenbankspezifische Spezialfunktionen **nur außerhalb** des gemeinsamen Pflichtvertrags nutzen.

## 8. Zwei Datenbankadapter mit einem gemeinsamen Vertrag

~~~mermaid
flowchart TB
    App["CrudService / QueryPlan"]
    Port["RecordRepository + TransactionPort"]
    App --> Port
    Port --> Maria["MariaDbPdoAdapter<br/>PDO mysql"]
    Port --> SQLite["SqlitePdoAdapter<br/>PDO sqlite"]
    Maria --> DB1[("MariaDB")]
    SQLite --> DB2[("SQLite")]
    Tests["Gemeinsame Contract-Test-Suite"] -.-> Maria
    Tests -.-> SQLite
~~~

### 8.1 MariaDB/MySQL

Beibehaltung von PDO, Prepared Statements, `utf8mb4`, kontrolliertem SQL-Identifier-Quoting, transaktionalen Writes und neutralem DB-Fehlermapping. Kein generischer SQL-Ausdruck und keine DDL aus HTTP.

### 8.2 SQLite

`pdo_sqlite`, DB-Datei außerhalb des Webroot, restriktive Dateirechte, `PRAGMA foreign_keys=ON` **bei jeder Verbindung**, gesetzter `busy_timeout`, definierte Transaktions- und Lock-Behandlung, getestete Konfiguration für WAL/Journalmodus. Ein `SQLITE_BUSY`-Fehler wird kontrolliert in eine nicht sensible, retry-fähige Fehlerklasse übersetzt; unendliche Retries sind ausgeschlossen. Keine Clientparameter für DB-Dateipfade.

### 8.3 Vertrag und Testinvarianten

- Gleiche logische Ressource und gleiche Testdaten ergeben unabhängig vom Backend dasselbe HTTP-/JSON-Schema, dieselben Pflichtfunktionen und dieselben Fehlerkategorien.
- Ein Adapterwechsel **migriert keine Bestandsdaten**. Der Betreiber erzeugt Datenbanken und führt eine explizite, fachlich geprüfte Übernahme durch.
- Composite Keys, Cross-DB-Joins und zusätzliche SQL-Backends sind nicht Teil des Pflichtumfangs.
- Writer-Audit und fachliche Änderung nutzen **dieselbe lokale Datenbanktransaktion**. Für SQLite darf Audit nicht unbemerkt in eine zweite, nicht atomare Datenbankverbindung ausgelagert werden.
- SQL-Constraints, Nullability, Boolean- und Decimal-Normalisierung sowie Sortier- und Filtersemantik werden durch gemeinsame Blackbox-Tests überprüft.

## 9. Sicherheit und Identitätsmodell

### 9.1 API-Key-Lebenszyklus

V1 unterstützt mehrere **technische Clientidentitäten** je Instanz. Ein Offline-Werkzeug erzeugt kryptografisch zufällige, hochentropische Keys (Planwert mindestens 256 Bit). Das Secret wird einmalig ausgegeben, der Key-Store hält eine nicht geheime ID, sicheren Prüfdigest (für hochentropische Zufallstoken beispielsweise SHA-256), Status und Rollenzuordnung. Lookup nach Key-ID, anschließender konstantzeitlicher Digestvergleich; Statuskontrolle vor jeder Rechteentscheidung. Rotation und Widerruf sind durch geschützte Konfigurationsänderung ohne Codeänderung möglich.

**Keine öffentliche Key-Administrationsroute.** Browser, mobile/öffentlich verteilte Anwendungen und Endnutzer benötigen zusätzliche Vertrauens-/Authentifizierungsschichten, die nicht V1-Kernbestandteil sind.

### 9.2 Rollen und durchgehende Freigabe

~~~text
Authentifizierter technischer Client
  └─ RolePolicy
      ├─ Resource erlaubt?
      ├─ HTTP-Methode erlaubt?
      ├─ Request-Felder und Response-Projektion erlaubt?
      ├─ Filter-/Sortierspalten erlaubt?
      ├─ Relation-/View-Ziel erlaubt?
      └─ Optional: serverseitige Datensatz-Sichtgrenze?
           └─ dann erst QueryPlan → Repository
~~~

Die Operatorrolle darf Diagnosen abfragen, bekommt aber **nicht automatisch alle Fachdaten**. Default für neue Ressourcen, Methoden und Felder bleibt **gesperrt**. Auch OpenAPI darf eine Rolle nicht über verbotene Tabellen informieren.

### 9.3 Optional: Datensatz-Sichtgrenzen (SOLL V1-F14)

Ein optionaler, serverseitig ermittelter Filter etwa `owner_id = serverAssignedOwner` kann an **jedes** Read-/Count-/Write-/Relation-Prädikat gebunden werden. Dieser Wert darf **nicht** aus frei veränderbarem Client-Querystring stammen. Ohne vollständig grüne Isolationstests auf MariaDB und SQLite wird die Funktion nicht freigegeben oder als Multi-Tenancy beworben.

### 9.4 Threat Model V1

| Risiko | Kontrollpunkt | Architekturtest |
|---|---|---|
| Key-Diebstahl/Fehlzuweisung | privater Key Store, Revokation, Rollen | widerrufener Key sofort wirkungslos |
| Privilegieneskalation | RolePolicy für Route/Methode/Feld/Relation | Reader kann nicht schreiben |
| Data-Leak über Filter/Count/Expand/OpenAPI | gleiche Policy vor QueryPlan und Schemaerzeugung | verbotene Felder bleiben unsichtbar |
| SQL-Injection | geprüfte AST-Felder + Parameterbindung | SQL-Fragmente bleiben Werte |
| Instanzübergriff | getrennte Keys/DBs/Logs | Key A erhält keine B-Daten |
| Unsichere SQLite-Datei | Pfad außerhalb Webroot + Rechte | direkter HTTP-Abruf scheitert |
| Client-Overload | Rate Limits, Body-, Query-, Include-Limits | 429/413/4xx, keine unbegrenzte Query |
| Audit-Bypass | Write + Audit im selben Commit | simulierte Audit-Persistenzstörung rollt Write zurück |
| Fehlkonfiguration | Config-Schema-/Role-/Relation-Validator | Fail-Closed, keine breite Defaultrolle |
| TLS-/Proxy-Bypass | Host-seitig erzwungenes HTTPS, vertrauenswürdige Header | HTTP/gefälschte Forwarded-Header helfen nicht |

## 10. Audit, Health, Logging und Datenminimierung

### 10.1 Audit in derselben Datenbank

Eine interne Tabelle `sasd_api_audit_events` wird **pro Instanzdatenbank** durch einen **Offline-Operator-Migrationsschritt** bereitgestellt. Vorgesehene Felder: `event_id`, `occurred_at_utc`, `instance_id`, `request_id`, `client_id`, `resource_name`, `method`, `record_key`, `outcome`. Keine kompletten Datensatz-Bodies oder API-Secrets; keine internetöffentliche Lese-API auf das Audit-Schema.

Im Write-Flow gilt: **BEGIN → Datenänderung → Audit-Insert → COMMIT**. Misslingt Audit, erfolgt Rollback. Bei abgewiesenen Writes ohne Fachdatenzugriff wird nur eine sanierte technische Sicherheitsspur geschrieben. Ein auditierter Commit ist keine Garantie gegen Manipulation durch den DB-Administrator und keine „revisionssichere“ Zertifizierung.

### 10.2 Health und Readiness

`/api/v1/health` ist auf nicht-sensitive Zustände reduziert. `/api/v1/ready` ist eine **operatorgeschützte** Route und prüft Konfiguration, Datenbankadapter, DB-Zugang und notwendige interne Audit-Strukturen. Eine öffentliche Statusantwort nennt keine DB-Dateipfade, SQL-Versionen, interne Tabellen oder Zugangsdaten.

### 10.3 Logs und optional Metriken

JSONL-Logs mit UTC, Request-ID, Instanz, Route, Status, Fehlerklasse, berechtigter technischer Clientkennung und knapper Zeitmessung – niemals unredigierte Payloads, Passwörter oder Auth-Header. Zugriffsrechte/Retention/Rotation werden betrieblich definiert. **SOLL V1-F27:** datensparsame Aggregate für Requests, Fehler und Latenz; kein Prometheus, Grafana oder Datadog notwendig.

## 11. OpenAPI und sprachunabhängige Clients

`OpenApiGenerator` leitet Pfade, Schemas, Queryparameter, Fehlerformate und Key-Auth-Schema aus derselben autorisierten ResourceRegistry ab, die zur Laufzeit verwendet wird. **Rollenabhängige Dokumentationssicht:** Clientrollen sehen nur die für sie erlaubten Ressourcen. Die OpenAPI-Datei ist kein Ersatz für Laufzeit-Policy. OpenAPI 3.1 ist das Ziel, soweit Generator/Validator dies korrekt unterstützen; andernfalls ist vor Release ein kompatibler 3.0.x-Stand explizit zu entscheiden.

Beispielclients für **curl, C#/.NET HttpClient und Java HttpClient** benutzen denselben HTTPS/JSON-Vertrag und prüfen 401/403/404 sowie das einheitliche Fehlerobjekt. Eigene SDKs sind nicht V1-Pflicht.

## 12. Konfigurations- und Release-Migration V0 → V1

~~~mermaid
flowchart TD
    Backup["V0 DB-/Config-Backup<br/>Restore-Test"] --> Copy["Staging-Kopie von V0"]
    Copy --> Convert["schemaVersion 1 → 2<br/>Ressourcen / Felder / Roles"]
    Convert --> Validate{"Config + Policy gültig?"}
    Validate -->|"nein"| Fix["manuell korrigieren<br/>kein Productiv-Cutover"]
    Validate -->|"ja"| Install["Offline-Migration<br/>internes Audit-Schema"]
    Install --> Check["Contract-/Security-/Readiness-Tests"]
    Check --> Cutover{"Gates bestanden?"}
    Cutover -->|"nein"| Fix
    Cutover -->|"ja"| V1["V1-Pfad bewusst aktivieren"]
~~~

**Migrationsregeln:**
1. Backup und geprüfter Restore vor jeder Änderung; Code- und Datenstand dokumentieren.
2. Ressourcen/Feld-Aliasse werden übernommen, neue Rollen **nicht** mit großzügigen Defaults angelegt.
3. Alte V0-Keys erhalten **keine** automatische Admin-/Ownerrolle; Zuweisung oder Rotation erfolgt bewusst.
4. Audit-Schema wird über geschützten Betreiberweg erstellt, **nicht** mittels HTTP-DDL.
5. Eine neue V1-Konfiguration wird erst nach vollständiger Prüfung atomar aktiviert; fehlerhafte Teilkonfigurationen werden nicht online geschaltet.
6. V0- und V1-URLs werden nicht unbemerkt vermischt; bei Rückrollung sind DB-Schema- und Datenkompatibilität zu beachten.
7. Ein zweiter Adapter ist keine Datenmigrationsfunktion. Die Überführung einer Anwendung von MariaDB zu SQLite ist separat zu planen und zu testen.

## 13. SOLL-Funktionen als optionale Architekturmodule

| ID | Modul | Begrenzter Aufbau | Gate |
|---|---|---|---|
| V1-F09 | NamedReadService | operatorseitig vordefinierte **read-only** SQL-Vorlagen; nur gebundene Parameter | kein SQL aus Request, Limits/Rollen |
| V1-F14 | RecordVisibilityPolicy | serverseitiger Owner-/Kontextfilter | gleiche Isolation bei Lesen/Schreiben/Count/Relation |
| V1-F16 | OptimisticLock | ETag/Version + bedingtes Update, 412/428 | zwei konkurrierende Clients, stale Write abgewiesen |
| V1-F22 | Controller-Extension Registry | explizit registrierte Erweiterung durch gleiche Security-Pipeline | Erweiterung kann Policy/Logging nicht umgehen |
| V1-F27 | MetricsCollector | aggregierte Zähler, Fehler und Zeiten | keine personenbezogenen Bodies, kein Pflicht-Worker |

SOLL-Module dürfen **nicht** heimlich die Core-API oder DB-Konventionen ausweiten. Ein deaktiviertes optionales Modul wird nicht in OpenAPI als verfügbare Funktion dokumentiert.

## 14. Testarchitektur, Fitness Functions und Abnahme

| Ebene | Was technisch nachgewiesen wird |
|---|---|
| Unit | Config-/Role-Validator, Query-AST, Typnormalisierung, Keyprüfung, Rate-Budget |
| Integration | MariaDB/SQLite CRUD, Constraints, Audit/Transaction, Views und Relations |
| Contract | identischer V1-HTTP-/JSON-Vertrag für beide Adapter, V0 separat versioniert |
| Security | Privilegien, SQL-Injection, Field-/Count-/Join-Leaks, Secretpfade, TLS und Rate-Fail-Closed |
| Migration | V0→V1 Resource-/Key-Konvertierung, Validierungsfehler und Rollback |
| Deployment | PHP-Shared-Hosting, PDO-Treiber, SQLite-Dateisperren, private Verzeichnisse |
| Docs | OpenAPI und reale Controller deckungsgleich; curl/Java/.NET Beispielaufrufe |

**Automatisierbare Fitnessregeln:** Controller dürfen keine DB-Adapterklassen direkt verwenden; kein Repository-Port enthält HTTP-Header/Response-Codes; kein QueryPlan enthält untrusted SQL-Identifier; neue Datenressourcen bleiben gesperrt; Pflichtadapter bestehen dieselbe Contract-Suite; keine Schreiboperation kann ohne erfolgreiches Audit committen.

### 14.1 Abnahmefälle aus dem Lastenheft V1

| Lastenheft-Test | Schwerpunkt |
|---|---|
| AT-V1-01 | Versionierter API-Vertrag |
| AT-V1-02 | Feldalias/Allowlist, kein Auto-Exposure |
| AT-V1-03 | CRUD-/Typgleichheit MariaDB und SQLite |
| AT-V1-04 | Filter/AND/OR/Projektion/Pagination |
| AT-V1-05 | begrenzte Relation plus Autorisierung |
| AT-V1-06 | schreibgeschützte View |
| AT-V1-07 | instanzisolierte DBs und Schlüssel |
| AT-V1-08 | Keyrotation und Rollen |
| AT-V1-09 | atomare Writes und Konflikte |
| AT-V1-10 | Injection, Payload, Rate, TLS/CORS |
| AT-V1-11 | sanierte Logs und Write-Audit |
| AT-V1-12 | Health/Readiness bei DB-Ausfall |
| AT-V1-13 | OpenAPI gegen reale Routen |
| AT-V1-14 | Config-Migration/Backup/Restore |
| AT-V1-15 | curl, Java, C#/.NET Vertrag |
| AT-V1-16 | Unit/Integration/Contract/Security in CI |

**Freigabegate:** Abgenommene V0 bleibt funktionsfähig; alle 22 V1-MUSS-Anforderungen bestehen Security- und Contract-Tests; beide Datenbankadapter bestehen ihren gemeinsamen Pflichtvertrag; Deployment und Wiederherstellung sind dokumentiert. SOLL-Funktionen sind nur dann releasekritisch, wenn sie ausdrücklich mit eigenem Testumfang aktiviert wurden.

## 15. Architekturentscheidungen (ADR-Kandidaten)

| ADR-ID | Vorgeschlagene Entscheidung | Konsequenz / Alternativen |
|---|---|---|
| ADR-V1-001 | V0-Vertrag erhalten, V1 über eigenen Pfad | Kompatibilität statt stiller In-place-Änderung |
| ADR-V1-002 | MariaDB und SQLite als **zwei** Pflichtadapter | echte Tests statt oberflächlicher Connector-Vielfalt |
| ADR-V1-003 | typisierter QueryPlan/AST | kein clientseitiger SQL-Interpreter |
| ADR-V1-004 | konfigurationsgesteuerte Feldaliase/Views | öffentliche Ressourcennamen bleiben stabil |
| ADR-V1-005 | API-Key-Identitäten mit Rollen | ohne eigene Endnutzer-/SSO-Plattform |
| ADR-V1-006 | Write-Audit **transaktional** in lokaler DB | Auditfehler rollt Fachschreibvorgang zurück |
| ADR-V1-007 | persistenter Rate-Store mit Fail-Closed | Schutz auf Shared Hosting, mögliche Verfügbarkeitskosten |
| ADR-V1-008 | OpenAPI aus autorisierten Definitionen | keine zweite, divergente Handdokumentation |
| ADR-V1-009 | Konfigurationsschema V2 mit gesicherter Migration | kein automatisches Öffnen fremder Rechte |
| ADR-V1-010 | SOLL-Bausteine separat aktivierbar | Scope-Disziplin |
| ADR-V1-011 | Shared-Hosting-Fähigkeit bleibt zwingend | kein Redis-/Container-/Worker-Zwang |

ADRs brauchen vor Verbindlichkeit ihren eigenen Datensatz: Kontext, Wahl, Alternativen, Konsequenzen, betroffene API-Verträge und Tests. Diese Übersicht ist noch **nicht** eine Sammlung bereits freigegebener ADRs.

## 16. Risiken, offene Entscheidungen und Architekturgrenzen

1. **Provider und Datei-Trennung:** Verfügbarkeit von PDO SQLite, privatem Dateispeicher, `flock` und sicherem Konfigurationswechsel auf dem konkreten Hosting ist nachzuweisen.
2. **SQLite-Parallelität:** Busy/Locking, Journalmodus, Netzdateisystem und Schreibkonkurrenz testen; V1 verspricht keine unbegrenzte Parallelität.
3. **Audit-Atomizität:** Notwendige interne DB-Tabellen/Rechte vor Freigabe bereitstellen; Änderungen und Audit müssen tatsächlich dieselbe Transaktion nutzen.
4. **Rate-Store-Ausfall:** Fail-Closed schützt Daten, kann die Verfügbarkeit reduzieren; Architektur- und Betriebsentscheid dokumentieren.
5. **Filter-Semantik:** SQL-Dialekte, Unicode-Kollationen, Dezimal-/Zeit-Typen und JSON-Werte brauchen testbare, klar begrenzte Garantien.
6. **Public-Client-Identität:** Technische Keys sind keine Endnutzeridentifikation; Browser-/Mobile-Clients benötigen einen zusätzlichen Vertrauenspfad.
7. **OpenAPI-Sicht:** Nutzerrollen dürfen über OpenAPI/Counts/Sortierung keine unerlaubten Felder oder Ressourcen erfahren.
8. **Deprecation/Restore:** V0→V1 setzt echte Backup-/Restore-Tests und eine geplante API-/Config-Migration voraus.
9. **SOLL-Scope:** Optionales Named SQL, Row Scope, Locking, Extensions und Metrics dürfen den Pflichtumfang nicht destabilisieren.
10. **Performancebudgets:** keine unbelegten Millisekunden-SLOs; Testdatenvolumen, Nebenläufigkeit und Hostingmodus sind im Releasebericht anzugeben.

## 17. Traceability: V1-Anforderungen → Architektur → Tests

| Lastenheft | Architektur | Technische Testreferenz |
|---|---|---|
| V1-F01 | `5 versionierter API-Vertrag | T-V1-01 |
| V1-F02 | `7 Ressourcen-/Feldalias | T-V1-02 |
| V1-F03 | ``5/8 CRUD | T-V1-03 |
| V1-F04 | ``5/8 Typ-Validierung | T-V1-03 |
| V1-F05 | `7 QueryPlan-Filter | T-V1-04 |
| V1-F06 | ``5/7 Sort/Page/Projection | T-V1-04 |
| V1-F07 | `7 eine Relationstiefe | T-V1-05 |
| V1-F08 | `7 read-only Views | T-V1-06 |
| V1-F09 | `13 Named Query (SOLL) | T-V1-15 optional |
| V1-F10 | `8 beide DB-Adapter | T-V1-03/07 |
| V1-F11 | ``2–3 Instanzisolation | T-V1-07 |
| V1-F12 | `9 Key-Lebenszyklus | T-V1-08 |
| V1-F13 | `9 Rollenpolicy | T-V1-08 |
| V1-F14 | `13 Record-Visibility (SOLL) | T-V1-16 optional |
| V1-F15 | ``6/8/10 Transaktion/Fehler | T-V1-09 |
| V1-F16 | `13 Optimistic Lock (SOLL) | T-V1-16 optional |
| V1-F17 | ``6/9 Rate/Query-Limits | T-V1-10 |
| V1-F18 | ``3/9 TLS/CORS/Defaults | T-V1-10 |
| V1-F19 | `10 Write-Audit/Logs | T-V1-09 |
| V1-F20 | `10 Health/Readiness | T-V1-11 |
| V1-F21 | `11 OpenAPI | T-V1-01 |
| V1-F22 | `13 Extension (SOLL) | T-V1-16 optional |
| V1-F23 | `12 Config-Versionierung | T-V1-12 |
| V1-F24 | ``3/12 Deployment/Restore | T-V1-12 |
| V1-F25 | `11 curl/Java/C# | T-V1-13 |
| V1-F26 | `14 Testgates | T-V1-14 |
| V1-F27 | ``10/13 Metriken (SOLL) | T-V1-16 optional |

## 18. Entwicklungsreihenfolge, Review und Definition of Done

| Architektur-Slice | Inhalt | Review-Gate |
|---|---|---|
| P0 | V0-Baseline stabilisieren | V0-Sicherheits-/Abnahmetests grün |
| P1 | V1-Pfade, Alias-/Config-Schema V2 | Contracts und Migrationsprüfungen |
| P2 | SqlitePdoAdapter und Typnormalisierung | gemeinsame CRUD-/Filtertests |
| P3 | QueryPlan, Views, Relation | Query-/Injection-/Berechtigungstests |
| P4 | Key-Store, Rollen und Rate-Limit | Security-/Fail-Closed-Test |
| P5 | auditierter Commit, Readiness, Ops | Audit-Rollback und Restore |
| P6 | OpenAPI und drei Clientbeispiele | OpenAPI-Contract |
| P7 | SOLL-Module nur bei Freigabe | separate Security-/Contract-Gates |
| P8 | vollständige V1-Abnahme | alle MUST + beide Adapter + Hosting |

**Keine ungeplante Implementierung aller recherchierten Vorbildfunktionen.** Architekturänderungen an Auth, Policies, Konfigurationsschema, QueryPlan, SQL-Dialekten, Audit-Transaktionen oder API-Vertrag werden als ADR oder dokumentierte Reviewentscheidung festgehalten und mit Tests abgesichert.

**Dokumentstatus:** Ausführlicher Architekturentwurf für die zukünftige V1; noch keine Implementierung, kein Testnachweis und keine formelle Abnahme.
