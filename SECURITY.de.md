# Sicherheitsrichtlinie – SASD API Server

> **Sprache:** [English (Standard)](SECURITY.md) | **Deutsch**  
> **Projektstatus:** Am 01.11.2026 sind V0 und V1 dokumentierte Entwürfe, noch keine abgenommenen Releases. Diese Richtlinie legt das beabsichtigte Vorgehen fest; sie belegt **kein** abgeschlossenes Sicherheitsaudit oder einen unterstützten Produktivbetrieb.

## 1. Unterstützte Versionen

| Version | Status | Sicherheitsunterstützung |
|---|---|---|
| V0.1.0 | Geplant | Noch nicht veröffentlicht; keine Zusage für produktiven Support |
| V1.0.0 | Geplant | Noch nicht veröffentlicht; keine Zusage für produktiven Support |
| Zukünftige Releases mit Git-Tag | Noch festzulegen | Unterstützungszeitraum und Bedingungen werden je Release dokumentiert |

Ein ungetaggter Entwurf oder ein Architekturdokument ist **kein** geprüfter, produktionsreifer API-Server.

## 2. Sicherheitslücken vertraulich melden

**Bitte veröffentliche keine ausnutzbaren Schwachstellen, Zugangsdaten, privaten Schlüssel oder personenbezogenen Informationen in öffentlichen GitHub-Issues oder Pull Requests.**

Falls GitHub im Repository die Funktion **Security → Report a vulnerability** anbietet, nutze diesen privaten Meldeweg. Ist sie nicht aktiviert, frage die Projektverantwortlichen zunächst **ohne Exploitdetails in einem öffentlichen Beitrag** nach einem vertraulichen Kommunikationskanal.

Übermittle erst im privaten Kanal die betroffene Version beziehungsweise den Commit, eine bereinigte Reproduktionsanleitung, die mögliche Auswirkung und gegebenenfalls einen Lösungsvorschlag.

Meldungen werden entsprechend der verfügbaren Projektkapazität geprüft. **Eine garantierte Reaktionszeit oder ein SLA wird nicht zugesagt.** Eine abgestimmte Offenlegung soll unnötige Risiken für Nutzer vor einer Gegenmaßnahme vermeiden.

## 3. Sicherheitsarchitektur und vorgesehene Schutzmaßnahmen

### 3.1 Schutz vor unbeabsichtigter Datenfreigabe

Die API muss über explizite Allowlists festlegen, welche Ressourcen, Methoden, lesbaren und schreibbaren Felder sowie Filter-/Sortierspalten und später Rollen oder Relationen zugänglich sind. **Standardmäßig ist alles verboten.** Eine neu angelegte Datenbanktabelle darf nicht automatisch zu einem öffentlichen Endpunkt werden.

### 3.2 Sichere SQL-Verarbeitung

SQL-Werte werden über Parameterbindung verarbeitet. SQL-Bezeichner und Operatoren werden ausschließlich aus vertrauenswürdigen, geprüften serverseitigen Definitionen ausgewählt. HTTP-Clients dürfen weder beliebiges SQL noch DDL, freie Dateipfade oder serverseitige Skriptbefehle übermitteln. Schreiboperationen müssen eindeutig auf den zulässigen Datensatz begrenzt sein und erforderliche Transaktionsregeln beachten.

### 3.3 API-Zugangsdaten und Grenzen der Endbenutzerauthentifizierung

V0 verwendet einen hochentropischen API-Schlüssel je Installation, der über einen HTTP-Header übertragen wird. V1 sieht mehrere technische Identitäten mit Schlüsselwiderruf und Rollen vor. Schlüssel liegen in geschützten Quellen, **niemals** in URLs, öffentlich erreichbaren Konfigurationsdateien, Logs oder Issues.

**Wichtig:** Ein API-Schlüssel in öffentlich ausgeliefertem Browser-JavaScript, einer verteilten mobilen Anwendung oder einem allgemein verfügbaren Desktopclient ist **kein zuverlässig geheim gehaltenes Credential**. Für solche Clients ist ein vertrauenswürdiger Backenddienst oder eine gesondert entwickelte Benutzerauthentifizierung nötig. CORS ersetzt keine Authentifizierung.

