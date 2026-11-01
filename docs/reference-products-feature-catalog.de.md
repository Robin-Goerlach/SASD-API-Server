> **Sprache:** [English (Standard)](reference-products-feature-catalog.md) | **Deutsch**

# Funktionskatalog der API-Server-Referenzprodukte

> **SASD-API-Server – technische Referenzsammlung, Stand 2026-11-01**
>
> Dieses Dokument beschreibt die Funktionen der drei für SASD ausgewählten Vorbilder **PHP-CRUD-API**, **DreamFactory** und **DB2Rest**. Es ist eine Recherche- und Ideenbasis, **kein Lastenheft und kein Entwicklungsauftrag** für SASD V0.1.0.

## 1. Zweck, Abgrenzung und Leseregeln

Das SASD-Projekt beginnt bewusst klein: ein leichtgewichtiger, konfigurierbarer REST-Server mit MariaDB/MySQL, sauberem Datenzugriff und mehreren unabhängig betreibbaren Instanzen. Wir untersuchen die Produkte, um bewährte Funktionen und architektonische Lösungswege zu verstehen; die Übernahme einzelner Funktionalitäten wird später gesondert beschlossen.

**Wichtige Begriffe:** CRUD = Create/Read/Update/Delete; Datenbank-Introspektion = Ermitteln von Tabellen, Spalten, Schlüsseln und Beziehungen aus Metadaten; Middleware = wiederverwendbare Verarbeitung vor oder nach einem HTTP-Endpunkt; RBAC = rollenbasierte Berechtigungssteuerung; Mandant = logisch isolierter Datenbereich.

**Nachweisstatus:** Wenn eine Funktion unten als vorhanden beschrieben wird, ist sie in einer offiziellen README oder Produktdokumentation dokumentiert; damit ist sie **nicht** automatisch in dieser Recherche ausgeführt oder durch Tests bestätigt. Insbesondere DreamFactory mischt Community- und kommerzielle Angebote in seiner Funktionsübersicht. DB2Rest unterscheidet in der Übersicht nicht immer klar zwischen ausgereiften und breit angekündigten Features. Einschränkungen sind deshalb explizit vermerkt.

**Umfang:** Erfasst sind die dokumentierten, für Daten-APIs und Betrieb relevanten Funktionsfamilien der drei Referenzprojekte, einschließlich einzelner Middleware-Bausteine und wichtiger Einsatzgrenzen. Eine mathematisch vollständige Aufzählung sämtlicher interner Klassen, Versionseigenheiten, Plugins oder zukünftiger Editionen ist ohne versionsgenaue Quellcodeprüfung und Praxistests nicht belegbar.

## 2. Vergleich auf einen Blick

| Merkmal | PHP-CRUD-API | DreamFactory | DB2Rest |
|---|---|---|---|
| Leitidee | Kleines PHP-Skript für SQL-REST | Umfangreiche API- und Integrationsplattform | Java-/Spring-basierte Data-API für SQL |
| Sprache / Unterbau | PHP, PDO, PSR-Middleware | PHP / Laravel und Connector-Pakete | Java / Spring Boot, SQL-Dialekte |
| MariaDB/MySQL | Ja | Ja | Ja |
| Weitere SQL-Backends | PostgreSQL, SQL Server, SQLite | Viele Connectoren; Edition prüfen | PostgreSQL, SQLite, SQL Server, Oracle, IBM Db2 u.a. |
| NoSQL / Fremddienste | Nicht Kernziel | NoSQL, Dateien, E-Mail, SOAP u.a. | SQL im tatsächlich gelisteten Datenbanksupport; weitergehende Ansprüche separat prüfen |
| REST-CRUD ohne eigenen Controllercode | Ja | Ja | Ja |
| Komplexere Abfragen / Beziehungen | Filter, relationale verschachtelte Leseabfragen | Filter, Beziehungen, Procedures | RSQL, Joins, Named SQL, Procedures |
| Authentifizierung | Mehrere optionale Middleware-Varianten | API-Keys, Benutzer und SSO je nach Edition | Basic und API-Key konfigurierbar |
| Erweiterung | Eigene Controller/Middleware | Skripte, Ereignisse, eigene Services | SQL-Vorlagen und Spring-basierte Integration |
| Hosting-Fit für SASD Shared Hosting | Hoch, nach Konfigurationstest | Gering für vollständige Plattform | Gering, benötigt Java-Prozess |
| Lizenz des Kernprojekts | MIT | Apache-2.0 (Core, nicht alle Pakete) | Apache-2.0 |

Die Spalte *Hosting-Fit* ist eine **SASD-Architektureinschätzung**, keine vom Hersteller zugesicherte Kompatibilität.

## 3. PHP-CRUD-API – detaillierter Funktionskatalog

**Produkt:** https://github.com/mevdschee/php-crud-api  
**Primärdokumentation:** https://github.com/mevdschee/php-crud-api/blob/main/README.md  
**Lizenz:** MIT (Repository-Lizenz prüfen, bevor Code übernommen wird).

PHP-CRUD-API lässt sich als einzelnes PHP-Skript bereitstellen. Es baut die HTTP-Ressourcen aus bestehenden SQL-Tabellen auf. Für unseren zunächst kleinen, auf PHP und MariaDB ausgerichteten Server ist das der direkteste Architekturvergleich.

### 3.1 Bereitstellung, Konfiguration und Datenquellen

**Primärquellen:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md)

#### Ein-Datei-Deployment

Eine gebündelte api.php kann in eine bestehende PHP-Webumgebung hochgeladen werden. Dadurch entfallen ein eigener Hintergrundprozess und ein großer Installationsstack; der Webserver muss die Aufrufpfade allerdings korrekt an PHP weiterreichen.

#### Composer-Einbindung

Neben der Einzeldatei ist die Einbindung über Composer vorgesehen. Das erlaubt die Integration in ein größeres PHP-Projekt, ohne die Bibliothek mit Anwendungscode zu vermischen.

#### Datenbanktreiber und PDO

Die Konfiguration wählt MySQL/MariaDB, PostgreSQL, SQL Server oder SQLite. Der entsprechende PDO-Treiber, gültige Zugangsdaten und passende Datenbankversionen bleiben technische Voraussetzungen.

#### Verbindungsparameter

Host oder Dateiname, Port, Datenbank, Benutzer, Passwort und ein optionales Initialisierungs-SQL können gesetzt werden. So kann dieselbe Software mit anderen Verbindungsdaten für verschiedene Installationen eingesetzt werden.

#### Umgebungsvariablen

Zentrale Einstellungen können durch PHP_CRUD_API_-Umgebungsvariablen überschrieben werden. So lassen sich Betriebsgeheimnisse und installationsspezifische Werte aus dem ausgelieferten Quellcode heraushalten.

#### Tabellenfreigabe

Über die Tabellenkonfiguration kann eingeschränkt werden, welche Tabellen veröffentlicht werden. Vorsicht: Die dokumentierte Voreinstellung ist breit; für SASD sollte eine explizite Allowlist verpflichtend sein.

#### Tabellen- und Spalten-Mapping

Physische Datenbanknamen lassen sich auf externe API-Namen abbilden. Das ist für Altanwendungen hilfreich, deren Tabellenbezeichnungen nicht in der öffentlichen REST-Schnittstelle erscheinen sollen.

#### Konfigurierbare Controller

Standardmäßig sind Controller für Datensätze, GeoJSON, OpenAPI und Status vorgesehen; eigene Controller sind zusätzlich registrierbar. Damit bleibt der Router erweiterbar, ohne den gesamten Server zu ersetzen.

#### Caching der Metadaten

Der Server bietet konfigurierbare Caches wie TempFile und optional Redis/Memcache/Memcached. Sie reduzieren vor allem wiederholte Metadatenarbeit; auf einfachem Shared Hosting ist die Dateicache-Variante am ehesten realistisch.

#### Basis-Pfad und JSON-Optionen

