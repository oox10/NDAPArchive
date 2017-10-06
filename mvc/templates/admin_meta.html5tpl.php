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
	<link rel="stylesheet" type="text/css" href="theme/css/css_meta_admin.css" />
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_admin.js"></script>
	<script type="text/javascript" src="js_meta_admin.js"></script>
	
	
	<!-- PHP -->
	<?php
	$user_info 		= isset($this->vars['server']['data']['user']) 		? $this->vars['server']['data']['user'] 	: array('user'=>array('user_name'=>'Anonymous'),'group'=>array());
	
	$data_filter    = isset($this->vars['server']['data']['filter']['submit']) 	? $this->vars['server']['data']['filter']['submit'] : array('zongs'=> ["檔案","公報","議事錄","議事影音","議員傳記","活動照片"],'limit'=>'' );  
	$data_termhit   = isset($this->vars['server']['data']['filter']['termhit']) 	? $this->vars['server']['data']['filter']['termhit'] : array();  
	
	$data_list  	= isset($this->vars['server']['data']['search']['list']) 	? $this->vars['server']['data']['search']['list'] : array();  
	
	$data_count 	= isset($this->vars['server']['data']['search']['count']) 	? $this->vars['server']['data']['search']['count'] : 0;  
	$data_pageing 	= isset($this->vars['server']['data']['search']['range'])    ? $this->vars['server']['data']['search']['range'] : '1-50';
	$data_start 	= isset($this->vars['server']['data']['search']['start'])    ? $this->vars['server']['data']['search']['start'] : 1;
	
	$page_conf  	= isset($this->vars['server']['data']['page'])    ? $this->vars['server']['data']['page'] : array();
	
	$module_info 		= isset($this->vars['server']['info']) ? $this->vars['server']['info'] : '';
    
    $meta_edit_flag = intval($user_info['user']['user_roles']['R00']) || intval($user_info['user']['user_roles']['R02']) ? 1 : 0;     // 是否可修改資料
	
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
		    <div class='topic_title'> 資料管理模組 </div>
			<div class='topic_descrip'> 詮釋資料編輯與數位檔案管理 </div>
		  </div>
		  <div class='module_filter'>
		    <div class='filter_query'>
			  	<div class='filter_search_block'>
				  <input type='text' id='filter_search_terms'  value='<?php echo isset($data_filter['search']['condition']) ? $data_filter['search']['condition']:''; ?>' placeholder='輸入搜尋關鍵字' /> 
				  <span class='input_date' ><input type='text' id='filter_date_start' placeholder='日期-起' size='10' value='<?php echo isset($data_filter['search']['date_start']) ? $date_filter['search']['date_start'] : '';  ?>' /><i class="fa fa-calendar" aria-hidden="true"></i></span>
				  <span class='input_date' ><input type='text' id='filter_date_end'   placeholder='日期-迄' size='10' value='<?php echo isset($data_filter['search']['date_end']) ? $date_filter['search']['date_end'] : '';  ?>' /><i class="fa fa-calendar" aria-hidden="true"></i></span> 
				</div>
				<span class='filter_option' >  
				  <button id='filter_submit'  type='button' class='active'><i class="fa fa-search" aria-hidden="true"></i> 篩選 </button> 
				  <a id='reset_filter' class='option' > <i class="fa fa-refresh" aria-hidden="true"></i> 清空條件</a>
				</span>
			</div>
			<ul class='filter_set'>
			  <li>
			    <label id='act_select_all_zong'>全宗篩選：</label>
				<input type='checkbox' class='zselect' name='data_zong' value='檔案'	<?php echo  in_array('檔案',$data_filter['zongs']) ? 'checked':'';?> 	/> <span class='zname'>檔案</span>
				<input type='checkbox' class='zselect' name='data_zong' value='議事錄' 	<?php echo  in_array('議事錄',$data_filter['zongs']) ? 'checked':'';?>	/> <span class='zname'>議事錄</span>
				<input type='checkbox' class='zselect' name='data_zong' value='公報' 	<?php echo  in_array('公報',$data_filter['zongs']) ? 'checked':'';?>		/><span class='zname'>公報</span>
				<input type='checkbox' class='zselect' name='data_zong' value='議事影音' <?php echo in_array('議事影音',$data_filter['zongs']) ? 'checked':'';?>	/><span class='zname'>議事影音</span>
				<input type='checkbox' class='zselect' name='data_zong' value='議員傳記' <?php echo in_array('議員傳記',$data_filter['zongs']) ? 'checked':'';?>	/><span class='zname'>議員傳記</span>
				<input type='checkbox' class='zselect' name='data_zong' value='活動照片' <?php echo in_array('活動照片',$data_filter['zongs']) ? 'checked':'';?>	/><span class='zname'>活動照片</span>
			  </li>
			  <li>
			    <label>特殊篩選：</label>
				<input type='radio' class='mlimit' name='focus'  value='review' checked  >不限制, 
				<input type='radio' class='mlimit' name='focus'  value='secret'  <?php echo $data_filter['limit']=='secret' ? 'checked' : ''; ?> >密件, 
				<input type='radio' class='mlimit' name='focus'  value='privacy' <?php echo $data_filter['limit']=='privacy' ? 'checked' : ''; ?> >隱私, 
				<input type='radio' class='mlimit' name='focus'  value='mask'    <?php echo $data_filter['limit']=='mask' ? 'checked' : ''; ?> >遮頁,
                <input type='radio' class='mlimit' name='focus'  value='close'  <?php echo $data_filter['limit']=='close' ? 'checked' : ''; ?> >關閉,
			    <input type='radio' class='mlimit' name='focus'  value='update'  <?php echo $data_filter['limit']=='update' ? 'checked' : ''; ?> >最近更新
			  </li>
			
			</ul>
			
		  </div>
		  
		</div>
		
		<div class='main_content' >
		  <!-- 資料列表區 -->
		  <div class='data_record_block' id='record_selecter' >
		    <div class='record_header'>
			  <span class='record_name'>建檔清單</span>
			  <span class='record_option'>
			    批次設定：
				<select id='act_record_batch_to'>
				  <?php if($meta_edit_flag): ?>
				  <option value='' disabled selected >設定勾選資料</option>
				  <optgroup label='資料開關' >
				    <option value='open/0' title='檢索系統中找不到目前勾選的資料' >資料關閉</option>
				    <option value='open/1' title='檢索系統中可以查詢目前勾選的資料' >資料開啟</option>
				  </optgroup>
				  <optgroup label='數位檔案' >
				    <option value='view/開放' title='數位檔案開放於網路上閱覽' >開放閱覽</option>
				    <option value='view/限閱' title='數位檔案提供與登入帳號閱覽' >會員閱覽</option>
				    <option value='view/會內' title='數位檔案僅提供登入帳號並限制會內IP閱覽'>會內閱覽</option>
				    <option value='view/不開放' title='不提供數位檔案閱覽'>不開放閱覽</option>
				  </optgroup>
				  <optgroup label='同步平台' >
				    <option value='sync/1' title='同步於地方議會議事錄開放平台' disabled>從平台上架</option>
				    <option value='sync/0' title='於地方議會議事錄開放平台下架' disabled>從平台下架</option>
				  </optgroup>
				  <?php endif; ?>
				  
				  <optgroup label='匯出勾選' >
				    <option value='export' title='匯出勾選資料' >匯出excel</option>
				  </optgroup>
				</select>
				<button type='button' class='active' id='act_execute_batch'>執行</button>
			  </span>
			</div> 
			<div class='record_body'>
		      <div class='record_control'>
			    <span class='record_limit'>  
			      每頁 :
				  <select class='record_pageing' >
				    <option value='1-10'   <?php echo $data_pageing=='1-10'? 'selected':''; ?> > 10 </option>
				    <option value='1-100'   <?php echo $data_pageing=='1-100'? 'selected':''; ?> > 100 </option>
					<option value='1-500'  <?php echo $data_pageing=='1-500'? 'selected':''; ?> > 500 </option>
				    <option value='1-1000' <?php echo $data_pageing=='1-1000'? 'selected':''; ?> > 1000 </option>
				  </select> 筆
				  / 共 <span> <?php echo $data_count; ?></span>  筆
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
					  <?php foreach($page_conf['jump'] as $p=>$limit ): ?>
				      <option value="<?php echo $limit; ?>"  <?php echo $p==$page_conf['now'] ? 'selected':''; ?> ><?php echo 'P.'.$p; ?></option>
				      <?php endforeach; ?>
                    </optgroup>					  
				  </select>
				</span>
			  </div>
			  <table class='record_list' id='tasks_list'>
		        <tr class='data_field'>
				  <td title='no'		><input type='checkbox' class='act_select_all'  >no.</td>
				  <td title='資料'		>詮釋資料</td>
				  <td title='功能'		>功能選項</td>
			    </tr>
			    <tbody class='data_result' mode='list' >   <!-- list / search--> 
			    <?php foreach($data_list as $i=>$data): ?>  
			      <tr class='data_record' collection='<?php echo $data['_source']['collection']; ?>'  no='<?php echo $data['_id'];?>' status='' >
                    <td class='meta_no'><input type='checkbox' class='act_selector' value='<?php echo $data['_id'];?>'> <?php echo $i+$data_start;?> </td>
                    <td class='meta_content'>
					  <?php //處理搜尋標示 
					    
						$data_display = $data['_source'];
						$pattern = array();
						
						if(count($data_termhit)){    
						  foreach($data_termhit as $term){
							$pattern = '@('.preg_quote($term).')@u';  
						  }		
                        }
						foreach($data_display as $key => $meta){
						  if(is_array($meta)) $meta = join('；',$meta);  
						  $data_display[$key] = count($pattern) ? preg_replace($pattern,'<hit>\\1</hit>',$meta) : $meta;
						}
					  
					  ?>
					  
						  <h1>
							<span class='zong'><?php echo $data['_source']['zong'];?></span>
							<span class='collection'><?php echo $data['_source']['collection'];?> <?php echo isset($data['_source']['collection_name']) ? '  /  '.$data['_source']['collection_name']:'';?> / </span>
							<span class='locat'><?php echo isset($data['_source']['pageinfo']) ? $data['_source']['pageinfo'] : '' ?></span>
						  </h1>
						  <div class='meta_field'> <label>典藏號</label>
							<div class='mvalue'><?php echo  $data_display['identifier'];unset($data_display['identifier']); ?></div>
						  </div>
						  <?php if(isset($data['_source']['serial'])): ?>
						  <div class='meta_field'> <label>瀏覽階層</label>
							<div class='mvalue'><?php echo  $data_display['serial'];unset($data_display['serial']); ?></div>
						  </div>
						  <?php endif; ?>
						  <?php if(isset($data['_source']['category_level'])): ?>
						  <div class='meta_field'> <label>分類階層</label>
							<div class='mvalue'><?php echo  $data_display['category_level'];unset($data_display['category_level']); ?></div>
						  </div>
						  <?php endif; ?>
						  <?php if(isset($data['_source']['date_string'])): ?>
						  <div class='meta_field'> <label>日期資訊</label>
							<div class='mvalue'><?php echo  $data_display['date_string'];unset($data_display['date_string']); ?></div>
						  </div>
						  <?php endif; ?>
						  <?php if(isset($data['_source']['member_list']) && count($data['_source']['member_list'])) : ?>
						  <div class='meta_field'> <label>相關人名</label>
							<div class='mvalue'><?php echo  $data_display['member_list'];unset($data_display['member_list'],$data_display['main_mamber']); ?></div>
							
						  </div>
						  <?php endif; ?>
						  <div class='meta_field'> <label>內容摘要</label>
							<div class='mvalue'><?php echo  $data_display['abstract'];unset($data_display['abstract']); ?></div>
						  </div>
						  
						  <?php foreach($data_display as $field=>$meta): ?>
						  <?php   if(strstr($meta,'<hit>')):?>
						  <div class='meta_field'> <label>[<?php echo $field; ?>]</label>
							<div class='mvalue'><?php echo  $meta; ?></div>
						  </div>
						  <?php   endif; ?>
						  <?php endforeach; ?>
					  
					  
					 
					    <div class='system_info'>
					      <div class='meta_field'> 
						    <label>最後更新</label>
					        <div class='mvalue'><?php echo  $data['_db']['@time']; ?>  @ <?php echo  $data['_db']['@user']; ?></div>
					      </div>
						  <div class='meta_field'> 
						    <label>同步平台</label>
					        <div class='mvalue'><?php echo  $data['_db']['sync'] ? '已' : '未'; ?>上傳開放資料平台 </div>
					      </div>
					    </div>
					 
					  
					</td>
                    <td class='meta_function'>
					  <ul class='moption'>
					    <li><label>密等 : </label><span class='status _variable lockmode' ><?php echo  $data['_db']['lockmode']; ?></span></li>
						<li><label>隱私 : </label><span class='status _variable auditint' data-flag='<?php echo  $data['_db']['auditint']; ?>'><i class="fa fa-check" aria-hidden="true"></i></span></li>
						<li><label>搜尋 : </label><span class='status _variable open' data-flag='<?php echo  $data['_db']['open']; ?>'><i class="fa fa-check" aria-hidden="true"></i><i class="fa fa-times" aria-hidden="true"></i></span></li>
						<li><label>使用 : </label><span class='status _variable view' ><?php echo  $data['_db']['view']; ?></span></li>
						<li class='mactivate'>
						  <button type='button' class='active act_meta_getin' flag-editable='<?php echo $meta_edit_flag;?>'>
						    <span class='act_editable'  ><i class="fa fa-pencil" aria-hidden="true"></i> 編輯</span> 
							<span class='act_viewable'  ><i class="fa fa-eye" aria-hidden="true"></i> 檢視</span>
						  </button>
						</li>
					  </ul>
					</td>				   
				  </tr> 
			    <?php endforeach; ?>
			    </tbody>
			  </table>
			  <div class='record_control'>
			    <span class='record_result'>  
			      顯示 <span> <?php echo $data_pageing; ?> </span> /
				  共 <span> <?php echo $data_count; ?></span>  筆
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
					  <?php foreach($page_conf['jump'] as $p=>$limit ): ?>
				      <option value="<?php echo $limit; ?>"  <?php echo $p==$page_conf['now'] ? 'selected':''; ?> ><?php echo 'P.'.$p; ?></option>
				      <?php endforeach; ?>
                    </optgroup>					  
				  </select>
				</span>
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
		  <div class='msg_title'><?php echo $module_info; ?></div>
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