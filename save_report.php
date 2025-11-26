<?php
// save_report.php
header('Content-Type: application/json; charset=utf-8');

// [중요] 가지고 계신 DB 연결 파일 이름과 똑같아야 함!
require_once 'db_connect.php'; 

// 랜덤 토큰 생성
function generate_token($length = 8) {
    return bin2hex(random_bytes((int)($length / 2)));
}

// 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$content = isset($input['content']) ? $input['content'] : '';

$response = ['success' => false, 'message' => '', 'token' => ''];

if ($content) {
    try {
        $token = generate_token();

        // [중요] 컬럼 이름이 DB랑 정확히 맞아야 함 (phone, content, status, token)
        $stmt = $pdo->prepare("INSERT INTO reports (phone, content, status, token) VALUES (?, ?, ?, ?)");
        
        // 익명으로 저장
        $result = $stmt->execute(['익명', $content, '접수대기', $token]);

        if ($result) {
            $response['success'] = true;
            $response['token'] = $token;
        } else {
            $response['message'] = 'DB 저장 명령은 실행됐으나 실패함';
        }
    } catch (PDOException $e) {
        // [디버깅용] 에러 내용을 그대로 보내줌 (배포 시엔 가리는 게 좋음)
        $response['message'] = 'DB 에러: ' . $e->getMessage();
    }
} else {
    $response['message'] = '보낼 내용이 비어있음';
}

echo json_encode($response);
?>