Ein URL-Basispfad sowie Parameter der JSON-Ausgabe können konfiguriert werden. Das vereinfacht Installationen unter unterschiedlichen Subdomains oder Verzeichnissen, ersetzt jedoch keine konsistente API-Versionierung.

### 3.2 CRUD und Darstellung von Datensätzen

**Primärquellen:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md)

#### Datensätze auflisten

GET /records/{table} liefert mehrere Datensätze als JSON-Liste. Die Menge kann durch Filter, Sortierung und Seitenbegrenzung gesteuert werden.

#### Einzelnen Datensatz lesen

GET /records/{table}/{id} adressiert einen Datensatz über seinen Primärschlüssel. Der Datenbank-Primärschlüssel wird dadurch unmittelbar Teil der API-Semantik.

#### Datensatz anlegen

POST /records/{table} übernimmt einen JSON-Datensatz oder kompatible Formulareingaben und legt eine Zeile an. Datenbank-Constraints und gegebenenfalls Validierungs-Middleware bestimmen, was akzeptiert wird.

#### Mehrere Datensätze anlegen

JSON-Arrays können als Eingabe für Batch-Inserts dienen. Das vermindert HTTP-Aufrufe, ist aber nicht gleichzusetzen mit garantierten komplexen, transaktionalen Geschäftsabläufen.

#### Datensatz ändern

PUT /records/{table}/{id} aktualisiert Felder einer vorhandenen Zeile. Für SASD müsste der genaue Umgang mit nicht mitgesendeten Feldern als API-Vertrag festgelegt und getestet werden.

#### Datensatz löschen

DELETE /records/{table}/{id} entfernt einen adressierten Datensatz, sofern Berechtigungen und Datenbankregeln dies erlauben. Löschschutz, Soft-Delete und Auditierung sind eigenständige fachliche Entscheidungen.

#### Atomaren Zähler ändern

PATCH /records/{table}/{id} kann numerische Felder direkt in der Datenbank inkrementieren. Das vermeidet den fehleranfälligen Ablauf aus separat Lesen, Berechnen und Schreiben bei konkurrierenden Zugriffen.

#### JSON-Objekte, JSON-Arrays und Formulare

Eingaben können aus einzelnen JSON-Objekten, JSON-Arrays und URL-form-codierten POST-Daten bestehen. Ein einheitlicher API-Vertrag sollte für SASD dennoch JSON als bevorzugte Eingabe festlegen.

#### Binärdaten in Base64

Binäre Datenbankfelder können Base64-kodiert transportiert werden. Das ist für kleine BLOB-Werte praktisch, ersetzt jedoch bei großen Dateien keinen separaten Datei-Upload- oder Download-Dienst.

#### JSON-Datenbankspalten

Eine optionale JSON-Middleware kann gespeicherte JSON-Zeichenketten als native JSON-Objekte oder -Arrays in der API behandeln. Dies ist eine Darstellungshilfe und keine generische Dokumentendatenbank.

#### XML-Ausgabe

Eine optionale XML-Middleware kann JSON-Eingaben und -Ausgaben in XML übersetzen. Für eine kleine SASD-V1 wäre dieses zusätzliche Format vermutlich kein Kernbedarf.

#### Status-Endpunkt

Ein integrierter Status-Controller dient der technischen Erreichbarkeitsprüfung. Ob eine konkrete Prüfung auch den Datenbankzustand ausreichend abdeckt, muss im Betrieb gesondert bewertet werden.

### 3.3 Suche, Beziehungen und räumliche Daten

**Primärquellen:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md#filters) · [P2](https://github.com/mevdschee/php-crud-api/blob/main/README.md#joins)

#### Vergleichsfilter

Ein Filterparameter unterstützt etwa Gleichheit, Ungleichheit, größer/kleiner, Bereich, Enthaltensein und Textanfang oder -ende. Die Filter werden in serverseitige Datenbankabfragen übersetzt, statt alle Datensätze an den Client auszuliefern.

#### Zusammengesetzte Bedingungen

Mehrfachfilter können AND-/OR-Verknüpfungen mit nummerierten und teils zusätzlich buchstabierten Parametern ausdrücken. Das ist wesentlich flexibler als ein einzelner Suchbegriff, muss aber gegen sehr teure Abfragen begrenzt werden.

#### Filter auf verknüpfte Tabellen

Abfragen können Bedingungen auf verwandten Datensätzen prüfen, auch über mehrere Beziehungsschritte. Das Produkt dokumentiert, dass solche Filter die Autorisierungs- und Mandantengrenzen der verwandten Tabelle respektieren sollen.

#### Spaltenauswahl und Ausschluss

include- und exclude-Parameter verringern die zurückgelieferten Felder. Das spart Bandbreite, ersetzt aber keine serverseitige Spalten-Autorisierung für vertrauliche Werte.

#### Mehrstufige Sortierung

Mehrere Sortierfelder und aufsteigende oder absteigende Reihenfolgen sind möglich. Für verknüpfte Spalten gelten dokumentierte Grenzen.

#### Top-N-Abfragen

Mit einer maximalen Ergebnisgröße und Sortierung können beispielsweise die letzten zehn Einträge abgefragt werden. Das ist besonders für Übersichten und Dashboards hilfreich.

#### Seitennavigation

Ein page-Parameter unterstützt Seiten und Seitengrößen; bei Pagination wird die Gesamtzahl passender Ergebnisse angegeben. Unsortierte Ergebnisse werden über den Primärschlüssel geordnet, damit Seiten überhaupt definiert sind.

#### Automatische Beziehungserkennung

Aus Fremdschlüsseln werden gehört-zu-, eins-zu-viele- und viele-zu-viele-Beziehungen abgeleitet. Auf Wunsch liefert ein Leseaufruf verschachtelte Objekte statt ausschließlich flacher Zeilen.

#### Joins über mehrere Ebenen

Mehrere Beziehungspfade lassen sich in einem REST-Aufruf anfordern. Solche verschachtelten Ergebnisse sind bequem, können aber Abfragekosten und Antwortgröße erheblich steigern.

#### Volltextähnliche einfache Textsuche

Die optionale textSearch-Middleware sucht einen Begriff über alle Textspalten einer Ressource. Das ist eine praktische Basissuche, aber kein Ersatz für suchmaschinenartige Relevanzwertung oder einen Suchindex.

#### GIS-Geometriefelder

Unterstützt werden räumliche Geometriedatentypen und räumliche Filter gemäß den dokumentierten Datenbankvoraussetzungen. Eine SRID-Einstellung steuert die Interpretation von Koordinaten.

#### GeoJSON-Lese-API

Der optionale GeoJSON-Controller liefert Datensätze als Features beziehungsweise FeatureCollections. Er unterstützt unter anderem Bounding-Box- und Kachelparameter für Kartenansichten; dokumentiert ist hier eine read-only-Sicht.

### 3.4 Sicherheit, Benutzer und Mandanten – jede Middleware getrennt

**Primärquellen:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md#middleware) · [P3](https://github.com/mevdschee/php-crud-api/blob/main/README.md#authentication)

#### firewall

Beschränkt Zugriffe auf definierte IP-Adressen und kann Reverse-Proxy-Betrieb berücksichtigen. Eine IP-Allowlist ist eine zusätzliche Verteidigungslinie, aber keine individuelle Nutzeridentifikation.

#### sslRedirect

Leitet unverschlüsselte Anfragen auf HTTPS um. Bei Reverse Proxies muss die korrekte Erkennung des ursprünglichen Protokolls geprüft werden.

#### cors

Setzt browserrelevante Cross-Origin-Regeln für Ursprünge, Header, Methoden und Credentials. CORS schützt nicht vor serverseitigen Clients und ersetzt keinerlei Authentifizierung.

#### xsrf

Schützt Cookie-basierte Nutzung über das Double-Submit-Cookie-Verfahren vor Cross-Site Request Forgery. Relevant ist das vor allem bei browserseitigen Sessions.

#### ajaxOnly

Kann Anfragen auf AJAX-artige Requests beschränken. Das ist allenfalls ein zusätzlicher Schutzbaustein, keine belastbare Authentifizierung.

