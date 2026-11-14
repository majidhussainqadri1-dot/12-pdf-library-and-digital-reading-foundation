<?php
/**
 * R67 POSITIVE source-ledger fixture: the V12.3 gate is not permanently NO_GO.
 * This fixture does NOT verify signatures, real reviewer identity or Hostinger.
 * An exit 0 means SOURCE_LEDGER_GO for synthetic test inputs, never deployment GO.
 */
declare(strict_types=1);
$root=dirname(__DIR__);
$tmp=sys_get_temp_dir().'/pldr-v123-r67-pos-'.bin2hex(random_bytes(8));
mkdir($tmp.'/docs',0700,true);mkdir($tmp.'/scripts',0700,true);
copy($root.'/scripts/file12-release-no-go.php',$tmp.'/scripts/file12-release-no-go.php');
$files=[
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>'status',
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>'staging_status',
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>'staging_status',
 'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'=>'staging_status'
];
foreach($files as $p=>$stage){
 $i=fopen($root.'/'.$p,'rb');$o=fopen($tmp.'/'.$p,'wb');
 $h=fgetcsv($i,0,',','"','\\');$k=array_flip($h);
 fputcsv($o,$h,',','"','\\');
 while(($row=fgetcsv($i,0,',','"','\\'))!==false){
  if(count($row)!==count($h))throw new RuntimeException('bad source fixture');
  $row[$k[$stage]]='PASS';
  if(isset($k['live_status']))$row[$k['live_status']]='PASS';
  $row[$k['evidence_id']]='SYNTHETIC-STAGE-RECEIPT';
  $row[$k['reviewer']]='SYNTHETIC-STAGING-REVIEWER';
  if(isset($k['environment']))$row[$k['environment']]='HOSTINGER_STAGING_FIXTURE';
  if(isset($k['observed']))$row[$k['observed']]='OBSERVED_STAGING_FIXTURE';
  if(isset($k['retest_status']))$row[$k['retest_status']]='PASS';
  if($p==='docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'){
    $row[$k['live_environment']]='HOSTINGER_PRODUCTION_FIXTURE';
    $row[$k['live_observed']]='OBSERVED_PRODUCTION_FIXTURE';
    $row[$k['live_evidence_id']]='SYNTHETIC-DISTINCT-LIVE-RECEIPT';
    $row[$k['live_reviewer']]='SYNTHETIC-LIVE-REVIEWER';
  }
  fputcsv($o,$row,',','"','\\');
 }
 fclose($i);fclose($o);
}
$approval=json_decode(file_get_contents($root.'/docs/R60-RELEASE-APPROVAL-STATE.json'),true);
foreach($approval['status'] as $k=>$_){$approval['status'][$k]='PASS';$approval['proofs'][$k]='SYNTHETIC-NOT-REAL-PROOF';}
$approval['release_candidate_git_sha']=str_repeat('a',40);
$approval['deployed_package_sha256']=str_repeat('b',64);
file_put_contents($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json',json_encode($approval));
$fail=[];
foreach(['predeploy','postdeploy'] as $mode){
 $output=[];$status=0;
 exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tmp.'/scripts/file12-release-no-go.php').' '.escapeshellarg($mode).' 2>&1',$output,$status);
 $msg=implode("\n",$output);
 if($status!==0||!str_contains($msg,'SOURCE_LEDGER_GO')||!str_contains($msg,'requires authorized human validation'))
   $fail[]=$mode.' source-ledger GO unavailable after complete SYNTHETIC fixture; exit '.$status.' '.substr($msg,0,240);
}
foreach(array_keys($files) as $p)unlink($tmp.'/'.$p);
unlink($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json');
unlink($tmp.'/scripts/file12-release-no-go.php');
rmdir($tmp.'/docs');rmdir($tmp.'/scripts');rmdir($tmp);
if($fail){fwrite(STDERR,implode("\n",$fail)."\n");exit(1);}
echo "R67 positive synthetic-only ledger schema/approval parity PASS in pre/post modes. Real deploy proof remains UNVERIFIED.\n";
