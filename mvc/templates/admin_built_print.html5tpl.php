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
	<link rel="stylesheet" type="text/css" href="theme/css/css_built_admin.css" />
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_admin.js"></script>
	<script type="text/javascript" src="js_meta_admin.js"></script>
	<script type="text/javascript" src="js_meta_print.js"></script>
	
	
	
	
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
		  
		  <!-- 影像顯示區 -->
		  <div class='data_dobj_continer'>
		  </div>
		  
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
					  <td title='典藏號'>典藏號</td>
					  <td title='頁次'	align='center' >頁次</td>
					  <td title='密等'	align='center' >密</td>
					  <td title='個資'	align='center' >隱</td>
					  <td title='開放'	align='center' >開</td>
					  <td title='更新'	align='center' >更新</td>
					</tr>
					<tbody class='data_result' mode='list' >   <!-- list / search--> 
					<?php foreach($elements as $i=>$meta): ?>  
					  <tr class='data_record _data_read' no='<?php echo $meta['system_id'];?>' page='' >
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
				
				<?php if($edit_form=='議事錄' || $edit_form=='公報'): ?>	
 				<div class='system_meta' >
				  <div class='data_col '> 
				    <label class='data_field _necessary'> 卷冊資訊 </label>
					<div class='data_value'> <input type='text' class='_variable _update' id='META-BookNo'    default=''  value='' placeholder='卷/冊號' /> </div>
					<div class='data_value' id='BookNameField'> <input type='text' class='_variable _update' id='META-BookName'  default=''  value='' placeholder='卷/冊名'  /> </div>
					<div class='data_value' id='StageAndVolume' > 
					  卷:<input type='text' class='_variable _update' id='META-StageNum'  default=''  value=''  placeholder=''  /> 
					  期:<input type='text' class='_variable _update' id='META-VolumeNum'  default=''  value='' placeholder='' />
					</div>
				  </div>
				  
				  <div class='data_col '> 
				    <label class='data_field _necessary'> 頁碼與範圍 </label>
					<div class='data_value' id='doconf' > 
					  <span id='page_num_set' >
					    <input type='text' class='_variable _update page_num' id='META-PageStart' default='' /> -  
					    <input type='text' class='_variable _update page_num' id='META-PageEnd' default='' /> 
					    <span id='page_num_checked' check=0></span>
					    <span class='option' id='page_file_putin' title='帶入影像檔案' ><i class="fa fa-reply" aria-hidden="true"></i></span>
					    / 
					  </span>
					  <span id='page_file_set'> 
					    <input type='text' class='_variable _update' id='META-DobjFrom' default='' /> 
					    <a class='option' id='input_page_file_start' title='設定為目前影像' ><i class="fa fa-picture-o" aria-hidden="true"></i></a>
					    ~
						<input type='text' class='_variable _update' id='META-DobjEnd' default='' /> 
					    <a class='option' id='input_page_file_end'   title='設定為目前影像'><i class="fa fa-picture-o" aria-hidden="true"></i></a>
					  </span>	
					</div>  
				  </div>
				  <div class='data_col '> 
					<label class='data_field _necessary'> 時間範圍 </label>
					<div class='data_value mutile_fields'> 
					  <input type='text' class='_variable _update _date_input' id='META-DateStart' default='' />
					  <span class='option' id='copy_start_date'  title='複製開始時間'><i class="fa fa-share" aria-hidden="true"></i></span>
					  <input type='text' class='_variable _update _date_input' id='META-DateEnd' default='' />
					  <i class='infield_name' id='fds' >起</i>					  
					  <i class='infield_name' id='fde' >迄</i>
					</div> 
				  </div>				  
				</div>
				
				<div class='data_col' id='meta_group_banner' > 
				  <label class='data_field '> 詮釋資料編輯 </label>
				  <ul id='meta_group_switch'>
				    <li class='meta_group_sel option' data-group='_all' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 顯示全部</li>
				    <li class='meta_group_sel option _atthis' data-group='mcontent' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 主要欄位</li>
				    <li class='meta_group_sel option' data-group='mtarget' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 相關欄位 </li>
				    <li class='meta_group_sel option' data-group='mfulltext' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 全文內容</li>
				    <li class='meta_group_sel option' data-group='mterms' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 關聯詞組</li>
				  </ul>	
				</div>
				<ul class='search_meta' id='meta_group_container'>
				  <li class='meta_group_block _display' id='mcontent' >
				      <div class='data_col '> <label class='data_field _necessary'> 類別階層 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-CategoryLevel' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 會議階層 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-MeetingLevel' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 內容摘要 </label><div class='data_value'> <textarea type='text' class='_variable _update _archive' id='META-Abstract' default='' ></textarea> </div> </div>
					<div class='mutile_col'>  
					  <div class='data_col ' id='col_chairman'> <label class='data_field '> 主席 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Chairman' default='' /> </div> </div>
					  <div class='data_col ' id='col_mamber_main' > <label class='data_field '> 主要議員 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Member' default='' /> </div> </div>
					</div>    
					  <div class='data_col '> <label class='data_field '> 主要機關 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Organ' default='' /> </div> </div>
					  
				  </li>	  
				  <li class='meta_group_block ' id='mtarget' >	  
					<div class='data_col '> <label class='data_field '> 相關議員 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-MemberOther' default='' /> </div> </div>
					<div class='data_col '> <label class='data_field '> 相關機關 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-OrganOther' default='' /> </div> </div>
					<div class='mutile_col'>  
					  <div class='data_col '> <label class='data_field '> 請願人 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-PetitionMen' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 請願機關 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-PetitionOrgan' default='' /> </div> </div>
				    </div>
					<div class='data_col '> <label class='data_field '> 文號 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-DocNo' default='' /> </div> </div>
					<div class='data_col '> <label class='data_field '> 參照與備註 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Reference' default='' /> </div> </div>
				  </li>	  
				  <li class='meta_group_block' id='mfulltext' >	  
					  <div class='data_col '> <label class='data_field '> 內容全文 </label><div class='data_value'> <textarea type='text' class='_variable _update' id='META-FullTexts' default='' ></textarea> </div> </div>
				  </li>
                  <li class='meta_group_block' id='mterms' >				  
					  <div class='data_col '> <label class='data_field '> 相關人員 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-PQ_Pperson' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 相關單位 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-PQ_Organ' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 關鍵詞組 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-PQ_Keyword' default='' /> </div> </div>
				  </li>
				</ul>
				
				
              <?php elseif($edit_form=='檔案'): ?>	
                <div class='system_meta' >
				  <div class='data_col '> 
					<label class='data_field _necessary'> 時間範圍 </label>
					<div class='data_value mutile_fields'> 
					  <input type='text' class='_variable _update _date_input' id='META-DateStart' default='' />
					  <span class='option' id='copy_start_date'  title='複製開始時間'><i class="fa fa-share" aria-hidden="true"></i></span>
					  <input type='text' class='_variable _update _date_input' id='META-DateEnd' default='' />
					  <i class='infield_name' id='fds' >起</i>					  
					  <i class='infield_name' id='fde' >迄</i>
					</div> 
				  </div>
				  <div class='data_col '> 
					<label class='data_field'> 密等與解密 </label>
					<div class='data_value mutile_fields'> 
					  <input type='text' class='_variable _update _date_input' id='META-Secret' default='' /> /
					  <input type='text' class='_variable _update _date_input' id='META-SecretProcess' default='' />
					</div> 
					<label class='data_field'> 頁次 </label>
					<div class='data_value ' id='archive_field_pagecount'> 
					  <input type='text' class='_variable _update _date_input' id='META-PageCount' default='' />
					</div>
				  </div>				  
				</div>
				
				<div class='data_col' id='meta_group_banner' > 
				  <label class='data_field '> 詮釋資料編輯 </label>
				  <ul id='meta_group_switch'>
				    <li class='meta_group_sel option' data-group='_all' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 顯示全部</li>
				    <li class='meta_group_sel option _atthis' data-group='mcontent' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 主要欄位</li>
				    <li class='meta_group_sel option' data-group='mtarget' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> 相關欄位 </li>
				  </ul>	
				</div>
				<ul class='search_meta' id='meta_group_container'>
				  <li class='meta_group_block _display' id='mcontent' >
				      <div class='data_col '> <label class='data_field _necessary'> 類別階層 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-CategoryLevel' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 檔案摘要 </label><div class='data_value'> <textarea type='text' class='_variable _update _archive' id='META-Abstract' default='' ></textarea> </div> </div>
					  <div class='data_col '> <label class='data_field '> 去隱私摘要 </label><div class='data_value'> <textarea type='text' class='_variable _update _archive' id='META-AbstractMask' default='' ></textarea> </div> </div>
					<div class='mutile_col'>  
					  <div class='data_col '> <label class='data_field '> 主要議員 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Member' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 主要機關 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Organ' default='' /> </div> </div>
					</div>  
					<div class='mutile_col'> 
					  <div class='data_col '> <label class='data_field '> 相關議員 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-MemberOther' default='' /> </div> </div>
					  <div class='data_col '> <label class='data_field '> 相關機關 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-OrganOther' default='' /> </div> </div>
				    </div>  
					  <div class='data_col '> <label class='data_field '> 文號 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-DocNo' default='' /> </div> </div>
				  </li>	  
				  <li class='meta_group_block ' id='mtarget' >	  
					<div class='data_col '> <label class='data_field '> 主題條目 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Subject' default='' /> </div> </div>
					<div class='data_col '> <label class='data_field '> 相關地點 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Location' default='' /> </div> </div>
					<div class='data_col '> <label class='data_field '> 參照 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Reference' default='' /> </div> </div>
				    <div class='data_col '> <label class='data_field '> 備註 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Temp' default='' /> </div> </div>					
				  </li>	  
				</ul> 

			  
			  <?php elseif($edit_form=='議事影音'): ?>			  
			  <?php elseif($edit_form=='活動照片'): ?>	
                <div class='system_meta' >
				  
				  <div class='data_col '> 
				    <label class='data_field _necessary'> 資料夾編號 </label>
					<div class='data_value'> <input type='text' class='_variable _update' id='META-FolderNo'    default=''  value='' placeholder='資料夾編號' /> </div>
					<label class='data_field _necessary'> 主題編號 </label>
					<div class='data_value'> <input type='text' class='_variable _update' id='META-SubjectNo'    default=''  value='' placeholder='主題編號' /> </div>
					<label class='data_field _necessary'> 照片編號 </label>
					<div class='data_value'> <input type='text' class='_variable _update' id='META-PhotoNo'    default=''  value='' placeholder='照片編號' /> </div>
				  </div>
				  
				  <div class='data_col '> 
					<label class='data_field _necessary'> 時間範圍 </label>
					<div class='data_value mutile_fields'> 
					  <input type='text' class='_variable _update _date_input' id='META-DateStart' default='' />
					  <span class='option' id='copy_start_date'  title='複製開始時間'><i class="fa fa-share" aria-hidden="true"></i></span>
					  <input type='text' class='_variable _update _date_input' id='META-DateEnd' default='' />
					  <i class='infield_name' id='fds' >起</i>					  
					  <i class='infield_name' id='fde' >迄</i>
					</div> 
				  </div>
                  <div class='data_col '> <label class='data_field _necessary'> 照片主題 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Subject' default='' /> </div> </div>
				  <div class='data_col '> <label class='data_field '> 內容描述 </label><div class='data_value'> <textarea type='text' class='_variable _update _photo' id='META-Descrip' default='' ></textarea> </div> </div>
				  <div class='data_col '> <label class='data_field '> 相關地點 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-PhotoLocation' default='' /> </div> </div>
				  <div class='data_col '> <label class='data_field '> 備註 </label><div class='data_value'> <input type='text' class='_variable _update' id='META-Note' default='' /> </div> </div>
				  <div class='data_col '> 
				    <label>識別備註</label>
				    <div class='data_value'>   
					  <select id='META-Identify' class='_variable _update'>
						<option value='' selected>尚未處理</option>
						<option value='全部識別' >全部識別</option>
						<option value='部分識別'>部分識別</option>
						<option value='無法識別'>無法識別</option>
					  </select>  
				    </div>
				  </div>				  
				</div>
				
					  
			  <?php elseif($edit_form=='議員傳記'): ?>			  
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
		  
		  <!-- 影像控制 -->
		  <div class='dobj_control_block' data-root='<?php echo $dobj_conf['root'];?>' data-folder='<?php echo $dobj_conf['folder'];?>' >
		    <span>
			  <input type='checkbox' id='act_dobj_edit_flag' />編輯
			</span>
			<span class='option page_mask'  id='act_add_mask' title='插入遮罩' ><i class="fa fa-clone" aria-hidden="true"></i></span>
			<span class='option page_mask'  id='act_del_mask' title='刪除遮罩' ><i class="fa fa-trash-o" aria-hidden="true"></i></span>
			<span class='option page_view'  id='act_switch_view' title='影像開關' display=1 ><i class="fa fa-eye" aria-hidden="true"></i><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
			<div class='pagernscale'>
			  <select class='page_selecter' scale='1' >
			    <option value='' disabled selected> 數位圖檔 </option>
			    <?php foreach($dobj_conf['files'] as $i => $file_name):?>
			    <option class='pager' id='<?php echo $file_name;?>' value='<?php echo $file_name;?>' data-serial=<?php echo $i; ?> display=1 >P.<?php echo ($i+1);?> /  <?php echo $file_name;?> </option>
			    <?php endforeach;?>
			  </select>
			  <span class='page_scale' >
			    <span class='scale_waper'>
			      <input id='scale_set' type='range' min="70" max="300" value='100' step="10" />
			      <span  id='scale_info' >1.0</span>
			    </span>
			  </span>
			</div>
			<span class='option page_switch' mode='prev' title='前一頁' ><i class="fa fa-chevron-left" aria-hidden="true"></i></span>
			<span class='option page_switch' mode='next' title='後一頁' ><i class="fa fa-chevron-right" aria-hidden="true"></i></span>
			
		  </div>
		  
		  
		  <!-- 影像縮圖 -->
		  <div class='dobj_thumb_block'>
		    <?php $i=1; ?>
			<?php foreach($dobj_conf['files'] as $i => $file):?>
			<div class='thumb' p='<?php echo $file;?>'  >
			  <img data-src="thumb.php?src=<?php echo $dobj_conf['root'].'thumb/'.$dobj_conf['folder'].'/'.$file; ?>"  /> 
			  <i>P.<?php echo ++$i;?></i>
			</div>
			<?php endforeach;?>
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