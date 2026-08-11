<?php

defined('ABSPATH') || exit;

final class PLDR_Future_REST {
    public static function register(): void {
        register_rest_route('pldr/v1', '/future/features', array('methods'=>'GET','callback'=>array(__CLASS__,'features'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/reflow/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'reflow'),'permission_callback'=>'__return_true','args'=>array('page'=>array('sanitize_callback'=>'absint'))));
        register_rest_route('pldr/v1', '/future/outline/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'outline'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/compare', array('methods'=>'GET','callback'=>array(__CLASS__,'compare'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/citation-export/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'citation_export'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/anchor', array('methods'=>'POST','callback'=>array(__CLASS__,'anchor'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1', '/future/authority', array('methods'=>'POST','callback'=>array(__CLASS__,'authority'),'permission_callback'=>array(__CLASS__,'can_publish')));
        register_rest_route('pldr/v1', '/future/ocr-quality/(?P<edition>\d+)', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'ocr_quality'),'permission_callback'=>'__return_true'),
            array('methods'=>'POST','callback'=>array(__CLASS__,'ocr_correction'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/ocr-corrections/(?P<id>\d+)/review', array('methods'=>'POST','callback'=>array(__CLASS__,'ocr_review'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1', '/future/annotations/(?P<edition>\d+)', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'annotations_export'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'annotations_import'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/iiif/(?P<id>[a-f0-9\-]{36})/manifest', array('methods'=>'GET','callback'=>array(__CLASS__,'iiif'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/search-heatmap/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'heatmap'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/offline-grant', array('methods'=>'POST','callback'=>array(__CLASS__,'offline_grant'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1', '/future/preferences', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'preferences_get'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'preferences_save'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/shelves', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'shelves'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'shelf_create'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/shelves/(?P<id>\d+)', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'shelf_rename'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'DELETE','callback'=>array(__CLASS__,'shelf_delete'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/shelves/(?P<id>\d+)/items', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'shelf_add'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'DELETE','callback'=>array(__CLASS__,'shelf_remove_item'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/insights', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'insights'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'insight_event'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/handoff/(?P<edition>\d+)', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'handoff_get'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'handoff_save'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/accessibility/(?P<edition>\d+)', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'a11y'),'permission_callback'=>'__return_true'),
            array('methods'=>'POST','callback'=>array(__CLASS__,'a11y_verify'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
        register_rest_route('pldr/v1', '/future/reading-room', array('methods'=>'POST','callback'=>array(__CLASS__,'reading_room'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1', '/future/context/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'context'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/corpus/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'corpus'),'permission_callback'=>'__return_true','args'=>array('offset'=>array('sanitize_callback'=>'absint'),'limit'=>array('sanitize_callback'=>'absint'))));
        register_rest_route('pldr/v1', '/future/derive-text', array('methods'=>'POST','callback'=>array(__CLASS__,'derive_text'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1', '/future/preservation/(?P<edition>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__,'preservation'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1', '/future/fingerprint/(?P<edition>\d+)', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'duplicates'),'permission_callback'=>array(__CLASS__,'logged_in')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'fingerprint'),'permission_callback'=>array(__CLASS__,'logged_in')),
        ));
    }

    public static function logged_in(): bool { return is_user_logged_in(); }
    public static function can_publish(): bool { return PLDR_Core::authorize('publish') || PLDR_Core::authorize('manage'); }
    public static function can_review(): bool { return PLDR_Core::authorize('rights') || PLDR_Core::authorize('manage'); }
    public static function can_preservation(): bool { return PLDR_Core::authorize('repair') || PLDR_Core::authorize('manage'); }

    private static function response($result) {
        if (is_wp_error($result)) return $result;
        if (is_array($result) && isset($result['error']) && is_wp_error($result['error'])) return $result['error'];
        return rest_ensure_response($result);
    }

    private static function body(WP_REST_Request $request): array {
        $body = $request->get_json_params();
        return is_array($body) ? $body : $request->get_params();
    }

    private static function idempotent(WP_REST_Request $request, string $route, callable $callback) {
        $key = substr(sanitize_text_field((string) $request->get_header('Idempotency-Key')), 0, 200);
        if ('' === $key) {
            $result=$callback();
            if(is_array($result)&&isset($result['error'])&&is_wp_error($result['error']))$result=$result['error'];
            return is_wp_error($result)?$result:rest_ensure_response($result);
        }
        $actor = get_current_user_id();
        $route = 'future-' . $route;
        $claim = PLDR_Core::idempotency_begin($route, $key, $actor);
        if ('hit' === ($claim['state'] ?? '')) return new WP_REST_Response($claim['body'], $claim['status']);
        if ('pending' === ($claim['state'] ?? '')) return PLDR_Core::machine_error('pldr_future_idempotency_in_progress','A request with this Idempotency-Key is already in progress.',409,array('retry_after'=>2));
        if ('reserved' !== ($claim['state'] ?? '')) return PLDR_Core::machine_error('pldr_future_idempotency_unavailable','Idempotency protection could not be reserved; the mutation was not executed.',503);
        $result = $callback();
        if (is_array($result) && isset($result['error']) && is_wp_error($result['error'])) $result = $result['error'];
        $response = is_wp_error($result) ? rest_convert_error_to_response($result) : rest_ensure_response($result);
        $status = $response instanceof WP_REST_Response ? $response->get_status() : 200;
        $body = $response instanceof WP_REST_Response ? $response->get_data() : $result;
        if (!PLDR_Core::idempotency_complete($route, $key, $actor, $body, $status)) return PLDR_Core::machine_error('pldr_future_idempotency_persist','The operation completed but its idempotency result could not be finalized; reconcile before retrying with a new key.',503,array('original_status'=>$status));
        return is_wp_error($result) ? $result : $response;
    }

    public static function features() { return rest_ensure_response(array('version'=>PLDR_Future::VERSION,'count'=>count(PLDR_Future::FEATURES),'features'=>PLDR_Future::FEATURES,'production_truth'=>'Source capability presence is not staging/live acceptance.')); }
    public static function reflow(WP_REST_Request $r) { return self::response(PLDR_Future_Data::reflow(absint($r['edition']),absint($r['page']))); }
    public static function outline(WP_REST_Request $r) { return self::response(PLDR_Future_Data::outline(absint($r['edition']))); }
    public static function compare(WP_REST_Request $r) { return self::response(PLDR_Future_Data::compare(absint($r['left']),absint($r['right']))); }
    public static function citation_export(WP_REST_Request $r) { return self::response(PLDR_Future_Citations::export(absint($r['edition']),sanitize_key((string)($r['format']?:'csl-json')),absint($r['page']))); }
    public static function anchor(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'anchor',static fn()=>PLDR_Future_Anchors::save(absint($b['edition_id']??0),absint($b['page']??0),(array)($b['selector']??array()),(string)($b['note']??''))); }
    public static function authority(WP_REST_Request $r) { $b=self::body($r);return self::response(PLDR_Future_Authority::lookup((string)($b['type']??''),(string)($b['value']??''),!empty($b['force']))); }
    public static function ocr_quality(WP_REST_Request $r) { return self::response(PLDR_Future_OCR_Lab::report(absint($r['edition']))); }
    public static function ocr_correction(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'ocr-correction',static fn()=>PLDR_Future_OCR_Lab::submit(absint($r['edition']),absint($b['page']??0),(string)($b['original']??''),(string)($b['corrected']??''))); }
    public static function ocr_review(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'ocr-review',static fn()=>PLDR_Future_OCR_Lab::review(absint($r['id']),(string)($b['decision']??''),(string)($b['note']??''))); }
    public static function annotations_export(WP_REST_Request $r) { return self::response(PLDR_Future_Annotations::export(absint($r['edition']))); }
    public static function annotations_import(WP_REST_Request $r) { return self::idempotent($r,'annotations-import',static fn()=>PLDR_Future_Annotations::import(absint($r['edition']),self::body($r))); }
    public static function iiif(WP_REST_Request $r) { return self::response(PLDR_Future_IIIF::manifest((string)$r['id'])); }
    public static function heatmap(WP_REST_Request $r) { return self::response(PLDR_Future_Search::heatmap(absint($r['edition']),(string)$r['q'])); }
    public static function offline_grant(WP_REST_Request $r) { $b=self::body($r);$edition=PLDR_Core::edition(absint($b['edition_id']??0));if(!$edition)return PLDR_Core::machine_error('pldr_offline_edition','Edition not found.',404);$grant=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'offline',get_current_user_id(),900);if(is_wp_error($grant))return $grant;$ttl=(int)apply_filters('pldr_offline_vault_ttl',7*DAY_IN_SECONDS,$edition,get_current_user_id());$ttl=max(HOUR_IN_SECONDS,min(30*DAY_IN_SECONDS,$ttl));$valid=time()+$ttl;if(!empty($edition['rights_expires_at'])){$rights=strtotime((string)$edition['rights_expires_at']);if($rights)$valid=min($valid,$rights);}if($valid<=time())return PLDR_Core::machine_error('pldr_offline_rights_expired','Offline rights have expired.',403);$grant['offline_valid_until']=gmdate('c',$valid);$grant['requires_logout_purge']=true;$grant['device_vault_policy']='non-extractable WebCrypto key; local expiry enforced; future refresh requires server reauthorization';return self::response($grant); }
    public static function preferences_get(WP_REST_Request $r) { return self::response(PLDR_Future_Preferences::get((string)($r['key']?:'reader'))); }
    public static function preferences_save(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'preferences-save',static fn()=>PLDR_Future_Preferences::save((string)($b['key']??'reader'),(array)($b['value']??array()),absint($b['expected_version']??0))); }
    public static function shelves() { return self::response(array('items'=>PLDR_Future_Shelves::list(),'private'=>true)); }
    public static function shelf_create(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-create',static fn()=>PLDR_Future_Shelves::create((string)($b['name']??''))); }
    public static function shelf_add(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-add',static fn()=>PLDR_Future_Shelves::add(absint($r['id']),absint($b['edition_id']??0))); }
    public static function shelf_rename(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-rename',static fn()=>PLDR_Future_Shelves::rename(absint($r['id']),(string)($b['name']??''))); }
    public static function shelf_delete(WP_REST_Request $r) { return self::idempotent($r,'shelf-delete',static fn()=>PLDR_Future_Shelves::remove(absint($r['id']))); }
    public static function shelf_remove_item(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'shelf-remove-item',static fn()=>PLDR_Future_Shelves::remove_item(absint($r['id']),absint($b['edition_id']??0))); }
    public static function insights(WP_REST_Request $r) { return self::response(PLDR_Future_Insights::report(absint($r['days']?:30))); }
    public static function insight_event(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'insight-event',static fn()=>PLDR_Future_Insights::event(absint($b['edition_id']??0),(string)($b['type']??''),absint($b['page']??1),absint($b['duration_seconds']??0),(array)($b['context']??array()))); }
    public static function handoff_get(WP_REST_Request $r) { return self::response(PLDR_Future_Handoff::get(absint($r['edition']))); }
    public static function handoff_save(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'handoff-save',static fn()=>PLDR_Future_Handoff::save(absint($r['edition']),(array)($b['context']??$b),absint($b['expected_version']??0))); }
    public static function a11y(WP_REST_Request $r) { return self::response(PLDR_Future_A11y::inspect(absint($r['edition']),!empty($r['refresh']))); }
    public static function a11y_verify(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'a11y-verify',static fn()=>PLDR_Future_A11y::verify(absint($r['edition']),(string)($b['note']??''))); }
    public static function reading_room(WP_REST_Request $r) { $b=self::body($r);return self::idempotent($r,'reading-room',static fn()=>PLDR_Future_Rooms::create(absint($b['edition_id']??0),absint($b['page']??1),(array)($b['anchor']??array()))); }
    public static function context(WP_REST_Request $r) { return self::response(PLDR_Future_Context::lookup(absint($r['edition']),(string)$r['selection'],absint($r['page']))); }
    public static function corpus(WP_REST_Request $r) { $limit=absint($r['limit']);if(!$limit)$limit=250;return self::response(PLDR_Future_Corpus::manifest(absint($r['edition']),absint($r['offset']),$limit)); }
    public static function derive_text(WP_REST_Request $r) { $b=self::body($r);return self::response(PLDR_Future_Derived_Text::derive(absint($b['edition_id']??0),absint($b['page']??0),(string)($b['text']??''),(string)($b['mode']??''),(string)($b['target']??''))); }
    public static function preservation(WP_REST_Request $r) { return self::response(PLDR_Future_Preservation::assess(absint($r['edition']),!empty($r['verify']))); }
    public static function duplicates(WP_REST_Request $r) { return self::response(array('items'=>PLDR_Future_Fingerprint::candidates(absint($r['edition'])),'automatic_merge'=>false)); }
    public static function fingerprint(WP_REST_Request $r) { return self::idempotent($r,'fingerprint',static fn()=>PLDR_Future_Fingerprint::compute_and_store(absint($r['edition']))); }
}
