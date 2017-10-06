<?php

  class Project_Model extends Admin_Model{
    
	
	/***--  Function Set --***/
    public function __construct(){
	  parent::__construct();
	  parent::initial_user($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['USER']);
	}
	
	/*[ Project Function Set ]*/ 
	
	
	
	
	//-- Admin Project Page Initial 
	// [input] : NULL;
	public function ADProject_Project_List(){
	  
	  $result_key = parent::Initial_Result('records');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		$handler_map = array();
		$DB_OBJ = $this->DBLink->prepare(SQL_AdProject::SELECT_ALL_PROJECT());
		
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		// 取得申請資料
		$projects = array();
		$projects = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);		
	    
		foreach($projects as &$data){
		  
		  // 設定申請資料
		  $folder_items = json_decode($data['pjelements'],true);
		  
		  $data['@count']      =  count($folder_items);
		  
		  // 檢查狀態
		  $status_info = ''; // 儲存狀態訊息 
          switch( (string)$data['_status'] ){
		    case '_initial': $status_info = '新建專案';    break;
		    case '_process': $status_info = '資料處理中';  break;
			case '_finish': $status_info  = '準備就緒';    break;
			default: $status_info = '未知狀態';   break;
		  }
		  $data['@status_info'] = $status_info;
		  
		}
		$result['action'] = true;		
		$result['data']   = $projects;		
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	//-- Admin Project Get Apply Data 
	// [input] : spno  :  \d+;
	public function ADProject_Get_Project_Data($DataNo=0){
	  $result_key = parent::Initial_Result('record');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查使用者序號
	    if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 取得資料
		$project = array();
		$DB_GET	= $this->DBLink->prepare( SQL_AdProject::GET_PROJECT_DATA() );
		$DB_GET->bindParam(':spno'   , $DataNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$tmp = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 處理內容
		$project['spno'] = $tmp['spno'];
		$project['pjname'] = $tmp['pjname'];
		$project['pjinfo'] = $tmp['pjinfo'];
		$project['user_name']  = $tmp['user_name'];
		$project['_update'] = $tmp['_update'];
		$project['_status'] = $tmp['_status'];
		$project['elements'] = json_decode($tmp['pjelements'],true);
		
		// final
		$result['action'] = true;
		$result['data'] = $project;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	//-- Admin Project Package Project Data 
	// [input] : DataNo       :  \d+  spno  ;
	// [input] : PaserString  :  (file name array).encode ,...;
	public function ADProject_Export_Project_Elements($DataNo=0,$PaserString=''){
	  
	  $result_key = parent::Initial_Result('file');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    $data_list    = json_decode(base64_decode(rawurldecode($PaserString)),true);
		$data_list    =  is_array( $data_list) ?   $data_list : [];
		
		
		// 取得資料
		$project = array();
		$DB_GET	= $this->DBLink->prepare( SQL_AdProject::GET_PROJECT_DATA() );
		$DB_GET->bindParam(':spno'   , $DataNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$project = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		$package_key    = _SYSTEM_NAME_SHORT.'_PJExport_'.date('YmdHis');
		$package_folder = _SYSTEM_DIGITAL_FILE_PATH.'PROJECT/'.str_pad($DataNo,5,'0',STR_PAD_LEFT).'/export/';
		if(!is_dir($package_folder)) mkdir($package_folder,0777,true);
		
		$package_zip = $package_folder.$package_key.'.zip';
		$package_file=0;
		
		// 建立zip檔案
		$zip_sys = new ZipArchive();
        if ($zip_sys->open($package_zip, ZipArchive::CREATE)!==TRUE) {
		 throw new Exception('_SYSTEM_ERROR_ZIP_INITIAL_FAIL');
		}
		
		
		// 處理內容
		$project_elements = json_decode($project['pjelements'],true);
		
		//打包輸出項目
		foreach($project_elements as $filename => $fileinfo){
		  if(count($data_list)){
			if(!in_array($filename,$data_list)){
			  continue;		
			}  
		  }
          
		  if($fileinfo['status']!='_import'){
			continue;  
		  }
		  
		  $file_path = _SYSTEM_DIGITAL_FILE_PATH.$fileinfo['path'].'saved/'.$filename;
		  $zip_sys->addFile( $file_path, $filename);
		  $package_file++;	
		}
		
		$zip_sys->setCompressionIndex(0, ZipArchive::CM_STORE);// 不要進行壓縮，只打包成ZIP
		//echo "加入壓縮檔的檔案數(numfiles): " . $zip->numFiles . "\n";
		//echo "壓縮檔狀態(status):" . $zip->status . "\n";//
		
		if($zip_sys->status){
	      throw new Exception('_SYSTEM_ERROR_ZIP_ERROR:'.$zip_sys->status); 		
		}
		
		$zip_sys->close();//關閉壓縮檔
		
		// 回存專案
		$DB_UPD	= $this->DBLink->prepare( SQL_AdProject::UPDATE_PROJECT_META(array('download_times','package_link')) );
		$DB_UPD->bindParam(':spno'   , $DataNo , PDO::PARAM_INT);	
		$DB_UPD->bindValue(':package_link'   , $package_key);
		$DB_UPD->bindValue(':download_times'   , ($project['download_times']+1));
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_UPDATE_FAIL');
		}
		
		// final 
		$result['data']['name']  = $package_key.'_'.$project['pjname'].'匯出('.$package_file.')_'.'.zip';
		$result['data']['size']  = filesize($package_zip);
		$result['data']['location']  = $package_zip ;
	    $result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;  
	}
	
	//-- Admin Project Package Project Data 
	// [input] : DataNo     :  \d+  spno  ;
	// [input] : FileNname  :  str  file name;
	public function ADProject_Remove_Project_Item($DataNo=0,$FileNname=''){
	  
	  $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查序號
	    if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	     
		// 取得資料
		$project = array();
		$DB_GET	= $this->DBLink->prepare( SQL_AdProject::GET_PROJECT_DATA() );
		$DB_GET->bindParam(':spno'   , $DataNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$project = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 處理內容
		$project_elements = json_decode($project['pjelements'],true);
		
		if(!isset($project_elements[$FileNname])){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');		
		}  
		
		unset($project_elements[$FileNname]);
		
		// 回存專案
		$DB_UPD	= $this->DBLink->prepare( SQL_AdProject::UPDATE_PROJECT_META(array('pjelements')) );
		$DB_UPD->bindParam(':spno'   , $DataNo , PDO::PARAM_INT);	
		$DB_UPD->bindValue(':pjelements'   , json_encode($project_elements));
		if( !$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_UPDATE_FAIL');
		}
		
		// final 
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;  
	}
	
	//-- Admin Project Save Data 
	// [input] : DataNo    :  \d+  
	// [input] : DataModify  :   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	public function ADProject_Save_Project_Data( $DataNo=0 , $DataModify='' ){
	  
	  $result_key = parent::Initial_Result('save');
	  $result  = &$this->ModelResult[$result_key];
	  
	  $data_modify = json_decode(base64_decode(rawurldecode($DataModify)),true);
	  
	  try{  
		
		// 檢查使用者序號
	    if( (!preg_match('/^\d+$/',$DataNo) && $DataNo != '_addnew' ) || !is_array($data_modify)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if($DataNo == '_addnew'){
		  
		  $DB_NEWA	= $this->DBLink->prepare(SQL_AdProject::INSERT_PROJECT_META());
		  $DB_NEWA->bindValue(':pjname' , $data_modify['pjname']);
		  $DB_NEWA->bindValue(':pjinfo' , $data_modify['pjinfo']);
		  $DB_NEWA->bindValue(':user' , $this->USER->UserNO);
		  
		  if( !$DB_NEWA->execute()){
			throw new Exception('_SYSTEM_ERROR_DB_UPDATE_FAIL');
	      }
		  
		  $DataNo = $this->DBLink->lastInsertId(); 
		  
		}else{
		  if($data_modify && count($data_modify)){
		    // 執行更新
		    $DB_SAVE	= $this->DBLink->prepare(SQL_AdProject::UPDATE_PROJECT_META(array_keys($data_modify)));
		    $DB_SAVE->bindValue(':spno' , $DataNo);
		    foreach($data_modify as $mf => $mv){
			  $DB_SAVE->bindValue(':'.$mf , $mv);
		    }
		    if( !$DB_SAVE->execute()){
			  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
	        }
		  }	
		}
		
		// final 
		$result['data'] = $DataNo;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	//-- Admin Project Delete Data 
	// [input] : DataNo    :  \d+  
	public function ADProject_Dele_Project_Data( $DataNo=0 ){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查資料序號
	    if(!preg_match('/^\d+$/',$DataNo)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 執行刪除
		$DB_SAVE	= $this->DBLink->prepare(SQL_AdProject::UPDATE_PROJECT_META(array('_keep')));
		$DB_SAVE->bindValue(':spno' , $DataNo);
		$DB_SAVE->bindValue(':_keep' , 0);
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
	    }
		
		// final 
		$result['data'] = $DataNo;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	
	
	//-- Upload digital object 
	// [input] : DataNo     : [int] user_apply.uano
	// [input] : FILES : [array] - System _FILES Array;
	public function ADApply_Upload_Apply_File( $DataNo='' , $FILES = array()){
	  
	  $result_key = parent::Initial_Result('upload');
	  $result  = &$this->ModelResult[$result_key];
	  
      // [name] => MyFile.jpg  / [type] => image/jpeg  /  [tmp_name] => /tmp/php/php6hst32 / [error] => UPLOAD_ERR_OK / [size] => 98174
	  // Allowed extentions.
      $allowedExts = array("xls","xlsx");
      
      // Get filename.
      $temp = explode(".", $FILES["file"]["name"]);

      // Get extension.
      $extension = end($temp);
      
	  // Validate uploaded files.
	  // Do not use $_FILES["file"]["type"] as it can be easily forged.
	  $finfo = finfo_open(FILEINFO_MIME_TYPE);
	  $mime  = finfo_file($finfo, $FILES["file"]["tmp_name"]);
	  
	  try{
		
		// 檢查參數
		if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$apply_id   = $DataNo;
		
		if (!in_array(strtolower($extension), $allowedExts)) {
	      throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	    }	
		
        if( $FILES["file"]["error"] ){
          throw new Exception('_SYSTEM_UPLOAD_ERROR:'.$FILES["file"]["error"]);  
        }
		
		$save_folder = _SYSTEM_UPLD_PATH.'apply_upload/';
		
		// 取得上傳資料
		move_uploaded_file($FILES["file"]["tmp_name"],$save_folder.$FILES["file"]["name"]);
		
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
      
	}
	
	
	//-- Admin Project Get Apply Data 
	// [input] : uano  :  \d+;
	// [input] : file  :  string: upload excel file;
	public function ADApply_Process_Apply_File($DataNo=0,$FileName=''){
	  $result_key = parent::Initial_Result('process');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查資料序號
	    if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 檢查檔案名稱與存在
		if(!strlen($FileName)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		if(!file_exists(_SYSTEM_UPLD_PATH.'apply_upload/'.$FileName)){
		  throw new Exception('_APPLY_UPLOAD_FILE_NOT_EXIST');  	
		}
		
		$excelReader = PHPExcel_IOFactory::createReaderForFile(_SYSTEM_UPLD_PATH.'apply_upload/'.$FileName);
	    $excelReader->setReadDataOnly(true);
		$objPHPExcel = $excelReader->load(_SYSTEM_UPLD_PATH.'apply_upload/'.$FileName);
		$objPHPExcel->setActiveSheetIndex(0);
		$apply_code = $objPHPExcel->getActiveSheet()->getTitle();
		$objSheet=$objPHPExcel->getActiveSheet();
		
		
		$col = 0;
		$row = 2;
		$counter = 0;
		while($field = trim($objSheet->getCellByColumnAndRow($col,$row)->getValue())){
		  //0 入藏登錄號號	1典藏號	2典藏位置	3是否數位化	 4頁數	5上次准駁結果	6本次准駁結果	7法令參照	8參考法令 ex:158"	9法令依據參考"		10備註自填內容"

		  $store_no     = trim($objSheet->getCellByColumnAndRow(0,$row)->getValue());
		  $detail_no    = trim($objSheet->getCellByColumnAndRow(1,$row)->getValue());
		  
		  $check_prev   = trim($objSheet->getCellByColumnAndRow(5,$row)->getValue()); //5上次准駁結果
		  
		  $check_info   = trim($objSheet->getCellByColumnAndRow(6,$row)->getValue()); // 本次准駁結果 // 全部提供、部分提供、暫緩提供
		  $check_range  = trim($objSheet->getCellByColumnAndRow(7,$row)->getValue()); // 法令參照
		  $reference    = trim($objSheet->getCellByColumnAndRow(8,$row)->getValue()); // 參考法令
		  $check_note   = trim($objSheet->getCellByColumnAndRow(9,$row)->getValue()); // 備註
		  
		  switch($check_info){
		    case '全部提供': $view_mode = $detail_no ? '處理中':'原件閱覽'; break;
			case '部分提供': $view_mode = '處理中'; break;
            case '暫緩提供': $view_mode = '處理中'; break; 
			case '已准駁'  : $view_mode = $check_prev ? '' : '處理中'; break; 
            default: $view_mode = '處理中'; break; 			
		  }
		  
		  $row++;
		  
		  if( $check_info && $check_info!='原件閱覽'){
			$DB_UPD	= $this->DBLink->prepare( SQL_AdApply::UPDATE_APPLY_RECORD() );    
			$DB_UPD->bindValue(':check_info' , $check_info); //全部提供、部分提供、暫緩提供
			$DB_UPD->bindValue(':check_range', $check_range);
			$DB_UPD->bindValue(':reference'  , $reference);
			$DB_UPD->bindValue(':check_note' , $check_note);
			$DB_UPD->bindValue(':apply_code' , $apply_code);
			$DB_UPD->bindValue(':in_store_no', $store_no);
			$DB_UPD->bindValue(':store_no',$detail_no);
			$DB_UPD->execute();  
          
		    // update metadata checked
			$DB_UPDMETA	= $this->DBLink->prepare( SQL_AdApply::UPDATE_APPLY_METADATA_CHECKED() ); 
		    $DB_UPDMETA->bindValue(':applyindex',$store_no.$detail_no);
			$DB_UPDMETA->execute();
			
			// update metadata view mode
			// 20170605 check_info=已准駁 的資料維持原本的 _view 不修改成處理中  
			// 20170323 設定 _view 狀態:  原件且提供=>原件閱覽 | 其他=>處理中  @10
			// 20170223 apply_update 不直接更新 metadata _view 欄位 @10
			if($view_mode){
			  $DB_UPDMETA	= $this->DBLink->prepare( SQL_AdApply::UPDATE_APPLY_METADATA_VIEW()); 
			  $DB_UPDMETA->bindValue(':applyindex',$store_no.$detail_no);
			  $DB_UPDMETA->bindValue(':view', $view_mode );
			  $DB_UPDMETA->execute();	
			}
			
		  }
		}
		
		// update user_apply
		$DB_UPDMETA	= $this->DBLink->prepare( SQL_AdApply::UPDATE_USER_APPLY() );
		$DB_UPDMETA->execute(array('apply_code'=>$apply_code));
		
		// update user_apply
		$DB_UPDSTATE= $this->DBLink->prepare( SQL_AdApply::COMPLETE_APPLY_CHECKED() );
		$DB_UPDSTATE->execute(array('apply_code'=>$apply_code));
		
		unset($objSheet);
		unset($objPHPExcel);
		unset($excelReader);
		
		// move file
		copy(_SYSTEM_UPLD_PATH.'apply_upload/'.$FileName,_SYSTEM_UPLD_PATH.'apply_history/'.date('mdHis').'_'.$FileName);
		unlink(_SYSTEM_UPLD_PATH.'apply_upload/'.$FileName);
		
		// final
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	
	
	
	//-- Admin Project Record Mode 
	// [input] : $DateStart;
	// [input] : $DateEnd;
	public function ADApply_Get_Apply_Record($DateStart='',$DateEnd=''){
	  
	  $result_key = parent::Initial_Result('records');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		$handler_map = array();
		if(isset($this->ModelResult['admin']) && $this->ModelResult['admin']['action']){
		  $handler_map = $this->ModelResult['admin']['data'];	
		}
		
		$date_start = $DateStart && strtotime($DateStart) ? date('Y-m-d',strtotime($DateStart)) : date('Y-m',strtotime('-3 month')).'-01';
		$date_end   = $DateEnd && strtotime($DateEnd) ? date('Y-m-d',strtotime($DateEnd)) : date('Y-m-d');
		
		if(strtotime($date_start) > strtotime($date_end) ){
		  $date_end = $date_start;	
		}
		
		$date_index    = strtotime($date_start);
		$stastic_range = array();
		while($date_index < strtotime($date_end ) ){
          $stastic_range[date('Y-m',$date_index)] = array('checked_cases'=>0,'checked_pages'=>0);   
          $date_index = strtotime('+1 month',$date_index);
		}
		
		// 查詢資料庫
		$DB_OBJ = $this->DBLink->prepare(SQL_AdApply::GET_APPLY_RECORD());
		$DB_OBJ->bindValue(':date_s',$date_start.' 00:00:00');
		$DB_OBJ->bindValue(':date_e',$date_end.' 23:59:59');
		$DB_OBJ->execute();
		
		
		$records = array();
		
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  
          if( !isset($records[$tmp['checker']]) ){
			$records[$tmp['checker']] = [
			  'checker_name'  => (isset($handler_map[$tmp['checker']]) ? $handler_map[$tmp['checker']] : $tmp['checker']),
			  'check_tasks'   => 0, // 分配總件數  
			  'check_pages'   => 0, // 總頁數  
			  'checked_cases' => 0, // 完成件數
			  'checked_pages' => 0, // 完成頁數
			  'date_range'    => $stastic_range,
			  'data_queue'    => array(),
			  
			];  
		  }
		  
		  $records[$tmp['checker']]['check_tasks']++;
		  $records[$tmp['checker']]['check_pages']+=$tmp['page_count'];
		  
		  if($tmp['check_state']=='_CHECKED'){
			$records[$tmp['checker']]['checked_cases']++;
		    $records[$tmp['checker']]['checked_pages']+=$tmp['page_count'];   
		    $records[$tmp['checker']]['date_range'][substr($tmp['check_time'],0,7)]['checked_cases']++;
			$records[$tmp['checker']]['date_range'][substr($tmp['check_time'],0,7)]['checked_pages']+=$tmp['page_count'];
		  } 
		  
		   $records[$tmp['checker']]['data_queue'][] = 
		     "<li class='status ".$tmp['check_state']."'  >".
			 "<span class='apply_code' title='申請單號'>".$tmp['apply_code']."</span>".
			 "<span class='apply_user' title='申請人：".$tmp['user_mail']."'>".$tmp['user_name']."</span>".
			 "<span class='handler' title='准駁人' >".$handler_map[$tmp['checker']]."</span>".
			 "<span class='apply_index' title='".$tmp['in_store_no'].'/'.$tmp['store_no']."'>".($tmp['store_no']?$tmp['store_no']:$tmp['in_store_no'])."</span>".
			 "<span class='apply_result' title='准駁:".$tmp['check_info'].' '.$tmp['check_time']."' >".($tmp['check_state']=='_CHECKED'? '('.mb_substr($tmp['check_info'],0,1).')':'')."</span>".
			 "</li>";
		}
		
		
		
		
		/*
		
		if( isset($this->USER->PermissionNow['group_roles']['R00']) || 
		   (isset($this->USER->PermissionNow['group_roles']['R03']) && $this->USER->PermissionNow['group_roles']['R03'] > 1) ){
		  
		  // 查詢資料庫
		  $DB_OBJ = $this->DBLink->prepare(SQL_AdApply::GET_APPLY_RECORD());
		}else{
		  // 查詢資料庫
		  $DB_OBJ = $this->DBLink->prepare(SQL_AdApply::SELECT_CHECKER_APPLY());	
		  $DB_OBJ->bindValue(':checker',$this->USER->UserNO);
		} 
		
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		// 取得申請資料
		$apply_list = array();
		$apply_list = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);		
	    
		foreach($apply_list as &$data){
		  
		  // 設定申請資料
		  $data['@progress']      =  $data['apply_count'] ? round(($data['process_count']/$data['apply_count'])*100,2).'%' : '-';
		  $data['@handler_name']  =  $data['handler']&&isset($handler_map[$data['handler']]) ? $handler_map[$data['handler']] : $data['handler'];
		  
		  // 檢查狀態
		  $status_info = ''; // 儲存狀態訊息 
          switch( (string)$data['_state'] ){
		      case '0': $status_info = '新申請';   break;
		      case '1': $status_info = '已受理';   break;
			  case '2': $status_info = '處理中';   break;
			  case '3': $status_info = '已完成';   break;
			  case '4': $status_info = '已通知';   break;
			  default: $status_info = '未知狀態';   break;
		  }
		  $data['@state_info'] = $status_info;
		  
		}
		*/
		$result['action'] = true;		
		$result['data']['stastic']   = $records;		
	    $result['data']['range']     = array('date_start'=>$date_start,'date_end'=>$date_end);
		
		//echo "<pre>";
		//var_dump($records);
		//exit(1);
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Project Reserve Mode : 取得今天到館閱覽申請
	// [input] : $DateStart;
	public function ADApply_Get_Reserve_Record( $DateStart ){
	  
	  $result_key = parent::Initial_Result('records');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		$date_start = $DateStart && strtotime($DateStart) ? date('Y-m-d',strtotime($DateStart)) : '';
		
		// 查詢資料庫
		if($date_start){
		  $DB_OBJ = $this->DBLink->prepare(SQL_AdApply::GET_APPLY_RESERVE());
		  $DB_OBJ->bindValue(':date',$date_start);
		}else{
		  $DB_OBJ = $this->DBLink->prepare(SQL_AdApply::ALL_APPLY_RESERVE());
		}
		
		$DB_OBJ->execute();
		
		$records = array();
		
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  $record = array();
          $record = $tmp;
		  $records[] = $record;
		}
		
		$result['action'] = true;		
		$result['data']['reserve']   = $records;		
	    $result['data']['range']     = array('date_start'=>$date_start);
		
		//echo "<pre>";
		//var_dump($records);
		//exit(1);
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
  }
?>