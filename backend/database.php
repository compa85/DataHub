<?php
/*
// =================================================================================================
DATABASE.PHP

Questo file contiene alcune funzioni che permettono di effettuare le principali operazioni per la gestione di un database (insert, update, delete).
La maggior parte delle seguenti funzioni richiedono come parametro un oggetto, le cui proprietà corrispondono al nome, ai campi e ai valori delle tabelle del database.

Per semplificare la creazione di questi oggetti, si consiglia di effettuare una conversione di file JSON, nel seguente modo:

$json = file_get_contents("example.json");
$object = json_decode($json);


Il seguente è un esempio del contenuto del file JSON:

{
    "customers": [
        {
            "name": "Mario",
            "surname": "Rossi",
            "email": "mario@example.com"
        },
        {
            "name": "Giovanni",
            "surname": "Verdi",
            "email": "giovanni@example.com"
        }
    ]
}

"customers" è il nome della tabella, mentre "name", "surname" e "email" sono i suoi campi.

// =================================================================================================
*/

class Database {
    // ========================================= VARIABILI =========================================

    public $conn;


    // ======================================== COSTRUTTORE ========================================

    public function __construct($host, $username, $password, $database) {
        try {
            $this->conn = new mysqli($host, $username, $password, $database);
        } catch (Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }


    // ======================================== INSERIMENTO ========================================

    public function insert($object) {
        // array delle query da eseguire (le query vengono eseguite solo alla fine, se non sono stati rilevati errori durante l'esecuzione)
        $queries = array();
        // messaggio di risposta
        $message = "";

        // scorro l'oggetto $object che contiente le tabelle del db
        foreach ($object as $table_name => $table) {
            // controllo che la tabella non sia vuota
            if (!empty($table)) {
                $query = "INSERT INTO $table_name ";
                $fields = null;
                $values = array();

                // scorro il vettore $table che rappresenta la tabella del db
                foreach ($table as $record) {
                    // array che contiene i nomi dei campi
                    $local_fields = array();
                    // array che contiene i valori dei campi
                    $local_values = array();

                    // scorro il vettore $record che rappresenta la riga del db
                    foreach ($record as $field => $value) {
                        // controllo che non ci siano spazi nei campi, per evitare errori durante l'esecuzione della query
                        if (str_contains($field, " ")) {
                            $message = "Error: '$field: $value' contains a space character";
                            return $message;
                        }

                        // aggiungo i nomi e i valori dei campi nei rispettivi array
                        array_push($local_fields, $field);
                        array_push($local_values, $value);
                    }

                    if ($fields == null) {
                        $fields = $local_fields;
                        // accodo alla query i nomi dei campi
                        $query .= "(" . implode(", ", $local_fields) . ") VALUES ";
                    } else {
                        // confronto gli array contenenti i nomi dei campi
                        $diff = array_diff($fields, $local_fields);
                        // controllo se i campi sono diversi o se non sono lo stesso numero
                        if (!empty($diff) || count($fields) != count($local_fields)) {
                            $message = "Error: Different fields in '$table_name'";
                            return $message;
                        }
                    }

                    // aggiungo la stringa dei valori all'array $values
                    $string = "('" . implode("', '", $local_values) . "')";
                    array_push($values, $string);
                }

                // accodo alla query le stringhe dei valori presenti in $values, separandoli con una virgola
                $query .= implode(", ", $values);
                // aggiungo la query all'array $queries
                array_push($queries, $query);
            } else {
                $message = "Error: Table '$table_name' is empty";
                return $message;
            }
        }

        // variabile per il conteggio dei record inseriti
        $inserted = 0;

        //eseguo tutte le query se non si sono verificati errori
        foreach ($queries as $query) {
            try {
                $this->conn->query($query);
                // incremento il numero di righe inserite
                $inserted += $this->conn->affected_rows;
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                return $message;
            }
        }

        return "$inserted records inserted";
    }


    // ========================================= RIMOZIONE =========================================

    public function delete($object) {
        // messaggio di risposta
        $message = 0;

        // scorro l'oggetto $object che contiente le tabelle del db
        foreach ($object as $name => $table) {
            // controllo che la tabella non sia vuota
            if (!empty($table)) {
                // scorro il vettore $table che rappresenta la tabella del db
                foreach ($table as $record) {
                    $query = "DELETE FROM $name WHERE ";

                    // assegno a $fields un array associativo che contiene i nomi e i valori dei campi
                    $fields = get_object_vars($record);
                    $conditions = array();

                    // scorro il vettore $fields che rappresenta la riga del db
                    foreach ($fields as $field => $value) {
                        // controllo che non ci siano spazi nei campi
                        if (str_contains($field, " ")) {
                            $message = "Error: '$field: $value' contains a space character";
                            return $message;
                        }

                        // aggiungo la stringa delle condizioni nell'array $conditions
                        $string = "$field = '$value'";
                        array_push($conditions, $string);
                    }

                    // accodo alla query le stringhe delle condizioni
                    $query .= implode(" AND ", $conditions);

                    // eseguo la query
                    try {
                        $this->conn->query($query);
                        // ottengo il numero di righe cancellate
                        $deleted = $this->conn->affected_rows;
                        $message += $deleted;
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        return $message;
                    }
                }
            } else {
                // elimino tutti i campi della tabella
                $query = "DELETE FROM $name";

                // eseguo la query
                try {
                    $this->conn->query($query);
                    $message = "All records of '$name' deleted";
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                }
                return $message;
            }
        }

        return "$message records deleted";
    }


    // ======================================= AGGIORNAMENTO =======================================

    public function update($object) {
        // messaggio di risposta
        $message = 0;

        // scorro l'oggetto $object che contiente le tabelle del db
        foreach ($object as $name => $table) {
            // scorro il vettore $table che rappresenta la tabella del db
            foreach ($table as $record) {
                $query = "UPDATE $name SET ";

                // se contiene due oggetti e quindi solo il soggetto e la modifica
                if (count($record) == 2) {
                    // assegno a $old e $new array associativi che contengono i nomi e i valori dei campi vecchi e nuovi
                    $old = get_object_vars($record[0]);
                    $new = get_object_vars($record[1]);
                    $conditions = array();

                    // scorro il vettore $new che rappresenta la riga del db
                    foreach ($new as $field => $value) {
                        // controllo che non ci siano spazi nei campi e nei valori
                        if (str_contains($field, " ")) {
                            $message = "Error: '$field: $value' contains a space character";
                            return $message;
                        }

                        // aggiungo la stringa delle condizioni di aggiornamento nell'array $conditions
                        $string = "$field = '$value'";
                        array_push($conditions, $string);
                    }

                    // accodo alla query le stringhe dei dati da aggiornare e ricreo array condizioni per cancellare dati inseriti precedentemente
                    $query .= implode(", ", $conditions);
                    $query .= " WHERE ";
                    $conditions = array();

                    // scorro il vettore $old che rappresenta la riga del db
                    foreach ($old as $field => $value) {
                        // controllo che non ci siano spazi nei campi e nei valori
                        if (str_contains($field, " ")) {
                            $message = "Error: '$field: $value' contains a space character";
                            return $message;
                        }

                        // aggiungo la stringa delle condizioni nell'array $conditions
                        $string = "$field = '$value'";
                        array_push($conditions, $string);
                    }

                    // accodo alla query le stringhe delle condizioni
                    $query .= implode(" AND ", $conditions);

                    // eseguo la query
                    try {
                        $this->conn->query($query);
                        // ottengo il numero di righe cancellate
                        $updated = $this->conn->affected_rows;
                        $message += $updated;
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        return $message;
                    }
                }
            }
        }

        return "$message records updated";
    }


    // ========================================= SELEZIONE =========================================

    public function select($object) {
        // messaggio di risposta
        $message = 0;

        // scorro l'oggetto $object che contiente le tabelle del db
        foreach ($object as $name => $table) {
            if (!empty($table)) {
                // scorro il vettore $table che rappresenta la tabella del db
                foreach ($table as $record) {
                    $query = "SELECT * FROM $name WHERE ";

                    // assegno a $fields un array associativo che contiene i nomi e i valori dei campi
                    $fields = get_object_vars($record);
                    $conditions = array();

                    // scorro il vettore $fields che rappresenta la riga del db
                    foreach ($fields as $field => $value) {
                        // controllo che non ci siano spazi nei campi e nei valori
                        if (str_contains($field, " ")) {
                            $message = "Error: '$field: $value' contains a space character";
                            return $message;
                        }

                        // aggiungo la stringa delle condizioni nell'array $conditions
                        $string = "$field = '$value'";
                        array_push($conditions, $string);
                    }

                    // accodo alla query le stringhe delle condizioni
                    $query .= implode(" AND ", $conditions);

                    // eseguo la query
                    try {
                        $this->conn->query($query);
                        // ottengo il numero di righe cancellate
                        $deleted = $this->conn->affected_rows;
                        $message += $deleted;
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        return $message;
                    }
                }
            } else {
                // seelziono tutti i campi della tabella
                $query = "SELECT * FROM $name";

                // eseguo la query
                try {
                    $this->conn->query($query);
                    $message = "All records of '$name' selected";
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                }
                return $message;
            }
        }

        return "$message records selected";
    }
}
