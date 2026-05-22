<?php

declare(strict_types=1);

$pageTitle = isset($title) ? (string) $title : '';
$activeKey = isset($active) ? (string) $active : '';
$includeDashboard = isset($includeDashboardScript) ? (bool) $includeDashboardScript : false;

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?></title>
    <style>
      :root{--bg:#0b1220;--panel:#121b2f;--border:#1c2a46;--text:#e6eaf2;--muted:#a7b1c8;--muted2:#b8c2d9;--brand:#2b6ef3}
      body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:var(--bg);color:var(--text)}
      .layout{display:flex;min-height:100vh}
      .sidebar{width:260px;background:#0d162a;border-right:1px solid var(--border);padding:18px 14px;position:sticky;top:0;align-self:flex-start;height:100vh;box-sizing:border-box}
      .brand{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
      .brand .name{font-weight:800;letter-spacing:.02em}
      .nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}
      .nav a{color:var(--muted);text-decoration:none;padding:10px 12px;border-radius:12px;display:flex;align-items:center;justify-content:space-between}
      .nav a:hover{background:#111e39;color:var(--text)}
      .nav a.active{background:#132247;border:1px solid var(--border);color:var(--text)}
      .badge{font-size:11px;font-weight:800;color:#6bb6ff;background:#0f2446;border:1px solid #16325c;padding:2px 8px;border-radius:999px}
      .content{flex:1}
      .wrap{max-width:1200px;margin:0 auto;padding:24px}
      .top{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
      .title{font-size:20px;font-weight:800}
      .btn{background:var(--brand);border:0;color:white;padding:10px 14px;border-radius:10px;cursor:pointer;font-weight:700}
      .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px;margin-top:16px}
      .card{grid-column:span 12;background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:14px}
      @media (min-width:900px){.card.half{grid-column:span 6}}
      .card h3{margin:0 0 10px 0;font-size:14px;color:var(--muted2);font-weight:800;letter-spacing:.04em;text-transform:uppercase}
      .kv{display:grid;grid-template-columns:180px 1fr;gap:6px 12px}
      .k{color:var(--muted)}
      .v{color:var(--text);word-break:break-word}
      table{width:100%;border-collapse:collapse}
      th,td{padding:10px;border-bottom:1px solid var(--border);text-align:left;font-size:13px}
      th{color:var(--muted2);font-weight:800}
      .pill{display:inline-block;padding:3px 10px;border-radius:999px;font-weight:800;font-size:12px}
      .ok{background:#12351f;color:#7ef29a;border:1px solid #1f5a34}
      .bad{background:#3a1414;color:#ff9aa3;border:1px solid #6a252a}
      .muted{color:var(--muted)}
      .field{display:grid;gap:6px;margin-top:10px}
      label{font-size:12px;color:var(--muted2);font-weight:800;letter-spacing:.03em;text-transform:uppercase}
      input,select,textarea{background:#0e1730;border:1px solid var(--border);border-radius:12px;color:var(--text);padding:10px 12px;font-size:14px;outline:none}
      textarea{min-height:110px;resize:vertical}
      .row{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
      .col-6{grid-column:span 12}
      @media (min-width:900px){.col-6{grid-column:span 6}}
      .hint{color:var(--muted);font-size:13px;line-height:1.4}
    </style>
  </head>
  <body>
    <div class="layout">
      <aside class="sidebar">
        <div class="brand">
          <div class="name">NEOFI Panel</div>
          <span class="badge" id="onlineCount">0/0</span>
        </div>
        <nav class="nav">
          <?php foreach (($pages ?? []) as $key => $item): ?>
            <?php
              $href = isset($item['href']) ? (string) $item['href'] : '#';
              $label = isset($item['label']) ? (string) $item['label'] : (string) $key;
              $isActive = ((string) $key) === $activeKey;
            ?>
            <a href="<?= $e($href) ?>" class="<?= $isActive ? 'active' : '' ?>"><?= $e($label) ?></a>
          <?php endforeach; ?>
        </nav>
      </aside>
      <main class="content">
        <div class="wrap">
          <?= $content ?? '' ?>
        </div>
      </main>
    </div>
    <script>
      async function fetchOverview(){
        const res = await fetch("/admin/overview", {headers: {"accept":"application/json"}});
        return await res.json();
      }

      async function loadOnlineCount(){
        try{
          const data = await fetchOverview();
          const online = data.routers.filter(r=>r.ok).length;
          const badge = document.getElementById("onlineCount");
          if (badge) badge.textContent = online + "/" + data.routers.length;
        }catch(e){}
      }

      loadOnlineCount();
<?php if ($includeDashboard): ?>
      const elHost = document.getElementById("host");
      const elRouters = document.getElementById("routers");
      const elRouterSummary = document.getElementById("routerSummary");
      function kv(key, val){const k=document.createElement("div");k.className="k";k.textContent=key;const v=document.createElement("div");v.className="v";v.textContent=val;elHost.appendChild(k);elHost.appendChild(v);}
      function pill(ok){const s=document.createElement("span");s.className="pill "+(ok?"ok":"bad");s.textContent=ok?"ONLINE":"OFFLINE";return s;}
      function td(text){const d=document.createElement("td");d.textContent=text;return d;}
      async function loadDashboard(){
        elHost.innerHTML=""; elRouters.innerHTML=""; elRouterSummary.textContent="Loading...";
        const data = await fetchOverview();
        kv("App Env", data.host.app_env);
        kv("PHP Version", data.host.php_version);
        kv("Server Time", data.host.server_time);
        kv("DB Status", data.host.db_ok ? "OK" : ("ERROR: "+(data.host.db_error||"unknown")));
        kv("Devices", String(data.routers.length));
        const online = data.routers.filter(r=>r.ok).length;
        elRouterSummary.textContent = online + " / " + data.routers.length + " online";
        for (const r of data.routers){
          const tr=document.createElement("tr");
          tr.appendChild(td(r.name));
          tr.appendChild(td(r.host));
          const s=document.createElement("td"); s.appendChild(pill(!!r.ok)); tr.appendChild(s);
          tr.appendChild(td(r.identity||""));
          tr.appendChild(td(r.version||""));
          tr.appendChild(td(r.uptime||""));
          tr.appendChild(td((r.cpu_load??"")!==""?String(r.cpu_load):""));
          tr.appendChild(td(r.memory||""));
          tr.appendChild(td(r.message||""));
          elRouters.appendChild(tr);
        }
        loadOnlineCount();
      }
      const refresh = document.getElementById("refresh");
      if (refresh) refresh.addEventListener("click", loadDashboard);
      loadDashboard();
<?php endif; ?>
    </script>
  </body>
</html>
