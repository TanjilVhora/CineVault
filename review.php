<?php
session_start();
require 'db.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Valid movie id
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movie_id <= 0) { header("Location: list.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM movies_details WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$movie) { header("Location: list.php"); exit; }

// Duplicate check
$chk = $pdo->prepare("SELECT COUNT(*) FROM review_details WHERE user_id = ? AND movie_id = ?");
$chk->execute([$_SESSION['user_id'], $movie_id]);
if ((int)$chk->fetchColumn() > 0) {
    header("Location: detail.php?id=$movie_id");
    exit;
}

$errors  = [];
$success = false;
$fields  = ['rating' => '', 'review_text' => ''];

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating      = isset($_POST['rating'])      ? (int)trim($_POST['rating'])  : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text'])   : '';

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Please choose a star rating from 1 to 5.';
    }
    if (mb_strlen($review_text) < 10) {
        $errors['review_text'] = 'Review must be at least 10 characters.';
    }
    if (mb_strlen($review_text) > 2000) {
        $errors['review_text'] = 'Review must be under 2,000 characters.';
    }

    if (empty($errors)) {
        // Double-check duplicate
        $chk->execute([$_SESSION['user_id'], $movie_id]);
        if ((int)$chk->fetchColumn() > 0) {
            header("Location: detail.php?id=$movie_id");
            exit;
        }

        // Insert review (status=pending until admin approves)
        $ins = $pdo->prepare(
            "INSERT INTO review_details (user_id, movie_id, rating, review_text, status, created_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())"
        );
        $ins->execute([$_SESSION['user_id'], $movie_id, $rating, $review_text]);

        // Recalculate avg_rating from approved reviews only
        $pdo->prepare(
            "UPDATE movies_details
             SET avg_rating = (
                 SELECT AVG(rating) FROM review_details
                 WHERE movie_id = ? AND status = 'approved'
             )
             WHERE id = ?"
        )->execute([$movie_id, $movie_id]);

        $success = true;
    } else {
        $fields['rating']      = $rating;
        $fields['review_text'] = htmlspecialchars($review_text);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review — <?= htmlspecialchars($movie['title']) ?> — CineVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --accent2:#e8c98e; --text:#e8e4d8; --muted:#6b6860; --gold:#f0c040; --err:#c0392b; --ok:#27ae60; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  body::before { content:''; position:fixed; inset:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events:none; z-index:0; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .page { display:grid; grid-template-columns:300px 1fr; min-height:calc(100vh - 60px); }
  .sidebar { border-right:1px solid var(--border); background:var(--surface); position:relative; overflow:hidden; }
  .sidebar-poster { width:100%; aspect-ratio:2/3; display:block; object-fit:cover; opacity:.7; }
  .sidebar-poster-placeholder { width:100%; aspect-ratio:2/3; display:flex; align-items:center; justify-content:center; font-family:'Cormorant Garamond',serif; font-size:5rem; color:var(--border); }
  .sidebar-info { padding:1.5rem; border-top:1px solid var(--border); }
  .sidebar-title { font-family:'Cormorant Garamond',serif; font-size:1.4rem; font-weight:300; line-height:1.2; margin-bottom:.3rem; }
  .sidebar-meta { color:var(--muted); font-size:11px; letter-spacing:.04em; }
  .form-area { padding:4rem; display:flex; flex-direction:column; max-width:700px; }
  .back-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); text-decoration:none; font-size:11px; letter-spacing:.08em; text-transform:uppercase; margin-bottom:2rem; transition:color .2s; }
  .back-link:hover { color:var(--accent); }
  .form-heading { font-family:'Cormorant Garamond',serif; font-size:clamp(1.8rem,3vw,2.8rem); font-weight:300; letter-spacing:.06em; text-transform:uppercase; margin-bottom:.4rem; }
  .form-heading em { color:var(--accent); font-style:italic; }
  .form-sub { color:var(--muted); letter-spacing:.06em; margin-bottom:2.5rem; font-size:12px; }
  .star-label { display:block; font-size:11px; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:.8rem; }
  .star-picker { display:flex; gap:.4rem; margin-bottom:1.8rem; flex-direction:row-reverse; justify-content:flex-end; }
  .star-picker input[type=radio] { display:none; }
  .star-picker label { font-size:2rem; cursor:pointer; color:var(--border); transition:color .15s,transform .15s; line-height:1; }
  .star-picker label:hover, .star-picker label:hover ~ label, .star-picker input:checked ~ label { color:var(--gold); transform:scale(1.15); }
  .field { margin-bottom:1.5rem; }
  .field label { display:block; font-size:11px; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:.5rem; }
  .field textarea { width:100%; background:var(--surface); border:1px solid var(--border); color:var(--text); font-family:'Cormorant Garamond',serif; font-size:1rem; line-height:1.7; padding:1rem; border-radius:2px; resize:vertical; min-height:180px; outline:none; transition:border-color .2s; }
  .field textarea:focus { border-color:var(--accent); }
  .field textarea.err { border-color:var(--err); }
  .char-count { text-align:right; font-size:10px; color:var(--muted); margin-top:.3rem; }
  .error-msg { color:var(--err); font-size:11px; margin-top:.4rem; letter-spacing:.04em; }
  .submit-row { display:flex; gap:1rem; align-items:center; margin-top:.5rem; }
  .btn-submit { padding:12px 32px; background:var(--accent); color:var(--bg); border:none; border-radius:2px; font-family:'DM Mono',monospace; font-size:12px; letter-spacing:.1em; text-transform:uppercase; cursor:pointer; transition:background .2s; }
  .btn-submit:hover { background:var(--accent2); }
  .btn-cancel { color:var(--muted); text-decoration:none; font-size:11px; letter-spacing:.08em; text-transform:uppercase; transition:color .2s; }
  .btn-cancel:hover { color:var(--text); }
  .success-banner { background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.25); border-radius:2px; padding:2rem; display:flex; flex-direction:column; gap:1rem; animation:fadeUp .4s ease both; }
  @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
  .success-banner h3 { font-family:'Cormorant Garamond',serif; font-size:1.5rem; font-weight:300; color:var(--ok); }
  .success-banner p { color:var(--muted); letter-spacing:.04em; line-height:1.6; }
  .success-banner a { color:var(--accent); text-decoration:none; }
  footer { border-top:1px solid var(--border); padding:1.5rem 3rem; color:var(--muted); font-size:11px; letter-spacing:.06em; display:flex; justify-content:space-between; }
  @media(max-width:720px){ .page{grid-template-columns:1fr} .sidebar{display:none} .form-area{padding:2rem 1.2rem} nav,footer{padding-left:1.2rem;padding-right:1.2rem} }
