# SASD API Server – Betrieb, Deployment, Backup und Recovery

> **Sprache:** **Deutsch (bisheriger Dateipfad)** · [English (Standard)](deployment-operations.md) · [Deutsch (kanonisch)](deployment-operations.de.md)  

> **Status:** Entwurfs-Runbook für V0/V1, keine produktive Installation nachgewiesen · **Stand:** 01.11.2026  
> Bezüge: [Architektur V0](architektur-v0.md), [Pflichtenheft V0](pflichtenheft-v0.md), [Architektur V1](architektur-v1.md), [Sicherheitsregeln](../SECURITY.md), [Releaseprozess](release-und-abnahme.md)

## 1. Zweck und Geltungsbereich

Dieses Runbook beschreibt, **wie eine sichere SASD-API-Server-Installation vorbereitet, betrieben, diagnostiziert, aktualisiert und wiederhergestellt werden soll**. Es ist keine Behauptung, dass die genannten Kommandos bereits auf IONOS getestet wurden. Erst ein tatsächlicher Provider- und Restore-Test macht aus einem geplanten Verfahren eine freigegebene Betriebsanleitung.

**V0:** PHP 8.4, MariaDB 10.11, ein Datenbankbackend pro Installation, Header-API-Key, per Konfiguration freigegebene REST-Ressourcen. **V1:** derselbe Grundbetrieb; zusätzlich SQLite-Alternative, geschützter Key-/Rollenstore, Rate-Limits, transaktionaler Write-Audit und Readiness.

Weder Backups noch Datenbankmigrationen werden durch bloßes Bereitstellen des API-Servers automatisch erledigt. Für die **Anwendungsdaten** ist der Betreiber verantwortlich, für fachliche Datenmodelle die jeweilige Fachanwendung.

## 2. Deployment- und Dateisystemgrenzen

~~~text
$PRIVATE_ROOT/                      # außerhalb Document Root
  releases/<version>/src/           # PHP-Klassen
  releases/<version>/vendor/        # ggf. Composer-Dependencies
  instances/<id>/config/            # Ressourcen ohne öffentliche Auslieferung
  instances/<id>/secrets/           # DB-Credentials, API-Key bzw. V1 Key Store
  instances/<id>/logs/              # strukturierte, geschützte Logs
  instances/<id>/data/              # nur V1 SQLite, falls genutzt
  instances/<id>/backups/           # nur geschützte Konfigurationsbackups

$DOCUMENT_ROOT/
  index.php                         # einziger notwendiger PHP-Front-Controller
~~~

Die Pfade sind ein **Soll-Beispiel**, kein fest verifizierter IONOS-Dateibaum. Privater Quellcode und Dependencies können über einen sicher konfigurierten PHP-include-Pfad geladen werden; ein öffentlich auslieferbares `vendor/`, `config/` oder `logs/` ist unzulässig. **Wenn der Tarif keine physisch geschützte Trennung erlaubt, wird das Deployment nicht freigegeben.** Ein möglicherweise ignoriertes `.htaccess` ist keine vollwertige Schutzbarriere.

### 2.1 Pflichtprüfungen vor dem ersten Deployment

| Prüfung | Erwartetes Ergebnis | Blockiert Produktion? |
|---|---|---|
| PHP 8.4 verfügbar | kompatibler PHP-Handler | Ja |
| PDO MySQL verfügbar | erfolgreiche **Test**-DB-Verbindung | Ja |
| HTTPS am Webserver verpflichtend | HTTP liefert keine geschützten Daten | Ja |
| Routing/Query-Fallback | definierte V0-Routen funktionieren | Ja |
| Config-/Secret-/Log-Pfade | direkter HTTP-Zugriff unmöglich | Ja |
| DB-Rechte | least-privilege nachweisbar oder Abweichung geprüft | Ja |
| Fehleranzeige | keine SQL-/Stacktrace-/Pfaddetails nach außen | Ja |
| V1: PDO SQLite/Dateisperren | SQLite-Verbindung und Lock-Test mit echten Hostprozessen | Ja, falls SQLite-Adapter genutzt |
| V1: Rate-/Audit-Store | atomar, zugriffsgeschützt und störungsfest | Ja |

