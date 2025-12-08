<?php
// check_report_status.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    echo json_encode(['status' => 'error']);
    exit;
}

try {
    // 토큰으로 해당 신고 건의 전화번호(phone)와 상태(status)를 조회
    $stmt = $pdo->prepare("SELECT phone, status FROM reports WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // 전화번호가 '익명'이 아니면 => 문자가 서버에 도착해서 매칭된 것!
        $isVerified = ($row['phone'] !== '익명');
        echo json_encode([
            'status' => 'success',
            'verified' => $isVerified, // true면 인증완료, false면 아직 안옴
            'db_status' => $row['status']
        ]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>