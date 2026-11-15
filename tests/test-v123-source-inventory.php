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

// R70 rc.45 is a SECOND immutable delta, rooted in the verified rc.44 blobs.
// Never overwrite either the rc.43 full inventory or the rc.44 three-file proof.
$rc45File=$root.'/docs/V123-FILE12-RC45-SOURCE-DELTA-OVERLAY.csv';
$rc45=[];
$rc45Fields=['path','baseline_blob_sha','candidate_blob_sha','candidate_bytes','introduced_at_sha','audit_status','runtime_status','reason'];
$rc45Handle=@fopen($rc45File,'rb');
if(!$rc45Handle){fwrite(STDERR,"Missing rc.45 immutable R70 source delta overlay\n");exit(1);}
$rc45Header=fgetcsv($rc45Handle,0,',','"','\\');
if($rc45Header!==$rc45Fields){fwrite(STDERR,"rc.45 source delta overlay schema mismatch\n");exit(1);}
while(($values=fgetcsv($rc45Handle,0,',','"','\\'))!==false){
 if($values===[null])continue;
 if(count($values)!==count($rc45Fields)){fwrite(STDERR,"Malformed rc.45 delta row\n");exit(1);}
 $record=array_combine($rc45Fields,$values);
 $path45=(string)$record['path'];
 if(isset($rc45[$path45])||!in_array($path45,[
   'pdf-library-foundation-12/assets/reader.js',
   'pdf-library-foundation-12/pdf-library-digital-reading.php'
 ],true)){fwrite(STDERR,"Unexpected or repeated rc.45 source delta ".$path45."\n");exit(1);}
 foreach(['baseline_blob_sha','candidate_blob_sha','introduced_at_sha'] as $shaField){
   if(!preg_match('/^[0-9a-f]{40}$/D',(string)$record[$shaField])){
     fwrite(STDERR,"Malformed rc.45 blob provenance ".$path45."\n");exit(1);
   }
 }
 if((string)$record['audit_status']!=='SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW'
   ||(string)$record['runtime_status']!=='UNVERIFIED'
   ||trim((string)$record['reason'])===''||!ctype_digit((string)$record['candidate_bytes'])){
     fwrite(STDERR,"Misleading rc.45 review/runtime status ".$path45."\n");exit(1);
 }
 $rc45[$path45]=$record;
}
fclose($rc45Handle);
if(count($rc45)!==2){fwrite(STDERR,"rc.45 overlay must contain exactly two source deltas\n");exit(1);}

// R71 third chained overlay. rc.43 baseline, rc.44 overlay and rc.45 overlay
// must be retained exactly, even for files revised by rc.46.
$rc46File=$root.'/docs/V123-FILE12-RC46-SOURCE-DELTA-OVERLAY.csv';
$rc46=[];
$rc46Fields=['path','baseline_blob_sha','candidate_blob_sha','candidate_bytes','introduced_at_sha','audit_status','runtime_status','reason'];
$rc46Handle=@fopen($rc46File,'rb');
if(!$rc46Handle){fwrite(STDERR,"Missing rc.46 immutable R71 source delta overlay\n");exit(1);}
$rc46Header=fgetcsv($rc46Handle,0,',','"','\\');
if($rc46Header!==$rc46Fields){fwrite(STDERR,"rc.46 source delta overlay schema mismatch\n");exit(1);}
while(($values=fgetcsv($rc46Handle,0,',','"','\\'))!==false){
 if($values===[null])continue;
 if(count($values)!==count($rc46Fields)){fwrite(STDERR,"Malformed rc.46 delta row\n");exit(1);}
 $record=array_combine($rc46Fields,$values);
 $path46=(string)$record['path'];
 if(isset($rc46[$path46])||!in_array($path46,[
   'pdf-library-foundation-12/assets/future-reader-vault.js',
   'pdf-library-foundation-12/pdf-library-digital-reading.php'
 ],true)){fwrite(STDERR,"Unexpected or duplicated rc.46 source delta ".$path46."\n");exit(1);}
 foreach(['baseline_blob_sha','candidate_blob_sha','introduced_at_sha'] as $field){
   if(!preg_match('/^[0-9a-f]{40}$/D',(string)$record[$field])){
     fwrite(STDERR,"Invalid rc.46 SHA provenance ".$path46."\n");exit(1);
   }
 }
 if((string)$record['audit_status']!=='SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW'
   ||(string)$record['runtime_status']!=='UNVERIFIED'
   ||trim((string)$record['reason'])===''||!ctype_digit((string)$record['candidate_bytes'])){
     fwrite(STDERR,"Invalid rc.46 review status ".$path46."\n");exit(1);
 }
 $rc46[$path46]=$record;
}
fclose($rc46Handle);
if(count($rc46)!==2){fwrite(STDERR,"rc.46 overlay must contain two source deltas\n");exit(1);}

