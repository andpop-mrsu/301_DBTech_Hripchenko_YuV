<?php
require 'conn.php';
if (!isset($_GET['student_id'])) {
    die("Не указан студент.");
}
$student_id = intval($_GET['student_id']);

// Получаем информацию о студенте (имя, группа) для заголовка
$stmt = $pdo->prepare("SELECT students.*, groups.group_code 
                      FROM students JOIN groups ON students.group_id = groups.id 
                      WHERE students.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
if (!$student) {
    die("Студент не найден");
}
$studentName = htmlspecialchars($student['first_name']." ".$student['last_name']);
$groupName   = htmlspecialchars($student['group_code']);

// Выбираем все экзамены данного студента
$stmt = $pdo->prepare("SELECT exams.*, disciplines.name AS discipline_name 
                      FROM exams 
                      JOIN disciplines ON exams.discipline_id = disciplines.id 
                      WHERE exams.student_id = ? 
                      ORDER BY exams.exam_date");
$stmt->execute([$student_id]);
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
<title>Результаты экзаменов – <?php echo $studentName; ?></title>
</head>
<body>
<section class="page">
  <div class="container">

<h2>Результаты экзаменов студента <?php echo $studentName; ?> (группа <?php echo $groupName; ?>)</h2>

<table border="1" cellpadding="5">
    <tr>
        <th>Дисциплина</th><th>Дата</th><th>Оценка</th><th>Действия</th>
    </tr>
    <?php foreach ($exams as $ex):
        $eid   = $ex['id'];
        $dname = htmlspecialchars($ex['discipline_name']);
        $date  = htmlspecialchars($ex['exam_date']);
        $grade = htmlspecialchars($ex['grade']);
        ?>
        <tr>
            <td><?php echo $dname; ?></td>
            <td><?php echo $date; ?></td>
            <td><?php echo $grade; ?></td>
            <td>
                <a href="styled_exam_form.php?student_id=<?php echo $student_id; ?>&id=<?php echo $eid; ?>">Редактировать</a> |
                <a href="styled_exam_delete.php?student_id=<?php echo $student_id; ?>&id=<?php echo $eid; ?>">Удалить</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<p><a href="styled_exam_form.php?student_id=<?php echo $student_id; ?>">Добавить результат экзамена</a></p>
<p><a href="styled_index.php">Вернуться к списку студентов</a></p>
  </div>
</section>
</body>
</html>
