# ADR-0020 – Austauschbare Datenbankadapter bei stabilem Daten-API-Vertrag

> **Sprache:** [English (Standard)](0020-pluggable-database-adapters.md) | **Deutsch**  
> **Status:** Vorgeschlagen — **nicht angenommen oder implementiert**  
> **Datum:** 24.11.2026  
> **Geltung:** Spätere Erweiterbarkeit der Persistenzschicht; keine Änderung des verpflichtenden V0-/V1-Umfangs.  
> **Bezüge:** [Architektur V0](../architecture-v0.de.md) · [Architektur V1](../architecture-v1.de.md) · [Roadmap](../../ROADMAP.de.md) · [ADR-Register](README.de.md)

## Ausgangslage

Der SASD API Server bietet eine kleine, konfigurierbare REST/JSON-API für ausdrücklich freigegebene Datenressourcen. Dieselbe Serverversion läuft in unabhängig konfigurierten Installationen. V0 setzt auf PHP 8.4 und MariaDB/MySQL über PDO, V1 zusätzlich auf SQLite. PostgreSQL, Oracle Database, IBM Db2 und Firebird sind interessante spätere Kandidaten. Die Liste soll offen bleiben, ohne Treiberverfügbarkeit, Shared-Hosting-Tauglichkeit oder konkrete Releases zu versprechen.

PDO vereinheitlicht Verbindungen und Statements, **nicht** aber SQL-Dialekte, Datentypen, generierte Schlüssel, Pagination, Transaktionsverhalten oder Constraint-Semantik. Ein bloß austauschbarer DSN ist deshalb kein vollständiger Datenbankadapter.

## Vorgeschlagene Entscheidung

1. **HTTP/JSON-Vertrag, Authentifizierung, Autorisierung, Validierung, Ressourcenrichtlinien, Fehlerabbildung und CRUD-Orchestrierung bleiben im Core** und unabhängig von SQL-Treiber und Dialekt.
2. Ein kleiner **datenbankneutraler RecordRepository-Port** verarbeitet typisierte und geprüfte Operationen/Abfragepläne. Datenbankspezifisches SQL, Identifier-Quoting, Parameterbindung, Schlüsselgenerierung, Typumsetzung und Fehlerübersetzung gehören in den Adapter.
3. Der Bootstrap erhält bei tatsächlichem Bedarf einen **expliziten Registrierungs-/Auswahlpunkt**. Die Provider-ID wird ausschließlich über eine vertrauenswürdige Allowlist installierter und geprüfter Adapter-Factories aufgelöst. Keine Treiberwahl durch HTTP-Clients, kein beliebiges Nachladen von Klassen, keine externen Plug-in-Downloads, kein eval und keine ausführbare Konfiguration.
4. Datenbankadapter gelten als **optionale, vertrauenswürdige Komponenten beim Deployment**. Später sind getrennte Composer-Pakete denkbar. V0 benötigt weder Plugin-Manager noch Laufzeit-Discovery noch öffentliche Administrationsoberfläche.
5. **Fähigkeiten und Grenzen** eines Adapters müssen angegeben und bei Start/Deployment gegen die Konfiguration validiert werden. Fehlende verpflichtende Funktionen dürfen nicht stillschweigend mit schwächerer Semantik ersetzt werden. Fehlkonfigurationen werden sicher blockiert und nur intern nachvollziehbar diagnostiziert.
6. Eine Installation verwendet **eine primäre konfigurierte Datenquelle**. Andere Installationen dürfen andere Adapter einsetzen. Das ist keine Zusage für datenbankübergreifende Joins oder verteilte Transaktionen.
7. **Fachliche API-Module** sind ein anderer Erweiterungspunkt als Datenbankadapter. Adapter definieren keine öffentlichen HTTP-Routen und umgehen niemals die Sicherheitsprüfung des Core.

Dies ist ein **Architekturvorschlag**, keine Freigabe zur Implementierung sämtlicher Adapter. Die nachstehenden Schnittstellennamen bleiben bis zur Überprüfung und zu konkreten Kompatibilitätstests illustrativ.

## Beispielhafte Verträge

- `RecordRepository`: Lesen/Auflisten/Anlegen/Ersetzen/teilweise Ändern/Löschen freigegebener Ressourcen; typisierte Schlüssel sowie festgelegte Not-Found-/Konflikt-Semantik.
- `StorageAdapterFactory`: Erzeugt Repository und Transaktionszugriff aus geprüfter privater Instanzkonfiguration.
- `QueryCompiler` oder `SqlDialect`: Übersetzt eingeschränkte interne Abfragepläne in zulässiges SQL, inklusive Quoting, Parameterbindung, begrenzter Filter, deterministischer Sortierung und Paging.
- `AdapterCapabilities`: Dokumentiert Fähigkeiten und Einschränkungen (Transaktionen, atomarer Audit, Views, generierte Schlüssel, optimistische Konflikterkennung, Beziehungen und Typgrenzen).
- `DatabaseErrorMapper`: Übersetzt Datenbankfehler in stabile interne Kategorien ohne SQL-Text, Zugangsdaten oder Verbindungsstrings offenzulegen.