#### apiKeyAuth

Prüft einen API-Schlüssel aus einem HTTP-Header gegen Konfigurationswerte. Geeignet für einfache technische Clients; Schlüssel müssen sicher gespeichert, rotiert und bei Verlust widerrufen werden können.

#### apiKeyDbAuth

Prüft API-Schlüssel anhand einer Datenbanktabelle statt einer statischen Konfiguration. Dies erleichtert individuelle Schlüssel je Nutzer oder Anwendung und deren serverseitige Verwaltung.

#### dbAuth

Bietet registrieren, anmelden, Passwort ändern, abmelden und aktuellen Nutzer abfragen über zusätzliche Routen. Nutzer liegen in einer Datenbanktabelle und die Anmeldung verwendet serverseitige Sessions.

#### basicAuth

Verwendet HTTP Basic Authentication mit passender Benutzerquelle. Ohne durchgängiges HTTPS wären übertragene Zugangsdaten gefährdet.

#### jwtAuth

Akzeptiert ein JWT im Authorization-Header und stellt dessen Claims für Autorisierungsentscheidungen bereit. Signatur, Gültigkeitsdauer, Aussteller und Schlüsselmanagement müssen korrekt konfiguriert werden.

#### wpAuth

Bindet WordPress-Authentifizierung an. Für SASD ist das nur als Integrationsbeispiel relevant, nicht als Kernfunktion.

#### authorization

Kann Zugriffe getrennt nach Pfaden, Tabellen, Spalten, Operationen und einzelnen Datensätzen einschränken. Besonders wichtig ist, dass der README zufolge ohne eigene Regeln zunächst viele Ressourcen öffentlich erreichbar sind.

#### validation

Validiert Eingaben mit Typregeln und eigenen Callbacks. Fehlerhafte Datentypen oder fachlich unzulässige Werte können so vor dem Datenbankschreiben abgewiesen werden.

#### sanitation

Bereinigt oder normalisiert Eingabewerte über anpassbare Handler. Sanitizing darf nicht fälschlich als Ersatz für Parametrisierung, Validierung oder sichere Ausgabe betrachtet werden.

#### ipAddress

Kann ein dafür vorgesehenes Feld beim Erstellen aus der festgestellten Client-IP befüllen und gegen Client-Überschreiben schützen. Hinter Proxies ist die Herkunftsprüfung der Header sicherheitsrelevant.

#### multiTenancy

Schränkt Tabellenzeilen per Mandantenbezug ein, sodass mehrere Kunden eine Datenbank teilen können. Der Mandantenbezug darf nicht blind aus manipulierbaren Clientparametern übernommen werden.

#### reconnect

Erlaubt anhand der Authentifizierungsinformationen einen Wechsel von Datenbank, Benutzer oder Passwort, beispielsweise für Datenbank-pro-Mandant. Diese Trennung ist anspruchsvoller als eine einzige gemeinsam genutzte Datenbank.

#### pageLimits

Begrenzt Seitenzahlen und zurücklieferbare Datensätze. Dies reduziert versehentlich oder absichtlich ausufernde Datenabfragen und unterstützt vorhersehbaren Ressourcenverbrauch.

#### joinLimits

Begrenzt Join-Tiefe, Tabellenzahl und die Menge verknüpfter Datensätze. Damit werden unerwartet teure verschachtelte Abfragen eingehegt.

#### Authentifizierung ist optional – kein automatisches Sicherheitsversprechen

Die verschiedenen Authentifizierungsverfahren müssen bewusst aktiviert, korrekt angeordnet und mit Autorisierung kombiniert werden. Gerade der Zugriff auf eine Benutzertabelle muss zwingend gesperrt sein, sofern er nicht ausdrücklich erlaubt werden soll.

### 3.5 Erweiterung, Schema, Standards und Grenzen

