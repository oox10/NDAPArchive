<?php

  class Record_Model extends Admin_Model{
    
	
	/***--  Function Set --***/
    public function __construct(){
	  parent::__construct();
	  parent::initial_user($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['USER']);
	}
	
	/*[ Record Function Set ]*/ 
    
	//-- Get System Store Count  // 取得系統資料統計  from meta & system_config
	
	public function ADRecord_Get_Store_Count( ){
	  
	  $result_key = parent::Initial_Result('statistics');
	  $result  = &$this->ModelResult[$result_key];
	 
	  try{  
		
		$element_count = [
		  '檔案' => ['collection'=>0,'meta'=>0,'dobj'=>0,'open'=>0],
		  '公報' => ['collection'=>0,'meta'=>0,'dobj'=>0,'open'=>0],
		  '議事錄' => ['collection'=>0,'meta'=>0,'dobj'=>0,'open'=>0],
		  '議事影音' => ['collection'=>0,'meta'=>0,'dobj'=>0,'open'=>0],
		  '議員傳記' => ['collection'=>0,'meta'=>0,'dobj'=>0,'open'=>0],
		  '活動照片' => ['collection'=>0,'meta'=>0,'dobj'=>0,'open'=>0],
		];
		
		// 取得資料數量統計
		$DB_MCOUNT = $this->DBLink->prepare(SQL_AdRecord::GET_META_ZONG_COUNT()); 
		if(! $DB_MCOUNT->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		while($tmp = $DB_MCOUNT->fetch(PDO::FETCH_ASSOC)){
          if(isset($element_count[ $tmp['zong'] ])){
			$element_count[ $tmp['zong'] ]['meta'] = $tmp['COUNT'];  
		  }
		}
		
		$DB_DCOUNT = $this->DBLink->prepare(SQL_AdRecord::GET_META_CONFIG_VALUE()); 
		if(! $DB_DCOUNT->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		while($tmp = $DB_DCOUNT->fetch(PDO::FETCH_ASSOC)){
          if(isset($element_count[ $tmp['label'] ])){
			$element_count[ $tmp['label'] ]['dobj'] = $tmp['setting'];  
		  }
		}
		
		
		$result['data'] = $element_count;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	//-- Get Search Record
	// search_temp / result_history /  result_visit  / user_access
	// [input] : $DateFrom :   date
    // [input] : $DateTo :   date
	public function ADRecord_Get_Search_Record($DateFrom = '', $DateTo='' ){
	  
	  $result_key = parent::Initial_Result('search');
	  $result  = &$this->ModelResult[$result_key];
	 
	  try{  
		
		// 設定日期範圍
		$record_range['date_start'] = strtotime($DateFrom) ? date('Y-m-01',strtotime($DateFrom)) :  date("Y-m-01");
		$record_range['date_end']   = strtotime($DateTo)   ? date('Y-m-t',strtotime($DateTo))    :  date("Y-m-t");
		         
		// 設定資料容器
		$chart_data_pattern=[];  //日期資料儲存
		$chart_range_tages=[];    //x軸標籤顯示
		$chart_range_tages_position=[]; //x軸標籤顯示位置
		
		// 設定tag length
		$tag_range = 'week';
		$date1 = new DateTime($record_range['date_start']);
		$date2 = new DateTime($record_range['date_end'] );
        $diff = date_diff($date2,$date1);
		if($diff->days <= 40 ){
		  $tag_range = 'week';	
		}else if($diff->days > 40 && $diff->days <= 130  ){
		  $tag_range = 'month';	
		}else if($diff->days > 130 && $diff->days <= 365  ){
		  $tag_range = 'session';	
		}else{
		  $tag_range = 'year';		
		}
		
		$start_date_time = strtotime($record_range['date_start'].' 00:00:00');
		$position = 0;
		
		do{ 
          
		  $chart_data_pattern[date('Y-m-d',$start_date_time)] = 0;		 
		  $chart_range_tages[] = date('Y-m-d',$start_date_time).', '.date('D',$start_date_time);   
		  
		  switch($tag_range){
			case 'week':  
			  if(date('N',$start_date_time)==1){
			    $chart_range_tages_position[] = $position;
		      }
              break;
            case 'month':
              if(date('d',$start_date_time)=='01'){
			    $chart_range_tages_position[] = $position;
		      }
              break;
            case 'session':
              if( intval(date('m',$start_date_time))%3==1 && date('d',$start_date_time)=='01'){
			    $chart_range_tages_position[] = $position;
		      }
              break;
			default:
              if(date('md',$start_date_time)=='0101'){
			    $chart_range_tages_position[] = $position;
		      }
              break;			  
		  }
		  
		  
		  $start_date_time = strtotime('+1 day',$start_date_time);
		  $position++;
		}while($start_date_time < strtotime($record_range['date_end'].' 23:59:59'));
			
        $search_chart=[
		  'totalchart'=>$chart_data_pattern,
		  'memberchart'=>$chart_data_pattern,
		  'guestschart'=>$chart_data_pattern,
		];  
        $member_list = [];         
		
		$search_terms = [];
		$search_zongs = [];
		
		$search_record = [];
		
		// 取得資料庫搜尋紀錄
		$DB_SEARCH = $this->DBLink->prepare(SQL_AdRecord::GET_SEARCH_RECORD_BY_DATE()); 
		$DB_SEARCH->bindValue(':date_start',$record_range['date_start']);
		$DB_SEARCH->bindValue(':date_end',$record_range['date_end']);
        if(! $DB_SEARCH->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 搜尋次數與帳號類型
		while($tmp = $DB_SEARCH->fetch(PDO::FETCH_ASSOC)){
		  $record_date = substr($tmp['Action_DateTime'],0,10);
		  $search_chart['totalchart'][$record_date]++;  
		  if(preg_match('/^[\w\d]{8}\-\d$/',$tmp['User_Access_ID'])){
			$search_chart['guestschart'][$record_date]++;    
		  }else{
			$search_chart['memberchart'][$record_date]++;    
		    $member_list[] = $tmp['User_Access_ID'];
		  }
		  $query_pattern = explode('⊕',$tmp['Query_Term_Set']);
		  foreach($query_pattern as $query_set){
			$search = explode(':',$query_set);
            if($search['0']=='zong'){
			  $search_zongs = array_merge($search_zongs,explode('|',$search['1']));
			}else if($search['0']=='data_type'){
              switch($search['1']){
				case 'archive':    $search_zongs = array_merge($search_zongs,['檔案','公報','議事錄','議事影音']); break;  
				case 'photo':      $search_zongs = array_merge($search_zongs,['活動照片']); break;
                case 'biography':  $search_zongs = array_merge($search_zongs,['議員傳記']); break;   				
			  } 
			}else{
			  $search_terms = array_merge($search_terms,explode('|',$search['1']));	
			}
		  }
		  
		  $search_record[] = [
		    'time'=>$tmp['Action_DateTime'],
			'user'=> preg_match('/^[\w\d]{8}\-\d$/',$tmp['User_Access_ID']) ? '訪客' : $tmp['User_Access_ID'],
			'query'=> $tmp['Query_Term_Set'],
		  ];
		  
		}
		
		// 整理搜尋條件
		$search_zong_data = array_count_values( $search_zongs);
		$search_term_data = array_count_values( $search_terms);
		
		
		$search_chart['zongschart'] = [
		  isset($search_zong_data['檔案']) ? $search_zong_data['檔案']: 0,
		  isset($search_zong_data['公報']) ? $search_zong_data['公報']: 0,
		  isset($search_zong_data['議事錄']) ? $search_zong_data['議事錄']: 0,
		  isset($search_zong_data['議事影音']) ? $search_zong_data['議事影音']: 0,
		  isset($search_zong_data['活動照片']) ? $search_zong_data['活動照片'] : 0,
		  isset($search_zong_data['議員傳記']) ? $search_zong_data['議員傳記']: 0
		];
		
		rsort($search_term_data);
		
		
		
		// 整理圖表統計資料
		foreach($search_chart as $index=>$chartarray){
		  $search_chart[$index]=array_values($chartarray);
		}
		
		// 取得人員統計
		$member_access_count = array_count_values($member_list);
		$member_account_list = array_keys($member_access_count);
		
		$DB_MEMBER = $this->DBLink->prepare(SQL_AdRecord::GET_SEARCH_RECORD_MEMBER_INFO($member_account_list)); 
		if(! $DB_MEMBER->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		//user_name,user_staff,user_organ,user_education,user_major
		$member_record=[
		  'staff'=>[],
		  'education'=>[],
		  'major'=>[],
		  'organ'=>[],
		];
		
		// 整理使用者特徵
		while($tmp = $DB_MEMBER->fetch(PDO::FETCH_ASSOC)){
		  
		  $staffs = array_filter(explode(';',$tmp['user_staff'])); 
		  foreach($staffs as $term){
			if(!isset($member_record['staff'][$term])){
			  $member_record['staff'][$term] = 0; 	
			}
			$member_record['staff'][$term] += (isset($member_access_count[$tmp['user_id']]) ? intval($member_access_count[$tmp['user_id']]):0 );
		  }
		  
		  $educations = array_filter(explode(';',$tmp['user_education'])); 
		  foreach($educations as $term){
			if(!isset($member_record['education'][$term])){
			  $member_record['education'][$term] = 0; 	
			}
			$member_record['education'][$term] += (isset($member_access_count[$tmp['user_id']]) ? intval($member_access_count[$tmp['user_id']]):0 );
		  }
		  
		  $majors = array_filter(explode(';',$tmp['user_major'])); 
		  foreach($majors as $term){
			if(!isset($member_record['major'][$term])){
			  $member_record['major'][$term] = 0; 	
			}
			$member_record['major'][$term] += (isset($member_access_count[$tmp['user_id']]) ? intval($member_access_count[$tmp['user_id']]):0 );
		  }
		  
		  $organs = array_filter(explode(';',$tmp['user_organ'])); 
		  foreach($organs as $term){
			if(!isset($member_record['organ'][$term])){
			  $member_record['organ'][$term] = 0; 	
			}
			$member_record['organ'][$term] += (isset($member_access_count[$tmp['user_id']]) ? intval($member_access_count[$tmp['user_id']]):0 );
		  }
		}
		
	    // 整理條件
		$member_chart = array();
		foreach($member_record as $chart_type => $chart_data){
		  $member_chart[$chart_type] = [];
		  foreach($chart_data as $term=>$count){
			$member_chart[$chart_type][] = [
			  'name'=>$term,
			  'y'=>$count
			];
		  }
		}
		
		$result['data']['filter'] = $record_range;
		$result['data']['list']   = array_slice(array_reverse($search_record),0,1000);
		$result['data']['count']   = count($search_record);
		$result['data']['chart']=[
		  'config'=>['tags'=>$chart_range_tages,'position'=>$chart_range_tages_position],
		  'search'=>$search_chart,
		  'member'=>$member_chart, 
		];
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	
	
	/*[ Record Function Set ]*/ 

	//-- Get Search Record
	// search_temp / result_history /  result_visit  / user_access
	// [input] : $DateFrom :   date
    // [input] : $DateTo :   date
	public function ADRecord_Get_Access_Record($DateFrom = '', $DateTo='' ){
	  
	  $result_key = parent::Initial_Result('access');
	  $result  = &$this->ModelResult[$result_key];
	 
	  try{  
		
		// 設定日期範圍
		$record_range['date_start'] = strtotime($DateFrom) ? date('Y-m-01',strtotime($DateFrom)) :  date("Y-m-01");
		$record_range['date_end']   = strtotime($DateTo)   ? date('Y-m-t',strtotime($DateTo))    :  date("Y-m-t");
		         
		// 設定資料容器
		$chart_data_pattern=[];  //日期資料儲存
		$chart_range_tages=[];    //x軸標籤顯示
		$chart_range_tages_position=[]; //x軸標籤顯示位置
		
		$start_date_time = strtotime($record_range['date_start'].' 00:00:00');
		$position = 0;
		do{ 
          $chart_data_pattern[date('Y-m-d',$start_date_time)] = 0;		 
		  $chart_range_tages[] = date('Y-m-d',$start_date_time).', '.date('D',$start_date_time);   
		  
		  if(date('N',$start_date_time)==1){
			$chart_range_tages_position[] = $position;
		  }
		  
		  $start_date_time = strtotime('+1 day',$start_date_time);
		  $position++;
		}while($start_date_time < strtotime($record_range['date_end'].' 23:59:59'));
			
        
        $member_list = [];         
		
		// 取得資料庫搜尋紀錄
		$DB_SEARCH = $this->DBLink->prepare(SQL_AdRecord::GET_SEARCH_RECORD_BY_DATE()); 
		$DB_SEARCH->bindValue(':date_start',$record_range['date_start']);
		$DB_SEARCH->bindValue(':date_end',$record_range['date_end']);
        if(! $DB_SEARCH->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		$access_chart=[
		  'totalchart'=>$chart_data_pattern,
		  '檔案'=>$chart_data_pattern,
		  '議事錄'=>$chart_data_pattern,
		  '公報'=>$chart_data_pattern,
		  '影音'=>$chart_data_pattern,
		  '照片'=>$chart_data_pattern,
		  '其他'=>$chart_data_pattern
		];
		
		// 取得資料庫搜尋紀錄
		$DB_ACCESS = $this->DBLink->prepare(SQL_AdRecord::GET_ACCESS_RECORD_BY_DATE()); 
		$DB_ACCESS->bindValue(':date_start',$record_range['date_start']);
		$DB_ACCESS->bindValue(':date_end',$record_range['date_end']);
        if(! $DB_ACCESS->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 搜尋存取數量
		while($tmp = $DB_ACCESS->fetch(PDO::FETCH_ASSOC)){
		  $record_date = substr($tmp['Creat_Time'],0,10);
		  $access_chart['totalchart'][$record_date]++;  
		  if(isset($access_chart[$tmp['zong']])){
			$access_chart[$tmp['zong']][$record_date]++;  
		  }else{
			$access_chart['其他'][$record_date]++;  
		  }
		  
		  if(!preg_match('/^[\w\d]{8}\-\d$/',$tmp['User_Name'])){
			 $member_list[] = $tmp['User_Name'];
		  } 
		}
		
		foreach($access_chart as $index=>$chartarray){
		  $access_chart[$index]=array_values($chartarray);
		}
		
		$result['data']['filter'] = $record_range;
		$result['data']['chart']=[
		  'config'=>['tags'=>$chart_range_tages,'position'=>$chart_range_tages_position],
		  'access'=>$access_chart, 
		];
		
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	  
	}
	
	
	//-- Admin Record Export Search Logs 
	// [input] : $DateFrom :   date
    // [input] : $DateTo :   date
	public function ADRecord_Export_Search_Logs($DateFrom = '', $DateTo=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	 
	  try{  
		
		// 設定日期範圍
		$record_range['date_start'] = strtotime($DateFrom) ? date('Y-m-01',strtotime($DateFrom)) :  date("Y-m-01");
		$record_range['date_end']   = strtotime($DateTo)   ? date('Y-m-t',strtotime($DateTo))    :  date("Y-m-t");
		         
		// 取得資料庫搜尋紀錄
		$DB_SEARCH = $this->DBLink->prepare(SQL_AdRecord::GET_SEARCH_RECORD_BY_DATE()); 
		$DB_SEARCH->bindValue(':date_start',$record_range['date_start']);
		$DB_SEARCH->bindValue(':date_end',$record_range['date_end']);
        if(! $DB_SEARCH->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		$search_logs = $DB_SEARCH->fetchAll(PDO::FETCH_ASSOC);
		$result['data']['excel'][] = $search_logs;
		$result['data']['fname'] = _SYSTEM_NAME_SHORT.'_SearchLogsExport_'.date('Ymd');
    	$result['data']['title'] = '檢索系統紀錄-'.$record_range['date_start'].'~'.$record_range['date_end'];
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Record account action logs 
	// [input] : $DateFrom :   date
    // [input] : $DateTo :   date
	public function ADRecord_Get_Account_Logs( $DateFrom = '', $DateTo='' ){
	  
	  $result_key = parent::Initial_Result('syslogs');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 設定日期範圍
	    $record_range['date_start'] = strtotime($DateFrom) ? date('Y-m-d',strtotime($DateFrom)) :  date("Y-m-d");
	    $record_range['date_end']   = strtotime($DateTo)   ? date('Y-m-d',strtotime($DateTo))   :  date("Y-m-d");
		
		$date1 = new DateTime($record_range['date_start']);
		$date2 = new DateTime($record_range['date_end'] );
        $diff = date_diff($date2,$date1);
		if($diff->days > 7 ){
		  $record_range['date_end'] = date('Y-m-d',strtotime('+7 day',strtotime($record_range['date_start'])));
		}
	  
	    $DB_OBJ = $this->DBLink->prepare(SQL_AdRecord::GET_SYSTEM_LOGS());
	    $DB_OBJ->bindValue(':date_start'   , $record_range['date_start'].' 00:00:00' , PDO::PARAM_STR);	
	    $DB_OBJ->bindValue(':date_end'     , $record_range['date_end'].' 23:59:59'	, PDO::PARAM_STR); 
		
		// 查詢資料庫
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$act_logs = array();
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  $act_logs[] = $tmp;
        }
		
		$result['data']['filter'] = $record_range;
		$result['data']['list']   = $act_logs;
		$result['data']['count']  = count($act_logs);
		$result['action'] = true;	
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Record Export System Logs 
	// [input] : $DateFrom :   date
    // [input] : $DateTo :   date
	public function ADRecord_Export_System_Logs($DateFrom = '', $DateTo=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	 
	  try{  
		
		// 設定日期範圍
		$record_range['date_start'] = strtotime($DateFrom) ? date('Y-m-01',strtotime($DateFrom)) :  date("Y-m-01");
		$record_range['date_end']   = strtotime($DateTo)   ? date('Y-m-t',strtotime($DateTo))    :  date("Y-m-t");
		         
		// 取得資料庫搜尋紀錄
		$DB_LOGS = $this->DBLink->prepare(SQL_AdRecord::GET_SYSTEM_LOGS()); 
		$DB_LOGS->bindValue(':date_start',$record_range['date_start']);
		$DB_LOGS->bindValue(':date_end',$record_range['date_end']);
        if(! $DB_LOGS->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		$system_logs = $DB_LOGS->fetchAll(PDO::FETCH_ASSOC);
		$result['data']['excel'][] = $system_logs;
		$result['data']['fname'] = _SYSTEM_NAME_SHORT.'_SearchLogsExport_'.date('Ymd');
    	$result['data']['title'] = '系統活動紀錄-'.$record_range['date_start'].'~'.$record_range['date_end'];
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
  }
  
  
?>