<?php
defined('ABSPATH') || exit;

final class SPL_Helpers {
    const TYPE = 'spl_document';
    const TAX = 'spl_category';
    const DOCTYPE = 'spl_document_type';

    public static function types() {
        return array(
            'book' => 'Book',
            'reference-book' => 'Reference Book',
            'research-paper' => 'Research Paper',
            'clinical-study' => 'Clinical Study',
            'educational-notes' => 'Educational Notes',
            'lecture-handout' => 'Lecture Handout',
            'materia-medica' => 'Materia Medica',
            'repertory' => 'Repertory',
            'philosophy' => 'Philosophy',
            'anatomy' => 'Anatomy',
            'pathology' => 'Pathology',
            'nutrition' => 'Nutrition',
            'public-health' => 'Public Health',
            'principles-hygiene' => 'Principles of Hygiene',
            'islamic-spiritual-healing' => 'Islamic Spiritual Healing',
            'historical-document' => 'Historical Document',
        );
    }

    public static function categories() {
        return array(
            'classical-homeopathy' => 'Classical Homeopathy',
            'homeopathy-education' => 'Homeopathy Education',
            'materia-medica' => 'Materia Medica',
            'repertory' => 'Repertory',
            'clinical-education' => 'Clinical Education',
            'patient-cases' => 'Patient Cases',
            'homeopathy-philosophy' => 'Homeopathy Philosophy',
            'research' => 'Research',
            'nutrition' => 'Nutrition',
            'public-health-education' => 'Public Health Education',
            'pathology' => 'Pathology',
            'anatomy' => 'Anatomy',
            'principles-hygiene' => 'Principles of Hygiene',
            'islamic-spiritual-healing' => 'Islamic Spiritual Healing',
            'platform-publications' => 'Platform Publications',
            'historical-homeopathy' => 'Historical Homeopathy',
        );
    }

    public static function report_reasons() {
        return array(
            'copyright',
            'unauthorized-scan',
            'attribution',
            'patient-privacy',
            'medical-safety',
            'misleading-claim',
            'false-credentials',
            'broken-pdf',
            'spam',
            'other',
        );
    }

    public static function founder($user_id = 0) {
        $user_id = $user_id ? absint($user_id) : get_current_user_id();
        return $user_id && $user_id === absint(get_option('spf_founder_user_id', 0));
    }

    public static function doctor($user_id = 0) {
        $user_id = $user_id ? absint($user_id) : get_current_user_id();
        return $user_id
            && class_exists('SPD_Helpers')
            && SPD_Helpers::is_doctor($user_id)
            && 'verified' === SPD_Helpers::verification_status($user_id);
    }

    public static function can_submit($user_id = 0) {
        $user_id = $user_id ? absint($user_id) : get_current_user_id();
        return $user_id && (
            user_can($user_id, 'manage_pdf_library')
            || self::founder($user_id)
            || self::doctor($user_id)
        );
    }

    public static function submission_status($user_id = 0) {
        $user_id = $user_id ? absint($user_id) : get_current_user_id();
        return user_can($user_id, 'manage_pdf_library') || self::founder($user_id) ? 'publish' : 'pending';
    }

    public static function meta($post_id, $key, $default = '') {
        $value = get_post_meta(absint($post_id), '_spl_' . $key, true);
        return '' === $value ? $default : $value;
    }

    public static function term($post_id, $taxonomy) {
        $terms = get_the_terms(absint($post_id), $taxonomy);
        return $terms && !is_wp_error($terms) ? $terms[0]->name : '';
    }

    public static function storage() {
        $configured = defined('SPL_PDF_STORAGE_DIR') ? (string) SPL_PDF_STORAGE_DIR : '';
        $directory = $configured
            ? $configured
            : trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'sabri-private/pdf-library';
        $directory = untrailingslashit($directory);
        if (function_exists('path_is_absolute') && !path_is_absolute($directory)) {
            return new WP_Error('spl_storage_absolute', 'SPL_PDF_STORAGE_DIR must be an absolute filesystem path.');
        }

        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            return new WP_Error('spl_storage_create', 'The private PDF storage directory could not be created. Define SPL_PDF_STORAGE_DIR to a writable directory outside the public web root.');
        }
        if (!is_writable($directory)) {
            return new WP_Error('spl_storage_write', 'The private PDF storage directory is not writable.');
        }

