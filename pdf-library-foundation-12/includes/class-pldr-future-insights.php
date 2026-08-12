<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Insights {
    private const MAX_EVENTS_PER_HOUR = 1200;
    private const REPORT_GROUP_LIMIT = 1000;
    private const COMPLETION_SCAN_LIMIT = 2000;

    public static function event(int $edition_id,string $type,int $page,int $duration,array $context=array()) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_insight_login','Log in to synchronize private reading insights.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $type=sanitize_key($type);if(!in_array($type,array('open','heartbeat','page','close'),true))return PLDR_Core::machine_error('pldr_insight_type','Unsupported reading event.',400);
        $page=max(1,min((int)$edition['pages'],$page));$duration=max(0,min(900,$duration));
        $device=sanitize_text_field((string)($context['device']??''));$device=function_exists('mb_substr')?mb_substr($device,0,80,'UTF-8'):substr($device,0,80);
        $layout=sanitize_key((string)($context['layout']??''));if(!in_array($layout,array('','single','continuous','spread-ltr','spread-rtl','horizontal','presentation'),true))$layout='';
        $lock='pldr_insight_'.substr(hash('sha256',(string)$uid),0,32);
        $locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(1!==$locked)return PLDR_Core::machine_error('pldr_insight_rate_lock','Private reading-event capacity is temporarily unavailable; retry shortly.',503,array('retry_after'=>2));
        try{
            $since=gmdate('Y-m-d H:i:s',time()-HOUR_IN_SECONDS);
            $wpdb->last_error='';
            $recent=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('reading_events').' WHERE user_id=%d AND created_at>=%s',$uid,$since));
            if(''!==(string)$wpdb->last_error){
                PLDR_Core::audit('edition',$edition_id,'reading_insight_rate_read_failed',array('provider_failure'=>false),$uid);
                return PLDR_Core::machine_error('pldr_insight_rate_read','Private reading-event rate state could not be verified; the event was not stored.',503,array('degraded'=>true));
            }
            try{$limit=(int)apply_filters('pldr_reading_event_hourly_limit',self::MAX_EVENTS_PER_HOUR,$uid,$edition_id);}
            catch(Throwable $e){PLDR_Core::audit('edition',$edition_id,'reading_insight_rate_policy_provider_failed',array('provider_failure'=>1),$uid);return PLDR_Core::machine_error('pldr_insight_rate_policy','Private reading-event rate policy is temporarily unavailable; the event was not stored.',503,array('degraded'=>true,'provider_failure'=>true));}
            $limit=max(60,min(5000,$limit));
            if($recent>=$limit)return PLDR_Core::machine_error('pldr_insight_rate_limit','Private reading-event synchronization is temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit));
            $stored=$wpdb->insert(PLDR_Core::table('reading_events'),array('event_id'=>PLDR_Core::uuid(),'user_id'=>$uid,'edition_id'=>$edition_id,'event_type'=>$type,'page_number'=>$page,'duration_seconds'=>$duration,'context_json'=>wp_json_encode(array('layout'=>$layout,'device'=>$device)),'created_at'=>PLDR_Core::now()));
            if(false===$stored)return PLDR_Core::machine_error('pldr_insight_store','Private reading event could not be stored.',500);
            return array('stored'=>true,'private'=>true,'non_gamified'=>true,'hourly_limit'=>$limit,'rate_accounting_serialized'=>true);
        }finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }

    public static function report(int $days=30):array {
        global $wpdb;$uid=get_current_user_id();if(!$uid)return array();$days=max(1,min(365,$days));$since=gmdate('Y-m-d H:i:s',time()-$days*DAY_IN_SECONDS);
        $wpdb->last_error='';
        $groups=$wpdb->get_results($wpdb->prepare('SELECT e.edition_id,SUM(e.duration_seconds) seconds,COUNT(DISTINCT e.page_number) distinct_pages,d.title FROM '.PLDR_Core::table('reading_events').' e JOIN '.PLDR_Core::table('editions').' ed ON ed.id=e.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=ed.document_id WHERE e.user_id=%d AND e.created_at>=%s GROUP BY e.edition_id,d.title ORDER BY seconds DESC LIMIT %d',$uid,$since,self::REPORT_GROUP_LIMIT+1),ARRAY_A);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('user',$uid,'reading_insights_report_read_failed',array('days'=>$days));
            return array('error'=>PLDR_Core::machine_error('pldr_insight_report_read','Private reading-insight aggregates could not be read reliably; no partial report was returned.',503,array('degraded'=>true)));
        }
        $groups=is_array($groups)?$groups:array();
        $group_scan_truncated=count($groups)>self::REPORT_GROUP_LIMIT;if($group_scan_truncated)$groups=array_slice($groups,0,self::REPORT_GROUP_LIMIT);
        $seconds=0;$pages=0;$editions=0;$recent=array();$hidden=0;
        foreach($groups as $row){
            $edition_id=(int)$row['edition_id'];$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition($edition_id,'read',$uid);
            if(''!==(string)$wpdb->last_error){PLDR_Core::audit('user',$uid,'reading_insights_access_read_failed',array('edition_id'=>$edition_id,'days'=>$days),$uid);return array('error'=>PLDR_Core::machine_error('pldr_insight_access_read','Private reading-insight authorization state could not be verified reliably; no partial aggregate was returned.',503,array('degraded'=>true)));}
            if(!$allowed){$hidden++;continue;}
            $editions++;$seconds+=(int)$row['seconds'];$pages+=(int)$row['distinct_pages'];if(count($recent)<10)$recent[]=array('edition_id'=>$edition_id,'seconds'=>(int)$row['seconds'],'title'=>(string)$row['title']);
        }
        $wpdb->last_error='';
        $completed_rows=$wpdb->get_col($wpdb->prepare('SELECT edition_id FROM '.PLDR_Core::table('reading_state').' WHERE user_id=%d AND percent>=%f AND updated_at>=%s ORDER BY updated_at DESC LIMIT %d',$uid,99.5,$since,self::COMPLETION_SCAN_LIMIT+1));
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('user',$uid,'reading_insights_completion_read_failed',array('days'=>$days));
            return array('error'=>PLDR_Core::machine_error('pldr_insight_completion_read','Private reading-completion state could not be read reliably; no partial report was returned.',503,array('degraded'=>true)));
        }
        $completed_rows=is_array($completed_rows)?$completed_rows:array();
        $completed=0;$completion_scan_truncated=count($completed_rows)>self::COMPLETION_SCAN_LIMIT;if($completion_scan_truncated)$completed_rows=array_slice($completed_rows,0,self::COMPLETION_SCAN_LIMIT);
        foreach($completed_rows as $edition_id){$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition_id,'read',$uid);if(''!==(string)$wpdb->last_error){PLDR_Core::audit('user',$uid,'reading_insights_completion_access_failed',array('edition_id'=>(int)$edition_id,'days'=>$days),$uid);return array('error'=>PLDR_Core::machine_error('pldr_insight_completion_access','Private reading-completion authorization could not be verified reliably; no partial completion count was returned.',503,array('degraded'=>true)));}if($allowed)$completed++;}
        return array(
            'days'=>$days,
            'reading_seconds'=>$seconds,
            'documents'=>$editions,
            'distinct_pages'=>$pages,
            'completed_documents'=>$completed,
            'most_used'=>$recent,
            'inaccessible_editions_excluded'=>$hidden,
            'group_scan_limit'=>self::REPORT_GROUP_LIMIT,
            'group_scan_truncated'=>$group_scan_truncated,
            'completion_scan_limit'=>self::COMPLETION_SCAN_LIMIT,
            'completion_scan_truncated'=>$completion_scan_truncated,
            'aggregate_truncated'=>$group_scan_truncated||$completion_scan_truncated,
            'windowed_completion'=>true,
            'entitlement_rechecked'=>true,
            'private'=>true,
            'streaks'=>false,
            'leaderboards'=>false,
            'shame_mechanics'=>false,
        );
    }
}
