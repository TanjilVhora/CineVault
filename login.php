<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Basic empty check
    if (empty($_POST['name']) || empty($_POST['password'])) {
        $error = "All fields are required!";
    } else {

        $name = $_POST['name'];

        // Look up the user by name
        $qry  = "SELECT * FROM user_details WHERE name = :nm LIMIT 1";
        $stmt = $pdo->prepare($qry);
        $stmt->bindParam(':nm', $name);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password against stored hash
        if ($user && password_verify($_POST['password'], $user['password'])) {

            // Start the session — these vars are used across all pages
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];   // 'user' or 'admin'

            // Redirect to wherever they were going, or movie list
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'list.php';
            header('Location: ' . $redirect);
            exit;

        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login – CineVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:     #0a0a0f;
            --surface:#111118;
            --border: #1e1e2e;
            --accent: #c8a96e;
            --text:   #e8e4d8;
            --muted:  #6b6860;
            --err:    #c0392b;
        }
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        body {
            background:var(--bg); color:var(--text);
            font-family:'DM Mono', monospace; font-size:13px;
            min-height:100vh;
            display:flex; align-items:center; justify-content:center;
        }
        .card {
            background:var(--surface); border:1px solid var(--border);
            border-radius:2px; padding:3rem; width:100%; max-width:400px;
        }
        .logo {
            font-family:'Cormorant Garamond',serif;
            font-size:1.8rem; font-weight:300; letter-spacing:.18em;
            color:var(--accent); text-transform:uppercase;
            text-align:center; margin-bottom:2rem;
            text-decoration:none; display:block;
        }
        h2 {
            font-family:'Cormorant Garamond',serif;
            font-size:1.4rem; font-weight:300; letter-spacing:.06em;
            text-align:center; margin-bottom:1.8rem;
        }
        .field { margin-bottom:1.2rem; }
        .field label {
            display:block; font-size:11px; letter-spacing:.1em;
            text-transform:uppercase; color:var(--muted); margin-bottom:.4rem;
        }
        .field input {
            width:100%; background:var(--bg); border:1px solid var(--border);
            color:var(--text); font-family:'DM Mono',monospace; font-size:13px;
            padding:10px 12px; border-radius:2px; outline:none; transition:border-color .2s;
        }
        .field input:focus { border-color:var(--accent); }
        .btn {
            width:100%; padding:11px; background:var(--accent); color:var(--bg);
            border:none; border-radius:2px; font-family:'DM Mono',monospace;
            font-size:12px; letter-spacing:.1em; text-transform:uppercase;
            cursor:pointer; transition:background .2s; margin-top:.5rem;
        }
        .btn:hover { background:#e8c98e; }
        .error {
            background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.3);
            color:var(--err); padding:.8rem 1rem; border-radius:2px;
            font-size:12px; margin-bottom:1.2rem; letter-spacing:.04em;
        }
        .footer-link {
            text-align:center; margin-top:1.5rem; color:var(--muted);
            font-size:11px; letter-spacing:.06em;
        }
        .footer-link a { color:var(--accent); text-decoration:none; }
        .footer-link a:hover { text-decoration:underline; }
    </style>
</head>
<body>

<div class="card">
    <a class="logo" href="list.php">CineVault</a>
    <h2>Sign In</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php<?= isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : '' ?>">
        <div class="field">
            <label for="name">Username</label>
            <input type="text" id="name" name="name"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   placeholder="Your username" autocomplete="username">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Your password" autocomplete="current-password">
        </div>
        <button class="btn" type="submit">Log In ✦</button>
    </form>

    <p class="footer-link">
        No account? <a href="register.php">Register here</a>
    </p>
</div>

</body>
</html>