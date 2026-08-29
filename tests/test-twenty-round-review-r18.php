<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static function(string $path) use($root):string{$full=$root.'/'.$path;if(!is_file($full)){fwrite(STDERR,"Missing R18 target: {$path}\n");exit(1);}return (string)file_get_contents($full);};
$must=static function(string $src,string $needle,string $why):void{if(false===strpos($src,$needle)){fwrite(STDERR,"R18 regression: {$why}\n");exit(1);}};

$rights=$read('includes/class-pldr-rights.php');
$outbox=$read('includes/class-pldr-r21-outbox.php');
$access=$read('includes/class-pldr-access.php');
$rest=$read('includes/class-pldr-rest.php');
$core=$read('includes/class-pldr-core.php');
$frest=$read('includes/class-pldr-future-rest.php');
$reader=$read('includes/class-pldr-reader.php');
$insights=$read('includes/class-pldr-future-insights.php');
$shelves=$read('includes/class-pldr-future-shelves.php');
$data=$read('includes/class-pldr-future-data.php');
$fingerprint=$read('includes/class-pldr-future-fingerprint.php');
$admin=$read('includes/class-pldr-admin.php');
$privacy=$read('includes/class-pldr-privacy.php');

// Round 1.
$must($rights,'pldr_case_precondition','rights decision expected_version precondition missing.');
$must($rights,'if($expected_version<1)','rights decision missing-version guard missing.');
// Round 2.
$must($rights,'pldr_approve_precondition','publication approval expected_version precondition missing.');
$must($rights,'pldr_approve_document_read','publication document read failure is not explicit.');
$must($rights,'pldr_approve_edition_read','publication edition read failure is not explicit.');
$must($rights,'pldr_approve_object_read','publication object read failure is not explicit.');
// Round 3.
$must($access,'rights_expiry_invalid','malformed rights-expiry fail-closed marker missing.');
$must($access,'$rights_ts<=time()&&!$curator','request-time rights-expiry enforcement missing.');
// Round 4.
$must($rest,'self::idempotent($request,\'reader-access\'','reader-access durable grant is not idempotent.');
// Round 5.
$must($rest,'self::idempotent($request,\'download-session\'','download-session durable grant is not idempotent.');
// Round 6.
$must($core,'public static function consume_mutation_rate','shared mutation rate limiter missing.');
$must($rest,'consume_mutation_rate(\'core-\'.$route','core mutation wrapper does not enforce the shared rate limiter.');
$must($core,'pldr_mutation_rate_limit','core mutation 429 path missing.');
// Round 7.
$must($frest,'$rate=PLDR_Core::consume_mutation_rate($route,$actor,600)','Future-24 mutation wrapper lacks shared abuse ceiling.');
// Round 8.
$must($reader,'pldr_progress_access_read','private reading-state authorization DB failure is not fail-visible.');
// Round 9.
$must($insights,'pldr_insight_access_read','reading insight authorization DB failure can still become a partial aggregate.');
$must($insights,'no partial aggregate was returned','reading-insight partial-result rejection disclosure missing.');
// Round 10.
$must($insights,'pldr_insight_completion_access','completion authorization DB failure can still undercount silently.');
// Round 11.
$must($shelves,'Shelf rename requires the exact expected shelf version.','shelf rename client precondition missing.');
$must($frest,'absint($b[\'expected_version\']??0)','Future shelf mutations do not accept expected_version.');
// Round 12.
$must($shelves,'Shelf deletion requires the exact expected shelf version.','shelf deletion client precondition missing.');
// Round 13.
$must($shelves,'membership insertion was rolled back','shelf membership add is not version-CAS atomic.');
$must($shelves,'membership removal was rolled back','shelf membership remove is not version-CAS atomic.');
$must($shelves,'public static function add(int $shelf_id,int $edition_id,int $expected_version=0)','shelf add does not carry expected_version.');
$must($shelves,'public static function remove_item(int $shelf_id,int $edition_id,int $expected_version=0)','shelf remove-item does not carry expected_version.');
// Round 14.
$must($reader,'encode_catalog_cursor','signed catalog cursor encoder missing.');
$must($reader,'decode_catalog_cursor','signed catalog cursor decoder missing.');
$must($reader,'pldr_catalog_cursor_required','deep legacy traversal is not forced onto the cursor path.');
$must($reader,'ORDER BY d.updated_at DESC,d.id DESC LIMIT %d','catalog keyset ordering/bound missing.');
$must($rest,'\'cursor\'=>array(\'sanitize_callback\'=>\'sanitize_text_field\')','catalog REST cursor argument missing.');
$must($reader,'pldr_catalog_query_long','catalog normalized query length bound missing.');
// Round 15.
$must($data,'consume_provider_rate(\'reflow\'','reflow provider rate limit missing.');
$must($data,'pldr_provider_rate_limit','Future provider 429 path missing.');
// Round 16.
$must($data,'consume_provider_rate(\'outline\'','outline provider rate limit missing.');
// Round 17.
$must($fingerprint,'pldr_fingerprint_scan_truncated','fingerprint source truncation is not fail-visible.');
$must($fingerprint,'pldr_fingerprint_results_truncated','fingerprint candidate-result truncation is not fail-visible.');
$must($fingerprint,'LIMIT 1001','fingerprint truncation probe is missing.');
// Round 18.
$must($access,'$batch=500','token/idempotency cleanup is not bounded.');
$must($rights,'ORDER BY e.rights_expires_at ASC LIMIT 100','rights-expiry job is not bounded.');
$must($admin,'$limit=100','search-index repair batch bound missing.');
$must($admin,'pldr_search_repair_state','search-index repair is not resumable.');
$must($outbox,'outbox_dead_lettered','corrupt outbox payloads are not quarantined/dead-lettered.');
$must($outbox,'\'invalid-payload-json\'','corrupt outbox payload dead-letter marker missing.');
// Round 19.
$must($admin,'cas_persistence','integrity sampling CAS persistence disclosure missing.');
$must($admin,'integrity_verify_reconcile_failed','integrity verification stale-state reconciliation missing.');
$must($admin,'integrity_quarantine_reconcile_failed','integrity quarantine stale-state reconciliation missing.');
$must($admin,'AND object_status=%s AND storage_name=%s AND key_id=%s AND encrypted_sha256=%s','integrity persistence is not bound to sampled object state.');
// Round 20.
$must($privacy,'pldr-future-shelf-items','Smart Shelf membership is missing from privacy export.');
$must($privacy,'subject_ref','erasure completion audit still records raw subject ID.');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-12-R18.md';
if(!is_file($doc)){fwrite(STDERR,"R18 review record missing.\n");exit(1);}
$record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R18 record missing Round {$i}.");
$must($record,'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20**.','R18 defect-round summary missing.');
$must($record,'Clean rounds: **none**.','R18 clean-round summary missing.');
$must($record,'4b403768940cceaed99ab1fb98248bb5c9d457f8','R18 source-review closure SHA missing.');

echo "PLDR eighteenth fresh twenty-round corrective review contract: PASS\n";
