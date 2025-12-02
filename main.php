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
       1. CSS 변수 설정
    ------------------------------------------- */
    :root {
        --primary-color: #3b82f6;
        --bg-color: #f8f9fb;
        --box-bg: #ffffff;
        --text-main: #222;
        --border-color: #e5e7eb;
        
        /* [중요] 다이얼 크기 계산 변수 */
        /* 항목 하나의 높이 (파란 박스 높이) */
        --dial-item-height: 60px; 
        /* 전체 다이얼 박스 높이 (항목 3개가 딱 보이는 높이: 60 * 3) */
        --dial-container-height: 180px; 
    }

    /* -------------------------------------------
       2. 기본 레이아웃
    ------------------------------------------- */
    body {
        margin: 0; background: var(--bg-color);
        font-family: "Pretendard", -apple-system, sans-serif;
        color: var(--text-main);
        display: flex; flex-direction: column;
        height: 100vh; overflow: hidden;
        padding: 15px; box-sizing: border-box;
    }

    .content-box {
        background: var(--box-bg);
        border-radius: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        padding: 20px;
        text-align: center; margin-bottom: 15px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    #sentenceBox {
        font-size: 1.5rem; color: #1e3a8a;
        min-height: 50px; line-height: 1.5; font-weight: 600;
        word-break: keep-all;
    }

    #guideBox {
        font-size: 1.5rem; font-weight: 800; color: #333;
        min-height: 50px; line-height: 1.4; word-break: keep-all;
    }
    #guideBox h2 { margin: 0; font-size: inherit; }

    /* -------------------------------------------
       3. 입력 영역 & 버튼
    ------------------------------------------- */
    #inputArea {
        flex: 1; display: flex; flex-direction: column;
        justify-content: flex-start; align-items: center;
        overflow-y: auto; padding-top: 10px;
    }

    .btn-container { 
        display: grid; grid-template-columns: 1fr 1fr; 
        gap: 12px; width: 100%; padding-bottom: 15px;
    }
    
    button.nav-btn { 
        width: 100%; min-height: 80px;
        background: #ffffffff; color: #333; 
        border: 2px solid var(--border-color); 
        border-radius: 20px; font-size: 1.3rem; font-weight: 700;
        cursor: pointer; box-shadow: 0 6px 0px var(--border-color);
        display: flex; align-items: center; justify-content: center;
        word-break: keep-all; line-height: 1.3; padding: 15px;
        transition: transform 0.1s;
    }
    button.nav-btn:active { transform: translateY(4px); box-shadow: none; background: #f1f3f5; border-color: #d1d5db; }

    /* 직접 입력 */
    #customInputArea { width: 100%; display: none; flex-direction: column; gap: 15px; margin-top: 10px; }
    #customInput { padding: 20px; font-size: 1.2rem; border: 2px solid var(--primary-color); border-radius: 16px; width: 100%; box-sizing: border-box; resize: none; min-height: 150px; }
    .input-confirm-btn { width: 100%; padding: 18px; background: var(--primary-color); color: white; border: none; border-radius: 16px; font-size: 1.3rem; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); }

    /* -------------------------------------------
       4. 다이얼(Picker) 스타일 [수정됨]
    ------------------------------------------- */
    .step-special-content { width: 100%; display: none; flex-direction: column; flex: 1; }

    .dial-wrapper { 
        position: relative; display: flex; justify-content: center; 
        width: 100%; 
        height: var(--dial-container-height); /* 180px */
        background: #ffffff; border-radius: 24px; 
        overflow: hidden; margin-bottom: 20px; 
        user-select: none; border: 2px solid #f1f3f5; 
    }

    /* 중앙 파란 박스 (하이라이트) */
    .dial-highlight { 
        position: absolute; 
        top: 50%; left: 0; width: 100%; 
        /* 높이 60px */
        height: var(--dial-item-height); 
        /* 중앙 정렬을 위해 높이의 절반만큼 올림 */
        margin-top: calc(var(--dial-item-height) / -2); 
        
        /* 디자인: 연한 파란 배경 + 위아래 진한 파란 선 */
        background-color: rgba(59, 130, 246, 0.08); 
        border-top: 2px solid var(--primary-color); 
        border-bottom: 2px solid var(--primary-color); 
        pointer-events: none; z-index: 1; 
    }

    .dial-column { 
        height: 100%; width: 60px; 
        overflow-y: scroll; 
        scroll-snap-type: y mandatory; 
        -ms-overflow-style: none; scrollbar-width: none; 
        z-index: 2; padding: 0; 
    }
    
    .dial-item { 
        height: var(--dial-item-height); /* 60px */
        display: flex; align-items: center; justify-content: center; 
        font-size: 26px; color: #ccc; 
        scroll-snap-align: center; /* 스크롤이 딱 멈추게 */
    }
    
    .dial-item.active { 
        color: #222; 
        font-weight: bold; 
        font-size: 34px; 
        transform: scale(1.1); 
    }
    .dial-label { 
        font-size: 20px; 
        font-weight: bold; 
        color: #333;
        margin: 0 5px; 
        z-index: 2; 
        padding-bottom: 3px; 
        display:flex; 
        align-items:center;
    }

    .btn-send-dial { 
        width: 100%; 
        padding: 20px; 
        background: #ff6b6b; 
        color: white; 
        border: none; 
        border-radius: 16px; 
        font-size: 1.4rem; 
        font-weight: bold; 
        cursor: pointer; 
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); 
        margin-top: auto; 
        margin-bottom: 120px; 
    }

    /* 역 선택 칩 */
    .station-scroll-wrapper { 
        width: 100%; 
        overflow-x: auto; 
        display: none; 
        padding: 15px 0; 
        scrollbar-width: none;
        margin-bottom: 10px; 
    }
    .station-container { 
        display: inline-flex; 
        gap: 10px; 
        padding: 0 5px; 
    }
    .station-chip { 
        background: #ffffff; 
        border: 2px solid var(--border-color); 
        border-radius: 25px; 
        padding: 15px 25px;
        white-space: nowrap; 
        color: #555; 
        font-size: 1.1rem; 
        font-weight: 600; 
        cursor: pointer; 
    }
    .station-chip.selected { 
        background: var(--primary-color); 
        color: white;
        border-color: var(--primary-color); 
        box-shadow: 0 4px 10px rgba(59,130,246,0.3); 
        transform: scale(1.05); 
    }

    /* 모달 */
    #help-modal { 
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0,0,0,0.85); 
        z-index: 1000; 
        flex-direction: column; 
        justify-content: center; 
        align-items: center; 
    }
    .help-content { 
        background: white; 
        padding: 25px; 
        border-radius: 20px; 
        width: 85%; 
        max-width: 350px; 
        text-align: center; 
    }
    .help-content img { 
        width: 100%; 
        border-radius: 10px; 
        margin-bottom: 20px; 
    }
  </style>
