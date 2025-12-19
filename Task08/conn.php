<?php

$dsn = "sqlite:" . __DIR__ . "/students.db";
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Включаем поддержку внешних ключей (необходимо для SQLite)
$pdo->exec("PRAGMA foreign_keys = ON");

// Создание таблиц (если не существуют)
$pdo->exec("CREATE TABLE IF NOT EXISTS groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_code TEXT NOT NULL UNIQUE,
    program TEXT,
    admission_year INTEGER
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    last_name TEXT,
    first_name TEXT,
    gender TEXT,
    group_id INTEGER,
    FOREIGN KEY(group_id) REFERENCES groups(id)
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS disciplines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    program TEXT,
    course_year INTEGER
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER,
    discipline_id INTEGER,
    exam_date TEXT,
    grade TEXT,
    FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY(discipline_id) REFERENCES disciplines(id)
)");

// Заполнение справочных данных, если таблицы пустые
// Добавляем группы
$count = $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO groups (group_code, program, admission_year) VALUES
        ('CS-2022-1','Computer Science',2022),
        ('CS-2023-1','Computer Science',2023),
        ('MATH-2022-1','Mathematics',2022),
        ('MATH-2023-1','Mathematics',2023)");
}
// Добавляем дисциплины для программ (4 курса для каждой программы)
$count = $pdo->query("SELECT COUNT(*) FROM disciplines")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO disciplines (name, program, course_year) VALUES
        ('Programming 101','Computer Science',1),
        ('Data Structures','Computer Science',2),
        ('Algorithms','Computer Science',3),
        ('Operating Systems','Computer Science',4),
        ('Calculus I','Mathematics',1),
        ('Linear Algebra','Mathematics',2),
        ('Abstract Algebra','Mathematics',3),
        ('Topology','Mathematics',4)
    ");
}
?>
