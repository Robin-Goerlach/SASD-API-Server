# ADR-0019: Konfigurierbare IP-Zugangskontrolle (Allowlist, Denylist und Geo-IP)

> **Sprache:** [English (Standard)](0019-ip-geoblocking.md) | **Deutsch**  
> **Status:** **Vorgeschlagen – nicht angenommen** · **Vorschlag vom:** 23.11.2026  
> **Ziel:** V0-G0 Architekturprüfung / V0-M1 HTTP-Sicherheitsfilter, nach bewusster Scope-Freigabe  
> **Anforderung:** [SEC-GEO-001](../ip-geoblocking.de.md) · **Risiken:** IP-Header-Spoofing, Geo-IP-Fehlzuordnung, veraltete Daten, Sanktionsrecht, Verfügbarkeit.

## Kontext

Der Betreiber möchte HTTP-Zugriffe aus geographisch beschränkten Ländern beziehungsweise einzelnen IP-Bereichen abweisen können. Die Lösung soll auf **PHP 8.4 und Shared Hosting** funktionieren, ohne zwingende weitere MariaDB-Abfrage, Redis, Hintergrundworker oder externe Geo-IP-Webanfrage pro Request.

IP-Adressen lassen sich durch unkontrollierte Proxy-Header vortäuschen. IPv6 und VPNs erschweren die Einordnung. **Sanktionen verbieten nicht automatisch jeden Internetzugriff aus einem ganzen Land**, und ein ISO-Länderkürzel identifiziert noch keine IP-Netze.

## Entscheidungsfaktoren

1. **Frühzeitige Sperrung** vor Authentifizierung, SQL und Anwendungslogik.
2. Betreiber kann eine **geprüfte, versionierte** Liste ohne PHP-Codeänderungen pflegen.
3. **Keine Abhängigkeit** von fachlicher MariaDB oder zentralem Auth Service.
4. Bei ausdrücklich verpflichtender, aber defekter Richtlinie **fail closed**.
5. Vollständige IPv4-/IPv6-CIDR-Unterstützung, kontrollierte Proxykette und atomare Updates.
6. Keine externe Geo-IP-Abfrage pro Request.
7. Zuständiges Sanktionsrecht und den betroffenen Dienst **fachlich prüfen** statt pauschale Länderlisten behaupten.
8. V0 klein halten; DB-gestützte Pflege kann später hinzukommen.

## Betrachtete Alternativen

| Option | Vorteil | Nachteil |
|---|---|---|
| **A. Geschützter lokaler CIDR-Snapshot (zunächst empfohlen)** | Nachvollziehbar, ohne DB-Abhängigkeit, Shared-Hosting-tauglich | Regelpflege; für ganze Länder möglicherweise viele Netze |
| **B. Blockierliste in Anwendungsdatenbank** | Strukturierte Pflege und Abfragen | Datenbank bereits vor Eingangssperre nötig; Fehler-/Ausfallabhängigkeit |
| **C. Externer Geo-IP-Webdienst pro Request** | Anbieter pflegt die Daten | Datenschutz, Latenz, Ausfälle, Gebühren |
| **D. Firewall/WAF/Reverse Proxy** | Früheres und ressourcensparendes Blockieren | Im Shared Hosting nicht immer kontrollierbar |
| **E. Keine Sperre** | Wenig Aufwand | Anforderung nicht erfüllt |

## Vorgeschlagene Entscheidung

**Option A vorschlagen:** `ClientIpResolver`, `IpAccessPolicy` und `NetworkRuleSource` bilden einen eigenständigen HTTP-Sicherheitsfilter. Nur eine **verifizierte Peer-/Proxy-IP** wird ausgewertet, niemals beliebige Client-Header. Bei Treffern kommt ein neutraler **403**. Ist eine ausdrücklich erforderliche aktive Liste defekt, fehlt sie oder ist sie zu alt, erfolgt ein **fail-closed mit kontrolliertem 503**.

**Länder-/Gebietspolitik und Geo-IP-Netzzuteilung getrennt halten.** Ganze Länder erfordern gegebenenfalls eine lizenzkonforme lokale Länderdatenbank oder einen offline erzeugten **indizierten** Präfixbestand, nicht eine lineare Suche über Hunderttausende Netze bei jedem Request. Eine spätere Datenbankquelle darf denselben Providervertrag implementieren, darf aber nicht die einzige frühe Schutzschicht werden.

Wenn eine vertrauenswürdige WAF/Firewall bereits am Netzwerkrand sperren kann, soll sie zusätzlich genutzt werden. Auth Service und LogSink bleiben für die frühe Entscheidung **optional**. Ein eigenständiger Auth Service benötigt eine eigene Eingangskontrolle.

Es kann die Betriebszustände `disabled`, `monitor` und `enforce` geben; **nur enforce** erfüllt eine verpflichtende Sperranforderung. **Diese ADR legt keine konkreten Embargoländer oder produktiven Netze fest.**

### Erweiterte Anforderung: ausschließlich freigegebene Firmen-IP-Adressen (24.11.2026)

Dieselbe Eingangskontrolle soll **Unternehmens-Allowlist** und **Länder-/Netz-Denylist** unterstützen. Dafür werden eine ausdrücklich konfigurierte **`defaultAction`** und zwei getrennte, validierte Regelquellen vorgeschlagen:

