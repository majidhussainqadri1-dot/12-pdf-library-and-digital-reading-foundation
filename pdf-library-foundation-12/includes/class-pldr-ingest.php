<?php

defined('ABSPATH') || exit;

final class PLDR_Ingest {
    public static function ingest(array $data, array $files, int $actor_id = 0) {
        global $wpdb;
        $actor_id = $actor_id ?: get_current_user_id();
        if (!$actor_id || !PLDR_Core::authorize('publish', 0, $actor_id)) {
            return PLDR_Core::machine_error('pldr_forbidden', 'You are not authorized to submit File 12 documents.', 403);
        }
        $existing_doc = null;
        if (!empty($data['document_public_id'])) {
            $existing_doc = PLDR_Core::document_by_public_id(sanitize_text_field((string)$data['document_public_id']));
            if (!$existing_doc) return PLDR_Core::machine_error('pldr_document_family_missing','The target canonical document family was not found.',404);
            if (!PLDR_Core::authorize('publish',(int)$existing_doc['id'],$actor_id) && !PLDR_Core::authorize('manage',(int)$existing_doc['id'],$actor_id)) return PLDR_Core::machine_error('pldr_document_family_forbidden','You cannot add an edition to this canonical document family.',403);
        }

        $required = array('title', 'document_type', 'category', 'language', 'author_name', 'source_name', 'rights_basis', 'access_mode');
        foreach ($required as $key) {
            if (!isset($data[$key]) || '' === trim((string) $data[$key])) {
                return PLDR_Core::machine_error('pldr_required', 'Required document metadata is incomplete.', 400, array('field' => $key));
            }
        }
        $type = sanitize_key((string) $data['document_type']);
        $category = sanitize_key((string) $data['category']);
        if (!isset(PLDR_Core::DOCUMENT_TYPES[$type]) || !isset(PLDR_Core::CATEGORIES[$category])) {
            return PLDR_Core::machine_error('pldr_taxonomy', 'Document type or category is not governed by File 12.', 400);
        }
        if ('platform-publications' === $category && !PLDR_Core::founder($actor_id) && !PLDR_Core::authorize('manage', 0, $actor_id)) {
            return PLDR_Core::machine_error('pldr_official_only', 'Platform Publications are reserved for authorized institutional publishing.', 403);
        }
        if ('patient-cases' === $category && empty($data['patient_case_consent'])) {
            return PLDR_Core::machine_error('pldr_patient_case_consent', 'Patient Case documents require verified anonymization and publication-consent confirmation.', 400);
        }

        $rights_basis = sanitize_key((string) $data['rights_basis']);
        $allowed_rights = array('founder-owned', 'owned', 'permission', 'public-domain', 'open-license', 'licensed');
        if (!in_array($rights_basis, $allowed_rights, true)) {
            return PLDR_Core::machine_error('pldr_rights_basis', 'A lawful publication-rights basis is required.', 400);
        }
        if (in_array($rights_basis, array('permission', 'licensed'), true) && empty($data['rights_evidence_ref'])) {
            return PLDR_Core::machine_error('pldr_rights_evidence', 'Permission or license publications require a restricted evidence reference.', 400);
        }

        $access_mode = sanitize_key((string) $data['access_mode']);
        $allowed_access = array('public', 'account', 'education-entitled', 'assigned');
        if (!in_array($access_mode, $allowed_access, true)) {
            return PLDR_Core::machine_error('pldr_access_mode', 'Invalid access policy audience.', 400);
        }

        if (empty($files['pdf']['tmp_name']) || !is_uploaded_file($files['pdf']['tmp_name'])) {
            return PLDR_Core::machine_error('pldr_pdf_missing', 'A verified HTTP-uploaded PDF is required.', 400);
        }
        $pdf = $files['pdf'];
        $validation = self::validate_pdf($pdf);
        if (is_wp_error($validation)) {
            return $validation;
        }
        $sha256 = hash_file('sha256', $pdf['tmp_name']);
        if (!$sha256) {
            return PLDR_Core::machine_error('pldr_checksum', 'The PDF checksum could not be computed.', 500);
        }

        $wpdb->last_error='';
        $duplicate = $wpdb->get_row($wpdb->prepare(
            'SELECT e.id,e.document_id,d.public_id,d.title FROM ' . PLDR_Core::table('editions') . ' e INNER JOIN ' . PLDR_Core::table('documents') . ' d ON d.id=e.document_id WHERE e.sha256=%s LIMIT 1',
            $sha256
        ), ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_duplicate_read','Duplicate-check state could not be read reliably; ingest was not attempted.',503,array('degraded'=>true));
        if ($duplicate) {
            return PLDR_Core::machine_error('pldr_exact_duplicate', 'This exact PDF object already exists in the canonical library.', 409, array('document_id' => $duplicate['public_id'], 'edition_id' => (int) $duplicate['id']));
        }

        $wpdb->last_error='';
        $similar = self::similar_candidate($data);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_similarity_read','Similarity-check state could not be read reliably; ingest was not attempted.',503,array('degraded'=>true));
        if ($similar && (!$existing_doc || (string)($similar['public_id']??'') !== (string)$existing_doc['public_id']) && empty($data['confirm_distinct_scan'])) {
            return PLDR_Core::machine_error('pldr_metadata_duplicate_candidate', 'A similar document or edition exists. Confirm that this is a distinct scan/edition before ingest.', 409, array('candidate' => $similar));
        }

        $scan_data=$data;$scan_data['filename']=sanitize_file_name((string)$pdf['name']);
        $scan = self::scan_file($pdf['tmp_name'], $scan_data);
        if (is_wp_error($scan)) {
            return $scan;
        }

        $allocation = PLDR_Storage::allocate('pldr');
        if (!empty($allocation['error'])) {
            return $allocation['error'];
        }
        $temp = PLDR_Storage::temp('encrypt');
        if (is_wp_error($temp)) {
            return $temp;
        }
        $crypto = array();
        $crypto_error = '';
        if (!PLDR_Crypto::encrypt_file($pdf['tmp_name'], $temp, $crypto, $crypto_error)) {
            return PLDR_Core::machine_error('pldr_encrypt_failed', $crypto_error ?: 'Private object encryption failed.', 503);
        }
        if (!PLDR_Storage::atomic_commit($temp, $allocation['path'])) {
            PLDR_Storage::delete($temp);
            return PLDR_Core::machine_error('pldr_atomic_commit', 'Encrypted object could not be committed atomically.', 500);
        }

        $title = sanitize_text_field((string) $data['title']);
        $author = sanitize_text_field((string) $data['author_name']);
        $language = sanitize_text_field((string) $data['language']);
        $subjects = PLDR_Core::sanitize_json_list($data['subjects'] ?? array());
        $collections = PLDR_Core::sanitize_json_list($data['collections'] ?? array());
        $public_id = $existing_doc ? (string)$existing_doc['public_id'] : PLDR_Core::uuid();
        $edition_status = self::publish_status($actor_id, $scan, $rights_basis);
        $document_status = $existing_doc ? (string)$existing_doc['status'] : $edition_status;
        $object_id = 0;
        $document_id = $existing_doc ? (int)$existing_doc['id'] : 0;
        $edition_id = 0;

        if(false===$wpdb->query('START TRANSACTION')){PLDR_Storage::delete($allocation['path']);return PLDR_Core::machine_error('pldr_ingest_transaction_start','PDF ingest transaction could not be started; committed storage was removed.',500);}
        try {
            $ok = $wpdb->insert(PLDR_Core::table('objects'), array(
                'storage_name' => $allocation['name'],
                'storage_scope' => 'pldr',
                'original_name' => sanitize_file_name((string) $pdf['name']),
                'mime_type' => 'application/pdf',
                'byte_size' => (int) $pdf['size'],
                'sha256' => $sha256,
                'encrypted_sha256' => (string) $crypto['encrypted_sha256'],
                'key_id' => (string) $crypto['key_id'],
                'format_version' => (string) $crypto['format'],
                'scan_status' => (string) $scan['status'],
                'object_status' => 'available',
                'created_at' => PLDR_Core::now(),
                'verified_at' => PLDR_Core::now(),
            ));
            if (false === $ok) throw new RuntimeException('Object record could not be saved.');
            $object_id = (int) $wpdb->insert_id;

            $search_text = PLDR_Core::normalize_search(implode(' ', array_merge(
                array($title, $author, (string) ($data['translator'] ?? ''), (string) ($data['publisher'] ?? ''), (string) ($data['isbn'] ?? ''), $language),
                $subjects,
                $collections
            )));
            if (!$existing_doc) {
                $ok = $wpdb->insert(PLDR_Core::table('documents'), array(
                    'public_id'=>$public_id,'title'=>$title,'slug'=>sanitize_title((string)($data['slug']??$title)),'document_type'=>$type,'category'=>$category,'language'=>$language,
                    'subjects_json'=>wp_json_encode($subjects),'collections_json'=>wp_json_encode($collections),'search_text'=>$search_text,'status'=>$document_status,'access_mode'=>$access_mode,
                    'created_by'=>$actor_id,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now(),
                ));
                if(false===$ok) throw new RuntimeException('Document record could not be saved.');
                $document_id=(int)$wpdb->insert_id;
            } else {
                $ok=$wpdb->update(PLDR_Core::table('documents'),array('title'=>$title,'slug'=>sanitize_title((string)($data['slug']??$title)),'document_type'=>$type,'category'=>$category,'language'=>$language,'subjects_json'=>wp_json_encode($subjects),'collections_json'=>wp_json_encode($collections),'search_text'=>$search_text,'access_mode'=>$access_mode,'updated_at'=>PLDR_Core::now()),array('id'=>$document_id));
                if(false===$ok) throw new RuntimeException('Canonical document family metadata could not be updated.');
            }

            $expires = self::date_or_null($data['rights_expires_at'] ?? '');
            $year = absint($data['publication_year'] ?? 0);
            if ($year && ($year < 1000 || $year > ((int) gmdate('Y') + 1))) {
                throw new RuntimeException('Publication year is outside the accepted range.');
            }
            $pages = absint($data['pages'] ?? 0);
            if ($pages < 1 || $pages > 1000000) {
                throw new RuntimeException('A valid positive page count is required.');
            }
            $isbn = preg_replace('/[^0-9Xx-]/', '', (string) ($data['isbn'] ?? '')) ?: '';
            $ok = $wpdb->insert(PLDR_Core::table('editions'), array(
                'document_id' => $document_id,
                'edition_label' => sanitize_text_field((string) ($data['edition_label'] ?? '')),
                'isbn' => strtoupper($isbn),
                'publication_year' => $year,
                'pages' => $pages,
                'language' => $language,
                'author_name' => $author,
                'translator' => sanitize_text_field((string) ($data['translator'] ?? '')),
                'publisher' => sanitize_text_field((string) ($data['publisher'] ?? '')),
                'source_name' => sanitize_text_field((string) $data['source_name']),
                'license_code' => sanitize_text_field((string) ($data['license_code'] ?? $rights_basis)),
                'rights_basis' => $rights_basis,
                'territory' => sanitize_text_field((string) ($data['territory'] ?? 'Worldwide / as licensed')),
                'rights_expires_at' => $expires,
                'takedown_contact' => sanitize_text_field((string) ($data['takedown_contact'] ?? '')),
                'sha256' => $sha256,
                'object_id' => $object_id,
                'status' => $edition_status,
                'supersedes_edition_id' => !empty($data['supersedes_edition_id']) ? absint($data['supersedes_edition_id']) : ($existing_doc && ($previous=PLDR_Core::current_edition($document_id)) ? (int)$previous['id'] : null),
                'version' => 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            if (false === $ok) throw new RuntimeException('Edition record could not be saved.');
            $edition_id = (int) $wpdb->insert_id;

            $ok = $wpdb->insert(PLDR_Core::table('access_policies'), array(
                'document_id' => $document_id,
                'audience' => $access_mode,
                'entitlement_key' => sanitize_text_field((string) ($data['entitlement_key'] ?? '')),
                'download_allowed' => empty($data['download_allowed']) ? 0 : 1,
                'print_allowed' => empty($data['print_allowed']) ? 0 : 1,
                'offline_allowed' => empty($data['offline_allowed']) ? 0 : 1,
                'embargo_until' => self::date_or_null($data['embargo_until'] ?? ''),
                'version' => $existing_doc ? ((int)(PLDR_Core::policy($document_id)['version'] ?? 0) + 1) : 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            if (false === $ok) throw new RuntimeException('Access policy could not be saved.');

            if ($existing_doc && 'published' === $edition_status) {
                $superseded=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('editions').' SET status=%s,updated_at=%s WHERE document_id=%d AND id<>%d AND status=%s','superseded',PLDR_Core::now(),$document_id,$edition_id,'published'));
                if(false===$superseded)throw new RuntimeException('Existing published editions could not be superseded.');
                $published=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d','published',PLDR_Core::now(),$document_id));
                if(false===$published)throw new RuntimeException('Canonical document publication state could not be updated.');
                $document_status='published';
            }

            if (!empty($data['rights_evidence_ref'])) {
                $rights_saved=$wpdb->insert(PLDR_Core::table('rights_cases'), array(
                    'case_key' => PLDR_Core::uuid(),
                    'document_id' => $document_id,
                    'reporter_id' => $actor_id,
                    'parent_case_id' => null,
                    'state' => 'closed',
                    'reason' => 'publication-rights-evidence',
                    'evidence_json' => wp_json_encode(array('restricted_reference' => sanitize_text_field((string) $data['rights_evidence_ref']))),
                    'decision_note' => 'Rights evidence reference recorded at ingest; evidence content remains outside public DTOs.',
                    'assigned_to' => $actor_id,
                    'version' => 1,
                    'created_at' => PLDR_Core::now(),
                    'updated_at' => PLDR_Core::now(),
                    'closed_at' => PLDR_Core::now(),
                ));
                if(false===$rights_saved)throw new RuntimeException('Restricted rights-evidence reference could not be recorded.');
            }
            if(false===$wpdb->query('COMMIT'))throw new RuntimeException('PDF ingest transaction could not be committed atomically.');
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            PLDR_Storage::delete($allocation['path']);
            return PLDR_Core::machine_error('pldr_ingest_transaction', $e->getMessage(), 500);
        }

        if (!empty($files['cover']['tmp_name']) && is_uploaded_file($files['cover']['tmp_name'])) {
            $cover_result=self::store_cover($edition_id, $files['cover'], $language, $actor_id);
            if(is_wp_error($cover_result))return PLDR_Core::machine_error('pldr_cover_reconcile','PDF ingest was committed but the supplied cover could not be persisted consistently; reconciliation is required.',503,array('committed'=>true,'edition_id'=>$edition_id,'cause'=>$cover_result->get_error_code()));
        }
        $scheduled=wp_schedule_single_event(time() + 5, 'pldr_generate_derivatives', array($edition_id, 0), true);
        if(is_wp_error($scheduled)||false===$scheduled)return PLDR_Core::machine_error('pldr_derivative_schedule_reconcile','PDF ingest was committed but derivative generation could not be scheduled; reconciliation is required.',503,array('committed'=>true,'edition_id'=>$edition_id));
        PLDR_Core::audit('document', $document_id, 'ingested', array('edition_id' => $edition_id, 'sha256' => $sha256, 'status' => $edition_status, 'document_family_existing'=>(bool)$existing_doc), $actor_id);
        $ingested_event=PLDR_Core::emit('PDFDocumentIngested.v1', 'document', $document_id, array('document_id' => $public_id, 'edition_id' => $edition_id, 'status' => $edition_status));
        if(is_wp_error($ingested_event))return PLDR_Core::machine_error('pldr_ingest_event_reconcile','PDF ingest was committed but its reliable event could not be persisted; reconciliation is required.',503,array('committed'=>true,'edition_id'=>$edition_id));
        if ('published' === $edition_status) {
            $published_event=PLDR_Core::emit('PDFDocumentPublished.v1', 'document', $document_id, array('document_id' => $public_id, 'edition_id' => $edition_id));
            if(is_wp_error($published_event))return PLDR_Core::machine_error('pldr_ingest_publish_event_reconcile','Published ingest was committed but its publication event could not be persisted; reconciliation is required.',503,array('committed'=>true,'edition_id'=>$edition_id));
        }

        return array('document_id' => $public_id, 'edition_id' => $edition_id, 'status' => $edition_status, 'sha256' => $sha256, 'similarity_reviewed' => (bool) $similar);
    }

    private static function validate_pdf(array $pdf) {
        if (UPLOAD_ERR_OK !== (int) ($pdf['error'] ?? UPLOAD_ERR_NO_FILE)) {
            return PLDR_Core::machine_error('pldr_upload_error', 'The PDF upload did not complete successfully.', 400);
        }
        $size = (int) ($pdf['size'] ?? 0);
        try{$max=(int)apply_filters('pldr_max_pdf_bytes',min(1024 * MB_IN_BYTES,max(1,wp_max_upload_size())));}
        catch(Throwable $e){return PLDR_Core::machine_error('pldr_pdf_size_policy','PDF size policy could not be verified; upload was not accepted.',503,array('degraded'=>true,'provider_failure'=>true));}
        $max=max(1024,min(1024*MB_IN_BYTES,$max));
        if ($size < 32 || $size > $max) {
            return PLDR_Core::machine_error('pldr_pdf_size', 'The PDF is empty or exceeds the governed File 12 size limit.', 413, array('max_bytes' => $max));
        }
        $path = (string) $pdf['tmp_name'];
        $head = file_get_contents($path, false, null, 0, 8);
        if (!is_string($head) || 0 !== strpos($head, '%PDF-')) {
            return PLDR_Core::machine_error('pldr_pdf_signature', 'The uploaded object does not begin with a genuine PDF signature.', 400);
        }
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return PLDR_Core::machine_error('pldr_pdf_read', 'The uploaded PDF cannot be inspected.', 400);
        }
        $tail_len = min(4096, $size);
        fseek($handle, -$tail_len, SEEK_END);
        $tail = fread($handle, $tail_len);
        fclose($handle);
        if (!is_string($tail) || false === strpos($tail, '%%EOF')) {
            return PLDR_Core::machine_error('pldr_pdf_eof', 'The PDF end marker is missing; the file may be truncated or polyglot.', 400);
        }
        $eof=strrpos($tail,'%%EOF');
        $after=false===$eof?'':substr($tail,$eof+5);
        if(''!==trim((string)$after," 	
 "))return PLDR_Core::machine_error('pldr_pdf_trailing_payload','Unexpected bytes follow the final PDF EOF marker; the object is rejected as a possible appended polyglot.',400);
        if (self::stream_contains($path, '/Encrypt')) {
            return PLDR_Core::machine_error('pldr_pdf_password', 'Password/encrypted source PDFs require controlled preprocessing and are not accepted directly.', 400);
        }
        $extension = strtolower(pathinfo((string) ($pdf['name'] ?? ''), PATHINFO_EXTENSION));
        if ('pdf' !== $extension) {
            return PLDR_Core::machine_error('pldr_pdf_extension', 'The uploaded filename must use the .pdf extension.', 400);
        }
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if ($mime && !in_array($mime, array('application/pdf', 'application/octet-stream'), true)) {
                return PLDR_Core::machine_error('pldr_pdf_mime', 'The uploaded file MIME does not match PDF.', 400);
            }
        }
        return true;
    }

    private static function scan_file(string $path, array $data) {
        try{$result=apply_filters('pldr_malware_scan',null,$path,array('filename'=>basename((string)($data['filename']??'document.pdf')),'sha256'=>hash_file('sha256',$path)));}
        catch(Throwable $e){return PLDR_Core::machine_error('pldr_scanner_provider_failed','Malware scanner provider failed; ingest is fail-closed.',503,array('degraded'=>true,'provider_failure'=>true));}
        if (is_array($result) && isset($result['status'])) {
            $status = sanitize_key((string) $result['status']);
            if ('infected' === $status || 'quarantined' === $status) {
                return PLDR_Core::machine_error('pldr_malware', 'The uploaded PDF failed the malware-safety gate.', 422);
            }
            if ('clean' === $status) {
                $provider=trim(sanitize_text_field((string)($result['provider']??'')));
                if(''===$provider)return PLDR_Core::machine_error('pldr_scanner_provenance','Malware scanner returned a clean result without provider provenance; ingest is fail-closed.',503,array('degraded'=>true,'provider_failure'=>true));
                return array('status' => 'clean', 'provider' => $provider);
            }
        }
        if (defined('PLDR_REQUIRE_MALWARE_SCANNER') && PLDR_REQUIRE_MALWARE_SCANNER) {
            return PLDR_Core::machine_error('pldr_scanner_unavailable', 'A required malware scanner is unavailable; ingest is fail-closed.', 503);
        }
        return array('status' => 'structural-pass-scanner-unavailable', 'provider' => 'none');
    }

    private static function similar_candidate(array $data): ?array {
        global $wpdb;
        $isbn = preg_replace('/[^0-9Xx]/', '', (string) ($data['isbn'] ?? '')) ?: '';
        if ($isbn) {
            $row = $wpdb->get_row($wpdb->prepare(
                'SELECT e.id,d.public_id,d.title FROM ' . PLDR_Core::table('editions') . ' e INNER JOIN ' . PLDR_Core::table('documents') . ' d ON d.id=e.document_id WHERE REPLACE(e.isbn,\'-\',\'\')=%s LIMIT 1',
                strtoupper($isbn)
            ), ARRAY_A);
            if ($row) return $row;
        }
        $needle = PLDR_Core::normalize_search((string) $data['title'] . ' ' . (string) $data['author_name']);
        $needle_length = function_exists('mb_strlen') ? mb_strlen($needle, 'UTF-8') : strlen($needle);
        if ($needle_length < 6) return null;
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT public_id,title FROM ' . PLDR_Core::table('documents') . ' WHERE search_text LIKE %s LIMIT 1',
            '%' . $wpdb->esc_like($needle) . '%'
        ), ARRAY_A);
        return $row ?: null;
    }

    private static function publish_status(int $actor_id, array $scan, string $rights_basis): string {
        if ('clean' !== ($scan['status'] ?? '')) return 'scan';
        if (PLDR_Core::founder($actor_id) || PLDR_Core::authorize('manage', 0, $actor_id)) return 'published';
        return 'rights_review';
    }

    private static function stream_contains(string $path, string $needle): bool {
        $handle = @fopen($path, 'rb');
        if (!$handle) return false;
        $overlap = max(0, strlen($needle) - 1);
        $carry = '';
        $found = false;
        while (!feof($handle)) {
            $chunk = fread($handle, 1024 * 1024);
            if (false === $chunk) break;
            $scan = $carry . $chunk;
            if (false !== strpos($scan, $needle)) { $found = true; break; }
            $carry = $overlap ? substr($scan, -$overlap) : '';
        }
        fclose($handle);
        return $found;
    }

    public static function rescan_document(int $document_id, int $actor_id = 0) {
        global $wpdb;
        $actor_id=$actor_id?:get_current_user_id();
        if(!PLDR_Core::authorize('repair',$document_id,$actor_id)&&!PLDR_Core::authorize('rights',$document_id,$actor_id))return PLDR_Core::machine_error('pldr_rescan_forbidden','Document rescan authority is required.',403);
        $edition=PLDR_Core::current_edition($document_id);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_rescan_edition_read','Current edition state could not be read reliably for rescan.',503,array('degraded'=>true));if(!$edition)return PLDR_Core::machine_error('pldr_rescan_edition','Current edition is missing.',404);
        $object=PLDR_Core::object((int)$edition['object_id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_rescan_object_read','Document object state could not be read reliably for rescan.',503,array('degraded'=>true));if(!$object||'available'!==$object['object_status'])return PLDR_Core::machine_error('pldr_rescan_object','Document object is unavailable.',409);
        $path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);if(is_wp_error($path))return $path;
        $plain=PLDR_Storage::temp('rescan');if(is_wp_error($plain))return $plain;$error='';
        if(!PLDR_Crypto::decrypt_to_file($path,$plain,$error)){PLDR_Storage::delete($plain);return PLDR_Core::machine_error('pldr_rescan_decrypt',$error?:'Document could not be decrypted for scanning.',500);}
        $scan=self::scan_file($plain,array('filename'=>$object['original_name']));PLDR_Storage::delete($plain);
        if(is_wp_error($scan)){
            $quarantined=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET scan_status=%s,object_status=%s,verified_at=%s WHERE id=%d AND object_status=%s','quarantined','quarantined',PLDR_Core::now(),(int)$object['id'],'available'));
            if(1!==$quarantined)return PLDR_Core::machine_error('pldr_rescan_quarantine_reconcile','Scanner failure occurred but object quarantine could not be persisted reliably; reconciliation is required.',503,array('scanner_error'=>$scan->get_error_code()));
            if(PLDR_Access::revoke_document($document_id,'rescan-quarantine')<0)return PLDR_Core::machine_error('pldr_rescan_revoke_reconcile','Object was quarantined after rescan failure but delivery grants could not be revoked; reconciliation is required.',503,array('committed'=>true));
            return $scan;
        }
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET scan_status=%s,verified_at=%s WHERE id=%d AND object_status=%s',$scan['status'],PLDR_Core::now(),(int)$object['id'],'available'));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_rescan_store','Verified rescan state could not be persisted reliably.',500);
        if('clean'===$scan['status']){
            $doc=PLDR_Core::document($document_id);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_rescan_document_read','Document state could not be read reliably after clean rescan.',503,array('degraded'=>true));
            if($doc&&'scan'===$doc['status']){
                $transitioned=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d AND status=%s AND version=%d','rights_review',PLDR_Core::now(),$document_id,'scan',(int)$doc['version']));
                if(1!==$transitioned)return PLDR_Core::machine_error('pldr_rescan_document_conflict','Rescan was stored but document state changed concurrently before rights review transition.',409,array('committed_scan'=>true));
            }
        }
        PLDR_Core::audit('document',$document_id,'rescanned',array('scan'=>$scan),$actor_id);
        return array('document_id'=>$document_id,'scan'=>$scan);
    }

