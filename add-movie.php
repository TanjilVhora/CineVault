<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: list.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $director    = trim($_POST['director'] ?? '');
    $release_year = (int)($_POST['release_year'] ?? 0);
    $synopsis    = trim($_POST['synopsis'] ?? '');

    if (empty($title)) {
        $error = 'Movie title is required.';
    } else {
        // Handle poster upload
        $poster_path = '';
        if (!empty($_FILES['poster']['name'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext         = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $poster_path = uniqid('poster_') . '.' . $ext;
            move_uploaded_file($_FILES['poster']['tmp_name'], $upload_dir . $poster_path);
        }

      // Fixed Query
$qry = "INSERT INTO movies_details (title, director_name, release_year, synopsis, poster_path, avg_rating)
        VALUES (:title, :director, :year, :synopsis, :poster, 0)";
        $stmt = $pdo->prepare($qry);
        $stmt->bindParam(':title',    $title);
        $stmt->bindParam(':director', $director);
        $stmt->bindParam(':year',     $release_year);
        $stmt->bindParam(':synopsis', $synopsis);
        $stmt->bindParam(':poster',   $poster_path);
        $stmt->execute();

        $success = "Movie \"$title\" added successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Movie — CineVault Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0a0a0f; --surface:#111118; --border:#1e1e2e; --accent:#c8a96e; --accent2:#e8c98e; --text:#e8e4d8; --muted:#6b6860; --ok:#27ae60; --err:#c0392b; }
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; min-height:100vh; }
  nav { position:sticky; top:0; display:flex; align-items:center; justify-content:space-between; padding:0 3rem; height:60px; background:rgba(10,10,15,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); z-index:100; }
  .nav-logo { font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:300; letter-spacing:.18em; color:var(--accent); text-decoration:none; text-transform:uppercase; }
  .nav-links { display:flex; gap:2rem; align-items:center; }
  .nav-links a { color:var(--muted); text-decoration:none; letter-spacing:.1em; font-size:11px; text-transform:uppercase; transition:color .2s; }
  .nav-links a:hover { color:var(--accent); }
  .form-wrap { max-width:600px; margin:4rem auto; padding:0 2rem; }
  .back-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); text-decoration:none; font-size:11px; letter-spacing:.08em; text-transform:uppercase; margin-bottom:2rem; transition:color .2s; }
  .back-link:hover { color:var(--accent); }
  h1 { font-family:'Cormorant Garamond',serif; font-size:2.5rem; font-weight:300; letter-spacing:.06em; margin-bottom:2rem; }
  h1 em { color:var(--accent); font-style:italic; }
  .field { margin-bottom:1.3rem; }
  .field label { display:block; font-size:11px; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:.5rem; }
  .field input, .field textarea, .field select { width:100%; background:var(--surface); border:1px solid var(--border); color:var(--text); font-family:'DM Mono',monospace; font-size:13px; padding:10px 12px; border-radius:2px; outline:none; transition:border-color .2s; }
  .field input:focus, .field textarea:focus { border-color:var(--accent); }
  .field textarea { min-height:120px; resize:vertical; font-family:'Cormorant Garamond',serif; font-size:1rem; line-height:1.6; }
  .btn { width:100%; padding:12px; background:var(--accent); color:var(--bg); border:none; border-radius:2px; font-family:'DM Mono',monospace; font-size:12px; letter-spacing:.1em; text-transform:uppercase; cursor:pointer; transition:background .2s; margin-top:.5rem; }
  .btn:hover { background:var(--accent2); }
  .success-box { background:rgba(39,174,96,.1); border:1px solid rgba(39,174,96,.25); color:var(--ok); padding:.8rem 1rem; border-radius:2px; font-size:12px; margin-bottom:1.4rem; letter-spacing:.04em; }
  .error-box { background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3); color:var(--err); padding:.8rem 1rem; border-radius:2px; font-size:12px; margin-bottom:1.4rem; letter-spacing:.04em; }
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

<div class="form-wrap">
  <a href="admin.php" class="back-link">← Admin Panel</a>
  <h1>Add <em>Movie</em></h1>

  <?php if ($success): ?>
    <div class="success-box"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="error-box"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="add-movie.php" enctype="multipart/form-data">
    <div class="field">
      <label for="title">Movie Title *</label>
      <input type="text" id="title" name="title" placeholder="e.g. The Godfather" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="director">Director</label>
      <input type="text" id="director" name="director" placeholder="e.g. Francis Ford Coppola" value="<?= htmlspecialchars($_POST['director'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="release_year">Release Year</label>
      <input type="number" id="release_year" name="release_year" placeholder="e.g. 1972" min="1888" max="2100" value="<?= htmlspecialchars($_POST['release_year'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="synopsis">Synopsis</label>
      <textarea id="synopsis" name="synopsis" placeholder="Brief description of the movie..."><?= htmlspecialchars($_POST['synopsis'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label for="poster">Poster Image</label>
      <input type="file" id="poster" name="poster" accept="image/*">
    </div>
    <button type="submit" class="btn">Add Movie ✦</button>
  </form>
</div>
</body>
</html>
