from pathlib import Path
import subprocess,re


def read(p): return Path(p).read_text()
def write(p,s): Path(p).write_text(s)
def replace_once(p,old,new):
    s=read(p)
    if s.count(old)!=1: raise SystemExit(f'expected one match in {p}; found {s.count(old)} for {old[:120]!r}')
    write(p,s.replace(old,new,1))
def lint_commit(n,msg,files):
    for f in files:
        if f.endswith('.php'): subprocess.run(['php','-l',f],check=True)
    subprocess.run(['git','add',*files],check=True)
    subprocess.run(['git','commit','-m',f'R18 round {n:02d}: {msg}'],check=True)

shelves='pdf-library-foundation-12/includes/class-pldr-future-shelves.php'
frest='pdf-library-foundation-12/includes/class-pldr-future-rest.php'
reader='pdf-library-foundation-12/includes/class-pldr-reader.php'
rest='pdf-library-foundation-12/includes/class-pldr-rest.php'
data='pdf-library-foundation-12/includes/class-pldr-future-data.php'

# Round 11 — a stale client must not overwrite a newer Smart Shelf rename.
replace_once(shelves,"    public static function rename(int $shelf_id,string $name) {","    public static function rename(int $shelf_id,string $name,int $expected_version=0) {")
replace_once(
    shelves,
    "        if('custom'!==$shelf['shelf_type'])return PLDR_Core::machine_error('pldr_shelf_system','Built-in Smart Shelves cannot be renamed.',409);\n        $name=self::name($name);",
    "        if('custom'!==$shelf['shelf_type'])return PLDR_Core::machine_error('pldr_shelf_system','Built-in Smart Shelves cannot be renamed.',409);\n        if($expected_version<1)return PLDR_Core::machine_error('pldr_shelf_precondition','Shelf rename requires the exact expected shelf version.',428,array('current_version'=>(int)$shelf['version']));\n        if((int)$shelf['version']!==$expected_version)return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed; refresh before renaming.',409,array('current_version'=>(int)$shelf['version']));\n        $name=self::name($name);"
)
replace_once(shelves,"        $next=(int)$shelf['version']+1;\n        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('shelves').' SET name=%s,version=%d,updated_at=%s WHERE id=%d AND user_id=%d AND version=%d',$name,$next,PLDR_Core::now(),$shelf_id,$uid,(int)$shelf['version']));","        $next=$expected_version+1;\n        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('shelves').' SET name=%s,version=%d,updated_at=%s WHERE id=%d AND user_id=%d AND version=%d',$name,$next,PLDR_Core::now(),$shelf_id,$uid,$expected_version));")
replace_once(frest,"    public static function shelf_rename(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-rename',static fn()=>PLDR_Future_Shelves::rename(absint($r['id']),(string)($b['name']??''))); }","    public static function shelf_rename(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-rename',static fn()=>PLDR_Future_Shelves::rename(absint($r['id']),(string)($b['name']??''),absint($b['expected_version']??0))); }")
lint_commit(11,'require client shelf version for rename',[shelves,frest])

# Round 12 — deleting a stale shelf view must also require the client version.
replace_once(shelves,"    public static function remove(int $shelf_id) {","    public static function remove(int $shelf_id,int $expected_version=0) {")
replace_once(
    shelves,
    "        if('custom'!==$shelf['shelf_type'])return PLDR_Core::machine_error('pldr_shelf_system','Built-in Smart Shelves cannot be deleted.',409);\n        if(false===$wpdb->query('START TRANSACTION'))",
    "        if('custom'!==$shelf['shelf_type'])return PLDR_Core::machine_error('pldr_shelf_system','Built-in Smart Shelves cannot be deleted.',409);\n        if($expected_version<1)return PLDR_Core::machine_error('pldr_shelf_precondition','Shelf deletion requires the exact expected shelf version.',428,array('current_version'=>(int)$shelf['version']));\n        if((int)$shelf['version']!==$expected_version)return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed; refresh before deleting.',409,array('current_version'=>(int)$shelf['version']));\n        if(false===$wpdb->query('START TRANSACTION'))"
)
replace_once(shelves,"        $deleted=$wpdb->query($wpdb->prepare('DELETE FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d AND version=%d',$shelf_id,$uid,(int)$shelf['version']));","        $deleted=$wpdb->query($wpdb->prepare('DELETE FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d AND version=%d',$shelf_id,$uid,$expected_version));")
replace_once(frest,"    public static function shelf_delete(WP_REST_Request $r) { return self::idempotent($r,'shelf-delete',static fn()=>PLDR_Future_Shelves::remove(absint($r['id']))); }","    public static function shelf_delete(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-delete',static fn()=>PLDR_Future_Shelves::remove(absint($r['id']),absint($b['expected_version']??0))); }")
lint_commit(12,'require client shelf version for deletion',[shelves,frest])

