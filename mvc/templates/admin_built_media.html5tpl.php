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
	
	
	<!-- jquery lazyload -->
	<script type="text/javascript" src="tool/lazy-load-xt-master/src/jquery.lazyloadxt.js"></script>
	
	<link type="text/css" href="tool/jquery.scrollbar/jquery.scrollbar.css" rel="stylesheet" />
	<script type="text/javascript" src="tool/jquery.scrollbar/jquery.scrollbar.min.js"></script>
	
	<!-- fabric canves library -->
	<script type="text/javascript" src="tool/fabric.min.js"></script>
	
	<script type="text/javascript" src="tool/jquery-mousewheel-3.1.13/jquery.mousewheel.min.js"></script>
	
	<!-- Self -->
	<link rel="stylesheet" type="text/css" href="theme/css/css_default.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_main.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_built_media_admin.css" />
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_admin.js"></script>
	
	<script type="text/javascript" src="js_meta_admin.js"></script>
	<script type="text/javascript" src="js_meta_media.js"></script>
	
	
	
	<!-- PHP -->
	<?php
	$user_info 		= isset($this->vars['server']['data']['user']) 		? $this->vars['server']['data']['user'] 	: array('user'=>array('user_name'=>'Anonymous'),'group'=>array());
	
	//$meta_collect  	= isset($this->vars['server']['data']['resouse']['meta_collection'])? $this->vars['server']['data']['resouse']['meta_collection'] : '[]';  
	$edit_mode  	= isset($this->vars['server']['data']['resouse']['edit_mode']) 		? $this->vars['server']['data']['resouse']['edit_mode'] : 'edit';  
	$edit_form  	= isset($this->vars['server']['data']['resouse']['form_mode']) 		? $this->vars['server']['data']['resouse']['form_mode'] : '檔案';  
	
	$dobj_conf  	= isset($this->vars['server']['data']['resouse']['dobj_config'])	? $this->vars['server']['data']['resouse']['dobj_config'] : array();  
	$elements       =  isset($this->vars['server']['data']['resouse']['meta_list']) 		? $this->vars['server']['data']['resouse']['meta_list'] : array(); 
	
	$user_project   = isset($this->vars['server']['data']['resouse']['user_project']) 		? $this->vars['server']['data']['resouse']['user_project'] : array();  
	
	//
	//$data_count 	= count($data_list);
	//$data_limit 	= isset($this->vars['server']['data']['record'])    ? $this->vars['server']['data']['record']['limit'] : array('start'=>date('Y-m-d',strtotime('-7 day')),'end'=>date('Y-m-d'));
	$page_info 		= isset($this->vars['server']['info']) ? $this->vars['server']['info'] : '';  
	
	$user_admin    = false;
	$user_roles    = '';
	foreach($user_info['group'] as $gset){
	  if($gset['now']){
		$user_roles =  join(',',$gset['roles']); 
	    if(isset($gset['roles']['R00']) ||  (isset($gset['roles']['R02']) && $gset['roles']['R02']==2 ) ){
		  $user_admin = true;	
		}
	  }
	}
	
	
	?>
	<meta id='DATAROOT' data-set='<?php echo $dobj_conf['root'];?>' ></meta>
	<meta id='DOFOLDER' data-set='<?php echo $dobj_conf['folder'];?>'  ></meta>
  </head>
  
  
  <body>
	<div class='system_main_area'>
	  <div class='system_manual_area'>
	  <?php include('area_admin_manual.php'); ?>
	  </div>
	  <div class='system_content_area wide_mode'>
        <div class='tool_banner' >
		  <ol id='system_breadcrumbs' typeof="BreadcrumbList" >
		  </ol>
		  <span class='account_option tool_right'></span>
		</div>
		
		<div class='main_content' >
		  
		  <!-- 影像顯示區 !多媒體型態無用 -->
		  <div class='data_dobj_continer'></div>
		  
		  <!-- 資料編輯區 -->
		  <div class='data_edit_continer'>	  
			  
			<!-- 列表 -->
			<div class='data_record_block' id='record_selecter'>
				<h1 class='record_header'>
				  <label class='record_title'>資料清單：<i class='_variable' id='META-BookName' ></i></label>
				  <span  class='record_info'> 
				    <?php echo count($elements); ?></i>  筆 ,
				    狀態:
				  </span>
				  <span  class='record_tasks'>  
					<!--
					<button type='button' class='cancel'  id='act_task_return' title='退回工作項目'> 退回 </button>
				    <button type='button' class='active'  id='act_task_checked' title='已確認建檔內容'> 確認 </button>
				    <button type='button' class='active'  id='act_task_finish' title='完成本次建檔工作'> 遞交 </button>
				    <button type='button' class=''  id='' title='遞交工作確認中' disabled > 已遞交 </button>
				    -->
				  </span>
				</h1> 
				<div class='record_body'>
				  <table class='record_list'>
					<tr class='data_field'>
					  <td title='no'	>no</td>
					  <td title='典藏號'>典藏號</td>
					  <td title='頁次'	align='center' >頁次</td>
					  <td title='密等'	align='center' >密</td>
					  <td title='個資'	align='center' >隱</td>
					  <td title='開放'	align='center' >開</td>
					  <td title='更新'	align='center' >更新</td>
					</tr>
					<tbody class='data_result' mode='list' >   <!-- list / search--> 
					<?php foreach($elements as $i=>$meta): ?>  
					  <tr class='data_record _data_read' no='<?php echo $meta['system_id'];?>' collection='<?php echo $meta['collection']; ?>' page='' >
					    <td field='_estatus'  id=<?php echo $meta['system_id'];?> > <?php echo $i;?>  </td>
						<td field='item_title' ><?php echo $meta['identifier']; ?></td>
						<td field='page_info'  ><?php echo $meta['_search']['pageinfo']?></td>
						<td field='_privacy'   ><?php echo $meta['_lockmode'];?></td>
						<td field='_auditint'   class='status_iconv'   _status=<?php echo $meta['_auditint'];?> ><i class="fa fa-check" aria-hidden="true"></i></td>
						<td field='_open'   	class='status_iconv'   _status=<?php echo $meta['_open'];?> ><i class="fa fa-check" aria-hidden="true"></i><i class="fa fa-ban" aria-hidden="true"></i></td>
						<td field='_update' title='<?php echo $meta['@time']; ?>' ><?php echo substr($meta['@time'],0,10); ?></td>
					  </tr> 
					<?php endforeach; ?>
					
					</tbody>
					<tbody class='data_target'></tbody>
				  </table>
				  
				</div>
				<div class='record_batch'>
				  <a class='option' id='act_task_downlaod' title='下載建檔目錄'> <i class="fa fa-download" aria-hidden="true"></i> 下載建檔目錄</a>
				  /
				  <a class='option' id='act_task_downlaod' title='更新建檔目錄'> <i class="fa fa-upload" aria-hidden="true"></i> 更新建檔目錄</a>
				</div>
			</div>
			  
			<!-- 編輯 -->  
			<div class='record_element'  id='metadata_editer' mode='' state='' last='' >
			  <div class='edit_function'>
				<span class='targetid'>
				  <i class='_variable' id='itemid'>資料序號</i>
				</span>
				<span class='switcher'>
				  <?php if(count($elements)>1):?>
				  <button class='item_switcher' data-mode='prev' >上一筆</button>
				  <button class='item_switcher' data-mode='next' >下一筆</button>
				  <?php endif; ?>
				</span>
				<span class='editfunc'>
				  
				  |
				  <!--
				  <button id='create_new_item' 		title='新增一筆' ><i class="fa fa-file-o" aria-hidden="true"></i></button>
				  <button id='import_arrange_meta'	title='帶入卷資料'><i class="fa fa-file-text-o" aria-hidden="true"></i></button>
				  <a class='option' title='完成編輯' id='finish_current_meta' ><i class="fa fa-check-square" aria-hidden="true"></i></a>
				  -->
				  <button id='save_current_meta'	title='儲存資料'><i class="fa fa-floppy-o" aria-hidden="true"></i></button>
				  <span class='close option' id='edit_close' ><i class="fa fa-times" aria-hidden="true"></i></span>
				</span>
				
			  </div>
			  <div class='edit_contents'>
			  
			  
				<div class='system_meta' >
				  <div class='data_col '> 
				    <label class='data_field _necessary'> 資料類型 </label>
					<div class='data_value' id='field_data_type'> 
					  <input type='text' class='' id=''  default='' readonly=true value='<?php echo $edit_form; ?>'/> 
					</div>
					<label class='data_field _necessary' style='text-align:right;'> 典藏號 </label>
					<div class='data_value' id='field_storeno'>   
					  <input type='text' class='_variable _update' id='META-StoreNo' default='' readonly=true />
					</div>
				  </div>
				  <div class='data_col '> 
				    <label class='data_field _necessary'> 參數設定 </label>
					<div class='data_config'> 
					  <div class='data_col '> 
					    <label>密件</label>
						<div class='data_value'>   
					      <label class="switch">
						    <input type="checkbox" class="boolean_switch _update" name="META-_flag_secret"  id='META-_flag_secret' data-save="1" data-default="0" checked="">
						    <div class="slider round"></div>
						  </label>  
					    </div>
					  </div>
					  <div class='data_col '> 
					    <label>隱私</label>
						<div class='data_value'>   
					      <label class="switch">
						    <input type="checkbox" class="boolean_switch _update" name="META-_flag_privacy"  id='META-_flag_privacy' data-save="1" data-default="0" checked="">
						    <div class="slider round"></div>
						  </label>  
					    </div>
					  </div>
					  <div class='data_col ' title='開放檢索'> 
					    <label>檢索</label>
						<div class='data_value'>   
					      <label class="switch">
						    <input type="checkbox" class="boolean_switch _update" name="META-_flag_open" id='META-_flag_open' data-save="1" data-default="0" checked="">
						    <div class="slider round"></div>
						  </label>  
					    </div>
					  </div>
					  <div class='data_col '> 
					    <label>數位檔案閱覽設定</label>
						<div class='data_value'>   
					      <select id='META-_view' class='_variable _update'>
						    <option value='開放' selected>開放 - 開放大眾閱覽</option>
							<option value='限閱'>限閱 - 限註冊帳號閱覽</option>
							<option value='會內'>會內 - 限註冊帳號會內閱覽 </option>
							<option value='不開放'>不開放 - 不開放讀取數位檔案 </option>
						  </select>  
					    </div>
					  </div>
					</div>
				  </div>
				</div>  
				
				<?php if($edit_form=='議事影音' ): ?>	
 				<div class='system_meta' >
				  <div class='data_col desc_col'> 
				    <label class='data_field _necessary'>會議資訊</label>
					<div class='data_value'> 
					  <select class='_variable _update' id='META-record_id' >
					    <option value='001'>001 臺灣省參議會</option>
						<option value='002'>002 臺灣省臨時省議會</option>
						<option value='003' selected>003 臺灣省議會</option>
						<option value='004'>004 臺灣省諮議會</option>
					  </select>
					</div>
					<i class='desc'>第</i>
					<div class='data_value' id='Period'><input type='text' class='_variable _update' id='META-record_period'  default=''  value='' placeholder='屆次'  /></div>
				    <i class='desc'>屆</i>
				  </div>
				  <div class='data_col desc_col' >
				    <label class='data_field _necessary'>大會資訊</label>
					<i class='desc'>第</i>
					<div class='data_value' id='ConfNo'> 
					  <input type='text' class='_variable _update' id='META-record_conf_typeno'  default=''  value=''  placeholder='大會別'  /> 
					</div>
					<i class='desc'>次</i>
					<div class='data_value' id='ConfType'> 
					  <select class='_variable _update' id='META-record_conf_type' >
					       <option value='OA' selected>OA 定期</option>
						  <option value='EA'>EA 臨時 </option>
						  <option value='IA'>IA 成立</option>
					 </select>
					</div>
					<i class='desc'>大會</i>，<i class='desc'>第</i>
					<div class='data_value'>   
				      <input type='text' class='_variable _update' id='META-record_conf_order'  default=''  value=''  placeholder='會次'  /> 
					</div>
					<i class='desc'>次會議</i>
				  </div>
				  <div class='data_col '>
				    <label class='data_field _necessary'>類號</label>
					<div class='data_value'> 
					  <input type='text' class='_variable _update' id='META-record_conf_no'  default=''  value=''  placeholder='類號'  /> 
					</div>
					<label class='data_field _necessary'>案號</label>
					<div class='data_value'> 
					  <input type='text' class='_variable _update' id='META-record_conf_seq'  default=''  value=''  placeholder='案號'  /> 
					</div>
				  </div>
				  
				  <div class='data_col '> 
					<label class='data_field _necessary'> 紀錄日期 </label>
					<div class='data_value'> 
					  <input type='text' class='_variable _update _date_input' id='META-record_date' default='' />
					</div>
                    <label class='data_field _necessary'> 內容語言 </label>
					<div class='data_value'> 
					  <input type='text' class='_variable _update _date_input' id='META-record_language' default='' />
					</div>
                    					
				  </div>				  
				</div>
				
				<div class='data_col' id='meta_group_banner' > 
				  <label class='data_field '> 詮釋資料編輯 </label>
				  <ul id='meta_group_switch'>
				    <li class='meta_group_sel option' data-group='_all' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 顯示全部</li>
				    <li class='meta_group_sel option _atthis' data-group='mcontent' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 內容欄位</li>
				    <li class='meta_group_sel option' data-group='mmanage' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 管理欄位 </li>
					<li class='meta_group_sel option' data-group='msegment' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 標註欄位 </li>
				  </ul>	
				</div>
				<ul class='search_meta' id='meta_group_container'>
				  <li class='meta_group_block _display' id='mcontent' >
				    <div class='mutile_col'>  
					  <div class='data_col ' id='col_chairman'> <label class='data_field '> 主席 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_chairman' default='' /> </div> </div>
					  <div class='data_col ' id='col_mamber_main' > <label class='data_field '> 議員 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_members' default='' /> </div> </div>
					</div>    
					<div class='data_col '> <label class='data_field '> 主要機關 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_organ' default='' /> </div> </div>
				  	<div class='data_col '> <label class='data_field '> 內容案由 </label><div class='data_value'> <textarea type='text' class='_variable _update _media' id='META-record_reason' default='' ></textarea> </div> </div>
					<div class='data_col '> <label class='data_field '> 關鍵字詞 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_keyword' default='' /> </div> </div>
				    <div class='data_col '> <label class='data_field '> 備註 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_remark' default='' /> </div> </div>
				  </li>	  
				  <li class='meta_group_block ' id='mmanage' >	  
					<div class='mutile_col'>  
					  <div class='data_col '> <label class='data_field '> 原件與否 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_original' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 取得方式 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_track' default='' /> </div> </div>
				    </div>
					<div class='mutile_col'>  
					  <div class='data_col '> <label class='data_field '> 保存狀況 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_status' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 存放地點 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_place' default='' /> </div> </div>
				    </div>
					<div class='mutile_col'>  
					  <div class='data_col '> <label class='data_field '> 類型 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_type' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 色彩 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_color' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 聲音 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_source' default='' /> </div> </div>
				    </div>
					<div class='data_col '> <label class='data_field '> 光碟編號 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-record_original_filename' default='' /> </div> </div>			
				  
				  </li>	 
                  
                  <li class='meta_group_block ' id='msegment' >	  
					<div class='mutile_col'>  
					  <div class='data_col '> <label class='data_field '> 檔名 </label><div class='data_value'> <input type='text' class='_variable ' id='DOBJ-file' readonly  /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 起始時間 </label><div class='data_value'> <input type='text' class='_variable ' id='DOBJ-stime' readonly/> </div> </div>
					  <div class='data_col '> <label class='data_field '> 結束時間 </label><div class='data_value'> <input type='text' class='_variable ' id='DOBJ-etime' readonly /> </div> </div>
				    </div>
				  </li>	 
 				  
				</ul>
				
			  <?php endif; ?>			  
			  	
				<div class='arrange_meta'>
				  <div class='data_col '> <label class='data_field'> 其他 </label><div class='data_value'> <input type='text' class='_variable ' id='' default='' /> </div> </div>			  
				</div>
				
			  </div>
			  
			  <div class='edit_information'>
				<div class='edit_conf' >
				  <div class='advance_conf' >
				    <a class='option' title='刪除資料' id='delete_current_meta' ><i class="fa fa-trash-o" aria-hidden="true"></i>  刪除資料 </a>
					<a class='option' title='關閉設定' id='act_close_setting' ><i class="fa fa-times" aria-hidden="true"></i></a>
				  </div>
				  <a class='option' title='進階設定' id='act_editor_setting' ><i class="fa fa-cog" aria-hidden="true"></i></a>
				</div>
				<div class='edit_logs' >
				  紀錄：
				  <span class='_variable' id='META-_timeupdate' ></span> 
				  by 
				  <span class='_variable' id='META-_userupdate' ></span> 
				</div>
			  </div>
			</div>
		  </div>
		  
		   <!-- 資料編輯區 -->
		  <div class='media_edit_continer'>	  
		    <div class='media_display_block'>
			  <div class='media_platform'>
			    <video  id='meta_media_tv'  preload='none' controls   >
				  <source type='video/mp4' id='meta_media_play' src='' />
				</video>
				<div class='mcontroler'>
				  <div class='video_control'>
				    <button class='act_video_time_control' data-second='-1' ><i class="fa fa-backward" aria-hidden="true"></i></button>
				    <button class='act_video_time_control' data-second='1' ><i class="fa fa-forward" aria-hidden="true"></i></button> 
				  </div>
				  <div class='project_save' id='adfile-project' >
				    <select  name='file_save_package' id='file_save_package' placeholder='打包檔名'  />
					  <option value='' disabled selected >專案資料夾 </option>
					<?php foreach($user_project as $spno=>$upoj):?>
					  <option value='<?php echo $spno;?>'> <?php echo $upoj['name'].' ( '.$upoj['count'].' )'; ?> </option>
					<?php endforeach;?>
					</select>
					<input type='input'  class='act_video_time_get' id='pjimport_stime'  placeholder='起' readonly />
					<input type='input'  class='act_video_time_get' id='pjimport_etime'  placeholder='迄' readonly />
					<button class='blue' id='act_adfile_package'>匯入</button>
				  </div>
				</div>
			  </div>
			  
			  <ul class='media_queue_block'>
			    <?php $i=1; ?>
				<?php foreach($dobj_conf['files'] as $i => $file):?>
				<li class='mfile' 
				    id='<?php echo $file['file'];?>'
				    data-file='<?php echo $file['file'];?>' 
					data-vw=<?php echo $file['width'];?> 
                    data-vh=<?php echo $file['height'];?> 
                    data-vl=<?php echo $file['length'];?>
				>
				  <div class='mframe'>
					<img src="thumb.php?src=<?php echo $dobj_conf['root'].'thumb/'.$dobj_conf['folder'].'/'.$file['thumb']; ?>"  /> 
				  </div>
				  <div class='mmeta'>
					<span class='fname'> <?php echo $file['file'];?></span>
					<span class='ftime'> <?php echo $file['duration'];?></span>	
				  </div>
				</li>
				<?php endforeach;?>
			  </ul> 
			</div>
		    
			<div class='media_meta_block'>
			  <h1>
			    
				<table class='meta_duration_list' >
				  <tr class='tags_field'>
					<td class='tag_num'>no.</td>
					<td class='tag_thumb' >截圖</td>
					<td class='tag_fname' >檔名</td>
					<td class='tag_time' >起始時間</td>
					<td class='tag_time' >結束時間</td>
					<td class='tag_func' >功能 <button id='act_create_segment' title='新增區段' ><i class="fa fa-plus" aria-hidden="true"></i></button></td>
				  </tr>	
			    </table>
			  </h1>
			  <div>
				  <canvas id='video_screenshot' ></canvas>
				  <table class='meta_duration_list' id='meta_media_tags' >
					<tbody id='meta_tag_template'>
					  <tr class='tag_record' src='' edit='0' play='0' stime='' etime='' fail=0 >
						<td class='tag_num' >1.</td>
						<td class='tag_thumb' >
						  <img class='tti stime' src=' '>
						  <img class='tti etime' src=' '>
						</td>
						<td class='tag_fname' >檔名</td>
						<td class='tag_time' ><input type='text' class='pointer stime' value='' readonly  /> <a class='act_set_time option'><i class="fa fa-tag" aria-hidden="true"></i></a></td>
						<td class='tag_time' ><input type='text' class='pointer etime' value='' readonly  /> <a class='act_set_time option'><i class="fa fa-tag" aria-hidden="true"></i></a></td>
						<td class='tag_func' >
						  <button type='button' class='cancel segment_dele' disabled ><i class="fa fa-trash" aria-hidden="true"></i></button>
						  <button type='button' class='active segment_edit'><i class="fa fa-pencil" aria-hidden="true"></i><i class="fa fa-floppy-o" aria-hidden="true"></i></button>
						  <button type='button' class='active segment_play'><i class="fa fa-play" aria-hidden="true"></i><i class="fa fa-pause" aria-hidden="true"></i></button>
						</td>
					  </tr>
					</tbody>
					<tbody id='meta_tags_queue'>
					</tbody>
					<tr class='segment_summary'>
					  <td colspan=3></td>
					</tr>
				  </table>
			  </div>
			</div>
		  </div>
		  
		  
		  <!-- 影像控制  -->
		  <div id='main_page_loading'>
		    <span>
			<?xml version="1.0" encoding="utf-8"?><svg width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-spin"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><g transform="translate(50 50)"><g transform="rotate(0) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(45) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.12s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.12s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(90) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.25s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.25s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(135) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.37s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.37s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(180) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.5s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.5s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(225) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.62s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.62s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(270) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.75s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.75s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g><g transform="rotate(315) translate(34 0)"><circle cx="0" cy="0" r="8" fill="#709a4e"><animate attributeName="opacity" from="1" to="0.1" begin="0.87s" dur="1s" repeatCount="indefinite"></animate><animateTransform attributeName="transform" type="scale" from="1.5" to="1" begin="0.87s" dur="1s" repeatCount="indefinite"></animateTransform></circle></g></g></svg>
		    </span>
		  </div>
		  
		  <!-- 資料儲存 -->
		  <data  id='taskid' data-refer='<?php //echo $meta_task['task_no']?>'></data>
		  <data  id='collection_meta' data-refer='<?php //echo $meta_collect;?>' ></data>
		  
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
  
  </body>
</html>