<?php
    
	/*
	將原始資料轉存詮釋資料 20170803
	省議會史料總庫 - 檔案
	
	SOURCE : source_archive & DataType ='檔案'
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
    
	$meet = array( 'OA'=>'定期大會' , 'IA'=>'成立大會' , 'EA'=>'臨時大會', 'AA'=>'大會');
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'metadata';
	$target_condition = "1";
	$meta_exist = array();
	
	ob_start();
	
	$db_update = $db->DBLink->prepare("UPDATE metadata SET search_json=:search_json,_lockmode=:lockmode,_auditint=:auditint,_open=:open,_view=:view,_index=0 WHERE system_id=:system_id;");
	
	try{ 
      
	  // get member reference 
	  $db_mbr = $db->DBLink->prepare("SELECT mbr_name FROM source_member WHERE 1;");
	  $db_mbr->execute();
	  $member_list = array();
	  while($member = $db_mbr->fetch(PDO::FETCH_ASSOC)){
		$member_list[] = $member['mbr_name'];    
	  }
	  
	  
	  $db_select = $db->DBLink->prepare("SELECT count(*) FROM ".$source_table." WHERE ".$target_condition." ORDER BY system_id ASC;");
       
	  if( !$db_select->execute() ){
		throw new Exception('查無目錄資料');    
	  }
	  
	  $total_count = $db_select->fetchColumn();
	  $paser_coimt = 0;
	  $limit = 0;
	  $frame = 10000;
	  
	  echo "[PASER] metadata search paser start : ".$total_count;
	  
	  while($limit < $total_count ){
	  
	    $db_select = $db->DBLink->prepare("SELECT * FROM ".$source_table." WHERE ".$target_condition." ORDER BY system_id ASC LIMIT ".$limit.",".$frame.";");
       
	    if( !$db_select->execute() ){
		  throw new Exception('查無目錄資料');    
	    }  
	  
	  
		  while( $meta = $db_select->fetch(PDO::FETCH_ASSOC) ){
			
			//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
			$paser_coimt++;
			echo "\n".str_pad($paser_coimt,6,'0',STR_PAD_LEFT).'. '.$meta['system_id']." : ";
			
			//if(isset($meta_exist[$source['StoreNo']])){
			//  echo "skip.";
			//  continue;		  
			//}
			
			// 依據不同類型檔案設定搜尋項目
			
			$source = json_decode($meta['source_json'],true);
			
			$search_field= [   //測試用 
			  'data_type'		=>'',   // 資料類型  archive 議事資料、 傳記 、 照片
			  'zong'			=>'',        // 資料型態  檔案  公報 議事錄 議事影音 議員傳記  活動照片
			  
			  'collection' 		=>'',   // 
			  'identifier' 		=>'',
			  
			  'serial'			=>'', // 資料瀏覽系列  
			  'category_level' 	=>'', // 類別階層
			  'meeting_level' 	=>'', // 會議階層
			  
			  'collection_name'	=>'', // 全宗名稱
			  
			  'date_string'		=>'',   // 日期描述
			  'date_start'		=>'',   // 日期起 for search
			  'date_end'		=>'',   // 日期迄 for search
			  'abstract'		=>'',   // 內容摘要
			  'abstract_mask'	=>'',   // 遮蔽摘要
			  'fulltext'        =>'',   // 全文資料
			  'chairman'		=>'',   // 會議主席
			  'main_mamber'		=>'',   // 主要議員
			  'docno'			=>'',   // 文號 
			  'reference'       =>'',   // 備註 
			  
			  'pageinfo' 		=>'',   // 資料顯示描述
			  
			  'yearrange'		=>[],   // 年代範圍
			  
			  'list_member'		=>[],   // 相關人
			  'list_organ'		=>[],   // 相關單位 
			  'list_location'	=>[],   // 相關地點
			  'list_subject'	=>[],   // 相關主題
			  
			  //提供索引檢索
			  '_flag_secret'	=>'一般',    // 密等設定     // 一般 密 機密 極機密 解密
			  '_flag_privacy'	=>0,    	 // 隱私資料     1/0
			  '_flag_open'		=>1,  	 	 // 公開檢索     1/0 
			  '_flag_mask'		=>0,    	 // 含有遮蔽影像 1/0
			  '_flag_update'	=>0,    	 // 最後更新時間 int
			  '_flag_view'		=>'公開',    // 開放方式     // 公開、限閱、會內、關閉、實體
			];
			
			$search_conf = [];
			
			// 提供系統處理 
			$lockmode   = '';  // 密等狀態
			$auditint   = 0;   // 隱私權註冊
			$open       = 0;
			$view		= '公開';  
			
			switch($meta['zong']){
			  
			  case '檔案':
				
				$meta_dobj    = json_decode($meta['dobj_json'],true);
				$is_mask_flag = (isset($meta_dobj['domask'])&&count($meta_dobj['domask'])) ? 1 : 0;
		        
				
				$search_conf['data_type']   = $meta['data_type'];
				$search_conf['zong']   		= '檔案';
				
				$search_conf['collection']  = $meta['collection'];
				$search_conf['identifier']  = $meta['identifier'];
				
				$search_conf['serial'] 		= '檔案/'.$source['CategoryLevel'];
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
				$parsedate = paser_date($meta_date);
				
				$search_conf['date_start'] = $parsedate['ds'];
				$search_conf['date_end']   = $parsedate['de'];
				$search_conf['yearrange']  = $parsedate['years'];
				$search_conf['abstract']  	= $source['Abstract'];
				$search_conf['abstract_mask']  = $source['AbstractMask'];
				$search_conf['docno']   	 = $source['DocNo'];
				$search_conf['reference']    = $source['Reference'];
				$search_conf['main_mamber'] = $source['Member'];
				$search_conf['pageinfo']  	= '共'.$source['PageCount'].'頁';    
				
				$member_array = [];
				$member_array[] = $source['Member'];
				$member_array[] = $source['MemberOther'];
				$search_conf['list_member']   = paser_person($member_array);
				
				$organ_array = [];
				$organ_array[] = $source['Organ'];
				$organ_array[] = $source['OrganOther'];
				$search_conf['list_organ']    = paser_organ($organ_array);  
				$search_conf['list_location'] = paser_postquery([$source['Location']]);
				$search_conf['list_subject']  = paser_postquery([$source['Subject']]);
				
				$search_conf['_flag_secret']  = $source['_flag_secret'];
				$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
				$search_conf['_flag_open']    = intval($source['_flag_open']);
				$search_conf['_flag_mask']    = intval($is_mask_flag);
				$search_conf['_flag_update']  = 0;
				$search_conf['_flag_view']    = $source['_view'];
				
				$lockmode   = $source['Secret'];
				$auditint   = $source['_flag_privacy'];
				$open       = $source['_flag_open'];
				$view		= $source['_view'];	
				
				break;
			  
			  case '議事錄': case '公報':
                
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
				
				$parsedate = paser_date($meta_date);
				
				$search_conf['date_start'] = $parsedate['ds'];
				$search_conf['date_end'] = $parsedate['de'];
				$search_conf['yearrange'] = $parsedate['years'];
				
				$search_conf['abstract']  = $source['Abstract'];
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
				$search_conf['list_member']   = paser_person($member_array);
				
				$organ_array = [];
				$organ_array[] = $source['Organ'];
				$organ_array[] = $source['PetitionOrgan'];
				$search_conf['list_organ']    = paser_organ($organ_array);  
				
				
				$search_conf['_flag_secret']  = intval($source['_flag_secret']);
				$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
				$search_conf['_flag_open']    = intval($source['_flag_open']);
				$search_conf['_flag_mask']    = 0;
				$search_conf['_flag_update']  = 0;
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
				
				$search_conf['serial'] = '會議/臺灣省議會/'.'第'.getChineseNumber(intval($source['record_period'])).'屆'.'/'.'第'.getChineseNumber(intval($source['record_conf_typeno'])).'次'.$meet[$source['record_conf_type']];
				$search_conf['collection_name'] = '臺灣省議會';
				$search_conf['collection_name'].= '第'.getChineseNumber(intval($source['record_period'])).'屆';
				$search_conf['collection_name'].= '第'.getChineseNumber(intval($source['record_conf_typeno'])).'次';
				$search_conf['collection_name'].= $meet[$source['record_conf_type']];
				$search_conf['meeting_level'] = mb_substr($search_conf['serial'],3);
				
				
				// 確認日期
				$meta_date = array();
				if(strtotime($source['record_date'])){
				  $meta_date[] = $source['record_date'];
				}
				$search_conf['date_string'] = count($meta_date) ? join(' ~ ',$meta_date) : '0000-00-00';
				$parsedate = paser_date($meta_date);
				
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
				$search_conf['list_member']   = paser_person($member_array);
				
				$organ_array = [];
				$organ_array[] = $source['record_organ'];
				$search_conf['list_organ']    = paser_organ($organ_array);  
				$search_conf['list_location']  = paser_postquery([$source['record_place']]);
				$search_conf['list_subject']  = paser_postquery([$source['record_keyword']]);
				
				$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
				$search_conf['_flag_open']    = intval($source['_flag_open']);
				$search_conf['_flag_mask']    = 0;
				$search_conf['_flag_update']  = 0;
				$search_conf['_flag_view']    = $source['_view'];
				
				$lockmode   = '普通';
				$auditint   = $source['_flag_privacy'];
				$open       = $source['_flag_open'];
				$view		= $source['_view'];	
				
				break;
			  
			  case '活動照片': 
			   
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
				
				$parsedate = paser_date($meta_date);
				
				$search_conf['date_start'] = $parsedate['ds'];
				$search_conf['date_end'] = $parsedate['de'];
				$search_conf['yearrange'] = $parsedate['years'];
				
				$search_conf['collection_name']  = $source['Subject'];
				$search_conf['abstract']  = $source['Descrip'];
				
				
				$member = array();
				foreach($member_list as $mbr){
                  if(preg_match('/'.$mbr.'/u',$source['Descrip'])){
					$member[] = $mbr;  
				  }
				}
				
				$year = substr($source['DateStart'],0,4);
				if( intval($year) > 1900 && intval($year) <= date('Y') ){
				  $search_conf['serial'] = '活動照片/'.$year.' 民國'.($year-1911).'年'; 	
				}else{
				  $search_conf['serial'] = '活動照片/none 未知日期'; 	
				}
				
				$search_conf['pageinfo']  = $source['PhotoNo'];    
				
				$search_conf['main_mamber'] = join(';',$member);
				$search_conf['list_member'] = $member;
				
				$search_conf['list_location']  = paser_postquery([$source['PhotoLocation']]);
				
				$search_conf['_flag_privacy'] = intval($source['_flag_privacy']);
				$search_conf['_flag_open']    = intval($source['_flag_open']);
				$search_conf['_flag_mask']    = 0;
				$search_conf['_flag_update']  = 0;
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
				$search_conf['_flag_update']  = 0;
				$search_conf['_flag_view']    = $source['_view'];
				
				$lockmode   = '普通';
				$auditint   = $source['_flag_privacy'];
				$open       = $source['_flag_open'];
				$view		= $source['_view'];	
				
				break;
			  
			  default: 
				exit(1);
				break;
			}
			
			// 更新 meta
			$db_update->bindValue(':lockmode' , $lockmode);
			$db_update->bindValue(':auditint' , $auditint);
			$db_update->bindValue(':open'	  , $open );
			$db_update->bindValue(':view'	  , $view);

			$db_update->bindValue(':search_json',json_encode($search_conf,JSON_UNESCAPED_UNICODE));
			$db_update->bindValue(':system_id',$meta['system_id']);
			
			if(!$db_update->execute()){
			  throw new Exception('新增資料更新失敗'); 	
			}
			echo "update .".date('c');
			
			ob_flush();
			flush();
		  }
		  
		  $limit+=$frame;  
		  
	  }
	  
    } catch (Exception $e) {
      echo $e->getMessage();
    }	
	
	
	// 轉換類別對照
	function map_to_serial($zone,$series,$fuid,$fufuid){
	  global $collection_mapping , $class_map;
	  
	  $class_level = array();
	  
	  if( !count($collection_mapping) || !count($class_map) ){
		return "";  
	  }
	  
	  if(!isset($collection_mapping[$zone])){
		return "";  
	  }
	  
	  $class_level[0] = $collection_mapping[$zone];
	  
	  $pattern = array('/\(/','/\)/','/\//');
	  $replace = array('&#40;','&#41;','&#47;');
	  
	  $class_level[1] = isset($class_map['series'][$series])  ? preg_replace($pattern,$replace,$class_map['series'][$series]) : "-";
	  $class_level[2] = isset($class_map['fuid'][$fuid])      ? preg_replace($pattern,$replace,$class_map['fuid'][$fuid]) : "-";
	  $class_level[3] = isset($class_map['fufuid'][$fufuid])  ? preg_replace($pattern,$replace,$class_map['fufuid'][$fufuid]) : "-";
	  
	  return join('/',$class_level);
	}
    

	      
	// 處理人名	  
	function paser_person($MemberArray){
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
	function paser_organ($OrganArray){
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
	function paser_postquery($FieldArray){
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
	 
		 
		 
	// 處理時間
	function paser_date($DateArray){
      
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
	
	
	
	// 轉國字數字
	function getChineseNumber($num){
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
	
	
	
?>