<?php

  class Archive_Model extends Admin_Model{
    
	
	
	protected $SearchString;  // 查詢條件
	protected $ResultSource;  // 查詢結果列表 (已分頁)
	protected $ResultIndexs;  // 查詢結果ID陣列 for metadata
	protected $ResultZongAgg;  // 查詢結果全宗分類
	protected $ResultYearAgg;  // 年代後分類
	protected $ResultNameAgg;  // 人員後分類
	protected $ResultSubjectAgg;  // 主題後分類
	protected $ResultOrganAgg;  // 組織後分類
	protected $ResultMeetAgg;  // 會議階層
	protected $ResultCateAgg;  // 分類階層
	
	
	protected $ResultClipAgg;  // 夾最詞後分類
	
	protected $ResultCount;   // 查詢結果數量
	protected $PageNow;   
	protected $LengthEachPage;
	
	protected $MetaConfig;   // 檢索設定參數
	protected $SearchConf;   // 檢索設定參數
	protected $SearchInfo;   // 檢索處理暫存
	
	protected $SearcLevel =false;  // 是否要取得類別階層
	protected $LevelTarget='';     // 目標階層
	
	
	protected $UserQuerySet;

	
	protected $UserApplyCollectLimit = 40;
	protected $UserApplyDigitalLimit = 3000;
	
	
	
	//初始化要執行的動作以及物件
	public function __construct(){
	  parent::__construct();	
      parent::initial_user($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['USER']);
	  
	  $session_space = $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']; 
	  
	  
	  $this->MetaConfig   = defined('_ARCHIVE_META_SEARCH_CONFIG') ? json_decode(_ARCHIVE_META_SEARCH_CONFIG,true) : array();
	  $this->SearchConf   = isset($session_space['SEARCH_CONF']) ? $session_space['SEARCH_CONF'] : json_decode(_USER_PROFILE_DEFAULE,true);
	  $this->UserAccessId = isset($session_space['ACCESS_KEY']) ? $session_space['ACCESS_KEY'] : System_Helper::get_client_ip();
	  
	  /*
	  echo "<pre>";
	  var_dump($_SESSION);
	  var_dump($this->SearchConf);
	  var_dump($this->USER);
	  exit(1);
	  */
	}
	
	public function __destruct(){
	  $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['SEARCH_CONF'] = $this->SearchConf;
	  if(isset($this->USER->UserFolder)){
		file_put_contents($this->USER->UserFolder.'search.conf',json_encode($this->SearchConf));  
	  }
	  parent::__destruct();	
	}
	
	/*[ Query Function Set ]*/ 	
	
	//-- Archive Meta Page Config 
	// [input] : $DataType => ;
	public function Archive_Get_Page_Config($DataType=''){
	  
	  $result_key = parent::Initial_Result('config');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
		// 取得欄位搜尋設定
		if(!defined('_ARCHIVE_META_SEARCH_CONFIG')){
		  throw new Exception('_ARCHIVE_READ_META_CONF_ERROR');  	
		}
		$result['data']['meta_search'] = $this->MetaConfig;
		
		// 取得版面配置設定
		$result['data']['user_config'] = $this->SearchConf;
		$result['data']['user_config']['zong']['show_counter'] = 0;
		$result['data']['user_config']['zong']['show_link'] = 1;
		
		
		// 取得分類篩選
		$zong = array();
		$DB_CLASS = $this->DBLink->prepare(SQL_Archive::GET_ARCHIVE_CLASS());
		$DB_CLASS->execute();
		while($tmp = $DB_CLASS->fetch(PDO::FETCH_ASSOC)){
		  if(!isset($zong[$tmp['ztype']])) $zong[$tmp['ztype']] = array(); 
          $zong[$tmp['ztype']][$tmp['zid']] =  $tmp;
		}
		$result['data']['meta_zong'] = $zong;
		
		
		// 使用者相關資料
		$result['data']['user_record'] = array();
		
		// 取得申請暫存資料
		$apply_queue = array('list'=>array(),'sum'=>0);
		$apply_queue['list'] = isset($this->SearchConf['TEMP_Apply_Queue']) ? $this->SearchConf['TEMP_Apply_Queue']:array();
		$apply_queue['sum']  = count($apply_queue['list']);
		
		// 取得申請歷史
		$apply_history = array();
		
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_USER_APPLY_QUEUE());  // 尚未結案的申請單
		$DB_OBJ->execute(array('uid'=>$this->USER->UserNO));
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		    $DB_COUNT = $this->DBLink->prepare(SQL_Archive::APPLY_DATA_LIST());
			$DB_COUNT->execute(array('apply_code'=>$tmp['apply_code'] ));
			while($apply = $DB_COUNT->fetch(PDO::FETCH_ASSOC)){
			  $code=array();
			  $code[]=$apply['in_store_no'];
			  $code[]=$apply['store_no'];
			  if($apply['_view']=='原件閱覽' && $apply['_checked'] ){  //讓申請檢閱的人可以申請調閱
			    continue;
			  }
			  $apply_history[join('',array_filter($code))] = 1;
			}
		}
		
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_USER_APPLY_RESERVE());  // 調閱所有的原件閱覽
		$DB_OBJ->execute(array('uid'=>$this->USER->UserNO));
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  $code=array();
		  $code[]=$tmp['in_store_no'];
		  $code[]=$tmp['store_no'];
		  if( strtotime('now') > strtotime($tmp['check_range'].' 15:30:00')){  //讓尚未到期的原件閱覽不可再申請
			break;
		  }
		  $apply_history[join('',array_filter($code))] = 1;
		}
		
		
		
		$result['data']['user_record']['apply_history'] = $apply_history;
		$result['data']['user_record']['apply_queue']   = $apply_queue;
		
		
		// 取得檢索歷史
		$result['data']['user_record']['search_history'] = array();
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::GET_USER_SEARCH_HISTORY_LIST());
		if($DB_OBJ->execute(array('user'=>$this->USER->UserID))){
		  $result['data']['user_record']['search_history'] = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);
		}
		
		// 取得類別階層
		$data_collection_type = 'archive'; // 預設查詢資料類別 : archive (檔案、會議資料)
		if(isset($this->SearchInfo['doms_config']['zong_selected'])){
		  $data_collection_type = in_array('biography',$this->SearchInfo['doms_config']['zong_selected']) ? 'biography' : $data_collection_type;
		  $data_collection_type = in_array('photo',$this->SearchInfo['doms_config']['zong_selected']) ? 'photo' : $data_collection_type;
		}
		
		
		$FileLevelArray = array();
		$result['data']['zong_level'] = self::built_file_level($data_collection_type,'serial','');
		$result['data']['level_term'] = $this->LevelTarget;
		
		
		$result['action'] = true;
	   
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Archive Meta Page Data OList 
	// [input] : $PagerMaxNum => int // 頁面按鈕最大數量
	public function Archive_Get_Page_List( $PagerMaxNum=1 ){
	  
	  $result_key = parent::Initial_Result('page');
	  $result  = &$this->ModelResult[$result_key];
      
	  try{
        
		$page_show_max = intval($PagerMaxNum) > 0 ? intval($PagerMaxNum) : 1;
		$pages = array('_all'=>array(1=>''));
		
		// 必要參數，從ADMeta_Get_Meta_List而來
		$result_count = $this->ResultCount ? $this->ResultCount : 0;
		$total_page = intval( $result_count / $this->LengthEachPage ) + ($result_count%$this->LengthEachPage ? 1 :0 );
		$effect_page= $total_page;
		
		// 建構分頁籤
		for($i=1;$i<=$total_page;$i++){
		  if(($i*$this->LengthEachPage)>10000){
			$effect_page = $i-1;
			break;
		  }
		  $pages['_all'][$i] = (($i-1)*$this->LengthEachPage+1).'-'.($i*$this->LengthEachPage);
		}
		
		if($total_page){
		  $pages['top']   = reset($pages['_all']);
		  $pages['end']   = end($pages['_all']);
		  $pages['prev']  = ($this->PageNow-1 > 0 ) ? $pages['_all'][$this->PageNow-1] : $pages['_all'][$this->PageNow];
		  $pages['next']  = ($this->PageNow+1 < $effect_page ) ? $pages['_all'][$this->PageNow+1] : $pages['_all'][$this->PageNow];
		  $pages['now']   = $this->PageNow;  
		  $this->SearchConf['SET_PageNow'] = $this->PageNow;
		}else{
		  $pages['top']   = 0;
		  $pages['end']   = 0;
		  $pages['prev']  = 0;
		  $pages['next']  = 0;	
		}
		
		
		$check = ($page_show_max-1)/2;
	    if($effect_page < $page_show_max){
		  $pages['list'] = $pages['_all'];  	
		}else {  
          if( ($this->PageNow - $check) <= 1 ){    // 抓最前面 X 個
            $start = 0;
		  }else if( ($this->PageNow + $check) > $effect_page ){  // 抓最後面 X 個
            $start = $effect_page-(2*$check)-1;    
		  }else{
            $start = $this->PageNow - $check -1;
		  }
	      $pages['list'] = array_slice($pages['_all'],$start,$page_show_max,TRUE);
		}
		
		// 建構選項
		for($x=1;$x<=$effect_page;$x++){
	      if($x==1 || $x==$effect_page || abs($x-$this->PageNow)<20){
	        $pages['jump'][$x] = $pages['_all'][$x];
	      }else if(abs($x-$this->PageNow)<100 && $x%10===0){
	        $pages['jump'][$x] = $pages['_all'][$x];
	      }else if(abs($x-$this->PageNow)<1000 && $x%200===0){
	        $pages['jump'][$x] = $pages['_all'][$x];
	      }else if(abs($x-$this->PageNow)>=1000 &&  abs($x-$this->PageNow)<10000 && $x%1000===0){
	        $pages['jump'][$x] = $pages['_all'][$x];
	      }else if(abs($x-$this->PageNow)>=10000 && $x%10000===0){
	        $pages['jump'][$x] = $pages['_all'][$x];
	      }
	    }
		
		unset($pages['_all']);
		
		
		$result['data']   = $pages;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	  	
	}
	
	
	//-- Archive Search Condition Process STEP 1 : normailize search condition	
	// [input] : SearchString   : accnum:urlencode(base64encode(json_pass()))  = array(F=>V)
	// NOTE:  accnum is default condition 
	// [OUTPUT]: $this->UserQuerySet
	
	public function Archive_ReForm_Search( $SearchType='index',$SearchString=''){
	    
      //OLD ATTR : $CouncilCode , $UserAccessId , $AccessNum , $ActionFrom , $NewQueryField ,$NewQueryString
      
	  $result_key = parent::Initial_Result('search');
   	  $result  = &$this->ModelResult[$result_key];
	  
	  $access_num  = 0;
	  $parent_query_term_set = array();  // 上一次的檢索條件,由accnum查詢而來
	  $user_query_string_set = array();  // 初始分割
	  $user_query_normal_set = array();  // 第一階段處理分割
	  
	  $search_ap_code_2_name = array('t%'=>'termpat','c%'=>'clipterm');
	  
	  $this->SearchInfo = array();
	  $this->SearchInfo['date_range']  = array();
	  $this->SearchInfo['term_filter'] = array(); // 後分類設定
	  $this->SearchInfo['doms_config'] = array(); // 介面設定參數
	  
	  
	  try{
        
		$search_sets = json_decode(base64_decode(str_replace('*','/',rawurldecode($SearchString))),true); 
        
		// 取得欄位搜尋設定
		if(!defined('_ARCHIVE_META_SEARCH_CONFIG')){
		  throw new Exception('_ARCHIVE_READ_META_CONF_ERROR');  	
		}
		
		// 檢索歷史存入:query_hash,:user_id,:acc_num,:query_string,:page
		
		if( is_array($search_sets) && count($search_sets) ){
		  $query_hash = md5($this->USER->UserID.serialize($search_sets['query']));
		  $DB_OBJ = $this->DBLink->prepare(SQL_Archive::INSERT_SEARCH_HISTORY_TABLE());
          $DB_OBJ->bindValue(':query_hash'	, $query_hash );
		  $DB_OBJ->bindValue(':user_id'		, $this->USER->UserID );
		  $DB_OBJ->bindValue(':acc_num'		, $this->SearchConf['TEMP_Last_QueryAccNum'] );
		  $DB_OBJ->bindValue(':query_string', json_encode($search_sets, JSON_UNESCAPED_UNICODE) );
		  $DB_OBJ->bindValue(':page'		, $this->SearchConf['SET_PageNow'] );
          $DB_OBJ->execute();    
		}
		
		
		// 查核accnum
		/* 2016版已無繼續查詢之設定 因此先註解
		if( !isset($search_sets['accnum']) ||  !intval($search_sets['accnum']) ){
		  // 新檢索
	      
		  $access_num = 0;
		  $this->SearchConf['SET_LevelMark'] = '';
		  $this->SearchConf['SET_PageNow']   = 1;
          
		}else{
		  // 繼承檢索組合
		 
		  //$DB_OBJ		= $this->DBLink->prepare(SQL_Archive::GET_SEARCH_TEMP_RECORD());
	      //$DB_Result 	= $DB_OBJ->execute(array('ACCNUM'=>$search_sets['accnum'],'UAID'=>$this->USER->UserID)); 
		  //$DB_Data	    = $DB_OBJ->fetch(PDO::FETCH_ASSOC);
		
		  //if(isset($DB_Data['Query_Term_Set'])){
		  //  $parent_query_term_set = explode('⊕',$DB_Data['Query_Term_Set']);
          //}
		  
		  $access_num = $search_sets['accnum'];
		  
		}
        */
		
		// 設定參照變數
		$this->SearchInfo['access_from']    = $access_num;
		$this->SearchInfo['action_from']    = $SearchType;
		$this->SearchInfo['doms_config']    = isset($search_sets['domconf']) ? $search_sets['domconf'] : array();  // 介面設定參數
		
		
		// 處理參數，防止進入過濾階段
		unset($search_sets['domconf']);
		unset($search_sets['accnum']);
		
	    
		
		// 依據情形處理對應參數
		switch($SearchType){
		  
		  case 'index': //取得最後一次檢索結果
		    
			$user_query_normal_set[] = array('field'=>'data_type','value'=>['archive'],'attr'=>'+');
			
			/*
			if( isset($this->SearchConf['TEMP_Last_QueryTerm']) && isset($this->SearchConf['TEMP_Last_QuerySet'])){
			  $parent_query_term_set = explode('⊕',$this->SearchConf['TEMP_Last_QuerySet']);
			}
			*/
			break;
			
		  //使用者輸入檢索
		  case 'search':  
		    
		    // 確認搜尋參數
		    if(!count($search_sets)){
		      throw new Exception('_ARCHIVE_SEARCH_QUERY_FAIL');  	
		    }
		    
			$set_counter = 0;    
			
			//$System_Chan_Code = array('∩','∪');
		    
			
			// 設定一般搜尋條件
			if(isset($search_sets['query'])){
				
				foreach($search_sets['query'] as $i=>$query_set){
				  
				  $field         = $query_set['field'];
				  $search_string = $query_set['value'];   
				  
				  
				  $search_attr   = isset($query_set['attr']) ? $query_set['attr'] : '+';
				  
				  $search_string = trim($search_string);
				  $search_string = html_entity_decode($search_string,ENT_COMPAT,'UTF-8');
				  
				  if(in_array($search_attr,array('t%','c%'))){
					  
					  $normal_string = $search_string;
					  
				  }else{
					  
					  $normal_string = '';  // 正規化後的字串
					  
					  //處理 在兩引號""間保留原始查詢條件  用來處理  英文多字之辭彙查詢  如  National Reconditioning
					  $term_keep  = false;
					  $quat_cont  = 0;
					  $word_count = mb_strlen($search_string);
					  
					  for( $spoint=0 ; $spoint < $word_count ; $spoint++){
						
						$word = mb_substr($search_string,$spoint,1);   //將條件逐字拆開
					
						if($word == '"'){				//如果遇到開頭引號  則開啟保留功能  結束引號則關閉功能
						  $quat_cont++;
						  $term_keep = ($quat_cont%2) ? TRUE:FALSE;  
						  continue;
						}
					  
						switch($word){
						  
						  case '-' :
							$prev = mb_substr($search_string,($spoint-1),1);
							if($prev==' '){
							  $normal_string.= $term_keep ? $word:'∩-';	
							}else{
							  $normal_string.= $word;	 	
							}
							break;
							
						  case '+' :
						  case '&' :
						  case '＆':
							$normal_string.= $term_keep ? $word:'∩';
							break;
						  case ' ':
						  case '|':
						  case '｜':
							$normal_string.= $term_keep ? $word:'∪';
							break;
						  default:  $normal_string.= $word; break;
						}
					  }
				  }
				  
				  $search_terms = preg_split('/∩/',$normal_string);
				  
				  foreach($search_terms as $i=> $term){
				  
					$User_Search_format = array(); //接收使用者自訂欄位
					$user_search_field    = '';  // 將要處理的檢索欄位
					$user_search_term	  = '';  // 將要處理的檢索條件
					
					/* 
					暫不處理特殊搜尋
					termpat
					clipterm
					*/
					
					$user_search_field 	= $field;
					$user_search_term	= trim($term);
					
					
					/*****----- 以下處理檢索詞彙 -----*****/
					
					if(!strlen($user_search_term)){
					  continue;
					}
					
					$user_query_normal_set[$set_counter] = array();
					
					// 處理詞彙 NOT 或 function 狀態
					if( preg_match('/^\+/',$user_search_term) && mb_strlen($user_search_term)>1  ){
					  $user_search_term = preg_replace('/^\+/','',$user_search_term);
					  $user_query_normal_set[$set_counter]['attr'] = '+';
					}else if( (preg_match('/^-/',$user_search_term) && mb_strlen($user_search_term)>1 ) || $search_attr=='-' ){
					  $user_search_term = preg_replace('/^-/','',$user_search_term);
					  $user_query_normal_set[$set_counter]['attr'] = '-';
					}else if(preg_match('/\{[\d\-\,]+\}/',$user_search_term,$ap_mode_check)){
					  $user_query_normal_set[$set_counter]['attr'] = $search_attr;
					}else if(preg_match('/[ct]%/',$user_search_term,$ap_mode_check)){
					  $user_query_normal_set[$set_counter]['attr'] = $ap_mode_check[0];
					}else{
					  $user_query_normal_set[$set_counter]['attr'] = '+';
					}
					  
					//處理詞彙 or 狀態
					$user_query_normal_set[$set_counter]['field'] = $user_search_field;
					$user_query_normal_set[$set_counter]['value'] = array_filter(preg_split('/∪/',$user_search_term)); 
					  
					$set_counter++;
				  }
				
				} 
			
			}
			
			
			//其他搜尋參數
			
			//-1 型態 
			if(isset($search_sets['format']) && count($search_sets['format'])){
			  $user_query_normal_set[] = array('field'=>'format','value'=>$search_sets['format'],'attr'=>'+');	
			}
			
			//-2 全宗
			if(isset($search_sets['zong']) && count($search_sets['zong'])){
			  $user_query_normal_set[] = array('field'=>'zong','value'=>$search_sets['zong'],'attr'=>'+');	
			  $this->SearchInfo['doms_config']['zong_selected'] = $search_sets['zong'];
			}
			
			//-3 日期 yearnum
			if(isset($search_sets['yearnum']) && count($search_sets['yearnum'])){
			  $user_query_normal_set[] = array('field'=>'yearnum','value'=>$search_sets['yearnum'],'attr'=>'+');	
			}
			
		    //-4 日期 dayrange
			if(isset($search_sets['dayrange']) && count($search_sets['dayrange'])){
			  $this->SearchInfo['date_range'] = $search_sets['dayrange'];
			}
			
			
			//-6 後分類篩選
			if(isset($search_sets['filter']) && count($search_sets['filter'])){
			  $this->SearchInfo['term_filter'] = $search_sets['filter'];
			  unset($search_sets['filter']);
			}
			
			
			break;	
		  
		  
		  case 'myapply': // 取得調閱清單資料 
		      
			$GET_APPLY = $this->DBLink->prepare(SQL_Archive::GET_USER_APPLY_INDEX());  
			$GET_APPLY->bindParam(':apply_code',$SearchString);
			$GET_APPLY->bindParam(':uid',$this->USER->UserNO);
			$GET_APPLY->execute();  
			
			$user_query_normal_set[0]['field'] = 'applyindex';
			$user_query_normal_set[0]['attr'] = '+';
			while( $tmp = $GET_APPLY->fetch(PDO::FETCH_ASSOC)){
			  $user_query_normal_set[0]['value'][] = $tmp['in_store_no'].$tmp['store_no'];
			}
			$this->SearchConf['TEMP_Last_QueryTerm'] = $user_query_normal_set[0]['value'];
			break;
			
		  default: break;	
		}
		
		//var_dump( $parent_query_term_set);
	    //var_dump( $user_query_normal_set);
		
		
		foreach($user_query_normal_set as $term_set_num => &$term_set_array){
		  
		  // 若開頭為反，則錯誤
		  if($term_set_num==0 && $term_set_array['attr']=='-'){
			throw new Exception('_ARCHIVE_SEARCH_TOTAL_TERM_INVERSE_ILLEGAL');  
		  }
		  
		  
		  /*
		  $term_set_array['field']
		  $term_set_array['value']
		  $term_set_array['attr']
		  */
		  $checked_term_array = array();
		  
		  foreach($term_set_array['value'] as $term_string){
			
			$temp_term 	= $term_string;
			$AP_Count 	= preg_match_all('/\.\{.*?\}/',$term_string,$AP_Pattern,PREG_SET_ORDER);
			
			//替換AP Pattern防止被破壞
			if(preg_match('/^\w%/',$term_set_array['attr']) and $AP_Count){
			  for($i=0  ; $i<$AP_Count; $i++){
			   $temp_term = preg_replace('/\.\{.*?\}/','＃SEARCHPATTERN'.$i.'＃',$temp_term,1);
			  }
			}
			
			//進行字串檢查過濾
			$New_Term = $temp_term;
			//$New_Term = preg_replace('/(\[|\]|\(|\)|\{|\}|<|>|\.|\*|\?|\\|\/|=|\!|\$|&|%|#|@|\^|:|\'|")/','_',$New_Term); //過濾奇怪符號
			//$New_Term = preg_replace('/台/','臺',$New_Term);   //轉換通用字
			//$New_Term = preg_replace('/([\x00-\x2F\x3A-\x40\x5B-\x5F\x7B-\x7F])/','\\\\\1',$New_Term);
			
			
			//換回原本之AP Pattern
			if($AP_Count){
			  for($i=0  ; $i<$AP_Count; $i++){
				$New_Term = preg_replace('/＃SEARCHPATTERN'.$i.'＃/',$AP_Pattern[$i][0],$New_Term);
			  }
			}
			
			if( preg_replace('/_/','',$New_Term) != '' ){
			  $checked_term_array[] = $New_Term;  
			}else{
			  continue;
			}
		  }
		  
		  if(count($checked_term_array)){
			$term_set_array['value'] = $checked_term_array;
		  }else{
			unset($user_query_normal_set[$term_set_num]);
		  }
		}
			
	    
		/******----- 檢查新檢索條件 -----*******/
	    if(!count($user_query_normal_set)){
		   throw new Exception('_ARCHIVE_SEARCH_TERM_EMPTY');  	 
		}
		
		
		/******----- 組合檢索條件 -----*******/
	  
		//條件pattern     kw:x|y|z:+ -
		$user_query_final_set = count($parent_query_term_set) ? $parent_query_term_set : array(); 
		  
		if(count($user_query_normal_set)){
		  foreach($user_query_normal_set as $term_set_num => $term_set_data){
			$user_query_final_set[] =  $term_set_data['field'].':'.join('|',$term_set_data['value']).':'.$term_set_data['attr'];
		  }
		}
		  
		//var_dump($user_query_final_set);
		  
		
		/******----- 檢查綜合條件 -----*******/
	    if(!count($user_query_final_set)){
		   throw new Exception('_ARCHIVE_SEARCH_TERM_EMPTY');  	 
		}
		
		$this->UserQuerySet = $user_query_final_set;
		
		/*
		echo "<pre>";
		var_dump($this->UserQuerySet);
		var_dump($this->SearchConf);
		exit(1);
		*/
		
		$result['action'] = true;
		$result['data']['condition'] = $search_sets;
		$result['data']['postquery'] = $this->SearchInfo['term_filter'];
		$result['data']['domconfig'] = $this->SearchInfo['doms_config'];
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	  
	}
	
	
	//TOOL: 處理檢索組合方式
	static private function Set_SQL_Connect_Operator($TermAttr , $FieldSearchType ,$FieldMatchType){
	  
	  $SQL_Operator  = array('link'=>'','left'=>'','right'=>'');  // SQL 串連運算元
	  switch($FieldSearchType){
		case 'exact':
		  switch($TermAttr){
			case '+':  $SQL_Operator['link'] = ' = ' ; break;
			case '-':  $SQL_Operator['link'] = ' != '; break;
			default :  $SQL_Operator['link'] = ' = ' ; break;
		  }
		  break;
		  
		case 'fuzzy':
		  switch($TermAttr){
			case '+':  $SQL_Operator['link'] = ' LIKE ' ; break;
			case '-':  $SQL_Operator['link'] = ' NOT LIKE '; break;
			default :  $SQL_Operator['link'] = ' LIKE ' ; break;
		  }	  
		  switch($FieldMatchType){
			case 'all':
			  $SQL_Operator['left']='';
			  $SQL_Operator['right']='';
			  break;
			case 'any':
              $SQL_Operator['left']='%';
			  $SQL_Operator['right']='%';
		      break;
            case 'left':
			  $SQL_Operator['left']='%';
			  $SQL_Operator['right']='';
              break;					  
			case 'right':
			  $SQL_Operator['left']='';
			  $SQL_Operator['right']='%';
			  break;
			default:
              $SQL_Operator['left']='%';
			  $SQL_Operator['right']='%';
		      break;
	      }
		  break;
	    
		case 'fulltext':
		  switch($TermAttr){
			case '+':  $SQL_Operator['link'] = '' ; break;
			case '-':  $SQL_Operator['link'] = '-'; break;
			default :  $SQL_Operator['link'] = '' ; break;
		  }
		  break;
	  }
	  return $SQL_Operator;
	}
	
	
	//-- Archive Search Condition Process STEP 2 : built query string	
	// [input] : $SearchEngine : array(MySQL,Elasticsearch,Sphinx)
	// [OUTPUT]: $this->SearchInfo
	public function Archive_Regist_Query($SearchEngine=array('MySQL')){
	  
	  $user_query_set = $this->UserQuerySet;
	  $search_ap_flag = 0;
	  $Search_FT_Flag = FALSE;
	  
	  $Query_Set_Sql_Format_Array = array('mysql'=>array());
	  
	  $term_attr_counter = array('-'=>0,'+'=>0,'t%'=>0,'c%'=>0); // 計算
	  
	  
	  $new_query_access_num = 0;  // 註冊後的最新 access_num
	  
	  
	  $result_key = parent::Initial_Result('query');
   	  $result  = &$this->ModelResult[$result_key];

	  try{
        
		if(!count($user_query_set)){
	      throw new Exception('_ARCHIVE_SEARCH_TERM_EMPTY');
	    }  
		
		
		foreach($user_query_set as $Set_Num => $Set_String){
		  
		  $Set_Array=array();
		  
		  if(preg_match('/^([\w\d\_\-]+):(.*):(.*?)$/',$Set_String,$Set_Split)){
            array_shift($Set_Split);
		  }else{
		    $Set_Split = explode(':',$Set_String);
		  } 
		  
		  $query_set['field'] = $Set_Split[0];
		  $query_set['value'] = explode('|',$Set_Split[1]);
		  $query_set['attr']  = $Set_Split[2];
		  
		  // 第一個條件不可為反向，會造成.all 錯誤
		  if($Set_Num === 0 && $query_set['attr'] =='-'){
		    throw new Exception('_ARCHIVE_SEARCH_TOTAL_TERM_INVERSE_ILLEGAL');
		  }
		 
		  /*******----- 以下架構檢索詞組模式 -----*******/
		  
		  // 處理 attr
		  $sql_built_mode	= 'normal';     //定義sql組合的模式   normal / regular 
		  $query_condition_set = array('mysql'=>array(),'sphinx'=>array('field'=>'','value'=>array(),'link'=>'',));
		  
		  if($query_set['field']=='not' || $query_set['field']=='tags'){
		    $search_ap_flag++;
		  }
		  
		  switch($query_set['attr']){
			case '+':
		    case '-':
			  $sql_built_mode	= 'normal';  break;
			case 't%':
			case 'c%':
			  $sql_built_mode	= 'regular'; $search_ap_flag++; break;      // regular 目前由全文檢索處理 / mysql 太慢了
		  }
		  
		  $term_attr_counter[$query_set['attr']]+= count($query_set['value']);
		  
		  
		  
		  // 處理 value
		  switch($sql_built_mode){
		    
			case 'normal':
		      
			  foreach($query_set['value'] as $Term){
			    
				
				//遮蔽條件,不處理此項
				if( $Term && preg_match('/~$/',$Term)){    
				  $term_attr_counter[$query_set['attr']]--;
				  continue;
				}  
				  
				  
				//處理field
				switch($query_set['field']){
				    case '_all':
				     
					  $Term_Condition_Set = array();
					  
					  foreach($this->MetaConfig as $field_code => $field_conf ){
						
						// 不可搜尋則跳過
						if(!$field_conf['SearchAble']) continue; 
						
						//組合檢索條件
						$field_target = $field_code=='_tags' ? "IFNULL(Tags,'')" : $field_code;  
						  
						/*因為 NULL 不可做比較 因必為 否 所以LEFT JOIN 的 Tags 要先配上  IFNULL 函數轉換內容  2013-10-17*/
						$SQL_Operator = self::Set_SQL_Connect_Operator($query_set['attr'] , $field_conf['SearchType'] ,$field_conf['MatchType']);
						$Term_Condition_Set[] = $field_target.$SQL_Operator['link']."'".$SQL_Operator['left'].$Term.$SQL_Operator['right']."'"; 
					  }
					  
					  $temp_condition_string = "(".join(' OR ',$Term_Condition_Set).")";
					  $temp_condition_string = preg_replace('/\s+/',' ',$temp_condition_string);
					  $query_condition_set['mysql'][]   = $temp_condition_string;
					  break;
				     
					case '_nots':  // 筆記
					  $SQL_Operator = self::Set_SQL_Connect_Operator($query_set['attr'] , $this->MetaConfig['_note']['SearchType'] ,$this->MetaConfig['_note']['MatchType']);
					  $query_condition_set['mysql'][] = "Notes".$SQL_Operator['link']."'".$SQL_Operator['left'].$Term.$SQL_Operator['right']."'";
			          break;
				    
					case '_tags':  // 標籤
					  $SQL_Operator = self::Set_SQL_Connect_Operator($query_set['attr'] , $this->MetaConfig['_tags']['SearchType'] ,$this->MetaConfig['_tags']['MatchType']);
					  $query_condition_set['mysql'][] = "IFNULL(Tags,'')".$SQL_Operator['link']."'".$SQL_Operator['left'].$Term.$SQL_Operator['right']."'";
				      break;
					  
				    default:				      
					  $SQL_Operator = self::Set_SQL_Connect_Operator($query_set['attr'] , $this->MetaConfig[$query_set['field']]['SearchType'] ,$this->MetaConfig[$query_set['field']]['MatchType']);
					  $query_condition_set['mysql'][]=$this->MetaConfig[$query_set['field']]['FieldName'].$SQL_Operator['link']."'".$SQL_Operator['left'].$Term.$SQL_Operator['right']."'";
				      break;
				}
			  }
			  
			  
			  break;  // end of normal mode
			  
			  
			case 'regular':
			  switch($query_set['attr']){
				case 't%':
                  $query_condition_set['mysql'][] =  "name like '".$query_set['value'][0]."'"; 
				  break;

                case 'c%':
                  $query_condition_set['mysql'][] =  "name like '".$query_set['value'][0]."'"; 
				  break;				
			  } 
			  
			
			
			  break;  // end of regular mode  
		  }
		  
		  /*******----- 串連所有條件 -----*******/
		  //串連mysql sql
		  $temp_condition_join_string = '';
		  if(count($query_condition_set['mysql'])){
			$temp_condition_join_string='('.join(' OR ',$query_condition_set['mysql']).')';
	        
			//處理條件反向條件
	        $Query_Set_Sql_Format_Array['mysql'][] = ($query_set['attr']=='-') ? preg_replace('/\sOR\s/',' AND ',$temp_condition_join_string) : $temp_condition_join_string ;
		  }
		}
		
		
		if(!intval($term_attr_counter['+']) && !intval($term_attr_counter['t%']) && !intval($term_attr_counter['c%'])  ){
		  $Query_Set_Sql_Format_Array['mysql']=array();
		  throw new Exception('_ARCHIVE_SEARCH_TOTAL_TERM_INVERSE_ILLEGAL');
		}
		
		/*******----- 以下檢查檢索條件串  -----*******/
		
		// 檢查檢索條件
		if(!count($Query_Set_Sql_Format_Array['mysql'])){
		  // 如果沒有檢索條件，則創一永不成立條件，保持SQL不出錯 
		  $Query_Set_Sql_Format_Array['mysql'][] = "system_id=0";
		}
		
		/*******----- 以下加入附加條件  -----*******/
		if($this->SearchConf['MODE_ResultMode'] == '_selected'){
		  //$Query_Set_Sql_Format_Array['mysql'][] 	 = "SelecterId='".$this->USER->UserID."'";
		  //$Query_Set_Sql_Format_Array['mysql'][] 	 = "UseLimit_Meta=1";
		}
		
		/*******----- 以下組合條件  -----*******/
		$final_query_sql = array();
		
		//組合所有條件
        //$ConditionSqlString = "Select * from metadata where ".join(' and ',$QuerySetSqlFormat)." order by ".$SysSet['Order_By'];
        $final_query_sql['head'] = SQL_Archive::SEARCH_SQL_HEADER_MYSQL($this->USER->UserID);
		$final_query_sql['body'] = "WHERE ".join(' AND ',$Query_Set_Sql_Format_Array['mysql'])." ";
		$final_query_sql['foot'] = "ORDER BY ".$this->SearchConf['SET_OrderByTarget']." ".$this->SearchConf['SET_OrderByMethod'].",system_id ASC";
		
		/*******----- 以下註冊檢索條件  -----*******/
		$DB_OBJ		= $this->DBLink->prepare(SQL_Archive::INSERT_SEARCH_TEMP());
		
		$DB_OBJ->bindParam(':UAID'		 , $this->USER->UserID);
        $DB_OBJ->bindValue(':SQL_Mysql'  , join('',$final_query_sql), PDO::PARAM_STR); 
		$DB_OBJ->bindValue(':Query_Set'  , join('⊕',$user_query_set), PDO::PARAM_STR); 
		$DB_OBJ->bindValue(':ACTION'  	 , $this->SearchInfo['action_from'], PDO::PARAM_STR); 
		$DB_OBJ->bindValue(':ACCNUM'  	 , $this->SearchInfo['access_from'] ? $this->SearchInfo['access_from']:0, PDO::PARAM_INT); 
		
		if(!$DB_OBJ->execute()){
		  throw new Exception('_ARCHIVE_SEARCH_REGIST_FAILS');
		}
		
		$this->SearchInfo['access_num']	= $this->DBLink->lastInsertId();
		$this->SearchInfo['sql_mysql']	= join('',$final_query_sql);
		$this->SearchInfo['query_set']	= $user_query_set;
		$this->SearchInfo['search_mode'] = 'mysql';
		$this->SearchInfo['search_ap']	= false ;
		
	    $result['action'] = true;
		$result['data'] = $this->SearchInfo['access_num'];
		
		
		// 儲存狀態設定
		$this->SearchConf['TEMP_Last_QueryAccNum'] = $this->SearchInfo['access_num'];
		$this->SearchConf['TEMP_Last_QuerySet'] = $user_query_set;
		
		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	  
	}
	
	
	//-- Archive Search Condition Process STEP 3 : built index query string	
	// [input] : $this->SearchInfo
	
	public function Archive_Built_ElasticSearch(){
	  
	  $result_key = parent::Initial_Result('index');
   	  $result  = &$this->ModelResult[$result_key];
	  $advance_mode = false;
	  $query_sort_disable = false;
	  
	  
	  try{
        
		if(!$this->SearchInfo['access_num']){
	      throw new Exception('_ARCHIVE_SEARCH_INFO_ERROR');
	    }  
	    
		$query_string = array();
		
		foreach($this->SearchInfo['query_set'] as $set_num => $set_string){
		  
		  $set_array=array();
		  $set_split=array();
		  
		  if(preg_match('/^([\w\d\_\-]+):(.*):(.*?)$/',$set_string,$set_split)){
            array_shift($set_split);
		  }else{
		    $set_split = explode(':',$set_string);
		  } 
		  
		  $query_set=array();
		  $query_set['field'] = $set_split[0];
		  $query_set['value'] = ( $set_split[2]=='+' || $set_split[2]=='-' ) ? explode('|',$set_split[1]) : explode('@',$set_split[1]);
		  $query_set['attr']  = $set_split[2];
		  
		  
		  switch($query_set['field']){
			
			case '_all':
			  
			  $query_set['value'] = array_map(function($term){ return htmlentities($term,ENT_QUOTES, "UTF-8");   },$query_set['value']);
			  
			  if($query_set['attr'] == '+'){
				$query_string[] = '("'.join('" | "',$query_set['value']).'")';   
			  }else{
				$query_string[] = '(-"'.join('" & "-',$query_set['value']).'")';    
			  }
			  break;
            
            case 'serial':
			  $pattern = array('/\(/','/\)/','/(\d)\/(\d)/');
			  $replace = array('&#40;','&#41;','\\1&#47;\\2');
			  $query_set['value'] = array_map(function($field) use($pattern,$replace){ return preg_replace($pattern,$replace,$field); },$query_set['value'] );
			  $query_string[] = $query_set['attr'].$query_set['field'].':(*'.addcslashes( join(' | ',$query_set['value']),'/').'*)';
			  $this->LevelTarget = join('',$query_set['value']);
			  break;
			
			case 'person':
            case 'location':
            case 'keywords':
			case 'from_date':
			case 'to_date':
			  $query_string[] = $query_set['attr'].$query_set['field'].':(*'.join('* OR *',$query_set['value']).'*)';
              break;
			  
		    case 'termpat':
			  
			  $capture_term   = $query_set['value'][0];  // 詞彙工具
			  $target_term    = isset($query_set['value'][1]) ? $query_set['value'][1] : ''; // 目標辭
			  $advance_mode   = preg_replace('/(\{[\d\-\,]\}+)/','.\\1', $capture_term);
              $query_string[] = '("'.preg_replace('/\{[\d\-\,]+\}/','',$capture_term).'")';     
			  
			  break;
			
			case 'clipterm':
			  $capture_term   = $query_set['value'][0];  // 詞彙工具
			  $target_term    = isset($query_set['value'][1]) ? $query_set['value'][1] : ''; // 目標辭
			  $advance_mode = preg_replace('/(\{[\d\-\,]+\})/','.\\1',$capture_term);
			  $clipterms    = preg_split('/(\{[\d\-\,]+\})/',$capture_term);
			  foreach($clipterms as $cterm){
				$query_string[] = '(\\"'.$cterm.'\\")'; 
			  }
			  break;
			
			default:
			  $term_set = array();
			  foreach($query_set['value'] as $qterm){
			    
				$qterm = htmlentities($qterm,ENT_QUOTES, "UTF-8");
				
				switch($this->MetaConfig[$query_set['field']]['SearchType']){
				  case 'exact':  $term_set[] = '"'.$qterm.'"'; break;
				  case 'fuzzy':	
					if($this->MetaConfig[$query_set['field']]['MatchType'] == 'left'){
					  $term_set[] = '*'.$qterm;
					}else if($this->MetaConfig[$query_set['field']]['MatchType'] == 'right'){
					  $term_set[] = $qterm.'*';	
					}else{
					  $term_set[] = '"'.$qterm.'"';	
					}
					break;
				  default: break;
				}
			  }
			  $query_string[] = $query_set['attr'].$query_set['field'].':('.join(' OR ',$term_set).')';
		      break;
		  }
		  
		  //-- zong search config
		  
		  if($query_set['field']=='zong' && in_array('議員傳記',$query_set['value'])){
			$query_sort_disable = true;  
		  }
		  
		  
		  
		}
		
		if(count($this->SearchInfo['date_range'])){
		  $query_string[] = "date_start:[".join(" TO ",$this->SearchInfo['date_range'])."]";
		}
		
		// filter  開啟才能檢索
		$query_string[] = "_flag_open:1";
		
		
		$params =[
			"size" => 20,
			"from" => 0,
			'index' => 'ndap',
			'type' => 'search',
			'body' => [
			  'query'=>[
				 "query_string" => [
					//"query"=> "(\"蔣中正\") AND (\"顧祝\") AND location:(*漢口* *江西*) AND in_store_no:00200000*",
					"query"=> join(" AND ",$query_string),
					//"fields"=> ["_all"],
					//"default_operator"=>"and"
				 ],
			  ],
			  "sort"=>[
			    "collection"=>["order"=>"asc"],
				"identifier"=>["order"=>"asc","missing"=>"_first"]
			  ],
			  "post_filter"=>[
			    "bool" =>[
				  "must"=>[
				   // ["terms"=>['yearnum'=>['1933民國22年']]],
		           // ["terms"=>['person'=>['陳誠']]]
				  ]
				]
			  ],
			  "aggs"=>[
				"pq_zong"=>[
				  "terms"=>[
					"field"=>"zong",
					"size" => "5"	
				  ],
				  "aggs"=>[
				    "ycount"=>[
					  "terms"=>[
					    "field"=>"yearrange",
					    "size" => "100",
						"order"=>[
							"_term" => "asc" 
					    ]
					  ]
					] 
				  ]
				],
				"pq_meeting"=>[
				  "terms"=>[
					"field"=>"meeting_level",
					"size" => "100"	
				  ]
				],
				"pq_category"=>[
				  "terms"=>[
					"field"=>"category_level",
					"size" => "100"	
				  ]
				],
				"pq_yearnum"=>[
				  "terms"=>[
					"field"=>"yearrange",
					"size" => "100",
					"order"=>[
					  "_term" => "asc" 
					]		
				  ]
				],
				"pq_person"=>[
				  "terms"=>[
					"field"=>"list_member",
					"size" => "100"	
				  ]
				],
				"pq_organ"=>[
				  "terms"=>[
					"field"=>"list_organ",
					"size" => "100"	
				  ]
				],
				"pq_subject"=>[
				  "terms"=>[
					"field"=>"list_subject",
					"size" => "100"	
				  ]
				]
				
			  ],
			  
			] 
		];
		
		foreach($this->SearchInfo['term_filter'] as $field=>$terms){
		  $params['body']['post_filter']['bool']['must'][] = ["terms"=>[$field=>$terms]];	
		}
		
		if($query_sort_disable){
		  unset($params['body']['sort']);
		}else if(isset($this->SearchInfo['doms_config']['sortby'])){
		  switch($this->SearchInfo['doms_config']['sortby']){
			case 'date_string-asc':  $params['body']['sort'] = array('date_string'=>array('order'=>'asc'))+$params['body']['sort']; break; 
			case 'date_string-desc':  $params['body']['sort'] = array('date_string'=>array('order'=>'desc'))+$params['body']['sort']; break; 
			case 'identifier-asc' :  $params['body']['sort'] = ["identifier"=>["order"=>"asc","missing"=>"_last"]]; break;  
		    case 'identifier-desc' :  $params['body']['sort'] = ["identifier"=>["order"=>"desc","missing"=>"_last"]]; break;  
		    default: break;
		  }
		}
        
		
		// 夾綴詞功能
		if( $advance_mode ){
		  
		  $advance_search_hash  = md5($advance_mode);
		  $advance_search_cache = _SYSTEM_SEARCH_AP_BUFFER.$advance_search_hash;
		  $terms_capture        = array();
		  $cache_rewrite        = true;
		  
		  if( file_exists($advance_search_cache) ){  //確認是否有查詢快取
			$termcapture_json  = file_get_contents($advance_search_cache);  
		    $termcapture_cache = json_decode($termcapture_json,true);
		    if(  strtotime('now') < strtotime('+7 day',$termcapture_cache['time']) ){
			  $terms_capture = $termcapture_cache['sets'];  	
			  $cache_rewrite = false;
			}
		  }
		  
		  if(!count($terms_capture)){ // 無法從快取取得
			  // 執行詞彙抓取
			  $size  = 10000;
			  $page  = 0;
			  $hits  = 0;
			  $terms = array();
			  $yfilter= array();
			  $appara = $params;
			  $appara['body']['query']['query_string']['fields']=["description","name"];
			  
			  do{
				ini_set('memory_limit', '1000M');
				$appara['size'] = $size;
				$appara['from'] = 0;
				
				$hosts = [
				  '127.0.0.1:9200',         // IP + Port
				];
				$defaultHandler = Elasticsearch\ClientBuilder::defaultHandler();
				$client = Elasticsearch\ClientBuilder::create()
					  ->setHandler($defaultHandler)
					  ->setHosts($hosts)
					  ->setRetries(0)
					  ->build();
				
				$response = $client->search($appara);
				$totalhit = isset($response['hits']['total']) && intval($response['hits']['total']) ? intval($response['hits']['total']) : 0;
				$records  = $totalhit ? $response['hits']['hits']:array();
				
				
				// 如果查詢結果超過 10000 則利用年代分布分片資料
				
				if( $totalhit > 10000 ){
				  $yfilter   = $response['aggregations']['pq_yearnum']['buckets'];	
				  $next = array_shift($yfilter);
				  $appara['body']["post_filter"]["bool"]["must"]=[["terms"=>['yearnum'=>[$next['key']]]]];
				  continue;
				}
				
				if(count($yfilter)){
				  $next = array_shift($yfilter);
				  $appara['body']["post_filter"]["bool"]["must"]=[["terms"=>['yearnum'=>[$next['key']]]]];		
				}
				
				foreach($records as $meta ){   
				  
				  $text_pool = $meta['_source']['name'];
				  $text_pool.= isset($meta['_source']['description']) ? ';'.$meta['_source']['description'] : '';
				  $text_pool = preg_replace('/([\x00-\x2F\x3A-\x40\x5B-\x5F\x7B-\x7F])/','',$text_pool);
				  
				  if(preg_match_all('/'.$advance_mode.'/u',$text_pool,$matchs,PREG_PATTERN_ORDER )){
					foreach( $matchs[0] as $match){
					  
					  //preg_match("/\p{Han}+/u", $utf8_str);  中文字
					  //([\x00-\x2F\x3A-\x40\x5B-\x5F\x7B-\x7F]) 奇怪符號
					  
					  if(!isset($terms_capture[$match])) $terms_capture[$match]=0;
					  $terms_capture[$match]++; 				  
					
					}
				  }
				}
				
			  }while( count($yfilter) );
		  }
		  
		  // 取篩選辭
		  
		  if($target_term){  
			
			$focus_terms = explode('|',$target_term);
			$params['body']['query']['query_string']["query"].= ' AND ("'.join('" | "',$focus_terms).'")';  			
            $this->SearchInfo['query_set'][] = '_all:'.$target_term.':+'; 
			
		  }else if(count($terms_capture)){
			
			arsort($terms_capture);
		    $first_term = current(array_keys($terms_capture)); 
		    
		    if($cache_rewrite){ // 建立快取
			  $terms_buffer = [
			    'time'=>strtotime('now'),
				'pattern'=>$advance_mode,
			    'sets'=>$terms_capture
			  ];
              file_put_contents($advance_search_cache,json_encode($terms_buffer));			  	
			}
		    $params['body']['query']['query_string']["query"].=' AND ("'.$first_term.'")';
			$this->SearchInfo['query_set'][] = '_all:'.$first_term.':+'; 
            		  
		    $target_term = $first_term;
		  }
		  
		  foreach($terms_capture as $key => $num){
			$this->ResultClipAgg[] = ['key'=>$key,'doc_count'=>$num];
		  }
          
		  $result['data']['capture']  = $target_term;
		}
		
		
		$this->SearchInfo['query_string']	= $params;
		
		// 回傳檔案
		$DB_OBJ		= $this->DBLink->prepare(SQL_Archive::UPDATE_SEARCH_TEMP());
		$DB_OBJ->bindValue(':query'	  , json_encode($params,JSON_UNESCAPED_UNICODE));
        $DB_OBJ->bindValue(':accnum'  , $this->SearchInfo['access_num'], PDO::PARAM_INT); 
		$DB_OBJ->execute();
		
		//var_dump( $params );
		//exit(1);
		
		$result['action']  = true;
		$result['data']['query']    = $this->SearchInfo['query_string'];
		
		
		return $result;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	  
	}
	
	//-- Archive Search Condition Process STEP 3 : built index query string	
	// [input] : $Page : 1-20 
	
	public function Archive_Active_Query($Page){
	  
	  $result_key = parent::Initial_Result('active');
   	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		// initial 
		$result['data']['pquery'] =array();
		$result['data']['chart']  = '';
		
		
		
		$Page = trim($Page);
		if(!preg_match('/^\d+\-\d+$/', $Page )) $Page = '1-20';	
		list($p_start,$p_end) = explode('-',$Page);
		
		// 防止網址竄改
		if($p_start > 10000){
		  $p_start =  9901;
          $p_end   = 10000;
		}else if( $p_start >=  $p_end){
		  $p_end = $p_start+20;	
		}else if( $p_end-$p_start > 99){
          $p_end = $p_start+99;
		}
		
		
	    $params = $this->SearchInfo['query_string'];  
		$params['size'] = $p_end - $p_start + 1;
		$params['from'] = $p_start - 1;
		
		$this->PageNow     	  = intval($p_end/$params['size']);   
	    $this->LengthEachPage = $params['size'];
		
		$hosts = [
		  '127.0.0.1:9200',         // IP + Port
	    ];
	    //require _SYSTEM_ROOT_PATH.'mvc/lib/vendor/autoload.php';
		$defaultHandler = Elasticsearch\ClientBuilder::defaultHandler();
		//$singleHandler  = Elasticsearch\ClientBuilder::singleHandler();
		//$multiHandler   = Elasticsearch\ClientBuilder::multiHandler();
	    //$customHandler  = new MyCustomHandler();
		
	    $client = Elasticsearch\ClientBuilder::create()
				  ->setHandler($defaultHandler)
				  ->setHosts($hosts)
				  ->setRetries(0)
				  ->build();
		$response = $client->search($params);
        
		
		//file_put_contents('logs.txt',print_r($response,true));
		
		
		// 頁面參數
		$this->ResultSource  = isset($response['hits']['total']) && intval($response['hits']['total']) ? $response['hits']['hits']:array();
		$this->ResultIndexs  = array_map(function($document){return $document['_id'];},$this->ResultSource);
		$this->ResultCount   = intval($response['hits']['total']);   // 查詢結果數量
	    $this->ResultZongAgg = isset($response['aggregations']['pq_zong'])  ? $response['aggregations']['pq_zong']['buckets'] : array();
		$this->ResultYearAgg = isset($response['aggregations']['pq_yearnum']) ? $response['aggregations']['pq_yearnum']['buckets'] : array();
		$this->ResultNameAgg = isset($response['aggregations']['pq_person'])  ? $response['aggregations']['pq_person']['buckets'] : array();
		$this->ResultOrganAgg = isset($response['aggregations']['pq_organ'])? $response['aggregations']['pq_organ']['buckets'] : array();
		$this->ResultSubjectAgg = isset($response['aggregations']['pq_subject'])? $response['aggregations']['pq_subject']['buckets'] : array();
		$this->ResultMeetAgg = isset($response['aggregations']['pq_meeting'])? $response['aggregations']['pq_meeting']['buckets'] : array();
		$this->ResultCateAgg = isset($response['aggregations']['pq_category'])? $response['aggregations']['pq_category']['buckets'] : array();
		
		
		// 檢測是否須取得階層資料
		$zong_group          = isset($response['aggregations']['pq_zong'])? $response['aggregations']['pq_zong']['buckets'] : array();
		
		
		
		// 建立 chart
		$Chart_Data = array(
		  'category'=>[],  // x軸label
		  'data_total'=>[], // 總計
		  'data_file'=>[], // 檔案
		  'data_meet'=>[], // 公報/議事錄
		  'tick'=> 0 ,  // 計算y軸刻度
		);
		
		$Max_Value  = 0;
		$year_range = array();
		for($i=1940 ; $i<=date('Y') ; $i++  ){
		  $year_range[$i] = 0; 	
		}
		foreach($this->ResultYearAgg as $yearnum){
		  if($yearnum['key']=='none') continue;
		  $year = substr($yearnum['key'],0,4);
		  $year_range[$year] = $yearnum['doc_count'];
		}  
		  
		foreach($year_range as $year => $count){  
		  $Chart_Data['category'][] = $year%10 ? '' : $year;
	      $Chart_Data['data_total'][]     = array('name'=>'西元'.$year.'年','y'=>$count);
		  $Max_Value = $count > $Max_Value ? $count : $Max_Value ;
	    }
		$Chart_Data['tick'] = (substr(strval($Max_Value/5),0,1)+1) * pow(10,(strlen(intval($Max_Value/5))-1));
	    
		
		$zong_year = array_keys($year_range);
		$zong_chart= array_combine($zong_year,array_fill(0, count($zong_year),0));
		
		// 建立資料集hart
		if(is_array($this->ResultZongAgg)&&count($this->ResultZongAgg)){
		  foreach($this->ResultZongAgg as $pq_zong){
			
			if(!isset($pq_zong['ycount']['buckets']) || !count($pq_zong['ycount']['buckets'])){
			  continue;	
			}
			
			if($pq_zong['key']=='檔案'){
			  $zong_year = array_keys($year_range);
		      $zong_chart= array_combine($zong_year,array_fill(0, count($zong_year),0));	
				
			  foreach($pq_zong['ycount']['buckets'] as $pqzy){
				$yindex = intval(substr($pqzy['key'],0,4));
				if(!isset($zong_chart[$yindex])){
				  continue;	
				} 
				$zong_chart[$yindex] = array('name'=>'西元'.$yindex.'年','y'=>$pqzy['doc_count']);
			  }
			  $Chart_Data['data_file'] = array_values($zong_chart);
			  
			  $zong_year = array_keys($year_range);
		      $zong_chart= array_combine($zong_year,array_fill(0, count($zong_year),0));
		
			}else if($pq_zong['key']=='公報' || $pq_zong['key']=='議事錄' || $pq_zong['key']=='議事影音'){
			  foreach($pq_zong['ycount']['buckets'] as $pqzy){
				$yindex = intval(substr($pqzy['key'],0,4));
				if(!isset($zong_chart[$yindex])){
				  continue;	
				}
                if(isset($zong_chart[$yindex]['name'])){
				  $zong_chart[$yindex]['y'] += $pqzy['doc_count'];	
				}else{
				  $zong_chart[$yindex] = array('name'=>'西元'.$yindex.'年','y'=>$pqzy['doc_count']);	
				}
			  }
			  $Chart_Data['data_meet'] = array_values($zong_chart);	
			}
			
		  }	
		}
		
		
		$result['data']['total'] = isset($response['hits']['total']) ? intval($response['hits']['total']):0;
		$result['data']['start'] = $params['from'];
		$result['data']['psize'] = $params['size'];
		
		
		
		
		$result['data']['pquery']['zong']= $this->ResultZongAgg;
		$result['data']['pquery']['yearrange']= $this->ResultYearAgg;
		$result['data']['pquery']['list_member']= $this->ResultNameAgg;
		$result['data']['pquery']['meeting_level']= $this->ResultMeetAgg;
		$result['data']['pquery']['category_level']= $this->ResultCateAgg;
		$result['data']['pquery']['list_subject']= $this->ResultSubjectAgg;
		$result['data']['pquery']['list_organ']= $this->ResultOrganAgg;
		
		if(count($this->ResultClipAgg)){
		  $result['data']['pquery']['termcapture'] = $this->ResultClipAgg; 	
		}
		
		$result['data']['chart'] = json_encode($Chart_Data);
		$result['action'] = true;
		
		/*
		echo "<pre>";
        var_dump($params);
		var_dump($response);
		file_put_contents('logs.txt',print_r($response,true));
		exit(1);
		*/
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Archive Search Condition Process STEP 4 : process result data	
	// [input] : $QueryResult  array('total'=> , 'meta'=> ) 
	// NOTE : 處理檢索結果
	public function Archive_Process_Result($QueryResult = array()){
	  
	  $result_list  = $this->ResultSource;
	  $result_index = $this->ResultIndexs;
	  $search_term  = $this->SearchInfo['query_set'];
	  
	  // 設定變數
	  $metadata    = array();
	  $Data_Result_Array  = array();  // 存放處理完成資料
	  $Mark_Term_Array    = array();  // 存放要比對之檢索條件
	  $meta_display_config= json_decode(_ARCHIVE_META_DISPLAY_CONFIG,true);
	  $result_link_map    = '';
	  
	  $auth_list = array("丁瑞彬"=>1,"任公藩"=>1,"何寶珍"=>1,"何春木"=>1,"何義"=>1,"何茂取"=>1,"余慎"=>1,"余政道"=>1,"余玲雅"=>1,"余陳月瑛"=>1,"侯惠仙"=>1,"傅學鵬"=>1,"傅文政"=>1,"凃麗生"=>1,"劉傳來"=>1,"劉克"=>1,"劉兼善"=>1,"劉守成"=>1,"劉定國"=>1,"劉文雄"=>1,"劉明朝"=>1,"劉榭燻"=>1,"劉濶才"=>1,"劉炳偉"=>1,"劉邦友"=>1,"劉銓忠"=>1,"卜慶葵"=>1,"古燧昌"=>1,"吳一衛"=>1,"吳伯雄"=>1,"吳國棟"=>1,"吳大清"=>1,"吳文妃"=>1,"吳水雲"=>1,"吳泉洝"=>1,"吳瑞泰"=>1,"吳益利"=>1,"吳鴻森"=>1,"呂俊傑"=>1,"呂吉助"=>1,"呂安德"=>1,"呂永凱"=>1,"呂秀惠"=>1,"呂進芳"=>1,"呂錦花"=>1,"周慧瑛"=>1,"周清玉"=>1,"周滄淵"=>1,"周細滿"=>1,"周錫瑋"=>1,"姚冬聲"=>1,"宋艾克"=>1,"官來壽"=>1,"廖朝錩"=>1,"廖枝源"=>1,"廖榮祺"=>1,"廖泉裕"=>1,"廖秉輝"=>1,"廖繼魯"=>1,"張丁誥"=>1,"張俊宏"=>1,"張俊雄"=>1,"張堅華"=>1,"張學舜"=>1,"張富"=>1,"張振生"=>1,"張文獻"=>1,"張明雄"=>1,"張朝權"=>1,"張清芳"=>1,"張溫鷹"=>1,"張濰濱"=>1,"張瑞麒"=>1,"張福興"=>1,"張蔡美"=>1,"張豐緒"=>1,"張貴木"=>1,"張賢東"=>1,"張郭秀霞"=>1,"張錫褀"=>1,"彭德"=>1,"彭添富"=>1,"徐享城"=>1,"徐堅"=>1,"徐慶元"=>1,"徐輝國"=>1,"戴麗華"=>1,"方醫良"=>1,"施松輝"=>1,"施治明"=>1,"施金協"=>1,"施鐘响"=>1,"曹啟鴻"=>1,"曾華德"=>1,"曾蔡美佐"=>1,"朱有福"=>1,"李儒侯"=>1,"李儒將"=>1,"李友三"=>1,"李子駸"=>1,"李存敬"=>1,"李崇禮"=>1,"李文來"=>1,"李文正"=>1,"李明通"=>1,"李炳盛"=>1,"李烏棕"=>1,"李玉泉"=>1,"李秋遠"=>1,"李萬居"=>1,"李詩益"=>1,"李銑"=>1,"李雅景"=>1,"李雅樵"=>1,"林世南"=>1,"林久翔"=>1,"林亮雲"=>1,"林仙保"=>1,"林佾廷"=>1,"林傳旺"=>1,"林再生"=>1,"林南生"=>1,"林國龍"=>1,"林壁輝"=>1,"林宗男"=>1,"林忠信"=>1,"林文雄"=>1,"林日高"=>1,"林明德"=>1,"林明正"=>1,"林春德"=>1,"林樂善"=>1,"林正二"=>1,"林水木"=>1,"林淵熙"=>1,"林清松"=>1,"林源山"=>1,"林漢周"=>1,"林澄增"=>1,"林火順"=>1,"林為寬"=>1,"林為恭"=>1,"林爾昌"=>1,"林牛港"=>1,"林獻堂"=>1,"林王紫燕"=>1,"林瑞昌"=>1,"林益川"=>1,"林福地"=>1,"林秋龍"=>1,"林義雄"=>1,"林羵羊"=>1,"林耿清"=>1,"林聯登"=>1,"林虛中"=>1,"林連宗"=>1,"林進春"=>1,"林錫耀"=>1,"柯明謀"=>1,"柯水源"=>1,"梁道"=>1,"楊仁福"=>1,"楊天賦"=>1,"楊文欣"=>1,"楊泰順"=>1,"楊玉城"=>1,"楊瓊瓔"=>1,"楊秋興"=>1,"楊罄宜"=>1,"楊金寶"=>1,"楊陶"=>1,"歐石秀"=>1,"殷占魁"=>1,"江上清"=>1,"江恩"=>1,"洪周金女"=>1,"洪性榮"=>1,"洪振宗"=>1,"洪文泰"=>1,"洪木村"=>1,"洪火煉"=>1,"洪約白"=>1,"涂延卿"=>1,"游任和"=>1,"游月霞"=>1,"游錫堃"=>1,"湯慶松"=>1,"王世勛"=>1,"王兆釧"=>1,"王吟貴"=>1,"王慶豐"=>1,"王添灯"=>1,"王玲惠"=>1,"王顯明"=>1,"白世維"=>1,"白權"=>1,"白炳輝"=>1,"盧根德"=>1,"盧秀燕"=>1,"盧逸峰"=>1,"祝畫澄"=>1,"程惠卿"=>1,"章博隆"=>1,"童福來"=>1,"簡明景"=>1,"簡欣哲"=>1,"簡盛義"=>1,"簡維章"=>1,"簡金卿"=>1,"簡錦益"=>1,"羅明旭"=>1,"翁文德"=>1,"苗素芳"=>1,"莊北斗"=>1,"莊姬美"=>1,"莊金生"=>1,"華加志"=>1,"華清吉"=>1,"葉國光"=>1,"葉宜津"=>1,"葉黃鵲喜"=>1,"董榮芳"=>1,"董錦樹"=>1,"蔡介雄"=>1,"蔡來福"=>1,"蔡建生"=>1,"蔡文玉"=>1,"蔡江來"=>1,"蔡江淋"=>1,"蔡端仁"=>1,"蔡聰明"=>1,"蔡讚雄"=>1,"蔡陳翠蓮"=>1,"蔣天降"=>1,"蔣淦生"=>1,"蔣渭川"=>1,"蕭錫齡"=>1,"藍榮祥"=>1,"蘇俊雄"=>1,"蘇惟梁"=>1,"蘇文雄"=>1,"蘇治洋"=>1,"蘇洪月嬌"=>1,"蘇貞昌"=>1,"蘇順國"=>1,"許信良"=>1,"許寬茂"=>1,"許新枝"=>1,"許登宮"=>1,"許素葉"=>1,"許記盛"=>1,"謝三升"=>1,"謝修平"=>1,"謝崑山"=>1,"謝明琳"=>1,"謝東春"=>1,"謝水藍"=>1,"謝清雲"=>1,"謝漢儒"=>1,"謝漢津"=>1,"謝章捷"=>1,"謝言信"=>1,"謝許英"=>1,"謝貴"=>1,"謝鈞惠"=>1,"賴志榮"=>1,"賴榮松"=>1,"賴樹旺"=>1,"賴誠吉"=>1,"趙森海"=>1,"趙綉娃"=>1,"趙良燕"=>1,"連錦水"=>1,"邱仕豐"=>1,"邱創良"=>1,"邱泉華"=>1,"邱益三"=>1,"邱茂男"=>1,"邱連輝"=>1,"邱鏡淳"=>1,"郭俊銘"=>1,"郭吳合巧"=>1,"郭國基"=>1,"郭岐"=>1,"郭榮振"=>1,"郭雨新"=>1,"鄭品聰"=>1,"鄭國忠"=>1,"鄭大洽"=>1,"鄭宋柳"=>1,"鄭文鍵"=>1,"鄭李惠"=>1,"鄭貞德"=>1,"鄭逢時"=>1,"鄭金玲"=>1,"金萬里"=>1,"鍾德珍"=>1,"鍾紹和"=>1,"陳世叫"=>1,"陳啟吉"=>1,"陳天錫"=>1,"陳學益"=>1,"陳希哲"=>1,"陳建年"=>1,"陳志彬"=>1,"陳恆隆"=>1,"陳愷"=>1,"陳慶春"=>1,"陳按察"=>1,"陳振宗"=>1,"陳振雄"=>1,"陳文石"=>1,"陳新發"=>1,"陳施蕊"=>1,"陳旺成"=>1,"陳昌瑞"=>1,"陳明文"=>1,"陳景星"=>1,"陳根塗"=>1,"陳榮盛"=>1,"陳歐珀"=>1,"陳洦汾"=>1,"陳清棟"=>1,"陳照郎"=>1,"陳義秋"=>1,"陳興盛"=>1,"陳茂堤"=>1,"陳華宗"=>1,"陳超明"=>1,"陳進祥"=>1,"陳重光"=>1,"陳金德"=>1,"陳錦相"=>1,"陳錫章"=>1,"韓石泉"=>1,"顏欽賢"=>1,"顏清標"=>1,"馬有岳"=>1,"馬榮吉"=>1,"高崇熙"=>1,"高恭"=>1,"高文良"=>1,"高育仁"=>1,"高順賢"=>1,"高龍雄"=>1,"魏東安"=>1,"魏綸洲"=>1,"魏雲杰"=>1,"黃光平"=>1,"黃國展"=>1,"黃國政"=>1,"黃奇正"=>1,"黃朝琴"=>1,"黃木添"=>1,"黃正義"=>1,"黃永欽"=>1,"黃永聰"=>1,"黃玉嬌"=>1,"黃秀孟"=>1,"黃秀森"=>1,"黃純青"=>1,"黃聯登"=>1,"黃聲鏞"=>1,"黃英雄"=>1,"黃金鳳"=>1,"黃鈴雄"=>1,"黃鎮岳"=>1,"黃陳瑟"=>1,"黃高碧桃"=>1,);
	  
	  
	  /*
	  if(is_file($this->Config->ProfileAddress.'//Temp_AuthList.json')){
	    $auth_list = json_decode(file_get_contents($this->Config->ProfileAddress.'//Temp_AuthList.json'),true);
	  }else{
	    
	  }
	  */
	  
	  $result_key = parent::Initial_Result('result');
   	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		if(!is_array($this->ResultIndexs) || !count($this->ResultIndexs)){
		  throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	
		}
		
		// 取得原始資料
		$DB_OBJ		= $this->DBLink->prepare(SQL_Archive::SEARCH_SQL_GET_RESULT_METAS($this->ResultIndexs));
		if( !$DB_OBJ->execute() || !$datas = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC)){
		  throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE'); 	
		}
		
		foreach($datas as $meta){
		  $metadata[$meta['system_id']] = $meta;
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
		
		
		$filter_set = $this->SearchInfo['term_filter'];
		if(count($filter_set)){
		  foreach($filter_set  as $field => $terms){
			foreach($terms as $t){
			  if(!isset($Mark_Term_Array[$t])){
				$Mark_Term_Array[$t] =  $field; 
			  }
			}  
		  }	
		}
		
	    // 處理檢索資料
	    $i=0;
		
		
		foreach($result_list as $i=>$search_result){
			
			$search_meta = $search_result['_source'];
			$Data_Result_Array[$i] = array();
			
			// 設定連線參數
			$Data_Result_Array[$i]['@SystemLink']['value'] = $search_result['_id'];
			$Data_Result_Array[$i]['@SystemLink']['field'] = md5($search_result['_id'].'œ'.microtime(true));  //加密
			$Data_Result_Array[$i]['@SystemLink']['match'] = false;
			
			// 檢索結果連結轉換表
			$result_link_map.='#'.$Data_Result_Array[$i]['@SystemLink']['field'].'<'.$Data_Result_Array[$i]['@SystemLink']['value'].'>';
			
			
			// 設定類型
			$Data_Result_Array[$i]['@Type']['field'] = '資料類型';
			$Data_Result_Array[$i]['@Type']['value'] = $metadata[$search_result['_id']]['data_type'];
			$Data_Result_Array[$i]['@Type']['match'] = false;
			
			
			// 取得關聯資料
			if($metadata[$search_result['_id']]['data_type'] == 'archive'){
				$meta_refer = json_decode($metadata[$search_result['_id']]['refer_json'],true);
				if($meta_refer && count($meta_refer)){
				  foreach($meta_refer as $rsysid => $rsyscontent){
					$link_key = md5($rsysid.'œ'.microtime(true));
					$result_link_map.='#'.$link_key.'<'.$rsysid.'>';    
					$meta_refer[$rsysid]['linkkey'] = $link_key; 
				  }
				  
				  $Data_Result_Array[$i]['@Refer']['field']	= '關聯資料';	
				  $Data_Result_Array[$i]['@Refer']['value']	= $meta_refer;	
				  $Data_Result_Array[$i]['@Refer']['match']	= false;
				   			   
				}
			
			}else if($metadata[$search_result['_id']]['data_type'] == 'biography'){
			  
			  $meta_refer = json_decode($metadata[$search_result['_id']]['source_json'],true); 	
			  
			  $Data_Result_Array[$i]['@Offer']['field']	= '當選屆次';	
			  $Data_Result_Array[$i]['@Offer']['value']	= $meta_refer['mbr_offer'];	
			  $Data_Result_Array[$i]['@Offer']['match']	= false;
			  
			  $Data_Result_Array[$i]['@Source']['field']	= '資料來源';	
			  $Data_Result_Array[$i]['@Source']['value']	= $meta_refer['_sourcefrom'];	
			  $Data_Result_Array[$i]['@Source']['match']	= false;
			  
			  $meta_dobj = json_decode($metadata[$search_result['_id']]['dobj_json'],true);
			  
			  $Data_Result_Array[$i]['@Portrait']['field']	= isset($meta_dobj['portrait']['name']) ? $meta_dobj['portrait']['name'] : '議員頭像';	
			  $Data_Result_Array[$i]['@Portrait']['value']	= isset($meta_dobj['portrait']['source']) ? $meta_dobj['portrait']['source'] : 'theme/image/nopicture.png';	
			  $Data_Result_Array[$i]['@Portrait']['match']	= false;
			  
			}else if($metadata[$search_result['_id']]['data_type'] == 'photo'){
			  
			  $meta_dobj = json_decode($metadata[$search_result['_id']]['dobj_json'],true); 	
			  
			  $Data_Result_Array[$i]['@Thumb']['field']	= '照片縮圖';	
			  $Data_Result_Array[$i]['@Thumb']['value']	= $meta_dobj['dopath'].'thumb/'.$metadata[$search_result['_id']]['collection'].'/'.array_shift($meta_dobj['position']);	
			  $Data_Result_Array[$i]['@Thumb']['match']	= false;
			  
			}
			
			
			// 設定模式
			$Data_Result_Array[$i]['@Secret']['field'] = '密等';
			$Data_Result_Array[$i]['@Secret']['value'] = $metadata[$search_result['_id']]['_lockmode']; 
			$Data_Result_Array[$i]['@Secret']['apply'] = $metadata[$search_result['_id']]['_lockmode'] == '普通' ? '普通':$metadata[$search_result['_id']]['_lockmode'];
			$Data_Result_Array[$i]['@Secret']['match'] = false;
			
			$source_json = $metadata[$search_result['_id']]['source_json'];  
		    $source_meta = json_decode($source_json,true);
			if( $source_meta && isset($source_meta['decryption'] ) && $source_meta['decryption']  ){
			  $Data_Result_Array[$i]['@Secret']['apply'] .= $source_meta['decryption'] ? ' / '.$source_meta['decryption'] : '';				  
			}
			
			$Data_Result_Array[$i]['@Auditint']['field'] = '隱私問題';
			$Data_Result_Array[$i]['@Auditint']['value'] = $metadata[$search_result['_id']]['_auditint'];
			$Data_Result_Array[$i]['@Auditint']['match'] = false;
			if($metadata[$search_result['_id']]['_checked']){
			  $Data_Result_Array[$i]['@Auditint']['apply'] = $metadata[$search_result['_id']]['_auditint'] ? '隱私問題' : '無隱私';	
			}else{
			  $Data_Result_Array[$i]['@Auditint']['apply'] = "尚未查核";		
			}
			
			
			$Data_Result_Array[$i]['@Digited']['field'] = '數位化';
			$Data_Result_Array[$i]['@Digited']['value'] = $metadata[$search_result['_id']]['_digited'];
			$Data_Result_Array[$i]['@Digited']['apply'] = $metadata[$search_result['_id']]['_digited'] ? '已數位化' : '尚未數位化';
			$Data_Result_Array[$i]['@Digited']['match'] = false;
			
			$Data_Result_Array[$i]['@ViewMode']['field'] = '閱覽方式';
			$Data_Result_Array[$i]['@ViewMode']['value'] = '';  // action mode
			$Data_Result_Array[$i]['@ViewMode']['apply'] = '';  // action name
			$Data_Result_Array[$i]['@ViewMode']['match'] = false;
			
			$ditital_type = ''; // 數位檔案型態
			switch($metadata[$search_result['_id']]['zong']){
			  case '檔案': case '公報':  case '議事錄': $ditital_type = '紙本掃描'; break;
			  case '議事影音': $ditital_type = '多媒體影音'; break;
			  case '活動照片': $ditital_type = '數位照片'; break;
			  case '議員傳記': $ditital_type = '傳記全文'; break;
			  default: $ditital_type = '數位檔案'; break; 
			}
			
			
			
			// 設定開放模式:未數位化則不開
			if(!$metadata[$search_result['_id']]['_digited']){
			  // 未數位化皆不開放	
			  $Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
			  $Data_Result_Array[$i]['@ViewMode']['value'] ='denial'; 
			  		  
			}else{
			  
              switch($metadata[$search_result['_id']]['_view']){
				case '開放':
                  $Data_Result_Array[$i]['@ViewMode']['apply'] ='線上閱覽';
			      $Data_Result_Array[$i]['@ViewMode']['value'] ='online'; 				
 				  break;
				  
                case '限閱':
				  if(isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['ACCOUNT_TYPE']) && $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['ACCOUNT_TYPE']!='GUEST'){
					$Data_Result_Array[$i]['@ViewMode']['apply'] ='帳號線上閱覽';
			        $Data_Result_Array[$i]['@ViewMode']['value'] ='online'; 				
				  }else{
					$Data_Result_Array[$i]['@ViewMode']['apply'] ='僅提供申請帳號閱覽';
			        $Data_Result_Array[$i]['@ViewMode']['value'] ='denial'; 	  
				  }
				  break;
				  
				case '會內':
                  
				  if(isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['ACCOUNT_TYPE']) && $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['ACCOUNT_TYPE']!='GUEST'){
					
					// 檢查IP狀態
					$user_ip = filter_var($this->USER->UserIP , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
					if( $this->USER->UserIP!='0.0.0.0' && strlen($user_ip)  ){
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='僅提供申請帳號於會內閱覽';
			          $Data_Result_Array[$i]['@ViewMode']['value'] ='denial';
					}else{
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='帳號線上閱覽';
			          $Data_Result_Array[$i]['@ViewMode']['value'] ='online'; 				
					}
				  
				  }else{
					$Data_Result_Array[$i]['@ViewMode']['apply'] ='僅提供申請帳號於會內閱覽';
			        $Data_Result_Array[$i]['@ViewMode']['value'] ='denial'; 	  
				  }
				  
				  break;
				
                case '不開放':default:
                  $Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
			      $Data_Result_Array[$i]['@ViewMode']['value'] ='denial'; 				
 				  break;
			  }

			  
				
			}
			
			
			
			/* //開放設定參考
			if(!$metadata[$search_result['_id']]['_auditint'] && $metadata[$search_result['_id']]['_lockmode']=='普通'){
				
				switch($metadata[$search_result['_id']]['_view']){
				  case '開放': 
					if($metadata[$search_result['_id']]['_digited'] && $metadata[$search_result['_id']]['_checked']){
					  $Data_Result_Array[$i]['@ViewMode']['apply'] =$ditital_type.'／線上閱覽';	
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='online'; 
					}else{
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='申請閱覽 (尚未檢視)';	
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='apply'; 
					}
					break;
					
				  case '未數位化':	
				  case '不開放':
				  case '暫不開放':
					
					if( $metadata[$search_result['_id']]['_checked'] ){ // 已查核
					  
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='denial'; 
					
					  
					}else if( $metadata[$search_result['_id']]['data_type']=='element' && !$metadata[$search_result['_id']]['_digited'] ){  //未數位化 (ex:116 閻錫山 142 151 153 154 ) //$metadata[$search_result['_id']]['zong']=='116' && 
					  
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='尚未數位化，請以卷調閱';	
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='denial';	
					  
					}else{
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='申請閱覽 (尚未檢視)';	
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='apply';
					}
					
					break;
				  
				  case '原件閱覽':
				    if( $metadata[$search_result['_id']]['_checked'] ){ // 原件已查核
				     
					  // 查詢准駁設定
					  $DB_CHECK	= $this->DBLink->prepare(SQL_Archive::SEARCH_SQL_GET_APPLY_CHECKED(''));
					  $DB_CHECK->bindValue(':in_store_no',$metadata[$search_result['_id']]['collection']);
					  $DB_CHECK->execute();
					  $check_last = $DB_CHECK->fetch(PDO::FETCH_ASSOC);
					  
					  if($check_last && $check_last['check_info']=='暫緩提供'){
						
						$Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
					    $Data_Result_Array[$i]['@ViewMode']['value'] ='denial'; 
					  
					  }else{
						
						$Data_Result_Array[$i]['@ViewMode']['apply'] ='原件／霧峰省諮議會';
					    $Data_Result_Array[$i]['@ViewMode']['value'] ='reserve';
					
					    // 查詢預約時間
					    $DB_BOOK	= $this->DBLink->prepare(SQL_Archive::SEARCH_SQL_GET_APPLY_BOOKING());
					    $DB_BOOK->bindValue(':in_store_no',$metadata[$search_result['_id']]['collection']);
					    $DB_BOOK->bindValue(':uid',$this->USER->UserNO);
					    $DB_BOOK->execute();
					    while($check_last = $DB_BOOK->fetch(PDO::FETCH_ASSOC)){
						  if( !$check_last['check_range'] || !strtotime($check_last['check_range'])  || strtotime('now') > strtotime($check_last['check_range'].' 15:00:00') ){
						    continue;
						  }
						  $Data_Result_Array[$i]['@ViewMode']['reason'] = '預約到館閱覽時間：'.$check_last['check_range'];	
						  break;
					    }
					  
					  }
					  
					}else{
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='申請閱覽 (尚未檢視)';	
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='apply';	
					}
				    
				   break;
					
				  case '館內閱覽':
				  case '限閱':
					if($metadata[$search_result['_id']]['_checked']){
					  if($metadata[$search_result['_id']]['_digited']){
						
						$Data_Result_Array[$i]['@ViewMode']['apply'] =$ditital_type.'／霧峰省諮議會';
					    $Data_Result_Array[$i]['@ViewMode']['value'] ='online';				  
					  
					  }else{
						
						$Data_Result_Array[$i]['@ViewMode']['apply'] ='原件／新店閱覽室';
					    $Data_Result_Array[$i]['@ViewMode']['value'] ='reserve';  
						
						// 查詢預約時間
					    $DB_BOOK	= $this->DBLink->prepare(SQL_Archive::SEARCH_SQL_GET_APPLY_BOOKING());
					    $DB_BOOK->bindValue(':in_store_no',$metadata[$search_result['_id']]['collection']);
					    $DB_BOOK->bindValue(':uid',$this->USER->UserNO);
					    $DB_BOOK->execute();
					    while($check_last = $DB_BOOK->fetch(PDO::FETCH_ASSOC)){
						  if( !$check_last['check_range'] || !strtotime($check_last['check_range'])  || strtotime('now') > strtotime($check_last['check_range'].' 15:00:00') ){
						    continue;
						  }
						  $Data_Result_Array[$i]['@ViewMode']['reason'] = '預約到館閱覽時間：'.$check_last['check_range'];	
						  break;
					    }
					  
					  }
					}else{
					  $Data_Result_Array[$i]['@ViewMode']['apply'] ='申請閱覽 (尚未檢視)';
					  $Data_Result_Array[$i]['@ViewMode']['value'] ='apply';   				  
					}
					break;
					
				  case '處理中':
				    $Data_Result_Array[$i]['@ViewMode']['apply'] ='處理中';
					$Data_Result_Array[$i]['@ViewMode']['value'] ='denial';  
					break;
				  
				  default:
					$Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
					$Data_Result_Array[$i]['@ViewMode']['value'] ='denial';   	
					break;				
				}
			
			}else{
			  
			  //密等資料狀態設定
			  if( $metadata[$search_result['_id']]['_checked']  ){ 
			    
				// 原件已查核且已解密
                switch($metadata[$search_result['_id']]['_view']){
				  case '開放': 
                    $Data_Result_Array[$i]['@ViewMode']['apply'] =$ditital_type.'／線上閱覽';	
					$Data_Result_Array[$i]['@ViewMode']['value'] ='online'; 
					break;
					
				  case '限閱':
                    $Data_Result_Array[$i]['@ViewMode']['apply'] =$ditital_type.'／霧峰省諮議會';
					$Data_Result_Array[$i]['@ViewMode']['value'] ='online';
					break;
				   
                  default:
				    $Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
                    $Data_Result_Array[$i]['@ViewMode']['value'] ='denial';  	
				    break;
			    }
			  }else{
			    $Data_Result_Array[$i]['@ViewMode']['apply'] ='暫不開放';
                $Data_Result_Array[$i]['@ViewMode']['value'] ='denial';
			  }		  
			}
			
			// 若資料已分件，則不開放全卷調閱
			if(!$metadata[$search_result['_id']]['applyindex'] && $Data_Result_Array[$i]['@ViewMode']['apply']=='申請閱覽 (尚未檢視)'){
			  $Data_Result_Array[$i]['@ViewMode']['reason'] ='已分件，請以件調閱'; 	
			  $Data_Result_Array[$i]['@ViewMode']['apply']  ='不提供申請';
              $Data_Result_Array[$i]['@ViewMode']['value'] ='denial';  				  
			}
			
			// 若為非總統文物類，則設定為僅供借展，不提供申請閱覽
			if( substr($metadata[$search_result['_id']]['collection'],-1,1)=='C' ){
			  $Data_Result_Array[$i]['@ViewMode']['reason'] =''; 	
			  $Data_Result_Array[$i]['@ViewMode']['apply']  ='僅供借展，不提供申請閱覽';
              $Data_Result_Array[$i]['@ViewMode']['value'] ='denial';  				  
			}
			
			
			
			
			
			
			
			
			// 影像存取通過函數
			if(isset($this->USER->PermissionClient['doaccess'])){
			  $rules = $this->USER->PermissionClient['doaccess'];
			  foreach($rules as $rule){
				
				list($field,$condition) = explode(' = ',$rule);
				if(!isset($Data_Result_Array[$i]['@ViewMode'][$field])){
			      continue;
				}
				
				$check_condition = str_replace("'","",$condition);
				$check_list      = explode('&',$check_condition);
				$check_main      = array_shift($check_list);
				
				// 主要檢核條件
				if($Data_Result_Array[$i]['@ViewMode'][$field]!=$check_main ){
				  continue;  	
				}
				
				// 附加檢核條件
				if(count($check_list)){
				  $check_point = count($check_list);     // 需檢核通過數量
				  foreach($check_list as $check_rule){
                   	list($sf,$sc) = explode('=',$check_rule);
					if(isset($metadata[$search_result['_id']][$sf]) && $metadata[$search_result['_id']][$sf]==$sc ){
					  $check_point--; 	
					}
				  }
				  if($check_point){  // 副檢核不通過
					continue;  
				  }
				
				  // REFER : 附加檢核條件主要對應以下兩條控管
				  //$metadata[$search_result['_id']]['_view']=='不開放'
				  //$metadata[$search_result['_id']]['_checked'] == 0
				}
				
				// 符合特殊條件開放後
				if($Data_Result_Array[$i]['@ViewMode']['value']!='online'){
				  $Data_Result_Array[$i]['@ViewMode']['value'] = 'online';	
				  $Data_Result_Array[$i]['@ViewMode']['apply'] = '閱覽權限已開通 / 原狀態:'.$Data_Result_Array[$i]['@ViewMode']['apply'];		
				}
			  }
			}
			*/
			
			
			
			
			
			
			
			
			
			/* 還原 
			if(preg_match('/#([\w\d_]{32})<'.$search_result[_SYSTEM_SEARCH_DATA_LINK_FIELD].'>/',$_SESSION['Data_Link_Map'],$map_match)){
			  $Data_Result_Array[$i]['@SystemLink']['field'] = $map_match[1];
			}
			*/
			
			$mark_person_array   = _SYSTEM_SEARCH_DATA_PERSON_MARK && isset($search_meta[_SYSTEM_SEARCH_MARK_PERSON_FIELD])  ? $search_meta[_SYSTEM_SEARCH_MARK_PERSON_FIELD] :array();
			$mark_location_array = _SYSTEM_SEARCH_DATA_LOCATION_MARK && isset($search_meta[_SYSTEM_SEARCH_DATA_LOCATION_MARK]) ? $search_meta[_SYSTEM_SEARCH_MARK_LOCATION_FIELD] :array();
			
			
			foreach($search_meta as $meta_field => $meta_value){
			  
			  $Meta_Value   = is_array($meta_value) ?join('，',$meta_value):$meta_value;
			  $Option_Value = $Meta_Value;
			  $Option_Access= isset($meta_display_config[$meta_field]) && $meta_display_config[$meta_field]['Access'] ? true : false;     
			  $Option_Check = isset($meta_display_config[$meta_field]) ? $meta_display_config[$meta_field]['Access'] : false;
			  
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
						if( $Term && preg_match('/'.$Term.'/u',$Option_Value)){
						  $person_tag = isset($auth_list[$Term]) ? 'persona':'person';
						}else{
						  $person_tag = 'person';
						}
						//$Option_Value = $Term ? mb_ereg_replace('('.$Term.')',"<".$person_tag.">\\1</".$person_tag.">",$Option_Value) : $Option_Value;  // 20170104 mb 會造成memory 問題導致apache重啟
						$Option_Value = $Term ? preg_replace('/('.$Term.')/u',"<".$person_tag.">\\1</".$person_tag.">",$Option_Value) : $Option_Value;
					  }
					}
				
				    $name_mark = array('{#','#}');
					$Option_Value = str_replace($name_mark,'',$Option_Value);
	
										
					// 標記 地名
					if(count($mark_location_array) && $meta_display_config[$meta_field]['Mark-L'] ){  
					  foreach($mark_location_array as $Term){
						$Term = quotemeta($Term);
						$Option_Value = $Term ? preg_replace('/('.$Term.')/u',"<location>\\1</location>",$Option_Value) : $Option_Value; 
					  }
					}
					
					
					// 標記檢索條件
					if(count($Mark_Term_Array) && $meta_display_config[$meta_field]['Mark-K'] ){  
					  foreach($Mark_Term_Array as $Term => $Term_Field_Code){
						
						$Term = htmlentities($Term,ENT_QUOTES, "UTF-8");
						
						if($Term_Field_Code=='_all' || $Term_Field_Code==$meta_field ){
						  $match_array = array();
						  if(preg_match('/\{.*?\}/',$Term)){ // ****!!!進階搜尋現在沒用到，之後若用到要修改mb_ereg 防止記憶體問題
							
							//term tag 會干擾贅詞查詢標註
							//因此須先將 term tag 移除後再進行 search term mark  再將term tag 導回
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
							$Option_Value = preg_replace("@(".$Term.")@u","<search>\\1</search>",$Option_Value);
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
					
					$Data_Result_Array[$i][$meta_field]['field'] = isset($this->MetaConfig[$meta_field]) ? $this->MetaConfig[$meta_field]['FieldName'] : $meta_field;
					$Data_Result_Array[$i][$meta_field]['apply'] = $Option_Value;
					$Data_Result_Array[$i][$meta_field]['value'] = $Meta_Value;
					$Data_Result_Array[$i][$meta_field]['print'] = true ;
					$Data_Result_Array[$i][$meta_field]['match'] = preg_match('/<search>/',$Option_Value) && $meta_display_config[$meta_field]['Display']==='attach' ? true : false ;
					
					
					break;
				  
				  case  false === $Option_Check : break;
				  case  NULL  === $Option_Check : break;
				  default:  break;
				}
			  }
			}  
			$i++;
		}
		
	    
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  $result['session']['Data_Link_Map'] = $result_link_map;
	  $result['data']   = $Data_Result_Array; 
	  $result['action'] = true;
	 
	  return $result;
	}
	
	
	
	//-- Export User Select Meta
	// [input] : ExportType  = page/result
	// [input] : UserSelectList
	public function Archive_Export_User_Select_Meta($ExportType,$DataList){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $export_types = array('page','result'); 
	  
	  try{
		
		if(!in_array(strtolower($ExportType),$export_types)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	 
		}
		
		$export_type = strtolower($ExportType);
		$export_list = array();
		
		if($export_type=='page'){  // 匯出整頁
		  $data_array = json_decode(base64_decode(rawurldecode($DataList)),true);
		
		  if(!is_array($data_array) || !count($data_array)){
		    throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');   
		  }
		
		  // 查詢資料庫
		  $DB_OBJ = $this->DBLink->prepare(SQL_Archive::GET_USER_EXPORT_META($data_array));
		  if(!$DB_OBJ->execute()){
		    throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	
		  }
		
		  $counter=1;
		  while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		    $record = array();
		    $meta = json_decode($tmp['search_json'],true);
			
			
			
		    $record['no']			= $counter;
		    $record['in_store_no']  = $meta['in_store_no'];
		    $record['name'] 		= $meta['name'];
		    $record['series'] 	    = isset($meta['series']) ? $meta['series'] : $meta['zong_name'];
		    $record['description']  = isset($meta['description']) ? $meta['description'] :'';
		    $record['from_date'] 	= isset($meta['from_date']) ? $meta['from_date'] :'';
		    $record['to_date']      = isset($meta['to_date']) ? $meta['to_date'] :'';
		    $record['store_no'] 	= isset($meta['store_no']) ? $meta['store_no'] :'';
		    $counter++;
		    //$export_list[] = $record;
			$export_list[] = array_map(function($field){ return html_entity_decode($field,ENT_COMPAT,'UTF-8'); },$record);
			
		  }
		
		}else{ // 匯出結果 1 - 10000
          $acc_num = base64_decode(rawurldecode($DataList));
          		  
          // get access query
		  $DB_GET = $this->DBLink->prepare(SQL_Archive::GET_SEARCH_TEMP_RECORD());
		  $DB_GET->bindValue(':ACCNUM',$acc_num);
		  $DB_GET->bindValue(':UAID',$this->USER->UserID);
		  if(!$DB_GET->execute()){
		    throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	
		  }
		  
		  if(!$record = $DB_GET->fetch(PDO::FETCH_ASSOC)){
			throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  
		  }
		  
		  $params = json_decode($record['Sql_2'],true);  
		  $params['size'] = 10000;
		  $params['from'] = 0;
		
		  $hosts = [
		    '127.0.0.1:9200',         // IP + Port
	      ];
	 
		  $defaultHandler = Elasticsearch\ClientBuilder::defaultHandler();
	      $client = Elasticsearch\ClientBuilder::create()
				  ->setHandler($defaultHandler)
				  ->setHosts($hosts)
				  ->setRetries(0)
				  ->build();
		
		  $response = $client->search($params);
        
		  // 頁面參數
		  $results  = isset($response['hits']['total']) && intval($response['hits']['total']) ? $response['hits']['hits']:array();
		  $counter  = 1;
	      foreach($results as $index_result ){
			$meta = $index_result['_source'];  
			$record = array();
		    $record['no']			= $counter;
		    $record['in_store_no']  = $meta['in_store_no'];
		    $record['name'] 		= $meta['name'];
		    $record['series'] 	    = isset($meta['series']) ? $meta['series'] : $meta['zong_name'];
		    $record['description']  = isset($meta['description']) ? $meta['description'] :'';
		    $record['from_date'] 	= isset($meta['from_date']) ? $meta['from_date'] :'';
		    $record['to_date']      = isset($meta['to_date']) ? $meta['to_date'] :'';
		    $record['store_no'] 	= isset($meta['store_no']) ? $meta['store_no'] :'';
		    $counter++;
		    $export_list[] = $record; 
		  }
		}
		
		if(isset($record) && count($record)){
		  $field_map = $this->MetaConfig;
          $fields = array_keys($record);  
		  $field_name = array_map(function($f) use($field_map){ return isset($field_map[$f])? $field_map[$f]['FieldName']:$f; },$fields);
		  array_unshift($export_list, $field_name);
		}
		
		$result['action'] = true;		
		$result['data']['record']  = $export_list;
		$result['data']['fname']   = _SYSTEM_NAME_SHORT.'_export_'.date('YmdHis');
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	
	//-- Get User Apply License List
	// [input] : none
	public function Archive_Get_Apply_List(){
	  
	  $result_key = parent::Initial_Result('apply');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		
		$limit_counter_collect = $this->UserApplyCollectLimit;
		$limit_counter_images  = $this->UserApplyDigitalLimit;
		
		// 查詢資料庫
		$apply_list = array();
		$apply_collect = array();
		$apply_counter = 0;
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_NOW_APPLY());
		$DB_OBJ->execute(array('uid'=>$this->USER->UserNO));
		
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  
		  //:未完成的都要算進等待序號
		  if($tmp['_state'] < 3){
			$apply_counter++;   
		  }
		  
		  //:屬於使用者的要加入清單
		  if($tmp['uid'] == $this->USER->UserNO){
			$record = array(); 
		    $record['code'] = $tmp['apply_code'];
		    $record['date'] = $tmp['apply_date'];
		    switch($tmp['_state']){
			  case 0: $record['state']='新申請'; break;
			  case 1: $record['state']='已受理 (等待序號 '.$apply_counter.' )'; break;
        	  case 2: $record['state']='處理中 (等待序號 '.$apply_counter.' )'; break;
			  case 3: $record['state']='處理中 (部分資料已完成)'; break;
              case 4: 
              case 5: $record['state']='全部完成 (請詳閱准駁清單)'; break;
              default: $record['state']='請洽國史館';break;
		    }	
		    array_unshift($apply_list, $record);
			
			$DB_COUNT = $this->DBLink->prepare(SQL_Archive::APPLY_DATA_LIST());
			$DB_COUNT->execute(array('apply_code'=>$record['code'] ));
			
			while($apply = $DB_COUNT->fetch(PDO::FETCH_ASSOC)){
			  if( $apply['check_state']!='_CHECKED' && $apply['check_state']!='_BOOKING' ){
				if($apply['page_count']!=0){
				  $limit_counter_images-=$apply['page_count'];  				  
				}else{
				  if(!isset($apply_collect[$apply['in_store_no']])) $apply_collect[$apply['in_store_no']]=0;
				  $apply_collect[$apply['in_store_no']]++;
				}
			  }
			}
		  }
		}
		
		$limit_counter_collect-=count($apply_collect);
		
		
		$result['action'] = true;		
		$result['data']['lists']   = $apply_list;
		$result['data']['limit']['collec']  = $limit_counter_collect >=0 ? $limit_counter_collect : 0;
		$result['data']['limit']['images']  = $limit_counter_images  >=0 ? $limit_counter_images : 0;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	
	
	//-- Get Apply Queue
	// [input] : none
	public function Archive_Get_Apply_Queue(){
	  
	  $result_key = parent::Initial_Result('queue');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		if(!isset($this->SearchConf['TEMP_Apply_Queue'])){
		  $this->SearchConf['TEMP_Apply_Queue'] = array();	
		}
		
		// 查詢資料庫
		$apply_list = array();
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_APPLY_META());
		foreach( $this->SearchConf['TEMP_Apply_Queue'] as $id=>$data){
          $DB_OBJ->bindValue(':applyindex'	, $id );
          $DB_OBJ->execute();
          
		  if(!$meta = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
			continue;  
		  }
		  
		  $apply_list[] = array(
		    'apply'=> $meta['applyindex'],
			'type' => $meta['data_type'],
			'meta' => json_decode($meta['search_json'],true),
            'page' => $meta['page_count'],
			'digi' => ($meta['_checked']==1 && ($meta['_view'] == '原件閱覽' || $meta['_digited']==0) ? 0:1 )
		  );
		}
		
		$result['action'] = true;		
		$result['data']   = $apply_list;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	//-- Insert Data To Apply List
	// [input] : $DataList =  accnum:urlencode(json_pass())  = array(id,id,id)
	// [input] : $Mode =  add / del
	
	public function User_Apply_Modify($DataList,$Mode='add'){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		// 檢查申請列表
		if(!intval($this->USER->UserNO)){
		  throw new Exception('申請閱覽必須先以帳號密碼登入');
		}
		
		$apply_list  = json_decode(rawurldecode($DataList),true);
		
		// 檢查申請列表
		if(!count($apply_list)){
		  throw new Exception('_APPLY_ELEMENT_IS_NULL');  
		}
		
		if(!isset($this->SearchConf['TEMP_Apply_Queue'])){
		  $this->SearchConf['TEMP_Apply_Queue'] = array();	
		}
		
		$add_counter = 0;
		$apply_queue = array_keys($this->SearchConf['TEMP_Apply_Queue']);
		
		if($Mode=='add'){
		  foreach($apply_list as $record_id){
		    if(!in_array($record_id,$apply_queue)){
			  $this->SearchConf['TEMP_Apply_Queue'][$record_id] = array('time'=>date('Y-m-d H:i:s'));  
		      $add_counter++;    
		    }
		  }	
		}else{
		  foreach($apply_list as $record_id){
		    if(in_array($record_id,$apply_queue)){
			  unset($this->SearchConf['TEMP_Apply_Queue'][$record_id]);  
		      $add_counter++;    
		    }
		  }	
		}
		
		ksort($this->SearchConf['TEMP_Apply_Queue']);
		
		$result['action'] = true;		
		$result['data']['newadd'] = $add_counter;
		$result['data']['total']  = count($this->SearchConf['TEMP_Apply_Queue']);
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}	
	
	
	
	
	//-- Submit User Apply
	//[input] : $DataList =  urlencode(json_pass())  = applyto=>array(),discard=>array(id,id,id)
	
	public function User_Apply_Submit($DataList){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $limit_counter_collect = $this->UserApplyCollectLimit;
	  $limit_counter_images  = $this->UserApplyDigitalLimit;
	  
	  try{    
		
		if(!isset($this->SearchConf['TEMP_Apply_Queue'])){
		  $this->SearchConf['TEMP_Apply_Queue'] = array();	
		}
		
		$apply_data = json_decode(base64_decode(rawurldecode($DataList)),true);
		
		if((!isset($apply_data['applyto']) || !count($apply_data['applyto'])) && (!isset($apply_data['booking']) || !count($apply_data['booking']))){
		  throw new Exception('_APPLY_ELEMENT_IS_NULL');  
		}
		
		//:確認使用者帳號
		if(!intval($this->USER->UserNO)){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNLOGIN');  	
		}
		
		
		// 查詢資料庫
		$apply_collect = array();
		$apply_counter = 0;
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_USER_APPLY_QUEUE());
		$DB_OBJ->execute(array('uid'=>$this->USER->UserNO));
		
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		    $DB_COUNT = $this->DBLink->prepare(SQL_Archive::APPLY_DATA_LIST());
			$DB_COUNT->execute(array('apply_code'=>$tmp['apply_code'] ));
			while($apply = $DB_COUNT->fetch(PDO::FETCH_ASSOC)){
			  if( $apply['check_state']!='_CHECKED'  && $apply['check_state']!='_BOOKING' ){
				if($apply['page_count']!=0){
				  $limit_counter_images-=$apply['page_count'];  				  
				}else{
				  if(!isset($apply_collect[$apply['in_store_no']])) $apply_collect[$apply['in_store_no']]=0;
				  $apply_collect[$apply['in_store_no']]++;	
				}
			  }
			}
		}
		$limit_counter_collect-=count($apply_collect);
		
		
		if($limit_counter_collect<1 && $limit_counter_images<1){
		  throw new Exception('_APPLY_UP_TO_LIMIT');   	
		}
		
		//:註冊申請序號
		$DB_REGIST = $this->DBLink->prepare(SQL_Archive::APPLY_REGIST_USER_APPLY_KEY());
		$DB_REGIST->bindValue(':uid',$this->USER->UserNO);
		$DB_REGIST->bindValue(':reason',isset($apply_data['reason']) ? $apply_data['reason'] : '' );
		if(!$DB_REGIST->execute()){
		   throw new Exception('_APPLY_REGIST_FAIL');  	 	
		}
		
		$apply_no = $this->DBLink->lastInsertId('user_apply');
		$apply_id = _SYSTEM_NAME_SHORT.str_pad($apply_no,5,'0',STR_PAD_LEFT);
		
		$this->DBLink->query("UPDATE user_apply SET apply_code='".$apply_id ."' WHERE uano=".$apply_no.";");
		
		//$this->SearchConf['TEMP_Apply_Queue']
		$result['action'] = true;		
		$result['data']   = $apply_id;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	
	//-- Pre Process User Apply
	//[input] : $ApplyCode =  AHAS\d+
	//[input] : $DataList  =  urlencode(json_pass())  = applyto=>array(),discard=>array(id,id,id)
	public function User_Apply_Preprocess($ApplyCode='',$DataList=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		if(!isset($this->SearchConf['TEMP_Apply_Queue'])){
		  $this->SearchConf['TEMP_Apply_Queue'] = array();	
		}
		
		$apply_data = json_decode(base64_decode(rawurldecode($DataList)),true);
		
		if((!isset($apply_data['applyto']) || !count($apply_data['applyto'])) && (!isset($apply_data['booking']) || !count($apply_data['booking']))){
		  throw new Exception('_APPLY_ELEMENT_IS_NULL');  
		}
		
		//:確認使用者帳號
		if(!intval($this->USER->UserNO)){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNLOGIN');  	
		}
		
		//:確認申請序號
		if(!preg_match('/^'._SYSTEM_NAME_SHORT.'\d+$/',$ApplyCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 計算目前剩餘數量
		//:檢查並輸入申請表單
		$apply_collection_upbound  = $this->UserApplyCollectLimit;
		$apply_digitalpage_upbound = $this->UserApplyDigitalLimit;
		
		$apply_collect = array();
		$apply_counter = 0;
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_USER_APPLY_QUEUE());
		$DB_OBJ->execute(array('uid'=>$this->USER->UserNO));
		
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		    $DB_COUNT = $this->DBLink->prepare(SQL_Archive::APPLY_DATA_LIST());
			$DB_COUNT->execute(array('apply_code'=>$tmp['apply_code'] ));
			while($apply = $DB_COUNT->fetch(PDO::FETCH_ASSOC)){
			  if($apply['check_state']!='_CHECKED' && $apply['check_state']!='_BOOKING'){
				if($apply['page_count']!=0){
				  $apply_digitalpage_upbound-=$apply['page_count'];  				  
				}else{
				  if(!isset($apply_collect[$apply['in_store_no']])) $apply_collect[$apply['in_store_no']]=0;
				  $apply_collect[$apply['in_store_no']]++;	
				}
			  }
			}
		}
		$apply_collection_upbound-=count($apply_collect);
		
		// 查詢資料庫
		$apply_refer = array();
		$DB_OBJ = $this->DBLink->prepare(SQL_Archive::APPLY_GET_APPLY_META());
		foreach( $this->SearchConf['TEMP_Apply_Queue'] as $id=>$data){
          $DB_OBJ->bindValue(':applyindex'	, $id );
          $DB_OBJ->execute();
		  if(!$meta = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
			continue;  
		  }
		  $apply_refer[$id] = array(
		    'store_no'  =>$meta['collection'],
			'digital_no'=>$meta['identifier'],
			'page'=>$meta['page_count']
		  );
		}
		
		$apply_collection_list = array();
		$apply_digitalpage_count = 0;
		
		$apply_counter = 0;
		$DB_APPLY = $this->DBLink->prepare(SQL_Archive::APPLY_LIST_INSERT());
		
		// 處理調閱申請
		foreach($apply_data['applyto'] as $data_identifier){
		  
		  if(!isset($apply_refer[$data_identifier])){
			continue;  
		  }	
	      
		  $collection_id = $apply_refer[$data_identifier]['store_no'];  //.$apply_refer[$data_identifier]['digital_no']
		  $digital_store = $apply_refer[$data_identifier]['digital_no'];
		  $digital_page  = $apply_refer[$data_identifier]['page'];
		  
		  // check apply limit
		  if($digital_page && $apply_digitalpage_count>=$apply_digitalpage_upbound ){ continue; }  // 有數位化但是頁數超過限制
		  
		  if(!$digital_page && count($apply_collection_list)>=$apply_collection_upbound){ continue; }  // 未數位化但是卷超過上限
		  
		  
		  // process apply insert
		  if($digital_page){
			$apply_digitalpage_count+=$digital_page;   
		  }else{
			if(!isset($apply_collection_list[$collection_id])) $apply_collection_list[$collection_id]=0;
		    $apply_collection_list[$collection_id]++;  
		  }
		  
		  $apply_copy_mode = isset($apply_data['copymod'][$data_identifier]) ? $apply_data['copymod'][$data_identifier] : '';
		  
		  $DB_APPLY->bindValue(':apply_code'	,$ApplyCode);
		  $DB_APPLY->bindValue(':in_store_no'	,$apply_refer[$data_identifier]['store_no'] );     
		  $DB_APPLY->bindValue(':store_no'		,$apply_refer[$data_identifier]['digital_no']);     
		  $DB_APPLY->bindValue(':copy_mode'		,$apply_copy_mode);  
		  
		  if( $DB_APPLY->execute() ){
			$apply_counter++;
		    if(isset($this->SearchConf['TEMP_Apply_Queue'][$data_identifier])){
			  unset($this->SearchConf['TEMP_Apply_Queue'][$data_identifier]);  
		    }
		  }
		}
		
		// 處裡預約調閱
		$DB_BOOKING = $this->DBLink->prepare(SQL_Archive::APPLY_BOOKING_INSERT());
		foreach($apply_data['booking'] as $data_identifier => $booking_date){
		  
		  if(!isset($apply_refer[$data_identifier])){
			continue;  
		  }	
		  
		  if(!strtotime($booking_date) || strtotime('now') > strtotime($booking_date.' 15:00:00') ){
			continue;  
		  }	
	      
		  $collection_id = $apply_refer[$data_identifier]['store_no'];  //.$apply_refer[$data_identifier]['digital_no']
		  $digital_store = $apply_refer[$data_identifier]['digital_no'];
		  $digital_page  = $apply_refer[$data_identifier]['page'];
		  
		  // check booking limit
		  /* !!!! 還沒處理預約上限 
		  if(count($apply_collection_list)>=$apply_collection_upbound ||  $apply_digitalpage_count>=$apply_digitalpage_upbound ){
			break;  
		  }
		  
		  // process apply insert
		  if(!isset($apply_collection_list[$collection_id])) $apply_collection_list[$collection_id]=0;
		  $apply_collection_list[$collection_id]++;  
		  
		  */
		  
		  $apply_copy_mode = isset($apply_data['copymod'][$data_identifier]) ? $apply_data['copymod'][$data_identifier] : '';
		  
		  $DB_BOOKING->bindValue(':apply_code'		,$ApplyCode);
		  $DB_BOOKING->bindValue(':in_store_no'		,$apply_refer[$data_identifier]['store_no'] );     
		  $DB_BOOKING->bindValue(':store_no'		,$apply_refer[$data_identifier]['digital_no']);     
		  $DB_BOOKING->bindValue(':copy_mode'		,$apply_copy_mode); 
		  $DB_BOOKING->bindValue(':booking_date'	,$booking_date);  		  
		  
		  if( $DB_BOOKING->execute() ){
			$apply_counter++;
		    if(isset($this->SearchConf['TEMP_Apply_Queue'][$data_identifier])){
			  unset($this->SearchConf['TEMP_Apply_Queue'][$data_identifier]);  
		    }
		  }
		}
		
		
		//:更新申請資料
		$DB_UPDATE = $this->DBLink->prepare(SQL_Archive::APPLY_SUBMIT_UPDATE());
		$DB_UPDATE->bindValue(':apply_code'	  ,$ApplyCode);
		$DB_UPDATE->bindValue(':apply_count'  ,$apply_counter );     
		$DB_UPDATE->bindValue(':uid'		  ,$this->USER->UserNO );     
		$DB_UPDATE->execute();
		
		//:移除捨棄資料
		foreach($apply_data['discard'] as $data_identifier){
		  if(isset($this->SearchConf['TEMP_Apply_Queue'][$data_identifier])){
			unset($this->SearchConf['TEMP_Apply_Queue'][$data_identifier]);  
		  }
		}
		
		sleep(2);
		
		$result['action'] = true;
		$result['data']['code']  = $ApplyCode;
		$result['data']['date']  = date('Y-m-d');
		$result['data']['status'] = '調閱申請已提交，等待受理中';
		$result['data']['apply'] = $apply_counter;
		$result['data']['queue'] = count($this->SearchConf['TEMP_Apply_Queue']);
		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	
	//-- Submit User Apply
	//[input] : $ApplyCode =  AHAS\d+
	
	public function User_Apply_Search($ApplyCode){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		//:確認申請序號
		if(!preg_match('/^'._SYSTEM_NAME_SHORT.'\d+$/',$ApplyCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		//:確認使用者帳號
		if(!intval($this->USER->UserNO)){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNLOGIN');  	
		}
		
		//:get apply data
		$apply = array();
		$DB_CHECK = $this->DBLink->prepare(SQL_Archive::APPLY_REGIST_SEARCH());
	    $DB_CHECK->bindValue(':uid',$this->USER->UserNO);
		$DB_CHECK->bindValue(':apply_code',$ApplyCode );
		if(!$DB_CHECK->execute() || !$apply=$DB_CHECK->fetch(PDO::FETCH_ASSOC) ){
		   throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	 	
		}
		
		//:get apply list
		$apply_data = array();
		$data_counter = 0;
		$page_counter = 0;
		$DB_APPLY = $this->DBLink->prepare(SQL_Archive::APPLY_DATA_LIST());
	    $DB_APPLY->bindValue(':apply_code',$ApplyCode );
		if(!$DB_APPLY->execute()){
		   throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	 	
		}
		
		while( $tmp=$DB_APPLY->fetch(PDO::FETCH_ASSOC) ){
		  $data_counter++;
		  $page_counter+=$tmp['page_count'];
		  $record = array();	
		  $meta = json_decode($tmp['search_json'],true);
		  $record['no'] = $data_counter;
		  $record['zong']  = $meta['zong_name'];
		  $record['store'] = $tmp['store_no'] ? $tmp['store_no'] : $tmp['in_store_no'];
		  $record['title'] = $meta['name'];
		  $record['check'] = $tmp['check_info'] ? $tmp['check_info'] : "<span style='color:red;'>處理中</span>";
		  $record['note']  = $tmp['check_note'];
		  $record['page']  = $tmp['check_range'];
		  
		  $copy_apply = explode(';',$tmp['copy_mode']);
		  $copy_mode  = array();
		  if(in_array('gray',$copy_apply)) $copy_mode[] = '紙本黑白';  
		  if(in_array('color',$copy_apply)) $copy_mode[] = '紙本彩色';  
		  if(in_array('digital',$copy_apply)) $copy_mode[] = '數位檔案';  
		  $record['copy']  = join(',',$copy_mode);
		  $apply_data[] = $record;
		}
		
		//final 
		$result['action'] = true;
        $result['data']['user'] = array('user_name'=>$this->USER->UserInfo['user_name'],'user_mail'=>$this->USER->UserInfo['user_mail']);			
		$result['data']['code'] = $ApplyCode;
		$result['data']['list'] = $apply_data;
		$result['data']['date'] = substr($apply['apply_date'],0,10);
		$result['data']['data_count'] = $data_counter;
		$result['data']['page_count'] = $page_counter;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	//-- 因為有些資訊不可公開，所以與上述功能區隔
	//[input] : $ApplyCode =  AHAS\d+
	public function User_Apply_Download($ApplyCode){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		//:確認申請序號
		if(!preg_match('/^'._SYSTEM_NAME_SHORT.'\d+$/',$ApplyCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		//:確認使用者帳號
		if(!intval($this->USER->UserNO)){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNLOGIN');  	
		}
		
		//:get apply data
		$apply = array();
		$DB_CHECK = $this->DBLink->prepare(SQL_Archive::APPLY_REGIST_SEARCH());
	    $DB_CHECK->bindValue(':uid',$this->USER->UserNO);
		$DB_CHECK->bindValue(':apply_code',$ApplyCode );
		if(!$DB_CHECK->execute() || !$apply=$DB_CHECK->fetch(PDO::FETCH_ASSOC) ){
		   throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	 	
		}
		
		//:get apply list
		$apply_data = array();
		$data_counter = 0;
		$page_counter = 0;
		$DB_APPLY = $this->DBLink->prepare(SQL_Archive::APPLY_DATA_LIST());
	    $DB_APPLY->bindValue(':apply_code',$ApplyCode );
		if(!$DB_APPLY->execute()){
		   throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	 	
		}
		
		while( $tmp=$DB_APPLY->fetch(PDO::FETCH_ASSOC) ){
		  $data_counter++;
		  $page_counter+=$tmp['page_count'];
		  $record = array();	
		  $meta = json_decode($tmp['search_json'],true);
		  $record['no'] = $data_counter;
		  $record['zong']  = $meta['zong_name'];
		  $record['store'] = $tmp['store_no'] ? $tmp['store_no'] : $tmp['in_store_no'];
		  $record['title'] = $meta['name'];
		  $record['check'] = $tmp['check_state']=='_CHECKED' ? $tmp['check_info'] : '<span style="color:red;">處理中</span>'  ;
		  $record['note']  = $tmp['check_note'];
		  $record['page']  = $tmp['check_range'];
		  $copy_apply = explode(';',$tmp['copy_mode']);
		  $copy_mode  = array();
		  if(in_array('gray',$copy_apply)) $copy_mode[] = '紙本黑白';  
		  if(in_array('color',$copy_apply)) $copy_mode[] = '紙本彩色';  
		  if(in_array('digital',$copy_apply)) $copy_mode[] = '數位檔案';  
		  $record['copy']  = join(',',$copy_mode);
		  
		  $apply_data[] = $record;
		}
		
		//final 
		$result['action'] = true;
        $result['data']['user'] = array('user_name'=>$this->USER->UserInfo['user_name'],'user_mail'=>$this->USER->UserInfo['user_mail']);			
		$result['data']['folder'] = $this->USER->UserFolder;		
		$result['data']['code'] = $ApplyCode;
		$result['data']['list'] = $apply_data;
		$result['data']['date'] = substr($apply['apply_date'],0,10);
		$result['data']['data_count'] = $data_counter;
		$result['data']['page_count'] = $page_counter;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	
	// built file level
	public function built_file_level($TypeCode,$LevelType,$LevelTarget){
	    
	  $idbs = $this->DBLink->prepare(SQL_Archive::SELECT_META_LEVEL( )); 
	  $idbs->bindParam(':zong',$TypeCode,PDO::PARAM_STR);
	  $idbs->bindParam(':type',$LevelType,PDO::PARAM_STR);
	  $idbs->execute();
	  
	  $lvarray = array();
	  $lvcount = array();
	  $lvnames = array();
	  
	  $lv_sets = $idbs->fetchAll(PDO::FETCH_ASSOC);
	  foreach($lv_sets as $lv_data){
		$record = $lv_data;
		$record['switch']   = ' * ';
		$lvarray[$record['lvcode']] = $record;
        
		if(!isset($lvcount[$record['uplv']])) $lvcount[$record['uplv']]=0;
		$lvcount[$record['uplv']]++;
	  }	
	  
	  // 排序
	  uksort($lvarray,function($a,$b){ 
	    $len=max(strlen($a),strlen($b));
		if ($a == $b) return 0;
		return hexdec(str_pad($a,$len,'0',STR_PAD_RIGHT)) > hexdec(str_pad($b,$len,'0',STR_PAD_RIGHT)) ? 1 : -1;
	  });
	  
	  foreach($lvarray as $code => &$record){
		$lvnames = array_slice($lvnames,0,$record['site']);
		$lvnames[($record['site']-1)] = $record['name'];
		$record['level'] = join('/',$lvnames);  
        
		if(isset($lvcount[$code]) && intval($lvcount[$code])){
		  $record['switch'] = " + "; // " - ";
		}
	  }
	  return $lvarray;
	}
	
	
	//-- 文物類資料
	//[input] : $RelicId 
	public function Get_Member_App_Data($MemberName){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		$member_name = rawurldecode($MemberName);
		
		//:確認人名
		if(!$member_name){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		//:get apply data
		$member = array();
		$DB_MBR = $this->DBLink->prepare(SQL_Archive::AP_GET_MEMBER_META());
	    $DB_MBR->bindValue(':member_name',$member_name);
		if(!$DB_MBR->execute() || !$tmp=$DB_MBR->fetch(PDO::FETCH_ASSOC) ){
		   throw new Exception('_ARCHIVE_SEARCH_RESULT_NONE');  	 	
		}
		
		$member['meta'] = json_decode($tmp['source_json'],true);     // 原始資料   
		if(!isset($member['meta']['mbr_staff'])){  
		  $member['meta']['mbr_staff'] = '議員';
		  if(preg_match_all('/(議長|副議長|議員)/',$member['meta']['mbr_offer'],$matchs)){
			$member['meta']['mbr_staff'] = join('，',array_reverse($matchs[0]));  
		  }		  
		}
		
		$member['dobj'] = json_decode($tmp['dobj_json'],true);  // 數位檔案 // 大頭照
		$member['refer'] = json_decode($tmp['refer_json'],true);// 相關參照 // 統計圖 
		
		//final 
		$result['action'] = true;
        $result['data'] = $member;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
  }	