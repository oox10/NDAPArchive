<?php
    
	/*
	處理數位檔案連結與縮圖 20170808
	SOURCE : source_archive & DataType ='檔案'
	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
	
	$lib_imagemagic =  'D:/webroot/NDAPArchive/mvc/lib/ImageMagick-7.0.3-7-portable-Q16-x64/convert.exe ';
    $lib_ffmpeg 	=  'D:/webroot/NDAPArchive/mvc/lib/ffmpeg-20161122-d316b21-win64-static/bin/ffmpeg.exe ';
	
	$folder_map = array( '檔案'=>'ARCHIVE' , '公報'=>'GAZETTE' , '議事錄'=>'RECORD', '議事影音'=>'MEDIA','活動照片'=>'PHOTO');
	$file_allow = array('jpg','png','tiff','tif','wmv','mp4','mp3');
	
	
	define('_SOURCE_LOCATION','F:/videos/mp4/');   // 原始伺服器位置
	define('_STORE_LOCATION','F:/DigitalStore/');
	
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO'); 
	
	$source_table     = 'metadata';
	$target_condition = "zong ='議事影音' AND dobj_json !='[]'";
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
			
			//if($meta['dobj_json']!='[]') continue;
			//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
			$paser_count++;
			
			echo "\n".str_pad($paser_count,6,'0',STR_PAD_LEFT).'. '.$meta['system_id']." : ";
			
			$dobj_store = _STORE_LOCATION.$folder_map[$meta['zong']];
			
			// 依據不同類型檔案設定搜尋項目
			$source = json_decode($meta['source_json'],true);
			$dobject = json_decode($meta['dobj_json'],true);
			
			
			
			switch($meta['zong']){
			  
			  case '議事影音':
			    
				$source_location = _SOURCE_LOCATION;
				
				
				//-- 建構檔案儲存結構
				if(!is_dir($dobj_store.'/browse/'.$meta['collection'].'/')) mkdir($dobj_store.'/browse/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/saved/'.$meta['collection'].'/')) mkdir($dobj_store.'/saved/'.$meta['collection'].'/',0777,true);
				if(!is_dir($dobj_store.'/thumb/'.$meta['collection'].'/')) mkdir($dobj_store.'/thumb/'.$meta['collection'].'/',0777,true);
				
				$browse_location = $dobj_store.'/browse/'.$meta['collection'].'/';
				$thumb_location = $dobj_store.'/thumb/'.$meta['collection'].'/';
				  
				echo "\n".$meta['collection'].':PASER START..';
				
				
				//-- 列表資料
				$file_paths = array();
				$file_times = array();
				
			    if(trim($source['record_filename'])){
				  $file_paths[] = $source['record_filename'];	
				  $file_times[$source['record_filename']] = ['stime'=>$source['record_stime'], 'etime'=>$source['record_etime']];
				}
				if(trim($source['record_filename2'])){
				  $file_paths[] = $source['record_filename2'];
				  $file_times[$source['record_filename2']] = ['stime'=>$source['record_stime2'], 'etime'=>$source['record_etime2']];				  
				}
				if(trim($source['record_filename3'])){
				  $file_paths[] = $source['record_filename3'];	
				  $file_times[$source['record_filename3']] = ['stime'=>$source['record_stime3'], 'etime'=>$source['record_etime3']];
				}
				
				
				$dobject['dopath']   = $folder_map[$meta['zong']].'/';
				$dobject['count']    = count($file_paths);
				$dobject['position'] = [];
				
				//處理檔案
				foreach($file_paths as $i=> $do_name ){
				  
				  $file = $source['record_type'] =='mp3' ? $do_name.'.mp3' : $do_name.'.mp4'; 
				  
				  if(!is_file(_SOURCE_LOCATION.$file))	continue;  // 檔案不存在
				  
				  //移動檔案
				  if(!is_file($browse_location.$file)){
					echo copy(_SOURCE_LOCATION.$file,$browse_location.$file) ? 'OK':'fail';  
				  }else{
					echo 'saved.' ;  
				  }
				  
				  if($source['record_type'] =='mp3'){
					
					copy('D:/webroot/NDAPArchive/systemFiles/iconv_mp3.png',$thumb_location.$file.'.png');
                    
					// 設定參數
					$dobject['position'][($i+1)] = ['file'=>$file,'pointer'=>$file_times[$do_name]];    
					  
				  }else{
					
					//建立檔案縮圖
					echo " W:";
					exec($lib_ffmpeg.' -n -ss 00:00:10 -i '.$browse_location.$file.' -r 1/1 -updatefirst 1 '.$thumb_location.$file.'.jpg' ,$result); 
					echo 'OK';
					  
					// 建立網頁用影像 jpg
					// 每十分鐘取一張圖  ffmpeg -i test.flv -vf fps=1/600 thumb%04d.bmp
					//exec($lib_ffmpeg.' -ss '.$file_times[$file]['stime'].'  -i '._SOURCE_LOCATION.$file.' -vf fps=1/60 '.$file_webs.$hashcode.'_%02d.jpg',$result); 
					  
					echo " SF:";
					exec($lib_ffmpeg.' -n -ss '.$file_times[$do_name]['stime'].' -i '.$browse_location.$file.' -r 1/1 -updatefirst 1 '.$thumb_location.$file.'-'.preg_replace('/:/','',$file_times[$do_name]['stime']).'.jpg' ,$result); 
					echo " EF:";
					$media_time = explode(':',$file_times[$do_name]['etime']);
					if(intval($media_time[2])){
					  $media_time[2] = str_pad((intval($media_time[2])-1),2,'0',STR_PAD_LEFT);  
					}else{
					  $media_time[2] = 59;
					  if(intval($media_time[1])){
						$media_time[1] = str_pad((intval($media_time[2])-1),2,'0',STR_PAD_LEFT);	
					  }else{
						$media_time[1] = 59;
						$media_time[0] = str_pad((intval($media_time[0])-1),2,'0',STR_PAD_LEFT);					  
				      }  
					}
					exec($lib_ffmpeg.' -n -ss '.join(':',$media_time).'  -i '.$browse_location.$file.' -r 1/1 -updatefirst 1 '.$thumb_location.$file.'-'.preg_replace('/:/','',$file_times[$do_name]['etime']).'.jpg' ,$result); 
					echo 'OK';
					
					// 設定參數
					$dobject['position'][($i+1)] = ['file'=>$file,'pointer'=>$file_times[$do_name]];
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
			
			if(!count($dobject['position'])){
			  echo "NO FILE!";	
			  continue;
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