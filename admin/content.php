<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/admin_entities.php';
require_login();

$definitions = entity_definitions();
$type = (string) ($_GET['type'] ?? 'announcements');
if (!isset($definitions[$type])) {
    http_response_code(404); exit('Content type not found.');
}
$def = $definitions[$type];
$action = (string) ($_GET['action'] ?? 'list');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$statusColumn = $def['status_column'] ?? 'status';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postAction = (string) ($_POST['form_action'] ?? 'save');
    $postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    if ($postAction === 'delete' && $postId) {
        $stmt = db()->prepare("SELECT * FROM `{$type}` WHERE id = ?"); $stmt->execute([$postId]); $old = $stmt->fetch();
        db()->prepare("DELETE FROM `{$type}` WHERE id = ?")->execute([$postId]);
        if ($old && isset($def['file_column'])) delete_upload($old[$def['file_column']] ?? null);
        log_activity('delete', $type, $postId, $def['singular'] . ' deleted');
        flash('success', $def['singular'] . ' was deleted.'); redirect("admin/content.php?type={$type}");
    }
    if (in_array($postAction, ['publish','unpublish','archive'], true) && $postId) {
        $newStatus = $postAction === 'publish' ? 'published' : ($postAction === 'archive' ? 'archived' : 'draft');
        db()->prepare("UPDATE `{$type}` SET `{$statusColumn}` = ? WHERE id = ?")->execute([$newStatus, $postId]);
        log_activity($postAction, $type, $postId, $def['singular'] . ' status changed');
        flash('success', $def['singular'] . ' status was updated.'); redirect("admin/content.php?type={$type}");
    }
    if ($postAction === 'save') {
        $values = [];
        $newUploads = [];
        try {
            foreach ($def['fields'] as $name => $field) {
                if ($field['type'] === 'file') {
                    $uploaded = upload_file($name, $field['folder'], $field['mimes']);
                    if ($uploaded) { $values[$name] = $uploaded; $newUploads[] = $uploaded; }
                    continue;
                }
                $value = trim((string) ($_POST[$name] ?? ''));
                if (!empty($field['required']) && $value === '') throw new RuntimeException($field['label'] . ' is required.');
                if ($field['type'] === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
                if ($field['type'] === 'number') $value = (string) max(0, (int) $value);
                if ($field['type'] === 'select' && !array_key_exists($value, $field['options'])) throw new RuntimeException('Choose a valid ' . strtolower($field['label']) . '.');
                if ($field['type'] === 'staff_select' && (int) $value < 1) throw new RuntimeException('Choose a staff member.');
                $values[$name] = $value === '' ? null : $value;
            }
            if (isset($values['start_date'], $values['end_date']) && $values['start_date'] && $values['end_date'] && $values['end_date'] < $values['start_date']) throw new RuntimeException('End date cannot be before start date.');
            if (isset($values['publish_date'], $values['expiry_date']) && $values['expiry_date'] && $values['expiry_date'] < $values['publish_date']) throw new RuntimeException('Expiry date cannot be before publication date.');
            if (isset($values['publication_date'], $values['expiry_date']) && $values['expiry_date'] && $values['expiry_date'] < $values['publication_date']) throw new RuntimeException('Expiry date cannot be before publication date.');
            if ($type === 'media_slides' && !$postId && empty($values['media_path'])) throw new RuntimeException('Select an image or video file.');
            if ($type === 'media_slides') {
                $mediaPath = $values['media_path'] ?? null;
                if (!$mediaPath && $postId) {
                    $pathStmt = db()->prepare('SELECT media_path FROM media_slides WHERE id = ?');
                    $pathStmt->execute([$postId]);
                    $mediaPath = $pathStmt->fetchColumn() ?: null;
                }
                $isVideo = $mediaPath && (bool) preg_match('/\.(mp4|webm)$/i', (string) $mediaPath);
                if (($values['media_type'] === 'video') !== $isVideo) throw new RuntimeException('The selected media type must match the uploaded file.');
            }
            if (in_array($type, ['announcements','news'], true) && !$postId) $values['created_by'] = (int) $_SESSION['user_id'];
            if ($postId) {
                if (isset($def['file_column']) && isset($values[$def['file_column']])) {
                    $oldStmt = db()->prepare("SELECT `{$def['file_column']}` FROM `{$type}` WHERE id = ?"); $oldStmt->execute([$postId]); delete_upload($oldStmt->fetchColumn() ?: null);
                }
                $set = implode(', ', array_map(fn($column) => "`{$column}` = ?", array_keys($values)));
                db()->prepare("UPDATE `{$type}` SET {$set} WHERE id = ?")->execute([...array_values($values), $postId]);
                log_activity('update', $type, $postId, $def['singular'] . ' updated');
                flash('success', $def['singular'] . ' was updated successfully.');
            } else {
                $columns = array_keys($values); $marks = implode(',', array_fill(0, count($columns), '?')); $columnSql = '`' . implode('`,`', $columns) . '`';
                db()->prepare("INSERT INTO `{$type}` ({$columnSql}) VALUES ({$marks})")->execute(array_values($values));
                $postId = (int) db()->lastInsertId(); log_activity('create', $type, $postId, $def['singular'] . ' created');
                flash('success', $def['singular'] . ' was created successfully.');
            }
            redirect("admin/content.php?type={$type}");
        } catch (Throwable $exception) {
            foreach ($newUploads as $newUpload) delete_upload($newUpload);
            flash('danger', $exception instanceof RuntimeException ? $exception->getMessage() : 'The record could not be saved.');
            redirect("admin/content.php?type={$type}&action=" . ($postId ? 'edit&id=' . $postId : 'create'));
        }
    }
}

