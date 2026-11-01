> **Sprache:** [English (Standard)](technical-specification-v1.md) | **Deutsch**

# SASD-API-Server – Pflichtenheft V1

> **Status:** Technischer Entwurf für V1.0 · **Dokumentversion:** 0.1 · **Stand:** 01.11.2026  
> **Grundlagen:** [Lastenheft V1](./lastenheft-v1.md) · [Pflichtenheft V0](./pflichtenheft-v0.md) · [Lastenheft V0](./lastenheft-v0.md)  
> **Recherche:** [Referenzprodukte-Funktionskatalog](./referenzprodukte-funktionskatalog.md)  
> **Architektur:** [Softwarearchitektur V1](./architektur-v1.md) (Architekturentwurf)  
> **Gültigkeit:** zukünftige, noch nicht implementierte V1.0; keine formale Freigabe oder Abnahme behauptet.

## 1. Zweck und Voraussetzungen

Dieses Pflichtenheft beschreibt eine technisch umsetzbare Lösung zu sämtlichen im V1-Lastenheft beschriebenen Anforderungen. Die **22 MUSS**-Funktionen sind verbindliche Zielbedingungen **nach Freigabe**. Die **fünf SOLL**-Funktionen werden als begrenzte, separat aktivierbare Ausbauschritte entworfen und sind nur nach ausdrücklicher Freigabe verpflichtender Teil des V1.0-Releases. Es wird **nicht** versucht, sämtliche 157 Funktionen der Vorbilder DreamFactory, PHP-CRUD-API und DB2Rest zu kopieren.

**Wesentlich:** Ein funktionierender und abgenommener V0-Stand ist Voraussetzung. Der V0-Kern wird **nicht** durch einen völlig anderen Server ersetzt. V1 ergänzt vorhandene Schichten, erweitert die Ressourcenbeschreibung und führt einen zweiten Datenbankadapter ein. Die Anwendung bleibt unter PHP 8.4 und gewöhnlichem PHP-Hosting ohne Dauerprozess, Docker oder Redis betreibbar.

### 1.1 V0-Fundament, das erhalten bleibt

- Öffentliche Schnittstelle basiert weiterhin auf HTTPS, JSON, API-Schlüsseln und definierten Ressourcen.
- `index.php` bleibt als Front Controller im Webroot verfügbar, einschließlich dokumentiertem Query-Routing-Fallback.
- Keine automatisch veröffentlichten Tabellen, unbekannten Spalten oder beliebigen SQL-Ausdrücke aus Client-Requests.
- Ein Endpunkt verändert höchstens die ausdrücklich adressierten Datensätze und hält die Datenbank-Constraints ein.
- Datenhaltung, API-HTTP-Schicht und Sicherheitsregeln bleiben voneinander getrennt.
- Pro Installation gelten eigene Credentials, Konfigurationen, Daten, Logs und Berechtigungen.

### 1.2 Was V1 zusätzlich erreichen soll

Eine stabile versionierte API, Feld-Aliasse und lesende Views, kontrollierte Filter/Beziehungen, eine zweite Datenbank (**SQLite**), mehrere API-Schlüssel mit Rollen, sicherer Betrieb einschließlich Audit und Rate Limits, automatisch ableitbare OpenAPI-Dokumentation und getestete Mehrsprachigkeit auf Clientseite.

## 2. Lösungsarchitektur

### 2.1 Architekturübersicht

```text
                         HTTPS / REST / JSON
                                  |
                index.php / Router / Request-ID
                                  |
               Middleware-Kette (Transport, Body,
                API-Key, Rolle, Limits, Audit)
                                  |
                         ResourceController
                                  |
              QueryParser / ResourceDTO / Validator
                                  |
                             CrudService
                                  |
             ResourceRegistry + AuthorizationPolicy
                                  |
               RecordRepository + TransactionPort
                     /                   \
          MariaDbPdoAdapter          SqlitePdoAdapter
                     \                   /
                  konfigurierte Datenquelle

  Querschnitt: Config Schema/Upgrade, Errors, OpenAPI,
    Health, Logging, Audit Event Store, Extension API
```

### 2.2 Layer- und Schnittstellenregeln

**HTTP/Controller:** Ermittelt Methode, Ressource, Parameter und HTTP-Kontrakt, enthält aber keine datenbankspezifischen SQL-Ausdrücke. **Application/Service:** Führt erlaubte CRUD-Vorgänge aus und legt deren Transaktionsrahmen fest. **Domain/ResourceRegistry:** Beschreibt externe Feldnamen, Rechte, Relationen, Validatoren und Quellen. **Contracts/Ports:** Gewährleistet einen gemeinsamen Funktionsumfang über Datenbankadapter. **Infrastructure/Adapters:** Übersetzt den gemeinsamen Query-Plan in einen sicheren MariaDB- bzw. SQLite-Dialekt. **Security:** Authentifiziert und autorisiert *vor* jedem Datenzugriff. **Diagnostics/Audit:** Beobachtet die Operation und bewahrt notwendige Ereignisse geschützt auf.

Es werden **keine** schweren Framework-Abhängigkeiten, globalen statischen DB-Verbindungen oder clientseitigen SQL-Interpreter zur Voraussetzung. Auf PHP-Shared-Hosting besteht eine HTTP-Anfrage aus einem unabhängigen PHP-Prozesskontext: Zustände zwischen Requests müssen explizit und sicher gespeichert werden.

### 2.3 Vorgeschlagene Erweiterung der V0-Projektstruktur

```text
index.php
src/Http/{Request,Response,Router,MiddlewareStack}.php
src/Http/Controller/{ResourceController,OpenApiController,HealthController}.php
src/Http/Parser/{ListQueryParser,JsonBodyParser}.php
src/Application/{CrudService,RelationReadService,NamedReadService}.php
src/Application/Query/{FilterExpression,SortSpec,PageSpec,QueryPlan}.php
src/Domain/{ResourceRegistry,ResourceDefinition,FieldDefinition}.php
src/Domain/{RelationDefinition,ViewDefinition,ApiError}.php
src/Contracts/{RecordRepository,TransactionPort,AuditEventWriter}.php
src/Infrastructure/Database/{MariaDbPdoAdapter,SqlitePdoAdapter}.php
src/Infrastructure/Database/{SqlDialect,ConnectionFactory}.php
src/Security/{ApiKeyStore,ApiKeyAuthenticator,RolePolicy,RequestRateLimiter}.php
src/Security/{RecordVisibilityPolicy,KeyManagementService}.php
src/Infrastructure/Audit/{SqlAuditWriter,SecurityEventLogger}.php
src/Infrastructure/Diagnostics/{ReadinessService,MetricsCollector}.php
src/OpenApi/{SchemaBuilder,OpenApiGenerator}.php
src/Config/{ConfigV1Loader,ConfigV1Validator,ConfigMigrator}.php
bin/{config-check,keys-create,keys-revoke,config-migrate}  # optionale Offline-CLI
config/{example.v1.json,example.keys.json}                # keine Secrets
migrations/{mariadb,sqlite}/                              # Offline-/Installationsmigrationen
tests/{Unit,Integration,Contract,Security,Migration,Deployment}/
```

