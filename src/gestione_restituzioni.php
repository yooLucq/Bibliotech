<?php
/**
 * BiblioTech - Dashboard Bibliotecario
 */

require_once 'includes/auth.php';
require_once 'includes/prestiti.php';

requireRole('bibliotecario');

$message = '';
$message_type = '';

// ── RESTITUZIONE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restituisci') {
    $result = registraRestituzione((int)$_POST['prestito_id'], getCurrentUserId());
    $message = $result['message'];
    $message_type = $result['success'] ? 'success' : 'error';
}

try {
    $pdo = getDbConnection();

    // Prestiti attivi
    $prestiti_attivi = getAllPrestitiAttivi();

    // Storico completo (inclusi restituiti)
    $storico = $pdo->query("
        SELECT
            p.id AS prestito_id,
            p.data_prestito,
            p.data_scadenza,
            p.data_restituzione,
            u.nome, u.cognome, u.username,
            l.titolo, l.autore,
            c.codice_copia,
            CASE WHEN p.data_restituzione IS NULL THEN 'attivo' ELSE 'restituito' END AS stato
        FROM prestiti p
        JOIN utenti u ON p.utente_id = u.id
        JOIN copie c ON p.copia_id = c.id
        JOIN libri l ON c.libro_id = l.id
        ORDER BY p.data_prestito DESC
    ")->fetchAll();

    // Elenco utenti
    $utenti = $pdo->query("
        SELECT id, username, nome, cognome, email, ruolo, data_creazione, attivo,
               (SELECT COUNT(*) FROM prestiti WHERE utente_id = utenti.id AND data_restituzione IS NULL) AS prestiti_attivi
        FROM utenti
        ORDER BY ruolo, cognome, nome
    ")->fetchAll();

    // Statistiche
    $stats = [
        'prestiti_attivi'  => count($prestiti_attivi),
        'prestiti_scaduti' => count(array_filter($prestiti_attivi, fn($p) => $p['scaduto'])),
        'totale_prestiti'  => count($storico),
        'totale_utenti'    => count(array_filter($utenti, fn($u) => $u['ruolo'] === 'studente')),
    ];

} catch (PDOException $e) {
    error_log($e->getMessage());
    $prestiti_attivi = $storico = $utenti = [];
    $stats = ['prestiti_attivi'=>0,'prestiti_scaduti'=>0,'totale_prestiti'=>0,'totale_utenti'=>0];
}

$tab = $_GET['tab'] ?? 'attivi';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BiblioTech</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; }

        .navbar {
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white; padding:15px 30px;
            display:flex; justify-content:space-between; align-items:center;
            box-shadow:0 2px 8px rgba(0,0,0,.15);
        }
        .navbar-brand { font-size:22px; font-weight:bold; }
        .navbar-right { display:flex; gap:15px; align-items:center; }
        .badge-role { background:rgba(255,255,255,.2); padding:4px 12px; border-radius:12px; font-size:12px; }
        .nav-link { color:white; text-decoration:none; font-weight:500; padding:7px 14px; border-radius:6px; transition:background .2s; }
        .nav-link:hover { background:rgba(255,255,255,.15); }
        .btn-logout { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.5); color:white; padding:7px 14px; border-radius:6px; text-decoration:none; font-size:13px; }

        .container { max-width:1400px; margin:0 auto; padding:25px 20px; }

        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:25px; }
        .stat-card { background:white; padding:20px 25px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.06); border-left:4px solid; }
        .stat-card.blue   { border-color:#667eea; }
        .stat-card.red    { border-color:#e74c3c; }
        .stat-card.green  { border-color:#27ae60; }
        .stat-card.purple { border-color:#9b59b6; }
        .stat-label { font-size:13px; color:#888; margin-bottom:6px; }
        .stat-value { font-size:32px; font-weight:700; color:#333; }

        .tabs { display:flex; gap:5px; margin-bottom:20px; background:white; padding:6px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.06); width:fit-content; }
        .tab { padding:9px 20px; border-radius:7px; text-decoration:none; font-size:14px; font-weight:500; color:#666; transition:all .2s; }
        .tab:hover { background:#f0f2f5; color:#333; }
        .tab.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }

        .alert { padding:12px 16px; border-radius:7px; margin-bottom:20px; border-left:4px solid; font-size:14px; }
        .alert-success { background:#d4edda; border-color:#28a745; color:#155724; }
        .alert-error   { background:#f8d7da; border-color:#dc3545; color:#721c24; }

        .card { background:white; border-radius:10px; padding:25px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .card-title { font-size:17px; font-weight:600; color:#333; }
        .count-badge { background:#f0f2f5; padding:4px 12px; border-radius:12px; font-size:13px; color:#666; }

        table { width:100%; border-collapse:collapse; }
        th,td { padding:11px 13px; text-align:left; border-bottom:1px solid #f0f0f0; font-size:13px; }
        th { background:#fafafa; font-weight:600; color:#666; font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fafbff; }
        .user-cell strong { display:block; color:#333; }
        .user-cell small  { color:#999; }

        .badge { padding:4px 10px; border-radius:10px; font-size:11px; font-weight:600; display:inline-block; }
        .badge-ok       { background:#d4edda; color:#155724; }
        .badge-warning  { background:#fff3cd; color:#856404; }
        .badge-danger   { background:#f8d7da; color:#721c24; }
        .badge-done     { background:#e2e3e5; color:#383d41; }
        .badge-biblio   { background:#d1ecf1; color:#0c5460; }
        .badge-studente { background:#e8d5f5; color:#6b21a8; }

        .btn-restituisci { background:#27ae60; color:white; border:none; padding:6px 14px; border-radius:5px; cursor:pointer; font-size:12px; font-weight:600; }
        .btn-restituisci:hover { background:#219a52; }

        .empty { text-align:center; padding:50px; color:#aaa; }
        .empty-icon { font-size:48px; margin-bottom:10px; }

        @media(max-width:900px) {
            .stats-grid { grid-template-columns:1fr 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand"> BiblioTech</div>
    <div class="navbar-right">
        <span class="badge-role">👤 BIBLIOTECARIO</span>
        <a href="gestione_catalogo.php" class="nav-link"> Catalogo</a>
        <span style="color:rgba(255,255,255,.7);font-size:14px"><?= htmlspecialchars($_SESSION['nome_completo']) ?></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-label">Prestiti Attivi</div>
            <div class="stat-value"><?= $stats['prestiti_attivi'] ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Prestiti Scaduti</div>
            <div class="stat-value"><?= $stats['prestiti_scaduti'] ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Prestiti Totali</div>
            <div class="stat-value"><?= $stats['totale_prestiti'] ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-label">Studenti Registrati</div>
            <div class="stat-value"><?= $stats['totale_utenti'] ?></div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?tab=attivi"  class="tab <?= $tab==='attivi'  ? 'active':'' ?>"> Prestiti Attivi (<?= $stats['prestiti_attivi'] ?>)</a>
        <a href="?tab=storico" class="tab <?= $tab==='storico' ? 'active':'' ?>"> Storico Completo</a>
        <a href="?tab=utenti"  class="tab <?= $tab==='utenti'  ? 'active':'' ?>"> Utenti (<?= count($utenti) ?>)</a>
    </div>

    <?php if ($tab === 'attivi'): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Prestiti Attivi</span>
            <span class="count-badge"><?= count($prestiti_attivi) ?> libri fuori sede</span>
        </div>
        <?php if (empty($prestiti_attivi)): ?>
            <div class="empty"><div class="empty-icon"></div>Nessun prestito attivo al momento.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Studente</th><th>Libro</th><th>Copia</th><th>Data Prestito</th><th>Scadenza</th><th>Stato</th><th>Azione</th></tr></thead>
            <tbody>
            <?php foreach ($prestiti_attivi as $p): ?>
            <tr>
                <td class="user-cell">
                    <strong><?= htmlspecialchars($p['cognome'].' '.$p['nome']) ?></strong>
                    <small><?= htmlspecialchars($p['username']) ?></small>
                </td>
                <td>
                    <strong><?= htmlspecialchars($p['titolo']) ?></strong><br>
                    <small style="color:#888"><?= htmlspecialchars($p['autore']) ?></small>
                </td>
                <td><code style="background:#f5f5f5;padding:2px 6px;border-radius:3px;font-size:11px"><?= htmlspecialchars($p['codice_copia']) ?></code></td>
                <td><?= date('d/m/Y', strtotime($p['data_prestito'])) ?></td>
                <td><?= date('d/m/Y', strtotime($p['data_scadenza'])) ?></td>
                <td>
                    <?php if ($p['scaduto']): ?>
                        <span class="badge badge-danger"> Scaduto (<?= abs($p['giorni_rimanenti']) ?> gg)</span>
                    <?php elseif ($p['giorni_rimanenti'] <= 3): ?>
                        <span class="badge badge-warning"> <?= $p['giorni_rimanenti'] ?> gg</span>
                    <?php else: ?>
                        <span class="badge badge-ok">✓ <?= $p['giorni_rimanenti'] ?> gg</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Confermi la restituzione?')">
                        <input type="hidden" name="action" value="restituisci">
                        <input type="hidden" name="prestito_id" value="<?= $p['prestito_id'] ?>">
                        <button type="submit" class="btn-restituisci"> RESTITUISCI</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'storico'): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Storico Completo Prestiti</span>
            <span class="count-badge"><?= count($storico) ?> record</span>
        </div>
        <?php if (empty($storico)): ?>
            <div class="empty"><div class="empty-icon"></div>Nessun prestito registrato.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>#</th><th>Studente</th><th>Libro</th><th>Copia</th><th>Prestito</th><th>Scadenza</th><th>Restituzione</th><th>Stato</th></tr></thead>
            <tbody>
            <?php foreach ($storico as $p): ?>
            <tr>
                <td style="color:#ccc;font-size:11px"><?= $p['prestito_id'] ?></td>
                <td class="user-cell">
                    <strong><?= htmlspecialchars($p['cognome'].' '.$p['nome']) ?></strong>
                    <small><?= htmlspecialchars($p['username']) ?></small>
                </td>
                <td>
                    <?= htmlspecialchars($p['titolo']) ?><br>
                    <small style="color:#888"><?= htmlspecialchars($p['autore']) ?></small>
                </td>
                <td><code style="background:#f5f5f5;padding:2px 6px;border-radius:3px;font-size:11px"><?= htmlspecialchars($p['codice_copia']) ?></code></td>
                <td><?= date('d/m/Y', strtotime($p['data_prestito'])) ?></td>
                <td><?= date('d/m/Y', strtotime($p['data_scadenza'])) ?></td>
                <td><?= $p['data_restituzione'] ? date('d/m/Y', strtotime($p['data_restituzione'])) : '<span style="color:#aaa">—</span>' ?></td>
                <td>
                    <?php if ($p['stato'] === 'attivo'): ?>
                        <span class="badge badge-warning">In corso</span>
                    <?php else: ?>
                        <span class="badge badge-done">Restituito</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'utenti'): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Utenti Registrati</span>
            <span class="count-badge"><?= count($utenti) ?> totali</span>
        </div>
        <?php if (empty($utenti)): ?>
            <div class="empty"><div class="empty-icon">👤</div>Nessun utente.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>#</th><th>Nome</th><th>Username</th><th>Email</th><th>Ruolo</th><th>Prestiti Attivi</th><th>Registrato il</th></tr></thead>
            <tbody>
            <?php foreach ($utenti as $u): ?>
            <tr>
                <td style="color:#ccc;font-size:11px"><?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['cognome'].' '.$u['nome']) ?></strong></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td style="color:#666;font-size:12px"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td>
                    <?php if ($u['ruolo'] === 'bibliotecario'): ?>
                        <span class="badge badge-biblio">Bibliotecario</span>
                    <?php else: ?>
                        <span class="badge badge-studente">Studente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['prestiti_attivi'] > 0): ?>
                        <span class="badge badge-warning"><?= $u['prestiti_attivi'] ?> libro/i</span>
                    <?php else: ?>
                        <span style="color:#aaa;font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td style="color:#888;font-size:12px"><?= date('d/m/Y', strtotime($u['data_creazione'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>