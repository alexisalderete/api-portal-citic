<?php
class Database {
    /*private $host = 'localhost'; //localhost 15.235.12.99
    private $db_name = 'usuarios'; //usuarios citicpycom_portal
    private $username = 'root'; //root citicpycom_alexis
    private $password = ''; //MbXKtNTasqw
    private $conn;*/


    private $host = '15.235.12.99'; //localhost 15.235.12.99
    private $db_name = 'citicpycom_portal'; //usuarios citicpycom_portal
    private $username = 'citicpycom_alexis'; //root citicpycom_alexis
    private $password = 'MbXKtNTasqw'; //MbXKtNTasqw
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Establecer collation UTF-8 explícitamente
            $this->conn->exec("SET NAMES 'utf8mb4'");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }
}