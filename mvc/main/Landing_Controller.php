<?php
class Landing_Controller extends Admin_Controller{
    
  /*
  *   [RCDH10 Archive Module] - Customized Module
  *   2017 ed.  
  */
  
  /*
  *    Forest  AreaBooking System - Client Module
  *    客戶端模組
  *      - Landing_Model.php
  *      -- SQL_Client.php
  *      - client_landing.html5tpl.php / 首頁
  *      -- theme/css/css_landing.css
  *      -- js_landing.js
  */
  
	//-- 共用物件
	public  $IPSaveZoon;
	
	//初始化要執行的動作以及物件
	public function __construct(){
	  parent::__construct();	
      
	  $this->Model = new Landing_Model;		
	  //$this->IPSaveZoon = json_decode(_USER_IP_SAVE_ZOON,true);
	  //header('HTTP/1.0 400 Bad Request', true, 400);
	  //exit(1);
	}
	
	
	/***--- LANDING ACTION SET ---***/
	
	// PAGE: client landing page
	public function index(){   
	  $this->Model->Access_Get_Client_Post_List();
	  self::data_output('html','client_landing',$this->Model->ModelResult);
	}
	
	
	// PAGE: guest login
	public function guest(){
	  if(!isset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT'])){
		
		$login = $this->Model->Guest_Login_Process();
	    
		if(!$login['action']){
		  self::redirectTo('index.php?act=Error/error/'.$this->Model->Get_Action_Message($login['message']));
	      exit(1);  
	    }
		
		$result = $this->Model->Account_Inter_System($login['data']['lgkey']);
	    
		if(!$result['action']){
		  self::redirectTo('index.php?act=Error/error/'.$this->Model->Get_Action_Message($login['message']));
	      exit(1);
	    }   
	    self::data_output('session','CLIENT',$this->Model->ModelResult);
	  
	  }
	  
	  if(isset($_SESSION[_SYSTEM_NAME_SHORT]['RrDIRECT']) && $_SESSION[_SYSTEM_NAME_SHORT]['RrDIRECT'] !=''){
		// 舊有連結導回搜尋頁面
		self::redirectTo('index.php?'.$_SESSION[_SYSTEM_NAME_SHORT]['RrDIRECT']);   
		unset($_SESSION[_SYSTEM_NAME_SHORT]['RrDIRECT']);
	  }else{
		self::redirectTo('index.php?act=Archive');    
	  }
	}
	
	
	// AJAX: system login paser
	// [input] LoginRefer : encode string 
	public function signin($LoginRefer = array()){
	  $this->Model->Account_Login_Process($LoginRefer);
	  self::data_output('json','',$this->Model->ModelResult);  
	}
	
	
	// PAGE: account inter system 
	public function inter($InterKey=''){
	  $result = $this->Model->Account_Inter_System($InterKey);
	  if(!$result['action']){
		self::data_output('html','client_landing',$this->Model->ModelResult); 
	    exit(1);
	  }
	  self::data_output('session','CLIENT',$this->Model->ModelResult);
	  self::redirectTo('index.php?act=Archive');   
	}
	
	
	
	/***--- REGIST ACTION SET ---***/
	
	// AJAX: system account register //使用者註冊申請
	public function signup($Captcha,$RegData){
	  $captcha_save = isset($_SESSION['turing_string']) ? $_SESSION['turing_string'] : false ;
	  $captcha_user = strlen($Captcha) ? $Captcha : '';
	  $regist = $this->Model->Account_Sign_Up($captcha_save,$captcha_user,$RegData);
	  if($regist['action']) $this->Model->Account_System_Space_Allocat($regist['data']['type'],$regist['data']['account']);
	  self::data_output('json','',$this->Model->ModelResult); 
	  unset($_SESSION['turing_string']);
	}
	
	// PAGE: system account start //使用者帳號啟動，開啟重設密碼頁面
	public function start($regCode=''){
	  $this->Model->Access_Get_Client_Post_List();
	  $result  = $this->Model->Check_Regist_Code($regCode);
	  if($result['action']){
	    self::data_output('html','client_repass',$this->Model->ModelResult);
	  }else{
	  	self::redirectTo('index.php?act=Error/error/'.$this->Model->Get_Action_Message($result['message']));
	  }
	}
	
	// AJAX: system account pw initial //新使用者設定密碼
	public function repass($PassData){
	  $reg_code = isset($_SESSION[_SYSTEM_NAME_SHORT]['regcode']) ? $_SESSION[_SYSTEM_NAME_SHORT]['regcode'] : '';
	  $reg_uno  = isset($_SESSION[_SYSTEM_NAME_SHORT]['user_no']) ? $_SESSION[_SYSTEM_NAME_SHORT]['user_no'] : NULL;
	  $reg_uid  = isset($_SESSION[_SYSTEM_NAME_SHORT]['user_id']) ? $_SESSION[_SYSTEM_NAME_SHORT]['user_id'] : NULL;
	  $this->Model->Account_Password_Initial($reg_code,$reg_uno,$reg_uid,$PassData);
	  self::data_output('json','',$this->Model->ModelResult); 
	}
	
	
	// AJAX: system password seset mail
	// [input] DataRefer : encode string 
	public function reseter($DataRefer = array()){
	  $verificationCode = isset($_SESSION['turing_reset']) ? $_SESSION['turing_reset'] : NULL;	  
	  $this->Model->Account_Sent_RePassword_Mail($DataRefer,$verificationCode);
	  self::data_output('json','',$this->Model->ModelResult);
	  unset($_SESSION['turing_reset']);
	}
	
	
    // PAGE: account logout 
	public function logout(){
      //session_unset();
	  unset($_SESSION[_SYSTEM_NAME_SHORT]['CLIENT']);
	  $this->redirectTo('index.php');
	}
	
	// PAGE: account unlogin  
	public function unlogin(){
      $this->Model->ModelResult[0] = array('action'=>false,'message'=>'_LOGIN_INFO_ACCOUNT_UNLOGIN');
	  self::data_output('html','client_account',$this->Model->ModelResult);
	}
	
	
	
	
	
	
	
	
	
	
	/***--- POST ACTION SET ---***/
	// PAGE: get client announcement
	public function getann($DataNo){   
	  $this->Model->Get_Client_Post_Target($DataNo);
	  self::data_output('json','',$this->Model->ModelResult);
	}
	
	
	
}
?>