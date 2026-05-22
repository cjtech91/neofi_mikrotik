<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$redirectUrl = (string) ($config['redirect_url'] ?? '');
$theme = (string) ($config['theme'] ?? '');
$brandName = (string) ($config['brand_name'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Portal') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Portal Configuration</h3>
    <div class="hint">Settings placeholder for captive portal pages and branding.</div>
    <form method="post" action="/admin/portal">
      <input type="hidden" name="action" value="save">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Redirect URL</label>
            <input type="text" name="redirect_url" value="<?= $e($redirectUrl) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Theme</label>
            <input type="text" name="theme" value="<?= $e($theme) ?>">
          </div>
        </div>
      </div>
      <div class="field">
        <label>Brand Name</label>
        <input type="text" name="brand_name" value="<?= $e($brandName) ?>">
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
