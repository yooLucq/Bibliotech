<?php

require_once 'includes/auth.php';
require_once 'includes/prestiti.php';

requireLogin();

$utente_id = getCurrentUserId();
$prestiti = getPrestitiAttiviUtente($utente_id);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Prestiti - BiblioTech</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        
        .prestiti-table-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            font-weight: 600;
            color: #555;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-scadenza {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-scaduto {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
        }
        
        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .empty-state a:hover {
            background: #5568d3;
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
        <h1>I Miei Prestiti Attivi</h1>
        
        <?php if (empty($prestiti)): ?>
            <div class="prestiti-table-container">
                <div class="empty-state">
                    <h2>Nessun prestito attivo</h2>
                    <p>Non hai libri in prestito al momento.</p>
                    <a href="libri.php">Sfoglia il Catalogo</a>
                </div>
            </div>
        <?php else: ?>
            <div class="prestiti-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Autore</th>
                            <th>Codice Copia</th>
                            <th>Data Prestito</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestiti as $prestito): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($prestito['titolo']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($prestito['autore']) ?></td>
                                <td><?= htmlspecialchars($prestito['codice_copia']) ?></td>
                                <td><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($prestito['data_scadenza'])) ?></td>
                                <td>
                                    <?php
                                    $giorni = $prestito['giorni_rimanenti'];
                                    if ($prestito['scaduto']) {
                                        $badge_class = 'status-scaduto';
                                        $badge_text = 'SCADUTO (' . abs($giorni) . ' gg di ritardo)';
                                    } elseif ($giorni <= 3) {
                                        $badge_class = 'status-scadenza';
                                        $badge_text = 'In scadenza (' . $giorni . ' gg)';
                                    } else {
                                        $badge_class = 'status-ok';
                                        $badge_text = 'OK (' . $giorni . ' gg rimanenti)';
                                    }
                                    ?>
                                    <span class="status-badge <?= $badge_class ?>">
                                        <?= $badge_text ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
