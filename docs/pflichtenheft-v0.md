# SASD-API-Server – Pflichtenheft V0

> **Status:** Technischer Entwurf, vor Implementierung zu prüfen · **Dokumentversion:** 0.1 · **Stand:** 01.11.2026  
> **Architektur:** [Softwarearchitektur V0](./architektur-v0.md) (Architekturentwurf)  
> **Geltungsbereich:** V0.1.0 · **Sprache:** Deutsch  
> **Fachliche Grundlage:** [Lastenheft V0](./lastenheft-v0.md) · **Weiterer Ausbau:** [Pflichtenheft V1](./pflichtenheft-v1.md) · [Lastenheft V1](./lastenheft-v1.md)  
> **Recherche:** [Referenzprodukte-Funktionskatalog](./referenzprodukte-funktionskatalog.md)

## 1. Auftrag und Geltung

Dieses **Pflichtenheft** beschreibt eine konkret implementierbare technische Lösung für die im [Lastenheft V0](./lastenheft-v0.md) definierten Anforderungen. Die Fachanforderungen bleiben maßgeblich; die hier vorgeschlagenen technischen Entscheidungen sind Entwurfsentscheidungen und werden erst nach Review verbindlich. **Es wurde damit noch kein Anwendungscode implementiert und kein Abnahmetest bestanden.**

**Leitmotiv:** Ein kleiner, wartbarer PHP-REST-Server für CRUD auf ausdrücklich freigegebenen MariaDB/MySQL-Tabellen, mit einer Konfiguration je Installation, sicherem Standardverhalten und klaren Erweiterungsgrenzen. Er ist **kein** generischer SQL-Interpreter und übernimmt **keine** anwendungsspezifischen Geschäftsprozesse.

### 1.1 Prioritäten und Zielumfang

- **Implementierungsziel:** V0.1.0 mit sämtlichen V0-MUSS-Anforderungen einschließlich grundlegender Sicherheit, Tests und Installationsdokumentation.
- **Nicht Teil der Umsetzung:** SQLite/PostgreSQL, dynamische Schemaänderung, Benutzerverwaltung, RBAC, allgemeine Joins, Massenoperationen, freie SQL-Ausdrücke, Redis, Dockerpflicht, Admin-UI und Integrationsdienste.
- **Abnahmereihenfolge:** zuerst sichere Startbarkeit/Routing/Konfiguration, danach lesende Endpunkte, anschließend einzelne Schreibmethoden, einfache Filter und Tests. Sicherheits- und Fehlertests laufen bei jedem Schritt mit.
- **Entscheidungsprinzip:** keine komplexe Abstraktion ohne tatsächliche V0-Aufgabe; Schnittstellen aber so definieren, dass V1 Datenbankadapter, Rollen und OpenAPI ergänzen kann.

## 2. Ausführungsumgebung und Deployment

| Bereich | V0-Lösung |
|---|---|
| Runtime | PHP 8.4, ohne Frameworkpflicht; Composer für Autoloading/Entwicklung möglich. |
| Primäre Datenbank | MariaDB 10.11 über PDO MySQL; kompatible MySQL-Versionen erst nach Tests freigeben. |
| Webserver | Apache/PHP bzw. PHP-fähiges Shared Hosting; kein Rootzugriff und kein dauerhaft laufender Hintergrundprozess erforderlich. |
| Einstieg | Ein `index.php` im Webroot ruft Bootstrap und Router auf; private Klassen und Einstellungen werden getrennt abgelegt. |
| URL-Behandlung | Bevorzugt `/api/v0/...` über getestete Rewrite-Regeln; verlässlicher Fallback über `/index.php?route=/api/v0/...`. |
| Transport | Extern ausschließlich HTTPS für Datenendpunkte; TLS darf vor PHP am vertrauenswürdigen Host/Proxy terminieren. |
| Konfiguration | JSON für nicht geheime Ressourcendefinitionen; vertrauliche Werte aus Umgebungsvariablen oder geschützter Datei **außerhalb** des Document Root. |
| Betrieb | Request/Response ohne persistente Sitzungen, Redis, Queue, cron oder Framework-Serverprozess. |

### 2.1 Webroot, sichere Dateipfade und Fehlerfall

Der Provider kann einen Webroot direkt im Projektverzeichnis verlangen. Darum darf die Sicherheit **nicht** auf eine möglicherweise ignorierte `.htaccess`-Sperre angewiesen sein. Die empfohlene Produktionsstruktur lautet: `<private>/vendor/`, `<private>/src/`, `<private>/config/`, `<private>/logs/` und `<webroot>/index.php`; das Deployment kann diese Trennung mittels Hosting-Verzeichnissen oder Release-Paketen herstellen. **Wenn private Dateien auf dem Zielhost nicht verlässlich außerhalb des Document Root liegen können, ist eine produktive Bereitstellung zu verweigern.** Ein Abschotten allein per Rewrite/.htaccess ist kein ausreichender Ersatz.

