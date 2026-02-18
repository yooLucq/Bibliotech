<?php


require_once 'includes/auth.php';

// Se già loggato, redirect alla home
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $cognome  = trim($_POST['cognome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validazione
    if (empty($nome))     $errors[] = 'Il nome è obbligatorio.';
    if (empty($cognome))  $errors[] = 'Il cognome è obbligatorio.';
    if (empty($username)) $errors[] = 'Lo username è obbligatorio.';
    if (strlen($username) < 3) $errors[] = 'Lo username deve avere almeno 3 caratteri.';
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) $errors[] = 'Lo username può contenere solo lettere, numeri, punti, trattini e underscore.';
    if (empty($email))    $errors[] = "L'email è obbligatoria.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato email non valido.";
    if (empty($password)) $errors[] = 'La password è obbligatoria.';
    if (strlen($password) < 8) $errors[] = 'La password deve avere almeno 8 caratteri.';
    if ($password !== $password_confirm) $errors[] = 'Le password non coincidono.';

    // Se nessun errore, salva nel DB
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();

            // Verifica username già esistente
            $stmt = $pdo->prepare("SELECT id FROM utenti WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username già in uso, scegline un altro.';
            }

            // Verifica email già esistente
            $stmt = $pdo->prepare("SELECT id FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email già registrata.';
            }

            if (empty($errors)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO utenti (username, password_hash, nome, cognome, email, ruolo)
                    VALUES (?, ?, ?, ?, ?, 'studente')
                ");
                $stmt->execute([$username, $hash, $nome, $cognome, $email]);
                $success = true;
            }

        } catch (PDOException $e) {
            error_log("Errore registrazione: " . $e->getMessage());
            $errors[] = 'Errore durante la registrazione. Riprova.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - BiblioTech</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 480px;
        }

        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #667eea; font-size: 28px; }
        .logo p { color: #888; font-size: 14px; margin-top: 5px; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #444;
            font-size: 13px;
        }

        input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            transition: border-color 0.2s;
            outline: none;
        }

        input:focus { border-color: #667eea; }

        .hint {
            font-size: 11px;
            color: #aaa;
            margin-top: 4px;
        }

        .errors {
            background: #fff0f0;
            border-left: 4px solid #e74c3c;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .errors p {
            color: #c0392b;
            font-size: 13px;
            line-height: 1.8;
        }

        .success-box {
            background: #f0fff4;
            border-left: 4px solid #27ae60;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
        }

        .success-box h2 { color: #27ae60; margin-bottom: 10px; }
        .success-box p { color: #555; font-size: 14px; margin-bottom: 15px; }

        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .btn-outline {
            display: block;
            text-align: center;
            padding: 11px;
            border: 2px solid #667eea;
            color: #667eea;
            background: white;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-outline:hover { background: #667eea; color: white; }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #888;
        }

        .login-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 6px;
            background: #eee;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>BiblioTech</h1>
        <p>Crea il tuo account per accedere alla biblioteca</p>
    </div>

    <?php if ($success): ?>
        <div class="success-box">
            <h2>Registrazione completata!</h2>
            <p>Il tuo account è stato creato con successo. Ora puoi accedere con le tue credenziali.</p>
            <a href="login.php" class="btn-outline">Vai al Login →</a>
        </div>
    <?php else: ?>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $e): ?>
                    <p>⚠ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome"
                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                           placeholder="Mario" required>
                </div>
                <div class="form-group">
                    <label for="cognome">Cognome</label>
                    <input type="text" id="cognome" name="cognome"
                           value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>"
                           placeholder="Rossi" required>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="mario.rossi" required>
                <div class="hint">Solo lettere, numeri, punti e trattini. Min. 3 caratteri.</div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="mario.rossi@panettipitagora.edu.it" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Almeno 8 caratteri" required
                       oninput="checkStrength(this.value)">
                <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                </div>
                <div class="hint" id="strength-text">Min. 8 caratteri</div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Conferma Password</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       placeholder="Ripeti la password" required>
            </div>

            <button type="submit" class="btn">Crea Account</button>
        </form>

        <div class="login-link">
            Hai già un account? <a href="login.php">Accedi qui</a>
        </div>

    <?php endif; ?>
</div>

<script>
function checkStrength(val) {
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: '0%',   color: '#eee',    label: 'Min. 8 caratteri' },
        { w: '25%',  color: '#e74c3c', label: 'Molto debole' },
        { w: '50%',  color: '#e67e22', label: 'Debole' },
        { w: '75%',  color: '#f1c40f', label: 'Discreta' },
        { w: '90%',  color: '#2ecc71', label: 'Buona' },
        { w: '100%', color: '#27ae60', label: 'Ottima' },
    ];

    const l = levels[Math.min(score, 5)];
    bar.style.width = l.w;
    bar.style.background = l.color;
    text.textContent = l.label;
    text.style.color = l.color === '#eee' ? '#aaa' : l.color;
}
</script>
</body>
</html>
