<?php

declare(strict_types=1);

?>
<div class="top">
  <div class="title">Dashboard</div>
  <button class="btn" id="refresh">Refresh</button>
</div>
<div class="grid">
  <div class="card half"><h3>Host Panel Status</h3><div class="kv" id="host"></div></div>
  <div class="card half"><h3>Mikrotik Routers</h3><div class="muted" id="routerSummary">Loading...</div></div>
  <div class="card">
    <h3>Router Status</h3>
    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>Name</th><th>Host</th><th>Status</th><th>Identity</th><th>Version</th><th>Uptime</th><th>CPU</th><th>Memory</th><th>Message</th>
          </tr>
        </thead>
        <tbody id="routers"></tbody>
      </table>
    </div>
  </div>
</div>
