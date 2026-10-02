/* ==========================================================
   سیستم: وضعیت سلامت، کتابخانه‌ی فایل، گزارش فعالیت، تنظیمات و امنیت
   ========================================================== */
(function(){
'use strict';
const { html, esc, $, $$ } = A;
const UPLOAD_HOST = window.MM_UPLOAD_HOST !== undefined ? window.MM_UPLOAD_HOST : 'https://upload.mehdimehrabi.ir';
async function tok(){ const { data:{ session } } = await sb.auth.getSession(); return session?.access_token||''; }
async function hostApi(action, post){
  const o = { headers:{ Authorization:'Bearer '+await tok() } };
  if(post){ o.method='POST'; const f=new FormData(); Object.entries(post).forEach(([k,v])=>f.append(k,v)); o.body=f; }
  const r = await fetch('/admin-files.php?action='+action, o);
  const t = await r.text(); try{ return JSON.parse(t); }catch(e){ throw new Error(r.status===404?'فایل admin-files.php روی هاست نیست':'پاسخ نامعتبر از admin-files.php'); }
}

/* ====================== وضعیت سیستم ====================== */
const TABLES = [['payments','تراکنش‌ها (فایل SQL را اجرا کن)'],['user_sessions','نشست‌های ورود هنرجوها'],['student_notes','یادداشت هنرجو'],['admin_audit_log','گزارش فعالیت'],['admin_roles','نقش‌ها'],['course_access','دسترسی دوره‌ها'],['course_videos','جلسات'],['course_attachments','پیوست‌ها']];
A.route('system', { title:'وضعیت سیستم', async render(box){
  A.mount(box, html`<div class="toolbar"><div class="sp"></div><button class="btn" data-act="reload">${A.icon('refresh')} بررسی دوباره</button></div><div id="sysBody"><div class="loading"><span class="spin"></span> در حال بررسی…</div></div>`);
  const out = []; const row = (name, ok, detail, kind) => out.push({ name, ok, detail, kind });
  // دیتابیس
  const t0 = performance.now(); const pr = await sb.from('site_content').select('key',{count:'exact',head:true}); const ms = Math.round(performance.now()-t0);
  row('اتصال به دیتابیس (Supabase)', !pr.error, pr.error?A.errMsg(pr.error):`پاسخ در ${A.fa(ms)} میلی‌ثانیه`+(ms>1500?' — کند است':''), ms>1500?'warn':null);
  const { data:{ session } } = await sb.auth.getSession(); row('نشست ورود مدیر', !!session, session?'معتبر تا '+A.dt(session.expires_at*1000):'منقضی');
  // جدول‌ها
  const tbl = [];
  for(const [t,d] of TABLES){ const r = await sb.from(t).select('*',{count:'exact',head:true}); tbl.push({t,d,ok:!r.error,n:r.count,err:r.error}); }
  // هاست
  let h = null, herr = null; try{ h = await hostApi('health'); if(!h.ok){ herr=h.error; h=null; } }catch(e){ herr=A.errMsg(e); }
  // نقطه‌های آپلود
  const ups = [];
  for(const [lbl,url] of [['آپلود ویدیو (ساب‌دامین)',UPLOAD_HOST+'/upload-video.php'],['آپلود تکه‌ای (ساب‌دامین)',UPLOAD_HOST+'/admin-chunk-upload.php'],['آپلود تکه‌ای (خود سایت)','/admin-chunk-upload.php'],['ارسال پیامک اعطای دسترسی','/send-grant-sms.php'],['بررسی پرداخت','/verify.php']]){
    try{ const r = await fetch(url,{method:'OPTIONS'}); ups.push({lbl, ok:r.status<400||r.status===405, d:'HTTP '+r.status}); }catch(e){ ups.push({lbl,ok:false,d:'در دسترس نیست'}); }
  }
  const b = $('#sysBody'); if(!b) return;
  const fmtAge = s => s==null?'—':s<90?A.fa(s)+' ثانیه':s<5400?A.fa(Math.round(s/60))+' دقیقه':A.fa(Math.round(s/3600))+' ساعت';
  A.mount(b, html`
    <div class="grid g2" style="align-items:start">
      <div class="card"><h3>اتصال‌ها</h3>${out.map(o=>html`<div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)"><span>${o.ok?A.badge('سالم',o.kind||'ok'):A.badge('مشکل','err')}</span><b style="font-weight:500">${o.name}</b><span class="muted" style="margin-right:auto;font-size:12.5px">${o.detail}</span></div>`)}
        ${ups.map(o=>html`<div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)"><span>${o.ok?A.badge('در دسترس','ok'):A.badge('نیست','err')}</span><b style="font-weight:500">${o.lbl}</b><span class="muted" style="margin-right:auto;font-size:12.5px">${o.d}</span></div>`)}</div>
      <div class="card"><h3>جدول‌های دیتابیس</h3>${tbl.map(x=>html`<div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)"><span>${x.ok?A.badge('هست','ok'):A.badge('ندارد',/payments|user_sessions/.test(x.t)?'err':'warn')}</span><b style="font-weight:500" class="mono">${x.t}</b><span class="muted" style="margin-right:auto;font-size:12.5px">${x.ok?A.fa(x.n||0)+' ردیف':x.d}</span></div>`)}
        ${tbl.some(x=>!x.ok)?html`<div class="alert warn" style="margin-top:12px">${A.icon('alert')}<div>برای ساخت جدول‌های جاافتاده فایل <b>admin-upgrade.sql</b> را در Supabase ← SQL Editor اجرا کن (چند ثانیه، بی‌خطر؛ چیزی را حذف نمی‌کند).</div></div>`:''}</div></div>
    <div class="grid g2" style="align-items:start">
      <div class="card"><h3>سرور هاست</h3>${h?html`<dl class="kv"><dt>PHP</dt><dd class="mono">${h.php}</dd><dt>سرور</dt><dd class="mono">${h.server}</dd><dt>حافظه‌ی PHP</dt><dd class="mono">${h.memory_limit}</dd><dt>سقف آپلود</dt><dd class="mono">${h.upload_max} / ${h.post_max}</dd>
        <dt>فضای دیسک</dt><dd>${A.bytes(h.disk_free)} آزاد از ${A.bytes(h.disk_total)} ${h.disk_total&&h.disk_free/h.disk_total<0.1?A.badge('کم است','err'):''}</dd><dt>بار سرور</dt><dd class="mono">${h.load?h.load.map(x=>x.toFixed(1)).join(' / '):'—'} ${h.load&&h.load[0]>20?A.badge('بالا','warn'):''}</dd>
        <dt>پوشه‌ی ویدیوها</dt><dd>${h.video_dir_ok?A.badge('قابل نوشتن','ok'):A.badge('مشکل','err')} <span class="muted">${A.fa(h.video_count)} فایل</span></dd><dt>پوشه‌ی کش</dt><dd>${h.cache_dir_ok?A.badge('قابل نوشتن','ok'):A.badge('مشکل','err')}</dd></dl>`:html`<div class="alert warn">${esc(herr||'نامشخص')}<br>فایل <span class="mono">admin-files.php</span> را روی هاست آپلود کن.</div>`}</div>
      <div class="card"><h3>کش سایت <span class="sp"></span><button class="btn sm primary" data-act="cache-refresh">${A.icon('refresh')} تازه‌سازی همه</button></h3><p class="hint">سایت داده‌ها را ۶۰ ثانیه کش می‌کند. بعد از هر ذخیره‌ی مهم پنل خودش کش را تازه می‌کند؛ اگر چیزی دیر نشست این دکمه را بزن.</p>
        ${h?Object.entries(h.cache_age).map(([k,v])=>html`<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)"><span class="mono">${k}</span><span class="muted">${v==null?'ساخته نشده':fmtAge(v)+' پیش'}</span></div>`):''}</div></div>
    <div class="card"><h3>اعلان تلگرام</h3><p class="hint">با هر خرید موفق، verify.php به تو در تلگرام خبر می‌دهد (از طریق تابع notify-admin در Supabase).</p><button class="btn" data-act="tg-test">${A.icon('mail')} ارسال پیام آزمایشی به تلگرام</button> <span id="tgRes" class="muted" style="margin-right:10px"></span></div>`);
}});
A.on('tg-test', async el=>{ const r = await A.busy(el, async ()=>{ try{ const x = await fetch(SUPABASE_URL+'/functions/v1/notify-admin',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({text:'✅ پیام آزمایشی از پنل مدیریت'})}); return x.ok; }catch(e){ return false; } }); $('#tgRes').innerHTML = r?'<span class="ok">ارسال شد؛ تلگرامت را نگاه کن ✓</span>':'<span class="err">ارسال نشد</span>'; });

/* ====================== کتابخانه‌ی فایل ====================== */
const IMG_FOLDERS = ['courses','work','ai','testimonials','blog'];
A.route('media', { title:'کتابخانه‌ی فایل', async render(box, args){
  const tab = args[0]||'host';
  A.mount(box, html`<div class="tabs"><a class="tab ${tab==='host'?'active':''}" href="#/media/host">ویدیوها و پیوست‌ها (هاست)</a><a class="tab ${tab==='img'?'active':''}" href="#/media/img">تصاویر (Supabase)</a></div><div id="mbody"><div class="loading"><span class="spin"></span></div></div>`);
  return tab==='img' ? images($('#mbody')) : hostFiles($('#mbody'));
}});
async function hostFiles(b){
  let d; try{ d = await hostApi('list'); if(!d.ok) throw new Error(d.error); }catch(e){ A.mount(b, html`<div class="alert err">${esc(A.errMsg(e))}</div>`); return; }
  const [vids, atts, courses] = await Promise.all([ sb.from('course_videos').select('id,title,file_name,course_id'), sb.from('course_attachments').select('id,title,file_name,video_id'), sb.from('courses').select('id,title,intro_video_file') ]);
  const used = {}; const ct = Object.fromEntries((courses.data||[]).map(c=>[c.id,c.title]));
  (vids.data||[]).forEach(v=>{ used[v.file_name]=`جلسه «${v.title}» — ${ct[v.course_id]||''}`; }); (atts.data||[]).forEach(a=>{ used[a.file_name]=`پیوست «${a.title}»`; });
  const introUsed = {}; (courses.data||[]).forEach(c=>{ if(c.intro_video_file) introUsed[c.intro_video_file]=`ویدیوی معرفی «${c.title}»`; });
  const files = d.videos.map(f=>({...f,kind:'video',use:used[f.name]})).concat(d.intro.map(f=>({...f,kind:'intro',use:introUsed[f.name]})));
  const total = files.reduce((s,f)=>s+f.size,0), orphan = files.filter(f=>!f.use);
  A.state.hostFiles = files;
  A.mount(b, html`<div class="grid g4 keep2" style="margin-bottom:16px"><div class="kpi"><div class="l">تعداد فایل</div><div class="v">${A.fa(files.length)}</div></div><div class="kpi"><div class="l">حجم کل</div><div class="v">${A.bytes(total)}</div></div>
    <div class="kpi ${orphan.length?'warn':''}"><div class="l">بدون استفاده</div><div class="v">${A.fa(orphan.length)}</div><div class="s">${A.bytes(orphan.reduce((s,f)=>s+f.size,0))}</div></div><div class="kpi"><div class="l">فضای آزاد دیسک</div><div class="v">${A.bytes(d.disk_free)}</div><div class="s">از ${A.bytes(d.disk_total)}</div></div></div>
    ${d.tmp_bytes>0?html`<div class="alert info">${A.icon('upload')}<div>${A.bytes(d.tmp_bytes)} آپلود نیمه‌کاره (تکه‌ای) روی هاست است؛ خودکار بعد از ۲۴ ساعت پاک می‌شود.</div></div>`:''}
    ${A.table([{h:'فایل',c:f=>html`<span class="mono">${f.name}</span> ${f.kind==='intro'?A.badge('معرفی','info'):''}`},{h:'حجم',c:f=>A.bytes(f.size),cls:'num'},{h:'تاریخ',c:f=>A.date(f.mtime*1000)},{h:'استفاده‌شده در',c:f=>f.use?f.use:A.badge('بدون استفاده','warn')},
      {h:'',c:f=>f.use?'':html`<button class="btn sm danger" data-act="hf-del" data-n="${f.name}" data-k="${f.kind}">${A.icon('trash')} حذف</button>`}], files, {empty:'فایلی روی هاست نیست'})}
    <p class="muted" style="font-size:12px;margin-top:10px">فقط فایل‌هایی که در هیچ جلسه/پیوست/ویدیوی معرفی استفاده نشده‌اند قابل حذف‌اند. فایلی که از قبل با FTP گذاشته‌ای و هنوز به جلسه‌ای وصل نکرده‌ای هم «بدون استفاده» نشان داده می‌شود.</p>`);
}
A.on('hf-del', async el=>{
  if(!await A.confirm({title:'حذف فایل از هاست',message:`فایل ${el.dataset.n} برای همیشه از هاست پاک شود؟ این کار برگشت ندارد.`,danger:true,confirm:'حذف دائمی'})) return;
  const r = await hostApi('delete',{file:el.dataset.n,kind:el.dataset.k}); if(!r.ok){ A.toast(r.error||'حذف نشد','err'); return; } A.audit('host_file_delete','file',el.dataset.n); A.toast('حذف شد'); A.rerender();
});
async function images(b){
  const bucket = sb.storage.from('media'); const all = [];
  for(const f of IMG_FOLDERS){ const r = await bucket.list(f,{limit:1000,sortBy:{column:'created_at',order:'desc'}}); if(!r.error) (r.data||[]).filter(x=>x.id).forEach(x=>all.push({path:`${f}/${x.name}`,folder:f,size:x.metadata?.size||0,at:x.created_at,url:bucket.getPublicUrl(`${f}/${x.name}`).data.publicUrl})); }
  const [c,p,t,po,sc] = await Promise.all([ sb.from('courses').select('cover_url'), sb.from('projects').select('media_url'), sb.from('testimonials').select('avatar_url'), sb.from('posts').select('cover_url,content,content_en'), sb.from('site_content').select('value').like('value','%/media/%') ]);
  const blob = JSON.stringify([c.data,p.data,t.data,po.data,sc.data]);
  all.forEach(f=>{ f.used = blob.includes(f.path); });
  const orphan = all.filter(f=>!f.used); A.state.imgs = all;
  A.mount(b, html`<div class="grid g4 keep2" style="margin-bottom:16px"><div class="kpi"><div class="l">تعداد تصویر</div><div class="v">${A.fa(all.length)}</div></div><div class="kpi"><div class="l">حجم کل</div><div class="v">${A.bytes(all.reduce((s,f)=>s+f.size,0))}</div></div><div class="kpi ${orphan.length?'warn':''}"><div class="l">بدون استفاده</div><div class="v">${A.fa(orphan.length)}</div><div class="s">${A.bytes(orphan.reduce((s,f)=>s+f.size,0))}</div></div></div>
    ${orphan.length?html`<div class="toolbar"><button class="btn danger" data-act="img-clean">${A.icon('trash')} پاک‌کردن همه‌ی ${A.fa(orphan.length)} تصویر بدون استفاده</button></div>`:''}
    <div class="mgrid">${all.map(f=>html`<div class="mitem"><div class="im" style="background-image:url('${f.url}')"></div><div class="m"><div class="mono muted" style="font-size:10.5px">${f.folder}</div><div>${A.bytes(f.size)}</div>${f.used?A.badge('استفاده‌شده','ok'):A.badge('بدون استفاده','warn')}</div></div>`)}</div>`);
}
A.on('img-clean', async ()=>{
  const orphan = A.state.imgs.filter(f=>!f.used);
  if(!await A.confirm({title:'پاک‌کردن تصاویر بدون استفاده',message:`${A.fa(orphan.length)} تصویر که در هیچ دوره/پست/نمونه‌کار/نظری استفاده نشده پاک شود؟\n(اگر تصویری را داخل متن پست با آدرس دستی گذاشته‌ای، قبلش آن را بررسی کن.)`,danger:true,confirm:'پاک کن'})) return;
  const r = await sb.storage.from('media').remove(orphan.map(f=>f.path)); if(r.error){ A.toast(A.errMsg(r.error),'err'); return; } A.audit('media_clean','storage',null,{n:orphan.length}); A.toast('پاک شد ✓','ok'); A.rerender();
});

/* ====================== گزارش فعالیت ====================== */
const AP = 30;
A.route('audit', { title:'گزارش فعالیت‌ها', async render(box){
  const st = A.state.aud = A.state.aud || { q:'', page:1 };
  A.mount(box, html`<div class="toolbar"><input class="in sm" id="auq" style="max-width:260px" placeholder="جستجو در نوع عملیات…" value="${st.q}"><div class="sp"></div><button class="btn" data-act="aud-export">${A.icon('download')} Excel</button></div><div id="aud"></div>`);
  $('#auq').oninput = A.debounce(()=>{ st.q=$('#auq').value.trim(); st.page=1; list(); },350); A.handlers.page = el=>{ st.page=+el.dataset.p; list(); };
  const mk = ()=>{ let q = sb.from('admin_audit_log').select('*',{count:'exact'}); if(st.q) q=q.ilike('action',`%${st.q.replace(/[%,()]/g,'')}%`); return q.order('created_at',{ascending:false}); };
  async function list(){
    const r = await mk().range((st.page-1)*AP, st.page*AP-1);
    if(r.error){ A.mount($('#aud'), html`<div class="alert warn">${A.missing(r.error)?'جدول admin_audit_log هنوز ساخته نشده؛ فایل admin-upgrade.sql را اجرا کن. (تا آن موقع فعالیت‌ها ثبت نمی‌شوند.)':A.errMsg(r.error)}</div>`); return; }
    A.mount($('#aud'), html`${A.table([{h:'زمان',c:x=>A.dt(x.created_at)},{h:'مدیر',c:x=>html`<span style="font-size:12px">${(x.admin_email||'').split('@')[0]}</span>`},{h:'عملیات',c:x=>html`<span class="mono">${x.action}</span>`},{h:'مورد',c:x=>x.entity||'—'},{h:'جزئیات',c:x=>html`<span class="muted" style="font-size:12px;word-break:break-all">${(x.detail||'').slice(0,160)}</span>`}], r.data||[], {empty:'هنوز فعالیتی ثبت نشده'})}${A.pager(r.count||0, st.page, AP)}`);
  }
  list();
  A.handlers['aud-export'] = async ()=>{ const rows = await A.fetchAll(mk); A.csv('audit-'+A.dayKey(Date.now())+'.csv',['زمان','مدیر','عملیات','مورد','شناسه','جزئیات'],rows.map(x=>[A.dt(x.created_at),x.admin_email,x.action,x.entity,x.entity_id,x.detail])); };
}});

/* ====================== تنظیمات و امنیت ====================== */
A.route('settings', { title:'تنظیمات و امنیت', async render(box){
  const fac = await sb.auth.mfa.listFactors().catch(()=>({data:null})); const totp = (fac.data?.totp||[]).filter(f=>f.status==='verified');
  let roles = null; if(A.role==='owner'){ const r = await sb.from('admin_roles').select('*').order('created_at',{ascending:true}); roles = r.error?null:(r.data||[]); }
  A.mount(box, html`<div class="grid g2" style="align-items:start">
    <div class="card"><h3>حساب من</h3><dl class="kv"><dt>ایمیل</dt><dd class="mono">${A.user.email}</dd><dt>نقش</dt><dd>${{owner:'مدیر کل',support:'پشتیبان',editor:'ویرایشگر'}[A.role]}</dd></dl>
      <hr style="border:0;border-top:1px solid var(--line);margin:16px 0"><h4 style="margin-bottom:10px">تغییر رمز عبور</h4><div class="fld"><input class="in ltr" id="np" type="password" placeholder="رمز جدید (حداقل ۱۰ کاراکتر)" autocomplete="new-password"></div><button class="btn primary" id="npBtn">تغییر رمز</button></div>
    <div class="card"><h3>ورود دومرحله‌ای (2FA)</h3>${totp.length?html`<div class="alert ok">${A.icon('shield')}<div>فعال است. هنگام ورود، کد برنامه‌ی احراز هویت خواسته می‌شود.</div></div><button class="btn danger" id="mfaOff">غیرفعال کردن</button>`:html`<p class="hint">با فعال‌کردن آن، حتی اگر رمزت لو برود بدون گوشی تو کسی وارد پنل نمی‌شود. به یک برنامه‌ی Google Authenticator / Authy نیاز داری.</p><button class="btn primary" id="mfaOn">${A.icon('shield')} فعال‌سازی</button><div id="mfaBox"></div>`}</div></div>
    ${roles!==null?html`<div class="card"><h3>همکاران و نقش‌ها</h3><p class="hint">«پشتیبان» فقط هنرجویان، تراکنش‌ها، اعطای دسترسی و پیام‌ها را می‌بیند. «ویرایشگر» فقط محتوای سایت و دوره‌ها. ⚠ این محدودیت فقط در رابط پنل اعمال می‌شود؛ هر کسی که حساب ورود دارد از نظر فنی همه‌ی دسترسی‌های دیتابیس را دارد، پس فقط به افراد مورد اعتماد حساب بده. ایجاد حساب جدید: Supabase ← Authentication ← Users ← Add user.</p>
      ${A.table([{h:'ایمیل',c:r=>html`<span class="mono">${r.email}</span>`},{h:'نقش',c:r=>({owner:'مدیر کل',support:'پشتیبان',editor:'ویرایشگر'}[r.role]||r.role)},{h:'',c:r=>r.email===A.user.email?'':html`<button class="btn sm danger" data-act="role-del" data-id="${r.id}">حذف</button>`}], roles, {empty:'هنوز همکاری اضافه نشده (تو مدیر کل هستی)'})}
      <div class="row3" style="margin-top:14px;align-items:end"><div class="fld"><label class="lb">ایمیل همکار</label><input class="in ltr" id="rEm"></div><div class="fld"><label class="lb">نقش</label><select class="in" id="rRole"><option value="support">پشتیبان</option><option value="editor">ویرایشگر</option><option value="owner">مدیر کل</option></select></div><div class="fld"><button class="btn primary" id="rAdd">افزودن</button></div></div></div>`:''}`);
  $('#npBtn').onclick = ev=>A.busy(ev.currentTarget, async ()=>{ const p=$('#np').value; if(p.length<10){ A.toast('رمز باید حداقل ۱۰ کاراکتر باشد','err'); return; } const { error } = await sb.auth.updateUser({password:p}); if(error){ A.toast(A.errMsg(error),'err'); return; } $('#np').value=''; A.audit('password_change','auth'); A.toast('رمز عبور تغییر کرد ✓','ok'); });
  const on = $('#mfaOn'); if(on) on.onclick = async ()=>{
    const r = await sb.auth.mfa.enroll({factorType:'totp',friendlyName:'پنل مدیریت '+Date.now()}); if(r.error){ A.toast(A.errMsg(r.error)+' — شاید MFA در Supabase خاموش است','err'); return; }
    $('#mfaBox').innerHTML = `<div style="margin-top:14px;text-align:center"><p class="muted" style="margin-bottom:8px">این QR را با برنامه‌ی احراز هویت اسکن کن:</p><img src="${esc(r.data.totp.qr_code)}" style="width:200px;background:#fff;border-radius:10px;padding:8px" alt="QR"><p class="muted mono" style="font-size:11.5px;margin:8px 0">${esc(r.data.totp.secret)}</p><div class="fld"><input class="in ltr" id="mCode" inputmode="numeric" placeholder="کد ۶ رقمی" style="text-align:center"></div><button class="btn primary" id="mVer">تأیید و فعال‌سازی</button></div>`;
    $('#mVer').onclick = async ()=>{ const ch = await sb.auth.mfa.challenge({factorId:r.data.id}); if(ch.error){ A.toast(A.errMsg(ch.error),'err'); return; } const v = await sb.auth.mfa.verify({factorId:r.data.id,challengeId:ch.data.id,code:A.toEn($('#mCode').value).trim()}); if(v.error){ A.toast('کد نادرست است','err'); return; } A.audit('2fa_enable','auth'); A.toast('ورود دومرحله‌ای فعال شد ✓','ok'); A.rerender(); };
  };
  const off = $('#mfaOff'); if(off) off.onclick = async ()=>{ if(!await A.confirm({title:'غیرفعال‌کردن 2FA',message:'ورود دومرحله‌ای خاموش شود؟',danger:true})) return; const r = await sb.auth.mfa.unenroll({factorId:totp[0].id}); if(r.error){ A.toast(A.errMsg(r.error),'err'); return; } A.audit('2fa_disable','auth'); A.toast('غیرفعال شد'); A.rerender(); };
  const ra = $('#rAdd'); if(ra) ra.onclick = async ()=>{ const em=$('#rEm').value.trim().toLowerCase(); if(!/^\S+@\S+\.\S+$/.test(em)){ A.toast('ایمیل نامعتبر','err'); return; } await A.q(sb.from('admin_roles').insert([{email:em,role:$('#rRole').value}])); A.audit('role_add','admin_roles',null,{em,role:$('#rRole').value}); A.rerender(); };
}});
A.on('role-del', async el=>{ if(!await A.confirm({title:'حذف همکار',message:'نقش این همکار حذف شود؟ (حساب ورودش در Supabase باقی می‌ماند؛ در صورت لزوم از آنجا حذفش کن)',danger:true})) return; await A.q(sb.from('admin_roles').delete().eq('id',el.dataset.id)); A.audit('role_del','admin_roles',el.dataset.id); A.rerender(); });
})();
