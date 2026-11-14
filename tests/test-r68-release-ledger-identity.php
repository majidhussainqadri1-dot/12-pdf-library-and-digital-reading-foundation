<?php
/**
 * R68 RED-first: source-governance release approval MUST reject shortened or
 * duplicated mandatory evidence ledgers even when every remaining row has
 * synthetic PASS values. No real source, staging or live data is touched.
 */
declare(strict_types=1);
$root=dirname(__DIR__);
$files=[
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>'status',
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>'staging_status',
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>'staging_status',
 'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'=>'staging_status'
];
$scenarios=[
 'missing_R57_required_test'=>['file'=>array_key_first($files),'kind'=>'remove'],
 'duplicate_R57_test_id'=>['file'=>array_key_first($files),'kind'=>'duplicate'],
 'missing_V123_required_criterion'=>['file'=>'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv','kind'=>'remove'],
 'duplicate_V123_criterion_id'=>['file'=>'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv','kind'=>'duplicate'],
 'empty_CF04_security_register'=>['file'=>'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv','kind'=>'empty']
];
$failures=[]; $checks=0;
foreach($scenarios as $label=>$scenario){
 $tmp=sys_get_temp_dir().'/pldr-r68-'.bin2hex(random_bytes(8));
 mkdir($tmp.'/docs',0700,true);mkdir($tmp.'/scripts',0700,true);
 copy($root.'/scripts/file12-release-no-go.php',$tmp.'/scripts/file12-release-no-go.php');
 foreach($files as $path=>$stage){
   $input=fopen($root.'/'.$path,'rb');
   $headers=fgetcsv($input,0,',','"','\\');
   if(!is_array($headers)) throw new RuntimeException('bad fixture header');
   $rows=[];$keys=array_flip($headers);
   while(($row=fgetcsv($input,0,',','"','\\'))!==false){
      if(count($row)!==count($headers)) throw new RuntimeException('bad fixture row');
      $row[$keys[$stage]]='PASS';
      if(isset($keys['live_status'])) $row[$keys['live_status']]='PASS';
      $row[$keys['evidence_id']]='R68-SYNTHETIC-STAGE-NOT-PROOF';
      $row[$keys['reviewer']]='R68-SYNTHETIC-STAGING-NOT-REVIEWER';
      if(isset($keys['environment'])) $row[$keys['environment']]='R68_HOSTINGER_STAGING_FIXTURE';
      if(isset($keys['observed'])) $row[$keys['observed']]='R68_OBSERVED_STAGING_FIXTURE';
      if(isset($keys['retest_status'])) $row[$keys['retest_status']]='PASS';
      if($path==='docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'){
         $row[$keys['live_environment']]='R68_HOSTINGER_PRODUCTION_FIXTURE';
         $row[$keys['live_observed']]='R68_OBSERVED_PRODUCTION_FIXTURE';
         $row[$keys['live_evidence_id']]='R68-SYNTHETIC-LIVE-NOT-PROOF';
         $row[$keys['live_reviewer']]='R68-SYNTHETIC-LIVE-NOT-REVIEWER';
      }
      $rows[]=$row;
   }
   fclose($input);
   if($path===$scenario['file']){
       if($scenario['kind']==='remove') array_shift($rows);
       if($scenario['kind']==='empty') $rows=[];
       if($scenario['kind']==='duplicate' && count($rows)>1){
           $idKey=$keys['test_id']??$keys['id']??null;
           if($idKey===null) throw new RuntimeException('no identity column');
           $rows[1][$idKey]=$rows[0][$idKey];
       }
   }
   $output=fopen($tmp.'/'.$path,'wb');
   fputcsv($output,$headers,',','"','\\');
   foreach($rows as $row) fputcsv($output,$row,',','"','\\');
   fclose($output);
 }
 $approval=json_decode(file_get_contents($root.'/docs/R60-RELEASE-APPROVAL-STATE.json'),true);
 foreach($approval['status'] as $k=>$_){
    $approval['status'][$k]='PASS';
    $approval['proofs'][$k]='R68-SYNTHETIC-APPROVAL-NOT-PROOF';
 }
 $approval['release_candidate_git_sha']=str_repeat('a',40);
 $approval['deployed_package_sha256']=str_repeat('b',64);
 file_put_contents($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json',json_encode($approval));
 foreach(['predeploy','postdeploy'] as $mode){
    $output=[];$status=0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tmp.'/scripts/file12-release-no-go.php').' '.escapeshellarg($mode).' 2>&1',$output,$status);
    $message=implode("\n",$output);$checks++;
    if($status!==2 || !str_contains($message,'NO_GO'))
       $failures[]=$label.' / '.$mode.' incorrectly accepted altered identity evidence: exit='.$status.' '.substr($message,0,180);
 }
 foreach(array_keys($files) as $p) unlink($tmp.'/'.$p);
 unlink($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json');
 unlink($tmp.'/scripts/file12-release-no-go.php');
 rmdir($tmp.'/docs');rmdir($tmp.'/scripts');rmdir($tmp);
}
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "R68 ledger cardinality and identity integrity: ".$checks." synthetic tampering scenarios denied (NO DEPLOY).\n";
