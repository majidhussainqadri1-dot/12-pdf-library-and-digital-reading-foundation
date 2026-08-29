from pathlib import Path
import subprocess,re


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

ocr='pdf-library-foundation-12/includes/class-pldr-future-ocr-lab.php'
frest='pdf-library-foundation-12/includes/class-pldr-future-rest.php'
a11y='pdf-library-foundation-12/includes/class-pldr-future-a11y.php'
access='pdf-library-foundation-12/includes/class-pldr-access.php'
admin='pdf-library-foundation-12/includes/class-pldr-admin.php'

# Round 11 — OCR correction review is a state transition and must carry the version the reviewer actually reviewed.
s=read(ocr)
pattern=r"    public static function review\(int \$correction_id, string \$decision, string \$note\) \{.*?\n    \}\n    private static function limit"
replacement="""    public static function review(int $correction_id,string $decision,string $note,int $expected_version=0) {
        global $wpdb;
        if (!in_array($decision,array('approved','rejected'),true)) return PLDR_Core::machine_error('pldr_ocr_review_input','A valid review decision is required.',400);
        $note=self::limit(sanitize_textarea_field($note),2000);
        if (''===trim($note)) return PLDR_Core::machine_error('pldr_ocr_review_input','A decision and review note are required.',400);
        $wpdb->last_error='';$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('ocr_corrections').' WHERE id=%d',$correction_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_ocr_review_read','OCR correction review state could not be read reliably.',503,array('degraded'=>true));
        if(!$row)return PLDR_Core::machine_error('pldr_ocr_correction_missing','OCR correction not found.',404);
        if($expected_version<1)return PLDR_Core::machine_error('pldr_ocr_review_precondition','OCR correction review requires the exact expected correction version.',428,array('current_version'=>(int)$row['version']));
        if((int)$row['version']!==$expected_version)return PLDR_Core::machine_error('pldr_ocr_review_conflict','OCR correction changed; refresh before reviewing.',409,array('current_version'=>(int)$row['version']));
        if('pending'!==$row['status'])return PLDR_Core::machine_error('pldr_ocr_review_final','This OCR correction already has a final reviewer decision.',409,array('status'=>$row['status']));
        $wpdb->last_error='';$edition=PLDR_Core::edition((int)$row['edition_id']);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_ocr_review_edition_read','OCR correction edition state could not be read reliably.',503,array('degraded'=>true));
        if(!$edition)return PLDR_Core::machine_error('pldr_ocr_review_edition','OCR correction edition is unavailable.',404);
        $document_id=(int)$edition['document_id'];
        if (!PLDR_Core::authorize('manage',$document_id) && !PLDR_Core::authorize('rights',$document_id)) return PLDR_Core::machine_error('pldr_ocr_review_forbidden','OCR correction review authority is required for this document.',403);
        if('approved'===$decision){
            $wpdb->last_error='';$source=(string)$wpdb->get_var($wpdb->prepare('SELECT text_content FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d AND page_number=%d',(int)$row['edition_id'],(int)$row['page_number']));
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_ocr_review_source_read','Base OCR source could not be re-read reliably; approval was not recorded.',503,array('degraded'=>true));
            if(''===$source||false===strpos($source,(string)$row['original_text']))return PLDR_Core::machine_error('pldr_ocr_review_stale','The base OCR source changed or no longer contains the submitted excerpt; re-submit the correction against current OCR.',409);
        }
        $updated=$wpdb->update(PLDR_Core::table('ocr_corrections'),array('status'=>$decision,'reviewed_by'=>get_current_user_id(),'review_note'=>$note,'version'=>$expected_version+1,'updated_at'=>PLDR_Core::now()),array('id'=>$correction_id,'version'=>$expected_version,'status'=>'pending'));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_ocr_review_conflict','OCR correction changed concurrently; refresh before reviewing.',409);
        PLDR_Core::audit('ocr_correction',$correction_id,'reviewed',array('decision'=>$decision,'document_id'=>$document_id));
        return array('id'=>$correction_id,'status'=>$decision,'version'=>$expected_version+1,'decision_final'=>true,'original_scan_immutable'=>true,'base_ocr_immutable'=>true,'derived_correction_layer'=>true);
    }
    private static function limit"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round11 OCR review matches={n}')
write(ocr,s2)
replace_once(frest,
"    public static function ocr_review(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'ocr-review',static fn()=>PLDR_Future_OCR_Lab::review(absint($r['id']),(string)($b['decision']??''),(string)($b['note']??''))); }",
"    public static function ocr_review(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'ocr-review',static fn()=>PLDR_Future_OCR_Lab::review(absint($r['id']),(string)($b['decision']??''),(string)($b['note']??''),absint($b['expected_version']??0))); }")
lint_commit(11,'bind OCR review to the client reviewed version',[ocr,frest])

# Round 12 — human accessibility verification must verify an existing stored assessment, not refresh provider evidence inside the verification action.
s=read(a11y)
pattern=r"    public static function verify\(int \$edition_id,string \$note=''\) \{.*?\n    \}\n\n    private static function dto"
replacement="""    public static function verify(int $edition_id,string $note='') {
        global $wpdb;
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $document_id=(int)$edition['document_id'];
        if(!PLDR_Core::authorize('manage',$document_id)&&!PLDR_Core::authorize('rights',$document_id))return PLDR_Core::machine_error('pldr_a11y_verify_forbidden','Accessibility verification authority is required for this document.',403);
        $note=self::limit(sanitize_textarea_field($note),2000);
        $wpdb->last_error='';$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('a11y_audits').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_a11y_verify_read','Stored accessibility evidence could not be read reliably for human verification.',503,array('degraded'=>true));
        if(!$row)return PLDR_Core::machine_error('pldr_a11y_verify_refresh_required','No stored accessibility assessment exists. Run the governed refresh first, review that result, then verify it.',428);
        if((int)$row['verified_by']>0)return PLDR_Core::machine_error('pldr_a11y_verify_final','This accessibility assessment is already human-verified. Refresh it before a new verification cycle.',409,array('verified_at'=>$row['verified_at']));
        $report=self::dto($row);if(isset($report['error'])&&is_wp_error($report['error']))return $report['error'];
        if((float)$row['score']<75)return PLDR_Core::machine_error('pldr_a11y_verify_score','Accessibility status is below the verification threshold.',409);
        $verified_at=PLDR_Core::now();
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('a11y_audits').' SET verified_by=%d,verified_at=%s,updated_at=%s WHERE edition_id=%d AND verified_by=0 AND score=%f AND status=%s AND findings_json=%s AND provider=%s AND updated_at=%s',get_current_user_id(),$verified_at,$verified_at,$edition_id,(float)$row['score'],(string)$row['status'],(string)$row['findings_json'],(string)$row['provider'],(string)$row['updated_at']));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_a11y_verify_conflict','Accessibility assessment changed before verification; refresh and review the new assessment.',409);
        PLDR_Core::audit('edition',$edition_id,'accessibility_verified',array('document_id'=>$document_id,'note_present'=>''!==trim($note),'assessment_updated_at'=>(string)$row['updated_at']));
        $report['verified']=true;$report['verified_at']=$verified_at;$report['public_badge_allowed']=true;$report['verification_used_stored_assessment']=true;return $report;
    }

    private static function dto"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round12 a11y verify matches={n}')
