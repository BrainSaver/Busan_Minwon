<?php
// admin/admin_check_sms_socket.php
header('Content-Type: text/html; charset=utf-8');

if (!file_exists('../db_connect.php')) { die("❌ 오류: 상위 폴더(../)에서 db_connect.php를 찾을 수 없습니다."); }
require_once '../db_connect.php'; 

// ---------------------------------------------------------
// [NEW] 오래된 익명 데이터 자동 삭제 (5분 경과)
// ---------------------------------------------------------
try {
    $pdo->exec("DELETE FROM reports WHERE phone = '익명' AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
} catch (PDOException $e) { }


// 2. 설정 파일 읽기
$imap_env_path = __DIR__ . '/imap.env';
if (!file_exists($imap_env_path)) die("❌ 오류: admin 폴더 안에 imap.env 파일이 없습니다.");

$conf = parse_ini_file($imap_env_path);
$gmail_id = $conf['GMAIL_ID'] ?? '';
$gmail_pw = $conf['GMAIL_PW'] ?? '';

if (!$gmail_id || !$gmail_pw) die("❌ imap.env 설정 오류: 아이디/비번을 확인하세요.");

class Pop3Client {
    private $fp;
    public function connect($host, $port = 995) {
        $this->fp = fsockopen("ssl://$host", $port, $errno, $errstr, 10);
        if (!$this->fp) die("❌ 소켓 연결 실패: $errstr ($errno)");
        $this->getResp();
    }
    public function login($user, $pass) {
        $this->send("USER $user");
        $this->send("PASS $pass");
    }
    public function getCount() {
        $resp = $this->send("STAT");
        if (strpos($resp, '+OK') === 0) {
            $parts = explode(' ', $resp);
            return isset($parts[1]) ? (int)$parts[1] : 0;
        }
        return 0;
    }
    public function getMessage($num) {
        $this->send("RETR $num", false);
        $content = "";
        while (!feof($this->fp)) {
            $line = fgets($this->fp, 512);
            if (trim($line) === '.') break;
            if (substr($line, 0, 2) == '..') $line = substr($line, 1);
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
    private function getResp() { return fgets($this->fp, 512); }
}

function decode_mime_header_manual($str) {
    if (function_exists('iconv_mime_decode')) { return iconv_mime_decode($str, 0, "UTF-8"); }
    return mb_decode_mimeheader($str);
}

function extract_body_text($raw_mail) {
    $parts = explode("\r\n\r\n", $raw_mail, 2);
    $head = $parts[0] ?? '';
    $body = $parts[1] ?? '';

    $boundary = null;
    if (preg_match('/Content-Type:.*boundary="?([^";\r\n]+)"?/i', $head, $m)) { $boundary = "--" . $m[1]; }

    if (!$boundary) { return decode_part($head, $body); }

    $contents = "";
    $segments = explode($boundary, $body);
    foreach ($segments as $segment) {
        if (trim($segment) == '--' || trim($segment) == '') continue;
        $seg_parts = explode("\r\n\r\n", $segment, 2);
        $seg_head = $seg_parts[0] ?? '';
        $seg_body = $seg_parts[1] ?? '';
        
        if (stripos($seg_head, 'text/') !== false || stripos($seg_head, 'Content-Transfer-Encoding') !== false || stripos($seg_head, 'text.txt') !== false) {
             $contents .= " " . decode_part($seg_head, $seg_body);
        }
    }
    return trim($contents);
}

function decode_part($header, $body) {
    $body = trim($body);
    if (stripos($header, 'base64') !== false) { return base64_decode($body); }
    if (stripos($header, 'quoted-printable') !== false) { return quoted_printable_decode($body); }
    return $body;
}

echo "<h2>🕵️ 통신사 문자 포맷 분석기 (5분 지난 익명데이터 자동삭제됨)</h2>";

try {
    $pop3 = new Pop3Client();
    $pop3->connect('pop.gmail.com');
    $pop3->login("recent:" . $gmail_id, $gmail_pw);
    
    $total = $pop3->getCount();
    echo "<p>총 메일 수: {$total}개 (최신 5개 분석)</p><hr>";
    
    $limit = 5;
    $count = 0;
    
    for ($i = $total; $i >= 1; $i--) {
        if ($count++ >= $limit) break;
        
        $raw_content = $pop3->getMessage($i);
        preg_match('/^Subject: (.+)$/m', $raw_content, $m_sub);
        $subject = decode_mime_header_manual(isset($m_sub[1]) ? trim($m_sub[1]) : '');
        preg_match('/^From: (.+)$/m', $raw_content, $m_from);
        $from = decode_mime_header_manual(isset($m_from[1]) ? trim($m_from[1]) : '');
        
        $body_text = extract_body_text($raw_content);
        $body_text = mb_convert_encoding($body_text, "UTF-8", "EUC-KR, UTF-8, ASCII");
        
        $clean_body = strip_tags($body_text);
        $clean_body = html_entity_decode($clean_body);
        $search_body = preg_replace('/\s+/', ' ', $clean_body);
        
        $full_text_for_search = $subject . " " . $from . " " . $clean_body; 
        preg_match('/010\d{7,8}/', preg_replace('/[^0-9]/', '', $full_text_for_search), $matches);
        $found_phone = isset($matches[0]) ? $matches[0] : '';
        
        echo "<div style='border:2px solid #333; padding:15px; margin:15px 0; background:#fff;'>";
        echo "<strong>보낸사람:</strong> " . htmlspecialchars($from) . "<br>";
        
        if ($found_phone) echo "✅ <strong>번호:</strong> <span style='color:blue;'>{$found_phone}</span><br>";
        else echo "❌ <strong>번호 없음</strong><br>";

        if (preg_match('/\[\s*접수코드\s*:\s*([a-zA-Z0-9]+)\s*\]/', $search_body, $token_match)) {
            $token = $token_match[1];
            echo "✅ <strong>토큰 발견:</strong> <span style='color:green; font-weight:bold; font-size:1.2em;'>{$token}</span><br>";
            
            if ($found_phone) {
                 $formatted_phone = preg_replace('/^(\d{3})(\d{3,4})(\d{4})$/', '$1-$2-$3', $found_phone);
                 $stmt = $pdo->prepare("UPDATE reports SET phone = ? WHERE token = ? AND phone = '익명'");
                 $stmt->execute([$formatted_phone, $token]);
                 if ($stmt->rowCount() > 0) echo "🎉 <strong style='color:blue;'>DB 업데이트 성공!</strong>";
                 else echo "👉 이미 처리됨.";
            }
        } else {
            echo "❌ <strong>토큰 인식 실패</strong><br>";
        }
        echo "</div>";
    }
    $pop3->quit();

} catch (Exception $e) {
    echo "<div style='color:red;'>오류: " . $e->getMessage() . "</div>";
}
?>