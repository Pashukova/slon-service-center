<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email']);
    $password = $data['password'];
    
    // Валидация
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            $errors['email'] = 'Пользователь с таким email не найден';
            http_response_code(401);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($password, $user['password'])) {
            $errors['password'] = 'Неверный пароль';
            http_response_code(401);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Сохраняем пользователя в сессии
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone']
        ];
        
        echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
}
?>