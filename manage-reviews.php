<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: list.php'); exit; }

// Handle approve
if (isset($_GET['approve'])) {
    $rid = (int)$_GET['approve'];
    $pdo->prepare("UPDATE review_details SET status='approved' WHERE id=?")->execute([$rid]);

    // Get movie_id to recalculate avg
    $r = $pdo->prepare("SELECT movie_id FROM review_details WHERE id=?");
    $r->execute([$rid]);
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pdo->prepare(
            "UPDATE movies_details SET avg_rating=(SELECT AVG(rating) FROM review_details WHERE movie_id=? AND status='approved') WHERE id=?"
        )->execute([$row['movie_id'], $row['movie_id']]);
    }
    header('Location: manage-reviews.php?msg=approved');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $rid = (int)$_GET['delete'];
    $r = $pdo->prepare("SELECT movie_id FROM review_details WHERE id=?");
    $r->execute([$rid]);
    $row = $r->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare("DELETE FROM review_details WHERE id=?")->execute([$rid]);

    if ($row) {
        $pdo->prepare(
            "UPDATE movies_details SET avg_rating=(SELECT AVG(rating) FROM review_details WHERE movie_id=? AND status='approved') WHERE id=?"
        )->execute([$row['movie_id'], $row['movie_id']]);
    }
    header('Location: manage-reviews.php?msg=deleted');
    exit;
}

// Fetch all reviews
$reviews = $pdo->query(
    "SELECT r.*, u.name AS reviewer_name, m.title AS movie_title
     FROM review_details r
     JOIN user_details u ON u.id = r.user_id
     JOIN movies_details m ON m.id = r.movie_id
     ORDER BY r.status ASC, r.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Reviews — CineVault Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --text:#e8e4d8; --muted:#6b6860; --ok:#27ae60; --warn:#e67e22; --err:#c0392b; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .content { padding:3rem; max-width:900px; }
  .back-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); text-decoration:none; font-size:11px; letter-spacing:.08em; text-transform:uppercase; margin-bottom:2rem; transition:color .2s; }
  .back-link:hover { color:var(--accent); }
  h1 { font-family:'Cormorant Garamond',serif; font-size:2.5rem; font-weight:300; letter-spacing:.06em; margin-bottom:2rem; }
  h1 em { color:var(--accent); font-style:italic; }
  .msg { padding:.8rem 1rem; border-radius:2px; font-size:12px; margin-bottom:1.5rem; letter-spacing:.04em; }
  .msg-ok { background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.25); color:var(--ok); }
  .msg-del { background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3); color:var(--err); }
  .review-row { background:var(--surface); border:1px solid var(--border); border-radius:2px; padding:1.2rem 1.5rem; margin-bottom:.8rem; display:grid; grid-template-columns:1fr auto auto; gap:1rem; align-items:center; transition:border-color .2s; }
  .review-row:hover { border-color:rgba(200,169,110,.15); }
  .movie { font-family:'Cormorant Garamond',serif; font-size:1.05rem; color:var(--text); }
  .meta { color:var(--muted); font-size:11px; margin-top:.3rem; letter-spacing:.04em; }
  .review-preview { color:rgba(232,228,216,.6); font-size:12px; margin-top:.4rem; font-family:'Cormorant Garamond',serif; }
  .badge { padding:3px 10px; border-radius:100px; font-size:10px; letter-spacing:.08em; text-transform:uppercase; }
  .badge-approved { background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.25); color:var(--ok); }
  .badge-pending { background:rgba(230,126,34,.1); border:1px solid rgba(230,126,34,.25); color:var(--warn); }
  .btn-approve { padding:6px 14px; background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.3); color:var(--ok); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; border-radius:2px; transition:all .2s; text-decoration:none; white-space:nowrap; }
  .btn-approve:hover { background:rgba(39,174,96,.2); }
  .btn-delete { padding:6px 14px; background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3); color:var(--err); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; border-radius:2px; transition:all .2s; text-decoration:none; white-space:nowrap; }
  .btn-delete:hover { background:rgba(192,57,43,.2); }
  .actions { display:flex; gap:.5rem; align-items:center; }
  .empty { padding:3rem; text-align:center; color:var(--muted); }
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
  <h1>Manage <em>Reviews</em></h1>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'approved'): ?>
      <div class="msg msg-ok">Review approved successfully.</div>
    <?php elseif ($_GET['msg'] === 'deleted'): ?>
      <div class="msg msg-del">Review deleted successfully.</div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (empty($reviews)): ?>
    <div class="empty">No reviews yet.</div>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
    <div class="review-row">
      <div>
        <div class="movie"><?= htmlspecialchars($r['movie_title']) ?></div>
        <div class="meta">by <?= htmlspecialchars($r['reviewer_name']) ?> · <?= $r['rating'] ?>/5 stars</div>
        <?php if (!empty($r['review_text'])): ?>
          <div class="review-preview"><?= htmlspecialchars(mb_substr($r['review_text'], 0, 100)) ?>...</div>
        <?php endif; ?>
      </div>
      <span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
      <div class="actions">
        <?php if ($r['status'] === 'pending'): ?>
          <a href="manage-reviews.php?approve=<?= $r['id'] ?>" class="btn-approve">Approve</a>
        <?php endif; ?>
        <a href="manage-reviews.php?delete=<?= $r['id'] ?>" class="btn-delete" onclick="return confirm('Delete this review permanently?')">Delete</a>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>
