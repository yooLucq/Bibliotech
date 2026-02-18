--BiblioTech - Sistema di Gestione Prestiti Bibliotecari
Sistema web completo per la gestione digitalizzata dei prestiti bibliotecari scolastici dell'istituto Panettipitagora.

--Descrizione
BiblioTech è un'applicazione web che sostituisce il registro cartaceo dei prestiti bibliotecari con una soluzione digitale centralizzata. Il sistema permette di:

Registrazione utenti — Gli studenti si registrano autonomamente
Gestire il catalogo — Il bibliotecario aggiunge, modifica ed elimina libri
Prestiti self-service — Gli studenti prendono libri in prestito con un clic
Restituzioni tracciabili — Il bibliotecario registra le restituzioni
Monitoraggio completo — Dashboard con statistiche, storico prestiti e gestione utenti


Passo 1: Avvia Docker
docker-compose up --build -d
Questo comando:

Scarica le immagini Docker necessarie (prima volta)
Crea 3 container:

php-web (porta 8080) — Applicazione PHP + Apache
mysql-db — Database MySQL
phpmyadmin (porta 8081) — Interfaccia gestione DB

Apri http://localhost:8081
Seleziona database myapp_db dalla barra laterale
Carica sql/database.sql
Clicca "Esegui"


Utilizzo del Sistema:
Per gli Studenti

Registrazione:

Clicca su "Registrati qui" nella pagina di login
Compila il form con nome, cognome, username, email, password
L'email deve avere dominio @panettipitagora.edu.it
Dopo la registrazione, effettua il login


Sfoglia il Catalogo:

Visualizza tutti i libri disponibili
Vedi quante copie sono disponibili in tempo reale
Clicca su "Vedi Dettagli" per aprire la scheda libro


Prendi in Prestito:

Nella scheda libro, clicca "PRENDI IN PRESTITO"
Il libro viene assegnato automaticamente
Durata prestito: 14 giorni
Non puoi prendere più copie dello stesso libro contemporaneamente


I Miei Prestiti:

Vedi tutti i tuoi prestiti attivi
Controlla le date di scadenza
Badge colorati indicano lo stato (OK / In scadenza / Scaduto)



Per il Bibliotecario

Login:

Username: admin
Password: password123


Dashboard (Tab Prestiti Attivi):

Vedi tutti i libri attualmente fuori sede
Per ogni prestito: studente, libro, data prestito, scadenza
Badge evidenziano i prestiti scaduti
Pulsante "RESTITUISCI" per registrare la restituzione


Dashboard (Tab Storico Completo):

Tutti i prestiti della storia (attivi + restituiti)
Filtro per vedere chi ha preso cosa e quando


Dashboard (Tab Utenti):

Elenco di tutti gli studenti e bibliotecari registrati
Vedi quanti prestiti attivi ha ogni studente
Info su data di registrazione


Gestione Catalogo:

Clicca su "Catalogo" nella navbar
Aggiungi Libro:

Compila il form a sinistra
Titolo e Autore sono obbligatori
Specifica il numero di copie da aggiungere
Ogni copia ha un codice univoco generato automaticamente


Modifica Libro:

Clicca "Modifica" nella tabella
Aggiorna i dati desiderati
Salva modifiche


Elimina Libro:

Clicca "Elimina" (con conferma)
Non è possibile eliminare libri con prestiti attivi