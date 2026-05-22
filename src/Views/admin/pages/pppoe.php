<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$deviceId = (int) ($config['device_id'] ?? 0);
$defaultProfile = (string) ($config['default_profile'] ?? '');
$serviceName = (string) ($config['service_name'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'PPPoE') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>PPPoE Configuration</h3>
    <div class="hint">Settings placeholder for PPPoE profiles, pools, and defaults.</div>
    <form method="post" action="/admin/pppoe">
      <input type="hidden" name="csrf_token" value="<?= $e((string) ($csrfToken ?? '')) ?>">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Router</label>
            <select name="device_id">
              <option value="0">-- select --</option>
              <?php foreach (($devices ?? []) as $d): ?>
                <?php $id = (int) ($d['id'] ?? 0); ?>
                <option value="<?= $e((string) $id) ?>" <?= $id === $deviceId ? 'selected' : '' ?>>
                  <?= $e((string) ($d['name'] ?? '')) ?> (<?= $e((string) ($d['host'] ?? '')) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Service Name</label>
            <input type="text" name="service_name" value="<?= $e($serviceName) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Default Profile</label>
            <input type="text" name="default_profile" value="<?= $e($defaultProfile) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn" type="submit" name="action" value="save">Save</button>
        <button class="btn" type="submit" name="action" value="apply">Save & Apply to Router</button>
      </div>
    </form>
  </div>
</div>
