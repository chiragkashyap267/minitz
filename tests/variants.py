"""Local category-dropdown and one-cover/seven-gallery-image integration checks."""
from pathlib import Path
import json
import urllib.request,urllib.parse,urllib.error,http.cookiejar,uuid,re,sqlite3
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088';tag='QACAT'+uuid.uuid4().hex[:8];c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));db=sqlite3.connect(ROOT/'application/database/database.sqlite');db.row_factory=sqlite3.Row;db.execute('PRAGMA foreign_keys=ON')

def req(path,data=None,files=None):
 headers={}
 if data is not None:
  token=re.search(r'name="csrf-token" content="([^"]+)"',req('/admin/login')[1]).group(1);data=dict(data,_token=token)
  if files:
   boundary='----'+uuid.uuid4().hex;parts=[]
   for k,value in data.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+str(value)+'\r\n').encode())
   for key,raw in files.items():parts.extend([('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"; filename="sample.png"\r\nContent-Type: image/png\r\n\r\n').encode(),raw,b'\r\n'])
   parts.append(('--'+boundary+'--\r\n').encode());data=b''.join(parts);headers['Content-Type']='multipart/form-data; boundary='+boundary
  else:data=urllib.parse.urlencode(data,doseq=True).encode()
 try:
  res=c.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=30);return res.status,res.read().decode('utf-8',errors='replace')
 except urllib.error.HTTPError as e:return e.code,e.read().decode('utf-8',errors='replace')


import sys,json,html
pid=None;variants=[];other=None;email=tag.lower()+'@example.test'
def payload(rows):
 d=dict(name=tag,sku=tag,category='Spice blends',weight='50 g',price='100',compare_price='150',stock='3',description='Local pack fixture',ingredients='Sample',allergens='Sample',colour='green',active='1',variants_present='1')
 for i,row in enumerate(rows):
  for key,value in row.items():d[f'variants[{i}][{key}]']=value
 return d
