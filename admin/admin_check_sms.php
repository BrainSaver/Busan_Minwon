<?php
// admin/admin_check_sms_socket.php
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

// ==========================================================
// [핵심] PHP IMAP 함수 대체용 - 소켓 통신 클래스 정의
// ==========================================================
class Pop3Client {
    private $fp;
    
    public function connect($host, $port = 995) {
        // SSL 접속 시도
        $this->fp = fsockopen("ssl://$host", $port, $errno, $errstr, 10);
        if (!$this->fp) {
            die("❌ 소켓 연결 실패: $errstr ($errno)");
        }
        $this->getResp(); // 초기 환영 메시지 읽기
    }
    
    public function login($user, $pass) {
        $this->send("USER $user");
        $this->send("PASS $pass");
    }
    
    public function getCount() {
        $resp = $this->send("STAT"); // 결과 예: "+OK 123 45678"
        if (strpos($resp, '+OK') === 0) {
            $parts = explode(' ', $resp);
            return isset($parts[1]) ? (int)$parts[1] : 0;
        }
        return 0;
    }
    
    public function getMessage($num) {
        $this->send("RETR $num", false); // 응답 확인 안함(본문이 길어서)
        
        // 데이터 읽기
        $content = "";
        while (!feof($this->fp)) {
            $line = fgets($this->fp, 512);
            // POP3에서 메일 끝은 온점(.) 하나만 있는 줄
            if (trim($line) === '.') {
                break;
            }
            // Dot-stuffing 제거 (줄 처음에 ..이 있으면 .으로 변경)
            if (substr($line, 0, 2) == '..') {
                $line = substr($line, 1);
            }
            $content .= $line;
        }
        return $content;
    }
    
    public function quit() {
        $this->send("QUIT");
        fclose($this->fp);
    }
    
    private function send($cmd, $chk = true) {
        fputs($this->fp, "$cmd\r\n");
        if ($chk) return $this->getResp();
    }
    
    private function getResp() {
        $resp = fgets($this->fp, 512);
        // 응답이 +OK로 시작하지 않으면 오류로 간주할 수 있음
        return $resp;
    }
}

// ==========================================================
// [핵심] 메일 파싱 헬퍼 함수 (imap_* 함수 대체)
// ==========================================================
function decode_mime_header_manual($str) {
    // iconv_mime_decode가 있다면 사용 (표준 PHP 모듈), 없다면 정규식으로 대충 처리
    if (function_exists('iconv_mime_decode')) {
        return iconv_mime_decode($str, 0, "UTF-8");
    }
    // 간단한 대체 로직 (완벽하진 않음)
    return mb_decode_mimeheader($str);
}

// ==========================================================
// [수정됨] 멀티파트(Boundary) 완벽 대응 파서
// ==========================================================
function extract_body_text($raw_mail) {
    // 1. 헤더와 본문 1차 분리
    $parts = explode("\r\n\r\n", $raw_mail, 2);
    $head = $parts[0] ?? '';
    $body = $parts[1] ?? '';

    // 2. 최상위 Boundary 찾기 (Content-Type: multipart/... boundary="...")
    $boundary = null;
    if (preg_match('/Content-Type:.*boundary="?([^";\r\n]+)"?/i', $head, $m)) {
        $boundary = "--" . $m[1]; // Boundary 앞에는 항상 --가 붙음
    }

    // 3. Boundary가 없으면 일반 메일이므로 통째로 처리 시도
    if (!$boundary) {
        return decode_part($head, $body);
    }

    // 4. Boundary가 있으면 쪼개서 각 파트별로 해석
    $contents = "";
    $segments = explode($boundary, $body);
    
    foreach ($segments as $segment) {
        // 끝나는 boundary(--)나 빈 값은 무시
        if (trim($segment) == '--' || trim($segment) == '') continue;
        
        // 각 파트도 헤더와 본문으로 나뉨
        $seg_parts = explode("\r\n\r\n", $segment, 2);
        $seg_head = $seg_parts[0] ?? '';
        $seg_body = $seg_parts[1] ?? '';
        
        // 텍스트(HTML 포함)인 경우만 추출
        if (stripos($seg_head, 'text/') !== false || stripos($seg_head, 'Content-Transfer-Encoding') !== false) {
             $contents .= " " . decode_part($seg_head, $seg_body);
        }
    }
    
    return trim($contents);
}

