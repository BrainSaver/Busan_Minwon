<?php
// main.php
$imap_env_path = __DIR__ . '/admin/imap.env';
$myEmail = 'busanminwon@gmail.com'; // 기본값

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
    /* 기본 스타일 */
    body { margin: 0; background: #f8f9fb; font-family: "Pretendard", sans-serif; color: #222; display: flex; flex-direction: column; justify-content: space-between; height: 100vh; overflow: hidden; }
    .sentence-box { background: #ffffff; margin: 12px; border-radius: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 20px; font-size: 1.1rem; text-align: center; color: #1e3a8a; min-height: 80px; line-height: 1.4; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; z-index: 5; }
    .step { 
        flex: 1; 
        display: none; 
        flex-direction: column; 
        justify-content: flex-start; 
        padding-top: 15vh; 
        align-items: center; 
        text-align: center; 
        padding-left: 20px; 
        padding-right: 20px;
        opacity: 0; 
        transform: translateY(30px); 
        transition: all 0.4s ease; 
        width: 100%; 
        box-sizing: border-box; 
    }
    .step.active { display: flex; opacity: 1; transform: translateY(0); }
    h2 { 
        font-size: 1.3rem; 
        margin-bottom: 40px; 
    }
    
    .btn-container { position: fixed; bottom: 0; left: 0; right: 0; display: flex; flex-wrap: wrap; justify-content: center; background: white; padding: 15px 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); border-radius: 16px 16px 0 0; gap: 10px; z-index: 10; }
    button.nav-btn { flex: 1 1 45%; background: #3b82f6; color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; transition: transform 0.2s ease, background 0.3s ease; cursor: pointer; }
    button.nav-btn:active { transform: scale(0.96); background: #2563eb; }


    /* 뒤로가기 버튼 */
    .nav-btn-row {
        width: 100%;
        padding: 5px 10px 15px 10px; 
        display: flex; /* Flexbox 적용 */
        justify-content: space-between; /* 양쪽 끝으로 배치 */
        align-items: center;
    }
    /* 텍스트형 버튼 공통 스타일 */
    .nav-text-btn {
        background: none;
        border: none;
        color: #555;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px;
    }
    .nav-text-btn:hover { color: #3b82f6; }
    
    /* 직접 입력 스타일 */
    
    #customInputArea {
        width: 100%; max-width: 350px; display: none; flex-direction: column; gap: 15px;
    }
    #customInput {
        padding: 15px; 
        font-size: 18px; 
        border: 2px solid #ddd; 
        border-radius: 12px; 
        width: 100%; 
        box-sizing: border-box; 
        transition: border-color 0.3s;
        
        font-family: "Pretendard", sans-serif; 
        resize: none; 
        overflow-y: auto; 
        min-height: 56px; 
        max-height: 200px; 
        line-height: 1.5; 
    }
    #customInput:focus { outline: none; border-color: #3b82f6; }

    #charCount {
        text-align: right;
        font-size: 13px;
        color: #888;
        margin-top: -10px;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .input-confirm-btn {
        width: 100%; padding: 15px; background: #3b82f6; color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: bold; cursor: pointer;
    }

    /* --------------------------------------------------- */
    /* [수정] 다이얼(Spinner) 스타일 */
    /* --------------------------------------------------- */
    .dial-wrapper {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 380px; /* 폭을 약간 늘림 */
        height: 160px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 20px;
        user-select: none; 
        -webkit-user-select: none;
    }

    .dial-highlight {
        position: absolute;
        top: 50%;
        left: 0;
        width: 100%;
        height: 50px;
        margin-top: -25px;
        background-color: rgba(59, 130, 246, 0.1);
        border-top: 1px solid #3b82f6;
        border-bottom: 1px solid #3b82f6;
        pointer-events: none;
        z-index: 1;
    }

    .dial-column {
        height: 100%;
        width: 50px; /* 컬럼 폭 조정 */
        overflow-y: scroll;
        scroll-snap-type: y mandatory;
        -ms-overflow-style: none;
        scrollbar-width: none;
        z-index: 2;
        padding: 0;
    }
    .dial-column::-webkit-scrollbar { display: none; }

    .dial-item {
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #ccc;
        scroll-snap-align: center;
        transition: all 0.2s ease;
    }
    .dial-item.active {
        color: #222;
        font-weight: bold;
        font-size: 30px;
        transform: scale(1.1);
    }

    .dial-label {
        font-size: 18px; /* 글자 크기 조정 */
        font-weight: bold;
        color: #333;
        margin: 0 3px;
        z-index: 2;
        padding-bottom: 3px;
    }

    .btn-send-dial {
        width: 100%; max-width: 360px;
        padding: 15px;
        background: #ff6b6b; color: white;
        border: none; border-radius: 12px;
        font-size: 1.2rem; font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
        transition: transform 0.2s;
    }
    .btn-send-dial:active { transform: scale(0.98); }

    /* 역 선택 스크롤 */
    .station-scroll-wrapper { width: 100%; overflow-x: auto; display: none; padding: 10px 0; -ms-overflow-style: none; scrollbar-width: none; }
    .station-scroll-wrapper::-webkit-scrollbar { display: none; }
    .station-container { display: inline-flex; gap: 10px; padding: 0 20px; }
    .station-chip { background: white; border: 1px solid #ddd; border-radius: 20px; padding: 10px 20px; white-space: nowrap; color: #555; font-size: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
    .station-chip.selected { background: #3b82f6; color: white; border-color: #3b82f6; box-shadow: 0 4px 8px rgba(59,130,246,0.3); transform: translateY(-2px); font-weight: bold; }
    
    /* 모달 */
    #help-modal {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); z-index: 1000;
        flex-direction: column; justify-content: center; align-items: center;
    }
    .help-content { background: white; padding: 25px; border-radius: 15px; width: 85%; max-width: 350px; text-align: center; }
    .help-content img { width: 100%; border-radius: 8px; margin-bottom: 20px; border: 2px solid #eee; }
    .help-text { font-size: 1.4rem; color: #111; font-weight: bold; line-height: 1.5; margin-bottom: 25px; word-break: keep-all; }
    .help-btn-open { background: none; border: none; color: #3b82f6; font-size: 0.95rem; font-weight: bold; cursor: pointer; text-decoration: underline; margin-top: 8px; }

    .fadeIn { animation: fadeIn 0.5s ease forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

  <div class="sentence-box" id="sentenceBox">
    🚇 신고 문장이 여기에 만들어집니다.
  </div>

  <div class="step main-step active fadeIn" id="step1">
    <h2>지금 어디 계신가요?</h2>
  </div>

  <div class="step" id="step-detail">
    <h2 id="detail-title">더 자세한 내용을 알려주세요</h2>

    <div id="customInputArea">
        <textarea id="customInput" rows="1" maxlength="200" placeholder="여기를 눌러서 입력해주세요"></textarea>
        <div id="charCount">0/200</div>
        <button class="input-confirm-btn" onclick="submitCustomInput()">입력 완료</button>
    </div>
  </div>

  <div class="step main-step" id="step2">
    <h2>어떤 상황인가요?</h2>
  </div>

  <div class="step main-step" id="step3">
    <h2>몇 호선인가요?</h2>
  </div>

  <div class="step main-step" id="step4">
    <h2 id="step4-title">정보를 선택해주세요</h2>
    
    <div class="guide" id="dial-guide" style="display:none; font-size: 14px; color: #666; margin-bottom: 15px;">
        위아래로 드래그하여 번호를 맞춰주세요.<br>
        <button class="help-btn-open" onclick="openHelp()">📷 번호가 어디 있나요?</button>
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

    <button class="btn-send-dial" id="dialSubmitBtn" style="display:none;" onclick="submitDialData()">
        🚀 신고 전송하기
    </button>

    <div class="station-scroll-wrapper" id="stationWrapper">
      <div class="station-container" id="stationList"></div>
    </div>
    <p id="scroll-guide" style="font-size:0.8rem; color:#888; margin-top:10px; display:none;">
      ⬅️ 좌우로 밀어서 역을 선택하세요 ➡️
    </p>
  </div>

  <form id="finalForm" action="report_process.php" method="POST" style="display:none;">
      <input type="hidden" name="phone" value="익명"> <input type="hidden" name="content" id="hiddenContent">
  </form>

  <div class="btn-container" id="btnContainer"></div>

  <div id="help-modal">
    <div class="help-content">
        <img src="train_guide.jpg" alt="차량번호 예시">
        <p class="help-text">
            출입문 위 또는<br>통로문 상단에 적힌<br>번호를 입력해주세요.
        </p>
        <button onclick="closeHelp()" style="width:100%; padding:15px; background:#3b82f6; color:white; border:none; border-radius:10px; font-size:1.1rem; font-weight:bold; cursor:pointer;">확인했습니다</button>
    </div>
  </div>

  <script>
    const steps = document.querySelectorAll('.main-step');
    const detailStepEl = document.getElementById('step-detail');
    const detailTitle = document.getElementById('detail-title');
    const customInputArea = document.getElementById('customInputArea'); 
    
    const btnContainer = document.getElementById('btnContainer');
    const sentenceBox = document.getElementById('sentenceBox');
    
    const step4Title = document.getElementById('step4-title');
    const stationWrapper = document.getElementById('stationWrapper');
    const stationList = document.getElementById('stationList');
    const scrollGuide = document.getElementById('scroll-guide');
    
    const dialWrapper = document.getElementById('dialWrapper');
    const dialGuide = document.getElementById('dial-guide');
    const dialSubmitBtn = document.getElementById('dialSubmitBtn');
    
    const hiddenContent = document.getElementById('hiddenContent');
    const finalForm = document.getElementById('finalForm');

    let currentStep = 1;
    let report = {
      place: '',
      situation: '',
      detailSituation: '',
      line: '',
      detail: '', 
      isInside: true
    };

    // 다이얼 값 저장 (line 추가됨)
    let dialValues = { line: 1, d1: 0, d2: 0, d3: 1 };

    const stationDB = {
      '1호선': ['다대포해수욕장','다대포항','낫개','신장림','장림','동매','신평','하단','당리','사하','괴정','대티','서대신','동대신','토성','자갈치','남포','중앙','부산역','초량','부산진','좌천','범일','범내골','서면','부전','양정','시청','연산','교대','동래','명륜','온천장','부산대','장전','구서','두실','남산','범어사','노포'],
      '2호선': ['장산','중동','해운대','동백','벡스코','센텀시티','민락','수영','광안','금련산','남천','경성대부경대','대연','못골','지게골','문현','국제금융센터','전포','서면','부암','가야','동의대','개금','냉정','주례','감전','사상','덕포','모덕','모라','구남','구명','덕천','수정','화명','율리','동원','금곡','호포','증산','부산대양산','남양산','양산'],
      '3호선': ['수영','망미','배산','물만골','연산','거제','종합운동장','사직','미남','만덕','남산정','숙등','덕천','구포','강서구청','체육공원','대저'],
      '4호선': ['미남','동래','수안','낙민','충렬사','명장','서동','금사','반여농산물','석대','영산대','윗반송','고촌','안평']
    };

    const detailOptions = {
      // 지하철 안이에요
        '온도 조절이 필요해요': [ 
            { text: '더워요', val: '더워요' }, 
            { text: '추워요', val: '추워요' } 
        ],
        '민폐를 끼쳐요': [
            { text: '위협을 해요', val: '위협을 해요' },
            { text: '욕설을 해요', val: '욕설을 해요' },
            { text: '구걸을 해요', val: '구걸을 해요' },
            { text: '종교 권유를 해요', val: '종교 권유를 해요' },
            { text: '시끄럽게 떠들어요', val: '시끄럽게 떠들어요' },
            { text: '음식물을 먹어요', val: '음식물을 먹어요' },
            { text: '빈좌석을 못쓰게 해요', val: '빈좌석을 못쓰게 해요' },
            { text: '의자에 누워있어요', val: '의자에 누워있어요' }
        ],
        '청소가 필요해요': [
            { text: '광고지가 붙어있어요', val: '광고지가 붙어있어요' },
            { text: '의자 시트가 더러워요', val: '의자 시트가 더러워요' },
            { text: '바닥에 쓰레기가 많아요', val: '바닥에 쓰레기가 많아요' },
            { text: '바닥에 액체가 흘렀어요', val: '바닥에 액체가 흘렀어요' },
            { text: '벽이 더러워요', val: '벽이 더러워요' },
            { text: '손잡이가 더러워요', val: '손잡이가 더러워요' }
        ],
        '시설물이 파손됐어요': [
            { text: '의자가 파손됐어요', val: '의자가 파손됐어요' },
            { text: '손잡이가 파손됐어요', val: '손잡이가 파손됐어요' },
            { text: '유리창이 깨져있어요', val: '유리창이 깨져있어요' },
            { text: '방송 스피커가 고장났어요', val: '방송 스피커가 고장났어요' },
            { text: '안내판이 고장났어요', val: '안내판이 고장났어요' },
            { text: '조명이 고장났어요', val: '조명이 고장났어요' }
        ],
        //지하철 밖이에요
        '소란을 피워요': [
            { text: '싸움이 일어났어요', val: '싸움이 일어났어요' },
            { text: '위협을 해요', val: '위협을 해요' },
            { text: '욕설을 해요', val: '욕설을 해요' },
            { text: '종교 권유를 해요', val: '종교 권유를 해요' },
            { text: '전단지를 돌려요', val: '전단지를 돌려요' },
            { text: '연설을 해요', val: '연설을 해요' },
            { text: '의자에 누워있어요', val: '의자에 누워있어요' }
        ],
        '청소가 안돼있어요': [
            { text: '광고지가 붙어있어요', val: '광고지가 붙어있어요' },
            { text: '쓰레기통이 넘쳐요', val: '쓰레기통이 넘쳐요' },
            { text: '바닥에 쓰레기가 많아요', val: '바닥에 쓰레기가 많아요' },
            { text: '바닥에 액체가 흘렀어요', val: '바닥에 액체가 흘렀어요' },
            { text: '의자가 더러워요', val: '의자가 더러워요' },
            { text: '벽이 더러워요', val: '벽이 더러워요' }
        ],
        '시설물이 고장났어요': [
            { text: '개찰구가 고장났어요', val: '개찰구가 고장났어요' },
            { text: '에스컬레이터가 고장났어요', val: '에스컬레이터가 고장났어요' },
            { text: '엘리베이터가 고장났어요', val: '엘리베이터가 고장났어요' },
            { text: '계단이 깨져있어요', val: '계단이 깨져있어요' },
            { text: '발매기/충전기가 고장났어요', val: '발매기/충전기가 고장났어요' },
            { text: '보관함이 고장났어요', val: '보관함이 고장났어요' },
            { text: '자판기가 고장났어요', val: '자판기가 고장났어요' },
            { text: '유리창이 깨져있어요', val: '유리창이 깨져있어요' },
            { text: '안내판이 고장났어요', val: '안내판이 고장났어요' },
            { text: '조명이 고장났어요', val: '조명이 고장났어요' }
        ],
        '화장실에 문제가 있어요': [
            { text: '청소가 필요해요(남자)', val: '청소가 필요해요(남자)' },
            { text: '청소가 필요해요(여자)', val: '청소가 필요해요(여자)' },
            { text: '시설이 고장났어요(남자)', val: '시설이 고장났어요(남자)' },
            { text: '시설이 고장났어요(여자)', val: '시설이 고장났어요(여자)' },
            { text: '휴지가 없어요(남자)', val: '휴지가 없어요(남자)' },
            { text: '휴지가 없어요(여자)', val: '휴지가 없어요(여자)' }
        ],
    };

    function openHelp() { document.getElementById("help-modal").style.display = "flex"; }
    function closeHelp() { document.getElementById("help-modal").style.display = "none"; }

    async function submitFinal() {
        updateSentenceBox();
        const finalSentence = makeSentence();
        const targetEmail = "<?= isset($myEmail) ? $myEmail : 'busanminwon@gmail.com' ?>"; 

        if(!confirm("신고 내용을 전송하시겠습니까?")) return;

        let token = "";
        try {
            const response = await fetch('save_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: finalSentence })
            });
            const text = await response.text(); 
            try {
                const result = JSON.parse(text);
                if (result.success) token = result.token;
            } catch (e) {}
        } catch (error) {}

        let msg = finalSentence;
        if(token) msg += "\n\n[접수코드:" + token + "]"; 
        else msg += "\n\n(서버 저장 실패로 접수코드 없음)";

        const encodedBody = encodeURIComponent(msg);
        const separator = navigator.userAgent.match(/iPhone/i) ? "&" : "?";
        location.href = "sms:" + targetEmail + separator + "body=" + encodedBody;
    }

    // [수정됨] 다이얼 초기화 (호선 다이얼 추가)
    function initDials(initialLine) {
        // 호선: 1~4호선
        createDial('dialLine', 1, 4, initialLine);
        // 편성: 0~9
        createDial('dial1', 0, 9, 0); 
        createDial('dial2', 0, 9, 0); 
        // 호차: 1~9
        createDial('dial3', 1, 9, 1); 
    }

    function createDial(elementId, start, end, initialVal) {
        const container = document.getElementById(elementId);
        container.innerHTML = '';
        container.appendChild(createSpacer());

        for (let i = start; i <= end; i++) {
            const item = document.createElement('div');
            item.className = 'dial-item';
            item.innerText = i;
            item.dataset.val = i;
            container.appendChild(item);
        }

        container.appendChild(createSpacer());

        container.addEventListener('scroll', () => {
            updateActiveItem(container, elementId);
        });

        setTimeout(() => {
            const items = container.querySelectorAll('.dial-item');
            items.forEach(item => {
                if(parseInt(item.innerText) == initialVal) {
                    container.scrollTop = item.offsetTop - container.offsetTop - 55;
                }
            });
            updateActiveItem(container, elementId);
        }, 100);
    }

    function createSpacer() {
        const div = document.createElement('div');
        div.style.height = "55px"; 
        div.style.flexShrink = "0";
        return div;
    }

    function updateActiveItem(container, dialId) {
        const items = container.querySelectorAll('.dial-item');
        let closest = null;
        let minDiff = Infinity;
        const containerCenter = container.scrollTop + (container.clientHeight / 2);

        items.forEach(item => {
            const itemCenter = item.offsetTop + (item.clientHeight / 2);
            const diff = Math.abs(containerCenter - itemCenter);
            item.classList.remove('active');
            if (diff < minDiff) {
                minDiff = diff;
                closest = item;
            }
        });

        if (closest) {
            closest.classList.add('active');
            
            // 값 업데이트
            if (dialId === 'dialLine') dialValues.line = closest.innerText;
            if (dialId === 'dial1') dialValues.d1 = closest.innerText;
            if (dialId === 'dial2') dialValues.d2 = closest.innerText;
            if (dialId === 'dial3') dialValues.d3 = closest.innerText;
            
            // 호선 정보 업데이트 (다이얼 돌리면 report.line도 갱신)
            report.line = dialValues.line + '호선';
            
            updateReportDetailFromDial();
            updateSentenceBox();
        }
    }

    function updateReportDetailFromDial() {
        // 예: 96편성 5호차
        report.detail = `${dialValues.d1}${dialValues.d2}편성 ${dialValues.d3}호차`;
    }

    function submitDialData() {
        updateReportDetailFromDial();
        submitFinal();
    }

    // 상황 선택 분기 함수
    function handleSituationStep(situationText) {
        report.situation = situationText;
        report.detailSituation = '';

        if (situationText === '직접 입력') {
            showCustomInputStep();
        } else if (detailOptions[situationText]) {
            showDetailStep(situationText);
        } else {
            nextStep(null, null); 
        }
    }

    // 직접 입력 함수
    function showCustomInputStep() {
        updateSentenceBox();
        steps[currentStep - 1].classList.remove('active');
        detailStepEl.classList.add('active');
        detailTitle.innerText = "불편사항을 직접 적어주세요.";

        renderNavButtons(true); // true = sub-step 모드
        customInputArea.style.display = 'flex';

        const input = document.getElementById('customInput');
        const countDisplay = document.getElementById('charCount');
        input.value = '';
        input.style.height = 'auto';
        countDisplay.innerText = "0/200";

        // 내용 입력 시 높이 자동 조절 로직
        input.oninput = function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';

            const currentLength = this.value.length;
            const maxLength = this.getAttribute('maxlength');
            countDisplay.innerText = `${currentLength}/${maxLength}`;
        };

        setTimeout(() => input.focus(), 300);
    }

    function submitCustomInput() {
        const val = document.getElementById('customInput').value;
        if (!val) { alert("내용을 입력해주세요."); return; }
        report.detailSituation = val;
        customInputArea.style.display = 'none';
        detailStepEl.classList.remove('active');
        nextStep(null, null);
    }

    function showDetailStep(situationText) {
        updateSentenceBox();
        steps[currentStep - 1].classList.remove('active');
        detailStepEl.classList.add('active');
        detailTitle.innerText = `"${situationText}"\n구체적으로 어떤가요?`;
        customInputArea.style.display = 'none';

        renderNavButtons(true); 

        const options = detailOptions[situationText];
        options.forEach(opt => {
            const b = document.createElement('button');
            b.className = 'nav-btn';
            b.innerText = opt.text;
            b.onclick = () => {
                report.detailSituation = opt.val;
                detailStepEl.classList.remove('active');
                nextStep(null, null);
            };
            btnContainer.appendChild(b);
        });
    }

    const stepButtons = {
      1: [
        { text: '지하철 안이에요', action: () => { report.isInside = true; nextStep('place', '지하철 안인데'); } },
        { text: '지하철 밖이에요', action: () => { report.isInside = false; nextStep('place', '지하철 밖인데'); } }
      ],
      2: {
        inside: [
          { text: '잡상인이 있어요', action: () => handleSituationStep('잡상인이 있어요') },
          { text: '싸움이 일어났어요', action: () => handleSituationStep('싸움이 일어났어요') },
          { text: '온도 조절이 필요해요', action: () => handleSituationStep('온도 조절이 필요해요') },
          { text: '민폐를 끼쳐요', action: () => handleSituationStep('민폐를 끼쳐요') },
          { text: '청소가 필요해요', action: () => handleSituationStep('청소가 필요해요') },
          { text: '시설물이 파손됐어요', action: () => handleSituationStep('시설물이 파손됐어요') },
          { text: '직접 입력', action: () => handleSituationStep('직접 입력') }
        ],
        outside: [
          { text: '구걸인/노숙인이 있어요', action: () => handleSituationStep('구걸인/노숙인이 있어요') },
          { text: '무임승차를 봤어요', action: () => handleSituationStep('무임승차를 봤어요') },
          { text: '소란을 피워요', action: () => handleSituationStep('소란을 피워요') },
          { text: '청소가 안돼있어요', action: () => handleSituationStep('청소가 안돼있어요') },
          { text: '시설물이 고장났어요', action: () => handleSituationStep('시설물이 고장났어요') },
          { text: '화장실에 문제가 있어요', action: () => handleSituationStep('화장실에 문제가 있어요') },
          { text: '직접 입력', action: () => handleSituationStep('직접 입력') }
        ]
      },
      3: [
        { text: '1호선', action: () => nextStep('line', '1호선') },
        { text: '2호선', action: () => nextStep('line', '2호선') },
        { text: '3호선', action: () => nextStep('line', '3호선') },
        { text: '4호선', action: () => nextStep('line', '4호선') }
      ],
      4: [
        { text: '🚀 위치 전송', action: () => {
            if (!report.isInside) {
               const selectedChip = document.querySelector('.station-chip.selected');
               if (!selectedChip) { alert('역을 선택해주세요.'); return; }
               report.detail = selectedChip.innerText + '역에 있어요';
               submitFinal();
            }
          }}
      ]
    };

    // 뒤로가기 로직 함수
    function goBack() {
        if (detailStepEl.classList.contains('active')) {
            detailStepEl.classList.remove('active');
            customInputArea.style.display = 'none'; 
            steps[currentStep - 1].classList.add('active'); 
            report.situation = ''; 
            report.detailSituation = '';
            updateSentenceBox();
            renderButtons(currentStep); 
            return;
        }

        if (currentStep === 1) {
            location.href = 'first.php';
            return;
        }

        steps[currentStep - 1].classList.remove('active');

        if (currentStep === 4 && report.isInside) {
            currentStep = 2; 
            report.line = ''; 
            report.detail = '';
        } else {
            currentStep--;

            if (currentStep === 3) report.detail = ''; 
            if (currentStep === 2) report.line = '';  
            if (currentStep === 1) report.situation = ''; 
        }

        steps[currentStep - 1].classList.add('active');

        if (currentStep !== 4) {
            dialWrapper.style.display = 'none';
            dialGuide.style.display = 'none';
            dialSubmitBtn.style.display = 'none';
            stationWrapper.style.display = 'none';
            scrollGuide.style.display = 'none';
        }

        updateSentenceBox();
        renderButtons(currentStep);
    }

    // 뒤로가기 버튼 렌더링 헬퍼 함수
    function renderNavButtons(isSubStep = false) {
        btnContainer.innerHTML = ''; 
        
        const row = document.createElement('div');
        row.className = 'nav-btn-row';
        
        const homeBtn = document.createElement('button');
        homeBtn.className = 'nav-text-btn';
        homeBtn.innerHTML = '🏠 홈으로';
        homeBtn.onclick = () => location.href = 'first.php';
        row.appendChild(homeBtn);

        const backBtn = document.createElement('button');
        backBtn.className = 'nav-text-btn';
        backBtn.innerHTML = '뒤로 가기 ↩';
        backBtn.onclick = goBack;
        row.appendChild(backBtn);
        
        btnContainer.appendChild(row);
    }


    // 버튼 구현 함수
    function renderButtons(step) {
      if (step === 4 && report.isInside) {
          renderNavButtons(); 
          return;
      }

      renderNavButtons(); 

      let currentButtons = [];
      if (step === 2) {
          currentButtons = report.isInside ? stepButtons[2].inside : stepButtons[2].outside;
      } else {
          currentButtons = stepButtons[step];
      }

      currentButtons.forEach(btn => {
        const b = document.createElement('button');
        b.className = 'nav-btn';
        b.innerText = btn.text;
        b.onclick = btn.action;
        btnContainer.appendChild(b);
      });
    }

    function updateSentenceBox() {
      const text = makeSentence() || '🚇 신고 문장이 여기에 만들어집니다.';
      sentenceBox.innerText = text;
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
      
      if (report.line) sentence += '지금 ' + report.line + ' ';
      if (report.detail) sentence += report.detail;
      return sentence.trim();
    }

    function renderStationList(lineName) {
      stationList.innerHTML = '';
      const stations = stationDB[lineName] || ['역 정보 없음'];
      stations.forEach(station => {
        const chip = document.createElement('div');
        chip.className = 'station-chip';
        chip.innerText = station;
        chip.onclick = () => {
          document.querySelectorAll('.station-chip').forEach(c => c.classList.remove('selected'));
          chip.classList.add('selected');
        };
        stationList.appendChild(chip);
      });
    }

    function nextStep(type, value) {
      if (type) report[type] = value;
      updateSentenceBox();

      if (currentStep < 4) {
        steps[currentStep - 1].classList.remove('active');
        currentStep++;
        if (currentStep === 3 && report.isInside) { currentStep++; }
        steps[currentStep - 1].classList.add('active');

        if (currentStep === 4) {
            if (report.isInside) {
                step4Title.innerText = "호선과 차량 번호를 맞춰주세요.";

                dialWrapper.style.display = 'flex';
                dialGuide.style.display = 'block';
                dialSubmitBtn.style.display = 'block';
                stationWrapper.style.display = 'none';
                scrollGuide.style.display = 'none';

                // Step3에서 선택한 호선을 초기값으로 다이얼 생성
                const initialLineNum = parseInt(report.line.replace('호선','')) || 1;
                initDials(initialLineNum);
            } else {
                step4Title.style.display = 'block';
                step4Title.innerText = "현재 무슨 역에 계신가요?";
                
                dialWrapper.style.display = 'none';
                dialGuide.style.display = 'none';
                dialSubmitBtn.style.display = 'none';
                stationWrapper.style.display = 'block';
                scrollGuide.style.display = 'block';
                renderStationList(report.line);
            }
        }
        renderButtons(currentStep);
      }
    }

    renderButtons(1);
  </script>
</body>
</html>