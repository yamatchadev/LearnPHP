<?php
require_once 'db.php';
$stmt = $pdo->prepare("SELECT created_at, title FROM news ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$news = $stmt->fetch();
$recentnewsdate = mb_substr($news['created_at'], 0, 10);
$recentnews = $news['title'];

?>

