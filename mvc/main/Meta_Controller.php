<?php
  /*
  *   [RCDH10 Admin Module] - Customized Module
  *   2016 ed.  
  */
  
  /*
  *    NDAP Admin - Meta Admin Module
  *    詮釋資料建檔管理模組
  *      - Meta_Model.php
  *      -- SQL_AdMeta.php
  *      - admin_meta.html5tpl.php
  *      -- theme/css/css_meta_admin.css
  *      -- js_meta_admin.js  
  *      - admin_built.htmltpl.php
  *      -- theme/css/css_built_admin.css  
  *      -- js_built_admin.js  
  */
  
  
  class Meta_Controller extends Admin_Controller{
    
	public function __construct(){
	  parent::__construct();	
      $this->Model = new Meta_Model;
	}
	
	// PAGE: 資料管理介面 O
	public function index($Page='1-50',$Search=''){
	  $this->Model->GetUserInfo();
	  $result = $this->Model->ADMeta_Process_Filter($Search);
	  $this->Model->ADMeta_Execute_Search($result['data']['esparams'],$Page);
	  $this->Model->ADMeta_Get_Page_List(5);
	  self::data_output('html','admin_meta',$this->Model->ModelResult);
	}
	
	
	// AJAX: 受領任務
	public function batch($Records,$Action,$Setting){
	  switch(strtolower($Action)){
        case 'export': break;		
		default: $this->Model->ADMeta_Execute_Batch($Records,strtolower($Action),$Setting); break;	
	  }
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	
	
	/*
	// AJAX: 取得資料內容
	public function read($DataNo){
	  $this->Model->ADTasks_Get_Task_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 受領任務
	public function receive($DataNo){
	  $this->Model->ADTasks_Receive_Task($DataNo);
	  $this->Model->ADTasks_Get_Task_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 放棄任務
	public function giveup($DataNo){
	  $this->Model->ADTasks_Giveup_Task($DataNo);
	  $this->Model->ADTasks_Get_Task_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	*/
	
	// PAGE: 資料管理介面 O
	public function editor($BookNo,$DataNo){
	  $this->Model->GetUserInfo();
	  //$this->Model->ADMeta_Get_Meta_Config($Type);
	  $result = $this->Model->ADMeta_Get_Task_Resouse($BookNo,$DataNo);
	  
	  if($result['data']['form_mode'] == '議事影音'){
		self::data_output('html','admin_built_media',$this->Model->ModelResult);  
	  }else if($result['data']['form_mode'] == '議員傳記'){
		self::data_output('html','admin_built_biography',$this->Model->ModelResult);  
	  }else{
		self::data_output('html','admin_built_print',$this->Model->ModelResult);    
	  }
	
	}
	
	/*
	// PAGE: 資料管理介面 O
	public function review($DataNo){
	  $this->Model->GetUserInfo();
	  $this->Model->ADBuilt_Get_Task_Resouse($DataNo,'review');
	  $this->Model->ADBuilt_Get_Task_Element($DataNo);
	  self::data_output('html','admin_built',$this->Model->ModelResult);
	}
	*/
	
	
	// AJAX: 取得資料內容
	public function readmeta($DataNo){
	  $this->Model->ADMeta_Get_Item_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 儲存資料 
	public function save($DataNo,$DataJson){  
	  $action = $this->Model->ADMeta_Save_Edit_Data($DataNo,$DataJson); //$_SESSION[_SYSTEM_NAME_SHORT]['METACOLLECTION']暫時沒用到
	  if($action['action']){
		$this->Model->ADMeta_Process_Meta_Update($action['data']);  // 更新系統meta
	  }
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 儲存多媒體分件
	public function media($DataNo,$DataJson){  
	  $action = $this->Model->ADMeta_Save_Media_Tags($DataNo,$DataJson);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	
	
	// AJAX: 新增資料 
	public function newaitem($TaskNo,$DataJson){
	  $action = $this->Model->ADBuilt_Newa_Item_Data($TaskNo,$_SESSION[_SYSTEM_NAME_SHORT]['METACOLLECTION'],$DataJson);
	  if($action['action']){
		$this->Model->ADBuilt_Get_Item_Data($TaskNo, $action['data']);
	  }
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 完成資料 
	public function doneitem($TaskNo,$DataNo){
	  $this->Model->ADBuilt_Done_Item_Data($TaskNo,$DataNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 刪除資料 
	public function deleitem($TaskNo,$DataNo){
	  $this->Model->ADBuilt_Dele_Item_Data($TaskNo,$DataNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 完成任務 
	public function finish($TaskNo){
	  $this->Model->ADBuilt_Finish_Work_Task($TaskNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 退回任務 
	public function goback($TaskNo){
	  $this->Model->ADBuilt_Return_Work_Task($TaskNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
    
	// AJAX: 確認任務 
	public function checked($TaskNo){
	  $this->Model->ADBuilt_Checked_Work_Task($TaskNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	// FILE: 下載任務資料 
	public function export($TaskNoString){
	  $this->Model->ADBuilt_Export_Work_Task($TaskNoString);
	  self::data_output('xlsx','template_built_task_export.xlsx',$this->Model->ModelResult);
	}
	
	// AJAX : 
	public function doedit($DataNo,$PageName,$ConfString){
	  $this->Model->ADMeta_DObj_Conf_Save($DataNo,$PageName,$ConfString);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX : 
	public function doview($DataNo,$PageName,$HideFlag){
	  $this->Model->ADMeta_DObj_Display_Switch($DataNo,$PageName,$HideFlag);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	/*
	// AJAX: 取得資料內容
	public function read($DataNo){
	  $this->Model->ADMeta_Get_Meta_Data($DataNo);
	  $this->Model->ADMeta_Get_Meta_DObj($DataNo);
	  
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 儲存資料 
	public function save($DataNo , $DataJson){
	  if($DataNo=='_addnew'){
	    $action = $this->Model->ADPost_Newa_Post_Data($DataJson);
	  }else{  
	    $action = $this->Model->ADMeta_Save_Meta_Data($DataNo,$DataJson);
	  }
	  
	  if($action['action']){
		$this->Model->ADMeta_Get_Meta_Data($action['data']);
	  }
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	
	
	*/
	
	
  }
  
  
  
  
  
?>


