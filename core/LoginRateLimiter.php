<?php

class LoginRateLimiter 
{
    private PDO $db;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(Database $db, int $maxAttempts = 5, int $decayMinutes = 15) 
    {
        $this->db = $db->getPDO();;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Comprueba si la IP o el usuario están bloqueados.
     */
    public function isLockedOut(string $ip, string $username): bool 
    {
        $sql = "SELECT COUNT(*) FROM login_attempts 
                WHERE (ip_address = :ip OR username = :username)
                  AND attempted_at > (NOW() - INTERVAL :minutes MINUTE)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':minutes', $this->decayMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() >= $this->maxAttempts;
    }

    /**
     * Registra un fallo de autenticación.
     */
    public function recordFailure(string $ip, string $username): void 
    {
        $sql = "INSERT INTO login_attempts (ip_address, username, attempted_at) 
                VALUES (:ip, :username, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ip'       => $ip,
            ':username' => $username,
        ]);
        if (random_int(1, 100) === 1) {
            $this->purgeOldAttempts();
        }
    }

    /**
     * Limpia los intentos fallidos tras un login exitoso.
     */
    public function clearAttempts(string $ip, string $username): void 
    {
        $sql = "DELETE FROM login_attempts 
                WHERE ip_address = :ip OR username = :username";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ip'       => $ip,
            ':username' => $username,
        ]);
    }

    /**
     * Mantenimiento: elimina registros caducados para que la tabla no crezca indefinidamente.
     */
    public function purgeOldAttempts(): void 
    {
        $sql = "DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)";
        $this->db->query($sql);
    }
}