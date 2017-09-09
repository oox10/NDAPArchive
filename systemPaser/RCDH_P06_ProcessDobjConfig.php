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
	$lib_ffprobe 	=  'D:/webroot/NDAPArchive/mvc/lib/ffmpeg-20161122-d316b21-win64-static/bin/ffprobe.exe ';
	
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
			
			// 如果沒有數位檔案設定，則跳過
			if($meta['dobj_json']=='[]') continue;
			
			//$collect = array_map(function($field){ return htmlentities($field,ENT_QUOTES, "UTF-8");   },$collect);  // 轉換奇怪符號為HTML編碼
			$paser_count++;
			
			
			$dobj_store = _STORE_LOCATION.$folder_map[$meta['zong']];
			
			// 依據不同類型檔案設定搜尋項目
			$source = json_decode($meta['source_json'],true);
			$dobject = json_decode($meta['dobj_json'],true);
			$doconfig = array();
			
			switch($meta['zong']){
			  
			  case '議事影音':
			    
				$browse_location =  $dobj_store.'/browse/'.$meta['collection'].'/';
				$profile_location = $dobj_store.'/profile/'.$meta['collection'].'.conf';
				
				
				echo "\n".$meta['collection'].':PASER START..';
				
				
				$dofiles = array_slice(scandir($browse_location),2);
				
				if(!count($dofiles)){
				  echo "NO DOFile!"; continue;	
				}
				
				foreach($dofiles as $i=>$dofile){
				  
				  $dopath = $browse_location.$dofile;
				  
				  $file_type = strtolower(pathinfo($dopath,PATHINFO_EXTENSION ));
				  
				  if($file_type=='mp4'){
					
					$fconfig = [
				      'file'   => $dofile,
					  'width'  => 0,
					  'height' => 0,
					  'length' => 0,
					  'thumb'  => $dofile.'.jpg',
					  'order'  => ++$i,
					  'update' => date('Y-m-d H:i:s'),
					  'editor' => 'RCDH'
					];
					$result=[];
					exec($lib_ffprobe .'-v error -of flat=s=_ -select_streams v:0 -show_entries stream=height,width '.$dopath ,$result); 
				    foreach($result as $attr){
					  list($a,$v) = explode('=',$attr);	
					  if(preg_match('/width/',$a)){
						$fconfig['width'] = intval($v);  
					  }else{
						$fconfig['height'] = intval($v);    
					  }
					}  
					
					$second=[];
					exec($lib_ffprobe .'-v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$dopath ,$second); 
					echo "\n".$dopath.' : '. $second[0];
					$fconfig['length'] = intval(ceil($second[0]));
					
					
					
					$fconfig['duration'] = str_pad(intval($fconfig['length']/3600),2,'0',STR_PAD_LEFT).':'.str_pad(intval(intval($fconfig['length']%3600)/60),2,'0',STR_PAD_LEFT).':'.str_pad(intval($fconfig['length']%60),2,'0',STR_PAD_LEFT);
					$doconfig[] = $fconfig;
				  
				  }else if($file_type=='mp3'){
					$fconfig = [
				      'file'   => $dofile,
					  'width'  => 150,
					  'height' => 150,
					  'length' => 0,
					  'thumb'  => $dofile.'.png',
					  'order'  => ++$i,
					  'update' => date('Y-m-d H:i:s'),
					  'editor' => 'RCDH'
					];  
					
					$second=[];
					exec($lib_ffprobe .'-v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$dopath ,$second); 
					echo "\n".$dopath.' : '. $second[0];
					$fconfig['length'] = intval(ceil($second[0]));
					$fconfig['duration'] = str_pad(intval($fconfig['length']/3600),2,'0',STR_PAD_LEFT).':'.str_pad(intval(intval($fconfig['length']%3600)/60),2,'0',STR_PAD_LEFT).':'.str_pad(intval($fconfig['length']%60),2,'0',STR_PAD_LEFT);
					
					$doconfig[] = $fconfig;  
				  }
				
				}  
				
				file_put_contents($profile_location,json_encode($doconfig));		
                echo "configed.";
				ob_flush();
				flush();
				
				break;
			  
			  default: 
				exit(1);
				break;
			}
			
			ob_flush();
			flush();
		  }
		  
		  $limit+=$frame;  
		  
	  }
	  
    } catch (Exception $e) {
      echo $e->getMessage();
    }	
	
	
	
?>