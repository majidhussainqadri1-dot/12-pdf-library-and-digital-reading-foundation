<?php

defined('ABSPATH') || exit;

final class PLDR_Search {
    public static function search(array $args, int $user_id = 0): array {
        global $wpdb;
        $term = PLDR_Core::normalize_search((string) ($args['q'] ?? ''));
        $type = sanitize_key((string) ($args['type'] ?? ''));
        $category = sanitize_key((string) ($args['category'] ?? ''));
        $language = sanitize_text_field((string) ($args['language'] ?? ''));
        $page = max(1, absint($args['page'] ?? 1));
        $per_page = min(48, max(1, absint($args['per_page'] ?? 24)));
        $logical_offset = ($page - 1) * $per_page;
        $where = array("d.status='published'");
        $base_params = array();
        if ($term) { $where[] = 'd.search_text LIKE %s'; $base_params[] = '%' . $wpdb->esc_like($term) . '%'; }
        if ($type && isset(PLDR_Core::DOCUMENT_TYPES[$type])) { $where[] = 'd.document_type=%s'; $base_params[] = $type; }
        if ($category && isset(PLDR_Core::CATEGORIES[$category])) { $where[] = 'd.category=%s'; $base_params[] = $category; }
        if ($language) { $where[] = 'd.language=%s'; $base_params[] = $language; }

        // Entitlements can only be resolved per edition/user, so page offsets must be
        // applied after access filtering. Otherwise page N can overlap page N+1 when
        // inaccessible raw rows are skipped. Scan in bounded batches from the ordered
        // start and expose truncation rather than silently claiming completeness.
        $batch_size = min(200, max(48, $per_page * 4));
        $target = $logical_offset + $per_page + 1;
        $suggested_limit = max(2000, $target * 8);
        $scan_limit_provider_failed = false;
        try {
            $scan_limit = (int) apply_filters('pldr_search_scan_limit', $suggested_limit, $args, $user_id);
        } catch (Throwable $e) {
            $scan_limit = $suggested_limit;
            $scan_limit_provider_failed = true;
            PLDR_Core::audit('search', 0, 'catalog_scan_limit_provider_failed', array('provider_failure'=>true), $user_id);
        }
        $scan_limit = max($batch_size, min(20000, $scan_limit));
        $raw_offset = 0;
        $eligible = array();
        $scan_truncated = false;

        while (count($eligible) < $target && $raw_offset < $scan_limit) {
            $limit = min($batch_size, $scan_limit - $raw_offset);
            $sql = 'SELECT d.* FROM ' . PLDR_Core::table('documents') . ' d WHERE ' . implode(' AND ', $where) . ' ORDER BY d.updated_at DESC,d.id DESC LIMIT %d OFFSET %d';
            $params = $base_params;
            $params[] = $limit;
            $params[] = $raw_offset;
            $wpdb->last_error = '';
            $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
            if ('' !== (string) $wpdb->last_error) {
                return array(
                    'items'=>array(),'page'=>$page,'per_page'=>$per_page,'has_more'=>false,
                    'error'=>PLDR_Core::machine_error('pldr_catalog_read','PDF Library catalog state could not be read reliably.',503,array('degraded'=>true)),
                    'degraded'=>true,'scan_limit_provider_failed'=>$scan_limit_provider_failed,
                );
            }
            $rows = is_array($rows) ? $rows : array();
            if (!$rows) break;
            foreach ($rows as $doc) {
                $edition = PLDR_Core::current_edition((int) $doc['id']);
                if (!$edition || !PLDR_Access::can_access_edition((int) $edition['id'], 'read', $user_id)) continue;
                $eligible[] = PLDR_Core::public_document_dto($doc, $edition);
                if (count($eligible) >= $target) break;
            }
            $raw_offset += count($rows);
            if (count($rows) < $limit) break;
        }
        if ($raw_offset >= $scan_limit && count($eligible) < $target) $scan_truncated = true;

        $items = array_slice($eligible, $logical_offset, $per_page);
        $has_more = count($eligible) > ($logical_offset + $per_page) || $scan_truncated;
        return array(
            'items' => $items,
            'page' => $page,
            'per_page' => $per_page,
            'has_more' => $has_more,
            'access_filtered_pagination' => true,
            'scan_truncated' => $scan_truncated,
            'raw_rows_scanned' => $raw_offset,
            'scan_limit_provider_failed' => $scan_limit_provider_failed,
        );
    }

