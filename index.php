<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php'; // DB 연결만 받아옴

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
        error_log("index.php - 로그인 사용자 프로필 이미지 조회 오류: " . $e->getMessage());
    }
}

$posts = [];
$searchQuery = $_GET['query'] ?? null;

if ($searchQuery) {
    if (trim($searchQuery) !== '') {
        try {
            $stmt = $pdo->prepare("
                SELECT p.id, p.title, COALESCE(u.username, '탈퇴한 사용자') AS username, u.profile_image, p.created_at
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.title LIKE :query OR p.content LIKE :query
                ORDER BY p.id DESC
            ");
            $stmt->bindValue(':query', '%' . $searchQuery . '%', PDO::PARAM_STR);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("index.php - 게시글 검색 오류: " . $e->getMessage());
        }
    } 

} else {
    try {
        $stmt = $pdo->query("
            SELECT p.id, p.title, COALESCE(u.username, '탈퇴한 사용자') AS username, u.profile_image, p.created_at
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.id DESC
        ");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("index.php - 게시글 목록 조회 오류: " . $e->getMessage());
        // echo "<p>게시글을 불러오는 중 오류가 발생했습니다.</p>";
    }
}

// 기본 프로필 이미지 경로 설정
$defaultProfileImage = "https://i.pinimg.com/564x/3b/73/a1/3b73a13983f88f8b84e130bb3fb29e17.jpg"; // 기본 이미지 URL
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판</title>
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
        a:link { color :#4c72c2; }
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

        /* 메인 콘텐츠 영역 */
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            min-height: 400px;
        }

        /* 게시글 목록 */
        .post-list {
            list-style-type: none;
        }

        .post-item {
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .post-item:hover {
            background-color: #f9f9f9;
        }

        .post-title {
            flex-grow: 1;
        }

        .post-title a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }

        .post-title a:hover {
            color: #3b5998;
            text-decoration: underline;
        }

        .post-info {
            color: #777;
            font-size: 14px;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .post-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .post-info {
                margin-top: 5px;
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
                <input type="text" name="query" placeholder="검색어를 입력하세요" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">검색</button>
            </form>
        </div>
    </div>
</div>

<div class="content">
    <?php if ($searchQuery): ?>
        <h2>검색 결과: "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
    <?php endif; ?>
    <ul class="post-list">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <li class="post-item">
                    <div class="post-title">
                        <a href="view.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                    </div>
                    <div class="post-info">
                        <?php echo htmlspecialchars($post['username']); ?> |
                        <?php echo $post['created_at']; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if ($searchQuery): ?>
                <p>검색 결과가 없습니다.</p>
            <?php else: ?>
                <p>등록된 게시글이 없습니다.</p>
            <?php endif; ?>
        <?php endif; ?>
    </ul>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.querySelector('.search-container form');
        const searchInput = searchForm.querySelector('input[name="query"]');

        searchForm.addEventListener('submit', function(event) {
            if (searchInput.value.trim() === '') {
                alert('검색어를 입력해주세요.');
                event.preventDefault(); // 폼 제출 방지
            }
        });
    });
</script>
</body>
</html>
