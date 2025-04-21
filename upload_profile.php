<?php
session_start();

// 로그인 상태 확인 (로그인되지 않았다면 로그인 페이지로 이동)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require_once __DIR__ . '/db_config.php';

$user_id = $_SESSION['user_id']; // 로그인된 사용자 ID (세션에서 가져옴)

// 업로드된 파일 검사
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['profile_image']['tmp_name'];
    $file_name = basename($_FILES['profile_image']['name']);
    $upload_dir = 'uploads/';

    // 확장자 검사 (보안)
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_extensions)) {
        echo "<script>alert('허용되지 않은 파일 형식입니다.'); history.back();</script>";
        exit();
    }

    // 새 파일명 지정 (충돌 방지)
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
    $destination = $upload_dir . $new_filename;

    // 파일 이동
    if (move_uploaded_file($file_tmp, $destination)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :id");
            $stmt->bindParam(':profile_image', $destination);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo "<script>alert('프로필 사진이 성공적으로 업로드되었습니다.'); window.location.href = 'mypage.php';</script>";
                exit;
            } else {
                echo "<script>alert('DB 업데이트 실패.'); window.location.href = 'mypage.php';</script>";
                exit;
            }

        } catch (PDOException $e) {
            die("데이터베이스 오류: " . $e->getMessage());
        }
    } else {
        echo "<script>alert('파일 업로드에 실패했습니다.'); window.location.href = 'mypage.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('업로드할 파일이 선택되지 않았습니다.'); window.location.href = 'mypage.php';</script>";
    exit;
}
?>