<?php
/**
 * File12 V12.3 native WordPress/MySQL negative + Book Pack lifecycle tests.
 *
 * Executed by WP-CLI eval-file in disposable GitHub Actions only.
 * NO live accounts, licensed books, medical notes, keys, or real PDFs.
 */
if (!defined('ABSPATH') || getenv('CI')!=='true' ||
    getenv('FILE12_CI_EPHEMERAL_DB')!=='yes' ||
    home_url('/')!=='http://file12-ci.invalid/') {
    fwrite(STDERR,"File12 native rights/Book Pack QA refused: not disposable CI.\n");
    exit(86);
}
function f12_pack_assert($condition, string $message):void {
    if (!$condition) {fwrite(STDERR,"FILE12 NATIVE PACK FAIL: ".$message."\n");exit(1);}
    echo "PASS: ".$message."\n";
}
require_once ABSPATH.'wp-admin/includes/plugin.php';
f12_pack_assert(is_plugin_active('pdf-library-foundation-12/pdf-library-digital-reading.php'),
    'exact library plugin active in ephemeral WordPress');
global $wpdb;
$pack_table=PLDR_Core::table('book_packs');
$outbox_table=PLDR_Core::table('outbox');
f12_pack_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.$pack_table)===0,
    'ephemeral Book Pack state starts empty');
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$admin_id=isset($admins[0])?(int)$admins[0]:0;
f12_pack_assert($admin_id>0,'ephemeral WordPress administrator role exists');
wp_set_current_user($admin_id);
f12_pack_assert(PLDR_Core::authorize('manage')===true,
    'native manager capability installed on disposable administrator');
f12_pack_assert(PLDR_Core::policy_flag('false')===0 &&
    PLDR_Core::policy_flag('true')===1 &&
    PLDR_Core::policy_flag('garbage')===null,
    'rights booleans reject PHP string truthiness');
f12_pack_assert(PLDR_Access::strict_rights_timestamp('2026-02-30')===false &&
    PLDR_Access::strict_rights_timestamp('2026-01-31 24:00:00')===false,
    'invalid calendar and time values fail closed');
$manifest=array(
    'pack_key'=>'ci-file12-synthetic-public-domain',
    'version'=>'1.0.0',
    'title'=>'Synthetic Test Metadata Only',
    'author'=>'Automated CI Fixture',
    'language'=>'en',
    'rights'=>'public-domain',
    'checksum'=>hash('sha256','no-copyright-content-and-no-pdf-binary'),
    'update_manifest'=>array('fixture'=>'test-only','revision'=>1),
    'provenance'=>'Synthetic metadata only; no book file or publication rights asserted.',
    'document_public_id'=>''
);
$invalid=$manifest;$invalid['rights']='unlicensed';
$result=PLDR_Book_Packs::register($invalid,$admin_id);
f12_pack_assert(is_wp_error($result)&&$result->get_error_code()==='pldr_pack_rights',
    'unknown licence cannot create Book Pack metadata');
$invalid=$manifest;$invalid['checksum']='untrusted-short-hash';
$result=PLDR_Book_Packs::register($invalid,$admin_id);
f12_pack_assert(is_wp_error($result)&&$result->get_error_code()==='pldr_pack_checksum',
    'non SHA256 manifest checksum is rejected');
$invalid=$manifest;$invalid['embedded_pdf']='PDF binary never belongs in manifest';
$result=PLDR_Book_Packs::register($invalid,$admin_id);
f12_pack_assert(is_wp_error($result)&&$result->get_error_code()==='pldr_pack_binary',
    'embedded PDF rejected in Book Pack metadata');
f12_pack_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.$pack_table)===0,
    'denied manager submissions leave no Book Pack rows');
$created=PLDR_Book_Packs::register($manifest,$admin_id);
f12_pack_assert(is_array($created) && ($created['status']??'')==='registered' &&
    ($created['immutable_version']??false)===true &&
    ($created['id']??0)>0,
    'native manager can register one synthetically lawful Book Pack manifest');
$pack_id=(int)$created['id'];
f12_pack_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.$pack_table)===1,
    'one actual MySQL Book Pack row created');
$event_count=(int)$wpdb->get_var($wpdb->prepare(
   'SELECT COUNT(*) FROM '.$outbox_table.' WHERE aggregate_type=%s AND aggregate_id=%d AND event_name=%s',
   'book_pack',$pack_id,'PDFBookPackRegistered.v1'
));
f12_pack_assert($event_count===1,
    'exactly one native reliable Book Pack registration outbox event persisted');
