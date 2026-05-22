<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$provider = (string) ($config['provider'] ?? '');
$defaultZoom = (string) ($config['default_zoom'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'MAps') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>MAps Configuration</h3>
    <div class="hint">Settings placeholder for map provider and device geolocation.</div>
    <form method="post" action="/admin/maps">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="csrf_token" value="<?= $e((string) ($csrfToken ?? '')) ?>">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Provider</label>
            <input type="text" name="provider" value="<?= $e($provider) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Default Zoom</label>
            <input type="text" name="default_zoom" value="<?= $e($defaultZoom) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
