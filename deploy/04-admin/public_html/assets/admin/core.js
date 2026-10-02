/* ==========================================================
   پنل مدیریت v2 — هسته: ابزارهای امن، دیالوگ‌ها، روتر، ورود
   همه‌ی خروجی‌ها از A.html (با escape خودکار) ساخته می‌شوند.
   ========================================================== */
(function(){
'use strict';
const A = window.A = { views:{}, handlers:{}, has:{}, state:{} };

/* درخواست‌های Supabase بیش از ۲۵ ثانیه معطل نشوند (آپلود فایل‌ها ۳ دقیقه) — وگرنه صفحه برای همیشه «در حال بارگذاری» می‌ماند */
const _fetch = window.fetch.bind(window);
window.fetch = (input, init) => {
  init = init || {}; const url = typeof input==='string' ? input : (input && input.url) || '';
  if(!/\/(rest|auth|storage|functions)\/v1\//.test(url)) return _fetch(input, init);
  const ctl = new AbortController(); const t = setTimeout(()=>ctl.abort(), /\/storage\//.test(url) ? 180000 : 25000);
  if(init.signal){ if(init.signal.aborted) ctl.abort(); else init.signal.addEventListener('abort',()=>ctl.abort()); }
  return _fetch(input, Object.assign({}, init, {signal:ctl.signal})).finally(()=>clearTimeout(t));
};

/* ---------- escape / template ---------- */
const ESC = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
A.esc = s => String(s==null?'':s).replace(/[&<>"']/g, c=>ESC[c]);
class Raw{ constructor(s){ this.s=s; } toString(){ return this.s; } }
A.Raw = Raw;
A.raw = s => new Raw(String(s==null?'':s));
A.str = v => v instanceof Raw ? v.s : Array.isArray(v) ? v.map(A.str).join('') : (v==null||v===false) ? '' : A.esc(v);
A.html = (strs,...vals) => new Raw(strs.reduce((o,s,i)=> o + s + (i<vals.length ? A.str(vals[i]) : ''), ''));
A.mount = (el, content) => { el.innerHTML = content instanceof Raw ? content.s : A.esc(content); return el; };
A.$ = (s, r=document) => r.querySelector(s);
A.$$ = (s, r=document) => Array.from(r.querySelectorAll(s));
const $ = A.$;

/* ---------- icons ---------- */
const P = {
  home:'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
  cash:'M3 7h18v10H3zM12 9.5a2.5 2.5 0 100 5 2.5 2.5 0 000-5zM6 10v4M18 10v4',
  card:'M3 6h18v12H3zM3 10h18M7 15h3',
  key:'M14 10a4 4 0 11-3.9 3.1L3 20v-3h3v-3h3l2.1-2.1A4 4 0 0114 10z',
  users:'M16 19v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1M9.5 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM21 19v-1a4 4 0 00-3-3.9M15.5 4.2a3.5 3.5 0 010 6.6',
  book:'M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zM4 19a2 2 0 012-2h13',
  image:'M3 5h18v14H3zM8.5 10a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM21 16l-5-5-8 8',
  inbox:'M3 13l3-8h12l3 8v6H3zM3 13h5a4 4 0 008 0h5',
  globe:'M12 21a9 9 0 100-18 9 9 0 000 18zM3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18',
  pen:'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
  star:'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
  help:'M12 21a9 9 0 100-18 9 9 0 000 18zM9.5 9.5a2.5 2.5 0 114 2c-.8.6-1.5 1-1.5 2M12 17h.01',
  palette:'M12 3a9 9 0 100 18c1.5 0 2-1 1.5-2s0-2 1.5-2h2a3 3 0 003-3c0-5-4-11-8-11zM7.5 11h.01M10 7.5h.01M15 8h.01',
  megaphone:'M3 11v2a1 1 0 001 1h2l7 4V6L6 10H4a1 1 0 00-1 1zM17 9a4 4 0 010 6',
  activity:'M3 12h4l3-8 4 16 3-8h4',
  shield:'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
  gear:'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z',
  search:'M11 19a8 8 0 100-16 8 8 0 000 16zM21 21l-4.3-4.3',
  plus:'M12 5v14M5 12h14',
  x:'M6 6l12 12M18 6L6 18',
  check:'M5 12l5 5 9-10',
  edit:'M4 20h4L19 9l-4-4L4 16z',
  trash:'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
  download:'M12 4v11M7 11l5 5 5-5M5 20h14',
  upload:'M12 16V5M7 9l5-5 5 5M5 20h14',
  eye:'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
  link:'M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1M14 10a4 4 0 00-5.7 0l-3 3A4 4 0 0011 18.7l1-1',
  menu:'M4 7h16M4 12h16M4 17h16',
  refresh:'M20 11a8 8 0 00-14.9-3M4 4v4h4M4 13a8 8 0 0014.9 3M20 20v-4h-4',
  grip:'M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01',
  clip:'M20 11l-8.5 8.5a5 5 0 01-7-7L13 4a3.5 3.5 0 015 5l-8.5 8.5a2 2 0 01-3-3L14 7',
  video:'M3 6h12v12H3zM15 10l6-3v10l-6-3',
  logout:'M9 4H5a2 2 0 00-2 2v12a2 2 0 002 2h4M16 8l4 4-4 4M20 12H9',
  alert:'M12 3l10 18H2zM12 10v5M12 18h.01',
  tag:'M3 12V3h9l9 9-9 9zM7.5 7.5h.01',
  phone:'M6 3h4l2 5-2.5 1.5a11 11 0 005 5L16 12l5 2v4a2 2 0 01-2 2A16 16 0 013 5a2 2 0 013-2z',
  clock:'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
  merge:'M6 3v6a6 6 0 006 6h6M18 11l3 4-3 4M6 21v-3',
  db:'M4 6c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3zM4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3',
  folder:'M3 6a2 2 0 012-2h4l2 3h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z',
  chart:'M4 20V10M10 20V4M16 20v-7M22 20H2',
  mail:'M3 6h18v12H3zM3 7l9 7 9-7',
  copy:'M9 9h11v11H9zM5 15V5h10',
  lock:'M6 11h12v9H6zM8 11V8a4 4 0 118 0v3',
};
A.icon = (n, cls='') => new Raw(`<svg class="ic ${cls}" viewBox="0 0 24 24" aria-hidden="true"><path d="${P[n]||P.help}"/></svg>`);

/* ---------- formatting ---------- */
const FA = '۰۱۲۳۴۵۶۷۸۹';
A.fa = n => (n==null||n==='' ) ? '—' : Number(n).toLocaleString('fa-IR');
A.toFa = s => String(s==null?'':s).replace(/[0-9]/g, d=>FA[d]);
A.toEn = s => String(s==null?'':s).replace(/[۰-۹٠-٩]/g, ch=>{ let i=FA.indexOf(ch); if(i<0) i='٠١٢٣٤٥٦٧٨٩'.indexOf(ch); return i<0?ch:i; });
A.num = s => { const v = parseInt(A.toEn(s).replace(/[^0-9-]/g,''),10); return isNaN(v) ? null : v; };
A.money = n => (n==null||n==='') ? '—' : A.fa(n) + ' تومان';
A.TZ = 'Asia/Tehran';
const dFmt = new Intl.DateTimeFormat('fa-IR',{timeZone:A.TZ,year:'numeric',month:'long',day:'numeric'});
const dtFmt = new Intl.DateTimeFormat('fa-IR',{timeZone:A.TZ,year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
const shortFmt = new Intl.DateTimeFormat('fa-IR',{timeZone:A.TZ,month:'short',day:'numeric'});
const tFmt = new Intl.DateTimeFormat('fa-IR',{timeZone:A.TZ,hour:'2-digit',minute:'2-digit'});
A.date = d => d ? dFmt.format(new Date(d)) : '—';
A.dt = d => d ? dtFmt.format(new Date(d)) : '—';
A.dshort = d => d ? shortFmt.format(new Date(d)) : '—';
A.time = d => d ? tFmt.format(new Date(d)) : '—';
A.ago = d => {
  if(!d) return '—'; const s = (Date.now()-new Date(d).getTime())/1000;
  if(s<60) return 'همین الان'; if(s<3600) return A.fa(Math.floor(s/60))+' دقیقه پیش';
  if(s<86400) return A.fa(Math.floor(s/3600))+' ساعت پیش'; if(s<86400*30) return A.fa(Math.floor(s/86400))+' روز پیش';
  return A.date(d);
};
// کلید روز تهران (YYYY-MM-DD میلادی) و مرزهای روز به‌وقت تهران
A.dayKey = d => new Date(d).toLocaleDateString('en-CA',{timeZone:A.TZ});
A.dayStart = key => new Date(key+'T00:00:00+03:30');
A.todayStart = () => A.dayStart(A.dayKey(Date.now()));
A.addDays = (dt, n) => new Date(dt.getTime()+n*86400000);
A.jalaliMonthStart = () => {
  const p = new Intl.DateTimeFormat('fa-IR-u-nu-latn',{timeZone:A.TZ,day:'numeric'}).formatToParts(new Date());
  const day = parseInt(p.find(x=>x.type==='day').value,10);
  return A.addDays(A.todayStart(), -(day-1));
};
A.initial = s => { s = String(s||'').trim(); return s ? Array.from(s)[0] : '؟'; };
A.maskPhone = p => p ? p.replace(/^(\d{4})\d{3}(\d{4})$/,'$1•••$2') : '';
A.bytes = n => { n=Number(n)||0; if(n<1024) return A.fa(n)+' B'; if(n<1048576) return A.fa((n/1024).toFixed(0))+' KB'; if(n<1073741824) return A.fa((n/1048576).toFixed(1))+' MB'; return A.fa((n/1073741824).toFixed(2))+' GB'; };
A.debounce = (fn, ms=300) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
A.slug = text => {
  const M={'آ':'a','ا':'a','ب':'b','پ':'p','ت':'t','ث':'s','ج':'j','چ':'ch','ح':'h','خ':'kh','د':'d','ذ':'z','ر':'r','ز':'z','ژ':'zh','س':'s','ش':'sh','ص':'s','ض':'z','ط':'t','ظ':'z','ع':'a','غ':'gh','ف':'f','ق':'gh','ک':'k','گ':'g','ل':'l','م':'m','ن':'n','و':'v','ه':'h','ی':'y','ي':'y','ك':'k','ئ':'y','ء':'','ة':'h','ؤ':'v','أ':'a'};
  return String(text||'').split('').map(ch=> M[ch]!==undefined ? M[ch] : (/[a-zA-Z0-9]/.test(ch)?ch.toLowerCase():'-')).join('').replace(/-+/g,'-').replace(/^-|-$/g,'').substring(0,60);
};
A.cleanSlug = s => String(s||'').trim().toLowerCase().replace(/[^a-z0-9-]/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
A.csv = (filename, header, rows) => {
  const cell = c => '"'+String(c==null?'':c).replace(/"/g,'""')+'"';
  const body = [header,...rows].map(r=>r.map(cell).join(',')).join('\r\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob(['﻿'+body],{type:'text/csv;charset=utf-8'}));
  a.download = filename; document.body.appendChild(a); a.click(); a.remove();
  setTimeout(()=>URL.revokeObjectURL(a.href), 4000);
};
A.copy = async (text) => { try{ await navigator.clipboard.writeText(text); A.toast('کپی شد ✓','ok'); }catch(e){ A.toast('کپی نشد','err'); } };

/* ---------- data helpers ---------- */
A.q = async p => { const r = await p; if(r.error) throw r.error; return r.data; };
A.missing = e => !!e && (e.code==='42P01' || e.code==='PGRST205' || /does not exist|schema cache|Could not find the table/i.test(e.message||''));
A.noCol = e => { const m=(e&&e.message||'').match(/'([a-z0-9_]+)' column/i)||(e&&e.message||'').match(/column "?([a-z0-9_]+)"? (of relation|does not exist)/i); return m?m[1]:null; };
A.errMsg = e => e ? (e.message||e.error_description||String(e)) : '';
A.count = async (table, f) => {
  try{ let q = sb.from(table).select('id',{count:'exact',head:true}); if(f) q = f(q); const r = await q; return r.error ? null : (r.count||0); }catch(e){ return null; }
};
// ذخیره‌ی یک ردیف با حذف خودکار ستون‌هایی که هنوز در دیتابیس ساخته نشده‌اند
A.saveRow = async (table, payload, id, protect=[]) => {
  const skipped = [];
  const run = () => id ? sb.from(table).update(payload).eq('id',id).select().maybeSingle() : sb.from(table).insert([payload]).select().maybeSingle();
  let res = await run();
  for(let i=0;i<6 && res.error;i++){
    const col = A.noCol(res.error);
    if(!col || !(col in payload) || protect.includes(col)) break;
    delete payload[col]; skipped.push(col); res = await run();
  }
  if(res.error) throw res.error;
  return { row:res.data, skipped };
};

/* ---------- toast ---------- */
A.toast = (msg, type='') => {
  let box = $('#toasts'); if(!box){ box=document.createElement('div'); box.id='toasts'; box.className='toasts'; document.body.appendChild(box); }
  const t = document.createElement('div'); t.className='toast '+type; t.textContent = msg; box.appendChild(t);
  setTimeout(()=>{ t.style.transition='.3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, type==='err'?6000:3200);
};

/* ---------- فیلدهای عددی: همیشه ارقام فارسی ---------- */
document.addEventListener('input', e=>{ const el=e.target; if(el.matches && el.matches('input.num')){ const p=el.selectionStart; const v=A.toFa(A.toEn(el.value).replace(/[^0-9]/g,'')); if(el.value!==v){ el.value=v; try{ el.setSelectionRange(p,p); }catch(_){} } } });

/* ---------- events (delegation) ---------- */
A.on = (name, fn) => { A.handlers[name] = fn; };
document.addEventListener('click', e => {
  const el = e.target.closest('[data-act]'); if(!el) return;
  const fn = A.handlers[el.dataset.act]; if(!fn) return;
  if(el.tagName==='A' && !el.getAttribute('href')) e.preventDefault();
  Promise.resolve(fn(el, e)).catch(err=>{ console.error(err); A.toast('خطا: '+A.errMsg(err),'err'); });
});

/* ---------- modal / drawer ---------- */
const stack = [];
A.modal = (o) => {
  const ov = document.createElement('div'); ov.className = 'ov'+(o.drawer?' drawer':'');
  const box = document.createElement('div'); box.className = 'modal '+(o.size||''); box.setAttribute('role','dialog');
  box.innerHTML = `<header><h3></h3><button class="btn ghost iconbtn" data-close aria-label="بستن">${A.icon('x').s}</button></header><div class="bd"></div>${o.footer?'<footer></footer>':''}`;
  $('h3',box).textContent = o.title||'';
  const bd = $('.bd',box); if(o.body!=null) o.body instanceof Node ? bd.appendChild(o.body) : A.mount(bd,o.body);
  if(o.footer) A.mount($('footer',box), o.footer);
  ov.appendChild(box); document.body.appendChild(ov);
  const h = { el:box, bd, ov, o,
    $:(s)=>$(s,box), $$:(s)=>A.$$(s,box),
    async close(force){
      if(!force && o.dirty && o.dirty()){ if(!await A.confirm({title:'تغییرات ذخیره نشده',message:'تغییرات این فرم ذخیره نشده. بدون ذخیره بسته شود؟',confirm:'بستن بدون ذخیره',danger:true})) return false; }
      const i = stack.indexOf(h); if(i>=0) stack.splice(i,1); ov.remove();
      if(o.onClose) o.onClose(); return true;
    } };
  $('[data-close]',box).addEventListener('click',()=>h.close());
  stack.push(h);
  setTimeout(()=>{ const f = $('input:not([type=hidden]):not([type=file]),textarea,select',bd); if(f && !o.noFocus) f.focus(); },30);
  return h;
};
document.addEventListener('keydown', e => {
  if(e.key==='Escape' && stack.length){ const t = stack[stack.length-1]; if(!t.o.noEsc) t.close(); }
});
A.confirm = ({title='تأیید',message='',confirm='تأیید',cancel='انصراف',danger=false,html=null}) => new Promise(res=>{
  const m = A.modal({ title, size:'sm', noFocus:true,
    body: html || A.html`<p style="line-height:2;white-space:pre-line">${message}</p>`,
    footer: A.html`<button class="btn" data-no>${cancel}</button><button class="btn ${danger?'danger':'primary'}" data-yes>${confirm}</button>` });
  let done=false; const fin = v => { if(done) return; done=true; m.close(true); res(v); };
  m.$('[data-no]').onclick = ()=>fin(false); m.$('[data-yes]').onclick = ()=>fin(true);
  m.$('[data-close]').onclick = ()=>fin(false);
  m.o.onClose = ()=>{ if(!done){ done=true; res(false); } };
  m.$('[data-yes]').focus();
});
A.prompt = ({title='ورودی',label='',value='',confirm='ذخیره',placeholder='',multiline=false}) => new Promise(res=>{
  const m = A.modal({ title, size:'sm',
    body: A.html`<div class="fld"><label class="lb">${label}</label>${A.raw(multiline?'<textarea class="in" data-v></textarea>':'<input class="in" data-v>')}</div>`,
    footer: A.html`<button class="btn" data-no>انصراف</button><button class="btn primary" data-yes>${confirm}</button>` });
  const inp = m.$('[data-v]'); inp.value = value; inp.placeholder = placeholder;
  let done=false; const fin = v => { if(done) return; done=true; m.close(true); res(v); };
  m.$('[data-no]').onclick = ()=>fin(null); m.$('[data-close]').onclick = ()=>fin(null);
  m.$('[data-yes]').onclick = ()=>fin(inp.value);
  inp.addEventListener('keydown',e=>{ if(e.key==='Enter' && !multiline) fin(inp.value); });
});
A.notice = (title, message) => new Promise(res=>{
  const m = A.modal({ title, size:'sm', noFocus:true, body: A.html`<p style="line-height:2;white-space:pre-line">${message}</p>`, footer: A.html`<button class="btn primary" data-ok>متوجه شدم</button>`, onClose:()=>res() });
  m.$('[data-ok]').onclick = ()=>m.close(true);
});
A.busy = async (btn, fn) => {
  if(btn){ btn.disabled = true; btn._t = btn.innerHTML; btn.innerHTML = '<span class="spin"></span>'; }
  try{ return await fn(); } finally { if(btn){ btn.disabled=false; btn.innerHTML = btn._t; } }
};

/* ---------- table / pager ---------- */
A.table = (cols, rows, o={}) => {
  if(!rows.length) return A.html`<div class="empty">${A.icon('inbox')}<div>${o.empty||'موردی یافت نشد'}</div></div>`;
  return A.html`<div class="tbl-wrap"><table class="t"><thead><tr>${cols.map(c=>A.html`<th>${c.h}</th>`)}</tr></thead><tbody>${
    rows.map(r=>A.html`<tr class="${o.row?'click':''}" ${A.raw(o.row?`data-act="${o.row}" data-id="${A.esc(r.id)}"`:'')}>${cols.map(c=>A.html`<td class="${c.cls||''}">${c.c(r)}</td>`)}</tr>`)
  }</tbody></table></div>`;
};
A.pager = (total, page, size, act='page') => {
  const pages = Math.max(1, Math.ceil(total/size)); if(pages<=1) return A.raw('');
  const btns = []; const add = (p,l,on)=>btns.push(`<button class="btn sm ${on?'on':''}" data-act="${act}" data-p="${p}">${l}</button>`);
  if(page>1) add(page-1,'‹ قبلی');
  const set = new Set([1,pages,page,page-1,page+1,page-2,page+2]); let last=0;
  [...set].filter(p=>p>=1&&p<=pages).sort((a,b)=>a-b).forEach(p=>{ if(p-last>1) btns.push('<span>…</span>'); add(p,A.fa(p),p===page); last=p; });
  if(page<pages) add(page+1,'بعدی ›');
  return A.raw(`<div class="pager">${btns.join('')}<span style="margin-right:12px">${A.fa(total)} مورد</span></div>`);
};
A.badge = (text, kind='mute') => A.html`<span class="badge b-${kind}">${text}</span>`;
A.chartBars = (items, {height=190, fmt=A.fa}={}) => {
  // items: [{label, value}]  → نمودار میله‌ای SVG
  const W=700, H=height, pad=24, max=Math.max(1,...items.map(i=>i.value));
  const bw = (W-pad*2)/Math.max(items.length,1);
  const bars = items.map((it,i)=>{
    const h = Math.round((H-pad*2)*(it.value/max)); const x=pad+i*bw+bw*.15, y=H-pad-h;
    return `<g><rect class="bar" x="${x}" y="${y}" width="${bw*.7}" height="${Math.max(h,it.value?2:0)}" rx="3"><title>${A.esc(it.label)}: ${A.esc(fmt(it.value))}</title></rect>${(items.length<=15||i%Math.ceil(items.length/10)===0)?`<text x="${x+bw*.35}" y="${H-6}" text-anchor="middle">${A.esc(it.short||it.label)}</text>`:''}</g>`;
  }).join('');
  return A.raw(`<svg class="chart" viewBox="0 0 ${W} ${H}"><line class="grid" x1="${pad}" x2="${W-pad}" y1="${H-pad}" y2="${H-pad}"/><line class="grid" x1="${pad}" x2="${W-pad}" y1="${pad}" y2="${pad}"/><text x="${pad}" y="${pad-6}">${A.esc(fmt(max))}</text>${bars}</svg>`);
};

/* ---------- audit log ---------- */
A.audit = (action, entity, entityId, detail) => {
  if(A.has.audit===false) return;
  const row = { admin_email:(A.user&&A.user.email)||null, action, entity:entity||null, entity_id:entityId?String(entityId):null, detail:detail?JSON.stringify(detail).slice(0,1500):null };
  sb.from('admin_audit_log').insert([row]).then(r=>{ if(r.error){ if(A.missing(r.error)) A.has.audit=false; } else A.has.audit=true; }).catch(()=>{});
};

/* ---------- nav / roles ---------- */
const NAV = [
  { id:'dashboard', t:'داشبورد', i:'home', roles:'owner,support,editor' },
  { g:'فروش و مالی', roles:'owner,support' },
  { id:'sales', t:'گزارش فروش', i:'chart', roles:'owner' },
  { id:'payments', t:'تراکنش‌ها', i:'card', roles:'owner,support' },
  { id:'access', t:'دسترسی‌ها و اعطای دستی', i:'key', roles:'owner,support' },
  { id:'licenses', t:'سفارش‌های لایسنسی', i:'tag', roles:'owner' },
  { g:'هنرجویان', roles:'owner,support' },
  { id:'students', t:'هنرجویان', i:'users', roles:'owner,support' },
  { g:'آموزش', roles:'owner,editor' },
  { id:'courses', t:'دوره‌ها و جلسات', i:'book', roles:'owner,editor' },
  { id:'media', t:'کتابخانه‌ی فایل', i:'folder', roles:'owner,editor' },
  { id:'inbox', t:'صندوق پیام‌ها', i:'inbox', badge:'inbox', roles:'owner,support,editor' },
  { g:'وب‌سایت', roles:'owner,editor' },
  { id:'blog', t:'بلاگ', i:'pen', roles:'owner,editor' },
  { id:'testimonials', t:'نظرات هنرجویان', i:'star', badge:'tst', roles:'owner,editor' },
  { id:'portfolio', t:'نمونه‌کارها', i:'image', roles:'owner,editor' },
  { id:'ai-works', t:'آثار هوش مصنوعی', i:'image', roles:'owner,editor' },
  { id:'faqs', t:'سؤالات متداول', i:'help', roles:'owner,editor' },
  { id:'site', t:'متن‌ها و تماس', i:'globe', roles:'owner,editor' },
  { id:'promo', t:'بنر و نشان‌ها', i:'megaphone', roles:'owner,editor' },
  { id:'style', t:'ظاهر و تایپوگرافی', i:'palette', roles:'owner,editor' },
  { g:'سیستم', roles:'owner' },
  { id:'system', t:'وضعیت سیستم', i:'activity', roles:'owner' },
  { id:'audit', t:'گزارش فعالیت‌ها', i:'shield', roles:'owner' },
  { id:'settings', t:'تنظیمات و امنیت', i:'gear', roles:'owner,support,editor' },
  { id:'help', t:'راهنما: چی کجاست؟', i:'help', roles:'owner,support,editor' },
];
A.role = 'owner';
A.can = id => { const n = NAV.find(x=>x.id===id); return !n || !n.roles || n.roles.split(',').includes(A.role); };
function renderNav(){
  let out = '', pendingGroup = null, groupHtml = '';
  const flush = () => { if(groupHtml) out += `<div class="nav-group">${pendingGroup?`<div class="gl">${A.esc(pendingGroup)}</div>`:''}${groupHtml}</div>`; groupHtml=''; };
  NAV.forEach(n=>{
    if(n.g){ flush(); pendingGroup = n.g; return; }
    if(!A.can(n.id)) return;
    groupHtml += `<a class="nav-link" href="#/${n.id}" data-nav="${n.id}">${A.icon(n.i).s}<span>${A.esc(n.t)}</span>${n.badge?`<span class="cnt hide" data-badge="${n.badge}"></span>`:''}</a>`;
  });
  flush();
  $('#nav').innerHTML = out;
}
A.refreshCounts = async () => {
  const [cm, ord, tst, lic] = await Promise.all([
    A.count('comments', q=>q.eq('is_approved',false)), A.count('orders', q=>q.eq('status','new')),
    A.count('testimonials', q=>q.eq('is_published',false)), A.count('course_orders', q=>q.eq('status','pending')) ]);
  A.state.counts = { inbox:(cm||0)+(ord||0), tst:tst||0, lic:lic||0, comments:cm||0, orders:ord||0 };
  A.$$('[data-badge]').forEach(b=>{ const v = A.state.counts[b.dataset.badge]; b.textContent = A.fa(v); b.classList.toggle('hide', !v); });
};

/* ---------- router ---------- */
A.route = (name, def) => { A.views[name] = def; };
A.go = (path) => { location.hash = '#/'+path.replace(/^#?\/?/,''); };
A.dirty = null;                                    // ویرایشگرها این را تنظیم می‌کنند
let currentHash = location.hash, navToken = 0, reverting = false;
function parseHash(){
  const parts = location.hash.replace(/^#\/?/,'').split('/').filter(Boolean).map(decodeURIComponent);
  return { name: parts[0]||'dashboard', args: parts.slice(1) };
}
async function render(){
  const {name, args} = parseHash();
  const def = A.views[name] || A.views.dashboard;
  const vname = A.views[name] ? name : 'dashboard';
  if(!A.can(vname)){ A.mount($('#content'), A.html`<div class="alert err">${A.icon('lock')} این بخش برای نقش شما در دسترس نیست.</div>`); return; }
  const tok = ++navToken; A.dirty = null;
  A.$$('.nav-link').forEach(a=>a.classList.toggle('active', a.dataset.nav===(def.nav||vname)));
  $('#pageTitle').textContent = typeof def.title==='function' ? def.title(args) : (def.title||'');
  $('#aside').classList.remove('open');
  const box = $('#content'); box.innerHTML = '<div class="loading"><span class="spin"></span></div>';
  window.scrollTo(0,0);
  try{ await def.render(box, args, ()=>tok===navToken); }
  catch(e){ if(tok!==navToken) return; console.error(e); A.mount(box, A.html`<div class="alert err">${A.icon('alert')}<div><b>خطا در بارگذاری این بخش:</b> ${A.errMsg(e)}</div></div><button class="btn" data-act="reload">تلاش دوباره</button>`); }
}
A.on('reload', ()=>render());
A.rerender = () => render();
window.addEventListener('hashchange', async ()=>{
  if(reverting){ reverting=false; currentHash=location.hash; return; }
  if(A.dirty && A.dirty()){
    const ok = await A.confirm({title:'تغییرات ذخیره نشده',message:'تغییرات این صفحه ذخیره نشده. از صفحه خارج شوی از بین می‌رود.',confirm:'خروج بدون ذخیره',danger:true});
    if(!ok){ reverting=true; location.hash = currentHash; return; }
  }
  currentHash = location.hash; render();
});
window.addEventListener('unhandledrejection', e=>{
  const msg = A.errMsg(e.reason); console.error('unhandled', e.reason);
  const box = $('#content'); const spin = box && box.querySelector('.loading');
  if(spin) spin.outerHTML = `<div class="alert err">${A.icon('alert').s}<div><b>خطا در دریافت اطلاعات:</b> ${A.esc(/abort/i.test(msg)?'پاسخی از سرور نیامد (اتصال کند یا قطع است)':msg)}</div></div><button class="btn" data-act="reload">تلاش دوباره</button>`;
  else A.toast('خطا: '+msg,'err');
  e.preventDefault();
});
window.addEventListener('beforeunload', e=>{ if(A.dirty && A.dirty()){ e.preventDefault(); e.returnValue=''; } });

/* ---------- global search (Ctrl+K) ---------- */
const PAGES = NAV.filter(n=>n.id).map(n=>({t:n.t, k:'بخش', go:n.id}));
A.openSearch = () => {
  const m = A.modal({ title:'', size:'', noEsc:false, body:A.html`<div class="cmd-in"></div>` });
  m.ov.firstChild.classList.add('cmd'); m.el.querySelector('header').remove();
  m.bd.style.padding='0';
  m.bd.innerHTML = `<input placeholder="جستجو: شماره موبایل، نام، کد رهگیری، دوره، پست، بخش…" autocomplete="off"><div class="res"></div>`;
  const inp = $('input',m.bd), res = $('.res',m.bd); let items=[], sel=0, seq=0;
  const draw = () => { A.mount(res, items.length ? A.html`${items.map((it,i)=>A.html`<div class="it ${i===sel?'sel':''}" data-i="${i}">${A.icon(it.i||'search')}<span>${it.t}</span><small>${it.k}</small></div>`)}` : A.html`<div class="empty">نتیجه‌ای نیست</div>`); };
  const run = A.debounce(async ()=>{
    const v = inp.value.trim(); const my=++seq; const list = [];
    PAGES.filter(p=>!v||p.t.includes(v)).slice(0,6).forEach(p=>list.push({t:p.t,k:'بخش',go:p.go,i:'globe'}));
    if(v.length>=2){
      const en = A.toEn(v).replace(/[%,()]/g,' '); const like = `%${en}%`;
      const [u,c,p,po] = await Promise.all([
        sb.from('academy_users').select('id,full_name,phone,email').or(`phone.ilike.${like},full_name.ilike.${like},email.ilike.${like},username.ilike.${like}`).limit(6),
        sb.from('courses').select('id,title').ilike('title',like).limit(4),
        sb.from('payments').select('id,track_id,ref_number,amount,status').or(`track_id.eq.${en},ref_number.eq.${en}`).limit(4),
        sb.from('posts').select('id,title').ilike('title',like).limit(4) ]);
      if(my!==seq) return;
      (u.data||[]).forEach(x=>list.push({t:`${x.full_name||'بدون نام'} — ${x.phone||x.email||''}`,k:'هنرجو',go:'students/'+x.id,i:'users'}));
      (c.data||[]).forEach(x=>list.push({t:x.title,k:'دوره',go:'courses/'+x.id,i:'book'}));
      (p.data||[]).forEach(x=>list.push({t:`تراکنش ${x.track_id||x.ref_number} — ${A.money(x.amount)}`,k:'پرداخت',go:'payments/'+x.id,i:'card'}));
      (po.data||[]).forEach(x=>list.push({t:x.title,k:'پست',go:'blog/'+x.id,i:'pen'}));
    }
    items = list; sel=0; draw();
  }, 220);
  const pick = i => { const it = items[i]; if(!it) return; m.close(true); A.go(it.go); };
  inp.addEventListener('input', run);
  inp.addEventListener('keydown', e=>{
    if(e.key==='ArrowDown'){ sel=Math.min(items.length-1,sel+1); draw(); e.preventDefault(); }
    else if(e.key==='ArrowUp'){ sel=Math.max(0,sel-1); draw(); e.preventDefault(); }
    else if(e.key==='Enter') pick(sel);
  });
  res.addEventListener('click', e=>{ const it=e.target.closest('[data-i]'); if(it) pick(+it.dataset.i); });
  run(); inp.focus();
};
document.addEventListener('keydown', e=>{ if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='k'){ e.preventDefault(); if(A.user) A.openSearch(); } });

/* ---------- auth / boot ---------- */
async function loadRole(){
  A.role = 'owner';
  try{
    const r = await sb.from('admin_roles').select('role').eq('email', A.user.email).maybeSingle();
    if(!r.error && r.data && ['owner','support','editor'].includes(r.data.role)) A.role = r.data.role;
    A.has.roles = !r.error;
  }catch(e){}
}
async function needMfa(){
  try{
    const { data } = await sb.auth.mfa.getAuthenticatorAssuranceLevel();
    return data && data.nextLevel==='aal2' && data.currentLevel!=='aal2';
  }catch(e){ return false; }
}
async function enter(){
  const { data:{ user } } = await sb.auth.getUser();
  if(!user){ showLogin(); return; }
  A.user = user; await loadRole();
  $('#login').style.display='none'; $('#app').classList.add('show');
  $('#userName').textContent = (user.email||'').split('@')[0]; $('#userAv').textContent = A.initial(user.email);
  $('#roleName').textContent = {owner:'مدیر کل',support:'پشتیبان',editor:'ویرایشگر'}[A.role];
  renderNav(); A.refreshCounts();
  if(!location.hash) location.hash = '#/'+(A.can('dashboard')?'':'');
  render();
}
function showLogin(){ $('#app').classList.remove('show'); $('#login').style.display='flex'; $('#loginForm').classList.remove('hide'); $('#mfaForm').classList.add('hide'); }
async function afterPassword(){
  if(await needMfa()){
    $('#loginForm').classList.add('hide'); $('#mfaForm').classList.remove('hide'); $('#mfaCode').focus(); return;
  }
  enter();
}
A.boot = async () => {
  $('#loginForm').addEventListener('submit', async e=>{
    e.preventDefault(); const btn=$('#loginBtn'), err=$('#loginErr'); err.classList.add('hide');
    btn.disabled=true; btn.textContent='در حال ورود…';
    const { error } = await sb.auth.signInWithPassword({ email:$('#loginEmail').value.trim(), password:$('#loginPass').value });
    btn.disabled=false; btn.textContent='ورود به پنل';
    if(error){ err.textContent='ایمیل یا رمز اشتباه است.'; err.classList.remove('hide'); return; }
    afterPassword();
  });
  $('#mfaForm').addEventListener('submit', async e=>{
    e.preventDefault(); const err=$('#mfaErr'); err.classList.add('hide');
    try{
      const { data:f } = await sb.auth.mfa.listFactors();
      const factor = (f.totp||[]).find(x=>x.status==='verified') || (f.totp||[])[0];
      if(!factor) throw new Error('عامل دو مرحله‌ای پیدا نشد');
      const ch = await sb.auth.mfa.challenge({ factorId:factor.id }); if(ch.error) throw ch.error;
      const v = await sb.auth.mfa.verify({ factorId:factor.id, challengeId:ch.data.id, code:A.toEn($('#mfaCode').value).trim() }); if(v.error) throw v.error;
      enter();
    }catch(ex){ err.textContent='کد نادرست است یا منقضی شده.'; err.classList.remove('hide'); }
  });
  $('#logoutBtn').addEventListener('click', async ()=>{ await sb.auth.signOut(); location.hash=''; location.reload(); });
  $('#menuBtn').addEventListener('click', ()=>$('#aside').classList.toggle('open'));
  $('#searchBtn').addEventListener('click', ()=>A.openSearch());
  $('#mfaCancel').addEventListener('click', async ()=>{ await sb.auth.signOut(); location.reload(); });
  const { data } = await sb.auth.getSession();
  if(data.session) afterPassword(); else showLogin();
};
})();
