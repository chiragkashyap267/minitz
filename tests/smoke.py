"""Read-only storefront smoke tests and public security boundaries, local only."""
import http.cookiejar,re,urllib.request,urllib.error
BASE='http://127.0.0.1:8088'
c=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def get(path):
 try:
  r=c.open(BASE+path,timeout=25);return r.status,r.read().decode(errors='replace'),r.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(errors='replace'),e.url
for path in ['/','/shop','/shop/everyday-garam-masala','/recipes','/recipes/sunday-biryani','/blogs','/community','/pages/about','/pages/contact','/pages/privacy','/pages/terms','/pages/payment','/pages/shipping','/pages/returns','/cart','/account','/admin/login']:
 status,page,url=get(path);assert status==200,(path,status)
 assert 'Internal Server Error' not in page
for path in ['/admin','/admin/products','/admin/content','/admin/community','/admin/messages','/admin/tv','/admin/reviews','/admin/reports/customers']:
 assert get(path)[2].endswith('/admin/login'),path
for path in ['/application/.env','/.git/config','/application/database/database.sqlite']:
 assert get(path)[0]==404,path
try:
 c.open(BASE+'/cart/add',b'id=1&quantity=1')
 raise AssertionError('Missing CSRF accepted')
except urllib.error.HTTPError as e:assert e.code==419
print('PASS 17 public pages, eight protected admin routes, private file protection and CSRF rejection')