### 3.4 TLS und Vertrauen in Reverse Proxies

Produktive Datenendpunkte erfordern HTTPS. Proxy-Informationen dürfen nur verwendet werden, wenn ihre Herkunft durch Providerkonfiguration verifiziert ist. Ein manipulierbarer Wert in `X-Forwarded-Proto` beweist keine verschlüsselte Verbindung. Auch ein Redirect schützt keine Zugangsdaten, die zuvor schon über HTTP gesendet wurden.

### 3.5 Webroot und private Daten

Konfiguration, Datenbankzugänge, API-Schlüssel, Logs, interne SQLite-Dateien und vertrauliche Backups liegen **außerhalb des öffentlich ausgelieferten Document Root**. Ein Schutz, der ausschließlich auf `.htaccess` beruht, genügt insbesondere bei restriktivem Shared Hosting nicht. Ist diese Isolation nicht möglich, darf die Installation nicht produktiv freigegeben werden.

### 3.6 Sichere Fehler und technische Diagnose

Clients erhalten einheitliche Fehlercodes ohne SQL, Stacktraces, Kennwörter, private Pfade oder interne Metadaten. Technische Logs minimieren personenbezogene Daten und schwärzen Zugangsdaten. Ein öffentlicher Health-Endpunkt zeigt ausschließlich nicht sensitive Zustände; ausführliche Diagnose erfordert geschützte Operatorberechtigungen.

### 3.7 Zusätzliche Schutzmaßnahmen in V1

- Rollenrechte gelten für Ressourcen, Felder, Filter, Sortierung, Views, Relationen, OpenAPI und gegebenenfalls Datensatzsicht.
- Datenänderung und erforderliches Auditereignis werden in **derselben lokalen Datenbanktransaktion** bestätigt.
- Der Rate-Limiter darf bei Ausfall seines sicheren, persistenten Zustands nicht stillschweigend **fail-open** werden.
- SQLite-Dateien benötigen sichere Pfade, Dateiberechtigungen, Foreign-Key-Prüfung und getestete Sperrsemantik.
- Konfigurationsmigrationen dürfen keine großzügigen Rollen- oder Ressourcenfreigaben erzeugen.

## 4. Vorgehen bei Sicherheitsvorfällen

1. Die betroffene Installation isolieren und gefährdete Schlüssel deaktivieren.
2. Nur erforderliche, ausreichend geschützte Beweise wie bereinigte Request-IDs und technische Logs sichern.
3. Betroffene Ressourcen und mögliche Auswirkungen prüfen, ohne Geheimnisse öffentlich zu machen.
4. Einen Fix erstellen und mit negativen Regressionstests überprüfen.
5. Falls erforderlich eine geprüfte Sicherung wiederherstellen und vor erneutem Betrieb Secrets rotieren.
6. Rechtliche Meldepflichten und die Kommunikation mit Betroffenen durch den zuständigen Betreiber prüfen.

Das [Betriebs- und Recovery-Runbook](docs/betrieb-und-deployment.md), das [Risikoregister](docs/risikoregister.md) und die [Teststrategie](docs/teststrategie.md) konkretisieren die geplanten Prüfungen.

## 5. Sicherheitskriterien für Releases

Ein Release ist gesperrt, solange ein ungelöstes kritisches Problem private Dateien öffentlich macht, Authentifizierung oder Rechte umgeht, beliebiges SQL erlaubt, Zugangsdaten offenlegt oder geschützte Schreibvorgänge ohne vorgeschriebenes Audit zulässt. Die Freigabe benötigt **tatsächlich ausgeführte** Tests; Dokumente allein sind kein Nachweis. Siehe [Release und Abnahme](docs/release-und-abnahme.md).

---

Diese Richtlinie wird angepasst, sobald tatsächliche Releases und private Meldewege feststehen.
