# SASD API Server – Teststrategie und Qualitätssicherung

> **Sprache:** **Deutsch (bisheriger Dateipfad)** · [English (Standard)](test-strategy.md) · [Deutsch (kanonisch)](test-strategy.de.md)  

> **Status:** Testkonzept, keine Testausführung behauptet · **Stand:** 01.11.2026  
> Bezug: [Lastenheft V0](lastenheft-v0.md), [Pflichtenheft V0](pflichtenheft-v0.md), [Lastenheft V1](lastenheft-v1.md), [Pflichtenheft V1](pflichtenheft-v1.md)  
> Prozess: [Entwicklungsplan](entwicklungsplan-v0.md) · [Release und Abnahme](release-und-abnahme.md) · [Roadmap](../ROADMAP.md)

## 1. Testziele und Umfang

Tests müssen nicht nur nachweisen, dass der normale CRUD-Aufruf funktioniert, sondern insbesondere, dass der **nicht erlaubte** Zugriff nicht funktioniert. Der SASD API Server ist eine potentiell öffentlich erreichbare Schicht vor Datenbanken. Deshalb sind **Auth-/Ressourcen-/Feld-Sperren, SQL-Injection-Abwehr, Hostingkonfiguration und Instanzisolation** gleichrangig mit den fachlichen Erfolgspfaden.

**Abgrenzung:** V0 testet PHP 8.4/MariaDB 10.11, die einfache API und zwei voneinander getrennte Installationen. V1 testet zusätzlich beide Pflichtadapter MariaDB/MySQL und SQLite, Rollen, Audit, Rate Limits, Views/Relationen und OpenAPI. V1-SOLL-Funktionen werden nur getestet, wenn sie tatsächlich Bestandteil eines freigegebenen Releases sind.

## 2. Testpyramide und Ausführungsorte

| Ebene | Testgegenstand | Ausführung | Zweck |
|---|---|---|---|
| Statisch | PHP-Syntax, Format, keine Secrets, gültige Docs/JSON | lokal und CI | frühe Fehlererkennung |
| Unit | Router, Parser, ConfigLoader, Key-Validator, Felder, QueryPlan | lokal/CI ohne DB | deterministische Regeln |
| Integration | PDO-Adapter, DDL-Testfixture, Constraints, Transaktionen | getrennte Test-DB | echte SQL-Semantik |
| HTTP-Contract | Routen, Methoden, Header, JSON/Status, Fallback | HTTP-Testserver/Host | Clientvertrag |
| Security | Auth, Allowlist, Injection, TLS, Secretpfade | lokal und echte Hostingumgebung | Angriffswege |
| Deployment | Installationsprozess, private Dateipfade, Routing, PHP-Extensions | Zielhosting mit Testdaten | Betriebsfähigkeit |
| Isolation | zwei Instanzen mit verschiedenen DBs/Keys/Config | Integration/Hosting | keine Kreuzzugriffe |
| Performance | definierte Lastprofile, Paging-Grenzen, SQLite-Sperren | reproduzierbares Profil | Grenzwerte begründen |
| Migration/Recovery | Config V0→V1, Backup/Restore | Staging | kontrolliertes Upgrade |

Kein Test ist allein deshalb erfolgreich, weil er in einer lokalen Entwicklungsumgebung durchläuft. Providerabhängige Aussagen (Rewrite, HTTPS, Dateisystem- oder SQLite-Sperren) erfordern **zusätzliche reale Hosttests**.

## 3. Fixtures, Umgebungen und Sicherheitsmaßnahmen

### 3.1 Reproduzierbare Testdaten

Ein neutrales, **nicht produktives** Fixture mit `books`, `authors`, einem Fremdschlüssel, mindestens einem Unique-Constraint, mehreren Datentypen und mehr als 100 Zeilen deckt CRUD und Paging ab. Dazu gehören absichtlich vorhandene/verweigerte Datensätze, Sonderzeichen, NULL-/Default-Fälle und Fremdschlüssel-Konflikte. Die Instanz A und Instanz B nutzen ausdrücklich **verschiedene** Testdatenbanken und Schlüssel.

Für V1 wird ein äquivalentes SQLite-Fixture entwickelt. Die logische Ressource bleibt gleich, auch wenn Typdefinition und SQL-DDL sich unterscheiden. Datenbankdateien, Zugänge und abgefragte sensible Testprotokolle dürfen nicht ungeschützt ins Repository geraten.

### 3.2 Testumgebungen

