<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = preg_replace('#/(admin|api)/.*$#', '', $script) ?: '';
    if (!str_contains($script, '/admin/') && !str_contains($script, '/api/')) {
        $root = rtrim(dirname($script), '/.');
    }
    return rtrim($root, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Your session token has expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings[$key] ?? $default;
}

function all_settings(): array
{
    $result = [];
    foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
        $result[$row['setting_key']] = $row['setting_value'];
    }
    return $result;
}

function update_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP');
    $stmt->execute([$key, $value]);
}

function public_path(?string $path, string $fallback = 'assets/images/placeholder.svg'): string
{
    if (!$path) {
        return base_url($fallback);
    }
    return base_url($path);
}

function upload_file(string $field, string $folder, array $allowedMime, int $maxBytes = MAX_UPLOAD_BYTES): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxBytes) {
        throw new RuntimeException('The upload failed or exceeds the allowed file size.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!array_key_exists($mime, $allowedMime)) {
        throw new RuntimeException('This file type is not allowed.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
    $relative = 'uploads/' . trim($folder, '/') . '/' . $filename;
    $target = dirname(__DIR__) . '/' . $relative;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }
    return $relative;
}

function delete_upload(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/')) {
        return;
    }
    $full = realpath(dirname(__DIR__) . '/' . $path);
    $uploads = realpath(dirname(__DIR__) . '/uploads');
    if ($full && $uploads && str_starts_with($full, $uploads . DIRECTORY_SEPARATOR) && is_file($full)) {
        unlink($full);
    }
}

function log_activity(string $action, string $entityType = '', ?int $entityId = null, string $details = ''): void
{
    $stmt = db()->prepare('INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $entityType, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

function clean_status(string $value): string
{
    return in_array($value, ['draft', 'published', 'archived'], true) ? $value : 'draft';
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '—';
    }
    return date($format, strtotime($date));
}

function fetch_board_data(): array
{
    $pdo = db();
    $today = date('Y-m-d');
    $ann = $pdo->prepare("SELECT * FROM announcements WHERE status='published' AND publish_date <= ? AND (expiry_date IS NULL OR expiry_date >= ?) ORDER BY FIELD(priority,'urgent','important','normal'), display_order, publish_date DESC LIMIT 8");
    $ann->execute([$today, $today]);
    $news = $pdo->prepare("SELECT * FROM news WHERE status='published' AND publication_date <= ? AND (expiry_date IS NULL OR expiry_date >= ?) ORDER BY display_order, publication_date DESC LIMIT 8");
    $news->execute([$today, $today]);
    $leave = $pdo->prepare("SELECT l.*, s.name, s.position, s.department, s.photo FROM staff_leave l JOIN staff s ON s.id=l.staff_id WHERE l.status='published' AND l.start_date <= ? AND l.end_date >= ? AND s.status='published' ORDER BY l.display_order, l.end_date, l.id LIMIT 8");
    $leave->execute([$today, $today]);
    $ticker = $pdo->prepare("SELECT * FROM ticker_notices WHERE status='published' AND start_date <= ? AND (expiry_date IS NULL OR expiry_date >= ?) ORDER BY FIELD(priority,'urgent','important','normal'), display_order LIMIT 12");
    $ticker->execute([$today, $today]);
    $slides = $pdo->prepare("SELECT * FROM media_slides WHERE status='published' AND (start_date IS NULL OR start_date <= ?) AND (expiry_date IS NULL OR expiry_date >= ?) ORDER BY display_order, id DESC");
    $slides->execute([$today, $today]);
    $publicSettings = all_settings();
    unset($publicSettings['mission']);

    return [
        'settings' => $publicSettings,
        'announcements' => $ann->fetchAll(),
        'news' => $news->fetchAll(),
        'staff_leave' => $leave->fetchAll(),
        'student_statistics' => $pdo->query("SELECT id, academic_year, total_students, status, created_at, updated_at FROM student_statistics WHERE status='published' ORDER BY academic_year DESC, id DESC LIMIT 1")->fetch() ?: [],
        'staff_statistics' => $pdo->query("SELECT * FROM staff_statistics WHERE status='published' ORDER BY recorded_date DESC, id DESC LIMIT 1")->fetch() ?: [],
        'programmes' => $pdo->query("SELECT * FROM programmes WHERE publication_status='published' AND programme_status='active' ORDER BY display_order, name")->fetchAll(),
        'ticker_notices' => $ticker->fetchAll(),
        'media_slides' => $slides->fetchAll(),
        'generated_at' => date(DATE_ATOM),
    ];
}
