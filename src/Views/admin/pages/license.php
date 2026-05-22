<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$licenseKey = (string) ($config['license_key'] ?? '');
$companyName = (string) ($config['company_name'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'License') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>License</h3>
    <div class="hint">License management placeholder.</div>
    <form method="post" action="/admin/license">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="csrf_token" value="<?= $e((string) ($csrfToken ?? '')) ?>">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>License Key</label>
            <input type="text" name="license_key" value="<?= $e($licenseKey) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Company Name</label>
            <input type="text" name="company_name" value="<?= $e($companyName) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