if (in_array($action, ['create','edit','view'], true)) {
    $record = [];
    if ($id) { $stmt = db()->prepare("SELECT * FROM `{$type}` WHERE id = ?"); $stmt->execute([$id]); $record = $stmt->fetch() ?: []; if (!$record) { http_response_code(404); exit('Record not found.'); } }
    $readOnly = $action === 'view';
    admin_header(($action === 'create' ? 'Add ' : ($action === 'view' ? 'View ' : 'Edit ')) . $def['singular'], $def['active']);
    ?>
    <div class="content-toolbar"><div><p><?= e($readOnly ? 'Review the complete record.' : 'Complete the fields below and save your changes.') ?></p></div><a class="btn-light-custom" href="<?= e(base_url("admin/content.php?type={$type}")) ?>"><i class="bi bi-arrow-left"></i> Back to list</a></div>
    <section class="admin-card form-card"><div class="admin-card-header"><div><h2><i class="bi <?= e($def['icon']) ?>"></i> <?= e($def['singular']) ?> details</h2><p>Fields marked with * are required.</p></div></div><div class="admin-card-body">
    <form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="form_action" value="save"><?= csrf_field() ?><div class="form-grid">
    <?php foreach ($def['fields'] as $name => $field): $value = $record[$name] ?? $field['default'] ?? ''; $full = !empty($field['full']) ? ' full' : ''; ?>
        <div class="form-group<?= $full ?>"><label class="form-label" for="<?= e($name) ?>"><?= e($field['label']) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
        <?php if ($field['type'] === 'textarea'): ?><textarea class="form-control" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4" <?= $readOnly ? 'readonly' : '' ?> <?= !empty($field['required']) ? 'required' : '' ?>><?= e($value) ?></textarea>
        <?php elseif ($field['type'] === 'select'): ?><select class="form-select" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= $readOnly ? 'disabled' : '' ?> <?= !empty($field['required']) ? 'required' : '' ?>><?php foreach ($field['options'] as $optionValue => $label): ?><option value="<?= e($optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
        <?php elseif ($field['type'] === 'staff_select'): $staff = db()->query("SELECT id, name, position FROM staff WHERE status != 'archived' ORDER BY name")->fetchAll(); ?><select class="form-select" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= $readOnly ? 'disabled' : '' ?> required><option value="">Choose a staff member</option><?php foreach ($staff as $person): ?><option value="<?= (int) $person['id'] ?>" <?= (int) $value === (int) $person['id'] ? 'selected' : '' ?>><?= e($person['name'] . ' — ' . $person['position']) ?></option><?php endforeach; ?></select>
        <?php elseif ($field['type'] === 'file'): ?><?php if (!$readOnly): ?><input class="form-control" type="file" id="<?= e($name) ?>" name="<?= e($name) ?>" accept="<?= e($field['accept']) ?>" data-preview="preview-<?= e($name) ?>"><div class="form-hint">Maximum file size: 20 MB. Existing file is kept if no replacement is selected.</div><?php endif; ?><?php if ($value): ?><?php if (preg_match('/\.(mp4|webm)$/i', (string) $value)): ?><video class="current-image" src="<?= e(public_path((string) $value)) ?>" controls></video><?php else: ?><img class="current-image" id="preview-<?= e($name) ?>" src="<?= e(public_path((string) $value)) ?>" alt="Current file"><?php endif; ?><?php elseif (!$readOnly): ?><img class="current-image" id="preview-<?= e($name) ?>" alt="Preview" hidden><?php endif; ?>
        <?php else: ?><input class="form-control" id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($field['type']) ?>" value="<?= e($value) ?>" <?= $field['type'] === 'number' ? 'min="0"' : '' ?> <?= $readOnly ? 'readonly' : '' ?> <?= !empty($field['required']) ? 'required' : '' ?>><?php endif; ?>
        </div>
    <?php endforeach; ?></div><?php if (!$readOnly): ?><div class="form-actions"><button class="btn-primary-custom" type="submit"><i class="bi bi-check2"></i> Save <?= e(strtolower($def['singular'])) ?></button><a class="btn-light-custom" href="<?= e(base_url("admin/content.php?type={$type}")) ?>">Cancel</a></div><?php endif; ?></form></div></section>
    <?php admin_footer(); exit;
}

