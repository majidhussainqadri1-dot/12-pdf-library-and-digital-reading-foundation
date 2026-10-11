<?php
/** R28: direct HTML reader must preflight current user/read policy before bearer token issuance. */
$path=dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
$src=(string)file_get_contents($path);
$start=strpos($src,'public static function reader_html(');
$end=strpos($src,'public static function reading_dashboard_html(',$start);
if(false===$start||false===$end){fwrite(STDERR,"R28 HTML reader ownership missing\n");exit(1);}
$fn=substr($src,$start,$end-$start);
$grant=strpos($fn,"\$grant = PLDR_Access::issue_token(");
if(false===$grant){fwrite(STDERR,"R28 HTML reader bearer issuance missing\n");exit(1);}
foreach(array('PLDR_Reading::state(', 'PLDR_Core::public_document_dto(', 'PLDR_Core::policy(', "if(''!==(string)\$wpdb->last_error||!\$policy)") as $gate){
    $at=strpos($fn,$gate);
    if(false===$at||$at>=$grant){fwrite(STDERR,"R28 HTML reader creates token before {$gate}\n");exit(1);}
}
$thumb=strpos($fn,'self::thumbnail_tokens(');
if(false===$thumb||$thumb<=$grant){fwrite(STDERR,"R28 HTML reader creates thumbnail grants before PDF preflight\n");exit(1);}
echo "PASS R28 HTML reader pre-grant source invariants\n";
