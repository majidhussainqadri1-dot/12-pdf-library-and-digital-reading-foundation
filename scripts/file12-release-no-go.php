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
$blocked=array();$total=0;$ready=0;
foreach($tables as $def){
 $h=@fopen($root.'/'.$def['file'],'r');
 if($h===false){$blocked[]='missing '.$def['file'];continue;}
 $header=fgetcsv($h,0,',','"','\\');
 if(!is_array($header)){$blocked[]='malformed '.$def['file'];fclose($h);continue;}
 $n=0;$ok=0;
 while(($values=fgetcsv($h,0,',','"','\\'))!==false){
  $n++;$total++;
  if(count($values)!==count($header))continue;
  $r=array_combine($header,$values);
  $key=$mode==='postdeploy'&&$def['live']?$def['live']:$def['stage'];
  if(($r[$key]??'')!=='PASS'||empty($r['evidence_id'])||empty($r['reviewer']))continue;
  // The original 39-test ledger carries an explicit staging environment and
  // observed result. A fabricated PASS must never override untouched
  // HOSTINGER_STAGING_REQUIRED / NOT_OBSERVED evidence placeholders.
  if(in_array($def['file'],array('docs/R57-FILE12-V121-39-STAGING-GATES.csv','docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'),true)){
   $environment=strtoupper(trim((string)($r['environment']??'')));
   $observed=strtoupper(trim((string)($r['observed']??'')));
   if($environment===''||in_array($environment,array('HOSTINGER_STAGING_REQUIRED','UNVERIFIED','PENDING','NOT_OBSERVED'),true)
       ||$observed===''||in_array($observed,array('NOT_OBSERVED','UNVERIFIED','PENDING','NOT_RUN'),true))continue;
  }
  // A recorded defect cannot become a release PASS until its exact repaired
  // scenario has a reviewed and successful re-test in this same ledger.
  if(in_array($def['file'],array('docs/R57-FILE12-V121-39-STAGING-GATES.csv','docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv'),true)
      &&''!==trim((string)($r['defect_id']??''))
      &&'PASS'!==strtoupper(trim((string)($r['retest_status']??''))))continue;
  $ok++;$ready++;
 }
 fclose($h);
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
