<?php
/**
 * R69 positive native WordPress rendering: source candidate rc.44.
 * Ephemeral MySQL metadata-only public PDF candidate, WITHOUT real object
 * bytes, copyright book, CF04 storage or any actual delivery.
 */
if(!defined('ABSPATH')||getenv('CI')!=='true'||
 getenv('FILE12_CI_EPHEMERAL_DB')!=='yes'||
 home_url('/')!=='http://file12-ci.invalid/'){
 fwrite(STDERR,"R69 native markup fixture refused: not disposable CI.\n");exit(86);
}
function f12_r69_assert(bool $ok,string $description):void{
 if(!$ok){fwrite(STDERR,"R69 NATIVE FAIL: ".$description."\n");exit(1);}
 echo "PASS: ".$description."\n";
}
f12_r69_assert(defined('PLDR_VERSION')&&PLDR_VERSION==='1.1.0-rc.44',
 'R69 native test running exact 1.1.0-rc.44 candidate');
global $wpdb;
$now=PLDR_Core::now();
$admin=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);
$admin=(int)($admin[0]??0);
f12_r69_assert($admin>0,'ephemeral manager exists');
$public_id=wp_generate_uuid4();
$doc=[
 'public_id'=>$public_id,'title'=>'R69 Synthetic Public Reading Layout',
 'slug'=>'r69-public-reader','document_type'=>'book','category'=>'research',
 'language'=>'en','subjects_json'=>'[]','collections_json'=>'[]',
 'search_text'=>'r69 synthetic','status'=>'published','access_mode'=>'public',
 'created_by'=>$admin,'version'=>1,'created_at'=>$now,'updated_at'=>$now
];
f12_r69_assert($wpdb->insert(PLDR_Core::table('documents'),$doc)!==false,
 'R69 synthetic document metadata stored');
$doc_id=(int)$wpdb->insert_id;
$object=[
 'storage_name'=>'synthetic-r69-not-real.pldr','storage_scope'=>'pldr',
 'original_name'=>'synthetic-no-copyright.pdf','mime_type'=>'application/pdf',
 'byte_size'=>128,'sha256'=>hash('sha256','r69-no-real-book'),
 'encrypted_sha256'=>hash('sha256','r69-no-real-cipher'),
 'key_id'=>'fixture','format_version'=>'PLD3','scan_status'=>'clean',
 'object_status'=>'available','created_at'=>$now,
 'verified_at'=>$now,'deleted_at'=>null
];
f12_r69_assert($wpdb->insert(PLDR_Core::table('objects'),$object)!==false,
 'synthetic object metadata stored without creating any PDF bytes');
$object_id=(int)$wpdb->insert_id;
$ed=[
 'document_id'=>$doc_id,'edition_label'=>'Synthetic first',
 'isbn'=>'','publication_year'=>2026,'pages'=>3,
 'language'=>'en','author_name'=>'R69 CI fixture','translator'=>'',
 'publisher'=>'','source_name'=>'Synthetic-only metadata',
 'license_code'=>'test-not-publication','rights_basis'=>'public-domain',
 'territory'=>'test-only','rights_expires_at'=>null,'takedown_contact'=>'',
 'sha256'=>hash('sha256','r69-no-real-book'),'object_id'=>$object_id,
 'status'=>'published','supersedes_edition_id'=>null,'version'=>1,
 'created_at'=>$now,'updated_at'=>$now
];
f12_r69_assert($wpdb->insert(PLDR_Core::table('editions'),$ed)!==false,
 'synthetic edition metadata stored');
$ed_id=(int)$wpdb->insert_id;
$policy=[
 'document_id'=>$doc_id,'audience'=>'public','entitlement_key'=>'',
 'download_allowed'=>0,'print_allowed'=>0,'offline_allowed'=>0,
 'embargo_until'=>null,'version'=>1,'created_at'=>$now,'updated_at'=>$now
];
f12_r69_assert($wpdb->insert(PLDR_Core::table('access_policies'),$policy)!==false,
 'synthetic read-only PDF policy stored');
wp_set_current_user(0);
// WP-CLI does not fire the actual front-end asset enqueue hook. Register the
// canonical File12 script before asserting wp_add_inline_script metadata.
PLDR_Plugin::instance()->register_assets();
$guest=PLDR_Reader::reader_html($public_id);
f12_r69_assert(str_contains($guest,'data-pldr-reader')&&
 str_contains($guest,'Copy citation')&&
 !str_contains($guest,'data-action="bookmark"')&&
 !str_contains($guest,'data-action="note"')&&
 !str_contains($guest,'data-action="highlight"')&&
 str_contains($guest,'Sign in to save private notes'),
 'public guest reader keeps navigation/citation and truthfully hides private controls');
$js=wp_scripts();
$before=$js->registered['pldr-reader']->extra['before']??[];
$guest_config=implode("\n",(array)$before);
f12_r69_assert(str_contains($guest_config,'"canSavePrivate":false'),
 'native PHP attaches explicit boolean false for guest private-state requests');
$member=wp_create_user('file12_r69_member','disposable-ci-password','file12-r69@example.invalid');
f12_r69_assert(!is_wp_error($member)&&$member>0,'disposable member created');
$user=get_user_by('id',(int)$member);$user->set_role('subscriber');
wp_set_current_user((int)$member);
$member_html=PLDR_Reader::reader_html($public_id);
f12_r69_assert(str_contains($member_html,'data-action="bookmark"')&&
 str_contains($member_html,'data-action="note"')&&
 str_contains($member_html,'data-action="highlight"'),
 'authenticated member retains private controls in same lawful public reader');
$before=wp_scripts()->registered['pldr-reader']->extra['before']??[];
$member_config=implode("\n",(array)$before);
f12_r69_assert(str_contains($member_config,'"canSavePrivate":true'),
 'native PHP provides explicit boolean true for authenticated private reader state');
$wpdb->last_error='';
$tokens=(int)$wpdb->get_var($wpdb->prepare(
  'SELECT COUNT(*) FROM '.PLDR_Core::table('access_tokens').' WHERE edition_id=%d AND operation=%s',
  $ed_id,'read'));
f12_r69_assert($tokens>=2,'both guest and member received only synthetic read grants');
echo "R69 NATIVE GUEST/MEMBER READER PHP RENDER PASS; no actual book/delivery or Hostinger proof.\n";
