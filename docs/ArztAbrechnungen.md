# ArztAbrechnungen – Dokumentation

Stand: 17.10.2026 · erstellt aus dem Code in diesem Repository (`dashboard.php`, `blutdruck.php`)

> **Hinweis zum Umfang:** Im Repository liegt von der Anwendung *ArztAbrechnungen* bisher nur
> `blutdruck.php`. Die Seiten `index.php` (Abrechnungen), `antraege_liste.php` (Liste) und
> `config.php` sind nicht eingecheckt. Diese Teile sind unten nur so weit beschrieben, wie sie aus
> dem Dashboard erkennbar sind, und als **offen** markiert.

---

## 1. Überblick

*ArztAbrechnungen* ist der Gesundheitsbereich der privaten Haushalts-Webanwendung
„Haushalt-Ausgaben für Karin & Horst“. Sie verwaltet Arztrechnungen, die zugehörigen
Erstattungsanträge (Beihilfe/Krankenversicherung) und ein Blutdruck-Tagebuch.

| Merkmal | Wert |
|---|---|
| Sprache / Laufzeit | PHP (mit `declare(strict_types=1)`) |
| Datenbank | MySQL/MariaDB über PDO (`$pdo` aus `config.php`) |
| Oberfläche | Bootstrap 5.3, Chart.js |
| Zeitzone | `Europe/Berlin` |
| Hosting | Shared Hosting mit cPanel/phpMyAdmin (Link im Dashboard) |
| Authentifizierung | keine in der Anwendung selbst erkennbar |

## 2. Einbindung im Dashboard

Das zentrale `dashboard.php` verlinkt die Anwendung im Akkordeon-Abschnitt **Gesundheit**.
Alle Links öffnen sich in einem neuen Tab.

| Menüpunkt | Ziel | Zweck |
|---|---|---|
| 🧾 Abrechnungen | `../ArztAbrechnungen/public/index.php` | Erfassung und Übersicht der Arztrechnungen |
| 📋 Liste | `../ArztAbrechnungen/public/antraege_liste.php` | Liste der Erstattungsanträge |
| 🏥 Blutdruck | `../ArztAbrechnungen/public/blutdruck.php` | Blutdruck- und Pulsmessungen |

Auskommentierte Altversionen im Dashboard (nicht mehr aktiv): `../arztrechnungen/start.php`
(alte DB), `../aaRechnungen/public/index.php`, `../ArztAbrechnungen_alt/public/index.php`,
`../Arzt2/ANTRAG_Zuordnung.php`.

## 3. Verzeichnisstruktur auf dem Server

Aus den Pfaden abgeleitet:

```
<webroot>/
├── <Dashboard-Ordner>/
│   └── dashboard.php
└── ArztAbrechnungen/
    └── public/
        ├── config.php            # DB-Verbindung ($pdo)        – nicht im Repo
        ├── index.php             # Abrechnungen                – nicht im Repo
        ├── antraege_liste.php    # Antragsliste                – nicht im Repo
        └── blutdruck.php         # Blutdruck & Puls            – im Repo
```

## 4. Konfiguration (`config.php`)

`blutdruck.php` lädt `__DIR__ . '/config.php'` und erwartet danach eine PDO-Instanz in `$pdo`.
Damit der Code wie geschrieben funktioniert, muss die Verbindung so eingestellt sein:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (Fehler sollen nicht still verschluckt werden)
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (der Code greift per `$row['spalte']` zu)
- Zeichensatz `utf8mb4`

Beispiel (Zugangsdaten gehören **nicht** ins Repository):

```php
<?php
$pdo = new PDO(
    'mysql:host=localhost;dbname=DBNAME;charset=utf8mb4',
    'DBUSER',
    'DBPASS',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
```

## 5. Modul „Abrechnungen“ (`index.php`) – offen

Nicht im Repository. Bitte Datei einchecken, damit Datenmodell (Rechnungen, Ärzte, Beträge,
Erstattungsstatus) und Abläufe dokumentiert werden können.

## 6. Modul „Liste“ (`antraege_liste.php`) – offen

