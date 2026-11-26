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
post('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
db=sqlite3.connect(Path(__file__).resolve().parents[1]/'application/database/database.sqlite');settings=dict(db.execute('select key,value from settings'));keys=['tv_video_url','tv_video_id','tv_title','tv_enabled'];old={k:settings.get(k) for k in keys}
data={k:settings.get(k,'') for k in ['announcement','hero_heading','hero_text','about_heading','about_text','support_email','support_phone','whatsapp','instagram','facebook','youtube','shipping_paise','free_shipping_paise','policy_privacy','policy_terms','policy_shipping','policy_returns','policy_payment']}
try:
 page=get('/admin/content');assert 'YouTube broadcast URL' in page
 post('/admin/content',dict(data,tv_video_url='https://www.youtube.com/live/M7lc1UVf-VE',tv_title='QA broadcast',tv_enabled='1'))
 page=get('/');assert 'data-youtube-id="M7lc1UVf-VE"' in page and 'QA broadcast' in page
 for url in ['https://evil.example/watch?v=M7lc1UVf-VE','']:
  post('/admin/content',dict(data,tv_video_url=url,tv_enabled='1'))
  assert db.execute("select value from settings where key='tv_video_id'").fetchone()[0]=='M7lc1UVf-VE'
 post('/admin/content',dict(data,tv_video_url='https://www.youtube.com/live/M7lc1UVf-VE',tv_enabled='0'))
 assert 'data-load-youtube' not in get('/')
 print('PASS admin broadcast save, valid rendering, unsafe/missing URL rejection and disabled fallback')
finally:
 for k,val in old.items():
  if val is None:db.execute('delete from settings where key=?',(k,))
  else:db.execute('insert or replace into settings(key,value) values(?,?)',(k,val))
 db.commit();db.close()
