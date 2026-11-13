<?php
/** R65 RED-first: a previously found staging defect cannot have PASS while retest remains NOT_RUN. */
declare(strict_types=1);
$root=dirname(__DIR__);$tmp=sys_get_temp_dir().'/pldr-r65-'.bin2hex(random_bytes(8));
mkdir($tmp.'/scripts',0700,true);mkdir($tmp.'/docs',0700,true);
$script='scripts/file12-release-no-go.php';copy($root.'/'.$script,$tmp.'/'.$script);
$paths=array(
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>'status',
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>'staging_status',
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>'staging_status'
);
foreach($paths as $path=>$stage){
 $in=fopen($root.'/'.$path,'rb');$out=fopen($tmp.'/'.$path,'wb');
 $head=fgetcsv($in,0,',','"','\\');$idx=array_flip($head);
 fputcsv($out,$head,',','"','\\');
 $i=0;
 while(($row=fgetcsv($in,0,',','"','\\'))!==false){
   if(count($row)!==count($head))throw new RuntimeException('bad test fixture');
   $row[$idx[$stage]]='PASS';
   if(isset($idx['live_status']))$row[$idx['live_status']]='PASS';
   $row[$idx['reviewer']]='mock-independent-qa';
   $row[$idx['evidence_id']]='MOCK-OBSERVED-REVIEW';
   if($path==='docs/R57-FILE12-V121-39-STAGING-GATES.csv'){
      $row[$idx['environment']]='HOSTINGER_STAGING_REAL_FIXTURE';
      $row[$idx['observed']]='OBSERVED_HTTP_AND_DATABASE';
      $row[$idx['retest_status']]='PASS';
      if($i===0){
        // One identified defect was never re-tested after a purported repair.
        $row[$idx['defect_id']]='R65-UNRETESTED-001';
        $row[$idx['retest_status']]='NOT_RUN';
      }
   }
   fputcsv($out,$row,',','"','\\');$i++;
 }
 fclose($in);fclose($out);
}
$approval=json_decode(file_get_contents($root.'/docs/R60-RELEASE-APPROVAL-STATE.json'),true);
foreach($approval['status'] as $key=>$old){$approval['status'][$key]='PASS';$approval['proofs'][$key]='STUB-DO-NOT-DEPLOY';}
$approval['release_candidate_git_sha']=str_repeat('a',40);
$approval['deployed_package_sha256']=str_repeat('b',64);
file_put_contents($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json',json_encode($approval));
$fail=array();$checks=0;
foreach(array('predeploy','postdeploy') as $mode){
 $out=array();$status=0;
 exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tmp.'/'.$script).' '.escapeshellarg($mode).' 2>&1',$out,$status);
 $txt=implode("\n",$out);$checks++;
 if($status!==2||!str_contains($txt,'NO_GO')||!str_contains($txt,'1 of 39 evidence gates not proved')){
   $fail[]="R65 FAIL: $mode accepted un-retested defect despite staged PASS: exit=$status; ".substr($txt,0,210);
 }
}
foreach(array_keys($paths) as $path)unlink($tmp.'/'.$path);
unlink($tmp.'/'.$script);unlink($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json');
rmdir($tmp.'/scripts');rmdir($tmp.'/docs');rmdir($tmp);
if($fail){fwrite(STDERR,implode("\n",$fail)."\n");exit(1);}
echo "R65 release defect re-test proof: $checks incomplete-retest pre/postdeploy modes denied.\n";
