<?php

  /*
  *   [RCDH10 Archive Module] - Customized Module
  *   2016 ed.  
  */
  /*
  *    AHAS Client - Account Use Module
  *    資料庫查詢模組模組
  
  */

  class Archive_Controller extends Admin_Controller{
  
	//-- 共用物件
	public  $IPSaveZoon;
	public  $SearchEngine = array('MySQL','Elasticsearch');
	
	
	//初始化要執行的動作以及物件
	public function __construct(){
	  parent::__construct();	
      
	  $this->Model = new Archive_Model;		
	  //$this->IPSaveZoon = json_decode(_USER_IP_SAVE_ZOON,true);
	  //header('HTTP/1.0 400 Bad Request', true, 400);
	  //exit(1);
	}
	
	/***--- QUERY ACTION SET ---***/
	
	
	// PAGE: archive index page
	public function index(){   
	  
	  // 處理查詢
	  // default query all archive
	  
	  
	  $result = $this->Model->Archive_ReForm_Search('index',$Page='1-20');		// 處理查詢字串
	  if($result['action']){
		$this->Model->Archive_Regist_Query();		  // 轉換實際查詢語法  
	    $this->Model->Archive_Built_ElasticSearch();  // 轉換實際查詢語法  
	    $this->Model->Archive_Active_Query($Page);    // 執行查詢取得資源
		$this->Model->Archive_Process_Result();       // 重整資料
		$this->Model->Archive_Get_Page_List(7);       // 建構頁籤 
	  }
	  
	  $this->Model->GetUserInfo();
	  $this->Model->Archive_Get_Page_Config();  // 取得頁面設定參數
	  $this->Model->Archive_Get_Apply_List();   // 取得已申請調用資料
	  self::data_output('html','client_archive',$this->Model->ModelResult);
	}
    
	// PAGE: archive search page
	public function search($Query='',$Page='1-20'){
	  
	  //$this->Model->Access_Get_Client_Post_List();
	  
	  // 處理查詢
	  $result = $this->Model->Archive_ReForm_Search('search',$Query);		// 處理查詢字串
	  if($result['action']){
		$this->Model->Archive_Regist_Query();		  // 轉換實際查詢語法  
	    $this->Model->Archive_Built_ElasticSearch();  // 轉換實際查詢語法  
	    $this->Model->Archive_Active_Query($Page);    // 執行查詢取得資源
		$this->Model->Archive_Process_Result();       // 重整資料
		$this->Model->Archive_Get_Page_List(7);       // 建構頁籤 
	  }
	  
	  $this->Model->GetUserInfo();
	  $this->Model->Archive_Get_Page_Config();        // 取得頁面設定參數
	  $this->Model->Archive_Get_Apply_List();   	  // 取得已申請調用資料
	  
	  self::data_output('html','client_archive',$this->Model->ModelResult);
	}
	
	// PAGE: archive search page
	public function export($Type='',$DataList=''){
	  $this->Model->Archive_Export_User_Select_Meta($Type,$DataList); 
	  self::data_output('csv','',$this->Model->ModelResult);
	}
	
	// AJAX: get member refer app
	public function mbrapp($MemberName){
	  $this->Model->Get_Member_App_Data($MemberName);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// PAGE: archive get appply page
	public function myapply($ApplyCode='',$Page='1-20'){ 
	
	  // 處理查詢
	  $result = $this->Model->Archive_ReForm_Search('myapply',$ApplyCode);		// 處理查詢字串
	  if($result['action']){
		$this->Model->Archive_Regist_Query();		  // 轉換實際查詢語法  
	    $this->Model->Archive_Built_ElasticSearch();  // 轉換實際查詢語法  
	    $this->Model->Archive_Active_Query($Page);    // 執行查詢取得資源
		$this->Model->Archive_Process_Result();       // 重整資料
		$this->Model->Archive_Get_Page_List(7);       // 建構頁籤 
	  }
	  
	  $this->Model->GetUserInfo();
	  $this->Model->Archive_Get_Page_Config();        // 取得頁面設定參數
	  $this->Model->Archive_Get_Apply_List();   // 取得已申請調用資料
	  self::data_output('html','client_archive',$this->Model->ModelResult);
	}
	
	
	
	/***--- APPLY ACTION SET ---***/
	
	// PAGE: archive apply page
	public function apply(){   
	  //$this->Model->Access_Get_Client_Post_List();
	  
	  // AHAS : 調閱申請必須是會員帳號
      if(!isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['ACCOUNT_TYPE']) || $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']['ACCOUNT_TYPE']=='GUEST'){
		$this->Model->ModelResult[] = array('action'=>false,'message'=>array('_APPLY_MUST_IS_MEMBER'));
		self::data_output('html','client_account',$this->Model->ModelResult);
		exit(1);
	  }
	  
	  $this->Model->GetUserInfo();
	  $this->Model->Archive_Get_Page_Config();  // 取得頁面設定參數
	  $this->Model->Archive_Get_Apply_List();   // 取得已申請調用資料
	  $this->Model->Archive_Get_Apply_Queue();  // 取得待申請資料
	  self::data_output('html','client_apply',$this->Model->ModelResult);
	
	}
	
	// AJAX: apply add //資料加入申請單
	public function applyadd($DataList){
	  $this->Model->User_Apply_Modify($DataList,'add');
	  self::data_output('json','',$this->Model->ModelResult); 
	}
    
	// AJAX: apply add //資料移出申請單
	public function applydel($DataList){
	  $this->Model->User_Apply_Modify($DataList,'del');
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: apply for //提出申請單
	public function applyfor($DataList){
	  $this->Model->User_Apply_Submit($DataList);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
    // AJAX: applied //完成申請
	public function applied($DataId,$DataList){
	  $regist = $this->Model->User_Apply_Preprocess($DataId,$DataList); 
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: search //查詢我的申請
	public function applyask($DataId){
	  $regist = $this->Model->User_Apply_Search($DataId);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	// AJAX: apply doc download //下載准駁單
	public function applylicense($DataId){
	  $regist = $this->Model->User_Apply_Download($DataId);
	  self::data_output('pdf','print_applyresult',$this->Model->ModelResult); 
	}
	
  }