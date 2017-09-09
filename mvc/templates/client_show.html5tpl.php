<!DOCTYPE HTML>
<html>
  <head>
    <meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title><?php echo defined('_SYSTEM_HTML_TITLE') ? _SYSTEM_HTML_TITLE:'資料庫';?></title>
	<!-- CSS -->
	<link type="text/css" href="tool/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_default.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_system.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_display.css" />
	
	<link type="text/css" href="tool/jScrollPane/jScrollPane.css" rel="stylesheet" media="all" />
		 
	<!-- JS -->
	<script type="text/javascript" src="tool/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="tool/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script type="text/javascript" src="tool/jquery-mousewheel-3.1.13/jquery.mousewheel.min.js"></script>
	
	<script type="text/javascript" src="tool/canvasloader-min.js"></script>	
	<script type="text/javascript" src="tool/jScrollPane/jScrollPane_Group.js"></script>
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_client.js"></script>
	<script type="text/javascript" src="js_display.js"></script>	 
	
	
	<!-- PHP DATA -->
	
	<?php
	//echo "<pre>";
	//var_dump($_SESSION['AHAS']);
	//exit(1);
	
	$session = $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT'];
	
	$user_info 		= isset($this->vars['server']['data']['user']) 	? $this->vars['server']['data']['user'] 	: array('user'=>array(),'sign'=>'anonymous','group'=>array(),'login'=>'');
	
	// 查詢結果
	$data_records	= isset($this->vars['server']['data']['result']) ? $this->vars['server']['data']['result'] : array();
    
	$Reference = $data_records;
	
	$page_info = isset($this->vars['server']['info']) ? $this->vars['server']['info'] : ''; 
	
	$user_ip   = System_Helper::get_client_ip();
	
	?>
	
  </head>
  
  <body>
	<div class='system_main_area'>
	  
	  <div class='system_display_area'>
	    <!-- 系統內容 -->
		<div class='display_block'>
		
		  <div class='display_meta_area' id='Project_Mode_Meta'>
		    
			<div class='func_block' id='user_account'>
			  <h1>
				<span class='iconv'  ><i class="fa fa-user" aria-hidden="true"></i></span> 
				<?php if( isset($session['ACCOUNT_TYPE']) && $session['ACCOUNT_TYPE']!='GUEST' ): ?>
				<span class='signin'><?php echo $user_info['signin']; ?></span>
				<?php else: ?>
				<span class='signin'>訪客 ( <?php echo $user_info['signin']; ?> )</span>
				<?php endif; ?>
			  
			  </h1>
			  <div class='login_info'>
				<div class='login_time'><label>登入時間：<?php echo $user_info['login']; ?></label></div>
				<div class='user_group'><label>所屬群組：<?php echo $user_info['user']['user_group'] ? $user_info['user']['user_group'] : '訪客'; ?></label></div>
			  </div>
			</div>
			
			
			<div id='func_area'>
			  <?php if($data_records['@PrintOption']['value'] && $session['ACCOUNT_TYPE']!='GUEST' ): ?>
			  <span class='option' id='act_image_print'> <i class="fa fa-print" aria-hidden="true"></i> 列印影像 </span> 
			  <?php endif; ?>
			  <?php if(isset($session['ACCESS_LOCK']) && $session['ACCESS_LOCK'] && false): ?> 
			  <span class='option' id='act_image_unlock'> <i class="fa fa-lock" aria-hidden="true"></i> 解除鎖定 </span> 
			  <?php endif; ?>
			</div>
			
			
			
			
			
			<div class='browse_member'>
			  <div class='browse_member_content' id='meta_area'  >
				<table class='meta_table'>
			    <?php foreach($data_records as $field_name => $field_data): ?>
				  <?php if(!preg_match('/^@/',$field_name) && isset($field_data['print'])): ?>
					<?php if('text'===$field_data['print']): ?>     
			        <tr class='meta_record'>
				      <td colspan=2> 
				        <div class='meta_field'><?php echo $field_data['field'];?>：</div>
					    <div class='meta_value'><?php echo $field_data['value'];?></div> 
				      </td>
				    </tr> 
					<?php elseif('attach'===$field_data['print']): ?>
					
					  <?php if($field_data['value']): ?>
					<tr class='meta_record'><td class='meta_field'> <?php echo $field_data['field']?>：</td><td class='meta_value'><?php echo $field_data['value']?></td></tr> 
			          <?php endif; ?>
					  
				    <?php else: ?>      
					<tr class='meta_record'><td class='meta_field'> <?php echo $field_data['field']?>：</td><td class='meta_value'><?php echo $field_data['value']?></td></tr> 
			        <?php endif; ?>    
				  <?php endif; ?>
				<?php endforeach; ?>
			    </table>
			  </div>
			  
			</div>
		  </div> <!-- End of browse area--> 
		  
		  <div class='display_object_area' >
			<div class='obj_display'>
			  <div class='obj_view' id='image_display' ></div>
			  <div class='obj_bottom'>
			    <ul class='obj_option_area' name='<?php echo $data_records['@SystemLink']['field'];?>'>
                  <li class='obj_opt' >
				    共 <span id='img_num'></span> 頁，
			        <span> 前往 </span>
					<select id='img_jump' >
				      <option > P.1 </option>
				    </select>
			      </li>
			      <li class='obj_opt' id='img_ctrl' >
		            <div class='opt_size_title'>影像倍率</div>
				    <div class=''  id="opt_size_slider" ></div> 
			        <div class='opt_size_info' id='ImageSizeRate'>x1.0</div>
			      </li>
				  <li class='obj_opt' id='ref_block' style='display:none;'>
				    <span class='ref_title'> 引用連結 :</span> <span class='ref_block' id='ref_address'></span>
				  </li>
			    </ul>
			  </div>
			  <div class='obj_refer'>
			    <span class='ref_title'>引用資訊：</span>
				<div class='ref_string'><input id='reference_string' type='text' value="〈<?php echo _SYSTEM_HTML_TITLE; ?>，<?php if(isset($Reference['identifier'])): ?>〉典藏號：<?php echo strip_tags($Reference['identifier']['value']);?><?php endif; ?>。" readonly=true /></div>
			  </div>	
			  <a class='img_botton onpoj_ctrl onobj_dw' id='onimg_dw' title='下一頁'></a>
			  <a class='img_botton onpoj_ctrl onobj_up' id='onimg_up' title='上一頁'></a> 
			  <span class='onpoj_info' id='btinfo_dw'> Page End </span>
			  <span class='onpoj_info' id='btinfo_up'> Page First </span>
			  
			  <div id='main_page_loading'>
				<span>
				<?xml version="1.0" encoding="utf-8"?><svg width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-spin"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><g transform="translate(50 50)"><g transform="rotate(0) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(45) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.12s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.12s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(90) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.25s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.25s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(135) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.37s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.37s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(180) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.5s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.5s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(225) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.62s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.62s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(270) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.75s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.75s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(315) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.87s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.87s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g></g></svg>
				</span>
			  </div>
			  
			  
			</div>
		  </div> <!-- End Of obj_area -->
	      
		  <div class='display_function_area'>
		    <!-- 額外function -->
		  </div> 
		</div>
	  </div>  
	</div>
    
	
	<!-- System Alert  -->
	<div class='system_message_area'>
	    <div class='message_block'>
		  <div id='message_container'>
		    <div class='msg_title'></div>
		    <div class='msg_info'><?php echo $page_info; ?></div>
		  </div>
		  <div id='area_close'></div>
        </div>
	</div> 
	
	<!-- System Feedback  -->
	<div class='system_feedback_area'>
		<div class='feedback_block'>
		<div class='feedback_header tr_like' >
		  <span class='fbh_title'> 系統回報 </span>
		  <a class='fbh_option' id='act_feedback_close' title='關閉' ><i class='mark16 pic_close'></i></a>
		</div>
		<div class='feedback_body' >
		  <div class='fb_imgload'> 建立預覽中..</div>
		  <div class='fb_preview'></div>
		  <div class='fb_areasel'>
			<span>回報頁面:</span>
			<input type='radio' class='feedback_area_sel'   name='feedback_area' value='body'>全頁面
			<input type='radio' class='feedback_upload_sel' name='feedback_area' value='user_upload'><input type='file'  id='feedback_img_upload' >
		  </div>
		  <div class='fb_descrip'>
			<div class=''>
			  <span class='fbd_title'>回報類型:</span>
			  <input type='checkbox' class='feedback_type' name='fbd_type' value='資料問題' ><span >資料問題</span>，
			  <input type='checkbox' class='feedback_type' name='fbd_type' value='系統問題' ><span >系統問題</span>，
			  <input type='checkbox' class='feedback_type' name='fbd_type' value='使用問題' ><span >使用問題</span>，
			  <input type='checkbox' class='feedback_type' name='fbd_type' value='建議回饋' ><span >建議回饋</span>，
			  <input type='checkbox' class='feedback_type' name='fbd_type' value='其他' >其他:<input type='text' class='fbd_type_other' name='fbd_type_other' value='' >
			</div>
			<div class='fbd_title'>回報描述:</div>
			<textarea  class='feedback_content'  name='fbd_content'></textarea>
		  </div>
		</div>
		<div class='feedback_bottom tr_like' >
		  <a class='sysbtn btn_feedback' id='act_feedback_cancel' > <i class='mark16 pic_account_off'></i> 取 消 </a>
		  <a class='sysbtn btn_feedback' id='act_feedback_submit' > <i class='mark16 pic_account_on'></i> 送 出 </a>		
		</div>
		</div>
	</div>  
    
	<!-- System Loading -->
    <div class='system_loading_area'>
	    <div class='loading_block' >
	      <div class='loading_string'> 系統處理中 </div>
		  <div class='loading_image' id='sysloader'></div>
	      <div class='loading_info'>
		    <span >如果系統過久無回應，請按[ Esc ] 關閉 loading 版面，並重新操作剛才的動作.</span>
	      </div>
	    </div>
	</div>
	
	
  </body>
</html>  