    public static function ocr(int $edition_id, string $query, int $user_id = 0): array {
        global $wpdb;
        if (!PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return array('error'=>PLDR_Core::machine_error('pldr_ocr_forbidden','Document text search is unavailable.',404));
        $needle = PLDR_Core::normalize_search($query);
        $needle_len=function_exists('mb_strlen')?mb_strlen($needle,'UTF-8'):strlen($needle);
        if ($needle_len < 2) return array('error'=>PLDR_Core::machine_error('pldr_ocr_query_short','Document text search requires at least two characters.',400));
        if ($needle_len > 160) return array('error'=>PLDR_Core::machine_error('pldr_ocr_query_long','Document text search query is too long.',400,array('max_characters'=>160)));
        try {
            $variants = apply_filters('pldr_search_variants', array($needle), $needle, $edition_id);
        } catch (Throwable $e) {
            PLDR_Core::audit('edition',$edition_id,'ocr_search_variant_provider_failed',array('provider_failure'=>true),$user_id);
            return array('error'=>PLDR_Core::machine_error('pldr_ocr_variant_provider','Document text-search expansion is temporarily unavailable.',503,array('degraded'=>true,'provider_failure'=>true)));
        }
        $variants = array_values(array_unique(array_filter(array_map(static function($value):string {
            $value=PLDR_Core::normalize_search((string)$value);
            return function_exists('mb_substr')?mb_substr($value,0,160,'UTF-8'):substr($value,0,160);
        }, (array) $variants))));
        $clauses = array(); $params = array();
        foreach (array_slice($variants, 0, 5) as $variant) { if(''===$variant)continue; $clauses[] = 'normalized_text LIKE %s'; $params[] = '%' . $wpdb->esc_like($variant) . '%'; }
        if (!$clauses) return array('error'=>PLDR_Core::machine_error('pldr_ocr_variants','No safe document text-search variant was available.',400));
        array_unshift($params, $edition_id);
        $sql = 'SELECT page_number,language,quality_score,text_content FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d AND (' . implode(' OR ', $clauses) . ') ORDER BY page_number ASC LIMIT 100';
        $wpdb->last_error='';
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_ocr_read','Document OCR search state could not be read reliably.',503,array('degraded'=>true)));
        return array_map(static function (array $row) use ($query): array {
            $text = (string) $row['text_content'];
            $pos = function_exists('mb_stripos') ? mb_stripos($text,$query,0,'UTF-8') : stripos($text,$query);
            $start = false === $pos ? 0 : max(0, $pos - 90);
            $snippet=function_exists('mb_substr')?mb_substr($text,$start,240,'UTF-8'):substr($text,$start,240);
            return array('page' => (int) $row['page_number'], 'language' => $row['language'], 'quality' => (float) $row['quality_score'], 'snippet' => $snippet);
        }, is_array($rows)?$rows:array());
    }
}

final class PLDR_Reading {
    public static function save_progress(int $edition_id, int $page, int $user_id = 0) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id || !PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return PLDR_Core::machine_error('pldr_progress_forbidden', 'Reading progress cannot be saved for this document.', 403);
        $edition = PLDR_Core::edition($edition_id);
        if (!$edition) return PLDR_Core::machine_error('pldr_edition_missing', 'Document edition not found.', 404);
        $pages = max(1, (int) $edition['pages']);
        if ($page < 1 || $page > $pages) return PLDR_Core::machine_error('pldr_page_range', 'Reading page is outside the document.', 400);
        $percent = round(($page / $pages) * 100, 2);
        $ok = $wpdb->replace(PLDR_Core::table('reading_state'), array('user_id' => $user_id, 'edition_id' => $edition_id, 'last_page' => $page, 'percent' => $percent, 'edition_version' => (int) $edition['version'], 'updated_at' => PLDR_Core::now()), array('%d','%d','%d','%f','%d','%s'));
        if (false === $ok) return PLDR_Core::machine_error('pldr_progress_store', 'Reading progress could not be saved.', 500);
        $event=PLDR_Core::emit('ReadingProgressUpdated.v1', 'edition', $edition_id, array('user_id' => $user_id, 'edition_id' => $edition_id, 'page' => $page, 'percent' => $percent));
        if(is_wp_error($event))return PLDR_Core::machine_error('pldr_progress_event_reconcile','Reading progress was saved but its reliable event could not be persisted; reconciliation is required.',503,array('committed'=>true,'edition_id'=>$edition_id,'page'=>$page));
        return array('page' => $page, 'percent' => $percent, 'updated_at' => PLDR_Core::now());
    }

