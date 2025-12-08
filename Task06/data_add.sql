-- 1. Добавление новых пользователей
INSERT INTO users (name, email, gender, register_date, occupation_id)
VALUES 
('Максим Самылкин', 'maxim.samylkin@example.com', 'male', date('now'), 
    (SELECT id FROM occupations WHERE name = 'student')),
('Никита Андронов', 'nikita.andronov@example.com', 'male', date('now'), 
    (SELECT id FROM occupations WHERE name = 'student')),
('Вадим Орлов', 'vadim.orlov@example.com', 'male', date('now'), 
    (SELECT id FROM occupations WHERE name = 'student')),
('Михаил Марьин', 'mikhail.maryn@example.com', 'male', date('now'), 
    (SELECT id FROM occupations WHERE name = 'student')),
('Роман Пьянов', 'roman.pianov@example.com', 'male', date('now'), 
    (SELECT id FROM occupations WHERE name = 'student'));


-- 2. Добавление новых фильмов
INSERT INTO movies (title, year)
VALUES 
('Двухсотлетний человек', 1999),
('Закатать в асфальт', 2018),
('Догмен', 2023);


-- 3. Добавление жанров для фильмов
INSERT INTO movies_genres (movie_id, genre_id)
VALUES 
-- Двухсотлетний человек: Sci-Fi, Drama
((SELECT id FROM movies WHERE title = 'Двухсотлетний человек'), 
 (SELECT id FROM genres WHERE name = 'Sci-Fi')),
((SELECT id FROM movies WHERE title = 'Двухсотлетний человек'), 
 (SELECT id FROM genres WHERE name = 'Drama')),

-- Закатать в асфальт: Action, Crime, Thriller
((SELECT id FROM movies WHERE title = 'Закатать в асфальт'), 
 (SELECT id FROM genres WHERE name = 'Action')),
((SELECT id FROM movies WHERE title = 'Закатать в асфальт'), 
 (SELECT id FROM genres WHERE name = 'Crime')),
((SELECT id FROM movies WHERE title = 'Закатать в асфальт'), 
 (SELECT id FROM genres WHERE name = 'Thriller')),

-- Догмен: Drama, Comedy
((SELECT id FROM movies WHERE title = 'Догмен'), 
 (SELECT id FROM genres WHERE name = 'Drama')),
((SELECT id FROM movies WHERE title = 'Догмен'), 
 (SELECT id FROM genres WHERE name = 'Comedy'));


-- 4. Добавление отзывов
INSERT INTO ratings (user_id, movie_id, rating, timestamp)
VALUES 
((SELECT id FROM users WHERE email = 'maxim.samylkin@example.com'), 
 (SELECT id FROM movies WHERE title = 'Двухсотлетний человек'), 4.5, strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'maxim.samylkin@example.com'), 
 (SELECT id FROM movies WHERE title = 'Закатать в асфальт'), 4.3, strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'maxim.samylkin@example.com'), 
 (SELECT id FROM movies WHERE title = 'Догмен'), 4.8, strftime('%s', 'now'));


-- 5. Добавление тегов
INSERT INTO tags (user_id, movie_id, tag, timestamp)
VALUES 
((SELECT id FROM users WHERE email = 'maxim.samylkin@example.com'), 
 (SELECT id FROM movies WHERE title = 'Двухсотлетний человек'), 'робототехника айзек азимов', strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'maxim.samylkin@example.com'), 
 (SELECT id FROM movies WHERE title = 'Закатать в асфальт'), 'криминальный триллер', strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'maxim.samylkin@example.com'), 
 (SELECT id FROM movies WHERE title = 'Догмен'), 'бельгия оскар 2023', strftime('%s', 'now'));