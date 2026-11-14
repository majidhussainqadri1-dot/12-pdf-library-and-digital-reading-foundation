<?php
/**
 * R66 RED-first: V12.3 M01–M12 / QA-15..24 / AJ-14 cannot be bypassed
 * by fabricating completed prior V12.1 39/54/10 ledgers.
 *
 * ALL approvals, reviewers and environments here are synthetic fixture data.
 * NEVER use this as live evidence or deploy this fixture.
 */
declare(strict_types=1);
$root=dirname(__DIR__);
$tmp=sys_get_temp_dir().'/pldr-v123-r66-'.bin2hex(random_bytes(8));
mkdir($tmp.'/docs',0700,true);
mkdir($tmp.'/scripts',0700,true);
copy($root.'/scripts/file12-release-no-go.php',$tmp.'/scripts/file12-release-no-go.php');
$ledgers=[
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>'status',
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>'staging_status',
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>'staging_status'
];
foreach ($ledgers as $path=>$stage) {
    $src=fopen($root.'/'.$path,'rb');
    $dest=fopen($tmp.'/'.$path,'wb');
    if (!$src||!$dest) throw new RuntimeException('fixture open failure');
    $headers=fgetcsv($src,0,',','"','\\');
    $lookup=array_flip($headers);
    fputcsv($dest,$headers,',','"','\\');
    while (($vals=fgetcsv($src,0,',','"','\\'))!==false) {
        if (count($vals)!==count($headers)) throw new RuntimeException('source count mismatch');
        $vals[$lookup[$stage]]='PASS';
        if (isset($lookup['live_status'])) $vals[$lookup['live_status']]='PASS';
        $vals[$lookup['reviewer']]='SYNTHETIC_FIXTURE_NOT_A_REVIEWER';
        $vals[$lookup['evidence_id']]='SYNTHETIC_FIXTURE_NOT_A_PROOF';
        if (isset($lookup['environment'])) $vals[$lookup['environment']]='SYNTHETIC_HOSTINGER_STAGE_FIXTURE';
        if (isset($lookup['observed'])) $vals[$lookup['observed']]='SYNTHETIC_OBSERVED_FIXTURE';
        if (isset($lookup['retest_status'])) $vals[$lookup['retest_status']]='PASS';
        fputcsv($dest,$vals,',','"','\\');
    }
    fclose($src);fclose($dest);
}
copy($root.'/docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv',
     $tmp.'/docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv');
$approval=json_decode(file_get_contents($root.'/docs/R60-RELEASE-APPROVAL-STATE.json'),true);
if (!is_array($approval)) throw new RuntimeException('approval fixture missing');
foreach ($approval['status'] as $name=>$_) {
   $approval['status'][$name]='PASS';
   $approval['proofs'][$name]='SYNTHETIC_APPROVAL_FIXTURE';
}
$approval['release_candidate_git_sha']=str_repeat('a',40);
$approval['deployed_package_sha256']=str_repeat('b',64);
file_put_contents($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json',json_encode($approval));
$bad=[];
foreach (['predeploy','postdeploy'] as $mode) {
    $out=[];$status=0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($tmp.'/scripts/file12-release-no-go.php').' '.escapeshellarg($mode).' 2>&1',$out,$status);
    $message=implode("\n",$out);
    if ($status!==2 || !str_contains($message,'77 evidence gates not proved') || !str_contains($message,'NO_GO')) {
       $bad[]=$mode.': script failed to enforce 77 V12.3 acceptance gates; exit='.$status.' output='.substr($message,0,260);
    }
}
foreach (array_keys($ledgers) as $path) unlink($tmp.'/'.$path);
unlink($tmp.'/docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv');
unlink($tmp.'/docs/R60-RELEASE-APPROVAL-STATE.json');
unlink($tmp.'/scripts/file12-release-no-go.php');
rmdir($tmp.'/docs');rmdir($tmp.'/scripts');rmdir($tmp);
if ($bad) { fwrite(STDERR,implode("\n",$bad)."\n"); exit(1); }
echo "R66 V12.3 77-row NO_GO enforcement: 2 synthetic bypass scenarios rejected (no deployment).\n";
