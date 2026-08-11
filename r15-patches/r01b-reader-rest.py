from pathlib import Path

source = Path('r15-patches/r01-reader-rest.py').read_text()
source = source.replace('$config=array', '$config = array')
exec(compile(source, 'r15-patches/r01-reader-rest.py', 'exec'), {'__name__':'__main__'})
