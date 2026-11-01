# SASD-API-Server – Softwarearchitektur V0

> **Status:** Architekturentwurf, noch keine Implementierung oder Abnahme. **Version:** 0.1 · **Stand:** 01.11.2026 · **Ziel:** V0.1.0  
> Grundlage: [Lastenheft V0](./lastenheft-v0.md) · [Pflichtenheft V0](./pflichtenheft-v0.md) · [Referenzkatalog](./referenzprodukte-funktionskatalog.md)  
> Weiterentwicklung: [Architektur V1](./architektur-v1.md)

## 1. Auftrag, Geltungsbereich und Entwurfsprinzip

Dieses Dokument beschreibt die innere Architektur, Abhängigkeiten, Sicherheitsgrenzen und Betriebsformen des SASD API Servers. Es erklärt das **Warum** der im Pflichtenheft beschriebenen technischen Lösung und ergänzt sie um statische und dynamische Sichten, Architekturentscheidungen und überprüfbare Regeln. Das Lastenheft bleibt für den fachlichen Umfang maßgeblich, das Pflichtenheft für die konkreten technischen Zusagen.

**V0 bleibt bewusst klein:** PHP 8.4, MariaDB/MySQL über PDO, standardisierte HTTPS/JSON-Endpunkte für CRUD und einfache Listen. **Nur ausdrücklich konfigurierte** Tabellen, Felder und HTTP-Methoden werden angeboten. Der gleiche Code kann in mehreren voneinander getrennten Installationen unter verschiedenen URLs laufen; pro Installation eigene Datenbank, Credentials und Konfiguration. V0 ist weder DreamFactory noch JBoss noch ein Fachserver für Buchausleihen oder Gesundheitsabläufe.

### 1.1 Architekturziele

| Ziel | Strategie | Nachweis |
|---|---|---|
| Einfach deploybar | PHP-Anfrage ohne dauerhaften Prozess, Redis, Docker oder Root | frisches Shared-Hosting-Deployment |
| Sicherer Standard | Deny-by-default, API-Key, HTTPS, Feld-/Methoden-Allowlists | Security-Negativtests |
| Verständlich | klare Schichten und kleine Schnittstellen, kommentierte Entscheidungen | Komponentenreview und Unit-Tests |
| Mehrfach installierbar | Instanzdaten werden vollständig durch private Konfiguration bestimmt | zwei Testinstanzen mit verschiedenen DBs/Keys |
| Zukunftsfähig | Repository-Port statt PDO in Controller | fake-Repository im Test, V1-Adapter anschließbar |
| Beobachtbar | Request-ID, sanierte Logs, Status, geschützte Diagnose | Ausfall- und Logtests |
| Stabiler V0-Vertrag | versionierte Routen und einheitliche JSON-/Fehlerformate | HTTP-Contract-Tests |

### 1.2 Was V0 ausdrücklich nicht macht

Keine Benutzerregistrierung, SSO, Rollen- und Mandantenverwaltung, GraphQL, freie SQL-Ausführung, Auto-Veröffentlichung der gesamten DB, Cross-Database-Zugriffe, Joins, Massenschreiben/-löschen, Schemaänderungen über HTTP, Workflows, Datei-/Maildienste oder Verwaltungs-GUI. Datenbank-Backups, fachliche Schema-Migrationen und Geschäftsregeln gehören zu Betreiber bzw. aufrufender Fachanwendung.

## 2. Systemkontext und Sicherheitsgrenzen (C4 Level 1)

~~~mermaid
flowchart LR
    Users["Vertrauenswürdige technische Clients<br/>.NET / Java / curl / Backend"]
    Operator["Betreiber: Konfiguration,<br/>Schlüssel, Backup, Deployment"]
    API["SASD API Server<br/>V0-Instanz"]
    DB[("MariaDB / MySQL")]
    Users -->|"HTTPS + JSON + X-API-Key"| API
    Operator -->|"privater Wartungsweg"| API
    API -->|"PDO, minimal berechtigtes Konto"| DB
