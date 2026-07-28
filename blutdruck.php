<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

date_default_timezone_set('Europe/Berlin');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmtDateTime(string $datetime): string
{
    $ts = strtotime($datetime);
    return $ts ? date('d.m.Y H:i', $ts) : '';
}

function toDatetimeLocal(string $datetime): string
{
    $ts = strtotime($datetime);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

function valueClass(int $systole, int $diastole): string
{
    if ($systole >= 180 || $diastole >= 120) {
        return 'table-danger';
    }

    if ($systole >= 140 || $diastole >= 90) {
        return 'table-warning';
    }

    return '';
}

function badgeClass(int $systole, int $diastole): string
{
    if ($systole >= 180 || $diastole >= 120) {
        return 'text-bg-danger';
    }

    if ($systole >= 140 || $diastole >= 90) {
        return 'text-bg-warning';
    }

    return 'text-bg-success';
}

$error = '';
$editId = (int)($_GET['edit'] ?? 0);

$von = trim((string)($_GET['von'] ?? ''));
$bis = trim((string)($_GET['bis'] ?? ''));
$jahr = trim((string)($_GET['jahr'] ?? ''));

if ($jahr !== '' && preg_match('/^\d{4}$/', $jahr)) {
    $von = $jahr . '-01-01';
    $bis = $jahr . '-12-31';
} elseif ($von === '' && $bis === '') {
    $von = '2016-07-01';
    $bis = date('Y-m-d');
}

$form = [
    'id'        => 0,
    'messdatum' => date('Y-m-d\TH:i'),
    'systole'   => '',
    'diastole'  => '',
    'puls'      => '',
    'bemerkung' => '',
];

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM blutdruck WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editRow = $stmt->fetch();

    if ($editRow) {
        $form = [
            'id'        => (int)$editRow['id'],
            'messdatum' => toDatetimeLocal($editRow['messdatum']),
            'systole'   => $editRow['systole'],
            'diastole'  => $editRow['diastole'],
            'puls'      => $editRow['puls'],
            'bemerkung' => $editRow['bemerkung'] ?? '',
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id        = (int)($_POST['id'] ?? 0);
        $messdatum = trim((string)($_POST['messdatum'] ?? ''));
        $systole   = (int)($_POST['systole'] ?? 0);
        $diastole  = (int)($_POST['diastole'] ?? 0);
        $puls      = (int)($_POST['puls'] ?? 0);
        $bemerkung = trim((string)($_POST['bemerkung'] ?? ''));

        if ($messdatum === '') {
            $error = 'Bitte Datum und Uhrzeit eingeben.';
        } elseif ($systole <= 0 || $diastole <= 0 || $puls <= 0) {
            $error = 'Bitte Systole, Diastole und Puls eingeben.';
        } else {
            $dbMessdatum = str_replace('T', ' ', $messdatum) . ':00';

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE blutdruck
                    SET
                        messdatum = :messdatum,
                        systole   = :systole,
                        diastole  = :diastole,
                        puls      = :puls,
                        bemerkung = :bemerkung
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':messdatum' => $dbMessdatum,
                    ':systole'   => $systole,
                    ':diastole'  => $diastole,
                    ':puls'      => $puls,
                    ':bemerkung' => $bemerkung !== '' ? $bemerkung : null,
                    ':id'        => $id,
                ]);

                header('Location: blutdruck.php?updated=1');
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO blutdruck
                    (messdatum, systole, diastole, puls, bemerkung)
                VALUES
                    (:messdatum, :systole, :diastole, :puls, :bemerkung)
            ");

            $stmt->execute([
                ':messdatum' => $dbMessdatum,
                ':systole'   => $systole,
                ':diastole'  => $diastole,
                ':puls'      => $puls,
                ':bemerkung' => $bemerkung !== '' ? $bemerkung : null,
            ]);

            header('Location: blutdruck.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM blutdruck WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }

        header('Location: blutdruck.php?deleted=1');
        exit;
    }
}

$where = [];
$params = [];

if ($von !== '') {
    $where[] = "messdatum >= :von";
    $params[':von'] = $von . ' 00:00:00';
}

if ($bis !== '') {
    $where[] = "messdatum <= :bis";
    $params[':bis'] = $bis . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT
        id,
        messdatum,
        systole,
        diastole,
        puls,
        bemerkung,
        created_at,
        updated_at
    FROM blutdruck
    $whereSql
    ORDER BY messdatum DESC, id DESC
    LIMIT 500
");
$stmt->execute($params);
$werte = $stmt->fetchAll();

$jahrOptions = $pdo->query("SELECT DISTINCT YEAR(messdatum) AS y FROM blutdruck ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);

if (!$jahrOptions) {
    $jahrOptions = range((int)date('Y'), 2016);
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="blutdruck_export.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");

    fputcsv($out, ['Datum/Uhrzeit', 'Systole', 'Diastole', 'Puls', 'Bemerkung'], ';');

    foreach (array_reverse($werte) as $row) {
        fputcsv($out, [
            fmtDateTime($row['messdatum']),
            $row['systole'],
            $row['diastole'],
            $row['puls'],
            $row['bemerkung'] ?? '',
        ], ';');
    }

    fclose($out);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="blutdruck_export.xls"');

    echo "<table border='1'>";
    echo "<tr><th>Datum/Uhrzeit</th><th>Systole</th><th>Diastole</th><th>Puls</th><th>Bemerkung</th></tr>";

    foreach (array_reverse($werte) as $row) {
        echo '<tr>';
        echo '<td>' . h(fmtDateTime($row['messdatum'])) . '</td>';
        echo '<td>' . h($row['systole']) . '</td>';
        echo '<td>' . h($row['diastole']) . '</td>';
        echo '<td>' . h($row['puls']) . '</td>';
        echo '<td>' . h($row['bemerkung'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    exit;
}

$chartRows = array_reverse($werte);

$chartLabels = [];
$chartSystole = [];
$chartDiastole = [];
$chartPuls = [];

foreach ($chartRows as $row) {
    $chartLabels[] = fmtDateTime($row['messdatum']);
    $chartSystole[] = (int)$row['systole'];
    $chartDiastole[] = (int)$row['diastole'];
    $chartPuls[] = (int)$row['puls'];
}

$saved   = isset($_GET['saved']);
$updated = isset($_GET['updated']);
$deleted = isset($_GET['deleted']);
$isEdit  = (int)$form['id'] > 0;

$queryParams = [
    'von' => $von,
    'bis' => $bis,
];

$csvUrl = 'blutdruck.php?' . http_build_query(array_merge($queryParams, ['export' => 'csv']));
$xlsUrl = 'blutdruck.php?' . http_build_query(array_merge($queryParams, ['export' => 'excel']));
?>

<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Blutdruck & Puls</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(180deg, #f8f9fa 0%, #e3f2fd 100%);
        }

        .app-container {
            max-width: 980px;
            margin: 0 auto;
        }

        .num {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            font-variant-numeric: tabular-nums;
            text-align: right;
        }

        .mobile-card {
            display: none;
        }

        .wert-badge {
            font-size: 1rem;
            min-width: 90px;
        }

        .action-buttons {
            display: flex;
            gap: .35rem;
            justify-content: center;
            align-items: center;
        }

        .chart-box {
            height: 320px;
        }

        .legend-dot {
            display: inline-block;
            width: .9rem;
            height: .9rem;
            border-radius: 50%;
            margin-right: .35rem;
            vertical-align: -2px;
        }

        .dot-normal {
            background: #198754;
        }

        .dot-high {
            background: #ffc107;
        }

        .dot-danger {
            background: #dc3545;
        }

        @media (max-width: 767.98px) {
            .desktop-table {
                display: none;
            }

            .mobile-card {
                display: block;
            }

            .btn-mobile-full {
                width: 100%;
            }

            h3 {
                font-size: 1.35rem;
            }

            .chart-box {
                height: 260px;
            }
        }
    </style>
</head>
<body>

<div class="container py-3 app-container">

    <h3 class="mb-3">🫀 Blutdruck & Puls</h3>

    <?php if ($saved): ?>
        <div class="alert alert-success py-2">Messwert wurde gespeichert.</div>
    <?php endif; ?>

    <?php if ($updated): ?>
        <div class="alert alert-success py-2">Messwert wurde aktualisiert.</div>
    <?php endif; ?>

    <?php if ($deleted): ?>
        <div class="alert alert-warning py-2">Messwert wurde gelöscht.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <?= $isEdit ? '✏️ Messwert bearbeiten' : '➕ Neuer Messwert' ?>
            </h5>

            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= h($form['id']) ?>">

                <div class="col-12 col-md-4">
                    <label class="form-label">Datum / Uhrzeit</label>
                    <input
                        type="datetime-local"
                        name="messdatum"
                        class="form-control"
                        value="<?= h($form['messdatum']) ?>"
                        required
                    >
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Systole</label>
                    <input
                        type="number"
                        name="systole"
                        class="form-control num"
                        min="50"
                        max="250"
                        value="<?= h($form['systole']) ?>"
                        required
                    >
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Diastole</label>
                    <input
                        type="number"
                        name="diastole"
                        class="form-control num"
                        min="30"
                        max="160"
                        value="<?= h($form['diastole']) ?>"
                        required
                    >
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Puls</label>
                    <input
                        type="number"
                        name="puls"
                        class="form-control num"
                        min="30"
                        max="220"
                        value="<?= h($form['puls']) ?>"
                        required
                    >
                </div>

                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button class="btn <?= $isEdit ? 'btn-warning' : 'btn-primary' ?> btn-mobile-full">
                        <?= $isEdit ? 'Aktualisieren' : 'Speichern' ?>
                    </button>
                </div>

                <div class="col-12">
                    <label class="form-label">Bemerkung</label>
                    <input
                        type="text"
                        name="bemerkung"
                        class="form-control"
                        value="<?= h($form['bemerkung']) ?>"
                        placeholder="z. B. morgens, abends, nach Ruhe"
                    >
                </div>

                <?php if ($isEdit): ?>
                    <div class="col-12">
                        <a href="blutdruck.php" class="btn btn-outline-secondary btn-sm">
                            Bearbeiten abbrechen
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label">Von</label>
                    <input type="date" name="von" class="form-control" value="<?= h($von) ?>">
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label">Bis</label>
                    <input type="date" name="bis" class="form-control" value="<?= h($bis) ?>">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Jahr</label>
                    <select name="jahr" class="form-select" onchange="this.form.submit()">
                        <option value="">Alle</option>
                        <?php foreach ($jahrOptions as $y): ?>
                            <option value="<?= h($y) ?>" <?= $jahr === (string)$y ? 'selected' : '' ?>>
                                <?= h($y) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary">Filtern</button>

                    <a href="blutdruck.php?von=<?= h(date('Y-m-d', strtotime('-1 month'))) ?>&bis=<?= h(date('Y-m-d')) ?>" class="btn btn-outline-secondary">
                        Letzter Monat
                    </a>

                    <a href="blutdruck.php" class="btn btn-outline-secondary">
                        Zurücksetzen
                    </a>

                    <a href="<?= h($csvUrl) ?>" class="btn btn-outline-success">
                        CSV
                    </a>

                    <a href="<?= h($xlsUrl) ?>" class="btn btn-outline-success">
                        Excel
                    </a>
                </div>
            </form>

            <div class="small text-muted mt-3">
                <span><span class="legend-dot dot-normal"></span> normal</span>
                <span class="ms-3"><span class="legend-dot dot-high"></span> erhöht ab 140/90</span>
                <span class="ms-3"><span class="legend-dot dot-danger"></span> stark erhöht ab 180/120</span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title mb-3">📊 Verlauf Blutdruck & Puls</h5>

            <?php if (!$werte): ?>
                <div class="text-muted">
                    Keine Daten im gewählten Zeitraum vorhanden.
                </div>
            <?php else: ?>
                <div class="chart-box">
                    <canvas id="blutdruckChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm desktop-table">
        <div class="card-body">
            <h5 class="card-title mb-3">Letzte Messwerte</h5>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-primary">
                    <tr>
                        <th class="num">ID</th>
                        <th class="num">Datum / Uhrzeit</th>
                        <th class="num">Systole</th>
                        <th class="num">Diastole</th>
                        <th class="num">Puls</th>
                        <th>Bemerkung</th>
                        <th style="width: 105px;">Aktion</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$werte): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Keine Messwerte vorhanden.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($werte as $row): ?>
                        <?php
                        $s = (int)$row['systole'];
                        $d = (int)$row['diastole'];
                        $rowClass = valueClass($s, $d);
                        ?>
                        <tr class="<?= h($rowClass) ?>">
                            <td class="num"><?= h($row['id']) ?></td>
                            <td class="num"><?= h(fmtDateTime($row['messdatum'])) ?></td>
                            <td class="num fw-semibold"><?= h($row['systole']) ?></td>
                            <td class="num fw-semibold"><?= h($row['diastole']) ?></td>
                            <td class="num"><?= h($row['puls']) ?></td>
                            <td><?= h($row['bemerkung'] ?? '') ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a
                                        href="blutdruck.php?edit=<?= h($row['id']) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Bearbeiten"
                                    >✏️</a>

                                    <form method="post" class="m-0" onsubmit="return confirm('Messwert löschen?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Löschen">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="mobile-card">
        <h5 class="mb-2">Letzte Messwerte</h5>

        <?php if (!$werte): ?>
            <div class="alert alert-secondary py-2">
                Keine Messwerte im gewählten Zeitraum vorhanden.
            </div>
        <?php endif; ?>

        <?php foreach ($werte as $row): ?>
            <?php
            $s = (int)$row['systole'];
            $d = (int)$row['diastole'];
            $bClass = badgeClass($s, $d);
            ?>
            <div class="card shadow-sm mb-2">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-bold num text-start">
                                #<?= h($row['id']) ?> –
                                <?= h(fmtDateTime($row['messdatum'])) ?> Uhr
                            </div>

                            <div class="mt-2">
                                <span class="badge <?= h($bClass) ?> wert-badge">
                                    <?= h($row['systole']) ?> /
                                    <?= h($row['diastole']) ?>
                                </span>

                                <span class="badge text-bg-secondary wert-badge">
                                    Puls <?= h($row['puls']) ?>
                                </span>
                            </div>

                            <?php if (!empty($row['bemerkung'])): ?>
                                <div class="text-muted small mt-2">
                                    <?= h($row['bemerkung']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons">
                            <a
                                href="blutdruck.php?edit=<?= h($row['id']) ?>"
                                class="btn btn-sm btn-outline-primary"
                                title="Bearbeiten"
                            >✏️</a>

                            <form method="post" class="m-0" onsubmit="return confirm('Messwert löschen?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Löschen">🗑️</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
const systoleData = <?= json_encode($chartSystole) ?>;
const diastoleData = <?= json_encode($chartDiastole) ?>;
const pulsData = <?= json_encode($chartPuls) ?>;

const ctx = document.getElementById('blutdruckChart');

if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Systole',
                    data: systoleData,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Diastole',
                    data: diastoleData,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Puls',
                    data: pulsData,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const einheit = context.dataset.label === 'Puls' ? ' bpm' : ' mmHg';
                            return context.dataset.label + ': ' + context.parsed.y + einheit;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Blutdruck mmHg'
                    }
                },
                y1: {
                    beginAtZero: false,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Puls bpm'
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>
