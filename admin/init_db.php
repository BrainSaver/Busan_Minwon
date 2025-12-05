<?php
// init_db.php

// 1. 보안 설정: admin/db.env 파일 불러오기
$envPath = __DIR__ . '/db.env';

if (!file_exists($envPath)) {
    die("설정 파일 오류: $envPath 파일을 찾을 수 없습니다.");
}

// .env 파일 파싱하여 환경변수로 등록
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = explode('=', $line, 2);
    putenv(sprintf('%s=%s', trim($name), trim($value)));
}

// 2. 환경변수에서 정보 가져오기
$h    = getenv('DB_HOST');
$port = getenv('DB_PORT');
$u    = getenv('DB_USER');
$pw   = getenv('DB_PASS');
$db   = getenv('DB_NAME'); // .env에 설정된 DB 이름 사용 (sk9910064)

try {
    // 3. DB 연결 (이미 존재하는 DB에 접속)
    // 호스팅 환경을 고려하여 'CREATE DATABASE' 과정 없이 바로 해당 DB로 연결합니다.
    $dsn = "mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $u, $pw, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "DB '{$db}'에 연결 성공.<br>";

    // 4. 테이블 생성 시도 (기존 로직 유지)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS reports(
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL DEFAULT '익명',
            content TEXT NOT NULL,
            status VARCHAR(20) DEFAULT '접수대기',
            token VARCHAR(50) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "테이블 'reports' 확인 완료 (존재하거나 생성됨).<br>";
        
    } catch (PDOException $e) {
        // 1813 에러 (Tablespace exists) 등 특수 상황 처리
        if ($e->getCode() == '1813' || strpos($e->getMessage(), 'Tablespace for table') !== false) {
            echo "Tablespace 충돌 감지. 테이블 재생성 시도...<br>";
            $pdo->exec("DROP TABLE IF EXISTS reports");
            $pdo->exec("CREATE TABLE reports(
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL DEFAULT '익명',
                content TEXT NOT NULL,
                status VARCHAR(20) DEFAULT '접수대기',
                token VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            echo "테이블 'reports' 재생성 완료.<br>";
        } else {
            throw $e; // 다른 에러는 상위 catch로 던짐
        }
    }

    // 5. token 컬럼 추가 (이미 있으면 무시)
    try {
        $pdo->exec("ALTER TABLE reports ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER status");
        // 컬럼이 없어서 추가된 경우에만 메시지 출력하고 싶다면 rowCount 확인 필요하지만,
        // ALTER TABLE은 보통 성공/실패만 중요하므로 패스
    } catch (Exception $e) { 
        // 컬럼이 이미 존재하면 에러가 발생하므로 조용히 넘어감
    }

    echo "<strong>최종 완료:</strong> 데이터베이스 및 테이블 준비가 끝났습니다.";

} catch(PDOException $e) {
    die("작업 실패 (에러): " . $e->getMessage());
}
?>