rows=[dict(label='100 g',price='199.50',compare_price='250',stock='4'),dict(label='200 g',price='350',compare_price='',stock='0')]
try:
 req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 assert req('/admin/products',payload(rows))[0]==200
 p=db.execute('select * from products where sku=?',(tag,)).fetchone();assert p;pid=p['id']
 variants=list(db.execute('select * from product_variants where product_id=? order by position',(pid,)));assert len(variants)==2
 a,b=variants
 assert (a['price'],a['compare_price'],a['stock'])==(19950,25000,4)
 assert tag in req('/admin/products')[1] and '199.50' in req('/admin/products')[1]
 page=req('/shop/'+p['slug'])[1];assert page.count('data-pack-choice')==3 and 'form="product-buy"' in page
 req('/cart/add',{'id':pid,'quantity':1})
 req('/cart/add',{'id':pid,'variant_id':a['id'],'quantity':2,'price':1})
 cart=req('/cart')[1];key=str(pid)+':v'+str(a['id']);assert f'quantities[{pid}]' in cart and f'quantities[{key}]' in cart and '100 g' in cart and '₹499' in cart
 req('/cart/add',{'id':pid,'variant_id':a['id'],'quantity':5});assert '₹499' in req('/cart')[1]
 req('/cart/add',{'id':pid,'variant_id':b['id'],'quantity':1});assert '200 g ·' not in req('/cart')[1]
 req('/cart/update',{f'quantities[{pid}]':1,f'quantities[{key}]':3})
 assert '₹698.50' in req('/checkout')[1]
 checkout=req('/checkout')[1];nonce=re.search(r'name="checkout_token" value="([^"]+)"',checkout).group(1)
 address=dict(name=tag,email=email,phone='9990000000',line1='QA address',city='Delhi',state='Delhi',pincode='110001',accept=1,checkout_token=nonce,total=1)
 assert req('/checkout',address)[0]==200
 order=db.execute('select * from orders where email=? order by id desc',(email,)).fetchone();assert order and order['subtotal']==69850
 items=json.loads(order['items']);assert len(items)==2 and items[1]['variant_id']==a['id'] and items[1]['weight']=='100 g' and items[1]['price']==19950
 assert db.execute('select stock from product_variants where id=?',(a['id'],)).fetchone()[0]==4
 assert '100 g' in req('/orders/'+order['token'])[1]
 # Size removal archives its row; cart/order snapshots keep their original pack.
 req('/cart/add',{'id':pid,'variant_id':a['id'],'quantity':1})
 req('/admin/products/'+str(pid),payload([dict(rows[1],id=b['id'])]))
 assert not db.execute('select active from product_variants where id=?',(a['id'],)).fetchone()[0]
 assert req('/cart/add',{'id':pid,'variant_id':a['id'],'quantity':1})[0]==422
 req('/cart/add',{'id':pid,'quantity':1})
 checkout=req('/checkout')[1];nonce=re.search(r'name="checkout_token" value="([^"]+)"',checkout).group(1)
 count=db.execute('select count(*) from orders where email=?',(email,)).fetchone()[0]
 req('/checkout',dict(address,checkout_token=nonce))
 assert db.execute('select count(*) from orders where email=?',(email,)).fetchone()[0]==count
 assert json.loads(db.execute('select items from orders where id=?',(order['id'],)).fetchone()[0])[1]['weight']=='100 g'
 # Invalid variants must not partially update the product.
 bad=payload([dict(rows[0],label=' 50 g ')]);bad['stock']='17';req('/admin/products/'+str(pid),bad)
 assert db.execute('select stock from products where id=?',(pid,)).fetchone()[0]==3
 bad=payload([dict(rows[0],compare_price='99')]);req('/admin/products/'+str(pid),bad)
 assert db.execute('select active from product_variants where id=?',(a['id'],)).fetchone()[0]==0
 # A caller cannot add a different product's pack using this product ID.
 another=db.execute('select id from products where id!=? limit 1',(pid,)).fetchone()[0]
 assert req('/cart/add',{'id':another,'variant_id':b['id'],'quantity':1})[0]==422
 bad=payload([dict(rows[0],id=b['id']+999999)]);req('/admin/products/'+str(pid),bad)
 assert db.execute('select active from product_variants where id=?',(a['id'],)).fetchone()[0]==0
 # No extra rows means a one-size product; absent flag preserves existing packs for old clients.
 req('/admin/products/'+str(pid),payload([]))
 assert req('/shop/'+p['slug'])[1].count('data-pack-choice')==1
 restored=[dict(rows[0],id=a['id']),dict(rows[1],id=b['id'],stock='2')]
 req('/admin/products/'+str(pid),payload(restored));base=payload([]);base.pop('variants_present');req('/admin/products/'+str(pid),base)
 assert db.execute('select count(*) from product_variants where product_id=? and active=1',(pid,)).fetchone()[0]==2
 # Cards and detail choose an in-stock additional pack if the default pack sells out.
 zero=payload(restored);zero['stock']='0';req('/admin/products/'+str(pid),zero)
 assert 'value="'+str(a['id'])+'"' in req('/shop?q='+tag)[1]
 print('PASS pack creation/edit persistence, one-size rendering, separate cart lines, server prices, per-pack limits, checkout snapshots, archived-size rejection, invalid-MRP/duplicate/foreign-ID protection and sold-out default fallback')
 if '--keep' in sys.argv:
  req('/admin/products/'+str(pid),payload(restored))
  target=ROOT/'.local/variant-preview.json';target.parent.mkdir(exist_ok=True);target.write_text(json.dumps(dict(product_id=pid,slug=p['slug'],variant_ids=[a['id'],b['id']],tag=tag)))
  print('Disposable browser fixture:',p['slug'])
finally:
 db.execute('delete from orders where email=?',(email,))
 if pid and '--keep' not in sys.argv:
  db.execute('delete from product_variants where product_id=?',(pid,));db.execute('delete from products where id=?',(pid,))
 db.commit();db.close()
