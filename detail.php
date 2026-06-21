<?php
require_once 'logincheck.php';
require_once 'db.php';
require_once 'newscheck.php';

$post = null;
$deleteurl = "";
$deleteable = "false";
$deleteclass = "hidden"; 
if (isset($_GET['contentid'])) {
    $contentid = htmlspecialchars($_GET['contentid']);
    // JOINを使って投稿と投稿者情報を一括取得(AI)
    $stmt = $pdo->prepare(
        "SELECT posts.*, users.nickname, users.icon_path, users.username 
         FROM posts 
         JOIN users ON posts.user_id = users.id 
         WHERE posts.content_id = ?"
    );
    $stmt->execute([$contentid]);
    $post = $stmt->fetch();
    if($post){
        $post['user_id'] = (int)$post['user_id']; 
        $deleteable = isset($_SESSION['user_id']) && $_SESSION['user_id'] === $post['user_id'];
        if($deleteable){
            $deleteurl = "delete.php?contentid=" . $contentid;
            $deleteclass = "back-link";
        }else{
            $deleteurl = "";
            $deleteclass = "hidden";
        }
    }else{
        header('Location: timeline.php');
    }

}else{
    header('Location: timeline.php');
    exit;
}

if (!$post) {
    header('Location: timeline.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>投稿詳細 - LearnPHP</title>
                
        <link rel="stylesheet" id="theme-link" href="css/style-light.css">

        <style>
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: var(--bg-color);
                color: var(--text-color);
            }
            header {
                display: flex;
                flex-direction: column;
                gap: 8px;
                justify-content: space-between;
                align-items: center;
                padding: 12px 20px;
                background-color: var(--card-bg);
                border-bottom: 1px solid var(--border-color);
                position: sticky;
                top: 0;
                z-index: 50;
                transition: background-color 0.3s, border-color 0.3s;
            }
            .header-main-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }

            header img {
                margin-top: 5px;
                height: 45px;
            }
            
            .header-actions {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .theme-toggle-btn {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                padding: 4px;
                line-height: 1;
                user-select: none;
            }
            .theme-toggle-btn:hover {
                background: none;
                transform: scale(1.1);
            }

            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px 15px;
            }

            /* 詳細表示用のカードスタイル */
            .post-card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-bottom: 12px;
                border: 1px solid var(--border-color);
            }
            .front-link {
                position: relative;
                z-index: 2;
            }
            .post-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 15px;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 12px;
            }
            .post-icon {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border-color);
                flex-shrink: 0;
            }
            .post-nickname {
                font-weight: bold;
                font-size: 16px;
                color: var(--primary-color);
            }
            .post-username {
                font-size: 13px;
                color: #a0aec0;
                margin-top: 2px;
                text-decoration: underline;
                text-decoration-color: #a0aec0;
                text-decoration-thickness: 1px; 
            }
            .post-meta {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .post-name-column {
                display: flex;
                flex-direction: column;
            }
            .post-time {
                font-size: 12px;
                color: #a0aec0;
                margin-top: 15px;
                border-top: 1px solid var(--border-color);
                padding-top: 8px;
            }
            .post-content {
                font-size: 16px;
                line-height: 1.6;
                white-space: pre-wrap;
                margin: 0;
            }

            /* タイムラインに戻るボタン */
            .back-nav {
                margin-bottom: 15px;
            }
            .back-link {
                color: var(--primary-color);
                text-decoration: none;
                font-size: 15px;
                font-weight: bold;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .hidden{
                display: none;
            }
            .back-link:hover {
                text-decoration: underline;
            }

            .menu-btn {
                width: 30px;
                height: 24px;
                background: none;
                border: none;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 0;
            }
            .menu-btn span {
                display: block;
                width: 100%;
                height: 3px;
                background-color: var(--text-color);
                border-radius: 2px;
                transition: background-color 0.3s;
            }

            /* サイドメニュー */
            .side-menu {
                position: fixed;
                top: 0;
                right: -300px;
                width: 260px;
                height: 100%;
                background-color: #2d3748;
                transition: right 0.3s ease;
                z-index: 99;
                box-shadow: -4px 0 10px rgba(0,0,0,0.1);
            }
            .side-menu.active {
                right: 0;
            }

            .menu-close-wrapper {
                display: flex;
                justify-content: flex-end;
                padding: 15px 20px;
            }
            .close-btn {
                background: none;
                border: none;
                color: #a0aec0;
                font-size: 28px;
                cursor: pointer;
                line-height: 1;
                padding: 0;
            }
            .close-btn:hover {
                color: #fff;
            }

            .side-menu ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .side-menu ul li a {
                display: block;
                padding: 16px 24px;
                color: #e2e8f0;
                text-decoration: none;
                font-size: 16px;
                border-bottom: 1px solid #4a5568;
                transition: background 0.2s;
            }
            .side-menu ul li a:hover {
                background-color: #4a5568;
                color: #fff;
            }
            .side-menu ul li.danger a {
                color: #feb2b2;
            }
            .side-menu ul li.danger a:hover {
                background-color: #9b2c2c;
                color: #fff;
            }

            header h1 {
	            font-weight: normal;
	            margin: 0; padding: 0;
	            font-size: 0.8rem;
	            letter-spacing: 0.1em;
            }

	        @media screen and (max-width:700px) {
                header h1 {
                    font-size: 0.7em;
                }
            }
        </style>
    </head>
    <body>

        <header>
            <div id="header-top">
                <h1><?= $recentnewsdate; ?> <?= $recentnews; ?><a href="./news.php">詳細</a></h1>
            </div>

            <div class="header-main-row">
                <div>
                    <a href="timeline.php"><img src="img/logo_light.png" alt="LearnPHP" id="headerLogo"></a>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="テーマ切り替え">🌙</button>
                    
                    <button class="menu-btn" id="menuBtn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </header>

        <?php require 'sidemenu.php'; ?>
        <main class="container">
            <div class="back-nav">
                <a href="timeline.php" class="back-link">← タイムラインに戻る</a>
            </div>

            <div class="post-card">
                <div class="post-header">
                    <?php
                        $iconSrc = ($post['icon_path'] && file_exists(__DIR__ . '/' . $post['icon_path']))
                            ? htmlspecialchars($post['icon_path'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($post['nickname']) . '&background=4F5D95&color=fff';
                    ?>
                    <a href="profile.php?username=<?= htmlspecialchars($post['username']) ?>" class="front-link">
                        <img src="<?= $iconSrc ?>" alt="アイコン" class="post-icon">
                    </a>
                    <div class="post-meta">
                        <div class="post-name-column">
                            <div class="post-nickname"><?= htmlspecialchars($post['nickname']) ?></div>
                            <div class="post-username">
                                <a href="profile.php?username=<?= htmlspecialchars($post['username']); ?>" class="front-link" style="color: #a0aec0">@<?= htmlspecialchars($post['username']) ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="post-content"><?= htmlspecialchars($post['content']) ?></p>
                
                <div class="post-time"><?= htmlspecialchars($post['created_at']) ?></div>
            </div>
        <a href="<?= $deleteurl; ?>" class="<?= $deleteclass; ?>" style="color: #e53e3e;">この投稿を削除</a>
            
        </main>

        <script>
        const menuBtn = document.getElementById('menuBtn');
        const closeBtn = document.getElementById('closeBtn');
        const sideMenu = document.getElementById('sideMenu');
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const headerLogo = document.getElementById('headerLogo');
        const themeLink = document.getElementById('theme-link');

        menuBtn.addEventListener('click', () => {
            sideMenu.classList.add('active');
        });

        closeBtn.addEventListener('click', () => {
            sideMenu.classList.remove('active');
        });

        function updateToggleBtnIcon(theme) {
            themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        const currentTheme = localStorage.getItem('theme') || 'light';
        updateToggleBtnIcon(currentTheme);

        if (headerLogo) {
            headerLogo.src = currentTheme === 'dark' ? 'img/logo_dark.png' : 'img/logo_light.png';
        }
        if (themeLink) {
            themeLink.href = currentTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
        }

        themeToggleBtn.addEventListener('click', () => {
            const isDark = themeLink.href.includes('css/style-dark.css');
            const newTheme = isDark ? 'light' : 'dark';
            
            themeLink.href = newTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
            updateToggleBtnIcon(newTheme);
            if (headerLogo) {
                headerLogo.src = newTheme === 'dark' ? 'img/logo_dark.png' : 'img/logo_light.png';
            }

            localStorage.setItem('theme', newTheme);
        });
        </script>
    </body>
</html>