~~~

**Außerhalb des Servers:** UI, Fachprozesse, Datenbankschema-/Backup-Verwaltung, TLS-Terminierung des Hostings. **Innerhalb:** Request-Verarbeitung, Freigaben, CRUD-Orchestrierung, sichere SQL-Zugriffe, Fehlerübersetzung und Diagnose.

Vertrauensgrenzen: (1) Internet → Webserver: sämtliche Clientwerte sind untrusted; (2) Webserver/Proxy → PHP: Protokoll- und Forwarded-Header dürfen nur aus verifizierter Quelle vertraut werden; (3) PHP → private Dateien: physische Zugriffstrennung vom Document Root; (4) PHP → SQL: Least-Privilege-Benutzer plus parametrisiertes SQL; (5) Instanz A → B: **keine** gemeinsamen Secrets, Ressourcen oder Datenrechte.

**Wichtige Einschränkung:** Ein statischer API-Key in ausgeliefertem Browser-JavaScript oder einer öffentlichen Client-App ist kein geschütztes Geheimnis. V0 ist für vertrauenswürdige technische Clients oder einen vorgeschalteten vertrauenswürdigen Backenddienst vorgesehen, **nicht** für direkt öffentliche, frei registrierbare Multi-User-Clients.

## 3. Deployment-/Laufzeitsicht (C4 Level 2)

~~~mermaid
flowchart TB
    Client["HTTP-Client"] --> Web["HTTPS-Webserver<br/>PHP-Handler"]
    subgraph Public["Öffentliches Webroot"]
      Entry["index.php<br/>Front Controller"]
    end
    Web --> Entry
    subgraph Private["Privat, außerhalb Document Root"]
      Code["src/ und Dependencies"]
      Config["Ressourcen-Konfiguration"]
      Secrets["DB-Zugang / API-Key"]
      Logs["geschützte JSONL-Logs"]
    end
    Entry --> Code
    Code --> Config
    Code --> Secrets
    Code --> Logs
    Code --> SQL[("MariaDB")]
~~~

### 3.1 Webroot und Hosting-Sicherheit

Die physische Bereitstellung muss nur den erforderlichen HTTP-Einstieg öffentlich ausliefern. Private Konfiguration, Zugangsdaten, Logs und möglichst auch serverseitiger Anwendungscode befinden sich **außerhalb des Document Root**, selbst wenn die Quellcodeorganisation im Git-Repository anders aussieht. Eine möglicherweise ignorierte `.htaccess`-Regel ist **keine** ausreichende Schutzgrenze. Lässt sich die Isolation auf dem konkreten IONOS-Tarif nicht nachweisen, ist das Produktivdeployment nicht freizugeben.

PHP 8.4 und MariaDB 10.11 bilden die erste Testkombination. Keine Pflicht für laufende Worker, Docker, Redis, Root oder ein Framework. Die notwendige PHP-/PDO-Extension und Upload-/Dateirechte werden vor dem Go-live geprüft.

### 3.2 Routing und Fallback

Bevorzugter Pfad: `/api/v0/books`. Bei mangelnder Rewrite-Unterstützung: `/index.php?route=/api/v0/books`. Beide werden auf dieselbe interne Route normalisiert. Der Parameter `route` ist **kein Dateipfad**. Nullbytes, Traversal, doppelte Kodierung, unzulässige Pfadsegmente und mehrdeutige Parameter sind kontrolliert abzulehnen. Die kanonische öffentliche Basis-URL kommt aus Betreiberkonfiguration, nicht aus ungeprüften Host-Headern.

Zwei Installationen können auf demselben Hosting mit verschiedenen Subdomains/Pfaden liegen, sofern URL, Cache, Secret- und Log-Pfade pro Instanz eindeutig getrennt sind.

## 4. Komponentenmodell (C4 Level 3)

