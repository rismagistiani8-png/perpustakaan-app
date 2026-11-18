<?php
require __DIR__ . '/lib/app.php'; require __DIR__ . '/lib/admin_layout.php'; require_login();
$rows = load_riwayat();
admin_head('Riwayat Aktivitas', 'riwayat.php'); ?>
<p class="sub">200 aktivitas terakhir (tersimpan terenkripsi)</p><h1>Riwayat Aktivitas</h1>
<div class="panel"><table><tr><th>Waktu</th><th>Aktivitas</th><th>Keterangan</th><th>IP</th></tr>
<?php foreach ($rows as $x): $d = $x['d']; ?><tr><td><?= h($x['row']['created_at']) ?></td>
<?php if ($d): ?><td><?= h($d['a'] ?? '') ?></td><td><?= h($d['d'] ?? '') ?></td><td><?= h($d['ip'] ?? '') ?></td>
<?php else: ?><td colspan="3" class="err">Gagal didekripsi</td><?php endif; ?></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="4">Belum ada aktivitas.</td></tr><?php endif; ?></table></div>
<?php admin_foot();
