from pathlib import Path
import re, subprocess


def read(path): return Path(path).read_text()
def write(path, content): Path(path).write_text(content)
def replace_once(path, old, new):
    src=read(path); count=src.count(old)
    if count!=1: raise SystemExit(f'expected one match in {path}; found {count}: {old[:140]!r}')
    write(path,src.replace(old,new,1))
def regex_once(path, pattern, replacement):
    src=read(path); out,count=re.subn(pattern,replacement,src,flags=re.S)
    if count!=1: raise SystemExit(f'expected one regex match in {path}; found {count}: {pattern[:140]!r}')
    write(path,out)
def lint_commit(round_no,message,files):
    for f in files:
        if f.endswith('.php'): subprocess.run(['php','-l',f],check=True)
        if f.endswith('.js'): subprocess.run(['node','--check',f],check=True)
    subprocess.run(['git','add',*files],check=True)
    subprocess.run(['git','diff','--cached','--check'],check=True)
    subprocess.run(['git','commit','-m',f'R25 round {round_no:02d}: {message}'],check=True)

access='pdf-library-foundation-12/includes/class-pldr-access.php'
storage='pdf-library-foundation-12/includes/class-pldr-storage.php'
admin='pdf-library-foundation-12/includes/class-pldr-admin.php'
rights='pdf-library-foundation-12/includes/class-pldr-rights.php'
reader='pdf-library-foundation-12/includes/class-pldr-reader.php'
a11y='pdf-library-foundation-12/includes/class-pldr-future-a11y.php'
outbox='pdf-library-foundation-12/includes/class-pldr-r21-outbox.php'

# Rounds 1-3 were completed clean before this corrective script begins.

# Round 4 — a public/anonymous grant must remain public/anonymous during delivery reauthorization.
replace_once(access,
"    public static function can_access_edition(int $edition_id, string $operation = 'read', int $user_id = 0): bool {\n        $edition = PLDR_Core::edition($edition_id);\n        if (!$edition) return false;\n        $user_id = $user_id ?: get_current_user_id();",
"    public static function can_access_edition(int $edition_id, string $operation = 'read', int $user_id = 0): bool {\n        $edition = PLDR_Core::edition($edition_id);\n        if (!$edition) return false;\n        // A negative sentinel means an explicitly anonymous/public authorization check.\n        // Zero keeps the historical caller contract of resolving the current user.\n        $user_id = $user_id < 0 ? 0 : ($user_id ?: get_current_user_id());")
replace_once(access,
"        $still_allowed=self::can_access_edition((int)$row['edition_id'],(string)$row['operation'],(int)$row['user_id']);",
"        $grant_user=(int)$row['user_id']>0?(int)$row['user_id']:-1;\n        $still_allowed=self::can_access_edition((int)$row['edition_id'],(string)$row['operation'],$grant_user);")
lint_commit(4,'preserve anonymous delivery audience during reauthorization',[access])

# Round 5 completed clean against the corrected Round-4 state.

