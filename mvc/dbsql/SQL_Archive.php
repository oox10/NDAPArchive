<?php

  class SQL_Archive{
    
	
	
	/**************************
	   AHAS - Archive initial SQL
	**************************/
	
	//-- Get Class Table
	public static function GET_ARCHIVE_CLASS(){
	  $SQL_String = "SELECT * FROM meta_zong WHERE zcount_total>0 AND _view=1 ORDER BY ztype DESC, zorder ASC , zid ASC;";
	  return $SQL_String;
	} 
	
	
	/**************************
	   使用者首頁資訊 SQL 組  !!與 Landing Model 關聯
	**************************/
	
	//-- Client Post :  get post user list 
	public static function INDEX_GET_POST_LIST(){
	  $SQL_String = "SELECT pno,post_type,post_from,post_level,post_time_start,post_title,post_content FROM system_post WHERE post_to='申請系統' AND post_display=1 AND post_keep=1 AND ( (NOW() BETWEEN post_time_start AND post_time_end ) OR post_level=4 ) ORDER BY post_level DESC,post_time_start DESC,pno DESC;";
	  return $SQL_String;
	}  
	
	//-- Client Post:  get post user list 
	public static function GET_CLIENT_POST_LIST(){
	  $SQL_String = "SELECT pno,post_type,post_from,post_level,post_time_start,post_title,post_content FROM system_post WHERE post_to IN('檢索系統','所有系統') AND post_display=1 AND post_keep=1 AND ( (NOW() BETWEEN post_time_start AND post_time_end ) OR post_level=4 ) ORDER BY post_level DESC,post_time_start DESC,pno DESC;";
	  return $SQL_String;
	}
	
	
	//-- Client Post:  get post user select 
	public static function GET_CLIENT_POST_TARGET(){
	  $SQL_String = "SELECT pno,post_type,post_from,post_level,post_time_start,post_title,post_content,post_hits FROM system_post WHERE post_to IN('檢索系統','所有系統') AND post_display=1 AND post_keep=1 AND ( (NOW() BETWEEN post_time_start AND post_time_end ) OR post_level=4 ) AND pno=:pno;";
	  return $SQL_String;
	}
	
	//-- Client Post:  update post hits 
	public static function CLIENT_POST_HITS(){
	  $SQL_String = "UPDATE system_post SET post_hits=(post_hits+1) WHERE pno=:pno;";
	  return $SQL_String;
	}
	
	
	/********************
	    查詢熱門資料
	*********************/
	public static function SEARCH_LOGS_TOP_QUERY($num=50){
	  $SQL_String = "SELECT Query_Term_Set,count(*) AS count FROM search_temp WHERE Action_From IN('search','level','history') GROUP BY Query_Term_Set ORDER BY count DESC LIMIT 0,".$num.";";
	  return $SQL_String;
	}
	
	
	 /********************
	    查詢熱門資料
	*********************/
	public static function SEARCH_LOGS_TOP_ACCESS($num=50){
	  $SQL_String = "SELECT metadata.StoreNo,file_name,file_type,file_page,count(*) AS count FROM result_history LEFT JOIN metadata ON result_history.StoreNo=metadata.StoreNo WHERE 1 GROUP BY metadata.StoreNo ORDER BY count DESC LIMIT 0,".$num.";";
	  return $SQL_String;
	}
	
	
	/********************
	     管理系統
	*********************/

	
	/***---- 取得系統關聯資料  ----***/
	
	public static function AD_GET_DATA_GROUP($Type='#all'){
	  
	  switch($Type){
	    case '#all'   : $SQL_String = "SELECT * FROM user_group WHERE 1;"; break;
        case '#admin' : $SQL_String = "SELECT * FROM user_group WHERE ug_pri>0 ORDER BY ug_pri DESC;"; break;
	  }
	  return $SQL_String;
	}
	
	public static function AD_GET_DATA_ROLE(){
	  $SQL_String = "SELECT * FROM user_role WHERE 1;"; 
	  return $SQL_String;
	}
	
	public static function AD_GET_DATA_GROUP_USERS($Group = array('000')){
	  $SQL_String = "SELECT ugp_no,uid,user_name,user_staff FROM user_gumap LEFT JOIN user_info ON usr_no = uid WHERE ugp_no IN('".join("','",$Group)."');"; 
	  return $SQL_String;
	}
	
	
	/********************
         檢索系統
	********************/
	
	/*****----  Archive Search Function SET  ----*****/
	
	// 取得使用者目前影像讀取數量
	public static function GET_USER_ACCESS_RECORD(){
	  // 第一二頁不算
	  $SQL_String = "SELECT StoreNo,Visit_Page,User_IP,result_visit.User_Name,Visit_Time,count(*) as count FROM result_visit LEFT JOIN result_history ON Visit_Code = Concat(result_history.RslNum,result_history.Code) WHERE result_visit.User_Name =:user_id AND DATE(Visit_Time) > :start_date AND DATE(Visit_Time)<= :end_date AND Visit_Page > 1 GROUP BY StoreNo,Visit_Page ORDER BY Visit_Time DESC;";
	  return $SQL_String;
	}
	
	
	// 取得 Search Parent Note
	public static function GET_SEARCH_TEMP_RECORD(){
	  $SQL_String = "SELECT * FROM search_temp WHERE Search_Action_Num=:ACCNUM AND User_Access_ID=:UAID;";
	  return $SQL_String; 
	}
	
	// 存入search_history 資料表
	public static function INSERT_SEARCH_HISTORY_TABLE(){
	  $SQL_String = "INSERT INTO search_history VALUES(NULL,:query_hash,:user_id,0,:query_string,1,'',NULL) ON DUPLICATE KEY UPDATE Acc_Num=:acc_num,Final_Page=:page,Query_String=:query_string;";
	  return $SQL_String; 
	}
	
	
	// 搜尋 file_level 資料表
	public static function GET_FILE_LEVEL_TARGET(){
	  $SQL_String = "SELECT name FROM search_level WHERE organ=:organ AND lvcode=:LV;";
	  return $SQL_String; 
	}
	
	// 搜尋 access_table 資料表
	public static function GET_DATA_SEARCH_ACCESS_RULE(){
	  $SQL_String = "SELECT AccessRange FROM access_table WHERE AccessRules='DataSearch' AND Permit='denial' AND ((Conditions='ID' AND Value IN(:UID , 'ALL')) OR (Conditions='IP' AND Value IN(:UIP ,'ALL')) OR (Conditions='Meta'));";
	  return $SQL_String; 
	}
	
	// 註冊 search_temp 資料表
	public static function INSERT_SEARCH_TEMP(){
	  $SQL_String = "INSERT INTO search_temp VALUES(NULL,:UAID,:SQL_Mysql,'',:Query_Set,'','".date('Y-m-d H:i:s')."',:ACTION,:ACCNUM);";
	  return $SQL_String; 
	}
	
	// 更新 search_temp 資料表
	public static function UPDATE_SEARCH_TEMP(){
	  $SQL_String = "UPDATE search_temp SET SQL_2=:query WHERE Search_Action_num=:accnum;";
	  return $SQL_String; 
	}
	
	
	//檢索 SQL 設定
	public static function SEARCH_SQL_HEADER_MYSQL($UserId){
	  $SQL_String = "SELECT * FROM metadata LEFT JOIN user_select ON metadata.system_id = user_select.SysId and user_select.User_ID='".$UserId."' ";
	  return $SQL_String; 
	}
	
	public static function SEARCH_SQL_HEADER_SPHINX($UserId){
	  $SQL_String = "SELECT * FROM metadatafts LEFT JOIN metadata ON metadatafts.id=metadata.SphinxId LEFT JOIN user_select ON (user_select.SysId=metadata.SystemId AND user_select.User_ID='".$UserId."') ";
	  return $SQL_String; 
	}
	
	
	//-- 取得搜尋結果metadata
	public static function SEARCH_SQL_GET_RESULT_METAS($idlist = array(0)){
	  $SQL_String = "SELECT * FROM metadata WHERE system_id IN (".join(',',$idlist).");";
	  return $SQL_String; 
	}
	
	
	//-- 查詢准駁結果
	public static function SEARCH_SQL_GET_APPLY_CHECKED($StoreNo){
	  $SQL_String = "SELECT * FROM meta_apply WHERE in_store_no=:in_store_no AND store_no ='".$StoreNo."' AND check_state = '_CHECKED' AND check_info!='已准駁' ORDER BY check_time DESC;";
	  return $SQL_String; 
	}
	
	//-- 查詢預約日期
	public static function SEARCH_SQL_GET_APPLY_BOOKING(){
	  $SQL_String = "SELECT meta_apply.* FROM meta_apply LEFT JOIN user_apply ON user_apply.apply_code=meta_apply.apply_code WHERE in_store_no=:in_store_no AND uid=:uid AND check_info='原件閱覽' ORDER BY check_range ASC;";
	  return $SQL_String; 
	}
	
	
	public static function GET_USER_SEARCH_HISTORY_LIST(){
	  //會傳入是否要限制筆數
	  $SQL_String = "SELECT * FROM search_history WHERE User_ID=:user ORDER BY Update_Time DESC LIMIT 0,10;"; 
	  return $SQL_String; 
	}
	
	public static function GET_USER_HISTORY(){
	  //
	  $SQL_String = "SELECT * FROM search_history WHERE HisNum=:HSN AND User_Name=:UID;";
	  return $SQL_String; 
	}
	
	// 
	public static function GET_OBJECT_METADATA(){
	  $SQL_String = "SELECT * FROM metadata WHERE StoreNo=:StoreNo;";
	  return $SQL_String; 
	}
	
	
	// 
	public static function GET_OBJECT_ACCESS_RULE($GroupList="'999'"){
	  $SQL_String = "SELECT * FROM rule_table WHERE (type='object_limit' OR ( type='image_access' AND target='group' AND rvalue IN(".$GroupList."))) AND keep=1;";
	  return $SQL_String; 
	}
	
	
	
	public static function GET_DATA_BOOK_CATALOG(){
	  $SQL_String = "SELECT * FROM digital_catalog WHERE bookid=:bookid AND keep=1;";
	  return $SQL_String; 
	}
	
	
	
	public static function GET_USER_DATA_DISPLAY(){
	  $SQL_String = "SELECT Notes,Tags FROM user_select WHERE SelIndex = :SELINDEX;";
	  return $SQL_String; 
	}
	
	public static function GET_DISPLAY_StoreNo($AccType = 'Public'){
	  switch($AccType){
	    case 'Private':
		  $SQL_String = "SELECT InStoreNo,StoreNo,Acc_Permission FROM result_history WHERE CONCAT(RslNum,Code)=:ACCCODE AND User_Name=:UID;";
	      break;
		
		case 'Public':
		default:
		  $SQL_String = "SELECT InStoreNo,StoreNo,Acc_Permission FROM result_history WHERE CONCAT(RslNum,Code)=:ACCCODE;";
	      break;
	  }
	  return $SQL_String; 
	}
    
	
	
	
	//-- Get User Apply Meta List
	public static function GET_USER_APPLY_INDEX( ){
	  $SQL_String = "SELECT in_store_no,store_no FROM user_apply LEFT JOIN meta_apply ON user_apply.apply_code=meta_apply.apply_code WHERE user_apply.apply_code=:apply_code AND uid=:uid AND _keep=1 ORDER BY mano ASC;";
	  return $SQL_String;
	}
	
	
	
	
	//-- Get Export Meta List  // 使用者匯出
	public static function GET_USER_EXPORT_META($metalist){
	  $SQL_String = "SELECT search_json FROM metadata WHERE applyindex IN('".join("','",$metalist)."') AND _open=1 AND _keep=1 LIMIT 0,100;";
	  return $SQL_String;
	}
	
	
	
	
	
	
    /*****----  Archive AP SET  ----*****/
	
	
	// 查詢 search_level 表取得相關 Level Data
	public static function SELECT_META_LEVEL(){
	  $SQL_String = "SELECT * FROM search_level WHERE zong=:zong AND type=:type ORDER BY lvno ASC;";
	  return $SQL_String;
	}

	// 查詢 access_table 表取得類別顯示限制
	public static function SELECT_ACCESS_TABLE_CLASSACCESS(){
	  $SQL_String = "SELECT * FROM access_table WHERE AccessRules='ClassAccess' AND Action=1;";
	  return $SQL_String;
	}
 	
	public static function TAG_USER_RESULT($method = 'add'){
	  switch($method){
	    case 'untag':
		  $SQL_String = "UPDATE user_select SET Tags=REPLACE(Tags, :TAG , '' ),Update_Time='".date('Y-m-d H:i:s')."' WHERE User_ID=:UID AND SelIndex=:SELINDEX;";
		  break;
		case 'deltag':
	      $SQL_String = "UPDATE user_select SET Tags=REPLACE(Tags, :TAG , '' ),Update_Time='".date('Y-m-d H:i:s')."' WHERE User_ID=:UID;";
	      break;
	    default:
	      $SQL_String = "INSERT INTO user_select VALUES(:SELINDEX , :UID , :SYSID ,'' , :TAG ,'".date('Y-m-d H:i:s')."' ) ON DUPLICATE KEY UPDATE Tags = CONCAT( REPLACE(Tags , :TAG ,'') , :TAG ) ,Update_Time='".date('Y-m-d H:i:s')."';";
	      break;  
	  }  
	  return $SQL_String;
	}
	
	public static function COUNT_USER_TAG(){
	  $SQL_String = "SELECT COUNT(*) FROM user_select WHERE User_ID=:UID AND Tags LIKE :TAG;";
	  return $SQL_String;
	}
	
	public static function GET_REPORT_META(){
	  $SQL_String = "SELECT * FROM metadata WHERE StoreNo = :STNO;";
	  return $SQL_String;
	}
	
	public static function REGISTER_REPORT_DATA(){
	  $SQL_String = "INSERT INTO report_table VALUES(NULL,'".date('Y-m-d H:i:s')."',:RPTYPE,:STNO,:UID,'@System',:FIELD,:REPORT,'_REPORT','','','');";
	  return $SQL_String;
	}
	
	public static function MARK_USER_IMAGE(){
	  $SQL_String = "INSERT INTO user_mark VALUES(:MARKINDEX,:UID,:STNO,:PNUM,:NOTE,'".date('Y-m-d H:i:s')."',1) ON DUPLICATE KEY UPDATE Mark_Note = :NOTE,Mark_Time='".date('Y-m-d H:i:s')."';";
	  return $SQL_String;
	}
	
	
	public static function GET_USER_INFO_FROM_REGCODE(){
	  $SQL_String = "SELECT User_ID,user_name,user_idno,user_staff,user_organ,user_tel,user_mail,user_address,user_research FROM user_regist LEFT JOIN user ON uno=user_regist.uid LEFT JOIN user_info ON uno=user_info.uid WHERE reg_code like :reg_code;";
	  return $SQL_String;
	}
	
	
	// 查詢 user 相關之系統訊息列表 
	public static function AP_GET_USER_INFORMATION_LIST(){
	  $SQL_String = "SELECT * FROM system_info WHERE (Info_To='all' OR Info_To=:user_id) AND ((Pub_DateTime_Start<=:today AND Pub_DateTime_End >=:today AND Pub_Level>0  AND Pub_Level<4 ) OR ( Pub_Level=4 )) AND Info_Keep=1 ORDER BY Info_Type ASC,Pub_Level DESC,Pub_DateTime_Start DESC;";
	  return $SQL_String;
	}
	
	// 讀取訊息內容
	public static function AP_GET_INFORMATION_CONTENT(){
	  $SQL_String = "SELECT Info_Id,Info_Content FROM system_info WHERE (Info_To='all' OR Info_To=:user_id) AND ((Pub_DateTime_Start<=:today AND Pub_DateTime_End >=:today AND Pub_Level>0  AND Pub_Level<4 ) OR ( Pub_Level=4 )) AND Info_Keep=1 AND Info_Id=:info_id;";
	  return $SQL_String;
	}
	
	// 更新點擊數量
	
	public static function AP_UPDATE_INFORMATION_HIT_COUNTER(){
	  $SQL_String = "UPDATE system_info SET Info_Hits = (Info_Hits+1) WHERE Info_Id=:info_id;";
	  return $SQL_String;
	}
	
	
	//-- Open Data Download meta excel
	public static function AP_DOWNLOAD_BOOK_META(){
	  $SQL_String = "SELECT * FROM digital_meta WHERE bookid=:bookid AND meta_keep=1 ORDER BY page_ser_start ASC,page_ser_end ASC , dmno ASC;";
	  return $SQL_String;
	}
	
	//-- Open Data Download meta excel
	public static function AP_DOWNLOAD_BOOK_IMAGE_LIST(){
	  $SQL_String = "SELECT * FROM digital_scanner WHERE bookid=:bookid AND keep=1 ORDER BY orl_name ASC;";
	  return $SQL_String;
	}
	
	
	//-- application logs
	public static function AP_APPLICATION_ACTION_LOGS(){
	  $SQL_String = "INSERT INTO logs_searchapp VALUES(NULL,:organ,:type,:target,:refer,:link,:user_ip,NULL);";
	  return $SQL_String;
	}
	
	
	
	//-- member application 
	public static function AP_GET_MEMBER_META(){
	  $SQL_String = "SELECT * FROM metadata WHERE zong='議員傳記' AND identifier=:member_name AND _keep=1;";
	  return $SQL_String;
	}
	
	
	
	/*****----  DATA APPLY SET  ----*****/
	
	
	//-- get user apply history
	public static function APPLY_GET_NOW_APPLY(){
	  $SQL_String = "SELECT * FROM user_apply WHERE (_state < 3 OR (_state IN(3,4,5) AND uid=:uid))  AND _keep=1 ORDER BY uano ASC;";
	  return $SQL_String;
	}
	
	
	//-- search apply data
	public static function APPLY_GET_APPLY_META(){
	  $SQL_String = "SELECT * FROM metadata WHERE applyindex=:applyindex AND _keep=1 AND _open=1;";
	  return $SQL_String;
	}
	
	//-- check apply queue
	public static function APPLY_CHECK_USER_APPLY_QUEUE(){
	  $SQL_String = "SELECT count(*) AS count,max(apply_date) as last FROM user_apply WHERE uid=:uid AND _state !=3 AND _keep=1 GROUP BY uid;";
	  return $SQL_String;
	}
	
	
	//-- get user apply history
	public static function APPLY_GET_USER_APPLY_HISTORY(){
	  $SQL_String = "SELECT * FROM user_apply WHERE _keep=1 AND uid=:uid ORDER BY uano ASC;";
	  return $SQL_String;
	}
	
	
	//-- get user apply queue
	public static function APPLY_GET_USER_APPLY_QUEUE(){
	  $SQL_String = "SELECT * FROM user_apply WHERE _state < 3  AND _keep=1 AND uid=:uid ORDER BY uano ASC;";
	  return $SQL_String;
	}
	
	//-- get user apply queue
	public static function APPLY_GET_USER_APPLY_RESERVE(){
	  $SQL_String = "SELECT * FROM (SELECT * FROM user_apply WHERE _keep=1 AND uid=:uid) AS UAPPLY LEFT JOIN meta_apply ON UAPPLY.apply_code = meta_apply.apply_code WHERE check_info='原件閱覽' ORDER BY check_range DESC, mano ASC;";
	  return $SQL_String;
	}
	
	
	
	//-- regist apply tick
	public static function APPLY_REGIST_USER_APPLY_KEY(){
	  $SQL_String = "INSERT INTO user_apply VALUES(NULL,:uid,'',:reason,0,'".date('Y-m-d H:i:s')."',0,0,0,NULL,0);";
	  return $SQL_String;
	}
	
	//-- insert apply submit
	public static function APPLY_LIST_INSERT(){
	  $SQL_String = "INSERT INTO meta_apply VALUES(NULL,:apply_code,:in_store_no,:store_no,'_INITIAL','0000-00-00 00-00-00','','','','',:copy_mode,'0');";
	  return $SQL_String;
	}
	
	//-- insert apply submit
	public static function APPLY_BOOKING_INSERT(){
	  $SQL_String = "INSERT INTO meta_apply VALUES(NULL,:apply_code,:in_store_no,:store_no,'_BOOKING','0000-00-00 00-00-00','原件閱覽',:booking_date,'','',:copy_mode,'0');";
	  return $SQL_String;
	}
	
	
	//-- update apply regist
	public static function APPLY_SUBMIT_UPDATE(){
	  $SQL_String = "UPDATE user_apply SET apply_count=:apply_count , _keep=1 WHERE uid=:uid AND apply_code=:apply_code;";
	  return $SQL_String;
	}
	
	//-- check apply record
	public static function APPLY_REGIST_SEARCH(){
	  $SQL_String = "SELECT * FROM user_apply WHERE uid=:uid AND apply_code=:apply_code AND _keep=1;";
	  return $SQL_String;
	}
	
	//-- check apply record
	public static function APPLY_DATA_LIST(){
	  $SQL_String = "SELECT apply_code,in_store_no,store_no,check_state,check_time,check_info,check_range,check_note,copy_mode,search_json,page_count,_view,_checked FROM meta_apply LEFT JOIN metadata ON CONCAT(in_store_no,store_no)=applyindex WHERE apply_code=:apply_code;";
	  return $SQL_String;
	}
	
	
	/*-----------------------------------------------------*/
	
  }

?>