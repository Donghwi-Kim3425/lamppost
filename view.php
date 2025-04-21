<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("
        SELECT p.*, COALESCE(u.username, '탈퇴한 사용자') AS username
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = :id
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("게시물을 찾을 수 없습니다.");
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
        error_log("view.php - 로그인 사용자 프로필 이미지 조회 오류: " . $e->getMessage());
    }
}

// 기본 프로필 이미지 경로 설정
$defaultProfileImage = "https://i.pinimg.com/564x/3b/73/a1/3b73a13983f88f8b84e130bb3fb29e17.jpg";

// 로그인한 유저와 글 작성자 비교
$is_author = isset($_SESSION['user_id']) && $post && $_SESSION['user_id'] == $post['user_id'];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
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

        a:link { color #4c72c2; }
        a:visited { color:#4c72c2; }

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

        .header-mypage {
            flex-shrink: 0;
        }

        .header-mypage a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }

        .profile-image {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mypage-text {
            font-weight: bold;
        }

        /* 내비게이션 영역 */
        .nav {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-left a {
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-left a:hover {
            background-color: #f0f2f5;
        }

        .nav-right {
            display: flex;
            align-items: center;
        }

        .search-container {
            display: flex;
            align-items: center;
        }

        .search-container input[type="text"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 5px;
            font-size: 16px;
        }

        .search-container button {
            background-color: #74aaf7;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .search-container button:hover {
            background-color: #4383df;
        }

        /* 게시글 내용 스타일 */
        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .post-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .post-title {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .post-meta {
            display: flex;
            justify-content: space-between;
            color: #777;
            font-size: 14px;
        }

        .post-content {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .post-images {
            text-align: center;
            padding: 20px;
        }

        .post-images img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .post-actions {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .left-action {
        }

        .right-action {
            display: flex;
            gap: 10px;
        }


        .btn {
            padding: 8px 16px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: black;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #e4e4e4;
        }

        @media (max-width: 768px) {
            .post-meta {
                flex-direction: column;
            }

            .post-actions {
                flex-direction: column;
                gap: 10px;
            }
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
        <div class="header-mypage">
            <a href="mypage.php">
                <div class="profile-image">
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
    <div class="post-header">
        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="post-meta">
            <span>작성자: <?php echo htmlspecialchars($post['username']); ?></span>
            <span>작성일: <?php echo $post['created_at']; ?></span>
        </div>
    </div>

    <div class="post-content">
        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
    </div>

    <?php if (!empty($post['file_path'])): ?>
        <div class="post-images">
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
    <?php endif; ?>

    <div class="post-actions">
        <div class="left-action">
            <a href="index.php" class="btn">목록으로</a>
        </div>
        <div class="right-action">
            <?php if ($is_author): ?>
                <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn">수정</a>
                <a href="delete_process.php?id=<?php echo $post['id']; ?>" class="btn" onclick="return confirm('정말로 삭제하시겠습니까?');">삭제</a>
            <?php endif; ?>
        </div>
    </div>


</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.querySelector('.search-container form');
        const searchInput = searchForm.querySelector('input[name="query"]');

        searchForm.addEventListener('submit', function(event) {
            if (searchInput.value.trim() === '') {
                alert('검색어를 입력해주세요.');
                event.preventDefault();
            }
        });
    });
</script>
</body>
</html>