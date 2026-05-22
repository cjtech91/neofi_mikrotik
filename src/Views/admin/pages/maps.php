<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'MAps') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>MAps Configuration</h3>
    <div class="hint">Settings placeholder for map provider and device geolocation.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Provider</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Default Zoom</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
