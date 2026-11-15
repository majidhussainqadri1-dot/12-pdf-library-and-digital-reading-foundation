<?php
/**
 * R73: disposable WordPress runtime proof that the Future24 JS configuration
 * carries the currently authenticated actor, not a constant or guessed ID.
 * This is synthetic CI only. No Hostinger users, books or credentials.
 */
if (!defined('ABSPATH') || getenv('CI')!=='true'
 || getenv('FILE12_CI_EPHEMERAL_DB')!=='yes'
 || home_url('/')!=='http://file12-ci.invalid/') {
 fwrite(STDERR,"R73 native actor fixture refused outside disposable CI.\n");
 exit(86);
}
function f12_r73_assert(bool $ok,string $why):void {
 if(!$ok){fwrite(STDERR,"R73 NATIVE FAIL: ".$why."\n");exit(1);}
 echo "PASS: ".$why."\n";
}
f12_r73_assert(class_exists('PLDR_Future') && method_exists('PLDR_Future','assets'),
 'actual Future24 asset provider is loaded');
$admins=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);
$admin=isset($admins[0])?(int)$admins[0]:0;
f12_r73_assert($admin>0,'real synthetic administrator exists');
global $wp_query;
f12_r73_assert($wp_query instanceof WP_Query,'real WordPress query object loaded');
$wp_query->set('pldr_route','read');
$extract=function() {
 $registered=wp_scripts()->registered['pldr-future-24']??null;
 if(!$registered)return null;
 $before=$registered->extra['before']??[];
 foreach(array_reverse($before) as $line){
  $prefix='window.PLDR_FUTURE=';
  if(!str_starts_with((string)$line,$prefix))continue;
  $payload=substr((string)$line,strlen($prefix));
  if(str_ends_with($payload,';'))$payload=substr($payload,0,-1);
  $decoded=json_decode($payload,true);
  return is_array($decoded)?$decoded:null;
 }
 return null;
};
wp_set_current_user($admin);
PLDR_Future::assets();
$adminCfg=$extract();
f12_r73_assert(is_array($adminCfg)&&($adminCfg['loggedIn']??null)===true
 &&($adminCfg['userId']??null)===$admin,
 'real WordPress authenticated actor injected into Future24 client config');
wp_dequeue_script('pldr-future-24');
wp_deregister_script('pldr-future-24');
wp_set_current_user(0);
PLDR_Future::assets();
$guestCfg=$extract();
f12_r73_assert(is_array($guestCfg)&&($guestCfg['loggedIn']??null)===false
 &&($guestCfg['userId']??null)===0,
 'logged-out reader does not retain the previous user identity in JS config');
f12_r73_assert(isset(rest_get_server()->get_routes()['/pldr/v1/future/offline-grant'])
 && isset(rest_get_server()->get_routes()['/pldr/v1/future/offline-authorization/(?P<edition>\\d+)']),
 'native actor-aware offline REST routes remain registered');
echo "R73 native disposable WordPress actor-context configuration PASS; not a Hostinger/live identity isolation proof.\n";