**Primärquellen:** [P1](https://github.com/mevdschee/php-crud-api/blob/main/README.md)

#### customization-Middleware

Vorher- und Nachher-Handler können Request- oder Response-Verarbeitung ergänzen. Hier lassen sich etwa Normalisierung, kleine Prüfungen oder zusätzliche Ausgabefelder integrieren.

#### Eigene Controller

Zusätzliche REST-Routen können über registrierte Controller implementiert werden, die Router und Datenbankzugriff nutzen. Das ist die natürliche Erweiterung für Funktionen, die nicht mehr reine Tabellen-CRUD-Vorgänge sind.

#### Erweiterbare OpenAPI-Builder

Das automatisch erzeugte OpenAPI-Dokument kann durch eigene Builder ergänzt werden. So erhalten auch benutzerdefinierte Endpunkte eine maschinenlesbare Beschreibung.

#### Rechteabhängige OpenAPI-Ausgabe

Die Dokumentation kann die für die laufende Sitzung erlaubten Tabellen und Operationen berücksichtigen. Das vermindert die Anzeige unerlaubter Ressourcen, ersetzt aber die serverseitige Durchsetzung nicht.

#### Datenbankschema lesen

Ein Schema-Controller kann Tabellen- und Spalteninformationen als JSON bereitstellen. Dies ist beispielsweise für Verwaltungswerkzeuge oder die UI-Generierung interessant.

#### Datenbankschema verändern

Ein optionaler columns-Controller kann auch Datenbankstrukturen ändern. Er ist bewusst standardmäßig deaktiviert; in einer kleinen öffentlichen SASD-API sollte diese Fähigkeit gar nicht freigeschaltet werden.

#### PSR-Standards

Das Projekt nennt Unterstützung für PSR-4, PSR-7, PSR-12, PSR-15 und PSR-17. Solche Schnittstellen unterstützen saubere Middleware-Integration, Coding-Standards und austauschbare HTTP-Komponenten.

#### Dokumentierte Schlüsselgrenzen

Zusammengesetzte Primär- und Fremdschlüssel sind nicht unterstützt; erwartet werden geeignete autoinkrementierende oder UUID-basierte Schlüssel. Das ist ein wichtiger Hinweis für die automatische API-Generierung aus vorhandenen Datenbanken.

#### Keine komplexen Schreibtransaktionen

Das Projekt nennt komplexe Transaktionen ausdrücklich als Grenze. Mehrschrittige Geschäftsvorgänge sollten deshalb in einem separat entworfenem Service und einer kontrollierten Transaktion stattfinden.

#### Keine beliebige SQL-Ausdruckssprache

Frei zusammensetzbare komplexe SQL-Funktionen oder Aggregationsausdrücke sind nicht die Stärke des generischen CRUD-Endpunkts. Bei Bedarf sind Views, eigene Controller oder spezialisierte Abfrageendpunkte sinnvoll.

## 4. DreamFactory – detaillierter Funktionskatalog

**Produkt:** https://github.com/dreamfactorysoftware/dreamfactory  
**Primärdokumentation:** https://docs.dreamfactory.com/ und https://github.com/dreamfactorysoftware/dreamfactory#key-features  
**Lizenz:** Apache-2.0 für das offene Kernrepository. **Nicht alle angepriesenen Funktionen sind ohne kommerzielle Lizenz nutzbar.** Die offizielle README nennt unter anderem zusätzliche Datenbank-Connectoren, LDAP/SAML, Audit-Logging, Rate Limits und Multi-Tenancy als mögliche Bestandteile kommerzieller Editionen. Die genaue Editions-/Versionsmatrix ist vor jeder Übernahme getrennt zu prüfen.

DreamFactory ist keine bloße CRUD-Bibliothek: Es kombiniert Datenzugriff, Integrationen, Sicherheitskonfiguration, Administration und zusätzliche Laufzeitdienste.
### 4.1 Automatisch generierte Datenbank-APIs

**Primärquellen:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#key-features) · [D2](https://www.dreamfactory.com/features/)

#### Datenbank anbinden und Struktur erkennen

Eine Datenquelle wird konfiguriert, anschließend werden Tabellen, Views, Schlüssel und Beziehungen introspektiert. Daraus entstehen REST-Ressourcen ohne eigens geschriebene CRUD-Controller.

#### CRUD-Routen

Lesen, Anlegen, Ändern und Löschen werden als HTTP-Endpunkte angeboten. Die Zugriffskontrolle wird unabhängig von der Datenbankstruktur über Plattformregeln konfiguriert.

#### SQL- und NoSQL-Connectoren

Die Plattform nennt MySQL/MariaDB, PostgreSQL, SQLite, SQL Server, Oracle und weitere SQL-Systeme sowie MongoDB, Cassandra, Couchbase und CouchDB. Ob ein konkreter Connector im Open-Source-Kern enthalten ist, muss anhand des Connector-Pakets überprüft werden.

#### Mehrere Datenquellen hinter einem Server

Eine DreamFactory-Installation kann mehrere Services beziehungsweise Datenbanken registrieren. Clients erhalten konsistente REST-Zugänge, obwohl die zugrunde liegenden Systeme unterschiedlich sind.

#### Filter

Serverseitige Query-Parameter schränken Ergebnisse ein. Damit entfallen für viele Standardabfragen eigens geschriebene SQL-Endpunkte.

#### Sortierung und Pagination

Endpunkte unterstützen Sortierreihenfolgen und begrenzte Ergebnismengen. Das ist essenziell, um große Tabellen nicht vollständig über HTTP übertragen zu müssen.

#### Beziehungen und verschachtelte Antworten

Zugehörige Parent-/Child-Informationen können in einem Aufruf zusammengefasst werden. Damit wird vermieden, für jeden verknüpften Datensatz einen zusätzlichen Request auszuführen.

#### Bulk Insert/Update/Delete

Mehrere Datensätze können in Sammeloperationen verändert werden. Das erhöht den Durchsatz, verlangt aber sorgfältige Prüfung von Fehlerfällen und Transaktionssemantik.

#### Views

Bestehende Datenbankansichten können als API-Ressourcen angeboten werden. Views sind für SASD interessant, weil sie öffentliche Datenmodelle von physischen Tabellen entkoppeln können.

#### Stored Procedures und Functions

Vorhandene Datenbanklogik wird als aufrufbarer API-Dienst bereitgestellt. So kann ein Legacy-System verwendet werden, ohne die Geschäftsregeln aus seinem Datenbankkern neu zu implementieren.

#### Virtuelle Fremdschlüssel

Die Produktdokumentation nennt zusätzlich definierbare Beziehungen, wenn die Datenbank selbst keine passenden Foreign-Key-Constraints besitzt. Das vereinfacht Legacy-Integrationen, erhöht aber die Verantwortung für konsistente Modellierung.

#### OpenAPI/Swagger automatisch

Für erzeugte APIs werden maschinenlesbare Beschreibungen bereitgestellt. Diese können Entwicklerdokumentation, interaktive API-Explorer oder Clientcode-Generierung unterstützen.

#### Datenbankschema verwalten

Über Admin-Funktionen beziehungsweise Management-APIs können Datenbankstrukturen teils angelegt oder geändert werden. Für öffentlich erreichbare Produktions-APIs ist das eine hochprivilegierte und gesondert zu schützende Fähigkeit.

### 4.2 Sicherheit und API-Governance

**Primärquellen:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#security-and-access-control) · [D3](https://github.com/dreamfactorysoftware/dreamfactory#commercial-licenses)

#### API-Schlüssel pro Anwendung

Aufrufende Anwendungen können über individuell vergebene Schlüssel identifiziert und einem Berechtigungskontext zugeordnet werden. Schlüssel sind verwaltbare technische Identitäten und dürfen nicht ungeschützt in öffentlich ausgelieferte Clients eingebettet werden.

#### Benutzer- und Sitzungsverwaltung

Die Plattform verwaltet Benutzer und verwendet unter anderem Sessions für authentifizierte Aufrufe. Das ist deutlich mehr als die reine Anbindung eines SQL-Kontos.

#### Rollenbasierte Zugriffskontrolle (RBAC)

Rollen können erlaubte HTTP-Methoden, Routen und Datenbankressourcen eingrenzen. Eine Anwendung kann beispielsweise nur lesen, während ein Verwaltungsdienst zusätzlich schreiben darf.

#### Spalten- und Feldberechtigungen

Sensible Spalten können von der Ausgabe ausgeschlossen oder entsprechend Rollen maskiert werden. Diese serverseitige Kontrolle ist für Passworthashes, personenbezogene Daten und interne Kennzeichen unverzichtbar.

#### SSO / externe Identität

Die Produktübersicht nennt SAML 2.0, OAuth 2.0, OpenID Connect, Azure AD und LDAP/Active Directory. Laut Lizenzhinweisen sind Teile dieser Enterprise-Identitätsintegration kommerziell; Verfügbarkeit nicht aus einer Marketingliste ableiten.

#### Rate Limiting

Anfragehäufigkeiten können begrenzt werden, um Missbrauch und Lastspitzen einzuhegen. Die offizielle README nennt Rate Limiting ausdrücklich auch im Zusammenhang kommerzieller Pakete.

#### Audit-Protokollierung

Zugriffe können mit Benutzer-, Zeit- und API-Kontext dokumentiert werden. Umfang und Lizenzverfügbarkeit hängen von der Edition ab; insbesondere sensible Request-Bodies dürfen nicht unreflektiert protokolliert werden.

#### Identity Passthrough

Die aktuelle Herstellerdarstellung betont die Weitergabe der Benutzeridentität an Datenquellen, statt alle Zugriffe über einen hochprivilegierten gemeinsamen Service-Account auszuführen. Das kann bestehende datenbankseitige Rechte erhalten, ist aber stark connector- und editionsabhängig.

#### Mandantentrennung

Die Produktübersicht nennt mehrere isolierte API-Umgebungen innerhalb einer Plattform. Es ist zwischen logisch getrennten Services, Benutzerrollen und einer belastbaren Datenisolierung je Mandant zu unterscheiden; die kommerzielle Editionsgrenze ist zu prüfen.

#### Datenmaskierung

Felder können je Zielgruppe in ihrer Sichtbarkeit oder Darstellung eingeschränkt werden. Für SASD wäre das langfristig interessant, sollte aber zuerst über explizite API-DTOs oder erlaubte Spalten gelöst werden.

### 4.3 Erweiterbarkeit und Integrationsdienste

**Primärquellen:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#extensibility) · [D4](https://github.com/dreamfactorysoftware/dreamfactory#supported-data-sources)

#### Pre-Process-Skripte

Ein Skript wird vor der eigentlichen API-Aktion aufgerufen und kann Eingaben prüfen, normalisieren oder zurückweisen. Damit lassen sich wiederkehrende Regeln ohne eigenes Forken des Kernservers ergänzen.

#### Post-Process-Skripte

Nach einer API-Aktion kann eine Antwort angereichert oder transformiert werden. Hier ist auf eine feste Reihenfolge von Validierung, Berechtigung, Transaktion und Fehlerbehandlung zu achten.

#### Skripting in PHP, Python und Node.js

Die Plattform dokumentiert mehrere Skript-Laufzeiten. Das ist flexibel, vergrößert aber Abhängigkeiten, Angriffsfläche und den Aufwand für isolierte Ausführung.

#### Eigene Services

Neben automatisch erzeugten CRUD-Routen können vollständig eigene API-Dienste registriert werden. So lassen sich Fachfunktionen integrieren, für die einfache Tabellenoperationen nicht ausreichen.

#### Webhooks und Ereignisweitergabe

Datenänderungen können nach außen gemeldet oder externe Workflows angestoßen werden. Für zuverlässige Zustellung benötigt ein solches System Retry-, Idempotenz- und Fehlerkonzepte; deren konkrete Garantien sind versionsabhängig zu überprüfen.

#### Lokaler Dateispeicher

Dateien und Verzeichnisse können über eine REST-Schnittstelle verwaltet werden. Die binäre Objektablage ist hier ein separater Dienst neben der relationalen Datenbank.

#### Objektspeicher wie Amazon S3 und Azure Blob

Externe Buckets oder Container können als API-Dienste zugänglich gemacht werden. Dadurch wird die REST-Oberfläche zur Integration unterschiedlicher Speicheranbieter.

#### FTP/SFTP-Dateizugriff

Auch vorhandene entfernte Dateisysteme werden über einen API-Service ansprechbar. Dies hilft bei Altintegrationen, ist aber für einen kleinen neuen API Server nicht notwendig.

#### SMTP-E-Mail-Versand

Eine API kann den Versand von E-Mails über einen konfigurierten Maildienst auslösen. Das ist eher ein separater Integrationsservice als eine Kernfunktion einer Datenhaltungs-API.

#### Push-Benachrichtigungen

Mobile Push-Dienste werden als integrationsfähige Schnittstellen geführt. Sie verlangen providerabhängige Anmeldedaten und eigene Zustellungs- und Fehlermodelle.

#### SOAP-zu-REST

Bestehende SOAP/WSDL-Dienste lassen sich hinter REST-Endpunkten kapseln. Der Mechanismus eignet sich für Modernisierung, gehört jedoch eher zur fortgeschrittenen Gateway-Rolle und kann lizenzabhängig sein.

### 4.4 Betrieb, Verwaltung und KI-Integration

**Primärquellen:** [D1](https://github.com/dreamfactorysoftware/dreamfactory#administration) · [D5](https://github.com/dreamfactorysoftware/dreamfactory#mcp-server-for-ai-and-llm-integration)

#### Webbasierte Administrationskonsole

Administratoren konfigurieren Datenquellen, Benutzer, Rollen und API-Schlüssel im Browser. Dadurch kann ein API-Gateway ohne direkte Bearbeitung jeder Konfigurationsdatei verwaltet werden.

#### Management-API

Verwaltungsvorgänge sind ebenfalls per REST automatisierbar. Das ist für Infrastructure-as-Code und reproduzierbare Installationen interessant, verlangt aber besonders restriktive Verwaltungsrechte.

#### Konfigurationspakete und Migration

Konfigurationen können zwischen Umgebungen transportiert werden. Dadurch müssen Test-, Staging- und Produktionsumgebung nicht manuell auseinanderlaufend gepflegt werden.

#### API-Nutzungsberichte

Die Plattform beschreibt Dashboards für Aufrufzahlen, Fehler und Performance. Bei kommerziellen Telemetrie- und Auditfunktionen muss auf die konkrete Lizenz geachtet werden.

#### Selbsthosting

DreamFactory dokumentiert Installationen auf Linux/Windows sowie Docker und Kubernetes. Das ist für eigene Server geeignet, aber für einfaches PHP-Shared-Hosting aufgrund der zusätzlichen Dienste und Abhängigkeiten keine naheliegende Wahl.

#### MCP-Integration für KI-Clients

Die aktuelle Dokumentation beschreibt die Bereitstellung strukturierter Datenbankaktionen als MCP-Tools für KI-Anwendungen. Ziel ist, dass Clients berechtigte API-Operationen ausführen, anstatt selbst unkontrolliert SQL zu generieren.

#### Kontrollierter Datenzugriff für KI

Auch KI-Clients sollen dieselben Berechtigungs- und Governance-Regeln wie normale Anwendungen durchlaufen. Die praktische Sicherheit hängt insbesondere an den tatsächlich freigegebenen Tools und dem jeweiligen Identitätskontext ab.

#### Air-Gap/On-Premise-Betrieb

Die Herstellerseite bewirbt den Betrieb in eigener Infrastruktur ohne externen Datenfluss. Das ist ein Betriebsmodell, keine automatische Garantie: externe Integrationen, Telemetrie und Lizenzanforderungen müssen jeweils geprüft werden.

## 5. DB2Rest – detaillierter Funktionskatalog

**Produkt:** https://github.com/9tigerio/db2rest  
**Dokumentation:** https://db2rest.com/docs/  
**Lizenz:** Apache-2.0 (Repository).

DB2Rest setzt auf Java/Spring Boot und erzeugt Datenbank-REST-Schnittstellen ohne ORM-Modelle oder pro Tabelle generierte Anwendungsklassen. Besonders aufschlussreich sind die Abfragesprache, der Zugriff auf mehrere Datenbanken und die Trennung zwischen dynamischen Standardabfragen und benannten SQL-Abfragen.

### 5.1 Verbindungen, Metadaten und Deployment

**Primärquellen:** [B1](https://github.com/9tigerio/db2rest) · [B2](https://db2rest.com/docs/data-api-features) · [B3](https://db2rest.com/docs/multidb/connect-multiple-db)

#### Automatische SQL-REST-Ressourcen

Auf Basis einer Datenbankverbindung werden Tabellen und Views unmittelbar als REST-Ressourcen ansprechbar. Eigene Java-Entity-Klassen oder ein ORM-Modell sind für Standardoperationen nicht erforderlich.

#### Mehrere relationale Datenbanken

Das Projekt listet PostgreSQL, MySQL/MariaDB, SQLite, Microsoft SQL Server, Oracle, IBM Db2 und weitere kompatible oder verwaltete SQL-Angebote. Cloud-Hoster-Einträge sind zum Teil Bereitstellungsvarianten vorhandener Datenbanktypen, keine eigenen Dialekte.

#### Eine Instanz – mehrere Datenbanken

Mehrere Datenbankverbindungen können innerhalb eines DB2Rest-Prozesses verwaltet werden; Pfade verwenden Datenbankkennungen. Das ist ein anderes Betriebsmodell als mehrere vollständig getrennte SASD-Installationen.

#### SQL-Dialekt-Abstraktion

Die Implementierung besitzt Dialektmodule, um Datenbankunterschiede unter einer möglichst konsistenten REST-API abzufangen. Gerade dieses Adapterprinzip ist für eine spätere Portierung auf andere Datenbanken lehrreich.

#### Schemaübersicht als REST-Endpunkt

Eine spezielle Schema-Ressource kann Tabellen, Views und optional Spaltendetails mit Typen und Schlüsselinformationen liefern. Das dient Diagnosewerkzeugen, Administratoren und Metadaten-gesteuerten Anwendungen.

#### Schemas einschränken

Mit INCLUDED_SCHEMAS wird festgelegt, welche Datenbankschemata überhaupt geladen und als API sichtbar werden. Das reduziert Metadaten-Last und die exponierte Datenoberfläche.

#### Views und materialisierte Views

Die Dokumentation beschreibt Lesezugriff auf normale und materialisierte Views, soweit die jeweilige Datenbank das unterstützt. Views können stabile öffentliche Abfragemodelle bereitstellen.

#### Java-Prozess/JAR und Docker

DB2Rest wird als Java-Anwendung beziehungsweise Container betrieben. Im Gegensatz zu PHP-CRUD-API ist ein geeigneter Applikationsprozess nötig; Shared Hosting mit ausschließlich PHP genügt nicht.

#### SQL-Pooling und Transaktionsinfrastruktur

Die Projektdokumentation nennt Connection Pooling und Transaktionsmanagement als eingebaute Betriebsgrundlagen. Die jeweiligen Isolationslevel, Poolgrößen und Fehlergarantien müssen bei realem Einsatz getestet werden.

### 5.2 Daten schreiben und verändern

**Primärquellen:** [B2](https://db2rest.com/docs/data-api-features) · [B4](https://db2rest.com/docs/category/insert) · [B5](https://db2rest.com/docs/category/update) · [B6](https://db2rest.com/docs/category/delete)

#### Einzelnen Datensatz speichern

Eine HTTP-Anfrage mit den gewünschten Feldern erzeugt einen neuen Datensatz. Datentypen und Datenbank-Constraints bestimmen, welche Werte zulässig sind.

#### Mehrere Datensätze einfügen

Bulk-Insert unterstützt die Übergabe mehrerer Datensätze in einem Aufruf. Das verringert Netzwerkkosten; atomare Ausführung und Verhalten bei Teilfehlern müssen anhand der konkreten Version geprüft werden.

#### Bulk-Dateiimport

Die Featureübersicht erwähnt das Einspielen von Datensätzen über Dateien. Format, Größenlimits und die tatsächlich unterstützten Formate sollten vor einer produktiven Anforderung zusätzlich im Detail verifiziert werden.

#### Update/Patch

Bestehende Datensätze können über Schreibendpunkte verändert werden. Die API braucht dafür eine eindeutig definierte Auswahl der betroffenen Zeilen.

#### Update mit Zeilenfilter

Die Dokumentation nennt auch Änderungen für alle Datensätze, die einen Filter erfüllen. Das ist mächtig, aber ein hohes Risiko für unbeabsichtigte Massenänderungen; SASD sollte solche Funktionen nicht früh freigeben.

#### Delete mit Zeilenfilter

Löschoperationen können über Bedingungen bestimmte Datensätze auswählen. Für sichere Nutzung sind Authentifizierung, Autorisierung und Mengenbegrenzung entscheidend.

#### Safe Delete

DB2Rest nennt einen besonderen sicheren Löschmodus. Ohne versionsgenaue Prüfung seines exakten Ablaufs sollte daraus weder ein Soft-Delete noch ein automatisches Backup oder eine Undo-Funktion abgeleitet werden.

#### Schreibtransaktionen

Die Featureübersicht nennt Transaktionsunterstützung für Save, Edit und Purge. Sie belegt allein noch keine frei kombinierbaren, mehrstufigen Transaktionen über mehrere HTTP-Requests oder mehrere Datenbanken.

#### Automatische Schlüsselvergabe

Die Insert-Dokumentation behandelt Strategien für automatisch generierte IDs einschließlich datenbankspezifischer Varianten. Eine mögliche SASD-Schnittstelle sollte Primärschlüsselgenerierung als Adapterverantwortung definieren.

### 5.3 Abfragesprache und analytische Funktionen

**Primärquellen:** [B2](https://db2rest.com/docs/data-api-features) · [B7](https://db2rest.com/docs/category/query) · [B8](https://db2rest.com/docs/read/supported-rsql-operators)

#### Datensätze lesen

Tabellen- und View-Inhalte lassen sich über GET-Endpunkte abrufen. Das ist das Grundelement der ohne Programmierung bereitgestellten API.

#### Einzelzeilenabfrage

Ein Filter kann einen eindeutigen Datensatz auswählen, dessen Antwort als Einzelobjekt zurückgegeben wird. Der genaue Rückgabetyp unterscheidet sich damit von Listenabfragen.

#### RSQL-Filter

DB2Rest verwendet eine URI-taugliche Abfragesprache auf Basis von RSQL/FIQL. Bedingungen werden deklarativ übergeben und serverseitig in Datenbankabfragen umgesetzt.

#### Vergleichsoperatoren

Die Dokumentation nennt Gleichheit, Ungleichheit, größer/kleiner sowie größer-gleich und kleiner-gleich. Dies reicht bereits für viele fachliche Filter von Datum, Status oder numerischen Werten.

#### Mengenoperatoren

IN und NOT IN wählen Datensätze anhand einer Wertemenge aus oder schließen sie aus. Das reduziert eine große Zahl einzelner, ähnlicher API-Abfragen.

#### Textmuster und Null-Abfragen

LIKE, NOT LIKE, Start-/Endmuster sowie IS NULL und IS NOT NULL gehören zum dokumentierten Operatorumfang. Bei Wildcards und Textmustern müssen Indexnutzung und Abfragekosten berücksichtigt werden.

#### Logische UND-/ODER-Gruppen

RSQL erlaubt mehrere Bedingungen und Klammergruppen. Damit können Clients zusammengesetzte Filter bilden, ohne direkt SQL auszuführen.

#### Spaltenprojektion

Clients können festlegen, welche Spalten zurückgegeben werden sollen. Die serverseitige Berechtigungsprüfung muss verhindern, dass dadurch unerlaubte Felder zugänglich werden.

#### Spalten-Aliasse

Antwortfelder lassen sich umbenennen. Das ist vor allem bei Joins wichtig, weil mehrere Tabellen gleichnamige Spalten besitzen können.

#### Sortierung

Das Ergebnis kann auf- oder absteigend nach ausgewählten Spalten geordnet werden. Der Server muss Sortierspalten gegen zulässige Metadaten prüfen.

#### Limit und Offset

Pagination begrenzt die Zahl der Datensätze und ermöglicht Blättern durch große Ergebnismengen. Offset-Pagination ist einfach, kann bei häufigen Änderungen oder sehr tiefen Seiten aber Nachteile haben.

#### Datensätze zählen

Gesamtzahlen zu einer Tabelle oder gefilterten Ergebnismenge können serverseitig berechnet werden. So müssen Clients keine komplette Tabelle übertragen, um eine Trefferzahl anzuzeigen.

#### Gruppierung und Aggregationsansätze

Die Featureübersicht nennt GROUP BY. Welche Aggregationen pro Datenbank und Version tatsächlich zur Verfügung stehen, sollte anhand einzelner API-Beispiele getestet werden.

#### CSV- und Excel-Export

Der Funktionsüberblick führt beide Exportformate auf. Vor Aufnahme in eine SASD-Roadmap sind konkreter Endpunkt, Dateiformat, Speicherbedarf und Stream-Verhalten zu prüfen.

#### JSON- und JSONB-Operatoren

Die RSQL-Referenz enthält Operationen für JSON- und JSONB-Inhalte, beispielsweise Schlüsselprüfung und Enthaltensein. Nicht jeder JSON-Operator ist für jede Datenbank in gleicher Weise verfügbar.

#### Join verschiedener Tabellen

DB2Rest stellt Join-Abfragen als strukturierte API-Anfragen bereit und beschreibt INNER, LEFT, RIGHT, SELF und CROSS. FULL JOIN wird in der Join-Dokumentation ausdrücklich als nicht unterstützt markiert.

#### Joins über Datenbankgrenzen

Die Join-Dokumentation erwähnt Joins über mehrere konfigurierte Datenbanken. Technische Umsetzung, Einschränkungen und Konsistenz hängen von Dialekten und der jeweiligen DB2Rest-Version ab und müssen praktisch nachgewiesen werden.

### 5.4 Erweiterte SQL-Funktionen und Sicherheit

**Primärquellen:** [B9](https://db2rest.com/docs/namedsql/introduction-to-named-queries) · [B10](https://db2rest.com/docs/remoteprocedurecall/stored-procedure) · [B11](https://db2rest.com/docs/security/basic_auth) · [B12](https://db2rest.com/docs/security/api_key)

#### Named SQL

Vordefinierte SQL-Dateien beziehungsweise benannte Abfragen können als API-Endpunkte angeboten werden. Das ist ein kontrollierter Ausweg aus dem Standard-CRUD-Schema, um komplexe Leseabfragen ohne beliebige Client-SQL auszuführen.

#### Stored Procedures

Datenbankprozeduren können mit definierten Parametern per REST aufgerufen werden. Auf diese Weise bleibt komplexe Transaktions- oder Geschäftslogik im Datenbanksystem, ohne dass der Client direkten SQL-Zugriff benötigt.

#### Stored Functions

Die Dokumentation führt neben Prozeduren auch aufrufbare Datenbankfunktionen. Rückgabeformate und SQL-Dialektunterschiede sind je Datenbank zu prüfen.

#### HTTP Basic Auth

Ein konfigurierbares Verfahren schützt Endpunkte mit Benutzername und Passwort. Die Dokumentation nennt ENABLE_AUTH=true; standardmäßig ist Authentifizierung deaktiviert.

#### API-Key-Authentifizierung

Ein API-Schlüssel wird über den Header X-API-KEY geprüft. Auch dieses Verfahren erfordert eine explizite Aktivierung und die Auswahl des Auth-Providers.

#### Dateibasierte Auth-Konfiguration

Die Referenz beschreibt eine separate Authentifizierungsdatei beziehungsweise deren Speicherort. Dies trennt Benutzer- und Schlüsselkonfiguration von den eigentlichen Datenbank-Verbindungsparametern.

#### Berechtigungen

Die Projektbeschreibung erwähnt Autorisierung und Entitlements. Konkrete Zeilen-, Feld-, Tabellen- oder Rollenmodelle sollten aber nicht ohne Sichtung der einzelnen Konfigurationsbeispiele als umfassendes RBAC zugesichert werden.

#### Rate- und Zeilenbegrenzung

Die allgemeinen Vorteile nennen Rate Limiting und Row Limiting als Sicherheitsmaßnahmen. Detaillierte Semantik und Verfügbarkeit müssen versionsgenau geprüft werden.

#### Datenmaskierung, Verschlüsselung und Tokenisierung

Diese Begriffe werden als Sicherheitsvorteile genannt, aber die überblicksartige Beschreibung belegt noch kein universelles, ohne weitere Module einsatzfähiges Feldmaskierungsmodell. Für SASD sind sie zunächst Forschungsfelder.

#### Tenant-ID, Schema- und Datenbankmandanten

Die Funktionsübersicht nennt drei Mandantenstrategien: Zeilenkennzeichen, separates Schema und separate Datenbank. Welche davon vollständig implementiert und autorisierungsfest sind, muss gesondert anhand konkreter Konfiguration und Integrationstests geprüft werden.

### 5.5 Betrieb, Diagnose und Beobachtbarkeit

**Primärquellen:** [B13](https://db2rest.com/docs/actuator/heath-check) · [B14](https://db2rest.com/docs/observability/grafana) · [B15](https://db2rest.com/docs/category/monitoring)

#### Health Check

Der Endpunkt /actuator/health meldet den Zustand des laufenden Prozesses, beispielhaft mit status UP. Ein Produktionssystem sollte zusätzlich prüfen, ob wichtige Datenbankabhängigkeiten tatsächlich benutzbar sind.

#### Metrik-Endpunkte

Spring-Actuator-Metriken können für Diagnose, Lastbeobachtung und Monitoring genutzt werden. Das hilft beim Erkennen von Fehlern und Engpässen.

#### Prometheus-Export

Über PROMETHEUS_EXPORT_ENABLED kann /actuator/prometheus zur Abfrage durch Prometheus aktiviert werden. Darüber lassen sich Zeitreihen und Alarme aufbauen.

#### Grafana-Integration

Grafana kann die über Prometheus erfassten Messwerte in Dashboards visualisieren. Grafana selbst ist kein Bestandteil der eigentlichen CRUD-Laufzeitlogik.

#### Datadog-Integration

Das Produkt dokumentiert die Übermittlung von Metriken an Datadog. Das ist ein Betriebs- und Monitoringthema, nicht Teil der öffentlichen Anwendungs-REST-API.

#### Container- und VM-Betrieb

Das Repository nennt Containerimages und Bereitstellung auf VMs sowie Cloud-Plattformen. Für SASD bedeutet das: ein eigener Linux-Server ist möglich, die geringe Shared-Hosting-Komplexität einer PHP-Einzeldatei jedoch nicht erreichbar.

## 6. Quervergleich nach Funktionsgruppen

Legende: **J** = offiziell dokumentiert; **O** = optional oder editionsabhängig; **E** = nur eingeschränkt beziehungsweise mit Besonderheiten; **–** = in den gesichteten Quellen nicht als generische Standardfunktion nachgewiesen. Die Matrix ist keine Zertifizierung für jede Edition oder Datenbank.

| Funktionsfamilie | PHP-CRUD-API | DreamFactory | DB2Rest | Für SASD |
|---|:---:|:---:|:---:|---|
| CRUD über REST | J | J | J | V0.1 |
| JSON-Ein-/Ausgabe | J | J | J | V0.1 |
| Konfiguration pro Installation | J | J | J | V0.1 |
| MariaDB/MySQL | J | J | J | V0.1 |
| Tabellen explizit freigeben | J | J | J | V0.1 |
| Authentifizierung | O | J/O | O | V0.1 minimal |
| Rollen-/Datensatzrechte | J mit Regeln | J/O | E | nach V0.1 |
| Validierung vor Datenbankzugriff | J | über Logik/Skripte | E | V0.1 basic |
| Filter | J | J | J | V0.1 einfach |
| Sortierung | J | J | J | V0.1 einfach |
| Pagination | J | J | J | V0.1 einfach |
| Projektion von Feldern | J | J | J | später |
| Joins / Beziehungen | J | J | J | später |
| Batch-Operationen | J bei Insert | J | J | später |
| Mehrere Datenbanken gleichzeitig | E | J | J | später |
| Mandantenfähigkeit | J | O | E | viel später |
| SQL-Views | Datenbankabhängig | J | J | später |
| Stored Procedures | – | J/O | J | später |
| Named SQL | eigener Controller | über Dienste/Skripte | J | später |
| Benutzer-/Session-Verwaltung | O | J/O | E | später |
| API Keys | O | J | O | V0.1 Option |
| JWT / SSO | O JWT | O SSO | – | später |
| OpenAPI | J | J | separat prüfen | später |
| Admin-Oberfläche | ergänzende Projekte | J | – | später |
| Health / Monitoring | J Status | J/O | J | V0.1 Health |
| Datenbankschema lesen | J | J | J | intern, später |
| Datenbankschema ändern | O, gefährlich | J | – | nicht in V0.1 |
| GIS / GeoJSON | J | connectorabhängig | – | nur bei Bedarf |
| Dateispeicher / E-Mail | – | J/O | – | außerhalb des Kerns |
| MCP für KI | – | J/O | – | nicht in V0.1 |

## 7. Was wir aus jedem Produkt lernen können

### 7.1 PHP-CRUD-API – Schlichtheit und Middleware

**Lerneffekt:** Ein kleiner Server kann mit wenigen klar abgegrenzten Komponenten sehr viel leisten. Besonders wertvoll sind die Trennung von Controller und Middleware, der einheitliche CRUD-Router, das Tabellen-Mapping und die konfigurierbaren Backends. **Nicht übernehmen:** den gefährlichen Gedanken, bei fehlender Berechtigungsregel automatisch alle Tabellen zu veröffentlichen. SASD soll umgekehrt zunächst nichts freigeben.

### 7.2 DreamFactory – Konfigurationsmodell und API-Governance

**Lerneffekt:** Datenquellen, API-Ressourcen, Benutzer, Rechte und Erweiterungen können getrennt verwaltet werden. Das ist ein gutes langfristiges Zielbild. **Nicht übernehmen:** die gesamte Admin-Konsole, den Connector-Zoo, SSO, KI-Gateway, Datei- und Maildienste bereits für eine erste Version.

### 7.3 DB2Rest – SQL-Dialekte, Named SQL und Observability

**Lerneffekt:** Eine klar definierte Adaptergrenze ermöglicht unterschiedliche Datenbanken, ohne das öffentliche REST-Modell jeweils neu zu bauen. Benannte SQL-Abfragen bilden einen guten kontrollierten Erweiterungsweg; Health und Metriken sind wertvolle Betriebskonzepte. **Nicht übernehmen:** sofortige Unterstützung aller Dialekte, beliebige clientseitige Abfragen oder Massenänderungen ohne Schutz.

## 8. Vorschlag: klein beginnen, später an den Vorbildern orientieren

Dies ist **eine Orientierung**, noch keine freigegebene Roadmap oder Implementierungsentscheidung.

### V0.1.0 – bewusst klein

1. PHP 8.4 und MariaDB/MySQL als erste, ausdrücklich konfigurierte Betriebsart (Shared Hosting beachten).
2. HTTP-Routing über index.php im Repository-Root; kein Zwang zu /public oder Docker.
3. Saubere Schichten: HTTP/Router → Controller → Service → Repository → Storage-Adapter.
4. Konfiguration aus nicht öffentlich erreichbarer Datei beziehungsweise gesicherten Umgebungsvariablen; niemals Secrets im Repository oder Log.
5. Konfigurierbare, **explizit freigeschaltete** Ressourcen; keine automatische Vollveröffentlichung der Datenbank.
6. GET-Liste, GET-Einzelobjekt, POST, PUT/PATCH nach festgelegtem Vertrag und DELETE, jeweils mit passenden HTTP-Statuscodes.
7. Einfaches Filtern, Sortieren und begrenzte Pagination mit erlaubten Feldern; keine clientgesteuerte Roh-SQL-Ausführung.
8. Parametrisierte SQL-Abfragen, grundlegende Schema- und Wertevalidierung sowie konsistente JSON-Fehlerantworten.
9. HTTPS-Pflicht, minimale Authentifizierung beziehungsweise klar gesperrte Schreibrechte, Least-Privilege-Datenbankbenutzer.
10. Ein kleiner Health-Endpunkt, strukturierte Logs ohne Passwörter/Token und automatisierte Tests der zentralen CRUD- und Sicherheitsfälle.

### V0.2.x – bei echtem Bedarf

- API-Key-Verwaltung, feinere Rollen-/Ressourcenberechtigungen und Zugriff auf einzelne Spalten/Zeilen.
- Unterstützte, dokumentierte Filteroperatoren, Feldprojektion und zuverlässige Pagination.
- Konfigurierbare Tabellen-/Spalten-Aliasse und lesende Views.
- Versionskennzeichnung in API-Pfaden, Migration von Konfigurationsformaten und OpenAPI.

### Später und nur nach konkretem Anwendungsfall

- SQLite-/PostgreSQL-Adapter, mehrere gleichzeitig angebundene Datenbanken, Mandantenmodelle.
- Relationale Joins, Named Queries, streng begrenzte Batch-Operationen und Fachtransaktionen.
- Erweiterungs-/Hook-Konzept, Betriebsmetriken und ggf. Verwaltungsoberfläche.
- Import/Export, GIS, Dateispeicher, SSO, MCP oder Integrationsdienste – **keine automatische Verpflichtung**.

## 9. Architekturrisiken und offene Prüffragen

1. **Versehentliche Datenfreigabe:** Tabellen, Spalten und Operationen müssen in SASD per Allowlist freigegeben werden. Ein verbindlicher Repository-/Datenbankbenutzer mit minimalen Rechten ist zusätzliche Absicherung.
2. **Generische CRUD-API ist nicht identisch mit Fach-API:** Vorgänge wie Buchausleihe, Buchrückgabe oder medizinische Dokumentation benötigen oft atomare, fachlich validierte Abläufe. Sie gehören in eigene Services.
3. **Primär- und Fremdschlüssel:** Automatische REST-Generatoren unterscheiden sich im Umgang mit UUIDs, zusammengesetzten Keys, Views und fehlenden Foreign Keys. Das ist vor Adapter-Erweiterungen zu spezifizieren.
4. **Unsichere Filter:** Abfragen müssen serverseitig auf bekannte Tabellen, Spalten und erlaubte Operatoren begrenzt und parametrisiert werden. Komplexität und Ergebnisgröße brauchen Limits.
5. **Betrieb auf IONOS Shared Hosting:** PATH_INFO, mod_rewrite, .htaccess, PDO-Treiber, Fehlerausgabe, Schreibrechte und Speicherlimits sind mit realen Requests zu testen. Die SASD-API darf nicht implizit /public oder einen dauerhaft laufenden Prozess voraussetzen.
6. **Datenbankmigrationen und Backup/Recovery:** Eine CRUD-Schnittstelle ersetzt weder Datenbankmigrationskontrolle noch Backup, Restore-Tests, Rollback-Strategie oder Audit-Protokollierung.
7. **Lizenzgrenzen:** MIT, Apache-2.0 und DreamFactory-Commerce-Lizenzen dürfen nicht gleichgesetzt werden. Diese Recherche erlaubt keine pauschale Übernahme fremden Codes; Abhängigkeiten, NOTICE- und Copyright-Hinweise müssen je Komponente geprüft werden.
8. **Version und Edition:** Features aus Marketingseiten und READMEs können unterschiedlich verteilt oder seit der Dokumentation geändert worden sein. Vor Übernahme einer konkreten Funktion ist ein kleiner technischer Proof of Concept sinnvoll.

## 10. Verifikations- und Pflegeplan

Für jedes Kandidatenfeature, das in einen SASD-Meilenstein übernommen werden soll: (1) konkrete Upstream-Version/Commit identifizieren, (2) Community-/Pro-Lizenz prüfen, (3) Quellcode oder API-Referenz gegen die README abgleichen, (4) eine reproduzierbare Demo ausführen, (5) negative Sicherheitsfälle testen, (6) technischen Entscheid in eigener SASD-ADR dokumentieren. Der vorliegende Katalog ist ein recherchierter Überblick; es wurden **keine** Produkte installiert oder Integrationstests ausgeführt.

## 11. Primärquellen und Ausgangspunkte

### PHP-CRUD-API

- [P1] https://github.com/mevdschee/php-crud-api/blob/main/README.md – Konfiguration, Endpunkte, Middleware, Authentifizierung, Einschränkungen.
- [P2] https://github.com/mevdschee/php-crud-api/blob/main/README.md#joins – Beziehungspfad-/Join-Abfragen.
- [P3] https://github.com/mevdschee/php-crud-api/blob/main/README.md#authentication – Authentifizierungsvarianten.

### DreamFactory

- [D1] https://github.com/dreamfactorysoftware/dreamfactory – aktuelle README mit Funktionsübersicht.
- [D2] https://www.dreamfactory.com/features/ – Produkt-/Enterprise-Funktionsdarstellung.
- [D3] https://github.com/dreamfactorysoftware/dreamfactory#commercial-licenses – Editionshinweis.
- [D4] https://github.com/dreamfactorysoftware/dreamfactory#supported-data-sources – Datenquellen/Services.
- [D5] https://github.com/dreamfactorysoftware/dreamfactory#mcp-server-for-ai-and-llm-integration – MCP-/KI-Funktionen.
- Weiterführend: https://docs.dreamfactory.com/ und https://guide.dreamfactory.com/.

### DB2Rest

- [B1] https://github.com/9tigerio/db2rest – Architektur, Datenbanken, Releases.
- [B2] https://db2rest.com/docs/data-api-features – Funktionsliste; einzelne Aussagen sind Überblicksbehauptungen.
- [B3] https://db2rest.com/docs/multidb/connect-multiple-db – mehrere Datenbanken.
- [B4] https://db2rest.com/docs/category/insert – Inserts.
- [B5] https://db2rest.com/docs/category/update – Updates.
- [B6] https://db2rest.com/docs/category/delete – Deletes.
- [B7] https://db2rest.com/docs/category/query – Lesen, Filter, Pagination.
- [B8] https://db2rest.com/docs/read/supported-rsql-operators – Filteroperatoren.
- [B9] https://db2rest.com/docs/namedsql/introduction-to-named-queries – Named SQL.
- [B10] https://db2rest.com/docs/remoteprocedurecall/stored-procedure – Procedures.
- [B11] https://db2rest.com/docs/security/basic_auth – Basic Auth (Default: deaktiviert).
- [B12] https://db2rest.com/docs/security/api_key – API-Key-Auth.
- [B13] https://db2rest.com/docs/actuator/heath-check – Health.
- [B14] https://db2rest.com/docs/observability/grafana – Prometheus/Grafana.
- [B15] https://db2rest.com/docs/category/monitoring – Monitoring.
- Ergänzung: https://db2rest.com/docs/join/how-to-join-multiple-tables – Join-Arten und dokumentierte Einschränkungen.

---

**Dokumentstatus:** Externe Referenzrecherche, nicht normative SASD-Anforderung. **Pflegehinweis:** Vor einem späteren Architekturentscheid die verlinkten Upstream-Dokumente und Editionsstände erneut überprüfen.
