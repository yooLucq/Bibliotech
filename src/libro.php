<?php


require_once 'includes/auth.php';
require_once 'includes/libri.php';
require_once 'includes/prestiti.php';

requireLogin();

$libro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$message_type = '';

// Gestione richiesta prestito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'prestito') {
    $utente_id = getCurrentUserId();
    
    // Verifica se ha già in prestito questo libro
    if (haGiaInPrestito($utente_id, $libro_id)) {
        $message = 'Hai già in prestito una copia di questo libro';
        $message_type = 'error';
    } else {
        $result = creaPrestito($libro_id, $utente_id, null);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

$libro = getLibroById($libro_id);

if (!$libro) {
    header('Location: libri.php');
    exit();
}

$copie = getCopieByLibro($libro_id);
$puo_prendere_prestito = $libro['copie_disponibili'] > 0 && !haGiaInPrestito(getCurrentUserId(), $libro_id);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($libro['titolo']) ?> - BiblioTech</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        .navbar-links a:hover {
            opacity: 0.8;
        }
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border: 1px solid white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .book-detail {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .book-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .book-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .book-author {
            font-size: 18px;
            color: #666;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            line-height: 1.6;
            color: #555;
        }
        
        .availability-section {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .availability-header {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .copies-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .copies-table th,
        .copies-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .copies-table th {
            background: rgba(102, 126, 234, 0.1);
            font-weight: 600;
            color: #555;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-disponibile {
            background: #d4edda;
            color: #155724;
        }
        
        .status-prestito {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-manutenzione {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-perso {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-section {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .warning-text {
            color: #856404;
            font-size: 14px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">BiblioTech</div>
        <div class="navbar-user">
            <div class="navbar-links">
                <a href="libri.php">Catalogo</a>
                <a href="prestiti.php">I Miei Prestiti</a>
            </div>
            <span><?= htmlspecialchars($_SESSION['nome_completo']) ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <a href="libri.php" class="back-link">← Torna al Catalogo</a>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="book-detail">
            <div class="book-header">
                <h1 class="book-title"><?= htmlspecialchars($libro['titolo']) ?></h1>
                <p class="book-author">di <?= htmlspecialchars($libro['autore']) ?></p>
            </div>
            
            <div class="info-grid">
                <?php if ($libro['isbn']): ?>
                    <div class="info-label">ISBN:</div>
                    <div class="info-value"><?= htmlspecialchars($libro['isbn']) ?></div>
                <?php endif; ?>
                
                <?php if ($libro['editore']): ?>
                    <div class="info-label">Editore:</div>
                    <div class="info-value"><?= htmlspecialchars($libro['editore']) ?></div>
                <?php endif; ?>
                
                <?php if ($libro['anno_pubblicazione']): ?>
                    <div class="info-label">Anno di pubblicazione:</div>
                    <div class="info-value"><?= htmlspecialchars($libro['anno_pubblicazione']) ?></div>
                <?php endif; ?>
                
                <?php if ($libro['genere']): ?>
                    <div class="info-label">Genere:</div>
                    <div class="info-value"><?= htmlspecialchars($libro['genere']) ?></div>
                <?php endif; ?>
            </div>
            
            <?php if ($libro['descrizione']): ?>
                <div class="description">
                    <strong>Descrizione:</strong><br>
                    <?= nl2br(htmlspecialchars($libro['descrizione'])) ?>
                </div>
            <?php endif; ?>
            
            <div class="availability-section">
                <h3 class="availability-header">
                    Disponibilità: 
                    <span style="color: <?= $libro['copie_disponibili'] > 0 ? '#27ae60' : '#e74c3c' ?>">
                        <?= $libro['copie_disponibili'] ?> / <?= $libro['copie_totali'] ?> copie disponibili
                    </span>
                </h3>
                
                <table class="copies-table">
                    <thead>
                        <tr>
                            <th>Codice Copia</th>
                            <th>Stato</th>
                            <th>Data Acquisizione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($copie as $copia): ?>
                            <tr>
                                <td><?= htmlspecialchars($copia['codice_copia']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $copia['stato'] ?>">
                                        <?= ucfirst($copia['stato']) ?>
                                    </span>
                                </td>
                                <td><?= $copia['data_acquisizione'] ? date('d/m/Y', strtotime($copia['data_acquisizione'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-section">
                <?php if ($puo_prendere_prestito): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="prestito">
                        <button type="submit" class="btn btn-primary">
                             PRENDI IN PRESTITO
                        </button>
                    </form>
                    <span style="color: #666; font-size: 14px;">
                        (Il prestito ha durata di 14 giorni)
                    </span>
                <?php elseif (haGiaInPrestito(getCurrentUserId(), $libro_id)): ?>
                    <button class="btn btn-disabled" disabled>
                        Hai già in prestito questo libro
                    </button>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>
                        Nessuna copia disponibile
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
