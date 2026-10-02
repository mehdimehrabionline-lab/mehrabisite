/* ==========================================================
   فروش و مالی: گزارش فروش، تراکنش‌ها، دسترسی‌ها، اعطای دستی، لایسنس‌ها
   ========================================================== */
(function(){
'use strict';
const { html, raw, esc, $, $$ } = A;

/* ---------- ابزارهای مشترک داده ---------- */
// خواندن همه‌ی ردیف‌ها با صفحه‌بندی (PostgREST به‌طور پیش‌فرض فقط ۱۰۰۰ ردیف می‌دهد)
A.fetchAll = async (make, page=1000, max=20000) => {
  let out = [];
  for(let from=0; from<max; from+=page){
    const r = await make().range(from, from+page-1); if(r.error) throw r.error;
    out = out.concat(r.data||[]); if((r.data||[]).length<page) break;
  }
  return out;
};
A.byIds = async (table, cols, ids) => {
  ids = [...new Set((ids||[]).filter(Boolean))]; const map = {};
  for(let i=0;i<ids.length;i+=100){
    const r = await sb.from(table).select(cols).in('id', ids.slice(i,i+100)); if(r.error) throw r.error;
    (r.data||[]).forEach(x=>map[x.id]=x);
  }
  return map;
};
let courseCache = null, courseAt = 0;
A.courses = async (force) => {
  if(!force && courseCache && Date.now()-courseAt<30000) return courseCache;
  let r = await sb.from('courses').select('id,title,price_amount,is_active,sort_order,cover_url').order('sort_order',{ascending:true});
  if(r.error) throw r.error; courseCache = r.data||[]; courseAt = Date.now(); return courseCache;
};
A.userName = u => (u && (u.full_name || u.username)) || (u && (u.phone||u.email)) || 'بدون نام';
A.userLink = (u, id) => u ? html`<a class="user-cell" href="#/students/${u.id||id}"><span class="avatar">${A.initial(A.userName(u))}</span><span><div>${A.userName(u)}</div><div class="muted mono" style="font-size:11.5px">${u.phone||u.email||''}</div></span></a>` : html`<span class="muted">—</span>`;
A.PAY = { paid:['موفق','ok'], pending:['معلق','warn'], failed:['ناموفق','err'] };
A.payBadge = s => { const x = A.PAY[s]||[s||'—','mute']; return A.badge(x[0], x[1]); };
const TEST_LIMIT = 500000;

/* ---------- اعطای دسترسی دستی (قابل استفاده از همه‌ی بخش‌ها) ---------- */
A.grantAccess = async ({phone='', courseId='', onDone}={}) => {
  const courses = await A.courses();
  const m = A.modal({ title:'اعطای دسترسی دوره', size:'',
    body: html`
      <div class="fld"><label class="lb">شماره موبایل هنرجو</label><input class="in ltr" id="gPhone" inputmode="numeric" placeholder="09123456789" maxlength="11">
        <div class="help" id="gWho">اگر این شماره حساب نداشته باشد، یک حساب جدید ساخته می‌شود.</div></div>
      <div class="fld"><label class="lb">دوره</label><select class="in" id="gCourse">${courses.map(c=>html`<option value="${c.id}">${c.title}${c.is_active===false?' (غیرفعال)':''}</option>`)}</select></div>
      <div class="fld"><label class="switch"><input type="checkbox" id="gSms" checked><i></i><span>پیامک «خرید موفق» برای هنرجو ارسال شود</span></label></div>
      <div class="fld"><label class="lb">یادداشت داخلی (اختیاری — فقط در گزارش فعالیت‌ها ثبت می‌شود)</label><input class="in" id="gNote" placeholder="مثلاً: واریز کارت‌به‌کارت، هدیه…"></div>
      <div id="gMsg"></div>`,
    footer: html`<button class="btn" data-close2>انصراف</button><button class="btn primary" id="gGo">${A.icon('key')} اعطای دسترسی</button>`,
    dirty: ()=> !!$('#gPhone')?.value });
  m.$('[data-close2]').onclick = ()=>m.close();
  const ph = m.$('#gPhone'); ph.value = phone; if(courseId) m.$('#gCourse').value = courseId;
  const who = m.$('#gWho');
  const check = A.debounce(async ()=>{
    const p = A.toEn(ph.value).replace(/\D/g,''); ph.value = p;
    if(p.length!==11){ who.textContent='اگر این شماره حساب نداشته باشد، یک حساب جدید ساخته می‌شود.'; return; }
    const r = await sb.from('academy_users').select('id,full_name').eq('phone',p).maybeSingle();
    who.innerHTML = r.data ? `<span class="ok">✓ حساب موجود: ${esc(r.data.full_name||'بدون نام')}</span>` : '<span class="warn">حسابی با این شماره نیست؛ حساب جدید ساخته می‌شود.</span>';
  }, 300);
  ph.addEventListener('input', check); if(phone) check();
  m.$('#gGo').onclick = async (ev) => {
    const btn = ev.currentTarget, msg = m.$('#gMsg'); msg.innerHTML='';
    const p = A.toEn(ph.value).trim(), cid = m.$('#gCourse').value;
    if(!/^09\d{9}$/.test(p)){ msg.innerHTML='<div class="alert err">شماره موبایل معتبر نیست (مثال: 09123456789)</div>'; return; }
    if(!cid){ msg.innerHTML='<div class="alert err">یک دوره انتخاب کن</div>'; return; }
    await A.busy(btn, async ()=>{
      try{
        let u = (await A.q(sb.from('academy_users').select('id,full_name').eq('phone',p).maybeSingle()));
        let created = false;
        if(!u){ u = await A.q(sb.from('academy_users').insert([{phone:p,is_verified:true}]).select('id').single()); created = true; }
        const ex = await A.q(sb.from('course_access').select('id,is_active').eq('user_id',u.id).eq('course_id',cid).maybeSingle());
        if(ex && ex.is_active!==false){ msg.innerHTML='<div class="alert ok">این شماره از قبل به این دوره دسترسی دارد ✓</div>'; return; }
        if(ex) await A.q(sb.from('course_access').update({is_active:true}).eq('id',ex.id));
        else await A.q(sb.from('course_access').insert([{user_id:u.id,course_id:cid,is_active:true}]));
        const course = courses.find(c=>c.id===cid);
        let smsOk = null;
        if(m.$('#gSms').checked){
          try{
            const { data:{ session } } = await sb.auth.getSession();
            const r = await fetch('/send-grant-sms.php',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(session?.access_token||'')},body:JSON.stringify({phone:p,course_title:course?course.title:'دوره'})});
            smsOk = r.ok;
          }catch(e){ smsOk = false; }
        }
        A.audit('grant_access','course_access',u.id,{phone:p,course:course&&course.title,note:m.$('#gNote').value||null,sms:smsOk,newUser:created});
        A.toast('دسترسی داده شد'+(smsOk===true?' و پیامک رفت ✓':smsOk===false?' (پیامک ارسال نشد)':' ✓'), smsOk===false?'':'ok');
        m.close(true); if(onDone) onDone();
      }catch(e){ msg.innerHTML=`<div class="alert err">خطا: ${esc(A.errMsg(e))}</div>`; }
    });
  };
};
A.on('grant', el => A.grantAccess({ phone:el.dataset.phone||'', courseId:el.dataset.course||'', onDone:()=>A.rerender() }));

