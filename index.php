<?php
session_start();
include 'db.php';

// Fetch top 6 movies by rating for the homepage hero grid
$movies = $pdo->query(
    "SELECT m.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
     FROM movies_details m
     LEFT JOIN review_details r ON r.movie_id = m.id AND r.status = 'approved'
     GROUP BY m.id
     ORDER BY avg_rating DESC, m.created_at DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

function stars(float $r): string {
    $full = floor($r); $half = ($r - $full) >= .5 ? 1 : 0; $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CineVault — Your Film Archive</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:      #0a0a0f;
    --surface: #111118;
    --border:  #1e1e2e;
    --accent:  #c8a96e;
    --accent2: #e8c98e;
    --text:    #e8e4d8;
    --muted:   #6b6860;
    --gold:    #f0c040;
  }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body {
    background:var(--bg); color:var(--text);
    font-family:'DM Mono', monospace; font-size:13px;
    min-height:100vh; overflow-x:hidden;
  }
  body::before {
    content:''; position:fixed; inset:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events:none; z-index:0;
  }
  nav {
    position:sticky; top:0;
    display:flex; align-items:center; justify-content:space-between;
    padding:0 3rem; height:60px;
    background:rgba(10,10,15,.92); backdrop-filter:blur(12px);
    border-bottom:1px solid var(--border); z-index:100;
  }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .nav-links .btn { border:1px solid var(--accent); color:var(--accent); padding:6px 16px; border-radius:2px; transition:all .2s; }
  .nav-links .btn:hover { background:var(--accent); color:var(--bg); }
  .hero {
    min-height:88vh; display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; padding:4rem 2rem; position:relative; overflow:hidden;
  }
  .hero::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse 80% 60% at 50% 40%, rgba(200,169,110,.07) 0%, transparent 70%);
    pointer-events:none;
  }
  .hero-eyebrow { font-size:11px; letter-spacing:.22em; text-transform:uppercase; color:var(--accent); margin-bottom:1.2rem; animation: fadeUp .6s ease both; }
  .hero-title { font-family:'Cormorant Garamond',serif; font-size:clamp(3rem,8vw,7rem); font-weight:300; line-height:1; letter-spacing:.04em; text-transform:uppercase; animation: fadeUp .6s .1s ease both; }
  .hero-title em { color:var(--accent); font-style:italic; }
  .hero-sub { color:var(--muted); max-width:480px; line-height:1.8; letter-spacing:.06em; margin-top:1.2rem; animation: fadeUp .6s .2s ease both; }
  .hero-actions { display:flex; gap:1rem; flex-wrap:wrap; justify-content:center; margin-top:2.5rem; animation: fadeUp .6s .3s ease both; }
  .btn-primary { padding:12px 32px; background:var(--accent); color:var(--bg); border:none; border-radius:2px; font-family:'DM Mono',monospace; font-size:12px; letter-spacing:.1em; text-transform:uppercase; text-decoration:none; cursor:pointer; transition:background .2s; }
  .btn-primary:hover { background:var(--accent2); }
  .btn-outline { padding:12px 32px; background:transparent; border:1px solid var(--border); color:var(--muted); border-radius:2px; font-family:'DM Mono',monospace; font-size:12px; letter-spacing:.1em; text-transform:uppercase; text-decoration:none; cursor:pointer; transition:all .2s; }
  .btn-outline:hover { border-color:var(--accent); color:var(--accent); }
  .scroll-hint { position:absolute; bottom:2rem; left:50%; transform:translateX(-50%); color:var(--muted); font-size:10px; letter-spacing:.15em; text-transform:uppercase; display:flex; flex-direction:column; align-items:center; gap:.4rem; animation: fadeUp .6s .5s ease both; }
  .scroll-hint::after { content:''; width:1px; height:40px; background:var(--border); animation: grow 1.4s ease infinite; }
  @keyframes grow { 0%{height:0;opacity:0} 50%{height:40px;opacity:1} 100%{height:40px;opacity:0} }
  .section-label { display:flex; align-items:center; gap:1.5rem; padding:2rem 3rem 1.5rem; }
  .section-label h2 { font-family:'Cormorant Garamond',serif; font-size:1.5rem; font-weight:300; letter-spacing:.08em; text-transform:uppercase; white-space:nowrap; }
  .section-label::after { content:''; flex:1; height:1px; background:var(--border); }
  .section-label a { color:var(--muted); font-size:11px; letter-spacing:.08em; text-transform:uppercase; text-decoration:none; white-space:nowrap; transition:color .2s; }
  .section-label a:hover { color:var(--accent); }
  .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1px; background:var(--border); margin:0 3rem 4rem; }
  .card { background:var(--surface); position:relative; overflow:hidden; cursor:pointer; transition:transform .3s cubic-bezier(.16,1,.3,1); display:flex; flex-direction:column; animation: fadeUp .5s ease both; }
  .card:hover { transform:scale(1.03) translateY(-4px); z-index:10; box-shadow:0 20px 60px rgba(0,0,0,.6); }
  .card:hover .card-overlay { opacity:1; }
  .poster-wrap { aspect-ratio:2/3; overflow:hidden; background:#181820; }
  .poster-wrap img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .4s; }
  .card:hover .poster-wrap img { transform:scale(1.06); }
  .poster-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-family:'Cormorant Garamond',serif; font-size:3rem; color:var(--border); }
  .card-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,.9) 0%, transparent 60%); opacity:0; transition:opacity .3s; display:flex; align-items:flex-end; padding:1rem; }
  .overlay-btn { width:100%; padding:8px; background:var(--accent); color:var(--bg); font-family:'DM Mono',monospace; font-size:11px; letter-spacing:.1em; text-transform:uppercase; text-align:center; text-decoration:none; border-radius:2px; }
  .card-body { padding:.9rem 1rem; flex:1; display:flex; flex-direction:column; gap:.3rem; }
  .card-title { font-family:'Cormorant Garamond',serif; font-size:1.05rem; font-weight:400; line-height:1.2; color:var(--text); text-decoration:none; }
  .card-title:hover { color:var(--accent); }
  .card-meta { color:var(--muted); font-size:11px; letter-spacing:.04em; }
  .card-rating { display:flex; align-items:center; gap:.4rem; margin-top:auto; padding-top:.5rem; }
  .stars { color:var(--gold); font-size:11px; letter-spacing:-.5px; }
  .rating-num { color:var(--accent2); font-size:11px; }
  .no-rating { color:var(--muted); font-size:10px; letter-spacing:.04em; }
  .empty-grid { margin:0 3rem 4rem; border:1px dashed var(--border); border-radius:2px; padding:4rem; text-align:center; color:var(--muted); letter-spacing:.06em; }
  .empty-grid span { display:block; font-family:'Cormorant Garamond',serif; font-size:3rem; color:var(--border); margin-bottom:.5rem; }
  .cta-strip { border-top:1px solid var(--border); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; padding:2.5rem 3rem; gap:2rem; flex-wrap:wrap; background:var(--surface); }
  .cta-strip h3 { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.06em; }
  .cta-strip h3 em { color:var(--accent); font-style:italic; }
  .cta-strip p { color:var(--muted); margin-top:.3rem; letter-spacing:.04em; line-height:1.6; max-width:480px; }
  footer { border-top:1px solid var(--border); padding:1.5rem 3rem; color:var(--muted); font-size:11px; letter-spacing:.06em; display:flex; justify-content:space-between; align-items:center; }
  @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
  @media(max-width:600px){ nav, .section-label, .cta-strip, footer { padding-left:1.2rem; padding-right:1.2rem; } .grid { margin:0 0 3rem; } .hero-title { font-size:3.2rem; } .nav-links .hide-mobile { display:none; } }
