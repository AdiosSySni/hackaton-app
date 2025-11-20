<?php
// api/index.php

// Заголовки для CORS и JSON-ответов, чтобы можно было принимать и отправлять запроссы между бекендом и фронтендом, иначе при отправке каких-нибудь куки или сессий, браузер будет ругаться на CORS-политику. И в принципе многие запросы попросту не будут работать, т.к. они будут требовать авторизации на стороне сервера, то есть сервер должен знать откуда и от кого он принимает информацию.
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
// Подключаем модели, примерное тоже самое что и в laravel(в них хранится основная бизнес-логика, того, что делают эти сущности, в основном как происходит общение с БД и т.д.)
require_once '../config/database.php';
require_once '../models/Therapist.php';
require_once '../models/Patient.php';
require_once '../models/Session.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Простая маршрутизация: берём часть пути после /api/
$path = '';
if (isset($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} elseif (isset($_SERVER['REQUEST_URI'])) {
    $path = $_SERVER['REQUEST_URI'];
}
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);
$request = array_values(array_filter(explode('/', trim($path, '/'))));
$endpoint = '';
if (isset($request[0])) $endpoint = $request[0];

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) return [];
    return $data;
}

// Вспомогательная валидация: возвращает массив с отсутствующими или пустыми полями
function validateRequiredFields(array $data, array $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }
    return $missing;
}

function checkAuth() {
    if (!isset($_SESSION['therapist_id'])) {
        jsonResponse(['error' => 'Не авторизован'], 401);
    }
    return $_SESSION['therapist_id'];
}

// ------------------------------
// АВТОРИЗАЦИЯ: вход, регистрация, выход
// ------------------------------

// Вход(вход происходит посредством ввода емайла и пароля терапевта)
if ($endpoint === 'login' && $method === 'POST') {
    $data = getJsonInput();
    $email = '';
    $password = '';
    if (is_array($data)) {
        if (isset($data['email'])) $email = $data['email'];
        if (isset($data['password'])) $password = $data['password'];
    }
    // Валидация полей
    $missing = validateRequiredFields($data, ['email', 'password']);
    if (!empty($missing)) {
        jsonResponse(['error' => 'Отсутствуют требуемые поля', 'fields' => $missing], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Неверный формат электронной почты'], 400);
    }

    $therapist = new Therapist($db);
    $therapist->email = $email;
    $therapist->password = $password;

    if ($therapist->login()) {
        $_SESSION['therapist_id'] = $therapist->id;
        $_SESSION['therapist_name'] = $therapist->name;
        jsonResponse(['success' => true, 'therapist' => ['id' => $therapist->id, 'name' => $therapist->name, 'email' => $therapist->email]]);
    }

    jsonResponse(['error' => 'Неверные учётные данные'], 401);
}
// Регистрация нового терапевта
if ($endpoint === 'register' && $method === 'POST') {
    $data = getJsonInput();
    $email = '';
    $password = '';
    $name = '';
    if (is_array($data)) {
        if (isset($data['email'])) $email = $data['email'];
        if (isset($data['password'])) $password = $data['password'];
        if (isset($data['name'])) $name = $data['name'];
    }

    // Валидация полей
    $missing = validateRequiredFields($data, ['email','password','name']);
    if (!empty($missing)) jsonResponse(['error' => 'Отсутствуют требуемые поля', 'fields' => $missing], 400);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Неверный формат электронной почты'], 400);
    }

    if (!is_string($password) || strlen($password) < 6) {
        jsonResponse(['error' => 'Пароль должен быть не менее 6 символов'], 400);
    }

    $therapist = new Therapist($db);
    $therapist->email = $email;
    $therapist->password = $password;
    $therapist->name = $name;
    // Сохраняем данные в сессию
    if ($therapist->register()) {
        $_SESSION['therapist_id'] = $therapist->id;
        $_SESSION['therapist_name'] = $therapist->name;
        jsonResponse(['success' => true, 'therapist' => ['id' => $therapist->id, 'name' => $therapist->name]], 201);
    }
    jsonResponse(['error' => 'Регистрация не удалась (электронная почта может быть уже зарегистрирована)'], 400);
}
// Выход(уничтожение сессии)
if ($endpoint === 'logout' && $method === 'POST') {
    session_destroy();
    jsonResponse(['success' => true]);
}
// Простой роут для проверки того, что пользователь(терапевт) авторизован, можно удалить
if ($endpoint === 'check-auth' && $method === 'GET') {
    if (isset($_SESSION['therapist_id'])) {
        jsonResponse(['authenticated' => true, 'therapist' => ['id' => $_SESSION['therapist_id'], 'name' => $_SESSION['therapist_name']]]);
    }
    jsonResponse(['authenticated' => false]);
}