Der Core nutzt diese Verträge und nicht unmittelbar PDO. Ein späterer Adapter darf intern PDO, OCI8, ODBC oder einen anderen geprüften Treiber verwenden. Kein Adapter darf Ressourcen-/Feldfreigaben erweitern, freie Client-SQL-Abfragen erlauben oder Audit-/Transaktionsgarantien stillschweigend deaktivieren.

## Releasegrenzen und Adapterkandidaten

| Stufe | Datenbank / möglicher Treiber | Verbindlichkeit |
|---|---|---|
| V0.1 | MariaDB/MySQL — PDO_MYSQL | Einziger verpflichtender V0-Adapter |
| V1.0 | SQLite — PDO_SQLITE | Zweiter Pflichtadapter **erst nach abgenommener V0** |
| Nach V1 | PostgreSQL — PDO_PGSQL | Separat evaluieren und implementieren |
| Nach V1 | Oracle — PDO_OCI oder OCI8 | Treiber, Clientbibliotheken, Oracle-SQL und Kompatibilität prüfen |
| Später | IBM Db2 — PDO_IBM oder PDO_ODBC | Client-/Treiberverfügbarkeit, Db2-Variante und SQL-Dialekt prüfen |
| Später | Firebird — PDO_FIREBIRD | Nativer Client, Dialekt und Nebenläufigkeit prüfen |
| Weitere | Zusätzliche relationale Datenbanken | Eigene Scope-/ADR-/Testfreigabe |

Ein vorhandener PHP-Treiber beweist **nicht**, dass er auf dem Zielhost nutzbar ist. Gerade Oracle und Db2 können Clientbibliotheken/Erweiterungen benötigen, die auf Shared Hosting nicht installierbar sind. Unterstützte PHP-/Treiber-/Datenbankversionen und Nutzungsbedingungen sind pro Deployment zu verifizieren.

## Verbindliche Kompatibilitäts- und Sicherheitsprüfungen

Jeder freizugebende Adapter muss mit einer eigenen isolierten Testdatenbank dieselbe **externe HTTP-Vertragstestsuite** für seine vorgesehenen Funktionen bestehen:

1. Default-Deny für Ressourcen, Methoden und Felder; SQL-Injection-Versuche über Bezeichner, Filter, Sortierung und Werte.
2. CRUD, Not-Found, Validierung, NULL, Unicode, Datum/Zeit mit eindeutigen Zeitzonenregeln, Boolesche Werte, Dezimalpräzision, erlaubte Binärwerte und Schlüsselerzeugung.
3. Begrenzte deterministische Filter, Sortierung und Pagination; weder unbegrenzte Abfragen noch freie SQL-Ausdrücke.
4. Commit/Rollback, Unique-/Foreign-Key-Konflikte, konkurrierende Änderungen und Teilfehler; **in V1 keine Datenänderung ohne verpflichtenden Audit im selben Commit**.
5. Schema-/Bezeichnerverhalten, minimal privilegierte DB-Konten, getrennte Secrets, Verbindungsfehler und bereinigte Logs.
6. Fehlender/deaktivierter/inkompatibler Adapter oder erforderliche Funktion führt zu einem kontrollierten Deployment-/Health-Fehler, niemals zu großzügigeren Berechtigungen.
7. Reproduzierbare Tests, dokumentierte Migrationen und Backup-/Restore-Proben gegen **reale unterstützte** Datenbankversionen. Ein erfolgreicher PDO-Verbindungstest reicht nicht.

Änderungen am öffentlichen Vertrag müssen versioniert oder nachweislich kompatibel normalisiert sein. Datenbankschema-Migrationen bleiben eine gesonderte explizite Betreiber-/Anwendungsaufgabe. Diese ADR verspricht **keine** automatische Migration zwischen unterschiedlichen Datenbanken.

## Bewertete Alternativen

- **MariaDB überall fest verdrahten:** zunächst schnell, später teuer; als langfristige Struktur ungeeignet.
- **PDO allein als vollständige Datenbankabstraktion:** unzureichend, da SQL-Dialekte/Semantik nicht vereinheitlicht werden.
- **Alle Adapter und Plugin-Verwaltung in V0:** zu teuer, schwer prüfbar, Shared-Hosting-Risiko; ausdrücklich zurückgestellt.
- **Kleiner Repository-Port jetzt; geprüfte Adapterregistrierung mit realem zweitem Adapter:** bevorzugter inkrementeller Weg.

## Folgen, Vorgehen und Entscheidungsstand

**V0:** geplanten kleinen datenbankneutralen Port und MariaDB-Adapter umsetzen; keine PDO-/MySQL-Semantik in Controller/Services verteilen; keine spekulative Plug-in-Infrastruktur.

**V1:** SQLite als zweiten Adapter implementieren und testen; bei Bedarf erst dann Factory/Registry und Capability-Vertrag endgültig festlegen. PostgreSQL und Oracle gehören nicht zu den V1-MUSS-Anforderungen.

**Nach V1:** PostgreSQL und Oracle als erste Machbarkeitsprüfungen; Db2 und Firebird als unabhängige weitere Kandidaten. Jede Freigabe benötigt eigene Testnachweise und ein Hostingprofil.

**Entscheidungsverantwortung/Review:** offen. **Angenommen am:** nicht zutreffend. **Implementierungsnachweis:** keiner. Die Nennung eines Adapters ist keine Aussage, dass er bereits unterstützt wird.
