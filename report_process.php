<?php
session_start();
require 'db_connect.php'; // DB 연결

// POST로 데이터가 들어왔는지 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content']; // 만들어진 신고 문장
    $phone = $_POST['phone'];     // 신고자 전화번호 (DB에 필수값이므로 받아야 함)

    // 유효성 검사
    if (empty($content) || empty($phone)) {
        echo "<script>alert('내용이나 연락처가 비어있습니다.'); history.back();</script>";
        exit;
    }

    try {
        // DB에 저장 (reports 테이블)
        // status는 기본값 '접수대기'로 들어갑니다.
        $sql = "INSERT INTO reports (phone, content, created_at) VALUES (:phone, :content, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':content', $content);
        $stmt->execute();

        // 성공 시 알림 후 메인으로 이동
        echo "<script>
            alert('민원 신고가 성공적으로 접수되었습니다!\\n관리자가 곧 확인합니다.');
            location.href = 'main.php'; 
        </script>";

    } catch (PDOException $e) {
        // 에러 발생 시
        echo "<script>alert('신고 접수 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "'); history.back();</script>";
    }
} else {
    // 주소창으로 직접 접속 시 차단
    echo "<script>location.href = 'main.php';</script>";
}
?>