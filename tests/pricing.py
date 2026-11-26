"""Local MRP checks: decimal persistence, sale display and selling-price checkout."""
from pathlib import Path
import sqlite3,re,json,http.cookiejar,urllib.request,urllib.parse
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def client():return urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def get(c,path):return c.open(BASE+path,timeout=25).read().decode()
def post(c,path,data):
 token=re.search(r'name="csrf-token" content="([^"]+)"',get(c,'/')).group(1)
 return c.open(BASE+path,urllib.parse.urlencode(dict(data,_token=token),doseq=True).encode(),timeout=25).read().decode()
db=sqlite3.connect(ROOT/'application/database/database.sqlite');db.row_factory=sqlite3.Row;p=db.execute("select * from products where slug='everyday-garam-masala'").fetchone();admin=client();public=client()
post(admin,'/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
data={k:p[k] or '' for k in ['name','sku','category','weight','description','ingredients','allergens','colour','stock']};data.update(price='375.25',compare_price='499.99',active=str(int(p['active'])),is_bestseller=str(int(p['is_bestseller'])),featured_rank=p['featured_rank'],suggestion_mode=p['suggestion_mode'],related_display_limit=p['related_display_limit'],**{'related_product_ids[]':json.loads(p['related_product_ids'] or '[]')})
try:
 post(admin,'/admin/products/'+str(p['id']),data)
 row=db.execute('select price,compare_price from products where id=?',(p['id'],)).fetchone();assert tuple(row)==(37525,49999)
 page=get(public,'/shop/'+p['slug']).split('class="section related-products"')[0];assert '<del>' in page and '499.99' in page and 'product-sale-badge sale-inline' in page
 post(public,'/cart/buy-now',{'id':p['id'],'quantity':1});checkout=get(public,'/checkout');assert '375.25' in checkout and '499.99' not in checkout
 data['compare_price']='100';post(admin,'/admin/products/'+str(p['id']),data);assert db.execute('select compare_price from products where id=?',(p['id'],)).fetchone()[0]==49999
 data['compare_price']='375.25';post(admin,'/admin/products/'+str(p['id']),data);page=get(public,'/shop/'+p['slug']).split('class="section related-products"')[0];assert 'product-sale-badge sale-inline' not in page
 data['compare_price']='';post(admin,'/admin/products/'+str(p['id']),data);assert db.execute('select compare_price from products where id=?',(p['id'],)).fetchone()[0] is None
 print('PASS decimal selling price/MRP, sale badge, selling-price checkout, rejected MRP below selling price, equal-price/no-sale and optional MRP removal')
finally:
 db.execute('update products set price=?,compare_price=?,updated_at=? where id=?',(p['price'],p['compare_price'],p['updated_at'],p['id']));db.commit();db.close()
