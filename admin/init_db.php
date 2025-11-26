<?php
$h='localhost'; $port='3306'; $u='root'; $pw=''; $db='busan_minwon_system';
try{
    $pdo=new PDO("mysql:host=$h;port=$port;charset=utf8mb4",$u,$pw,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo=new PDO("mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4",$u,$pw,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports(
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        status VARCHAR(20) DEFAULT '접수대기',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    if((int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn() === 0){
        $pdo->prepare("INSERT INTO reports (phone,content,status) VALUES (?,?,?)")
            ->execute(['010-1234-5678','가로등이 고장났어요.','접수대기']);
    }

    echo "완료";
}catch(PDOException $e){ die($e->getMessage()); }
?>
