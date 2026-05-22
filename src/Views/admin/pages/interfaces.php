<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Interfaces') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Interfaces Configuration</h3>
    <div class="hint">Settings placeholder for interface sync, naming, and monitoring.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Default Router</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Sync Interval (sec)</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
