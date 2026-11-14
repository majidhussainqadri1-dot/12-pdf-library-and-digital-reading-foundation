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
$overlayFile=$root.'/docs/V123-FILE12-RC44-SOURCE-DELTA-OVERLAY.csv';
$overlay=[];
if(is_file($overlayFile)){
 $df=@fopen($overlayFile,'rb');
 $dh=$df?fgetcsv($df,0,',','"','\\'):false;
 $fields=['path','baseline_blob_sha','candidate_blob_sha','candidate_bytes','introduced_at_sha','audit_status','runtime_status','reason'];
 if($dh!==$fields){fwrite(STDERR,"Malformed rc.44 overlay schema\n");exit(1);}
 while(($dv=fgetcsv($df,0,',','"','\\'))!==false){
   if($dv===[null])continue;
   if(count($dv)!==count($fields)){fwrite(STDERR,"Malformed rc.44 overlay row\n");exit(1);}
   $record=array_combine($fields,$dv);
   $p=(string)$record['path'];
   if(isset($overlay[$p])||!in_array($p,[
      'pdf-library-foundation-12/assets/reader.js',
      'pdf-library-foundation-12/includes/class-pldr-reader.php',
      'pdf-library-foundation-12/pdf-library-digital-reading.php'
   ],true)) {fwrite(STDERR,"Unexpected or duplicated rc.44 overlay ".$p."\n");exit(1);}
   if(!preg_match('/^[0-9a-f]{40}$/D',(string)$record['baseline_blob_sha'])
     ||!preg_match('/^[0-9a-f]{40}$/D',(string)$record['candidate_blob_sha'])
     ||!preg_match('/^[0-9a-f]{40}$/D',(string)$record['introduced_at_sha'])
     ||(string)$record['audit_status']!=='SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW'
     ||(string)$record['runtime_status']!=='UNVERIFIED'
     ||trim((string)$record['reason'])===''){
        fwrite(STDERR,"Invalid rc.44 overlay provenance/status ".$p."\n");exit(1);
   }
   $overlay[$p]=$record;
 }
 fclose($df);
 if(count($overlay)!==3){fwrite(STDERR,"RC44 overlay must contain exact 3 source deltas\n");exit(1);}
}

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
 $base=(string)$r['git_blob_sha'];
 $currentIsFrozen=((string)$r['bytes']===(string)$bytes && strtolower($base)===$blob);
 if(isset($overlay[$p])){
   $patch=$overlay[$p];
   if(strtolower((string)$patch['baseline_blob_sha'])!==strtolower($base)
     ||strtolower((string)$patch['candidate_blob_sha'])!==$blob
     ||(string)$patch['candidate_bytes']!==(string)$bytes
     ||$currentIsFrozen)
       $failures[]="Invalid or unrecorded rc.44 overlay delta: ".$p;
 }elseif(!$currentIsFrozen) $failures[]="Unapproved source drift from immutable rc.43 baseline: ".$p;
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
echo "V12.3 File12 SOURCE inventory PASS: 58 original rc.43 blobs verified, plus ".count($overlay)." exact rc.44 candidate deltas (no overwritten baseline). ";
echo "All delta reviews and Hostinger deployed package, DB, and runtime remain UNVERIFIED.\n";
