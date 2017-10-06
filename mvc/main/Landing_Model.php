<?php

  class Landing_Model extends Admin_Model{
    
    
    /*[ System Function Set ]*/ 
	
	
	//-- User Account Sign Up  // 會員註冊
	// [input] : Captcha  :  server side chptcha code => from $_SESSION['turing_reset']
	// [input] : UserCap  :  client side chptcha keyin
	// [input] : UserData :  user sign up data => array(.....)
	public function Account_Sign_Up($Captcha=false , $UserCap='',$UserRegist=''){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  
	  try{
		
		// check captcha
		if(!$Captcha || $Captcha != $UserCap){
		  $result['data']['reg_captcha_input']='';	
		  throw new Exception('_REGISTER_ERROR_CAPTCHA_TEST_FAIL');
		}
		
		$user_data = json_decode(base64_decode(str_replace('*','/',rawurldecode($UserRegist))),true); 
		
		// check email
		if(!isset($user_data['user_mail']) || !filter_var($user_data['user_mail'], FILTER_VALIDATE_EMAIL)){ 
		  $result['data']['user_mail']='';
		  throw new Exception('_REGISTER_ERROR_EMAIL_TEST_FAIL');
		}
		
		// check other field
		if(!isset($user_data['user_name']) || !$user_data['user_name'] ){ 
		  $result['data']['user_name']='';
		  throw new Exception('_REGISTER_ERROR_EMAIL_TEST_FAIL');
		}
		
		// check account used
        $DB_CHK = $this->DBLink->prepare(SQL_Account::CHECK_REGISTER_MAIL_USED()); 
	    if( $DB_CHK->execute(array(':user_mail'=>$user_data['user_mail'])) && $DB_CHK->fetchColumn() ){
		  $result['data']['user_mail']='';
		  throw new Exception('_REGISTER_ERROR_EMAIL_USED');	
		}

		/*== start insert account data ==*/
		
		// STEP.1: insert table:user 
		$DB_OBJ		= $this->DBLink->prepare(SQL_Account::INSERT_NEW_USER_ACCOUNT());
		//:uname,:upass,:date_register,:date_open,:date_access,:ustate
		$uname = $user_data['user_mail'];
		$upass = '';
		$accdate = date('Y-m-d H:i:s', strtotime('+10 year'));
			
		$DB_OBJ->bindParam(':uname',$uname,PDO::PARAM_STR);
		$DB_OBJ->bindParam(':upass',$upass,PDO::PARAM_STR);
		$DB_OBJ->bindValue(':date_register',date('Y-m-d H:i:s'));
		$DB_OBJ->bindValue(':date_open'    ,date('Y-m-d H:i:s'));
		$DB_OBJ->bindParam(':date_access',$accdate,PDO::PARAM_STR);
		$DB_OBJ->bindValue(':ustate',1,PDO::PARAM_INT);
		
		if(!$DB_OBJ->execute()){
		  throw new Exception('_REGISTER_ERROR_SQL_FAIL_USER_INSERT');			  
		}
		
		// STEP.2: insert table:user_info  
		$uid = $this->DBLink->lastInsertId('user_login');
		$group_code = 'mbr';  // 一般民眾註冊為  會員群組
		
		//:uid,:user_name,:user_staff,:user_tel,:user_mail,:user_mail2,:user_info,'5'
		$DB_OBJ = $this->DBLink->prepare(SQL_Account::INSERT_NEW_USER_INFOMATION());
		$DB_OBJ->bindParam(':uid',$uid,PDO::PARAM_STR);
		$DB_OBJ->bindValue(':user_name'		, $user_data['user_name']);
		$DB_OBJ->bindValue(':user_idno'		, isset($user_data['user_idno']) ? $user_data['user_idno'] : '' );
		$DB_OBJ->bindValue(':user_mail'		, $user_data['user_mail']);
		$DB_OBJ->bindValue(':user_staff'	, isset($user_data['user_staff']) ? $user_data['user_staff']:'' );
		$DB_OBJ->bindValue(':user_organ'	, isset($user_data['user_organ']) ? $user_data['user_organ']:'' );
		$DB_OBJ->bindValue(':user_tel'		, isset($user_data['user_tel']) ? $user_data['user_tel'] : '' );
		$DB_OBJ->bindValue(':user_address'	, isset($user_data['user_address']) ? $user_data['user_address']:'' );
		
		$DB_OBJ->bindValue(':user_age'	, isset($user_data['user_age']) ? $user_data['user_age']:'' );
		$DB_OBJ->bindValue(':user_education'	, isset($user_data['user_education']) ? $user_data['user_education']:'' );
		$DB_OBJ->bindValue(':user_major'	, isset($user_data['user_major']) ? $user_data['user_major']:'' );
		$DB_OBJ->bindValue(':user_info'	, '註冊會員' );
		
		$DB_OBJ->bindValue(':user_pri'		, 1);  
		  
		if(!$DB_OBJ->execute()){  
		  $this->DBLink->query('DELETE FROM user_login WHERE uno='.$uid);
		  throw new Exception('_REGISTER_ERROR_SQL_FAIL_USER_INFO_INSERT');			
		}
		
		// STEP.3: insert table:digital_ftpuser   // 加入註冊群組 :uno,:gno,:rno,:creater
		$DB_UGP = $this->DBLink->prepare(SQL_Account::INSERT_GROUP_MEMBER());
		$DB_UGP->bindParam(':uno',$uid,PDO::PARAM_INT);
		$DB_UGP->bindValue(':gno',$group_code);
		$DB_UGP->bindValue(':master',1);
		$DB_UGP->bindvalue(':creater','system');
		$DB_UGP->execute();
		
		// 創造空間
		self::Account_System_Space_Allocat('MEMBER',$uname);
		
		// 是否自動通過
		$DB_CONF = $this->DBLink->prepare(SQL_Account::ACCOUNT_CONFIG_SIGNUP_AUTO_ACCEPT());
		if($DB_CONF->execute() && intval($DB_CONF->fetchColumn())){
			
			// 註冊文件連結
			$reg_code = substr(md5($uname.'#'.time()),(rand(0,3)*8),8).'.'.System_Helper::generator_password(2);  
			$DB_REG= $this->DBLink->prepare(SQL_Account::INSERT_ACCOUNT_REGIST_CODE());
			$DB_REG->bindParam(':uno',$uid,PDO::PARAM_INT);	
			$DB_REG->bindParam(':reg_code',$reg_code ,PDO::PARAM_STR);	
			$DB_REG->bindValue(':reg_state','_REGIST');
			$DB_REG->bindValue(':effect_time',date('Y-m-d H:i:s',strtotime("now +1 month")));
			$DB_REG->execute();
			
			
			$user_reglink = _SYSTEM_SERVER_ADDRESS."index.php?act=Landing/start/".$reg_code;
			  
			// 設定信件內容
			$mail_to_sent = $user_data['user_mail'];
			  
			$mail_title    = "["._SYSTEM_HTML_TITLE."]-帳號開通信件";
			   
			$mail_content  = "<div>"._SYSTEM_HTML_TITLE." 帳號申請</div>";
			$mail_content .= "<div>帳號：".$user_data['user_mail']."</div>";
			$mail_content .= "<div>啟動：<a href='".$user_reglink."' target=_blank>".$user_reglink."</a></div>";
			$mail_content .= "<div>（請利用以上連結開通帳號並設定密碼，連結將於一週後(".date('Y-m-d H:i:s',strtotime("+7 day")).")失效）</div>";
			$mail_content .= "<div>有任何問題請洽：</div>";
			$mail_content .= "<div>EMAIL：<a href='mailto:"._SYSTEM_MAIL_CONTACT."'>"._SYSTEM_MAIL_CONTACT."</a></div>";
			$mail_content .= "<div> </div>";
			$mail_content .= "<div>本信由系統發出，請勿直接回覆</div>";
			  
			  
			$mail_logs = [date('Y-m-d H:i:s')=>'Regist Mail From '._SYSTEM_NAME_SHORT.'.' ];
			  
			$DB_MAILJOB	= $this->DBLink->prepare(SQL_AdMailer::REGIST_MAIL_JOB());
			$DB_MAILJOB->bindValue(':mail_type','帳號啟動');
			$DB_MAILJOB->bindValue(':mail_from',_SYSTEM_MAIL_CONTACT);
			$DB_MAILJOB->bindValue(':mail_to',$mail_to_sent);
			$DB_MAILJOB->bindValue(':mail_title',$mail_title);
			$DB_MAILJOB->bindValue(':mail_content',htmlspecialchars($mail_content,ENT_QUOTES,'UTF-8'));
			$DB_MAILJOB->bindValue(':creator','system');
			$DB_MAILJOB->bindValue(':editor','');
			$DB_MAILJOB->bindValue(':mail_date',date('Y-m-d'));
			$DB_MAILJOB->bindValue(':active_logs',json_encode($mail_logs));
			if(!$DB_MAILJOB->execute()){
			  throw new Exception('_APPLY_MAIL_REGIST_FAIL');	
			}
		}
		
		/*
		// STEP.5: insert table:digital_ftpuser   加入FTP 帳號列表  //:uid, :username, ':user_group'
	    $DB_FTP = $this->DBLink->prepare(SQL_Account::INSERT_NEW_FTP_ACCOUNT());
		$DB_FTP->bindValue(':uno',$uid,PDO::PARAM_INT);
		$DB_FTP->bindValue(':user_account',$uname);
		$DB_FTP->bindvalue(':homedir',_SYSTEM_FTPS_PATH.$uname);
		$DB_FTP->execute();
		*/
		
		$result['data']['account'] = $uname;
		$result['data']['group']   = $group_code;
		$result['data']['type']    = 'MEMBER';
		
		$result['action'] = true;
	    
	  }catch(Exception $e){
		$result['data']['reg_captcha_input']='';	  
		$result['message'][] = $e->getMessage();    
	  }
	  
	  sleep(1);
	  return $result;  
	}
	
	//-- Admin Login & check O
	// [input] : login key  :  ********.**;
	public function Check_Regist_Code($RegCode = ''){
	  $result_key = parent::Initial_Result('regist');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		// 檢查登入驗證碼
		if( !preg_match('/^[\w\d\.]{11}$/i',$RegCode)){
	      throw new Exception('_REGISTER_ERROR_REGIST_CODE_ILLEGAL');
	    }
		
		// 檢查啟動碼是否有效
		$regist_data = NULL;
		$DB_CHK	= $this->DBLink->prepare(SQL_Account::SELECT_REGISTCODE_BY_CODE());
		$DB_CHK->bindParam(':reg_code'  , $RegCode, PDO::PARAM_STR);	
		$DB_CHK->bindValue(':now'   	, date('Y-m-d H:i:s'), PDO::PARAM_STR);	
		
		// 是否存在啟動碼
		if( !$DB_CHK->execute() ){
		  throw new Exception('_REGISTER_ERROR_GET_REGIST_DATA_SQL_FAIL');
		}
		
		// 查無啟動碼
		if( !$regist_data = $DB_CHK->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_REGISTER_ERROR_GET_REGIST_CODE_OVERDUE');
		}
		
		// 啟動碼已經使用過 
		if( '_REGIST' != $regist_data['reg_state'] ){
		  throw new Exception('_REGISTER_ERROR_ACCOUNT_ALREADY_START');
		}
		
		$result['session']['regcode'] = $RegCode;
	    $result['session']['user_no'] = $regist_data['uno'];
		$result['session']['user_id'] = $regist_data['user_id'];
		
	    $result['data']['regdate'] = $regist_data['date_register'];
		$result['data']['user_id'] = str_pad(substr($regist_data['user_id'],0,5),strlen($regist_data['user_id']),'*',STR_PAD_RIGHT);
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Account Password Reset 
	// [input] : RegCode  :  REGIST Code refer data => from $_SESSION
	// [input] : UserNo   :  user.uno    			=> from $_SESSION
	// [input] : UserId   :  user.User_ID    		=> from $_SESSION
	// [input] : PassWord :  User Account Reset Password Code => urlencode(base64encode(json_pass()))  = array('regist_password01'=>'' , 'regist_password02'=>'');;
	public function Account_Password_Initial( $RegCode='', $UserNo=Null , $UserId=Null , $PassWord='' ){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  $regist_pass = json_decode(base64_decode(rawurldecode($PassWord)),true);
	  
	  try{
	    
		// 檢查相關參數
		if(!$RegCode || !$UserNo || !$UserId ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		
		// 檢查輸入的密碼
		if( !isset($regist_pass['regist_password01']) && !strlen($regist_pass['regist_password01']) && $regist_pass['regist_password01'] == $regist_pass['regist_password02'] ){
	      throw new Exception('_REGISTER_ERROR_PASS_SET_FAIL');
	    }
		
		// 進行密碼加密
		$user_pass = md5(md5($regist_pass['regist_password01']._SYSTEM_LOGIN_PW_SEED).'@'.$UserId);
		
		// 更新資料庫
		// 設定註冊狀態  reg_state  => startup
		// 設定系統狀態  user_pri   => 3
		// 開啟登入權限  user_status => 1
        // 設定密碼      user_pass 
		// 設定FTP帳號密碼 passwd
		$DB_REG = $this->DBLink->prepare(SQL_Account::ACCOUNT_START_AND_SET_PASSWORD());
	    $DB_REG->bindParam(':uno',$UserNo,PDO::PARAM_STR);
		$DB_REG->bindParam(':reg_code' , $RegCode);
		$DB_REG->bindValue(':reg_state', '_STARTUP');
		$DB_REG->bindValue(':user_status',5);
		$DB_REG->bindValue(':user_pass',$user_pass);
		$DB_REG->bindValue(':password' ,$regist_pass['regist_password01']);
		
		
		// 完成啟動
		if(!$DB_REG->execute()){
		  throw new Exception('_REGISTER_ERROR_SQL_FAIL_ACCOUNT_START');
		}
				
		$result['action'] = true;
		sleep(2);
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- System User Login
	// [input] : LoginRefer : urlencode(base64encode(json_pass()))  = array( 'account'=>... , password=...   ) ;
	public function Account_Login_Process( $LoginRefer='' ){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  $login_data = json_decode(base64_decode(rawurldecode($LoginRefer)),true);
	  
	  try{
		
        // 檢查登入參數
		if( !isset($login_data['account'])  ||  !isset($login_data['password'])  ){
	      throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	    }
	    
		// 查詢資料庫
		$DB_OBJ = $this->DBLink->prepare(SQL_Account::SELECT_ACCOUNT_LOGIN_DATA());
		if(!$DB_OBJ->execute(array('user_id'=>$login_data['account']))){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		// 取得帳戶資料
		$user_login = false;
		if( !$user_login = $DB_OBJ->fetch(PDO::FETCH_ASSOC ) ){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNFOUND');  
		}
		
		// 檢查帳戶IP
		$user_ip = System_Helper::get_client_ip();
		
		if( $user_login['ip_range']!='0.0.0.0' && $user_login['ip_range']!==''){	
		  if( !self::check_ip_in_limit($user_ip,$user_login['ip_range']) ){
		    throw new Exception('_LOGIN_INFO_USER_IP_OUT_OF_LOGIN_RANGE');  
		  }	
		}
		
		// 帳戶檢查  DB:user_login.user_status  0=>stop  1=>register  2=>reviewed  3=>unactive 4=>repass  5=>active 
		if($user_login['user_status']<5){
          switch( (string)$user_login['user_status'] ){
		    case '0': throw new Exception('_LOGIN_INFO_ACCOUNT_STATUS_DISABLED');   break;
		    case '1':
			case '2': 
			case '3': throw new Exception('_LOGIN_INFO_ACCOUNT_STATUS_UNACTIVE');   break; 
			case '4': 
			  if($login_data['password']!=$user_login['user_mail']){
				throw new Exception('_LOGIN_INFO_ACCOUNT_REPASSWD_MAIL_CHECK'); break;   
			  }
			  // 註冊repass連結
		      $reg_code = substr(md5($login_data['account'].'#'.time()),(rand(0,3)*8),8).'.'.System_Helper::generator_password(2);  
			  
			  $DB_REG= $this->DBLink->prepare(SQL_Account::INSERT_ACCOUNT_REGIST_CODE());
		      $DB_REG->bindParam(':uno',$user_login['uno'],PDO::PARAM_INT);	
		      $DB_REG->bindParam(':reg_code',$reg_code ,PDO::PARAM_STR);	
		      $DB_REG->bindValue(':reg_state','_REGIST');
		      $DB_REG->bindValue(':effect_time',date('Y-m-d H:i:s',strtotime("now +3 minutes")));
		      $DB_REG->execute();
			    
			  $result['data']['repass'] = $reg_code;
		      $result['action'] = true;
			  return $result;
			  exit(1);
			  break;
			default: throw new Exception('_LOGIN_INFO_ACCOUNT_STATUS_UNKNOW');    break;
		  }
		}
		
		// 檢查帳戶密碼
		$user_password = md5(md5($login_data['password']._SYSTEM_LOGIN_PW_SEED).'@'.$login_data['account']);
		if( $user_login['user_pw'] !== $user_password   ){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_PASSWORD_FAIL');
		}
		
		// 檢查帳戶存取日期
		$account_date_now   = strtotime('now');
		$account_date_limit = strtotime($user_login['date_access']);
		$account_date_open  = strtotime($user_login['date_open']);
		
		
		if( $account_date_now > $account_date_limit     ){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_DATE_EXPIRED');
		}
		
		if( $account_date_now < $account_date_open     ){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_STATUS_UNACTIVE');
		}
		
		
		/*--------------------------------------------------------------------------*/
		
		// 成功登入 - 註冊登入序號
		$key_zone  = rand(0,3)*8;
		$login_key = substr(md5($login_data['account'].'#'.time()),$key_zone,8).'-'.($key_zone/8);
		
        $ID_REG	= $this->DBLink->prepare(SQL_Account::INSERT_ACCOUNT_LOGIN_CODE());
		$ID_REG->bindParam(':acc_key'   , $login_key , PDO::PARAM_STR);	
		$ID_REG->bindParam(':acc_uno'   , $user_login['uno'] , PDO::PARAM_INT );	
		$ID_REG->bindValue(':acc_into'  , _SYSTEM_NAME_SHORT , PDO::PARAM_STR);
		$ID_REG->bindValue(':acc_ip'    , System_Helper::get_client_ip());
        $ID_REG->bindValue(':acc_from'  , isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'' );
		
		if(!$ID_REG->execute()){
		  throw new Exception('_SYSTEM_ERROR_LOGIN_KEY_CREAT_FAILS');
		}		

		$result['action'] = true;		
		$result['data']['lgkey']   = $login_key;

	  }catch(Exception $e){
		$result['message'][] = $e->getMessage();  
	  }  
	  return $result;
	}
	
	
	
	//-- System Guest Login
	// [input] : LoginRefer : urlencode(base64encode(json_pass()))  = array( 'account'=>... , password=...   ) ;
	public function Guest_Login_Process( $LoginRefer='' ){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  //$login_data = json_decode(base64_decode(rawurldecode($LoginRefer)),true);
	  
	  try{
		
		// 檢查帳戶IP
		$user_ip = System_Helper::get_client_ip();
		
		/*
		if( $user_login['ip_range']!='0.0.0.0' && $user_login['ip_range']!==''){	
		  if( !self::check_ip_in_limit($user_ip,$user_login['ip_range']) ){
		    throw new Exception('_LOGIN_INFO_USER_IP_OUT_OF_LOGIN_RANGE');  
		  }	
		}
		*/
		
		/*--------------------------------------------------------------------------*/
		
		// 成功登入 - 註冊登入序號
		$key_zone  = rand(0,3)*8;
		$login_key = substr(md5($user_ip.'#'.time()),$key_zone,8).'-'.($key_zone/8);
		
        $ID_REG	= $this->DBLink->prepare(SQL_Account::INSERT_ACCOUNT_LOGIN_CODE());
		$ID_REG->bindParam(':acc_key'   , $login_key , PDO::PARAM_STR);	
		$ID_REG->bindValue(':acc_uno'   , 0, PDO::PARAM_INT );	
		$ID_REG->bindValue(':acc_into'  , _SYSTEM_NAME_SHORT , PDO::PARAM_STR);
		$ID_REG->bindValue(':acc_ip'    , System_Helper::get_client_ip());
        $ID_REG->bindValue(':acc_from'  , isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'' );
		
		if(!$ID_REG->execute()){
		  throw new Exception('_SYSTEM_ERROR_LOGIN_KEY_CREAT_FAILS');
		}		

		$result['action'] = true;		
		$result['data']['lgkey']   = $login_key;

	  }catch(Exception $e){
		$result['message'][] = $e->getMessage();  
	  }  
	  return $result;
	}
	
	//-- Admin Login & check 
	// [input] : login key  :  \w{8}-\d;
	public function Account_Inter_System($LoginKey=''){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		// 檢查登入序號
	    if(!preg_match('/^[\w\d]{8}\-\d$/',$LoginKey)){
		  throw new Exception('_LOGIN_INFO_LOGIN_KEY_FAIL');
		}
	    
		// 取得登入資訊
		$login_data = NULL;
		$DB_CHK	= $this->DBLink->prepare(SQL_Account::CHECK_ACCOUNT_LOGIN_KEY());
		$DB_CHK->bindParam(':acc_key'   , $LoginKey , PDO::PARAM_STR);	
		if( !$DB_CHK->execute() || !$login_data = $DB_CHK->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_LOGIN_INFO_LOGIN_KEY_FAIL');
		}
		
		// 檢查登入有效期限
		if( ( strtotime($login_data['acc_time'])+30 ) < strtotime("now") ){
		  throw new Exception('_LOGIN_INFO_LOGIN_KEY_EXPIRED');
		} 
		
		// 註銷登入序號
		$DB_UPTKEY	= $this->DBLink->prepare(SQL_Account::CANCEL_ACCOUNT_LOGIN_KEY());
		$DB_UPTKEY->bindParam(':acc_key'   , $LoginKey , PDO::PARAM_STR);	
		$DB_UPTKEY->bindValue(':acc_active', date('Y-m-d H:i:s'), PDO::PARAM_STR);	
		$DB_UPTKEY->execute();
		
		if($login_data['uno']){
		  
		  $AccoountObj = new UserAccount($login_data['uno'],$login_data['user_id']);
		  $AccoountObj->UserFolder = _SYSTEM_USER_PATH.$login_data['user_id'].'/';
		  if(file_exists(_SYSTEM_USER_PATH.$login_data['user_id'].'/search.conf')){
			$search_profile = json_decode(file_get_contents(_SYSTEM_USER_PATH.$login_data['user_id'].'/search.conf'),true);  
		  }else{
			$conf = json_decode(_USER_PROFILE_DEFAULE,true);
		    $conf['USER_IP']  = System_Helper::get_client_ip();;
		    $conf['USER_Date']= date('Y-m-d H:i:s');  
		    file_put_contents( _SYSTEM_USER_PATH.$login_data['user_id'].'/search.conf' , json_encode($conf,JSON_UNESCAPED_UNICODE) );  
			$search_profile = $conf;
		  }
		  $account_type = 'MEMBER';
		  
		}else{
		  
		  $AccoountObj = new UserAccount(0,$LoginKey);	
		  $AccoountObj->UserFolder = _SYSTEM_GUEST_PATH.$LoginKey.'/';
		  self::Account_System_Space_Allocat('GUEST',$LoginKey);	
		  $search_profile = json_decode(file_get_contents(_SYSTEM_GUEST_PATH.$LoginKey.'/search.conf'),true);
		  $account_type = 'GUEST';
		
		}
		
		$result['data']['LOGIN_TOKEN'] = date('Y-m-d H:i:s');
		$result['data']['ACCOUNT_TYPE'] 	  = $account_type; // 確認使用者是否有合格帳號
		$result['data']['USER']       = serialize($AccoountObj); 
		$result['data']['SEARCH_CONF']= $search_profile;
		$result['data']['ACCESS_KEY'] = $LoginKey;                // 標示本次登入的鑰匙
		$result['data']['LOGIN_TIME'] = date('Y-m-d H:i:s');      // 標示本次登入的時間
		$result['action'] = true;
		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;  
	}
	
	//-- User Account Space Allocat  // 使用者空間發派
	// [input] : UserGroup   :  user type / MEMBER / GUEST
	// [input] : UserAccount :  email or account
	public function Account_System_Space_Allocat($UserType , $UserAccount='' ){
	    
	  try{
		
		switch($UserType){
		  case "MEMBER":
            $user_profile_folder = _SYSTEM_USER_PATH;
            break;
			
          case "GUEST":			
			 $user_profile_folder = _SYSTEM_GUEST_PATH;
			break; 
		}
		
		// 帳號資料夾
		if(!is_dir($user_profile_folder.$UserAccount)){
		  mkdir($user_profile_folder.$UserAccount, 0777, true);	  
	    }
		
		// 搜尋設定
		if(!is_file($user_profile_folder.$UserAccount.'/search.conf')){
		  $conf = json_decode(_USER_PROFILE_DEFAULE,true);
		  $conf['USER_IP']  = System_Helper::get_client_ip();;
		  $conf['USER_Date']= date('Y-m-d H:i:s');  
		  file_put_contents( $user_profile_folder.$UserAccount.'/search.conf' , json_encode($conf,JSON_UNESCAPED_UNICODE) );
		}
		
        if(!is_file($user_profile_folder.$UserAccount.'/task_work.tmp')){
		  $work = array('user'=>array(),'task'=>array('wno'=>'','bid'=>'','mno'=>'','time'=>'','field'=>array(),'chk'=>'','save'=>''));
		  file_put_contents( $user_profile_folder.$UserAccount.'/task_work.tmp' , json_encode($work) );
		}
		
		return true;
		
	  }catch(Exception $e){
		 false;    
	  }
	}
	
	//-- Get Client Page Post List
	// [input] : NULL 
	public function Access_Get_Client_Post_List(){
	  $result_key = parent::Initial_Result('post');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		// 查詢資料庫
		$DB_OBJ = $this->DBLink->prepare(SQL_Client::INDEX_GET_POST_LIST());
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
        $result['action'] = true;		
		
		$result['data']   = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);;		
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;   
	}
	
	
	//-- Client Sent Reset Password Mail
	// [input] : RegistData   :  User Account Reset Password Code => urlencode(base64encode(json_pass()))  = array('regist_email'=>'' , 'verification'=>'');
	public function Account_Sent_RePassword_Mail(  $RegistData='' , $VerificationCode ){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  $regist_data = json_decode(base64_decode(rawurldecode($RegistData)),true);
	  
	  try{
	    
		// 檢查登入參數
		if( !isset($regist_data['regist_mail'])  ||  !isset($regist_data['verification'])  ){
	      throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
	    }
		
		// 檢查登入驗證碼
		if( $regist_data['verification'] != $VerificationCode ){
	      throw new Exception('_REGISTER_ERROR_CAPTCHA_TEST_FAIL');
	    }
		
		// 檢查電子郵件
		if( !filter_var($regist_data['regist_mail'], FILTER_VALIDATE_EMAIL) ){
	      throw new Exception('_REGISTER_ERROR_EMAIL_TEST_FAIL');
	    }
		
		// 檢查信箱是否存在
		$account_data = NULL;
		$DB_CHK	= $this->DBLink->prepare(SQL_Account::CHECK_ACCOUNT_REGIST_EMAIL());
		$DB_CHK->bindParam(':user_mail'   , $regist_data['regist_mail'] , PDO::PARAM_STR);	
		if( !$DB_CHK->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		if(!$account_data = $DB_CHK->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_REGISTER_ERROR_EMAIL_NOT_EXIST');
		}
		
		// 檢查註冊資料
		if( $regist_data['regist_name'] != $account_data['user_name'] ){
		  throw new Exception('_REGISTER_ERROR_EMAIL_NOT_EXIST');
		}
		
		// 檢查帳號狀態
		if( $account_data['user_status'] < 3 ){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_STATUS_REVIEWING');
		}
		
		$process_repassword = self::Process_Account_Password_Setting($account_data['uno'],$account_data['user_id'],$account_data['user_mail']);
		
		if(!$process_repassword['action']){
		  throw new Exception($process_repassword['message']);
		}
		
		// 變更使用者狀態
		$DB_UPD= $this->DBLink->prepare(SQL_Account::SET_ACCOUNT_STATUS_REPASSWORD());
		$DB_UPD->bindParam(':uno'   ,$account_data['uno'],PDO::PARAM_INT);	
		$DB_UPD->execute();
		$result['data']['account'] = $account_data['user_id'];
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;  
	}
	
	//-- Client Regist ReSet Password Process
	// [input] : AccountNo:  :  client_account.cano
    // [input] : UserAccount:  :  client_account.account 	
	// [input] : UserMail    :  client_account.mail 
	protected function Process_Account_Password_Setting( $AccountNo=0 , $UserAccount='' , $UserMail='' ){
	  $func_result = array('action'=>false,'message'=>'','data'=>'');
	  try{
		// 建立註冊序號
	    $reg_code = substr(md5($UserMail.'#'.time()),(rand(0,3)*8),8).'.'.System_Helper::generator_password(2);  
		
		// 註冊帳號開通連結
		$DB_REG= $this->DBLink->prepare(SQL_Account::INSERT_ACCOUNT_REGIST_CODE());
		$DB_REG->bindParam(':uno',$AccountNo,PDO::PARAM_INT);	
		$DB_REG->bindParam(':reg_code',$reg_code ,PDO::PARAM_STR);	
		$DB_REG->bindValue(':reg_state','_REGIST');
		$DB_REG->bindValue(':effect_time',date('Y-m-d H:i:s',strtotime("+1 day")));  
		
        if(! $DB_REG->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
        
		// 設定信件內容
        $to_sent = $UserMail;
        $user_reglink = _SYSTEM_SERVER_ADDRESS."index.php?act=Landing/start/".$reg_code;
		
        $mail_content  = "<div>"._SYSTEM_HTML_TITLE." - 帳號密碼重新設定</div>";
		$mail_content .= "<div>帳號：".$UserAccount."</div>";
		$mail_content .= "<div>啟動：<a href='".$user_reglink."' target=_blank>".$user_reglink."</a></div>";
		$mail_content .= "<div>（請利用以上連結開通帳號並設定密碼，連結將於24小時後失效）</div>";
		$mail_content .= "<div>有任何問題請洽"._SYSTEM_CONTACT_ORGAN."：</div>";
		$mail_content .= "<div>EMAIL：<a href='mailto:"._SYSTEM_CONTACT_MAIL."'>"._SYSTEM_CONTACT_MAIL."</a></div>";
		$mail_content .= "<div>TEL  ："._SYSTEM_CONTACT_TEL."</div>";
		$mail_content .= "<div> </div>";
		$mail_content .= "<div>本信由系統發出，請勿直接回覆</div>";
			  
        $mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
        $mail->IsSMTP(); // telling the class to use SMTP 
		$mail->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
		$mail->SMTPDebug  = 0;
		try {  
		
		  $mail->SMTPAuth   = _SYSTEM_MAIL_SMTPAuth;   // enable SMTP authentication      
		  if(_SYSTEM_MAIL_SSL_ACTIVE){
		    $mail->SMTPSecure = _SYSTEM_MAIL_SECURE;   // sets the prefix to the servie
		  }
		  $mail->Port       = _SYSTEM_MAIL_PORT;     // set the SMTP port for the GMAIL server
		  $mail->Host       = _SYSTEM_MAIL_HOST; 	   // SMTP server
		  
		  $mail->CharSet 	= "utf-8";
		  $mail->Username   = _SYSTEM_MAIL_ACCOUNT_USER;  // MAIL username
		  $mail->Password   = _SYSTEM_MAIL_ACCOUNT_PASS;  // MAIL password
		  //$mail->AddAddress('','');
          
		  $mail_to_sent = (preg_match('/.*?\s*<(.*?)>$/',$UserMail,$mail_paser)) ? trim($mail_paser[1]) : trim($UserMail);
		  if(!filter_var($mail_to_sent, FILTER_VALIDATE_EMAIL)){
		    throw new Exception('_LOGIN_INFO_REGISTER_MAIL_FALSE');
		  }
		  
		  $mail->AddAddress($mail_to_sent,'');
		  $mail->SetFrom(_SYSTEM_MAIL_ACCOUNT_USER.'@'._SYSTEM_MAIL_ACCOUNT_HOST, _SYSTEM_MAIL_FROM_NAME);
		  $mail->AddReplyTo(_SYSTEM_MAIL_ACCOUNT_USER.'@'._SYSTEM_MAIL_ACCOUNT_HOST, _SYSTEM_MAIL_FROM_NAME); // 回信位址
		  $mail->Subject = "["._SYSTEM_MAIL_FROM_NAME."]-密碼設定信";
		  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
		  $mail->MsgHTML($mail_content);
		  
		  //$mail->AddCC(); 
		  //$mail->AddAttachment('images/phpmailer.gif');      // attachment
	      
		  if(!$mail->Send()) {
			throw new Exception($mail->ErrorInfo);  
		  } 
		  
		  $func_result['action'] = true;
		  
		} catch (phpmailerException $e) {
		    $func_result['message'] = $e->errorMessage();  //Pretty error messages from PHPMailer
		} catch (Exception $e) {
		    $func_result['message'] = $e->errorMessage();  //echo $e->getMessage(); //Boring error messages from anything else!
		}
        
	  } catch (Exception $e) {
        $func_result['message'] = $e->getMessage();
      }
	  
	  return $func_result; 
	}
	
	
	
	/*[ Post Function Set ]*/ 
	
	//-- Get Client Post List
	// [input] : DataNo = system_post.pno
	public function Get_Client_Post_Target($DataNo){
	  
      $result_key = parent::Initial_Result('');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{    
		
		//:確認申請序號
		if(!preg_match('/^\d+$/',$DataNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');	
		}
		$post = array();
		
		$DB_OBJ = $this->DBLink->prepare(SQL_Client::GET_CLIENT_POST_TARGET());
		if(!$DB_OBJ->execute(array('pno'=>intval($DataNo))) || !$post=$DB_OBJ->fetch(PDO::FETCH_ASSOC) ){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');	
		}
		
		$DB_OBJ = $this->DBLink->prepare(SQL_Client::CLIENT_POST_HITS());
		$DB_OBJ->execute(array('pno'=>intval($DataNo)));
		
		$post['post_content'] = base64_encode(htmlspecialchars_decode($post['post_content']));
		$post['post_hits'] += 1;
		
		$result['action'] 	= true;		
		$result['data'] 	= $post;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      } 
	  return $result;
	}
	
	
  
  }
?>