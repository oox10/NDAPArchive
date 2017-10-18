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
		  $result_source[$key]['_db']['sync'] = $meta['_sync']; 
		  
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
		  
		  
		  // 執行logs
		  $DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		  $DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		  $DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		  $DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		  $DB_LOGS->bindValue(':source' , json_encode($source));
		  $DB_LOGS->bindValue(':update' , json_encode($meta_batch));
		  $DB_LOGS->bindValue(':user' , $this->USER->UserID);
		  $DB_LOGS->bindValue(':result' , 1);
		  $DB_LOGS->execute();
		  
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
	
	
	
	//-- Admin Meta Execute User Select Batch
	// [input] : SelectedSids  :  encoed array string;
	// [input] : Action        :  open / view / ;  !strtolower-Y
	// [input] : Setting       :  (open):0/1 (view):開放/限閱/會內/關閉  ;
	
	public function ADMeta_Export_Selected($SelectedSids){
		
	  $result_key = parent::Initial_Result('batch');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		
		$data_batch_counter = 0;
		$data_selected = json_decode(base64_decode(str_replace('*','/',rawurldecode($SelectedSids))),true); 
		
		// check permission
		if(  !intval($this->USER->PermissionNow['group_roles']['R00']) && !intval($this->USER->PermissionNow['group_roles']['R02']) ){
		  throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');	
		} 
		
		// check data
		if(!count($data_selected)) throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		
		
		// get data set
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_SELECTED($data_selected));
		if( !$DB_GET->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$excel = [];
		while($meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  if(!isset($excel[$meta['zong']])) $excel[$meta['zong']]=[];
		  $source_meta = json_decode($meta['source_json'],true);
		  $excel[$meta['zong']][] =  $source_meta; 
		  $data_batch_counter++;
		}
		
		
		// 建構 excel file
		
		$outputPHPExcel = new PHPExcel();	
	    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
		foreach($excel as $sheet=>$data_list ){	
			
		  //php excel initial
		  switch($sheet){
		    case '檔案':		$excel_template = 'template_ndap_source_archive.xlsx'; break;
		    case '公報':		$excel_template = 'template_ndap_source_meeting.xlsx'; break; 
		    case '議事錄':	$excel_template = 'template_ndap_source_meeting.xlsx'; break;	 	
		    case '議事影音': 	$excel_template = 'template_ndap_source_media.xlsx'; break;
		    case '活動照片': 	$excel_template = 'template_ndap_source_photo.xlsx'; break;
		    case '議員傳記': 	$excel_template = 'template_ndap_source_biography.xlsx'; break;
		    default: $excel_template = $xlsx_temp; break;
		  }
			
		  $objPHPExcel = $objReader->load(_SYSTEM_ROOT_PATH.'mvc/templates/'.$excel_template);
		  $objPHPExcel->setActiveSheetIndex(0);
		  $objPHPExcel->getActiveSheet()->setTitle($sheet);
		
		  $col = 0 ;
		  $row = 3 ;
			
		  foreach( $data_list as $data){
			$col = 0;
			foreach($data as $f=>$v){
			  if(preg_match('/^_/',$f)) break; 
			  if(is_array($v)) $v = join(';',$v);
			  if(!trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col,2))) break;
			  $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col, $row)->setValueExplicit($v, PHPExcel_Cell_DataType::TYPE_STRING);  	
			  $col++;
			}
			$row++;
		  }
		   
		  $objClonedWorksheet = $objPHPExcel->getActiveSheet();
		  $outputPHPExcel->addExternalSheet($objClonedWorksheet);
		  $objPHPExcel->disconnectWorksheets();
		  unset($objPHPExcel);
		}	
		  
		  
		$outputPHPExcel->setActiveSheetIndexByName('Worksheet');
		$sheetIndex = $outputPHPExcel->getActiveSheetIndex();
		$outputPHPExcel->removeSheetByIndex($sheetIndex);
		$outputPHPExcel->setActiveSheetIndex(0);
		
		$excel_file_name =  _SYSTEM_NAME_SHORT.'_export_'.date('Ymd');
		
		$objWriter = PHPExcel_IOFactory::createWriter($outputPHPExcel, 'Excel2007');
		$objWriter->save(_SYSTEM_USER_PATH.$this->USER->UserID.'/'.$excel_file_name.'.xlsx'); 
		$outputPHPExcel->disconnectWorksheets();
		unset($outputPHPExcel);
		
		// final
		$result['data']['fname']   = $excel_file_name;
		$result['data']['count']   = $data_batch_counter;
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Meta :Meta Batch Export XLSX 
	// [input] : FileName  : logs_digital.note	
	public function ADMeta_Access_Export_File( $FileName=''){
	  
	  $result_key = parent::Initial_Result('file');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
	    
		if(!$FileName){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		} 
		
		$file_path = _SYSTEM_USER_PATH.$this->USER->UserID.'/'.$FileName.'.xlsx';
		if(!file_exists($file_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// final 
		$result['data']['name']  = $FileName.'.xlsx';
		$result['data']['size']  = filesize($file_path);
		$result['data']['location']  = $file_path;
		
		$result['action'] = true;
    	
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
	  $lib_imagemagic =  _SYSTEM_ROOT_PATH.'mvc/lib/ImageMagick-7.0.3-7-portable-Q16-x64/convert.exe ';
      $lib_ffmpeg 	  =  _SYSTEM_ROOT_PATH.'mvc/lib/ffmpeg-20161122-d316b21-win64-static/bin/ffmpeg.exe ';
	  $lib_ffprobe    =  _SYSTEM_ROOT_PATH.'mvc/lib/ffmpeg-20161122-d316b21-win64-static/bin/ffprobe.exe ';
	
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
		
		  // 取得數位物件設定
		  $doprofileread = '';
		  $doprofilepath =  _SYSTEM_DIGITAL_FILE_PATH.$meta_dobj['dopath'].'profile/'.$meta['collection'].'.conf'; 
		  if(is_file( $doprofilepath )){
			$doprofileread = file_get_contents($doprofilepath);	  
		  }
		  
		  $dobj_profile = json_decode($doprofileread,true);
		  
		  // 若無數位檔案規劃設定
		  if( !$dobj_profile || ( !isset($dobj_profile['items']) || !count($dobj_profile['items']))){
			
			// 掃描實體檔案 
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
				if(preg_match('@-([\d_]+)\.(jpg|png)@',$img,$match) ){
				  if(preg_match('/0000_/',$match[1])){
					$ImageZnumArray[]=$img;
				  }else{
					$ImageNumeArray[]=$img;
				  }  
				}else if(preg_match('@-(0000_[\w\d]+)\.(jpg|png)@',$img)){
					$ImageINumArray[]=$img;
				}else if(preg_match('@-(ca[\d]+)\.(jpg|png)@',$img)){
					$ImageCNumArray[]=$img;
				}else if(preg_match('@-(ap[\d]+)\.(jpg|png)@',$img)){
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
            
			
			
			// 建立數位物件設定檔
			$do_conf = array('store'=>$digital_object_path  , 'saved'=>date('Y-m-d H:i:s') , 'items'=>[] );
			foreach($dobj_list as $i=>$do){
			  if(!file_exists($digital_object_path.$do)) continue;
			  
			  $file_type = strtolower(pathinfo($digital_object_path.$do,PATHINFO_EXTENSION));
			  
			  switch($file_type){
				case 'jpg': case 'jpeg': case 'png': case 'gif':  
				  list($imgw, $imgh) = getimagesize($digital_object_path.$do);
			      $do_conf['items'][] = [
			        'file' => $do,
				    'width'=> $imgw,
			        'height'=> $imgh,
				    'size'=> filesize($digital_object_path.$do)
			      ]; 
				  break;
				case 'mp3':
				  
				  $fconfig = [
				    'file'   => $do,
				    'width'  => 150,
				    'height' => 150,
				    'length' => 0,
					'duration'=> 0,
				    'thumb'  => $do.'.png',
				    'order'  => ++$i,
				    'update' => date('Y-m-d H:i:s'),
				    'editor' => 'RCDH'
				  ];  
					
				  $second=[];
				  exec($lib_ffprobe .'-v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$digital_object_path.$do ,$second); 
				  $fconfig['length'] = intval(ceil($second[0]));
				  $fconfig['duration'] = str_pad(intval($fconfig['length']/3600),2,'0',STR_PAD_LEFT).':'.str_pad(intval(intval($fconfig['length']%3600)/60),2,'0',STR_PAD_LEFT).':'.str_pad(intval($fconfig['length']%60),2,'0',STR_PAD_LEFT);
				  $do_conf['items'][] = $fconfig;  
				  break;
				  
				case 'mp4':
				  
				  $fconfig = [
				    'file'   => $do,
					'width'  => 0,
					'height' => 0,
					'length' => 0,
					'duration'=> 0,
					'thumb'  => $do.'.jpg',
					'order'  => ++$i,
					'update' => date('Y-m-d H:i:s'),
					'editor' => 'RCDH'
				  ];
			      
				  $result=[];
				  exec($lib_ffprobe .'-v error -of flat=s=_ -select_streams v:0 -show_entries stream=height,width '.$digital_object_path.$do ,$result); 
				  foreach($result as $attr){
					list($a,$v) = explode('=',$attr);	
					if(preg_match('/width/',$a)){
					  $fconfig['width'] = intval($v);  
					}else{
					  $fconfig['height'] = intval($v);    
					}
				  }  
					
				  $second=[];
				  exec($lib_ffprobe .'-v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$digital_object_path.$do ,$second); 
				  //echo "\n".$dopath.' : '. $second[0];
				  $fconfig['length'] = intval(ceil($second[0]));
				  $fconfig['duration'] = str_pad(intval($fconfig['length']/3600),2,'0',STR_PAD_LEFT).':'.str_pad(intval(intval($fconfig['length']%3600)/60),2,'0',STR_PAD_LEFT).':'.str_pad(intval($fconfig['length']%60),2,'0',STR_PAD_LEFT);
				  $do_conf['items'][] = $fconfig;
				  
				  break;
				
				default: break;
			  }
			}
			
			// 取得數位物件設定
		    $doprofilepath =  _SYSTEM_DIGITAL_FILE_PATH.$meta_dobj['dopath'].'profile/'.$meta['collection'].'.conf'; 
		    file_put_contents($doprofilepath,json_encode($do_conf));
			
			$dobj_config = $do_conf['items'];
		  
		  }else{
			$dobj_config = $dobj_profile['items'];  
		  }
		  
		  $dobj_config = $dobj_config ? $dobj_config : $dobj_list;
		  
		  $result['data']['dobj_config']['root']   = $meta_dobj['dopath'];
		  $result['data']['dobj_config']['folder'] = $meta['collection'];
		  $result['data']['dobj_config']['files']  = $dobj_config;
		  
		}else{
		  $result['data']['dobj_config']['files']  = $meta_dobj;
		}
		
		// 取得專案資料夾
		$project = NULL;
		$DB_POJ	= $this->DBLink->prepare( SQL_AdMeta::GET_USER_PROJECTS());
		$DB_POJ->bindParam(':userno' , $this->USER->UserNO , PDO::PARAM_INT);	
		if( !$DB_POJ->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		while( $tmp = $DB_POJ->fetch(PDO::FETCH_ASSOC) ){  
		  $poj = [
		    'name'  =>  $tmp['pjname'],
			'count' =>  0,
		  ];
		  $poj['count'] = count(json_decode($tmp['pjelements'],true));	
		  $project[$tmp['spno']] = $poj;
		}
		
		
		// final
		$result['action'] = true;
		$result['data']['meta_list']   = $list ;
		$result['data']['meta_source'] = $source;
		
		$result['data']['form_mode']   = $meta['zong'];
		$result['data']['edit_mode']   = $Mode;
		
		$result['data']['user_project'] = $project;
		
		//$result['session']['METACOLLECTION']  = json_decode($collection_meta,true);
		//$result['session']['DOBJCOLLECTION']  = $collection_dobj;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Meta : Get DOBJ profile
	// [input] : DataType  : ARCHIVE....
	// [input] : DataFolder: collection id // file folder 
	public function ADMeta_Read_Dobj_Profile( $DataType='' , $DataFolder=''  ){
	  
	  $result_key = parent::Initial_Result('dobjs');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/profile/'.$DataFolder.'.conf';
		
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// final 
		$result['data']   = count($dobj_profile['items']) ? $dobj_profile['items'] : [] ;
    	$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Meta : Batch Rename DOBJ And ReSave profile
	// [input] : DataType    : ARCHIVE....
	// [input] : DataFolder  : collection id // file folder 
	// [input] : FilePreHeader : 檔名前墜 
	// [input] : FileStartNum  : 檔名起始編號,含編號長度  001
	// [input] : DOSelectEncode  : digital file name array 
	public function ADMeta_Dobj_Batch_Rename( $DataType='' , $DataFolder='' ,$FilePreHeader='', $FileStartNum='' ,$DOSelectEncode='' ){
	  
	  $result_key = parent::Initial_Result('dobjs');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		$process_counter = 0;
		$process_list    = array();
		
		// 檢查檔名參數
		if(!$FilePreHeader || !$FileStartNum){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 檢查DO資料設定
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/profile/'.$DataFolder.'.conf';
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 讀取DO設定
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 檢查勾選列表
		$dobj_name_array = json_decode(base64_decode(str_replace('*','/',rawurldecode($DOSelectEncode))),true); 
		if(!$dobj_name_array || !is_array($dobj_name_array) || !count($dobj_name_array)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 設定檔名規則
		// 掃描資料並刪除
		if(!preg_match('/^(\d+)(.*?)/',$FileStartNum,$match)){
		  throw new Exception('_META_DOBJ_RENAME_STARTNUM_PATTERN_FAILE');	 	
		}
		$new_fileheader 	= $FilePreHeader;
		$new_filenum_start  = intval($match[1]);
		$new_filenum_length = strlen($match[1]);
		$new_filenum_footer = isset($match[2]) ? $match[2] : '';
		
		
		// 檢測重排模式為往前或往後
		$rename_mode = '-';
		$first_element_num = intval(str_replace($new_fileheader,'',$dobj_name_array[0]));
		if($new_filenum_start <= $first_element_num){  
          //前排模式 		
		  $dobj_rename_array     = $dobj_name_array;
		  $rename_filenum_start  = $new_filenum_start;	
		  $rename_mode = '+';
		}else{
		  //後排模式	
		  $dobj_rename_array     = array_reverse($dobj_name_array);
		  $rename_filenum_start  = $new_filenum_start + count($dobj_name_array) -1 ;	
		  $rename_mode = '-';
		}
		
		
		
		$rename_counter = 0;
		
		//確認實體檔案
		$dobj_path = [];
		$dobj_path['saved']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/saved/'.$DataFolder.'/';
		$dobj_path['browse'] = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/browse/'.$DataFolder.'/';
		$dobj_path['thumb']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/thumb/'.$DataFolder.'/';
		
		
		// 執行重新命名
		foreach($dobj_rename_array as $target_do_file){
          
		  list($orl_filename,$orl_file_extension) = explode('.',$target_do_file);
          
		  if($rename_mode=='-'){  //依據模式命名檔案
			$new_filename = $new_fileheader.str_pad($rename_filenum_start-$rename_counter,$new_filenum_length,'0',STR_PAD_LEFT).$new_filenum_footer.'.'.$orl_file_extension;		    
		  }else{
			$new_filename = $new_fileheader.str_pad($rename_filenum_start+$rename_counter,$new_filenum_length,'0',STR_PAD_LEFT).$new_filenum_footer.'.'.$orl_file_extension;  
		  }
		  
		  $rename_counter++; 
		   
		  // 掃描原始資料設定
		  $dobjlist_save = $dobj_profile['items'];
		  $check_file_name = array(); // ['hasfile'=>false,'collide'=>false]檢查重新命名是否有問題，若有儲存位置
		  foreach($dobjlist_save  as $i => $doset){
            if($doset['file']==$target_do_file) $check_file_name['hasfile']=$i;
			if($doset['file']==$new_filename) $check_file_name['collide']=$i;
		  }
		  
          //確認原始列表內是否有碰撞(預期是沒有碰撞)
		  if(isset($check_file_name['collide'])){
            
			if(isset($check_file_name['hasfile']) && $check_file_name['collide']==$check_file_name['hasfile'] ){
			  // 碰撞號與當前編號相同，不處理
			  continue;
			}
			
			$target_do = $dobj_profile['items'][$check_file_name['collide']];
			$target_do_change_name = preg_replace('/\./','_.',$target_do['file']);
			
			foreach($dobj_path as $active_folder){
		      if(!file_exists($active_folder.$target_do['file'])){ continue; }		
			  if(copy($active_folder.$target_do['file'],$active_folder.$target_do_change_name)){
				unlink($active_folder.$target_do['file']); 
			  }		  
			}
			
			$dobj_profile['items'][$check_file_name['collide']]['file'] = $target_do_change_name;
		    
			// 紀錄
		    //確認檔案已轉移
			if(!file_exists($dobj_profile['store'].$target_do_change_name)){
			  continue; //檔案未處理成功	
			}
			
			$DB_LOG= $this->DBLink->prepare( SQL_AdMeta::LOGS_DOBJ_MODIFY());
		    $DB_LOG->bindParam(':file'   , $target_do['file'] );	
		    $DB_LOG->bindValue(':action' , 'collide' );
		    $DB_LOG->bindParam(':store'  , $target_do_change_name);
		    $DB_LOG->bindValue(':note'   , '' );
		    $DB_LOG->bindParam(':user'   , $this->USER->UserID);
		    $DB_LOG->execute();
			
			unset($check_file_name['collide']);
			
		  }
          
		  //確認是否檔案未碰撞並存在
		  if(isset($check_file_name['collide']) || !isset($check_file_name['hasfile'])){
			continue;  
		  }
			
	      $target_do = $dobj_profile['items'][$check_file_name['hasfile']];  
            			
          // 各資料夾變更檔案
		  foreach($dobj_path as $active_folder){
		    if(!file_exists($active_folder.$target_do['file'])){ continue; }
			if(copy($active_folder.$target_do['file'],$active_folder.$new_filename)){
			  unlink($active_folder.$target_do['file']); 
			}
		  }
		  
		  $dobj_profile['items'][$check_file_name['hasfile']]['file'] = $new_filename;
		  
		  //確認檔案已轉移
		  if(!file_exists($dobj_profile['store'].$new_filename)){
			continue; //檔案未處理成功	
		  }
			
		  $DB_LOG= $this->DBLink->prepare( SQL_AdMeta::LOGS_DOBJ_MODIFY());
		  $DB_LOG->bindParam(':file'   , $target_do['file'] );	
		  $DB_LOG->bindValue(':action' , 'rename' );
		  $DB_LOG->bindParam(':store'  , $new_filename);
		  $DB_LOG->bindValue(':note'   , '' );
		  $DB_LOG->bindParam(':user'   , $this->USER->UserID);
		  $DB_LOG->execute();
		  
		  $process_counter++;
		  $process_list[] = $target_do_file;
		
		}
		
		$dobj_profile['saved'] = date('Y-m-d H:i:s');
		$dobj_config = file_put_contents($profile_path,json_encode($dobj_profile));
		
		// final 
		$result['data']['count'] = $process_counter;
		$result['data']['list']  = $process_list ;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Meta : Batch Reorder DOBJ And ReSave profile
	// [input] : DataType    : ARCHIVE....
	// [input] : DataFolder  : collection id // file folder 
	// [input] : DOFilesEncode  : digital file name array : all file
	public function ADMeta_Dobj_Batch_Reorder( $DataType='' , $DataFolder='' ,$DOFilesEncode='' ){
	  
	  $result_key = parent::Initial_Result('dobjs');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		$process_counter = 0;
		$process_list    = array();
		
		// 檢查DO資料設定
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/profile/'.$DataFolder.'.conf';
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 讀取DO設定
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 檢查檔案順序列表
		$dobj_name_array = json_decode(base64_decode(str_replace('*','/',rawurldecode($DOFilesEncode))),true); 
		if(!$dobj_name_array || !is_array($dobj_name_array) || !count($dobj_name_array)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		$dobjlist_save = $dobj_profile['items'];
		
		//確認檔案數量相符 
		if(count($dobj_name_array) != count($dobjlist_save)){
		  throw new Exception('_META_DOBJ_REORDER_FILE_COUNT_NOT_MATCH');		
		}
		
		//確認實體檔案
		$dobj_path = [];
		$dobj_path['saved']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/saved/'.$DataFolder.'/';
		$dobj_path['browse'] = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/browse/'.$DataFolder.'/';
		$dobj_path['thumb']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/thumb/'.$DataFolder.'/';
		
		$reorder_do_profile = array();  //新順序之設定
		
		// 執行重新命名
		foreach($dobj_name_array as $newi => $target_do_file){
		  
		  foreach($dobjlist_save  as $orli => $doset){
            if($doset['file']==$target_do_file){
			  
			  $reorder_do_profile[] = $doset;  	
			   
			  if($orli!==$newi){
			    $DB_LOG= $this->DBLink->prepare( SQL_AdMeta::LOGS_DOBJ_MODIFY());
			    $DB_LOG->bindParam(':file'   , $target_do_file );	
			    $DB_LOG->bindValue(':action' , 'reorder' );
			    $DB_LOG->bindValue(':store'  , '');
			    $DB_LOG->bindValue(':note'   , $orli.'=>'.$newi );
			    $DB_LOG->bindParam(':user'   , $this->USER->UserID);
			    $DB_LOG->execute();
			  
			    $process_counter++;
			    $process_list[] = $target_do_file;
			  
			  }
			  break;
			}
		  }
		}
		
		// 確認資料已變更
		if(md5(json_encode($dobj_profile['items']))!=md5(json_encode($reorder_do_profile))){
		  $dobj_profile['items'] = $reorder_do_profile;
		  $dobj_profile['saved'] = date('Y-m-d H:i:s');
		  $dobj_config = file_put_contents($profile_path,json_encode($dobj_profile));	
		}
		
		// final 
		$result['data']['count'] = $process_counter;
		$result['data']['list']  = $process_list ;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Meta : Batch Delete DOBJ And ReSave profile
	// [input] : DataType    : ARCHIVE....
	// [input] : DataFolder  : collection id // file folder 
	// [input] : DOSelectEncode  : digital file name array 
	// [input] : Recapture  : 驗證碼
	// [input] : Var  : digital file name array 
	public function ADMeta_Dobj_Batch_Delete( $DataType='' , $DataFolder='' ,$DOSelectEncode='' ,$Recapture='', $Verification=''){
	  
	  $result_key = parent::Initial_Result('dobjs');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		$process_counter = 0;
		$process_list    = array();
		
		// 檢查驗證碼
		if(!$Recapture || $Recapture!==$Verification){
		  throw new Exception('_REGISTER_ERROR_CAPTCHA_TEST_FAIL');
		}
		
		// 檢查DO資料設定
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/profile/'.$DataFolder.'.conf';
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 讀取DO設定
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 檢查勾選列表
		$dobj_name_array = json_decode(base64_decode(str_replace('*','/',rawurldecode($DOSelectEncode))),true); 
		if(!$dobj_name_array || !is_array($dobj_name_array) || !count($dobj_name_array)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 掃描資料並刪除
		$dobjlist_save = $dobj_profile['items'];
		
		foreach($dobjlist_save  as $i => $doset){
		    
          if(!in_array($doset['file'],$dobj_name_array)){ //檔案不在刪除清單中
			continue;  
		  }
		  
		  //確認實體檔案
		  $dobj_path = [];
		  $dobj_path['saved']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/saved/'.$DataFolder.'/'.$doset['file'];
		  $dobj_path['browse'] = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/browse/'.$DataFolder.'/'.$doset['file'];
		  $dobj_path['thumb']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/thumb/'.$DataFolder.'/'.$doset['file'];
		  
		  $resavename = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/trach/'.$doset['file'].microtime('true');  // 垃圾桶位置
		  
		  foreach($dobj_path as $dotype => $dopath){
            if(!file_exists($dopath)){ continue; }		
            copy($dopath , $resavename);
			unlink($dopath); 
		  }
		  
		  // 移出profile
		  unset($dobj_profile['items'][$i]);
		  
		  // 紀錄
		  $DB_LOG= $this->DBLink->prepare( SQL_AdMeta::LOGS_DOBJ_MODIFY());
		  $DB_LOG->bindParam(':file'   , $doset['file'] );	
		  $DB_LOG->bindValue(':action' , 'delete' );
		  $DB_LOG->bindParam(':store'  , $resavename);
		  $DB_LOG->bindValue(':note'   , '' );
		  $DB_LOG->bindParam(':user'   , $this->USER->UserID);
		  $DB_LOG->execute();
		  
          $process_list[] = $doset['file'];
		  $process_counter++;
		
		}
		
		$dobj_config = file_put_contents($profile_path,json_encode($dobj_profile));
		
		// final 
		$result['data']['count'] = $process_counter;
		$result['data']['list']  = $process_list ;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Meta : Batch Delete DOBJ And ReSave profile
	// [input] : DataType    : ARCHIVE....
	// [input] : DataFolder  : collection id // file folder 
	public function ADMeta_Dobj_Buffer_Update( $DataType='' , $DataFolder='' ){
		
	  $result_key = parent::Initial_Result('buffer');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查DO資料設定
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/profile/'.$DataFolder.'.conf';
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 讀取DO設定
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 更新imageBuffer
		$system_buffer_patch    = _SYSTEM_DIGITAL_LIST_BUFFER.$DataFolder.'_list.tmp';
		$system_buffer_contents = [$dobj_profile['store']];
		foreach($dobj_profile['items'] as $item){
	      $system_buffer_contents[] = $item['file'];		
		}
		
		file_put_contents($system_buffer_patch,join("\n", $system_buffer_contents));
		
		// final 
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
		
	
	
	
	//-- Admin Meta : DOBJ File Download Prepare
	// [input] : DataType    : ARCHIVE....
	// [input] : DataFolder  : collection id // file folder 
	// [input] : DoFileName  : digital file name 
	public function ADMeta_Dobj_Prepare( $DataType='' , $DataFolder='' , $DoFileName=''){
	  
	  $result_key = parent::Initial_Result('dobjs');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查DO資料設定
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/profile/'.$DataFolder.'.conf';
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 讀取DO設定
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		//確認實體檔案
		$dobj_path = [];
		$dobj_path['saved']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/saved/'.$DataFolder.'/'.$DoFileName;
		$dobj_path['browse'] = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/browse/'.$DataFolder.'/'.$DoFileName;
		$dobj_path['thumb']  = _SYSTEM_DIGITAL_FILE_PATH.$DataType.'/thumb/'.$DataFolder.'/'.$DoFileName;
		
		$dobj_download = '';
		foreach($dobj_path as $dotype => $dopath){
          if(!file_exists($dopath)){ continue; }		
          $dobj_download = $dopath;
		  break;
		}
		
		if(!$dobj_download){
		  throw new Exception('_META_DOBJ_DOWNLOAD_FILE_NOT_EXIST');			
		}
		
		$hash_download = md5($DoFileName.microtime(true));
		
		// 紀錄
		$DB_LOG= $this->DBLink->prepare( SQL_AdMeta::LOGS_DOBJ_MODIFY());
		$DB_LOG->bindParam(':file'   , $DoFileName );	
		$DB_LOG->bindValue(':action' , 'download' );
		$DB_LOG->bindParam(':store'  , $dobj_download);
		$DB_LOG->bindValue(':note'   , $hash_download);
		$DB_LOG->bindParam(':user'   , $this->USER->UserID);
		$DB_LOG->execute();
		
		// final 
		$result['data']['hash']  = $hash_download;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Meta :DOBJ File Save
	// [input] : DoDownloadHash  : logs_digital.note	
	public function ADMeta_Dobj_Get_Download( $DoDownloadHash=''){
	  
	  $result_key = parent::Initial_Result('file');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
	  
	    if(!$DoDownloadHash || strlen($DoDownloadHash)!='32'){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		} 
	  
		$DB_DOBJ= $this->DBLink->prepare( SQL_AdMeta::DOBJ_DOWNLOAD_RESOUCE());
		$DB_DOBJ->bindValue(':action','download');
		$DB_DOBJ->bindValue(':hash',$DoDownloadHash);
		$DB_DOBJ->bindValue(':user',$this->USER->UserID);
		if( !$DB_DOBJ->execute() || !$source = $DB_DOBJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// final 
		$result['data']['name']  = $source['doname'];
		$result['data']['size']  = filesize($source['store']);
		$result['data']['location']  = $source['store'];
		
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Upload Photo 
	// [input] : ZongCode     : [str] zong folder : RECORD ARCHIVE...    
	// [input] : FolderCode   : [str] collection_id;
	// [input] : DOSelectEncode     : [str] timeflag  date(YmdHis);
	// [input] : ProjectNo 	  : [int] system_project.spno;
	public function ADMeta_Dobj_Project_Import( $ZongCode='', $FolderCode='' , $DOSelectEncode='' , $ProjectNo=0){
	  
	  $result_key = parent::Initial_Result('dobjs');
	  $result  = &$this->ModelResult[$result_key];
	   
      // Allowed extentions.
      $allowedExts = array("jpg","tiff","png","gif","cr2","dng","tif","raf","mp3","mp4");
      
	  try{
		
		$process_list = [];
		$process_counter = 0;
		
		// 檢查參數
		if(!preg_match('/^[\w\d\_\-]+$/',$FolderCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 檢查勾選資料
		$dobj_name_array = json_decode(base64_decode(str_replace('*','/',rawurldecode($DOSelectEncode))),true); 
		if(!$dobj_name_array || !is_array($dobj_name_array) || !count($dobj_name_array)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}  
		
		// 檢查DO資料設定
	    $profile_path = _SYSTEM_DIGITAL_FILE_PATH.$ZongCode.'/profile/'.$FolderCode.'.conf';
		if(!file_exists($profile_path)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 取得數位檔案設定檔
		$dobj_config = file_get_contents($profile_path);
		$dobj_profile = json_decode($dobj_config,true);
		if( !$dobj_profile || ( !isset($dobj_profile['items']) || !is_array($dobj_profile['items']))){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');		
		}
		
		// 取得專案資料夾
		$project = array();
		$DB_POJ= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_PROJECT());
		$DB_POJ->bindValue(':userno',$this->USER->UserNO);
		$DB_POJ->bindValue(':spno'  ,intval($ProjectNo));
		if( !$DB_POJ->execute() || !$project = $DB_POJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$project_elements = json_decode($project['pjelements'],true);
		
		// 註冊 Task
		$DB_Task = $this->DBLink->prepare(SQL_AdMeta::REGIST_SYSTEM_TASK()); 
		$DB_Task->bindValue(':user',intval($ProjectNo));  // !!註冊專案序號，非使用者序號  20170927
		$DB_Task->bindValue(':task_name',"專案匯入");
		$DB_Task->bindValue(':task_type',"PROJECT");
		$DB_Task->bindValue(':task_num',count($dobj_name_array));
		$DB_Task->bindValue(':task_done',0);
		$DB_Task->bindValue(':time_initial',date('Y-m-d H:i:s'));
		
		if(!$DB_Task->execute()){
		  throw new Exception('_TASK_INITIAL_FAIL'); 	
		}
		
		$task_id = $this->DBLink->lastInsertId(); 
		
		
		// 處理檔案
		foreach($dobj_name_array as $package_target_file){
		  
		  if(preg_match('/\.mp\d/',$package_target_file)){
            list($package_target_file,$start_time,$end_time) = explode('#',$package_target_file);
		  }
		  
		  if(!file_exists($dobj_profile['store'].$package_target_file)){
			continue;
		  }
		  
		  $filepath = $dobj_profile['store'].$package_target_file;
		  $extension = pathinfo($filepath,PATHINFO_EXTENSION);
		  $filemime  = mime_content_type($filepath);
		  
		  
		  if(isset($start_time)&&isset($end_time)){
			$package_target_file = preg_replace('/(\..*?)$/','_'.$start_time.'-'.$end_time."\\1",$package_target_file); 
		  }
		  
		  $DB_Regist = $this->DBLink->prepare(SQL_AdMeta::REGIST_FILE_UPLOAD_RECORD()); 
		  $DB_Regist->bindValue(':utkid',	$task_id);
		  $DB_Regist->bindValue(':folder',  $FolderCode);
		  $DB_Regist->bindValue(':flag',	'system_project');
		  $DB_Regist->bindValue(':user',	$this->USER->UserID);
		  $DB_Regist->bindValue(':hash',	intval($ProjectNo));
		  $DB_Regist->bindValue(':store',	$filepath);
		  $DB_Regist->bindValue(':saveto',  _SYSTEM_DIGITAL_FILE_PATH.'PROJECT/'.str_pad(intval($ProjectNo),5,'0',STR_PAD_LEFT).'/');
		  $DB_Regist->bindValue(':name',	$package_target_file);
		  $DB_Regist->bindValue(':size',	filesize($filepath));
		  $DB_Regist->bindValue(':mime',	strtolower($filemime));
		  $DB_Regist->bindValue(':type',	strtolower($extension));
		  $DB_Regist->bindValue(':last',	isset($start_time)&&isset($end_time) ? $start_time.':'.$end_time: '');
		  
		 
		  if(!$DB_Regist->execute()){  
			continue;	
		  }
		  
		  $urno = $this->DBLink->lastInsertId();
		  
		  // 紀錄project element
		  $project_elements[$package_target_file] = [
		    'path'=>'PROJECT/'.str_pad(intval($ProjectNo),5,'0',STR_PAD_LEFT).'/',
            'type'=>$extension,
			'from'=>$FolderCode,
			'status'=>'_regist',
			'time'=>date('Y-m-d H:i:s'),
			'user'=>$this->USER->UserID,
		  ];
		  
		  $process_list[] = $package_target_file;
		  $process_counter++;
		
		}
		
		// 更新project
		$DB_POJ= $this->DBLink->prepare( SQL_AdMeta::UPDATE_TARGET_PROJECT());
		$DB_POJ->bindValue(':regtask',$task_id);
		$DB_POJ->bindValue(':userno',$this->USER->UserNO);
		$DB_POJ->bindValue(':spno'  ,intval($ProjectNo));
		$DB_POJ->bindValue(':pjelements'  ,json_encode($project_elements));
		$DB_POJ->execute();
		
		// 開啟匯入程序
		//exec(_SYSTEM_PHP_ROOT._SYSTEM_ROOT_PATH.'systemJob/Job_Import_Upload_Files.php');  // 做完才結束
		pclose(popen("start /b "._SYSTEM_PHP_ROOT._SYSTEM_ROOT_PATH.'systemTasks/Task_Process_Project_Files.php '.$task_id,"r"));  // 可以放著不管
		$result['data']['task']  = $task_id ;
		$result['data']['count'] = $process_counter;
		$result['data']['list']  = $process_list ;
		$result['action'] = true;
		
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
		
		// 執行mdlogs
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		$DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		$DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		$DB_LOGS->bindValue(':source' , json_encode($source));
		$DB_LOGS->bindValue(':update' , json_encode($meta_update));
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		
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
		  if($source['_metakeep']){
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
	
	//-- Admin Meta : Read Meta edit logs
	// [input] : DataNo  :  \w\d+;  system_id
	public function ADMeta_Read_Item_Logs( $DataNo='' ){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得編輯資料
		$logs = [];
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_LOGS());
		$DB_GET->bindParam(':sid'   , $DataNo );	
		if( !$DB_GET->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		while($tmp = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  $logs[] = [
		    'time'=>$tmp['mdtime'],
			'id'=>$tmp['identifier'],
			'editor'=>$tmp['uploader'],
			'fields'=>json_decode($tmp['updated'],true),
		  ];
		}
		
		// final 
		$result['action'] = true;
    	$result['data'] = $logs;
    	
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Meta : Dele Meta Element
	// [input] : DataNo  :  \w\d+;  system_id
	public function ADMeta_Dele_Item_Data( $DataNo='' ){
	  
	  $result_key = parent::Initial_Result('dele');
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
		
		// 補充系統欄位
		$meta_update['_userupdate'] = $this->USER->UserID;
		$meta_update['_metakeep'] = 0;
		
		// 執行更新
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_SOURCE_META(array('_metakeep'),$meta['zong']));
		$DB_SAVE->bindValue(':id'    , $meta['identifier']);
		$DB_SAVE->bindValue(':_metakeep' , 0);
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 刪除META
		$DB_DELE= $this->DBLink->prepare(SQL_AdMeta::DELETE_TARGET_METADATA());
		$DB_DELE->bindValue(':id'    , $DataNo);
		if( !$DB_DELE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		
		// 執行mdlogs
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		$DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		$DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		$DB_LOGS->bindValue(':source' , json_encode($source));
		$DB_LOGS->bindValue(':update' , json_encode($meta_update));
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
		$DB_LOGS->bindValue(':result' , 1);
		$DB_LOGS->execute();
		
		// final 
		$result['action'] = true;
    	$result['data'] = $DataNo;
    	
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
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		$DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		$DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		$DB_LOGS->bindValue(':source' , json_encode($source));
		$DB_LOGS->bindValue(':update' , json_encode($meta_update));
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
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
	
	
	
	
	
	
	
	/*== [ File Upload Module ] ==*/
	//name:數位檔案上傳模組
	
	
	//-- Initial Dobj Upload Initial 
	// 上傳檔案初始化，建立暫存空間，並確認資料是否重複
	// [input] : UploadData : urlencode(json_pass())  = array(folder , creater , classlv , list=>array(name, size, type, lastmdf=timestemp));
	// [input] : FILES : [array] - System _FILES Array;
	public function ADMeta_Upload_Task_Initial( $UploadData=''){
	  
	  $result_key = parent::Initial_Result('upload');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $upload_data = json_decode(rawurldecode($UploadData),true);   
	  
	  try{
		  
		if(!$upload_data || !count($upload_data)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}  
		
		$upload_time_flag = date('YmdHis');  // 用來識別task_upload file
		
		//create upload temp space 
		$upload_temp_folder = _SYSTEM_DIGITAL_FILE_PATH.'UPLOAD/'.$this->USER->UserID.'/'.$upload_data['folder'].'/';
		if(!is_dir($upload_temp_folder)){
		  mkdir($upload_temp_folder,0777,true);	
		}
		
		// check exist file 確認檔案室否曾經上傳
		$checked  = array();
		$DB_Check  = $this->DBLink->prepare(SQL_AdMeta::CHECK_FILE_UPLOAD_LIST()); 
		
		if(is_array($upload_data['list']) && count($upload_data['list'])){  
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
	
	
	
	
	//-- Upload Photo 
	// [input] : ZongCode     : [str] zong folder : RECORD ARCHIVE...    
	// [input] : FolderCode   : [str] collection_id;
	// [input] : TimeFlag     : [str] timeflag  date(YmdHis);
	// [input] : UploadMeta : accnum:urlencode(base64encode(json_pass()))  = array(F=>V);
	// [input] : FILES : [array] - System _FILES Array;
	public function ADMeta_Uploading_Dobj( $ZongCode='', $FolderCode='' ,$TimeFlag='' , $UploadMeta='' , $FILES = array()){
	  
	  $result_key = parent::Initial_Result('upload');
	  $result  = &$this->ModelResult[$result_key];
	  
      // [name] => MyFile.jpg  / [type] => image/jpeg  /  [tmp_name] => /tmp/php/php6hst32 / [error] => UPLOAD_ERR_OK / [size] => 98174
	  // Allowed extentions.
      $allowedExts = array("jpg","tiff","png","gif","cr2","dng","tif","raf","mp3","mp4");
      
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
		if(!preg_match('/^[\w\d\_\-]+$/',$FolderCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if(!preg_match('/^\d{14}$/',$TimeFlag)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
     	
		if (!in_array(strtolower($extension), $allowedExts)) {
	      throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	    }	
		
        if( $FILES["file"]["error"] ){
          throw new Exception('_SYSTEM_UPLOAD_ERROR:'.$FILES["file"]["error"]);  
        }
        
		
		//紀錄上傳檔案
		$hashkey = md5($FILES["file"]['name'].$FILES["file"]['size'].$FILES["file"]['lastmdf']);
		$filetmp  = microtime(true);
		$filepath = _SYSTEM_DIGITAL_FILE_PATH.'UPLOAD/'.$this->USER->UserID.'/'.$FolderCode.'/'.$filetmp;
		
		$DB_Regist = $this->DBLink->prepare(SQL_AdMeta::REGIST_FILE_UPLOAD_RECORD()); 
		$DB_Regist->bindValue(':utkid',	0);
		$DB_Regist->bindValue(':folder',$FolderCode);
		$DB_Regist->bindValue(':flag',	$TimeFlag);
		$DB_Regist->bindValue(':user',	$this->USER->UserID);
		$DB_Regist->bindValue(':hash',	$hashkey);
		$DB_Regist->bindValue(':store',	$filepath);
		$DB_Regist->bindValue(':saveto',_SYSTEM_DIGITAL_FILE_PATH.$ZongCode.'/');
		$DB_Regist->bindValue(':name',	$FILES["file"]['name']);
		$DB_Regist->bindValue(':size',	$FILES["file"]['size']);
		$DB_Regist->bindValue(':mime',	strtolower($FILES["file"]['type']));
		$DB_Regist->bindValue(':type',	strtolower($extension));
		$DB_Regist->bindValue(':last',	$FILES["file"]['lastmdf']);
		
		if(!$DB_Regist->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');	
		}
		
		$urno = $this->DBLink->lastInsertId();
		
		// 取得文件資料
		if(!move_uploaded_file($FILES["file"]["tmp_name"], $filepath )){
		  throw new Exception('_META_DOBJ_UPLOAD_MOVE_FAIL');		
		}
		
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
	
	//-- Finish Digital Object Upload Task 
	// [input] : ZongCode     : [str] zong folder : RECORD ARCHIVE...     
	// [input] : FolderId     : [str] metadata.collection
	// [input] : TimeFlag     : [int] fuploadtimeflag; \d{14}
	public function ADMeta_Upload_Task_Finish($ZongCode='', $FolderId='' , $TimeFlag='' ){
	  
	  $result_key = parent::Initial_Result('queue');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		  
		// 檢查參數
		if(!preg_match('/^[\w\d\-\_]+$/',$FolderId)  ||  !preg_match('/^\d{14}$/',$TimeFlag)   ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		
		// 確認資料夾狀態
		$dobj_exist   = array();
		$folder_conf = _SYSTEM_DIGITAL_FILE_PATH.$ZongCode.'/profile/'.$FolderId.'.conf';
		if(file_exists($folder_conf)){
		  $dobj_array = json_decode(file_get_contents($folder_conf),true);	
		  if(isset($dobj_array['items'])){
		    foreach($dobj_array['items'] as $dobj ){
		  	  $dobj_exist[] = $dobj['file'];
		    }
		  }
		}
		
		
		$folder_id = $FolderId;
		$upload_flag = $TimeFlag;
		
		// 查詢新上傳檔案
		$dobj_upload = array();
		$DB_DOJ = $this->DBLink->prepare(SQL_AdMeta::SELECT_UPLOAD_OBJECT_LIST());
		$DB_DOJ->bindValue(':folder', $FolderId); 
		$DB_DOJ->bindValue(':flag'	, $upload_flag); 
		$DB_DOJ->bindValue(':user'	, $this->USER->UserID); 
		if(!$DB_DOJ->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');	
		}
		
		while( $tmp = $DB_DOJ->fetch(PDO::FETCH_ASSOC)){
		  $dobj = $tmp;
		  if( in_array($tmp['name'],$dobj_exist) ){
			$dobj['@check'] = 'duplicate'; 
		  }else{
			$dobj['@check'] = '';   
		  }
		  $dobj_upload[] = $dobj;
		}
		
		if(!count($dobj_upload)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');	
		}
		
		$result['data']   = $dobj_upload;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Active Upload Object Import  讓上傳成功之檔案執行匯入
	// [input] : UploadListPaser     : [str] encode string(system_upload.urno.array)
	public function ADMeta_Process_Upload_Import( $UploadListPaser=''){
	 
	  $result_key = parent::Initial_Result('uplact');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
		
		// 處理勾選檔案
		$process_counter = 0;
		$process_list    = array();
        $uplfile_list = json_decode(base64_decode(str_replace('*','/',rawurldecode($UploadListPaser))),true);  
		
		
		//註冊匯入工作
		$DB_Task = $this->DBLink->prepare(SQL_AdMeta::REGIST_SYSTEM_TASK()); 
		$DB_Task->bindValue(':user',$this->USER->UserNO);
		$DB_Task->bindValue(':task_name',"數位檔案上傳");
		$DB_Task->bindValue(':task_type',"DOIMPORT");
		$DB_Task->bindValue(':task_num',count($uplfile_list));
		$DB_Task->bindValue(':task_done',0);
		$DB_Task->bindValue(':time_initial',date('Y-m-d H:i:s'));
		
		if(!$DB_Task->execute()){
		  throw new Exception('_TASK_INITIAL_FAIL'); 	
		}
		
		$task_id = $this->DBLink->lastInsertId(); 
		
		// 處理資料
		$DB_UPL = $this->DBLink->prepare(SQL_AdMeta::SELECT_TARGET_UPLOAD_FILE());  //查詢上傳檔案
		$DB_DEL = $this->DBLink->prepare(SQL_AdMeta::DELETE_TARGET_UPLOAD_FILE());  //標示檔案刪除 
		
		$DB_Bind = $this->DBLink->prepare(SQL_AdMeta::BIND_UPLOAD_TO_TASK());  // 將上傳資料綁定工作
		$DB_Bind->bindValue(':utkid',$task_id);
		
		foreach($uplfile_list as $urno){
			
		  $DB_UPL->bindValue(':urno',$urno);	
		  if(!$DB_UPL->execute()) continue;
          
		  $tmp = $DB_UPL->fetch(PDO::FETCH_ASSOC);	
		  $active_time = date('Y-m-d H:i:s');
		  $logs = $tmp['_logs'] ? json_decode($tmp['_logs'],true) : array();
		  
		  if(!file_exists($tmp['store'])){
			
			//檔案若不存在則標示檔案刪除
		    $logs[$active_time] = $this->USER->UserID.' upload file unfound.';
		    $DB_DEL->bindValue(':process',$active_time);
		    $DB_DEL->bindValue(':logs',json_encode($logs));
		    $DB_DEL->bindValue(':urno',$urno);	
		    if(!$DB_DEL->execute()) continue;
			 
		  }
		  
		  $logs[$active_time] = $this->USER->UserID.' regist import task:'.$task_id.'.';
		  
		  
		  $DB_Bind->bindValue(':urno',$urno); 
		  $DB_Bind->bindValue(':logs',json_encode($logs));		  
		  
		  if(!$DB_Bind->execute()) continue;
		 
		  $process_counter++;
		  $process_list[] = $urno;
		  
		}
		
		// 開啟匯入程序
		//exec(_SYSTEM_PHP_ROOT._SYSTEM_ROOT_PATH.'systemJob/Job_Import_Upload_Files.php');  // 做完才結束
		pclose(popen("start /b "._SYSTEM_PHP_ROOT._SYSTEM_ROOT_PATH.'systemTasks/Task_Import_Upload_Files.php '.$task_id,"r"));  // 可以放著不管
		
		$result['data']['count'] = $process_counter;
		$result['data']['task']  = $task_id ;
		$result['data']['list']  = $process_list ;
		
		$result['action'] = true;
	
	 } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	//-- Active Upload Object Delete  刪除上傳成功之檔案
	// [input] : UploadListPaser     : [str] encode string(system_upload.urno.array)
	public function ADMeta_Process_Upload_Delete( $UploadListPaser='' ){
	 
	  $result_key = parent::Initial_Result('uplact');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
        $uplfile_list = json_decode(base64_decode(str_replace('*','/',rawurldecode($UploadListPaser))),true);  
		
		if(!count($uplfile_list)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
        // 處理勾選檔案
		$process_counter = 0;
		$process_list    = array();
		
		$DB_UPL = $this->DBLink->prepare(SQL_AdMeta::SELECT_TARGET_UPLOAD_FILE());  //查詢上傳檔案
		$DB_DEL = $this->DBLink->prepare(SQL_AdMeta::DELETE_TARGET_UPLOAD_FILE());  //標示檔案刪除 
		
		foreach($uplfile_list as $urno){
		  
		  $DB_UPL->bindValue(':urno',$urno);	
		  if(!$DB_UPL->execute()) continue;
          
		  $tmp = $DB_UPL->fetch(PDO::FETCH_ASSOC);
		  
		  //刪除暫存檔
		  if(file_exists($tmp['store'])){
			unlink($tmp['store']);
		  }
		  
		  //資料庫更新
		  $active_time = date('Y-m-d H:i:s');
		  $logs = $tmp['_logs'] ? json_decode($tmp['_logs'],true) : array();
		  $logs[$active_time] = $this->USER->UserID.' delete upload file.';
		  
		  $DB_DEL->bindValue(':process',$active_time);
		  $DB_DEL->bindValue(':logs',json_encode($logs));
		  $DB_DEL->bindValue(':urno',$urno);	
		  if(!$DB_DEL->execute()) continue;
		  
		  $process_counter++;
		  $process_list[] = $urno;
		
		}
		
		$result['data']['count'] = $process_counter;
		$result['data']['list']  = $process_list ;
		$result['action'] = true;
	     
	 } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	//-- Upload Member Photo  // 更新議員頭像 
	// [input] : SystemId : metadata.system_id
	// [input] : FILES : [array] - System _FILES Array;
	public function ADMeta_Upload_Member_Photo( $SystemId='' , $FILES = array()){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
      // [name] => MyFile.jpg  / [type] => image/jpeg  /  [tmp_name] => /tmp/php/php6hst32 / [error] => UPLOAD_ERR_OK / [size] => 98174
	  // Allowed extentions.
      $allowedExts = array("jpg","png");
       
	  try{
		
		// 檢查序號
	    if(!preg_match('/^[\w\d\-]+$/',$SystemId)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// Get filename.
        $temp = explode(".", $FILES["file"]["name"]);
        // Get extension.
        $extension = end($temp);
		
		// 檢查上傳檔案資訊
		if (!in_array(strtolower($extension), $allowedExts)) {
	      throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	    }	
		
        if( $FILES["file"]["error"] ){
          throw new Exception('_SYSTEM_UPLOAD_ERROR:'.$FILES["file"]["error"]);  
        }
		
		
	  
	    // Validate uploaded files.
	    // Do not use $_FILES["file"]["type"] as it can be easily forged.
	    $finfo = finfo_open(FILEINFO_MIME_TYPE);
	    $mime  = finfo_file($finfo, $FILES["file"]["tmp_name"]);
		
		$zong_folder =  _SYSTEM_FILE_PATH.'BIOGRAPHY/';
		
		// 取得編輯資料
		$meta = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdMeta::GET_TARGET_META_DATA());
		$DB_GET->bindParam(':id'   , $SystemId );	
		if( !$DB_GET->execute() || !$meta = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 處理數位檔案
		$dobj_config = json_decode($meta['dobj_json'],true);
		
		if(!isset($dobj_config['portrait'])){
		  $dobj_config['portrait'] = [
		    "name"=>"議員頭像",
		    "type"=>"",
		    "mode"=>"base64",
		    "source"=>""
		  ]; 
		}else{
		  // 轉存
		  $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dobj_config['portrait']['source']));	
		  $resave = $zong_folder.'trach/'.str_pad($SystemId,'10','0',STR_PAD_LEFT).microtime(true).'.'.$dobj_config['portrait']['type'];
		  file_put_contents($resave, $data);
		}
		
		// 取得上傳資料
		$store_path = $zong_folder.'browse/'.str_pad($SystemId,'10','0',STR_PAD_LEFT).'.'.$dobj_config['portrait']['type'];
		move_uploaded_file($FILES["file"]["tmp_name"],$store_path);
		
		// 轉存base64
		$type = pathinfo($store_path, PATHINFO_EXTENSION);
	    $data = file_get_contents($store_path);
	    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		
		$dobj_config['portrait']['type'] = $type;
		$dobj_config['portrait']['source'] = $base64;
		
		// 執行更新
		$DB_SAVE= $this->DBLink->prepare(SQL_AdMeta::UPDATE_METADATA_DATA(array('dobj_json')));
		$DB_SAVE->bindValue(':sid'    	 , $meta['system_id']);
		$DB_SAVE->bindValue(':dobj_json' , json_encode($dobj_config));
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		$result['data']   = $base64;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	
	
	
	
	
	
	
	/* SAMPLE */
	/*******************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	********************************************************************************************************
	*******************************************************************************************************/
	
	
	
	/***== [ 建檔管理模組函數 ] ==***/
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
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		$DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		$DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		$DB_LOGS->bindValue(':source' , json_encode($source));
		$DB_LOGS->bindValue(':update' , json_encode($meta_update));
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
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
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		$DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		$DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		$DB_LOGS->bindValue(':source' , json_encode($source));
		$DB_LOGS->bindValue(':update' , json_encode($meta_update));
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
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
		$DB_LOGS	= $this->DBLink->prepare(SQL_AdMeta::LOGS_META_MODIFY());
		$DB_LOGS->bindValue(':zong' 	  , $meta['zong']);
		$DB_LOGS->bindValue(':sysid' 	  , $meta['system_id']);
		$DB_LOGS->bindValue(':identifier' , $meta['identifier']);
		$DB_LOGS->bindValue(':source' , json_encode($source));
		$DB_LOGS->bindValue(':update' , json_encode($meta_update));
		$DB_LOGS->bindValue(':user' , $this->USER->UserID);
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
	
	
	
	
	
	//-- Finish Photo Upload Task 
	// [input] : FolderId     : [str] metadata.identifter
	// [input] : TimeFlag     : [int] fuploadtimeflag; \d{14}
	public function ADMeta_Upload_Task_Finish_Restore( $FolderId='' , $TimeFlag=''){
	  
	  $result_key = parent::Initial_Result('task');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		  
		// 檢查參數
		if(!preg_match('/^[\w\d\-\_]+$/',$FolderId)  ||  !preg_match('/^\d{14}$/',$TimeFlag)   ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$folder_id = $FolderId;
		$upload_flag = $TimeFlag;
		
		
		// 查詢新上傳檔案
		$DB_PHO = $this->DBLink->prepare(SQL_AdMeta::SELECT_UPLOAD_OBJECT_LIST());
		$DB_PHO->bindValue(':folder', $folder_id); 
		$DB_PHO->bindValue(':flag'	, $upload_flag); 
		$DB_PHO->bindValue(':user'	, $this->USER->UserID); 
		if(!$DB_PHO->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');	
		}
		
		
		// 設定變數
		$meta  = $this->Metadata;
		if(!count($meta)){
		  throw new Exception('_SYSTEM_ERROR_ACCESS_PROCESS_FAIL');	
		}
		
		$dobject_config = json_decode($meta['dobj_json'],true);
		
		
		
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