// ------------------------------
// ПАЦИЕНТЫ
// ------------------------------

// GET один пациент (по id)
if ($endpoint === 'patients' && isset($request[1]) && is_numeric($request[1]) && $method === 'GET') {
    $therapist_id = checkAuth();
    $patient = new Patient($db);
    $patient->id = (int)$request[1];
    $patient->therapist_id = $therapist_id;

    $data = $patient->getOne();
    if ($data) jsonResponse($data);
    jsonResponse(['error' => 'Пациент не найден'], 404);
}

// GET список пациентов
if ($endpoint === 'patients' && !isset($request[1]) && $method === 'GET') {
    $therapist_id = checkAuth();
    $patient = new Patient($db);
    $patient->therapist_id = $therapist_id;
    $stmt = $patient->getAll();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($patients);
}

// CREATE пациент
if ($endpoint === 'patients' && $method === 'POST') {
    $therapist_id = checkAuth();
    $data = getJsonInput();
    $patient = new Patient($db);
    $patient->therapist_id = $therapist_id;
    // Валидация полей
    $missing = validateRequiredFields($data, ['name','age','gender']);
    if (!empty($missing)) jsonResponse(['error' => 'Отсутствуют требуемые поля', 'fields' => $missing], 400);

    // Дополнительная валидация
    if (!is_numeric($data['age']) || (int)$data['age'] < 0) {
        jsonResponse(['error' => 'Возраст должен быть неотрицательным числом'], 400);
    }

    $patient->name = $data['name'];
    $patient->age = (int)$data['age'];
    $patient->gender = $data['gender'];

    if ($patient->create()) {
        jsonResponse(['success' => true, 'id' => $patient->id], 201);
    }
    jsonResponse(['error' => 'Не удалось сохранить пациента'], 400);
}

// UPDATE пациент
if ($endpoint === 'patients' && isset($request[1]) && is_numeric($request[1]) && $method === 'PUT') {
    $therapist_id = checkAuth();
    $data = getJsonInput();
    $patient = new Patient($db);
    $patient->id = (int)$request[1];
    $patient->therapist_id = $therapist_id;
    // Валидация полей
    $missing = validateRequiredFields($data, ['name','age','gender']);
    if (!empty($missing)) jsonResponse(['error' => 'Отсутствуют требуемые поля', 'fields' => $missing], 400);

    if (!is_numeric($data['age']) || (int)$data['age'] < 0) {
        jsonResponse(['error' => 'Возраст должен быть неотрицательным числом'], 400);
    }

    $patient->name = $data['name'];
    $patient->age = (int)$data['age'];
    $patient->gender = $data['gender'];

    if ($patient->update()) jsonResponse(['success' => true]);
    jsonResponse(['error' => 'Не удалось обновить пациента'], 400);
}

// DELETE пациент
if ($endpoint === 'patients' && isset($request[1]) && is_numeric($request[1]) && $method === 'DELETE') {
    $therapist_id = checkAuth();
    $patient = new Patient($db);
    $patient->id = (int)$request[1];
    $patient->therapist_id = $therapist_id;

    if ($patient->delete()) jsonResponse(['success' => true]);
    jsonResponse(['error' => 'Не удалось удалить пациента'], 400);
}

