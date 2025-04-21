<!DOCTYPE html>
<html>
<head>
    <title>게시물 검색</title>
</head>
<body>
    <h1>게시물 검색</h1>
    <form action="search_result.php" method="get">
        <div>
            <label for="search_keyword">검색어:</label>
            <input type="text" name="keyword" id="search_keyword" required>
        </div>
        <button type="submit">검색</button>
        <button type="button" onclick="location.href='index.php'">취소</button>
    </form>
</body>
</html>
