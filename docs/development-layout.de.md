# Implementierungsstruktur und schrittweise Plugin-Entwicklung

> **Sprache:** [English (Standard)](development-layout.md) | **Deutsch**  
> **Datum:** 24.11.2026 · **Stand:** erstes PHP-Bootstrap-Grundgerüst; keine freigegebene Version.  
> **Bezüge:** [V0-Entwicklungsplan](development-plan-v0.de.md) · [V0-Architektur](architecture-v0.de.md) · [Dienstgrenzen](service-boundaries-and-integrations.de.md) · [ADR-0021](adr/0021-pluggable-database-adapters.de.md)

## 1. Aufbau und Sicherheitsgrenzen

Ein gemeinsames Produkt bietet einen einheitlichen versionierten **REST/JSON**-Vertrag, aber PHP, Java und C# sind **eigenständige Implementierungen**. Gemeinsame HTTP-Vertragstests entstehen später unter `contracts/`. Eine Installation hat eigene Konfiguration, Geheimnisse, Ressourcen, Datenbank und technische Logs. Kein sprachübergreifend ladbares Plugin und kein allgemeiner Application Server in V0.

```text
SASD-API-Server/
  docs/                     Architektur, Anforderungen, Betrieb in DE/EN
  contracts/                Künftige sprachneutrale API-Tests
  implementations/
    php/                    AKTIV: PHP 8.4
      webroot/index.php     Einziger öffentlicher Einstieg
      autoload.php
      src/{Bootstrap.php,Http/,Config/,Contracts/,Core/Plugin/,Infrastructure/Logging/}
      config/               Private JSON-Konfiguration + Beispiel
      plugins/
        logging/            Optionales austauschbares Logging
        identity/           Identitätsprüfung, nicht Datenrechte
        storage/            Datenbankadapter
        application/        Spätere fachliche Module
      tests/
    java/                   GEPLANT, eigener Core und Plugin-Ordner
      core/
      plugins/{logging,identity,storage,application}/
    csharp/                 GEPLANT, eigener .NET-Core und Plugin-Ordner
      core/
      plugins/{logging,identity,storage,application}/
```

Das lokale technische Mindestlogging unter PHP `src/Infrastructure/Logging/` gehört **zum Core**, damit auch Plugin-Startfehler sichtbar bleiben. Zusätzliche Logger gehören in `plugins/logging/`. Identität und Speicheradapter sind ebenfalls getrennt. Für Java/C# und die späteren PHP-Plugins existieren derzeit **nur Verzeichnisdokumentationen**.

## 2. Reihenfolge

1. **Jetzt:** Grundlagen aus V0-M0/M1, explizite geprüfte Factory-Registrierung, strenge JSON-Konfiguration, minimaler Health-Endpunkt, korrelierte geschützte JSONL-Protokolle, Sprach- und Plugin-Verzeichnisse. Noch **kein CRUD** und keine Produktionsfreigabe.
2. **Danach Logging-Plugin:** echtes PHP-Modul unter `plugins/logging/`, versionierter Vertrag, Tests, mögliche Anregungen aus LogSink, MustelaLogAPI, Mustela und NimbusCore; jedoch keine Abhängigkeit von deren Dienstverfügbarkeit. Das spätere **atomare Schreib-Audit** verbleibt in der lokalen Fachdatenbanktransaktion und kann nicht durch Fernlogging ersetzt werden.
3. **Dann Identity-Plugin:** PHP-Verifier unter `plugins/identity/`; weiterhin lokale API-Key-Basis und Default-Deny, keine Pflicht für einen externen Auth Service. **Ressourcen-, Methoden-, Feld- und Datensatzrechte verbleiben stets im Core.**
4. **Geschützte CRUD-Funktionen:** TLS/Proxy/IP- und Ressourcenrichtlinien, authentifizierte Datenrouten, geprüfte Eingaben, neutraler Repository-Port, genau ein MariaDB-Produktivadapter für V0. Anschließend alle V0-F01–F17 / AT-V0-01–10 tatsächlich nachweisen; insbesondere die IONOS-/Hoster-Sicherheitsvoraussetzungen.
5. **Später:** V0 abnehmen, V1 mit SQLite/Rollen/Audit/OpenAPI umsetzen. PostgreSQL, Oracle, Db2, Firebird ausschließlich nach eigenen Freigaben. Java als zweite Sprache, C# danach.

Die frühe, bewusst eingeschränkte Plugin-Verwaltung ist eine neue Projektvorgabe gegenüber dem älteren ADR-Vorschlag: ausschließlich registrierte Factories, kein Dateiscan, kein Marketplace, keine automatisch eingebundenen Fremdmodule. ADR-0021 behält die spätere Datenbank-Reihenfolge bei; Architektur- und Sicherheitsfreigaben bleiben offen.

## 3. Prüfkriterien und offene Gates

- **Default-Deny:** unbekannte oder doppelte IDs, fehlerhafte Konfiguration, Versionskonflikte und Startfehler verhindern den sicheren Start. Kein Hot-Reload.
- **Dateischutz:** tatsächlicher HTTP-Document-Root ist `webroot/`; Konfiguration, Quellcode, Logfiles, DB-Passwörter und Backups physisch außerhalb. `.htaccess` reicht nicht. Die echte Hosting-/TLS-Konfiguration ist vor Produktion separat nachzuweisen.
- **Datensparsame Logs:** UTC-Zeit, Request-ID, Methode, Status, fest definierte Ereigniskategorie, Laufzeit. Keine Keys, Passwörter, Query-Strings, Bodies, SQL-Werte oder IP-Adressen. Dateirechte, Rotation, Aufbewahrung und PHP-Startuplog-Ziel durch Betreiber prüfen.
- **Keine Plugin-Sicherheitsumgehung:** Plugins dürfen keine unabhängigen HTTP-Routen, freien SQL-Zugriffe oder dynamischen Code-Downloads einführen.
- **Aktuelle Tests:** `php -l`, `php implementations/php/tests/run.php`, lokale HTTP-Tests (Health, verbotene Datenrouten). Noch kein MariaDB-, TLS-, reales Hoster-, Zwei-Instanzen- oder Backup-/Restore-Abnahmenachweis.

**Offen:** V0-G0 (private Pfade und TLS auf dem echten Hoster), API-Key-/Zugriffs-/IP-Richtlinien, CRUD/DB, externe Plugin-Verträge und vollständige Release-Abnahme. Ein getestetes Grundgerüst ist **noch kein abgenommener Server**.
