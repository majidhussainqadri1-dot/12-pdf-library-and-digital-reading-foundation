<?php
/**
 * File 12 R27: twenty distinct source-domain/plan-to-code regression passes.
 * Static presence is not equivalent to live staging/browser verification.
 */
$base=dirname(__DIR__).'/pdf-library-foundation-12/';
$loaded=array();
$get=static function(string $path)use($base,&$loaded):string{
    if(!isset($loaded[$path])){
        $full=$base.$path;
        if(!is_file($full)){fwrite(STDERR,"R27 missing code owner: {$path}\n");exit(1);}
        $loaded[$path]=(string)file_get_contents($full);
    }
    return $loaded[$path];
};
$rounds=array(
  1=>array('Domain ownership',array(array('includes/class-pldr-core.php','class PLDR_Core'),array('includes/class-pldr-schema.php','class PLDR_Schema'),array('includes/class-pldr-rights.php','class PLDR_Rights'))),
  2=>array('Document taxonomy',array(array('includes/class-pldr-core.php','DOCUMENT_TYPES'),array('includes/class-pldr-core.php','CATEGORIES'))),
  3=>array('Bibliographic projections',array(array('includes/class-pldr-core.php','public_document_dto'),array('includes/class-pldr-schema.php','editions'))),
  4=>array('Genuine PDF ingest',array(array('includes/class-pldr-ingest.php','validate_pdf'),array('includes/class-pldr-ingest.php','scan'),array('includes/class-pldr-ingest.php','rights'))),
  5=>array('Rights and takedown',array(array('includes/class-pldr-rights.php','appeal'),array('includes/class-pldr-rights-policy.php','rights'),array('includes/class-pldr-access.php','revoke_document'))),
  6=>array('Authenticated encryption',array(array('includes/class-pldr-crypto.php','encrypt_file'),array('includes/class-pldr-crypto.php','stream_range'),array('includes/class-pldr-crypto.php','verify_file'))),
  7=>array('Private storage path',array(array('includes/class-pldr-storage.php','Invalid private object name'),array('includes/class-pldr-storage.php','function delete'))),
  8=>array('Anonymous signed grant',array(array('includes/class-pldr-access.php','$grant_user='),array('includes/class-pldr-access.php','$user_id = $user_id < 0 ? -1'),array('includes/class-pldr-core.php','if ($user_id < 0) return false;'))),
  9=>array('REST IDOR and idempotency',array(array('includes/class-pldr-rest.php','owned_reading_item_before_delete'),array('includes/class-pldr-rest.php','idempotent'),array('includes/class-pldr-rest.php','delivery_edition_or_unavailable'))),
 10=>array('Reader manifest preflight',array(array('includes/class-pldr-rest.php','reader_manifest'),array('includes/class-pldr-rest.php','accessibility_metadata'))),
 11=>array('Catalog and OCR cursors',array(array('includes/class-pldr-reader.php','decode_catalog_cursor'),array('includes/class-pldr-reader.php','decode_ocr_cursor'))),
 12=>array('Private progress and notes',array(array('includes/class-pldr-reader.php','save_progress'),array('includes/class-pldr-reader.php','reading_items'),array('includes/class-pldr-privacy.php','erase'))),
 13=>array('OCR correction layer',array(array('includes/class-pldr-future-data.php','ocr_corrections'),array('includes/class-pldr-future-data.php','derived_correction_layer'))),
 14=>array('Future Digital Reading 24',array(array('includes/class-pldr-future.php','F12-FUT-001'),array('includes/class-pldr-future.php','F12-FUT-024'))),
 15=>array('Encrypted offline/reconnect',array(array('assets/future-reader-vault.js','indexedDB'),array('assets/future-reader-vault.js','crypto'),array('includes/class-pldr-future-rest.php','offline_authorization'))),
 16=>array('File 05/06/16 companion boundaries',array(array('includes/class-pldr-future-corpus.php','pldr_ai_corpus_allowed'),array('includes/class-pldr-future-context.php','File 05'),array('includes/class-pldr-future-context.php','File 06'))),
 17=>array('IIIF access rights',array(array('includes/class-pldr-future-iiif.php','PLDR_Access::issue_token'),array('includes/class-pldr-future-iiif.php','PLDR_Core::policy'))),
 18=>array('Accessibility and human review',array(array('includes/class-pldr-future-a11y.php','verify'),array('includes/class-pldr-future-a11y.php','inspect'))),
 19=>array('Privacy / migration / outbox',array(array('includes/class-pldr-schema.php','legacy_migration'),array('includes/class-pldr-r21-outbox.php','dispatch'),array('includes/class-pldr-privacy.php','legal_hold'))),
 20=>array('Release & cross-file registration',array(array('includes/class-pldr-plugin.php','core_ready'),array('includes/class-pldr-rights.php','spf_register_route'),array('includes/class-pldr-rights.php','spui_register_component_provider')))
);
$done=0;$checks=0;
foreach($rounds as $number=>$round){
    [$name,$assertions]=$round;
    foreach($assertions as [$path,$needle]){
        if(false===strpos($get($path),$needle)){
            fwrite(STDERR,"R27 round {$number} ({$name}): missing {$needle} in {$path}\n");
            exit(1);
        }
        $checks++;
    }
    $done++;
}
$rest=$get('includes/class-pldr-rest.php');
$readerStart=strpos($rest,'public static function reader_manifest(');
$readerEnd=strpos($rest,'private static function accessibility_metadata(', $readerStart);
if(false===$readerStart||false===$readerEnd)exit(1);
$reader=substr($rest,$readerStart,$readerEnd-$readerStart);
$readerGrant=strpos($reader,'$grant=PLDR_Access::issue_token(');
if(false===$readerGrant)exit(1);
foreach(array('PLDR_Core::policy(', 'PLDR_Reading::state(', 'accessibility_metadata(', '$thumb_rows=$wpdb->get_results(') as $preflight){
    $at=strpos($reader,$preflight);
    if(false===$at||$at>=$readerGrant){fwrite(STDERR,"R27 reader grant issued before {$preflight} check.\n");exit(1);}
}
$iiif=$get('includes/class-pldr-future-iiif.php');
$policyAt=strpos($iiif,'$policy=PLDR_Core::policy(');
$grantAt=strpos($iiif,'PLDR_Access::issue_token(');
if(false===$policyAt||false===$grantAt||$grantAt<=$policyAt){
    fwrite(STDERR,"R27 IIIF grant issued before access policy check.\n");exit(1);
}
echo "File 12 R27: {$done}/20 source-plan rounds, {$checks} anchors, reader/IIIF preflight invariants: PASS\n";
