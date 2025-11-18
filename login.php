<?php
// 세션을 시작합니다 (로그인 정보를 저장하기 위해 필수)
session_start(); 
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>민원 신고 시스템 - 로그인</title>
</head>
<body>
    <h2>로그인</h2>
    <form action="login_process.php" method="POST">
        <label for="user_id">아이디:</label>
        <input type="text" id="user_id" name="user_id" required><br><br>

        <label for="password">비밀번호:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <button type="submit">로그인</button>
    </form>
</body>
</html>