~~~mermaid
flowchart LR
    Entry["index.php / Bootstrap"] --> Router["Router / HTTP-Adapter"]
    Router --> Guard["TLS + Key + Policy + Limits"]
    Guard --> Ctrl["ResourceController"]
    Ctrl --> Service["CrudService"]
    Service --> Registry["ResourceRegistry / Validator"]
    Service --> Port["RecordRepository (Port)"]
    Port --> Adapter["MariaDbPdoRepository"]
    Adapter --> DB[("MariaDB")]
    Ctrl -.-> Response["JsonResponder / ErrorMapper"]
    Guard -.-> Logs["SanitizedLogger / Request-ID"]
~~~

| Baustein | Exakte Verantwortung | Verbotene Kopplung |
|---|---|---|
| `index.php` / Bootstrap | Initialisierung, Container/Composition Root, oberste Fehlergrenze | selbst SQL ausführen |
| Request/Router | Methode, Pfad, Query, Content-Type, Routenmatch | interne Tabellen aus URL herleiten |
| Security Pipeline | HTTPS, Key, Größenlimit, Ressourcen-/Methodenfreigabe | gültigen Key mit All-access verwechseln |
| ResourceController | HTTP-Objekte auf Use Cases abbilden, Resultate als HTTP ausgeben | PDO direkt verwenden |
| CrudService | CRUD-Anwendungsfälle, Prüf- und Transaktionsreihenfolge | SQL-Dialekt oder $_SERVER lesen |
| ResourceRegistry/Definitions | erlaubte Tabellen, Felder, PK, Methoden und Filter-/Sortierregeln | unkonfigurierte DB-Tabellen freischalten |
| Validator/ListQueryParser | Syntax, Typen, Feldfreigaben, Limitparameter | Werte in SQL-Quelltext übersetzen |
| RecordRepository | neutraler Vertrag für listen/lesen/anlegen/ändern/löschen | HTTP-Status definieren |
| MariaDbPdoRepository | Prepared SQL, Transaktionen, Datenbankfehler-Mapping | Clientheader lesen |
| ErrorMapper/Responder | konsistente HTTP-Status/JSON-Schemas, keine Interna | Fehlerdetails unredigiert ausgeben |
| SanitizedLogger/Health | Request-ID, technische Logs, knapper Status | Secrets oder ganze Bodies speichern |

Abhängigkeitsregel: **HTTP → Application → Domain/Contracts ← Infrastructure**. Nur Bootstrap kennt konkrete Implementierungen. Domain und Use Cases sind unabhängig von PDO, Webserver-Globals und Ressourcennamen in fremdem Code. Interfaces werden **nur für tatsächlich vorhandene Use Cases** definiert, nicht für hypothetische Backends.

### 4.1 Vorgeschlagene Repository- und Package-Struktur

~~~text
index.php
composer.json
src/
  Bootstrap.php
  Http/{Request,Response,Router,JsonParser,JsonResponder}.php
  Http/Controller/ResourceController.php
  Application/{CrudService,ListQuery}.php
  Domain/{ResourceDefinition,FieldDefinition,ApiError}.php
  Contracts/RecordRepository.php
  Security/{ApiKeyAuthenticator,AccessPolicy}.php
  Config/{ConfigLoader,ConfigValidator}.php
  Infrastructure/Database/{PdoFactory,MariaDbPdoRepository}.php
  Infrastructure/Logging/SanitizedLogger.php
  Infrastructure/Diagnostics/HealthService.php
config/example.resources.json
tests/{Unit,Integration,Contract,Security,Deployment}/
docs/{installation,api,operations}.md
~~~

Dies sind vorgeschlagene Komponenten und Pfade, **kein Verzeichnisnachweis** des derzeitigen Repositories. Wenn eine einfachere Struktur dieselbe Verantwortlichkeit und Testbarkeit erreicht, kann sie per ADR freigegeben werden.

## 5. Dynamische Sicht – Requestabläufe

### 5.1 Lesen eines Datensatzes oder einer Liste

