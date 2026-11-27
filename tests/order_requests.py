"""HTTP owner/auth/CSRF and lifecycle controls; local demo only, no provider calls."""
from pathlib import Path
import urllib.request,urllib.parse,urllib.error,http.cookiejar,sqlite3,uuid,subprocess,re
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088';php=r'C:\xampp\php\php.exe';db=sqlite3.connect(root/'application/database/database.sqlite');db.execute('PRAGMA foreign_keys=ON');ids=[]
def client():return urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
c=client();other=client();admin=client()
def req(c,path,data=None):
 if data is not None:
  page=req(c,'/account/forgot-password')[1];token=re.search(r'name="csrf-token" content="([^"]+)"',page).group(1);data=urllib.parse.urlencode(dict(data,_token=token)).encode()
 try:r=c.open(path if path.startswith('http') else base+path,data,timeout=30)
 except urllib.error.HTTPError as e:r=e
 return r.code,r.read().decode(errors='replace')
def order(state):
 token=str(uuid.uuid4());tag=uuid.uuid4().hex
 oid=db.execute("INSERT INTO orders(token,checkout_token,name,email,phone,address,items,subtotal,total,status,payment_order_id,payment_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))",(token,str(uuid.uuid4()),'Request QA','request-'+tag+'@example.test','9990000000','QA address','[]',37500,37500,state,'order_'+tag,'pay_'+tag)).lastrowid;ids.append(oid);db.commit()
 code=r'require "application/vendor/autoload.php";$app=require "application/bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();Illuminate\Support\Facades\URL::forceRootUrl($argv[2]);echo App\Services\OrderLinks::email(Illuminate\Support\Facades\DB::table("orders")->find($argv[1]));'
 url=subprocess.check_output([php,'-r',code,str(oid),base],cwd=root,text=True);return oid,token,url
try:
 oid,token,url=order('paid');assert req(other,'/orders/'+token)[0]==404
 assert req(other,url+'&changed=1')[0]==403
 assert req(c,url)[0]==200
 assert req(other,'/orders/'+token+'/request',{'kind':'cancel','reason':'Please cancel this order'})[0]==404
 assert req(c,'/orders/'+token+'/request',{'kind':'cancel','reason':'Please cancel this order'})[0]==200
 change=db.execute('SELECT id FROM order_requests WHERE order_id=?',(oid,)).fetchone()[0]
 req(c,'/orders/'+token+'/request',{'kind':'cancel','reason':'Please cancel this order'});assert db.execute('SELECT count(*) FROM order_requests WHERE order_id=?',(oid,)).fetchone()[0]==1
 assert req(other,'/admin/orders/'+str(oid)+'/requests/'+str(change),{'action':'approve','note':'Cancellation approved'})[0]==200
 assert db.execute('SELECT status FROM orders WHERE id=?',(oid,)).fetchone()[0]=='paid'
 req(admin,'/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 assert 'Customer emails' in req(admin,'/admin/orders')[1]
 assert req(admin,'/admin/orders/'+str(oid),{'status':'processing'})[0]==409
 req(admin,'/admin/orders/'+str(oid)+'/requests/'+str(change),{'action':'approve','note':'Cancellation approved before dispatch'})
 assert db.execute('SELECT status FROM orders WHERE id=?',(oid,)).fetchone()[0]=='cancelled'
 assert db.execute('SELECT status FROM order_refunds WHERE order_id=?',(oid,)).fetchone()[0]=='demo'
 assert req(admin,'/admin/orders/'+str(oid)+'/requests/'+str(change),{'action':'approve','note':'Again approved'})[0]==409
 assert 'Full refund' in req(c,'/orders/'+token)[1]
 oid2,token2,url2=order('delivered');req(c,url2);req(c,'/orders/'+token2+'/request',{'kind':'return','reason':'Package seal was damaged'})
 change2=db.execute('SELECT id FROM order_requests WHERE order_id=?',(oid2,)).fetchone()[0]
 assert req(admin,'/admin/orders/'+str(oid)+'/requests/'+str(change2),{'action':'approve','note':'Wrong order request'})[0]==404
 req(admin,'/admin/orders/'+str(oid2)+'/requests/'+str(change2),{'action':'approve','note':'Please contact support to arrange return pickup'})
 assert db.execute('SELECT count(*) FROM order_refunds WHERE order_id=?',(oid2,)).fetchone()[0]==0
 req(admin,'/admin/orders/'+str(oid2)+'/requests/'+str(change2),{'action':'receive','note':'Return received and inspected'})
 assert db.execute('SELECT status FROM order_requests WHERE id=?',(change2,)).fetchone()[0]=='received'
 assert db.execute('SELECT status FROM order_refunds WHERE order_id=?',(oid2,)).fetchone()[0]=='demo'
 print('PASS signed guest order access, tamper rejection, customer ownership, admin guards, duplicate requests, cancellation/return approvals and refund staging; no real refunds or mail.')
finally:
 for oid in ids:db.execute('DELETE FROM orders WHERE id=?',(oid,))
 db.commit();db.close()
