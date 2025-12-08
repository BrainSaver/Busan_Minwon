<?php
session_start();
require '../db_connect.php';

// [로그인 및 로그아웃]
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$admin_id = 'admin'; 
$admin_pw = 'your_password'; // ★ 실제 비밀번호로 변경하세요

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id'], $_POST['login_pw'])) {
        if ($_POST['login_id'] === $admin_id && $_POST['login_pw'] === $admin_pw) {
            $_SESSION['is_admin'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "아이디 또는 비밀번호가 올바르지 않습니다.";
        }
    }
    // 로그인 폼
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head><meta charset="UTF-8"><title>관리자 로그인</title>
    <style>body{display:flex;justify-content:center;align-items:center;height:100vh;background:#f4f6f9;margin:0;font-family:'Noto Sans KR',sans-serif;} .login-box{background:white;padding:40px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.1);width:100%;max-width:350px;text-align:center;} h2{margin-bottom:30px;color:#333;} input{width:100%;padding:12px;margin-bottom:15px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box;} button{width:100%;padding:12px;background-color:#3b82f6;color:white;border:none;border-radius:5px;font-weight:bold;cursor:pointer;font-size:1rem;} button:hover{background-color:#2563eb;} .error-msg{color:#dc3545;margin-bottom:15px;font-size:0.9rem;}</style></head>
    <body>
        <div class="login-box">
            <h2>🔒 관리자 로그인</h2>
            <?php if (isset($login_error)) echo "<div class='error-msg'>{$login_error}</div>"; ?>
            <form method="POST"><input type="text" name="login_id" placeholder="아이디" required autofocus><input type="password" name="login_pw" placeholder="비밀번호" required><button type="submit">로그인</button></form>
        </div>
    </body>
    </html>
    <?php exit;
}

// [자동 삭제]
try {
    $del_sql = "DELETE FROM reports WHERE phone = '익명' AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $pdo->exec($del_sql);
} catch (PDOException $e) {}

// [설정 값 받기]
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

// 필터 변수
$loc_filter = isset($_GET['loc']) ? $_GET['loc'] : 'all';   
$line_filter = isset($_GET['line']) ? $_GET['line'] : 'all'; 
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'waiting';
$cat_filter = isset($_GET['category']) ? $_GET['category'] : 'all'; // [NEW] 유형 필터

// 검색어
$search_phone = isset($_GET['search_phone']) ? trim($_GET['search_phone']) : '';
$search_token = isset($_GET['search_token']) ? trim($_GET['search_token']) : '';

// [키워드 정의] (요청하신 분류대로 매핑)
$keywords = [
    '잡상인' => ['잡상인', '물건', '판매'],
    '싸움' => ['싸움', '폭행', '욕설', '위협', '난동'],
    '온도조절' => ['에어컨', '히터', '덥', '춥', '온도', '추워', '더워'],
    '민폐' => ['민폐', '구걸', '노숙', '시끄', '고성', '음식', '전도', '연설', '소란'],
    '청소' => ['청소', '쓰레기', '오물', '토사물', '냄새', '더러'],
    '파손' => ['파손', '고장', '깨져', '작동', '스크린도어'],
    '기타' => [] 
];

// [쿼리 조건 생성]
$where_sql = "WHERE phone != '익명'"; 
$params = [];

// 1. 상태
if ($status_filter === 'waiting') $where_sql .= " AND status = '접수대기'";
elseif ($status_filter === 'completed') $where_sql .= " AND status = '처리완료'";

// 2. 장소
if ($loc_filter === 'inside') $where_sql .= " AND content LIKE '%지하철 안%'";
elseif ($loc_filter === 'outside') $where_sql .= " AND content LIKE '%지하철 밖%'";