Nicht im Repository. Laut Menü eine Übersicht der Erstattungsanträge; vermutlich die
Zuordnung von Rechnungen zu Anträgen (Vorgänger: `Arzt2/ANTRAG_Zuordnung.php`).

## 7. Modul „Blutdruck & Puls“ (`blutdruck.php`)

Eine einzelne Seite für Erfassen, Bearbeiten, Löschen, Filtern, grafische Darstellung und Export
von Messwerten.

### 7.1 Datenbanktabelle `blutdruck`

Aus den Abfragen abgeleitet (Typen sind eine Annahme, bitte mit phpMyAdmin abgleichen):

| Spalte | Typ (vermutet) | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT, AUTO_INCREMENT, PK | ja | Laufende Nummer |
| `messdatum` | DATETIME | ja | Zeitpunkt der Messung |
| `systole` | SMALLINT | ja | Oberer Wert in mmHg |
| `diastole` | SMALLINT | ja | Unterer Wert in mmHg |
| `puls` | SMALLINT | ja | Herzfrequenz in bpm |
| `bemerkung` | VARCHAR, NULL | nein | Freitext, leerer Text wird als `NULL` gespeichert |
| `created_at` | TIMESTAMP | – | Wird gelesen, aber vom Code nicht gesetzt (DB-Default) |
| `updated_at` | TIMESTAMP | – | Wird gelesen, aber vom Code nicht gesetzt (DB-Default) |

