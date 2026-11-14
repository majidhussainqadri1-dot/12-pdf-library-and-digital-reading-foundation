<?php
/**
 * V12.3 File12 source-governance ledger structure checks ONLY.
 * A green result must NEVER imply installed WordPress, staging, or live acceptance.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$matrix = $root . '/docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv';
function fail_v123(string $message): void { fwrite(STDERR, "V123 ledger FAIL: " . $message . PHP_EOL); exit(1); }
function csv_v123(string $path): array {
    if (!is_readable($path)) fail_v123("Cannot read ".basename($path));
    $handle=fopen($path,'r');
    if ($handle===false) fail_v123("Cannot open ".basename($path));
    $headers=fgetcsv($handle,0,',','"','\\');
    if (!is_array($headers) || $headers===[]) fail_v123("Missing CSV header");
    if (count(array_unique($headers))!==count($headers)) fail_v123("Repeated CSV column");
    $rows=[];
    while (($vals=fgetcsv($handle,0,',','"','\\'))!==false) {
        if ($vals===[null]) continue;
        if (count($vals)!==count($headers)) fail_v123("Malformed row in ".basename($path));
        $rows[]=array_combine($headers,$vals);
    }
    fclose($handle);
    return [$headers,$rows];
}
[$columns,$rows]=csv_v123($matrix);
foreach (['id','group','work_package','v123_requirement','primary_owner','linked_acceptance',
  'code_anchor_claim','source_review_status','staging_status','live_status',
  'environment','observed','evidence_id','reviewer','defect_id','retest_status','decision',
  'live_environment','live_observed','live_evidence_id','live_reviewer'] as $field) {
    if (!in_array($field,$columns,true)) fail_v123("Missing column ".$field);
}
$counts=['FR'=>0,'NFR'=>0,'FUT'=>0,'CEN'=>0,'MODERN'=>0,'QA'=>0,'JOURNEY'=>0];
$seen=[];
foreach ($rows as $row) {
    $id=(string)$row['id'];
    if ($id==='' || isset($seen[$id])) fail_v123("Empty or duplicate ID ".$id);
    $seen[$id]=true;
    $group=(string)$row['group'];
    if (!array_key_exists($group,$counts)) fail_v123("Unknown group ".$group);
    $counts[$group]++;
    foreach (['work_package','v123_requirement','primary_owner','linked_acceptance','code_anchor_claim','decision'] as $required) {
        if (trim((string)$row[$required])==='') fail_v123($id." missing ".$required);
    }
    if (!preg_match('/^WP-(?:0[0-9]|1[0-3])$/D',(string)$row['work_package'])) fail_v123($id." invalid WP");
    foreach (['staging_status','live_status'] as $state) {
        $status=(string)$row[$state];
        if (!in_array($status,['UNVERIFIED','PASS','FAIL','BLOCKED'],true)) fail_v123($id." invalid ".$state);
        if ($status==='PASS') {
            $isLive=($state==='live_status');
            $proofKey=$isLive?'live_evidence_id':'evidence_id';
            $reviewerKey=$isLive?'live_reviewer':'reviewer';
            $environmentKey=$isLive?'live_environment':'environment';
            $observedKey=$isLive?'live_observed':'observed';
            $environment=strtoupper(trim((string)$row[$environmentKey]));
            $observed=strtoupper(trim((string)$row[$observedKey]));
            if (trim((string)$row[$proofKey])==='' || trim((string)$row[$reviewerKey])==='' ||
                in_array($environment,['','UNVERIFIED','HOSTINGER_STAGING_REQUIRED','NOT_OBSERVED','PENDING'],true) ||
                in_array($observed,['','NOT_OBSERVED','UNVERIFIED','PENDING','NOT_RUN'],true))
                fail_v123($id." fabricated/context-free ".$state." PASS");
            if ($isLive && ($row['staging_status']!=='PASS' ||
                !preg_match('/(?:LIVE|PROD)/',$environment) ||
                (string)$row['live_evidence_id']===(string)$row['evidence_id']))
                fail_v123($id." LIVE proof must be separate from accepted STAGING");
            if (trim((string)$row['defect_id'])!=='' && (string)$row['retest_status']!=='PASS')
                fail_v123($id." defect has no passing retest");
        }
    }
    if ($group==='FR' && !preg_match('/^F12-FR-(?:0(?:0[1-9]|1[0-9]))$/D',$id)) fail_v123("Bad FR id ".$id);
    if ($group==='NFR' && !preg_match('/^F12-NFR-0(?:0[1-9]|10)$/D',$id)) fail_v123("Bad NFR id ".$id);
    if ($group==='FUT' && !preg_match('/^F12-FUT-0(?:0[1-9]|1[0-9]|2[0-4])$/D',$id)) fail_v123("Bad FUT id ".$id);
}
$expected=['FR'=>19,'NFR'=>10,'FUT'=>24,'CEN'=>1,'MODERN'=>12,'QA'=>10,'JOURNEY'=>1];
if ($counts!==$expected || count($rows)!==77) fail_v123("Count mismatch ".json_encode($counts));
foreach (array_merge(
  array_map(static fn($n)=>sprintf('F12-FR-%03d',$n),range(1,19)),
  array_map(static fn($n)=>sprintf('F12-NFR-%03d',$n),range(1,10)),
  array_map(static fn($n)=>sprintf('F12-FUT-%03d',$n),range(1,24)),
  ['F12-CEN-01','AJ-14'],
  array_map(static fn($n)=>sprintf('M%02d',$n),range(1,12)),
  array_map(static fn($n)=>sprintf('QA-%02d',$n),range(15,24))
) as $expectedId) {
  if (!isset($seen[$expectedId])) fail_v123("Missing immutable ID ".$expectedId);
}
$legacy=[
 'docs/R57-FILE12-V121-39-STAGING-GATES.csv'=>39,
 'docs/R58-FILE12-V121-54-REQUIREMENTS.csv'=>54,
 'docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv'=>10
];
foreach ($legacy as $path=>$expectedRows) {
    [$head,$records]=csv_v123($root.'/'.$path);
    if (count($records)!==$expectedRows) fail_v123("Legacy evidence count changed: ".$path);
    foreach ($records as $record) {
      foreach (['staging_status','live_status','status'] as $state) {
        if (!array_key_exists($state,$record)) continue;
        if ($record[$state]==='PASS' && (empty($record['evidence_id']) || empty($record['reviewer'])))
            fail_v123("Unsupported legacy PASS: ".$path);
      }
    }
}
echo "V12.3 SOURCE-LEDGER STRUCTURE PASS: 77 IDs (54+12+10+1); original 39/54/10 registers retained. ";
echo "NO staging or live runtime acceptance is asserted.".PHP_EOL;