// [보조 함수] 인코딩 방식에 맞춰 디코딩
function decode_part($header, $body) {
    $body = trim($body);
    
    // Base64 확인
    if (stripos($header, 'base64') !== false) {
        return base64_decode($body);
    }
    // Quoted-Printable 확인
    if (stripos($header, 'quoted-printable') !== false) {
        return quoted_printable_decode($body);
    }
    
    // 인코딩이 명시되지 않았거나 7bit/8bit면 그대로 반환 (단, 불필요한 공백 제거)
    return $body;
}

// ==========================================================
// 3. 실행 로직
// ==========================================================

echo "<h2>🕵️ 통신사 문자 포맷 분석기 (No-IMAP Socket 버전)</h2>";

try {
    $pop3 = new Pop3Client();
    $pop3->connect('pop.gmail.com');
    $pop3->login("recent:" . $gmail_id, $gmail_pw);
    
    $total = $pop3->getCount();
    echo "<p>총 메일 수: {$total}개 (최신 5개 분석)</p><hr>";
    
    $limit = 5;
    $count = 0;
    
    // 최신 메일(번호 큰 것)부터 역순 조회
    for ($i = $total; $i >= 1; $i--) {
        if ($count++ >= $limit) break;
        
        $raw_content = $pop3->getMessage($i);
        
        // --- 헤더 추출 (정규식 사용) ---
        preg_match('/^Subject: (.+)$/m', $raw_content, $m_sub);
        $raw_subject = isset($m_sub[1]) ? trim($m_sub[1]) : '';
        $subject = decode_mime_header_manual($raw_subject);
        
        preg_match('/^From: (.+)$/m', $raw_content, $m_from);
        $raw_from = isset($m_from[1]) ? trim($m_from[1]) : '';
        $from = decode_mime_header_manual($raw_from);
        
        // --- 본문 추출 및 정제 ---
        // imap_fetchbody 대신 전체 원문에서 텍스트를 발굴합니다.
        $body_text = extract_body_text($raw_content);
        
        // HTML 태그 제거 및 정리
        $clean_body = strip_tags($body_text);
        $clean_body = html_entity_decode($clean_body);
        $search_body = preg_replace('/\s+/', ' ', $clean_body); // 한 줄로
        
        // --- 번호 추출 ---
        $full_text_for_search = $subject . " " . $from . " " . $clean_body; // 전체 텍스트에서 검색
        preg_match('/010\d{7,8}/', preg_replace('/[^0-9]/', '', $full_text_for_search), $matches);
        $found_phone = isset($matches[0]) ? $matches[0] : '';
        
        // --- 화면 출력 ---
        echo "<div style='border:2px solid #333; padding:15px; margin:15px 0; background:#fff;'>";
        echo "<h3 style='margin-top:0;'>📧 메일 #{$i}</h3>";
        echo "<strong>제목:</strong> " . htmlspecialchars($subject) . "<br>";
        echo "<strong>보낸사람:</strong> " . htmlspecialchars($from) . "<br>";
        
        if ($found_phone) {
             echo "✅ <strong>번호 추출:</strong> <span style='color:blue; font-size:1.2em;'>{$found_phone}</span><br>";
        } else {
             echo "❌ <strong>번호 없음</strong><br>";
        }

        // --- 토큰 찾기 ---
        if (preg_match('/\[\s*접수코드\s*:\s*([a-zA-Z0-9]+)\s*\]/', $search_body, $token_match)) {
            $token = $token_match[1];
            echo "✅ <strong>토큰 발견:</strong> <span style='color:green; font-weight:bold; font-size:1.2em;'>{$token}</span><br>";
            
            // DB 업데이트
            if ($found_phone) {
                 $formatted_phone = preg_replace('/^(\d{3})(\d{3,4})(\d{4})$/', '$1-$2-$3', $found_phone);
                 
                 $stmt = $pdo->prepare("UPDATE reports SET phone = ? WHERE token = ? AND phone = '익명'");
                 $stmt->execute([$formatted_phone, $token]);
                 
                 if ($stmt->rowCount() > 0) {
                     echo "🎉 <strong style='color:blue;'>DB 업데이트 성공!</strong> (익명 -> {$formatted_phone})";
                 } else {
                     echo "👉 <strong>DB 변경 없음:</strong> 이미 처리되었거나 DB에 없음.";
                 }
            }
        } else {
            echo "❌ <strong>토큰 인식 실패</strong><br>";
            echo "<p style='background:#eee; padding:10px; font-size:0.9em; color:#666;'><strong>🔍 텍스트 미리보기:</strong><br>" . htmlspecialchars(mb_substr($search_body, 0, 200)) . "...</p>";
        }
        echo "</div>";
    }
    
    $pop3->quit();

} catch (Exception $e) {
    echo "<div style='color:red; font-weight:bold;'>오류 발생: " . $e->getMessage() . "</div>";
}
?>