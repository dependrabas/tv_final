<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function admin_header(string $title, string $active = 'dashboard'): void
{
    require_login();
    $user = current_user();
    $flash = take_flash();
    $nav = [
        'dashboard' => ['admin/index.php', 'bi-grid-1x2-fill', 'Dashboard'],
        'welcome' => ['admin/welcome.php', 'bi-emoji-smile', 'Welcome note'],
        'announcements' => ['admin/content.php?type=announcements', 'bi-megaphone', 'Announcements'],
        'news' => ['admin/content.php?type=news', 'bi-newspaper', 'Latest news'],
        'staff' => ['admin/content.php?type=staff', 'bi-people', 'Staff directory'],
        'leave' => ['admin/content.php?type=staff_leave', 'bi-calendar2-week', 'Staff leave'],
        'programmes' => ['admin/content.php?type=programmes', 'bi-mortarboard', 'Programmes'],
        'ticker' => ['admin/content.php?type=ticker_notices', 'bi-broadcast-pin', 'Ticker notices'],
        'slides' => ['admin/content.php?type=media_slides', 'bi-images', 'Media slideshow'],
        'statistics' => ['admin/statistics.php', 'bi-bar-chart', 'Statistics'],
        'settings' => ['admin/settings.php', 'bi-sliders', 'Board settings'],
        'activity' => ['admin/activity.php', 'bi-clock-history', 'Activity log'],
    ];
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
    <title><?= e($title) ?> • TCHS Admin</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"><link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>"></head>
    <body class="admin-body">
    <div class="admin-shell">
        <aside class="sidebar" id="sidebar">
            <a class="sidebar-brand" href="<?= e(base_url('admin/index.php')) ?>"><img src="<?= e(public_path(setting('logo_path', 'assets/images/college-logo.png'))) ?>" alt=""><span><strong>TCHS</strong><small>Information Board</small></span></a>
            <nav class="sidebar-nav" aria-label="Administration">
                <span class="nav-label">Workspace</span>
                <?php foreach ($nav as $key => [$url, $icon, $label]): ?><a class="<?= $active === $key ? 'active' : '' ?>" href="<?= e(base_url($url)) ?>"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span></a><?php endforeach; ?>
            </nav>
            <div class="sidebar-footer"><div class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'A', 0, 1))) ?></div><div><strong><?= e($user['name'] ?? '') ?></strong><small><?= e(str_replace('_', ' ', $user['role'] ?? '')) ?></small></div><a href="<?= e(base_url('logout.php')) ?>" title="Sign out"><i class="bi bi-box-arrow-right"></i></a></div>
        </aside>
        <div class="admin-content">
            <header class="topbar"><button class="menu-button" type="button" id="menu-toggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button><div><span class="breadcrumb-label">Administration /</span><h1><?= e($title) ?></h1></div><div class="topbar-actions"><a class="board-link" target="_blank" href="<?= e(base_url('index.php')) ?>"><i class="bi bi-display"></i><span>View board</span></a><a class="icon-button" href="<?= e(base_url('admin/password.php')) ?>" title="Security"><i class="bi bi-shield-lock"></i></a></div></header>
            <main class="page-content">
                <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
            </main>
            <footer class="admin-footer"><span>© <?= date('Y') ?> Trongsa College of Heritage and Contemporary Studies</span><span>Secure administration portal</span></footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script><script src="<?= e(base_url('assets/js/admin.js')) ?>"></script>
    </body></html>
    <?php
}
