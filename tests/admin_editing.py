"""Detailed local admin editing audit; restores settings and removes its own fixtures."""
from pathlib import Path
import html,json,subprocess,traceback
import urllib.request,urllib.parse,urllib.error,http.cookiejar,uuid,re,sqlite3
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088';tag='QACAT'+uuid.uuid4().hex[:8];c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));db=sqlite3.connect(ROOT/'application/database/database.sqlite');db.row_factory=sqlite3.Row

def req(path,data=None,files=None):
 headers={'Referer':BASE+'/admin/content'}
 if data is not None:
  token=re.search(r'name="csrf-token" content="([^"]+)"',req('/admin/login')[1]).group(1);data=dict(data,_token=token)
  if files:
   boundary='----'+uuid.uuid4().hex;parts=[]
   for k,value in data.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+str(value)+'\r\n').encode())
   for key,raw in files.items():parts.extend([('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"; filename="'+('sample.mp4' if key=='video' else 'sample.png')+'"\r\nContent-Type: '+('video/mp4' if key=='video' else 'image/png')+'\r\n\r\n').encode(),raw,b'\r\n'])
   parts.append(('--'+boundary+'--\r\n').encode());data=b''.join(parts);headers['Content-Type']='multipart/form-data; boundary='+boundary
  else:data=urllib.parse.urlencode(data,doseq=True).encode()
 try:
  res=c.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=30);return res.status,res.read().decode('utf-8',errors='replace')
 except urllib.error.HTTPError as e:return e.code,e.read().decode('utf-8',errors='replace')


settings=dict(db.execute('select key,value from settings'));files=[];created={'recipes':[],'testimonials':[],'coupons':[],'orders':[]};failures=[]
def check(name,fn):
 try:fn();print('PASS',name,flush=True)
 except Exception as error:failures.append(name+': '+str(error));print('FAIL',name,traceback.format_exc(),flush=True)
def rows(table,ident):return dict(db.execute('select * from '+table+' where id=?',(ident,)).fetchone())
def content():
 config=json.loads(subprocess.check_output([r'C:\xampp\php\php.exe','-r',"echo json_encode(require 'application/config/content.php');"],text=True))
 copy={key:tag+'_'+key for fields in config.values() for key in fields}
 data=dict(copy,announcement=tag+'_announcement',hero_heading=tag+'_hero',hero_text=tag+'_intro',about_heading=tag+'_about',about_text=tag+'_story',support_email='qa@example.com',support_phone='9990000000',whatsapp='919990000000',instagram='https://instagram.com/minitzqa',facebook='https://facebook.com/minitzqa',youtube='https://www.youtube.com/@minitzqa',shipping_paise='12300',free_shipping_paise='999900')
 for key in ['privacy','terms','shipping','returns','payment']:data['policy_'+key]=tag+'_'+key
 raw=(ROOT/'assets/artwork-01.png').read_bytes()
 req('/admin/content',data,{'image':raw,'content_images[story_art]':raw,'content_images[blog_mascot]':raw})
 actual=dict(db.execute('select key,value from settings'));files.extend(ROOT/'application/storage/app/public/uploads'/Path(actual[key]).name for key in ['hero_image','story_art','blog_mascot'])
 assert all(actual.get(key)==value for key,value in data.items()),'Some settings did not persist'
 editor=req('/admin/content')[1];assert all(html.escape(value) in editor for value in copy.values()),'Some saved text missing from editor'
 page=req('/')[1]
 missing=[key for key,value in copy.items() if value not in page and not (key.startswith('home_reviews_') and 'home-review-marquee' not in page)]
 for key in ['privacy','terms','shipping','returns','payment']:assert tag+'_'+key in req('/pages/'+key)[1]
 for key in ['hero_image','story_art','blog_mascot']:assert actual[key] in page and req('/'+actual[key])[0]==200
 assert 'qa@example.com' in req('/pages/contact')[1]
 req('/admin/content',dict(data,hero_heading='SHOULD NOT SAVE',instagram='javascript:alert(1)'))
 assert dict(db.execute('select key,value from settings'))['hero_heading']==data['hero_heading']
 # Restore delivery values before checkout suites; all settings are restored in finally.
 for key in ['shipping_paise','free_shipping_paise']:db.execute('update settings set value=? where key=?',(settings[key],key))
 db.commit()
 assert not missing,'Editable fields have no homepage output: '+', '.join(missing)
def recipes():
 data=dict(title=tag+' recipe',category='QA',minutes='15',servings='2',description=tag+' intro',ingredients='Salt\nRice',steps='Mix\nCook',active='1')
 req('/admin/recipes',data,{'image':(ROOT/'assets/artwork-01.png').read_bytes()})
 row=dict(db.execute('select * from recipes where title=?',(data['title'],)).fetchone());ident=row['id'];created['recipes'].append(ident);files.append(ROOT/'application/storage/app/public/uploads'/Path(row['image']).name)
 assert data['title'] in req('/recipes')[1]
 changed=dict(data,title=tag+' edited',minutes='27',servings='4',description=tag+' changed',ingredients='Rice\nSpice',steps='Stir\nServe')
 req('/admin/recipes/'+str(ident),changed)
 saved=rows('recipes',ident);assert saved['minutes']==27 and saved['servings']==4 and saved['image']==row['image'] and saved['slug']==row['slug']
 page=req('/recipes/'+row['slug'])[1];assert changed['title'] in page and tag+' changed' in page
 assert 'Stir' in req('/recipes/'+row['slug']+'/receipt')[1]
 req('/admin/archive/recipes/'+str(ident),{})
 assert req('/recipes/'+row['slug'])[0]==404 and not rows('recipes',ident)['active']