# Round 13 — membership changes were invisible to shelf versioning; make add/remove optimistic and atomic.
s=read(shelves)
pattern=r"    public static function add\(int \$shelf_id,int \$edition_id\) \{.*?\n    public static function rename"
replacement="""    public static function add(int $shelf_id,int $edition_id,int $expected_version=0) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_shelf_login','Log in to manage shelves.',401);
        $wpdb->last_error='';$shelf=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d',$shelf_id,$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_read','Private shelf state could not be read reliably.',503,array('degraded'=>true));
        if(!$shelf)return PLDR_Core::machine_error('pldr_shelf_missing','Shelf not found.',404);
        if($expected_version<1)return PLDR_Core::machine_error('pldr_shelf_precondition','Adding a shelf item requires the exact expected shelf version.',428,array('current_version'=>(int)$shelf['version']));
        if((int)$shelf['version']!==$expected_version)return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed; refresh before adding an item.',409,array('current_version'=>(int)$shelf['version']));
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_shelf_item_transaction','Shelf membership transaction could not start.',500);
        $stored=$wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.PLDR_Core::table('shelf_items').' (shelf_id,edition_id,added_at) VALUES (%d,%d,%s)',$shelf_id,$edition_id,PLDR_Core::now()));
        if(false===$stored){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_item_store','Shelf item could not be stored.',500);}
        if(0===$stored){$wpdb->query('ROLLBACK');return array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id,'added'=>false,'already_present'=>true,'version'=>$expected_version);}
        $next=$expected_version+1;
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('shelves').' SET version=%d,updated_at=%s WHERE id=%d AND user_id=%d AND version=%d',$next,PLDR_Core::now(),$shelf_id,$uid,$expected_version));
        if(1!==$updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed concurrently; membership insertion was rolled back.',409);}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_item_commit','Shelf membership could not be committed atomically.',500);}
        return array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id,'added'=>true,'already_present'=>false,'version'=>$next);
    }

    public static function rename"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round13 add method matches={n}')
write(shelves,s2)
s=read(shelves)
pattern=r"    public static function remove_item\(int \$shelf_id,int \$edition_id\) \{.*?\n    private static function name"
replacement="""    public static function remove_item(int $shelf_id,int $edition_id,int $expected_version=0) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_shelf_login','Log in to manage shelves.',401);
        $wpdb->last_error='';$shelf=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d',$shelf_id,$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_read','Private shelf state could not be read reliably.',503,array('degraded'=>true));
        if(!$shelf)return PLDR_Core::machine_error('pldr_shelf_missing','Shelf not found.',404);
        if($expected_version<1)return PLDR_Core::machine_error('pldr_shelf_precondition','Removing a shelf item requires the exact expected shelf version.',428,array('current_version'=>(int)$shelf['version']));
        if((int)$shelf['version']!==$expected_version)return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed; refresh before removing an item.',409,array('current_version'=>(int)$shelf['version']));
        $wpdb->last_error='';$exists=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('shelf_items').' WHERE shelf_id=%d AND edition_id=%d',$shelf_id,$edition_id));
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_item_read','Shelf membership state could not be read reliably.',503,array('degraded'=>true));
        if(!$exists)return PLDR_Core::machine_error('pldr_shelf_item_missing','Shelf item was not found.',404);
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_shelf_item_transaction','Shelf membership transaction could not start.',500);
        $deleted=$wpdb->delete(PLDR_Core::table('shelf_items'),array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id));
        if(1!==$deleted){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_item_delete','Shelf item could not be removed.',500);}
        $next=$expected_version+1;
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('shelves').' SET version=%d,updated_at=%s WHERE id=%d AND user_id=%d AND version=%d',$next,PLDR_Core::now(),$shelf_id,$uid,$expected_version));
        if(1!==$updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed concurrently; membership removal was rolled back.',409);}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_item_commit','Shelf membership removal could not be committed atomically.',500);}
        return array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id,'removed'=>true,'version'=>$next);
    }

    private static function name"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round13 remove-item matches={n}')
