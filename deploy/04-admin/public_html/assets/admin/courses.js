/* ==========================================================
   دوره‌ها: فهرست، ویرایشگر چندتبی، جلسات (کشیدن‌وارها)، پیوست‌ها، آپلود تکه‌ای
   ========================================================== */
(function(){
'use strict';
const { html, raw, esc, $, $$ } = A;
const UPLOAD_HOST = window.MM_UPLOAD_HOST !== undefined ? window.MM_UPLOAD_HOST : 'https://upload.mehdimehrabi.ir';
const LEVELS = ['مقدماتی','متوسط','پیشرفته','مقدماتی تا پیشرفته','همه سطوح'];
const MODES = ['حضوری','آنلاین','حضوری و آنلاین'];
const TAGS = ['آموزش','پرفروش','ویژه','تخصصی','جدید','رایگان'];

/* ---------- آپلود فایل به هاست ---------- */
async function token(){ const { data:{ session } } = await sb.auth.getSession(); return session?.access_token || ''; }
const sleep = ms => new Promise(r=>setTimeout(r,ms));
A.fmtSpeed = bps => A.fa((bps/1048576).toFixed(1))+' MB/s';

// آپلود یک‌تکه‌ای (روش قدیمیِ آزموده) — مناسب فایل‌های کوچک
function simpleUpload(file, {url, field, onProgress, signal}){
  return new Promise(async (res, rej)=>{
    const form = new FormData(); form.append(field, file);
    const xhr = new XMLHttpRequest(); xhr.open('POST', url);
    xhr.setRequestHeader('Authorization','Bearer '+await token());
    xhr.upload.onprogress = e=>{ if(e.lengthComputable && onProgress) onProgress(e.loaded, e.total); };
    xhr.onload = ()=>{ try{ const d = JSON.parse(xhr.responseText); d.ok ? res(d) : rej(new Error(d.error||'خطا در آپلود')); }catch(e){ rej(new Error('پاسخ نامعتبر از سرور (حجم بیش از حد مجاز؟)')); } };
    xhr.onerror = ()=>rej(new Error('خطای شبکه')); xhr.onabort = ()=>rej(new Error('لغو شد'));
    if(signal) signal.addEventListener('abort', ()=>xhr.abort());
    xhr.send(form);
  });
}
// آپلود تکه‌ای با ادامه‌ی خودکار بعد از قطعی
async function chunkUpload(file, {kind, onProgress, signal}){
  const ext = (file.name.split('.').pop()||'').toLowerCase();
  let base = UPLOAD_HOST;
  const call = async (fields, blob) => {
    const f = new FormData(); Object.entries(fields).forEach(([k,v])=>f.append(k,v)); if(blob) f.append('chunk', blob, 'c.bin');
    const r = await fetch(base+'/admin-chunk-upload.php',{method:'POST',headers:{Authorization:'Bearer '+await token()},body:f,signal});
    let d; try{ d = await r.json(); }catch(e){ throw new Error('پاسخ نامعتبر از سرور ('+r.status+')'); }
    return { d, status:r.status };
  };
  let init;
  try{ init = await call({action:'init',kind,ext,size:file.size}); }
  catch(e){ if(e.name==='AbortError') throw new Error('لغو شد'); base = ''; init = await call({action:'init',kind,ext,size:file.size}); }   // اگر ساب‌دامین آپلود در دسترس نبود، از خود سایت
  if(!init.d.ok) throw new Error(init.d.error||'شروع آپلود ناموفق');
  const id = init.d.upload_id; const CH = 8*1024*1024; let pos = 0, fails = 0; const t0 = Date.now(); let startPos = 0;
  while(pos < file.size){
    if(signal && signal.aborted){ throw new Error('لغو شد'); }
    const blob = file.slice(pos, Math.min(pos+CH, file.size));
    try{
      const { d, status } = await call({action:'chunk',upload_id:id,offset:pos}, blob);
      if(d.ok){ pos = d.next; fails = 0; }
      else if(status===409 && typeof d.next==='number'){ pos = d.next; }      // سرور جلوتر/عقب‌تر بود؛ هم‌گام شو
      else throw new Error(d.error||'خطا');
      if(onProgress) onProgress(pos, file.size, (pos-startPos)/Math.max(1,(Date.now()-t0)/1000));
    }catch(e){
      if(e.name==='AbortError' || (signal&&signal.aborted)) throw new Error('لغو شد');
      if(++fails > 8){ throw new Error('اتصال قطع شد و ادامه ممکن نشد: '+e.message+' — دوباره همین فایل را انتخاب کن (از ابتدا شروع نمی‌شود).'); }
      if(onProgress) onProgress(pos, file.size, 0, 'قطع شد؛ تلاش مجدد… ('+A.fa(fails)+')');
      await sleep(Math.min(1000*2**fails, 15000));
      try{ const s = await call({action:'status',upload_id:id}); if(s.d.ok) pos = s.d.next; }catch(_){}
    }
  }
  const fin = await call({action:'finish',upload_id:id});
  if(!fin.d.ok) throw new Error(fin.d.error||'نهایی‌سازی ناموفق');
  return fin.d;
}
// نقطه‌ی ورود: خودش بر اساس حجم روش مناسب را انتخاب می‌کند
A.uploadHost = async (file, {kind='video', onProgress, signal, forceChunk=false}={}) => {
  const LIMIT = 150*1024*1024;
  if(!forceChunk && file.size <= LIMIT){
    const url = UPLOAD_HOST + (kind==='attach' ? '/upload-attachment.php' : '/upload-video.php');
    return simpleUpload(file,{url, field: kind==='attach'?'file':'video', onProgress:(a,b)=>onProgress&&onProgress(a,b), signal});
  }
  return chunkUpload(file,{kind,onProgress,signal});
};

/* بررسی فایل ویدیو قبل از آپلود: مدت، و اینکه برای پخش آنلاین بهینه (faststart) شده یا نه */
A.probeVideo = async (file) => {
  const out = { duration:null, faststart:null };
  try{
    const url = URL.createObjectURL(file);
    out.duration = await new Promise(res=>{ const v=document.createElement('video'); v.preload='metadata'; v.muted=true; const t=setTimeout(()=>res(null),6000); v.onloadedmetadata=()=>{ clearTimeout(t); res(isFinite(v.duration)?v.duration:null); }; v.onerror=()=>{ clearTimeout(t); res(null); }; v.src=url; });
    URL.revokeObjectURL(url);
  }catch(e){}
  if(/\.(mp4|m4v)$/i.test(file.name)){
    try{
      let off = 0;
      for(let i=0;i<16 && off < file.size;i++){
        const buf = new DataView(await file.slice(off, off+16).arrayBuffer()); if(buf.byteLength<8) break;
        let size = buf.getUint32(0); const type = String.fromCharCode(buf.getUint8(4),buf.getUint8(5),buf.getUint8(6),buf.getUint8(7));
        if(type==='moov'){ out.faststart = true; break; } if(type==='mdat'){ out.faststart = false; break; }
        if(size===1 && buf.byteLength>=16) size = Number(buf.getBigUint64(8)); if(size<8) break; off += size;
      }
    }catch(e){}
  }
  return out;
};
A.durText = sec => {
  if(sec==null) return ''; sec = Math.round(sec);
  if(sec<60) return A.fa(sec)+' ثانیه';
  const m = Math.round(sec/60); if(m<60) return A.fa(m)+' دقیقه';
  const h = Math.floor(m/60), r = m%60; return A.fa(h)+' ساعت'+(r?' و '+A.fa(r)+' دقیقه':'');
};

/* تصویر: کوچک‌سازی خودکار (حداکثر ۱۶۰۰px، webp) و آپلود به Storage سوپابیس */
A.prepImage = async (file, maxW=1600) => {
  if(!/^image\/(jpeg|png|webp)$/.test(file.type)) return file;       // svg/gif دست‌نخورده
  try{
    const bmp = await createImageBitmap(file); if(bmp.width<=maxW && file.size<400*1024) return file;
    const sc = Math.min(1, maxW/bmp.width); const c = document.createElement('canvas'); c.width=Math.round(bmp.width*sc); c.height=Math.round(bmp.height*sc);
    c.getContext('2d').drawImage(bmp,0,0,c.width,c.height);
    const blob = await new Promise(r=>c.toBlob(r,'image/webp',0.86)); if(!blob || blob.size>=file.size) return file;
    return new File([blob], file.name.replace(/\.[^.]+$/,'')+'.webp', {type:'image/webp'});
  }catch(e){ return file; }
};
A.uploadImage = async (file, folder) => {
  const f = await A.prepImage(file); const ext = (f.name.split('.').pop()||'jpg').toLowerCase();
  const path = `${folder}/${Date.now()}-${Math.random().toString(36).slice(2,7)}.${ext}`;
  const { error } = await sb.storage.from('media').upload(path, f, {cacheControl:'31536000', upsert:false}); if(error) throw error;
  return sb.storage.from('media').getPublicUrl(path).data.publicUrl;
};
A.removeStored = url => { if(!url) return; const i=url.indexOf('/media/'); if(i>=0) sb.storage.from('media').remove([url.slice(i+7).split('?')[0]]).catch(()=>{}); };
A.syncCache = async (types=['courses','course_full','projects','posts','site_content','testimonials','faqs']) => {
  let ok = 0; for(const t of types){ try{ await fetch(`/api/cache.php?type=${t}&refresh=1&_=${Date.now()}`,{cache:'no-store'}); ok++; }catch(e){} } return ok;
};
// بعد از ذخیره‌ی دوره: چک می‌کند توضیحات تازه واقعاً روی سایت آمده باشد
async function syncCourse(id, expectedDesc){
  for(let a=0;a<3;a++){
    await A.syncCache(['courses','course_full']); await sleep(a===0?2500:6000);
    if(!id || typeof expectedDesc!=='string') return;
    try{ const r = await fetch(`/api/cache.php?type=course_full&_=${Date.now()}`,{cache:'no-store'}); const l = await r.json(); const c = Array.isArray(l)?l.find(x=>x.id===id):null; if(!c || (c.description||'')===expectedDesc){ A.toast('روی سایت اعمال شد ✓','ok'); return; } }catch(e){}
  }
  A.toast('ذخیره شد ولی سایت هنوز نسخه‌ی قبلی را نشان می‌دهد؛ از «وضعیت سیستم» کش را تازه کن و چند ثانیه بعد صفحه را رفرش کن.','err');
}
A.on('cache-refresh', async el => { const n = await A.busy(el, ()=>A.syncCache()); A.toast(`کش سایت تازه شد ✓ (${A.fa(n)} بخش)`,'ok'); });

/* ====================== فهرست دوره‌ها ====================== */
A.route('courses', { title:a=>a[0]?(a[0]==='new'?'دوره‌ی جدید':'ویرایش دوره'):'دوره‌ها و جلسات', nav:'courses', async render(box, args){
  if(args[0]) return editor(box, args[0], args[1]);
  const st = A.state.crs = A.state.crs || { f:'' };
  const r = await sb.from('courses').select('*').order('sort_order',{ascending:true}).order('created_at',{ascending:false});
  if(r.error){ A.mount(box, html`<div class="alert err">${A.missing(r.error)?'جدول courses ساخته نشده است.':A.errMsg(r.error)}</div>`); return; }
  const all = r.data||[];
  const [acc, vids, paid] = await Promise.all([
    sb.from('course_access').select('course_id').neq('is_active',false), sb.from('course_videos').select('course_id'),
    sb.from('payments').select('course_id,amount').eq('status','paid') ]);
  const cnt = (rows)=>{ const m={}; (rows.data||[]).forEach(x=>m[x.course_id]=(m[x.course_id]||0)+1); return m; };
  const sA = cnt(acc), sV = cnt(vids); const rev = {}; (paid.data||[]).forEach(p=>rev[p.course_id]=(rev[p.course_id]||0)+p.amount);
  const list = all.filter(c=> st.f==='active'?c.is_active!==false : st.f==='inactive'?c.is_active===false : true);
  A.mount(box, html`
    <div class="toolbar"><div class="seg">${[['','همه'],['active','فعال'],['inactive','غیرفعال / آرشیو']].map(x=>html`<button data-act="cf" data-k="${x[0]}" class="${x[0]===st.f?'on':''}">${x[1]}</button>`)}</div><div class="sp"></div>
      <button class="btn primary" data-act="nav" data-to="courses/new">${A.icon('plus')} دوره‌ی جدید</button></div>
    <div class="cgrid">${list.length ? list.map(c=>html`
      <div class="ccard ${c.is_active===false?'off':''}">
        <div class="cv" style="${c.cover_url?`background-image:url('${c.cover_url}')`:''}">${c.is_active===false?A.badge('غیرفعال','mute'):c.is_featured?A.badge('ویژه','acc'):''}</div>
        <div class="bd"><h4>${c.title||'—'}</h4>
          <div class="muted" style="font-size:12px;margin-bottom:8px">${[c.tag,c.level,c.mode].filter(Boolean).join(' · ')}</div>
          <div style="color:var(--accent);font-weight:600">${c.price_amount?A.money(c.price_amount):(c.price||'قیمت تنظیم نشده')}${c.discount_active?html` <span class="badge b-err">تخفیف</span>`:''}</div>
          <div class="muted" style="font-size:12px;margin-top:8px;display:flex;gap:14px;flex-wrap:wrap"><span>${A.fa(sA[c.id]||0)} هنرجو</span><span>${A.fa(sV[c.id]||0)} جلسه</span><span>${A.money(rev[c.id]||0)} فروش</span></div></div>
        <div class="ft"><a class="btn sm primary" href="#/courses/${c.id}">${A.icon('edit')} ویرایش</a><a class="btn sm" href="#/courses/${c.id}/lessons">${A.icon('video')} جلسات</a>
          <button class="btn sm" data-act="grant" data-course="${c.id}">${A.icon('key')}</button>
          <button class="btn sm" data-act="c-toggle" data-id="${c.id}" data-on="${c.is_active===false?'0':'1'}">${c.is_active===false?'فعال‌سازی':'غیرفعال'}</button></div>
      </div>`) : html`<div class="empty" style="grid-column:1/-1">دوره‌ای نیست</div>`}</div>`);
}});
A.on('cf', el => { A.state.crs.f = el.dataset.k; A.rerender(); });
A.on('nav', el => A.go(el.dataset.to));
A.on('c-toggle', async el => {
  const on = el.dataset.on==='1';
  if(on && !await A.confirm({title:'غیرفعال‌سازی دوره',message:'دوره از سایت پنهان می‌شود (هنرجوهای فعلی همچنان به آن دسترسی دارند). ادامه؟'})) return;
  await A.q(sb.from('courses').update({is_active:!on}).eq('id',el.dataset.id)); A.audit(on?'course_off':'course_on','courses',el.dataset.id,{});
  A.toast(on?'دوره غیرفعال شد':'دوره فعال شد ✓','ok'); A.syncCache(['courses','course_full']); A.rerender();
});

/* ====================== ویرایشگر دوره ====================== */
async function editor(box, id, tab){
  const isNew = id==='new';
  let c = {};
  if(!isNew){ c = await A.q(sb.from('courses').select('*').eq('id',id).maybeSingle()); if(!c){ A.mount(box, html`<div class="alert err">دوره پیدا نشد.</div>`); return; } }
  const st = { cover:null, introMode:'form', dirty:false, introFile:c.intro_video_file||'' };
  A.dirty = () => st.dirty;
  const tabs = [['info','اطلاعات'],['price','قیمت و تخفیف'],['lessons','جلسات و ویدیوها'],['content','سرفصل‌ها'],['en','English'],['pub','انتشار']];
  const sel = (arr, v) => arr.map(x=>html`<option ${x===v?'selected':''}>${x}</option>`);
  const fld = (label, id, val, o={}) => html`<div class="fld"><label class="lb">${label}</label>${o.area?html`<textarea class="in" id="${id}" style="min-height:${o.h||110}px" placeholder="${o.ph||''}">${val||''}</textarea>`:html`<input class="in ${o.ltr?'ltr':''} ${o.num?'num':''}" id="${id}" value="${val==null?'':val}" placeholder="${o.ph||''}" ${o.ml?`maxlength="${o.ml}"`:''}>`}${o.help?html`<div class="help">${o.help}</div>`:''}</div>`;
  A.mount(box, html`
    <div class="page-h"><a class="btn ghost" href="#/courses">‹ دوره‌ها</a><h2>${isNew?'دوره‌ی جدید':c.title}</h2><div class="sp"></div>
      ${!isNew?html`<a class="btn" target="_blank" rel="noopener" href="/c/${c.slug||''}">${A.icon('eye')} مشاهده در سایت</a>`:''}
      <button class="btn primary" id="cSave">${A.icon('check')} ذخیره</button></div>
    <div class="tabs" id="cTabs">${tabs.map(t=>html`<div class="tab" data-t="${t[0]}">${t[1]}</div>`)}</div>

    <div data-p="info"><div class="grid g2" style="align-items:start">
      <div class="card"><h3>اطلاعات اصلی</h3>
        ${fld('عنوان دوره *','cTitle',c.title)}
        <div class="fld"><label class="lb">آدرس صفحه (slug)</label><input class="in ltr" id="cSlug" value="${c.slug||''}" placeholder="hoosh-masnoee-jame"><div class="help">آدرس دوره در سایت: <span class="mono" id="slugPrev"></span></div></div>
        <div class="row3"><div class="fld"><label class="lb">برچسب</label><select class="in" id="cTag">${sel(TAGS,c.tag||'آموزش')}</select></div>
          <div class="fld"><label class="lb">سطح</label><select class="in" id="cLevel">${sel(LEVELS,c.level||'مقدماتی تا پیشرفته')}</select></div>
          <div class="fld"><label class="lb">نحوه‌ی برگزاری</label><select class="in" id="cMode">${sel(MODES,c.mode||'حضوری و آنلاین')}</select></div></div>
        ${fld('توضیح دوره','cDesc',c.description,{area:1,h:150})}
        <div class="row3">${fld('مدت دوره','cDuration',c.duration,{ph:'مثلاً ۱۲ جلسه، ۲۴ ساعت'})}${fld('تاریخ شروع','cStart',c.start_date,{ph:'مثلاً شهریور ۱۴۰۴'})}${fld('ظرفیت (نفر)','cCap',c.capacity?A.fa(c.capacity):'',{num:1})}</div></div>
      <div><div class="card"><h3>تصویر بنر</h3><div class="drop ${c.cover_url?'has':''}" id="coverDrop">${c.cover_url?'برای تغییر تصویر کلیک کن':'برای انتخاب تصویر کلیک کن (به‌طور خودکار بهینه می‌شود)'}</div><input type="file" id="coverFile" accept="image/*" class="hide"><img id="coverPrev" src="${c.cover_url||''}" style="${c.cover_url?'':'display:none;'}width:100%;border-radius:10px;margin-top:12px;max-height:200px;object-fit:cover" alt=""></div>
        <div class="card"><h3>ویدیوی معرفی (رایگان، روی صفحه‌ی دوره)</h3>
          ${fld('لینک آپارات / یوتیوب','cVideoUrl',c.video_url,{ltr:1,ph:'https://www.aparat.com/v/xxxxx',help:'اگر لینک بدهی، همین نمایش داده می‌شود؛ وگرنه فایل آپلودی زیر.'})}
          <div class="fld"><label class="lb">یا فایل ویدیو روی هاست</label><div class="seg" style="margin-bottom:10px"><button type="button" data-im="form" class="on">آپلود از اینجا</button><button type="button" data-im="ftp">نام فایل (FTP)</button></div>
            <div id="imForm"><div class="drop" id="introDrop">${c.intro_video_file?'✓ فایل فعلی: '+c.intro_video_file+' — برای تغییر کلیک کن':'انتخاب ویدیو (MP4/WebM)'}</div><input type="file" id="introFile" accept="video/mp4,video/webm" class="hide"><div class="progress hide" id="introPg"><i></i></div></div>
            <div id="imFtp" class="hide"><input class="in ltr" id="introFtp" placeholder="teaser.mp4 (فایل داخل assets/intro)"></div></div></div>
        <div class="card"><h3>دکمه‌ی ثبت‌نام</h3>${fld('لینک دکمه','cLink',c.link,{ltr:1,ph:'#contact یا https://…',help:'خالی بماند، دکمه‌ی خرید آنلاین عادی کار می‌کند.'})}${('sms_title' in c)?fld('عنوان کوتاه برای پیامک خرید','cSms',c.sms_title,{help:'حداکثر ۲۵ کاراکتر؛ در پیامک موفقیت خرید به‌جای عنوان کامل دوره می‌آید.',ml:25}):''}</div></div></div></div>

    <div data-p="price" class="hide"><div class="grid g2" style="align-items:start"><div class="card"><h3>قیمت</h3>
        ${fld('قیمت فروش (تومان) *','cAmount',c.price_amount?A.fa(c.price_amount):'',{num:1,ltr:1,ph:'۷۹۰۰۰۰۰',help:'همین مبلغ به درگاه پرداخت داده می‌شود. این تنها منبع قیمت واقعی است.'})}
        <div class="fld"><label class="lb">متن نمایشی قیمت (روی کارت دوره)</label><div style="display:flex;gap:8px"><input class="in" id="cPriceTxt" value="${c.price||''}" placeholder="۷٬۹۰۰٬۰۰۰ تومان"><button class="btn" type="button" id="priceFromAmt">از روی مبلغ بساز</button></div><div class="help" id="priceWarn"></div></div>
        <div class="card" style="background:var(--panel2);margin:0"><h3>تخفیف</h3>
          <div class="fld"><label class="switch"><input type="checkbox" id="cDisc" ${c.discount_active?'checked':''}><i></i><span>تخفیف فعال باشد</span></label></div>
          <div id="discBox" class="${c.discount_active?'':'hide'}"><div class="row2">${fld('قیمت اصلی قبل از تخفیف (تومان)','cOrig',c.original_price_amount?A.fa(c.original_price_amount):'',{num:1,ltr:1})}${fld('درصد تخفیف','cPct',c.discount_percent?A.fa(c.discount_percent):'',{num:1,ltr:1})}</div>
          ${fld('عنوان تخفیف','cDiscLbl',c.discount_label,{ph:'مثلاً جشنواره‌ی پاییزه'})}<div class="alert info" id="discCalc"></div></div></div></div>
      <div class="card"><h3>پیش‌نمایش روی سایت</h3><div id="pricePrev"></div></div></div></div>

    <div data-p="lessons" class="hide"><div id="lessonsBox"></div></div>

    <div data-p="content" class="hide"><div class="card"><h3>سرفصل‌ها</h3><p class="hint">هر سرفصل در یک خط جدا نوشته شود.</p><textarea class="in" id="cSyl" style="min-height:260px" placeholder="مقدمات هوش مصنوعی&#10;ابزارهای تولید تصویر&#10;ساخت ویدیو با AI">${c.syllabus||''}</textarea></div></div>

    <div data-p="en" class="hide"><div class="card"><h3>نسخه‌ی انگلیسی (اختیاری)</h3>${fld('Title','cTitleEn',c.title_en,{ltr:1})}${fld('Description','cDescEn',c.description_en,{area:1,ltr:1})}${fld('Price text','cPriceEn',c.price_en,{ltr:1,ph:'Contact us'})}</div></div>

    <div data-p="pub" class="hide"><div class="grid g2" style="align-items:start"><div class="card"><h3>وضعیت انتشار</h3>
        <div class="fld"><label class="switch"><input type="checkbox" id="cActive" ${c.is_active!==false?'checked':''}><i></i><span>فعال و قابل‌مشاهده در سایت</span></label></div>
        <div class="fld"><label class="switch"><input type="checkbox" id="cFeat" ${c.is_featured?'checked':''}><i></i><span>دوره‌ی ویژه (نشان ⭐)</span></label></div>
        ${fld('ترتیب نمایش','cSort',A.fa(c.sort_order||0),{num:1,help:'عدد کوچک‌تر بالاتر نمایش داده می‌شود.'})}</div>
      ${!isNew?html`<div class="card"><h3>منطقه‌ی خطر</h3><p class="hint">حذف دوره فقط وقتی ممکن است که هیچ هنرجو یا پرداختی نداشته باشد. در غیر این صورت «غیرفعال کردن» امن‌ترین کار است.</p><button class="btn danger" id="cDelete">${A.icon('trash')} حذف دوره</button></div>`:''}</div></div>`);

  const v = i => $('#'+i).value;
  const mark = () => { st.dirty = true; };
  $$('[data-p] input,[data-p] textarea,[data-p] select').forEach(e=>{ if(e.type!=='file') e.addEventListener('input',mark); e.addEventListener('change',mark); });
  const show = t => { $$('#cTabs .tab').forEach(x=>x.classList.toggle('active',x.dataset.t===t)); $$('[data-p]').forEach(p=>p.classList.toggle('hide',p.dataset.p!==t)); if(t==='lessons') lessons(c, isNew); };
  $$('#cTabs .tab').forEach(x=>x.onclick=()=>show(x.dataset.t)); show(['info','price','lessons','content','en','pub'].includes(tab)?tab:'info');
  // اسلاگ
  const slugPrev = ()=>{ $('#slugPrev').textContent = '/c/'+(A.cleanSlug(v('cSlug'))||'…'); }; slugPrev();
  $('#cSlug').addEventListener('input',slugPrev);
  $('#cTitle').addEventListener('blur',()=>{ if(!v('cSlug').trim() && v('cTitle').trim()){ $('#cSlug').value = A.slug(v('cTitle')); slugPrev(); } });
  // تصویر
  $('#coverDrop').onclick = ()=>$('#coverFile').click();
  $('#coverFile').onchange = e=>{ const f=e.target.files[0]; if(!f) return; st.cover=f; mark(); $('#coverDrop').textContent='✓ '+f.name; $('#coverDrop').classList.add('has'); const r=new FileReader(); r.onload=()=>{ $('#coverPrev').src=r.result; $('#coverPrev').style.display='block'; }; r.readAsDataURL(f); };
  // ویدیوی معرفی
  $$('[data-im]').forEach(b=>b.onclick=()=>{ st.introMode=b.dataset.im; $$('[data-im]').forEach(x=>x.classList.toggle('on',x===b)); $('#imForm').classList.toggle('hide',st.introMode!=='form'); $('#imFtp').classList.toggle('hide',st.introMode!=='ftp'); mark(); });
  $('#introDrop').onclick = ()=>$('#introFile').click();
  $('#introFile').onchange = async e=>{ const f=e.target.files[0]; if(!f) return; const pg=$('#introPg'); pg.classList.remove('hide'); $('#introDrop').textContent='در حال آپلود: '+f.name;
    try{ const d = await simpleUpload(f,{url:'/upload-intro.php',field:'file',onProgress:(a,b)=>{ pg.firstChild.style.width=Math.round(a/b*100)+'%'; }}); st.introFile=d.file_name; mark(); $('#introDrop').textContent='✓ آپلود شد: '+f.name; $('#introDrop').classList.add('has'); }
    catch(err){ $('#introDrop').textContent='❌ '+err.message; pg.classList.add('hide'); } };
  // قیمت
  const calc = () => {
    const amt = A.num(v('cAmount')), orig = A.num(v('cOrig')), on = $('#cDisc').checked; $('#discBox').classList.toggle('hide',!on);
    const box = $('#discCalc');
    if(on && orig && amt && orig>amt){ const p = Math.round((1-amt/orig)*100); box.innerHTML = `با قیمت اصلی ${esc(A.money(orig))} و قیمت فروش ${esc(A.money(amt))}، تخفیف واقعی <b>${A.fa(p)}٪</b> است.`; if(!A.num(v('cPct'))) $('#cPct').value = A.fa(p); }
    else box.textContent = on ? 'قیمت اصلی باید بیشتر از قیمت فروش باشد.' : '';
    const txt = v('cPriceTxt'); const w = $('#priceWarn');
    w.innerHTML = (amt && txt && A.toEn(txt).replace(/[^0-9]/g,'') && A.num(txt)!==amt && A.toEn(txt).replace(/[^0-9]/g,'')!==String(amt)) ? '<span class="warn">⚠ مبلغ داخل این متن با «قیمت فروش» یکی نیست؛ مشتری یک عدد می‌بیند و عدد دیگری پرداخت می‌کند.</span>' : '';
    $('#pricePrev').innerHTML = `<div style="font-size:22px;color:var(--accent)">${esc(txt||A.money(amt))}</div>${on&&orig?`<div class="muted"><s>${esc(A.money(orig))}</s> ${A.num(v('cPct'))?`<span class="badge b-err">${esc(A.fa(A.num(v('cPct'))))}٪ تخفیف</span>`:''} ${esc(v('cDiscLbl'))}</div>`:''}`;
  };
  ['cAmount','cOrig','cPct','cPriceTxt','cDiscLbl','cDisc'].forEach(i=>{ $('#'+i).addEventListener('input',calc); $('#'+i).addEventListener('change',calc); }); calc();
  $('#priceFromAmt').onclick = ()=>{ const a=A.num(v('cAmount')); if(a){ $('#cPriceTxt').value = A.fa(a)+' تومان'; mark(); calc(); } };
  $$('input.num').forEach(el=>el.addEventListener('input',()=>{ const p=el.selectionStart; el.value = A.toFa(A.toEn(el.value).replace(/[^0-9]/g,'')); try{el.setSelectionRange(p,p);}catch(e){} }));

  // ذخیره
  $('#cSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{
    const title = v('cTitle').trim(); if(!title){ A.toast('عنوان دوره الزامی است','err'); show('info'); return; }
    const amt = A.num(v('cAmount')); if(amt!=null && amt<1000){ A.toast('قیمت فروش نامعتبر است','err'); show('price'); return; }
    const on = $('#cDisc').checked, orig = A.num(v('cOrig'));
    if(on && orig && amt && orig<=amt){ A.toast('قیمت اصلی باید بیشتر از قیمت فروش باشد','err'); show('price'); return; }
    const p = {
      title, title_en:v('cTitleEn').trim()||null, tag:v('cTag'), level:v('cLevel'), mode:v('cMode'),
      description:v('cDesc').trim(), description_en:v('cDescEn').trim()||null,
      price:v('cPriceTxt').trim()||null, price_en:v('cPriceEn').trim()||null, duration:v('cDuration').trim()||null,
      capacity:A.num(v('cCap'))||null, start_date:v('cStart').trim()||null, link:v('cLink').trim()||null, syllabus:v('cSyl').trim()||null,
      video_url:v('cVideoUrl').trim()||null, intro_video_file:(st.introMode==='ftp'?v('introFtp').trim():st.introFile)||null,
      price_amount:amt||null, slug:A.cleanSlug(v('cSlug'))||null, discount_active:on, original_price_amount:orig||null,
      discount_percent:A.num(v('cPct'))||null, discount_label:v('cDiscLbl').trim()||null,
      sort_order:A.num(v('cSort'))||0, is_active:$('#cActive').checked, is_featured:$('#cFeat').checked };
    if($('#cSms')) p.sms_title = v('cSms').trim()||null;
    if(st.cover) p.cover_url = await A.uploadImage(st.cover,'courses');
    const { row, skipped } = await A.saveRow('courses', p, isNew?null:id, ['title','description']);
    A.audit(isNew?'course_create':'course_edit','courses',(row&&row.id)||id,{title,price:amt});
    st.dirty=false; A.dirty=null;
    if(skipped.length) A.notice('دوره ذخیره شد، ولی بعضی فیلدها نه','این فیلدها ذخیره نشدند چون ستونشان هنوز در دیتابیس ساخته نشده:\n'+skipped.join('، '));
    else A.toast('دوره ذخیره شد ✓','ok');
    syncCourse(isNew?null:id, p.description);
    if(isNew && row) A.go('courses/'+row.id+'/lessons'); else A.rerender();
  });
  const del = $('#cDelete'); if(del) del.onclick = async ()=>{
    const [n1,n2] = await Promise.all([A.count('course_access',q=>q.eq('course_id',id)), A.count('payments',q=>q.eq('course_id',id).eq('status','paid'))]);
    if(n1||n2){ A.toast(`حذف ممکن نیست: ${A.fa(n1||0)} دسترسی و ${A.fa(n2||0)} پرداخت موفق دارد. به‌جای حذف، دوره را غیرفعال کن.`,'err'); return; }
    if(!await A.confirm({title:'حذف دوره',message:`دوره‌ی «${c.title}» برای همیشه حذف شود؟ (جلسه‌ها و پیوست‌های ثبت‌شده‌ی آن هم از فهرست پاک می‌شوند؛ فایل‌های روی هاست باقی می‌مانند.)`,danger:true,confirm:'حذف دوره'})) return;
    await A.q(sb.from('courses').delete().eq('id',id)); A.removeStored(c.cover_url); A.audit('course_delete','courses',id,{title:c.title}); st.dirty=false; A.dirty=null; A.toast('حذف شد'); A.syncCache(['courses','course_full']); A.go('courses');
  };
}

/* ====================== جلسات ====================== */
async function lessons(c, isNew){
  const box = $('#lessonsBox');
  if(isNew){ A.mount(box, html`<div class="alert info">اول دوره را ذخیره کن، بعد جلسه اضافه کن.</div>`); return; }
  const r = await sb.from('course_videos').select('*').eq('course_id',c.id).order('sort_order',{ascending:true});
  if(r.error){ A.mount(box, html`<div class="alert err">${A.missing(r.error)?'جدول course_videos ساخته نشده':A.errMsg(r.error)}</div>`); return; }
  const rows = r.data||[]; const att = await sb.from('course_attachments').select('video_id').in('video_id', rows.length?rows.map(x=>x.id):['00000000-0000-0000-0000-000000000000']);
  const ac = {}; (att.data||[]).forEach(a=>ac[a.video_id]=(ac[a.video_id]||0)+1);
  A.mount(box, html`<div class="toolbar"><span class="muted">${A.fa(rows.length)} جلسه — با کشیدن (☰) ترتیب را عوض کن</span><div class="sp"></div><button class="btn primary" id="lAdd">${A.icon('plus')} جلسه‌ی جدید</button></div>
    <div id="lList">${rows.length?rows.map((l,i)=>html`<div class="lesson" draggable="true" data-id="${l.id}"><span class="grip">${A.icon('grip')}</span><span class="n">${A.fa(i+1)}</span>
      <div class="t"><b>${l.title}</b><span class="muted" style="font-size:12px">${l.duration||'—'} · <span class="mono">${(l.file_name||'').slice(0,18)}</span></span></div>
      <button class="btn sm ghost" data-act="l-up" data-id="${l.id}" title="بالا">▲</button><button class="btn sm ghost" data-act="l-down" data-id="${l.id}" title="پایین">▼</button>
      <button class="btn sm" data-act="l-att" data-id="${l.id}">${A.icon('clip')} ${A.fa(ac[l.id]||0)}</button>
      <button class="btn sm" data-act="l-edit" data-id="${l.id}">${A.icon('edit')}</button><button class="btn sm danger" data-act="l-del" data-id="${l.id}">${A.icon('trash')}</button></div>`):html`<div class="empty">${A.icon('video')}<div>هنوز جلسه‌ای اضافه نشده</div></div>`}</div>`);
  A.state.lessons = { course:c, rows };
  $('#lAdd').onclick = ()=>lessonModal(c, null, ()=>lessons(c,false));
  // drag & drop
  const list = $('#lList'); let dragEl=null;
  list.addEventListener('dragstart',e=>{ dragEl=e.target.closest('.lesson'); if(dragEl){ dragEl.classList.add('drag'); e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain',dragEl.dataset.id); } });
  list.addEventListener('dragover',e=>{ e.preventDefault(); const t=e.target.closest('.lesson'); $$('.lesson',list).forEach(x=>x.classList.remove('over')); if(t&&t!==dragEl){ t.classList.add('over'); } });
  list.addEventListener('drop',async e=>{ e.preventDefault(); const t=e.target.closest('.lesson'); if(!t||!dragEl||t===dragEl) return;
    const ids = $$('.lesson',list).map(x=>x.dataset.id); const from=ids.indexOf(dragEl.dataset.id), to=ids.indexOf(t.dataset.id); ids.splice(to,0,ids.splice(from,1)[0]); await reorder(ids); });
  list.addEventListener('dragend',()=>{ if(dragEl) dragEl.classList.remove('drag'); $$('.lesson',list).forEach(x=>x.classList.remove('over')); });
}
async function reorder(ids){
  const { course, rows } = A.state.lessons; const byId = Object.fromEntries(rows.map(r=>[r.id,r]));
  for(let i=0;i<ids.length;i++){ if(byId[ids[i]] && byId[ids[i]].sort_order!==i) await A.q(sb.from('course_videos').update({sort_order:i}).eq('id',ids[i])); }
  A.syncCache(['course_full']); lessons(course,false);
}
A.on('l-up', el=>moveL(el.dataset.id,-1)); A.on('l-down', el=>moveL(el.dataset.id,1));
async function moveL(id, d){ const ids = A.state.lessons.rows.map(r=>r.id); const i=ids.indexOf(id), j=i+d; if(j<0||j>=ids.length) return; [ids[i],ids[j]]=[ids[j],ids[i]]; await reorder(ids); }
A.on('l-edit', el=>{ const l=A.state.lessons.rows.find(r=>r.id===el.dataset.id); lessonModal(A.state.lessons.course, l, ()=>lessons(A.state.lessons.course,false)); });
A.on('l-del', async el=>{
  const l=A.state.lessons.rows.find(r=>r.id===el.dataset.id);
  if(!await A.confirm({title:'حذف جلسه',message:`جلسه‌ی «${l.title}» از دوره حذف شود؟\nفایل ویدیو روی هاست باقی می‌ماند (می‌توانی از «کتابخانه‌ی فایل» پاکش کنی).`,danger:true,confirm:'حذف جلسه'})) return;
  await A.q(sb.from('course_videos').delete().eq('id',l.id)); A.audit('lesson_delete','course_videos',l.id,{title:l.title});
  const ids = A.state.lessons.rows.filter(r=>r.id!==l.id).map(r=>r.id); A.state.lessons.rows = A.state.lessons.rows.filter(r=>r.id!==l.id); await reorder(ids); A.toast('جلسه حذف شد');
});

// مودال افزودن/ویرایش جلسه
function lessonModal(course, l, done){
  const edit = !!l; let fileName = '', mode = 'up', upl = null, busy = false, ctl = null;
  const m = A.modal({ title: edit?'ویرایش جلسه':'جلسه‌ی جدید', size:'lg',
    body: html`<div class="fld"><label class="lb">عنوان جلسه *</label><input class="in" id="lT" value="${l?.title||''}"></div>
      <div class="row2"><div class="fld"><label class="lb">مدت</label><input class="in" id="lD" value="${l?.duration||''}" placeholder="۲۵ دقیقه"><div class="help" id="lDh"></div></div><div></div></div>
      <div class="fld"><label class="lb">توضیح (اختیاری)</label><textarea class="in" id="lDesc" style="min-height:70px">${l?.description||''}</textarea></div>
      <div class="fld"><label class="lb">${edit?'تعویض ویدیوی جلسه (اختیاری)':'فایل ویدیو *'}</label>
        ${edit?html`<div class="help" style="margin:0 0 8px">فایل فعلی: <span class="mono">${l.file_name||'—'}</span></div>`:''}
        <div class="seg" style="margin-bottom:10px"><button type="button" data-m="up" class="on">آپلود از کامپیوتر</button><button type="button" data-m="ftp">نام فایل (FTP)</button></div>
        <div id="mUp"><div class="drop" id="lDrop">انتخاب ویدیو (MP4 / WebM) — فایل‌های بزرگ خودکار تکه‌تکه و با قابلیت ادامه آپلود می‌شوند</div><input type="file" id="lFile" accept="video/mp4,video/webm,.mp4,.m4v,.webm" class="hide">
          <div id="lWarn"></div><div class="progress hide" id="lPg"><i></i></div><div class="help hide" id="lPgT"></div><button class="btn sm danger hide" id="lCancel" style="margin-top:8px">لغو آپلود</button></div>
        <div id="mFtp" class="hide"><input class="in ltr" id="lFtp" placeholder="مثلاً lesson1.mp4 (فایل باید در protected_videos باشد)"></div></div>`,
    footer: html`<button class="btn" data-x>انصراف</button><button class="btn primary" id="lSave">${edit?'ذخیره تغییرات':'افزودن جلسه'}</button>`,
    dirty: ()=> !busy && (!!$('#lT')?.value && !edit) });
  m.$('[data-x]').onclick = ()=>m.close();
  m.$$('[data-m]').forEach(b=>b.onclick=()=>{ mode=b.dataset.m; m.$$('[data-m]').forEach(x=>x.classList.toggle('on',x===b)); m.$('#mUp').classList.toggle('hide',mode!=='up'); m.$('#mFtp').classList.toggle('hide',mode!=='ftp'); });
  m.$('#lDrop').onclick = ()=>m.$('#lFile').click();
  m.$('#lFile').onchange = async e=>{
    const f = e.target.files[0]; if(!f) return; fileName=''; busy=true; ctl=new AbortController();
    const pg=m.$('#lPg'), tx=m.$('#lPgT'), dr=m.$('#lDrop'), w=m.$('#lWarn');
    dr.textContent='در حال بررسی فایل…'; w.innerHTML='';
    const pr = await A.probeVideo(f);
    if(pr.duration && !m.$('#lD').value.trim()){ m.$('#lD').value = A.durText(pr.duration); }
    if(pr.faststart===false) w.innerHTML = '<div class="alert warn" style="margin-top:10px">⚠ این فایل برای پخش آنلاین بهینه نشده (moov در انتهای فایل است) — پخش دیرتر شروع می‌شود. در HandBrake گزینه‌ی «Web Optimized» را بزن و دوباره خروجی بگیر.</div>';
    if(!/^video\/(mp4|webm)/.test(f.type) && !/\.(mp4|m4v|webm)$/i.test(f.name)){ dr.textContent='❌ فقط MP4 یا WebM'; busy=false; return; }
    dr.textContent='⬆️ '+f.name+' ('+A.bytes(f.size)+')'; pg.classList.remove('hide'); tx.classList.remove('hide'); m.$('#lCancel').classList.remove('hide'); pg.firstChild.style.width='0';
    try{
      const d = await A.uploadHost(f,{kind:'video',signal:ctl.signal,onProgress:(a,b,sp,note)=>{ pg.firstChild.style.width=Math.round(a/b*100)+'%'; tx.textContent = note || `${A.fa(Math.round(a/b*100))}٪ — ${A.bytes(a)} از ${A.bytes(b)}${sp?' — '+A.fmtSpeed(sp):''}`; }});
      fileName = d.file_name; dr.textContent='✓ آپلود شد: '+f.name; dr.classList.add('has'); tx.textContent='آماده — حالا ذخیره را بزن';
    }catch(err){ dr.textContent='❌ '+err.message; pg.classList.add('hide'); }
    busy=false; m.$('#lCancel').classList.add('hide');
  };
  m.$('#lCancel').onclick = ()=>{ if(ctl) ctl.abort(); };
  m.$('#lSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{
    const title = m.$('#lT').value.trim(); if(!title){ A.toast('عنوان الزامی است','err'); return; }
    if(busy){ A.toast('صبر کن آپلود تمام شود','err'); return; }
    const file = mode==='ftp' ? m.$('#lFtp').value.trim() : fileName;
    const p = { title, duration:m.$('#lD').value.trim()||null, description:m.$('#lDesc').value.trim()||null };
    if(edit){
      if(file){ if(!await A.confirm({title:'تعویض ویدیو',message:'ویدیوی این جلسه با فایل جدید جایگزین شود؟'})) return; p.file_name=file; }
      await A.q(sb.from('course_videos').update(p).eq('id',l.id)); A.audit('lesson_edit','course_videos',l.id,{title,replaced:!!file});
    } else {
      if(!file){ A.toast('فایل ویدیو را آپلود کن یا نام فایل FTP را بنویس','err'); return; }
      const last = await A.q(sb.from('course_videos').select('sort_order').eq('course_id',course.id).order('sort_order',{ascending:false}).limit(1));
      await A.q(sb.from('course_videos').insert([{...p, course_id:course.id, file_name:file, sort_order:(last[0]?.sort_order??-1)+1}])); A.audit('lesson_add','course_videos',course.id,{title,file});
    }
    A.syncCache(['course_full']); A.toast('ذخیره شد ✓','ok'); m.close(true); done();
  });
}

/* ---------- پیوست‌های هر جلسه ---------- */
A.on('l-att', el=>{
  const l = A.state.lessons.rows.find(r=>r.id===el.dataset.id);
  const m = A.modal({ title:'فایل‌های جلسه: '+l.title, size:'lg', body: html`<div id="atList"></div><hr style="border:0;border-top:1px solid var(--line);margin:18px 0"><h4 style="margin-bottom:10px">افزودن فایل</h4>
    <div class="row2"><div class="fld"><label class="lb">عنوان نمایشی</label><input class="in" id="aT"></div>
    <div class="fld"><label class="lb">فایل</label><div class="drop" id="aDrop">انتخاب فایل (PDF, ZIP, عکس, Word, PowerPoint)</div><input type="file" id="aFile" class="hide"></div></div>
    <div class="progress hide" id="aPg"><i></i></div><div class="fld" style="margin-top:10px"><label class="lb">یا نام فایل FTP</label><input class="in ltr" id="aFtp" placeholder="notes.pdf"></div>`,
    footer: html`<button class="btn" data-x>بستن</button><button class="btn primary" id="aAdd">افزودن</button>` });
  m.$('[data-x]').onclick = ()=>m.close(); let fname='';
  const load = async ()=>{
    const r = await sb.from('course_attachments').select('*').eq('video_id',l.id).order('sort_order',{ascending:true});
    A.mount(m.$('#atList'), r.error?html`<div class="alert err">${A.errMsg(r.error)}</div>`:A.table([{h:'عنوان',c:a=>a.title},{h:'فایل',c:a=>html`<span class="mono muted">${(a.file_name||'').slice(0,16)}</span>`},
      {h:'',c:a=>html`<button class="btn sm" data-act="at-ren" data-id="${a.id}" data-t="${a.title}">نام</button> <button class="btn sm danger" data-act="at-del" data-id="${a.id}">${A.icon('trash')}</button>`}], r.data||[], {empty:'فایلی اضافه نشده'}));
    m.reload = load;
  }; load(); A.state.attModal = m;
  m.$('#aDrop').onclick = ()=>m.$('#aFile').click();
  m.$('#aFile').onchange = async e=>{ const f=e.target.files[0]; if(!f) return; const pg=m.$('#aPg'); pg.classList.remove('hide'); m.$('#aDrop').textContent='⬆️ '+f.name;
    try{ const d = await A.uploadHost(f,{kind:'attach',onProgress:(a,b)=>{ pg.firstChild.style.width=Math.round(a/b*100)+'%'; }}); fname=d.file_name; m.$('#aDrop').textContent='✓ آپلود شد: '+f.name; m.$('#aDrop').classList.add('has'); if(!m.$('#aT').value.trim()) m.$('#aT').value=f.name.replace(/\.[^.]+$/,''); }
    catch(err){ m.$('#aDrop').textContent='❌ '+err.message; } };
  m.$('#aAdd').onclick = async ()=>{
    const title = m.$('#aT').value.trim(), file = m.$('#aFtp').value.trim() || fname; if(!title||!file){ A.toast('عنوان و فایل الزامی است','err'); return; }
    const last = await A.q(sb.from('course_attachments').select('sort_order').eq('video_id',l.id).order('sort_order',{ascending:false}).limit(1));
    await A.q(sb.from('course_attachments').insert([{video_id:l.id,title,file_name:file,sort_order:(last[0]?.sort_order??-1)+1}]));
    A.audit('attachment_add','course_attachments',l.id,{title}); fname=''; m.$('#aT').value=''; m.$('#aFtp').value=''; m.$('#aDrop').textContent='انتخاب فایل'; m.$('#aDrop').classList.remove('has'); m.$('#aPg').classList.add('hide'); A.toast('فایل اضافه شد ✓','ok'); load();
  };
});
A.on('at-ren', async el=>{ const t = await A.prompt({title:'تغییر نام نمایشی',label:'نام جدید',value:el.dataset.t}); if(t===null) return; if(!t.trim()){ A.toast('نام نمی‌تواند خالی باشد','err'); return; } await A.q(sb.from('course_attachments').update({title:t.trim()}).eq('id',el.dataset.id)); A.state.attModal.reload(); });
A.on('at-del', async el=>{ if(!await A.confirm({title:'حذف فایل',message:'این فایل از جلسه حذف شود؟',danger:true})) return; await A.q(sb.from('course_attachments').delete().eq('id',el.dataset.id)); A.state.attModal.reload(); });
})();
