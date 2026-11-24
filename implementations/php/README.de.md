# PHP-8.4-Referenzimplementierung – erstes Bootstrap-Grundgerüst

> **Sprache:** [English (Standard)](README.md) | **Deutsch**

**Stand:** Unvollständige Entwicklungsbasis, **kein produktionsreifer Server** und keine vollständige V0.1.0. Enthalten sind ein vertrauensbasierter Plugin-Manager, geschütztes technisches Mindestlogging und ein öffentlicher Health-Endpunkt. **CRUD, API-Key-Prüfung, HTTPS-Erzwingung, Proxy-Vertrauensregeln und Hosting-Abnahme fehlen noch.** Keine echten Daten damit bereitstellen.

## Verzeichnisse und Schutzgrenze

```text
implementations/php/
  autoload.php                Klassennamensraum ohne Composer-Zwang
  webroot/index.php           Einziger HTTP-Einstieg, HTTP-Document-Root = webroot/
  src/Bootstrap.php           Composition Root je PHP-Request
  src/Config/                  JSON-Konfigurationsprüfung ohne Codeausführung
  src/Contracts/               Schnittstellen für Plugin, Factory, Logging
  src/Core/Plugin/             Explizite Registrierung und Plugin-Startprüfung
  src/Http/                    Request, Router, JSON-Response
  src/Infrastructure/Logging/ Geschütztes UTC-JSONL-Logging
  config/plugins.example.json Beispiel, keine echten Secrets
  plugins/                     Erweiterungen bewusst außerhalb des Cores
  tests/run.php                Tests ohne Zusatzbibliotheken
```

**Nur `webroot/` darf öffentlich sein.** Das darin liegende `index.php` ist der Root-Einstieg für die bereitgestellte Domain. Quellcode, Konfiguration und Logs liegen physisch außerhalb des öffentlich ausgelieferten Verzeichnisses – nicht bloß hinter `.htaccess`. Kein Docker, Redis oder dauerhaft laufender Dienst erforderlich.

## Lokal testen

```bash
cd implementations/php
php -v
php tests/run.php
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Für einen **rein lokalen** HTTP-Test ohne TLS:

```bash
cd implementations/php
export SASD_PLUGIN_CONFIG="$(pwd)/config/plugins.example.json"
export SASD_TECHNICAL_LOG="/tmp/sasd-api-development.jsonl"
php -S 127.0.0.1:8080 -t webroot webroot/index.php
# Anderes Terminal:
curl -i http://127.0.0.1:8080/api/v0/health
curl -i 'http://127.0.0.1:8080/index.php?route=/api/v0/health'
```

Das Beispielmanifest `{"schemaVersion":1,"enabled":[]}` aktiviert kein Plugin. Fehlende oder fehlerhafte Konfiguration, unbekannte Plugin-IDs oder fehlgeschlagener Plugin-Start verhindern den Dienststart (HTTP 503). Datenrouten bleiben gesperrt (404). Ein Fehler beim Schreiben geschützter Logs führt ebenfalls zu HTTP 503. Startup-Fehler werden nur als allgemeines Ereignis an das PHP-`error_log` übergeben; dessen tatsächlicher Speicherort muss auf dem Hoster geprüft werden. Die technischen JSONL-Ereignisse enthalten ausschließlich UTC-Zeit, Request-ID, Methode, Status, Ereigniskategorie und Dauer – keine Anfragetexte, Credentials, Query-Strings oder Client-IPs.

## Sichere Plugin-Verwaltung

Plugin-Aktivierung benötigt **zwei unabhängige Schritte**: Auswahl der ID durch den Betreiber in einer privaten, nicht ausführbaren JSON-Datei und **explizite Registrierung der vorher geprüften Factory im PHP-Quellcode** (`Bootstrap::create()`, Parameter `trustedFactories`). Im produktiven HTTP-Einstieg ist bisher **keine Factory registriert**. Daher lässt sich mit JSON allein niemals fremder Code laden. Kein Verzeichnis-Scanning, kein Remote-Download, keine beliebigen Klassen aus Konfiguration, keine Plugin-HTTP-Route. Plugin-API-Vertrag: `1.0`; Kategorien `logging`, `identity`, `storage` (jeweils höchstens eine Instanz) und zukünftig `application`.

Ein kleines technisches Basislogging gehört zwingend zum Core, auch wenn später ein Logging-Plugin externe Ziele anbindet. Ein Identity-Plugin prüft künftig Identitäten, die Daten-/Methoden-/Feld- und Datensatzberechtigung bleibt aber immer **beim API-Core**. Speicheradapter sind separat und dürfen keine ungeschützten HTTP-Routen definieren.

Zur mehrsprachigen Struktur und Reihenfolge: [`docs/development-layout.de.md`](../../docs/development-layout.de.md). Vor produktiver Bereitstellung sind das Hosting-/Sicherheits-Gate V0-G0 und sämtliche vorgesehenen V0-Abnahmetests abzuschließen.
