/* Theme toggle: stamps data-theme on <html> (wins over the OS preference in both directions)
   and persists the choice. With nothing stored, the page follows the OS via prefers-color-scheme. */
(function(){
  var root=document.documentElement;
  var btn=document.getElementById('theme-toggle');
  if(!btn)return;
  var sun='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>';
  var moon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  try{var saved=localStorage.getItem('panda-theme');if(saved)root.dataset.theme=saved}catch(e){}
  function eff(){
    return root.dataset.theme||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');
  }
  function paint(){btn.innerHTML=(eff()==='dark')?sun:moon;
    btn.title=(eff()==='dark')?'Switch to light theme':'Switch to dark theme'}
  btn.addEventListener('click',function(){
    var next=(eff()==='dark')?'light':'dark';
    root.dataset.theme=next;
    try{localStorage.setItem('panda-theme',next)}catch(e){}
    paint();
  });
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change',paint);
  paint();
})();

/* Notification bell: panel expands on click, collapses on outside click / Escape.
   "Mark all read" clears the unread highlights and the badge. */
(function(){
  var btn=document.getElementById('notif-btn');
  var panel=document.getElementById('notif-panel');
  if(!btn||!panel)return;
  btn.addEventListener('click',function(e){
    e.stopPropagation();
    panel.classList.toggle('open');
  });
  document.addEventListener('click',function(e){
    if(!e.target.closest('#notif-panel')&&!e.target.closest('#notif-btn'))
      panel.classList.remove('open');
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape')panel.classList.remove('open');
  });
  document.getElementById('notif-clear').addEventListener('click',function(){
    panel.querySelectorAll('.nrow.unread').forEach(function(r){r.classList.remove('unread')});
    document.getElementById('notif-count').style.display='none';
  });
})();

function showSub(id){
  var panel=document.getElementById('t-'+id);
  if(!panel)return;
  var section=panel.closest('.screen');
  var hl=panel.dataset.stab||id; /* sub-views (e.g. employee PAN history) highlight their parent tab */
  section.querySelectorAll('.stab').forEach(function(x){x.classList.toggle('on',x.dataset.t===hl)});
  section.querySelectorAll('.sub').forEach(function(s){s.classList.remove('on')});
  panel.classList.add('on');
  window.scrollTo(0,0);
}
document.querySelectorAll('.stab').forEach(function(b){
  b.addEventListener('click',function(){showSub(b.dataset.t)});
});
document.querySelectorAll('[data-goto]').forEach(function(b){
  b.addEventListener('click',function(){showSub(b.dataset.goto)});
});

/* Division Head: Return / Dispute opens a modal */
var rmodal=document.getElementById('return-modal');
document.querySelectorAll('#s-dh .btn.danger, #s-dh [data-return]').forEach(function(b){
  b.addEventListener('click',function(){
    var row=b.closest('tr');
    var ref=row&&row.querySelector('.ref')?row.querySelector('.ref').textContent:'PAN-2026-00347';
    var dispute=b.textContent.indexOf('Dispute')>-1;
    document.getElementById('rm-title').textContent=(dispute?'Dispute — ':'Return to Requestor — ')+ref;
    document.getElementById('rm-send').textContent=dispute?'Send back to HR Preparer':'Return to Requestor';
    rmodal.classList.add('on');
  });
});
rmodal.querySelectorAll('[data-close]').forEach(function(b){
  b.addEventListener('click',function(){rmodal.classList.remove('on')});
});
rmodal.addEventListener('click',function(e){if(e.target===rmodal)rmodal.classList.remove('on')});

/* HR Preparation: "Update PAN" modal (starts a new PAN directly from HR) */
var umodal=document.getElementById('update-modal');
document.querySelectorAll('[data-modal="update"]').forEach(function(b){
  b.addEventListener('click',function(){
    var row=b.closest('tr');
    if(row){
      var who=row.querySelector('.who');
      document.getElementById('um-title').textContent='Update PAN — '+who.querySelector('b').textContent
        +' ('+who.querySelector('small').textContent+')';
    }else{
      document.getElementById('um-title').textContent='Update PAN — S. Lim (EMP-10233)';
    }
    umodal.classList.add('on');
  });
});
umodal.querySelectorAll('[data-close]').forEach(function(b){
  b.addEventListener('click',function(){umodal.classList.remove('on')});
});
umodal.addEventListener('click',function(e){if(e.target===umodal)umodal.classList.remove('on')});

