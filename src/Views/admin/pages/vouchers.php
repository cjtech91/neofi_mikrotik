<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$prefix = (string) ($config['prefix'] ?? '');
$length = (string) ($config['length'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Vouchers') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Voucher Configuration</h3>
    <div class="hint">Settings placeholder for voucher generation rules.</div>
    <form method="post" action="/admin/vouchers">
      <input type="hidden" name="action" value="save">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Voucher Prefix</label>
            <input type="text" name="prefix" value="<?= $e($prefix) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Voucher Length</label>
            <input type="text" name="length" value="<?= $e($length) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