write(a11y,s2)
lint_commit(12,'separate accessibility refresh from human verification',[a11y])

# Round 13 — a delivery failure from an old sampled object state must not quarantine a concurrently rotated healthy object.
s=read(access)
pattern=r"    private static function quarantine_delivery_failure\(array \$grant,array \$object,string \$error\): void \{.*?\n    \}\n\n    private static function parse_range"
replacement="""    private static function quarantine_delivery_failure(array $grant,array $object,string $error): void {
        global $wpdb;
        $object_id=(int)($object['id']??0);$edition_id=(int)($grant['edition_id']??0);if($object_id<1||$edition_id<1)return;
        $wpdb->last_error='';
        $changed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET object_status=%s,verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND key_id=%s AND encrypted_sha256=%s','quarantined',PLDR_Core::now(),$object_id,'available',(string)($object['storage_name']??''),(string)($object['key_id']??''),(string)($object['encrypted_sha256']??'')));
        if(false===$changed){PLDR_Core::audit('object',$object_id,'delivery_integrity_reconciliation_failed',array('edition_id'=>$edition_id,'db_error'=>substr((string)$wpdb->last_error,0,500)));return;}
        if(1===$changed){
            $wpdb->last_error='';$edition=PLDR_Core::edition($edition_id);
            if(''!==(string)$wpdb->last_error){PLDR_Core::audit('object',$object_id,'delivery_integrity_quarantined',array('edition_id'=>$edition_id,'document_revocation_pending'=>true,'error'=>substr(sanitize_text_field($error),0,500)));return;}
            $document_id=(int)($edition['document_id']??0);if($document_id>0)self::revoke_document($document_id,'delivery-integrity-failure');
            PLDR_Core::audit('object',$object_id,'delivery_integrity_quarantined',array('edition_id'=>$edition_id,'document_id'=>$document_id,'error'=>substr(sanitize_text_field($error),0,500),'sample_state_cas'=>true));return;
        }
        $wpdb->last_error='';$current=PLDR_Core::object($object_id);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('object',$object_id,'delivery_integrity_reconciliation_failed',array('edition_id'=>$edition_id,'db_error'=>substr((string)$wpdb->last_error,0,500)));return;}
        if($current&&'quarantined'===(string)$current['object_status'])return;
        PLDR_Core::audit('object',$object_id,'delivery_integrity_stale_sample_ignored',array('edition_id'=>$edition_id,'sample_state_changed'=>true));
    }

    private static function parse_range"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round13 delivery quarantine matches={n}')
write(access,s2)
lint_commit(13,'CAS bind delivery integrity quarantine to sampled object state',[access])

# Round 14 — key rotation must not overwrite object metadata that changed after the source object was sampled.
s=read(admin)
old="""$updated=$wpdb->update(PLDR_Core::table('objects'),array('storage_name'=>$allocation['name'],'storage_scope'=>'pldr','encrypted_sha256'=>$meta['encrypted_sha256'],'key_id'=>$meta['key_id'],'format_version'=>$meta['format'],'verified_at'=>PLDR_Core::now()),array('id'=>(int)$object['id']));if(false===$updated){$wpdb->query('ROLLBACK');PLDR_Storage::delete($allocation['path']);$results[]=array('object_id'=>(int)$object['id'],'ok'=>false,'error'=>'Object metadata key rotation failed.');continue;}"""
new="""$wpdb->last_error='';$updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET storage_name=%s,storage_scope=%s,encrypted_sha256=%s,key_id=%s,format_version=%s,verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND key_id=%s AND encrypted_sha256=%s',$allocation['name'],'pldr',$meta['encrypted_sha256'],$meta['key_id'],$meta['format'],PLDR_Core::now(),(int)$object['id'],'available',(string)$object['storage_name'],(string)$object['key_id'],(string)$object['encrypted_sha256']));if(1!==$updated){$wpdb->query('ROLLBACK');PLDR_Storage::delete($allocation['path']);$results[]=array('object_id'=>(int)$object['id'],'ok'=>false,'error'=>false===$updated?'Object metadata key rotation failed.':'Object state changed during key rotation; the stale rotation was rolled back.','conflict'=>0===$updated);continue;}"""
replace_once(admin,old,new)
lint_commit(14,'CAS bind key rotation to sampled object metadata',[admin])

# Round 15 — rebuilding a derived search index must not mutate the document business timestamp used for catalog ordering/cursors.
replace_once(admin,
"if(false===$wpdb->update(PLDR_Core::table('documents'),array('search_text'=>$text,'updated_at'=>PLDR_Core::now()),array('id'=>(int)$r['id'])))",
"if(false===$wpdb->update(PLDR_Core::table('documents'),array('search_text'=>$text),array('id'=>(int)$r['id'])))")
lint_commit(15,'keep derived search-index repair from rewriting document timestamps',[admin])
