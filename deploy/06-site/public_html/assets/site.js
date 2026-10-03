/* ==========================================================
   لایه‌ی رفتاری یکدستی سایت (فقط رابط کاربری):
   • دیالوگ‌های درون‌صفحه‌ای (mmAlert / mmConfirm)
   • آیکون SVG به‌جای ایموجی
   • اصلاح حروف‌فاصله‌ی متن فارسی و حداقل اندازه‌ی خوانا
   • اسکلت بارگذاری، پنهان‌شدن نماد اعتماد خراب
   ========================================================== */
(function(){
'use strict';

/* ---------- آیکون‌ها ---------- */
var P={
 chat:'M21 12a8 8 0 01-11.6 7.1L4 20l1-4.4A8 8 0 1121 12z',
 send:'M21 3L3 10.5l7 2.5 2.5 7zM21 3L10 13',
 camera:'M4 8h3l2-3h6l2 3h3v11H4zM12 17a4 4 0 100-8 4 4 0 000 8z',
 mail:'M3 6h18v12H3zM3 7l9 7 9-7',
 pin:'M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11zM12 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
 phone:'M8 3h8a1 1 0 011 1v16a1 1 0 01-1 1H8a1 1 0 01-1-1V4a1 1 0 011-1zM11 18h2',
 play:'M7 4l13 8-13 8z',
 flame:'M12 3c1 3.5 5 5 5 10a5 5 0 01-10 0c0-2 1-3 2-4 .3 1.5 1 2 2 2 0-3-1-5 1-8z',
 chart:'M4 20V10M10 20V4M16 20v-7M22 20H2',
 clock:'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
 users:'M16 19v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1M9.5 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM21 19v-1a4 4 0 00-3-3.9M15.5 4.2a3.5 3.5 0 010 6.6',
 cal:'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
 book:'M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zM4 19a2 2 0 012-2h13',
 user:'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1',
 globe:'M12 21a9 9 0 100-18 9 9 0 000 18zM3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18',
 card:'M3 6h18v12H3zM3 10h18M7 15h3',
 star:'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
 alert:'M12 3l10 18H2zM12 10v5M12 18h.01',
 checkc:'M12 21a9 9 0 100-18 9 9 0 000 18zM8 12.5l3 3 5-6',
 xc:'M12 21a9 9 0 100-18 9 9 0 000 18zM9 9l6 6M15 9l-6 6',
 hour:'M7 3h10M7 21h10M8 3c0 4 4 5 4 9s-4 5-4 9M16 3c0 4-4 5-4 9s4 5 4 9',
 key:'M14 10a4 4 0 11-3.9 3.1L3 20v-3h3v-3h3l2.1-2.1A4 4 0 0114 10z',
 lock:'M6 11h12v9H6zM8 11V8a4 4 0 118 0v3',
 edit:'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
 eye:'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
 clip:'M20 11l-8.5 8.5a5 5 0 01-7-7L13 4a3.5 3.5 0 015 5l-8.5 8.5a2 2 0 01-3-3L14 7',
 file:'M6 3h9l4 4v14H6zM14 3v5h5',
 dl:'M12 4v11M7 11l5 5 5-5M5 20h14',
 ticket:'M3 8a2 2 0 002-2h14a2 2 0 002 2v2a2 2 0 000 4v2a2 2 0 00-2 2H5a2 2 0 00-2-2v-2a2 2 0 000-4zM13 6v12',
 dot:'M12 16a4 4 0 100-8 4 4 0 000 8z'
};
// ایموجی → [آیکون، کلاس رنگ، پر؟]
var MAP={
 '💬':['chat'],'✈':['send'],'📸':['camera'],'✉':['mail'],'📍':['pin'],'📱':['phone'],'▶':['play','','fill'],'🔥':['flame','ic-flame'],
 '📊':['chart'],'⏱':['clock'],'👥':['users'],'📅':['cal'],'📚':['book'],'👤':['user'],'🌐':['globe'],'💳':['card'],'🎓':['book'],
 '⭐':['star','ic-acc'],'⚠':['alert','ic-warn'],'✅':['checkc','ic-ok'],'⏳':['hour','ic-warn'],'❌':['xc','ic-err'],'🔑':['key'],
 '🔒':['lock'],'🔐':['lock'],'📝':['edit'],'👁':['eye'],'📎':['clip'],'📄':['file'],'⬇':['dl'],'🎟':['ticket'],
 '🔵':['dot','','fill'],'🔴':['dot','ic-err','fill'],'👋':null,'🙏':null
};
var CLS={'🔵':'#6ab0ff','🔴':'#ff8080'};
var KEYS=Object.keys(MAP).map(function(k){return k.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');}).join('|');
var RE=new RegExp('('+KEYS+')\\uFE0F?','g');
function svg(ch){
  var m=MAP[ch]; if(m===null) return '';
  var d=P[m[0]]; var cls='ic'+(m[1]?' '+m[1]:'')+(m[2]?' fill':'');
  var style=CLS[ch]?' style="color:'+CLS[ch]+'"':'';
  return '<svg class="'+cls+'"'+style+' viewBox="0 0 24 24" aria-hidden="true"><path d="'+d+'"/></svg>';
}
var SKIP={SCRIPT:1,STYLE:1,TEXTAREA:1,NOSCRIPT:1,TITLE:1,OPTION:1,INPUT:1,SVG:1,CODE:1};
function iconify(root){
  if(!root) return;
  var w=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,{acceptNode:function(n){
    var p=n.parentNode; if(!p||SKIP[p.nodeName.toUpperCase()]) return NodeFilter.FILTER_REJECT;
    RE.lastIndex=0; return RE.test(n.nodeValue)?NodeFilter.FILTER_ACCEPT:NodeFilter.FILTER_REJECT;}});
  var list=[],n; while((n=w.nextNode())) list.push(n);
  list.forEach(function(t){
    var html=t.nodeValue.replace(/[&<>]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c];}).replace(RE,function(_,ch){return svg(ch);});
    var s=document.createElement('span'); s.className='mm-ico'; s.style.display='contents'; s.innerHTML=html; t.parentNode.replaceChild(s,t);
  });
}

/* ---------- اصلاح تایپوگرافی متن فارسی ---------- */
var AR=/[؀-ۿ]/;
function fixType(root){
  if(!root||root.nodeType!==1) return;
  var els=[root].concat([].slice.call(root.querySelectorAll('*')));
  els.forEach(function(e){
    if(SKIP[e.nodeName.toUpperCase()]||e.dataset&&e.dataset.mmFixed) return;
    var own=''; for(var c=e.firstChild;c;c=c.nextSibling) if(c.nodeType===3) own+=c.nodeValue;
    if(!own.trim()) return;
    var cs=getComputedStyle(e);
    if(AR.test(own)&&cs.letterSpacing!=='normal'&&parseFloat(cs.letterSpacing)>0) e.style.letterSpacing='0';
    var fs=parseFloat(cs.fontSize); if(fs&&fs<12) e.style.fontSize='12px';
    if(e.dataset) e.dataset.mmFixed='1';
  });
}

/* ---------- دسترسی‌پذیری: نام برای فیلدهایی که label ندارند ---------- */
function a11y(root){
  if(!root||root.nodeType!==1) return;
  [].forEach.call(root.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]),select,textarea'),function(i){
    if(i.getAttribute('aria-label')||i.getAttribute('aria-labelledby')||(i.labels&&i.labels.length)) return;
    var t=i.getAttribute('placeholder')||i.getAttribute('title')||i.name||i.id; if(t) i.setAttribute('aria-label',t);
  });
}

