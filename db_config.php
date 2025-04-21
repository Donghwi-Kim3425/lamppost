<?php
$host = 'localhost';
$dbname = 'board';
$username = 'board_user';
$password = '7k]FR+8xd866';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Prepared Statement를 강제로 사용하도록 설정 (보안 강화)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

return $pdo;
?>
