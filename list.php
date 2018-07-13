<?php
error_reporting(E_ALL);
session_start();
if (isset($_SESSION['login'])) {
    $login = $_SESSION['login'];
} else {
    http_response_code(404);
    echo ' ERROR: PAGE NOT FOUND!';
exit(1);
}
$button = 'Добавить';
$errors = "";
$username = 'sadchikov';
$password = 'neto1734';
$db = new PDO('mysql:host=localhost;dbname=sadchikov;charset=utf8', $username, $password);

//add
if (isset($_POST['save'])) {
        $desc = $_POST['description'];
        $id = $_POST['id'];
        if ($id) {
            $editPrep = $db->prepare("UPDATE task SET description = ? WHERE id = ? LIMIT 1");
            $editPrep->execute([$desc, $id]);
            header('Location:list.php');
        } else {
            $currentUser = $db->prepare("SELECT id, login FROM user WHERE login = ?");
            $currentUser->execute([$login]);
            $user = $currentUser->fetch();
            $addPrep = $db->prepare("INSERT INTO task (description, is_done, date_added, user_id, assigned_user_id) VALUES (?, ?, CURRENT_TIMESTAMP, ?, ?)");
            $addPrep->execute([$desc, false, $user['id'], $user['id']]);
            header('Location:list.php');
        }
    }   

 //done
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'done') {
        $sql_query = 'UPDATE task SET is_done = 1 WHERE id = ?';
        $rows = $db->prepare($sql_query);
        $rows->execute([$_GET['id']]);
        header('Location:list.php');
    }
//edit
    if ($_GET['action'] === 'edit') {
        $sql_query = 'SELECT * FROM task WHERE id = ?';
        $rows = $db->prepare($sql_query);
        $rows->execute([$_GET['id']]);
        $description = $rows->fetch()['description'];
        $button = 'Сохранить';
    }
//del
    if ($_GET['action'] === 'delete') {
        $sql_query = "DELETE FROM task WHERE id = ?";
        $rows = $db->prepare($sql_query);
        $rows->execute([$_GET['id']]);
        header('Location:list.php');
    }
}
//список пользователей
$users = $db->query('SELECT login FROM user');
$users = $users->fetchAll(PDO::FETCH_ASSOC);

//переложить ответственность
if (isset($_POST['assign'])) {
        $assign_to = $db->quote($_POST['assign_to']);
        $taskId = $_POST['id'];
        $assign_user_id = $db->query("SELECT id FROM user WHERE login = $assign_to")->fetch()['id'];
        $assignPrep = $db->prepare("UPDATE task SET assigned_user_id = ? WHERE id = ? LIMIT 1");
        $assignPrep->execute([$assign_user_id, $taskId]);
    }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>to-do-list</title>
</head>
<body>

<style>
    table { 
        border-spacing: 0;
        border-collapse: collapse;
    }
    table td, table th {
        border: 1px solid #ccc;
        padding: 5px;
    }
    
    table th {
        background: #eee;
    }
</style>
<h1>Добро пожаловать <?php echo "$login"; ?></h1>
<h2>Список дел на сегодня</h2>
<div style="float: left">
    <form method="POST">
        <?php if (isset($errors)) { ?>
        <p><?php echo $errors; ?></p>
        <?php } ?>
        <input type="hidden" name="id" value="<?php if (isset($_GET['id'])){echo($_GET['id']);}?>">
        <input type="text" name="description" required placeholder="Описание задачи" value="<?php if (isset($description)) {echo($description);} ?>"/>
        <input type="submit" name="save" value="<?php echo($button); ?>" />
    </form>
</div>
<!-- <div style="float: left; margin-left: 20px;">
    <form method="POST">
        <label for="sort">Сортировать по:</label>
        <select name="sort_by">
            <option value="date_created">Дате добавления</option>
            <option value="is_done">Статусу</option>
            <option value="description">Описанию</option>
        </select>
        <input type="submit" name="sort" value="Отсортировать" />
    </form>
</div> -->
<div style="clear: both"></div>

<table>
    <tr>
        <th>Описание задачи</th>
        <th>Дата добавления</th>
        <th>Статус</th>
        <th>Действие</th>
        <th>Ответственный</th>
        <th>Автор</th>
        <th>Закрепить задачу за другим пользователем</th>
    </tr>
    <?php
    $select = "SELECT t.id as task_id, t.description as description, u.id as author_id, u.login as author_name, au.id as assigned_user_id, au.login as assigned_user_name, t.is_done as is_done, t.date_added as date_added FROM task t INNER JOIN user u ON u.id=t.user_id INNER JOIN user au ON t.assigned_user_id=au.id WHERE u.login = ?";
    $result = $db->prepare($select);
    $result->execute([$login]);
    while ($row = $result->fetch()) {
     ?>
    <tr>
        <td><?php echo $row['description']; ?></td>
        <td><?php echo $row['date_added']; ?></td>
        <td>
            <?php  
            if ($row['is_done'] == 1) {
                echo '<span style="color: darkgreen">Выполнено</span>';
            } elseif ($row['is_done'] == 0) {
                echo '<span style="color: darkorange">В процессе</span>';
            }
            ?>
                 
        </td>
        <td>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=edit'>Изменить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=done'>Выполнить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=delete'>Удалить</a>
        </td>
        <td><?php echo $row['assigned_user_name']; ?></td>
        <td><?php echo $row['author_name']; ?></td>
        <td><form method="post" action="">
                <input type="hidden" name="id" value="<?php echo($row['task_id']); ?>">
                <select name="assign_to">
                    <?php 
                        foreach ($users as $user) {
                            if ($user['login'] !== $login)
                            {
                                echo '<option>'.$user['login'].'</option>';
                            }
                        }
                     ?>
                </select>
                <input type="submit" value="Переложить ответственность" name="assign">
            </form>
        </td>
    </tr>
    <?php  } ?>
</table>

<h2>Также, посмотрите, что от Вас требуют другие люди:</h2>
    <table>
    <tr>
        <th>Описание задачи</th>
        <th>Дата добавления</th>
        <th>Статус</th>
        <th>Действия</th>
        <th>Ответственный</th>
        <th>Автор</th>
    </tr>

<?php
$select = "SELECT t.id as task_id, t.description as description, u.id as author_id, u.login as author_name, au.id as assigned_user_id, au.login as assigned_user_name, t.is_done as is_done, t.date_added as date_added FROM task t INNER JOIN user u ON u.id=t.user_id INNER JOIN user au ON t.assigned_user_id=au.id WHERE au.login = ? AND u.login <> ?";
$result = $db->prepare($select);
$result->execute([$login, $login]);
while ($row = $result->fetch()) {
?>
<tr>
    <td><?php echo $row['description'];?></td>
    <td><?php echo $row['date_added']; ?></td>
    <td>
            <?php  
            if ($row['is_done'] == 1) {
                echo '<span style="color: darkgreen">Выполнено</span>';
            } elseif ($row['is_done'] == 0) {
                echo '<span style="color: darkorange">В процессе</span>';
            }
            ?>
                 
        </td>
        <td>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=edit'>Изменить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=done'>Выполнить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=delete'>Удалить</a>
        </td>
        <td><?php echo $row['assigned_user_name']; ?></td>
        <td><?php echo $row['author_name']; ?></td>
</tr>
<?php } ?>     
</table>

<a href="logout.php">Выйти</a>
</body>
</html>