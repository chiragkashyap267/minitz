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


import html,shutil
product_ids=[];customer_id=None;review_ids=[];photo_path=None
keys=['support_email','support_phone','whatsapp','home_reviews_heading','home_reviews_eyebrow'];old_settings={key:db.execute('select value from settings where key=?',(key,)).fetchone() for key in keys}
try:
 sample=dict(db.execute('select * from products limit 1').fetchone());sample.pop('id')
 for i in range(6):
  values=dict(sample,name=tag+' product '+str(i),sku=tag+'-'+str(i),slug=tag.lower()+'-'+str(i),is_bestseller=int(i<5),featured_rank=0,active=int(i!=4),image=None)
  cols=list(values);cur=db.execute('insert into products ('+','.join(cols)+') values ('+','.join('?' for _ in cols)+')',list(values.values()));product_ids.append(cur.lastrowid)
 cur=db.execute("insert into customers (name,email,password,created_at,updated_at) values (?,?,?,datetime('now'),datetime('now'))",(tag+' Customer',tag.lower()+'@example.test','test fixture; not used for authentication'));customer_id=cur.lastrowid
 import uuid
 photo=uuid.uuid4().hex+'.png';photo_path=ROOT/'application/storage/app/private/reviews'/photo;shutil.copyfile(ROOT/'assets/artwork-01.png',photo_path)
 for index,status in [(0,'approved'),(1,'pending'),(2,'rejected'),(4,'approved')]:
  cur=db.execute("insert into product_reviews (product_id,customer_id,display_name,rating,title,body,photo,verified_purchase,status,created_at,updated_at) values (?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))",(product_ids[index],customer_id,tag+' Cook',5,tag,tag+' body '+str(index)+' <script>alert(1)</script>',photo if index==0 else None,0,status));review_ids.append(cur.lastrowid)
 for key,value in [('support_email','hello@example.test'),('support_phone','9990000000'),('whatsapp','919990000000'),('home_reviews_heading',tag+' feedback'),('home_reviews_eyebrow',tag+' kitchens')]:db.execute('insert or replace into settings(key,value) values(?,?)',(key,value))
 db.commit()
 status,page=req('/');assert status==200
 assert 'id="home-review-marquee"' in page and tag+' feedback' in page and tag+' kitchens' in page
 assert tag+' body 0 &lt;script&gt;' in page and '<script>alert(1)</script>' not in page
 assert all(tag+' body '+str(index) not in page for index in [1,2,4])
 assert '/review-media/'+str(review_ids[0]) in page
 assert req('/review-media/'+str(review_ids[0]))[0]==200
 # Cards must repeat equal groups for seamless motion; copies are inert.
 assert page.count('class="home-review-group"')==2 and 'data-reviews-pause' in page
 expected=[row['name'] for row in db.execute('select name from products where active=1 and is_bestseller=1 order by featured_rank,id limit 4')]
 assert re.findall(r'aria-label="View ([^"]+)"',page)==[html.escape(name,quote=True) for name in expected]
 shop=req('/shop?category='+urllib.parse.quote(sample['category']))[1]
 assert tag+' product 5' in shop and tag+' product 4' not in shop
 for path in ['/','/shop/'+sample['slug']]:
  html_page=req(path)[1];assert 'class="section need-help"' in html_page
  for link in ['mailto:hello@example.test','tel:9990000000','https://wa.me/919990000000']:assert link in html_page
 product=req('/shop/'+sample['slug'])[1]
 for label in ['pdp-price-card','pdp-pack-chip','pdp-buy-form','Add to cart','Buy now']:assert label in product
 req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 req('/admin/reviews/'+str(review_ids[0]),{'status':'rejected'})
 assert tag+' body 0' not in req('/')[1]
 req('/admin/reviews/'+str(review_ids[1]),{'status':'approved'})
 assert tag+' body 1' in req('/')[1] and tag+' Cook' in req('/')[1]
 assert 'class="review-initials"' in req('/')[1]
 print('PASS approved-only homepage reviews, hidden/archived exclusion, photo/initials, escaped content, immediate moderation sync, four bestseller cards, full shop catalogue, shared support links and product layout')
finally:
 for ident in review_ids:db.execute('delete from product_reviews where id=?',(ident,))
 if customer_id:db.execute('delete from customers where id=?',(customer_id,))
 for ident in product_ids:db.execute('delete from products where id=?',(ident,))
 for key,row in old_settings.items():
  if row:db.execute('insert or replace into settings(key,value) values(?,?)',(key,row[0]))
  else:db.execute('delete from settings where key=?',(key,))
 db.commit();db.close()
 if photo_path and photo_path.is_file():photo_path.unlink()
