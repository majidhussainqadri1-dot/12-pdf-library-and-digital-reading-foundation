<?php
/** R58 54 authorized requirements; no static source row grants staging/live acceptance. */
declare(strict_types=1);
$path=dirname(__DIR__).'/docs/R58-FILE12-V121-54-REQUIREMENTS.csv';
$fh=fopen($path,'r');
if($fh===false){fwrite(STDERR,"Missing requirements ledger\n");exit(1);}
$cols=array('requirement_id','group','feature','source_anchor_claim','automated_evidence_claim','source_status','staging_status','live_status','evidence_id','reviewer');
if(fgetcsv($fh,0,',','"','\\')!==$cols){fwrite(STDERR,"Wrong requirements ledger schema\n");exit(1);}
$ids=array();$counts=array('FR'=>0,'NFR'=>0,'FUT'=>0,'CEN'=>0);
while(($row=fgetcsv($fh,0,',','"','\\'))!==false){
  if(count($row)!==count($cols)){fwrite(STDERR,"Malformed 54 requirement line\n");exit(1);}
  $r=array_combine($cols,$row);$id=$r['requirement_id'];
  if(isset($ids[$id])||!isset($counts[$r['group']])){fwrite(STDERR,"Duplicate or invalid group\n");exit(1);}
  if(''===trim($r['feature'])||''===trim($r['source_anchor_claim'])){fwrite(STDERR,"Missing requirement identity or anchor claim\n");exit(1);}
  foreach(array('staging_status','live_status') as $level){
    if(!in_array($r[$level],array('UNVERIFIED','BLOCKED','PASS','FAIL'),true)){fwrite(STDERR,"Invalid acceptance state\n");exit(1);}
    if(in_array($r[$level],array('PASS','FAIL'),true)&&(''===$r['evidence_id']||''===$r['reviewer'])){
      fwrite(STDERR,"Unsupported runtime acceptance claim\n");exit(1);
    }
  }
  $counts[$r['group']]++;$ids[$id]=true;
}
fclose($fh);
foreach(array('FR'=>19,'NFR'=>10,'FUT'=>24,'CEN'=>1) as $type=>$total){
 for($n=1;$n<=$total;$n++){
   $id=$type==='CEN'?'F12-CEN-01':sprintf('F12-%s-%03d',$type,$n);
   if(!isset($ids[$id])){fwrite(STDERR,"Missing V12.1 requirement: $id\n");exit(1);}
 }
 if($counts[$type]!==$total){fwrite(STDERR,"V12.1 requirement count mismatch\n");exit(1);}
}
echo "R58 V12.1 requirements registry 19 FR + 10 NFR + 24 FUT + 1 CEN = 54: PASS (source-only).\n";
