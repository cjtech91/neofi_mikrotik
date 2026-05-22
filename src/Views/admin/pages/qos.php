<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'QoS') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>QoS Configuration</h3>
    <div class="hint">Settings placeholder for bandwidth profiles and shaping.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Default Limit</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Burst Limit</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
