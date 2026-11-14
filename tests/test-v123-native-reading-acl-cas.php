<?php
/**
 * V12.3 WP-06/WP-08: real disposable MySQL ACL, private notes and revision CAS.
 * Not a PDF delivery, CF04 storage, OCR or Hostinger staging test.
 * All rows/users are synthetic and destroyed with the ephemeral CI job.
 */
if(!defined('ABSPATH') || getenv('CI')!=='true' ||
  getenv('FILE12_CI_EPHEMERAL_DB')!=='yes' ||
  home_url('/')!=='http://file12-ci.invalid/'){
  fwrite(STDERR,"File12 reading ACL/CAS fixture refused: not disposable CI.\n");exit(86);
}
function f12_read_assert($condition,string $msg):void{
  if(!$condition){fwrite(STDERR,"FILE12 READING ACL/CAS FAIL: ".$msg."\n");exit(1);}
  echo "PASS: ".$msg."\n";
}
global $wpdb;
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$admin=isset($admins[0])?(int)$admins[0]:0;
f12_read_assert($admin>0,'actual synthetic WordPress manager exists');
$reader=get_user_by('login','file12_ci_reader');
if(!$reader){
 $created=wp_create_user('file12_ci_reader','only-an-ephemeral-ci-password','file12-reader@example.invalid');
 f12_read_assert(!is_wp_error($created),'created synthetic subscriber');
 $reader=get_user_by('id',(int)$created);
 $reader->set_role('subscriber');
}
$subscriber=(int)$reader->ID;
$now=PLDR_Core::now();
$doc_pub=wp_generate_uuid4();
$doc=[
 'public_id'=>$doc_pub,'title'=>'Synthetic Rights and Privacy Sample','slug'=>'synthetic-rights-qa',
 'document_type'=>'book','category'=>'research','language'=>'en',
 'subjects_json'=>'[]','collections_json'=>'[]',
 'search_text'=>'synthetic rights and privacy sample','status'=>'published',
 'access_mode'=>'public','created_by'=>$admin,'version'=>1,
 'created_at'=>$now,'updated_at'=>$now
];
f12_read_assert($wpdb->insert(PLDR_Core::table('documents'),$doc)!==false,
  'synthetic rights metadata document created in isolated MySQL');
$docid=(int)$wpdb->insert_id;
$edition=[
 'document_id'=>$docid,'edition_label'=>'Synthetic v1','isbn'=>'',
 'publication_year'=>2026,'pages'=>5,'language'=>'en',
 'author_name'=>'CI fixture','translator'=>'','publisher'=>'',
 'source_name'=>'Synthetic source — not a real copyrighted publication',
 'license_code'=>'fixture-only','rights_basis'=>'public-domain',
 'territory'=>'testing-only','rights_expires_at'=>null,'takedown_contact'=>'',
 'sha256'=>hash('sha256','no-real-pdf-exists-for-this-edition'),
 'object_id'=>999999,'status'=>'published','supersedes_edition_id'=>null,
 'version'=>1,'created_at'=>$now,'updated_at'=>$now
];
f12_read_assert($wpdb->insert(PLDR_Core::table('editions'),$edition)!==false,
 'synthetic edition row created without any PDF binary');
$editionid=(int)$wpdb->insert_id;
$policy=[
 'document_id'=>$docid,'audience'=>'public','entitlement_key'=>'',
 'download_allowed'=>0,'print_allowed'=>0,'offline_allowed'=>0,
 'embargo_until'=>null,'version'=>1,'created_at'=>$now,'updated_at'=>$now
];
f12_read_assert($wpdb->insert(PLDR_Core::table('access_policies'),$policy)!==false,
  'synthetic public read-only rights policy persisted');
$policyid=(int)$wpdb->insert_id;
f12_read_assert(PLDR_Access::can_access_edition($editionid,'read',-1)===true,
  'anonymous audience can read rights-granted public metadata');
foreach(['download','print','offline'] as $operation)
 f12_read_assert(!PLDR_Access::can_access_edition($editionid,$operation,-1),
  'public read does not imply '.$operation.' entitlement');
f12_read_assert(!PLDR_Access::can_access_edition($editionid,'unsupported',-1),
 'unknown delivery operation is denied');
