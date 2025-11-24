<?php
session_start();
require '../db_connect.php';

// POST로 넘어온 데이터가 있는지 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $new_status = $_POST['new_status'];

    try {
        // DB 업데이트 쿼리 실행
        $sql = "UPDATE reports SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 처리가 끝나면 다시 관리자 페이지로 돌아감
        echo "<script>alert('처리 상태가 변경되었습니다.'); location.href='admin.php';</script>";

    } catch (PDOException $e) {
        die("업데이트 오류: " . $e->getMessage());
    }
} else {
    // 잘못된 접근 시
    echo "<script>location.href='admin.php';</script>";
}
?>