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
	
	$post_list = isset($this->vars['server']['data']['post']) ? $this->vars['server']['data']['post'] : ''; 
	
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
		  <ul class='form_manual'>
		    <li class='option formmode  atthis' data-dom='guest_form'>訪客登入</li>
			<li class='option formmode ' data-dom='login_form'>帳號登入</li>
			<li class='option formmode ' id='tosignup' data-dom='regist_form'>註冊帳號</li>
		  </ul>
		  <div class='formblock' id='guest_form'>
		    <h1>史料總庫使用規範</h1>
		    <div class='form_raw' >
			  <p>本史料總庫將所有目錄資料全數提供搜尋，並依據資料類型提供相應的使用權限：</p>
			  <ul>
			    <li>公報、議事錄、議員傳記：不須登入即可閱覽數位檔案</li>
				<li>檔案：提供註冊帳號閱覽</li>
				<li>議事影音：提供註冊帳號於省議會內閱覽</li>
				<li>議員照片：提供註冊帳號於省議會內閱覽</li>
			  </ul>
			</div>
		    
            <div class='form_func'>
			  <button class='submit green'>註冊帳號</button>
			  <button class='submit blue' id='act_guestlogin' ><i class="fa fa-user-o" aria-hidden="true"></i> 訪客進入</button>
			</div>			
		  </div>
		  <div class='formblock ' id='login_form'>
		    <div id='check_form' >
			  <h1>資料庫檢索系統登入</h1>		
              <div class='form_raw' data-field='uname' >
			    <label>使用者帳號</label>
				<input type='text' id='uname'  placeholder="預設為註冊email"   />
			  </div>	
			  <div class='form_raw ' data-field='upass'>
			    <label>使用者密碼</label>
				<input type='password' id='upass'  placeholder="password" />
			  </div>
			  <div class='form_func'>
				<a class='option forgot' id='act_forgot'> 忘記密碼 </a>
				<span>
				  <button class='cancel'  >取消</button>
				  <button class='submit blue' id='act_signin' >  登入 </button>
				</span>
			  </div>
			</div>
			<div id='recover_form' >
			  <h1>重新寄發密碼認證</h1>		
              <div class='form_raw' data-field='reg_name' >
			    <label>申請人姓名</label>
				<input type='text'  class='_keyin' id='reg_name' placeholder="註冊姓名"   />
			  </div>	
			  <div class='form_raw' data-field='reg_mail'>
			    <label>申請人信箱</label>
				<input type='email' class='_keyin' id='reg_mail' placeholder="電子郵件信箱" />
			  </div>
			  <div class='form_func'>
			    <span class='captcha' >
				  <input type="text" id='rcv_captcha_input' class='_keyin '  name="Turing" value="" size='5'/>
				  <img src="tool/captcha/check.php" id="captcha_rcv">
				  <a class='reset_capture' href="#" onclick="document.getElementById('captcha_rcv').src = document.getElementById('captcha_rcv').src + '?' + (new Date()).getMilliseconds()" title='新驗證碼'><i class="fa fa-refresh" aria-hidden="true"></i></a>				
			    </span>  
				<span>
				  <button class='cancel'  >取消</button>
				  <button class='submit blue' id='act_recover'>查詢</button>
				</span>
			  </div>
			</div>
		  
		  </div>
		  <div class='formblock' id='regist_form'>
		    <h1>系統帳號註冊</h1>
		    <div class='form_raw' data-field='user_name' >
			  <label>使用者姓名</label>
			  <input type='text' class='_regist _form' id='user_name'  placeholder="使用者證件姓名"   />
			</div>
            <div class='form_raw' data-field='user_mail' >
			  <label>使用者Email(預設為登入帳號)</label>
			  <input type='text' class='_regist _form' id='user_mail'  placeholder="email將視為註冊帳號"   />
			</div> 			
            <div class='form_raw' data-field='user_age' >
			  <label>年齡</label>
			  <select id='user_age' class='_regist _form'  >
			    <option value='' selected>使用者年齡</option>
				<option value="-19" > 19歲以下 </option>
				<option value="20-29" > 20-29歲 </option>
				<option value="30-39" > 30-39歲 </option>
				<option value="40-49" > 40-49歲 </option>
				<option value="50-59" > 50-59歲 </option>
				<option value="60-" > 60歲以上 </option>
			  </select>
			</div>
			<div class='form_raw' data-field='user_major' >
			  <label>主修</label>
			  <div class='form_selecter'>
			  <input type='checkbox' class='user_major _form' name='master' value='歷史'>歷史
			  <input type='checkbox' class='user_major _form' name='master' value='政治'>政治 
			  <input type='checkbox' class='user_major _form' name='master' value='檔案學'>檔案學
			  <input type='checkbox' class='user_major _form' name='master' value='圖書資訊'>圖書資訊
			  </div >
			  <input type='text'  class='user_major _form'   value='' placeholder='其他：請填寫主修資訊' />
			</div>
			
			<div class='form_raw' data-field='user_staff' >
			  <label>職業</label>
			  <div  class='form_selecter'>
			  <input type='radio' name='pro' class='user_staff _form' value='學生'>學生 
			  <input type='radio' name='pro' class='user_staff _form' value='公'>公 
			  <input type='radio' name='pro' class='user_staff _form' value='教'>教 
			  <input type='radio' name='pro' class='user_staff _form' value='軍'>軍
			  <input type='radio' name='pro' class='user_staff _form' value='商'>商
			  <input type='radio' name='pro' class='user_staff _form' value='服務業'>服務業
			  </div  >
			  <input type='text' value='' class='user_staff _form' placeholder='其他：請填寫職業資訊' />
			</div>
			<div class='form_raw' data-field='user_organ' >
			  <label>服務單位</label>
			  <input type='text' class='_regist _form' id='user_organ'  placeholder="請填寫服務單位或學校"   />
			</div> 
            <div class='form_func'>
			  <span id='reg_submit'>
			    <span class='captcha' >
				  <input type="text" id='reg_captcha_input' class='_form'  name="Turing" value="" />
				  <img src="tool/captcha/code.php" id="captcha_img">
				  <a class='reset_capture' href="#" onclick="document.getElementById('captcha_img').src = document.getElementById('captcha_img').src + '?' + (new Date()).getMilliseconds()" title='新驗證碼'><i class="fa fa-refresh" aria-hidden="true"></i></a>				
			    </span>  
				<span>
			      <button class='cancel'  >取消</button>
			      <button class='submit blue' id='act_reg_sent'>註冊</button> 
			    </span>
			  </span>
			  <span id='reg_process'>
				
			  </span>
			  <span id='reg_finish'>
				
			  </span>
			</div>			
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
