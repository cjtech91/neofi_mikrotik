<?php

declare(strict_types=1);

$rows = '';
foreach (($devices ?? []) as $d) {
    $name = $e((string) ($d['name'] ?? ''));
    $host = $e((string) ($d['host'] ?? ''));
    $apiPort = $e((string) ($d['api_port'] ?? ''));
    $useSsl = $e(((bool) ($d['use_ssl'] ?? false)) ? 'yes' : 'no');
    $username = $e((string) ($d['username'] ?? ''));
    $rows .= '<tr><td>' . $name . '</td><td>' . $host . '</td><td>' . $apiPort . '</td><td>' . $useSsl . '</td><td>' . $username . '</td></tr>';
}

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Devices') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Registered Routers</h3>
    <div class="hint">Add / edit devices via API endpoints.</div>
    <div style="overflow:auto;margin-top:10px">
      <table>
        <thead>
          <tr>
            <th>Name</th><th>Host</th><th>API Port</th><th>SSL</th><th>Username</th>
          </tr>
        </thead>
        <tbody><?= $rows ?></tbody>
      </table>
    </div>
  </div>
</div>
