<?php
  
  /*
  *   [RCDH10 Admin Module] - Meta Sql Library 
  *   System Meta Admin SQL SET
  *
  *   2016-12-01 ed.  
  */
  
  /* [ System Execute function Set ] */ 	
  
  class SQL_AdMeta{
	
	
	/***-- Admin Meta SQL --***/  
	
	
	//-- Admin Meta :  get search meta list
	public static function GET_SEARCH_META($IdArray=array()){
	  $SQL_String = "SELECT * FROM metadata WHERE system_id=:system_id AND _keep=1";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Meta :  get target meta   
	public static function GET_TARGET_META_DATA(){
	  $SQL_String = "SELECT * FROM metadata WHERE system_id=:id AND _keep=1 ;";
	  return $SQL_String;
	}  
	
	//-- Admin Meta :  get user meta selected     
	public static function GET_TARGET_META_SELECTED($MetaArray = array()){
	  $SQL_String = "SELECT * FROM metadata WHERE system_id IN('".join("','",$MetaArray)."') AND _keep=1;";
	  return $SQL_String;
	}  
	
	
	//-- Admin Built : get collection detail arrange meta
	public static function GET_TARGET_COLLECTION_META(){
	  $SQL_String = "SELECT * FROM metadata WHERE zong=:zong AND collection=:collection_id AND _keep=1 ORDER BY system_id ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Built : get user projects 
	public static function GET_USER_PROJECTS(){
	  $SQL_String = "SELECT * FROM system_project WHERE _user=:userno AND _keep=1 ORDER BY spno ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Built : get target project
	public static function GET_TARGET_PROJECT(){
	  $SQL_String = "SELECT * FROM system_project WHERE _user=:userno AND _keep=1 AND spno=:spno;";
	  return $SQL_String;
	}
	
	//-- Admin Built : get target project
	public static function UPDATE_TARGET_PROJECT(){
	  $SQL_String = "UPDATE system_project SET regist_task=CONCAT(regist_task,';',:regtask),pjelements=:pjelements,_status='_import' WHERE _user=:userno AND spno=:spno;";
	  return $SQL_String;
	}
	
	
	//-- Admin Built : get source meta
	public static function GET_SOURCE_META($Zong){
	  switch($Zong){
		case '檔案': $SQL_String = "SELECT * FROM source_archive WHERE StoreNo=:id;"; break;
		case '議事錄': case '公報': $SQL_String = "SELECT * FROM source_meeting WHERE StoreNo=:id;"; break;
        case '議事影音': $SQL_String = "SELECT * FROM source_media  WHERE StoreNo=:id;"; break;
        case '議員傳記': $SQL_String = "SELECT * FROM source_member WHERE mbr_name=:id;"; break;
        case '活動照片': $SQL_String = "SELECT * FROM source_photo  WHERE StoreNo=:id;"; break;
		default: $SQL_String = "SELECT * FROM metadata WHERE system_id=:id AND _keep=2;"; break;
      }
	  return $SQL_String;
	}
	
	
	//-- Admin Built : update task element data
	public static function UPDATE_METADATA_DATA( $MmodifyFields = array(1) ){
	  $condition = array();
	  foreach($MmodifyFields as $field){
	    $condition[] = $field.'=:'.$field;
	  }
	  $SQL_String = "UPDATE metadata SET ".join(',',$condition)." WHERE system_id=:sid AND _keep=1;";
	  return $SQL_String;
	}
	
	//-- Admin Built : update task element data
	public static function UPDATE_SOURCE_META( $MmodifyFields = array(1) ,$Zong){
	  $condition = array();
	  foreach($MmodifyFields as $field){
	    $condition[] = $field.'=:'.$field;
	  }
	  switch($Zong){
		case '檔案': 	 $SQL_String = "UPDATE source_archive SET ".join(',',$condition)." WHERE StoreNo=:id;"; break;
		case '議事錄': 
		case '公報': 	 $SQL_String = "UPDATE source_meeting SET ".join(',',$condition)." WHERE StoreNo=:id;"; break;
        case '議事影音': $SQL_String = "UPDATE source_media SET ".join(',',$condition)." WHERE StoreNo=:id;"; break;
        case '議員傳記': $SQL_String = "UPDATE source_member SET ".join(',',$condition)." WHERE mbr_name=:id;"; break;
        case '活動照片': $SQL_String = "UPDATE source_photo SET ".join(',',$condition)." WHERE StoreNo=:id;"; break;
		default: $SQL_String = "UPDATE metadata SET ".join(',',$condition)." WHERE system_id=:id AND _keep=2;"; break;
      }
	  
	  //$SQL_String = "UPDATE metadata SET ".join(',',$condition)." WHERE system_id=:sid AND _keep=1;";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Meta : Metadata edit logs 
	public static function GET_TARGET_META_LOGS( ){
	  $SQL_String = "SELECT * FROM logs_metaedit WHERE systemid=:sid ORDER BY mmno DESC;";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Meta : Delete Metadata 
	public static function DELETE_TARGET_METADATA( ){
	  $SQL_String = "DELETE FROM metadata WHERE system_id=:sid AND _keep=1;";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Built :  set target task handler   
	public static function SET_TARGET_TASK_HANDLER(){
	  $SQL_String = "UPDATE meta_builttask SET handler=:handler,_status=:status WHERE task_no=:id AND _keep=1 ;";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Built : get task element list
	public static function GET_TARGET_TASK_ELEMENTS(){
	  $SQL_String = "SELECT * FROM meta_builtitem WHERE taskid=:taskid AND _keep=1 ORDER BY itemid ASC,mbio ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Built : get task element list
	public static function GET_TASKS_ELEMENTS_EXPORT($TasksId = array()){
	  $SQL_String = "SELECT * FROM meta_builtitem WHERE taskid IN('".join("','",$TasksId)."') AND _keep=1 ORDER BY taskid ASC, itemid ASC;";
	  return $SQL_String;
	}
	
	
	
	
	
	//-- Admin Built : get task element list
	public static function GET_TARGET_ELEMENT(){
	  $SQL_String = "SELECT * FROM meta_builtitem WHERE taskid=:taskid AND itemid=:itemid AND _keep=1 ;";
	  return $SQL_String;
	}
	
	//-- Admin
	public static function INSERT_ELEMENT_DATA( ){
	  $SQL_String = "INSERT INTO meta_builtitem VALUES(NULL,:taskid,:itemid,:item_title,:meta_json,'[]',:page_num_start,:page_num_end,:page_file_start,'','[]','','_initial',:editor,'".date('Y-m-d H:i:s')."',1);";
	  return $SQL_String;
	}
	
	
	
	//-- Admin Built : finish all element
	public static function FINISH_TASK_ELEMENTS(){
	  $SQL_String = "UPDATE meta_builtitem SET _estatus='_finish' WHERE taskid=:taskid AND _keep=1;";
	  return $SQL_String;
	}
	
	
	//-- Admin Built : update task status
	public static function UPDATE_TASK_STATUS(){
	  $SQL_String = "UPDATE meta_builttask SET element_count=(SELECT count(*) FROM meta_builtitem WHERE taskid=:taskid AND _estatus='_finish' AND _keep=1),_status=:status WHERE task_no=:taskid;";
	  return $SQL_String;
	}
	
	
	
	
	
	//-- Admin Meta : get meta can edit field
	public static function ADMIN_META_GET_META_EDIT_DATA(){
	  $SQL_String = "SELECT system_id,collection,identifier,json_string,_open,_view,_onland FROM metadata WHERE identifier=:id AND _keep=1 ;";
	  return $SQL_String;
	} 
	
	
	
	
	//-- Admin Meta :  logs meta modify   
	public static function LOGS_META_MODIFY(){
	  $SQL_String = "INSERT INTO logs_metaedit VALUES (NULL,NULL,:zong,:sysid,:identifier,:source,:update,:user,:result);";
	  return $SQL_String;
	}  
	
	//-- Admin Meta :  get target meta do config field  
	public static function ADMIN_META_GET_DOBJ_FIELD(){
	  $SQL_String = "SELECT system_id,data_type,dobj_json FROM metadata WHERE identifier=:id AND _keep=1 ;";
	  return $SQL_String;
	}
	
	
	//-- Admin Post : create new post
	public static function ADMIN_POST_INSERT_NEW_POST_DATA(){
	  $SQL_String = "INSERT INTO system_post VALUES(NULL,:post_type,:post_from,:post_to,:post_target,:post_level,:post_time_start,:post_time_end,:post_title,:post_content,:post_refer,1,'',:edit_user,NULL,:edit_group,0,1);";
	  return $SQL_String;
	}
	
	
	/***--  使用者上傳 SQL SET  --***/
	
	//- check upload file exist
	//  _upload : 完成上傳
	//  _archived : 完成導入
	public static function CHECK_FILE_UPLOAD_LIST(){
	  $SQL_String = "SELECT folder,_regist FROM system_upload WHERE hash=:hash AND _upload!='' AND _archived!='';";
	  return $SQL_String;
	}
	
	//- regist upload file 
	public static function REGIST_FILE_UPLOAD_RECORD(){
	  $SQL_String = "INSERT INTO system_upload VALUES(NULL,:utkid,:folder,:flag,:user,:hash,:store,:saveto,:name,:size,:mime,:type,:last,'".date('Y-m-d H:i:s')."','','','','[]',1);";
	  return $SQL_String;
	}
	
	//- update upload state  
	public static function UPDATE_FILE_UPLOAD_UPLOADED(){
	  $SQL_String = "UPDATE system_upload SET _upload='".date('Y-m-d H:i:s')."' WHERE urno=:urno;";
	  return $SQL_String;
	}
	
	
	//- process upload : get upload file  
	public static function SELECT_TARGET_UPLOAD_FILE(){
	  $SQL_String = "SELECT * FROM system_upload WHERE urno=:urno AND _keep=1;";
	  return $SQL_String;
	}
	
	//- process upload : delete upload file   
	public static function DELETE_TARGET_UPLOAD_FILE(){
	  $SQL_String = "UPDATE system_upload SET _keep=0 ,_process=:process , _logs=:logs WHERE urno=:urno;";
	  return $SQL_String;
	}
	
	
	//-- regist system task 
	public static function REGIST_SYSTEM_TASK(){
	  $SQL_String = "INSERT INTO system_tasks VALUES (NULL,:user,:task_name,:task_type,:task_num,:task_done,:time_initial,'0000-00-00 00:00:00','','',1);";
	  return $SQL_String;
	}
	
	//-- bind photo process task 
	public static function BIND_UPLOAD_TO_TASK(){
	  $SQL_String = "UPDATE system_upload SET utkid=:utkid,_logs=:logs WHERE urno=:urno;";
	  return $SQL_String;
	}
	
	
	
	//- update upload process  
	public static function UPDATE_FILE_UPLOAD_PROCESSED(){
	  $SQL_String = "UPDATE system_upload SET _process='".date('Y-m-d H:i:s')."',_archived=:archive,_logs=:logs WHERE urno=:urno;";
	  return $SQL_String;
	}
	
	//-- get uploaded file list 
	public static function SELECT_UPLOAD_OBJECT_LIST(){
	  $SQL_String = "SELECT * FROM system_upload WHERE user=:user AND folder=:folder AND flag=:flag AND _upload!='' AND _process='';";
	  return $SQL_String;
	}
	
	//-- finish folder upload state 
	public static function FINISH_USER_UPLOAD_TASK(){
	  $SQL_String = "UPDATE system_upload SET uploadtime='',_uploading=0 WHERE owner=:uno AND ufno=:ufno;";
	  return $SQL_String;
	}
	
	//-- Admin Meta :  get dobj download resouce   
	public static function DOBJ_DOWNLOAD_RESOUCE(){
	  $SQL_String = "SELECT * FROM logs_digitalobject WHERE action=:action AND note=:hash AND _user=:user;";
	  return $SQL_String;
	}  
	
	//-- Admin Meta :  logs do modify   
	public static function LOGS_DOBJ_MODIFY(){
	  $SQL_String = "INSERT INTO logs_digitalobject VALUES (NULL,NULL,:file,:action,:store,:note,:user);";
	  return $SQL_String;
	}  
	
	
	
	
	
	
  }

?>