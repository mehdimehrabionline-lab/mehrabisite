/* ==========================================================
   داشبورد
   ========================================================== */
(function(){
'use strict';
const { html, $ } = A;

A.route('dashboard', { title:'داشبورد', async render(box){
  const today = A.todayStart(), d7 = A.addDays(today,-6), m0 = A.jalaliMonthStart(), d30 = A.addDays(today,-29);
  let paid = [], payOk = true;
  try{ paid = await A.fetchAll(()=>sb.from('payments').select('id,user_id,course_id,amount,paid_at,created_at,status').eq('status','paid').order('created_at',{ascending:false})); }
  catch(e){ payOk = false; }
  const at = p => new Date(p.paid_at||p.created_at);
  const sum = (arr)=>arr.reduce((s,p)=>s+p.amount,0);
  const inR = (from)=>paid.filter(p=>at(p)>=from);
  const t = inR(today), w = inR(d7), mo = inR(m0);
  const [newUsers, pendLive, failToday, cmPend, ordNew, tstPend, licPend, pendStale] = await Promise.all([
    A.count('academy_users', q=>q.gte('created_at',d7.toISOString())),
    A.count('payments', q=>q.eq('status','pending').not('track_id','is',null).gte('created_at',new Date(Date.now()-3600000).toISOString())),
    A.count('payments', q=>q.eq('status','failed').gte('created_at',today.toISOString())),
    A.count('comments', q=>q.eq('is_approved',false)), A.count('orders', q=>q.eq('status','new')),
    A.count('testimonials', q=>q.eq('is_published',false)), A.count('course_orders', q=>q.eq('status','pending')),
    A.count('payments', q=>q.eq('status','pending').not('track_id','is',null).lt('created_at',new Date(Date.now()-900000).toISOString())) ]);
  const mism = payOk ? await A.pay.mismatches(paid.filter(p=>at(p)>=d30)) : [];
  const byDay = {}; paid.forEach(p=>{ const k=A.dayKey(at(p)); byDay[k]=(byDay[k]||0)+p.amount; });
  const days=[]; for(let i=0;i<30;i++){ const d=A.addDays(d30,i); days.push({label:A.date(d),short:A.dshort(d),value:byDay[A.dayKey(d)]||0}); }
  const recent = paid.slice(0,8);
  const us = await A.byIds('academy_users','id,full_name,username,phone', recent.map(p=>p.user_id));
  const cs = await A.courses(); const cm = Object.fromEntries(cs.map(c=>[c.id,c]));
  const top = {}; paid.forEach(p=>{ const x=top[p.course_id]=top[p.course_id]||{n:0,s:0}; x.n++; x.s+=p.amount; });
  const topRows = Object.entries(top).sort((a,b)=>b[1].s-a[1].s).slice(0,5);
  const attn = [];
  if(mism.length) attn.push(['err',`${A.fa(mism.length)} پرداخت موفق بدون دسترسی دوره (۳۰ روز اخیر)`,'payments/mismatch','بررسی']);
  if(pendStale) attn.push(['warn',`${A.fa(pendStale)} تراکنش معلق که ممکن است پرداخت شده باشد`,'payments','بررسی از زیبال']);
  if(licPend) attn.push(['warn',`${A.fa(licPend)} سفارش لایسنسی در انتظار تأیید`,'licenses','مشاهده']);
  if(ordNew) attn.push(['info',`${A.fa(ordNew)} پیام/سفارش جدید از فرم تماس`,'inbox','صندوق پیام']);
  if(cmPend) attn.push(['info',`${A.fa(cmPend)} دیدگاه بلاگ در انتظار تأیید`,'inbox','صندوق پیام']);
  if(tstPend) attn.push(['info',`${A.fa(tstPend)} نظر هنرجو در انتظار تأیید`,'testimonials','نظرات']);
  A.mount(box, html`
    ${!payOk?html`<div class="alert warn">${A.icon('alert')}<div>خواندن جدول payments ممکن نبود. فایل <b>admin-upgrade.sql</b> را در Supabase اجرا کن؛ تا آن موقع آمار فروش خالی است.</div></div>`:''}
    <div class="grid g4 keep2" style="margin-bottom:18px">
      <div class="kpi accent"><div class="l">${A.icon('cash')} فروش امروز</div><div class="v">${A.fa(sum(t))}<small>تومان</small></div><div class="s">${A.fa(t.length)} فروش</div></div>
      <div class="kpi"><div class="l">${A.icon('chart')} ۷ روز اخیر</div><div class="v">${A.fa(sum(w))}<small>تومان</small></div><div class="s">${A.fa(w.length)} فروش</div></div>
      <div class="kpi"><div class="l">${A.icon('chart')} این ماه (شمسی)</div><div class="v">${A.fa(sum(mo))}<small>تومان</small></div><div class="s">${A.fa(mo.length)} فروش</div></div>
      <div class="kpi"><div class="l">${A.icon('users')} هنرجوی جدید (۷ روز)</div><div class="v">${newUsers==null?'—':A.fa(newUsers)}</div><div class="s">${pendLive?`${A.fa(pendLive)} نفر الان در حال پرداخت`:''}${failToday?` · ${A.fa(failToday)} ناموفق امروز`:''}</div></div>
    </div>
    <div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:18px;align-items:start" id="dgrid">
      <div>
        <div class="card"><h3>درآمد ۳۰ روز اخیر (تومان)</h3>${A.chartBars(days)}</div>
        <div class="card"><h3>آخرین فروش‌ها <span class="sp"></span><a class="btn sm" href="#/payments">همه ←</a></h3>${A.table([
          {h:'هنرجو', c:p=>A.userLink(us[p.user_id],p.user_id)},{h:'دوره', c:p=>cm[p.course_id]?.title||'—'},
          {h:'مبلغ', c:p=>A.money(p.amount), cls:'num'},{h:'زمان', c:p=>A.ago(at(p)), cls:'nowrap'} ], recent, {empty:'هنوز فروشی ثبت نشده'})}</div>
      </div>
      <div>
        <div class="card"><h3>نیازمند توجه</h3>${attn.length ? attn.map(a=>html`<div class="alert ${a[0]}" style="align-items:center">${A.icon(a[0]==='err'?'alert':'inbox')}<div style="flex:1">${a[1]}</div><a class="btn sm" href="#/${a[2]}">${a[3]}</a></div>`) : html`<div class="alert ok">${A.icon('check')} همه‌چیز مرتب است</div>`}</div>
        <div class="card"><h3>پرفروش‌ترین دوره‌ها</h3>${topRows.length? topRows.map(r=>html`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line)"><span>${cm[r[0]]?.title||'—'}</span><span class="muted">${A.fa(r[1].n)} فروش</span></div>`) : html`<div class="muted">—</div>`}</div>
        <div class="card"><h3>میان‌بُر</h3><div style="display:flex;flex-direction:column;gap:8px">
          <button class="btn" data-act="grant">${A.icon('key')} اعطای دسترسی دستی</button>
          <a class="btn" href="#/courses">${A.icon('book')} دوره‌ها و جلسات</a>
          <a class="btn" href="#/blog/new">${A.icon('pen')} نوشتن پست جدید</a>
          <button class="btn" data-act="cache-refresh">${A.icon('refresh')} تازه‌سازی کش سایت</button></div></div>
      </div>
    </div>`);
  if(innerWidth<900) $('#dgrid').style.gridTemplateColumns='minmax(0,1fr)';
}});
})();
