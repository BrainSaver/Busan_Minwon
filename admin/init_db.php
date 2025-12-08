<?php
// init_db.php
header('Content-Type: text/html; charset=utf-8');

// 1. 보안 설정: admin/db.env 파일 불러오기
$envPath = __DIR__ . '/db.env';

if (!file_exists($envPath)) {
    die("❌ 설정 파일 오류: $envPath 파일을 찾을 수 없습니다.");
}

// .env 파일 파싱
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = explode('=', $line, 2);
    putenv(sprintf('%s=%s', trim($name), trim($value)));
}

$h    = getenv('DB_HOST');
$port = getenv('DB_PORT');
$u    = getenv('DB_USER');
$pw   = getenv('DB_PASS');
$db   = getenv('DB_NAME'); 

try {
    $dsn = "mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $u, $pw, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "✅ DB '{$db}'에 연결 성공.<br>";

    // 4. 테이블 생성 (새로운 컬럼 포함)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports(
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL DEFAULT '익명',
        content TEXT NOT NULL,
        status VARCHAR(20) DEFAULT '접수대기',
        token VARCHAR(50) DEFAULT NULL,
        line VARCHAR(20) DEFAULT NULL,      -- 호선 (예: 1호선)
        train_num VARCHAR(20) DEFAULT NULL, -- 편성 (예: 47)
        car_num VARCHAR(20) DEFAULT NULL,   -- 호차 (예: 6)
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "✅ 테이블 'reports' 확인 완료.<br>";

    // [중요] 기존 테이블이 있을 경우를 대비해 컬럼 추가 시도 (없으면 추가됨)
    $alter_cmds = [
        "ALTER TABLE reports ADD COLUMN line VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE reports ADD COLUMN train_num VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE reports ADD COLUMN car_num VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE reports ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER status"
    ];

    foreach ($alter_cmds as $sql) {
        try {
            $pdo->exec($sql);
            echo "👉 컬럼 추가됨: $sql<br>";
        } catch (Exception $e) {
            // 이미 컬럼이 있으면 에러가 나므로 무시하고 진행
        }
    }

    echo "<hr><h3 style='color:blue'>🎉 최종 완료: 데이터베이스 구조가 업데이트되었습니다.</h3>";

} catch(PDOException $e) {
    die("❌ 작업 실패 (에러): " . $e->getMessage());
}
?>