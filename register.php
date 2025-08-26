<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name']);
    $phone = trim($data['phone']);
    $email = trim($data['email']);
    $password = $data['password'];
    $confirmPassword = $data['confirmPassword'];
    
    // Валидация
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = 'Введите имя';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Введите телефон';
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'Неверный формат телефона';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный формат email';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Пароли не совпадают';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    try {
        $conn = getDBConnection();
        
        // Проверка существующего email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $errors['email'] = 'Этот email уже зарегистрирован';
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Хеширование пароля
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Создание пользователя
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $hashedPassword]);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Регистрация прошла успешно!']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
}
?>