~~~mermaid
sequenceDiagram
    participant C as Client
    participant R as Router
    participant G as Security
    participant S as CrudService
    participant D as Repository
    C->>R: GET /api/v0/books?limit=25
    R->>G: normalisierter Request
    G->>G: HTTPS, API-Key, Ressource, Methode
    G->>S: ListQuery + freigegebene ResourceDefinition
    S->>S: Feld-/Filter-/Limitprüfung
    S->>D: list(ResourceDefinition, ListQuery)
    D-->>S: PageResult
    S-->>C: 200, JSON data/meta + Request-ID
~~~

**Invariant:** Ein ungültiger Filter wird niemals „zur Sicherheit“ ignoriert und als ungefilterte Abfrage ausgeführt. Das Repository projiziert nur freigegebene lesbare Spalten. Der API-Key wird vor jeder Datenoperation geprüft.

### 5.2 Schreiben und Fehlerpfad

~~~mermaid
sequenceDiagram
    participant C as Client
    participant G as Security
    participant V as Validator
    participant D as MariaDbPdoRepository
    participant DB as MariaDB
    C->>G: PATCH /api/v0/books/42 + JSON
    G->>V: autorisierter Request
    V->>V: Felder und Typen validieren
    V->>D: patch(id, allowedChanges)
    D->>DB: BEGIN
    D->>DB: UPDATE ... WHERE id = :id
    alt gültiger Commit
      D->>DB: SELECT sichtbare Felder, COMMIT
      D-->>C: 200 + persistierter Record
    else Constraint / Datenbankfehler
      D->>DB: ROLLBACK
      D-->>C: typisierter Fehler → 4xx/5xx
    end
~~~

**Concurrency:** Bei `rowCount()=0` kann ein Datensatz existieren, aber unverändert geblieben sein. Der Adapter muss Existenz und tatsächliche Änderung korrekt unterscheiden. V0 hat noch **kein** Optimistic Locking und macht keine Garantie gegen Lost Updates konkurrierender Clients.

## 6. Öffentlicher API-Vertrag

### 6.1 Endpunkte und Status

| HTTP | Route | Bedeutung | Erfolgsstatus |
|---|---|---|---|
| GET | `/api/v0/{resource}` | Liste; eq/AND, Sortierung, Pagination | 200 |
| GET | `/api/v0/{resource}/{id}` | einzelner Datensatz nach 1 PK | 200 |
| POST | `/api/v0/{resource}` | genau einen Datensatz erzeugen | 201 + `Location` |
| PUT | `/api/v0/{resource}/{id}` | kompletter Ersatz clientseitig schreibbarer Felder | 200 |
| PATCH | `/api/v0/{resource}/{id}` | partielle Änderung | 200 |
| DELETE | `/api/v0/{resource}/{id}` | ein Datensatz | 204 ohne Body |
| GET | `/api/v0/health` | minimierter Status | 200/503 |

Header: `X-API-Key` für alle Datenoperationen; Schreibrequests `Content-Type: application/json`. Antworten mit `data`, Listen zusätzlich `meta`, Fehler mit `error.code`, `error.message` und `requestId`. Bei Auth-Responses gilt standardmäßig `Cache-Control: no-store`.

~~~json
{
  "data": [{"id": 42, "title": "Beispiel"}],
  "meta": {"limit": 25, "offset": 0, "count": 1, "hasMore": false}
}
~~~

`meta.count` zählt **nur** zurückgegebene Seitenelemente, nicht alle passenden Datensätze; `hasMore` kann mittels eines auf `limit+1` begrenzten internen Abrufs ermittelt werden. Keine implizite Vollzählung.

### 6.2 Filter, Sorting und PUT/PATCH

