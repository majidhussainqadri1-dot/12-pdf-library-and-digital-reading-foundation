<?php
/** Source-only File12 R60 pre/post-deploy human evidence gate. NEVER deploys. */
declare(strict_types=1);
$mode=$argv[1]??'predeploy';
if(!in_array($mode,array('predeploy','postdeploy'),true)){fwrite(STDERR,"Usage: php scripts/file12-release-no-go.php predeploy|postdeploy\n");exit(64);}
$root=dirname(__DIR__);
$tables=array(
 array('file'=>'docs/R57-FILE12-V121-39-STAGING-GATES.csv','stage'=>'status','live'=>null),
 array('file'=>'docs/R58-FILE12-V121-54-REQUIREMENTS.csv','stage'=>'staging_status','live'=>'live_status'),
 array('file'=>'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv','stage'=>'staging_status','live'=>'live_status'),
 // V12.3 adds 12 modern criteria, QA-15..24 and AJ-14 alongside the original 54.
 // These 77 rows are extra evidence obligations, not 77 invented features or live PASSes.
 array('file'=>'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv','stage'=>'staging_status','live'=>'live_status')
);
// R68: release rows are a fixed, governed requirement universe. A missing,
// substituted, or duplicate ID must never disappear from the denominator.
$baseF12=array_merge(
 array_map(static fn(int $n):string=>sprintf('F12-FR-%03d',$n),range(1,19)),
 array_map(static fn(int $n):string=>sprintf('F12-NFR-%03d',$n),range(1,10)),
 array_map(static fn(int $n):string=>sprintf('F12-FUT-%03d',$n),range(1,24)),
 array('F12-CEN-01')
);
$identityRequirements=array(
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>array(
   'column'=>'test_id','ids'=>array_map(static fn(int $n):string=>sprintf('T-%02d',$n),range(1,39))),
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>array(
   'column'=>'requirement_id','ids'=>$baseF12),
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>array(
   'column'=>'sto_id','ids'=>array_map(static fn(int $n):string=>sprintf('STO-%02d',$n),range(1,10))),
 'docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'=>array(
   'column'=>'id','ids'=>array_merge(
       $baseF12,
       array_map(static fn(int $n):string=>sprintf('M%02d',$n),range(1,12)),
       array_map(static fn(int $n):string=>sprintf('QA-%02d',$n),range(15,24)),
       array('AJ-14')))
);
$blocked=array();$total=0;$ready=0;
foreach($tables as $def){
 $h=@fopen($root.'/'.$def['file'],'r');
 if($h===false){$blocked[]='missing '.$def['file'];continue;}
 $header=fgetcsv($h,0,',','"','\\');
 if(!is_array($header)||count($header)===0||count($header)!==count(array_unique($header))){
   $blocked[]='malformed or duplicated CSV columns '.$def['file'];fclose($h);continue;
 }
 $required=$identityRequirements[$def['file']]??null;
 if(!is_array($required)||!in_array($required['column'],$header,true)){
   $blocked[]='missing required identity column '.$def['file'];fclose($h);continue;
 }
 $requiredIds=array_fill_keys($required['ids'],true);
 $seenIds=array();$invalidIdentity=false;
 $n=0;$ok=0;
 while(($values=fgetcsv($h,0,',','"','\\'))!==false){
  $n++;$total++;
  if(count($values)!==count($header)){$invalidIdentity=true;continue;}
  $r=array_combine($header,$values);
  $id=trim((string)($r[$required['column']]??''));
  if($id===''||!isset($requiredIds[$id])||isset($seenIds[$id])){
    $invalidIdentity=true;continue;
  }
  $seenIds[$id]=true;
  $key=$mode==='postdeploy'&&$def['live']?$def['live']:$def['stage'];
  $isV123=($def['file']==='docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv');
  // V12.3 keeps STAGING and LIVE as two independent observed realities.
  // A postdeploy PASS cannot reuse an earlier staging receipt, environment,
  // reviewer/evidence fields or a source-only GitHub Actions success.
  $liveEvidence=($isV123&&$mode==='postdeploy');
  $proofKey=$liveEvidence?'live_evidence_id':'evidence_id';
  $reviewerKey=$liveEvidence?'live_reviewer':'reviewer';
  $environmentKey=$liveEvidence?'live_environment':'environment';
  $observedKey=$liveEvidence?'live_observed':'observed';
  if(($r[$key]??'')!=='PASS'||empty($r[$proofKey])||empty($r[$reviewerKey]))continue;
  if($liveEvidence){
    if(($r['staging_status']??'')!=='PASS'
       ||empty($r['evidence_id'])||empty($r['reviewer'])
       ||(string)$r['evidence_id']===(string)$r['live_evidence_id'])continue;
  }
  if(in_array($def['file'],array('docs/R57-FILE12-V121-39-STAGING-GATES.csv','docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'),true)){
   $environment=strtoupper(trim((string)($r[$environmentKey]??'')));
   $observed=strtoupper(trim((string)($r[$observedKey]??'')));
   if($environment===''||in_array($environment,array('HOSTINGER_STAGING_REQUIRED','UNVERIFIED','PENDING','NOT_OBSERVED'),true)
       ||$observed===''||in_array($observed,array('NOT_OBSERVED','UNVERIFIED','PENDING','NOT_RUN'),true))continue;
   if($liveEvidence){
      $stageEnv=strtoupper(trim((string)($r['environment']??'')));
      $stageObs=strtoupper(trim((string)($r['observed']??'')));
      if(in_array($stageEnv,array('','HOSTINGER_STAGING_REQUIRED','UNVERIFIED','PENDING'),true)
         ||in_array($stageObs,array('','NOT_OBSERVED','UNVERIFIED','PENDING','NOT_RUN'),true))continue;
      // Production observation must identify LIVE/PRODUCTION, not a staging target.
      if(!preg_match('/(?:LIVE|PROD)/',$environment)||str_contains($environment,'STAGING'))continue;
   }
  }
  // A recorded defect cannot become a release PASS until its exact repaired
  // scenario has a reviewed and successful re-test in this same ledger.
  if(in_array($def['file'],array('docs/R57-FILE12-V121-39-STAGING-GATES.csv','docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'),true)
      &&''!==trim((string)($r['defect_id']??''))
      &&'PASS'!==strtoupper(trim((string)($r['retest_status']??''))))continue;
  $ok++;$ready++;
 }
 fclose($h);
 $requiredCount=count($requiredIds);
 if($invalidIdentity||$n!==$requiredCount||count($seenIds)!==$requiredCount){
   // This must remain a blocking reason, including when 0/0 rows remain.
   // Never treat a truncated register as an empty set of successful gates.
   $blocked[]='invalid immutable identity/cardinality in '.$def['file']
     .' (rows='.$n.', distinct allowed='.count($seenIds).', required='.$requiredCount.')';
 }
 $blocked[]=$def['file'].': '.($n-$ok).' of '.$n.' evidence gates not proved';
}
$evidencePath=$root.'/docs/R60-RELEASE-APPROVAL-STATE.json';
$evidence=is_file($evidencePath)?json_decode(file_get_contents($evidencePath),true):null;
if(!is_array($evidence)){$blocked[]='approval-state-json missing or invalid';$evidence=array();}
$must=array('current_live_freeze','staging_parity','backup_restore','rollback_rehearsal',
 'founder_signoff','rights_cutover','file26_book_pack_contract','cf04_owner_ready');