**Wichtig:** Das ist ein architektonischer Strukturvorschlag, nicht die Behauptung vorhandener Dateien. Wenn eine verantwortungsklare, kleinere Struktur dieselben Tests erfüllt, ist sie vorzuziehen.

### 2.4 Request-Lebenszyklus

1. Bootstrap lädt die zu dieser Installation gehörende Konfiguration und validiert ihre Version sowie die Rechte-/Ressourcendefinitionen.
2. Ein serverseitig erzeugter Request-Identifier korreliert Logs und Fehler.
3. Der Router normalisiert Pfad/Query-Parameter, prüft HTTP-Methode, HTTPS und sichere Größenlimits.
4. Ein API-Key wird gegen den geschützten Key Store authentifiziert. Missglückte Authentifizierung liefert keine Tabellen-/Rolleninformationen.
5. Der Rate Limiter prüft ein vorgegebenes Budget; bei fehlgeschlagener eigener Sicherheitsinfrastruktur gilt fail-closed.
6. Die Policy bestimmt, ob Schlüsselrolle, Ressource, Methode, Spalten, Filter, Relationen oder Viewzugriff erlaubt sind.
7. QueryParser/Validator erstellt einen typisierten, begrenzten QueryPlan – niemals frei angehängte SQL-Fragmente.
8. Der passende Adapter übersetzt QueryPlan und Ressource in parametrisierte SQL-Operationen. Bei Schreiben wird ein Transaktionsrahmen eröffnet.
9. Für erfolgreiche Schreibvorgänge wird das notwendige Auditereignis **in derselben Datenbanktransaktion** persistiert, bevor Commit bestätigt wird.
10. Responder liefert standardisiertes JSON und sichere Header; Logger protokolliert Request-ID, Status und bereinigte technische Kennzahlen.

## 3. Stabiler V1-HTTP-Vertrag

### 3.1 Routen

| Methode | Pfad | Ergebnis |
|---|---|---|
| GET | `/api/v1/{resource}` | Liste mit `data` und `meta` |
| GET | `/api/v1/{resource}/{id}` | Einzelressource in `data` |
| POST | `/api/v1/{resource}` | Neuer Datensatz, 201 + `Location` |
| PUT | `/api/v1/{resource}/{id}` | Vollersatz der klientseitig schreibbaren Felder, 200 |
| PATCH | `/api/v1/{resource}/{id}` | Teiländerung, 200 |
| DELETE | `/api/v1/{resource}/{id}` | Einzel-Löschung, 204 |
| GET | `/api/v1/openapi.json` | rollenabhängig geschützte OpenAPI |
| GET | `/api/v1/health` | minimale statusbezogene öffentliche Antwort |
| GET | `/api/v1/ready` | authentifizierte, berechtigte Readiness |
| GET | `/api/v1/queries/{name}` | **SOLL**: vordefinierte, read-only Named Query |

Alternative Pfade über `/index.php?route=/api/v1/...` müssen dasselbe Verhalten besitzen. Die Route `/api/v0/` wird **nicht stillschweigend** zu V1 umgeleitet. Ein paralleler Betrieb von V0 und V1 wird nur nach gesondertem Deprecation-/Migrationskonzept zugesichert.

### 3.2 Einheitliche Formate

Einzelergebnis:

```json
{ "data": { "id": 42, "title": "Architecture", "active": true } }
```

Liste:

```json
{
  "data": [{ "id": 42, "title": "Architecture" }],
  "meta": { "limit": 25, "offset": 0, "count": 1, "hasMore": false }
}
```

Standardfehler:

```json
{
  "error": {
    "code": "forbidden",
    "message": "Access is not permitted.",
    "details": []
  },
  "requestId": "d23f20a5304d4bcb"
}
```

Erfolgsstatus und Fehlercodes folgen grundsätzlich V0. Für `412 Precondition Failed` wird ein eigener Code `precondition_failed` reserviert, falls der **SOLL**-Baustein zur optimistischen Konflikterkennung aktiviert ist. `429` enthält nach Möglichkeit einen sicheren `Retry-After`-Header. `Cache-Control: no-store` bleibt der Standard für geschützte Ressourcen. GET kann unter kontrollierten Bedingungen später spezifische Cache-Header erhalten, jedoch nicht durch bloße Anwendung eines Shared Caches.

### 3.3 Datentypen und Formatgrenzen

| Externer Typ | V1-JSON-Darstellung | Gemeinsame Backend-Regel |
|---|---|---|
| Zeichenkette | JSON string, UTF-8 | konfigurierte Länge und Unicode-Verhalten beachten; Kollation wird nicht unbegründet gleichgesetzt |
| Integer-ID | positive JSON integer bis `2^53-1` | IDs außerhalb sicherer JSON-Integer-Reichweite sind für diesen Typ nicht freigegeben |
| UUID | kanonischer lowercase JSON string | String/UUID-Spalten nach Adapterformat |
| Boolean | `true` oder `false` | MariaDB `TINYINT(1)`-/SQLite `INTEGER`-Werte werden normalisiert; keine freie Wahrheitstabelle |
| Decimal | JSON string nach fester Dezimalschreibweise | keine PHP-Float-Rundung bei Geld-/präzisen Dezimalwerten; SQLite-Speicherstrategie im Schema festgelegt |
| Datum/Zeit | ISO-8601 UTC, z. B. `2026-11-01T14:30:00Z` | Adapter wandeln kontrolliert in/from DB; kein impliziter lokaler TZ-Wechsel |
| `null` | JSON null | nur bei `nullable=true`; Null-Vergleich explizit statt `= NULL` |

Diese Typkonventionen werden in V1 für die unterstützten Felder vertraglich festgelegt. Große Binär-Uploads, offene JSON-Dokumentenfelder und zeitabhängige SQL-Funktionen gehören nicht zur automatischen Standardunterstützung. Wenn ein V0-Client andere Typkonventionen verwendet, muss die Umstellung über V1-Vertragsversion und Migration erfolgen.

### 3.4 V1-Filter- und Sortiergrammatik

