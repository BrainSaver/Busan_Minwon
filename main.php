<?php
// main.php
$imap_env_path = __DIR__ . '/admin/imap.env';
$myEmail = 'busanminwon@gmail.com'; 

if (file_exists($imap_env_path)) {
    $content = file_get_contents($imap_env_path);
    if (preg_match('/GMAIL_ID\s*=\s*["\']?([^"\']+)["\']?/', $content, $matches)) {
        $myEmail = $matches[1];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>부산지하철 민원신고</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <style>
    /* -------------------------------------------
       1. CSS 변수 및 리셋
    ------------------------------------------- */
    :root {
        --primary-color: #2563EB; /* 진한 파랑 (디자인 시안) */
        --bg-color: #ffffff;
        --box-bg: #F3F4F6;        /* 문장 박스 배경 (연한 회색) */
        --text-main: #111;
        --text-sub: #666;
        
        --dial-item-height: 50px; 
        --dial-container-height: 150px; 
    }

    body {
        margin: 0; background: var(--bg-color);
        font-family: "Pretendard", -apple-system, BlinkMacSystemFont, system-ui, Roboto, sans-serif;
        color: var(--text-main);
        display: flex; flex-direction: column;
        height: 100vh; overflow: hidden;
        box-sizing: border-box;
    }
    
    button { -webkit-tap-highlight-color: transparent; }

    /* -------------------------------------------
       2. 상단 헤더
    ------------------------------------------- */
    .top-header {
        display: flex; align-items: center; justify-content: center;
        height: 50px; padding: 10px 20px;
        position: relative; flex-shrink: 0;
        font-size: 1.2rem; font-weight: 600; color: #1e3a8a;
    }
    .back-icon {
        position: absolute; left: 20px; color: var(--primary-color); cursor: pointer;
        font-size: 1.5rem; font-weight: bold; padding: 10px; margin-left: -10px; /* 터치 영역 확보 */
    }

    /* -------------------------------------------
       3. 진행상황 문장 박스 (회색 박스)
    ------------------------------------------- */
    .sentence-container {
        padding: 0 20px;
        margin-top: 10px;
        flex-shrink: 0;
    }
    #sentenceBox {
        background: var(--box-bg);
        border-radius: 12px;
        padding: 20px;
        font-size: 1.05rem;
        color: #111;
        font-weight: 600;
        text-align: center;
        min-height: 24px;
        display: flex; align-items: center; justify-content: center;
        line-height: 1.4;
        word-break: keep-all;
    }

    /* -------------------------------------------
       4. 질문 텍스트 (가이드)
    ------------------------------------------- */
    .guide-container {
        text-align: center;
        padding: 30px 20px 20px 20px;
        flex-shrink: 0;
    }
    #guideText {
        font-size: 1.3rem; font-weight: 700; color: #1e3a8a;
        margin: 0; line-height: 1.3;
    }

    /* 내부 가이드 이미지 (차량번호 등) */
    #guide-inner-content {
        margin-top: 15px; width: 100%;
        display: flex; flex-direction: column; align-items: center;
        animation: fadeIn 0.5s ease;
    }
    #guide-inner-content img {
        width: 80%; border-radius: 8px; border: 1px solid #eee;
    }
    #guide-inner-content p {
        font-size: 0.9rem; color: #666; margin-top: 5px;
    }

    /* -------------------------------------------
       5. 입력 영역 & 버튼 그리드
    ------------------------------------------- */
    #inputArea {
        flex: 1; 
        display: flex; flex-direction: column;
        justify-content: flex-start; /* 상단 정렬 */
        padding: 0 20px;
        overflow-y: auto; 
    }

    /* 그리드 버튼 컨테이너 */
    .grid-btn-container { 
        display: grid; 
        grid-template-columns: 1fr 1fr; /* 2열 */
        gap: 10px; 
        width: 100%; 
        padding-bottom: 20px;
    }

    /* 파란색 디자인 버튼 (공통) */
    .blue-btn {
        background: var(--primary-color); 
        color: white;
        border: none; border-radius: 12px;
        min-height: 55px;
        font-size: 0.95rem; font-weight: 600;
        cursor: pointer; 
        display: flex; align-items: center; justify-content: center;
        gap: 8px; /* 아이콘과 텍스트 간격 */
        padding: 10px;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15);
        transition: transform 0.1s, background 0.2s;
    }
    .blue-btn:active { transform: scale(0.98); background: #1d4ed8; }
    .blue-btn svg { width: 20px; height: 20px; fill: currentColor; flex-shrink: 0; }

    /* 전체 너비 버튼 (Step 1, 직접 입력 등) */
    .full-width { grid-column: span 2; }

    /* 직접 입력 텍스트박스 */
    #customInputArea { width: 100%; display: none; flex-direction: column; gap: 10px; margin-top: 0; }
    #customInput { 
        padding: 15px; font-size: 1.1rem; 
        border: 2px solid var(--primary-color); border-radius: 12px; 
        width: 100%; box-sizing: border-box; resize: none; min-height: 150px; 
        font-family: inherit;
    }
    .char-count { text-align: right; font-size: 0.85rem; color: #888; }

    /* -------------------------------------------
       6. 다이얼 (Picker) - Step 4
    ------------------------------------------- */
    .step-special-content { width: 100%; display: none; flex-direction: column; flex: 1; align-items: center; }
    
    .dial-wrapper { 
        position: relative; display: flex; justify-content: center; 
        width: 100%; height: var(--dial-container-height); 
        background: #ffffff; border-radius: 18px; 
        overflow: hidden; margin-bottom: 15px; 
        border: 1px solid #e5e7eb; 
        flex-shrink: 0;
    }
    .dial-highlight { 
        position: absolute; top: 50%; left: 0; width: 100%; 
        height: var(--dial-item-height); 
        margin-top: calc(var(--dial-item-height) / -2); 
        background-color: rgba(37, 99, 235, 0.05); 
        border-top: 2px solid var(--primary-color); 
        border-bottom: 2px solid var(--primary-color); 
        pointer-events: none; z-index: 1; 
    }
    .dial-column { height: 100%; width: 60px; overflow-y: scroll; scroll-snap-type: y mandatory; -ms-overflow-style: none; scrollbar-width: none; z-index: 2; }
    .dial-item { height: var(--dial-item-height); display: flex; align-items: center; justify-content: center; font-size: 20px; color: #ccc; scroll-snap-align: center; }
    .dial-item.active { color: #111; font-weight: bold; font-size: 24px; transform: scale(1.1); }
    .dial-label { font-size: 16px; font-weight: bold; color: #333; display:flex; align-items:center; z-index:2; margin: 0 2px;}

    .station-dial-column { height: 100%; flex: 1; overflow-y: scroll; scroll-snap-type: y mandatory; -ms-overflow-style: none; scrollbar-width: none; z-index: 2; padding: 0; border-right: 1px solid #eee; }
    .station-dial-item { height: var(--dial-item-height); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #ccc; scroll-snap-align: center; white-space: nowrap; }
    .station-dial-item.active { color: #111; font-weight: bold; font-size: 19px; }

    /* -------------------------------------------
       7. 결과 화면 & 페이지네이션
    ------------------------------------------- */
    .message-img-container { width: 100%; display: flex; justify-content: center; margin-bottom: 20px; }
    .message-img-container img { max-width: 80%; height: auto; }

    .pagination-dots {
        display: flex; justify-content: center; align-items: center;
        gap: 8px; padding-bottom: 30px; margin-top: auto; flex-shrink: 0;
    }
    .dot { width: 8px; height: 8px; background-color: #e5e7eb; border-radius: 50%; transition: background-color 0.3s; }
    .dot.active { background-color: var(--primary-color); width: 8px; height: 8px; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

  <div class="top-header">
      <div class="back-icon" id="backBtn" onclick="goBack()">‹</div>
      <span id="headerTitle">Location</span>
  </div>

  <div class="sentence-container">
      <div id="sentenceBox"></div>
  </div>
  
  <div class="guide-container">
      <h2 id="guideText">지금 어디 계신가요?</h2>
      <div id="guideBox"></div> </div>

  <div id="inputArea">
      
      <div class="grid-btn-container" id="btnContainer"></div>

      <div id="customInputArea">
          <textarea id="customInput" placeholder="여기를 눌러서 자세한 내용을 입력해주세요"></textarea>
          <div class="char-count" id="charCount">0/200</div>
          <button class="blue-btn full-width" onclick="submitCustomInput()" style="margin-top:10px;">입력 완료</button>
      </div>

      <div id="step4-content" class="step-special-content">
          <div class="dial-wrapper" id="dialWrapper" style="display:none;">
              <div class="dial-highlight"></div>
              <div class="dial-column" id="dialLine"></div><div class="dial-label">호선</div>
              <div class="dial-column" id="dial1"></div><div class="dial-column" id="dial2"></div><div class="dial-label">편성</div>
              <div class="dial-column" id="dial3"></div><div class="dial-label">호차</div>
          </div>
          <div class="dial-wrapper" id="stationDialWrapper" style="display:none;">
             <div class="dial-highlight"></div>
             <div class="station-dial-column" id="stationDialLeft"></div>
             <div class="station-dial-column" id="stationDialRight"></div>
          </div>
          <button class="blue-btn full-width" id="dialNextBtn" onclick="goToStep5()">다음</button>
      </div>

      <div id="step5-content" class="step-special-content" style="height:100%; display:none;">
          <div class="message-img-container">
              <img src="message.png" alt="안내 메시지" onerror="this.style.display='none'">
          </div>
          <div class="confirm-text" id="finalConfirmText" style="text-align:center; margin-bottom:20px; font-size:0.95rem; color:#444;"></div>
          <button class="blue-btn full-width" id="finalSubmitBtn" onclick="finalSmsSend()">🚀 신고 전송하기</button>
      </div>

  </div>

  <div class="pagination-dots" id="paginationDots">
      <div class="dot active"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
  </div>

  <script>
    // ---------------------------------------------------------
    // 1. 데이터 정의 (아이콘 SVG 포함)
    // ---------------------------------------------------------
    
    // SVG 아이콘 모음
    const ICONS = {
        train: `<svg viewBox="0 0 24 24"><path d="M12 2c-4 0-8 .5-8 4v9.5C4 17.43 5.57 19 7.5 19L6 22h2l1.5-3h5l1.5 3h2l-1.5-3c1.93 0 3.5-1.57 3.5-3.5V6c0-3.5-4-4-8-4zm0 2c3.5 0 6 .5 6 2v1H6V6c0-1.5 2.5-2 6-2zm6 11.5c0 .83-.67 1.5-1.5 1.5h-9c-.83 0-1.5-.67-1.5-1.5V9h12v6.5z"/></svg>`,
        walk: `<svg viewBox="0 0 14 23" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.51141 21.5414L6.84188 16.1452L4.7325 13.447C4.4208 12.9193 4.25634 12.3176 4.25625 11.7047V5.63297H4.9936C5.49446 5.63383 5.97454 5.83328 6.32857 6.18758C6.6826 6.54187 6.88169 7.0221 6.88219 7.52297V15.1411" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.50625 5.63297C3.50625 5.21875 3.84204 4.88297 4.25625 4.88297L4.9949 4.88297C5.69432 4.88418 6.36472 5.16269 6.8591 5.65744C7.35348 6.1522 7.6315 6.8228 7.63219 7.52222L7.63219 15.8911H7.55295L10.5162 21.8811L9.17173 22.5462L6.2037 16.5466L4.14164 13.909C4.12161 13.8833 4.10327 13.8564 4.08673 13.8284C3.70681 13.1852 3.50636 12.4518 3.50625 11.7048V5.63297ZM6.13219 14.0197V7.52339C6.13181 7.2212 6.01165 6.93148 5.79804 6.71771C5.58765 6.50716 5.30351 6.38722 5.00625 6.38307V11.7047C5.00634 12.1681 5.12673 12.6234 5.3553 13.026L6.13219 14.0197Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.85519 6.08316L1.5 9.4461V13.3908H0V9.13595C0 8.93734 0.0787804 8.74683 0.219059 8.60623L3.79331 5.02373L4.85519 6.08316Z" fill="white"/><path d="M12.383 12.8391L8.44781 10.1962V8.10187L13.3547 11.5214L12.383 12.8391Z" fill="white"/><path d="M2.95078 22.3476L1.94625 21.0862L5.71172 17.2641L6.43969 18.7673L2.95078 22.3476Z" fill="white"/><path d="M6.88312 3.87937C7.85083 3.87937 8.63531 3.09489 8.63531 2.12719C8.63531 1.15948 7.85083 0.375 6.88312 0.375C5.91542 0.375 5.13094 1.15948 5.13094 2.12719C5.13094 3.09489 5.91542 3.87937 6.88312 3.87937Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.88312 0.75C6.12252 0.75 5.50594 1.36659 5.50594 2.12719C5.50594 2.88779 6.12252 3.50437 6.88312 3.50437C7.64372 3.50437 8.26031 2.88779 8.26031 2.12719C8.26031 1.36659 7.64372 0.75 6.88312 0.75ZM4.75594 2.12719C4.75594 0.952374 5.70831 0 6.88312 0C8.05794 0 9.01031 0.952374 9.01031 2.12719C9.01031 3.302 8.05794 4.25437 6.88312 4.25437C5.70831 4.25437 4.75594 3.302 4.75594 2.12719Z" fill="white"/></svg>`,
        dollar: `<svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="9" r="9" fill="white"/><path d="M9 4.30872C10.1087 4.33855 11.315 4.81501 12 5.5L9 4.30872ZM9 4.30872C7.6809 4.27322 6.5 4.86998 6.5 6.50001C6.5 9.5 12 8 12 11C12 12.711 10.5362 13.4462 9 13.391M9 4.30872V13.391M9 4.30872V2.5M9 13.391C7.8428 13.3494 6.64448 12.8593 6 12L9 13.391ZM9 13.391V15.5" stroke="#135BEC" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
        fist: `<svg width="22" height="17" viewBox="0 0 22 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19.7135 12.8306C19.7135 12.8306 19.3736 7.98718 19.8835 7.98718C20.3933 7.98718 20.8182 10.1115 21.0731 10.7063C21.328 11.3011 21.1581 13.2555 21.0731 14.2751C21.0051 15.0909 20.1384 15.6914 19.7135 15.8896H15.6348C14.6152 15.8896 13.7655 16.1445 12.151 16.5694C10.8594 16.9093 10.0833 16.711 9.85672 16.5694C9.24492 15.9576 9.7151 14.8983 10.0267 14.4451C10.2249 14.5867 10.8764 14.87 11.8961 14.87C12.9157 14.87 13.6805 14.2468 13.9354 13.9353C14.2186 14.3884 15.193 15.2778 16.8245 15.2098C18.4559 15.1419 19.4303 13.5954 19.7135 12.8306Z" fill="white"/><path d="M0 4.07869V12.0661C0.141621 12.5193 0.747758 13.4256 2.03934 13.4256C3.33092 13.4256 3.88041 12.8592 3.99371 12.5759V6.62785C4.02203 6.54288 4.12966 6.37293 4.33359 6.37293C4.53753 6.37293 4.70181 6.54288 4.75846 6.62785V12.8308C4.9284 13.1424 5.5572 13.7655 6.71282 13.7655C7.86845 13.7655 8.49724 13.1424 8.66719 12.8308L8.58222 5.94807C8.61054 5.8631 8.73517 5.69315 9.00708 5.69315C9.27899 5.69315 9.34697 5.8631 9.34697 5.94807V12.8308C9.46027 13.2557 10.0437 14.1054 11.4713 14.1054C12.8988 14.1054 13.369 13.2557 13.4256 12.8308V5.35326C13.454 5.23997 13.5956 5.01337 13.9355 5.01337C14.2754 5.01337 14.3603 5.23997 14.3603 5.35326V12.8308C14.4736 13.369 15.0571 14.4283 16.4847 14.3603C17.9122 14.2924 18.5523 13.3124 18.6939 12.8308C18.7506 9.8568 18.8639 3.78977 18.8639 3.31393C18.8639 2.71912 17.5893 1.5295 16.9095 1.18961C16.3657 0.917702 14.5869 0.793076 13.7655 0.764752C13.454 0.509835 12.5589 0 11.4713 0C10.3836 0 9.37529 0.623131 9.00708 0.934697C8.61054 0.736428 7.57954 0.33989 6.62785 0.33989C5.67616 0.33989 4.70181 1.41622 4.33359 1.95438C3.82376 1.89773 2.61715 1.81842 1.86939 1.95438C1.12164 2.09033 0.311566 3.42723 0 4.07869Z" fill="white"/></svg>`,
        temp: `<svg width="8" height="18" viewBox="0 0 8 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.71428 14.0629C5.71412 14.3723 5.62758 14.6756 5.46413 14.9398C5.30067 15.2041 5.06659 15.4189 4.78746 15.561C4.50833 15.7031 4.19489 15.767 3.8814 15.7456C3.56791 15.7242 3.26642 15.6184 3.00989 15.4397C2.75336 15.261 2.55165 15.0164 2.4268 14.7325C2.30196 14.4486 2.25879 14.1364 2.302 13.83C2.34521 13.5236 2.47315 13.2348 2.67183 12.9951C2.87052 12.7554 3.1323 12.5741 3.42857 12.471V7.31273C3.42857 7.16354 3.48877 7.02047 3.59594 6.91497C3.7031 6.80948 3.84845 6.75022 4 6.75022C4.15155 6.75022 4.2969 6.80948 4.40406 6.91497C4.51122 7.02047 4.57143 7.16354 4.57143 7.31273V12.471C4.9058 12.5874 5.19528 12.803 5.39997 13.0881C5.60465 13.3732 5.71447 13.7138 5.71428 14.0629Z" fill="white"/><path d="M1.14286 2.81259C1.14286 2.06664 1.44388 1.35125 1.9797 0.823788C2.51551 0.296326 3.24224 0 4 0C4.75776 0 5.48449 0.296326 6.0203 0.823788C6.55612 1.35125 6.85714 2.06664 6.85714 2.81259V11.3066C7.40838 11.8604 7.78125 12.5622 7.929 13.3238C8.07674 14.0854 7.99279 14.873 7.68766 15.5878C7.38253 16.3026 6.86981 16.9128 6.21379 17.342C5.55777 17.7711 4.78765 18 4 18C3.21235 18 2.44223 17.7711 1.78621 17.342C1.13019 16.9128 0.617471 16.3026 0.312342 15.5878C0.00721291 14.873 -0.0767425 14.0854 0.0710032 13.3238C0.218749 12.5622 0.591619 11.8604 1.14286 11.3066V2.81259ZM4 1.12504C3.54534 1.12504 3.10931 1.30283 2.78782 1.61931C2.46633 1.93579 2.28572 2.36502 2.28572 2.81259V11.7983L2.09486 11.967C1.66351 12.3467 1.35946 12.8464 1.22294 13.4C1.08642 13.9536 1.12386 14.5349 1.33032 15.067C1.53678 15.5992 1.90251 16.0571 2.37913 16.3801C2.85574 16.7032 3.42076 16.8761 3.99943 16.8761C4.57809 16.8761 5.14312 16.7032 5.61973 16.3801C6.09635 16.0571 6.46208 15.5992 6.66854 15.067C6.87499 14.5349 6.91244 13.9536 6.77592 13.4C6.6394 12.8464 6.33535 12.3467 5.904 11.967L5.71429 11.7983V2.81259C5.71429 2.36502 5.53367 1.93579 5.21218 1.61931C4.89069 1.30283 4.45466 1.12504 4 1.12504Z" fill="white"/></svg>`,
        angry: `<svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>`,
        trash: `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.375 1.6875V2.8125H16.3125C16.4617 2.8125 16.6048 2.87176 16.7102 2.97725C16.8157 3.08274 16.875 3.22582 16.875 3.375C16.875 3.52418 16.8157 3.66726 16.7102 3.77275C16.6048 3.87824 16.4617 3.9375 16.3125 3.9375H15.7073L14.7476 15.93C14.7024 16.4938 14.4464 17.02 14.0307 17.4036C13.615 17.7872 13.07 18.0001 12.5044 18H5.49563C4.92997 18.0001 4.38502 17.7872 3.96931 17.4036C3.5536 17.02 3.29763 16.4938 3.25238 15.93L2.29275 3.9375H1.6875C1.53832 3.9375 1.39524 3.87824 1.28975 3.77275C1.18426 3.66726 1.125 3.52418 1.125 3.375C1.125 3.22582 1.18426 3.08274 1.28975 2.97725C1.39524 2.87176 1.53832 2.8125 1.6875 2.8125H5.625V1.6875C5.625 1.23995 5.80279 0.810725 6.11926 0.494257C6.43572 0.17779 6.86495 0 7.3125 0H10.6875C11.1351 0 11.5643 0.17779 11.8807 0.494257C12.1972 0.810725 12.375 1.23995 12.375 1.6875ZM6.75 1.6875V2.8125H11.25V1.6875C11.25 1.53832 11.1907 1.39524 11.0852 1.28975C10.9798 1.18426 10.8367 1.125 10.6875 1.125H7.3125C7.16332 1.125 7.02024 1.18426 6.91475 1.28975C6.80926 1.39524 6.75 1.53832 6.75 1.6875ZM5.0625 5.65762L5.625 15.2201C5.628 15.2949 5.64589 15.3683 5.6776 15.436C5.70932 15.5038 5.75423 15.5645 5.80972 15.6147C5.8652 15.6649 5.93013 15.7035 6.00072 15.7283C6.07131 15.7531 6.14613 15.7635 6.2208 15.759C6.29548 15.7545 6.36851 15.7352 6.43562 15.7021C6.50273 15.6691 6.56257 15.623 6.61164 15.5665C6.66071 15.51 6.69802 15.4443 6.72139 15.3733C6.74476 15.3022 6.75372 15.2272 6.74775 15.1526L6.18525 5.59012C6.18225 5.51537 6.16436 5.44197 6.13265 5.37422C6.10093 5.30647 6.05602 5.24572 6.00053 5.19554C5.94505 5.14535 5.88012 5.10674 5.80953 5.08196C5.73894 5.05718 5.66412 5.04673 5.58945 5.05122C5.51477 5.0557 5.44174 5.07505 5.37463 5.1081C5.30752 5.14116 5.24768 5.18728 5.19861 5.24375C5.14954 5.30022 5.11223 5.36591 5.08886 5.43697C5.06549 5.50804 5.05653 5.58305 5.0625 5.65762ZM12.4088 5.06363C12.2599 5.05502 12.1137 5.10588 12.0023 5.20503C11.8909 5.30418 11.8234 5.44351 11.8148 5.59238L11.2523 15.1549C11.2474 15.3015 11.3001 15.4443 11.399 15.5526C11.498 15.661 11.6353 15.7264 11.7818 15.7349C11.9283 15.7434 12.0723 15.6944 12.1831 15.5982C12.294 15.502 12.3628 15.3664 12.375 15.2201L12.9375 5.65762C12.9461 5.50875 12.8952 5.36255 12.7961 5.25117C12.6969 5.13978 12.5576 5.07232 12.4088 5.06363ZM9 5.0625C8.85082 5.0625 8.70774 5.12176 8.60225 5.22725C8.49676 5.33274 8.4375 5.47582 8.4375 5.625V15.1875C8.4375 15.3367 8.49676 15.4798 8.60225 15.5852C8.70774 15.6907 8.85082 15.75 9 15.75C9.14918 15.75 9.29226 15.6907 9.39775 15.5852C9.50324 15.4798 9.5625 15.3367 9.5625 15.1875V5.625C9.5625 5.47582 9.50324 5.33274 9.39775 5.22725C9.29226 5.12176 9.14918 5.0625 9 5.0625Z" fill="white"/></svg>`,
        tool: `<svg width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.7039 2.74922C17.6627 2.66429 17.6017 2.59045 17.5261 2.53387C17.4505 2.47729 17.3625 2.4396 17.2693 2.42395C17.1762 2.4083 17.0807 2.41514 16.9908 2.44389C16.9008 2.47265 16.819 2.52249 16.7523 2.58926L14.954 4.3875C14.8807 4.45986 14.7818 4.50042 14.6788 4.50042C14.5757 4.50042 14.4768 4.45986 14.4035 4.3875L13.6132 3.59719C13.5409 3.52373 13.5004 3.4248 13.5004 3.32174C13.5004 3.21868 13.5409 3.11975 13.6132 3.04629L15.4019 1.25719C15.4704 1.18875 15.521 1.10459 15.5494 1.01206C15.5778 0.919531 15.5831 0.821454 15.5648 0.726408C15.5465 0.631363 15.5052 0.542244 15.4445 0.466848C15.3838 0.391451 15.3056 0.332074 15.2166 0.293909C13.8206 -0.303747 12.0849 0.031995 10.9979 1.1127C10.0554 2.04821 9.7734 3.50332 10.2245 5.10996C10.2439 5.17892 10.2438 5.25192 10.2242 5.32082C10.2046 5.38972 10.1662 5.45183 10.1134 5.5002L4.03484 11.148C3.83347 11.3292 3.67109 11.5495 3.55759 11.7954C3.44409 12.0414 3.38185 12.3079 3.37467 12.5786C3.36749 12.8494 3.41551 13.1188 3.51581 13.3704C3.61611 13.6221 3.76659 13.8506 3.95808 14.0422C4.14957 14.2338 4.37806 14.3844 4.62962 14.4848C4.88119 14.5852 5.15057 14.6334 5.42136 14.6263C5.69214 14.6193 5.95865 14.5572 6.20465 14.4438C6.45066 14.3304 6.671 14.1682 6.85227 13.9669L12.5507 7.87781C12.5985 7.82615 12.6593 7.78848 12.7269 7.76884C12.7944 7.74921 12.866 7.74835 12.9339 7.76637C14.516 8.18824 15.9574 7.90699 16.8866 6.99293C17.4209 6.46559 17.7939 5.72133 17.9374 4.89375C18.072 4.11328 17.987 3.33141 17.7039 2.74922Z" fill="white"/><path d="M15.3749 11.616C14.8848 11.2085 13.8378 10.266 12.7708 9.28793L10.4481 11.7703C11.4409 12.825 12.3395 13.8041 12.7332 14.2622L12.7357 14.265C12.8303 14.3733 12.946 14.461 13.0757 14.5229C13.2055 14.5848 13.3465 14.6196 13.4901 14.625H13.5288C13.805 14.6242 14.07 14.5154 14.2671 14.322L15.4448 13.1463C15.5469 13.0445 15.6266 12.9224 15.6788 12.788C15.731 12.6535 15.7545 12.5097 15.7479 12.3656C15.7413 12.2215 15.7047 12.0805 15.6404 11.9514C15.5761 11.8223 15.4855 11.708 15.3745 11.616H15.3749Z" fill="white"/><path d="M4.1674 5.85527C4.16797 5.72184 4.13146 5.59088 4.06193 5.47699C4.05219 5.46159 4.04698 5.44376 4.04691 5.42554C4.04683 5.40731 4.05189 5.38944 4.0615 5.37396C4.07112 5.35848 4.08489 5.34602 4.10126 5.338C4.11762 5.32998 4.13591 5.32674 4.15404 5.32863H4.15615C4.18568 5.3318 4.34353 5.35465 4.56747 5.4907C4.73376 5.5916 5.20837 5.91539 6.03454 6.74016C6.02575 7.02106 6.11555 7.29619 6.28837 7.51781L8.61677 5.35395C8.41055 5.15868 8.14109 5.04391 7.8574 5.03051C7.85475 5.02762 7.85228 5.02457 7.85001 5.02137L7.82716 4.99711L6.96443 3.80742C6.86852 3.67719 6.80562 3.52564 6.78113 3.36577C6.75663 3.20589 6.77128 3.04247 6.8238 2.88949C6.90294 2.66165 7.04687 2.46188 7.23794 2.31469C7.43798 2.15965 7.88939 1.97297 8.27294 1.94133C8.45161 1.92656 8.63148 1.93769 8.80697 1.97438C8.89104 1.99466 8.97391 2.01966 9.05517 2.04926C9.0798 2.05846 9.10506 2.06586 9.13076 2.07141C9.24235 2.09613 9.35883 2.08627 9.46468 2.04314C9.57052 2.00001 9.66072 1.92565 9.72326 1.82998C9.7858 1.7343 9.8177 1.62185 9.81473 1.50759C9.81175 1.39333 9.77404 1.28268 9.70662 1.19039C9.69747 1.17809 9.66021 1.12746 9.60501 1.05926C9.50203 0.92937 9.38919 0.807604 9.26751 0.69504C8.98099 0.43629 8.23849 1.16107e-06 7.26361 1.16107e-06C6.70571 -0.000407438 6.15299 0.107032 5.63587 0.316407C4.28693 0.859571 3.4045 1.60242 2.9988 1.99652L2.99564 1.99969C2.72265 2.26924 2.4699 2.55854 2.23943 2.86524C2.1523 2.9881 2.09566 3.12994 2.07419 3.27902C2.06416 3.34245 2.03081 3.39985 1.98067 3.43997C1.93053 3.4801 1.86722 3.50006 1.80314 3.49594C1.79048 3.49594 1.77747 3.49594 1.76482 3.49594C1.57316 3.49466 1.38878 3.56922 1.25189 3.70336L0.216536 4.7127L0.205286 4.72395C0.0750484 4.85925 0.001595 5.03931 2.56717e-05 5.22711C-0.00154366 5.41491 0.0688903 5.59617 0.196848 5.73363C0.203528 5.74067 0.209856 5.74734 0.216887 5.75402L1.88681 7.38281C2.0244 7.51762 2.20922 7.59332 2.40185 7.59375C2.5935 7.59503 2.77788 7.52047 2.91478 7.38633L3.95048 6.37418C4.01956 6.30663 4.07435 6.22589 4.11162 6.13675C4.14888 6.04761 4.16785 5.95189 4.1674 5.85527Z" fill="white"/></svg>`,
        edit: `<svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`,
        alert: `<svg viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm1 17h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
        ticket: `<svg viewBox="0 0 24 24"><path d="M13 13v8h8v-8h-8zM3 21h8v-8H3v8zM3 3v8h8V3H3zm13.66-1.31L11 7.34 16.66 13l5.66-5.66-5.66-5.65z"/></svg>`,
        mic: `<svg viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 2.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>`,
        wc: `<svg viewBox="0 0 24 24"><path d="M5.5 22v-7.5H4V9c0-1.1.9-2 2-2h3c1.1 0 2 .9 2 2v5.5H9.5V22h-4zM18 22v-6h3l-2.54-7.63C18.18 7.55 17.42 7 16.56 7h-.12c-.86 0-1.63.55-1.9 1.37L12 16h3v6h3zM7.5 6c1.11 0 2-.89 2-2s-.89-2-2-2-2 .89-2 2 .89 2 2 2zm9 0c1.11 0 2-.89 2-2s-.89-2-2-2-2 .89-2 2 .89 2 2 2z"/></svg>`
    };

 
        const STATION_DB = {
      '1호선': ['다대포해수욕장','다대포항','낫개','신장림','장림','동매','신평','하단','당리','사하','괴정','대티','서대신','동대신','토성','자갈치','남포','중앙','부산역','초량','부산진','좌천','범일','범내골','서면','부전','양정','시청','연산','교대','동래','명륜','온천장','부산대','장전','구서','두실','남산','범어사','노포'],
      '2호선': ['장산','중동','해운대','동백','벡스코','센텀시티','민락','수영','광안','금련산','남천','경성대부경대','대연','못골','지게골','문현','국제금융센터','전포','서면','부암','가야','동의대','개금','냉정','주례','감전','사상','덕포','모덕','모라','구남','구명','덕천','수정','화명','율리','동원','금곡','호포','증산','부산대양산','남양산','양산'],
      '3호선': ['수영','망미','배산','물만골','연산','거제','종합운동장','사직','미남','만덕','남산정','숙등','덕천','구포','강서구청','체육공원','대저'],
      '4호선': ['미남','동래','수안','낙민','충렬사','명장','서동','금사','반여농산물','석대','영산대','윗반송','고촌','안평']
    };

    // 하위 선택지 데이터
    const DETAIL_OPTIONS = {
        '온도 조절이 필요해요': [ { text: '더워요', val: '더워요' }, { text: '추워요', val: '추워요' } ],
        '민폐를 끼쳐요': [ { text: '위협을 해요', val: '위협을 해요' }, { text: '욕설을 해요', val: '욕설을 해요' }, { text: '구걸을 해요', val: '구걸을 해요' }, { text: '종교 권유를 해요', val: '종교 권유를 해요' }, { text: '시끄럽게 떠들어요', val: '시끄럽게 떠들어요' }, { text: '음식물을 먹어요', val: '음식물을 먹어요' }, { text: '빈좌석을 못쓰게 해요', val: '빈좌석을 못쓰게 해요' }, { text: '의자에 누워있어요', val: '의자에 누워있어요' } ],
        '청소가 필요해요': [ { text: '광고지가 붙어있어요', val: '광고지가 붙어있어요' }, { text: '의자 시트가 더러워요', val: '의자 시트가 더러워요' }, { text: '바닥에 쓰레기가 많아요', val: '바닥에 쓰레기가 많아요' }, { text: '바닥에 액체가 흘렀어요', val: '바닥에 액체가 흘렀어요' }, { text: '벽이 더러워요', val: '벽이 더러워요' }, { text: '손잡이가 더러워요', val: '손잡이가 더러워요' } ],
        '시설물이 파손됐어요': [ { text: '의자가 파손됐어요', val: '의자가 파손됐어요' }, { text: '손잡이가 파손됐어요', val: '손잡이가 파손됐어요' }, { text: '유리창이 깨져있어요', val: '유리창이 깨져있어요' }, { text: '방송 스피커가 고장났어요', val: '방송 스피커가 고장났어요' }, { text: '안내판이 고장났어요', val: '안내판이 고장났어요' }, { text: '조명이 고장났어요', val: '조명이 고장났어요' } ],
        '소란을 피워요': [ { text: '싸움이 일어났어요', val: '싸움이 일어났어요' }, { text: '위협을 해요', val: '위협을 해요' }, { text: '욕설을 해요', val: '욕설을 해요' }, { text: '종교 권유를 해요', val: '종교 권유를 해요' }, { text: '전단지를 돌려요', val: '전단지를 돌려요' }, { text: '연설을 해요', val: '연설을 해요' }, { text: '의자에 누워있어요', val: '의자에 누워있어요' } ],
        '청소가 안돼있어요': [ { text: '광고지가 붙어있어요', val: '광고지가 붙어있어요' }, { text: '쓰레기통이 넘쳐요', val: '쓰레기통이 넘쳐요' }, { text: '바닥에 쓰레기가 많아요', val: '바닥에 쓰레기가 많아요' }, { text: '바닥에 액체가 흘렀어요', val: '바닥에 액체가 흘렀어요' }, { text: '의자가 더러워요', val: '의자가 더러워요' }, { text: '벽이 더러워요', val: '벽이 더러워요' } ],
        '시설물이 고장났어요': [ { text: '개찰구가 고장났어요', val: '개찰구가 고장났어요' }, { text: '에스컬레이터가 고장났어요', val: '에스컬레이터가 고장났어요' }, { text: '엘리베이터가 고장났어요', val: '엘리베이터가 고장났어요' }, { text: '계단이 깨져있어요', val: '계단이 깨져있어요' }, { text: '발매기/충전기가 고장났어요', val: '발매기/충전기가 고장났어요' }, { text: '보관함이 고장났어요', val: '보관함이 고장났어요' }, { text: '자판기가 고장났어요', val: '자판기가 고장났어요' }, { text: '유리창이 깨져있어요', val: '유리창이 깨져있어요' }, { text: '안내판이 고장났어요', val: '안내판이 고장났어요' }, { text: '조명이 고장났어요', val: '조명이 고장났어요' } ],
        '화장실에 문제가 있어요': [ { text: '청소가 필요해요(남자)', val: '청소가 필요해요(남자)' }, { text: '청소가 필요해요(여자)', val: '청소가 필요해요(여자)' }, { text: '시설이 고장났어요(남자)', val: '시설이 고장났어요(남자)' }, { text: '시설이 고장났어요(여자)', val: '시설이 고장났어요(여자)' }, { text: '휴지가 없어요(남자)', val: '휴지가 없어요(남자)' }, { text: '휴지가 없어요(여자)', val: '휴지가 없어요(여자)' } ],
    };

    // ---------------------------------------------------------
    // 2. 상태 관리 및 단계별 버튼 설정
    // ---------------------------------------------------------
    const els = {
        topHeader: document.querySelector('.top-header'),
        headerTitle: document.getElementById('headerTitle'),
        backBtn: document.getElementById('backBtn'),
        guideBox: document.getElementById('guideBox'),
        guideText: document.getElementById('guideText'),
        btnContainer: document.getElementById('btnContainer'),
        sentenceBox: document.getElementById('sentenceBox'),
        customInputArea: document.getElementById('customInputArea'),
        customInput: document.getElementById('customInput'),
        charCount: document.getElementById('charCount'),
        step4Content: document.getElementById('step4-content'),
        step5Content: document.getElementById('step5-content'), 
        dialWrapper: document.getElementById('dialWrapper'),
        stationDialWrapper: document.getElementById('stationDialWrapper'),
        dialNextBtn: document.getElementById('dialNextBtn'),
        paginationDots: document.getElementById('paginationDots')
    };

    let currentStep = 1;
    let report = { place: '', situation: '', detailSituation: '', line: '', detail: '', isInside: true };
    let dialValues = { line: 1, d1: 0, d2: 0, d3: 1 };
    let generatedCode = ''; 
    
    // Step별 버튼 구성
    const stepButtons = {
      1: [ 
          { text: '지하철 내부', icon: ICONS.train, action: () => { report.isInside = true; nextStep('place', '지하철 내부에'); } }, 
          { text: '지하철 외부', icon: ICONS.walk, action: () => { report.isInside = false; nextStep('place', '지하철 외부에'); } } 
      ],
      2: { 
          inside: [ 
            { text: '잡상인이 있어요', icon: ICONS.dollar, action: () => handleSituationStep('잡상인이 있어요') }, 
            { text: '싸움이 일어났어요', icon: ICONS.fist, action: () => handleSituationStep('싸움이 일어났어요') }, 
            { text: '온도 조절이 필요해요', icon: ICONS.temp, action: () => handleSituationStep('온도 조절이 필요해요') }, 
            { text: '민폐를 끼쳐요', icon: ICONS.angry, action: () => handleSituationStep('민폐를 끼쳐요') }, 
            { text: '청소가 필요해요', icon: ICONS.trash, action: () => handleSituationStep('청소가 필요해요') }, 
            { text: '시설물이 고장났어요', icon: ICONS.tool, action: () => handleSituationStep('시설물이 파손됐어요') }, 
            { text: '직접 입력', icon: ICONS.edit, fullWidth: true, action: () => handleSituationStep('직접 입력') } 
          ], 
          outside: [ 
            { text: '구걸인/노숙인', icon: ICONS.dollar, action: () => handleSituationStep('구걸인/노숙인이 있어요') }, 
            { text: '무임승차', icon: ICONS.ticket, action: () => handleSituationStep('무임승차를 봤어요') }, 
            { text: '소란을 피워요', icon: ICONS.mic, action: () => handleSituationStep('소란을 피워요') }, 
            { text: '청소가 안돼있어요', icon: ICONS.trash, action: () => handleSituationStep('청소가 안돼있어요') }, 
            { text: '시설물이 고장났어요', icon: ICONS.tool, action: () => handleSituationStep('시설물이 고장났어요') }, 
            { text: '화장실 문제', icon: ICONS.wc, action: () => handleSituationStep('화장실에 문제가 있어요') }, 
            { text: '직접 입력', icon: ICONS.edit, fullWidth: true, action: () => handleSituationStep('직접 입력') } 
          ] 
      },
      3: [ 
          { text: '1호선', icon: ICONS.train, action: () => nextStep('line', '1호선') }, 
          { text: '2호선', icon: ICONS.train, action: () => nextStep('line', '2호선') }, 
          { text: '3호선', icon: ICONS.train, action: () => nextStep('line', '3호선') }, 
          { text: '4호선', icon: ICONS.train, action: () => nextStep('line', '4호선') } 
      ]
    };

    // ---------------------------------------------------------
    // 3. 네비게이션 및 UI 업데이트 로직
    // ---------------------------------------------------------

    function pushHistoryState(stateObj) {
        const newState = { ...stateObj, report: JSON.parse(JSON.stringify(report)) };
        history.pushState(newState, null, null);
    }

    function goBack() {
        if (currentStep > 1) {
            history.back();
        }
    }

    function updateSentenceBox() {
        let text = makeSentence();
        if (text) {
             els.sentenceBox.style.display = 'flex';
             els.sentenceBox.innerText = text;
        } else {
             // Step 1 등 초기 상태
             els.sentenceBox.style.display = 'none';
        }
    }

    function makeSentence() {
        let sentence = '';
        if (report.place) sentence += report.place + ' ';
        if (report.situation) { 
            // 괄호 안에 상황 넣기 등 포맷팅
            sentence += '( ';
            sentence += (report.situation === '직접 입력') 
                ? (report.detailSituation ? report.detailSituation : '입력 중...') 
                : (report.situation + (report.detailSituation ? ` - ${report.detailSituation}` : '')); 
            sentence += ' )';
        }
        return sentence;
    }

    function updateHeaderAndDots(step) {
        // 헤더 타이틀 변경
        const titles = { 1: 'Location', 2: 'Situation', 3: 'Line', 4: 'Detail', 5: 'Complete' };
        els.headerTitle.innerText = titles[step] || 'Report';

        // Back 버튼 숨김 처리 (Step 1일때)
        els.backBtn.style.display = (step === 1) ? 'none' : 'block';

        // Dots 업데이트
        const dots = document.querySelectorAll('.dot');
        dots.forEach((dot, idx) => {
            if (idx === step - 1) dot.classList.add('active');
            else dot.classList.remove('active');
        });
    }

    function updateGuideText(step) {
        const texts = { 
            1: "지금 어디 계신가요?", 
            2: "어떤 상황인가요?", 
            3: "몇 호선인가요?", 
            4: (report.isInside ? "차량 번호를 입력해주세요" : "현재 역을 선택해주세요"),
            5: "신고 내용을 확인해주세요"
        };
        
        els.guideText.innerText = texts[step] || "";

        // 기존 내부 가이드 컨텐츠 초기화
        const oldContent = document.getElementById('guide-inner-content');
        if(oldContent) oldContent.remove();

        // Step 4 내부일 때 이미지 추가
        if (step === 4 && report.isInside) {
            const div = document.createElement('div');
            div.id = 'guide-inner-content';
            div.innerHTML = `
                <img src="train_num_guide.jpg" alt="차량번호 안내" onerror="this.style.display='none'">
                <p>통로문 위나 차체 벽면 번호를 확인해주세요.</p>
            `;
            els.guideBox.appendChild(div);
        }
    }

    function renderButtons(step) {
        els.btnContainer.innerHTML = '';
        
        // 버튼 렌더링이 필요없는 단계
        if (step === 4 || step === 5) {
            return;
        }

        // 1열(Full Width) vs 2열 그리드 결정
        if (step === 1) {
            // Step 1은 전체 너비 버튼으로 (디자인 1번 파일 참고)
            stepButtons[1].forEach(btn => {
                const b = document.createElement('button');
                b.className = 'blue-btn full-width'; // 꽉 찬 버튼
                b.onclick = btn.action;
                b.innerHTML = `${btn.icon} <span>${btn.text}</span>`;
                els.btnContainer.appendChild(b);
            });
        } else {
            // Step 2, 3은 그리드
            const currentButtons = (step === 2) 
                ? (report.isInside ? stepButtons[2].inside : stepButtons[2].outside) 
                : stepButtons[step];
            
            currentButtons.forEach(btn => {
                const b = document.createElement('button');
                b.className = 'blue-btn';
                if (btn.fullWidth) b.classList.add('full-width'); // 직접입력 등
                b.onclick = btn.action;
                b.innerHTML = `${btn.icon} <span>${btn.text}</span>`;
                els.btnContainer.appendChild(b);
            });
        }
    }

    function updateUI(step) {
        updateHeaderAndDots(step);
        els.btnContainer.style.display = 'grid'; // 기본 복구
        els.customInputArea.style.display = 'none';
        els.step4Content.style.display = 'none';
        els.step5Content.style.display = 'none';

        if (step === 4) {
            els.btnContainer.style.display = 'none'; // 버튼 숨김
            els.step4Content.style.display = 'flex';
            
            if (report.isInside) {
                els.dialWrapper.style.display = 'flex';
                els.stationDialWrapper.style.display = 'none';
                initDials(parseInt(report.line.replace('호선', '')) || 1);
            } else {
                els.dialWrapper.style.display = 'none';
                els.stationDialWrapper.style.display = 'flex';
                renderStationDials(report.line);
            }
        } else if (step === 5) {
            els.btnContainer.style.display = 'none';
            els.step5Content.style.display = 'flex';
            
            // 최종 확인 텍스트 생성
            const finalDiv = document.getElementById('finalConfirmText');
            finalDiv.innerHTML = `
                <strong style="font-size:1.1rem; color:#1e3a8a;">${makeSentence()}</strong><br><br>
                ${report.detail}
            `;
        } else {
            renderButtons(step);
        }
    }

    function nextStep(type, value) {
        if (type) report[type] = value;
        updateSentenceBox();
        
        if (currentStep < 5) { 
            currentStep++;
            // Step 3(호선) 건너뛰기 로직 (외부일 때 등 필요하면 추가)
            if (currentStep === 3 && report.isInside) { 
                // 내부는 호선을 따로 안 물어보고 바로 4단계로 가거나, 
                // 로직상 3단계(호선선택)가 필요하다면 유지. 
                // 여기서는 호선 선택이 필요하므로 유지.
            }
            
            pushHistoryState({ step: currentStep });
            updateGuideText(currentStep);
            updateUI(currentStep);
        }
    }

    function handleSituationStep(situationText) {
        report.situation = situationText; report.detailSituation = '';
        
        if (situationText === '직접 입력') {
            pushHistoryState({ step: currentStep, sub: 'customInput' });
            showCustomInputStep();
        } else if (DETAIL_OPTIONS[situationText]) {
            pushHistoryState({ step: currentStep, sub: 'detail', detailKey: situationText });
            showDetailStep(situationText);
        } else {
            nextStep(null, null);
        }
    }

    // 상세 선택 (Grid 버튼 재활용)
    function showDetailStep(situationText) {
        updateSentenceBox();
        els.guideText.innerText = "구체적으로 어떤가요?";
        els.customInputArea.style.display = 'none';
        els.btnContainer.innerHTML = ''; // 초기화

        const options = DETAIL_OPTIONS[situationText];
        options.forEach(opt => {
            const b = document.createElement('button');
            b.className = 'blue-btn'; // 그리드 스타일 적용
            b.innerHTML = `<span>${opt.text}</span>`; // 아이콘 없이 텍스트만
            b.onclick = () => { report.detailSituation = opt.val; nextStep(null, null); };
            els.btnContainer.appendChild(b);
        });
    }

    function showCustomInputStep() {
        updateSentenceBox();
        els.guideText.innerText = "내용을 입력해주세요";
        els.btnContainer.innerHTML = '';
        els.customInputArea.style.display = 'flex';
        els.customInput.value = ''; 
        setTimeout(() => els.customInput.focus(), 300);
    }

    function submitCustomInput() {
        const val = els.customInput.value;
        if (!val.trim()) { alert("내용을 입력해주세요."); return; }
        report.detailSituation = val; 
        // 직접 입력 후 다음 단계로
        nextStep(null, null);
    }

    // ---------------------------------------------------------
    // 4. 다이얼 로직 (Step 4)
    // ---------------------------------------------------------
    const DIAL_ITEM_HEIGHT = 50; 
    
    function createInfiniteDial(elementId, start, end, initialVal) {
        const container = document.getElementById(elementId);
        container.innerHTML = '';
        const spacer = document.createElement('div');
        spacer.style.height = `${DIAL_ITEM_HEIGHT}px`; spacer.style.flexShrink = '0';
        container.appendChild(spacer);
        
        const REPEAT_COUNT = 30; 
        const fragment = document.createDocumentFragment();
        for (let k = 0; k < REPEAT_COUNT; k++) {
            for (let i = start; i <= end; i++) {
                const item = document.createElement('div');
                item.className = 'dial-item';
                item.innerText = i;
                fragment.appendChild(item);
            }
        }
        container.appendChild(fragment);
        
        const spacerBottom = spacer.cloneNode(true);
        container.appendChild(spacerBottom);
        
        const distinctCount = (end - start + 1);
        
        container.addEventListener('scroll', () => {
             updateActiveInfiniteItem(container, elementId, distinctCount);
        });

        // 초기 위치 설정
        setTimeout(() => {
            // 중앙값 근처의 initialVal을 찾아 스크롤
            const middleSetIndex = Math.floor(REPEAT_COUNT / 2) * distinctCount;
            // offset 계산
            const offset = (initialVal - start); 
            const targetIndex = middleSetIndex + offset;
            container.scrollTop = targetIndex * DIAL_ITEM_HEIGHT;
        }, 100);
    }

    function updateActiveInfiniteItem(container, dialId, distinctCount) {
        let index = Math.round(container.scrollTop / DIAL_ITEM_HEIGHT);
        const items = container.querySelectorAll('.dial-item');
        if (index < 0) index = 0; if (index >= items.length) index = items.length - 1;
        
        // Active Class 관리
        const activeItem = container.querySelector('.dial-item.active');
        if(activeItem) activeItem.classList.remove('active');
        
        const target = items[index];
        if (target) {
            target.classList.add('active');
            const val = target.innerText; 
            
            if (dialId === 'dialLine') dialValues.line = val;
            if (dialId === 'dial1') dialValues.d1 = val;
            if (dialId === 'dial2') dialValues.d2 = val;
            if (dialId === 'dial3') dialValues.d3 = val;
            
            if (dialId === 'dialLine') report.line = dialValues.line + '호선';
            updateReportDetailFromDial();
        }
    }

    function updateReportDetailFromDial() {
        if (report.isInside) {
            report.detail = `${dialValues.d1}${dialValues.d2}편성 ${dialValues.d3}호차`;
        }
    }

    function initDials(initialLine) {
        createInfiniteDial('dialLine', 1, 4, initialLine);
        createInfiniteDial('dial1', 0, 9, 0);
        createInfiniteDial('dial2', 0, 9, 0);
        createInfiniteDial('dial3', 1, 9, 1);
    }

    function renderStationDials(lineName) {
        const stations = STATION_DB[lineName] || ['정보 없음'];
        const midIndex = Math.ceil(stations.length / 2);
        const leftStations = stations.slice(0, midIndex);
        const rightStations = stations.slice(midIndex);
        
        createStationDial('stationDialLeft', leftStations, 'left');
        createStationDial('stationDialRight', rightStations, 'right');
    }

    function createStationDial(containerId, list, side) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        const spacer = document.createElement('div');
        spacer.style.height = `${DIAL_ITEM_HEIGHT}px`; spacer.style.flexShrink = '0';
        container.appendChild(spacer);
        
        const fragment = document.createDocumentFragment();
        list.forEach(name => {
            const item = document.createElement('div');
            item.className = 'station-dial-item'; item.innerText = name; 
            fragment.appendChild(item);
        });
        container.appendChild(fragment);
        const spacerBottom = spacer.cloneNode(true);
        container.appendChild(spacerBottom);
        
        container.onscroll = function() { updateActiveStation(container, side); };
    }

    function updateActiveStation(container, side) {
        let index = Math.round(container.scrollTop / DIAL_ITEM_HEIGHT);
        const items = container.querySelectorAll('.station-dial-item');
        if (items.length === 0) return;
        if (index < 0) index = 0; if (index >= items.length) index = items.length - 1;

        // Reset active
        const prevActive = container.querySelector('.station-dial-item.active');
        if (prevActive) prevActive.classList.remove('active');
        
        const target = items[index];
        target.classList.add('active');

        // 반대편 비활성화 (한쪽만 선택 가능하게)
        const otherSideId = (side === 'left') ? 'stationDialRight' : 'stationDialLeft';
        const otherContainer = document.getElementById(otherSideId);
        const otherActive = otherContainer.querySelector('.station-dial-item.active');
        if (otherActive) otherActive.classList.remove('active');

        report.detail = target.innerText + '역에 있어요';
    }

    // ---------------------------------------------------------
    // 5. 전송 로직
    // ---------------------------------------------------------
    function generateReceiptCode() {
        return Array.from({length: 8}, () => Math.floor(Math.random() * 16).toString(16)).join('');
    }

    function goToStep5() {
        // 마지막 값 확정
        if(report.isInside) updateReportDetailFromDial();
        
        generatedCode = generateReceiptCode();
        
        // DB 저장 (비동기)
        let finalSentence = makeSentence() + " " + report.detail;
        fetch('save_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                content: finalSentence + `\n\n[접수코드: ${generatedCode}]`,
                token: generatedCode 
            }) 
        }).catch(err => console.error(err));

        nextStep(null, null);
    }

    function finalSmsSend() {
        let finalSentence = makeSentence() + " " + report.detail;
        let warningText = " ※ 허위 신고 시 철도안전법에 따라 처벌될 수 있습니다.\n\n";
        let displaySentence = warningText + "신고 내용: " + finalSentence + `\n\n[접수코드: ${generatedCode}]`;
        
        // 클립보드 복사
        navigator.clipboard.writeText(generatedCode).catch(err => {
            const ta = document.createElement('textarea');
            ta.value = generatedCode; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        });
        
        const targetEmail = "<?= isset($myEmail) ? $myEmail : 'busanminwon@gmail.com' ?>"; 
        const encodedBody = encodeURIComponent(displaySentence);
        const separator = navigator.userAgent.match(/iPhone/i) ? "&" : "?";
        location.href = "sms:" + targetEmail + separator + "body=" + encodedBody;
    }

    // ---------------------------------------------------------
    // 6. 히스토리(뒤로가기) 처리
    // ---------------------------------------------------------
    window.addEventListener('popstate', function(event) {
        const state = event.state;
        if (!state) { 
            // 초기 상태 복구
            currentStep = 1;
            report = { place: '', situation: '', detailSituation: '', line: '', detail: '', isInside: true };
            updateSentenceBox();
            updateGuideText(1);
            updateUI(1);
            return;
        }
        currentStep = state.step;
        if (state.report) report = state.report; 
        
        if (state.sub === 'customInput') showCustomInputStep();
        else if (state.sub === 'detail') showDetailStep(state.detailKey);
        else { 
            updateSentenceBox(); 
            updateGuideText(currentStep); 
            updateUI(currentStep); 
        }
    });

    // 초기 실행
    history.replaceState({ step: 1, report: JSON.parse(JSON.stringify(report)) }, null, null);
    updateSentenceBox();
    updateGuideText(1);
    updateUI(1);

  </script>
</body>
</html>