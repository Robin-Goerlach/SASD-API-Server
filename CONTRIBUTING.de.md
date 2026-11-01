# Mitwirken am SASD API Server

> **Sprache:** [English (Standard)](CONTRIBUTING.md) | **Deutsch**

Vielen Dank für dein Interesse an **SASD API Server**. Das Open-Source-Projekt konzentriert sich derzeit auf die Planung und spätere Umsetzung eines schlanken, konfigurierbaren REST-Datenservers auf PHP-Basis. Konstruktive Architekturbeiträge, Korrekturen an der Dokumentation, nachvollziehbare Fehlermeldungen, Testfälle und kleine, gut abgegrenzte Codebeiträge sind willkommen.

> **Projektstatus:** V0.1.0 und V1.0.0 sind geplante Ziele. Spezifikationen und Diagramme beweisen **nicht**, dass ein Server bereits implementiert, geprüft oder veröffentlicht wurde.

## 1. Einstieg

- [Roadmap (Deutsch)](ROADMAP.de.md): Reihenfolge V0 vor V1 sowie Freigabegates.
- [Dokumentationsübersicht (Deutsch)](docs/README.de.md): Lastenhefte, Pflichtenhefte, Architektur, Betrieb und Tests.
- [Lastenheft V0](docs/lastenheft-v0.md) und [Pflichtenheft V0](docs/pflichtenheft-v0.md): unmittelbarer Entwicklungsumfang.
- [Architektur V0](docs/architektur-v0.md): Komponenten, Schichten und Sicherheitsgrenzen.
- [Teststrategie](docs/teststrategie.md) und [Sicherheitsrichtlinie](SECURITY.de.md): Negativtests und vertrauliche Meldungen.
- [ADR-Register](docs/adr/README.md): bedeutende Architekturentscheidungen nachvollziehbar festhalten.

Die primären Planungsunterlagen liegen auf Deutsch vor, englische Anforderungen und der Referenzkatalog ebenfalls. Anforderungskennungen bleiben über beide Sprachversionen identisch.

## 2. Welche Beiträge helfen besonders?

**Vor der ersten V0-Implementierung**: Widersprüche zwischen Dokumenten aufdecken, klare Abnahmekriterien formulieren, reale PHP/MariaDB-/Shared-Hosting-Grenzen prüfen, Sicherheitsnegativtests entwerfen und Ressourcenkonfiguration sowie HTTP-Vertrag überprüfen.

**Nach dem Architekturreview**: kleine V0-Slices mit getesteten PHP-Klassen, neutralen Fixtures und verständlichen Erläuterungen. V1-Funktionen bitte nicht ungefragt nach V0 übernehmen.

## 3. Vor der Arbeit

1. Suche ein thematisch passendes GitHub-Issue oder erstelle eines.
2. Nenne den Meilenstein (z. B. `V0-M2`) und die Anforderung (z. B. `V0-F02`).
3. Beschreibe erwartetes Verhalten, **Nicht-Ziele** und konkrete Abnahmeprüfungen.
4. Bei Änderungen an Authentifizierung, SQL, Routing, öffentlicher API, Repository-Port, Konfigurationsformat oder Secret-Verwaltung eine [ADR](docs/adr/README.md) prüfen.
5. Stelle sicher, dass V0 nicht von Docker, Redis, einem Hintergrund-Worker oder anderem im Zielhosting nicht gewährleistetem Dienst abhängig wird.

Eine kleine Änderung mit passenden Tests ist einem umfangreichen Refactoring vorzuziehen, das mehrere zukünftige Releases gleichzeitig vorwegnimmt.

## 4. Coding- und Architekturregeln

- Anfangs **PHP 8.4**, lesbarer PSR-orientierter Code mit klaren Namespaces.
- HTTP, Anwendungsservices, Ressourcendefinition, Sicherheit, Repository und Infrastruktur sauber trennen.
- Controller dürfen **kein** SQL erstellen und nicht unmittelbar von PDO abhängen; Anwendungsservices verwenden einen schmalen Repository-Vertrag.
- SQL-Werte **parametrisieren**; SQL-Bezeichner ausschließlich aus validierten serverseitigen Allowlists übernehmen. Kein frei übermitteltes SQL aus HTTP.
- Neue Ressourcen, Felder, Methoden, Relationen und Rollen bleiben ohne ausdrückliche Freigabe **gesperrt**.
- Verträge, Sicherheitsentscheidungen, Transaktionsgrenzen und ungewöhnliche Fehlerpfade sinnvoll mit PHPDoc und Kommentaren beschreiben. Kommentare erklären insbesondere das **Warum**.
- Erst messen, dann optimieren; Verständlichkeit, Korrektheit und Wartbarkeit haben Vorrang.
- Root-`index.php` für Shared Hosting beibehalten, private Konfiguration **außerhalb des Document Root**.
- Keine Funktion als implementiert darstellen, solange Code und Tests fehlen.

