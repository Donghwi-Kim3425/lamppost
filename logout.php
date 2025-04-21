<?php
session_start();

// 세션 변수 삭제
unset($_SESSION['user_id']);
unset($_SESSION['username']);

// 세션 파괴
// session_destroy();

// 홈페이지로 리다이렉트
header('Location: index.php');
exit;
?>