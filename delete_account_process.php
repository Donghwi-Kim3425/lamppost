<?php
session_start();

// 로그인 상태 확인
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require_once __DIR__ . '/db_config.php';
$loggedInUserId = $_SESSION['user_id'];

try {
    // users 테이블에서 사용자 정보 삭제
    $stmtUsers = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmtUsers->bindParam(':id', $loggedInUserId, PDO::PARAM_INT);
    $stmtUsers->execute();

    session_unset();
    session_destroy();
    echo "<script>alert('성공적으로 탈퇴되었습니다. 작성하신 글은 유지됩니다.'); location.href='index.php';</script>";
    exit;

} catch (PDOException $e) {
    error_log("회원 탈퇴 오류: " . $e->getMessage());
    echo "<script>alert('회원 탈퇴 중 오류가 발생했습니다. 다시 시도해주세요.'); window.history.back();</script>";
    exit;
}
?>