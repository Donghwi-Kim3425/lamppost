<?php
session_start();

// 로그인 상태 확인 (로그인되지 않았다면 로그인 페이지로 이동)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST 요청으로 데이터가 전송되었는지 확인

    // 자기소개 내용 가져오기
    $bio = isset($_POST['bio']) ? $_POST['bio'] : '';

    // 데이터베이스 업데이트
    try {
        $stmt = $pdo->prepare("UPDATE users SET bio = :bio WHERE id = :id");
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('자기소개가 성공적으로 저장되었습니다.'); window.location.href = 'mypage.php';</script>";
            exit;
        } else {
            echo "<script>alert('자기소개 저장에 실패했습니다.'); window.location.href = 'mypage.php';</script>";
            exit;
        }

    } catch (PDOException $e) {
        die("데이터베이스 오류: " . $e->getMessage());
    }
} else {
    // POST 요청이 아닌 경우 (잘못된 접근)
    header('Location: mypage.php');
    exit;
}
?>