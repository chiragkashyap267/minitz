"""Local contact form persistence, validation and admin inbox checks."""
from pathlib import Path
import urllib.request,urllib.parse,urllib.error,http.cookiejar,uuid,re,sqlite3
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088';tag='QACAT'+uuid.uuid4().hex[:8];c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));db=sqlite3.connect(ROOT/'application/database/database.sqlite');db.row_factory=sqlite3.Row

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



try:
 for page in ['/', '/pages/contact']:
  status,html=req(page);assert status==200 and 'action="/contact"' in html
 assert 'Contact messages' not in req('/admin/messages')[1]
 for source in ['home','contact']:
  status,html=req('/contact',dict(name=tag,email='qa@example.com',message='<script>alert(1)</script>',source=source))
  assert status==200 and 'Your message has been received' in html
 assert db.execute('select count(*) from contact_messages where name=?',(tag,)).fetchone()[0]==2
 req('/contact',dict(name=tag,email='bad-email',message='test',source='home'))
 assert db.execute('select count(*) from contact_messages where name=?',(tag,)).fetchone()[0]==2
 try:
  c.open(BASE+'/contact',b'name=test',timeout=20)
  raise AssertionError('Missing CSRF accepted')
 except urllib.error.HTTPError as e:assert e.code==419
 req('/admin/login',dict(email='preview@minitz.test',password='Minitz-Preview-Only-2026!'))
 status,html=req('/admin/messages');assert status==200 and tag in html
 assert '&lt;script&gt;alert(1)&lt;/script&gt;' in html and '<script>alert(1)</script>' not in html
 print('PASS homepage/contact forms, persistent messages, success feedback, invalid email rejection, CSRF and private escaped admin inbox')
finally:
 db.execute('delete from contact_messages where name=?',(tag,));db.commit();db.close()
