<html>
<body>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $error = $_SESSION['flash_error'] ?? null;
    $oldUsername = $_SESSION['old_username'] ?? '';

    // Consumir los datos flash para que desaparezcan al recargar
    unset($_SESSION['flash_error'], $_SESSION['old_username']);
    ?>

    <form action="login" method="post">
        <?php if ($error): ?>
            <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? '' ?>">
        Username: <input type="text" name="username" value="<?= htmlspecialchars($oldUsername, ENT_QUOTES, 'UTF-8') ?>" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>

</body>
</html>