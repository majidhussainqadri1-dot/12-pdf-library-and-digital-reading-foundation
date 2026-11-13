<?php
/** R61 RED-first: public Book Pack writer rejects an anonymous/no-actor caller. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
class WP_Error {
    private string $code;
    public function __construct($code,$message='',$data=array()){$this->code=(string)$code;}
    public function get_error_code(){return $this->code;}
}
function get_current_user_id():int{return $GLOBALS['actor']??0;}
class PLDR_Core {
    public static function authorize($scope,$id=0,$actor=0):bool {return $GLOBALS['permit']??false;}
    public static function founder($actor):bool {return false;}
    public static function machine_error($code,$message,$status=400,$info=array()){return new WP_Error($code,$message);}
}
$GLOBALS['permit']=false;
$GLOBALS['actor']=0;
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rights.php';
$checks=0;
function require_forbidden($result,$scope){
    global $checks;$checks++;
    if(!($result instanceof WP_Error) || $result->get_error_code()!=='pldr_pack_forbidden'){
        fwrite(STDERR,"R61 FAIL: $scope must deny before any manifest processing or database work\n");exit(1);
    }
}
require_forbidden(PLDR_Book_Packs::register(array(),0),'anonymous actor zero');
$GLOBALS['actor']=14;
require_forbidden(PLDR_Book_Packs::register(array(),0),'authenticated non-manager');
$GLOBALS['actor']=0;
require_forbidden(PLDR_Book_Packs::register(array(),14),'explicit forged non-manager');
// An arbitrary actor ID supplied from another code path is not login proof.
$GLOBALS['permit']=true;
$GLOBALS['actor']=0;
require_forbidden(PLDR_Book_Packs::register(array(),14),'anonymous caller supplying an authorized third-party actor ID');
$GLOBALS['permit']=false;
// Valid real manager session is allowed to reach manifest validation.
$GLOBALS['actor']=14;
$GLOBALS['permit']=true;
$validManager=PLDR_Book_Packs::register(array(),14);
$checks++;
if(!($validManager instanceof WP_Error)||$validManager->get_error_code()!=='pldr_pack_required'){
    fwrite(STDERR,"R61 FAIL: current authorized manager incorrectly denied before form validation\\n");exit(1);
}
// Bundled manifests remain a private, fixed-directory system operation; no public actor bypass.
$private=new ReflectionMethod('PLDR_Book_Packs','persist_authorized');
$checks++;
if(!$private->isPrivate()){fwrite(STDERR,"R61 FAIL: Bundled persistence method must remain private\\n");exit(1);}
$rightsSource=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rights.php');
$checks++;
if(!str_contains($rightsSource,'if(is_array($manifest))self::persist_authorized($manifest,0);')){
    fwrite(STDERR,"R61 FAIL: Trusted bundle importer does not use private controlled path\\n");exit(1);
}
echo "R61 Book Pack actor authority: $checks fail-closed isolated cases PASS.\n";
