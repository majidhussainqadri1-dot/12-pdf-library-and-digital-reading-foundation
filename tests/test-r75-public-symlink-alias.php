<?php
/**
 * R75 follow-up RED: a URL-visible symlink alias under public_html pointing
 * to otherwise private storage is unsafe, even though realpath() lands outside
 * the webroot. Synthetic temp directory only, no real Hostinger files.
 */
declare(strict_types=1);
class WP_Error{
 public function __construct(private string $code,private string $message=''){}
 public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function trailingslashit(string $s):string{return rtrim($s,"/\\").'/';}
function untrailingslashit(string $s):string{return rtrim($s,"/\\");}
function path_is_absolute(string $s):bool{return str_starts_with($s,'/');}
function wp_mkdir_p(string $s):bool{return is_dir($s)||mkdir($s,0700,true);}
$base=sys_get_temp_dir().'/file12-r75-alias-'.bin2hex(random_bytes(6));
$web=$base.'/public_html';
$wp=$web.'/wp';
$private=$base.'/secret-vault';
$alias=$web.'/books';
foreach([$wp,$private] as $dir)if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Unable to create synthetic directory');
register_shutdown_function(static function()use($base):void{
 if(!is_dir($base))return;
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $item){if($item->isDir()&&!$item->isLink())@rmdir($item->getPathname());else @unlink($item->getPathname());}
 @rmdir($base);
});
if(!function_exists('symlink')){fwrite(STDERR,"R75 symlink test requires POSIX symlink support.\n");exit(1);}
if(!symlink($private,$alias))throw new RuntimeException('Unable to create isolated fake public symlink');
define('ABSPATH',$wp.'/');
define('SPL_PDF_STORAGE_DIR',$alias);
define('PLDR_PDF_STORAGE_DIR',$private);
$_SERVER['DOCUMENT_ROOT']=$web;
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-storage.php';
$good=PLDR_Storage::root('pldr');
if(is_wp_error($good))throw new RuntimeException('Real private root unexpectedly denied: '.$good->get_error_code());
$bad=PLDR_Storage::root('spl');
if(!is_wp_error($bad)||$bad->get_error_code()!=='pldr_storage_public'){
 fwrite(STDERR,"R75 RED: a public_html/books symlink alias to private files was incorrectly allowed.\n");
 exit(1);
}
echo "R75 public URL symlink alias to outside private vault: DENIED (synthetic PASS).\n";
