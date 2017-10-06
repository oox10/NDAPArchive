        
	  <div class='manual_continer'>	
		<div class='system_mark'>
		  <span class='manual_mark'><i class='option pic_mark_manual mark32'></i></span>
		  <span class='system_title'>
		    <span class='mark_title_word'><?php echo _SYSTEM_NAME_SHORT; ?></span>
		    <span class='mark_version_word'>Pb. <?php echo _SYSTEM_PUBLISH_VERSION; ?></span>
		  </span>
		</div>
	    <ul class='main_manual'>
		  <?php $admin_filter = isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION']['*']) ? true : false; // 權限過濾，開通之模組才會顯示 ?>
		  <li>
		    <div class='opgroup_name '>
			  <i class="fa fa-cog" aria-hidden="true"></i><span>系統管理</span>
			</div>
		    <ul class='group_manuel'>
			  <li  title='帳號管理'	class='option func_activate'	id='Staff'		>
			    <i class="fa fa-user" aria-hidden="true"></i> <span >帳號與單位管理</span> 
			    <a class='alert' num=<?php echo isset($user_info['newaccount']) ? $user_info['newaccount']:0; ?> ><?php echo isset($user_info['newaccount']) ? $user_info['newaccount']:0; ?></a>
			  </li>
			  <li  title='回報管理'	class='option func_activate'	id='Tracking'	><i class="fa fa-wrench" aria-hidden="true"></i> <span >回報管理</span> </li>
			</ul>
		  </li>
		  <li class='option_group'>
		    <div class='opgroup_name '>
			  <i class="fa fa-code" aria-hidden="true"></i> <span>網站管理</span>
			</div>
		    <ul class='group_manuel'>
			  <?php if($admin_filter || (isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Post')]) && intval($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Post')])!=0) ): ?>
			  <li  title='發布消息'	class='option func_activate'	id='Post'	><i class="fa fa-bell-o" aria-hidden="true"></i> <span >發布消息</span> </li>
			  <?php endif; ?>
			  
			  <?php if($admin_filter || (isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Mailer')]) && intval($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Mailer')])!=0 )): ?>
			  <li  title='信件寄送'  class='option func_activate'	id='Mailer'	><i class="fa fa-envelope-o" aria-hidden="true"></i> <span >信件寄送</span> </li>
			  <?php endif; ?>
              
			  <?php if($admin_filter || (isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Record')]) && intval($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Record')])!=0) ): ?>
			  <li  title='統計資訊'  class='option func_activate'	id='Record'	><i class="fa fa-area-chart" aria-hidden="true"></i> <span >統計資訊</span> </li>
			  <?php endif; ?>
              
			</ul>
		  </li>
		  <li class='option_group'>
		    <div class='opgroup_name '>
			  <i class="fa fa-calendar-check-o" aria-hidden="true"></i> <span>資料管理</span>
			</div>
		    <ul class='group_manuel'>
			  <?php if($admin_filter || (isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Meta')]) && intval($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Meta')])!=0) ): ?>
			  <li  title='資料管理'	 class='option func_activate'	id='Meta'	><i class="fa fa-pencil-square-o" aria-hidden="true"></i> <span >詮釋資料編輯</span> </li>
			  <?php endif; ?>
			  
			  <?php if($admin_filter || (isset($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Project')]) && intval($_SESSION[_SYSTEM_NAME_SHORT]['ADMIN']['PERMISSION'][strtolower('Project')])!=0) ): ?>
			  <li  title='專題資料夾'	 class='option func_activate'	id='Project'	><i class="fa fa-folder-open-o" aria-hidden="true"></i> <span >專題資料夾</span> </li>
			  <?php endif; ?>
			  
			</ul>
		  </li>
		  <li>
		    <div class='opgroup_name '>
			  <i class="fa fa-tasks" aria-hidden="true"></i> <span>使用者功能</span>
			</div>
		    <ul class='group_manuel'>
			  <li  title='錯誤回報' class='option' id='user_feedback'><i class="fa fa-bug" aria-hidden="true"></i> <span >錯誤回報</span> </li>
			</ul>
		  </li>
		  
	    </ul>
	  </div>
  