<?php
defined('ABSPATH') || exit;

final class SPL_Frontend {
    public function hooks() {
        add_shortcode('spl_library', array($this, 'library'));
        add_shortcode('spl_upload', array($this, 'form'));
        add_shortcode('spl_saved', array($this, 'saved'));
        add_shortcode('spl_reading_workspace', array($this, 'workspace'));
        add_action('admin_post_spl_submit', array($this, 'precheck'), 9);
        add_action('admin_post_spl_submit', array($this, 'submit'));
        add_action('admin_post_spl_stream', array($this, 'stream'));
        add_action('admin_post_nopriv_spl_stream', array($this, 'stream'));
        add_filter('the_content', array($this, 'single'), 20);
        add_action('pre_comment_on_post', array($this, 'comments'));
    }

    public function library() {
        $search = sanitize_text_field(wp_unslash($_GET['library_search'] ?? ''));
        $category = sanitize_title(wp_unslash($_GET['library_category'] ?? ''));
        $type = sanitize_title(wp_unslash($_GET['document_type'] ?? ''));
        $sort = sanitize_key(wp_unslash($_GET['library_sort'] ?? 'latest'));
        $page = max(1, absint($_GET['library_page'] ?? 1));

        $args = array(
            'post_type' => SPL_Helpers::TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 24,
            'paged' => $page,
            'spl_library_search' => $search,
        );

        $tax_query = array('relation' => 'AND');
        if (isset(SPL_Helpers::categories()[$category])) {
            $tax_query[] = array('taxonomy' => SPL_Helpers::TAX, 'field' => 'slug', 'terms' => $category);
        }
        if (isset(SPL_Helpers::types()[$type])) {
            $tax_query[] = array('taxonomy' => SPL_Helpers::DOCTYPE, 'field' => 'slug', 'terms' => $type);
        }
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        if ('read' === $sort) {
            $args['meta_key'] = '_spl_views';
            $args['orderby'] = array('meta_value_num' => 'DESC', 'date' => 'DESC');
        } elseif ('saved' === $sort) {
            $args['meta_key'] = '_spl_saves';
            $args['orderby'] = array('meta_value_num' => 'DESC', 'date' => 'DESC');
        } else {
            $sort = 'latest';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        add_filter('posts_search', array($this, 'library_search_sql'), 10, 2);
        $query = new WP_Query($args);
        remove_filter('posts_search', array($this, 'library_search_sql'), 10);

        $featured = get_posts(array(
            'post_type' => SPL_Helpers::TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_key' => '_spl_featured',
            'meta_value' => '1',
        ));
        $map = (array) get_option('spl_page_map', array());

        ob_start();
        ?>
        <main class="spl-shell">
            <?php echo SPL_Helpers::nav(); ?>
            <header class="spl-hero">
                <div>
                    <span>Global Digital Reading</span>
                    <h1>PDF Library</h1>
                    <p>Read responsible books, research, references, and educational documents online.</p>
                </div>
                <form method="get">
                    <input name="library_search" value="<?php echo esc_attr($search); ?>" placeholder="Search title, author, ISBN, or keywords">
                    <button type="submit">Search</button>
                </form>
            </header>

            <?php if ($featured) : ?>
                <section class="spl-featured">
                    <?php echo get_the_post_thumbnail($featured[0], 'medium', array('alt' => $featured[0]->post_title)); ?>
                    <div>
                        <span>Featured Document</span>
                        <h2><a href="<?php echo esc_url(get_permalink($featured[0])); ?>"><?php echo esc_html($featured[0]->post_title); ?></a></h2>
                        <p><?php echo esc_html($featured[0]->post_excerpt); ?></p>
                    </div>
                </section>
            <?php endif; ?>

            <nav class="spl-categories" aria-label="PDF Library categories">
                <a href="<?php echo esc_url(remove_query_arg(array('library_category', 'library_page'))); ?>">All</a>
                <?php foreach (SPL_Helpers::categories() as $key => $label) : ?>
                    <a href="<?php echo esc_url(add_query_arg(array('library_category' => $key, 'library_page' => false))); ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>

            <form class="spl-filters" method="get">
                <input type="hidden" name="library_search" value="<?php echo esc_attr($search); ?>">
                <select name="document_type">
                    <option value="">All document types</option>
                    <?php foreach (SPL_Helpers::types() as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($type, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="library_category">
                    <option value="">All categories</option>
                    <?php foreach (SPL_Helpers::categories() as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($category, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="library_sort">
                    <option value="latest" <?php selected($sort, 'latest'); ?>>Latest</option>
                    <option value="read" <?php selected($sort, 'read'); ?>>Most read</option>
                    <option value="saved" <?php selected($sort, 'saved'); ?>>Most saved</option>
                </select>
                <button type="submit">Apply</button>
                <?php if (SPL_Helpers::can_submit() && !empty($map['upload'])) : ?>
                    <a href="<?php echo esc_url(get_permalink($map['upload'])); ?>">Upload Library Document</a>
                <?php endif; ?>
            </form>

            <div class="spl-grid">
                <?php
                if ($query->have_posts()) {
                    foreach ($query->posts as $item) {
                        echo $this->card($item);
                    }
                } else {
                    echo '<p class="spl-empty">No matching library documents were found.</p>';
                }
                ?>
            </div>

            <?php
            $pagination = paginate_links(array(
                'base' => str_replace('999999999', '%#%', esc_url_raw(add_query_arg('library_page', 999999999))),
                'format' => '',
                'current' => $page,
                'total' => max(1, (int) $query->max_num_pages),
                'type' => 'list',
            ));
            if ($pagination) {
                echo '<nav class="spl-pagination" aria-label="PDF Library pages">' . wp_kses_post($pagination) . '</nav>';
            }
            ?>

            <p class="spl-disclaimer">Educational information only. Historical and complementary-health documents do not replace current diagnosis, emergency care, or qualified medical advice.</p>
        </main>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function library_search_sql($search_sql, $query) {
        global $wpdb;
        $term = $query->get('spl_library_search');
        if (!$term) {
            return $search_sql;
        }

        $like = '%' . $wpdb->esc_like($term) . '%';
        $meta_keys = array('_spl_author_name', '_spl_isbn', '_spl_keywords');
        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        $params = array_merge(array($like, $like, $like), $meta_keys, array($like));

        return $wpdb->prepare(
            " AND (
                {$wpdb->posts}.post_title LIKE %s
                OR {$wpdb->posts}.post_excerpt LIKE %s
                OR {$wpdb->posts}.post_content LIKE %s
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} spl_pm
                    WHERE spl_pm.post_id={$wpdb->posts}.ID
                    AND spl_pm.meta_key IN ({$placeholders})
                    AND spl_pm.meta_value LIKE %s
                )
            ) ",
            $params
        );
    }

    private function card($post) {
        ob_start();
        ?>
        <article class="spl-card">
            <a class="spl-cover" href="<?php echo esc_url(get_permalink($post)); ?>">
                <?php echo get_the_post_thumbnail($post, 'medium', array('loading' => 'lazy', 'alt' => $post->post_title)); ?>
            </a>
            <div>
                <span><?php echo esc_html(SPL_Helpers::term($post->ID, SPL_Helpers::DOCTYPE)); ?></span>
                <h2><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a></h2>
                <p><?php echo esc_html(SPL_Helpers::meta($post->ID, 'author_name')); ?> · <?php echo absint(SPL_Helpers::meta($post->ID, 'pages')); ?> pages</p>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    public function form() {
        if (!is_user_logged_in() || !SPL_Helpers::can_submit()) {
            return '<div class="spl-note">Only the Founder, administrators, and fully verified doctors may upload documents.</div>';
        }

        $health = SPL_Helpers::upload_blockers();
        if ($health) {
            return '<div class="spl-note spl-error"><strong>Uploads are temporarily blocked:</strong><ul><li>'
                . implode('</li><li>', array_map('esc_html', $health))
                . '</li></ul></div>';
        }

        ob_start();
        ?>
        <main class="spl-shell">
            <h1>Upload Library Document</h1>
            <form class="spl-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="spl_submit">
                <?php wp_nonce_field('spl_submit', 'spl_nonce'); ?>

                <?php
                $fields = array(
                    'title' => 'Document title',
                    'subtitle' => 'Subtitle',
                    'author_name' => 'Author name',
                    'contributor' => 'Contributor',
                    'publication_year' => 'Publication year',
                    'edition' => 'Edition',
                    'publisher' => 'Publisher',
                    'isbn' => 'ISBN',
                    'pages' => 'Number of pages',
                    'language' => 'Language',
                    'keywords' => 'Keywords',
                );
                foreach ($fields as $key => $label) :
                    $required = in_array($key, array('title', 'author_name', 'pages', 'language'), true);
                    ?>
                    <label><?php echo esc_html($label); ?>
                        <input name="<?php echo esc_attr($key); ?>" <?php echo $required ? 'required' : ''; ?>>
                    </label>
                <?php endforeach; ?>

                <label>Document type
                    <select name="document_type">
                        <?php foreach (SPL_Helpers::types() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Category
                    <select name="category">
                        <?php foreach (SPL_Helpers::categories() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Cover image<input type="file" name="cover" accept="image/jpeg,image/png,image/webp" required></label>
                <label>PDF file<input type="file" name="pdf" accept="application/pdf" required></label>
                <label class="wide">Short description<textarea name="excerpt" required></textarea></label>
                <label class="wide">Complete description<textarea name="description" required></textarea></label>
                <label class="wide">Table of contents<textarea name="contents"></textarea></label>
                <label class="wide">References<textarea name="references" required></textarea></label>
                <label class="wide">Medical safety notice<textarea name="safety" required></textarea></label>
                <label>Copyright status
                    <select name="copyright_status">
                        <option value="owned">I Own the Copyright</option>
                        <option value="permission">I Have Written Permission</option>
                        <option value="public-domain">Public Domain</option>
                        <option value="open-license">Open License</option>
                        <option value="platform">Official Platform Publication</option>
                    </select>
                </label>
                <label><input type="checkbox" name="download_allowed" value="1"> Reading and download allowed</label>
                <label class="wide"><input type="checkbox" name="rights" required> I have the legal right to publish this document; it is not an unauthorized scan.</label>
                <label class="wide"><input type="checkbox" name="medical" required> I confirm the medical-safety, patient-privacy, and educational limitations.</label>
                <label class="wide"><input type="checkbox" name="patient_case" value="1"> If this is a Patient Case, all identifying information is removed and valid publication consent has been obtained.</label>
                <button type="submit">Submit Document</button>
            </form>
        </main>
        <?php
        return ob_get_clean();
    }

    public function precheck() {
        check_admin_referer('spl_submit', 'spl_nonce');
        if (!is_user_logged_in() || !SPL_Helpers::can_submit()) {
            wp_die('Access denied.', array('response' => 403));
        }
        if (!SPL_Helpers::rate_limit('submit', 10, HOUR_IN_SECONDS)) {
            wp_die('Please wait before submitting another document.', array('response' => 429));
        }

        $health = SPL_Helpers::upload_blockers();
        if ($health) {
            wp_die(esc_html(implode(' ', $health)), array('response' => 503));
        }

        $required_fields = array('title', 'author_name', 'excerpt', 'description', 'references', 'safety', 'language');
        $public_text = '';
        foreach ($required_fields as $key) {
            $value = isset($_POST[$key]) ? wp_strip_all_tags(wp_unslash($_POST[$key])) : '';
            if (!trim($value)) {
                wp_die('Complete every required public field.');
            }
            $public_text .= ' ' . $value;
        }

        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{0900}-\x{097F}\x{0400}-\x{04FF}\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}]/u', $public_text)) {
            wp_die('This release accepts American English public content only.');
        }

        $category = sanitize_title(wp_unslash($_POST['category'] ?? ''));
        if ('platform-publications' === $category && !SPL_Helpers::founder() && !current_user_can('manage_pdf_library')) {
            wp_die('Platform Publications is reserved for official publishing.');
        }
        if ('patient-cases' === $category && empty($_POST['patient_case'])) {
            wp_die('Patient Case documents require anonymity and valid consent confirmation.');
        }
    }

    public function submit() {
        check_admin_referer('spl_submit', 'spl_nonce');
        if (!is_user_logged_in() || !SPL_Helpers::can_submit()) {
            wp_die('Access denied.', array('response' => 403));
        }

        $data = $this->validate_submission();
        if (is_wp_error($data)) {
            wp_die(esc_html($data->get_error_message()), array('response' => 400));
        }

        $post_id = 0;
        $cover_id = 0;
        $encrypted_path = '';
        $storage_name = wp_generate_uuid4() . '.spl';

        try {
            $post_id = wp_insert_post(
                array(
                    'post_type' => SPL_Helpers::TYPE,
                    'post_status' => 'draft',
                    'post_title' => $data['title'],
                    'post_excerpt' => $data['excerpt'],
                    'post_content' => $data['description'],
                    'post_author' => get_current_user_id(),
                    'comment_status' => 'open',
                ),
                true
            );
            if (is_wp_error($post_id)) {
                throw new RuntimeException($post_id->get_error_message());
            }

            $type_result = wp_set_object_terms($post_id, $data['document_type'], SPL_Helpers::DOCTYPE);
            $category_result = wp_set_object_terms($post_id, $data['category'], SPL_Helpers::TAX);
            if (is_wp_error($type_result) || is_wp_error($category_result)) {
                throw new RuntimeException('The document classification could not be saved.');
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $cover_id = media_handle_upload(
                'cover',
                $post_id,
                array(),
                array(
                    'test_form' => false,
                    'mimes' => array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'),
                )
            );
            if (is_wp_error($cover_id)) {
                throw new RuntimeException('The required cover image could not be uploaded: ' . $cover_id->get_error_message());
            }
            set_post_thumbnail($post_id, $cover_id);

            $path = SPL_Helpers::storage_path($storage_name);
            if (is_wp_error($path)) {
                throw new RuntimeException($path->get_error_message());
            }
            $temporary_path = $path . '.tmp-' . wp_generate_password(12, false, false);
            $crypto_meta = array();
            $crypto_error = '';
            if (!SPL_Crypto::encrypt_file($data['pdf_tmp'], $temporary_path, $crypto_meta, $crypto_error)) {
                throw new RuntimeException($crypto_error ?: 'PDF encryption failed.');
            }
            if (!@rename($temporary_path, $path)) {
                @unlink($temporary_path);
                throw new RuntimeException('The encrypted PDF could not be committed atomically to private storage.');
            }
            $encrypted_path = $path;

            foreach ($data['metadata'] as $key => $value) {
                update_post_meta($post_id, '_spl_' . $key, $value);
            }
            foreach (array(
                'storage_name' => $storage_name,
                'original_name' => $data['original_name'],
                'file_size' => $data['file_size'],
                'download_allowed' => $data['download_allowed'],
                'crypto_format' => $crypto_meta['format'],
                'crypto_key_id' => $crypto_meta['key_id'],
                'views' => 0,
                'saves' => 0,
            ) as $key => $value) {
                update_post_meta($post_id, '_spl_' . $key, $value);
            }

            $final_status = SPL_Helpers::submission_status();
            $updated = wp_update_post(array('ID' => $post_id, 'post_status' => $final_status), true);
            if (is_wp_error($updated)) {
                throw new RuntimeException($updated->get_error_message());
            }

            SPL_Helpers::audit('document', $post_id, 'submitted', 'Document uploaded and encrypted.', 'draft', $final_status);
        } catch (Throwable $exception) {
            if ($encrypted_path && is_file($encrypted_path)) {
                @unlink($encrypted_path);
            }
            if ($cover_id && !is_wp_error($cover_id)) {
                wp_delete_attachment($cover_id, true);
            }
            if ($post_id && !is_wp_error($post_id)) {
                wp_delete_post($post_id, true);
            }
            wp_die(esc_html($exception->getMessage()), array('response' => 500));
        }

        $map = (array) get_option('spl_page_map', array());
        $redirect = !empty($map['upload']) ? get_permalink($map['upload']) : get_permalink($post_id);
        wp_safe_redirect(add_query_arg('spl_submitted', 1, $redirect));
        exit;
    }

    private function validate_submission() {
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $type = sanitize_title(wp_unslash($_POST['document_type'] ?? ''));
        $category = sanitize_title(wp_unslash($_POST['category'] ?? ''));
        $pages = absint($_POST['pages'] ?? 0);
        $year = absint($_POST['publication_year'] ?? 0);
        $copyright = sanitize_key(wp_unslash($_POST['copyright_status'] ?? ''));
        $allowed_copyright = array('owned', 'permission', 'public-domain', 'open-license', 'platform');

        if (!$title || !isset(SPL_Helpers::types()[$type]) || !isset(SPL_Helpers::categories()[$category])) {
            return new WP_Error('spl_required', 'Complete the title, document type, and category.');
        }
        if (!$pages || $pages > 100000) {
            return new WP_Error('spl_pages', 'Enter a valid positive page count.');
        }
        if ($year && ($year < 1000 || $year > (int) gmdate('Y') + 1)) {
            return new WP_Error('spl_year', 'Enter a valid publication year.');
        }
        if (!in_array($copyright, $allowed_copyright, true)) {
            return new WP_Error('spl_copyright', 'Select a valid copyright status.');
        }
        if (empty($_POST['rights']) || empty($_POST['medical'])) {
            return new WP_Error('spl_confirmations', 'The publishing-rights and medical-safety confirmations are required.');
        }
        if ('patient-cases' === $category && empty($_POST['patient_case'])) {
            return new WP_Error('spl_patient_case', 'Patient Case documents require anonymity and valid consent confirmation.');
        }
        if (empty($_FILES['pdf']['tmp_name']) || empty($_FILES['cover']['tmp_name'])) {
            return new WP_Error('spl_files', 'Both the PDF and cover image are required.');
        }

        $file = $_FILES['pdf'];
        $maximum = min(100 * MB_IN_BYTES, wp_max_upload_size());
        if (UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] < 5 || (int) $file['size'] > $maximum) {
            return new WP_Error('spl_pdf_size', 'The PDF is invalid or exceeds the upload limit.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('spl_pdf_upload', 'The PDF upload could not be verified.');
        }

        $head = file_get_contents($file['tmp_name'], false, null, 0, 5);
        $checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], array('pdf' => 'application/pdf'));
        if ('%PDF-' !== $head || 'pdf' !== strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) || empty($checked['ext']) || 'pdf' !== $checked['ext']) {
            return new WP_Error('spl_pdf_type', 'Only a genuine PDF file is accepted.');
        }

        $isbn = preg_replace('/[^0-9Xx-]/', '', (string) wp_unslash($_POST['isbn'] ?? ''));
        if ($isbn && !preg_match('/^(?:\d{9}[\dXx]|\d{13}|[\dXx-]{10,17})$/', $isbn)) {
            return new WP_Error('spl_isbn', 'Enter a valid ISBN-10 or ISBN-13 value, or leave the field empty.');
        }

        $metadata = array();
        $fields = array('subtitle', 'author_name', 'contributor', 'edition', 'publisher', 'language', 'keywords', 'contents', 'references', 'safety');
        foreach ($fields as $key) {
            $metadata[$key] = sanitize_textarea_field(wp_unslash($_POST[$key] ?? ''));
        }
        $metadata['publication_year'] = $year ?: '';
        $metadata['isbn'] = strtoupper($isbn);
        $metadata['pages'] = $pages;
        $metadata['copyright_status'] = $copyright;
        $metadata['patient_case_confirmed'] = !empty($_POST['patient_case']) ? '1' : '0';

        return array(
            'title' => $title,
            'document_type' => $type,
            'category' => $category,
            'excerpt' => sanitize_textarea_field(wp_unslash($_POST['excerpt'] ?? '')),
            'description' => wp_kses_post(wp_unslash($_POST['description'] ?? '')),
            'metadata' => $metadata,
            'pdf_tmp' => $file['tmp_name'],
            'original_name' => sanitize_file_name($file['name']),
            'file_size' => (int) $file['size'],
            'download_allowed' => empty($_POST['download_allowed']) ? '0' : '1',
        );
    }

    public function stream() {
        $post_id = absint($_GET['document_id'] ?? 0);
        $download = !empty($_GET['download']);
        if (SPL_Helpers::TYPE !== get_post_type($post_id)) {
            wp_die('Document unavailable.', array('response' => 404));
        }

        $status = get_post_status($post_id);
        if ('publish' !== $status) {
            if (!is_user_logged_in() || !current_user_can('edit_post', $post_id)) {
                wp_die('Document unavailable.', array('response' => 404));
            }
            check_admin_referer('spl_private_stream_' . $post_id);
        }

        if ($download && '1' !== SPL_Helpers::meta($post_id, 'download_allowed', '0')) {
            wp_die('Download is not permitted.', array('response' => 403));
        }

        $path = SPL_Helpers::storage_path(SPL_Helpers::meta($post_id, 'storage_name'));
        if (is_wp_error($path)) {
            wp_die(esc_html($path->get_error_message()), array('response' => 503));
        }

        $inspect_error = '';
        $metadata = SPL_Crypto::inspect($path, $inspect_error);
        if (!$metadata) {
            wp_die(esc_html($inspect_error ?: 'The encrypted PDF could not be opened.'), array('response' => 500));
        }

        $verification_error = '';
        $verified = SPL_Crypto::stream_file($path, function ($chunk) {}, $metadata, $verification_error);
        if (!$verified) {
            wp_die(esc_html($verification_error ?: 'The encrypted PDF failed integrity verification.'), array('response' => 500));
        }

        while (ob_get_level()) {
            ob_end_clean();
        }
        nocache_headers();
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . sanitize_file_name(SPL_Helpers::meta($post_id, 'original_name', 'document.pdf')) . '"');
        header('Content-Length: ' . (int) $metadata['original_size']);

        $stream_error = '';
        $ok = SPL_Crypto::stream_file(
            $path,
            function ($chunk) {
                echo $chunk;
                flush();
            },
            $metadata,
            $stream_error
        );
        if (!$ok) {
            error_log('SPL PDF stream failure: ' . $stream_error);
        }
        exit;
    }

