<?php

declare(strict_types=1);

$kv = '';
foreach (($flags ?? []) as $k => $v) {
    $kv .= '<div class="k">' . $e((string) $k) . '</div><div class="v">' . $e((string) $v) . '</div>';
}

?>
<div class="top">
  <div class="title"><?= $e($title ?? 'Settings') ?></div>
</div>
<div class="grid">
  <div class="card half">
    <h3>Environment</h3>
    <div class="kv"><?= $kv ?></div>
  </div>
  <div class="card half">
    <h3>Notes</h3>
    <div class="hint">Sensitive values are not displayed here.</div>
  </div>
</div>
