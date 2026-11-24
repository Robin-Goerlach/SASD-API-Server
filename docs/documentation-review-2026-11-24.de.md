# SASD API Server – Kritische Prüfung des Gesprächs gegen die Repository-Dokumentation

> **Sprache:** [English (Standard)](documentation-review-2026-11-24.md) | **Deutsch**  
> **Prüfdatum:** 24.11.2026 · **Umfang:** Themen aus diesem SASD-API-Server-Gespräch und die tatsächlich vorhandenen Markdown-Dokumente im Repository.  
> **Status:** Dokumentations-/Architekturreview, **keine** implementierte Funktion, keine ausgeführte Software-Sicherheitsprüfung, keine reale Hostabnahme und keine Freigabe neuer Muss-Anforderungen.

## 1. Ergebnis der kritischen Durchsicht

Die Projektdokumentation ist bereits umfangreich: Es existieren zweisprachige Lasten- und Pflichtenhefte, Architekturen, Roadmap, Teststrategie, Sicherheits-/Betriebsdokumente, AGENTS.md und README. **V0** ist bewusst klein (PHP 8.4, MariaDB, API-Key, freigegebene Ressourcen, unabhängige Instanzen). **V1** baut später mit SQLite, technischen Clientrollen, Audit und OpenAPI darauf auf.

Die Diskussion hat aber **drei wesentliche Lücken beziehungsweise Unschärfen** sichtbar gemacht:

1. **Dienstaufteilung:** Wir haben einen getrennten SASD Auth Service, den Daten-API-Server und den vorhandenen LogSink diskutiert, aber die Zuständigkeiten, Verträge und Ausfallregeln waren noch nicht geschlossen in Lasten-/Pflichtenheften und Architektur erfasst.
2. **IP-Zugangskontrolle:** Länder-/Netzsperren und reine Firmen-Allowlisten sind bereits als **SEC-GEO-001** beschrieben. Die Anforderung bleibt jedoch außerhalb der **nummerierten V0-MUSS-Anforderungen und V0-Abnahme**. Das ist ehrlich, erfordert aber eine bewusste Scope-Entscheidung vor dem endgültigen HTTP-Sicherheitskern.
3. **Logging ist nicht gleich Audit:** Das V1-Lastenheft sprach von Nachweisen erfolgreicher und abgewiesener Schreibversuche; das technische Pflichtenheft sah transaktionales Audit nur für erfolgreiche Änderungen vor. Zwischen **technischen Logs**, **Sicherheits-/Ablehnungsereignissen** und **atomarem Pflicht-Audit** musste genauer getrennt werden.

Zusätzlich gab es **Dokumentationsmängel**: Bei älteren parallelen Dateipfaden fehlten neuere Ergänzungen, manche Traceability-Tabellen hatten fehlerhafte Markdown-Backticks und nachträglich eingefügte Geo-IP-Texte beschädigten teilweise die Formatierung von Überschriften.

## 2. Abgleich aller besprochenen Themen

| Thema aus unserem Gespräch | Dokumentationsstand | Bewertung / Ergebnis |
|---|---|---|
| Universeller, wiederverwendbarer CRUD-Server | V0/V1-Lasten-/Pflichtenhefte, Architektur | **Enthalten**; kleine V0 und explizite Freigaben beibehalten |
| Unabhängige Installationen | V0-F10, V1-F11, Betrieb | **Enthalten**: gleiche Codebasis, eigene URL, DB, Keys, Logs |
| PHP 8.4, IONOS Shared Hosting | Architektur, Roadmap, Runbooks | **Enthalten, aber noch nicht auf realem Host verifiziert** |
| Kein zwingender Docker/Redis/Worker | V0/V1-Entwürfe | **Enthalten**; künftiges Logging darf keinen versteckten Workerbedarf einführen |
| Trennung in Auth Service, API Server, LogSink | Bisher nur vereinzelte Erwähnungen | **Jetzt SEC-INT-001 und ADR-0020**; keine zusätzliche Pflichtabhängigkeit |
| Authentifizierung vs. Datenberechtigung | V0-Key, V1-Rollen | **Präzisiert**: Identität prüfen ist etwas anderes als Ressourcen-/Feldfreigabe |
| Technische Clients vs. Menschen | Ausdrückliche V0/V1-Nichtziele, Sicherheitsrisiken | **Enthalten und präzisiert**; ein öffentlich verteilter API-Key ist kein Benutzerlogin |
| Ausfall eines zentralen Auth Service | Kein früherer zusammenhängender Vertrag | **Jetzt Entwurf**, aber Tokenstandard ist noch zu entscheiden |
| Vorhandener Logging Server | Externes LogSink-Repository | **Sicherheitsrisiko**: aktuelle Lese-/Schreibschnittstelle ungeschützt; speichert Roh-Requests |
| Verhalten bei LogSink-Ausfall | Allgemeine lokale Logs | **Präzisiert**: normales berechtigtes CRUD läuft weiter, Fallback begrenzt |
| Verpflichtendes V1-Schreib-Audit | V1-F19, Pflichtenheft | **Beibehalten**: Fachänderung + Auditdatensatz in derselben DB-Transaktion |
| Abgewiesene Schreibversuche | Bisher widersprüchliche Begriffe | **Präzisiert**: getrennte geschützte Sicherheitsereignisse; Haltbarkeit noch offen |
| Länder-/Embargo-IP-Sperren | SEC-GEO-001, ADR-0019 | **Entwurf vorhanden**, kein automatischer Rechts-/Sanktionsnachweis |
| Ausschließlich Firmen-IP freigeben | SEC-GEO-001, ADR-0019 | **Entwurf vorhanden**: Default-Deny, ausdrückliche Firmen-/VPN-Netze, Deny vor Allow |
| Textdatei oder DB-Tabelle für IP-Rules | SEC-GEO-001 | **Enthalten**: private Datei zuerst, DB-Provider später |
| IPv6, vertrauenswürdige Proxykette | Geo-IP-Konzept, Hosting | **Entwurf vorhanden, realer Provider-Test fehlt** |
| Aussperren des Administrators | Geo-IP-Konzept/ADR | **Enthalten**: Unternehmensausgang prüfen, sicherer Rollback außerhalb API |
| Englische Standardsprache/Deutsch | README, docs/README, Dokumentpaare | **Enthalten**; doppelte alte Pfade müssen synchron bleiben |
| README, Roadmap, AGENTS.md | Repository Root | **Vorhanden** |
| Screenshot des API Servers | API-Beispiele, Architekturdiagramme | **Kein Produktfeature**; reale Screenshots erst bei vorhandener API/OpenAPI/Diagnose und ohne Geheimnisse sinnvoll |

