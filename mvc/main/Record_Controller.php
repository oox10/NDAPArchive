<?php
  /*
  *   [RCDH10 Admin Module] - Customized Module
  *   2016 ed.  
  */
  /*
  *    Forest Admin - Record Admin Module
  *    申請管理模組
  *      - Record_Model.php
  *      -- SQL_AdRecord.php
  *      - admin_record.html5tpl.php
  *      -- theme/css/css_record_admin.css
  *      -- js_record_admin.js  
  */
	
  class Record_Controller extends Admin_Controller{
    
	public function __construct(){
	  parent::__construct();	
      $this->Model = new Record_Model;
	}
	
	// PAGE: 統計資料葉面
	public function index( $D_Start='',$D_to=''){
	  $this->Model->GetUserInfo();
	  $this->Model->ADRecord_Get_Store_Count();
	  $this->Model->ADRecord_Get_Search_Record($D_Start , $D_to);
	  $this->Model->ADRecord_Get_Access_Record($D_Start , $D_to);
	  self::data_output('html','admin_record_search',$this->Model->ModelResult);
	}
	
	// PAGE: 帳號活動紀錄
	public function sylogs($D_Start='',$D_to=''){
	  $this->Model->GetUserInfo();
	  $this->Model->ADRecord_Get_Account_Logs($D_Start,$D_to);
	  self::data_output('html','admin_record_syslogs',$this->Model->ModelResult);
	}
	
	// FILE: 下載紀錄
	public function logssearch( $D_Start='',$D_to='' ){
	  $this->Model->ADRecord_Export_Search_Logs($D_Start , $D_to);
      self::data_output('xlsx','template_ndap_search_logs.xlsx',$this->Model->ModelResult);
	}
	
	// FILE: 下載紀錄
	public function logssystem( $D_Start='',$D_to='' ){
	  $this->Model->ADRecord_Export_System_Logs($D_Start , $D_to);
      self::data_output('xlsx','template_ndap_system_logs.xlsx',$this->Model->ModelResult);
	}
	
	
	
	
  }
  
  
  
  
?>