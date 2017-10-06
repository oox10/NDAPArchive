<?php
  
  /*
  *   [RCDH10 Admin Module] - Record Sql Library 
  *   Admin Record SQL SET
  *
  *   2017-01-01 ed.  
  */
  
  /* [ System Execute function Set ] */ 	
  
  class SQL_AdRecord{
	
	/***-- Admin Record SQL --***/
	
	//-- Admin Record : Get Meta Count
	public static function GET_META_ZONG_COUNT(){
	  $SQL_String = "SELECT zong,count(*) as COUNT From metadata WHERE 1 GROUP BY zong;";
	  return $SQL_String;
	}
	
	public static function GET_META_CONFIG_VALUE(){
	  $SQL_String = "SELECT label,setting From system_config WHERE module='Record' AND field='_statistics_dobj_count';";
	  return $SQL_String;
	}
	
	
	//-- Admin Record : Get Client Search Record
	public static function GET_SEARCH_RECORD_BY_DATE(){
	  $SQL_String = "SELECT Action_DateTime,User_Access_ID,Query_Term_Set From search_temp WHERE Action_DateTime BETWEEN :date_start AND :date_end ORDER BY Search_Action_Num ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Record : Get Client Search Member info
	public static function GET_SEARCH_RECORD_MEMBER_INFO($MemberAccountArray=array()){
	  $SQL_String = "SELECT user_id,user_name,user_staff,user_organ,user_education,user_major FROM user_login LEFT JOIN user_info ON uid=uno WHERE user_id IN('".join("','",$MemberAccountArray)."');";
	  return $SQL_String;
	}
	
	
	//-- Admin Record : Get Client Access Record
	public static function GET_ACCESS_RECORD_BY_DATE(){
	  $SQL_String = "SELECT SysId,StoreNo,User_Name,Creat_Time,data_type,zong,_view FROM result_history LEFT JOIN metadata ON SysId=system_id WHERE Creat_Time BETWEEN :date_start AND :date_end ORDER BY RslNum ASC;";
	  return $SQL_String;
	}
	
	//-- Admin Record : Get Target Account Logs
	public static function GET_SYSTEM_LOGS(){
	  $SQL_String = "SELECT slgno,time,acc_ip,acc_act,request,result,agent FROM logs_system WHERE time BETWEEN :date_start AND :date_end ORDER BY slgno DESC;";
	  return $SQL_String;
	}
	
	
	
  }	