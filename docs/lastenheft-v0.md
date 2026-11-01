# SASD-API-Server – Lastenheft V0

> **Status:** Entwurf zur fachlichen Abstimmung · **Version:** 0.1 · **Erstellt:** 01.11.2026  
> **Repository:** [Robin-Goerlach/SASD-API-Server](https://github.com/Robin-Goerlach/SASD-API-Server)  
> **Weiterführend:** [Lastenheft V1](./lastenheft-v1.md) · [Funktionskatalog der Referenzprodukte](./referenzprodukte-funktionskatalog.md) · [englischer Funktionskatalog](./reference-products-feature-catalog.en.md)

## 1. Gegenstand und Charakter dieses Dokuments

Dieses **Lastenheft** beschreibt, **was** der erste SASD API Server leisten soll und woran die Auftraggeberseite seine Eignung erkennt. Es definiert fachliche Ziele, Grenzen, Qualitätsanforderungen und Abnahmekriterien; konkrete Klassen, Bibliotheken und interne Lösungen gehören in ein späteres **Pflichtenheft**. Technische Randbedingungen, die durch den vorgesehenen Betrieb vorgegeben sind, werden ausdrücklich genannt.

**V0 ist ein kleiner, sicher betreibbarer erster Entwicklungsstand, kein funktionsreicher API-Baukasten.** V0.1.0 ist der erste Ziel-Release innerhalb der V0-Entwicklungslinie. V1 wird in einem gesonderten Lastenheft als späterer Produktumfang beschrieben und ist weder Voraussetzung für V0 noch automatisch freigegeben.

### 1.1 Ausgangslage

Mehrere SASD-Anwendungen benötigen vergleichbare Basisdienste für persistente Daten: Datensätze anlegen, finden, lesen, ändern und löschen. Eine wiederholt neu entwickelte CRUD-Schicht verursacht Aufwand und uneinheitliche Schnittstellen. Der geplante Server soll als eigenständige, wiederverwendbare Installation aufrufbar sein. Unterschiedliche SASD-Anwendungen sollen **mehrere getrennte Installationen derselben Software** verwenden können, jeweils mit eigenem URL, eigenen Zugangsdaten, Ressourcen und Datenbankverbindungen. Die einzelnen Anwendungen müssen trotzdem ihre fachlichen Abläufe selbst verantworten.

### 1.2 Ziele

1. Eine kleine, einheitliche REST/JSON-Schnittstelle für freigegebene MariaDB/MySQL-Ressourcen bereitstellen.
2. Neue unabhängige Instanzen primär über Konfiguration statt durch kopierten oder angepassten PHP-Quellcode bereitstellen.
3. Standardoperationen und Fehler verlässlich und gut verständlich definieren.
4. Daten **niemals automatisch oder ungesichert** veröffentlichen; ein minimaler Sicherheitsstandard gilt bereits für V0.
5. Ein übersichtliches technisches Fundament schaffen, das spätere Erweiterungen zulässt, ohne sie vorwegzunehmen.

### 1.3 Nicht-Ziele

V0 ist **weder** ein vollständiger Jakarta-/Java-Application-Server **noch** ein API-Gateway wie DreamFactory, **noch** eine eigenständige Geschäftsprozess-/Workflow-Plattform. V0 ersetzt keine datenbankspezifische Backup-Software, keine Unternehmensbenutzerverwaltung und keine komplexe Anwendungsschicht.

## 2. Zielgruppen, Einsatzszenarien und Systemgrenze

**Zielgruppen:** (a) SASD-Entwicklung, die eine konsistente Daten-API benötigt; (b) Betreiber einzelner Instanzen; (c) Clients wie Webanwendungen, Desktopprogramme oder andere Server, die HTTP und JSON verwenden.

**Szenario A – getrennte Installationen:** Eine erste Instanz auf api.sasd.de spricht Datenbank A an; eine zweite Instanz unter einer anderen URL spricht Datenbank B an. Beide verwenden denselben freigegebenen Softwarestand, jedoch eigene Konfiguration und Credentials. Eine Instanz darf keinen Zugriff auf Ressourcen der anderen Instanz vermitteln.

**Szenario B – einfacher CRUD-Client:** Ein autorisierter Client listet Bücher, liest eines, legt einen Datensatz an, ändert zulässige Felder und entfernt einen Datensatz, soweit dessen Ressourcenkonfiguration dies erlaubt. Dies ist ein neutrales Beispiel; HARALD-Ausleihvorgänge und vergleichbare Fachtransaktionen bleiben außerhalb des generischen CRUD-Servers.

**Systemgrenze:** Der API Server bearbeitet HTTP-Anfragen und speichert über eine konfigurierte SQL-Verbindung. Anwendungsseitige UIs, Datenbankadministration, Migration fachlicher Tabellen, Backup-Scheduler, E-Mail und Anwendungsworkflows sind externe Zuständigkeiten.

## 3. Zielumgebung und Randbedingungen

| Kennung | Verbindliche Randbedingung |
|---|---|
| RB-01 | Primärziel: PHP 8.4 mit MariaDB 10.11/MySQL-kompatibler Datenbank. Versionsabweichungen werden erst nach Kompatibilitätstests freigegeben. |
| RB-02 | Bereitstellung auf gewöhnlichem PHP-Shared-Hosting muss ohne Docker, Redis, Rootzugang und lang laufenden Prozess möglich sein. |
| RB-03 | Deployment darf **kein** /public-Verzeichnis voraussetzen; der HTTP-Einstieg erfolgt bei Bedarf über index.php im Webroot. |
| RB-04 | Das URL-Routing muss auf Hostingsystemen mit nur eingeschränkt wirksamer .htaccess/mod_rewrite getestet werden. Ein dokumentierter, kompatibler Fallback ist erforderlich, wenn saubere Pfade nicht funktionieren. |
| RB-05 | HTTP(S), JSON und standardisierte HTTP-Methoden/-Statuscodes sind die externe Kommunikationsgrundlage. |
| RB-06 | Die API wird pro Installation separat konfiguriert. Zentralisierte Instanzverwaltung und dynamisches Umschalten zwischen fremden Installationen gehören nicht zu V0. |
| RB-07 | Der Quellcode soll als MIT-lizenziertes, eigenständiges Open-Source-Projekt weiterentwickelt werden; Fremdcode darf nur unter geprüften Bedingungen übernommen werden. |

## 4. Funktionsanforderungen

Alle Anforderungen mit Priorität **MUSS** sind für V0.1.0 zwingend. **SOLL** bezeichnet einen wichtigen, aber bei explizit dokumentierter Begründung verschiebbaren Umfang. **KANN** ist nicht abnahmepflichtig. Der später angegebene Abnahmekatalog verwendet ausschließlich MUSS-Anforderungen.

### V0-F01 – REST-Ressourcen veröffentlichen

**Priorität:** MUSS  
**Anforderung:** Der Server bietet klar benannte HTTP-Endpunkte für ausdrücklich freigegebene Datenressourcen an.

**Umfang und erwartetes Verhalten:** Jede Ressource besitzt einen externen Namen und verweist intern auf genau eine freigegebene Tabelle. Der Server kann mindestens zwei unterschiedliche Ressourcen innerhalb einer Instanz verwalten. Ungültige oder nicht konfigurierte Ressourcen bleiben unzugänglich. Die API-Struktur wird öffentlich dokumentiert, ohne interne Datenbanktabellen unkontrolliert preiszugeben.

**Abnahme:** Eine konfigurierte Ressource ist lesbar; ein beliebiger nicht konfigurierter Tabellenname liefert keine Datensätze, sondern eine passende Ablehnung (beispielsweise 404).

**Referenz:** PHP-CRUD-API: Tabellenfreigabe, automatische REST-Ressourcen; DreamFactory/DB2Rest: CRUD-Ressourcen.

### V0-F02 – Liste und Einzelobjekt lesen

**Priorität:** MUSS  
**Anforderung:** Ein berechtigter Client kann alle sichtbaren Datensätze einer Ressource und einen einzelnen Datensatz über einen Primärschlüssel lesen.

**Umfang und erwartetes Verhalten:** GET auf eine Sammlungsressource liefert eine JSON-Liste mit definierter Hülle; GET auf einen eindeutig adressierten Datensatz liefert ein JSON-Objekt. Für V0 werden einfache Tabellen mit **einem** Primärschlüssel unterstützt; zusammengesetzte Primärschlüssel werden bewusst ausgeschlossen. Leere Listen und fehlende Einzelobjekte werden unterschiedlich behandelt.

**Abnahme:** Ein vorhandener Datensatz wird korrekt ausgegeben; eine leere Liste wird erfolgreich mit leerem Array dargestellt; ein nicht existierender Schlüssel liefert 404.

**Referenz:** PHP-CRUD-API/DB2Rest: Records lesen.

### V0-F03 – Datensätze anlegen

**Priorität:** MUSS  
**Anforderung:** Ein berechtigter Client kann per POST einen neuen Datensatz einer schreibbaren Ressource anlegen.

**Umfang und erwartetes Verhalten:** Erlaubte Eingabefelder werden aus der Ressourcenkonfiguration abgeleitet. Nicht freigegebene Felder und unbekannte Attribute werden abgewiesen; die Datenbank darf nötige Standardwerte oder eine automatisch erzeugte ID vergeben. Fehlende Pflichtwerte werden nachvollziehbar gemeldet. Die Antwort benennt die neue Ressource bzw. den neuen Schlüssel.

**Abnahme:** Ein valider POST liefert einen Datensatz und einen passenden Erfolgscode (in der Regel 201); ein fehlendes Pflichtfeld erzeugt keine neue Zeile und einen strukturierten Fehler.

**Referenz:** PHP-CRUD-API/DB2Rest: Einzel-Insert.

### V0-F04 – Datensätze ändern

**Priorität:** MUSS  
**Anforderung:** Ein berechtigter Client kann bestehende Datensätze aktualisieren.

**Umfang und erwartetes Verhalten:** PUT und PATCH werden mit **bewusst dokumentierter, unterschiedlicher Semantik** angeboten: PUT ersetzt die konfigurierten veränderbaren Felder nach festgelegtem Vollersatz-Vertrag; PATCH ändert ausschließlich ausdrücklich mitgesendete Felder. Schreibgeschützte Schlüssel, nicht freigegebene Spalten und unzulässige Werte sind geschützt. Ein nicht vorhandener Datensatz darf nicht stillschweigend angelegt werden.

**Abnahme:** Ein PATCH mit nur einem Feld verändert keine weiteren Felder; nicht schreibbare Felder werden abgewiesen; Änderung eines unbekannten Schlüssels liefert 404.

**Referenz:** PHP-CRUD-API: Update; DB2Rest: Update/Patch.

### V0-F05 – Datensätze löschen

**Priorität:** MUSS  
**Anforderung:** Ein berechtigter Client kann einen vorhandenen Datensatz gezielt löschen, sofern dies für die Ressource freigegeben ist.

**Umfang und erwartetes Verhalten:** DELETE bezieht sich in V0 ausschließlich auf einen vollständig adressierten Einzel-Datensatz. Es gibt **kein** bedingtes Massenlöschen. Datenbankseitige Referenzintegrität bleibt wirksam; der Server umgeht sie nicht.

**Abnahme:** Nur die konkrete ID wird gelöscht. Ein Löschversuch auf einer schreibgeschützten Ressource wird verweigert; ein referenziell gesperrter Datensatz wird ohne Datenverlust mit verständlichem Fehler behandelt.

**Referenz:** PHP-CRUD-API/DB2Rest: Delete; bewusster Verzicht auf Bulk Delete.

### V0-F06 – Ressourcen und erlaubte Operationen konfigurieren

**Priorität:** MUSS  
**Anforderung:** Ein Betreiber bestimmt pro Installation und Ressource, was überhaupt erreichbar ist.

**Umfang und erwartetes Verhalten:** Eine dokumentierte Konfiguration führt die erlaubten Ressourcen auf und ordnet ihnen SQL-Tabelle, zulässige Lese-/Schreibfelder, Primärschlüssel und erlaubte Methoden zu. **Deny by default** gilt für jede nicht freigegebene Ressource, Spalte und Operation. V0 bietet keine öffentliche, zur Laufzeit schreibbare Administrations-API.

**Abnahme:** Eine neu angelegte Datenbanktabelle erscheint nicht automatisch in der API. Eine Methode außerhalb der Freigabe wird auch dann abgewiesen, wenn die Datenbank sie technisch erlaubt.

**Referenz:** PHP-CRUD-API: Tabellen-Mapping, Authorization; DreamFactory: explizite Service-/Ressourcenfreigabe.

### V0-F07 – Einfache Filter

**Priorität:** MUSS  
**Anforderung:** Ein Client kann Listen mit genau definierten, sicheren Filterbedingungen einschränken.

**Umfang und erwartetes Verhalten:** Für V0 werden **Gleichheitsfilter** auf explizit erlaubte Spalten sowie mehrere mit UND verbundene Gleichheitsfilter unterstützt. Feldnamen werden niemals ungeprüft in SQL übernommen, Werte ausschließlich parametrisiert. Unsinnige Filter oder unbekannte Spalten liefern einen definierten Clientfehler statt einer SQL-Fehlermeldung. Komplexe OR-Ausdrücke, freie SQL-Fragmente und Joins sind nicht enthalten.

**Abnahme:** Ein Filter liefert die passenden Zeilen; unbekannte oder gesperrte Filterspalten werden abgewiesen; ein SQL-Injection-Versuch führt nicht zur Ausführung fremden SQLs.

**Referenz:** PHP-CRUD-API: Filter; DB2Rest: RSQL als bewusst vereinfachtes Vorbild.

### V0-F08 – Sortierung und Seitengröße

**Priorität:** MUSS  
**Anforderung:** Listen sind für Clients nachvollziehbar sortier- und begrenzbar.

**Umfang und erwartetes Verhalten:** Mindestens ein erlaubtes Sortierfeld sowie auf- oder absteigende Reihenfolge werden unterstützt. Eine begrenzte Pagination mit festen, konfigurierbaren Obergrenzen ist Pflicht. Fehlt eine Sortierung, wird eine stabile Standardsortierung anhand des Primärschlüssels verwendet. Die maximale Seitengröße darf vom Client nicht überschrieben werden; exakte Grenzwerte legt das Pflichtenheft fest.

**Abnahme:** Zwei aufeinanderfolgende Seiten liefern nachvollziehbare Ergebnisse und respektieren die Seitengrenze. Eine geforderte überhöhte Seitengröße wird nach dokumentierter Regel zurückgewiesen oder gekappt.

**Referenz:** PHP-CRUD-API: Pagination/Page Limits; DreamFactory/DB2Rest: Sortierung und Pagination.

### V0-F09 – Einheitliches JSON- und Fehlerformat

**Priorität:** MUSS  
**Anforderung:** Clients können Erfolg, Fehler und Validierungsprobleme einheitlich auswerten.

**Umfang und erwartetes Verhalten:** V0 definiert ein konsistentes JSON-Format für Listen, Einzelressourcen und Fehler einschließlich maschinenlesbarem Fehlercode, lesbarer Meldung und optionalen Feldfehlern. Erforderliche HTTP-Statuscodes umfassen mindestens 200/201/204, 400, 401, 403, 404, 405, 409, 413, 415, 422 und 500, soweit der Fall auftritt. Interne SQL-Abfragen, Dateipfade, Passwörter oder Stacktraces dürfen nicht an den Client gelangen.

**Abnahme:** Ein ungültiges Feld führt zu einem dokumentierten 4xx-Code und JSON-Fehlerobjekt; ein künstlich provozierter Serverfehler gibt keine vertraulichen internen Details preis.

**Referenz:** Alle Referenzprodukte: standardisierte REST-Rückmeldungen.

### V0-F10 – Konfiguration und getrennte Installationen

**Priorität:** MUSS  
**Anforderung:** Dieselbe Software lässt sich ohne Quellcodeänderung für verschiedene Anwendungen betreiben.

**Umfang und erwartetes Verhalten:** Mindestens zwei Instanzen können parallel mit **verschiedenen URLs, Datenbanken, API-Schlüsseln und Ressourcenlisten** betrieben werden. Credentials und sonstige Secrets stehen nicht im Repository oder in öffentlich auslieferbaren Dateien. Die Konfiguration ist nachvollziehbar dokumentiert; ein Wechsel der Instanz ändert keine Quellcodedateien. Bei mehreren Installationen darf die Cache-/Log-Konfiguration keine gemeinsamen Daten unkontrolliert vermischen.

**Abnahme:** Zwei Testinstallationen verwenden denselben Programmstand, liefern unterschiedliche Datensätze und akzeptieren ihre jeweils eigenen Schlüssel; Kreuz-Zugriffe werden verhindert.

**Referenz:** PHP-CRUD-API: Verbindungsparameter, Umgebungsvariablen; DreamFactory: Datenquellen-Konfiguration.

### V0-F11 – Minimale API-Schlüssel-Authentifizierung

**Priorität:** MUSS  
**Anforderung:** Nichtöffentliche Datenendpunkte sind bereits in V0 vor anonymem Zugriff geschützt.

**Umfang und erwartetes Verhalten:** Jede API-Instanz benötigt mindestens einen starken, installationsspezifischen Schlüssel, der über einen dokumentierten HTTP-Header übertragen wird. **Alle Datenoperationen erfordern standardmäßig Authentifizierung**, auch GET. Vergleiche erfolgen sicher; Schlüsselwerte dürfen nicht in Logs oder Fehlermeldungen erscheinen. Eine Konfigurationsänderung erlaubt den Austausch beziehungsweise Widerruf eines Schlüssels. Rollen, Benutzerkonten und Browser-Login sind ausdrücklich nicht Teil von V0. Ein fest eingebauter API-Key ist **keine geeignete Geheimhaltung für öffentlich verteilte Browser-/Mobile-Clients**; hierfür muss ein vertrauenswürdiger Backend-Proxy oder späteres Benutzer-Auth-Konzept verwendet werden.

**Abnahme:** Ohne/mit falschem Schlüssel keine Daten; mit gültigem Schlüssel nur konfigurierte Ressourcen; ein ausgetauschter Schlüssel wird nicht mehr akzeptiert.

**Referenz:** PHP-CRUD-API: apiKeyAuth; DreamFactory/DB2Rest: API-Keys.

### V0-F12 – Eingabevalidierung und SQL-Sicherheit

**Priorität:** MUSS  
**Anforderung:** Ungültige oder gefährliche Eingaben dürfen Datenbankintegrität und Systemsicherheit nicht beeinträchtigen.

**Umfang und erwartetes Verhalten:** V0 prüft JSON-Syntax, erlaubte Feldnamen, erforderliche Felder, einfache Datentypen, Maximallängen und zulässige Werte, soweit konfiguriert. SQL-Werte werden parametrisiert; **SQL-Identifiers** (Tabellen-, Spalten- und Sortiernamen) kommen ausschließlich aus serverseitigen Allowlists. Anfragegrößen und Ergebnismengen werden begrenzt. Unerwartete Datenbankfehler führen zu einer kontrollierten Antwort.

**Abnahme:** Testfälle mit unbekannten Attributen, fehlerhaften Typen, großen Bodys und SQL-Injection-Payloads verändern weder Schema noch nicht betroffene Datensätze.

**Referenz:** PHP-CRUD-API: Validation/Sanitation, Page Limits; DB2Rest: Query-Limits.

### V0-F13 – HTTPS und Betriebssicherheit

**Priorität:** MUSS  
**Anforderung:** Eine öffentlich erreichbare Instanz darf Zugangsdaten und Nutzdaten nicht unverschlüsselt übertragen.

**Umfang und erwartetes Verhalten:** Der Betreiber muss HTTPS für externe Requests voraussetzen und die HTTPS-Konfiguration dokumentieren. Wenn der Webserver TLS terminiert, müssen nur vertrauenswürdige Proxyinformationen berücksichtigt werden. CORS-Header dürfen nicht versehentlich alle Browserursprünge mit Credentials zulassen; CORS ist kein Authentifizierungsersatz. Fehleranzeigen und Debugmodus müssen im Produktivbetrieb abschaltbar sein.

**Abnahme:** Eine HTTP-Anfrage erhält keine vertraulichen Daten. Nicht erlaubte Browserursprünge werden gemäß dokumentierter CORS-Policy behandelt; Zugangsdaten erscheinen nicht in Antworten.

**Referenz:** PHP-CRUD-API: sslRedirect, cors, firewall; DreamFactory: Security Governance.

### V0-F14 – Diagnose- und Statusschnittstelle

**Priorität:** MUSS  
**Anforderung:** Ein Betreiber kann einen laufenden Server grundlegend prüfen, ohne sensible Daten auszulesen.

**Umfang und erwartetes Verhalten:** Ein schmaler Status-Endpunkt meldet die Erreichbarkeit und optional nach ausdrücklich definierter Zugriffskontrolle die Datenbankbereitschaft. Öffentliche Antworten dürfen keine DB-Credentials, Schema-Namen, Hostpfade oder Versionsdetails preisgeben. Detaillierte Fehler sind nur in geschützten Betreiberprotokollen sichtbar.

**Abnahme:** Der Health-Aufruf liefert ein wohldefiniertes Ergebnis. Ein simulierter Datenbankausfall wird erkannt oder als eingeschränkte Bereitschaft ausgewiesen, ohne Secrets zu offenbaren.

**Referenz:** PHP-CRUD-API: Status; DB2Rest: Actuator Health.

### V0-F15 – Nachvollziehbare Protokollierung

**Priorität:** MUSS  
**Anforderung:** Betreiber können technische Fehler und abgewiesene Zugriffe untersuchen.

**Umfang und erwartetes Verhalten:** Der Server protokolliert Request-Zeit, Route oder Ressourcenkennung, HTTP-Methode, Status, Korrelations-/Request-ID und Fehlerkategorie in angemessenem Umfang. **Nicht** protokolliert werden Klartextschlüssel, Passwörter, vollständige Auth-Header oder pauschal alle Nutzdaten. Shared-Hosting-kompatible Logging-Ziele und maximale Logmengen sind zu dokumentieren.

**Abnahme:** Für einen künstlich erzeugten Fehler kann ein Protokolleintrag anhand einer Request-ID identifiziert werden; Logdateien enthalten keine API-Schlüssel.

**Referenz:** DreamFactory: Auditing als weiterführende Referenz; DB2Rest: Monitoring; SASD: notwendiges Minimal-Logging.

### V0-F16 – Dokumentation, Beispiele und Installation

**Priorität:** MUSS  
**Anforderung:** Ein Entwickler kann den Server ohne Kenntnis seiner internen Klassen installieren und benutzen.

**Umfang und erwartetes Verhalten:** Mindestens README, Konfigurationsbeispiel **ohne Secrets**, Installationshinweise für PHP-Shared-Hosting und einen ersten erfolgreichen REST-Request liefern. Alle V0-Routen, HTTP-Methoden, Antwortformate, Filter-/Pagination-Parameter, Fehlertypen und sicherheitsrelevanten Defaults werden dokumentiert. Die Beispiele unterscheiden Entwicklungs- und Produktionsbetrieb.

**Abnahme:** Eine unabhängige Testperson kann anhand der Anleitung eine zweite Instanz mit separater Testdatenbank starten und die dokumentierten CRUD-Requests ausführen.

**Referenz:** PHP-CRUD-API: einfache Bereitstellung; alle Projekte: dokumentierte API.

### V0-F17 – Automatisierte Kernprüfung

**Priorität:** MUSS  
**Anforderung:** Der veröffentlichte Entwicklungsstand muss reproduzierbar auf grundlegende Funktion und Sicherheit geprüft werden können.

**Umfang und erwartetes Verhalten:** Es gibt Tests für jede CRUD-Methode, Filter, Sortierung, Pagination, Ressourcensperren, Schlüsselprüfung, Datentypfehler, SQL-Injection-Abwehr und Isolation zweier Instanzen. Eine kleine Beispieldatenbank mit wiederherstellbaren Testdaten ist vorgesehen. Tests dürfen keine Produktionsdaten benötigen.

**Abnahme:** Ein dokumentierter Testlauf ist erfolgreich; Sicherheits-Negativtests und Datenbankfehlerfälle sind Teil des Abnahmeprotokolls.

**Referenz:** SASD-Engineering-Qualitätsanforderung; Referenzkatalog Abschnitt 9/10.

## 5. Qualitätsanforderungen (nicht funktional)

| ID | Kategorie | V0-Mindestanforderung und Nachweis |
|---|---|---|
| V0-N01 | Einfachheit | Erste lauffähige Bereitstellung auf PHP-Shared-Hosting ohne zusätzliche Hintergrunddienste; Installationsanleitung anhand einer frischen Instanz getestet. |
| V0-N02 | Wartbarkeit | HTTP, Controller, Service, Repository, Sicherheitsprüfung und Infrastruktur bleiben in klaren Verantwortungsbereichen getrennt; gut lesbare und nachvollziehbar kommentierte öffentliche Schnittstellen. Das ist eine Architektur-Qualitätsanforderung, keine Vorschrift für einzelne Klassen. |
| V0-N03 | Sicherheit | Zugriff standardmäßig gesperrt; least-privilege SQL-Konto; parametrisierte Queries; keine Secrets in Git/Logs/Antworten; zentrale Fehlerbehandlung; dokumentierter Schutz gegen übergroße Requests. |
| V0-N04 | Kompatibilität | PHP 8.4/MariaDB 10.11 als Zielkombination; Lauf auf Windows/WSL zum Entwickeln und Linux-Shared-Hosting zum Bereitstellen ist mit Testhinweisen nachzuweisen. |
| V0-N05 | Zuverlässigkeit | Erfolgreiche einzelne Schreiboperationen werden dauerhaft gespeichert; bei Fehlern darf kein halbfertiger Einzelvorgang verbleiben; Datenbank-Constraints werden respektiert. |
| V0-N06 | Leistung | Kleine Datensätze müssen ohne unangemessen hohe Speicher- oder Abfragelast verarbeitet werden. Gemessene Beispielwerte und Testdatenvolumen werden in der Abnahme dokumentiert; erfundene Millisekunden-Grenzen werden hier bewusst vermieden. |
| V0-N07 | Portabilität | Installationsunterschiede stecken in dokumentierter Konfiguration; ein Webroot-index.php-Szenario sowie ein Fallback bei eingeschränktem Rewriting werden getestet. |
| V0-N08 | Testbarkeit | Wiederholbare Tests mit eigener Datenbank, gesicherten Test-Credentials und reproduzierbarem Beispieldatensatz. |
| V0-N09 | Datenschutz | Datenminimierung in Rückgaben und Protokollen, gesicherter Zugang und Löschbarkeit; datenschutzrechtliche Einordnung der jeweiligen Fachanwendung bleibt deren Verantwortungsbereich. |

## 6. Fachlicher API-Rahmen und Beispielabläufe

Die **konkrete Schreibweise** der folgenden Beispiele ist vorläufig und wird im Pflichtenheft verbindlich festgelegt. Für V0 wird ein eigener, als vorläufig gekennzeichneter API-Pfad empfohlen, etwa **/api/v0/**, um einen späteren stabilen V1-Vertrag sauber davon zu trennen. Der Release-Name V0.1.0 und die URL-Version sind unterschiedliche Begriffe.

**Beispielressource books:** GET /api/v0/books; GET /api/v0/books/42; POST /api/v0/books; PATCH /api/v0/books/42; PUT /api/v0/books/42; DELETE /api/v0/books/42. Tabellen- und Feldnamen dürfen **nicht** frei aus Benutzerparametern abgeleitet werden, sondern ausschließlich aus der Ressourcenkonfiguration.

**Beispiel eines sicheren Ablaufes:** (1) Der Client übermittelt HTTPS-Anfrage samt API-Schlüssel. (2) Der Server prüft Authentifizierung und Ressourcen-/Methodenfreigabe. (3) Eingaben werden validiert und auf erlaubte Felder beschränkt. (4) Eine parametrisierte Datenbankoperation wird ausgeführt. (5) Eine dokumentierte JSON-Antwort wird mit Request-ID zurückgegeben. (6) Der Vorgang wird ohne Geheimnisse protokolliert.

**Transaktionsabgrenzung:** Ein einzelner HTTP-Schreibrequest soll atomar sein, soweit die Datenbank dies unterstützt; fachliche Mehrschritttransaktionen über mehrere Ressourcen bzw. mehrere Requests sind nicht Teil von V0.

## 7. Datenhaltung und Datenverantwortung

- V0 verwaltet keine fachlichen Tabellenmigrationen automatisch und legt nicht stillschweigend neue Anwendungstabellen an. Die Tabellen müssen vor der Bereitstellung vorhanden sein.
- Die API veröffentlicht nur die nach Konfiguration erforderlichen Spalten. Interne Passworthashes, Sicherheitswerte oder andere sensible Felder bleiben aus der API ausgeschlossen.
- Ein eigenes, minimal berechtigtes Datenbankkonto pro Instanz wird empfohlen; in Produktionskonfigurationen müssen alle erforderlichen Berechtigungen dokumentiert und auf das notwendige Maß begrenzt sein.
- **Backup/Restore:** Der Betreiber ist für Datenbank-Backups und Wiederherstellung verantwortlich; die Produktdokumentation beschreibt Voraussetzungen und empfiehlt regelmäßige Restore-Tests. Eine eigene Backup-Engine gehört nicht zu V0.
- Zeitangaben sollen eindeutig behandelt werden: UTC als persistente Zeitbasis, sofern V0 selbst Zeitstempel erzeugt; fachanwendungsseitige Zeitzonenumrechnung ist Sache der Clients.

## 8. Explizite Ausschlüsse und Verschiebungen

| Nicht in V0 | Begründung / Zielbereich |
|---|---|
| Benutzerregistrierung, Passwort-Login, SSO, JWT, LDAP/SAML | Umfangreiche Identitätsverwaltung; erst nach konkretem Benutzerkonzept. |
| Feingranulares RBAC, Zeilenrechte, Mandantentrennung | In V0 zunächst Instanzisolation und Ressourcen-/Methoden-/Spalten-Allowlist. V1 behandelt feinere Zugriffsregeln. |
| PostgreSQL, SQLite, SQL Server, Oracle als produktive Adapter | V0 erprobt zunächst genau einen SQL-Treiber und eine saubere Adaptergrenze. |
| JSON-Dateispeicher, externe APIs als Storage-Backends | Andere Konsistenz-, Sperr- und Fehlersemantik; erst nach eigenen Anforderungen. |
| Automatisierte Joins, komplexes RSQL, freie Aggregationen | Risiko großer Abfragen und zusätzlicher Sicherheitsregeln. |
| Bulk-Insert/-Update/-Delete; Cross-Request-Transaktionen | Komplexere Fehlerbehandlung, Wiederholbarkeit und Atomarität. |
| Schemaänderungen über HTTP, beliebige Client-SQL | Unverhältnismäßig gefährlich für eine erste öffentliche API. |
| OpenAPI-Generator, Web-Administrationsoberfläche, GraphQL | Nicht nötig für eine dokumentierte erste CRUD-API; OpenAPI später in V1. |
| Dateiupload, S3, FTP/SFTP, SMTP, SOAP, Push, Webhooks | Integrationsplattform statt schlanker Daten-API. |
| GIS/GeoJSON, MCP/KI-Schnittstellen, Prometheus/Grafana | Kein V0-Kernnutzen; Bedarf später separat bewerten. |

## 9. Abnahmeszenarien und Release-Gate

| Testfall | Nachweis | Zugeordnete Anforderungen |
|---|---|---|
| AT-V0-01 | Frische Installation auf Zielhosting mit dokumentiertem URL-Routing | F01, F10, F16, N01, N07 |
| AT-V0-02 | Erlaubte GET-Liste und Einzelobjekt, inklusive 404/Leerliste | F02, F09 |
| AT-V0-03 | POST, PUT, PATCH und DELETE mit DB-Nachweis und erlaubten Feldern | F03, F04, F05, F06 |
| AT-V0-04 | Gleichheitsfilter, erlaubte Sortierung und begrenzte Seiten | F07, F08 |
| AT-V0-05 | Unbekannte Tabelle, Spalte und Methode werden gesperrt | F01, F06, F12 |
| AT-V0-06 | Anonym, ungültiger Schlüssel, widerrufener Schlüssel – kein Datenzugriff | F11, F13 |
| AT-V0-07 | SQL-Injection, falsche JSON-Typen, übergroßer Request – sicher abgewiesen | F09, F12 |
| AT-V0-08 | Zwei getrennte Instanzen mit verschiedenen Daten und Credentials | F10 |
| AT-V0-09 | Health/Log-Test bei erreichbarer und nicht erreichbarer Datenbank | F14, F15 |
| AT-V0-10 | README-Beispiele und automatisierte Tests vollständig reproduzierbar | F16, F17 |

**Abnahme von V0.1.0:** Alle MUSS-Anforderungen und AT-V0-01 bis AT-V0-10 sind nachweislich erfüllt. Nicht bestandene Fälle werden als offene Punkte dokumentiert; eine geänderte Anforderung bedarf einer bewussten Entscheidung. Eine Funktionsdemo allein ohne die Sicherheits-Negativtests ist keine vollständige Abnahme.

## 10. Risiken, Annahmen und offene Entscheidungen

1. **Routing auf Shared Hosting:** Die tatsächliche Behandlung von index.php, PATH_INFO und URL-Rewriting ist zu testen; die Dokumentation muss einen praxistauglichen Fallback vorsehen.
2. **Schlüssel in unsicheren Clients:** Ein im Browser ausgelieferter oder ungeschützt verteilter API-Key ist kein dauerhaftes Secret. Die erste Freigabe gilt daher nur für kontrollierte Clients, Backend-zu-Backend-Szenarien oder eine geeignete vorgeschaltete Auth-Schicht.
3. **Primärschlüssel:** Für V0 nur einzelspaltige Schlüssel; genaue Typunterstützung (Zahl/UUID) und URL-Kodierung im Pflichtenheft klären.
4. **PUT/PATCH-Vertrag:** Vollersatz und Teiländerung müssen anhand konkreter Nullable- und Default-Felder getestet und dokumentiert werden.
5. **Konfiguration statt Entdeckungs-API:** Tabellen sind explizit freizugeben; automatische Schema-Introspektion darf daraus keine öffentliche Standardfreigabe erzeugen.
6. **Anwendungssicherheit:** V0 ist bewusst nicht für öffentlich registrierbare Multi-User-Webanwendungen oder besonders schutzbedürftige Fachdatensätze ohne zusätzliche Anwendungssicherheit vorgesehen.

## 11. Herkunft der wichtigsten Auswahlentscheidungen

| Gewählter V0-Baustein | Vorbild aus dem Funktionskatalog | Grund für frühe Aufnahme |
|---|---|---|
| Basis-CRUD und Ressourcenfreigabe | PHP-CRUD-API §§3.1–3.2; DreamFactory §4.1 | Kernnutzen des Produkts |
| Einfache Filter, Pagination, Limits | PHP-CRUD-API §3.3/3.4; DB2Rest §5.3 | Praktische Clientlisten, keine unkontrollierten Queries |
| API-Key und Allowlist | PHP-CRUD-API §3.4; DreamFactory §4.2 | Schutz muss vor produktivem Einsatz vorhanden sein |
| Validierung und sichere Fehler | PHP-CRUD-API §3.4; DB2Rest §5.4 | Datenintegrität und sichere HTTP-Schnittstelle |
| Health und Logs | PHP-CRUD-API §3.2; DB2Rest §5.5 | Diagnosefähigkeit ohne Monitoringplattform |
| Adapterfähige Trennung der Datenhaltung | DB2Rest §5.1 | Später erweiterbar, ohne weitere Adapter jetzt zu bauen |

## 12. Pflege, Freigabe und Dokumentenbeziehungen

- **Lastenheft V0 (dieses Dokument):** verbindlicher gewünschter Funktionsumfang **nach** fachlicher Freigabe.
- [Lastenheft V1](./lastenheft-v1.md): separates Zielbild für einen späteren, stabileren Produktrelease. V1 erweitert die tatsächlich abgenommene V0-Basis.
- [Funktionskatalog](./referenzprodukte-funktionskatalog.md): externe Inspirations-/Recherchequelle, kein automatischer Teil dieses Lastenhefts.
- **Pflichtenheft V0 (noch zu erstellen):** technische Umsetzung und konkrete API-Verträge, Entscheidungen zu Konfigurationsformat, IDs, Tests und Fehlerobjekten.
- **Änderungssteuerung:** Neue Anforderungen nur mit eigener Kennung, nachvollziehbarer Begründung und bewusster Zuordnung zu V0/V1/später aufnehmen.

---

**Hinweis:** Diese Fassung ist als gründlicher **Entwurf** erstellt. Sie ist nicht als bereits implementiert, getestet oder durch den Auftraggeber formal freigegeben gekennzeichnet.