/* ---------- محاسبه‌ی بازه‌ها ---------- */
A.ranges = {
  today:{ t:'امروز', f:()=>[A.todayStart(), null] },
  yesterday:{ t:'دیروز', f:()=>{ const s=A.todayStart(); return [A.addDays(s,-1), s]; } },
  d7:{ t:'۷ روز اخیر', f:()=>[A.addDays(A.todayStart(),-6), null] },
  d30:{ t:'۳۰ روز اخیر', f:()=>[A.addDays(A.todayStart(),-29), null] },
  month:{ t:'این ماه (شمسی)', f:()=>[A.jalaliMonthStart(), null] },
  all:{ t:'همه‌ی زمان‌ها', f:()=>[null, null] },
};
const payDate = p => p.paid_at || p.created_at;

/* ====================== گزارش فروش ====================== */
A.route('sales', { title:'گزارش فروش', async render(box){
  const st = A.state.sales = A.state.sales || { range:'d30', excludeTest:false };
  const [from,to] = A.ranges[st.range].f();
  const mk = (cols) => () => { let q = sb.from('payments').select(cols); if(from) q=q.gte('created_at', from.toISOString()); if(to) q=q.lt('created_at', to.toISOString()); return q.order('created_at',{ascending:false}); };
  let all;
  try{ all = await A.fetchAll(mk('id,user_id,course_id,amount,status,paid_at,created_at,track_id')); }
  catch(e){ if(A.missing(e)){ A.mount(box, html`<div class="alert warn">جدول payments پیدا نشد.</div>`); return; } throw e; }
  if(st.excludeTest) all = all.filter(p=>p.amount>=TEST_LIMIT);
  const paid = all.filter(p=>p.status==='paid'), pend = all.filter(p=>p.status==='pending'), fail = all.filter(p=>p.status==='failed');
  const rev = paid.reduce((s,p)=>s+p.amount,0);
  const conv = all.length ? Math.round(paid.length/all.length*100) : 0;
  const courses = await A.courses(); const cmap = Object.fromEntries(courses.map(c=>[c.id,c]));
  // نمودار روزانه
  const byDay = {}; paid.forEach(p=>{ const k=A.dayKey(payDate(p)); byDay[k]=(byDay[k]||0)+p.amount; });
  const days = []; const start = from ? A.dayKey(from) : (paid.length ? A.dayKey(paid[paid.length-1].paid_at||paid[paid.length-1].created_at) : A.dayKey(Date.now()));
  for(let d=A.dayStart(start), n=0; d<=new Date() && n<400; d=A.addDays(d,1), n++){ const k=A.dayKey(d); days.push({label:A.date(d), short:A.dshort(d), value:Math.round((byDay[k]||0)) }); }
  // به تفکیک دوره
  const per = {}; paid.forEach(p=>{ const x=per[p.course_id]=per[p.course_id]||{n:0,sum:0}; x.n++; x.sum+=p.amount; });
  const perRows = Object.entries(per).sort((a,b)=>b[1].sum-a[1].sum);
  // ناسازگاری: پرداخت موفق بدون دسترسی
  const mism = await A.pay.mismatches(paid);
  A.mount(box, html`
    <div class="toolbar">
      <div class="seg" id="rangeSeg">${Object.entries(A.ranges).map(([k,v])=>html`<button data-act="srange" data-k="${k}" class="${k===st.range?'on':''}">${v.t}</button>`)}</div>
      <label class="switch"><input type="checkbox" id="exTest" ${st.excludeTest?'checked':''}><i></i><span>حذف تراکنش‌های آزمایشی (زیر ${A.fa(TEST_LIMIT)} تومان)</span></label>
      <div class="sp"></div>
      <button class="btn" data-act="sales-export">${A.icon('download')} خروجی Excel</button>
    </div>
    ${mism.length ? html`<div class="alert err">${A.icon('alert')}<div><b>${A.fa(mism.length)} پرداخت موفق بدون دسترسی دوره!</b> این هنرجوها پول داده‌اند ولی دوره برایشان فعال نشده. <a href="#/payments/mismatch">مشاهده و رفع ←</a></div></div>`:''}
    <div class="grid g4 keep2" style="margin-bottom:18px">
      <div class="kpi accent"><div class="l">${A.icon('cash')} درآمد</div><div class="v">${A.fa(rev)}<small>تومان</small></div><div class="s">${st.range==='all'?'کل':A.ranges[st.range].t}</div></div>
      <div class="kpi"><div class="l">${A.icon('check')} تعداد فروش</div><div class="v">${A.fa(paid.length)}</div><div class="s">میانگین سفارش: ${paid.length?A.money(Math.round(rev/paid.length)):'—'}</div></div>
      <div class="kpi"><div class="l">${A.icon('activity')} نرخ تبدیل پرداخت</div><div class="v">${A.fa(conv)}٪</div><div class="s">از ${A.fa(all.length)} تلاش پرداخت</div></div>
      <div class="kpi ${pend.length?'warn':''}"><div class="l">${A.icon('clock')} معلق / ناموفق</div><div class="v">${A.fa(pend.length)} / ${A.fa(fail.length)}</div><div class="s"><a href="#/payments">بررسی تراکنش‌ها ←</a></div></div>
    </div>
    <div class="card"><h3>درآمد روزانه (تومان)</h3>${A.chartBars(days)}</div>
    <div class="card"><h3>فروش به تفکیک دوره</h3>${A.table([
      {h:'دوره', c:r=>cmap[r[0]]?.title||'(دوره‌ی حذف‌شده)'},
      {h:'تعداد فروش', c:r=>A.fa(r[1].n), cls:'num'},
      {h:'درآمد', c:r=>A.money(r[1].sum), cls:'num'},
      {h:'سهم', c:r=>A.fa(rev?Math.round(r[1].sum/rev*100):0)+'٪', cls:'num'} ], perRows, {empty:'در این بازه فروشی ثبت نشده'})}</div>
    <p class="muted" style="font-size:12px">مبالغ به تومان و همان مبلغی است که در جدول payments ثبت شده. روزها به وقت تهران محاسبه می‌شوند.</p>`);
  $('#exTest').onchange = e=>{ st.excludeTest = e.target.checked; A.rerender(); };
  A.handlers['sales-export'] = async () => {
    const us = await A.byIds('academy_users','id,full_name,phone', paid.map(p=>p.user_id));
    A.csv('sales-'+A.dayKey(Date.now())+'.csv', ['تاریخ','نام','موبایل','دوره','مبلغ (تومان)','کد رهگیری'],
      paid.map(p=>[A.dt(payDate(p)), us[p.user_id]?.full_name||'', us[p.user_id]?.phone||'', cmap[p.course_id]?.title||'', p.amount, p.track_id||'']));
  };
}});
A.on('srange', el => { A.state.sales.range = el.dataset.k; A.rerender(); });

