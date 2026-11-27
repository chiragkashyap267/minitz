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

paths=[];pid=None
try:
 req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 req('/admin/categories',{'name':tag});assert db.execute('select id from product_categories where name=?',(tag,)).fetchone()
 req('/admin/categories',{'name':tag.lower()});assert db.execute('select count(*) from product_categories where lower(name)=?',(tag.lower(),)).fetchone()[0]==1
 assert 'value="'+tag+'"' in req('/admin/products')[1]
 raw=(ROOT/'assets/artwork-01.png').read_bytes();data={'name':tag,'sku':tag,'category':tag,'price':'199.50','weight':'50 g','description':'Local fixture','ingredients':'Sample','allergens':'Sample','colour':'green','stock':'5','active':'1'}
 files={'image':raw};files.update({'gallery_images['+str(i)+']':raw for i in range(7)})
 assert req('/admin/products',data,files)[0]==200
 p=db.execute('select * from products where sku=?',(tag,)).fetchone();assert p;pid=p['id'];paths.append(ROOT/'application/storage/app/public/uploads'/Path(p['image']).name)
 assert db.execute("select count(*) from product_media where product_id=? and kind='image' and active=1",(pid,)).fetchone()[0]==7
 page=req('/shop/'+p['slug'])[1];assert page.count('data-gallery-target=')==8
 data['stock']='9';req('/admin/products/'+str(pid),data,{'gallery_images[0]':raw});assert db.execute('select stock from products where id=?',(pid,)).fetchone()[0]==5
 req('/admin/products/'+str(pid)+'/media',{'position':'8','alt':tag},{'media':raw});assert db.execute('select count(*) from product_media where product_id=? and active=1',(pid,)).fetchone()[0]==7
 media=db.execute('select id from product_media where product_id=? limit 1',(pid,)).fetchone()[0];req('/admin/products/'+str(pid)+'/media/'+str(media)+'/archive',{})
 req('/admin/products/'+str(pid),data,{'gallery_images[0]':raw});assert db.execute('select count(*) from product_media where product_id=? and active=1',(pid,)).fetchone()[0]==7
 # Merchandising and suggested-product settings must persist and change public output.
 data.update(is_bestseller='1',featured_rank='0',suggestion_mode='manual',related_display_limit='2')
 data['related_product_ids[0]']='1';data['related_product_ids[1]']='2';data['related_product_ids[2]']='3'
 req('/admin/products/'+str(pid),data)
 saved=db.execute('select * from products where id=?',(pid,)).fetchone()
 assert saved['is_bestseller']==1 and saved['featured_rank']==0 and saved['suggestion_mode']=='manual' and saved['related_display_limit']==2
 assert json.loads(saved['related_product_ids'])==['1','2','3'] or json.loads(saved['related_product_ids'])==[1,2,3]
 assert 'related-products' in req('/shop/'+p['slug'])[1]
 data.update(suggestion_mode='hidden',is_bestseller='0')
 req('/admin/products/'+str(pid),data)
 assert 'related-products' not in req('/shop/'+p['slug'])[1] and tag not in req('/')[1]
 data['category']='Does not exist '+tag;req('/admin/products/'+str(pid),data);assert db.execute('select category from products where id=?',(pid,)).fetchone()[0]==tag
 print('PASS category creation, case-insensitive duplicate protection, dropdown, persistent cover + seven thumbnails, upload limit rejection, archive/replacement and valid-category enforcement')
finally:
 if pid:
  paths.extend(ROOT/'application/storage/app/public/uploads'/Path(row['path']).name for row in db.execute('select path from product_media where product_id=?',(pid,)))
  db.execute('delete from product_media where product_id=?',(pid,));db.execute('delete from products where id=?',(pid,))
 db.execute('delete from product_categories where name=?',(tag,));db.commit();db.close()
 for path in set(paths):
  if path.is_file():path.unlink()