        $protections = array(
            'index.php' => "<?php http_response_code(403); exit;\n",
            'index.html' => '',
            '.htaccess' => "Deny from all\n",
            'web.config' => '<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>',
        );
        foreach ($protections as $name => $contents) {
            $path = trailingslashit($directory) . $name;
            if (!file_exists($path) && false === @file_put_contents($path, $contents, LOCK_EX)) {
                return new WP_Error('spl_storage_protection', 'The private PDF storage protection files could not be written.');
            }
        }

        return $directory;
    }

    public static function storage_path($storage_name) {
        $directory = self::storage();
        if (is_wp_error($directory)) {
            return $directory;
        }
        return trailingslashit($directory) . basename((string) $storage_name);
    }

    public static function stream_url($post_id, $download = false) {
        return add_query_arg(
            array(
                'action' => 'spl_stream',
                'document_id' => absint($post_id),
                'download' => $download ? 1 : 0,
            ),
            admin_url('admin-post.php')
        );
    }

    public static function nav() {
        return class_exists('SDD_Helpers')
            ? str_replace('class="sdd-main-nav"', 'class="spl-main-nav"', SDD_Helpers::navigation())
            : '';
    }

    public static function rate_limit($action, $limit, $window) {
        $user_id = get_current_user_id();
        $identity = $user_id ? 'u' . $user_id : 'i' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $key = 'spl_rl_' . md5($action . '|' . $identity);
        $count = absint(get_transient($key));
        if ($count >= absint($limit)) {
            return false;
        }
        set_transient($key, $count + 1, absint($window));
        return true;
    }

    public static function update_counter($post_id, $type) {
        global $wpdb;
        $post_id = absint($post_id);
        if ('save' === $type) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spl_user_data WHERE document_id=%d AND data_type='save'",
                $post_id
            ));
            update_post_meta($post_id, '_spl_saves', $count);
        }
    }

    public static function audit($object_type, $object_id, $action, $note = '', $old_value = '', $new_value = '') {
        global $wpdb;
        return false !== $wpdb->insert(
            $wpdb->prefix . 'spl_audit',
            array(
                'object_type' => sanitize_key($object_type),
                'object_id' => absint($object_id),
                'action' => sanitize_key($action),
                'actor_id' => get_current_user_id(),
                'note' => sanitize_textarea_field($note),
                'old_value' => sanitize_text_field($old_value),
                'new_value' => sanitize_text_field($new_value),
                'created_at' => current_time('mysql', true),
            ),
            array('%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );
    }

    public static function upload_blockers() {
        $issues = array();
        $crypto_message = '';
        if (!SPL_Crypto::is_ready($crypto_message)) {
            $issues[] = $crypto_message;
        }

        $storage = self::storage();
        if (is_wp_error($storage)) {
            $issues[] = $storage->get_error_message();
        } elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $document_root = realpath((string) $_SERVER['DOCUMENT_ROOT']);
            $storage_root = realpath($storage);
            if ($document_root && $storage_root && 0 === strpos($storage_root, rtrim($document_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                $issues[] = 'The PDF storage directory is inside the public document root. Define SPL_PDF_STORAGE_DIR to a protected directory outside it.';
            }
        }

        return $issues;
    }

    public static function integration_warnings() {
        $warnings = array();
        $dependencies = array(
            'SPD_Helpers' => 'Files 03, 07, or 09 doctor verification helper',
            'SDD_Helpers' => 'File 07 directory/navigation helper',
        );
        foreach ($dependencies as $class => $label) {
            if (!class_exists($class)) {
                $warnings[] = $label . ' is not currently available; related integration will remain limited.';
            }
        }
        return $warnings;
    }

    public static function health() {
        return array_merge(self::upload_blockers(), self::integration_warnings());
    }
}
