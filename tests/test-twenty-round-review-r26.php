<?php
/**
 * R26 authorization regression: public/anonymous delivery grants must never
 * inherit the currently signed-in curator's privileged role when rechecked.
 * Tests the actual PLDR_Access class with an isolated WordPress policy fixture.
 */
declare(strict_types=1);
error_reporting(E_ALL);
define('ABSPATH', __DIR__.'/');
function get_current_user_id(): int { return 9; }
function apply_filters($tag, $value, ...$args) { return $value; }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
class WP_Error {}
class PLDR_Core {
    public static array $editions = array();
    public static array $policies = array();
    public static function edition(int $id) { return self::$editions[$id] ?? null; }
    public static function policy(int $document_id) { return self::$policies[$document_id] ?? null; }
    public static function authorize(string $action, int $document_id = 0, int $user_id = 0): bool {
        // This intentionally reproduces the actual Core::authorize fallback.
        $user_id = $user_id ?: get_current_user_id();
        return $user_id === 9 && in_array($action, array('manage', 'rights', 'read_private'), true);
    }
    public static function audit(string $kind, int $id, string $event, array $extra = array(), int $actor = 0): void {}
}
require_once __DIR__.'/../pdf-library-foundation-12/includes/class-pldr-access.php';
$fail = static function (string $detail): void {
    throw new RuntimeException('R26 anonymous grant regression: '.$detail);
};
$edition = array(
    'id' => 51, 'document_id' => 31, 'status' => 'published',
    'document_status' => 'published', 'rights_expires_at' => null,
);
$policy = array('audience' => 'public', 'download_allowed' => 1,
    'print_allowed' => 1, 'offline_allowed' => 1, 'embargo_until' => null);
PLDR_Core::$editions[51] = $edition;
PLDR_Core::$policies[31] = $policy;
if (!PLDR_Access::can_access_edition(51, 'read', -1)) $fail('public published edition should remain readable anonymously');
if (!PLDR_Access::can_access_edition(51, 'read', 9)) $fail('explicit curator should read published edition');
$scenarios = array(
    'draft_edition' => array('edition_status' => 'draft'),
    'restricted_document' => array('document_status' => 'restricted'),
    'removed_document' => array('document_status' => 'removed'),
    'rights_expired' => array('rights_expires_at' => '2020-01-01 00:00:00'),
    'embargo_active' => array('embargo_until' => '2099-01-01 00:00:00'),
);
foreach ($scenarios as $label => $changes) {
    PLDR_Core::$editions[51] = $edition;
    PLDR_Core::$policies[31] = $policy;
    foreach ($changes as $key => $value) {
        if ($key === 'edition_status') PLDR_Core::$editions[51]['status'] = $value;
        elseif (array_key_exists($key, $edition)) PLDR_Core::$editions[51][$key] = $value;
        else PLDR_Core::$policies[31][$key] = $value;
    }
    if (PLDR_Access::can_access_edition(51, 'read', -1)) $fail($label.' leaked current curator authority to anonymous grant');
    if (!PLDR_Access::can_access_edition(51, 'read', 9)) $fail($label.' unexpectedly denied an explicitly named curator in test fixture');
}
PLDR_Core::$editions[51] = $edition;
PLDR_Core::$policies[31] = array_merge($policy, array('audience' => 'account'));
if (PLDR_Access::can_access_edition(51, 'read', -1)) $fail('anonymous grant passed account-only audience');
if (!PLDR_Access::can_access_edition(51, 'read', 9)) $fail('explicit account should pass account-only audience');
$access_source = file_get_contents(__DIR__.'/../pdf-library-foundation-12/includes/class-pldr-access.php');
if (strpos($access_source, '$grant_user=(int)$row[\'user_id\']>0?(int)$row[\'user_id\']:-1') === false) {
    $fail('delivery no longer uses explicit anonymous grant sentinel');
}
echo "PASS R26: public grant identity remains anonymous despite signed-in privileged session\n";
