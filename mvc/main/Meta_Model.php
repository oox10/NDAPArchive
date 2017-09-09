<?php

  class Meta_Model extends Admin_Model{
    
	
	/***--  Function Set --***/
    public function __construct(){
	  parent::__construct();
	  parent::initial_user($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['USER']);
	}
	
	protected $ResultCount;   // 查詢結果數量
	protected $PageNow;       // 當前頁數 
	protected $LengthEachPage;// 每頁筆數
	
	protected $Metadata;
	
	/*[ Meta Function Set ]*/ 
    
	//-- Admin Meta Process Search Filter 
	// [input] : $SearchConfig => 搜尋設定  (string)base64_decode();
	
	public function ADMeta_Process_Filter($SearchConfig=''){
	  
	  $result_key = parent::Initial_Result('filter');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $default_zong_set = ["檔案","公報","議事錄","議事影音","議員傳記","活動照片"];
	  
	  try{
	    
		// 搜尋參數
		/* 
		zongs = [ ]
		limit[  // 條件篩選
		  none : 不限制   
		  secret : 密件  private: 隱私  mask : 遮頁資料  close : 未開放  newest : 最新資料
		]
		search[  // 一般搜尋
		  date_start : 
		  date_end : 
          condition :
		]
		order[
		  modify_time
		  identifier
		  date_start
		]
		*/
	    
  	    $data_search = json_decode(base64_decode(str_replace('*','/',rawurldecode($SearchConfig))),true); 
		
		// 處理搜尋條件
		$time_query = array(); // 時間參數
		$term_query = array(); // 條件參數
		$order_conf = array(); // 排序參數
		
		$heightline = array();
		
		
		// 處理全宗篩選
		$zong_array = isset($data_search['zongs']) && count($data_search['zongs']) ?  explode(';',$data_search['zongs']) : $default_zong_set;
		$zong_query = join(" | ",$zong_array);
		$data_search['zongs'] = $zong_array;
		
		// test
		//$data_search['search']['condition'] = "蘇貞昌";
		
		if(isset($data_search['search'])&&count($data_search['search'])){
		  foreach($data_search['search'] as $field => $searchstring ){
			
            switch($field){
              case 'date_start':
			    if(strtotime($searchstring)){
				  $time_query['start']	= date('Y-m-d',strtotime($searchstring));	
				}
				break;
				
              case 'date_end':
                if(strtotime($searchstring)){
				  $time_query['end']	= date('Y-m-d',strtotime($searchstring));	
				}
				break;
			    
			  case 'condition':
			  default:
                $search_and_sets = preg_split('/[&]/',$searchstring);
				foreach($search_and_sets as $termset){
				  
				  $attr = '+';
				  if(preg_match('/^\-/',$termset)){
					$termset = preg_replace('/^-/','',$termset);  
					$attr = '-';
				  }
				  
				  $search_or_sets = preg_split('/[|\s+]/',$termset);		  
				  
				  if($attr == '+'){
					$term_query[] = '("'.join('" | "',$search_or_sets).'")';   
				  }else{
					$term_query[] = '(-"'.join('" & "-',$search_or_sets).'")';    
				  }
				
				  $heightline = array_merge($heightline,$search_or_sets); 
				
				}  			  
			  break;
			}			
		  }
		  
		  // 將時間搜尋加入條件
		  if(count($time_query)){
			$term_query[] = "date_start:[ ".(isset($time_query['start'])?$time_query['start']:'*')." TO ".(isset($time_query['end'])?$time_query['end']:'*')." ]"; 
		  }
		}
		
		//處理特殊篩選
		if(isset($data_search['limit'])){
		  switch($data_search['limit']){
			  case 'secret':   $term_query[] = '(_flag_secret:1)'; break;  //密件
			  case 'privacy':  $term_query[] = '(_flag_privacy:1)'; break; //隱私 
			  case 'mask':     $term_query[] = '(_flag_mask:1)'; break;    //遮頁資料 
			  case 'close':    $term_query[] = '(_flag_open:0)'; break;    //不開放
			  case 'update':   $term_query[] = '(_flag_update:0)'; break;  //最新資料 
			  case 'none': default:   break; // 不限制與其他		
		  }
		}else{
		  $data_search['limit'] = '';	
		}
		
		$params =[
			"size" => 20,
			"from" => 0,
			'index' => 'ndap',
			'type' => 'search',
			'body' => [
			  'query'=>[
				 "query_string" => [
					//"query"=> "(\"蔣中正\") AND (\"顧祝\") AND location:(*漢口* *江西*) AND in_store_no:00200000*",
					"query"=> "zong:( ".$zong_query." ) ".( count($term_query) ? "AND".join(" AND ",$term_query) : '' ),
				 ],
			  ],
			  "highlight"=>[
			     "pre_tags"=>["<em>"],
				 "post_tags"=> ["</em>"],
				 "fields"=>["main_mamber"=>["type"=>"plain"]]
			  ],
			  "sort"=>[
			    "collection"=>["order"=>"asc"],
				"identifier"=>["order"=>"asc","missing"=>"_last"]
			  ],
			  "post_filter"=>[
			    "bool" =>[
				  "must"=>[
				   // ["terms"=>['person'=>['陳誠']]]
				  ]
				]
			  ],
			  "aggs"=>[
				"pq_zong"=>[
				  "terms"=>[
					"field"=>"zong",
					"size" => "5"	
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
			  ],
			 
			] 
		];
		
		
		/*
		foreach($this->SearchInfo['term_filter'] as $field=>$terms){
		  $params['body']['post_filter']['bool']['must'][] = ["terms"=>[$field=>$terms]];	
		}
		*/
		/*
		if(isset($this->SearchInfo['doms_config']['sortby'])){
		  switch($this->SearchInfo['doms_config']['sortby']){
			case 'date_string-asc':  $params['body']['sort'] = array('date_string'=>array('order'=>'asc'))+$params['body']['sort']; break; 
			case 'date_string-desc':  $params['body']['sort'] = array('date_string'=>array('order'=>'desc'))+$params['body']['sort']; break; 
			case 'identifier-asc' :  $params['body']['sort'] = ["identifier"=>["order"=>"asc","missing"=>"_last"]]; break;  
		    case 'identifier-desc' :  $params['body']['sort'] = ["identifier"=>["order"=>"desc","missing"=>"_last"]]; break;  
		    default: break;
		  }
		}
		*/
		
		$result['action'] = true;
		$result['data']['submit']   = $data_search;
	    $result['data']['esparams'] = $params;
		$result['data']['termhit']  = array_unique($heightline);
	    
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Meta Search Query 
	// [input] : $DataType => _all / 檔案 / 公報 / 議事錄 / 議事影音 /  議員傳記  /  活動照片;
	// [input] : $SearchConfig => 搜尋設定  (string)base64_decode();
	
	public function ADMeta_Execute_Search($SearchPattern=array(),$Pageing){
	  
	  $result_key = parent::Initial_Result('search');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		  
        $Pageing = trim($Pageing);
		if(!preg_match('/^\d+\-\d+$/', $Pageing )) $Pageing = '1-50';	
		list($p_start,$p_end) = explode('-',$Pageing);
		
		
		
		
		// 防止網址竄改
		if($p_start > 10000){
		  $p_start =  9901;
          $p_end   = 10000;
		}else if( $p_start >=  $p_end){
		  $p_end = $p_start+20;	
		}else if( $p_end-$p_start > 999){
          $p_end = $p_start+999;
		}
		
	    $params = $SearchPattern;  
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
		
		$this->ResultCount   = intval($response['hits']['total']);   // 查詢結果數量
		
		$result_source  = isset($response['hits']['total']) && intval($response['hits']['total']) ? $response['hits']['hits']:array();
		$result_indexs  = count($result_source) ? array_map(function($document){return $document['_id'];},$result_source ) : array();
		
		// 取得db資料
		$data = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_SEARCH_META());
		foreach($result_source as $key => $source){
		  if( !$DB_GET->execute(array('system_id'=> $source['_id']))){
		    continue;
		  }
		  $meta = $DB_GET->fetch(PDO::FETCH_ASSOC);
		  $result_source[$key]['_db']['@time'] = $meta['@time'];
		  $result_source[$key]['_db']['@user'] = $meta['@user'];
		  $result_source[$key]['_db']['lockmode'] = $meta['_lockmode'];
		  $result_source[$key]['_db']['auditint'] = $meta['_auditint'];
		  $result_source[$key]['_db']['open'] = $meta['_open'];
		  $result_source[$key]['_db']['view'] = $meta['_view']; 
		}
		
		
		/*
		// 頁面參數
		
	    $this->ResultZongAgg = isset($response['aggregations']['pq_zong'])  ? $response['aggregations']['pq_zong']['buckets'] : array();
		$this->ResultYearAgg = isset($response['aggregations']['pq_yearnum']) ? $response['aggregations']['pq_yearnum']['buckets'] : array();
		
		// 檢測是否須取得階層資料
		$zong_group          = isset($response['aggregations']['pq_zong'])? $response['aggregations']['pq_zong']['buckets'] : array();
		*/
		$result['action'] = true;
		$result['data']['list']   = $result_source;
		$result['data']['count']  = isset($response['hits']['total']) && intval($response['hits']['total']) ? $response['hits']['total']:0;
		$result['data']['range']  = '1-'.($p_end-$p_start+1);
		$result['data']['start']  = $p_start;
		$result['data']['limit']  = array('start'=>$p_start,'length'=>$params['size'],'range'=>$Pageing);	
		
	   
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	
	}
	
	
	//-- Admin Meta Page Data OList 
	// [input] : $PagerMaxNum => int // 頁面按鈕最大數量
	public function ADMeta_Get_Page_List( $PagerMaxNum=1 ){
	  
	  $result_key = parent::Initial_Result('page');
	  $result  = &$this->ModelResult[$result_key];
      
	  try{
        
		
		$page_show_max = intval($PagerMaxNum) > 0 ? intval($PagerMaxNum) : 1;
		
		
	    $pages = array();
		
		$pages['all'] = array(1=>'');
		
		
		// 必要參數，從ADMeta_Get_Meta_List而來
		$this->ResultCount;   // 查詢結果數量
	    $this->PageNow;   
	    $this->LengthEachPage;
		
		$total_page = ( $this->ResultCount / $this->LengthEachPage ) + ($this->ResultCount%$this->LengthEachPage ? 1 :0 );
		
		// 建構分頁籤
		for($i=1;$i<=$total_page;$i++){
		  $pages['all'][$i] = (($i-1)*$this->LengthEachPage+1).'-'.($i*$this->LengthEachPage);
		}
		
		$pages['top']   = reset($pages['all']);
		$pages['end']   = end($pages['all']);
		$pages['prev']  = ($this->PageNow-1 > 0 ) ? $pages['all'][$this->PageNow-1] : $pages['all'][$this->PageNow];
		$pages['next']  = ($this->PageNow+1 < $total_page ) ? $pages['all'][$this->PageNow+1] : $pages['all'][$this->PageNow];
		$pages['now']   = $this->PageNow;  
		
		$check = ($page_show_max-1)/2;
	    if($total_page < $page_show_max){
		  $pages['list'] = $pages['all'];  	
		}else {  
          if( ($this->PageNow - $check) <= 1 ){    // 抓最前面 X 個
            $start = 0;
		  }else if( ($this->PageNow + $check) > $total_page ){  // 抓最後面 X 個
            $start = $total_page-(2*$check)-1;    
		  }else{
            $start = $this->PageNow - $check -1;
		  }
	      $pages['list'] = array_slice($pages['all'],$start,$page_show_max,TRUE);
		}
		
		// 建構選項
		$effect_page = count($pages['all']);
		
		if(count($pages['all']) > 500 ){
			for($x=1;$x<=$effect_page;$x++){
			  if($x==1 || $x==$effect_page || abs($x-$this->PageNow)<20){
				$pages['jump'][$x] = $pages['all'][$x];
			  }else if(abs($x-$this->PageNow)<100 && $x%10===0){
				$pages['jump'][$x] = $pages['all'][$x];
			  }else if(abs($x-$this->PageNow)<1000 && $x%200===0){
				$pages['jump'][$x] = $pages['all'][$x];
			  }else if(abs($x-$this->PageNow)>=1000 &&  abs($x-$this->PageNow)<10000 && $x%1000===0){
				$pages['jump'][$x] = $pages['all'][$x];
			  }else if(abs($x-$this->PageNow)>=10000 && $x%10000===0){
				$pages['jump'][$x] = $pages['all'][$x];
			  }
			}
		  
		}else{
		  $pages['jump'] = $pages['all'];	
		}
		unset($pages['all']);
		
		
		$result['data']   = $pages;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	
	}
	
	
	//-- Admin Meta Execute User Select Batch
	// [input] : SelectedSids  :  encoed array string;
	// [input] : Action        :  open / view / ;  !strtolower-Y
	// [input] : Setting       :  (open):0/1 (view):開放/限閱/會內/關閉  ;
	
	public function ADMeta_Execute_Batch($SelectedSids,$Action,$Setting){
		
	  $result_key = parent::Initial_Result('batch');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 允許之批次設定
		$accept_action = array('open','view','export');
		$accept_view_config = array('開放','限閱','會內','關閉');
		
		$data_batch_counter = 0;
		$data_selected = json_decode(base64_decode(str_replace('*','/',rawurldecode($SelectedSids))),true); 
		
		// check permission
		if(  !intval($this->USER->PermissionNow['group_roles']['R00']) && !intval($this->USER->PermissionNow['group_roles']['R02']) ){
		  throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');	
		} 
		
		// check data
		if(!count($data_selected)) throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		if(!in_array($Action,$accept_action)) throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		
		// set config 
		$meta_batch = array();
		switch($Action){
		  case 'open': $meta_batch['_flag_open'] = intval($Setting); break;
          case 'view':
		    if(in_array($Setting,$accept_view_config)){
			  $meta_batch['_view'] = $Setting ; 
			}
			break;
		}
		if(!count($meta_batch)) throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	
		// get data set
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_SELECTED($data_selected));
		if( !$DB_GET->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		while($meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  
		  // 取得原始資料
		  $source = array();
		  $DB_SOURCE	= $this->DBLink->prepare( SQL_AdMeta::GET_SOURCE_META($meta['zong']));
		  $DB_SOURCE->bindParam(':id'   , $meta['identifier'] );	
		  if( !$DB_SOURCE->execute() || !$source = $DB_SOURCE->fetch(PDO::FETCH_ASSOC)){
		    continue;
		  }
          
		  // 補充系統欄位
		  $meta_batch['_userupdate'] = $this->USER->UserID;
			
		  // 執行修改
		  $DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_SOURCE_META(array_keys($meta_batch),$meta['zong']));
		  $DB_SAVE->bindValue(':id'    , $meta['identifier']);
		  foreach($meta_batch as $uf=>$uv){
			$DB_SAVE->bindValue(':'.$uf , $uv);
		  }
		  if( !$DB_SAVE->execute()){
			continue;
		  }
		  
		  // 執行更新
		  $result = self::ADMeta_Process_Meta_Update($meta['system_id']);
		  if(!$result['action']){
			continue;  
		  }
		  
		  $data_batch_counter++;
		}
		
		// final
		$result['action'] = true;
		$result['data'] = $data_batch_counter;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-- Admin Tasks Receive Task Data 
	// [input] : identifier  :  \w\d+;
	public function ADTasks_Receive_Task($DataNo=''){
		
	  $result_key = parent::Initial_Result('action');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d]+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	   
		// 取得資料
		$data = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$data = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
	    
		if($data['handler']){
		  throw new Exception('_BUILT_TASK_HAS_HANDLER');	
		}
		
		if($data['_status'] != '_INITIAL'){
		  throw new Exception('_BUILT_TASK_STATUS_FAIL');	
		}
		
		$DB_UPD	= $this->DBLink->prepare( SQL_AdMeta::SET_TARGET_TASK_HANDLER());
		$DB_UPD->bindValue(':id',$data['task_no'] );
		$DB_UPD->bindValue(':handler',$this->USER->UserID);
		$DB_UPD->bindValue(':status','_ALLOCAT');
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// final
		$result['action'] = true;
		$result['data']   = $data['task_no'] ;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Tasks Giveup Task Data 
	// [input] : identifier  :  \w\d+;
	public function ADTasks_Giveup_Task($DataNo=''){
		
	  $result_key = parent::Initial_Result('action');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d]+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	   
		// 取得資料
		$data = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$data = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		if( $data['handler'] != $this->USER->UserID){
		  throw new Exception('_BUILT_TASK_NOT_HANDLER');	
		}
		
		if($data['_status'] == '_EDITING'){
		  throw new Exception('_BUILT_TASK_STATUS_EDITING');	
		}
		
		if($data['_status'] != '_ALLOCAT'){
		  throw new Exception('_BUILT_TASK_STATUS_FAIL');	
		}
		
		$DB_UPD	= $this->DBLink->prepare( SQL_AdMeta::SET_TARGET_TASK_HANDLER());
		$DB_UPD->bindValue(':id',$data['task_no'] );
		$DB_UPD->bindValue(':handler','');
		$DB_UPD->bindValue(':status','_INITIAL');
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// final
		$result['action'] = true;
		$result['data']   = $data['task_no'] ;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	/* [ Built Function Set : 編輯頁面模組 ] */
	
	//-- Admin Meta : Get Meta Resouce
	// [input] : BookNo  :  \w\d+;  全宗號
	// [input] : DataNo  :  \d+;  系統序號
	
	
	public function ADMeta_Get_Task_Resouse($BookNo='',$DataNo=0,$Mode='edit'){
      
	  $result_key = parent::Initial_Result('resouse');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d]+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 目標系統資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$meta['data_type'];
		$meta['zong'];
		$meta['identifier'];
		$meta['collection'];
		$meta['dobj_json'];
		$meta['refer_json'];
		
		// 取得原始資料
		$source = array();
		$DB_SOURCE	= $this->DBLink->prepare( SQL_AdMeta::GET_SOURCE_META($meta['zong']));
		$DB_SOURCE->bindParam(':id'   , $meta['identifier'] );	
		if( !$DB_SOURCE->execute() || !$source = $DB_SOURCE->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 取得全宗詮釋資料
		$list = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_COLLECTION_META());
		$DB_GET->bindParam(':zong'   , $meta['zong'] );	
		$DB_GET->bindParam(':collection_id'   , $BookNo);	
		if( !$DB_GET->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		while($tmp = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  $element  = $tmp;	
		  $element['_search'] = json_decode($tmp['search_json'],true);
		  $element['_source'] = json_decode($tmp['source_json'],true);
		  $list[] = $element;	
		}
		
		// 取得數位檔案設定
		$file_list = array();
		$dobj_list = array();
		$meta_dobj = json_decode($meta['dobj_json'],true);
		
		$digital_object_path = _SYSTEM_DIGITAL_FILE_PATH;
		
		if(isset($meta_dobj['dopath'])){
          $digital_object_path.= $meta_dobj['dopath'];
		  $digital_object_path.= 'browse/';
		  $digital_object_path.= $meta['collection'].'/';
		
		  if(is_dir($digital_object_path)){
            $file_list = array_slice(scandir($digital_object_path),2);			
		  }
		  
		  if( $meta['zong']=='公報' || $meta['zong']=='議事錄'){
			  // 議事錄影像需要重新排序
			  $book_page_list  = array();
			  $ImageZnumArray  = array();
			  $ImageNumeArray  = array();
			  $ImageINumArray  = array();
			  $ImageCNumArray  = array();
			  $ImageAPnumArray = array();
				
			  foreach($file_list as $img){
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
			  $ImageNumeArray = array_merge( $ImageZnumArray , $ImageNumeArray );    //把影像編號00的排到最前面
			  $ImageNumeArray = array_merge( $ImageCNumArray , $ImageNumeArray); 
			  $dobj_list = $ImageNumeArray;
			
		  }else{
			$dobj_list = $file_list;	
		  }
			
			
			
		  // 取得數位物件設定
		  $doprofileread = '';
		  $doprofilepath =  _SYSTEM_DIGITAL_FILE_PATH.$meta_dobj['dopath'].'profile/'.$meta['collection'].'.conf'; 
		  if(is_file( $doprofilepath )){
			$doprofileread = file_get_contents($doprofilepath);	  
		  }
		  $dobj_profile = json_decode($doprofileread,true);
		  $dobj_profile = $dobj_profile ? $dobj_profile : $dobj_list;
		  
		 
		  $result['data']['dobj_config']['root']   = $meta_dobj['dopath'];
		  $result['data']['dobj_config']['folder'] = $meta['collection'];
		  $result['data']['dobj_config']['files']  = $dobj_profile;
		  
		}else{
		  
		  $result['data']['dobj_config']['files']  = $meta_dobj;
		
		}
		
		
		// final
		$result['action'] = true;
		$result['data']['meta_list']   = $list ;
		$result['data']['meta_source'] = $source;
		
		$result['data']['form_mode']   = $meta['zong'];
		$result['data']['edit_mode']   = $Mode;
		
		//$result['session']['METACOLLECTION']  = json_decode($collection_meta,true);
		//$result['session']['DOBJCOLLECTION']  = $collection_dobj;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Built : Get Task Target Element
	// [input] : DataNo  :  \w\d+;  // metadata system_id
	public function ADMeta_Get_Item_Data( $DataNo=0){
		
	  $result_key = parent::Initial_Result('meta');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 取得原始資料
		$source = array();
		$DB_SOURCE	= $this->DBLink->prepare( SQL_AdMeta::GET_SOURCE_META($meta['zong']));
		$DB_SOURCE->bindParam(':id'   , $meta['identifier'] );	
		if( !$DB_SOURCE->execute() || !$source = $DB_SOURCE->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		if(count($source)){
		  foreach($source as $mfield => $mvalue){
			$element['META-'.$mfield] = $mvalue;  
		  }
		}
		
		// 處理關聯資料
		$dobj_config = json_decode($meta['dobj_json'],true);
		$refer_config = json_decode($meta['refer_json'],true);
		
		// final
		$result['action'] = true;
		$result['data']['source'] = $element;
		$result['data']['dobj']   = $dobj_config;
		$result['data']['refer']  = $refer_config;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result; 	 
	}
	
	
	//-- Admin Built : Save Task Element
	// [input] : DataNo  :  \w\d+;  system_id
	// [input] : DataModify   :   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	// [input] : CollecttionMeta : from session
	public function ADMeta_Save_Edit_Data( $DataNo='' , $DataModify='' , $CollecttionMeta=array()){
	  
	  $result_key = parent::Initial_Result('save');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $data_modify = json_decode(base64_decode(str_replace('*','/',rawurldecode($DataModify))),true); 
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 取得原始資料
		$source = array();
		$DB_SOURCE	= $this->DBLink->prepare( SQL_AdMeta::GET_SOURCE_META($meta['zong']));
		$DB_SOURCE->bindParam(':id'   , $meta['identifier'] );	
		if( !$DB_SOURCE->execute() || !$source = $DB_SOURCE->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 檢查更新欄位是否合法
		$meta_update = [];
		foreach($data_modify  as $mf => $mv){
		  $meta_field = str_replace('META-','',$mf);
		  if(isset($source[$meta_field]) && $source[$meta_field]!=$mv ){
			$meta_update[$meta_field] = $mv; 
		  }	
		}
		
		if(!count($meta_update)){
		  throw new Exception('_SYSTEM_ERROR_DB_UPDATE_NULL');	
		}
		
		// 補充系統欄位
		$meta_update['_userupdate'] = $this->USER->UserID;
		
		// 執行更新
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_SOURCE_META(array_keys($meta_update),$meta['zong']));
		$DB_SAVE->bindValue(':id'    , $meta['identifier']);
		foreach($meta_update as $uf=>$uv){
		  $DB_SAVE->bindValue(':'.$uf , $uv);
		}
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		// final 
		$result['data']   = $meta['system_id'];
    	$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Built : Save Task Element
	// [input] : DataNo  :  \w\d+;  system_id
	// [input] : DataModify   :   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	// [input] : CollecttionMeta : from session
	public function ADMeta_Process_Meta_Update( $DataNo=''){
	  
	  $result_key = parent::Initial_Result('renew');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $meet = array( 'OA'=>'定期大會' , 'IA'=>'成立大會' , 'EA'=>'臨時大會', 'AA'=>'大會');
	
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 取得原始資料
		$source = array();
		$DB_SOURCE	= $this->DBLink->prepare( SQL_AdMeta::GET_SOURCE_META($meta['zong']));
		$DB_SOURCE->bindParam(':id'   , $meta['identifier'] );	
		if( !$DB_SOURCE->execute() || !$source = $DB_SOURCE->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		//計算遮頁資料
		$meta_dobj    = json_decode($meta['dobj_json'],true);
	    $is_mask_flag = (isset($meta_dobj['domask'])&&count($meta_dobj['domask'])) ? 1 : 0;
				
		$search_conf = [];
		
		switch($meta['zong']){
		  
		  case "檔案":
            
			$search_conf['zong']   = '檔案';
			$search_conf['data_type']   = $meta['data_type'];
			$search_conf['collection']   = $meta['collection'];
			$search_conf['collection_name'] = $meta['collection'];
			$search_conf['identifier']   = $meta['identifier'];
			$search_conf['serial'] = '檔案/'.$source['CategoryLevel'];
			$search_conf['category_level'] = $source['CategoryLevel'];
			
			// 確認日期
			$meta_date = array();
			if(strtotime($source['DateStart'])){
			  $meta_date[] = $source['DateStart'];
			}
			if($source['DateEnd'] !='0000-00-00' && strtotime($source['DateEnd'])){
			  $meta_date[] = $source['DateEnd'];  	
			}
			$search_conf['date_string'] = count($meta_date) ? join(' ~ ',$meta_date) : '0000-00-00';
			$parsedate = self::paser_date_array($meta_date);
			$search_conf['date_start'] = $parsedate['ds'];
			$search_conf['date_end']   = $parsedate['de'];
			$search_conf['yearrange']  = $parsedate['years'];
			
			$search_conf['abstract']  = $source['Abstract'];
			$search_conf['abstract_mask']  = $source['AbstractMask'];
			$search_conf['docno']    = $source['DocNo'];
			$search_conf['reference']    = $source['Reference'];
			
			$search_conf['main_mamber'] = $source['Member'];
			$search_conf['pageinfo']  = '共'.$source['PageCount'].'頁';    
			
			$member_array = [];
			$member_array[] = $source['Member'];
			$member_array[] = $source['MemberOther'];
			$search_conf['list_member']   = self::paser_person($member_array);
			
			$organ_array = [];
			$organ_array[] = $source['Organ'];
			$organ_array[] = $source['OrganOther'];
			$search_conf['list_organ']    = self::paser_organ($organ_array);  
			
			$search_conf['list_location'] = self::paser_postquery([$source['Location']]);
			$search_conf['list_subject']  = self::paser_postquery([$source['Subject']]);
			
			$search_conf['_flag_secret']  = intval($source['_flag_secret']);
			$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
			$search_conf['_flag_mask']    = $is_mask_flag;
			$search_conf['_flag_open']    = intval($source['_flag_open']);
			$search_conf['_flag_update']  = date('YmdHis');
			$search_conf['_flag_view']    = $source['_view'];
			
			$lockmode   = $source['Secret'];
			$auditint   = $source['_flag_privacy'];
			$open       = $source['_flag_open'];
			$view		= $source['_view'];		  
			
			break;
			
		  case "議事錄": case "公報":
            
			$search_conf['data_type']   = $meta['data_type'];
			$search_conf['zong']   		= $meta['zong'];
			$search_conf['collection']  = $meta['collection'];
			$search_conf['identifier']  = $meta['identifier'];
			
			$search_conf['serial'] = '會議/'.$source['MeetingLevel'];			  
			$search_conf['category_level']  = $source['CategoryLevel'];
			$search_conf['collection_name'] = $source['BookName'];
			if( trim($source['StageNum']) && $source['StageNum']!='00'){
			  $search_conf['collection_name'].=$source['StageNum'];  		
			}
			$search_conf['meeting_level']   = $source['MeetingLevel'];
			
			// 確認日期
			$meta_date = array();
			if(strtotime($source['DateStart'])){
			  $meta_date[] = $source['DateStart'];
			}
			if($source['DateEnd'] !='0000-00-00' && strtotime($source['DateEnd'])){
			  $meta_date[] = $source['DateEnd'];  	
			}
			$search_conf['date_string'] = count($meta_date) ? join(' ~ ',$meta_date) : '0000-00-00';
			
			$parsedate = self::paser_date_array($meta_date);
			
			$search_conf['date_start'] = $parsedate['ds'];
			$search_conf['date_end'] = $parsedate['de'];
			$search_conf['yearrange'] = $parsedate['years'];
			
			$search_conf['abstract']  = $source['Abstract'];
			$search_conf['fulltext']  = $source['FullTexts'];
			$search_conf['chairman']  = $source['Chairman'];
			$search_conf['main_mamber'] = $source['Member'];
			
			$search_conf['docno']    = $source['DocNo'];    
			$search_conf['reference'] = $source['Reference'];
			$search_conf['pageinfo']  = 'P.'.$source['PageStart'].' ~ '.'P.'.$source['PageEnd'];    
			
			$member_array = [];
			$member_array[] = $source['Chairman'];
			$member_array[] = $source['Member'];
			$member_array[] = $source['MemberOther'];
			$member_array[] = $source['PetitionMen'];
			$search_conf['list_member']   = self::paser_person($member_array);
			
			$organ_array = [];
			$organ_array[] = $source['Organ'];
			$organ_array[] = $source['PetitionOrgan'];
			$search_conf['list_organ']    = self::paser_organ($organ_array);  
			
			
			$search_conf['_flag_secret']  = intval($source['_flag_secret']);
			$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
			$search_conf['_flag_open']    = intval($source['_flag_open']);
			$search_conf['_flag_mask']    = $is_mask_flag;
			$search_conf['_flag_update']  = date('YmdHis');;
			$search_conf['_flag_view']    = $source['_view'];
			
			$lockmode   = '普通';
			$auditint   = $source['_flag_privacy'];
			$open       = $source['_flag_open'];
			$view		= $source['_view'];	
			
			break;	
		  
		  case '議事影音':
		    
			$search_conf['data_type']   = $meta['data_type'];
			$search_conf['zong']   		= $meta['zong'];
			$search_conf['collection']  = $meta['collection'];
			$search_conf['identifier']  = $meta['identifier'];
			
			$search_conf['serial'] = '會議/臺灣省議會/'.'第'.self::getChineseNumber(intval($source['record_period'])).'屆'.'/'.'第'.self::getChineseNumber(intval($source['record_conf_typeno'])).'次'.$meet[$source['record_conf_type']];
			$search_conf['collection_name'] = '臺灣省議會';
			$search_conf['collection_name'].= '第'.self::getChineseNumber(intval($source['record_period'])).'屆';
			$search_conf['collection_name'].= '第'.self::getChineseNumber(intval($source['record_conf_typeno'])).'次';
			$search_conf['collection_name'].= $meet[$source['record_conf_type']];
			$search_conf['meeting_level'] = mb_substr($search_conf['serial'],3);
			
			
			// 確認日期
			$meta_date = array();
			if(strtotime($source['record_date'])){
			  $meta_date[] = $source['record_date'];
			}
			$search_conf['date_string'] = count($meta_date) ? join(' ~ ',$meta_date) : '0000-00-00';
			$parsedate = self::paser_date_array($meta_date);
			
			$search_conf['date_start'] = $parsedate['ds'];
			$search_conf['date_end'] = $parsedate['de'];
			$search_conf['yearrange'] = $parsedate['years'];
			
			$search_conf['abstract']  = $source['record_reason'];
			$search_conf['chairman']  = $source['record_chairman'];
			$search_conf['main_mamber'] = $source['record_members'];
			
			$search_conf['reference'] = $source['record_remark'];
			$search_conf['pageinfo']  = $source['record_stime'].' ~ '.$source['record_etime'];    
			
			$member_array = [];
			$member_array[] = $source['record_chairman'];
			$member_array[] = $source['record_members'];
			$search_conf['list_member']   = self::paser_person($member_array);
			
			$organ_array = [];
			$organ_array[] = $source['record_organ'];
			$search_conf['list_organ']    = self::paser_organ($organ_array);  
			$search_conf['list_location'] = self::paser_postquery([$source['record_place']]);
			$search_conf['list_subject']  = self::paser_postquery([$source['record_keyword']]);
			
			$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
			$search_conf['_flag_open']    = intval($source['_flag_open']);
			$search_conf['_flag_mask']    = $is_mask_flag;
			$search_conf['_flag_update']  = date('YmdHis');
			$search_conf['_flag_view']    = $source['_view'];
			
			$lockmode   = '普通';
			$auditint   = $source['_flag_privacy'];
			$open       = $source['_flag_open'];
			$view		= $source['_view'];	
		    break;	
			
          case '活動照片': 
			
            // get member reference 
			$db_mbr = $this->DBLink->prepare("SELECT mbr_name FROM source_member WHERE 1;");
			$db_mbr->execute();
			$member_list = array();
			while($member = $db_mbr->fetch(PDO::FETCH_ASSOC)){
			  $member_list[] = $member['mbr_name'];    
			}            
  			
			$search_conf['data_type']   = $meta['data_type'];
			$search_conf['zong']   		= $meta['zong'];
			$search_conf['collection']  = $meta['collection'];
			$search_conf['identifier']  = $meta['identifier'];
			
			// 確認日期
			$meta_date = array();
			if(strtotime($source['DateStart'])){
			  $meta_date[] = $source['DateStart'];
			}
			if($source['DateEnd'] !='0000-00-00' && strtotime($source['DateEnd'])){
			  $meta_date[] = $source['DateEnd'];  	
			}
			$search_conf['date_string'] = count($meta_date) ? join(' ~ ',$meta_date) : '0000-00-00';
			
			$parsedate = self::paser_date_array($meta_date);
			
			$search_conf['date_start'] = $parsedate['ds'];
			$search_conf['date_end'] = $parsedate['de'];
			$search_conf['yearrange'] = $parsedate['years'];
			
			$search_conf['collection_name']  = $source['Subject'];
			$search_conf['abstract']  = $source['Descrip'];
			
			$year = substr($source['DateStart'],0,4);
			if( intval($year) > 1900 && intval($year) <= date('Y') ){
			  $search_conf['serial'] = '活動照片/'.$year.' 民國'.($year-1911).'年'; 	
			}else{
			  $search_conf['serial'] = '活動照片/none 未知日期'; 	
			}
			
			$search_conf['pageinfo']  = $source['PhotoNo'];    
			
			$search_conf['list_location']  = self::paser_postquery([$source['PhotoLocation']]);
			
			$member = array();
			foreach($member_list as $mbr){
			  if(preg_match('/'.$mbr.'/u',$source['Descrip'])){
				$member[] = $mbr;  
			  }
			}
			$search_conf['main_mamber'] = join(';',$member);
			$search_conf['list_member'] = $member;
			
			$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
			$search_conf['_flag_open']    = intval($source['_flag_open']);
			$search_conf['_flag_mask']    = $is_mask_flag;
			$search_conf['_flag_update']  = date('YmdHis');;
			$search_conf['_flag_view']    = $source['_view'];
			
			$lockmode   = '普通';
			$auditint   = $source['_flag_privacy'];
			$open       = $source['_flag_open'];
			$view		= $source['_view'];	
			
			break;		  
			
		  case '議員傳記':
		    
			// 計算紀錄
			$jobs = array();
			$serial = array();
			
			$search_conf['data_type']   = $meta['data_type'];
			$search_conf['zong']   		= $meta['zong'];
			$search_conf['collection']  = $meta['collection'];
			$search_conf['identifier']  = $meta['identifier'];
			
			$search_conf['meeting_level']   = $source['mbr_offer'];
			
			
			if(preg_match('/臺灣省參議會/u',$search_conf['meeting_level'])){
			  $jobs[]	= '臺灣省參議會議員'; 
			}
			
			if(preg_match('/臺灣省臨時省議會第[一二三四五六七八九十、]+屆/u',$search_conf['meeting_level'],$match)){
			  preg_match_all('/([一二三四五六七八九十]+)/u',$match[0],$stage,PREG_SET_ORDER);	
			  foreach($stage as $i =>$set){
				$jobs[]	=  '臺灣省臨時省議會/第'.$set[0].'屆議員';
			  }
			}
			
			if(preg_match('/臺灣省議會第[一二三四五六七八九十、]+屆/u',$search_conf['meeting_level'],$match)){
			  preg_match_all('/([一二三四五六七八九十]+)/u',$match[0],$stage,PREG_SET_ORDER);	
			  foreach($stage as $i =>$set){
				$jobs[]	=  '臺灣省議會/第'.$set[0].'屆議員';
			  }
			}
			
			foreach($jobs as $tpa){
			  $serial[] =  '議員傳記/'.$tpa;
			}
			
			$search_conf['serial'] = join(';',$serial);
			$search_conf['date_string'] = $source['mbr_time'];
			$search_conf['abstract']  =  $source['mbr_history'];
			$search_conf['reference'] = $source['mbr_refer'];
			
			$search_conf['_flag_open']    = intval($source['_flag_open']);
			$search_conf['_flag_update']  = date('YmdHis');
			$search_conf['_flag_view']    = $source['_view'];
			
			$lockmode   = '普通';
			$auditint   = $source['_flag_privacy'];
			$open       = $source['_flag_open'];
			$view		= $source['_view'];	
			
			break;
		
		}
		
		$DB_UPD = $this->DBLink->prepare(SQL_AdMeta::UPDATE_METADATA_DATA(array('source_json','search_json','_lockmode','_auditint','_open','_view','_index')));
	    $DB_UPD->bindValue(':_lockmode' , $lockmode);
		$DB_UPD->bindValue(':_auditint'	, $auditint);
		$DB_UPD->bindValue(':_open'	 	, $open );
		$DB_UPD->bindValue(':_view'	 	, $view);
		$DB_UPD->bindValue(':_index'	,0);
		$DB_UPD->bindValue(':source_json',json_encode($source,JSON_UNESCAPED_UNICODE));
		$DB_UPD->bindValue(':search_json',json_encode($search_conf,JSON_UNESCAPED_UNICODE));
		$DB_UPD->bindValue(':sid', $meta['system_id']);
		
		if(!$DB_UPD->execute()){
		  throw new Exception('新增資料更新失敗'); 	
		}
		
		$hosts = [
			'127.0.0.1:9200',         // IP + Port
			//'192.168.1.2',              // Just IP
			//'mydomain.server.com:9201', // Domain + Port
			//'mydomain2.server.com',     // Just Domain
			//'https://localhost',        // SSL to localhost
			//'https://192.168.1.3:9200'  // SSL to IP + Port
		];
		 
		$defaultHandler = Elasticsearch\ClientBuilder::defaultHandler();
		$singleHandler  = Elasticsearch\ClientBuilder::singleHandler();
		$multiHandler   = Elasticsearch\ClientBuilder::multiHandler();
		//$customHandler  = new MyCustomHandler();
		  
		  
		$client = Elasticsearch\ClientBuilder::create()
					  ->setHandler($defaultHandler)
					  ->setHosts($hosts)
					  ->setRetries(0)
					  ->build();
		  
		
		// 更新索引
		$params = [
			'index' => 'ndap',
			'type' => 'search',
			'id' => $meta['system_id'],
		];
		  
		try {
		  if($meta['_keep']){
			$params['body']=$search_conf;
			$response = $client->index($params);
		  }else{
			$response = $client->delete($params);
		  }
	      //var_dump($response);
		} catch (Elasticsearch\Common\Exceptions\ElasticsearchException $e) {
		  $logs_message = date("Y-m-d H:i:s").' [ELASTIC]'.$e->getMessage().'. '.PHP_EOL;
		  file_put_contents('logs.txt',$logs_message,FILE_APPEND);
		  //echo $e->getMessage().PHP_EOL;
		}  
		
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		if(count($source)){
		  foreach($source as $mfield => $mvalue){
			$element['META-'.$mfield] = $mvalue;  
		  }
		}
		
		// 處理關聯資料
		$dobj_config = json_decode($meta['dobj_json'],true);
		$refer_config = json_decode($meta['refer_json'],true);
		
		// final
		$result['action'] = true;
		$result['data']['source'] = $element;
		$result['data']['refer']  = $refer_config;
		
    	$result['action'] = true;
    	
		
		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	
	
	//-- Admin Built : Save Task Element
	// [input] : DataNo  :  \w\d+;  system_id
	// [input] : MediaSegments:   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 影片標記
	public function ADMeta_Save_Media_Tags( $DataNo='' , $MediaSegments=''  ){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $media_tags = json_decode(base64_decode(str_replace('*','/',rawurldecode($MediaSegments))),true); 
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// decode dobj_json
		$dobj_config = json_decode($meta['dobj_json'],true);
		
		// paser media tag
		foreach($media_tags as &$segment){
		
		  if(isset($segment['sthumb'])){
			$thumb_path = _SYSTEM_DIGITAL_FILE_PATH.$dobj_config['dopath'].'thumb/'.$meta['collection'].'/'.$segment['file'].'-'.str_replace(':','',$segment['pointer']['stime']).'.jpg';
			$encodedData = str_replace(' ','+',$segment['sthumb']);
            file_put_contents($thumb_path,base64_decode($encodedData));
		    unset($segment['sthumb']);
		  }
		  
		  if(isset($segment['ethumb'])){
			$thumb_path = _SYSTEM_DIGITAL_FILE_PATH.$dobj_config['dopath'].'thumb/'.$meta['collection'].'/'.$segment['file'].'-'.str_replace(':','',$segment['pointer']['etime']).'.jpg';
			$encodedData = str_replace(' ','+',$segment['ethumb']);
			file_put_contents($thumb_path,base64_decode($encodedData));
		    unset($segment['ethumb']);
		  }
		}
		
		$dobj_config['count'] = count($media_tags);
		$dobj_config['position'] = $media_tags;
		$dobj_config['logs'][date('Y-m-d H:i:s')] = $this->USER->UserID.' updated.'; 
		
		
		// 執行更新
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_METADATA_DATA(array('dobj_json')));
		$DB_SAVE->bindValue(':sid'    	 , $meta['system_id']);
		$DB_SAVE->bindValue(':dobj_json' , json_encode($dobj_config));
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
  
		// final 
		$result['data']   = $dobj_config;
    	$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	
	
	
	
	
	
	//-- Admin Built : Save Task Element
	// [input] : taskid  :  \w\d+;
	public function ADBuilt_Newa_Item_Data( $TaskId='', $CollecttionMeta=array() , $DefaultMeta=''){
	  
	  $result_key = parent::Initial_Result('newa');
	  $result  = &$this->ModelResult[$result_key];
	  $default_meta = json_decode(base64_decode(str_replace('*','/',rawurldecode($DefaultMeta))),true); 
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^TSK\d+$/',$TaskId)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 全宗資料
		if(!isset($CollecttionMeta) || !isset($CollecttionMeta['store_no']) ){
          throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$item_id_head = $CollecttionMeta['store_no'];
		
		// 取得任務資料
		$elements = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_TASK_ELEMENTS());
		$DB_GET->bindParam(':taskid'   , $TaskId );	
		if( !$DB_GET->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 計算典藏號
		$item_id_queue = array();
		while($item = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  $item_id_queue[] = intval(substr($item['itemid'],-3,3));
		}
		$new_item_no = count($item_id_queue) ? max($item_id_queue)+1 : 1;
		$new_item_id = $item_id_head.'-'.str_pad($new_item_no,3,'0',STR_PAD_LEFT);
		
		// 執行新增
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::INSERT_ELEMENT_DATA());
		$DB_SAVE->bindValue(':taskid' , $TaskId);
		$DB_SAVE->bindValue(':itemid' , $new_item_id);
		$DB_SAVE->bindValue(':item_title' , '新增資料');
		$DB_SAVE->bindValue(':meta_json' , json_encode(array("id"=>$new_item_id)));
		$DB_SAVE->bindValue(':page_num_start' ,'');
		$DB_SAVE->bindValue(':page_num_end' , '');
		$DB_SAVE->bindValue(':page_file_start' , isset($default_meta['page_file_start']) && $default_meta['page_file_start'] ? $default_meta['page_file_start'] : '');
		
		$DB_SAVE->bindValue(':editor' , $this->USER->UserID);
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		// final 
		$result['data'] = $new_item_id;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	//-- Admin Built : Save Task Element
	// [input] : taskid  :  \w\d+;
	// [input] : itemid  :  \w\d+;
	public function ADBuilt_Done_Item_Data( $TaskId='',$ItemId=''){
	  
	  $result_key = parent::Initial_Result('done');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^TSK\d+$/',$TaskId)  ||  !preg_match('/^[\w\d\-]+$/',$ItemId)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		$task_meta['_estatus'] = '_finish';
		
		// 執行更新
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_ELEMENT_DATA(array('_estatus')));
		$DB_SAVE->bindValue(':taskid' , $TaskId);
		$DB_SAVE->bindValue(':itemid' , $ItemId);
		$DB_SAVE->bindValue(':_estatus', '_finish');
		
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 更新任務資料
		$DB_UPD= $this->DBLink->prepare(SQL_AdMeta::UPDATE_TASK_STATUS());
		$DB_UPD->bindValue(':taskid' , $TaskId);
		$DB_UPD->bindValue(':status' , '_EDITING');
		
		
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		// final 
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Built : Dele Task Element
	// [input] : taskid  :  \w\d+;
	// [input] : itemid  :  \w\d+;
	public function ADBuilt_Dele_Item_Data( $TaskId='',$ItemId=''){
	  
	  $result_key = parent::Initial_Result('done');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^TSK\d+$/',$TaskId)  ||  !preg_match('/^[\w\d\-]+$/',$ItemId)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		$task_meta['_keep'] = 0;
		
		// 執行更新
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_ELEMENT_DATA(array('_keep')));
		$DB_SAVE->bindValue(':taskid' , $TaskId);
		$DB_SAVE->bindValue(':itemid' , $ItemId);
		$DB_SAVE->bindValue(':_keep', 0);
		
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 更新任務資料
		$DB_UPD= $this->DBLink->prepare(SQL_AdMeta::UPDATE_TASK_STATUS());
		$DB_UPD->bindValue(':taskid' , $TaskId);
		$DB_UPD->bindValue(':status' , '_EDITING');
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		// final 
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	//-- Admin Built : Finish Task Work
	// [input] : taskid  :  \w\d+;
	public function ADBuilt_Finish_Work_Task( $TaskId=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^TSK\d+$/',$TaskId) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 查詢任務
		$task = array();
		$DB_TASK= $this->DBLink->prepare(SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_TASK->bindValue(':id' , $TaskId);
		if( !$DB_TASK->execute() || !$task=$DB_TASK->fetch(PDO::FETCH_ASSOC) ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		if( $task['handler'] != $this->USER->UserID){
		  throw new Exception('_BUILT_TASK_HANDLER_FAIL');	
		}
		
		// 更新所有任務案件
		$DB_UPD= $this->DBLink->prepare(SQL_AdMeta::FINISH_TASK_ELEMENTS());
		$DB_UPD->bindValue(':taskid' , $TaskId);
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 更新任務資料
		$DB_UPD= $this->DBLink->prepare(SQL_AdMeta::UPDATE_TASK_STATUS());
		$DB_UPD->bindValue(':taskid' , $TaskId);
		$DB_UPD->bindValue(':status' , '_FINISH');
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		// final 
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Built : Return Task Work
	// [input] : taskid  :  \w\d+;
	public function ADBuilt_Return_Work_Task( $TaskId=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^TSK\d+$/',$TaskId) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 確定腳色權限
		if( !isset($this->USER->PermissionNow['group_roles']['R00']) && 
		   (!isset($this->USER->PermissionNow['group_roles']['R02']) || $this->USER->PermissionNow['group_roles']['R02'] <= 1 )){
		   throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');	
		}
		
		// 查詢任務
		$task = array();
		$DB_TASK= $this->DBLink->prepare(SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_TASK->bindValue(':id' , $TaskId);
		if( !$DB_TASK->execute() || !$task=$DB_TASK->fetch(PDO::FETCH_ASSOC) ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 更新任務資料
		$DB_UPD= $this->DBLink->prepare(SQL_AdMeta::UPDATE_TASK_STATUS());
		$DB_UPD->bindValue(':taskid' , $TaskId);
		$DB_UPD->bindValue(':status' , '_EDITING');
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		// final 
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Built : Checked Task Work
	// [input] : taskid  :  \w\d+;
	public function ADBuilt_Checked_Work_Task( $TaskId=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^TSK\d+$/',$TaskId) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 確定腳色權限
		if( !isset($this->USER->PermissionNow['group_roles']['R00']) && 
		   (!isset($this->USER->PermissionNow['group_roles']['R02']) || $this->USER->PermissionNow['group_roles']['R02'] <= 1 )){
		   throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');	
		}
		
		// 查詢任務
		$task = array();
		$DB_TASK= $this->DBLink->prepare(SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_TASK->bindValue(':id' , $TaskId);
		if( !$DB_TASK->execute() || !$task=$DB_TASK->fetch(PDO::FETCH_ASSOC) ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 更新任務資料
		$DB_UPD= $this->DBLink->prepare(SQL_AdMeta::UPDATE_TASK_STATUS());
		$DB_UPD->bindValue(':taskid' , $TaskId);
		$DB_UPD->bindValue(':status' , '_CHECKED');
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 執行logs
		/*
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::ADMIN_META_GET_LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':sid' , $meta_orl['system_id']);
		$DB_LOGS->bindValue(':orl' , serialize($meta_data));
		$DB_LOGS->bindValue(':new' , serialize($data_modify));
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		*/
		
		// final 
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Built : Download Select Tasks Elements
	// [input] : taskidstring  :  taskid;taskid;.... ;
	public function ADBuilt_Export_Work_Task($TaskIdString=''){
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d;]+$/',$TaskIdString)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		
		// 取得任務資料
		
		$targets  = explode(';',$TaskIdString);
		$exports  = array();
		$collection = array();
		
		foreach($targets as $data_id){
	      
		  $task = NULL;
		  $DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		  $DB_GET->bindParam(':id'   , $data_id );	
		  if( !$DB_GET->execute() || !$task = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		    throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		  }
		
		  // 確定腳色權限
		  if( $task['handler']==$this->USER->UserID ){
			
		  }elseif( isset($this->USER->PermissionNow['group_roles']['R00']) || 
		     (isset($this->USER->PermissionNow['group_roles']['R02']) && $this->USER->PermissionNow['group_roles']['R02'] > 1 )){
		  }else{
		    throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');		
		  }  
		  
		  $exports[] = $task['task_no'];
		  $collection[$task['task_no']] = array('id'=>$task['collection_id'],'name'=>$task['collection_name']);
		}
		
		if(!count($exports)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL'); 	
		}
		
		
		// 取得任務資料
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TASKS_ELEMENTS_EXPORT($exports));
		if( !$DB_GET->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$excel_records = array();
		while($tmp = $DB_GET->fetch(PDO::FETCH_ASSOC)){
          
		  $meta = json_decode($tmp['meta_json'],true);
		  		  
          $record = array();
		  $record[] = $collection[$tmp['taskid']]['id'];
		  $record[] = $tmp['itemid'];
		  $record[] = $tmp['page_file_start'];
		  $record[] = $tmp['page_file_end'];
		  $record[] = $collection[$tmp['taskid']]['name'];
		  $record[] = isset($meta['description']) ? $meta['description'] : '';
		  $record[] = isset($meta['from_date']) ? $meta['from_date'] : '';
		  $record[] = isset($meta['to_date']) ? $meta['to_date'] : '';
		  $record[] = isset($meta['per_name']) ? $meta['per_name'] : '';
		  $record[] = isset($meta['place_info']) ? $meta['place_info'] : '';
		  $record[] = isset($meta['key_word']) ? $meta['key_word'] : '';
		  $record[] = isset($meta['edit_note']) ? $meta['edit_note'] : '';
		  $record[] = $tmp['_editor'];
		  $record[] = $tmp['_update'];
		  $record[] = $tmp['_estatus'];
		  $excel_records[] = $record;  	
		}
		
		// final
		$result['action'] = true;
		$result['data']['excel'][] = $excel_records;
		$result['data']['fname'] = count($exports)==1 ? 'AHAS_MetaEditor_Export_'.$task['collection_id'].'_'.date('Ymd') : 'AHAS_MetaEditor_Export_'.date('Ymd');
		$result['data']['title'] = count($exports)==1 ? $task['collection_id'] : '匯出'.count($exports).'個任務';
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result; 	 
	}
	
	
	
	
	//-- Admin Meta - DigitalObject View Switch
	// [input] : DataNo    :  \w\d+  = DB.metadata.system_id;
	// [input] : DObjFileName       :  digital file name
	// [input] : DobjConfigString   :  urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	
	public function ADMeta_DObj_Conf_Save($DataNo='',$DObjFileName='',$DobjConfigString='' ){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
	    
		// 檢查資料序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo) || !$DObjFileName || !$DobjConfigString ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$dobj_objects = json_decode(base64_decode(str_replace('*','/',rawurldecode($DobjConfigString))),true);  
		
		if(!count($dobj_objects)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$meta_doconf = json_decode($meta['dobj_json'],true);  // from convas objects
		
		
		$canvas_store  = [];
		$config_setting= array();
		$canvas_object = $dobj_objects['objects'][0];
		
		if(isset($canvas_object['objects'])){
		  foreach($canvas_object['objects'] as $object){
			  switch($object['type']){
				case 'image':  
				  $config_setting['base_sx'] = $object['scaleX'] ? $object['scaleX'] : 1;
				  $config_setting['base_sy'] = $object['scaleY'] ? $object['scaleY'] : 1;
				  $config_setting['base_h'] = intval($object['width']*$config_setting['base_sx']);
				  $config_setting['base_w'] = intval($object['height']*$config_setting['base_sy']);
				  $config_setting['base_l'] = $object['left'];
				  $config_setting['base_t'] = $object['top'];
				  break;
				  
				default:
				  $canvas_store[] = [
					'type'	=> 'mask',
					'shap'  => $object['type'],
					'width'	=> round(intval($object['width']*$object['scaleX'])/$config_setting['base_sx'], 2),
					'height'=> round(intval($object['height']*$object['scaleY'])/$config_setting['base_sy'], 2),			
					'left' 	=> round(($object['left']- $config_setting['base_l'])/$config_setting['base_sx'], 2),
					'top'  	=> round(($object['top'] - $config_setting['base_t'])/$config_setting['base_sx'], 2),
				  ];
				   break;  
			  }
		  }	
		}
		
		
		if(!isset($meta_doconf['domask'])){
		  $meta_doconf['domask'] = array();	
		}
		if(!isset($meta_doconf['domask'][$DObjFileName])){
		  $meta_doconf['domask'][$DObjFileName] = [];
		}
		
		if(count($canvas_store)){
		  $meta_doconf['domask'][$DObjFileName]['mode'] = 'edit';
		  $meta_doconf['domask'][$DObjFileName]['conf'] = $canvas_store;
		  $meta_doconf['domask'][$DObjFileName]['creater'] = $this->USER->UserID;
		  $meta_doconf['domask'][$DObjFileName]['time'] = date('Y-m-d H:i:s');
		}else{
		  if(isset($meta_doconf['domask'][$DObjFileName]['mode']) 
		     && $meta_doconf['domask'][$DObjFileName]['mode']=='edit'){
			   unset($meta_doconf['domask'][$DObjFileName]);    
		  }
		}
		
		$meta_doconf['logs'][date('Y-m-d H:i:s')] = " edited by ".$this->USER->UserID;
		
		$DB_UPD	= $this->DBLink->prepare( SQL_AdMeta::UPDATE_METADATA_DATA(array('dobj_json')));
		$DB_UPD->bindParam(':sid'   , $meta['system_id'] , PDO::PARAM_INT);	
		$DB_UPD->bindValue(':dobj_json' , json_encode($meta_doconf));
	    if( !$DB_UPD->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// final 
		$result['data']   = 1;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	  
	}
	
	
	//-- Admin Meta Hide/Show Image
	// [input] : DataNo  :  \d+;
	// [input] : Switch => 0/1
	public function ADMeta_DObj_Display_Switch($DataNo , $PageName , $HideFlag){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		$do_display = intval($HideFlag) ? 1 : 0;
		
		
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $DataNo );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$meta_doconf = json_decode($meta['dobj_json'],true);  // from convas objects
		
		if(!isset($meta_doconf['domask'][$PageName])){
		  $meta_doconf['domask'][$PageName] = [];	
		}
		
		$meta_doconf['domask'][$PageName]['display'] = $do_display;
		$meta_doconf['logs'][date('Y-m-d H:i:s')] = "display ".$do_display." by ".$this->USER->UserID;
		
		$DB_UPD	= $this->DBLink->prepare( SQL_AdMeta::UPDATE_METADATA_DATA(array('dobj_json')));
		$DB_UPD->bindParam(':sid'   , $meta['system_id'] , PDO::PARAM_INT);	
		$DB_UPD->bindValue(':dobj_json' , json_encode($meta_doconf));
	    if( !$DB_UPD->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// final 
		$result['data']   = $PageName;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	
	
	/* [ Upload Method Set] */
	
	
	
	
	//-- Initial Upload Task 
	// [input] : UploadData : urlencode(json_pass())  = array(folder , creater , classlv , list=>array(name size type lastmdf=timestemp));
	// [input] : FILES : [array] - System _FILES Array;
	
	// tip: UploadData['folder']  == meta.identifier
	
	public function ADMeta_Upload_Task_Initial( $UploadData=''){
	  
	  $result_key = parent::Initial_Result('upload');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $upload_data = json_decode(rawurldecode($UploadData),true);   
	  
	  try{
		  
		if(!$upload_data || !count($upload_data)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}  
		
		
		//查詢檔案資料
		$meta = array();
		$DB_GET	= $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdMeta::ADMIN_META_GET_META_VIEW_DATA()));
		$DB_GET->bindParam(':id'      , $upload_data['folder'] , PDO::PARAM_STR);
		if( !$DB_GET->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		if( !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');	
		}
		
		$upload_time_flag = date('YmdHis');  // 用來識別task_upload file
		
		// check & create object folder
		$digital_object_folder = $meta['data_type'].'/'.$meta['identifier'].'/';
		if(!is_dir(_SYSTEM_FILE_PATH.$digital_object_folder.'upload/')){
		  mkdir(_SYSTEM_FILE_PATH.$digital_object_folder.'upload/',0777,true);	  
		} 		  
		
		// 快取使用者設定
		$result['session']['cache']['upload_folder_id']      = $upload_data['folder'];
		$result['session']['cache']['upload_folder_address'] = $digital_object_folder;
		
		// check exist file
		$checked  = array();
		$DB_Check  = $this->DBLink->prepare(SQL_AdMeta::CHECK_FILE_UPLOAD_LIST()); 
		if(count($upload_data['list'])){  
		  foreach($upload_data['list'] as $i=>$file){
			$checked[$i] = array();
			
			$hashkey = md5($file['name'].$file['size'].$file['lastmdf']);
			
			$DB_Check->bindValue(':hash',$hashkey);
			$DB_Check->execute();
			$chk = $DB_Check->fetchAll(PDO::FETCH_ASSOC);
			
			$checked[$i]['check']  = count($chk) ? 'double' : 'accept';
		  }  
		}
		
		// return folder 
		$result['data']['folder'] = $upload_data['folder'];
		$result['data']['tmflag'] = $upload_time_flag;
		$result['data']['check']  = $checked;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	
	//-- Upload digital object 
	// [input] : FolderId     : [str] metadata.identifter
	// [input] : TimeFlag     : [int] fuploadtimeflag; \d{14}
	// [input] : UploadMeta   : accnum:urlencode(base64encode(json_pass()))  = array(F=>V);
	// [input] : FILES : [array] - System _FILES Array;
	public function ADMeta_Upload_DObj( $FolderId='' , $TimeFlag='' , $UploadMeta='' , $FILES = array()){
	  
	  $result_key = parent::Initial_Result('upload');
	  $result  = &$this->ModelResult[$result_key];
	  
      // [name] => MyFile.jpg  / [type] => image/jpeg  /  [tmp_name] => /tmp/php/php6hst32 / [error] => UPLOAD_ERR_OK / [size] => 98174
	  // Allowed extentions.
      $allowedExts = array("jpg","tiff","png","gif","cr2","dng","tif","raf","mp4");
      
      // Get filename.
      $temp = explode(".", $FILES["file"]["name"]);

      // Get extension.
      $extension = end($temp);
      
	  // Validate uploaded files.
	  // Do not use $_FILES["file"]["type"] as it can be easily forged.
	  $finfo = finfo_open(FILEINFO_MIME_TYPE);
	  $mime  = finfo_file($finfo, $FILES["file"]["tmp_name"]);
	  $upload_data = json_decode(base64_decode(str_replace('*','/',$UploadMeta)),true);   
	  
	  try{
		
		// 檢查參數
		if(!preg_match('/^[\w\d\-]+$/',$FolderId)  ||  !preg_match('/^\d{14}$/',$TimeFlag)   ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$folder_id = $FolderId;
		$upload_flag = $TimeFlag;
		
     	
		if (!in_array(strtolower($extension), $allowedExts)) {
	      throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	    }	
		
        if( $FILES["file"]["error"] ){
          throw new Exception('_SYSTEM_UPLOAD_ERROR:'.$FILES["file"]["error"]);  
        }
		
		if(!isset($_SESSION[_SYSTEM_NAME_SHORT]['cache']['upload_folder_address'])){
		  throw new Exception('_SYSTEM_ERROR_ACCESS_DENIAL');	 
		}
		
		$save_folder = _SYSTEM_FILE_PATH.$_SESSION[_SYSTEM_NAME_SHORT]['cache']['upload_folder_address'].'upload/';
		
		
		//紀錄上傳檔案
		$hashkey = md5($FILES["file"]['name'].$FILES["file"]['size'].$FILES["file"]['lastmdf']);
		$DB_Regist = $this->DBLink->prepare(SQL_AdMeta::REGIST_FILE_UPLOAD_RECORD()); 
		$DB_Regist->bindValue(':utkid',0);
		$DB_Regist->bindValue(':folder',$folder_id);
		$DB_Regist->bindValue(':flag',$upload_flag);
		$DB_Regist->bindValue(':user',$this->USER->UserID);
		$DB_Regist->bindValue(':hash',$hashkey);
		$DB_Regist->bindValue(':creater',isset($upload_data['creater']) ? $upload_data['creater'] : '');
		$DB_Regist->bindValue(':store',$_SESSION[_SYSTEM_NAME_SHORT]['cache']['upload_folder_address']);
		$DB_Regist->bindValue(':name',$FILES["file"]['name']);
		$DB_Regist->bindValue(':size',$FILES["file"]['size']);
		$DB_Regist->bindValue(':mime',strtolower($FILES["file"]['type']));
		$DB_Regist->bindValue(':type',strtolower($extension));
		$DB_Regist->bindValue(':last',$FILES["file"]['lastmdf']);
		
		if(!$DB_Regist->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');	
		}
		$urno = $this->DBLink->lastInsertId();
		
		if(!isset($_SESSION[_SYSTEM_NAME_SHORT]['cache']['upload_folder_address'])){
		  throw new Exception('_SYSTEM_ERROR_ACCESS_DENIAL');	 
		}
		
		
		
		// 取得上傳資料
		move_uploaded_file($FILES["file"]["tmp_name"],$save_folder.str_pad($urno,8,'0',STR_PAD_LEFT).$hashkey );
		
		// 更新上傳紀錄
		$DB_Update = $this->DBLink->prepare(SQL_AdMeta::UPDATE_FILE_UPLOAD_UPLOADED()); 
		$DB_Update->bindValue(':urno',$urno );
		$DB_Update->execute();
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
      
	}
	
	//-- Finish Photo Upload Task 
	// [input] : FolderId     : [str] metadata.identifter
	// [input] : TimeFlag     : [int] fuploadtimeflag; \d{14}
	public function ADMeta_Upload_Task_Finish( $FolderId='' , $TimeFlag=''){
	  
	  $result_key = parent::Initial_Result('task');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		  
		// 檢查參數
		if(!preg_match('/^[\w\d\-]+$/',$FolderId)  ||  !preg_match('/^\d{14}$/',$TimeFlag)   ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$folder_id = $FolderId;
		$upload_flag = $TimeFlag;
		
		
		// 設定變數
		$meta  = $this->Metadata;
		if(!count($meta)){
		  throw new Exception('_SYSTEM_ERROR_ACCESS_PROCESS_FAIL');	
		}
		
		
		$dobject_config = json_decode($meta['dobj_json'],true);
		
		// 查詢新上傳檔案
		$DB_PHO = $this->DBLink->prepare(SQL_AdMeta::SELECT_UPLOAD_OBJECT_LIST());
		$DB_PHO->bindValue(':folder', $folder_id); 
		$DB_PHO->bindValue(':flag'	, $upload_flag); 
		$DB_PHO->bindValue(':user'	, $this->USER->UserID); 
		if(!$DB_PHO->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');	
		}
		
		$objs = $DB_PHO->fetchAll(PDO::FETCH_ASSOC);
		if(!count($objs)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');	
		}
		
		
		
		$new_do_conf = array();
		
		
		 /* [ 處理上傳程序 ] */
		foreach($objs as $obj){
		  
		  // 1. 重新命名
		  $meta_folder =  _SYSTEM_FILE_PATH.$obj['store'];
		  
		  // 依據原始資料夾檔案數量命名
		  if(!is_dir($meta_folder)){
		    throw new Exception('_SYSTEM_ERROR_ACCESS_DENIAL'); 
		  }
		  $elements = count( array_filter(array_slice(scandir($meta_folder),2) , function($file) use($meta_folder){ return is_file(  $meta_folder.$file  ); }));  // 原檔案數量
		  
		  
		  do{
            $elements++;
			$new_file_name =  _SYSTEM_NAME_SHORT.'_'.$folder_id.'_'.str_pad($elements,4,'0',STR_PAD_LEFT).'.'.$obj['type'];
		  }while( file_exists($meta_folder.$new_file_name) );
		  
		  
		  // 2. 歸檔
		  $DB_UPLOAD = $this->DBLink->prepare(SQL_AdMeta::UPDATE_FILE_UPLOAD_PROCESSED()); 
		  
		  if(!copy( $meta_folder.'upload/'.str_pad($obj['urno'],8,'0',STR_PAD_LEFT).$obj['hash'] , $meta_folder.$new_file_name )){
            
			// 註記歸檔失敗
			$DB_UPLOAD->bindValue(':logs',"FAIL : copy upload file fail");
		    $DB_UPLOAD->bindValue(':urno',$obj['urno']);
			$DB_UPLOAD->bindValue(':archive','');
			$DB_UPLOAD->execute();
			
		    continue;
		  }
		  
		  unlink($meta_folder.'upload/'.str_pad($obj['urno'],8,'0',STR_PAD_LEFT).$obj['hash']);
		  
		  // 3. 更新狀態
		  $DB_UPLOAD->bindValue(':logs',"SUCCESS : save as ".$new_file_name);
		  $DB_UPLOAD->bindValue(':urno',$obj['urno']);
		  $DB_UPLOAD->bindValue(':archive',date('Y-m-d H:i:s'));
		  $DB_UPLOAD->execute();
		  
		  // 4. 加入原始meta do config
		  //{"thcc-hp-dng00961-0001-i.jpg":{"name":"thcc-hp-dng00961-0001-i.jpg","addr":"photo\/dng00961\/thcc-hp-dng00961-0001-i.jpg","hash":"f72ecf28","view":1,"order":0,"index":0,"exist":1,"logs":["20160911_10:34:51 inserted."]}}
		  $dobj_conf[$new_file_name] = array(
		   'name'=>$new_file_name,
		   'addr'=>$obj['store'].$new_file_name,
		   'hash'=>substr(md5($meta_folder.$new_file_name.time()),(rand(0,3)*8),8),
		   'view'=>1,
		   'order'=>99,
		   'index'=>0,
		   'exist'=>1,
		   'logs'=>array(date('Ymd_H:i:s').' '.$this->USER->UserID.' uploaded.')
		  );
		  
		  $new_do_conf[$new_file_name] = $dobj_conf[$new_file_name];
		  
		}
		
		// 5. 更新原始meta
		$DB_UPD	= $this->DBLink->prepare( SQL_AdMeta::ADMIN_META_UPDATE_META_DATA(array('dobj_json')));
		$DB_UPD->bindParam(':sid'   , $meta['system_id'] , PDO::PARAM_INT);	
		$DB_UPD->bindValue(':dobj_json' , json_encode($dobj_conf));
		$DB_UPD->execute();
		
		// 放回全域變數
		$this->Metadata['dobj_json'] = json_encode($dobj_conf);
		
		
		// 開啟匯入程序 - 外部處理sample 
		//exec(_SYSTEM_PHP_ROOT._SYSTEM_ROOT_PATH.'systemJob/Job_Import_Upload_Files.php');  // 做完才結束
		//pclose(popen("start /b "._SYSTEM_PHP_ROOT._SYSTEM_ROOT_PATH.'systemJob/Job_Import_Upload_Files.php '.$task_id,"r"));  // 可以放著不管
		$result['data']    = $new_do_conf;
		
		$result['action'] = true;
		
		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	
	/* SAMPLE */
	
	
	
	
	//-- Admin Post Create New Post 
	// [input] : DataCreate  :   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	
	public function ADPost_Newa_Post_Data($DataCreate='' ){
	  
	  $result_key = parent::Initial_Result('save');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $data_newa   = json_decode(base64_decode(rawurldecode($DataCreate)),true);
	  
	  try{  
		
		// 檢查參數
		if(  !isset($data_newa['post_to']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if(  !isset($data_newa['post_type']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if(  !isset($data_newa['post_from']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if(  !isset($data_newa['post_level']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if(  !isset($data_newa['post_title']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		
		$date_start = isset($data_newa['post_time_start']) && strtotime($data_newa['post_time_start'])  ? date('Y-m-d H:i:s',strtotime($data_newa['post_time_start'])) : date('Y-m-d H:i:s');
		$date_end 	= isset($data_newa['post_time_end'])   && strtotime($data_newa['post_time_end']) 	? date('Y-m-d H:i:s',strtotime($data_newa['post_time_end']))   : date('Y-m-d').' 23:59:59';
		
		$DB_NEW	= $this->DBLink->prepare(SQL_AdPost::ADMIN_POST_INSERT_NEW_POST_DATA());
		
		$DB_NEW->bindParam(':post_type'  	  , $data_newa['post_type']);
		$DB_NEW->bindParam(':post_from'  	  , $data_newa['post_from']);
		$DB_NEW->bindParam(':post_to'  		  , $data_newa['post_to']);
		$DB_NEW->bindValue(':post_target'     , '_ALL');
		$DB_NEW->bindParam(':post_level'  	  , $data_newa['post_level']);
		$DB_NEW->bindParam(':post_time_start' , $date_start);
		$DB_NEW->bindParam(':post_time_end'   , $date_end );
		$DB_NEW->bindValue(':post_title'  	  , strip_tags($data_newa['post_title']));
		$DB_NEW->bindValue(':post_content'	  , htmlspecialchars($data_newa['post_content'],ENT_QUOTES,'UTF-8'));
		$DB_NEW->bindValue(':post_refer'	, '');
		$DB_NEW->bindParam(':edit_user'  	, $this->USER->UserID);
		$DB_NEW->bindParam(':edit_group'  	, $this->USER->PermissionNow['group_code']);
		
		if( !$DB_NEW->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_UPDATE_FAIL');
		}
		
		$new_data_no  = $this->DBLink->lastInsertId('system_post');
		
		// final 
		$result['data']   = $new_data_no;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
		$result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Post Delete Post Data 
	// [input] : pno  :  \d+;
	public function ADPost_Del_Post_Data($DataNo=0){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查資料序號
	    if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// post_keep => 0
		$DB_SAVE	= $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdPost::ADMIN_POST_UPDATE_POST_DATA(array('post_keep'))));
		$DB_SAVE->bindParam(':pno'      , $DataNo , PDO::PARAM_INT);
		$DB_SAVE->bindValue(':post_keep' , 0 );
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// final 
		$result['data']   = $DataNo;
		$result['action'] = true;
		sleep(1);
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	// 處理時間
	static public function paser_date_array($DateArray){
		  
	  $paser_return = [
		'ds'=>NULL,
		'de'=>NULL,
		'years'=>['none'],
	  ];
	  
	  $date_queue = array();
	  
	  if(!is_array($DateArray)){
		return $paser_return;  
	  }
	  
	  
	  
	  foreach($DateArray as $dstr){
	  
		$ynum = intval(substr($dstr,0,4));		  
		$dset = preg_split('/(\/|\-|\.)/',$dstr);
		 
		if(!strtotime(strtr($dstr,'.','-')) || ( $ynum < 1911 || $ynum > date('Y'))){
		  continue;
		}
		
		if(isset($dset[1]) && !intval($dset[1])){
		  $dset[1]='01';		
		}
		
		if(isset($dset[2]) && !intval($dset[2])){
		  $dset[2]='01';		
		}
		
		$date_queue[] = strtotime(join('-',$dset));	
	  }
	  
	  if(!count($date_queue)){
		return $paser_return;    
	  }
	  
	  $paser_return['ds'] = date('Y-m-d',min($date_queue));
	  $paser_return['de'] = date('Y-m-d',max($date_queue));
	  $paser_return['years'] = [];
	  
	  for($i=intval(substr($paser_return['ds'],0,4)); $i<= intval(substr($paser_return['de'],0,4)) ; $i++){
		$yearnum = $i; //str_pad($i,4,'0',STR_PAD_LEFT);
		if( ($yearnum-1911) > 0 ){
		  $paser_return['years'][] = str_pad($yearnum,4,'0',STR_PAD_LEFT).' 民國'.($yearnum-1911).'年';   
		}else if(($yearnum-1911) < 0){
		  $paser_return['years'][] = str_pad($yearnum,4,'0',STR_PAD_LEFT).' 民國前'.($yearnum-1911).'年';   
		}else{
		  $paser_return['years'][] = str_pad($yearnum,4,'0',STR_PAD_LEFT).' 民國元年';  
		}
	  }
	  //var_dump($paser_return);
	  return $paser_return; 
		  
	}
	
	// 處理人名	  
	static public function paser_person($MemberArray){
	  $paser_return = [];
	   	
	  if(!is_array($MemberArray)){
		return $paser_return;  
	  }
	  
	  $data_queue = array();
	  
	  foreach($MemberArray as $mbr_string){
		$data_queue += preg_split('/(，|、|；|;|,)/u',$mbr_string);
	  }
	  
	  $data_queue = array_unique(array_filter($data_queue));
	  
	  if(!count($data_queue)){
		return $paser_return;   
	  }
	  
	  return array_values($data_queue); 
	}	  
	

	// 處理單位  
	static public function  paser_organ($OrganArray){
	  $paser_return = [];
	   	
	  if(!is_array($OrganArray)){
		return $paser_return;  
	  }
	  
	  $data_queue = array();
	  
	  foreach($OrganArray as $org_string){
		$data_queue += preg_split('/(，|、|；|;|,|\s+)/u',$org_string);
	  }
	  
	  $data_queue = array_unique(array_filter($data_queue));
	  
	  foreach($data_queue as $key=>$ditem){
		if(preg_match('/:|：/',$ditem)){
		  $ditemsplit = preg_split('/:|：/',$ditem);
		  $data_queue[$key] = array_shift($ditemsplit);	
		}
	  }
	  
	  if(!count($data_queue)){
		return $paser_return;   
	  }
	  
	  return array_values($data_queue); 
	} 
		 
	// 處理多重欄位	  
	static public function  paser_postquery($FieldArray){
	  $paser_return = [];
	   	
	  if(!is_array($FieldArray)){
		return $paser_return;  
	  }
	  
	  $data_queue = array();
	  
	  foreach($FieldArray as $field_string){
		$data_queue += preg_split('/(，|、|；|;|,)/u',$field_string);
	  }
	  
	  $data_queue = array_unique(array_filter($data_queue));
	  
	  if(!count($data_queue)){
		return $paser_return;   
	  }
	  return array_values($data_queue); 
	}	 
	
		// 轉國字數字
	static public function getChineseNumber($num){
		  $conver = array(
		   0 => '-',
		   1 => '一',
		   2 => '二',
		   3 => '三',
		   4 => '四',
		   5 => '五',
		   6 => '六',
		   7 => '七',
		   8 => '八',
		   9 => '九',
		   10 => '十',
		   11 => '十一',
		   12 => '十二',
		   13 => '十三',
		   14 => '十四',
		   15 => '十五',
		   16 => '十六',
		   17 => '十七',
		   18 => '十八',
		   19 => '十九',
		   20 => '二十',
		  );
		  
	    return isset($conver[$num]) ? $conver[$num] : '-';
	}
	
	
  }
?>