V0 enthält eine Konfigurationsprüfung, die bei fehlendem Secret, nicht existierender Ressourcenliste, inkonsistenter Tabellenfreigabe oder fehlenden benötigten PHP-Erweiterungen **keine Datenendpunkte freigibt**. Fehlerausgaben nach außen sind neutral; die detaillierte Ursache wird nur geschützt protokolliert.

### 2.2 Routing-Fallback als verbindliches Installationsprofil

Beide URL-Formen müssen dieselbe intern normalisierte Route ergeben. Beim Query-Fallback ist `route` **nur** ein Pfadselektor; andere Queryparameter (`filter[title]`, `limit`, `offset`, `sort`) bleiben unabhängig davon. Der Router akzeptiert nur definierte UTF-8-Pfade, normalisiert genau einmal und verweigert doppelte Kodierung, `..`-Traversal sowie unerwartete Slashes. `route` wird nicht als Dateipfad interpretiert.

## 3. Schichtenmodell und Verantwortlichkeiten

```text
Client (.NET, Java, PHP, curl)
          | HTTPS / JSON / X-API-Key
          v
index.php -> Bootstrap -> Router -> [Request-ID, HTTPS, API-Key, Limits]
                                 |
                                 v
                          ResourceController
                                 |
                                 v
                           CrudService
                                 |
                                 v
                       ResourceDefinition + Validator
                                 |
                                 v
                        RecordRepository (Port)
                                 |
                                 v
                   MariaDbPdoRepository (Adapter)
                                 |
                                 v
                             MariaDB

Querschnitt: ConfigLoader, ErrorMapper, JsonResponder, SanitizedLogger
```

### 3.1 Vorgeschlagene Quellcodegliederung

```text
index.php                             # Öffentlicher Front Controller
composer.json                         # Autoload/Entwicklungswerkzeuge, keine Secrets
src/Bootstrap.php                     # Verdrahtung und sichere Initialisierung
src/Http/{Request,Response,Router}.php
src/Http/{JsonParser,JsonResponder}.php
src/Http/Controller/ResourceController.php
src/Application/CrudService.php
src/Application/Query/ListQuery.php
src/Domain/{ResourceDefinition,FieldDefinition}.php
src/Domain/{ValidationError,ApiError}.php
src/Contracts/RecordRepository.php
src/Security/{ApiKeyAuthenticator,AccessPolicy}.php
src/Config/{ConfigLoader,ConfigValidator}.php
src/Infrastructure/Database/{PdoFactory,MariaDbPdoRepository}.php
src/Infrastructure/Logging/SanitizedLogger.php
src/Infrastructure/Diagnostics/HealthService.php
config/example.resources.json         # Nur schematisches, öffentliches Beispiel
tests/{Unit,Integration,Security,Contract}/
docs/{installation,api,operations}.md  # In der Umsetzung anzulegen
```

Der Root-Einstieg bleibt klein; er enthält weder SQL noch Validierungslogik. Der **Controller** übersetzt HTTP in einen typisierten Anwendungsaufruf; der **Service** prüft Ressourcenregeln und Orchestrierung; das **Repository-Interface** beschreibt CRUD ohne PDO-Abhängigkeit; der **MariaDB-Adapter** kapselt SQL und Datenbankbesonderheiten; **Domain-/Konfigurationsobjekte** beschreiben Freigaben und Datentypen. Ein Interface für Adapter wird jetzt definiert, aber **es wird nur ein** produktiver Adapter implementiert.

### 3.2 Feste Ausführungsreihenfolge einer Datenanfrage

1. Server generiert eine Request-ID; Methode, Pfad und Body-Limit werden kontrolliert.
2. Konfiguration wird geladen und validiert; bei Fehlern kein Datenzugriff.
3. HTTPS-Voraussetzung wird geprüft (nur vertrauenswürdige Proxyinformationen).
4. API-Schlüssel wird geprüft **bevor** Ressourcendetails, Schema oder Datensätze offengelegt werden.
5. Router gleicht normalisierten Pfad und unterstützte Methode ab.
6. AccessPolicy prüft die ausdrücklich freigegebene Ressource, Methode und Spalten.
7. Eingaben/Queryparameter werden geparst und auf Typen sowie Grenzwerte validiert.
8. CrudService führt die exakt eine zulässige Operation am Repository aus; Schreiben erfolgt in einer Transaktion.
9. Eine wohldefinierte HTTP-/JSON-Antwort wird erstellt und ein redigierter strukturierter Logeintrag geschrieben.

Für einen unbekannten Ressourcennamen antwortet der Server bevorzugt mit `404`, damit auch authentifizierte Clients keine Liste intern existierender, aber nicht freigegebener Tabellen erhalten. Fehlende oder falsche Authentifizierung ergibt `401`; bekanntes, aber gesperrtes Verfahren `403` oder `405` nach definierter Methode/Rechte-Regel. Der endgültige Vorrang dieser Zustände wird per Contract-Test fixiert.

