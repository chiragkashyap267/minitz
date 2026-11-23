"""Create a portable Hostinger ZIP without environment files or runtime data."""
from pathlib import Path
import zipfile

root=Path(__file__).resolve().parents[1]
out=root.parent/'outputs'/'Minitz_Hostinger_Preview.zip'
out.parent.mkdir(parents=True,exist_ok=True)
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=6) as z:
    for file in root.rglob('*'):
        if not file.is_file():continue
        rel=file.relative_to(root)
        path=rel.as_posix()
        if rel.parts[0] in ['.git','.runtime','dist','tests','tools']:continue
        if path.startswith(('application/storage/','application/bootstrap/cache/')):continue
        if path in ['application/.env','application/database/database.sqlite','application/local-setup.php','router.php']:continue
        if file.name.endswith(('.log','.tmp','.sqlite','.sqlite-shm','.sqlite-wal')):continue
        z.write(file,path)
    for directory in ['application/bootstrap/cache/','application/storage/app/private/','application/storage/app/public/uploads/','application/storage/framework/cache/data/','application/storage/framework/sessions/','application/storage/framework/views/','application/storage/logs/']:
        z.writestr(directory,'')
with zipfile.ZipFile(out) as z:
    names=z.namelist()
    assert 'index.php' in names and 'index.html' in names
    assert 'application/vendor/autoload.php' in names
    assert not any(p.endswith('/.env') or '/storage/installed' in p or p.endswith('.sqlite') for p in names)
print(str(out))
print('ZIP bytes:',out.stat().st_size,'Files:',len(names),'No credentials, local database or runtime sessions included.')
