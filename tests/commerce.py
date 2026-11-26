"""Local-only integration checks for customer ownership, drawer totals and coupon limits."""
from pathlib import Path
import datetime as dt
import http.cookiejar
import json
import re
import sqlite3
import urllib.request
import urllib.error
import urllib.parse
import uuid

BASE='http://127.0.0.1:8088'
tag=uuid.uuid4().hex[:7].upper()
codes=[]; emails=[]

def client():
    return urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(c,path):
    try:
        r=c.open(BASE+path,timeout=20); body=r.read().decode();return r.status,body,r.geturl()
    except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.geturl()

def post(c,path,data,ajax=False):
    _,page,_=get(c,'/')
    token=re.search(r'name="csrf-token" content="([^"]+)"',page).group(1)
    data=dict(data,_token=token)
    req=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode(),headers={'Accept':'application/json' if ajax else 'text/html','Content-Type':'application/x-www-form-urlencoded'})
    try:
        r=c.open(req,timeout=20); body=r.read().decode();return r.status,json.loads(body) if r.headers.get_content_type()=='application/json' else body,r.geturl()
    except urllib.error.HTTPError as e:
        body=e.read().decode();return e.code,json.loads(body) if e.headers.get_content_type()=='application/json' else body,e.geturl()

def register(c,name):
    email=f'qa-{tag.lower()}-{name.lower()}@example.test';emails.append(email)
    result=post(c,'/account/register',{'name':name,'email':email,'password':'Local-QA-Cook-2026!','password_confirmation':'Local-QA-Cook-2026!'})
    assert result[0]==200 and 'Hello, '+name in result[1],result
    return email

admin=client(); cook=client(); other=client()
try:
    result=post(admin,'/admin/login',{'email':'preview@minitz.test','password':'Minitz-Preview-Only-2026!'})
    assert result[0]==200 and result[2].endswith('/admin')
    assert get(admin,'/admin/coupons')[0]==200
    assert get(admin,'/admin/videos')[0]==200
    print('PASS coupon and video admin screens')
    email=register(cook,'KitchenQA');register(other,'OtherQA')
    post(cook,'/cart/add',{'id':1,'quantity':2},True)
    def coupon(suffix,**fields):
        code='QA-'+tag+'-'+suffix;codes.append(code)
        payload={'code':code,'type':'percentage','value':'12.5','minimum':'100','maximum_discount':'50','usage_limit':2,'per_customer_limit':1,'starts_at':'','ends_at':'','active':1}|fields
        status,body,_=post(admin,'/admin/coupons',payload,True)
        assert status==200 and 'Coupon saved.' in body,(status,body)
        return code
    percent=coupon('PERCENT')
    status,data,_=post(cook,'/cart/coupon',{'code':percent.lower()},True)
    assert status==200 and '−₹50' in data['html'] and '₹700' in data['html']
    _,page,_=get(cook,'/checkout'); nonce=re.search(r'name="checkout_token" value="([^"]+)"',page).group(1)
    status,page,url=post(cook,'/checkout',{'name':'KitchenQA','email':'tampered@example.test','phone':'9990000000','line1':'Sample address','city':'Delhi','state':'Delhi','pincode':'110001','accept':1,'checkout_token':nonce,'discount':999999,'total':1})
    assert status==200 and '/orders/' in url and '₹700' in page and 'No payment' in page
    order_path=url.removeprefix(BASE)
    assert get(other,order_path)[0]==404
    assert '₹700' in get(cook,'/account')[1]
    post(cook,'/account/logout',{})
    assert get(cook,order_path)[0]==404
    post(cook,'/account/login',{'email':email,'password':'Local-QA-Cook-2026!'})
    assert get(cook,order_path)[0]==200
    post(cook,'/cart/add',{'id':1,'quantity':2},True)
    assert post(cook,'/cart/coupon',{'code':percent},True)[0]==422
    print('PASS percentage cap, server-priced checkout, per-customer limit and order ownership across login/logout')
    flat=coupon('FLAT',type='flat',value='50',minimum='800',maximum_discount='',usage_limit='',per_customer_limit='')
    assert post(cook,'/cart/coupon',{'code':flat},True)[0]==422
    post(cook,'/cart/add',{'id':1,'quantity':1},True)
    status,data,_=post(cook,'/cart/coupon',{'code':flat},True)
    assert status==200 and '₹1,075' in data['html']
    assert post(cook,'/cart/coupon/remove',{},True)[0]==200
    assert '₹1,125' in json.loads(get(cook,'/cart/drawer')[1])['html']
    yesterday=(dt.datetime.now()-dt.timedelta(days=1)).strftime('%Y-%m-%dT%H:%M')
    tomorrow=(dt.datetime.now()+dt.timedelta(days=1)).strftime('%Y-%m-%dT%H:%M')
    expired=coupon('EXPIRED',starts_at='',ends_at=yesterday)
    future=coupon('FUTURE',starts_at=tomorrow)
    paused=coupon('PAUSED',active=0)
    for code in [expired,future,paused]:assert post(cook,'/cart/coupon',{'code':code},True)[0]==422,code
    assert post(admin,'/admin/coupons',{'code':'QA-'+tag+'-INVALID','type':'percentage','value':101,'minimum':0,'active':1},True)[0]==422
    assert post(other,'/admin/coupons',{'code':'UNAUTH'},True)[2].endswith('/admin/login')
    print('PASS flat discounts, minimum spend, removal, expired/future/paused codes and invalid percentages')
    once=coupon('ONCE',type='flat',value='10',minimum=0,maximum_discount='',usage_limit=1,per_customer_limit='')
    assert post(other,'/cart/add',{'id':1,'quantity':1},True)[0]==200
    assert post(other,'/cart/coupon',{'code':once},True)[0]==200
    _,page,_=get(other,'/checkout');nonce=re.search(r'name="checkout_token" value="([^"]+)"',page).group(1)
    result=post(other,'/checkout',{'name':'OtherQA','email':emails[-1],'phone':'9990000000','line1':'Sample address','city':'Delhi','state':'Delhi','pincode':'110001','accept':1,'checkout_token':nonce})
    assert result[0]==200 and '₹365' in result[1]
    assert post(cook,'/cart/coupon',{'code':once},True)[0]==422
    print('PASS total coupon usage limit is enforced across different customers')
    status,receipt,_=get(cook,'/recipes/sunday-biryani/receipt')
    assert status==200 and 'recipe-printer' in json.loads(receipt)['html']
    assert get(cook,'/recipes/not-a-real-recipe/receipt')[0]==404
    print('PASS inline recipe receipt and missing-recipe handling')
finally:
    database=Path(__file__).resolve().parents[1]/'application/database/database.sqlite'
    with sqlite3.connect(database) as db:
        for email in emails:
            db.execute('DELETE FROM coupon_redemptions WHERE order_id IN (SELECT id FROM orders WHERE email=?)',(email,))
            db.execute('DELETE FROM orders WHERE email=?',(email,))
            db.execute('DELETE FROM customers WHERE email=?',(email,))
        for code in codes:db.execute('DELETE FROM coupons WHERE code=?',(code,))
    print('Temporary QA customers, orders and coupons removed.')
