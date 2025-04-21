<?php
session_start();

// 로그인 상태 확인 (로그인되지 않았다면 로그인 페이지로 이동)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require_once __DIR__ . '/db_config.php';

$user_id = $_SESSION['user_id']; // 로그인된 사용자 ID (세션에서 가져옴)
