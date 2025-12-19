<?php

$dsn = "sqlite:" . __DIR__ . "/students.db";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    fwrite(STDERR, "Ошибка подключения к базе данных: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$currentYear = date("Y");
$sqlGroups = "SELECT `number` FROM `groups` WHERE `graduation_year` <= :year ORDER BY `number`";
try {
    $stmt = $pdo->prepare($sqlGroups);
    $stmt->execute(['year' => $currentYear]);
    $groupNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    fwrite(STDERR, "Ошибка при получении списка групп: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

if (!$groupNumbers || count($groupNumbers) === 0) {
    echo "В базе данных нет групп, удовлетворяющих условию." . PHP_EOL;
    exit(0);
}

$groupNumbers = array_map('strval', $groupNumbers);

echo "Доступные группы: " . implode(", ", $groupNumbers) . PHP_EOL;
echo "Введите номер группы для фильтрации или нажмите Enter для вывода всех: ";
$handle = fopen("php://stdin", "r");
$inputGroup = trim(fgets($handle));

while ($inputGroup !== "" && !in_array($inputGroup, $groupNumbers, true)) {
    // Если введено не пусто и такого номера нет в списке, запрашиваем повторно
    echo "Группа \"$inputGroup\" не найдена. Пожалуйста, введите существующий номер группы (или нажмите Enter для всех): ";
    $inputGroup = trim(fgets($handle));
}
$sqlStudents = "
    SELECT 
        g.number AS group_number,
        g.program AS program,
        s.last_name AS last_name,
        s.first_name AS first_name,
        s.patronymic AS patronymic,
        s.gender AS gender,
        s.birthdate AS birthdate,
        s.student_card AS student_card
    FROM students s
    JOIN groups g ON s.group_id = g.id
    WHERE g.graduation_year <= :year";
if ($inputGroup !== "") {
    $sqlStudents .= " AND g.number = :groupNumber";
}
$sqlStudents .= " ORDER BY g.number, s.last_name";

try {
    $stmt = $pdo->prepare($sqlStudents);
    if ($inputGroup === "") {
        $stmt->execute(['year' => $currentYear]);
    } else {
        $stmt->execute(['year' => $currentYear, 'groupNumber' => $inputGroup]);
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    fwrite(STDERR, "Ошибка при выполнении запроса студентов: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

if (!$students || count($students) === 0) {
    echo "Нет данных для отображения (студентов не найдено)." . PHP_EOL;
    exit(0);
}

$headers = [
    "номер группы",
    "направление подготовки",
    "ФИО",
    "пол",
    "дата рождения",
    "номер студенческого билета"
];
foreach ($students as &$st) {
    $fullName = $st['last_name'] . " " . $st['first_name'];
    if (!empty($st['patronymic'])) {
        $fullName .= " " . $st['patronymic'];
    }
    $st['full_name'] = $fullName;
    if (!empty($st['birthdate'])) {
        $st['birthdate'] = date("d.m.Y", strtotime($st['birthdate']));
    } else {
        $st['birthdate'] = "";
    }
}
$rows = [];
foreach ($students as $st) {
    $rows[] = [
        $st['group_number'],
        $st['program'],
        $st['full_name'],
        $st['gender'],
        $st['birthdate'],
        $st['student_card']
    ];
}

array_unshift($rows, $headers);

$colWidths = array_fill(0, count($headers), 0);
foreach ($rows as $row) {
    foreach ($row as $colIndex => $colValue) {
        $length = mb_strlen($colValue, 'UTF-8');
        if ($length > $colWidths[$colIndex]) {
            $colWidths[$colIndex] = $length;
        }
    }
}

$h = "─";  // горизонтальная линия
$v = "│";  // вертикальная линия
$topLeft = "┌";
$topRight = "┐";
$bottomLeft = "└";
$bottomRight = "┘";
$crossTop = "┬";
$crossMiddle = "┼";
$crossBottom = "┴";
$verticalLeft = "├";
$verticalRight = "┤";

// Функция для построения разделительной линии
function buildSeparator($leftChar, $midChar, $rightChar, $colWidths, $hChar)
{
    $line = $leftChar;
    $colCount = count($colWidths);
    foreach ($colWidths as $i => $width) {
        // рисуем горизонтальную линию равной длины столбца
        $line .= str_repeat($hChar, $width);
        // добавляем пересечение или правый угол
        if ($i < $colCount - 1) {
            $line .= $midChar;
        } else {
            $line .= $rightChar;
        }
    }
    return $line;
}

// Строим верхнюю границу таблицы
$topBorder = buildSeparator($topLeft, $crossTop, $topRight, $colWidths, $h);
// Строим разделитель между заголовком и данными (можно использовать ту же функцию, с соответствующими символами)
$headerBorder = buildSeparator($verticalLeft, $crossMiddle, $verticalRight, $colWidths, $h);
// Строим нижнюю границу таблицы
$bottomBorder = buildSeparator($bottomLeft, $crossBottom, $bottomRight, $colWidths, $h);

// Выводим верхнюю границу
echo $topBorder . PHP_EOL;

// Выводим строки таблицы
foreach ($rows as $index => $row) {
    // Вывод одной строки таблицы
    echo $v;  // левая граница строки
    foreach ($row as $colIndex => $colValue) {
        // Выравниваем значение по левому краю, дополняя пробелами до ширины столбца
        $padLength = $colWidths[$colIndex] - mb_strlen($colValue, 'UTF-8');
        if ($padLength < 0) $padLength = 0;
        $cell = $colValue . str_repeat(" ", $padLength);
        echo $cell . $v;
    }
    echo PHP_EOL;
    // После вывода заголовка вставляем разделительную линию headerBorder
    if ($index === 0) {
        echo $headerBorder . PHP_EOL;
    }
}
// Выводим нижнюю границу
echo $bottomBorder . PHP_EOL;

