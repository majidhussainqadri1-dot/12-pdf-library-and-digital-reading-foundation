from pathlib import Path
import subprocess


def read(p): return Path(p).read_text()
def write(p,s): Path(p).write_text(s)
def replace_once(p,old,new):
    s=read(p)
    if s.count(old)!=1: raise SystemExit(f'expected one match in {p}; found {s.count(old)} for {old[:100]!r}')
    write(p,s.replace(old,new,1))
def lint_commit(n,msg,files):
    for f in files:
        if f.endswith('.php'): subprocess.run(['php','-l',f],check=True)
    subprocess.run(['git','add',*files],check=True)
    subprocess.run(['git','commit','-m',f'R18 round {n:02d}: {msg}'],check=True)

core='pdf-library-foundation-12/includes/class-pldr-core.php'
rest='pdf-library-foundation-12/includes/class-pldr-rest.php'
frest='pdf-library-foundation-12/includes/class-pldr-future-rest.php'
reader='pdf-library-foundation-12/includes/class-pldr-reader.php'
insights='pdf-library-foundation-12/includes/class-pldr-future-insights.php'

# Round 6 — File 12's mutating API constitution requires a server-side abuse ceiling for every mutation.
helper="""    public static function consume_mutation_rate(string $route,int $actor_id=0,int $default_limit=600) {
        global $wpdb;
        $route=substr(sanitize_key(str_replace('/','-',$route)),0,120);
        if(''===$route)$route='mutation';
        $actor_id=$actor_id?:get_current_user_id();
        if($actor_id>0)$identity='u:'.$actor_id;
        else{
            $ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));
            $ua=substr(sanitize_text_field((string)($_SERVER['HTTP_USER_AGENT']??'unknown')),0,300);
            $identity='a:'.hash_hmac('sha256',$ip.'|'.$ua,wp_salt('auth'));
        }
        $scope=hash('sha256',$identity.'|'.$route);
        $bucket='pldr_mut_rate_'.substr(hash('sha256',$scope.'|'.gmdate('YmdH')),0,32);
        $lock='pldr_mut_rate_'.substr($scope,0,32);
        $wpdb->last_error='';$locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(''!==(string)$wpdb->last_error||1!==$locked)return self::machine_error('pldr_mutation_rate_lock','Mutation abuse-protection state is temporarily unavailable; no mutation was executed.',503,array('retry_after'=>2));
        try{
            $count=(int)get_transient($bucket);
            try{$limit=(int)apply_filters('pldr_mutation_hourly_limit',$default_limit,$route,$actor_id);}
            catch(Throwable $e){self::audit('mutation',0,'mutation_rate_policy_provider_failed',array('route'=>$route,'provider_failure'=>true),$actor_id);return self::machine_error('pldr_mutation_rate_policy','Mutation rate policy could not be verified; no mutation was executed.',503,array('degraded'=>true,'provider_failure'=>true));}
            $limit=max(60,min(5000,$limit));
            if($count>=$limit)return self::machine_error('pldr_mutation_rate_limit','This mutation is temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit));
            if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return self::machine_error('pldr_mutation_rate_store','Mutation rate state could not be stored; no mutation was executed.',503);
            return array('allowed'=>true,'hourly_limit'=>$limit,'remaining'=>max(0,$limit-$count-1));
        }finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }

"""
replace_once(core,"    public static function request_fingerprint(WP_REST_Request $request): string {",helper+"    public static function request_fingerprint(WP_REST_Request $request): string {")
replace_once(
    rest,
    "        if('reserved'!==($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_unavailable','Idempotency protection could not be reserved; the mutation was not executed.',503);\n        try {",
    "        if('reserved'!==($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_unavailable','Idempotency protection could not be reserved; the mutation was not executed.',503);\n        $rate=PLDR_Core::consume_mutation_rate('core-'.$route,$actor,600);\n        if(is_wp_error($rate)){if(!PLDR_Core::idempotency_abort($route,$key,$actor))PLDR_Core::audit('mutation',0,'idempotency_abort_after_rate_failure',array('route'=>$route),$actor);return $rate;}\n        try {"
)
lint_commit(6,'enforce centralized core mutation rate limits',[core,rest])

