"""Build public read-only preview pages from the local seeded Laravel app."""
from pathlib import Path
from html import unescape
import json
import re
import urllib.request

root=Path(__file__).resolve().parents[1]
out=root/'assets'/'preview'
out.mkdir(parents=True,exist_ok=True)
opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
paths=['/','/shop','/recipes','/cart','/admin/login']+['/pages/'+p for p in ['about','contact','privacy','terms','shipping','returns','payment']]
manifest={}
while paths:
    path=paths.pop(0)
    if path in manifest:continue
    html=opener.open('http://127.0.0.1:8087'+path,timeout=20).read().decode()
    for link in re.findall(r'href="([^"]+)"',html):
        if re.fullmatch(r'/(shop|recipes)/[a-z0-9-]+',link) and link not in manifest and link not in paths:paths.append(link)
    html=re.sub(r'<meta name="csrf-token"[^>]*>','',html)
    html=re.sub(r'<input type="hidden" name="_token"[^>]*>','',html)
    html=html.replace('<body class="','<body data-static-preview="true" class="',1)
    def readonly(match):
        form=match.group(0)
        form=re.sub(r'<button\b(?![^>]*disabled)', '<button disabled title="Ordering opens soon"', form)
        form=re.sub(r'>Add to bag \+</button>', '>Coming soon</button>', form)
        return form
    html=re.sub(r'<form\b[^>]*method="post".*?</form>',readonly,html,flags=re.S)
    html=html.replace('<main id="main">','<div class="static-preview-notice">Store preview · ordering opens soon</div><main id="main">',1)
    filename=('home' if path=='/' else path.strip('/').replace('/','-'))+'.html'
    (out/filename).write_text(html,encoding='utf-8',newline='\n')
    manifest[path]=filename
manifest['/admin']=manifest['/admin/login']
(out/'manifest.json').write_text(json.dumps(manifest,indent=2),encoding='utf-8',newline='\n')
(root/'index.html').write_text((out/'home.html').read_text(encoding='utf-8'),encoding='utf-8',newline='\n')
print('Built',len(manifest),'read-only preview routes; no credentials or customer records included.')