- `filter[title]=Beispiel` ist ein Gleichheitsfilter; mehrere Filter werden mit AND kombiniert.
- `sort=title` oder `sort=-title`; genau ein erlaubtes Sortierfeld; Default eindeutiger Primärschlüssel.
- `limit` Default 25, maximal 100; `offset` Default 0, vorläufig maximal 10.000. Beide Schranken sind im Hostingtest zu prüfen und dann als Konfigurationsvertrag zu fixieren.
- `PUT` verlangt **alle** als `clientWritable` definierten Felder (vollständige clientverwaltete Repräsentation); Systemfelder bleiben unverändert. `PATCH` ändert ausschließlich gesendete Felder; ein leeres Objekt ist unzulässig.
- Einspaltige positive Integer- oder explizit konfigurierte UUID-Primärschlüssel; keine zusammengesetzten Keys.
- Fehlende Ressource/ID 404; fehlerhaftes JSON/Query 400; Auth 401; unzulässige Operation 403/405 nach dokumentierter Sicherheitsregel; Konflikt 409; Payload 413; Medientyp 415; Feldvalidierung 422; interne Fehler 500 und nicht bereiter Dienst 503.

~~~json
{
  "error": {
    "code": "validation_failed",
    "message": "Invalid request body.",
    "details": [{"field": "title", "code": "required"}]
  },
  "requestId": "9fc085c3b2a043d3"
}
~~~

## 7. Konfiguration als API-Definition

~~~mermaid
flowchart LR
    CFG["Privates Ressourcen-JSON"] --> Loader["ConfigLoader"]
    Secret["Geschützte Env/Secret-Datei"] --> Loader
    Loader --> Validation{"Schema/Allowlist gültig?"}
    Validation -->|"ja"| Reg["Immutable ResourceRegistry"]
    Validation -->|"nein"| Off["Fail closed: keine Daten-API"]
~~~

Der Betreiber wählt Ressourcenname, Datenbanktabelle, Primärschlüssel, freigegebene Methoden und Felder, lesbare/schreibbare Filter und Sortierfelder. Sensible Werte werden **nie** in einer öffentlich erreichbaren Ressourcendatei gespeichert. In V0 wird die aktive Konfiguration bei jedem Bootstrap geprüft; einen globalen Distributed Cache gibt es nicht. Beispiel einer neutralen Teildefinition:

~~~json
{
  "schemaVersion": 1,
  "instanceId": "books-test",
  "apiPrefix": "/api/v0",
  "database": {"driver": "pdo_mysql", "passwordEnv": "SASD_DB_PASSWORD"},
  "security": {"apiKeyEnv": "SASD_API_KEY", "requireHttps": true},
  "resources": {
    "books": {
      "table": "books",
      "primaryKey": {"field": "id", "type": "integer"},
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {"type": "integer", "readable": true, "clientWritable": false},
        "title": {"type": "string", "maxLength": 200, "readable": true,
                  "clientWritable": true, "filterable": true, "sortable": true}
      }
    }
  }
}
~~~

Das Beispiel ist **verkürzt und nicht unmittelbar ausführbar**. Fehlen erforderliche Werte, enthält die Konfiguration eine unbekannte Operation oder ein unsicheres Feldmapping, wird die Instanz nicht freigegeben. Weder eine neue DB-Tabelle noch eine Schema-Introspektion veröffentlicht automatisch eine Ressource.

## 8. Persistenz, SQL und Konsistenz

Der Service arbeitet gegen einen schmalen Port, etwa `RecordRepository` mit `list`, `find`, `insert`, `replace`, `patch` und `delete`. Der einzige V0-Adapter ist `MariaDbPdoRepository`. Seine technischen Regeln:

1. Verbindungs- und SQL-Ausnahmen bleiben in Infrastructure; im Service erscheinen neutrale Fehlerkategorien.
2. SQL-Werte werden als PDO-Parameter gebunden. Tabellen, Spalten, Sortierfelder und Operatoren kommen **ausschließlich aus validierten Allowlist-Definitionen** und werden nach Dialektregeln gequotet.
3. `SELECT *` wird nicht verwendet; Rückgaben enthalten nur erlaubte Spalten. `UPDATE` und `DELETE` haben eine zwingende Primärschlüsselbedingung.
4. Einzelne POST/PUT/PATCH/DELETE-Aufrufe laufen in einer lokalen Datenbanktransaktion mit Commit/Rollback. Kein Transaktionskontext über mehrere HTTP-Aufrufe.
5. Unique-/Foreign-Key-Fehler führen zu kontrolliertem `409`; DB-Unerreichbarkeit darf nicht als leere Liste verschleiert werden.
6. DB-Account je Installation nach Least Privilege; SQL-Constraints bleiben wirksam. Das API-Policy-System schützt zusätzlich zu SQL-GRANTs.
7. Fachschemata werden vorher angelegt, im API-Server nicht dynamisch über HTTP migriert.