    public static function state(int $edition_id, int $user_id = 0): array {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id || !PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return array('page' => 1, 'percent' => 0);
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . PLDR_Core::table('reading_state') . ' WHERE user_id=%d AND edition_id=%d', $user_id, $edition_id), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('page'=>1,'percent'=>0,'error'=>PLDR_Core::machine_error('pldr_progress_read','Private reading progress could not be read reliably.',503,array('degraded'=>true)));
        return $row ? array('page' => (int) $row['last_page'], 'percent' => (float) $row['percent'], 'updated_at' => $row['updated_at']) : array('page' => 1, 'percent' => 0);
    }

    public static function add_item(int $edition_id, array $data, int $user_id = 0) {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id || !PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return PLDR_Core::machine_error('pldr_item_forbidden', 'Private reading item cannot be saved.', 403);
        $edition = PLDR_Core::edition($edition_id);
        if (!$edition) return PLDR_Core::machine_error('pldr_edition_missing', 'Document edition not found.', 404);
        $type = sanitize_key((string) ($data['type'] ?? 'bookmark'));
        if (!in_array($type, array('bookmark','note','highlight'), true)) return PLDR_Core::machine_error('pldr_item_type', 'Unsupported private reading item type.', 400);
        $page = absint($data['page'] ?? 0);
        if ($page < 1 || $page > (int) $edition['pages']) return PLDR_Core::machine_error('pldr_page_range', 'Reading item page is outside the document.', 400);
        $note = self::limit_text(sanitize_textarea_field((string) ($data['note'] ?? '')),4000);
        if ('note' === $type && '' === trim($note)) return PLDR_Core::machine_error('pldr_note_empty', 'A private note cannot be empty.', 400);
        $anchor=self::limit_text(sanitize_text_field((string)($data['anchor']??'')),500);
        $tags=array_slice(PLDR_Core::sanitize_json_list($data['tags']??array()),0,30);
        $tags=array_map(static fn(string $tag):string=>self::limit_text($tag,80),$tags);
        $tags_json=wp_json_encode($tags);
        if(!is_string($tags_json))return PLDR_Core::machine_error('pldr_item_tags','Private reading-item tags could not be encoded safely.',400);
        $ok = $wpdb->insert(PLDR_Core::table('reading_items'), array('user_id' => $user_id, 'edition_id' => $edition_id, 'item_type' => $type, 'page_number' => $page, 'anchor_text' => $anchor, 'note_text' => $note, 'tags_json' => $tags_json, 'version' => 1, 'created_at' => PLDR_Core::now(), 'updated_at' => PLDR_Core::now()));
        if (false === $ok) return PLDR_Core::machine_error('pldr_item_store', 'Private reading item could not be saved.', 500);
        return array('id' => (int) $wpdb->insert_id, 'type' => $type, 'page' => $page, 'private' => true);
    }

    public static function items(int $edition_id, int $user_id = 0): array {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id || !PLDR_Access::can_access_edition($edition_id, 'read', $user_id)) return array();
        $wpdb->last_error='';
        $rows = $wpdb->get_results($wpdb->prepare('SELECT id,item_type,page_number,anchor_text,note_text,tags_json,version,created_at,updated_at FROM ' . PLDR_Core::table('reading_items') . ' WHERE user_id=%d AND edition_id=%d ORDER BY page_number ASC,id ASC LIMIT 1000', $user_id, $edition_id), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_items_read','Private reading items could not be read reliably.',503,array('degraded'=>true)));
        $rows=is_array($rows)?$rows:array();
        foreach ($rows as &$row) { $row['id']=(int)$row['id']; $row['page_number']=(int)$row['page_number']; $row['version']=(int)$row['version']; $row['tags']=json_decode((string)$row['tags_json'],true)?:array(); unset($row['tags_json']); }
        return $rows ?: array();
    }

    private static function limit_text(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }

    public static function delete_item(int $item_id, int $user_id = 0): bool {
        global $wpdb;
        $user_id = $user_id ?: get_current_user_id();
        return false !== $wpdb->delete(PLDR_Core::table('reading_items'), array('id' => $item_id, 'user_id' => $user_id), array('%d','%d'));
    }

    public static function clear(int $user_id): void {
        global $wpdb;
        $wpdb->delete(PLDR_Core::table('reading_state'), array('user_id' => $user_id), array('%d'));
        $wpdb->delete(PLDR_Core::table('reading_items'), array('user_id' => $user_id), array('%d'));
    }
}

