<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>부산 민원 신고 시스템</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            font-family: 'Pretendard', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .title-area {
            text-align: center;
            margin-bottom: 100px;
        }

        h1 {
            color: #0b5ed7;
            margin: 0;
            font-size: 2rem;
        }

        p {
            color: #666;
            margin-top: 10px;
        }

        /* 하단 버튼 영역 */
        .bottom-nav {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
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
            transition: transform 0.2s;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn:active {
            transform: scale(0.98);
        }

        /* 좌측: 내역 조회 (흰색 배경) */
        .btn-history {
            background-color: #ffffff;
            color: #333;
            border: 1px solid #ddd;
        }

        /* 우측: 신고하기 (파란색 배경) */
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