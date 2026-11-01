---
name: Fehlerbericht (Deutsch)
about: Reproduzierbaren Fehler melden, keine Sicherheitslücke veröffentlichen
title: "bug: "
labels: ["bug"]
assignees: []
---

> **Sicherheit:** Keine Sicherheitslücken, API-Schlüssel, Passwörter, internen Pfade, Kundendaten oder Gesundheitsinformationen öffentlich teilen. Siehe [SECURITY.de.md](../../SECURITY.de.md).
>
> **Projektstatus:** Eine funktionsfähige API wurde noch nicht als Release freigegeben. Betrifft der Fehler nur einen Entwurf, bitte „Dokumentation/Architektur“ auswählen.

## Betroffener Bereich

- [ ] V0 Entwurf / Implementierung
- [ ] V1 Entwurf / Implementierung
- [ ] Dokumentation / Architektur
- Betroffene Anforderung (z. B. `V0-F06`):
- Meilenstein / Entwicklungsslice:

## Testumgebung (ohne Geheimnisse)

- Git-Commit / tatsächlich veröffentlichte Version:
- PHP-Version:
- MariaDB/MySQL-/SQLite-Version, falls relevant:
- Betriebssystem / Hosting / Client:
- Routing: `/api/v0/...` oder `index.php?route=...`

## Reproduktionsschritte

1.
2.
3.

## Erwartetes Ergebnis

Erwartetes beobachtbares Verhalten und Dokumentationsquelle.

## Tatsächliches Ergebnis

HTTP-Status und **bereinigtes** Fehlerobjekt beziehungsweise Request-ID angeben. Keine Authorization-Header, Tokens, DB-Dumps oder vertraulichen Logs einkopieren.

## Zusätzliche Prüfungen

- [ ] In einer Testinstanz statt in der Produktion reproduziert
- [ ] Passende Architektur-/API-Dokumentation geprüft
- [ ] Nach vorhandenen Issues gesucht
- [ ] Keine sicherheitsrelevanten Geheimnisse offengelegt
