<?php
$pdo = require_once __DIR__ . '/db_config.php';

// 사용자가 폼을 통해 아이디와 비밀번호를 제출했는지 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Prepared Statement 생성
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");

        // 파라미터 바인딩
        $stmt->bindParam(':username', $username);

        // 쿼리 실행
        $stmt->execute();

        // 결과 가져오기
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 사용자가 존재하는지 확인하고 비밀번호 검증
        if ($user && password_verify($password, $user['password'])) {
            // 로그인 성공 처리 (세션 시작 등)
            session_start();
            $_SESSION['user_id'] = $user['id']; // 예시: 사용자 ID를 세션에 저장
            $_SESSION['username'] = $user['username'];
            header('Location: index.php'); // 로그인 후 이동할 페이지
            exit;
        } else {
            // 로그인 실패 처리 (에러 메시지 표시 등)
            $error = "아이디 또는 비밀번호가 일치하지 않습니다.";
            header('Location: login.php?error=' . urlencode($error)); // 로그인 페이지로 다시 이동하며 에러 메시지 전달
            exit;
        }

    } catch (PDOException $e) {
        die("데이터베이스 오류: " . $e->getMessage());
    }
} else {
    // 폼이 제대로 제출되지 않은 경우 처리
    header('Location: login.php');
    exit;
}
?>
