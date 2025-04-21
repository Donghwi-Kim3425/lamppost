<?php
$pdo = require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // 비밀번호 해싱

    // 아이디 중복 확인
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt_check->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_check->execute();
    if ($stmt_check->fetchColumn() > 0) {
        echo "<script>alert('이미 사용 중인 아이디입니다.'); history.back();</script>";
        exit();
    }

    $stmt_insert = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $stmt_insert->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_insert->bindParam(':password', $password, PDO::PARAM_STR);

    if ($stmt_insert->execute()) {
        echo "<script>alert('회원가입에 성공했습니다.'); location.href='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('회원가입에 실패했습니다.'); history.back();</script>";
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>