V1 nutzt eine **begrenzte, explizite Query-Parameter-Grammatik**, die auch über gewöhnliche HTTP-Clients verwendet werden kann:

```text
filter[field][eq]=value          # Equality
filter[year][gte]=2020          # Greater or equal
filter[state][in][]=draft       # IN list, repeated [] entries
filter[state][in][]=published
filter[title][contains]=API     # Text contains
filter[reviewedAt][isNull]=true # NULL predicate
or[0][state][eq]=draft          # optional single OR group
or[1][state][eq]=published
sort=title,-id                  # multiple allowed sort fields
fields=id,title,state           # allowed response columns
limit=25&offset=0
include=author                 # one approved relation
```

Einzelne `filter`-Bedingungen werden mit AND verknüpft; die **eine** optional unterstützte `or[]`-Gruppe ist logisch ein weiterer geklammerter Ausdruck und wird mit dem übrigen Filter per AND kombiniert. **Keine freie rekursive Filter-DSL**. Vorläufige Budgets: höchstens acht AND-Bedingungen, acht OR-Glieder, maximal 20 Werte je IN-Liste, zehn Sortierfelder sind **nicht** vorgesehen (V1 plant maximal drei), maximale Seitenlänge 100, Offsetgrenze 10.000, eine Relationstiefe. Zahlen und konkrete Budgets müssen im Performance- und Security-Test validiert und vor Vertragsfreigabe bestätigt werden.

Erlaubte Operatoren: `eq`, `ne`, `lt`, `lte`, `gt`, `gte`, `in`, `isNull`, `contains`, `startsWith`. Operatoren werden nur für geeignete deklarierte Feldtypen freigegeben. Jede Feldreferenz wird in `ResourceRegistry` geprüft, der Adapter fügt nur feste SQL-Operatoren ein und bindet **sämtliche Werte als Parameter**. Feldnamen, Sortierausdrücke und Relationennamen werden nie ungeprüft in SQL eingefügt.

**Datenbankübergreifende Textsuche:** MariaDB und SQLite haben unterschiedliche Kollations- und LIKE-Semantik. Der V1-Vertrag spezifiziert deshalb für `contains`/`startsWith` eine klar dokumentierte, zunächst einfach gehaltene Semantik (z. B. ASCII-case-sensitive bzw. explizite Normalisierung auf Anwendungsebene für freigegebene Textfelder). Sprachenübergreifende Unicode-Volltextsuche ist **kein** V1-Versprechen.

### 3.5 Beziehungen und Schutz vor Datenexfiltration

Eine Relation wird in der Konfiguration mit Namen, Quelle, Zielressource, Fremdschlüsselspalten, Kardinalität und erlaubten Lesefeldern definiert. `include=author` erweitert die `books`-Liste um höchstens **eine** freigegebene, lesende Relationsebene. Für jede Quelle und jedes Ziel werden Rollen- und Spaltenrechte separat geprüft. `N+1`-Datenbankabfragen sollen durch gebündelte, begrenzte Lesezugriffe vermieden werden; die technische Join-Strategie liegt beim Adapter. Der Server beschränkt die Zahl eingebetteter Child-Records pro Parent und die Gesamtergebnismenge.

**Wenn eine Relation im Backend nicht sicher oder ohne unverhältnismäßige Last auflösbar ist, wird sie kontrolliert abgewiesen** statt fremde Daten nachzuladen. V1 braucht keine Cross-Database-Joins, keine rekursiven Joins und keine freie SQL-JOIN-Klausel aus dem Request.

## 4. Ressourcen- und Konfigurationsmodell

### 4.1 Versionierte Konfiguration und Migration

V1 führt `schemaVersion: 2` als dokumentierte Konfigurationsversion ein. Ein `ConfigV1Validator` prüft alle Konfigurationen bei Bootstrap bzw. bei einer Offline-Vorabprüfung. Der Migrationspfad **V0 schemaVersion 1 → V1 schemaVersion 2** überträgt Ressourcen, Methoden, Felder und Auth-Settings und erzeugt einen Vorschlag für neue Rollen/Schlüssel. Nicht eindeutig migrierbare Felder erfordern manuelle Review; **nie** eine automatische Öffnung von Rechten.

Beispiel einer V1-Ressource (gekürzt, illustrative Konfigurationsform):

```json
{
  "schemaVersion": 2,
  "instanceId": "harald-test",
  "apiPrefix": "/api/v1",
  "database": { "adapter": "mariadb", "connectionRef": "MAIN_DB" },
  "resources": {
    "books": {
      "source": { "kind": "table", "name": "library_books" },
      "primaryKey": { "field": "id", "column": "id", "type": "integer" },
      "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"],
      "fields": {
        "id": { "column": "id", "type": "integer", "read": true, "write": false },
        "title": { "column": "book_title", "type": "string", "maxLength": 200, "read": true, "write": true, "required": true },
        "active": { "column": "is_active", "type": "boolean", "read": true, "write": true, "required": true }
      },
      "relations": { "author": { "target": "authors", "localColumn": "author_id", "remoteColumn": "id", "cardinality": "many-to-one", "maxDepth": 1 } }
    },
    "book_summary": { "source": { "kind": "view", "name": "v_book_summary" }, "methods": ["GET"] }
  }
}
```

**Kein Secret in diesem JSON:** `connectionRef` bezeichnet eine Betreiberkonfiguration außerhalb des Webroots, nicht eine aus Clientanfragen ableitbare Datenbankadresse. `rolePolicies`, Key-Store und Sicherheitslimits werden in einer gesonderten, ebenfalls versionierten und geschützten Datei verwaltet. Ein Feld in der `fields`-Liste wird nicht allein durch seine Existenz öffentlich; Resource-/Role-Allowlist und `read`-/`write`-Flags müssen gemeinsam passen.

### 4.2 Feld-Aliasse und Schemakompatibilität

Der öffentliche Feldname `title` kann intern auf die Datenbankspalte `book_title` zeigen. Beim Lesen mappt der Adapter DB-Werte in die API-Datentypen; beim Schreiben erlaubt er nur freigegebene API-Namen und wandelt sie in bekannte DB-Spalten zurück. Externe Clients dürfen keine SQL-Spalten- oder Tabellennamen auswählen, die nicht in der Konfiguration stehen.

### 4.3 Views als ausschließlich lesbare Ressourcen

Views erhalten `source.kind=view` und erlauben nur GET, auch wenn der Datenbanktreiber technisch schreibbare Views unterstützt. Filter-, Sortier- und Spaltenfreigaben gelten wie bei Tabellen. Für Sicherheitszwecke sind Views kein Ersatz für Rollen: Der API-Server erzwingt zusätzlich eigene Rechte.

