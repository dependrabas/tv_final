<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_login();
$pdo = db();
$today = date('Y-m-d');
$cards = [
    ['Announcements', (int) $pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published' AND (expiry_date IS NULL OR expiry_date >= CURDATE())")->fetchColumn(), 'bi-megaphone-fill', ''],
    ['Published news', (int) $pdo->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(), 'bi-newspaper', 'blue'],
    ['Staff on leave', (int) $pdo->query("SELECT COUNT(*) FROM staff_leave WHERE status='published' AND CURDATE() BETWEEN start_date AND end_date")->fetchColumn(), 'bi-calendar2-week-fill', 'gold'],
    ['Urgent notices', (int) $pdo->query("SELECT COUNT(*) FROM ticker_notices WHERE status='published' AND priority='urgent' AND (expiry_date IS NULL OR expiry_date >= CURDATE())")->fetchColumn(), 'bi-exclamation-triangle-fill', 'red'],
];
$logs = $pdo->query('SELECT a.*, u.name user_name FROM activity_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 8')->fetchAll();
$user = current_user();
admin_header('Dashboard', 'dashboard');
?>
<section class="dashboard-welcome"><div><h2>Kuzuzangpo, <?= e(explode(' ', $user['name'] ?? 'Administrator')[0]) ?>.</h2><p>Here is what is happening on the TCHS information board today, <?= e(format_date($today, 'l, d F Y')) ?>.</p></div><a target="_blank" href="<?= e(base_url('index.php')) ?>"><i class="bi bi-fullscreen"></i> Open display</a></section>
<section class="summary-grid"><?php foreach ($cards as [$label,$value,$icon,$class]): ?><article class="summary-card <?= e($class) ?>"><div class="summary-icon"><i class="bi <?= e($icon) ?>"></i></div><div><div class="summary-value"><?= number_format($value) ?></div><div class="summary-label"><?= e($label) ?></div></div></article><?php endforeach; ?></section>
<section class="dashboard-grid">
    <article class="admin-card"><div class="admin-card-header"><div><h2>Quick actions</h2><p>Publish and maintain the information shown around campus.</p></div></div><div class="admin-card-body"><div class="quick-grid">
        <a class="quick-link" href="<?= e(base_url('admin/content.php?type=announcements&action=create')) ?>"><i class="bi bi-megaphone"></i>Add announcement</a>
        <a class="quick-link" href="<?= e(base_url('admin/content.php?type=news&action=create')) ?>"><i class="bi bi-newspaper"></i>Publish a news story</a>
        <a class="quick-link" href="<?= e(base_url('admin/content.php?type=staff_leave&action=create')) ?>"><i class="bi bi-calendar-plus"></i>Record staff leave</a>
        <a class="quick-link" href="<?= e(base_url('admin/content.php?type=media_slides&action=create')) ?>"><i class="bi bi-cloud-arrow-up"></i>Upload slideshow media</a>
        <a class="quick-link" href="<?= e(base_url('admin/statistics.php')) ?>"><i class="bi bi-bar-chart"></i>Update statistics</a>
        <a class="quick-link" href="<?= e(base_url('admin/settings.php')) ?>"><i class="bi bi-sliders"></i>Change board settings</a>
    </div></div></article>
    <article class="admin-card"><div class="admin-card-header"><div><h2>Recent activity</h2><p>Latest administrator actions.</p></div><a class="mini-note" href="<?= e(base_url('admin/activity.php')) ?>">View all</a></div><div class="admin-card-body activity-list">
        <?php if (!$logs): ?><div class="empty-state">No activity recorded yet.</div><?php endif; ?>
        <?php foreach ($logs as $log): ?><div class="activity-row"><div class="activity-icon"><i class="bi bi-clock-history"></i></div><div><strong><?= e(ucfirst($log['action']) . ' ' . str_replace('_',' ', $log['entity_type'])) ?></strong><small><?= e($log['user_name'] ?? 'System') ?></small></div><small><?= e(format_date($log['created_at'], 'd M, H:i')) ?></small></div><?php endforeach; ?>
    </div></article>
</section>
<?php admin_footer();

