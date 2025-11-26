<?php
// admin/check_status.php
header('Content-Type: text/plain; charset=utf-8');
require '../db_connect.php'; 

try {
    // 1. 전체 신고 건수
    // 2. 전화번호가 '익명'이 아닌 건수 (매칭된 건수)
    // 3. 가장 마지막에 들어온 신고 ID
    $sql = "SELECT 
                COUNT(*) as total, 
                SUM(CASE WHEN phone != '익명' THEN 1 ELSE 0 END) as matched,
                MAX(id) as last_id
            FROM reports";
            
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();

    // 이 값들을 합쳐서 하나의 '상태 코드'로 만듭니다. (예: 10-3-15)
    // 데이터가 하나라도 바뀌면 이 문자열이 변하게 됩니다.
    echo "{$row['total']}-{$row['matched']}-{$row['last_id']}";

} catch (PDOException $e) {
    echo "error";
}
?>