/* پرداخت موفقی که دسترسی ندارد (عیب‌یابی verify.php) */
A.pay = {
  async mismatches(paid){
    if(!paid.length) return [];
    const ids = [...new Set(paid.map(p=>p.user_id))]; const have = new Set();
    for(let i=0;i<ids.length;i+=80){
      const r = await sb.from('course_access').select('user_id,course_id,is_active').in('user_id', ids.slice(i,i+80));
      if(r.error) return []; (r.data||[]).forEach(a=>{ if(a.is_active!==false) have.add(a.user_id+'|'+a.course_id); });
    }
    return paid.filter(p=>!have.has(p.user_id+'|'+p.course_id));
  }
};

/* ====================== تراکنش‌ها ====================== */
const PS = 25;
A.route('payments', { title:'تراکنش‌ها', async render(box, args){
  const st = A.state.pay = A.state.pay || { status:'', course:'', q:'', range:'all', page:1 };
  if(args[0]==='mismatch'){ st.status='mismatch'; st.page=1; } else if(args[0] && st.status==='mismatch' && args[0].length>10){ /* نگه داشتن */ }
  const courses = await A.courses();
  A.mount(box, html`
    <div class="toolbar">
      <input class="in sm" id="pq" style="max-width:260px" placeholder="موبایل، کد رهگیری یا شماره مرجع…" value="${st.q}">
      <select class="in sm" id="ps" style="width:215px"><option value="">همه‌ی وضعیت‌ها</option><option value="paid">موفق</option><option value="pending">معلق</option><option value="stale">معلق قدیمی (+۲۴ ساعت)</option><option value="failed">ناموفق</option><option value="mismatch">⚠ موفق بدون دسترسی</option></select>
      <select class="in sm" id="pc" style="width:200px"><option value="">همه‌ی دوره‌ها</option>${courses.map(c=>html`<option value="${c.id}">${c.title}</option>`)}</select>
      <select class="in sm" id="pr" style="width:130px">${Object.entries(A.ranges).map(([k,v])=>html`<option value="${k}">${v.t}</option>`)}</select>
      <div class="sp"></div>
      <button class="btn" data-act="pay-reconcile">${A.icon('refresh')} بررسی معلق‌ها از زیبال</button>
      <button class="btn" data-act="pay-export">${A.icon('download')} Excel</button>
    </div>
    <div id="payList"><div class="loading"><span class="spin"></span></div></div>`);
  $('#ps').value = st.status; $('#pc').value = st.course; $('#pr').value = st.range;
  const apply = () => { st.q=$('#pq').value.trim(); st.status=$('#ps').value; st.course=$('#pc').value; st.range=$('#pr').value; st.page=1; list(); };
  $('#pq').oninput = A.debounce(apply,350); ['ps','pc','pr'].forEach(i=>$('#'+i).onchange=apply);
  A.handlers.page = el => { st.page = +el.dataset.p; list(); };
  A.handlers['pay-export'] = async () => {
    const rows = await query(true); const us = await A.byIds('academy_users','id,full_name,phone',rows.map(p=>p.user_id));
    const cm = Object.fromEntries(courses.map(c=>[c.id,c.title]));
    A.csv('payments-'+A.dayKey(Date.now())+'.csv',['تاریخ ایجاد','تاریخ پرداخت','نام','موبایل','دوره','مبلغ','وضعیت','کد رهگیری','شماره مرجع','کارت'],
      rows.map(p=>[A.dt(p.created_at),p.paid_at?A.dt(p.paid_at):'',us[p.user_id]?.full_name||'',us[p.user_id]?.phone||'',cm[p.course_id]||'',p.amount,(A.PAY[p.status]||[p.status])[0],p.track_id||'',p.ref_number||'',p.card_number||'']));
  };
  async function query(all){
    const [from,to] = A.ranges[st.range].f();
    let userIds = null;
    if(st.q){
      const v = A.toEn(st.q).replace(/[%,()]/g,'');
      const ur = await sb.from('academy_users').select('id').or(`phone.ilike.%${v}%,full_name.ilike.%${v}%`).limit(200);
      userIds = (ur.data||[]).map(x=>x.id);
    }
    const mk = () => {
      let q = sb.from('payments').select('*', {count:'exact'});
      if(st.status==='paid'||st.status==='mismatch') q=q.eq('status','paid'); else if(st.status==='pending') q=q.eq('status','pending'); else if(st.status==='failed') q=q.eq('status','failed');
      else if(st.status==='stale') q=q.eq('status','pending').lt('created_at', new Date(Date.now()-86400000).toISOString());
      if(st.course) q=q.eq('course_id',st.course);
      if(from) q=q.gte('created_at',from.toISOString()); if(to) q=q.lt('created_at',to.toISOString());
      if(st.q){ const v=A.toEn(st.q).replace(/[%,()]/g,''); const parts=[`track_id.eq.${v}`,`ref_number.eq.${v}`]; if(userIds&&userIds.length) parts.push(`user_id.in.(${userIds.join(',')})`); q=q.or(parts.join(',')); }
      return q.order('created_at',{ascending:false});
    };
    if(all) return A.fetchAll(mk);
    if(st.status==='mismatch'){ const rows = await A.fetchAll(mk); const mm = await A.pay.mismatches(rows); return { data:mm.slice((st.page-1)*PS, st.page*PS), count:mm.length }; }
    const r = await mk().range((st.page-1)*PS, st.page*PS-1); if(r.error) throw r.error; return r;
  }
  async function list(){
    const out = $('#payList'); out.innerHTML = '<div class="loading"><span class="spin"></span></div>';
    const r = await query(false); const rows = r.data||[]; const total = r.count||0;
    const us = await A.byIds('academy_users','id,full_name,username,phone,email', rows.map(p=>p.user_id)); const cm = Object.fromEntries(courses.map(c=>[c.id,c]));
    A.state.payRows = Object.fromEntries(rows.map(p=>[p.id,{p,u:us[p.user_id],c:cm[p.course_id]}]));
    A.mount(out, html`${A.table([
      {h:'تاریخ', c:p=>html`<div>${A.date(payDate(p))}</div><div class="muted" style="font-size:11.5px">${A.time(payDate(p))}</div>`},
      {h:'هنرجو', c:p=>A.userLink(us[p.user_id], p.user_id)},
      {h:'دوره', c:p=>cm[p.course_id]?.title||'—'},
      {h:'مبلغ', c:p=>html`<b>${A.fa(p.amount)}</b> <span class="muted">ت</span>`, cls:'num'},
      {h:'وضعیت', c:p=>A.payBadge(p.status)},
      {h:'کد رهگیری', c:p=>html`<span class="mono">${p.track_id||'—'}</span>`},
      {h:'شماره مرجع', c:p=>html`<span class="mono">${p.ref_number||'—'}</span>`},
    ], rows, {row:'pay-open', empty:'تراکنشی با این فیلتر نیست'})}${A.pager(total, st.page, PS)}`);
    if(!total && st.status==='' && !st.q){
      const anyAccess = await A.count('course_access');
      if(anyAccess>0) out.insertAdjacentHTML('beforeend','<div class="alert warn" style="margin-top:14px">اگر مطمئنی پرداخت‌هایی ثبت شده ولی اینجا نمی‌بینی، فایل <b>admin-upgrade.sql</b> را در Supabase اجرا کن (اجازه‌ی خواندن جدول payments برای پنل).</div>');
    }
  }
  list();
  if(args[0] && args[0]!=='mismatch') setTimeout(()=>A.openPayment(args[0]),50);
}});