// 3. [NEW] 유형 필터 로직
if ($cat_filter !== 'all') {
    if ($cat_filter === '기타') {
        // 기타: 위의 모든 키워드가 '포함되지 않는' 경우
        foreach ($keywords as $k => $words) {
            if ($k === '기타') continue;
            foreach ($words as $w) {
                $where_sql .= " AND content NOT LIKE ?";
                $params[] = "%{$w}%";
            }
        }
    } elseif (isset($keywords[$cat_filter])) {
        // 특정 유형: 해당 키워드 중 하나라도 포함
        $like_clauses = [];
        foreach ($keywords[$cat_filter] as $word) {
            $like_clauses[] = "content LIKE ?";
            $params[] = "%{$word}%";
        }
        if (!empty($like_clauses)) {
            $where_sql .= " AND (" . implode(" OR ", $like_clauses) . ")";
        }
    }
}

// 4. 호선
if (in_array($line_filter, ['1', '2', '3', '4'])) {
    $search_line_num = str_replace('호선', '', $line_filter);
    $where_sql .= " AND (line = ? OR (line IS NULL AND content LIKE ?))"; 
    $params[] = $search_line_num;
    $params[] = "%{$line_filter}호선%";
}

// 5. 검색어
if (!empty($search_phone)) {
    $clean_input_phone = str_replace('-', '', $search_phone);
    $where_sql .= " AND REPLACE(phone, '-', '') LIKE ?";
    $params[] = "%{$clean_input_phone}%";
}
if (!empty($search_token)) {
    $where_sql .= " AND token LIKE ?";
    $params[] = "%{$search_token}%";
}

// [데이터 조회]
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM reports $where_sql");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetchColumn();
    $total_pages = ceil($total_count / $limit);

    $sql = "SELECT * FROM reports $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} catch (PDOException $e) { die("데이터 조회 실패"); }

