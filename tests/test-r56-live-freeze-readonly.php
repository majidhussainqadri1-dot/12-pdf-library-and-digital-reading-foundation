<?php
/** R56 source-only limits; does not execute WP-CLI on a real host. */
declare(strict_types=1);
$src=file_get_contents(dirname(__DIR__).'/scripts/file12-live-reality-freeze-readonly.sh');
if(!is_string($src)){fwrite(STDERR,"Missing read-only freeze script\n");exit(1);}
$n=0;
foreach(array('set -euo pipefail','--skip-plugins','--skip-themes','active_plugins','pldr_db_version','information_schema.TABLES','information_schema.COLUMNS','information_schema.STATISTICS','sha256sum','PLUGIN_ROOT=','lock tokens','row content') as $term){
    $n++;if(!str_contains($src,$term)){fwrite(STDERR,"Missing evidence guard: $term\n");exit(1);}
}
foreach(array('wp plugin activate','wp plugin update','wp db import','wp db repair','wp db reset','wp option update','wp option delete','UPDATE ','DELETE FROM ','INSERT INTO ','DROP TABLE ','TRUNCATE TABLE ') as $term){
    $n++;if(str_contains($src,$term)){fwrite(STDERR,"Unsafe mutation operation: $term\n");exit(1);}
}
echo "R56 readonly freeze source assertions: $n PASS.\n";