</style>
</head>
<body>
<nav>
  <a class="nav-logo" href="index.php">CineVault</a>
  <div class="nav-links">
    <a href="list.php" class="hide-mobile">All Films</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <span style="color:var(--muted);font-size:11px;letter-spacing:.06em" class="hide-mobile">Hello, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="admin.php" class="hide-mobile">Admin</a>
      <?php endif; ?>
      <a href="logout.php">Log out</a>
    <?php else: ?>
      <a href="login.php" class="hide-mobile">Log in</a>
      <a href="register.php" class="btn">Register</a>
    <?php endif; ?>
  </div>
</nav>

<section class="hero">
  <p class="hero-eyebrow">✦ The Film Archive</p>
  <h1 class="hero-title">Discover<br><em>Great Cinema</em></h1>
  <p class="hero-sub">Browse films, read honest reviews, and share what you think. A community archive built by film lovers.</p>
  <div class="hero-actions">
    <a href="list.php" class="btn-primary">Browse All Films →</a>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <a href="register.php" class="btn-outline">Join Free</a>
    <?php else: ?>
      <a href="list.php" class="btn-outline">Write a Review</a>
    <?php endif; ?>
  </div>
  <div class="scroll-hint">Scroll</div>
</section>

<div class="section-label">
  <h2>Top Rated</h2>
  <a href="list.php">View all films →</a>
</div>

<?php if (empty($movies)): ?>
  <div class="empty-grid">
    ✦<span class="card-meta"><?= (int)$m['release_year'] ?><?php if (!empty($m['director_name'])): ?> · <?= htmlspecialchars($m['director_name']) ?><?php endif; ?></span>
    No movies yet. Add some from the admin panel to get started.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($movies as $i => $m):
      $rating = $m['avg_rating'] ? round((float)$m['avg_rating'], 1) : null;
      $poster = !empty($m['poster_path']) ? 'uploads/' . htmlspecialchars($m['poster_path']) : null;
    ?>
    <div class="card" style="animation-delay:<?= $i * 70 ?>ms">
      <div class="poster-wrap">
        <?php if ($poster): ?>
          <img src="<?= $poster ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy">
        <?php else: ?>
          <div class="poster-placeholder">✦</div>
        <?php endif; ?>
        <div class="card-overlay">
          <a href="detail.php?id=<?= $m['ID'] ?>" class="overlay-btn">View Film →</a>
        </div>
      </div>
      <div class="card-body">
        <a href="detail.php?id=<?= $m['ID'] ?>" class="card-title"><?= htmlspecialchars($m['title']) ?></a>
        <span class="card-meta"><?= (int)$m['release_year'] ?><?php if (!empty($m['director'])): ?> · <?= htmlspecialchars($m['director']) ?><?php endif; ?></span>
        <div class="card-rating">
          <?php if ($rating): ?>
            <span class="stars"><?= stars($rating) ?></span>
            <span class="rating-num"><?= $rating ?></span>
            <span class="no-rating">(<?= (int)$m['review_count'] ?>)</span>
          <?php else: ?>
            <span class="no-rating">No reviews yet</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!isset($_SESSION['user_id'])): ?>
<section class="cta-strip">
  <div>
    <h3>Join <em>CineVault</em></h3>
    <p>Create a free account to write reviews, rate films, and build your watchlist.</p>
  </div>
  <div style="display:flex;gap:.8rem;flex-wrap:wrap">
    <a href="register.php" class="btn-primary">Create Account ✦</a>
    <a href="login.php" class="btn-outline">Log In</a>
  </div>
</section>
<?php endif; ?>

<footer>
  <span>CineVault Film Archive</span>
  <span><?= count($movies) ?> films · <?= date('Y') ?></span>
</footer>
</body>
</html>
