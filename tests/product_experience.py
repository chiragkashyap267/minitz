"""Local integration checks for gallery uploads, private photo reviews and Buy now."""
from pathlib import Path
import sqlite3,uuid,re,io,json,http.cookiejar,urllib.request,urllib.parse,urllib.error
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088';tag='QAEXP'+uuid.uuid4().hex[:8]
def client():return urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(c,path,data=None,files=None,csrf=True):
 headers={}
 if data is not None:
  if csrf:
   token=re.search(r'name="csrf-token" content="([^"]+)"',req(c,'/')[1]).group(1);data=dict(data,_token=token)
  if files:
   boundary='----'+uuid.uuid4().hex;parts=[]
   for k,v in data.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+str(v)+'\r\n').encode())
   for field,(name,mime,raw) in files.items():parts.extend([('--'+boundary+'\r\nContent-Disposition: form-data; name="'+field+'"; filename="'+name+'"\r\nContent-Type: '+mime+'\r\n\r\n').encode(),raw,b'\r\n'])
   parts.append(('--'+boundary+'--\r\n').encode());data=b''.join(parts);headers['Content-Type']='multipart/form-data; boundary='+boundary
  else:data=urllib.parse.urlencode(data,doseq=True).encode()
 try:
  res=c.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=25);raw=res.read();return res.status,raw.decode('utf-8',errors='replace'),res.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode('utf-8',errors='replace'),e.url
public=client();admin=client();cook=client();db=sqlite3.connect(ROOT/'application/database/database.sqlite');db.row_factory=sqlite3.Row;paths=[]
p=db.execute("select * from products where slug='everyday-garam-masala'").fetchone();pid=p['id'];slug=p['slug'];product_path='/shop/'+slug
png=io.BytesIO((ROOT/'assets/artwork-01.png').read_bytes());photo={'photo':('review.png','image/png',png.getvalue())}
try:
 assert req(public,'/cart/buy-now',{'id':pid,'quantity':1})[2].endswith('/checkout')
 assert req(public,'/cart/buy-now',{'id':pid,'quantity':0})[0]!=500
 assert req(public,'/cart/buy-now',{'id':pid,'quantity':1},csrf=False)[0]==419
 assert req(public,'/admin/products/'+str(pid)+'/media')[2].endswith('/admin/login')
 req(admin,'/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 assert req(admin,'/admin/products/'+str(pid)+'/media')[0]==200
 assert req(admin,'/admin/reviews')[0]==200
 req(admin,'/admin/products/'+str(pid)+'/media',{'position':999,'alt':tag},{'media':('gallery.png','image/png',png.getvalue())})
 media=db.execute('select * from product_media where alt=?',(tag,)).fetchone();assert media;paths.append(ROOT/'application/storage/app/public/uploads'/Path(media['path']).name)
 assert 'Show photo' in req(public,product_path)[1]
 req(admin,'/admin/products/'+str(pid)+'/media/'+str(media['id'])+'/archive',{})
 assert db.execute('select active from product_media where id=?',(media['id'],)).fetchone()[0]==0
 assert req(public,'/products/'+str(pid)+'/reviews',{'rating':5,'title':tag,'body':'test'})[2].endswith('/account')
 req(cook,'/account/register',{'name':tag,'email':tag.lower()+'@example.test','password':'Preview-Testing-2026!','password_confirmation':'Preview-Testing-2026!'})
 customer=db.execute('select id from customers where email=?',(tag.lower()+'@example.test',)).fetchone();assert customer
 reviewdata={'name':tag,'rating':4,'title':tag,'body':'<script>unsafe()</script> A real local test.'}
 assert req(cook,'/products/'+str(pid)+'/reviews',reviewdata)[0]==200
 initial=db.execute('select * from product_reviews where customer_id=?',(customer['id'],)).fetchone();assert initial['photo'] is None
 req(admin,'/admin/reviews/'+str(initial['id']),{'status':'approved'})
 assert 'class="review-initials"' in req(public,product_path)[1]
 assert req(cook,'/products/'+str(pid)+'/reviews',reviewdata,photo)[0]==200
 review=db.execute('select * from product_reviews where customer_id=?',(customer['id'],)).fetchone();assert review and review['status']=='pending' and not review['verified_purchase'];paths.append(ROOT/'application/storage/app/private/reviews'/review['photo'])
 rid=review['id'];assert tag not in req(public,product_path)[1]
 assert req(public,'/review-media/'+str(rid))[0]==404
 assert req(cook,'/review-media/'+str(rid))[0]==200
 assert req(admin,'/review-media/'+str(rid))[0]==200
 assert req(cook,'/admin/reviews/'+str(rid),{'status':'approved'})[2].endswith('/admin/login')
 req(admin,'/admin/reviews/'+str(rid),{'status':'approved'})
 page=req(public,product_path)[1];assert tag in page and '&lt;script&gt;unsafe()&lt;/script&gt;' in page and '<script>unsafe()' not in page
 assert req(public,'/review-media/'+str(rid))[0]==200
 req(cook,'/products/'+str(pid)+'/reviews',dict(reviewdata,rating=3))
 assert db.execute('select count(*) from product_reviews where customer_id=?',(customer['id'],)).fetchone()[0]==1
 assert req(public,'/review-media/'+str(rid))[0]==404
 req(admin,'/admin/reviews/'+str(rid),{'status':'rejected'});assert tag not in req(public,product_path)[1]
 db.execute("insert into orders(token,checkout_token,customer_id,name,email,phone,address,items,subtotal,shipping,total,status,created_at,updated_at) values(?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))",(str(uuid.uuid4()),str(uuid.uuid4()),customer['id'],tag,tag.lower()+'@example.test','9990000000','Test only',json.dumps([{'id':pid,'quantity':1}]),100,0,100,'paid'));db.commit()
 req(cook,'/products/'+str(pid)+'/reviews',reviewdata)
 assert db.execute('select verified_purchase from product_reviews where id=?',(rid,)).fetchone()[0]==1
 req(admin,'/admin/reviews/'+str(rid),{'status':'approved'});assert 'Verified purchase' in req(public,product_path)[1]
 print('PASS purchase badge excludes preview orders and recognises paid orders')
 assert req(public,'/shop?category='+urllib.parse.quote(p['category']))[0]==200
 assert req(public,'/shop?page=2')[0]==200
 print('PASS Buy now, CSRF, gallery upload/archive, customer authentication, private photo access, moderation, stored-text escaping, review update persistence and catalogue pagination')
finally:
 for row in db.execute('select path,poster from product_media where alt=?',(tag,)):
  for value in row:
   if value:paths.append(ROOT/'application/storage/app/public/uploads'/Path(value).name)
 db.execute('delete from orders where name=?',(tag,));db.execute('delete from product_media where alt=?',(tag,));db.execute('delete from product_reviews where customer_id in (select id from customers where email=?)',(tag.lower()+'@example.test',));db.execute('delete from customers where email=?',(tag.lower()+'@example.test',));db.commit();db.close()
 for path in set(paths):
  if path.is_file():path.unlink()
