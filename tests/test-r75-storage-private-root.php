<?php
/**
 * File12 R75 RED-first: a misconfigured shared owner (scope=spl) must not
 * expose even encrypted book objects under the public document root.
 * Unknown scopes must not bypass the private-root protections.
 * This PHP fixture uses isolated synthetic temp directories, no WP/Hostinger.
 */
declare(strict_types=1);
class WP_Error {
    private string $code;
    public function __construct(string $code,string $message=''){$this->code=$code;}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($value):bool{return $value instanceof WP_Error;}
function trailingslashit(string $path):string{return rtrim($path,"/\\").'/';}
function untrailingslashit(string $path):string{return rtrim($path,"/\\");}
function path_is_absolute(string $path):bool{return str_starts_with($path,'/');}
function wp_mkdir_p(string $path):bool{return is_dir($path)||mkdir($path,0700,true);}
function pldr_check($bool,string $name):void{if(!$bool){fwrite(STDERR,"R75 RED failure: ".$name."\n");exit(1);}echo "PASS: ".$name."\n";}
$base=sys_get_temp_dir().'/file12-r75-'.bin2hex(random_bytes(6));
$web=$base.'/public_html';
$wp=$web.'/wp';
$shared=$wp.'/shared-pdf-objects';
$private=$base.'/private-vault';
foreach([$shared,$private] as $dir)if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException("fixture mkdir");
register_shutdown_function(static function()use($base):void{
  if(!is_dir($base))return;
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
  foreach($it as $item){if($item->isDir()&&!$item->isLink())@rmdir($item->getPathname());else @unlink($item->getPathname());}
  @rmdir($base);
});
define('ABSPATH',$wp.'/');
define('SPL_PDF_STORAGE_DIR',$shared);
define('PLDR_PDF_STORAGE_DIR',$private);
$_SERVER['DOCUMENT_ROOT']=$web;
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-storage.php';
$root=PLDR_Storage::root('pldr');
pldr_check(!is_wp_error($root) && realpath($root)===realpath($private),
 'ordinary File12 owner-private storage remains allowed outside document root');
$exposed=PLDR_Storage::root('spl');
pldr_check(is_wp_error($exposed) && $exposed->get_error_code()==='pldr_storage_public',
 'shared-owner storage inside public webroot must fail closed');
$exposedPath=PLDR_Storage::path('synthetic-file.pldr','spl');
pldr_check(is_wp_error($exposedPath)&&$exposedPath->get_error_code()==='pldr_storage_public',
 'shared-owner path() must never bypass root privacy');
$unknown=PLDR_Storage::root('unexpected-owner-scope');
pldr_check(is_wp_error($unknown)&&$unknown->get_error_code()==='pldr_storage_scope',
 'unexpected storage scope cannot bypass root guards');
$_SERVER['DOCUMENT_ROOT']='';
$missingDocRoot=PLDR_Storage::root('spl');
pldr_check(is_wp_error($missingDocRoot)&&$missingDocRoot->get_error_code()==='pldr_storage_public',
 'WordPress ABSPATH fallback must protect even if DOCUMENT_ROOT is empty');
echo "R75 File12 private storage scope and webroot confinement: PASS (synthetic only).\n";
