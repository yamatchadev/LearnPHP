<?php
session_start();
if(isset($_SESSION['error'])){
    $error = $_SESSION['error'];
    require_once 'gmail.php';
    $devemail = "noteusforschool@gmail.com";
    $subject = "【LearnPHP】データベースエラーが発生しました";
    $body = <<<EOT
        お客様がサイトにアクセスしたとき、データベース接続に問題が生じました。
        直ちに対応してください。

        EOT;

    sendGmail($devemail, $subject, $body);
}else{
    header('Location: timeline.php');
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー - LearnPHP</title>
</head>
<body>
    <h1></h1>
</body>
</html>