// R72 fourth immutable overlay: do not rewrite earlier rc.43/44/45/46 hashes.
$rc47File=$root.'/docs/V123-FILE12-RC47-SOURCE-DELTA-OVERLAY.csv';
$rc47=[];
$rc47Fields=['path','baseline_blob_sha','candidate_blob_sha','candidate_bytes','introduced_at_sha','audit_status','runtime_status','reason'];
$rc47Handle=@fopen($rc47File,'rb');
if(!$rc47Handle){fwrite(STDERR,"Missing rc.47 exact R72 source delta overlay\n");exit(1);}
$rc47Header=fgetcsv($rc47Handle,0,',','"','\\');
if($rc47Header!==$rc47Fields){fwrite(STDERR,"rc.47 source delta schema mismatch\n");exit(1);}
while(($values=fgetcsv($rc47Handle,0,',','"','\\'))!==false){
 if($values===[null])continue;
 if(count($values)!==count($rc47Fields)){fwrite(STDERR,"Malformed rc.47 source delta\n");exit(1);}
 $record=array_combine($rc47Fields,$values);
 $path47=(string)$record['path'];
 if(isset($rc47[$path47])||!in_array($path47,[
  'pdf-library-foundation-12/assets/future-reader-vault.js',
  'pdf-library-foundation-12/pdf-library-digital-reading.php'
 ],true)){fwrite(STDERR,"Unexpected or duplicate rc.47 source delta ".$path47."\n");exit(1);}
 foreach(['baseline_blob_sha','candidate_blob_sha','introduced_at_sha'] as $field){
   if(!preg_match('/^[0-9a-f]{40}$/D',(string)$record[$field])){
     fwrite(STDERR,"Invalid rc.47 blob SHA ".$path47."\n");exit(1);
   }
 }
 if((string)$record['audit_status']!=='SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW'
   ||(string)$record['runtime_status']!=='UNVERIFIED'
   ||trim((string)$record['reason'])===''||!ctype_digit((string)$record['candidate_bytes'])){
     fwrite(STDERR,"Invalid rc.47 review or runtime state ".$path47."\n");exit(1);
 }
 $rc47[$path47]=$record;
}
fclose($rc47Handle);
if(count($rc47)!==2){fwrite(STDERR,"rc.47 overlay must contain exactly two source deltas\n");exit(1);}

