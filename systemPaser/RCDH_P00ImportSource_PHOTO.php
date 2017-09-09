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
	
	$file_path = './rawdata/ndap_photo_meta_20170829.xls';
	
	try{ 
      
	 
	  if(!is_file($file_path)){
		throw new Exception($file_path.': File Not Exist.');  
	  }
	  
	  $db_insert = $db->DBLink->prepare("INSERT INTO source_photo VALUES (NULL,:FolderNo,:SubjectNo,:PhotoNo,:StoreNo,:DateStart,:DateEnd,:Subject,:Descrip,:PhotoLocation,:Creater,:Note,:Identify,'會內',0,0,1,'臺灣省議會時期104.11.23.xls','".date('Y-m-d H:i:s')."','RCDH','0000-000-00 00:00:00','',1);");
	 
	  $excelReader = PHPExcel_IOFactory::createReaderForFile($file_path);
	  $excelReader->setReadDataOnly(true);
	  $objPHPExcel = $excelReader->load($file_path);
		 
	  $excel_sheet_num = $objPHPExcel->getSheetCount();
	  $excel_sheet_names = $objPHPExcel->getSheetNames();
	  
	  $counter = 0;
	  
	  for($sheet=0;$sheet<$excel_sheet_num;$sheet++){
		  
		  echo $sheet.'-';
		  
		  $objSheet=$objPHPExcel->getSheet($sheet);
		  
		  $row=3;
		  echo trim($objSheet->getCellByColumnAndRow(0,$row)->getValue());
		  
		  while( trim($objSheet->getCellByColumnAndRow(0,$row)->getValue()) ){
			
			echo "\n".$counter.':';
			$db_insert->bindValue(':FolderNo',trim($objSheet->getCellByColumnAndRow(0,$row)->getValue()));
			$db_insert->bindValue(':SubjectNo',trim($objSheet->getCellByColumnAndRow(1,$row)->getValue()));
			$db_insert->bindValue(':PhotoNo',trim($objSheet->getCellByColumnAndRow(2,$row)->getValue()));
			$db_insert->bindValue(':StoreNo',trim($objSheet->getCellByColumnAndRow(3,$row)->getValue()));
			
			$year  = intval(trim($objSheet->getCellByColumnAndRow(4,$row)->getValue())) ? 1911+intval(trim($objSheet->getCellByColumnAndRow(4,$row)->getValue())) : '0000';
			
			$dates = trim($objSheet->getCellByColumnAndRow(5,$row)->getValue());
			$datee   = trim($objSheet->getCellByColumnAndRow(6,$row)->getValue());
			
			$date_start = preg_match('/\d+\.\d+/',$dates)  ?   preg_replace('/\./','-',$dates) : '00-00';
			$date_end   = preg_match('/\d+\.\d+/',$datee)  ?   preg_replace('/\./','-',$datee) : '00-00';
			
			$db_insert->bindValue(':DateStart',$year.'-'.$date_start);
			$db_insert->bindValue(':DateEnd',$year.'-'.$date_end);
			$db_insert->bindValue(':Subject',trim($objSheet->getCellByColumnAndRow(7,$row)->getValue()));
			$db_insert->bindValue(':Descrip',preg_replace('/\s+/',';',trim($objSheet->getCellByColumnAndRow(8,$row)->getValue())));
			$db_insert->bindValue(':PhotoLocation',trim($objSheet->getCellByColumnAndRow(9,$row)->getValue()));
			$db_insert->bindValue(':Creater',trim($objSheet->getCellByColumnAndRow(10,$row)->getValue()));
			$db_insert->bindValue(':Note',trim($objSheet->getCellByColumnAndRow(11,$row)->getValue()));
			
			if(intval($objSheet->getCellByColumnAndRow(12,$row)->getValue())){
			  $identify = '全部識別';
			}else if(intval($objSheet->getCellByColumnAndRow(13,$row)->getValue())){
			  $identify = '部分識別';	
			}else{
			  $identify = '無法識別';	 	
			}
			
			$db_insert->bindValue(':Identify',$identify);
			
			if(!$db_insert->execute()){
			  throw new Exception('新增資料失敗'); 	
			}
			
			echo "done.";
			$row++;
			$counter++;
		  
		  }  
		  
		  
	  }
	  
	  /*
	  // 整理task    1.所有資料標記為 _finish  2.task 標記為 _FINISH  3. 計算資料數量
	  foreach($task_queue as $taskid=>$count){
		//設定狀態
		$db->DBLink->query("UPDATE meta_builtitem SET _estatus='_finish' WHERE taskid='".$taskid."' AND _keep=1;");
		$db->DBLink->query("UPDATE meta_builttask SET element_count=(SELECT count(*) FROM meta_builtitem WHERE taskid='".$taskid."' AND _keep=1 AND _estatus='_finish'),_status='_FINISH' WHERE task_no='".$taskid."' AND _keep=1;");  
	  }
	  */
	  
	  $objPHPExcel->disconnectWorksheets();  
	  unset($objPHPExcel);
    
	} catch (Exception $e) {
      echo $e->getMessage();
    }	
	
?>