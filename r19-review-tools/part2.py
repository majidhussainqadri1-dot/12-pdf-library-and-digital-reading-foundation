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

rest='pdf-library-foundation-12/includes/class-pldr-rest.php'
reader='pdf-library-foundation-12/includes/class-pldr-reader.php'
js='pdf-library-foundation-12/assets/reader.js'

# Round 6 — private reading-item deletion needs the item version the client actually reviewed.
s=read(rest)
pattern=r"    private static function delete_reading_item_owned\(int \$item_id\) \{.*?\n    \}\n\n    public static function delete_reading_item\(WP_REST_Request \$request\) \{.*?\n"
replacement="""    private static function delete_reading_item_owned(int $item_id,int $expected_version=0) {
        global $wpdb;
        $user_id=get_current_user_id();
        if(!$user_id)return PLDR_Core::machine_error('pldr_item_login','Log in to delete a private reading item.',401);
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT id,version FROM '.PLDR_Core::table('reading_items').' WHERE id=%d AND user_id=%d',$item_id,$user_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_item_read','Private reading-item state could not be read reliably.',503,array('degraded'=>true));
        if(!$row)return PLDR_Core::machine_error('pldr_item_missing','Private reading item was not found.',404);
        if($expected_version<1)return PLDR_Core::machine_error('pldr_item_precondition','Deleting a private reading item requires its exact expected version.',428,array('current_version'=>(int)$row['version']));
        if((int)$row['version']!==$expected_version)return PLDR_Core::machine_error('pldr_item_conflict','Private reading item changed; refresh before deleting.',409,array('current_version'=>(int)$row['version']));
        $deleted=$wpdb->query($wpdb->prepare('DELETE FROM '.PLDR_Core::table('reading_items').' WHERE id=%d AND user_id=%d AND version=%d',$item_id,$user_id,$expected_version));
        if(0===$deleted)return PLDR_Core::machine_error('pldr_item_conflict','Private reading item changed concurrently; deletion was not performed.',409);
        if(1!==$deleted)return PLDR_Core::machine_error('pldr_item_delete','Private reading item could not be deleted.',500);
        return array('deleted'=>true,'id'=>$item_id,'deleted_version'=>$expected_version);
    }

    public static function delete_reading_item(WP_REST_Request $request) { $body=$request->get_json_params();$body=is_array($body)?$body:$request->get_params();return self::idempotent($request,'reading-item-delete',static fn()=>self::delete_reading_item_owned(absint($request['id']),absint($body['expected_version']??0))); }
"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round6 delete method matches={n}')
write(rest,s2)
replace_once(js,
"await api(`reading/items/${item.id}`,{method:'DELETE',headers:{'Idempotency-Key':crypto.randomUUID?.()||`delete-${item.id}-${Date.now()}-${Math.random()}`}});",
"await api(`reading/items/${item.id}`,{method:'DELETE',body:{expected_version:item.version},headers:{'Idempotency-Key':crypto.randomUUID?.()||`delete-${item.id}-${Date.now()}-${Math.random()}`}});")
lint_commit(6,'require optimistic version on private item deletion',[rest,js])

# Round 7 — the private reading-items query exposes cursor pagination instead of relying only on deep OFFSET traversal.
s=read(rest)
pattern=r"    public static function reading_items\(WP_REST_Request \$request\) \{.*?\n    \}\n    public static function add_reading_item"
replacement="""    private static function encode_reading_items_cursor(int $user_id,int $edition_id,int $page_number,int $id):string {
        $json=wp_json_encode(array('u'=>$user_id,'e'=>$edition_id,'p'=>$page_number,'i'=>$id,'t'=>time()));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');return $payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth'));
    }

    private static function decode_reading_items_cursor(string $token,int $user_id,int $edition_id){
        if(''===$token)return array('page'=>0,'id'=>0);if(strlen($token)>500||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_items_cursor','Private reading-items cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);if(!hash_equals(hash_hmac('sha256',$payload,wp_salt('auth')),$sig))return PLDR_Core::machine_error('pldr_items_cursor','Private reading-items cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$d=is_string($raw)?json_decode($raw,true):null;
        if(!is_array($d)||(int)($d['u']??0)!==$user_id||(int)($d['e']??0)!==$edition_id||absint($d['i']??0)<1||absint($d['p']??0)<1||absint($d['t']??0)<time()-1800)return PLDR_Core::machine_error('pldr_items_cursor','Private reading-items cursor is invalid, expired, or belongs to a different audience.',400);
        return array('page'=>absint($d['p']),'id'=>absint($d['i']));
    }

    public static function reading_items(WP_REST_Request $request) {
        global $wpdb;
        $uid=get_current_user_id();$edition_id=absint($request['edition_id']);
        if(!$uid)return PLDR_Core::machine_error('pldr_items_forbidden','Private reading items are unavailable.',403);
        $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition($edition_id,'read',$uid);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_items_access_read','Private reading-item authorization state could not be verified reliably.',503,array('degraded'=>true));
        if(!$allowed)return PLDR_Core::machine_error('pldr_items_forbidden','Private reading items are unavailable.',403);
        $limit=max(1,min(200,absint($request['limit']?:100)));$cursor_token=trim((string)$request['cursor']);$offset=max(0,min(5000,absint($request['offset'])));
        $cursor=self::decode_reading_items_cursor($cursor_token,$uid,$edition_id);if(is_wp_error($cursor))return $cursor;
        $table=PLDR_Core::table('reading_items');$wpdb->last_error='';
        if($cursor_token){$rows=$wpdb->get_results($wpdb->prepare('SELECT id,item_type,page_number,anchor_text,note_text,tags_json,version,created_at,updated_at FROM '.$table.' WHERE user_id=%d AND edition_id=%d AND (page_number>%d OR (page_number=%d AND id>%d)) ORDER BY page_number ASC,id ASC LIMIT %d',$uid,$edition_id,$cursor['page'],$cursor['page'],$cursor['id'],$limit+1),ARRAY_A);}
        else{$rows=$wpdb->get_results($wpdb->prepare('SELECT id,item_type,page_number,anchor_text,note_text,tags_json,version,created_at,updated_at FROM '.$table.' WHERE user_id=%d AND edition_id=%d ORDER BY page_number ASC,id ASC LIMIT %d OFFSET %d',$uid,$edition_id,$limit+1,$offset),ARRAY_A);}
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_items_read','Private reading items could not be read reliably.',503,array('degraded'=>true));
        $rows=is_array($rows)?$rows:array();$has_more=count($rows)>$limit;if($has_more)$rows=array_slice($rows,0,$limit);
        foreach($rows as &$row){$row['id']=(int)$row['id'];$row['page_number']=(int)$row['page_number'];$row['version']=(int)$row['version'];$tags=json_decode((string)$row['tags_json'],true);if(!is_array($tags)){PLDR_Core::audit('reading_item',(int)$row['id'],'reading_item_tags_corrupt',array('edition_id'=>$edition_id),$uid);return PLDR_Core::machine_error('pldr_items_corrupt','Stored private reading-item tags failed integrity validation; no partial item page was returned.',500,array('item_id'=>(int)$row['id']));}$row['tags']=$tags;unset($row['tags_json']);}unset($row);
        $last=$rows?end($rows):null;$next=$has_more&&is_array($last)?self::encode_reading_items_cursor($uid,$edition_id,(int)$last['page_number'],(int)$last['id']):null;
        return rest_ensure_response(array('items'=>$rows,'limit'=>$limit,'has_more'=>$has_more,'next_cursor'=>$next,'cursor_supported'=>true,'pagination_mode'=>$cursor_token?'cursor':($offset?'legacy-offset':'cursor-ready'),'legacy_offset'=>$cursor_token?0:$offset));
    }
    public static function add_reading_item"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round7 reading_items matches={n}')
write(rest,s2)
lint_commit(7,'add signed cursor pagination to private reading items',[rest])

# Round 8 — the Reading Workspace silently stopped at 100 records with no navigation.
s=read(reader)
pattern=r"    public static function reading_dashboard_html\(\): string \{.*?\n    \}\n\n    public static function citation"
replacement="""    public static function reading_dashboard_html(): string {
        if (!is_user_logged_in()) return '<div class="pldr-state">' . esc_html__('Log in to view private reading progress.','pdf-library-digital-reading') . '</div>';
        global $wpdb;$uid=get_current_user_id();$page=max(1,absint($_GET['reading_page']??1));$limit=100;$offset=($page-1)*$limit;
        $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare('SELECT s.*,e.document_id,d.public_id,d.title,d.slug FROM '.PLDR_Core::table('reading_state').' s JOIN '.PLDR_Core::table('editions').' e ON e.id=s.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE s.user_id=%d ORDER BY s.updated_at DESC,s.edition_id DESC LIMIT %d OFFSET %d',$uid,$limit+1,$offset),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');$rows=is_array($rows)?$rows:array();$has_more=count($rows)>$limit;if($has_more)$rows=array_slice($rows,0,$limit);
        $visible=array();foreach($rows as $row){$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$row['edition_id'],'read',$uid);if(''!==(string)$wpdb->last_error)return self::state_html('error');if($allowed)$visible[]=$row;}$rows=$visible;
        ob_start();?><main class="pldr-shell"><h1><?php esc_html_e('Reading Workspace','pdf-library-digital-reading');?></h1><div class="pldr-grid"><?php foreach($rows as $row):?><article class="pldr-card"><div class="pldr-card-body"><h2><a href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$row['public_id'])));?>"><?php echo esc_html($row['title']);?></a></h2><p><?php echo esc_html(sprintf(__('Page %1$d · %2$s%% complete','pdf-library-digital-reading'),(int)$row['last_page'],(string)$row['percent']));?></p></div></article><?php endforeach;?></div><nav class="pldr-pagination" aria-label="<?php esc_attr_e('Reading workspace pages','pdf-library-digital-reading');?>"><?php if($page>1):?><a href="<?php echo esc_url(add_query_arg('reading_page',$page-1));?>"><?php esc_html_e('Previous','pdf-library-digital-reading');?></a><?php endif;?><?php if($has_more):?><a href="<?php echo esc_url(add_query_arg('reading_page',$page+1));?>"><?php esc_html_e('Next','pdf-library-digital-reading');?></a><?php endif;?></nav></main><?php return (string)ob_get_clean();
    }

    public static function citation"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round8 dashboard matches={n}')
write(reader,s2)
lint_commit(8,'paginate the private reading workspace',[reader])

# Round 9 — signed catalog cursors lacked an expiry/replay window.
replace_once(reader,
"        $json=wp_json_encode(array('u'=>$updated_at,'i'=>$id,'c'=>$context));if(!is_string($json))return '';",
"        $json=wp_json_encode(array('u'=>$updated_at,'i'=>$id,'c'=>$context,'t'=>time()));if(!is_string($json))return '';")
replace_once(reader,
"        if(!is_array($decoded)||!isset($decoded['u'],$decoded['i'],$decoded['c'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['i'])<1||false===strtotime((string)$decoded['u']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience or is invalid.',400);",
"        if(!is_array($decoded)||!isset($decoded['u'],$decoded['i'],$decoded['c'],$decoded['t'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['i'])<1||absint($decoded['t'])<time()-1800||absint($decoded['t'])>time()+60||false===strtotime((string)$decoded['u']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience, is expired, or is invalid.',400);")
lint_commit(9,'add expiry to catalog cursors',[reader])

# Round 10 — cursor traversal must freeze a snapshot because updated_at is mutable between page requests.
replace_once(reader,
"        $cursor=self::decode_catalog_cursor($cursor_token,$cursor_context);\n        if(is_wp_error($cursor))return array('items'=>array(),'error'=>$cursor);",
"        $cursor=self::decode_catalog_cursor($cursor_token,$cursor_context);\n        if(is_wp_error($cursor))return array('items'=>array(),'error'=>$cursor);\n        $snapshot_at=$cursor_token?(string)($cursor['snapshot_at']??''):self::now_snapshot();\n        if(''===$snapshot_at)return array('items'=>array(),'error'=>PLDR_Core::machine_error('pldr_catalog_snapshot','Catalog snapshot could not be established safely.',503));")
replace_once(reader,
"        $where = array(\"d.status='published'\");$base_params=array();",
"        $where = array(\"d.status='published'\",'d.updated_at<=%s');$base_params=array($snapshot_at);")
replace_once(reader,
"        $next_cursor=$has_more&&!empty($cursor_point['id'])?self::encode_catalog_cursor((string)$cursor_point['updated_at'],(int)$cursor_point['id'],$cursor_context):null;",
"        $next_cursor=$has_more&&!empty($cursor_point['id'])?self::encode_catalog_cursor((string)$cursor_point['updated_at'],(int)$cursor_point['id'],$cursor_context,$snapshot_at):null;")
replace_once(reader,
"    private static function encode_catalog_cursor(string $updated_at,int $id,string $context):string {\n        $json=wp_json_encode(array('u'=>$updated_at,'i'=>$id,'c'=>$context,'t'=>time()));if(!is_string($json))return '';",
"    private static function now_snapshot():string { return gmdate('Y-m-d H:i:s'); }\n\n    private static function encode_catalog_cursor(string $updated_at,int $id,string $context,string $snapshot_at):string {\n        $json=wp_json_encode(array('u'=>$updated_at,'i'=>$id,'c'=>$context,'s'=>$snapshot_at,'t'=>time()));if(!is_string($json))return '';")
replace_once(reader,
"        if(!is_array($decoded)||!isset($decoded['u'],$decoded['i'],$decoded['c'],$decoded['t'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['i'])<1||absint($decoded['t'])<time()-1800||absint($decoded['t'])>time()+60||false===strtotime((string)$decoded['u']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience, is expired, or is invalid.',400);\n        return array('updated_at'=>(string)$decoded['u'],'id'=>absint($decoded['i']));",
"        if(!is_array($decoded)||!isset($decoded['u'],$decoded['i'],$decoded['c'],$decoded['s'],$decoded['t'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['i'])<1||absint($decoded['t'])<time()-1800||absint($decoded['t'])>time()+60||false===strtotime((string)$decoded['u'])||false===strtotime((string)$decoded['s']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience, is expired, or is invalid.',400);\n        return array('updated_at'=>(string)$decoded['u'],'id'=>absint($decoded['i']),'snapshot_at'=>(string)$decoded['s']);")
lint_commit(10,'freeze stable catalog snapshots across cursor pages',[reader])
