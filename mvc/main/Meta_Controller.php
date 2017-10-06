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
	public function batch($Records,$Action,$Setting=''){
	  switch(strtolower($Action)){
        default: $this->Model->ADMeta_Execute_Batch($Records,strtolower($Action),$Setting); break;	  
	  }
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 受領任務
	public function batchexport($Records){
	  $this->Model->ADMeta_Export_Selected($Records);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 下載打包
	public function getexport($ExportName){
	  $this->Model->ADMeta_Access_Export_File($ExportName);
	  self::data_output('file','',$this->Model->ModelResult);
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
	
	
	/***== [ Meta Admin Module ] 檔案編輯模組  ==***/
	
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
	
	
	
	
	/***== [ 控管數位檔案函數 ] ==***/
	
	// AJAX: 讀取數位設定檔
	public function doconf($DataType,$Folder){  
	  $action = $this->Model->ADMeta_Read_Dobj_Profile($DataType,$Folder);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	// AJAX: 重新命名勾選數位檔
	public function dorename($DataType,$Folder,$FilePreName,$FileStartNum,$DataJson){  
	  $action = $this->Model->ADMeta_Dobj_Batch_Rename($DataType,$Folder,$FilePreName,$FileStartNum,$DataJson);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 重新排序數位檔
	public function doreorder($DataType,$Folder,$DataJson){  
	  $action = $this->Model->ADMeta_Dobj_Batch_Reorder($DataType,$Folder,$DataJson);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 刪除勾選數位檔
	public function dodele($DataType,$Folder,$DataJson,$Recapture=''){  
	  $verificationCode = isset($_SESSION['turing_string']) ? $_SESSION['turing_string'] : NULL;	
	  $action = $this->Model->ADMeta_Dobj_Batch_Delete($DataType,$Folder,$DataJson,$Recapture,$verificationCode);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 打包數位原始檔案
	public function doprepare($DataType,$Folder,$DoFileName){  
	  $action = $this->Model->ADMeta_Dobj_Prepare($DataType,$Folder,$DoFileName);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// File: 下載數位原始檔案
	public function dodownload($DownloadHash){  
	  $action = $this->Model->ADMeta_Dobj_Get_Download($DownloadHash);
	  self::data_output('file','',$this->Model->ModelResult); 
	}
	
	// File: 專案儲存數位檔案
	public function dopackage($DataType,$Folder,$DataJson,$ProjectNo){  
	  $action = $this->Model->ADMeta_Dobj_Project_Import($DataType,$Folder,$DataJson,$ProjectNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	/*
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
	
	
	*/
	
	/* [ Dobj Edit Module ] 數位檔案設定	*/
	
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
	
	
	/* [ File Upload Module ] 檔案上傳設定	*/
	
	
	// AJAX: 上傳檢查
	public function uplinit( $data ){
      $this->Model->ADMeta_Upload_Task_Initial($data); 
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 上傳圖片
	public function upldobj( $Zong, $FolderId, $TimeFlag , $DataPass ){
      $_FILES['file']['lastmdf'] = $_REQUEST['lastmdf'];
	  $this->Model->ADMeta_Uploading_Dobj( $Zong, $FolderId , $TimeFlag , $DataPass , $_FILES); 
	  self::data_output('upload','',$this->Model->ModelResult);
	}
	
	// AJAX: 上傳結束
	public function uplend( $Zong, $FolderId, $TimeFlag){
      $this->Model->ADMeta_Upload_Task_Finish($Zong , $FolderId, $TimeFlag); 
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 刪除上傳資料
	public function upldel( $PassData){
      $this->Model->ADMeta_Process_Upload_Delete($PassData); 
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// AJAX: 刪除上傳資料
	public function uplimport( $PassData){
      $this->Model->ADMeta_Process_Upload_Import($PassData); 
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	
	// POST: 更新議員頭像
	public function mbrpho($DataNo=''){
	  $this->Model->ADMeta_Upload_Member_Photo($DataNo,$_FILES);
	  self::data_output('html','admin_callback_reloadportrait',$this->Model->ModelResult); 
	}
	
  }
  
  
  
  
  
?>