$policy_table=PLDR_Core::table('access_policies');
f12_read_assert(1===$wpdb->update($policy_table,['download_allowed'=>2],['id'=>$policyid]),
  'test-only malformed download flag injected directly');
f12_read_assert(!PLDR_Access::can_access_edition($editionid,'download',-1),
  'malformed persisted policy flag 2 cannot grant public download');
$wpdb->update($policy_table,['download_allowed'=>0],['id'=>$policyid]);
f12_read_assert(1===$wpdb->update($policy_table,['audience'=>'account'],['id'=>$policyid]),
  'test-only audience restricted to WordPress account');
f12_read_assert(!PLDR_Access::can_access_edition($editionid,'read',-1),
  'anonymous user denied after public-to-account restriction');
wp_set_current_user($subscriber);
f12_read_assert(PLDR_Access::can_access_edition($editionid,'read',$subscriber)===true,
  'real disposable logged-in subscriber allowed account-only read');
f12_read_assert(1===$wpdb->update($policy_table,['audience'=>'public'],['id'=>$policyid]),
 'test-only public-read policy restored');
wp_set_current_user($admin);
$first=PLDR_Reading::save_progress($editionid,1,$admin,'');
f12_read_assert(is_array($first)&&($first['page']??0)===1 &&
  !empty($first['updated_at']),
 'first reading progress stored through real MySQL/transaction/outbox');
$second=PLDR_Reading::save_progress($editionid,2,$admin,(string)$first['updated_at']);
f12_read_assert(is_array($second)&&($second['page']??0)===2 &&
  (string)$second['updated_at']>(string)$first['updated_at'],
 'same-second updated_at revision increases monotonically');
$stale=PLDR_Reading::save_progress($editionid,3,$admin,(string)$first['updated_at']);
f12_read_assert(is_wp_error($stale)&&$stale->get_error_code()==='pldr_progress_conflict',
 'stale device cannot overwrite newer saved page with obsolete revision');
$state=PLDR_Reading::state($editionid,$admin);
f12_read_assert(($state['page']??0)===2,
 'newer reading position survived rejected stale write');
$note=PLDR_Reading::add_item($editionid,[
 'type'=>'note','page'=>2,'note'=>'SYNTHETIC PRIVATE NOTE — DO NOT PUBLISH',
 'anchor'=>'sample','tags'=>['private']
],$admin);
f12_read_assert(is_array($note)&&($note['private']??false)===true &&
  ($note['id']??0)>0,'private note stored with true owner');
$own=PLDR_Reading::items($editionid,$admin);
f12_read_assert(is_array($own)&&count($own)===1 &&
  ($own[0]['note_text']??'')==='SYNTHETIC PRIVATE NOTE — DO NOT PUBLISH',
 'owner can retrieve own single private note');
wp_set_current_user($subscriber);
$other=PLDR_Reading::items($editionid,$subscriber);
f12_read_assert(is_array($other)&&count($other)===0,
 'second logged-in user cannot enumerate first user private note');
$other_state=PLDR_Reading::state($editionid,$subscriber);
f12_read_assert(($other_state['page']??0)===1 &&
  !isset($other_state['updated_at']),
 'other user cannot read first user progress or revision');
$doc_table=PLDR_Core::table('documents');
f12_read_assert(1===$wpdb->update($doc_table,['status'=>'restricted'],['id'=>$docid]),
 'test-only document rights withdrawn');
f12_read_assert(!PLDR_Access::can_access_edition($editionid,'read',$subscriber),
 'restricted doc denies subscriber even with account');
f12_read_assert(!PLDR_Access::can_access_edition($editionid,'read',-1),
 'restricted doc denies anonymous user');
f12_read_assert(PLDR_Reading::items($editionid,$subscriber)===[],
 'revoked reader receives no private note API data');
$denied=PLDR_Reading::save_progress($editionid,4,$subscriber,'');
f12_read_assert(is_wp_error($denied)&&$denied->get_error_code()==='pldr_progress_forbidden',
 'rights revocation denies private progress mutation');
echo "FILE12 NATIVE MYSQL READING ACL/CAS/PRIVACY PASS; synthetic metadata only, NO actual PDF and NO Hostinger acceptance.\n";