final class PLDR_Reader {
    private const THUMBNAIL_PREVIEW_LIMIT = 300;

    public static function library_html(array $args = array()): string {
        $result = PLDR_Search::search(array_merge($_GET, $args), get_current_user_id());
        if(isset($result['error'])&&is_wp_error($result['error']))return self::state_html('error');
        ob_start();
        ?>
        <main class="pldr-shell" dir="auto">
            <header class="pldr-hero">
                <div><span><?php esc_html_e('Global Digital Reading', 'pdf-library-digital-reading'); ?></span><h1><?php esc_html_e('PDF Library', 'pdf-library-digital-reading'); ?></h1><p><?php esc_html_e('Rights-aware books, research, references and educational documents.', 'pdf-library-digital-reading'); ?></p></div>
                <form method="get" role="search"><label class="screen-reader-text" for="pldr-q"><?php esc_html_e('Search library', 'pdf-library-digital-reading'); ?></label><input id="pldr-q" name="q" value="<?php echo esc_attr((string) ($_GET['q'] ?? '')); ?>" placeholder="<?php esc_attr_e('Title, author, ISBN, subject…', 'pdf-library-digital-reading'); ?>"><button type="submit"><?php esc_html_e('Search', 'pdf-library-digital-reading'); ?></button></form>
            </header>
            <form class="pldr-filters" method="get">
                <input type="hidden" name="q" value="<?php echo esc_attr((string) ($_GET['q'] ?? '')); ?>">
                <select name="type" aria-label="<?php esc_attr_e('Document type', 'pdf-library-digital-reading'); ?>"><option value=""><?php esc_html_e('All document types', 'pdf-library-digital-reading'); ?></option><?php foreach (PLDR_Core::DOCUMENT_TYPES as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected((string)($_GET['type']??''),$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                <select name="category" aria-label="<?php esc_attr_e('Category', 'pdf-library-digital-reading'); ?>"><option value=""><?php esc_html_e('All categories', 'pdf-library-digital-reading'); ?></option><?php foreach (PLDR_Core::CATEGORIES as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected((string)($_GET['category']??''),$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                <input name="language" value="<?php echo esc_attr((string)($_GET['language']??'')); ?>" placeholder="<?php esc_attr_e('Language', 'pdf-library-digital-reading'); ?>">
                <button type="submit"><?php esc_html_e('Apply filters', 'pdf-library-digital-reading'); ?></button>
            </form>
            <section class="pldr-grid" aria-live="polite">
                <?php if (!$result['items']): ?><div class="pldr-empty"><h2><?php esc_html_e('No eligible documents found', 'pdf-library-digital-reading'); ?></h2><p><?php esc_html_e('Try a broader search or different filters.', 'pdf-library-digital-reading'); ?></p></div><?php endif; ?>
                <?php foreach ($result['items'] as $item): ?>
                    <article class="pldr-card"><div class="pldr-card-body"><span class="pldr-kicker"><?php echo esc_html(PLDR_Core::DOCUMENT_TYPES[$item['type']] ?? $item['type']); ?></span><h2><a href="<?php echo esc_url(PLDR_Core::route_url('document', array('id'=>$item['id'],'slug'=>$item['slug']))); ?>"><?php echo esc_html($item['title']); ?></a></h2><p><?php echo esc_html((string)($item['edition']['author']??'')); ?> · <?php echo absint((int)($item['edition']['pages']??0)); ?> <?php esc_html_e('pages', 'pdf-library-digital-reading'); ?></p><p><?php echo esc_html((string)($item['language']??'')); ?></p></div></article>
                <?php endforeach; ?>
            </section>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    public static function document_html(string $public_id): string {
        $doc = PLDR_Core::document_by_public_id($public_id);
        if (!$doc) return self::state_html('not-found');
        $edition = PLDR_Core::current_edition((int)$doc['id']);
        if (!$edition || !PLDR_Access::can_access_edition((int)$edition['id'], 'read', get_current_user_id())) return self::state_html('restricted');
        $dto = PLDR_Core::public_document_dto($doc, $edition);
        $cover = self::cover_token((int)$edition['id']);
        try{$related_html=(string)apply_filters('pldr_related_content_html','',(int)$edition['id'],$dto);}
        catch(Throwable $e){$related_html='';PLDR_Core::audit('edition',(int)$edition['id'],'related_content_provider_failed',array('provider_failure'=>true));}
        ob_start(); ?>
        <main class="pldr-shell pldr-document" dir="auto">
            <nav class="pldr-local-nav" aria-label="<?php esc_attr_e('Document navigation', 'pdf-library-digital-reading'); ?>"><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'pdf-library-digital-reading'); ?></a><a href="<?php echo esc_url(PLDR_Core::route_url('library')); ?>"><?php esc_html_e('PDF Library', 'pdf-library-digital-reading'); ?></a></nav>
            <section class="pldr-document-hero"><?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(sprintf(__('Cover of %s','pdf-library-digital-reading'),$dto['title'])); ?>"><?php endif; ?><div><span class="pldr-kicker"><?php echo esc_html(PLDR_Core::DOCUMENT_TYPES[$dto['type']]??$dto['type']); ?></span><h1><?php echo esc_html($dto['title']); ?></h1><p><?php echo esc_html((string)$dto['edition']['author']); ?></p><dl><dt><?php esc_html_e('Edition','pdf-library-digital-reading'); ?></dt><dd><?php echo esc_html((string)$dto['edition']['label']); ?></dd><dt><?php esc_html_e('Year','pdf-library-digital-reading'); ?></dt><dd><?php echo absint((int)$dto['edition']['year']); ?></dd><dt><?php esc_html_e('Pages','pdf-library-digital-reading'); ?></dt><dd><?php echo absint((int)$dto['edition']['pages']); ?></dd><dt><?php esc_html_e('License','pdf-library-digital-reading'); ?></dt><dd><?php echo esc_html((string)$dto['edition']['license']); ?></dd></dl><div class="pldr-actions"><a class="pldr-primary" href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$dto['id']))); ?>"><?php esc_html_e('Read online','pdf-library-digital-reading'); ?></a><?php if ($dto['permissions']['download']): ?><a class="pldr-primary" href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$dto['id'])) . '#download'); ?>"><?php esc_html_e('Download Manager','pdf-library-digital-reading'); ?></a><?php endif; ?></div></div></section>
            <section class="pldr-panel"><h2><?php esc_html_e('Stable citation','pdf-library-digital-reading'); ?></h2><p><?php echo esc_html(self::citation($edition,1,'sabri')); ?></p></section>
            <?php echo wp_kses_post($related_html); ?>
        </main>
        <?php return (string)ob_get_clean();
    }

    public static function reader_html(string $public_id): string {
        $doc = PLDR_Core::document_by_public_id($public_id);
        if (!$doc) return self::state_html('not-found');
        $edition = PLDR_Core::current_edition((int)$doc['id']);
        $requested_edition=absint($_GET['edition']??0);
        if($requested_edition && (PLDR_Core::authorize('manage',(int)$doc['id'])||PLDR_Core::authorize('rights',(int)$doc['id']))){$candidate=PLDR_Core::edition($requested_edition);if($candidate&&(int)$candidate['document_id']===(int)$doc['id'])$edition=$candidate;}
        if (!$edition || !PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id())) return self::state_html('restricted');
        $object = PLDR_Core::object((int)$edition['object_id']);
        if (!$object) return self::state_html('error');
        $grant = PLDR_Access::issue_token((int)$edition['id'],(int)$object['id'],'read',get_current_user_id(),900);
        if (is_wp_error($grant)) return self::state_html('error');
        $state = PLDR_Reading::state((int)$edition['id']);
        if(isset($state['error'])&&is_wp_error($state['error']))return self::state_html('error');
        try{$interaction_html=(string)apply_filters('pldr_interaction_controls_html','',(int)$edition['id'],PLDR_Core::public_document_dto($doc,$edition));}
        catch(Throwable $e){$interaction_html='';PLDR_Core::audit('edition',(int)$edition['id'],'reader_interaction_provider_failed',array('provider_failure'=>true));}
        $thumbs = self::thumbnail_tokens((int)$edition['id']);
        $config = array(
            'editionId'=>(int)$edition['id'],'publicId'=>$public_id,'title'=>(string)$doc['title'],'pages'=>(int)$edition['pages'],'url'=>$grant['url'],'expiresAt'=>$grant['expires_at'],'startPage'=>max(1,(int)$state['page']),
            'rest'=>esc_url_raw(rest_url('pldr/v1/')),'nonce'=>wp_create_nonce('wp_rest'),'thumbnails'=>$thumbs,'canDownload'=>!empty(PLDR_Core::policy((int)$doc['id'])['download_allowed']),'canPrint'=>!empty(PLDR_Core::policy((int)$doc['id'])['print_allowed']),'canOffline'=>!empty(PLDR_Core::policy((int)$doc['id'])['offline_allowed']),
            'strings'=>array('loading'=>__('Loading document…','pdf-library-digital-reading'),'error'=>__('The reader could not load this document. Retry or use the accessible fallback.','pdf-library-digital-reading'),'saved'=>__('Reading position saved privately.','pdf-library-digital-reading')),
        );
        wp_enqueue_style('pldr-reader'); wp_enqueue_script('pldr-reader');
        wp_add_inline_script('pldr-reader','window.PLDR_READER=' . wp_json_encode($config) . ';','before');
        ob_start(); ?>
        <main class="pldr-reader-shell" data-pldr-reader dir="auto">
            <a class="pldr-skip" href="#pldr-reader-frame"><?php esc_html_e('Skip to document','pdf-library-digital-reading'); ?></a>
            <header class="pldr-reader-header"><div><a href="<?php echo esc_url(PLDR_Core::route_url('document',array('id'=>$public_id,'slug'=>$doc['slug']))); ?>">← <?php esc_html_e('Back to document','pdf-library-digital-reading'); ?></a> · <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home','pdf-library-digital-reading'); ?></a><h1><?php echo esc_html($doc['title']); ?></h1></div><div class="pldr-reader-status" aria-live="polite"></div></header>
            <div class="pldr-reader-toolbar" role="toolbar" aria-label="<?php esc_attr_e('PDF reader controls','pdf-library-digital-reading'); ?>">
                <button type="button" data-action="prev" aria-label="<?php esc_attr_e('Previous page','pdf-library-digital-reading'); ?>">‹</button>
                <label><?php esc_html_e('Page','pdf-library-digital-reading'); ?> <input data-page type="number" min="1" max="<?php echo absint((int)$edition['pages']); ?>" value="<?php echo absint((int)$state['page']); ?>"></label><span>/ <?php echo absint((int)$edition['pages']); ?></span>
                <button type="button" data-action="next" aria-label="<?php esc_attr_e('Next page','pdf-library-digital-reading'); ?>">›</button>
                <button type="button" data-action="zoom-out"><?php esc_html_e('Zoom −','pdf-library-digital-reading'); ?></button><button type="button" data-action="zoom-in"><?php esc_html_e('Zoom +','pdf-library-digital-reading'); ?></button><button type="button" data-action="fit"><?php esc_html_e('Fit','pdf-library-digital-reading'); ?></button><button type="button" data-action="fullscreen"><?php esc_html_e('Full screen','pdf-library-digital-reading'); ?></button>
                <button type="button" data-action="bookmark"><?php esc_html_e('Bookmark','pdf-library-digital-reading'); ?></button><button type="button" data-action="note"><?php esc_html_e('Private note','pdf-library-digital-reading'); ?></button><button type="button" data-action="highlight"><?php esc_html_e('Highlight note','pdf-library-digital-reading'); ?></button><button type="button" data-action="citation"><?php esc_html_e('Copy citation','pdf-library-digital-reading'); ?></button><?php if (!empty($config['canPrint'])): ?><button type="button" data-action="print"><?php esc_html_e('Print','pdf-library-digital-reading'); ?></button><?php endif; ?><?php if (!empty($config['canDownload'])): ?><button type="button" data-action="download"><?php esc_html_e('Download','pdf-library-digital-reading'); ?></button><?php endif; ?>
            </div>
            <?php $thumb_limit=min((int)$edition['pages'],self::THUMBNAIL_PREVIEW_LIMIT); ?><div class="pldr-reader-layout"><aside class="pldr-thumbnails" aria-label="<?php esc_attr_e('Page thumbnails','pdf-library-digital-reading'); ?>"><ol><?php for($p=1;$p<=$thumb_limit;$p++): ?><li><button type="button" data-thumb-page="<?php echo $p; ?>"><?php if(isset($thumbs[$p])):?><img loading="lazy" src="<?php echo esc_url($thumbs[$p]); ?>" alt="<?php echo esc_attr(sprintf(__('Page %d thumbnail','pdf-library-digital-reading'),$p)); ?>"><?php else:?><span><?php echo $p; ?></span><?php endif;?></button></li><?php endfor;?></ol><?php if((int)$edition['pages']>$thumb_limit): ?><p><?php echo esc_html(sprintf(__('Showing the first %d page previews; use Page jump for later pages.','pdf-library-digital-reading'),$thumb_limit)); ?></p><?php endif; ?></aside><section class="pldr-reader-stage"><iframe id="pldr-reader-frame" data-frame title="<?php echo esc_attr(sprintf(__('PDF reader for %s','pdf-library-digital-reading'),$doc['title'])); ?>" loading="eager"></iframe><div class="pldr-reader-fallback"><p><?php esc_html_e('If the embedded PDF viewer is unavailable, use the OCR text search or an authorized download where permitted.','pdf-library-digital-reading'); ?></p></div></section></div>
            <section class="pldr-reader-tools"><?php echo wp_kses_post($interaction_html); ?><form data-ocr-search><label><?php esc_html_e('Search document text','pdf-library-digital-reading'); ?><input name="q" minlength="2"></label><button type="submit"><?php esc_html_e('Search','pdf-library-digital-reading'); ?></button></form><div data-ocr-results aria-live="polite"></div><div data-private-items></div></section>
            <section class="pldr-download-manager" hidden data-download-manager><h2><?php esc_html_e('Download Manager','pdf-library-digital-reading'); ?></h2><div data-download-status></div><progress max="100" value="0" data-download-progress></progress><div><button type="button" data-download-start><?php esc_html_e('Start','pdf-library-digital-reading'); ?></button><button type="button" data-download-pause><?php esc_html_e('Pause','pdf-library-digital-reading'); ?></button><button type="button" data-download-resume><?php esc_html_e('Resume','pdf-library-digital-reading'); ?></button></div><code data-download-checksum></code></section>
        </main>
        <?php return (string)ob_get_clean();
    }

