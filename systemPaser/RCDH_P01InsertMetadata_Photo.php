<?php
    
	/*
	將原始資料轉存詮釋資料 20170803
	省議會史料總庫 - 檔案
	
	SOURCE : source_archive & DataType ='檔案'
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
    
	$meet = array( 'OA'=>'定期大會' , 'IA'=>'成立大會' , 'EA'=>'臨時大會');
	
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'source_photo';
	$target_condition = "1";
	$meta_exist = array();
	
	$db_insert = $db->DBLink->prepare("INSERT INTO metadata VALUES (NULL,:data_type,:zong,:collection,:identifier,:applyindex,:source_json,:search_json,:dobj_json,:refer_json,:page_count,NULL,'RCDHPaser','PhotoServer','".date('Y-m-d H:i:s')."',:lockmode,:auditint,:checked,:digited,:open,:view,0,1);");
	
	try{ 
      
	  $db_select = $db->DBLink->prepare("SELECT * FROM ".$source_table." WHERE ".$target_condition." ORDER BY  pno ASC;");
       
	  if( !$db_select->execute() ){
		throw new Exception('查無目錄資料');    
	  }
	  
	  while( $source = $db_select->fetch(PDO::FETCH_ASSOC) ){
		
		//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
		
		echo "\n".$source['pno']." : ";
		
		$collection_id = $source['FolderNo'];
		$element_id    = $source['StoreNo'];
		
		if(isset($meta_exist[$element_id])){
		  echo "skip.";
          continue;		  
		}
		
		// 初步整編
		$db_insert->bindValue(':data_type','photo');
		$db_insert->bindValue(':zong', '活動照片');
	    $db_insert->bindValue(':collection'	,$collection_id);
	    $db_insert->bindValue(':identifier'	,$element_id);
		$db_insert->bindValue(':applyindex'	,$element_id);
		$db_insert->bindValue(':source_json',json_encode($source,JSON_UNESCAPED_UNICODE));
	    $db_insert->bindValue(':search_json','[]');
	    $db_insert->bindValue(':dobj_json'	,'[]');
	    $db_insert->bindValue(':refer_json'	,'[]');
		$db_insert->bindValue(':page_count'	,1);
		$db_insert->bindValue(':lockmode'	,'普通');
	    $db_insert->bindValue(':auditint'	,0);
	    $db_insert->bindValue(':checked'	,0);
		$db_insert->bindValue(':digited'	,1);
		$db_insert->bindValue(':open'		,1);
	    $db_insert->bindValue(':view'		,'會內');
		
		if(!$db_insert->execute()){
		  throw new Exception('新增資料失敗'); 	
		}
		
		$system_id = $db->DBLink->lastInsertId();
		echo $system_id." >> ";
	  }
	  
	  
    } catch (Exception $e) {
      echo $e->getMessage();
    }
	
?>