write(shelves,s2)
replace_once(frest,"    public static function shelf_add(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-add',static fn()=>PLDR_Future_Shelves::add(absint($r['id']),absint($b['edition_id']??0))); }","    public static function shelf_add(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-add',static fn()=>PLDR_Future_Shelves::add(absint($r['id']),absint($b['edition_id']??0),absint($b['expected_version']??0))); }")
replace_once(frest,"    public static function shelf_remove_item(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-remove-item',static fn()=>PLDR_Future_Shelves::remove_item(absint($r['id']),absint($b['edition_id']??0))); }","    public static function shelf_remove_item(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-remove-item',static fn()=>PLDR_Future_Shelves::remove_item(absint($r['id']),absint($b['edition_id']??0),absint($b['expected_version']??0))); }")
lint_commit(13,'version Smart Shelf membership changes atomically',[shelves,frest])

# Round 14 — catalog API lacked its governed cursor contract and allowed pathological query/page windows.
replace_once(rest,"'per_page'=>array('sanitize_callback'=>'absint'))));","'per_page'=>array('sanitize_callback'=>'absint'),'cursor'=>array('sanitize_callback'=>'sanitize_text_field'))));")
s=read(reader)
pattern=r"    public static function search\(array \$args, int \$user_id = 0\): array \{.*?\n    public static function ocr"
replacement="""    public static function search(array $args, int $user_id = 0): array {
        global $wpdb;
        $term = PLDR_Core::normalize_search((string) ($args['q'] ?? ''));
        $term_len=function_exists('mb_strlen')?mb_strlen($term,'UTF-8'):strlen($term);
        if($term_len>160)return array('items'=>array(),'error'=>PLDR_Core::machine_error('pldr_catalog_query_long','PDF Library search query is too long.',400,array('max_characters'=>160)));
        $type = sanitize_key((string) ($args['type'] ?? ''));
        $category = sanitize_key((string) ($args['category'] ?? ''));
        $language = sanitize_text_field((string) ($args['language'] ?? ''));
        $page = max(1, absint($args['page'] ?? 1));
        $per_page = min(48, max(1, absint($args['per_page'] ?? 24)));
        $cursor_token=trim((string)($args['cursor']??''));
        $cursor_context=hash('sha256',implode('|',array($term,$type,$category,$language,(string)$user_id)));
        $cursor=self::decode_catalog_cursor($cursor_token,$cursor_context);
        if(is_wp_error($cursor))return array('items'=>array(),'error'=>$cursor);
        $logical_offset=$cursor_token?0:(($page-1)*$per_page);
        if(!$cursor_token&&$logical_offset>20000)return array('items'=>array(),'error'=>PLDR_Core::machine_error('pldr_catalog_cursor_required','Deep catalog traversal requires the signed cursor returned by the previous page.',400,array('legacy_offset_limit'=>20000)));
        $where = array("d.status='published'");$base_params=array();
        if ($term) { $where[]='d.search_text LIKE %s';$base_params[]='%'.$wpdb->esc_like($term).'%'; }
        if ($type && isset(PLDR_Core::DOCUMENT_TYPES[$type])) { $where[]='d.document_type=%s';$base_params[]=$type; }
        if ($category && isset(PLDR_Core::CATEGORIES[$category])) { $where[]='d.category=%s';$base_params[]=$category; }
        if ($language) { $where[]='d.language=%s';$base_params[]=$language; }
        $batch_size=min(200,max(48,$per_page*4));$target=$logical_offset+$per_page+1;$suggested_limit=max(2000,$target*8);$scan_limit_provider_failed=false;
        try{$scan_limit=(int)apply_filters('pldr_search_scan_limit',$suggested_limit,$args,$user_id);}catch(Throwable $e){$scan_limit=$suggested_limit;$scan_limit_provider_failed=true;PLDR_Core::audit('search',0,'catalog_scan_limit_provider_failed',array('provider_failure'=>true),$user_id);}
        $scan_limit=max($batch_size,min(20000,$scan_limit));$raw_scanned=0;$eligible=array();$scan_truncated=false;
        $after_updated=(string)($cursor['updated_at']??'');$after_id=absint($cursor['id']??0);$page_cursor=array();$exhausted=false;
        while(count($eligible)<$target&&$raw_scanned<$scan_limit){
            $limit=min($batch_size,$scan_limit-$raw_scanned);$loop_where=$where;$params=$base_params;
            if(''!==$after_updated&&$after_id>0){$loop_where[]='(d.updated_at<%s OR (d.updated_at=%s AND d.id<%d))';$params[]=$after_updated;$params[]=$after_updated;$params[]=$after_id;}
            $sql='SELECT d.* FROM '.PLDR_Core::table('documents').' d WHERE '.implode(' AND ',$loop_where).' ORDER BY d.updated_at DESC,d.id DESC LIMIT %d';$params[]=$limit;
            $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare($sql,$params),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_read','PDF Library catalog state could not be read reliably.',503,array('degraded'=>true)),'degraded'=>true,'scan_limit_provider_failed'=>$scan_limit_provider_failed);
            $rows=is_array($rows)?$rows:array();if(!$rows){$exhausted=true;break;}
            $stop=false;
            foreach($rows as $doc){
                $after_updated=(string)$doc['updated_at'];$after_id=(int)$doc['id'];$raw_scanned++;
                $wpdb->last_error='';$edition=PLDR_Core::current_edition((int)$doc['id']);
                if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_edition_read','PDF Library edition state could not be read reliably during catalog filtering.',503,array('degraded'=>true)),'degraded'=>true);
                if(!$edition)continue;
                $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',$user_id);
                if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_access_read','PDF Library authorization state could not be verified reliably during catalog filtering.',503,array('degraded'=>true)),'degraded'=>true);
                if(!$allowed)continue;
                $wpdb->last_error='';$dto=PLDR_Core::public_document_dto($doc,$edition);
                if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_projection_read','PDF Library access-policy projection could not be read reliably.',503,array('degraded'=>true)),'degraded'=>true);
                $eligible[]=$dto;
                if(count($eligible)===$logical_offset+$per_page)$page_cursor=array('updated_at'=>$after_updated,'id'=>$after_id);
                if(count($eligible)>=$target){$stop=true;break;}
                if($raw_scanned>=$scan_limit)break;
            }
            if($stop)break;
            if(count($rows)<$limit){$exhausted=true;break;}
        }
        if(!$exhausted&&$raw_scanned>=$scan_limit&&count($eligible)<$target)$scan_truncated=true;
        $items=array_slice($eligible,$logical_offset,$per_page);$has_more=count($eligible)>($logical_offset+$per_page)||$scan_truncated;
        $cursor_point=$page_cursor?:array('updated_at'=>$after_updated,'id'=>$after_id);
        $next_cursor=$has_more&&!empty($cursor_point['id'])?self::encode_catalog_cursor((string)$cursor_point['updated_at'],(int)$cursor_point['id'],$cursor_context):null;
        return array('items'=>$items,'page'=>$page,'per_page'=>$per_page,'has_more'=>$has_more,'next_cursor'=>$next_cursor,'cursor_supported'=>true,'pagination_mode'=>$cursor_token?'cursor':'legacy-page-compatible','access_filtered_pagination'=>true,'scan_truncated'=>$scan_truncated,'raw_rows_scanned'=>$raw_scanned,'scan_limit_provider_failed'=>$scan_limit_provider_failed);
    }

    private static function encode_catalog_cursor(string $updated_at,int $id,string $context):string {
        $json=wp_json_encode(array('u'=>$updated_at,'i'=>$id,'c'=>$context));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');$sig=hash_hmac('sha256',$payload,wp_salt('auth'));return $payload.'.'.$sig;
    }

    private static function decode_catalog_cursor(string $token,string $context){
        if(''===$token)return array();if(strlen($token)>600||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);$expected=hash_hmac('sha256',$payload,wp_salt('auth'));if(!hash_equals($expected,$sig))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$decoded=is_string($raw)?json_decode($raw,true):null;
        if(!is_array($decoded)||!isset($decoded['u'],$decoded['i'],$decoded['c'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['i'])<1||false===strtotime((string)$decoded['u']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience or is invalid.',400);
        return array('updated_at'=>(string)$decoded['u'],'id'=>absint($decoded['i']));
    }

    public static function ocr"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round14 search method matches={n}')
write(reader,s2)
lint_commit(14,'add signed cursor catalog pagination and bounded query windows',[reader,rest])

# Round 15 — public reflow fallback could repeatedly invoke a costly external provider without a provider-call ceiling.
provider_helper="""    private const PROVIDER_CALLS_PER_HOUR = 120;

    private static function consume_provider_rate(string $kind,int $edition_id) {
        global $wpdb;$uid=get_current_user_id();
        if($uid>0)$identity='u:'.$uid;else{$ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));$ua=substr(sanitize_text_field((string)($_SERVER['HTTP_USER_AGENT']??'unknown')),0,300);$identity='a:'.hash_hmac('sha256',$ip.'|'.$ua,wp_salt('auth'));}
        $kind=sanitize_key($kind);$scope=hash('sha256',$identity.'|'.$kind.'|'.$edition_id);$bucket='pldr_provider_rate_'.substr(hash('sha256',$scope.'|'.gmdate('YmdH')),0,32);$lock='pldr_provider_rate_'.substr($scope,0,32);
        $wpdb->last_error='';$locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));if(''!==(string)$wpdb->last_error||1!==$locked)return PLDR_Core::machine_error('pldr_provider_rate_lock','Advanced reading provider capacity is temporarily unavailable.',503,array('retry_after'=>2));
        try{$count=(int)get_transient($bucket);try{$limit=(int)apply_filters('pldr_future_provider_hourly_limit',self::PROVIDER_CALLS_PER_HOUR,$kind,$edition_id,$uid);}catch(Throwable $e){PLDR_Core::audit('edition',$edition_id,'future_provider_rate_policy_failed',array('provider_kind'=>$kind,'provider_failure'=>true),$uid);return PLDR_Core::machine_error('pldr_provider_rate_policy','Advanced reading provider rate policy could not be verified.',503,array('degraded'=>true,'provider_failure'=>true));}$limit=max(20,min(2000,$limit));if($count>=$limit)return PLDR_Core::machine_error('pldr_provider_rate_limit','Advanced reading provider capacity is temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit,'provider_kind'=>$kind));if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return PLDR_Core::machine_error('pldr_provider_rate_store','Advanced reading provider rate state could not be stored.',503);return true;}finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }

"""
replace_once(data,"    private const REFLOW_WINDOW_LIMIT = 100;\n", "    private const REFLOW_WINDOW_LIMIT = 100;\n"+provider_helper)
replace_once(data,"        if (!$pages) {\n            try {","        if (!$pages) {\n            $provider_rate=self::consume_provider_rate('reflow',$edition_id);if(is_wp_error($provider_rate))return array('error'=>$provider_rate);\n            try {")
lint_commit(15,'rate limit external reflow provider calls',[data])
