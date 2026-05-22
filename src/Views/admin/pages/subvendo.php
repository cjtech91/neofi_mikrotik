<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Subvendo') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Subvendo Configuration</h3>
    <div class="hint">Settings placeholder for reseller accounts and commissions.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Commission (%)</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Min Wallet Balance</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
