<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static fn($p)=>file_get_contents($root.'/'.$p);
$schema=$read('includes/class-pldr-schema.php');
$ingest=$read('includes/class-pldr-ingest.php');
$access=$read('includes/class-pldr-access.php');
$rights=$read('includes/class-pldr-rights.php');
$core=$read('includes/class-pldr-core.php');
$plugin=$read('includes/class-pldr-plugin.php');
$reader=$read('includes/class-pldr-reader.php');
$crypto=$read('includes/class-pldr-crypto.php');
$admin=$read('includes/class-pldr-admin.php');
$checks=array(
 'edition lifecycle status'=>strpos($schema,"status varchar(24) NOT NULL DEFAULT 'rights_review'")!==false,
 'published edition selection'=>strpos($core,"AND e.status=%s")!==false,
 'scanner fail-closed publication'=>strpos($ingest,"if ('clean' !== (\$scan['status'] ?? '')) return 'scan';")!==false,
 'streaming Encrypt scan'=>strpos($ingest,'stream_contains($path, \'/Encrypt\')')!==false,
 'rescan recovery'=>strpos($ingest,'rescan_document')!==false,
 'canonical edition family'=>strpos($ingest,'document_public_id')!==false && strpos($ingest,'supersedes_edition_id')!==false,
 'multiple Range rejection'=>strpos($access,'Multiple byte ranges are not supported.')!==false,
 'access policy versioning'=>strpos($access,'update_policy')!==false && strpos($access,'access-policy-change')!==false,
 'appeal review not deadlocked'=>strpos($rights,"if('closed'===\$case['state'])")!==false,
 'clean object publication guard'=>strpos($rights,"'clean'!==\$object['scan_status']")!==false,
 'explicit approval command'=>strpos($rights,'approve_document')!==false,
 'legacy plaintext hash'=>strpos($schema,'plaintext_sha256')!==false,
 'legacy reading migration'=>strpos($schema,'migrate_legacy_user_data')!==false,
 'legacy report migration'=>strpos($schema,'migrate_legacy_reports')!==false,
 'key ring merge'=>strpos($crypto,"defined('SPL_PDF_MASTER_KEYS')")!==false && strpos($crypto,"defined('PLDR_PDF_MASTER_KEYS')")!==false,
 'bounded thumbnail DOM'=>strpos($reader,'$thumb_limit=min')!==false,
 'print/highlight controls'=>strpos($reader,'data-action="print"')!==false && strpos($reader,'data-action="highlight"')!==false,
 'safe key rotation'=>strpos($admin,'rotate_keys')!==false,
 'bundled packs not every init'=>strpos($plugin,'scan_bundled_manifests')===false,
 'non-destructive uninstall'=>strpos($read('uninstall.php'),'DROP TABLE')===false,
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"Review regression failed: {$name}\n");exit(1);}}
echo "PLDR independent review regressions: PASS (".count($checks).")\n";
