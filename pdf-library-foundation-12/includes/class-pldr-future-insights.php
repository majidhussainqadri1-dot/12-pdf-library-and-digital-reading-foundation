<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Insights {
    public static function event(int $edition_id,string $type,int $page,int $duration,array $context=array()) {
        global $wpdb;$uid=get_current_user_id();if(!$uid)return PLDR_Core::machine_error('pldr_insight_login','Log in to synchronize private reading insights.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $type=sanitize_key($type);if(!in_array($type,array('open','heartbeat','page','close'),true))return PLDR_Core::machine_error('pldr_insight_type','Unsupported reading event.',400);
        $page=max(1,min((int)$edition['pages'],$page));$duration=max(0,min(900,$duration));
        $device=sanitize_text_field((string)($context['device']??''));$device=function_exists('mb_substr')?mb_substr($device,0,80,'UTF-8'):substr($device,0,80);
        $layout=sanitize_key((string)($context['layout']??''));if(!in_array($layout,array('','single','continuous','spread-ltr','spread-rtl','horizontal','presentation'),true))$layout='';
        $stored=$wpdb->insert(PLDR_Core::table('reading_events'),array('event_id'=>PLDR_Core::uuid(),'user_id'=>$uid,'edition_id'=>$edition_id,'event_type'=>$type,'page_number'=>$page,'duration_seconds'=>$duration,'context_json'=>wp_json_encode(array('layout'=>$layout,'device'=>$device)),'created_at'=>PLDR_Core::now()));
        if(false===$stored)return PLDR_Core::machine_error('pldr_insight_store','Private reading event could not be stored.',500);
        return array('stored'=>true,'private'=>true,'non_gamified'=>true);
    }

    public static function report(int $days=30):array {
        global $wpdb;$uid=get_current_user_id();if(!$uid)return array();$days=max(1,min(365,$days));$since=gmdate('Y-m-d H:i:s',time()-$days*DAY_IN_SECONDS);
        $groups=$wpdb->get_results($wpdb->prepare('SELECT e.edition_id,SUM(e.duration_seconds) seconds,COUNT(DISTINCT e.page_number) distinct_pages,d.title FROM '.PLDR_Core::table('reading_events').' e JOIN '.PLDR_Core::table('editions').' ed ON ed.id=e.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=ed.document_id WHERE e.user_id=%d AND e.created_at>=%s GROUP BY e.edition_id,d.title ORDER BY seconds DESC LIMIT 1000',$uid,$since),ARRAY_A)?:array();
        $seconds=0;$pages=0;$editions=0;$recent=array();$hidden=0;
        foreach($groups as $row){$edition_id=(int)$row['edition_id'];if(!PLDR_Access::can_access_edition($edition_id,'read',$uid)){$hidden++;continue;}$editions++;$seconds+=(int)$row['seconds'];$pages+=(int)$row['distinct_pages'];if(count($recent)<10)$recent[]=array('edition_id'=>$edition_id,'seconds'=>(int)$row['seconds'],'title'=>(string)$row['title']);}
        $completed=0;$completed_rows=$wpdb->get_col($wpdb->prepare('SELECT edition_id FROM '.PLDR_Core::table('reading_state').' WHERE user_id=%d AND percent>=%f AND updated_at>=%s ORDER BY updated_at DESC LIMIT 2000',$uid,99.5,$since))?:array();
        foreach($completed_rows as $edition_id)if(PLDR_Access::can_access_edition((int)$edition_id,'read',$uid))$completed++;
        return array('days'=>$days,'reading_seconds'=>$seconds,'documents'=>$editions,'distinct_pages'=>$pages,'completed_documents'=>$completed,'most_used'=>$recent,'inaccessible_editions_excluded'=>$hidden,'windowed_completion'=>true,'entitlement_rechecked'=>true,'private'=>true,'streaks'=>false,'leaderboards'=>false,'shame_mechanics'=>false);
    }
}
