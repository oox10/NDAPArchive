<?php 
  
  /*
    HAKKArchive 圖片快速顯示
	1. 必為 jpg
	2. 必為 system 縮圖
  */
  require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
  session_start();
  if(isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['LOGIN_TOKEN'])){
	$photo_location = is_file(_SYSTEM_DIGITAL_FILE_PATH.'MEDIA/thumb/'.$_REQUEST['src']) ? _SYSTEM_DIGITAL_FILE_PATH.'MEDIA/thumb/'.$_REQUEST['src'] :_SYSTEM_ROOT_PATH.'SystemFile/System-Search_Query-Display_ImageNotFound.jpg';
	ob_get_clean();
	header('Content-Type: image/jpeg');
	readfile($photo_location);  
  }else{
	header("HTTP/1.0 404 Not Found");  
	header("location:index.php");
  }
?>