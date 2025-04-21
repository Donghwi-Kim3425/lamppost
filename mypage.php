<?php
session_start();
$pdo = require_once __DIR__ . '/db_config.php'; // DB 연결만 받아옴


// 로그인 상태 확인 (로그인되지 않았다면 로그인 페이지로 이동)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 현재 로그인한 사용자 ID 세션에서 가져오기
$loggedInUserId = $_SESSION['user_id'] ?? null;
$loggedInUserProfileImage = null;
if ($loggedInUserId) {
    try {
        // 프로필 이미지와 자기소개 함께 조회
        $stmtLoggedInUser = $pdo->prepare("SELECT profile_image, username, created_at, bio FROM users WHERE id = :id");
        $stmtLoggedInUser->bindParam(':id', $loggedInUserId, PDO::PARAM_INT);
        $stmtLoggedInUser->execute();
        $user = $stmtLoggedInUser->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['profile_image']) {
            $loggedInUserProfileImage = $user['profile_image'];
        }
    } catch (PDOException $e) {
        error_log("index.php - 로그인 사용자 정보 조회 오류: " . $e->getMessage());
    }
}

$posts = []; // 게시글 목록 초기화
$searchQuery = $_GET['query'] ?? null; // GET 요청에서 검색어 가져오기

if ($searchQuery) {
    // 검색어가 비어있지 않은 경우에만 검색 수행
    if (trim($searchQuery) !== '') {
        try {
            $stmt = $pdo->prepare("SELECT p.id, p.title, u.username, profile_image, p.created_at FROM posts p JOIN users u ON p.user_id = u.id WHERE p.title LIKE :query OR p.content LIKE :query ORDER BY p.id DESC");
            $stmt->bindValue(':query', '%' . $searchQuery . '%', PDO::PARAM_STR);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("index.php - 게시글 검색 오류: " . $e->getMessage());
        }
    }
    
} else {
    // 검색어가 없으면 모든 게시글을 불러옴
    $stmt = $pdo->query("SELECT p.id, p.title, u.username, profile_image, p.created_at FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 기본 프로필 이미지 경로 설정
$defaultProfileImage = "https://i.pinimg.com/564x/3b/73/a1/3b73a13983f88f8b84e130bb3fb29e17.jpg"; // 기본 이미지 URL
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>마이페이지</title>
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
            justify-content: space-between; /* 양쪽 끝으로 요소 배치 */
            align-items: center;
            flex-wrap: wrap;
            gap: 15px; /* 요소 간 간격 */
        }

        .nav-left {
            display: flex; /* 링크들을 가로로 배치 */
            align-items: center;
            gap: 15px; /* 링크 사이 간격 */
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

        /* 마이페이지 콘텐츠 영역 */
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        .mypage-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-image-mypage {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 15px;
            border: 2px solid #ddd;
        }

        .profile-image-mypage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-image-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .custom-file-label {
            background-color: #3b5998;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background-color 0.3s;
        }

        .custom-file-label:hover {
            background-color: #2c4882;
        }

        .file-input {
            display: none;
        }

        .upload-section {
            margin-top: 10px;
            display: none;
        }

        .upload-button {
            background-color: #3b5998;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .upload-button:hover {
            background-color: #2c4882;
        }


        .mypage-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
        }

        .mypage-section h2 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 10px;
            color: #3b5998;
        }

        .mypage-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .mypage-section input[type="password"],
        .mypage-section input[type="text"] {
            width: calc(100% - 12px);
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .mypage-section button {
            background-color: #3b5998;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .mypage-section button:hover {
            background-color: #2c4882;
        }

        .delete-account {
            text-align: right; /* 버튼을 오른쪽으로 정렬 */
            margin-top: 20px; /* 다른 요소와의 간격 조절 */
        }

        .delete-account button {
            background-color: #dc3456;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .delete-account button:hover{
            background-color: #aa2f48;
        }
        .custom-file-label {
            display: inline-block;
            background-color: #3b5998;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background-color 0.3s;
        }

        .custom-file-label:hover {
            background-color: #2c4882;
        }

        .file-input {
            display: none;
        }

        .upload-button {
            background-color: #3b5998;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .upload-button:hover {
            background-color: #2c4882;
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
        <div class="mypage-profile">
            <div class="profile-image-mypage">
                <?php if ($loggedInUserProfileImage): ?>
                    <img id="profile-preview" src="<?php echo htmlspecialchars($loggedInUserProfileImage); ?>" alt="프로필 사진">
                <?php else: ?>
                    <img id="profile-preview" src="<?php echo htmlspecialchars($defaultProfileImage); ?>" alt="기본 프로필 사진">
                <?php endif; ?>
            </div>

            <!-- 프로필 사진 업로드 영역 -->
            <form action="upload_profile.php" method="POST" enctype="multipart/form-data" class="profile-image-upload">
                <label for="profile_image" class="custom-file-label">프로필 사진 선택하기</label>
                <input type="file" id="profile_image" name="profile_image" class="file-input" accept="image/*" onchange="previewImage(this)">

                <div id="uploadSection" class="upload-section">
                    <button type="submit" class="upload-button">업로드</button>
                </div>
            </form>
        </div>

        <div class="mypage-section">
            <h2>기본 정보</h2>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>

        <div class="mypage-section">
            <h2>자기소개</h2>
            <form action="update_intro.php" method="post">
                <textarea id = "bio" name="bio" rows="5" style="width:100%; font-size:16px; padding:10px;" placeholder="자기소개를 작성하세요..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                <button type="submit">저장</button>
            </form>
        </div>

        <div class="mypage-section">
            <a href="my_posts.php"><h2>내가 작성한 글</h2></a>
        </div>

        <div class="mypage-section">
            <h2>가입일자</h2>
            <p><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
        </div>

        <div class="mypage-section">
            <h2>비밀번호 변경</h2>
            <form action="change_password_process.php" method="post">
                <label for="current_password">현재 비밀번호:</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">새 비밀번호:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_new_password">새 비밀번호 확인:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required>

                <button type="submit">비밀번호 변경</button>
            </form>
        </div>

        <div class="delete-account">
            <form action="delete_account_process.php" method="post" onsubmit="return confirm('정말로 탈퇴하시겠습니까?');">
                <button type="submit" class="delete-account">회원 탈퇴</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            // 이미지 미리보기와 업로드 버튼 표시
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }

                reader.readAsDataURL(input.files[0]);
                showUploadButton(); // 업로드 버튼도 표시
            }
        }

        function showUploadButton() {
            document.getElementById('uploadSection').style.display = 'block';
        }
    </script>

</body>
</html>
