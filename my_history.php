<?php
require 'db_connect.php';

$phone = '';
$reports = [];
$search_attempted = false;

// 사용자가 전화번호를 입력하고 조회 버튼을 눌렀을 때
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['phone'])) {
    $phone = $_GET['phone'];
    $search_attempted = true;

    if (!empty($phone)) {
        try {
            // 해당 전화번호로 등록된 민원 조회 (최신순)
            $stmt = $pdo->prepare("SELECT * FROM reports WHERE phone = :phone ORDER BY created_at DESC");
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();
            $reports = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "조회 중 오류 발생";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 신고 내역</title>
    <style>
        body { font-family: 'Pretendard', sans-serif; background: #f8f9fb; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        
        .search-box {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; text-align: center;
        }
        input[type="tel"] {
            padding: 12px; width: 70%; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;
        }
        button {
            padding: 12px 20px; background: #333; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;
        }
        
        .report-card {
            background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #ddd;
        }
        /* 처리완료 시 왼쪽 테두리 초록색 */
        .report-card.done { border-left-color: #28a745; }
        /* 접수대기 시 왼쪽 테두리 노란색 */
        .report-card.waiting { border-left-color: #ffc107; }

        .header { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; color: #666; }
        .status { font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .status.waiting { background: #fff3cd; color: #856404; }
        .status.done { background: #d4edda; color: #155724; }
        
        .content { font-size: 1.1rem; font-weight: bold; color: #333; line-height: 1.4; }
        .back-btn { display: block; text-align: center; margin-top: 30px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align:center;">📋 내 신고 내역</h2>

    <div class="search-box">
        <p>신고할 때 입력한 연락처를 입력하세요.</p>
        <form action="my_history.php" method="GET">
            <input type="tel" name="phone" placeholder="010-0000-0000" value="<?php echo htmlspecialchars($phone); ?>" required>
            <button type="submit">조회</button>
        </form>
    </div>

    <?php if ($search_attempted): ?>
        <?php if (count($reports) > 0): ?>
            <?php foreach ($reports as $row): ?>
                <div class="report-card <?php echo ($row['status'] == '처리완료') ? 'done' : 'waiting'; ?>">
                    <div class="header">
                        <span><?php echo substr($row['created_at'], 0, 16); ?></span> <?php if ($row['status'] == '접수대기'): ?>
                            <span class="status waiting">접수중</span>
                        <?php else: ?>
                            <span class="status done">처리완료 ✅</span>
                        <?php endif; ?>
                    </div>
                    <div class="content">
                        <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#888;">해당 번호로 접수된 내역이 없습니다.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="first.php" class="back-btn">⬅ 홈으로 돌아가기</a>
</div>

</body>
</html>