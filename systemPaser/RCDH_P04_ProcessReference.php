<?php
    
	/*
	處理關聯檔案 20170808  
	SOURCE : metadata & data_type ='archive'
	REFER  : resource_metasimilar
	
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
	
	$folder_map = array( '檔案'=>'ARCHIVE' , '公報'=>'GAZETTE' , '議事錄'=>'RECORD', '議事影音'=>'MEDIA','活動照片'=>'PHOTO','議員傳記'=>'BIOGRAPHY');
	$file_allow = array('jpg','png','tiff','wmv','mp4','mp3');
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'metadata';
	$target_condition = "data_type ='archive'";
	$meta_exist = array();
	
	ob_start();
	
	$db_update = $db->DBLink->prepare("UPDATE metadata SET refer_json=:refer_json WHERE system_id=:system_id;");
	
	try{ 
      
	  // get similar reference 
	  $db_mbr = $db->DBLink->prepare("SELECT * FROM resouce_metasimilar WHERE 1;");
	  $db_mbr->execute();
	  $refer_similar = array();
	  while($tmp = $db_mbr->fetch(PDO::FETCH_ASSOC)){
		if(!isset($refer_similar[$tmp['system_id']])) $refer_similar[$tmp['system_id']] = array();
		$refer_similar[$tmp['system_id']][] = $tmp;
	  }
	  
	  
	  
	  $db_select = $db->DBLink->prepare("SELECT count(*) FROM ".$source_table." WHERE ".$target_condition." ORDER BY system_id ASC;");
       
	  if( !$db_select->execute() ){
		throw new Exception('查無目錄資料');    
	  }
	  
	  $total_count = $db_select->fetchColumn();
	  $paser_count = 0;
	  $limit = 0;
	  $frame = 10000;
	  
	  echo "[PASER] metadata refer paser start : ".$total_count;
	  
	  while($limit < $total_count ){
	  
	      $db_select = $db->DBLink->prepare("SELECT * FROM ".$source_table." WHERE ".$target_condition." ORDER BY system_id ASC LIMIT ".$limit.",".$frame.";");
       
	      if( !$db_select->execute() ){
		    throw new Exception('查無目錄資料');    
	      }  
	      
		  while( $meta = $db_select->fetch(PDO::FETCH_ASSOC) ){
			
			//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
			$paser_count++;
			
			echo "\n".str_pad($paser_count,6,'0',STR_PAD_LEFT).'. '.$meta['system_id']." : ";
			
			//if(isset($meta_exist[$source['StoreNo']])){
			//  echo "skip.";
			//  continue;		  
			//}
			
			
			// 依據不同類型檔案設定搜尋項目
			$source = json_decode($meta['source_json'],true);
			$mrefer = json_decode($meta['refer_json'],true);
			
			switch($meta['zong']){
			  
			  case '檔案': case '議事錄': case '公報': 
			    
				if(!isset($refer_similar[$meta['identifier']])){  // 沒有關聯檔案
				  echo "skip.";
				  continue;
				}
				
				$db_meta = $db->DBLink->prepare("SELECT * FROM metadata WHERE identifier=:identifier;");
				
				foreach($refer_similar[$meta['identifier']] as $similar){
				  
				  // 註冊序號
				  if(!isset($mrefer[$similar['similar_data_id']])) $mrefer[$similar['similar_data_id']] = array();
				  
				  // 取得關聯案資訊
                  $db_meta->execute(array('identifier'=>$similar['similar_data_id']));
				  $rmeta = $db_meta->fetch(PDO::FETCH_ASSOC);
				  $rmeta_search = json_decode($rmeta['search_json'],true);
				  
				  $mrefer[$similar['similar_data_id']]['type']  = $rmeta['zong'];
				  $mrefer[$similar['similar_data_id']]['rate']  = $similar['similar_rate'];
				  $mrefer[$similar['similar_data_id']]['info']  = isset($rmeta_search['abstract_mask']) ? $rmeta_search['abstract_mask'] : $rmeta_search['abstract'];
				  $mrefer[$similar['similar_data_id']]['keep']  = 1;
				  $mrefer[$similar['similar_data_id']]['thumb'] = '';
				  $mrefer[$similar['similar_data_id']]['logs']  = [date('Y-m-d H:i:s') => 'inserted.'];
				
				}
				
				break;
			  
			  case '議事影音': 
			    
				
				
				
				
				break;
			  
			  default: 
				exit(1);
				break;
			}
			
			// 更新 meta
			$db_update->bindValue(':refer_json',json_encode($mrefer,JSON_UNESCAPED_UNICODE));
			$db_update->bindValue(':system_id',$meta['system_id']);
			
			if(!$db_update->execute()){
			  throw new Exception('新增資料更新失敗'); 	
			}
			
			echo " update .".date('c');
			
			ob_flush();
			flush();
		  }
		  
		  $limit+=$frame;  
		  
	  }
	  
    } catch (Exception $e) {
      echo $e->getMessage();
    }	
	
	
	
?>