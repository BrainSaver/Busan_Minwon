<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>부산지하철 민원신고</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* 기존 스타일 유지 */
    body { margin: 0; background: #f8f9fb; font-family: "Pretendard", sans-serif; color: #222; display: flex; flex-direction: column; justify-content: space-between; height: 100vh; overflow: hidden; }
    .sentence-box { background: #ffffff; margin: 12px; border-radius: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 20px; font-size: 1.1rem; text-align: center; color: #1e3a8a; min-height: 80px; line-height: 1.4; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; }
    .step { flex: 1; display: none; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px; opacity: 0; transform: translateY(30px); transition: all 0.4s ease; width: 100%; box-sizing: border-box; }
    .step.active { display: flex; opacity: 1; transform: translateY(0); }
    h2 { font-size: 1.3rem; margin-bottom: 20px; }
    .btn-container { position: fixed; bottom: 0; left: 0; right: 0; display: flex; flex-wrap: wrap; justify-content: center; background: white; padding: 15px 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); border-radius: 16px 16px 0 0; gap: 10px; z-index: 10; }
    button { flex: 1 1 45%; background: #3b82f6; color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; transition: transform 0.2s ease, background 0.3s ease; cursor: pointer; }
    button:active { transform: scale(0.96); background: #2563eb; }
    
    /* 입력창 스타일 통합 */
    input[type="text"], input[type="tel"] { border: none; border-radius: 8px; padding: 10px; font-size: 16px; width: 80%; box-shadow: 0 2px 6px rgba(0,0,0,0.1); text-align: center; margin-bottom: 10px; }
    input:focus { outline: none; box-shadow: 0 2px 6px rgba(59,130,246,0.5); }

    /* 스크롤 UI 스타일 */
    .station-scroll-wrapper { width: 100%; overflow-x: auto; display: none; padding: 10px 0; -ms-overflow-style: none; scrollbar-width: none; }
    .station-scroll-wrapper::-webkit-scrollbar { display: none; }
    .station-container { display: inline-flex; gap: 10px; padding: 0 20px; }
    .station-chip { background: white; border: 1px solid #ddd; border-radius: 20px; padding: 10px 20px; white-space: nowrap; color: #555; font-size: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
    .station-chip.selected { background: #3b82f6; color: white; border-color: #3b82f6; box-shadow: 0 4px 8px rgba(59,130,246,0.3); transform: translateY(-2px); font-weight: bold; }
    
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
    <input type="text" id="userInput" placeholder="예: 3칸">
    <div class="station-scroll-wrapper" id="stationWrapper">
      <div class="station-container" id="stationList"></div>
    </div>
    <p id="scroll-guide" style="font-size:0.8rem; color:#888; margin-top:10px; display:none;">
      ⬅️ 좌우로 밀어서 역을 선택하세요 ➡️
    </p>
  </div>

  <div class="step" id="step5">
    <h2>🚨 신고 접수</h2>
    <p style="margin-bottom:10px; color:#555;">처리 결과를 위해 연락처를 입력해주세요.</p>
    
    <form id="finalForm" action="report_process.php" method="POST" style="width:100%;">
        <input type="tel" name="phone" id="phoneInput" placeholder="010-0000-0000" required>
        <input type="hidden" name="content" id="hiddenContent">
    </form>
    
    <div id="result" style="font-size: 1.1rem; font-weight: bold; color: #222; margin-top:15px;"></div>
  </div>

  <div class="btn-container" id="btnContainer"></div>

  <script>
    const steps = document.querySelectorAll('.step');
    const btnContainer = document.getElementById('btnContainer');
    const sentenceBox = document.getElementById('sentenceBox');
    
    // 요소 참조
    const step4Title = document.getElementById('step4-title');
    const userInput = document.getElementById('userInput');
    const stationWrapper = document.getElementById('stationWrapper');
    const stationList = document.getElementById('stationList');
    const scrollGuide = document.getElementById('scroll-guide');
    
    // 추가된 참조
    const phoneInput = document.getElementById('phoneInput');
    const hiddenContent = document.getElementById('hiddenContent');
    const finalForm = document.getElementById('finalForm');

    let currentStep = 1;

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
        { text: '다음으로', action: () => {
            let detailVal = '';
            if (report.isInside) {
               detailVal = userInput.value;
               if (!detailVal) { alert('칸 번호를 입력해주세요.'); return; }
               report.detail = detailVal + '이에요';
            } else {
               const selectedChip = document.querySelector('.station-chip.selected');
               if (!selectedChip) { alert('역을 선택해주세요.'); return; }
               detailVal = selectedChip.innerText;
               report.detail = detailVal + '역에 있어요';
            }
            nextStep();
          }}
      ],
      5: [
        // 실제 DB 전송 버튼
        { text: '📢 신고 접수하기', action: () => {
            if(!phoneInput.value) {
                alert("연락처를 입력해주세요.");
                phoneInput.focus();
                return;
            }
            // 최종 문장을 hidden input에 넣고 폼 제출
            hiddenContent.value = makeSentence();
            finalForm.submit();
          }}
      ]
    };

    function renderButtons(step) {
      btnContainer.innerHTML = '';
      stepButtons[step].forEach(btn => {
        const b = document.createElement('button');
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

      if (currentStep < steps.length) {
        steps[currentStep - 1].classList.remove('active');
        currentStep++;
        steps[currentStep - 1].classList.add('active');

        if (currentStep === 4) {
            userInput.value = ''; 
            if (report.isInside) {
                step4Title.innerText = "몇 번째 칸에 계신가요?";
                userInput.style.display = 'inline-block';
                userInput.placeholder = "예: 3번째 칸, 3102호";
                stationWrapper.style.display = 'none';
                scrollGuide.style.display = 'none';
            } else {
                step4Title.innerText = "현재 무슨 역에 계신가요?";
                userInput.style.display = 'none';
                stationWrapper.style.display = 'block';
                scrollGuide.style.display = 'block';
                renderStationList(report.line);
            }
        }

        if (currentStep === 5) {
          document.getElementById('result').innerText = '"' + makeSentence() + '"';
        }

        renderButtons(currentStep);
      }
    }

    renderButtons(1);
  </script>
</body>
</html>