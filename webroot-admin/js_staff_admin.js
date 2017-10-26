/* [ Admin Staff Function Set ] */
	
  
  $(window).on('load',function () {   //  || $(document).ready(function() {		
	
	
	//-- submit condition
	function module_page_rebuilt(){
      
	  var parameter 	= {};
	  
	  // get data group  
	  var record_mode = $("input[name='data_type']:checked").val();
	  
	  // get data search 
	  parameter['condition'] = $('#data_search_condition').val();	  
	  
	  // get data order
	  if($(".order_by[mode != '0']").length){
		parameter['orderby']   = {'field':$(".order_by[mode!='0']").attr('order'),'mode':$(".order_by[mode!='0']").attr('mode') , 'name':$(".order_by[mode!='0']").attr('name') };  
	  }
	  
	  // get page config
	  var pager = $('.record_view').val();
	  
	  var passer_data	= encodeURIComponent(Base64.encode(JSON.stringify(parameter)));
	  location.href='index.php?act=Staff/index/'+record_mode+'/'+pager+'/'+passer_data;  
	}
	
	
	//-- datepicker initial
	$("#date_open,#date_access").datepicker({
	    dateFormat: 'yy-mm-dd',
	    onClose: function(dateText, inst) { 
	      if(/\d{4}-\d{2}-\d{2}$/.test(dateText)){
		    $(this).val(dateText+' 00:00:01');
		  }
	    } 
	});
	
	//-- data type selecter	
	$("input:radio[name='data_type']").click(function(){
	  if($(this).val()=='search'){
		$('#data_search_condition').focus();
		return true;  
	  }	
	  module_page_rebuilt();
	});
	
	
	//-- data search
	$('#act_record_search').click(function(){
	  var search = $('#data_search_condition').val();	
      if(search==''){
		system_message_alert('','請輸入搜尋條件');
        $('#data_search_condition').focus();
        return false; 		
	  }
	  module_page_rebuilt()
	});
	
	//-- data order by 
	$('.order_by').click(function(){
	  var order_by_mode  = parseInt($(this).attr('mode'));
	  var order_by_next  = ((order_by_mode+1)%3);	
      $('.order_by').attr('mode','0');
	  $(this).attr('mode',order_by_next);
      module_page_rebuilt();
	});
	
	
	//-- select all data
	$('.act_select_all').change(function(){
	  var member_class = $(this).attr('member');
	  $('.account_selecter,.act_select_all').prop('checked',$(this).prop('checked'));
	});
	
	
	//-- select one data
	$('.account_selecter').change(function(){
	  var select_all_checked = $('.account_selecter').length != $('.account_selecter:checked').length ? false : true;
	  $('.act_select_all').prop('checked',select_all_checked); 
	})
	
	
	//-- 設定分頁 資料超過上萬，分頁自訂
	$(document).off('click','.page_to');
	$(document).off('change','.record_view');
	
	$('.record_view').change(function(){
	  var link = location.search.replace('/#.*?$/','').split('/');
	  link[3] = $(this).val();
	  location.search = link.join('/');
	});
	
	$('.page_to').click(function(){
	  if(!$(this).attr('page')){
	    return false;
	  }	
	  var link = location.search.replace('/#.*?$/','').split('/');
	  link[3] = $(this).attr('page');
	  location.search = link.join('/');
	});
	
	$('.page_jump').change(function(){
	  if(!$(this).val()){
	    return false;
	  }	
	  var link = location.search.replace('/#.*?$/','').split('/');
	  link[3] = $(this).val();
	  location.search = link.join('/');
	});
	
	//-- select collection
	$('.select_all').change(function(){
	  var member_class = $(this).attr('member');
	  $('.'+member_class).prop('checked',$(this).prop('checked'));
	});
	
	
	
	//-- record order 
	$('#order_by_regist').click(function(){
       		
	})
	
	
	//-- admin staff get user data
	$(document).on('click','._data_read',function(){
	  
      // initial	  
	  $('._target').removeClass('_target');
	  
	  
	  // get value
	  var dom_record = $(this).parents('.data_record');
	  var user_no    = dom_record.attr('no');
	  
	  // active ajax
	  if( ! user_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	  
	  initial_record_editer();
	  
	  if( user_no=='_new' ){
	    
		dom_record.addClass('_target');
		data_orl = {};
		
		$('#user_id').prop('readonly',false);
		$('#main_group').html('同管理者');
		$('#user_status').prop('disabled',true).val(2)
		
		$dom = dom_record.clone().removeClass('_data_read');
	    $('#record_selecter').find('.record_control').hide();
		$('#record_selecter').find('.record_list').children('.data_result').hide();
		$('#record_selecter').find('.record_list').children('.data_target').empty().append( $dom).show();
		
		active_header_footprint_option('record_selecter','新增帳戶','_return_list');
	    
		$('#record_editor').find('a.view_switch').trigger('click','open');
			  
		
	  }else{
	    
		$.ajax({
          url: 'index.php',
	      type:'POST',
	      dataType:'json',
	      data: {act:'Staff/read/'+user_no},
		  beforeSend: 	function(){ system_loading();  },
          error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	      success: 		function(response) {
		    if(response.action){  
			  dom_record.addClass('_target');
			  
			  var dataObj =  response.data.user;
			  data_orl = dataObj;
			  
			  // change _data_read area
			  $dom = dom_record.clone().removeClass('_data_read');
			  $('#record_selecter').find('.record_control').hide();
			  $('#record_selecter').find('.record_list').children('.data_result').hide();
			  $('#record_selecter').find('.record_list').children('.data_target').empty().append( $dom).show();
			  
			  // insert data
			  insert_staff_data_to_form(dataObj);
			  
			  // set foot print 
			  active_header_footprint_option('record_selecter',dataObj.user_mail,'_return_list');
			  
			  // hash the address
			  location.hash = dataObj.user_id
			  
			  $('#record_editor').find('a.view_switch').trigger('click','open');
			  
		    }else{
			  system_message_alert('',response.info);
		    }
	      },
		  complete: function(){   }
	    }).done(function() { system_loading();   });
	  }
	
	  
	
	});
	
	
	//-- data change
	$('.data_trival').click(function(){
	  	
	  // get now record
	  var data_target = (!$('._target').length) ? $('.data_record:first') : $('._target');
	  if(!data_target.length){
		system_message_alert('','目前無任何資料');  
	    return false;
	  }
	  
	  // get next record
	  var data_toaccess = ( $(this).attr('id') == 'act_record_prev' ) ? data_target.prev('.data_record'):data_target.next('.data_record');
	  if(!data_toaccess.length){
		system_message_alert('','沒有 '+$(this).attr('title')+' 資料');   
	  }
	  
	  // change page num
	  if(!data_toaccess.is(':visible')){
		if($(this).attr('id') == 'act_record_prev'){
		  $(".page_to[page='prev']").trigger('click');  	
		}else{
		  $(".page_to[page='next']").trigger('click');  	
		}
	  }
	  
	  // final
	  data_toaccess.find('._data_read').trigger('click');
	  
	});
	
	
	
	
	//-- save data modify
	$('#act_staff_save').click(function(){
	  
      // initial	  
	  var staff_no    =  $('._target').length? $('._target').attr('no') : '';
	  var modify_data = {};
	  var roles_data  = {};
	  
	  var act_object  = $(this);
	  var checked = true;
	  
	  // option active checked  // 檢查按鈕是否在可執行狀態
	  if( act_object.prop('disabled') ){
	    return false;
	  }
	  
	  // check process data
	  if( !staff_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  } 
	  
	  // get value
	  $('._update').each(function(){
	    if($(this)[0].tagName=='INPUT' && $(this).attr('name')=='roles'){
		  var field_name = $(this).attr('name');
		  roles_data[$(this).val()] = $(this).prop('checked') ? 1 : 0;
		}else{
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
		}
	  });
	  
	  if(!checked){
		return false;  
	  }
	  
	  // encode data
	  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(modify_data)));
	  var passer_roles = encodeURIComponent(Base64.encode(JSON.stringify(roles_data)));
      // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Staff/save/'+staff_no+'/'+passer_data+'/'+passer_roles},
		beforeSend: function(){  active_loading(act_object,'initial'); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  
		  if(response.action){
			
			var dataObj = response.data.user;
			data_orl = dataObj;
			
			// insert data
			insert_staff_data_to_form(dataObj);
			
			// update data no 
			if( staff_no == '_addnew'){  $('._target').attr('no',dataObj.uno) }
		  
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) {  active_loading(act_object , r.action );  });
	});
	
	
	//-- get staff data to editer  // 從server取得使用者資料並放入編輯區
	function insert_staff_data_to_form(dataObj){
	  var dom_record  = $('._target'); 
	  
	  $.each(dataObj,function(field,meta){
		if(field=='roles' && meta){
		  //  'R01':1 'R02':0 ...	
		  $.each(meta,function(rid,checked){
			$("input[name='roles'][value='"+rid+"']").prop('checked',checked);    
		    $(".role_map[data-role='"+rid+"']").attr('on',checked);
		  });
		}else if(field=='groups'){
			$("span[name='groups']").html('');	  
			$.each(meta,function(i,g){
			  if(parseInt(g.master)){
				$("span#main_group").html("<i title='"+g.ug_info+"'>"+g.ug_name+"</i>");  
			  }else{
				$("span#rela_group").append("<i title='"+g.ug_info+"'>"+g.ug_name+"；</i>");	  
			  }
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
	
	//-- change role map display
    $("input[name='roles']").change(function(){	
	  $(".role_map[data-role='"+$(this).val()+"']").attr('on',$(this).prop('checked')*1);
	});
    
	//-- create new staff data
	$('#act_staff_new').click(function(){
	    
	  // initial page
	  $('#editor_reform').trigger('click');
	  
	  // create new record
	  $tr = $("<tr/>").addClass('data_record').attr('no','_new');
	  $tr.append(" <td  > - </td>");
	  $tr.append(" <td field='uno'  > - </td>");
	  $tr.append(" <td field='user_group'  ></td>");
	  $tr.append(" <td field='user_organ'  ></td>");
	  $tr.append(" <td field='user_id'  > </td>");
	  $tr.append(" <td field='user_name'  ></td>");
	  $tr.append(" <td field='user_tel'  ></td>");
	  $tr.append(" <td field='@date_register'  > - </td>");
	  $tr.append(" <td ><i class='mark24 pic_account_status1'></i></td>");
	  $tr.append(" <td title='讀取帳號資料'><a class='option _data_read' class='act_read_data' ><i class='fa fa-user-circle' aria-hidden='true'></i></a></td>");
	  
	  // inseart to record table	
	  if(!$("tr.data_record[no='_new']").length){
	    $tr.prependTo('tbody.data_result').find('._data_read').trigger('click');
	  }	
	  
	  
	});
	
	
	
	//-- iterm function execute
	$('#act_func_execute').click(function(){
	  
	  var staff_no     =  $('._target').length? $('._target').attr('no') : '';
	  var execute_func =  $('#execute_function_selecter').length ? $('#execute_function_selecter').val() : '';
	  
	  // check process target
	  if( !staff_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  }  
	  
	  // check process action
	  if( !execute_func.length ){
	    system_message_alert('',"尚未選擇執行功能");
	    return false;
	  } 

      // check process action
	  if( staff_no=='_addnew' ){
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
	    data: {act:'Staff/'+execute_func+'/'+staff_no},
		beforeSend: function(){  system_loading(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			switch(execute_func){
			  case 'dele' : act_staff_del_after(response.data);break;
			  case 'startmail': alert("已成功寄出帳號開通信件 TO: "+response.data+" "); break;
			}
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading(); });
	  $('#execute_function_selecter').val('');
	});
	
	// 執行帳號刪除後的動作
	function act_staff_del_after(StaffNo){
      $("tr._target[no='"+StaffNo+"']").remove();
	  $('#editor_reform').trigger('click');
	  $('.record_view').trigger('change');
	}
	
	
	/**-- [ staff bath config ] --**/
    $('#act_bath_account_pass').click(function(){
            
	  if(!$('.account_selecter:checked').length){
		system_message_alert('','尚未勾選資料!!');  
	    return false;
	  } 
	  
	  var selecter = $('.account_selecter:checked').map(function(){ return $(this).val() }).get().join(';');
	  
	  if(!confirm("確定要對勾選的 《 "+selecter.length+" 》個帳號批次通過? \n這個動作將會寄發審核通知信")){
		return false; 
	  }
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Staff/batchaccept/'+selecter},
		beforeSend: function(){  system_loading(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			system_message_alert('alert',response.data);
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading(); });
	  
	  	
	});
    	
	
	
	
	/**-- [ group member setting Setting ] --**/

    //-- Open group member Setting area
    $('#act_set_gmember').click(function(){
	  
	  // Update DB
	  $.ajax({
		url: 'index.php',
		type:'POST',
		dataType:'json',
		data: {act:'Staff/gmember'},
		beforeSend: function(){  system_loading(); },
		error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		success: 	function(response) {
		  if(response.action){
			
			var now_group  = '';
			var now_member = {};
			var pool_member=[];
			
			// insert group selecter
			var gps = response.data.groups;
			$.each(gps,function(i,gp){
			  var $DOM = $("<option/>").val(gp.ug_code).html(gp.ug_name + ' - ' +gp.ug_info ).attr({'data-name':gp.ug_name,'data-info':gp.ug_info,'data-code':gp.ug_code});
			  if( $('#acc_group_select').val() == gp.ug_code){
				$DOM.prop('selected',true);  
				now_group = $('#acc_group_select').val();
			  }
              $DOM.appendTo('#group_queue');
			});
			
			// insert member select
			var mbr = response.data.members;
			$.each(mbr,function(gpc,mbrs){
			  if(gpc == now_group){
				now_member = mbrs; 
			  }else{
				var gpcode = '';  
				$.each(mbrs,function(i,mbr){
				  if(gpcode != mbr['gid']){
					$('#group_members').append("<optgroup label='"+gpc+"' >");  
					gpcode = mbr['gid'];
				  }
				  var $DOM = $("<option/>").val(mbr.uno).html(mbr.user_id+' / '+mbr.user_name);
				  $DOM.appendTo('#group_members');
				  pool_member.push(mbr.user_id+' / '+mbr.user_name);
				});  
			  }
			});
			
			// insert group members
			$.each(now_member,function(i,mem){
			  var $DOM = $("<tr/>").addClass('gmember').attr('no',mem['user_id']);
              $DOM.append("<td>"+mem['user_id']+"</td>");  
			  $DOM.append("<td>"+mem['user_name']+"</td>");
			  $DOM.append("<td>"+mem['roles'].join(',')+"</td>");
			  $DOM.append("<td>"+mem['filter']+"</td>");
			  
			  if(parseInt(mem['master'])){
				$DOM.append("<td>-</td>");  
			  }else{
				$DOM.append("<td><button type='button' class='act_leave_group cancel'><i class='mark16 pic_group_leave'></i></button></td>");  
			  }
			  $DOM.appendTo('#member_list');
			});
			
			// create member select auto cokplete
			$( "#member_search" ).autocomplete({
			  source: pool_member,
			  select: function( event, ui ) {
				var target = $('#group_members').find('option:contains("'+ui.item.value+'")').val()
				$('#group_members').val(target);
			  },
			  close: function( event, ui ) {
				$(this).val('');  
			  }
			});
			
			
		  }else{
			system_message_alert('',response.info);
		  }
		},
		complete:	function(){  }
	  }).done(function(r) {   system_loading();   });
	  
	  $('.group_setting_area').show();
	});
	
	
	
	// change admin groups
	$('#group_selecter').change(function(){
	  
	  $('#group_members,#member_list').empty();
	  
	  var target_group = $(this).find('option:selected');
	  var group_code = $(this).val();
	  var pool_member=[];
	  
	  if(group_code=='_new_group'){
		// insert group meta
		$('#group_name').val('').focus();
		$('#group_info').val('');
		$('#group_code').val('').prop('readonly',false);    
	  }else{
		// insert group meta
        $('#group_name').val(target_group.data('name'));
        $('#group_info').val(target_group.data('info'));
        $('#group_code').val(target_group.data('code')).prop('readonly',true);
	  }
	  
	  // Update DB
	  $.ajax({
		url: 'index.php',
		type:'POST',
		dataType:'json',
		data: {act:'Staff/gmember'},
		beforeSend: function(){  system_loading(); },
		error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		success: 	function(response) {
		  if(response.action){
			
			var now_group  = '';
			var now_member = {};
			
			// insert member select
			var mbr = response.data.members;
			$.each(mbr,function(gpc,mbrs){
			  if(gpc == $('#group_selecter').val()){
				now_member = mbrs; 
			  }else{
				var gpcode = '';  
				$.each(mbrs,function(i,mbr){
				  if(gpcode != mbr['gid']){
					$('#group_members').append("<optgroup label='"+gpc+"' >");  
					gpcode = mbr['gid'];
				  }
				  var $DOM = $("<option/>").val(mbr.uno).html(mbr.user_id+' / '+mbr.user_name);
				  $DOM.appendTo('#group_members');
				   pool_member.push(mbr.user_id+' / '+mbr.user_name);
				});  
			  }
			});
			
			// insert group members
			$.each(now_member,function(i,mem){
			  var $DOM = $("<tr/>").addClass('gmember').attr('no',mem['user_id']);
			  $DOM.append("<td>"+mem['user_id']+"</td>");  
			  $DOM.append("<td>"+mem['user_name']+"</td>");
			  $DOM.append("<td>"+mem['roles'].join(',')+"</td>");
			  $DOM.append("<td>"+mem['filter']+"</td>");
			  if(parseInt(mem['master'])){
				$DOM.append("<td>-</td>");  
			  }else{
				$DOM.append("<td><button type='button' class='act_leave_group cancel'><i class='mark16 pic_group_leave'></i></button></td>");  
			  }
			  $DOM.appendTo('#member_list');
			});
			
			// create member select auto cokplete
			$( "#member_search" ).autocomplete("option","source",pool_member);
			
		  }else{
			system_message_alert('',response.info);
		  }
		},
		complete:	function(){  }
	  }).done(function(r) {   system_loading();   });  
	  
	  $('.group_setting_area').show();
	});
	
	
	// group leave function
	$(document).on('click','.act_leave_group',function(){
	  var user   = $(this).parents('tr.gmember').attr('no');
      var record = $(this).parents('tr.gmember');
	  var group = $('#group_selecter').val();
	  
      $.ajax({
		url: 'index.php',
		type:'POST',
		dataType:'json',
		data: {act:'Staff/gpdef/'+user+'/'+group},
		beforeSend: function(){  system_loading(); },
		error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		success: 	function(response) {
		  if(response.action){
			record.remove();
			system_message_alert('alert',"使用者已移出群組");
		  }else{
			system_message_alert('',response.info);
		  }
		},
		complete:	function(){  }
	  }).done(function(r) {   system_loading();   });
	  
	  // remove 
		
	});
	
	// group add function
	$('#act_addto_group').click(function(){
	  
	  var user  = $('#group_members').val();
	  var group = $('#group_selecter').val();
	  var role  = {};
	  $("input[name='add_role']").each(function(){
		role[$(this).val()] = $(this).prop('checked') ? 1 : 0;   
	  });
	  
	  // check user
	  if( !user ){
	    system_message_alert('',"尚未選擇成員");
		return false;
	  }	
	  
	  // check role
	  if( !$("input[name='add_role']:checked").length ){
	    system_message_alert('',"尚未設定角色");
		return false;
	  }	
	  
	  var passer_data    = encodeURIComponent(Base64.encode(JSON.stringify(role)));
	  var member_qualify = encodeURIComponent($('#member_qualify').val());
	  
	  $.ajax({
		url: 'index.php',
		type:'POST',
		dataType:'json',
		data: {act:'Staff/gpadd/'+user+'/'+group+'/'+passer_data+'/'+member_qualify},
		beforeSend: function(){  system_loading(); },
		error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		success: 	function(response) {
		  if(response.action){
			var mem = response.data;  
			if($("tr.gmember[no='"+mem['user_id']+"']").length){
			  $("tr.gmember[no='"+mem['user_id']+"']").children('td:nth-child(4)').html(mem['roles'].join(','));
			}else{
			  var $DOM = $("<tr/>").attr('no',mem['user_id']);
		      $DOM.append("<td>"+mem['user_id']+"</td>");  
		      $DOM.append("<td>"+mem['user_name']+"</td>");
		      $DOM.append("<td>"+mem['roles'].join(',')+"</td>");
		      $DOM.append("<td>"+mem['filter']+"</td>");
		      if(mem['master']){
			    $DOM.append("<td> - </td>");  
		      }else{
			    $DOM.append("<td><button type='button' class='act_leave_group cancel'><i class='mark16 pic_group_leave'></i></button></td>");  
		      }
		      $DOM.appendTo('#member_list');	
			}
			system_message_alert('alert',"帳號加入成功");
		  }else{
			system_message_alert('',response.info);
		  }
		},
		complete:	function(){
		  $('#add_member').val('');
		  $("input[name='add_role']").prop('checked',false);
		}
	  }).done(function(r) {   system_loading();   });
	  
	  
	});
	
	
	//-- save group meta
    $('#act_save_group').click(function(){
	  var target_group = $('#group_selecter').find('option:selected');
	  var group_code   = $('#group_selecter').val();
	  
	  var group = {}
	  
	  if( !$('#group_name').val().length || !$('#group_code').val().length){
		system_message_alert('','群組名稱與代號不可空白');  
		return false;
	  }
	  
	  group['name'] = $('#group_name').val();
	  group['info'] = $('#group_info').val();
	  group['code'] = $('#group_code').val();
	  
	  var passer_data    = encodeURIComponent(Base64M.encode(JSON.stringify(group)));
	  
	  $.ajax({
		url: 'index.php',
		type:'POST',
		dataType:'json',
		data: {act:'Staff/gsave/'+passer_data},
		beforeSend: function(){  system_loading(); },
		error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		success: 	function(response) {
		  if(response.action){
			if(group_code=='_new_group'){
			  var $DOM = $("<option/>").val(response.data).html(group['name'] + ' - ' +group['info'] ).attr({'data-name':group['name'],'data-info':group['info'],'data-code':group['code'] });
			  $DOM.appendTo('#group_queue');
			  system_message_alert('alert',"群組新增成功");
			  $('#group_selecter').find("option[value='"+response.data+"']").data({'name':group['name'],'info':group['info']}).html(group['name'] + '-' +group['info']);
			}else{
			  system_message_alert('alert',"群組更新成功");  	
			}
			$('#group_selecter').val(response.data).trigger('change');
		  }else{
			system_message_alert('',response.info);
		  }
		},
		complete:	function(){}
	  }).done(function(r) {   system_loading();   });
	
	});	
	
	//-- save group meta
    $('#act_dele_group').click(function(){
	  var target_group = $('#group_selecter').find('option:selected');
	  var group_code   = $('#group_selecter').val();
	  
	  if( !group_code.length && group_code != '_new_group'){
		system_message_alert('','尚未選擇群組');  
		return false;
	  }
	  
	  // check group members 
	  if($('tr.gmember').length){
		system_message_alert('','請先將所有群組成員移除!!');  
		return false;  
	  }
	  
	  // confirm to admin
	  if(!confirm("確定要刪除 [ "+target_group.html()+" ] ?")){
	    return false;  
	  }
	  
	  $.ajax({
		url: 'index.php',
		type:'POST',
		dataType:'json',
		data: {act:'Staff/gdele/'+group_code},
		beforeSend: function(){  system_loading(); },
		error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		success: 	function(response) {
		  if(response.action){
			system_message_alert('alert',"群組已移除");
			$('#group_selecter').find("option[value='"+response.data+"']").remove();
			$('#group_selecter').val('adm').trigger('change');
		  }else{
			system_message_alert('',response.info);
		  }
		},
		complete:	function(){}
	  }).done(function(r) {   system_loading();   });
	
	});	
	
	
	//-- Close Project Setting & cancal now
    $('#close_setter').click(function(){
	  $('._setinit').empty().val('');
	  $("input[name='add_role']").prop('checked',false);	  
      $('.group_setting_area').hide();   
    });
	
	
	
  });	
  
  
  