## 3. In dieser Prüfung vorgenommene Ergänzungen

- **Neu:** [SEC-INT-001 – Dienstgrenzen und Integrationsverträge](service-boundaries-and-integrations.de.md) · [English](service-boundaries-and-integrations.md). Beschreibt Auth/API/LogSink-Verantwortlichkeiten, V0/V1-Abgrenzung, spätere Tokenmodelle, Sicherheitsanforderungen an LogSink, die drei Logarten, Verhalten bei Ausfällen, zwölf Integrationstests und offene Entscheidungen.
- **Neu:** [ADR-0020 – Trennung der Dienste (Vorschlag)](adr/0020-auth-api-logging-service-boundaries.de.md) · [English](adr/0020-auth-api-logging-service-boundaries.md), mit Alternativen, Entscheidungen und Konsequenzen.
- **Präzisiert:** **V1-F19** in Lasten-/Pflichtenheften beider Sprachen: erfolgreiche Fachänderungen und Audit atomar; abgewiesene Versuche als separate geschützte Sicherheitsereignisse.
- **Verknüpft:** Neue Dienstgrenzen aus V0/V1-Lastenheften, Pflichtenheften, Architekturen, Roadmaps, Teststrategien, Betrieb, Release und Risikoregister.
- **Ergänzte Risiken:** **R-028** (Auth-Service-/Tokenkopplung) und **R-029** (ungeschützter LogSink/Verwechslung mit Pflicht-Audit).
- **Bereinigt:** Fehlerhafte Markdown-Formatierung in kanonischen Traceability-Tabellen und einige beschädigte Geo-IP-Überschriften.
- **Nicht einfach freigegeben:** SEC-GEO-001/ADR-0019 bleiben **Entwürfe**, keine angeblich laufenden IP-Sperren oder neu gezählten V0-MUSS-Funktionen.

## 4. Noch offene Entscheidungen – nach Dringlichkeit

| Priorität | Aufgabe/Entscheidung | Konsequenz |
|---|---|---|
| **P0 vor echter LogSink-Integration** | LogSink-Sender authentifizieren, Leserrechte trennen, TLS, minimiertes Schema, Retention, Größen-/Ratenlimits und Sicherheits-Negativtests; sonst **nicht integrieren** | Verantwortliche für Logging; Sperre für produktive sensible Events |
| **P0 vor jedem Produktivbetrieb** | Private Dateien außerhalb Webroot, belastbare HTTPS-/Proxyerkennung, minimale DB-Rechte, Backup und Restore tatsächlich prüfen | V0-G0/Hosting |
| **P0 bei vorgeschriebener IP-Sperre** | SEC-GEO-001 formal in V0 einordnen und reale IPv4-/IPv6-/Proxy-/Listen-/Rollbacktests sowie geprüfte Länder-/Firmenpolitik definieren | Sicherheits-/Betreiberfreigabe vor betreffendem Deployment |
| **P1 vor V0-Architektur-Freeze** | Schmale lokale CredentialVerifier-Schnittstelle bestätigen oder begründet weglassen, **ohne** externen Auth-Zwang | V0-HTTP-/Security-Kern |
| **P1 vor V1-F19-Abnahme** | Dauerhaftigkeit, Fehlerverhalten, minimierte Inhalte und Aufbewahrung von **abgewiesenen** Sicherheitsereignissen festlegen | V1-Sicherheits-/Betriebsentscheidung |
| **P1 vor LogSink-Anbindung** | Versioniertes bereinigtes Ereignisschema mit HTTPS/Key, begrenzten Retries, Duplikat-/Retention-Verhalten | Integrationsvertrag; kein Dauerworker voraussetzen |
| **P1 vor zentralem Login** | SASD-Benutzermodell/SSO, JWT vs. Opaque-Token, Issuer/Audience, Signatur, Cache, Widerruf, lokales Rollenmapping und Ausfälle entscheiden | Eigenes Auth-Lasten-/Pflichtenheft/ADR |
| **P1 Dokumentationsqualität** | Bisherige Dateinamen synchron halten oder bewusst auf kontrollierte Verweise umstellen; Links, IDs, Sprachgleichwertigkeit und Markdown regelmäßig prüfen | Dokumentations-/CI-Wartung |
| **P2 spätere README-Bilder** | Erst nach laufendem Code sichere curl-/OpenAPI-Screenshots oder Diagramme ergänzen, niemals Secrets abbilden | Dokumentation, **keine** Funktionsanforderung |
| **P2 spätere Implementierung** | V0-M0/M1 in kleinen getesteten Schritten umsetzen; Dokumentation beweist keinen lauffähigen Server | Release |

