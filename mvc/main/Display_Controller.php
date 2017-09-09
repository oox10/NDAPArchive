<?php

  /*
  *   [RCDH10 Archive Module] - Customized Module
  *   2016 ed.  
  */
  /*
  *    AHAS Client - Digital Object Use Module
  *    數位物件顯示模組
  
  */

  class Display_Controller extends Admin_Controller{
  
    
	//-- 共用物件
	public  $IPSaveZoon;
	public  $SearchEngine = array('MySQL','Elasticsearch');
	
	
	//初始化要執行的動作以及物件
	public function __construct(){
	  parent::__construct();	
      
	  $this->Model = new Display_Model;		
	  //$this->IPSaveZoon = json_decode(_USER_IP_SAVE_ZOON,true);
	  //header('HTTP/1.0 400 Bad Request', true, 400);
	  //exit(1);
	}
    
    // AJAX: display initial
	public function initial($AccKey){
	  // 接收顯示CODE，呈現數位檔案
	  $resourse_map = isset($_SESSION[_SYSTEM_NAME_SHORT]['Data_Link_Map']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Data_Link_Map'] : '';
	  
	  $this->Model->Regist_Display_Data($resourse_map,$AccKey);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// PAGE: display image
	public function image($ResouseKey){   
	  $this->Model->GetUserInfo();
	  $this->Model->Display_Process_Meta($ResouseKey);       // 重整資料
	  self::data_output('html','client_show',$this->Model->ModelResult); 
	  
	}
	
	// PAGE: display video
	public function video($ResouseKey){
	  $this->Model->GetUserInfo();
	  $this->Model->Display_Process_Meta($ResouseKey);       // 重整資料
	  self::data_output('html','client_video',$this->Model->ModelResult); 
	}
	
    // AJAX: 建立數位物件顯示架構
	public function built($RecorseCode,$PageCode=''){
	  $dobj_conf = isset($_SESSION[_SYSTEM_NAME_SHORT]['DOBJCONF']) ? $_SESSION[_SYSTEM_NAME_SHORT]['DOBJCONF']: array();
	  $dobj_view = isset($_SESSION[_SYSTEM_NAME_SHORT]['DOBJVIEW']) ? $_SESSION[_SYSTEM_NAME_SHORT]['DOBJVIEW']: false;  //影像閱覽模式  online / other 可否公開連結
	  $this->Model->Built_Display_Object($RecorseCode,$PageCode,$dobj_conf,$dobj_view );
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	
	// AJAX: 讀取數位檔案
	public function loadimg($PageCode=''){
	  $img_storeno 	= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_StoreNo'])    ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_StoreNo'] :'';
	  $img_address 	= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Access_Add']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Access_Add']:''; 
	  $img_encodekey= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Encode_Key']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Encode_Key'] : '';
	  $img_block_set= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Block_Set']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Block_Set'] : '';
	  $dobj_conf = isset($_SESSION[_SYSTEM_NAME_SHORT]['DOBJCONF']) ? $_SESSION[_SYSTEM_NAME_SHORT]['DOBJCONF']: array();
	  
	  $result = $this->Model->Decode_Image_Code( $PageCode , $img_storeno , $img_address , $img_encodekey, $img_block_set , $dobj_conf);
	  self::data_output('image','',$this->Model->ModelResult); 
	}
	
	// AJAX: 讀取數位檔案
	public function loadmp4($PageCode=''){
	  $img_storeno 	= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_StoreNo'])    ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_StoreNo'] :'';
	  $img_address 	= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Access_Add']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Access_Add']:''; 
	  $img_encodekey= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Encode_Key']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Encode_Key'] : '';
	  $img_block_set= isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Block_Set']) ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Block_Set'] : '';
	  $dobj_conf = isset($_SESSION[_SYSTEM_NAME_SHORT]['DOBJCONF']) ? $_SESSION[_SYSTEM_NAME_SHORT]['DOBJCONF']: array();
	  
	  $result = $this->Model->Decode_Image_Code( $PageCode , $img_storeno , $img_address , $img_encodekey, $img_block_set , $dobj_conf);
	  self::data_output('media','',$this->Model->ModelResult); 
	}
	
	
	
	// AJAX: 解除鎖定
	public function unlock($PointX,$PointY=0){  
      $acc_code  = isset($_SESSION[_SYSTEM_NAME_SHORT]['Image_Resouce_Code'])    ? $_SESSION[_SYSTEM_NAME_SHORT]['Image_Resouce_Code'] :''; 	
	  $this->Model->Access_Checker_Unlock($PointX,$PointY,$acc_code);       // 重整資料
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	
	// PAGE: print Image
	public function ciprint($ImgAddress){
	  $this->Model->ModelResult[] = array('action'=>true,'data'=>base64_decode(str_replace('*','/',$ImgAddress)));
	  self::data_output('html','print_clientimage',$this->Model->ModelResult);
	}
	
	
  
  }	