**Port-Design:** Der Vertrag beschreibt *Record-Operationen* und nicht PDO-Objekte oder SQL-Strings. Ein zweiter Adapter wird erst in V1 implementiert. Der Port darf später für definierte Query-Pläne erweitert werden, ohne den HTTP-Controller an einen SQL-Dialekt zu koppeln.

## 9. Sicherheitsarchitektur / Threat Model

| Risiko | Gegenmaßnahmen | Test |
|---|---|---|
| Unbefugte Tabelle/Spalte | explizite Resource-/Field-Allowlist | unbekannte Ressource/Spalte abgewiesen |
| SQL-Injection über Werte/Sortierung | PDO Binding, Identifier-Allowlist | Injection-Versuche verändern Schema/Daten nicht |
| Key-Leak / schwacher Key | hochentropischer Header-Key, konstante Digest-Prüfung, private Speicherung | kein Secret in Responses/Logs |
| HTTP/Proxy-Bypass | HTTPS-Pflicht, vertrauenswürdige Forwarded-Header nur nach Betreiberprüfung | HTTP liefert keine Daten |
| Body-/Query-Overload | 64-KiB-Body als zu prüfender Startwert, maximal 100 Datensätze/Seite, begrenzte Filter | 413 / 4xx ohne DB-Überlast |
| Unsichere Dateien | Private Verzeichnisse außerhalb Webroot | direkter HTTP-Dateiabruf scheitert |
| Verbotsumgehung per Route | einmalige Normalisierung, keine Traversals, gleiche Policy für Rewrite/Fallback | Security-Contract-Tests |
| Datenleck in Fehler/Status | zentraler ErrorMapper, minimale öffentliche Health-Sicht | kein SQL, Pfad oder Credential |
| Cross-Instanz-Leck | getrennte Konfiguration, DB, Keys, Logs | fremde Instanz nicht lesbar |

Der API-Key authentifiziert die **technische Installation/Anwendung**, keinen Endnutzer. CORS schützt nur Browser-Origin-Konventionen und ist **kein** Authentifizierungssystem. Es gibt in V0 keinen Cookie-Login; CSRF-/Sessionmodelle werden nicht künstlich eingeführt. Logs enthalten Uhrzeit UTC, Methode, Ressourcenkennung, HTTP-Status, Fehlerkategorie und Request-ID, aber keine Auth-Header, Passwörter, SQL-Parameter oder kompletten Nutzdaten.

**Produktiv-Gate:** Privatpfade, TLS-Zwang, tatsächliches Routing, Datenbankrechte, CORS und sichere Logpfade müssen auf dem gewählten Hosting nachweisbar funktionieren. Falls ein Punkt nicht erfüllbar ist: keine Freigabe, statt die Sicherheitsbedingung still zu lockern.

## 10. Betrieb, Fehler und Wiederherstellung

- `/api/v0/health` zeigt nur `ok` oder `unavailable`, keine privaten Host-/Datenbankdetails. Eine ausführlichere DB-Readiness muss über eine geschützte Betreiberroute bzw. einen internen Diagnosemodus erfolgen; konkrete Route noch festzulegen.
- Logs in geschütztem JSON-Lines-Format außerhalb des Webroots. Rotation und Retention sind Betreiberpflicht; ohne Logging darf kein Secret an Standardausgabe fallen.
- Fehlkonfiguration: Fail-closed; DB-Ausfall: neutraler Fehler; ungültiges JSON: Clientfehler; HTTP-Methode gesperrt: 403/405 nach festgelegter Priorität.
- Datenbankbackup, Restore-Test und sichere Config-Sicherung gehören in das Betreiber-Runbook. Der API Server selbst hat keinen Backup-Scheduler und keine DDL-API.
- Bei einem Update müssen Version, Tests und Installationskonfiguration protokolliert werden; ein Rollback setzt passende DB-Schema-Kompatibilität voraus.

