<?php
// admin/admin_db_init.php
header('Content-Type: text/html; charset=utf-8');

// 1. DB 연결
if (!file_exists('../db_connect.php')) {
    die("❌ 오류: 상위 폴더(../)에서 db_connect.php를 찾을 수 없습니다.");
}
require_once '../db_connect.php'; 

// ==========================================================
// [설정] 안전장치 및 초기화 대상 테이블
// ==========================================================

// 1. 실수 방지용 안전장치 (실행하려면 true로 변경하세요)
$is_confirm = false; 

// 2. 초기화할 테이블 이름 (여러 개면 배열에 추가: ['reports', 'users', ...])
$target_tables = ['reports']; 

// ==========================================================
// [실행 로직]
// ==========================================================
echo "<h2>🧨 민원 DB 데이터 초기화 도구</h2>";

if (!$is_confirm) {
    echo "<div style='border: 3px solid red; padding: 20px; background: #fff0f0; color: red; font-weight: bold;'>";
    echo "⚠️ 경고: 이 파일은 DB의 모든 데이터를 영구 삭제합니다.<br><br>";
    echo "실행하려면 코드 15번째 줄의 <code>\$is_confirm = false;</code>를 <br>";
    echo "<code>\$is_confirm = true;</code>로 변경하고 다시 고침하세요.";
    echo "</div>";
    exit;
}

try {
    // 외래키 검사 해제 (테이블 간 연결되어 있어도 강제 삭제 가능하도록)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    foreach ($target_tables as $table) {
        // [핵심] TRUNCATE: 내용을 비우고 ID(Auto Increment)를 1로 초기화
        $sql = "TRUNCATE TABLE " . $table;
        $pdo->exec($sql);
        
        echo "<p>✅ <strong>[{$table}]</strong> 테이블이 초기화되었습니다. (ID: 1번부터 시작)</p>";
    }

    // 외래키 검사 다시 활성화
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<hr><h3 style='color:blue'>🎉 모든 작업이 완료되었습니다. DB가 깨끗해졌습니다.</h3>";
    echo "<a href='./admin_check_sms_socket.php'>[문자 분석기 페이지로 이동]</a>";

} catch (PDOException $e) {
    echo "<div style='color:red'>❌ 오류 발생: " . $e->getMessage() . "</div>";
}
?>