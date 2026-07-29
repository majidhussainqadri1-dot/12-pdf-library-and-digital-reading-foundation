<?php
defined('ABSPATH') || exit;

final class SPL_SEO {
    public function hooks() {
        add_action('wp_head', array($this, 'schema'), 40);
        add_filter('wp_robots', array($this, 'robots'));
    }

    public function schema() {
        if (!is_singular(SPL_Helpers::TYPE) || 'publish' !== get_post_status(get_queried_object_id())) {
            return;
        }
        $post_id = get_queried_object_id();
        $type = 'book' === sanitize_title(SPL_Helpers::term($post_id, SPL_Helpers::DOCTYPE)) ? 'Book' : 'DigitalDocument';
        $data = array(
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => get_the_title($post_id),
            'author' => array('@type' => 'Person', 'name' => SPL_Helpers::meta($post_id, 'author_name')),
            'description' => get_the_excerpt($post_id),
            'inLanguage' => SPL_Helpers::meta($post_id, 'language'),
            'datePublished' => SPL_Helpers::meta($post_id, 'publication_year'),
            'isbn' => SPL_Helpers::meta($post_id, 'isbn'),
            'image' => get_the_post_thumbnail_url($post_id, 'full'),
        );
        echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    public function robots($robots) {
        $map = (array) get_option('spl_page_map', array());
        foreach (array('upload', 'saved', 'reading') as $key) {
            if (!empty($map[$key]) && is_page($map[$key])) {
                $robots['noindex'] = true;
                $robots['noarchive'] = true;
            }
        }
        if (is_singular(SPL_Helpers::TYPE) && 'publish' !== get_post_status(get_queried_object_id())) {
            $robots['noindex'] = true;
            $robots['noarchive'] = true;
        }
        return $robots;
    }
}
