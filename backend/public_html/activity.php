<?php
declare(strict_types=1);
require dirname(__DIR__) . '/hoteltv/bootstrap/app.php';
hoteltv_require_admin();
$limit = isset($_GET['limit']) ? max(25, min(500, (int)$_GET['limit'])) : 100;
$statement = $pdo->query('SELECT a.*, ad.email administrator_email FROM audit_logs a LEFT JOIN administrators ad ON ad.id=a.administrator_id ORDER BY a.id DESC LIMIT ' . $limit);
$logs = $statement->fetchAll();
hoteltv_admin_header('Activity', 'activity');
?>
<div class="toolbar"><div><strong>Latest <?=count($logs)?> changes</strong><div class="help">Administrative create, update, activation and removal history.</div></div><div class="filter-links"><a href="?limit=100" class="<?=$limit===100?'active':''?>">100</a><a href="?limit=250" class="<?=$limit===250?'active':''?>">250</a><a href="?limit=500" class="<?=$limit===500?'active':''?>">500</a></div></div>
<section class="card"><div class="section-head"><div><h2>Audit log</h2><p>Useful for tracking who changed hotel configuration.</p></div></div>
<?php if (!$logs): ?><div class="empty"><strong>No activity recorded</strong><p>Changes will appear here.</p></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Administrator</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead><tbody>
<?php foreach ($logs as $log): ?><tr><td><?=h($log['created_at'])?></td><td><?=h($log['administrator_email'] ?: 'System/API')?></td><td><span class="badge"><?=h($log['action'])?></span></td><td><?=h($log['entity_type'] ?: '—')?><?=!empty($log['entity_id'])?' #'.h($log['entity_id']):''?></td><td class="audit-details"><?=h($log['details_json'] ?: '—')?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php hoteltv_admin_footer(); ?>
