<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$currency = (string) ($config['currency'] ?? '');
$receiptHeader = (string) ($config['receipt_header'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'PPPOE Sales') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>PPPOE Sales</h3>
    <div class="hint">Sales configuration placeholder.</div>
    <form method="post" action="/admin/pppoesales">
      <input type="hidden" name="action" value="save">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Currency</label>
            <input type="text" name="currency" value="<?= $e($currency) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Receipt Header</label>
            <input type="text" name="receipt_header" value="<?= $e($receiptHeader) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
