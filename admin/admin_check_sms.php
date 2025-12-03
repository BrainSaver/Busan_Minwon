<?php
// admin/admin_check_sms.php
header('Content-Type: text/html; charset=utf-8');

// 1. DB 연결
if (!file_exists('../db_connect.php')) {
    die("❌ 오류: 상위 폴더(../)에서 db_connect.php를 찾을 수 없습니다.");
}
require_once '../db_connect.php'; 

// 2. 설정 파일 읽기
$imap_env_path = __DIR__ . '/imap.env';
if (!file_exists($imap_env_path)) die("❌ 오류: admin 폴더 안에 imap.env 파일이 없습니다.");

$conf = parse_ini_file($imap_env_path);
$gmail_id = $conf['GMAIL_ID'] ?? '';
$gmail_pw = $conf['GMAIL_PW'] ?? '';

if (!$gmail_id || !$gmail_pw) die("❌ imap.env 설정 오류: 아이디/비번을 확인하세요.");

// 3. 지메일 접속
$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$inbox = imap_open($hostname, $gmail_id, $gmail_pw) or die('❌ 지메일 접속 실패: ' . imap_last_error());

// 4. 메일 검색
$emails = imap_search($inbox, 'ALL'); 

echo "<h2>🕵️ 통신사 문자 포맷 분석기 (개선판)</h2>";
echo "<p>HTML 태그 제거 및 유연한 검색 적용됨</p><hr>";

if ($emails) {
    rsort($emails); // 최신순

    $count = 0;
    foreach ($emails as $email_number) {
        if($count++ >= 5) break; 

        // 헤더
        $header = imap_headerinfo($inbox, $email_number);
        $raw_subject = isset($header->subject) ? $header->subject : '';
        $decoded_subject = imap_utf8($raw_subject);

        $fromObj = $header->from[0];
        $fromAddress = $fromObj->mailbox . "@" . $fromObj->host;
        $fromName = isset($fromObj->personal) ? $fromObj->personal : '';
        $decoded_fromName = imap_utf8($fromName);

        // --- [핵심 수정 1] 본문 가져오기 로직 개선 ---
        // 재귀함수를 통해 텍스트 부분만 우선적으로 추출합니다.
        $body = get_part($inbox, $email_number, "TEXT/PLAIN");
        if (!$body) {
            $body = get_part($inbox, $email_number, "TEXT/HTML");
        }
        if (!$body) {
            $body = imap_body($inbox, $email_number);
        }
        
        // 인코딩 변환
        $body = mb_convert_encoding($body, "UTF-8", "auto");

        // --- [핵심 수정 2] HTML 태그 및 특수문자 제거 ---
        // HTML 태그를 모두 지워서 순수 텍스트만 남깁니다.
        $clean_body = strip_tags($body); 
        // &nbsp; 같은 엔티티 제거
        $clean_body = html_entity_decode($clean_body); 
        // 줄바꿈과 탭을 공백으로 변경하여 한 줄로 만듦 (검색 용이)
        $search_body = preg_replace('/\s+/', ' ', $clean_body);

        // --- 번호 추출 ---
        $full_text_for_search = $decoded_subject . " " . $decoded_fromName . " " . $fromAddress;
        preg_match('/010\d{7,8}/', preg_replace('/[^0-9]/', '', $full_text_for_search), $matches);
        $found_phone = isset($matches[0]) ? $matches[0] : '';

        // --- 화면 출력 ---
        echo "<div style='border:2px solid #333; padding:15px; margin:15px 0; background:#fff;'>";
        echo "<h3 style='margin-top:0;'>📧 메일 #{$count}</h3>";
        
        echo "<strong>보낸사람:</strong> " . htmlspecialchars($fromAddress) . "<br>";
        
        if ($found_phone) {
             echo "✅ <strong>번호 추출:</strong> <span style='color:blue; font-size:1.2em;'>{$found_phone}</span><br>";
        } else {
             echo "❌ <strong>번호 없음</strong><br>";
        }

        // --- [핵심 수정 3] 토큰 찾기 정규식 강화 ---
        // \s* : 공백이 있어도 허용
        // [a-zA-Z0-9]+ : 영문+숫자 조합
        if (preg_match('/\[\s*접수코드\s*:\s*([a-zA-Z0-9]+)\s*\]/', $search_body, $token_match)) {
            $token = $token_match[1];
            echo "✅ <strong>토큰 발견:</strong> <span style='color:green; font-weight:bold; font-size:1.2em;'>{$token}</span><br>";
            
            // DB 업데이트
            if ($found_phone) {
                 $formatted_phone = preg_replace('/^(\d{3})(\d{3,4})(\d{4})$/', '$1-$2-$3', $found_phone);
                 
                 // 이미 등록된 번호인지 확인하지 않고, 익명인 것만 업데이트
                 $stmt = $pdo->prepare("UPDATE reports SET phone = ? WHERE token = ? AND phone = '익명'");
                 $stmt->execute([$formatted_phone, $token]);
                 
                 if ($stmt->rowCount() > 0) {
                     echo "🎉 <strong style='color:blue;'>DB 업데이트 성공!</strong> (익명 -> {$formatted_phone})";
                 } else {
                     echo "👉 <strong>DB 변경 없음:</strong> 이미 처리되었거나 해당 토큰이 DB에 없습니다.";
                 }
            }
        } else {
            echo "❌ <strong>토큰 인식 실패</strong><br>";
            echo "<p style='background:#eee; padding:10px; font-size:0.9em;'><strong>🔍 검색 대상 본문(정제됨):</strong><br>" . htmlspecialchars(substr($search_body, 0, 300)) . "...</p>";
        }

        // 읽음 처리
        imap_setflag_full($inbox, $email_number, "\\Seen");
        echo "</div>";
    }
} else {
    echo "메일함이 비어있습니다.";
}

imap_close($inbox);

// --- [핵심 수정 4] 강력한 본문 추출 함수 ---
function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
    if (!$structure) {
        $structure = imap_fetchstructure($imap, $uid);
    }
    if ($structure) {
        if ($mimetype == get_mime_type($structure)) {
            if (!$partNumber) {
                $partNumber = 1;
            }
            $text = imap_fetchbody($imap, $uid, $partNumber);
            switch ($structure->encoding) {
                case 3: return imap_base64($text);
                case 4: return imap_qprint($text);
                default: return $text;
            }
        }
        if ($structure->type == 1) { // multipart
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . ".";
                }
                $data = get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
}

function get_mime_type($structure) {
    $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if ($structure->subtype) {
        return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}
?>