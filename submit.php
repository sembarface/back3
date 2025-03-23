<?php
header("Content-Type: text/html; charset=UTF-8");

$host = 'localhost';
$dbname = 'u68684';
$user = 'u68684';
$password = '1432781'; // Здесь должен быть твой пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Включаем обработку ошибок
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Устанавливаем режим выборки
    ]);
    echo "✅ Подключение к базе успешно!";
} catch (PDOException $e) {
    die("❌ Ошибка подключения к БД: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $birthdate = $_POST['dob'];
    $gender = $_POST['gender'];
    $bio = trim($_POST['bio']);
    $contract = isset($_POST['contract']) ? 1 : 0;
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];

    // Валидация данных
    if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s-]{1,150}$/u", $name)) {
        die("Ошибка: Некорректное имя.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Ошибка: Некорректный email.");
    }
    if (!preg_match("/^\+?[0-9]{7,15}$/", $phone)) {
        die("Ошибка: Некорректный телефон.");
    }

    // Вставка данных в таблицу application
    $stmt = $pdo->prepare("INSERT INTO application (name, phone, email, birthdate, gender, bio, contract) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $email, $birthdate, $gender, $bio, $contract]);

    // Получаем ID последней вставленной записи
    $application_id = $pdo->lastInsertId();

    // Вставка данных в таблицу связей application_languages
    $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $language) {
        $stmt->execute([$application_id, $language]);
    }

    echo "Данные успешно сохранены!";
    
}
?>
