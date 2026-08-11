<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Fingerprint {
    public static function compute_and_store(int $edition_id): array {
        global $wpdb;
        $edition=PLDR_Core::edition($edition_id);
        if(!$edition)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_edition','Edition not found.',404));
        $pages=PLDR_Future_Data::ocr_pages($edition_id);
        $sample=''; foreach(array_slice($pages,0,12) as $row)$sample.=' '.PLDR_Core::normalize_search((string)$row['text_content']);
        $ocr=self::simhash($sample);
        $meta=hash('sha256',PLDR_Core::normalize_search((string)$edition['title'].' '.(string)$edition['author_name'].' '.(string)$edition['publication_year'].' '.(string)$edition['pages']));
        $now=PLDR_Core::now();
        if($ocr)$wpdb->replace(PLDR_Core::table('scan_fingerprints'),array('edition_id'=>$edition_id,'fingerprint_type'=>'ocr-simhash64','fingerprint_value'=>$ocr,'metadata_hash'=>$meta,'version'=>1,'created_at'=>$now,'updated_at'=>$now));
        $visual=self::visual_fingerprint($edition_id);
        if($visual){$wpdb->replace(PLDR_Core::table('scan_fingerprints'),array('edition_id'=>$edition_id,'fingerprint_type'=>'visual-ahash','fingerprint_value'=>$visual,'metadata_hash'=>$meta,'version'=>1,'created_at'=>$now,'updated_at'=>$now));}
        PLDR_Core::audit('edition',$edition_id,'scan_fingerprint_computed',array('visual'=>(bool)$visual,'ocr'=>(bool)$ocr));
        return array('edition_id'=>$edition_id,'visual_fingerprint'=>$visual,'ocr_fingerprint'=>$ocr,'metadata_hash'=>$meta,'automatic_merge'=>false,'immutable_scan_family_evidence'=>true);
    }

    public static function candidates(int $edition_id): array {
        global $wpdb;
        $rowsCurrent=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('scan_fingerprints').' WHERE edition_id=%d',$edition_id),ARRAY_A)?:array();
        if(!$rowsCurrent){self::compute_and_store($edition_id);$rowsCurrent=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('scan_fingerprints').' WHERE edition_id=%d',$edition_id),ARRAY_A)?:array();}
        $current=array_column($rowsCurrent,null,'fingerprint_type'); if(!$current)return array();
        $rows=$wpdb->get_results($wpdb->prepare('SELECT f.*,e.document_id,e.edition_label,d.public_id,d.title FROM '.PLDR_Core::table('scan_fingerprints').' f JOIN '.PLDR_Core::table('editions').' e ON e.id=f.edition_id JOIN '.PLDR_Core::table('documents').' d ON d.id=e.document_id WHERE f.edition_id<>%d ORDER BY f.updated_at DESC LIMIT 1000',$edition_id),ARRAY_A)?:array();
        $grouped=array(); foreach($rows as $row)$grouped[(int)$row['edition_id']][]=$row;
        $out=array();
        foreach($grouped as $otherId=>$fingerprints){$visualDistance=null;$ocrDistance=null;$meta=false;$info=$fingerprints[0];foreach($fingerprints as $row){$meta=$meta||hash_equals((string)($current['ocr-simhash64']['metadata_hash']??$current['visual-ahash']['metadata_hash']??''),(string)$row['metadata_hash']);if('visual-ahash'===$row['fingerprint_type']&&isset($current['visual-ahash']))$visualDistance=self::hamming((string)$current['visual-ahash']['fingerprint_value'],(string)$row['fingerprint_value']);if('ocr-simhash64'===$row['fingerprint_type']&&isset($current['ocr-simhash64']))$ocrDistance=self::hamming((string)$current['ocr-simhash64']['fingerprint_value'],(string)$row['fingerprint_value']);}
            $isCandidate=($visualDistance!==null&&$visualDistance<=18)||($ocrDistance!==null&&$ocrDistance<=10)||$meta; if(!$isCandidate)continue;
            $class=($visualDistance!==null&&$visualDistance<=8)?'probable-same-scan-family':(($visualDistance!==null&&$visualDistance<=18)||($ocrDistance!==null&&$ocrDistance<=10)?'possible-same-scan-family':'metadata-similar');
            $out[]=array('edition_id'=>$otherId,'document_id'=>$info['public_id'],'title'=>$info['title'],'edition_label'=>$info['edition_label'],'visual_distance'=>$visualDistance,'ocr_distance'=>$ocrDistance,'metadata_match'=>$meta,'classification'=>$class,'automatic_merge'=>false);
            if(count($out)>=50)break;
        }
        return $out;
    }

    private static function visual_fingerprint(int $edition_id): string {
        global $wpdb;
        if(!class_exists('Imagick'))return '';
        $rows=$wpdb->get_results($wpdb->prepare('SELECT object_id,page_number FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s ORDER BY page_number ASC LIMIT 3',$edition_id,'thumbnail','available'),ARRAY_A)?:array();
        if(!$rows)return '';
        $hashes=array();
        foreach($rows as $row){$object=PLDR_Core::object((int)$row['object_id']);if(!$object)continue;$path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);if(is_wp_error($path))continue;$tmp=PLDR_Storage::temp('fingerprint');if(is_wp_error($tmp))continue;$error='';if(!PLDR_Crypto::decrypt_to_file((string)$path,(string)$tmp,$error)){PLDR_Storage::delete((string)$tmp);continue;}try{$im=new Imagick((string)$tmp);$im->setImageColorspace(Imagick::COLORSPACE_GRAY);$im->resizeImage(8,8,Imagick::FILTER_BOX,1);$pixels=$im->exportImagePixels(0,0,8,8,'I',Imagick::PIXEL_CHAR);$avg=$pixels?array_sum($pixels)/count($pixels):0;$bits='';foreach((array)$pixels as $px)$bits.=$px>=$avg?'1':'0';if(64===strlen($bits))$hashes[]=$bits;$im->clear();}catch(Throwable $e){}PLDR_Storage::delete((string)$tmp);}
        if(!$hashes)return '';
        $majority='';for($i=0;$i<64;$i++){$ones=0;foreach($hashes as $bits)$ones+=($bits[$i]??'0')==='1'?1:0;$majority.=$ones>=(count($hashes)/2)?'1':'0';}
        $hex='';for($i=0;$i<64;$i+=4)$hex.=base_convert(substr($majority,$i,4),2,16);return str_pad($hex,16,'0',STR_PAD_LEFT);
    }

    private static function simhash(string $text):string { $tokens=preg_split('/\s+/u',$text,-1,PREG_SPLIT_NO_EMPTY)?:array();if(!$tokens)return ''; $vector=array_fill(0,64,0);foreach(array_slice($tokens,0,50000) as $token){$hex=substr(hash('sha256',$token),0,16);$bin='';foreach(str_split($hex) as $h)$bin.=str_pad(base_convert($h,16,2),4,'0',STR_PAD_LEFT);for($i=0;$i<64;$i++)$vector[$i]+=($bin[$i]??'0')==='1'?1:-1;}$bits='';foreach($vector as $v)$bits.=$v>=0?'1':'0';$hex='';for($i=0;$i<64;$i+=4)$hex.=base_convert(substr($bits,$i,4),2,16);return str_pad($hex,16,'0',STR_PAD_LEFT); }
    private static function hamming(string $a,string $b):int { $a=str_pad($a,16,'0');$b=str_pad($b,16,'0');$distance=0;for($i=0;$i<16;$i++){$x=hexdec($a[$i])^hexdec($b[$i]);$distance+=substr_count(decbin($x),'1');}return $distance; }
}
