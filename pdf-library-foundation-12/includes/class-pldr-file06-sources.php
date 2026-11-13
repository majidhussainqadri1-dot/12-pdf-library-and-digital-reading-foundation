<?php
/**
 * File 12 ↔ File 06: exact edition/page, independently approved scholarly
 * bibliography and source quotation. There are NO manufactured excerpts.
 */
defined('ABSPATH') || exit;

final class PLDR_File06_Sources {
	const PREFIX='pldr_file06_sources_v1_';
	const QUOTE_PREFIX='pldr_file06_quote_v1_';

	public static function hooks():void {
		add_filter('he_v12_canonical_related_resources',array(__CLASS__,'collect'),30,2);
		add_filter('he_v12_related_resource_owner_attested_v1',array(__CLASS__,'attest'),30,3);
		add_filter('he_v12_file12_quote_assertion_v1',array(__CLASS__,'quote_assertion'),10,2);
		add_action('rest_api_init',array(__CLASS__,'routes'));
	}
	/** Reject numeric coercion: JSON identifiers must be actual positive integers. */
	private static function positive_id($value):int {
		return is_int($value)&&$value>0?$value:0;
	}
	private static function uuid($s):bool {
		return is_string($s)&&(bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',$s);
	}
	private static function link_key(string $uuid):string {return self::PREFIX.hash('sha256',strtolower($uuid));}
	private static function quote_key(array $claim):string {
		return self::QUOTE_PREFIX.hash('sha256',strtolower((string)$claim['concept_public_id']).'|'.
			(int)$claim['edition_id'].'|'.(int)$claim['page'].'|'.hash('sha256',(string)$claim['exact']));
	}
	private static function edition_gate(int $edition_id) {
		global $wpdb;
		$wpdb->last_error='';
		$edition=PLDR_Core::edition($edition_id);
		if(''!==(string)$wpdb->last_error||!is_array($edition)
			||'published'!==($edition['status']??'')
			||'published'!==($edition['document_status']??'')
			||empty($edition['sha256'])||empty($edition['rights_basis'])
			||!in_array((string)$edition['rights_basis'],array('public-domain','open-license','owned','founder-owned','licensed','permission'),true)
			||!empty($edition['rights_expires_at'])&&strtotime((string)$edition['rights_expires_at'].' UTC')<=time()
		) return null;
		$wpdb->last_error='';
		$allowed=PLDR_Access::can_access_edition($edition_id,'read',-1);
		if(''!==(string)$wpdb->last_error||!$allowed)return null;
		$doc=PLDR_Core::document((int)$edition['document_id']);
		if(''!==(string)$wpdb->last_error||!$doc||'published'!==($doc['status']??''))return null;
		$rights=PLDR_Rights_Policy::check((int)$doc['id']);
		if(is_wp_error($rights))return null;
		return array('edition'=>$edition,'document'=>$doc);
	}

	private static function approved_link(array $entry,array $concept) {
		foreach(array('concept_version','document_id','edition_id','edition_version','page','reviewer_id') as $field){
			if(!is_int($entry[$field]??null)||$entry[$field]<1)return null;
		}
		if(true!==($entry['approved']??false)
			||!self::uuid((string)($concept['id']??''))
			||!hash_equals(strtolower((string)$concept['id']),(string)($entry['concept_public_id']??''))
			||(int)($entry['concept_version']??0)!==(int)($concept['version']??0)
			||absint($entry['edition_id']??0)<1
			||absint($entry['reviewer_id']??0)<1
			||!preg_match('/^[A-Za-z0-9._:-]{8,120}$/',(string)($entry['rights_evidence_ref']??''))) return null;
		$pair=self::edition_gate((int)$entry['edition_id']);
		if(!$pair||(int)$pair['edition']['version']!==(int)($entry['edition_version']??0)
			||!hash_equals((string)$pair['edition']['sha256'],(string)($entry['edition_sha256']??''))
			||absint($pair['document']['id'])!==absint($entry['document_id']??0))return null;
		$url=PLDR_Core::route_url('read',array('id'=>$pair['document']['public_id']));
		if(!is_string($url)||!wp_http_validate_url($url))return null;
		$page=absint($entry['page']??0);
		if($page<1||$page>(int)$pair['edition']['pages'])return null;
		return array('owner_file'=>'file-12','owner_id'=>(string)$pair['document']['public_id'],
			'edition_id'=>(int)$pair['edition']['id'],'edition_version'=>(int)$pair['edition']['version'],
			'edition_sha256'=>(string)$pair['edition']['sha256'],
			'page'=>$page,'owner_version'=>(int)$pair['document']['version'],
			'source_version'=>(int)$concept['version'],'eligible'=>true,'rights_ok'=>true,
			'url'=>$url,'label'=>sanitize_text_field((string)$pair['document']['title']).' (page '.$page.')');
	}

	public static function collect($links,$dto) {
		$links=is_array($links)?$links:array();
		if(!is_array($dto)||!self::uuid((string)($dto['id']??''))||empty($dto['version']))return $links;
		$rows=get_option(self::link_key($dto['id']),array());
		foreach(array_slice(is_array($rows)?$rows:array(),0,12) as $entry) {
			if(!is_array($entry))continue;
			$link=self::approved_link($entry,$dto);
			if($link)$links[]=$link;
		}
		return $links;
	}
	public static function attest($verified,$link,$dto) {
		if(!is_array($link)||'file-12'!==($link['owner_file']??''))return $verified;
		if(!is_array($dto)||!self::uuid((string)($dto['id']??'')))return false;
		$rows=get_option(self::link_key($dto['id']),array());
		foreach(array_slice(is_array($rows)?$rows:array(),0,12) as $row) {
			if(!is_array($row))continue;
			$fresh=self::approved_link($row,$dto);
			if($fresh&&hash_equals((string)$fresh['url'],(string)($link['url']??''))
				&&hash_equals((string)$fresh['edition_sha256'],(string)($link['edition_sha256']??''))
				&&(int)$fresh['edition_id']===(int)($link['edition_id']??0)
				&&(int)$fresh['page']===(int)($link['page']??0))return true;
		}
		return false;
	}

	/**
	 * Current source assertion for File06's reviewed quotation. Only OCR
	 * source text anchored in a current and rights-cleared edition is eligible.
	 * This does not assert an independent translation or a new book.
	 */
	public static function quote_assertion($result,$claim) {
		if(!is_array($claim)||!self::uuid((string)($claim['concept_public_id']??''))
			||!is_int($claim['edition_id']??null)||!is_int($claim['page']??null)
			||!is_int($claim['concept_version']??null)
			||!is_string($claim['exact']??null)||!is_string($claim['rights_evidence_ref']??null))return null;
		$edition_id=absint($claim['edition_id']??0);
		$page=absint($claim['page']??0);
		$exact=(string)($claim['exact']??'');
		if(!$edition_id||!$page||strlen($exact)<4||strlen($exact)>3000)return null;
		$pair=self::edition_gate($edition_id);
		$approved_mapping=false;
		$related=get_option(self::link_key((string)$claim['concept_public_id']),array());
		foreach(array_slice(is_array($related)?$related:array(),0,12) as $candidate) {
			if(!is_array($candidate))continue;
			if((int)($candidate['edition_id']??0)!==$edition_id||(int)($candidate['page']??0)!==$page)continue;
			$verified=self::approved_link($candidate,array('id'=>$claim['concept_public_id'],
				'version'=>absint($claim['concept_version']??0)));
			if($verified){$approved_mapping=true;break;}
		}
		if(!$approved_mapping||!$pair||$page>(int)$pair['edition']['pages']
			||(int)($claim['edition_version']??0)!==(int)$pair['edition']['version']
			||!hash_equals((string)$pair['edition']['sha256'],(string)($claim['edition_sha256']??'')))return null;
		$proof=get_option(self::quote_key($claim),null);
		if(!is_array($proof)||true!==($proof['approved']??false)
			||!hash_equals(hash('sha256',$exact),(string)($proof['exact_sha256']??''))
			||!hash_equals((string)$pair['edition']['sha256'],(string)($proof['edition_sha256']??''))
			||!hash_equals((string)($claim['rights_evidence_ref']??''),(string)($proof['rights_evidence_ref']??''))
			||absint($proof['reviewer_id']??0)<1)return null;
		global $wpdb;
		$wpdb->last_error='';
		$rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
		if(''!==(string)$wpdb->last_error||!is_array($rows)||count($rows)!==1
			||absint($rows[0]['page_number']??0)!==$page)return null;
		$full=(string)($rows[0]['text_content']??'');
		if(''===$full||false===strpos(PLDR_Core::normalize_search($full),PLDR_Core::normalize_search($exact)))return null;
		// A narrow excerpt does not presume blanket copyright ownership.
		$length=count(preg_split('/\s+/u',trim($exact)));
		if($length>25&&!in_array((string)$pair['edition']['rights_basis'],array('public-domain','owned','founder-owned'),true))return null;
		return array('verified'=>true,'owner_file'=>'file-12',
			'edition_id'=>$edition_id,'edition_version'=>(int)$pair['edition']['version'],
			'edition_sha256'=>(string)$pair['edition']['sha256'],
			'document_public_id'=>(string)$pair['document']['public_id'],
			'source_title'=>(string)$pair['document']['title'],
			'edition_label'=>(string)$pair['edition']['edition_label'],
			'page'=>$page,'exact'=>$exact,
			'rights_basis'=>(string)$pair['edition']['rights_basis'],
			'rights_evidence_ref'=>(string)$proof['rights_evidence_ref'],
			'citation_url'=>PLDR_Core::route_url('read',array('id'=>$pair['document']['public_id'])));
	}

	public static function routes() {
		register_rest_route('pldr/v1','/file06/source/approve',array('methods'=>'POST',
			'callback'=>array(__CLASS__,'approve_source'),
			'permission_callback'=>static function(){return get_current_user_id()>0&&PLDR_Core::authorize('rights');}));
		register_rest_route('pldr/v1','/file06/source/approve-quote',array('methods'=>'POST',
			'callback'=>array(__CLASS__,'approve_quote'),
			'permission_callback'=>static function(){return get_current_user_id()>0&&PLDR_Core::authorize('rights');}));
	}
	private static function data($request) {
		if(!wp_verify_nonce((string)$request->get_header('X-WP-Nonce'),'wp_rest'))return null;
		$data=$request->get_json_params();
		return is_array($data)?$data:null;
	}
	public static function approve_source($request) {
		$data=self::data($request);
		if(null===$data)return PLDR_Core::machine_error('pldr_f06_nonce','Current session nonce required',403);
		if(!is_string($data['concept_public_id']??null)||!is_string($data['rights_evidence_ref']??null)
			||!self::positive_id($data['concept_version']??null)
			||!self::positive_id($data['edition_id']??null)
			||!self::positive_id($data['page']??null))
			return PLDR_Core::machine_error('pldr_f06_input','Canonical positive integer IDs and typed source evidence required',422);
		$uuid=strtolower(sanitize_text_field($data['concept_public_id']));
		$cv=self::positive_id($data['concept_version']);
		$edition_id=self::positive_id($data['edition_id']);
		$page=self::positive_id($data['page']);
		$rights=sanitize_text_field($data['rights_evidence_ref']);
		$pair=self::edition_gate($edition_id);
		$actor=get_current_user_id();
		if(!$actor||!self::uuid($uuid)||!$cv||!$pair||!$page||$page>(int)$pair['edition']['pages']
			||!PLDR_Core::authorize('rights',(int)$pair['document']['id'],$actor)
			||!preg_match('/^[A-Za-z0-9._:-]{8,120}$/',$rights))return PLDR_Core::machine_error('pldr_f06_input','Current edition, page, rights reviewer and evidence required',422);
		$canonical=apply_filters('he_v12_file06_concept_assertion_v1',null,$uuid,$cv);
		if(!is_array($canonical)||true!==($canonical['published']??false)||'file-06'!==($canonical['owner_file']??'')
			||!hash_equals($uuid,(string)($canonical['public_id']??'')))return PLDR_Core::machine_error('pldr_f06_owner','Current canonical knowledge record not verified',503);
		$row=array('approved'=>true,'concept_public_id'=>$uuid,'concept_version'=>$cv,
			'document_id'=>(int)$pair['document']['id'],'edition_id'=>$edition_id,
			'edition_version'=>(int)$pair['edition']['version'],'edition_sha256'=>(string)$pair['edition']['sha256'],
			'rights_evidence_ref'=>$rights,'page'=>$page,'reviewer_id'=>$actor,'reviewed_at_utc'=>gmdate('c'));
		$key=self::link_key($uuid);$stored=get_option($key,array());
		$stored=is_array($stored)?$stored:array();
		$stored=array_values(array_filter($stored,static function($r)use($edition_id,$page){return is_array($r)&&
			(absint($r['edition_id']??0)!==$edition_id||absint($r['page']??0)!==$page);}));
		if(count($stored)>=12)return PLDR_Core::machine_error('pldr_f06_cap','Too many independently approved links',409);
		$stored[]=$row;
		if(false===update_option($key,$stored,false)&&get_option($key)!==$stored)return PLDR_Core::machine_error('pldr_f06_store','Source approval could not be stored',503);
		return rest_ensure_response(array('approved'=>true,'concept_public_id'=>$uuid,'edition_id'=>$edition_id,'page'=>$page));
	}

	public static function approve_quote($request) {
		$data=self::data($request);
		if(null===$data)return PLDR_Core::machine_error('pldr_f06_nonce','Current session nonce required',403);
		if(!is_string($data['concept_public_id']??null)||!is_string($data['original_excerpt']??null)
			||!is_string($data['rights_evidence_ref']??null)
			||!self::positive_id($data['concept_version']??null)
			||!self::positive_id($data['edition_id']??null)
			||!self::positive_id($data['page']??null))
			return PLDR_Core::machine_error('pldr_f06_quote_invalid','Canonical positive integer IDs and a typed exact quotation are required',422);
		$claim=array('concept_public_id'=>strtolower(sanitize_text_field($data['concept_public_id'])),
			'edition_id'=>self::positive_id($data['edition_id']),'page'=>self::positive_id($data['page']),
			'exact'=>$data['original_excerpt'],
			'rights_evidence_ref'=>sanitize_text_field($data['rights_evidence_ref']));
		$pair=self::edition_gate((int)$claim['edition_id']);
		if(!$pair||!self::uuid($claim['concept_public_id'])
			||!PLDR_Core::authorize('rights',(int)$pair['document']['id'],get_current_user_id())
			||!preg_match('/^[A-Za-z0-9._:-]{8,120}$/',$claim['rights_evidence_ref'])
			||$claim['page']<1||$claim['page']>(int)$pair['edition']['pages']
			||strlen($claim['exact'])<4||strlen($claim['exact'])>3000)
			return PLDR_Core::machine_error('pldr_f06_quote_invalid','Current rights, source page and exact bounded excerpt required',422);
		$claim['edition_version']=(int)$pair['edition']['version'];
		$claim['edition_sha256']=(string)$pair['edition']['sha256'];
		$claim['concept_version']=self::positive_id($data['concept_version']);
		$concept=apply_filters('he_v12_file06_concept_assertion_v1',null,$claim['concept_public_id'],$claim['concept_version']);
		$link_ready=false;
		foreach((array)get_option(self::link_key($claim['concept_public_id']),array()) as $link) {
			if(!is_array($link))continue;
			if((int)($link['edition_id']??0)!==$claim['edition_id']
				||(int)($link['page']??0)!==$claim['page'])continue;
			if(self::approved_link($link,array('id'=>$claim['concept_public_id'],'version'=>$claim['concept_version']))){$link_ready=true;break;}
		}
		if(!$link_ready)return PLDR_Core::machine_error('pldr_f06_quote_mapping_missing','An independently approved File06-to-edition/page mapping is required before any quotation is approved',409);
		if(!is_array($concept)||true!==($concept['published']??false))return PLDR_Core::machine_error('pldr_f06_concept_unverified','Current File06 concept proof required',503);
		// The pending proof is not approved until the source really contains the quotation.
		global $wpdb;$wpdb->last_error='';
		$rows=PLDR_Future_Data::ocr_pages((int)$claim['edition_id'],(int)$claim['page'],1,0);
		if(''!==(string)$wpdb->last_error||count($rows)!==1||absint($rows[0]['page_number']??0)!==$claim['page']
			||false===strpos(PLDR_Core::normalize_search((string)($rows[0]['text_content']??'')),
				PLDR_Core::normalize_search($claim['exact'])))return PLDR_Core::machine_error('pldr_f06_quote_not_in_source','Exact text was not verified against this approved edition and page',422);
		$wordcount=count(preg_split('/\s+/u',trim($claim['exact'])));
		if($wordcount>25&&!in_array((string)$pair['edition']['rights_basis'],array('public-domain','owned','founder-owned'),true))
			return PLDR_Core::machine_error('pldr_f06_quote_limit','Quotation exceeds conservative licensed excerpt limit',422);
		$row=array('approved'=>true,'exact_sha256'=>hash('sha256',$claim['exact']),
			'edition_sha256'=>$claim['edition_sha256'],
			'rights_evidence_ref'=>$claim['rights_evidence_ref'],
			'reviewer_id'=>get_current_user_id(),'reviewed_at_utc'=>gmdate('c'));
		$key=self::quote_key($claim);
		if(false===update_option($key,$row,false)&&get_option($key)!==$row)return PLDR_Core::machine_error('pldr_f06_quote_store','Source quote approval could not be stored',503);
		return rest_ensure_response(array('approved'=>true,'edition_id'=>$claim['edition_id'],
			'page'=>$claim['page'],'source_sha256'=>$claim['edition_sha256'],'excerpt_sha256'=>$row['exact_sha256'],
			'rights_basis'=>$pair['edition']['rights_basis']));
	}
}
