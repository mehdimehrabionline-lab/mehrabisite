/* ==========================================================
   بلاگ: فهرست و ویرایشگر حرفه‌ای (دیداری + HTML، درج تصویر، پیش‌نمایش، پیش‌نمایش گوگل، ذخیره‌ی خودکار پیش‌نویس)
   ========================================================== */
(function(){
'use strict';
const { html, esc, $, $$ } = A;
const CATS = ['هوش مصنوعی','تولید محتوا','ویدیوگرافی','آموزش','عمومی'];
const PS = 20;

A.route('blog', { title:a=>a[0]?(a[0]==='new'?'پست جدید':'ویرایش پست'):'بلاگ', nav:'blog', async render(box, args){
  if(args[0]) return editor(box, args[0]);
  const st = A.state.blog = A.state.blog || { f:'', q:'', page:1 };
  A.mount(box, html`<div class="toolbar"><input class="in sm" id="bq" style="max-width:260px" placeholder="جستجو در عنوان…" value="${st.q}">
    <div class="seg">${[['','همه'],['pub','منتشرشده'],['draft','پیش‌نویس']].map(x=>html`<button data-act="bf" data-k="${x[0]}" class="${x[0]===st.f?'on':''}">${x[1]}</button>`)}</div><div class="sp"></div>
    <a class="btn primary" href="#/blog/new">${A.icon('plus')} پست جدید</a></div><div id="bList"></div>`);
  $('#bq').oninput = A.debounce(()=>{ st.q=$('#bq').value.trim(); st.page=1; list(); },350);
  A.handlers.page = el=>{ st.page=+el.dataset.p; list(); };
  async function list(){
    let q = sb.from('posts').select('id,title,category,is_published,is_featured,views,created_at,slug',{count:'exact'});
    if(st.f==='pub') q=q.eq('is_published',true); else if(st.f==='draft') q=q.eq('is_published',false);
    if(st.q) q=q.ilike('title',`%${st.q.replace(/[%,()]/g,'')}%`);
    const r = await q.order('created_at',{ascending:false}).range((st.page-1)*PS, st.page*PS-1);
    if(r.error){ A.mount($('#bList'), html`<div class="alert err">${A.missing(r.error)?'جدول posts ساخته نشده.':A.errMsg(r.error)}</div>`); return; }
    A.mount($('#bList'), html`${A.table([
      {h:'عنوان',c:p=>html`<a href="#/blog/${p.id}"><b>${p.title||'—'}</b></a><div class="mono muted" style="font-size:11.5px">${p.slug||''}</div>`},{h:'دسته',c:p=>p.category||'—'},
      {h:'وضعیت',c:p=>html`${p.is_published?A.badge('منتشر','ok'):A.badge('پیش‌نویس','mute')} ${p.is_featured?A.badge('ویژه','acc'):''}`},{h:'بازدید',c:p=>A.fa(p.views||0),cls:'num'},{h:'تاریخ',c:p=>A.date(p.created_at)},
      {h:'',c:p=>html`${p.is_published?html`<a class="btn sm" target="_blank" rel="noopener" href="/p/${p.slug}">${A.icon('eye')}</a> `:''}<a class="btn sm" href="#/blog/${p.id}">${A.icon('edit')}</a> <button class="btn sm" data-act="post-pub" data-id="${p.id}" data-on="${p.is_published?1:0}">${p.is_published?'پیش‌نویس':'انتشار'}</button> <button class="btn sm danger" data-act="post-del" data-id="${p.id}" data-cover="">${A.icon('trash')}</button>`}],
      r.data||[], {empty:'هنوز پستی نیست'})}${A.pager(r.count||0, st.page, PS)}`);
  }
  list();
}});
A.on('bf', el=>{ A.state.blog.f=el.dataset.k; A.state.blog.page=1; A.rerender(); });
A.on('post-pub', async el=>{ const on=el.dataset.on==='1'; await A.q(sb.from('posts').update({is_published:!on,updated_at:new Date().toISOString()}).eq('id',el.dataset.id)); A.audit(on?'post_unpublish':'post_publish','posts',el.dataset.id); A.toast(on?'به پیش‌نویس تبدیل شد':'منتشر شد ✓','ok'); A.syncCache(['posts']); A.rerender(); });
A.on('post-del', async el=>{
  const p = await A.q(sb.from('posts').select('id,title,cover_url').eq('id',el.dataset.id).maybeSingle());
  if(!await A.confirm({title:'حذف پست',message:`«${p.title}» و تمام دیدگاه‌هایش حذف شود؟ این کار برگشت ندارد.`,danger:true,confirm:'حذف پست'})) return;
  await A.q(sb.from('posts').delete().eq('id',p.id)); A.removeStored(p.cover_url); A.audit('post_delete','posts',p.id,{title:p.title}); A.syncCache(['posts']); A.toast('حذف شد'); A.go('blog');
});

/* ---------- ویرایشگر ---------- */
async function editor(box, id){
  const isNew = id==='new'; let p = {};
  if(!isNew){ p = await A.q(sb.from('posts').select('*').eq('id',id).maybeSingle()); if(!p){ A.mount(box, html`<div class="alert err">پست پیدا نشد.</div>`); return; } }
  const dk = 'mm_draft_post_'+(isNew?'new':id); const st = { dirty:false, cover:null, html: p.content||'', htmlEn: p.content_en||'', mode:'visual' };
  A.dirty = ()=>st.dirty;
  A.mount(box, html`
    <div class="page-h"><a class="btn ghost" href="#/blog">‹ بلاگ</a><h2>${isNew?'پست جدید':'ویرایش پست'}</h2><div class="sp"></div>
      <span class="muted" id="draftNote" style="font-size:12px"></span><button class="btn" id="bPrev">${A.icon('eye')} پیش‌نمایش</button>
      <button class="btn primary" id="bSave">${A.icon('check')} ذخیره</button></div>
    <div class="grid" style="grid-template-columns:minmax(0,1fr) 340px;align-items:start;gap:18px" id="bgrid">
      <div>
        <div class="card"><div class="fld"><label class="lb">عنوان *</label><input class="in" id="pT" value="${p.title||''}" style="font-size:18px"></div>
          <div class="ed-bar" id="edBar">
            <button data-c="bold" title="ضخیم"><b>B</b></button><button data-c="italic" title="کج"><i>I</i></button><button data-b="h2">عنوان ۲</button><button data-b="h3">عنوان ۳</button><button data-b="p">متن</button>
            <button data-c="insertUnorderedList">• فهرست</button><button data-c="insertOrderedList">۱. فهرست</button><button data-b="blockquote">❝ نقل‌قول</button><button data-l>${A.icon('link')} لینک</button><button data-i>${A.icon('image')} تصویر</button><button data-c="insertHorizontalRule">―</button><button data-c="removeFormat">پاک‌کردن قالب</button>
            <span style="flex:1"></span><button data-m>&lt;/&gt; HTML</button></div>
          <div class="ed-area" id="pEd" contenteditable="true" dir="rtl"></div><textarea class="in ltr mono hide" id="pHtml" style="min-height:420px;border-radius:0 0 9px 9px"></textarea><input type="file" id="pImg" accept="image/*" class="hide"></div>
        <div class="card"><h3>خلاصه (برای کارت بلاگ و گوگل)</h3><textarea class="in" id="pEx" style="min-height:80px">${p.excerpt||''}</textarea><div class="help" id="exCnt"></div></div>
        <div class="card"><h3>نسخه‌ی انگلیسی (اختیاری)</h3><div class="fld"><label class="lb">Title</label><input class="in ltr" id="pTe" value="${p.title_en||''}"></div><div class="fld"><label class="lb">Excerpt</label><textarea class="in ltr" id="pExe" style="min-height:70px">${p.excerpt_en||''}</textarea></div><div class="fld"><label class="lb">Content (HTML)</label><textarea class="in ltr mono" id="pCe" style="min-height:200px">${p.content_en||''}</textarea></div><div class="fld"><label class="lb">Category</label><input class="in ltr" id="pCate" value="${p.category_en||''}"></div></div></div>
      <div>
        <div class="card"><h3>انتشار</h3><div class="fld"><label class="switch"><input type="checkbox" id="pPub" ${p.is_published?'checked':''}><i></i><span>منتشر شود</span></label></div><div class="fld"><label class="switch"><input type="checkbox" id="pFeat" ${p.is_featured?'checked':''}><i></i><span>پست ویژه</span></label></div>
          <div class="fld"><label class="lb">دسته</label><select class="in" id="pCat">${CATS.map(c=>html`<option ${c===(p.category||'هوش مصنوعی')?'selected':''}>${c}</option>`)}</select></div>
          <div class="fld"><label class="lb">زمان مطالعه (دقیقه)</label><div style="display:flex;gap:8px"><input class="in num" id="pRt" value="${A.fa(p.read_time||5)}"><button class="btn sm" id="pRtAuto" type="button">محاسبه</button></div></div></div>
        <div class="card"><h3>آدرس (slug)</h3><input class="in ltr" id="pSlug" value="${p.slug||''}"><div class="help mono" id="slugHelp"></div></div>
        <div class="card"><h3>تصویر شاخص</h3><div class="drop ${p.cover_url?'has':''}" id="pDrop">${p.cover_url?'برای تغییر کلیک کن':'انتخاب تصویر'}</div><input type="file" id="pCover" accept="image/*" class="hide"><img id="pCoverPrev" src="${p.cover_url||''}" style="${p.cover_url?'':'display:none;'}width:100%;border-radius:10px;margin-top:10px" alt=""></div>
        <div class="card"><h3>ویدیو (آپارات/یوتیوب)</h3><input class="in ltr" id="pVid" value="${p.video_url||''}" placeholder="https://…"></div>
        <div class="card"><h3>پیش‌نمایش در گوگل</h3><div class="serp"><div class="u" id="sU"></div><div class="tt" id="sT"></div><div class="d" id="sD"></div></div></div></div></div>`);
  const ed = $('#pEd'), src = $('#pHtml'); ed.innerHTML = st.html || '<p><br></p>';
  const getHtml = () => st.mode==='html' ? src.value : ed.innerHTML;
  const mark = ()=>{ st.dirty=true; try{ localStorage.setItem(dk, JSON.stringify({t:Date.now(),title:$('#pT').value,html:getHtml(),ex:$('#pEx').value})); $('#draftNote').textContent='پیش‌نویس خودکار ذخیره شد'; }catch(e){} };
  $$('#bgrid input,#bgrid textarea,#bgrid select, #pT').forEach(e=>{ if(e.type!=='file') e.addEventListener('input',mark); });
  ed.addEventListener('input',mark);
  ed.addEventListener('paste',e=>{ e.preventDefault(); const t=(e.clipboardData||window.clipboardData).getData('text/plain'); document.execCommand('insertHTML',false, esc(t).split(/\n{2,}/).map(x=>'<p>'+x.replace(/\n/g,'<br>')+'</p>').join('')); });
  // بازیابی پیش‌نویس
  try{ const d = JSON.parse(localStorage.getItem(dk)||'null'); if(d && d.html && d.html!==st.html && Date.now()-d.t<14*86400000){ A.confirm({title:'پیش‌نویس ذخیره‌نشده',message:'نسخه‌ای ذخیره‌نشده از همین پست در این مرورگر هست ('+A.ago(d.t)+'). بازیابی شود؟',confirm:'بازیابی'}).then(ok=>{ if(ok){ $('#pT').value=d.title||$('#pT').value; ed.innerHTML=d.html; $('#pEx').value=d.ex||$('#pEx').value; st.dirty=true; } else localStorage.removeItem(dk); }); } }catch(e){}
  // نوار ابزار
  $$('#edBar [data-c]').forEach(b=>b.onmousedown=e=>{ e.preventDefault(); if(st.mode==='html') return; document.execCommand(b.dataset.c); mark(); });
  $$('#edBar [data-b]').forEach(b=>b.onmousedown=e=>{ e.preventDefault(); if(st.mode==='html') return; document.execCommand('formatBlock',false,b.dataset.b==='p'?'p':b.dataset.b); mark(); });
  $('#edBar [data-l]').onmousedown = async e=>{ e.preventDefault(); const sel=window.getSelection(); const range=sel.rangeCount?sel.getRangeAt(0).cloneRange():null; const u = await A.prompt({title:'افزودن لینک',label:'آدرس لینک',placeholder:'https://…'}); if(!u) return; if(!/^(https?:\/\/|\/|#|mailto:)/i.test(u)){ A.toast('آدرس باید با https:// شروع شود','err'); return; } ed.focus(); if(range){ sel.removeAllRanges(); sel.addRange(range); } document.execCommand('createLink',false,u); mark(); };
  $('#edBar [data-i]').onmousedown = e=>{ e.preventDefault(); const sel=window.getSelection(); st.range=sel.rangeCount?sel.getRangeAt(0).cloneRange():null; $('#pImg').click(); };
  $('#pImg').onchange = async e=>{ const f=e.target.files[0]; e.target.value=''; if(!f) return; try{ A.toast('در حال آپلود تصویر…'); const url = await A.uploadImage(f,'blog'); ed.focus(); if(st.range){ const s=window.getSelection(); s.removeAllRanges(); s.addRange(st.range); } document.execCommand('insertHTML',false,`<img src="${esc(url)}" alt="">`); mark(); }catch(err){ A.toast('آپلود تصویر ناموفق: '+A.errMsg(err),'err'); } };
  $('#edBar [data-m]').onclick = ()=>{ if(st.mode==='visual'){ src.value=ed.innerHTML; ed.classList.add('hide'); src.classList.remove('hide'); st.mode='html'; } else { ed.innerHTML=src.value||'<p><br></p>'; src.classList.add('hide'); ed.classList.remove('hide'); st.mode='visual'; } };
  // اسلاگ، سئو، زمان مطالعه
  const slugOf = () => A.cleanSlug($('#pSlug').value);
  const seo = ()=>{ $('#sU').textContent = location.host+' › p › '+(slugOf()||'…'); $('#sT').textContent = $('#pT').value||'عنوان پست'; $('#sD').textContent = ($('#pEx').value||'خلاصه‌ی پست').slice(0,160); const n=$('#pEx').value.length; $('#exCnt').innerHTML = `${A.fa(n)} نویسه — ${n>160?'<span class="warn">بیشتر از ۱۶۰ در گوگل بریده می‌شود</span>':n<70?'<span class="muted">کوتاه است؛ ۱۰۰ تا ۱۶۰ ایده‌آل است</span>':'<span class="ok">مناسب</span>'}`; $('#slugHelp').textContent = '/p/'+(slugOf()||'…'); };
  ['pT','pEx','pSlug'].forEach(i=>$('#'+i).addEventListener('input',seo)); seo();
  $('#pT').addEventListener('blur',()=>{ if(isNew && !$('#pSlug').value.trim()){ $('#pSlug').value = A.slug($('#pTe').value||$('#pT').value); seo(); } });
  $('#pRtAuto').onclick = ()=>{ const w = (getHtml().replace(/<[^>]+>/g,' ').match(/\S+/g)||[]).length; $('#pRt').value = A.fa(Math.max(1,Math.round(w/180))); mark(); };
  // تصویر شاخص
  $('#pDrop').onclick = ()=>$('#pCover').click();
  $('#pCover').onchange = e=>{ const f=e.target.files[0]; if(!f) return; st.cover=f; mark(); $('#pDrop').textContent='✓ '+f.name; $('#pDrop').classList.add('has'); const r=new FileReader(); r.onload=()=>{ $('#pCoverPrev').src=r.result; $('#pCoverPrev').style.display='block'; }; r.readAsDataURL(f); };
  // پیش‌نمایش
  $('#bPrev').onclick = ()=>{ const m = A.modal({ title:'پیش‌نمایش پست', size:'lg', body: A.raw('<iframe sandbox style="width:100%;height:65vh;border:0;border-radius:10px;background:#0c0b09"></iframe>') });
    m.$('iframe').srcdoc = `<html dir="rtl"><body style="font-family:Tahoma,sans-serif;background:#0c0b09;color:#e8e4da;line-height:2.1;padding:24px;max-width:760px;margin:auto"><h1>${esc($('#pT').value)}</h1>${$('#pCoverPrev').style.display!=='none'?`<img src="${esc($('#pCoverPrev').src)}" style="width:100%;border-radius:12px">`:''}${getHtml()}<style>img{max-width:100%;border-radius:10px}a{color:#c98a5a}blockquote{border-right:3px solid #c98a5a;margin:0;padding:0 16px;color:#aaa}</style></body></html>`; };
  // ذخیره
  $('#bSave').onclick = ev => A.busy(ev.currentTarget, async ()=>{
    const title=$('#pT').value.trim(), slug=slugOf(); if(!title){ A.toast('عنوان الزامی است','err'); return; } if(!slug){ A.toast('آدرس (slug) الزامی است — فقط حروف انگلیسی و خط تیره','err'); return; }
    const dup = await A.q(sb.from('posts').select('id').eq('slug',slug).neq('id',isNew?'00000000-0000-0000-0000-000000000000':id).limit(1)); if(dup.length){ A.toast('این آدرس برای پست دیگری استفاده شده','err'); return; }
    const content = getHtml(); const pay = { title, title_en:$('#pTe').value.trim()||null, slug, excerpt:$('#pEx').value.trim()||null, excerpt_en:$('#pExe').value.trim()||null,
      content:content&&content!=='<p><br></p>'?content:null, content_en:$('#pCe').value||null, video_url:$('#pVid').value.trim()||null, category:$('#pCat').value, category_en:$('#pCate').value.trim()||null,
      read_time:A.num($('#pRt').value)||5, is_published:$('#pPub').checked, is_featured:$('#pFeat').checked, updated_at:new Date().toISOString() };
    if(st.cover) pay.cover_url = await A.uploadImage(st.cover,'blog');
    const { row } = await A.saveRow('posts', pay, isNew?null:id, ['title','slug']);
    A.audit(isNew?'post_create':'post_edit','posts',(row&&row.id)||id,{title,published:pay.is_published}); st.dirty=false; A.dirty=null; try{ localStorage.removeItem(dk); }catch(e){}
    A.toast('پست ذخیره شد ✓','ok'); A.syncCache(['posts']); if(isNew && row) A.go('blog/'+row.id); else A.rerender();
  });
  if(innerWidth<1000) $('#bgrid').style.gridTemplateColumns='minmax(0,1fr)';
}
})();
