<?php
require 'conn.php';
if (!isset($_GET['id'])) {
    die("Не указан ID студента.");
}
$id = intval($_GET['id']);

// Обработка подтверждения (POST-запроса)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        // Выполняем удаление студента по ID
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
    }
    // В любом случае возвращаемся на главную страницу
    header("Location: styled_index.php");
    exit;
}

// Если GET-запрос: показываем форму подтверждения
$stmt = $pdo->prepare("SELECT students.last_name, students.first_name, groups.group_code 
                      FROM students JOIN groups ON students.group_id = groups.id 
                      WHERE students.id = ?");
$stmt->execute([$id]);
$stud = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
<title>Удаление студента</title>
</head>
<body>
<section class="page">
  <div class="container">

<h2>Удаление студента</h2>

<?php if (!$stud): ?>
    <p>Студент не найден!</p>
<?php else:
    // Отображаем вопрос с именем студента и группой
    $name = htmlspecialchars($stud['first_name']." ".$stud['last_name']);
    $group = htmlspecialchars($stud['group_code']);
    ?>
    <p>Вы уверены, что хотите удалить студента <b><?php echo $name; ?></b> (группа <?php echo $group; ?>)?</p>
    <form method="post">
        <button type="submit" name="confirm" value="yes">Да, удалить</button>
        <button type="submit" name="confirm" value="no">Нет, отмена</button>
    </form>
<?php endif; ?>

  </div>
</section>
</body>
</html>
