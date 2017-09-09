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
	
	<!-- froala editer-->
	<link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<link href="tool/froala_editor/css/froala_editor.min.css" rel="stylesheet" type="text/css" />
    <link href="tool/froala_editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
	<!-- Include Editor Plugins style. -->
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/char_counter.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/code_view.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/colors.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/file.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/fullscreen.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/image.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/image_manager.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/line_breaker.css">
    <link rel="stylesheet" href="tool/froala_editor/css/plugins/table.css">
	
	<script type="text/javascript" src="tool/froala_editor/js/froala_editor.min.js"></script>
	<script type="text/javascript" src="tool/froala_editor/js/froala_editor.pkgd.min.js"></script>
	<!-- Include Language file if we'll use it. -->
    <script type="text/javascript" src="tool/froala_editor/js/languages/zh_tw.js"></script>
	
	
	<script type="text/javascript" src="tool/jquery-mousewheel-3.1.13/jquery.mousewheel.min.js"></script>
	
	<!-- Self -->
	<link rel="stylesheet" type="text/css" href="theme/css/css_default.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_main.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_built_biography_admin.css" />
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_admin.js"></script>
	
	<script type="text/javascript" src="js_meta_admin.js"></script>
	<script type="text/javascript" src="js_meta_biography.js"></script>
	
	
	
	<!-- PHP -->
	<?php
	$user_info 		= isset($this->vars['server']['data']['user']) 		? $this->vars['server']['data']['user'] 	: array('user'=>array('user_name'=>'Anonymous'),'group'=>array());
	
	//$meta_collect  	= isset($this->vars['server']['data']['resouse']['meta_collection'])? $this->vars['server']['data']['resouse']['meta_collection'] : '[]';  
	$edit_mode  	= isset($this->vars['server']['data']['resouse']['edit_mode']) 		? $this->vars['server']['data']['resouse']['edit_mode'] : 'edit';  
	$edit_form  	= isset($this->vars['server']['data']['resouse']['form_mode']) 		? $this->vars['server']['data']['resouse']['form_mode'] : '檔案';  
	
	$dobj_conf  	= isset($this->vars['server']['data']['resouse']['dobj_config'])	? $this->vars['server']['data']['resouse']['dobj_config'] : array();  
	$elements       =  isset($this->vars['server']['data']['resouse']['meta_list']) 		? $this->vars['server']['data']['resouse']['meta_list'] : array(); 
	
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
				<div class='record_header'>
				  <span class='record_name'>建檔清單</span>
				  <span class='record_option'>
				 
				    <button type='button' class='cancel'  id='act_task_return' title='退回工作項目'> 退回 </button>
				    <button type='button' class='active'  id='act_task_checked' title='已確認建檔內容'> 確認 </button>
				    <button type='button' class='active'  id='act_task_finish' title='完成本次建檔工作'> 遞交 </button>
				    <button type='button' class=''  id='' title='遞交工作確認中' disabled > 已遞交 </button>
				 	<a class='option view_switch' >  −  </a>
				  </span>
				</div> 
				<div class='record_body'>
				  <div class='record_control'>
					<span class='record_result'>  
					  共 <span> <?php echo count($elements); ?></span>  筆 /
					  <a class='option' id='act_task_downlaod' title='下載建檔目錄'> <i class="fa fa-download" aria-hidden="true"></i> 下載建檔目錄</a>
					</span>
					<span class='record_function'>
					</span>
				  </div>
				  <table class='record_list'>
					<tr class='data_field'>
					  <td title='no'	>no</td>
					  <td title='典藏號'>議員姓名</td>
					  <td title='生卒'	align='center' >生卒</td>
					  <td title='密等'	align='center' >密</td>
					  <td title='個資'	align='center' >隱</td>
					  <td title='開放'	align='center' >開</td>
					  <td title='更新'	align='center' >更新</td>
					</tr>
					<tbody class='data_result' mode='list' >   <!-- list / search--> 
					<?php foreach($elements as $i=>$meta): ?>  
					  <tr class='data_record _data_read' no='<?php echo $meta['system_id'];?>' page='' >
					    <td field='_estatus'  id=<?php echo $meta['system_id'];?> > <?php echo ++$i;?>  </td>
						<td field='item_title' ><?php echo $meta['identifier']; ?></td>
						<td field='page_info'  ><?php echo $meta['_search']['date_string']?></td>
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
				  
				  <button id='save_current_meta'	title='儲存資料'><i class="fa fa-floppy-o" aria-hidden="true"></i></button>
				  |
				  <button id='create_new_item' 		title='新增一筆' ><i class="fa fa-file-o" aria-hidden="true"></i></button>
				  <button id='import_arrange_meta'	title='帶入卷資料'><i class="fa fa-file-text-o" aria-hidden="true"></i></button>
				  
				</span>
				<span class='close option' id='edit_close' ><i class="fa fa-times" aria-hidden="true"></i></span>
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
					  <input type='text' class='_variable _update' id='META-mbrno' default='' readonly=true />
					</div>
				  </div>
				  <div class='data_col '> 
				    <label class='data_field _necessary'> 參數設定 </label>
					<div class='data_config'> 
					  <div class='data_col '> 
					    <label>開放</label>
						<div class='data_value'>   
					      <label class="switch">
						    <input type="checkbox" class="boolean_switch _update" name="META-_flag_open" id='META-_flag_open' data-save="1" data-default="0" checked="">
						    <div class="slider round"></div>
						  </label>  
					    </div>
					  </div>
					  <div class='data_col '> 
					    <label>傳記閱覽設定</label>
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
				
			  <?php if($edit_form=='議員傳記'): ?>	
                <div class='search_meta' >
				  <div class='mutile_col'>
				      <div class='data_col '> 
						<label class='data_field _necessary'> 議員姓名 </label>
						<div class='data_value'> <input type='text' class='_variable _update' id='META-mbr_name'    default=''  value='' placeholder='議員姓名' /> </div>
					  </div>
					  <div class='data_col '> 
						<label class='data_field _necessary'> 生卒年份 </label>
						<div class='data_value '> <input type='text' class='_variable _update' id='META-mbr_time'    default=''  value='' placeholder='生卒年份' /> </div> 
					  </div>
                  </div>
				  <div class='data_col '> <label class='data_field _necessary'> 議員任職 </label><div class='data_value'> <textarea type='text' class='_variable _update _photo' id='META-mbr_offer' default='' ></textarea> </div> </div>
				  <div class='data_col '> <label class='data_field '> 參考資料 </label><div class='data_value'> <textarea type='text' class='_variable _update _biography' id='META-mbr_refer' default='' ></textarea> </div> </div>				  
				</div>
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
				  <a class='option' title='完成編輯' id='finish_current_meta' ><i class="fa fa-check-square" aria-hidden="true"></i></a>
				</div>
				<div class='edit_logs' >
				  紀錄：
				  <span class='_variable' id='_update' ></span> 
				  by 
				  <span class='_variable' id='_editor' ></span> 
				</div>
			  </div>
			</div>
		  </div>
		  
		   <!-- 資料編輯區 -->
		  <div class='biography_edit_continer'>	  
		    <h1>
			  <span>傳記內容編輯</span>
			</h1>
		    <div class='biography_container'>
			  <textarea type='text' class='_variable _update _biography' id='META-mbr_history' default='' ></textarea>
			</div>
		    <div class='editor_information'>
			  
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