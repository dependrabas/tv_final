<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_login();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $current=(string)($_POST['current_password']??''); $new=(string)($_POST['new_password']??''); $confirm=(string)($_POST['confirm_password']??'');
    $stmt=db()->prepare('SELECT password FROM users WHERE id=?'); $stmt->execute([$_SESSION['user_id']]); $hash=(string)$stmt->fetchColumn();
    if (!password_verify($current,$hash)) flash('danger','Your current password is incorrect.');
    elseif (strlen($new)<10 || !preg_match('/[A-Z]/',$new) || !preg_match('/[a-z]/',$new) || !preg_match('/\d/',$new)) flash('danger','Use at least 10 characters with uppercase, lowercase, and a number.');
    elseif ($new!==$confirm) flash('danger','The new passwords do not match.');
    else { db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['user_id']]); log_activity('password_change','user',(int)$_SESSION['user_id'],'Administrator password changed'); flash('success','Your password was changed successfully.'); }
    redirect('admin/password.php');
}
admin_header('Change Password','');
?>
<div class="content-toolbar"><p>Use a strong, unique password for your administrator account.</p></div><section class="admin-card form-card"><div class="admin-card-header"><div><h2><i class="bi bi-shield-lock"></i> Account security</h2><p>You will use the new password on your next sign-in.</p></div></div><div class="admin-card-body"><form method="post"><?= csrf_field() ?><div class="form-grid"><div class="form-group full"><label class="form-label" for="current_password">Current password *</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div><div><label class="form-label" for="new_password">New password *</label><input class="form-control" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="10" required><div class="form-hint">At least 10 characters, including uppercase, lowercase, and a number.</div></div><div><label class="form-label" for="confirm_password">Confirm new password *</label><input class="form-control" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="10" required></div></div><div class="form-actions"><button class="btn-primary-custom" type="submit">Change password</button></div></form></div></section>
<?php admin_footer();

