<?php

  /*  
  *   IISPhoArchive Jobs Process
  *   -  Import User Upload File To Archive    2016-01-22
  *   
  */
  ini_set('memory_limit', '1000M');	
  require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
  
  require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');
  
  
  
  /*
    RCDH 數位檔案輸入物件
	DB.table: system_tasks , system_upload
	
  */
  
  class ImportDobj{
	
	private $DB;
	private $Task		   = '';       // 任務序號 system_task.utkid
	private $UploadRecord  = array();  // 任務關聯資料列表  system_upload 
	
	private $UploData	   = array();  // 上傳資料主紀錄
	private $UserData      = array();  // 上傳使用者紀錄，可用於命名或寫入備註
	private $MetaData      = array();  // 詮釋資料紀錄
	private $FolderConf    = null;  // 黨案資料夾內容設定
	private $TaskData      = array();  // 處理檔案設定
	
	private $ImageMagicPath = '';  // 影像處理軟體位置
	
	private $TimeStart     = '';
	
	public function __construct($db,$task=0){
	  $this->DB   = $db;
	  $this->Task = $task;
	  $DB_Access = $db->DBLink->query("SELECT * FROM system_upload WHERE utkid=".$task." AND _upload!='' AND _process='';");
      if( $DB_Access->execute() && $upload = $DB_Access->fetchAll(PDO::FETCH_ASSOC) ){
	    $this->UploadRecord = $upload;
      }
	  
	  $this->TimeStart = microtime(true); 
	  $this->ImageMagicLib = _SYSTEM_ROOT_PATH.'mvc/lib/ImageMagick-7.0.3-7-portable-Q16-x64/convert.exe ';
	  
	} 
	
	public function __destruct(){
	  echo microtime(true) - $this->TimeStart;; 
	}
	
	
	//-- 準備處理輸入資料
	//  $UplRecord : 單筆的system_upload record 
	protected function prepareImport($UplRecord){
		
	  try{ 
	    
		$this->TaskData      = array();
	    $this->UserData      = array();
	    $this->MetaData      = array();
		
		// get record data 
		$this->UploData = $UplRecord;
		$this->DB->DBLink->query("UPDATE system_upload SET _process='".date('Y-m-d H:i:s')."' WHERE urno = ".$this->UploData['urno'].";");
		
		// check file 
	    if(!file_exists($UplRecord['store']) || filesize($UplRecord['store'])!=$UplRecord['size'] ){
		  throw new Exception('上傳暫存錯誤');  	
		}
		
		$this->TaskData['source']   = $UplRecord['store'];
		$this->TaskData['saveroot'] = $UplRecord['saveto'];
		$this->TaskData['folder'] = $UplRecord['folder'];
		$this->TaskData['finename'] = $UplRecord['name'];
		
		// get uploader data
	    $DB_Access = $this->DB->DBLink->query("SELECT uid,user_name,user_idno,user_mail FROM user_login LEFT JOIN user_info ON uid=uno WHERE user_id='".$UplRecord['user']."';");
        if( $DB_Access->execute() && $user_data = $DB_Access->fetch(PDO::FETCH_ASSOC) ){
	      $this->UserData = $user_data;
        }else{
		  throw new Exception('查無使用者');  
		}
	    
		// read folder profile 
		if(!is_array($this->FolderConf)){
		  $folder_conf = $UplRecord['saveto'].'profile/'.$UplRecord['folder'].'.conf';
		  if(file_exists($folder_conf)){
		    $this->FolderConf = json_decode(file_get_contents($folder_conf),true);
		  }else{
			$this->FolderConf = array('store'=>$UplRecord['saveto'].'browse/'.$UplRecord['folder'].'/',"saved"=>date('Y-m-d H:i:s'),"items"=>[]);  
		  }
		}
		
		return true;
	  
	  } catch (Exception $e) {
        return $e->getMessage();
      }
	}
	
	
	//-- 執行匯入
	private function activeImport(){
	  $meta = array();
	  
	  try{
        
		$iptc = array();
		$exif = array();
		//$extract = self::extractImageMeta($this->TaskData['source'],$this->UploData['name']); 
		
		// 儲存IPTC&EXIF萃取資料
		//file_put_contents(_SYSTEM_FILE_PATH.$meta['_store']."metadata/".$meta['identifier'].'.meta',print_r($extract['meta'], true),FILE_APPEND | LOCK_EX);
		
		//$this->MetaData = $meta;
		//$this->MetaData['system_id'] = $meta_no;
          
		//-- Process Image
		switch($this->UploData['mime']){
			case 'image/jpeg': $imgprocess = self::process_image( 'jpg'  , $this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
			case 'image/png' : $imgprocess = self::process_image( 'png'  , $this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
			case 'image/raf': $imgprocess = self::process_image('raf',$this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
			case 'image/cr2':
			case 'image/x-canon-cr2':$imgprocess = self::process_image('cr2',$this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
			case 'image/tiff': 
			  if($this->UploData['type']=='dng'){
				$imgprocess = self::process_image( 'dng' , $this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']);   
			  }else{
				$imgprocess = self::process_image( 'tif' , $this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']);   
			  }  
			  break;
			case 'image/dng': $imgprocess = self::process_image('dng',$this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;  
			case 'application/octet-stream':  // 
			  switch($this->UploData['type']){
			    case 'cr2':$imgprocess = self::process_image('cr2',$this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
				case 'dng':$imgprocess = self::process_image('dng',$this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
				case 'raf':$imgprocess = self::process_image('raf',$this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename']); break;
			    default:break;
			  }
			default:break;
		}
		
		if(!isset($imgprocess) || !is_array($imgprocess)){
		  throw new Exception('影像處理失敗');	
		}
		
		// 填入DO profile
		$img_size = explode(' x ',$imgprocess['s']);
		$newfileconf = [
		  'file'=>$this->TaskData['finename'],
		  'width'=> intval($img_size[0]),
		  'height'=> intval($img_size[1]),
		  'size'=>filesize($this->TaskData['saveroot'].'browse/'.$this->TaskData['folder'].'/'.$this->TaskData['finename']),
		];
		
		$rewrite = 0;
		foreach($this->FolderConf['items'] as $i=>$dof ){
		  if( $dof['file'] == $this->TaskData['finename'] ){
			$this->FolderConf['items'][$i]=$newfileconf; 
		    $rewrite = 1;
			break;
		  }
		}
		if(!$rewrite){
		  $this->FolderConf['items'][]	= $newfileconf;
		}
		
		return true;
	  
	  } catch (Exception $e) {
        return $e->getMessage();
      }
	
	}
	
	//-- 處理影像檔案檔案
	public function process_image($itype , $FileSource , $FileRoot , $MainFolder, $FileName){
	  
	  $imgprocess = array('o'=>'','s'=>'','t'=>'');	
	  
	  $file_store 	= $FileRoot.'saved/'.$MainFolder.'/'; // 原始檔案位置 
	  $file_browse 	= $FileRoot.'browse/'.$MainFolder.'/'; // 原始檔案位置 
	  $file_thumb 	= $FileRoot.'thumb/'.$MainFolder.'/'; // 原始檔案位置 
	  
	  
	  //確認位置是否存在
	  if(!is_dir($file_store))  mkdir($file_store,0777,true);  
	  if(!is_dir($file_browse))  mkdir($file_browse,0777,true);  
	  if(!is_dir($file_thumb))  mkdir($file_thumb,0777,true);  
	  
	  //確認原始資料是否有重複 //僅處理原始檔案
	  if(file_exists($file_store.$FileName)) copy($file_store.$FileName,$FileRoot.'trach/'.$FileName.'-'.microtime(true));
	  
	  // 將檔案預先處理成為  JPG
      switch(strtolower($itype)){
		case 'jpg':  $file_process = self::process_jpg($FileSource,$file_store.$FileName); break;
		case 'png':  $file_process = self::process_png($FileSource,$file_store.$FileName); break;
        case 'tif':  $file_process = self::process_tif($FileSource,$file_store.$FileName); break;
        case 'cr2':  $file_process = self::process_cr2($FileSource,$file_store.$FileName); break;
        case 'dng':  $file_process = self::process_dng($FileSource,$file_store.$FileName); break;
		case 'raf':  $file_process = self::process_raf($FileSource,$file_store.$FileName); break;
        default: return false; 
	  }
	  
	  if(!$file_process || !is_file($file_process)){ return false; }
	  
	  list($width, $height, $type, $attr) = getimagesize($file_process);  
	  $imgprocess['o']  = $width.' x '.$height;
	  $imgprocess['s']  = self::image_resize($file_process,$file_browse.$FileName,'m');  // 處理系統圖片
	  $imgprocess['t']  = self::image_resize($file_process,$file_thumb.$FileName,'s');    // 處理縮圖
	  
      return $imgprocess;
	}
	
	//-- 處理 JPEG 檔案
	public function process_jpg($fileSource,$fileSave){
	  if(!copy($fileSource,$fileSave)){ return false; }
	  return $fileSave;
	}
	
	//-- 處理 PNG 檔案
	public function process_png($fileSource , $fileSave){
	  if(!copy($fileSource,$fileSave)){ return false; }
	  return $fileSave;
	}
	
	//-- 處理 TIFF 檔案
	public function process_tif($fileSource , $fileSave){
	  $file_extract = preg_replace(array('/original/','/\.tif$/'),array('extract','.jpg'),$fileSave);
	  if(!copy($fileSource,$fileSave)){ return false; }
	  exec($this->ImageMagicLib.' '.$fileSave.' '.$file_extract ,$output, $return_var);
	  if(count($output)) file_put_contents("process.log",print_r($output, true),FILE_APPEND | LOCK_EX);
	  if(!is_file($file_extract)){
	    $im = new Imagick( $fileSave );
	    $im->setImageFormat( 'jpg' );
	    $im->writeImage( $file_extract);
	    $im->clear();
	    $im->destroy();
	  }	
	  return $file_extract;
	}
	
	//-- 處理 RAF 檔案
	public function process_raf($fileSource , $fileSave){
	  $file_extract = preg_replace(array('/original/','/\.raf$/'),array('extract','.jpg'),$fileSave);
	  if(!copy($fileSource,$fileSave)){ return false; }
	  exec($this->ImageMagicLib.' '.$fileSave.' '.$file_extract ,$output, $return_var);	
	  //exec('gm -convert '.$fileSave.' '.$file_extract ,$output, $return_var);
	  if(count($output)) file_put_contents("process.log",print_r($output, true),FILE_APPEND | LOCK_EX);	
	  if(!is_file($file_extract)){
		$im = new Imagick( $fileSave );
	    $im->setImageFormat( 'jpg' );
	    $im->writeImage( $file_extract );
	    $im->clear();
	    $im->destroy();	  
	  }
	  return $file_extract; 
	}
	
	//-- 處理 DNG 檔案
	public function process_dng($fileSource , $fileSave){
	  
	  $file_extract = preg_replace(array('/original/','/\.dng$/'),array('extract','.jpg'),$fileSave);
	  if(!copy($fileSource,$fileSave)){ return false; }
	  exec($this->ImageMagicLib.' '.$fileSave.' -sigmoidal-contrast 3,0%  '.$file_extract ,$output, $return_var);	
	  //exec(_SYSTEM_ROOT_PATH.'mvc/lib/ImageMagick-7.0.0-0-portable-Q16-x64/convert.exe '.$fileSave.' -brightness-contrast 50x25  '.$file_extract ,$output, $return_var);	
	  
	  if(count($output)) file_put_contents("process.log",print_r($output, true),FILE_APPEND | LOCK_EX);	
	  if(!is_file($file_extract)){
		$im = new Imagick( $fileSave );
	    $im->setImageFormat( 'jpg' );
	    $im->writeImage( $file_extract );
	    $im->clear();
	    $im->destroy();	  
	  }
	  return $file_extract; 
	}
	
	//-- 處理 CR2 檔案
	public function process_cr2($fileSource , $fileSave){
	  $file_extract = preg_replace(array('/original/','/\.cr2$/'),array('extract','-preview3.jpg'),$fileSave);
	  $extract_location = preg_replace('/original.*$/','extract/',$fileSave);
	  if(!copy($fileSource,$fileSave)){ return false; }
	  exec(_SYSTEM_ROOT_PATH.'mvc/lib/exiv2-0.25-win/exiv2 -ep3 -l '.$extract_location.' '.$fileSave,$output, $return_var);
	  if(count($output)) file_put_contents("process.log",print_r($output, true),FILE_APPEND | LOCK_EX);
	  if(!is_file($file_extract)){
		$im = new Imagick( $fileSave );
		$im->setImageFormat( 'jpg' );
		$im->writeImage( $file_extract);
		$im->clear();
		$im->destroy();		
	  }
	  return $file_extract; 
	}

	public function image_resize($filesource,$filesave,$size){	
		/** PHP GD : resize an image using GD library */

		// File and new size //the original image has 800x600
		$filename = $filesource;
		
		// Get new sizes
		list($width, $height) = getimagesize($filename);
		if($width > $height){
		  $bound = $size == 'm' ? 1440 : 150;
		}else{
		  $bound = $size == 'm' ? 1080 : 200; 	
		}
		$quilty  = $size == 'm' ? 80 : 50;
		$base 	 = $width >= $height ? $width : $height; 
		if($size == 'm'){
		  
		  if( $width > 1440 || $height > 1080 ){
			$config = ($width >= $height) ?  ' -resize 1440 ' : ' -resize x1080 ';
		    $config.= '-quality 85 ';
		    exec($this->ImageMagicLib.$config.$filesource.' '.$filesave ,$result); 
		  }else{
			copy($filesource,$filesave);   
		  }
		}else{
		  $config = ($width >= $height) ?  ' -thumbnail 150 ' : ' -thumbnail x200 ';
          $config.= '-quality 50 ';  
		  exec($this->ImageMagicLib.$config.$filesource.' '.$filesave ,$result);
		}
		
		if(is_file($filesave)){
		  list($niw, $nih) = getimagesize($filesave);	
		  return  $niw.' x '.$nih;
		}else{
		  return false;	
		}
		
		// 以下為舊轉檔設定
		/* IM - 需導入php IM lib 
		
		$thumb = new Imagick($filesource);
		$thumb->setImageCompression(imagick::COMPRESSION_JPEG); 
        $thumb->setImageCompressionQuality($quilty); 
		$thumb->resizeImage($newwidth,$newheight,Imagick::FILTER_LANCZOS,1);
		
		// #20170208 updated
		$orientation = $thumb->getImageOrientation(); 
		switch($orientation) { 
          case imagick::ORIENTATION_BOTTOMRIGHT: 
            $image->thumb(new ImagickPixel(),  180); // rotate 180 degrees 
            break; 

          case imagick::ORIENTATION_RIGHTTOP: 
            $image->thumb(new ImagickPixel(),  90); // rotate 90 degrees CW 
            break; 

          case imagick::ORIENTATION_LEFTBOTTOM: 
            $image->thumb(new ImagickPixel(),  -90); // rotate 90 degrees CCW 
            break; 
        } 
        
		$thumb->writeImage($filesave);
		$thumb->destroy(); 
		*/
		
		/* GD - 畫質很差
		$thumb	 	= imagecreatetruecolor($newwidth, $newheight); // Load
		$source 	= imagecreatefromjpeg($filename);
        imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height); // Resize
        imagejpeg($thumb , $filesave,100);
		imagedestroy($thumb);
		*/
		
		
    }
	
	//-- 抽取 image meta 
	public function extractImageMeta($FileSorce,$FileOrlName = ''){
	    /*
		$extract_image = array();    
		$size = getimagesize($FileSorce, $info);
		if(isset($info['APP13'])) $iptc = iptcparse($info['APP13']);	
		$exif = exif_read_data( $this->TaskData['source'] ,'EXIF' );
		$photodate = isset($exif['DateTimeOriginal'])&&strtotime($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : '';
		if(isset($exif['DateTimeOriginal'])&&strtotime($exif['DateTimeOriginal'])){
		  $photodate = 	$exif['DateTimeOriginal'];	
		}else if(isset($exif['DateTimeCreate'])&&strtotime($exif['DateTimeCreate'])){
		  $photodate = 	$exif['DateTimeCreate'];			
		}else if(isset($exif['DateTime'])&&strtotime($exif['DateTime'])){
		  $photodate = 	$exif['DateTime'];	
		}else if(isset($iptc['2#055'])){
		  $photodate = 	$iptc['2#055'];	
		}else{
		  $photodate = '0000-00-00 00:00:00';	
		}
		$photodate     = '';
		$photolocat    = isset($iptc['2#025'][2]) && trim($iptc['2#025'][2]) ? $iptc['2#025'][2] : '';
		$photokeywords = isset($iptc['2#025'][1])&&trim($iptc['2#025'][1]) ? join(';',array_filter(preg_split('/\s+/',$iptc['2#025'][1]))):'';
		*/
		
		
		// get meta
		exec(_SYSTEM_ROOT_PATH.'mvc/lib/exiv2-0.25-win/exiv2.exe -Pkt '.$FileSorce  , $meta_extract, $return_var);
		$extract = array(
			'phototime'=>'',
			'creater'  =>array(),
			'keywords'=>array(),
			'model'=>'',
			'photodesc'=>'',
			'photoloc'=>array(),
			'score'=>0,
			'copyright'=>array('RCDH'),
		    'meta' => $meta_extract
		);
		
		foreach($meta_extract as $meta_line){
			$meta = preg_split('/\s+/',$meta_line);
			$tag = array_shift($meta);
			switch($tag){ 
				
				case 'Exif.Image.ImageDescription':
				case 'Iptc.Application2.Caption':
				  $desc = trim(join(' ',$meta));
				  if($desc) $extract['photodesc'] = $desc;
				  break;
				
				case 'Exif.Photo.DateTimeOriginal':
				  $tmp_time = strtotime(trim(join(' ',$meta)));
				  if($tmp_time){
					$extract['phototime'] =  date('Y-m-d H:i:s',$tmp_time);
				  }
				  break;
				
				case 'Exif.Image.Artist':
				case 'Iptc.Application2.Byline':
				case 'Xmp.dc.creator':
				  $extract['creater'][] = trim(join(' ',$meta));
				  break;
				  
				  
				case 'Iptc.Application2.City':
                case 'Xmp.photoshop.City':
                  $extract['photoloc'][] = trim(join(' ',$meta));
                  break;				  
				
				case 'Exif.Image.Model':                              //NIKON D800
				  $extract['model'] = trim(join(' ',$meta));
				  break;
				
				case 'Xmp.xmp.Rating':
				  $extract['score'] = intval(join('',$meta));
				  break;
				  
				//case 'Exif.Image.XPKeywords':  同Iptc.Application2.Keywords
				case 'Iptc.Application2.Keywords':
				  $kwstring = join(' ',$meta); // 排除作者
				  if(!in_array($kwstring,$extract['creater']) && !in_array(strtoupper($kwstring),$extract['creater'])){
					foreach($meta as $m){
					  $extract['keywords'][] = $m;	
					}
				  }
				  break;
				  
			    /*  預設為國傳司
				case 'Iptc.Application2.Copyright':                   //taiwan panorama magazine // haoprophoto
				case 'Exif.Image.Copyright': 
				  $extract['copyright'][] = trim(join(' ',$meta));		
				  break;
				*/
			    
			}
		}
		
		if(!count($extract['creater'])){
	      $extract['creater'][] = $this->UploData['creater'];
		}
		
		// set copyright
		// 中文編碼現在未知，須先轉換為 unicode \u465 格式
		$insert_string  = '外交部國際傳播司';
		$unicode_string = substr(json_encode(array($insert_string)),2,-2);
		exec(_SYSTEM_ROOT_PATH.'mvc/lib/exiv2-0.25-win/exiv2.exe -M"set Exif.Image.Copyright '.$unicode_string.'" '.$FileSorce  , $meta_extract, $return_var);
		
		/*
		[6] => Exif.Image.Make                               NIKON CORPORATION
        [7] => Exif.Image.Model                              NIKON D800
	    [15] => Exif.Photo.DateTimeOriginal                   2012:12:26 15:53:56
        [16] => Exif.Photo.DateTimeDigitized                  2012:12:26 15:53:56
		[56] => Iptc.Application2.DateCreated                 2012-12-26
        [57] => Iptc.Application2.TimeCreated                 15:53:56+08:00
	
	    [13] => Exif.Image.Artist                             Chin,Hung Hao
        [60] => Iptc.Application2.Byline                      CHUANG KUN JU
        [75] => Xmp.dc.creator                                CHUANG KUN JU
        
		[61] => Iptc.Application2.Copyright                   taiwan panorama magazine
		[15] => Exif.Image.Copyright                          haoprophoto
		
	    [53] => Iptc.Application2.Keywords                    chuang kun ju
        [54] => Iptc.Application2.Keywords                    台南土溝 社區營造 美術館
        [55] => Iptc.Application2.Keywords                    土溝社區
		*/
		
	  return $extract;	
	}
	
	//-- 錯誤紀錄
	public function processfalse($falseLogs){
	  $logs = json_decode($this->UploData['_logs'],true);
	  $logs[date('Y-m-d H:i:s')] = 'Task fail:'.$falseLogs;	
	  $this->DB->DBLink->query("UPDATE system_upload SET _process='".date('Y-m-d H:i:s')."',_logs='".json_encode($logs)."' WHERE urno = ".$this->UploData['urno'].";");
	}
	
	
	//-- 最終完成手續
	public function finishImport(){
      
	  $logs = json_decode($this->UploData['_logs'],true);
	  $logs[date('Y-m-d H:i:s')] = 'Task Process Complete';	
	  
	  // 完成上載queue
	  $this->DB->DBLink->query("UPDATE system_upload LEFT JOIN system_tasks ON utk=utkid SET task_done=(task_done+1),_archived='".date('Y-m-d H:i:s')."',_logs='".json_encode($logs)."' WHERE urno = ".$this->UploData['urno'].";");	   
      
	  // 移除暫存檔
	  unlink($this->TaskData['source']);	
	
	  return true;
	}
	
	//-- 最終任務
	public function finishTask(){
      
	  // resave profile 
	  if(is_array($this->FolderConf)){
		$folder_conf = $this->TaskData['saveroot'].'profile/'.$this->TaskData['folder'].'.conf';
		file_put_contents($folder_conf,json_encode($this->FolderConf));
	  }
	  
	  $this->DB->DBLink->query("UPDATE system_tasks SET time_finish='".date('Y-m-d H:i:s')."' WHERE utk='".$this->Task."';");  
	  return true;
	}
	
	//-- 程序主函式 
	public function processImport(){
	  
	  $newfile = array();
	  
	  try{
		
		echo "[TASK]: Process DO Import Start, taskid:".$this->Task." / ".$this->TimeStart." \n";
		
        if(!count($this->UploadRecord)){
		  throw new Exception('目前無待輸入資料');  	
		}   
        
		
		
		foreach($this->UploadRecord as $ufile){
		  
		  echo "\n".$ufile['name'].":";
		  
		  //-- 讀取檔案
		  $check = self::prepareImport($ufile);
		  if($check!==true){
			self::processfalse($check);
			echo $check;
			continue;
		  }			  
		  
		  //-- 處理匯入
		  $active = self::activeImport();
		  if($active!==true){
			self::processfalse($active); 
			echo $check;
            continue;
		  }     
		  
		  //-- 完成匯入手續 
		  $finish = self::finishImport();
		  if($finish!==true){
			echo $finish;
		  }
		  echo " done.\n";
		}
		
		self::finishTask();
        
	  } catch (Exception $e) {
        echo $e->getMessage()."\n";
      }
	  
	  echo "[TASK] END / ".date('Y-m-d H:i:s');
	
	}
  }
  
  /*================================*
    TASK START : import upload file
  =================================*/
  
  // check task regist id 
  if(!isset($argv[1])){
	echo "task no fail"; 
	exit(1);
  }
  
  $task_num = $argv[1];
  if(!intval($task_num)){
	echo "task no fail";  
    exit(1);
  }
  
  $db = new DBModule;
  $db->db_connect('PDO');
  $import_update = new ImportDobj($db,$task_num);
  $import_update->processImport();
  
?>