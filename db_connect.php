<?php
// XAMPP MySQL 연결 정보 (비밀번호 설정 후)
$host = 'localhost';
$db   = 'busan_minwon_system'; // 생성하신 DB 이름으로 변경
$user = 'root';
$pass = ''; // 설정하신 비밀번호로 변경
$charset = 'utf8mb4';
$port = '3306';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    // 에러 발생 시 예외(Exception)를 던지도록 설정
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // 기본적으로 결과를 연관 배열(필드 이름으로 접근)로 가져오도록 설정
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // 연결 실패 시 오류 메시지 출력 후 종료
     die("데이터베이스 연결 실패: " . $e->getMessage());
}

// 모든 PHP 파일에서 DB 연결 객체 $pdo 를 사용할 수 있게 됩니다.
?>