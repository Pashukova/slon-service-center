<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name']);
    $phone = trim($data['phone']);
    $email = trim($data['email']);
    $service = trim($data['service']);
    $date = $data['date'];
    $time = $data['time'];
    $message = trim($data['message']);
    
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
    
    if (empty($service)) {
        $errors['service'] = 'Выберите услугу';
    }
    
    if (empty($date)) {
        $errors['date'] = 'Выберите дату';
    }
    
    if (empty($time)) {
        $errors['time'] = 'Выберите время';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    try {
        $conn = getDBConnection();
        
        // Получаем ID услуги
        $stmt = $conn->prepare("SELECT id FROM services WHERE title = ?");
        $stmt->execute([$service]);
        $serviceData = $stmt->fetch(PDO::FETCH_ASSOC);
        $serviceId = $serviceData ? $serviceData['id'] : null;
        
        // Получаем ID пользователя, если он авторизован
        $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
        
        // Создаем запись
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, name, phone, email, booking_date, booking_time, problem_description) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $serviceId, $name, $phone, $email, $date, $time, $message]);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Запись успешно создана!']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
}
?>