$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1); $perPage = 15; $offset = ($page - 1) * $perPage;
$total = (int) db()->query("SELECT COUNT(*) FROM `{$type}`")->fetchColumn();
$rows = db()->query("SELECT * FROM `{$type}` ORDER BY updated_at DESC, id DESC LIMIT {$perPage} OFFSET {$offset}")->fetchAll();
$staffNames = []; if ($type === 'staff_leave') foreach (db()->query('SELECT id,name FROM staff') as $person) $staffNames[$person['id']] = $person['name'];
$today = date('Y-m-d');
$effective_status = function (array $row) use ($def, $statusColumn, $today): array {
    $value = (string) $row[$statusColumn];
    $window = $def['date_window'] ?? null;
    if ($value === 'published' && $window) {
        $end = $row[$window['end']] ?? null;
        $start = $row[$window['start']] ?? null;
        if ($end && $end < $today) return ['expired', 'Expired'];
        if ($start && $start > $today) return ['scheduled', 'Scheduled'];
    }
    return [$value, $value];
};
admin_header($def['label'], $def['active']);
?>
<div class="content-toolbar"><p><?= number_format($total) ?> record<?= $total === 1 ? '' : 's' ?> in this section</p><div class="content-toolbar-actions"><?php if ($type === 'staff_leave'): ?><a class="btn-light-custom" target="_blank" href="<?= e(base_url('index.php?view=staff-on-leave')) ?>"><i class="bi bi-display"></i> View staff on leave</a><?php endif; ?><a class="btn-primary-custom" href="<?= e(base_url("admin/content.php?type={$type}&action=create")) ?>"><i class="bi bi-plus-lg"></i> Add <?= e(strtolower($def['singular'])) ?></a></div></div>
<section class="data-card"><div class="table-responsive"><table class="table"><thead><tr><?php foreach ($def['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th class="text-end">Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="<?= count($def['columns']) + 1 ?>" class="empty-table"><i class="bi <?= e($def['icon']) ?>"></i><br>No records yet. Add the first one to get started.</td></tr><?php endif; ?>
<?php foreach ($rows as $row): ?><tr><?php foreach ($def['columns'] as $column => $label): $value = $column === 'staff_id' ? ($staffNames[$row[$column]] ?? 'Unknown staff') : $row[$column]; ?><td><?php if ($column === $statusColumn): [$badgeClass, $badgeLabel] = $effective_status($row); ?><span class="badge-status <?= e($badgeClass) ?>"><?= e($badgeLabel) ?></span><?php elseif (in_array($column, ['publication_status','programme_status','priority'], true)): ?><span class="badge-status <?= e((string) $value) ?>"><?= e((string) $value) ?></span><?php elseif (str_contains($column, 'date')): ?><?= e(format_date($value)) ?><?php else: ?><span class="<?= in_array($column, ['title','name','message'], true) ? 'table-title' : '' ?>"><?= e(mb_strimwidth((string) $value, 0, 75, '…')) ?></span><?php endif; ?></td><?php endforeach; ?>
<td><div class="actions"><a class="action-btn" href="<?= e(base_url("admin/content.php?type={$type}&action=view&id={$row['id']}")) ?>" title="View"><i class="bi bi-eye"></i></a><a class="action-btn" href="<?= e(base_url("admin/content.php?type={$type}&action=edit&id={$row['id']}")) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
<form method="post" title="<?= $row[$statusColumn] === 'published' ? 'Unpublish' : 'Publish' ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="form_action" value="<?= $row[$statusColumn] === 'published' ? 'unpublish' : 'publish' ?>"><button class="action-btn" type="submit"><i class="bi <?= $row[$statusColumn] === 'published' ? 'bi-eye-slash' : 'bi-check-circle' ?>"></i></button></form>
<form method="post" data-confirm="Delete this <?= e(strtolower($def['singular'])) ?> permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="form_action" value="delete"><button class="action-btn danger" type="submit" title="Delete"><i class="bi bi-trash"></i></button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php if ($total > $perPage): $pages = (int) ceil($total / $perPage); ?><div class="pagination-wrap"><span>Page <?= $page ?> of <?= $pages ?></span><div><?php if ($page > 1): ?><a class="btn-light-custom" href="?type=<?= e($type) ?>&page=<?= $page - 1 ?>">Previous</a><?php endif; ?> <?php if ($page < $pages): ?><a class="btn-light-custom" href="?type=<?= e($type) ?>&page=<?= $page + 1 ?>">Next</a><?php endif; ?></div></div><?php endif; ?></section>
<?php admin_footer();
