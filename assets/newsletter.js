(() => {
 const dialog=document.querySelector('#newsletter-dialog');
 const dismissKey='minitz-newsletter-dismissed', joinedKey='minitz-newsletter-joined';
 const save=(key,value)=>{try{localStorage.setItem(key,value);}catch(_){}};
 const read=key=>{try{return localStorage.getItem(key);}catch(_){return null;}};
 const close=()=>{if(dialog?.open)dialog.close();};
 const open=()=>{if(!dialog||dialog.open||document.querySelector('dialog[open]'))return;dialog.showModal();document.body.classList.add('has-newsletter');};
 document.addEventListener('click',event=>{const trigger=event.target.closest('[data-open-newsletter]');if(trigger&&dialog){event.preventDefault();open();}if(event.target.closest('[data-newsletter-close]'))close();});
 if(dialog){
  dialog.addEventListener('close',()=>{document.body.classList.remove('has-newsletter');save(dismissKey,String(Date.now()));});
  dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const box=dialog.getBoundingClientRect();if(event.clientX<box.left||event.clientX>box.right||event.clientY<box.top||event.clientY>box.bottom)close();});
  let tries=0;
  const auto=()=>{if(document.hidden||document.documentElement.classList.contains('has-brand-intro')||document.querySelector('dialog[open]')){if(++tries<20)setTimeout(auto,1000);return;}open();};
  if(dialog.dataset.autoNewsletter==='1'&&!read(joinedKey)&&Date.now()-Number(read(dismissKey)||0)>7*24*60*60*1000)setTimeout(auto,9000);
 }
 document.querySelectorAll('[data-newsletter-form]').forEach(form=>form.addEventListener('submit',async event=>{
  event.preventDefault();const button=form.querySelector('button[type=submit],button:not([type])'),feedback=form.querySelector('[data-newsletter-feedback]');
  if(button.disabled)return;button.disabled=true;form.setAttribute('aria-busy','true');feedback.textContent='Saving your signup…';feedback.classList.remove('is-error');
  try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat()[0]||'Could not save right now. Try again.');feedback.textContent=data.message;save(joinedKey,'1');button.textContent='You’re on the list';if(dialog?.contains(form))setTimeout(close,2000);}
  catch(error){feedback.textContent=error.message||'Could not save right now. Try again.';feedback.classList.add('is-error');button.disabled=false;}
  finally{form.removeAttribute('aria-busy');}
 }));
})();