## 11. Architektur-Qualitätssicherung und Abnahme

| Testebene | Nachweis |
|---|---|
| Unit | Query-/Routeparser, ConfigValidator, Typ-/Policyprüfungen, ErrorMapper |
| Integration | MariaDB CRUD, Unique/FK, Transaktionen, gültige/ungültige Datentypen |
| Contract | HTTP-Vertrag, JSON, Status, Queryrouting-Fallback |
| Security | Key, SQL-Injection, Deny-by-default, CORS, HTTPS, Requestgrößen, Secretpfade |
| Deployment | echte PHP-Hosting-Umgebung, DB-Verbindungsfehler und unzugängliche Privatedateien |
| Isolation | zwei Instanzen aus gleichem Code mit verschiedenen DBs/Keys und Logs |
| Dokumentation | Beispielkonfiguration, curl-Anfragen, vollständige Installation und Restore-Hinweise |

**Architektur-Fitnessregeln:** Kein PDO in Controllern; kein HTTP-Konzept im Repository-Port; keine SQL-Identifier aus freien Requestwerten; jede neue Datenressource bleibt gesperrt, bis konfiguriert; keine Security-Änderung ohne negative Tests. Coverage allein ersetzt keine überprüften Angriffsfälle.

### 11.1 Nachweis der V0-Abnahmeszenarien

| Lastenheft-Abnahme | Fokus |
|---|---|
| AT-V0-01 | Installation/Routing/Privatpfade |
| AT-V0-02 | Liste und Einzelobjekt |
| AT-V0-03 | POST/PUT/PATCH/DELETE und Transaktionen |
| AT-V0-04 | Filter, Sortierung, Paging |
| AT-V0-05 | gesperrte Ressourcen und Felder |
| AT-V0-06 | Key/HTTPS/Widerruf |
| AT-V0-07 | Injection/Body/Datentyp |
| AT-V0-08 | Instanzisolation |
| AT-V0-09 | Health/Logs/DB-Ausfall |
| AT-V0-10 | Dokumentation und vollständige Tests |

## 12. Wesentliche Architekturentscheidungen (ADR-Kandidaten)

| ID | Vorschlag | Nicht gewählte Alternative / Folge |
|---|---|---|
| ADR-V0-001 | PHP 8.4 / PDO, kein Frameworkzwang | schwerer Application-Server, weniger Shared-Hosting-Eignung |
| ADR-V0-002 | explizite Allowlist statt Vollveröffentlichung | kein unsicherer Default |
| ADR-V0-003 | Webroot-index.php, private Dateien physisch getrennt | .htaccess allein zu unsicher |
| ADR-V0-004 | API-Key je Instanz, keine Endnutzerverwaltung | begrenzte Zielgruppe in V0 |
| ADR-V0-005 | Repository-Port mit nur MariaDB-Adapter | spätere Austauschbarkeit ohne Overengineering |
| ADR-V0-006 | definierte PUT-/PATCH-Semantik | weniger Interpretationsspielräume |
| ADR-V0-007 | einfache eq/AND-Filter, begrenztes Offset | keine beliebige SQL-Sprache |
| ADR-V0-008 | JSONL-Logs + Request-ID | keine neue Monitoringinfrastruktur |
| ADR-V0-009 | HTTPS-Only, keine blind vertrauten Proxy-Header | sicherer Credential-Transport |

Diese Einträge sind Entscheidungen **zur Freigabe**, keine bereits abgezeichneten ADR-Dateien. Jede dauerhafte Änderung dokumentiert Kontext, Alternativen, Konsequenzen und Tests.

## 13. Offene Architektur- und Betreiberfragen

