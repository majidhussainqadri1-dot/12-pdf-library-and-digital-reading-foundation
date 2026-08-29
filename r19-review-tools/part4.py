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

access='pdf-library-foundation-12/includes/class-pldr-access.php'
admin='pdf-library-foundation-12/includes/class-pldr-admin.php'
rights='pdf-library-foundation-12/includes/class-pldr-rights.php'
privacy='pdf-library-foundation-12/includes/class-pldr-privacy.php'
storage='pdf-library-foundation-12/includes/class-pldr-storage.php'

# Round 16 — repair/cron token cleanup must report database failure truthfully rather than an unconditional success.
s=read(access)
pattern=r"    public static function cleanup_tokens\(\): void \{.*?\n    \}\n\}"
replacement="""    public static function cleanup_tokens(): array {
        global $wpdb;$batch=500;$errors=array();$scheduled=false;
        $wpdb->last_error='';$tokens=$wpdb->query($wpdb->prepare(\"DELETE FROM \".PLDR_Core::table('access_tokens').\" WHERE expires_at<%s OR (revoked_at IS NOT NULL AND revoked_at<%s) ORDER BY id ASC LIMIT {$batch}\",gmdate('Y-m-d H:i:s',time()-DAY_IN_SECONDS),gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS)));
        if(false===$tokens){$errors[]='access_tokens';PLDR_Core::audit('system',0,'access_token_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$tokens=0;}
        $wpdb->last_error='';$idem=$wpdb->query($wpdb->prepare(\"DELETE FROM \".PLDR_Core::table('idempotency').\" WHERE expires_at<%s ORDER BY expires_at ASC LIMIT {$batch}\",PLDR_Core::now()));
        if(false===$idem){$errors[]='idempotency';PLDR_Core::audit('system',0,'idempotency_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$idem=0;}
        if($batch===$tokens||$batch===$idem){if(!wp_next_scheduled('pldr_cleanup_tokens')){$scheduled=(bool)wp_schedule_single_event(time()+60,'pldr_cleanup_tokens');}else$scheduled=true;}
        return array('ok'=>!$errors,'access_tokens_deleted'=>(int)$tokens,'idempotency_deleted'=>(int)$idem,'batch_limit'=>$batch,'continuation_scheduled'=>$scheduled,'errors'=>$errors);
    }
}"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round16 cleanup method matches={n}')
write(access,s2)
replace_once(admin,
"        if('tokens'===$operation){PLDR_Access::cleanup_tokens();return array('operation'=>'tokens','ok'=>true);}",
"        if('tokens'===$operation){$result=PLDR_Access::cleanup_tokens();return array_merge(array('operation'=>'tokens'),$result);}")
lint_commit(16,'make token cleanup repair fail visible and resumable',[access,admin])

# Round 17 — outbox repair/cron execution must return truthful bounded dispatch outcomes, including dead-letter/retry persistence failures.
s=read(rights)
pattern=r"    public static function dispatch_outbox\(\):void \{.*?\n    \}\n\}"
replacement="""    public static function dispatch_outbox():array {
        global $wpdb;
        $summary=array('ok'=>true,'selected'=>0,'claimed'=>0,'sent'=>0,'retried'=>0,'dead_lettered'=>0,'errors'=>0,'batch_limit'=>50);
        $now=PLDR_Core::now();$wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('outbox').' WHERE status IN (%s,%s,%s) AND available_at<=%s ORDER BY id ASC LIMIT 50','pending','retry','processing',$now),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'outbox_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$summary['ok']=false;$summary['errors']=1;return $summary;}
        $rows=is_array($rows)?$rows:array();$summary['selected']=count($rows);
        foreach($rows as $row){
            $lease_until=gmdate('Y-m-d H:i:s',time()+10*MINUTE_IN_SECONDS);$wpdb->last_error='';
            $claimed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('outbox').' SET status=%s,available_at=%s WHERE id=%d AND status IN (%s,%s,%s) AND available_at<=%s','processing',$lease_until,(int)$row['id'],'pending','retry','processing',$now));
            if(false===$claimed){$summary['ok']=false;$summary['errors']++;PLDR_Core::audit('outbox',(int)$row['id'],'outbox_claim_failed',array('event_id'=>(string)$row['event_id'],'db_error'=>substr((string)$wpdb->last_error,0,500)));continue;}
            if(1!==$claimed)continue;$summary['claimed']++;
            $payload=json_decode((string)$row['payload_json'],true);
            if(!is_array($payload)||JSON_ERROR_NONE!==json_last_error()){
                $dead=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>'dead-letter','attempts'=>max(8,(int)$row['attempts']+1),'last_error'=>'invalid-payload-json'),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                if(1===$dead)$summary['dead_lettered']++;else{$summary['ok']=false;$summary['errors']++;}
                PLDR_Core::audit('outbox',(int)$row['id'],'outbox_payload_corrupt',array('event_id'=>(string)$row['event_id'],'dead_letter_persisted'=>1===$dead));continue;
            }
            try{
                $accepted=apply_filters('pldr_dispatch_event',true,(string)$row['event_name'],$payload,(string)$row['event_id']);if(false===$accepted)throw new RuntimeException('A consumer requested retry.');
                do_action('sabri_domain_event',(string)$row['event_name'],$payload,(string)$row['event_id'],'file-12');do_action('pldr_event',(string)$row['event_name'],$payload,(string)$row['event_id']);
                $stored=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>'sent','sent_at'=>PLDR_Core::now(),'last_error'=>''),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));if(1!==$stored)throw new RuntimeException('Dispatched event lease changed or state could not be persisted.');$summary['sent']++;
            }catch(Throwable $e){
                $attempts=(int)$row['attempts']+1;$status=$attempts>=8?'dead-letter':'retry';$delay=min(3600,30*(2**min($attempts,6)));
                $retry=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>$status,'attempts'=>$attempts,'available_at'=>gmdate('Y-m-d H:i:s',time()+$delay),'last_error'=>sanitize_text_field($e->getMessage())),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                if(1===$retry){if('dead-letter'===$status)$summary['dead_lettered']++;else$summary['retried']++;}else{$summary['ok']=false;$summary['errors']++;PLDR_Core::audit('outbox',(int)$row['id'],'outbox_retry_persist_failed',array('event_id'=>(string)$row['event_id']));}
            }
        }
        return $summary;
    }
}"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round17 dispatch method matches={n}')
write(rights,s2)
replace_once(admin,
"        if('outbox'===$operation){PLDR_Integrations::dispatch_outbox();return array('operation'=>'outbox','ok'=>true);}",
"        if('outbox'===$operation){$result=PLDR_Integrations::dispatch_outbox();return array_merge(array('operation'=>'outbox'),$result);}")
lint_commit(17,'surface truthful bounded outbox dispatch outcomes',[rights,admin])

