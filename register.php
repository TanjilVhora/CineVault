<?php
session_start();
include 'db.php';

$error = '';

// checking method post or get 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // checking name and pass should not be empty
    if (empty($_POST['name']) || empty($_POST['password'])) {
        $error = "All fields are required!";
    } else {

        $name = $_POST['name'];

        // converting pass into hash using BCRYPT
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $qry = "INSERT INTO user_details (name, password) VALUES (:nm, :pwd)";

        // $pdo is object from db.php
        $stmt = $pdo->prepare($qry);
        $stmt->bindParam(':nm', $name);
        $stmt->bindParam(':pwd', $password);
        $stmt->execute();

        // will load login.php directly after registration
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – CineVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #0a0a0f;
            --surface: #111118;
            --border:  #1e1e2e;
            --accent:  #c8a96e;
            --accent2: #e8c98e;
            --text:    #e8e4d8;
            --muted:   #6b6860;
            --err:     #c0392b;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Mono', monospace;
            font-size: 13px;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── Grain overlay ── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 0;
        }

        /* ── Left panel ── */
        .left-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3rem;
            background: linear-gradient(160deg, #0d0d14 0%, #0a0a0f 100%);
            border-right: 1px solid var(--border);
            overflow: hidden;
        }

        /* Big decorative text behind */
        .left-panel::before {
            content: 'CINE\AVAULT';
            position: absolute;
            top: 50%; left: -1rem;
            transform: translateY(-50%);
            font-family: 'Cormorant Garamond', serif;
            font-size: 9rem;
            font-weight: 600;
            line-height: .88;
            color: rgba(200,169,110,.05);
            letter-spacing: -.02em;
            pointer-events: none;
            white-space: pre-line;
        }

        /* Animated accent line */
        .left-panel::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, var(--accent), transparent);
            animation: slide 3s ease-in-out infinite;
        }
        @keyframes slide {
            0%   { transform: translateY(-100%); opacity: 0; }
            40%  { opacity: 1; }
            100% { transform: translateY(100%); opacity: 0; }
        }

        .panel-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem; font-weight: 300;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--accent); text-decoration: none;
            margin-bottom: 3rem;
            position: relative; z-index: 1;
        }

        .panel-quote {
            position: relative; z-index: 1;
        }
        .panel-quote blockquote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.9rem; font-weight: 300;
            font-style: italic; line-height: 1.4;
            color: var(--text);
        }
        .panel-quote blockquote em { color: var(--accent); font-style: normal; }
        .panel-quote cite {
            display: block;
            margin-top: .8rem;
            font-size: 11px; letter-spacing: .12em;
            text-transform: uppercase; color: var(--muted);
            font-style: normal;
        }

        /* Floating film-reel circles */
        .orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .orb-1 {
            width: 300px; height: 300px;
            top: 8%; right: -80px;
            background: radial-gradient(circle, rgba(200,169,110,.08) 0%, transparent 70%);
        }
        .orb-2 {
            width: 180px; height: 180px;
            top: 40%; left: 30%;
            background: radial-gradient(circle, rgba(200,169,110,.05) 0%, transparent 70%);
        }

        /* ── Right panel (form) ── */
        .right-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 4rem;
            position: relative; z-index: 1;
        }

        .form-card {
            width: 100%; max-width: 380px;
        }

        .form-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.4rem; font-weight: 300;
            letter-spacing: .05em; margin-bottom: .3rem;
        }
        .form-heading em { color: var(--accent); font-style: italic; }

        .form-sub {
            color: var(--muted); font-size: 12px;
            letter-spacing: .06em; margin-bottom: 2.5rem;
        }

        /* Error box */
        .error-box {
            background: rgba(192,57,43,.1);
            border: 1px solid rgba(192,57,43,.3);
            color: var(--err);
            padding: .8rem 1rem;
            border-radius: 2px;
            font-size: 12px;
            margin-bottom: 1.4rem;
            letter-spacing: .04em;
        }

        /* Fields */
        .field { margin-bottom: 1.3rem; }

        .field label {
            display: block;
            font-size: 10px; letter-spacing: .14em;
            text-transform: uppercase; color: var(--muted);
            margin-bottom: .45rem;
        }

        .input-wrap { position: relative; }

        .input-wrap .icon {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted); font-size: 15px;
            pointer-events: none;
        }

        .field input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            font-family: 'DM Mono', monospace;
            font-size: 13px;
            padding: 11px 12px 11px 38px;
            border-radius: 2px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,169,110,.08);
        }
        .field input::placeholder { color: var(--muted); }

        /* Password strength bar */
        .strength-bar {
            height: 2px;
            background: var(--border);
            border-radius: 2px;
            margin-top: .5rem;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%; width: 0%;
            border-radius: 2px;
            transition: width .3s, background .3s;
        }
        .strength-label {
            font-size: 10px; letter-spacing: .08em;
            color: var(--muted); margin-top: .3rem;
            text-align: right; min-height: 14px;
        }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--accent); color: var(--bg);
            border: none; border-radius: 2px;
            font-family: 'DM Mono', monospace;
            font-size: 12px; letter-spacing: .12em;
            text-transform: uppercase;
            cursor: pointer; transition: background .2s;
            margin-top: .4rem;
            position: relative; overflow: hidden;
        }
        .btn-submit:hover { background: var(--accent2); }
        .btn-submit::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,.08) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform .5s;
        }
        .btn-submit:hover::after { transform: translateX(100%); }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 1rem;
            margin: 1.5rem 0; color: var(--muted); font-size: 11px;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        .login-link {
            text-align: center; color: var(--muted);
            font-size: 11px; letter-spacing: .06em;
        }
        .login-link a { color: var(--accent); text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { padding: 2.5rem 1.5rem; justify-content: flex-start; padding-top: 4rem; }
        }
    </style>