def videos():
 data=dict(title=tag+' clip',caption=tag+' caption',active='1')
 raw=(ROOT/'assets/artwork-01.png').read_bytes();clip=(ROOT/'assets/brand/saved-mein-hai.mp4').read_bytes()
 req('/admin/videos',data,{'image':raw,'video':clip})
 row=dict(db.execute('select * from testimonials where title=?',(data['title'],)).fetchone());ident=row['id'];created['testimonials'].append(ident)
 files.extend(ROOT/'application/storage/app/public/uploads'/Path(row[key]).name for key in ['image','video'])
 assert data['title'] in req('/')[1] and row['video'] in req('/')[1]
 req('/admin/videos/'+str(ident),dict(title=tag+' updated',caption=tag+' updated caption',active='1'))
 saved=rows('testimonials',ident);assert saved['image']==row['image'] and saved['video']==row['video'] and tag+' updated caption' in req('/')[1]
 req('/admin/videos/'+str(ident),dict(title=saved['title'],caption=saved['caption']))
 assert not rows('testimonials',ident)['active'] and saved['title'] not in req('/')[1]
def coupons():
 data=dict(code=tag,type='flat',value='25.50',minimum='100',maximum_discount='',usage_limit='5',per_customer_limit='1',starts_at='',ends_at='',active='1')
 req('/admin/coupons',data)
 row=dict(db.execute('select * from coupons where code=?',(tag.upper(),)).fetchone());ident=row['id'];created['coupons'].append(ident)
 assert row['value']==2550 and row['minimum']==10000
 req('/admin/coupons/'+str(ident),dict(data,type='percentage',value='12.5',maximum_discount='40',usage_limit='10',per_customer_limit='2',starts_at='2026-01-01T10:30',ends_at='2030-01-01T10:30'))
 saved=rows('coupons',ident);assert saved['type']=='percentage' and saved['value']==1250 and saved['maximum_discount']==4000 and saved['usage_limit']==10 and saved['per_customer_limit']==2
 assert '2026-01-01T10:30' in req('/admin/coupons')[1]
 req('/admin/coupons/'+str(ident)+'/status',{'active':'0'});assert not rows('coupons',ident)['active']
def fulfilment():
 original=dict(db.execute('select * from orders limit 1').fetchone());original.pop('id');original.update(name=tag,email=tag.lower()+'@example.test',token=str(uuid.uuid4()),checkout_token=str(uuid.uuid4()),status='paid',tracking=None,payment_order_id=None,payment_id=None)
 cols=list(original);cur=db.execute('insert into orders ('+','.join(cols)+') values ('+','.join('?' for _ in cols)+')',list(original.values()));ident=cur.lastrowid;created['orders'].append(ident);db.commit()
 for state in ['processing','shipped','delivered']:
  req('/admin/orders/'+str(ident),{'status':state,'tracking':tag+'-TRACK'})
  saved=rows('orders',ident);assert saved['status']==state and saved['tracking']==tag+'-TRACK'
 req('/admin/orders/'+str(ident),{'status':'invalid','tracking':'BAD'});assert rows('orders',ident)['status']=='delivered'
 assert tag in req('/admin/orders?q='+tag)[1] and tag in req('/admin/customers')[1]
try:
 assert req('/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})[0]==200
 for section in ['','/products','/recipes','/content','/orders','/customers','/coupons','/videos','/blogs','/reviews','/tv','/community','/messages']:
  assert req('/admin'+section)[0]==200,section
 assert req('/admin/reports')[0]==404
 print('PASS all 13 admin screens and disabled PDF dashboard')
 check('All website content fields, artwork, socials, policies and invalid-input atomicity',content)
 check('Recipe create/edit/reopen, photo preservation, receipt and archive',recipes)
 check('Video and poster upload, metadata update, file preservation and hide',videos)
 check('Coupon edit, decimal conversion, dates, limits and pause',coupons)
 check('Paid-order fulfilment stages, tracking, invalid status and customer directory',fulfilment)
finally:
 for key, in db.execute('select key from settings').fetchall():
  if key not in settings:db.execute('delete from settings where key=?',(key,))
 for key,value in settings.items():db.execute('insert or replace into settings(key,value) values(?,?)',(key,value))
 for table,ids in created.items():
  for ident in ids:db.execute('delete from '+table+' where id=?',(ident,))
 db.commit();db.close()
 for path in set(files):
  if path.is_file():path.unlink()
if failures:raise AssertionError('\n'.join(failures))
