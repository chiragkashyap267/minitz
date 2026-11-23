"""Integration checks against the local sample store; never targets production."""
import http.cookiejar
import re
import urllib.parse
import urllib.request
import urllib.error

BASE = 'http://127.0.0.1:8087'

def client():
    return urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(opener, path, data=None, token=True):
    if data is not None:
        if token:
            _, page, _ = request(opener, '/admin/login' if path.startswith('/admin') else '/')
            data = dict(data, _token=re.search(r'name="csrf-token" content="([^"]+)"', page).group(1))
        data = urllib.parse.urlencode(data).encode()
    try:
        r = opener.open(BASE+path, data=data, timeout=25)
        raw=r.read()
        return r.status, raw.decode('utf-8', errors='replace'), r.geturl()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8', errors='replace'), e.geturl()

store=client()
for path in ['/', '/shop','/shop/everyday-garam-masala','/recipes','/recipes/sunday-biryani','/pages/about','/pages/contact','/pages/privacy','/pages/terms','/pages/payment','/pages/shipping','/pages/returns','/cart','/admin/login']:
    status,html,_=request(store,path)
    assert status==200,(path,status)
    assert 'Internal Server Error' not in html,path
print('PASS public storefront, product, recipe and policy pages')
_,_,url=request(store,'/admin')
assert url.endswith('/admin/login')
_,_,url=request(store,'/admin/reports/customers')
assert url.endswith('/admin/login')
assert request(store,'/application/.env')[0]==404
assert request(store,'/.git/config')[0]==404
assert request(store,'/cart/add',{'id':1,'quantity':1},token=False)[0]==419
assert request(store,'/payments/webhook',{'event':'payment.captured'},token=False)[0]==403
print('PASS admin protection, private paths, CSRF and webhook rejection')
status,html,_=request(store,'/cart/add',{'id':1,'quantity':2,'price':1})
assert status==200
_,html,_=request(store,'/cart')
assert '₹750' in html
status,html,url=request(store,'/cart/add',{'id':1,'quantity':100})
_,html,_=request(store,'/cart')
assert '₹750' in html
_,html,_=request(store,'/checkout')
checkout=re.search(r'name="checkout_token" value="([^"]+)"',html).group(1)
payload={'name':'Preview Customer','email':'preview-customer@example.test','phone':'9990000000','line1':'Sample address for preview only','city':'Sample City','state':'Sample State','pincode':'110001','accept':1,'checkout_token':checkout,'total':1}
status,html,url=request(store,'/checkout',payload)
assert status==200 and '/orders/' in url,(status,url)
assert 'No payment has been collected' in html and '₹750' in html
assert request(client(),url.removeprefix(BASE))[0]==404
assert request(store,'/checkout',payload)[0]==419
print('PASS server-priced cart, quantity validation, preview checkout, private order and replay protection')
admin=client()
status,html,url=request(admin,'/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
assert status==200 and url.endswith('/admin'),(status,url)
for path in ['/admin','/admin/products','/admin/recipes','/admin/content','/admin/orders','/admin/customers','/admin/reports']:
    assert request(admin,path)[0]==200,path
for path in ['/admin/reports/order/1','/admin/reports/customers','/admin/reports/sales']:
    status,body,_=request(admin,path)
    assert status==200 and body.startswith('%PDF-'),(path,status,body[:100])
print('PASS admin login, all dashboard screens and private PDF generation')
product={'name':'QA Test Product','sku':'MZ-QA-ONLY','category':'Test','price':'199.50','weight':'50 g','description':'Temporary sample used for integration checks.','ingredients':'Placeholder','allergens':'Placeholder','colour':'green','stock':10,'active':1}
status,html,url=request(admin,'/admin/products',product)
assert status==200 and 'Product saved.' in html and 'QA Test Product' in html
status,html,_=request(store,'/shop?q=QA+Test+Product')
assert 'QA Test Product' in html and '₹199.50' in html
assert request(store,'/admin/products',product)[2].endswith('/admin/login')
recipe={'title':'QA Test Recipe','category':'Test','minutes':20,'servings':2,'description':'Temporary recipe used for checks.','ingredients':'Sample ingredient','steps':'Sample step','active':1}
status,html,_=request(admin,'/admin/recipes',recipe)
assert status==200 and 'Recipe saved.' in html
assert 'QA Test Recipe' in request(store,'/recipes')[1]
print('PASS product creation with decimal pricing, recipe creation and unauthorised-write protection')
status,html,_=request(admin,'/admin/orders/1',{'status':'shipped','tracking':'TEST'})
assert 'Only verified paid orders' in html
assert request(admin,'/admin/reports/sales?from=2026-11-23&to=2026-11-01')[0] in (200,422)
request(admin,'/admin/logout',{})
assert request(admin,'/admin')[2].endswith('/admin/login')
print('PASS preview order cannot enter fulfilment and admin logout')
