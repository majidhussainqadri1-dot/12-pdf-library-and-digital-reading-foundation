<?php
/**
 * R27 executable regression: a signed grant never remains valid for a PDF
 * object or thumbnail that is no longer attached to its issuing edition.
 * This loads the actual PLDR_Access method with a strict isolated DB fixture.
 */
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
class WP_Error {
    public string $code;
    public function __construct(string $code) { $this->code=$code; }
}
function is_wp_error($x):bool { return $x instanceof WP_Error; }
final class PLDR_R28_Binding_DB {
    public string $last_error='';
    public bool $derivative_available=true;
    public bool $derivative_read_failed=false;
    public function prepare(string $sql,...$args):string {return $sql;}
    public function get_var(string $sql) {
        if($this->derivative_read_failed) {$this->last_error='simulated DB read failure';return null;}
        return $this->derivative_available ? 61 : null;
    }
}
final class PLDR_Core {
    public static ?array $edition=array('id'=>21,'object_id'=>41);
    public static bool $edition_read_failed=false;
    public static function edition(int $id):?array {
        global $wpdb;
        if(self::$edition_read_failed){$wpdb->last_error='simulated edition read failure';return null;}
        return self::$edition && $id===(int)self::$edition['id'] ? self::$edition : null;
    }
    public static function table(string $name):string {return 'wp_pldr_'.$name;}
    public static function machine_error(string $code,string $message,int $status=400,array $extra=array()):WP_Error {return new WP_Error($code);}
}
$GLOBALS['wpdb']=new PLDR_R28_Binding_DB();
require __DIR__.'/../pdf-library-foundation-12/includes/class-pldr-access.php';
$method=new ReflectionMethod('PLDR_Access','current_delivery_binding');
$check=static function(bool $condition,string $message):void {if(!$condition)throw new RuntimeException('R28 delivery binding regression: '.$message);};
$bind=static function(int $edition_id,int $object_id)use($method){return $method->invoke(null,$edition_id,$object_id);};
$check(true===$bind(21,41),'Current PDF original must remain eligible');
$check(true===$bind(21,42),'Attached available derivative must remain eligible');
$GLOBALS['wpdb']->derivative_available=false;
$check(false===$bind(21,42),'Detached/rotated derivative must invalidate an old signed grant');
$GLOBALS['wpdb']->derivative_available=true;
$check(false===$bind(22,41),'Unrelated edition must not gain original-object binding');
PLDR_Core::$edition=null;
$check(false===$bind(21,41),'Deleted edition must invalidate old signed grant');
PLDR_Core::$edition=array('id'=>21,'object_id'=>41);
PLDR_Core::$edition_read_failed=true;
$failure=$bind(21,41);
$check(is_wp_error($failure)&&'pldr_delivery_binding_read'===$failure->code,'Edition read failure must fail closed');
PLDR_Core::$edition_read_failed=false;
$GLOBALS['wpdb']->derivative_read_failed=true;
$failure=$bind(21,42);
$check(is_wp_error($failure)&&'pldr_derivative_read'===$failure->code,'Derivative association read failure must fail closed');
$source=file_get_contents(__DIR__.'/../pdf-library-foundation-12/includes/class-pldr-access.php');
$check(substr_count($source,'self::current_delivery_binding((int)$row[\'edition_id\'],(int)$row[\'object_id\'])')===1,
       'Signed-delivery path must call the current-binding gate exactly once');
echo "PASS R28: current original/derivative bindings, detached grants and database failure denial\n";
