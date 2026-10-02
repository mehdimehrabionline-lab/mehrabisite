/* ==========================================================
   بازاریابی: کد تخفیف، کد معرف و پورسانت معرف‌ها
   (نیاز به promo-upgrade.sql؛ بدون آن فقط پیام راهنما نشان داده می‌شود)
   ========================================================== */
(function(){
'use strict';
const { html, esc, $, $$ } = A;
const SITE = location.origin;
const fmtVal = (type, v) => type==='percent' ? A.fa(v)+'٪' : A.money(v);
const needSql = e => A.missing(e) ? html`<div class="alert warn">${A.icon('alert')}<div>جدول کدها هنوز ساخته نشده. فایل <b>promo-upgrade.sql</b> را در Supabase ← SQL Editor اجرا کن.</div></div>` : html`<div class="alert err">${A.errMsg(e)}</div>`;
const genCode = () => { const c='ABCDEFGHJKMNPQRSTUVWXYZ23456789'; let s=''; for(let i=0;i<7;i++) s+=c[Math.floor(Math.random()*c.length)]; return s; };

A.route('codes', { title:'کدهای تخفیف و معرف', async render(box, args){
  const tab = args[0]||'codes';
  const tabs = [['codes','کدها'],['comm','پورسانت معرف‌ها'],['sms','تنظیمات پیامک']];
  A.mount(box, html`<div class="tabs">${tabs.map(t=>html`<a class="tab ${t[0]===tab?'active':''}" href="#/codes/${t[0]}">${t[1]}</a>`)}</div><div id="cbody"><div class="loading"><span class="spin"></span></div></div>`);
  const b = $('#cbody');
  if(tab==='comm') return commissions(b); if(tab==='sms') return smsSettings(b); return codesList(b);
}});

/* ---------- فهرست کدها ---------- */
async function codesList(b){
  const [r, courses] = await Promise.all([ sb.from('promo_codes').select('*').order('created_at',{ascending:false}), A.courses().catch(()=>[]) ]);
  if(r.error){ A.mount(b, needSql(r.error)); return; }
  A.state.codes = r.data||[]; A.state.cmap = Object.fromEntries(courses.map(c=>[c.id,c]));
  A.mount(b, html`<div class="toolbar"><span class="muted">هر خرید فقط یک کد می‌پذیرد. تخفیف روی مبلغ پرداختی اعمال می‌شود و پورسانت از مبلغ <b>بعد از تخفیف</b> حساب می‌شود.</span><div class="sp"></div>
    <button class="btn" data-act="code-edit" data-kind="discount">${A.icon('plus')} کد تخفیف</button><button class="btn primary" data-act="code-edit" data-kind="referral">${A.icon('plus')} کد معرف</button></div>
    ${A.table([
      {h:'کد',c:c=>html`<b class="mono" style="font-size:14px">${c.code}</b> <button class="btn ghost sm" data-act="copy" data-v="${c.code}">${A.icon('copy')}</button>`},
      {h:'نوع',c:c=>c.kind==='referral'?A.badge('معرف','acc'):A.badge('تخفیف','info')},
      {h:'تخفیف خریدار',c:c=>fmtVal(c.discount_type,c.discount_value)},
      {h:'معرف',c:c=>c.kind==='referral'?html`${c.referrer_name||'—'}<div class="mono muted" style="font-size:11.5px">${c.referrer_phone||''}</div>`:'—'},
      {h:'پورسانت',c:c=>c.kind==='referral'&&c.commission_type?fmtVal(c.commission_type,c.commission_value):'—'},
      {h:'مصرف',c:c=>html`${A.fa(c.used_count||0)}${c.max_uses!=null?' / '+A.fa(c.max_uses):''}`},
      {h:'دوره',c:c=>c.course_id?(A.state.cmap[c.course_id]?.title||'—'):'همه'},
      {h:'انقضا',c:c=>c.expires_at?(new Date(c.expires_at)<new Date()?A.badge('منقضی','err'):A.date(c.expires_at)):'—'},
      {h:'وضعیت',c:c=>c.is_active?A.badge('فعال','ok'):A.badge('غیرفعال','mute')},
      {h:'',c:c=>html`<button class="btn sm" data-act="code-edit" data-id="${c.id}">${A.icon('edit')}</button> <button class="btn sm" data-act="code-links" data-id="${c.id}">${A.icon('link')}</button> <button class="btn sm" data-act="code-toggle" data-id="${c.id}">${c.is_active?'غیرفعال':'فعال'}</button>`}], A.state.codes, {empty:'هنوز کدی نساخته‌ای'})}`);
}
A.on('code-toggle', async el=>{ const c=A.state.codes.find(x=>x.id===el.dataset.id); await A.q(sb.from('promo_codes').update({is_active:!c.is_active}).eq('id',c.id)); A.audit('code_toggle','promo_codes',c.id,{code:c.code,on:!c.is_active}); A.rerender(); });
A.on('code-links', async el=>{
  const c=A.state.codes.find(x=>x.id===el.dataset.id); const courses = (await A.courses()).filter(x=>x.is_active!==false);
  const rows = await sb.from('courses').select('id,title,slug').in('id',courses.map(x=>x.id)); 
  const list = (rows.data||[]).filter(x=>x.slug && (!c.course_id || c.course_id===x.id));
  const m = A.modal({ title:'لینک‌های اشتراک کد '+c.code, size:'lg', body: html`<p class="hint">هر کس با این لینک وارد صفحه‌ی دوره شود، کد خودکار در کادر کد تخفیف پر می‌شود.</p>${list.length?list.map(x=>html`<div style="display:flex;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)"><span style="flex:0 0 38%">${x.title}</span><input class="in ltr mono sm" readonly value="${SITE}/c/${x.slug}?ref=${c.code}"><button class="btn sm" data-act="copy" data-v="${SITE}/c/${x.slug}?ref=${c.code}">${A.icon('copy')}</button></div>`):html`<div class="muted">دوره‌ی فعالِ دارای slug پیدا نشد.</div>`}`, footer: html`<button class="btn" data-x>بستن</button>` });
  m.$('[data-x]').onclick=()=>m.close();
});
A.on('code-edit', el=>{
  const c = el.dataset.id ? A.state.codes.find(x=>x.id===el.dataset.id) : { kind:el.dataset.kind||'discount', discount_type:'percent', discount_value:0, is_active:true, commission_type:'percent' };
  const isRef = c.kind==='referral'; const courses = Object.values(A.state.cmap||{});
  const m = A.modal({ title:(c.id?'ویرایش ':'ساخت ')+(isRef?'کد معرف':'کد تخفیف'), size:'lg', body: html`
    <div class="row2"><div class="fld"><label class="lb">کد (انگلیسی/عدد) *</label><div style="display:flex;gap:8px"><input class="in ltr mono" id="kCode" value="${c.code||''}" maxlength="32" style="text-transform:uppercase"><button class="btn" type="button" id="kGen">تصادفی</button></div></div>
      <div class="fld"><label class="lb">محدود به دوره</label><select class="in" id="kCourse"><option value="">همه‌ی دوره‌ها</option>${courses.map(x=>html`<option value="${x.id}" ${x.id===c.course_id?'selected':''}>${x.title}</option>`)}</select></div></div>
    <div class="card" style="background:var(--panel2)"><h3>تخفیف برای خریدار</h3><div class="row2"><div class="fld"><label class="lb">نوع</label><select class="in" id="kDT"><option value="percent" ${c.discount_type!=='amount'?'selected':''}>درصد</option><option value="amount" ${c.discount_type==='amount'?'selected':''}>مبلغ ثابت (تومان)</option></select></div>
      <div class="fld"><label class="lb">مقدار</label><input class="in num ltr" id="kDV" value="${A.fa(c.discount_value||0)}"></div></div>
      <div class="help">مبلغ نهایی هیچ‌وقت کمتر از ۱٬۰۰۰ تومان نمی‌شود.</div></div>
    ${isRef?html`<div class="card" style="background:var(--panel2)"><h3>معرف و پورسانت</h3><div class="row2"><div class="fld"><label class="lb">نام معرف *</label><input class="in" id="kRN" value="${c.referrer_name||''}"></div><div class="fld"><label class="lb">موبایل معرف * (پیامک پورسانت به همین شماره می‌رود)</label><input class="in ltr" id="kRP" value="${c.referrer_phone||''}" maxlength="11" inputmode="numeric"></div></div>
      <div class="row2"><div class="fld"><label class="lb">نوع پورسانت</label><select class="in" id="kCT"><option value="percent" ${c.commission_type!=='amount'?'selected':''}>درصد از مبلغ پرداختی</option><option value="amount" ${c.commission_type==='amount'?'selected':''}>مبلغ ثابت برای هر فروش (تومان)</option></select></div>
      <div class="fld"><label class="lb">مقدار پورسانت</label><input class="in num ltr" id="kCV" value="${A.fa(c.commission_value||0)}"></div></div></div>`:''}
    <div class="row3"><div class="fld"><label class="lb">سقف تعداد استفاده</label><input class="in num ltr" id="kMax" value="${c.max_uses!=null?A.fa(c.max_uses):''}" placeholder="نامحدود"></div>
      <div class="fld"><label class="lb">تاریخ انقضا</label><input class="in ltr" type="date" id="kExp" value="${c.expires_at?A.dayKey(c.expires_at):''}"></div>
      <div class="fld"><label class="lb">&nbsp;</label><label class="switch"><input type="checkbox" id="kOn" ${c.is_active!==false?'checked':''}><i></i><span>فعال</span></label></div></div>
    <div class="fld"><label class="lb">یادداشت داخلی</label><input class="in" id="kNote" value="${c.note||''}"></div>`,
    footer: html`<button class="btn" data-x>انصراف</button><button class="btn primary" id="kSave">ذخیره</button>`, dirty:()=>false });
  m.$('[data-x]').onclick=()=>m.close(); m.$('#kGen').onclick=()=>{ m.$('#kCode').value=genCode(); };
  m.$('#kSave').onclick = ev=>A.busy(ev.currentTarget, async ()=>{
    const code = String(m.$('#kCode').value).replace(/[^A-Za-z0-9_-]/g,'').toUpperCase(); if(code.length<3){ A.toast('کد باید حداقل ۳ حرف/عدد انگلیسی باشد','err'); return; }
    const dt=m.$('#kDT').value, dv=A.num(m.$('#kDV').value)||0;
    if(dt==='percent' && (dv<0||dv>100)){ A.toast('درصد تخفیف بین ۰ تا ۱۰۰ باشد','err'); return; } if(dt==='amount' && dv<0){ A.toast('مبلغ نامعتبر','err'); return; }
    if(!isRef && dv<=0){ A.toast('برای کد تخفیف، مقدار تخفیف را وارد کن','err'); return; }
    const p = { code, kind:c.kind, discount_type:dt, discount_value:dv, course_id:m.$('#kCourse').value||null, max_uses:A.num(m.$('#kMax').value), is_active:m.$('#kOn').checked, note:m.$('#kNote').value.trim()||null,
      expires_at: m.$('#kExp').value ? new Date(m.$('#kExp').value+'T23:59:59+03:30').toISOString() : null };
    if(isRef){
      const ph = A.toEn(m.$('#kRP').value).trim(); if(!/^09\d{9}$/.test(ph)){ A.toast('موبایل معرف معتبر نیست','err'); return; }
      const ct=m.$('#kCT').value, cv=A.num(m.$('#kCV').value)||0; if(ct==='percent'&&(cv<0||cv>100)){ A.toast('درصد پورسانت بین ۰ تا ۱۰۰ باشد','err'); return; }
      if(!m.$('#kRN').value.trim()){ A.toast('نام معرف را بنویس','err'); return; }
      Object.assign(p,{referrer_name:m.$('#kRN').value.trim(), referrer_phone:ph, commission_type:ct, commission_value:cv});
    }
    const dup = await A.q(sb.from('promo_codes').select('id').eq('code',code).neq('id',c.id||'00000000-0000-0000-0000-000000000000').limit(1)); if(dup.length){ A.toast('این کد قبلاً ساخته شده','err'); return; }
    await A.q(c.id?sb.from('promo_codes').update(p).eq('id',c.id):sb.from('promo_codes').insert([p]));
    A.audit(c.id?'code_edit':'code_create','promo_codes',c.id,{code,kind:c.kind}); A.toast('ذخیره شد ✓','ok'); m.close(true); A.rerender();
  });
});

/* ---------- پورسانت‌ها ---------- */
async function commissions(b){
  const st = A.state.comm = A.state.comm || { code:'', status:'pending' };
  let rows;
  try{ rows = await A.fetchAll(()=>sb.from('promo_redemptions').select('*').order('created_at',{ascending:false})); }catch(e){ A.mount(b, needSql(e)); return; }
  const refs = rows.filter(r=>r.kind==='referral');
  const by = {}; refs.forEach(r=>{ const x=by[r.code]=by[r.code]||{code:r.code,name:r.referrer_name,phone:r.referrer_phone,n:0,pend:0,paid:0}; x.n++; if(r.commission_status==='paid') x.paid+=r.commission_amount; else x.pend+=r.commission_amount; });
  const sums = Object.values(by).sort((a,b)=>b.pend-a.pend);
  const list = refs.filter(r=>(!st.code||r.code===st.code) && (!st.status||r.commission_status===st.status));
  const us = await A.byIds('academy_users','id,full_name,phone', list.map(r=>r.buyer_user_id)); const cs = Object.fromEntries((await A.courses()).map(c=>[c.id,c.title]));
  A.state.commRows = list;
  A.mount(b, html`
    <div class="grid g3" style="margin-bottom:16px">${sums.length?sums.slice(0,6).map(s=>html`<div class="kpi ${s.pend?'warn':''}"><div class="l">${s.name||'—'} <span class="mono">${s.code}</span></div><div class="v">${A.fa(s.pend)}<small>ت پرداخت‌نشده</small></div><div class="s">${A.fa(s.n)} فروش · پرداخت‌شده ${A.money(s.paid)}</div>
      <div style="display:flex;gap:6px;margin-top:10px">${s.pend?html`<button class="btn sm primary" data-act="comm-paid-all" data-code="${s.code}">ثبت پرداخت همه</button>`:''}<button class="btn sm" data-act="comm-report" data-code="${s.code}">${A.icon('mail')} پیامک گزارش</button></div></div>`):html`<div class="empty" style="grid-column:1/-1">هنوز فروشی با کد معرف ثبت نشده</div>`}</div>
    <div class="toolbar"><select class="in sm" id="cmC" style="width:200px"><option value="">همه‌ی معرف‌ها</option>${sums.map(s=>html`<option value="${s.code}">${s.name||''} — ${s.code}</option>`)}</select>
      <select class="in sm" id="cmS" style="width:160px"><option value="">همه</option><option value="pending">پرداخت‌نشده</option><option value="paid">پرداخت‌شده</option></select><div class="sp"></div><button class="btn" data-act="comm-export">${A.icon('download')} Excel</button></div>
    ${A.table([{h:'تاریخ',c:r=>A.dt(r.created_at)},{h:'معرف',c:r=>html`${r.referrer_name||'—'} <span class="mono muted">${r.code}</span>`},{h:'خریدار',c:r=>us[r.buyer_user_id]?A.userLink(us[r.buyer_user_id]):'—'},{h:'دوره',c:r=>cs[r.course_id]||'—'},
      {h:'مبلغ پرداختی',c:r=>A.money(r.final_amount),cls:'num'},{h:'تخفیف',c:r=>A.money(r.discount_amount),cls:'num'},{h:'پورسانت',c:r=>html`<b>${A.money(r.commission_amount)}</b>`,cls:'num'},
      {h:'وضعیت',c:r=>r.commission_status==='paid'?A.badge('پرداخت شد','ok'):A.badge('پرداخت‌نشده','warn')},{h:'',c:r=>r.commission_status==='paid'?'':html`<button class="btn sm" data-act="comm-paid" data-id="${r.id}">ثبت پرداخت</button>`}], list, {empty:'موردی نیست'})}`);
  $('#cmC').value=st.code; $('#cmS').value=st.status; $('#cmC').onchange=()=>{ st.code=$('#cmC').value; A.rerender(); }; $('#cmS').onchange=()=>{ st.status=$('#cmS').value; A.rerender(); };
}
A.on('comm-paid', async el=>{ await A.q(sb.from('promo_redemptions').update({commission_status:'paid',paid_at:new Date().toISOString()}).eq('id',el.dataset.id)); A.audit('commission_paid','promo_redemptions',el.dataset.id); A.rerender(); });
A.on('comm-paid-all', async el=>{
  const code=el.dataset.code; const pend = await A.q(sb.from('promo_redemptions').select('commission_amount').eq('code',code).eq('commission_status','pending')); const sum = pend.reduce((s,x)=>s+x.commission_amount,0);
  if(!await A.confirm({title:'ثبت پرداخت پورسانت',message:`پرداخت ${A.fa(pend.length)} پورسانت به مبلغ ${A.money(sum)} برای کد ${code} ثبت شود؟\n(این فقط یک ثبت حسابداری است؛ پولی جابه‌جا نمی‌شود.)`,confirm:'ثبت شد'})) return;
  await A.q(sb.from('promo_redemptions').update({commission_status:'paid',paid_at:new Date().toISOString()}).eq('code',code).eq('commission_status','pending')); A.audit('commission_paid_all','promo_redemptions',null,{code,sum}); A.toast('ثبت شد ✓','ok'); A.rerender();
});
A.on('comm-report', async el=>{
  if(!await A.confirm({title:'پیامک گزارش به معرف',message:'گزارش (تعداد فروش، پورسانت پرداخت‌شده و پرداخت‌نشده) به شماره‌ی خودِ معرف پیامک شود؟',confirm:'ارسال پیامک'})) return;
  const { data:{ session } } = await sb.auth.getSession();
  try{ const r = await fetch('/send-referral-report.php',{method:'POST',headers:{'Content-Type':'application/json',Authorization:'Bearer '+(session?.access_token||'')},body:JSON.stringify({code:el.dataset.code})}); const d = await r.json();
    if(d.ok){ A.audit('referral_report_sms','promo_codes',null,{code:el.dataset.code}); A.toast('پیامک گزارش ارسال شد ✓','ok'); } else A.toast(d.error||'ارسال نشد','err');
  }catch(e){ A.toast('ارتباط با سرور برقرار نشد','err'); }
});
A.on('comm-export', async ()=>{ const rows=A.state.commRows||[]; A.csv('commissions-'+A.dayKey(Date.now())+'.csv',['تاریخ','کد','معرف','موبایل معرف','مبلغ پرداختی','تخفیف','پورسانت','وضعیت'],rows.map(r=>[A.dt(r.created_at),r.code,r.referrer_name,r.referrer_phone,r.final_amount,r.discount_amount,r.commission_amount,r.commission_status==='paid'?'پرداخت شد':'پرداخت‌نشده'])); });

/* ---------- تنظیمات پیامک ---------- */
async function smsSettings(b){
  const r = await sb.from('site_content').select('key,value').in('key',['referral_sms_template','referral_report_sms_template']); const m={}; (r.data||[]).forEach(x=>m[x.key]=x.value);
  A.mount(b, html`<div class="card"><h3>پیامک به معرف<span class="sp"></span><button class="btn primary" id="sSave">${A.icon('check')} ذخیره</button></h3>
    <p class="hint">پیامک‌ها با «الگوی» (Template) تأییدشده‌ی SMS.ir ارسال می‌شوند، مثل پیامک خرید موفق. در پنل SMS.ir دو الگو بساز و شناسه‌ی عددی‌شان را اینجا بنویس. تا شناسه ثبت نشود، پیامکی فرستاده نمی‌شود (پورسانت همچنان ثبت می‌شود).</p>
    <div class="fld"><label class="lb">شناسه‌ی الگوی «خرید جدید با کد معرف» (خودکار، با هر فروش)</label><input class="in ltr" id="sT1" value="${m.referral_sms_template||''}" inputmode="numeric" placeholder="مثلاً 123456">
      <div class="help">متن پیشنهادی الگو (متغیرها دقیقاً همین نام‌ها باشند؛ نام دوره عمداً در پیامک نیست):<br><span class="mono">سلام #NAME# عزیز، خرید جدیدی با کد معرف شما ثبت شد. پورسانت شما: #AMOUNT# تومان</span></div></div>
    <div class="fld"><label class="lb">شناسه‌ی الگوی «گزارش» (دستی، با دکمه‌ی پیامک گزارش)</label><input class="in ltr" id="sT2" value="${m.referral_report_sms_template||''}" inputmode="numeric" placeholder="مثلاً 123457">
      <div class="help"><span class="mono">سلام #NAME# عزیز، گزارش شما: #COUNT# فروش — پورسانت پرداخت‌نشده: #PENDING# تومان — پرداخت‌شده: #PAID# تومان</span></div></div></div>`);
  $('#sSave').onclick = ev=>A.busy(ev.currentTarget, async ()=>{
    const v1=A.toEn($('#sT1').value).trim(), v2=A.toEn($('#sT2').value).trim(); if((v1&&!/^\d+$/.test(v1))||(v2&&!/^\d+$/.test(v2))){ A.toast('شناسه فقط عدد باشد','err'); return; }
    await A.q(sb.from('site_content').upsert([{key:'referral_sms_template',value:v1},{key:'referral_report_sms_template',value:v2}],{onConflict:'key'})); A.audit('referral_sms_settings','site_content'); A.toast('ذخیره شد ✓','ok');
  });
}
})();