## 3. Konfigurations- und Secret-Management

Die Ressourcenkonfiguration liegt pro Instanz in privater JSON-Datei (V0 `schemaVersion=1`, V1 `schemaVersion=2`). DB-Passwort und API-Keys sind **keine** Ressourcendefinitionen und werden über gesicherte Umgebungsvariablen oder private Secret-Dateien geladen. Beispielkonfigurationen im Repository enthalten **nur** Platzhalter und keine funktionsfähigen Schlüssel.

**Keine** produktiven Schlüssel oder Passwörter per URL, Querystring, README, CI-Ausgabe, git commit oder öffentlichem Issue teilen. API-Schlüssel bestehen aus kryptografisch sicherem Zufall. V0 verwendet einen installationsspezifischen Schlüssel; V1 kann mehrere technische Identitäten und Rollen verwalten. Ein unvertrauenswürdiger Browser oder weit verteilte Desktop-App kann keinen geheimen hardcodierten API-Key garantieren.

### 3.1 Initiale Konfiguration

1. Instanz-ID, öffentliche Basis-URL, HTTPS-Policy und private Pfade definieren.
2. Test-/Produktiv-DB samt dediziertem SQL-Konto einrichten; notwendige GRANTs begrenzen.
3. Erforderliche Tabellen **außerhalb der API** anlegen; Ressourcennamen, Felder und Methoden explizit freigeben.
4. Secret-Quelle mit echten Credentials **außerhalb Document Root** bereitstellen; Dateiberechtigungen prüfen.
5. Config-Validator, DB-Ready-Test und Resource-Allowlist-Negativtest ausführen.
6. Kanonische Route und `index.php?route=...`-Fallback anhand echter HTTPS-Anfragen prüfen.
7. Alle sensiblen Verzeichnisse per HTTP direkt anfragen – der Abruf muss scheitern.
8. Abnahmeprotokoll dokumentieren: PHP, PDO, MariaDB/SQLite-Version, Proxy/TLS-Modell, Testresultate.

## 4. Geplanter Ablauf einer sicheren Installation (V0)

**Vorbereitung:** Revision/Releasekandidat und Integrität dokumentieren; Datenbankbenutzer und nicht öffentliche Zielpfade bereithalten; getrennte Testinstanz wählen.

**Deployment:** freigegebenen Code hochladen, Composer-Dependencies je Freigabestand bereitstellen, `index.php` sicher auf die private Bootstrap-Datei verweisen lassen, private Ressourcen-/Secret-Konfiguration installieren. Niemals einen produktiven Fehler dadurch beheben, dass Secrets ins Webroot verschoben werden.

**Smoke-Test:** Health prüfen, ohne dass Server-Interna erscheinen; fehlender/falscher Schlüssel darf keine Daten liefern; mit gültigem Schlüssel einfache, ausdrücklich freigegebene GET-Abfrage durchführen; ungültige Tabellen-/Spaltennamen müssen scheitern. Danach mit isolierten Testdaten POST/PATCH/DELETE und Rollback/Constraint-Fall prüfen.

**Betriebsfreigabe:** nur wenn alle V0-Abnahmeanforderungen und externen Hosting-Gates nachweisbar erfüllt sind. Dieselbe Software kann danach für eine zweite URL installiert werden, **mit anderem** Konfigurations-/Key-/DB-Kontext. Gemeinsame Quellcode-Releases ersetzen keine getrennten Betriebseinstellungen.

## 5. Reverse Proxy, Routing und HTTP-Schutz

