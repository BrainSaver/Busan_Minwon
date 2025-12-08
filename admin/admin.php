<?php
session_start();
require '../db_connect.php';

// ----------------------
// [로그인 및 로그아웃]
// ----------------------
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ----------------------
// [로그인 체크 및 폼 출력]
// ----------------------
$admin_id = 'admin'; 
$admin_pw = 'your_password'; // 실제 비밀번호로 변경하세요

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // 1. 로그인 시도 처리 (POST 요청)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id'], $_POST['login_pw'])) {
        if ($_POST['login_id'] === $admin_id && $_POST['login_pw'] === $admin_pw) {
            $_SESSION['is_admin'] = true;
            header("Location: admin.php"); // 로그인 성공 시 새로고침
            exit;
        } else {
            $login_error = "아이디 또는 비밀번호가 올바르지 않습니다.";
        }
    }

    // 2. 로그인 폼 출력
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>관리자 로그인</title>
        <style>
            body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0; font-family: 'Noto Sans KR', sans-serif; }
            .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
            h2 { margin-bottom: 30px; color: #333; }
            input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background-color: #3b82f6; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem; }
            button:hover { background-color: #2563eb; }
            .error-msg { color: #dc3545; margin-bottom: 15px; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔒 관리자 로그인</h2>
            <?php if (isset($login_error)) echo "<div class='error-msg'>{$login_error}</div>"; ?>
            <form method="POST">
                <input type="text" name="login_id" placeholder="아이디" required autofocus>
                <input type="password" name="login_pw" placeholder="비밀번호" required>
                <button type="submit">로그인</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit; 
}

// ----------------------
// [자동 삭제]
// ----------------------
try {
    $del_sql = "DELETE FROM reports WHERE phone = '익명' AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $pdo->exec($del_sql);
} catch (PDOException $e) {}

// ----------------------
// [검색 및 페이지네이션]
// ----------------------
$loc_filter = isset($_GET['loc']) ? $_GET['loc'] : 'all';   
$line_filter = isset($_GET['line']) ? $_GET['line'] : 'all'; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 15; // 한 페이지당 보여줄 개수
$offset = ($page - 1) * $limit;

// 기본 조건
$where_sql = "WHERE phone != '익명'"; 
$params = [];

if ($loc_filter === 'inside') {
    $where_sql .= " AND content LIKE '%지하철 안%'";
} elseif ($loc_filter === 'outside') {
    $where_sql .= " AND content LIKE '%지하철 밖%'";
}

if (in_array($line_filter, ['1', '2', '3', '4'])) {
    // 새 데이터(line 컬럼)가 있으면 그것으로 검색, 없으면 content에서 검색 (하위 호환)
    $search_line_num = str_replace('호선', '', $line_filter); // "1호선" -> "1"
    $where_sql .= " AND (line = ? OR (line IS NULL AND content LIKE ?))"; 
    $params[] = $search_line_num;
    $params[] = "%{$line_filter}호선%";
}

// 1. 전체 개수 구하기
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM reports $where_sql");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetchColumn();
    $total_pages = ceil($total_count / $limit);
} catch (PDOException $e) {
    $total_count = 0;
    $total_pages = 1;
}

// 2. 데이터 조회
$sql = "SELECT * FROM reports $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    die("데이터 조회 실패: " . $e->getMessage());
}