# Round 6 — private object identities are canonical single-component names; path-like values must be rejected, and post-rotation old-object cleanup must be observable.
replace_once(storage,
"        $name = basename($storage_name);\n        if ('' === $name || '.' === $name || '..' === $name) {\n            return new WP_Error('pldr_storage_name', 'Invalid private object name.');\n        }",
"        $raw=trim($storage_name);\n        $name=basename($raw);\n        if ('' === $name || '.' === $name || '..' === $name || $raw !== $name || false !== strpos($raw, '/') || false !== strpos($raw, '\\\\') || false !== strpos($raw, \"\\0\")) {\n            return new WP_Error('pldr_storage_name', 'Invalid private object name.');\n        }")
replace_once(storage,
"    public static function delete(string $path): void {\n        if (is_file($path)) {\n            @unlink($path);\n        }\n    }",
"    public static function delete(string $path): bool {\n        if (!is_file($path)) return true;\n        return @unlink($path);\n    }")
replace_once(admin,
"            PLDR_Storage::delete((string)$old);\n            PLDR_Core::audit('object',$object_id,'key_rotated',array('old_key'=>$object['key_id'],'new_key'=>$meta['key_id'],'plaintext_integrity_verified'=>true,'post_rotation_verified'=>true,'cas_committed'=>true));\n            $results[]=array('object_id'=>$object_id,'ok'=>true,'key_id'=>$meta['key_id'],'plaintext_integrity_verified'=>true,'post_rotation_verified'=>true,'cas_committed'=>true);",
"            $old_deleted=PLDR_Storage::delete((string)$old);\n            PLDR_Core::audit('object',$object_id,'key_rotated',array('old_key'=>$object['key_id'],'new_key'=>$meta['key_id'],'plaintext_integrity_verified'=>true,'post_rotation_verified'=>true,'cas_committed'=>true,'old_ciphertext_deleted'=>$old_deleted));\n            $results[]=array('object_id'=>$object_id,'ok'=>$old_deleted,'key_id'=>$meta['key_id'],'plaintext_integrity_verified'=>true,'post_rotation_verified'=>true,'cas_committed'=>true,'old_ciphertext_deleted'=>$old_deleted,'reconciliation_required'=>!$old_deleted);" )
lint_commit(6,'harden private object identity and key-rotation cleanup truthfulness',[storage,admin])

# Round 7 — rights-expiry processing must surface transition failures and schedule prompt reconciliation.
regex_once(rights,
r"    public static function expire_rights\(\):void \{.*?\n    \}\n\}\n\nfinal class PLDR_Book_Packs",
"""    public static function expire_rights():array {
        global $wpdb;
        $summary=array('ok'=>true,'selected'=>0,'restricted'=>0,'failed'=>0,'batch_limit'=>100,'continuation_scheduled'=>false);
        $editions=PLDR_Core::table('editions');
        $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare(
            \"SELECT e.document_id FROM {$editions} e INNER JOIN (SELECT document_id,MAX(id) current_id FROM {$editions} WHERE status=%s GROUP BY document_id) current ON current.current_id=e.id WHERE e.rights_expires_at IS NOT NULL AND e.rights_expires_at<=%s ORDER BY e.rights_expires_at ASC LIMIT 100\",
            'published',PLDR_Core::now()
        ),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'rights_expiry_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$summary['ok']=false;$summary['failed']=1;return $summary;}
        $rows=is_array($rows)?$rows:array();$summary['selected']=count($rows);
        foreach($rows as $r){
            $document_id=(int)$r['document_id'];$wpdb->last_error='';$doc=PLDR_Core::document($document_id);
            if(''!==(string)$wpdb->last_error){$summary['ok']=false;$summary['failed']++;PLDR_Core::audit('document',$document_id,'rights_expiry_document_read_failed',array());continue;}
            if(!$doc||'published'!==$doc['status'])continue;
            $changed=self::set_document_status($document_id,'restricted','rights-expired');
            if(is_wp_error($changed)){$summary['ok']=false;$summary['failed']++;PLDR_Core::audit('document',$document_id,'rights_expiry_transition_failed',array('error_code'=>$changed->get_error_code()));continue;}
            $summary['restricted']++;
        }
        if(100===count($rows)||$summary['failed']>0){
            if(wp_next_scheduled('pldr_rights_expiry'))$summary['continuation_scheduled']=true;
            else $summary['continuation_scheduled']=(bool)wp_schedule_single_event(time()+60,'pldr_rights_expiry');
        }
        return $summary;
    }
}

final class PLDR_Book_Packs""")
lint_commit(7,'make rights-expiry failures explicit and retryable',[rights])

# Round 8 completed clean against the corrected Round-7 state.

