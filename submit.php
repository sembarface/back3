<?php
// Устанавливаем соединение с базой данных
$host = 'localhost';
$dbname = 'u68684';
$username = 'u68684';
$password = '1432781';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Проверяем POST-запрос
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Валидация данных
    $errors = [];
    
    // ФИО (только буквы и пробелы, до 150 символов)
    if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s]{1,150}$/u", $_POST['name'])) {
        $errors[] = "Неверный формат ФИО";
    }
    
    // Телефон (простейшая проверка)
    if (!preg_match("/^\+?\d{1,3}[-\s]?\(?\d{3}\)?[-\s]?\d{3}[-\s]?\d{2}[-\s]?\d{2}$/", $_POST['phone'])) {
        $errors[] = "Неверный формат телефона";
    }
    
    // Email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Неверный формат email";
    }
    
    // Дата рождения (не будущее время)
    if (strtotime($_POST['birthdate']) > time()) {
        $errors[] = "Дата рождения не может быть в будущем";
    }
    
    // Пол
    if (!in_array($_POST['gender'], ['male', 'female', 'other'])) {
        $errors[] = "Неверно указан пол";
    }
    
    // Языки программирования
    $validLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala'];
    $selectedLanguages = $_POST['languages'] ?? [];
    
    if (empty($selectedLanguages)) {
        $errors[] = "Выберите хотя бы один язык программирования";
    } else {
        foreach ($selectedLanguages as $lang) {
            if (!in_array($lang, $validLanguages)) {
                $errors[] = "Выбран недопустимый язык программирования";
                break;
            }
        }
    }
    
    // Биография
    if (empty(trim($_POST['bio']))) {
        $errors[] = "Заполните биографию";
    }
    
    // Чекбокс
    if (!isset($_POST['contract_accepted'])) {
        $errors[] = "Необходимо принять условия контракта";
    }
    
    // Если есть ошибки - выводим
    if (!empty($errors)) {
        header('Content-Type: text/html; charset=utf-8');
        echo "<h2>Ошибки:</h2><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        exit;
    }
    
    // Если ошибок нет - сохраняем в БД
    try {
        $pdo->beginTransaction();
        
        // 1. Сохраняем основную информацию
        $stmt = $pdo->prepare("INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract_accepted) 
                              VALUES (:name, :phone, :email, :birthdate, :gender, :bio, :contract)");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':birthdate' => $_POST['birthdate'],
            ':gender' => $_POST['gender'],
            ':bio' => $_POST['bio'],
            ':contract' => isset($_POST['contract_accepted']) ? 1 : 0
        ]);
        
        $applicationId = $pdo->lastInsertId();
        
        // 2. Сохраняем языки программирования
        // Сначала убедимся, что таблица languages существует
        $pdo->exec("CREATE TABLE IF NOT EXISTS languages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE
        )");
        
        // Создаем таблицу связей, если ее нет
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_languages (
            application_id INT UNSIGNED NOT NULL,
            language_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (application_id, language_id),
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        )");
        
        // Для каждого выбранного языка
        foreach ($selectedLanguages as $langName) {
            // Проверяем есть ли язык в БД
            $stmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
            $stmt->execute([$langName]);
            $langId = $stmt->fetchColumn();
            
            // Если нет - добавляем
            if (!$langId) {
                $stmt = $pdo->prepare("INSERT INTO languages (name) VALUES (?)");
                $stmt->execute([$langName]);
                $langId = $pdo->lastInsertId();
            }
            
            // Добавляем связь
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            $stmt->execute([$applicationId, $langId]);
        }
        
        $pdo->commit();
        
        // Перенаправляем с сообщением об успехе
        header("Location: index.html?success=1");
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Ошибка при сохранении данных: " . $e->getMessage());
    }
}
?>
