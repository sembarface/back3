<?php
header("Content-Type: text/html; charset=UTF-8");

$host = 'localhost';
$dbname = 'u68684';
$user = 'u68684';
$pass = '1432781';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// 🚀 Валидация данных
$errors = [];

if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]{1,150}$/u', $_POST['name'])) {
    $errors[] = "ФИО должно содержать только буквы, пробелы и дефисы (макс. 150 символов).";
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный e-mail.";
}

if (!preg_match('/^\+?[0-9]{7,20}$/', $_POST['phone'])) {
    $errors[] = "Некорректный номер телефона.";
}

if (!in_array($_POST['gender'], ['male', 'female'])) {
    $errors[] = "Некорректный пол.";
}

if (!isset($_POST['contract'])) {
    $errors[] = "Необходимо согласие с контрактом.";
}

if (empty($_POST['languages'])) {
    $errors[] = "Выберите хотя бы один язык программирования.";
}

// Остановка, если есть ошибки
if (!empty($errors)) {
    echo "<b>Ошибки:</b><br>" . implode("<br>", $errors);
    exit;
}

// 🚀 Запись в БД
try {
    $stmt = $pdo->prepare("INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract_accepted) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['name'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['bio'], 1
    ]);

    $applicationId = $pdo->lastInsertId();

    // Запись выбранных языков
    $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($_POST['languages'] as $lang) {
        $stmt->execute([$applicationId, $lang]);
    }

    echo "✅ Данные успешно сохранены!";
} catch (PDOException $e) {
    die("Ошибка записи: " . $e->getMessage());
}
?>
