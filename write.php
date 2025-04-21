<?php
session_start();

// 로그인 상태 확인 (로그인되지 않았다면 로그인 페이지로 이동)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 글 쓰기</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }

        h1 {
            color: #3b5998;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: calc(100% - 12px);
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        textarea {
            min-height: 300px;
        }

        button[type="submit"] {
            background-color: #3b5998;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #2c4882;
        }

        .back-link {
            display: block;
            margin-top: 15px;
            color: #3b5998;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1>새 글 쓰기</h1>
    <form action="write_process.php" method="post" enctype="multipart/form-data">
        <div>
            <label for="title">제목:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div>
            <label for="content">내용:</label>
            <textarea id="content" name="content" required></textarea>
        </div>
        <div>
            <label for="files">첨부 파일 (최대 5개, 이미지/GIF):</label>
            <input type="file" id="files" name="files[]" multiple accept="image/jpeg, image/png, image/gif">
            <small>최대 5개의 이미지 또는 GIF 파일을 첨부할 수 있습니다.</small>
        </div>
        <button type="submit">저장</button>
    </form>
    <a href="index.php" class="back-link">목록으로 돌아가기</a>
</div>
</body>
</html>