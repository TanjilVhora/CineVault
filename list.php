<?php
session_start();

require 'db.php';

// Genres removed from project — using empty array
$genres = [];

// Build movie query with optional search
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT m.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
        FROM movies_details m
        LEFT JOIN review_details r ON r.movie_id = m.id AND r.status = 'approved'";

$params = [];

if ($search !== '') {
    $sql .= " WHERE (m.title LIKE :q OR m.director_name LIKE :q)";
    $params[':q'] = "%$search%";
}

$sql .= " GROUP BY m.id ORDER BY avg_rating DESC, m.release_year DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

function stars(float $rating): string {
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CineVault — All Films</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --accent2:#e8c98e; --text:#e8e4d8; --muted:#6b6860; --gold:#f0c040; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; overflow-x:hidden; }
  body::before { content:''; position:fixed; inset:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events:none; z-index:0; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .nav-links .btn-login { border:1px solid var(--accent); color:var(--accent); padding:6px 16px; border-radius:2px; }
  .nav-links .btn-login:hover { background:var(--accent); color:var(--bg); }
  .page-header { padding:5rem 3rem 3rem; border-bottom:1px solid var(--border); position:relative; overflow:hidden; }
  .page-header::after { content:'FILMS'; position:absolute; right:2rem; bottom:-1.5rem; font-family:'Cormorant Garamond',serif; font-size:9rem; font-weight:600; color:rgba(200,169,110,.04); line-height:1; letter-spacing:.05em; pointer-events:none; }
  .page-header h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2rem,5vw,4rem); font-weight:300; letter-spacing:.06em; text-transform:uppercase; color:var(--text); }
  .page-header h1 em { color:var(--accent); font-style:italic; }
  .page-header p { color:var(--muted); margin-top:.5rem; letter-spacing:.08em; }
  .toolbar { display:flex; flex-wrap:wrap; gap:1rem; align-items:center; padding:1.5rem 3rem; border-bottom:1px solid var(--border); background:var(--surface); }
  .search-wrap { position:relative; flex:1; min-width:200px; max-width:360px; }
  .search-wrap input { width:100%; background:var(--bg); border:1px solid var(--border); color:var(--text); font-family:'DM Mono',monospace; font-size:12px; padding:8px 12px 8px 36px; outline:none; border-radius:2px; transition:border-color .2s; }
  .search-wrap input:focus { border-color:var(--accent); }
  .search-wrap .icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; }
  .count-badge { margin-left:auto; color:var(--muted); letter-spacing:.06em; white-space:nowrap; font-size:11px; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1px; padding:2rem 3rem 4rem; background:var(--border); }
  .card { background:var(--surface); position:relative; overflow:hidden; cursor:pointer; transition:transform .3s cubic-bezier(.16,1,.3,1); display:flex; flex-direction:column; }
  .card:hover { transform:scale(1.03) translateY(-4px); z-index:10; box-shadow:0 20px 60px rgba(0,0,0,.6); }
  .card:hover .card-overlay { opacity:1; }
  .poster-wrap { aspect-ratio:2/3; overflow:hidden; position:relative; background:#181820; }
  .poster-wrap img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .4s ease; }
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
  .empty { grid-column:1/-1; padding:5rem; text-align:center; color:var(--muted); letter-spacing:.06em; }
  .empty span { display:block; font-family:'Cormorant Garamond',serif; font-size:3rem; color:var(--border); margin-bottom:1rem; }
  footer { border-top:1px solid var(--border); padding:1.5rem 3rem; color:var(--muted); font-size:11px; letter-spacing:.06em; display:flex; justify-content:space-between; align-items:center; }
  @media(max-width:600px){ nav, .page-header, .toolbar, .grid, footer { padding-left:1.2rem; padding-right:1.2rem; } .grid { grid-template-columns:repeat(2,1fr); } }
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
      <a href="register.php" class="btn-login">Register</a>
    <?php endif; ?>
  </div>
</nav>

<header class="page-header">
  <h1>All <em>Films</em></h1>
  <p><?= count($movies) ?> titles in the archive</p>
</header>

<div class="toolbar">
  <form method="GET" style="display:contents">
    <div class="search-wrap">
      <span class="icon">⌕</span>
      <input type="text" name="q" placeholder="Search title or director…" value="<?= htmlspecialchars($search) ?>">
    </div>
  </form>
  <span class="count-badge"><?= count($movies) ?> results</span>
</div>

<main>
  <div class="grid">
    <?php if (empty($movies)): ?>
      <div class="empty">
        <span>✦</span>
        No films found. Try a different search term.
      </div>
    <?php else: ?>
      <?php foreach ($movies as $m):
        $rating = $m['avg_rating'] ? round((float)$m['avg_rating'], 1) : null;
        $poster = !empty($m['poster_path']) ? 'uploads/' . htmlspecialchars($m['poster_path']) : null;
      ?>
      <div class="card">
        <div class="poster-wrap">
          <?php if ($poster): ?>
            <img src="<?= $poster ?>" alt="<?= htmlspecialchars($m['title']) ?> poster" loading="lazy">
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
    <?php endif; ?>
  </div>
</main>

<footer>
  <span>CineVault Archive</span>
  <span><?= count($movies) ?> films · <?= date('Y') ?></span>
</footer>
</body>
</html>
