<?php
// models/Patient.php
// Модель пациента: CRUD операции, ограниченные терапевтом.

class Patient {
    private $conn;

    public $id;
    public $therapist_id;
    public $name;
    public $age;
    public $gender;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Возвращает PDOStatement со списком пациентов терапевта
    public function getAll() {
        $sql = "SELECT patients.*, COUNT(sessions.id) AS sessions_count
            FROM patients
            LEFT JOIN sessions ON patients.id = sessions.patient_id
            WHERE patients.therapist_id = :therapist_id
            GROUP BY patients.id
            ORDER BY patients.name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':therapist_id', $this->therapist_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Возвращает массив данных одного пациента или false
    public function getOne() {
        $sql = "SELECT patients.*, COUNT(sessions.id) AS sessions_count
            FROM patients
            LEFT JOIN sessions ON patients.id = sessions.patient_id
            WHERE patients.id = :id AND patients.therapist_id = :therapist_id
            GROUP BY patients.id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':therapist_id', $this->therapist_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->name = $row['name'];
            $this->age = $row['age'];
            $this->gender = $row['gender'];
            return $row;
        }
        return false;
    }

    // Создает пациента и сохраняет id
    public function create() {
        $sql = "INSERT INTO patients (therapist_id, name, age, gender)
            VALUES (:therapist_id, :name, :age, :gender)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':therapist_id', $this->therapist_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':age', $this->age, PDO::PARAM_INT);
        $stmt->bindParam(':gender', $this->gender);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Обновляет пациента (только если принадлежит терапевту)
    public function update() {
        $sql = "UPDATE patients SET name = :name, age = :age, gender = :gender
            WHERE id = :id AND therapist_id = :therapist_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':age', $this->age, PDO::PARAM_INT);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':therapist_id', $this->therapist_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Удаляет пациента (только если принадлежит терапевту)
    public function delete() {
        $sql = "DELETE FROM patients WHERE id = :id AND therapist_id = :therapist_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':therapist_id', $this->therapist_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>