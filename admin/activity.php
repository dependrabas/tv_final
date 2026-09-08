<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_layout.php';
require_login();
$page=max(1,filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT)?:1);$perPage=30;$offset=($page-1)*$perPage;$total=(int)db()->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
$rows=db()->query("SELECT a.*,u.name user_name FROM activity_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT {$perPage} OFFSET {$offset}")->fetchAll();admin_header('Activity Log','activity');
?>
<div class="content-toolbar"><p>An audit trail of important administrator actions.</p></div><section class="data-card"><div class="table-responsive"><table class="table"><thead><tr><th>Date & time</th><th>Administrator</th><th>Action</th><th>Content type</th><th>Details</th><th>IP address</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="6" class="empty-table">No activity has been recorded.</td></tr><?php endif;?><?php foreach($rows as $row):?><tr><td><?=e(format_date($row['created_at'],'d M Y, H:i'))?></td><td><?=e($row['user_name']??'System')?></td><td><span class="badge-status published"><?=e($row['action'])?></span></td><td><?=e(str_replace('_',' ',$row['entity_type']))?></td><td><?=e($row['details'])?></td><td><?=e($row['ip_address']??'—')?></td></tr><?php endforeach;?></tbody></table></div><?php if($total>$perPage):$pages=(int)ceil($total/$perPage);?><div class="pagination-wrap"><span>Page <?=$page?> of <?=$pages?></span><div><?php if($page>1):?><a class="btn-light-custom" href="?page=<?=$page-1?>">Previous</a><?php endif;?> <?php if($page<$pages):?><a class="btn-light-custom" href="?page=<?=$page+1?>">Next</a><?php endif;?></div></div><?php endif;?></section>
<?php admin_footer();