# Round 9 — catalog keyset traversal must use an immutable ordering field so updates/repairs cannot move rows across page boundaries.
replace_once(reader,
"        $after_updated=(string)($cursor['updated_at']??'');$after_id=absint($cursor['id']??0);$page_cursor=array();$exhausted=false;",
"        $after_created=(string)($cursor['created_at']??'');$after_id=absint($cursor['id']??0);$page_cursor=array();$exhausted=false;")
replace_once(reader,
"            if(''!==$after_updated&&$after_id>0){$loop_where[]='(d.updated_at<%s OR (d.updated_at=%s AND d.id<%d))';$params[]=$after_updated;$params[]=$after_updated;$params[]=$after_id;}\n            $sql='SELECT d.* FROM '.PLDR_Core::table('documents').' d WHERE '.implode(' AND ',$loop_where).' ORDER BY d.updated_at DESC,d.id DESC LIMIT %d';$params[]=$limit;",
"            if(''!==$after_created&&$after_id>0){$loop_where[]='(d.created_at<%s OR (d.created_at=%s AND d.id<%d))';$params[]=$after_created;$params[]=$after_created;$params[]=$after_id;}\n            $sql='SELECT d.* FROM '.PLDR_Core::table('documents').' d WHERE '.implode(' AND ',$loop_where).' ORDER BY d.created_at DESC,d.id DESC LIMIT %d';$params[]=$limit;")
replace_once(reader,
"                $after_updated=(string)$doc['updated_at'];$after_id=(int)$doc['id'];$raw_scanned++;",
"                $after_created=(string)$doc['created_at'];$after_id=(int)$doc['id'];$raw_scanned++;")
replace_once(reader,
"                if(count($eligible)===$logical_offset+$per_page)$page_cursor=array('updated_at'=>$after_updated,'id'=>$after_id);",
"                if(count($eligible)===$logical_offset+$per_page)$page_cursor=array('created_at'=>$after_created,'id'=>$after_id);")
replace_once(reader,
"        $cursor_point=$page_cursor?:array('updated_at'=>$after_updated,'id'=>$after_id);\n        $remaining_skip=$scan_truncated?max(0,$logical_offset-count($eligible)):0;\n        $next_cursor=$has_more&&!empty($cursor_point['id'])?self::encode_catalog_cursor((string)$cursor_point['updated_at'],(int)$cursor_point['id'],$cursor_context,$remaining_skip):null;",
"        $cursor_point=$page_cursor?:array('created_at'=>$after_created,'id'=>$after_id);\n        $remaining_skip=$scan_truncated?max(0,$logical_offset-count($eligible)):0;\n        $next_cursor=$has_more&&!empty($cursor_point['id'])?self::encode_catalog_cursor((string)$cursor_point['created_at'],(int)$cursor_point['id'],$cursor_context,$remaining_skip):null;")
replace_once(reader,
"    private static function encode_catalog_cursor(string $updated_at,int $id,string $context,int $skip=0):string {\n        $json=wp_json_encode(array('u'=>$updated_at,'i'=>$id,'c'=>$context,'s'=>max(0,$skip)));if(!is_string($json))return '';",
"    private static function encode_catalog_cursor(string $created_at,int $id,string $context,int $skip=0):string {\n        $json=wp_json_encode(array('c_at'=>$created_at,'i'=>$id,'ctx'=>$context,'s'=>max(0,$skip),'t'=>time()));if(!is_string($json))return '';")
replace_once(reader,
"        if(!is_array($decoded)||!isset($decoded['u'],$decoded['i'],$decoded['c'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['i'])<1||false===strtotime((string)$decoded['u']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience or is invalid.',400);\n        return array('updated_at'=>(string)$decoded['u'],'id'=>absint($decoded['i']),'skip'=>absint($decoded['s']??0));",
"        if(!is_array($decoded)||!isset($decoded['c_at'],$decoded['i'],$decoded['ctx'],$decoded['t'])||!hash_equals($context,(string)$decoded['ctx'])||absint($decoded['i'])<1||absint($decoded['t'])<time()-1800||absint($decoded['t'])>time()+60||false===strtotime((string)$decoded['c_at']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience, is expired, or is invalid.',400);\n        return array('created_at'=>(string)$decoded['c_at'],'id'=>absint($decoded['i']),'skip'=>absint($decoded['s']??0));")
lint_commit(9,'use immutable creation ordering for catalog cursor stability',[reader])

# Rounds 10-11 completed clean against the corrected Round-9 state.

