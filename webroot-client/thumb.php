<?php 
  
  /*
    1. 必為 jpg
	2. 必為 system 縮圖
  */
  require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
  session_start();
 
  if(isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['LOGIN_TOKEN'])){
	$photo_location = is_file(_SYSTEM_DIGITAL_FILE_PATH.$_REQUEST['src']) ? _SYSTEM_DIGITAL_FILE_PATH.$_REQUEST['src'] :_SYSTEM_ROOT_PATH.'SystemFiles/photo_error.png';
  }else{
    $photo_location = _SYSTEM_ROOT_PATH.'SystemFiles/photo_error.png';
  }
  
  ob_get_clean();
  header('Content-Type: image/'.strtolower(pathinfo($photo_location,PATHINFO_EXTENSION )));
  readfile($photo_location);  
  
?>