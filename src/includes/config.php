<?php

define('DB_HOST', 'db'); 
define('DB_NAME', 'myapp_db');
define('DB_USER', 'myuser');
define('DB_PASS', 'mypassword');
define('DB_CHARSET', 'utf8mb4');

/**
 * Ottiene una connessione PDO al database
 * @return PDO
 * @throws PDOException
 */

function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Errore connessione database: " . $e->getMessage());
            throw new PDOException("Impossibile connettersi al database");
        }
    }
    
    return $pdo;
}
?>
