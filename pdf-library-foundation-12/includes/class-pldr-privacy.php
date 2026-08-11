<?php

defined('ABSPATH') || exit;

final class PLDR_Privacy {
    private const BATCH = 50;

    public static function hooks(): void {
        add_filter('wp_privacy_personal_data_exporters', array(__CLASS__, 'exporters'));
        add_filter('wp_privacy_personal_data_erasers', array(__CLASS__, 'erasers'));
    }

    public static function exporters(array $exporters): array {
        $exporters['pldr-reading'] = array(
            'exporter_friendly_name' => __('PDF Library private reading data', 'pdf-library-digital-reading'),
            'callback' => array(__CLASS__, 'export'),
        );
        return $exporters;
    }

    public static function erasers(array $erasers): array {
        $erasers['pldr-reading'] = array(
            'eraser_friendly_name' => __('PDF Library private reading data', 'pdf-library-digital-reading'),
            'callback' => array(__CLASS__, 'erase'),
        );
        return $erasers;
    }

    private static function user_id(string $email): int {
        $user = get_user_by('email', $email);
        return $user ? (int) $user->ID : 0;
    }

    private static function table_exists(string $suffix): bool {
        global $wpdb;
        $table = PLDR_Core::table($suffix);
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    private static function add_export_rows(array &$data, array $rows, string $group_id, string $group_label, string $id_field, callable $mapper): void {
        foreach ($rows as $row) {
            $data[] = array(
                'group_id' => $group_id,
                'group_label' => $group_label,
                'item_id' => $group_id . '-' . sanitize_key((string) ($row[$id_field] ?? '0')),
                'data' => $mapper($row),
            );
        }
    }

    public static function export(string $email, int $page = 1): array {
        global $wpdb;
        $user_id = self::user_id($email);
        if (!$user_id) return array('data'=>array(), 'done'=>true);

        $limit = self::BATCH;
        $offset = (max(1, $page) - 1) * $limit;
        $data = array();
        $counts = array();

        $states = $wpdb->get_results($wpdb->prepare(
            'SELECT s.*,d.public_id,d.title FROM '.PLDR_Core::table('reading_state').' s JOIN '.PLDR_Core::table('editions').' e ON e.id=s.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE s.user_id=%d ORDER BY s.updated_at DESC LIMIT %d OFFSET %d',
            $user_id, $limit, $offset
        ), ARRAY_A) ?: array();
        $counts[] = count($states);
        self::add_export_rows($data, $states, 'pldr-reading-state', __('PDF Library reading progress','pdf-library-digital-reading'), 'edition_id', static function(array $row): array {
            return array(
                array('name'=>'Document','value'=>$row['title'].' ('.$row['public_id'].')'),
                array('name'=>'Last page','value'=>(int)$row['last_page']),
                array('name'=>'Percent','value'=>$row['percent']),
                array('name'=>'Updated','value'=>$row['updated_at']),
            );
        });

        $items = $wpdb->get_results($wpdb->prepare(
            'SELECT i.*,d.public_id,d.title FROM '.PLDR_Core::table('reading_items').' i JOIN '.PLDR_Core::table('editions').' e ON e.id=i.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE i.user_id=%d ORDER BY i.id ASC LIMIT %d OFFSET %d',
            $user_id, $limit, $offset
        ), ARRAY_A) ?: array();
        $counts[] = count($items);
        self::add_export_rows($data, $items, 'pldr-reading-items', __('PDF Library bookmarks, highlights and private notes','pdf-library-digital-reading'), 'id', static function(array $row): array {
            return array(
                array('name'=>'Document','value'=>$row['title'].' ('.$row['public_id'].')'),
                array('name'=>'Type','value'=>$row['item_type']),
                array('name'=>'Page','value'=>(int)$row['page_number']),
                array('name'=>'Anchor','value'=>$row['anchor_text']),
                array('name'=>'Private note','value'=>$row['note_text']),
                array('name'=>'Tags','value'=>$row['tags_json']),
                array('name'=>'Updated','value'=>$row['updated_at']),
            );
        });

        if (self::table_exists('access_tokens')) {
            $tokens=$wpdb->get_results($wpdb->prepare(
                'SELECT id,edition_id,operation,expires_at,revoked_at,used_count,max_uses,created_at FROM '.PLDR_Core::table('access_tokens').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d',
                $user_id,$limit,$offset
            ),ARRAY_A)?:array();
            $counts[]=count($tokens);
            self::add_export_rows($data,$tokens,'pldr-delivery-grants',__('PDF Library delivery grant metadata','pdf-library-digital-reading'),'id',static function(array $row):array {
                return array(
                    array('name'=>'Edition','value'=>(int)$row['edition_id']),
                    array('name'=>'Operation','value'=>(string)$row['operation']),
                    array('name'=>'Expires','value'=>(string)$row['expires_at']),
                    array('name'=>'Revoked','value'=>(string)($row['revoked_at']??'')),
                    array('name'=>'Uses','value'=>(int)$row['used_count']),
                    array('name'=>'Maximum uses','value'=>(int)$row['max_uses']),
                    array('name'=>'Created','value'=>(string)$row['created_at']),
                );
            });
        }

        $future_specs = array(
            'future_prefs' => array('id'=>'preference_key','order'=>'preference_key','group'=>'pldr-future-preferences','label'=>__('PDF Library advanced reading preferences','pdf-library-digital-reading'),'fields'=>array('preference_key','preference_json','version','updated_at')),
            'shelves' => array('id'=>'shelf_key','order'=>'id','group'=>'pldr-future-shelves','label'=>__('PDF Library private shelves','pdf-library-digital-reading'),'fields'=>array('shelf_key','name','shelf_type','sort_order','version','created_at','updated_at')),
            'reading_events' => array('id'=>'event_id','order'=>'id','group'=>'pldr-future-reading-events','label'=>__('PDF Library private reading insights events','pdf-library-digital-reading'),'fields'=>array('event_id','edition_id','event_type','page_number','duration_seconds','context_json','created_at')),
            'session_handoffs' => array('id'=>'edition_id','order'=>'updated_at','group'=>'pldr-future-handoffs','label'=>__('PDF Library cross-device reading handoff','pdf-library-digital-reading'),'fields'=>array('edition_id','page_number','zoom','layout_mode','anchor_json','device_hint','version','updated_at')),
            'room_contexts' => array('id'=>'room_key','order'=>'id','group'=>'pldr-future-reading-rooms','label'=>__('PDF Library reading-room contexts','pdf-library-digital-reading'),'fields'=>array('room_key','edition_id','page_number','anchor_json','provider_ref','status','created_at','updated_at')),
        );

        foreach ($future_specs as $suffix => $spec) {
            if (!self::table_exists($suffix)) { $counts[] = 0; continue; }
            $table = PLDR_Core::table($suffix);
            $owner_column = 'room_contexts' === $suffix ? 'created_by' : 'user_id';
            $order = preg_replace('/[^a-z0-9_]/i', '', $spec['order']);
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$owner_column}=%d ORDER BY {$order} ASC LIMIT %d OFFSET %d", $user_id, $limit, $offset), ARRAY_A) ?: array();
            $counts[] = count($rows);
            self::add_export_rows($data, $rows, $spec['group'], $spec['label'], $spec['id'], static function(array $row) use ($spec): array {
                $out = array();
                foreach ($spec['fields'] as $field) $out[] = array('name'=>$field, 'value'=>is_scalar($row[$field] ?? '') ? (string)($row[$field] ?? '') : wp_json_encode($row[$field] ?? null));
                return $out;
            });
        }

        return array('data'=>$data, 'done'=>max($counts ?: array(0)) < $limit);
    }

