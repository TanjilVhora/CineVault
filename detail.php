<?php
session_start();
require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: list.php"); exit; }

// Fetch movie
$stmt = $pdo->prepare("SELECT * FROM movies_details WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$movie) { header("Location: list.php"); exit; }

// Fetch approved reviews with reviewer names
$stmt = $pdo->prepare(
    "SELECT r.*, u.name AS reviewer_name
     FROM review_details r
     JOIN user_details u ON u.id = r.user_id
     WHERE r.movie_id = ? AND r.status = 'approved'
     ORDER BY r.created_at DESC"
);
$stmt->execute([$id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Average rating from approved reviews
$avg = count($reviews)
    ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1)
    : null;

// Has the current user already reviewed this?
$already_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM review_details WHERE user_id = ? AND movie_id = ?");
    $chk->execute([$_SESSION['user_id'], $id]);
    $already_reviewed = (bool)$chk->fetchColumn();
}

function stars(float $r): string {
    $full = floor($r); $half = ($r - $full) >= .5 ? 1 : 0; $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60).'m ago';
    if ($diff < 86400)  return floor($diff/3600).'h ago';
    if ($diff < 604800) return floor($diff/86400).'d ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($movie['title']) ?> — CineVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --accent2:#e8c98e; --text:#e8e4d8; --muted:#6b6860; --gold:#f0c040; --red:#c0392b; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  body::before { content:''; position:fixed; inset:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events:none; z-index:0; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .nav-links .btn { border:1px solid var(--accent); color:var(--accent); padding:6px 16px; border-radius:2px; }
  .nav-links .btn:hover { background:var(--accent); color:var(--bg); }
  .hero { display:grid; grid-template-columns:280px 1fr; gap:0; min-height:420px; border-bottom:1px solid var(--border); }
  .poster-col { position:relative; background:#181820; overflow:hidden; }
  .poster-col img { width:100%; height:100%; object-fit:cover; display:block; opacity:.92; }
  .poster-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-family:'Cormorant Garamond',serif; font-size:5rem; color:var(--border); }
  .movie-info { padding:3rem 3.5rem; display:flex; flex-direction:column; background:linear-gradient(135deg,#0f0f16 0%,var(--bg) 100%); }
  .back-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); text-decoration:none; font-size:11px; letter-spacing:.08em; text-transform:uppercase; margin-bottom:1.5rem; transition:color .2s; }
  .back-link:hover { color:var(--accent); }
  .movie-title { font-family:'Cormorant Garamond',serif; font-size:clamp(2rem,4vw,3.5rem); font-weight:300; line-height:1.05; letter-spacing:.04em; color:var(--text); margin-bottom:.4rem; }
  .movie-title em { color:var(--accent); font-style:italic; }
  .movie-meta { color:var(--muted); letter-spacing:.06em; font-size:12px; margin-bottom:1.5rem; }
  .rating-block { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
  .big-rating { font-family:'Cormorant Garamond',serif; font-size:3rem; font-weight:300; color:var(--accent2); line-height:1; }
  .rating-detail { display:flex; flex-direction:column; gap:.2rem; }
  .stars-row { color:var(--gold); font-size:16px; letter-spacing:1px; }
  .review-count { color:var(--muted); font-size:11px; letter-spacing:.04em; }
  .no-rating-msg { color:var(--muted); font-style:italic; letter-spacing:.04em; }
  .synopsis { font-family:'Cormorant Garamond',serif; font-size:1.05rem; line-height:1.7; color:rgba(232,228,216,.8); max-width:600px; margin-bottom:2rem; }
  .action-btns { display:flex; gap:.8rem; flex-wrap:wrap; margin-top:auto; }
  .btn-primary, .btn-secondary { padding:10px 24px; border-radius:2px; font-family:'DM Mono',monospace; font-size:12px; letter-spacing:.1em; text-transform:uppercase; text-decoration:none; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:.5rem; transition:all .2s; }
  .btn-primary { background:var(--accent); color:var(--bg); }
  .btn-primary:hover { background:var(--accent2); }
  .btn-secondary { background:transparent; border:1px solid var(--border); color:var(--muted); }
  .btn-secondary:hover { border-color:var(--accent); color:var(--accent); }
  .btn-disabled { opacity:.35; cursor:not-allowed; }
  .reviews-section { padding:3rem; max-width:1000px; }
  .section-heading { font-family:'Cormorant Garamond',serif; font-size:1.8rem; font-weight:300; letter-spacing:.08em; text-transform:uppercase; color:var(--text); border-bottom:1px solid var(--border); padding-bottom:1rem; margin-bottom:2rem; display:flex; align-items:baseline; gap:1rem; }
  .section-heading span { color:var(--muted); font-size:1rem; font-family:'DM Mono',monospace; }
  .review-card { background:var(--surface); border:1px solid var(--border); border-radius:2px; padding:1.5rem; margin-bottom:1rem; transition:border-color .2s; animation:fadeUp .4s ease both; }
  .review-card:hover { border-color:rgba(200,169,110,.2); }
  @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
  .review-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.8rem; flex-wrap:wrap; gap:.5rem; }
  .reviewer { display:flex; align-items:center; gap:.8rem; }
  .avatar { width:32px; height:32px; border-radius:50%; background:var(--border); display:flex; align-items:center; justify-content:center; font-family:'Cormorant Garamond',serif; font-size:1rem; color:var(--accent); flex-shrink:0; }
  .reviewer-name { color:var(--text); font-size:12px; letter-spacing:.04em; }
  .review-date { color:var(--muted); font-size:10px; letter-spacing:.04em; }
  .review-stars { color:var(--gold); font-size:13px; letter-spacing:1px; }
  .review-text { font-family:'Cormorant Garamond',serif; font-size:1rem; line-height:1.75; color:rgba(232,228,216,.8); }
  .empty-reviews { text-align:center; padding:3rem; color:var(--muted); letter-spacing:.06em; }
  .empty-reviews span { display:block; font-family:'Cormorant Garamond',serif; font-size:3rem; color:var(--border); margin-bottom:.5rem; }
  footer { border-top:1px solid var(--border); padding:1.5rem 3rem; color:var(--muted); font-size:11px; letter-spacing:.06em; display:flex; justify-content:space-between; }
  @media(max-width:700px){ .hero{grid-template-columns:1fr} .poster-col{height:280px} nav,.reviews-section,footer{padding-left:1.2rem;padding-right:1.2rem} .movie-info{padding:1.5rem 1.2rem} }
</style>
</head>
<body>
<nav>
  <a class="nav-logo" href="index.php">CineVault</a>
  <div class="nav-links">
    <a href="list.php">Films</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php"><?= htmlspecialchars($_SESSION['name'] ?? 'Account') ?></a>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="admin.php">Admin</a>
      <?php endif; ?>
      <a href="logout.php">Log out</a>
    <?php else: ?>
      <a href="login.php">Log in</a>
      <a href="register.php" class="btn">Register</a>
    <?php endif; ?>
  </div>
</nav>

<section class="hero">
  <div class="poster-col">
    <?php if (!empty($movie['poster_path'])): ?>
      <img src="uploads/<?= htmlspecialchars($movie['poster_path']) ?>" alt="Poster">
    <?php else: ?>
      <div class="poster-placeholder">✦</div>
    <?php endif; ?>
  </div>
  <div class="movie-info">
    <a href="list.php" class="back-link">← All Films</a>
    <h1 class="movie-title">
      <?= htmlspecialchars($movie['title']) ?>
      <?php if (!empty($movie['release_year'])): ?>
        <em style="font-size:.55em;margin-left:.3em"><?= (int)$movie['release_year'] ?></em>
      <?php endif; ?>
    </h1>
   <?php if (!empty($movie['director_name'])): ?>
  <p class="movie-meta">Directed by <?= htmlspecialchars($movie['director_name']) ?></p>
<?php endif; ?>
    <div class="rating-block">
      <?php if ($avg): ?>
        <div class="big-rating"><?= $avg ?></div>
        <div class="rating-detail">
          <span class="stars-row"><?= stars($avg) ?></span>
          <span class="review-count"><?= count($reviews) ?> approved review<?= count($reviews) !== 1 ? 's' : '' ?></span>
        </div>
      <?php else: ?>
        <p class="no-rating-msg">No reviews yet — be the first.</p>
      <?php endif; ?>
    </div>
    <?php if (!empty($movie['synopsis'])): ?>
      <p class="synopsis"><?= nl2br(htmlspecialchars($movie['synopsis'])) ?></p>
    <?php endif; ?>
    <div class="action-btns">
      <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="login.php" class="btn-primary">Log in to review ✦</a>
      <?php elseif ($already_reviewed): ?>
        <span class="btn-primary btn-disabled">Already reviewed ✓</span>
      <?php else: ?>
        <a href="review.php?id=<?= $id ?>" class="btn-primary">Write a Review ✦</a>
      <?php endif; ?>
      <a href="list.php" class="btn-secondary">Browse All Films</a>
    </div>
  </div>
</section>

<section class="reviews-section">
  <h2 class="section-heading">Reviews <span><?= count($reviews) ?> total</span></h2>
  <?php if (empty($reviews)): ?>
    <div class="empty-reviews"><span>✦</span>No approved reviews yet. Share your thoughts!</div>
  <?php else: ?>
    <?php foreach ($reviews as $i => $rev): ?>
    <article class="review-card" style="animation-delay:<?= $i * 60 ?>ms">
      <div class="review-header">
        <div class="reviewer">
          <div class="avatar"><?= mb_strtoupper(mb_substr($rev['reviewer_name'], 0, 1)) ?></div>
          <div>
            <div class="reviewer-name"><?= htmlspecialchars($rev['reviewer_name']) ?></div>
            <div class="review-date"><?= timeAgo($rev['created_at']) ?></div>
          </div>
        </div>
        <span class="review-stars"><?= stars((float)$rev['rating']) ?> <?= number_format((float)$rev['rating'], 1) ?></span>
      </div>
      <?php if (!empty($rev['review_text'])): ?>
        <p class="review-text"><?= nl2br(htmlspecialchars($rev['review_text'])) ?></p>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<footer>
  <span><a href="list.php" style="color:inherit;text-decoration:none">← Back to films</a></span>
  <span>CineVault · <?= date('Y') ?></span>
</footer>
</body>
</html>