</style>
</head>
<body>
<nav>
  <a class="nav-logo" href="index.php">CineVault</a>
  <div class="nav-links">
    <a href="list.php">Films</a>
    <a href="detail.php?id=<?= $movie_id ?>"><?= htmlspecialchars($movie['title']) ?></a>
    <a href="logout.php">Log out</a>
  </div>
</nav>

<div class="page">
  <aside class="sidebar">
    <?php if (!empty($movie['poster_path'])): ?>
      <img class="sidebar-poster" src="uploads/<?= htmlspecialchars($movie['poster_path']) ?>" alt="">
    <?php else: ?>
      <div class="sidebar-poster-placeholder">✦</div>
    <?php endif; ?>
    <div class="sidebar-info">
      <div class="sidebar-title"><?= htmlspecialchars($movie['title']) ?></div>
      <div class="sidebar-meta">
        <?= !empty($movie['release_year']) ? (int)$movie['release_year'] : '' ?>
        <?= !empty($movie['director']) ? ' · ' . htmlspecialchars($movie['director']) : '' ?>
      </div>
    </div>
  </aside>

  <main class="form-area">
    <a href="detail.php?id=<?= $movie_id ?>" class="back-link">← Back to film</a>

    <?php if ($success): ?>
      <div class="success-banner">
        <h3>Review submitted ✦</h3>
        <p>Thank you for sharing your thoughts on <strong><?= htmlspecialchars($movie['title']) ?></strong>.<br>Your review is pending approval and will appear publicly once approved.</p>
        <p>
          <a href="detail.php?id=<?= $movie_id ?>">← Return to film page</a>
          &nbsp;·&nbsp;
          <a href="list.php">Browse all films</a>
        </p>
      </div>
    <?php else: ?>
      <h1 class="form-heading">Write a <em>Review</em></h1>
      <p class="form-sub">Reviewing: <strong style="color:var(--text)"><?= htmlspecialchars($movie['title']) ?></strong><?= !empty($movie['release_year']) ? ' (' . (int)$movie['release_year'] . ')' : '' ?></p>

      <form method="POST" action="review.php?id=<?= $movie_id ?>" novalidate>
        <span class="star-label">Your Rating</span>
        <div class="star-picker">
          <?php for ($s = 5; $s >= 1; $s--): ?>
            <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>" <?= (int)$fields['rating'] === $s ? 'checked' : '' ?>>
            <label for="star<?= $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★</label>
          <?php endfor; ?>
        </div>
        <?php if (!empty($errors['rating'])): ?>
          <p class="error-msg"><?= htmlspecialchars($errors['rating']) ?></p>
        <?php endif; ?>

        <div class="field">
          <label for="review_text">Your Review</label>
          <textarea id="review_text" name="review_text" maxlength="2000" class="<?= !empty($errors['review_text']) ? 'err' : '' ?>" placeholder="What did you think of the film? Min. 10 characters."><?= $fields['review_text'] ?></textarea>
          <div class="char-count"><span id="cc">0</span> / 2000</div>
          <?php if (!empty($errors['review_text'])): ?>
            <p class="error-msg"><?= htmlspecialchars($errors['review_text']) ?></p>
          <?php endif; ?>
        </div>

        <div class="submit-row">
          <button type="submit" class="btn-submit">Submit Review ✦</button>
          <a href="detail.php?id=<?= $movie_id ?>" class="btn-cancel">Cancel</a>
        </div>
      </form>
    <?php endif; ?>
  </main>
</div>

<footer>
  <span><a href="list.php" style="color:inherit;text-decoration:none">← All Films</a></span>
  <span>CineVault · <?= date('Y') ?></span>
</footer>

<script>
  const ta = document.getElementById('review_text');
  const cc = document.getElementById('cc');
  if (ta && cc) { cc.textContent = ta.value.length; ta.addEventListener('input', () => cc.textContent = ta.value.length); }
</script>
</body>
</html>
