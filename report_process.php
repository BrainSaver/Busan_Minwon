<?php
session_start();
require 'db_connect.php'; // DB 연결 설정 파일

// POST로 데이터가 들어왔는지 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    
    // main.php에서 전화번호 입력란을 없앴으므로, 값이 없으면 '익명'으로 처리합니다.
    $phone = !empty($_POST['phone']) ? $_POST['phone'] : '익명'; 

    // 유효성 검사 (내용만 체크)
    if (empty($content)) {
        echo "<script>alert('신고 내용이 비어있습니다.'); history.back();</script>";
        exit;
    }

    try {
        // DB에 저장 (reports 테이블)
        // status 컬럼이 있다면 기본값(예: '접수대기')으로 들어갑니다.
        $sql = "INSERT INTO reports (phone, content, created_at) VALUES (:phone, :content, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':content', $content);
        $stmt->execute();

        // [핵심 변경] 성공 시 알림 후 'first.php'로 이동
        echo "<script>
            alert('민원 신고가 성공적으로 접수되었습니다!\\n관리자가 곧 확인합니다.');
            location.href = 'first.php'; 
        </script>";

    } catch (PDOException $e) {
        // 에러 발생 시
        echo "<script>alert('신고 접수 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "'); history.back();</script>";
    }
} else {
    // 주소창으로 직접 접속 시 차단하고 first.php로 보냄
    echo "<script>location.href = 'first.php';</script>";
}
?>