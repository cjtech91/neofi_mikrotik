<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$deviceId = (int) ($config['device_id'] ?? 0);
$defaultLimit = (string) ($config['default_limit'] ?? '');
$burstLimit = (string) ($config['burst_limit'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'QoS') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>QoS Configuration</h3>
    <div class="hint">Settings placeholder for bandwidth profiles and shaping.</div>
    <form method="post" action="/admin/qos">
      <input type="hidden" name="action" value="save">
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
            <label>Default Limit</label>
            <input type="text" name="default_limit" value="<?= $e($defaultLimit) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Burst Limit</label>
            <input type="text" name="burst_limit" value="<?= $e($burstLimit) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
