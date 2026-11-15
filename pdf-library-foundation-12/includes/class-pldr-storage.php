<?php

defined('ABSPATH') || exit;

final class PLDR_Storage {
    private static array $temporary_paths=array();
    private static bool $shutdown_registered=false;
    public static function root(string $scope = 'pldr') {
        // The shared SPL/CF04 owner is not automatically a private filesystem
        // path. Unknown scopes must never inherit the default directory while
        // bypassing the stricter File12 owner privacy checks.
        if (!in_array($scope, array('pldr', 'spl'), true)) {
            return new WP_Error('pldr_storage_scope', 'Unknown private storage owner scope.');
        }
        if ('spl' === $scope) {
            $configured = defined('SPL_PDF_STORAGE_DIR') ? (string) SPL_PDF_STORAGE_DIR : '';
            $root = $configured ?: trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'sabri-private/pdf-library';
        } else {
            $configured = defined('PLDR_PDF_STORAGE_DIR') ? (string) PLDR_PDF_STORAGE_DIR : '';
            $root = $configured ?: trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'sabri-private/file-12';
        }
        $root = untrailingslashit($root);
        if (function_exists('path_is_absolute') && !path_is_absolute($root)) {
            return new WP_Error('pldr_storage_absolute', 'The File 12 storage path must be absolute.');
        }
        if ('pldr' === $scope && !is_dir($root) && !wp_mkdir_p($root)) {
            return new WP_Error('pldr_storage_create', 'The private File 12 storage directory could not be created.');
        }
        if (!is_dir($root)) {
            return new WP_Error('pldr_storage_missing', 'The requested private storage directory is unavailable.');
        }
        if ('pldr' === $scope && !is_writable($root)) {
            return new WP_Error('pldr_storage_write', 'The private File 12 storage directory is not writable.');
        }

        // Use canonical real paths (including symlink targets) for BOTH owner
        // scopes. DOCUMENT_ROOT is often absent under WP-CLI or cron, therefore
        // WordPress ABSPATH also acts as a conservative public-root boundary.
        // Neither a manager setting nor a shared owner config can waive it.
        $real = realpath($root);
        if (!$real) {
            return new WP_Error('pldr_storage_realpath', 'Private storage directory could not be resolved.');
        }
        $public_roots = array();
        foreach (array(
            (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
            defined('ABSPATH') ? (string) ABSPATH : '',
        ) as $candidate) {
            if ('' === trim($candidate)) continue;
            // Preserve the literal served filesystem path in addition to the
            // resolved path. A symlink *inside* the public tree pointing out
            // to a private directory must still be denied: its alias may be
            // directly addressable through the web server.
            $literal = rtrim($candidate, DIRECTORY_SEPARATOR);
            $canonical = realpath($candidate);
            foreach (array($literal, $canonical) as $public_root) {
                if (false !== $public_root && '' !== $public_root &&
                    !in_array($public_root, $public_roots, true)) {
                    $public_roots[] = $public_root;
                }
            }
        }
        foreach ($public_roots as $public_root) {
            $boundary = rtrim($public_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($real === $public_root ||
                str_starts_with($real, $boundary) ||
                $root === $public_root ||
                str_starts_with($root, $boundary)) {
                return new WP_Error('pldr_storage_public',
                    'Shared and File 12 object storage must reside outside the public document and WordPress roots.');
            }
        }
        if ('pldr' === $scope) {
            self::protect($root);
        }
        return $root;
    }

    private static function protect(string $root): void {
        $files = array(
            'index.php' => "<?php http_response_code(403); exit;\n",
            'index.html' => '',
            '.htaccess' => "Deny from all\nOptions -Indexes\n",
            'web.config' => '<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>',
        );
        foreach ($files as $name => $content) {
            $path = trailingslashit($root) . $name;
            if (!file_exists($path)) {
                @file_put_contents($path, $content, LOCK_EX);
            }
        }
    }

    public static function path(string $storage_name, string $scope = 'pldr') {
        $root = self::root($scope);
        if (is_wp_error($root)) {
            return $root;
        }
        $raw=trim($storage_name);
        $name=basename($raw);
        if ('' === $name || '.' === $name || '..' === $name || $raw !== $name || false !== strpos($raw, '/') || false !== strpos($raw, '\\') || false !== strpos($raw, "\0")) {
            return new WP_Error('pldr_storage_name', 'Invalid private object name.');
        }
        return trailingslashit($root) . $name;
    }

    public static function allocate(string $extension = 'pldr'): array {
        $name = PLDR_Core::uuid() . '.' . preg_replace('/[^a-z0-9]/i', '', $extension);
        $path = self::path($name);
        if (is_wp_error($path)) {
            return array('error' => $path);
        }
        return array('name' => $name, 'path' => $path);
    }

    public static function temp(string $purpose = 'work') {
        $root = self::root();
        if (is_wp_error($root)) {
            return $root;
        }
        $dir = trailingslashit($root) . '.tmp';
        if (!is_dir($dir) && !wp_mkdir_p($dir)) {
            return new WP_Error('pldr_temp_create', 'The File 12 private temporary directory could not be created.');
        }
        self::protect($dir);
        $path=trailingslashit($dir) . sanitize_key($purpose) . '-' . PLDR_Core::uuid() . '.tmp';
        self::$temporary_paths[$path]=true;
        if(!self::$shutdown_registered){
            self::$shutdown_registered=true;
            register_shutdown_function(array(__CLASS__,'cleanup_temporary_paths'));
        }
        return $path;
    }

    public static function cleanup_temporary_paths():void {
        foreach(array_keys(self::$temporary_paths) as $path){
            if(is_file($path))@unlink($path);
        }
        self::$temporary_paths=array();
    }

    public static function atomic_commit(string $temp, string $final): bool {
        if (!is_file($temp)) {
            return false;
        }
        if (@rename($temp, $final)) {
            @chmod($final, 0640);
            return true;
        }
        return false;
    }

    public static function delete(string $path): bool {
        if (!is_file($path)) return true;
        return @unlink($path);
    }
}
