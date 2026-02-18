<?php

require_once __DIR__ . '/config.php';

function creaPrestito($libro_id, $utente_id, $bibliotecario_id = null) {
    try {
        $pdo = getDbConnection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT id FROM copie 
            WHERE libro_id = ? AND stato = 'disponibile' 
            LIMIT 1 FOR UPDATE
        ");
        $stmt->execute([$libro_id]);
        $copia = $stmt->fetch();

        if (!$copia) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Nessuna copia disponibile'];
        }

        $copia_id = $copia['id'];
        $data_scadenza = date('Y-m-d', strtotime('+14 days'));

        $stmt = $pdo->prepare("
            INSERT INTO prestiti (copia_id, utente_id, data_scadenza, bibliotecario_prestito_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$copia_id, $utente_id, $data_scadenza, $bibliotecario_id]);

        $stmt = $pdo->prepare("UPDATE copie SET stato = 'prestito' WHERE id = ?");
        $stmt->execute([$copia_id]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Prestito registrato con successo', 'data_scadenza' => $data_scadenza];

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log("Errore creaPrestito: " . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante la creazione del prestito'];
    }
}

function registraRestituzione($prestito_id, $bibliotecario_id = null) {
    try {
        $pdo = getDbConnection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT copia_id, data_restituzione FROM prestiti WHERE id = ? FOR UPDATE");
        $stmt->execute([$prestito_id]);
        $prestito = $stmt->fetch();

        if (!$prestito) { $pdo->rollBack(); return ['success' => false, 'message' => 'Prestito non trovato']; }
        if ($prestito['data_restituzione'] !== null) { $pdo->rollBack(); return ['success' => false, 'message' => 'Prestito già restituito']; }

        $stmt = $pdo->prepare("UPDATE prestiti SET data_restituzione = NOW(), bibliotecario_restituzione_id = ? WHERE id = ?");
        $stmt->execute([$bibliotecario_id, $prestito_id]);

        $stmt = $pdo->prepare("UPDATE copie SET stato = 'disponibile' WHERE id = ?");
        $stmt->execute([$prestito['copia_id']]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Restituzione registrata con successo'];

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log("Errore registraRestituzione: " . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante la registrazione della restituzione'];
    }
}

function getPrestitiAttiviUtente($utente_id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT 
                p.id AS prestito_id,
                p.data_prestito,
                p.data_scadenza,
                l.id AS libro_id,
                l.titolo,
                l.autore,
                l.isbn,
                c.codice_copia,
                DATEDIFF(p.data_scadenza, CURDATE()) AS giorni_rimanenti,
                CASE WHEN CURDATE() > p.data_scadenza THEN 1 ELSE 0 END AS scaduto
            FROM prestiti p
            INNER JOIN copie c ON p.copia_id = c.id
            INNER JOIN libri l ON c.libro_id = l.id
            WHERE p.utente_id = ? AND p.data_restituzione IS NULL
            ORDER BY p.data_scadenza ASC
        ");
        $stmt->execute([$utente_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Errore getPrestitiAttiviUtente: " . $e->getMessage());
        return [];
    }
}

function getAllPrestitiAttivi() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("
            SELECT
                p.id AS prestito_id,
                p.data_prestito,
                p.data_scadenza,
                u.id AS utente_id,
                u.nome,
                u.cognome,
                u.username,
                l.id AS libro_id,
                l.titolo,
                l.autore,
                c.id AS copia_id,
                c.codice_copia,
                DATEDIFF(p.data_scadenza, CURDATE()) AS giorni_rimanenti,
                CASE WHEN CURDATE() > p.data_scadenza THEN 1 ELSE 0 END AS scaduto
            FROM prestiti p
            INNER JOIN utenti u ON p.utente_id = u.id
            INNER JOIN copie c ON p.copia_id = c.id
            INNER JOIN libri l ON c.libro_id = l.id
            WHERE p.data_restituzione IS NULL
            ORDER BY p.data_scadenza ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Errore getAllPrestitiAttivi: " . $e->getMessage());
        return [];
    }
}

function haGiaInPrestito($utente_id, $libro_id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM prestiti p
            INNER JOIN copie c ON p.copia_id = c.id
            WHERE p.utente_id = ? AND c.libro_id = ? AND p.data_restituzione IS NULL
        ");
        $stmt->execute([$utente_id, $libro_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Errore haGiaInPrestito: " . $e->getMessage());
        return false;
    }
}
?>