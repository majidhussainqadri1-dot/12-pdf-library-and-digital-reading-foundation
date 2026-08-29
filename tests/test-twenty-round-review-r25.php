<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static function(string $path)use($root):string{$full=$root.'/'.$path;if(!is_file($full)){fwrite(STDERR,"R25 missing target: {$path}\n");exit(1);}return (string)file_get_contents($full);};
$must=static function(string $haystack,string $needle,string $why):void{if(false===strpos($haystack,$needle)){fwrite(STDERR,"R25 regression: {$why}\n");exit(1);}};
$forbid=static function(string $haystack,string $needle,string $why):void{if(false!==strpos($haystack,$needle)){fwrite(STDERR,"R25 regression: {$why}\n");exit(1);}};
$access=$read('includes/class-pldr-access.php');
$storage=$read('includes/class-pldr-storage.php');
$admin=$read('includes/class-pldr-admin.php');
$rights=$read('includes/class-pldr-rights.php');
$reader=$read('includes/class-pldr-reader.php');
$a11y=$read('includes/class-pldr-future-a11y.php');
$outbox=$read('includes/class-pldr-r21-outbox.php');
$main=$read('pdf-library-digital-reading.php');

$must($access,"\$grant_user=(int)\$row['user_id']>0?(int)\$row['user_id']:-1",'public delivery grant does not preserve anonymous reauthorization semantics');
$must($access,'$user_id = $user_id < 0 ? 0 : ($user_id ?: get_current_user_id());','explicit anonymous authorization sentinel missing');
$must($storage,'$raw !== $name','path-like private storage metadata is still silently canonicalized');
$must($storage,'public static function delete(string $path): bool','storage deletion outcome is not observable');
$must($admin,"'old_ciphertext_deleted'=>\$old_deleted",'key rotation does not surface superseded-ciphertext cleanup');
$must($rights,'rights_expiry_transition_failed','rights expiry transition failure is not fail-visible');
$must($rights,"'continuation_scheduled'",'rights expiry reconciliation scheduling evidence missing');
$must($reader,'ORDER BY d.created_at DESC,d.id DESC LIMIT %d','catalog keyset is not bound to immutable creation ordering');
$must($reader,"'c_at'=>\$created_at",'catalog cursor immutable boundary missing');
$must($reader,'time()-1800','catalog cursor bounded lifetime missing');
$must($a11y,'pldr_a11y_verify_refresh_required','human verification does not require a stored reviewed assessment');
$must($a11y,'verification_used_stored_assessment','stored-assessment verification disclosure missing');
$forbid($a11y,'$report=self::inspect($edition_id,true);','human verification still refreshes provider evidence inside verify');
$must($outbox,'public static function dispatch():array','canonical outbox dispatcher does not return execution evidence');
$must($outbox,'$persisted=1===$retry','outbox retry persistence still accepts a zero-row lease update as success');
$must($admin,"array_merge(array('operation'=>'outbox','canonical_guard'=>'R21'),\$result)",'repair still fabricates outbox success');
$must($admin,"array('search_text'=>\$text)",'derived search-index repair still lacks non-semantic update path');
$forbid($admin,"array('search_text'=>\$text,'updated_at'=>PLDR_Core::now())",'derived search-index repair still rewrites canonical document updated_at');
$must($access,'public static function cleanup_tokens(): array','token cleanup does not return truthful bounded outcome');
$must($admin,"array_merge(array('operation'=>'tokens'),\$result)",'token repair still fabricates success');
$must($main,'Version: 1.1.0-rc.4','R25 release-candidate marker missing');
$must($main,"define('PLDR_VERSION', '1.1.0-rc.4')",'R25 runtime release-candidate constant missing');
$must($main,"define('PLDR_DB_VERSION', '1.1.0')",'R25 unexpectedly changed DB version');
$must($main,"define('PLDR_CONTRACT_VERSION', '1.1.0')",'R25 unexpectedly changed integration contract version');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-29-R25.md';
if(!is_file($doc)){fwrite(STDERR,"R25 review record missing.\n");exit(1);}
$record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R25 review record missing Round {$i}");
$must($record,'First-ten defect rounds: **4, 6, 7, 9**.','R25 first-ten defect accounting missing');
$must($record,'Final defect rounds: **4, 6, 7, 9, 12, 14, 17, 20**.','R25 final defect accounting missing');
$must($record,'Final clean rounds: **1, 2, 3, 5, 8, 10, 11, 13, 15, 16, 18, 19**.','R25 clean-round accounting missing');

echo "PLDR R25 fresh twenty-round corrective-review contract: PASS\n";
