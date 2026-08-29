<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Fingerprint {
    public static function compute_and_store(int $edition_id): array {
        global $wpdb;
        $wpdb->last_error='';$edition=PLDR_Core::edition($edition_id);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_edition_read','Edition state could not be read reliably for scan fingerprinting.',503,array('degraded'=>true)));
        if(!$edition)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_edition','Edition not found.',404));
        if(!self::can_compute($edition))return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_compute_forbidden','Scan-fingerprint computation requires document review or repair authority.',403));
        $wpdb->last_error='';
        $pages=PLDR_Future_Data::ocr_pages($edition_id,0,12,0);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'scan_fingerprint_ocr_read_failed',array('sample_limit'=>12));
            return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_ocr_read','Scan-fingerprint OCR evidence could not be read reliably; no fingerprint evidence was persisted.',503,array('degraded'=>true)));
        }
        $sample=''; foreach($pages as $row)$sample.=' '.PLDR_Core::normalize_search((string)$row['text_content']);
        $ocr=self::simhash($sample);
        $visual=self::visual_fingerprint($edition_id);
        if(is_wp_error($visual))return array('error'=>$visual);
        $meta=hash('sha256',PLDR_Core::normalize_search((string)$edition['title'].' '.(string)$edition['author_name'].' '.(string)$edition['publication_year'].' '.(string)$edition['pages']));
        $now=PLDR_Core::now();
        if(!$ocr&&!$visual)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_unavailable','No lawful OCR or visual evidence was available to compute a scan fingerprint.',409,array('degraded'=>true)));
        if(false===$wpdb->query('START TRANSACTION'))return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_transaction','Scan fingerprint persistence could not start.',500));
        $versions=array();
        if($ocr){$stored=self::store_fingerprint($edition_id,'ocr-simhash64',$ocr,$meta,$now);if(is_wp_error($stored)){$wpdb->query('ROLLBACK');return array('error'=>$stored);}$versions['ocr-simhash64']=$stored;}
        if($visual){$stored=self::store_fingerprint($edition_id,'visual-ahash',$visual,$meta,$now);if(is_wp_error($stored)){$wpdb->query('ROLLBACK');return array('error'=>$stored);}$versions['visual-ahash']=$stored;}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_commit','Scan fingerprint persistence could not be committed.',500));}
        PLDR_Core::audit('edition',$edition_id,'scan_fingerprint_computed',array('visual'=>(bool)$visual,'ocr'=>(bool)$ocr,'versions'=>$versions));
        return array('edition_id'=>$edition_id,'visual_fingerprint'=>$visual,'ocr_fingerprint'=>$ocr,'metadata_hash'=>$meta,'fingerprint_versions'=>$versions,'automatic_merge'=>false,'immutable_scan_family_evidence'=>true,'ocr_pages_sampled'=>count($pages),'atomic_persistence'=>true,'versioned_provenance'=>true);
    }

    public static function candidates(int $edition_id): array {
        global $wpdb;
        $wpdb->last_error='';$edition=PLDR_Core::edition($edition_id);if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_edition_read','Edition state could not be read reliably for scan-fingerprint review.',503,array('degraded'=>true)));if(!$edition)return array();if(!self::can_inspect($edition))return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_review_forbidden','Scan-fingerprint candidates are review evidence and require document review or repair authority.',403));
        $wpdb->last_error='';
        $rowsCurrent=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('scan_fingerprints').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'scan_fingerprint_current_read_failed',array());
            return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_current_read','Current scan-fingerprint evidence could not be read reliably; recomputation and comparison were not attempted.',503,array('degraded'=>true)));
        }
        $rowsCurrent=is_array($rowsCurrent)?$rowsCurrent:array();
        $current_types=array_column($rowsCurrent,null,'fingerprint_type');
        $missing_types=array();
        if(!isset($current_types['ocr-simhash64']))$missing_types[]='ocr-simhash64';
        if(class_exists('Imagick')&&!isset($current_types['visual-ahash']))$missing_types[]='visual-ahash';
        if(!$rowsCurrent||$missing_types){
            return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_required','Scan-fingerprint review is read-only. Compute the missing fingerprint evidence with the idempotent fingerprint POST before requesting candidates.',409,array('missing_types'=>$missing_types?:array('any-supported-fingerprint'))));
        }
        $current=array_column($rowsCurrent,null,'fingerprint_type'); if(!$current)return array();
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare('SELECT f.*,e.document_id,e.edition_label,d.public_id,d.title FROM '.PLDR_Core::table('scan_fingerprints').' f JOIN '.PLDR_Core::table('editions').' e ON e.id=f.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE f.edition_id<>%d ORDER BY f.updated_at DESC LIMIT 1001',$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'scan_fingerprint_candidate_read_failed',array('limit'=>1000));
            return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_candidate_read','Scan-fingerprint comparison evidence could not be read reliably; no empty candidate result was returned.',503,array('degraded'=>true)));
        }
        $rows=is_array($rows)?$rows:array();
        if(count($rows)>1000)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_scan_truncated','Scan-fingerprint candidate evidence exceeds the bounded 1000-row review window; no misleading partial candidate list was returned.',409,array('scan_limit'=>1000,'source_truncated'=>true)));
        $grouped=array(); foreach($rows as $row)$grouped[(int)$row['edition_id']][]=$row;
        $out=array();$candidate_seen=0;
        foreach($grouped as $otherId=>$fingerprints){$wpdb->last_error='';$other=PLDR_Core::edition((int)$otherId);if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_candidate_edition_read','Candidate edition state could not be read reliably; comparison was stopped.',503,array('degraded'=>true)));if(!$other||!self::can_inspect($other))continue;$visualDistance=null;$ocrDistance=null;$meta=false;$info=$fingerprints[0];foreach($fingerprints as $row){$meta=$meta||hash_equals((string)($current['ocr-simhash64']['metadata_hash']??$current['visual-ahash']['metadata_hash']??''),(string)$row['metadata_hash']);if('visual-ahash'===$row['fingerprint_type']&&isset($current['visual-ahash']))$visualDistance=self::hamming((string)$current['visual-ahash']['fingerprint_value'],(string)$row['fingerprint_value']);if('ocr-simhash64'===$row['fingerprint_type']&&isset($current['ocr-simhash64']))$ocrDistance=self::hamming((string)$current['ocr-simhash64']['fingerprint_value'],(string)$row['fingerprint_value']);}
            $isCandidate=($visualDistance!==null&&$visualDistance<=18)||($ocrDistance!==null&&$ocrDistance<=10)||$meta; if(!$isCandidate)continue;
            $class=($visualDistance!==null&&$visualDistance<=8)?'probable-same-scan-family':(($visualDistance!==null&&$visualDistance<=18)||($ocrDistance!==null&&$ocrDistance<=10)?'possible-same-scan-family':'metadata-similar');
            $candidate_seen++;if($candidate_seen>50)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_results_truncated','More than 50 scan-family candidates matched; no misleading partial candidate list was returned.',409,array('candidate_limit'=>50,'results_truncated'=>true)));
            $out[]=array('edition_id'=>$otherId,'document_id'=>$info['public_id'],'title'=>$info['title'],'edition_label'=>$info['edition_label'],'visual_distance'=>$visualDistance,'ocr_distance'=>$ocrDistance,'metadata_match'=>$meta,'classification'=>$class,'automatic_merge'=>false);
        }
        return $out;
    }

    private static function store_fingerprint(int $edition_id,string $type,string $value,string $meta,string $now) {
        global $wpdb;$table=PLDR_Core::table('scan_fingerprints');
        $wpdb->last_error='';$existing=$wpdb->get_row($wpdb->prepare('SELECT version,created_at FROM '.$table.' WHERE edition_id=%d AND fingerprint_type=%s',$edition_id,$type),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_fingerprint_existing_read','Existing fingerprint provenance could not be read reliably; persistence was rolled back.',503,array('fingerprint_type'=>$type,'degraded'=>true));
        if($existing){$current=max(1,(int)$existing['version']);$next=$current+1;$updated=$wpdb->query($wpdb->prepare('UPDATE '.$table.' SET fingerprint_value=%s,metadata_hash=%s,version=%d,updated_at=%s WHERE edition_id=%d AND fingerprint_type=%s AND version=%d',$value,$meta,$next,$now,$edition_id,$type,$current));if(1!==$updated)return PLDR_Core::machine_error('pldr_fingerprint_conflict','Scan fingerprint changed concurrently; persistence was rolled back.',409,array('fingerprint_type'=>$type));return $next;}
        $inserted=$wpdb->insert($table,array('edition_id'=>$edition_id,'fingerprint_type'=>$type,'fingerprint_value'=>$value,'metadata_hash'=>$meta,'version'=>1,'created_at'=>$now,'updated_at'=>$now));
        if(1!==$inserted)return PLDR_Core::machine_error('pldr_fingerprint_store','Scan fingerprint could not be stored.',500,array('fingerprint_type'=>$type));return 1;
    }

    private static function can_inspect(array $edition): bool {
        $document_id=(int)($edition['document_id']??0);
        return PLDR_Core::authorize('manage',$document_id)||PLDR_Core::authorize('rights',$document_id)||PLDR_Core::authorize('repair',$document_id);
    }

    private static function can_compute(array $edition): bool {
        if(function_exists('wp_doing_cron')&&wp_doing_cron()&&function_exists('doing_action')&&doing_action('pldr_future_fingerprint_edition'))return true;
        $document_id=(int)($edition['document_id']??0);
        return PLDR_Core::authorize('manage',$document_id)||PLDR_Core::authorize('rights',$document_id)||PLDR_Core::authorize('repair',$document_id);
    }

    private static function visual_fingerprint(int $edition_id) {
        global $wpdb;if(!class_exists('Imagick'))return '';
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare('SELECT object_id,page_number FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s ORDER BY page_number ASC LIMIT 3',$edition_id,'thumbnail','available'),ARRAY_A);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'scan_fingerprint_visual_read_failed',array('sample_limit'=>3));
            return PLDR_Core::machine_error('pldr_fingerprint_visual_read','Visual scan-fingerprint derivative evidence could not be read reliably; no fingerprint evidence was persisted.',503,array('degraded'=>true));
        }
        $rows=is_array($rows)?$rows:array();if(!$rows)return '';
        $hashes=array();
        foreach($rows as $row){$wpdb->last_error='';$object=PLDR_Core::object((int)$row['object_id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_fingerprint_visual_object_read','Visual fingerprint object state could not be read reliably.',503,array('degraded'=>true));if(!$object)continue;$path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);if(is_wp_error($path))continue;$tmp=PLDR_Storage::temp('fingerprint');if(is_wp_error($tmp))continue;$error='';if(!PLDR_Crypto::decrypt_to_file((string)$path,(string)$tmp,$error)){PLDR_Storage::delete((string)$tmp);continue;}try{$im=new Imagick((string)$tmp);$im->setImageColorspace(Imagick::COLORSPACE_GRAY);$im->resizeImage(8,8,Imagick::FILTER_BOX,1);$pixels=$im->exportImagePixels(0,0,8,8,'I',Imagick::PIXEL_CHAR);$avg=$pixels?array_sum($pixels)/count($pixels):0;$bits='';foreach((array)$pixels as $px)$bits.=$px>=$avg?'1':'0';if(64===strlen($bits))$hashes[]=$bits;$im->clear();}catch(Throwable $e){}PLDR_Storage::delete((string)$tmp);}
        if(!$hashes)return '';$majority='';for($i=0;$i<64;$i++){$ones=0;foreach($hashes as $bits)$ones+=($bits[$i]??'0')==='1'?1:0;$majority.=$ones>=(count($hashes)/2)?'1':'0';}$hex='';for($i=0;$i<64;$i+=4)$hex.=base_convert(substr($majority,$i,4),2,16);return str_pad($hex,16,'0',STR_PAD_LEFT);
    }

    private static function simhash(string $text):string { $tokens=preg_split('/\s+/u',$text,-1,PREG_SPLIT_NO_EMPTY)?:array();if(!$tokens)return ''; $vector=array_fill(0,64,0);foreach(array_slice($tokens,0,50000) as $token){$hex=substr(hash('sha256',$token),0,16);$bin='';foreach(str_split($hex) as $h)$bin.=str_pad(base_convert($h,16,2),4,'0',STR_PAD_LEFT);for($i=0;$i<64;$i++)$vector[$i]+=($bin[$i]??'0')==='1'?1:-1;}$bits='';foreach($vector as $v)$bits.=$v>=0?'1':'0';$hex='';for($i=0;$i<64;$i+=4)$hex.=base_convert(substr($bits,$i,4),2,16);return str_pad($hex,16,'0',STR_PAD_LEFT); }
    private static function hamming(string $a,string $b):int { $a=str_pad($a,16,'0');$b=str_pad($b,16,'0');$distance=0;for($i=0;$i<16;$i++){$x=hexdec($a[$i])^hexdec($b[$i]);$distance+=substr_count(decbin($x),'1');}return $distance; }
}
