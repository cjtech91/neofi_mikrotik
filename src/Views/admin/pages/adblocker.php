<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Adblocker') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Adblocker</h3>
    <div class="hint">Settings placeholder for DNS-based blocking and allow/deny lists.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Mode</label>
          <select>
            <option>disabled</option>
            <option>enabled</option>
          </select>
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Provider</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
    <div class="field">
      <label>Allowlist Domains</label>
      <textarea></textarea>
    </div>
    <div class="field">
      <label>Denylist Domains</label>
      <textarea></textarea>
    </div>
  </div>
</div>
