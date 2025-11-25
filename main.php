<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>부산지하철 민원신고</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <style>
    /* --------------------------------------------------- */
    /* 기본 스타일 */
    /* --------------------------------------------------- */
    body { margin: 0; background: #f8f9fb; font-family: "Pretendard", sans-serif; color: #222; display: flex; flex-direction: column; justify-content: space-between; height: 100vh; overflow: hidden; }
    .sentence-box { background: #ffffff; margin: 12px; border-radius: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 20px; font-size: 1.1rem; text-align: center; color: #1e3a8a; min-height: 80px; line-height: 1.4; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; }
    .step { flex: 1; display: none; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px; opacity: 0; transform: translateY(30px); transition: all 0.4s ease; width: 100%; box-sizing: border-box; }
    .step.active { display: flex; opacity: 1; transform: translateY(0); }
    h2 { font-size: 1.3rem; margin-bottom: 20px; }
    
    /* 하단 버튼 컨테이너 */
    .btn-container { position: fixed; bottom: 0; left: 0; right: 0; display: flex; flex-wrap: wrap; justify-content: center; background: white; padding: 15px 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); border-radius: 16px 16px 0 0; gap: 10px; z-index: 10; }
    button.nav-btn { flex: 1 1 45%; background: #3b82f6; color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; transition: transform 0.2s ease, background 0.3s ease; cursor: pointer; }
    button.nav-btn:active { transform: scale(0.96); background: #2563eb; }
    
    /* --------------------------------------------------- */
    /* 입력 UI 스타일 */
    /* --------------------------------------------------- */
    #display { 
        font-size: 45px; height: 70px; line-height: 70px; width: 100%; max-width: 400px;
        margin-bottom: 20px; background: #fff; 
        border: 2px solid #ddd; border-radius: 12px; 
        font-weight: bold; letter-spacing: 5px; color: #333;
        display: none; 
    }

    .keypad { 
        display: none; 
        grid-template-columns: repeat(3, 1fr); gap: 10px; 
        max-width: 400px; width: 100%; margin: 0 auto; 
    }
    .key-btn { 
        font-size: 24px; padding: 15px; background: #fff; 
        border: none; border-radius: 12px; cursor: pointer; 
        box-shadow: 0 3px 0 #e9ecef; color: #333; font-weight: bold;
    }
    .key-btn:active { transform: translateY(2px); box-shadow: none; background: #f1f3f5; }
    .btn-send { background: #ff6b6b; color: white; box-shadow: 0 3px 0 #fa5252; }
    
    /* 스크롤 UI (역 선택용) */
    .station-scroll-wrapper { width: 100%; overflow-x: auto; display: none; padding: 10px 0; -ms-overflow-style: none; scrollbar-width: none; }
    .station-scroll-wrapper::-webkit-scrollbar { display: none; }
    .station-container { display: inline-flex; gap: 10px; padding: 0 20px; }
    .station-chip { background: white; border: 1px solid #ddd; border-radius: 20px; padding: 10px 20px; white-space: nowrap; color: #555; font-size: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
    .station-chip.selected { background: #3b82f6; color: white; border-color: #3b82f6; box-shadow: 0 4px 8px rgba(59,130,246,0.3); transform: translateY(-2px); font-weight: bold; }
    
    /* 호선 선택 모달 */
    #line-modal {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8); z-index: 999;
        flex-direction: column; justify-content: center; align-items: center;
    }
    .modal-content { background: white; padding: 25px; border-radius: 15px; width: 90%; max-width: 350px; text-align: center; }
    .color-btn { 
        display: block; width: 100%; padding: 15px; margin: 10px 0; 
        color: white; font-size: 18px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;
    }
    .bg-1 { background-color: #f39c12; } 
    .bg-2 { background-color: #12b560; } 
    .bg-3 { background-color: #d1a575; } 
    .bg-4 { background-color: #2e78c9; } 
    .bg-5 { background-color: #70449D; } 

    .fadeIn { animation: fadeIn 0.5s ease forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

  <div class="sentence-box" id="sentenceBox">
    🚇 신고 문장이 여기에 만들어집니다.
  </div>

  <div class="step active fadeIn" id="step1">
    <h2>어디 계신가요?</h2>
  </div>

  <div class="step" id="step2">
    <h2>어떤 상황인가요?</h2>
  </div>

  <div class="step" id="step3">
    <h2>몇 호선인가요?</h2>
  </div>

  <div class="step" id="step4">
    <h2 id="step4-title">몇 칸에 계신가요?</h2>
    
    <div class="guide" id="keypad-guide" style="display:none; font-size: 14px; color: #666; margin-bottom: 20px;">
        보이는 숫자 3~4자리를 입력하세요.<br>(예: 2347 또는 347)
    </div>
    <div id="display">----</div>
    <div class="keypad" id="keypad">
        <button class="key-btn" onclick="press(1)">1</button>
        <button class="key-btn" onclick="press(2)">2</button>
        <button class="key-btn" onclick="press(3)">3</button>
        <button class="key-btn" onclick="press(4)">4</button>
        <button class="key-btn" onclick="press(5)">5</button>
        <button class="key-btn" onclick="press(6)">6</button>
        <button class="key-btn" onclick="press(7)">7</button>
        <button class="key-btn" onclick="press(8)">8</button>
        <button class="key-btn" onclick="press(9)">9</button>
        <button class="key-btn" onclick="clearInput()" style="color:#e03131">지움</button>
        <button class="key-btn" onclick="press(0)">0</button>
        <button class="key-btn btn-send" onclick="checkAndSend()">🚀 전송</button>
    </div>

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

  <div id="line-modal">
    <div class="modal-content">
        <h3>🎨 노선 색깔을 선택하세요</h3>
        <p>입력하신 번호에 호선 정보가 없습니다.<br>지금 타고 계신 노선 색깔은?</p>
        <button class="color-btn bg-1" onclick="selectLine('1')">🧡 1호선 (주황색)</button>
        <button class="color-btn bg-2" onclick="selectLine('2')">💚 2호선 (초록색)</button>
        <button class="color-btn bg-3" onclick="selectLine('3')">🤎 3호선 (갈색)</button>
        <button class="color-btn bg-4" onclick="selectLine('4')">💙 4호선 (파란색)</button>
        <button class="color-btn bg-5" onclick="selectLine('부산김해경전철')">💜 부산김해경전철 (보라색)</button>
        <button onclick="closeModal()" style="margin-top:10px; background:#ddd; padding:10px; width:100%; border-radius:8px; border:none;">취소</button>
    </div>
  </div>

  <script>
    const steps = document.querySelectorAll('.step');
    const btnContainer = document.getElementById('btnContainer');
    const sentenceBox = document.getElementById('sentenceBox');
    
    // 요소 참조
    const step4Title = document.getElementById('step4-title');
    const stationWrapper = document.getElementById('stationWrapper');
    const stationList = document.getElementById('stationList');
    const scrollGuide = document.getElementById('scroll-guide');
    
    // 키패드 관련 요소
    const keypad = document.getElementById('keypad');
    const display = document.getElementById('display');
    const keypadGuide = document.getElementById('keypad-guide');
    
    // 폼 참조
    const hiddenContent = document.getElementById('hiddenContent');
    const finalForm = document.getElementById('finalForm');

    let currentStep = 1;
    let inputNum = ""; 

    let report = {
      place: '',
      situation: '',
      line: '',
      detail: '', 
      isInside: true
    };

    const stationDB = {
      '1호선': ['다대포해수욕장','다대포항','낫개','신장림','장림','동매','신평','하단','당리','사하','괴정','대티','서대신','동대신','토성','자갈치','남포','중앙','부산역','초량','부산진','좌천','범일','범내골','서면','부전','양정','시청','연산','교대','동래','명륜','온천장','부산대','장전','구서','두실','남산','범어사','노포'],
      '2호선': ['장산','중동','해운대','동백','벡스코','센텀시티','민락','수영','광안','금련산','남천','경성대부경대','대연','못골','지게골','문현','국제금융센터','전포','서면','부암','가야','동의대','개금','냉정','주례','감전','사상','덕포','모덕','모라','구남','구명','덕천','수정','화명','율리','동원','금곡','호포','증산','부산대양산','남양산','양산'],
      '3호선': ['수영','망미','배산','물만골','연산','거제','종합운동장','사직','미남','만덕','남산정','숙등','덕천','구포','강서구청','체육공원','대저'],
      '4호선': ['미남','동래','수안','낙민','충렬사','명장','서동','금사','반여농산물','석대','영산대','윗반송','고촌','안평'],
      '부산김해경전철': ['사상','괘법르네시떼','서부산유통','공항','덕두','등구','대저','평강','대사','불암','지내','안동','김해대학','인제대','김해시청','부원','봉황','수로왕릉','박물관','연지공원','장신대','가야대']
    };

    // -----------------------------------------------------
    // [중요] 즉시 전송 함수
    // -----------------------------------------------------
    function submitFinal() {
        updateSentenceBox();
        hiddenContent.value = makeSentence();
        
        // 전송 전 알림 (테스트용, 실제 서비스시 제거 가능)
        alert("🚨 신고가 즉시 전송됩니다:\n" + hiddenContent.value);
        
        finalForm.submit();
    }

    // -----------------------------------------------------
    // 키패드 로직
    // -----------------------------------------------------
    function press(n) {
        if(inputNum.length < 4) {
            inputNum += n;
            document.getElementById("display").innerText = inputNum;
        }
    }

    function clearInput() {
        inputNum = "";
        document.getElementById("display").innerText = "----";
    }

    function checkAndSend() {
        if (inputNum.length === 4) {
            const firstChar = inputNum.charAt(0);
            if(['1','2','3','4'].includes(firstChar)) {
                report.line = firstChar + '호선';
            }
            const carOrder = inputNum.charAt(1);
            const trainSet = inputNum.substring(2, 4);
            report.detail = trainSet + '편성 ' + carOrder + '호차';

            // [변경] 입력 완료 시 다음 단계가 아니라 즉시 전송
            submitFinal(); 
        } else if (inputNum.length === 3) {
            document.getElementById("line-modal").style.display = "flex";
        } else {
            alert("숫자 3자리 또는 4자리를 입력해주세요.");
        }
    }

    function closeModal() {
        document.getElementById("line-modal").style.display = "none";
    }

    function selectLine(line) {
        closeModal();

        if (line === '부산김해경전철') {
            report.line = '부산김해경전철';
            report.detail = '차량번호 ' + inputNum + '호';
            document.getElementById("display").innerText = inputNum;
        } else {
            let fixedNum = line + inputNum;
            report.line = line + '호선';
            const carOrder = fixedNum.charAt(1);
            const trainSet = fixedNum.substring(2, 4);
            report.detail = trainSet + '편성 ' + carOrder + '호차';
            document.getElementById("display").innerText = fixedNum;
        }

        // [변경] 호선 선택 시 다음 단계가 아니라 즉시 전송
        submitFinal();
    }

    // -----------------------------------------------------
    // 메인 스텝 로직
    // -----------------------------------------------------

    const stepButtons = {
      1: [
        { text: '지하철 안이에요', action: () => { report.isInside = true; nextStep('place', '지하철 안인데'); } },
        { text: '지하철 밖이에요', action: () => { report.isInside = false; nextStep('place', '지하철 밖인데'); } }
      ],
      2: [
        { text: '잡상인이 있어요', action: () => nextStep('situation', '잡상인이 있어요') },
        { text: '싸움이 일어났어요', action: () => nextStep('situation', '싸움이 일어났어요') },
        { text: '에어컨이 추워요', action: () => nextStep('situation', '에어컨이 추워요') },
        { text: '지저분해요', action: () => nextStep('situation', '지저분해요') },
        { text: '시설물이 파손됐어요', action: () => nextStep('situation', '시설물이 파손됐어요') }
      ],
      3: [
        { text: '1호선', action: () => nextStep('line', '1호선') },
        { text: '2호선', action: () => nextStep('line', '2호선') },
        { text: '3호선', action: () => nextStep('line', '3호선') },
        { text: '4호선', action: () => nextStep('line', '4호선') },
        { text: '부산김해경전철', action: () => nextStep('line', '부산김해경전철') }
      ],
      4: [
        // [지하철 밖]일 때만 이 버튼이 보임
        { text: '🚀 위치 전송', action: () => {
            if (!report.isInside) {
               const selectedChip = document.querySelector('.station-chip.selected');
               if (!selectedChip) { alert('역을 선택해주세요.'); return; }
               report.detail = selectedChip.innerText + '역에 있어요';
               
               // [변경] 즉시 전송
               submitFinal();
            }
          }}
      ]
      // Step 5 버튼 삭제됨
    };

    function renderButtons(step) {
      btnContainer.innerHTML = '';
      
      // Step 4이고 지하철 안(키패드)인 경우, 하단 버튼 숨김 (키패드에 전송 버튼 있음)
      if (step === 4 && report.isInside) {
          return; 
      }

      stepButtons[step].forEach(btn => {
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
      if (report.situation) sentence += report.situation + ' ';
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

      // Step 4까지만 진행하고 그 이후는 자동 전송이므로 currentStep 조건 변경 불필요
      if (currentStep < 4) {
        steps[currentStep - 1].classList.remove('active');
        currentStep++;
        
        // 지하철 안(Inside)이면 Step 3(호선 선택) 건너뛰고 바로 Step 4
        if (currentStep === 3 && report.isInside) {
             currentStep++; 
        }

        steps[currentStep - 1].classList.add('active');

        // Step 4 진입 시 UI 분기
        if (currentStep === 4) {
            clearInput(); 

            if (report.isInside) {
                // [지하철 안] -> 키패드 활성화
                step4Title.innerText = "차량 번호를 입력해주세요";
                keypad.style.display = 'grid';
                display.style.display = 'block';
                keypadGuide.style.display = 'block';
                stationWrapper.style.display = 'none';
                scrollGuide.style.display = 'none';
            } else {
                // [지하철 밖] -> 역 선택 리스트 활성화
                step4Title.innerText = "현재 무슨 역에 계신가요?";
                keypad.style.display = 'none';
                display.style.display = 'none';
                keypadGuide.style.display = 'none';
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