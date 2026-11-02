<?php

class User {
    private int $id;
    private string $username;
    private string $password;
    private DateTimeImmutable $birthDate;
    public function __construct(int $id, string $username, string $password, DateTimeImmutable $birthDate) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->birthDate = $birthDate;
    }
    public function getId(): int {
        return $this->id;
    }
    public function getUsername(): string {
        return $this->username;
    }
    public function getBirthDate(): DateTimeImmutable {
        return $this->birthDate;
    }
    public function getPassword(): string {
        return $this->password;
    }
}