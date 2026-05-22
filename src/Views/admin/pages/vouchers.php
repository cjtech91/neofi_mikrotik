<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Vouchers') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Voucher Configuration</h3>
    <div class="hint">Settings placeholder for voucher generation rules.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Voucher Prefix</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Voucher Length</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
