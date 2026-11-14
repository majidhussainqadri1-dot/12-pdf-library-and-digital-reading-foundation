<?php
/**
 * V12.3 File12 exact-source manifest verifier: 58 files and Git blob identities.
 * This asserts source bytes ON THIS CHECKOUT only, never Hostinger deployment parity.
 */
declare(strict_types=1);
$root=dirname(__DIR__);
$file=$root.'/docs/V123-FILE12-RC43-EXACT-SOURCE-INVENTORY.csv';
$in=@fopen($file,'rb');
if(!$in){fwrite(STDERR,"Missing File12 source inventory\n");exit(1);}
$h=fgetcsv($in,0,',','"','\\');
$required=['path','git_blob_sha','bytes','code_type','candidate_git_sha','review_status','runtime_status'];
if(!is_array($h)||$h!==$required){fwrite(STDERR,"Source inventory schema mismatch\n");exit(1);}
$seen=[];$count=0;$failures=[];
while(($v=fgetcsv($in,0,',','"','\\'))!==false){
 if($v===[null])continue;
 if(count($v)!==count($h)){$failures[]="bad row";continue;}
 $r=array_combine($h,$v);
 $p=(string)$r['path'];
 if(!str_starts_with($p,'pdf-library-foundation-12/')||str_contains($p,'..')||isset($seen[$p])){$failures[]="unsafe/duplicate path: ".$p;continue;}
 $seen[$p]=true;
 $absolute=$root.'/'.$p;
 if(!is_file($absolute)||is_link($absolute)){$failures[]="missing/link: ".$p;continue;}
 $raw=@file_get_contents($absolute);
 if($raw===false){$failures[]="unreadable: ".$p;continue;}
 $bytes=strlen($raw);
 $blob=sha1('blob '.$bytes."\0".$raw);
 if((string)$r['bytes']!==(string)$bytes||strtolower((string)$r['git_blob_sha'])!==$blob)
   $failures[]="Git blob or byte size drift: ".$p;
 if((string)$r['candidate_git_sha']!=='d7c0d9458402019a3c46c73b3a7563329e9be8f7')
   $failures[]="candidate source SHA mismatch: ".$p;
 if((string)$r['runtime_status']!=='UNVERIFIED')
   $failures[]="source manifest claims unproven runtime status: ".$p;
 $count++;
}
fclose($in);
$directory=$root.'/pdf-library-foundation-12';
$iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS));
$actual=[];
foreach($iter as $candidate){
 $p=str_replace('\\','/',substr($candidate->getPathname(),strlen($root)+1));
 if($candidate->isLink()){$failures[]="symlink inside plugin package: ".$p;continue;}
 if($candidate->isFile())$actual[$p]=true;
}
foreach($actual as $p=>$_)if(!isset($seen[$p]))$failures[]="unregistered plugin file: ".$p;
if($count!==58||count($actual)!==58)$failures[]="Source file-count mismatch: inventoried ".$count."; plugin files ".count($actual);
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "V12.3 File12 exact SOURCE inventory PASS: 58 plugin files / Git blob SHA-1 and byte size match. ";
echo "Hostinger deployed package, DB and runtime remain UNVERIFIED.\n";
