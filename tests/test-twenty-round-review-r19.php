<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static function(string $path) use($root):string{$full=$root.'/'.$path;if(!is_file($full)){fwrite(STDERR,"Missing R19 target: {$path}\n");exit(1);}return (string)file_get_contents($full);};
$must=static function(string $src,string $needle,string $why):void{if(false===strpos($src,$needle)){fwrite(STDERR,"R19 regression: {$why}\n");exit(1);}};
$mustNot=static function(string $src,string $needle,string $why):void{if(false!==strpos($src,$needle)){fwrite(STDERR,"R19 regression: {$why}\n");exit(1);}};

$core=$read('includes/class-pldr-core.php');$access=$read('includes/class-pldr-access.php');$admin=$read('includes/class-pldr-admin.php');$privacy=$read('includes/class-pldr-privacy.php');$rest=$read('includes/class-pldr-rest.php');$reader=$read('includes/class-pldr-reader.php');$rights=$read('includes/class-pldr-rights.php');$schema=$read('includes/class-pldr-schema.php');$ingest=$read('includes/class-pldr-ingest.php');
$frest=$read('includes/class-pldr-future-rest.php');$ocr=$read('includes/class-pldr-future-ocr-lab.php');$annotations=$read('includes/class-pldr-future-annotations.php');$preservation=$read('includes/class-pldr-future-preservation.php');$rooms=$read('includes/class-pldr-future-rooms.php');$future=$read('includes/class-pldr-future.php');$corpus=$read('includes/class-pldr-future-corpus.php');$shelves=$read('includes/class-pldr-future-shelves.php');
$readerjs=$read('assets/reader.js');$personaljs=$read('assets/future-reader-personal.js');$scholarjs=$read('assets/future-reader-scholar.js');

// R2.
$must($ocr,'pldr_ocr_review_precondition','OCR review exact-version precondition missing.');
$must($rest,'pldr_item_precondition','private reading-item exact-version delete precondition missing.');
// R3.
$must($access,'pldr_token_edition_read','delivery token DB-read failure is not explicit.');
$must($admin,'could not be read reliably','admin DB failure is still silently empty.');
// R5.
$must($privacy,"array('idempotency','actor_id','direct')",'idempotency actor privacy erasure missing.');
// R6.
$must($access,'ROLLBACK','access-policy atomic rollback missing.');
$must($rights,'ROLLBACK','rights atomic rollback missing.');
// R8.
$must($ingest,'expected_document_version','ingest/publication optimistic precondition missing.');
$must($ingest,'rights_review','untrusted/pending edition safety state missing.');
// R9.
$must($readerjs,'expected_version:Number(item.version||0)','browser reading-item delete version missing.');
// R10.
$must($reader,'encode_ocr_cursor','OCR signed cursor encoder missing.');
$must($readerjs,'Load more search results','OCR browser continuation missing.');
// R11.
$must($annotations,'next_after_id','annotation export keyset continuation missing.');
// R13.
$must($admin,'cas_persistence','preservation/integrity CAS persistence evidence missing.');
// R14.
$must($rooms,'pldr_reading_room_provider_compensate','reading-room provider compensation missing.');
// R15.
$must($future,"wp_schedule_single_event(time()+60,'pldr_future_cleanup')",'Future cleanup continuation scheduling missing.');
$must($future,'pldr_future_fingerprint_edition','fingerprint background retry/job missing.');
// R16.
$must($schema,'legacy_checksum_unverified','legacy unverified-checksum quarantine evidence missing.');
// R17.
$must($rights,'LIMIT 100','rights-expiry bounded batch missing.');
$must($rights,'manifest_json)>262144','content-pack metadata bound missing.');
// R18.
$must($core,'AUDIT_CONTEXT_MAX_BYTES = 16384','audit context byte bound missing.');
$must($core,'OUTBOX_PAYLOAD_MAX_BYTES = 65536','outbox payload bound missing.');
$must($admin,'pldr_future_schema_error','Future schema health evidence missing.');
// R20 — rights appeal, migration, cursors and frontend parity.
$must($rights,'pldr_appeal_precondition','rights appeal expected-version precondition missing.');
$must($rights,'FOR UPDATE','rights appeal parent serialization missing.');
$must($rights,'pldr_appeal_exists','single appeal-child protection missing.');
$must($schema,'LEGACY_INTERACTION_BATCH = 500','legacy interaction resumable batch missing.');
$must($schema,'last_legacy_id','legacy keyset checkpoint missing.');
$mustNot($schema,'LIMIT 5001','legacy >5000 hard-failure marker returned.');
$must($rest,"'cursor_supported'=>true",'private reading-item cursor response missing.');
$must($corpus,"'cursor_supported'=>true",'File 16 corpus cursor response missing.');
$must($frest,"'/future/shelves/bootstrap'",'Smart Shelf explicit bootstrap mutation missing.');
$mustNot($shelves,'public static function list():array { self::ensure_defaults','Smart Shelf GET still writes default shelves.');
$must($personaljs,'expected_version:Number(shelf.version||0)','Smart Shelf browser version precondition missing.');
$must($readerjs,'Load more private bookmarks and notes','private reading-item browser continuation missing.');
$must($scholarjs,'complete_export:true','complete paginated annotation export missing.');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-13-R19.md';if(!is_file($doc)){fwrite(STDERR,"R19 review record missing.\n");exit(1);} $record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R19 record missing Round {$i}.");
$must($record,'Defect rounds: **2, 3, 5, 6, 8, 9, 10**.','R19 first-ten defect summary missing.');
$must($record,'Defect rounds: **2, 3, 5, 6, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 19, 20**.','R19 final defect summary missing.');
$must($record,'Clean rounds: **1, 4, 7, 12**.','R19 final clean summary missing.');
$must($record,'8eb4ef4d64f59c8280874c486bad18c111bfbb5a','R19 source-review closure SHA missing.');

echo "PLDR nineteenth fresh twenty-round corrective review contract: PASS\n";