/* ---------- دیالوگ‌ها ---------- */
var lastFocus=null;
function dialog(o){
  return new Promise(function(res){
    lastFocus=document.activeElement;
    var ov=document.createElement('div'); ov.className='mm-ov'; ov.setAttribute('role','dialog'); ov.setAttribute('aria-modal','true');
    var ic=o.icon||'alert';
    ov.innerHTML='<div class="mm-dlg"><div class="mm-ic"></div><div class="mm-msg"></div><div class="mm-acts"></div></div>';
    ov.querySelector('.mm-ic').innerHTML=svg(ic==='alert'?'⚠':ic==='ok'?'✅':ic==='key'?'🔑':'⚠');
    ov.querySelector('.mm-msg').textContent=o.msg==null?'':String(o.msg);
    var acts=ov.querySelector('.mm-acts'); var done=false;
    function fin(v){ if(done) return; done=true; document.removeEventListener('keydown',kd,true); ov.remove(); if(lastFocus&&lastFocus.focus) try{lastFocus.focus();}catch(e){} res(v); }
    if(o.cancel){ var c=document.createElement('button'); c.type='button'; c.textContent=o.cancel; c.onclick=function(){fin(false);}; acts.appendChild(c); }
    var b=document.createElement('button'); b.type='button'; b.className='mm-ok'; b.textContent=o.ok||'باشه'; b.onclick=function(){fin(true);}; acts.appendChild(b);
    function kd(e){ if(e.key==='Escape'){ e.stopPropagation(); fin(false); } else if(e.key==='Tab'){ var f=ov.querySelectorAll('button'); if(!f.length) return; var i=[].indexOf.call(f,document.activeElement); e.preventDefault(); f[(i+(e.shiftKey?-1:1)+f.length)%f.length].focus(); } }
    document.addEventListener('keydown',kd,true);
    ov.addEventListener('mousedown',function(e){ if(e.target===ov&&!o.cancel) fin(true); });
    document.body.appendChild(ov); iconify(ov); b.focus();
  });
}
window.mmAlert=function(msg,opts){ return dialog({msg:msg,ok:(opts&&opts.ok)||'باشه',cancel:null,icon:(opts&&opts.icon)||'alert'}); };
window.mmConfirm=function(msg,opts){ opts=opts||{}; return dialog({msg:msg,ok:opts.ok||'بله، ادامه',cancel:opts.cancel||'انصراف',icon:opts.icon||'alert'}); };