A.on('pay-open', el => A.openPayment(el.dataset.id));
A.openPayment = async (id) => {
  let ctx = A.state.payRows && A.state.payRows[id];
  if(!ctx){
    const p = await A.q(sb.from('payments').select('*').eq('id',id).maybeSingle()); if(!p){ A.toast('تراکنش پیدا نشد','err'); return; }
    const us = await A.byIds('academy_users','id,full_name,username,phone,email',[p.user_id]); const cs = await A.courses();
    ctx = { p, u:us[p.user_id], c:cs.find(c=>c.id===p.course_id) };
  }
  const { p, u, c } = ctx;
  const acc = await sb.from('course_access').select('id,is_active,payment_id,granted_at').eq('user_id',p.user_id).eq('course_id',p.course_id).maybeSingle();
  const hasAccess = acc.data && acc.data.is_active!==false;
  const m = A.modal({ drawer:true, title:'جزئیات تراکنش', body: html`
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px">${A.payBadge(p.status)}<b style="font-size:20px">${A.money(p.amount)}</b></div>
    ${p.status==='paid' && !hasAccess ? html`<div class="alert err">${A.icon('alert')}<div>پرداخت موفق است ولی هنرجو به این دوره دسترسی ندارد.</div></div>`:''}
    ${p.status==='pending' && p.track_id ? html`<div class="alert warn">${A.icon('clock')}<div>این تراکنش هنوز «معلق» است. اگر هنرجو واقعاً پرداخت کرده، با دکمه‌ی «بررسی از زیبال» وضعیت را قطعی کن.</div></div>`:''}
    <dl class="kv">
      <dt>هنرجو</dt><dd>${u?html`<a href="#/students/${u.id}" data-close-drawer>${A.userName(u)}</a> <span class="mono muted">${u.phone||''}</span>`:'—'}</dd>
      <dt>دوره</dt><dd>${c?c.title:'—'}</dd>
      <dt>تاریخ ایجاد</dt><dd>${A.dt(p.created_at)}</dd>
      <dt>تاریخ پرداخت</dt><dd>${p.paid_at?A.dt(p.paid_at):'—'}</dd>
      <dt>کد رهگیری (track)</dt><dd class="mono">${p.track_id||'—'} ${p.track_id?html`<button class="btn ghost sm" data-act="copy" data-v="${p.track_id}">${A.icon('copy')}</button>`:''}</dd>
      <dt>شماره مرجع</dt><dd class="mono">${p.ref_number||'—'}</dd>
      <dt>شماره کارت</dt><dd class="mono">${p.card_number||'—'}</dd>
      <dt>دسترسی دوره</dt><dd>${hasAccess?A.badge('فعال','ok'):A.badge('ندارد','err')}</dd>
      <dt>شناسه‌ی داخلی</dt><dd class="mono muted" style="font-size:11px">${p.id}</dd>
    </dl>
    <div id="pdMsg" style="margin-top:14px"></div>`,
    footer: html`${p.status==='pending'&&p.track_id?html`<button class="btn primary" id="pdVerify">${A.icon('refresh')} بررسی از زیبال</button>`:''}${p.status==='paid'&&!hasAccess?html`<button class="btn primary" id="pdGrant">${A.icon('key')} اعطای دسترسی</button>`:''}${p.status==='pending'&&!p.track_id?html`<button class="btn" id="pdFail">علامت «ناموفق»</button>`:''}<button class="btn" data-close2>بستن</button>` });
  m.$('[data-close2]').onclick = ()=>m.close(true);
  m.$$('[data-close-drawer]').forEach(a=>a.addEventListener('click',()=>m.close(true)));
  const v = m.$('#pdVerify'); if(v) v.onclick = ev => A.busy(ev.currentTarget, async ()=>{
    const out = await A.verifyTrack(p.track_id);
    m.$('#pdMsg').innerHTML = `<div class="alert ${out.ok?'ok':'warn'}">${esc(out.text)}</div>`;
    if(out.ok){ A.audit('verify_payment','payments',p.id,{track:p.track_id}); }
  });
  const g = m.$('#pdGrant'); if(g) g.onclick = async ev => A.busy(ev.currentTarget, async ()=>{
    const ex = await A.q(sb.from('course_access').select('id').eq('user_id',p.user_id).eq('course_id',p.course_id).maybeSingle());
    if(ex) await A.q(sb.from('course_access').update({is_active:true,payment_id:p.id}).eq('id',ex.id));
    else await A.q(sb.from('course_access').insert([{user_id:p.user_id,course_id:p.course_id,payment_id:p.id,is_active:true}]));
    A.audit('fix_access','payments',p.id,{}); A.toast('دسترسی فعال شد ✓','ok'); m.close(true); A.rerender();
  });
  const f = m.$('#pdFail'); if(f) f.onclick = async () => {
    if(!await A.confirm({title:'علامت‌گذاری ناموفق',message:'این تراکنش (بدون کد رهگیری، یعنی هرگز به درگاه نرسیده) «ناموفق» شود؟'})) return;
    await A.q(sb.from('payments').update({status:'failed'}).eq('id',p.id)); A.audit('mark_failed','payments',p.id,{}); m.close(true); A.rerender();
  };
};
A.on('copy', el => A.copy(el.dataset.v));
// verify.php خودش با زیبال تماس می‌گیرد؛ تکراری‌بودن مشکلی ایجاد نمی‌کند (idempotent)
A.verifyTrack = async (track) => {
  try{
    const r = await fetch('/verify.php?trackId='+encodeURIComponent(track), {cache:'no-store'}); const d = await r.json();
    if(d.ok) return { ok:true, text: d.already ? 'قبلاً تأیید شده بود ✓' : 'پرداخت در زیبال موفق بود؛ وضعیت قطعی شد و دسترسی دوره فعال شد ✓' };
    return { ok:false, text: 'نتیجه: '+(d.error||'ناموفق') };
  }catch(e){ return { ok:false, text:'اتصال به verify.php ناموفق: '+A.errMsg(e) }; }
};
A.on('pay-reconcile', async () => {
  const lim = new Date(Date.now()-15*60000).toISOString();
  const rows = await A.fetchAll(()=>sb.from('payments').select('id,track_id,created_at').eq('status','pending').not('track_id','is',null).lt('created_at',lim).order('created_at',{ascending:false}));
  if(!rows.length){ A.toast('تراکنش معلقی برای بررسی نیست ✓','ok'); return; }
  if(!await A.confirm({title:'بررسی معلق‌ها از زیبال', confirm:`بررسی ${A.fa(rows.length)} تراکنش`,
    message:`برای ${A.fa(rows.length)} تراکنش معلق (قدیمی‌تر از ۱۵ دقیقه) وضعیت واقعی از زیبال پرسیده می‌شود.\n• اگر پرداخت شده باشد → موفق می‌شود، دسترسی دوره فعال می‌شود و پیامک خرید می‌رود.\n• اگر پرداخت نشده باشد → «ناموفق» می‌شود.\nاین کار بی‌خطر است و هر وقت لازم بود می‌توان تکرارش کرد.` })) return;
  const m = A.modal({ title:'در حال بررسی…', size:'sm', noEsc:true, body: html`<div class="progress"><i id="rcBar"></i></div><p id="rcTxt" class="muted" style="margin-top:12px"></p>` });
  let ok=0, fail=0, err=0;
  for(let i=0;i<rows.length;i++){
    const r = await A.verifyTrack(rows[i].track_id); if(r.ok) ok++; else if(/ناموفق|کد/.test(r.text)) fail++; else err++;
    m.$('#rcBar').style.width = Math.round((i+1)/rows.length*100)+'%'; m.$('#rcTxt').textContent = `${A.fa(i+1)} از ${A.fa(rows.length)} — موفق: ${A.fa(ok)} · ناموفق: ${A.fa(fail)} · خطای اتصال: ${A.fa(err)}`;
  }
  A.audit('reconcile_payments','payments',null,{checked:rows.length,ok,fail,err});
  m.close(true); A.toast(`تمام شد: ${A.fa(ok)} موفق، ${A.fa(fail)} ناموفق${err?`، ${A.fa(err)} خطا`:''}`,'ok'); A.rerender();
});

