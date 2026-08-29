<?php

defined('ABSPATH') || exit;

final class PLDR_Search {
    public static function search(array $args, int $user_id = 0): array {
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
        $logical_offset=$cursor_token?absint($cursor['skip']??0):(($page-1)*$per_page);
        if(!$cursor_token&&$logical_offset>20000)return array('items'=>array(),'error'=>PLDR_Core::machine_error('pldr_catalog_cursor_required','Deep catalog traversal requires the signed cursor returned by the previous page.',400,array('legacy_offset_limit'=>20000)));
        $where = array("d.status='published'");$base_params=array();
        if ($term) { $where[]='d.search_text LIKE %s';$base_params[]='%'.$wpdb->esc_like($term).'%'; }
        if ($type && isset(PLDR_Core::DOCUMENT_TYPES[$type])) { $where[]='d.document_type=%s';$base_params[]=$type; }
        if ($category && isset(PLDR_Core::CATEGORIES[$category])) { $where[]='d.category=%s';$base_params[]=$category; }
        if ($language) { $where[]='d.language=%s';$base_params[]=$language; }
        $batch_size=min(128,max(48,$per_page*3));$target=$logical_offset+$per_page+1;$suggested_limit=max(256,$target*4);$scan_limit_provider_failed=false;
        try{$scan_limit=(int)apply_filters('pldr_search_scan_limit',$suggested_limit,$args,$user_id);}catch(Throwable $e){$scan_limit=$suggested_limit;$scan_limit_provider_failed=true;PLDR_Core::audit('search',0,'catalog_scan_limit_provider_failed',array('provider_failure'=>true),$user_id);}
        $scan_limit=max($batch_size,min(1000,$scan_limit));$raw_scanned=0;$eligible=array();$scan_truncated=false;
        $after_created=(string)($cursor['created_at']??'');$after_id=absint($cursor['id']??0);$page_cursor=array();$exhausted=false;
        while(count($eligible)<$target&&$raw_scanned<$scan_limit){
            $limit=min($batch_size,$scan_limit-$raw_scanned);$loop_where=$where;$params=$base_params;
            if(''!==$after_created&&$after_id>0){$loop_where[]='(d.created_at<%s OR (d.created_at=%s AND d.id<%d))';$params[]=$after_created;$params[]=$after_created;$params[]=$after_id;}
            $sql='SELECT d.* FROM '.PLDR_Core::table('documents').' d WHERE '.implode(' AND ',$loop_where).' ORDER BY d.created_at DESC,d.id DESC LIMIT %d';$params[]=$limit;
            $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare($sql,$params),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_read','PDF Library catalog state could not be read reliably.',503,array('degraded'=>true)),'degraded'=>true,'scan_limit_provider_failed'=>$scan_limit_provider_failed);
            $rows=is_array($rows)?$rows:array();if(!$rows){$exhausted=true;break;}
            $stop=false;
            foreach($rows as $doc){
                $after_created=(string)$doc['created_at'];$after_id=(int)$doc['id'];$raw_scanned++;
                $wpdb->last_error='';$edition=PLDR_Core::current_edition((int)$doc['id']);
                if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_edition_read','PDF Library edition state could not be read reliably during catalog filtering.',503,array('degraded'=>true)),'degraded'=>true);
                if(!$edition)continue;
                $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',$user_id);
                if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_access_read','PDF Library authorization state could not be verified reliably during catalog filtering.',503,array('degraded'=>true)),'degraded'=>true);
                if(!$allowed)continue;
                $wpdb->last_error='';$dto=PLDR_Core::public_document_dto($doc,$edition);
                if(''!==(string)$wpdb->last_error)return array('items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,'error'=>PLDR_Core::machine_error('pldr_catalog_projection_read','PDF Library access-policy projection could not be read reliably.',503,array('degraded'=>true)),'degraded'=>true);
                $eligible[]=$dto;
                if(count($eligible)===$logical_offset+$per_page)$page_cursor=array('created_at'=>$after_created,'id'=>$after_id);
                if(count($eligible)>=$target){$stop=true;break;}
                if($raw_scanned>=$scan_limit)break;
            }
            if($stop)break;
            if(count($rows)<$limit){$exhausted=true;break;}
        }
        if(!$exhausted&&$raw_scanned>=$scan_limit&&count($eligible)<$target)$scan_truncated=true;
        $items=array_slice($eligible,$logical_offset,$per_page);$has_more=count($eligible)>($logical_offset+$per_page)||$scan_truncated;
        $cursor_point=$page_cursor?:array('created_at'=>$after_created,'id'=>$after_id);
        $remaining_skip=$scan_truncated?max(0,$logical_offset-count($eligible)):0;
        $next_cursor=$has_more&&!empty($cursor_point['id'])?self::encode_catalog_cursor((string)$cursor_point['created_at'],(int)$cursor_point['id'],$cursor_context,$remaining_skip):null;
        return array('items'=>$items,'page'=>$page,'per_page'=>$per_page,'has_more'=>$has_more,'next_cursor'=>$next_cursor,'cursor_supported'=>true,'pagination_mode'=>$cursor_token?'cursor':'legacy-page-compatible','access_filtered_pagination'=>true,'scan_truncated'=>$scan_truncated,'raw_rows_scanned'=>$raw_scanned,'cursor_skip_remaining'=>$remaining_skip,'scan_limit_provider_failed'=>$scan_limit_provider_failed);
    }

    private static function encode_catalog_cursor(string $created_at,int $id,string $context,int $skip=0):string {
        $json=wp_json_encode(array('c_at'=>$created_at,'i'=>$id,'ctx'=>$context,'s'=>max(0,$skip),'t'=>time()));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');$sig=hash_hmac('sha256',$payload,wp_salt('auth'));return $payload.'.'.$sig;
    }

    private static function decode_catalog_cursor(string $token,string $context){
        if(''===$token)return array();if(strlen($token)>600||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);$expected=hash_hmac('sha256',$payload,wp_salt('auth'));if(!hash_equals($expected,$sig))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$decoded=is_string($raw)?json_decode($raw,true):null;
        if(!is_array($decoded)||!isset($decoded['c_at'],$decoded['i'],$decoded['ctx'],$decoded['t'])||!hash_equals($context,(string)$decoded['ctx'])||absint($decoded['i'])<1||absint($decoded['t'])<time()-1800||absint($decoded['t'])>time()+60||false===strtotime((string)$decoded['c_at']))return PLDR_Core::machine_error('pldr_catalog_cursor','Catalog cursor does not match this query/audience, is expired, or is invalid.',400);
        return array('created_at'=>(string)$decoded['c_at'],'id'=>absint($decoded['i']),'skip'=>absint($decoded['s']??0));
    }

    public static function ocr(int $edition_id, string $query, int $user_id = 0,string $cursor_token='',int $limit=50): array {
        global $wpdb;
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id, 'read', $user_id);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_ocr_access_read','Document text-search authorization state could not be verified reliably.',503,array('degraded'=>true)));
        if (!$allowed) return array('error'=>PLDR_Core::machine_error('pldr_ocr_forbidden','Document text search is unavailable.',404));
        $needle = PLDR_Core::normalize_search($query);
        $needle_len=function_exists('mb_strlen')?mb_strlen($needle,'UTF-8'):strlen($needle);
        if ($needle_len < 2) return array('error'=>PLDR_Core::machine_error('pldr_ocr_query_short','Document text search requires at least two characters.',400));
        if ($needle_len > 160) return array('error'=>PLDR_Core::machine_error('pldr_ocr_query_long','Document text search query is too long.',400,array('max_characters'=>160)));
        $limit=max(1,min(100,$limit));
        $context=hash('sha256',$edition_id.'|'.$needle.'|'.$user_id);
        $after_page=self::decode_ocr_cursor($cursor_token,$context);
        if(is_wp_error($after_page))return array('error'=>$after_page);
        try {
            $variants = apply_filters('pldr_search_variants', array($needle), $needle, $edition_id);
        } catch (Throwable $e) {
            PLDR_Core::audit('edition',$edition_id,'ocr_search_variant_provider_failed',array('provider_failure'=>true),$user_id);
            return array('error'=>PLDR_Core::machine_error('pldr_ocr_variant_provider','Document text-search expansion is temporarily unavailable.',503,array('degraded'=>true,'provider_failure'=>true)));
        }
        $variants = array_values(array_unique(array_filter(array_map(static function($value):string {
            $value=PLDR_Core::normalize_search((string)$value);
            return function_exists('mb_substr')?mb_substr($value,0,160,'UTF-8'):substr($value,0,160);
        }, (array) $variants))));
        $clauses = array(); $params = array($edition_id);
        foreach (array_slice($variants, 0, 5) as $variant) { if(''===$variant)continue; $clauses[] = 'normalized_text LIKE %s'; $params[] = '%' . $wpdb->esc_like($variant) . '%'; }
        if (!$clauses) return array('error'=>PLDR_Core::machine_error('pldr_ocr_variants','No safe document text-search variant was available.',400));
        $where='edition_id=%d AND (' . implode(' OR ', $clauses) . ')';
        if($after_page>0){$where.=' AND page_number>%d';$params[]=$after_page;}
        $sql = 'SELECT page_number,language,quality_score,text_content FROM ' . PLDR_Core::table('ocr_text') . ' WHERE '.$where.' ORDER BY page_number ASC LIMIT %d';
        $params[]=$limit+1;
        $wpdb->last_error='';
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_ocr_read','Document OCR search state could not be read reliably.',503,array('degraded'=>true)));
        $rows=is_array($rows)?$rows:array();$has_more=count($rows)>$limit;if($has_more)$rows=array_slice($rows,0,$limit);
        $items=array_map(static function (array $row) use ($query): array {
            $text = (string) $row['text_content'];
            $pos = function_exists('mb_stripos') ? mb_stripos($text,$query,0,'UTF-8') : stripos($text,$query);
            $start = false === $pos ? 0 : max(0, $pos - 90);
            $snippet=function_exists('mb_substr')?mb_substr($text,$start,240,'UTF-8'):substr($text,$start,240);
            return array('page' => (int) $row['page_number'], 'language' => $row['language'], 'quality' => (float) $row['quality_score'], 'snippet' => $snippet);
        },$rows);
        $last=$items?(int)$items[count($items)-1]['page']:0;
        return array('items'=>$items,'limit'=>$limit,'has_more'=>$has_more,'next_cursor'=>$has_more&&$last>0?self::encode_ocr_cursor($last,$context):null,'cursor_supported'=>true);
    }