1. Kann die Hostingumgebung Config/Secrets/Logs nachweislich **außerhalb** des Document Root aufnehmen?
2. Wie wird HTTPS zuverlässig erzwungen und welche Proxy-Header dürfen aus welcher Quelle vertraut werden?
3. Welches JSON-Config-Schema und welcher V0-Pfad werden final bestätigt?
4. Sollen Integer- und UUID-IDs beide schon im V0-Referenzschema vorkommen?
5. Wie genau wird `PUT` bei nullable und DB-generierten Feldern getestet?
6. Sind die vorgeschlagenen Grenzen (64 KiB, 100 Datensätze, Offset 10.000) auf dem Zielhost sinnvoll?
7. Brauchen vorgesehene Clients einen vertrauenswürdigen Backend-Proxy statt eines mitgelieferten API-Keys?
8. Welche unterstützten MariaDB-/PHP-Versionen und DB-GRANTs können auf dem echten Hosting nachgewiesen werden?

## 14. Entwicklungsslices und Freigabereihenfolge

| Slice | Inhalt | Gate |
|---|---|---|
| A0 | sichere Verzeichnis-/Deployment-Grenze, Testdatenbank | kein Secret im Webroot |
| A1 | Bootstrap, Router, Fehlerformat, Request-ID | HTTP-Contract |
| A2 | ConfigLoader, ResourceRegistry, Key und HTTPS | Security-Negativtests |
| A3 | MariaDB Repository, sichere GET-Ressourcen | DB-/Field-Allowlist |
| A4 | POST, PUT, PATCH, DELETE, Transaktionen | Integritäts-/Konflikttests |
| A5 | Filter, Sortierung, Pagination und Limits | Query-/Injection-Tests |
| A6 | Health, Logs, zwei Instanzen, Runbooks | alle V0-Abnahmeszenarien bestanden |

## 15. Rückverfolgbarkeit: V0-Lastenheft → Pflichtenheft → Architektur

| V0-Funktion | Architekturentscheidung/Abschnitt | Technischer Nachweis |
|---|---|---|
| V0-F01 | ``4–7 Router/Registry | T-V0-01/05 |
| V0-F02 | ``5–6, 8 GET | T-V0-01 |
| V0-F03 | ``5/8 POST | T-V0-02 |
| V0-F04 | ``5–6 PUT/PATCH | T-V0-03 |
| V0-F05 | ``5/8 DELETE | T-V0-04 |
| V0-F06 | `7 Konfigurations-Allowlist | T-V0-05 |
| V0-F07 | `6 Filter | T-V0-06 |
| V0-F08 | `6 Sortierung/Paging | T-V0-07 |
| V0-F09 | `6 Fehler/JSON | T-V0-09 |
| V0-F10 | ``2–3 Isolation | T-V0-11 |
| V0-F11 | `9 API-Key | T-V0-08 |
| V0-F12 | ``6–9 Validierung/SQL | T-V0-06/09 |
| V0-F13 | ``3/9 TLS/CORS | T-V0-10 |
| V0-F14 | `10 Health | T-V0-10 |
| V0-F15 | `10 Logging | T-V0-10 |
| V0-F16 | ``3/10/14 Dokumentation | T-V0-12 |
| V0-F17 | `11 Tests/Fitness | T-V0-12 |

## 16. Review, Änderungen, Definition of Done

Änderungen an HTTP-Verträgen, Ressourcen-/Feldfreigaben, Key-Handling, Repository-Ports, SQL-Bau, TLS-/Config-Grenzen oder Fehlerformat sind **architekturrelevant** und benötigen Review plus Contract-/Security-Tests. Fachliche Änderungen werden zuerst im Lastenheft, danach im Pflichtenheft und in dieser Architektur nachgeführt.

**Definition of Done (spätere Implementierung):** alle 17 V0-MUSS-Anforderungen und AT-V0-01 bis AT-V0-10 bestanden; Daten/Secrets getrennt; Shared-Hosting-Installation getestet; alle Sicherheits-Negativtests nachgewiesen. Bis dahin bleibt dies ein Architekturentwurf.
