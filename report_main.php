<?php
// ---------------------------------------------------------
// 1. API 호출 및 데이터 처리 (백엔드 로직)
// ---------------------------------------------------------

$envPath = 'api.env';

if (!file_exists($envPath)) {
    die("에러: api.env 파일을 찾을 수 없습니다.");
}

$env = parse_ini_file($envPath);
if (!isset($env['MY_API_KEY'])) {
    die("에러: api.env 파일 안에 MY_API_KEY가 없습니다.");
}
$serviceKey = $env['MY_API_KEY'];

// API 요청 주소 및 파라미터
$url = "http://data.humetro.busan.kr/voc/api/open_api_process.tnn";
$queryParams = '?' . urlencode('serviceKey') . '=' . $serviceKey;
$queryParams .= '&' . urlencode('act') . '=' . urlencode('json');
$queryParams .= '&' . urlencode('scode') . '=' . urlencode('101'); // 101: 신평역

$fullUrl = $url . $queryParams;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$items = []; // 데이터를 담을 빈 배열
$stationName = "알 수 없음";

if ($httpCode == 200) {
    // [중요] 한글 깨짐 해결 (EUC-KR -> UTF-8)
    $response = mb_convert_encoding($response, "UTF-8", "EUC-KR");
    $data = json_decode($response, true);

    if ($data !== null && isset($data['response']['body']['item'])) {
        $items = $data['response']['body']['item'];
        
        // 역 이름 가져오기 (첫 번째 데이터 기준)
        if (count($items) > 0) {
            $stationName = $items[0]['sname'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>부산 도시철도 시간표</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .line-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #f39c12; /* 1호선 주황색 느낌 */
            color: white;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .empty-msg {
            text-align: center;
            color: red;
            padding: 20px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>🚉 <?php echo $stationName; ?>역 열차 시간표</h1>
        
        <?php if (empty($items)): ?>
            <div class="empty-msg">
                <p>데이터를 가져오지 못했습니다.</p>
                <small>(API 키나 네트워크 상태를 확인해주세요)</small>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>노선</th>
                        <th>열차번호</th>
                        <th>도착시간</th>
                        <th>방향</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td>
                                <span class="line-badge"><?php echo $row['line']; ?>호선</span>
                            </td>
                            <td><?php echo $row['trainno']; ?></td>
                            <td>
                                <strong><?php echo $row['hour'] . ":" . $row['time']; ?></strong>
                            </td>
                            <td>
                                <?php echo ($row['updown'] == '0') ? '상행' : '하행'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>
</html>