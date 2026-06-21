<?php
$page = basename(__FILE__);
require_once 'logincheck.php';
require_once 'db.php';
require_once 'newscheck.php';
// 投稿フォーム処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    if ($content !== '') {
        $content_clean = htmlspecialchars($content);

        $content_id = '';
        for ($i = 0; $i < 15; $i++) {
            $content_id .= random_int(0, 9);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO posts (user_id, content, content_id) VALUES (?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $content_clean, $content_id]);
    }
    header('Location: timeline.php');
    exit;
}

// ───【追加】ログインユーザーの情報を取得 ───
$user_stmt = $pdo->prepare("SELECT nickname, icon_path, username FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$current_user = $user_stmt->fetch();

// ログインユーザー用のアイコンパスを確定
$current_icon_src = ($current_user['icon_path'] && file_exists(__DIR__ . '/' . $current_user['icon_path']))
    ? htmlspecialchars($current_user['icon_path'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['nickname'] ?? 'User') . '&background=4F5D95&color=fff';
// ──────────────────────────────────────────

// 投稿一覧取得（投稿者名も一緒に）
$posts = $pdo->prepare(
    "SELECT posts.*,
       users.nickname, users.icon_path, users.username,
       COUNT(likes.id) AS like_count,
       SUM(CASE WHEN likes.user_id = ? THEN 1 ELSE 0 END) AS liked_by_me
     FROM posts
     JOIN users ON posts.user_id = users.id
     LEFT JOIN likes ON likes.post_id = posts.id
     WHERE posts.parent_id IS NULL
     GROUP BY posts.id
     ORDER BY posts.created_at DESC
    "
);
$posts->execute([$_SESSION['user_id']]);
$posts = $posts->fetchALL();

$latest_id = !empty($posts) ? $posts[0]['id'] : 0;
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>タイムライン - LearnPHP</title>
                
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
            
            /* ヘッダー右側のボタン配置用エリア */
            .header-actions {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            /* ダークモード切替ボタンのスタイル */
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
            .card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-bottom: 20px;
                border: 1px solid var(--border-color);

            }
            textarea {
                width: 100%;
                background-color: var(--card-bg);
                color: var(--text-color);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 12px;
                box-sizing: border-box;
                font-size: 15px;
                resize: none;
                margin-bottom: 10px;

            }
            textarea:focus {
                outline: none;
                border-color: var(--primary-color);
            }
            
            /* ───【追加・変更】投稿フォーム下部のレイアウト調整 ─── */
            .form-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .form-user-icon {
                width: 35px;
                height: 35px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border-color);
            }
            /* ────────────────────────────────────────────────── */

            button {
                background-color: var(--button-color);
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 15px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.2s;
            }
            button:hover {
                background-color: #3b4671;
            }

            /* 投稿リスト */
            .post-card {
                position: relative;
                background: var(--card-bg);
                border-radius: 12px;
                padding: 15px 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                margin-bottom: 12px;
                border: 1px solid var(--border-color);
            }
            .post-card-link {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1; /* カード内の通常テキスト（詳細リンク）のレイヤー */
            }
            .front-link {
                position: relative;
                z-index: 2; /* 詳細リンク(z-index:1)より手前に出すことで個別にクリック可能に */
            }
            
            /* 上部: ユーザー情報エリア */
            .post-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 12px;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 10px;
            }
            .post-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border-color);
                flex-shrink: 0;
            }
            .post-meta {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .post-name-row {
                display: flex;
                flex-direction: column; /* 横並びから縦並びにし、detail.phpと統一 */
            }
            .post-nickname {
                font-weight: bold;
                color: var(--primary-color);
                font-size: 15px;
            }
            .post-username {
                font-size: 12px;
                color: #a0aec0;
                margin-top: 2px;
                text-decoration: underline;
                text-decoration-color: #a0aec0;
                text-decoration-thickness: 1px; 
            }
            
            /* 中央: 本文エリア */
            .post-content {
                font-size: 15px;
                line-height: 1.6;
                white-space: pre-wrap;
                margin: 0 0 12px 0; /* 下部に余白を確保 */
            }
            .post-actions {
                margin-bottom: 8px;
            }
            .like-btn {
                background: none;
                border: 1px solid var(--border-color);
                border-radius: 20px;
                padding: 4px 12px;
                cursor: pointer;
                color: var(--text-color);
                font-size: 14px;
            }
            .like-btn.liked {
                border-color: #e53e3e;
                color: #e53e3e;
            }
            .like-btn:hover {
                background-color: rgba(229, 62, 62, 0.1);
            }

            /* 下部: 日時エリア */
            .post-time {
                font-size: 12px;
                color: #a0aec0;
                border-top: 1px solid var(--border-color);
                padding-top: 8px;
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

            /* メニュー内の閉じるボタンエリア */
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

            /*h1テキスト。このテンプレートでは、画面最上部の帯の左側に小文字で入れているテキストです。*/
            header h1 {
	            font-weight: normal;
	            margin: 0;padding: 0;
	            font-size: 0.8rem;		/*文字サイズを80%*/
	            letter-spacing: 0.1em;	/*文字間隔を少しだけ広く*/
                
            }

	        /*画面幅700px以下の追加指定*/
	        @media screen and (max-width:700px) {

	        header h1 {
	            font-size: 0.7em;
            }
           }/*追加指定ここまで*/

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-8px); }
                to   { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>

        <header>
            <div id="header-top">
                <h1><?= $recentnewsdate; ?> <?= $recentnews; ?><a href="<?= $recentnewsurl ?>">詳細</a></h1>
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
            <div class="card">

                <form action="timeline.php" method="post">
                    <textarea name="content" rows="3" placeholder="何かつぶやいてみよう！"></textarea>
                    <div class="form-footer">
                        <a href="<?= "profile.php?username=".$current_user['username']; ?>"><img src="<?= $current_icon_src ?>" alt="マイアイコン" class="form-user-icon"></a>
                        <button type="submit">投稿する</button>
                    </div>
                </form>
                </div>

            <div class="post-list" id="postList">
                <?php foreach ($posts as $p): ?>
                    <div class="post-card">

                        <a href="detail.php?contentid=<?= htmlspecialchars($p['content_id']) ?>" class="post-card-link" aria-label="投稿の詳細を見る"></a>

                        <div class="post-header">
                            <?php
                                $iconSrc = ($p['icon_path'] && file_exists(__DIR__ . '/' . $p['icon_path']))
                                    ? htmlspecialchars($p['icon_path'])
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($p['nickname']) . '&background=4F5D95&color=fff';
                            ?>
                            <a href="profile.php?username=<?= htmlspecialchars($p['username']) ?>" class="front-link"><img src="<?= $iconSrc ?>" alt="アイコン" class="post-icon"></a>
                            <div class="post-meta">
                                <div class="post-name-row">
                                    <div class="post-nickname"><?= htmlspecialchars($p['nickname']) ?></div>
                                    <div class="post-username"><a href="profile.php?username=<?= htmlspecialchars($p['username']); ?>" class="front-link" style="color: #a0aec0">@<?= htmlspecialchars($p['username']) ?></a></div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="post-content"><?= htmlspecialchars($p['content']) ?></p>
                        <div class="post-actions front-link">
                            <button 
                                class="like-btn <?= $p['liked_by_me'] ? 'liked' : '' ?>" 
                                data-content-id="<?= htmlspecialchars($p['content_id']) ?>">
                                ♡<span class="like-count"><?= (int)$p['like_count'] ?></span>
                            </button>
                        </div>
                        <div class="post-time"><?= htmlspecialchars($p['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
<script>
const menuBtn = document.getElementById('menuBtn');
const closeBtn = document.getElementById('closeBtn');
const sideMenu = document.getElementById('sideMenu');
const themeToggleBtn = document.getElementById('themeToggleBtn');
const headerLogo = document.getElementById('headerLogo');
const themeLink = document.getElementById('theme-link');
const textarea = document.querySelector('textarea[name="content"]');
const submitBtn = document.querySelector('button[type="submit"]');

// 初期状態は無効
submitBtn.disabled = true;
submitBtn.style.opacity = '0.5';

textarea.addEventListener('input', () => {
    const isEmpty = textarea.value.trim() === '';
    submitBtn.disabled = isEmpty;
    submitBtn.style.opacity = isEmpty ? '0.5' : '1';
});

// ハンバーガーマークを押したときにメニューを開く
menuBtn.addEventListener('click', () => {
    sideMenu.classList.add('active');
});

// 閉じる（×）ボタンを押したときにメニューを閉じる
closeBtn.addEventListener('click', () => {
    sideMenu.classList.remove('active');
});

// ボタンの絵文字を更新する関数
function updateToggleBtnIcon(theme) {
    themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
}

// ページ読み込み時の初期化処理
const currentTheme = localStorage.getItem('theme') || 'light';
updateToggleBtnIcon(currentTheme);

// 初期テーマに合わせてロゴ画像とCSSファイルを確定させる
if (headerLogo) {
    headerLogo.src = currentTheme === 'dark' ? 'img/logo_dark.png' : 'img/logo_light.png';
}
if (themeLink) {
    themeLink.href = currentTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
}

// 切り替えボタンを押したときのイベント
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

// ── リアルタイム更新 ──────────────────────────
let latestId = <?= (int)$latest_id ?>;
const postList = document.getElementById('postList');

function buildPostCard(p) {
    const icon = (p.icon_path)
        ? p.icon_path
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.nickname)}&background=4F5D95&color=fff`;

    return `
    <div class="post-card" style="animation: fadeIn 0.4s ease">
        <a href="detail.php?contentid=${p.content_id}" class="post-card-link"></a>
        <div class="post-header">
            <a href="profile.php?username=${p.username}" class="front-link">
                <img src="${icon}" alt="アイコン" class="post-icon">
            </a>
            <div class="post-meta">
                <div class="post-name-row">
                    <div class="post-nickname">${p.nickname}</div>
                    <div class="post-username">
                        <a href="profile.php?username=${p.username}" class="front-link" style="color:#a0aec0">@${p.username}</a>
                    </div>
                </div>
            </div>
        </div>
        <p class="post-content">${p.content}</p>
            <div class="post-actions front-link">
            <button
                class="like-btn <?= $p['liked_by_me'] ? 'liked' : '' ?>" 
                data-content-id="<?= htmlspecialchars($p['content_id']) ?>">
                ♡<span class="like-count"><?= (int)$p['like_count'] ?></span>
            </button>
        </div>
        <div class="post-time">${p.created_at}</div>
    </div>`;
}

async function checkNewPosts() {
    try {
        const res = await fetch(`api/new_posts.php?since_id=${latestId}`);
        const posts = await res.json();
        if (posts.length > 0) {
            posts.forEach(p => postList.insertAdjacentHTML('afterbegin', buildPostCard(p)));
            latestId = posts[0].id;
        }
    } catch (e) {
        console.error('更新エラー:', e);
    }
}

// タブが見えているときだけポーリング（負荷対策）
let timer = setInterval(checkNewPosts, 4000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(timer);
    } else {
        checkNewPosts(); // タブに戻った瞬間に即チェック
        timer = setInterval(checkNewPosts, 4000);
    }
});

//いいねボタン
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.like-btn');
    if (!btn) return;

    e.stopPropagation(); // カードリンクへの伝播を防ぐ

    const contentId = btn.dataset.contentId;
    const countEl = btn.querySelector('.like-count');

    const res = await fetch('api/like_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `content_id=${contentId}`
    });
    const data = await res.json();

    countEl.textContent = data.count;
    btn.classList.toggle('liked');
});
        </script>

    </body>
</html>