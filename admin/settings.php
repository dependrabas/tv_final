<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_login();
$allowed = ['college_name','university_name','short_name','vision','mission','primary_colour','accent_colour','refresh_interval','default_slide_duration','footer_text','contact_email','contact_phone'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $logo = null;
    $oldLogo = setting('logo_path');
    try {
        db()->beginTransaction();
        foreach ($allowed as $key) {
            $value = trim((string)($_POST[$key]??''));
            if (in_array($key,['refresh_interval','default_slide_duration'],true)) $value=(string)max($key==='refresh_interval'?30:3,(int)$value);
            if (in_array($key,['primary_colour','accent_colour'],true) && !preg_match('/^#[0-9a-fA-F]{6}$/',$value)) throw new RuntimeException('Enter valid six-digit colour values.');
            update_setting($key,$value);
        }
        $logo=upload_file('logo','logo',['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'],5*1024*1024);
        if ($logo) update_setting('logo_path',$logo);
        db()->commit();
        if ($logo) delete_upload($oldLogo);
        log_activity('update','settings',null,'General board settings updated'); flash('success','Board settings were saved.');
    } catch (Throwable $exception) { if(db()->inTransaction()) db()->rollBack(); if($logo) delete_upload($logo); flash('danger',$exception instanceof RuntimeException?$exception->getMessage():'Settings could not be saved.'); }
    redirect('admin/settings.php');
}
$s=all_settings(); admin_header('Board Settings','settings');
?>
<div class="content-toolbar"><p>Manage college identity, board content, timing, and appearance. Manage the public board's welcome greeting from <a href="<?= e(base_url('admin/welcome.php')) ?>">Welcome note</a>.</p></div>
<section class="admin-card form-card"><div class="admin-card-header"><div><h2>General system settings</h2><p>Changes appear after the public board's next live refresh.</p></div></div><div class="admin-card-body"><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><div class="form-grid">
<div class="form-group full"><label class="form-label" for="college_name">College name *</label><input class="form-control" id="college_name" name="college_name" value="<?= e($s['college_name']??'') ?>" required></div>
<div><label class="form-label" for="university_name">University name</label><input class="form-control" id="university_name" name="university_name" value="<?= e($s['university_name']??'') ?>"></div><div><label class="form-label" for="short_name">Short name</label><input class="form-control" id="short_name" name="short_name" value="<?= e($s['short_name']??'') ?>"></div>
<div class="form-group full"><label class="form-label" for="vision">Vision *</label><textarea class="form-control" id="vision" name="vision" rows="4" required><?= e($s['vision']??'') ?></textarea></div><div class="form-group full"><label class="form-label" for="mission">Mission *</label><textarea class="form-control" id="mission" name="mission" rows="4" required><?= e($s['mission']??'') ?></textarea></div>
<div><label class="form-label" for="primary_colour">Primary colour</label><input class="form-control form-control-color w-100" id="primary_colour" type="color" name="primary_colour" value="<?= e($s['primary_colour']??'#123d2c') ?>"></div><div><label class="form-label" for="accent_colour">Accent colour</label><input class="form-control form-control-color w-100" id="accent_colour" type="color" name="accent_colour" value="<?= e($s['accent_colour']??'#d29d2b') ?>"></div>
<div><label class="form-label" for="refresh_interval">Live refresh interval (seconds)</label><input class="form-control" id="refresh_interval" type="number" min="30" name="refresh_interval" value="<?= (int)($s['refresh_interval']??180) ?>"><div class="form-hint">Minimum 30 seconds.</div></div><div><label class="form-label" for="default_slide_duration">Information view duration (seconds)</label><input class="form-control" id="default_slide_duration" type="number" min="3" name="default_slide_duration" value="<?= (int)($s['default_slide_duration']??10) ?>"><div class="form-hint">Default is 10 seconds. A media slide may use its own duration.</div></div>
<div><label class="form-label" for="contact_email">Contact email</label><input class="form-control" id="contact_email" type="email" name="contact_email" value="<?= e($s['contact_email']??'') ?>"></div><div><label class="form-label" for="contact_phone">Contact telephone</label><input class="form-control" id="contact_phone" name="contact_phone" value="<?= e($s['contact_phone']??'') ?>"></div>
<div class="form-group full"><label class="form-label" for="footer_text">College motto / footer text</label><input class="form-control" id="footer_text" name="footer_text" value="<?= e($s['footer_text']??'') ?>"></div>
<div class="form-group full"><label class="form-label" for="logo">College logo</label><input class="form-control" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" data-preview="logo-preview"><div class="form-hint">PNG, JPG, or WebP up to 5 MB.</div><img class="current-image" id="logo-preview" src="<?= e(public_path($s['logo_path']??'assets/images/college-logo.png')) ?>" alt="Current college logo"></div>
</div><div class="form-actions"><button class="btn-primary-custom" type="submit"><i class="bi bi-check2"></i> Save settings</button></div></form></div></section>
<?php admin_footer();
