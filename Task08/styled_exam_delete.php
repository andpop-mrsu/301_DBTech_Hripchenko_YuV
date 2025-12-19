<?php
require __DIR__ . '/conn.php';
if (!isset($_GET['id'], $_GET['student_id'])) {
    die("Недостаточно данных.");
}
$student_id = intval($_GET['student_id']);
$id = intval($_GET['id']);

// Если подтверждение отправлено
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ? AND student_id = ?");
        $stmt->execute([$id, $student_id]);
    }
    // Возвращаемся к списку экзаменов студента
    header("Location: styled_results.php?student_id=$student_id");
    exit;
}

// Получаем данные экзамена для сообщения
$stmt = $pdo->prepare("SELECT disciplines.name, exams.exam_date 
                      FROM exams JOIN disciplines ON exams.discipline_id = disciplines.id 
                      WHERE exams.id = ? AND exams.student_id = ?");
$stmt->execute([$id, $student_id]);
$exam = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
<title>Удаление результата экзамена</title>
</head>
<body>
<section class="page">
  <div class="container">

<h2>Удаление результата экзамена</h2>

<?php if (!$exam): ?>
    <p>Данные экзамена не найдены.</p>
<?php else:
    $dname = htmlspecialchars($exam['name']);
    $date  = htmlspecialchars($exam['exam_date']);
    ?>
    <p>Вы уверены, что хотите удалить результат экзамена по предмету <b><?php echo $dname; ?></b> (дата <?php echo $date; ?>)?</p>
    <form method="post">
        <button type="submit" name="confirm" value="yes">Да, удалить</button>
        <button type="submit" name="confirm" value="no">Нет, отмена</button>
    </form>
<?php endif; ?>

  </div>
</section>
</body>
</html>
