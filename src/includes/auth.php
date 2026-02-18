<?php

require_once __DIR__ . '/config.php';

// Avvia la sessione se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function login($username, $password) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, nome, cognome, email, ruolo 
            FROM utenti 
            WHERE username = ? AND attivo = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login riuscito - inizializza la sessione
            session_regenerate_id(true); // Previene session fixation
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nome_completo'] = $user['nome'] . ' ' . $user['cognome'];
            $_SESSION['ruolo'] = $user['ruolo'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // Aggiorna ultimo accesso nel database
            $updateStmt = $pdo->prepare("UPDATE utenti SET ultimo_accesso = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return $user;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Errore durante login: " . $e->getMessage());
        return false;
    }
}


function logout() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}


function isLoggedIn() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Controllo timeout inattività (30 minuti)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logout();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Richiede che l'utente sia autenticato, altrimenti redirect a login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}


function requireRole($required_role) {
    requireLogin();
    
    if ($_SESSION['ruolo'] !== $required_role) {
        http_response_code(403);
        die('
            <!DOCTYPE html>
            <html lang="it">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Accesso Negato - BiblioTech</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    h1 { color: #e74c3c; }
                    a { color: #3498db; text-decoration: none; }
                </style>
            </head>
            <body>
                <h1> 
                Accesso Negato</h1>
                <p>Non hai i permessi necessari per accedere a questa pagina.</p>
                <p><a href="index.php">← Torna alla Home</a></p>
            </body>
            </html>
        ');
    }
}


function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}


function getCurrentUserRole() {
    return $_SESSION['ruolo'] ?? null;
}


function isBibliotecario() {
    return getCurrentUserRole() === 'bibliotecario';
}


function isStudente() {
    return getCurrentUserRole() === 'studente';
}
?>