</head>
<body>

  <div class="content-box" id="sentenceBox">🚇 신고 문장이 여기에 만들어집니다.</div>
  <div class="content-box" id="guideBox"><h2 id="guideText">지금 어디 계신가요?</h2></div>

  <div id="inputArea">
      <div id="customInputArea">
          <textarea id="customInput" rows="1" maxlength="200" placeholder="여기를 눌러서 입력해주세요"></textarea>
          <div id="charCount">0/200</div>
          <button class="input-confirm-btn" onclick="submitCustomInput()">입력 완료</button>
      </div>

      <div id="step4-content" class="step-special-content">
          <div class="guide" id="dial-guide" style="display:none; font-size: 15px; color: #555; margin-bottom: 15px; text-align:center;">
              위아래로 드래그하여 번호를 맞춰주세요.<br>
              <button onclick="openHelp()" style="background:none; border:none; color:var(--primary-color); text-decoration:underline; cursor:pointer; margin-top:8px; font-size: 14px; font-weight: bold;">📷 번호가 어디 있나요?</button>
          </div>

          <div class="dial-wrapper" id="dialWrapper" style="display:none;">
              <div class="dial-highlight"></div>
              
              <div class="dial-column" id="dialLine"></div>
              <div class="dial-label">호선</div>
              
              <div class="dial-column" id="dial1"></div>
              <div class="dial-column" id="dial2"></div>
              <div class="dial-label">편성</div>
              
              <div class="dial-column" id="dial3"></div>
              <div class="dial-label">호차</div>
          </div>

          <div class="station-scroll-wrapper" id="stationWrapper">
            <div class="station-container" id="stationList"></div>
          </div>
          <p id="scroll-guide" style="font-size:0.9rem; color:#888; margin-top:5px; display:none; text-align:center;">⬅️ 좌우로 밀어서 역을 선택하세요 ➡️</p>

          <button class="btn-send-dial" id="dialSubmitBtn" style="display:none;" onclick="submitDialData()">🚀 신고 전송하기</button>
      </div>

      <div class="btn-container" id="btnContainer"></div>
  </div>

  <form id="finalForm" style="display:none;">
      <input type="hidden" name="phone" value="익명"> 
      <input type="hidden" name="content" id="hiddenContent">
  </form>

  <div id="help-modal">
    <div class="help-content">
        <img src="train_guide.jpg" alt="차량번호 예시">
        <p style="font-size:1.4rem; color:#111; font-weight:bold; margin-bottom:25px; line-height:1.5;">출입문 위 또는<br>통로문 상단에 적힌<br>번호를 입력해주세요.</p>
        <button onclick="closeHelp()" style="width:100%; padding:15px; background:var(--primary-color); color:white; border:none; border-radius:10px; font-size:1.1rem; font-weight:bold; cursor:pointer;">확인했습니다</button>
    </div>
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
        guideText: document.getElementById('guideText'),
        btnContainer: document.getElementById('btnContainer'),
        sentenceBox: document.getElementById('sentenceBox'),
        customInputArea: document.getElementById('customInputArea'),
        customInput: document.getElementById('customInput'),
        charCount: document.getElementById('charCount'),
        step4Content: document.getElementById('step4-content'),
        stationWrapper: document.getElementById('stationWrapper'),
        stationList: document.getElementById('stationList'),
        scrollGuide: document.getElementById('scroll-guide'),
        dialWrapper: document.getElementById('dialWrapper'),
        dialGuide: document.getElementById('dial-guide'),
        dialSubmitBtn: document.getElementById('dialSubmitBtn'),
        modal: document.getElementById('help-modal')
    };

    let currentStep = 1;
    let report = { place: '', situation: '', detailSituation: '', line: '', detail: '', isInside: true };
    let dialValues = { line: 1, d1: 0, d2: 0, d3: 1 };
    
    // [중요] 다이얼 높이 상수 (CSS와 일치해야 함)
    const DIAL_ITEM_HEIGHT = 60; 

    const stepButtons = {
      1: [ { text: '지하철 안이에요', action: () => { report.isInside = true; nextStep('place', '지하철 안인데'); } }, { text: '지하철 밖이에요', action: () => { report.isInside = false; nextStep('place', '지하철 밖인데'); } } ],
      2: { inside: [ { text: '잡상인이 있어요', action: () => handleSituationStep('잡상인이 있어요') }, { text: '싸움이 일어났어요', action: () => handleSituationStep('싸움이 일어났어요') }, { text: '온도 조절이 필요해요', action: () => handleSituationStep('온도 조절이 필요해요') }, { text: '민폐를 끼쳐요', action: () => handleSituationStep('민폐를 끼쳐요') }, { text: '청소가 필요해요', action: () => handleSituationStep('청소가 필요해요') }, { text: '시설물이 파손됐어요', action: () => handleSituationStep('시설물이 파손됐어요') }, { text: '직접 입력', action: () => handleSituationStep('직접 입력') } ], outside: [ { text: '구걸인/노숙인이 있어요', action: () => handleSituationStep('구걸인/노숙인이 있어요') }, { text: '무임승차를 봤어요', action: () => handleSituationStep('무임승차를 봤어요') }, { text: '소란을 피워요', action: () => handleSituationStep('소란을 피워요') }, { text: '청소가 안돼있어요', action: () => handleSituationStep('청소가 안돼있어요') }, { text: '시설물이 고장났어요', action: () => handleSituationStep('시설물이 고장났어요') }, { text: '화장실에 문제가 있어요', action: () => handleSituationStep('화장실에 문제가 있어요') }, { text: '직접 입력', action: () => handleSituationStep('직접 입력') } ] },
      3: [ { text: '1호선', action: () => nextStep('line', '1호선') }, { text: '2호선', action: () => nextStep('line', '2호선') }, { text: '3호선', action: () => nextStep('line', '3호선') }, { text: '4호선', action: () => nextStep('line', '4호선') } ]
    };

    function pushHistoryState(stateObj) {
        const newState = { ...stateObj, report: JSON.parse(JSON.stringify(report)) };
        history.pushState(newState, null, null);
    }

    function updateSentenceBox() {
        const text = makeSentence() || '🚇 신고 문장이 여기에 만들어집니다.';
        els.sentenceBox.innerText = text;
    }

    function makeSentence() {
        let sentence = '';
        if (report.place) sentence += report.place + ' ';
        if (report.situation) { 
            sentence += (report.situation === '직접 입력') 
                ? (report.detailSituation ? report.detailSituation + ' ' : '') 
                : (report.situation + (report.detailSituation ? `(${report.detailSituation})` : '') + ' '); 
        }
        if (report.line) sentence += '\n' + report.line + ' ';
        if (report.detail) sentence += report.detail;
        return sentence.trim();
    }

    function updateGuideText(step) {
        const texts = { 
            1: "지금 어디 계신가요?", 
            2: "어떤 상황인가요?", 
            3: "몇 호선인가요?", 
            4: (report.isInside ? "호선과 차량 번호를 맞춰주세요." : "현재 무슨 역에 계신가요?") 
        };
        els.guideText.innerText = texts[step];
    }

    function renderButtons(step) {
        els.btnContainer.innerHTML = '';
        if (step === 4) return;
        
        const currentButtons = (step === 2) 
            ? (report.isInside ? stepButtons[2].inside : stepButtons[2].outside) 
            : stepButtons[step];
        
        const fragment = document.createDocumentFragment();
        currentButtons.forEach(btn => {
            const b = document.createElement('button');
            b.className = 'nav-btn'; b.innerText = btn.text; b.onclick = btn.action;
            fragment.appendChild(b);
        });
        els.btnContainer.appendChild(fragment);
    }

    function updateUI(step) {
        els.btnContainer.innerHTML = '';
        els.customInputArea.style.display = 'none';
        els.step4Content.style.display = 'none';

        if (step === 4) {
            els.step4Content.style.display = 'flex';
            if (report.isInside) {
                els.dialWrapper.style.display = 'flex';
                els.dialGuide.style.display = 'block';
                els.dialSubmitBtn.style.display = 'block';
                els.stationWrapper.style.display = 'none';
                els.scrollGuide.style.display = 'none';
                initDials(parseInt(report.line.replace('호선', '')) || 1);
            } else {
                els.dialWrapper.style.display = 'none';
                els.dialGuide.style.display = 'none';
                els.dialSubmitBtn.style.display = 'none';
                els.stationWrapper.style.display = 'block';
                els.scrollGuide.style.display = 'block';
                renderStationList(report.line);
            }
        } else {
            renderButtons(step);
        }
    }

    function nextStep(type, value) {
        if (type) report[type] = value;
        updateSentenceBox();
        if (currentStep < 4) {
            currentStep++;
            if (currentStep === 3 && report.isInside) { currentStep++; }
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

    function showCustomInputStep() {
        updateSentenceBox();
        els.guideText.innerText = "불편사항을 직접 적어주세요.";
        els.btnContainer.innerHTML = '';
        els.customInputArea.style.display = 'flex';
        els.customInput.value = ''; els.charCount.innerText = "0/200";
        setTimeout(() => els.customInput.focus(), 300);
    }

    function submitCustomInput() {
        const val = els.customInput.value;
        if (!val) { alert("내용을 입력해주세요."); return; }
        report.detailSituation = val; els.customInputArea.style.display = 'none'; nextStep(null, null);
    }

    function showDetailStep(situationText) {
        updateSentenceBox();
        els.guideText.innerText = `"${situationText}"\n구체적으로 어떤가요?`;
        els.customInputArea.style.display = 'none';
        els.btnContainer.innerHTML = '';
        const fragment = document.createDocumentFragment();
        DETAIL_OPTIONS[situationText].forEach(opt => {
            const b = document.createElement('button');
            b.className = 'nav-btn'; b.innerText = opt.text;
            b.onclick = () => { report.detailSituation = opt.val; nextStep(null, null); };
            fragment.appendChild(b);
        });
        els.btnContainer.appendChild(fragment);
    }

    // ----------------------------------------------------
    // 다이얼(Picker) 로직 수정됨: 60px 단위 정렬
    // ----------------------------------------------------
    function createDial(elementId, start, end, initialVal) {
        const container = document.getElementById(elementId);
        container.innerHTML = '';
        
        // 스페이서 높이를 정확히 60px로 설정 (중앙 정렬 핵심)
        const spacer = document.createElement('div');
        spacer.style.height = `${DIAL_ITEM_HEIGHT}px`; 
        spacer.style.flexShrink = '0';
        container.appendChild(spacer);

        const fragment = document.createDocumentFragment();
        for (let i = start; i <= end; i++) {
            const item = document.createElement('div');
            item.className = 'dial-item';
            item.innerText = i;
            item.dataset.val = i;
            fragment.appendChild(item);
        }
        container.appendChild(fragment);
        
        // 하단 스페이서도 60px
        const spacerBottom = spacer.cloneNode(true);
        container.appendChild(spacerBottom);

        container.addEventListener('scroll', () => updateActiveItem(container, elementId));
        
        setTimeout(() => {
            const items = container.querySelectorAll('.dial-item');
            for (let i = 0; i < items.length; i++) {
                if(parseInt(items[i].innerText) == initialVal) {
                    // 항목 인덱스 * 높이 = 스크롤 위치 (스페이서 때문에 0번 인덱스가 0위치가 됨)
                    container.scrollTop = i * DIAL_ITEM_HEIGHT; 
                    break;
                }
            }
            updateActiveItem(container, elementId);
        }, 100);
    }

    function initDials(initialLine) {
        createDial('dialLine', 1, 4, initialLine);
        createDial('dial1', 0, 9, 0);
        createDial('dial2', 0, 9, 0);
        createDial('dial3', 1, 9, 1);
    }

    function updateActiveItem(container, dialId) {
        // 스크롤 위치 / 항목 높이(60) = 현재 인덱스
        let index = Math.round(container.scrollTop / DIAL_ITEM_HEIGHT);
        
        const items = container.querySelectorAll('.dial-item');
        if (index < 0) index = 0;
        if (index >= items.length) index = items.length - 1;

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
            
            report.line = dialValues.line + '호선';
            updateReportDetailFromDial();
            updateSentenceBox();
        }
    }

    function updateReportDetailFromDial() {
        report.detail = `${dialValues.d1}${dialValues.d2}편성 ${dialValues.d3}호차`;
    }
    
    function submitDialData() { updateReportDetailFromDial(); submitFinal(); }

    function renderStationList(lineName) {
        els.stationList.innerHTML = '';
        const stations = STATION_DB[lineName] || ['역 정보 없음'];
        const fragment = document.createDocumentFragment();
        stations.forEach(station => {
            const chip = document.createElement('div');
            chip.className = 'station-chip'; chip.innerText = station;
            chip.onclick = () => {
                const prev = document.querySelectorAll('.station-chip.selected');
                prev.forEach(p => p.classList.remove('selected'));
                chip.classList.add('selected');
                report.detail = station + '역에 있어요';
                submitFinal();
            };
            fragment.appendChild(chip);
        });
        els.stationList.appendChild(fragment);
    }

    function openHelp() { els.modal.style.display = "flex"; }
    function closeHelp() { els.modal.style.display = "none"; }

    window.addEventListener('popstate', function(event) {
        const state = event.state;
        if (!state) { 
            currentStep = 1;
            report = { place: '', situation: '', detailSituation: '', line: '', detail: '', isInside: true };
            updateSentenceBox();
            updateGuideText(1);
            updateUI(1);
            return;
        }
        currentStep = state.step;
        if (state.report) report = state.report; 

        if (state.sub === 'customInput') {
            showCustomInputStep();
        } else if (state.sub === 'detail') {
            showDetailStep(state.detailKey);
        } else {
            updateSentenceBox();
            updateGuideText(currentStep);
            updateUI(currentStep);
        }
    });

    history.replaceState({ step: 1, report: JSON.parse(JSON.stringify(report)) }, null, null);
    updateGuideText(1);
    renderButtons(1);
  </script>
</body>
</html>