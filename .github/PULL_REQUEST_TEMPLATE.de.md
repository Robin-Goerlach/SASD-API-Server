# Pull Request – Vorlage (Deutsch)

## Zweck und Umfang

Änderung, Meilenstein (z. B. V0-M2), Issue und betroffene Anforderungs-IDs beschreiben.

**Nicht enthalten:** Was ist ausdrücklich außerhalb dieses Pull Requests?

## Architektur und Design

- [ ] HTTP, Service, Repository und Adapter bleiben getrennt
- [ ] Ressourcen-, Feld- und Methodenregeln bleiben default-deny
- [ ] SQL-Werte gebunden; Identifier nur aus serverseitigen Allowlists
- [ ] Keine ungeprüfte Pflichtabhängigkeit von Docker/Redis/Workern
- [ ] Bei relevanter Architekturänderung ADR erstellt/angepasst
- [ ] Öffentliche API- und Konfigurationsverträge dokumentiert

## Verifikation – Ergebnisse korrekt angeben

- [ ] PHP-Syntax-/statische Prüfungen **ausgeführt**
- [ ] Relevante Unit-Tests **ausgeführt**
- [ ] Relevante DB-Integrations-Tests **ausgeführt**
- [ ] HTTP-Contract- und Fehlerformat-Tests **ausgeführt**
- [ ] Negative Sicherheitstests **ausgeführt**
- [ ] Hosting-/Restore-Tests **ausgeführt**, falls betroffen
- [ ] Nicht anwendbare/nicht ausgeführte Prüfungen nachstehend begründet

### Durchgeführte Tests und Nachweise

Kommandos, Testumgebung, Ergebnis und CI-/Log-Verweis **ohne** Zugangsdaten oder private Nutzdaten nennen. Ein Häkchen nur nach tatsächlicher Ausführung setzen.

## Risiken und bekannte Grenzen

Offene Entscheidungen, betroffene Risikokennungen und mögliche Auswirkungen auf V0-/V1-Clients nennen.

## Sicherheit und Vertraulichkeit

- [ ] Keine Keys, Passwörter, personenbezogenen Daten oder vertraulichen Logs enthalten
- [ ] Neue Endpunkte/Filter/Felder bleiben ohne explizite Freigabe gesperrt
- [ ] Fehlerantworten enthalten kein SQL, private Pfade oder Secrets

## Dokumentation und Release

- [ ] README/Dokumentationsindex/Spezifikationen bei Bedarf angepasst
- [ ] CHANGELOG aktualisiert, sofern die Änderung Nutzer betrifft
- [ ] Kein Release oder bestandener Test ohne echten Nachweis behauptet
