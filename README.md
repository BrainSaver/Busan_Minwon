# 부산 민원 신고 시스템 - 사용자 인증 기능

## 📅 구현된 기능

* **회원가입:** 사용자는 아이디, 이름, 비밀번호를 입력하여 계정을 생성할 수 있습니다.
    * **ID 중복 검사:** 가입 시 이미 존재하는 아이디인지 실시간으로 확인합니다.
    * **비밀번호 암호화:** `password_hash()` 함수(BCRYPT 알고리즘)를 사용하여 비밀번호를 안전하게 해시하여 저장합니다.
* **로그인:** 가입된 아이디와 비밀번호로 로그인을 수행합니다.
    * **비밀번호 검증:** `password_verify()` 함수를 사용하여 입력된 비밀번호와 DB에 저장된 해시 값을 안전하게 비교합니다.
    * **세션 관리:** 로그인 성공 시, PHP 세션(`$_SESSION`)을 생성하여 사용자의 로그인 상태를 유지합니다.
* **데이터베이스 관리:**
    * **DB 초기화:** `init_db.php` 스크립트를 통해 `busan_minwon_system` 데이터베이스와 `user` 테이블을 자동으로 생성합니다.
    * **안전한 DB 연결:** PDO(PHP Data Objects)를 사용하여 데이터베이스에 연결하며, SQL Injection 공격을 방지하기 위해 Prepared Statements를 사용합니다.

## 🛠 사용된 기술

* **백엔드:** PHP
* **데이터베이스:** MySQL (MariaDB)
* **DB 연결:** PDO (PHP Data Objects)
* **프론트엔드:** HTML
* **개발 환경 (권장):** XAMPP 또는 MAMP, WAMP 등 Apache, PHP, MySQL을 지원하는 통합 환경

## 🚀 설치 및 실행 방법

### 1. 개발 환경 준비

1.  [XAMPP](https://www.apachefriends.org/index.html)와 같은 로컬 서버 환경을 설치합니다.
2.  XAMPP 제어판에서 **Apache**와 **MySQL** 서버를 시작(Start)합니다.

### 2. 파일 배치

1.  제공된 PHP 파일들(`login.php`, `register.php`, `init_db.php` 등)을 XAMPP 설치 폴더 내의 `htdocs` 디렉터리 아래에 새 프로젝트 폴더(예: `busan_minwon`)를 만들어 모두 복사합니다.
    * *예시 경로: `C:\xampp\htdocs\busan_minwon\`*

### 3. 데이터베이스 연결 정보 확인

1.  `db_connect.php` 파일과 `init_db.php` 파일을 엽니다.
2.  파일 상단의 DB 연결 정보를 자신의 XAMPP MySQL 설정과 일치하는지 확인합니다. (XAMPP 기본 설정은 대부분 아래와 같습니다.)
    ```php
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // XAMPP 기본 비밀번호는 보통 비어있습니다.
    $port = '3306';
    ```

### 4. 데이터베이스 및 테이블 생성

1.  웹 브라우저를 열고 `init_db.php` 파일에 접속하여 스크립트를 실행합니다.
    * *접속 주소 (예시): `http://localhost/busan_minwon/init_db.php`*
2.  "✅ DB 초기 설정 완료!" 메시지가 나타나면 `busan_minwon_system` 데이터베이스와 `user` 테이블이 성공적으로 생성된 것입니다.

### 5. 시스템 사용

1.  **회원가입:** 웹 브라우저에서 `register.php` 파일에 접속하여 새 계정을 생성합니다.
    * *접속 주소 (예시): `http://localhost/busan_minwon/register.php`*
2.  **로그인:** 회원가입 완료 후 `login.php` 파일에 접속하여 방금 생성한 아이디와 비밀번호로 로그인합니다.
    * *접속 주소 (예시): `http://localhost/busan_minwon/login.php`*
3.  **로그인 성공:** 로그인에 성공하면 `report_main.php` 페이지로 이동합니다.

## 🗂 파일 구조

* `init_db.php`
    * 데이터베이스(`busan_minwon_system`)와 사용자 테이블(`user`)을 생성하는 초기화 스크립트입니다.
* `db_connect.php`
    * PDO를 사용하여 데이터베이스에 연결하는 공통 설정 파일입니다. 다른 PHP 파일에서 `require`로 호출됩니다.
* `register.php`
    * 사용자에게 회원가입 폼을 보여주는 HTML/PHP 페이지입니다.
* `register_process.php`
    * `register.php`에서 받은 폼 데이터를 처리합니다.
    * 아이디 중복을 검사하고, 비밀번호를 해시하여 DB에 저장합니다.
* `login.php`
    * 사용자에게 로그인 폼을 보여주는 HTML/PHP 페이지입니다.
* `login_process.php`
    * `login.php`에서 받은 폼 데이터를 처리합니다.
    * DB에서 사용자 정보를 조회하고 `password_verify`로 비밀번호를 검증한 뒤, 세션을 생성합니다.

## 🔜 향후 개발 계획

* `report_main.php`: 로그인한 사용자만 접근할 수 있는 민원 신고 메인 페이지 구현
* 로그아웃 기능 구현
* 민원(report) 테이블 생성 및 CRUD 기능 구현