# Round 12 — human accessibility verification must verify a stored reviewed assessment, not refresh provider evidence inside the verify action.
regex_once(a11y,
r"    public static function verify\(int \$edition_id,string \$note=''\) \{.*?\n    \}\n\n    private static function dto",
"""    public static function verify(int $edition_id,string $note='') {
        global $wpdb;
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $document_id=(int)$edition['document_id'];
        if(!PLDR_Core::authorize('manage',$document_id)&&!PLDR_Core::authorize('rights',$document_id))return PLDR_Core::machine_error('pldr_a11y_verify_forbidden','Accessibility verification authority is required for this document.',403);
        $note=self::limit(sanitize_textarea_field($note),2000);
        $wpdb->last_error='';$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('a11y_audits').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_a11y_verify_read','Stored accessibility evidence could not be read reliably for human verification.',503,array('degraded'=>true));
        if(!$row)return PLDR_Core::machine_error('pldr_a11y_verify_refresh_required','No stored accessibility assessment exists. Run the governed refresh first, review that assessment, then verify it.',428);
        if((int)$row['verified_by']>0)return PLDR_Core::machine_error('pldr_a11y_verify_final','This accessibility assessment is already verified. Refresh it before a new verification cycle.',409,array('verified_at'=>$row['verified_at']));
        $report=self::dto($row);if(isset($report['error'])&&is_wp_error($report['error']))return $report['error'];
        if((float)$row['score']<75)return PLDR_Core::machine_error('pldr_a11y_verify_score','Accessibility status is below the verification threshold.',409);
        $verified_at=PLDR_Core::now();
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('a11y_audits').' SET verified_by=%d,verified_at=%s,updated_at=%s WHERE edition_id=%d AND verified_by=0 AND score=%f AND status=%s AND findings_json=%s AND provider=%s AND updated_at=%s',get_current_user_id(),$verified_at,$verified_at,$edition_id,(float)$row['score'],(string)$row['status'],(string)$row['findings_json'],(string)$row['provider'],(string)$row['updated_at']));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_a11y_verify_conflict','Accessibility assessment changed before verification; refresh and review the new assessment.',409);
        PLDR_Core::audit('edition',$edition_id,'accessibility_verified',array('document_id'=>$document_id,'note_present'=>''!==trim($note),'assessment_updated_at'=>(string)$row['updated_at']));
        $report['verified']=true;$report['verified_at']=$verified_at;$report['public_badge_allowed']=true;$report['verification_used_stored_assessment']=true;return $report;
    }

    private static function dto""")
lint_commit(12,'separate accessibility refresh from human verification',[a11y])

# Round 13 completed clean against the corrected Round-12 state.

