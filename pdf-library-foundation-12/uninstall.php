<?php
/** File 12 uninstall is intentionally non-destructive. */
defined('WP_UNINSTALL_PLUGIN') || exit;
if (!defined('PLDR_PURGE_ON_UNINSTALL') || true !== PLDR_PURGE_ON_UNINSTALL) {
    return;
}
if (!defined('PLDR_PURGE_CONFIRMATION') || 'DELETE-FILE-12-DATA' !== PLDR_PURGE_CONFIRMATION) {
    return;
}
// Deliberately no automatic purge. A separately audited migration/retention tool must perform destructive deletion.