- **Unit:** kein Netzwerk und keine reale Datenbank erforderlich.
- **MariaDB-Integration:** eigene isolierte DB mit minimal privilegierten Testcredentials, reproduzierbare Neuinitialisierung.
- **HTTP-Contract:** gleiche Tests sowohl auf Rewrite-Pfaden als auch mit `index.php?route=...`; wenn der Host nur Fallback erlaubt, wird dies dokumentiert.
- **IONOS-Testdeployment:** echte, unkritische Testinstanz, private Dateien außerhalb Document Root, TLS-Verifikation, absichtlicher HTTP-Abruf von Konfigurationsdateien.
- **V1-SQLite:** beschreibbare private Datei, aktivierte Foreign Keys, Busy-Timeout und Parallelzugriff mit reproduzierbaren Konflikten.
- **Staging für Releases:** Kopie des **Test**zustands; Restore-Probe vor Migration. Kein Test mit produktiven Gesundheits-, Bibliotheks- oder Kundendaten.

## 4. Konventionen für Testfälle und Nachweise

Testkennungen verwenden `T-V0-xx` bzw. `T-V1-xx` aus dem jeweiligen Pflichtenheft. Abnahmereferenzen nutzen `AT-V0-01 … AT-V0-10` und `AT-V1-01 … AT-V1-16`. Bei jeder neuen Funktion dokumentieren wir:

1. Ausgangszustand, Fixtures, Testumgebung und verwendeten Commit.
2. Request (Methode, Pfad, Body **ohne Secret**); bei automatisierten Tests Schlüssel nur aus Secret-Environment.
3. Erwartete HTTP-Statuscodes, Header, Antwortschema und persistierten Datenzustand.
4. Negativen Fall (falsche Berechtigung, fehlerhafter Input, Konflikt oder abhängiger Dienst ausgefallen).
5. Tatsächliches Ergebnis, Logs/Request-ID und mögliche Abweichung.
6. Entscheidung: bestanden, fehlgeschlagen, nicht ausgeführt oder nicht anwendbar – mit Grund.

**Ein nicht ausgeführter Test ist niemals „grün“.** Ergebnisse werden erst mit Datum/Umgebung in einem tatsächlichen Abnahmeprotokoll dokumentiert.

## 5. V0-Matrix – funktionaler Kern

| Gruppe | Erfolgsfall | Gefährlicher Gegenfall | Lastenheft |
|---|---|---|---|
| GET-Liste | sichtbare Daten und Leerliste | unerlaubte Tabelle wird nicht ausgegeben | F01/F02/F06 |
| GET-Einzelobjekt | korrekter Datensatz nach PK | unbekannte/fehlerhafte ID und Injection | F02/F12 |
| POST | erlaubter Datensatz mit 201/Location | unbekanntes Feld, fehlender Pflichtwert | F03/F09/F12 |
| PUT | alle erlaubten Felder ersetzt | unvollständige Repräsentation, readonly-ID | F04/F12 |
| PATCH | nur angegebene Felder geändert | leeres Objekt, unzulässiges Feld | F04/F12 |
| DELETE | nur benannter Datensatz entfernt | nicht erlaubte Methode, FK-Konflikt | F05/F06 |
| Filter | AND/equality, zulässige Spalten | SQL-Injection, private Spalte | F07/F12 |
| Paging | limit/offset/sort, stabile Reihenfolge | negative/überhöhte Werte | F08 |
| Auth | gültiger API-Key erlaubt freigegebene Route | fehlender/falscher/fremder/widerrufener Key | F11 |
| HTTP/JSON | dokumentiertes Schema und Status | ungültiges JSON/Content-Type/Method | F09 |
| Hosting | Rewrite und Query-Fallback verarbeiten gleiche Routen | direkte Secret-/Config-URL erreichbar? Muss scheitern | F10/F13 |
| Betrieb | Health und sanierte Logs | DB offline, ohne Secret-/SQL-Leak | F14/F15 |
| Dokumentation | zweites Deployment nach Anleitung | nicht dokumentierter Pflichtschritt | F16/F17 |

### 5.1 Minimales V0-Abnahmepaket