- Bevorzugter Pfad: `/api/v0/{resource}` bzw. `/api/v1/{resource}`.
- Garantierter Fallback im Entwurf: `/index.php?route=/api/v0/{resource}`.
- URI-Quelle und tatsächlich ausgeführte PHP-Datei dürfen keinen Zugriff auf die privaten Ordner erlauben.
- HTTP-Anfragen an sensible Datenendpunkte werden serverseitig abgelehnt bzw. bereits an der TLS-Grenze sicher behandelt; ein Redirect allein schützt keinen über HTTP bereits gesendeten Schlüssel.
- Falls ein Reverse Proxy TLS terminiert, dürfen nur durch den Provider garantiert echte TLS-Informationen verwendet werden; **nie** beliebige Client-Forwarded-Header.
- Für Browser wird eine explizite CORS-Origin-Allowlist gesetzt; CORS ist **kein** Authentifizierungsverfahren.
- Der öffentliche Hostname für `Location`-Header stammt aus Konfiguration statt untrusted Host-Headern.

## 6. Backup, Restore und Datenkonsistenz

### 6.1 Was sichern?

| Artefakt | Muss gesichert werden? | Besonderheit |
|---|---|---|
| MariaDB-Anwendungsdaten | Ja, durch Betreiber | konsistenter DB-Backup und Restore-Test |
| Private Ressourcen-Konfiguration | Ja | ohne unnötige Verbreitung von Secrets |
| Private Key-/Secret-Quelle | Ja, verschlüsselt und besonders restriktiv | Verlust führt ggf. zu Schlüsselrotation |
| Laufende Servercodeversion | nachvollziehbarer Release/Commit | aus Git/Release reproduzierbar |
| V1 SQLite-Datenbank | Ja, SQLite-konsistenter Backupmechanismus | nicht einfach laufende DB-Datei unkoordiniert kopieren |
| V1 internes Auditschema | Ja, im gemeinsamen DB-Backup | transaktional mit Fachdaten verbunden |
| Protokolle | nach Retentionsregel | keine unkontrollierte langfristige PII-Ablage |
| Rate-Limit-Kurzzeitstore | in der Regel nein | beim Reset mögliche Burst-Änderung berücksichtigen |

Ein MariaDB-Dump allein ist möglicherweise nicht konsistent, wenn Tabellen ohne transaktionale Engine beteiligt sind. Für geeignete InnoDB-Tabellen kann ein getestetes konsistentes Backupverfahren eingesetzt werden; die konkrete Datenbank-/Hostverwaltung entscheidet über zulässige Werkzeuge. Bei SQLite sollte ein vom Datenbanksystem unterstütztes Online-Backup/API- oder kontrolliertes Quiesce-Verfahren verwendet werden, **nicht** unreflektiertes Kopieren einer aktiven DB mitsamt WAL-Dateien.

### 6.2 Restore-Probelauf (Pflicht vor Release)

1. Unabhängige **Test**-Zielinstanz mit eigener URL und privaten Pfaden bereitstellen.
2. Sicherung und Quelle/Version nachvollziehbar identifizieren; keine bestehende Produktion überschreiben.
3. Datenbank und Config/Key-Material gemäß dokumentierter Reihenfolge wiederherstellen.
4. Erforderliche Tabellen, PK/FK, Accounts, Instanzrechte und V1-Audittabelle prüfen.
5. Authentifizierte GET-/Write-Smoke-Tests ausführen; Querzugriff und vertrauliche Dateien negativ testen.
6. Gemessene Wiederherstellungsdauer, Hindernisse und Datenstand protokollieren. **RTO/RPO werden nicht erfunden**, sondern aus tatsächlichen Testwerten und Betreiberzielen abgeleitet.

### 6.3 Update und Rollback

Vor jeder Änderung werden Code-Commit/Tag, Configschema-Version, DB-Schema und Backups dokumentiert. Ein Release wird **zuerst** mit Testdaten in Staging geprüft. V1 kann Schema- und Auditmigrationen voraussetzen; deshalb ist „alter PHP-Code wieder hochladen“ **nicht automatisch** ein sicherer Rollback. Im Notfall kann der getestete DB-Restore erforderlich sein. Alte Keys dürfen nach V1-Migration nicht stillschweigend administrative Rechte erhalten.

## 7. Betrieb und regelmäßige Kontrollen

**Technische Logs:** JSON-Lines, UTC, Request-ID, Ressource, Methode, HTTP-Status, Fehlerklasse, ggf. technische Clientkennung. Keine Klartextsecrets, kompletten Bodies, Auth-Header oder SQL-Bindwerte. Logpfad und -rotation sind pro Instanz getrennt. Keine Logs im öffentlich auslieferbaren Verzeichnis.