# Round 14 — the canonical outbox dispatcher and repair surface must truthfully report lease/persistence outcomes.
regex_once(outbox,
r"    public static function dispatch\(\):void \{.*?\n    \}\n\n    private static function dead_letter\(int \$id,string \$lease_until,int \$attempts,string \$code,string \$event_id\):void \{.*?\n    \}\n\}",
"""    public static function dispatch():array {
        global $wpdb;$now=PLDR_Core::now();$table=PLDR_Core::table('outbox');
        $summary=array('ok'=>true,'selected'=>0,'claimed'=>0,'sent'=>0,'retried'=>0,'dead_lettered'=>0,'errors'=>0,'batch_limit'=>50);
        $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare(\"SELECT * FROM {$table} WHERE status IN (%s,%s,%s) AND available_at<=%s ORDER BY id ASC LIMIT 50\",'pending','retry','processing',$now),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'outbox_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$summary['ok']=false;$summary['errors']=1;return $summary;}
        $rows=is_array($rows)?$rows:array();$summary['selected']=count($rows);
        foreach($rows as $row){
            $lease_until=gmdate('Y-m-d H:i:s',time()+10*MINUTE_IN_SECONDS);$wpdb->last_error='';
            $claimed=$wpdb->query($wpdb->prepare(\"UPDATE {$table} SET status=%s,available_at=%s WHERE id=%d AND status IN (%s,%s,%s) AND available_at<=%s\",'processing',$lease_until,(int)$row['id'],'pending','retry','processing',$now));
            if(false===$claimed){$summary['ok']=false;$summary['errors']++;PLDR_Core::audit('outbox',(int)$row['id'],'outbox_claim_failed',array('event_id'=>(string)$row['event_id'],'db_error'=>substr((string)$wpdb->last_error,0,500)));continue;}
            if(1!==$claimed)continue;$summary['claimed']++;
            $event_name=(string)$row['event_name'];
            if(!isset(self::CONTRACTS[$event_name])){if(self::dead_letter((int)$row['id'],$lease_until,(int)$row['attempts'],'unknown-event-contract',(string)$row['event_id']))$summary['dead_lettered']++;else{$summary['ok']=false;$summary['errors']++;}continue;}
            $payload=json_decode((string)$row['payload_json'],true);
            if(!is_array($payload)||JSON_ERROR_NONE!==json_last_error()){if(self::dead_letter((int)$row['id'],$lease_until,(int)$row['attempts'],'invalid-payload-json',(string)$row['event_id']))$summary['dead_lettered']++;else{$summary['ok']=false;$summary['errors']++;}continue;}
            try{
                $accepted=apply_filters('pldr_dispatch_event',true,$event_name,$payload,(string)$row['event_id']);if(false===$accepted)throw new RuntimeException('consumer-requested-retry');
                do_action('sabri_domain_event',$event_name,$payload,(string)$row['event_id'],'file-12');do_action('pldr_event',$event_name,$payload,(string)$row['event_id']);
                $stored=$wpdb->update($table,array('status'=>'sent','sent_at'=>PLDR_Core::now(),'last_error'=>null),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                if(1!==$stored)throw new RuntimeException('lease-state-persist-failed');$summary['sent']++;
            }catch(Throwable $e){
                $attempts=(int)$row['attempts']+1;$status=$attempts>=8?'dead-letter':'retry';$delay=min(3600,30*(2**min($attempts,6)));$safe_code='consumer-dispatch-failed';
                $retry=$wpdb->update($table,array('status'=>$status,'attempts'=>$attempts,'available_at'=>gmdate('Y-m-d H:i:s',time()+$delay),'last_error'=>$safe_code),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                $persisted=1===$retry;
                if($persisted){if('dead-letter'===$status)$summary['dead_lettered']++;else$summary['retried']++;}else{$summary['ok']=false;$summary['errors']++;}
                PLDR_Core::audit('outbox',(int)$row['id'],'outbox_dispatch_failed',array('event_id'=>(string)$row['event_id'],'event_name'=>$event_name,'attempts'=>$attempts,'next_state'=>$status,'error_class'=>sanitize_key(get_class($e)),'persisted'=>$persisted));
            }
        }
        return $summary;
    }

    private static function dead_letter(int $id,string $lease_until,int $attempts,string $code,string $event_id):bool {
        global $wpdb;$table=PLDR_Core::table('outbox');
        $stored=$wpdb->update($table,array('status'=>'dead-letter','attempts'=>max(8,$attempts+1),'last_error'=>$code),array('id'=>$id,'status'=>'processing','available_at'=>$lease_until));
        $persisted=1===$stored;PLDR_Core::audit('outbox',$id,'outbox_dead_lettered',array('event_id'=>$event_id,'reason'=>$code,'persisted'=>$persisted));return $persisted;
    }
}""")
replace_once(admin,
"        if('outbox'===$operation){PLDR_R21_Outbox::dispatch();return array('operation'=>'outbox','ok'=>true,'canonical_guard'=>'R21');}",
"        if('outbox'===$operation){$result=PLDR_R21_Outbox::dispatch();return array_merge(array('operation'=>'outbox','canonical_guard'=>'R21'),$result);}")
replace_once(rights,
"    public static function dispatch_outbox():void {\n        // Backward-compatible legacy entrypoint; the governed R21 dispatcher is the single implementation.\n        PLDR_R21_Outbox::dispatch();\n    }",
"    public static function dispatch_outbox():array {\n        // Backward-compatible legacy entrypoint; the governed R21 dispatcher is the single implementation.\n        return PLDR_R21_Outbox::dispatch();\n    }")
lint_commit(14,'make governed outbox dispatch and repair outcomes truthful',[outbox,admin,rights])

