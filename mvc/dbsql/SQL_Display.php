<?php
  
  /*
  *   [RCDH10 Admin Module] - Meta Sql Library 
  *   Archive Display Clint SQL SET
  *
  *   2016-08-01 ed.  
  */
  
  /* [ System Execute function Set ] */ 	
  
  class SQL_Display{
    
	/***-- Client Display SQL --***/  
	
	//-- Client Display :  get metadata from system id
	public static function GET_DISPLAY_METADATA(){
	  $SQL_String = "SELECT * FROM  metadata WHERE system_id=:sid;";
	  return $SQL_String;
	}
	
	public static function GET_DISPLAY_METADATA_BY_IDENTIFIER(){
	  $SQL_String = "SELECT * FROM  metadata WHERE identifier=:sid;";
	  return $SQL_String;
	}
	
	//-- :註冊檔案存取
	public static function REGIST_RESULT_HISTORY(){
	  $SQL_String = "INSERT INTO result_history VALUES(NULL,:CODE,:SYSID,:ISNO,:SNO,:UID,:ACCPER,NULL);";
	  return $SQL_String; 
	}
	
	public static function GET_DATA_DISPLAY(){
	  $SQL_String = "SELECT * FROM result_history LEFT JOIN metadata ON result_history.SysId = metadata.system_id WHERE CONCAT(RslNum,Code)=:ACCCODE;";
	  return $SQL_String; 
	}
	
	public static function REGIST_DISPLAY_PAGE(){
	  $SQL_String = "INSERT INTO result_visit VALUES(NULL,:ACCCODE,:ACCPAGE,:UIP,:UID,'".date('Y-m-d H:i:s')."');";
	  return $SQL_String; 
	}
	
	public static function GET_VISIT_PAGE($limit=20){
	  $SQL_String = "SELECT Vtime,Visit_Code,Visit_Page,result_history.StoreNo,Title FROM (SELECT Visit_Time AS Vtime,VisitId,Visit_Code,Visit_Page FROM result_visit,(SELECT max(Visit_Time) as vt FROM result_visit WHERE User_Name=:UID GROUP BY Visit_Code) as rv WHERE Visit_Time = rv.vt ORDER BY Visit_Time DESC) AS temp LEFT JOIN result_history ON temp.Visit_Code=CONCAT(result_history.RslNum,result_history.Code) LEFT JOIN metadata ON result_history.StoreNo = metadata.StoreNo ORDER BY Vtime DESC LIMIT 0,".$limit.';';
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
	
	public static function UNLOCK_ACC_PERMISSION(){
	  $SQL_String = "UPDATE result_history SET Acc_Permission=1 WHERE RslNum=:ACCCODE;";
	  return $SQL_String; 
	}
	
  
  }