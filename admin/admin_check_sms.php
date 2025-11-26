<?php
// admin/admin_check_sms.php
header('Content-Type: text/html; charset=utf-8');

// 1. DB 연결 (db_connect.php는 상위 폴더에 있으므로 ../ 사용)
if (!file_exists('../db_connect.php')) {
    die("❌ 오류: 상위 폴더(../)에서 db_connect.php를 찾을 수 없습니다.");
}
require_once '../db_connect.php'; 

// 2. 설정 파일 읽기 (같은 폴더에 있으므로 바로 읽음)
$imap_env_path = __DIR__ . '/imap.env';

if (!file_exists($imap_env_path)) {
    die("❌ 오류: admin 폴더 안에 imap.env 파일이 없습니다.");
}

$conf = parse_ini_file($imap_env_path);
$gmail_id = $conf['GMAIL_ID'] ?? '';
$gmail_pw = $conf['GMAIL_PW'] ?? '';

if (!$gmail_id || !$gmail_pw) die("❌ imap.env 설정 오류: 아이디/비번을 확인하세요.");

// 3. 지메일 접속
$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$inbox = imap_open($hostname, $gmail_id, $gmail_pw) or die('❌ 지메일 접속 실패: ' . imap_last_error());

// 4. 메일 검색 (테스트용으로 최근 5개만 가져옴)
$emails = imap_search($inbox, 'ALL'); 

echo "<h2>🕵️ 통신사 문자 포맷 분석기</h2>";
echo "<p>메일함을 분석 중입니다... (최근 5건)</p><hr>";

$deleted_any = false;

if ($emails) {
    rsort($emails); // 최신순 정렬

    $count = 0;
    foreach ($emails as $email_number) {
        if($count++ >= 5) break; 

        // 헤더 정보 가져오기
        $header = imap_headerinfo($inbox, $email_number);
        
        // --- [중요] 원본 데이터 추출 ---
        // 1. 제목
        $raw_subject = isset($header->subject) ? $header->subject : '';
        $decoded_subject = imap_utf8($raw_subject);

        // 2. 보낸 사람 (주소 + 이름)
        $fromObj = $header->from[0];
        $fromAddress = $fromObj->mailbox . "@" . $fromObj->host;
        $fromName = isset($fromObj->personal) ? $fromObj->personal : '';
        $decoded_fromName = imap_utf8($fromName);

        // 3. 본문
        $structure = imap_fetchstructure($inbox, $email_number);
        $body = get_decoded_body($inbox, $email_number, $structure);

        // --- 번호 추출 시도 ---
        // 제목, 보낸사람 이름, 이메일 주소 3곳에서 숫자만 다 긁어모음
        $full_text_for_search = $decoded_subject . " " . $decoded_fromName . " " . $fromAddress;
        
        // 010으로 시작하는 숫자 찾기
        preg_match('/010\d{7,8}/', preg_replace('/[^0-9]/', '', $full_text_for_search), $matches);
        $found_phone = isset($matches[0]) ? $matches[0] : '';


        // --- 화면 출력 (이 부분을 복사해서 보여주세요) ---
        echo "<div style='border:2px solid #333; padding:15px; margin:15px 0; background:#fff;'>";
        echo "<h3 style='margin-top:0;'>📧 메일 #{$count}</h3>";
        
        echo "<strong style='color:red;'>[RAW 데이터 - 보낸사람]</strong>: " . htmlspecialchars($fromAddress) . "<br>";
        echo "<strong style='color:red;'>[RAW 데이터 - 보낸이름]</strong>: " . htmlspecialchars($decoded_fromName) . "<br>";
        echo "<strong style='color:red;'>[RAW 데이터 - 제목]</strong>: " . htmlspecialchars($decoded_subject) . "<br>";
        echo "<hr>";
        
        if ($found_phone) {
             echo "✅ <strong>추출된 번호:</strong> <span style='color:blue; font-size:1.2em;'>{$found_phone}</span><br>";
        } else {
             echo "❌ <strong>번호 추출 실패</strong> (위 RAW 데이터에 번호가 안 보입니다)<br>";
        }

        // 토큰 확인
        if (preg_match('/\[접수코드:([a-zA-Z0-9]+)\]/', $body, $token_match)) {
            echo "✅ <strong>토큰 발견:</strong> " . $token_match[1] . "<br>";
            
            // DB 업데이트 시도
            if ($found_phone) {
                 $formatted_phone = preg_replace('/^(\d{3})(\d{3,4})(\d{4})$/', '$1-$2-$3', $found_phone);
                 $stmt = $pdo->prepare("UPDATE reports SET phone = ? WHERE token = ? AND phone = '익명'");
                 $stmt->execute([$formatted_phone, $token_match[1]]);
                 echo "👉 <strong>DB 업데이트:</strong> " . ($stmt->rowCount() > 0 ? "성공" : "이미 처리됨/대상없음");
            }
        } else {
            echo "❌ <strong>토큰 없음</strong> (본문에 [접수코드:...] 형식이 없습니다)<br>";
            echo "<details><summary>본문 보기</summary><pre style='font-size:0.8em; color:#666;'>" . htmlspecialchars(substr($body, 0, 500)) . "...</pre></details>";
        }

        // 메일 삭제 표시 (삭제 플래그 설정)
        if (imap_delete($inbox, $email_number)) {
            $deleted_any = true;
            echo "<br>🗑️ 메일이 삭제 표시되었습니다.<br>";
        } else {
            echo "<br>⚠️ 메일 삭제 표시 실패: " . htmlspecialchars(imap_last_error()) . "<br>";
        }

        echo "</div>";
    }

    // 삭제 플래그가 설정된 메일들을 영구 삭제 (expunge)
    if ($deleted_any) {
        if (imap_expunge($inbox)) {
            echo "<p>✅ 삭제 표시된 메일들을 영구 삭제했습니다.</p>";
        } else {
            echo "<p>⚠️ 메일 영구 삭제 실패: " . htmlspecialchars(imap_last_error()) . "</p>";
        }
    }
} else {
    echo "메일함이 비어있거나 검색 결과가 없습니다.";
}

imap_close($inbox);

// 도우미 함수
function get_decoded_body($inbox, $email_number, $structure) {
    $body = "";
    $encoding = 0;
    if (isset($structure->parts) && count($structure->parts)) {
        $body = imap_fetchbody($inbox, $email_number, 1);
        $encoding = $structure->parts[0]->encoding;
    } else {
        $body = imap_body($inbox, $email_number);
        $encoding = $structure->encoding;
    }
    if ($encoding == 3) { $body = imap_base64($body); }
    elseif ($encoding == 4) { $body = imap_qprint($body); }
    return mb_convert_encoding($body, 'UTF-8', 'auto');
}
?>