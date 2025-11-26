<?php
// db.php
$h='localhost'; $port='3306'; $u='root'; $pw=''; $db='busan_minwon_system';

try {
    $pdo = new PDO("mysql:host=$h;port=$port;charset=utf8mb4", $u, $pw, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    
    // DB 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo = new PDO("mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4", $u, $pw, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    // 테이블 생성 (token 컬럼 추가됨!)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports(
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL DEFAULT '익명',
        content TEXT NOT NULL,
        status VARCHAR(20) DEFAULT '접수대기',
        token VARCHAR(50) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 기존 테이블에 token 컬럼이 없을 경우를 대비해 추가 (이미 있으면 무시됨)
    try {
        $pdo->exec("ALTER TABLE reports ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER status");
    } catch (Exception $e) { /* 컬럼이 이미 있으면 에러 무시 */ }

} catch(PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}
?>


<?php
// fix_db.php
require '../db_connect.php'; // 가지고 계신 파일명

try {
    // reports 테이블에 token 컬럼이 없으면 추가하는 명령어
    $pdo->exec("ALTER TABLE reports ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER status");
    echo "<h1>✅ 성공! DB에 'token' 칸을 추가했습니다.</h1>";
} catch (PDOException $e) {
    // 이미 있으면 에러가 날 수 있는데, 그건 괜찮음
    echo "<h1>⚠️ 이미 칸이 있거나 에러가 났습니다: " . $e->getMessage() . "</h1>";
}
?>