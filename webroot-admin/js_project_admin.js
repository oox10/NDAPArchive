/* [ Admin Project Function Set ] */
	
  
  $(window).on('load',function () {   //  || $(document).ready(function() {		
	
	
	/*== [ 管理模式 ] ==*/
	
	
	//-- create new staff data
	$('#act_project_new').click(function(){
	    
	  // initial page
	  $('#editor_reform').trigger('click');
	  
	  // create new record
	  $tr = $("<tr/>").addClass('data_record _data_read').attr('no','_addnew');
	  $tr.append(" <td field='no'  > new </td>");
	  $tr.append(" <td field='pjname'  > 新增專案 </td>");
	  $tr.append(" <td field='pjinfo'  ></td>");
	  $tr.append(" <td field='user_name'  ></td>");
	  $tr.append(" <td field='user_id'  > </td>");
	  $tr.append(" <td field='@count'  >0</td>");
	  $tr.append(" <td field='_update'  ></td>");
	  $tr.append(" <td field='project_status'  >  </td>");
	  
	  // inseart to record table	
	  if(!$("tr.data_record[no='_addnew']").length){
	    $tr.prependTo('tbody.data_result').find('._data_read').end().trigger('click'); 
	  }
	  
	});
	
	
	
	//-- admin staff get user data
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
	  
	  initial_record_editer();
	  
	  if( data_no=='_addnew' ){
	    dom_record.addClass('_target');
		data_orl = {};
		
		$dom = dom_record.clone().removeClass('_data_read');
	    $('#record_selecter').find('.record_control').hide();
		$('#record_selecter').find('.record_list').children('.data_result').hide();
		$('#record_selecter').find('.record_list').children('.data_target').empty().append( $dom).show();
		$('#record_editor').find('a.view_switch').trigger('click');
		active_header_footprint_option('record_selecter','新增專案','_return_list');
	    $('#project_element_list').empty();
		
	  }else{
	    
		$.ajax({
          url: 'index.php',
	      type:'POST',
	      dataType:'json',
	      data: {act:'Project/read/'+data_no},
		  beforeSend: 	function(){ system_loading();  },
          error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	      success: 		function(response) {
		    if(response.action){  
			  dom_record.addClass('_target');
			  
			  var dataObj =  response.data.record;
			  data_orl = dataObj;
			  
			  // change _data_read area
			  $dom = dom_record.clone().removeClass('_data_read');
			  $('#record_selecter').find('.record_control').hide();
			  $('#record_selecter').find('.record_list').children('.data_result').hide();
			  $('#record_selecter').find('.record_list').children('.data_target').empty().append( $dom).show();
			  $('#record_editor').find('a.view_switch').trigger('click');
			  
			  // insert data
			  insert_project_data_to_form(dataObj);
			  
			  // set foot print 
			  active_header_footprint_option('record_selecter',dataObj.pjname,'_return_list');
			  
			  // hash the address
			  location.hash = dataObj.spno
			  
			  
		    }else{
			  system_message_alert('',response.info);
		    }
	      },
		  complete:		function(){   }
	    }).done(function() { system_loading();   });
	  }
	});
	
	
	
	//-- save data modify
	$('#act_project_save').click(function(){
	  
      // initial	  
	  var data_no    =  $('._target').length? $('._target').attr('no') : '';
	  var modify_data = {};
	  
	  
	  var act_object  = $(this);
	  var checked = true;
	  
	  // option active checked  // 檢查按鈕是否在可執行狀態
	  if( act_object.prop('disabled') ){
	    return false;
	  }
	  
	  // check process data
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  } 
	  
	  // get value
	  $('._update').each(function(){
	    
		var field_name  = $(this).attr('id');
	    var field_value = $(this).val();
		if( data_orl[field_name] !== field_value){
		  modify_data[field_name]  =  field_value;
	    }
		  
		if( $(this).parent().prev().hasClass('_necessary') && field_value==''  ){  
		  $(this).focus();
		  system_message_alert('',"請填寫必要欄位 ( * 標示)");
		  checked = false;
		  return false;
		}
	  });
	  
	  
	  if(!checked){
		return false;  
	  }
	  
	  // encode data
	  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(modify_data)));
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Project/save/'+data_no+'/'+passer_data},
		beforeSend: function(){  active_loading(act_object,'initial'); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  
		  if(response.action){
			var dataObj = response.data.record;
			data_orl = dataObj;
			insert_project_data_to_form(dataObj);
			
			if(data_no=='_addnew'){
			  $('._target').attr('no',response.data.save);	
			}
			
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) {  active_loading(act_object , r.action );  });
	});
	
	
	//-- 下載專案清單
	$('#act_export_project').click(function(){
	  
      // get value
	  var data_no    = $('._target').attr('no');
	  //var dom_record = $(this);
	  
	  // active ajax
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
      
	  if(!$("input[name='output']").length){
		system_message_alert('',"專案尚未收錄資料");
		return false;
	  }
	  
	  var select = [];
	  if($("input[name='output']:checked").length){
		$("input[name='output']:checked").each(function(){
		  select.push($(this).val());	
		});  
	  }
	  
	  // encode data
	  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(select)));
      window.open('index.php?act=Project/export/'+data_no+'/'+passer_data);   	  
	});
	
	
	//-- 刪除專案資料
	$(document).on('click','.act_remove_pjelement',function(){
	
	  var data_no     =  $('._target').length? $('._target').attr('no') : '';
	  
	  // check main_dom
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  }  
	  
	  var record      =  $(this).parents('tr.pjelement');
	  var file_name   =  record.attr('file');
	  
	  // check target file
	  if( !file_name.length ){
	    system_message_alert('',"尚未選擇檔案");
	    return false;
	  } 

	  // confirm to admin
	  if(!confirm("確定要對將檔案移出專案 ?")){
	    return false;  
	  }
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Project/pjeremove/'+data_no+'/'+file_name},
		beforeSend: function(){  system_loading(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			system_message_alert('alert',file_name+' 已成功移出於專案');
			record.remove();
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading(); });
	  
	});
	
	
	//-- get apply data to editer  // 從server取得使用者資料並放入編輯區
	function insert_project_data_to_form(dataObj){
		
	  var dom_record  = $('._target'); 
	  $('#project_element_list').empty();
	  $.each(dataObj,function(field,meta){
		
		if(field=='elements'){
		  
		  var list_dom = $('#project_element_list');
		  var counter  = 0;  
		  $.each(meta,function(fname,finfo){
			  
		    var dom = $('<tr/>').addClass('pjelement').attr({'file':fname});
			
			dom.append("<td><input type='checkbox' name='output' value='"+fname+"'  >"+(++counter)+". </td>");	
			dom.append("<td><div class='pjethumb'><img src='thumb.php?src="+finfo['path']+"thumb/"+(fname.match(/mp3|mp4/)?fname+'.jpg':fname)+"' ></div></td>");	
			dom.append("<td class='pjeinfo'   ><div><label>來源</label>"+ finfo['from']+"</div><div><label>檔案</label>"+ (fname)+"</div><div><label>類型</label>"+ (finfo['type'])+"</div><div><label>收錄者</label>"+ finfo.user+"</div><div><label>加入</label>"+ finfo['time']+"</div></td>");
			dom.append("<td class='pjestatus' status='"+finfo['status']+"'><span class='pjeprocess _regist'><i class='fa fa-check-square-o' aria-hidden='true'></i> 加入專案 </span><span class='pjeprocess _process'><i class='fa fa-refresh' aria-hidden='true'></i> 處理中..</span><span class='pjeprocess _import'><i class='fa fa-check' aria-hidden='true'></i> 準備完成 </span><a class='option act_remove_pjelement' title='刪除'><i class='fa fa-trash-o' aria-hidden='true'></i></a></td>");
			dom.appendTo(list_dom);
			  
		  });
		  
		}else{
		  if(  $("._variable[id='"+field+"']").length ){  
			$("._variable[id='"+field+"']").val(meta);  
		  }
		}
		
		// update target record 
		var record_field = dom_record.children("td[field='"+field+"']");
		if( record_field.length && record_field.html() != meta  ){
		  record_field.html(meta);
	    }
		
	  });
	  $('._modify').removeClass('_modify');
	}
	
	
	
	//-- set upload excel file
	$('.user_apply_list').change(function(){
	  
	   // initial	  
	  var data_no    =  $('._target').length? $('._target').attr('no') : '';
  	  
	  // check process data
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
		$(this).val('');
	    return false;
	  } 
	  
	  
	  var file_upload = $(this).val();
	  var file_name   = file_upload.split('\\').pop();
	  
	  if(!file_upload){
		return false;		
	  }
	  
	  if( /^AHAS\d+.*?\.xls(x)?$/.test(file_name)===false ){
		system_message_alert('','檔案名稱錯誤，請上傳正確資料');  
		$(this).val('');
	    return false;	
	  }
	  
	  var action = $('#apply_upload_form').attr('action');
	  $('#apply_upload_form').attr('action',action+data_no);
	  var FormObj = document.getElementById('apply_upload_form'); 
	  FormObj.submit()
	  $('#apply_upload_form').attr('action',action);
	  
	});
	
	//-- upl apply list
	$('#act_append_pjelement').click(function(){
      
	  system_message_alert('','尚未開放');
	  return false;
	  
       // initial	  
	  var data_no    =  $('._target').length? $('._target').attr('no') : '';
  	  
	  // check process data
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
		$(this).val('');
	    return false;
	  } 
	  
	  if(!$('.user_apply_list').val()){
		system_message_alert('','尚未選擇准駁清單');    
	    return false;
	  }
	  
	  var file_upload = $('.user_apply_list').val();
	  var file_name   = file_upload.split('\\').pop();
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {'act':'Apply/uplupd/'+data_no+'/'+file_name},
		beforeSend: function(){  system_loading() },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response){
		  if(response.action){
			
			var dataObj = response.data.apply;
			// insert data
			insert_apply_data_to_form(dataObj);
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) { system_loading()  });
	
	});
	
	
	
	
	//-- iterm function execute
	$('#act_func_execute').click(function(){
	  
	  var data_no     =  $('._target').length? $('._target').attr('no') : '';
	  var execute_func =  $('#execute_function_selecter').length ? $('#execute_function_selecter').val() : '';
	  
	  // check process target
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  }  
	  
	  // check process action
	  if( !execute_func.length ){
	    system_message_alert('',"尚未選擇執行功能");
	    return false;
	  } 

      // check process action
	  if( data_no=='_addnew' ){
	    system_message_alert('',"資料尚在編輯中，請先儲存資料");
		return false;
	  }	    
	  
	  // confirm to admin
	  if(!confirm("確定要對資料執行 [ "+$("option[value='"+execute_func+"']").html()+" ] ?")){
	    return false;  
	  }
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Project/'+execute_func+'/'+data_no},
		beforeSend: function(){  system_loading(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			switch(execute_func){
			  case 'dele' : act_record_del_after(response.data);break;
			}
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading(); });
	  
	  
	  $('#execute_function_selecter').val('');
	});
	
	
	// 執行申請刪除後的動作
	function act_record_del_after(DataNo){
      $("tr._target[no='"+DataNo+"']").remove();
	  $('#editor_reform').trigger('click');
	  $('.record_view').trigger('change');
	}
	
	
	// initial account data  //帶有參數的網址連結資料
    if(document.location.hash.match(/^#.+/)){
		
	    $target = $("tr._data_read[no='"+location.hash.replace(/^#/,'')+"']");
        if($target.length){ 
		  if( !$target.hasClass( '_target' )){
			$target.trigger('click');		
	      }
	    }else{
		  system_message_alert('','查無資料');
	    }
	}
	
	//-- 設定分頁
	$('.record_view').val(10).trigger('change');
	
  });	
  
  
  