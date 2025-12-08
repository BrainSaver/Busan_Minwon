<?php
// save_report.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php'; 

function generate_token($length = 8) {
    return bin2hex(random_bytes((int)($length / 2)));
}

$input = json_decode(file_get_contents('php://input'), true);
$content = isset($input['content']) ? $input['content'] : '';
$clientToken = isset($input['token']) ? $input['token'] : ''; 

// [추가] main.php에서 보낸 데이터 받기
$line = isset($input['line']) ? $input['line'] : null;
$train_num = isset($input['train_num']) ? $input['train_num'] : null;
$car_num = isset($input['car_num']) ? $input['car_num'] : null;

$response = ['success' => false, 'message' => ''];

if ($content) {
    try {
        // 본문에서 [접수코드: ...] 제거
        $content = preg_replace('/\[\s*접수코드\s*:\s*[^\]]+\s*\]/', '', $content);
        $content = trim($content); 

        $token = !empty($clientToken) ? $clientToken : generate_token();

        // [수정] 새 컬럼들(line, train_num, car_num) 함께 저장
        $sql = "INSERT INTO reports (phone, content, status, token, line, train_num, car_num) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['익명', $content, '접수대기', $token, $line, $train_num, $car_num]);

        if ($result) {
            $response['success'] = true;
            $response['token'] = $token;
        } else {
            $response['message'] = '저장 실패';
        }
    } catch (PDOException $e) {
        $response['message'] = 'DB 에러: ' . $e->getMessage();
    }
} else {
    $response['message'] = '내용 없음';
}

echo json_encode($response);
?>