### 4.4 Überprüfung und atomarer Konfigurationswechsel

Ein Offline-Prüfkommando `config-check` oder die entsprechende Paketfunktion validiert JSON-Struktur, Keys, Rollen, Ressourcen, Relationenziele, Feldnamen und Dateirechte. Eine neue Konfiguration wird zunächst neben der laufenden Konfiguration geprüft und anschließend **atomar ersetzt** (z. B. Dateisystem-Rename auf demselben Volume). Fehlerhafte oder unvollständige Änderungen werden nicht teilweise aktiv. Auf Shared Hosting ist die tatsächlich unterstützte Dateisystemsemantik zu testen.

## 5. Datenbankadapter und Persistenz

### 5.1 Gemeinsamer Datenzugriffsvertrag

Der bestehende V0-Port `RecordRepository` bleibt die Basis. V1 ergänzt definierte Fähigkeiten für `QueryPlan`, `RelationSpec`, `ReadOnlyView` und sichere SQL-Transaktionen; die Ports dürfen keine PDO-Methoden oder Dialekt-Strings nach außen reichen. Gemeinsame Tests werden gegen jeden Adapter als Blackbox ausgeführt. Eine neue Datenbank kann später eingeführt werden, ohne den API-Client-Vertrag umzubauen.

### 5.2 MariaDB/MySQL-Adapter

Der MariaDB-Adapter bleibt PDO-basiert, nutzt `utf8mb4`, sichere Prepared Statements und einen wohldefinierten Connection-Lifecycle pro HTTP-Request. Weder SQL-Bezeichner noch Operatoren oder Relationennamen werden aus rohen Requeststrings in Queries kopiert. Data Definition Language (CREATE/ALTER/DROP) ist **nicht** über die öffentliche API zugänglich.

### 5.3 SQLite-Adapter

Der SQLite-Adapter verwendet `pdo_sqlite` und eine **kanonisch validierte SQLite-Datei außerhalb des Document Root**, mit restriktiven Dateirechten. Ein vom Client gelieferter Datenbankpfad ist unzulässig. Der Adapter aktiviert `PRAGMA foreign_keys=ON` für jede Verbindung, konfiguriert einen angemessenen `busy_timeout`, behandelt `SQLITE_BUSY` mit begrenztem Verhalten und nutzt Transaktionen für Schreibvorgänge. Journaling-/WAL-Einstellungen werden je Hostingumgebung geprüft; insbesondere darf WAL bei Netzdateisystemen nicht pauschal als sicher zugesichert werden.

Bei Booleschen Werten, UTC-Datumsfeldern und exakten Dezimalen erfolgt eine definierte Normalisierung über Schema und Adapter. Tests müssen sicherstellen, dass eine logische Ressource denselben V1-Vertrag gegen MariaDB und SQLite erfüllt. **Der Adapterwechsel migriert keine Daten**; dies ist eine gesonderte Betreiberaufgabe.

### 5.4 Gemeinsame Integritäts- und Query-Regeln

- Single-column integer/UUID Primärschlüssel; keine implizite Unterstützung für zusammengesetzte Schlüssel.
- `SELECT` projiziert ausschließlich freigegebene Spalten; `UPDATE`/`DELETE` verwenden die vollständige adressierte ID und, falls aktiviert, Versionsbedingung.
- Konstant definierter Umgang mit Missing/NULL/Default bei POST/PUT/PATCH. V0-Vollersatzregel gilt in V1 unverändert, soweit nicht in einer Version explizit geändert.
- Gleichartige Fehlerkategorien für Duplicate-Key, Foreign-Key, Invalid-Value, Locked-Database, Missing-Record und unerwarteten Datenbankausfall.
- Jeder erfolgreiche Schreibrequest commitet Datenänderung **und** verpflichtendes Auditereignis atomar (siehe §7).
- Identische erlaubte Filter-/Sortiersemantik, soweit in beiden Adaptern implementiert; sonst kontrollierte Ablehnung statt irreführender Ergebnisse.

## 6. Sicherheits- und Berechtigungskonzept

### 6.1 Technische Clientidentitäten statt Endbenutzerverwaltung

V1 authentifiziert primär technische Clients über `X-API-Key`. Dies ist **keine** interaktive Benutzerverwaltung. Jeder Schlüssel gehört zu einer eindeutigen technischen Kennung und einer Rolle. Browsercode, der einen Schlüssel direkt ausliefert, wird nicht als geschützte Identitätsquelle betrachtet. Für öffentliche Endnutzeranwendungen wird weiterhin ein vertrauenswürdiger Backend-Proxy oder eine separat spezifizierte Endbenutzeridentität benötigt.

### 6.2 Schlüsselgenerierung und geschützte Ablage

Schlüssel werden durch ein **Offline-Betreiberwerkzeug** mit kryptografisch sicherem Zufall (mindestens 256 Bit Secret-Entropie) erzeugt. Das Secret wird beim Erstellen **einmal** angezeigt; in der geschützten Konfiguration liegt eine Kennung und ein kryptografisch sicherer Prüfdigest (zum Beispiel SHA-256 des hochentropischen Zufallssecrets), nicht der Klartext. Der Lookup verwendet eine unkritische Key-ID; der Digestvergleich ist konstantzeitlich. Ein gestohlener Token kann in der Konfiguration mit `disabled=true` widerrufen und durch einen neuen ersetzt werden. Bereits ungültige Keys bleiben ungültig; eine Keyrotation benötigt keine Änderung der Anwendungsquellen.

Da Shared Hosting nicht zwingend eine interaktive Server-CLI bereitstellt, muss das Offline-Tool auch auf einem Administrator- oder Entwicklungsrechner laufen können. Die **sichere Übertragung** der vorbereiteten Key-Konfiguration an den Zielhost gehört zum Deployment-Runbook. Eine internetexponierte Admin-/Schlüssel-Erstellungs-API wird nicht verlangt.

### 6.3 Rollenbasierte Policies

Beispielhafte Rollen `reader`, `editor`, `operator` beschreiben **keine** fest im Programm verdrahtete Berechtigung. Pro Rolle wird eine Liste erlaubter Ressourcennamen, Methoden, lesbarer und schreibbarer Felder, optionaler Relationen und benannter Abfragen hinterlegt. Nicht definierte Rechte gelten als verboten. Die Operatorrolle erhält nur die explizit vorgesehenen Diagnose- und Konfigurationskontrollen, nicht automatisch Zugang zu allen Anwendungsdaten.

