<html>
<body>

<h1>Bienvenido, <?= htmlspecialchars($user->getUsername())?></h1>
<p>Fecha de nacimiento: <?= htmlspecialchars($user->getBirthDate()->format('D d/m/Y'));?></p>

</body>
</html>