// R73 fifth immutable overlay: do not rewrite earlier rc.43/44/45/46 hashes.
$rc48File=$root.'/docs/V123-FILE12-RC48-SOURCE-DELTA-OVERLAY.csv';
$rc48=[];
$rc48Fields=['path','baseline_blob_sha','candidate_blob_sha','candidate_bytes','introduced_at_sha','audit_status','runtime_status','reason'];
$rc48Handle=@fopen($rc48File,'rb');
if(!$rc48Handle){fwrite(STDERR,"Missing rc.48 exact R72 source delta overlay\n");exit(1);}
$rc48Header=fgetcsv($rc48Handle,0,',','"','\\');
if($rc48Header!==$rc48Fields){fwrite(STDERR,"rc.48 source delta schema mismatch\n");exit(1);}
while(($values=fgetcsv($rc48Handle,0,',','"','\\'))!==false){
 if($values===[null])continue;
 if(count($values)!==count($rc48Fields)){fwrite(STDERR,"Malformed rc.48 source delta\n");exit(1);}
 $record=array_combine($rc48Fields,$values);
 $path48=(string)$record['path'];
 if(isset($rc48[$path48])||!in_array($path48,[
  'pdf-library-foundation-12/assets/future-reader-vault.js',
  'pdf-library-foundation-12/pdf-library-digital-reading.php'
 ],true)){fwrite(STDERR,"Unexpected or duplicate rc.48 source delta ".$path48."\n");exit(1);}
 foreach(['baseline_blob_sha','candidate_blob_sha','introduced_at_sha'] as $field){
   if(!preg_match('/^[0-9a-f]{40}$/D',(string)$record[$field])){
     fwrite(STDERR,"Invalid rc.48 blob SHA ".$path48."\n");exit(1);
   }
 }
 if((string)$record['audit_status']!=='SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW'
   ||(string)$record['runtime_status']!=='UNVERIFIED'
   ||trim((string)$record['reason'])===''||!ctype_digit((string)$record['candidate_bytes'])){
     fwrite(STDERR,"Invalid rc.48 review or runtime state ".$path48."\n");exit(1);
 }
 $rc48[$path48]=$record;
}
fclose($rc48Handle);
if(count($rc48)!==2){fwrite(STDERR,"rc.48 overlay must contain exactly two source deltas\n");exit(1);}

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
 // Each intermediate parent identity is checked, not just the newest bytes.
 if(isset($overlay[$p])&&strtolower((string)$overlay[$p]['baseline_blob_sha'])!==strtolower($base))
   $failures[]="Invalid rc.44 source parent: ".$p;
 $rc45Parent=isset($overlay[$p])?(string)$overlay[$p]['candidate_blob_sha']:$base;
 if(isset($rc45[$p])&&strtolower((string)$rc45[$p]['baseline_blob_sha'])!==strtolower($rc45Parent))
   $failures[]="Invalid rc.45 source parent: ".$p;
 $rc46Parent=isset($rc45[$p])?(string)$rc45[$p]['candidate_blob_sha']:$rc45Parent;
 if(isset($rc46[$p])&&strtolower((string)$rc46[$p]['baseline_blob_sha'])!==strtolower($rc46Parent))
   $failures[]="Invalid rc.46 source parent: ".$p;
 $rc47Parent=isset($rc46[$p])?(string)$rc46[$p]['candidate_blob_sha']:$rc46Parent;
 if(isset($rc47[$p])&&strtolower((string)$rc47[$p]['baseline_blob_sha'])!==strtolower($rc47Parent))
   $failures[]="Invalid rc.47 source parent: ".$p;
 $rc48Parent=isset($rc47[$p])?(string)$rc47[$p]['candidate_blob_sha']:$rc47Parent;
 if(isset($rc48[$p])){
   $new=$rc48[$p];
   $oldBytes=isset($rc47[$p])?(string)$rc47[$p]['candidate_bytes']:
     (isset($rc46[$p])?(string)$rc46[$p]['candidate_bytes']:
      (isset($rc45[$p])?(string)$rc45[$p]['candidate_bytes']:
       (isset($overlay[$p])?(string)$overlay[$p]['candidate_bytes']:(string)$r['bytes'])));
   if(strtolower((string)$new['baseline_blob_sha'])!==strtolower($rc48Parent)
     ||strtolower((string)$new['candidate_blob_sha'])!==$blob
     ||(string)$new['candidate_bytes']!==(string)$bytes
     ||($blob===$rc48Parent&&(string)$bytes===$oldBytes))
       $failures[]="Invalid or unrecorded rc.48 source delta: ".$p;
 }elseif(isset($rc47[$p])){
   $new=$rc47[$p];
   $oldBytes=isset($rc46[$p])?(string)$rc46[$p]['candidate_bytes']:
     (isset($rc45[$p])?(string)$rc45[$p]['candidate_bytes']:
      (isset($overlay[$p])?(string)$overlay[$p]['candidate_bytes']:(string)$r['bytes']));
   if(strtolower((string)$new['baseline_blob_sha'])!==strtolower($rc47Parent)
     ||strtolower((string)$new['candidate_blob_sha'])!==$blob
     ||(string)$new['candidate_bytes']!==(string)$bytes
     ||($blob===$rc47Parent&&(string)$bytes===$oldBytes))
       $failures[]="Invalid or unrecorded rc.47 source delta: ".$p;
 }elseif(isset($rc46[$p])){
   $new=$rc46[$p];
   $oldBytes=isset($rc45[$p])?(string)$rc45[$p]['candidate_bytes']:
     (isset($overlay[$p])?(string)$overlay[$p]['candidate_bytes']:(string)$r['bytes']);
   if(strtolower((string)$new['baseline_blob_sha'])!==strtolower($rc46Parent)
     ||strtolower((string)$new['candidate_blob_sha'])!==$blob
     ||(string)$new['candidate_bytes']!==(string)$bytes
     ||($blob===$rc46Parent&&(string)$bytes===$oldBytes))
       $failures[]="Invalid or unrecorded rc.46 source delta: ".$p;
 }elseif(isset($rc45[$p])){
   $new=$rc45[$p];
   $oldBytes=isset($overlay[$p])?(string)$overlay[$p]['candidate_bytes']:(string)$r['bytes'];
   if(strtolower((string)$new['baseline_blob_sha'])!==strtolower($rc45Parent)
     ||strtolower((string)$new['candidate_blob_sha'])!==$blob
     ||(string)$new['candidate_bytes']!==(string)$bytes
     ||($blob===$rc45Parent&&(string)$bytes===$oldBytes))
       $failures[]="Invalid or unrecorded rc.45 source delta: ".$p;
 }elseif(isset($overlay[$p])){
   $patch=$overlay[$p];
   if(strtolower((string)$patch['baseline_blob_sha'])!==strtolower($base)
     ||strtolower((string)$patch['candidate_blob_sha'])!==$blob
     ||(string)$patch['candidate_bytes']!==(string)$bytes
     ||$currentIsFrozen)
       $failures[]="Invalid or unrecorded rc.44 source delta: ".$p;
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
echo "V12.3 File12 SOURCE inventory PASS: 58 original rc.43 blobs verified, plus ".count($overlay)." rc.44 and ".count($rc45)." rc.45 and ".count($rc46)." rc.46 and ".count($rc47)." rc.47 and ".count($rc48)." rc.48 exact candidate deltas (no overwritten baseline). ";
echo "All delta reviews and Hostinger deployed package, DB, and runtime remain UNVERIFIED.\n";