# Round 7 — Future-24 mutations need the same mandatory abuse ceiling, not only selected endpoints.
replace_once(
    frest,
    "        if ('reserved' !== ($claim['state'] ?? '')) return PLDR_Core::machine_error('pldr_future_idempotency_unavailable','Idempotency protection could not be reserved; the mutation was not executed.',503);\n        try{$result=$callback();}",
    "        if ('reserved' !== ($claim['state'] ?? '')) return PLDR_Core::machine_error('pldr_future_idempotency_unavailable','Idempotency protection could not be reserved; the mutation was not executed.',503);\n        $rate=PLDR_Core::consume_mutation_rate($route,$actor,600);\n        if(is_wp_error($rate)){if(!PLDR_Core::idempotency_abort($route,$key,$actor))PLDR_Core::audit('mutation',0,'future_idempotency_abort_after_rate_failure',array('route'=>$route),$actor);return $rate;}\n        try{$result=$callback();}"
)
lint_commit(7,'enforce centralized Future-24 mutation rate limits',[frest])

# Round 8 — private reading progress reads must not convert authorization DB failure into an innocent page-1 state.
replace_once(
    reader,
    "        $user_id = $user_id ?: get_current_user_id();\n        if (!$user_id || !PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return array('page' => 1, 'percent' => 0);\n        $wpdb->last_error='';",
    "        $user_id = $user_id ?: get_current_user_id();\n        if (!$user_id) return array('page' => 1, 'percent' => 0);\n        $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition($edition_id,'read',$user_id);\n        if(''!==(string)$wpdb->last_error)return array('page'=>1,'percent'=>0,'error'=>PLDR_Core::machine_error('pldr_progress_access_read','Private reading-progress authorization state could not be verified reliably.',503,array('degraded'=>true)));\n        if(!$allowed)return array('page'=>1,'percent'=>0);\n        $wpdb->last_error='';"
)
lint_commit(8,'make private progress authorization failures fail visible',[reader])

# Round 9 — reading-insight aggregates must abort, not silently hide rows, when entitlement checks hit DB failure.
old="        foreach($groups as $row){$edition_id=(int)$row['edition_id'];if(!PLDR_Access::can_access_edition($edition_id,'read',$uid)){$hidden++;continue;}$editions++;$seconds+=(int)$row['seconds'];$pages+=(int)$row['distinct_pages'];if(count($recent)<10)$recent[]=array('edition_id'=>$edition_id,'seconds'=>(int)$row['seconds'],'title'=>(string)$row['title']);}"
new="        foreach($groups as $row){\n            $edition_id=(int)$row['edition_id'];$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition($edition_id,'read',$uid);\n            if(''!==(string)$wpdb->last_error){PLDR_Core::audit('user',$uid,'reading_insights_access_read_failed',array('edition_id'=>$edition_id,'days'=>$days),$uid);return array('error'=>PLDR_Core::machine_error('pldr_insight_access_read','Private reading-insight authorization state could not be verified reliably; no partial aggregate was returned.',503,array('degraded'=>true)));}\n            if(!$allowed){$hidden++;continue;}\n            $editions++;$seconds+=(int)$row['seconds'];$pages+=(int)$row['distinct_pages'];if(count($recent)<10)$recent[]=array('edition_id'=>$edition_id,'seconds'=>(int)$row['seconds'],'title'=>(string)$row['title']);\n        }"
replace_once(insights,old,new)
lint_commit(9,'fail closed on insight aggregate authorization read errors',[insights])

# Round 10 — completion counts need the same fail-visible entitlement recheck.
replace_once(
    insights,
    "        foreach($completed_rows as $edition_id)if(PLDR_Access::can_access_edition((int)$edition_id,'read',$uid))$completed++;",
    "        foreach($completed_rows as $edition_id){$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition_id,'read',$uid);if(''!==(string)$wpdb->last_error){PLDR_Core::audit('user',$uid,'reading_insights_completion_access_failed',array('edition_id'=>(int)$edition_id,'days'=>$days),$uid);return array('error'=>PLDR_Core::machine_error('pldr_insight_completion_access','Private reading-completion authorization could not be verified reliably; no partial completion count was returned.',503,array('degraded'=>true)));}if($allowed)$completed++;}"
)
lint_commit(10,'fail closed on completion entitlement read errors',[insights])
