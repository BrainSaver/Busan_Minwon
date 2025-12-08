<?php
// cancel_report.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php'; // DB 연결 설정 파일 포함

// JSON 입력 받기
$input = json_decode(file_get_contents('php://input'), true);
$token = isset($input['token']) ? $input['token'] : '';

if (empty($token)) {
    echo json_encode(['status' => 'error', 'msg' => '토큰이 없습니다.']);
    exit;
}

try {
    // 토큰으로 해당 신고 내역 삭제
    $stmt = $pdo->prepare("DELETE FROM reports WHERE token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        // 이미 삭제되었거나 없는 토큰일 경우도 성공으로 처리하거나 별도 메시지
        echo json_encode(['status' => 'success', 'msg' => '이미 삭제된 데이터입니다.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>