## 4. Verbindlicher HTTP-/JSON-Kontrakt V0

### 4.1 URI-Schema und HTTP-Methoden

| Methode | Route (bevorzugt) | Semantik | Erfolg |
|---|---|---|---|
| GET | `/api/v0/{resource}` | Sammlung lesen | 200 |
| GET | `/api/v0/{resource}/{id}` | Einzelobjekt lesen | 200 |
| POST | `/api/v0/{resource}` | Einzelobjekt anlegen | 201 + `Location` |
| PUT | `/api/v0/{resource}/{id}` | vollständige Ersetzung der klientseitig schreibbaren Repräsentation | 200 |
| PATCH | `/api/v0/{resource}/{id}` | Teiländerung einzelner freigegebener Felder | 200 |
| DELETE | `/api/v0/{resource}/{id}` | genau ein Objekt löschen | 204, ohne Body |
| GET | `/api/v0/health` | begrenzter Status ohne Details | 200/503 |

Der Sondername `health` ist reserviert und kann nicht als Datenressource konfiguriert werden. Alle öffentlichen URL-Verträge sind **Vorschläge zur technischen Freigabe**, nicht bereits implementierte Schnittstellen. Es gibt in V0 keine freie `/sql`-, `/tables`- oder `/admin`-Route.

### 4.2 Header, Inhaltstypen und IDs

- Authentifizierung: `X-API-Key: <secret>` bei allen Datenrouten; kein Schlüssel in URL oder Querystring.
- Schreiben mit JSON: `Content-Type: application/json`, UTF-8. Ein falscher Medientyp wird mit `415`, ungültiges JSON mit `400` abgewiesen.
- Ausgabe: `application/json; charset=utf-8` außer `204 No Content`. Für Fehler gilt ebenfalls JSON, soweit technisch noch möglich.
- Korrelationskennung: serverseitig generierte zufällige Request-ID in `X-Request-ID`, optional auch im Fehlerobjekt. Eingehende untrusted IDs werden nicht als unkontrollierter Log-Text übernommen.
- Einzel-ID: URL-kodierte einzelne Primärschlüsselkomponente. V0 unterstützt `positive integer` oder `UUID` **nur wenn in Ressourcenkonfiguration freigegeben**; andere Schlüsseltypen und zusammengesetzte Schlüssel sind ausgeschlossen.
- Response `Location` nach POST: kanonische Einzelressourcenroute entsprechend dem dokumentierten URL-Modus; Proxy-/Host-Header dürfen nicht unkontrolliert zu absoluten externen URLs zusammengesetzt werden.

### 4.3 Datenantworten

```json
{
  "data": [
    {"id": 42, "title": "Beispiel", "published": true}
  ],
  "meta": {
    "limit": 25, "offset": 0, "count": 1, "hasMore": false
  }
}
```

Eine Einzelressource liefert `{"data":{"id":42,"title":"Beispiel","published":true}}`. POST/PUT/PATCH geben das persistierte einzelne Objekt in derselben `data`-Hülle zurück. `count` ist die Anzahl **in der aktuellen Seite**, nicht die Datenbank-Gesamtzahl. `hasMore` wird über ein intern um eins erhöhtes, begrenztes Abfragelimit ermittelt; eine teure Gesamtzählung ist V0 nicht vorgeschrieben. Keine unkonfigurierten Spalten, auch keine technischen Passworthashes, dürfen im JSON erscheinen.

### 4.4 Filter, Sortierung und Pagination

- `filter[field]=value` für Gleichheitsfilter; mehrere erlaubte Filter werden mit `AND` kombiniert. Feldnamen entsprechen in V0 freigegebenen öffentlichen Spaltennamen.
- `sort=field` beziehungsweise `sort=-field` für aufsteigend/absteigend. V0 unterstützt **genau ein** Sortierfeld; bei fehlender Sortierung erfolgt stabile Sortierung nach Primärschlüssel.
- `limit` ist eine ganze Zahl von `1` bis `100`; Default `25`. `offset` ist eine ganze Zahl ab `0`, Default `0`, mit konfigurierbarer Obergrenze (erste Planung `10.000`).
- `count` und `hasMore` beschreiben nur die aktuelle, zum Abfragezeitpunkt sichtbare Ergebnismenge; beim gleichzeitigen Schreiben anderer Clients wird **keine** Snapshotsicht über Seiten hinweg garantiert.
- Filterbare und sortierbare Felder werden pro Ressource separat freigegeben. Unbekannte Parameter/Operatoren liefern `400`, nicht stillschweigend ungefilterte Daten.
- Textfilter gelten in der SQL-Kollation des Datenbankfeldes; eine datenbankübergreifende Groß-/Kleinschreibungssemantik wird erst in V1 spezifiziert.

Beispiel: `GET /api/v0/books?filter[published]=true&sort=-id&limit=20&offset=0`. Bei Query-Routing: `/index.php?route=/api/v0/books&filter[published]=true&sort=-id&limit=20`. Die konkrete URL-Kodierung durch Clients bleibt erforderlich.

