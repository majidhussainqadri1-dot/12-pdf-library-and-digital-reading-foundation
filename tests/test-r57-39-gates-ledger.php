<?php
/** R57: 39 V12.1 staging gates — machine-readable evidence ledger shape only. */
declare(strict_types=1);
$h=fopen(dirname(__DIR__).'/docs/R57-FILE12-V121-39-STAGING-GATES.csv','r');
if($h===false){fwrite(STDERR,"Missing staging ledger\n");exit(1);}
$head=fgetcsv($h,0,',','"','\\');
$expected=array('test_id','group','scenario','dependency','status','environment','observed','evidence_id','defect_id','retest_status','reviewer');
if($head!==$expected){fwrite(STDERR,"Staging ledger schema mismatch\n");exit(1);}
$rows=array();
while(($row=fgetcsv($h,0,',','"','\\'))!==false){
  if(count($row)!==count($expected)){fwrite(STDERR,"Malformed staging row\n");exit(1);}
  $d=array_combine($expected,$row);
  if(isset($rows[$d['test_id']])){fwrite(STDERR,"Duplicate test ID\n");exit(1);}
  if(!in_array($d['status'],array('UNVERIFIED','BLOCKED','PASS','FAIL'),true)){fwrite(STDERR,"Unapproved gate state\n");exit(1);}
  if(in_array($d['status'],array('PASS','FAIL'),true)&&(''===$d['evidence_id']||''===$d['reviewer'])){
    fwrite(STDERR,"Status requires actual staging evidence and reviewer\n");exit(1);
  }
  if(''===trim($d['scenario'])||''===trim($d['dependency'])){fwrite(STDERR,"Missing gate contract\n");exit(1);}
  $rows[$d['test_id']]=$d;
}
fclose($h);
if(count($rows)!==39){fwrite(STDERR,"Staging gate count must equal 39\n");exit(1);}
for($i=1;$i<=39;$i++){if(!isset($rows[sprintf('T-%02d',$i)])){fwrite(STDERR,"Missing indexed staging gate\n");exit(1);}}
$notrun=count(array_filter($rows,static fn($r)=>$r['status']==='UNVERIFIED'));
echo "R57 staging evidence ledger: 39 unique IDs; $notrun unverified; no site runtime evidence inferred. PASS.\n";
