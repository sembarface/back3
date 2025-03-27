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
    
    // [Оставьте вашу валидацию без изменений]
    
    if (!empty($errors)) {
        echo "<h2>Ошибки:</h2><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        exit;
    }
    
    try {
        // Начинаем транзакцию ТОЛЬКО если нет ошибок валидации
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
        
        // 2. Оптимизированная обработка языков
        // Оптимизированная обработка языков
$validLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala'];
$selectedLanguages = array_intersect($_POST['languages'] ?? [], $validLanguages);

if (!empty($selectedLanguages)) {
    // 1. Получаем существующие языки
    $placeholders = rtrim(str_repeat('?,', count($selectedLanguages)), ',');
    $stmt = $pdo->prepare("SELECT id, name FROM languages WHERE name IN ($placeholders)");
    $stmt->execute($selectedLanguages);
    $existingLanguages = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['C' => 2, 'Python' => 5]
    
    // 2. Находим отсутствующие языки
    $missingLanguages = array_diff($selectedLanguages, array_keys($existingLanguages));
    
    // 3. Добавляем только отсутствующие языки (с обработкой возможных ошибок)
    if (!empty($missingLanguages)) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO languages (name) VALUES (?)");
        foreach ($missingLanguages as $lang) {
            $stmt->execute([$lang]);
            // Получаем ID добавленного языка
            if ($stmt->rowCount() > 0) {
                $existingLanguages[$lang] = $pdo->lastInsertId();
            } else {
                // Если язык не добавился (уже существует), получаем его ID
                $stmtSelect = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
                $stmtSelect->execute([$lang]);
                $existingLanguages[$lang] = $stmtSelect->fetchColumn();
            }
        }
    }
    
    // 4. Добавляем связи с заявкой
    $stmt = $pdo->prepare("INSERT IGNORE INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($existingLanguages as $langId) {
        $stmt->execute([$applicationId, $langId]);
    }
}
        
        $pdo->commit();
        
        header("Location: index.html?success=1");
        exit;
        
    } catch (PDOException $e) {
        // Откатываем ТОЛЬКО если транзакция была начата
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Ошибка при сохранении данных: " . $e->getMessage());
    }
}
?>
