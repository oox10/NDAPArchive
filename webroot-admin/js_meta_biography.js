/* [ Admin Meta Piography Type Built Function Set ] */
	
  $(window).on('load',function () {   //  || $(document).ready(function() {		
	
	
	/***-------------------------***/
	/* [ BUILT CONTENTS FUNCTION ] */
	/***-------------------------***/
	
	//-- initial video tag
	/* [editor tool setting] */
	var FroalaTool = [ 'bold', 'italic', 'underline', 'strikeThrough', 'fontFamily', 'fontSize', '|', 'color', 'paragraphStyle', '|', 'insertHR', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'insertLink', 'insertImage', 'insertTable','|', 'clearFormatting','html'];  //, 'paragraphFormat', 'quote' , 'undo', 'redo', 'selectAll','fullscreen', 'html' , '|', '-','insertFile'  //,'fullscreen' 有問題會變白
	
	if($('#META-mbr_history').length){ 
	  $('#META-mbr_history').froalaEditor({
		language: 'zh_tw',  
		iframe: true,
		heightMax: $('.biography_container').height()-79,
        toolbarButtons: FroalaTool
	  });
	  
	  $("a:contains('Unlicensed Froala Editor')").hide();
	}
	
	
	var historyinput  = {};  // 儲存上次的內容
	var dobjectconf   = {};  // 數位檔案設定
	
	//-- meta group switcher
	
	$('.meta_group_sel').click(function(){
	  var meta_group_block = $(this).data('group');

	  if($(this).hasClass('_atthis')){
		return false; 
	  }
	  
      if(meta_group_block=='_all'){
		$('.meta_group_block').addClass('_display');  
	  }else{
		$('._display').removeClass('_display');
        $('li#'+meta_group_block).addClass('_display');		
	  }
	  
	  $('.meta_group_sel').removeClass('_atthis');
	  $(this).addClass('_atthis');
	});
	
	//-- 確認資料是否變更
	function check_meta_modify(){
	  if($('._modify').length){
		if(!confirm("尚有資料變更未儲存，請問要放棄變更資料嗎?")){
		  return false	
		}  
	  }
	  return true;
	}
	
	
	//-- check page_num value
	function check_page_num(){
	  var pnum_s = parseInt($('#page_num_start').val()) ? parseInt($('#page_num_start').val()):0;
	  var pnum_e = parseInt($('#page_num_end').val()) ? parseInt($('#page_num_end').val()):0;
	  pnum_s+=1;
	  pnum_e+=1;
	  
	  if(pnum_s > pnum_e){
		$('#page_num_checked').attr('check','0').text('設定錯誤');
		return false;		
	  }
	  var pager_s = $('option.pager:nth-child('+pnum_s+')');  
	  
	  if(!pager_s.length){
		$('#page_num_checked').attr('check','0').text('起始頁頁面不存在');
		return false;	
	  }
	  
	  var pager_e = $('option.pager:nth-child('+pnum_e+')');  
	  if(!pager_e.length){
		$('#page_num_checked').attr('check','0').text('結束頁頁面不存在');
		return false;	
	  }
	  
	  $('#page_num_checked').attr('check','1').text('');
	}
	
	
	//-- close editer
	$('#edit_close').click(function(){
	  
	  if(!check_meta_modify()) return false;  
	  
	  var editer_dom = $('#edit_close').parents('#metadata_editer');
	  var edit_state = editer_dom.attr('state')	
      switch(edit_state){
		case 'editing':  break;
		default:break;  
	  }
	  editer_dom.css('display','none');
	  initial_record_editer();
	});
	
	//-- change item
	$('.item_switcher').click(function(){
	  
	  if(!check_meta_modify()) return false;  
	  
	  var target_dom = '';
      var moveto_dom = '';
 	  
      if(!$('._target').length){
		system_message_alert('','尚未選擇資料'); 
		return false;
	  }
	  target_dom = $('._target');  
	  switch($(this).data('mode')){
		case 'prev': moveto_dom = target_dom.prev('._data_read'); break;	
	    case 'next': 
		  historyinput['page_file_end'] = $('#page_file_end').val() ? $('#page_file_end').val() : '';
		  moveto_dom = target_dom.next('._data_read'); 
		  break;
		default:system_message_alert('','發生問題，請洽管理者');  
	  }
	  
	  if(!moveto_dom.length){
		system_message_alert('','資料已達端點');
        return false;		
	  }
	  
	  moveto_dom.trigger('click');
	});
	
	
	/* == 資料編輯函數 == */
	
	//-- get data to editer  // 從server取得資料並放入編輯區
	function insert_data_to_meta_form(DataObj){
	  
	  $('._modify').removeClass('_modify');
	  $('._refer').removeClass('_refer');
	  
	  var dom_record  = $('._target');
	  
	  $.each(DataObj,function(field,meta){  
		if(field=='META-_flag_open' || field=='META-_flag_privacy' || field=='META-_flag_secret'){
			$("input#"+field).prop('checked',(parseInt(meta) ? true : false) );    
		}else if(field=='META-mbr_history'){
		  if(  $("._variable[id='"+field+"']").length ){  
			$('#META-mbr_history').froalaEditor('html.set',meta,true);
		  }
	    }else{  
          // 一般欄位編輯
		  if(  $("._variable[id='"+field+"']").length ){
			if($("._variable[id='"+field+"']").hasClass('_update')){
			  $("._variable[id='"+field+"']").val(meta); 
			}else{
			  $("._variable[id='"+field+"']").html(meta); 	
			}
		  }
		}
		
		if(dom_record.length){
		  var record_field = dom_record.children("td[field='"+field+"']");
		  if(field=='_estatus'){
			var no = record_field.text();
			record_field.html( no+" <i class='mark24 built"+meta+"'></i>")
		  }else if( record_field.length && record_field.html() != meta  ){
		    record_field.html(meta);
	      }	
		}
	  });
	
	  // 載入預載資料
	  if( !$('#page_file_start').val() && historyinput['page_file_end'] ){ 
		var target_pager = $("option.pager[value='"+historyinput['page_file_end']+"']"); 
		if( target_pager.next().length){
		  $('#page_file_start').val(target_pager.next().attr('value')).parents('.data_col').addClass('_refer');
		}  
	  }
	    
	  historyinput = {};
	
	}
	
	//-- get data to editer  // 放入參照資料
	function insert_refer_to_meta_form(ReferObj){ 
	  $.each(ReferObj,function(rf,rv){
		var field_id = 'META-'+rf;
		if(!$("._variable[id='"+field_id+"']").length){ return true;	}
		if($("._variable[id='"+field_id+"']").val()!=''){ return true;}
		if(!rv.length){ return true; }
		$("._variable[id='"+field_id+"']").val(rv).parents('.data_col').addClass('_refer');   
	  });
	}
	
	
	//--read item data
	$(document).on('click','._data_read',function(){
	  
	 
      // initial	  
	  $('._target').removeClass('_target');
	  
	  // get value
	  var data_no    = $(this).attr('no');
	  var dom_record = $(this);
	  
	  // active ajax
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	  
	  //initial_record_editer();
	  
	  $.ajax({
          url: 'index.php',
	      type:'POST',
	      dataType:'json',
	      data: {act:'Meta/readmeta/'+data_no},
		  beforeSend: 	function(){ system_loading();  },
          error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	      success: 		function(response) {
		    if(response.action){  
			  
			  
			  dom_record.addClass('_target');
			  
			  var data_load =  response.data.meta.source;
			  data_orl = data_load;
			  
			  insert_data_to_meta_form(data_load);
			  
			  $('#metadata_editer').css('display','flex');
			  
			  location.hash = data_no;
			  $('#system_breadcrumbs').find('li#target_record').remove().end().append("<li class='breadcrumb' id='target_record' >"+data_load['META-mbr_name']+" (編輯中) </li>");
			  
			  // 處理數位檔案
			  var meta_dobjconfig = response.data.meta.dobj;
			  
			  if(meta_dobjconfig.portrait){
				if($('#DOBJ-portrait').length){
				  $('<img/>').attr('src',meta_dobjconfig.portrait.source).appendTo($('#DOBJ-portrait'));	
				}  
			  }
			  
			  
		    }else{
			  system_message_alert('',response.info);
		    }
	      },
		  complete:		function(){   }
	    }).done(function() { system_loading();   });
	  
	});
	
	
	//--import item data
	$(document).on('click','#import_arrange_meta',function(){
	  var collection_arrange_meta = $('#collection_meta').data('refer');
	  insert_refer_to_meta_form(collection_arrange_meta);
      
	  var refer_count = $('._refer').length;
	  if(refer_count){
		system_message_alert('alert',"填入參考資料 "+refer_count+" 筆");  
	  }else{
		system_message_alert('alert',"未匯入任何參考資料");   
	  }
	  
	});
	
	
	//-- save data modify
	$('#save_current_meta').click(function(){
	  
	  // get value
	  var data_no    = $('._target').attr('no');
	  var dom_record = $(this);
	  
	  // active ajax
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	  
	  var modify_data = {};
	  var act_object  = $(this);
	  var checked = true;
	  
	  // option active checked  // 檢查按鈕是否在可執行狀態
	  if( act_object.prop('disabled') ){
	    return false;
	  }
	  
	  
	  // get value
	  $('._update').each(function(){
	    if($(this)[0].tagName=='INPUT' && $(this).attr('type')=='checkbox'){
		  var field_name = $(this).attr('name');
		  modify_data[field_name] = $(this).prop('checked') ? 1 : 0;
		}else{
		  var field_name  = $(this).attr('id');
	      
		  if(field_name=='META-mbr_history'){
			var field_value =  $('#META-mbr_history').froalaEditor('html.get');  
		  }else{
			var field_value = $(this).val();  
		  }	
		  
		  modify_data[field_name] = field_value;  
		  if( $(this).parents('.data_value').prev().hasClass('_necessary') && field_value=='' ){  
			$(this).focus();
			system_message_alert('',"請填寫必要欄位 ( * 標示)");
		    checked = false;
		    return false;
		  }
		}
	  });
	  
	  if(!checked) return false; 
	  
	  //console.log(modify_data);
	  // encode data
	  var passer_data  = encodeURIComponent(Base64M.encode(JSON.stringify(modify_data)));
      
      // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Meta/save/'+data_no+'/'+passer_data},
		beforeSend: function(){  active_loading(act_object,'initial'); },   // 因為video load 會將  event peading
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			
			var data_load =  response.data.renew.source;
			data_orl = data_load;
			insert_data_to_meta_form(data_load);
			
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  active_loading(act_object , r.action );  });
	});
	
	
	//-- create new item
	$('#create_new_item').click(function(){
	  
	  // get value
	  var task_no    = $('#taskid').data('refer');
	  
	  var modify_data = {};
	  var act_object  = $(this);
	  var checked = true;
	  
	  var defaultval  = {};
      
	  if($('#page_file_end').val()){
		var page_now_id = $('#page_file_end').val();
		var pager_dom   = $("option.pager[value='"+page_now_id+"']");
		if(pager_dom.length  &&  pager_dom.next().length ){
		  defaultval['page_file_start'] = pager_dom.next().val();
		}else{
		  defaultval['page_file_start'] = pager_dom.val();	
		}
	  }
	  
	   // encode data
	  var passer_data  = encodeURIComponent(Base64M.encode(JSON.stringify(defaultval)));
	  
	  
      // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Built/newaitem/'+task_no+'/'+passer_data},
		beforeSend: function(){  system_loading() },  
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			var DataLoad = response.data.item;
			data_orl = DataLoad;
			$('._target').removeClass('_target');
			if(!$("tr.data_record[no='"+DataLoad.itemid+"']").length){
				var record = $("<tr>").addClass('data_record _data_read _target').attr('no',DataLoad.itemid);
				record.append("<td field='_estatus'  ><i class='mark24 built"+DataLoad._estatus+"'></i></td>");  
				record.append("<td field='item_title'  >"+DataLoad.item_title+"</td>");  
				record.append("<td field='page_info'  >"+DataLoad.page_num_start+' - '+DataLoad.page_num_end+"</td>");  
				record.append("<td field='_update'  >"+DataLoad._update.substr(0,10)+"</td>");  
				record.appendTo($('.data_result')).trigger('click');
			}
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading()  }); 
	});
	
	
	//-- save data modify
	$('#finish_current_meta').click(function(){
	  
	  // get value
	  var task_no    = $('#taskid').data('refer');
	  var data_no    = $('._target').attr('no');
	  var dom_record = $(this);
	  
	  // active ajax
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	  
	  if($('._modify').length){
		system_message_alert('',"尚有資料未儲存，請先儲存修改資料");
		return false;  
	  }
	  
	  var checked = true;
	  $('._update').each(function(){
	    if($(this)[0].tagName=='INPUT' && $(this).attr('type')=='checkbox'){
		  var field_name = $(this).attr('name');
		 
		}else{
		  var field_name  = $(this).attr('id');
	      var field_value = $(this).val();
		  if( $(this).parents('.data_value').prev().hasClass('_necessary') && field_value=='' ){  
			$(this).focus();
			system_message_alert('',"請填寫必要欄位 ( * 標示)");
		    checked = false;
		    return false;
		  }
		}
	  });
	  if(!checked) return false; 
	  
      // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Built/doneitem/'+task_no+'/'+data_no},
		beforeSend: function(){ system_loading(); },   // 因為video load 會將  event peading
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			$('#edit_close').trigger('click');
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading();  });
	});
	
	
	//-- iterm function execute
	$('#delete_current_meta').click(function(){
	  
	   // get value
	  var task_no    = $('#taskid').data('refer');
	  var data_no    = $('._target').attr('no');
	  var dom_record = $(this);
	  
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	   
	  // confirm to admin
	  if(!confirm("確定要刪除本筆資料?")){
	    return false;  
	  }
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Built/deleitem/'+task_no+'/'+data_no},
		beforeSend: function(){  system_loading(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			if($("tr._target").length){
			  $("tr._target").remove();		  
			}
			location.hash = '';
			$('#edit_close').trigger('click');
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading(); });
	});
	
	
	//-- initial account data  //帶有參數的網址連結資料
    if(document.location.hash.match(/^#.+/)){
	    $target = $("tr.data_record[no='"+location.hash.replace(/^#/,'')+"']");
        if($target.length){ 
		  if( !$target.hasClass( '_target' )){
			$target.trigger('click');		
	      }
	    }else{
		  system_message_alert('','查無資料');
	    }
	}else{
	  var address_path = document.location.search.split('/');	
	  if(typeof address_path[3] !='undefined' && parseInt(address_path[3])){
		$target = $("tr.data_record[no='"+address_path[3]+"']");
		if($target.length){ 
		  if( !$target.hasClass( '_target' )){
			$target.trigger('click');		
	      }
	    }else{
		  system_message_alert('','查無資料');
	    }  
	  }	
	} 
	
	
	//-- upload photo file
	$('#act_portrait_upload').change(function(){
	  
	  // get id
	  var data_no    = $('._target').attr('no');
	  var dom_record = $(this);
	   
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	  
	  // check file 
	  var file_upload = $(this).val();
	  var file_name   = file_upload.split('\\').pop();
	  
	  if(!file_upload){
		return false;		
	  }
	  
	  if( /\.(png|jpg)$/gi.test(file_name)===false ){
		system_message_alert('','圖檔格式錯誤，請使用jpg或png');  
		$(this).val('');
	    return false;	
	  }
	  
	  if(!confirm("確定要更新議員頭像?")){
		return false	
	  }  
	  
	  var action = $('#pho_upload_form').attr('action');
	  $('#pho_upload_form').attr('action',action+data_no);
	  var FormObj = document.getElementById('pho_upload_form'); 
	  FormObj.submit()
	  $('#pho_upload_form').attr('action',action);
	  $(this).val('');
	  
	});
	
	
  }); /*** end of html load ***/
  
  //-- 重新讀取照片	   
  function reloadportrait(Base64String){
   if(Base64String){
	  if($('#DOBJ-portrait').length){
		$('#DOBJ-portrait').empty();  
		$('<img/>').attr('src',Base64String).appendTo($('#DOBJ-portrait'));	
	  }  
	}  
  }	   
	
	
	
  