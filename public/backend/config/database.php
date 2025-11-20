<?php

class Database {
    private $host = 'MySQL-8.2'; // меняем хост на ваше имя хоста базы данных
    private $db_name = 'hackaton_db'; // имя базы данных не забываем так же изменить
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // В итоговом приложении ошибки на экран не выводим
            // echo 'Connection error: ' . $e->getMessage();
        }
        return $this->conn;
    }
}
?>