<?php
$pdo = require_once __DIR__ . '/db_config.php';

if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = $_GET['keyword'];
    $stmt = $pdo->prepare("SELECT p.id, p.title, u.username, p.created_at FROM posts p JOIN users u ON p.user_id = u.id WHERE p.title LIKE :keyword OR p.content LIKE :keyword ORDER BY p.id DESC");
    $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    header("Location: search.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>검색 결과</title>
</head>
<body>
    <h1>검색 결과</h1>
    <?php if (count($results) > 0): ?>
        <ul>
            <?php foreach ($results as $post): ?>
                <li>
                    <a href="view.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                    (<?php echo htmlspecialchars($post['username']); ?>, <?php echo $post['created_at']; ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>검색 결과가 없습니다.</p>
    <?php endif; ?>
    <p><a href="search.php">다시 검색</a> | <a href="index.php">목록으로</a></p>
</body>
</html>
