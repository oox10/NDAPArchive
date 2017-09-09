<!DOCTYPE HTML>
<html>
  <head>
    <meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	
	<title><?php echo defined('_SYSTEM_HTML_TITLE') ? _SYSTEM_HTML_TITLE:'資料庫';?></title>
	
	<!-- CSS -->
	<link type="text/css" href="tool/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_default.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_system.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_client.css" />
	<link rel="stylesheet" type="text/css" href="theme/css/css_landing.css" />
	
	<!-- JS -->
	<script type="text/javascript" src="tool/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="tool/canvasloader-min.js"></script>	
	
	<!-- TOOL -->
	<script type="text/javascript" src="tool/jquery-date-range-picker/moment.min.js"></script>
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_client.js"></script>
	<script type="text/javascript" src="js_landing.js"></script>
	
	<!-- PHP DATA -->
	
	<?php
	$page_info = isset($this->vars['server']['info']) ? $this->vars['server']['info'] : ''; 
	$post_list = [];
	 
	
	$area_group_flag = '';
	
	?>
	
  </head>
  
  <body>
    <header class='navbar '>
	  <div class='container'>
	    <div id='navbar-header'>
		  <img  id='system_mark' src='theme/image/mark_tpa.png' />
		  <span id='system_title' ><?php echo _SYSTEM_HTML_TITLE;?></span>
		</div>
		<ul id='navbar-manual'>
		  <li>最新消息</li>
		  <li>檢索系統</li>
		  <li>操作手冊</li>
		  <li>相關連結</li>
		</ul>
	  </div>
	</header>
    	
	<div class='system_border_area'>
	  <div class='container border-block'>
	    
		<div class='introduction' >
	      <h1>
		    <span>臺灣省議會<br>史料總庫</span>
		  </h1>
	      <p>
		    臺灣省議會史料總庫，目前版本為V4(測試版)，內容包含民國35年至民國87年間議會相關檔案、議事錄與公報，共 448,645 筆資料，影像收納範圍則為民國35年到民國87年省虛級化為止。目前已全數匯入資料庫中。
		  </p>
		</div>
		
		<div class='registration' >
		   
		  <!-- 審核狀態 -->
		  <div class='booking_step' id='submit_status' style='display:block;'>
		    <h1>
			  <span class='step_title'>發生錯誤 </span>
			  <span class='step_option'>
			    <button type='button' class='active' onclick="window.history.go(-1);"   > 回到上一頁 </button>	
			  </span>
			</h1>
			
			<section>
			  <div class="declaration" >
                <h2>發生時間：</h2>
				<p class='rule'><?php echo date('Y-m-d H:i:s'); ?></p>
				<h2>錯誤訊息：</h2>
				<p class='rule'><span style='font-weight:bold;font-size:1em;color:red;'><?php echo $alert_message; ?></span></p>
			  </div>
			</section>
		  </div>
		</div>
		
		
		
		
	  </div>
	</div>	
		
    <div class='system_body_area'>
	  
	  <div class='container information-block' >
	    <div class='billboard' id='collection'>
		  <h1>
		    <label>收錄內容</label>
			<span class='more ' >總計6大類, 499,341筆資料</span>
		  </h1>
		  <div class='zong_descrip '>
			  <div class='zong_set'>
				<div class='cover'>
				  <img src='theme/image/thumb_data01.jpg' />
				</div>
				<div class='intro'>
				  <h3 >
					<span class='ztitle'>臺灣省議會檔案</span>
					<span class='zrange'> </span>
				  </h3>
				  <div class='descrip'>
					<div>1946~1998 省議會檔案數位化資料，共98,795筆</div>
				  </div>
				</div>
			  </div>
			  
			  <div class='zong_set'>
				<div class='cover'>
				  <img src='theme/image/thumb_data02.jpg' />
				</div>
				<div class='intro'>
				  <h3 >
					<span class='ztitle'>臺灣省議會議事錄</span>
					<span class='zrange'>  </span>
				  </h3>
				  <div class='descrip'>
					<div>1946~1998 省議會議事錄數位化資料，共121,863筆</div>
				  </div>
				</div>
			  </div>
			  <div class='zong_set'>
				<div class='cover'>
				  <img src='theme/image/thumb_data03.jpg' />
				</div>
				<div class='intro'>
				  <h3 >
					<span class='ztitle'>臺灣省議會公報</span>
					<span class='zrange'> </span>
				  </h3>
				  <div class='descrip'>
					<div></div>
					<div>1959~1998 省議會時期公報數位化資料，共227,987筆</div>
				  </div>
				</div>
			  </div>
			  <div class='zong_set'>
				<div class='cover'>
				  <img src='theme/image/thumb_data04.jpg' />
				</div>
				<div class='intro'>
				  <h3 >
					<span class='ztitle'>議員小傳&前傳</span>
					<span class='zrange'> </span>
				  </h3>
				  <div class='descrip'>
				   省參議會、臨時省議會、省議會三個時期，369位議員傳記 
				  </div>
				</div>
			  </div>
			  <div class='zong_set'>
				<div class='cover'>
				  <img src='theme/image/thumb_data05.jpg' />
				</div>
				<div class='intro'>
				  <h3 >
					<span class='ztitle'>臺灣省議會照片</span>
					<span class='zrange'>  </span>
				  </h3>
				  <div class='descrip'>
					收錄省議會議員議事活動等相關照片資料
				  </div>
				</div>
			  </div>
			  <div class='zong_set'>
				<div class='cover'>
				  <img src='theme/image/thumb_data05.jpg' />
				</div>
				<div class='intro'>
				  <h3 >
					<span class='ztitle'>議事影音</span>
					<span class='zrange'>  </span>
				  </h3>
				  <div class='descrip'>
					收錄省議會影音資料
				  </div>
				</div>
			  </div>
		  </div>
		  
		  
		</div>
		<div class='billboard' id='announcement' more='0' >
	      <h1>
		    <label>最新消息</label>
		    <?php if(count($post_list )>6): ?>
			<span class='more option' id='act_switch_post_mode' ><i class="fa fa-angle-double-right" aria-hidden="true"></i> <i class='more' title='顯示所有公告'>MORE</i><i class='hide' title='顯示前列公告'>HIDE</i> </span>	
			<?php endif; ?>
		  </h1>
		  <div class='news_block' >
		    <?php foreach($post_list as $post): ?>   
            <div class='post' no='<?php echo intval($post['pno']);?>' top='<?php echo $post['post_level'] > 2 ? 1 : 0; ?>' mark="<?php echo $post['post_type']; ?>" popout='<?php echo $post['post_type']=='緊急通告' ? 1 : 0; ?>'  >
			  <h2>
			    <span class='post_date' > <?php echo substr($post['post_time_start'],0,10); ?></span>
				<span class='post_type' > <?php echo $post['post_type']; ?> </span>
				<span class='post_summary' > <?php echo $post['post_title']; ?> </span>
				<span class='post_organ'>  <?php echo $post['post_from']; ?> </span>
				<span class='post_rate' style='width:<?php echo ($post['post_level']-1)*22; ?>px'>  </span>
			  </h2>
			  <div class='post_content'>
			    <?php echo $post['post_title']; ?>
			  </div>
			</div>
		    <?php endforeach; ?>
		  </div>
		</div>
        



		
	  </div>
	  
	  <div class='container ' >
	    
		
	  </div>
	  
	  <!-- area intro -->
	  <div class='container ' >
	    <div>-</div>
	  </div>
	  
	</div>	
	
	<footer>
	<?php include("area_client_footer.php"); ?>    
	</footer>
	
	<!-- 框架外結構  -->
	
	<!-- 公告訊息 -->
	<div class='system_announcement_area'>
	    <div class='container'>
		  <div id='announcement_block'>
		    <h1>
			  <div class='ann_header'>
			    <span class='ann_type'></span> 
				<span class='ann_title'></span> 
			  </div>
			  <span class='ann_close option' title='關閉'><i class="fa fa-times-circle" aria-hidden="true"></i></span>
			</h1>
			<div class='ann_contents'></div>
			<div class='ann_footer'>
			  <div>
			    <span class='ann_time'>  </span>
				From
				<span class='ann_from'>  </span>
			  </div>
			  <div>
			    <span class='ann_counter'>  </span>
			  </div>
			</div>
		  </div>
        </div>
	</div>
	
	<!-- 系統訊息 -->
	<div class='system_message_area'>
	  <div class='message_block'>
		<div id='message_container'>
		  <div class='msg_title'></div>
		  <div class='msg_info'><?php echo $page_info;?></div>
		</div>
		<div id='area_close'></div>
      </div>
	</div> 
    
  </body>
</html>