    public static function reading_dashboard_html(): string {
        if (!is_user_logged_in()) return '<div class="pldr-state">' . esc_html__('Log in to view private reading progress.','pdf-library-digital-reading') . '</div>';
        global $wpdb;
        $uid=get_current_user_id();
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare('SELECT s.*,e.document_id,d.public_id,d.title,d.slug FROM '.PLDR_Core::table('reading_state').' s JOIN '.PLDR_Core::table('editions').' e ON e.id=s.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE s.user_id=%d ORDER BY s.updated_at DESC LIMIT 100',$uid),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return self::state_html('error');
        $rows=is_array($rows)?$rows:array();
        $rows=array_values(array_filter($rows,static fn(array $row):bool=>PLDR_Access::can_access_edition((int)$row['edition_id'],'read',$uid)));
        ob_start();?><main class="pldr-shell"><h1><?php esc_html_e('Reading Workspace','pdf-library-digital-reading');?></h1><div class="pldr-grid"><?php foreach($rows as $row):?><article class="pldr-card"><div class="pldr-card-body"><h2><a href="<?php echo esc_url(PLDR_Core::route_url('read',array('id'=>$row['public_id'])));?>"><?php echo esc_html($row['title']);?></a></h2><p><?php echo esc_html(sprintf(__('Page %1$d · %2$s%% complete','pdf-library-digital-reading'),(int)$row['last_page'],(string)$row['percent']));?></p></div></article><?php endforeach;?></div></main><?php return (string)ob_get_clean();
    }

