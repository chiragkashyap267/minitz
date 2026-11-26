"""Local newsletter persistence, consent, deduplication and admin export checks."""
import urllib.request,urllib.parse,urllib.error,http.cookiejar,re,json,sqlite3,uuid,csv,io
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088';email='qa-news-'+uuid.uuid4().hex[:8]+'@example.test'
c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(path,data=None,token=True,ajax=False):
 headers={'Accept':'application/json' if ajax else 'text/html','Referer':BASE+'/newsletter'}
 if data is not None:
  if token:data=dict(data,_token=re.search(r'name="csrf-token" content="([^"]+)"',req('/newsletter')[1]).group(1))
  data=urllib.parse.urlencode(data).encode()
 try:
  r=c.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=20);return r.status,r.read().decode(errors='replace'),r.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(errors='replace'),e.url
with sqlite3.connect(ROOT/'application/database/database.sqlite') as db:
 try:
  assert req('/newsletter')[0]==200 and 'id="newsletter-dialog"' in req('/')[1]
  assert req('/admin/newsletter')[2].endswith('/admin/login')
  assert req('/admin/newsletter/export')[2].endswith('/admin/login')
  data=dict(email=email.upper(),name='=QA formula',source='popup')
  assert req('/newsletter',data,ajax=True)[0]==422
  assert req('/newsletter',dict(data,consent=1),token=False,ajax=True)[0]==419
  assert req('/newsletter',dict(data,email='bad',consent=1),ajax=True)[0]==422
  assert req('/newsletter',dict(data,consent=1),ajax=True)[0]==200
  row=db.execute('select email,name,consented_at,sync_status from newsletter_subscribers where email=?',(email,)).fetchone()
  assert row and row[0]==email and row[1]=='=QA formula' and row[2] and row[3]=='pending'
  assert req('/newsletter',dict(data,consent=1,source='page'),ajax=True)[0]==200
  assert db.execute('select count(*) from newsletter_subscribers where email=?',(email,)).fetchone()[0]==1
  assert req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})[2].endswith('/admin')
  assert email in req('/admin/newsletter')[1]
  status,body,_=req('/admin/newsletter/export');assert status==200
  rows=list(csv.reader(io.StringIO(body)));record=next(r for r in rows if r[0]==email)
  assert record[1]=="'=QA formula" and record[2] and record[3]=='page'
  print('PASS newsletter form, consent, CSRF, email validation, lowercase deduplication, permanent consent storage, private admin list/CSV and spreadsheet formula protection; provider sync remains pending')
 finally:
  db.execute('delete from newsletter_subscribers where email=?',(email,));db.commit()
