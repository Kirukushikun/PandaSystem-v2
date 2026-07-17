<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Logs &amp; Audit</h2>
      <p>Read-only records: sign-in attempts, and every significant action taken across the system.</p></div>
    <div class="spacer"></div>
    <button class="btn" type="button" onclick="showToast('CSV export arrives with the real build.')">Export CSV</button>
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Access Log — sign-in attempts</h3>
      <div>
        <div class="logrow"><time>Jul 14 · 08:02</time><span style="flex:1"><b>M. Dela Cruz</b> · 10.20.4.117</span><span class="pill p-appr">Success</span></div>
        <div class="logrow"><time>Jul 14 · 07:58</time><span style="flex:1"><b>T. Navarro</b> · 10.20.4.201</span><span class="pill p-appr">Success</span></div>
        <div class="logrow"><time>Jul 14 · 07:51</time><span style="flex:1"><b>j.ramos</b> · 10.20.7.33</span><span class="pill p-ret">Failed — bad password</span></div>
        <div class="logrow"><time>Jul 13 · 17:22</time><span style="flex:1"><b>K. Reyes</b> · 10.20.5.88</span><span class="pill p-appr">Success</span></div>
        <div class="logrow"><time>Jul 13 · 17:20</time><span style="flex:1"><b>unknown / admin</b> · 172.16.9.4</span><span class="pill p-ret">Failed — no account</span></div>
      </div>
    </div>
    <div class="pane">
      <h3>Audit Trail — system actions</h3>
      <div>
        <div class="logrow"><time>Jul 14 · 08:15</time><span class="mod">HR Prep</span><span><b>M. Dela Cruz</b> tagged PAN-2026-00341 as Confidential (Manila)</span></div>
        <div class="logrow"><time>Jul 14 · 08:04</time><span class="mod">Div Head</span><span><b>K. Reyes</b> approved PAN-2026-00344</span></div>
        <div class="logrow"><time>Jul 13 · 16:40</time><span class="mod">Final</span><span><b>V. Salazar</b> bulk-approved 3 Regularization PANs</span></div>
        <div class="logrow"><time>Jul 13 · 15:12</time><span class="mod">HR Approve</span><span><b>R. Ocampo</b> returned PAN-2026-00338 — "Wage number mismatch"</span></div>
        <div class="logrow"><time>Jul 13 · 11:03</time><span class="mod">HR Prep</span><span><b>T. Navarro</b> voided PAN-2026-00316 — "Duplicate of 00311"</span></div>
        <div class="logrow"><time>Jul 12 · 09:47</time><span class="mod">Admin</span><span><b>IT Admin</b> granted Division Head to K. Reyes (Broiler Operations)</span></div>
      </div>
    </div>
  </div>
</div>
