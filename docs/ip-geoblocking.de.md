# SEC-GEO-001 – IP-Netz- und länderbezogene Zugriffssperren

> **Sprache:** [English (Standard)](ip-geoblocking.md) | **Deutsch**  
> **Status:** **Vorgeschlagene zusätzliche Sicherheitsanforderung für V0** und Architekturergänzung – noch nicht implementiert, getestet, rechtlich freigegeben oder produktiv eingesetzt.  
> **Datum:** 23.11.2026 · **Geltung:** SASD API Server, später auch SASD Auth Service; LogSink-Anbindung optional.  
> **Entscheidung:** [ADR-0019 (Vorschlag)](adr/0019-ip-geoblocking.de.md) · **Technischer Kontext:** [Pflichtenheft V0](technical-specification-v0.de.md) und [Architektur V0](architecture-v0.de.md).  
> **Anforderungskennung:** **SEC-GEO-001** (neue querschnittliche Security-Anforderung; die verbindliche Aufnahme in die nummerierten V0-F-Anforderungen und deren Gesamtzählung ist noch als Scope-Entscheidung zu bestätigen).

## 1. Ausgangslage und Ziel

Der Betreiber muss HTTP-Anfragen **abweisen können, wenn die verlässlich ermittelte Client-IP zu einem konfigurierten gesperrten IP-Netz gehört**. Dazu zählen IP-Netze, die nach einem geprüften Betreiberbeschluss geographischen Gebieten zugeordnet werden, für welche Zugriffsrestriktionen gelten. Die Prüfung unterstützt **IPv4 und IPv6** und benötigt **keine Änderungen am PHP-Programmcode**, wenn die Liste angepasst wird.

Als erste Verwaltungsquelle empfehlen wir eine **geschützte Text-/Konfigurationsdatei**. Eine **Datenbanktabelle als alternativer Regel-Provider** kann später ergänzt werden. Der Schutz vor dem ersten Datenzugriff darf nicht davon abhängen, ob die Anwendungs-MariaDB gerade erreichbar ist.

**Wichtig:** Die Geo-IP-Sperre ist ein zusätzliches Schutzverfahren, aber **kein vollständiger rechtlicher Sanktionsnachweis**. EU-Sanktionen können sich gegen bestimmte Personen, Unternehmen, Produkte, Branchen oder Gebiete richten. Daraus folgt **nicht automatisch**, dass jeder HTTP-Zugriff aus einem bestimmten Staat verboten wäre. Zuständige Rechtsordnung, Dienst, Rechtsgrundlage und gesperrte Gebiete müssen vor Aktivierung fachlich und gegebenenfalls juristisch geprüft werden.

