<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$provider = (string) ($config['provider'] ?? '');
$webhookUrl = (string) ($config['webhook_url'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Chat') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Chat Configuration</h3>
    <div class="hint">Settings placeholder for chat integration.</div>
    <form method="post" action="/admin/chat">
      <input type="hidden" name="action" value="save">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Provider</label>
            <input type="text" name="provider" value="<?= $e($provider) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Webhook URL</label>
            <input type="text" name="webhook_url" value="<?= $e($webhookUrl) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
