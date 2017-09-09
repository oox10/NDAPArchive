<?php
    
	/*
	處理數位檔案連結與縮圖 20170808
	SOURCE : source_archive & DataType ='檔案' 公報  議事錄
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
	
	$lib_imagemagic =  'D:/webroot/NDAPArchive/mvc/lib/ImageMagick-7.0.3-7-portable-Q16-x64/convert.exe ';
    
	$folder_map = array( '檔案'=>'ARCHIVE' , '公報'=>'GAZETTE' , '議事錄'=>'RECORD', '議事影音'=>'MEDIA','活動照片'=>'PHOTO');
	$file_allow = array('jpg','png','tiff','wmv','mp4','mp3');
	
	define('_SOURCE_LOCATION','F:/DigitalStore/');
	define('_STORE_LOCATION','F:/DigitalStore/');
	
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'metadata';
	$target_condition = "zong ='檔案' AND dobj_json='[]'";
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
			
			if($meta['dobj_json']!='[]') continue;
			
			//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
			$paser_count++;
			
			echo "\n".str_pad($paser_count,6,'0',STR_PAD_LEFT).'. '.$meta['system_id']." : ";
			
			//if(isset($meta_exist[$source['StoreNo']])){
			//  echo "skip.";
			//  continue;		  
			//}
			
			$dobj_store = _STORE_LOCATION.$folder_map[$meta['zong']];
			
			// 依據不同類型檔案設定搜尋項目
			$source = json_decode($meta['source_json'],true);
			$dobject = json_decode($meta['dobj_json'],true);
			
			
			switch($meta['zong']){
			  
			  case '檔案':
			    
				$source_location = _SOURCE_LOCATION.'0DRTPA-Image/FL-Image/';
				
				//-- 建構檔案儲存結構
				if(!is_dir($dobj_store.'/browse/'.$meta['collection'].'/')) mkdir($dobj_store.'/browse/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/saved/'.$meta['collection'].'/')) mkdir($dobj_store.'/saved/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/thumb/'.$meta['collection'].'/')) mkdir($dobj_store.'/thumb/'.$meta['collection'].'/',0777,true);
				
				
				echo "\n".$meta['collection'].':PASER START..';
				
				$do_files = array_slice(scandir($dobj_store.'/browse/'.$meta['collection']),2);
				
				
                if(!count($do_files)){
					
				  //取得舊檔案位置
                  $db_map = $db->DBLink->prepare("SELECT * FROM resouse_imagefilemap WHERE FileId=:collection;");
				  $db_map->execute(array('collection'=>$meta['collection']));
				  $location = $db_map->fetch(PDO::FETCH_ASSOC);
				  
				  if(!$location || !isset($location['ImageFile'])){ // 查無對應表
					continue;  
				  }
				  
				  if(!is_dir($source_location.$location['ImageFile'])){ // 無來源資料夾
					continue;    
				  }
				  
				  $file_list = array_slice(scandir($source_location.$location['ImageFile']),2);
				  
				  if(!count($file_list)){ // 資料夾為空
					continue;  
				  }
				  
				  sort($file_list);
				  
				  // 掃描檔案
				  $page_count = 0;
				  
				  foreach($file_list as $file){
					echo "\n".$file.': ';
					$file_from = $source_location.$location['ImageFile'].'/'.$file;
					
					// 確認型態
					if(!in_array(strtolower(pathinfo($file_from,PATHINFO_EXTENSION)),$file_allow) ){
					  echo "skip.";
					  continue;	
					}
					
					list($iw, $ih, $it, $attr) = getimagesize($file_from);	
					
					// 複製檔案
					echo " S: ";
					if(is_file($dobj_store.'/browse/'.$meta['collection'].'/'.$file)){
					  echo 'skip. ';	
					}else{
					  echo copy($file_from,$dobj_store.'/browse/'.$meta['collection'].'/'.$file) ? 'OK':'fail';	
					}
					  
					//  建立縮圖 thumb  width 200 |  height 200 
					echo " T:";
					$file_thumb   = $dobj_store.'/thumb/'.$meta['collection'].'/'.$file;
					if(is_file($file_thumb)){
					  echo 'skip. ';	
					}else{
					  $config = ($iw >= $ih) ?  ' -thumbnail 150 ' : ' -thumbnail x200 ';
					  exec($lib_imagemagic.$config.$file_from.' '.$file_thumb ,$result);
					  echo count($result) ? 'fail':'OK';	
					}
					$page_count++;
				  }
				}else{
				  $page_count = count($do_files);	
				}  
				  
				  $dobject['dopath']  = $folder_map[$meta['zong']].'/';
				  $dobject['count']   = $page_count;
				  
				  //取得遮蔽檔案清單
                  $dobject['domask'] = array();
				  $db_mask = $db->DBLink->prepare("SELECT * FROM resouse_imagefilemask WHERE StoreNo=:collection;");
				  $db_mask->execute(array('collection'=>$meta['collection']));
				  while($tmp = $db_mask->fetch(PDO::FETCH_ASSOC)){
					$dobject['domask'][$tmp['image_name']] = [
					  'mode'=>'disabled',
					  'display'=>0,
					  'creater'=>$tmp['creater'],
                      'time'=>$tmp['create_time']
					];  
				  }
				  $dobject['transfer']= date('Y-m-d H:i:s');
				  $dobject['logs']=[
				    date('Y-m-d H:i:s')=>'transfered by RCDH.'
				  ];
				
				break;
			  
			  case '議事錄':
				
				$source_location = _SOURCE_LOCATION.'0DRTPA-Image/ES-Image/';
				
				//-- 建構檔案儲存結構
				if(!is_dir($dobj_store.'/browse/'.$meta['collection'].'/')) mkdir($dobj_store.'/browse/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/saved/'.$meta['collection'].'/')) mkdir($dobj_store.'/saved/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/thumb/'.$meta['collection'].'/')) mkdir($dobj_store.'/thumb/'.$meta['collection'].'/',0777,true);
				
				echo "\n".$meta['collection'].':PASER START..';
				$do_files = array_slice(scandir($dobj_store.'/browse/'.$meta['collection']),2);
				
                $source = json_decode($meta['source_json'],true);                   
				$path_array = explode('-',$meta['collection']);
				$location = $path_array[0].'/'.$path_array[1].'/'.( intval($path_array[3]) ? $path_array[2].'-'.$path_array[3] : $path_array[2] ).'/';
				  
				
				  
				  if(!is_dir($source_location.$location)){ // 無來源資料夾
					file_put_contents('dopaser.log',date('Y-m-d H:i:s')." [ERROR]\t".$meta['collection']." folder unfound!!" ,FILE_APPEND);
					continue;    
				  }
				  
				  $file_list = array_slice(scandir($source_location.$location),2);
				  
				  if(!count($file_list)){ // 資料夾為空
					file_put_contents('dopaser.log',date('Y-m-d H:i:s')." [ERROR]\t".$meta['collection']." folder empty!!" ,FILE_APPEND);
					continue;
				  }
				  
				  //因為是 1->多 確認是否已移動影像
				  $store_folder = array_slice(scandir($dobj_store.'/browse/'.$meta['collection'].'/'),2);
				  
				  if(count($store_folder)){
					  echo "Folder already Stored.";
					  $page_count = count($store_folder);
					  
					  // 議事錄影像需要重新排序
					  $book_page_list  = array();
					  $ImageZnumArray  = array();
					  $ImageNumeArray  = array();
					  $ImageINumArray  = array();
					  $ImageCNumArray  = array();
					  $ImageAPnumArray = array();
						
					  foreach($store_folder as $img){
						   
						if(preg_match('@-([\d_]+)\.jpg@',$img,$match) ){
						  if(preg_match('/0000_/',$match[1])){
							$ImageZnumArray[]=$img;
						  }else{
							$ImageNumeArray[]=$img;
						  }  
						}else if(preg_match('@-(0000_[\w\d]+)\.jpg@',$img)){
							$ImageINumArray[]=$img;
						}else if(preg_match('@-(ca[\d]+)\.jpg@',$img)){
							$ImageCNumArray[]=$img;
						}else if(preg_match('@-(ap[\d]+)\.jpg@',$img)){
							$ImageAPnumArray[]=$img;
						}
					  }
						
					  $ImageCNumArray = array_merge( $ImageCNumArray , $ImageINumArray );  //把Info 0000_abcd 的塞到Category 之後
					  $ImageNumeArray = array_merge( $ImageNumeArray , $ImageAPnumArray ); //把影像編號AP的排到最後面
					  $ImageNumeArray = array_merge( $ImageZnumArray , $ImageNumeArray );  //把影像編號00的排到最前面
					  $ImageNumeArray = array_merge( $ImageCNumArray , $ImageNumeArray); 
					  $book_page_list = $ImageNumeArray;
					  
				  }else{
				  
					  // 議事錄影像需要重新排序
					  $book_page_list  = array();
					  $ImageZnumArray  = array();
					  $ImageNumeArray  = array();
					  $ImageINumArray  = array();
					  $ImageCNumArray  = array();
					  $ImageAPnumArray = array();
						
					  foreach($file_list as $img){
						   
						if(preg_match('@-([\d_]+)\.jpg@',$img,$match) ){
						  if(preg_match('/0000_/',$match[1])){
							$ImageZnumArray[]=$img;
						  }else{
							$ImageNumeArray[]=$img;
						  }  
						}else if(preg_match('@-(0000_[\w\d]+)\.jpg@',$img)){
							$ImageINumArray[]=$img;
						}else if(preg_match('@-(ca[\d]+)\.jpg@',$img)){
							$ImageCNumArray[]=$img;
						}else if(preg_match('@-(ap[\d]+)\.jpg@',$img)){
							$ImageAPnumArray[]=$img;
						}
					  }
						
					  $ImageCNumArray = array_merge( $ImageCNumArray , $ImageINumArray );  //把Info 0000_abcd 的塞到Category 之後
					  $ImageNumeArray = array_merge( $ImageNumeArray , $ImageAPnumArray ); //把影像編號AP的排到最後面
					  $ImageNumeArray = array_merge( $ImageZnumArray , $ImageNumeArray );    //把影像編號00的排到最前面
					  $ImageNumeArray = array_merge( $ImageCNumArray , $ImageNumeArray); 
					  $book_page_list = $ImageNumeArray;
					  
					  // 掃描檔案
					  $page_count = 0;
					  
					  foreach($book_page_list as $file){
						echo "\n".$file.': ';
						$file_from = $source_location.$location.$file;
						
						// 確認型態
						if(!in_array(strtolower(pathinfo($file_from,PATHINFO_EXTENSION)),$file_allow) ){
						  echo "skip.";
						  continue;	
						}
						
						list($iw, $ih, $it, $attr) = getimagesize($file_from);	
						
						// 複製檔案
						echo " S: ";
						if(is_file($dobj_store.'/browse/'.$meta['collection'].'/'.$file)){
						  echo 'skip. ';	
						}else{
						  echo copy($file_from,$dobj_store.'/browse/'.$meta['collection'].'/'.$file) ? 'OK':'fail';	
						}
						  
						//  建立縮圖 thumb  width 200 |  height 200 
						echo " T:";
						$file_thumb   = $dobj_store.'/thumb/'.$meta['collection'].'/'.$file;
						if(is_file($file_thumb)){
						  echo 'skip. ';	
						}else{
						  $config = ($iw >= $ih) ?  ' -thumbnail 150 ' : ' -thumbnail x200 ';
						  exec($lib_imagemagic.$config.$file_from.' '.$file_thumb ,$result);
						  echo count($result) ? 'fail':'OK';	
						}
						$page_count++;
					  }
				  }
				  
				  $dobject['dopath']  = $folder_map[$meta['zong']].'/';
				  $dobject['count']   = $page_count;
				  
				  //定位影像
                  $dobject['position'] = [];
				  //"PageStart": "0019", "PageEnd": "0021",
				  $range_flag = 0;
				  foreach($book_page_list as $i=>$file_name){
					
					if(!$range_flag){
					  if(preg_match('/\-'.$source['PageStart'].'\./',$file_name)){
						$range_flag = 1;
					  }	
					}
					
					if($range_flag){
					  $dobject['position'][($i+1)] = $file_name;
					  if(preg_match('/\-'.$source['PageEnd'].'\./',$file_name)){
					  	$range_flag = 0;
					    break;
					  }
					} 
				  }
				  
				  if($range_flag){
					$dobject['position'] = array_slice($dobject['position'],0,1);  
				  }
				  
				  $dobject['transfer']= date('Y-m-d H:i:s');
				  $dobject['logs']=[
				    date('Y-m-d H:i:s')=>'transfered by RCDH.'
				  ];
				  ob_flush();
				  flush();
				break;
			  
			  
			  case '公報':
				
				$source_location = _SOURCE_LOCATION.'0DRTPA-Image/KP-Image/';
				
				//-- 建構檔案儲存結構
				if(!is_dir($dobj_store.'/browse/'.$meta['collection'].'/')) mkdir($dobj_store.'/browse/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/saved/'.$meta['collection'].'/')) mkdir($dobj_store.'/saved/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/thumb/'.$meta['collection'].'/')) mkdir($dobj_store.'/thumb/'.$meta['collection'].'/',0777,true);
				
				echo "\n".$meta['collection'].':PASER START..';
				  
                  $source = json_decode($meta['source_json'],true);                   
				   
				  $path_array = explode('-',$meta['collection']);
				  $location = $path_array[0].'/'.$path_array[3].'/';
				  
				  if(!is_dir($source_location.$location)){ // 無來源資料夾
					file_put_contents('dopaser.log',date('Y-m-d H:i:s')." [ERROR]\t".$meta['collection']." folder unfound!!" ,FILE_APPEND);
					continue;    
				  }
				  
				  $file_list = array_slice(scandir($source_location.$location),2);
				  
				  if(!count($file_list)){ // 資料夾為空
					file_put_contents('dopaser.log',date('Y-m-d H:i:s')." [ERROR]\t".$meta['collection']." folder empty!!" ,FILE_APPEND);
					continue;
				  }
				  
				  //因為是 1->多 確認是否已移動影像
				  $store_folder = array_slice(scandir($dobj_store.'/browse/'.$meta['collection'].'/'),2);
				  
				  if(count($store_folder)){
					  echo "Folder already Stored.";
					  $page_count = count($store_folder);
					  
					  // 議事錄影像需要重新排序
					  $book_page_list  = array();
					  $ImageZnumArray  = array();
					  $ImageNumeArray  = array();
					  $ImageINumArray  = array();
					  $ImageCNumArray  = array();
					  $ImageAPnumArray = array();
						
					  foreach($store_folder as $img){
						   
						if(preg_match('@-([\d_]+)\.jpg@',$img,$match) ){
						  if(preg_match('/0000_/',$match[1])){
							$ImageZnumArray[]=$img;
						  }else{
							$ImageNumeArray[]=$img;
						  }  
						}else if(preg_match('@-(0000_[\w\d]+)\.jpg@',$img)){
							$ImageINumArray[]=$img;
						}else if(preg_match('@-(ca[\d]+)\.jpg@',$img)){
							$ImageCNumArray[]=$img;
						}else if(preg_match('@-(ap[\d]+)\.jpg@',$img)){
							$ImageAPnumArray[]=$img;
						}
					  }
						
					  $ImageCNumArray = array_merge( $ImageCNumArray , $ImageINumArray );  //把Info 0000_abcd 的塞到Category 之後
					  $ImageNumeArray = array_merge( $ImageNumeArray , $ImageAPnumArray ); //把影像編號AP的排到最後面
					  $ImageNumeArray = array_merge( $ImageZnumArray , $ImageNumeArray );  //把影像編號00的排到最前面
					  $ImageNumeArray = array_merge( $ImageCNumArray , $ImageNumeArray); 
					  $book_page_list = $ImageNumeArray;
					  
				  }else{
				  
					  // 議事錄影像需要重新排序
					  $book_page_list  = array();
					  $ImageZnumArray  = array();
					  $ImageNumeArray  = array();
					  $ImageINumArray  = array();
					  $ImageCNumArray  = array();
					  $ImageAPnumArray = array();
						
					  foreach($file_list as $img){
						   
						if(preg_match('@-([\d_]+)\.jpg@',$img,$match) ){
						  if(preg_match('/0000_/',$match[1])){
							$ImageZnumArray[]=$img;
						  }else{
							$ImageNumeArray[]=$img;
						  }  
						}else if(preg_match('@-(0000_[\w\d]+)\.jpg@',$img)){
							$ImageINumArray[]=$img;
						}else if(preg_match('@-(ca[\d]+)\.jpg@',$img)){
							$ImageCNumArray[]=$img;
						}else if(preg_match('@-(ap[\d]+)\.jpg@',$img)){
							$ImageAPnumArray[]=$img;
						}
					  }
						
					  $ImageCNumArray = array_merge( $ImageCNumArray , $ImageINumArray );  //把Info 0000_abcd 的塞到Category 之後
					  $ImageNumeArray = array_merge( $ImageNumeArray , $ImageAPnumArray ); //把影像編號AP的排到最後面
					  $ImageNumeArray = array_merge( $ImageZnumArray , $ImageNumeArray );    //把影像編號00的排到最前面
					  $ImageNumeArray = array_merge( $ImageCNumArray , $ImageNumeArray); 
					  $book_page_list = $ImageNumeArray;
					  
					  // 掃描檔案
					  $page_count = 0;
					  
					  foreach($book_page_list as $file){
						echo "\n".$file.': ';
						$file_from = $source_location.$location.$file;
						
						// 確認型態
						if(!in_array(strtolower(pathinfo($file_from,PATHINFO_EXTENSION)),$file_allow) ){
						  echo "skip.";
						  continue;	
						}
						
						list($iw, $ih, $it, $attr) = getimagesize($file_from);	
						
						// 複製檔案
						echo " S: ";
						if(is_file($dobj_store.'/browse/'.$meta['collection'].'/'.$file)){
						  echo 'skip. ';	
						}else{
						  echo copy($file_from,$dobj_store.'/browse/'.$meta['collection'].'/'.$file) ? 'OK':'fail';	
						}
						  
						//  建立縮圖 thumb  width 200 |  height 200 
						echo " T:";
						$file_thumb   = $dobj_store.'/thumb/'.$meta['collection'].'/'.$file;
						if(is_file($file_thumb)){
						  echo 'skip. ';	
						}else{
						  $config = ($iw >= $ih) ?  ' -thumbnail 150 ' : ' -thumbnail x200 ';
						  exec($lib_imagemagic.$config.$file_from.' '.$file_thumb ,$result);
						  echo count($result) ? 'fail':'OK';	
						}
						$page_count++;
					  }
				  }
				  
				  $dobject['dopath']  = $folder_map[$meta['zong']].'/';
				  $dobject['count']   = $page_count;
				  
				  //定位影像
                  $dobject['position'] = [];
				  //"PageStart": "0019", "PageEnd": "0021",
				  $range_flag = 0;
				  foreach($book_page_list as $i=>$file_name){
					
					if(!$range_flag){
					  if(preg_match('/\-'.$source['PageStart'].'\./',$file_name)){
						$range_flag = 1;
					  }	
					}
					
					if($range_flag){
					  $dobject['position'][($i+1)] = $file_name;
					  if(preg_match('/\-'.$source['PageEnd'].'\./',$file_name)){
					  	$range_flag = 0;
					    break;
					  }
					} 
				  }
				  
				  if($range_flag){
					$dobject['position'] = array_slice($dobject['position']['page'],0,1);  
				  }
				  
				  $dobject['transfer']= date('Y-m-d H:i:s');
				  $dobject['logs']=[
				    date('Y-m-d H:i:s')=>'transfered by RCDH.'
				  ];
				  ob_flush();
				  flush();
				break;
				
			  case '議事影音': 
				break;
			  
			  case '活動照片': 
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