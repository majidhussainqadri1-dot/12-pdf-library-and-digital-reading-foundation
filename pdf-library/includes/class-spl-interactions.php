<?php
defined('ABSPATH') || exit;

final class SPL_Interactions {
    public function hooks() {
        add_action('wp_ajax_spl_action', array($this, 'ajax'));
    }

    public static function controls($post_id) {
        ob_start();
        ?>
        <section class="spl-controls" data-document="<?php echo absint($post_id); ?>">
            <?php if (is_user_logged_in()) : ?>
                <button type="button" data-spl="save">Save</button>
                <button type="button" data-spl="like">Like</button>
                <button type="button" data-spl="dislike">Dislike</button>
                <details>
                    <summary>Reading Progress, Bookmark, or Note</summary>
                    <form data-spl-form>
                        <select name="kind">
                            <option value="progress">Update reading progress</option>
                            <option value="bookmark">Add or update page bookmark</option>
                            <option value="note">Add private note</option>
                            <option value="report">Report document</option>
                        </select>
                        <input type="number" name="page" min="1" max="<?php echo absint(SPL_Helpers::meta($post_id, 'pages', 1)); ?>" placeholder="Page number">
                        <textarea name="text" maxlength="1500" placeholder="Private note or report details"></textarea>
                        <select name="reason">
                            <option value="copyright">Copyright violation</option>
                            <option value="unauthorized-scan">Unauthorized scan</option>
                            <option value="attribution">Incorrect attribution</option>
                            <option value="patient-privacy">Patient privacy</option>
                            <option value="medical-safety">Medical safety</option>
                            <option value="misleading-claim">Misleading health claim</option>
                            <option value="false-credentials">False credentials</option>
                            <option value="broken-pdf">Broken PDF</option>
                            <option value="spam">Spam</option>
                            <option value="other">Other</option>
                        </select>
                        <button type="submit">Submit</button>
                    </form>
                </details>
            <?php else : ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink($post_id))); ?>">Log in to save, bookmark, or make private notes</a>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function ajax() {
        check_ajax_referer('spl_action', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Log in first.'), 401);
        }
        if (!SPL_Helpers::rate_limit('interaction', 120, HOUR_IN_SECONDS)) {
            wp_send_json_error(array('message' => 'Too many requests. Please wait.'), 429);
        }

        $post_id = absint($_POST['document'] ?? 0);
        $kind = sanitize_key(wp_unslash($_POST['kind'] ?? ''));
        if (SPL_Helpers::TYPE !== get_post_type($post_id) || 'publish' !== get_post_status($post_id)) {
            wp_send_json_error(array('message' => 'Document unavailable.'), 404);
        }

        if (in_array($kind, array('save', 'like', 'dislike'), true)) {
            $this->toggle_state($post_id, $kind);
        }

        $pages = max(1, absint(SPL_Helpers::meta($post_id, 'pages', 1)));
        $page = absint($_POST['page'] ?? 0);
        $text = sanitize_textarea_field(wp_unslash($_POST['text'] ?? ''));

        if (in_array($kind, array('progress', 'bookmark', 'note'), true)) {
            if (in_array($kind, array('progress', 'bookmark'), true) && ($page < 1 || $page > $pages)) {
                wp_send_json_error(array('message' => 'Enter a page number within the document range.'), 400);
            }
            if ('note' === $kind && !$text) {
                wp_send_json_error(array('message' => 'A private note cannot be empty.'), 400);
            }
            if (!$this->save_private_item($post_id, $kind, $page, $text)) {
                wp_send_json_error(array('message' => 'The private reading item could not be saved.'), 500);
            }
            wp_send_json_success(array('message' => 'Saved privately.'));
        }

        if ('report' === $kind) {
            $reason = sanitize_key(wp_unslash($_POST['reason'] ?? ''));
            if (!in_array($reason, SPL_Helpers::report_reasons(), true)) {
                wp_send_json_error(array('message' => 'Select a valid report reason.'), 400);
            }
            if (!$text) {
                wp_send_json_error(array('message' => 'Report details are required.'), 400);
            }
            if (!SPL_Helpers::rate_limit('report', 10, DAY_IN_SECONDS)) {
                wp_send_json_error(array('message' => 'The report limit has been reached. Please wait before sending another report.'), 429);
            }

            global $wpdb;
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'spl_reports',
                array(
                    'user_id' => get_current_user_id(),
                    'document_id' => $post_id,
                    'reason' => $reason,
                    'details' => $text,
                    'status' => 'open',
                    'created_at' => current_time('mysql', true),
                    'updated_at' => current_time('mysql', true),
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            if (false === $inserted) {
                wp_send_json_error(array('message' => 'The report could not be saved.'), 500);
            }
            SPL_Helpers::audit('document', $post_id, 'reported', $text, '', $reason);
            wp_send_json_success(array('message' => 'Report received.'));
        }

        wp_send_json_error(array('message' => 'Unsupported action.'), 400);
    }

    private function toggle_state($post_id, $kind) {
        global $wpdb;
        $user_id = get_current_user_id();
        $type = 'save' === $kind ? 'save' : 'reaction';
        $table = $wpdb->prefix . 'spl_user_data';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id=%d AND document_id=%d AND data_type=%s AND item_key='singleton' LIMIT 1",
            $user_id,
            $post_id,
            $type
        ));

        if ($existing && ('save' === $kind || $existing->reaction === $kind)) {
            $wpdb->delete($table, array('id' => $existing->id), array('%d'));
        } else {
            $data = array(
                'user_id' => $user_id,
                'document_id' => $post_id,
                'data_type' => $type,
                'item_key' => 'singleton',
                'page_number' => 0,
                'note' => '',
                'reaction' => 'save' === $kind ? '' : $kind,
                'progress' => 0,
                'updated_at' => current_time('mysql', true),
            );
            $saved = $wpdb->replace($table, $data, array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s'));
            if (false === $saved) {
                wp_send_json_error(array('message' => 'The interaction could not be saved.'), 500);
            }
        }

        if ('save' === $kind) {
            SPL_Helpers::update_counter($post_id, 'save');
        }
        wp_send_json_success(array('message' => 'Updated.', 'reload' => true));
    }

    private function save_private_item($post_id, $kind, $page, $text) {
        global $wpdb;
        $item_key = 'note' === $kind
            ? 'note-' . wp_generate_uuid4()
            : ('bookmark' === $kind ? 'page-' . $page : 'singleton');

        $result = $wpdb->replace(
            $wpdb->prefix . 'spl_user_data',
            array(
                'user_id' => get_current_user_id(),
                'document_id' => $post_id,
                'data_type' => $kind,
                'item_key' => $item_key,
                'page_number' => $page,
                'note' => $text,
                'reaction' => '',
                'progress' => 'progress' === $kind ? $page : 0,
                'updated_at' => current_time('mysql', true),
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        return false !== $result;
    }
}