## 5. Vorgeschlagene Anforderungen und Entscheidungen gesondert verfolgen

| Kennung | Inhalt | Aktueller Status |
|---|---|---|
| **SEC-GEO-001** | IP-/CIDR-Eingangskontrolle: Länder-/Netzsperren, Firmen-Allowlist und Kombination | **Vorgeschlagen** für V0-G0/V0-M1; noch keine formelle V0-F18 |
| **SEC-INT-001** | Eigenständige Auth-/API-/Logging-Dienstgrenzen, spätere Verifier und geschützte Logging-Verträge | **Vorgeschlagene Querschnittsarchitektur**, keine neue Pflicht zu externen Diensten |
| **ADR-0019** | Datei-/Geo-IP-Regeln, Proxyvertrauen, Fail-Closed und Admin-Rollback | **Vorgeschlagen** |
| **ADR-0020** | Identität, Fachautorisierung, technische Logs, Sicherheitsereignisse und Pflicht-Audit trennen | **Vorgeschlagen** |

**Zählungen bleiben bewusst unverändert:** V0 **17 nummerierte Funktionen und zehn Abnahmeszenarien**, V1 **27 Funktionen (22 MUSS, fünf SOLL) und 16 Abnahmeszenarien**. Die vorgeschlagenen AT-GEO-01–18 und AT-INT-01–12 sind **keine angeblich bereits bestandenen Tests**.

## 6. Empfohlene nächste Beschlüsse

**A – IP-Policy:** Muss SEC-GEO-001 bei **jeder** produktiven V0-Instanz verpflichtend aktiv sein oder nur bei Installationen, deren Betreiber diese Netzwerkzugangsprüfung verlangt? Davon hängen Standardkonfiguration und Release-Gate ab.

**B – Diensttrennung:** ADR-0020 als **Architekturgrenze** akzeptieren, ohne damit automatisch SASD Auth Service oder LogSink als Pflichtkomponenten in V0/V1 einzubauen. Der API Server bleibt damit eigenständig testbar.

**C – Abgewiesene Schreibversuche:** Reicht ein geschütztes lokales Sicherheitsevent mit festgelegter Aufbewahrung, oder verlangen wir einen stärker dauerhaft gespeicherten, getrennten Auditnachweis für Ablehnungen? Ein Remote-Log-POST ist **keine atomare Fachdatenbanktransaktion**.

**D – Pflege der Sprachen:** Kanonische englische und deutsche Dokumentpaare als Arbeitsgrundlage verwenden. Ältere Dateipfade müssen entweder synchronisierte Kopien oder bewusst kontrollierte Zeiger mit erhaltenen Ankern bleiben; vor größeren Änderungen einen prüfbaren Link-/Divergenztest etablieren.

## 7. Grenzen und ehrlicher Stand

Dies ist eine **kritische Prüfung der Dokumentationsinhalte**, kein formaler Satz-für-Satz-Beweis sprachlicher Gleichwertigkeit, kein Penetrationstest, kein rechtliches Exportkontrollgutachten und kein Test des tatsächlichen IONOS-Hostings. Beispiele und technische Annahmen können während der Implementierung zusätzliche Korrekturen benötigen. Annahmeentscheidungen gehören in ausdrücklich akzeptierte ADRs und Lastenhefte; bestandene Tests in reale Testberichte.

**Nächster Meilenstein bleibt V0-G0:** IP-Policy-Scope und Dienstgrenzen entscheiden, Provider-/Sicherheitsbedingungen nachweisen, danach V0-M0/M1 in kleinen überprüfbaren Schritten aufbauen. Aus umfangreicher Dokumentation darf keine Softwarefreigabe abgeleitet werden.