/* ---------- اسکلت بارگذاری ---------- */
function skeletons(){
  [].forEach.call(document.querySelectorAll('.work-loading'),function(el){
    if(el.dataset.mmSk) return; el.dataset.mmSk='1';
    var t=(el.textContent||'').trim();
    el.setAttribute('role','status'); el.setAttribute('aria-label',t||'در حال بارگذاری');
    el.innerHTML='<div class="mm-sk-grid" aria-hidden="true">'+[1,2,3].map(function(){return '<div class="mm-sk-card"><div class="mm-sk"></div><div class="mm-sk"></div><div class="mm-sk"></div><div class="mm-sk"></div></div>';}).join('')+'</div>';
  });
}

/* ---------- نماد اعتماد خراب ---------- */
document.addEventListener('error',function(e){
  var t=e.target; if(t&&t.tagName==='IMG'){ var a=t.closest&&t.closest('a[href*="trustseal"]'); if(a) a.classList.add('mm-hide'); }
},true);

/* ---------- اجرا ---------- */
var pending=false, queue=[];
function run(nodes){
  nodes.forEach(function(n){ if(n.nodeType===3) n=n.parentNode; if(!n||n.nodeType!==1) return; iconify(n); fixType(n); a11y(n); });
  skeletons();
}
function start(){
  run([document.body]);
  if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches){ [].forEach.call(document.querySelectorAll('video[autoplay]'),function(v){ try{v.pause();}catch(e){} }); }
  new MutationObserver(function(ms){
    ms.forEach(function(m){ [].forEach.call(m.addedNodes,function(n){ queue.push(n); }); if(m.type==='characterData') queue.push(m.target); });
    if(pending||!queue.length) return; pending=true;
    setTimeout(function(){ var q=queue.splice(0); pending=false; run(q.filter(function(n){return !(n.classList&&n.classList.contains('mm-ico'));})); },60);
  }).observe(document.body,{childList:true,subtree:true,characterData:true});
}
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start); else start();
})();

