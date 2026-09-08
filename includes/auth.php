<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please sign in to access the administration panel.');
        redirect('login.php');
    }
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, name, username, email, role FROM users WHERE id = ? AND status = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