## 5. Testerwartungen

Jede Verhaltensänderung braucht positive Fälle und erforderliche **Negativ-/Sicherheitstests**. Details stehen in der [Teststrategie](docs/teststrategie.md).

Wichtige Gegenfälle: unbekannte Tabellen, gesperrte Spalten, fehlende/widerrufene Schlüssel, SQL-Injection in ID/Filter/Sortierung, zu große Bodies, öffentlich erreichbare Konfigurationsdateien, Rollenumgehung und fehlgeschlagene Transaktionen.

Nur **synthetische Testdaten** und isolierte Testdatenbanken verwenden. Keine Reset-/DELETE-Tests in der Produktion. Providerabhängiges Verhalten (IONOS-Routing, SQLite-Locks usw.) muss auf dem tatsächlichen Host geprüft werden.

**CI-Hinweis:** Ein GitHub-Actions-Workflow ist erst vorhanden, wenn er tatsächlich erstellt und getestet wurde. Ergebnisse nicht ohne Belege als bestanden ausgeben.

## 6. Pull-Request-Checkliste

- [ ] Problem, Meilenstein und Anforderungs-IDs benannt.
- [ ] Umfang und Nicht-Ziele klar.
- [ ] Änderungen am API-/JSON-/Konfigurationsvertrag dokumentiert.
- [ ] Relevante Unit-/Integrations-/Contract-Tests tatsächlich ausgeführt.
- [ ] Negative Sicherheitsfälle geprüft.
- [ ] Keine produktiven Daten, API-Schlüssel oder vertraulichen Logs eingecheckt.
- [ ] Dokumentation, ggf. [Roadmap](ROADMAP.de.md) und [Changelog](CHANGELOG.de.md) aktualisiert.
- [ ] Notwendige ADR erstellt beziehungsweise überprüft.
- [ ] Neue Abhängigkeiten und Lizenzen kontrolliert.
- [ ] Shared-Hosting-Sicherheitsregeln nicht abgeschwächt.

Sicherheitslücken **nicht** mit Ausnutzungsdetails in öffentlichen Issues melden. Siehe [SECURITY.de.md](SECURITY.de.md).

## 7. Fehler melden und Funktionen vorschlagen

**Fehlerbericht:** betroffener Commit/Version, Umgebung (PHP, Betriebssystem/Host, DB), bereinigte Reproduktionsschritte, erwartetes/tatsächliches Ergebnis, HTTP-Status/Request-ID und Einordnung als V0-/V1-/Dokumentationsfehler. Tokens, Passwörter, Personendaten und private Serverpfade entfernen.

**Funktionswunsch:** Anwendungsfall, Zielversion, Lücke im geplanten CRUD-Verhalten, Sicherheits- und Datenfolgen, kleine Implementierungsgrenze, passende Tests und Nicht-Ziele. Ein Wunsch außerhalb der Roadmap wird nicht automatisch umgesetzt.

## 8. Lizenz und Quellenangaben

Das Repository nutzt die [MIT-Lizenz](LICENSE). Beiträge müssen kompatibel sein. Fremden Programmcode, Bilder, Dokumentationen oder Bibliotheken nur nach Lizenzprüfung und notwendigen Quellen-/NOTICE-Angaben übernehmen; Marketingbeschreibungen anderer Produkte sind keine Nutzungserlaubnis.

## 9. Zusammenarbeit

Diskussionen sollen respektvoll, technisch fundiert und nachvollziehbar sein. Unterschiedliche Lösungswege sind willkommen, sofern ihre Folgen erläutert werden. Öffentliche Issues und Pull Requests dürfen keine Zugangsdaten oder sensiblen Benutzerdaten enthalten.

---

Die Regeln laden zur Mitarbeit ein; sie bedeuten keine Zusage, jeden Funktionswunsch oder Pull Request anzunehmen.
