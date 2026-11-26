"""Run local suites sequentially, keeping login rate-limit state isolated per suite."""
import subprocess,sys,time,json,tempfile,sqlite3
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];PHP=r'C:\xampp\php\php.exe'
out=Path(tempfile.mkdtemp(prefix='minitz-test-results-'));results=[]
for name in ['smoke','admin_editing','homepage_experience','catalogue_editor','pricing','blogs','broadcast','product_experience','community','contact','commerce','fallback','youtube','payment']:
 subprocess.run([PHP,'application/artisan','cache:clear'],cwd=ROOT,capture_output=True,check=True)
 command=[PHP if name in ['youtube','payment'] else sys.executable,str(ROOT/'tests'/(name+('.php' if name in ['youtube','payment'] else '.py')))]
 started=time.monotonic();r=subprocess.run(command,cwd=ROOT,capture_output=True,text=True,encoding='utf-8',errors='replace')
 (out/(name+'.log')).write_text(r.stdout+r.stderr,encoding='utf-8')
 results.append(dict(suite=name,passed=r.returncode==0,seconds=round(time.monotonic()-started,2)))
 print(('PASS ' if r.returncode==0 else 'FAIL ')+name,flush=True)
 if r.returncode:print(r.stderr[-2000:],flush=True)
(out/'results.json').write_text(json.dumps(results,indent=2));print('Results:',out,flush=True)
sys.exit(any(not row['passed'] for row in results))