Amtliche Ausgangspunkte:
- [EU-Rat – Arten von Sanktionen](https://www.consilium.europa.eu/en/policies/sanctions-different-types/)
- [Europäische Kommission – Sanktionen und amtliche Quellen](https://finance.ec.europa.eu/eu-and-world/sanctions-restrictive-measures/overview-sanctions-and-related-resources_en)

## 2. Funktionale Anforderung SEC-GEO-001

### 2.1 MUSS-Verhalten bei aktivierter, freigegebener Richtlinie

1. **Früh prüfen:** Die IP-Sperre läuft **vor** Anmeldung, umfangreicher Requestverarbeitung, SQL und fachlicher API-Logik; dasselbe gilt entsprechend für spätere Auth-Endpunkte.
2. **CIDR:** Einzeladressen (IPv4 `/32`, IPv6 `/128`) und ganze Netze einschließlich `/0` werden streng validiert und korrekt verglichen.
3. **Konfigurierbar:** Eine versionierte, nur serverseitig lesbare Datei liefert die Regeln. Keine öffentlich zugängliche CRUD-Schnittstelle darf die Sperrliste ändern.
4. **Treffer:** Der Zugriff wird mit einem neutralen **HTTP 403 Forbidden** abgewiesen – ohne Daten oder sensible Erklärungen. **HTTP 451** ist nur nach gesonderter rechtlicher/vertraglicher Prüfung angemessen, nicht automatisch.
5. **Kein Treffer:** Der Request durchläuft **weiterhin die normale Anmeldung, Berechtigungsprüfung, Validierung und Datenverarbeitung**; fehlender CIDR-Treffer ist keine Authentifizierung.
6. **IPv4 und IPv6:** Normalisierung, IPv4-gemappte IPv6-Adressen, verkürzte IPv6-Schreibweise, Grenzpräfixe und fehlerhafte Adressen müssen getestet werden.
7. **Vertrauenswürdige Client-IP:** Maßgeblich sind die vom Server gelieferte Gegenstellenadresse (z. B. `REMOTE_ADDR`) beziehungsweise Informationen aus einer **nachweislich vertrauenswürdigen Proxykette**. Frei gesendete `X-Forwarded-For`-, `X-Real-IP`-, `CF-Connecting-IP`- oder `Forwarded`-Header werden **nicht** als Wahrheit übernommen.
8. **Konsistenter Regelstand:** Neue Regeln erst nach vollständiger Prüfung aktivieren; Dateiänderungen atomar übernehmen. Eine teilweise geschriebene oder defekte Liste darf nicht stillschweigend zu „alles erlaubt“ führen.
9. **Ausfallregel:** Wenn die Sperre als **verpflichtend/aktiv** konfiguriert ist, muss eine fehlende, unlesbare, ungültige oder gemäß Ablaufregel veraltete Liste **fail closed** wirken. Ein kontrollierter **503** kann diesen Ausfall von der regulären **403-Sperre** unterscheiden.
10. **Datenschutz und Nachweis:** Nur notwendige, bereinigte Ereignisdaten protokollieren – etwa Request-ID, Regelversion, Regelkennung/Ländercode und Ergebnis. IP-Adressen können personenbezogene Daten sein.
11. **Prüfbarkeit:** IPv4-/IPv6-, Header-, Proxy-, Fehler- und Update-Tests mit synthetischen Netzen; keine externe Geo-IP-Webanfrage pro HTTP-Request.

### 2.2 Konfigurierbare Betreiberentscheidung

Der Betreiber legt ausdrücklich fest, **welche Netze/Gebiete und warum** gesperrt werden und für welche Dienste die Richtlinie gilt. In Entwicklungsumgebungen darf sie standardmäßig deaktiviert bleiben. **Wenn eine produktive Installation rechtlich oder durch Betreiberbeschluss Geo-Blocking benötigt, muss sie die Sperre aktivieren und einen validierten, nicht leeren Regelstand vorweisen, bevor geschützte Anfragen verarbeitet werden.**

Ein Prüfmodus `monitor` kann Fehlzuordnungen sichtbar machen. Er blockiert aber **nicht** und erfüllt deshalb keine verpflichtende Sperrauflage.

## 3. Position in der Serverarchitektur

~~~mermaid
flowchart TD
    Request["HTTPS-Request"] --> IP["TrustedClientIpResolver"]
    IP -->|Ungültig / nicht vertrauenswürdig| Bad["Sicher abweisen"]
    IP -->|Verifizierte IP| Gate["IpAccessPolicy"]
    Rules["Geprüfte lokale Regeln<br/>IPv4 + IPv6 CIDR"] --> Gate
    Gate -->|Gesperrt| Deny["403 Forbidden<br/>Bereinigtes Ereignis"]
    Gate -->|Erforderliche Liste fehlerhaft| Unavailable["503 Fail Closed"]
    Gate -->|Nicht gesperrt| Auth["Authentifizierung / API-Key"]
    Auth --> Policy["Ressourcen- und Feldberechtigungen"]
    Policy --> API["CRUD Service / Datenbank"]
    Gate -.->|"Bereinigtes Ereignis; nicht blockierend"| Log["Privates lokales Log<br/>später optional LogSink"]
~~~

**Platzierung:** an der HTTP-Eingangsgrenze, nachdem Client-IP und Transportinformationen belastbar ermittelt wurden, jedoch **vor** teuren Verarbeitungsschritten und der Authentifizierung. Die Prüfung gehört nicht in einen SQL-Repository-Adapter oder jeden Controller einzeln.

Denkbare, noch nicht implementierte Schnittstellen:

~~~text
ClientIpResolver.resolve(ServerRequest, TrustedProxyPolicy): ValidatedIp
NetworkRuleSource.loadValidatedSnapshot(): IpPolicySnapshot
IpAccessPolicy.evaluate(ValidatedIp, IpPolicySnapshot): Allow | Deny | Unavailable
~~~

Ein SASD API Server muss ohne zentralen Auth Service lauffähig bleiben. Wird ein eigener Auth Service eingesetzt, braucht **dieser eine gleichwertige Prüfung an seinem Eingang**. Die Data-API darf nicht blind auf die Filterung eines fremden Dienstes vertrauen.

### 3.1 Sperre am Netzwerkrand

Eine verlässlich kontrollierte Firewall, ein Reverse Proxy oder eine WAF kann Anfragen häufig **vor dem PHP-Aufruf** effizienter sperren. Der PHP-Schutz kann zusätzlich bestehen bleiben. Auf Shared Hosting ohne solche Möglichkeiten erfolgt die Prüfung so früh wie sicher möglich im Front Controller.

## 4. Empfehlung V0: geschützte Datei statt DB-Abhängigkeit

### 4.1 Richtlinien-Konfiguration

Ausschließlich **illustrative Testkonfiguration**, keine echte Embargo-Länderliste:

~~~json
{
  "schemaVersion": 1,
  "ipAccess": {
    "mode": "enforce",
    "source": "file",
    "cidrFile": "PRIVATE_CONFIG_DIR/blocked-networks.tsv",
    "required": true,
    "rejectWhenUnusable": true,
    "maxSnapshotAgeHours": 168
  }
}
~~~

`PRIVATE_CONFIG_DIR` steht für ein vom Betreiber bestimmtes **geschütztes Verzeichnis außerhalb des Document Root**, nicht für einen vorgegebenen Dateipfad. Der spätere ConfigLoader prüft Schema, Dateipfad, Rechte, Dateigröße, Versions- und Ablaufdaten.

**Das Beispiel ist ein Entwurf**, kein bereits lesbares SASD-Konfigurationsformat.

### 4.2 Textdatei mit Netzen

Vorgeschlagen wird eine UTF-8-**TSV-Datei** (Tabulator-getrennt), je Zeile CIDR-Netz, ISO-3166-Länderkürzel und optional Regelkennung. Die Liste enthält **nur effektiv zu sperrende Netze**, die aus einem freigegebenen Regelbestand stammen.

~~~text
# blocked-networks.tsv — NUR SYNTHETISCHE TESTDATEN
# policy-version: test-001
# generated-at: 2026-11-23T12:00:00Z
# valid-until: 2026-11-30T12:00:00Z
# source: synthetic-test-fixture
# format: cidr<TAB>country_iso2<TAB>rule_id
198.51.100.0/24	ZZ	TEST-V4-001
2001:db8::/32	ZZ	TEST-V6-001
~~~

**Wichtig:** `198.51.100.0/24` und `2001:db8::/32` sind reservierte Dokumentations-/Testnetze; `ZZ` ist **nur ein Testplatzhalter**. Sie repräsentieren **kein** reales Embargoland. Für manuell gesperrte einzelne Netze ohne Länderbezug ist eine separate nachvollziehbare Regelkategorie zu verwenden, statt fälschlich einen Ländercode zuzuordnen.

Für den Produktivbetrieb wird ein **geprüfter, lizenzkonform nutzbarer, datierter IP-Länder-Datenbestand** mit einer **separat freigegebenen Länder-/Gebietspolitik** verknüpft. Keine unbekannten Listen aus dem Internet unkontrolliert importieren und keine externe Geo-IP-Webanfrage bei jeder Datenanforderung.

### 4.3 Länderliste und IP-Bereiche sind verschiedene Daten

**Ein Landesname oder ISO-Code reicht nicht aus, um alle zugehörigen IP-Bereiche zu kennen.** Wir benötigen zwei Quellen:

1. **Betreiberregel:** Welche Länder, Gebiete oder einzelnen IP-Netze sollen gesperrt werden?
2. **IP-Zuordnung:** Welche IPv4-/IPv6-Netze sind diesen Gebieten zum fraglichen Zeitpunkt zugeordnet?

Bei wenigen Netzen reicht eine kleine CIDR-Datei. Wenn **ganze Länder** abgedeckt werden sollen, wäre eine sequentielle Suche durch Hunderttausende CIDR-Einträge **pro PHP-Request** zu teuer. Dann sollte eine **lokale Geo-IP-Länderdatenbank** oder ein **offline erzeugter, indizierter Präfixbestand** verwendet werden. Die Zuordnung ist eine technische Datengrundlage, **keine gesetzliche Verbotsliste**.

Ein späterer `NetworkRuleSource` könnte eine Tabelle mit `cidr`, `country_code`, `reason`, `enabled`, `source_version`, `updated_at_utc` und Freigabemetadaten lesen. Die frühe Zugriffsprüfung darf aber nicht von der **Anwendungsdatenbank** abhängen, die erst nach der Schutzprüfung kontaktiert werden soll.

### 4.4 Sichere Pflege und Aktualisierung

- Aktualisierungen nur aus **dokumentierter, vertrauenswürdiger Datenquelle** und nach Lizenzprüfung.
- Länder-/Gebietssperrentscheidung und Geo-IP-Datenerneuerung **getrennt** prüfen.
- Neue Datei zunächst **privat temporär** erstellen; Syntax, Präfixe, Duplikate, Überlappungen, Datenmenge, Version und Ablauf validieren.
- Hash/Signatur prüfen, sofern ein geeigneter Vertrauensanker existiert. **SHA-256 allein beweist keinen vertrauenswürdigen Herausgeber.**
- Erst danach vollständigen Regelstand **atomar aktivieren**. Letzten gültigen Snapshot für Rollback schützen.
- Quelle, Version, Datum, Prüfer, Unterschied zur Vorversion und Freigabe dokumentieren.
- Cache-/Ladestrategie unter PHP Shared Hosting mit echten Messungen bestimmen; große Textdateien nicht bei jedem Request linear durchsuchen.
- Bei verpflichtend aktivierter Richtlinie und ungültigem/überaltertem Stand **geschützte Datenanfragen sperren** statt eine leere Allow-all-Liste zu laden.

**Für V0 ist keine Pflicht zu Cron, Docker, Redis, Hintergrundworker oder externem Geo-IP-Webdienst nötig.** Eine manuell geprüfte Aktualisierung reicht zunächst.

## 5. IP-Vergleich und Proxy-Vertrauen

- IP-Adressen in PHP mit `inet_pton()` binär parsen. CIDR-Präfixlängen je Familie prüfen; **kein Zeichenkettenpräfix-Vergleich**.
- `/0`, `/32`, `/128`, IPv4-gemappte IPv6-Adressen und verschiedene gültige IPv6-Schreibweisen konsistent behandeln.
- Überlappende Sperrnetze deterministisch bewerten; eine zutreffende Sperrregel gewinnt.
- Private/Loopback-/reservierte IPs nicht ungefragt ausnehmen; etwaige Ausnahmen sind **ausdrückliche** Entwicklungs-/Proxyregeln.
- `REMOTE_ADDR` ist zunächst die **direkte Gegenstelle**, hinter einem Proxy nicht zwingend der Endnutzer.
- Weitergeleitete Header **nur** bei nachweislich vertrauenswürdigen Proxys auswerten; direkter Zugriff auf den Origin darf nicht gleichzeitig offen sein.
- Geo-IP erkennt bei VPN, Proxy, Cloudhost oder Tor möglicherweise **deren Austrittsknoten**, nicht den wirklichen Standort der Person. Diese Grenze gehört in die Betriebs- und Rechtsdokumentation.

## 6. HTTP-Verhalten und Protokollierung

| Fall | Verhalten |
|---|---|
| IP passt auf gültige aktivierte Sperrliste | **403**, neutraler Fehler `access_restricted`, keine Nutzdaten |
| IP nicht gesperrt; Liste gültig | Normale Authentifizierung und Rechteprüfung |
| Client-IP bei verpflichtender Sperre nicht vertrauenswürdig bestimmbar | Sicher abweisen, nicht automatisch erlauben |
| Erforderliche Liste fehlt, ist defekt oder abgelaufen | **503**, `ip_policy_unavailable`, fail-closed |
| Sperre im lokalen Testprofil deaktiviert | Keine Geo-Sperre; API-Key und Ressourcenrechte gelten trotzdem |
| Monitor-Modus | Nur bereinigtes Ereignis, **keine** wirkliche Sperrung |
| Gefälschte Forwarded-Header | Ignorieren; geprüftes Peer-/Proxy-Modell verwenden |
| Separater Auth Service | Eigene Ingress-Prüfung des Auth Service |

Ein Client sollte weder das angeblich erkannte Land noch genaue CIDR-Regeln, Datenquellen oder Rechtsdetails ungeschützt erhalten. **403** ist der normale technische Sperrstatus. **451** braucht eine gesondert geprüfte rechtliche Grundlage und bewusst definierten HTTP-Vertrag.

**Logging:** Nur kleine, bereinigte Sicherheitsereignisse in ein geschütztes lokales Log schreiben. Ein später gehärteter LogSink kann zusätzlich eine Kopie empfangen. **LogSink darf weder den Request blockieren noch vollständige Login-Requests oder Tokens erhalten.** Der derzeit dokumentierte ungeschützte Raw-Body-LogSink ist für unveränderte sicherheitssensible Ereignisse nicht geeignet.

**Datenschutz:** IP-Adressen können personenbezogene Daten darstellen. Zweck, Rechtsgrundlage, Zugriffsschutz, Speicherfrist und mögliche Übermittlung an einen Logging-Dienst sind zu prüfen. Möglichst Regelkennung, Land/Präfix und Request-ID statt dauerhaft vollständiger IPs protokollieren, soweit die notwendige Nachvollziehbarkeit das zulässt.

## 7. Abnahmetests

Neue Testkennungen **AT-GEO-01 bis AT-GEO-12**; sie sind noch **nicht** Bestandteil der bisherigen V0-AT-V0-01–10-Zählung:

| Test | Nachweis |
|---|---|
| AT-GEO-01 | IPv4-CIDR blockiert **vor** Auth/SQL |
| AT-GEO-02 | IPv6-CIDR blockiert |
| AT-GEO-03 | Nicht gesperrte IP muss weiterhin reguläre API-Key-/Rollenprüfung bestehen |
| AT-GEO-04 | Netzgrenzen, /0, /32, /128 und Überlappungen |
| AT-GEO-05 | IPv4-Mapped-IPv6, Normalisierung, ungültige Adressen |
| AT-GEO-06 | Gefälschte X-Forwarded-For-/X-Real-IP-/CF-Connecting-IP-Header umgehen Regeln nicht |
| AT-GEO-07 | Vertrauenswürdige Proxykette, kein direkter Origin-Bypass |
| AT-GEO-08 | Fehlende/defekte/alte Pflichtliste führt zu **fail closed** |
| AT-GEO-09 | Atomares Update, Rollback, kein halbfertiger aktiver Stand |
| AT-GEO-10 | Zwei API-Instanzen haben unabhängige Sperrregeln |
| AT-GEO-11 | 403-Antwort und Security-Logs verraten keine Secrets oder Request-Bodies |
| AT-GEO-12 | Bei ganzen Ländern: Datenstand und Lade-/Matching-Kosten gemessen und begrenzt |

Bei einem späteren Auth Service gelten gleichwertige Eingangstests. Eine blockierte Anfrage darf nicht über andere API-Pfade oder ein fehlendes Plugin an geschützte Daten gelangen.

## 8. Risiken und offene Entscheidungen

| Risiko | Folge | Maßnahme |
|---|---|---|
| Fehlerhafte Geo-IP-Zuordnung | Zu viel oder zu wenig gesperrt | Gepflegte Datenquelle und Korrektur-/Freigabeprozess |
| VPN, Proxys, Tor, Cloudhost | Erkannt wird Austrittsort, nicht Wohnsitz | Zusätzliche Schutzmaßnahme, kein Identitäts-/Sanktionsnachweis |
| Gefälschte HTTP-IP-Header | Bypass | Verifizierte Proxykette und geschützter Origin |
| IPv6-/Mapped-IP-Unterschiede | Bypass | Vollständige binäre Präfixtests |
| Veraltete Zuordnungsdatei | Neue Netze bleiben unentdeckt | Ablaufdatum, Aktualisierung, fail closed bei Pflichtregeln |
| Sehr große Ländersperrliste | Shared Hosting wird langsam | Indizierter Geo-IP-/CIDR-Snapshot, gemessene Performance |
| Datei bei Update halb geschrieben | Temporäres fail-open | Atomares Aktivieren, validierter vorheriger Snapshot |
| Externer Logging-Ausfall | Request verzögert/abgewiesen | Lokales geschütztes Log, optionale spätere Übermittlung |
| Embargo ≠ pauschales Landesverbot | Rechtliche Fehleinstufung | Zuständiges Recht, betroffene Leistung und Gebiete prüfen |
| Weitergabe von IP-Daten | DSGVO-Risiken | Datenminimierung, Zugriffsschutz, Speicherfristen |

### Entscheidungen in V0-G0

1. Anforderung formal als **V0-MUSS** oder bewussten späteren Scope aufnehmen; dann die nummerierten V0-F- und Abnahmetabellen **konsistent** aktualisieren.
2. Rechtsraum und gesperrte Dienste/Gebiete **explizit** freigeben, keine willkürliche Länderverbotsliste.
3. Entscheiden: wenige manuelle CIDR-Regeln oder flächendeckender Geo-IP-Länderdatenbestand mit lokaler Indizierung.
4. Feststellen, wie der reale Hostinganbieter die Client-IP liefert und Proxy-Bypass verhindert.
5. Datenaktualisierung, maximale Gültigkeit, Fehler-/Fail-Closed-Verhalten und Rollback festlegen.
6. Geltung für API, Auth, Health und andere HTTP-Routen verbindlich bestimmen.

**Derzeitige Empfehlung:** geschützte Datei zuerst, Datenbank als späterer austauschbarer Provider, vertrauenswürdige IP-Ermittlung und fail closed bei verpflichtender aktiver Sperre. **Es wurde noch kein echtes Land und kein reales IP-Netz zur Sperrung festgelegt.**
