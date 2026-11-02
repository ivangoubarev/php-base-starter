<?php

declare(strict_types=1);

class Database 
{
    protected ?PDO $conn = null;

    public function __construct(
        private string $host,
        private string $dbname,
        private string $username,
        private string $password,
        private string $charset = 'utf8mb4'
    ) {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Sentencias preparadas nativas del motor
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Registrar el detalle real en el log del servidor web de forma privada
            error_log('[DB Connection Error] ' . $e->getMessage());

            // Mensaje opaco al cliente para no revelar arquitectura interna
            http_response_code(500);
            exit('Error de conexión con el servicio de base de datos.');
        }
    }

    public function query(string $sql, array $params = []): PDOStatement 
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function getPDO(): PDO 
    {
        return $this->conn;
    }
}