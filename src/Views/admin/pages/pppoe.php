<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'PPPoE') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>PPPoE Configuration</h3>
    <div class="hint">Settings placeholder for PPPoE profiles, pools, and defaults.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Default Profile</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Service Name</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
