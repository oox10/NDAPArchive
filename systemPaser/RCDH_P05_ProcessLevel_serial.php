<?php
    
	/*
	建構全宗階層
	20161114

	
	*/
	ini_set("memory_limit", "2048M");
    
    require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
    require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
	
	//-- load meta assist from db 
    $db = new DBModule;
    $db->db_connect('PDO');
	   
	$target_table = 'metadata';   
	
	$organ_reset_code_start = false;  // 每一組organ是否需要重編起始序號
	
	$db = new DBModule;
    $db->db_connect('PDO');
    
	// -- 清空類別表
	$db->DBLink->query('TRUNCATE TABLE search_level;');
	$code = array();
	$db_meta = $db->DBLink->prepare("SELECT search_json FROM metadata WHERE data_type='archive' AND _open=1 AND _keep=1 ORDER BY system_id ASC;");
	
	if( $db_meta->execute() && $metas = $db_meta->fetchAll(PDO::FETCH_ASSOC)){
	  
	  $class_lvset = array();
	  $meet_lvset = array();
	  
	  if($organ_reset_code_start){
		$code = array();
	  }
	  
	  
	  foreach($metas as $meta){
		
		if(!$meta['search_json']  || !$search=json_decode($meta['search_json'],true)  ){
		  continue;	
		}
		
		if(!isset($search['serial']) || !$search['serial']){
		  continue;	
		}
		
		$serial_set = explode(';',$search['serial']);
		foreach($serial_set as $serial){
			$cl = strlen($serial) ? explode('/',$serial) : array();
			
			$site = 0;
			$uplv = '';
			while( $c = array_shift($cl) ){
			
			  if($c=='-' && !count($cl) ){ break; }
			
			  $site++;
			  
			  if(isset($class_lvset[$uplv.':'.$c])){
				$class_lvset[$uplv.':'.$c]['count']++;
				$uplv = $class_lvset[$uplv.':'.$c]['lvcode'];
			  }else{ 
				if(!isset($code[$uplv])){
				  $code[$uplv] = 0;
				}
				$code[$uplv]++;
				if($site==1  && preg_match('/e/',dechex($code[$uplv]))){
				  $code[$uplv]++;
				}
				$lvid = $site==1 ?  strtoupper(str_pad(dechex($code[$uplv]),2,'0',STR_PAD_LEFT)) : str_pad($code[$uplv],2,'0',STR_PAD_LEFT);  
				$class_lvset[$uplv.':'.$c] = array(
				  'type'  => 'serial',
				  'zong' =>  'archive',
				  'uplv'  => $uplv,
				  'lvid'  => $lvid,
				  'lvcode'=> $uplv.$lvid,
				  'site'  => $site,
				  'name'  => $c,
				  'info'  => '',
				  'count'=>1); 
				$uplv = $uplv.$lvid;
			  }  
			}
		}	
	  }
	  
	  $lvset = array();
	  
	  foreach($class_lvset as $lvname => $lvdata){
		$lvset[$lvdata['lvcode']] = $lvdata;
	  }
	  
	  ksort($lvset);
	  
	  foreach($lvset as $lc => $ld){
		
		
		$db_lv = $db->DBLink->prepare("INSERT INTO search_level VALUES(NULL,:type,:zong,:uplv,:lvid,:lvcode,:site,:name,:info,:count);");
		$db_lv->bindValue(':type',$ld['type']);
		$db_lv->bindValue(':zong',$ld['zong']);
		$db_lv->bindValue(':uplv',$ld['uplv']);
		$db_lv->bindValue(':lvid',$ld['lvid']);
		$db_lv->bindValue(':lvcode',$ld['lvcode']);
		$db_lv->bindValue(':site',$ld['site']);
		$db_lv->bindValue(':name',$ld['name']);
		$db_lv->bindValue(':info',$ld['info']);
		$db_lv->bindValue(':count',$ld['count']);
		
		echo $lc.' / '.$ld['name'];
		if($db_lv->execute()){
		  echo " O ";
		}
		echo "<br/>";
	  }
	  
	  
	  echo ' ----------------------  '."<br/>"; 
	  echo ' ----------------------  '."<br/>"; 
	  echo "<br/>"; 
	  
	}
		
	  
	
	
	
	
	
	
	
	



?>