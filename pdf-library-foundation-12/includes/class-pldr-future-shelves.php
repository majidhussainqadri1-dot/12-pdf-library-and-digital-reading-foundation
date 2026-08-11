<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Shelves {
    private const CUSTOM_SHELF_LIMIT = 100;
    private const LIST_LIMIT = 120;

    public static function ensure_defaults(int $uid) {
        global $wpdb;
        foreach(array('reading'=>'Reading now','later'=>'Read later','complete'=>'Completed','reference'=>'Important reference') as $type=>$name){
            $wpdb->last_error='';
            $exists=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('shelves').' WHERE user_id=%d AND shelf_type=%s LIMIT 1',$uid,$type));
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_default_read','Default shelf state could not be read reliably.',503,array('degraded'=>true,'shelf_type'=>$type));
            if($exists)continue;
            $key=self::default_key($uid,$type);
            $inserted=$wpdb->insert(PLDR_Core::table('shelves'),array('shelf_key'=>$key,'user_id'=>$uid,'name'=>$name,'shelf_type'=>$type,'sort_order'=>0,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
            if(false===$inserted){
                $wpdb->last_error='';
                $race=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('shelves').' WHERE user_id=%d AND shelf_type=%s LIMIT 1',$uid,$type));
                if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_default_recheck','Default shelf state could not be rechecked after a concurrent/store failure.',503,array('degraded'=>true,'shelf_type'=>$type));
                if(!$race){PLDR_Core::audit('user',$uid,'future_shelf_default_failed',array('shelf_type'=>$type));return PLDR_Core::machine_error('pldr_shelf_default_store','Default shelf could not be stored.',500,array('shelf_type'=>$type));}
            }
        }
        return true;
    }

    public static function list():array {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return array();
        $defaults=self::ensure_defaults($uid);if(is_wp_error($defaults))return array('error'=>$defaults);
        $shelves=PLDR_Core::table('shelves');$items=PLDR_Core::table('shelf_items');
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare(
            "SELECT s.*,COUNT(i.id) item_count FROM {$shelves} s LEFT JOIN {$items} i ON i.shelf_id=s.id WHERE s.user_id=%d GROUP BY s.id ORDER BY s.sort_order ASC,s.id ASC LIMIT %d",
            $uid,self::LIST_LIMIT
        ),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_shelf_list_read','Private shelves could not be read reliably.',503,array('degraded'=>true)));
        $rows=is_array($rows)?$rows:array();
        foreach($rows as &$row){$row['id']=(int)$row['id'];$row['version']=(int)$row['version'];$row['count']=(int)($row['item_count']??0);unset($row['item_count']);}
        unset($row);
        return $rows;
    }

    public static function create(string $name):array {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return array('error'=>PLDR_Core::machine_error('pldr_shelf_login','Log in to create a private shelf.',401));
        $name=self::name($name);
        if(''===$name)return array('error'=>PLDR_Core::machine_error('pldr_shelf_name','Shelf name is required.',400));
        $lock='pldr_shelf_create_'.substr(hash('sha256',(string)$uid),0,32);
        $locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,2)',$lock));
        if(1!==$locked)return array('error'=>PLDR_Core::machine_error('pldr_shelf_limit_lock','Private shelf capacity is temporarily busy; retry shortly.',503,array('retry_after'=>2)));
        try{
            $wpdb->last_error='';
            $custom_count=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('shelves').' WHERE user_id=%d AND shelf_type=%s',$uid,'custom'));
            if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_shelf_limit_read','Private shelf capacity could not be verified; no shelf was created.',503,array('degraded'=>true)));
            if($custom_count>=self::CUSTOM_SHELF_LIMIT)return array('error'=>PLDR_Core::machine_error('pldr_shelf_limit','The private custom-shelf limit has been reached.',409,array('limit'=>self::CUSTOM_SHELF_LIMIT)));
            $key=PLDR_Core::uuid();
            $inserted=$wpdb->insert(PLDR_Core::table('shelves'),array('shelf_key'=>$key,'user_id'=>$uid,'name'=>$name,'shelf_type'=>'custom','sort_order'=>0,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
            if(false===$inserted||!(int)$wpdb->insert_id)return array('error'=>PLDR_Core::machine_error('pldr_shelf_store','Private shelf could not be stored.',500));
            return array('id'=>(int)$wpdb->insert_id,'shelf_key'=>$key,'name'=>$name,'version'=>1,'custom_limit'=>self::CUSTOM_SHELF_LIMIT,'limit_serialized'=>true);
        }finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }

    public static function add(int $shelf_id,int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_shelf_login','Log in to manage shelves.',401);
        $wpdb->last_error='';$shelf=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d',$shelf_id,$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_read','Private shelf state could not be read reliably.',503,array('degraded'=>true));
        if(!$shelf)return PLDR_Core::machine_error('pldr_shelf_missing','Shelf not found.',404);
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return $edition;
        $stored=$wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.PLDR_Core::table('shelf_items').' (shelf_id,edition_id,added_at) VALUES (%d,%d,%s)',$shelf_id,$edition_id,PLDR_Core::now()));
        if(false===$stored)return PLDR_Core::machine_error('pldr_shelf_item_store','Shelf item could not be stored.',500);
        return array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id,'added'=>1===$stored,'already_present'=>0===$stored);
    }

    public static function rename(int $shelf_id,string $name) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_shelf_login','Log in to manage shelves.',401);
        $wpdb->last_error='';$shelf=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d',$shelf_id,$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_read','Private shelf state could not be read reliably.',503,array('degraded'=>true));
        if(!$shelf)return PLDR_Core::machine_error('pldr_shelf_missing','Shelf not found.',404);
        if('custom'!==$shelf['shelf_type'])return PLDR_Core::machine_error('pldr_shelf_system','Built-in Smart Shelves cannot be renamed.',409);
        $name=self::name($name);
        if(''===$name)return PLDR_Core::machine_error('pldr_shelf_name','Shelf name is required.',400);
        $next=(int)$shelf['version']+1;
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('shelves').' SET name=%s,version=%d,updated_at=%s WHERE id=%d AND user_id=%d AND version=%d',$name,$next,PLDR_Core::now(),$shelf_id,$uid,(int)$shelf['version']));
        if(false===$updated)return PLDR_Core::machine_error('pldr_shelf_rename','Shelf could not be renamed.',500);
        if(1!==$updated)return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed concurrently; refresh before renaming.',409);
        return array('id'=>$shelf_id,'name'=>$name,'version'=>$next);
    }

    public static function remove(int $shelf_id) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_shelf_login','Log in to manage shelves.',401);
        $wpdb->last_error='';$shelf=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d',$shelf_id,$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_read','Private shelf state could not be read reliably.',503,array('degraded'=>true));
        if(!$shelf)return PLDR_Core::machine_error('pldr_shelf_missing','Shelf not found.',404);
        if('custom'!==$shelf['shelf_type'])return PLDR_Core::machine_error('pldr_shelf_system','Built-in Smart Shelves cannot be deleted.',409);
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_shelf_transaction','Shelf deletion transaction could not start.',500);
        $items=$wpdb->delete(PLDR_Core::table('shelf_items'),array('shelf_id'=>$shelf_id));
        if(false===$items){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_delete','Shelf items could not be removed.',500);}
        $deleted=$wpdb->query($wpdb->prepare('DELETE FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d AND version=%d',$shelf_id,$uid,(int)$shelf['version']));
        if(1!==$deleted){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_conflict','Shelf changed concurrently; deletion was rolled back.',409);}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_shelf_commit','Shelf deletion could not be committed atomically.',500);}
        return array('id'=>$shelf_id,'deleted'=>true,'items_removed'=>(int)$items);
    }

    public static function remove_item(int $shelf_id,int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_shelf_login','Log in to manage shelves.',401);
        $wpdb->last_error='';$shelf=$wpdb->get_row($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('shelves').' WHERE id=%d AND user_id=%d',$shelf_id,$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_read','Private shelf state could not be read reliably.',503,array('degraded'=>true));
        if(!$shelf)return PLDR_Core::machine_error('pldr_shelf_missing','Shelf not found.',404);
        $wpdb->last_error='';$exists=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('shelf_items').' WHERE shelf_id=%d AND edition_id=%d',$shelf_id,$edition_id));
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_shelf_item_read','Shelf membership state could not be read reliably.',503,array('degraded'=>true));
        if(!$exists)return PLDR_Core::machine_error('pldr_shelf_item_missing','Shelf item was not found.',404);
        $deleted=$wpdb->delete(PLDR_Core::table('shelf_items'),array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id));
        if(1!==$deleted)return PLDR_Core::machine_error('pldr_shelf_item_delete','Shelf item could not be removed.',500);
        return array('shelf_id'=>$shelf_id,'edition_id'=>$edition_id,'removed'=>true);
    }

    private static function name(string $name):string {
        $name=trim(sanitize_text_field($name));
        return function_exists('mb_substr')?mb_substr($name,0,120,'UTF-8'):substr($name,0,120);
    }

    private static function default_key(int $uid,string $type):string {
        $hex=hash('sha256','pldr-smart-shelf|'.$uid.'|'.$type);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-5'.substr($hex,13,3).'-a'.substr($hex,17,3).'-'.substr($hex,20,12);
    }
}
