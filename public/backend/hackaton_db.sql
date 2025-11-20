-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.2
-- Время создания: Ноя 20 2025 г., 02:09
-- Версия сервера: 8.2.0
-- Версия PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `hackaton_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `patients`
--

CREATE TABLE `patients` (
  `id` int NOT NULL,
  `therapist_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` int NOT NULL,
  `gender` set('мужчина','женщина') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `patients`
--

INSERT INTO `patients` (`id`, `therapist_id`, `name`, `age`, `gender`, `created_at`) VALUES
(16, 4, 'Петров Иванович', 40, 'мужчина', '2025-11-19 23:02:57'),
(17, 4, 'Иванова Дарья', 45, 'женщина', '2025-11-19 23:41:18'),
(18, 4, 'Иванов Алексей', 30, 'мужчина', '2025-11-19 23:44:08'),
(19, 1, 'Иванов Алексей', 20, 'мужчина', '2025-11-19 23:50:21');

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `sud_score` int NOT NULL,
  `quality_score` int NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `session_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Дамп данных таблицы `sessions`
--

INSERT INTO `sessions` (`id`, `patient_id`, `sud_score`, `quality_score`, `comments`, `session_date`, `created_at`) VALUES
(47, 16, 8, 10, 'Тестовый комментарий', '2025-11-20 01:03:33', '2025-11-19 23:03:33'),
(48, 18, 2, 5, '123123', '2025-11-20 01:44:18', '2025-11-19 23:44:18'),
(49, 17, 10, 5, 'цукфывцйу', '2025-11-20 01:46:23', '2025-11-19 23:46:23'),
(50, 18, 2, 7, 'Тестовый комментарий номер два', '2025-11-20 01:48:55', '2025-11-19 23:48:55'),
(51, 19, 3, 10, 'Иванов Алексей, прошел физиотерапию на отлично', '2025-11-20 01:50:56', '2025-11-19 23:50:56');

-- --------------------------------------------------------

--
-- Структура таблицы `therapists`
--

CREATE TABLE `therapists` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `therapists`
--

INSERT INTO `therapists` (`id`, `email`, `password`, `name`, `created_at`) VALUES
(1, 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Доктор Иванов', '2025-11-19 18:42:09'),
(2, 'ivanov@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Доктор Иванов Сергей', '2024-01-15 08:00:00'),
(3, 'petrova@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Доктор Петрова Анна', '2024-02-20 09:30:00'),
(4, 'sidorov@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Доктор Сидоров Михаил', '2024-03-10 12:15:00');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_therapist` (`therapist_id`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_date` (`session_date`);

--
-- Индексы таблицы `therapists`
--
ALTER TABLE `therapists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `therapists`
--
ALTER TABLE `therapists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`therapist_id`) REFERENCES `therapists` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