```sql
CREATE TABLE blutdruck (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  messdatum  DATETIME NOT NULL,
  systole    SMALLINT UNSIGNED NOT NULL,
  diastole   SMALLINT UNSIGNED NOT NULL,
  puls       SMALLINT UNSIGNED NOT NULL,
  bemerkung  VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_messdatum (messdatum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.2 Bedienung

1. **Neuer Messwert:** Datum/Uhrzeit (vorbelegt mit jetzt), Systole, Diastole, Puls und optional
   eine Bemerkung eingeben → *Speichern*.
2. **Bearbeiten:** ✏️ in der Tabelle klickt auf `?edit=<id>`; das Formular wird gelb
   (*Aktualisieren*). *Bearbeiten abbrechen* kehrt zur leeren Erfassung zurück.
3. **Löschen:** 🗑️ mit Sicherheitsabfrage „Messwert löschen?“.
4. **Filtern:** Zeitraum über *Von*/*Bis* oder ein *Jahr* (Auswahl sendet sofort ab).
   Schnellwahl *Letzter Monat*, *Zurücksetzen* stellt den Standardzeitraum wieder her.
5. **Export:** *CSV* oder *Excel* exportiert die aktuell gefilterten Werte.

Auf Bildschirmen unter 768 px wird statt der Tabelle eine Kartenansicht mit farbigen Badges
gezeigt.

### 7.3 Request-Parameter

| Parameter | Methode | Wirkung |
|---|---|---|
| `von`, `bis` | GET | Zeitraum (`YYYY-MM-DD`), jeweils inklusive ganzer Tag |
| `jahr` | GET | Vierstellige Jahreszahl; überschreibt `von`/`bis` mit 01.01.–31.12. |
| `edit` | GET | ID des zu bearbeitenden Messwerts |
| `export` | GET | `csv` oder `excel` |
| `saved`, `updated`, `deleted` | GET | Zeigen nach Weiterleitung die Erfolgsmeldung |
| `action=save` | POST | Neu anlegen (`id=0`) oder ändern (`id>0`) |
| `action=delete` | POST | Löschen des Datensatzes `id` |

Ohne Filterangabe gilt der Zeitraum **01.07.2016 bis heute**.

Nach jedem Speichern/Löschen wird per Post/Redirect/Get auf `blutdruck.php?…` umgeleitet, damit
ein Neuladen das Formular nicht erneut absendet.

### 7.4 Validierung

- Browser: `required`; Wertebereiche Systole 50–250, Diastole 30–160, Puls 30–220.
- Server: Datum darf nicht leer sein, Systole/Diastole/Puls müssen größer 0 sein.
  Die Wertebereiche werden serverseitig **nicht** geprüft.
- Das Datum aus `datetime-local` (`2026-10-17T08:30`) wird zu `2026-10-17 08:30:00` umgewandelt.

### 7.5 Bewertung der Messwerte

Funktionen `valueClass()` (Zeilenfarbe Tabelle) und `badgeClass()` (Badge mobil):

| Stufe | Bedingung | Tabelle | Badge |
|---|---|---|---|
| normal | sonst | ohne Farbe | grün |
| erhöht | Systole ≥ 140 **oder** Diastole ≥ 90 | gelb | gelb |
| stark erhöht | Systole ≥ 180 **oder** Diastole ≥ 120 | rot | rot |

Die Grenzwerte orientieren sich an den gängigen Hypertonie-Schwellen; sie sind keine ärztliche
Bewertung.

### 7.6 Diagramm

Liniendiagramm mit Chart.js, chronologisch aufsteigend:

- linke Achse: Systole und Diastole in mmHg
- rechte Achse: Puls in bpm
- Tooltip zeigt alle drei Werte eines Zeitpunkts mit Einheit

### 7.7 Export

| Format | Datei | Details |
|---|---|---|
| CSV | `blutdruck_export.csv` | UTF-8 mit BOM, Trennzeichen `;` – öffnet direkt korrekt in deutschem Excel |
| Excel | `blutdruck_export.xls` | HTML-Tabelle mit Excel-MIME-Typ; Excel zeigt beim Öffnen eine Formatwarnung |

Spalten: Datum/Uhrzeit, Systole, Diastole, Puls, Bemerkung – chronologisch aufsteigend.
Der Export enthält **alle** Werte im gewählten Zeitraum; das `LIMIT 500` der Bildschirmansicht gilt hier nicht.

### 7.8 Hilfsfunktionen

| Funktion | Aufgabe |
|---|---|
| `h($value)` | HTML-Escaping für alle Ausgaben |
| `fmtDateTime($dt)` | DB-Datum → `TT.MM.JJJJ HH:MM` |
| `toDatetimeLocal($dt)` | DB-Datum → Wert für `<input type="datetime-local">` |
| `valueClass($s, $d)` | Bootstrap-Tabellenklasse je Bewertung |
| `badgeClass($s, $d)` | Bootstrap-Badgeklasse je Bewertung |

## 8. Sicherheit und bekannte Einschränkungen

| # | Punkt | Auswirkung | Empfehlung |
|---|---|---|---|
| 1 | Kein Login in der Anwendung | Wer die URL kennt, sieht und ändert Gesundheitsdaten | Verzeichnisschutz (`.htaccess`/cPanel „Directory Privacy“) oder Login |
| 2 | Kein CSRF-Schutz bei Speichern/Löschen | Fremde Seite könnte Aktionen auslösen, wenn man angemeldet ist | CSRF-Token in Formularen |
| 3 | `display_errors = 1` | Fehlermeldungen mit Pfaden/SQL im Browser sichtbar | Im Produktivbetrieb abschalten |
| 4 | `LIMIT 500` | Bei vielen Messungen fehlen ältere Werte in Tabelle und Diagramm, ohne Hinweis (der Export ist nicht betroffen) | Hinweis anzeigen oder Seitenaufteilung |
| 5 | Chart.js ohne feste Version (`cdn.jsdelivr.net/npm/chart.js`) | Neue Hauptversion kann das Diagramm unbemerkt brechen | Version festlegen, z. B. `chart.js@4.4.1` |
| 6 | Wertebereiche nur im Browser geprüft | Unplausible Werte über direkte Requests möglich | Bereiche auch serverseitig prüfen |
| 7 | Unterschiedliche Bootstrap-Versionen (Dashboard 5.3.2, Blutdruck 5.3.3) | Kosmetisch | Vereinheitlichen |

SQL-Injection ist durch durchgängig vorbereitete Statements (`prepare`/`execute`) abgedeckt; alle
Ausgaben laufen über `h()`.

## 9. Offene Punkte für diese Dokumentation

- `index.php`, `antraege_liste.php` und `config.php` (ohne Zugangsdaten) ins Repository aufnehmen
- Tabellenstruktur per phpMyAdmin exportieren (*Exportieren → nur Struktur*) und als
  `docs/schema.sql` ablegen
