<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PANDA</title>
{{-- Deliberately unbranded and minimal — this page should not advertise what it is. --}}
<style>
:root{--ground:#F6F7F4;--panel:#fff;--line:#DCE1D8;--ink:#1C221E;--accent:#1F5E42}
@media (prefers-color-scheme: dark){:root{--ground:#141915;--panel:#1B211C;--line:#2E3830;--ink:#E7ECE7;--accent:#5CA67F}}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--ground);color:var(--ink);
  font:14px/1.5 "Segoe UI",sans-serif;padding:24px}
form{width:100%;max-width:300px;display:flex;gap:8px}
input{flex:1;border:1px solid var(--line);border-radius:8px;background:var(--panel);color:var(--ink);
  font:inherit;padding:10px 12px;outline:none}
input:focus{border-color:var(--accent)}
button{border:0;border-radius:8px;background:var(--accent);color:#fff;font:600 14px "Segoe UI",sans-serif;
  padding:10px 16px;cursor:pointer}
</style>
</head>
<body>
  <form method="POST" action="/bypass">
    @csrf
    <input type="password" name="p" placeholder="Secret" required autofocus autocomplete="off">
    <button type="submit">Enter</button>
  </form>
</body>
</html>
