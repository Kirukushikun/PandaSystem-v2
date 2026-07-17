<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PANDA — Sign in</title>
{{-- Standalone page ported from panda-login-concept.html — styles inline, no app.css/Vite.
     Extends the concept with the app's data-theme override so the saved theme choice holds here too. --}}
<script>
  try{var t=localStorage.getItem('panda-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}
</script>
<style>
:root{
  --ground:#F6F7F4; --panel:#FFFFFF; --line:#DCE1D8; --line-soft:#E8ECE5;
  --ink:#1C221E; --ink-2:#4B564E; --ink-3:#7C877E;
  --accent:#1F5E42; --accent-ink:#FFFFFF; --accent-soft:#E3EEE7;
  --red:#B3402F; --red-soft:#F7E3DE;
  --shadow:0 1px 2px rgba(28,34,30,.06),0 14px 40px rgba(28,34,30,.10);
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    --ground:#141915; --panel:#1B211C; --line:#2E3830; --line-soft:#28312A;
    --ink:#E7ECE7; --ink-2:#A9B4AA; --ink-3:#758177;
    --accent:#5CA67F; --accent-ink:#0F1712; --accent-soft:#1F3328;
    --red:#D9705C; --red-soft:#3D231D;
    --shadow:0 1px 2px rgba(0,0,0,.4),0 14px 40px rgba(0,0,0,.35);
  }
}
:root[data-theme="dark"]{
  --ground:#141915; --panel:#1B211C; --line:#2E3830; --line-soft:#28312A;
  --ink:#E7ECE7; --ink-2:#A9B4AA; --ink-3:#758177;
  --accent:#5CA67F; --accent-ink:#0F1712; --accent-soft:#1F3328;
  --red:#D9705C; --red-soft:#3D231D;
  --shadow:0 1px 2px rgba(0,0,0,.4),0 14px 40px rgba(0,0,0,.35);
}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--ground);color:var(--ink);
  font:14px/1.5 "Segoe UI Variable Text","Segoe UI",-apple-system,"Helvetica Neue",sans-serif;padding:24px}
.card{width:100%;max-width:380px;background:var(--panel);border:1px solid var(--line);border-radius:14px;
  box-shadow:var(--shadow);padding:34px 32px 28px}
.brand{text-align:center;margin-bottom:26px}
.brand h1{margin:0;font-size:26px;letter-spacing:.06em;font-weight:700;color:var(--accent)}
.brand h1 span{color:var(--ink-3);font-weight:400}
.brand p{margin:4px 0 0;font-size:12px;color:var(--ink-3)}
.field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.field label{font-size:11.5px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink-2)}
.field input{border:1px solid var(--line);border-radius:8px;background:var(--panel);color:var(--ink);
  font:inherit;padding:10px 12px;outline:none}
.field input:focus{border-color:var(--accent)}
.row{display:flex;align-items:center;justify-content:space-between;margin:2px 0 18px;font-size:12.5px;color:var(--ink-2)}
.row label{display:flex;align-items:center;gap:6px;cursor:pointer}
input[type=checkbox]{accent-color:var(--accent)}
.row a{color:var(--accent);text-decoration:none;font-weight:600}
.row a:hover{text-decoration:underline}
button{width:100%;border:0;border-radius:8px;background:var(--accent);color:var(--accent-ink);
  font:600 14px "Segoe UI Variable Text","Segoe UI",sans-serif;padding:11px;cursor:pointer}
button:hover{filter:brightness(.95)}
button:focus-visible,.field input:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.hint{margin:16px 0 0;text-align:center;font-size:11.5px;color:var(--ink-3)}
footer{margin-top:22px;text-align:center;font-size:11px;color:var(--ink-3)}
</style>
</head>
<body>
<div>
  {{-- Static login scaffold. Real build: credentials checked against the external company
       system (ExternalAuthService — PANDA never stores passwords); failed attempts land in
       the Access Log. Submitting just navigates into the app. --}}
  <form class="card" action="{{ route('requests.index') }}" method="get">
    <div class="brand">
      <h1>PANDA<span>&nbsp;v2</span></h1>
      <p>Personnel Action Notice workflow · BFC Group</p>
    </div>
    <div class="field">
      <label for="u">Username</label>
      <input id="u" autocomplete="username" placeholder="e.g. mdelacruz">
    </div>
    <div class="field">
      <label for="p">Password</label>
      <input id="p" type="password" autocomplete="current-password" placeholder="••••••••">
    </div>
    <div class="row">
      <label><input type="checkbox"> Remember me</label>
      <a href="#">Forgot password?</a>
    </div>
    <button type="submit">Sign in</button>
    <p class="hint">Company account required — access is managed by IT Administration.</p>
  </form>
  <footer>PANDA v2 · static UI concept — no data is live</footer>
</div>
</body>
</html>
