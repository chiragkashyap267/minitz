"""Local-only publishing checks: authorization, draft visibility and escaped article text."""
import sys,uuid,re,sqlite3,http.cookiejar,urllib.request,urllib.parse,urllib.error
from pathlib import Path
BASE='http://127.0.0.1:8088'
def client():return urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(c,path,data=None,csrf=True):
 if data is not None:
  if csrf:
   page=c.open(BASE+'/admin/login').read().decode(); token=re.search(r'name="csrf-token" content="([^"]+)"',page).group(1);data=dict(data,_token=token)
  data=urllib.parse.urlencode(data).encode()
 try:
  res=c.open(BASE+path,data=data,timeout=20);return res.status,res.read().decode(),res.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
slug='qa-blog-'+uuid.uuid4().hex[:10];public=client();admin=client();db=sqlite3.connect(Path(__file__).resolve().parents[1]/'application/database/database.sqlite')
try:
 assert req(public,'/admin/blogs')[2].endswith('/admin/login')
 assert req(public,'/admin/blogs',{'title':'Unauthorized'})[2].endswith('/admin/login')
 assert req(admin,'/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})[2].endswith('/admin')
 assert req(admin,'/admin/blogs')[0]==200
 editor=req(admin,'/admin/blogs/new')[1]
 assert 'name="author"' not in editor and 'name="slug"' not in editor
 data=dict(title='QA kitchen story',slug=slug,category='QA Stories',excerpt='Local publishing check',body='## A kitchen heading\n\nA paragraph. <script>alert(1)</script>',meta_title='QA "><script>alert(2)</script>',meta_description='QA "><script>alert(3)</script>')
 _,html,url=req(admin,'/admin/blogs',data);assert '/edit' in url,url
 ident=db.execute('select id from blogs where slug=?',(slug,)).fetchone()[0]
 assert db.execute('select author from blogs where id=?',(ident,)).fetchone()[0]=='Minitz Kitchen'
 assert req(public,'/blogs/'+slug)[0]==404
 assert slug not in req(public,'/blogs')[1]
 assert req(public,'/admin/blogs/'+str(ident)+'/archive',{})[2].endswith('/admin/login')
 assert req(admin,'/admin/blogs/'+str(ident),data,False)[0]==419
 publish=dict(data,active='1');publish.pop('slug')
 assert req(admin,'/admin/blogs/'+str(ident),publish)[0]==200
 assert db.execute('select slug from blogs where id=?',(ident,)).fetchone()[0]==slug
 status,page,_=req(public,'/blogs/'+slug);assert status==200
 assert '<h2>A kitchen heading</h2>' in page
 assert '&lt;script&gt;alert(1)&lt;/script&gt;' in page and '<script>alert(' not in page
 assert slug in req(public,'/blogs?category=QA+Stories')[1]
 assert slug in req(public,'/blogs?q=QA+kitchen')[1]
 assert slug in req(public,'/')[1]
 assert req(admin,'/admin/blogs/'+str(ident),dict(data,slug='bad slug',active='1'))[0]==200
 assert db.execute('select slug from blogs where id=?',(ident,)).fetchone()[0]==slug
 assert req(admin,'/admin/blogs/'+str(ident)+'/archive',{})[0]==200
 assert req(public,'/blogs/'+slug)[0]==404
 print('PASS admin protection, CSRF, draft/publish/unpublish, search/category, homepage stories, slug validation and escaped article/metadata')
finally:
 db.execute('delete from blogs where slug=?',(slug,));db.commit();db.close()