/* ====================== دسترسی‌ها ====================== */
A.route('access', { title:'دسترسی‌ها و اعطای دستی', async render(box){
  const st = A.state.access = A.state.access || { q:'', course:'', inactive:false, page:1 };
  const courses = await A.courses();
  A.mount(box, html`
    <div class="page-h"><div class="sp"></div><button class="btn primary" data-act="grant">${A.icon('plus')} اعطای دسترسی جدید</button></div>
    <div class="toolbar">
      <input class="in sm" id="aq" style="max-width:260px" placeholder="موبایل یا نام هنرجو…" value="${st.q}">
      <select class="in sm" id="ac" style="width:200px"><option value="">همه‌ی دوره‌ها</option>${courses.map(c=>html`<option value="${c.id}">${c.title}</option>`)}</select>
      <label class="switch"><input type="checkbox" id="ai" ${st.inactive?'checked':''}><i></i><span>فقط لغوشده‌ها</span></label>
    </div><div id="accList"></div>`);
  $('#ac').value = st.course;
  const apply = ()=>{ st.q=$('#aq').value.trim(); st.course=$('#ac').value; st.inactive=$('#ai').checked; st.page=1; list(); };
  $('#aq').oninput = A.debounce(apply,350); $('#ac').onchange = apply; $('#ai').onchange = apply;
  A.handlers.page = el => { st.page=+el.dataset.p; list(); };
  async function list(){
    const out = $('#accList'); out.innerHTML='<div class="loading"><span class="spin"></span></div>';
    let uids=null;
    if(st.q){ const v=A.toEn(st.q).replace(/[%,()]/g,''); const ur=await sb.from('academy_users').select('id').or(`phone.ilike.%${v}%,full_name.ilike.%${v}%,email.ilike.%${v}%`).limit(300); uids=(ur.data||[]).map(x=>x.id); }
    let q = sb.from('course_access').select('id,user_id,course_id,granted_at,is_active,payment_id',{count:'exact'});
    q = st.inactive ? q.eq('is_active',false) : q.neq('is_active',false);
    if(st.course) q=q.eq('course_id',st.course);
    if(uids) q = uids.length ? q.in('user_id',uids) : q.eq('user_id','00000000-0000-0000-0000-000000000000');
    const r = await q.order('granted_at',{ascending:false}).range((st.page-1)*PS, st.page*PS-1); if(r.error) throw r.error;
    const rows = r.data||[]; const us = await A.byIds('academy_users','id,full_name,username,phone,email',rows.map(x=>x.user_id)); const cm = Object.fromEntries(courses.map(c=>[c.id,c]));
    A.mount(out, html`${A.table([
      {h:'هنرجو', c:a=>A.userLink(us[a.user_id], a.user_id)},
      {h:'دوره', c:a=>cm[a.course_id]?.title||'—'},
      {h:'منبع', c:a=>a.payment_id?A.badge('خرید آنلاین','ok'):A.badge('اعطای دستی','acc')},
      {h:'تاریخ', c:a=>A.date(a.granted_at)},
      {h:'', c:a=>a.is_active===false ? html`<button class="btn sm" data-act="acc-on" data-id="${a.id}">فعال‌سازی مجدد</button>` : html`<button class="btn sm danger" data-act="acc-off" data-id="${a.id}">لغو دسترسی</button>`},
    ], rows, {empty:'موردی نیست'})}${A.pager(r.count||0, st.page, PS)}`);
  }
  list();
}});
A.on('acc-off', async el => {
  if(!await A.confirm({title:'لغو دسترسی',message:'دسترسی این هنرجو به این دوره لغو شود؟ (هر وقت خواستی می‌توانی دوباره فعالش کنی)',confirm:'لغو دسترسی',danger:true})) return;
  await A.q(sb.from('course_access').update({is_active:false}).eq('id',el.dataset.id)); A.audit('revoke_access','course_access',el.dataset.id,{}); A.toast('دسترسی لغو شد'); A.rerender();
});
A.on('acc-on', async el => { await A.q(sb.from('course_access').update({is_active:true}).eq('id',el.dataset.id)); A.audit('restore_access','course_access',el.dataset.id,{}); A.toast('دسترسی فعال شد ✓','ok'); A.rerender(); });

