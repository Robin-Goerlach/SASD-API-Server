# SASD API Server – Risikoregister

> **Sprache:** **Deutsch (bisheriger Dateipfad)** · [English (Standard)](risk-register.md) · [Deutsch (kanonisch)](risk-register.de.md)  

> **Stand:** 01.11.2026 · **Status:** Initiale Architektur- und Planungsbewertung; keine gemessenen Eintrittswahrscheinlichkeiten  
> Querverweise: [Roadmap](../ROADMAP.md), [Sicherheitsregeln](../SECURITY.md), [Architektur V0](architektur-v0.md), [Architektur V1](architektur-v1.md), [Teststrategie](teststrategie.md), [ADR-Register](adr/README.md)

## 1. Bewertungsverfahren

Dieses Register sammelt technische, sicherheitsrelevante und organisatorische Risiken, bevor sie in der Entwicklung teuer werden. **P0/blockierend** bedeutet, dass bei ungeklärtem Risiko keine öffentliche Produktivfreigabe erfolgen darf. **P1/hoch** muss vor dem zugeordneten Feature-Gate durch Test oder Entscheidung geklärt sein. **P2/normal** ist dokumentiert und wird bei Entwicklung überprüft. Diese Einstufung ist **kein mathematisch errechneter Risikowert**.

| Zustandsfeld | Bedeutung |
|---|---|
| Offen | Ursache und Gegenmaßnahme identifiziert, noch nicht nachgewiesen |
| In Prüfung | realer Test oder ADR/Review läuft |
| Behoben | nachweisbare Mitigation und Retest dokumentiert |
| Akzeptiert | bewusste, begründete, freigegebene Restunsicherheit |
| Entfallen | Funktion wurde nicht realisiert oder Risiko greift nicht mehr |

**Alle Risiken sind zunächst offen**, sofern nicht ein reales Prüfprotokoll das Gegenteil belegt. Jede Änderung erhält Datum, Verantwortlichkeit und Link zu Issue/ADR/Prüfprotokoll, sobald diese existieren.

## 2. Register

| ID | Phase | Priorität | Risiko / Auswirkung | Mitigation und Gate | Status |
|---|---|---|---|---|---|
| R-001 | V0 | **P0** | Secrets/Config/Logs liegen öffentlich im Webroot, evtl. wegen ignorierter .htaccess. Vollständige Kompromittierung möglich. | Physisch private Pfade; direkter HTTP-Abruf muss scheitern; sonst **kein Produktivstart** (V0-G0). | Offen |
| R-002 | V0 | **P0** | HTTPS wird fälschlich durch manipulierbare Forwarded-Header angenommen. API-Key im Klartext gefährdet. | TLS am Provider erzwingen, vertrauenswürdige Proxykette dokumentieren, HTTP-Negativtest. | Offen |
| R-003 | V0 | **P0** | Alle DB-Tabellen werden versehentlich automatisch veröffentlicht. | Resource-/Method-/Field-Allowlist mit Default-Deny und Tests. | Offen |
| R-004 | V0 | **P0** | SQL-Injection durch Filter-, Tabellen-, Sortier- oder ID-Parameter. | SQL-Werte binden; Identifier aus geprüfter Registry; Security-Negativtests. | Offen |
| R-005 | V0 | **P0** | Statischer API-Key wird in Browser/öffentliches Desktopbinary ausgeliefert. | V0 nur für vertrauenswürdige Server-Clients oder Backendproxy; echte Endnutzer-Auth separat planen. | Offen |
| R-006 | V0 | **P1** | mod_rewrite/PATH_INFO funktioniert auf Shared Hosting nicht. API erscheint als 404. | `index.php?route=...` als nachgewiesener Fallback; Providerintegration testen. | Offen |
| R-007 | V0 | **P1** | Unzureichende DB-GRANTs oder zu breite Rechte ermöglichen größeren Datenverlust. | dedizierte DB-Identität/Least Privilege, dokumentierte Ausnahmen, Policy zusätzlich. | Offen |
| R-008 | V0 | **P1** | PUT/PATCH-Semantik uneindeutig; Client überschreibt unbeabsichtigt Felder. | Vollersatz-/Teiländerungsvertrag und Nullable-/Default-Tests. | Offen |
| R-009 | V0 | **P1** | Änderungs-/Löschoperation ohne sicheren Schlüssel-Filter; Massenschaden. | verpflichtende PK-Bedingung, SQL-Review, Test gegen fehlendes WHERE. | Offen |
| R-010 | V0 | **P1** | Keys/Passwörter in Logs, Exceptions, CI oder Fehlermeldungen. | zentraler Fehler-/Log-Redactor, negative Secrettests. | Offen |
| R-011 | V0 | **P1** | Datenbankausfall wird als leere Liste oder HTTP 200 dargestellt. | typisierte DB-Ausnahmen, 500/503, Health-/Failuretests. | Offen |
| R-012 | V0 | **P1** | Fehlende Backups oder nicht funktionierender Restore. | Betreiber-Runbook mit tatsächlichem Restore auf Testsystem. | Offen |
| R-013 | V0 | **P1** | Zwei Installationen teilen versehentlich Daten oder API-Schlüssel. | getrennte Config/DB/Keys/Logs und Kreuz-Zugriffstest. | Offen |
| R-014 | V0 | **P2** | Überladene Architektur verzögert kleines V0. | ein Repository-Port, ein Adapter, keine vorgezogenen Connectoren/SSO. | Offen |
| R-015 | V0 | **P2** | Überlange Requests/Seiten erschöpfen Shared-Hosting-Ressourcen. | Body-/Page-/Offset-Limits, Messungen, 413/4xx-Tests. | Offen |
| R-016 | V1 | **P0** | RBAC gilt nicht für Filter/Count/Join/View/OpenAPI; verdeckte Datenexfiltration. | Policy vor QueryPlan und Schema, negative Leak-Tests aller Wege. | Offen |
| R-017 | V1 | **P0** | Write erfolgreich, Audit fehlgeschlagen; fehlende Nachvollziehbarkeit. | Datenänderung und Audit **in derselben lokalen DB-Transaktion**, Rollbacktest. | Offen |
| R-018 | V1 | **P1** | SQLite auf Provider- oder Netzdateisystem mit unpassender Lock-/WAL-Semantik. | reale Paralleltests, FK=ON, busy_timeout, privater Pfad, keine pauschale WAL-Annahme. | Offen |
| R-019 | V1 | **P1** | MariaDB/SQLite verhalten sich bei Boolean/Decimal/UTC/Text unterschiedlich. | feste Typkonventionen + gemeinsame Contract-Tests und Testfixtures. | Offen |
| R-020 | V1 | **P1** | Dateibasierter Rate-Limiter verliert Atomicity oder fällt aus. | `flock`-/Transaktionstest, Fail-Closed, definierter 429/503-Pfad. | Offen |
| R-021 | V1 | **P1** | Migrationsprozess öffnet alle Ressourcen/Rollen durch Defaults. | Config-Schema V2, Policyprüfung, sicherer Operatorprozess, Migrationstest. | Offen |
| R-022 | V1 | **P1** | V1 bricht laufende V0-Clients stillschweigend. | getrennte `/api/v0/`-/`/api/v1/`-Verträge, Contract-/Deprecation-Tests. | Offen |
| R-023 | V1 | **P1** | Key-Rotation/Widerruf veraltet oder wird in verteilten Deployments nicht sofort wirksam. | Request-übergreifend sicherer Key Store, Tests für Änderung/Revokation. | Offen |
| R-024 | V1 | **P2** | Relationale Expansion verursacht N+1 oder große Resultate. | Depth-/Child-Limits, Query-Budget, Profiling. | Offen |
| R-025 | Alle | **P1** | Dokumentation wird mit implementiertem Verhalten verwechselt; Fehlfreigabe. | keine erfundenen Testergebnisse; Implementierungs-/Abnahmeprotokoll, CHANGELOG. | Offen |
| R-026 | Alle | **P2** | Neue Features ohne Roadmap/ADR erhöhen Scope und Angriffsfläche. | Issue mit Begründung, Priorität, Nicht-Zielen und Tests. | Offen |

