<?php

declare(strict_types=1);

/**
 * Carga pares clave=valor desde un archivo .env en $_ENV y getenv()
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar comentarios o líneas en blanco
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Separar solo en el primer '='
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Eliminar comillas envolventes si las tiene ("secreto" o 'secreto')
            $value = trim($value, "\"'");

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// 1. Cargar el archivo .env ubicado en la raíz del proyecto
loadEnv(__DIR__ . '/../.env');

// 2. Configuración y arranque seguro de sesión (si no está ya activa)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}