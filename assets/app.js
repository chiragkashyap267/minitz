
const buy=document.querySelector('#buy'),order=document.querySelector('#order'),qty=document.querySelector('#qty');
buy.addEventListener('click',()=>{order.hidden=false;buy.setAttribute('aria-expanded','true');qty.focus()});
qty.addEventListener('input',()=>{const count=Number(qty.value);document.querySelector('#total').textContent=qty.validity.valid&&Number.isInteger(count)&&count>=1?'Subtotal ₹'+(count*375).toLocaleString('en-IN'):'Enter a quantity from 1 to 20.'});
document.querySelectorAll('[data-msg]').forEach(button=>button.addEventListener('click',()=>{document.getElementById(button.dataset.msg).hidden=false}));
