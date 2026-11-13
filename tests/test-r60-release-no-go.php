<?php
/** R60 CI must prove source-only release gate denies an unevidenced deployment. */
declare(strict_types=1);
$gate=dirname(__DIR__).'/scripts/file12-release-no-go.php';
if(!is_file($gate)){fwrite(STDERR,"Missing release gate source\n");exit(1);}
foreach(array('predeploy','postdeploy') as $mode){
  $output=array();$status=0;
  exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($gate).' '.escapeshellarg($mode).' 2>&1',$output,$status);
  $text=implode("\n",$output);
  if($status!==2||!str_contains($text,'NO_GO')||!str_contains($text,'39 evidence gates not proved')
      ||!str_contains($text,'54 evidence gates not proved')
      ||!str_contains($text,'10 evidence gates not proved')){
    fwrite(STDERR,"Source release gate did not fail closed for $mode; status=$status\n$text\n");exit(1);
  }
}
echo "R60 release NO_GO gate on 39+54+10 unevidenced controls: 2 modes PASS (no deploy).\n";
