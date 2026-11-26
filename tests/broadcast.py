"""Local admin broadcast validation; restores prior TV settings."""
import sqlite3,re,http.cookiejar,urllib.request,urllib.parse,urllib.error
from pathlib import Path
base='http://127.0.0.1:8088';c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def get(path):
 try:
  r=c.open(base+path,timeout=20);return r.read().decode()
 except urllib.error.HTTPError as e:raise AssertionError((path,e.code))
def post(path,data):
 token=re.search(r'name="csrf-token" content="([^"]+)"',get('/admin/login')).group(1)
 return c.open(base+path,urllib.parse.urlencode(dict(data,_token=token)).encode(),timeout=20).read().decode()
anonymous=urllib.request.build_opener(urllib.request.ProxyHandler({}))
assert anonymous.open(base+'/admin/tv').url.endswith('/admin/login')
try:
 anonymous.open(base+'/admin/tv',b'tv_enabled=1')
 raise AssertionError('Anonymous mutation accepted')
except urllib.error.HTTPError as e:assert e.code==419
post('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
db=sqlite3.connect(Path(__file__).resolve().parents[1]/'application/database/database.sqlite');settings=dict(db.execute('select key,value from settings'));keys=['tv_video_url','tv_video_id','tv_title','tv_enabled'];old={k:settings.get(k) for k in keys}
data={k:settings.get(k,'') for k in ['announcement','hero_heading','hero_text','about_heading','about_text','support_email','support_phone','whatsapp','instagram','facebook','youtube','shipping_paise','free_shipping_paise','policy_privacy','policy_terms','policy_shipping','policy_returns','policy_payment']}
try:
 try:
  c.open(base+'/admin/tv',b'tv_enabled=1',timeout=20)
  raise AssertionError('Missing CSRF accepted')
 except urllib.error.HTTPError as e:assert e.code==419
 page=get('/admin/tv');assert 'YouTube live stream link' in page
 post('/admin/tv',dict(tv_video_url='https://www.youtube.com/live/M7lc1UVf-VE',tv_title='QA broadcast',tv_enabled='1'))
 page=get('/');assert 'data-youtube-id="M7lc1UVf-VE"' in page and 'QA broadcast' in page
 assert 'youtube-nocookie.com/embed/M7lc1UVf-VE' in get('/admin/tv')
 post('/admin/content',data)
 assert db.execute("select value from settings where key='tv_enabled'").fetchone()[0]=='1'
 assert db.execute("select value from settings where key='tv_video_id'").fetchone()[0]=='M7lc1UVf-VE'
 for url in ['https://evil.example/watch?v=M7lc1UVf-VE','']:
  post('/admin/tv',dict(tv_video_url=url,tv_enabled='1'))
  assert db.execute("select value from settings where key='tv_video_id'").fetchone()[0]=='M7lc1UVf-VE'
 post('/admin/tv',dict(tv_video_url='https://www.youtube.com/live/M7lc1UVf-VE',tv_enabled='0'))
 assert 'data-load-youtube' not in get('/')
 print('PASS admin protection, CSRF, broadcast save/preview, isolated content settings, unsafe/missing URL rejection and disabled fallback')
finally:
 for k,val in old.items():
  if val is None:db.execute('delete from settings where key=?',(k,))
  else:db.execute('insert or replace into settings(key,value) values(?,?)',(k,val))
 db.commit();db.close()
