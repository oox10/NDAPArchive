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
	private $ProjectConf   = array();     // 專案資料夾成員
	private $TaskData      = array();  // 處理檔案設定
	
	private $ImageMagicPath = '';  // 影像處理軟體位置
	
	private $TimeStart     = '';
	
	public function __construct($db,$task=0){
	  $this->DB   = $db;
	  $this->Task = $task;
	  $DB_Access = $db->DBLink->query("SELECT * FROM system_upload WHERE utkid=".$task." AND _process='';");
      if( $DB_Access->execute() && $upload = $DB_Access->fetchAll(PDO::FETCH_ASSOC) ){
	    $this->UploadRecord = $upload;
      }
	  
	  $project = array();
	  $DB_Project = $db->DBLink->query("SELECT spno,pjelements,task_type FROM system_tasks LEFT JOIN system_project ON uid=spno WHERE utk=".$task.";");
      if( $DB_Access->execute() && $project = $DB_Project->fetch(PDO::FETCH_ASSOC) ){
	    $this->ProjectConf = json_decode($project['pjelements'],true);
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
		  throw new Exception('目標檔案不存在');  	
		}
		
		$this->TaskData['source']   = $UplRecord['store'];
		$this->TaskData['saveroot'] = $UplRecord['saveto'];
		$this->TaskData['folder'] = $UplRecord['folder'];
		$this->TaskData['finename'] = $UplRecord['name'];
		$this->TaskData['timeflag'] = $UplRecord['last'];
		
		
		
		// get collector data
	    $DB_Access = $this->DB->DBLink->query("SELECT uid,user_name,user_idno,user_mail FROM user_login LEFT JOIN user_info ON uid=uno WHERE user_id='".$UplRecord['user']."';");
        if( $DB_Access->execute() && $user_data = $DB_Access->fetch(PDO::FETCH_ASSOC) ){
	      $this->UserData = $user_data;
        }else{
		  throw new Exception('查無使用者');  
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
			case 'video/mp4':  $imgprocess = self::process_mp4($this->TaskData['source'],$this->TaskData['saveroot'],$this->TaskData['folder'],$this->TaskData['finename'],$this->TaskData['timeflag']); break;  
			  
			default:break;
		}
		
		if(!isset($imgprocess) || !is_array($imgprocess)){
		  throw new Exception('影像處理失敗');	
		}
		
		
		
		return true;
	  
	  } catch (Exception $e) {
        return $e->getMessage();
      }
	
	}
	
	//-- 處理影片檔案
	public function process_mp4($FileSource , $FileRoot , $MainFolder, $FileName, $TimeFlag){
	  $imgprocess = array('o'=>'','s'=>'','t'=>'');	
	  
	  $file_store 	= $FileRoot.'saved/'; // 原始檔案位置 
	  $file_thumb 	= $FileRoot.'thumb/'; // 原始檔案位置 
	  
	  $media_time   = explode(':',$TimeFlag);
	  
	  //確認位置是否存在
	  if(!is_dir($file_store))  mkdir($file_store,0777,true);  
	  if(!is_dir($file_thumb))  mkdir($file_thumb,0777,true);  
	  
	  $lib_ffmpeg 	  =  _SYSTEM_ROOT_PATH.'mvc/lib/ffmpeg-20161122-d316b21-win64-static/bin/ffmpeg.exe ';
	   
	  $this->ProjectConf[$this->UploData['name']]['status']  = '_import';  
	  
	  //切割影片
	  exec($lib_ffmpeg." -y -ss ".$media_time[0]." -t ".($media_time[1]-$media_time[0])." -i ".$FileSource." -vcodec copy -acodec copy  ".$file_store.$FileName ,$result); 
	  
	  //建立檔案縮圖
	  exec($lib_ffmpeg.' -n -ss 00:00:01 -i '.$file_store.$FileName.' -r 1/1 -updatefirst 1 '.$file_thumb.$FileName.'.jpg' ,$result); 
	  
	  $imgprocess['o']  = $FileName;
	  $imgprocess['t']  = $FileName.'.jpg';    // 處理縮圖
	  
      return $imgprocess;
	}
	
	
	//-- 處理影像檔案
	public function process_image($itype , $FileSource , $FileRoot , $MainFolder, $FileName){
	  
	  $imgprocess = array('o'=>'','s'=>'','t'=>'');	
	  
	  $file_store 	= $FileRoot.'saved/'; // 原始檔案位置 
	  $file_thumb 	= $FileRoot.'thumb/'; // 原始檔案位置 
	  
	  //確認位置是否存在
	  if(!is_dir($file_store))  mkdir($file_store,0777,true);  
	  if(!is_dir($file_thumb))  mkdir($file_thumb,0777,true);  
	  
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
      if(isset($this->ProjectConf[$this->UploData['name']])){
		$this->ProjectConf[$this->UploData['name']]['status']  = '_import';
	  }
	  
	  return true;
	}
	
	//-- 最終任務
	public function finishTask(){
      $this->DB->DBLink->query("UPDATE system_tasks LEFT JOIN system_project ON uid=spno SET time_finish='".date('Y-m-d H:i:s')."' , _status='_finish',pjelements='".json_encode($this->ProjectConf)."' WHERE utk='".$this->Task."';");  
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