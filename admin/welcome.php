<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $welcomeEnabled = ($_POST['welcome_enabled'] ?? '') === '1';
        $welcomeKicker = trim((string) ($_POST['welcome_kicker'] ?? ''));
        $welcomeTitle = trim((string) ($_POST['welcome_title'] ?? ''));
        $welcomeMessage = trim((string) ($_POST['welcome_message'] ?? ''));
        $welcomeDuration = trim((string) ($_POST['welcome_duration'] ?? ''));
        if ($welcomeEnabled && ($welcomeTitle === '' || $welcomeMessage === '')) {
            throw new RuntimeException('Enter a welcome title and message before enabling the greeting.');
        }
        $kickerLength = function_exists('mb_strlen') ? mb_strlen($welcomeKicker, 'UTF-8') : strlen($welcomeKicker);
        $titleLength = function_exists('mb_strlen') ? mb_strlen($welcomeTitle, 'UTF-8') : strlen($welcomeTitle);
        $messageLength = function_exists('mb_strlen') ? mb_strlen($welcomeMessage, 'UTF-8') : strlen($welcomeMessage);
        if ($kickerLength > 100) {
            throw new RuntimeException('Keep the greeting line to 100 characters or fewer.');
        }
        if ($titleLength > 160 || $messageLength > 600) {
            throw new RuntimeException('Keep the welcome title to 160 characters and the message to 600 characters or fewer.');
        }
        if (!ctype_digit($welcomeDuration) || (int) $welcomeDuration < 3 || (int) $welcomeDuration > 60) {
            throw new RuntimeException('Welcome greeting duration must be a whole number between 3 and 60 seconds.');
        }
        db()->beginTransaction();
        update_setting('welcome_enabled', $welcomeEnabled ? '1' : '0');
        update_setting('welcome_kicker', $welcomeKicker);
        update_setting('welcome_title', $welcomeTitle);
        update_setting('welcome_message', $welcomeMessage);
        update_setting('welcome_duration', (string) (int) $welcomeDuration);
        db()->commit();
        log_activity('update', 'settings', null, 'Welcome note updated');
        flash('success', 'Welcome note was saved.');
    } catch (Throwable $exception) {
        if (db()->inTransaction()) db()->rollBack();
        flash('danger', $exception instanceof RuntimeException ? $exception->getMessage() : 'Welcome note could not be saved.');
    }
    redirect('admin/welcome.php');
}
$s = all_settings();
admin_header('Welcome Note', 'welcome');
?>
<div class="content-toolbar"><p>Control the greeting shown on the public board's welcome page.</p></div>
<section class="admin-card form-card">
    <div class="admin-card-header"><div><h2>Welcome greeting</h2><p>Shown as a dedicated view in the public-board rotation.</p></div></div>
    <div class="admin-card-body">
        <form method="post"><?= csrf_field() ?>
            <div class="form-check form-switch mb-3"><input class="form-check-input" id="welcome_enabled" type="checkbox" name="welcome_enabled" value="1" <?= ($s['welcome_enabled'] ?? '1') === '1' ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="welcome_enabled">Display the welcome greeting</label></div>
            <div class="form-grid">
                <div class="form-group full"><label class="form-label" for="welcome_kicker">Greeting line</label><input class="form-control" id="welcome_kicker" name="welcome_kicker" maxlength="100" value="<?= e($s['welcome_kicker'] ?? 'Kuzuzangpo la') ?>"><div class="form-hint">A short line above the welcome title.</div></div>
                <div class="form-group full"><label class="form-label" for="welcome_title">Welcome title</label><input class="form-control" id="welcome_title" name="welcome_title" maxlength="160" value="<?= e($s['welcome_title'] ?? 'Welcome to Trongsa College') ?>"><div class="form-hint">Keep it concise so it is easy to read from a distance.</div></div>
                <div class="form-group full"><label class="form-label" for="welcome_message">Welcome message</label><textarea class="form-control" id="welcome_message" name="welcome_message" rows="3" maxlength="600"><?= e($s['welcome_message'] ?? 'A place where Bhutanese heritage, curiosity, and purposeful learning come together.') ?></textarea></div>
                <div><label class="form-label" for="welcome_duration">Greeting duration (seconds)</label><input class="form-control" id="welcome_duration" type="number" min="3" max="60" step="1" name="welcome_duration" value="<?= (int) ($s['welcome_duration'] ?? 10) ?>" required><div class="form-hint">Between 3 and 60 seconds.</div></div>
            </div>
            <div class="form-actions"><button class="btn-primary-custom" type="submit"><i class="bi bi-check2"></i> Save welcome note</button></div>
        </form>
    </div>
</section>
<?php admin_footer();
