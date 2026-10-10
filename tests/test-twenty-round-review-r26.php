<?php
/**
 * R26 executable authorization regression: a public bearer grant must retain its
 * original anonymous audience even in a browser logged into a privileged account.
 */
require __DIR__ . '/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A', 'ARRAY_A');
if (!function_exists('absint')) { function absint($value): int { return abs((int)$value); } }
final class PLDR_R26_DB {
    public string $prefix = 'wp_';
    public string $last_error = '';
    public array $edition = array(
        'id'=>11, 'document_id'=>7, 'status'=>'published',
        'document_status'=>'restricted', 'rights_expires_at'=>null
    );
    public array $policy = array(
        'audience'=>'public', 'embargo_until'=>null,
        'download_allowed'=>1, 'print_allowed'=>1, 'offline_allowed'=>1,
        'entitlement_key'=>''
    );
    public function prepare(string $sql, ...$args): string { return $sql; }
    public function get_row(string $sql, $format = ARRAY_A): ?array {
        if (str_contains($sql, 'INNER JOIN')) return $this->edition;
        if (str_contains($sql, 'access_policies')) return $this->policy;
        return null;
    }
}
$GLOBALS['wpdb'] = new PLDR_R26_DB();
function get_current_user_id(): int { return 77; } // Privileged browser session.
function get_option(string $name, $default = false) { return 'spf_founder_user_id' === $name ? 77 : $default; }
function apply_filters(string $name, $value, ...$args) { return $value; }
function user_can(int $id, string $cap): bool { return 77 === $id; }
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-core.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-access.php';
$check = static function(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "R26 anonymous audience regression: {$message}\n"); exit(1); }
};
// Core must not inherit the browser identity for an explicit anonymous caller.
$check(!PLDR_Core::authorize('manage', 7, -1), 'negative anonymous sentinel inherited logged-in Founder privileges');
$check(!PLDR_Access::can_access_edition(11, 'read', -1), 'anonymous grant became valid for restricted document');
$check(PLDR_Access::can_access_edition(11, 'read', 77), 'legitimate explicit curator denied restricted document');
$GLOBALS['wpdb']->edition['document_status'] = 'published';
$GLOBALS['wpdb']->policy['embargo_until'] = gmdate('Y-m-d H:i:s', time()+3600);
$check(!PLDR_Access::can_access_edition(11, 'read', -1), 'anonymous grant bypassed embargo via privileged browser');
$check(PLDR_Access::can_access_edition(11, 'read', 77), 'explicit curator embargo authority unexpectedly lost');
$GLOBALS['wpdb']->policy['embargo_until'] = null;
$GLOBALS['wpdb']->edition['rights_expires_at'] = '2000-01-01 00:00:00';
$check(!PLDR_Access::can_access_edition(11, 'read', -1), 'anonymous grant bypassed expired rights');
$GLOBALS['wpdb']->edition['rights_expires_at'] = null;
$check(PLDR_Access::can_access_edition(11, 'read', -1), 'legitimate public document denied to anonymous audience');
$GLOBALS['wpdb']->policy['audience'] = 'account';
$check(!PLDR_Access::can_access_edition(11, 'read', -1), 'anonymous audience became account-entitled');
$check(PLDR_Access::can_access_edition(11, 'read', 77), 'explicit logged-in reader denied account access');
echo "PLDR R26 authenticated-browser / anonymous-bearer authorization regression: PASS\n";