    private static function delete_ids(string $suffix, string $id_column, array $ids): int {
        global $wpdb;
        if (!$ids) return 0;
        $table = PLDR_Core::table($suffix);
        $safe_ids = array_values(array_filter(array_map('absint', $ids)));
        if (!$safe_ids) return 0;
        $in = implode(',', $safe_ids);
        $deleted = $wpdb->query("DELETE FROM {$table} WHERE {$id_column} IN ({$in})");
        return false === $deleted ? -1 : (int)$deleted;
    }

    private static function erase_id_batch(string $suffix, string $owner_column, int $user_id): int {
        global $wpdb;
        if (!self::table_exists($suffix)) return 0;
        $table = PLDR_Core::table($suffix);
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE {$owner_column}=%d ORDER BY id ASC LIMIT %d", $user_id, self::BATCH));
        return self::delete_ids($suffix, 'id', $ids ?: array());
    }

    private static function erase_direct_batch(string $suffix, string $owner_column, int $user_id): int {
        global $wpdb;
        if (!self::table_exists($suffix)) return 0;
        $table = PLDR_Core::table($suffix);
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE {$owner_column}=%d LIMIT %d", $user_id, self::BATCH));
        return false === $deleted ? -1 : (int)$deleted;
    }

    public static function erase(string $email, int $page = 1): array {
        global $wpdb;
        $user_id = self::user_id($email);
        if (!$user_id) return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);

        try {
            $hold = (bool) apply_filters('pldr_privacy_legal_hold', false, $user_id);
        } catch (Throwable $e) {
            PLDR_Core::audit('privacy',0,'privacy_legal_hold_provider_failed',array('user_id'=>$user_id,'provider_failure'=>true),$user_id);
            return array(
                'items_removed'=>false,
                'items_retained'=>true,
                'messages'=>array(__('File 12 legal-hold status could not be verified, so erasure was not performed. Retry after the privacy provider is reconciled.','pdf-library-digital-reading')),
                'done'=>false,
            );
        }
        if ($hold) return array(
            'items_removed'=>false,
            'items_retained'=>true,
            'messages'=>array(__('File 12 data is subject to a documented legal/rights hold and cannot yet be erased.','pdf-library-digital-reading')),
            'done'=>true,
        );

