<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php'; // DB 연결

// --- 로그인 확인 ---
// 현재 로그인한 사용자 ID 세션에서 가져오기
$loggedInUserId = $_SESSION['user_id'] ?? null;

// 로그인이 되어 있지 않으면 로그인 페이지로 리다이렉트 또는 메시지 출력
if (!$loggedInUserId) {
    // header('Location: login.php'); // 옵션 1: 로그인 페이지로 이동
    // exit;
    die("로그인이 필요한 페이지입니다. <a href='login.php'>로그인</a>"); // 옵션 2: 메시지 출력
}

// --- 로그인 사용자 프로필 이미지 가져오기 (헤더용) ---
$loggedInUserProfileImage = null;
try {
    $stmtLoggedInUser = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
    $stmtLoggedInUser->bindParam(':id', $loggedInUserId, PDO::PARAM_INT);
    $stmtLoggedInUser->execute();
    $loggedInUser = $stmtLoggedInUser->fetch(PDO::FETCH_ASSOC);

    if ($loggedInUser && $loggedInUser['profile_image']) {
        $loggedInUserProfileImage = $loggedInUser['profile_image'];
    }
} catch (PDOException $e) {
    // 로그만 남기고 페이지 로딩은 계속 진행
    error_log("my_posts.php - 로그인 사용자 프로필 이미지 조회 오류: " . $e->getMessage());
}

// --- 내 게시글 목록 가져오기 (검색 기능 포함) ---
$posts = []; // 게시글 목록 초기화
$searchQuery = $_GET['query'] ?? null; // GET 요청에서 검색어 가져오기

try {
    if ($searchQuery && trim($searchQuery) !== '') {
        // 검색어가 있는 경우
        $stmt = $pdo->prepare(
            "SELECT p.id, p.title, u.username, p.created_at
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.user_id = :userId AND p.title LIKE :query
             ORDER BY p.id DESC"
        );
        $stmt->bindParam(':userId', $loggedInUserId, PDO::PARAM_INT);
        $stmt->bindValue(':query', '%' . $searchQuery . '%', PDO::PARAM_STR);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 검색어가 없는 경우 (또는 빈 검색어)
        $stmt = $pdo->prepare(
            "SELECT p.id, p.title, u.username, p.created_at
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.user_id = :userId
             ORDER BY p.id DESC"
        );
        $stmt->bindParam(':userId', $loggedInUserId, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("my_posts.php - 내 게시글 조회 오류: " . $e->getMessage());
}

// 기본 프로필 이미지 경로 설정
$defaultProfileImage = "https://i.pinimg.com/564x/3b/73/a1/3b73a13983f88f8b84e130bb3fb29e17.jpg"; // 기본 이미지 URL
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 작성글</title>
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

        .nav a:link, .nav a:visited,
        .post-title a:link, .post-title a:visited {
            color: #3b5998;
        }
        .header a:link, .header a:visited {
            color: white;
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

        /* 페이지 제목 추가 */
        .content h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
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
            margin-right: 15px; /* 제목과 정보 사이 간격 */
        }

        .post-title a {
            /* color: #333; */ /* 위에서 통합 관리 */
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
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .post-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .post-title {
                margin-right: 0;
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
        <a href="index.php">전체 글 보기</a>
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
            <form action="my_posts.php" method="get">
                <input type="text" name="query" placeholder="검색어를 입력하세요">
                <button type="submit">검색</button>
            </form>
        </div>
    </div>
</div>

<div class="content">
    <h2><?php if ($searchQuery): ?>검색 결과: "<?php echo htmlspecialchars($searchQuery); ?>"<?php else: ?>내 작성글 목록<?php endif; ?></h2>
    <ul class="post-list">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <li class="post-item">
                    <div class="post-title">
                        <a href="view.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                    </div>
                    <div class="post-info">
                        <?php echo htmlspecialchars($post['username']); ?> |
                        <?php echo date("Y-m-d H:i", strtotime($post['created_at'])); // 날짜 형식 변경 (선택 사항) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <p><?php if ($searchQuery): ?>검색 결과가 없습니다.<?php else: ?>작성한 게시글이 없습니다.<?php endif; ?></p>
        <?php endif; ?>
    </ul>
</div>

</body>
</html>