**Prüfungsreihenfolge:** Authentifizierung → Rolle → Ressource → Methode → Feld-/Query-/Relation-Berechtigung → Datensatz-Sichtgrenze (falls aktiviert) → Datenbankzugriff. Spalten und Relationen, auf die kein Zugriff besteht, dürfen auch nicht in Fehlermeldungen oder aggregierten Ergebnissen durchschimmern.

### 6.4 Optional: datensatzbezogene Sichtgrenzen (SOLL V1-F14)

Ein optional aktivierter `RecordVisibilityPolicy` fügt serverseitig einen vertrauenswürdigen Vergleich hinzu, etwa `owner_id = <serverAssignedClientOwnerId>`. Der Wert stammt ausschließlich aus der authentifizierten technischen Identität bzw. Betreiberkonfiguration, **nicht** aus einer freien Query. Die Regel gilt einheitlich für Lesen, Zählen, Joins, PATCH, PUT und DELETE. Für Datenänderungen wird der Filter im selben SQL-Statement bzw. einer transaktional gleich sicheren Operation durchgesetzt. Ohne vollständig getestetes Verhalten auf beiden Datenbanken bleibt die Funktion deaktiviert und darf nicht als mandantensicher beworben werden.

### 6.5 HTTPS, CORS, sichere Antwortheader

HTTPS und Reverse-Proxy-Prüfung folgen V0. CORS-Origin-Allowlist ist installationsbezogen; der Server setzt für authentifizierte Antworten `Cache-Control: no-store` und einen geeigneten `Content-Type`. `X-Content-Type-Options: nosniff` wird gesetzt. Keine unkontrolliert übernommenen `Forwarded`- oder `X-Forwarded-Proto`-Werte. Die dokumentierten Deployments müssen einen funktionsfähigen HTTPS-Zwang nachweisen, nicht allein einen optionalen Client-Redirect.

### 6.6 Rate Limiting ohne Redis-Pflicht

Für eine einzelne PHP-Shared-Hosting-Instanz kann ein persistenter, kurzlebiger **dateibasierter Token-Bucket- oder Sliding-Window-Zähler** außerhalb des Webroots verwendet werden, sofern exklusive Dateisperren und atomare Updates auf dem Zielhost nachgewiesen sind. Zusätzlich wird ein Budget für ungültige Schlüsselversuche anhand vertrauenswürdig abgeleiteter Clientadresse geführt, um Header-Bruteforce abzufangen. Die endgültigen Zahlen werden anhand eines Lastprofils dokumentiert (Ausgangswert zum Testen z. B. 60 erlaubte Datenrequests je Minute und Key).

Wenn der Rate-Limit-Store nicht sicher aktualisiert werden kann, gilt für geschützte Datenendpunkte **fail-closed** (kontrollierter `503`-Fehler statt ungeprüfter Freigabe). Bei mehreren Webserverknoten ohne gemeinsamen atomaren Store ist das lokale Verfahren **nicht** als globale Rate-Garantie geeignet; ein verteilter Limiter wäre dann eine spätere Betriebsalternative. V1 führt keine Redis-Pflicht ein.

## 7. Audit, Diagnose, Monitoring und Fehlerbehandlung

### 7.1 Audit-Ereignisse für Schreibvorgänge

Für jeden erfolgreichen POST/PUT/PATCH/DELETE wird **im selben lokalen DB-Transaktionskontext** ein begrenztes Auditereignis persistiert. Dafür wird eine **interne**, nicht über die generische Daten-API veröffentlichte Tabelle `sasd_api_audit_events` pro Instanzdatenbank vorgesehen. Das Migrationspaket stellt MariaDB- und SQLite-DDL für das interne Audit-Schema bereit; es ist **keine über HTTP erreichbare Schemaänderungsfunktion**. Die Tabelle darf nur über kontrollierte Operatorprozesse abgefragt werden.

Minimalfelder: `event_id`, `occurred_at_utc`, `instance_id`, `request_id`, `client_id`, `resource_name`, `method`, `record_key`, `outcome`. Keine kompletten Record-Bodies und keine API-Secrets. Schlägt das Schreiben des Auditereignisses fehl, muss die zugehörige Datensatzänderung zurückgerollt werden, statt einen nicht protokollierten Erfolg zu melden. **Keine** Behauptung einer manipulationssicheren, revisionszertifizierten Auditplattform.

Abgewiesene Auth-/Policy-Versuche führen **keine** Datentransaktion aus; sie werden als datensparsame technische Sicherheitsereignisse über den geschützten Logger mit Request-ID erfasst. Fehlende Dateischreibrechte müssen als Betriebsfehler erkannt und dokumentiert werden.

### 7.2 Health und Readiness

`GET /api/v1/health` zeigt nur `ok` oder `unavailable` ohne Schema- oder Hostinformationen. `GET /api/v1/ready` erfordert eine Operatorberechtigung und prüft Konfiguration, ausgewählten Datenbankadapter, Datenbankverbindung und Erreichbarkeit des internen Audit-Schemas. Werte werden mit einem sicheren, festen Antwortmodell und ohne PDO-Verbindungsdetails ausgegeben.

### 7.3 Logs und Metriken

Technische Logs enthalten UTC-Zeit, Request-ID, Instanz, Route, Status, technische Clientkennung (soweit rechtlich zulässig), Fehlerkategorie und knappe Laufzeitmetrik. Logs stehen außerhalb des Webroots, werden aufbewahrungsbegrenzt und niemals pauschal mit SQL-Strings, Auth-Headern oder Personendaten befüllt. V1-F27 (SOLL) ergänzt Aggregate aus Anfrageanzahl, Antwortzeit und Fehlerkategorien; ein Prometheus-Endpunkt, Grafana und Datadog sind nicht verpflichtend.

### 7.4 Fehlerfälle

Die zentralen HTTP-Status- und JSON-Fehlerregeln aus V0 gelten weiterhin. V1 ergänzt sauber klassifizierte Fälle für Role denied (403), Rate budget exhausted (429), fehlgeschlagene Vorbedingung (412, nur bei aktivem Optimistic Lock), nicht unterstützte Adapterfähigkeit (400/422 nach klarer Vertragsdefinition), SQLite-Lock-/Timeout-Situation (kontrolliert 503) und fehlerhafte Konfiguration (503). Kein detaillierter SQLSTATE, Dateipfad, Token oder Stacktrace in Antworten.

## 8. Optionaler V1-Ausbau mit klarer Priorität

### 8.1 Benannte Leseabfragen (SOLL V1-F09)