| Betrieb | Standard | Erforderliche Datei | Wirkung |
|---|---|---|---|
| Denylist | `allow` | `blocked-networks.tsv` | Treffer sperren, andere weiter zur Authentifizierung |
| Allowlist | **`deny`** | `allowed-networks.tsv`, nicht leer | Nur freigegebene Firmen-/VPN-IP weiter zur Authentifizierung |
| Kombiniert | `allow` oder `deny` | Konfigurierte Allow-/Deny-Dateien | **Explizite Sperre vor expliziter Freigabe vor Standardaktion** |

**Priorität:** Eine kleinere Allow-CIDR darf eine überlappende Deny-CIDR nicht automatisch aufheben. Teilnetze eines erlaubten Firmennetzes können ausdrücklich gesperrt bleiben. Ohne Regel und `defaultAction=deny` wird abgewiesen. Eine freigegebene IP ersetzt **niemals** API-Key/Token, Rollen oder Ressourcenrechte.

Bei `defaultAction=deny` ist eine unerwartet leere/fehlende Allowliste ein **Konfigurationsfehler (503, fail closed)**, keine Erlaubnis für alle. Vor Aktivierung Firmen-/VPN-Austrittsnetze für **IPv4 und IPv6** prüfen und eine **separat gesicherte Host-/Datei-Rollbackmöglichkeit** vorsehen. Keine „Admin-IP“-Hintertür oder HTTP-Bypass. Regeldateien bleiben außerhalb des Webroots und werden atomar aktualisiert.

Pro Installation können verschiedene Regeln gelten. API, Auth, Query-Routing-Fallback, OpenAPI und geschützte Admin-Endpunkte dürfen die Filterung nicht umgehen. IP-Adressen können bei Unternehmen dynamisch oder durch NAT geteilt sein: Das ist **Netzzugangskontrolle**, keine Nutzeridentität.

**Neue Testfälle:** `AT-GEO-13` bis `AT-GEO-18`: Firmen-Allow, nicht freigegebene IP blockiert, Konfliktpriorität, ungültige/leere Allowlist führt zu Fail-Closed, IPv4-/IPv6-/VPN-/Proxy-Verhalten und sicheres Rollback. Die bisherigen `AT-GEO-01` bis `AT-GEO-12` bleiben gültig.

**Status:** Nur eine vorgeschlagene Erweiterung. Die ADR ist weiterhin **nicht angenommen**; die Aufnahme in die bisher 17 nummerierten V0-F-MUSS-Anforderungen erfordert eine bewusste Freigabe.

## Konsequenzen

**Nutzen:** wiederverwendbarer frühzeitiger Schutz, unabhängige Installation, geprüfte Regeldateien, klare Fehlersemantik und spätere austauschbare Regelquellen.

**Kosten/Risiken:** Datenaktualisierung, VPN-Fehlzuordnungen, IPv6-/Mapped-IP-Kanten, Provider-Konfiguration, mögliche fälschliche Sperrungen und reduzierte Verfügbarkeit bei Pflichtregel-Ausfall.

**Sicherheit:** geschützte, vollständig validierte und atomar wechselbare Regeldateien. Ein CIDR-Treffer verweigert, aber ein Nichttreffer **gewährt noch keinen** Zugriff. Keine alternativen Routen mit Bypass.

**Datenschutz:** IP-Verarbeitung benötigt Zweck, Speicherfristen, Zugriffsrechte und Datenminimierung; erst gehärteter LogSink erhält maximal bereinigte Ereignisse.

**Recht:** Für EU-/deutsche Export- und Sanktionsvorgaben braucht es eine gesonderte Prüfung. Eine IP-Adresse ist **kein beweiskräftiger Nachweis** für Wohnsitz, Nationalität oder Listenzugehörigkeit.

## Offene Prüfungen vor Freigabe

- [ ] Scope V0 versus späteres Release festlegen; nummerierte Anforderungs-/Abnahmezählung konsistent halten.
- [ ] IONOS-Client-IP-/Proxy-/TLS-Kette und möglichen WAF-Schutz tatsächlich prüfen.
- [ ] Betroffene Rechtsordnung, Dienstleistung, Länder/Gebiete und zuständige freigebende Stelle festlegen.
- [ ] Datenquelle, Lizenz, Ablaufdatum, Aktualisierung und fail-closed festlegen.
- [ ] IPv4, IPv6, Mapped-Adressen, Header-Spoofing, zwei Instanzen und defekte Regeln testen.
- [ ] Atomare Updates und Rückfall auf vorherigen gültigen Stand nachweisen.
- [ ] Bei Ländersperren Speicher/CPU/Latenz unter echtem Hosting messen.
- [ ] `AT-GEO-01` bis `AT-GEO-12` für tatsächliche Abnahme berücksichtigen.
- [ ] ADR ausdrücklich **annehmen oder ablehnen**, bevor daraus verbindlicher Produktionscode entsteht.

**Diese ADR ist nur vorgeschlagen und ändert die bisherige Zählung von 17 V0-F-Anforderungen nicht automatisch.**
