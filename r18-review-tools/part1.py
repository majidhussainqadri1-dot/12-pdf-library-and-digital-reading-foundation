from pathlib import Path
import subprocess


def path(p): return Path(p)
def read(p): return path(p).read_text()
def write(p,s): path(p).write_text(s)
def replace_once(p, old, new):
    s=read(p)
    if s.count(old)!=1:
        raise SystemExit(f"expected exactly one match in {p}: {old[:120]!r}; found {s.count(old)}")
    write(p,s.replace(old,new,1))
def lint_commit(round_no, message, files):
    for f in files:
        if f.endswith('.php'):
            subprocess.run(['php','-l',f],check=True)
    subprocess.run(['git','add',*files],check=True)
    subprocess.run(['git','commit','-m',f'R18 round {round_no:02d}: {message}'],check=True)

rights='pdf-library-foundation-12/includes/class-pldr-rights.php'
access='pdf-library-foundation-12/includes/class-pldr-access.php'
rest='pdf-library-foundation-12/includes/class-pldr-rest.php'

# Round 1 — a rights decision is a state transition; expected_version must be mandatory.
replace_once(
    rights,
    "        if($expected_version && (int)$case['version']!==$expected_version)return PLDR_Core::machine_error('pldr_case_conflict','Rights case changed; refresh before deciding.',409,array('current_version'=>(int)$case['version']));",
    "        if($expected_version<1)return PLDR_Core::machine_error('pldr_case_precondition','Rights-case decisions require the exact expected case version.',428,array('current_version'=>(int)$case['version']));\n        if((int)$case['version']!==$expected_version)return PLDR_Core::machine_error('pldr_case_conflict','Rights case changed; refresh before deciding.',409,array('current_version'=>(int)$case['version']));"
)
lint_commit(1,'require rights decision optimistic precondition',[rights])

# Round 2 — publication approval also needs an explicit client precondition and fail-visible source reads.
replace_once(
    rights,
    "        $doc = PLDR_Core::document($document_id); if(!$doc) return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);\n        if ($expected_version && (int)$doc['version'] !== $expected_version) return PLDR_Core::machine_error('pldr_document_conflict','Document changed; refresh before approval.',409,array('current_version'=>(int)$doc['version']));",
    "        $wpdb->last_error='';$doc = PLDR_Core::document($document_id);\n        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_document_read','Document state could not be read reliably before publication approval.',503,array('degraded'=>true));\n        if(!$doc) return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);\n        if ($expected_version<1) return PLDR_Core::machine_error('pldr_approve_precondition','Document publication approval requires the exact expected document version.',428,array('current_version'=>(int)$doc['version']));\n        if ((int)$doc['version'] !== $expected_version) return PLDR_Core::machine_error('pldr_document_conflict','Document changed; refresh before approval.',409,array('current_version'=>(int)$doc['version']));"
)
replace_once(
    rights,
    "        $edition = PLDR_Core::latest_edition($document_id); $object = $edition ? PLDR_Core::object((int)$edition['object_id']) : null;\n        if(!$edition || !$object || 'available'!==$object['object_status'] || 'clean'!==$object['scan_status']) return PLDR_Core::machine_error('pldr_approve_scan','A clean available encrypted object is required before publication.',409);",
    "        $wpdb->last_error='';$edition = PLDR_Core::latest_edition($document_id);\n        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_edition_read','Latest edition state could not be read reliably before publication approval.',503,array('degraded'=>true));\n        $object=null;if($edition){$wpdb->last_error='';$object=PLDR_Core::object((int)$edition['object_id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_object_read','Encrypted object state could not be read reliably before publication approval.',503,array('degraded'=>true));}\n        if(!$edition || !$object || 'available'!==$object['object_status'] || 'clean'!==$object['scan_status']) return PLDR_Core::machine_error('pldr_approve_scan','A clean available encrypted object is required before publication.',409);"
)
lint_commit(2,'require publication approval precondition and reliable reads',[rights])