Ein `NamedReadService` registriert vordefinierte, **read-only** SQL-Abfragen des Betreibers mit Parametertypen, Rollen und Ergebnisprojektion. SQL-Text wird niemals als HTTP-Parameter entgegengenommen. Ein statisch geprüfter Query-Name wird auf eine freigegebene SQL-Vorlage aufgelöst; Parameter werden gebunden, Ergebnisgröße und Timeouts limitiert. Unterschiedliche SQL-Dialekte erfordern eigene Vorlagen oder eindeutig begrenzte gemeinsame Abfragen. Nicht unterstützte Queries werden explizit deaktiviert.

### 8.2 Optimistische Konflikterkennung (SOLL V1-F16)

Konfigurierbare Ressourcen können ein monotones Versionsfeld besitzen. GET liefert dessen Darstellung beispielsweise als `ETag`; PUT/PATCH erfordern bei aktivierter Funktion `If-Match`. Eine atomare UPDATE-Bedingung prüft ID **und** erwartete Version und erhöht sie bei Erfolg; fehlende oder falsche Vorbedingung wird mit einem passenden 4xx-Code (`428` für erforderliche, `412` für nicht passende Bedingung) beantwortet. `rowCount=0` ist durch sichere Exists/Version-Prüfung genauer einzuordnen. Der Mechanismus darf nur für zuverlässig versionierte Tabellen aktiviert werden.

### 8.3 Erweiterungspunkte (SOLL V1-F22)

Ein Registry-Eintrag erlaubt zusätzliche, ausdrücklich definierte Controller oder Read Services. Jede Erweiterung muss die zentrale Auth-, Role-, Limit-, Error-, Audit- und Logging-Pipeline verwenden. Es gibt weder dynamischen PHP-Eval noch internetseitigen Skript-Upload oder allgemeine Plugin-Installation über HTTP. Eine Beispielerweiterung wird mit negativem Berechtigungstest geliefert, wenn die Funktion aktiviert wird.

### 8.4 Datensatzgrenzen (SOLL V1-F14)

Siehe §6.4. Diese optionale Funktion wird nicht als fertige Multi-Tenancy beworben; sie erhält eine eigene Security-Testmatrix inklusive Zählungen, Sortierung, Joins, Änderungen und nicht erlaubter Filterwerte.

### 8.5 Betriebsmetriken (SOLL V1-F27)

Die in §7.3 genannten Aggregate werden optional erhoben. Die Erfassung erfolgt ohne sensitives Request-Logging und ohne verpflichtende Hintergrunddienste. Jede Messung nennt Testumgebung, Datenvolumen, Parallelität und Routingmodus; es werden keine unbelegten Performanceversprechen gemacht.

## 9. OpenAPI und Clientintegration

### 9.1 Dokumentenerzeugung

Ein `OpenApiGenerator` erzeugt OpenAPI 3.1 (sofern eingesetzte Generator-/Validatorwerkzeuge diesen Stand korrekt unterstützen; sonst dokumentierter, kompatibler 3.0.x-Stand) aus Ressourcendefinitionen, Query-/Field-Schemas, Status-/Fehlerobjekten, API-Version und Authentifizierung. Die OpenAPI-Definition verwendet `apiKey` in Header `X-API-Key`. Damit sind weder frei wählbare SQL-Tabellen noch nicht aktivierte Funktionsmodule automatisch dokumentiert.

Die Auslieferung von `/api/v1/openapi.json` erfordert authentifizierte Rechte; es wird pro Rolle **nur** die entsprechend autorisierte Sicht generiert. Um Fehlinformationen zu vermeiden, erklärt die Doku verbindlich, dass OpenAPI die Policy-Prüfung **nicht ersetzt** und sich berechtigte Felder je Clientrolle unterscheiden können. Das generierte Dokument wird gegen einen OpenAPI-Validator und echte HTTP-Contract-Tests abgeglichen.

### 9.2 Clients in C#, Java und curl

Die API verlangt ausschließlich HTTPS, JSON und `X-API-Key`. Die Entwicklerdokumentation enthält eine funktionsfähige GET-Beispielanfrage für curl, eine .NET-`HttpClient`-Variante und eine Java-`HttpClient`-Variante. Alle Beispiele verarbeiten 401/403/404 und die standardisierte Fehlerstruktur. Es besteht **keine** Pflicht zur Entwicklung eigener SDKs oder von Desktopkomponenten.

## 10. Konfigurations- und Deploymentmigration V0 → V1

1. Vor dem Update: Datenbankbackup erstellen und eine Wiederherstellung an einem **Testsystem** nachweisen. Separate Sicherung der nicht geheimen Ressourcen-Konfiguration und des geschützten Key-Stores; niemals Klartextsecrets in Git.
2. Im Testsystem eine Kopie der V0-Instanz betreiben. Die V0-Konfiguration auf das neue `schemaVersion=2`-Format übertragen; fehlende neue Felder nur mit **gesperrten** Defaults anlegen.
3. Den zusätzlichen SQLite-Adapter in einer **eigenen** Instanz mit separater Testdatenbank qualifizieren, nicht ungeplant die produktive MariaDB überschreiben.
4. Die notwendigen Operator-Migrationen für interne Audittabellen ausführen (MariaDB oder SQLite). Die API erhält **keinen** öffentlichen DDL-Endpunkt.
5. Betreiber-Key-Store mit Rollen anlegen. Alte API-Schlüssel dürfen nicht stillschweigend als neue Owner-/Admin-Schlüssel importiert werden; jedes alte Credential benötigt eine explizite Rollenfreigabe oder Rotation.
6. V1-Konfiguration offline validieren, Readiness testen, OpenAPI prüfen und Vertrags-/Security-Tests auf beiden Datenbanken ausführen.
7. V1 unter einem eindeutig versionierten Pfad aktivieren. V0-Clients dürfen nicht ohne bewusste Migration auf die neue Semantik umgeleitet werden.
8. Im Fehlerfall mit dokumentiertem Rollback auf die gesicherte Konfiguration bzw. Softwareversion wechseln. Wegen möglicher Schemaänderungen ist auch ein Datenbank-Restore als Notfallpfad zu dokumentieren; Rückabwicklung wird nicht als automatisch verlustfrei versprochen.

### 10.1 Konfigurationssicherheit unter Shared Hosting

Alle aktiven Konfigurations-, Schlüssel- und Logdateien liegen **außerhalb** des öffentlich erreichbaren Document Root. Pfadzugriff wird vor dem ersten produktiven Start mit HTTP-Negativtests geprüft. Darf ein Provider diese Trennung nicht sicher leisten, darf V1 nicht als produktionsfähig freigegeben werden. Es gibt keinen dauerhaft erforderlichen Worker. Offline-Migration und Key-Erstellung können auf einem vertrauenswürdigen Entwicklungsrechner erfolgen; manuell ausgeführte SQL-Setup-Skripte sind zulässig, wenn Server-CLI fehlt.

