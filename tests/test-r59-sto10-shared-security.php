<?php
/** R59 V12.1 shared CF04 STO1..10 registry: only source-document identity. */
declare(strict_types=1);
$path=dirname(__DIR__).'/docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv';
$h=fopen($path,'r');
if($h===false){fwrite(STDERR,"Missing shared storage security ledger\n");exit(1);}
$cols=array('sto_id','owner','acceptance_scope','dependency','source_status','staging_status','live_status','evidence_id','reviewer');
if(fgetcsv($h,0,',','"','\\')!==$cols){fwrite(STDERR,"Invalid STO header\n");exit(1);}
$found=array();
while(($line=fgetcsv($h,0,',','"','\\'))!==false){
  if(count($line)!==count($cols)){fwrite(STDERR,"Invalid STO row length\n");exit(1);}
  $r=array_combine($cols,$line);$id=$r['sto_id'];
  if(isset($found[$id])||$r['owner']!=='CF04_ORIGINAL_OWNER'){fwrite(STDERR,"Duplicate/invalid STO owner\n");exit(1);}
  if(''===trim($r['acceptance_scope'])||''===trim($r['dependency'])){fwrite(STDERR,"STO contract missing\n");exit(1);}
  foreach(array('staging_status','live_status') as $where){
    if(!in_array($r[$where],array('UNVERIFIED','BLOCKED','PASS','FAIL'),true)){fwrite(STDERR,"Unsupported STO status\n");exit(1);}
    if(in_array($r[$where],array('PASS','FAIL'),true)&&(''===$r['evidence_id']||''===$r['reviewer'])){
      fwrite(STDERR,"STO runtime verdict without proof\n");exit(1);
    }
  }
  $found[$id]=true;
}
fclose($h);
if(count($found)!==10){fwrite(STDERR,"Must have exactly 10 STO controls\n");exit(1);}
for($i=1;$i<=10;$i++){if(!isset($found[sprintf('STO-%02d',$i)])){fwrite(STDERR,"Missing V12.1 STO control\n");exit(1);}}
echo "R59 10 CF04 security/storage source-only acceptance gates: PASS; 10 real staging states unverified.\n";
