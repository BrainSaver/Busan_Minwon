<?php
// save_report.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php'; // DB 연결 파일

// 비상용 토큰 생성 (혹시 안 넘어왔을 때 대비)
function generate_token($length = 8) {
    return bin2hex(random_bytes((int)($length / 2)));
}

// 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$content = isset($input['content']) ? $input['content'] : '';

// [핵심] main.php에서 보낸 토큰을 받음
$clientToken = isset($input['token']) ? $input['token'] : ''; 

$response = ['success' => false, 'message' => ''];

if ($content) {
    try {
        // [핵심] 클라이언트 토큰이 있으면 그걸 쓰고, 없으면 새로 만듦
        $token = !empty($clientToken) ? $clientToken : generate_token();

        // DB 저장
        $stmt = $pdo->prepare("INSERT INTO reports (phone, content, status, token) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute(['익명', $content, '접수대기', $token]);

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