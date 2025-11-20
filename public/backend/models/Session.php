<?php

class Session {
    private $conn;

    // Публичные свойства используются как входные параметры для запросов
    public $id;
    public $patient_id;
    public $sud_score;
    public $quality_score;
    public $comments;
    public $session_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Возвращает PDOStatement с сессиями для одного пациента
    public function getByPatient() {
        $sql = "SELECT sessions.* FROM sessions
            WHERE sessions.patient_id = :patient_id
            ORDER BY sessions.session_date DESC, sessions.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':patient_id', $this->patient_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Создает новую сессию
    public function create() {
        $sql = "INSERT INTO sessions
            (patient_id, sud_score, quality_score, comments, session_date)
            VALUES (:patient_id, :sud_score, :quality_score, :comments, :session_date)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':patient_id', $this->patient_id, PDO::PARAM_INT);
        $stmt->bindParam(':sud_score', $this->sud_score);
        $stmt->bindParam(':quality_score', $this->quality_score);
        $stmt->bindParam(':comments', $this->comments);
        $stmt->bindParam(':session_date', $this->session_date);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Обновляет существующую сессию по id
    public function update() {
        $sql = "UPDATE sessions SET
                    sud_score = :sud_score,
                    quality_score = :quality_score,
                    comments = :comments,
                    session_date = :session_date
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sud_score', $this->sud_score);
        $stmt->bindParam(':quality_score', $this->quality_score);
        $stmt->bindParam(':comments', $this->comments);
        $stmt->bindParam(':session_date', $this->session_date);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Удаляет сессию по id
    public function delete() {
        $sql = "DELETE FROM sessions WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
