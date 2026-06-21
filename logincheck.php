<?php

use function GuzzleHttp\headers_from_lines;

session_start();

if(!isset($_SESSION['user_id'])) {
    if(!isset($_COOKIE['remember_token'])){ //remember_tokenがないとき
        if(isset($page)) {
            header('Location:login.php?to='.$page);
            exit;
        }else{
            header('Location:login.php');
            exit;
        }
    }else{ // remember_tokenがあるとき
        echo "remember_tokenがクッキー上にあります。";
        require_once 'db.php';
        $token = hash('sha256',$_COOKIE['remember_token']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if(!$user){ // DBにない
            echo "クッキーのトークンがDBと一致しません。";
            if(isset($page)) {
                header('Location:login.php?to='.$page);
               exit;
            }else{ // DBにある
                $_SESSION['user_id'] = $user['user_id'];

                exit;
            }
        }
    }   
}


?>