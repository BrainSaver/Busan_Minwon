<?php
// db.php
$h='localhost'; $port='3306'; $u='root'; $pw=''; $db='busan_minwon_system';

try {
    $pdo = new PDO("mysql:host=$h;port=$port;charset=utf8mb4", $u, $pw, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    
    // DB가 없으면 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo = new PDO("mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4", $u, $pw, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    // 테이블 생성 시도
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS reports(
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL DEFAULT '익명',
            content TEXT NOT NULL,
            status VARCHAR(20) DEFAULT '접수대기',
            token VARCHAR(50) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // 1813 에러 (Tablespace exists)인 경우에만 DROP 후 재생성
        if ($e->getCode() == '1813' || strpos($e->getMessage(), 'Tablespace for table') !== false) {
            $pdo->exec("DROP TABLE IF EXISTS reports");
            $pdo->exec("CREATE TABLE reports(
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL DEFAULT '익명',
                content TEXT NOT NULL,
                status VARCHAR(20) DEFAULT '접수대기',
                token VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            throw $e; // 다른 에러는 다시 throw
        }
    }

    // token 컬럼 추가 (이미 있으면 무시)
    try {
        $pdo->exec("ALTER TABLE reports ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER status");
    } catch (Exception $e) { /* 컬럼이 이미 있으면 에러 무시 */ }

    echo "데이터베이스 '{$db}' 생성 및 테이블 준비가 완료되었습니다.";

} catch(PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}
?>