<?php
// PHP 코드를 시작할 때 항상 세션을 시작합니다.
session_start(); 
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>민원 신고 시스템 - 회원가입</title>
</head>
<body>
    <h2>회원가입</h2>
    <form action="register_process.php" method="POST">
        <label for="user_id">아이디:</label>
        <input type="text" id="user_id" name="user_id" required><br><br>

        <label for="name">이름:</label>
        <input type="text" id="name" name="name" required><br><br>

        <label for="password">비밀번호:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <button type="submit">가입하기</button>
    </form>
    <p>이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
</body>
</html>