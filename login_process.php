<?php
session_start();
// 1. DB 연결 파일을 포함합니다.
require 'db_connect.php'; 

// 2. 폼에서 전송된 데이터를 받습니다.
$user_id = $_POST['user_id'];
$password = $_POST['password'];

// 3. DB에서 사용자 정보(특히 암호화된 비밀번호)를 조회합니다.
try {
    // DB에 저장된 user_id, password_hash, salt 를 가져옵니다.
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM user WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        // 4. 비밀번호 검증 (DB에 저장된 해시 값과 사용자가 입력한 비밀번호를 비교)
        // password_verify() 함수가 사용자가 입력한 비밀번호를 해시하여 DB의 해시와 비교해줍니다.
        if (password_verify($password, $user['password_hash'])) {
            
            // 5. 로그인 성공 처리: 세션에 로그인 정보 저장 (사용자 인증)
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            
            // 6. 민원 신고 페이지로 리다이렉션
            header("Location: report_main.php");
            exit();
        
        } else {
            // 비밀번호 불일치
            echo "<script>alert('비밀번호가 일치하지 않습니다.'); history.back();</script>";
        }
    } else {
        // 사용자 ID가 DB에 없음
        echo "<script>alert('존재하지 않는 사용자 ID 입니다.'); history.back();</script>";
    }

} catch (PDOException $e) {
    die("로그인 처리 중 오류 발생: " . $e->getMessage());
}
?>