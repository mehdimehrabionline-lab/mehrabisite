/* ==========================================================
   صندوق پیام‌ها: فرم تماس/سفارش خدمات، دیدگاه‌های بلاگ، نظرات هنرجویان
   ========================================================== */
(function(){
'use strict';
const { html, esc, $ } = A;
const OST = { new:['جدید','ok'], prog:['در حال انجام','warn'], done:['تکمیل شده','mute'] };

A.route('inbox', { title:'صندوق پیام‌ها', async render(box, args){
  await A.refreshCounts(); const c = A.state.counts || {};
  const tab = args[0] || (c.orders ? 'orders' : c.comments ? 'comments' : c.tst ? 'tst' : 'orders');
  const tabs = [['orders','پیام‌ها و سفارش خدمات',c.orders],['comments','دیدگاه‌های بلاگ',c.comments],['tst','نظرات هنرجویان',c.tst]];
  A.mount(box, html`<div class="tabs">${tabs.map(t=>html`<a class="tab ${t[0]===tab?'active':''}" href="#/inbox/${t[0]}">${t[1]}${t[2]?html`<span class="cnt">${A.fa(t[2])}</span>`:''}</a>`)}</div><div id="ibody"></div>`);
  const b = $('#ibody');
  if(tab==='orders') return orders(b); if(tab==='comments') return comments(b); return tst(b);
}});

async function orders(b){
  const st = A.state.ord = A.state.ord || { f:'' };
  let q = sb.from('orders').select('*').order('created_at',{ascending:false}).limit(300); if(st.f) q=q.eq('status',st.f);
  const r = await q; if(r.error){ A.mount(b, html`<div class="alert err">${A.errMsg(r.error)}</div>`); return; }
  A.state.ordRows = r.data||[];
  A.mount(b, html`<div class="toolbar"><div class="seg">${[['','همه'],['new','جدید'],['prog','در حال انجام'],['done','تکمیل شده']].map(x=>html`<button data-act="of" data-k="${x[0]}" class="${x[0]===st.f?'on':''}">${x[1]}</button>`)}</div></div>
    ${A.table([{h:'نام',c:o=>html`<b>${o.name||'—'}</b>`},{h:'تماس',c:o=>html`<div class="mono" style="font-size:12px">${o.phone||''}</div><div style="font-size:12px">${o.email||''}</div>`},{h:'خدمت',c:o=>o.service||'—'},{h:'تاریخ',c:o=>A.ago(o.created_at)},
      {h:'وضعیت',c:o=>A.badge((OST[o.status]||OST.new)[0],(OST[o.status]||OST.new)[1])},{h:'',c:o=>html`<button class="btn sm" data-act="o-view" data-id="${o.id}">${A.icon('eye')}</button> <button class="btn sm" data-act="o-next" data-id="${o.id}">وضعیت بعدی</button> <button class="btn sm danger" data-act="o-del" data-id="${o.id}">${A.icon('trash')}</button>`}], A.state.ordRows, {empty:'پیامی نیست'})}`);
}
A.on('of', el=>{ A.state.ord.f=el.dataset.k; A.rerender(); });
A.on('o-view', el=>{
  const o = A.state.ordRows.find(x=>x.id===el.dataset.id);
  A.modal({ drawer:true, title:'پیام از '+(o.name||'—'), body: html`<dl class="kv"><dt>نام</dt><dd>${o.name||'—'}</dd><dt>ایمیل</dt><dd>${o.email?html`<a href="mailto:${o.email}">${o.email}</a>`:'—'}</dd><dt>تلفن</dt><dd class="mono">${o.phone?html`<a href="tel:${o.phone}">${o.phone}</a>`:'—'}</dd><dt>خدمت</dt><dd>${o.service||'—'}</dd><dt>زمان</dt><dd>${A.dt(o.created_at)}</dd></dl>
    <h4 style="margin:18px 0 8px">متن پیام</h4><div style="white-space:pre-wrap;line-height:2;background:var(--panel2);padding:14px;border-radius:10px">${o.message||'—'}</div>` });
  if(o.status==='new') A.q(sb.from('orders').update({status:'prog'}).eq('id',o.id)).then(()=>A.refreshCounts()).catch(()=>{});
});
A.on('o-next', async el=>{ const o=A.state.ordRows.find(x=>x.id===el.dataset.id); const nx={new:'prog',prog:'done',done:'new'}[o.status]||'new'; await A.q(sb.from('orders').update({status:nx}).eq('id',o.id)); A.refreshCounts(); A.rerender(); });
A.on('o-del', async el=>{ if(!await A.confirm({title:'حذف پیام',message:'این پیام حذف شود؟',danger:true})) return; await A.q(sb.from('orders').delete().eq('id',el.dataset.id)); A.audit('order_delete','orders',el.dataset.id); A.refreshCounts(); A.rerender(); });

async function comments(b){
  const st = A.state.cmt = A.state.cmt || { f:'pending' };
  let q = sb.from('comments').select('id,name,email,content,is_approved,created_at,post_id').order('created_at',{ascending:false}).limit(300);
  if(st.f==='pending') q=q.eq('is_approved',false); else if(st.f==='ok') q=q.eq('is_approved',true);
  const r = await q; if(r.error){ A.mount(b, html`<div class="alert err">${A.missing(r.error)?'جدول comments ساخته نشده.':A.errMsg(r.error)}</div>`); return; }
  const posts = await A.byIds('posts','id,title,slug',(r.data||[]).map(c=>c.post_id));
  A.mount(b, html`<div class="toolbar"><div class="seg">${[['pending','در انتظار تأیید'],['ok','تأیید شده'],['','همه']].map(x=>html`<button data-act="cf2" data-k="${x[0]}" class="${x[0]===st.f?'on':''}">${x[1]}</button>`)}</div></div>
    ${A.table([{h:'نویسنده',c:c=>html`<b>${c.name}</b><div class="muted" style="font-size:12px">${c.email||''}</div>`},{h:'دیدگاه',c:c=>html`<div style="max-width:420px;white-space:pre-wrap;font-size:13px">${(c.content||'').slice(0,300)}</div>`},
      {h:'پست',c:c=>posts[c.post_id]?html`<a href="/p/${posts[c.post_id].slug}" target="_blank" rel="noopener">${posts[c.post_id].title}</a>`:'—'},{h:'تاریخ',c:c=>A.ago(c.created_at)},
      {h:'',c:c=>html`<button class="btn sm ${c.is_approved?'':'primary'}" data-act="c-ap" data-id="${c.id}" data-on="${c.is_approved?1:0}">${c.is_approved?'رد':'تأیید'}</button> <button class="btn sm danger" data-act="c-del" data-id="${c.id}">${A.icon('trash')}</button>`}], r.data||[], {empty:'دیدگاهی نیست'})}`);
}
A.on('cf2', el=>{ A.state.cmt.f=el.dataset.k; A.rerender(); });
A.on('c-ap', async el=>{ const on=el.dataset.on==='1'; await A.q(sb.from('comments').update({is_approved:!on}).eq('id',el.dataset.id)); A.audit('comment_'+(on?'reject':'approve'),'comments',el.dataset.id); A.refreshCounts(); A.rerender(); });
A.on('c-del', async el=>{ if(!await A.confirm({title:'حذف دیدگاه',message:'این دیدگاه حذف شود؟',danger:true})) return; await A.q(sb.from('comments').delete().eq('id',el.dataset.id)); A.audit('comment_delete','comments',el.dataset.id); A.refreshCounts(); A.rerender(); });

async function tst(b){
  const r = await sb.from('testimonials').select('*').eq('is_published',false).order('created_at',{ascending:false});
  if(r.error){ A.mount(b, html`<div class="alert err">${A.errMsg(r.error)}</div>`); return; }
  A.state.tstRows = r.data||[];
  A.mount(b, html`<p class="hint">نظراتی که هنرجوهای خریدار از داشبورد خودشان ثبت کرده‌اند و منتظر تأیید تو هستند.</p>${(r.data||[]).length?(r.data||[]).map(t=>html`<div class="card"><div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><b>${t.name}</b><span class="muted">${t.course||''}</span><span style="color:var(--accent)">${'★'.repeat(t.rating||5)}</span><span class="muted" style="margin-right:auto;font-size:12px">${A.ago(t.created_at)}</span></div>
      <p style="margin:12px 0;line-height:2;white-space:pre-wrap">${t.content}</p><button class="btn primary sm" data-act="tst-pub" data-id="${t.id}" data-on="0">تأیید و نمایش در سایت</button> <button class="btn sm" data-act="tst-edit" data-id="${t.id}">ویرایش</button> <button class="btn sm danger" data-act="tst-del" data-id="${t.id}">حذف</button></div>`):html`<div class="empty">${A.icon('check')}<div>نظر در انتظاری نیست</div></div>`}`);
}
})();
