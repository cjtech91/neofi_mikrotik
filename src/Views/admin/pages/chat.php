<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Chat') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Chat Configuration</h3>
    <div class="hint">Settings placeholder for chat integration.</div>
    <div class="row" style="margin-top:10px">
      <div class="col-6">
        <div class="field">
          <label>Provider</label>
          <input type="text" value="">
        </div>
      </div>
      <div class="col-6">
        <div class="field">
          <label>Webhook URL</label>
          <input type="text" value="">
        </div>
      </div>
    </div>
  </div>
</div>
