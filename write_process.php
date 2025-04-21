<?php
session_start();

// 로그인 상태 확인
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($content)) {
        echo "<script>alert('제목과 내용을 모두 입력해주세요.'); window.location.href = 'write.php';</script>";
        exit;
    }

    // 파일 업로드 처리
    $uploadedFiles = $_FILES['files'] ?? null;
    $filePaths = [];
    $uploadDir = 'uploads/'; // 업로드할 디렉토리 (미리 생성해야 함)
    $maxFiles = 5;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if ($uploadedFiles && is_array($uploadedFiles['name'])) {
        $numFiles = count($uploadedFiles['name']);
        if ($numFiles > $maxFiles) {
            echo "<script>alert('최대 " . $maxFiles . "개의 파일만 첨부할 수 있습니다.'); window.location.href = 'write.php';</script>";
            exit;
        }

        for ($i = 0; $i < $numFiles; $i++) {
            if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                $fileTmpPath = $uploadedFiles['tmp_name'][$i];
                $fileName = basename($uploadedFiles['name'][$i]);
                $fileType = $uploadedFiles['type'][$i];
                $fileSize = $uploadedFiles['size'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = uniqid('post_') . '.' . $fileExtension;
                $destinationPath = $uploadDir . $newFileName;

                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true); // 디렉토리 없으면 생성
                    }
                    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                        $filePaths[] = $destinationPath;
                    } else {
                        echo "<script>alert('" . htmlspecialchars($fileName) . " 파일 업로드에 실패했습니다.'); window.location.href = 'write.php';</script>";
                        exit;
                    }
                } else {
                    echo "<script>alert('" . htmlspecialchars($fileName) . " 파일 형식이 올바르지 않거나 용량이 너무 큽니다. (허용 형식: jpg, png, gif, 최대 용량: 5MB)'); window.location.href = 'write.php';</script>";
                    exit;
                }
            } elseif ($uploadedFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                echo "<script>alert('파일 업로드 중 오류가 발생했습니다.'); window.location.href = 'write.php';</script>";
                exit;
            }
        }
    }

    $filePathString = implode(',', $filePaths); // 여러 파일 경로를 쉼표로 연결

    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, created_at, file_path) VALUES (:user_id, :title, :content, NOW(), :file_path)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $filePathString, PDO::PARAM_STR);
        $stmt->execute();

        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        die("데이터베이스 오류: " . $e->getMessage());
    }
} else {
    header('Location: index.php');
    exit;
}
?>