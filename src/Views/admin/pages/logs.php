<?php

declare(strict_types=1);

$rows = '';
foreach (($logs ?? []) as $l) {
    $id = $e((string) ($l['id'] ?? ''));
    $deviceName = $e((string) ($l['device_name'] ?? ''));
    $action = $e((string) ($l['action'] ?? ''));
    $createdAt = $e((string) ($l['created_at'] ?? ''));
    $rows .= '<tr><td>' . $id . '</td><td>' . $deviceName . '</td><td>' . $action . '</td><td>' . $createdAt . '</td></tr>';
}

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Logs') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Recent Activity</h3>
    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Device</th><th>Action</th><th>Time</th>
          </tr>
        </thead>
        <tbody><?= $rows ?></tbody>
      </table>
    </div>
  </div>
</div>
