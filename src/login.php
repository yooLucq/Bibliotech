<?php

require_once 'includes/auth.php';

// Se già loggato, redirect alla home
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

// Gestione submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Inserisci username e password';
    } else {
        $user = login($username, $password);
        
        if ($user) {
            // Login riuscito - redirect alla home
            header('Location: index.php');
            exit();
        } else {
            $error = 'Username o password errati';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BiblioTech</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 3px solid #c33;
        }
        
        .info-box {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 3px solid #2196F3;
        }
        
        .info-box h3 {
            color: #1976D2;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .info-box p {
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }
        
        .credentials {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 8px;
            border-radius: 3px;
            margin: 5px 0;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>BiblioTech</h1>
            <p>Sistema Gestione Prestiti Bibliotecari</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                >
            </div>
            
            <button type="submit" class="btn-login">Accedi</button>
        </form>
        
        <div style="text-align:center; margin-top: 20px; font-size: 13px; color: #888;">
            Non hai un account? <a href="registrazione.php" style="color:#667eea; font-weight:600; text-decoration:none;">Registrati qui</a>
        </div>
        </div>
    </div>
</body>
</html>