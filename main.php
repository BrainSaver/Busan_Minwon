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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <style>
    /* [기본 스타일] */
    :root { --primary-color: #3b82f6; --bg-color: #f8f9fb; --box-bg: #ffffff; --text-main: #222; --border-color: #e5e7eb; --dial-item-height: 50px; --dial-container-height: 150px; }
    body { margin: 0; background: var(--bg-color); font-family: "Pretendard", -apple-system, sans-serif; color: var(--text-main); display: flex; flex-direction: column; height: 100vh; overflow: hidden; padding: 10px; box-sizing: border-box; }
    
    /* [공통 UI 컴포넌트] */
    .content-box { background: var(--box-bg); border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 12px; text-align: center; margin-bottom: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.3s ease; }
    #sentenceBox { font-size: 1.5rem; color: #1e3a8a; min-height: 70px; line-height: 1.3; font-weight: 700; word-break: keep-all; }
    #guideBox { min-height: 60px; color: #333; }
    #guideText { font-size: 1.3rem; font-weight: 800; margin: 0; line-height: 1.2; word-break: keep-all; }
    
    #guide-inner-content { margin-top: 8px; width: 100%; display: flex; flex-direction: column; align-items: center; animation: fadeIn 0.5s ease; }
    #guide-inner-content img { width: 80%; height: auto; border-radius: 6px; margin-bottom: 5px; border: 1px solid #eee; object-fit: contain; }
    #guide-inner-content p { font-size: 0.85rem; color: #666; margin: 0; line-height: 1.3; font-weight: normal; }
    
    .message-img-container { width: 100%; display: flex; justify-content: center; margin-bottom: 2px; margin-top: -5px; flex-shrink: 0; animation: fadeIn 0.5s ease; }
    .message-img-container img { max-width: 100%; height: auto; max-height: 250px; object-fit: contain; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    
    #inputArea { flex: 1; display: flex; flex-direction: column; justify-content: flex-start; align-items: center; overflow-y: auto; padding-top: 5px; padding-bottom: 10px; width: 100%; }
    
    /* [버튼 컨테이너] */
    .btn-container { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; padding-bottom: 10px; position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); width: 90%; z-index: 100; }
    button.nav-btn { width: 100%; min-height: 70px; background: #3b82f6; color: #ffffffff; border: 2px solid var(--border-color); border-radius: 14px; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 0px var(--border-color); display: flex; align-items: center; justify-content: center; word-break: keep-all; line-height: 1.2; padding: 8px; transition: transform 0.1s; }
    button.nav-btn:active { transform: translateY(4px); box-shadow: none; background: #f1f3f5; border-color: #d1d5db; }
    
    .big-img-btn { width: 100%; height: 150px; background: white; border: 2px solid #e5e7eb; border-radius: 18px; cursor: pointer; display: flex; flex-direction: column; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.1s, border-color 0.2s; margin-bottom: 12px; flex-shrink: 0; overflow: hidden; }
    .big-img-btn:active { transform: scale(0.98); border-color: var(--primary-color); }
    .big-img-btn img { width: 100%; height: 65%; object-fit: cover; display: block; }
    .big-img-btn span { height: 35%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; color: #222; background: #fff; border-top: 1px solid #f0f0f0; }
    
    #customInputArea { width: 100%; display: none; flex-direction: column; gap: 10px; margin-top: 5px; }
    #customInput { padding: 15px; font-size: 1.1rem; border: 2px solid var(--primary-color); border-radius: 12px; width: 100%; box-sizing: border-box; resize: none; min-height: 120px; }
    .input-confirm-btn { width: 100%; padding: 15px; background: var(--primary-color); color: white; border: none; border-radius: 12px; font-size: 1.2rem; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); }
    
    .step-special-content { width: 100%; display: none; flex-direction: column; flex: 1; align-items: center; justify-content: flex-start; }
    
    /* [다이얼 관련 스타일] */
    .dial-wrapper { position: relative; display: flex; justify-content: center; width: 100%; height: var(--dial-container-height); background: #ffffff; border-radius: 18px; overflow: hidden; margin-bottom: 10px; user-select: none; border: 2px solid #f1f3f5; flex-shrink: 0; }
    .dial-highlight { position: absolute; top: 50%; left: 0; width: 100%; height: var(--dial-item-height); margin-top: calc(var(--dial-item-height) / -2); background-color: rgba(59, 130, 246, 0.08); border-top: 2px solid var(--primary-color); border-bottom: 2px solid var(--primary-color); pointer-events: none; z-index: 1; }
    .dial-column { height: 100%; width: 60px; overflow-y: scroll; scroll-snap-type: y mandatory; -ms-overflow-style: none; scrollbar-width: none; z-index: 2; padding: 0; }
    .dial-item { height: var(--dial-item-height); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #ccc; scroll-snap-align: center; }
    .dial-item.active { color: #222; font-weight: bold; font-size: 30px; transform: scale(1.1); }
    .dial-label { font-size: 18px; font-weight: bold; color: #333; margin: 0 5px; z-index: 2; padding-bottom: 3px; display:flex; align-items:center; }
    .station-dial-column { height: 100%; flex: 1; overflow-y: scroll; scroll-snap-type: y mandatory; -ms-overflow-style: none; scrollbar-width: none; z-index: 2; padding: 0; border-right: 1px solid #eee; }
    .station-dial-column:last-child { border-right: none; }
    .station-dial-item { height: var(--dial-item-height); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #ccc; scroll-snap-align: center; white-space: nowrap; }
    .station-dial-item.active { color: #222; font-weight: bold; font-size: 20px; transform: scale(1.05); }
    .station-range-label { position: absolute; top: 0; width: 50%; height: 25px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666; font-weight: bold; background: #fff; z-index: 10; border-bottom: 1px solid #eee; }
    
    .btn-send-dial { padding: 18px; background: #ff6b6b; color: white; border: none; border-radius: 16px; font-size: 1.3rem; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: 90%; z-index: 100; }
    .confirm-text { font-size: 1.1rem; line-height: 1.5; color: #333; text-align: center; padding: 5px; font-weight: 500; word-break: keep-all; }
    
    /* [Step 6 모바일 최적화 스타일] */
    #step6-content { height: 100%; display: none; flex-direction: column; justify-content: space-between; padding-bottom: 20px; }
    .status-container { display: flex; flex-direction: column; width: 100%; gap: 15px; margin-top: 10px; }
    .status-display-box { width: 100%; background: #fff; border: 2px solid #e5e7eb; border-radius: 16px; padding: 30px 15px; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 140px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: border-color 0.3s ease; }
    .action-btn { width: 100%; padding: 18px; border-radius: 16px; font-size: 1.2rem; font-weight: 800; cursor: pointer; border: none; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); box-sizing: border-box; }
    .action-btn:active { transform: scale(0.98); }
    .btn-cancel-outline { background: #ffffff; border: 2px solid #3b82f6; color: #3b82f6; margin-bottom: 10px; }
    .btn-resend { background: #ff6b6b; color: white; margin-bottom: 10px; box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3); }
    .btn-home-outline { background: #ffffff; border: 2px solid #3b82f6; color: #3b82f6; margin-top: auto; }
  </style>
</head>
<body>

  <div class="content-box" id="sentenceBox">🚇 신고 문장이 여기에 만들어집니다.</div>
  
  <div class="content-box" id="guideBox">
      <h2 id="guideText">지금 어디 계신가요?</h2>
  </div>

  <div id="inputArea">
      <div id="customInputArea">
          <textarea id="customInput" rows="1" maxlength="200" placeholder="여기를 눌러서 입력해주세요"></textarea>
          <div id="charCount">0/200</div>
          <button class="input-confirm-btn" onclick="submitCustomInput()">입력 완료</button>
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
             <div id="labelLeft" class="station-range-label" style="left:0;"></div>
             <div id="labelRight" class="station-range-label" style="right:0;"></div>
             <div class="station-dial-column" id="stationDialLeft"></div>
             <div class="station-dial-column" id="stationDialRight"></div>
          </div>

          <button class="btn-send-dial" id="dialNextBtn" style="display:none; background-color:#3b82f6;" onclick="handleDialNext()">다음</button>
      </div>

      <div id="step5-content" class="step-special-content" style="height:100%; display:none;">
          <div class="message-img-container">
              <img src="message.png" alt="안내 메시지">
          </div>
          <button class="btn-send-dial" id="finalSubmitBtn" style="margin-top: auto;" onclick="finalSmsSend()">🚀 신고 전송하기</button>
      </div>

      <div id="step6-content">
          <div class="status-container">
              <div class="status-display-box" id="statusBox">
                  <h2 id="resultStatusTitle" style="margin: 0 0 10px 0; font-size: 1.6rem; color: #3b82f6;">상태 확인 중...</h2>
                  <p id="resultStatusDesc" style="font-size: 1.1rem; color: #555; line-height: 1.5; margin: 0; word-break: keep-all; text-align: center;">잠시만 기다려주세요.</p>
              </div>
              <button class="action-btn btn-cancel-outline" id="cancelReportBtn" onclick="cancelReport()">접수 취소</button>
              <button class="action-btn btn-resend" id="resendSmsBtn" style="display: none;" onclick="finalSmsSend()">📩 문자 앱 다시 열기</button>
          </div>
          <button class="action-btn btn-home-outline" onclick="goToMain()">메인화면</button>
      </div>

      <div class="btn-container" id="btnContainer"></div>
  </div>

  <script>
    const STATION_DB = {
      '1호선': ['다대포해수욕장','다대포항','낫개','신장림','장림','동매','신평','하단','당리','사하','괴정','대티','서대신','동대신','토성','자갈치','남포','중앙','부산역','초량','부산진','좌천','범일','범내골','서면','부전','양정','시청','연산','교대','동래','명륜','온천장','부산대','장전','구서','두실','남산','범어사','노포'],
      '2호선': ['장산','중동','해운대','동백','벡스코','센텀시티','민락','수영','광안','금련산','남천','경성대부경대','대연','못골','지게골','문현','국제금융센터','전포','서면','부암','가야','동의대','개금','냉정','주례','감전','사상','덕포','모덕','모라','구남','구명','덕천','수정','화명','율리','동원','금곡','호포','증산','부산대양산','남양산','양산'],
      '3호선': ['수영','망미','배산','물만골','연산','거제','종합운동장','사직','미남','만덕','남산정','숙등','덕천','구포','강서구청','체육공원','대저'],
      '4호선': ['미남','동래','수안','낙민','충렬사','명장','서동','금사','반여농산물','석대','영산대','윗반송','고촌','안평']
    };

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

    const els = {
        guideBox: document.getElementById('guideBox'), guideText: document.getElementById('guideText'),
        btnContainer: document.getElementById('btnContainer'), sentenceBox: document.getElementById('sentenceBox'),
        customInputArea: document.getElementById('customInputArea'), customInput: document.getElementById('customInput'),
        charCount: document.getElementById('charCount'), step4Content: document.getElementById('step4-content'),
        step5Content: document.getElementById('step5-content'), dialWrapper: document.getElementById('dialWrapper'),
        stationDialWrapper: document.getElementById('stationDialWrapper'), dialNextBtn: document.getElementById('dialNextBtn'),
        step6Content: document.getElementById('step6-content'),
        resultStatusTitle: document.getElementById('resultStatusTitle'),
        resultStatusDesc: document.getElementById('resultStatusDesc'),
        resendSmsBtn: document.getElementById('resendSmsBtn'),
        cancelReportBtn: document.getElementById('cancelReportBtn'),
        statusBox: document.getElementById('statusBox')
    };

    let currentStep = 1;
    let report = { place: '', situation: '', detailSituation: '', line: '', detail: '', isInside: true, stationName: '', specificLocation: '' };
    let dialValues = { line: 1, d1: 0, d2: 0, d3: 1 };
    let generatedCode = ''; 
    let smsAppOpened = false; 
    let statusCheckInterval = null; 
    const DIAL_ITEM_HEIGHT = 50; 

    const stepButtons = {
      1: [ { text: '지하철 안이에요', img: 'inside.jpg', action: () => { report.isInside = true; nextStep('place', '지하철 안인데'); } }, { text: '지하철 밖이에요', img: 'outside.jpg', action: () => { report.isInside = false; nextStep('place', '지하철 밖인데'); } } ],
      2: { inside: [ { text: '잡상인이 있어요', action: () => handleSituationStep('잡상인이 있어요') }, { text: '싸움이 일어났어요', action: () => handleSituationStep('싸움이 일어났어요') }, { text: '온도 조절이 필요해요', action: () => handleSituationStep('온도 조절이 필요해요') }, { text: '민폐를 끼쳐요', action: () => handleSituationStep('민폐를 끼쳐요') }, { text: '청소가 필요해요', action: () => handleSituationStep('청소가 필요해요') }, { text: '시설물이 파손됐어요', action: () => handleSituationStep('시설물이 파손됐어요') }, { text: '직접 입력', action: () => handleSituationStep('직접 입력') } ], outside: [ { text: '구걸인/노숙인이 있어요', action: () => handleSituationStep('구걸인/노숙인이 있어요') }, { text: '무임승차를 봤어요', action: () => handleSituationStep('무임승차를 봤어요') }, { text: '소란을 피워요', action: () => handleSituationStep('소란을 피워요') }, { text: '청소가 안돼있어요', action: () => handleSituationStep('청소가 안돼있어요') }, { text: '시설물이 고장났어요', action: () => handleSituationStep('시설물이 고장났어요') }, { text: '화장실에 문제가 있어요', action: () => handleSituationStep('화장실에 문제가 있어요') }, { text: '직접 입력', action: () => handleSituationStep('직접 입력') } ] },
      3: [ { text: '1호선', action: () => nextStep('line', '1호선') }, { text: '2호선', action: () => nextStep('line', '2호선') }, { text: '3호선', action: () => nextStep('line', '3호선') }, { text: '4호선', action: () => nextStep('line', '4호선') } ]
    };

    function pushHistoryState(stateObj) {
        const newState = { ...stateObj, report: JSON.parse(JSON.stringify(report)) };
        history.pushState(newState, null, null);
    }

function makeSentence() {
    let sentence = '';
    if (report.place) sentence += report.place + ' ';
    
    if (report.situation) { 
        if (report.situation === '직접 입력') {
            if (report.detailSituation) sentence += report.detailSituation + ' ';
        } else {
            sentence += report.situation;
            if (report.detailSituation) sentence += `(${report.detailSituation})`;
            sentence += ' ';
        }
    }

    // ★ [핵심 수정] 상세 위치를 '지금' 키워드보다 앞에 붙입니다.
    // 이렇게 해야 관리자 페이지에서 '신고 내용' 칸에 상세 위치가 보입니다.
    if (!report.isInside && report.specificLocation) {
        sentence += ` (${report.specificLocation}) `;
    }

    if (report.line) sentence += '지금 ' + report.line + ' ';
    if (report.detail) sentence += report.detail;
    
    return sentence.trim();
}

    function updateSentenceBox() {
        let text = makeSentence();
        if ((currentStep === 5 || currentStep === 6) && generatedCode) { text += `\n\n[접수코드: ${generatedCode}]`; }
        els.sentenceBox.innerText = text || '🚇 신고 문장이 여기에 만들어집니다.';
    }

    function updateGuideText(step) {
        const texts = { 1: "지금 어디 계신가요?", 2: "어떤 상황인가요?", 3: "몇 호선인가요?", 4: (report.isInside ? "사진을 참고하여 \n 호선/편성/호차를 맞춰주세요." : "현재 무슨 역에 계신가요?") };
        if(step !== 5 && step !== 6) { els.guideText.innerText = texts[step]; }
        const oldContent = document.getElementById('guide-inner-content');
        if(oldContent) oldContent.remove();

        if (step === 4 && report.isInside) {
            const div = document.createElement('div');
            div.id = 'guide-inner-content';
            div.innerHTML = `<img src="train_num_guide.jpg" alt="차량번호 안내" style="width: 40%; height: auto; max-height: none;"><p>호선과 차량번호는 이곳에 있습니다.<br>참고해주세요.</p>`;
            els.guideBox.appendChild(div);
        }
        if (step === 5) {
            els.guideText.innerText = ""; 
            const div = document.createElement('div');
            div.id = 'guide-inner-content'; div.className = 'confirm-text';
            div.innerHTML = `<div style="margin-bottom:10px;">접수코드(<span style="color:#3b82f6; font-weight:800; font-size:1.3rem;">${generatedCode}</span>)가 복사되었습니다.</div><div style="margin-bottom:10px; color: red;">문자 앱에서 수정되는 신고 내역은 수정되어 전송되지 않습니다!!</div><div style="font-size: 0.95rem; color:#555;">접수코드로 신고내역을 조회할수 있습니다.<br>잠시 후 문자 앱이 열리면 안내사항과 토큰값이 존재합니다.<br>붙여넣기 할 필요 없이 바로 전송하세요.</div>`;
            els.guideBox.appendChild(div);
        }
        if (step === 6) {
             els.guideBox.style.display = 'none';
        } else {
             els.guideBox.style.display = 'block';
        }
    }

    function renderButtons(step) {
        els.btnContainer.innerHTML = '';
        if (step === 4 || step === 5 || step === 6) return;
        
        if (step === 1) {
            els.btnContainer.style.display = 'flex'; els.btnContainer.style.flexDirection = 'column';
            stepButtons[1].forEach(btn => {
                const b = document.createElement('div'); b.className = 'big-img-btn'; b.onclick = btn.action;
                const img = document.createElement('img'); img.src = btn.img; img.alt = btn.text;
                const text = document.createElement('span'); text.innerText = btn.text;
                b.appendChild(img); b.appendChild(text); els.btnContainer.appendChild(b);
            });
            return;
        } 
        
        els.btnContainer.style.display = 'grid'; els.btnContainer.style.flexDirection = 'row';
        const currentButtons = (step === 2) ? (report.isInside ? stepButtons[2].inside : stepButtons[2].outside) : stepButtons[step];
        const fragment = document.createDocumentFragment();
        currentButtons.forEach(btn => {
            const b = document.createElement('button'); b.className = 'nav-btn'; b.innerText = btn.text; b.onclick = btn.action;
            if (btn.text === '직접 입력') { b.style.gridColumn = "1 / -1"; }
            fragment.appendChild(b);
        });
        els.btnContainer.appendChild(fragment);
    }

    function updateUI(step) {
        els.btnContainer.innerHTML = ''; els.customInputArea.style.display = 'none';
        els.step4Content.style.display = 'none'; els.step5Content.style.display = 'none';
        els.step6Content.style.display = 'none';

        if (step === 4) {
            els.step4Content.style.display = 'flex'; els.dialNextBtn.style.display = 'block';
            if (report.isInside) { els.dialWrapper.style.display = 'flex'; els.stationDialWrapper.style.display = 'none'; initDials(parseInt(report.line.replace('호선', '')) || 1); } 
            else { els.dialWrapper.style.display = 'none'; els.stationDialWrapper.style.display = 'flex'; renderStationDials(report.line); }
        } else if (step === 5) { 
            els.step5Content.style.display = 'flex'; 
        } else if (step === 6) {
            els.step6Content.style.display = 'flex';
            checkReportStatus(); 
        } else { 
            renderButtons(step); 
        }
    }

    function nextStep(type, value) {
        if (type) report[type] = value;
        updateSentenceBox();
        if (currentStep < 5) { 
            currentStep++; if (currentStep === 3 && report.isInside) { currentStep++; }
            pushHistoryState({ step: currentStep }); updateGuideText(currentStep); updateUI(currentStep);
        }
    }

    function handleSituationStep(situationText) {
        report.situation = situationText; report.detailSituation = '';
        if (situationText === '직접 입력') { pushHistoryState({ step: currentStep, sub: 'customInput' }); showCustomInputStep(); } 
        else if (DETAIL_OPTIONS[situationText]) { pushHistoryState({ step: currentStep, sub: 'detail', detailKey: situationText }); showDetailStep(situationText); } 
        else { nextStep(null, null); }
    }

    function showCustomInputStep() {
        updateSentenceBox(); els.guideText.innerText = "불편사항을 직접 적어주세요."; els.btnContainer.innerHTML = '';
        els.customInputArea.style.display = 'flex'; els.customInput.value = ''; els.charCount.innerText = "0/200";
        els.customInput.placeholder = "내용을 입력해주세요";
        els.customInput.oninput = function() {
            els.charCount.innerText = this.value.length + "/200";
        };
        setTimeout(() => els.customInput.focus(), 300);
    }

    function showLocationInputStep() {
        updateSentenceBox();
        els.guideText.innerText = "정확한 위치를 입력해주세요.\n(예: 3번 출구 앞, 개찰구 근처)";
        els.step4Content.style.display = 'none'; 
        els.customInputArea.style.display = 'flex';
        els.customInput.value = '';
        els.charCount.innerText = "0/200";
        els.customInput.placeholder = "상세 위치를 입력해주세요";
        els.customInput.oninput = function() {
             els.charCount.innerText = this.value.length + "/200";
        };
        setTimeout(() => els.customInput.focus(), 300);
    }

    function submitCustomInput() {
        const val = els.customInput.value;
        if (!val) { alert("내용을 입력해주세요."); return; }
        
        if (history.state && history.state.sub === 'locationInput') {
             report.specificLocation = val; 
             report.isInside = false;       
             els.customInputArea.style.display = 'none';
             goToStep5(); 
        } else {
             report.detailSituation = val; 
             els.customInputArea.style.display = 'none'; 
             nextStep(null, null);
        }
    }

    function handleDialNext() {
        updateReportDetailFromDial();
        
        if (report.isInside) {
            goToStep5();
        } else {
            pushHistoryState({ step: currentStep, sub: 'locationInput' });
            showLocationInputStep();
        }
    }

    // [DB 전송 및 문자 생성 함수 - 강력한 붙여넣기]
    function goToStep5() {
        generatedCode = generateReceiptCode(); 
        
        let finalSentence = makeSentence();

        let trainNumVal = '';
        let carNumVal = '';

        if (report.isInside) {
            trainNumVal = `${dialValues.d1}${dialValues.d2}`;
            carNumVal = `${dialValues.d3}`;
        } else {
            trainNumVal = report.stationName || ''; 
            carNumVal = ''; 
        }

        const payload = {
            content: finalSentence + `\n\n[접수코드: ${generatedCode}]`, // 최종 문장
            token: generatedCode,
            line: report.line,
            train_num: trainNumVal, 
            car_num: carNumVal
        };

        fetch('save_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).catch(err => console.error(err));

        nextStep(null, null);
    }

    function showDetailStep(situationText) {
        updateSentenceBox(); els.guideText.innerText = `"${situationText}"\n구체적으로 어떤가요?`;
        els.customInputArea.style.display = 'none'; els.btnContainer.innerHTML = '';
        const fragment = document.createDocumentFragment();
        DETAIL_OPTIONS[situationText].forEach(opt => {
            const b = document.createElement('button'); b.className = 'nav-btn'; b.innerText = opt.text;
            b.onclick = () => { report.detailSituation = opt.val; nextStep(null, null); };
            fragment.appendChild(b);
        });
        els.btnContainer.appendChild(fragment);
    }

    function createInfiniteDial(elementId, start, end, initialVal) {
        const container = document.getElementById(elementId); container.innerHTML = '';
        const spacer = document.createElement('div'); spacer.style.height = `${DIAL_ITEM_HEIGHT}px`; spacer.style.flexShrink = '0'; container.appendChild(spacer);
        const REPEAT_COUNT = 30; const fragment = document.createDocumentFragment();
        for (let k = 0; k < REPEAT_COUNT; k++) {
            for (let i = start; i <= end; i++) {
                const item = document.createElement('div'); item.className = 'dial-item'; item.innerText = i; item.dataset.val = i; fragment.appendChild(item);
            }
        }
        container.appendChild(fragment);
        const spacerBottom = spacer.cloneNode(true); container.appendChild(spacerBottom);
        const distinctCount = (end - start + 1);
        container.addEventListener('scroll', () => updateActiveInfiniteItem(container, elementId, distinctCount));
        setTimeout(() => {
            const items = container.querySelectorAll('.dial-item');
            const middleSetIndex = Math.floor(REPEAT_COUNT / 2) * distinctCount; 
            let targetIndex = -1;
            for(let i=0; i<distinctCount; i++) { if(parseInt(items[middleSetIndex + i].innerText) == initialVal) { targetIndex = middleSetIndex + i; break; } }
            if(targetIndex !== -1) container.scrollTop = targetIndex * DIAL_ITEM_HEIGHT; else container.scrollTop = middleSetIndex * DIAL_ITEM_HEIGHT;
            updateActiveInfiniteItem(container, elementId, distinctCount);
        }, 100);
    }

    function updateActiveInfiniteItem(container, dialId, distinctCount) {
        let index = Math.round(container.scrollTop / DIAL_ITEM_HEIGHT);
        const items = container.querySelectorAll('.dial-item');
        if (index < 0) index = 0; if (index >= items.length) index = items.length - 1;
        const activeItem = container.querySelector('.dial-item.active'); if(activeItem) activeItem.classList.remove('active');
        const target = items[index];
        if (target) {
            target.classList.add('active'); const val = target.innerText; 
            if (dialId === 'dialLine') dialValues.line = val;
            if (dialId === 'dial1') dialValues.d1 = val;
            if (dialId === 'dial2') dialValues.d2 = val;
            if (dialId === 'dial3') dialValues.d3 = val;
            report.line = dialValues.line + '호선';
            updateReportDetailFromDial();
            updateSentenceBox();
        }
    }

    function initDials(initialLine) {
        createInfiniteDial('dialLine', 1, 4, initialLine); createInfiniteDial('dial1', 0, 9, 0); createInfiniteDial('dial2', 0, 9, 0); createInfiniteDial('dial3', 1, 9, 1);
    }

    function renderStationDials(lineName) {
        const stations = STATION_DB[lineName] || ['정보 없음'];
        const midIndex = Math.ceil(stations.length / 2);
        const leftStations = stations.slice(0, midIndex); const rightStations = stations.slice(midIndex);
        document.getElementById('labelLeft').innerText = leftStations.length > 0 ? `${leftStations[0]} ~ ${leftStations[leftStations.length-1]}` : '';
        document.getElementById('labelRight').innerText = rightStations.length > 0 ? `${rightStations[0]} ~ ${rightStations[rightStations.length-1]}` : '';
        createStationDial('stationDialLeft', leftStations, 'left'); createStationDial('stationDialRight', rightStations, 'right');
        
        // [초기값 설정]
        if (leftStations.length > 0) {
            report.stationName = leftStations[0];
            report.detail = leftStations[0] + '역에 있어요';
        }
    }

    function createStationDial(containerId, list, side) {
        const container = document.getElementById(containerId); container.innerHTML = '';
        const spacer = document.createElement('div'); spacer.style.height = `${DIAL_ITEM_HEIGHT}px`; spacer.style.flexShrink = '0'; container.appendChild(spacer);
        const fragment = document.createDocumentFragment();
        list.forEach(name => {
            const item = document.createElement('div'); item.className = 'station-dial-item'; item.innerText = name; item.dataset.val = name; fragment.appendChild(item);
        });
        container.appendChild(fragment);
        const spacerBottom = spacer.cloneNode(true); container.appendChild(spacerBottom);
        container.onscroll = function() { updateActiveStation(container, side); };
        setTimeout(() => { container.scrollTop = 0; updateActiveStation(container, side); }, 100);
    }

    function updateActiveStation(container, side) {
        let index = Math.round(container.scrollTop / DIAL_ITEM_HEIGHT);
        const items = container.querySelectorAll('.station-dial-item');
        if (items.length === 0) return;
        if (index < 0) index = 0; if (index >= items.length) index = items.length - 1;
        const prevActive = container.querySelector('.station-dial-item.active'); if (prevActive) prevActive.classList.remove('active');
        const target = items[index]; target.classList.add('active');
        const otherSideId = (side === 'left') ? 'stationDialRight' : 'stationDialLeft';
        const otherContainer = document.getElementById(otherSideId);
        const otherActive = otherContainer.querySelector('.station-dial-item.active');
        if (otherActive) otherActive.classList.remove('active');
        
        report.detail = target.innerText + '역에 있어요';
        report.stationName = target.innerText; 
        updateSentenceBox();
    }

    function updateReportDetailFromDial() {
        if (report.isInside) { report.detail = `${dialValues.d1}${dialValues.d2}편성 ${dialValues.d3}호차`; }
    }
    
    function generateReceiptCode() { return Array.from({length: 8}, () => Math.floor(Math.random() * 16).toString(16)).join(''); }

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).catch(err => { fallbackCopyTextToClipboard(text); }); } else { fallbackCopyTextToClipboard(text); }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea"); textArea.value = text; textArea.style.top = "0"; textArea.style.left = "0"; textArea.style.position = "fixed"; document.body.appendChild(textArea); textArea.focus(); textArea.select();
        try { document.execCommand('copy'); } catch (err) { console.error('Fallback copy failed', err); } document.body.removeChild(textArea);
    }

    function finalSmsSend() {
        let finalSentence = makeSentence();
        
        // SMS 보낼 때도 강제로 상세 위치 붙여서 보냄
        if (!report.isInside && report.specificLocation && finalSentence.indexOf(report.specificLocation) === -1) {
            finalSentence += " (" + report.specificLocation + ")";
        }

        let warningText = " ※ 허위 신고 시 철도안전법에 따라 처벌될 수 있습니다.\n\n";
        warningText += "※ 접수코드 수정 시 신고가 접수되지 않습니다.\n\n";
        let displaySentence = warningText + "신고 내용: " + finalSentence + `\n\n[접수코드: ${generatedCode}]`;
        
        copyToClipboard(generatedCode);
        
        const targetEmail = "<?= isset($myEmail) ? $myEmail : 'busanminwon@gmail.com' ?>"; 
        const encodedBody = encodeURIComponent(displaySentence);
        const separator = navigator.userAgent.match(/iPhone/i) ? "&" : "?";
        
        smsAppOpened = true; 
        location.href = "sms:" + targetEmail + separator + "body=" + encodedBody;
    }

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible" && smsAppOpened && currentStep === 5) {
            smsAppOpened = false;
            goToStep6();
        }
    });

    function goToStep6() {
        currentStep = 6;
        updateUI(6);
        updateGuideText(6);
        updateSentenceBox();
    }
    
    function goToMain() {
        location.reload();
    }

    function cancelReport() {
        if(!confirm("정말로 접수를 취소하시겠습니까?")) return;
        
        fetch('cancel_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: generatedCode })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(statusCheckInterval) clearInterval(statusCheckInterval); 
                
                els.resultStatusTitle.innerText = "접수 취소됨";
                els.resultStatusTitle.style.color = "#ff6b6b";
                els.resultStatusDesc.innerText = "민원 신고가 취소되었습니다.";
                els.statusBox.style.borderColor = "#ff6b6b";
                
                els.cancelReportBtn.style.display = 'none';
                els.resendSmsBtn.style.display = 'none';
                
            } else {
                alert("취소 실패: " + (data.msg || '알 수 없는 오류'));
            }
        })
        .catch(err => {
            console.error(err);
            alert("서버 통신 오류로 취소하지 못했습니다.");
        });
    }

    function checkReportStatus() {
        if(statusCheckInterval) clearInterval(statusCheckInterval);

        const poll = () => {
            fetch('check_report_status.php?token=' + generatedCode)
                .then(res => res.json())
                .then(data => {
                    const title = els.resultStatusTitle;
                    const desc = els.resultStatusDesc;
                    const resendBtn = els.resendSmsBtn;
                    const cancelBtn = els.cancelReportBtn;
                    const box = els.statusBox;

                    if (data.verified) {
                        title.innerText = "접수 대기";
                        title.style.color = "#3b82f6"; 
                        desc.innerText = "민원 신고 완료되었습니다.";
                        resendBtn.style.display = 'none';
                        box.style.borderColor = "#3b82f6";
                        if(statusCheckInterval) clearInterval(statusCheckInterval);
                    } else {
                        title.innerText = "신고 전";
                        title.style.color = "#ff6b6b"; 
                        desc.innerText = "5분 안에 문자를 보내지 않으면 접수되지 않습니다.";
                        resendBtn.style.display = 'block';
                        box.style.borderColor = "#ff6b6b";
                    }
                })
                .catch(err => {
                    console.error("Status Check Error:", err);
                });
        };

        poll(); 
        statusCheckInterval = setInterval(poll, 3000); 
    }

    window.addEventListener('popstate', function(event) {
        const state = event.state;
        if (!state) { 
            currentStep = 1;
            report = { place: '', situation: '', detailSituation: '', line: '', detail: '', isInside: true, stationName: '', specificLocation: '' };
            updateSentenceBox(); updateGuideText(1); updateUI(1);
            return;
        }
        currentStep = state.step;
        if (state.report) report = state.report; 
        
        if (state.sub === 'customInput') {
             showCustomInputStep();
        } else if (state.sub === 'locationInput') {
             showLocationInputStep();
        } else if (state.sub === 'detail') {
             showDetailStep(state.detailKey);
        } else { 
             updateSentenceBox(); updateGuideText(currentStep); updateUI(currentStep); 
        }
    });

    history.replaceState({ step: 1, report: JSON.parse(JSON.stringify(report)) }, null, null);
    updateGuideText(1);
    renderButtons(1);
  </script>