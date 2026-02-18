<?php

require_once 'includes/auth.php';
require_once 'includes/libri.php';

requireRole('bibliotecario');

$message = '';
$message_type = '';
$edit_libro = null;

try {
    $pdo = getDbConnection();

    // ELIMINA 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'elimina') {
        $id = (int)$_POST['libro_id'];

        // Controlla che non ci siano prestiti attivi
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM prestiti p
            JOIN copie c ON p.copia_id = c.id
            WHERE c.libro_id = ? AND p.data_restituzione IS NULL
        ");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Impossibile eliminare: il libro ha prestiti attivi.';
            $message_type = 'error';
        } else {
            $pdo->prepare("DELETE FROM libri WHERE id = ?")->execute([$id]);
            $message = 'Libro eliminato con successo.';
            $message_type = 'success';
        }
    }

    // SALVA (aggiungi o modifica)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'salva') {
        $id                  = (int)($_POST['libro_id'] ?? 0);
        $titolo              = trim($_POST['titolo'] ?? '');
        $autore              = trim($_POST['autore'] ?? '');
        $editore             = trim($_POST['editore'] ?? '') ?: null;
        $anno_pubblicazione  = trim($_POST['anno_pubblicazione'] ?? '') ?: null;
        $genere              = trim($_POST['genere'] ?? '') ?: null;
        $descrizione         = trim($_POST['descrizione'] ?? '') ?: null;
        $num_copie           = max(1, (int)($_POST['num_copie'] ?? 1));

        if (empty($titolo) || empty($autore)) {
            $message = 'Titolo e Autore sono obbligatori.';
            $message_type = 'error';
        } else {
            $pdo->beginTransaction();

            if ($id > 0) {
                // MODIFICA libro esistente
                $stmt = $pdo->prepare("
                    UPDATE libri SET titolo=?, autore=?, isbn=?, editore=?,
                    anno_pubblicazione=?, genere=?, descrizione=?, copertina_url=?
                    WHERE id=?
                ");
                $stmt->execute([$titolo, $autore, $isbn, $editore,
                                $anno_pubblicazione, $genere, $descrizione, $copertina_url, $id]);
                $message = 'Libro aggiornato con successo.';
            } else {
                // NUOVO libro
                $stmt = $pdo->prepare("
                    INSERT INTO libri (titolo, autore, isbn, editore,
                    anno_pubblicazione, genere, descrizione, copertina_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$titolo, $autore, $isbn, $editore,
                                $anno_pubblicazione, $genere, $descrizione, $copertina_url]);
                $id = $pdo->lastInsertId();

                // Aggiunge le copie richieste
                for ($i = 1; $i <= $num_copie; $i++) {
                    $codice = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $titolo));
                    $codice = substr($codice, 0, 6) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                    // Rende univoco il codice aggiungendo l'id del libro
                    $codice = $id . '-' . $codice;
                    $pdo->prepare("INSERT INTO copie (libro_id, codice_copia) VALUES (?, ?)")
                        ->execute([$id, $codice]);
                }

                $message = "Libro aggiunto con successo ($num_copie " . ($num_copie === 1 ? 'copia' : 'copie') . ").";
            }

            $pdo->commit();
            $message_type = 'success';
        }
    }

    // CARICA PER MODIFICA 
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM libri WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $edit_libro = $stmt->fetch();
    }

    $libri = getAllLibri();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $message = 'Errore database: ' . $e->getMessage();
    $message_type = 'error';
    $libri = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Catalogo - BiblioTech</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body { font-family:'Segoe UI',sans-serif; background:#f5f5f5; }

        /* NAV */
        .navbar {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color:white; padding:15px 30px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .navbar-brand { font-size:22px; font-weight:bold; }
        .navbar-right { display:flex; gap:20px; align-items:center; }
        .navbar-right a { color:white; text-decoration:none; font-weight:500; }
        .badge-role { background:rgba(255,255,255,.2); padding:4px 10px; border-radius:12px; font-size:12px; }
        .btn-logout {
            background:rgba(255,255,255,.2); border:1px solid white;
            color:white; padding:7px 14px; border-radius:5px; text-decoration:none; font-size:13px;
        }

        /* LAYOUT */
        .container { max-width:1300px; margin:30px auto; padding:0 20px; display:grid; grid-template-columns:380px 1fr; gap:25px; }

        /* FORM */
        .form-card {
            background:white; border-radius:10px; padding:25px;
            box-shadow:0 2px 10px rgba(0,0,0,.08); height:fit-content; position:sticky; top:20px;
        }
        .form-card h2 { color:#333; margin-bottom:20px; font-size:18px; }

        .form-group { margin-bottom:14px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        input, textarea, select {
            width:100%; padding:9px 12px; border:1.5px solid #ddd;
            border-radius:6px; font-size:13px; outline:none; transition:border-color .2s;
            font-family:inherit;
        }
        input:focus, textarea:focus { border-color:#667eea; }
        textarea { resize:vertical; min-height:80px; }

        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        .btn-submit {
            width:100%; padding:11px; background:linear-gradient(135deg,#667eea,#764ba2);
            color:white; border:none; border-radius:6px; font-size:14px;
            font-weight:600; cursor:pointer; transition:transform .2s;
        }
        .btn-submit:hover { transform:translateY(-1px); }

        .btn-cancel {
            width:100%; padding:10px; background:white; color:#667eea;
            border:2px solid #667eea; border-radius:6px; font-size:14px;
            font-weight:600; cursor:pointer; margin-bottom:10px; text-decoration:none;
            display:block; text-align:center; transition:all .2s;
        }
        .btn-cancel:hover { background:#667eea; color:white; }

        /* ALERT */
        .alert {
            padding:12px 16px; border-radius:7px; margin-bottom:20px;
            border-left:4px solid; font-size:14px;
        }
        .alert-success { background:#d4edda; border-color:#28a745; color:#155724; }
        .alert-error   { background:#f8d7da; border-color:#dc3545; color:#721c24; }

        /* TABLE */
        .table-card { background:white; border-radius:10px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .table-card h2 { color:#333; margin-bottom:20px; font-size:18px; }

        table { width:100%; border-collapse:collapse; }
        th, td { padding:11px 12px; text-align:left; border-bottom:1px solid #eee; font-size:13px; }
        th { background:#f8f9fa; font-weight:600; color:#555; }
        tr:hover td { background:#fafafa; }

        .copies-badge {
            display:inline-block; padding:3px 10px; border-radius:12px;
            font-size:12px; font-weight:600;
        }
        .copies-ok  { background:#d4edda; color:#155724; }
        .copies-low { background:#fff3cd; color:#856404; }
        .copies-none{ background:#f8d7da; color:#721c24; }

        .actions { display:flex; gap:8px; }
        .btn-edit {
            padding:5px 12px; background:#667eea; color:white;
            border:none; border-radius:4px; cursor:pointer; font-size:12px;
            text-decoration:none; display:inline-block;
        }
        .btn-edit:hover { background:#5568d3; }
        .btn-delete {
            padding:5px 12px; background:#e74c3c; color:white;
            border:none; border-radius:4px; cursor:pointer; font-size:12px;
        }
        .btn-delete:hover { background:#c0392b; }

        .title-cell strong { display:block; color:#333; }
        .title-cell small  { color:#888; }

        /* COPIE SECTION */
        .copie-form { margin-top:12px; padding-top:12px; border-top:1px dashed #ddd; }
        .copie-form label { color:#667eea; }
        .copie-hint { font-size:11px; color:#aaa; margin-top:4px; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand"> BiblioTech - Admin</div>
    <div class="navbar-right">
        <span class="badge-role"> BIBLIOTECARIO</span>
        <a href="gestione_restituzioni.php">Restituzioni</a>
        <span><?= htmlspecialchars($_SESSION['nome_completo']) ?></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- FORM AGGIUNGI / MODIFICA -->
    <div class="form-card">
        <h2><?= $edit_libro ? ' Modifica Libro' : ' Aggiungi Libro' ?></h2>

        <?php if (!empty($message) && $message_type === 'error'): ?>
            <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($edit_libro): ?>
            <a href="gestione_catalogo.php" class="btn-cancel">✕ Annulla modifica</a>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="salva">
            <input type="hidden" name="libro_id" value="<?= $edit_libro['id'] ?? 0 ?>">

            <div class="form-group">
                <label>Titolo *</label>
                <input type="text" name="titolo" required
                       value="<?= htmlspecialchars($edit_libro['titolo'] ?? $_POST['titolo'] ?? '') ?>"
                       placeholder="Es. Il Nome della Rosa">
            </div>

            <div class="form-group">
                <label>Autore *</label>
                <input type="text" name="autore" required
                       value="<?= htmlspecialchars($edit_libro['autore'] ?? $_POST['autore'] ?? '') ?>"
                       placeholder="Es. Umberto Eco">
            </div>

            <div class="form-group">
                <label>Anno</label>
                <input type="number" name="anno_pubblicazione" min="1000" max="<?= date('Y') ?>"
                       value="<?= htmlspecialchars($edit_libro['anno_pubblicazione'] ?? $_POST['anno_pubblicazione'] ?? '') ?>"
                       placeholder="<?= date('Y') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Editore</label>
                    <input type="text" name="editore"
                           value="<?= htmlspecialchars($edit_libro['editore'] ?? $_POST['editore'] ?? '') ?>"
                           placeholder="Es. Mondadori">
                </div>
                <div class="form-group">
                    <label>Genere</label>
                    <input type="text" name="genere"
                           value="<?= htmlspecialchars($edit_libro['genere'] ?? $_POST['genere'] ?? '') ?>"
                           placeholder="Es. Romanzo">
                </div>
            </div>

            <div class="form-group">
                <label>Descrizione</label>
                <textarea name="descrizione" placeholder="Breve sinossi del libro..."><?= htmlspecialchars($edit_libro['descrizione'] ?? $_POST['descrizione'] ?? '') ?></textarea>
            </div>

            <?php if (!$edit_libro): ?>
                <div class="copie-form">
                    <div class="form-group">
                        <label>Numero di copie iniziali</label>
                        <input type="number" name="num_copie" min="1" max="50"
                               value="<?= (int)($_POST['num_copie'] ?? 1) ?>">
                        <div class="copie-hint">Quante copie fisiche aggiungere al magazzino</div>
                    </div>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <?= $edit_libro ? ' Salva Modifiche' : ' Aggiungi Libro' ?>
            </button>
        </form>
    </div>

    <!-- TABELLA CATALOGO -->
    <div class="table-card">
        <h2> Catalogo Libri (<?= count($libri) ?> titoli)</h2>

        <?php if (!empty($message) && $message_type === 'success'): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (empty($libri)): ?>
            <p style="color:#888; text-align:center; padding:40px;">Nessun libro nel catalogo.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Titolo / Autore</th>
                    <th>Genere</th>
                    <th>Anno</th>
                    <th>Copie</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libri as $libro): ?>
                <tr>
                    <td class="title-cell">
                        <strong><?= htmlspecialchars($libro['titolo']) ?></strong>
                        <small><?= htmlspecialchars($libro['autore']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($libro['genere'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($libro['anno_pubblicazione'] ?? '-') ?></td>
                    <td>
                        <?php
                        $d = (int)$libro['copie_disponibili'];
                        $t = (int)$libro['copie_totali'];
                        $cls = $d === 0 ? 'copies-none' : ($d <= 2 ? 'copies-low' : 'copies-ok');
                        ?>
                        <span class="copies-badge <?= $cls ?>"><?= $d ?> / <?= $t ?></span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="gestione_catalogo.php?edit=<?= $libro['libro_id'] ?>" class="btn-edit"> Modifica</a>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Eliminare definitivamente questo libro?')">
                                <input type="hidden" name="action" value="elimina">
                                <input type="hidden" name="libro_id" value="<?= $libro['libro_id'] ?>">
                                <button type="submit" class="btn-delete"> Elimina</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>