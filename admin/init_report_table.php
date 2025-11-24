<?php
require '../db_connect.php'; // 팀원이 만든 DB 연결 파일 활용

try {
    // reports 테이블 생성 SQL
    // id: 고유번호, phone: 전화번호, content: 신고내용, status: 처리상태, created_at: 신고시간
    $sql = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        status VARCHAR(20) DEFAULT '접수대기', 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<h1>✅ 민원(reports) 테이블 생성 완료!</h1>";
    echo "<p>이제 admin.php를 만들 수 있습니다.</p>";

    // (테스트용) 더미 데이터 하나 추가하기
    $stmt = $pdo->prepare("INSERT INTO reports (phone, content, status) VALUES ('010-1234-5678', '가로등이 고장났어요.', '접수대기')");
    $stmt->execute();
    echo "<p>테스트용 샘플 데이터가 하나 추가되었습니다.</p>";

} catch (PDOException $e) {
    die("테이블 생성 실패: " . $e->getMessage());
}
?>