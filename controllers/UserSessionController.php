<?php 

class UserSessionController {
    private UserRepository $userRepository;
    private LoginRateLimiter $loginRateLimiter;
    private Logger $logger;
    public function __construct(UserRepository $userRepository, LoginRateLimiter $loginRateLimiter, Logger $logger) {
        $this->userRepository = $userRepository;
        $this->loginRateLimiter = $loginRateLimiter;
        $this->logger = $logger;
    }
    public function getLogin(){
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        require '../views/loginView.php';
    }

    public function postLogin(): void 
    {
        // 1. Validar token CSRF
        $token = (string) ($_POST['csrf_token'] ?? '');
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        if ($token === '' || !hash_equals($sessionToken, $token)) {
            $this->logger->warning("Intento de POST a /login con token CSRF no válido.");
            http_response_code(403);
            exit('Token CSRF no válido.');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $rateLimiter = $this->loginRateLimiter;

        // 2. Comprobar si está bloqueado por demasiados intentos
        if ($rateLimiter->isLockedOut($ip, $username)) {
            $_SESSION['flash_error'] = 'Demasiados intentos fallidos. Inténtalo de nuevo en 15 minutos.';
            $_SESSION['old_username'] = $username;
            header('Location: /login');
            exit;
        }

        // 3. Mitigar DoS por complejidad de algoritmo (bcrypt limit)
        if (strlen($password) > 255) {
            $this->logger->warning('Intento de inicio de sesión con contraseña con longitud superior a 255 caracteres.');
            $rateLimiter->recordFailure($ip, $username);
            $_SESSION['flash_error'] = 'Credenciales no válidas.';
            header('Location: /login');
            exit;
        }

        // 4. Intentar autenticación (con hash simulado contra timing attacks)
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012345';
        $user = $this->userRepository->getByUsername($username);
        $hash = $user ? $user->getPassword() : $dummyHash;

        if ($user && password_verify($password, $hash)) {
            // ÉXITO: Limpiar historial de bloqueos y regenerar sesión
            $rateLimiter->clearAttempts($ip, $username);            

            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['user'] = $user->getUsername();

            $this->logger->info('Inicio de sesión.');

            header('Location: /');
            exit;
        }

        // 5. FALLO: Registrar intento fallido
        $rateLimiter->recordFailure($ip, $username);
        $this->logger->info('Fallo en el inicio de sesión.');

        $_SESSION['flash_error'] = 'Wrong username or password';
        $_SESSION['old_username'] = $username;
        header('Location: /login');
        exit;
    }

}