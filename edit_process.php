<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];

    // 게시글 존재 및 수정 권한 확인
    $stmt_check = $pdo->prepare("SELECT file_path FROM posts WHERE id = :id AND user_id = :user_id");
    $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_check->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_check->execute();
    $post = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo "<script>alert('게시물을 찾을 수 없거나 수정 권한이 없습니다.'); history.back();</script>";
        exit();
    }

    $old_file_paths = !empty($post['file_path']) ? explode(',', $post['file_path']) : [];
    $new_file_paths = [];

    // 첨부파일 삭제 체크 처리
    if (isset($_POST['delete_file']) && $_POST['delete_file'] == 1) {
        // 기존 파일 삭제
        foreach ($old_file_paths as $path) {
            $path = trim($path);
            if (!empty($path) && file_exists($path)) {
                unlink($path);
            }
        }
        $old_file_paths = []; // 기존 파일 경로 초기화
    } else {
        $new_file_paths = $old_file_paths; // 기존 파일 유지
    }

    // 새로운 파일 업로드 처리
    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        $uploadDir = 'uploads/';

        // 디렉토리 존재 확인 및 생성
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileCount = count($_FILES['files']['name']);
        $maxFiles = 5;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        for ($i = 0; $i < min($fileCount, $maxFiles); $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['files']['tmp_name'][$i];
                $fileName = $_FILES['files']['name'][$i];
                $fileSize = $_FILES['files']['size'][$i];
                $fileType = $_FILES['files']['type'][$i];

                // 파일 유효성 검사
                if (!in_array($fileType, $allowedTypes)) {
                    echo "<script>alert('허용되지 않는 파일 형식입니다: {$fileName}'); history.back();</script>";
                    exit();
                }

                if ($fileSize > $maxFileSize) {
                    echo "<script>alert('파일 크기가 5MB를 초과합니다: {$fileName}'); history.back();</script>";
                    exit();
                }

                // 고유한 파일명 생성
                $uniqueName = uniqid() . '_' . $fileName;
                $targetPath = $uploadDir . $uniqueName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $new_file_paths[] = $targetPath;
                } else {
                    echo "<script>alert('파일 업로드에 실패했습니다: {$fileName}'); history.back();</script>";
                    exit();
                }
            } elseif ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                echo "<script>alert('파일 업로드 중 오류가 발생했습니다.'); history.back();</script>";
                exit();
            }
        }
    }

    // 파일 경로를 쉼표로 구분된 문자열로 변환
    $file_path_string = !empty($new_file_paths) ? implode(',', $new_file_paths) : null;

    $stmt_update = $pdo->prepare("UPDATE posts SET title = :title, content = :content, file_path = :file_path, updated_at = NOW() WHERE id = :id");
    $stmt_update->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt_update->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt_update->bindParam(':file_path', $file_path_string, PDO::PARAM_STR);
    $stmt_update->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        header("Location: view.php?id=$id");
        exit();
    } else {
        echo "<script>alert('게시글 수정에 실패했습니다.'); history.back();</script>";
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>