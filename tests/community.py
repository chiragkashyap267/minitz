"""Local community upload persistence, visibility and authorization checks."""
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


photo=None
try:
 raw=(ROOT/'assets/artwork-01.png').read_bytes()
 assert req('/admin/community')[0]==200
 req('/community/photos',{'title':tag},{'image':raw})
 assert db.execute('select count(*) from community_photos where title=?',(tag,)).fetchone()[0]==0
 req('/account/login',{'email':'cook@minitz.test','password':'Minitz-Cook-2026!'})
 assert 'Upload your photo' in req('/')[1]
 req('/community/photos',{'title':tag},{'image':raw})
 photo=db.execute('select * from community_photos where title=?',(tag,)).fetchone();assert photo
 path=ROOT/'application/storage/app/public/uploads'/Path(photo['image']).name
 assert path.is_file()
 assert tag in req('/')[1] and tag in req('/community')[1]
 assert req('/'+photo['image'])[0]==200
 # Signed-in customers cannot change visibility through admin endpoints.
 req('/admin/community/'+str(photo['id']),{'active':'0'})
 assert db.execute('select active from community_photos where id=?',(photo['id'],)).fetchone()[0]==1
 req('/community/photos',{'title':tag+'bad'},{'image':b'not an image'})
 assert db.execute('select count(*) from community_photos where title=?',(tag+'bad',)).fetchone()[0]==0
 req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
 assert tag in req('/admin/community')[1]
 req('/admin/community/'+str(photo['id']),{'active':'0'})
 assert tag not in req('/')[1] and tag not in req('/community')[1]
 req('/admin/community/'+str(photo['id']),{'active':'1'})
 assert tag in req('/community')[1]
 print('PASS signed-in upload, database and file persistence, immediate homepage/gallery display, invalid file rejection and admin-only hide/show')
finally:
 if photo:
  db.execute('delete from community_photos where id=?',(photo['id'],));db.commit()
  path=ROOT/'application/storage/app/public/uploads'/Path(photo['image']).name
  if path.is_file():path.unlink()
 db.close()