## 11. Teststrategie, Contract-Tests und negative Sicherheitstests

### 11.1 Testpyramide und Betriebsumgebungen

- **Unit:** QueryParser, Feldtyp-Konverter, Konfigurations-/Role-Validator, Rate-Limit-Budgetberechnung, OpenAPI-Schemas.
- **Integration:** CRUD/Constraints/Transaction/Audit gegen MariaDB und SQLite; nachvollziehbare Fixtures für Relation, View, Sperrkonflikte und Rollen.
- **Contract:** dasselbe HTTP/JSON-Testpaket auf beiden Adaptern mit festen Inputs/Outputs; Abweichungen werden explizit als nicht unterstützte Datenfunktion dokumentiert, dürfen aber keine MUST-Grundfunktion betreffen.
- **Security:** unautorisierte Tabellen/Spalten, Role-Escalation, SQL-Injection, Filter-/Relation-/Count-Leaks, fremde Instanzkeys, CORS-/HTTPS-Fehlkonfiguration, Rate-Limiter-Fail-Closed.
- **Migration:** bestehende V0-Konfiguration und Datenfixture → V1-Draft → erfolgreiche Umstellung und fehlerfreier Rollback-Fall.
- **Deployment:** Linux/PHP Shared Hosting mit `index.php`-Fallback sowie lokale Entwicklungsumgebung unter Windows/WSL. Für Dateilimiter und SQLite sind Sperrsemantik und Rechte unter echter Host-Parallelität zu testen.

### 11.2 V1-Abnahmetestfälle

| Test-ID | Technischer Nachweis | Lastenheft V1 |
|---|---|---|
| T-V1-01 | OpenAPI/HTTP-Vertrag und Rückwärtskompatibilität versioniert | F01, F21 |
| T-V1-02 | Alias-Ressourcen, Spalten, unfreigegebene Tabellen/Views unsichtbar | F02, F08 |
| T-V1-03 | CRUD + Validierung auf MariaDB und SQLite mit identischen Fehlerkategorien | F03, F04, F10, F15 |
| T-V1-04 | Vergleichs-/NULL-/IN-/AND-/OR-Filter, Field-Projektion und sortiertes Paging | F05, F06 |
| T-V1-05 | Ein-Level-Relation und Schutz vor unerlaubter Expansion/Spaltenleck | F07, F13 |
| T-V1-06 | Konfigurierbare View nur lesbar, schreibende Requests abgewiesen | F08 |
| T-V1-07 | Zwei unabhängige Installationen, verschiedene DBs, Konfigurationen und Keys | F10, F11 |
| T-V1-08 | Keyrotation, Revokation und rollenabhängige Rechte für GET/Schreiben | F12, F13 |
| T-V1-09 | Audit im gleichen Commit, FK-/Unique-Konflikte, Rollback bei Auditfehler | F15, F19 |
| T-V1-10 | Begrenzte Anfragegröße, Filter-/Join-Budget, Ratestore-Ausfall, TLS/CORS | F17, F18 |
| T-V1-11 | Geschützte Readiness und öffentlicher Health ohne Datenleck | F20 |
| T-V1-12 | Fehlerhafte Konfiguration abgelehnt; V0→V1 Migration mit Backup/Recovery | F23, F24 |
| T-V1-13 | C#, Java und curl Beispiele nutzen denselben V1-GET-Vertrag | F25 |
| T-V1-14 | Gesamte Unit-/Integrations-/Contract-/Security-Suite in CI und lokal | F26 |
| T-V1-15 | Optional: Named SQL read-only mit SQL-Injection-Negativtest | SOLL F09 |
| T-V1-16 | Optional: Row-Level-Sicht, Optimistic Lock, Extensions, Metrics | SOLL F14, F16, F22, F27 |

### 11.3 Freigabegates

**V1.0 darf erst freigegeben werden**, wenn V0-Abnahme nachweislich weiterhin gilt, alle V1-MUSS-Anforderungen erfolgreich getestet sind, beide verpflichtenden Datenbankadapter die gemeinsamen Contract-Tests bestehen, der Security-Gate keine offenen kritischen Probleme aufweist und Installation sowie Restore-Test dokumentiert wurden. Nicht implementierte SOLL-Funktionen sind als solche zu deklarieren; Tests dafür sind nur verpflichtend, wenn die Funktionen aktiviert bzw. freigegeben werden.

**Konkretisierung:** Die Beispiele in diesem Dokument enthalten vorgeschlagene Defaultbudgets. Vor der ersten produktiven V1-Freigabe werden sie mit realen Testergebnissen bestätigt bzw. als versionierter Konfigurationsvertrag angepasst. Es gibt keine erfundenen Service-Level-Zusagen.

## 12. Traceability: Lastenheft V1 → technische Lösung → Tests

| Requirement | Umsetzung | Test |
|---|---|---|
| V1-F01 | §3.1–3.3 versionierter HTTP/JSON-Vertrag | T-V1-01 |
| V1-F02 | §4.1–4.3 ResourceRegistry/Aliase | T-V1-02 |
| V1-F03 | §3.1–3.2, §5 CRUD/Schreibsemantik | T-V1-03 |
| V1-F04 | §3.3, §5 Typnormalisierung/Validierung | T-V1-03 |
| V1-F05 | §3.4 QueryParser/Operatoren | T-V1-04 |
| V1-F06 | §3.4 Sort/Projection/Pagination | T-V1-04 |
| V1-F07 | §3.5 RelationReadService | T-V1-05 |
| V1-F08 | §4.3 Read-only Views | T-V1-02/06 |
| V1-F09 | §8.1 Named SQL (SOLL) | T-V1-15 optional |
| V1-F10 | §5 MariaDB+SQLite Adapter | T-V1-03/07 |
| V1-F11 | §§4/10 getrennte Installationen | T-V1-07 |
| V1-F12 | §6.1–6.2 Key Store/Rotation | T-V1-08 |
| V1-F13 | §6.3 RBAC | T-V1-05/08 |
| V1-F14 | §6.4 Row-Level-Sicht (SOLL) | T-V1-16 optional |
| V1-F15 | §§5.4/7.1 atomare Operation/Audit | T-V1-09 |
| V1-F16 | §8.2 Optimistic Lock (SOLL) | T-V1-16 optional |
| V1-F17 | §§3.4/6.6 Requestbudgets/Rate Limits | T-V1-10 |
| V1-F18 | §6.5 TLS/CORS/Defaults | T-V1-10 |
| V1-F19 | §7.1–7.3 Audit und Logs | T-V1-09 |
| V1-F20 | §7.2 Health/Readiness | T-V1-11 |
| V1-F21 | §9.1 OpenAPI | T-V1-01 |
| V1-F22 | §8.3 Erweiterungen (SOLL) | T-V1-16 optional |
| V1-F23 | §4.4, §10 Config-Versionswechsel | T-V1-12 |
| V1-F24 | §10 Installation/Restore/Update | T-V1-12 |
| V1-F25 | §9.2 HTTP-Clientbeispiele | T-V1-13 |
| V1-F26 | §11 vollständige Testgates | T-V1-14 |
| V1-F27 | §8.5 Kennzahlen (SOLL) | T-V1-16 optional |

