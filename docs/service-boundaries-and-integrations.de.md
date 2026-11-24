# SEC-INT-001 – Dienstgrenzen und Integrationsverträge der SASD-Architektur

> **Sprache:** [English (Standard)](service-boundaries-and-integrations.md) | **Deutsch**  
> **Stand:** 24.11.2026 · **Status:** **Architektur- und Anforderungsentwurf**, nicht implementiert oder formell abgenommen.  
> **Verknüpft mit:** [Lastenheft V0](requirements-v0.de.md), [Lastenheft V1](requirements-v1.de.md), [Pflichtenheft V0](technical-specification-v0.de.md), [Pflichtenheft V1](technical-specification-v1.de.md), [IP-Zugangsregeln](ip-geoblocking.de.md), [ADR-0020](adr/0020-auth-api-logging-service-boundaries.de.md).  
> **Tatsächlicher Stand:** Ein zentraler Identitätsdienst ist **keine bereits freigegebene API-Server-Abhängigkeit**. Das vorhandene [SASD-LogSink-Repository](https://github.com/Robin-Goerlach/LogSink) dokumentiert eine experimentelle Implementierung, deren aktuelle HTTP-Schreib-/Lesezugriffe **bewusst ungeschützt** sind und die den POST-Body unverändert speichert. Für produktive Sicherheitslogs ist dieser Stand ohne Härtung ungeeignet.

## 1. Ausgangspunkt und Zielarchitektur

Unsere bisherige Diskussion trennt drei Verantwortlichkeiten:

1. **Authentifizierung und Identität:** Wer ist der Benutzer beziehungsweise technische Client? Wer darf Anmeldedaten verwalten, Sessions eröffnen oder später Tokens ausstellen?
2. **Autorisierung und Anwendungsdaten:** Welche Ressourcen, Methoden, Felder, Filter, Relationen und Datensätze darf diese Identität bearbeiten?
3. **Technisches Logging und Sicherheitsereignisse:** Was wird datensparsam protokolliert, zentral ausgewertet und für Fehlersuche beziehungsweise Missbrauchserkennung benötigt?

Die Dienste sollen **logisch eigenständig** sein, ohne dass V0 zwingend drei laufende Server voraussetzt. Sie können später unter getrennten URLs auf demselben Host oder auf verschiedenen Maschinen betrieben werden.

~~~mermaid
flowchart LR
    C["Vertrauenswürdige Anwendung / Backend"] -->|"Später optional Anmeldung"| A["SASD Auth Service"]
    A -->|"Zeitlich begrenztes Token"| C
    C -->|"HTTPS + Zugangsdaten"| API["SASD API Server"]
    API -->|"Lokale Token-/Key-Prüfung und Ressourcenrechte"| Policy["Autorisierung"]
    Policy --> DB[("Instanzdatenbank")]
    A -.->|"Nur bereinigte Ereignisse"| L["Gehärteter LogSink (optional)"]
    API -.->|"Nur bereinigte Ereignisse"| L
    API -->|"Pflicht-Audit innerhalb derselben V1-DB-Transaktion"| DB
~~~

## 2. Wer ist wofür verantwortlich?

| Verantwortung | Auth Service (später optional) | SASD API Server | LogSink (erst nach Härtung) |
|---|---|---|---|
| Benutzeranmeldung, Passwort, Login/Logout, Session, Refresh/Recovery | **Zuständig, wenn beauftragt** | Kein interaktiver Login im V0-/V1-MUSS-Umfang | Nicht zuständig |
| Technische Zugangsdaten | Später Token-Aussteller denkbar | **V0-API-Key und V1-Client-Keys/Rollen bleiben zunächst lokal** | Eigene, getrennte Writer-/Reader-Credentials |
| Token ausstellen und widerrufen | Später zentrale Identität | Tokens nur nach explizit freigegebener Integration prüfen | Keine Daten-API-Tokens ausstellen |
| Tabellen-/Methoden-/Feld-/Datensatzberechtigung | Keine SQL-Fachentscheidung | **Immer zuständig** | Nicht zuständig |
| Geschäfts-SQL, Constraints, Transaktionen | Nein | **Zuständig** | Nein |
| Audit erfolgreicher V1-Schreibvorgänge | Nicht für API-Fachdaten | **Gleiche Datenbank und derselbe Commit** | Nur optionale **Kopie** |
| Technische Logs / Sicherheitsereignisse | Eigene, bereinigte Ereignisse | Eigene, bereinigte Ereignisse | Optionale Aggregation/Suche nach Freigabe |
| Frühe IPv4-/IPv6-Zugangsprüfung | An eigenem Eingang, falls öffentlich | An eigenem Eingang, wenn aktiviert | Eigene Auth-/Netzregeln |
| OpenAPI und Diagnose | Eigene Verträge | Nur freigegebene Fachdatenmodelle | Geschützte Logging-Schnittstellen |

**Ein gültiges Login, Token oder Firmen-IP-Netz erlaubt niemals automatisch Zugriff auf beliebige Daten.** Die API entscheidet selbst über ihre lokalen Ressourcen- und Rollenregeln.

## 3. Zuweisung zu den Versionen

### V0.1.0 – unabhängig

- Ein installationsspezifischer technischer **API-Key** und explizite Ressourcen-/Feld-/Methodenfreigaben wie im bestehenden Lastenheft vorgesehen.
- Eine **kleine, austauschbare Verifier-Grenze** (z. B. `CredentialVerifier`) kann bereits mit dem lokalen Key arbeiten. Sie darf V0 **nicht** unnötig um OAuth/OIDC, JWT, zentrale Benutzerverwaltung oder einen Remote-Request erweitern.
- **Geschützte lokale technische Protokolle**, kein Pflichtaufruf an LogSink.
- Freigabe-/Sperrlisten für IP-Netze bleiben, falls in V0 bestätigt, eine **frühe HTTP-Eingangskontrolle** je Installation.
- Kein Zwang zu zentralem Auth Service, Redis, Worker, separater Log-Infrastruktur oder Internetverbindung für einen gewöhnlichen lokalen CRUD-Aufruf.

### V1.0 – technische Identitäten, Rollen und lokales Audit

- **V1-F12/F13:** mehrere technische Schlüssel, Widerruf/Rotation, Rollenrechte auf Ressourcen, Methoden und Felder. Das begründet **keine Pflicht**, einen externen Auth Service bereitzustellen.
- **V1-F19:** Zwei getrennte Zusagen:
  1. **Erfolgreiche Datenänderungen** erzeugen einen Pflicht-Auditeintrag **in derselben Anwendungsdatenbanktransaktion**; schlägt das Audit fehl, wird die Datenänderung zurückgerollt.
  2. **Abgewiesene Anfragen/Schreibversuche** haben keinen erfolgreichen Datentransaktions-Commit. Sie werden als bereinigte, geschützte **Sicherheitsereignisse** protokolliert. Die benötigte Haltbarkeit dieser Ereignisse muss vor der Freigabe präzisiert werden.
- LogSink-Anbindung bleibt ein **separater optionaler Integrationsschritt**, nachdem HTTPS, Authentifizierung, getrennte Lese-/Schreibberechtigungen, minimiertes Ereignisschema, Limits, Aufbewahrung und Sicherheits-Negativtests nachgewiesen sind.
- Zentraler Auth Service und LogSink dürfen keine versteckten Verfügbarkeitsabhängigkeiten für die bestehenden V1-MUSS-Funktionen werden.

### Später – zentrale Anmeldung und gemeinsame Benutzerkonten

Eine SASD-weite interaktive Anmeldung oder SSO bekommt ein **eigenes Lastenheft, Pflichtenheft, Bedrohungsmodell und Abnahme**. OAuth2/OIDC mit bewährten Komponenten ist ein Kandidat; ein selbst erfundenes Passwort-/Session-/Tokenprotokoll wäre unnötig riskant.

Die Daten-API kann später über einen konfigurierten Verifier Tokens akzeptieren, behält aber **sämtliche Fachberechtigungen**. Der zentrale Auth Service darf nicht bei jedem einzelnen Feldfilter oder SQL-Zugriff beteiligt sein.

**Kennung:** `SEC-INT-001` dokumentiert die diskutierten Schnittstellen als **Querschnittsentwurf**, nicht als zusätzliche angenommene Nummer innerhalb der 17 V0-F- oder 27 V1-F-Anforderungen.

## 4. Vorgeschlagener Vertrag für die Prüfung von Anmeldedaten

~~~text
CredentialVerifier.verify(credential, requestContext) -> VerifiedPrincipal | AuthenticationFailure

VerifiedPrincipal:
  issuer                # geprüfte Quelle der Identität
  subject               # stabile technische / Benutzerkennung
  kind                  # technical_client | human_user
  audience              # geprüfter Ziel-API-Server / Instanz
  scopes_or_roles       # erst nach lokaler Policy-Abbildung verwendbar
  expiresAt?            # erforderlich bei zeitbegrenzten externen Tokens
  authenticationMethod
~~~

Diese Signaturen sind **keine existierenden Klassen**. Ein V0-Key kann eine technische Clientidentität liefern; V1 ergänzt Key-Widerruf und Rollen. Externe JWT-/OIDC-Prüfung bleibt Zukunftsumfang.

Bei späterer Freigabe gelten insbesondere:

- **Asymmetrisch signierte Tokens:** Signatur anhand vertrauenswürdiger Schlüssel prüfen; erlaubte Algorithmen festlegen; `issuer`, `audience`, `exp`, `nbf`, Instanzbindung und zulässige Uhrabweichung prüfen. Kein `alg=none`, keine fremden dynamischen Schlüssel-URLs, keine unbegrenzte Akzeptanz veralteter Schlüssel. Lokales Key-/JWKS-Caching und Widerrufsfristen dokumentieren.
- **Opaque Token / Introspection:** Gegenseitige Authentifizierung der Dienste, Antworten/Cache, Timeouts und Widerruf definieren. Ist eine zwingende Tokenprüfung wegen Ausfall nicht möglich, **fail closed**, keine automatische Erlaubnis.
- **Öffentliche Clients:** Im verteilten Desktopprogramm oder öffentlichen JavaScript ist ein fest eingebauter API-Key kein verlässlich verborgenes Secret. Vertrauenswürdigen Backenddienst oder echte Benutzeridentität verwenden.
- **Rechte lokal abbilden:** Unbekannte Rollen/Principals standardmäßig abweisen; Token-Gültigkeit ist kein globales Administrationsrecht.
- **Migration:** V0-Schlüssel nicht stillschweigend zu zentralen Administrator-/Owner-Tokens umdeuten.

## 5. Drei unterschiedliche Protokollarten

| Art | Beispiel | Haltbarkeit / Fehlerverhalten | Ablage |
|---|---|---|---|
| **Technisches Log** | Request-ID, Methode, Status, grobe Laufzeit und Fehlerklasse | Lokal geschützt; optionales externes Senden **best effort** | JSONL lokal, später gehärteter LogSink |
| **Sicherheitsereignis** | Fehlgeschlagene Anmeldung, ungültiger Key, gesperrte IP, verweigerte Ressource | Datenschutzgerecht und zugriffsgeschützt; Aufbewahrung und Ausfallverhalten ausdrücklich definieren | Privates Security-Log, später gehärteter LogSink |
| **Pflicht-Audit einer erfolgreichen Datenänderung (V1)** | Wer hat wann welche Ressource/ID geändert? | **Atomar mit Datenänderung**; Auditfehler = Rollback | Interne Tabelle `sasd_api_audit_events` in derselben Instanzdatenbank |

### 5.1 Gefundene Formulierungsabweichung im bisherigen Lasten-/Pflichtenheft

Das V1-Lastenheft verlangt Nachvollziehbarkeit erfolgreicher **und abgewiesener** Schreibversuche. Das technische Pflichtenheft sieht eine **transaktionale** Audit-Tabelle jedoch nur für erfolgreiche Schreibvorgänge vor. Dies ist nicht zwangsläufig ein Architekturfehler – abgewiesene Versuche haben keinen erfolgreichen Fachdaten-Commit –, muss aber **klar unterschieden und abgenommen** werden.

Empfehlung: Für **erfolgreiche Schreibvorgänge** Pflicht-Audit in derselben DB-Transaktion; für **abgewiesene Versuche** ein separates, geschütztes Sicherheitsereignis mit Request-ID/Fehlercode. Wenn die Betreiber einen garantiert haltbaren Ablehnungs-Audit-Store brauchen, ist dies **zusätzlich** explizit zu spezifizieren. Der derzeit ungeschützte Rohdaten-LogSink darf keines von beidem stillschweigend ersetzen.

### 5.2 Minimiertes Ereignisformat (Entwurf)

~~~json
{
  "schemaVersion": 1,
  "eventId": "test-event-0001",
  "occurredAtUtc": "2026-11-24T11:00:00Z",
  "sourceService": "sasd-api-server",
  "instanceId": "test-instance",
  "requestId": "test-request-0001",
  "category": "security",
  "action": "ip_access_denied",
  "outcome": "denied",
  "actor": {"type": "anonymous", "id": null},
  "resource": {"name": null, "id": null},
  "reasonCode": "ip_policy_denied",
  "policyRuleId": "RULE-TEST-01"
}
~~~

Dies ist **kein** implementierter LogSink-API-Vertrag. Später müssen Schemas, Ereignis-IDs, UTC, Limits, Schemaänderungen, Duplikate, Wiederholung und Aufbewahrung geprüft werden. **Nicht protokollieren:** Passwörter, API-Keys, `Authorization`-/Cookie-Header, Tokens, SQL-Bindings, vollständige Request-Bodies, personenbezogene Datenfelder und ungeprüft vollständige Client-IP-Adressen.

### 5.3 Härtung des vorhandenen LogSink

Der dokumentierte Entwicklungsstand kennt `POST /api/logs`, `GET /api/logs`, ungeschützte Lese-/Schreibzugriffe und die Speicherung des unveränderten Request-Bodys als `raw_message`. Vor produktiver Sicherheitsintegration sind nötig:

1. **HTTPS, Authentifizierung der Sender, separate Leser-/Schreiberrechte**, Widerruf und sichere Schlüssel.
2. **Validiertes, minimiertes Ereignisschema** statt ungeprüfter Rohdatenübernahme.
3. Body-/Rate-/Abfrage-/Aufbewahrungslimits, geschütztes DB-/Datei-Backup.
4. Kein öffentliches Lesen von Security-Logs; persönliche Daten minimieren und löschen können.
5. Negativtests für falsche/entzogene Keys, unbefugte Leser, ungültige Events, Wiederholungen, Log-Injection und Netzausfälle.
6. Versionierten Integrationsvertrag dokumentieren; einen anderen gehärteten Logging-Service erst nach tatsächlicher Prüfung seiner Sicherheit berücksichtigen.

## 6. Verfügbarkeit und Fehlerverhalten

| Störung | Erforderliches Verhalten | Bemerkung |
|---|---|---|
| Lokaler V0-Key nicht verfügbar | Geschützte Datenzugriffe ablehnen / kontrollierter 503 | Kein Rückfall auf anonym |
| Späterer zentraler Auth Service ausgefallen, aber **lokal prüfbares gültiges signiertes Token** | Prüfung kann nach den festgelegten Gültigkeits-/Signatur-/Key-Cache-/Widerrufsregeln weiter funktionieren | Keine neuen Logins, keine pauschale Annahme alter Keys |
| Späterer Auth Service ausgefallen, **Opaque-Introspection nötig** | **Fail closed** | Timeout darf nicht zu Allow führen |
| LogSink ausgefallen/langsam | Normale berechtigte CRUD-Verarbeitung läuft weiter | Privates lokales Log, optional begrenzter Spool/Retry |
| Pflicht-Audit für V1-Schreibvorgang fehlgeschlagen | **Datenänderung rollbacken**, kontrollierter Fehler | Remote-Log-POST ist kein Ersatz |
| Verpflichtende IP-Richtlinie ungültig/nicht verfügbar | **Fail closed**, kontrollierter 503 | Regel-Treffer: 403 |
| Fachdatenbank nicht erreichbar | Kontrollierter 500/503 | Keine falsche erfolgreiche leere Liste |
| Login/Auth/Schreibzugriff abgewiesen | Definierte 401/403/404 + minimiertes Sicherheitsevent | Kein erfolgreicher Business-Audit-Datensatz |

**Wichtig im PHP Shared Hosting:** „Asynchron“ bedeutet nicht automatisch einen dauerhaft laufenden Hintergrundworker. Ein optionaler privater Spool muss nach Größe, Wiederholungszahl und Alter begrenzt sein; ein Betreiberjob oder geprüftes opportunistisches Senden kann später ergänzt werden. Keine unendlichen Retries, nicht zu Lasten der Datensicherheit.

## 7. IP-Eingangssperre für jeden öffentlich erreichbaren Dienst

- [SEC-GEO-001](ip-geoblocking.de.md) ist **Netzzugangskontrolle**, keine Benutzeranmeldung und keine Fachautorisierung.
- Der API Server und ein späterer eigenständiger Auth Service brauchen **jeweils eigene** Regeln für öffentliche Eingänge. Ein öffentlich erreichbarer LogSink benötigt weiterhin Sender-/Leserauthentifizierung, selbst wenn Firmen-IP-Filter vorgeschaltet sind.
- Firmenmodus `defaultAction=deny`: nur freigegebene CIDR weiter; **explizite Sperre vor Freigabe**; fehlende Pflichtdatei → fail closed.
- Client-IP ausschließlich aus nachweislich vertrauenswürdiger Proxykette, IPv4 und IPv6 vollständig, HTTPS erzwingen.
- Instanzen dürfen Schlüssel, Datenbank, Rollen, Logs und IP-Richtlinien nicht unbeabsichtigt gemeinsam verwenden.

## 8. Vorgeschlagene Integrationstests

Diese IDs sind **noch keine verpflichtenden V0-/V1-Abnahmetests**, sondern eine Entscheidungs- und Testgrundlage:

| ID | Geplanter Nachweis |
|---|---|
| AT-INT-01 | V0-CRUD funktioniert ohne Auth Service, LogSink, Worker und externe Netzabfragen |
| AT-INT-02 | Lokaler V0-API-Key ersetzt keine Ressourcen-/Methoden-/Feldrechte |
| AT-INT-03 | Späterer externer Principal bekommt nur instanzspezifisch freigegebene Rollen |
| AT-INT-04 | Falsche Token-Signatur, issuer, audience, Ablauf, Algorithmus sicher abgewiesen |
| AT-INT-05 | Auth-Service-Ausfall: Signierte und opaque Tokens werden nach getrennten Ausfallregeln behandelt |
| AT-INT-06 | LogSink-Ausfall blockiert normale CRUD-Operationen nicht; begrenzte Fallback-Ablage |
| AT-INT-07 | Pflicht-Auditfehler in V1 rollt die Fachdatenänderung zurück |
| AT-INT-08 | Abgelehnter Schreibversuch liefert bereinigtes, zugeordnetes Sicherheitsevent, keinen fiktiven Commit |
| AT-INT-09 | Gehärteter LogSink schützt Sender/Leser und nimmt keine Secrets/ganzen Bodies an |
| AT-INT-10 | Keine Tokens/Secrets/Rohkörper in Logs, Fehlern oder öffentlicher Diagnose |
| AT-INT-11 | Keine Umgehung von API-/Auth-/Logging-Eingangspolitik über alternative Pfade/Proxys |
| AT-INT-12 | Zwei API-Instanzen bleiben hinsichtlich Keys, Rollen, DB, Audit, Logs und IP-Regeln getrennt |

## 9. Entscheidungen und Folgearbeiten

| Entscheidung | Stand | Zeitpunkt |
|---|---|---|
| Lokaler V0-API-Key und unabhängig lauffähiger CRUD-Server | **Bereits bestehender V0-Umfang** | V0-Kern |
| Lokale technische Client-Identitäten und Rollen für V1 | **Bereits bestehender V1-Umfang** | V1-Sicherheit |
| Zentraler Auth Service für SASD-Benutzer und SSO | **Option, offen** | Separates Auth-Lastenheft |
| Signiertes Token oder Opaque-Token-Introspection | **Offen** | Vor externer Auth-Anbindung |
| LogSink für bereinigte aggregierte Events | **Optional, durch Sicherheitsstand des LogSink blockiert** | Nach Härtung und Tests |
| Pflicht-Audit in lokaler DB-Transaktion | **Bestehende V1-Zusage** | V1-Schreiboperationen |
| Nachvollziehbarkeit und Dauerhaftigkeit abgelehnter Schreibversuche | **Formulierungs-/Umfangsentscheidung offen** | V1-F19-Abnahme |
| IP-Policy als nummerierte V0-MUSS-Anforderung | **SEC-GEO-001 vorgeschlagen** | V0-G0-Scope |
| Speicherfristen, Log-Leser, Retry/Spool und Vorfallregeln | **Betreiberentscheidung offen** | Vor produktiver Logging-Integration |

**Empfehlung:** [ADR-0020](adr/0020-auth-api-logging-service-boundaries.de.md) als Abgrenzung prüfen, **ohne** damit schon einen zentralen Auth Service oder LogSink produktiv einzubauen. Die V1-F19-Formulierung zu abgelehnten Schreibversuchen und die Freigabe von SEC-GEO-001 für V0 werden separat entschieden.