</head>
<body>

<!-- ── LEFT PANEL ── -->
<div class="left-panel">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <a href="index.php" class="panel-logo">✦ CineVault</a>

    <div class="panel-quote">
        <blockquote>
            Cinema is a mirror by which we often see ourselves.<br>
            Join the archive, share your <em>voice</em>.
        </blockquote>
        <cite>— The CineVault Community</cite>
    </div>
</div>

<!-- ── RIGHT PANEL ── -->
<div class="right-panel">
    <div class="form-card">

        <h1 class="form-heading">Create <em>Account</em></h1>
        <p class="form-sub">Join the archive. It's free.</p>

        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <!-- Username -->
            <div class="field">
                <label for="name">Username</label>
                <div class="input-wrap">
                    <span class="icon">◈</span>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Choose a username"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="icon">◉</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        required
                    >
                </div>
                <!-- Strength indicator -->
                <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <button type="submit" class="btn-submit">Create Account ✦</button>
        </form>

        <div class="divider">or</div>

        <p class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </p>

    </div>
</div>

<script>
    // Password strength meter
    const pwd    = document.getElementById('password');
    const fill   = document.getElementById('strengthFill');
    const label  = document.getElementById('strengthLabel');

    const levels = [
        { min: 0,  pct: '0%',   color: 'transparent', text: '' },
        { min: 1,  pct: '25%',  color: '#c0392b',      text: 'Weak' },
        { min: 6,  pct: '50%',  color: '#e67e22',      text: 'Fair' },
        { min: 10, pct: '75%',  color: '#f0c040',      text: 'Good' },
        { min: 14, pct: '100%', color: '#27ae60',       text: 'Strong' },
    ];

    pwd.addEventListener('input', () => {
        const len = pwd.value.length;
        const hasUpper = /[A-Z]/.test(pwd.value);
        const hasNum   = /\d/.test(pwd.value);
        const hasSpec  = /[^a-zA-Z0-9]/.test(pwd.value);
        const score = len === 0 ? 0
                    : len < 6  ? 1
                    : len < 10 ? 2
                    : (len >= 10 && (hasUpper || hasNum || hasSpec)) ? 3
                    : (len >= 14 && hasUpper && hasNum && hasSpec)   ? 4
                    : 3;

        const lvl = levels[score];
        fill.style.width      = lvl.pct;
        fill.style.background = lvl.color;
        label.textContent     = lvl.text;
        label.style.color     = lvl.color;
    });
</script>

</body>
</html>