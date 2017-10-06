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
	
	<script type="text/javascript" src="js_library.js"></script>
	<script type="text/javascript" src="js_client.js"></script>
	<!-- PHP DATA -->
	
	<?php
	/*-- 介面資訊 --*/
	$page_info = isset($this->vars['server']['info']) ? $this->vars['server']['info'] : ''; 
	
	?>
	
	<style type="text/css">
	  
	  div.system_body_area{
		display:block;  
	  }
	  .site_link_block{
		padding:10px;  
	  }	
		h1{
		  font-size:1.3em;	 
		  font-weigth:bold;
		  line-height:1.5em;	  
		  border-bottom:1px #6c272d solid;
		  padding-bottom:8px;
		  margin-bottom:8px;
		}
		
		ul.linkcontainer {
		  display:flex;
		  flex-wrap:wrap;
		  justify-content:space-between;
		}
		ul.linkcontainer > li{
		  width:48%;
          padding:10px 0;
		  background:rgba(255,255,255,0.6);
		  border-radius:5px;
		  margin:10px 0;
		  display:flex;
		  align-items:center;
		}
		ul.linkcontainer > li >img{
		  width:170px;	
		  display:inline-block;
		  margin:0 5px;
		}
		
    </style>
  </head>
  <body>
    <div id='page-wrap' class='system_body_area'> <!-- 整體區塊 -->
	  <div id='archive-content' > <!-- 上半身 -->
		
		<header class='navbar'>
		  <div class='container'>
			<div id='navbar-header'>
			  <img  id='system_mark' src='theme/image/mark_tpa.png' />
			  <span id='system_title' ><?php echo _SYSTEM_HTML_TITLE; ?></span>
			</div>
			<ul id='navbar-manual'>
			  <li ><a href='index.php?act=Landing/index'>首頁</a></li>
			  <li ><a href='index.php?act=Archive/index'>資料檢索</a></li>
			  <li ><a href='index.php?act=Landing/account'>帳號註冊</a></li>
			  <li ><a href='index.php?act=Landing/helper'>使用說明</a></li>
			  <li atthis='1'>相關連結</li>
			  <li ><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <a id='user_feedback'>系統回報</a></li>
			</ul>
		  </div>
		</header>
		<div class='container'>
		  <div class='site_link_block'>
		    <h1> 相關連結 </h1>
		    <ul class='linkcontainer'>
		      <li> <img src='theme/image/sitemark4.png' /><a href='http://www.tpa.gov.tw' target=_blank >臺灣省諮議會</a>  </li>
			  <li> <img src='theme/image/sitemark2.png' /><a href='http://digital.ntu.edu.tw' target=_blank >臺大數位人文研究中心</a> </li>
		      <li> <img src='theme/image/sitemark1.png' /><a href='http://journal.tpa.gov.tw/' target=_blank >臺灣地方議會議事錄總庫</a> </li>
			  <li> <img src='theme/image/sitemark3.jpg' /><a href='http://ccouncil.tw/' target=_blank >臺灣議事資料開放平臺</a> </li>
		    </ul>
		  </div >
		</div> 
		  
	  
	  
	  
	  </div> <!-- end of archive-content -->
	</div> <!-- end of wrap -->
	
	<div id='archive-footer'>  <!-- 置底 -->
	<?php require_once('client_area_footer.php'); ?>  
	</div>
    
	<!-- 框架外結構  -->
	
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
		  <a class='fbh_option' id='act_feedback_close' title='關閉' ><i class="fa fa-times" aria-hidden="true"></i></a>
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