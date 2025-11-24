<?php
session_start();
require '../db_connect.php';

// 로그인 확인 (선택 사항: 관리자만 들어오게 하려면 주석 해제)
/*
if (!isset($_SESSION['logged_in'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit();
}
*/

// DB에서 모든 민원 내역 가져오기 (최신순)
try {
    $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC");
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    die("데이터 조회 실패: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리자 민원 관리 페이지</title>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* 테이블 스타일 */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover { background-color: #f1f1f1; }

        /* 상태 뱃지 스타일 */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; color: #fff; display: inline-block; }
        .badge.waiting { background-color: #ffc107; color: #000; } /* 접수대기: 노랑 */
        .badge.done { background-color: #28a745; } /* 처리완료: 초록 */

        /* 버튼 스타일 */
        .btn-process {
            background-color: #0b5ed7; color: white; border: none; padding: 6px 12px; 
            border-radius: 4px; cursor: pointer; font-size: 0.9rem;
        }
        .btn-process:hover { background-color: #0a58ca; }
        .empty-msg { text-align: center; padding: 30px; color: #666; }
    </style>
</head>
<body>

<div class="container">
    <h2>👮‍♂️ 민원 신고 관리자 현황</h2>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">신고 시간</th>
                <th style="width: 15%;">전화번호</th>
                <th style="width: 40%;">신고 내용</th>
                <th style="width: 10%;">상태</th>
                <th style="width: 15%;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($reports) > 0): ?>
                <?php foreach ($reports as $row): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['content'])); ?></td>
                    <td>
                        <?php if ($row['status'] == '접수대기'): ?>
                            <span class="badge waiting">접수대기</span>
                        <?php else: ?>
                            <span class="badge done">처리완료</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] == '접수대기'): ?>
                        <form action="admin_update.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="new_status" value="처리완료">
                            <button type="submit" class="btn-process" onclick="return confirm('처리완료 상태로 변경하시겠습니까?')">
                                처리 완료하기
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="color:#888; font-size:0.9rem;">처리됨</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty-msg">현재 접수된 민원이 없습니다.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>