<?php
/**
 * BiblioTech - Home Page
 * Redirect alla pagina appropriata in base al ruolo
 */

require_once 'includes/auth.php';

requireLogin();

// Redirect in base al ruolo
if (isBibliotecario()) {
    header('Location: gestione_restituzioni.php');
} else {
    header('Location: libri.php');
}
exit();
?>
