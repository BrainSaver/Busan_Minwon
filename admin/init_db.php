<?php
// DB 이름과 연결 정보
$host = 'localhost';
$db   = 'busan_minwon_system'; // 생성할 DB 이름
$user = 'root';
$pass = ''; // 설정한 비밀번호
$port = '3306'; // 변경된 포트 번호

// 1단계: DB '서버'에만 연결 (특정 DB 선택 전)
$dsn_server = "mysql:host=$host;port=$port;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo_server = new PDO($dsn_server, $user, $pass, $options);
    echo "<h2>1. DB 서버 연결 성공!</h2>";

    // 2단계: DB 생성
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "<h3>2. 데이터베이스 '$db' 생성 또는 이미 존재함.</h3>";

    // 3단계: 생성된 DB에 다시 연결
    $dsn_db = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo_db = new PDO($dsn_db, $user, $pass, $options);
    echo "<h3>3. '$db' DB 연결 성공!</h3>";

    // 4단계: 테이블 생성 (user 테이블 예시)
    $pdo_db->exec("
        CREATE TABLE IF NOT EXISTS user (
            user_id VARCHAR(50) PRIMARY KEY,
            password_hash VARCHAR(255) NOT NULL,
            salt VARCHAR(255) NOT NULL,
            name VARCHAR(50) NOT NULL
        )
    ");
    echo "<h3>4. 'user' 테이블 생성 완료!</h3>";

    echo "<h1>✅ DB 초기 설정 완료!</h1>";

} catch (\PDOException $e) {
    die("오류 발생: " . $e->getMessage());
}
?>