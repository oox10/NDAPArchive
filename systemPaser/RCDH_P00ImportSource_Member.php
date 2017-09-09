<?php
    
	/*
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
    require_once(dirname(dirname(__FILE__)).'/mvc/lib/PHPExcel-1.8/Classes/PHPExcel.php');
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$file_path = './rawdata/member_excels/';
	
	try{ 
      
	  $db_insert = $db->DBLink->prepare("INSERT INTO source_member VALUES (:mbrno,:mbr_set,:mbr_name,:mbr_time,:mbr_offer,:mbr_history,:mbr_refer,'[]','[]','開放',0,0,1,1,:source,'".date('Y-m-d H:i:s')."','RCDH','0000-000-00 00:00:00','',1) ON duplicate key UPDATE mbr_set=:mbr_set,mbr_time=:mbr_time,mbr_offer=:mbr_offer,mbr_history=:mbr_history,mbr_refer=:mbr_refer,_sourcefrom=:source;");
     
	  if(!is_dir($file_path)){
		throw new Exception($file_path.': Folder Not Exist.');  
	  }
	  
	  $historys = array_slice(scandir($file_path),2);
	  
	  foreach($historys as $hisxls){
		
        if(!is_file($file_path.$hisxls)){
		  throw new Exception($file_path.$hisxls.': File Not Exist.');  
	    }
        
		
		$excelReader = PHPExcel_IOFactory::createReaderForFile($file_path.$hisxls);
		$excelReader->setReadDataOnly(true);
		$objPHPExcel = $excelReader->load($file_path.$hisxls);
			 
		$excel_sheet_num = $objPHPExcel->getSheetCount();
		$excel_sheet_names = $objPHPExcel->getSheetNames();
		  
		  
		$counter = 0;
		  
		for($sheet=0;$sheet<$excel_sheet_num;$sheet++){
			  
		  echo $hisxls.':';
			  
		  $objSheet=$objPHPExcel->getSheet($sheet);
		  
		  
		  $row=2;
		  
		  while( trim($objSheet->getCellByColumnAndRow(2,$row)->getValue()) ){
			
			//:mbr_set,:mbr_name,:mbr_time,:mbr_offer,:mbr_history,:mbr_refer :source
			
			$mbr_name = trim($objSheet->getCellByColumnAndRow(2,$row)->getValue());
			$mbr_time = trim($objSheet->getCellByColumnAndRow(3,$row)->getValue());
			$mbr_offer= trim($objSheet->getCellByColumnAndRow(4,$row)->getValue());
			$mbr_history = trim($objSheet->getCellByColumnAndRow(5,$row)->getValue());
            $mbr_refer = trim($objSheet->getCellByColumnAndRow(6,$row)->getValue());
            
			echo "\n".$counter.'. ['.$mbr_name.'] : ';	
				
			$db_insert->bindValue(':mbrno', $counter);
			$db_insert->bindValue(':mbr_set', '議員傳記');
			$db_insert->bindValue(':mbr_name', $mbr_name);
			$db_insert->bindValue(':mbr_time', $mbr_time);
			$db_insert->bindValue(':mbr_offer', preg_replace('/\s+臺/',', 臺',$mbr_offer) );
			$db_insert->bindValue(':mbr_history', nl2br($mbr_history) );
			$db_insert->bindValue(':mbr_refer', nl2br($mbr_refer) );
			$db_insert->bindValue(':source',$excel_sheet_names[0] );
			
			if(!$db_insert->execute()){
			  throw new Exception('新增資料失敗'); 	
			}
				
			echo "done.";
			$row++;
			$counter++;  
		  }
	    }
	  }
	  
	  $objPHPExcel->disconnectWorksheets();  
	  unset($objPHPExcel);
    
	} catch (Exception $e) {
      echo $e->getMessage();
    }	
	
?>