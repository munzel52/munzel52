<?php
require_once 'config.php'; // $abrBlatt + $pdo
$seite = $_GET['seite'] ?? 'aktuell';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>HorstEurope Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
.cursive {
  font-family: cursive;
  font-size: 1.9em;
  text-align: center;
  color: white;
}

/* ===== Dark Mode ===== */
body.dark-mode { background:#121212!important; color:#e0e0e0; }
body.dark-mode .bg-white,
body.dark-mode .card { background:#1e1e1e!important; color:#e0e0e0; }
body.dark-mode .border-end { border-color:#333!important; }

/* ===== Scrollbereich Tabelle ===== */
.table-container{
  max-height: calc(100vh - 20px - 110px);
  overflow-y: auto;
}
.content-height {
  height: calc(100vh - 60px);
}

/* Sticky Kopf & Fuß */
.table-sticky thead th{
  position:sticky;
  top:0;
  z-index:3;
}
.table-sticky tfoot tr{
  position:sticky;
  bottom:0;
  z-index:3;
}
.table-sticky tfoot td{ background: white; }
body.dark-mode .table-sticky tfoot td{ background: #1e1e1e; }

/* Sidebar Accordion: etwas kompakter */
.sidebar {
  height: 100vh;
  overflow: auto;
}
.sidebar .accordion-button {
  padding: .5rem .75rem;
  font-weight: 600;
}
.sidebar .accordion-body {
  padding: .5rem .25rem;
}
.sidebar .nav-link {
  padding: .25rem .75rem;
  border-radius: .375rem;
}
.sidebar .nav-link.active {
  background: rgba(13,110,253,.12);
  font-weight: 600;
}
</style>
</head>

<body class="bg-light">

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-dark bg-primary sticky-top">
  <div class="container-fluid d-flex justify-content-center">
    <span class="cursive"><b>Haushalt-Ausgaben für Karin &amp; &nbsp;Horst</b></span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">

<!-- =====================================================
     SIDEBAR (Accordion / ein- und ausklappbar)
===================================================== -->
<div class="col-12 col-md-3 col-lg-2 bg-white border-end p-2 sidebar">

  <div class="accordion" id="sidebarAcc">

    <!-- Haushalt -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hHaushalt">
        <button class="accordion-button" type="button"
                data-bs-toggle="collapse" data-bs-target="#cHaushalt"
                aria-expanded="true" aria-controls="cHaushalt">
          Haushalt
        </button>
      </h2>
      <div id="cHaushalt" class="accordion-collapse collapse show"
           aria-labelledby="hHaushalt" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link <?= $seite === 'aktuell' ? 'active' : '' ?>"
                 href="?seite=aktuell"
                 <?= $seite === 'aktuell' ? 'aria-current="page"' : '' ?>>
                Aktuell → <?= date('m-Y') ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $seite === 'vormonat' ? 'active' : '' ?>"
                 href="?seite=vormonat"
                 <?= $seite === 'vormonat' ? 'aria-current="page"' : '' ?>>
                Vormonat(e)
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Ausgaben -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hEdit">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cEdit"
                aria-expanded="false" aria-controls="cEdit">
          Ausgaben
        </button>
      </h2>
      <div id="cEdit" class="accordion-collapse collapse"
           aria-labelledby="hEdit" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="../DTAusgaben/ausgaben_neu.php" target="_blank" rel="noopener">
                Ausgaben eingeben
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../DTAusgaben/ausgaben_bearbeiten.php" target="_blank" rel="noopener">
                Ausgaben bearbeiten
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="ausgaben/ausgaben.php" target="_blank" rel="noopener">
                Ausgaben 🆕 aktuell
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="ausgaben/ausgaben_all.php" target="_blank" rel="noopener">
                Ausgaben 🏛 früher
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="ausgaben/vormonat_delete.php" target="_blank" rel="noopener">
                Übertrag Vormonat‼️<br>❌ Löschen
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../AusgabenJahr/fixkosten_jahr.php?jahr=<?= date('Y') ?>" target="_blank" rel="noopener">
                Jahresübersicht
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Pauschalen -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hPausch">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cPausch"
                aria-expanded="false" aria-controls="cPausch">
          Pauschalen
        </button>
      </h2>
      <div id="cPausch" class="accordion-collapse collapse"
           aria-labelledby="hPausch" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="../DTAusgaben/pausch_verwalten.php"
                 onclick="return openTabWithFlag(this.href)">
                Pauschalen verwalten
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../DTAusgaben/pauschalen_eintragen.php"
                 onclick="return openTabWithFlag(this.href)">
                Pauschalen eintragen
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Bestellungen -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hBestell">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cBestell"
                aria-expanded="false" aria-controls="cBestell">
          Bestellungen
        </button>
      </h2>
      <div id="cBestell" class="accordion-collapse collapse"
           aria-labelledby="hBestell" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="../Bestellungen/index.php" target="_blank" rel="noopener">
                Bestellungen
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../Geraete/index.php" target="_blank" rel="noopener">
                Geräte
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Verbrauch -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hVerbrauch">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cVerbrauch"
                aria-expanded="false" aria-controls="cVerbrauch">
          Verbrauch
        </button>
      </h2>
      <div id="cVerbrauch" class="accordion-collapse collapse"
           aria-labelledby="hVerbrauch" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item mt-2">
              <a class="nav-link" href="../EditorWasser/start.php" target="_blank" rel="noopener">⚡️🔥🚰 Eingabe</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="strom/strom.php" target="_blank" rel="noopener">⚡️ Strom</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="waerme/waerme.php" target="_blank" rel="noopener">🔥 Wärme</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="wasser/wasser.php" target="_blank" rel="noopener">🚰 Wasser</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="verbrauchstrom.php" target="_blank" rel="noopener">Strom Übersicht</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="verbrauchwaerme.php" target="_blank" rel="noopener">Wärme Übersicht</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="verbrauchwasser.php" target="_blank" rel="noopener">Wasser Übersicht</a>
            </li>
            <li class="nav-item mt-2">
              <a class="nav-link" href="../EditorStrom/strom.html" target="_blank" rel="noopener">Strom-Daten-Grafik</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../EditorStrom/waerme.html" target="_blank" rel="noopener">Wärme-Daten-Grafik</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../EditorStrom/wasser.html" target="_blank" rel="noopener">Wasser-Daten-Grafik</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Videos -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hVideo">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cVideo"
                aria-expanded="false" aria-controls="cVideo">
          Videos
        </button>
      </h2>
      <div id="cVideo" class="accordion-collapse collapse"
           aria-labelledby="hVideo" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="videos/videos.php" target="_blank" rel="noopener">🎬 Videos erste</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../EditorVideos/videos.html" target="_blank" rel="noopener">📹 Videos letzte</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Tanken -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hTanken">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cTanken"
                aria-expanded="false" aria-controls="cTanken">
          Tanken
        </button>
      </h2>
      <div id="cTanken" class="accordion-collapse collapse"
           aria-labelledby="hTanken" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="../Tanken/benzin_golf.php" target="_blank" rel="noopener">⛽️ Benzin Golf</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../Tanken/tanken.php" target="_blank" rel="noopener">🚗 Tanken PKW</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Arztrechnungen -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hArzt">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cArzt"
                aria-expanded="false" aria-controls="cArzt">
          Gesundheit
        </button>
      </h2>
      <div id="cArzt" class="accordion-collapse collapse"
           aria-labelledby="hArzt" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="../ArztAbrechnungen/public/index.php" target="_blank" rel="noopener">
                🧾 Abrechnungen
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../ArztAbrechnungen/public/antraege_liste.php" target="_blank" rel="noopener">
                📋 Liste
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../ArztAbrechnungen/public/blutdruck.php" target="_blank" rel="noopener">
                🏥 Blutdruck
              </a>
            </li>
            <!--
            <li class="nav-item"><a class="nav-link" href="../arztrechnungen/start.php" target="_blank" rel="noopener">Rechnungen alte DB</a></li>
            <li class="nav-item"><a class="nav-link" href="../aaRechnungen/public/index.php" target="_blank" rel="noopener">aRechnungen</a></li>
            <li class="nav-item"><a class="nav-link" href="../ArztAbrechnungen_alt/public/index.php" target="_blank" rel="noopener">Abrechnungen alt</a></li>
            <li class="nav-item"><a class="nav-link" href="../Arzt2/ANTRAG_Zuordnung.php" target="_blank" rel="noopener">Zuordnung alte DB</a></li>
            -->
          </ul>
        </div>
      </div>
    </div>

    <!-- Sonstiges -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="hSonst">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#cSonst"
                aria-expanded="false" aria-controls="cSonst">
          Sonstiges
        </button>
      </h2>
      <div id="cSonst" class="accordion-collapse collapse"
           aria-labelledby="hSonst" data-bs-parent="#sidebarAcc">
        <div class="accordion-body">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="https://sxb1plzcpnl507138.prod.sxb1.secureserver.net:2083/" target="_blank" rel="noopener">
                phpMyAdmin
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../Struktur/projektstruktur.php" target="_blank" rel="noopener">
                Struktur anzeigen
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../Struktur/import_struktur.php" target="_blank" rel="noopener">
                Struktur-Import
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="" target="_blank" rel="noopener">
                LEER
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="start.php" target="_blank" rel="noopener">
                ChatGPT-Übersicht
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    </div><!-- /accordion -->
    </div>

    <!-- =====================================================
         CONTENT
    ===================================================== -->
    <div class="col-12 col-md-9 col-lg-10 p-3 content-height">
      <div class="card h-100 shadow-sm">
        <div class="card-body p-0 d-flex flex-column">
          <?php
          switch ($seite) {
            case 'aktuell':
              require 'ausgaben_aktuell.php';
              break;

            case 'vormonat':
              require 'ausgaben_vormonat.php';
              break;

            default:
              echo '<div class="p-3 text-danger">Unbekannte Seite</div>';
          }
          ?>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ✅ Bootstrap JS ist nötig für Collapse/Accordion -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openTabWithFlag(url) {
  const w = window.open(url, '_blank');

  // Popup-Blocker → normal navigieren
  if (!w) {
    window.location.href = url;
    return false;
  }

  try {
    // Flag im neuen Tab setzen
    w.sessionStorage.setItem('openedByJs', '1');
  } catch (e) {}

  w.focus();
  return false; // verhindert normales <a>-Verhalten
}

sessionStorage.setItem('returnUrl', window.location.href);
</script>

</body>
</html>