$again=PLDR_Book_Packs::register($manifest,$admin_id);
f12_pack_assert(is_array($again)&&($again['already_registered']??false)===true &&
    (int)$again['id']===$pack_id,
    'same immutable manifest is idempotent');
$changed=$manifest;$changed['title']='Different Synthetic Metadata';
$conflict=PLDR_Book_Packs::register($changed,$admin_id);
f12_pack_assert(is_wp_error($conflict)&&$conflict->get_error_code()==='pldr_pack_version_conflict',
    'same pack key/version cannot silently replace original metadata');
f12_pack_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.$pack_table)===1,
    'idempotence and version conflict created no additional rows');
f12_pack_assert((int)$wpdb->get_var($wpdb->prepare(
   'SELECT COUNT(*) FROM '.$outbox_table.' WHERE aggregate_type=%s AND aggregate_id=%d AND event_name=%s',
   'book_pack',$pack_id,'PDFBookPackRegistered.v1'
))===1,'idempotent and conflicting registration added no duplicate outbox event');
$subscriber=wp_create_user('file12_ci_reader','synthetic-not-a-secret','file12-ci-reader@example.invalid');
f12_pack_assert(!is_wp_error($subscriber) && (int)$subscriber>0,
    'ephemeral subscriber created');
$reader=get_user_by('id',(int)$subscriber);
$reader->set_role('subscriber');
wp_set_current_user((int)$subscriber);
f12_pack_assert(PLDR_Core::authorize('manage')===false,
    'subscriber cannot acquire manager capability');
$forged=PLDR_Book_Packs::register($manifest,$admin_id);
f12_pack_assert(is_wp_error($forged)&&$forged->get_error_code()==='pldr_pack_forbidden',
    'caller-supplied administrator ID does not impersonate active session');
$other=PLDR_Book_Packs::register($manifest,0);
f12_pack_assert(is_wp_error($other)&&$other->get_error_code()==='pldr_pack_forbidden',
    'subscriber cannot create Book Pack metadata');
wp_set_current_user(0);
$anon=PLDR_Book_Packs::register($manifest,$admin_id);
f12_pack_assert(is_wp_error($anon)&&$anon->get_error_code()==='pldr_pack_forbidden',
    'anonymous actor cannot impersonate manager');
f12_pack_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.$pack_table)===1,
    'unauthorized attempts did not mutate book pack state');
$protected=array(
   array('POST','/pldr/v1/ingest'),
   array('POST','/pldr/v1/book-packs'),
   array('GET','/pldr/v1/health'),
   array('POST','/pldr/v1/repair'),
   array('POST','/pldr/v1/reading/progress'),
   array('GET','/pldr/v1/reading/items'),
   array('POST','/pldr/v1/reading/items'),
   array('DELETE','/pldr/v1/reading/items/1'),
   array('POST','/pldr/v1/future/anchor'),
   array('POST','/pldr/v1/future/offline-grant'),
   array('GET','/pldr/v1/future/offline-authorization/1'),
   array('GET','/pldr/v1/future/insights'),
   array('GET','/pldr/v1/future/shelves'),
   array('POST','/pldr/v1/future/shelves/bootstrap'),
   array('POST','/pldr/v1/future/reading-room')
);
foreach($protected as $case) {
    $request=new WP_REST_Request($case[0],$case[1]);
    $response=rest_do_request($request);
    $status=$response instanceof WP_REST_Response?$response->get_status():0;
    f12_pack_assert(in_array($status,array(401,403),true),
        'anonymous private REST deny '.$case[0].' '.$case[1].' ('.$status.')');
}
foreach(array(
  '/pldr/v1/document/00000000-0000-4000-8000-000000000001',
  '/pldr/v1/reader-manifest/999999',
  '/pldr/v1/citation/999999',
  '/pldr/v1/future/iiif/00000000-0000-4000-8000-000000000001/manifest'
) as $endpoint) {
   $response=rest_do_request(new WP_REST_Request('GET',$endpoint));
   $status=$response instanceof WP_REST_Response?$response->get_status():0;
   f12_pack_assert($status>=400 && $status<500,
      'nonexistent private/public document cannot be read as real '.$endpoint.' ('.$status.')');
}
f12_pack_assert(PLDR_File26_Connector::register()===false &&
  (PLDR_File26_Connector::manifest()['entity_types']??null)===array('pdf'),
  'unapproved File26 owner is disabled; registered synthetic pack is not indexed');
echo "FILE12 DISPOSABLE NATIVE RIGHTS/BOOKPACK/REST NEGATIVE CASES PASS; NO Hostinger staging/live acceptance.\n";
