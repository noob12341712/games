<?php
// admin.php — password-protected suggestion manager
// CHANGE THIS PASSWORD before uploading!
define('ADMIN_PASSWORD', '11910');

session_start();

$file = __DIR__ . '/suggestions.json';

// ── LOGIN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
    } else {
        $loginError = 'Wrong password.';
    }
}
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── DELETE ──
if (isset($_SESSION['admin']) && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $suggestions = [];
    if (file_exists($file)) {
        $suggestions = json_decode(file_get_contents($file), true) ?: [];
    }
    $suggestions = array_values(array_filter($suggestions, fn($s) => $s['id'] !== $id));
    file_put_contents($file, json_encode($suggestions, JSON_PRETTY_PRINT));
    header('Location: admin.php');
    exit;
}

// ── DELETE ALL ──
if (isset($_SESSION['admin']) && isset($_POST['delete_all'])) {
    file_put_contents($file, json_encode([]));
    header('Location: admin.php');
    exit;
}

// ── LOAD SUGGESTIONS ──
$suggestions = [];
if (file_exists($file)) {
    $suggestions = json_decode(file_get_contents($file), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Game Suggestions — Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0a0a0f;
      --surface: #12121a;
      --border: #25252f;
      --border-dark: #3a3a45;
      --text-primary: #f0f0f5;
      --text-secondary: #a0a0b0;
      --text-hint: #66667a;
      --accent: #60a5fa;
      --danger: #f87171;
    }
    body {
      background: var(--bg);
      color: var(--text-primary);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      font-size: 15px;
      -webkit-font-smoothing: antialiased;
    }

    /* ── LOGIN SCREEN ── */
    .login-wrap {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 24px;
    }
    .login-box {
      background: var(--surface);
      border: 1px solid var(--border-dark);
      border-radius: 20px;
      padding: 40px 36px;
      width: 100%; max-width: 360px;
      text-align: center;
    }
    .login-icon { font-size: 32px; margin-bottom: 12px; }
    .login-title { font-size: 20px; font-weight: 600; margin-bottom: 6px; }
    .login-sub { font-size: 13px; color: var(--text-hint); margin-bottom: 28px; }
    .login-box input[type=password] {
      width: 100%;
      background: #1a1a24;
      border: 1px solid var(--border-dark);
      border-radius: 10px;
      padding: 11px 14px;
      color: var(--text-primary);
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      outline: none;
      margin-bottom: 12px;
      transition: border-color 0.15s;
    }
    .login-box input[type=password]:focus { border-color: var(--accent); }
    .login-error { font-size: 13px; color: var(--danger); margin-bottom: 12px; }
    .btn-primary {
      width: 100%;
      padding: 11px;
      border-radius: 99px;
      border: none;
      background: var(--accent);
      color: #0a0a0f;
      font-size: 14px;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      transition: opacity 0.15s;
    }
    .btn-primary:hover { opacity: 0.85; }

    /* ── ADMIN LAYOUT ── */
    .admin-header {
      border-bottom: 1px solid var(--border);
      background: rgba(10,10,15,0.95);
      backdrop-filter: blur(12px);
      position: sticky; top: 0; z-index: 10;
    }
    .admin-header-inner {
      max-width: 860px; margin: 0 auto;
      padding: 0 28px; height: 60px;
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
    }
    .admin-title { font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .admin-title span { font-size: 13px; font-family: 'DM Mono', monospace; color: var(--text-hint); }
    .header-actions { display: flex; align-items: center; gap: 10px; }
    .btn-ghost {
      padding: 7px 16px;
      border-radius: 99px;
      border: 1px solid var(--border-dark);
      background: var(--surface);
      color: var(--text-secondary);
      font-size: 13px;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
      transition: all 0.15s;
    }
    .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
    .btn-danger-ghost {
      padding: 7px 16px;
      border-radius: 99px;
      border: 1px solid rgba(248,113,113,0.3);
      background: rgba(248,113,113,0.08);
      color: var(--danger);
      font-size: 13px;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      transition: all 0.15s;
    }
    .btn-danger-ghost:hover { background: rgba(248,113,113,0.18); }

    /* ── CONTENT ── */
    .content { max-width: 860px; margin: 0 auto; padding: 32px 28px 80px; }
    .empty-state {
      text-align: center; padding: 80px 0;
      color: var(--text-hint); font-size: 14px;
    }
    .empty-state .empty-icon { font-size: 40px; margin-bottom: 12px; }

    /* ── SUGGESTION CARD ── */
    .suggestion-list { display: flex; flex-direction: column; gap: 12px; }
    .suggestion-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 18px 20px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
    }
    .suggestion-card:hover { border-color: var(--border-dark); }
    .suggestion-main { flex: 1; min-width: 0; }
    .suggestion-name {
      font-size: 16px; font-weight: 600;
      color: var(--text-primary); margin-bottom: 4px;
    }
    .suggestion-reason {
      font-size: 13.5px; color: var(--text-secondary);
      line-height: 1.55; margin-bottom: 8px;
    }
    .suggestion-meta {
      font-size: 11px; font-family: 'DM Mono', monospace;
      color: var(--text-hint); display: flex; gap: 16px; flex-wrap: wrap;
    }
    .delete-btn {
      flex-shrink: 0;
      padding: 7px 14px;
      border-radius: 99px;
      border: 1px solid rgba(248,113,113,0.25);
      background: rgba(248,113,113,0.06);
      color: var(--danger);
      font-size: 12px;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s;
      white-space: nowrap;
    }
    .delete-btn:hover { background: rgba(248,113,113,0.18); border-color: var(--danger); }

    @media (max-width: 600px) {
      .admin-header-inner, .content { padding-left: 16px; padding-right: 16px; }
      .suggestion-card { flex-direction: column; }
    }
  </style>
</head>
<body>

<?php if (!isset($_SESSION['admin'])): ?>
<!-- LOGIN -->
<div class="login-wrap">
  <div class="login-box">
    <div class="login-icon">🔒</div>
    <div class="login-title">Admin Access</div>
    <div class="login-sub">Game suggestions manager</div>
    <?php if (!empty($loginError)): ?>
      <div class="login-error"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="password" name="password" placeholder="Password" autofocus/>
      <button class="btn-primary" type="submit" name="login">Sign in</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ADMIN PANEL -->
<div class="admin-header">
  <div class="admin-header-inner">
    <div class="admin-title">
      🎮 Suggestions
      <span><?= count($suggestions) ?> pending</span>
    </div>
    <div class="header-actions">
      <a class="btn-ghost" href="index.html">← Back to site</a>
      <?php if (!empty($suggestions)): ?>
      <form method="POST" style="display:inline;" onsubmit="return confirm('Delete ALL suggestions? This cannot be undone.')">
        <button class="btn-danger-ghost" type="submit" name="delete_all">Clear all</button>
      </form>
      <?php endif; ?>
      <form method="POST" style="display:inline;">
        <button class="btn-ghost" type="submit" name="logout">Log out</button>
      </form>
    </div>
  </div>
</div>

<div class="content">
  <?php if (empty($suggestions)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      No suggestions yet — check back later!
    </div>
  <?php else: ?>
    <div class="suggestion-list">
      <?php foreach ($suggestions as $s): ?>
      <div class="suggestion-card">
        <div class="suggestion-main">
          <div class="suggestion-name"><?= htmlspecialchars($s['name']) ?></div>
          <?php if (!empty($s['username'])): ?>
            <div style="font-size:12px;color:#60a5fa;margin-bottom:4px;font-family:'DM Mono',monospace;">by <?= htmlspecialchars($s['username']) ?></div>
          <?php endif; ?>
          <?php if (!empty($s['reason'])): ?>
            <div class="suggestion-reason"><?= htmlspecialchars($s['reason']) ?></div>
          <?php endif; ?>
          <div class="suggestion-meta">
            <span>📅 <?= htmlspecialchars($s['date']) ?></span>
            <span>🌐 <?= htmlspecialchars($s['ip']) ?></span>
            <span>#<?= htmlspecialchars($s['id']) ?></span>
          </div>
        </div>
        <a class="delete-btn" href="admin.php?delete=<?= urlencode($s['id']) ?>"
           onclick="return confirm('Remove this suggestion?')">✕ Remove</a>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php endif; ?>
</body>
</html>