**Health:** minimaler öffentlicher Status ohne interne DB-/Schema-/Versionsdetails. **Readiness:** geschützter DB-/Audit-Status; V1 nur mit Operatorrolle. Ein Ausfall darf nicht als leere erfolgreiche Datensatzliste ausgegeben werden.

**Wiederkehrende Prüfung:** Hostingversionen, TLS, private Dateipfade, DB-Rechte, aufrufende Schlüssel, Error-Logs, Backup-Erfolg, letzter Restore-Test, Resource-Policies und offene Sicherheitsmeldungen.

## 8. Störungshandbuch

| Symptom | Sichere Erstprüfung | Korrektur / Eskalation |
|---|---|---|
| 401 bei allen Requests | Schlüsselquelle, Header, Keyrotation | Key nicht ins Log/Chat kopieren; geschützte Config prüfen |
| 403/404 für erlaubte Ressource | Resource-/Method-/Role-Policy, API-Version | Config prüfen, nie pauschal All-access aktivieren |
| 500/503 | Request-ID, geschützte Logs, DB-/Configbereitschaft | technische Ursache intern beheben, Details nicht öffentlich zeigen |
| 404 bei allen API-Pfaden | Front Controller, Rewrite und Query-Fallback | per `index.php?route=...` testen, Webserver-Regeln korrigieren |
| MariaDB nicht erreichbar | Dienststatus/Host/DB-Account/GRANTs über gesicherte Wege | keine DB-Zugangsdaten in Fehlermeldungen |
| SQLite `SQLITE_BUSY` (V1) | Parallelität, Timeout, Locking, WAL/Filesystem | sichere Retrygrenzen prüfen, keine unendlichen Retries |
| V1 Rate-Limit 429 | Client-ID, Budget/Retry-After | kein Freischalten für alle Clients |
| V1 Rate-Store fehlerhaft | Datei-/DB-Schreibrechte, Locking | fail-closed beibehalten; Store sicher wiederherstellen |
| V1 Audit-Write fehlt | interne Tabelle/Rechte/Transaktionsprüfung | Write muss rollbacken; nicht ohne Audit fortsetzen |
| Secret-Datei per URL erreichbar | Zugriff sofort stoppen, Secrets rotieren, Ursache isolieren | sicherheitskritischer Vorfall, siehe SECURITY.md |

### 8.1 Vorgehen bei einem Sicherheitsvorfall

Zuerst **Schaden begrenzen**: gefährdete Instanz sperren oder sicher isolieren, kompromittierte Keys deaktivieren/rotieren, private Files schützen. Relevante, datensparsame Beweise sichern; keine privaten Inhalte in öffentliche GitHub-Issues stellen. Anschließend Ursache und Auswirkungen anhand Request-IDs, Betreiber-Logs und Backupzustand untersuchen. Betroffene Daten/Personen und rechtliche Meldungen müssen vom jeweiligen Betreiber anhand seines konkreten Falls geprüft werden. Erst nach Negativtest und Freigabe erneut öffnen.

## 9. Betriebs-Checklisten

**Installation:** PHP/PDO ok; DB-Rechte minimal; Config gültig; HTTPS; private Files unerreichbar; Key sicher; erlaubte CRUD-Ressourcen; Routing-Fallback; Backup; Logs.

**Update:** getesteter Releasekandidat; Backup und Restore-Probe; Configmigration; kein heimlicher API-Vertragsbruch; Security-/Contract-Tests; Rollbackplan.

**Wiederherstellung:** fremde Testinstanz; DB/Config/Key-Rekonstruktion; korrekter Softwarestand; Auditstruktur; Auth-/Isolationstest; Ergebnis dokumentiert.

**Dokumentstatus:** Betriebsdesign, nicht nachgewiesenes Produktivverfahren. Konkrete hostabhängige Befehle werden erst nach erfolgreichem IONOS-Praxistest als ausführbare Anleitung freigegeben.
