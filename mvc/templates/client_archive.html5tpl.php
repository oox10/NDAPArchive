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
	<link rel="stylesheet" type="text/css" href="theme/css/css_client.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_archive.css" />
	
	<!-- JS -->
	<script type="text/javascript" src="tool/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="tool/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	
	<script type="text/javascript" src="tool/canvasloader-min.js"></script>	
    <script type="text/javascript" src="tool/html2canvas.js"></script>	  	
	<script type="text/javascript" src="tool/Highcharts-5.0.14/code/highcharts.js"></script>
	
	<link rel="stylesheet" href="tool/jQRangeSlider-5.7.2/css/classic.css" type="text/css" />
	<script src="tool/jQRangeSlider-5.7.2/jQDateRangeSlider-min.js"></script>
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_client.js"></script>
	<script type="text/javascript" src="js_archive.js"></script>
	<!-- PHP DATA -->
	
	<?php
	//echo "<pre>";
	//var_dump($_SESSION['AHAS']);
	//exit(1);
	
	/*-- 介面資訊 --*/
	$format_array = array('文件','圖書','文物','底片','照片','微捲','微片','視聽');
	
	$user_info 		= isset($this->vars['server']['data']['user']) 	? $this->vars['server']['data']['user'] 	: array('user'=>array(),'sign'=>'anonymous','group'=>array(),'login'=>'');
	
	//使用者針對meta之參考資料
	$user_record    = isset($this->vars['server']['data']['config']['user_record']) ? $this->vars['server']['data']['config']['user_record'] : array('apply_queue'=>array(),'apply_history'=>array());
	
	
	//欄位設定
	$meta_search    = isset($this->vars['server']['data']['config']['meta_search']) ? $this->vars['server']['data']['config']['meta_search'] : array();
	
	//全宗項目列表
	$meta_zong      = isset($this->vars['server']['data']['config']['meta_zong'])   ? $this->vars['server']['data']['config']['meta_zong'] : array();
	
	//使用者參數設定
	$user_conf      = isset($this->vars['server']['data']['config']['user_config']) ? $this->vars['server']['data']['config']['user_config'] : array();
	
	//階層類別
	$zong_level      = isset($this->vars['server']['data']['config']['zong_level']) ? $this->vars['server']['data']['config']['zong_level'] : array();
	$level_term      = isset($this->vars['server']['data']['config']['level_term']) ? $this->vars['server']['data']['config']['level_term'] : '';
	
	/*-- 查詢結果 --*/
	$access_num     = isset($this->vars['server']['data']['query']) ? $this->vars['server']['data']['query'] : 0;
	$query_active   = isset($this->vars['server']['data']['active']) ? $this->vars['server']['data']['active'] : array('total'=>0,'start'=>1,'psize'=>20,'pquery'=>array(),'chart'=>'');
	$data_records	= isset($this->vars['server']['data']['result']) ? $this->vars['server']['data']['result'] : array();
    $data_page  	= isset($this->vars['server']['data']['page']) 	? $this->vars['server']['data']['page']    : array('all'=>array(),'list'=>array(),'top'=>'0-0','end'=>'0-0','next'=>'0-0','prev'=>'0-0','now'=>'1');  
		
	// 調用申請
	$data_applys	= isset($this->vars['server']['data']['apply']) ? $this->vars['server']['data']['apply']['lists'] : array();
	
	
	//介面後處理設定
	$dom_show['zong_filter'] = intval($access_num)&&intval($query_active['total'])!=0 ? 'none':'flex';
	
	//介面欄位填值
	$dom_val = isset($this->vars['server']['data']['search']['condition']) ? $this->vars['server']['data']['search']['condition'] : array();
	$dom_pqs = isset($this->vars['server']['data']['search']['postquery']) ? $this->vars['server']['data']['search']['postquery'] : array();
	$dom_set = isset($this->vars['server']['data']['search']['domconfig']) ? $this->vars['server']['data']['search']['domconfig'] : array();
	
	//資料集設定
	$zong = isset($dom_set['zong_selected']) ? $dom_set['zong_selected'] : array('檔案','公報','議事錄','議事影音');
	
	//夾贅詞功能擴充
	$term_capture = isset($this->vars['server']['data']['index']['capture']) ? $this->vars['server']['data']['index']['capture'] : '';
	
	
	//var_dump($term_capture);
	//exit(1);
	
	
	
	$session = $_SESSION[_SYSTEM_NAME_SHORT]['CLIENT'];
	
	
	$page_info = isset($this->vars['server']['info']) ? $this->vars['server']['info'] : ''; 
	
	if($access_num && isset($query_active['total']) && intval($query_active['total'])===0 ){
	  $page_info.="查詢無結果，請重新設定檢索條件";	
	}
	
	?>
  </head>
  <body>
    <div id='page-wrap'> <!-- 整體區塊 -->
	  <div id='archive-content' > <!-- 上半身 -->
		
		
		<header class='navbar'>
		  <div class='container'>
			<div id='navbar-header'>
			  <img  id='system_mark' src='theme/image/mark_tpa.png' />
			  <span id='system_title' ><?php echo _SYSTEM_HTML_TITLE; ?></span>
			</div>
			<ul id='navbar-manual'>
			  <li ><a href='index.php?act=Landing/announcement'>最新消息</a></li>
			  <li atthis='1'> 資料檢索 </li>
			  <li ><a href='index.php?act=Landing/account'>帳號註冊</a></li>
			  <li ><a href='index.php?act=Archive/apply'>使用說明</a></li>
			  <li ><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <a id='user_feedback'>錯誤回報</a></li>
			</ul>
		  </div>
		</header>
		<div class='system_body_area container'>
		  
		  <!-- Info & Filter   -->
		  <div class='function_area'>
			
			<!-- Account -->
			<div class='func_block' id='user_account'>
			  <h1>
				<span class='iconv'  ><i class="fa fa-user" aria-hidden="true"></i></span> 
				<?php if( isset($session['ACCOUNT_TYPE']) && $session['ACCOUNT_TYPE']!='GUEST' ): ?>
				<span class='signin'><?php echo $user_info['signin']; ?></span>
				<span class='option' id='signout' title='登出 / signout' style='font-size:0.8em;'>登出</span>
				<?php else: ?>
				<span class='signin'>訪客 ( <?php echo $user_info['signin']; ?> )</span>
				<span class='option' id='signin' title='登入 / signin' style='font-size:0.8em;'>登入</span>
				<?php endif; ?>
			  
			  </h1>
			  <div class='login_info'>
				<div class='login_time'><label>登入時間：<?php echo $user_info['login']; ?></label></div>
				<div class='user_group'><label>所屬群組：<?php echo $user_info['user']['user_group'] ? $user_info['user']['user_group'] : '訪客'; ?></label></div>
				
			  </div>
			</div>
			
			<!-- History -->
			<div class='func_block' id='query_history'>
			  <h1> 
				<span class='iconv'><i class="fa fa-history" aria-hidden="true"></i></span>
				<span class='title'>檢索歷史</span>
				<span class='option func_switch'><i class="fa fa-plus-square" aria-hidden="true"></i></span>
			  </h1>
			  <div class='func_contents _domconf _setshow' id='query_history_content' style='display:<?php echo isset($dom_set['query_history_content']) ? $dom_set['query_history_content'] : 'block' ?>;'>
			  <?php if( isset($user_record['search_history']) && count($user_record['search_history']) ): ?>
			  <?php
			  ?> 
				
				<div class='history_block history_now' >
				  <div class='history_title'>
					<span class='his_name his_now'>目前檢索組合</span>
				  </div>
				  <div class='history_content'>
				  <?php 
					$now_his     = array_shift($user_record['search_history']); 
					$now_query   = json_decode($now_his['Query_String'],true);
				  ?>
				  <?php if(isset($now_query['query']) && count($now_query['query'])): ?>
				  <?php   foreach($now_query['query'] as $i => $query):?>   
					<div class='his_term_block'> 
					  <div class='his_option_del'> </div>
					  <div class='his_term_cont' >
						<a class='his_term_pop' >
						  <span class='his_field'><?php echo $meta_search[$query['field']]['FieldName'];?></span>
						  <span ><?php echo isset($query['attr'])&&$query['attr']=='-' ? '&ne;' :'='; ?></span>
						  <span ><?php echo $query['value']; ?></span>
						</a>
					  </div>
					</div>
				  <?php   endforeach; ?>
				  <?php endif; ?>		
				  </div>
				</div>
				<ul class='record_list query_history' mode='limit'>
				  <?php foreach($user_record['search_history'] as $i=>$qhis ):  ?> 
				  <?php 
					$query_set   = json_decode($qhis['Query_String'],true);
					$first_query = current($query_set['query']);
					$query_count = count($query_set['query']);
					$query_search = rawurlencode(str_replace('/','*',base64_encode($qhis['Query_String'])));
					
				  ?>
				  <li class='history_block'> 
					<div class='history_title'>
					  <span class='his_name'>
						<a href='index.php?act=Archive/Search/<?php echo $query_search;?>' target=_self> 
						<?php echo '檢索歷史 - '.($i+1); ?>   
						</a>
					  </span> 
					  <span class='his_time'> ( <?php echo substr($qhis['Update_Time'],0,16);?> ) </span>
					</div>
					<div class='history_content'>
					  <div class='his_cont'>
						<?php foreach($query_set['query'] as $query):?>
						<div class='his_term_block'> &#187; 
						  <span class='his_field'><?php echo $meta_search[$first_query['field']]['FieldName'];?></span>
						  <span ><?php echo isset($query['attr'])&&$query['attr']=='-' ? '&ne;' :'='; ?></span>
						  <span > <?php echo $query['value'];?> </span>
						</div>
						<?php endforeach; ?>
					  </div>
					  <div class='his_page'>@ P.<?php echo $qhis['Final_Page'];?></div>
					</div>
				  </li>
				  <?php endforeach; ?>
				</ul>
				
				<?php if(count($user_record['search_history'])>2): ?>
				<div class='record_func'><span class='option list_more'  ><i> hide </i>more</span></div>
				<?php endif; ?>
			  
			  
			  <?php endif; ?>
			  </div>
			</div>
			
			<!-- Browses -->
			<div class='func_block' id='zong_level'>
			  <h1> 
				<span class='iconv'><i class="fa fa-bookmark" aria-hidden="true"></i></span>
				<span class='title'>類別瀏覽</span>
				<span class='option func_switch'><i class="fa fa-plus-square" aria-hidden="true"></i></span>
			  </h1>
			  <div class='func_contents _domconf _setshow' id='zong_level_content' style='display:<?php echo isset($dom_set['zong_level_content']) ? $dom_set['zong_level_content'] : 'block' ?>;'>
				<ul class='level_group'>
				<?php foreach($zong_level as $lvcode => $lvdata):?>
				  <?php 
				  if(preg_match('/'.addcslashes($lvdata['level'],'/').'/', $level_term)){
					$switch  = 1;
					$open_lv[$lvcode] = true;
					$display = 'block';				
				  }else{
					$display = isset($open_lv[$lvdata['uplv']]) || $lvdata['uplv']=='' ? 'block':'none';
					
					$switch  = $lvdata['site'] < ($level_term?1:2) ? 1:0 ;  
					
					if($switch){
					  $open_lv[$lvcode] = true; 	
					}
				  }
				  ?>
				  <li class='level <?php echo $lvdata['level']==$level_term ? 'lvat':''; ?> ' id='<?php echo $lvcode; ?>' 
					  data-set = '<?php echo $lvdata['level']; ?>'
					  site='<?php echo $lvdata['site'];?>' 
					  up='<?php echo $lvdata['uplv'];?>' 
					  switch='<?php echo $switch;?>'  
					  style='display:<?php echo  $display;?>;'  >
					<span class='option' >
					<?php echo $lvdata['switch']==' * ' ? '<i class="fa fa-asterisk" aria-hidden="true"></i>' : '<i class="fa fa-plus hide" aria-hidden="true"></i><i class="fa fa-minus open" aria-hidden="true"></i>'; ?>  
					</span>
					<span class='name'><?php echo System_Helper::short_string_utf8($lvdata['name'],(30-($lvdata['site']-1)*3)); ?></span>
					<span class='count' ><?php echo $lvdata['count']; ?></span>
				  </li>
				<?php endforeach; ?>
				</ul>
			  </div>
			</div>
			
			<!-- Capture -->
			<?php if(isset($query_active['pquery']['termcapture'])): ?>
			<div class='func_block' id='term_capture' >
			  <h1> 
				<span class='iconv'><i class="fa fa-tasks" aria-hidden="true"></i></span>
				<span class='title'>相關詞彙</span>
				<span class='option func_switch'><i class="fa fa-plus-square" aria-hidden="true"></i></span>
			  </h1>
			  <div class='func_contents _domconf _setshow' id='term_capture_content' style='display:block' >
				<?php    $term_focus = explode('|',$term_capture); ?>
				<ul class='term_list _setshow' id='termcapture' style='display:block'>
				  <?php foreach($query_active['pquery']['termcapture'] as $i=>$pqset):?>
				  <li>
					<input type='checkbox'  
						   class='<?php echo 'capture';?>' 
						   name='<?php echo $pqfield;?>' 
						   value='<?php echo $pqset['key'];?>'   
						   <?php echo in_array($pqset['key'],$term_focus)?'checked':'';?> >   
					<span class='term_name'  ><?php echo System_Helper::short_string_utf8($pqset['key'],23); ?></span>
					<span class='term_count' ><?php echo $pqset['doc_count'];?></span>
				  </li>
				  <?php endforeach; ?>
				</ul>
			  </div>
			</div>
			<?php endif; ?>
			 
			<!-- PQuery -->
			<div class='func_block' id='post_query' >
			  <h1> 
				<span class='iconv'><i class="fa fa-tasks" aria-hidden="true"></i></span>
				<span class='title'>後分類篩選</span>
				<span class='option func_switch'><i class="fa fa-plus-square" aria-hidden="true"></i></span>
			  </h1>
			  <ul class='term_list' id='filter_queue' ></ul>
			  <div class='func_contents _domconf _setshow' id='post_query_content' style='display:<?php echo isset($dom_set['post_query_content']) ? $dom_set['post_query_content'] : 'block' ?>;' >
				<select id='facetsby' class='facets_select _domconf _setval'>
				  <option value='zong' <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='zong' ? 'selected':'' ?> >全宗系列</option> 
				  <option value='meeting_level'  <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='meeting_level' ? 'selected':'' ?> >會議階層</option>
				  <option value='category_level'  <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='category_level' ? 'selected':'' ?> >分類階層</option>
				  <option value='yearrange'   <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='yearrange' ? 'selected':'' ?> >年代統計</option>
				  <option value='list_member'    <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='list_member' ? 'selected':'' ?> >相關人名</option>
				  <option value='list_organ'  <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='list_organ' ? 'selected':'' ?> >相關單位</option>
				  <option value='list_subject'  <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='list_subject' ? 'selected':'' ?> >相關主題</option>
				  <!--<option value='location'  <?php echo isset($dom_set['facetsby']) && $dom_set['facetsby']=='location' ? 'selected':'' ?> >相關地名</option>-->
				  
				</select>
				
				<?php foreach($query_active['pquery'] as $pqfield => $pqarray ): ?>
				<ul class='term_list _domconf _setshow' id='<?php echo $pqfield ; ?>' style='display:<?php echo isset($dom_set[$pqfield]) ? $dom_set[$pqfield]:'' ?> '>
				  <?php foreach($pqarray as $i=>$pqset):?>
				  <?php   if($pqfield!='termcapture'): ?>
				  <li>
					<input type='checkbox'  
						   class='<?php echo 'filter'; ?>' 
						   name='<?php echo $pqfield;?>' 
						   value='<?php echo $pqset['key'];?>'   
						   <?php echo isset($dom_pqs[$pqfield])&&in_array($pqset['key'],$dom_pqs[$pqfield])?'checked':'';?> >   
					<span class='term_name'  ><?php echo System_Helper::short_string_utf8($pqset['key'],23); ?></span>
					<span class='term_count' ><?php echo $pqset['doc_count'];?></span>
				  </li>
				  <?php  endif; ?>
				  <?php endforeach; ?>
				</ul>
				<?php endforeach; ?>
			  </div>
			</div>
			
			
			
		  </div>	  
		  
		  <!-- 內容區 -->
		  <div class='contents_area'>
			
			<!-- 搜尋 -->
			<ul class='search_mode'>
			  <li class='mode_switch '  id='general' >一般查詢</li>
			  <li class='mode_switch <?php echo count($dom_val)>1 || ( isset($dom_val['query']) && count($dom_val['query'])>1) ? 'atthis':''; ?>'  id='advance' >進階查詢</li>
			  <li class='zong_filter' >
			    <label>資料集：</label>
				<ul class='zong_picker' >
				  <li class='archive' title='議事相關資料' >
				    <input type='checkbox' class='zselect' name='archive' value='檔案' 		<?php echo in_array('檔案',$zong) ? 'checked':''; ?> /> <span class='zname option' >檔案</span>
				    <input type='checkbox' class='zselect' name='archive' value='議事錄'	<?php echo in_array('議事錄',$zong) ?'checked':''; ?> /> <span class='zname option' >議事錄</span>
				    <input type='checkbox' class='zselect' name='archive' value='公報' 		<?php echo in_array('公報',$zong) ? 'checked':''; ?>/> <span class='zname option' >公報</span>
				    <input type='checkbox' class='zselect' name='archive' value='議事影音' 	<?php echo in_array('議事影音',$zong) ? 'checked':''; ?>/> <span class='zname option' >議事影音</span> |
				  </li>
				  <li class='biography' title='省諮議會編輯之議員傳記資料' ><input type='radio'  class='zselect' name='biography' value='議員傳記'  <?php echo in_array('議員傳記',$zong) ? 'checked':''; ?>	/><span class='zname option' >議員傳記</span> | </li>
				  <li class='photo'		title='省議會活動照片' ><input type='radio'  class='zselect' name='photo' value='活動照片' <?php echo in_array('活動照片',$zong) ? 'checked':''; ?> /><span class='zname option' >活動照片</span></li> 
				</ul>
			  </li>
			  
			  <li class='mode_switch '  id='initial' >重新查詢</li>
			</ul>
			<div class='search_block'>
			  <form class='search_form' name='search_form' id='normal_search' method='get' onsubmit="return UserSearchSubmit();">
				<div id='general_search_block'>	
					<?php 
					  $search_field = isset($dom_val['query'][0]) ? $dom_val['query'][0]['field'] : '_all';
					  $search_mode  = $search_field=='termpat' || $search_field=='clipterm' ? $search_field : 'normal';
					  $search_terms = '';
					  $termpad_set  = ['2','','',''];
					  $clipterm_set = ['','1','',''];	
					  
					  if($search_mode=='termpat'){
						  
						$pattern = isset($dom_val['query'][0]) ? $dom_val['query'][0]['value']:'';  
						if(preg_match('/^(\{\d+\})?(.*?)(\{\d+\})?@(.*?)$/',$pattern,$match)){
						  array_shift($match);	
						  $termpad_set[0] = isset($match[0]) ? preg_replace('/\{|\}/','',$match[0]) : '2';
						  $termpad_set[1] = $match[1];
						  $termpad_set[2] = isset($match[2]) ? preg_replace('/\{|\}/','',$match[2]) : '' ;
						  $termpad_set[3] = isset($match[3]) ? trim($match[3]) : '';
						  if($term_capture && $term_capture!=$termpad_set[3]){
							$termpad_set[3] = $term_capture; 
						  }
						  
						}
					  }else if($search_mode=='clipterm'){
						$pattern = isset($dom_val['query'][0]) ? $dom_val['query'][0]['value']:'';  
						if(preg_match('/^(.*?)\{(.*?)\}(.*?)@(.*?)$/',$pattern,$match)){
						  array_shift($match);	
						  $clipterm_set[0] = $match[0] ? $match[0] : '';
						  $clipterm_set[1] = $match[1] ? $match[1] : '1';
						  $clipterm_set[2] = $match[2] ? $match[2] : '';
						  $clipterm_set[3] = isset($match[3]) ? trim($match[3]) : '';
						  if($term_capture && $term_capture!=$clipterm_set[3]){
							$clipterm_set[3] = $term_capture; 
						  }
						  
						}
					  }else{
						$search_terms = isset($dom_val['query'][0]) ? $dom_val['query'][0]['value']:'';;  
					  }
					?>
					
					
					<select id='search_field' name="Query_Field">
					<?php foreach($meta_search as $field => $sconf): ?>
					  <?php if($sconf['SearchOption']): ?>
					  <option mode='normal' value='<?php echo $field;?>' <?php echo  $search_field==$field ? 'selected':'' ?> > 
						<?php echo $sconf['FieldName'];?>
					  </option>
					  <?php endif;?>
					<?php endforeach; ?>
					  <option mode='termpat'  value='termpat'  <?php echo  $search_field=='termpat'  ? 'selected':'' ?> > 綴詞查詢 </option>
					  <option mode='clipterm' value='clipterm' <?php echo  $search_field=='clipterm' ? 'selected':'' ?> > 夾詞查詢 </option>
					</select> :
					<ul id='search_input_mode'>
					  <li id='normal' style='display:<?php echo $search_mode=='normal' ? 'block' : 'none'; ?>' >
						<input  id='search_input' 
								name='Query_String' 
								type='text'
								value ='<?php echo $search_terms;?>' />
						<input  type="hidden" name="accnum" id='accnum' value="<?php echo $access_num; ?>"  />
					  <li>
					  <li id='termpat' style='display:<?php echo $search_mode=='termpat' ? 'flex' : 'none'; ?>'  >
						<select class='ap_input' id='termpat_search_input_prev' >
						  <option value=''  <?php echo $termpad_set[0] === '' ? 'selected' : ''; ?> > - </option>
						  <option value='1' <?php echo $termpad_set[0] === '1' ? 'selected' : ''; ?> >1字</option>
						  <option value='2' <?php echo $termpad_set[0] === '2' ? 'selected' : ''; ?> >2字</option>
						  <option value='3' <?php echo $termpad_set[0] === '3' ? 'selected' : ''; ?> >3字</option>
						  <option value='4' <?php echo $termpad_set[0] === '4' ? 'selected' : ''; ?> >4字</option>
						  <option value='5' <?php echo $termpad_set[0] === '5' ? 'selected' : ''; ?> >5字</option>
						</select> 
						<input  class='ap_input' type='text' id='termpat_search_input' value='<?php echo $termpad_set[1]; ?>' > 
						<select class='ap_input' id='termpat_search_input_back' >
						  <option value=''  <?php echo $termpad_set[2] === ''  ? 'selected' : ''; ?> > - </option> 
						  <option value='1' <?php echo $termpad_set[2] === '1' ? 'selected' : ''; ?> >1字</option>
						  <option value='2' <?php echo $termpad_set[2] === '2' ? 'selected' : ''; ?> >2字</option>
						  <option value='3' <?php echo $termpad_set[2] === '3' ? 'selected' : ''; ?> >3字</option>
						  <option value='4' <?php echo $termpad_set[2] === '4' ? 'selected' : ''; ?> >4字</option>
						  <option value='5' <?php echo $termpad_set[2] === '5' ? 'selected' : ''; ?> >5字</option>
						</select> 
						@ 
						<input type='text' class='term_focus' id='termpat_search_target' placeholder='目標詞彙' value='<?php echo $termpad_set[3]; ?>' />
					  </li>
					  <li id='clipterm' style='display:<?php echo $search_mode=='clipterm' ? 'flex' : 'none'; ?>' >
						<input  class='ap_input' type='text' id='clipterm_search_input_prev' value='<?php echo $clipterm_set[0]; ?>'  />
						<select class='ap_input' id='clipterm_search_input' >
						  <option value='1' <?php echo $clipterm_set[1] === '1' ? 'selected' : ''; ?> >1字</option>
						  <option value='2' <?php echo $clipterm_set[1] === '2' ? 'selected' : ''; ?> >2字</option>
						  <option value='3' <?php echo $clipterm_set[1] === '3' ? 'selected' : ''; ?> >3字</option>
						  <option value='4' <?php echo $clipterm_set[1] === '4' ? 'selected' : ''; ?> >4字</option>
						  <option value='5' <?php echo $clipterm_set[1] === '5' ? 'selected' : ''; ?> >5字</option>
						  <option value='1,2' <?php echo $clipterm_set[1] === '1,2' ? 'selected' : ''; ?> >2字以內</option>
						  <option value='1,3' <?php echo $clipterm_set[1] === '1,3' ? 'selected' : ''; ?> >3字以內</option>
						  <option value='1,4' <?php echo $clipterm_set[1] === '1,4' ? 'selected' : ''; ?> >4字以內</option>
						  <option value='1,5' <?php echo $clipterm_set[1] === '1,5' ? 'selected' : ''; ?> >5字以內</option>
						</select> 
						<input  class='ap_input' type='text' id='clipterm_search_input_back' value='<?php echo $clipterm_set[2]; ?>' /> 
						@
						<input type='text'  class='term_focus' id='clipterm_search_targte' placeholder='目標詞彙' value='<?php echo $clipterm_set[3]; ?>' />
					  </li>
					</ul>
					
					<button type="button" id="search_submit" />查詢</button>
					<span class='option' id='act_reset_search' title='重設檢索'><i class="fa fa-times" aria-hidden="true"></i></span>
					
				</div>
				<div id='advance_search_block' class='' style='visibility:hidden;' >
				  <div class='condition field_filter'>
					<label> 增加條件 <span class='option' id='add_search_term' ><i class="fa fa-plus" aria-hidden="true"></i></span></label>:
					<ul id='additional_continer'>
					  <li class='additional_search _template' >
						<select class='attr' >
						  <option value='+' <?php echo isset($dom_val['query'][1])&&$dom_val['query'][1]['attr']=='+'?'selected':'';?> >+</option>
						  <option value='-' <?php echo isset($dom_val['query'][1])&&$dom_val['query'][1]['attr']=='-'?'selected':'';?> >−</option>
						</select>
						<select class="field">
						  <?php foreach($meta_search as $field => $sconf): ?>
							<?php if($sconf['SearchOption']): ?>
							<option value='<?php echo $field;?>' <?php echo  isset($dom_val['query'][1])&&$dom_val['query'][1]['field']==$field ? 'selected':'' ?>   > 
							  <?php echo $sconf['FieldName'];?> 
							</option>
							<?php endif;?>
						  <?php endforeach; ?>
							<option value='_all'>相關詞彙</option>
						</select>
						<input  class='term'  type='text' value ='<?php echo isset($dom_val['query'][1]) ? $dom_val['query'][1]['value']:'';?>' />
						<button type="button" class='blue delete_search_add' /> <i class="fa fa-trash-o" aria-hidden="true"></i> </button>
					  </li>
					  <?php if(isset($dom_val['query']) && count($dom_val['query'])>2): ?>
					  <?php   for($i=2;$i<count($dom_val['query']);$i++): ?>
					  <li class='additional_search' >
						<select class='attr' >
						  <option value='+' <?php echo isset($dom_val['query'][$i])&&$dom_val['query'][$i]['attr']=='+'?'selected':'';?> >+</option>
						  <option value='-' <?php echo isset($dom_val['query'][$i])&&$dom_val['query'][$i]['attr']=='-'?'selected':'';?> >−</option>
						</select>
						<select class="field">
						  <?php foreach($meta_search as $field => $sconf): ?>
							<?php if($sconf['SearchOption']): ?>
							<option value='<?php echo $field;?>' <?php echo  $dom_val['query'][$i]['field']==$field ? 'selected':'' ?>   > 
							  <?php echo $sconf['FieldName'];?> 
							</option>
							<?php endif;?>
						  <?php endforeach; ?>
						</select>
						<input  class='term'  type='text' value ='<?php echo isset($dom_val['query'][$i]) ? $dom_val['query'][$i]['value']:'';?>' />
						<button type="button" class='green delete_search_add' /> <i class="fa fa-trash-o" aria-hidden="true"></i> </button>
					  </li>
					  <?php   endfor; ?>
					  <?php endif; ?>
					</ul>
				  </div>
				  
				  
				  <!--  
				  <div class='condition format_filter'>
					<label>類型篩選 <span class='option' id='reset_format_sel' ><i class="fa fa-refresh" aria-hidden="true"></i></span></label>:
					<ul >
					  <?php foreach($format_array as $format): ?>	
					  <li ><input type='checkbox' name='format' value='<?php echo $format;?>' <?php echo isset($dom_val['format'])&&in_array($format,$dom_val['format'])?'checked':'' ?> />.<?php echo $format;?></li>
					  <?php endforeach; ?>	
					</ul>
				  </div>
				  -->
				  
				  <!-- 時間篩選以後在弄，現在有點煩
				  <div class='condition date_filter'>
					<label>時間篩選 <span class='option' id='reset_daterange_set' ><i class="fa fa-refresh" aria-hidden="true"></i></span></label>:
					
					<ul id='daterange_continer'>
					  <li class='daterange_search _template' >
					    <input class='term'  type='text' />
					    <input class='term'  type='text' />
					    
					  </li>
					  
					 
					  <li class='additional_search _template' >
						<select class='attr' >
						  <option value='+' <?php echo isset($dom_val['query'][1])&&$dom_val['query'][1]['attr']=='+'?'selected':'';?> >+</option>
						  <option value='-' <?php echo isset($dom_val['query'][1])&&$dom_val['query'][1]['attr']=='-'?'selected':'';?> >−</option>
						</select>
						<select class="field">
						  <?php foreach($meta_search as $field => $sconf): ?>
							<?php if($sconf['SearchOption']): ?>
							<option value='<?php echo $field;?>' <?php echo  isset($dom_val['query'][1])&&$dom_val['query'][1]['field']==$field ? 'selected':'' ?>   > 
							  <?php echo $sconf['FieldName'];?> 
							</option>
							<?php endif;?>
						  <?php endforeach; ?>
							<option value='_all'>相關詞彙</option>
						</select>
						<input  class='term'  type='text' value ='<?php echo isset($dom_val['query'][1]) ? $dom_val['query'][1]['value']:'';?>' />
						<button type="button" class='blue delete_search_add' /> <i class="fa fa-trash-o" aria-hidden="true"></i> </button>
					  </li>
					  
					  
					  
					  
					  
					  
					  <?php if(isset($dom_val['query']) && count($dom_val['query'])>2): ?>
					  <?php   for($i=2;$i<count($dom_val['query']);$i++): ?>
					  <li class='additional_search' >
						<select class='attr' >
						  <option value='+' <?php echo isset($dom_val['query'][$i])&&$dom_val['query'][$i]['attr']=='+'?'selected':'';?> >+</option>
						  <option value='-' <?php echo isset($dom_val['query'][$i])&&$dom_val['query'][$i]['attr']=='-'?'selected':'';?> >−</option>
						</select>
						<select class="field">
						  <?php foreach($meta_search as $field => $sconf): ?>
							<?php if($sconf['SearchOption']): ?>
							<option value='<?php echo $field;?>' <?php echo  $dom_val['query'][$i]['field']==$field ? 'selected':'' ?>   > 
							  <?php echo $sconf['FieldName'];?> 
							</option>
							<?php endif;?>
						  <?php endforeach; ?>
						</select>
						<input  class='term'  type='text' value ='<?php echo isset($dom_val['query'][$i]) ? $dom_val['query'][$i]['value']:'';?>' />
						<button type="button" class='green delete_search_add' /> <i class="fa fa-trash-o" aria-hidden="true"></i> </button>
					  </li>
					  <?php   endfor; ?>
					  <?php endif; ?>
					  
					
					</ul>
					
					<input type='checkbox' name='yearnum' id='select_date_null'  value='none' <?php echo isset($dom_val['yearnum'])?'checked':''?> > 篩選無日期資料
				    
				  
				  </div>
				  -->
					  
				</div>
			  </form>
			</div>
			
			
			<!-- 圖表 -->
			<div class='chart_block' id='query_chart' data-chart='<?php echo $query_active['chart'];?>'></div>
			
			
			<!-- 檢索結果 -->
			<?php if(count($data_records)): ?>
			<div >  
			  <div class='page_block'>
				<span class='record_summary'>  
				  <span>共   <?php echo $query_active['total']; ?> 筆 </span>
				  /
				  <span>顯示 <?php echo $data_page['list'][$data_page['now']]; ?> </span>
				</span>
				<span class='record_pages'>
				  <a class='page_tap page_to' page='<?php echo $data_page['prev'];?>' > &#171; </a>
				  <span class='page_select'>
				  <?php foreach($data_page['list'] as $p=>$limit ): ?>
				  <a class="page_tap <?php echo $p==$data_page['now'] ? 'page_now':'page_to'; ?>" page="<?php echo $limit;?>" ><?php echo $p; ?></a>
				  <?php endforeach; ?>
				  </span>
				  <a class='page_tap page_to' page='<?php echo $data_page['next'];?>' > &#187; </a>
				  ，跳至
				  <select class='page_jump'>
					<optgroup label="首尾頁">
					  <option value='<?php echo $data_page['top'];?>' >首頁</option>
					  <option value='<?php echo $data_page['end'];?>' >尾頁</option>
					</optgroup>
					<optgroup label="-">
					  <?php foreach($data_page['jump'] as $p=>$limit ): ?>
					  <option value="<?php echo $limit; ?>"  <?php echo $p==$data_page['now'] ? 'selected':''; ?> ><?php echo 'P.'.$p; ?></option>
					  <?php endforeach; ?>
					</optgroup>					  
				  </select>
				</span>
			  </div>
			
			  <!-- 功能 -->
			  <div class='option_block '>
				  <div class='result_collect'>
					<span class='option_field'> <input type='checkbox' class='result_selected_all' >全選 </span> 
					<input type='radio' name='user_select_target' value='page' checked >本頁 
					<input type='radio' name='user_select_target' value='<?php echo $query_active['total'] ? $access_num : 0; ?>' >所有結果，
					<span class='option' id='act_export_meta'> <i class="fa fa-download" aria-hidden="true"></i> 下載目錄 </span>
					<!--
					<span >，將勾選加入</span>
					<select class='user_tags'>
					  <option class='user_tag' value='調閱申請'> 調閱申請單 </option>
					  <!-- <option value='add_new_tag' class='add_tag' > + 新增資料夾 + </option> -->
					<!--
					</select>		
					<input class='user_new_tag' type='text'   value='<?php echo date('Y-m-d H:i:s');?>' /> 
					<input class='save_button'  type='button' value='+' /> 
					-->
				  </div>
				  <div class='result_setting'>
					<label>排序</label>
					<select id='sortby' name="sortby" class='_domconf _setval'>
					<?php foreach($meta_search as $field => $sconf): ?>
					  <?php if($sconf['SearchSort']): ?>
					  <option value='<?php echo $field.'-asc';?>' <?php echo isset($dom_set['sortby']) && $dom_set['sortby']==$field.'-asc' ? 'selected':'' ?>  > <?php echo $sconf['FieldName'];?> &#8593; </option>
					  <option value='<?php echo $field.'-desc';?>' <?php echo isset($dom_set['sortby']) && $dom_set['sortby']==$field.'-desc' ? 'selected':'' ?>  > <?php echo $sconf['FieldName'];?> &#8595; </option>
					  <?php endif;?>
					<?php endforeach; ?>
					</select>
					，
					<label>每頁</label>
					<select id='pageing' name="pageing" class='_domconf _setval'>
					  <option value='20'  <?php echo $query_active['psize']==20 ? 'selected':'' ?> > 20 筆 </option>
					  <option value='50'  <?php echo $query_active['psize']==50 ? 'selected':'' ?> > 50 筆 </option>
					  <option value='100' <?php echo $query_active['psize']==100 ? 'selected':'' ?> > 100 筆 </option>
					</select>
				  </div >
			  </div>
			
			  <!-- 內容 -->
			  <div class='result_block'>
				
				<?php foreach($data_records as $no => $meta): ?>
				  <div class='data_record tr_like'>
					<div class='result_select'>
					  <input type='checkbox' 
							 class='result_selecter' 
							 value='<?php echo $meta['@SystemLink']['value'];?>'
					  >.
					  <?php echo $query_active['start']+$no+1; ?>
					</div>
					<div class='result_content'>
					  
					  <?php if($meta['@Type']['value']=='biography'): ?>  
					  <div class='result_header'>
						<div class='result_title'>
						  <div class='result_type'>
							
						  </div>
						  <div class='result_link'>
							<span class='acc_link'>  
							  <span class='meta_zong_mark' lv='<?php echo $meta['data_type']['value'];?>' ><?php echo $meta['zong']['value'];?></span>
							  <?php if($meta['identifier']['value']): ?>
								<a  target=_blank><?php echo $meta['identifier']['apply'];?> </a>
							  <?php else: ?>
								<a  target=_blank><?php echo $meta['collection']['apply'];?> </a>
							  <?php endif; ?>
							</span>
						  </div> 
						  <div class='result_active'>
							
						  </div>
						</div>
						<div class='result_info'>
						  
						  <!-- 全宗欄位 -->
						  <div style='width:100%'>
							<div class='result_field ' >
							  <span class='field_name'> &#187; 年代 </span><span class='field_value'><?php echo $meta['date_string']['apply']; ?></span>
							</div>
							<div class='result_field ' >
							  <span class='field_name'> &#187; <?php echo $meta['@Offer']['field']; ?></span><span class='field_value'><?php echo $meta['@Offer']['value']; ?></span>
							</div>
						  </div>
						</div>
						
					  </div>
					  <div class='result_body'>
					    <div class='result_descrip'>
						  <div class='result_text'>
						    <?php echo  $meta['abstract']['apply']; ?>
						  </div>
						  <div class='result_source'>
						    <?php echo isset($meta['@Source']) ? $meta['@Source']['value'] : ''; ?>
						  </div>
						</div>
					  
					  </div>
					  <div class='result_floder'>
						<div class='result_reference' >
						  <?php echo isset($meta['reference']) ? preg_replace('/\s+/','',$meta['reference']['apply']) : ''; ?>
						</div>
					  </div>
					  
					  <?php elseif($meta['@Type']['value']=='photo'): ?>  
					  <div class='result_header'>
						<div class='result_title'>
						  <div class='result_type'>
							
						  </div>
						  <div class='result_link'>
							<span class='acc_link'>  
							  <span class='meta_zong_mark' lv='<?php echo $meta['data_type']['value'];?>' ><?php echo $meta['zong']['value'];?></span>
							  <a  target=_blank><?php echo $meta['collection_name']['apply'];?> - <?php echo $meta['identifier']['apply'];?> </a>
							</span>
						  </div> 
						</div>
						<div class='result_info'>
						  
						  <!-- 全宗欄位 -->
						  <div style='width:100%'>
							<div class='result_field ' >
							  <span class='field_name'> &#187; 拍攝日期 </span><span class='field_value'><?php echo $meta['date_string']['apply']; ?></span>
							</div>
							
							<?php if(isset($meta['main_mamber']) && $meta['main_mamber']['value']): ?>
							<div class='result_field ' >
							  <span class='field_name'> &#187; 內容議員 </span><span class='field_value'><?php echo $meta['main_mamber']['apply']; ?></span>
							</div>
							<?php endif; ?>
							
							<?php if(isset($meta['list_location']) && $meta['list_location']['value']): ?>
							<div class='result_field ' >
							  <span class='field_name'> &#187; 拍攝地點 </span><span class='field_value'><?php echo $meta['list_location']['apply'] ? $meta['list_location']['apply'] : '-' ; ?></span>
							</div>
						    <?php endif; ?>
							
							<div class='result_field result_active' >
							  <span class='field_name'> &#187; 提供方式 </span>
							  <span class='field_value'>
								<span class='option  <?php echo $meta['@ViewMode']['value']; ?>'  apply=''  acckey='<?php echo $meta['@SystemLink']['field'];?>'   >
								  <a target='_new'><?php echo $meta['@ViewMode']['apply']; ?></a></span>
								</span>
							</div>
						  </div>
						</div>
						
					  </div>
					  <div class='result_body'>
					    <div class='result_descrip photo_mode'>
						  
						  <div class='photo_wrapper'>
						    <img src='thumb.php?src=<?php echo $meta['@Thumb']['value']?>' />
						  </div>	  
						  <div class='photo_descrip'>
						    <?php echo  ($meta['abstract']['apply']) ? $meta['abstract']['apply'] : '(無內容描述)'; ?>
						  </div>
						</div> 
					  </div>
					 
					  <?php else: //檔案;公報;議事錄;影片 ?>  
					  <div class='result_header'>
						<div class='result_title'>
						  <div class='result_type'>
							
						  </div>
						  <div class='result_link'>
							<span class='acc_link'>  
							  <span class='meta_zong_mark' lv='<?php echo $meta['data_type']['value'];?>' ><?php echo $meta['zong']['value'];?></span>
							  <?php if($meta['identifier']['value']): ?>
								<a  target=_blank><?php echo $meta['identifier']['apply'];?> </a>
							  <?php else: ?>
								<a  target=_blank><?php echo $meta['collection']['apply'];?> </a>
							  <?php endif; ?>
							</span>
						  </div> 
						  <div class='result_active'>
							
						  </div>
						</div>
						<div class='result_info'>
						  
						  <!-- 全宗欄位 -->
						  <div style='width:100%'>
							
							<?php if($meta['data_type']['value'] == 'archive'): ?> 
							  
							  <?php if($meta['zong']['value']=='公報' || $meta['zong']['value']=='議事錄'): ?>
							  <div class='result_field ' >
								<span class='field_name'> &#187; 卷/冊號</span>
								<span class='field_value'>
								  <?php //公報與議事路可以卷搜尋
						            $store_no_set = explode('-',$meta['collection']['value']);	
									$case_no = array_pop($store_no_set);	
									//查詢連結 
									$search = array();
									$search['query'][] = array('field'=>'collection','value'=>$meta['collection']['value'],'attr'=>'+'); 	 
									$search_string = rawurlencode(str_replace('/','*',base64_encode(json_encode($search)))); 
								  ?>
								  <?php echo $meta['collection']['apply'];?>
								  <a class='search_collection' href='index.php?act=Archive/Search/<?php echo $search_string;?>' title='查詢本卷所有件' target='_blank'>
								    <?php echo isset($meta['collection_name']) ? $meta['collection_name']['apply'] : $meta['collection']['apply']; ?>
								  </a>
								</span>
							  </div>
							  <?php endif; ?>
							  
							  
							  <?php if(isset($meta['meeting_level'])): ?>
							  <div class='result_field ' >
								<span class='field_name'> &#187; 會議階層 </span><span class='field_value'>
								<?php 
								$reglv = preg_replace(array('/<search>/','/<\/search>/'),array("<SS>","<SE>"),$meta['meeting_level']['apply']);
								$level = explode('/',$reglv);  
								$links = array();
								do{
								  $series = join('/',$level);
								  $term = array_pop($level);  
								  $search = array();
								  $search['query'][] = array('field'=>'meeting_level','value'=>strip_tags($series),'attr'=>'+'); 	 
								  $links[] = "<a class='search_series' href='index.php?act=Archive/Search/".rawurlencode(str_replace('/','*',base64_encode(json_encode($search))))."' target='_blank' >".$term."</a>"; 
								}while(count($level)>1);
								echo preg_replace(array("/<SS>/","/<SE>/"),array('<search>','</search>'),$level[0].'/'.join('/',array_reverse($links)));
								?>
								</span>
							  </div>
							  <?php endif; ?> 
							  <?php if(isset($meta['category_level'])): ?>
							  <div class='result_field ' >
								<span class='field_name'> &#187; 分類階層 </span>
								<span class='field_value'> <?php echo $meta['category_level']['apply'];?> </span>
							  </div>
							  <?php endif; ?> 

							  
							<?php endif; ?>
							  
							  <!-- 全宗通用欄位 -->
							  <div class='result_field ' >
								<span class='field_name'> &#187; 資料日期</span><span class='field_value'><?php echo $meta['date_string']['apply'];?></span>
							  </div>
							  
							  <?php if(isset($meta['zong'])&& $meta['zong']['value']=='檔案'): ?>
							  <div class='result_field ' >
								<span class='field_name'> &#187; 密等/解密</span><span class='field_value'><?php echo $meta['@Secret']['apply']; ?></span>
							  </div>
							  <?php endif; ?>
							  
							  <?php if(isset($meta['use_limit'])&&trim($meta['use_limit']['value'])): ?>
							  <div class='result_field ' >
								<span class='field_name'> &#187; <?php echo $meta['use_limit']['field']; ?></span><span class='field_value'><?php echo $meta['use_limit']['apply']; ?></span>
							  </div>
							  <?php endif; ?>
							  <div class='result_field result_active' >
								<span class='field_name'> &#187; 提供方式 </span>
								<span class='field_value'>
								  <span class='option  <?php echo $meta['@ViewMode']['value']; ?>'  apply=''  acckey='<?php echo $meta['@SystemLink']['field'];?>'   ><a target='_new'><?php echo $meta['@ViewMode']['apply']; ?></a></span>
								  - 
								  <?php echo $meta['pageinfo']['apply']; ?>
								</span>
							  </div>
							  
						  </div>
						  
						  <!-- 搜尋欄位 -->
						  <div>  
							  <?php foreach($meta as $FieldTerm => $Data_Cont):  ?>
							  <?php   if( $Data_Cont['match']): //顯示搜尋到的欄位 ?>
							  <div class='result_field ' >
								<span class='field_name'> &#187; <?php echo $Data_Cont['field'];?></span><span class='field_value'><?php echo $Data_Cont['apply'];?></span>
							  </div>
							  <?php   endif; ?>	
							  <?php endforeach; ?> 
						  </div>
						  
						</div>
						
					  </div>
					  <div class='result_body'>
					    <div class='result_descrip'>
						  <div class='result_text'>
						    <?php echo isset($meta['abstract_mask']) ? $meta['abstract_mask']['apply'] : $meta['abstract']['apply']; ?>
						  </div>
						  <div class='result_source'>
						    <?php echo isset($meta['fileno']) ? $meta['fileno']['apply'] : ''; ?>
						  </div>
						</div>
					  
					  </div>
					  <div class='result_floder'>
						<div class='result_function tr_like' >
						  <!--
						  <ul class='res_tag_area'>
							<li class='tag_option' >
							  <span class='tag_unset'>
								<a class='del_option' name='' value=''></a> 
							  </span> 
							  <span class='tag_term'>TAG</span>
							</li>
						  </ul>
						  -->
						</div>
						
						<?php if(isset($meta['@Refer'])):?>
						<div class='result_relate_block'>
						  <h2 class='related_border'><?php echo $meta['@Refer']['field'];?></h2>
						  <ul class='related_list'>
						  <?php foreach($meta['@Refer']['value'] as $store_no => $refer_content): ?>
						    <li>
						      <h3>
							    <label>《<?php echo $refer_content['type'];?>》</label>
							    <span class='option  online'  apply=''  acckey='<?php echo $refer_content['linkkey'];?>'   ><a target='_new'><?php echo $store_no; ?></a></span>
								<i> 相似度：<?php echo $refer_content['rate'];?>%</i>
							  </h3>
                              <p><?php echo System_Helper::short_string_utf8($refer_content['info'],150)?></p>
						    </li>
						  <?php endforeach; ?>
						  </ul>
					    </div>
					    <?php endif; ?>
					   
					  </div>
					  <?php endif; ?>
					  
					  
					</div>
				  </div>
				<?php endforeach; ?>
			  </div>  <!-- END result block -->
			  
			  <!-- 分頁 -->
			  <div class='page_block'>
				<div class='result_collect'>
					<span class='option_field'> <input type='checkbox' class='result_selected_all' >全選</span>
				</div>
				<span class='record_pages'>
				  <a class='page_tap page_to' page='<?php echo $data_page['prev'];?>' > &#171; </a>
				  <span class='page_select'>
				  <?php foreach($data_page['list'] as $p=>$limit ): ?>
				  <a class="page_tap <?php echo $p==$data_page['now'] ? 'page_now':'page_to'; ?>" page="<?php echo $limit;?>" ><?php echo $p; ?></a>
				  <?php endforeach; ?>
				  </span>
				  <a class='page_tap page_to' page='<?php echo $data_page['next'];?>' > &#187; </a>
				  ，跳至
				  <select class='page_jump'>
					<optgroup label="首尾頁">
					  <option value='<?php echo $data_page['top'];?>' >首頁</option>
					  <option value='<?php echo $data_page['end'];?>' >尾頁</option>
					</optgroup>
					<optgroup label="-">
					  <?php foreach($data_page['jump'] as $p=>$limit ): ?>
					  <option value="<?php echo $limit; ?>"  <?php echo $p==$data_page['now'] ? 'selected':''; ?> ><?php echo 'P.'.$p; ?></option>
					  <?php endforeach; ?>
					</optgroup>					  
				  </select>
				</span>	
			  </div>
			</div>
			<?php endif; ?>
		  </div>
		</div>
	  
	  
	  
	  </div> <!-- end of archive-content -->
	</div> <!-- end of wrap -->
	
	<div id='archive-footer'>  <!-- 置底 -->
	<?php require_once('client_area_footer.php'); ?>  
	</div>
    
	<!-- 框架外結構  -->
	
	
	<!-- Relate Member Viewer -->
	<div class='system_member_area'>
	    <div class='app_container'>
		  <div id='member_block'>
		    <h1>
			  <div class='mbr_header'>
			    <span class='mbr_type'>議員傳記</span> 
				<span class='mbr_title'></span> 
			  </div>
			  <span class='mbr_close option' title='關閉'><i class="fa fa-times-circle" aria-hidden="true"></i></span>
			</h1>
			<div class='mbr_contents'>
			  <div class='mbr_information'>
			    <div id='mbr_photo' ></div>
				<div class='mbr_meta' >
				  <div id='mbr_namenyear'>
				    <span id='mbr_name'></span>
					<span id='mbr_staff'></span>
					( <span id='mbr_year'></span> )
				  </div>
				  <div id='mbr_experience'></div>
				  <div id='mbr_statistic'></div>  
				</div>
			  </div>
			  <div class='mbr_descrip'>
			    <p id='mbr_history'></p>
				<h1>參考資料：</h1>
				<p id='mbr_reference'></p>
			  </div>
			</div>
			<div class='mbr_footer'>
			  <div>
			    <span class='mbr_time'>  </span>
				資料來源：
				<span class='mbr_from'>  </span>
			  </div>
			  <div>
			    <span class='mbr_counter'>  </span>
			  </div>
			</div>
		  </div>
        </div>
	</div>
	
	
	
	<!-- Relic Photo Viewer -->
	<div class='system_relic_viewer'>
	  <div class='viewer_container'>
	    <h1>
		  <span class='relic_title'>title</span>
		  <span class='option' id='act_close_relic_viewer' ><i class="fa fa-times" aria-hidden="true"></i></span>
		</h1>
	    <div class='photo_block'>
		  <div class='photo_display' >
		  photo
		  </div>
		  <a class='photo_switch option' id='toprev' ><i class="fa fa-chevron-circle-left" aria-hidden="true"></i></a> <!--prev -->
		  <a class='photo_switch option' id='tonext' ><i class="fa fa-chevron-circle-right" aria-hidden="true"></i></a> <!--next -->
		</div>
	    <ul class='photo_selecter'></ul>
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
		<div class='feedback_bottom' >
		  <button type='button' class='cancel btn_feedback' id='act_feedback_cancel' > <i class="fa fa-trash-o" aria-hidden="true"></i>  取 消 </button>
		  <button type='button' class='active btn_feedback' id='act_feedback_submit' > <i class="fa fa-paper-plane-o" aria-hidden="true"></i>  送 出 </button>		
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