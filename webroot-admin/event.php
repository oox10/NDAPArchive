<?php
  session_start();
  
  require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
  require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');
  
  $task_no = isset($_REQUEST['task']) ? intval($_REQUEST['task']) : 0;
  $dobj_no = isset($_REQUEST['dobj']) ? intval($_REQUEST['dobj']) : 0;
 
  if(!isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['LOGIN_TOKEN']) || !$task_no ){
	header('HTTP/1.0 400 Bad Request', true, 400);
    exit(1);	
  }
  
  $db = new DBModule;
  $db->db_connect('PDO');
  
  // 確認任務
  $task_query = $db->DBLink->query("SELECT * FROM system_tasks WHERE utk=".$task_no.";");
  if(!$task_event = $task_query->fetch(PDO::FETCH_ASSOC)){
	header('HTTP/1.0 400 Bad Request', true, 400);
    exit(1);  
  }
  
  //動作函數
  //TASK : PROCESSING / TASKFINISH
  //DOBJ : DOIMPORTED  
  
  ob_end_clean();
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  ob_start();
  
  
  if(!$dobj_no){ 
    
	//沒有物件號，純任務處理 
	if($task_event['time_finish']=='0000-00-00 00:00:00'){
	  // 動作仍在持續
      echo "event: PROCESSING"."\n";
	  echo 'data: {"task": "'.$task_no.'", "time": "'.intval(strtotime('now')-strtotime($task_event['time_initial'])).'", "progress": "'.$task_event['task_done'].' / '.$task_event['task_num'].'"}'."\n\n";  
    }else{
	  // 動作完成
      $task_query = $db->DBLink->query("UPDATE system_tasks SET time_access='".date('Y-m-d H:i:s')."' WHERE utk=".$task_no.";");  
	  echo "event: TASKFINISH"."\n";
	  echo 'data: {"task": "'.$task_no.'", "name": "", "count": "'.$task_event['task_done'].'", "href": "'.$task_event['task_result'].'"}'."\n\n";  
    }
  
  }else{
	// 數位物件處理
    $dobj_query = $db->DBLink->query("SELECT * FROM system_upload WHERE utkid=".$task_no." AND urno=".$dobj_no." AND _keep=1;");
    
	if(!$dobj_event = $dobj_query->fetch(PDO::FETCH_ASSOC)){
	  header('HTTP/1.0 400 Bad Request', true, 400);
      exit(1);  
    }	
	
	if($dobj_event['_archived']){  
	  // 已完成	
	  echo "event: DOIMPORTED"."\n";
	  echo 'data: {"task": "'.$task_no.'", "time": "'.$dobj_event['_archived'].'", "target": "'.$dobj_event['name'].'", "info": "'.$dobj_event['mime'].'"}'."\n\n";
	  
	}else{
	  // 處理中	
      echo "event: PROCESSING"."\n";
	  echo 'data: {"task": "'.$task_no.'", "time": "'.intval(strtotime('now')-strtotime($dobj_event['_process'])).'", "target": "'.$dobj_event['name'].'"}'."\n\n";  	  
	}
	  
  }
  
  ob_flush();
  flush();
  exit(1);
?>