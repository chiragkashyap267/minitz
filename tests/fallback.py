"""Check the public holding page while preserving the full preview sources."""
from pathlib import Path
import shutil
import subprocess
import json
from html.parser import HTMLParser

root=Path(__file__).resolve().parents[1]
fixture=root.parent/'.local-tests'/'minitz-fallback'
(fixture/'assets'/'preview').mkdir(parents=True,exist_ok=True)
shutil.copyfile(root/'index.php',fixture/'index.php')
shutil.copyfile(root/'coming-soon.html',fixture/'coming-soon.html')
for file in (root/'assets'/'preview').glob('*'):
    shutil.copyfile(file,fixture/'assets'/'preview'/file.name)
manifest=json.loads((root/'assets'/'preview'/'manifest.json').read_text())
class Links(HTMLParser):
    def handle_starttag(self,tag,attrs):
        data=dict(attrs)
        if tag in ['script','img','link']:
            path=data.get('src',data.get('href','')).split('?')[0]
            if path.startswith('/assets/'):
                assert (root/path.lstrip('/')).is_file(),path
        if tag=='a':
            path=data.get('href','').split('?')[0]
            if path.startswith('/') and '#' not in path:assert path in manifest,path
for path,filename in manifest.items():
    html=(root/'assets'/'preview'/filename).read_text(encoding='utf-8')
    assert 'csrf-token' not in html and 'name="_token"' not in html
    assert 'preview@minitz.test' not in html
    Links().feed(html)
    code='$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["REQUEST_URI"]='+json.dumps(path)+'; require "index.php";'
    result=subprocess.run(['C:/xampp/php/php.exe','-r',code],cwd=fixture,capture_output=True,check=True)
    assert b'Coming soon.' in result.stdout,path
    assert b'Add to bag' not in result.stdout,path
print('PASS',len(manifest),'public routes show the holding page; full preview sources and links remain intact')
