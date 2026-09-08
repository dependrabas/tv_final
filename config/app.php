<?php
declare(strict_types=1);

const APP_NAME = 'TCHS Digital Information Board';
const APP_TIMEZONE = 'Asia/Thimphu';
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

date_default_timezone_set(APP_TIMEZONE);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('tchs_board_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

