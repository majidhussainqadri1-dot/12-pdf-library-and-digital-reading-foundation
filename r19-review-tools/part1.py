from pathlib import Path
import subprocess


def read(p): return Path(p).read_text()
def write(p,s): Path(p).write_text(s)
def replace_once(p,old,new):
    s=read(p); n=s.count(old)
    if n!=1: raise SystemExit(f'expected one match in {p}; found {n}: {old[:120]!r}')
    write(p,s.replace(old,new,1))
def lint_commit(n,msg,files):
    for f in files:
        if f.endswith('.php'): subprocess.run(['php','-l',f],check=True)
        if f.endswith('.js'): subprocess.run(['node','--check',f],check=True)
    subprocess.run(['git','add',*files],check=True)
    subprocess.run(['git','commit','-m',f'R19 round {n:02d}: {msg}'],check=True)

rights='pdf-library-foundation-12/includes/class-pldr-rights.php'
access='pdf-library-foundation-12/includes/class-pldr-access.php'
reader='pdf-library-foundation-12/includes/class-pldr-reader.php'

# Round 1 — DB failure while resolving a rights-report document must not become an ordinary 404.
replace_once(rights,
"        $doc = PLDR_Core::document($document_id);\n        if (!$doc) return PLDR_Core::machine_error('pldr_document_missing', 'Document not found.', 404);",
"        $wpdb->last_error='';$doc = PLDR_Core::document($document_id);\n        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_case_document_read','Rights-report document state could not be read reliably; the report was not filed.',503,array('degraded'=>true));\n        if (!$doc) return PLDR_Core::machine_error('pldr_document_missing', 'Document not found.', 404);")
lint_commit(1,'make rights report source DB failures fail visible',[rights])

# Round 2 — malformed embargo metadata must fail closed rather than silently acting as no embargo.
replace_once(access,
"        if (!empty($policy['embargo_until']) && strtotime((string) $policy['embargo_until']) > time() && !PLDR_Core::authorize('manage', (int) $edition['document_id'], $user_id)) return false;",
"        if(!empty($policy['embargo_until'])){\n            $embargo_raw=(string)$policy['embargo_until'];$embargo_ts=strtotime($embargo_raw);\n            if(false===$embargo_ts){PLDR_Core::audit('edition',$edition_id,'access_embargo_invalid',array('document_id'=>(int)$edition['document_id']),$user_id);if(!PLDR_Core::authorize('manage',(int)$edition['document_id'],$user_id)&&!PLDR_Core::authorize('rights',(int)$edition['document_id'],$user_id))return false;}\n            elseif($embargo_ts>time()&&!PLDR_Core::authorize('manage',(int)$edition['document_id'],$user_id))return false;\n        }")
lint_commit(2,'fail closed on malformed embargo metadata',[access])

# Round 3 — token issuance must distinguish authorization-state DB failure from a real access denial.
replace_once(access,
"        $user_id = $user_id ?: get_current_user_id();\n        if (!self::can_access_edition($edition_id, $operation, $user_id)) {\n            return PLDR_Core::machine_error('pldr_access_denied', 'The requested document is unavailable for this operation.', 403);\n        }",
"        $user_id = $user_id ?: get_current_user_id();\n        $wpdb->last_error='';$allowed=self::can_access_edition($edition_id,$operation,$user_id);\n        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_token_access_read','Delivery authorization state could not be verified reliably; no grant was issued.',503,array('degraded'=>true));\n        if (!$allowed) {\n            return PLDR_Core::machine_error('pldr_access_denied', 'The requested document is unavailable for this operation.', 403);\n        }")
lint_commit(3,'make delivery grant authorization DB failures explicit',[access])

# Round 4 — a grant issued to the anonymous/public audience must remain anonymous during delivery revalidation.
replace_once(access,
"    public static function can_access_edition(int $edition_id, string $operation = 'read', int $user_id = 0): bool {\n        $edition = PLDR_Core::edition($edition_id);\n        if (!$edition) return false;\n        $user_id = $user_id ?: get_current_user_id();",
"    public static function can_access_edition(int $edition_id, string $operation = 'read', int $user_id = 0): bool {\n        $edition = PLDR_Core::edition($edition_id);\n        if (!$edition) return false;\n        $user_id = $user_id < 0 ? 0 : ($user_id ?: get_current_user_id());")
replace_once(access,
"        $still_allowed=self::can_access_edition((int)$row['edition_id'],(string)$row['operation'],(int)$row['user_id']);",
"        $grant_user=(int)$row['user_id']>0?(int)$row['user_id']:-1;\n        $still_allowed=self::can_access_edition((int)$row['edition_id'],(string)$row['operation'],$grant_user);")
lint_commit(4,'preserve anonymous audience during token revalidation',[access])

# Round 5 — OCR search authorization DB failure must not collapse into an ordinary unavailable/404 result.
replace_once(reader,
"        if (!PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return array('error'=>PLDR_Core::machine_error('pldr_ocr_forbidden','Document text search is unavailable.',404));",
"        $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition($edition_id,'read',$user_id);\n        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_ocr_access_read','Document text-search authorization state could not be verified reliably.',503,array('degraded'=>true)));\n        if(!$allowed)return array('error'=>PLDR_Core::machine_error('pldr_ocr_forbidden','Document text search is unavailable.',404));")
lint_commit(5,'make OCR search authorization failures fail visible',[reader])
