<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // 게시글 소유자 확인 (보안 중요)
    $stmt_check = $pdo->prepare("SELECT user_id, file_path FROM posts WHERE id = :id");
    $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    $post = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$post || (int)$post['user_id'] !== (int)$_SESSION['user_id']) {
        echo "<script>alert('삭제 권한이 없습니다.'); history.back();</script>";
        exit();
    }

    // 첨부 파일 삭제
    if (!empty($post['file_path']) && file_exists($post['file_path'])) {
        unlink($post['file_path']);
    }

    $stmt_delete = $pdo->prepare("DELETE FROM posts WHERE id = :id");
    $stmt_delete->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt_delete->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('게시글 삭제에 실패했습니다.'); history.back();</script>";
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
