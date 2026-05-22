<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$deviceId = (int) ($config['device_id'] ?? 0);
$mode = (string) ($config['mode'] ?? 'disabled');
$redirectIp = (string) ($config['redirect_ip'] ?? '0.0.0.0');
$allowlist = (string) ($config['allowlist'] ?? '');
$denylist = (string) ($config['denylist'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Adblocker') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Adblocker</h3>
    <div class="hint">Settings placeholder for DNS-based blocking and allow/deny lists.</div>
    <form method="post" action="/admin/adblocker">
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
            <label>Mode</label>
            <select name="mode">
              <option value="disabled" <?= $mode === 'disabled' ? 'selected' : '' ?>>disabled</option>
              <option value="enabled" <?= $mode === 'enabled' ? 'selected' : '' ?>>enabled</option>
            </select>
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Redirect IP</label>
            <input type="text" name="redirect_ip" value="<?= $e($redirectIp) ?>">
          </div>
        </div>
      </div>

      <div class="field">
        <label>Allowlist Domains</label>
        <textarea name="allowlist"><?= $e($allowlist) ?></textarea>
      </div>
      <div class="field">
        <label>Denylist Domains</label>
        <textarea name="denylist"><?= $e($denylist) ?></textarea>
      </div>

      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn" type="submit" name="action" value="save">Save</button>
        <button class="btn" type="submit" name="action" value="apply">Save & Apply to Router</button>
      </div>
    </form>
  </div>
</div>
