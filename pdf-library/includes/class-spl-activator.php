<?php
defined('ABSPATH') || exit;

final class SPL_Activator {
    const DB_VERSION = '0.2.0';

    public static function register() {
        register_post_type(
            SPL_Helpers::TYPE,
            array(
                'labels' => array('name' => 'PDF Library', 'singular_name' => 'Library Document'),
                'public' => true,
                'show_ui' => true,
                'show_in_rest' => false,
                'has_archive' => 'library-documents',
                'rewrite' => array('slug' => 'library-document'),
                'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions'),
                'taxonomies' => array(SPL_Helpers::TAX, SPL_Helpers::DOCTYPE),
            )
        );
        register_taxonomy(
            SPL_Helpers::TAX,
            SPL_Helpers::TYPE,
            array('public' => true, 'show_ui' => false, 'hierarchical' => true, 'rewrite' => array('slug' => 'library-category'))
        );
        register_taxonomy(
            SPL_Helpers::DOCTYPE,
            SPL_Helpers::TYPE,
            array('public' => true, 'show_ui' => false, 'hierarchical' => true, 'rewrite' => array('slug' => 'document-type'))
        );
    }

    public static function activate() {
        self::register();

        $administrator = get_role('administrator');
        if ($administrator) {
            $administrator->add_cap('manage_pdf_library');
        }

        foreach (array(SPL_Helpers::TAX => SPL_Helpers::categories(), SPL_Helpers::DOCTYPE => SPL_Helpers::types()) as $taxonomy => $items) {
            foreach ($items as $slug => $name) {
                if (!get_term_by('slug', $slug, $taxonomy)) {
                    wp_insert_term($name, $taxonomy, array('slug' => $slug));
                }
            }
        }

        self::upgrade_schema();
        self::create_pages();
        SPL_Helpers::storage();
        set_transient('spl_notice', '1', 120);
        flush_rewrite_rules();
    }

    public static function maybe_upgrade() {
        if (self::DB_VERSION !== get_option('spl_db_version')) {
            self::upgrade_schema();
        }
    }

    public static function upgrade_schema() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collation = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$wpdb->prefix}spl_user_data (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            document_id bigint unsigned NOT NULL,
            data_type varchar(20) NOT NULL,
            item_key varchar(64) NOT NULL DEFAULT '',
            page_number int unsigned NOT NULL DEFAULT 0,
            note text NOT NULL,
            reaction varchar(10) NOT NULL,
            progress int unsigned NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_document (user_id,document_id),
            KEY data_type (data_type)
        ) {$collation};");

        dbDelta("CREATE TABLE {$wpdb->prefix}spl_reports (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            document_id bigint unsigned NOT NULL,
            reason varchar(40) NOT NULL,
            details text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'open',
            created_at datetime NOT NULL,
            updated_at datetime NULL,
            PRIMARY KEY  (id),
            KEY document_id (document_id),
            KEY status (status),
            KEY user_id (user_id)
        ) {$collation};");

        dbDelta("CREATE TABLE {$wpdb->prefix}spl_audit (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            object_type varchar(30) NOT NULL,
            object_id bigint unsigned NOT NULL,
            action varchar(40) NOT NULL,
            actor_id bigint unsigned NOT NULL,
            note text NOT NULL,
            old_value varchar(100) NOT NULL,
            new_value varchar(100) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY object_lookup (object_type,object_id),
            KEY actor_id (actor_id),
            KEY created_at (created_at)
        ) {$collation};");

        self::migrate_user_data();
        if (!self::ensure_unique_index()) {
            set_transient('spl_schema_error', $wpdb->last_error ?: 'The PDF Library unique-state index could not be created.', 10 * MINUTE_IN_SECONDS);
            return false;
        }
        self::migrate_counters();
        update_option('spl_db_version', self::DB_VERSION, false);
        delete_transient('spl_schema_error');
        return true;
    }

    private static function migrate_user_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'spl_user_data';
        $column = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", 'item_key'));
        if (!$column) {
            return;
        }

        $wpdb->query("UPDATE {$table} SET item_key='singleton' WHERE item_key='' AND data_type IN ('save','reaction','progress')");
        $wpdb->query("UPDATE {$table} SET item_key=CONCAT('page-',page_number) WHERE item_key='' AND data_type='bookmark'");
        $wpdb->query("UPDATE {$table} SET item_key=CONCAT('note-',id) WHERE item_key='' AND data_type='note'");
        $wpdb->query("UPDATE {$table} SET item_key=CONCAT(data_type,'-',id) WHERE item_key=''");

        $duplicates = $wpdb->get_results("SELECT user_id,document_id,data_type,item_key,MAX(id) keep_id,COUNT(*) total FROM {$table} GROUP BY user_id,document_id,data_type,item_key HAVING total>1");
        foreach ($duplicates as $duplicate) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE user_id=%d AND document_id=%d AND data_type=%s AND item_key=%s AND id<>%d",
                $duplicate->user_id,
                $duplicate->document_id,
                $duplicate->data_type,
                $duplicate->item_key,
                $duplicate->keep_id
            ));
        }
    }

    private static function ensure_unique_index() {
        global $wpdb;
        $table = $wpdb->prefix . 'spl_user_data';
        $index = $wpdb->get_var("SHOW INDEX FROM {$table} WHERE Key_name='unique_item'");
        if (!$index) {
            $result = $wpdb->query("ALTER TABLE {$table} ADD UNIQUE KEY unique_item (user_id,document_id,data_type,item_key)");
            if (false === $result) {
                return false;
            }
        }
        return (bool) $wpdb->get_var("SHOW INDEX FROM {$table} WHERE Key_name='unique_item'");
    }

    private static function migrate_counters() {
        global $wpdb;
        $wpdb->query("INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
            SELECT legacy.post_id,'_spl_views',legacy.meta_value FROM {$wpdb->postmeta} legacy
            LEFT JOIN {$wpdb->postmeta} current ON current.post_id=legacy.post_id AND current.meta_key='_spl_views'
            WHERE legacy.meta_key='_spl_reads' AND current.meta_id IS NULL");

        $document_ids = get_posts(array(
            'post_type' => SPL_Helpers::TYPE,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ));
        foreach ($document_ids as $document_id) {
            SPL_Helpers::update_counter($document_id, 'save');
        }
    }

    private static function create_pages() {
        $foundation_map = (array) get_option('spf_page_map', array());
        $map = array();
        $map['library'] = self::page(!empty($foundation_map['pdf']) ? absint($foundation_map['pdf']) : 0, 'PDF Library', 'pdf-library', '[spl_library]');
        $map['upload'] = self::page(0, 'Upload Library Document', 'upload-library-document', '[spl_upload]');
        $map['saved'] = self::page(0, 'Saved Documents', 'saved-documents', '[spl_saved]');
        $map['reading'] = self::page(0, 'Reading Workspace', 'reading-workspace', '[spl_reading_workspace]');
        update_option('spl_page_map', $map, false);
        $foundation_map['pdf'] = $map['library'];
        update_option('spf_page_map', $foundation_map, false);
    }

    private static function page($preferred_id, $title, $slug, $shortcode) {
        $post = $preferred_id ? get_post($preferred_id) : get_page_by_path($slug);
        if ($post instanceof WP_Post && self::is_managed_page($post, $shortcode)) {
            $result = wp_update_post(array('ID' => $post->ID, 'post_content' => $shortcode), true);
            if (!is_wp_error($result)) {
                update_post_meta($post->ID, '_spl_managed', '1');
                return (int) $post->ID;
            }
        }

        $existing = get_page_by_path($slug);
        if ($existing instanceof WP_Post && !self::is_managed_page($existing, $shortcode)) {
            $slug = wp_unique_post_slug($slug, 0, 'publish', 'page', 0);
        }

        $id = wp_insert_post(
            array(
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => $shortcode,
                'post_status' => 'publish',
                'post_type' => 'page',
            ),
            true
        );
        if (is_wp_error($id)) {
            return 0;
        }
        update_post_meta($id, '_spl_managed', '1');
        return (int) $id;
    }

    private static function is_managed_page($post, $shortcode) {
        return (bool) get_post_meta($post->ID, '_spl_managed', true)
            || (bool) get_post_meta($post->ID, '_spf_managed_page', true)
            || false !== strpos((string) $post->post_content, $shortcode);
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