// ----------------------
// [통계]
// ----------------------
$keywords = [
    '잡상인' => ['잡상인', '물건', '판매'],
    '냉/난방' => ['에어컨', '히터', '덥', '춥', '온도'],
    '질서저해' => ['싸움', '욕설', '시끄', '고성', '난동', '폭행'],
    '시설물' => ['고장', '파손', '화장실', '스크린도어', '작동'],
    '구걸/노숙' => ['구걸', '노숙'],
    '기타' => [] 
];
$stats_count = array_fill_keys(array_keys($keywords), 0);
$stat_sql = "SELECT content FROM reports $where_sql";
$stat_stmt = $pdo->prepare($stat_sql);
$stat_stmt->execute($params);
while ($row = $stat_stmt->fetch(PDO::FETCH_ASSOC)) {
    $content = $row['content'];
    $matched = false;
    foreach ($keywords as $type => $words) {
        if ($type === '기타') continue;
        foreach ($words as $word) {
            if (strpos($content, $word) !== false) {
                $stats_count[$type]++;
                $matched = true;
                break;
            }
        }
        if ($matched) break;
    }
    if (!$matched) $stats_count['기타']++;
}
$stats_json = json_encode($stats_count);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리자 민원 관리 페이지</title>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin: 0; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-logout { padding: 8px 16px; background-color: #dc3545; color: #fff; border: none; border-radius: 5px; font-size: 0.9rem; font-weight: bold; cursor: pointer; text-decoration: none; }
        
        .control-panel { background-color: #f8f9fa; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .filter-section { display: flex; flex-direction: column; gap: 10px; }
        .filter-row { display: flex; align-items: center; gap: 10px; }
        .filter-label { font-weight: bold; color: #495057; font-size: 0.9rem; width: 40px; }
        .toggle-group { display: flex; background: #fff; border: 1px solid #ced4da; border-radius: 6px; overflow: hidden; }
        .toggle-btn { padding: 6px 14px; text-decoration: none; color: #495057; font-size: 0.9rem; border-right: 1px solid #ced4da; transition: all 0.2s; }
        .toggle-btn:last-child { border-right: none; }
        .toggle-btn:hover { background-color: #f1f3f5; }
        .toggle-btn.active { background-color: #3b82f6; color: white; font-weight: bold; }
        
        .action-group { display: flex; gap: 10px; }
        .btn-action { padding: 10px 16px; border: none; border-radius: 6px; font-size: 0.95rem; font-weight: bold; cursor: pointer; color: white; display: flex; align-items: center; gap: 5px; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-stat { background-color: #6c757d; }
        .btn-sms { background-color: #4c6ef5; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; word-break: break-all; vertical-align: middle; }
        th { background-color: #f8f9fa; font-weight: bold; color: #333; }
        
        th:nth-child(1) { width: 5%; }  /* No */
        th:nth-child(2) { width: 12%; } /* 시간 */
        th:nth-child(3) { width: 18%; } /* 위치 */
        th:nth-child(4) { width: 12%; } /* 번호 */
        th:nth-child(5) { width: 10%; } /* 접수코드 */
        th:nth-child(6) { width: 25%; } /* 내용 */
        th:nth-child(7) { width: 8%; }  /* 상태 */
        th:nth-child(8) { width: 10%; } /* 관리 */
        
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; color: #fff; display: inline-block; white-space: nowrap; }
        .badge.waiting { background-color: #ffc107; color: #000; } 
        .badge.done { background-color: #28a745; } 

        .btn-process { background-color: #0b5ed7; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; width: 100%; }
        .btn-reopen { background-color: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; width: 100%; }
        .empty-msg { text-align: center; padding: 30px; color: #666; }

        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .page-link { padding: 8px 12px; border: 1px solid #ddd; color: #333; text-decoration: none; border-radius: 4px; background: white; }
        .page-link:hover { background: #f1f3f5; }
        .page-link.active { background: #3b82f6; color: white; border-color: #3b82f6; font-weight: bold; }

        /* 모달 스타일 */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; width: 90%; max-width: 500px; padding: 25px; border-radius: 15px; position: relative; }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #888; }
        .stat-row { display: flex; align-items: center; margin-bottom: 12px; }
        .stat-label { width: 80px; font-weight: bold; font-size: 0.9rem; }
        .stat-bar-bg { flex: 1; background: #eee; height: 10px; border-radius: 5px; margin: 0 10px; overflow: hidden; }
        .stat-bar-fill { height: 100%; background: #3b82f6; border-radius: 5px; transition: width 0.5s; }
        .stat-val { width: 30px; text-align: right; font-weight: bold; color: #555; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-row">
        <h2>👮‍♂️ 민원 신고 관리자 현황</h2>
        <a href="?logout=1" class="btn-logout" onclick="return confirm('로그아웃 하시겠습니까?')">🚪 로그아웃</a>
    </div>
    <hr style="border: 0; border-top: 2px solid #333; margin-bottom: 20px;">

    <div class="control-panel">
        <div class="filter-section">
            <div class="filter-row">
                <span class="filter-label">장소</span>
                <div class="toggle-group">
                    <a href="?loc=all&line=<?=$line_filter?>&page=1" class="toggle-btn <?=$loc_filter=='all'?'active':''?>">전체</a>
                    <a href="?loc=inside&line=<?=$line_filter?>&page=1" class="toggle-btn <?=$loc_filter=='inside'?'active':''?>">내부</a>
                    <a href="?loc=outside&line=<?=$line_filter?>&page=1" class="toggle-btn <?=$loc_filter=='outside'?'active':''?>">외부</a>
                </div>
            </div>
            <div class="filter-row">
                <span class="filter-label">호선</span>
                <div class="toggle-group">
                    <a href="?loc=<?=$loc_filter?>&line=all&page=1" class="toggle-btn <?=$line_filter=='all'?'active':''?>">전체</a>
                    <a href="?loc=<?=$loc_filter?>&line=1&page=1" class="toggle-btn <?=$line_filter=='1'?'active':''?>">1호선</a>
                    <a href="?loc=<?=$loc_filter?>&line=2&page=1" class="toggle-btn <?=$line_filter=='2'?'active':''?>">2호선</a>
                    <a href="?loc=<?=$loc_filter?>&line=3&page=1" class="toggle-btn <?=$line_filter=='3'?'active':''?>">3호선</a>
                    <a href="?loc=<?=$loc_filter?>&line=4&page=1" class="toggle-btn <?=$line_filter=='4'?'active':''?>">4호선</a>
                </div>
            </div>
        </div>

        <div class="action-group">
            <a href="statistics.php" style="text-decoration:none;">
    <button type="button" class="btn-action btn-stat">📊 상세 통계 보기</button>
</a>
            <a href="admin_check_sms.php" style="text-decoration:none;">
                <button class="btn-action btn-sms">🔄 문자 확인 & 매칭</button>
            </a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th> <th>신고 시간</th> <th>위치</th> <th>전화번호</th> <th>접수코드</th> <th>신고 내용</th> <th>상태</th> <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($reports) > 0): ?>
                <?php foreach ($reports as $row): 
                    // 1. 토큰 추출 및 본문에서 제거
                    $full_content = $row['content'];
                    $token_text = isset($row['token']) && !empty($row['token']) ? $row['token'] : '-';
                    if ($token_text == '-' && preg_match('/\[접수코드:\s*([^\]]+)\]/', $full_content, $matches)) {
                        $token_text = $matches[1];
                        $full_content = str_replace($matches[0], '', $full_content);
                    } else {
                        $full_content = preg_replace('/\[\s*접수코드\s*:\s*[^\]]+\s*\]/', '', $full_content);
                    }

                    // 2. 위치 및 상황 텍스트 결정
                    $location_display = '';
                    $situation_text = '';

                    // [수정 포인트] 새 컬럼 데이터(line, train_num, car_num)가 있으면 그것을 우선 사용
                    if (!empty($row['line'])) {
                        $location_display = $row['line'] . "호선";
                        if (!empty($row['train_num'])) $location_display .= " " . $row['train_num'] . "편성";
                        if (!empty($row['car_num'])) $location_display .= " " . $row['car_num'] . "호차";
                        
                        // '지금' 뒷부분 제거하고 순수 내용만 가져오기
                        $parts = explode('지금', $full_content);
                        $situation_text = trim($parts[0]);
                    } 
                    else {
                        // 예전 데이터 처리 (content 파싱 방식)
                        $parts = explode('지금', $full_content);
                        $situation_text = trim($parts[0]); 
                        $location_display = isset($parts[1]) ? trim($parts[1]) : '-';
                        // 파싱 실패시 예외 처리
                        if(count($parts) < 2) { 
                            $situation_text = $full_content; 
                            $location_display = '-'; 
                        }
                    }
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo date('m-d H:i', strtotime($row['created_at'])); ?></td>
                    <td style="color: #0b5ed7; font-weight: bold;"><?php echo htmlspecialchars($location_display); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td style="color: #666; font-weight: bold; font-size:0.9rem;"><?php echo htmlspecialchars($token_text); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($situation_text)); ?></td>
                    <td>
                        <?php if ($row['status'] == '접수대기'): ?>
                            <span class="badge waiting">접수대기</span>
                        <?php else: ?>
                            <span class="badge done">처리완료</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] == '접수대기'): ?>
                        <form action="admin_update.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="new_status" value="처리완료">
                            <button type="submit" class="btn-process" onclick="return confirm('처리완료하시겠습니까?')">완료 처리</button>
                        </form>
                        <?php else: ?>
                        <form action="admin_update.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="new_status" value="접수대기">
                            <button type="submit" class="btn-reopen" onclick="return confirm('다시 접수 상태로 돌리시겠습니까?')">접수 취소</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="empty-msg">
                        매칭된 민원 내역이 없습니다.<br>
                        <small>(전화번호 매칭 전 데이터는 표시되지 않습니다.)</small>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php 
        $base_url = "?loc={$loc_filter}&line={$line_filter}";
        if ($page > 1) {
            echo '<a href="'.$base_url.'&page='.($page-1).'" class="page-link">&laquo; 이전</a>';
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo '<a href="'.$base_url.'&page='.$i.'" class="page-link '.$active.'">'.$i.'</a>';
        }
        if ($page < $total_pages) {
            echo '<a href="'.$base_url.'&page='.($page+1).'" class="page-link">다음 &raquo;</a>';
        }
        ?>
    </div>

</div>

<div id="statModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="closeStats()">&times;</span>
        <h3 style="text-align:center; margin-top:0;">📈 신고 유형별 통계</h3>
        <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
        <div id="statBody"></div>
        <div style="text-align:center; margin-top:20px; color:#888; font-size:0.8rem;">* 내용 키워드 기준 자동 분류</div>
    </div>
</div>

<script>
    let lastStatus = ""; 
    getStatus().then(status => { lastStatus = status; });

    setInterval(async function() {
        try {
            await fetch('admin_check_sms.php'); 
            const currentStatus = await getStatus();
            if (lastStatus !== "" && currentStatus !== lastStatus && currentStatus !== "error") {
                location.reload();
            }
        } catch (error) { console.error("통신 에러:", error); }
    }, 5000);

    async function getStatus() {
        try {
            const response = await fetch('check_status.php');
            return await response.text();
        } catch (e) { return "error"; }
    }

    const statsData = <?php echo $stats_json; ?>;
    const modal = document.getElementById('statModal');
    const statBody = document.getElementById('statBody');

    function openStats() { modal.style.display = 'flex'; renderStats(); }
    function closeStats() { modal.style.display = 'none'; }
    window.onclick = function(event) { if (event.target == modal) closeStats(); }

    function renderStats() {
        statBody.innerHTML = '';
        let total = 0;
        for (let key in statsData) total += statsData[key];
        if(total === 0) {
            statBody.innerHTML = '<div style="text-align:center;">데이터가 없습니다.</div>';
            return;
        }
        for (let [key, val] of Object.entries(statsData)) {
            const percent = (val / total) * 100;
            const html = `
                <div class="stat-row">
                    <div class="stat-label">${key}</div>
                    <div class="stat-bar-bg"><div class="stat-bar-fill" style="width: ${percent}%;"></div></div>
                    <div class="stat-val">${val}건</div>
                </div>`;
            statBody.innerHTML += html;
        }
    }
</script>
</body>
</html>