# Round 3 — request-time authorization must enforce edition rights expiry even if cron is delayed.
replace_once(
    access,
    "        if (!$policy) return false;\n        if (!empty($policy['embargo_until']) && strtotime((string) $policy['embargo_until']) > time() && !PLDR_Core::authorize('manage', (int) $edition['document_id'], $user_id)) return false;",
    "        if (!$policy) return false;\n        if(!empty($edition['rights_expires_at'])){\n            $rights_raw=(string)$edition['rights_expires_at'];$rights_ts=strtotime($rights_raw);\n            $curator=PLDR_Core::authorize('manage',(int)$edition['document_id'],$user_id)||PLDR_Core::authorize('rights',(int)$edition['document_id'],$user_id);\n            if(false===$rights_ts){PLDR_Core::audit('edition',$edition_id,'rights_expiry_invalid',array('document_id'=>(int)$edition['document_id']),$user_id);if(!$curator)return false;}\n            elseif($rights_ts<=time()&&!$curator)return false;\n        }\n        if (!empty($policy['embargo_until']) && strtotime((string) $policy['embargo_until']) > time() && !PLDR_Core::authorize('manage', (int) $edition['document_id'], $user_id)) return false;"
)
lint_commit(3,'enforce rights expiry at request time',[access])

# Round 4 — reader-access POST creates durable grants and therefore must use idempotency.
replace_once(
    rest,
    "        return rest_ensure_response(PLDR_Access::issue_token($edition_id,$object_id,$operation,get_current_user_id(),absint($request['ttl']?:600)));",
    "        return self::idempotent($request,'reader-access',static fn()=>PLDR_Access::issue_token($edition_id,$object_id,$operation,get_current_user_id(),absint($request['ttl']?:600)));"
)
lint_commit(4,'make reader access grant issuance idempotent',[rest])

# Round 5 — download-session POST also creates a durable grant and must be idempotent.
s=read(rest)
old="    public static function download_session(WP_REST_Request $request) { global $wpdb;$edition=PLDR_Core::edition(absint($request['edition_id']));if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_download_edition_read','Download edition state could not be read reliably.',503,array('degraded'=>true));if(!$edition)return PLDR_Core::machine_error('pldr_edition_missing','Edition not found.',404);$grant=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'download',get_current_user_id(),900);if(is_wp_error($grant))return $grant;PLDR_Core::audit('edition',(int)$edition['id'],'download_session_issued',array('size'=>$grant['size'],'sha256'=>$grant['sha256']));return rest_ensure_response(array('job_id'=>PLDR_Core::uuid(),'delivery'=>$grant,'range_bytes'=>2*MB_IN_BYTES,'checksum'=>'sha256:'.$grant['sha256'],'resume_supported'=>true,'revocation_rechecked'=>true)); }"
new="    public static function download_session(WP_REST_Request $request) {\n        return self::idempotent($request,'download-session',static function() use($request){\n            global $wpdb;$wpdb->last_error='';$edition=PLDR_Core::edition(absint($request['edition_id']));\n            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_download_edition_read','Download edition state could not be read reliably.',503,array('degraded'=>true));\n            if(!$edition)return PLDR_Core::machine_error('pldr_edition_missing','Edition not found.',404);\n            $grant=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'download',get_current_user_id(),900);if(is_wp_error($grant))return $grant;\n            PLDR_Core::audit('edition',(int)$edition['id'],'download_session_issued',array('size'=>$grant['size'],'sha256'=>$grant['sha256']));\n            return array('job_id'=>PLDR_Core::uuid(),'delivery'=>$grant,'range_bytes'=>2*MB_IN_BYTES,'checksum'=>'sha256:'.$grant['sha256'],'resume_supported'=>true,'revocation_rechecked'=>true);\n        });\n    }"
if s.count(old)!=1: raise SystemExit(f'round5 download_session pattern count={s.count(old)}')
write(rest,s.replace(old,new,1))
lint_commit(5,'make download session grant issuance idempotent',[rest])