**AT-V0-01 bis AT-V0-10** müssen sämtlich bestanden sein (Details: Lastenheft V0 `9). Dazu zählen Erfolg und Negative Cases, eine Hostinginstallation, Instanztrennung und reproduzierbare Dokumentation. Ein bestandener Unit-Test ohne erfolgreichen Security- und Hosting-Gate reicht ausdrücklich nicht.

## 6. V1-Matrix – zusätzlich zum V0-Kern

| V1-Komponente | Pflicht-Positivtests | Pflicht-Negativtests |
|---|---|---|
| SQLite-Adapter | derselbe gemeinsame CRUD-Vertrag wie MariaDB | fehlende FK-Prüfung, Locked-DB, Dateipfad außerhalb Root |
| Aliase und Views | öffentliche Namen unabhängig vom SQL-Spaltennamen; View lesen | nicht erlaubte Spalte, View-Schreibversuch |
| Filter und QueryPlan | eq/ne/lt/lte/gt/gte/in/isNull/contains/startsWith, AND/OR | unbekannter Operator, OR-/Sort-/IN-Budget |
| Relationen | genau erlaubte erste Relationsebene | private Felder, unbekannte Ziele, zu tiefe Expansion |
| Key-Store | mehrere Identitäten, Rotation, Widerruf | deaktivierter Key, fremde Rolle |
| RBAC | Reader, Editor, Operator gemäß Policies | Reader-Write, verbotene Projektion/Filter/Count |
| Audit | Fachdatenänderung **und** Auditereignis im selben Commit | Audit-Ausfall → vollständiger Rollback |
| Rate-Limit | 429 mit dokumentiertem Retry-Verhalten | Store-Ausfall → fail-closed; IP-Spoofing |
| Health/Readiness | geschützter DB-Check | öffentlicher Health verrät keine Interna |
| OpenAPI | gültige Beschreibung realer freigegebener Routen | nicht freigegebene Ressource erscheint nicht im Rollenschema |
| Config V0→V1 | Validierung/Migration/Rollback auf Staging | unbekannte Config-/Role-Felder öffnen keine Rechte |
| Clients | curl, Java, .NET gegen identischen Vertrag | keine PHP-spezifische Sessionannahme |

**SOLL**: Named SQL, Row-Visibility, Optimistic Locking, Controller-Erweiterungen und Metriken erhalten jeweils eine eigene Testgruppe, sobald sie ausdrücklich freigegeben sind; andernfalls werden sie nicht als implementiert beworben.

## 7. Sicherheits-Testkatalog

1. SQL-Injection in Pfad-ID, Filterwert, Sortier-/Feldname, Body und Routenparameter.
2. Schema-/Field-Enumeration über erratische Ressourcen-, OpenAPI-, Fehler- und Sortierparameter.
3. Policy-Bypass über PUT/PATCH, relationale Expansion, Count und benannte Views.
4. Token-/API-Key-Leaks in Exceptions, Logs, CI-Artefakten und HTTP-Redirects.
5. Fehlendes HTTPS; manipulierbare Forwarded-Header; direkte HTTP-Konfigurationsdateien.
6. Ungültige oder doppelt kodierte Pfade, doppelte Queryparameter, nicht erlaubte HTTP-Methoden.
7. Große/verschachtelte JSON-Requests, extreme Paging- und IN-Listen, Sonderzeichen und invalides UTF-8.
8. Datenbank-/Audit-/Limiter-Ausfälle und sichere Fehlerreaktion ohne allgemeine Freigabe.
9. Rechte-/Datenvermischung zwischen Installation A und B.
10. V1: gleichzeitige Schreibzugriffe, SQLite Busy/Timeout, optionale Versionierung.
11. Nicht erlaubte CORS-Ursprünge; **CORS ist keine Authentifizierung**.

**Verbindlich:** Eine Schwachstelle mit möglichem unautorisierten Datenzugriff oder Secret-Offenlegung blockiert den Release bis zur Behebung und Retest.

## 8. Performance- und Ressourcenprüfungen

Es werden keine unbelegten festen Millisekunden-SLOs erfunden. Vor jedem Release wird eine Tabelle mit Testhardware/Host, PHP-/Datenbankversion, Datensatzgrößen, Anzahl Requests, Parallelität, Latenzverteilung, Speicher und Fehlerrate erstellt. Relevante Profile: einzelne GET/POSTs, Seite 1 und hohe Offsetwerte, 100+-Zeilen-Listen, einfache Filter; bei V1 zusätzlich die Relation und 2–10 parallele SQLite-Writer. Grenzwerte für Bodygröße, Query-Budget und Rate Limiting werden danach verbindlich eingestellt und als Configdefaults festgehalten.

## 9. CI-Vorschlag ohne falsche Ausführungsversprechen

**Noch kein aktiver Workflow wird hiermit behauptet.** Bei der Implementierung ist eine GitHub-Actions-Pipeline für PHP-Lint, Unit, statische Prüfungen, MariaDB-Integration und Contract-/Security-Tests einzurichten. CI darf niemals auf Produktivdaten zugreifen oder reale Secrets ins Log schreiben. Hostingabhängige manuelle Tests bleiben ein **zusätzliches** Release-Gate. V1 ergänzt SQLite-Integration und gemeinsame DB-Contract-Suite.

Die Pipeline nutzt datensparsame Testausgaben; ein fehlgeschlagener Test gibt seinen Testfall, nicht den API-Key, aus. Jede Build-/Testversion wird im späteren Abnahmeprotokoll nachgewiesen.

## 10. Exit-Kriterien

**V0:** alle V0-F01–F17 und AT-V0-01–10 bestanden, SQL- und API-Security geprüft, zweite Instanz isoliert, Installation und Restore-Test nachgewiesen. **V1:** V0-Basis weiterhin gültig plus alle 22 verpflichtenden V1-Funktionen und AT-V1-01–16, MariaDB-/SQLite-Vertrag grün, Audit-Rollback/Rate-Fail-Closed, OpenAPI und Migration nachgewiesen. **SOLL** nur bei ausdrücklicher Freigabe.

**Dokumentstatus:** Testdesign, noch kein Testbericht und kein belegter CI-Erfolg.
