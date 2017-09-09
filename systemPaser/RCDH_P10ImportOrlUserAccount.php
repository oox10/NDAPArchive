<?php
    
	/*
	將原始資料轉存詮釋資料 20170803
	省議會史料總庫 - 檔案
	
	SOURCE : source_archive & DataType ='檔案'
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
    
	$meet = array( 'OA'=>'定期大會' , 'IA'=>'成立大會' , 'EA'=>'臨時大會');
	
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'user_ndap';
	$target_condition = "1";
	$meta_exist = array();
		
	$db_login = $db->DBLink->prepare("INSERT INTO user_login VALUES (null,:user_id,'',:date_register,:date_open,:date_access,'0.0.0.0',4,NULL);");
	$db_info  = $db->DBLink->prepare("INSERT INTO user_info VALUES( :uid , :user_name , :user_idno , :user_staff , :user_organ , :user_tel , :user_mail, :user_address ,:user_age,:user_education,:user_major, :user_info  , :user_pri );");
	
	try{ 
      
	  $db_select = $db->DBLink->prepare("SELECT * FROM ".$source_table." WHERE ".$target_condition." ORDER BY  id ASC;");
       
	  if( !$db_select->execute() ){
		throw new Exception('查無目錄資料');    
	  }
	  
	  while( $source = $db_select->fetch(PDO::FETCH_ASSOC) ){
		
		echo "\n".$source['uname']." : ";
		
		
		if(isset($meta_exist[strtolower($source['uname'])])){
		  echo "double.";
          continue;		  
		}
		
		
		
		// 登錄帳號
		$db_login->bindValue(':user_id'       , $source['uname']);
		$db_login->bindValue(':date_register' , $source['RegisterDate']);
	    $db_login->bindValue(':date_open'	  , '2017-09-01 00:00:00');
	    $db_login->bindValue(':date_access'   , '2025-12-31 00:00:00');
		if(!$db_login->execute()){
		  throw new Exception('新增資料失敗'); 	
		}
		
		$user_no = $db->DBLink->lastInsertId();
		
		// STEP.5 建立資料上載暫存資料夾
		if(!is_dir(_SYSTEM_USER_PATH.$source['uname'])){
		  mkdir(_SYSTEM_USER_PATH.$source['uname'],0777,true);	  
		}
		
		$db_info->bindValue(':uid'	, $user_no );
		$db_info->bindValue(':user_name'		,$source['RealName']);
	    $db_info->bindValue(':user_idno'		,'');
	    $db_info->bindValue(':user_staff'		,$source['Profession']);
	    $db_info->bindValue(':user_organ'		,$source['Organ']);
		$db_info->bindValue(':user_tel'			,'');
		$db_info->bindValue(':user_mail'		,$source['Email']);
	    $db_info->bindValue(':user_address'		,'');
	    $db_info->bindValue(':user_age'			,$source['Age']);
		$db_info->bindValue(':user_education'	,$source['Educational']);
		$db_info->bindValue(':user_major'		,$source['Class']);
	    $db_info->bindValue(':user_info'		,'');
		$db_info->bindValue(':user_pri'		    , '2' );
		if(!$db_info->execute()){
		  throw new Exception('新增資料失敗'); 	
		}
		
		// STEP.4: insert table:digital_ftpuser   // 加入註冊群組 :uno,:gno,:rno,:creater
		$role_conf = "COLUMN_CREATE('R00', 0, 'R01', 0, 'R02', 0, 'R03',0, 'R04',0, 'R05', 1)";
		
		
		$DB_UGP = $db->DBLink->prepare("INSERT INTO permission_matrix VALUES(:uno,:gno,".$role_conf.",:master,'',:creater,NULL);");
		$DB_UGP->bindParam(':uno',$user_no ,PDO::PARAM_INT);
		$DB_UGP->bindValue(':gno','mbr');
		$DB_UGP->bindValue(':master',1);
		$DB_UGP->bindvalue(':creater','system');
		$DB_UGP->execute();
		
		$meta_exist[strtolower($source['uname'])] = 1;
		
		
		echo " done. ";
	  }
	  
    } catch (Exception $e) {
      echo $e->getMessage();
    }	
	
?>