### 4.5 Validierung und PUT/PATCH-Semantik

**POST:** Nur konfigurierte, anlegbare Felder sind erlaubt. Fehlende Pflichtfelder werden mit `422` gemeldet; serverseitig generierte Schlüssel sind nicht überschreibbar.  
**PUT:** Der Client liefert **alle** als `clientWritable` definierten Felder vollständig (auch optionale Werte müssen als Wert oder explizit `null` auftreten, sofern `nullable`). Der Server ersetzt damit die clientseitig verwaltete Repräsentation; systemverwaltete und schreibgeschützte Felder bleiben unverändert. Der verpflichtende Vollersatz verhindert unbeabsichtigtes Nullsetzen ausgelassener Felder.  
**PATCH:** Nur gesendete und zulässige `clientWritable`-Felder werden geändert; fehlende Felder bleiben unverändert, explizites `null` ist nur bei `nullable=true` zulässig. Ein leeres PATCH-Objekt wird mit `422` abgewiesen.  
**DELETE:** Nur exakte, durch die Route identifizierte Datensätze; keine Massenlöschung.  
**Transaktion:** Pro Schreibrequest genau eine Datenbanktransaktion (PDO begin/commit/rollback); keine HTTP-requestübergreifende Transaktion.

**Einordnung:** Die konkretisierte PUT-Vollständigkeitsregel ist eine umsetzungsseitige Festlegung innerhalb der im Lastenheft verlangten unterschiedlichen PUT/PATCH-Semantik. Falls eine Fachanwendung eine andere Bedeutung von PUT benötigt, ist das vor Implementierung als API-Vertragsentscheidung zu klären.

### 4.6 Fehlerobjekte und HTTP-Codes

