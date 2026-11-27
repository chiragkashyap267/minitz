"""Customer reset flow, no real emails. Fixtures are removed."""
from pathlib import Path
import urllib.request,urllib.parse,urllib.error,http.cookiejar,sqlite3,subprocess,uuid,re,json,hashlib
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088';php=r'C:\xampp\php\php.exe';email='reset-'+uuid.uuid4().hex[:10]+'@example.test';db=sqlite3.connect(root/'application/database/database.sqlite');db.execute('PRAGMA foreign_keys=ON')
c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(path,data=None):
 if data is not None:
  token=re.search(r'name="csrf-token" content="([^"]+)"',req('/account/forgot-password')[1]).group(1);data=urllib.parse.urlencode(dict(data,_token=token)).encode()
 try:r=c.open(base+path,data,timeout=30)
 except urllib.error.HTTPError as e:r=e
 return r.code,r.read().decode(errors='replace')
def cli(code,*args):return subprocess.check_output([php,'-r',r'require "application/vendor/autoload.php";$app=require "application/bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();'+code,*args],cwd=root,text=True)
uid=None
try:
 hashed=cli(r'echo Illuminate\Support\Facades\Hash::make("Old-Sample-Password-2026!");')
 uid=db.execute('INSERT INTO customers(name,email,password,auth_version,created_at,updated_at) VALUES(?,?,?,?,datetime(\'now\'),datetime(\'now\'))',('Reset Tester',email,hashed,1)).lastrowid;db.commit()
 assert req('/account/login',{'email':email,'password':'Old-Sample-Password-2026!'})[0]==200
 assert 'Hello,' in req('/account')[1]
 assert 'Forgot password?' in req('/account/forgot-password')[1]
 known=req('/account/forgot-password',{'email':email})[1];unknown=req('/account/forgot-password',{'email':'unknown-'+email})[1]
 phrase='If an account exists for that email';assert phrase in known and phrase in unknown
 row=db.execute("SELECT id,payload FROM order_notifications WHERE recipient=? AND event='password_reset' ORDER BY id DESC",(email,)).fetchone();assert row
 data=json.loads(cli(r'echo Illuminate\Support\Facades\Crypt::decryptString(Illuminate\Support\Facades\DB::table("order_notifications")->where("id",$argv[1])->value("payload"));',str(row[0])))
 token=data['token'];assert token not in row[1];assert db.execute('SELECT token_hash FROM customer_password_resets WHERE email=?',(email,)).fetchone()[0]==hashlib.sha256(token.encode()).hexdigest()
 payload=dict(email=email,token=token,password='New-Sample-Password-2026!',password_confirmation='New-Sample-Password-2026!')
 assert req('/account/reset-password/'+token+'?email='+urllib.parse.quote(email))[0]==200
 db.execute("UPDATE customer_password_resets SET expires_at=datetime('now','-1 hour') WHERE email=?",(email,));db.commit();req('/account/reset-password',payload)
 assert db.execute('SELECT auth_version FROM customers WHERE id=?',(uid,)).fetchone()[0]==1
 db.execute("UPDATE customer_password_resets SET expires_at=? WHERE email=?",(cli('echo now()->addHour()->toDateTimeString();'),email));db.commit()
 req('/account/reset-password',payload);assert db.execute('SELECT auth_version FROM customers WHERE id=?',(uid,)).fetchone()[0]==2
 assert db.execute('SELECT count(*) FROM customer_password_resets WHERE email=?',(email,)).fetchone()[0]==0
 req('/account/reset-password',payload);assert db.execute('SELECT auth_version FROM customers WHERE id=?',(uid,)).fetchone()[0]==2
 req('/account/login',{'email':email,'password':'Old-Sample-Password-2026!'});assert 'Hello,' not in req('/account')[1]
 req('/account/login',{'email':email,'password':'New-Sample-Password-2026!'});assert 'Hello,' in req('/account')[1]
 # A previously authenticated session version becomes invalid on the next request.
 db.execute('UPDATE customers SET auth_version=auth_version+1 WHERE id=?',(uid,));db.commit();assert 'Hello,' not in req('/account')[1]
 print('PASS generic reset response, encrypted queued link, hashed expiring single-use token, password replacement, replay rejection and old-session invalidation; no emails sent.')
finally:
 db.execute('DELETE FROM order_notifications WHERE recipient=?',(email,));db.execute('DELETE FROM customer_password_resets WHERE email=?',(email,))
 if uid:db.execute('DELETE FROM customers WHERE id=?',(uid,))
 db.commit();db.close()
