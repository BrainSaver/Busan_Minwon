<?php
// my_history.php
require 'db_connect.php';

$token = ''; // 변수명을 phone에서 token으로 변경
$reports = [];
$search_attempted = false;

// 사용자가 토큰을 입력하고 조회 버튼을 눌렀을 때
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = trim($_GET['token']); // 공백 제거
    $search_attempted = true;

    if (!empty($token)) {
        try {
            // [변경] 전화번호 대신 토큰값으로 조회
            // 토큰은 고유값이므로 보통 1개의 결과만 나오지만, 구조상 리스트로 유지합니다.
            $stmt = $pdo->prepare("SELECT * FROM reports WHERE token = :token ORDER BY created_at DESC");
            $stmt->bindParam(':token', $token);
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
    <title>내 신고 내역 조회</title>
    <style>
        body { font-family: 'Pretendard', sans-serif; background: #f8f9fb; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        
        .search-box {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; text-align: center;
        }
        /* 입력창 스타일 */
        input[type="text"] {
            padding: 12px; width: 70%; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;
            text-align: center; letter-spacing: 1px;
        }
        button {
            padding: 12px 20px; background: #4c6ef5; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;
            font-weight: bold; margin-top: 10px;
        }
        button:hover { background: #4263eb; }
        
        .report-card {
            background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #ddd;
        }
        .report-card.done { border-left-color: #28a745; }
        .report-card.waiting { border-left-color: #ffc107; }

        .header { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; color: #666; }
        .status { font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .status.waiting { background: #fff3cd; color: #856404; }
        .status.done { background: #d4edda; color: #155724; }
        
        .content { font-size: 1.1rem; font-weight: bold; color: #333; line-height: 1.4; }
        .token-info { margin-top:10px; font-size: 0.85rem; color: #888; border-top: 1px dashed #eee; padding-top: 10px;}
        
        .back-btn { display: block; text-align: center; margin-top: 30px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align:center;">📋 신고 내역 조회</h2>

    <div class="search-box">
        <p>신고 시 발급받은 <strong>접수 코드(토큰)</strong>를 입력하세요.</p>
        <form action="my_history.php" method="GET">
            <input type="text" name="token" placeholder="예: a1b2c3d4" value="<?php echo htmlspecialchars($token); ?>" required autocomplete="off">
            <br>
            <button type="submit">내역 조회하기</button>
        </form>
    </div>

    <?php if ($search_attempted): ?>
        <?php if (count($reports) > 0): ?>
            <?php foreach ($reports as $row): ?>
                <div class="report-card <?php echo ($row['status'] == '처리완료') ? 'done' : 'waiting'; ?>">
                    <div class="header">
                        <span><?php echo substr($row['created_at'], 0, 16); ?></span> 
                        <?php if ($row['status'] == '접수대기'): ?>
                            <span class="status waiting">접수중</span>
                        <?php else: ?>
                            <span class="status done">처리완료 ✅</span>
                        <?php endif; ?>
                    </div>
                    <div class="content">
                        <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                    </div>
                    <div class="token-info">
                        접수 코드: <?php echo htmlspecialchars($row['token']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding: 20px;">
                <p style="color:#e03131; font-weight:bold;">❌ 조회된 내역이 없습니다.</p>
                <p style="color:#888; font-size:0.9rem;">코드를 정확히 입력했는지 확인해주세요.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a href="first.php" class="back-btn">⬅ 홈으로 돌아가기</a>
</div>

</body>
</html>