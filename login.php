<?php
// 세션을 시작합니다 (로그인 정보를 저장하기 위해 필수)
session_start(); 
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>민원 신고 시스템 - 로그인</title>
    <style>
        /* 기본 리셋 및 가독성 */
        :root{
            --bg: #f6f8fa;
            --card: #ffffff;
            --primary: #0b5ed7;
            --text: #222;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans KR', 'Helvetica Neue', Arial;
            background:var(--bg);
            color:var(--text);
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:16px;
        }

        /* 카드 컨테이너 */
        .card{
            width:100%;
            max-width:420px;
            background:var(--card);
            border-radius:12px;
            box-shadow:0 6px 18px rgba(15,23,42,0.08);
            padding:22px;
        }

        h2{
            margin:0 0 12px 0;
            font-size:20px;
            text-align:center;
        }

        form{display:block}

        .field{
            display:flex;
            flex-direction:column;
            margin-bottom:14px;
        }

        label{
            font-size:13px;
            margin-bottom:6px;
        }

        input[type="text"], input[type="password"]{
            width:100%;
            padding:12px 14px;
            font-size:16px;
            border:1px solid #d6d9de;
            border-radius:8px;
            background:#fff;
        }

        input[type="text"]:focus, input[type="password"]:focus{
            outline:none;
            border-color:var(--primary);
            box-shadow:0 0 0 4px rgba(11,94,215,0.08);
        }

        .actions{
            display:flex;
            gap:8px;
            flex-direction:column;
            margin-top:8px;
        }

        button[type="submit"]{
            appearance:none;
            -webkit-appearance:none;
            border:0;
            background:var(--primary);
            color:#fff;
            padding:12px 14px;
            font-size:16px;
            border-radius:10px;
            cursor:pointer;
        }

        button[type="submit"]:active{transform:translateY(1px)}

        .small{font-size:13px;color:#555;text-align:center;margin-top:10px}

        /* 데스크탑에서 약간 넓게 보이도록 조정 */
        @media(min-width:640px){
            h2{font-size:22px}
            .card{padding:28px}
            .actions{flex-direction:row}
            .actions a{align-self:center}
        }
    </style>
</head>
<body>
    <div class="card" role="main">
        <h2>로그인</h2>

        <form action="login_process.php" method="POST" aria-label="로그인 폼">
            <div class="field">
                <label for="user_id">아이디</label>
                <input type="text" id="user_id" name="user_id" inputmode="email" autocomplete="username" required aria-required="true" placeholder="ID 또는 이메일">
            </div>

            <div class="field">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required aria-required="true" placeholder="비밀번호">
            </div>

            <div class="actions">
                <button type="submit">로그인</button>
            </div>
        </form>

        <div class="small">모바일에서 편하게 이용하려면 브라우저에서 "홈 화면에 추가"를 사용하세요.</div>
    </div>
</body>
</html>