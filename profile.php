<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM user_details WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all reviews by this user with movie title
$stmt = $pdo->prepare(
    "SELECT r.*, m.title AS movie_title, m.id AS movie_id
     FROM review_details r
     JOIN movies_details m ON m.id = r.movie_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_reviews = count($reviews);
$approved      = count(array_filter($reviews, fn($r) => $r['status'] === 'approved'));
$pending       = count(array_filter($reviews, fn($r) => $r['status'] === 'pending'));
$avg_given     = $total_reviews
    ? round(array_sum(array_column($reviews, 'rating')) / $total_reviews, 1)
    : null;

function stars(float $r): string {
    $full = floor($r); $half = ($r - $full) >= .5 ? 1 : 0; $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}
function timeAgo(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60)     return 'just now';
    if ($d < 3600)   return floor($d/60).'m ago';
    if ($d < 86400)  return floor($d/3600).'h ago';
    if ($d < 604800) return floor($d/86400).'d ago';
    return date('M j, Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($user['name']) ?> — CineVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --accent2:#e8c98e; --text:#e8e4d8; --muted:#6b6860; --gold:#f0c040; --ok:#27ae60; --warn:#e67e22; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  body::before { content:''; position:fixed; inset:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events:none; z-index:0; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover, .nav-links a.active { color:var(--accent); }
  .profile-header { position:relative; overflow:hidden; padding:4rem 3rem 3rem; border-bottom:1px solid var(--border); background:linear-gradient(135deg,#0f0f18 0%,var(--bg) 100%); }
  .profile-header::after { content:attr(data-initial); position:absolute; right:2rem; top:50%; transform:translateY(-50%); font-family:'Cormorant Garamond',serif; font-size:16rem; font-weight:600; line-height:1; color:rgba(200,169,110,.04); pointer-events:none; letter-spacing:-.02em; }
  .profile-inner { display:flex; align-items:center; gap:2rem; flex-wrap:wrap; position:relative; z-index:1; }
  .avatar-lg { width:90px; height:90px; border-radius:50%; background:linear-gradient(135deg,var(--border),#1a1a2a); border:2px solid var(--accent); display:flex; align-items:center; justify-content:center; font-family:'Cormorant Garamond',serif; font-size:3rem; color:var(--accent); flex-shrink:0; }
  .profile-name { font-family:'Cormorant Garamond',serif; font-size:clamp(1.8rem,4vw,3rem); font-weight:300; letter-spacing:.05em; line-height:1; }
  .profile-role { display:inline-block; margin-top:.5rem; padding:3px 12px; border:1px solid var(--border); border-radius:100px; font-size:10px; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); }
  .profile-role.admin { border-color:var(--accent); color:var(--accent); }
  .stats-row { display:grid; grid-template-columns:repeat(4,1fr); border-bottom:1px solid var(--border); }
  .stat { padding:1.8rem; border-right:1px solid var(--border); text-align:center; transition:background .2s; }
  .stat:last-child { border-right:none; }
  .stat:hover { background:var(--surface); }
  .stat-num { font-family:'Cormorant Garamond',serif; font-size:2.5rem; font-weight:300; color:var(--accent2); line-height:1; display:block; }
  .stat-label { color:var(--muted); font-size:10px; letter-spacing:.1em; text-transform:uppercase; margin-top:.3rem; }
  .content { padding:2.5rem 3rem 4rem; max-width:860px; }
  .section-heading { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.08em; text-transform:uppercase; border-bottom:1px solid var(--border); padding-bottom:.8rem; margin-bottom:1.5rem; display:flex; align-items:baseline; gap:1rem; }
  .section-heading span { color:var(--muted); font-size:.9rem; font-family:'DM Mono',monospace; }
  .review-card { background:var(--surface); border:1px solid var(--border); border-radius:2px; padding:1.4rem 1.5rem; margin-bottom:.8rem; display:grid; grid-template-columns:1fr auto; gap:.6rem 1.5rem; align-items:start; transition:border-color .2s; animation:fadeUp .4s ease both; }
  .review-card:hover { border-color:rgba(200,169,110,.2); }
  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
  .review-movie { font-family:'Cormorant Garamond',serif; font-size:1.1rem; font-weight:400; color:var(--text); text-decoration:none; line-height:1.2; }
  .review-movie:hover { color:var(--accent); }
  .review-meta { display:flex; align-items:center; gap:.8rem; margin-top:.3rem; flex-wrap:wrap; }
  .review-stars { color:var(--gold); font-size:12px; letter-spacing:.5px; }
  .review-date { color:var(--muted); font-size:11px; letter-spacing:.04em; }
  .review-text { grid-column:1/-1; font-family:'Cormorant Garamond',serif; font-size:1rem; line-height:1.7; color:rgba(232,228,216,.75); margin-top:.2rem; }
  .badge { padding:3px 10px; border-radius:100px; font-size:10px; letter-spacing:.08em; text-transform:uppercase; white-space:nowrap; align-self:start; }
  .badge-approved { background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.25); color:var(--ok); }
  .badge-pending { background:rgba(230,126,34,.1); border:1px solid rgba(230,126,34,.25); color:var(--warn); }
  .empty { padding:3rem; text-align:center; color:var(--muted); letter-spacing:.06em; border:1px dashed var(--border); border-radius:2px; }
  .empty span { display:block; font-family:'Cormorant Garamond',serif; font-size:3rem; color:var(--border); margin-bottom:.5rem; }
  .empty a { color:var(--accent); text-decoration:none; }
  footer { border-top:1px solid var(--border); padding:1.5rem 3rem; color:var(--muted); font-size:11px; letter-spacing:.06em; display:flex; justify-content:space-between; }
  @media(max-width:650px){ nav,.profile-header,.content,footer{padding-left:1.2rem;padding-right:1.2rem} .stats-row{grid-template-columns:repeat(2,1fr)} .profile-header::after{display:none} }
</style>
</head>
<body>
<nav>
  <a class="nav-logo" href="index.php">CineVault</a>
  <div class="nav-links">
    <a href="list.php">Films</a>
    <a href="profile.php" class="active">Profile</a>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
      <a href="admin.php">Admin</a>
    <?php endif; ?>
    <a href="logout.php">Log out</a>
  </div>
</nav>

<header class="profile-header" data-initial="<?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?>">
  <div class="profile-inner">
    <div class="avatar-lg"><?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
    <div>
      <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
      <span class="profile-role <?= $user['role'] === 'admin' ? 'admin' : '' ?>"><?= ucfirst($user['role']) ?></span>
    </div>
  </div>
</header>

<div class="stats-row">
  <div class="stat"><span class="stat-num"><?= $total_reviews ?></span><span class="stat-label">Total Reviews</span></div>
  <div class="stat"><span class="stat-num"><?= $approved ?></span><span class="stat-label">Approved</span></div>
  <div class="stat"><span class="stat-num"><?= $pending ?></span><span class="stat-label">Pending</span></div>
  <div class="stat"><span class="stat-num"><?= $avg_given ?? '—' ?></span><span class="stat-label">Avg. Rating Given</span></div>
</div>

<main class="content">
  <h2 class="section-heading">My Reviews <span><?= $total_reviews ?> total</span></h2>
  <?php if (empty($reviews)): ?>
    <div class="empty">
      <span>✦</span>
      You haven't written any reviews yet.<br><br>
      <a href="list.php">Browse films and write your first review →</a>
    </div>
  <?php else: ?>
    <?php foreach ($reviews as $i => $r): ?>
    <div class="review-card" style="animation-delay:<?= $i * 60 ?>ms">
      <div>
        <a href="detail.php?id=<?= $r['movie_id'] ?>" class="review-movie"><?= htmlspecialchars($r['movie_title']) ?></a>
        <div class="review-meta">
          <span class="review-stars"><?= stars((float)$r['rating']) ?> <?= $r['rating'] ?>/5</span>
          <span class="review-date"><?= timeAgo($r['created_at']) ?></span>
        </div>
      </div>
      <span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
      <?php if (!empty($r['review_text'])): ?>
        <p class="review-text"><?= nl2br(htmlspecialchars($r['review_text'])) ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<footer>
  <a href="list.php" style="color:inherit;text-decoration:none">← All Films</a>
  <span>CineVault · <?= date('Y') ?></span>
</footer>
</body>
</html>
