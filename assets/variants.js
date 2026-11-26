(() => {
 let next=Date.now();
 document.addEventListener('click',event=>{
  const add=event.target.closest('[data-add-pack]'),remove=event.target.closest('[data-remove-pack]');
  if(remove){remove.closest('[data-variant-row]').remove();return;}
  if(!add)return;const editor=add.closest('[data-variant-editor]'),rows=editor.querySelector('[data-variant-rows]');
  if(rows.children.length>=11){add.textContent='Maximum 12 pack sizes';return;}
  const fragment=editor.querySelector('template').content.cloneNode(true);fragment.querySelectorAll('[name]').forEach(input=>{input.name=input.name.replace('__INDEX__',String(next));});next++;rows.append(fragment);rows.lastElementChild.querySelector('input:not([type=hidden])').focus();
 });
 const money=value=>new Intl.NumberFormat('en-IN',{style:'currency',currency:'INR',maximumFractionDigits:2,minimumFractionDigits:0}).format(value/100);
 document.addEventListener('change',event=>{
  const choice=event.target.closest('[data-pack-choice]');if(!choice)return;
  const scope=choice.closest('.pdp-details'),price=Number(choice.dataset.packPrice),mrp=Number(choice.dataset.packMrp),stock=Number(choice.dataset.packStock),sale=mrp>price;
  const pricing=scope.querySelector('[data-pack-price] .product-pricing');pricing.replaceChildren();if(sale){const old=document.createElement('del');old.textContent=money(mrp);pricing.append(old);}const current=document.createElement('strong');current.textContent=money(price);pricing.append(current);
  const saleSlot=scope.querySelector('[data-pack-sale]');saleSlot.replaceChildren();if(sale){const badge=document.createElement('span');badge.className='product-sale-badge sale-inline';badge.textContent='Sale';saleSlot.append(badge);}
  const saving=scope.querySelector('[data-pack-saving]');saving.hidden=!sale;saving.textContent=sale?'You save '+money(mrp-price):'';
  const input=scope.querySelector('input[name=quantity]');input.max=String(Math.min(20,Math.max(1,stock)));input.value=String(Math.min(Math.max(1,Number(input.value)||1),Number(input.max)));
  const label=scope.querySelector('[data-pack-stock-label]');label.textContent=stock>0?'In stock':'Out of stock';label.classList.toggle('available',stock>0);label.classList.toggle('unavailable',stock<1);
  scope.querySelectorAll('.pdp-buy-form>.button').forEach(button=>button.disabled=stock<1);
 });
})();