```json
{
  "error": {
    "code": "validation_failed",
    "message": "Invalid request body.",
    "details": [{"field": "title", "code": "required"}]
  },
  "requestId": "9fc085c3b2a043d3"
}
``

| Status | Code (Beispiel) | Ursache |
|---|---|---|
| 400 | `invalid_request` | ungültige Querysyntax, ungültiges JSON, Pfad-/Parameterformat |
| 401 | `unauthenticated` | API-Key fehlt, ist ungültig oder wurde widerrufen |
| 403 | `forbidden` | Ressource ist bekannt, aber explizit untersagte Operation; ohne preisgebende Details |
| 404 | `not_found` | unbekannte Ressource oder Einzel-ID |
| 405 | `method_not_allowed` | HTTP-Methode wird auf vorhandener Route nicht unterstützt; `Allow` nur wenn sicher |
| 409 | `conflict` | Eindeutigkeits- oder referenzielle Constraintverletzung |
| 413 | `payload_too_large` | Request-Limit überschritten |
| 415 | `unsupported_media_type` | falscher Content-Type für schreibende Anfrage |
| 422 | `validation_failed` | syntaktisch korrekt, inhaltlich fehlerhaft |
| 429 | `too_many_requests` | optional bei Host- oder Proxy-Limits |
| 500 | `internal_error` | unerwarteter interner Fehler; keine Details |
| 503 | `service_unavailable` | Konfiguration/Datenbank nicht betriebsbereit |

**Vorrangregeln:** zuerst Authentifizierung und sichere Grundprüfung, dann Routensichtbarkeit, Methodenerlaubnis, Format/Validierung und Datenbankzugriff. Unbekannte Datenbankfehler werden nicht mit rohen PDO-Ausnahmen ausgegeben; Klassifizierung von Unique-/FK-Fehlern ist adapterintern. Der Server gibt `Cache-Control: no-store` für authentifizierte Datenantworten aus, solange keine differenzierte Cache-Policy existiert.

## 5. Konfigurationsmodell

### 5.1 Prinzip

Die Konfiguration ist **pro Installation** festgelegt und nicht durch HTTP schreibbar. Eine interne `ConfigLoader`-Komponente liest eine JSON-Datei mit nicht geheimen Definitionen, validiert sie einmal pro Request beim Bootstrap (optionaler sicherer In-Process-Cache ist bei PHP Shared Hosting nicht vorausgesetzt) und verbindet sie mit geschützten Credentials. **Fehlerhafte Konfiguration führt zum sicheren Abbruch**; es gibt keine automatische Freischaltung aller Tabellen.

### 5.2 Beispielkonfiguration (illustratives Vertragsmuster)

```json
{
  "schemaVersion": 1,
  "apiPrefix": "/api/v0",
  "instanceId": "library-test",
  "database": {
    "driver": "pdo_mysql",
    "hostEnv": "SASD_DB_HOST",
    "nameEnv": "SASD_DB_NAME",
    "userEnv": "SASD_DB_USER",
    "passwordEnv": "SASD_DB_PASSWORD"
  },
  "security": { "apiKeyEnv": "SASD_API_KEY", "requireHttps": true },
  "limits": { "defaultPageSize": 25, "maxPageSize": 100, "maxOffset": 10000, "maxBodyBytes": 65536 },
  "resources": {
    "books": {
      "table": "books",
      "primaryKey": { "field": "id", "type": "integer" },
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": {"type": "integer", "readable": true, "clientWritable": false, "filterable": true, "sortable": true},
        "title": {"type": "string", "maxLength": 200, "required": true, "nullable": false, "readable": true, "clientWritable": true, "filterable": true, "sortable": true},
        "published": {"type": "boolean", "required": true, "nullable": false, "readable": true, "clientWritable": true, "filterable": true, "sortable": false}
      }
    }
  }
}
```

Das ist **keine bereits lauffähige Konfigurationsdatei**, sondern das im Pflichtenheft vorgeschlagene Format. Die Felder `hostEnv` etc. referenzieren geheime Variablen, statt deren Werte in JSON zu schreiben. Wird auf dem Zielhosting keine vertrauenswürdige Secret-Umgebungsvariable angeboten, darf alternativ ein nur serverseitig lesbares Secret-File **außerhalb** des Webroots verwendet werden. Ein fehlendes Secret wird nie durch ein Defaultpasswort ersetzt.

### 5.3 Validierungsregeln für Ressourcen

- Öffentliche Ressourcennamen: ASCII `[a-z][a-z0-9_-]*` und reservierte Namen explizit ausschließen.
- Physische Tabellen-/Spaltennamen: nur valide, vom Betreiber festgelegte Bezeichner; vor dynamischer Verwendung serverseitig gegen die konfigurierte Allowlist prüfen; Werte ausschließlich per SQL-Binding.
- Primärschlüssel: eine Spalte, eindeutiger Wert, Integer oder explizit freigegebene UUID; Schlüssel darf nicht über PATCH/PUT verändert werden.
- Jede `GET`-Ressource benötigt mindestens ein lesbares Feld; jede Schreibmethode benötigt definierte schreibbare Felder und ein klares Schlüsselverhalten.
- Jeder filterbare/sortierbare Name muss auch gelesen werden dürfen oder eine bewusst dokumentierte, nicht datenleckende Ausnahme besitzen. **Für V0 wird die Ausnahme nicht zugelassen.**
- Config- und Secret-Pfade dürfen nicht per API-Route heruntergeladen werden. Produktionsdeployments prüfen diese Erreichbarkeit ausdrücklich.

## 6. Persistenzdesign und SQL-Ausführung

### 6.1 `RecordRepository`-Schnittstelle

Die Anwendungslogik arbeitet mit einem minimalen, Datenbank-unabhängigen Vertrag. Vorgeschlagene Operationen: `list(ResourceDefinition, ListQuery): PageResult`, `find(ResourceDefinition, RecordId): ?Record`, `insert(ResourceDefinition, RecordInput): Record`, `replace(ResourceDefinition, RecordId, RecordInput): Record`, `patch(ResourceDefinition, RecordId, RecordInput): Record` und `delete(ResourceDefinition, RecordId): void`. Für die Umsetzung werden in PHP passende Typen und Fehlerobjekte eingeführt; diese Signaturen sind konzeptionell, nicht bereits implementierte PHP-Dateien.

### 6.2 PDO-Adapter und sichere Queries

- PDO-Verbindung mit Fehlerbehandlung über Exceptions, deaktivierter Emulation von Prepared Statements (soweit vom Treiber unterstützt) und `utf8mb4` für MariaDB/MySQL.
- SQL-Tabellen-, Spalten- und ORDER-BY-Bezeichner stammen ausschließlich aus validierten serverseitigen Ressourcendefinitionen. Identifiers werden nach Adapterregeln korrekt gequotet; **niemals** Werte über Stringkonkatenation in SQL schreiben.
- Dynamische WHERE-Werte werden durch benannte oder positionale Parameter gebunden. `LIMIT` und `OFFSET` werden als validierte Ganzzahlen nach einer kontrollierten Adapterstrategie eingesetzt.
- `SELECT *` wird vermieden: Es werden ausschließlich lesbare Spalten ausgewählt. Schreibzugriffe verwenden ausschließlich konfiguriert schreibbare Spalten.
- `UPDATE`/`DELETE` enthalten eine verpflichtende Primärschlüsselbedingung. Ein fehlendes WHERE ist kein zulässiger Codepfad.
- Bei Updates ist die Existenzfrage auch dann korrekt zu behandeln, wenn sich trotz gültigem Ziel keine Feldwerte ändern (`rowCount()===0` beweist allein **nicht**, dass die ID fehlt).
- Transaktionen sind für einzelne POST/PUT/PATCH/DELETE-Operationen verpflichtend; bei Ausnahme wird zurückgerollt. Kein geteiltes Transaktionsobjekt über verschiedene HTTP-Anfragen.
- Schlüssel und Fremdschlüssel werden nicht über interne Schemaänderungen erzeugt. Die Anwendung prüft vor dem Deployment, ob die vorgegebenen Tabellen passend existieren.

### 6.3 Testschema für die Abnahme

Ein dokumentiertes SQL-Testfixture mit mindestens `books(id, title, published)`, einer optionalen zweiten Ressource `authors(id, name)`, definierten Unique-/Foreign-Key-Constraints und mindestens 101 Beispieldatensätzen wird für funktionale Tests vorbereitet. Das Fixture ist **nicht** ein produktives HARALD-Datenbankschema. Tests können die Datenbank vor jedem Lauf reproduzierbar zurücksetzen. Produktionsdaten werden nie für Testzwecke verändert.

## 7. Security-by-default

### 7.1 API-Key-Prüfung

Der Client übermittelt den installationsspezifischen Schlüssel im Header `X-API-Key`. Die V0-Implementierung vergleicht den von einer geschützten Quelle bezogenen Erwartungswert **zeitkonstant**, beispielsweise mittels Vergleich fester SHA-256-Digests mit `hash_equals()`. Vor der Verarbeitung wird die Headerlänge begrenzt. Der Schlüssel wird nicht in der URL, einem Cookie, Log, Stacktrace oder einer Fehlermeldung ausgegeben. Schlüsselwechsel erfordern kein neues Deployment des Programmcodes; bis V1 kann dafür eine kontrollierte Secret-Konfigurationsänderung notwendig sein.

**Unvertrauenswürdige Clients:** Die Kenntnis eines API-Keys in kompilierten Desktopprogrammen, Mobile-Apps oder JavaScript-Quellcode kann nicht als garantiert geschützt betrachtet werden. Die V0-Dokumentation erlaubt produktive Nutzung nur für kontrollierte technische Clients bzw. hinter einem vertrauenswürdigen Backend; direkte öffentlich registrierte Mehrbenutzersysteme brauchen mehr als diesen Mechanismus.

### 7.2 Transport, CORS und Berechtigungsprüfung

- HTTPS wird vor der Datenverarbeitung geprüft; `X-Forwarded-Proto` darf nur von ausdrücklich vertrauenswürdigem Proxy bzw. einer vom Hostingbetreiber garantierten Infrastruktur übernommen werden. Wenn TLS nicht sicher feststellbar oder serverseitig nicht erzwingbar ist, gilt die Instanz als **nicht produktionsfähig**.
- Standard-CORS ist **keine Freigabe**: keine beliebigen Origins mit Credentials. Unterstützte Origins werden explizit konfiguriert. Falls Browser-Clients benötigt werden, sind CORS-Preflight-OPTIONS-Anfragen ausschließlich für genehmigte Pfade/Methoden zu behandeln und dürfen dadurch keine Datenberechtigung ersetzen.
- Methoden- und Spaltenberechtigungen werden unabhängig vom SQL-Konto geprüft. Das SQL-Konto wird zusätzlich mit nur notwendigen GRANT-Rechten eingerichtet.
- Keine per HTTP auslösbare DDL, keine Dateizugriffe auf vom Client gewählte Pfade, kein freier Query-Interpreter.
- Für fehlerhafte Authentifizierung werden keine Schlüsselteile, Konten oder vorhandenen Tabellennamen preisgegeben.

### 7.3 Abuse- und Datenschutz-Grenzen

- Maximal 64 KiB JSON-Request-Body als vorläufiger Default; vor dem Lesen `Content-Length` prüfen und beim tatsächlichen Streamlesen hart begrenzen (nicht auf Content-Length allein vertrauen).
- Maximal 100 Datensätze pro Seite, höchstens 10.000 Offset als erster Betriebswert; beide Grenzwerte sind konfigurierbar und auf dem Zielhosting zu testen.
- JSON-Nesting-Tiefe und Feldanzahl werden durch den Parser/Validator begrenzt; unbekannte Parameter und Felder werden abgewiesen.
- Logdaten sind datensparsam und werden außerhalb des Webroots geschrieben; keine Speicherung von Auth-Headern und ganzen Nutzdaten.
- Ein API-Key ist keine Endbenutzer-Identität. V0 darf daher keine benutzerbezogene Audit- oder Mandantenisolation versprechen.

## 8. Diagnose, Logging und Fehlertoleranz

### 8.1 Health und Readiness

`GET /api/v0/health` liefert ohne API-Key höchstens `{"status":"ok"}` bzw. `{"status":"unavailable"}` (200/503). **Die öffentliche Antwort enthält keine Datenbankdetails und prüft standardmäßig nur den grundlegenden Startzustand.** Ein erweitertes Datenbank-Readiness-Ergebnis darf nur unter authentifizierter Betreiberroute oder internem Diagnosemodus ermittelt werden. Für V0 wird ein authentifizierter Diagnosemodus empfohlen; seine zusätzliche Route gehört zur finalen Reviewentscheidung.

### 8.2 Technische Protokolle

Pro Request werden UTC-Zeitstempel, generierte Request-ID, Methodenname, normalisierte Ressource, Status, Laufzeitkategorie und Fehlerkategorie als strukturiertes JSON-Line-Log geschrieben. Sensitive Felder werden bereits vor dem Logaufruf ausgeschlossen (nicht lediglich nachträglich geschwärzt). Konfigurierbarer Logpfad außerhalb des Webroots und eine Betreiberanleitung für Rotation/Retention sind Pflicht. Bei nicht verfügbarem Logging darf kein Secret ungeschützt in die Standardausgabe fallen; kritische betriebliche Fehler werden sicher behandelt.

### 8.3 Fehlerabbildung

Bekannte Validierungs-, Zugriffs- und Datenbank-Constraint-Ausnahmen werden zentral auf definierte API-Fehler gemappt. Unerwartete Fehler werden mit interner Request-ID geschützt geloggt und als neutrale `500`/gegebenenfalls `503` beantwortet. Debugausgaben, PDO-SQLSTATEs, Stacktraces und Dateipfade gelangen nicht zum Client.

## 9. Testspezifikation

### 9.1 Testebenen

1. **Unit:** `ResourceDefinition`, Feldvalidator, Parameterparser, Authenticator und Fehlermapper mit isolierten Beispielen.
2. **Integration:** Gegen eigene MariaDB-Testdatenbank: alle CRUD-Methoden, Rollback, Constraints, Filter, Pagination, Datentypen.
3. **Contract:** Exakte HTTP-Statuscodes, Header und JSON-Schemata; Normal- und Query-Routing liefern die gleiche Semantik.
4. **Security:** unkonfigurierte Tabelle, unbekannte Felder, SQL-Injection in Filter/Sortierung/ID, fehlerhafter Key, nicht erlaubte Methode, übergroße Bodies, fehlendes HTTPS.
5. **Deployment:** Zwei Instanzen mit getrennten URLs/DBs/Keys; Hosting-Test mit gesperrtem URL-Rewrite und bewusstem Datenbankausfall.

### 9.2 Testdaten, Ausführung und Qualität

Tests erhalten eigene isolierte DB-Credentials und eine wiederholbar erzeugbare Testdatenbank. Jede Testgruppe dokumentiert Setup/Teardown. Das Testkommando wird im README verbindlich festgehalten; ein CI-Lauf kann gegen einen dedizierten MariaDB-Testservice arbeiten, lokale Shared-Hosting-Abnahme erfolgt mit denselben HTTP-Contract-Tests. PHPUnit ist ein möglicher Test-Runner, aber keine im Lastenheft erzwungene Abhängigkeit. **Kein Test darf produktive Tabellen löschen oder Traces mit Secrets erzeugen.**

### 9.3 Konkrete Testfälle und Zuordnung

| Test-ID | Erwartetes Ergebnis | Bezug |
|---|---|---|
| T-V0-01 | GET-Liste/Einzelobjekt; leere Liste; nicht vorhandene ID = 404 | F01, F02, F09 |
| T-V0-02 | POST erzeugt genau einen Datensatz; `Location` und `201` | F03, F09 |
| T-V0-03 | PUT verlangt vollständige mutable Repräsentation, PATCH ändert nur gesendetes Feld | F04, F12 |
| T-V0-04 | DELETE entfernt exakte ID; FK-Konflikt wird als 409 behandelt | F05, F09 |
| T-V0-05 | Neue Tabelle ohne Allowlist nicht erreichbar, ebenso versteckte Spalte | F01, F06 |
| T-V0-06 | Gleichheitsfilter (AND) liefert erwartete Ergebnismenge; SQL-Injection wird blockiert | F07, F12 |
| T-V0-07 | Sort, limit, offset und hasMore konsistent; Maxlimits wirken | F08, F09 |
| T-V0-08 | Fehlender, falscher, widerrufener Key: 401; korrekter Key möglich | F11, F13 |
| T-V0-09 | Ungültige JSON-Daten, Typen und unbekannte Felder sauber abgewiesen | F09, F12 |
| T-V0-10 | HTTPS/CORS, Logs/Health, keine Geheimnisse in Ausgaben | F13, F14, F15 |
| T-V0-11 | Unterschiedliche Instanzen/Keys/Daten sind voneinander isoliert | F10 |
| T-V0-12 | Dokumentierte Installation, Query-Routing-Fallback, automatisierte Suite | F16, F17 |

## 10. V0-Implementierungsschritte und Meilensteine

| Schritt | Ergebnis | Freigabebedingung |
|---|---|---|
| M0 – Repository-Basis | Autoloading, Struktur, Konfigurationsbeispiel, grundlegende Tests | saubere lokale Entwicklungsumgebung und Schutz privater Dateien |
| M1 – Infrastruktur | index.php, Bootstrap, Router, Request-ID, JSON-Fehler, HTTPS, API-Key | unberechtigter Request gelangt nicht zur Datenbank |
| M2 – sichere Lese-API | Ressourcenmodell, PDO, GET-Liste/Einzelobjekt | allowlistgeschützte Reads inklusive negativer Tests |
| M3 – CRUD-Schreibwege | POST, PUT, PATCH, DELETE, Validierung, Transaktionen | alle Schreib- und Constrainttests erfolgreich |
| M4 – Listenfunktionen | Gleichheitsfilter, Sortierung, Pagination, Limits | kontrollierte Queries und Anti-Injection-Tests |
| M5 – Betrieb und Abnahme | Hosting-Fallback, Logs, Health, zweite Instanz, Dokumentation | AT-V0-01 bis AT-V0-10 nachgewiesen |

Die Meilensteine sind eine **sachliche Reihenfolge**, keine Termin- oder Arbeitszeitschätzung. Optimierung erfolgt erst nach einer lesbaren, klar geschichteten V0 mit funktionierenden Tests.

## 11. Rückverfolgbarkeit zum Lastenheft V0

| Lastenheft | Umsetzung in diesem Pflichtenheft | Tests |
|---|---|---|
| V0-F01 | §§3–5, erlaubte Ressourcen/Router | T-V0-01, T-V0-05 |
| V0-F02 | §4.1–4.3, GET-Vertrag | T-V0-01 |
| V0-F03 | §4.1/4.5, §6, POST | T-V0-02 |
| V0-F04 | §4.5, PUT/PATCH | T-V0-03 |
| V0-F05 | §4.5, §6, DELETE | T-V0-04 |
| V0-F06 | §5, Allowlist/Methoden | T-V0-05 |
| V0-F07 | §4.4, parametrisierte Gleichheitsfilter | T-V0-06 |
| V0-F08 | §4.4, Sortierung/Pagination | T-V0-07 |
| V0-F09 | §4.2–4.6, HTTP-/JSON-Fehler | T-V0-01–04/09 |
| V0-F10 | §§2/5/7, Instanzkonfiguration | T-V0-11 |
| V0-F11 | §7.1, API-Key | T-V0-08 |
| V0-F12 | §§4–7, Validierung/SQL-Schutz | T-V0-06/09 |
| V0-F13 | §§2/7, HTTPS/CORS | T-V0-10 |
| V0-F14 | §8.1, Statusdiagnose | T-V0-10 |
| V0-F15 | §8.2–8.3, Protokollierung | T-V0-10 |
| V0-F16 | §§2/4/5/10, Installation und Beispiele | T-V0-12 |
| V0-F17 | §9, CI- und Testebenen | T-V0-12 |

## 12. Abnahme, offene Detailentscheidungen und Risiken

**Abnahmebedingung:** Sämtliche im [Lastenheft V0](./lastenheft-v0.md) definierten MUSS-Anforderungen und AT-V0-01 bis AT-V0-10 sind durch reproduzierbare Tests belegt. Die Nachweise enthalten getestete PHP-/MariaDB-Versionen, Hosting-Modus, Request-/Response-Beispiele, Testprotokoll und bekannte Einschränkungen.

**Vor dem ersten Code-Slice zu prüfen/freizugeben:**

1. Kann das IONOS-Zielhosting private Konfigurations- und Logdateien nachweislich **außerhalb** des Document Root halten?
2. Welche Mechanismen ermöglichen auf dem Zielhosting **zuverlässige HTTPS-Erkennung und -Erzwingung** bei TLS-Terminierung?
3. Welche Schreib-/Leseformen sollen bei Ressourcen mit nicht nullable oder Datenbank-generierten Feldern für PUT gelten? Der hier gewählte strenge Vollersatz ist die vorgeschlagene Lösung.
4. Genügt ein geschützter einzelner API-Key im V0-Einsatz, insbesondere bei geplanten Browser-/Desktop-Clients? Andernfalls muss ein zusätzliches vertrauenswürdiges Backend davor geschaltet werden.
5. Werden integer- und UUID-Schlüssel beide im ersten Testfixture benötigt? Das Ressourcenmodell erlaubt beide, aber die Abnahme muss konkrete Schlüsseltypen belegen.

**Risiken:** Unzuverlässiges Routing, unzureichende Secret-Trennung, falsche Proxy-Header, ungewollte Tabellenfreigabe, SQL-Dialekteffekte und ungesicherte Desktop-/Browser-Schlüssel. Jedes Risiko erhält vor Produktivsetzung einen dokumentierten Test bzw. eine Freigabeentscheidung.

## 13. Änderungsmanagement und Dokumentstatus

Dieses Dokument wird zusammen mit dem V0-Lastenheft versioniert. Änderungen am öffentlich sichtbaren HTTP-Vertrag sind mit Contract-Tests und nachvollziehbarer Entscheidung zu dokumentieren; eine Änderung der fachlichen MUSS-Anforderung erfordert eine Aktualisierung des Lastenhefts. Geplante V1-Features werden nicht rückwirkend zur V0-Abnahmebedingung.

**Status: technischer Entwurf, nicht implementiert, nicht getestet, nicht formal freigegeben.**