    public static function citation(array $edition,int $page=0,string $style='sabri'): string {
        $author=trim((string)$edition['author_name']); $title=trim((string)$edition['title']); $year=(int)$edition['publication_year']; $label=trim((string)$edition['edition_label']);
        $stable=home_url('/library/document/'.rawurlencode((string)$edition['public_id']).'/');
        if ('apa' === $style) {
            return trim($author . '. ' . ($year ? '(' . $year . '). ' : '') . $title . ($label ? ' (' . $label . ').' : '.') . ' ' . $stable . ($page ? ' p. ' . $page : ''));
        }
        if ('mla' === $style) {
            return trim($author . '. “' . $title . '.” ' . ($edition['publisher'] ?: '') . ', ' . ($year ?: 'n.d.') . '. ' . $stable . ($page ? ' p. ' . $page . '.' : ''));
        }
        return trim($author.' — '.$title.($label?' — '.$label:'').($year?' — '.$year:'').' — File 12 '.$edition['public_id'].($page?' — p. '.$page:'').' — '.$stable);
    }

    private static function cover_token(int $edition_id): string { global $wpdb; $wpdb->last_error=''; $oid=(int)$wpdb->get_var($wpdb->prepare('SELECT object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s LIMIT 1',$edition_id,'cover','available')); if(''!==(string)$wpdb->last_error){PLDR_Core::audit('edition',$edition_id,'cover_derivative_read_failed',array());return'';} if(!$oid)return''; $grant=PLDR_Access::issue_token($edition_id,$oid,'preview',get_current_user_id(),900); return is_array($grant)?$grant['url']:''; }
    private static function thumbnail_tokens(int $edition_id): array { global $wpdb; $wpdb->last_error=''; $rows=$wpdb->get_results($wpdb->prepare('SELECT page_number,object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s ORDER BY page_number ASC LIMIT %d',$edition_id,'thumbnail','available',self::THUMBNAIL_PREVIEW_LIMIT),ARRAY_A); if(''!==(string)$wpdb->last_error){PLDR_Core::audit('edition',$edition_id,'thumbnail_derivative_read_failed',array());return array();} $rows=is_array($rows)?$rows:array(); $out=array(); foreach($rows as $r){$g=PLDR_Access::issue_token($edition_id,(int)$r['object_id'],'preview',get_current_user_id(),900);if(is_array($g))$out[(int)$r['page_number']]=$g['url'];} return $out; }
    private static function state_html(string $state): string { $messages=array('not-found'=>__('Document not found.','pdf-library-digital-reading'),'restricted'=>__('This document is restricted, expired, embargoed, or not available to your account.','pdf-library-digital-reading'),'error'=>__('The document reader is temporarily unavailable.','pdf-library-digital-reading')); return '<main class="pldr-shell"><div class="pldr-state"><h1>'.esc_html($messages[$state]??$messages['error']).'</h1><a href="'.esc_url(PLDR_Core::route_url('library')).'">'.esc_html__('Return to PDF Library','pdf-library-digital-reading').'</a></div></main>'; }
}
