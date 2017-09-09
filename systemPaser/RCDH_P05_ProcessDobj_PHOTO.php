<?php
    
	/*
	處理數位檔案連結與縮圖 20170808
	SOURCE : source_archive & DataType ='檔案'
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
	
	$lib_imagemagic =  'D:/webroot/NDAPArchive/mvc/lib/ImageMagick-7.0.3-7-portable-Q16-x64/convert.exe ';
    
	$folder_map = array( '檔案'=>'ARCHIVE' , '公報'=>'GAZETTE' , '議事錄'=>'RECORD', '議事影音'=>'MEDIA','活動照片'=>'PHOTO');
	$file_allow = array('jpg','png','tiff','tif','wmv','mp4','mp3');
	
	define('_SOURCE_LOCATION','F:/DigitalStore/');
	define('_STORE_LOCATION','F:/DigitalStore/');
	
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'metadata';
	$target_condition = "zong ='活動照片'";
	$meta_exist = array();
	
	ob_start();
	
	$db_update = $db->DBLink->prepare("UPDATE metadata SET dobj_json=:dobj_json WHERE system_id=:system_id;");
	
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
	  $paser_count = 0;
	  $limit = 0;
	  $frame = 10000;
	  
	  echo "[PASER] metadata dobj paser start : ".$total_count;
	  
	  while($limit < $total_count ){
	  
	      $db_select = $db->DBLink->prepare("SELECT * FROM ".$source_table." WHERE ".$target_condition." ORDER BY collection ASC,system_id ASC LIMIT ".$limit.",".$frame.";");
       
	      if( !$db_select->execute() ){
		    throw new Exception('查無目錄資料');    
	      }  
	      
		  while( $meta = $db_select->fetch(PDO::FETCH_ASSOC) ){
			
			//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
			$paser_count++;
			
			if($meta['dobj_json']!='[]') continue;
			
			echo "\n".str_pad($paser_count,6,'0',STR_PAD_LEFT).'. '.$meta['system_id']." : ";
			
			$dobj_store = _STORE_LOCATION.$folder_map[$meta['zong']];
			
			// 依據不同類型檔案設定搜尋項目
			$source  = json_decode($meta['source_json'],true);
			$dobject = json_decode($meta['dobj_json'],true);
			
			switch($meta['zong']){
			  
			  case '活動照片':
			    
				$source_location = _SOURCE_LOCATION.'0PhotoServer/';
				
				
				//-- 建構檔案儲存結構
				if(!is_dir($dobj_store.'/browse/'.$meta['collection'].'/')) mkdir($dobj_store.'/browse/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/saved/'.$meta['collection'].'/')) mkdir($dobj_store.'/saved/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/thumb/'.$meta['collection'].'/')) mkdir($dobj_store.'/thumb/'.$meta['collection'].'/',0777,true);
				
				echo "\n".$meta['collection'].':PASER START..';
				  
                
				//因為是 1->多 確認是否已移動影像
				$store_folder = array_slice(scandir($dobj_store.'/browse/'.$meta['collection'].'/'),2);
				  
				if(count($store_folder)){
					
				  echo "Folder already Stored.";
				  $page_count = count($store_folder);
				  $file_list =  $store_folder;  
					 
				}else{
				  
				  $tiff_path = $source_location.'TIFF/'.$meta['collection'].'/';
				  $jpeg_path = $source_location.'JPG/'.$meta['collection'].'/';
				
				  
				  if(!is_dir($jpeg_path)){ // 無來源資料夾
				    file_put_contents('dopaser.log',date('Y-m-d H:i:s')." [ERROR]\t".$meta['collection']." folder unfound!!" ,FILE_APPEND);
				    continue;    
				  }
				  
				  $file_list = array_slice(scandir($tiff_path),2);
				  
				  if(!count($file_list)){ // 資料夾為空
				    file_put_contents('dopaser.log',date('Y-m-d H:i:s')." [ERROR]\t".$meta['collection']." folder empty!!" ,FILE_APPEND);
				    continue;
				  }
				  
				  
				  // 掃描檔案
				  $page_count = 0;
				  
				  foreach($file_list as $file){
					
					echo "\n".$file.': ';
					$file_from = $tiff_path.$file;
					list($iw, $ih, $it, $attr) = getimagesize($file_from);
					
					// 確認型態
					if(!in_array(strtolower(pathinfo($file_from,PATHINFO_EXTENSION)),$file_allow) ){
					  echo "skip.";
					  continue;	
					}
					
					// 取得檔名
					$do_file_name = pathinfo($file_from,PATHINFO_FILENAME);
					
					// 複製原始檔
					echo " D: ";
					if(is_file($dobj_store.'/saved/'.$meta['collection'].'/'.$file)){
					  echo 'skip. ';	
					}else{
					  echo copy($file_from,$dobj_store.'/saved/'.$meta['collection'].'/'.$file) ? 'OK':'fail';	
					}
					
					// 一般
					echo " S: ";
					if(is_file($jpeg_path.$do_file_name.'.jpg')){
					  echo copy($jpeg_path.$do_file_name.'.jpg',$dobj_store.'/browse/'.$meta['collection'].'/'.$do_file_name.'.jpg') ? 'OK':'fail';	
					}else{
					  $config = ($iw >= $ih) ?  ' -resize 1080x -quality 70 ' : ' -resize x1080 -quality 70 ';
					  exec($lib_imagemagic.$config.$file_from.' '.$dobj_store.'/browse/'.$meta['collection'].'/'.$do_file_name.'.jpg' ,$result);
					  echo count($result) ? 'fail':'OK';
					}
					
					// 縮圖
					if(is_file($jpeg_path.$do_file_name.'_1.jpg')){
					  echo copy($jpeg_path.$do_file_name.'_1.jpg',$dobj_store.'/thumb/'.$meta['collection'].'/'.$do_file_name.'.jpg') ? 'OK':'fail';	
					}else{
					  $config = ($iw >= $ih) ?  ' -thumbnail 150 ' : ' -thumbnail x200 ';
					  exec($lib_imagemagic.$config.$dobj_store.'/browse/'.$meta['collection'].'/'.$do_file_name.'.jpg'.' '.$dobj_store.'/thumb/'.$meta['collection'].'/'.$do_file_name.'.jpg' ,$result);
					  echo count($result) ? 'fail':'OK';
					}
					
					$page_count++;
				  
				  }
				  
				  $file_list = array_slice(scandir($dobj_store.'/browse/'.$meta['collection'].'/'),2);
				  
				}
				  
				$dobject['dopath']  = $folder_map[$meta['zong']].'/';
				$dobject['count']   = $page_count;
				  
				//定位影像
                $dobject['position'] = [];
				foreach($file_list as $i=>$file_name){
				  if(preg_match('/'.$meta['identifier'].'\./',$file_name)){
					$dobject['position'][($i+1)] = $file_name;
					break;
				  }
				}	
				$dobject['transfer']= date('Y-m-d H:i:s');
				$dobject['logs']=[
				  date('Y-m-d H:i:s')=>'transfered by RCDH.'
				];
				
				
			
				
				ob_flush();
				flush();
				break;
			  
			  default: 
				exit(1);
				break;
			}
			
			// 更新 meta
			$db_update->bindValue(':dobj_json',json_encode($dobject,JSON_UNESCAPED_UNICODE));
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
	
	
	
?>