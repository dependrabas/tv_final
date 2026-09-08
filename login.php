<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 1 LIMIT 1');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        log_activity('login', 'user', (int) $user['id'], 'Administrator signed in');
        redirect('admin/index.php');
    }
    usleep(random_int(250000, 550000));
    $error = 'The username or password is incorrect.';
}
$flash = take_flash();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrator sign in • TCHS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"><link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>"></head>
<body class="login-body">
<main class="login-shell">
    <section class="login-brand">
        <img src="<?= e(public_path(setting('logo_path', 'assets/images/college-logo.png'))) ?>" alt="TCHS logo">
        <span class="login-kicker">Royal University of Bhutan</span>
        <h1>A clearer campus starts here.</h1>
        <p>Manage the college information board, publish timely updates, and keep the TCHS community connected.</p>
        <a href="<?= e(base_url('index.php')) ?>"><i class="bi bi-arrow-left"></i> View public information board</a>
    </section>
    <section class="login-card">
        <div class="login-card-heading"><span>Secure administration</span><h2>Welcome back</h2><p>Sign in with your administrator account.</p></div>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div><?php endif; ?>
        <form method="post" class="login-form">
            <?= csrf_field() ?>
            <div><label for="username">Username or email</label><div class="input-icon"><i class="bi bi-person"></i><input class="form-control" id="username" name="username" autocomplete="username" required autofocus placeholder="Enter your username"></div></div>
            <div><label for="password">Password</label><div class="input-icon"><i class="bi bi-lock"></i><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password"></div></div>
            <button class="btn login-button" type="submit">Sign in <i class="bi bi-arrow-right"></i></button>
        </form>
        <p class="login-help">For account assistance, contact the system administrator.</p>
    </section>
</main>
</body></html>
