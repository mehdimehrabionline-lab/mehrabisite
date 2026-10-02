/* ==========================================================
   محتوای سایت: متن‌ها و تماس/پرداخت، بنر و نشان‌ها، ظاهر، نمونه‌کارها، آثار AI،
   نظرات هنرجویان، سؤالات متداول
   همه‌ی داده‌ها همان جدول‌ها و کلیدهای پنل قبلی هستند.
   ========================================================== */
(function(){
'use strict';
const { html, esc, $, $$ } = A;

/* ---------- ابزار عمومی site_content ---------- */
async function getKeys(keys){
  const r = await sb.from('site_content').select('key,value,value_en').in('key', keys); if(r.error) throw r.error;
  const m = {}; (r.data||[]).forEach(x=>m[x.key]=x); return m;
}
async function putKeys(rows){ const r = await sb.from('site_content').upsert(rows,{onConflict:'key'}); if(r.error) throw r.error; }
function field(f, val, valEn){
  const id = 'k-'+f.k, tag = f.area ? 'textarea' : 'input';
  const one = (id, v, ltr) => f.area ? html`<textarea class="in ${ltr?'ltr':''}" id="${id}" style="min-height:${f.h||80}px" placeholder="${f.ph||''}">${v||''}</textarea>` : html`<input class="in ${ltr||f.ltr?'ltr':''}" id="${id}" value="${v||''}" placeholder="${f.ph||''}">`;
  return html`<div class="fld"><label class="lb">${f.l}</label>${one(id,val,false)}${f.en?html`<label class="lb" style="margin-top:8px">${f.l} — English</label>${one(id+'_en',valEn,true)}`:''}${f.help?html`<div class="help">${f.help}</div>`:''}</div>`;
}
function collect(fields){ return fields.map(f=>{ const row = { key:f.k, value:$('#k-'+f.k).value.trim() }; if(f.en) row.value_en = $('#k-'+f.k+'_en').value.trim()||null; return row; }); }
function dirtyOn(root, st){ $$('input,textarea,select',root).forEach(e=>e.addEventListener('input',()=>{ st.d=true; })); A.dirty = ()=>st.d; }

async function formCard(box, {title, hint, fields, after}){
  const m = await getKeys(fields.flatMap(f=>[f.k]));
  const st = {d:false};
  A.mount(box, html`<div class="card"><h3>${title}<span class="sp"></span><button class="btn primary" data-save>${A.icon('check')} ذخیره</button></h3>${hint?html`<p class="hint">${hint}</p>`:''}${fields.map(f=>field(f, m[f.k]?.value, m[f.k]?.value_en))}</div>`);
  dirtyOn(box, st);
  $('[data-save]',box).onclick = ev => A.busy(ev.currentTarget, async ()=>{ await putKeys(collect(fields)); st.d=false; A.dirty=null; A.audit('site_content',title,null,{keys:fields.map(f=>f.k)}); A.toast('ذخیره شد ✓ (تا ۱ دقیقه روی سایت می‌نشیند)','ok'); A.syncCache(['site_content']); });
}

/* ====================== متن‌ها و تماس ====================== */
const TEXTS = [
  {k:'nav_brand',l:'نام برند بالای سایت (کنار لوگو)'},{k:'hero_eyebrow_badge',l:'نشان بالای هیرو (مثلاً ✓ اینماد تأییدشده)'},{k:'hero_tag',l:'برچسب کوچک بالای تیتر هیرو'},
  {k:'courses_eyebrow_custom',l:'برچسب بخش دوره‌ها'},{k:'courses_title',l:'عنوان اصلی بخش دوره‌ها'},{k:'about_eyebrow_custom',l:'برچسب بخش درباره'},{k:'about_title',l:'عنوان اصلی بخش درباره'},
  {k:'work_eyebrow_custom',l:'برچسب بخش نمونه‌کارها'},{k:'work_title',l:'عنوان اصلی بخش نمونه‌کارها'},{k:'assistant_eyebrow_custom',l:'برچسب بخش دستیار هوشمند'},{k:'assistant_title',l:'عنوان اصلی بخش دستیار هوشمند'},
  {k:'footer_credit',l:'متن پایین صفحه (فوتر)'},{k:'testimonials_eyebrow',l:'برچسب بخش نظرات هنرجویان'},{k:'testimonials_title',l:'عنوان بخش نظرات هنرجویان'},{k:'faq_eyebrow',l:'برچسب بخش سؤالات متداول'},{k:'faq_title',l:'عنوان بخش سؤالات متداول'} ];
const HOME = [
  {k:'hero_title',l:'تیتر اصلی هیرو (می‌توانی از em استفاده کنی)',en:1},{k:'hero_lead',l:'متن معرفی هیرو',area:1,en:1},
  {k:'about_p1',l:'پاراگراف اول «درباره»',area:1,en:1},{k:'about_p2',l:'پاراگراف دوم «درباره»',area:1,en:1},
  {k:'stat_projects',l:'آمار: پروژه'},{k:'stat_brands',l:'آمار: برند'},{k:'stat_years',l:'آمار: سال تجربه'} ];
const CONTACT = [
  {k:'contact_eyebrow',l:'برچسب کوچک بالای عنوان تماس',ph:'۰۷ — تماس'},{k:'contact_title',l:'عنوان اصلی تماس',ph:'بیا باهم کار کنیم.'},{k:'contact_lead',l:'متن توضیح زیر عنوان',area:1},
  {k:'contact_cta_text',l:'متن دکمه CTA',ph:'ثبت‌نام و خرید دوره ←'},{k:'contact_location',l:'موقعیت مکانی',ph:'📍 شیراز، ایران'},
  {k:'contact_phone',l:'تلفن (واتساپ/تلگرام)',ltr:1},{k:'contact_email',l:'ایمیل تماس'},{k:'contact_instagram',l:'اینستاگرام (بدون @)'},{k:'telegram_bot',l:'آدرس ربات تلگرام خرید (بدون @)',ltr:1,ph:'mehrabi_ai_bot'} ];
const PAYSET = [ {k:'card_number',l:'شماره کارت',ltr:1,ph:'6219-8610-XXXX-XXXX'},{k:'card_owner',l:'نام صاحب کارت'},{k:'payment_guide',l:'متن راهنمای پرداخت (زیر شماره کارت نمایش داده می‌شود)',area:1} ];

A.route('site', { title:'متن‌ها و اطلاعات تماس', async render(box, args){
  const tabs = [['home','صفحه‌ی اصلی'],['titles','عنوان بخش‌ها'],['contact','تماس'],['pay','اطلاعات پرداخت دستی']];
  const t = args[0] || 'home';
  A.mount(box, html`<div class="tabs">${tabs.map(x=>html`<a class="tab ${x[0]===t?'active':''}" href="#/site/${x[0]}">${x[1]}</a>`)}</div><div id="sbody"></div>`);
  const b = $('#sbody');
  if(t==='home') await formCard(b,{title:'متن‌های صفحه‌ی اصلی',hint:'هر متن دو خانه دارد: فارسی و English. خانه‌ی انگلیسی خالی باشد، نسخه‌ی فارسی نشان داده می‌شود.',fields:HOME});
  else if(t==='titles') await formCard(b,{title:'برچسب‌ها و عنوان بخش‌ها',fields:TEXTS});
  else if(t==='contact') await formCard(b,{title:'بخش تماس و راه‌های ارتباطی',fields:CONTACT});
  else await formCard(b,{title:'اطلاعات پرداخت (کارت‌به‌کارت)',fields:PAYSET});
}});

/* ====================== بنر و نشان‌ها ====================== */
A.route('promo', { title:'بنر و نشان‌های اعتماد', async render(box){
  const m = await getKeys(['promo_enabled','promo_text','promo_link','promo_link_text','hero_badges_extra']);
  let badges = []; try{ badges = JSON.parse(m.hero_badges_extra?.value||'[]'); }catch(e){} if(!badges.length) badges=[''];
  const st = {d:false};
  A.mount(box, html`<div class="grid g2" style="align-items:start">
    <div class="card"><h3>بنر تبلیغاتی بالای سایت<span class="sp"></span><button class="btn primary" id="pSave">${A.icon('check')} ذخیره</button></h3>
      <div class="fld"><label class="switch"><input type="checkbox" id="pOn" ${m.promo_enabled?.value==='true'?'checked':''}><i></i><span>نمایش بنر</span></label></div>
      <div class="fld"><label class="lb">متن بنر</label><input class="in" id="pText" value="${m.promo_text?.value||''}"></div>
      <div class="row2"><div class="fld"><label class="lb">متن لینک</label><input class="in" id="pLT" value="${m.promo_link_text?.value||''}"></div><div class="fld"><label class="lb">آدرس لینک</label><input class="in ltr" id="pL" value="${m.promo_link?.value||''}"></div></div></div>
    <div class="card"><h3>نشان‌های اعتماد هیرو<span class="sp"></span><button class="btn primary" id="bSave">${A.icon('check')} ذخیره</button></h3><p class="hint">کنار «اینماد» بالای صفحه‌ی اصلی نمایش داده می‌شوند.</p><div id="bList">${badges.map(b=>html`<div style="display:flex;gap:8px;margin-bottom:8px" data-row><input class="in" value="${b}" placeholder="مثلاً ✓ ۲۵۰+ هنرجو"><button class="btn" data-act="b-del">حذف</button></div>`)}</div><button class="btn" id="bAdd">${A.icon('plus')} نشان جدید</button></div></div>`);
  dirtyOn(box, st);
  A.on('b-del', el=>{ el.closest('[data-row]').remove(); st.d=true; });
  $('#bAdd').onclick = ()=>{ $('#bList').insertAdjacentHTML('beforeend','<div style="display:flex;gap:8px;margin-bottom:8px" data-row><input class="in" placeholder="مثلاً ✓ ۲۵۰+ هنرجو"><button class="btn" data-act="b-del">حذف</button></div>'); };
  $('#pSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{ await putKeys([{key:'promo_enabled',value:$('#pOn').checked?'true':'false'},{key:'promo_text',value:$('#pText').value.trim()},{key:'promo_link',value:$('#pL').value.trim()},{key:'promo_link_text',value:$('#pLT').value.trim()}]); st.d=false; A.audit('promo_save','site_content'); A.toast('بنر ذخیره شد ✓','ok'); A.syncCache(['site_content']); });
  $('#bSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{ const v=$$('#bList input').map(i=>i.value.trim()).filter(Boolean); await putKeys([{key:'hero_badges_extra',value:JSON.stringify(v)}]); st.d=false; A.audit('badges_save','site_content'); A.toast('نشان‌ها ذخیره شد ✓','ok'); A.syncCache(['site_content']); });
}});

/* ====================== ظاهر و تایپوگرافی ====================== */
const GCOL = [{key:'st_accent',label:'رنگ اصلی (طلایی)',def:'#c98a5a'},{key:'st_bg',label:'پس‌زمینه',def:'#0c0b09'},{key:'st_ink',label:'متن اصلی',def:'#f4f1ea'},{key:'st_muted',label:'متن کم‌رنگ',def:'#8f897e'}];
const SECT = [{id:'hero',label:'تیتر اصلی صفحه',s:64,c:'#f4f1ea'},{id:'hero_sub',label:'زیرتیتر صفحه اصلی',s:16,c:'#d8d3c8'},{id:'section_h2',label:'تیتر بخش‌ها',s:44,c:'#f4f1ea'},{id:'eyebrow',label:'برچسب بالای بخش‌ها',s:11,c:'#c98a5a'},
  {id:'body',label:'متن عادی',s:16,c:'#d8d3c8'},{id:'course_title',label:'عنوان دوره‌ها',s:20,c:'#f4f1ea'},{id:'course_price',label:'قیمت دوره‌ها',s:22,c:'#c98a5a'},{id:'work_title',label:'عنوان نمونه‌کارها',s:18,c:'#f4f1ea'},
  {id:'nav',label:'منوی بالای سایت',s:13,c:'#8f897e'},{id:'footer',label:'متن پایین سایت',s:12,c:'#8f897e'}];
const WEIGHTS = [['','پیش‌فرض'],['300','نازک'],['400','معمولی'],['500','متوسط'],['600','ضخیم'],['700','خیلی ضخیم']];
const styleKeys = ()=>[...GCOL.map(c=>c.key), ...SECT.flatMap(s=>[`sty_${s.id}_size`,`sty_${s.id}_color`,`sty_${s.id}_weight`])];
A.route('style', { title:'ظاهر و تایپوگرافی سایت', async render(box){
  const m = await getKeys(styleKeys()); const val=(k,d)=>m[k]?.value||d; const st={d:false};
  A.mount(box, html`<div class="card"><h3>رنگ‌های کلی<span class="sp"></span><button class="btn" id="sReset">بازگشت به پیش‌فرض</button><button class="btn primary" id="sSave">${A.icon('check')} ذخیره</button></h3>
    <div class="grid g4">${GCOL.map(c=>html`<div class="fld"><label class="lb">${c.label}</label><div style="display:flex;gap:8px"><input type="color" id="${c.key}" value="${val(c.key,c.def)}" style="width:44px;height:38px;padding:2px;border-radius:8px;background:var(--panel2);border:1px solid var(--line)"><input class="in ltr mono" id="${c.key}_hex" value="${val(c.key,c.def)}"></div></div>`)}</div></div>
    <div class="card"><h3>اندازه و رنگ متن هر بخش</h3>${SECT.map(s=>html`<div class="row3 tg" style="align-items:end;padding:10px 0;border-bottom:1px solid var(--line)"><div style="padding-bottom:8px">${s.label}</div>
      <div><label class="lb">اندازه (px)</label><input class="in ltr" id="sty_${s.id}_size" value="${val(`sty_${s.id}_size`,s.s)}" inputmode="numeric"></div>
      <div><label class="lb">رنگ</label><input type="color" id="sty_${s.id}_color" value="${val(`sty_${s.id}_color`,s.c)}" style="width:100%;height:38px;padding:2px;border-radius:8px;background:var(--panel2);border:1px solid var(--line)"></div>
      <div><label class="lb">ضخامت</label><select class="in" id="sty_${s.id}_weight">${WEIGHTS.map(w=>html`<option value="${w[0]}" ${w[0]===val(`sty_${s.id}_weight`,'')?'selected':''}>${w[1]}</option>`)}</select></div></div>`)}</div>`);
  dirtyOn(box, st);
  GCOL.forEach(c=>{ const p=$('#'+c.key), h=$('#'+c.key+'_hex'); p.oninput=()=>h.value=p.value; h.oninput=()=>{ if(/^#[0-9a-f]{6}$/i.test(h.value)) p.value=h.value; }; });
  $('#sSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{
    const rows=[]; styleKeys().forEach(k=>{ const el=$('#'+k); if(el && el.value!=='') rows.push({key:k,value:A.toEn(el.value)}); });
    await putKeys(rows); st.d=false; A.dirty=null; A.audit('style_save','site_content'); A.toast(`${A.fa(rows.length)} تنظیم ذخیره شد ✓`,'ok'); A.syncCache(['site_content']);
  });
  $('#sReset').onclick = async ()=>{ if(!await A.confirm({title:'بازگشت به پیش‌فرض',message:'همه‌ی تنظیمات ظاهری به حالت پیش‌فرض برگردد؟',danger:true})) return; await A.q(sb.from('site_content').delete().in('key',styleKeys())); A.audit('style_reset','site_content'); A.toast('به حالت پیش‌فرض برگشت ✓','ok'); A.syncCache(['site_content']); A.rerender(); };
}});

/* ====================== سؤالات متداول ====================== */
A.route('faqs', { title:'سؤالات متداول', async render(box){
  const r = await sb.from('faqs').select('*').order('sort_order',{ascending:true});
  if(r.error){ A.mount(box, html`<div class="alert err">${A.missing(r.error)?'جدول faqs ساخته نشده.':A.errMsg(r.error)}</div>`); return; }
  A.state.faqs = r.data||[];
  A.mount(box, html`<div class="toolbar"><div class="sp"></div><button class="btn primary" data-act="faq-edit">${A.icon('plus')} سؤال جدید</button></div>
    ${A.table([{h:'سؤال',c:f=>f.question},{h:'وضعیت',c:f=>f.is_published?A.badge('نمایش','ok'):A.badge('مخفی','mute')},{h:'ترتیب',c:f=>A.fa(f.sort_order||0)},
      {h:'',c:f=>html`<button class="btn sm" data-act="faq-edit" data-id="${f.id}">${A.icon('edit')}</button> <button class="btn sm danger" data-act="faq-del" data-id="${f.id}">${A.icon('trash')}</button>`}], A.state.faqs, {empty:'هنوز سؤالی اضافه نشده'})}`);
}});
A.on('faq-edit', el=>{
  const f = (A.state.faqs||[]).find(x=>x.id===el.dataset.id) || {};
  const m = A.modal({ title:f.id?'ویرایش سؤال':'سؤال جدید', body: html`<div class="fld"><label class="lb">سؤال *</label><input class="in" id="fq" value="${f.question||''}"></div><div class="fld"><label class="lb">جواب *</label><textarea class="in" id="fa" style="min-height:130px">${f.answer||''}</textarea></div>
    <div class="row2"><div class="fld"><label class="lb">ترتیب</label><input class="in num" id="fs" value="${A.fa(f.sort_order||0)}"></div><div class="fld"><label class="lb">&nbsp;</label><label class="switch"><input type="checkbox" id="fp" ${f.is_published!==false?'checked':''}><i></i><span>نمایش در سایت</span></label></div></div>`,
    footer: html`<button class="btn" data-x>انصراف</button><button class="btn primary" id="fok">ذخیره</button>`, dirty:()=>false });
  m.$('[data-x]').onclick=()=>m.close();
  m.$('#fok').onclick = ev=>A.busy(ev.currentTarget, async ()=>{
    const p={question:m.$('#fq').value.trim(),answer:m.$('#fa').value.trim(),sort_order:A.num(m.$('#fs').value)||0,is_published:m.$('#fp').checked}; if(!p.question||!p.answer){ A.toast('سؤال و جواب الزامی است','err'); return; }
    await A.q(f.id?sb.from('faqs').update(p).eq('id',f.id):sb.from('faqs').insert([p])); A.audit('faq_save','faqs',f.id); A.toast('ذخیره شد ✓','ok'); A.syncCache(['faqs']); m.close(true); A.rerender();
  });
});
A.on('faq-del', async el=>{ if(!await A.confirm({title:'حذف سؤال',message:'این سؤال حذف شود؟',danger:true})) return; await A.q(sb.from('faqs').delete().eq('id',el.dataset.id)); A.audit('faq_delete','faqs',el.dataset.id); A.syncCache(['faqs']); A.rerender(); });

/* ====================== نظرات هنرجویان ====================== */
A.route('testimonials', { title:'نظرات هنرجویان', async render(box){
  const st = A.state.tst = A.state.tst || { f:'' };
  const r = await sb.from('testimonials').select('*').order('is_published',{ascending:true}).order('created_at',{ascending:false});
  if(r.error){ A.mount(box, html`<div class="alert err">${A.missing(r.error)?'جدول testimonials ساخته نشده.':A.errMsg(r.error)}</div>`); return; }
  A.state.tstRows = r.data||[]; const pend = A.state.tstRows.filter(t=>!t.is_published).length;
  const list = A.state.tstRows.filter(t=>st.f==='pending'?!t.is_published : st.f==='pub'?t.is_published : true);
  A.mount(box, html`<div class="toolbar"><div class="seg">${[['','همه'],['pending','در انتظار تأیید'+(pend?' ('+A.fa(pend)+')':'')],['pub','منتشرشده']].map(x=>html`<button data-act="tf" data-k="${x[0]}" class="${x[0]===st.f?'on':''}">${x[1]}</button>`)}</div><div class="sp"></div><button class="btn primary" data-act="tst-edit">${A.icon('plus')} نظر جدید</button></div>
    ${A.table([{h:'نام',c:t=>html`<b>${t.name||'—'}</b><div class="muted" style="font-size:12px">${t.role||''}</div>`},{h:'دوره',c:t=>t.course||'—'},{h:'نظر',c:t=>html`<div style="max-width:340px;font-size:13px">${(t.content||'').slice(0,140)}${(t.content||'').length>140?'…':''}</div>`},
      {h:'امتیاز',c:t=>html`<span style="color:var(--accent)">${'★'.repeat(t.rating||5)}</span>`},{h:'وضعیت',c:t=>t.is_published?A.badge('نمایش','ok'):A.badge('در انتظار','warn')},
      {h:'',c:t=>html`${!t.is_published?html`<button class="btn sm primary" data-act="tst-pub" data-id="${t.id}" data-on="0">تأیید</button> `:html`<button class="btn sm" data-act="tst-pub" data-id="${t.id}" data-on="1">مخفی</button> `}<button class="btn sm" data-act="tst-edit" data-id="${t.id}">${A.icon('edit')}</button> <button class="btn sm danger" data-act="tst-del" data-id="${t.id}">${A.icon('trash')}</button>`}], list, {empty:'نظری نیست'})}`);
}});
A.on('tf', el=>{ A.state.tst.f=el.dataset.k; A.rerender(); });
A.on('tst-pub', async el=>{ await A.q(sb.from('testimonials').update({is_published:el.dataset.on!=='1'}).eq('id',el.dataset.id)); A.audit('tst_publish','testimonials',el.dataset.id,{on:el.dataset.on!=='1'}); A.syncCache(['testimonials']); A.refreshCounts(); A.rerender(); });
A.on('tst-del', async el=>{ const t=A.state.tstRows.find(x=>x.id===el.dataset.id); if(!await A.confirm({title:'حذف نظر',message:'این نظر حذف شود؟',danger:true})) return; await A.q(sb.from('testimonials').delete().eq('id',t.id)); A.removeStored(t.avatar_url); A.audit('tst_delete','testimonials',t.id); A.syncCache(['testimonials']); A.refreshCounts(); A.rerender(); });
A.on('tst-edit', el=>{
  const t = (A.state.tstRows||[]).find(x=>x.id===el.dataset.id) || {}; let file=null;
  const m = A.modal({ title:t.id?'ویرایش نظر':'نظر جدید', body: html`<div class="row2"><div class="fld"><label class="lb">نام *</label><input class="in" id="tn" value="${t.name||''}"></div><div class="fld"><label class="lb">شغل / عنوان</label><input class="in" id="tr" value="${t.role||''}"></div></div>
    <div class="fld"><label class="lb">دوره</label><input class="in" id="tc" value="${t.course||''}"></div><div class="fld"><label class="lb">متن نظر *</label><textarea class="in" id="tt" style="min-height:110px">${t.content||''}</textarea></div>
    <div class="row3"><div class="fld"><label class="lb">امتیاز</label><select class="in" id="tra">${[5,4,3,2,1].map(n=>html`<option value="${n}" ${n===(t.rating||5)?'selected':''}>${'★'.repeat(n)}</option>`)}</select></div><div class="fld"><label class="lb">ترتیب</label><input class="in num" id="ts" value="${A.fa(t.sort_order||0)}"></div><div class="fld"><label class="lb">&nbsp;</label><label class="switch"><input type="checkbox" id="tp" ${t.is_published!==false?'checked':''}><i></i><span>نمایش</span></label></div></div>
    <div class="fld"><label class="lb">لینک ویدیوی نظر (اختیاری)</label><input class="in ltr" id="tv" value="${t.video_url||''}"></div>
    <div class="fld"><label class="lb">عکس</label><div class="drop ${t.avatar_url?'has':''}" id="tdrop">${t.avatar_url?'✓ عکس موجود — برای تغییر کلیک کن':'انتخاب عکس'}</div><input type="file" id="tf" accept="image/*" class="hide"></div>`,
    footer: html`<button class="btn" data-x>انصراف</button><button class="btn primary" id="tok">ذخیره</button>` });
  m.$('[data-x]').onclick=()=>m.close(); m.$('#tdrop').onclick=()=>m.$('#tf').click(); m.$('#tf').onchange=e=>{ file=e.target.files[0]; if(file){ m.$('#tdrop').textContent='✓ '+file.name; m.$('#tdrop').classList.add('has'); } };
  m.$('#tok').onclick = ev=>A.busy(ev.currentTarget, async ()=>{
    const p={name:m.$('#tn').value.trim(),role:m.$('#tr').value.trim()||null,course:m.$('#tc').value.trim()||null,content:m.$('#tt').value.trim(),video_url:m.$('#tv').value.trim()||null,rating:+m.$('#tra').value||5,sort_order:A.num(m.$('#ts').value)||0,is_published:m.$('#tp').checked};
    if(!p.name||!p.content){ A.toast('نام و متن نظر الزامی است','err'); return; } if(file) p.avatar_url = await A.uploadImage(file,'testimonials');
    await A.q(t.id?sb.from('testimonials').update(p).eq('id',t.id):sb.from('testimonials').insert([p])); A.audit('tst_save','testimonials',t.id); A.syncCache(['testimonials']); A.refreshCounts(); m.close(true); A.rerender();
  });
});

/* ====================== نمونه‌کارها و آثار هوش مصنوعی ====================== */
function portfolio(section, title){
  A.route(section==='ai'?'ai-works':'portfolio', { title, async render(box){
    const r = await sb.from('projects').select('*').order('sort_order',{ascending:true}).order('created_at',{ascending:false});
    if(r.error){ A.mount(box, html`<div class="alert err">${A.errMsg(r.error)}</div>`); return; }
    const rows = (r.data||[]).filter(p=>(p.section||'work')===section); A.state.proj = r.data||[];
    A.mount(box, html`<div class="toolbar"><span class="muted">${A.fa(rows.length)} مورد</span><div class="sp"></div><button class="btn primary" data-act="proj-edit" data-s="${section}">${A.icon('plus')} افزودن</button></div>
      <div class="mgrid" style="grid-template-columns:repeat(auto-fill,minmax(210px,1fr))">${rows.length?rows.map(p=>html`<div class="mitem"><div class="im" style="${p.media_type!=='video'&&p.media_url?`background-image:url('${p.media_url}')`:''}">${p.media_type==='video'?A.icon('video'):(p.media_url?'':'بدون تصویر')}</div><div class="m"><b style="font-size:13px">${p.title}</b><div class="muted">${[p.category,p.year].filter(Boolean).join(' · ')}</div>
        <div style="display:flex;gap:6px;margin-top:8px"><button class="btn sm" data-act="proj-edit" data-id="${p.id}" data-s="${section}">${A.icon('edit')}</button><button class="btn sm danger" data-act="proj-del" data-id="${p.id}">${A.icon('trash')}</button></div></div></div>`):html`<div class="empty" style="grid-column:1/-1">موردی اضافه نشده</div>`}</div>`);
  }});
}
portfolio('work','نمونه‌کارها'); portfolio('ai','آثار هوش مصنوعی');
A.on('proj-del', async el=>{ const p=A.state.proj.find(x=>x.id===el.dataset.id); if(!await A.confirm({title:'حذف',message:`«${p.title}» حذف شود؟`,danger:true})) return; await A.q(sb.from('projects').delete().eq('id',p.id)); A.removeStored(p.media_url); A.audit('project_delete','projects',p.id,{title:p.title}); A.syncCache(['projects']); A.rerender(); });
A.on('proj-edit', el=>{
  const p = (A.state.proj||[]).find(x=>x.id===el.dataset.id) || {}; const section = p.section || el.dataset.s || 'work'; let file=null;
  const m = A.modal({ title:p.id?'ویرایش':(section==='ai'?'افزودن اثر هوش مصنوعی':'افزودن نمونه‌کار'), size:'lg', body: html`
    <div class="row2"><div class="fld"><label class="lb">عنوان *</label><input class="in" id="mt" value="${p.title||''}"></div><div class="fld"><label class="lb">Title (English)</label><input class="in ltr" id="mte" value="${p.title_en||''}"></div></div>
    <div class="row3"><div class="fld"><label class="lb">دسته</label><input class="in" id="mc" value="${p.category||''}"></div><div class="fld"><label class="lb">Category</label><input class="in ltr" id="mce" value="${p.category_en||''}"></div><div class="fld"><label class="lb">سال</label><input class="in" id="my" value="${p.year||''}"></div></div>
    <div class="row2"><div class="fld"><label class="lb">توضیح</label><textarea class="in" id="md">${p.description||''}</textarea></div><div class="fld"><label class="lb">Description</label><textarea class="in ltr" id="mde">${p.description_en||''}</textarea></div></div>
    <div class="row3"><div class="fld"><label class="lb">نوع</label><select class="in" id="mm"><option value="image" ${p.media_type!=='video'?'selected':''}>عکس</option><option value="video" ${p.media_type==='video'?'selected':''}>ویدیو (آپارات/یوتیوب)</option></select></div>
      <div class="fld"><label class="lb">جهت</label><select class="in" id="mo"><option value="landscape" ${p.orientation!=='portrait'?'selected':''}>افقی ۱۶:۹</option><option value="portrait" ${p.orientation==='portrait'?'selected':''}>عمودی ۹:۱۶</option></select></div>
      <div class="fld"><label class="lb">لینک (اختیاری)</label><input class="in ltr" id="ml" value="${p.link||''}"></div></div>
    <div id="mImg" class="fld"><label class="lb">تصویر</label><div class="drop ${p.media_url&&p.media_type!=='video'?'has':''}" id="mdrop">${p.media_url&&p.media_type!=='video'?'✓ تصویر موجود — برای تغییر کلیک کن':'انتخاب تصویر'}</div><input type="file" id="mf" accept="image/*" class="hide"></div>
    <div id="mVid" class="fld hide"><label class="lb">لینک ویدیو</label><input class="in ltr" id="mv" value="${p.media_type==='video'?(p.media_url||''):''}" placeholder="https://www.aparat.com/v/…"></div>`,
    footer: html`<button class="btn" data-x>انصراف</button><button class="btn primary" id="mok">ذخیره</button>` });
  const tog = ()=>{ const v=m.$('#mm').value==='video'; m.$('#mImg').classList.toggle('hide',v); m.$('#mVid').classList.toggle('hide',!v); }; m.$('#mm').onchange=tog; tog();
  m.$('[data-x]').onclick=()=>m.close(); m.$('#mdrop').onclick=()=>m.$('#mf').click(); m.$('#mf').onchange=e=>{ file=e.target.files[0]; if(file){ m.$('#mdrop').textContent='✓ '+file.name; m.$('#mdrop').classList.add('has'); } };
  m.$('#mok').onclick = ev=>A.busy(ev.currentTarget, async ()=>{
    const title=m.$('#mt').value.trim(); if(!title){ A.toast('عنوان لازم است','err'); return; } const video=m.$('#mm').value==='video';
    let url = video ? (m.$('#mv').value.trim()||null) : (p.media_type!=='video'?p.media_url:null)||null; if(!video && file) url = await A.uploadImage(file, section);
    const pay={title,title_en:m.$('#mte').value.trim()||null,category:m.$('#mc').value.trim(),category_en:m.$('#mce').value.trim()||null,year:m.$('#my').value.trim(),description:m.$('#md').value.trim(),description_en:m.$('#mde').value.trim()||null,media_type:m.$('#mm').value,media_url:url,orientation:m.$('#mo').value,link:m.$('#ml').value.trim()||null,section};
    await A.q(p.id?sb.from('projects').update(pay).eq('id',p.id):sb.from('projects').insert([pay])); A.audit('project_save','projects',p.id,{title}); A.syncCache(['projects']); A.toast('ذخیره شد ✓','ok'); m.close(true); A.rerender();
  });
});
})();
