<?php
  /*
  *   [RCDH10 Admin Module] - Customized Module
  *   2016 ed.  
  */
  /*
  *    AHAS Admin - Apply Admin Module
  *    申請管理模組
  *      - Apply_Model.php
  *      -- SQL_AdApply.php
  *      - admin_Apply.html5tpl.php
  *      -- theme/css/css_Apply_admin.css
  *      -- js_Apply_admin.js  
  */
	
  class Project_Controller extends Admin_Controller{
    
	public function __construct(){
	  parent::__construct();	
      $this->Model = new Project_Model;
	}
	
	// PAGE: 申請管理介面 O
	public function index(){
	  $this->Model->GetUserInfo();
	  $this->Model->ADProject_Project_List();
	  self::data_output('html','admin_project',$this->Model->ModelResult);
	}
	
	// AJAX: 讀取專案資料 O
	public function read($DataNo){
	  $this->Model->ADProject_Get_Project_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	
	// FILE: 下載勾選檔案
	public function export($DataNo,$PaserString=''){
	  $result = $this->Model->ADProject_Export_Project_Elements($DataNo,$PaserString);
	  self::data_output('file','',$this->Model->ModelResult);
	}
	
	// AJAX: 刪除專案元素
	public function pjeremove($DataNo,$FileName){
	  $this->Model->ADProject_Remove_Project_Item($DataNo,$FileName);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	
	// AJAX: 儲存資料 
	public function save($DataNo , $DataJson){
	  $action = $this->Model->ADProject_Save_Project_Data($DataNo,$DataJson);
	  if($action['action']){
		$this->Model->ADProject_Get_Project_Data($action['data']);
	  }
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: 刪除資料 
	public function dele($DataNo ){
	  $action = $this->Model->ADProject_Dele_Project_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	/*
	
	
	// AJAX: 上傳清單
	public function upllist($DataNo=0){
	  $this->Model->ADApply_Upload_Apply_File($DataNo,$_FILES);

	}
	
	// AJAX: 處理清單
	public function uplupd($DataNo=0,$FileName=''){
	  $this->Model->ADApply_Process_Apply_File($DataNo,$FileName);
	  $this->Model->ADApply_Get_Apply_Data($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	// PAGE: 申請管理介面 O
	public function statis($DateFrom='',$DateTo=''){
	  $this->Model->GetUserInfo();
	  $this->Model->ADApply_Get_Apply_Admin();
	  $this->Model->ADApply_Get_Apply_Record($DateFrom,$DateTo);
	  self::data_output('html','admin_apply_record',$this->Model->ModelResult);
	}
	
	// PAGE: 原件調閱 O
	public function reserve($DateFrom=''){
	  $date_search = $DateFrom && strtotime($DateFrom) ? $DateFrom : '_all';	
	  $this->Model->GetUserInfo();
	  $this->Model->ADApply_Get_Reserve_Record($date_search);
	  self::data_output('html','admin_apply_booking',$this->Model->ModelResult);
	}
	
	*/
	
	
	
	
	
	
	
	
	
  }
  
  
  
  
  
?>