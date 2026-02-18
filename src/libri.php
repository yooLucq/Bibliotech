<?php


require_once 'includes/auth.php';
require_once 'includes/libri.php';

requireLogin();

$libri = getAllLibri();
$current_user = $_SESSION['nome_completo'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo Libri - BiblioTech</title>
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
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .book-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .book-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .book-author {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .book-info {
            font-size: 13px;
            color: #777;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .availability {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .availability-label {
            font-size: 13px;
            color: #555;
        }
        
        .availability-count {
            font-weight: bold;
            font-size: 16px;
        }
        
        .available {
            color: #27ae60;
        }
        
        .unavailable {
            color: #e74c3c;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 10px;
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
            <span><?= htmlspecialchars($current_user) ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <h1> Catalogo Biblioteca</h1>
        
        <?php if (empty($libri)): ?>
            <div class="empty-state">
                <h2>Nessun libro disponibile</h2>
                <p>Il catalogo è momentaneamente vuoto.</p>
            </div>
        <?php else: ?>
            <div class="books-grid">
                <?php foreach ($libri as $libro): ?>
                    <div class="book-card">
                        <div class="book-title"><?= htmlspecialchars($libro['titolo']) ?></div>
                        <div class="book-author">di <?= htmlspecialchars($libro['autore']) ?></div>
                        
                        <div class="book-info">
                            <?php if ($libro['editore']): ?>
                                <div><strong>Editore:</strong> <?= htmlspecialchars($libro['editore']) ?></div>
                            <?php endif; ?>
                            <?php if ($libro['anno_pubblicazione']): ?>
                                <div><strong>Anno:</strong> <?= htmlspecialchars($libro['anno_pubblicazione']) ?></div>
                            <?php endif; ?>
                            <?php if ($libro['genere']): ?>
                                <div><strong>Genere:</strong> <?= htmlspecialchars($libro['genere']) ?></div>
                            <?php endif; ?>
                            <?php if ($libro['isbn']): ?>
                                <div><strong>ISBN:</strong> <?= htmlspecialchars($libro['isbn']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="availability">
                            <span class="availability-label">Copie disponibili:</span>
                            <span class="availability-count <?= $libro['copie_disponibili'] > 0 ? 'available' : 'unavailable' ?>">
                                <?= $libro['copie_disponibili'] ?> / <?= $libro['copie_totali'] ?>
                            </span>
                        </div>
                        
                        <a href="libro.php?id=<?= $libro['libro_id'] ?>" class="btn btn-primary">
                            Vedi Dettagli
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