    private static function date_or_null($value): ?string {
        if (!$value) return null;
        $timestamp = strtotime((string) $value);
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : null;
    }

    private static function store_cover(int $edition_id, array $cover, string $language, int $actor_id) {
        global $wpdb;
        if (UPLOAD_ERR_OK !== (int) ($cover['error'] ?? UPLOAD_ERR_NO_FILE) || (int) $cover['size'] > 10 * MB_IN_BYTES) return PLDR_Core::machine_error('pldr_cover_upload','The supplied cover upload is invalid or exceeds 10 MB.',400);
        $check = wp_check_filetype_and_ext($cover['tmp_name'], $cover['name'], array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'));
        if (empty($check['ext']) || empty($check['type'])) return PLDR_Core::machine_error('pldr_cover_type','The supplied cover is not an accepted image type.',400);
        $image=@getimagesize((string)$cover['tmp_name']);
        if(!is_array($image)||empty($image[0])||empty($image[1]))return PLDR_Core::machine_error('pldr_cover_decode','The supplied cover could not be decoded as a genuine image.',400);
        $pixels=(int)$image[0]*(int)$image[1];
        if($pixels<1||$pixels>40000000)return PLDR_Core::machine_error('pldr_cover_dimensions','The supplied cover exceeds the governed decoded-pixel limit.',413,array('max_pixels'=>40000000));
        $decoded_mime=sanitize_text_field((string)($image['mime']??''));
        if(''!==$decoded_mime&&$decoded_mime!==(string)$check['type'])return PLDR_Core::machine_error('pldr_cover_mime','The supplied cover extension/type does not match its decoded image format.',400);
        $allocation = PLDR_Storage::allocate('pldr');
        if (!empty($allocation['error'])) return $allocation['error'];
        $temp = PLDR_Storage::temp('cover');
        if (is_wp_error($temp)) return $temp;
        $crypto = array();$error = '';
        if (!PLDR_Crypto::encrypt_file($cover['tmp_name'], $temp, $crypto, $error) || !PLDR_Storage::atomic_commit($temp, $allocation['path'])) {
            PLDR_Storage::delete($temp);
            return PLDR_Core::machine_error('pldr_cover_encrypt','The supplied cover could not be encrypted and committed safely.',500);
        }
        $sha = hash_file('sha256', $cover['tmp_name']) ?: '';
        if(''===$sha){PLDR_Storage::delete($allocation['path']);return PLDR_Core::machine_error('pldr_cover_checksum','The cover checksum could not be computed.',500);}
        if(false===$wpdb->query('START TRANSACTION')){PLDR_Storage::delete($allocation['path']);return PLDR_Core::machine_error('pldr_cover_transaction','The cover metadata transaction could not be started.',500);}
        try{
            $stored=$wpdb->insert(PLDR_Core::table('objects'), array(
                'storage_name' => $allocation['name'], 'storage_scope' => 'pldr', 'original_name' => sanitize_file_name($cover['name']),
                'mime_type' => $check['type'], 'byte_size' => (int) $cover['size'], 'sha256' => $sha, 'encrypted_sha256' => $crypto['encrypted_sha256'],
                'key_id' => $crypto['key_id'], 'format_version' => $crypto['format'], 'scan_status' => 'derived-cover', 'object_status' => 'available', 'created_at' => PLDR_Core::now(), 'verified_at' => PLDR_Core::now(),
            ));
            if(false===$stored)throw new RuntimeException('Cover object metadata could not be stored.');
            $object_id=(int)$wpdb->insert_id;if($object_id<1)throw new RuntimeException('Cover object persistence could not be confirmed.');
            $linked=$wpdb->replace(PLDR_Core::table('derivatives'), array(
                'edition_id' => $edition_id, 'derivative_type' => 'cover', 'page_number' => 0, 'object_id' => $object_id, 'language' => $language,
                'quality_score' => 100, 'lawful_basis' => 'publisher-provided', 'status' => 'available', 'source_version' => 1, 'created_at' => PLDR_Core::now(), 'updated_at' => PLDR_Core::now(),
            ));
            if(false===$linked)throw new RuntimeException('Cover derivative metadata could not be linked.');
            if(false===$wpdb->query('COMMIT'))throw new RuntimeException('Cover metadata transaction could not be committed.');
        }catch(Throwable $e){$wpdb->query('ROLLBACK');PLDR_Storage::delete($allocation['path']);return PLDR_Core::machine_error('pldr_cover_store',$e->getMessage(),500);}
        PLDR_Core::audit('edition', $edition_id, 'cover_stored', array('object_id' => $object_id), $actor_id);
        return array('object_id'=>$object_id,'stored'=>true);
    }

    public static function generate_derivatives(int $edition_id, int $cursor = 0): void {
        global $wpdb;
        $edition = PLDR_Core::edition($edition_id);
        if (!$edition) return;
        $object = PLDR_Core::object((int) $edition['object_id']);
        if (!$object || 'available' !== $object['object_status']) return;
        $source = PLDR_Storage::path((string) $object['storage_name'], (string) $object['storage_scope']);
        if (is_wp_error($source)) return;
        $plain = PLDR_Storage::temp('derivative-source');
        if (is_wp_error($plain)) return;
        $error = '';
        if (!PLDR_Crypto::decrypt_to_file($source, $plain, $error)) {
            PLDR_Core::audit('edition', $edition_id, 'derivative_decrypt_failed', array('error' => $error));
            return;
        }

        try {
            if (class_exists('Imagick')) {
                self::generate_thumbnail_batch($edition, $plain, $cursor);
            } elseif (0 === $cursor) {
                $wpdb->replace(PLDR_Core::table('derivatives'), array(
                    'edition_id' => $edition_id, 'derivative_type' => 'preview-status', 'page_number' => 0, 'object_id' => 0, 'language' => $edition['language'],
                    'quality_score' => 0, 'lawful_basis' => 'rights-policy', 'status' => 'provider-unavailable', 'source_version' => (int) $edition['version'], 'created_at' => PLDR_Core::now(), 'updated_at' => PLDR_Core::now(),
                ));
            }
            if (0 === $cursor) {
                self::generate_ocr($edition, $plain);
            }
        } finally {
            PLDR_Storage::delete($plain);
        }
    }

    private static function generate_thumbnail_batch(array $edition, string $plain, int $cursor): void {
        global $wpdb;
        $batch = 12;$pages=max(1,(int)$edition['pages']);$end=min($pages,$cursor+$batch);
        for($page=$cursor;$page<$end;$page++){
            $allocation=null;$committed_path='';$tmp='';
            try{
                $image=new Imagick();
                if(method_exists($image,'setResourceLimit')){
                    if(defined('Imagick::RESOURCETYPE_MEMORY'))$image->setResourceLimit(Imagick::RESOURCETYPE_MEMORY,128*1024*1024);
                    if(defined('Imagick::RESOURCETYPE_MAP'))$image->setResourceLimit(Imagick::RESOURCETYPE_MAP,256*1024*1024);
                    if(defined('Imagick::RESOURCETYPE_DISK'))$image->setResourceLimit(Imagick::RESOURCETYPE_DISK,512*1024*1024);
                }
                $image->setResolution(90,90);$image->readImage($plain.'['.$page.']');
                $image->setImageFormat('jpeg');$image->setImageCompressionQuality(72);$image->thumbnailImage(180,240,true,true);
                if(method_exists($image,'stripImage'))$image->stripImage();
                $tmp=PLDR_Storage::temp('thumb');if(is_wp_error($tmp))throw new RuntimeException($tmp->get_error_message());
                if(!$image->writeImage($tmp))throw new RuntimeException('Thumbnail renderer could not write output.');
                $image->clear();
                $allocation=PLDR_Storage::allocate('pldr');if(!empty($allocation['error']))throw new RuntimeException($allocation['error']->get_error_message());
                $encrypted_tmp=PLDR_Storage::temp('thumb-enc');if(is_wp_error($encrypted_tmp))throw new RuntimeException($encrypted_tmp->get_error_message());
                $crypto=array();$error='';
                if(!PLDR_Crypto::encrypt_file($tmp,$encrypted_tmp,$crypto,$error)||!PLDR_Storage::atomic_commit($encrypted_tmp,$allocation['path'])){PLDR_Storage::delete($encrypted_tmp);throw new RuntimeException($error?:'Thumbnail encryption/storage failed.');}
                $committed_path=$allocation['path'];$sha=hash_file('sha256',$tmp)?:'';$size=filesize($tmp)?:0;PLDR_Storage::delete($tmp);$tmp='';
                if(''===$sha||$size<1)throw new RuntimeException('Thumbnail checksum/size could not be verified.');
                if(false===$wpdb->query('START TRANSACTION'))throw new RuntimeException('Thumbnail metadata transaction could not be started.');
                $stored=$wpdb->insert(PLDR_Core::table('objects'),array('storage_name'=>$allocation['name'],'storage_scope'=>'pldr','original_name'=>'page-'.($page+1).'.jpg','mime_type'=>'image/jpeg','byte_size'=>$size,'sha256'=>$sha,'encrypted_sha256'=>$crypto['encrypted_sha256'],'key_id'=>$crypto['key_id'],'format_version'=>$crypto['format'],'scan_status'=>'derived-preview','object_status'=>'available','created_at'=>PLDR_Core::now(),'verified_at'=>PLDR_Core::now()));
                if(false===$stored)throw new RuntimeException('Thumbnail object metadata could not be stored.');
                $object_id=(int)$wpdb->insert_id;if($object_id<1)throw new RuntimeException('Thumbnail object persistence could not be confirmed.');
                $linked=$wpdb->replace(PLDR_Core::table('derivatives'),array('edition_id'=>(int)$edition['id'],'derivative_type'=>'thumbnail','page_number'=>$page+1,'object_id'=>$object_id,'language'=>$edition['language'],'quality_score'=>85,'lawful_basis'=>'reader-preview','status'=>'available','source_version'=>(int)$edition['version'],'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
                if(false===$linked)throw new RuntimeException('Thumbnail derivative metadata could not be linked.');
                if(false===$wpdb->query('COMMIT'))throw new RuntimeException('Thumbnail metadata transaction could not be committed.');
                $committed_path='';
            }catch(Throwable $e){$wpdb->query('ROLLBACK');if($tmp)PLDR_Storage::delete($tmp);if($committed_path)PLDR_Storage::delete($committed_path);PLDR_Core::audit('edition',(int)$edition['id'],'thumbnail_failed',array('page'=>$page+1,'error'=>substr($e->getMessage(),0,500)));}
        }
        if($end<$pages){
            $scheduled=wp_schedule_single_event(time()+10,'pldr_generate_derivatives',array((int)$edition['id'],$end),true);
            if(is_wp_error($scheduled)||false===$scheduled)PLDR_Core::audit('edition',(int)$edition['id'],'thumbnail_continuation_schedule_failed',array('cursor'=>$end,'reconciliation_required'=>true));
        }
    }

    private static function generate_ocr(array $edition, string $plain): void {
        global $wpdb;
        try{$rights=apply_filters('pldr_ocr_allowed',true,$edition);}
        catch(Throwable $e){PLDR_Core::audit('edition',(int)$edition['id'],'ocr_rights_provider_failed',array('provider_failure'=>true));return;}
        if(!$rights){
            $stored=$wpdb->replace(PLDR_Core::table('derivatives'),array('edition_id'=>(int)$edition['id'],'derivative_type'=>'ocr-status','page_number'=>0,'object_id'=>0,'language'=>$edition['language'],'quality_score'=>0,'lawful_basis'=>'not-authorized','status'=>'rights-disabled','source_version'=>(int)$edition['version'],'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
            if(false===$stored)PLDR_Core::audit('edition',(int)$edition['id'],'ocr_status_store_failed',array('status'=>'rights-disabled'));
            return;
        }
        try{$result=apply_filters('pldr_ocr_extract_text',null,$plain,$edition);}
        catch(Throwable $e){PLDR_Core::audit('edition',(int)$edition['id'],'ocr_provider_failed',array('provider_failure'=>true));$result=null;}
        if(!is_array($result)||empty($result['pages'])||!is_array($result['pages'])){
            $stored=$wpdb->replace(PLDR_Core::table('derivatives'),array('edition_id'=>(int)$edition['id'],'derivative_type'=>'ocr-status','page_number'=>0,'object_id'=>0,'language'=>$edition['language'],'quality_score'=>0,'lawful_basis'=>'rights-policy','status'=>'provider-unavailable','source_version'=>(int)$edition['version'],'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
            if(false===$stored)PLDR_Core::audit('edition',(int)$edition['id'],'ocr_status_store_failed',array('status'=>'provider-unavailable'));
            return;
        }
        $provider=trim(sanitize_text_field((string)($result['provider']??'')));
        if(''===$provider){PLDR_Core::audit('edition',(int)$edition['id'],'ocr_provider_provenance_missing',array('provider_failure'=>true));return;}
        $language=sanitize_text_field((string)($result['language']??$edition['language']));$quality=max(0,min(100,(float)($result['quality']??0)));
        $max_pages=min(max(1,(int)$edition['pages']),5000);$stored_pages=0;$total_bytes=0;$truncated=false;
        foreach($result['pages'] as $page=>$text){
            if($stored_pages>=$max_pages){$truncated=true;break;}
            $page=absint($page);if($page<1||$page>(int)$edition['pages'])continue;
            $text=wp_strip_all_tags((string)$text);
            if(function_exists('mb_substr'))$text=mb_substr($text,0,1000000,'UTF-8');else $text=substr($text,0,1000000);
            $total_bytes+=strlen($text);if($total_bytes>50000000){$truncated=true;break;}
            $row=$wpdb->replace(PLDR_Core::table('ocr_text'),array('edition_id'=>(int)$edition['id'],'page_number'=>$page,'language'=>$language,'quality_score'=>$quality,'text_content'=>$text,'normalized_text'=>PLDR_Core::normalize_search($text),'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
            if(false===$row){PLDR_Core::audit('edition',(int)$edition['id'],'ocr_text_store_failed',array('page'=>$page,'provider'=>$provider));$status=$wpdb->replace(PLDR_Core::table('derivatives'),array('edition_id'=>(int)$edition['id'],'derivative_type'=>'ocr-status','page_number'=>0,'object_id'=>0,'language'=>$language,'quality_score'=>$quality,'lawful_basis'=>'rights-policy','status'=>'storage-failed','source_version'=>(int)$edition['version'],'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));return;}
            $stored_pages++;
        }
        if($stored_pages<1){PLDR_Core::audit('edition',(int)$edition['id'],'ocr_empty_after_validation',array('provider'=>$provider));return;}
        $status_value=$truncated?'partial-truncated':'available';
        $status=$wpdb->replace(PLDR_Core::table('derivatives'),array('edition_id'=>(int)$edition['id'],'derivative_type'=>'ocr-status','page_number'=>0,'object_id'=>0,'language'=>$language,'quality_score'=>$quality,'lawful_basis'=>'rights-policy','status'=>$status_value,'source_version'=>(int)$edition['version'],'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        if(false===$status){PLDR_Core::audit('edition',(int)$edition['id'],'ocr_status_store_failed',array('status'=>$status_value,'provider'=>$provider));return;}
        PLDR_Core::audit('edition',(int)$edition['id'],'ocr_generated',array('provider'=>$provider,'pages'=>$stored_pages,'truncated'=>$truncated));
        if($truncated)return;
        $event=PLDR_Core::emit('PDFDocumentOCRReady.v1','edition',(int)$edition['id'],array('edition_id'=>(int)$edition['id'],'quality'=>$quality,'provider'=>$provider,'pages'=>$stored_pages));
        if(is_wp_error($event))PLDR_Core::audit('edition',(int)$edition['id'],'ocr_event_reconciliation_required',array('provider'=>$provider,'pages'=>$stored_pages));
    }

}
