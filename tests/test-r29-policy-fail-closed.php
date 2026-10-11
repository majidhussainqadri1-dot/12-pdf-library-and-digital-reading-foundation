<?php
/**
 * R29: malformed persisted embargo and unknown access audiences must fail
 * closed, including when external entitlements would otherwise return true.
 *
 * Executes the actual PLDR_Access::can_access_edition method (not a copy).
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
final class PLDR_R29_Core_Fixture {
    public static array $edition = array(
        'id'=>21, 'document_id'=>7, 'status'=>'published',
        'document_status'=>'published', 'rights_expires_at'=>null
    );
    public static array $policy = array(
        'audience'=>'public', 'embargo_until'=>null, 'entitlement_key'=>'education-basic',
        'download_allowed'=>1, 'print_allowed'=>1, 'offline_allowed'=>1
    );
    public static array $audit = array();
    public static int $filter_calls=0;
}
class PLDR_Core {
    public static function edition(int $id) {return $id===21 ? PLDR_R29_Core_Fixture::$edition : null;}
    public static function policy(int $id) {return $id===7 ? PLDR_R29_Core_Fixture::$policy : null;}
    public static function authorize(string $role, int $document_id=0, int $user_id=0):bool {
        // Deliberately uses the current user's privilege when the caller uses zero.
        $user_id=$user_id?:get_current_user_id();
        return $user_id===77 && in_array($role,array('manage','rights','read_private'),true);
    }
    public static function audit(string $type,int $id,string $event,array $extra=array(),int $actor=0):void {
        PLDR_R29_Core_Fixture::$audit[]=$event;
    }
}
function get_current_user_id():int {return 77;}
function apply_filters(string $name,$value,...$args) {
    PLDR_R29_Core_Fixture::$filter_calls++;
    return $name==='pldr_user_entitled' ? true : $value;
}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-access.php';
$assert=static function(bool $ok,string $reason):void {if(!$ok)throw new RuntimeException('R29 authorization policy failure: '.$reason);};
$allow=static function(int $uid,string $op='read'):bool {return PLDR_Access::can_access_edition(21,$op,$uid);};
$reset=static function():void {
    PLDR_R29_Core_Fixture::$edition['rights_expires_at']=null;
    PLDR_R29_Core_Fixture::$edition['status']='published';
    PLDR_R29_Core_Fixture::$edition['document_status']='published';
    PLDR_R29_Core_Fixture::$policy['audience']='public';
    PLDR_R29_Core_Fixture::$policy['embargo_until']=null;
    PLDR_R29_Core_Fixture::$policy['entitlement_key']='education-basic';
    PLDR_R29_Core_Fixture::$filter_calls=0;
};
// Valid cases must continue to work, so this is not just a blanket denial patch.
$reset();$assert($allow(-1),'public valid edition denied to anonymous audience');
$assert($allow(12),'public valid edition denied to ordinary account');
$assert($allow(77),'public valid edition denied to curator');
$reset();PLDR_R29_Core_Fixture::$policy['embargo_until']=gmdate('Y-m-d H:i:s',time()-86400);
$assert($allow(-1),'expired valid embargo should allow anonymous public reading');
$reset();PLDR_R29_Core_Fixture::$policy['embargo_until']=gmdate('Y-m-d H:i:s',time()+86400);
$assert(!$allow(-1),'future embargo allowed an anonymous grant');
$assert(!$allow(12),'future embargo allowed ordinary user');
$assert($allow(77),'future embargo unexpectedly blocked explicitly authorized manager');
// Historically imported or corrupted embargo values cannot silently bypass rights.
$reset();PLDR_R29_Core_Fixture::$policy['embargo_until']='not-a-real-date';
$assert(!$allow(-1),'malformed embargo allowed anonymous');
$assert(!$allow(12),'malformed embargo allowed ordinary account');
$assert($allow(77),'malformed embargo unexpectedly changed existing manager exception');
$assert(in_array('embargo_until_invalid',PLDR_R29_Core_Fixture::$audit,true),'malformed embargo event not audited');
// Invalid audience must not reach the entitlement provider even if it returns true.
$reset();PLDR_R29_Core_Fixture::$policy['audience']='legacy-premium';
$assert(!$allow(-1),'unknown audience granted anonymous access');
$assert(!$allow(12),'unknown audience allowed external entitlement override');
$assert(!$allow(77),'unknown audience unexpectedly permitted a curator via free/public shortcut');
$assert(PLDR_R29_Core_Fixture::$filter_calls===0,'external entitlement provider was called for invalid audience');
$assert(in_array('policy_audience_invalid',PLDR_R29_Core_Fixture::$audit,true),'unknown audience not audited');
$reset();PLDR_R29_Core_Fixture::$policy['audience']='education-entitled';
$assert(!$allow(-1),'education-entitled audience allowed anonymous');
$assert($allow(12),'known education audience provider falsely denied');
$assert(PLDR_R29_Core_Fixture::$filter_calls===1,'valid education entitlement provider not called once');
$reset();PLDR_R29_Core_Fixture::$policy['audience']='account';
$assert(!$allow(-1),'account audience allowed anonymous');
$assert($allow(12),'account audience denied named user');
$reset();PLDR_R29_Core_Fixture::$policy['audience']='assigned';
$assert(!$allow(-1),'assigned audience allowed anonymous');
$assert($allow(12),'assigned authorized provider denied named user');
echo "PASS R29: 23 valid/invalid embargo, audience, privilege and entitlement boundary assertions\n";
