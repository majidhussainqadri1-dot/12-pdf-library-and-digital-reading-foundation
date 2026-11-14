<?php
/** V12.3 File12 50/50 PHP static-source audit coverage check; NOT an application-security certification. */
declare(strict_types=1);
$root=dirname(__DIR__);
function scan_csv(string $path):array{
 $f=@fopen($path,'rb');if(!$f)throw new RuntimeException('Missing scan '.$path);
 $h=fgetcsv($f,0,',','"','\\');$rows=[];
 if(!is_array($h)||!in_array('path',$h,true)||!(in_array('sha',$h,true)||in_array('git_blob_sha',$h,true)))throw new RuntimeException('Malformed scan headers '.$path);
 while(($v=fgetcsv($f,0,',','"','\\'))!==false){
  if($v===[null])continue;
  if(count($v)!==count($h))throw new RuntimeException('Malformed scan row '.$path);
  $rows[]=array_combine($h,$v);
 }
 fclose($f);return $rows;
}
$inventory=scan_csv($root.'/docs/V123-FILE12-RC43-EXACT-SOURCE-INVENTORY.csv');
$source=[];
foreach($inventory as $r){
 $p=(string)$r['path'];
 if(str_ends_with($p,'.php'))$source[$p]=(string)$r['git_blob_sha'];
}
if(count($source)!==50)throw new RuntimeException('Expected exact source 50 PHP files; found '.count($source));
$parts=[
 'docs/V123-FILE12-RC43-CORE-STATIC-SCAN.csv'=>18,
 'docs/V123-RC43-ADDITIONAL-PHP-STATIC-SCAN-A.csv'=>16,
 'docs/V123-RC43-ADDITIONAL-PHP-STATIC-SCAN-B.csv'=>16
];
$seen=[];
foreach($parts as $file=>$count){
 $rows=scan_csv($root.'/'.$file);
 if(count($rows)!==$count)throw new RuntimeException($file.' incorrect scan count');
 foreach($rows as $r){
  $p=(string)$r['path'];$sha=(string)($r['sha']??$r['git_blob_sha']??'');
  if(isset($seen[$p])||!isset($source[$p])||$source[$p]!==$sha)
   throw new RuntimeException('Duplicated/missing/different Git source blob: '.$p);
  $seen[$p]=true;
 }
}
if(count($seen)!==count($source))throw new RuntimeException('Some exact source PHP files were not scanned');
echo "V12.3 File12 50/50 PHP CODE-SHAPE AUDIT COVERAGE PASS on frozen Git blob identities. ";
echo "This does not establish runtime behavior, vulnerability absence or live deployment.\n";