# Round 18 — rights-expiry cron must not silently discard failed state transitions or wait for the next recurrence after a partial failure.
s=read(rights)
pattern=r"    public static function expire_rights\(\):void \{.*?\n    \}\n\}\n\nfinal class PLDR_Book_Packs"
replacement="""    public static function expire_rights():array {
        global $wpdb;$summary=array('ok'=>true,'selected'=>0,'restricted'=>0,'failed'=>0,'batch_limit'=>100,'continuation_scheduled'=>false);
        $editions=PLDR_Core::table('editions');$wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare(\"SELECT e.document_id FROM {$editions} e INNER JOIN (SELECT document_id,MAX(id) current_id FROM {$editions} WHERE status=%s GROUP BY document_id) current ON current.current_id=e.id WHERE e.rights_expires_at IS NOT NULL AND e.rights_expires_at<=%s ORDER BY e.rights_expires_at ASC LIMIT 100\",'published',PLDR_Core::now()),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'rights_expiry_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$summary['ok']=false;$summary['failed']=1;return $summary;}
        $rows=is_array($rows)?$rows:array();$summary['selected']=count($rows);
        foreach($rows as $r){
            $document_id=(int)$r['document_id'];$wpdb->last_error='';$doc=PLDR_Core::document($document_id);
            if(''!==(string)$wpdb->last_error){$summary['ok']=false;$summary['failed']++;PLDR_Core::audit('document',$document_id,'rights_expiry_document_read_failed',array());continue;}
            if(!$doc||'published'!==$doc['status'])continue;
            $changed=self::set_document_status((int)$doc['id'],'restricted','rights-expired');
            if(is_wp_error($changed)){$summary['ok']=false;$summary['failed']++;PLDR_Core::audit('document',$document_id,'rights_expiry_transition_failed',array('error_code'=>$changed->get_error_code()));continue;}
            $summary['restricted']++;
        }
        if(100===count($rows)||$summary['failed']>0){if(!wp_next_scheduled('pldr_rights_expiry')){$summary['continuation_scheduled']=(bool)wp_schedule_single_event(time()+60,'pldr_rights_expiry');}else$summary['continuation_scheduled']=true;}
        return $summary;
    }
}

final class PLDR_Book_Packs"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round18 rights expiry matches={n}')
write(rights,s2)
lint_commit(18,'make rights expiry failures explicit and retryable',[rights])

# Round 19 — privacy diagnostics should not create fresh raw subject identifiers in audit context after minimization/erasure work.
replace_once(privacy,
"    private static function user_id(string $email): int {\n        $user = get_user_by('email', $email);\n        return $user ? (int) $user->ID : 0;\n    }",
"    private static function user_id(string $email): int {\n        $user = get_user_by('email', $email);\n        return $user ? (int) $user->ID : 0;\n    }\n\n    private static function subject_ref(int $user_id): string {\n        return substr(hash_hmac('sha256',(string)$user_id,wp_salt('auth')),0,24);\n    }")
replace_once(privacy,
"        PLDR_Core::audit('privacy',0,'privacy_export_read_failed',array('user_id'=>$user_id,'scope'=>$scope),$user_id);",
"        PLDR_Core::audit('privacy',0,'privacy_export_read_failed',array('subject_ref'=>self::subject_ref($user_id),'scope'=>$scope),$user_id);")
replace_once(privacy,
"            PLDR_Core::audit('privacy',0,'privacy_legal_hold_provider_failed',array('user_id'=>$user_id,'provider_failure'=>true),$user_id);",
"            PLDR_Core::audit('privacy',0,'privacy_legal_hold_provider_failed',array('subject_ref'=>self::subject_ref($user_id),'provider_failure'=>true),$user_id);")
replace_once(privacy,
"        $subject_ref=substr(hash_hmac('sha256',(string)$user_id,wp_salt('auth')),0,24);",
"        $subject_ref=self::subject_ref($user_id);")
lint_commit(19,'minimize privacy audit subject identifiers',[privacy])

# Round 20 — storage object names are canonical single-component identifiers; silently basename-normalizing a corrupt path-like DB value can bind the wrong object.
replace_once(storage,
"        $name = basename($storage_name);\n        if ('' === $name || '.' === $name || '..' === $name) {\n            return new WP_Error('pldr_storage_name', 'Invalid private object name.');\n        }",
"        $raw=trim($storage_name);$name=basename($raw);\n        if ('' === $name || '.' === $name || '..' === $name || $raw !== $name || false !== strpos($raw, '/') || false !== strpos($raw, '\\\\') || false !== strpos($raw, \"\\0\")) {\n            return new WP_Error('pldr_storage_name', 'Invalid private object name.');\n        }")
lint_commit(20,'reject path-like private object names instead of silently canonicalizing them',[storage])
