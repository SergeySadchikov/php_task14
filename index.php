<?php 
session_start();
$username = 'sadchikov';
$password = 'neto1734';
$pdo = new PDO('mysql:host=localhost;dbname=sadchikov;charset=utf8', $username, $password);

if (!empty($_POST)) {
    $login = trim(htmlspecialchars(stripslashes($_POST['login'])));
    $password = password_hash(trim(htmlspecialchars(stripslashes($_POST['password']))), PASSWORD_DEFAULT);

    $sql = 'SELECT login, password FROM user WHERE login = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$login]);
    $user_data = $query->fetch();
}

if (isset($_POST['sign_in'])) {
    if ($user_data && password_verify($_POST['password'], $user_data['password'])) {
        $_SESSION['login'] = $login;
        header("Location: list.php");
    } else {
        echo 'Ошибка. Неверный логин или пароль.';
    }
}

if (isset($_POST['sign_up'])) {
    if ($user_data !== false) {
       echo 'Такой пользователь уже существует !';
    } else {
    $sql = 'INSERT INTO user (login, password) VALUES (?, ?)';
    $res = $pdo->prepare($sql);
    $res->execute([$login, $password]);
    $_SESSION['login'] = $login;
    header("Location: list.php");
    }
}
 ?>
 <!doctype html>
 <html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добро пожаловать!</title>
</head>
<body>
    <?php if (!isset($_SESSION['login'])) { ?>
<p>Войдите или зарегистрируйтесь:</p>
<form method="post" action="">
    <input name="login" placeholder="Логин">
    <input type="password" name="password" placeholder="Пароль">
    <input type="submit" name="sign_in" value="Вход">
    <input type="submit" name="sign_up" value="Регистрация">
</form>
<?php } else { ?>
    <p>Вы уже вошли на сайт</p>
    <a href="logout.php">Выйти</a>
<?php } ?>

</body>
</html>

