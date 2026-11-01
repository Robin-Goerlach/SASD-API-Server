# SASD-API-Server – Lastenheft V1

> **Sprache:** **Deutsch (bisheriger Dateipfad)** · [English (Standard)](requirements-v1.md) · [Deutsch (kanonisch)](requirements-v1.de.md)  

> **Status:** Entwurf für die spätere Produktversion 1.0 · **Dokumentversion:** 0.1 · **Erstellt:** 01.11.2026  
> **Repository:** [Robin-Goerlach/SASD-API-Server](https://github.com/Robin-Goerlach/SASD-API-Server)  
> **Grundlage:** [Lastenheft V0](./lastenheft-v0.md) · [Referenzprodukte-Funktionskatalog (DE)](./referenzprodukte-funktionskatalog.md) · [Feature Catalog (EN)](./reference-products-feature-catalog.en.md)

## 1. Einordnung des Zielprodukts

Dieses Lastenheft legt die **fachlich gewünschten Fähigkeiten** eines stabilen **SASD-API-Server V1.0** fest. Es ist ein **Zielbild nach V0**, kein Auftrag, alle 157 recherchierten Funktionen der Referenzprodukte zu implementieren. Es beschreibt das **Was und Warum**, die Grenzen und nachprüfbare Erwartungen. Das **Wie** (konkrete Klassen, Bibliotheken, SQL-Aufbau, Framework- und Cacheentscheidungen) gehört in ein späteres Pflichtenheft.

### 1.1 Weshalb V1 benötigt wird

Nach der kleinen, testbaren V0 sollen mehrere SASD-Anwendungen denselben API-Server-Kern langfristig nutzen. Sie benötigen dafür stabile API-Verträge, feinere Zugriffsrechte, einheitliche Datenmodelle, einen zusätzlichen Datenbankadapter, kontrollierte Abfragen sowie bessere Diagnose und Dokumentation. Trotz dieser Erweiterungen soll das Projekt bewusst **kleiner als DreamFactory, JBoss oder ein allgemeines Integrations-Gateway** bleiben.

### 1.2 Produktdefinition

Der SASD API Server ist eine **konfigurierbare REST/JSON-Datendienst-Plattform**, die definierte Ressourcen auf konfigurierte Datenquellen abbildet, Datenzugriff schützt und eine konsistente HTTP-Schnittstelle für unterschiedliche Clients bereitstellt. Jede Installation besitzt eigene URL, Einstellungen, Zugangsdaten und Ressourcendefinitionen. Die Fachlogik von HARALD, Gesundheitsanwendungen oder anderen Programmen wird nicht pauschal in einen generischen CRUD-Server verlagert.

### 1.3 Abhängigkeit von V0

**V1 baut auf einem vollständig abgenommenen V0-Stand auf.** Alle MUSS-Anforderungen des [Lastenhefts V0](./lastenheft-v0.md) gelten fort, soweit dieses Dokument sie ausdrücklich erweitert oder die V1-Vertragsversion bewusst anders festlegt. Fehlerbehebung, Sicherheitsnachweise und Testbarkeit von V0 dürfen für neue V1-Features nicht zurückgestellt werden.

### 1.4 Stakeholder und Zielgruppen

- **SASD-Entwicklung:** benötigt einen wiederverwendbaren Serverkern und stabile Vertrags-/Testgrundlagen.
- **Anwendungsentwickler:** benötigen dokumentierte, sichere Endpunkte ohne Kenntnis interner SQL-Tabellen.
- **Betreiber:** müssen Instanzen getrennt konfigurieren, bereitstellen, überwachen, sichern und nach Updates wiederherstellen können.
- **Client-Anwendungen:** sollen unabhängig von ihrer Programmiersprache über HTTP/JSON und dokumentierte Fehlerfälle arbeiten können.
- **Sicherheitsverantwortliche:** benötigen Least-Privilege, freigabebasierte Ressourcen, Nachvollziehbarkeit und kontrollierten Umgang mit personenbezogenen Daten.

## 2. Strategische Ziele und Erfolgsmaßstäbe

| Ziel-ID | Produktziel | Beobachtbares Ergebnis |
|---|---|---|
| Z-01 | Einheitliche API | Zwei unterschiedliche Beispielanwendungen konsumieren unabhängig dieselben API-Konventionen. |
| Z-02 | Konfigurierbarkeit statt Codekopien | Eine neue API-Instanz benötigt keine Änderung am gemeinsamen Programmcode. |
| Z-03 | Austauschbare Datenhaltung | Derselbe definierte Ressourcen- und CRUD-Vertrag funktioniert mit MariaDB/MySQL und SQLite. |
| Z-04 | Sichere Nutzung | Keine Datenressource ist ohne ausdrückliche Freigabe sichtbar; Rollen und erlaubte Operationen werden serverseitig geprüft. |
| Z-05 | Stabile V1-Schnittstelle | Clients können gegen eine versionierte, dokumentierte HTTP/JSON-Spezifikation entwickelt werden. |
| Z-06 | Alltagstaugliche Administration | Deployment, Konfigurationsänderung, Diagnose, Wiederherstellung und Update sind dokumentiert und testbar. |
| Z-07 | Begrenzter Umfang | V1 ist eine Data-API-Plattform, kein beliebiger Datenbank-SQL-Proxy, ESB oder universeller Anwendungshost. |

## 3. Priorisierung und Versionsgrenzen

**MUSS**: Bestandteil der V1.0-Abnahme. **SOLL**: fachlich wichtig und nach Möglichkeit in V1.0, darf mit dokumentierter Entscheidung und eigener Nachfolgeplanung verschoben werden. **KANN**: keine Verpflichtung für V1.0. Funktionale MUSS-Anforderungen dürfen nicht stillschweigend durch reine Prototypen ersetzt werden.

**V0 ↔ V1:** V0 verwendet eine klar als vorläufig bezeichnete Schnittstelle (empfohlen /api/v0/). V1 veröffentlicht einen **stabilen eigenen Vertragsraum** (empfohlen /api/v1/). Ein V0-Client darf nicht unangekündigt inkompatible V1-Antworten erhalten. Das Produkt-Release 1.0 und der HTTP-Pfad sind getrennte Begriffe; die konkreten URLs werden im Pflichtenheft festgelegt.

**Ein Datenbankadapter ist eine Implementierung hinter demselben Ressourcenvertrag**, nicht das unkontrollierte Durchreichen beliebiger SQL-Anfragen. Der zweite Adapter muss **denselben API-Vertrag** im dokumentierten, unterstützten Funktionsumfang erfüllen.

## 4. Funktionsanforderungen V1

### V1-F01 – Stabile versionierte REST-/JSON-Verträge

**Priorität:** MUSS  
**Bedarf:** Clients brauchen eine langfristig verlässliche, sprach- und datenbankunabhängige Schnittstelle.

**Erwartete Leistung und Abgrenzung:** V1 definiert konsistente Ressourcenpfade, HTTP-Methoden, Statuscodes, JSON-Hüllen, Datumsformate, Schlüsselbehandlung und Fehlerobjekte. Öffentliche Änderungen am Vertrag werden versioniert, dokumentiert und vor Veröffentlichung auf Abwärtskompatibilität geprüft. Nicht kompatible Erweiterungen müssen einen neuen Vertragsraum nutzen; normale interne Codeänderungen dürfen keine stillen Schnittstellenbrüche verursachen.

**Abnahmekriterium:** Ein Vertragstest kann mit denselben Requests gegen zwei V1-Installationen ausgeführt werden; absichtlich inkompatible Änderungen schlagen in der Kompatibilitätsprüfung fehl.

**Herkunft / Inspiration:** V0-F01/F09; alle Referenzprodukte: REST-API-Konventionen.

### V1-F02 – Ressourcenmodell und Alias-Namen

**Priorität:** MUSS  
**Bedarf:** Anwendungen sollen stabile fachlich neutrale Ressourcennamen verwenden, ohne physische Tabellen- oder Spaltennamen zu kennen.

**Erwartete Leistung und Abgrenzung:** Eine Ressourcendefinition ordnet einem öffentlichen Namen eine freigegebene Datenquelle und die erlaubten Felder zu. Öffentliche Feld-Aliasse sind von SQL-Spaltennamen entkoppelt; Pflichtfelder, Typen, Nullbarkeit, Schlüssel, Defaultverhalten und read-only-Eigenschaften sind beschreibbar. Eine neue Datenbanktabelle erscheint nicht automatisch in der API. V1 ist **konfigurationsgetrieben**; Schema-Introspektion darf Vorschläge machen, aber nichts von selbst publizieren.

**Abnahmekriterium:** Nach einer internen Umbenennung einer SQL-Spalte kann die öffentliche API – soweit dieselbe Bedeutung erhalten bleibt – unverändert bleiben; unbekannte Spalten und Ressourcen bleiben gesperrt.

**Herkunft / Inspiration:** PHP-CRUD-API §3.1: Table/Column Mapping; DreamFactory §4.1: Introspection.

### V1-F03 – CRUD mit klaren Schreibsemantiken

**Priorität:** MUSS  
**Bedarf:** Die aus V0 bekannten CRUD-Operationen müssen produktionsreif und konsistent funktionieren.

**Erwartete Leistung und Abgrenzung:** List/Read/Create/Replace/Partial Update/Delete erhalten explizite Verträge: Umgang mit fehlenden IDs, nicht erlaubten Feldern, NULL und Defaults, Datenbank-Constraints, Erfolgsstatus und Wiederholung einer Anfrage ist dokumentiert. Schreiboperationen sind innerhalb eines Requests atomar; fehlerhafte Änderungen dürfen keine Teiländerung eines einzelnen Geschäftsobjekts zurücklassen. V1 verhindert willkürliches Update/Delete ohne eindeutig adressiertes Ziel.

**Abnahmekriterium:** Standard-CRUD-Vertragstests bestehen auf beiden verpflichtenden Datenbankadaptern und zeigen in Fehlerfällen konsistente Resultate.

**Herkunft / Inspiration:** V0-F02 bis F05; PHP-CRUD-API §3.2, DB2Rest §5.2.

### V1-F04 – Validierung und normalisierte Datentypen

**Priorität:** MUSS  
**Bedarf:** Clientfehler sollen vor einer irreversiblen Datenbankaktion erkannt und verständlich gemeldet werden.

**Erwartete Leistung und Abgrenzung:** Ressourcen können einfache Regeln für Pflichtangaben, Typ, Wertebereich, Format, Länge, NULL und schreibgeschützte Felder festlegen. Fehlerantworten nennen ein maschinenlesbares Feld und eine Fehlerkategorie, ohne Datenbankdetails preiszugeben. Soweit ein zweiter Adapter einen abweichenden SQL-Typ nutzt, bleibt das externe JSON-Verhalten konsistent. Sondertypen und komplexe Schema-Sprachen werden nicht generell verlangt.

**Abnahmekriterium:** Dieselbe bewusst fehlerhafte Eingabe erzeugt auf beiden Backends gleichartige Validierungsfehler und keine persistente Änderung.

**Herkunft / Inspiration:** PHP-CRUD-API §3.4: Validation; DB2Rest §5.1: Dialektabstraktion.

### V1-F05 – Erweiterte, begrenzte Filter

**Priorität:** MUSS  
**Bedarf:** Benutzeroberflächen benötigen mehr als reine Gleichheitsfilter, ohne selbst SQL zu senden.

**Erwartete Leistung und Abgrenzung:** Auf explizit freigegebenen Feldern werden mindestens eq, ne, lt, lte, gt, gte, IN, NULL-Prüfungen und einfache textuelle Teil-/Präfixsuche unterstützt, soweit der Feldtyp dies zulässt. Mehrere Bedingungen können mit AND und kontrolliertem OR kombiniert werden. Die Anzahl von Bedingungen, OR-Gruppen, Operatoren und die Länge von Filterwerten sind begrenzt. Ungültige oder nicht autorisierte Abfragen werden abgewiesen. Die Syntax wird dokumentiert; die genaue DSL wird im Pflichtenheft ausgewählt.

**Abnahmekriterium:** Filtertests mit Zahlen, Text, NULL, AND und OR liefern auf beiden Backends semantisch vergleichbare Ergebnisse; nicht erlaubte Operatoren und Felder werden abgewiesen.

**Herkunft / Inspiration:** PHP-CRUD-API §3.3: Filters; DB2Rest §5.3: RSQL.

### V1-F06 – Pagination, Sortierung und Feldprojektion

**Priorität:** MUSS  
**Bedarf:** Clients sollen große Listen sicher und bandbreitenschonend anzeigen können.

**Erwartete Leistung und Abgrenzung:** Es gibt stabile Standardreihenfolgen, Mehrspalten-Sortierung mit eindeutiger Tie-Breaker-Regel, maximale Ergebnismengen und dokumentierte Pagination-Parameter. Clients können **nur explizit lesbare** Ergebnisfelder auswählen. Der Server bietet bei Bedarf eine definierte Gesamtzahl oder klar dokumentiert nur ein hasMore-Kennzeichen. Die verwendete Strategie (Offset oder Cursor) darf nicht zu irreführenden Garantien bei gleichzeitigen Änderungen führen.

**Abnahmekriterium:** Bei Testdaten über mehrere Seiten bleiben Seiten reproduzierbar sortiert, die maximale Seitengröße gilt auch bei manipulierten Parametern, verbotene Spalten erscheinen nie in Antworten.

**Herkunft / Inspiration:** PHP-CRUD-API §3.3; DreamFactory §4.1; DB2Rest §5.3.

### V1-F07 – Deklarierte Beziehungen und begrenzte verknüpfte Lesezugriffe

**Priorität:** MUSS  
**Bedarf:** Clients sollen häufig benötigte Zusammenhänge lesen können, ohne eine unkontrollierte Join-Sprache zu erhalten.

**Erwartete Leistung und Abgrenzung:** Beziehungen zwischen freigegebenen Ressourcen können in einer Konfiguration beschrieben oder kontrolliert aus Foreign Keys übernommen werden. Mindestens **eine Stufe** von read-only-Relationen (beispielsweise Buch → Autor oder Autor → Bücher) kann optional eingebettet werden. Der Server begrenzt Join-/Expand-Tiefe, Menge und Abfragekosten; er prüft Berechtigungen auf **allen** beteiligten Ressourcen. Cross-Database-Joins gehören nicht zum Pflichtumfang.

**Abnahmekriterium:** Ein deklarierter Relationstest liefert nur genehmigte Felder; eine unerlaubte Beziehung oder zu große Expansion wird abgewiesen bzw. nach klarer Regel begrenzt.

**Herkunft / Inspiration:** PHP-CRUD-API §3.3: Joins/Join Limits; DreamFactory §4.1: Relationships; DB2Rest §5.3: Joins.

### V1-F08 – Lesende Views als API-Ressourcen

**Priorität:** MUSS  
**Bedarf:** Ein Betreiber soll freigegebene Datenbankansichten verwenden können, um externe Datenmodelle unabhängig von Tabellenstrukturen zu gestalten.

**Erwartete Leistung und Abgrenzung:** SQL-Views können als **schreibgeschützte** Ressourcen konfiguriert und über dieselben Lese-/Filter-/Sortierkonventionen angesprochen werden, soweit die zugrunde liegenden Spalten dies erlauben. Schreibende Operationen auf Views bleiben außerhalb von V1, selbst wenn die Datenbank technisch schreibbare Views ermöglicht.

**Abnahmekriterium:** Eine als read-only definierte View kann gelesen, aber nicht per POST/PATCH/PUT/DELETE verändert werden; Daten aus nicht explizit freigegebenen Views bleiben unerreichbar.

**Herkunft / Inspiration:** DreamFactory §4.1 und DB2Rest §5.1: Views.

### V1-F09 – Kontrollierte benannte Leseabfragen

**Priorität:** SOLL  
**Bedarf:** Einige Anwendungsberichte lassen sich mit standardisierten CRUD-Filtern nicht ausreichend ausdrücken.

**Erwartete Leistung und Abgrenzung:** Der Betreiber kann **vordefinierte, benannte, schreibgeschützte** Abfragen mit typisierten Parametern als separate Ressourcen anbieten. Clients wählen nur einen freigegebenen Namen und liefern Werte; es gibt keine freie SQL-Expression-Schnittstelle. Jede Query unterliegt Berechtigungen, Ergebnislimits und Tests. Bei Verschiebung wird diese Fähigkeit nicht durch unsichere clientseitige Roh-SQL-Übergabe ersetzt.

**Abnahmekriterium:** Ein Beispielreport mit Parameter und Paging funktioniert; ein Versuch, SQL-Text als Parameter einzuschleusen, verändert die Abfragestruktur nicht.

**Herkunft / Inspiration:** DB2Rest §5.4: Named SQL; PHP-CRUD-API §3.5: Custom Controllers.

### V1-F10 – Zwei austauschbare Datenbankadapter

**Priorität:** MUSS  
**Bedarf:** Der Server soll nicht dauerhaft an eine einzige Datenbankfamilie gebunden sein.

**Erwartete Leistung und Abgrenzung:** **MariaDB/MySQL** bleibt erster Pflichtadapter; **SQLite** wird als zweiter Pflichtadapter für kleinere, eigenständige Installationen vorgesehen. Beide liefern denselben **klar dokumentierten gemeinsamen V1-Funktionsumfang** für CRUD, Validierung, Filter, Paging, Schlüssel und Fehler. Der Betreiber wählt den Adapter pro Instanz; ein einzelner Request darf nicht beliebige Verbindungsdaten festlegen. PostgreSQL, Oracle, SQL Server, JSON-Dateien und fremde REST-APIs sind keine V1-Pflichtadapter.

**Abnahmekriterium:** Ein gemeinsames Contract-Testpaket besteht unverändert gegen MariaDB/MySQL und SQLite. Eine Konfigurationsumstellung erfordert keinen Umbau der externen API-Ressourcen, sofern die Datenbank das gleiche logische Schema enthält.

**Herkunft / Inspiration:** DB2Rest §5.1: SQL-Dialekte; PHP-CRUD-API §3.1: PDO/Datenbanken.

### V1-F11 – Getrennte Installationen und reproduzierbare Konfiguration

**Priorität:** MUSS  
**Bedarf:** Mehrere SASD-Anwendungen müssen dieselbe Software zuverlässig mit unterschiedlicher Konfiguration nutzen.

**Erwartete Leistung und Abgrenzung:** Installationen bleiben über URL, Datenbank, Schlüssel, Ressourcen, Policies, Logs und Cache logisch getrennt. Secrets liegen außerhalb der öffentlichen Auslieferung; die Konfiguration ist versions- und schema-validierbar. Gleiche Programmversionen können auf getrennten Installationen parallel laufen. Eine Instanz kann eine einzige konfigurierte primäre Datenquelle verwenden; **mehrere simultan wechselbare Datenbanken je Instanz** gehören nicht zum MUSS-Umfang.

**Abnahmekriterium:** Zwei Installationen mit unterschiedlichem Backend (MariaDB/SQLite) und verschiedenen Rechten lassen sich reproduzierbar starten; Daten- und Schlüsselgrenzen werden durch Tests bestätigt.

**Herkunft / Inspiration:** V0-F10; DreamFactory §4.1: multiple sources (als weiterführendes Vorbild).

### V1-F12 – API-Schlüssel-Lebenszyklus und technische Identitäten

**Priorität:** MUSS  
**Bedarf:** Ein Betreiber soll aufrufende technische Anwendungen unterscheiden und ihre Schlüssel widerrufen können.

**Erwartete Leistung und Abgrenzung:** Pro Instanz werden mehrere identifizierbare Clients/Schlüssel unterstützt. Schlüssel besitzen mindestens Kennung, sicheren Geheimniswert, Aktiv-/Sperrstatus und zugewiesene Berechtigungsrolle; Schlüssel können rotiert und widerrufen werden, ohne den gesamten Server neu zu programmieren. Schlüssel dürfen in Ruhe nicht ungeschützt als Klartext in Protokollen oder öffentlich sichtbaren Konfigurationsdateien landen. Die konkrete sichere Ablage ist Betriebs- und Architekturentscheidung. Ein API-Schlüssel in öffentlich ausgeliefertem Browsercode ist weiterhin **kein sicher verborgenes Geheimnis**.

**Abnahmekriterium:** Zwei Schlüssel lassen sich unterschiedlichen Rollen zuordnen; das Sperren eines Schlüssels beendet dessen Zugriff, während der andere aktiv bleibt; Logs/Responses verraten keine Schlüssel.

**Herkunft / Inspiration:** PHP-CRUD-API §3.4: apiKeyAuth/apiKeyDbAuth; DreamFactory §4.2.

### V1-F13 – Rollenbasierte Ressourcen- und Methodenberechtigungen

**Priorität:** MUSS  
**Bedarf:** Nicht jeder Client darf alle erlaubten Daten lesen und bearbeiten.

**Erwartete Leistung und Abgrenzung:** Konfigurierbare **Rollen** bestimmen pro API-Ressource die erlaubten Methoden, optional freigegebene Spalten und die Nutzung deklarierter Beziehungen oder benannter Queries. Der Grundsatz **deny by default** bleibt bestehen; nicht zugewiesene Rollen haben keine Rechte. Rollen gelten für authentifizierte technische Clients; öffentliche Nutzerregistrierung, Passwort-Reset und Social Login sind keine V1-Voraussetzungen.

**Abnahmekriterium:** Ein Read-only-Schlüssel kann lesen, nicht schreiben; ein Editor kann erlaubte Felder ändern; ein unbekannter Client und eine nicht erlaubte Route erhalten keine Daten.

**Herkunft / Inspiration:** DreamFactory §4.2: RBAC; PHP-CRUD-API §3.4: authorization.

### V1-F14 – Konfigurierbare Datensatz-Sichtgrenzen

**Priorität:** SOLL  
**Bedarf:** Bei gemeinsam gespeicherten Daten müssen berechtigte Clients unter Umständen nur ihren erlaubten Datenbereich sehen.

**Erwartete Leistung und Abgrenzung:** Für explizit konfigurierte Ressourcen können einfache, **serverseitig erzwungene** Datensatzregeln auf Basis der authentifizierten Clientidentität oder fest zugewiesener Kontextmerkmale hinterlegt werden, zum Beispiel owner_id oder eine fest konfigurierte Organisationskennung. Solche Regeln wirken auf Lesen, Ändern, Löschen, Filter, Joins und Zählungen gleichermaßen. Der Client darf seine eigene Berechtigung nicht per frei wählbarem Query-Parameter erhöhen. **Vollständige Multi-Tenancy mit dynamischer Benutzerverwaltung, Datenbank-pro-Mandant und cross-tenant Administration** ist ausgeschlossen.

**Abnahmekriterium:** Ein Client A sieht und ändert keine Daten eines Client B, auch nicht über Zählungen, Beziehungen und fehlerhafte Abfrageparameter; Zugriffe mit fehlendem Kontext werden abgewiesen.

**Herkunft / Inspiration:** PHP-CRUD-API §3.4: MultiTenancy; DreamFactory §4.2.

### V1-F15 – Konsistentes Schreib-, Konflikt- und Transaktionsverhalten

**Priorität:** MUSS  
**Bedarf:** Schreiboperationen dürfen bei Fehlern oder konkurrierenden Requests keine stillen Datenverluste erzeugen.

**Erwartete Leistung und Abgrenzung:** Jeder einzelne Schreibrequest erhält eine klare Transaktionsgrenze; Constraints werden nicht umgangen. Der API-Vertrag unterscheidet zumindest Not Found, Validierungsfehler, Konflikt und internes Problem. Der Server darf keine Erfolge bestätigen, bevor die Datenbankoperation tatsächlich erfolgreich abgeschlossen wurde. Freie Transaktionen über mehrere HTTP-Requests sowie Distributed Transactions sind kein V1-Bestandteil.

**Abnahmekriterium:** Eine künstlich abgebrochene Mehrfeldänderung hinterlässt keinen unvollständigen Datensatz; referenzielle Konflikte erzeugen eine kontrollierte 4xx-Antwort und keine inkorrekte Erfolgsmeldung.

**Herkunft / Inspiration:** V0-F03–F05; DB2Rest §5.2: Transaktionsinfrastruktur.

### V1-F16 – Optimistische Konflikterkennung

**Priorität:** SOLL  
**Bedarf:** Unbemerkte Überschreibungen durch parallel arbeitende Clients sollen vermieden werden.

**Erwartete Leistung und Abgrenzung:** Für geeignete Ressourcen kann eine Versionskennung beziehungsweise Änderungsmarke vom Client bei Updates mitgesendet werden. Stimmt sie nicht mit dem aktuellen Stand überein, wird der Schreibvorgang zurückgewiesen und die Konkurrenzsituation verständlich signalisiert. Diese Fähigkeit darf nur auf Ressourcen aktiviert werden, die einen zuverlässig gepflegten Versionswert besitzen.

**Abnahmekriterium:** Zwei Clients lesen dieselbe Version; nach Änderung durch A wird der veraltete Schreibversuch von B erkannt und verworfen.

**Herkunft / Inspiration:** Aus dem SASD-Bedarf abgeleitete Qualitätsfunktion; V0-Konfliktbehandlung.

### V1-F17 – Anfrage- und Antwortlimits, Missbrauchsschutz

**Priorität:** MUSS  
**Bedarf:** Eine öffentlich erreichbare API soll auch mit fehlerhaften oder aggressiven Clients kontrollierbar bleiben.

**Erwartete Leistung und Abgrenzung:** Erforderlich sind Limits für Bodygröße, Paging, Sortier-/Filterkomplexität, Relationstiefe und standardmäßig maximale Query-Ergebnisse. Für technische Clientidentitäten ist zusätzlich eine angemessene, konfigurierbare Anfragehäufigkeitsbegrenzung vorgesehen, die auf dem Zielhosting ohne zwingenden Redis-Dienst funktioniert. Die genaue Verteilung auf Webserver und Anwendung wird im Pflichtenheft festgelegt.

**Abnahmekriterium:** Überhöhte Payloads und Abfragebudgets werden kontrolliert abgewiesen; ein Client kann nicht unbegrenzt Daten abrufen oder durch manipulierbare Parameter Serverressourcen dominieren.

**Herkunft / Inspiration:** PHP-CRUD-API §3.4: Page/Join Limits; DreamFactory §4.2: Rate Limiting.

### V1-F18 – HTTPS, Browsergrenzen und sichere Betriebsdefaults

**Priorität:** MUSS  
**Bedarf:** Authentifizierte Datenübertragung muss auf produktiven Installationen verlässlich geschützt werden.

**Erwartete Leistung und Abgrenzung:** HTTPS und vertrauenswürdige Proxykonfiguration sind erforderlich; Debug-Ausgaben werden in Produktion unterdrückt. Browser-CORS wird nur für konfigurierte Ursprünge erlaubt. V1 nutzt im Kern headerbasierte technische Clientauthentifizierung; Cookie-Sessions und dadurch benötigter CSRF-Schutz sind nicht automatisch enthalten. Sicherheitsrelevante Fehlkonfigurationen werden möglichst früh erkannt und verständlich gemeldet, ohne Secrets auszugeben.

**Abnahmekriterium:** HTTP liefert keine vertraulichen Nutzdaten; ein nicht erlaubter Origin erhält keine entsprechende Browserfreigabe; Debug/Stacktrace bleiben verborgen.

**Herkunft / Inspiration:** PHP-CRUD-API §3.4: sslRedirect/cors; DreamFactory §4.2.

### V1-F19 – Strukturierte technische Logs und gezieltes Audit

**Priorität:** MUSS  
**Bedarf:** Betreiber müssen nachvollziehen können, was mit dem Server geschieht und welche schreibenden Aktionen ausgeführt wurden.

**Erwartete Leistung und Abgrenzung:** Technische Logs enthalten mindestens Request-ID, Zeitpunkt in UTC, Instanz, Ressource, Operation, Status, Fehlerklasse und technische Clientkennung, soweit datenschutzrechtlich zulässig. **Für schreibende Aktionen** werden nachvollziehbare Auditereignisse erzeugt (wer, wann, was, welches logische Objekt, Ergebnis); vollständige personenbezogene Inhalte, Tokens oder Passwörter werden nicht pauschal geloggt. Logrotation, Aufbewahrung und Zugriffsschutz werden dokumentiert. Ein vollwertiger revisionssicherer Audit-Tresor wird nicht behauptet.

**Abnahmekriterium:** Eine erfolgreiche Änderung und eine verweigerte Änderung lassen sich anhand geschützter Ereignisse zuordnen, ohne Klartextschlüssel oder ganze Datensätze in Logs zu finden.

**Herkunft / Inspiration:** DreamFactory §4.2: Audit Logging (ggf. kommerziell); DB2Rest §5.5: Observability.

### V1-F20 – Health, Readiness und Diagnosen

**Priorität:** MUSS  
**Bedarf:** Betreiber sollen Störungen zügig erkennen und abgrenzen können.

**Erwartete Leistung und Abgrenzung:** Es gibt einen knappen öffentlichen oder anderweitig geschützten Health-Endpunkt und eine **geschützte** Readiness-/Diagnosesicht für die konfigurierte Datenbank und wichtige abhängige Komponenten. Öffentliche Ausgaben sind auf nicht sensitive Statusinformationen begrenzt; Version, Schema, Pfade und Verbindungsdetails werden nicht ungeprüft offengelegt. Diagnosen sollen bei Shared Hosting auch ohne Prometheus/Grafana nutzbar sein.

**Abnahmekriterium:** Bei absichtlich unterbrochener DB-Verbindung zeigt der geschützte Readiness-Test einen Fehler, ohne Zugangsdaten preiszugeben; die öffentliche Health-Antwort verrät keine interne Topologie.

**Herkunft / Inspiration:** DB2Rest §5.5: Actuator Health; PHP-CRUD-API §3.2: Status.

### V1-F21 – Automatisch ableitbare OpenAPI-Dokumentation

**Priorität:** MUSS  
**Bedarf:** Cliententwickler benötigen eine überprüfbare, maschinenlesbare Beschreibung der bereitgestellten Schnittstelle.

**Erwartete Leistung und Abgrenzung:** Der Server kann für freigegebene Ressourcen einen OpenAPI-Vertrag mit Pfaden, Methoden, Parametern, JSON-Schemas, Authentifizierung und typischen Fehlerantworten erzeugen oder bereitstellen. Die Dokumentation darf keine nicht freigegebenen Ressourcen als nutzbare öffentliche Endpunkte versprechen. Die dokumentierte API-Version muss mit dem Releasevertrag übereinstimmen. Eine eigene Swagger-Weboberfläche ist optional und nicht notwendig.

**Abnahmekriterium:** Ein OpenAPI-Validator akzeptiert das Dokument; die generierten Endpunkte stimmen mit automatisierten Contract-Tests überein und veröffentlichen keine geheimen Ressourcendetails.

**Herkunft / Inspiration:** PHP-CRUD-API §3.5: OpenAPI; DreamFactory §4.1: Swagger/OpenAPI.

### V1-F22 – Kontrollierte Erweiterungspunkte

**Priorität:** SOLL  
**Bedarf:** Einige Anwendungen werden zusätzliche, fachlich sinnvolle HTTP-Operationen benötigen.

**Erwartete Leistung und Abgrenzung:** Es wird ein dokumentierter Mechanismus für zusätzliche **explizit registrierte** Controller/Handler oder Leseabfragen vorgesehen, ohne Kernrouting, Authentifizierung und Logging zu kopieren. Erweiterungen unterliegen denselben Sicherheits- und Fehlerregeln. Das Ziel ist eine kleine, kontrollierbare Erweiterungsschicht – keine beliebige Laufzeitskriptausführung, kein öffentlicher SQL-Interpreter und kein großflächiges Plugin-Marktplatzsystem.

**Abnahmekriterium:** Eine einfache Beispielerweiterung ist nur unter ihrem konfigurierten Pfad und mit passender Rolle erreichbar, nutzt die Standardfehlerbehandlung und bleibt von Basis-CRUD isoliert.

**Herkunft / Inspiration:** PHP-CRUD-API §3.5: Custom Controllers/Middleware; DreamFactory §4.3: Custom Services.

### V1-F23 – Konfiguration validieren, exportieren und sicher aktualisieren

**Priorität:** MUSS  
**Bedarf:** Fehlerhafte Konfigurationen sollen nicht zu unbemerkter Freigabe von Daten oder verlorenen Instanzeinstellungen führen.

**Erwartete Leistung und Abgrenzung:** Konfigurationsversion, unterstützte Einstellungen, Pflichtwerte und unbekannte Optionen werden vor beziehungsweise beim Start der Instanz geprüft. Ungültige Ressourcenzuordnungen und Sicherheitsregeln verursachen eine sichere, verständliche Fehlermeldung statt eines permissiven Fallbacks. Nicht geheime Konfiguration ist exportierbar und versionierbar; Geheimnisse werden getrennt verwaltet. Ältere V0-Einstellungen erhalten dokumentierte Migrationsregeln.

**Abnahmekriterium:** Eine absichtlich fehlerhafte Konfiguration wird nicht stillschweigend übernommen; ein dokumentierter Migrationsfall lässt sich nachvollziehbar durchführen und bei Fehlversuch rückgängig machen.

**Herkunft / Inspiration:** DreamFactory §4.4: Config Migration; PHP-CRUD-API §3.1: Configuration.

### V1-F24 – Installation, Update, Betrieb und Wiederherstellung

**Priorität:** MUSS  
**Bedarf:** Ein Open-Source-Nutzer muss V1 sicher installieren, aktualisieren und bei Störungen wiederherstellen können.

**Erwartete Leistung und Abgrenzung:** V1 bietet eine dokumentierte Installation auf unterstütztem PHP-Hosting ohne Docker-/Redis-Pflicht; der index.php-Einstieg im Webroot bleibt möglich. Dokumentiert werden PHP-Erweiterungen, Datenbankberechtigungen, HTTPS, Routing-Fallback, Logverzeichnis, Konfigurationssicherung, Update-/Rollback-Reihenfolge sowie Betreiberaufgaben für DB-Backups und Restore-Tests. Der Server muss nicht selbst einen universellen Backup-Scheduler implementieren.

**Abnahmekriterium:** Eine frische V1-Installation und eine dokumentierte V0→V1-Migration werden nachvollziehbar vorgeführt; Konfigurationsbackup und Datenbank-Restore im Testsystem funktionieren.

**Herkunft / Inspiration:** V0: Shared Hosting; DreamFactory §4.4: Operations und Migration.

### V1-F25 – Spracheunabhängige Client-Nutzung

**Priorität:** MUSS  
**Bedarf:** Desktop-, Web- und Serverprogramme sollen denselben Dienst nutzen können.

**Erwartete Leistung und Abgrenzung:** Alle öffentlich relevanten Funktionen basieren auf standardisiertem HTTP/JSON, UTF-8, eindeutig definierten Datentypen und dokumentierter Fehlerstruktur. Die API darf keine PHP-Sessions oder serverseitige Programmiersprachenkenntnis voraussetzen. Beispiele für mindestens **C#/.NET**, **Java** und einen einfachen curl-Client zeigen identische Ressourcenzugriffe; das sind Dokumentationsbeispiele, keine vollständigen SDKs.

**Abnahmekriterium:** Ein C#-, Java- und curl-Beispiel kann dieselbe Testressource mit derselben Authentifizierungsmethode lesen; Antworten entsprechen dem dokumentierten Vertrag.

**Herkunft / Inspiration:** SASD-Produktanforderung: paralleler Einsatz in unterschiedlichen Anwendungen.

### V1-F26 – Automatisierte Tests, Security- und Kompatibilitäts-Gates

**Priorität:** MUSS  
**Bedarf:** V1-Änderungen sollen nicht unbemerkt Sicherheitslücken oder Datenbankabhängigkeiten einführen.

**Erwartete Leistung und Abgrenzung:** Verpflichtend sind wiederholbare CRUD-/Filter-/Relation-/Rollen-/Rate-Limit-/Audit-/Fehler-/Konfigurations-/Health-Tests sowie **gemeinsame Contract-Tests** für MariaDB/MySQL und SQLite. Negative Tests decken unzulässige Ressourcen, fehlende Autorisierung, SQL-Injection, Datensatz-Sichtgrenzen (wenn freigegeben), schädliche Größen und verwundbare Routingvarianten ab. Der Releaseprozess dokumentiert bestandene Tests, offene Risiken und Abweichungen.

**Abnahmekriterium:** Die gesamte definierte V1-MUSS-Testsuite besteht auf den beiden Backends; ein absichtlich eingeführter Berechtigungsfehler führt zu einem fehlgeschlagenen Security-Test.

**Herkunft / Inspiration:** Referenzkatalog §9/10: Prüfverfahren; V0-F17.

### V1-F27 – Betriebskennzahlen und Leistungsnachweis

**Priorität:** SOLL  
**Bedarf:** Betreiber sollen Last, Antwortverhalten und Datenbankkosten einschätzen können.

**Erwartete Leistung und Abgrenzung:** Der Server erhebt nachvollziehbare, datensparsame Kennzahlen wie Anfragezahl, Fehlerkategorien und Antwortzeiten, ohne sensible Payloads zu sammeln. Für ein repräsentatives Testprofil werden Messwerte und Datenvolumen dokumentiert. Exporte im Prometheus-Format oder externe Plattformen wie Grafana/Datadog bleiben optional; die Kernanwendung muss ohne diese Dienste laufen.

**Abnahmekriterium:** Ein reproduzierbarer Performance-Bericht und Basisstatistiken werden mit mindestens einer kleinen und einer größeren Testdatenmenge dokumentiert.

**Herkunft / Inspiration:** DB2Rest §5.5: Metrics; DreamFactory §4.4: Usage Reporting.

## 5. V1-Schnittstellen und erwartete fachliche Szenarien

### 5.1 Szenario S1 – mehrere eigenständige SASD-Produkte

Für HARALD wird eine eigene Installation mit freigegebenen Ressourcen etwa für Medien und Exemplare betrieben. Für eine andere SASD-Anwendung existiert unter anderer URL eine unabhängige Installation mit anderem DB-Backend und eigenen Schlüssel-/Rollenregeln. Beide Installationen verwenden dieselbe Server-Software und dieselben API-Konventionen; sie teilen jedoch **keine** Daten- oder Authentifizierungsgrenze.

### 5.2 Szenario S2 – kontrolliertes Lesen und Bearbeiten

Ein lesender Client darf nur explizit freigegebene Ressourcen anzeigen und filtert Ergebnisse serverseitig. Ein autorisierter Bearbeiter darf definierte Felder ändern. Ein ungültiger API-Key, eine nicht genehmigte Methode oder eine verbotene Spalte führt zu einer kontrollierten Ablehnung. Sicherheitsprüfungen erfolgen im Server, niemals nur in der Oberfläche.

### 5.3 Szenario S3 – Datenbankwechsel ohne neuen API-Vertrag

Eine kleine lokale Anwendung setzt SQLite ein, während eine gehostete Anwendung MariaDB verwendet. Die Ressourcen haben logisch dieselben Namen und Datentypen; der HTTP-Client muss daher keine datenbankspezifischen SQL-Besonderheiten kennen. **Datenmigration zwischen diesen Datenbanken wird nicht automatisch durch den Adapterwechsel erledigt.**

### 5.4 Szenario S4 – kontrollierte Beziehungen

Eine Ressource mit einem bekannten Fremdschlüssel kann eine freigegebene Relation in der Antwort einbetten. Die Menge und Tiefe werden beschränkt. Es bleibt ausgeschlossen, mit einer frei wählbaren Join-Anweisung alle Tabellen einer Datenbank zu durchsuchen.

### 5.5 Szenario S5 – Betrieb und Störungsbehebung

Eine Instanz meldet einen Datenbankausfall über einen geschützten Readiness-Test; technische Logs dokumentieren Zeit, Fehlerart und Request-ID, aber keine Credentials. Ein Betreiber kann anhand der Dokumentation den Datenbankzugang prüfen, Konfiguration wiederherstellen und den Dienst erneut freigeben.

## 6. Nichtfunktionale Anforderungen

| ID | Bereich | Erwartung / Nachweis |
|---|---|---|
| V1-N01 | Verständlichkeit | Dokumentierte Beispiele und Fehler, konsistente Ressourcennamen und keine unvorhersehbaren API-Nebeneffekte. |
| V1-N02 | Sicherheit | Deny-by-default, Least-Privilege-Datenbankrechte, getrennte Instanzen, sichere Schlüsselrotation, parametrisierte SQL-Werte, erlaubte Identifiers, HTTPS, Missbrauchsgrenzen und geschützte Diagnose. |
| V1-N03 | Wartbarkeit | Klare Verantwortungsgrenzen zwischen HTTP, API-Vertrag, Autorisierung, Geschäftsregeln, Repository und Storage-Adapter. Externe Erweiterungspunkte dürfen den Kern nicht ungeordnet durchdringen. |
| V1-N04 | Portabilität | PHP-8.4-basierter Betrieb auf dokumentiertem Linux-Webhosting sowie Entwicklungs-/Testbetrieb unter Windows/WSL. Die konkreten Minimum-PHP-Versionen und Erweiterungen sind in der Release-Dokumentation festzuhalten. |
| V1-N05 | Datenintegrität | Atomare einzelne Schreibvorgänge, respektierte Constraints und kontrollierte Schreibkonflikte. Ausdrücklich keine garantierten Distributed Transactions. |
| V1-N06 | Verfügbarkeit | Störungen werden ohne Offenlegung interner Details erkennbar; ein einzelner Client kann nicht unbegrenzt teure Abfragen erzwingen. Keine unbelegte Hochverfügbarkeits- oder SLA-Garantie. |
| V1-N07 | Leistungsfähigkeit | Reproduzierbare Testprofile für definierte Datensätze und Datenmengen, dokumentierte Beobachtung von Antwortzeit und Speicherbedarf, verbindliche Anfrage-/Ergebnislimits. Konkrete Grenzwerte werden vor Abnahme im Pflichtenheft festgelegt und gemessen. |
| V1-N08 | Testbarkeit | Automatisierbare Contract- und Security-Tests auf beiden Datenbankadaptern; reproduzierbare Installationen mit Testdaten statt Produktionsdaten. |
| V1-N09 | Dokumentation | Deutsche Hauptdokumentation; API-Vertrag, Beispiele und Installations-/Betriebsschritte so formuliert, dass auch externe Open-Source-Beitragende die Schnittstelle nachvollziehen können. Englische Übersetzungen sind erwünscht, jedoch gesondert freizugeben. |
| V1-N10 | Datenschutz | Datenminimierung, gezielte Datensatz-/Spaltenberechtigungen, sparsame Log- und Auditdaten, dokumentierte Lösch- und Aufbewahrungsaufgaben. Fachliche Verantwortung und rechtliche Prüfung verbleiben bei der Anwendung bzw. dem Betreiber. |
| V1-N11 | Offenheit | Das SASD-Kernprodukt soll ohne proprietäre Laufzeitabhängigkeit nutzbar bleiben. Fremdmodule müssen lizenzrechtlich geprüft werden; durch DreamFactory beworbene Enterprise-Funktionen dürfen nicht als frei verfügbar vorausgesetzt werden. |
| V1-N12 | Konfigurationssicherheit | Ungültige Einstellungen führen zum sicheren Abbruch beziehungsweise zur Nichtfreigabe, nicht zum automatischen Einschalten weitreichender Zugriffsrechte. |

## 7. Architekturleitplanken ohne Vorwegnahme des Pflichtenhefts

1. **Trennung des öffentlichen Vertrages von SQL und Datenbankdialekt.** Der Client kennt Ressourcen, keine internen Tabellen-/Verbindungsdetails.
2. **Konfiguration vor automatischer Datenbankfreigabe.** Schema-Metadaten dürfen einen Vorschlag unterstützen, sind aber niemals die Berechtigungsquelle.
3. **Einheitliche Sicherheitskontrollen für alle Endpunkte.** Erweiterungen, Views und Relationen dürfen sich nicht an Authentifizierung, Rollen, Limits und Audit vorbeischleichen.
4. **Anwendungslogik bleibt Anwendungslogik.** Die API stellt Datenoperationen bereit; z. B. ein Buchausleihvorgang umfasst Fachregeln, die nicht durch generisches DELETE/PUT ersetzt werden.
5. **Adapterfähigkeit mit kleinem ersten Schritt.** Genau zwei Pflichtadapter in V1: MariaDB/MySQL und SQLite; zusätzliche Adapter sind Erweiterungsoptionen.
6. **Shared Hosting bleibt Primärziel.** Kein obligatorisches Redis, Docker, Cron, Queue-System oder dauerhaft laufender Java-Prozess. Zusätzliche Betriebsplattformen sind möglich, aber keine Freigabevoraussetzung.
7. **Keine Ausführung fremder SQL- oder Skripttexte aus Requests.** Benannte Abfragen und Erweiterungen sind ausschließlich aus kontrollierter Betreiberkonfiguration zugänglich.

## 8. Was ausdrücklich nicht in V1.0 enthalten ist

| Ausgeschlossene Funktionsfamilie | Warum nicht in V1? | Perspektive |
|---|---|---|
| Allgemeines Java/Jakarta-EE-Application-Hosting wie WildFly/WebLogic | Andere technische Produktkategorie als eine Daten-REST-API. | Nicht Ziel des Produkts |
| Webbasierte vollständige No-Code-Administrationskonsole | Hoher UI-, Auth- und Wartungsumfang. | Eigenes späteres Teilprojekt |
| OAuth2/OIDC/SAML/LDAP-SSO, User Self-Service und Passwort-Reset | Benötigt ein umfassendes Identitäts- und Benutzerkonzept. | Nach konkretem Integrationsbedarf |
| Vollständige Multi-Tenancy mit Datenbank-pro-Mandant oder Wechsel der Mandanten-DB pro Request | Komplexe Isolation, Pooling und Betriebssicherheit. | Eigenes Architekturvorhaben |
| Gleichzeitiger Multi-DB-Mixer in einer einzelnen Instanz, Cross-DB-Joins | Zu komplex für die erste stabile API-Version. | Nach konkretem Bedarf |
| PostgreSQL, Oracle, SQL Server und NoSQL-Adapter | V1 testet zwei Datenbankadapter vollständig statt viele oberflächlich. | Spätere Releases |
| JSON-Dateispeicher als universelles Transaktions-Backend | Sperren, konkurrierende Änderungen und Recovery unterscheiden sich stark von SQL. | Separate Adapterstudie |
| GraphQL, generisches RSQL über beliebige SQL-Dialekte, beliebige SQL-Funktionen | Erhöht Angriffsfläche und Aufwand bei Query-Plänen. | Nur mit belastbarem Bedarf |
| Vollwertige Massenschreib-/Massenlöschoperationen und Multi-Request-Transaktionen | Hoher Schutz- und Fehlermanagementaufwand. | Nach spezifizierten Use Cases |
| Datenbankschema-Schreibzugriff per Internet-API | Sehr hohes Sicherheitsrisiko. | Explizit nicht Kernziel |
| SOAP-zu-REST, FTP/SFTP, S3, Mail, Push, allgemeine Webhooks | Integrations- statt Daten-API-Aufgaben. | Separate Dienste/Module |
| GIS/GeoJSON, ML/MCP-Toolserver | Spezialisierte Nutzung, nicht notwendig für Kern-CRUD. | Separater Bedarf / Forschungsfeld |
| Native GUI, vollständig ausgelieferte .NET-/Java-SDKs | V1 ist server- und protokollzentriert. | SDKs ggf. nach Bedarf |
| Eigene plattformübergreifende Backup-Engine | Datenbank- und Betreiberverantwortung; Recovery-Prozess wird dokumentiert. | Externe Tools + Runbooks |
| Grafana, Prometheus-Server, Datadog und Cluster-Orchestrierung | Betriebsoptionen, keine Voraussetzung für PHP-Shared-Hosting. | Optionales Monitoringpaket |

## 9. Abnahmekatalog V1.0

**Freigaberegel:** V0-MUSS-Basis weiterhin erfüllt **und** sämtliche V1-MUSS-Anforderungen erfolgreich nachgewiesen. SOLL-Punkte können nur mit ausdrücklich dokumentierter Freigabe und Nachfolgeentscheidung verschoben werden. Datenbanktests werden mindestens auf den zwei verpflichtenden Adaptern ausgeführt.

| Test | Aussage und Prüfumfang | Relevante Anforderungen |
|---|---|---|
| AT-V1-01 | Stabile V1-Pfade, Schema, HTTP-Status und versionierte Fehlerstruktur anhand Contract-Test | F01, F03, F04 |
| AT-V1-02 | Ressourcen- und Feldaliase, allowlistbasierte Freigabe, keine automatische Veröffentlichung neuer Tabellen | F02 |
| AT-V1-03 | Gleiches CRUD- und Validierungsverhalten mit MariaDB/MySQL **und** SQLite | F03, F04, F10 |
| AT-V1-04 | Filteroperatoren, AND/OR, Feldprojektion und begrenzte Pagination, inklusive ungültiger Queries | F05, F06 |
| AT-V1-05 | Eine explizite Relationstiefe, Join-Limits und Berechtigungserhalt für verwandte Ressourcen | F07 |
| AT-V1-06 | Freigegebene View lesen; jede Schreibmethode verweigern | F08 |
| AT-V1-07 | Parallele getrennte Instanzen mit unterschiedlichen DBs, Konfigurationen und Schlüsseln | F10, F11, F12 |
| AT-V1-08 | Schlüsselrotation, Revokation, Rollen für Read und Write; unbekannte Clients blockiert | F12, F13 |
| AT-V1-09 | Atomarer Schreibvorgang, nachvollziehbarer Constraintkonflikt, kontrollierte Fehler | F03, F15 |
| AT-V1-10 | Angriffe mit SQL-Injection, Überlastparametern, übergroßen Bodies und hohem Aufrufvolumen | F04, F05, F17, F18 |
| AT-V1-11 | Strukturierte technische Logs und Audit für zulässige/verweigerte Schreibaktionen, ohne Secrets | F19 |
| AT-V1-12 | Geschützte Readiness bei Datenbankausfall, sicherer öffentlicher Health-Status | F20 |
| AT-V1-13 | Generiertes OpenAPI-Dokument entspricht realen Routen und Auth-Regeln | F21 |
| AT-V1-14 | Fehlerhafte und ältere Konfiguration sicher prüfen/migrieren; Backup- und Restore-Probelauf | F23, F24 |
| AT-V1-15 | curl, Java und C# Beispiele; mehrsprachig gleicher HTTP-Vertrag | F25 |
| AT-V1-16 | Vollständige CI-/lokale Tests auf beiden Adaptern; Security-Negativtests schlagen bei absichtlichem Fehler an | F26 |

### 9.1 Zusätzliche fachliche Freigabeprüfungen

- Ein neuer fachlicher Anwendungsfall kann **durch Konfiguration**, ohne Kopie oder Änderung am Serverkern, seine freigegebenen Ressourcen zur Verfügung stellen.
- Es existiert kein Standardpfad, der automatisch das ganze Datenbankschema, private Tabellen oder technische Zugangsdaten preisgibt.
- Ein öffentlicher Browser-Client wird nicht mit einem hartcodierten Geheimnis fälschlich als sicher ausgegeben.
- Die V0→V1-Einführung enthält dokumentierte Umstellungsschritte für Client-URLs und Konfiguration; ein stiller Bruch laufender V0-Clients ist nicht zulässig.
- Die bekannten Einschränkungen der einzelnen Adapter sind in einer nutzerverständlichen Kompatibilitätsliste dokumentiert.

## 10. Auswahl aus dem Referenzkatalog – bewusst selektiv

| Feature aus den Vorbildern | Entscheidung V1 | Grund | Quelle im Funktionskatalog |
|---|---|---|---|
| Tabellen/Spalten-Mapping und explizite Freigabe | MUSS | Stabilität und sicherer externer Vertrag | PHP-CRUD-API §3.1 |
| Filters, Sort, Pagination, Projection | MUSS | Praxisnutzen und kontrollierte Abfragen | PHP-CRUD-API §3.3; DB2Rest §5.3 |
| Read-only Relationships/Views | MUSS | Häufige relationale Leseanwendungsfälle | DreamFactory §4.1; DB2Rest §5.1/5.3 |
| Mehrere Datenbankdialekte | MUSS, aber nur MariaDB/MySQL und SQLite | Echte Adapterprobe bei begrenztem Aufwand | DB2Rest §5.1 |
| API Keys und feinere rollenbasierte Rechte | MUSS | Sichere technische Mehrclient-Nutzung | DreamFactory §4.2; PHP-CRUD-API §3.4 |
| OpenAPI | MUSS | Vertraglich klar und für Fremdclients nutzbar | PHP-CRUD-API §3.5; DreamFactory §4.1 |
| Health/Logging/Audit | MUSS | Nachvollziehbarkeit und Betrieb | DB2Rest §5.5; DreamFactory §4.2 |
| Named SQL | SOLL (read-only, vorkonfiguriert) | Nützlich für komplexe Berichte, nicht notwendiger Kern | DB2Rest §5.4 |
| Erweiterbare Controller | SOLL (kontrolliert) | Fachspezifische Ergänzungen ohne Fork | PHP-CRUD-API §3.5 |
| Row-level-Sichtgrenzen | SOLL (eng begrenzt) | Fachlich sinnvoll, aber sicherheitskritisch | PHP-CRUD-API §3.4 |
| RSQL, Massenschreiben, freie API-SQL | NICHT V1 | Zu groß beziehungsweise zu riskant | DB2Rest §5.3/5.4 |
| SOAP, E-Mail, Files, S3, MCP, No-Code-Admin | NICHT V1 | Sprengt bewusst die Daten-API-Produktgrenze | DreamFactory §§4.3–4.4 |

## 11. Risiken und offene Entscheidungen

### 11.1 Sicherheitsrisiken

**R-01 Datenexfiltration durch Resource Discovery:** Eine Datenbankstruktur darf nicht automatisch öffentlich sichtbar werden. Jede API-Ressource braucht eine eigene Freigabe.  
**R-02 untrusted SQL-Identifiers:** Werte können parametrisiert werden, Tabellennamen und Sortierspalten jedoch nicht allgemein. Sie müssen aus freigegebenen Metadaten kommen.  
**R-03 verborgene Filter-/Join-Lecks:** Rollen- und gegebenenfalls Zeilengrenzen müssen nicht nur auf Antworten, sondern auch auf Counts, Joins, Fehlerdetails und Suchfunktionen wirken.  
**R-04 Secret Handling:** API-Keys gehören nicht in fremdlesbare Browserpakete, öffentliche Konfigurationsdateien oder Logs.  
**R-05 Access-Logging:** Audit wird datensparsam durchgeführt und nach sachlich definierten Fristen behandelt.

### 11.2 Technische und organisatorische Risiken

**R-06 Heterogene Datenbanktypen:** SQLite und MariaDB unterscheiden sich bei SQL-Syntax, NULL-Sortierung, Boolean-/Datumsdarstellung und Transaktionen. Der gemeinsame unterstützte Vertrag muss ausdrücklich definiert und auf beiden Systemen getestet werden.  
**R-07 Shared Hosting:** Rewrite-, PATH_INFO- und Zugriffsregeln auf dem Zielhost sind verbindlich zu testen; keine stillen Abhängigkeiten von Redis/Docker.  
**R-08 Scope Creep:** 157 Recherchefunktionen sind keine 157 Produktanforderungen. Neue Wünsche erfordern Priorisierung und separate Freigabe.  
**R-09 Kompatibilität:** V0-Konfigurationen und Clients dürfen beim Übergang nicht durch eine unbemerkte Schnittstellenänderung verloren gehen.  
**R-10 Anwendungstransaktionen:** CRUD ersetzt keine durchgängige Fachoperation für Ausleihe, Rückgabe, medizinische Abläufe oder vergleichbare Geschäftsregeln.

### 11.3 Noch im Pflichtenheft zu klären

- Exakte URL-Konventionen und die Semantik von /api/v0/ gegenüber /api/v1/.
- Endgültiger Satz der JSON-Typen und die Abbildung von Datum/Zeit, Dezimalzahl, Boolean und UUID auf MariaDB/SQLite.
- Form, Versionierung und Validierungsregeln der Installations- und Ressourcen-Konfiguration.
- Syntax der erlaubten Filter, OR-Limits und Feldprojektionen sowie Default-Seitengrößen.
- Konkretes Schlüssel- und Policy-Speichermodell, sichere Schlüsselvergleiche und Rotation.
- Methode der OpenAPI-Erzeugung sowie Sichtbarkeit technischer Diagnosen.
- Belastungstestprofil, SLO-/Grenzwerte sowie Entscheidung über passende Rate-Limit-Strategie im Shared Hosting.

## 12. Dokumentation, Änderungen und Freigabe

1. **[Lastenheft V0](./lastenheft-v0.md):** kleine erste Version, deren MUSS-Anforderungen erhalten bleiben.
2. **Dieses Lastenheft V1:** späterer Zielumfang für stabile V1.0; noch kein implementierter Produktstand.
3. **[Referenzkatalog DE](./referenzprodukte-funktionskatalog.md) / [EN](./reference-products-feature-catalog.en.md):** externe Recherche und Ideenquelle, keine vollständige technische Abnahme der Vorbilder.
4. **[Pflichtenheft V1](./pflichtenheft-v1.md) (technischer Entwurf):** technische Architektur, konkrete Formate, REST-Beispiele und Testdesign.
5. **Änderungskontrolle:** Jede zusätzliche Funktion erhält eine ID, Nutzenbegründung, Priorität und Abnahmebeschreibung. Verschobene SOLL-Anforderungen werden ausdrücklich protokolliert.

---

**Dokumentstatus:** Gründlicher, fachlicher Anforderungs**entwurf**. Keine Behauptung einer bestehenden Implementierung, erfolgreichen Testabnahme oder bereits erfolgten Auftraggeberfreigabe.
