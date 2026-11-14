<?php
/**
 * R67 RED-first: POSTDEPLOY cannot reuse STAGING proof to claim 77 LIVE V12.3 gates.
 * All sources, users, evidence labels and SHA fields below are SYNTHETIC TEST FIXTURES.
 */
declare(strict_types=1);
$root=dirname(__DIR__);
$tmp=sys_get_temp_dir().'/pldr-v123-r67-'.bin2hex(random_bytes(8));
mkdir($tmp.'/docs',0700,true);mkdir($tmp.'/scripts',0700,true);
copy($root.'/scripts/file12-release-no-go.php',$tmp.'/scripts/file12-release-no-go.php');
$ledgers=[
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>'status',
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>'staging_status',
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>'staging_status',
 'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'=>'staging_status'
];
foreach($ledgers as $p=>$stage){
  $in=fopen($root.'/'.$p,'rb');$out=fopen($tmp.'/'.$p,'wb');
  if(!$in||!$out)throw new RuntimeException('Fixture open failed');
  $header=fgetcsv($in,0,',','"','\\');$map=array_flip($header);
  fputcsv($out,$header,',','"','\\');
  while(($row=fgetcsv($in,0,',','"','\\'))!==false){
    if(count($row)!==count($header))throw new RuntimeException('Malformed fixture source');
    $row[$map[$stage]]='PASS';
    if(isset($map['live_status']))$row[$map['live_status']]='PASS';
    $row[$map['evidence_id']]='STAGING_ONLY_SYNTHETIC';
    $row[$map['reviewer']]='SYNTHETIC_STAGE_REVIEWER';
    if(isset($map['environment']))$row[$map['environment']]='HOSTINGER_STAGING_SYNTHETIC';
    if(isset($map['observed']))$row[$map['observed']]='OBSERVED_IN_SYNTHETIC_STAGE';
    if(isset($map['retest_status']))$row[$map['retest_status']]='PASS';
    // New live-specific proof fields, if present, stay UNVERIFIED/EMPTY.
    fputcsv($out,$row,',','"','\\');
  }
  fclose($in);fclose($out);
}
$approval=json_decode(file_get_contents($root.'/docs/R60-RELEASE-APPROVAL-STATE.json'),true);
foreach($approval['status'] as $k=>$_){$approval['status'][$k]='PASS';$approval['proofs'][$k]='SYNTHETIC_DO_NOT_DEPLOY';}
$approval['release_candidate_git_sha']=str_repeat('a',40);
$approval['deployed_package_sha256']=str_repeat('b',64);
file_put_contents($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json',json_encode($approval));
$out=[];$status=0;
exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tmp.'/scripts/file12-release-no-go.php').' postdeploy 2>&1',$out,$status);
$msg=implode("\n",$out);
foreach(array_keys($ledgers) as $p)unlink($tmp.'/'.$p);
unlink($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json');
unlink($tmp.'/scripts/file12-release-no-go.php');
rmdir($tmp.'/docs');rmdir($tmp.'/scripts');rmdir($tmp);
if($status!==2||!str_contains($msg,'77 evidence gates not proved')||!str_contains($msg,'NO_GO')){
 fwrite(STDERR,"R67 LIVE/STAGE conflation: postdeploy accepted STAGING-only proof; exit=$status; output=".substr($msg,0,300)."\n");
 exit(1);
}
echo "R67 independent LIVE proof requirement: staging-only 77 V12.3 rows denied at postdeploy (no deploy).\n";
