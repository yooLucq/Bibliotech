<?php


require_once __DIR__ . '/config.php';


function getAllLibri() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM vista_disponibilita_libri ORDER BY titolo");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Errore getAllLibri: " . $e->getMessage());
        return [];
    }
}

/**
 * Ottiene i dettagli di un libro specifico con disponibilità
 * @param int $libro_id
 * @return array|false
 */
function getLibroById($libro_id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM vista_disponibilita_libri WHERE libro_id = ?");
        $stmt->execute([$libro_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Errore getLibroById: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se un libro ha copie disponibili
 * @param int $libro_id
 * @return bool
 */
function hasCopieDisponibili($libro_id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as disponibili 
            FROM copie 
            WHERE libro_id = ? AND stato = 'disponibile'
        ");
        $stmt->execute([$libro_id]);
        $result = $stmt->fetch();
        return $result['disponibili'] > 0;
    } catch (PDOException $e) {
        error_log("Errore hasCopieDisponibili: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottiene tutte le copie di un libro
 * @param int $libro_id
 * @return array
 */
function getCopieByLibro($libro_id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM copie 
            WHERE libro_id = ? 
            ORDER BY codice_copia
        ");
        $stmt->execute([$libro_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Errore getCopieByLibro: " . $e->getMessage());
        return [];
    }
}
?>
