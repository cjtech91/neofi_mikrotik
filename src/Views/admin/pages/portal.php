<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Portal') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Portal Configuration</h3>
    <div class="hint">Settings placeholder for captive portal pages and branding.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Redirect URL</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Theme</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
    <div class="field">
      <label>Brand Name</label>
      <input type="text" value="">
    </div>
  </div>
</div>
