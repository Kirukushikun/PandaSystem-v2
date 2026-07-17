<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pan }} — Print</title>
{{-- PLACEHOLDER. The real print layout ports from print-view.blade.php (the source of truth):
     3 copies (Employee / 201 Filing / Payroll), Courier, green borders, 4 signatories.
     This page only proves the route + print flow; always white, ignores the app theme. --}}
<style>
body{margin:0;background:#EDEFEA;font:13px/1.5 "Courier New",Courier,monospace;color:#1C221E;padding:24px}
.toolbar{max-width:760px;margin:0 auto 16px;display:flex;gap:10px;align-items:center;
  font-family:"Segoe UI Variable Text","Segoe UI",sans-serif}
.toolbar a,.toolbar button{border:1px solid #DCE1D8;border-radius:8px;background:#fff;color:#1C221E;
  font:inherit;font-size:13px;padding:7px 14px;cursor:pointer;text-decoration:none}
.toolbar button.primary{background:#1F5E42;border-color:#1F5E42;color:#fff;font-weight:600}
.toolbar .note{font-size:12px;color:#7C877E;margin-left:auto}
.sheet{max-width:760px;margin:0 auto 20px;background:#fff;border:3px double #1F5E42;padding:26px 30px}
.sheet header{display:flex;justify-content:space-between;align-items:baseline;border-bottom:2px solid #1F5E42;padding-bottom:8px;margin-bottom:14px}
.sheet header b{font-size:15px;letter-spacing:.08em}
.copy{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#1F5E42;font-weight:bold}
.ph{border:1px dashed #9DB3A6;padding:36px 20px;text-align:center;color:#7C877E;font-size:12px}
@media print{
  body{background:#fff;padding:0}
  .toolbar{display:none}
  .sheet{border-color:#1F5E42;page-break-after:always;max-width:none;margin:0 0 20px}
}
</style>
</head>
<body>
  <div class="toolbar">
    <button class="primary" onclick="window.print()">Print</button>
    <a href="javascript:history.back()">← Back</a>
    <span class="note">Placeholder — the real PAN layout ports from print-view.blade.php</span>
  </div>

  @foreach (['Employee Copy', '201 Filing Copy', 'Payroll Copy'] as $copy)
  <div class="sheet">
    <header>
      <b>PERSONNEL ACTION NOTICE</b>
      <span>{{ $pan }}</span>
    </header>
    <p class="copy">{{ $copy }}</p>
    <div class="ph">
      Print layout placeholder — the official 3-copy PAN form<br>
      (employment details, Action Reference From/To table, 4 signatories)<br>
      is ported from print-view.blade.php in the real build.
    </div>
  </div>
  @endforeach
</body>
</html>
