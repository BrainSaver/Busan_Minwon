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

// 1. 호선, 편성, 호차 받기
$line = isset($input['line']) ? $input['line'] : null;
$train_num = isset($input['train_num']) ? $input['train_num'] : null;
$car_num = isset($input['car_num']) ? $input['car_num'] : null;

$response = ['success' => false, 'message' => ''];

if ($content) {
    try {
        // [수정 1] content 정제: "접수코드" 및 "지금..." 뒤의 위치 정보 완전 삭제
        // 1-1. [접수코드: ...] 제거
        $content = preg_replace('/\[\s*접수코드\s*:\s*[^\]]+\s*\]/', '', $content);
        
        // 1-2. "지금" 이라는 단어를 기준으로 문장을 쪼개서, 앞부분(신고내용)만 가져옴
        // 예: "잡상인이 있어요 지금 1호선..." -> "잡상인이 있어요" 만 남음
        $parts = explode('지금', $content);
        $clean_content = trim($parts[0]); 

        // [수정 2] line 정제: "호선" 글자 제거하고 숫자만 저장 (예: "1호선" -> "1")
        if ($line) {
            $line = str_replace('호선', '', $line);
        }

        // 토큰 생성
        $token = !empty($clientToken) ? $clientToken : generate_token();

        // DB 저장 (이제 clean_content 와 숫자만 남은 line이 저장됨)
        $sql = "INSERT INTO reports (phone, content, status, token, line, train_num, car_num) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['익명', $clean_content, '접수대기', $token, $line, $train_num, $car_num]);

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