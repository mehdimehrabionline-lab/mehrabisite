/* ==========================================================
   هنرجویان: فهرست، پروفایل کامل، دستگاه‌ها، یادداشت، ادغام حساب‌ها
   (هرگز password_hash خوانده نمی‌شود)
   ========================================================== */
(function(){
'use strict';
const { html, esc, $ } = A;
const COLS = 'id,full_name,username,phone,email,google_email,age,job,goal,how_found,created_at,last_login,is_verified';
const PS = 25;

A.route('students', { title:a=>a[0]?'پروفایل هنرجو':'هنرجویان', nav:'students', async render(box, args){
  if(args[0]) return profile(box, args[0], args[1]);
  const st = A.state.stu = A.state.stu || { q:'', f:'', sort:'new', page:1 };
  const total = await A.count('academy_users');
  A.mount(box, html`
    <div class="toolbar">
      <input class="in sm" id="sq" style="max-width:300px" placeholder="جستجو: موبایل، نام، ایمیل، یوزرنیم…" value="${st.q}">
      <select class="in sm" id="sf" style="width:160px"><option value="">همه‌ی هنرجوها</option><option value="complete">پروفایل کامل</option><option value="incomplete">پروفایل ناقص</option></select>
      <select class="in sm" id="so" style="width:160px"><option value="new">جدیدترین عضویت</option><option value="old">قدیمی‌ترین</option><option value="login">آخرین ورود</option></select>
      <span class="muted" id="stuTotal">${total==null?'':A.fa(total)+' هنرجو'}</span>
      <div class="sp"></div>
      <button class="btn" data-act="stu-export">${A.icon('download')} Excel</button>
      <button class="btn primary" data-act="grant">${A.icon('key')} اعطای دسترسی</button>
    </div><div id="stuList"></div>`);
  $('#sf').value = st.f; $('#so').value = st.sort;
  const apply = ()=>{ st.q=$('#sq').value.trim(); st.f=$('#sf').value; st.sort=$('#so').value; st.page=1; list(); };
  $('#sq').oninput = A.debounce(apply,350); $('#sf').onchange = apply; $('#so').onchange = apply;
  A.handlers.page = el => { st.page=+el.dataset.p; list(); };
  const build = (q) => {
    if(st.q){ const v=A.toEn(st.q).replace(/[%,()]/g,''); q=q.or(`phone.ilike.%${v}%,full_name.ilike.%${v}%,email.ilike.%${v}%,username.ilike.%${v}%`); }
    if(st.f==='complete') q=q.not('full_name','is',null).not('email','is',null); else if(st.f==='incomplete') q=q.or('full_name.is.null,email.is.null');
    return st.sort==='old' ? q.order('created_at',{ascending:true}) : st.sort==='login' ? q.order('last_login',{ascending:false,nullsFirst:false}) : q.order('created_at',{ascending:false});
  };
  async function list(){
    const out = $('#stuList'); out.innerHTML='<div class="loading"><span class="spin"></span></div>';
    const r = await build(sb.from('academy_users').select(COLS,{count:'exact'})).range((st.page-1)*PS, st.page*PS-1); if(r.error) throw r.error;
    const rows = r.data||[]; const cnt = {};
    if(rows.length){ const ar = await sb.from('course_access').select('user_id').in('user_id',rows.map(x=>x.id)).neq('is_active',false); (ar.data||[]).forEach(a=>cnt[a.user_id]=(cnt[a.user_id]||0)+1); }
    A.mount(out, html`${A.table([
      {h:'هنرجو', c:u=>A.userLink(u)},
      {h:'ایمیل', c:u=>html`<span style="font-size:12.5px">${u.email||u.google_email||'—'}</span>`},
      {h:'دوره‌ها', c:u=>cnt[u.id]?A.badge(A.fa(cnt[u.id])+' دوره','ok'):A.badge('بدون دوره','mute')},
      {h:'پروفایل', c:u=>(u.full_name&&u.email)?A.badge('کامل','ok'):A.badge('ناقص','warn')},
      {h:'عضویت', c:u=>A.date(u.created_at)},
      {h:'آخرین ورود', c:u=>u.last_login?A.ago(u.last_login):'—'},
    ], rows, {row:'stu-open', empty:'هنرجویی پیدا نشد'})}${A.pager(r.count||0, st.page, PS)}`);
    $('#stuTotal').textContent = A.fa(r.count||0)+' هنرجو';
  }
  list();
  A.handlers['stu-export'] = async () => {
    const rows = await A.fetchAll(()=>build(sb.from('academy_users').select(COLS)));
    A.csv('students-'+A.dayKey(Date.now())+'.csv',['نام','یوزرنیم','موبایل','ایمیل','سن','شغل','هدف','آشنایی','وضعیت','تاریخ عضویت','آخرین ورود'],
      rows.map(u=>[u.full_name,u.username,u.phone,u.email,u.age,u.job,u.goal,u.how_found,(u.full_name&&u.email)?'کامل':'ناقص',A.dt(u.created_at),u.last_login?A.dt(u.last_login):'']));
  };
}});
A.on('stu-open', el => A.go('students/'+el.dataset.id));

/* ---------- پروفایل ---------- */
async function profile(box, id, tab){
  tab = tab || 'courses';
  const u = await A.q(sb.from('academy_users').select(COLS).eq('id',id).maybeSingle());
  if(!u){ A.mount(box, html`<div class="alert err">هنرجو پیدا نشد (شاید ادغام یا حذف شده).</div><a class="btn" href="#/students">بازگشت</a>`); return; }
  const [acc, pays] = await Promise.all([
    sb.from('course_access').select('id,course_id,granted_at,is_active,payment_id').eq('user_id',id).order('granted_at',{ascending:false}),
    sb.from('payments').select('*').eq('user_id',id).order('created_at',{ascending:false}) ]);
  const cs = await A.courses(); const cm = Object.fromEntries(cs.map(c=>[c.id,c]));
  const owned = (acc.data||[]).filter(a=>a.is_active!==false);
  const paidSum = (pays.data||[]).filter(p=>p.status==='paid').reduce((s,p)=>s+p.amount,0);
  const tabs = [['courses','دوره‌ها',owned.length],['pays','پرداخت‌ها',(pays.data||[]).length],['devices','دستگاه‌ها'],['notes','یادداشت‌ها'],['info','اطلاعات و ویرایش']];
  A.mount(box, html`
    <div class="page-h"><a class="btn ghost" href="#/students">‹ هنرجویان</a></div>
    <div class="card" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
      <div class="avatar lg">${A.initial(A.userName(u))}</div>
      <div style="flex:1;min-width:200px"><div style="font-size:20px;font-weight:600">${A.userName(u)}</div>
        <div class="muted"><span class="mono">${u.phone||'بدون شماره'}</span> · ${u.email||u.google_email||'بدون ایمیل'}</div>
        <div class="muted" style="font-size:12px;margin-top:2px">عضویت: ${A.date(u.created_at)} · آخرین ورود: ${u.last_login?A.ago(u.last_login):'—'}</div></div>
      <div style="text-align:left"><div class="muted" style="font-size:12px">مجموع خرید</div><b style="font-size:20px;color:var(--accent)">${A.money(paidSum)}</b></div>
      <button class="btn primary" data-act="grant" data-phone="${u.phone||''}">${A.icon('key')} اعطای دوره</button>
    </div>
    <div class="tabs">${tabs.map(t=>html`<a class="tab ${t[0]===tab?'active':''}" href="#/students/${id}/${t[0]}">${t[1]}${t[2]?html` <span class="badge b-mute">${A.fa(t[2])}</span>`:''}</a>`)}</div>
    <div id="stuTab"></div>`);
  const out = $('#stuTab');
  if(tab==='courses'){
    A.mount(out, A.table([
      {h:'دوره', c:a=>cm[a.course_id]?.title||'—'},{h:'منبع', c:a=>a.payment_id?A.badge('خرید آنلاین','ok'):A.badge('اعطای دستی','acc')},
      {h:'تاریخ', c:a=>A.date(a.granted_at)},{h:'وضعیت', c:a=>a.is_active===false?A.badge('لغو شده','err'):A.badge('فعال','ok')},
      {h:'', c:a=>a.is_active===false?html`<button class="btn sm" data-act="acc-on" data-id="${a.id}">فعال‌سازی</button>`:html`<button class="btn sm danger" data-act="acc-off" data-id="${a.id}">لغو دسترسی</button>`} ], acc.data||[], {empty:'هنوز دوره‌ای ندارد'}));
  } else if(tab==='pays'){
    A.state.payRows = Object.fromEntries((pays.data||[]).map(p=>[p.id,{p,u,c:cm[p.course_id]}]));
    A.mount(out, A.table([
      {h:'تاریخ', c:p=>A.dt(p.paid_at||p.created_at)},{h:'دوره', c:p=>cm[p.course_id]?.title||'—'},{h:'مبلغ', c:p=>A.money(p.amount), cls:'num'},
      {h:'وضعیت', c:p=>A.payBadge(p.status)},{h:'کد رهگیری', c:p=>html`<span class="mono">${p.track_id||'—'}</span>`} ], pays.data||[], {row:'pay-open', empty:'پرداختی ثبت نشده'}));
  } else if(tab==='devices') await devices(out, u);
  else if(tab==='notes') await notes(out, u);
  else info(out, u);
}

async function devices(out, u){
  let r = await sb.from('user_sessions').select('id,created_at,last_seen_at,expires_at,revoked').eq('user_id',u.id).order('last_seen_at',{ascending:false}).limit(50);
  if(r.error && A.noCol(r.error)) r = await sb.from('user_sessions').select('id,last_seen_at,expires_at,revoked').eq('user_id',u.id).order('last_seen_at',{ascending:false}).limit(50);
  if(r.error){ A.mount(out, html`<div class="alert warn">خواندن دستگاه‌ها ممکن نشد: ${A.errMsg(r.error)} — فایل admin-upgrade.sql را اجرا کن.</div>`); return; }
  const now = Date.now(); const act = (r.data||[]).filter(s=>!s.revoked && new Date(s.expires_at)>now);
  A.mount(out, html`<div class="toolbar"><span class="muted">حداکثر ۲ دستگاه هم‌زمان مجاز است؛ ورود از دستگاه سوم قدیمی‌ترین را خارج می‌کند.</span><div class="sp"></div>
      ${act.length?html`<button class="btn danger" data-act="dev-all" data-id="${u.id}">خروج از همه‌ی دستگاه‌ها</button>`:''}</div>
    ${A.table([{h:'آخرین فعالیت', c:s=>A.ago(s.last_seen_at)},{h:'ورود', c:s=>s.created_at?A.dt(s.created_at):'—'},{h:'انقضا', c:s=>A.date(s.expires_at)},
      {h:'وضعیت', c:s=>(!s.revoked&&new Date(s.expires_at)>now)?A.badge('فعال','ok'):A.badge(s.revoked?'خارج‌شده':'منقضی','mute')},
      {h:'', c:s=>(!s.revoked&&new Date(s.expires_at)>now)?html`<button class="btn sm danger" data-act="dev-one" data-id="${s.id}">خروج</button>`:''}], r.data||[], {empty:'نشستی ثبت نشده'})}`);
}
A.on('dev-one', async el => { await A.q(sb.from('user_sessions').update({revoked:true}).eq('id',el.dataset.id)); A.audit('revoke_session','user_sessions',el.dataset.id,{}); A.toast('دستگاه خارج شد','ok'); A.rerender(); });
A.on('dev-all', async el => {
  if(!await A.confirm({title:'خروج از همه‌ی دستگاه‌ها',message:'هنرجو از همه‌ی دستگاه‌ها خارج می‌شود و باید دوباره با کد پیامکی وارد شود.',danger:true,confirm:'خروج از همه'})) return;
  await A.q(sb.from('user_sessions').update({revoked:true}).eq('user_id',el.dataset.id).eq('revoked',false)); A.audit('revoke_all_sessions','academy_users',el.dataset.id,{}); A.toast('همه‌ی دستگاه‌ها خارج شدند','ok'); A.rerender();
});

async function notes(out, u){
  const r = await sb.from('student_notes').select('*').eq('user_id',u.id).order('created_at',{ascending:false});
  if(r.error){ A.mount(out, html`<div class="alert warn">${A.missing(r.error)?'جدول یادداشت‌ها هنوز ساخته نشده؛ فایل admin-upgrade.sql را اجرا کن.':A.errMsg(r.error)}</div>`); return; }
  A.mount(out, html`<div class="card"><div class="fld"><textarea class="in" id="nTxt" placeholder="یادداشت داخلی (فقط خودت می‌بینی): مثلاً «تماس گرفت، مشکل پخش داشت»"></textarea></div><button class="btn primary" id="nAdd">افزودن یادداشت</button></div>
    ${(r.data||[]).map(n=>html`<div class="card" style="padding:14px 18px"><div style="white-space:pre-wrap">${n.note}</div><div class="muted" style="font-size:11.5px;margin-top:8px">${A.dt(n.created_at)} · ${n.admin_email||''} <button class="btn ghost sm" data-act="note-del" data-id="${n.id}" style="margin-right:10px">حذف</button></div></div>`)}`);
  $('#nAdd').onclick = async ()=>{ const v=$('#nTxt').value.trim(); if(!v) return; await A.q(sb.from('student_notes').insert([{user_id:u.id,note:v,admin_email:A.user.email}])); A.rerender(); };
}
A.on('note-del', async el => { if(!await A.confirm({title:'حذف یادداشت',message:'این یادداشت حذف شود؟',danger:true})) return; await A.q(sb.from('student_notes').delete().eq('id',el.dataset.id)); A.rerender(); });

function info(out, u){
  const rows = [['موبایل',u.phone],['ایمیل',u.email],['ایمیل گوگل',u.google_email],['نام کاربری',u.username],['سن',u.age],['شغل',u.job],['هدف از یادگیری',u.goal],['نحوه‌ی آشنایی',u.how_found],['تأیید موبایل',u.is_verified?'بله':'خیر']];
  A.mount(out, html`<div class="grid g2" style="align-items:start">
    <div class="card"><h3>اطلاعات ثبت‌نامی</h3><dl class="kv">${rows.map(r=>html`<dt>${r[0]}</dt><dd>${r[1]==null||r[1]===''?'—':r[1]}</dd>`)}</dl></div>
    <div class="card"><h3>ویرایش</h3>
      <div class="fld"><label class="lb">نام و نام خانوادگی</label><input class="in" id="eName" value="${u.full_name||''}"></div>
      <div class="fld"><label class="lb">موبایل</label><input class="in ltr" id="ePhone" value="${u.phone||''}" maxlength="11"></div>
      <div class="fld"><label class="lb">ایمیل</label><input class="in ltr" id="eMail" value="${u.email||''}"></div>
      <button class="btn primary" id="eSave">ذخیره</button>
      <hr style="border:0;border-top:1px solid var(--line);margin:20px 0">
      <h3 style="margin-bottom:8px">ادغام حساب تکراری</h3>
      <p class="hint">اگر این هنرجو دو حساب دارد (مثلاً یکی با شماره و یکی با گوگل)، این حساب در حساب دیگری ادغام می‌شود: دوره‌ها و پرداخت‌ها منتقل و این حساب حذف می‌شود.</p>
      <button class="btn danger" id="eMerge">${A.icon('merge')} ادغام در حساب دیگر…</button></div></div>`);
  $('#eSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{
    const phone = A.toEn($('#ePhone').value).trim(), email=$('#eMail').value.trim(), name=$('#eName').value.trim();
    if(phone && !/^09\d{9}$/.test(phone)){ A.toast('شماره موبایل معتبر نیست','err'); return; }
    if(phone && phone!==u.phone){ const d = await A.q(sb.from('academy_users').select('id').eq('phone',phone).neq('id',u.id).limit(1)); if(d.length){ A.toast('این شماره برای هنرجوی دیگری ثبت است — از «ادغام» استفاده کن','err'); return; } }
    await A.q(sb.from('academy_users').update({full_name:name||null,phone:phone||null,email:email||null}).eq('id',u.id));
    A.audit('edit_student','academy_users',u.id,{from:{phone:u.phone,email:u.email,name:u.full_name},to:{phone,email,name}}); A.toast('ذخیره شد ✓','ok'); A.rerender();
  });
  $('#eMerge').onclick = async ()=>{
    const ph = await A.prompt({title:'ادغام حساب',label:'شماره موبایل حسابِ مقصد (حسابی که باقی می‌ماند):',confirm:'ادامه',placeholder:'09123456789'}); if(!ph) return;
    const p = A.toEn(ph).trim(); const t = await A.q(sb.from('academy_users').select('id,full_name,phone').eq('phone',p).maybeSingle());
    if(!t){ A.toast('حسابی با این شماره پیدا نشد','err'); return; } if(t.id===u.id){ A.toast('همان حساب است','err'); return; }
    if(!await A.confirm({title:'تأیید ادغام',danger:true,confirm:'ادغام کن',message:`حساب «${A.userName(u)}» (${u.phone||u.email||''}) در حساب «${A.userName(t)}» (${t.phone}) ادغام شود؟\nدوره‌ها و پرداخت‌ها منتقل و این حساب برای همیشه حذف می‌شود.`})) return;
    const mine = await A.q(sb.from('course_access').select('id,course_id').eq('user_id',u.id)); const theirs = await A.q(sb.from('course_access').select('course_id').eq('user_id',t.id)); const have = new Set(theirs.map(x=>x.course_id));
    for(const a of mine){ if(have.has(a.course_id)) await A.q(sb.from('course_access').delete().eq('id',a.id)); else await A.q(sb.from('course_access').update({user_id:t.id}).eq('id',a.id)); }
    await A.q(sb.from('payments').update({user_id:t.id}).eq('user_id',u.id));
    await sb.from('user_sessions').update({revoked:true}).eq('user_id',u.id);
    await A.q(sb.from('academy_users').delete().eq('id',u.id));
    A.audit('merge_students','academy_users',u.id,{into:t.id}); A.toast('ادغام شد ✓','ok'); A.go('students/'+t.id);
  };
}
})();
