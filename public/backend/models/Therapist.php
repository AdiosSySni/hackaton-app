<?php
// models/Therapist.php
// Модель терапевта: регистрация и логин.
// Register теперь проверяет, существует ли email, и использует хеш пароля.

class Therapist {
    private $conn;

    public $id;
    public $email;
    public $password;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Проверяет учетные данные терапевта
    public function login() {
        $sql = "SELECT id, email, password, name
            FROM therapists
            WHERE email = :email
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($this->password, $row['password'])) {
            // Сохраняем данные в объекте для удобства
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->name = $row['name'];
            return true;
        }
        return false;
    }

    // Регистрирует нового терапевта, возвращает false при дубликате email
    public function register() {
        // Проверим, не занят ли email
        $checkSql = "SELECT id FROM therapists WHERE email = :email LIMIT 1";
        $check = $this->conn->prepare($checkSql);
        $check->bindParam(':email', $this->email);
        $check->execute();
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            return false; // email уже используется
        }

        $hashed = password_hash($this->password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO therapists (email, password, name)
            VALUES (:email, :password, :name)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':name', $this->name);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>