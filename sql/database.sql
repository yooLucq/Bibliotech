
USE myapp_db;


DROP TABLE IF EXISTS prestiti;
DROP TABLE IF EXISTS copie;
DROP TABLE IF EXISTS libri;
DROP TABLE IF EXISTS utenti;

CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    ruolo ENUM('studente', 'bibliotecario') NOT NULL DEFAULT 'studente',
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso TIMESTAMP NULL,
    attivo BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_ruolo (ruolo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE libri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE,
    titolo VARCHAR(255) NOT NULL,
    autore VARCHAR(255) NOT NULL,
    editore VARCHAR(150),
    anno_pubblicazione YEAR,
    genere VARCHAR(100),
    descrizione TEXT,
    copertina_url VARCHAR(500),
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_titolo (titolo),
    INDEX idx_autore (autore),
    INDEX idx_isbn (isbn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    id INT AUTO_INCREMENT PRIMARY KEY,
    libro_id INT NOT NULL,
    codice_copia VARCHAR(50) NOT NULL UNIQUE COMMENT 'Es: 1984-001, 1984-002',
    stato ENUM('disponibile', 'prestito', 'manutenzione', 'perso') NOT NULL DEFAULT 'disponibile',
    note TEXT,
    data_acquisizione DATE,
    FOREIGN KEY (libro_id) REFERENCES libri(id) ON DELETE CASCADE,
    INDEX idx_libro (libro_id),
    INDEX idx_stato (stato),
    INDEX idx_codice (codice_copia)
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE prestiti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    copia_id INT NOT NULL,
    utente_id INT NOT NULL,
    data_prestito TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_scadenza DATE NOT NULL,
    data_restituzione TIMESTAMP NULL,
    bibliotecario_prestito_id INT COMMENT 'Bibliotecario che ha registrato il prestito',
    bibliotecario_restituzione_id INT COMMENT 'Bibliotecario che ha registrato la restituzione',
    note TEXT,
    FOREIGN KEY (copia_id) REFERENCES copie(id) ON DELETE RESTRICT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE RESTRICT,
    FOREIGN KEY (bibliotecario_prestito_id) REFERENCES utenti(id) ON DELETE SET NULL,
    FOREIGN KEY (bibliotecario_restituzione_id) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_copia (copia_id),
    INDEX idx_utente (utente_id),
    INDEX idx_data_prestito (data_prestito),
    INDEX idx_data_restituzione (data_restituzione),
    INDEX idx_attivo (data_restituzione) COMMENT 'Prestiti attivi hanno data_restituzione NULL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO utenti (username, password_hash, nome, cognome, email, ruolo) VALUES
('admin', '$2y$10$X8uhNZI0UK0Vm/fLDAV4LOnuhFI1yGWpdebZT26KOBbws9zS6lkz6', 'Admin', 'Principale', 'admin.principale@panettipitagora.edu.it', 'bibliotecario');


CREATE OR REPLACE VIEW vista_disponibilita_libri AS
SELECT 
    l.id AS libro_id,
    l.titolo,
    l.autore,
    l.isbn,
    l.genere,
    l.editore,
    l.anno_pubblicazione,
    l.descrizione,
    l.copertina_url,
    COUNT(c.id) AS copie_totali,
    SUM(CASE WHEN c.stato = 'disponibile' THEN 1 ELSE 0 END) AS copie_disponibili,
    SUM(CASE WHEN c.stato = 'prestito' THEN 1 ELSE 0 END) AS copie_in_prestito
FROM libri l
LEFT JOIN copie c ON l.id = c.libro_id
GROUP BY l.id, l.titolo, l.autore, l.isbn, l.genere, l.editore, l.anno_pubblicazione, l.descrizione, l.copertina_url;

-- Vista: Prestiti attivi
CREATE OR REPLACE VIEW vista_prestiti_attivi AS
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
    CASE 
        WHEN CURDATE() > p.data_scadenza THEN TRUE 
        ELSE FALSE 
    END AS scaduto
FROM prestiti p
INNER JOIN utenti u ON p.utente_id = u.id
INNER JOIN copie c ON p.copia_id = c.id
INNER JOIN libri l ON c.libro_id = l.id
WHERE p.data_restituzione IS NULL
ORDER BY p.data_scadenza ASC;



SELECT '✓ Database BiblioTech creato con successo!' AS status;
SELECT 'Bibliotecario: admin / password123' AS credenziali_bibliotecario;
SELECT 'Tutte le email: @panettipitagora.edu.it' AS dominio_email;