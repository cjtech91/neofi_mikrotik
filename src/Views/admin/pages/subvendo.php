<?php

declare(strict_types=1);

$config = is_array($config ?? null) ? $config : [];
$commission = (string) ($config['commission_percent'] ?? '');
$minWallet = (string) ($config['min_wallet_balance'] ?? '');
?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Subvendo') ?></div>
</div>
<div class="grid">
  <div class="card">
    <h3>Subvendo Configuration</h3>
    <div class="hint">Settings placeholder for reseller accounts and commissions.</div>
    <form method="post" action="/admin/subvendo">
      <input type="hidden" name="action" value="save">
      <div class="row" style="margin-top:10px">
        <div class="col-6">
          <div class="field">
            <label>Commission (%)</label>
            <input type="text" name="commission_percent" value="<?= $e($commission) ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="field">
            <label>Min Wallet Balance</label>
            <input type="text" name="min_wallet_balance" value="<?= $e($minWallet) ?>">
          </div>
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