/* Admin: Add / Edit Employee modal */
var emodal=document.getElementById('emp-modal');
document.querySelectorAll('[data-modal="employee"]').forEach(function(b){
  b.addEventListener('click',function(){
    var row=b.closest('tr');
    document.getElementById('em-title').textContent=
      row?'Edit Employee — '+row.querySelector('.who b').textContent:'Add Employee';
    emodal.classList.add('on');
  });
});
emodal.querySelectorAll('[data-close]').forEach(function(b){
  b.addEventListener('click',function(){emodal.classList.remove('on')});
});
emodal.addEventListener('click',function(e){if(e.target===emodal)emodal.classList.remove('on')});
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){rmodal.classList.remove('on');umodal.classList.remove('on');emodal.classList.remove('on');dmodal.classList.remove('on')}
});

/* HR Preparation: tagging / lock simulation.
   Rules: untagged = locked for everyone; Tarlac = unlocked (normal preparer's job, HR Head may still open);
   Manila = locked for a normal preparer (in the real app they are redirected and lose View access),
   unlocked only for an HR Head Preparer. */
var simRole='normal';
var simTag=document.getElementById('sim-tag');
var roleBtns={normal:document.getElementById('sim-role-normal'),head:document.getElementById('sim-role-head')};
function simUpdate(){
  var tag=simTag.value,locked,cls,ic,msg;
  if(tag==='none'){
    locked=true;cls='note info';ic='i';
    msg='Locked — tag the request (Tarlac or Manila) before preparation can begin. Any preparer may apply the initial tag.';
  }else if(tag==='manila'&&simRole==='normal'){
    locked=true;cls='note manila';ic='●';
    msg='<b>Locked — tagged Manila.</b>&nbsp;Only an HR Head Preparer can view and prepare this request. In the actual project you are returned to the list and your View button becomes permanently inactive.';
  }else if(tag==='manila'){
    locked=false;cls='note manila';ic='●';
    msg='<b>Tagged Manila</b>&nbsp;— unlocked for you as HR Head Preparer. The DH Head (not the regular Division Head) will act at the confirmation stage.';
  }else if(simRole==='head'){
    locked=false;cls='note info';ic='i';
    msg='Tagged Tarlac (routine) — unlocked. By process a normal HR Preparer completes routine PANs, but as HR Head you can still view or edit it.';
  }else{
    locked=false;cls='note info';ic='i';
    msg='Tagged Tarlac (routine) — unlocked. Continue with Employment Details and the Action Reference.';
  }
  document.getElementById('prep-lockable').classList.toggle('locked',locked);
  var note=document.getElementById('prep-note');
  note.className=cls;
  note.innerHTML='<span class="ic">'+ic+'</span><span>'+msg+'</span>';
}
simTag.addEventListener('change',simUpdate);

/* Previous-PAN quick access: "See more" expands the inline summary.
   In the actual project the reference id navigates to that PAN's view instead. */
var prevDetail=document.getElementById('prev-detail');
var prevMore=document.getElementById('prev-more');
function togglePrev(){
  prevDetail.hidden=!prevDetail.hidden;
  prevMore.textContent=prevDetail.hidden?'See more':'See less';
}
prevMore.addEventListener('click',togglePrev);
document.getElementById('prev-ref').addEventListener('click',togglePrev);

/* Action Reference: typed "To" values highlight green; allowance rows are added/removed dynamically */
document.addEventListener('input',function(e){
  if(e.target.classList&&e.target.classList.contains('toin'))
    e.target.classList.toggle('filled',e.target.value.trim()!=='');
});
document.getElementById('allow-add').addEventListener('click',function(){
  var type=document.getElementById('allow-type').value;
  var tr=document.createElement('tr');
  tr.innerHTML='<td class="lbl">'+type+'</td><td class="num">—</td><td class="arrow">→</td>'
    +'<td class="num"><input class="toin numin" placeholder="₱ 0.00"></td>'
    +'<td><button class="rowdel" title="Remove allowance row">×</button></td>';
  document.getElementById('prep-ar-body').appendChild(tr);
  tr.querySelector('.toin').focus();
});
document.addEventListener('click',function(e){
  if(e.target.classList&&e.target.classList.contains('rowdel')&&!e.target.disabled){
    var row=e.target.closest('tr')||e.target.closest('.refrow');
    if(row)row.remove();
  }
});

