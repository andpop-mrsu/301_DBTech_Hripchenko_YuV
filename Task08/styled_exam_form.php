<?php
require 'conn.php';
if (!isset($_GET['student_id'])) {
    die("Не указан студент.");
}
$student_id = intval($_GET['student_id']);

// Получаем информацию о студенте (для отображения и фильтрации дисциплин)
$stmt = $pdo->prepare("SELECT students.*, groups.group_code, groups.program, groups.admission_year 
                      FROM students JOIN groups ON students.group_id = groups.id 
                      WHERE students.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
if (!$student) {
    die("Студент не найден");
}
$studentName = htmlspecialchars($student['first_name']." ".$student['last_name']);
$groupName   = htmlspecialchars($student['group_code']);
$program     = $student['program'];

$editing = false;
$exam = ['id' => null, 'discipline_id' => '', 'exam_date' => '', 'grade' => '', 'course_year' => ''];

// Если редактирование (есть параметр id результата)
if (isset($_GET['id'])) {
    $editing = true;
    $eid = intval($_GET['id']);
    // Выбираем запись экзамена этого студента
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND student_id = ?");
    $stmt->execute([$eid, $student_id]);
    $exam = $stmt->fetch();
    if (!$exam) {
        die("Данные экзамена не найдены.");
    }
    // Определяем курс по дисциплине этого экзамена
    $discData = $pdo->prepare("SELECT course_year FROM disciplines WHERE id = ?");
    $discData->execute([$exam['discipline_id']]);
    $discRow = $discData->fetch();
    $exam['course_year'] = $discRow ? $discRow['course_year'] : '';
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid          = $_POST['id'] ?? '';
    $discipline_id = $_POST['discipline_id'];
    $exam_date    = $_POST['exam_date'];
    $grade        = $_POST['grade'];
    if ($eid) {
        // Обновление существующего результата
        $sql = "UPDATE exams 
                SET discipline_id = ?, exam_date = ?, grade = ? 
                WHERE id = ? AND student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$discipline_id, $exam_date, $grade, $eid, $student_id]);
    } else {
        // Добавление нового результата
        $sql = "INSERT INTO exams (student_id, discipline_id, exam_date, grade) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $discipline_id, $exam_date, $grade]);
    }
    // Возврат к списку результатов
    header("Location: styled_results.php?student_id=$student_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
<title><?php echo $editing ? "Редактирование результата экзамена" : "Добавление результата экзамена"; ?></title>
</head>
<body>
<section class="page">
  <div class="container">

<h2>
    <?php echo $editing ? "Редактирование результата экзамена" : "Добавление результата экзамена"; ?>
    <br>студента <?php echo $studentName; ?> (группа <?php echo $groupName; ?>)
</h2>

<form method="post">
    <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
    <?php endif; ?>

    Группа:
    <select disabled>
        <option><?php echo $groupName; ?></option>
    </select>
    <br>
    Студент:
    <select disabled>
        <option><?php echo $studentName; ?></option>
    </select>
    <br>
    Курс:
    <select name="course_year" id="course" onchange="filterDisciplines()" required>
        <option value="">-- Выберите курс --</option>
        <?php for ($y = 1; $y <= 4; $y++):
            $sel = ($exam['course_year'] == $y) ? 'selected' : ''; ?>
            <option value="<?php echo $y; ?>" <?php echo $sel; ?>><?php echo $y; ?></option>
        <?php endfor; ?>
    </select>
    <br>
    Дисциплина:
    <select name="discipline_id" id="discipline" required>
        <option value="">-- Выберите дисциплину --</option>
        <?php
        // Получаем все дисциплины для данного учебного направления
        $discList = $pdo->prepare("SELECT id, name, course_year 
                                   FROM disciplines 
                                   WHERE program = ? 
                                   ORDER BY course_year, name");
        $discList->execute([$program]);
        $allDiscs = $discList->fetchAll();
        foreach ($allDiscs as $disc):
            $id   = $disc['id'];
            $name = htmlspecialchars($disc['name']);
            $year = $disc['course_year'];
            $sel  = ($id == $exam['discipline_id']) ? 'selected' : '';
            ?>
            <option value="<?php echo $id; ?>" data-year="<?php echo $year; ?>" <?php echo $sel; ?>>
                <?php echo $name; ?> (<?php echo $year; ?> курс)
            </option>
        <?php endforeach; ?>
    </select>
    <br>
    Дата экзамена:
    <input type="date" name="exam_date" value="<?php echo htmlspecialchars($exam['exam_date']); ?>" required>
    <br>
    Оценка:
    <input type="text" name="grade" value="<?php echo htmlspecialchars($exam['grade']); ?>" required>
    <br>
    <input type="submit" value="Сохранить">
    <a href="styled_results.php?student_id=<?php echo $student_id; ?>">Отмена</a>
</form>

<!-- Скрипт для фильтрации списка дисциплин по выбранному курсу -->
<script>
    function filterDisciplines() {
        var course = document.getElementById('course').value;
        var disciplineSelect = document.getElementById('discipline');
        var options = disciplineSelect.options;
        if (course === "") {
            // Если курс не выбран, отключаем список дисциплин
            disciplineSelect.value = "";
            disciplineSelect.disabled = true;
        } else {
            disciplineSelect.disabled = false;
            // Показываем только те опции, чей data-year совпадает с выбранным курсом
            for (var i = 0; i < options.length; i++) {
                var opt = options[i];
                if (opt.value === "") continue;  // пропускаем плейсхолдер
                var year = opt.getAttribute('data-year');
                opt.style.display = (year === course) ? "" : "none";
            }
            // Если текущая выбранная дисциплина не соответствует курсу, сбросим выбор
            if (disciplineSelect.value) {
                var selectedYear = disciplineSelect.options[disciplineSelect.selectedIndex].getAttribute('data-year');
                if (selectedYear !== course) {
                    disciplineSelect.value = "";
                }
            }
        }
    }
    // При загрузке страницы выполняем фильтрацию (на случай, если редактирование и курс уже выбран)
    document.addEventListener('DOMContentLoaded', filterDisciplines);
</script>

  </div>
</section>
</body>
</html>