    public function single($content) {
        if (!is_singular(SPL_Helpers::TYPE) || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        update_post_meta($post_id, '_spl_views', absint(SPL_Helpers::meta($post_id, 'views')) + 1);
        $reader_url = SPL_Helpers::stream_url($post_id);
        if ('publish' !== get_post_status($post_id) && current_user_can('edit_post', $post_id)) {
            $reader_url = wp_nonce_url($reader_url, 'spl_private_stream_' . $post_id);
        }

        $reader = '<div class="spl-reader"><iframe title="PDF reader for ' . esc_attr(get_the_title()) . '" src="' . esc_url($reader_url) . '"></iframe></div>';
        $meta = '<section class="spl-details">'
            . '<p><b>Author:</b> ' . esc_html(SPL_Helpers::meta($post_id, 'author_name')) . '</p>'
            . '<p><b>Type:</b> ' . esc_html(SPL_Helpers::term($post_id, SPL_Helpers::DOCTYPE)) . '</p>'
            . '<p><b>Pages:</b> ' . absint(SPL_Helpers::meta($post_id, 'pages')) . '</p>'
            . '<p><b>Language:</b> ' . esc_html(SPL_Helpers::meta($post_id, 'language')) . '</p>'
            . '</section>';
        if ('1' === SPL_Helpers::meta($post_id, 'download_allowed')) {
            $download_url = SPL_Helpers::stream_url($post_id, true);
            if ('publish' !== get_post_status($post_id) && current_user_can('edit_post', $post_id)) {
                $download_url = wp_nonce_url($download_url, 'spl_private_stream_' . $post_id);
            }
            $meta .= '<a class="spl-download" href="' . esc_url($download_url) . '">Download PDF</a>';
        }

        $tail = SPL_Interactions::controls($post_id)
            . '<section class="spl-panel"><h2>Table of Contents</h2><p>' . nl2br(esc_html(SPL_Helpers::meta($post_id, 'contents'))) . '</p></section>'
            . '<section class="spl-panel"><h2>References</h2><p>' . nl2br(esc_html(SPL_Helpers::meta($post_id, 'references'))) . '</p></section>'
            . '<section class="spl-panel spl-safety"><h2>Medical Safety Notice</h2><p>' . nl2br(esc_html(SPL_Helpers::meta($post_id, 'safety'))) . '</p></section>';

        return $reader . $meta . $content . $tail;
    }

    public function saved() {
        return $this->user_items('Saved Documents', 'save');
    }

    public function workspace() {
        return $this->user_items('Reading Workspace', 'progress');
    }

    private function user_items($title, $type) {
        if (!is_user_logged_in()) {
            return '<div>Log in first.</div>';
        }

        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT document_id FROM {$wpdb->prefix}spl_user_data WHERE user_id=%d AND data_type=%s ORDER BY updated_at DESC",
            get_current_user_id(),
            $type
        ));
        $items = get_posts(array(
            'post_type' => SPL_Helpers::TYPE,
            'post_status' => 'publish',
            'post__in' => $ids ?: array(0),
            'orderby' => 'post__in',
            'posts_per_page' => 100,
        ));

        $output = '<main class="spl-shell"><h1>' . esc_html($title) . '</h1><div class="spl-grid">';
        foreach ($items as $item) {
            $output .= $this->card($item);
        }
        return $output . '</div></main>';
    }

    public function comments($post_id) {
        if (SPL_Helpers::TYPE === get_post_type($post_id) && !is_user_logged_in()) {
            wp_die('Log in to comment.', array('response' => 403));
        }
    }
}
