from pathlib import Path
import base64
import os
import subprocess
import tempfile
import zlib

parts=[]
for i in range(8):
    parts.append(Path(f'r15-patches/chunks/{i:02d}.txt').read_text().strip())
data=''.join(parts)
patch=zlib.decompress(base64.b64decode(data))
fd,path=tempfile.mkstemp(prefix='r15-',suffix='.patch')
os.write(fd,patch)
os.close(fd)
try:
    subprocess.run(['git','apply','--check',path],check=True)
    subprocess.run(['git','apply',path],check=True)
finally:
    os.unlink(path)
