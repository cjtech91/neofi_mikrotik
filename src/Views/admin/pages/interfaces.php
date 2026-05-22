<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$defaultRouterId = (int) ($config['default_router_id'] ?? 0);
$syncInterval = (string) ($config['sync_interval_sec'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Interfaces') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Interfaces Configuration</h3>
    <div class="hint">Settings placeholder for interface sync, naming, and monitoring.</div>
    <form method="post" action="/admin/interfaces">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="csrf_token" value="<?= $e((string) ($csrfToken ?? '')) ?>">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Default Router</label>
            <select name="default_router_id">
              <option value="0">-- select --</option>
              <?php foreach (($devices ?? []) as $d): ?>
                <?php $id = (int) ($d['id'] ?? 0); ?>
                <option value="<?= $e((string) $id) ?>" <?= $id === $defaultRouterId ? 'selected' : '' ?>>
                  <?= $e((string) ($d['name'] ?? '')) ?> (<?= $e((string) ($d['host'] ?? '')) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Sync Interval (sec)</label>
            <input type="text" name="sync_interval_sec" value="<?= $e($syncInterval) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Sync From Router</h3>
    <div class="hint">Fetch live interfaces from the selected router.</div>
    <form method="get" action="/admin/interfaces">
      <input type="hidden" name="sync" value="1">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Router</label>
            <select name="device_id">
              <option value="0">-- select --</option>
              <?php foreach (($devices ?? []) as $d): ?>
                <?php $id = (int) ($d['id'] ?? 0); ?>
                <option value="<?= $e((string) $id) ?>" <?= $id === $defaultRouterId ? 'selected' : '' ?>>
                  <?= $e((string) ($d['name'] ?? '')) ?> (<?= $e((string) ($d['host'] ?? '')) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-6" style="display:flex;align-items:flex-end">
          <button class="btn" type="submit">Sync Now</button>
        </div>
      </div>
    </form>

    <?php if (isset($interfaces_error) && (string) $interfaces_error !== ''): ?>
      <div class="alert error" style="margin-top:12px"><?= $e((string) $interfaces_error) ?></div>
    <?php endif; ?>

    <?php if (isset($interfaces) && is_array($interfaces)): ?>
      <div style="overflow:auto;margin-top:12px">
        <table>
          <thead>
            <tr>
              <th>Name</th><th>Type</th><th>MTU</th><th>Running</th><th>Disabled</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($interfaces as $it): ?>
              <tr>
                <td><?= $e((string) ($it['name'] ?? '')) ?></td>
                <td><?= $e((string) ($it['type'] ?? '')) ?></td>
                <td><?= $e((string) ($it['mtu'] ?? '')) ?></td>
                <td><?= $e((string) ($it['running'] ?? '')) ?></td>
                <td><?= $e((string) ($it['disabled'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
