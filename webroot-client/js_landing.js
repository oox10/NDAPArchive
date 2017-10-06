  
 
  $( window ).on('load',function () {   //  || $(document).ready(function() {
	
	
	/*================================*/
	/*--    Landing Function set    --*/
	/*================================*/
	
	
	//-- page link 
	$('#system_mark').click(function(){
	  location.href='index.php';	
	});
	
	//-- form initial 首頁使用者選單
	$('.formmode').click(function(){
	  var target_dom = $(this).data('dom')
	  $('.formblock').hide();
	  $('#'+target_dom).show();
	  $('.formmode.atthis').removeClass('atthis'); 
	  $(this).addClass('atthis');
	});
	
	if($('.formmode.atthis').length){
	  $('.formmode.atthis').trigger('click'); 
    }
	
	//-- initial account login
	if(location.hash.match('#signin')){
	  $(".formmode[data-dom='login_form']").trigger('click');	
	}
	
	
	//-- go to recover
	$('#act_forgot').click(function(){
      $('#check_form').hide();
      $('#recover_form').show();
	});
	
	
	//-- client active password reset mail
	$('#act_recover').click(function(){
	  
	  // initial
	  $('input._keyin').removeClass('form_error');
	  
	  // check input 
	  if(!$('#reg_mail').val()){
	    $('#reg_mail').addClass('form_error').focus();
		system_message_alert('error',"請輸入註冊信箱");
		return false;
	  }
	  
	  if( !$('#reg_name').val()){
	    $('#reg_name').addClass('form_error').val('').focus();
		system_message_alert('error',"請輸入註冊姓名");
		return false;
	  }
	  
	  if(!$('#rcv_captcha_input').val()){
	    $('#rcv_captcha_input').addClass('form_error').focus();
		system_message_alert('error',"請輸入驗證碼");
		return false;
	  }
	  
	  var register_info = {};
	  register_info['regist_mail']  = $('#reg_mail').val();
	  register_info['regist_name']  = $('#reg_name').val();
	  register_info['verification'] = $('#rcv_captcha_input').val();
	  
	  var pass_data = encodeURIComponent(Base64.encode(JSON.stringify(register_info)));
	  var $dom = $(this);
	 
	  $.ajax({
        url: 'index.php',
		type:'POST',
	    dataType:'json',
	    data: {act:'Landing/reseter/'+pass_data},
	    beforeSend: function(){ active_loading($dom,'initial'); $('._keyin').prop('disabled',true);},
        error: function(xhr, ajaxOptions, thrownError){ console.log( ajaxOptions+" / "+thrownError); },
	    success: function(response) {
		  if(response.action){
			system_message_alert('alert','已發送密碼重設信件');
		  }else{
			system_message_alert('error',response.info);
		  }
		},
		complete:		function(){  }
	  }).done(function(r) { 
	    active_loading($dom,r.action); 
	    $('.reset_capture').trigger('click');
	    $('input._keyin').val('');
		$('input._keyin').prop('disabled',false);	
	  
	    // reset 驗證碼
		document.getElementById('captcha_rcv').src=document.getElementById('captcha_rcv').src + '?' + (new Date()).getMilliseconds();
		$('#rcv_captcha_input').val('');  
	  });
	  
	});
	  
	
	//-- open sign up
    $('#act_opensignup').click(function(){
	   $('#tosignup').trigger('click');
    });
	
	
	//-- go to index
	$(document).on('click','.act_gohome',function(){
      location.href='./';
	});
	
	//-- cancel 
	$('button.cancel').click(function(){
	  if($(this).attr('from')=='forgot'){
		$('#check_form,#recover_form').toggle();
	  }else{
		location.href='./';   
	  }
	});
	
	
	//-- guest login
	$('#act_guestlogin').click(function(){
	  location.href='index.php?act=Landing/guest'
	});
	
	
	/*================================*/
	/*--    Regist Function set    --*/
	/*================================*/
	
	// user declare
	if($('.declare_option').length){
	  $('#declare_agree').click(function(){
	    $('.regdeclare_block').hide();
	  });
	  
	  $('#declare_disagree').click(function(){
	    //alert("您不同意本聲明，將回到系統首頁.");
	    location.href='index.php?act=Account';
	  });
	  
	}
	
	// user sign up check
    if($('#act_reg_sent').length){
      
	  $('#act_reg_sent').click(function(){
	    
		var reg_check = {};
		$('._wrong').removeClass('_wrong');
		$('._regist').each(function(){  
		  console.log($(this).attr('id'));
		  
		  switch($(this).attr('id')){
		    case 'user_name'	: if( $(this).val().length ){reg_check[$(this).attr('id')]=$(this).val();  }else{$(this).parents('.form_raw').addClass('_wrong');} break;
			case 'user_mail'	: if( $(this).val().match(/^[\w\d\.\_\-]+@[\w\d\.]+$/)){ reg_check[$(this).attr('id')]=$(this).val();   }else{$(this).parents('.form_raw').addClass('_wrong');} break;
			case 'user_age'	    : if( $(this).val().length ){reg_check[$(this).attr('id')]=$(this).val();  }else{$(this).parents('.form_raw').addClass('_wrong');} break;
			case 'user_organ'	: if( $(this).val().length ){reg_check[$(this).attr('id')]=$(this).val();  }else{$(this).parents('.form_raw').addClass('_wrong');} break;
		  }
		});
		
	    // 取得主修資訊
		var major = [];
		$('.user_major').each(function(){
		  if( ($(this).attr('type')=='checkbox' || $(this).attr('type')=='radio') && $(this).prop('checked') ){
			major.push($(this).val());
		  }else if( $(this).attr('type')=='text' && $(this).val() ){
			major.push($(this).val());    
		  }
		});
		
		if(major.length){
		  reg_check['user_major'] = major.join(';');	
		}else{
		  $(".form_raw[data-field='user_major']").addClass('_wrong');
		}
		
		
		
		// 取得職業資訊
		var staff = [];
		$('.user_staff').each(function(){
		  if( ($(this).attr('type')=='checkbox' || $(this).attr('type')=='radio') && $(this).prop('checked') ){
			staff.push($(this).val());
		  }else if($(this).attr('type')=='text' && $(this).val()){
			staff.push($(this).val());    
		  }
		});
		
		if(staff.length){
		  reg_check['user_staff'] = staff.join(';');	
		}else{
		  $(".form_raw[data-field='user_staff']").addClass('_wrong');
		}
		
		
		if($('._wrong').length){
	      // reset 驗證碼
		  document.getElementById('captcha_img').src=document.getElementById('captcha_img').src + '?' + (new Date()).getMilliseconds();
		  $('#reg_captcha_input').val('');  
		  system_message_alert('','請將資料填寫完整');
		  return false;	
		}
		
		if($('#reg_captcha_input').val().length != 4 ){
		  $('#reg_captcha_input').focus();
		  system_message_alert('',"請輸入正確的驗證碼");
		  // reset 驗證碼
		  document.getElementById('captcha_img').src=document.getElementById('captcha_img').src + '?' + (new Date()).getMilliseconds();
		  $('#reg_captcha_input').val('');
		  return false;
		}
		
		// lock all field
		$('input._form').prop('disabled',true);
		
		//送出表單
		if(!confirm("確認送出註冊申請？")){	
		  return false;  
		}	
		
		var captcha = $('#reg_captcha_input').val();
		var reg_data = encodeURIComponent(Base64M.encode(JSON.stringify(reg_check)));
			
		$.ajax({
          url: 'index.php',
	      type:'POST',
	      dataType:'json',
	      data: {act:'Landing/signup/'+captcha+'/'+reg_data},
		  beforeSend: function(){  system_loading(); 
		    $('#reg_submit').hide();
			$('#reg_process').html('帳號註冊中....').show();
		  },
		  error: function(xhr, ajaxOptions, thrownError) { 
		    system_message_alert('',"頁面失敗，請重新送出"); 
		    $('#reg_submit').show();
			$('#reg_process').empty().hide();
			$('._form').prop('disabled',false);
		  },
	      success: function(response) {
			if(response.action){
			  $('#reg_process').html('<span>帳號註冊成功，請靜候審核通知信件</span> <a class="option act_gohome" >回首頁</a>');
              system_message_alert('alert','帳號註冊成功，請靜候審核通知信件 ');
			}else{
			  $('._form').prop('disabled',false);
			  $('#reg_submit').show();
			  $('#reg_process').empty().hide(); 
			  $.each(response.data , function(key,err){
				$('#'+key).addClass('_wrong').focus().val(err);
			  });
			  system_message_alert('',response.info)
			}  
		  },
		  complete:function(){
			document.getElementById('captcha_img').src=document.getElementById('captcha_img').src + '?' + (new Date()).getMilliseconds();
		    $('#reg_captcha_input').val('');	
			system_loading();
		  }
        });
		
	  });
	  
    }
   
   
    // 重新輸入
    if($('#reg_act_reset').length){
      $('#reg_act_reset').click(function(){
	  
		$('input.reg_cont').val('');
		$('textarea.reg_cont').val('');
		$("input[name='user_staff']").prop('checked',false);
		$("input#user_staff_other").val('');
		$('span.reg_data').html('');
		
		// reset 驗證碼
		document.getElementById('captcha').src=document.getElementById('captcha').src + '?' + (new Date()).getMilliseconds()
		$('#captcha_input').val('');
	  });
    }
	
	
	//-- admin login 
	$('#act_signin').click(function(){

	  // initial
	  $('.form_error').removeClass('form_error');
	  
	  // check input 
	  if(!$('#uname').val()){
	    $('#uname').addClass('form_error').focus();
		system_message_alert('error','請填寫帳號');
		return false;
	  }
	  
	  if(!$('#upass').val()){
	    $('#upass').addClass('form_error').focus();
		system_message_alert('error','請填寫密碼');
		return false;
	  }
	  
	  var login_info = {};
	  login_info['account']  = $('#uname').val();
	  login_info['password'] = $('#upass').val();
	  
	  var login_data = encodeURIComponent(Base64.encode(JSON.stringify(login_info)));
	 
	  $.ajax({
        url: 'index.php',
		type:'POST',
	    dataType:'json',
	    data: {act:'Landing/signin/'+login_data},
	    beforeSend: function(){ system_loading(); },
        error: function(xhr, ajaxOptions, thrownError){ console.log( ajaxOptions+" / "+thrownError); },
	    success: function(response) { 
		  $('.form_error').removeClass('form_error');
		  
		  if(!response.action){ 
		    system_message_alert('error',response.info);
		    $('#upass').val('').focus();
			system_loading();
		    return 0
		  }
		  if(typeof response.data.repass != 'undefined' ){
			location.href='index.php?act=Landing/start/'+response.data.repass;
		    return 1;	
		  }else if( typeof response.data.lgkey != 'undefined' ){
			location.href='index.php?act=Landing/inter/'+response.data.lgkey;
			return 1;	
		  }else{
			system_message_alert('error','未知錯誤，請洽管理人員');
		  }
		},
		complete:		function(){ }
	  }).done(function() {   });
	});
	
	
	
	
	//-- client reset password submit
	$('#act_repass').click(function(){
	  
	  // initial
	  $('input._keyin').removeClass('_wrong');
	  
	  // check input 
	  if(!$('#upass').val()){
	    $('#upass').addClass('_wrong').focus();
		system_message_alert('error',"請輸入密碼");
		return false;
	  }
	  
	  if( $('#upass_chk').val() != $('#upass').val()  ){
	    $('#upass_chk').addClass('_wrong').val('').focus();
		system_message_alert('error',"2次輸入的密碼不相符，請重新輸入");
		return false;
	  }
	  
	  var register_info = {};
	  register_info['regist_password01'] = $('#upass').val();
	  register_info['regist_password02'] = $('#upass_chk').val();
	  
	  var $dom = $(this);
	  var pass_data = encodeURIComponent(Base64.encode(JSON.stringify(register_info)));
	  
	  $.ajax({
        url: 'index.php',
		type:'POST',
	    dataType:'json',
	    data: {act:'Landing/repass/'+pass_data},
	    beforeSend: function(){ active_loading($dom,'initial'); $('._keyin').prop('readonly',true);  },
        error: function(xhr, ajaxOptions, thrownError){ console.log( ajaxOptions+" / "+thrownError); },
	    success: function(response) {
		  if(response.action){
			active_loading($dom,response.action);  
			system_message_alert('alert','帳號已啟動,請重新登入');
		    alert("密碼已經更新，關閉後將跳轉至登入頁面");
			location.href = 'index.php?act=Landing/account#signin'
		  }else{
			$('._keyin').prop('readonly',false);  
		    system_message_alert('error',response.info);
		  }
		},
		complete:		function(){ }
	  }).done(function(r) { active_loading($dom,r.action); });
	});
	
	
	/*===============================*/
	/*-- Announcement Function set --*/
	/*===============================*/
	
	$('#act_switch_post_mode').click(function(){
	  var more_flag = parseInt($('.billboard').attr('more'));	  
      $('.billboard').attr('more',parseInt(1 - more_flag));
	});
	
	
	
	$('.post').click(function(){
	  var dom = $(this);
	  var data_no = dom.attr('no');
	  
	  if(!parseInt(data_no)){
		system_message_alert('',"尚未選擇資料");  
	    return false;  
	  }
	  
	  $.ajax({
		  url: 'index.php',
		  type:'POST',
		  dataType:'json',
		  data: {act:'Landing/getann/'+data_no},
		  beforeSend: 	function(){ system_loading();  },
		  error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		  success: 		function(response) {
			if(response.action){  
			  $(this).addClass('viewed');	
	          
			  var post = response.data;
			  
			  $('.ann_type').text(post.post_type);
			  $('.ann_title').text(post.post_title);
			  $('.ann_contents').html(Base64.decode(post.post_content));
			  $('.ann_time').text(post.post_time_start);
			  $('.ann_from').text(post.post_from);
			  $('.ann_counter').text(post.post_hits);
			  
			  $('.system_announcement_area').css('display','block');	
			}else{
			  system_message_alert('',response.info);
			}
		  },
		  complete:		function(){   }
	  }).done(function() { system_loading();   });
	  
	});
	
	$('.ann_close').click(function(){
	  $('.viewed').removeClass('viewed');
	  $('.system_announcement_area').css('display','none');	
	});
	  
	//-- post emergency popout
	if( $(".post[popout='1']").length ){
	  $(".post[popout='1']:eq(0)").trigger('click');	
	}
	
	
	
	
	
	
  }); /* << end of window load >> */    
  
 