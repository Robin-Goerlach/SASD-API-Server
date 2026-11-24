# ADR-0020: Anmeldung, Datenberechtigungen und Logging getrennt halten

> **Sprache:** [English (Standard)](0020-auth-api-logging-service-boundaries.md) | **Deutsch**  
> **Status:** **Vorgeschlagen**, noch nicht angenommen · **Datum:** 24.11.2026 · **Ziel:** V0-/V1-Schichtengrenzen und spätere Integrationen  
> **Spezifikation:** [SEC-INT-001](../service-boundaries-and-integrations.de.md) · **Verwandt:** [IP-Zugangs-ADR-0019](0019-ip-geoblocking.de.md), [Lastenheft V1](../requirements-v1.de.md).

## Kontext

Wir haben diskutiert, den SASD API Server perspektivisch in **einen eigenständigen Anmelde-/Identitätsdienst**, **einen Datendienst** und **einen gesonderten Logging-Dienst** zu trennen. [LogSink existiert als Entwicklungsrepository](https://github.com/Robin-Goerlach/LogSink), dessen README jedoch gegenwärtig ungeschützte HTTP-Lese-/Schreibzugriffe und unveränderte Rohdatenaufzeichnung beschreibt. Die API-Entwürfe V0/V1 enthalten bereits **lokale technische Keys**, **V1-Rollenrechte**, **geschützte lokale Logs** und **transaktionales V1-Schreib-Audit**.

Die zentrale Frage ist nicht bloß, auf welchem Host Code läuft, sondern **wer die Identität verwaltet, wer über Fachrechte entscheidet und welche Ausfälle andere Dienste verursachen dürfen**.

## Bewertete Alternativen

| Alternative | Vorteil | Nachteil |
|---|---|---|
| A. Anmeldung, Fachrechte und Logging dauerhaft im API Server | Kleine V0 | Später Duplikation/Kopplung |
| B. Bereits für V0 drei Netzwerkdienste vorschreiben | Zentrale Funktionen | Zusätzliche Ausfälle, Latenz, aktueller ungeschützter LogSink, schwieriges Shared Hosting |
| **C. V0/V1 unabhängig halten, kleine Integrationsports vorsehen, Dienste später anbinden** | Kleine abnehmbare V0, klare Zuständigkeiten | Spätere Verträge/Claim-Abbildung nötig |
| D. Sämtliche Fachrechte zentral im Auth Service prüfen | Ein zentrales Regelwerk | Fach-/SQL-Kontext, harte Laufzeitabhängigkeit, Datenleck-Risiko |
| E. LogSink als einzige Auditablage | Zentraler Überblick | Kein atomarer Commit mit Fachdatenbank, Ausfall-/Inkonsistenzrisiko |

## Vorgeschlagene Entscheidung

**Option C wird vorgeschlagen**, mit diesen Grenzen:

1. **V0** behält installationsspezifischen technischen API-Key, lokale Ressourcen-/Feld-/Methodenrechte und geschützte lokale Protokolle. Eine kleine `CredentialVerifier`-Grenze kann vorbereitet werden; **kein** JWT, OAuth/SSO oder Remote-Auth-Zwang.
2. **V1** erfüllt zunächst die schon vereinbarten lokalen technischen Clientidentitäten und RBAC-Regeln. SASD-weite Benutzerkonten/SSO sind **späterer eigenständig beauftragter Umfang**.
3. Ein späterer **Auth Service** prüft Identitäten beziehungsweise stellt Tokens aus, **nicht** jede einzelne Tabellen-/Feldberechtigung. Der API Server autorisiert seine Fachressourcen selbst. Korrekt signierte Tokens können lokal geprüft werden; notwendige Opaque-Introspection benötigt ausdrückliche Fail-Closed-Ausfallregeln.
4. **LogSink** erhält nach einer Sicherheitsprüfung ausschließlich **minimierte, bereinigte technische/Sicherheitsereignisse**. LogSink-Ausfälle dürfen normale berechtigte CRUD-Operationen **nicht** blockieren. Jeder lokale Spool/Retry ist begrenzt; ein nicht vorhandener dauerhafter Worker wird nicht vorausgesetzt.
5. **Pflicht-Audit erfolgreicher V1-Datenänderungen** bleibt **in derselben lokalen Datenbanktransaktion**. Auditfehler rollen die Änderung zurück. Abgewiesene Anfragen erzeugen **separate geschützte Sicherheitsereignisse**; stärkere Haltbarkeit solcher Ablehnungsereignisse benötigt eine eigene Freigabe.
6. **IP-/Netzzugangspolitik je Dienst**: Eine Firmen-IP ist keine Identität; Auth/API/LogSink dürfen sich weder auf ungeprüfte Proxy-Header noch auf bloß vermutete Sperren anderer Dienste verlassen.
7. Logische Dienste können denselben physischen Host verwenden, müssen aber Zugänge, Konfigurationen, Angriffsflächen und Fachberechtigungen sicher trennen.

## Konsequenzen

**Nutzen:** eindeutige Schutzgrenzen, keine zusätzliche Netzabhängigkeit für V0, korrekte Trennung von Remote-Log und Transaktionsaudit, später zentrale SASD-Anmeldung ohne fremde Fachrechte, reduzierte LogSink-Risiken bis zur Härtung.

**Kosten:** spätere Vertrags-/Kompatibilitätsarbeit, Claim-/Rollenabbildung, Schlüsselrotation, Ereignis-Schema-/Versionierung, Wiederholungen/Duplikate, Dienstberechtigungen und Monitoring. Ein technischer V0-Key wird durch diese Architektur **nicht automatisch** zur Benutzeranmeldung.

**Sicherheit:** Keine Passwörter, Tokens, Authorization-Header oder vollständigen Personendatensätze in zentralen Ereignissen. HTTPS, private Pfade, begrenzte Berechtigungen und getrennte Instanzen bleiben Pflicht.

## Prüfungen vor Annahme

- [ ] Dienstgrenzen ohne automatische Vergrößerung der V0-MUSS-Anforderungen freigeben.
- [ ] V0-Lokal-Key und V1-Client-Key-/Rollenmodell bestätigen.
- [ ] **V1-F19** hinsichtlich abgewiesener versus erfolgreich committeter Schreibvorgänge präzisieren.
- [ ] `AT-INT-01` bis `AT-INT-12` **nur für tatsächlich freigegebene Integrationen** einplanen.
- [ ] LogSink erst nach Senderauthentifizierung, getrennten Leserrechten, Datenminimierung, Limits und Aufbewahrung integrieren.
- [ ] Tokenstandard, Audience/Issuer, Widerruf, Cache und Ausfallregeln für einen späteren Auth Service separat entscheiden.
- [ ] [IP-ADR-0019](0019-ip-geoblocking.de.md) mit den eigenständigen Diensteingängen abstimmen.
- [ ] Lastenhefte, Pflichtenhefte, Architekturen, Roadmap und Abnahme **in beiden Sprachen** nach jeder formalen Scope-Entscheidung nachziehen.

**Status: Vorgeschlagen.** Dieses Dokument bedeutet weder eine produktive Auth-Service-Integration noch einen gehärteten LogSink oder einen bereits existierenden Verifier-Code.
