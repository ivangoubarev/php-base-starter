<?php

declare(strict_types=1);

class Logger
{
    private string $requestId;

    public function __construct(
        private LogDatabase $db
    ) {
        // Generar un ID único por petición HTTP (UUID v4 o bin2hex)
        $this->requestId = $this->generateRequestId();
    }

    /**
     * Registra un mensaje de severidad INFO.
     */
    public function info(string $message, ?string $username = null): void
    {
        $this->log('INFO', $message, $username);
    }

    /**
     * Registra un mensaje de severidad WARNING.
     */
    public function warning(string $message, ?string $username = null): void
    {
        $this->log('WARNING', $message, $username);
    }

    /**
     * Registra un mensaje de severidad ERROR.
     */
    public function error(string $message, ?string $username = null): void
    {
        $this->log('ERROR', $message, $username);
    }

    /**
     * Registra el evento en la base de datos de auditoría.
     */
    public function log(string $severity, string $message, ?string $username = null): void
    {
        try {
            // Resolver el usuario: prioridad al parámetro explícito, fallback a $_SESSION si existe
            $resolvedUser = $username ?? $this->resolveSessionUser();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) 
                ? mb_substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 500) 
                : null;

            $sql = "INSERT INTO logs (request_id, ip_address, username, severity, message, user_agent)
                    VALUES (:request_id, :ip_address, :username, :severity, :message, :user_agent)";

            $params = [
                ':request_id' => $this->requestId,
                ':ip_address' => $ip,
                ':username'   => $resolvedUser,
                ':severity'   => strtoupper($severity),
                ':message'    => $message,
                ':user_agent' => $userAgent,
            ];

            // Delegar la ejecución en el método query() de LogDatabase
            $this->db->query($sql, $params);

        } catch (\Throwable $e) {
            // El registro de logs jamás debe quebrar la ejecución de la aplicación web principal.
            // Se envía el error al registro de contingencia del servidor web / syslog.
            error_log('[Logger Error] No se pudo guardar el log de auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el identificador de la petición actual (útil para devolverlo en cabeceras HTTP o vistas de error).
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Resuelve el nombre de usuario de la sesión activa si está inicializada.
     */
    private function resolveSessionUser(): ?string
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            return is_string($_SESSION['user']) ? $_SESSION['user'] : null;
        }

        return null;
    }

    /**
     * Genera un identificador único seguro para rastrear la petición.
     */
    private function generateRequestId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // Versión 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // Variante RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}