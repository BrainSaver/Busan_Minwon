<?php
// db_connect.php (PDO 버전)

// 1. 환경설정 파일(.env) 경로 설정
// __DIR__은 현재 파일(db_connect.php)의 위치입니다.
$envPath = __DIR__ . '/admin/db.env';

if (!file_exists($envPath)) {
    die("설정 파일 오류: $envPath 파일을 찾을 수 없습니다.");
}

// 2. .env 파일 읽어서 환경변수 등록
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    
    list($name, $value) = explode('=', $line, 2);
    putenv(sprintf('%s=%s', trim($name), trim($value)));
}

// 3. 변수 할당
$host    = getenv('DB_HOST');
$user    = getenv('DB_USER');
$pass    = getenv('DB_PASS');
$db_name = getenv('DB_NAME');
$port    = getenv('DB_PORT');
$charset = getenv('DB_CHARSET') ?: 'utf8mb4'; // 값이 없으면 기본값 utf8mb4

// 4. PDO 연결 시도
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 에러 발생 시 예외 던짐
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 결과를 연관 배열로 가져옴
        PDO::ATTR_EMULATE_PREPARES   => false,                  // SQL Injection 방지 강화
    ];
    
    // admin.php에서 사용하는 변수명인 $pdo로 생성
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    // 연결 실패 시 에러 출력
    die("DB 연결 실패: " . $e->getMessage());
}
?>