// ------------------------------
// СЕССИИ
// ------------------------------

// GET сессии по id пациента
if ($endpoint === 'sessions' && !isset($request[1]) && $method === 'GET') {
    checkAuth();
    $patient_id = null;
    if (isset($_GET['patient_id'])) {
        $patient_id = $_GET['patient_id'];
    }
    if (!$patient_id) jsonResponse(['error' => 'Требуется patient_id'], 400);

    $session = new Session($db);
    $session->patient_id = (int)$patient_id;
    $stmt = $session->getByPatient();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($sessions);
}

// CREATE session
if ($endpoint === 'sessions' && $method === 'POST') {
    checkAuth();
    $data = getJsonInput();
    $session = new Session($db);
    $session->patient_id = 0;
    $session->sud_score = 0;
    $session->quality_score = 0;
    $session->comments = '';
    $session->session_date = date('Y-m-d H:i:s');
    // Валидация полей
    $missing = validateRequiredFields($data, ['patient_id','sud_score','quality_score']);
    if (!empty($missing)) jsonResponse(['error' => 'Отсутствуют требуемые поля', 'fields' => $missing], 400);

    if (!is_numeric($data['patient_id']) || (int)$data['patient_id'] <= 0) {
        jsonResponse(['error' => 'patient_id должен быть положительным целым числом'], 400);
    }
    if (!is_numeric($data['sud_score']) || !is_numeric($data['quality_score'])) {
        jsonResponse(['error' => 'sud_score и quality_score должны быть числами'], 400);
    }

    $session->patient_id = (int)$data['patient_id'];
    $session->sud_score = $data['sud_score'];
    $session->quality_score = $data['quality_score'];
    $session->comments = isset($data['comments']) ? $data['comments'] : '';
    $session->session_date = isset($data['session_date']) ? $data['session_date'] : date('Y-m-d H:i:s');

    if ($session->create()) jsonResponse(['success' => true, 'id' => $session->id], 201);
    jsonResponse(['error' => 'Не удалось сохранить сессию'], 400);
}

// UPDATE session
if ($endpoint === 'sessions' && isset($request[1]) && is_numeric($request[1]) && $method === 'PUT') {
    checkAuth();
    $data = getJsonInput();
    $session = new Session($db);
    $session->id = (int)$request[1];
    $missing = validateRequiredFields($data, ['sud_score','quality_score']);
    if (!empty($missing)) jsonResponse(['error' => 'Отсутствуют требуемые поля', 'fields' => $missing], 400);

    if (!is_numeric($data['sud_score']) || !is_numeric($data['quality_score'])) {
        jsonResponse(['error' => 'sud_score и quality_score должны быть числами'], 400);
    }

    $session->sud_score = $data['sud_score'];
    $session->quality_score = $data['quality_score'];
    $session->comments = isset($data['comments']) ? $data['comments'] : '';
    $session->session_date = isset($data['session_date']) ? $data['session_date'] : date('Y-m-d H:i:s');

    if ($session->update()) jsonResponse(['success' => true]);
    jsonResponse(['error' => 'Не удалось обновить сессию'], 400);
}

// DELETE session
if ($endpoint === 'sessions' && isset($request[1]) && is_numeric($request[1]) && $method === 'DELETE') {
    checkAuth();
    $session = new Session($db);
    $session->id = (int)$request[1];

    if ($session->delete()) jsonResponse(['success' => true]);
    jsonResponse(['error' => 'Не удалось удалить сессию'], 400);
}

// Если ничего не совпало — 404
jsonResponse(['error' => 'Конечная точка не найдена', 'debug' => ['endpoint' => $endpoint, 'request' => $request, 'method' => $method]], 404);
?>