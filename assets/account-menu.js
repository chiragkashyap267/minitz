(() => {
 const menu=document.querySelector('[data-account-menu]');if(!menu)return;
 const button=menu.querySelector('[data-account-toggle]'),panel=menu.querySelector('.account-dropdown');
 const show=open=>{panel.hidden=!open;button.setAttribute('aria-expanded',String(open));};
 button.addEventListener('click',()=>show(panel.hidden));
 menu.addEventListener('pointerenter',event=>{if(event.pointerType==='mouse')show(true);});
 menu.addEventListener('pointerleave',event=>{if(event.pointerType==='mouse'&&!menu.contains(document.activeElement))show(false);});
 menu.addEventListener('focusout',event=>{if(!menu.contains(event.relatedTarget))show(false);});
 document.addEventListener('click',event=>{if(!menu.contains(event.target))show(false);});
 menu.addEventListener('keydown',event=>{if(event.key==='Escape'){show(false);button.focus();}if(event.key==='ArrowDown'&&event.target===button){event.preventDefault();show(true);panel.querySelector('a').focus();}});
})();
