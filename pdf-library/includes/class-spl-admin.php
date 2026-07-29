<?php
defined('ABSPATH') || exit;

final class SPL_Admin {
    public function hooks() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_post_spl_review', array($this, 'review'));
        add_action('admin_post_spl_report', array($this, 'report'));
        add_action('admin_notices', array($this, 'notice'));
    }

    public function menu() {
        add_menu_page('PDF Library Management', 'PDF Library Management', 'manage_pdf_library', 'pdf-library-management', array($this, 'page'), 'dashicons-book-alt', 32);
        add_submenu_page('pdf-library-management', 'Reports', 'Reports', 'manage_pdf_library', 'pdf-library-reports', array($this, 'reports'));
        add_submenu_page('pdf-library-management', 'System Health', 'System Health', 'manage_pdf_library', 'pdf-library-health', array($this, 'health'));
    }

    public function page() {
        $items = get_posts(array(
            'post_type' => SPL_Helpers::TYPE,
            'post_status' => array('pending', 'publish', 'draft', 'private'),
            'posts_per_page' => 250,
        ));
        ?>
        <div class="wrap">
            <h1>PDF Library Management</h1>
            <table class="widefat striped">
                <thead><tr><th>Document</th><th>Status</th><th>Review action</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($item->post_title); ?></strong><br><?php echo esc_html(SPL_Helpers::term($item->ID, SPL_Helpers::DOCTYPE)); ?></td>
                        <td><?php echo esc_html(get_post_status($item)); ?></td>
                        <td>
                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                <input type="hidden" name="action" value="spl_review">
                                <input type="hidden" name="id" value="<?php echo absint($item->ID); ?>">
                                <?php wp_nonce_field('spl_review_' . $item->ID); ?>
                                <select name="decision">
                                    <option value="publish">Approve</option>
                                    <option value="draft">Reject</option>
                                    <option value="private">Hide</option>
                                    <option value="feature">Toggle Featured</option>
                                </select>
                                <textarea name="note" placeholder="Copyright and safety review note"></textarea>
                                <button class="button button-primary" type="submit">Apply</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function review() {
        if (!current_user_can('manage_pdf_library')) {
            wp_die('Denied', array('response' => 403));
        }
        $post_id = absint($_POST['id'] ?? 0);
        check_admin_referer('spl_review_' . $post_id);
        if (SPL_Helpers::TYPE !== get_post_type($post_id)) {
            wp_die('Invalid library document.', array('response' => 400));
        }

        $decision = sanitize_key(wp_unslash($_POST['decision'] ?? ''));
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        $old_status = (string) get_post_status($post_id);

        if (in_array($decision, array('draft', 'private'), true) && !$note) {
            wp_die('A review note is required when rejecting or hiding a document.', array('response' => 400));
        }

        if ('feature' === $decision) {
            $old_value = SPL_Helpers::meta($post_id, 'featured', '0');
            $new_value = '1' === $old_value ? '0' : '1';
            update_post_meta($post_id, '_spl_featured', $new_value);
            SPL_Helpers::audit('document', $post_id, 'feature_toggled', $note, $old_value, $new_value);
        } elseif (in_array($decision, array('publish', 'draft', 'private'), true)) {
            $result = wp_update_post(array('ID' => $post_id, 'post_status' => $decision), true);
            if (is_wp_error($result)) {
                wp_die(esc_html($result->get_error_message()), array('response' => 500));
            }
            update_post_meta($post_id, '_spl_last_review_note', $note);
            update_post_meta($post_id, '_spl_last_reviewer_id', get_current_user_id());
            update_post_meta($post_id, '_spl_last_reviewed_at', current_time('mysql', true));
            SPL_Helpers::audit('document', $post_id, 'reviewed', $note, $old_status, $decision);
        } else {
            wp_die('Invalid review decision.', array('response' => 400));
        }

        wp_safe_redirect(admin_url('admin.php?page=pdf-library-management'));
        exit;
    }

    public function reports() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spl_reports ORDER BY created_at DESC LIMIT 250");
        ?>
        <div class="wrap">
            <h1>Library Reports</h1>
            <table class="widefat striped">
                <thead><tr><th>Document</th><th>Report</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html(get_the_title($row->document_id)); ?></td>
                        <td><?php echo esc_html($row->reason); ?><br><?php echo esc_html($row->details); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="spl_report">
                                <input type="hidden" name="id" value="<?php echo absint($row->id); ?>">
                                <?php wp_nonce_field('spl_report_' . $row->id); ?>
                                <select name="status">
                                    <?php foreach (array('open', 'reviewing', 'resolved', 'dismissed') as $status) : ?>
                                        <option value="<?php echo esc_attr($status); ?>" <?php selected($row->status, $status); ?>><?php echo esc_html(ucfirst($status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea name="note" placeholder="Resolution note"></textarea>
                                <button class="button" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function report() {
        if (!current_user_can('manage_pdf_library')) {
            wp_die('Denied', array('response' => 403));
        }
        $report_id = absint($_POST['id'] ?? 0);
        check_admin_referer('spl_report_' . $report_id);
        $status = sanitize_key(wp_unslash($_POST['status'] ?? ''));
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        if (!in_array($status, array('open', 'reviewing', 'resolved', 'dismissed'), true)) {
            wp_die('Invalid report status.', array('response' => 400));
        }

        global $wpdb;
        $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spl_reports WHERE id=%d", $report_id));
        if (!$report) {
            wp_die('Report not found.', array('response' => 404));
        }
        if (in_array($status, array('resolved', 'dismissed'), true) && !$note) {
            wp_die('A resolution note is required.', array('response' => 400));
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'spl_reports',
            array('status' => $status, 'updated_at' => current_time('mysql', true)),
            array('id' => $report_id),
            array('%s', '%s'),
            array('%d')
        );
        if (false === $updated) {
            wp_die('The report status could not be updated.', array('response' => 500));
        }
        SPL_Helpers::audit('report', $report_id, 'status_changed', $note, $report->status, $status);
        wp_safe_redirect(admin_url('admin.php?page=pdf-library-reports'));
        exit;
    }

    public function health() {
        $blockers = SPL_Helpers::upload_blockers();
        $warnings = SPL_Helpers::integration_warnings();
        ?>
        <div class="wrap">
            <h1>PDF Library System Health</h1>
            <?php if (!$blockers) : ?>
                <div class="notice notice-success inline"><p>Encryption, private storage, and required runtime checks are ready.</p></div>
            <?php else : ?>
                <div class="notice notice-error inline"><p><strong>Uploads remain blocked until these issues are corrected:</strong></p><ul>
                    <?php foreach ($blockers as $issue) : ?><li><?php echo esc_html($issue); ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
            <?php if ($warnings) : ?>
                <div class="notice notice-warning inline"><p><strong>Integration warnings:</strong></p><ul>
                    <?php foreach ($warnings as $warning) : ?><li><?php echo esc_html($warning); ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
            <h2>Required key configuration</h2>
            <p>Define a backed-up 32-byte key in <code>wp-config.php</code>. A key ring is preferred so encrypted files retain their key ID during future rotation.</p>
            <pre>define('SPL_PDF_MASTER_KEYS', array('v1' =&gt; 'base64:REPLACE_WITH_32_BYTE_BASE64_KEY'));
define('SPL_PDF_ACTIVE_KEY_ID', 'v1');</pre>
            <p>Never remove an old key from the key ring until every file using that key has been migrated and verified.</p>
        </div>
        <?php
    }

    public function notice() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        if (get_transient('spl_notice')) {
            delete_transient('spl_notice');
            echo '<div class="notice notice-success"><p>PDF Library 0.2.0 is active. Complete System Health before accepting uploads.</p></div>';
        }
        $schema_error = get_transient('spl_schema_error');
        if ($schema_error) {
            echo '<div class="notice notice-error"><p><strong>PDF Library database upgrade failed:</strong> ' . esc_html($schema_error) . '</p></div>';
        }
        $blockers = SPL_Helpers::upload_blockers();
        if ($blockers) {
            echo '<div class="notice notice-error"><p><strong>PDF Library upload gate is closed:</strong> ' . esc_html(implode(' ', $blockers)) . '</p></div>';
        }
        $warnings = SPL_Helpers::integration_warnings();
        if ($warnings) {
            echo '<div class="notice notice-warning"><p><strong>PDF Library integration warning:</strong> ' . esc_html(implode(' ', $warnings)) . '</p></div>';
        }
    }
}
