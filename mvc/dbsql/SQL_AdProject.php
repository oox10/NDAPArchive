<?php
  
  /*
  *   [RCDH10 Admin Module] - Project Admin Sql Library 
  *   System Apply SQL SET
  *
  *   2017-09-28 ed.  
  */
  
  /* [ System Execute function Set ] */ 	
  
  class SQL_AdProject{
	
   
	/***-- Admin Project SQL --***/
	
	//-- Admin Project : Get Apply List
	public static function SELECT_ALL_PROJECT(){
	  $SQL_String = "SELECT system_project.*,user_info.user_name FROM system_project LEFT JOIN  user_info ON uid = _user WHERE _keep=1  ORDER BY  spno  DESC;";
	  return $SQL_String;
	}
	
	//-- Admin Project : Get Project Data
	public static function GET_PROJECT_DATA(){
	  $SQL_String = "SELECT system_project.*,user_name FROM system_project LEFT JOIN user_info ON _user=uid WHERE spno =:spno AND _keep=1;";
	  return $SQL_String;
	}
	
	
	//-- Admin Built : update task element data
	public static function UPDATE_PROJECT_META( $MmodifyFields = array(1) ){
	  $condition = array();
	  foreach($MmodifyFields as $field){
	    $condition[] = $field.'=:'.$field;
	  }
	  $SQL_String = "UPDATE system_project SET ".join(',',$condition)." WHERE spno=:spno AND _keep=1;";
	  return $SQL_String;
	}
	
	
	//-- Admin Project : insert new project
	public static function INSERT_PROJECT_META(){
	  $SQL_String = "INSERT INTO system_project VALUES(NULL,:pjname,:pjinfo,'[]','','',0,'_initial',NULL,:user,1);";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Project : Get My Apply  List
	public static function SELECT_CHECKER_APPLY(){
	  $SQL_String = "SELECT uano,apply_code,apply_date,apply_reason,apply_count,_state,handler,user_name,process_count FROM ( SELECT user_apply.* FROM  user_apply LEFT JOIN meta_apply ON user_apply.apply_code=meta_apply.apply_code WHERE checker=:checker GROUP BY apply_code) AS user_apply LEFT JOIN user_info ON user_apply.uid=user_info.uid  WHERE _keep=1  ORDER BY _state ASC , apply_date DESC;";
	  return $SQL_String;
	}
	
	
	
	
	
	//-- Admin Project : Get Apply Data List
	public static function GET_APPLY_DATA_LIST(){
	  $SQL_String = "SELECT meta_apply.*,page_count,_checked FROM meta_apply LEFT JOIN metadata ON CONCAT(in_store_no,store_no) = metadata.applyindex WHERE apply_code=:apply_code ORDER BY checker ASC,mano ASC;";
	  return $SQL_String;
	}
	//-- Admin Project : Get Apply Data List
	public static function GET_APPLY_CHECKER_DATA_LIST(){
	  $SQL_String = "SELECT meta_apply.*,page_count,_checked FROM meta_apply LEFT JOIN metadata ON CONCAT(in_store_no,store_no) = metadata.applyindex WHERE apply_code=:apply_code AND checker=:checker ORDER BY checker ASC,mano ASC;";
	  return $SQL_String;
	}
	
	
	//-- Admin Project : Get Apply Data History
	public static function GET_APPLY_DATA_HISTORY(){
	  $SQL_String = "SELECT * FROM meta_apply WHERE in_store_no=:in_store_no AND store_no=:store_no AND mano !=:mano ORDER BY check_time DESC,mano ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Project : Get Apply Check List
	public static function GET_APPLY_CHECK_LIST(){
	   $SQL_String = "SELECT mano,apply_code,in_store_no,store_no,check_state,check_time,check_info,check_range,reference,check_note,copy_mode,collection,identifier,search_json,page_count,initial_json,detail_json,_digited FROM meta_apply LEFT JOIN metadata ON CONCAT(in_store_no,store_no)=applyindex WHERE apply_code=:apply_code;";
	  return $SQL_String;
	}
	
	//-- Admin Project : Get Apply Check List
	public static function GET_CHECKER_APPLY_CHECK_LIST(){
	   $SQL_String = "SELECT mano,apply_code,in_store_no,store_no,check_state,check_time,check_info,check_range,reference,check_note,copy_mode,collection,identifier,search_json,page_count,initial_json,detail_json,_digited FROM meta_apply LEFT JOIN metadata ON CONCAT(in_store_no,store_no)=applyindex WHERE apply_code=:apply_code AND checker=:checker;";
	  return $SQL_String;
	}
	
	
	//-- Admin Project : UPDATE Apply Data
	public static function UPDATE_APPLY_RECORD(){
	  $SQL_String = "UPDATE meta_apply SET check_time='".date('Y-m-d H:i:s')."',check_info=:check_info,check_state='_CHECKED',check_range=:check_range,reference=:reference,check_note=:check_note WHERE apply_code=:apply_code AND in_store_no=:in_store_no AND store_no=:store_no;";
	  return $SQL_String;
	}
	
	//-- Admin Project : UPDATE Apply Meta To checked  // 更新准駁狀態
	public static function UPDATE_APPLY_METADATA_CHECKED(){
	  $SQL_String = "UPDATE metadata SET _checked=1 WHERE applyindex=:applyindex;";
	  return $SQL_String;
	}
	
	//-- Admin Project : UPDATE Apply Meta View  // 更新檢視影像狀態 (原狀態為不開放者則變更為限閱)
	public static function UPDATE_APPLY_METADATA_VIEW(){
	  $SQL_String = "UPDATE metadata SET _view=:view WHERE applyindex=:applyindex;";
	  return $SQL_String;
	}
	
	
	
	
	//-- Admin Project : UPDATE APPLY STATE FINISH // 申請單准駁完成後更新
	public static function COMPLETE_APPLY_CHECKED(){
	  $SQL_String = "UPDATE user_apply SET _state=IF(apply_count=process_count,4, _state ) WHERE apply_code=:apply_code;";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Project : UPDATE User APPLY
	public static function UPDATE_APPLY_HANDLER(){
	  $SQL_String = "UPDATE user_apply SET handler=:handler WHERE uano=:uano;";
	  return $SQL_String;
	}
	
	//-- Admin Project : Get Apply User
	public static function GET_APPLY_USER(){
	  $SQL_String = "SELECT uano,apply_code,apply_count,apply_date,user_name,user_mail FROM user_apply LEFT JOIN user_info ON user_apply.uid=user_info.uid WHERE uano=:uano;";
	  return $SQL_String;
	}
	
	
	//-- Admin Project : UPDATE APPLY meta checker
	public static function UPDATE_APPLY_CHECKER(){
	  $SQL_String = "UPDATE meta_apply SET checker=:checker WHERE mano=:mano;";
	  return $SQL_String;
	}
	
	//-- Admin Project : Modify Apply Data
	public static function UPDATE_APPLY_DATA( $MmodifyFields = array(1) ){
	  $condition = array();
	  foreach($MmodifyFields as $field){
	    $condition[] = $field.'=:'.$field;
	  }
	  $SQL_String = "UPDATE user_apply SET ".join(',',$condition)." WHERE uano=:uano;";
	  return $SQL_String;
	}
	
	
	//-- Admin Project : Get Apply Admin Account  R03
	public static function SELECT_APPLY_ADUSER(){
	  $SQL_String = "SELECT user_info.uid,user_name FROM (SELECT uid,gid,COLUMN_GET(rset,'R03' as INTEGER) AS pri FROM permission_matrix WHERE gid IN ('aha','adm')) AS PRIORITY LEFT JOIN user_info ON user_info.uid=PRIORITY.uid WHERE pri>=1;";
	  return $SQL_String;
	}
	

	//-- Admin Project : Get Apply check refer info
	public static function GET_CHECK_REFER(){
	  $SQL_String = "SELECT check_range AS reason FROM meta_apply WHERE check_state='_CHECKED' AND check_range != '' GROUP BY check_range;";
	  return $SQL_String;
	}
	
	
	//-- Admin Project : Regist Apply mail alert job
	public static function REGIST_ALERT_MAIL_JOB(){
	  $SQL_String = "INSERT INTO system_mailer VALUES(NULL,:mail_type,:mail_from,:mail_to,:mail_title,:mail_content,0,:creator,'".date('c')."',:editor,'0000-00-00 00:00:00',:mail_date,'0000-00-00 00:00:00','',:active_logs,1);";
	  return $SQL_String;
	}
	
	
	
	/*== [ Record Mode ] ==*/
	
	//-- Admin Project : Get Apply Record For statistic 
	public static function GET_APPLY_RECORD(){
	  $SQL_String = "SELECT apply_record.* , page_count ,user_name,user_mail FROM (SELECT uano,apply_date,user_apply.apply_code,uid,CONCAT(in_store_no,store_no) AS applykey,in_store_no,store_no,check_state,check_time,check_info,checker FROM user_apply LEFT JOIN meta_apply ON user_apply.apply_code = meta_apply.apply_code WHERE ( apply_date BETWEEN :date_s AND :date_e) AND _keep=1) AS apply_record LEFT JOIN metadata ON applykey = applyIndex LEFT JOIN user_info ON apply_record.uid=user_info.uid WHERE applykey IS NOT NULL AND checker>0 ORDER BY checker ASC , apply_date ASC,check_time ASC;";
	  return $SQL_String;
	}
	
	/*== [ Reserve Mode ] ==*/
	
	//-- Admin Project : Get ALL Apply Reserve From NOW 
	public static function ALL_APPLY_RESERVE(){
	  $SQL_String = "SELECT reserve.*,user_name,user_mail FROM (SELECT user_apply.apply_code,in_store_no,check_range,uid,apply_reason,apply_date FROM meta_apply LEFT JOIN user_apply ON user_apply.apply_code=meta_apply.apply_code WHERE (check_state = '_BOOKING' OR check_info='原件閱覽') AND _keep=1 ) AS reserve LEFT JOIN user_info ON reserve.uid=user_info.uid WHERE 1 ORDER BY check_range DESC ,uid ASC,in_store_no ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Project : Get Apply Reserve From Date 
	public static function GET_APPLY_RESERVE(){
	  $SQL_String = "SELECT reserve.*,user_name,user_mail FROM (SELECT user_apply.apply_code,in_store_no,check_range,uid,apply_reason,apply_date FROM meta_apply LEFT JOIN user_apply ON user_apply.apply_code=meta_apply.apply_code WHERE (check_state = '_BOOKING' OR check_info='原件閱覽') AND check_range=:date AND _keep=1 ORDER BY check_range ASC) AS reserve LEFT JOIN user_info ON reserve.uid=user_info.uid WHERE 1 ORDER BY uid ASC,in_store_no ASC;";
	  return $SQL_String;
	}
	
	
  }
  
  
?>