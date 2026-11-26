(() => {
 const panel=document.querySelector('#minitz-help-panel'),launcher=document.querySelector('[data-help-open]');if(!panel||!launcher)return;
 const log=panel.querySelector('[data-help-log]'),form=panel.querySelector('[data-help-form]');
 const close=()=>{panel.hidden=true;launcher.setAttribute('aria-expanded','false');launcher.focus();};
 launcher.addEventListener('click',()=>{if(!panel.hidden){close();return;}panel.hidden=false;launcher.setAttribute('aria-expanded','true');panel.querySelector('[data-help-close]').focus();});
 panel.querySelector('[data-help-close]').addEventListener('click',close);panel.addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();close();}});
 const answers={
  shop:{text:'Explore our published products and categories. Open a product to see pack sizes, ingredients, price and stock.',links:[['Shop products','/shop']]},
  track:{text:'Sign in, open Your orders, then View order. Any tracking reference added by the team appears there. For a guest order, use your order-confirmation link or contact the team.',links:[['Track your order','/track-order'],['My orders','/account'],['Contact support','/pages/contact']]},
  delivery:{text:'Check our shipping page for delivery details. The checkout shows the configured delivery charge before payment. Contact the team for help with a particular location.',links:[['Shipping & delivery','/pages/shipping'],['Contact the team','/pages/contact']]},
  returns:{text:'Read our returns/refunds and cancellation terms, then contact the team about your order. I can help you find the right page.',links:[['Returns & refunds','/pages/returns'],['Terms','/pages/terms'],['Contact support','/pages/contact']]},
  payment:{text:'Open your order page to check payment status. If a payment is still pending, use the secure payment retry there. Contact the team if you need help reconciling a payment.',links:[['My orders','/account'],['Payment information','/pages/payment'],['Contact support','/pages/contact']]},
  recipes:{text:'A little kitchen inspiration, right this way. Explore recipes and food stories from Minitz.',links:[['Recipes','/recipes'],['Food stories','/blogs']]},
  coupon:{text:'Enter an available coupon in your cart. Its minimum spend, dates and usage limits are checked before the discount is applied.',links:[['Open cart','/cart']]},
  support:{text:'The Minitz team can help with product questions and your order. Use the contact form or the support details on that page.',links:[['Contact the team','/pages/contact#contact-us']]},
  fallback:{text:'I’m Minitz’s quick-help bot. I can point you to products, orders, delivery, returns, payment help or recipes. For an order-specific answer, contact the team.',links:[['My orders','/account'],['Contact support','/pages/contact']]}
 };
 const identify=text=>{if(/refund|return|cancel|exchange|replace|वापस|रिफंड/i.test(text))return 'returns';if(/payment|paid|razorpay|money|पेमेंट|पैसे/i.test(text))return 'payment';if(/track|order|parcel|awb|ऑर्डर|ट्रैक/i.test(text))return 'track';if(/deliver|ship|pincode|pin code|courier|डिलीवरी/i.test(text))return 'delivery';if(/recipe|cook|food story|खाना|रेसिपी/i.test(text))return 'recipes';if(/coupon|discount|offer|कूपन/i.test(text))return 'coupon';if(/shop|product|size|price|ingredient|allerg|masala|मसाला|कीमत/i.test(text))return 'shop';if(/support|human|contact|whatsapp|email|help|मदद/i.test(text))return 'support';return 'fallback';};
 const message=(text,kind,links=[])=>{const bubble=document.createElement('div');bubble.className='help-chat-message '+kind+'-message';const p=document.createElement('p');p.textContent=text;bubble.append(p);if(links.length){const list=document.createElement('div');list.className='help-chat-links';links.forEach(([label,url])=>{const a=document.createElement('a');a.textContent=label;a.href=url;list.append(a);});bubble.append(list);}log.append(bubble);while(log.children.length>24)log.firstElementChild.remove();log.scrollTop=log.scrollHeight;};
 const ask=(text,topic)=>{message(text,'user');const answer=answers[topic]||answers.fallback;message(answer.text,'bot',answer.links);};
 panel.addEventListener('click',event=>{const button=event.target.closest('[data-help-topic]');if(button)ask(button.textContent,button.dataset.helpTopic);});
 form.addEventListener('submit',event=>{event.preventDefault();const input=form.elements.question,text=input.value.trim().slice(0,250);if(!text)return;ask(text,identify(text));input.value='';input.focus();});
})();
