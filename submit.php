<?php
// Устанавливаем соединение с базой данных
$host = 'localhost';
$dbname = 'u68684';  // Замени на имя своей базы данных
$username = 'u68684'; // Замени на твое имя пользователя
$password = '1432781'; // Замени на твой пароль
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

// Пытаемся подключиться к базе данных
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Ошибка подключения к БД: " . $e->getMessage();
    exit;
}

// Проверяем, был ли отправлен POST-запрос
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];  // Мужской/Женский/Другое
    $languages = $_POST['languages'];  // Массив языков
    $biography = $_POST['biography'];
    $contract = isset($_POST['contract_accepted']) ? 1 : 0;

    // Валидация данных (пример для ФИО)
    if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s]+$/u", $name)) {
        echo "Ошибка: Неверное ФИО.";
        exit;
    }

    // Получаем ID выбранного языка (например, 'C++')
    $language_id = [];
    foreach ($languages as $language) {
        $stmt = $pdo->prepare("SELECT id FROM languages WHERE name = :name");
        $stmt->execute(['name' => $language]);
        $id = $stmt->fetchColumn();
        if ($id) {
            $language_id[] = $id; // Собираем ID выбранных языков
        }
    }

    // Вставляем заявку в таблицу application
    try {
        $stmt = $pdo->prepare("INSERT INTO application (name, phone, email, birthdate, gender, biography, contract) 
                               VALUES (:name, :phone, :email, :birthdate, :gender, :biography, :contract)");
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'biography' => $biography,
            'contract' => $contract
        ]);

        // Получаем ID последней вставленной заявки
        $application_id = $pdo->lastInsertId();

        // Вставляем языки программирования в таблицу связей
        foreach ($language_id as $lang_id) {
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) 
                                   VALUES (:application_id, :language_id)");
            $stmt->execute([
                'application_id' => $application_id,
                'language_id' => $lang_id
            ]);
        }

        echo "Данные успешно сохранены!";
    } catch (PDOException $e) {
        echo "Ошибка при вставке данных: " . $e->getMessage();
    }
}
?>