# Rounds 15-16 completed clean against the corrected Round-14 state.

# Round 17 — operational repair must not mutate document business timestamps for derived index work, and token cleanup must report failure/continuation truthfully.
replace_once(admin,
"if(false===$wpdb->update(PLDR_Core::table('documents'),array('search_text'=>$text,'updated_at'=>PLDR_Core::now()),array('id'=>(int)$r['id'])))",
"if(false===$wpdb->update(PLDR_Core::table('documents'),array('search_text'=>$text),array('id'=>(int)$r['id'])))")
replace_once(access,
"    public static function cleanup_tokens(): void {\n        global $wpdb;$batch=500;$continuation=false;\n        $tokens=$wpdb->query($wpdb->prepare(\"DELETE FROM \".PLDR_Core::table('access_tokens').\" WHERE expires_at<%s OR (revoked_at IS NOT NULL AND revoked_at<%s) ORDER BY id ASC LIMIT {$batch}\",gmdate('Y-m-d H:i:s',time()-DAY_IN_SECONDS),gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS)));\n        if(false===$tokens)PLDR_Core::audit('system',0,'access_token_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));elseif($tokens===$batch)$continuation=true;\n        $wpdb->last_error='';\n        $idempotency=$wpdb->query($wpdb->prepare(\"DELETE FROM \".PLDR_Core::table('idempotency').\" WHERE expires_at<=%s ORDER BY expires_at ASC LIMIT {$batch}\",PLDR_Core::now()));\n        if(false===$idempotency)PLDR_Core::audit('system',0,'idempotency_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));elseif($idempotency===$batch)$continuation=true;\n        if($continuation)wp_schedule_single_event(time()+60,'pldr_cleanup_tokens');\n    }",
"    public static function cleanup_tokens(): array {\n        global $wpdb;$batch=500;$continuation=false;$errors=array();\n        $wpdb->last_error='';$tokens=$wpdb->query($wpdb->prepare(\"DELETE FROM \".PLDR_Core::table('access_tokens').\" WHERE expires_at<%s OR (revoked_at IS NOT NULL AND revoked_at<%s) ORDER BY id ASC LIMIT {$batch}\",gmdate('Y-m-d H:i:s',time()-DAY_IN_SECONDS),gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS)));\n        if(false===$tokens){$errors[]='access_tokens';PLDR_Core::audit('system',0,'access_token_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$tokens=0;}elseif($tokens===$batch)$continuation=true;\n        $wpdb->last_error='';$idempotency=$wpdb->query($wpdb->prepare(\"DELETE FROM \".PLDR_Core::table('idempotency').\" WHERE expires_at<=%s ORDER BY expires_at ASC LIMIT {$batch}\",PLDR_Core::now()));\n        if(false===$idempotency){$errors[]='idempotency';PLDR_Core::audit('system',0,'idempotency_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$idempotency=0;}elseif($idempotency===$batch)$continuation=true;\n        $scheduled=false;if($continuation){if(wp_next_scheduled('pldr_cleanup_tokens'))$scheduled=true;else$scheduled=(bool)wp_schedule_single_event(time()+60,'pldr_cleanup_tokens');}\n        return array('ok'=>!$errors,'access_tokens_deleted'=>(int)$tokens,'idempotency_deleted'=>(int)$idempotency,'batch_limit'=>$batch,'continuation_needed'=>$continuation,'continuation_scheduled'=>$scheduled,'errors'=>$errors);\n    }")
replace_once(admin,
"        if('tokens'===$operation){PLDR_Access::cleanup_tokens();return array('operation'=>'tokens','ok'=>true);}",
"        if('tokens'===$operation){$result=PLDR_Access::cleanup_tokens();return array_merge(array('operation'=>'tokens'),$result);}")
lint_commit(17,'make derived index repair non-semantic and cleanup status truthful',[admin,access])

# Rounds 18-19 completed clean against the corrected Round-17 state.
# Round 20 release identity/evidence is intentionally closed after permanent R25 record/regression creation.
