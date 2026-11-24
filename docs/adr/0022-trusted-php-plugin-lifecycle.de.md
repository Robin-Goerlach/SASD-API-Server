# ADR-0022 – Vertrauenswürdiger PHP-Plugin-Lebenszyklus getrennt vom Serverkern

> **Sprache:** [English (Standard)](0022-trusted-php-plugin-lifecycle.md) | **Deutsch**  
> **Datum:** 24.11.2026  
> **Entscheidungsstand:** Vorgeschlagen; erstes Bootstrap-Grundgerüst existiert, **nicht formell abgenommen** und **nicht produktionsfreigegeben**.  
> **Bezüge:** [Entwicklungsstruktur](../development-layout.de.md) · [ADR-0020](0020-auth-api-logging-service-boundaries.de.md) · [ADR-0021](0021-pluggable-database-adapters.de.md)

## Problem

Logging, Identitätsprüfung, Datenbankadapter und ggf. fachliche API-Module sollen austauschbar werden. PHP, Java und C# erhalten jeweils eigene Plugin-Verzeichnisse **außerhalb des Core**. Ein genereller, unkontrollierter Loader oder eine öffentliche Plugin-Verwaltung würde die Angriffsfläche insbesondere auf Shared Hosting unnötig erhöhen.

## Vorgeschlagene Entscheidung; bisher nur als kleines PHP-Grundgerüst umgesetzt

1. PHP 8.4 erhält einen **engen Factory-/Laufzeit-Vertrag** (derzeit Plugin-API `1.0`). Nur explizit im geprüften Bootstrap-Quellcode registrierte Factories sind auswählbar. JSON darf deren IDs auswählen, jedoch keine Klassennamen oder Dateipfade nachladen. Kein Scanning, Remote-Download, `eval`, öffentlicher Upload oder Hot-Reload.
2. Der Betreiber pflegt ein **nicht ausführbares JSON-Manifest physisch außerhalb des Document Root**. Fehlende/fehlerhafte Konfiguration, nicht registrierte oder doppelte IDs, inkompatible Schnittstellenversion, falsche Identität/Kategorie oder Fehler beim Plugin-Start verhindern den sicheren Start.
3. Die Kategorien heißen `logging`, `identity`, `storage`, `application`. Bei den ersten drei ist höchstens ein aktiver Anbieter erlaubt; fachliche Module kommen später und dürfen **keine automatische HTTP-Route** veröffentlichen. Startreihenfolge nach Prüfung sämtlicher IDs: Logging → Identity → Storage → Application.
4. Ein kleines **obligatorisches technisches und privates Kernlogging** bleibt bestehen. Spätere verpflichtende Schreib-Audit-Datensätze müssen in derselben lokalen Fachdatenbanktransaktion wie die Datenänderung geschrieben werden; kein Fernlogger kann diese Atomarität ersetzen.
5. Ein Identity-Plugin kann Identität prüfen, **der Core autorisiert** aber Ressourcen, Methoden, Felder und Datensätze. Storage-Plugins umgehen niemals Registry, Validierung oder zentrale HTTP-Policy.
6. Java und C# erhalten **eigene** Plugin-Verträge; gemeinsame HTTP/JSON-Antworten bedeuten nicht kompatible Plugin-Binärdateien.

## Alternativen und Konsequenzen

- Sämtliche optionalen Dienste dauerhaft fest in PHP verdrahten: einfach, jedoch schlecht austauschbar.
- Beliebige PHP-Pakete aus `plugins/` automatisch laden: bequem, aber ohne Lieferkettenprüfung und Isolation gefährlich; **vorerst verworfen**.
- Externe Auth-/LogSink-Dienste als Startpflicht: zusätzliche Verfügbarkeitstransitive und Vertrauensgrenzen; **für V0 verworfen**.
- Kleine explizite Registry pro Sprache: ausgewählte erste Lösung.

Die Erstimplementierung enthält **noch keine produktiv registrierten Plugins**, kein CRUD, keine öffentliche Plugin-Verwaltung und keine externen Dienste. PHP-In-Process-Plugins besitzen dieselben Betriebssystemrechte wie der Server: **keine Sandbox** und deshalb keine beliebigen Drittanbieter. Das Beispiel aktiviert nichts. Beim Start ausgeführte externe Nebenwirkungen lassen sich vom Manager nicht automatisch zurückrollen; Factories bleiben prüfungs- und testpflichtig.

**Offene Freigaben:** Lieferkette, Plugin-Lebenszyklus, Versions-/Kategorievertrag, Sicherheitsintegration, tatsächliche Hosterpfade/TLS, Logaufbewahrung und End-to-End-Autorisierung. Die bestehende Anzahl der V0-/V1-Pflichtanforderungen und Abnahmetests **ändert sich nicht automatisch** durch diesen Vorschlag oder seinen experimentellen Code.
