<?php
session_start();
include 'db.php';

// Protection — must be logged in and admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: list.php');
    exit;
}

// Stats
$total_movies  = $pdo->query("SELECT COUNT(*) FROM movies_details")->fetchColumn();
$total_users   = $pdo->query("SELECT COUNT(*) FROM user_details")->fetchColumn();
$total_reviews = $pdo->query("SELECT COUNT(*) FROM review_details")->fetchColumn();
$pending       = $pdo->query("SELECT COUNT(*) FROM review_details WHERE status='pending'")->fetchColumn();

// Recent pending reviews
$stmt = $pdo->query(
    "SELECT r.*, u.name AS reviewer_name, m.title AS movie_title
     FROM review_details r
     JOIN user_details u ON u.id = r.user_id
     JOIN movies_details m ON m.id = r.movie_id
     WHERE r.status = 'pending'
     ORDER BY r.created_at DESC
     LIMIT 5"
);
$pending_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — CineVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --accent2:#e8c98e; --text:#e8e4d8; --muted:#6b6860; --ok:#27ae60; --warn:#e67e22; --err:#c0392b; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  body::before { content:''; position:fixed; inset:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events:none; z-index:0; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover, .nav-links a.active { color:var(--accent); }
  .page-header { padding:4rem 3rem 2rem; border-bottom:1px solid var(--border); }
  .page-header h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2rem,4vw,3.5rem); font-weight:300; letter-spacing:.06em; text-transform:uppercase; }
  .page-header h1 em { color:var(--accent); font-style:italic; }
  .page-header p { color:var(--muted); margin-top:.4rem; letter-spacing:.06em; }
  .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1px; background:var(--border); margin:2rem 3rem; }
  .stat-card { background:var(--surface); padding:2rem; text-align:center; transition:background .2s; }
  .stat-card:hover { background:#161620; }
  .stat-num { font-family:'Cormorant Garamond',serif; font-size:3rem; font-weight:300; color:var(--accent2); display:block; line-height:1; }
  .stat-label { color:var(--muted); font-size:10px; letter-spacing:.12em; text-transform:uppercase; margin-top:.5rem; display:block; }
  .stat-card.warn .stat-num { color:#e67e22; }
  .actions-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin:0 3rem 2rem; }
  .action-card { background:var(--surface); border:1px solid var(--border); border-radius:2px; padding:1.5rem; text-decoration:none; transition:border-color .2s,background .2s; display:flex; flex-direction:column; gap:.5rem; }
  .action-card:hover { border-color:var(--accent); background:#161620; }
  .action-title { font-family:'Cormorant Garamond',serif; font-size:1.3rem; font-weight:300; color:var(--text); }
  .action-desc { color:var(--muted); font-size:11px; letter-spacing:.04em; line-height:1.6; }
  .action-arrow { color:var(--accent); font-size:18px; margin-top:auto; }
  .section-heading { font-family:'Cormorant Garamond',serif; font-size:1.4rem; font-weight:300; letter-spacing:.08em; text-transform:uppercase; border-bottom:1px solid var(--border); padding-bottom:.8rem; margin:2rem 3rem 1rem; display:flex; align-items:baseline; gap:1rem; }
  .section-heading span { color:var(--muted); font-size:.9rem; font-family:'DM Mono',monospace; }
  .review-row { display:grid; grid-template-columns:1fr auto auto; gap:1rem; align-items:center; padding:1rem 1.5rem; border-bottom:1px solid var(--border); background:var(--surface); transition:background .2s; }
  .review-row:hover { background:#161620; }
  .review-info .movie { font-family:'Cormorant Garamond',serif; font-size:1rem; color:var(--text); }
  .review-info .meta { color:var(--muted); font-size:11px; margin-top:.2rem; letter-spacing:.04em; }
  .btn-approve { padding:6px 14px; background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.3); color:var(--ok); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; border-radius:2px; transition:all .2s; text-decoration:none; }
  .btn-approve:hover { background:rgba(39,174,96,.2); }
  .btn-delete { padding:6px 14px; background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3); color:var(--err); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; border-radius:2px; transition:all .2s; text-decoration:none; }
  .btn-delete:hover { background:rgba(192,57,43,.2); }
  .empty-msg { padding:2rem 3rem; color:var(--muted); letter-spacing:.06em; }
  footer { border-top:1px solid var(--border); padding:1.5rem 3rem; color:var(--muted); font-size:11px; letter-spacing:.06em; display:flex; justify-content:space-between; margin-top:3rem; }
  @media(max-width:700px){ .stats-grid,.actions-grid{grid-template-columns:repeat(2,1fr)} nav,.page-header,.section-heading,.empty-msg,footer{padding-left:1.2rem;padding-right:1.2rem} .stats-grid,.actions-grid{margin-left:1.2rem;margin-right:1.2rem} }
</style>
</head>
<body>
<nav>
  <a class="nav-logo" href="index.php">CineVault</a>
  <div class="nav-links">
    <a href="list.php">Films</a>
    <a href="admin.php" class="active">Admin</a>
    <a href="logout.php">Log out</a>
  </div>
</nav>

<header class="page-header">
  <h1>Admin <em>Panel</em></h1>
  <p>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?></p>
</header>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card"><span class="stat-num"><?= $total_movies ?></span><span class="stat-label">Total Movies</span></div>
  <div class="stat-card"><span class="stat-num"><?= $total_users ?></span><span class="stat-label">Total Users</span></div>
  <div class="stat-card"><span class="stat-num"><?= $total_reviews ?></span><span class="stat-label">Total Reviews</span></div>
  <div class="stat-card warn"><span class="stat-num"><?= $pending ?></span><span class="stat-label">Pending Approval</span></div>
</div>

<!-- Action links -->
<div class="actions-grid">
  <a href="add-movie.php" class="action-card">
    <div class="action-title">Add Movie</div>
    <div class="action-desc">Add a new movie to the archive with title, director, year and poster.</div>
    <div class="action-arrow">→</div>
  </a>
  <a href="manage-reviews.php" class="action-card">
    <div class="action-title">Manage Reviews</div>
    <div class="action-desc">Approve or delete pending and published reviews.</div>
    <div class="action-arrow">→</div>
  </a>
  <a href="manage-users.php" class="action-card">
    <div class="action-title">Manage Users</div>
    <div class="action-desc">View all registered users and delete accounts if needed.</div>
    <div class="action-arrow">→</div>
  </a>
</div>

<!-- Pending reviews preview -->
<h2 class="section-heading">Pending Reviews <span><?= $pending ?> awaiting</span></h2>

<?php if (empty($pending_reviews)): ?>
  <p class="empty-msg">No pending reviews right now.</p>
<?php else: ?>
  <?php foreach ($pending_reviews as $r): ?>
  <div class="review-row">
    <div class="review-info">
      <div class="movie"><?= htmlspecialchars($r['movie_title']) ?></div>
      <div class="meta">by <?= htmlspecialchars($r['reviewer_name']) ?> · <?= $r['rating'] ?>/5 stars</div>
    </div>
    <a href="manage-reviews.php?approve=<?= $r['id'] ?>" class="btn-approve">Approve</a>
    <a href="manage-reviews.php?delete=<?= $r['id'] ?>" class="btn-delete" onclick="return confirm('Delete this review?')">Delete</a>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<footer>
  <span>CineVault Admin</span>
  <span><?= date('Y') ?></span>
</footer>
</body>
</html>
