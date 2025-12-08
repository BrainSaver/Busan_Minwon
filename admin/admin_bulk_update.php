<?php
// admin/admin_bulk_update.php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die(json_encode(['success' => false, 'message' => '권한 없음']));
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = isset($input['ids']) ? $input['ids'] : [];
$action = isset($input['action']) ? $input['action'] : '';

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => '선택된 항목 없음']);
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // [삭제 기능 제거됨]
    // 오직 상태 변경(토글)만 수행
    if ($action === 'toggle') {
        // 접수대기 -> 처리완료
        // 처리완료 -> 접수대기 (접수 취소)
        $sql = "UPDATE reports 
                SET status = IF(status = '접수대기', '처리완료', '접수대기') 
                WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '잘못된 요청']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>