// [통계 계산] (현재 검색 조건 기준)
$stats_count = array_fill_keys(array_keys($keywords), 0);
$stat_sql = "SELECT content FROM reports $where_sql"; 
$stat_stmt = $pdo->prepare($stat_sql);
$stat_stmt->execute($params);
while ($row = $stat_stmt->fetch(PDO::FETCH_ASSOC)) {
    $content = $row['content']; $matched = false;
    foreach ($keywords as $type => $words) {
        if ($type === '기타') continue;
        foreach ($words as $word) { if (strpos($content, $word) !== false) { $stats_count[$type]++; $matched = true; break; } }
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
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h2 { margin: 0; }
        .btn-logout { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 0.9rem; }
        
        .control-panel { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef; }
        .filter-section { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 15px; border-bottom: 1px solid #e9ecef; padding-bottom: 15px; }
        .filter-row { display: flex; align-items: center; gap: 10px; }
        .filter-label { font-weight: bold; color: #495057; width: 40px; }
        
        .toggle-group { display: flex; background: #fff; border: 1px solid #ced4da; border-radius: 6px; overflow: hidden; flex-wrap: wrap; }
        .toggle-btn { padding: 6px 12px; text-decoration: none; color: #495057; font-size: 0.9rem; border-right: 1px solid #ced4da; }
        .toggle-btn:last-child { border-right: none; }
        .toggle-btn.active { background: #3b82f6; color: white; font-weight: bold; }
        .toggle-btn:hover { background: #f1f3f5; }
        
        .search-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .search-form { display: flex; gap: 10px; align-items: center; }
        .search-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; width: 180px; font-size: 0.95rem; }
        .btn-search { padding: 8px 18px; background: #333; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        
        .action-group { display: flex; gap: 10px; }
        .btn-action { padding: 8px 15px; border: none; border-radius: 4px; font-size: 0.9rem; font-weight: bold; cursor: pointer; color: white; text-decoration: none; }
        .btn-stat { background: #6c757d; }
        .btn-sms { background: #4c6ef5; }
        
        .btn-bulk-toggle { background: #fd7e14; color:white; }
        .bulk-actions { display: none; gap: 10px; }
        .btn-bulk-exec { padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: white; }
        .bg-green { background: #28a745; } .bg-red { background: #dc3545; }

        /* 유형별 일괄 선택 버튼 */
        .type-select-row { display: flex; gap: 10px; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ccc; display: none; flex-wrap: wrap; }
        .btn-type-select { padding: 5px 12px; border: 1px solid #ccc; background: white; border-radius: 15px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
        .btn-type-select:hover { background: #e9ecef; }
        .btn-type-select.selected { background: #3b82f6; color: white; border-color: #3b82f6; } /* 선택 시 강조 */

        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: middle; word-break: break-all; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        
        .chk-col { width: 40px; text-align: center; display: none; }
        .chk-col input { transform: scale(1.3); cursor: pointer; }

        th:nth-child(2) { width: 5%; }  th:nth-child(3) { width: 12%; } th:nth-child(4) { width: 18%; } th:nth-child(5) { width: 12%; }
        th:nth-child(6) { width: 10%; } th:nth-child(7) { width: 25%; } th:nth-child(8) { width: 8%; }  th:nth-child(9) { width: 10%; }

        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .waiting { background: #ffc107; color: #000; } .done { background: #28a745; color: #fff; }
        .btn-act { width: 100%; padding: 6px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 0.85rem; }
        .btn-blue { background: #0b5ed7; } .btn-red { background: #dc3545; }
        .empty-msg { padding: 40px; text-align: center; color: #777; }

        .pagination { display: flex; justify-content: center; margin-top: 25px; gap: 5px; }
        .page-link { padding: 8px 12px; border: 1px solid #ddd; color: #333; text-decoration: none; border-radius: 4px; background: white; }
        .page-link.active { background: #3b82f6; color: white; border-color: #3b82f6; font-weight: bold; }

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
        <h2>👮‍♂️ 민원 신고 관리자</h2>
        <a href="?logout=1" class="btn-logout">로그아웃</a>
    </div>

    <div class="control-panel">
        <div class="filter-section">
            <div class="filter-row">
                <span class="filter-label">상태</span>
                <div class="toggle-group">
                    <?php $qs = "&loc=$loc_filter&type=$cat_filter&line=$line_filter&search_phone=$search_phone&search_token=$search_token"; ?>
                    <a href="?status=waiting<?=$qs?>" class="toggle-btn <?=$status_filter=='waiting'?'active':''?>">대기중</a>
                    <a href="?status=completed<?=$qs?>" class="toggle-btn <?=$status_filter=='completed'?'active':''?>">처리완료</a>
                    <a href="?status=all<?=$qs?>" class="toggle-btn <?=$status_filter=='all'?'active':''?>">전체</a>
                </div>
            </div>
            <div class="filter-row">
                <span class="filter-label">장소</span>
                <div class="toggle-group">
                    <?php $qs = "&status=$status_filter&type=$cat_filter&line=$line_filter&search_phone=$search_phone&search_token=$search_token"; ?>
                    <a href="?loc=all<?=$qs?>" class="toggle-btn <?=$loc_filter=='all'?'active':''?>">전체</a>
                    <a href="?loc=inside<?=$qs?>" class="toggle-btn <?=$loc_filter=='inside'?'active':''?>">내부</a>
                    <a href="?loc=outside<?=$qs?>" class="toggle-btn <?=$loc_filter=='outside'?'active':''?>">외부</a>
                </div>
            </div>
            
            <div class="filter-row">
                <span class="filter-label">유형</span>
                <div class="toggle-group">
                    <?php $qs = "&status=$status_filter&loc=$loc_filter&line=$line_filter&search_phone=$search_phone&search_token=$search_token"; ?>
                    <a href="?category=all<?=$qs?>" class="toggle-btn <?=$cat_filter=='all'?'active':''?>">전체</a>
                    <a href="?category=잡상인<?=$qs?>" class="toggle-btn <?=$cat_filter=='잡상인'?'active':''?>">잡상인</a>
                    <a href="?category=싸움<?=$qs?>" class="toggle-btn <?=$cat_filter=='싸움'?'active':''?>">싸움</a>
                    <a href="?category=온도조절<?=$qs?>" class="toggle-btn <?=$cat_filter=='온도조절'?'active':''?>">온도</a>
                    <a href="?category=민폐<?=$qs?>" class="toggle-btn <?=$cat_filter=='민폐'?'active':''?>">민폐</a>
                    <a href="?category=청소<?=$qs?>" class="toggle-btn <?=$cat_filter=='청소'?'active':''?>">청소</a>
                    <a href="?category=파손<?=$qs?>" class="toggle-btn <?=$cat_filter=='파손'?'active':''?>">파손</a>
                    <a href="?category=기타<?=$qs?>" class="toggle-btn <?=$cat_filter=='기타'?'active':''?>">기타</a>
                </div>
            </div>

            <div class="filter-row">
                <span class="filter-label">호선</span>
                <div class="toggle-group">
                    <?php $qs = "&status=$status_filter&loc=$loc_filter&type=$cat_filter&search_phone=$search_phone&search_token=$search_token"; ?>
                    <a href="?line=all<?=$qs?>" class="toggle-btn <?=$line_filter=='all'?'active':''?>">전체</a>
                    <a href="?line=1<?=$qs?>" class="toggle-btn <?=$line_filter=='1'?'active':''?>">1호선</a>
                    <a href="?line=2<?=$qs?>" class="toggle-btn <?=$line_filter=='2'?'active':''?>">2호선</a>
                    <a href="?line=3<?=$qs?>" class="toggle-btn <?=$line_filter=='3'?'active':''?>">3호선</a>
                    <a href="?line=4<?=$qs?>" class="toggle-btn <?=$line_filter=='4'?'active':''?>">4호선</a>
                </div>
            </div>
        </div>

        <div class="search-row">
            <form method="GET" class="search-form">
                <input type="hidden" name="status" value="<?=$status_filter?>">
                <input type="hidden" name="loc" value="<?=$loc_filter?>">
                <input type="hidden" name="category" value="<?=$cat_filter?>">
                <input type="hidden" name="line" value="<?=$line_filter?>">
                
                <input type="text" name="search_phone" class="search-input" value="<?=htmlspecialchars($search_phone)?>" placeholder="전화번호 (01012345678)">
                <input type="text" name="search_token" class="search-input" value="<?=htmlspecialchars($search_token)?>" placeholder="접수코드" style="width:120px;">
                <button type="submit" class="btn-search">검색</button>
            </form>

            <div class="action-group">
                <button class="btn-action btn-bulk-toggle" onclick="toggleBulkMode()">📝 일괄 관리</button>
                <div class="bulk-actions" id="bulkActions">
                    <button class="btn-bulk-exec bg-green" id="btnBulkStatus" onclick="submitBulk('toggle')">상태 변경</button>
                </div>

                <a href="statistics.php" style="text-decoration:none;"><button class="btn-action btn-stat">📊 통계</button></a>
                <a href="admin_check_sms.php" style="text-decoration:none;"><button class="btn-action btn-sms">🔄 문자 확인</button></a>
            </div>
        </div>

        <div class="type-select-row" id="typeSelectRow">
            <span style="font-weight:bold; font-size:0.9rem;">📌 화면 내 일괄 선택 (클릭/해제):</span>
            <?php foreach(array_keys($keywords) as $k): if($k=='기타')continue; ?>
                <button class="btn-type-select" onclick="selectByType(this, '<?=$k?>')"><?=$k?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="chk-col"><input type="checkbox" onchange="toggleAll(this)"></th>
                <th>No</th> <th>신고 시간</th> <th>위치</th> <th>전화번호</th> <th>접수코드</th> <th>신고 내용</th> <th>상태</th> <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($reports) > 0): ?>
                <?php foreach ($reports as $row): 
                    $full_content = $row['content'];
                    $token_text = isset($row['token']) && !empty($row['token']) ? $row['token'] : '-';
                    if ($token_text == '-' && preg_match('/\[접수코드:\s*([^\]]+)\]/', $full_content, $matches)) {
                        $token_text = $matches[1];
                        $full_content = str_replace($matches[0], '', $full_content);
                    } else { $full_content = preg_replace('/\[\s*접수코드\s*:\s*[^\]]+\s*\]/', '', $full_content); }

                    $location_display = ''; $situation_text = '';
                    if (!empty($row['line'])) {
                        $location_display = $row['line'] . "호선";
                        if (!empty($row['train_num'])) $location_display .= " " . $row['train_num'] . "편성";
                        if (!empty($row['car_num'])) $location_display .= " " . $row['car_num'] . "호차";
                        $parts = explode('지금', $full_content); $situation_text = trim($parts[0]);
                    } else {
                        $parts = explode('지금', $full_content); $situation_text = trim($parts[0]); 
                        $location_display = isset($parts[1]) ? trim($parts[1]) : '-';
                        if(count($parts) < 2) { $situation_text = $full_content; $location_display = '-'; }
                    }
                ?>
                <tr class="report-row" data-content="<?=htmlspecialchars($situation_text)?>">
                    <td class="chk-col" style="text-align:center;">
                        <input type="checkbox" class="row-chk" value="<?=$row['id']?>">
                    </td>
                    
                    <td><?=$row['id']?></td>
                    <td><?=date('m-d H:i', strtotime($row['created_at']))?></td>
                    <td style="color: #0b5ed7; font-weight: bold;"><?=htmlspecialchars($location_display)?></td>
                    <td><?=htmlspecialchars($row['phone'])?></td>
                    <td style="color: #666; font-weight: bold; font-size:0.9rem;"><?=htmlspecialchars($token_text)?></td>
                    <td><?=nl2br(htmlspecialchars($situation_text))?></td>
                    <td>
                        <?php if($row['status']=='접수대기'): ?> <span class="badge waiting">접수대기</span>
                        <?php else: ?> <span class="badge done">처리완료</span> <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['status']=='접수대기'): ?>
                        <form action="admin_update.php" method="POST">
                            <input type="hidden" name="id" value="<?=$row['id']?>">
                            <input type="hidden" name="new_status" value="처리완료">
                            <button type="submit" class="btn-act btn-blue" onclick="return confirm('완료 처리하시겠습니까?')">완료</button>
                        </form>
                        <?php else: ?>
                        <form action="admin_update.php" method="POST">
                            <input type="hidden" name="id" value="<?=$row['id']?>">
                            <input type="hidden" name="new_status" value="접수대기">
                            <button type="submit" class="btn-act btn-red" onclick="return confirm('접수 상태로 되돌리시겠습니까?')">취소</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="empty-msg">데이터가 없습니다.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php 
        $qs = "?status=$status_filter&loc=$loc_filter&category=$cat_filter&line=$line_filter&search_phone=$search_phone&search_token=$search_token";
        if ($page > 1) echo "<a href='$qs&page=".($page-1)."' class='page-link'>&laquo; 이전</a>";
        for ($i = 1; $i <= $total_pages; $i++) {
            $cls = ($i == $page) ? 'active' : '';
            echo "<a href='$qs&page=$i' class='page-link $cls'>$i</a>";
        }
        if ($page < $total_pages) echo "<a href='$qs&page=".($page+1)."' class='page-link'>&raquo;</a>";
        ?>
    </div>
</div>

<div id="statModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="closeStats()">&times;</span>
        <h3 style="text-align:center;">📈 현재 필터 기준 통계</h3>
        <hr style="border:0; border-top:1px solid #eee;">
        <div id="statBody"></div>
    </div>
</div>

<script>
    let lastStatus = ""; getStatus().then(s => lastStatus = s);
    setInterval(async () => { try { await fetch('admin_check_sms.php'); const s = await getStatus(); if(lastStatus && s!==lastStatus && s!=="error") location.reload(); } catch(e){} }, 5000);
    async function getStatus() { try { return await (await fetch('check_status.php')).text(); } catch(e){ return "error"; } }
    const statsData = <?=$stats_json?>;
    const keywordMap = <?php echo json_encode($keywords); ?>;

    const modal = document.getElementById('statModal'); const statBody = document.getElementById('statBody');
    function openStats() { modal.style.display = 'flex'; renderStats(); }
    function closeStats() { modal.style.display = 'none'; }
    window.onclick = function(e) { if(e.target==modal) closeStats(); }
    function renderStats() {
        statBody.innerHTML = ''; let total=0; for(let k in statsData) total+=statsData[k];
        if(total===0) { statBody.innerHTML='<div style="text-align:center;padding:20px;">데이터 없음</div>'; return; }
        for(let [k,v] of Object.entries(statsData)) {
            const p = (v/total)*100;
            statBody.innerHTML += `<div class="stat-row"><div class="stat-label">${k}</div><div class="stat-bar-bg"><div class="stat-bar-fill" style="width:${p}%;"></div></div><div class="stat-val">${v}건</div></div>`;
        }
    }

    let isBulkMode = false;
    const currentTab = "<?=$status_filter?>";

    function toggleBulkMode() {
        isBulkMode = !isBulkMode;
        const chkCols = document.querySelectorAll('.chk-col');
        const bulkActions = document.getElementById('bulkActions');
        const typeSelectRow = document.getElementById('typeSelectRow');
        const btnToggle = document.querySelector('.btn-bulk-toggle');
        const btnStatus = document.getElementById('btnBulkStatus');
        
        if (isBulkMode) {
            chkCols.forEach(el => el.style.display = 'table-cell');
            bulkActions.style.display = 'flex';
            typeSelectRow.style.display = 'flex';
            btnToggle.innerText = '취소';
            btnToggle.style.backgroundColor = '#6c757d';

            if (currentTab === 'completed') {
                btnStatus.innerText = '🔄 일괄 접수 취소'; 
                btnStatus.style.backgroundColor = '#dc3545';
            } else {
                btnStatus.innerText = '✅ 일괄 완료 처리';
                btnStatus.style.backgroundColor = '#28a745';
            }

        } else {
            // [수정] 취소 시 모든 체크박스 해제 및 버튼 스타일 초기화
            document.querySelectorAll('.row-chk, .chk-col input').forEach(chk => chk.checked = false);
            document.querySelectorAll('.btn-type-select').forEach(btn => btn.classList.remove('selected'));
            
            chkCols.forEach(el => el.style.display = 'none');
            bulkActions.style.display = 'none';
            typeSelectRow.style.display = 'none';
            btnToggle.innerText = '📝 일괄 관리';
            btnToggle.style.backgroundColor = '#fd7e14';
        }
    }

    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.row-chk');
        checkboxes.forEach(chk => chk.checked = source.checked);
    }

    // [수정] 유형별 선택 토글 (버튼 누를 때마다 체크/해제 반복)
    function selectByType(btn, type) {
        const keywords = keywordMap[type] || [];
        if (keywords.length === 0) return;

        const isSelected = btn.classList.toggle('selected'); // 버튼 스타일 토글
        const rows = document.querySelectorAll('.report-row');
        let count = 0;

        rows.forEach(row => {
            const content = row.dataset.content;
            const chk = row.querySelector('.row-chk');
            
            let matched = false;
            for (let w of keywords) {
                if (content.includes(w)) {
                    matched = true;
                    break;
                }
            }

            if (matched) {
                chk.checked = isSelected; // 버튼 상태에 따라 체크 or 해제
                count++;
            }
        });

        if (isSelected && count === 0) {
            alert(`'${type}' 유형 항목이 현재 페이지에 없습니다.`);
            btn.classList.remove('selected'); // 없으면 버튼 스타일 원복
        }
    }

    function submitBulk(action) {
        const checkedBoxes = document.querySelectorAll('.row-chk:checked');
        if (checkedBoxes.length === 0) { alert('선택된 항목이 없습니다.'); return; }
        
        let msg = '';
        if (currentTab === 'completed') {
            msg = `선택한 ${checkedBoxes.length}건을 [접수 대기] 상태로 되돌리시겠습니까?`;
        } else {
            msg = `선택한 ${checkedBoxes.length}건을 [처리 완료] 하시겠습니까?`;
        }

        if (!confirm(msg)) return;

        const ids = Array.from(checkedBoxes).map(chk => chk.value);

        fetch('admin_bulk_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids, action: action })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('처리되었습니다.');
                location.reload();
            } else {
                alert('오류 발생: ' + data.message);
            }
        })
        .catch(err => alert('통신 오류 발생'));
    }
</script>
</body>
</html>