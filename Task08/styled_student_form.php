<?php
require 'conn.php';
$editing = false;
$student = ['id' => null, 'last_name' => '', 'first_name' => '', 'gender' => '', 'group_id' => ''];

// Если передан параметр id, получаем данные студента для редактирования
if (isset($_GET['id'])) {
    $editing = true;
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if (!$student) {
        die("Студент не найден!");
    }
}

// Если форма отправлена методом POST, обрабатываем данные
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'] ?? '';            // будет пустой строкой для нового студента
    $last    = $_POST['last_name'];
    $first   = $_POST['first_name'];
    $gender  = $_POST['gender'] ?? '';
    $group_id = $_POST['group_id'];
    if ($id) {
        // Обновление существующего студента
        $sql = "UPDATE students 
                SET last_name = ?, first_name = ?, gender = ?, group_id = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$last, $first, $gender, $group_id, $id]);
    } else {
        // Вставка нового студента
        $sql = "INSERT INTO students (last_name, first_name, gender, group_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$last, $first, $gender, $group_id]);
    }
    // После сохранения перенаправляем на главную страницу
    header("Location: styled_index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
<title><?php echo $editing ? "Редактирование студента" : "Добавление студента"; ?></title>
</head>
<body>
<section class="page">
  <div class="container">

<h2><?php echo $editing ? "Редактирование студента" : "Добавление студента"; ?></h2>

<form method="post">
    <?php if ($editing): ?>
        <!-- При редактировании добавляем скрытое поле с ID -->
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
    <?php endif; ?>

    Фамилия:
    <input type="text" name="last_name" required
           value="<?php echo htmlspecialchars($student['last_name']); ?>">
    <br>
    Имя:
    <input type="text" name="first_name" required
           value="<?php echo htmlspecialchars($student['first_name']); ?>">
    <br>
    Пол:
    <input type="radio" name="gender" value="М"
        <?php if ($student['gender'] === 'М') echo 'checked'; ?>> Мужской
    <input type="radio" name="gender" value="Ж"
        <?php if ($student['gender'] === 'Ж') echo 'checked'; ?>> Женский
    <br>
    Группа:
    <select name="group_id" required>
        <option value="">-- Выберите группу --</option>
        <?php
        // Заполняем список групп из справочника групп
        $groups = $pdo->query("SELECT id, group_code FROM groups");
        foreach ($groups as $grp) {
            $sel = ($grp['id'] == $student['group_id']) ? 'selected' : '';
            echo "<option value=\"{$grp['id']}\" $sel>{$grp['group_code']}</option>";
        }
        ?>
    </select>
    <br>
    <input type="submit" value="Сохранить">
    <a href="styled_index.php">Отмена</a>
</form>

  </div>
</section>
</body>
</html>
