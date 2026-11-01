# SASD API Server – Architecture Decision Records (ADRs)

> **Status:** ADR-Register vorbereitet. Die nachstehenden Einträge sind **Entscheidungskandidaten, keine bereits freigegebenen Beschlüsse**.  
> Ausgangsbasis: [Architektur V0](../architektur-v0.md), [Architektur V1](../architektur-v1.md), [Risikoregister](../risikoregister.md), [Roadmap](../../ROADMAP.md)

## 1. Zweck

Eine Architecture Decision Record (ADR) hält eine technisch wichtige **Entscheidung samt Begründung** langfristig fest. Ein ausführliches Pflichtenheft beschreibt die Gesamtarchitektur; eine ADR beantwortet dagegen eine einzelne, umstrittene oder langfristig bindende Frage: Welche Alternativen gab es, warum wurde genau diese gewählt, welche Konsequenzen folgen daraus und welche Tests belegen sie?

Wir wollen keine Flut nutzloser Kleindokumente. Eine ADR ist sinnvoll, wenn eine Entscheidung den API-Vertrag, die Sicherheit, Datenbank-Portabilität, Deploymentgrenzen, Versions-/Konfigurationsschema oder längerfristige Wartbarkeit beeinflusst.

## 2. Statusmodell

| Status | Bedeutung |
|---|---|
| Vorgeschlagen | Ein Lösungsvorschlag, noch nicht freigegeben |
| Angenommen | bewusster Beschluss nach Review |
| Ersetzt | durch eine neue ADR ausdrücklich abgelöst |
| Verworfen | Alternative wurde geprüft und abgelehnt |
| Zurückgezogen | Entscheidung derzeit nicht notwendig |

**Wichtig:** Ein Eintrag in der Architektur oder dieses Register allein begründet **noch keinen** „Angenommen“-Status.

## 3. Vorgesehene ADRs V0

| Geplante ID | Frage / Kandidat | Verbindung |
|---|---|---|
| ADR-0001 | PHP 8.4 + PDO ohne Frameworkpflicht für Shared Hosting | Architektur V0 ``3–4 |
| ADR-0002 | Ressourcen/Felder/Methoden explizit per Allowlist | V0-F01/F06, R-003 |
| ADR-0003 | index.php im Webroot, private Dateien außerhalb | R-001/R-006 |
| ADR-0004 | API-Key je V0-Instanz statt Endnutzerverwaltung | V0-F11, R-005 |
| ADR-0005 | kleiner Repository-Port, MariaDB als einziger V0-Adapter | V0-F02–05 |
| ADR-0006 | PUT-Vollersatz/PATCH-Teiländerung und atomarer Einzelrequest | V0-F04 |
| ADR-0007 | begrenzte eq/AND-Filter statt freiem SQL | V0-F07/F08 |
| ADR-0008 | Query-Routing-Fallback bei eingeschränktem Rewriting | V0-RB04 |
| ADR-0009 | HTTPS-/Proxy-Vertrauensgrenze | R-002 |

## 4. Vorgesehene ADRs V1

| Geplante ID | Frage / Kandidat | Verbindung |
|---|---|---|
| ADR-0010 | Separater API-V1-Vertragsraum | V1-F01 |
| ADR-0011 | SQLite als zweiter verpflichtender DB-Adapter | V1-F10, R-018/R-019 |
| ADR-0012 | AST-/QueryPlan vor SQL-Compiler | V1-F05/F07 |
| ADR-0013 | mehrere technische API-Key-Identitäten und rollenbasierte Policies | V1-F12/F13 |
| ADR-0014 | Write-Audit im gleichen lokalen DB-Commit | V1-F19, R-017 |
| ADR-0015 | Dateibasierter oder DB-basierter Rate-Limit-Store ohne Redis | V1-F17, R-020 |
| ADR-0016 | OpenAPI aus autorisierten Ressourcendefinitionen | V1-F21 |
| ADR-0017 | Ressourcen-/Konfigurationsschema Version 2 mit Migration | V1-F23 |
| ADR-0018 | fünf SOLL-Funktionen als getrennte, optionale Slices | V1-F09/F14/F16/F22/F27 |

Die Nummern reservieren noch **keine Dateien**. Bei tatsächlichen Reviewentscheidungen können wir die erste akzeptierte ADR als `0001-kurztitel.md` anlegen; eine weitere Entscheidung ersetzt sie nur durch ausdrückliche Referenz.

## 5. ADR-Prozess

1. Eine Frage oder ein Risiko benennt einen langfristig bedeutsamen Architekturkonflikt.
2. Erstelle eine Kopie von [0000-template.md](0000-template.md) mit eindeutiger ID und sprechendem Dateinamen.
3. Beschreibe Kontext, zwingende Anforderungen, mindestens eine sinnvolle Alternative und Konsequenzen für Tests/Security/Hosting.
4. Markiere `Vorgeschlagen`; prüfe im Review mit Bezug zu Lastenheft und Pflichtenheft.
5. Nach bewusster Freigabe auf `Angenommen` setzen, Begründung und Entscheiddatum vermerken.
6. Verlinke die ADR aus Architektur, PR, Roadmap oder betroffener Testdokumentation.
7. Ändert sich die Entscheidung später, lege eine neue ADR an; die alte wird `Ersetzt` statt rückwirkend umgeschrieben.

## 6. Besondere Reviewfragen für SASD

- Sind private Konfigurations-/Schlüsseldateien wirklich außerhalb des Webroots?
- Bleiben unbekannte Datenbankressourcen standardmäßig geschlossen?
- Wird der V0-Clientvertrag durch die Entscheidung unbemerkt verändert?
- Erlaubt eine Abstraktion V1-Adapter, ohne jetzt Überengineering einzuführen?
- Gibt es einen negativen Sicherheits- und einen realen Provider-Test?
- Kann die Entscheidung bei Problemen anhand eines dokumentierten Backups zurückgenommen werden?

**Dokumentstatus:** Register und Vorgehen vorhanden; bisher keine individualisierte, akzeptierte ADR durch dieses Dokument behauptet.