## 3. Blockierende Entscheidungen vor V0

**V0-G0:**
1. Gibt es auf dem Zielhosting einen **nachweislich privaten Ordner** außerhalb des Document Root?
2. Ist HTTPS-Zwang zuverlässig durchsetzbar, und auf welcher nachgewiesenen Proxy-Vertrauensgrenze?
3. Welche PHP-/PDO-/MariaDB-Versionen und Rechte stehen wirklich zur Verfügung?
4. Wie lautet das endgültige Ressourcenkonfigurationsformat und die V0-API-Versionierung?
5. Sind alle geplanten aufrufenden Anwendungen **vertrauenswürdige technische Clients**, oder braucht es einen zusätzlichen Auth-Proxy?

**Kein Workaround durch „Secret in index.php“ oder „CORS reicht als Auth“.** Wenn ein blockierender Punkt nicht lösbar ist, ist das Deploymentprofil oder der Implementierungsumfang explizit zu ändern und zu dokumentieren.

## 4. Gate für V1

Erst nach bestandener V0-Abnahme entscheiden über (a) SQLite-Providerfähigkeit, (b) Key- und Rollenstorage, (c) Audit-Schema/DB-Transaktionen, (d) atomaren Rate-Limit-Store, (e) Filter-/Datentypsemantik und (f) V0→V1-Config-Migration. Optionales Row-Level-Scoping und Named SQL bleiben solange deaktiviert, bis konkrete Sicherheits- und Datenkonsistenztests bestanden sind.

## 5. Pflege und Eskalation

Für jeden Entwicklungs-Slice wird die betroffene R-ID im Issue genannt und bei Abschluss mit Testverweis aktualisiert. **P0 ohne erfolgreiche Mitigation blockiert den Release.** P1 benötigt eine nachweisbar geprüfte Lösung oder einen ausdrücklichen, verantworteten Rest-Risikoentscheid; ein bloßer Kommentar „gering“ reicht nicht. R-Prioritäten sind nach neuen Erkenntnissen zu überprüfen.

**Dokumentstatus:** Risikoerfassung, keine behauptete Durchführung von Maßnahmen.
