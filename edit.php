<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("게시물을 찾을 수 없거나 수정 권한이 없습니다.");
    }
} else {
    die("잘못된 접근입니다.");
}

// 현재 로그인한 사용자 ID 세션에서 가져오기
$loggedInUserId = $_SESSION['user_id'] ?? null;
$loggedInUserProfileImage = null;
if ($loggedInUserId) {
    try {
        $stmtLoggedInUser = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
        $stmtLoggedInUser->bindParam(':id', $loggedInUserId, PDO::PARAM_INT);
        $stmtLoggedInUser->execute();
        $loggedInUser = $stmtLoggedInUser->fetch(PDO::FETCH_ASSOC);

        if ($loggedInUser && $loggedInUser['profile_image']) {
            $loggedInUserProfileImage = $loggedInUser['profile_image'];
        }
    } catch (PDOException $e) {
        error_log("edit.php - 로그인 사용자 프로필 이미지 조회 오류: " . $e->getMessage());
    }
}

// 기본 프로필 이미지 경로 설정
$defaultProfileImage = "https://i.pinimg.com/564x/3b/73/a1/3b73a13983f88f8b84e130bb3fb29e17.jpg";
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 수정</title>
    <style>
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
        }

        a {
            text-decoration: none;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .edit-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .edit-header h1 {
            color: #3b5998;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .edit-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea.form-control {
            min-height: 250px;
            resize: vertical;
        }

        .file-info {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .file-preview {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .file-preview img {
            max-width: 150px;
            max-height: 100px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #3b5998;
            color: white;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .delete-checkbox {
            margin-top: 10px;
        }

        /* 헤더 영역*/
        .header {
            background-color: #3b5998;
            color: white;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title a {
            text-decoration: none;
            color: white;
        }

        .header-title h1 {
            font-size: 36px;
            color: white;
            margin-bottom: 0;
        }

        .header-mypage a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }

        .profile-image-header {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
        }

        .profile-image-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mypage-text {
            font-weight: bold;
        }

        /*네비게이션 영역*/
        .nav {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-left a {
            color: #3b5998;
            margin-right: 15px;
        }

        .search-container input[type="text"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 5px;
        }

        .search-container button {
            background-color: #74aaf7;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="header">
    <a href="index.php" class="header-title">
        <h1>게시판</h1>
    </a>
    <div class="header-mypage">
        <a href="mypage.php">
            <div class="profile-image-header">
                <?php if ($loggedInUserProfileImage): ?>
                    <img src="<?php echo htmlspecialchars($loggedInUserProfileImage); ?>" alt="프로필 사진">
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars($defaultProfileImage); ?>" alt="기본 프로필 사진">
                <?php endif; ?>
            </div>
            <span class="mypage-text">Mypage</span>
        </a>
    </div>
</div>

<div class="nav">
    <div class="nav-left">
        <a href="write.php">새 글 쓰기</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">로그아웃</a>
        <?php else: ?>
            <a href="register.php">회원가입</a>
            <a href="login.php">로그인</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <div class="search-container">
            <form action="index.php" method="get">
                <input type="text" name="query" placeholder="검색어를 입력하세요">
                <button type="submit">검색</button>
            </form>
        </div>
    </div>
</div>

<div class="container">
    <div class="edit-header">
        <h1>게시글 수정</h1>
    </div>

    <form action="edit_process.php" method="post" enctype="multipart/form-data" class="edit-form">
        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">

        <div class="form-group">
            <label for="title">제목:</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="content">내용:</label>
            <textarea id="content" name="content" class="form-control" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="new_file">첨부 파일 (최대 5개, 이미지/GIF):</label>
            <input type="file" id="new_file" name="files[]" class="form-control" multiple accept="image/jpeg, image/png, image/gif">
            <div class="file-info">최대 5개의 이미지 또는 GIF 파일을 첨부할 수 있습니다.</div>

            <?php if (!empty($post['file_path'])): ?>
                <div class="file-preview">
                    <?php
                    $filePaths = explode(',', $post['file_path']);
                    foreach ($filePaths as $path):
                        if (trim($path) !== ''):
                            ?>
                            <img src="<?php echo htmlspecialchars(trim($path)); ?>" alt="첨부 이미지">
                        <?php
                        endif;
                    endforeach;
                    ?>
                </div>
                <div class="delete-checkbox">
                    <input type="checkbox" id="delete_file" name="delete_file" value="1">
                    <label for="delete_file">기존 첨부파일 삭제</label>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="location.href='view.php?id=<?php echo $post['id']; ?>'">취소</button>
            <button type="submit" class="btn btn-primary">수정 완료</button>
        </div>
    </form>
</div>
</body>
</html>