/* ==========================================================
   ثبت کیفیت اتصال بازدیدکننده (RUM) — فقط برای عیب‌یابی سرعت/قطعی
   فقط «میزبان+مسیر» ثبت می‌شود (بدون پارامتر/توکن/موبایل). هر خطایی بی‌صدا نادیده گرفته می‌شود.
   ========================================================== */
(function(){
try{
  var EP='/api/rum.php', MAXEV=6, evCount=0, loadSent=false, tmr=null;
  var notes={res:[],fx:[],err:[]}, vid={err:0,wait:0,stall:0}, seenVid=false, t0=0;
  var of=window.fetch;
  function ls(k,v){ try{ if(v===undefined) return localStorage.getItem(k); localStorage.setItem(k,v); }catch(e){} return null; }
  var cid=ls('mm_cid'); if(!cid){ cid=Math.random().toString(36).slice(2,10)+Date.now().toString(36).slice(-4); ls('mm_cid',cid); }
  function clean(u){ try{ var a=new URL(u,location.href); return a.host+a.pathname.slice(0,60); }catch(e){ return String(u||'').split('?')[0].slice(0,80); } }
  function r(n){ return (typeof n==='number'&&isFinite(n))?Math.max(0,Math.round(n)):0; }
  function queue(body){ try{ var q=JSON.parse(ls('mm_rum_q')||'[]'); q.push(body); ls('mm_rum_q',JSON.stringify(q.slice(-8))); }catch(e){} }
  function post(body,beacon){
    try{
      if(beacon&&navigator.sendBeacon){ try{ if(navigator.sendBeacon(EP,new Blob([body],{type:'text/plain'}))) return; }catch(e){} }
      of.call(window,EP,{method:'POST',body:body,keepalive:true,headers:{'Content-Type':'text/plain'}}).then(function(x){ if(!x||!x.ok&&x.status!==204) queue(body); },function(){ queue(body); });
    }catch(e){}
  }
  function conn(){ var c=navigator.connection||navigator.mozConnection||{}; return {et:c.effectiveType||'',rtt:r(c.rtt),dl:c.downlink||0,sd:c.saveData?1:0,ty:c.type||''}; }
  function nav(){
    try{
      var n=performance.getEntriesByType&&performance.getEntriesByType('navigation')[0];
      if(n) return {dns:r(n.domainLookupEnd-n.domainLookupStart),con:r(n.connectEnd-n.connectStart),tls:n.secureConnectionStart>0?r(n.connectEnd-n.secureConnectionStart):0,
        ttfb:r(n.responseStart),srv:r(n.responseStart-n.requestStart),dl:r(n.responseEnd-n.responseStart),dcl:r(n.domContentLoadedEventEnd),load:r(n.loadEventEnd),proto:n.nextHopProtocol||'',size:r(n.transferSize)};
      var t=performance.timing; return {dns:r(t.domainLookupEnd-t.domainLookupStart),con:r(t.connectEnd-t.connectStart),tls:0,ttfb:r(t.responseStart-t.navigationStart),srv:r(t.responseStart-t.requestStart),dl:r(t.responseEnd-t.responseStart),dcl:r(t.domContentLoadedEventEnd-t.navigationStart),load:r(t.loadEventEnd-t.navigationStart),proto:'',size:0};
    }catch(e){ return {}; }
  }
  function slowRes(){
    try{
      var l=(performance.getEntriesByType('resource')||[]), o=[], h1=0;
      l.forEach(function(x){ if(x.nextHopProtocol==='http/1.1') h1++; if(x.duration>4000) o.push({u:clean(x.name),ms:r(x.duration),p:x.nextHopProtocol||''}); });
      o.sort(function(a,b){return b.ms-a.ms;}); return {n:l.length,h1:h1,slow:o.slice(0,3)};
    }catch(e){ return {}; }
  }
  function base(k){ return {k:k,cid:cid,p:location.pathname.slice(0,60),ts:Date.now(),vw:window.innerWidth||0,con:conn()}; }
  function sendLoad(){
    if(loadSent) return; loadSent=true;
    var d=base('load'); d.nav=nav(); d.rs=slowRes();
    if(notes.res.length) d.res=notes.res; if(notes.fx.length) d.fx=notes.fx; if(notes.err.length) d.err=notes.err;
    post(JSON.stringify(d));
    try{ var q=JSON.parse(ls('mm_rum_q')||'[]'); if(q.length){ ls('mm_rum_q','[]'); q.forEach(function(b){ post(b); }); } }catch(e){}
  }
  function sendEvent(extra){
    if(evCount>=MAXEV) return; evCount++;
    var d=base('ev'); if(notes.res.length) d.res=notes.res.splice(0); if(notes.fx.length) d.fx=notes.fx.splice(0); if(notes.err.length) d.err=notes.err.splice(0);
    if(extra) for(var k in extra) d[k]=extra[k];
    post(JSON.stringify(d));
  }
  function later(){ if(!loadSent) return; clearTimeout(tmr); tmr=setTimeout(function(){ sendEvent(); },1500); }

  /* منابع خراب (تصویر/اسکریپت/استایل) و خطاهای JS */
  window.addEventListener('error',function(e){
    try{
      var t=e.target;
      if(t&&t!==window&&(t.src||t.href)){ if(isMedia(t)) return; var u=clean(t.currentSrc||t.src||t.href); if(u.indexOf('/api/rum.php')<0&&notes.res.length<5){ notes.res.push(u); later(); } return; }
      if(notes.err.length<3){ notes.err.push(String(e.message||'').slice(0,100)); later(); }
    }catch(x){}
  },true);

  /* درخواست‌های fetch ناموفق یا کند (سایت و Supabase) */
  if(of){
    window.fetch=function(){
      var a=arguments, st=Date.now(), p=of.apply(this,a);
      try{
        var u=typeof a[0]==='string'?a[0]:(a[0]&&a[0].url)||''; 
        if(u.indexOf('/api/rum.php')<0){
          p.then(function(x){ var ms=Date.now()-st; if((!x.ok||ms>8000)&&notes.fx.length<5){ notes.fx.push({u:clean(u),ms:ms,st:x.status}); later(); } },
                 function(er){ if(notes.fx.length<5){ notes.fx.push({u:clean(u),ms:Date.now()-st,e:(er&&er.name)||'err'}); later(); } });
        }
      }catch(e){}
      return p;
    };
  }

  /* ویدیو: خطا، توقف‌های بافر، زمان تا اولین تصویر */
  function isMedia(t){ return t&&(t.tagName==='VIDEO'||t.tagName==='AUDIO'); }
  document.addEventListener('loadstart',function(e){ if(isMedia(e.target)){ seenVid=true; t0=Date.now(); vid.src=clean(e.target.currentSrc||e.target.src); } },true);
  document.addEventListener('playing',function(e){ if(isMedia(e.target)&&vid.ttff===undefined&&t0) vid.ttff=Date.now()-t0; },true);
  document.addEventListener('waiting',function(e){ if(isMedia(e.target)) vid.wait++; },true);
  document.addEventListener('stalled',function(e){ if(isMedia(e.target)) vid.stall++; },true);
  document.addEventListener('error',function(e){
    if(!isMedia(e.target)) return;
    vid.err++; vid.code=(e.target.error&&e.target.error.code)||0;
    sendEvent({vid:{code:vid.code,src:vid.src||clean(e.target.currentSrc),t:r(e.target.currentTime),ns:e.target.networkState,rs:e.target.readyState}});
  },true);
  var endSent=false;
  function sendEnd(){
    if(endSent||!seenVid||!(vid.err||vid.wait>2||vid.stall||vid.ttff>6000)) return;
    endSent=true; var d=base('end'); d.vid=vid; post(JSON.stringify(d),true);
  }
  window.addEventListener('pagehide',sendEnd);
  document.addEventListener('visibilitychange',function(){ if(document.visibilityState==='hidden') sendEnd(); });

  function go(){ setTimeout(sendLoad,2500); }
  if(document.readyState==='complete') go(); else window.addEventListener('load',go);
}catch(e){}
})();
