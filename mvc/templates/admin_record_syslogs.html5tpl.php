<!DOCTYPE HTML>
<html>
  <head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" >
	<title><?php echo defined('_SYSTEM_HTML_TITLE') ? _SYSTEM_HTML_TITLE : 'RCDH System'; ?></title>
	
	<!-- CSS -->
	<link type="text/css" href="tool/jquery-ui-1.12.1.custom/jquery-ui.structure.min.css" rel="stylesheet" />
	<link type="text/css" href="tool/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css" rel="stylesheet" />
	<link type="text/css" href="tool/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet" />
	
	<!-- JS -->
	<script type="text/javascript" src="tool/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="tool/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script type="text/javascript" src="tool/canvasloader-min.js"></script>	
	<script type="text/javascript" src="tool/html2canvas.js"></script>	  
	
	<script type="text/javascript" src="tool/Highcharts-5.0.14/code/highcharts.js"></script>
	
	<!-- Self -->
	<link rel="stylesheet" type="text/css" href="theme/css/css_default.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_main.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_record_admin.css" />
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_admin.js"></script>
	<script type="text/javascript" src="js_record_admin.js"></script>
	
	
	<!-- PHP -->
	<?php
	$user_info 		= isset($this->vars['server']['data']['user']) 		? $this->vars['server']['data']['user'] 	: array('user'=>array('user_name'=>'Anonymous'),'group'=>array());
	
	$data_filter  	= isset($this->vars['server']['data']['syslogs']['filter']) 	? $this->vars['server']['data']['syslogs']['filter'] : array('date_start'=>date('Y-m-01'),'date_end'=>date('Y-m-t'));  
	$data_list  	= isset($this->vars['server']['data']['syslogs']['list']) 	? $this->vars['server']['data']['syslogs']['list'] : array();  
	$data_count 	= isset($this->vars['server']['data']['syslogs']['count']) 	? $this->vars['server']['data']['syslogs']['count'] : array();  
	
	$page_info 		= isset($this->vars['server']['info']) ? $this->vars['server']['info'] : '';  
	
	?>
	
	
  
  </head>
  
  
  <body>
	<div class='system_main_area'>
	  <div class='system_manual_area'>
	  <?php include('area_admin_manual.php'); ?>
	  </div>
	  <div class='system_content_area'>
        <div class='tool_banner' >
		  <ol id='system_breadcrumbs' typeof="BreadcrumbList" >
		  </ol>
		  <ul class='mode_select'>
		    <li class='mode_switch ' data-mode='index'>檢索系統紀錄</li>
			<li class='mode_switch atthis' data-mode='sylogs'>系統活動紀錄</li>
		  </ul>
		  <span class='account_option tool_right'>
		    <div class='account_info'>
			  <span id='acc_mark'><i class='m_head'></i><i class='m_body'></i></span>
			  <span id='acc_string'> 
			    <i class='acc_name'><?php echo $user_info['user']['user_name']; ?></i>
			    <i class='acc_group'><?php echo $user_info['user']['user_group']; ?></i>
			  </span>
			  <span id='acc_option'><a class='mark16 pic_more'></a> </span>
			</div>
		    <div class='account_control arrow_box'>
			  <ul class='acc_option_list'>
			    <li >
				  <label title='目前群組'> <i class="fa fa-university" aria-hidden="true"></i> 群組 </label>
				  <select id='acc_group_select'>
				    <?php foreach($user_info['group'] as $gset): ?>  
				    <option value='<?php echo $gset['id']?>' <?php echo $gset['now']?'selected':'' ?> > <?php echo $gset['name']; ?></option>
				    <?php endforeach; ?>
				  </select>
				</li>
				<li> 
				  <label> <i class="fa fa-user-secret" aria-hidden="true"></i> 角色 </label>
				  <span>
				    <?php foreach($user_info['group'] as $gid => $gset): ?>  
				    <?php if($gset['now']) echo join(',',$gset['roles']); ?>
				    <?php endforeach; ?>
				  </span> 
				</li>
				<li>
				  <label> <i class="fa fa-clock-o" aria-hidden="true"></i> 登入</label>
				  <span> <?php echo $user_info['login']; ?></span>
				</li>
			  </ul>
			  <div class='acc_option_final'>
			    <span id='acc_logout'> 登出 </span>
			  </div>
		    </div>
		  </span>
		</div>
		
		<div class='topic_banner'>
		  <div class='topic_header'> 
		    <div class='topic_title'> 資料紀錄與統計 </div>
			<div class='topic_descrip'> 系統資料數量、檢索系統使用紀錄 </div>
		  </div>
		</div>
		
		<div class='main_content' >
		  <!-- 資料列表區 -->
		  <div class='data_record_block' id='record_selecter' >
		    <div class='record_header'>
			  <span class='record_name'>系統操作紀錄</span>
			  <span>
			    <input type='text' class='record_filter' id='date_start' value='<?php echo $data_filter['date_start'];?>'> - 
				<input type='text' class='record_filter' id='date_end'   value='<?php echo $data_filter['date_end'];?>'>
				<span class='sysbtn' id='search_by_date' >查詢</span>
			  </span>
			  <span class='record_option'>
			    <i class='sysbtn' id='act_record_export_system_logs' title='下載活動紀錄'><a class='btn_mark pic_excel_file_s'  ></a> 下載活動紀錄 </i>
			  </span>
			</div> 
			<div class='record_body'>
		      <h1 class='record_name'>系統操作紀錄：<?php echo $data_filter['date_start'];?> - <?php echo $data_filter['date_end'];?> 期間 , 共<?php echo $data_count;?>筆活動紀錄 </h1>
			  <table class='record_list system_logs'>
		        <tr class='data_field'>
				  <td title='no'	>no.</td>
			      <td title='時間'	>時間</td>
				  <td title='類型'	>IP</td>
				  <td title='動作'	>動作</td>
				  <td title='參數'	>參數</td>
				  <td title='回應'	>回應</td>
				  <td title='介面'	>介面</td>
				  
				</tr>
				<tbody class='data_result' mode='list'   >   <!-- list / search--> 
				<?php foreach($data_list as $i=> $record): ?>  
				  <tr class='data_record actlog'>
				    <td ><?php echo $record['slgno'] ;?>. </td>
			        <td ><?php echo $record['time'] ;?></td>
				    <td ><?php echo $record['acc_ip'] ;?></td>
					<td ><?php echo $record['acc_act'] ;?></td>
					
					<td ><div class='longtext' ><?php echo $record['request'] ;?></div></td>
					<td ><div class='longtext' ><?php echo $record['result'] ;?></div></td>
					<td ><div class='longtext' ><?php echo $record['agent'] ;?></div></td>
			      </tr>
				<?php endforeach; ?>
			    </tbody>
			  </table>
			  <div class='record_control'>
			  共 <?php echo $data_count;?> 筆
			  <?php if(count($data_list) != $data_count):?>
			   / 顯示前 1000 筆
			  <?php endif; ?>	
			  </div>
		    </div>
		  </div>
		  
		</div>
	  </div>
	</div>
	
	
	<!-- 框架外結構  -->
	<div class='system_message_area'>
	  <div class='message_block'>
		<div id='message_container'>
		  <div class='msg_title'><?php echo $page_info; ?></div>
		  <div class='msg_info'></div>
		</div>
		<div id='area_close'></div>
      </div>
	</div> 
	<!-- 系統report -->
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
            <input type='radio' class='feedback_area_sel' name='feedback_area' value='system_body_block'>全頁面
            <input type='radio' class='feedback_area_sel' name='feedback_area' value='system_content_area'>中版面
            <input type='radio' class='feedback_area_sel' name='feedback_area' value='system_edit_area'>右版面
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
	<!-- 系統Loading -->
    <div class='system_loading_area'>
	  <div class='loading_block' >
	    <div class='loading_string'> 系統處理中 </div>
		<div class='loading_image' id='sysloader'></div>
	    <div class='loading_info'>
		  <span >如果系統過久無回應，請按[ Esc ] 關閉 loading 版面，並重新操作剛才的動作.</span>
	    </div>
	  </div>
	</div>
	<div class='system_print_area'><div class='page_print_container'></div></div>
  
  </body>
</html>