<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_login();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $kind = (string) ($_POST['kind'] ?? '');
    if ($kind === 'students') {
        $year = trim((string) ($_POST['academic_year'] ?? ''));
        $total = max(0, (int) ($_POST['total_students'] ?? 0));
        if ($year === '') { flash('danger','Academic year is required.'); redirect('admin/statistics.php'); }
        $pdo->prepare('INSERT INTO student_statistics (academic_year,total_students,status) VALUES (?,?,?)')->execute([$year,$total,clean_status((string)($_POST['status']??''))]);
        log_activity('update','student_statistics',(int)$pdo->lastInsertId(),'New student statistics snapshot'); flash('success','Student statistics were updated.');
    } elseif ($kind === 'staff') {
        $teaching=max(0,(int)($_POST['teaching_staff']??0)); $support=max(0,(int)($_POST['admin_support_staff']??0)); $date=(string)($_POST['recorded_date']??date('Y-m-d'));
        $pdo->prepare('INSERT INTO staff_statistics (teaching_staff,admin_support_staff,recorded_date,status) VALUES (?,?,?,?)')->execute([$teaching,$support,$date,clean_status((string)($_POST['status']??''))]);
        log_activity('update','staff_statistics',(int)$pdo->lastInsertId(),'New staff statistics snapshot'); flash('success','Staff statistics were updated.');
    }
    redirect('admin/statistics.php');
}
$students=$pdo->query('SELECT * FROM student_statistics ORDER BY academic_year DESC,id DESC LIMIT 1')->fetch()?:[];
$staff=$pdo->query('SELECT * FROM staff_statistics ORDER BY recorded_date DESC,id DESC LIMIT 1')->fetch()?:[];
admin_header('College Statistics','statistics');
?>
<div class="content-toolbar"><p>Saving creates a dated snapshot; the latest published snapshot appears on the board.</p></div>
<div class="stat-form-grid">
<section class="admin-card"><div class="admin-card-header"><div><h2><i class="bi bi-people-fill"></i> Student statistics</h2><p>Current total student enrolment.</p></div></div><div class="admin-card-body"><form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="kind" value="students"><div class="form-group full"><label class="form-label" for="academic_year">Academic year *</label><input class="form-control" id="academic_year" name="academic_year" value="<?= e($students['academic_year']??date('Y').'–'.(date('Y')+1)) ?>" required></div><div><label class="form-label" for="total_students">Total students *</label><input class="form-control" id="total_students" type="number" min="0" name="total_students" value="<?= (int)($students['total_students']??0) ?>" required></div><div><label class="form-label" for="student_status">Status</label><select class="form-select" id="student_status" name="status"><option value="published">Published</option><option value="draft">Draft</option><option value="archived">Archived</option></select></div><div class="form-actions full"><button class="btn-primary-custom" type="submit">Save student statistics</button></div></form></div></section>
<section class="admin-card"><div class="admin-card-header"><div><h2><i class="bi bi-person-badge-fill"></i> Staff statistics</h2><p>Teaching and administrative staffing.</p></div></div><div class="admin-card-body"><form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="kind" value="staff"><div><label class="form-label" for="teaching_staff">Teaching staff</label><input class="form-control" id="teaching_staff" type="number" min="0" name="teaching_staff" value="<?= (int)($staff['teaching_staff']??0) ?>"></div><div><label class="form-label" for="admin_support_staff">Admin & support staff</label><input class="form-control" id="admin_support_staff" type="number" min="0" name="admin_support_staff" value="<?= (int)($staff['admin_support_staff']??0) ?>"></div><div><label class="form-label" for="recorded_date">Recorded date</label><input class="form-control" id="recorded_date" type="date" name="recorded_date" value="<?= e($staff['recorded_date']??date('Y-m-d')) ?>" required></div><div><label class="form-label" for="staff_status">Status</label><select class="form-select" id="staff_status" name="status"><option value="published">Published</option><option value="draft">Draft</option><option value="archived">Archived</option></select></div><div class="form-actions full"><button class="btn-primary-custom" type="submit">Save staff statistics</button></div></form></div></section>
</div>
<?php admin_footer();
