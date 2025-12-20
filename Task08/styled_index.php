<?php require 'conn.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
<title>Список студентов</title>
</head>
<body>
<section class="page">
  <div class="container">

<h1>Список студентов</h1>

<!-- Форма фильтрации по группе -->
<form method="get" action="styled_index.php">
    Фильтр по группе:
    <select name="group_id">
        <option value="">Все группы</option>
        <?php require 'conn.php';
        // Получаем список всех групп для выпадающего списка
        $stmt = $pdo->query("SELECT id, group_code FROM groups");
        foreach ($stmt as $grp) {
            // Если сейчас выбран фильтр по группе, отмечаем соответствующий option
            $selected = (isset($_GET['group_id']) && $_GET['group_id'] == $grp['id']) ? 'selected' : '';
            echo "<option value=\"{$grp['id']}\" $selected>{$grp['group_code']}</option>";
        }
        ?>
    </select>
    <button type="submit">Применить</button>
</form>

<!-- Таблица со списком студентов -->
<table border="1" cellpadding="5">
    <tr>
        <th>Группа</th><th>Фамилия</th><th>Имя</th><th>Пол</th><th>Действия</th>
    </tr>
    <?php
    // Формируем SQL-запрос для выборки студентов (с учётом фильтра, если применён)
    $query = "SELECT students.*, groups.group_code 
          FROM students 
          JOIN groups ON students.group_id = groups.id";
    if (isset($_GET['group_id']) && $_GET['group_id'] !== '') {
        $gid = intval($_GET['group_id']);
        $query .= " WHERE students.group_id = $gid";
    }
    $query .= " ORDER BY groups.group_code, students.last_name";
    $stmt = $pdo->query($query);
    // Выводим каждую запись студента в строку таблицы
    foreach ($stmt as $st) {
        $id     = $st['id'];
        $group  = htmlspecialchars($st['group_code']);
        $last   = htmlspecialchars($st['last_name']);
        $first  = htmlspecialchars($st['first_name']);
        $gender = htmlspecialchars($st['gender']);
        echo "<tr>";
        echo "<td>$group</td><td>$last</td><td>$first</td><td>$gender</td>";
        echo "<td>
            <a href='styled_student_form.php?id=$id'>Редактировать</a> |
            <a href='styled_student_delete.php?id=$id'>Удалить</a> |
            <a href='styled_results.php?student_id=$id'>Результаты экзаменов</a>
          </td>";
        echo "</tr>";
    }
    ?>
</table>

<p><a href="styled_student_form.php">Добавить студента</a></p>
  </div>
</section>
</body>
</html>
