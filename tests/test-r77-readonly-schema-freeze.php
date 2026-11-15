<?php
/**
 * R77 RED-first: the authorized Hostinger read-only collector must inventory
 * all independent File12 schema-version/checkpoint families WITHOUT disclosing
 * private option payloads, lock tokens, PDF bytes or patient records.
 * CI scans source only; NEVER invokes this script against Hostinger.
 */
declare(strict_types=1);
$root=dirname(__DIR__);
$src=@file_get_contents($root.'/scripts/file12-live-reality-freeze-readonly.sh');
if(!is_string($src)) {fwrite(STDERR,"R77 freeze collector missing\n");exit(1);}
$required=[
 'pldr_db_version','pldr_contract_version','pldr_future_db_version',
 'pldr_future_schema_revision','pldr_schema_corrections_revision',
 'pldr_migration_lock','pldr_future_migration_lock',
 'pldr_legacy_migration_state','pldr_schema_error',
 'pldr_future_schema_error','pldr_deferred_upgrade'
];
foreach($required as $key){
 if(!str_contains($src,$key)){
   fwrite(STDERR,"R77 source ledger blind to ".$key."\n");exit(1);
 }
}
foreach(['--skip-plugins','--skip-themes','information_schema.TABLES',
 'information_schema.COLUMNS','information_schema.STATISTICS',
 'PLUGIN_ROOT=','sha256sum','[REDACTED_OR_INVALID]'] as $marker){
 if(!str_contains($src,$marker)){fwrite(STDERR,"R77 missing safe evidence boundary ".$marker."\n");exit(1);}
}
foreach(['wp plugin activate','wp plugin update','wp db import','wp db repair',
 'wp option update','wp option delete','UPDATE ','DELETE FROM ',
 'INSERT INTO ','DROP TABLE ','TRUNCATE TABLE '] as $forbidden){
 if(str_contains($src,$forbidden)){fwrite(STDERR,"R77 unsafe mutation ".$forbidden."\n");exit(1);}
}
echo "R77 read-only full schema/migration-source inventory PASS; no Hostinger execution.\n";
