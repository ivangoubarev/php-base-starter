<?php
require_once '../entities/User.php';

class UserRepository {
    private Database $db;
    public function __construct(Database $db) {
        $this->db = $db;
    }
    public function getByUsername(string $username): ?User {
        $sql = "SELECT * FROM Users WHERE username = ?";
        $stmt = $this->db->query($sql, [$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new User($row["id"], $row["username"], $row["password"], new DateTimeImmutable($row["birth_date"])) : null;
    }
}