    private static function encode_ocr_cursor(int $page,string $context):string {
        $json=wp_json_encode(array('p'=>$page,'c'=>$context));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');return $payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth'));
    }

    private static function decode_ocr_cursor(string $token,string $context){
        $token=trim($token);if(''===$token)return 0;if(strlen($token)>500||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_ocr_cursor','OCR search cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);$expected=hash_hmac('sha256',$payload,wp_salt('auth'));if(!hash_equals($expected,$sig))return PLDR_Core::machine_error('pldr_ocr_cursor','OCR search cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$decoded=is_string($raw)?json_decode($raw,true):null;
        if(!is_array($decoded)||!isset($decoded['p'],$decoded['c'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['p'])<1)return PLDR_Core::machine_error('pldr_ocr_cursor','OCR search cursor does not match this query/audience or is invalid.',400);
        return absint($decoded['p']);
    }

}

final class PLDR_Reading {
    public static function save_progress(int $edition_id, int $page, int $user_id = 0, string $expected_updated_at = '') {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id) return PLDR_Core::machine_error('pldr_progress_forbidden', 'Reading progress cannot be saved for this document.', 403);
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id, 'read', $user_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_progress_access_read','Reading-progress authorization state could not be verified reliably.',503,array('degraded'=>true));
        if(!$allowed)return PLDR_Core::machine_error('pldr_progress_forbidden', 'Reading progress cannot be saved for this document.', 403);
        $wpdb->last_error='';
        $edition = PLDR_Core::edition($edition_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_progress_edition_read','Reading-progress edition state could not be read reliably.',503,array('degraded'=>true));
        if (!$edition) return PLDR_Core::machine_error('pldr_edition_missing', 'Document edition not found.', 404);
        $pages = max(1, (int) $edition['pages']);
        if ($page < 1 || $page > $pages) return PLDR_Core::machine_error('pldr_page_range', 'Reading page is outside the document.', 400);
        $percent = round(($page / $pages) * 100, 2);
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_progress_transaction','Reading-progress transaction could not be started.',500);
        $table=PLDR_Core::table('reading_state');$wpdb->last_error='';
        $current=$wpdb->get_row($wpdb->prepare('SELECT updated_at FROM '.$table.' WHERE user_id=%d AND edition_id=%d FOR UPDATE',$user_id,$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_progress_revision_read','Reading-progress revision could not be verified reliably.',503,array('degraded'=>true));}
        $current_revision=(string)($current['updated_at']??'');
        if($current_revision!==$expected_updated_at){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_progress_conflict','Reading progress changed on another session or device; refresh before replacing that position.',409,array('current_updated_at'=>$current_revision));}
        $updated_at=PLDR_Core::now();
        if($current_revision){
            $current_ts=strtotime($current_revision);
            $next_ts=strtotime($updated_at);
            if(false!==$current_ts&&false!==$next_ts&&$next_ts<=$current_ts)$updated_at=gmdate('Y-m-d H:i:s',$current_ts+1);
        }
        if($current){
            $ok=$wpdb->query($wpdb->prepare('UPDATE '.$table.' SET last_page=%d,percent=%s,edition_version=%d,updated_at=%s WHERE user_id=%d AND edition_id=%d AND updated_at=%s',$page,(string)$percent,(int)$edition['version'],$updated_at,$user_id,$edition_id,$current_revision));
        }else{
            $ok=$wpdb->insert($table,array('user_id'=>$user_id,'edition_id'=>$edition_id,'last_page'=>$page,'percent'=>$percent,'edition_version'=>(int)$edition['version'],'updated_at'=>$updated_at),array('%d','%d','%d','%f','%d','%s'));
        }
        if (1 !== $ok){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_progress_conflict', 'Reading progress changed concurrently; refresh before saving again.', 409);}
        $event=PLDR_Core::emit('ReadingProgressUpdated.v1', 'edition', $edition_id, array('user_id' => $user_id, 'edition_id' => $edition_id, 'page' => $page, 'percent' => $percent, 'updated_at'=>$updated_at));
        if(is_wp_error($event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_progress_event_atomic','Reading progress was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false,'edition_id'=>$edition_id,'page'=>$page));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_progress_commit','Reading progress could not be committed atomically.',500);}
        return array('page' => $page, 'percent' => $percent, 'updated_at' => $updated_at);
    }

    public static function state(int $edition_id, int $user_id = 0): array {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id) return array('page' => 1, 'percent' => 0);
        $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition($edition_id,'read',$user_id);
        if(''!==(string)$wpdb->last_error)return array('page'=>1,'percent'=>0,'error'=>PLDR_Core::machine_error('pldr_progress_access_read','Private reading-progress authorization state could not be verified reliably.',503,array('degraded'=>true)));
        if(!$allowed)return array('page'=>1,'percent'=>0);
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . PLDR_Core::table('reading_state') . ' WHERE user_id=%d AND edition_id=%d', $user_id, $edition_id), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('page'=>1,'percent'=>0,'error'=>PLDR_Core::machine_error('pldr_progress_read','Private reading progress could not be read reliably.',503,array('degraded'=>true)));
        return $row ? array('page' => (int) $row['last_page'], 'percent' => (float) $row['percent'], 'updated_at' => $row['updated_at']) : array('page' => 1, 'percent' => 0);
    }

    public static function add_item(int $edition_id, array $data, int $user_id = 0) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id) return PLDR_Core::machine_error('pldr_item_forbidden', 'Private reading item cannot be saved.', 403);
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id, 'read', $user_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_item_access_read','Private reading-item authorization state could not be verified reliably.',503,array('degraded'=>true));
        if(!$allowed)return PLDR_Core::machine_error('pldr_item_forbidden', 'Private reading item cannot be saved.', 403);
        $wpdb->last_error='';
        $edition = PLDR_Core::edition($edition_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_item_edition_read','Private reading-item edition state could not be read reliably.',503,array('degraded'=>true));
        if (!$edition) return PLDR_Core::machine_error('pldr_edition_missing', 'Document edition not found.', 404);
        $type = sanitize_key((string) ($data['type'] ?? 'bookmark'));
        if (!in_array($type, array('bookmark','note','highlight'), true)) return PLDR_Core::machine_error('pldr_item_type', 'Unsupported private reading item type.', 400);
        $page = absint($data['page'] ?? 0);
        if ($page < 1 || $page > (int) $edition['pages']) return PLDR_Core::machine_error('pldr_page_range', 'Reading item page is outside the document.', 400);
        $note = self::limit_text(sanitize_textarea_field((string) ($data['note'] ?? '')),4000);
        if ('note' === $type && '' === trim($note)) return PLDR_Core::machine_error('pldr_note_empty', 'A private note cannot be empty.', 400);
        $anchor=self::limit_text(sanitize_text_field((string)($data['anchor']??'')),500);
        $tags=array_slice(PLDR_Core::sanitize_json_list($data['tags']??array()),0,30);
        $tags=array_map(static fn(string $tag):string=>self::limit_text($tag,80),$tags);
        $tags_json=wp_json_encode($tags);
        if(!is_string($tags_json))return PLDR_Core::machine_error('pldr_item_tags','Private reading-item tags could not be encoded safely.',400);
        $ok = $wpdb->insert(PLDR_Core::table('reading_items'), array('user_id' => $user_id, 'edition_id' => $edition_id, 'item_type' => $type, 'page_number' => $page, 'anchor_text' => $anchor, 'note_text' => $note, 'tags_json' => $tags_json, 'version' => 1, 'created_at' => PLDR_Core::now(), 'updated_at' => PLDR_Core::now()));
        if (false === $ok) return PLDR_Core::machine_error('pldr_item_store', 'Private reading item could not be saved.', 500);
        return array('id' => (int) $wpdb->insert_id, 'type' => $type, 'page' => $page, 'private' => true);
    }

    public static function items(int $edition_id, int $user_id = 0): array {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id) return array();
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id, 'read', $user_id);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_items_access_read','Private reading-item authorization state could not be verified reliably.',503,array('degraded'=>true)));
        if(!$allowed)return array();
        $wpdb->last_error='';
        $rows = $wpdb->get_results($wpdb->prepare('SELECT id,item_type,page_number,anchor_text,note_text,tags_json,version,created_at,updated_at FROM ' . PLDR_Core::table('reading_items') . ' WHERE user_id=%d AND edition_id=%d ORDER BY page_number ASC,id ASC LIMIT 1000', $user_id, $edition_id), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_items_read','Private reading items could not be read reliably.',503,array('degraded'=>true)));
        $rows=is_array($rows)?$rows:array();
        foreach ($rows as &$row) {
            $row['id']=(int)$row['id'];
            $row['page_number']=(int)$row['page_number'];
            $row['version']=(int)$row['version'];
            $tags=json_decode((string)$row['tags_json'],true);
            if(!is_array($tags)){
                PLDR_Core::audit('reading_item',(int)$row['id'],'reading_item_tags_corrupt',array('edition_id'=>$edition_id),$user_id);
                return array('error'=>PLDR_Core::machine_error('pldr_items_corrupt','Stored private reading-item tags failed integrity validation; no partial item list was returned.',500,array('item_id'=>(int)$row['id'])));
            }
            $row['tags']=$tags;
            unset($row['tags_json']);
        }
        unset($row);
        return $rows ?: array();
    }

    private static function limit_text(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }

    public static function delete_item(int $item_id, int $user_id = 0): bool {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        return false !== $wpdb->delete(PLDR_Core::table('reading_items'), array('id' => $item_id, 'user_id' => $user_id), array('%d','%d'));
    }

    public static function clear(int $user_id): void {
        global $wpdb;
        $wpdb->delete(PLDR_Core::table('reading_state'), array('user_id' => $user_id), array('%d'));
        $wpdb->delete(PLDR_Core::table('reading_items'), array('user_id' => $user_id), array('%d'));
    }
}

final class PLDR_Reader {
    private const THUMBNAIL_PREVIEW_LIMIT = 300;
    private const THUMBNAIL_GRANT_LIMIT = 50;

    public static function library_html(array $args = array()): string {
        $result = PLDR_Search::search(array_merge($_GET, $args), get_current_user_id());
        if(isset($result['error'])&&is_wp_error($result['error']))return self::state_html('error');
        ob_start();
        ?>
        <main class="pldr-shell" dir="auto">
            <header class="pldr-hero">
                <div><span><?php esc_html_e('Global Digital Reading', 'pdf-library-digital-reading'); ?></span><h1><?php esc_html_e('PDF Library', 'pdf-library-digital-reading'); ?></h1><p><?php esc_html_e('Rights-aware books, research, references and educational documents.', 'pdf-library-digital-reading'); ?></p></div>
                <form method="get" role="search"><label class="screen-reader-text" for="pldr-q"><?php esc_html_e('Search library', 'pdf-library-digital-reading'); ?></label><input id="pldr-q" name="q" value="<?php echo esc_attr((string) ($_GET['q'] ?? '')); ?>" placeholder="<?php esc_attr_e('Title, author, ISBN, subject…', 'pdf-library-digital-reading'); ?>"><button type="submit"><?php esc_html_e('Search', 'pdf-library-digital-reading'); ?></button></form>
            </header>
            <form class="pldr-filters" method="get">
                <input type="hidden" name="q" value="<?php echo esc_attr((string) ($_GET['q'] ?? '')); ?>">
                <select name="type" aria-label="<?php esc_attr_e('Document type', 'pdf-library-digital-reading'); ?>"><option value=""><?php esc_html_e('All document types', 'pdf-library-digital-reading'); ?></option><?php foreach (PLDR_Core::DOCUMENT_TYPES as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected((string)($_GET['type']??''),$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                <select name="category" aria-label="<?php esc_attr_e('Category', 'pdf-library-digital-reading'); ?>"><option value=""><?php esc_html_e('All categories', 'pdf-library-digital-reading'); ?></option><?php foreach (PLDR_Core::CATEGORIES as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected((string)($_GET['category']??''),$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                <input name="language" value="<?php echo esc_attr((string)($_GET['language']??'')); ?>" placeholder="<?php esc_attr_e('Language', 'pdf-library-digital-reading'); ?>">
                <button type="submit"><?php esc_html_e('Apply filters', 'pdf-library-digital-reading'); ?></button>
            </form>
            <section class="pldr-grid" aria-live="polite">
                <?php if (!$result['items']): ?><div class="pldr-empty"><h2><?php esc_html_e('No eligible documents found', 'pdf-library-digital-reading'); ?></h2><p><?php esc_html_e('Try a broader search or different filters.', 'pdf-library-digital-reading'); ?></p></div><?php endif; ?>
                <?php foreach ($result['items'] as $item): ?>
                    <article class="pldr-card"><div class="pldr-card-body"><span class="pldr-kicker"><?php echo esc_html(PLDR_Core::DOCUMENT_TYPES[$item['type']] ?? $item['type']); ?></span><h2><a href="<?php echo esc_url(PLDR_Core::route_url('document', array('id'=>$item['id'],'slug'=>$item['slug']))); ?>"><?php echo esc_html($item['title']); ?></a></h2><p><?php echo esc_html((string)($item['edition']['author']??'')); ?> · <?php echo absint((int)($item['edition']['pages']??0)); ?> <?php esc_html_e('pages', 'pdf-library-digital-reading'); ?></p><p><?php echo esc_html((string)($item['language']??'')); ?></p></div></article>
                <?php endforeach; ?>
            </section>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    public static function document_html(string $public_id): string {
        global $wpdb;
        $wpdb->last_error='';
        $doc = PLDR_Core::document_by_public_id($public_id);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        if (!$doc) return self::state_html('not-found');
        $wpdb->last_error='';
        $edition = PLDR_Core::current_edition((int)$doc['id']);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        if (!$edition) return self::state_html('restricted');
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition((int)$edition['id'], 'read', get_current_user_id());
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        if(!$allowed)return self::state_html('restricted');
        $wpdb->last_error='';
        $dto = PLDR_Core::public_document_dto($doc, $edition);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        $cover = self::cover_token((int)$edition['id']);
        try{$related_html=(string)apply_filters('pldr_related_content_html','',(int)$edition['id'],$dto);}
        catch(Throwable $e){$related_html='';PLDR_Core::audit('edition',(int)$edition['id'],'related_content_provider_failed',array('provider_failure'=>true));}
        ob_start(); ?>
        <main class="pldr-shell pldr-document" dir="auto">
            <nav class="pldr-local-nav" aria-label="<?php esc_attr_e('Document navigation', 'pdf-library-digital-reading'); ?>"><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'pdf-library-digital-reading'); ?></a><a href="<?php echo esc_url(PLDR_Core::route_url('library')); ?>"><?php esc_html_e('PDF Library', 'pdf-library-digital-reading'); ?></a></nav>
            <section class="pldr-document-hero"><?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(sprintf(__('Cover of %s','pdf-library-digital-reading'),$dto['title'])); ?>"><?php endif; ?><div><span class="pldr-kicker"><?php echo esc_html(PLDR_Core::DOCUMENT_TYPES[$dto['type']]??$dto['type']); ?></span><h1><?php echo esc_html($dto['title']); ?></h1><p><?php echo esc_html((string)$dto['edition']['author']); ?></p><dl><dt><?php esc_html_e('Edition','pdf-library-digital-reading'); ?></dt><dd><?php echo esc_html((string)$dto['edition']['label']); ?></dd><dt><?php esc_html_e('Year','pdf-library-digital-reading'); ?></dt><dd><?php echo absint((int)$dto['edition']['year']); ?></dd><dt><?php esc_html_e('Pages','pdf-library-digital-reading'); ?></dt><dd><?php echo absint((int)$dto['edition']['pages']); ?></dd><dt><?php esc_html_e('License','pdf-library-digital-reading'); ?></dt><dd><?php echo esc_html((string)$dto['edition']['license']); ?></dd></dl><div class="pldr-actions"><a class="pldr-primary" href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$dto['id']))); ?>"><?php esc_html_e('Read online','pdf-library-digital-reading'); ?></a><?php if ($dto['permissions']['download']): ?><a class="pldr-primary" href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$dto['id'])) . '#download'); ?>"><?php esc_html_e('Download Manager','pdf-library-digital-reading'); ?></a><?php endif; ?></div></div></section>
            <section class="pldr-panel"><h2><?php esc_html_e('Stable citation','pdf-library-digital-reading'); ?></h2><p><?php echo esc_html(self::citation($edition,1,'sabri')); ?></p></section>
            <?php echo wp_kses_post($related_html); ?>
        </main>
        <?php return (string)ob_get_clean();
    }

    public static function reader_html(string $public_id): string {
        global $wpdb;
        $wpdb->last_error='';
        $doc = PLDR_Core::document_by_public_id($public_id);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        if (!$doc) return self::state_html('not-found');
        $wpdb->last_error='';
        $edition = PLDR_Core::current_edition((int)$doc['id']);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        $requested_edition=absint($_GET['edition']??0);
        if($requested_edition && (PLDR_Core::authorize('manage',(int)$doc['id'])||PLDR_Core::authorize('rights',(int)$doc['id']))){
            $wpdb->last_error='';
            $candidate=PLDR_Core::edition($requested_edition);
            if(''!==(string)$wpdb->last_error)return self::state_html('error');
            if($candidate&&(int)$candidate['document_id']===(int)$doc['id'])$edition=$candidate;
        }
        if (!$edition) return self::state_html('restricted');
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id());
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        if(!$allowed)return self::state_html('restricted');
        $wpdb->last_error='';
        $object = PLDR_Core::object((int)$edition['object_id']);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        if (!$object) return self::state_html('error');
        $grant = PLDR_Access::issue_token((int)$edition['id'],(int)$object['id'],'read',get_current_user_id(),900);
        if (is_wp_error($grant)) return self::state_html('error');
        $state = PLDR_Reading::state((int)$edition['id']);
        if(isset($state['error'])&&is_wp_error($state['error']))return self::state_html('error');
        $wpdb->last_error='';
        $interaction_dto=PLDR_Core::public_document_dto($doc,$edition);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        try{$interaction_html=(string)apply_filters('pldr_interaction_controls_html','',(int)$edition['id'],$interaction_dto);}
        catch(Throwable $e){$interaction_html='';PLDR_Core::audit('edition',(int)$edition['id'],'reader_interaction_provider_failed',array('provider_failure'=>true));}
        $thumbs = self::thumbnail_tokens((int)$edition['id']);
        $wpdb->last_error='';
        $policy=PLDR_Core::policy((int)$doc['id']);
        if(''!==(string)$wpdb->last_error||!$policy)return self::state_html('error');
        $config = array(
            'editionId'=>(int)$edition['id'],'publicId'=>$public_id,'title'=>(string)$doc['title'],'pages'=>(int)$edition['pages'],'url'=>$grant['url'],'expiresAt'=>$grant['expires_at'],'startPage'=>max(1,(int)$state['page']),'progressRevision'=>(string)($state['updated_at']??''),
            'rest'=>esc_url_raw(rest_url('pldr/v1/')),'nonce'=>wp_create_nonce('wp_rest'),'thumbnails'=>$thumbs,'canDownload'=>!empty($policy['download_allowed']),'canPrint'=>!empty($policy['print_allowed']),'canOffline'=>!empty($policy['offline_allowed']),
            'strings'=>array('loading'=>__('Loading document…','pdf-library-digital-reading'),'error'=>__('The reader could not load this document. Retry or use the accessible fallback.','pdf-library-digital-reading'),'saved'=>__('Reading position saved privately.','pdf-library-digital-reading')),
        );
        wp_enqueue_style('pldr-reader'); wp_enqueue_script('pldr-reader');
        wp_add_inline_script('pldr-reader','window.PLDR_READER=' . wp_json_encode($config) . ';','before');
        ob_start(); ?>
        <main class="pldr-reader-shell" data-pldr-reader dir="auto">
            <a class="pldr-skip" href="#pldr-reader-frame"><?php esc_html_e('Skip to document','pdf-library-digital-reading'); ?></a>
            <header class="pldr-reader-header"><div><a href="<?php echo esc_url(PLDR_Core::route_url('document',array('id'=>$public_id,'slug'=>$doc['slug']))); ?>">← <?php esc_html_e('Back to document','pdf-library-digital-reading'); ?></a> · <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home','pdf-library-digital-reading'); ?></a><h1><?php echo esc_html($doc['title']); ?></h1></div><div class="pldr-reader-status" aria-live="polite"></div></header>
            <div class="pldr-reader-toolbar" role="toolbar" aria-label="<?php esc_attr_e('PDF reader controls','pdf-library-digital-reading'); ?>">
                <button type="button" data-action="prev" aria-label="<?php esc_attr_e('Previous page','pdf-library-digital-reading'); ?>">‹</button>
                <label><?php esc_html_e('Page','pdf-library-digital-reading'); ?> <input data-page type="number" min="1" max="<?php echo absint((int)$edition['pages']); ?>" value="<?php echo absint((int)$state['page']); ?>"></label><span>/ <?php echo absint((int)$edition['pages']); ?></span>
                <button type="button" data-action="next" aria-label="<?php esc_attr_e('Next page','pdf-library-digital-reading'); ?>">›</button>
                <button type="button" data-action="zoom-out"><?php esc_html_e('Zoom −','pdf-library-digital-reading'); ?></button><button type="button" data-action="zoom-in"><?php esc_html_e('Zoom +','pdf-library-digital-reading'); ?></button><button type="button" data-action="fit"><?php esc_html_e('Fit','pdf-library-digital-reading'); ?></button><button type="button" data-action="fullscreen"><?php esc_html_e('Full screen','pdf-library-digital-reading'); ?></button>
                <button type="button" data-action="bookmark"><?php esc_html_e('Bookmark','pdf-library-digital-reading'); ?></button><button type="button" data-action="note"><?php esc_html_e('Private note','pdf-library-digital-reading'); ?></button><button type="button" data-action="highlight"><?php esc_html_e('Highlight note','pdf-library-digital-reading'); ?></button><button type="button" data-action="citation"><?php esc_html_e('Copy citation','pdf-library-digital-reading'); ?></button><?php if (!empty($config['canPrint'])): ?><button type="button" data-action="print"><?php esc_html_e('Print','pdf-library-digital-reading'); ?></button><?php endif; ?><?php if (!empty($config['canDownload'])): ?><button type="button" data-action="download"><?php esc_html_e('Download','pdf-library-digital-reading'); ?></button><?php endif; ?>
            </div>
            <?php $thumb_limit=min((int)$edition['pages'],self::THUMBNAIL_PREVIEW_LIMIT); ?><div class="pldr-reader-layout"><aside class="pldr-thumbnails" aria-label="<?php esc_attr_e('Page thumbnails','pdf-library-digital-reading'); ?>"><ol><?php for($p=1;$p<=$thumb_limit;$p++): ?><li><button type="button" data-thumb-page="<?php echo $p; ?>"><?php if(isset($thumbs[$p])):?><img loading="lazy" src="<?php echo esc_url($thumbs[$p]); ?>" alt="<?php echo esc_attr(sprintf(__('Page %d thumbnail','pdf-library-digital-reading'),$p)); ?>"><?php else:?><span><?php echo $p; ?></span><?php endif;?></button></li><?php endfor;?></ol><?php if((int)$edition['pages']>$thumb_limit): ?><p><?php echo esc_html(sprintf(__('Showing the first %d page previews; use Page jump for later pages.','pdf-library-digital-reading'),$thumb_limit)); ?></p><?php endif; ?></aside><section class="pldr-reader-stage"><iframe id="pldr-reader-frame" data-frame title="<?php echo esc_attr(sprintf(__('PDF reader for %s','pdf-library-digital-reading'),$doc['title'])); ?>" loading="eager"></iframe><div class="pldr-reader-fallback"><p><?php esc_html_e('If the embedded PDF viewer is unavailable, use the OCR text search or an authorized download where permitted.','pdf-library-digital-reading'); ?></p></div></section></div>
            <section class="pldr-reader-tools"><?php echo wp_kses_post($interaction_html); ?><form data-ocr-search><label><?php esc_html_e('Search document text','pdf-library-digital-reading'); ?><input name="q" minlength="2"></label><button type="submit"><?php esc_html_e('Search','pdf-library-digital-reading'); ?></button></form><div data-ocr-results aria-live="polite"></div><div data-private-items></div></section>
            <section class="pldr-download-manager" hidden data-download-manager><h2><?php esc_html_e('Download Manager','pdf-library-digital-reading'); ?></h2><div data-download-status></div><progress max="100" value="0" data-download-progress></progress><div><button type="button" data-download-start><?php esc_html_e('Start','pdf-library-digital-reading'); ?></button><button type="button" data-download-pause><?php esc_html_e('Pause','pdf-library-digital-reading'); ?></button><button type="button" data-download-resume><?php esc_html_e('Resume','pdf-library-digital-reading'); ?></button></div><code data-download-checksum></code></section>
        </main>
        <?php return (string)ob_get_clean();
    }

    public static function reading_dashboard_html(): string {
        if (!is_user_logged_in()) return '<div class="pldr-state">' . esc_html__('Log in to view private reading progress.','pdf-library-digital-reading') . '</div>';
        global $wpdb;
        $uid=get_current_user_id();
        $cursor_token=substr(sanitize_text_field(wp_unslash((string)($_GET['cursor']??''))),0,600);
        $cursor=self::decode_reading_dashboard_cursor($cursor_token,$uid);
        if(is_wp_error($cursor))return self::state_html('error');
        $where='s.user_id=%d';$params=array($uid);
        if($cursor){
            $where.=' AND (s.updated_at<%s OR (s.updated_at=%s AND s.edition_id<%d))';
            $params[]=(string)$cursor['updated_at'];$params[]=(string)$cursor['updated_at'];$params[]=(int)$cursor['edition_id'];
        }
        $params[]=51;
        $wpdb->last_error='';
        $raw=$wpdb->get_results($wpdb->prepare('SELECT s.*,e.document_id,d.public_id,d.title,d.slug FROM '.PLDR_Core::table('reading_state').' s JOIN '.PLDR_Core::table('editions').' e ON e.id=s.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE '.$where.' ORDER BY s.updated_at DESC,s.edition_id DESC LIMIT %d',$params),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        $raw=is_array($raw)?$raw:array();$has_more=count($raw)>50;if($has_more)$raw=array_slice($raw,0,50);
        $visible=array();
        foreach($raw as $row){
            $wpdb->last_error='';
            $allowed=PLDR_Access::can_access_edition((int)$row['edition_id'],'read',$uid);
            if(''!==(string)$wpdb->last_error)return self::state_html('error');
            if($allowed)$visible[]=$row;
        }
        $next_cursor='';
        if($has_more&&$raw){$last=$raw[count($raw)-1];$next_cursor=self::encode_reading_dashboard_cursor((string)$last['updated_at'],(int)$last['edition_id'],$uid);}
        ob_start();?><main class="pldr-shell"><h1><?php esc_html_e('Reading Workspace','pdf-library-digital-reading');?></h1><?php if(!$visible):?><div class="pldr-empty"><p><?php echo esc_html($has_more?__('No currently accessible reading items were found in this bounded page. Continue to older private progress.','pdf-library-digital-reading'):__('No accessible private reading progress is available.','pdf-library-digital-reading'));?></p></div><?php endif;?><div class="pldr-grid"><?php foreach($visible as $row):?><article class="pldr-card"><div class="pldr-card-body"><h2><a href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$row['public_id'])));?>"><?php echo esc_html($row['title']);?></a></h2><p><?php echo esc_html(sprintf(__('Page %1$d · %2$s%% complete','pdf-library-digital-reading'),(int)$row['last_page'],(string)$row['percent']));?></p></div></article><?php endforeach;?></div><?php if($next_cursor):?><nav class="pldr-local-nav" aria-label="<?php esc_attr_e('Reading workspace pagination','pdf-library-digital-reading');?>"><a class="pldr-primary" href="<?php echo esc_url(add_query_arg('cursor',$next_cursor,PLDR_Core::route_url('reading')));?>"><?php esc_html_e('Older reading progress','pdf-library-digital-reading');?></a></nav><?php endif;?></main><?php return (string)ob_get_clean();
    }

    private static function encode_reading_dashboard_cursor(string $updated_at,int $edition_id,int $user_id):string {
        $json=wp_json_encode(array('u'=>$updated_at,'e'=>$edition_id,'a'=>hash('sha256','reading-dashboard|'.$user_id)));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');return $payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth'));
    }

    private static function decode_reading_dashboard_cursor(string $token,int $user_id){
        if(''===$token)return array();if(strlen($token)>600||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_reading_cursor','Reading-workspace cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);$expected=hash_hmac('sha256',$payload,wp_salt('auth'));if(!hash_equals($expected,$sig))return PLDR_Core::machine_error('pldr_reading_cursor','Reading-workspace cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$decoded=is_string($raw)?json_decode($raw,true):null;$audience=hash('sha256','reading-dashboard|'.$user_id);
        if(!is_array($decoded)||!isset($decoded['u'],$decoded['e'],$decoded['a'])||!hash_equals($audience,(string)$decoded['a'])||absint($decoded['e'])<1||false===strtotime((string)$decoded['u']))return PLDR_Core::machine_error('pldr_reading_cursor','Reading-workspace cursor does not match this account or is invalid.',400);
        return array('updated_at'=>(string)$decoded['u'],'edition_id'=>absint($decoded['e']));
    }

    public static function citation(array $edition,int $page=0,string $style='sabri'): string {
        $author=trim((string)$edition['author_name']); $title=trim((string)$edition['title']); $year=(int)$edition['publication_year']; $label=trim((string)$edition['edition_label']);
        $stable=home_url('/library/document/'.rawurlencode((string)$edition['public_id']).'/');
        if ('apa' === $style) {
            return trim($author . '. ' . ($year ? '(' . $year . '). ' : '') . $title . ($label ? ' (' . $label . ').' : '.') . ' ' . $stable . ($page ? ' p. ' . $page : ''));
        }
        if ('mla' === $style) {
            return trim($author . '. “' . $title . '.” ' . ($edition['publisher'] ?: '') . ', ' . ($year ?: 'n.d.') . '. ' . $stable . ($page ? ' p. ' . $page . '.' : ''));
        }
        return trim($author.' — '.$title.($label?' — '.$label:'').($year?' — '.$year:'').' — File 12 '.$edition['public_id'].($page?' — p. '.$page:'').' — '.$stable);
    }

    private static function cover_token(int $edition_id): string { global $wpdb; $wpdb->last_error=''; $oid=(int)$wpdb->get_var($wpdb->prepare('SELECT object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s LIMIT 1',$edition_id,'cover','available')); if(''!==(string)$wpdb->last_error){PLDR_Core::audit('edition',$edition_id,'cover_derivative_read_failed',array());return'';} if(!$oid)return''; $grant=PLDR_Access::issue_token($edition_id,$oid,'preview',get_current_user_id(),900); return is_array($grant)?$grant['url']:''; }
    private static function thumbnail_tokens(int $edition_id): array { global $wpdb; $wpdb->last_error=''; $rows=$wpdb->get_results($wpdb->prepare('SELECT page_number,object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s ORDER BY page_number ASC LIMIT %d',$edition_id,'thumbnail','available',self::THUMBNAIL_GRANT_LIMIT),ARRAY_A); if(''!==(string)$wpdb->last_error){PLDR_Core::audit('edition',$edition_id,'thumbnail_derivative_read_failed',array());return array();} $rows=is_array($rows)?$rows:array(); $out=array(); foreach($rows as $r){$g=PLDR_Access::issue_token($edition_id,(int)$r['object_id'],'preview',get_current_user_id(),900);if(is_array($g))$out[(int)$r['page_number']]=$g['url'];} return $out; }
    private static function state_html(string $state): string {
        $messages=array('not-found'=>__('Document not found.','pdf-library-digital-reading'),'restricted'=>__('This document is restricted, expired, embargoed, or not available to your account.','pdf-library-digital-reading'),'error'=>__('The document reader is temporarily unavailable.','pdf-library-digital-reading'));
        $trace='error'===$state?PLDR_Core::trace_id():'';
        $actions='<nav class="pldr-local-nav" aria-label="'.esc_attr__('Recovery navigation','pdf-library-digital-reading').'"><a href="'.esc_url(PLDR_Core::route_url('library')).'">'.esc_html__('Return to PDF Library','pdf-library-digital-reading').'</a><a href="'.esc_url(home_url('/')).'">'.esc_html__('Home','pdf-library-digital-reading').'</a></nav>';
        if('error'===$state){$actions.='<p><a href="'.esc_url((string)($_SERVER['REQUEST_URI']??PLDR_Core::route_url('library'))).'">'.esc_html__('Retry this page','pdf-library-digital-reading').'</a></p><p class="pldr-kicker">'.esc_html(sprintf(__('Support reference: %s','pdf-library-digital-reading'),$trace)).'</p>';}
        return '<main class="pldr-shell"><div class="pldr-state"><h1>'.esc_html($messages[$state]??$messages['error']).'</h1>'.$actions.'</div></main>';
    }
}
