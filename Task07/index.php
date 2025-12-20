<?php
// index.php - веб-приложение для отображения списка студентов с фильтром по группе

$dsn = "sqlite:" . __DIR__ . "/students.db";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Если не удалось соединиться, покажем ошибку и остановим выполнение
    die("<p><strong>Ошибка подключения к базе данных:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Получение списка номеров всех действующих групп для выпадающего списка
$currentYear = date("Y");
$groupNumbers = [];  // массив для номеров групп
try {
    $stmt = $pdo->prepare("SELECT `number` FROM `groups` WHERE `graduation_year` <= :year ORDER BY `number`");
    $stmt->execute(['year' => $currentYear]);
    $groupNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("<p>Ошибка запроса групп: " . htmlspecialchars($e->getMessage()) . "</p>");
}
if (!$groupNumbers) {
    $groupNumbers = [];
}
// Преобразуем к строкам (на случай, если номера групп числовые)
$groupNumbers = array_map('strval', $groupNumbers);

// Определяем выбранный фильтр (номер группы) из параметра запроса
$selectedGroup = "";
if (isset($_GET['group'])) {
    $selectedGroup = trim($_GET['group']);
    // Проверка валидности: если не пусто и нет в списке доступных групп, сбрасываем фильтр
    if ($selectedGroup !== "" && !in_array($selectedGroup, $groupNumbers, true)) {
        $selectedGroup = "";
    }
}

// Подготовка SQL-запроса для выборки студентов с учетом фильтра
$sql = "
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
if ($selectedGroup !== "") {
    $sql .= " AND g.number = :groupNumber";
}
$sql .= " ORDER BY g.number, s.last_name";

try {
    $stmt = $pdo->prepare($sql);
    if ($selectedGroup === "") {
        $stmt->execute(['year' => $currentYear]);
    } else {
        $stmt->execute(['year' => $currentYear, 'groupNumber' => $selectedGroup]);
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<p>Ошибка запроса студентов: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Обработка данных студентов: объединяем ФИО и форматируем дату
foreach ($students as &$st) {
    // Объединяем фамилию, имя, отчество в одну строку
    $fio = $st['last_name'] . " " . $st['first_name'];
    if (!empty($st['patronymic'])) {
        $fio .= " " . $st['patronymic'];
    }
    $st['fio'] = $fio;
    // Форматируем дату рождения в дд.мм.гггг
    if (!empty($st['birthdate'])) {
        $st['birthdate'] = date("d.m.Y", strtotime($st['birthdate']));
    }
}
// Освобождаем ссылку
unset($st);
?>
<!DOCTYPE html>
<html class="page" lang="ru">
<head class="page__head">
  <meta class="page__meta" charset="UTF-8">
  <meta class="page__meta" name="viewport" content="width=device-width, initial-scale=1">
  <title class="page__title">Список студентов</title>
  <style class="page__styles">
    :root{
      --bg:#0b0f14;
      --bg2:#0f1620;
      --stroke:rgba(255,255,255,.12);
      --stroke2:rgba(255,255,255,.18);
      --card:rgba(255,255,255,.06);
      --card2:rgba(255,255,255,.09);
      --text:rgba(255,255,255,.92);
      --muted:rgba(255,255,255,.68);
      --muted2:rgba(255,255,255,.52);
      --accent:#66a5ad;
      --accent2:#c4dfe6;
      --radius-xl:20px;
      --radius-lg:16px;
      --radius-md:12px;
      --shadow:0 14px 50px rgba(0,0,0,.45);
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      color:var(--text);
      font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,"Noto Sans","Apple Color Emoji","Segoe UI Emoji";
      line-height:1.35;
      background:
        radial-gradient(1100px 600px at 15% -10%, rgba(102,165,173,.22), transparent 60%),
        radial-gradient(900px 520px at 90% 0%, rgba(196,223,230,.14), transparent 55%),
        radial-gradient(1200px 800px at 50% 110%, rgba(7,87,91,.18), transparent 60%),
        linear-gradient(180deg,var(--bg),var(--bg2));
    }
    a{color:inherit;text-decoration:none}
    .container{width:min(1100px,calc(100% - 32px));margin:0 auto}
    .app{min-height:100%;display:flex;flex-direction:column}
    .app__header{
      position:sticky;top:0;z-index:20;
      background:rgba(11,15,20,.72);
      backdrop-filter:blur(14px);
      border-bottom:1px solid var(--stroke);
    }
    .header__inner{
      display:flex;align-items:center;justify-content:space-between;
      gap:14px;padding:16px 0;
    }
    .brand{display:flex;align-items:center;gap:10px;min-width:0}
    .brand__logo{
      width:40px;height:40px;border-radius:14px;
      background:
        radial-gradient(60% 60% at 30% 30%, rgba(196,223,230,.55), transparent 60%),
        radial-gradient(70% 70% at 70% 70%, rgba(102,165,173,.55), transparent 60%),
        linear-gradient(145deg, rgba(0,59,70,.7), rgba(7,87,91,.35));
      border:1px solid var(--stroke);
      box-shadow:0 10px 30px rgba(0,0,0,.35);
      flex:0 0 auto;
    }
    .brand__text{min-width:0}
    .brand__title{margin:0;font-size:16px;font-weight:800;letter-spacing:.2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .brand__subtitle{margin:2px 0 0 0;font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .header__meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .pill{
      display:inline-flex;align-items:center;gap:8px;
      padding:8px 12px;border-radius:999px;
      background:rgba(255,255,255,.06);
      border:1px solid var(--stroke);
      color:var(--muted);font-size:12px;
    }
    .pill__dot{width:7px;height:7px;border-radius:999px;background:var(--accent);box-shadow:0 0 0 4px rgba(102,165,173,.16)}
    .app__main{padding:22px 0 34px 0;flex:1}
    .section{width:100%}
    .panel{
      border-radius:var(--radius-xl);
      border:1px solid var(--stroke);
      background:linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.04));
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    .panel__top{
      padding:18px 18px 14px 18px;
      border-bottom:1px solid var(--stroke);
      display:flex;align-items:flex-start;justify-content:space-between;
      gap:12px;flex-wrap:wrap;
    }
    .panel__title{margin:0;font-size:18px;font-weight:900;letter-spacing:.2px}
    .panel__hint{margin:6px 0 0 0;color:var(--muted);font-size:13px}
    .panel__tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .filter{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .filter__label{font-size:12px;color:var(--muted)}
    .filter__select{
      appearance:none;-webkit-appearance:none;-moz-appearance:none;
      padding:10px 40px 10px 12px;
      border-radius:12px;border:1px solid var(--stroke);
      background:linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.06));
      color:var(--text);outline:none;font-size:13px;cursor:pointer;
      background-image:
        linear-gradient(45deg, transparent 50%, rgba(255,255,255,.7) 50%),
        linear-gradient(135deg, rgba(255,255,255,.7) 50%, transparent 50%);
      background-position:calc(100% - 18px) 52%, calc(100% - 12px) 52%;
      background-size:6px 6px, 6px 6px;
      background-repeat:no-repeat;
    }
    .filter__select:focus{border-color:rgba(102,165,173,.7);box-shadow:0 0 0 4px rgba(102,165,173,.14)}
    .filter__button{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 14px;border-radius:12px;
      border:1px solid rgba(102,165,173,.45);
      background:rgba(102,165,173,.18);
      color:var(--text);font-weight:800;font-size:13px;
      cursor:pointer;transition:transform .12s ease, background .12s ease, border-color .12s ease;
      user-select:none;
    }
    .filter__button:hover{transform:translateY(-1px);background:rgba(102,165,173,.24);border-color:rgba(196,223,230,.55)}
    .filter__button:active{transform:translateY(0)}
    .filter__reset{
      display:inline-flex;align-items:center;
      padding:10px 12px;border-radius:12px;
      border:1px solid var(--stroke);
      background:rgba(255,255,255,.05);
      color:var(--muted);font-size:13px;
      transition:background .12s ease, border-color .12s ease, color .12s ease;
    }
    .filter__reset:hover{background:rgba(255,255,255,.08);border-color:var(--stroke2);color:var(--text)}
    .panel__body{padding:14px 18px 18px 18px}
    .empty{
      border:1px dashed rgba(255,255,255,.22);
      border-radius:var(--radius-lg);
      padding:18px;background:rgba(0,0,0,.14);
    }
    .empty__title{margin:0;font-size:14px;font-weight:900}
    .empty__text{margin:6px 0 0 0;color:var(--muted);font-size:13px}
    .table-wrap{
      border-radius:var(--radius-lg);
      border:1px solid var(--stroke);
      background:rgba(0,0,0,.14);
      overflow:auto;
    }
    .table{width:100%;border-collapse:separate;border-spacing:0;min-width:900px}
    .table__head{position:sticky;top:0;z-index:10}
    .table__head-cell{
      text-align:left;font-size:12px;font-weight:900;letter-spacing:.3px;
      color:rgba(255,255,255,.82);
      padding:14px 14px;
      background:linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.06));
      border-bottom:1px solid var(--stroke);
      white-space:nowrap;
    }
    .table__row{transition:background .12s ease}
    .table__row:nth-child(odd){background:rgba(255,255,255,.03)}
    .table__row:hover{background:rgba(102,165,173,.10)}
    .table__cell{
      padding:12px 14px;
      border-bottom:1px solid rgba(255,255,255,.06);
      color:rgba(255,255,255,.86);
      font-size:13px;vertical-align:top;
    }
    .badge{
      display:inline-flex;align-items:center;gap:8px;
      padding:6px 10px;border-radius:999px;
      border:1px solid var(--stroke);
      background:rgba(255,255,255,.06);
      font-size:12px;color:var(--text);white-space:nowrap;
    }
    .badge__dot{width:8px;height:8px;border-radius:999px;background:var(--accent)}
    .badge--gender-m .badge__dot{background:#66a5ad}
    .badge--gender-f .badge__dot{background:#c4dfe6}
    .app__footer{border-top:1px solid var(--stroke);background:rgba(11,15,20,.6)}
    .footer__inner{padding:14px 0;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;color:var(--muted2);font-size:12px}
    .footer__link{color:var(--muted);transition:color .12s ease}
    .footer__link:hover{color:var(--accent2)}
    @media (max-width: 860px){.table{min-width:0}}
    @media (max-width: 640px){
      .header__inner{padding:14px 0}
      .panel__top{padding:16px 14px 12px 14px}
      .panel__body{padding:12px 14px 14px 14px}
      .table-wrap{overflow:visible;border:none;background:transparent}
      .table,.table__head,.table__head-row,.table__body,.table__row,.table__cell{display:block;width:100%;min-width:0}
      .table__head{display:none}
      .table__row{border:1px solid var(--stroke);border-radius:var(--radius-lg);background:rgba(255,255,255,.05);margin:0 0 12px 0;overflow:hidden}
      .table__cell{
        border-bottom:1px solid rgba(255,255,255,.07);
        padding:12px 12px;
        display:grid;
        grid-template-columns:1fr;
        gap:6px;
      }
      .table__cell:last-child{border-bottom:none}
      .table__cell::before{
        content: attr(data-label);
        font-size:11px;
        font-weight:900;
        text-transform:lowercase;
        letter-spacing:.2px;
        color:var(--muted2);
      }
    }
  </style>
</head>
<body class="page__body">
  <div class="app">

    <header class="app__header">
      <div class="container header__inner">
        <div class="brand">
          <div class="brand__logo" aria-hidden="true"></div>
          <div class="brand__text">
            <p class="brand__title">Список студентов</p>
            <p class="brand__subtitle">SQLite + PHP • фильтр по группе</p>
          </div>
        </div>

        <div class="header__meta">
          <span class="pill"><span class="pill__dot" aria-hidden="true"></span>Год: <?= htmlspecialchars($currentYear) ?></span>
          <span class="pill">Записей: <?= htmlspecialchars((string)count($students)) ?></span>
        </div>
      </div>
    </header>

    <main class="app__main">
      <section class="section">
        <div class="container">
          <div class="panel">
            <div class="panel__top">
              <div class="panel__info">
                <h1 class="panel__title">
                  Студенты
                  <?php if ($selectedGroup !== "" && in_array($selectedGroup, $groupNumbers, true)): ?>
                    — группа <?= htmlspecialchars($selectedGroup) ?>
                  <?php endif; ?>
                </h1>
                <p class="panel__hint">На телефоне таблица автоматически превращается в карточки.</p>
              </div>

              <div class="panel__tools">
                <form class="filter" method="get" action="index.php">
                  <label class="filter__label" for="groupSelect">Группа</label>
                  <select class="filter__select" name="group" id="groupSelect">
                    <option value=""<?= ($selectedGroup === "" ? " selected" : "") ?>>Все группы</option>
                    <?php foreach ($groupNumbers as $groupNum): ?>
                      <option value="<?= htmlspecialchars($groupNum) ?>"<?= ($groupNum === $selectedGroup ? " selected" : "") ?>>
                        <?= htmlspecialchars($groupNum) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="filter__button" type="submit">Показать</button>
                  <?php if ($selectedGroup !== ""): ?>
                    <a class="filter__reset" href="index.php">Сбросить</a>
                  <?php endif; ?>
                </form>
              </div>
            </div>

            <div class="panel__body">
              <?php if (empty($students)): ?>
                <div class="empty">
                  <p class="empty__title">Нет данных для отображения</p>
                  <p class="empty__text">Попробуй выбрать другую группу или сбросить фильтр.</p>
                </div>
              <?php else: ?>
                <div class="table-wrap">
                  <table class="table">
                    <thead class="table__head">
                      <tr class="table__head-row">
                        <th class="table__head-cell">номер группы</th>
                        <th class="table__head-cell">направление подготовки</th>
                        <th class="table__head-cell">ФИО</th>
                        <th class="table__head-cell">пол</th>
                        <th class="table__head-cell">дата рождения</th>
                        <th class="table__head-cell">студбилет</th>
                      </tr>
                    </thead>
                    <tbody class="table__body">
                      <?php foreach ($students as $st): ?>
                        <?php
                          $gender = (string)($st['gender'] ?? '');
                          $genderClass = '';
                          if ($gender === 'М') { $genderClass = ' badge--gender-m'; }
                          if ($gender === 'Ж') { $genderClass = ' badge--gender-f'; }
                        ?>
                        <tr class="table__row">
                          <td class="table__cell" data-label="номер группы"><?= htmlspecialchars($st['group_number']) ?></td>
                          <td class="table__cell" data-label="направление подготовки"><?= htmlspecialchars($st['program']) ?></td>
                          <td class="table__cell" data-label="ФИО"><?= htmlspecialchars($st['fio']) ?></td>
                          <td class="table__cell" data-label="пол">
                            <span class="badge<?= $genderClass ?>">
                              <span class="badge__dot" aria-hidden="true"></span>
                              <?= htmlspecialchars($gender) ?>
                            </span>
                          </td>
                          <td class="table__cell" data-label="дата рождения"><?= htmlspecialchars($st['birthdate']) ?></td>
                          <td class="table__cell" data-label="студбилет"><?= htmlspecialchars($st['student_card']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="app__footer">
      <div class="container footer__inner">
        <span class="footer__text">© <?= htmlspecialchars($currentYear) ?> • Учебный проект</span>
        <a class="footer__link" href="#">PHP + SQLite</a>
      </div>
    </footer>

  </div>
</body>
</html>
