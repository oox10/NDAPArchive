<?php
  
  /* 
  發信機器人
  
  時間：每日
  頻率：10min
  對象：所有信件列表
  
  */
  
  
  define('ROOT',dirname(dirname(__FILE__)).'/');
  
  require_once(dirname(dirname(__FILE__)).'/conf/system_config.php');
  require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
  require_once(dirname(dirname(__FILE__)).'/mvc/dbsql/SQL_AdMailer.php');   
  
  require ROOT.'mvc/lib/vendor/autoload.php';
  
  $result  = array('action'=>false,'data'=>array(),'message'=>array());
  $logs_file = ROOT.'/systemTasks/jobs.log';
  $logs_message = date("Y-m-d H:i:s").' [TASK] SYSTEM MAILJOBS START!.'.PHP_EOL;
  file_put_contents($logs_file,$logs_message,FILE_APPEND);
  echo $logs_message;
  
  try{
	
	$db = new DBModule;
    $db->db_connect('PDO'); 
	
	$MailType = isset($argv[1]) ? $argv[1] : 'APPLY';
	$MailDate = isset($argv[2]) ? $argv[2] : date('Y-m-d');
	
	// 取得本日發信工作
	$mail_jobs = NULL;
	$DB_GET	= $db->DBLink->prepare(SQL_AdMailer::GET_MAILER_TASKS());
	$DB_GET->bindValue(':mail_date'   , strtotime($MailDate) ? date('Y-m-d',strtotime($MailDate)) : date('Y-m-d'));
	if( !$DB_GET->execute() ){
	  throw new Exception('_SYSTEM_ERROR_DB_RESULT_NULL');
	}
	
	while( $mail_job = $DB_GET->fetch(PDO::FETCH_ASSOC) ){
	  // 設定信件內容
	  $logs_message = date("Y-m-d H:i:s")." [TASK] SENT MAIL [".$mail_job['mail_to']."]: ";	  
	  $to_sent      = explode(';',$mail_job['mail_to']);		  
	  $mail_title   = $mail_job['mail_title'];
	  $mail_content = htmlspecialchars_decode($mail_job['mail_content'],ENT_QUOTES);
	  $mail_from    =  $mail_job['mail_from'];
	  $mail_logs    = json_decode($mail_job['_active_logs'],true);
	  
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
	  $mail_error = '';
	  
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
		
		foreach($to_sent as $mail_to){
		  //$mail->AddAddress('','');
	      $mail_to_sent = (preg_match('/.*?\s*<(.*?)>$/',$mail_to,$mail_paser)) ? trim($mail_paser[1]) : trim($mail_to);
		  if(!filter_var($mail_to_sent, FILTER_VALIDATE_EMAIL)){
		    throw new Exception('_LOGIN_INFO_REGISTER_MAIL_FALSE');
		  }
		  $mail->AddAddress($mail_to_sent,'');	
		}
		
		$mail->SetFrom( $mail_from, _SYSTEM_MAIL_FROM_NAME);
		$mail->AddReplyTo( $mail_from, _SYSTEM_MAIL_FROM_NAME); // 回信位址
		$mail->Subject = $mail_title ;
		$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
		$mail->MsgHTML($mail_content);
	  
		//$mail->AddCC(); 
		//$mail->AddAttachment('images/phpmailer.gif');      // attachment
		
		if(!$mail->Send()) {
		  throw new Exception($mail->ErrorInfo);  
		}  
		
		$logs_message .= 'success.'.PHP_EOL;
        file_put_contents($logs_file,$logs_message,FILE_APPEND);
        echo $logs_message;
		sleep(3);
		
	  } catch (phpmailerException $e) {
		$mail_error = $e->errorMessage();  //Pretty error messages from PHPMailer
		$logs_message .= "fail:".$mail_error.PHP_EOL;
        file_put_contents($logs_file,$logs_message,FILE_APPEND);
        echo $logs_message;
		
	  } catch (Exception $e) {
		$mail_error = $e->errorMessage();  //echo $e->getMessage(); //Boring error messages from anything else!
		$logs_message .= "fail:".$mail_error.PHP_EOL;
        file_put_contents($logs_file,$logs_message,FILE_APPEND);
        echo $logs_message;
	  }
	  
	  // final 
	  $mail_logs[date('Y-m-d H:i:s')] = $mail_error ?  'SENT MAIL Fails:'.$mail_error : 'SENT MAIL SUCCESS.';
	  $fail_array = array_map(function($logs){ return preg_match('/SENT MAIL Fails/',$logs)?1:0; },array_values($mail_logs));
	  $status = $mail_error=='' ? 1 : (array_sum($fail_array) >= 5 ? -1:0) ;  
	  $DB_UPD = $db->DBLink->prepare(SQL_AdMailer::UPDATE_MAIL_DATA(array('_status_code','_active_time','_result','_active_logs')));
	  $DB_UPD->bindValue(':_status_code'  , $status , PDO::PARAM_INT);	
	  $DB_UPD->bindValue(':_active_time' , date('Y-m-d H:i:s'));
	  $DB_UPD->bindValue(':_result'  , $mail_error ? $mail_error:'Mail Sent');
	  $DB_UPD->bindValue(':_active_logs'  , json_encode($mail_logs));
	  $DB_UPD->bindValue(':smno'    , $mail_job['smno']);
	  $DB_UPD->execute();
	}
  } catch (Exception $e) {
	$result['message'][] = $e->getMessage();
  }
   
  $logs_message = date("Y-m-d H:i:s").' [TASK] SYSTEM MAILJOBS FINISH!.'.PHP_EOL;
  file_put_contents($logs_file,$logs_message,FILE_APPEND);
  echo $logs_message;
  
?>