if($mode==='postdeploy'){
 $must=array_merge($must,array('deployment_parity','migration_state','live_retest'));
}
foreach($must as $field){
 if(($evidence['status'][$field]??'')!=='PASS'||empty($evidence['proofs'][$field])){
   $blocked[]='missing independent proof: '.$field;
 }
}
if(!preg_match('/^[0-9a-f]{40}$/i',(string)($evidence['release_candidate_git_sha']??''))){
 $blocked[]='missing exact release candidate Git SHA';
}
if($mode==='postdeploy'&&!preg_match('/^[0-9a-f]{64}$/i',(string)($evidence['deployed_package_sha256']??''))){
 $blocked[]='missing deployed package SHA256';
}
$gateFailure=count(array_filter($blocked,static fn($x)=>!preg_match('/:\\s*0 of \\d+ evidence gates not proved$/',$x)));
if($gateFailure){
 echo "FILE12 ".strtoupper($mode)." NO_GO; ".$ready." of ".$total." row controls with reviewed evidence.\n";
 foreach($blocked as $reason){echo " - ",$reason,"\n";}
 echo "No deployment or DB change is performed by this gate.\n";
 exit(2);
}
echo "FILE12 ".strtoupper($mode)." SOURCE_LEDGER_GO; all recorded proofs present, still requires authorized human validation.\n";
exit(0);
