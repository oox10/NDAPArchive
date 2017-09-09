<?php 
  
  /*
    HAKKArchive 影片輸出
	1. 必為 mp4
  */
  require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
  require_once(dirname(dirname(__FILE__)).'/mvc/lib/VideoStream.php');
  session_start();
  if(isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['LOGIN_TOKEN'])){
	$video_location = is_file(_SYSTEM_DIGITAL_FILE_PATH.$_REQUEST['src']) ? _SYSTEM_DIGITAL_FILE_PATH.$_REQUEST['src'] :_SYSTEM_ROOT_PATH.'SystemFile/System-Search_Query-Display_ImageNotFound.jpg';
	ob_get_clean();
	$video = new VideoStream( $video_location );  // from https://gist.github.com/ranacseruet/9826293
	$video->start(); 
  }else{
	header("HTTP/1.0 404 Not Found");  
	header("location:index.php");
  }
  
?>