/* ====================== سفارش‌های لایسنسی (قدیمی) ====================== */
A.route('licenses', { title:'سفارش‌های لایسنسی', async render(box){
  const st = A.state.lic = A.state.lic || { status:'' };
  A.mount(box, html`<div class="alert info">${A.icon('help')}<div>این بخش مربوط به سیستم قدیمی خرید با لایسنس/ربات است. اگر دیگر استفاده نمی‌شود، خالی می‌ماند.</div></div>
    <div class="toolbar"><select class="in sm" id="ls" style="width:170px"><option value="">همه</option><option value="pending">در انتظار</option><option value="approved">تأیید شده</option><option value="rejected">رد شده</option></select></div><div id="licList"></div>`);
  $('#ls').value = st.status; $('#ls').onchange = ()=>{ st.status=$('#ls').value; list(); };
  async function list(){
    let q = sb.from('course_orders').select('*,academy_users(full_name,phone),courses(title)').order('created_at',{ascending:false}).limit(300);
    if(st.status) q=q.eq('status',st.status);
    const r = await q; const out=$('#licList');
    if(r.error){ A.mount(out, html`<div class="empty">${A.missing(r.error)?'این جدول وجود ندارد.':A.errMsg(r.error)}</div>`); return; }
    const sm = {pending:['در انتظار','warn'],approved:['تأیید','ok'],rejected:['رد','err']};
    A.mount(out, A.table([
      {h:'هنرجو', c:o=>o.academy_users?.full_name||o.full_name||'—'},{h:'موبایل', c:o=>html`<span class="mono">${o.academy_users?.phone||o.phone||'—'}</span>`},
      {h:'دوره', c:o=>o.courses?.title||'—'},{h:'وضعیت', c:o=>A.badge((sm[o.status]||[o.status])[0],(sm[o.status]||[0,'mute'])[1])},
      {h:'لایسنس', c:o=>html`<span class="mono">${o.license_key||'—'}</span>`},{h:'تاریخ', c:o=>A.date(o.created_at)},
      {h:'', c:o=>o.status==='pending'?html`<button class="btn sm primary" data-act="lic-ok" data-id="${o.id}">تأیید</button> <button class="btn sm danger" data-act="lic-no" data-id="${o.id}">رد</button>`:''} ], r.data||[], {empty:'سفارشی وجود ندارد'}));
  }
  list();
}});
async function licAct(id, approved){
  if(!await A.confirm({title:approved?'تأیید سفارش':'رد سفارش', message:approved?'سفارش تأیید و لایسنس ارسال شود؟':'این سفارش رد شود؟', danger:!approved})) return;
  const { data:{ session } } = await sb.auth.getSession();
  const r = await fetch(SUPABASE_URL+'/functions/v1/academy-bot?action=approve',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(session?.access_token||'')},body:JSON.stringify({order_id:id,approved})});
  if(r.ok){ A.audit(approved?'license_approve':'license_reject','course_orders',id,{}); A.toast(approved?'تأیید شد و لایسنس ارسال شد':'رد شد','ok'); A.refreshCounts(); A.rerender(); } else A.toast('خطا در پردازش','err');
}
A.on('lic-ok', el=>licAct(el.dataset.id,true)); A.on('lic-no', el=>licAct(el.dataset.id,false));
})();
