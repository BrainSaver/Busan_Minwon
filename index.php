<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>부산 민원 신고 시스템</title>
    <style>
        /* html, body 설정: 스크롤 방지 및 전체 화면 고정 */
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* 스크롤 아예 막기 */
            position: fixed; /* 모바일에서 스크롤 튕김 방지 */
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Pretendard', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            /* 100vh 대신 100dvh를 사용하여 모바일 주소창 높이 변화에 대응 */
            height: 100dvh; 
        }

        .title-area {
            text-align: center;
            /* 화면이 작을 경우를 대비해 절대적인 픽셀 대신 상대적 위치 조정 */
            margin-bottom: 15vh; 
        }

        h1 {
            color: #0b5ed7;
            margin: 0;
            font-size: 2rem;
            line-height: 1.3;
        }

        p {
            color: #666;
            margin-top: 10px;
            font-size: 1rem;
        }

        .bottom-nav {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            /* 아이폰 등 하단 홈 바 영역 침범 방지 */
            padding-bottom: env(safe-area-inset-bottom);
        }

        .btn {
            flex: 1;
            padding: 20px;
            border-radius: 15px;
            border: none;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.1s; /* 터치 반응 속도 개선 */
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            /* 텍스트 줄바꿈 방지 */
            white-space: nowrap; 
        }

        .btn:active {
            transform: scale(0.96);
        }

        .btn-history {
            background-color: #ffffff;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-report {
            background-color: #0b5ed7;
            color: white;
        }
    </style>
</head>
<body>

    <div class="title-area">
        <h1>부산 지하철<br>민원 신고 센터</h1>
        <p>안전하고 쾌적한 지하철을<br>함께 만들어갑니다.</p>
    </div>

    <div class="bottom-nav">
        <a href="my_history.php" class="btn btn-history">
            📋 내역 조회
        </a>
        
        <a href="main.php" class="btn btn-report">
            🚨 신고하기
        </a>
    </div>

</body>
</html>