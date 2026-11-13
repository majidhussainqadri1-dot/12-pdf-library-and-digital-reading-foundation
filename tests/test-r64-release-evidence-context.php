<?php
/** R64 RED-first: release gate must reject fake PASS rows with NO OBSERVATION and placeholder environment. */
declare(strict_types=1);
$root=dirname(__DIR__);
$tmp=sys_get_temp_dir().'/pldr-r64-'.bin2hex(random_bytes(8));
mkdir($tmp.'/scripts',0700,true);mkdir($tmp.'/docs',0700,true);
$script='scripts/file12-release-no-go.php';
copy($root.'/'.$script,$tmp.'/'.$script);
$source=array(
    'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>'status',
    'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>'staging_status',
    'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>'staging_status'
);
foreach($source as $path=>$key){
    $in=fopen($root.'/'.$path,'rb');$out=fopen($tmp.'/'.$path,'wb');
    if(!$in||!$out){fwrite(STDERR,"R64 setup failed copying evidence ledger\n");exit(1);}
    $header=fgetcsv($in,0,',','"','\\');
    fputcsv($out,$header,',','"','\\');
    $keys=array_flip($header);
    while(($row=fgetcsv($in,0,',','"','\\'))!==false){
        if(count($row)!==count($header)){fwrite(STDERR,"R64 malformed source ledger\n");exit(1);}
        $row[$keys[$key]]='PASS';
        if(isset($keys['live_status']))$row[$keys['live_status']]='PASS';
        $row[$keys['evidence_id']]='EV-FABRICATED-00001';
        $row[$keys['reviewer']]='fake-reviewer';
        // Deliberately leave R57.environment=HOSTINGER_STAGING_REQUIRED,
        // R57.observed=NOT_OBSERVED and retest_status=NOT_RUN untouched.
        fputcsv($out,$row,',','"','\\');
    }
    fclose($in);fclose($out);
}
$approval=json_decode(file_get_contents($root.'/docs/R60-RELEASE-APPROVAL-STATE.json'),true);
foreach($approval['status'] as $field=>$_){$approval['status'][$field]='PASS';$approval['proofs'][$field]='FABRICATED-PROOF';}
$approval['release_candidate_git_sha']=str_repeat('a',40);
$approval['deployed_package_sha256']=str_repeat('b',64);
file_put_contents($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json',json_encode($approval));
$failures=array();$checks=0;
foreach(array('predeploy','postdeploy') as $mode){
    $lines=array();$exit=0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tmp.'/'.$script).' '.escapeshellarg($mode).' 2>&1',$lines,$exit);
    $text=implode("\n",$lines);
    $checks++;
    if($exit!==2||!str_contains($text,'NO_GO')||!str_contains($text,'39 evidence gates not proved')){
        $failures[]="R64 FAIL: $mode accepted counterfeit PASS while staging environment/observation remained unset: status=$exit; output=".substr($text,0,280);
    }
}
foreach(array_keys($source) as $p)unlink($tmp.'/'.$p);
unlink($tmp.'/'.$script);
unlink($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json');
rmdir($tmp.'/scripts');rmdir($tmp.'/docs');rmdir($tmp);
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "R64 release evidence context integrity: $checks fake-PASS pre/post deployment modes denied.\n";
