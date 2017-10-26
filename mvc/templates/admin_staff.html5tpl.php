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
	
	<!-- Self -->
	<link rel="stylesheet" type="text/css" href="theme/css/css_default.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_main.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_staff_admin.css" />
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_admin.js"></script>
	<script type="text/javascript" src="js_staff_admin.js"></script>
	
	
	<!-- PHP -->
	<?php
	
	//<<Refer from page>>  : 頁面相依變數
	$user_info 		= isset($this->vars['server']['data']['user']) 		? $this->vars['server']['data']['user'] 	: array('user'=>array('user_name'=>'Anonymous'),'group'=>array());
	
	// 模組參數 : [role,setting]
	$module_config  = isset($this->vars['server']['data']['config']) 	? $this->vars['server']['data']['config'] 	: array();  
	
	$page_info 		= isset($this->vars['server']['info']) ? $this->vars['server']['info'] : '';  
	
	$data_list  	= isset($this->vars['server']['data']['records']['list']) 	? $this->vars['server']['data']['records']['list'] : array();  
	$data_count 	= isset($this->vars['server']['data']['records']['count']) 	? $this->vars['server']['data']['records']['count'] : 0;  
	$data_limit 	= isset($this->vars['server']['data']['records']['limit'])    ? $this->vars['server']['data']['records']['limit'] : array();
	$data_page  	= isset($this->vars['server']['data']['records']['page'])    ? $this->vars['server']['data']['records']['page'] : '1-10';
	$data_type  	= isset($this->vars['server']['data']['records']['type'])    ? $this->vars['server']['data']['records']['type'] : 'all';
	$html_conf      = isset($this->vars['server']['data']['records']['config'])    ? $this->vars['server']['data']['records']['config'] : '';
	
	$page_conf  	= isset($this->vars['server']['data']['page'])    ? $this->vars['server']['data']['page'] : array();
	
	//<<Refer from login>> : 登入相依變數
	$ui_config      = isset($user_info['permission']['interface_mask']) ? $user_info['permission']['interface_mask'] : array();
	$admin_open   = isset($ui_config['*']) ? true : false;
	
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
		    <div class='topic_title'> 群組帳號管理 </div>
			<div class='topic_descrip'> 群組內之帳號審核、設定與管理 </div>
		  </div>
		  <div class='module_setting' id='module_setting'>
		  <?php if( $admin_open || isset($ui_config['admin_staff.html5tpl.php']['module_setting']) && intval($ui_config['admin_staff.html5tpl.php']['module_setting'])): ?> 
			<h2>模組參數設定</h2>
			<ul class='mdconfig'>
			<?php if(isset($module_config['setting'])):?>  
			<?php   foreach($module_config['setting'] as $i => $mset): ?>
			  <li>
			    <label><?php echo ($i+1).'. '.$mset['label']; ?></label>
				<?php if($mset['type']=='switch'): ?>
			    <label class="switch" >
				  <input type="checkbox"  class='module_config' name='<?php echo $mset['field']; ?>' data-module='<?php echo $mset['module']; ?>'  data-save='<?php echo $mset['setting'];?>'  data-default='<?php echo $mset['default']; ?>'  <?php echo intval($mset['setting'])?'checked':''; ?>  />
				  <div class="slider round"></div>
				</label>
				<?php elseif($mset['type']=='text'): ?>
			    <input type='text' name='<?php echo $mset['field']; ?>' data-module='<?php echo $mset['module']; ?>' value='<?php echo $mset['setting']; ?>' data-save='<?php echo $mset['setting'];?>' data-default='<?php echo $mset['default']; ?>' />
			    <?php endif; ?>
			  </li>
			<?php   endforeach; ?>
			<?php endif; ?>
			</ul>
		  <?php endif ?>	
		  </div> 
		  <div class='lunch_option'> 
		    <?php if($admin_open || isset($ui_config['admin_staff.html5tpl.php']['act_set_gmember']) && intval($ui_config['admin_staff.html5tpl.php']['act_set_gmember'])): ?> 
			<button type="button" class='active' id='act_set_gmember'><i class="fa fa-users" aria-hidden="true"></i> 設定群組</button>
			<?php endif ?>
		  </div>
		</div>
		
		<div class='main_content' >
		  <!-- 資料列表區 -->
		  <div class='data_record_block' id='record_selecter' >
		    <div class='record_header'>
			  <span class='record_name'>帳號清單</span>
			  <span class='record_option'>
			    資料篩選：	
				<input type='radio' name='data_type' value='all' <?php echo $data_type=='all'?'checked':''; ?> >全部帳號
				<input type='radio' name='data_type' value='mbr' <?php echo $data_type=='mbr'?'checked':''; ?> >註冊會員
				<input type='radio' name='data_type' value='tpa' <?php echo $data_type=='tpa'?'checked':''; ?> >單位帳號
				<input type='radio' name='data_type' value='self' <?php echo $data_type=='self'?'checked':''; ?> >我的帳號
				/ 搜尋:
				<span class='record_search_field'> 
				  <input  type='text'   id='data_search_condition' name='data_search' value='<?php echo isset($html_conf['condition'])&&$html_conf['condition'] ?$html_conf['condition']:'';?>' placeholder='輸入搜尋條件'  />
				  <button type='button' class='active' id='act_record_search' ><i class="fa fa-search" aria-hidden="true"></i></button>
				</span>
			  </span>
			</div> 
			<div class='record_body'>
		      <div class='record_control'>
			    <span class='record_limit'>  
			      顯示 :
				  <select class='record_view' >
				    <option value='1-5'   <?php echo $data_limit['range'] =='1-5'	? 'selected': '' ?>  > 5 </option>
					<option value='1-10'  <?php echo $data_limit['range'] =='1-10'	? 'selected': '' ?> > 10 </option>
					<option value='1-100' <?php echo $data_limit['range'] =='1-100'	? 'selected': '' ?> > 100 </option>
				  </select> 筆
				  <?php if(isset($html_conf['orderby']['name'])):?>
				  <span> / 排序依 : <?php echo $html_conf['orderby']['name']; ?> : <?php echo $html_conf['orderby']['mode']=='1' ? 'DESC' : 'ASC'; ?></span>
				  <?php endif;?>
			    </span>
				<span class='record_option'>
				  <a class='sysbtn' id='act_bath_account_pass' title='勾選名單批次通過'> <i class="fa fa-check-circle-o" aria-hidden="true"></i> 批次通過 </a>
			    </span>
			  </div>
			  <table class='record_list'>
		        <tr class='data_field'>
			      <td title='全選/全不選' ><input type='checkbox' value='_all' class='act_select_all' /></td>
				  <td title='編號'	>
				    no.
				    <a class = 'option order_by' 
					   order = 'uno'  
					   name  = '註冊序號' 
					   mode  = '<?php echo isset($html_conf['orderby']['field']) && $html_conf['orderby']['field']=='uno' ? $html_conf['orderby']['mode'] : '0' ?>' 
					>
					  <i class="fa fa-sort" aria-hidden="true"  title='可排序' ></i>
                      <i class="fa fa-long-arrow-up" aria-hidden="true" title='大到小' ></i>
					  <i class="fa fa-long-arrow-down" aria-hidden="true"  title='小到大'></i>
					</a>
				  </td>
				  <td title='群組'	>群組</td>
				  <td title='單位'	>單位</td>
				  <td title='帳號'	>帳號</td>
			      <td title='姓名'	>姓名</td>
				  <td title='電話'	>電話</td>
				  <td title='註冊時間'	>
				    註冊時間
					<a class = 'option order_by' 
					   order = 'date_register'  
					   name  = '註冊時間' 
					   mode  = '<?php echo isset($html_conf['orderby']['field']) && $html_conf['orderby']['field']=='date_register' ? $html_conf['orderby']['mode'] : '0' ?>' 
					>
					  <i class="fa fa-sort" aria-hidden="true"  title='可排序' ></i>
                      <i class="fa fa-long-arrow-up" aria-hidden="true" title='進到遠' ></i>
					  <i class="fa fa-long-arrow-down" aria-hidden="true"  title='遠到進'></i>
					</a>
				  </td>
				  <td style='text-align:center;' > 狀態 </td>
				  <td style='text-align:center;' ><i class='sysbtn btn_plus' id='act_staff_new' title='新增群組帳號'> + </i> </td>
			    </tr>
			    <tbody class='data_result' mode='list' >   <!-- list / search--> 
			    <?php foreach($data_list as  $data): ?>  
			      <tr class='data_record ' no='<?php echo intval($data['uno']);?>' page='' >
                    <td filed='@selecter'  ><input type='checkbox' value='<?php echo $data['uno']; ?>' class='account_selecter' /></td>
					<td field='uno'  	   ><?php echo $data['uno']; ?></td>
			        <td field='user_group' ><?php echo $data['user_group']; ?></td>
				    <td field='user_organ' ><?php echo $data['user_organ']; ?></td>
				    <td field='user_id'    ><?php echo $data['user_id']; ?></td>
				    <td field='user_name'  ><?php echo $data['user_name']; ?></td>
					<td field='user_tel'   ><?php echo $data['user_tel']; ?></td>
				    <td field='@date_register'  ><?php echo System_Helper::short_string_utf8($data['date_register'],11);?></td>
					<td ><i class='mark24 pic_account_status<?php echo $data['user_status'];?>' title='<?php echo $data['account_info']; ?>' ></i></td>
					<td title='讀取帳號資料' ><a class='option _data_read' class='act_read_data' ><i class="fa fa-user-circle" aria-hidden="true"></i></a></td>
				  </tr> 
			    <?php endforeach; ?>
			      <tr class='data_field'>
			        <td title='全選/全不選' ><input type='checkbox' value='_all' class='act_select_all' /></td>
				    <td title='編號'	>no.</td>
				    <td title='群組'	>群組</td>
				    <td title='單位'	>單位</td>
				    <td title='帳號'	>帳號</td>
			        <td title='姓名'	>姓名</td>
				    <td title='電話'	>電話</td>
					<td title='註冊時間'	>註冊時間</td>
				    <td title='狀態'	>狀態</td>
					<td title='編輯'	>編輯</td>
			      </tr> 
				</tbody>
				<tbody class='data_target'></tbody>
			  </table>
			  <div class='record_control'>
			    <span class='record_result'>  
			      共 <span> <?php echo $data_count; ?></span>  筆
				  / 顯示 <span> <?php echo $data_limit['range']; ?> </span>
				</span>
				<span class='record_pages'>
				  <a class='page_tap page_to' page='<?php echo $page_conf['prev'];?>' > &#171; </a>
				  <span class='page_select'>
				  <?php foreach($page_conf['list'] as $p=>$limit ): ?>
				  <a class="page_tap <?php echo $p==$page_conf['now'] ? 'page_now':'page_to'; ?>" page="<?php echo $limit;?>" ><?php echo $p; ?></a>
				  <?php endforeach; ?>
				  </span>
				  <a class='page_tap page_to' page='<?php echo $page_conf['next'];?>' > &#187; </a>
				  ，跳至
				  <select class='page_jump'>
				    <optgroup label="首尾頁">
					  <option value='<?php echo $page_conf['top'];?>' >首頁</option>
					  <option value='<?php echo $page_conf['end'];?>' >尾頁</option>
					</optgroup>
					<optgroup label="-">
					  <?php foreach($page_conf['all'] as $p=>$limit ): ?>
				      <option value="<?php echo $limit; ?>"  <?php echo $p==$page_conf['now'] ? 'selected':''; ?> ><?php echo 'P.'.$p; ?></option>
				      <?php endforeach; ?>
                    </optgroup>					  
				  </select>
				</span>
			  </div>
		    </div>
		  </div>
		  
		  <div class='data_record_block' id='record_editor'>
		    <div class='record_header'>
			  <span class='record_name'>帳號資料</span>
			  <span class='record_option'>
			    <i class='sysbtn' id='act_staff_save'><a class='btn_mark pic_save'  ></a></i>
			   ｜
			    <a class='sysbtn data_trival' id='act_record_prev' title='上一筆' ><i class="fa fa-angle-double-left" aria-hidden="true"></i></a>
			    <a class='sysbtn data_trival' id='act_record_next' title='下一筆'><i class="fa fa-angle-double-right" aria-hidden="true"></i></a>
                
				<a class='option view_switch' id='editor_switch' >  +  </a>
				<a class='option' id='editor_reform'  >  &times;  </a>
			  </span>
			</div> 
		    <div class='record_body tr_like' id='record_form_block'>  
			  <div class='form_block float_cell' id='meta_input'>
			    <div class='data_col '> <label class='data_field _necessary'> 登入帳號 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_id' default='readonly' /> </div> </div>
				<div class='data_col '> <label class='data_field _necessary'> 連絡信箱 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_mail' /> </div> </div>
			    <div class='data_col '> <label class='data_field '> 姓名 </label><div class='data_value mutile_fields'><input type='text' class='_variable _update' id='user_name' />  /  <input type='text' class='_variable _update' id='user_idno' placeholder='代號(非必填)' /></div> </div>
				<div class='data_col '> <label class='data_field '> 連絡電話 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_tel' /> </div> </div>
				<div class='data_col '> <label class='data_field '> 聯繫地址 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_address' /> </div> </div>
				<div class='fieldset '> 
				  <div class='data_col '> <label class='data_field '> 單位 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_organ' /> </div> </div>
				  <div class='data_col '> <label class='data_field '> 職稱/職業 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_staff' /> </div> </div>
				</div>  
               	<div class='fieldset '> 
				  <div class='data_col '> <label class='data_field '> 年齡 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_age' /> </div> </div>
				  <div class='data_col '> <label class='data_field '> 教育程度 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_education' /> </div> </div>
				</div>  
				<div class='data_col '> <label class='data_field '> 主修 </label><div class='data_value'> <input type='text' class='_variable _update' id='user_major' /> </div> </div>
			  </div>
			  <div class='form_block float_cell' id='status_input'>
			    
				<div class='data_col '> 
				  <label class='data_field _necessary'> 使用期限 </label>
				  <div class='data_value mutile_fields'> 
				    <input type='text' class='datetime _variable _update' id='date_open' /> - <input type='text' class='datetime _variable _update' id='date_access' />
				  </div>
				</div>
				<div class='data_col '> 
				  <label class='data_field'> IP限制 </label>
				  <div class='data_value'> <input type='text' class='_variable _update' id='ip_range' /></div>
				</div>
				<div class='data_col '> 
				  <label class='data_field'> 帳號群組 </label>
				  <div class='data_value'>  <span class='_variable' name='groups' id="main_group" >123</span>  </div> 
				</div>
				
				
				<?php if($admin_open || isset($ui_config['admin_staff.html5tpl.php']['roleset']) && intval($ui_config['admin_staff.html5tpl.php']['roleset'])): ?> 
				<div class='data_col' id='roleset'> 
				  <label class='data_field'> 帳號角色 </label>
				  <div class='data_value '> 
				  <?php if(!isset($module_config['roles'])): ?> 
				    <div  > 系統尚未進行角色設定 </div>
				  <?php else: ?> 
				  	<?php foreach($module_config['roles'] as $role) : ?>  
				    <div  >
					  <?php // 角色模式  單->radio 多->checkbox // 依需求客製化 ?>
					  <input type='checkbox' class='_variable _update'    name='roles'  value='<?php echo $role['rno'];?>' >  <?php echo $role['name'];?> : <i><?php echo $role['descrip'];?></i>
					</div>
				    <?php endforeach; ?>  
				  <?php endif; ?> 	
				  </div> 
				</div>
				<?php endif;?> 
				
				<div class='data_col '> 
				  <label class='data_field'> 加入群組 </label>
				  <div class='data_value'> <span class='_variable' name='groups' id="rela_group" ></span> -</div> 
				</div>
				
				<?php if($admin_open || isset($ui_config['admin_staff.html5tpl.php']['statusset']) && intval($ui_config['admin_staff.html5tpl.php']['statusset'])): ?> 
				<div class='data_col ' id='statusset'> 
				  <label class='data_field'> 帳號狀態 </label>
				  <div class='data_value'> 
				    <select class='_variable _update' id='user_status'>
					    <option value=''> - </option>
					    <option value='0'> 0. 帳號已關閉 </option>
					    <option value='1'> 1. 帳號審核中 </option>
					    <option value='2'> 2. 審核通過，待啟動帳號 </option>
					    <option value='3'> 3. 已發送啟動通知 </option>
					    <option value='4'> 4. 重新設定密碼 </option>
					    <option value='5'> 5. 帳號已開通 </option>
					</select>
				  </div> 
				</div>
				<?php endif;?> 
			    
				<div class='data_col  action_col'> 
				  <label class='data_field'> 帳號功能 </label>
				  <div class='data_value'> 
				    <select class='form_function _reset' id='execute_function_selecter' >
					  <option value=''> - </option>
					  <optgroup label='[ 發信功能 ]'>
					    <option value='startmail' > - 寄發帳號開通&密碼重設通知 </option>
					  </optgroup>
					  <optgroup class='_attention' label='[ 帳號功能 ]' >
					    <option value='dele'> - 刪除帳號 </option>
					  </optgroup>
					</select> 
				    <i class='sysbtn btn_activate' id='act_func_execute'> 執行 </i>
				  </div> 
				</div>
			  </div>
			</div>
		  </div>
		  
		</div>
	  </div>
	</div>
	
	
	<!-- plugin option : group member editor -->
	<?php if(isset($user_info['user']['user_roles']['R00']) || isset($user_info['user']['user_roles']['R01'])) :?>
	<div class='group_setting_area'>
	  <div class='setting_block' id='member_container'>
	    <div class='setter_header'>
		  <h1 >
            <span id='target_project_name' class=''  >群組成員設定</span>		  
		    <i    id='close_setter' class='option mark16 pic_close' ></i>
		  </h1>
		</div>
	    <div class='setter_content'>
		  <h2>選擇新增成員</h2>
          <div class='member_setting'>
		    <label>選擇目標帳號：</label>
			<select class='_setinit' id='group_members'>
			  <option value=''> - </option>	 	
			</select>
			，
			<input type='text' id='member_search' placeholder='搜尋帳號' />
		  </div>
		  <div class='member_setting'>
		    <?php if(!isset($module_config['roles'])): ?> 
			<span> 系統尚未進行角色設定 </span>
			<?php else: ?> 
			<?php  foreach($module_config['roles'] as $role) : ?>  
			<input type='checkbox' class='' name='add_role'  value='<?php echo $role['rno'];?>' > <label title='<?php echo $role['descrip'];?>' ><?php echo $role['rno'];?> <?php echo $role['name'];?>, </label>
			<?php  endforeach; ?>  
			<?php endif; ?> 
			<label>設定IP範圍:</label>
		    <input type='text' id='member_qualify' value='' placeholder='請輸入限制IP範圍' />
			<button type="button" class='active' id='act_addto_group' > 加入 </button> 
		  </div>
		  <h2>群組成員編輯</h2>
		  <div>
		    <select id='group_selecter'>
			  <optgroup label="系統群組" class='_setinit' id='group_queue' > </optgroup>
			  <option value='_new_group'> + 新增群組 + </option>	 	
		    </select>
			
			<label>編輯：</label>
			<input type='text' class='_setinit _gmeta' id='group_name'  placeholder='群組名稱:' />
			<input type='text' class='_setinit _gmeta' id='group_info'  placeholder='群組說明:' />
			<input type='text' class='_setinit _gmeta' id='group_code'  placeholder='代號:' readonly=true />
			<button id='act_dele_group' > <i class="fa fa-trash-o" aria-hidden="true"></i> </button>
			<button id='act_save_group' > <i class="fa fa-floppy-o" aria-hidden="true"></i> </button>
          </div>
		  <table id='member_tabel' >
		    <tr>
			  <th>帳號</th><th>姓名</th><th>角色</th><th>IP限制</th><th>退出</th>
			</tr>
		    <tbody id='member_list' class='_setinit'></tbody> 
          </table>		  
		</div>
	  </div>
	</div>
	<?php endif; ?> 
	
	
	<!-- 框架外結構  -->
	<div class='system_message_area'>
	  <div class='message_block'>
		<div id='message_container'>
		  <div class='msg_title'></div>
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
  
  </body>
</html>