/* Maintenance — Reference Values: add farm/department entries */
document.querySelectorAll('[data-addref]').forEach(function(b){
  b.addEventListener('click',function(){
    var input=b.parentElement.querySelector('.refin');
    var name=input.value.trim();
    if(!name)return;
    var row=document.createElement('div');
    row.className='refrow';
    row.innerHTML='<span></span><small>0 in use</small><button class="rowdel" title="Delete value">×</button>';
    row.querySelector('span').textContent=name;
    b.parentElement.parentElement.insertBefore(row,b.parentElement);
    input.value='';
    showToast('"'+name+'" added to reference values.');
  });
});

/* Maintenance — Backup & Restore */
document.getElementById('run-backup').addEventListener('click',function(){
  showToast('Manual backup started — it will appear in the list when complete.');
});

/* Maintenance — Danger Zone (adapted from data-wipe.html):
   pick a mode → optional date/year/quarter inputs → Preview Count → type the exact
   count (or RESTORE) in the confirm modal → job is queued and audited. */
var dstate={wipe:{mode:null,count:null},attach:{mode:null,count:null},dlog:{mode:null,count:null}};
var dnoun={wipe:'PAN records (with preparation details and attachments)',
  attach:'PANs — records kept, marked "Attachment Expired"',dlog:'log entries'};
document.querySelectorAll('.rgrid').forEach(function(g){
  var grp=g.dataset.group;
  g.querySelectorAll('.rcard').forEach(function(c){
    c.addEventListener('click',function(){
      g.querySelectorAll('.rcard').forEach(function(x){x.classList.remove('sel')});
      c.classList.add('sel');
      dstate[grp].mode=c.dataset.value;
      dstate[grp].count=null;
      document.getElementById(grp+'-badge').hidden=true;
      ['all','range','year','quarter'].forEach(function(m){
        var p=document.getElementById(grp+'-p-'+m);
        if(p)p.hidden=(m!==c.dataset.value);
      });
    });
  });
});
/* changing any date/year/quarter input invalidates a previous preview (as in data-wipe.html) */
document.querySelectorAll('[id*="-p-"] input,[id*="-p-"] select').forEach(function(el){
  el.addEventListener('input',function(){
    var grp=el.closest('[id*="-p-"]').id.split('-p-')[0];
    dstate[grp].count=null;
    document.getElementById(grp+'-badge').hidden=true;
  });
});
/* quarter select stays disabled until a year is chosen */
var ayear=document.getElementById('attach-year'),aq=document.getElementById('attach-quarter');
ayear.addEventListener('change',function(){
  aq.disabled=!ayear.value;
  if(!ayear.value)aq.value='';
});
document.querySelectorAll('[data-preview]').forEach(function(b){
  b.addEventListener('click',function(){
    var grp=b.dataset.preview;
    if(!dstate[grp].mode){showToast('Select a mode first.');return}
    var n=Math.floor(Math.random()*9000)+100;
    dstate[grp].count=n;
    var badge=document.getElementById(grp+'-badge');
    var lbl={wipe:' PAN records will be deleted',attach:' PANs will have attachments purged',dlog:' log entries will be deleted'};
    badge.querySelector('span').textContent=n.toLocaleString()+lbl[grp];
    badge.hidden=false;
  });
});
var dmodal=document.getElementById('danger-modal');
var dmInput=document.getElementById('dm-input');
var dmConfirm=document.getElementById('dm-confirm');
var dmRequired='',dmToast='';
function openDanger(title,msg,required,toastMsg,btnLabel){
  document.getElementById('dm-title').textContent=title;
  document.getElementById('dm-msg').innerHTML=msg;
  document.getElementById('dm-req').textContent=required;
  document.getElementById('dm-btn-label').textContent=btnLabel||'Confirm';
  dmRequired=required;dmToast=toastMsg;
  dmInput.value='';dmConfirm.disabled=true;
  dmodal.classList.add('on');
  setTimeout(function(){dmInput.focus()},50);
}
document.querySelectorAll('[data-danger]').forEach(function(b){
  b.addEventListener('click',function(){
    var grp=b.dataset.danger;
    if(grp==='restore'){
      openDanger('Confirm Data Restore',
        'This will <b>overwrite current data</b> with the uploaded backup’s contents. A safety backup is taken first. This cannot be undone.',
        'RESTORE','Restore job queued — the system will be briefly unavailable.','Queue Restore');
      return;
    }
    if(dstate[grp].count===null){showToast('Run Preview Count first.');return}
    var n=dstate[grp].count;
    var titles={wipe:'Confirm Permanent Deletion',attach:'Confirm Attachment Purge',dlog:'Confirm Activity Log Purge'};
    var btns={wipe:'Queue Deletion of '+n.toLocaleString()+' PANs',
      attach:'Queue Purge of '+n.toLocaleString()+' Attachments',
      dlog:'Queue Purge of '+n.toLocaleString()+' Entries'};
    openDanger(titles[grp],
      'This will permanently delete <b>'+n.toLocaleString()+'</b> '+dnoun[grp]+'. This action <b>cannot be undone</b>.',
      String(n),'Job queued — '+n.toLocaleString()+' '+ (grp==='attach'?'attachment purges':'deletions') +' will run in the background.',
      btns[grp]);
  });
});
dmInput.addEventListener('input',function(){dmConfirm.disabled=(dmInput.value!==dmRequired)});
dmConfirm.addEventListener('click',function(){
  dmodal.classList.remove('on');
  showToast(dmToast);
  Object.keys(dstate).forEach(function(k){dstate[k].count=null});
  document.querySelectorAll('.count-badge').forEach(function(el){el.hidden=true});
});
dmodal.querySelectorAll('[data-close]').forEach(function(b){
  b.addEventListener('click',function(){dmodal.classList.remove('on')});
});
dmodal.addEventListener('click',function(e){if(e.target===dmodal)dmodal.classList.remove('on')});

