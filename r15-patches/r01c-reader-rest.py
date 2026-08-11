from pathlib import Path

source = Path('r15-patches/r01-reader-rest.py').read_text()
source = source.replace('$config=array', '$config = array')
old = "$state = PLDR_Reading::state((int)$edition['id']);\\n        $config = array"
new = "$state = PLDR_Reading::state((int)$edition['id']);\\n        $thumbs = self::thumbnail_tokens((int)$edition['id']);\\n        $config = array"
source = source.replace(old, new)
old2 = "PLDR_Core::audit('edition',(int)$edition['id'],'reader_interaction_provider_failed',array('provider_failure'=>true));}\\n        $config = array"
new2 = "PLDR_Core::audit('edition',(int)$edition['id'],'reader_interaction_provider_failed',array('provider_failure'=>true));}\\n        $thumbs = self::thumbnail_tokens((int)$edition['id']);\\n        $config = array"
source = source.replace(old2, new2)
exec(compile(source, 'r15-patches/r01-reader-rest.py', 'exec'), {'__name__':'__main__'})