## 13. Arbeitsabschnitte und Entwicklungsvorgehen

| Phase | Inhalt | Vorbedingung für Folgeschritt |
|---|---|---|
| P0: Baseline und Stabilisierung | Abgenommene V0, CI-, Deployment- und Sicherheitsstand reproduzierbar | V0-Gates vollständig grün |
| P1: Vertrags-/Config-V2 | Versionierter V1-Pfad, Feldaliase, Typmodell, Konfigurationsmigration | Contract-Tests sichern V0/V1-Verträge |
| P2: SQLite-Adapter | PDO SQLite, gemeinsame Repository-Ports, Cross-DB-Contract-Tests | CRUD/Filter/Transaktionen auf zwei Backends grün |
| P3: Suche und Views | sichere Filtergrammatik, Projektion, Sorting, read-only Views | Parser-/SQL-Injection-/Budgettests grün |
| P4: Relationen und Policies | ein-Level-Relationen, Key Store, Rollen/Permissions, Rate Limits | Security-Gates auf allen Datenquellen grün |
| P5: Betrieb und Audit | auditierte Schreibtransaktionen, Readiness, Config-Migration | Restore-, Audit- und Ausfalltests grün |
| P6: OpenAPI und Clients | rollenspezifisches OpenAPI, curl/Java/.NET Dokumentation | Schema und Endpunkte durch Contract-Tests deckungsgleich |
| P7: Optionale SOLL-Slices | benannte Queries, Row Scope, Optimistic Lock, Extensions, Metrics – nur nach Freigabe | jeder Slice mit eigener Abnahme, kein Aufweichen des Kerns |
| P8: V1-Abnahme | umfassende Tests, Hosting-Test, Security Review, Release-Dokumentation | formale Freigabe durch Projektverantwortung |

Die Schritte sind bewusst in kleine, nachvollziehbare Entwicklungsinkremente gegliedert. Die Qualität der Schichten, vollständige Kommentare an wichtigen Klassen und nachvollziehbare Tests sind höher priorisiert als frühzeitige Optimierung. **Zeitpläne und Ressourcen werden hier nicht erfunden.**

## 14. Technische Risiken und offene Freigabeentscheidungen

1. **Shared Hosting:** Sind `pdo_sqlite`, sichere private Dateipfade, `flock`-Dateisperren und Konfigurationswechsel auf dem realen IONOS-Tarif einsetzbar? Ohne gesicherten Betrieb braucht die jeweilige Funktion eine andere freigegebene Umsetzung.
2. **SQLite auf Shared Hosting:** Dateisperren, Busy-Timeout, Journalmodus, Schreibparallelität und Dateisystemtyp müssen mit realer Hostumgebung getestet werden; keine unbelegte hohe Parallelität versprechen.
3. **Ausfall des Rate-Limit-Stores:** Fail-closed für Datenzugriff kann Verfügbarkeit reduzieren. Eine explizite Betriebsentscheidung ist erforderlich; Schutz darf nicht heimlich deaktiviert werden.
4. **Audit-Verfügbarkeit:** Verpflichtende atomare Schreibprotokollierung erfordert ein internes Audit-Schema auf beiden Datenbanken und entsprechende minimal nötige DB-Rechte. Migration und Berechtigungen müssen vor Produktivsetzung geklärt sein.
5. **Type Fidelity:** Dezimal-/Boolean-/Datumsverhalten der zwei Datenbanken kann voneinander abweichen. Ohne Normalisierung und gemeinsame Contract-Tests ist der Adapter nicht freigegeben.
6. **CORS/API-Key:** Browserclients mit eingebautem API-Key sind keine sichere Identitätsstrategie. Eine separat entwickelte, echte Endbenutzer-Authentifizierung kann nicht durch CORS ersetzt werden.
7. **Rollensicherheit:** Felder können durch Filter, Sort, Joins, Count, Error-Responses und OpenAPI indirekt verraten werden; deshalb Security-Tests auf allen Abfragewegen.
8. **SOLL-Umfang:** Named SQL, Row Scope, Optimistic Lock, Controller-Erweiterungen und Metriken sind **bewusst optional**. Ihr Wegfall darf den V1-MUSS-Vertrag nicht beschädigen.

### 14.1 Vor dem Implementierungsstart ausdrücklich zu entscheiden

- Freigabe des Ressourcenkonfigurationsschemas V2 und des V0→V1-Migrationsverfahrens.
- Freigabe der Query-Grammatik einschließlich `filter`, OR-Gruppierung, Offset-Grenze und Unicode-Textsuche.
- Festlegung des Umgangs mit SQLite-Datei- und Schreibsperren auf dem vorgesehenen Hosting.
- Entscheidung über Key Store (geschützte Datei) und atomare Offline-Keyrotation.
- Entscheidung, ob das interne Audit-Schema mit jedem Produktdeployment automatisch per Operator-Migration oder manuell installiert wird.
- Bestätigung der OpenAPI-Auslieferung pro Rolle und der Berechtigung für `/api/v1/ready`.
- Entscheidung, welche SOLL-Slices separat vor oder nach der V1.0-Freigabe umgesetzt werden.

## 15. Abnahme und Dokumentenpflege

Das [Lastenheft V1](./lastenheft-v1.md) bleibt für den fachlichen Umfang maßgeblich; dieses Dokument konkretisiert die technische Lösung. Jede Abweichung von einer MUSS-Anforderung ist **vorher** fachlich zu genehmigen und im Lastenheft nachzuführen. Die gemeinsamen Contract-Tests, Security-Negativtests und Restore-Nachweise sind Bestandteil der Freigabe. Externe Referenzprojekte und deren Features sind keine verbindlichen SASD-Implementierungen.

**Dokumentstatus: ausführlicher technischer Entwurf; kein bestehender V1-Code, keine durchgeführte Abnahme und keine bereits erteilte fachliche Freigabe.**
