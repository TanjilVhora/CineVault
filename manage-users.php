<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: list.php'); exit; }

// Handle delete
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    // Don't allow admin to delete themselves
    if ($uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM user_details WHERE id=?")->execute([$uid]);
        header('Location: manage-users.php?msg=deleted');
        exit;
    } else {
        header('Location: manage-users.php?msg=self');
        exit;
    }
}

// Fetch all users
$users = $pdo->query(
    "SELECT u.*, COUNT(r.id) AS review_count
     FROM user_details u
     LEFT JOIN review_details r ON r.user_id = u.id
     GROUP BY u.id
     ORDER BY u.ID DESC" 
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users — CineVault Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --text:#e8e4d8; --muted:#6b6860; --ok:#27ae60; --err:#c0392b; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .content { padding:3rem; max-width:800px; }
  .back-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); text-decoration:none; font-size:11px; letter-spacing:.08em; text-transform:uppercase; margin-bottom:2rem; transition:color .2s; }
  .back-link:hover { color:var(--accent); }
  h1 { font-family:'Cormorant Garamond',serif; font-size:2.5rem; font-weight:300; letter-spacing:.06em; margin-bottom:2rem; }
  h1 em { color:var(--accent); font-style:italic; }
  .msg { padding:.8rem 1rem; border-radius:2px; font-size:12px; margin-bottom:1.5rem; letter-spacing:.04em; }
  .msg-del { background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3); color:var(--err); }
  .msg-warn { background:rgba(230,126,34,.1); border:1px solid rgba(230,126,34,.25); color:#e67e22; }
  .user-row { background:var(--surface); border:1px solid var(--border); border-radius:2px; padding:1rem 1.5rem; margin-bottom:.8rem; display:grid; grid-template-columns:40px 1fr auto auto; gap:1rem; align-items:center; transition:border-color .2s; }
  .user-row:hover { border-color:rgba(200,169,110,.15); }
  .avatar { width:36px; height:36px; border-radius:50%; background:var(--border); display:flex; align-items:center; justify-content:center; font-family:'Cormorant Garamond',serif; font-size:1.1rem; color:var(--accent); }
  .user-name { font-size:13px; color:var(--text); font-weight:400; }
  .user-meta { color:var(--muted); font-size:11px; margin-top:.2rem; letter-spacing:.04em; }
  .badge { padding:3px 10px; border-radius:100px; font-size:10px; letter-spacing:.08em; text-transform:uppercase; }
  .badge-admin { background:rgba(200,169,110,.1); border:1px solid rgba(200,169,110,.3); color:var(--accent); }
  .badge-user { background:rgba(107,104,96,.1); border:1px solid rgba(107,104,96,.3); color:var(--muted); }
  .btn-delete { padding:6px 14px; background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3); color:var(--err); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; border-radius:2px; transition:all .2s; text-decoration:none; }
  .btn-delete:hover { background:rgba(192,57,43,.2); }
  .btn-self { opacity:.3; cursor:not-allowed; pointer-events:none; padding:6px 14px; border:1px solid var(--border); color:var(--muted); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.06em; text-transform:uppercase; border-radius:2px; }
</style>
</head>
<body>
<nav>
  <a class="nav-logo" href="index.php">CineVault</a>
  <div class="nav-links">
    <a href="admin.php">Admin</a>
    <a href="logout.php">Log out</a>
  </div>
</nav>

<div class="content">
  <a href="admin.php" class="back-link">← Admin Panel</a>
  <h1>Manage <em>Users</em></h1>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'deleted'): ?>
      <div class="msg msg-del">User deleted successfully.</div>
    <?php elseif ($_GET['msg'] === 'self'): ?>
      <div class="msg msg-warn">You cannot delete your own account.</div>
    <?php endif; ?>
  <?php endif; ?>

  <?php foreach ($users as $u): ?>
  <div class="user-row">
    <div class="avatar"><?= mb_strtoupper(mb_substr($u['name'], 0, 1)) ?></div>
    <div>
      <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
      <div class="user-meta"><?= $u['review_count'] ?> reviews</div>
    </div>
    <span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
    <?php if ($u['ID'] === (int)$_SESSION['user_id']): ?>
      <span class="btn-self">You</span>
    <?php else: ?>
      <a href="manage-users.php?delete=<?= $u['ID'] ?>" class="btn-delete" onclick="return confirm('Delete user <?= htmlspecialchars($u['name']) ?> and all their reviews?')">Delete</a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
</body>
</html>
