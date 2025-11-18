<?php
session_start();
// 1. DB 연결 파일을 포함합니다.
require 'db_connect.php'; 

// 2. 폼에서 전송된 데이터를 받습니다.
$user_id = $_POST['user_id'];
$name = $_POST['name'];
$password = $_POST['password'];

// 3. ✨ 비밀번호 암호화 (보안 핵심!)
// password_hash() 함수는 비밀번호를 강력하게 해시하고 Salt까지 자동으로 처리합니다.
$password_hash = password_hash($password, PASSWORD_BCRYPT);
// 참고: PASSWORD_BCRYPT 방식은 Salt를 자동으로 생성하여 해시값에 포함하므로, 
//      별도의 'salt' 필드를 만들 필요는 없지만, 이전 테이블 구조에 맞추려면 빈 문자열을 넣습니다.
$salt_placeholder = ''; // user 테이블 구조에 맞추기 위한 임시 필드

// 4. DB에 사용자 정보 저장
try {
    // 5. 아이디 중복 체크 (선행)
    $stmt_check = $pdo->prepare("SELECT user_id FROM user WHERE user_id = :user_id");
    $stmt_check->bindParam(':user_id', $user_id);
    $stmt_check->execute();
    if ($stmt_check->rowCount() > 0) {
        echo "<script>alert('이미 사용 중인 아이디입니다.'); history.back();</script>";
        exit();
    }
    
    // 6. 데이터베이스에 삽입 (Prepared Statement로 보안 강화)
    $sql = "INSERT INTO user (user_id, password_hash, salt, name) 
            VALUES (:user_id, :password_hash, :salt, :name)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':salt', $salt_placeholder); // salt 필드에 빈 값 삽입
    $stmt->bindParam(':name', $name);
    
    $stmt->execute();
    
    // 7. 가입 성공 후 로그인 페이지로 이동
    echo "<script>alert('회원가입이 완료되었습니다. 로그인 해주세요.'); location.href='login.php';</script>";
    exit();

} catch (PDOException $e) {
    // 에러 발생 시 처리 (예: DB 연결 문제, 쿼리 오류)
    die("회원가입 처리 중 오류 발생: " . $e->getMessage());
}
?>