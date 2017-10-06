<?php

  class Staff_Model extends Admin_Model{
    
	
	/***--  Function Set --***/
    public function __construct(){
	  parent::__construct();
	  parent::initial_user($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['USER']);  
	}
	
	/*[ Staff Function Set ]*/ 
    
	protected $SearchString;  // 查詢條件
	
	protected $ResultCount;   // 查詢結果數量
	protected $PageNow;       // 當前頁數 
	protected $LengthEachPage;// 每頁筆數
	
	protected $Metadata;
	
	
	//-- Admin Staff Page Initial // 取得模組管理頁面相關相關參變數
	// [input] : NULL;
	public function ADStaff_Get_Module_Config(){
	  $result_key = parent::Initial_Result('config');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		// 取得模組通用變數 
		$DB_OBJ = $this->DBLink->prepare(SQL_AdStaff::GET_STAFF_CONFIG());
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		$roles =  $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);
		$result['data']['setting']   = $roles;		
	  
		
		// 取得角色列表
		$DB_OBJ = $this->DBLink->prepare(SQL_AdStaff::GET_ROLES_LIST());
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		$roles =  $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);
		$result['data']['roles']   = $roles;		
	    
	    
	    $result['action'] = true;		
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	
	}
	
	//-- Admin Staff Page Initial 
	// [input] : RecordType   = (string) all,mbr,tpa,search / mbr;
	// [input] : PageLimit    = (string) 1-10 / ;
	// [input] : SearchString = (string) base64_decode ;  [condition = [] , orderby? = [field: , mode: 0 1 2 ]]
	public function ADStaff_Get_Staff_List($RecordType,$PageLimit,$SearchString){
	  
	  $result_key = parent::Initial_Result('records');
	  $result  = &$this->ModelResult[$result_key];
	  
	  // 搜尋欄位表
	  $search_fields= array('user_id','user_name','user_mail','user_organ','user_staff');
			  
	  try{
	    
		// 取得群組名稱對應表
		$DB_OBJ = $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdStaff::SELECT_USER_GROUP_MAP()));
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		$user_group = array();
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  $user_group[$tmp['puid']] = $tmp['ug_name'];   	
		}
		
		// 處理頁數參數
		$limit = explode('-',$PageLimit);
        $limit_start   = 0;
        $limit_length  = 0;
		
        $limit_start = (isset($limit[0]) && intval($limit[0]) ) ? intval($limit[0])-1 : 0;
		$limit_length= (isset($limit[1]) && intval($limit[1]) && intval($limit[1]) > intval($limit[0]) ) ?  (intval($limit[1])-$limit_start) : 10;
		
		$record_count = 0;
		
		// 解析頁面建構參數
		$search_config = json_decode(base64_decode(str_replace('*','/',rawurldecode($SearchString))),true);
		
        // 依據類型篩選資料
		$search_condition = [];
		  
		  
		switch($RecordType){
		  case 'mbr': $search_condition[] = "gid='mbr'"; break;			  
		  case 'tpa': $search_condition[] = "gid='tpa'"; break;	  
		  case 'all':default:  $search_condition[] = 1 ; break;
		}
	      
		// 處理搜尋條件
		if($search_config && isset($search_config['condition']) && trim($search_config['condition']) ){
			$terms  = explode('&',$search_config['condition']);
			$querys = array();
			foreach($terms as $t){
			  $condition = array();
			  foreach($search_fields as $f){
				$condition[] =  $f." LIKE '%".$t."%'";
			  }	
			  $querys[] = "(".join(" OR ",$condition).")"; 
			}
			$search_condition[] = join(' AND ',$querys);  
		}	  
		  
        // 處理排序條件
        if($search_config && isset($search_config['orderby'])){
		  $order_by_field  = isset($search_config['orderby']['field']) ?  $search_config['orderby']['field'] : 'uno';
		  $order_by_method = isset($search_config['orderby']['mode'])&&intval($search_config['orderby']['mode']) ?  (intval($search_config['orderby']['mode'])==1 ? 'DESC' : 'ASC') : 'DESC';
		  $order_by_config = ' ORDER BY '.$order_by_field.' '.$order_by_method;
		}else{
		  $order_by_config = ' ORDER BY uno DESC'; 
		}
		  
		$DB_COUNT = $this->DBLink->prepare( parent::SQL_Permission_Filter(SQL_AdStaff::SELECT_COUNT_STAFF($search_condition)) );
		if(!$DB_COUNT->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
	   	}
		  
		$record_count = $DB_COUNT->fetchColumn();	
		  
		$DB_OBJ = $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdStaff::SELECT_ALL_STAFF($search_condition,$order_by_config)));
		$DB_OBJ->bindValue(':page_start',$limit_start,PDO::PARAM_INT);
		$DB_OBJ->bindValue(':page_length',$limit_length,PDO::PARAM_INT);
		  
		if(!$DB_OBJ->execute()){
	      throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
	   	}
		$staff_list = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);	
		
       
		foreach($staff_list as &$staff){
		  
		  // 設定帳戶群組
		  $staff['user_group'] = isset($user_group[intval($staff['uno'])]) ? $user_group[intval($staff['uno'])] : 'NULL';
		  
		  // 檢查帳戶狀態
		  $status_info = ''; // 儲存狀態訊息 
          if($staff['user_status']<5){
            switch( (string)$staff['user_status'] ){
		      case '0': $status_info = '_LOGIN_INFO_ACCOUNT_STATUS_DISABLED';   break;
		      case '1': $status_info = '_LOGIN_INFO_ACCOUNT_STATUS_REVIEWING';   break;
			  case '2': $status_info = '_LOGIN_INFO_ACCOUNT_STATUS_REVIEWED';   break;
			  case '3': $status_info = '_LOGIN_INFO_ACCOUNT_STATUS_UNACTIVE';   break;
			  case '4': $status_info = '_LOGIN_INFO_ACCOUNT_STATUS_REPASSWD';   break;
			  default: $status_info = '_LOGIN_INFO_ACCOUNT_STATUS_UNKNOW';   break;
		    }
		  }
		  
		  // 檢查帳戶存取日期
		  $account_date_now   = strtotime('now');
		  $account_date_start = strtotime($staff['date_open']);
		  $account_date_limit = strtotime($staff['date_access']);
		  if( $account_date_now < $account_date_start){
		    $status_info = '_LOGIN_INFO_ACCOUNT_DATE_STARTYET';
		  }
		  if( $account_date_now > $account_date_limit){
		    $status_info = '_LOGIN_INFO_ACCOUNT_DATE_EXPIRED';
		  }
		  if( $staff['user_status']==5 && $status_info){
			$staff['user_status'] = 0;  
		  }
		  $staff['account_start'] = $status_info ? false : true ;
		  $staff['account_info']  = $staff['account_start'] ? '' : self::Message_Translate($status_info) ;
		}
		
		
		// 共用參數
		$this->ResultCount    = $record_count;
		$this->PageNow        = round($limit_start/$limit_length)+1;
		$this->LengthEachPage = $limit_length;
		
		$result['action'] = true;		
		$result['data']['type']   = $RecordType;
		$result['data']['list']   = $staff_list;		
	    $result['data']['count']  = $record_count;		
	    $result['data']['config'] = $search_config;		
	    $result['data']['limit']  = array('start'=>$limit_start,'length'=>$limit_length,'range'=> '1-'.$limit_length);	
       	$result['data']['nums']   = $limit_length;
        $result['data']['page']   = $PageLimit;		
	  
	  } catch (Exception $e) {
		$result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	//-- Admin Meta Page Data OList 
	// [input] : $PagerMaxNum => int // 頁面按鈕最大數量
	public function ADStaff_Get_Page_List( $PagerMaxNum=1 ){
	  
	  $result_key = parent::Initial_Result('page');
	  $result  = &$this->ModelResult[$result_key];
      
	  try{
        
		$page_show_max = intval($PagerMaxNum) > 0 ? intval($PagerMaxNum) : 1;
		
	    $pages = array();
		
		$pages['all'] = array(1=>'');
		
		
		// 必要參數，從ADMeta_Get_Meta_List而來
		$this->ResultCount;   // 查詢結果數量
	    $this->PageNow;   
	    $this->LengthEachPage;
		
		$total_page = ( $this->ResultCount / $this->LengthEachPage ) + ($this->ResultCount%$this->LengthEachPage ? 1 :0 );
		
		// 建構分頁籤
		for($i=1;$i<=$total_page;$i++){
		  $pages['all'][$i] = (($i-1)*$this->LengthEachPage+1).'-'.($i*$this->LengthEachPage);
		}
		
		$pages['top']   = reset($pages['all']);
		$pages['end']   = end($pages['all']);
		$pages['prev']  = ($this->PageNow-1 > 0 ) ? $pages['all'][$this->PageNow-1] : $pages['all'][$this->PageNow];
		$pages['next']  = ($this->PageNow+1 < $total_page ) ? $pages['all'][$this->PageNow+1] : $pages['all'][$this->PageNow];
		$pages['now']   = $this->PageNow;  
		
		$check = ($page_show_max-1)/2;
		
	    if($total_page < $page_show_max){
		  $pages['list'] = $pages['all'];  	
		}else {  
          if( ($this->PageNow - $check) <= 1 ){    // 抓最前面 X 個
            $start = 0;
		  }else if( ($this->PageNow + $check) > $total_page ){  // 抓最後面 X 個
            $start = $total_page-(2*$check)-1;    
		  }else{
            $start = $this->PageNow - $check -1;
		  }
	      $pages['list'] = array_slice($pages['all'],$start,$page_show_max,TRUE);
		}
		
		$result['data']   = $pages;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	  
		
	}
	
	//-- Admin Staff Get Staff Data 
	// [input] : uno  :  \d+;
	
	public function ADStaff_Get_Staff_Data($StaffNo=0){
	  $result_key = parent::Initial_Result('user');
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查使用者序號
	    if(!preg_match('/^\d+$/',$StaffNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 搜尋權限設定表
		$DB_GET	= $this->DBLink->prepare( parent::SQL_Permission_Filter(SQL_AdStaff::ADMIN_STAFF_CHECK_STAFF_ACCESS_PERMISSION()) );
		$DB_GET->bindParam(':uid'   , $StaffNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$user = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');
		}
		
		// 取得使用者資料
		$staff_data = NULL;
		$DB_GET	= $this->DBLink->prepare( SQL_AdStaff::ADMIN_STAFF_GET_STAFF_ADMIN_DATA() );
		$DB_GET->bindParam(':uno'   , $user['uid'] , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$staff_data = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
	    
		// 取得使用者 role
		$staff_data['roles'] = array();
		$DB_GET	= $this->DBLink->prepare( SQL_AdStaff::ADMIN_STAFF_GET_STAFF_GROUP_ROLES() );
		$DB_GET->bindParam(':uid'   , $user['uid'] , PDO::PARAM_INT);
        $DB_GET->bindParam(':gid'   , $user['gid'] , PDO::PARAM_INT);			
		if( !$DB_GET->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		$staff_data['roles']  = json_decode($DB_GET->fetchColumn(),true);
		
		// 取得使用者 groups
		$staff_data['groups'] = array();
		$DB_GET	= $this->DBLink->prepare( SQL_AdStaff::ADMIN_STAFF_GET_STAFF_GROUP_OTHER() );
		$DB_GET->bindParam(':uid'   , $user['uid'] , PDO::PARAM_INT);
        if( !$DB_GET->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		$staff_data['groups'] = $DB_GET->fetchAll(PDO::FETCH_ASSOC);
		
		// final
		$result['action'] = true;
		$result['data'] = $staff_data;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Staff Save Staff Data 
	// [input] : StaffNo    :  \d+  = DB.user_info.uid;
	// [input] : StaffModify  :   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	// [input] : UserRoles  :   urlencode(base64encode(json_pass()))  = array( R01, R02, .. ) ;  // 角色選項
	
	public function ADStaff_Save_Staff_Data( $StaffNo=0 , $StaffModify='' , $UserRoles=''){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  $staff_modify = json_decode(base64_decode(rawurldecode($StaffModify)),true);
	  $staff_roles  = json_decode(base64_decode(rawurldecode($UserRoles)),true);
	  
	  try{  
		
		// 檢查使用者序號
	    if(!preg_match('/^\d+$/',$StaffNo)  || !is_array($staff_modify)  ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 搜尋權限設定表
		$user = array();
		$DB_GET	= $this->DBLink->prepare( parent::SQL_Permission_Filter(SQL_AdStaff::ADMIN_STAFF_CHECK_STAFF_ACCESS_PERMISSION()) );
		$DB_GET->bindParam(':uid'   , $StaffNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$user = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');
		}
		
		// 取得使用者資料
		$staff_data = NULL;
		$DB_GET	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_GET_STAFF_ADMIN_DATA());
		$DB_GET->bindParam(':uno'   , $user['uid'] , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$staff_data = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		foreach($staff_modify as $mf => $mv){
		  if(!isset($staff_data[$mf])){
			throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		  }
		}
		
		if($staff_modify && count($staff_modify)){
		    // 執行更新
			$DB_SAVE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_UPDATE_STAFF_DATA(array_keys($staff_modify)));
			$DB_SAVE->bindValue(':uno' , $user['uid']);
			foreach($staff_modify as $mf => $mv){
			  $DB_SAVE->bindValue(':'.$mf , $mv);
			}
			if( !$DB_SAVE->execute()){
			  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
			}
		}
		
		
		if($staff_roles && count($staff_roles)){
		  $DB_ROLE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_UPDATE_STAFF_ROLES(array_keys($staff_roles)));
		  $DB_ROLE->bindValue(':uid' ,  $user['uid']);
		  $DB_ROLE->bindValue(':gid' ,  $user['gid']);
		  $DB_ROLE->bindValue(':master' ,  1);
		  $DB_ROLE->bindValue(':user' , $this->USER->UserID);
		  foreach($staff_roles as $rid => $rset){
			$DB_ROLE->bindValue(':'.$rid , intval($rset) ,PDO::PARAM_INT);
		  }
		  $DB_ROLE->execute();
		}
		
		
		// final 
		$result['data'] = $user['uid'];
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	
	//-- Admin Staff Create Staff Data 
	// [input] : StaffModify  :   urlencode(base64encode(json_pass()))  = array( 'field_name'=>new value , ......   ) ;  // 修改之欄位 - 變動
	// [input] : UserRoles    :   urlencode(base64encode(json_pass()))  = array( R01, R02, .. ) ;  // 角色選項
	
	public function ADStaff_Newa_Staff_Data($StaffCreate='' , $UserRoles=''){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  $staff_newa   = json_decode(base64_decode(rawurldecode($StaffCreate)),true);
	  $staff_roles  = json_decode(base64_decode(rawurldecode($UserRoles)),true);  
	  
	  try{  
		
		// 檢查參數
		if(  !isset($staff_newa['user_id']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		if(  !isset($staff_newa['user_mail']) ){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		$date_open   = isset($staff_newa['date_open']) && strtotime($staff_newa['date_open'])   ? date('Y-m-d H:i:s',strtotime($staff_newa['date_open'])) : date('Y-m-d H:i:s');
		$date_access = isset($staff_newa['date_open']) && strtotime($staff_newa['date_access']) ? date('Y-m-d H:i:s',strtotime($staff_newa['date_access'])) : date('Y-m-d').' 23:59:59';
		$user_iprange= isset($staff_newa['ip_range']) && filter_var($staff_newa['ip_range'],FILTER_VALIDATE_IP) ? $staff_newa['ip_range'] : '0.0.0.0';
		
		$DB_NEW	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_INSERT_USER_LOGIN());
		$DB_NEW->bindParam(':user_id'    ,$staff_newa['user_id']);
		$DB_NEW->bindParam(':date_open'  ,$date_open);
		$DB_NEW->bindParam(':ip_range'   ,$user_iprange);
		$DB_NEW->bindParam(':date_access',$date_access);
	    if( !$DB_NEW->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_UPDATE_FAIL');
		}
		
		$new_user_no  = $this->DBLink->lastInsertId('user_login');
		
		$DB_INFO	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_INSERT_USER_INFO());
		$DB_INFO->bindParam(':uid'    	  , $new_user_no);
		$DB_INFO->bindValue(':user_name'  , isset($staff_newa['user_name']) ? $staff_newa['user_name'] : '' );
		$DB_INFO->bindValue(':user_idno'  , isset($staff_newa['user_idno']) ? $staff_newa['user_idno'] : '');
		$DB_INFO->bindValue(':user_staff' , isset($staff_newa['user_staff']) ? $staff_newa['user_staff'] : '');
		$DB_INFO->bindValue(':user_organ' , isset($staff_newa['user_organ']) ? $staff_newa['user_organ'] : '');
		$DB_INFO->bindValue(':user_tel'   , isset($staff_newa['user_tel']) ? $staff_newa['user_tel'] : '');
		$DB_INFO->bindValue(':user_mail'   , isset($staff_newa['user_mail']) ? $staff_newa['user_mail'] : '');
		$DB_INFO->bindValue(':user_address',isset($staff_newa['user_address']) ? $staff_newa['user_address'] : '');
		$DB_INFO->bindValue(':user_age'  , isset($staff_newa['user_age']) ? $staff_newa['user_age'] : '');
		$DB_INFO->bindValue(':user_education'  , isset($staff_newa['user_education']) ? $staff_newa['user_education'] : '');
		$DB_INFO->bindValue(':user_major'  , isset($staff_newa['user_major']) ? $staff_newa['user_major'] : '');
		$DB_INFO->bindValue(':user_info'  , isset($staff_newa['user_info']) ? $staff_newa['user_info'] : '');
		$DB_INFO->bindValue(':user_pri'   , isset($staff_newa['user_pri']) ? $staff_newa['user_pri'] : 1);
		if( !$DB_INFO->execute() ){
		  throw new Exception('_SYSTEM_ERROR_DB_UPDATE_FAIL');
		}
		
		// STEP.4: insert table:digital_ftpuser   // 加入註冊群組 :uno,:gno,:rno,:creater
		
		// 設定角色參數
		if(count($staff_roles)){
		  $roleset = array();
		  foreach($staff_roles as $rcode => $rset){
			$roleset[]="'".$rcode."',".$rset;	
		  }	
		  $role_conf = "COLUMN_CREATE(".join(',',$roleset).")";
		}else{
		  $role_set = [];
		  $DB_OBJ = $this->DBLink->prepare(SQL_AdStaff::GET_ROLES_LIST());
		  $DB_OBJ->execute();
		  while($role = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC)){
		    $role_set[] = "'".$role['rno']."',".$role['_initial'];	
		  }
		  $role_conf = "COLUMN_CREATE(".join(',',$role_set).")";
		}
		
		$DB_UGP = $this->DBLink->prepare(SQL_AdStaff::INSERT_GROUP_MEMBER($role_conf));
		$DB_UGP->bindParam(':uno',$new_user_no,PDO::PARAM_INT);
		$DB_UGP->bindValue(':gno',$this->USER->PermissionNow['group_code']);
		$DB_UGP->bindValue(':master',1);
		$DB_UGP->bindvalue(':creater','system');
		$DB_UGP->execute();
		
		// STEP.5 建立會員資料夾
		if(!is_dir(_SYSTEM_USER_PATH.$staff_newa['user_id'])){
		  mkdir(_SYSTEM_USER_PATH.$staff_newa['user_id'],0777,true);	  
		}
		
		$DB_FTP = $this->DBLink->prepare(SQL_Account::INSERT_NEW_FTP_ACCOUNT());
		$DB_FTP->bindValue(':uno',$new_user_no,PDO::PARAM_INT);
		$DB_FTP->bindValue(':user_account',$staff_newa['user_id']);
		$DB_FTP->bindvalue(':homedir',_SYSTEM_UPLD_PATH.$staff_newa['user_id']);
		$DB_FTP->execute();
		
		// final 
		$result['data'] = $new_user_no;
		$result['action'] = true;
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
	
	//-- Admin Staff Account Accept & Sent Repass Mail 
	// [input] : uno  :  \d+;
	
	public function ADStaff_Staff_Account_Accept_Mail($StaffNo=0){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		// 檢查使用者序號
	    if(!preg_match('/^\d+$/',$StaffNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// 搜尋權限設定表
		$user = array();
		$DB_GET	= $this->DBLink->prepare( parent::SQL_Permission_Filter(SQL_AdStaff::ADMIN_STAFF_CHECK_STAFF_ACCESS_PERMISSION()) );
		$DB_GET->bindParam(':uid'   , $StaffNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$user = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');
		}
		
		// 取得使用者資料
		$staff_data = NULL;
		$DB_GET	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_GET_STAFF_ADMIN_DATA());
		$DB_GET->bindParam(':uno'   , $user['uid'] , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$staff_data = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
		}
		
		// 建立註冊序號
	    $reg_code = substr(md5($staff_data['user_id'].'#'.time()),(rand(0,3)*8),8).'.'.System_Helper::generator_password(2);  
		
		// 註冊帳號開通連結
		$DB_REG= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_LOGIN_REGISTER_REPASSWORD_CODE());
		$DB_REG->bindParam(':uno', $StaffNo ,PDO::PARAM_INT);	
		$DB_REG->bindParam(':reg_code',$reg_code ,PDO::PARAM_STR);	
		$DB_REG->bindValue(':reg_state','_REGIST');
		$DB_REG->bindValue(':effect_time',date('Y-m-d H:i:s',strtotime("+7 day")));  
		
		
        if(! $DB_REG->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
        
		// 設定信件內容
        $to_sent = $staff_data['user_mail'];
        $user_reglink = _SYSTEM_MANAGE_ADDRESS."index.php?act=Account/start/".$reg_code;
		  
        $mail_content  = "<div>"._SYSTEM_HTML_TITLE." 帳號申請</div>";
        $mail_content .= "<div>帳號：".$staff_data['user_id']."</div>";
        $mail_content .= "<div>啟動：<a href='".$user_reglink."' target=_blank>".$user_reglink."</a></div>";
        $mail_content .= "<div>（請利用以上連結開通帳號並設定密碼，連結將於一週後(".date('Y-m-d H:i:s',strtotime("+7 day")).")失效）</div>";
        $mail_content .= "<div>有任何問題請洽：</div>";
        $mail_content .= "<div>EMAIL：<a href='mailto:"._SYSTEM_MAIL_CONTACT."'>"._SYSTEM_MAIL_CONTACT."</a></div>";
        $mail_content .= "<div> </div>";
        $mail_content .= "<div>本信由系統發出，請勿直接回覆</div>";
		      
        $mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
        $mail->IsSMTP(); // telling the class to use SMTP 
		
		try {  
		  
          $mail->SMTPAuth   = _SYSTEM_MAIL_SMTPAuth;   // enable SMTP authentication      
		  if(_SYSTEM_MAIL_SSL_ACTIVE){
		    $mail->SMTPSecure = _SYSTEM_MAIL_SECURE;   // sets the prefix to the servie
		  }
		  $mail->Port       = _SYSTEM_MAIL_PORT;     // set the SMTP port for the GMAIL server
				
		  $mail->Host       = _SYSTEM_MAIL_HOST; 	   // SMTP server
		  $mail->SMTPDebug  = false;                       // enables SMTP debug information (for testing)
		  $mail->CharSet 	= "utf-8";
		  $mail->Username   = _SYSTEM_MAIL_ACCOUNT_USER;  // MAIL username
		  $mail->Password   = _SYSTEM_MAIL_ACCOUNT_PASS;  // MAIL password
		  //$mail->AddAddress('','');
          
		  $mail_to_sent = (preg_match('/.*?\s*<(.*?)>$/',$to_sent,$mail_paser)) ? trim($mail_paser[1]) : trim($to_sent);
		  if(!filter_var($mail_to_sent, FILTER_VALIDATE_EMAIL)){
		    throw new Exception('_LOGIN_INFO_REGISTER_MAIL_FALSE');
		  }
		  
		  $mail->AddAddress($mail_to_sent,'');
		  $mail->SetFrom(_SYSTEM_MAIL_ACCOUNT_USER.'@'._SYSTEM_MAIL_ACCOUNT_HOST, _SYSTEM_MAIL_FROM_NAME);
		  $mail->AddReplyTo(_SYSTEM_MAIL_ACCOUNT_USER.'@'._SYSTEM_MAIL_ACCOUNT_HOST, _SYSTEM_MAIL_FROM_NAME); // 回信位址
		  $mail->Subject = "["._SYSTEM_MAIL_FROM_NAME."]-帳號開通信件";
		  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
		  $mail->MsgHTML($mail_content);
		  
		  //$mail->AddCC(); 
		  //$mail->AddAttachment('images/phpmailer.gif');      // attachment
	      
		  if(!$mail->Send()) {
			throw new Exception($mail->ErrorInfo);  
		  } 
		  
          // 變更user_info pri => 0
		  $DB_SAVE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_UPDATE_STAFF_DATA(array('user_status')));
		  $DB_SAVE->bindParam(':uno'      , $StaffNo , PDO::PARAM_INT);
		  $DB_SAVE->bindValue(':user_status' , 3 );
		  if( !$DB_SAVE->execute()){
		    throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		  } 
		
		  // final 
		  $result['data']   = $staff_data['user_id'];
		  $result['action'] = true;
		
		} catch (phpmailerException $e) {
		    $result['message'][] = $e->errorMessage();  //Pretty error messages from PHPMailer
		} catch (Exception $e) {
		    $result['message'][] = $e->errorMessage();  //echo $e->getMessage(); //Boring error messages from anything else!
		}
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;  
	}
	
	
	//-- Admin Staff Delete Staff Data 
	// [input] : uno  :  \d+;
	
	public function ADStaff_Del_Staff_Data($StaffNo=0){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{  
		
		// 檢查使用者序號
	    if(!preg_match('/^\d+$/',$StaffNo)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		
		// 搜尋權限設定表
		$user = array();
		$DB_GET	= $this->DBLink->prepare( parent::SQL_Permission_Filter(SQL_AdStaff::ADMIN_STAFF_CHECK_STAFF_ACCESS_PERMISSION()) );
		$DB_GET->bindParam(':uid'   , $StaffNo , PDO::PARAM_INT);	
		if( !$DB_GET->execute() || !$user = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_SYSTEM_ERROR_PERMISSION_DENIAL');
		}
		
		
		// 變更user_info pri => 0
		$DB_SAVE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_UPDATE_STAFF_DATA(array('user_pri')));
		$DB_SAVE->bindParam(':uno'      , $user['uid'] , PDO::PARAM_INT);
		$DB_SAVE->bindValue(':user_pri' , 0 );
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// 移除user_login record
		$DB_SAVE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_DELETE_STAFF_LOGIN());
		$DB_SAVE->bindParam(':uno'      , $user['uid'] );
		if( !$DB_SAVE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		}
		
		// final 
		$result['data']   = $user['uid'];
		$result['action'] = true;
		sleep(1);
    	
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;  
	}
	
    
	
	//-- Admin Staff Account Accept & Sent Repass Mail 
	// [input] : UserNoString  :  \d+;\d+;\d+.. 
	
	public function ADStaff_Account_Batch_Accept($UserNoString=''){
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  try{  
		
		
		// 處理使用者序號
		$user_no_set = explode(';',$UserNoString);
		
		// 檢查資料
	    if(!count($user_no_set)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
	    
		// accept user 
		$accept_account_count = 0;
		
		foreach($user_no_set as $user_no){
			
		  if(!intval($user_no)){
			continue;  
		  }	
			
		  // 取得使用者資料
		  $staff_data = NULL;
		  $DB_GET	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_GET_STAFF_ADMIN_DATA());
		  $DB_GET->bindParam(':uno'   , $user_no , PDO::PARAM_INT);	
		  if( !$DB_GET->execute() || !$staff_data = $DB_GET->fetch(PDO::FETCH_ASSOC)){
		    continue;
		  }  	
		  
		  // 檢查是否為新註冊
          if($staff_data['user_status']!=1){
			continue;
		  }
          		  
		  // 建立註冊序號
	      $reg_code = substr(md5($staff_data['user_id'].'#'.time()),(rand(0,3)*8),8).'.'.System_Helper::generator_password(2);  
		
		  // 註冊帳號開通連結
		  $DB_REG= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_LOGIN_REGISTER_REPASSWORD_CODE());
		  $DB_REG->bindParam(':uno', $user_no ,PDO::PARAM_INT);	
		  $DB_REG->bindParam(':reg_code',$reg_code ,PDO::PARAM_STR);	
		  $DB_REG->bindValue(':reg_state','_REGIST');
		  $DB_REG->bindValue(':effect_time',date('Y-m-d H:i:s',strtotime("+7 day")));  
		
          if(! $DB_REG->execute()){
		    throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		  }		

		  $user_reglink = _SYSTEM_MANAGE_ADDRESS."index.php?act=Account/start/".$reg_code;
		  
          // 設定信件內容
          $mail_to_sent = $staff_data['user_mail'];
          
		  $mail_title    = "["._SYSTEM_HTML_TITLE."]-帳號開通信件";
		   
          $mail_content  = "<div>"._SYSTEM_HTML_TITLE." 帳號申請</div>";
          $mail_content .= "<div>帳號：".$staff_data['user_id']."</div>";
          $mail_content .= "<div>啟動：<a href='".$user_reglink."' target=_blank>".$user_reglink."</a></div>";
          $mail_content .= "<div>（請利用以上連結開通帳號並設定密碼，連結將於一週後(".date('Y-m-d H:i:s',strtotime("+7 day")).")失效）</div>";
          $mail_content .= "<div>有任何問題請洽：</div>";
          $mail_content .= "<div>EMAIL：<a href='mailto:"._SYSTEM_MAIL_CONTACT."'>"._SYSTEM_MAIL_CONTACT."</a></div>";
          $mail_content .= "<div> </div>";
          $mail_content .= "<div>本信由系統發出，請勿直接回覆</div>";
		  
		  
          $mail_logs = [date('Y-m-d H:i:s')=>'Regist Mail From '._SYSTEM_NAME_SHORT.'.' ];
		  
		  $DB_MAILJOB	= $this->DBLink->prepare(SQL_AdMailer::REGIST_MAIL_JOB());
          $DB_MAILJOB->bindValue(':mail_type','帳號啟動');
          $DB_MAILJOB->bindValue(':mail_from',_SYSTEM_MAIL_FROM_NAME);
          $DB_MAILJOB->bindValue(':mail_to',$mail_to_sent);
          $DB_MAILJOB->bindValue(':mail_title',$mail_title);
          $DB_MAILJOB->bindValue(':mail_content',htmlspecialchars($mail_content,ENT_QUOTES,'UTF-8'));
          $DB_MAILJOB->bindValue(':creator',$this->USER->UserID);
          $DB_MAILJOB->bindValue(':editor',$this->USER->UserID);
          $DB_MAILJOB->bindValue(':mail_date',date('Y-m-d'));
          $DB_MAILJOB->bindValue(':active_logs',json_encode($mail_logs));
          if(!$DB_MAILJOB->execute()){
		    throw new Exception('_APPLY_MAIL_REGIST_FAIL');	
          }   
		  //$mail->AddCC(); 
		  //$mail->AddAttachment('images/phpmailer.gif');      // attachment
	      $accept_account_count++;
		
		  // 變更user_info pri => 0
		  $DB_SAVE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_UPDATE_STAFF_DATA(array('user_status')));
		  $DB_SAVE->bindParam(':uno'      , $user_no , PDO::PARAM_INT);
		  $DB_SAVE->bindValue(':user_status' , 3 );
		  if( !$DB_SAVE->execute()){
		    throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');
		  }
		}
		
		if(!$accept_account_count){
		  throw new Exception('_STAFF_ERROR_BATCH_ACCEPT_EMPTY');  	
		}
		
		// final 
		$result['data']   = '已通過勾選之帳號：'.$accept_account_count."組 \n將於今日發送註冊通過通知信.";
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  
	  return $result;  
	}
	
	
	
	
	
	
	//-- Admin Staff Record Save To Excel 
	// [input] : null
	
	public function ADStaff_Staff_Account_Record_Save(){
	  $SaveLocation = _SYSTEM_FILE_PATH;
	  
	  try{  
	    // 查詢資料庫
	    $DB_OBJ = $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_SELECT_OUTPUT_STAFF());
	    if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
	    }
	  
	  
	    // 取得帳戶資料
	    $staff_list = array();
	    $staff_list = $DB_OBJ->fetchAll(PDO::FETCH_NUM);		
	    
        $meta_list = isset( $staff_list ) && is_array($staff_list) ?  $staff_list : array();
	  
	    if( !count($meta_list) || !is_file(_SYSTEM_ROOT_PATH.'mvc/templates/tmp_account_records.xlsx')){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');  
	    }
			
		$output_filename = 'TWSPDB_account_'.date('Ymd_His').'.xlsx';
			
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objPHPExcel = $objReader->load(_SYSTEM_ROOT_PATH.'mvc/templates/tmp_account_records.xlsx');
		$objPHPExcel->setActiveSheetIndex(0);
			
		$col = 0 ;
		$row = 2 ;
		foreach( $meta_list as $meta){
		  for($i=$col ; $i<count($meta); $i++){
			$objPHPExcel->getActiveSheet()->getCellByColumnAndRow($i, $row)->setValueExplicit($meta[$i], PHPExcel_Cell_DataType::TYPE_STRING);
		  }  
		  $row++;
		}
		
		if(is_file($SaveLocation.$output_filename)){
		  unlink($SaveLocation.$output_filename);
		}
		
		// final
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($SaveLocation.$output_filename); 
		$objPHPExcel->disconnectWorksheets();
		unset($objPHPExcel);
		$this->ModelResult['action']   = true;
		$this->ModelResult['data']   = $output_filename;
		
	  } catch (Exception $e) {
        $this->ModelResult['message'][] = $e->getMessage();
      }
	  
	  return $this->ModelResult;	 
	}
	
	
	
	/*[ Group Member Setting Function Set ]*/ 
	
	//-- Admin Staff Group
	// [input] : NULL;
	public function ADStaff_Get_Group_List(){
	  
	  $result_key = parent::Initial_Result('groups');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		// 查詢資料庫
		$DB_OBJ = $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdStaff::SELECT_GROUP_LIST()));
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		$groups = $DB_OBJ->fetchAll(PDO::FETCH_ASSOC);
		$result['action'] = true;		
		$result['data']   = $groups;		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	//-- Admin Staff Group Member Include other Groups 
	// [input] : NULL;
	public function ADStaff_Get_Group_Memners(){
	  
	  $result_key = parent::Initial_Result('members');
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
	    
		// 查詢資料庫
		$DB_OBJ = $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdStaff::SELECT_GROUP_MEMBER()));
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$members = array();
		while($tmp = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  if(!isset($members[$tmp['gid']])) $members[$tmp['gid']] = array();
		  $role_set = json_decode($tmp['role_json'],true) ? json_decode($tmp['role_json'],true) : array();
          $tmp['roles'] = array_keys(array_filter($role_set));  
		  $members[$tmp['gid']][] = $tmp;
		}
		$result['action'] = true;		
		$result['data']   = $members;		
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	//-- Admin Staff Group Member Add
	// [input] : $User  : [string] user_id or user_mail;
	// [input] : $GroupCode  : [string] group code;
	// [input] : $Role  : [string]  urlencode(base64encode(json_pass()))  = array( R01=>1, R02=>0, .. ) ;  // 
	
	public function ADStaff_Add_Group_Memner($User='',$GroupCode='',$Roles='',$Filter=''){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
		// 檢查參數
	    if(!strlen($User)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		$role_set    = json_decode(base64_decode(rawurldecode($Roles)),true);  
		$filter_conf = rawurldecode($Filter);
		  
		// 查詢使用者
		$DB_OBJ = $this->DBLink->prepare(SQL_AdStaff::CHECK_MEMBER_ACCOUNT());
		$DB_OBJ->bindValue(':uno'	,$User);
		$DB_OBJ->bindValue(':user_id'	,$User);
		$DB_OBJ->bindValue(':user_name'	,$User);
		$DB_OBJ->bindValue(':user_mail'	,$User);
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$user = array();
	    if(!$user = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNFOUND');  
	    }
		
		$user['roles'] = array_keys(array_filter($role_set));
		$user['master']= 0;
		$user['filter']= $filter_conf;
		
		// 執行新增
		$DB_ROLE	= $this->DBLink->prepare(SQL_AdStaff::ADMIN_STAFF_UPDATE_STAFF_ROLES(array_keys($role_set)));
		$DB_ROLE->bindValue(':uid' ,  $user['uno']);
		$DB_ROLE->bindValue(':gid' ,  $GroupCode);
		$DB_ROLE->bindValue(':master' ,  0);
		$DB_ROLE->bindValue(':filter' ,  $filter_conf);
		$DB_ROLE->bindValue(':user' , $this->USER->UserID);
		foreach($role_set as $rid => $rset){
		  $DB_ROLE->bindValue(':'.$rid , intval($rset) ,PDO::PARAM_INT);
		}
		
		if(!$DB_ROLE->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$result['action'] = true;		
		$result['data']   = $user;		
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	//-- Admin Staff Group Member Del
	// [input] : $User  : [string] user_id or user_mail;
	// [input] : $GroupCode  : [string] group code;
	public function ADStaff_Del_Group_Memner($User='',$GroupCode=''){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
		// 檢查參數
	    if(!strlen($User)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 查詢使用者
		$DB_OBJ = $this->DBLink->prepare(SQL_AdStaff::CHECK_MEMBER_ACCOUNT());
		$DB_OBJ->bindValue(':uno'	,$User);
		$DB_OBJ->bindValue(':user_id'	,$User);
		$DB_OBJ->bindValue(':user_name'	,$User);
		$DB_OBJ->bindValue(':user_mail'	,$User);
		if(!$DB_OBJ->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$user = array();
	    if(!$user = $DB_OBJ->fetch(PDO::FETCH_ASSOC)){
		  throw new Exception('_LOGIN_INFO_ACCOUNT_UNFOUND');  
	    }
			
		// 執行移除
		$DB_DEL	= $this->DBLink->prepare(parent::SQL_Permission_Filter(SQL_AdStaff::DELETE_MEMBER_FROM_GROUP()));
		$DB_DEL->bindValue(':uid' ,  $user['uno']);
		$DB_DEL->bindValue(':gid' ,  $GroupCode);
		if(!$DB_DEL->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		// 確定執行數量 
		if(!$DB_DEL->rowCount()){
		  throw new Exception('_STAFF_ERROR_MASTER_MEMBER_CANT_REMOVE');  	
		}
		
		$result['data']   = $User;
		$result['action'] = true;
		
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
	//-- Admin Staff Group Meta Save
	// [input] : $GroupData  : [string] encode json;
	
	public function ADStaff_Group_Meta_Save($GroupData){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
		$group_meta  = json_decode(base64_decode(str_replace('*','/',rawurldecode($GroupData))),true);  
		
		// 檢查參數
	    if(!isset($group_meta['name']) || !isset($group_meta['code'])){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 執行更新
		$DB_UPD= $this->DBLink->prepare(SQL_AdStaff::RENEW_GROUP_META());
		$DB_UPD->bindValue(':ug_code' ,  $group_meta['code']);
		$DB_UPD->bindValue(':ug_name' ,  $group_meta['name']);
		$DB_UPD->bindValue(':ug_info' ,  $group_meta['info']);
		$DB_UPD->bindValue(':creater' , $this->USER->UserID);
		
		if(!$DB_UPD->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$result['action'] = true;		
		$result['data']   = $group_meta['code'];		
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	//-- Admin Staff Group Meta Delete
	// [input] : $GroupCode : [string] ;
	public function ADStaff_Group_Meta_Delete($GroupCode){
	  
	  $result_key = parent::Initial_Result();
	  $result  = &$this->ModelResult[$result_key];
	  
	  try{
		
		// 檢查參數
	    if(!strlen($GroupCode)){
		  throw new Exception('_SYSTEM_ERROR_PARAMETER_FAILS');
		}
		
		// 執行更新
		$DB_DEL= $this->DBLink->prepare(SQL_AdStaff::DELETE_GROUP_META());
		$DB_DEL->bindValue(':ug_code' ,  $GroupCode);
		if(!$DB_DEL->execute()){
		  throw new Exception('_SYSTEM_ERROR_DB_ACCESS_FAIL');  
		}
		
		$result['action'] = true;		
		$result['data']   = $GroupCode;		
	  
	  } catch (Exception $e) {
        $result['message'][] = $e->getMessage();
      }
	  return $result;
	}
	
	
	
  }
?>