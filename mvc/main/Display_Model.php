<?php

  class Display_Model extends Admin_Model{
    
		
	/*[ Display Function Set ]*/ 
	  
	protected $MetaConfig;   // 檢索設定參數
	protected $SearchConf;   // 檢索設定參數  
	  
	//初始化要執行的動作以及物件
	public function __construct(){
	  parent::__construct();	
	  parent::initial_user($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['USER']);
      $this->MetaConfig   = defined('_ARCHIVE_META_SEARCH_CONFIG') ? json_decode(_ARCHIVE_META_SEARCH_CONFIG,true) : array();
	  $this->SearchConf   = isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['SEARCH_CONF']) ? $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['SEARCH_CONF'] : json_decode(_USER_PROFILE_DEFAULE,true);
	}
	
	// Decode Access Key And Regist Display
	// [input] : $ObjectMap  : #Code<StoreNo> 對應表，產生於 Built_Search_Data()
	// [input] : $ObjectCode : 資料連結代號  md5 string
	
	public function Regist_Display_Data( $ObjectMap , $ObjectCode){
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  try{    
	    
        if(!strlen($ObjectCode)==32 || !preg_match('/#'.$ObjectCode.'<([\w\d-]+?)>/',$ObjectMap,$matches)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
	    }
		
		$Display_Info = array();
	    $view_code    =''; 
		$system_id = $matches[1];
		
		// 取得對應檔案 // 關聯資料會使用典藏號作為索引
		$meta = array();
		if(preg_match('/^\d+$/',$system_id)){
		  $DB_OBJ = $this->DBLink->prepare(SQL_Display::GET_DISPLAY_METADATA());
		}else{
		  $DB_OBJ = $this->DBLink->prepare(SQL_Display::GET_DISPLAY_METADATA_BY_IDENTIFIER());  	
		}
		$DB_OBJ->bindParam(':sid',$system_id);
		if(!$DB_OBJ->execute() || !$meta=$DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}	
		
		
		// 檢查狀態
		$access_check= 0;
		
	    // 影像存取通過函數
	    if(isset($this->USER->PermissionClient['doaccess'])){
		  $rules = $this->USER->PermissionClient['doaccess'];
		  foreach($rules as $rule){
			list($field,$condition) = explode(' = ',$rule);
		    $condition = str_replace("'","",$condition);
			
			// 開放兩個讓修纂處看圖檔的條件
			if($field=='apply' && !$meta['_checked'] && !strstr($meta['_lockmode'],'密') ){
			  $meta['_view'] = '館內閱覽';
			  $meta['_checked'] = 1;
			}else if($field=='apply' && !$meta['_checked'] && strstr($meta['_lockmode'],'密')  && $meta['_view']=='不開放' ){
			  $meta['_view'] = '館內閱覽';
			  $meta['_checked'] = 1;
			}
		  }
		}
		  
		if(!$access_check){
		  $display_online_state = array('開放','限閱','會內');
		  if(!in_array($meta['_view'],$display_online_state)){
		    throw new Exception('_DISPLAY_TARGET_WAS_NOT_OPEN');  	
		  }
		  
		  // 檢查IP狀態
		  if( $this->USER->UserIP!='0.0.0.0' && !filter_var($this->USER->UserIP , FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE  )){
		    throw new Exception('_SYSTEM_ERROR_SERVER_IP_NOT_ALLOW');  		
		  }
		
		  if( $meta['_view']=='會內' ){
		  
			$user_ip = filter_var($this->USER->UserIP , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
		    if( $this->USER->UserIP!='0.0.0.0' && strlen($user_ip)  ){
			  throw new Exception('_SYSTEM_ERROR_SERVER_IP_NOT_ALLOW');   
		    }
		  
		  }
		
		  if(!$meta['_digited'] || !$meta['_checked']){
		    throw new Exception('_DISPLAY_TARGET_WAS_NOT_OPEN');	
		  }	
		}	
		
		
		// 確認物件存取狀態
		//$acc_state = self::access_rule_check( 'image_access', $store_no);
		
		$acc_type = $meta['zong']=='議事影音' ?  'video' :'image' ;	
		
		
		$view_code = System_Helper::md5_string_to_short_code($ObjectCode);
		
		// 確認是否需要加鎖
		$acc_pri = 1;
		/*
		//if( ($this->USER->UserID=='admin' || $this->USER->UserID=='ahas') && $meta['_view']=='開放' ){
		if( $_SESSION['AHAS']['CLIENT']['ACCOUNT_TYPE']=='GUEST' && $meta['_view']=='開放' ){
		
		  $user_ip = filter_var($this->USER->UserIP , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);  //被濾掉就是內部IP
		  if( strlen($user_ip)  ){
		    // 不是內部IP
		    $country_code = '';
		    $DB_IPLOC = $this->DBLink->prepare(SQL_AdRecord::GET_IP_LOCATION()); 
		    $DB_IPLOC->bindValue(':iplong', sprintf("%u", ip2long($this->USER->UserIP)));
		    $DB_IPLOC->execute();
		    if( $ip_location = $DB_IPLOC->fetch(PDO::FETCH_ASSOC)){
			  $country_code =  $ip_location['country_code'];
	        }
		    if($country_code != 'TW' || $this->USER->UserIP=='140.112.114.183'){
		      $_SESSION['AHAS']['CLIENT']['ACCESS_LOCK'] = true;
			  $acc_pri = 0;
		    }
		  }
		
		}
		*/
		
		// 註冊顯示表
		$idbs = $this->DBLink->prepare(SQL_Display::REGIST_RESULT_HISTORY()); 
		$idbs->bindParam(':CODE',$view_code,PDO::PARAM_STR);
		$idbs->bindParam(':SYSID',$meta['system_id'],PDO::PARAM_STR);
		$idbs->bindParam(':ISNO',$meta['collection'],PDO::PARAM_STR);
	    $idbs->bindParam(':SNO' ,$meta['identifier'],PDO::PARAM_STR);
	    $idbs->bindParam(':UID' ,$this->USER->UserID,PDO::PARAM_STR);
	    $idbs->bindValue(':ACCPER',$acc_pri);
	    if(!$idbs->execute()){
		  throw new Exception('_DISPLAY_REGIST_FALSE');		
		}
		
		$loginRecord = $this->DBLink->lastInsertId();
		$result['action'] = true;		
		$result['data']['resouse']   = $loginRecord.$view_code;
		$result['data']['display']   = $acc_type;
		
		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result; 
	}
	
	
	
	//-- Display Result data	
	// [input] : $ResouseKey  \d+\w{5}
	// NOTE : 處理顯示資料
	public function Display_Process_Meta($ResouseKey = ''){
	  
	  $result_list  = array();
	  $search_term  = $this->SearchConf['TEMP_Last_QuerySet'];
	  $search_term  = is_array($search_term) ? $search_term : array();
	  
	  // 設定變數
	  $metadata    = array();
	  $display     = array();  // 存放處理完成資料
	  $Mark_Term_Array    = array();  // 存放要比對之檢索條件
	  $meta_display_config= json_decode(_ARCHIVE_META_DISPLAY_CONFIG,true);
	  $result_link_map    = '';
	  
	  $auth_list = array();
	  /*
	  if(is_file($this->Config->ProfileAddress.'//Temp_AuthList.json')){
	    $auth_list = json_decode(file_get_contents($this->Config->ProfileAddress.'//Temp_AuthList.json'),true);
	  }else{
	    
	  }
	  */
	  
	
	  $result_key = parent::Initial_Result('result');
   	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		//取得原始資料
		$meta = array();
		$real_objCode = str_pad($ResouseKey,17,'0',STR_PAD_LEFT);
		
		$DB_OBJ = $this->DBLink->prepare(SQL_Display::GET_DATA_DISPLAY()); 
		$DB_OBJ->bindParam(':ACCCODE',$real_objCode,PDO::PARAM_STR);
		if( !$DB_OBJ->execute() || !$meta = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE'); 	
		}
		
		
		
		// 處理檢索條件
		foreach($search_term as $term_set_group){
			  
			$Term_Set   = array();
			$Term_Group = array();
			
			if(preg_match('/^([\w\d]+):(.*):(.*?)$/',$term_set_group,$Term_Set)){
			  array_shift($Term_Set);
			}else{
			  $Term_Set = explode(':',$term_set_group);
			} 
			
			
			switch(trim($Term_Set[2])){
			  
			  case '+':  // 正向條件
				$Term_Group = explode('|',$Term_Set[1]);
				foreach($Term_Group as $Term){
				  if( $Term && !preg_match('/~$/',$Term) ){
					
					$term_array = array();
					$term_string = mb_ereg_replace('_','.',$Term);
					$term_array  = System_helper::utf8_str_split($term_string,1);
					
					foreach($term_array as $key =>$word){
					  if(isset($term_array[$key-1]) && $term_array[$key-1]=='\\'){
						$term_array[$key] = '\\'.$term_array[$key];
						unset($term_array[$key-1]);
					  }
					}
					
					$Mark_Term = is_array($term_array) && count($term_array) ? join('(<\/?(persona?|location|subject|date)>)*',$term_array)  : $term_string;
					//isset($Mark_Term_Array[$Mark_Term]) ? $Mark_Term_Array[$Mark_Term]++ : $Mark_Term_Array[$Mark_Term]=1;  
					$Mark_Term_Array[$Mark_Term] = $Term_Set[0];
				  }
				}
				break;

			  case '-': // 反向條件
				// 不處理
				break;
				
			  default: // 其他 
				if(preg_match('/\w%$/',$Term_Set[2])){  // 處理贅詞 
				  // {\d-\d} => {\d,\d}
				  $term_array = array();
				  $term_string = mb_ereg_replace('-',',',$Term_Set[1]);
				  $Mark_Term = $term_string;
				  //isset($Mark_Term_Array[$Mark_Term]) ? $Mark_Term_Array[$Mark_Term]++ : $Mark_Term_Array[$Mark_Term]=1;  
				  $Mark_Term_Array[$Mark_Term] = $Term_Set[0];
				}
			   break;
			}
		}
		
		
		
	    // 處理檢索資料
	    $i=0;
		
		$display = array();
		
		// 設定連線參數
		$display['@SystemLink']['value'] = $meta['system_id'];
		$display['@SystemLink']['field'] = md5($meta['system_id'].'œ'.microtime(true));  //加密
		$display['@SystemLink']['match'] = false;
		
		// 設定類型
		$display['@Type']['field'] = '編目層級';
		$display['@Type']['value'] = $meta['data_type'];
		$display['@Type']['match'] = false;
		
		// 設定模式
		$display['@Privacy']['field'] = '密等';
		$display['@Privacy']['value'] = $meta['_lockmode'];
		$display['@Privacy']['apply'] = $meta['_lockmode'] == '普通' ? '普通':'密';
		$display['@Privacy']['match'] = false;
		
		$display['@Auditint']['field'] = '隱私問題';
		$display['@Auditint']['value'] = $meta['_auditint'];
		$display['@Auditint']['apply'] = $meta['_auditint'] ? '隱私' : '無隱私';
		$display['@Auditint']['match'] = false;
		
		$display['@Digited']['field'] = '數位化';
		$display['@Digited']['value'] = $meta['_digited'];
		$display['@Digited']['apply'] = $meta['_digited'] ? '已數位化' : '尚未數位化';
		$display['@Digited']['match'] = false;
		
		$display['@ViewMode']['field'] = '閱覽方式';
		$display['@ViewMode']['value'] = '';  // action mode
		$display['@ViewMode']['apply'] = '';  // action name
		$display['@ViewMode']['match'] = false;
		
		switch($meta['_view']){
		  case '開放': 
			if($meta['_digited'] && $meta['_checked']){
			  $display['@ViewMode']['apply'] ='線上閱覽';	
			  $display['@ViewMode']['value'] ='online'; 
			}else{
			  $display['@ViewMode']['apply'] ='申請調閱';	
			  $display['@ViewMode']['value'] ='apply'; 
			}
			break;
		  case '館內閱覽':
		  case '限閱':
			if($meta['_digited'] && $meta['_checked']){
			  $display['@ViewMode']['apply'] ='館內閱覽';
			  $display['@ViewMode']['value'] ='inlan';				  
			}else{
			  $display['@ViewMode']['apply'] ='申請調閱';
			  $display['@ViewMode']['value'] ='apply';   				  
			}
			break;
		  default:
			$display['@ViewMode']['apply'] ='不開放';
			break;				
		}
		
		$display['@PrintOption']['field'] = '開放列印';
		$display['@PrintOption']['value'] = false;
		$display['@PrintOption']['match'] = false;
		$user_ip = filter_var($this->USER->UserIP , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);  //被濾掉就是內部IP
		if( !strlen($user_ip)  ){
		  $display['@PrintOption']['value'] = true;
		}
		
		
		// 影像物件處理
		$result['session']['DOBJCONF'] = json_decode($meta['dobj_json'],true);
		$result['session']['DOBJVIEW'] = $display['@ViewMode']['value'];
		$result['session']['DECRYPTION'] = '';
		
		
		/* 還原 
		if(preg_match('/#([\w\d_]{32})<'.$meta[_SYSTEM_SEARCH_DATA_LINK_FIELD].'>/',$_SESSION['Data_Link_Map'],$map_match)){
		  $display['@SystemLink']['field'] = $map_match[1];
		}
		*/
		
		
		
		
		$search_meta = json_decode($meta['search_json'],true);
		$mark_person_array   = _SYSTEM_SEARCH_DATA_PERSON_MARK && isset($search_meta[_SYSTEM_SEARCH_MARK_PERSON_FIELD])  ? $search_meta[_SYSTEM_SEARCH_MARK_PERSON_FIELD] :array();
		$mark_location_array = _SYSTEM_SEARCH_DATA_LOCATION_MARK && isset($search_meta[_SYSTEM_SEARCH_DATA_LOCATION_MARK]) ? $search_meta[_SYSTEM_SEARCH_MARK_LOCATION_FIELD] :array();
		
		/*
		  隱私欄位處理
          檔案資料須將abstract_mask 取代 abstract
		*/
		if($search_meta['zong'] == '檔案'){
		  $search_meta['abstract'] =isset($search_meta['abstract_mask'] )&&$search_meta['abstract_mask'] ? $search_meta['abstract_mask'] :$search_meta['abstract'];	
		  unset($search_meta['abstract_mask']);
		}
		
		
		foreach($search_meta as $meta_field => $meta_value){
		  
		  $Meta_Value   = is_array($meta_value) ?join('，',$meta_value):$meta_value;
		  $Option_Value = $Meta_Value;
		  $Option_Access= isset($meta_display_config[$meta_field]) && $meta_display_config[$meta_field]['Access'] ? true : false;     
		  $Option_Check = isset($meta_display_config[$meta_field]) ? $meta_display_config[$meta_field]['Option'] : false;
		  
		  if($Option_Access){
		  
			switch(true){
			  case 'check'=== $Option_Check :
				if(!strlen($Option_Value) || $Option_Value=='none'){
				  break;
				}
			  case  true  === $Option_Check :
			  case  'true'  === $Option_Check :
			  case 'show' === $Option_Check :
				
				// 標記 人名
				if(count($mark_person_array) && $meta_display_config[$meta_field]['Mark-P'] ){
				  foreach($mark_person_array as $Term){
					$Term = quotemeta($Term);
					
					if( $Term && mb_ereg($Term,$Option_Value)){
					  $person_tag = isset($auth_list[$Term]) ? 'persona':'person';
					}else{
					  $person_tag = 'person';
					}
					
					$Option_Value = $Term ? mb_ereg_replace('('.$Term.')',"<".$person_tag.">\\1</".$person_tag.">",$Option_Value) : $Option_Value;
					$Option_Value = $Term ? mb_ereg_replace('{#(<'.$person_tag.'>.*?</'.$person_tag.'>)#}',"\\1",$Option_Value) : $Option_Value;
				  }
				}
				
				$Option_Value = preg_replace('/(\{\#|\#\})/','',$Option_Value);
				
				
				// 標記 地名
				if(count($mark_location_array) && $meta_display_config[$meta_field]['Mark-L'] ){  
				  foreach($mark_location_array as $Term){
					$Term = quotemeta($Term);
					$Option_Value = $Term ? mb_ereg_replace('('.$Term.')',"<location>\\1</location>",$Option_Value) : $Option_Value; 
				  }
				}
				
				// 標記檢索條件
				if(count($Mark_Term_Array) && $meta_display_config[$meta_field]['Mark-K'] ){  
				  foreach($Mark_Term_Array as $Term => $Term_Field_Code){
					
					if($Term_Field_Code=='_all' || $Term_Field_Code==$meta_field ){
					  $match_array = array();
					  if(preg_match('/\{.*?\}/',$Term)){
						/**--
						term tag 會干擾贅詞查詢標註
						因此須先將 term tag 移除後再進行 search term mark  再將term tag 導回
						--**/
						if(preg_match_all('@<(\w+)>(.*?)<\/\\1>@',$Option_Value,$matchs,PREG_SET_ORDER)){
						  foreach($matchs as $search_mark){	
							$match_array[$search_mark[2]] = $search_mark[0];
							$Option_Value = mb_ereg_replace($search_mark[0],$search_mark[2],$Option_Value);  
						  }
						}
						$Option_Value = mb_ereg_replace('('.$Term.')',"<search>\\1</search>",$Option_Value);
					  
						foreach($match_array as $term_target => $term_orl){
						  $Option_Value = mb_ereg_replace($term_target,$term_orl,$Option_Value); 
						}
					  
					  }else{
						$Option_Value = mb_ereg_replace('('.$Term.')',"<search>\\1</search>",$Option_Value);
					  }
					}
				  }
				  
				  // 處理 tag 重疊
				  if(preg_match_all('@<search>.*?</search>@',$Option_Value,$matchs,PREG_SET_ORDER)){
					foreach($matchs as $search_mark){	
					  if(preg_match('@(<\/?(person|persona|location|subject|date)>)@',$search_mark[0])){
						$search_string = preg_replace('@(<\/?(person|persona|location|subject|date)>)@','</search>\\1<search>',$search_mark[0]);						
						$Option_Value = mb_ereg_replace($search_mark[0],$search_string,$Option_Value);
					  }
					}
				  }
				}
				
			  
			  case 'print' === $Option_Check:
				
				$display[$meta_field]['field'] = isset($this->MetaConfig[$meta_field]) ? $this->MetaConfig[$meta_field]['FieldName'] : $meta_field;
				$display[$meta_field]['apply'] = $Option_Value;
				$display[$meta_field]['value'] = $Meta_Value;
				$display[$meta_field]['print'] = $meta_display_config[$meta_field]['Display'] ;
				$display[$meta_field]['match'] = preg_match('/<search>/',$Option_Value) && $meta_display_config[$meta_field]['Display']==='attach' ? true : false ;
				
				
				break;
			  
			  case  false === $Option_Check : break;
			  case  NULL  === $Option_Check : break;
			  default:  break;
			}
		  }
		}  
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  
	  $result['data']   = $display; 
	  $result['action'] = true;
	 
	  return $result;
	}
	
	
	/******************************************
	*******************************************
	  取得顯示連線代碼
	    
		參數 :
		  1. $ObjectCode 	= view code
		  2. $PageCode 		= encode string
		  3. $UserInfo 		= User data
		  4. $AccType		= Public  /  Private  影像讀取模式:公開/私有  公開模式可存取別人的影像網址  私有模式只能存取自己的影像網址
		  
		  
		  
		回傳 :
		  New_Page_Code
		  
        提醒 :
	      因代碼轉換依據每次搜尋產生，因此無須再驗證是否符合檢索條件
		  但須驗證是否存在對應序號
	
	******************************************	  
	******************************************/
	public function Built_Display_Object( $ResourceCode='' , $PageCode='' , $DobjConf = array() ,$DobjViewOnline = false ){
	  
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $ditital_object_start_page = 1;  //資料預設起始頁
	
	  try{    
	    
        if(!preg_match('/^\d+[\w\-\+=]+$/',$ResourceCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
	    }
		
		$Real_ObjectCode = str_pad($ResourceCode,17,'0',STR_PAD_LEFT);
		
		// 若已加鎖，則需要測試是否可解鎖，否則將鎖定於第一頁
		if(isset($_SESSION['AHAS']['CLIENT']['ACCESS_TEST'])){
		  $image_accesss_lock = $_SESSION['AHAS']['CLIENT']['ACCESS_TEST'];
          if(preg_match('/^(.*?)@(.*?)$/',$PageCode,$match) && $match[2]==$image_accesss_lock['recapture']){
			unset($_SESSION['AHAS']['CLIENT']['ACCESS_LOCK']); 
			unset($_SESSION['AHAS']['CLIENT']['ACCESS_TEST']); 
		    $PageCode = $match[1];
		  }else{
			$PageCode = '';  
		  }
		}else{
		  $PageCode = preg_replace('/@.*?$/','',$PageCode);
		}
		
		
		// 相關參數設定
		$AccType = $DobjViewOnline=='online' ? 'Public' : 'Private';  //讀取的影像連結是否公開  
		$Image_Access = false;
	    $Page_Limit_Pre_Year = 10000  ;  // 每年讀取限制
	    
	    $New_Encode_Seed = sha1($ResourceCode.'Ÿ'._SYSTEM_LOGIN_PW_SEED);
	    $New_Page_Info = array(); // 原始影像處理資料 
		$New_Page_View = array(); // 回傳client資料
		
	    
		// 設定檔案起始頁
		if(isset($DobjConf['position']) && count($DobjConf['position'])){
		  $first_page = array_keys($DobjConf['position'])[0];	
		  $ditital_object_start_page = 	intval($first_page) ? intval($first_page) : 1;
		}
		$Page_Decode   = self::idecode($New_Encode_Seed,rawurldecode($PageCode));
	    $Now_Page_Num  = $PageCode && $Page_Decode ? intval($Page_Decode) : $ditital_object_start_page;
	    
		
		$object = array();
		$DB_OBJ = $this->DBLink->prepare(SQL_Display::GET_DISPLAY_StoreNo($AccType)); 
	    switch($AccType){
		  case 'Private':  
		    $DB_OBJ->bindParam(':UID',$this->USER->UserID,PDO::PARAM_STR);
		  case 'Public': 
		  default:  
			$DB_OBJ->bindParam(':ACCCODE',$Real_ObjectCode,PDO::PARAM_STR); 
			break;
		}
		if(!$DB_OBJ->execute() || !$object=$DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_DISPLAY_ACCESS_NOT_FOUND');
		}
		
		
		//-- 20170529 未解鎖之圖片不可使用分享功能，將會將資料鎖定於第一頁
		if(!intval($object['Acc_Permission'])){
		  $Now_Page_Num = 1;
		}
		
		
		//-- 20170222 加入 InStoreNo 可存取快速掃描影像之條件
		$dobj_access_key = $object['InStoreNo']==$object['StoreNo'] ? $object['StoreNo'] : $object['InStoreNo'];
		
		$acc_state    = 1;  // 存取許可設定
		
		$Image_Access = self::Get_Access_Info($dobj_access_key , $Now_Page_Num ,  'display' , _SYSTEM_DIGITAL_FILE_SAVE , $acc_state ,$DobjConf);
		
		
		//註冊顯示頁面
		$DB_VIEW = $this->DBLink->prepare(SQL_Display::REGIST_DISPLAY_PAGE()); 
		$DB_VIEW->bindParam(':ACCCODE',$Real_ObjectCode,PDO::PARAM_STR);
		$DB_VIEW->bindParam(':ACCPAGE',$Now_Page_Num,PDO::PARAM_STR);
		$DB_VIEW->bindParam(':UIP',$this->USER->UserIP ,PDO::PARAM_STR);
		$DB_VIEW->bindParam(':UID',$this->USER->UserID ,PDO::PARAM_STR);
		$DB_VIEW->execute();
		
		
		// 計算影像讀取數量 不算
		 
		//$New_Page_View['result'] = 'OVERLOAD';
		//$New_Page_View['result'] = 'ACCESSNULL';
		if(!$Image_Access){
	      $New_Page_View['result'] = 'ACCESSNULL';
		}else{
	      $New_Page_Info = $Image_Access; // 影像原始資料
		  
		  $New_Page_View['page_code_up']  = rawurlencode(self::iencode($New_Encode_Seed,$Image_Access['page_up']));
	      $New_Page_View['page_code_now'] = rawurlencode(self::iencode($New_Encode_Seed,$Image_Access['page_now']));
		  $New_Page_View['page_code_dw']  = rawurlencode(self::iencode($New_Encode_Seed,$Image_Access['page_dw']));
		
		  $New_Page_View['page_option'] = '';
		  $New_Page_View['page_option'].= true  ? "<a class='tool_botton bt_link'   onclick=show_link('".$New_Page_View['page_code_now']."'); title='link'></a>":"";
		  $New_Page_View['page_option'].= true  ? "<a class='tool_botton bt_zoomin' onclick=zoom_img('img_".$New_Page_View['page_code_now']."','+'); title='zoom in'></a><a class='tool_botton bt_zoomout' onclick=zoom_img('img_".$New_Page_View['page_code_now']."','-'); title='zoom out'></a>":"";
		  $New_Page_View['print_option'] = 1;
		  $New_Page_View['page_list']   = $Image_Access['page_list'];
		  $New_Page_View['page_count']   = $Image_Access['count'];
		  
		  $New_Page_View['page_access_lock'] = isset($_SESSION['AHAS']['CLIENT']['ACCESS_LOCK']) && $_SESSION['AHAS']['CLIENT']['ACCESS_LOCK'] ? 1 : 0;
		  
		  foreach($New_Page_View['page_list'] as &$Page_Num){
		    $Page_Num = rawurlencode(self::iencode($New_Encode_Seed,$Page_Num));
		  }
		  //$New_Page_View['seed'] = $New_Encode_Seed;  // 不需要提供給 client
	    }
	    
		$result['session']['Image_Resouce_Code'] = $Real_ObjectCode;
		$result['session']['Image_Encode_Key'] = $New_Encode_Seed;
		$result['session']['Image_Access_Add'] = $New_Page_Info['addr'];
		$result['session']['Image_StoreNo']    = $New_Page_Info['stno'];
		$result['session']['Image_Block_Set']  = array();
		
		// 設定無法列印參數
		if(isset($DobjConf['block'])){
		  $start  = isset($DobjConf['block']['s']) ? $DobjConf['block']['s'] : '';
		  $finish = isset($DobjConf['block']['f']) ? $DobjConf['block']['f'] : '';;
		  
		  $block_flag = 0;
		  
		  foreach($Image_Access['list'] as $image_name ){
		    
			if(!$block_flag){
			  if( strpos( $image_name , $start ) !== false  ){
			    $block_flag = 1;
			  }
			}
			
			if($block_flag){
			  $result['session']['Image_Block_Set'][] = $image_name;
			  
			  if( strpos($image_name , $finish ) !== false  ){
			    $block_flag = 0;
			  }
			}
		  }
		}
		
		
		if(isset($Image_Access['list'][($Image_Access['page_now']-1)]) && 
		   in_array($Image_Access['list'][($Image_Access['page_now']-1)],$result['session']['Image_Block_Set'])){
		   $New_Page_View['print_option'] = 0;
		}
		
		$media_list = [];
		if(isset($DobjConf['dopath']) && $DobjConf['dopath']=='MEDIA/' ){
          
		  
		  if(isset($DobjConf['position']) && count($DobjConf['position'])){
		    foreach($DobjConf['position'] as  $order => $doinfo){
			  $media_list[] = [
			    'code' => rawurlencode(self::iencode($New_Encode_Seed,$order+1)),
				'file' =>$doinfo['file'],
				'thumb'=>$object['InStoreNo'].'/'.$doinfo['file'].'-'.str_replace(':','',$doinfo['pointer']['stime']).'.jpg',
				'stime'=>$doinfo['pointer']['stime'],				
			    'etime'=>$doinfo['pointer']['etime']				
			  ];	
			}
		  }
		  $New_Page_View['media'] = $media_list;
		  
		}
		
		
		
		$result['data']   = $New_Page_View; 
	    $result['action'] = true;
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;
	}
	
	
	
	// 解碼資料並確認檔案是否存在
	public function Decode_Image_Code( $imgCode , $imgStoreNo='' , $imgAccessAddr='' , $imgDecodeKey='' , $imgBlockSet ,  $DobjConf=array()  ){ 
	 
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
	    
		$StoreNo = $imgStoreNo;
		$file_address = '';
		$image_block_print = false;
		
		if(!file_exists(_SYSTEM_DIGITAL_LIST_BUFFER.$StoreNo.'_list.tmp')){
		  throw new Exception('_DISPLAY_FILES_NOT_FOUND');	  
		}
		
		switch($imgCode){
		  case 'OVERLOAD': $file_address=_SYSTEM_IMAGE_PATH.'System-Search_Query-Display_ImageOverLoad.jpg'; break;
		  case 'ACCESSNULL': $file_address=_SYSTEM_IMAGE_PATH.'System-Search_Query-Display_ImageNotFound.jpg'; break;
		  default:
		    $Image_File = file_get_contents(_SYSTEM_DIGITAL_LIST_BUFFER.$StoreNo.'_list.tmp');  
			$Image_List = explode("\n",$Image_File);
			array_shift($Image_List);
		    
			if(count($Image_List) && $Image_List[0] != _SYSTEM_ERROR_IMAGE_NAME){
		   
			  if( $imgCode && isset($Image_List[self::idecode($imgDecodeKey,rawurldecode($imgCode))-1])){  
				// 計算影像讀取紀錄陣列    
				// return _SYSTEM_IMAGE_PATH._SYSTEM_OVERLOAD_IMAGE_NAME;
				  $image_file = $Image_List[self::idecode($imgDecodeKey,rawurldecode($imgCode))-1];  
				  
				  if(in_array($image_file,$imgBlockSet)){
				    $image_block_print = true;
				  }
				  
				  
				  switch(_SYSTEM_DIGITAL_FILE_SAVE){
					case 'http':
					  $Page_Addr = $imgAccessAddr.$image_file;
					  $file_address=  $Page_Addr;
					  // http_check is slow /
					  //return $this->http_check( $Page_Addr ) ? $Page_Addr : false;
					  break;
			  
					case 'local':
					  $Page_Addr = $imgAccessAddr.$image_file;
					  $file_address= is_file($Page_Addr) ? $Page_Addr : false;
					  
					  var_dump($file_address);
					  
					  
					  break; 
			
					case 'ftp':
					  $Page_Addr = $imgAccessAddr.$image_file;
					  if($this->ftp_access()){
						error_reporting(0);
						$file_address= ftp_size($this->Conn_id, $Page_Addr) ? $this->ftp_address().$Page_Addr : false;
						ftp_close($this->Conn_id);
					  }else{
						$file_address= _SYSTEM_IMAGE_PATH._SYSTEM_ERROR_IMAGE_NAME;
					  }  
					  break; 
					default : $file_address= _SYSTEM_IMAGE_PATH._SYSTEM_ERROR_IMAGE_NAME;
				  }
				
			  }else{
				 $file_address= _SYSTEM_IMAGE_PATH._SYSTEM_ERROR_IMAGE_NAME;
			  }
			
			}else{
			  if(isset($Image_List[0])){
				$file_address= _SYSTEM_IMAGE_PATH.$Image_List[0];
			  }else{
				$file_address= _SYSTEM_IMAGE_PATH._SYSTEM_ERROR_IMAGE_NAME;
			  }
			}
			
			break;
		}
		
		// 登入以及位於內網的人員輸出檔名才能是正確的
		$user_ip = filter_var($this->USER->UserIP , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);  //被濾掉就是內部IP
		$filename = pathinfo($file_address,PATHINFO_BASENAME );
		$file_download_name = '';
		if( !strlen($user_ip) &&  $_SESSION['AHAS']['CLIENT']['ACCOUNT_TYPE']!='GUEST'){
		  $file_download_name = pathinfo($file_address,PATHINFO_BASENAME );
		}else{
		  $file_download_name = hash('crc32', pathinfo($file_address,PATHINFO_BASENAME ).time()).'.'.pathinfo($file_address,PATHINFO_EXTENSION);
		}
		
		// 確認影像顯示狀態
		$page_edit = [];
		if(isset($DobjConf['domask'][$filename])){
		  
		  $do_maskconf = $DobjConf['domask'][$filename];
		  
		  switch($do_maskconf['mode']){
			
			//v2系統設定遮蔽
			case 'disabled': $file_address = _SYSTEM_IMAGE_PATH.'System-Search_Query-Display_ImagePrivacyMask.jpg';  break;	
            
			//v4系統設定編輯
			case 'edit': $page_edit = $DobjConf['domask'][$filename]['conf']; break;
			default:break;  
		  }
          
		  // v4使用者設定遮蔽
		  if(isset($do_maskconf['display']) &&  !intval($do_maskconf['display']) ){
			$file_address = _SYSTEM_IMAGE_PATH.'System-Search_Query-Display_ImagePrivacyMask.jpg';  
		  }
         
		}
		
		$result['action']=true;
		$result['data']['address']    = $file_address;
		$result['data']['storeno']    = $StoreNo;
		$result['data']['filename']   = $file_download_name;
		$result['data']['warterm'][0] = $this->USER->UserInfo['user_name'] ? $this->USER->UserInfo['user_name'] : $this->USER->UserID; 
		$result['data']['warterm'][1] = $this->USER->UserIP; 
		$result['data']['warterm'][2] = $page_edit; 
		$result['data']['warterm'][3] = $image_block_print ? "禁止翻拍複製":''; 
		
	  } catch (Exception $e) {
        
		$result['data']['address']    = $file_address=_SYSTEM_IMAGE_PATH.'System-Search_Query-Display_ImageNotFound.jpg';
		$result['data']['storeno']    = '';
		$result['data']['filename']   = hash('crc32', time());
		$result['data']['warterm'][0] = ''; 
		$result['data']['warterm'][1] = $this->USER->UserIP; 
		$result['data']['warterm'][2] = ''; 
		$result['data']['warterm'][3] = $e->getMessage(); 
		
		$result['message'][] = $e->getMessage();
      } 
	  return $result;  
	}
	
	
	// 解除閱覽鎖定
	public function Access_Checker_Unlock($PointX=0 , $PointY=0 , $AccessResouceCode=''){ 
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
	    
		$user_point_x = intval($PointX) ? intval($PointX) : 0;
		$user_point_y = intval($PointX) ? intval($PointY) : 0;
		
		$lock_point_x = 0;
		$lock_point_y = 0;
		
		if(isset($_SESSION['AHAS']['CLIENT']['ACCESS_LOCK']) && $_SESSION['AHAS']['CLIENT']['ACCESS_LOCK']){
		  $image_accesss_lock = $_SESSION['AHAS']['CLIENT']['ACCESS_TEST'];  
		  $lock_point_x = isset($image_accesss_lock['point_x']) ? intval($image_accesss_lock['point_x']) : 0;
		  $lock_point_y = isset($image_accesss_lock['point_y']) ? intval($image_accesss_lock['point_y']) : 0;
		}
		
		if(!$user_point_x || !$user_point_y || !$lock_point_x || !$lock_point_y){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if( (($user_point_x >= ($lock_point_x-3)) && ($user_point_x <= ($lock_point_x+2))) && (($user_point_y >= ($lock_point_y-3)) && ($user_point_y <= ($lock_point_y+2))) ){
		  unset($_SESSION['AHAS']['CLIENT']['ACCESS_LOCK']); 
	      unset($_SESSION['AHAS']['CLIENT']['ACCESS_TEST']); 
		  
		  // 若已解鎖，則將存取鎖定取消
		  $DB_UPD = $this->DBLink->prepare(SQL_Display::UNLOCK_ACC_PERMISSION());
		  $DB_UPD->bindValue(':ACCCODE',intval(substr($AccessResouceCode,0,10)),PDO::PARAM_INT); 
		  $DB_UPD->execute();
		  
		}else{
		  //確認失敗就替換掉鎖頭
		  $_SESSION['AHAS']['CLIENT']['ACCESS_TEST']['point_x'] = rand(0,100);
		}
		
		$result['action']=true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;  
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/******************************************
	*******************************************
	  數字加解密函數
	    
		參數 :
		  1. $v   =  原始數字
		  2. $key =  加密 key  
		  2. $c_t =  加密字串
		  
		回傳 :
		  
		  PageCode
		  
        提醒 :
	      因代碼轉換依據每次搜尋產生，因此無須再驗證是否符合檢索條件
		  但須驗證是否存在對應序號
	
	******************************************	  
	******************************************/
	function iencode($key=array() , $v){
      
	  //生成參照表  想要使用的字串符，字元不得重覆。共 62 個
	  $key_array = array_unique(str_split($key));
	  $seed_array= array_diff(str_split('OlJFu0Gt1Hs2Ir3TgUfAz5By6Cx7Dq4KmVeNjRiShPkQpLoMnw8Ev9WdXcYbZa'),$key_array);
	  $code_array = array_merge($key_array,$seed_array);
	  $code=join('',$code_array);
      
	  $shift=16;
      $len = strlen($code);
      $v = ($v <<  $shift);
      $out="";
      do{
        $r = $v%$len;
        $out .= isset($code[$r]) ? $code[$r] : 0;
        $v = ($v - $r)/$len;
      }while($v>0);
      return $out;
    }

    function idecode($key=array() , $s){
      $key_array = array_unique(str_split($key));
	  $seed_array= array_diff(str_split('OlJFu0Gt1Hs2Ir3TgUfAz5By6Cx7Dq4KmVeNjRiShPkQpLoMnw8Ev9WdXcYbZa'),$key_array);
	  $code_array = array_merge($key_array,$seed_array);
	  $code=join('',$code_array);
	  
	  //var_dump($code);
	  
	  $shift=16;
      $len = strlen($code);
      $in=0;
      for($ii=0;$ii<strlen($s);$ii++){
        $r=strpos($code, $s[$ii]);
        $in += $r* pow($len,$ii);
      }
      $in = ($in >>$shift);
      return $in;
    }
	
	
	
	//取得數位檔案位址
	private function Get_Access_Info( $DobjAccessNo='' , $PageNum='' ,  $AccessMode='preView' , $LinkMode='local' , $AccessPrim = 0 , $DobjConfig=array()){
	  
	  $Folder_Access_Address = '';  // 資料聯繫位址
	  $Type_Folder    		 = '';  // 資料種類資料夾
	  $Image_List 			 = array(); // 資料夾內檔案列表
	  $Image_Access_Data 	 = array(); // 影像存取資訊
	  
	 
      //確認檔案是否是數位檔
	  function file_check($var){
		return preg_match("/\.(jpg|pdf|png|gif|flv|mp3|mp4)$/i",$var) ? true : false ;
	  }
	  
	  if($AccessPrim){
	  
	    //Get Image List 
		$Folder_Access_Address = '';
		$Image_List = array();
		
		if(is_file(_SYSTEM_DIGITAL_LIST_BUFFER.$DobjAccessNo.'_list.tmp')){
		  $Image_File = file_get_contents(_SYSTEM_DIGITAL_LIST_BUFFER.$DobjAccessNo.'_list.tmp');  
		  $Image_List = explode("\n",$Image_File);
		  $Folder_Access_Address = array_shift($Image_List);
		}
		
	    if( $Folder_Access_Address=='' || $Folder_Access_Address == 'D:\webroot\AHAS/systemFiles/'){
	      
		  $Image_List   = array(); 
		  
		  if(isset($DobjConfig['dopath']) ){
			$Folder_Access_Address = _SYSTEM_DIGITAL_FILE_PATH.$DobjConfig['dopath'].'browse/'.$DobjAccessNo.'/';
		  }else{
			$Folder_Access_Address == 'D:\webroot\AHAS/systemFiles/'; 
		  }
		  
		  switch($LinkMode){
		    case 'local':
		      // 檔案連結位址
		      if( is_dir($Folder_Access_Address) ){  
		        $Image_List = scandir($Folder_Access_Address);
		        $Image_List = array_values(array_filter($Image_List, "file_check"));
		      }
		      break;
		  
	        case 'http':
		      if( $this->http_check($Folder_Access_Address) ){
		        $image_list_page = file_get_contents($Folder_Access_Address);
	            if(preg_match_all('/<a href="(.*?\.(jpg|flv|png))">/i',$image_list_page,$ImgMatchs,PREG_SET_ORDER)){
		          foreach($ImgMatchs as $matchset){
		            $Image_List[] = $matchset[1];
		          }  
	            }	
			    $Image_List = array_values(array_filter($Image_List, "file_check"));
		      }
			  break;

		    case 'ftp':		  
		      $this->ftp_access(); 
		      if($this->FTP_Status){
			    // 檔案連結位址
			    error_reporting(0);
			    if (ftp_chdir($this->Conn_id,$Folder_Access_Address)) {
			      $Image_List = ftp_nlist($this->Conn_id,".");
			      asort($Image_List);
			      $Image_List = array_values(array_filter($Image_List, "file_check"));
	            } else { 
                  ftp_close($this->Conn_id);
			    }
		      }
		      break;
	      }
		  
		  
		  
		  if(!count($Image_List)){
		    
			$Folder_Access_Address = _SYSTEM_IMAGE_PATH;
			$Image_List = array(_SYSTEM_ERROR_IMAGE_NAME);
			file_put_contents(_SYSTEM_DIGITAL_LIST_BUFFER.$DobjAccessNo.'_list.tmp',$Folder_Access_Address."\n".join("\n",$Image_List),LOCK_EX);
			
		  }else{
		    
			if(preg_match('/^(RECORD|GAZETTE)/',$DobjConfig['dopath'])){
			  // 議事錄影像需要重新排序
			  $ImageZnumArray  = array();
			  $ImageNumeArray  = array();
			  $ImageINumArray  = array();
			  $ImageCNumArray  = array();
			  $ImageAPnumArray = array();
				
			  foreach($Image_List as $img){
				   
				if(preg_match('@-([\d_]+)\.jpg@',$img,$match) ){
				  if(preg_match('/0000_/',$match[1])){
					$ImageZnumArray[]=$img;
				  }else{
					$ImageNumeArray[]=$img;
				  }  
				}else if(preg_match('@-(0000_[\w\d]+)\.jpg@',$img)){
					$ImageINumArray[]=$img;
				}else if(preg_match('@-(ca[\d]+)\.jpg@',$img)){
					$ImageCNumArray[]=$img;
				}else if(preg_match('@-(ap[\d]+)\.jpg@',$img)){
					$ImageAPnumArray[]=$img;
				}
			  }
				
			  $ImageCNumArray = array_merge( $ImageCNumArray , $ImageINumArray );  //把Info 0000_abcd 的塞到Category 之後
			  $ImageNumeArray = array_merge( $ImageNumeArray , $ImageAPnumArray ); //把影像編號AP的排到最後面
			  $ImageNumeArray = array_merge( $ImageZnumArray , $ImageNumeArray );  //把影像編號00的排到最前面
			  $ImageNumeArray = array_merge( $ImageCNumArray , $ImageNumeArray); 
			  $Image_List = $ImageNumeArray;
			}
			
			file_put_contents(_SYSTEM_DIGITAL_LIST_BUFFER.$DobjAccessNo.'_list.tmp',$Folder_Access_Address."\n".join("\n",$Image_List),LOCK_EX);
		  
		  }
		}
		
	  }else{
		$Folder_Access_Address = _SYSTEM_IMAGE_PATH;
		$Image_List[] = 'ImageNotAccess'; 
		$DobjAccessNo = $Image_List[0];
		$PageNum = 0;
	  }
	  
	  
	  $image_num = count($Image_List);
	  
	  if($image_num && $image_num >= $PageNum){
	    
		$page_up = $PageNum > 1 ?  $PageNum-1 : 1 ;
		$page_dw = $PageNum < $image_num ?  $PageNum+1 : $image_num ;

		//preg_match_all('/('.$DobjAccessNo.'-0*)(\d+)\.(jpg|pdf|png|gif|flv|mp4)/',$Image_List[intval($page_up-1)].$Image_List[intval($PageNum-1)].$Image_List[intval($page_dw-1)],$matchs,PREG_SET_ORDER);
		
		$Image_Access_Data['stno']= $DobjAccessNo;
		$Image_Access_Data['count']= $image_num;
		$Image_Access_Data['addr'] = $Folder_Access_Address;
		$Image_Access_Data['list']  = $Image_List;
		$Image_Access_Data['page_up']  = $page_up;
		$Image_Access_Data['page_now'] = $PageNum;
	    $Image_Access_Data['page_dw']  = $page_dw;
	    $Image_Access_Data['page_list']=array();
		
		
		foreach( $Image_List as $imgKey=>$imgName){
		  $List_Page_Num = $imgKey+1;
		  switch(true){
		    case $List_Page_Num === 1:
			case abs($List_Page_Num - $PageNum) < 10:
			case abs($List_Page_Num - $PageNum) >= 10  && abs($List_Page_Num - $PageNum) < 100 && $List_Page_Num%10 === 0 : 
			case abs($List_Page_Num - $PageNum) >= 100 && abs($List_Page_Num - $PageNum) < 1000 && $List_Page_Num%200 === 0 : 
			case abs($List_Page_Num - $PageNum) >= 1000 && $List_Page_Num%1000 === 0 : 
			case $List_Page_Num === count($Image_List):
			  if(preg_match('/\.(jpg|pdf|png|gif|flv|mp4)/i',$imgName)){
			    $Image_Access_Data['page_list'][$List_Page_Num]= $List_Page_Num;
			  }
			default: break;
		  }
		}
		
		
	  }else{
	    $Image_Access_Data = false;
	  }
	  return $Image_Access_Data;
	}
	
	
	
	/******************************************  尚未處理
	  使用者存取權限檢查
	    參數   
		  1. $string  輸入字串
		  2. $method  檢查種類  UID UPW UIP  預設 mysql_real_escape_string

		回傳
		  $ReturnInfo  檢查結果
		  $ReturnInfo[info] : 訊息序號
		  $ReturnInfo[value] : 檢查回傳字串  
		
        提醒
		  
	******************************************/
	protected function access_rule_check( $CheckType='' , $StoreNo='' , $UserInfo=array() ){
	  $check_result = false;
	  
	  if($CheckType && $StoreNo && $UserInfo){
	    
        // 取得 StoreNo metadata
		$DB_GETMETA = $this->DBLink->prepare(SQL_Display::GET_OBJECT_METADATA()); 
	    if($DB_GETMETA->execute(array('StoreNo'=>$StoreNo))){
		  $DB_Meta  = $DB_GETMETA->fetch(PDO::FETCH_ASSOC);  
		}
		
		// 取得 rule list
	    $user_group = isset($UserInfo['User_Group']) ? $UserInfo['User_Group'] : array();
		$usr_gp_string = "'".join("','",$user_group)."'";
		
		$DB_RULE = array();
		$DB_GETRULE = $this->DBLink->prepare(SQL_Display::GET_OBJECT_ACCESS_RULE($usr_gp_string)); 
	    if($DB_GETRULE->execute()){
		  $DB_RULE  = $DB_GETRULE->fetchAll(PDO::FETCH_ASSOC);
		}

		$rule_check_result = array('+'=>array(),'-'=>array());
		
		foreach($DB_RULE as $rule){
		  
		  // 取得確認目標
		  $check_target = false;
		  switch($rule['limit_field']){
			case 'id': $check_target=$UserInfo['User_Name'];  break;
			case 'ip': $check_target=$UserInfo['User_IP'];break;
			default  : $check_target= isset($DB_Meta[$rule['limit_field']]) ? $DB_Meta[$rule['limit_field']]:false;break;
		  }
		  
		  // 設定預設值
		  switch($rule['permit']){
		    case '+': $rsl_default=0; $rsl_match=1;  break;
		    case '-': 
			default: $rsl_default=1; $rsl_match=0;  break;
		  }
		  
		  
		  
		  // 執行檢測
		  switch(true){
			case $rule['target']=='group': 
			case isset($DB_Meta[$rule['target']]) && preg_match('@^'.$rule['value'].'@',$DB_Meta[$rule['target']]):
			 
			  $rule_check_result[$rule['permit']][$rule['rule_no']] = $rsl_default;
			 
			  // 取得確認範圍
			  if($rule['limit_range']=='all' || $rule['limit_range']=='*'){
				$rule_check_result[$rule['permit']][$rule['rule_no']] = $rsl_match;
			  }else{
				$range = explode(',',preg_replace('/[\r\n\s]/','',$rule['limit_range']));
				foreach($range as $key=>$term){
				  if(preg_match_all("/\[(\d+)\-(\d+)\]/",$term,$matchs,PREG_SET_ORDER)){
					foreach($matchs as $mhset){
					  $set_range = array();
					  for($i=$mhset[1];$i<=$mhset[2];$i++){
						$set_range[] = $i;
					  }
					  $set_string = '('.join('|',$set_range).')';
					  $term = str_replace($mhset[0],$set_string, $term);
					}
					$range[$key]=$term;
				  }
				  // 執行確認
				  //echo '['.$rule['permit'].']'.$check_target.' = '.$term.":".preg_match('@'.$term.'@',$check_target)."<br/>";
				  
				  //脫逸 ( )
				  //$term = preg_replace('/(\(|\)|\-)/','\\\\\1',$term);
				  
				  if($check_target && preg_match('@'.$term.'@u',$check_target)){
					$rule_check_result[$rule['permit']][$rule['rule_no']] = $rsl_match; 
					break;
				  }else{
					//現在目標不符合條件範圍
				  }
				}
			  }
		    default:break;
		  } 
		}
		
		
		$rule_check['+'] = count($rule_check_result['+']) ? array_sum($rule_check_result['+']) : 0;
		$rule_check['-'] = count($rule_check_result['-']) ? array_product($rule_check_result['-']) : 1;
		
		$check_result = count($rule_check) && array_product($rule_check)>0 ? true : false;
	  }
	  
	  
	  return $check_result;
	}
	
  }
 
?>