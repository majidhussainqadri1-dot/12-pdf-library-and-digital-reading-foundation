<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Authority {
    public static function lookup(string $type, string $value, bool $force = false) {
        global $wpdb;
        $type = sanitize_key($type); $value = trim(sanitize_text_field($value)); $value = function_exists('mb_substr') ? mb_substr($value,0,190,'UTF-8') : substr($value,0,190);
        if (!in_array($type, array('doi','orcid','isbn'), true) || '' === $value) return PLDR_Core::machine_error('pldr_authority_identifier', 'Use a DOI, ORCID or ISBN identifier.', 400);
        if (!PLDR_Core::authorize('manage') && !PLDR_Core::authorize('publish')) return PLDR_Core::machine_error('pldr_authority_forbidden', 'Bibliographic enrichment requires publishing authority.', 403);
        if (!$force) {
            $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . PLDR_Core::table('authority_cache') . ' WHERE identifier_type=%s AND identifier_value=%s AND expires_at>%s ORDER BY updated_at DESC LIMIT 1', $type, $value, PLDR_Core::now()), ARRAY_A);
            if ($row) return array('status' => 'cached', 'provider' => $row['provider'], 'result' => json_decode((string) $row['result_json'], true) ?: array(), 'provenance' => json_decode((string) $row['provenance_json'], true) ?: array(), 'canonical_overwrite' => false);
        }
        $result = apply_filters('pldr_authority_lookup', null, $type, $value);
        if (!is_array($result) || empty($result['data'])) return PLDR_Core::machine_error('pldr_authority_provider', 'No bibliographic authority provider is configured for this identifier.', 503, array('degraded' => true));
        $provider = sanitize_text_field((string) ($result['provider'] ?? 'adapter')); $provider = function_exists('mb_substr') ? mb_substr($provider,0,80,'UTF-8') : substr($provider,0,80);
        $encoded = wp_json_encode($result['data']);
        if (!is_string($encoded) || strlen($encoded) > 524288) return PLDR_Core::machine_error('pldr_authority_payload', 'Bibliographic authority provider response exceeded the governed payload limit.', 502);
        $provenance = array('provider' => $provider, 'retrieved_at' => PLDR_Core::now(), 'identifier_type' => $type, 'identifier_value' => $value, 'external_enrichment' => true, 'canonical_overwrite' => false);
        $stored=$wpdb->replace(PLDR_Core::table('authority_cache'), array('identifier_type'=>$type,'identifier_value'=>$value,'provider'=>$provider,'result_json'=>$encoded,'provenance_json'=>wp_json_encode($provenance),'expires_at'=>gmdate('Y-m-d H:i:s', time()+30*DAY_IN_SECONDS),'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        if(false===$stored)return PLDR_Core::machine_error('pldr_authority_cache_store','Bibliographic authority provenance could not be persisted.',500);
        PLDR_Core::audit('authority', 0, 'external_enrichment_cached', array('identifier_type'=>$type,'provider'=>$provider));
        return array('status' => 'fresh', 'provider' => $provider, 'result' => $result['data'], 'provenance' => $provenance, 'canonical_overwrite' => false);
    }
}
