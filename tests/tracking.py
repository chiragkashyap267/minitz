"""Local HTTP checks for AWB lookup, redirects and admin persistence; no courier requests."""
from pathlib import Path
import sqlite3, uuid, json, urllib.request, urllib.parse, urllib.error, http.cookiejar, re
root=Path(__file__).resolve().parents[1]
db=sqlite3.connect(root/'application/database/database.sqlite');db.execute('PRAGMA foreign_keys=ON')
base='http://127.0.0.1:8088'
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args,**kwargs):return None
jar=http.cookiejar.CookieJar()
c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(jar),NoRedirect())
def req(path,data=None):
 if data is not None:
  token=re.search(r'name="csrf-token" content="([^"]+)"',req('/track-order')[2]).group(1)
  data=urllib.parse.urlencode(dict(data,_token=token)).encode()
 try:res=c.open(urllib.request.Request(base+path,data=data,headers={'Referer':base+'/track-order'}),timeout=20)
 except urllib.error.HTTPError as e:res=e
 return res.code,res.headers,res.read().decode(errors='replace')
ids=[];tag='QA-AWB-'+uuid.uuid4().hex
try:
 page=req('/track-order');assert page[0]==200 and 'AWB / Tracking number' in page[2] and 'account-dropdown' in page[2]
 for state,url in [('shipped','https://courier.example/track/'+tag),('paid','https://courier.example/track/paid'),('shipped','javascript:alert(1)')]:
  awb=tag+'-'+state+str(len(ids));token=str(uuid.uuid4())
  cur=db.execute("INSERT INTO orders (token,checkout_token,name,email,phone,address,items,subtotal,total,status,tracking,tracking_url,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))",(token,str(uuid.uuid4()),'Private Customer','private@example.test','9990000000','Private address','[]',100,100,state,awb,url));ids.append(cur.lastrowid);db.commit()
  status,headers,body=req('/track-order',{'tracking':' '+awb+' '})
  assert 'Private Customer' not in body and 'Private address' not in body
  if len(ids)==1:assert status==303 and headers['Location']==url
  else:assert status==302 and not headers['Location'].startswith('https://courier')
 assert req('/track-order',{'tracking':'unmatched-'+tag})[0]==302
 # Save actual courier settings through the admin form.
 req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 status,headers,body=req('/admin/orders/'+str(ids[0]),{'status':'shipped','tracking':tag,'courier':'Example Courier','tracking_url':'https://courier.example/track/'+tag})
 row=db.execute('SELECT tracking,courier,tracking_url FROM orders WHERE id=?',(ids[0],)).fetchone()
 assert row==(tag,'Example Courier','https://courier.example/track/'+tag), (status,row)
 assert req('/track-order',{'tracking':tag})[1]['Location']==row[2]
 order_token=db.execute('SELECT token FROM orders WHERE id=?',(ids[0],)).fetchone()[0]
 order_page=req('/orders/'+order_token)
 assert order_page[0]==200 and 'Track your parcel' in order_page[2] and row[2] in order_page[2]
 req('/admin/orders/'+str(ids[0]),{'status':'shipped','tracking':tag,'courier':'Example Courier','tracking_url':'javascript:alert(1)'})
 assert db.execute('SELECT tracking_url FROM orders WHERE id=?',(ids[0],)).fetchone()[0]==row[2]
 # Duplicate AWBs are ambiguous and must not redirect to an arbitrary order.
 db.execute('UPDATE orders SET tracking=?, tracking_url=? WHERE id=?',(tag,row[2],ids[2]));db.commit()
 assert req('/track-order',{'tracking':tag})[0]==302
 print('PASS tracking form, saved courier redirect, AWB trimming, unshipped/unknown/unsafe/duplicate rejection, admin persistence and no customer details disclosed; no external requests.')
finally:
 for oid in ids:db.execute('DELETE FROM orders WHERE id=?',(oid,))
 db.commit();db.close()
