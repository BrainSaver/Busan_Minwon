<?php
require '../db_connect.php';

// ---------------------------------------------------------
// 1. 설정값 받기 (날짜, 필터)
// ---------------------------------------------------------
// 기본값: 오늘부터 30일 전까지
$default_start = date('Y-m-d', strtotime('-30 days'));
$default_end = date('Y-m-d');

$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : $default_start;
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : $default_end;

// 신고 유형 정의 (키워드 매칭용)
$keywords_map = [
    '잡상인' => ['잡상인', '판매', '물건'],
    '냉/난방' => ['에어컨', '히터', '덥', '춥', '온도'],
    '질서저해' => ['싸움', '욕설', '소란', '난동', '폭행'],
    '시설물' => ['고장', '파손', '스크린도어', '작동'],
    '구걸/노숙' => ['구걸', '노숙'],
    '청소/환경' => ['토사물', '오물', '쓰레기', '더러', '냄새'],
    '기타' => [] 
];
$all_types = array_keys($keywords_map);

// 사용자가 선택한 유형 (없으면 전체 선택)
$selected_types = isset($_GET['types']) ? $_GET['types'] : $all_types;

// ---------------------------------------------------------
// 2. 데이터 조회 (기간 내 전체 데이터 가져오기)
// ---------------------------------------------------------
// 날짜별로 그룹핑하지 않고 일단 다 가져와서 PHP에서 분류함 (키워드 로직 때문)
$sql = "SELECT DATE(created_at) as dt, content 
        FROM reports 
        WHERE created_at >= :start_dt AND created_at <= :end_dt 
        AND phone != '익명'
        ORDER BY created_at ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_dt' => $start_date . ' 00:00:00',
        ':end_dt' => $end_date . ' 23:59:59'
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("데이터 조회 실패");
}

// ---------------------------------------------------------
// 3. 데이터 가공 (날짜별 x 유형별 카운팅)
// ---------------------------------------------------------
// (1) 날짜 배열 초기화 (기간 내 모든 날짜를 0으로 채움 - 그래프 끊김 방지)
$daily_stats = [];
$current = strtotime($start_date);
$end = strtotime($end_date);

while ($current <= $end) {
    $date_str = date('Y-m-d', $current);
    foreach ($all_types as $type) {
        $daily_stats[$date_str][$type] = 0;
    }
    $current = strtotime('+1 day', $current);
}

// (2) DB 데이터 분류 및 카운트
foreach ($rows as $row) {
    $dt = $row['dt'];
    $content = $row['content'];
    $matched_type = '기타';

    // 키워드 검사
    foreach ($keywords_map as $type => $words) {
        if ($type == '기타') continue;
        foreach ($words as $w) {
            if (strpos($content, $w) !== false) {
                $matched_type = $type;
                break 2; // 찾으면 탈출
            }
        }
    }
    
    // 존재하는 날짜라면 카운트 증가
    if (isset($daily_stats[$dt])) {
        $daily_stats[$dt][$matched_type]++;
    }
}

// ---------------------------------------------------------
// 4. Chart.js용 JSON 데이터 생성
// ---------------------------------------------------------
$chart_labels = array_keys($daily_stats); // X축 (날짜들)
$chart_datasets = [];

// 색상 팔레트
$colors = [
    '잡상인' => 'rgba(255, 99, 132, 1)',   // 빨강
    '냉/난방' => 'rgba(54, 162, 235, 1)',   // 파랑
    '질서저해' => 'rgba(255, 206, 86, 1)',  // 노랑
    '시설물' => 'rgba(75, 192, 192, 1)',   // 초록
    '구걸/노숙' => 'rgba(153, 102, 255, 1)', // 보라
    '청소/환경' => 'rgba(255, 159, 64, 1)',  // 주황
    '기타' => 'rgba(201, 203, 207, 1)'     // 회색
];

// 선택된 유형만 데이터셋으로 만듦
foreach ($all_types as $type) {
    if (!in_array($type, $selected_types)) continue;

    $data_points = [];
    foreach ($daily_stats as $date => $counts) {
        $data_points[] = $counts[$type];
    }

    $chart_datasets[] = [
        'label' => $type,
        'data' => $data_points,
        'borderColor' => $colors[$type],
        'backgroundColor' => $colors[$type],
        'borderWidth' => 2,
        'tension' => 0.3, // 곡선 부드럽게
        'fill' => false
    ];
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>상세 통계 분석</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* 헤더 */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .title { font-size: 1.8rem; font-weight: bold; color: #333; }
        .btn-back { padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 8px; text-decoration: none; font-weight: bold; }
        
        /* 컨트롤 패널 */
        .control-panel { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-row { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
        .form-group { display: flex; align-items: center; gap: 10px; }
        label { font-weight: bold; color: #555; }
        input[type="date"] { padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        
        /* 체크박스 그룹 */
        .checkbox-group { display: flex; gap: 15px; flex-wrap: wrap; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #eee; }
        .chk-item { display: flex; align-items: center; gap: 5px; cursor: pointer; user-select: none; }
        .chk-item input { transform: scale(1.2); cursor: pointer; }
        
        .btn-search { padding: 10px 25px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: background 0.2s; }
        .btn-search:hover { background-color: #2563eb; }

        /* 차트 영역 */
        .chart-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative; height: 500px; width: 100%; }
        h3 { margin-top: 0; color: #333; border-bottom: 2px solid #f4f6f9; padding-bottom: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="title">📈 상세 통계 분석 (선 그래프)</div>
        <a href="admin.php" class="btn-back">⬅ 관리자 홈으로</a>
    </div>

    <form class="control-panel" method="GET">
        <div class="form-row">
            <div class="form-group">
                <label>📅 조회 시작일</label>
                <input type="date" name="start_date" value="<?=$start_date?>" required>
            </div>
            <div class="form-group">
                <label>~ 종료일</label>
                <input type="date" name="end_date" value="<?=$end_date?>" required>
            </div>
            <button type="submit" class="btn-search">조회하기</button>
        </div>

        <label style="display:block; margin-bottom:10px;">🔍 비교할 신고 유형 선택:</label>
        <div class="checkbox-group">
            <?php foreach ($all_types as $type): ?>
                <label class="chk-item">
                    <input type="checkbox" name="types[]" value="<?=$type?>" 
                        <?= in_array($type, $selected_types) ? 'checked' : '' ?>>
                    <?=$type?>
                </label>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="chart-container">
        <h3>📊 날짜별 신고 변동 추이</h3>
        <canvas id="trendChart"></canvas>
    </div>
</div>

<script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line', // 선 그래프
        data: {
            labels: <?php echo json_encode($chart_labels); ?>, // 날짜들
            datasets: <?php echo json_encode($chart_datasets); ?> // 유형별 데이터
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    title: { display: true, text: '신고 건수' }
                },
                x: {
                    title: { display: true, text: '날짜' }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    padding: 10,
                    backgroundColor: 'rgba(0,0,0,0.8)'
                }
            }
        }
    });
</script>

</body>
</html>