        $removed = 0;
        $errors = array();

        foreach (array(
            array('reading_items','user_id','id'),
            array('reading_state','user_id','direct'),
            array('access_tokens','user_id','id'),
            array('future_prefs','user_id','direct'),
            array('reading_events','user_id','id'),
            array('session_handoffs','user_id','direct'),
            array('room_contexts','created_by','id'),
        ) as $spec) {
            $result = 'id' === $spec[2]
                ? self::erase_id_batch($spec[0], $spec[1], $user_id)
                : self::erase_direct_batch($spec[0], $spec[1], $user_id);
            if ($result < 0) $errors[] = $spec[0]; else $removed += $result;
        }

        if (self::table_exists('shelves')) {
            $shelves = PLDR_Core::table('shelves');
            $shelf_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$shelves} WHERE user_id=%d ORDER BY id ASC LIMIT %d", $user_id, self::BATCH)) ?: array();
            if ($shelf_ids) {
                $shelf_items_remaining=false;
                if (self::table_exists('shelf_items')) {
                    $items = PLDR_Core::table('shelf_items');
                    $in = implode(',', array_map('absint', $shelf_ids));
                    $item_ids=$wpdb->get_col("SELECT id FROM {$items} WHERE shelf_id IN ({$in}) ORDER BY id ASC LIMIT ".self::BATCH)?:array();
                    if($item_ids){
                        $result=self::delete_ids('shelf_items','id',$item_ids);
                        if($result<0)$errors[]='shelf_items';else$removed+=$result;
                    }
                    $shelf_items_remaining=(bool)$wpdb->get_var("SELECT id FROM {$items} WHERE shelf_id IN ({$in}) LIMIT 1");
                }
                if(!$shelf_items_remaining&&!in_array('shelf_items',$errors,true)){
                    $result = self::delete_ids('shelves', 'id', $shelf_ids);
                    if ($result < 0) $errors[] = 'shelves'; else $removed += $result;
                }
            }
        }

        if (self::table_exists('ocr_corrections')) {
            $table = PLDR_Core::table('ocr_corrections');
            if (false === $wpdb->query($wpdb->prepare("UPDATE {$table} SET submitted_by=CASE WHEN submitted_by=%d THEN 0 ELSE submitted_by END,reviewed_by=CASE WHEN reviewed_by=%d THEN 0 ELSE reviewed_by END WHERE submitted_by=%d OR reviewed_by=%d", $user_id, $user_id, $user_id, $user_id))) $errors[] = 'ocr_corrections';
        }
        if (self::table_exists('a11y_audits')) {
            $table = PLDR_Core::table('a11y_audits');
            if (false === $wpdb->update($table, array('verified_by'=>0), array('verified_by'=>$user_id), array('%d'), array('%d'))) $errors[] = 'a11y_audits';
        }
        if (false === $wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('rights_cases').' SET reporter_id=0,updated_at=%s WHERE reporter_id=%d AND state=%s', PLDR_Core::now(), $user_id, 'closed'))) $errors[] = 'rights_cases';

        if ($errors) return array(
            'items_removed'=>$removed > 0,
            'items_retained'=>true,
            'messages'=>array(sprintf(__('Some File 12 privacy data could not be erased and requires retry/reconciliation: %s','pdf-library-digital-reading'), implode(', ', array_unique($errors)))),
            'done'=>false,
        );

        $remaining = 0;
        foreach (array(
            array('reading_items','user_id'), array('reading_state','user_id'), array('access_tokens','user_id'), array('future_prefs','user_id'), array('shelves','user_id'),
            array('reading_events','user_id'), array('session_handoffs','user_id'), array('room_contexts','created_by')
        ) as $spec) {
            if (!self::table_exists($spec[0])) continue;
            $table = PLDR_Core::table($spec[0]);
            $remaining += (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$spec[1]}=%d", $user_id));
        }

        PLDR_Core::audit('privacy', 0, 'user_reading_erasure_batch', array('user_id'=>$user_id,'removed'=>$removed,'remaining'=>$remaining,'page'=>max(1,$page)));
        return array(
            'items_removed'=>$removed > 0,
            'items_retained'=>$remaining > 0,
            'messages'=>array(__('Private File 12 reading data and user-bound delivery grants were erased in a bounded batch; durable review records were anonymized where no hold applied.','pdf-library-digital-reading')),
            'done'=>0 === $remaining,
        );
    }
}