/* kebab (⋯) row menus: one open at a time; close on outside click, item click, or Escape */
document.querySelectorAll('.kbtn').forEach(function(b){
  b.addEventListener('click',function(e){
    e.stopPropagation();
    var k=b.parentElement,wasOpen=k.classList.contains('open');
    document.querySelectorAll('.kebab.open').forEach(function(x){x.classList.remove('open')});
    if(!wasOpen)k.classList.add('open');
  });
});
document.addEventListener('click',function(e){
  if(!e.target.closest('.kbtn'))
    document.querySelectorAll('.kebab.open').forEach(function(x){x.classList.remove('open')});
});
document.addEventListener('keydown',function(e){
  if(e.key==='Escape')document.querySelectorAll('.kebab.open').forEach(function(x){x.classList.remove('open')});
});

var toastTimer;
function showToast(msg){
  var t=document.getElementById('toast');
  document.getElementById('toast-msg').textContent=msg;
  t.classList.add('on');
  clearTimeout(toastTimer);
  toastTimer=setTimeout(function(){t.classList.remove('on')},3500);
}
['normal','head'].forEach(function(r){
  roleBtns[r].addEventListener('click',function(){
    simRole=r;
    roleBtns.normal.classList.toggle('on',r==='normal');
    roleBtns.head.classList.toggle('on',r==='head');
    simUpdate();
  });
});
document.querySelectorAll('.navbtn').forEach(function(b){
  b.addEventListener('click',function(){
    document.querySelectorAll('.navbtn').forEach(function(x){x.classList.remove('active')});
    document.querySelectorAll('.screen').forEach(function(s){s.classList.remove('on')});
    b.classList.add('active');
    var sec=document.getElementById('s-'+b.dataset.s);
    sec.classList.add('on');
    var firstTab=sec.querySelector('.stab');
    if(firstTab)showSub(firstTab.dataset.t);
    window.scrollTo(0,0);
  });
  
});
document.querySelectorAll('.tog').forEach(function(t){
  t.addEventListener('click',function(){t.classList.toggle('on');t.setAttribute('aria-checked',t.classList.contains('on'))});
  t.addEventListener('keydown',function(e){if(e.key===' '||e.key==='Enter'){e.preventDefault();t.click()}});
});