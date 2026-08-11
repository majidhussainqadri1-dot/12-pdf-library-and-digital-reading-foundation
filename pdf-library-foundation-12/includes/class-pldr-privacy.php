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
        $wpdb->last_error='';
        $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        return ''===(string)$wpdb->last_error && $found === $table;
    }

    private static function table_check_failed(): bool {
        global $wpdb;
        return '' !== (string)$wpdb->last_error;
    }

    private static function export_failure(array $data,string $scope,int $user_id): array {
        PLDR_Core::audit('privacy',0,'privacy_export_read_failed',array('user_id'=>$user_id,'scope'=>$scope),$user_id);
        return array('data'=>$data,'done'=>false);
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

        $wpdb->last_error='';
        $states = $wpdb->get_results($wpdb->prepare(
            'SELECT s.*,d.public_id,d.title FROM '.PLDR_Core::table('reading_state').' s JOIN '.PLDR_Core::table('editions').' e ON e.id=s.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE s.user_id=%d ORDER BY s.updated_at DESC LIMIT %d OFFSET %d',
            $user_id, $limit, $offset
        ), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return self::export_failure($data,'reading_state',$user_id);
        $states=is_array($states)?$states:array();
        $counts[] = count($states);
        self::add_export_rows($data, $states, 'pldr-reading-state', __('PDF Library reading progress','pdf-library-digital-reading'), 'edition_id', static function(array $row): array {
            return array(
                array('name'=>'Document','value'=>$row['title'].' ('.$row['public_id'].')'),
                array('name'=>'Last page','value'=>(int)$row['last_page']),
                array('name'=>'Percent','value'=>$row['percent']),
                array('name'=>'Updated','value'=>$row['updated_at']),
            );
        });

        $wpdb->last_error='';
        $items = $wpdb->get_results($wpdb->prepare(
            'SELECT i.*,d.public_id,d.title FROM '.PLDR_Core::table('reading_items').' i JOIN '.PLDR_Core::table('editions').' e ON e.id=i.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE i.user_id=%d ORDER BY i.id ASC LIMIT %d OFFSET %d',
            $user_id, $limit, $offset
        ), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return self::export_failure($data,'reading_items',$user_id);
        $items=is_array($items)?$items:array();
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

        $access_tokens_exists=self::table_exists('access_tokens');
        if(self::table_check_failed())return self::export_failure($data,'access_tokens_table',$user_id);
        if ($access_tokens_exists) {
            $wpdb->last_error='';
            $tokens=$wpdb->get_results($wpdb->prepare(
                'SELECT id,edition_id,operation,expires_at,revoked_at,used_count,max_uses,created_at FROM '.PLDR_Core::table('access_tokens').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d',
                $user_id,$limit,$offset
            ),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return self::export_failure($data,'access_tokens',$user_id);
            $tokens=is_array($tokens)?$tokens:array();
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
            $exists=self::table_exists($suffix);
            if(self::table_check_failed())return self::export_failure($data,$suffix.'_table',$user_id);
            if (!$exists) { $counts[] = 0; continue; }
            $table = PLDR_Core::table($suffix);
            $owner_column = 'room_contexts' === $suffix ? 'created_by' : 'user_id';
            $order = preg_replace('/[^a-z0-9_]/i', '', $spec['order']);
            $wpdb->last_error='';
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$owner_column}=%d ORDER BY {$order} ASC LIMIT %d OFFSET %d", $user_id, $limit, $offset), ARRAY_A);
            if(''!==(string)$wpdb->last_error)return self::export_failure($data,$suffix,$user_id);
            $rows=is_array($rows)?$rows:array();
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
        if (!self::table_exists($suffix)) return self::table_check_failed() ? -1 : 0;
        $table = PLDR_Core::table($suffix);
        $wpdb->last_error='';
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE {$owner_column}=%d ORDER BY id ASC LIMIT %d", $user_id, self::BATCH));
        if(''!==(string)$wpdb->last_error)return -1;
        return self::delete_ids($suffix, 'id', $ids ?: array());
    }

    private static function erase_direct_batch(string $suffix, string $owner_column, int $user_id): int {
        global $wpdb;
        if (!self::table_exists($suffix)) return self::table_check_failed() ? -1 : 0;
        $table = PLDR_Core::table($suffix);
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE {$owner_column}=%d LIMIT %d", $user_id, self::BATCH));
        return false === $deleted ? -1 : (int)$deleted;
    }


    private static function anonymize_id_batch(string $suffix,string $where_sql,array $where_args,string $set_sql,array $set_args=array()): int {
        global $wpdb;
        if(!self::table_exists($suffix))return self::table_check_failed()?-1:0;
        $table=PLDR_Core::table($suffix);
        $select="SELECT id FROM {$table} WHERE {$where_sql} ORDER BY id ASC LIMIT %d";
        $wpdb->last_error='';
        $ids=$wpdb->get_col($wpdb->prepare($select,array_merge($where_args,array(self::BATCH))));
        if(''!==(string)$wpdb->last_error)return -1;
        $safe=array_values(array_filter(array_map('absint',$ids?:array())));
        if(!$safe)return 0;
        $in=implode(',',$safe);
        $query="UPDATE {$table} SET {$set_sql} WHERE id IN ({$in})";
        if($set_args)$query=$wpdb->prepare($query,$set_args);
        $updated=$wpdb->query($query);
        return false===$updated?-1:(int)$updated;
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

        $shelves_exists=self::table_exists('shelves');
        if(self::table_check_failed())$errors[]='shelves';
        if ($shelves_exists) {
            $shelves = PLDR_Core::table('shelves');
            $wpdb->last_error='';
            $shelf_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$shelves} WHERE user_id=%d ORDER BY id ASC LIMIT %d", $user_id, self::BATCH));
            if(''!==(string)$wpdb->last_error)$errors[]='shelves';
            $shelf_ids=is_array($shelf_ids)?$shelf_ids:array();
            if ($shelf_ids) {
                $shelf_items_remaining=false;
                $shelf_items_exists=self::table_exists('shelf_items');
                if(self::table_check_failed())$errors[]='shelf_items';
                if ($shelf_items_exists) {
                    $items = PLDR_Core::table('shelf_items');
                    $in = implode(',', array_map('absint', $shelf_ids));
                    $wpdb->last_error='';
                    $item_ids=$wpdb->get_col("SELECT id FROM {$items} WHERE shelf_id IN ({$in}) ORDER BY id ASC LIMIT ".self::BATCH);
                    if(''!==(string)$wpdb->last_error){$errors[]='shelf_items';$item_ids=array();}
                    $item_ids=is_array($item_ids)?$item_ids:array();
                    if($item_ids){
                        $result=self::delete_ids('shelf_items','id',$item_ids);
                        if($result<0)$errors[]='shelf_items';else$removed+=$result;
                    }
                    $wpdb->last_error='';$shelf_items_remaining=(bool)$wpdb->get_var("SELECT id FROM {$items} WHERE shelf_id IN ({$in}) LIMIT 1");
                    if(''!==(string)$wpdb->last_error){$errors[]='shelf_items';$shelf_items_remaining=true;}
                }
                if(!$shelf_items_remaining&&!in_array('shelf_items',$errors,true)){
                    $result = self::delete_ids('shelves', 'id', $shelf_ids);
                    if ($result < 0) $errors[] = 'shelves'; else $removed += $result;
                }
            }
        }

        $ocr_anon=self::anonymize_id_batch('ocr_corrections','submitted_by=%d OR reviewed_by=%d',array($user_id,$user_id),'submitted_by=CASE WHEN submitted_by=%d THEN 0 ELSE submitted_by END,reviewed_by=CASE WHEN reviewed_by=%d THEN 0 ELSE reviewed_by END',array($user_id,$user_id));
        if($ocr_anon<0)$errors[]='ocr_corrections';else$removed+=$ocr_anon;
        $a11y_anon=self::anonymize_id_batch('a11y_audits','verified_by=%d',array($user_id),'verified_by=0');
        if($a11y_anon<0)$errors[]='a11y_audits';else$removed+=$a11y_anon;
        $rights_anon=self::anonymize_id_batch('rights_cases','reporter_id=%d AND state=%s',array($user_id,'closed'),'reporter_id=0,updated_at=%s',array(PLDR_Core::now()));
        if($rights_anon<0)$errors[]='rights_cases';else$removed+=$rights_anon;

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
            $exists=self::table_exists($spec[0]);
            if(self::table_check_failed()){$errors[]=$spec[0];continue;}
            if (!$exists) continue;
            $table = PLDR_Core::table($spec[0]);
            $wpdb->last_error='';$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$spec[1]}=%d", $user_id));
            if(''!==(string)$wpdb->last_error){$errors[]=$spec[0];continue;}
            $remaining += $count;
        }
        foreach(array(
            array('ocr_corrections','submitted_by=%d OR reviewed_by=%d',array($user_id,$user_id)),
            array('a11y_audits','verified_by=%d',array($user_id)),
            array('rights_cases','reporter_id=%d AND state=%s',array($user_id,'closed'))
        ) as $spec){
            $exists=self::table_exists($spec[0]);if(self::table_check_failed()){$errors[]=$spec[0];continue;}
            if(!$exists)continue;$table=PLDR_Core::table($spec[0]);$wpdb->last_error='';
            $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$spec[1]}",$spec[2]));
            if(''!==(string)$wpdb->last_error){$errors[]=$spec[0];continue;}$remaining+=$count;
        }
        if($errors)return array('items_removed'=>$removed>0,'items_retained'=>true,'messages'=>array(__('File 12 privacy reconciliation could not confirm completion; retry is required.','pdf-library-digital-reading')),'done'=>false);

        PLDR_Core::audit('privacy', 0, 'user_reading_erasure_batch', array('user_id'=>$user_id,'removed'=>$removed,'remaining'=>$remaining,'page'=>max(1,$page)));
        return array(
            'items_removed'=>$removed > 0,
            'items_retained'=>$remaining > 0,
            'messages'=>array(__('Private File 12 reading data and user-bound delivery grants were erased in a bounded batch; durable review records were anonymized where no hold applied.','pdf-library-digital-reading')),
            'done'=>0 === $remaining,
        );
    }
}
