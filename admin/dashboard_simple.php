<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    die('Musíte být přihlášeni!');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>🎉 Funguje to! Jste přihlášeni jako: <?php echo $_SESSION['username']; ?></h1>
    <p>Role: <?php echo $_SESSION['user_role']; ?></p>
    <a href="../index.php">Zobrazit web</a>
</body>
</html>
```

