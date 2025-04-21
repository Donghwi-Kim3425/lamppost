<!DOCTYPE html>
<html lang = 'ko'>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인</title>
    <style>
        /* 전체 스타일 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Noto Sans KR', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
	
        a {
            text-decoration-line: none;
        }

        /* 헤더 영역 */
        .header {
            background-color: #3b5998;
            color: white;
            padding: 10px 15px;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title {
            flex-grow: 1;
            text-align: center;
        }


        .header-title a {
            text-decoration: none;
            color: white;
            display: inline-block;
        }

        /* 게시판 제목 스타일 */
        .header-title h1 {
            font-size: 36px;
            color: white;
            margin-bottom: 0;
            display: inline;
        }

        /* 메인 콘텐츠 영역 */
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 500px; /* 폼 너비 제한 */
            margin: 30px auto; /* 가운데 정렬 */
        }

        .content h1 {
            font-size: 30px;
            margin-bottom: 20px;
            text-align: center;
            color: #3b5998;
        }

        .content div {
            margin-bottom: 15px;
        }

        .content label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .content input[type="text"],
        .content input[type="password"] {
            width: calc(100% - 12px);
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .content button[type="submit"] {
            background-color: #3b5998;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .content button[type="submit"]:hover {
            background-color: #2c4882;
        }

        .content p {
            margin-top: 15px;
            text-align: center;
        }

        .content p a {
            color: #007bff;
            text-decoration: none;
        }

        .content p a:hover {
            text-decoration: underline;
        }

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div class="header-title">
            <a href="index.php">
                <h1>게시판</h1>
            </a>
        </div>
    </div>

    <div class="content">
        <h1>로그인</h1>
        <?php if (isset($_GET['error'])): ?>
            <p style="color: red;"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <form action="login_process.php" method="post">
            <div>
                <label for="username">아이디:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div>
                <label for="password">비밀번호:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">로그인</button>
            <p>아직 회원이 아니신가요? <a href="register.php">회원가입</a></p>
        </form>
    </div>
</body>
</html>
