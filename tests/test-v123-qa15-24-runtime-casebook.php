<?php
/**
 * File 12 V12.3 QA-15..QA-24 staging/live test protocol integrity.
 * Every CI PASS is only structure validation, NEVER a browser/runtime PASS.
 */
declare(strict_types=1);
$path=dirname(__DIR__).'/docs/V123-FILE12-QA15-QA24-60-RUNTIME-CASES.csv';
if(!is_readable($path)){fwrite(STDERR,"Missing QA casebook\n");exit(1);}
$cols=[
 'case_id','parent_qa','plan_trace','scenario','expected_observation','owner_role_device',
 'target_environment','source_sha','deployed_sha256','physical_db_version','migration_state',
 'staging_status','staging_evidence_id','staging_reviewer','staging_timestamp_utc',
 'live_status','live_evidence_id','live_reviewer','live_timestamp_utc',
 'defect_id','fix_sha','retest_sha','retest_status','blocker'
];
$fh=fopen($path,'rb');
$headers=fgetcsv($fh,0,',','"','\\');
if($headers!==$cols){fwrite(STDERR,"QA casebook schema changed without a controlled migration\n");exit(1);}
$seen=[];$counts=[];$rows=0;
while(($values=fgetcsv($fh,0,',','"','\\'))!==false){
 if($values===[null])continue;
 if(count($values)!==count($cols)){fwrite(STDERR,"Malformed QA casebook row\n");exit(1);}
 $r=array_combine($headers,$values);$id=$r['case_id'];
 if(isset($seen[$id])||!preg_match('/^QA-(1[5-9]|2[0-4])-T0[1-6]$/D',$id)
    ||$r['parent_qa']!==substr($id,0,5)){
    fwrite(STDERR,"Duplicate/malformed QA identity ".$id."\n");exit(1);
 }
 foreach(['plan_trace','scenario','expected_observation','owner_role_device'] as $field){
    if(trim($r[$field])===''){fwrite(STDERR,"Incomplete scope ".$id." / ".$field."\n");exit(1);}
 }
 foreach(['staging_status','live_status'] as $state){
   if(!in_array($r[$state],['UNVERIFIED','BLOCKED','PASS','FAIL'],true)){
      fwrite(STDERR,"Unsupported QA status ".$id."\n");exit(1);
   }
   if($r[$state]==='PASS'){
      $live=($state==='live_status');
      $prefix=$live?'live':'staging';
      if($r['source_sha']===''||!preg_match('/^[0-9a-f]{40}$/iD',$r['source_sha'])
         ||!preg_match('/^[0-9a-f]{64}$/iD',$r['deployed_sha256'])
         ||trim($r['physical_db_version'])===''||trim($r['migration_state'])===''
         ||trim($r[$prefix.'_evidence_id'])===''||trim($r[$prefix.'_reviewer'])===''
         ||trim($r[$prefix.'_timestamp_utc'])===''||$r['target_environment']==='HOSTINGER_STAGING_REQUIRED'){
          fwrite(STDERR,"Unverified context cannot pass ".$id." / ".$state."\n");exit(1);
      }
      if($live && ($r['staging_status']!=='PASS' ||
         $r['live_evidence_id']===$r['staging_evidence_id'])){
         fwrite(STDERR,"LIVE cannot reuse staging receipt ".$id."\n");exit(1);
      }
      if(trim($r['defect_id'])!=='' && ($r['retest_status']!=='PASS' ||
         !preg_match('/^[0-9a-f]{40}$/iD',$r['fix_sha']) ||
         !preg_match('/^[0-9a-f]{40}$/iD',$r['retest_sha']))){
         fwrite(STDERR,"Found defect without immutable corrective retest ".$id."\n");exit(1);
      }
   }
 }
 $seen[$id]=true;$counts[$r['parent_qa']]=($counts[$r['parent_qa']]??0)+1;$rows++;
}
fclose($fh);
for($n=15;$n<=24;$n++){
 $qa='QA-'.$n;
 if(($counts[$qa]??0)!==6){fwrite(STDERR,"Expected six QA cases under ".$qa."\n");exit(1);}
 for($i=1;$i<=6;$i++){
    $id=$qa.'-T0'.$i;
    if(!isset($seen[$id])){fwrite(STDERR,"Missing QA case ".$id."\n");exit(1);}
 }
}
if($rows!==60||count($seen)!==60){fwrite(STDERR,"QA casebook must contain exactly 60 distinct scenarios\n");exit(1);}
echo "V12.3 QA15..24 casebook: 60 immutable scenarios and proof schemas VALID; no staging or live tests claimed.\n";
