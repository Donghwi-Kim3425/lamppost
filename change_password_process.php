<?php
session_start();
// 로그인 상태 확인 (로그인되지 않았다면 로그인 페이지로 이동)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 폼에서 전송된 데이터 받기
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_new_password = isset($_POST['confirm_new_password']) ? $_POST['confirm_new_password'] : '';

    // 유효성 검사
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        echo "<script>alert('모든 필드를 입력해주세요.'); window.location.href = 'mypage.php';</script>";
        exit;
    }

    if ($new_password !== $confirm_new_password) {
        echo "<script>alert('새 비밀번호와 새 비밀번호 확인이 일치하지 않습니다.'); window.location.href = 'mypage.php';</script>";
        exit;
    }

    // 새 비밀번호 길이 확인
    if (strlen($new_password) < 6) {
        echo "<script>alert('새 비밀번호는 최소 6자 이상이어야 합니다.'); window.location.href = 'mypage.php';</script>";
        exit;
    }

    try {
        // 현재 사용자의 비밀번호 가져오기
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_password, $user['password'])) {
            // 현재 비밀번호가 일치하면 새 비밀번호 해싱하여 저장
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(':password', $hashed_new_password);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo "<script>alert('비밀번호가 성공적으로 변경되었습니다.'); window.location.href = 'mypage.php';</script>";
                exit;
            } else {
                echo "<script>alert('비밀번호 변경에 실패했습니다.'); window.location.href = 'mypage.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('현재 비밀번호가 일치하지 않습니다.'); window.location.href = 'mypage.php';</script>";
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