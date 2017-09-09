/* [ Admin Meta Print Type Built Function Set ] */
	
  $(window).on('load',function () {   //  || $(document).ready(function() {		
	
	
	/***-------------------------***/
	/* [ BUILT CONTENTS FUNCTION ] */
	/***-------------------------***/
	
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
	
	
	
	//-- finish task
	if( $('#act_task_finish').length ){
	  $('#act_task_finish').click(function(){
		
		var task_no    = $('#taskid').data('refer');
		
		// confirm to admin
	    if(!confirm("確定要設定此工作為完成?")){
	      return false;  
	    }
		
		$.ajax({
          url: 'index.php',
	      type:'POST',
	      dataType:'json',
	      data: {act:'Built/finish/'+task_no},
		  beforeSend: 	function(){ system_loading();  },
          error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	      success: 		function(response) {
		    if(response.action){  
			  location.href='index.php?act=Built';
		    }else{
			  system_message_alert('',response.info);
		    }
	      },
		  complete:		function(){   }
	    }).done(function() { system_loading();   });  
		  
	  });	
	}
	
	//-- return task
	$('#act_task_return').click(function(){
	  var task_no    = $('#taskid').data('refer');
	  // confirm to admin
	  if(!confirm("確定要將此工【 退回 】給承辦人?")){
		return false;  
	  }
		
	  $.ajax({
		  url: 'index.php',
		  type:'POST',
		  dataType:'json',
		  data: {act:'Built/goback/'+task_no},
		  beforeSend: 	function(){ system_loading();  },
		  error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		  success: 		function(response) {
			if(response.action){  
			  location.href='index.php?act=Built/index/comp';
			}else{
			  system_message_alert('',response.info);
			}
		  },
		  complete:		function(){   }
	  }).done(function() { system_loading();   });  
	});	
	
	//-- checked task
	$('#act_task_checked').click(function(){
	  var task_no    = $('#taskid').data('refer');
	  // confirm to admin
	  if(!confirm("確定要將此工作標示為【 已確認 】 ?")){
		return false;  
	  }
		
	  $.ajax({
		  url: 'index.php',
		  type:'POST',
		  dataType:'json',
		  data: {act:'Built/checked/'+task_no},
		  beforeSend: 	function(){ system_loading();  },
		  error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		  success: 		function(response) {
			if(response.action){  
			  location.href='index.php?act=Built/index/comp';
			}else{
			  system_message_alert('',response.info);
			}
		  },
		  complete:		function(){   }
	  }).done(function() { system_loading();   });  
	});	
	
	
	//-- download task
	$('#act_task_downlaod').click(function(){
      var task_no    = $('#taskid').data('refer');
	  window.open('index.php?act=Built/export/'+task_no);
	});
	
	
	//-- switch config block
	$('#act_editor_setting,#act_close_setting').click(function(){
	  if( $('.advance_conf').is(':visible')){
		$('.advance_conf').hide();  
	  }else{
		$('.advance_conf').show();    
	  }
	});
	
	
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
	  
	  initial_record_editer();
	  
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
			  $('#system_breadcrumbs').find('li#target_record').remove().end().append("<li class='breadcrumb' id='target_record' >"+data_load['META-StoreNo']+" (編輯中) </li>");
			  //active_header_footprint_option('record_selecter',dataObj.identifier,'_return_list');
			  
			  
			  
			  // 處理數位檔案
			  var meta_dobjconfig = response.data.meta.dobj;
			  
			  
			  // 標示遮蔽影像
			  if(typeof meta_dobjconfig.domask != 'undefined' ){
				
				dobjectconf  = meta_dobjconfig.domask;
			    $.each(dobjectconf,function(PageName,PageConf){
				  if( (PageConf['mode'] && PageConf['mode']=='disabled') || (  typeof PageConf['display']!='undefined' && !parseInt(PageConf['display'])) ){
					var pagerdom = $("option.pager[value='"+PageName+"']");
					var pagerstring = pagerdom.text();  
					pagerdom.attr('display',0);
					pagerdom.text(pagerstring+'-ｘ');
				  }  
				});
			  }
			  
			  
			  // 設定預設首頁
			  if(typeof meta_dobjconfig.position != 'undefined'){
				$.each(meta_dobjconfig.position,function(SerialNum,FileName){
				  $('.page_selecter').val(FileName).trigger('change');
				  return false				  
				});  
			    
				var page_config    = Object.keys(meta_dobjconfig.position);
			    var first_page_key = page_config[0];
			    var last_page_key  = page_config[(page_config.length-1)];
			  
			    if(!$('#META-DobjFrom').val())  $('#META-DobjFrom').val(meta_dobjconfig.position[first_page_key]).addClass('_modify');
				if(!$('#META-DobjEnd').val())  $('#META-DobjEnd').val(meta_dobjconfig.position[last_page_key]).addClass('_modify');
			    
			  }else{
				var first_page = $('option.pager').first().val()
				$('.page_selecter').val(first_page).trigger('change');
			  }
			  
			  
			  /*
			  if(data_load._estatus == '_initial' && data_load.meta_refer ){
			    insert_refer_to_meta_form(data_load.meta_refer);	
			  }
			  
			  // hash the address
			  
			  check_page_num();
			  
			  // 打開第一頁
			  if(data_load.page_file_start && $("option.pager[value='"+data_load.page_file_start+"']").length){
				$('.page_selecter').val(data_load.page_file_start).trigger('change');
			  }
			  
			  
			  /*
			  
			  
			  // change _data_read area
			  $dom = dom_record.clone().removeClass('_data_read');
			  $('#record_selecter').find('.record_control').hide();
			  $('#record_selecter').find('.record_list').children('.data_result').hide();
			  $('#record_selecter').find('.record_list').children('.data_target').empty().append( $dom).show();
			  $('#record_editor').find('a.view_switch').trigger('click');
			  
			  // insert data
			  // insert object
			  insert_data_to_dobj_form(digiObj);
			  
			  // set foot print 
			  
			  */
			  
			  
			  
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
	
	
	//--check page num
	$('.page_num').on('keyup change',function(){
	  check_page_num();	
	});
	
	//-- 填入頁碼
	$('#page_file_putin').click(function(){
	  
	  
	  var pser_s = parseInt($('#page_num_start').val()) + 1 ;
	  var pser_e = parseInt($('#page_num_end').val()) + 1 ;
	  
	  var pfval_s = $('#page_file_start').val();
	  var pfval_e = $('#page_file_end').val();
	  
	  
	  if( !pfval_s && !pfval_e ){  //全空白
		$('#page_file_start').val($('option.pager:nth-child('+pser_s+')').val()).trigger('change');
	    $('#page_file_end').val($('option.pager:nth-child('+pser_e+')').val()).trigger('change');
	  }else if(pfval_s && !pfval_e){
       var serno = pser_e - pser_s;
	   var start_page= $("option.pager[value='"+pfval_s+"']");
	   var start_index = start_page.index( "option" );
	   var end_index = start_index+serno;
	   $('#page_file_end').val($('option.pager:nth-child('+end_index+')').val()).trigger('change');  
	  }else{
		 
	  }
	  
	});
	
	//-- 前往頁碼
	$('#input_page_file_start,#input_page_file_end').click(function(){
	  var page_name = $(this).prev().val();
	  if(!page_name){ // 無資料就把目前影像帶入
		if($('.page_selecter').val()){
		  $(this).prev().val($('.page_selecter').val()).trigger('change');
		}else{
		  return false;	
		}
	  }else{
		$('.page_selecter').val(page_name).trigger('change');  
	  }
	});
	
	
	//-- 複製時間
	$('#copy_start_date').click(function(){
	  var copy_target = $(this).prev().val();
	  if(!copy_target){
		system_message_alert('','時間起尚未填寫');
        return false;		
	  }	
	  
	  var checkdate = new Date(copy_target);
	  
	  if(typeof checkdate == 'NaN' ){
		system_message_alert('','時間格式錯誤');
        return false;		
	  }
	  $(this).next().val(copy_target);
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
	      var field_value = $(this).val();
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
	  
	  console.log(modify_data);
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
	
	
	
	/* == 影像調用函數 == */
	
	// 繪製影像
	
	//-- initial
	var container_w = 0;
	var container_h = 0;
	var dobj_default_center = 0;  // 數位物件顯示介面中心點
	
	// initial canvas
	if($('.data_dobj_continer').length){
	  container_w = $('.data_dobj_continer').innerWidth()-20;
	  container_h = $('.data_dobj_continer').innerHeight(); 
	  var editer_width = $('.data_edit_continer').width();
	  dobj_default_center = parseInt(editer_width + (container_w-editer_width)/2 );  
	  
	  $('.data_dobj_continer').append("<canvas id='container_canvas' width='"+container_w+"' height='"+container_h+"'/>");
	  $('.dobj_control_block').css({'left':dobj_default_center+'px','margin-left':'-250px'})
	
	var ctx = document.getElementById('container_canvas').getContext('2d');
	    ctx.globalCompositeOperation = 'copy';
		var ctp;
		var canvas = new fabric.Canvas('container_canvas');	
		
		canvas.on('mouse:down', function(options) {
			if(dobj_mask_function_flag){
			  var rect = new fabric.Rect({
				width: 100,
				height: 200,
				left: (options.e.layerX-50),
				top: (options.e.layerY-100),
				fill: 'rgba(0,0,0,0.3)',
				selectable: true,
				hasControls: true, 
			  });
			  canvas.add(rect);		
			}  	  
			//console.log(options.e.clientX, options.e.clientY);
		});
	
	
	}
	
	//-- fast key initial
	var dobj_mask_function_flag = false;
	var dobj_mouse_wheel_scale_function_flag = false;
	
	$(document).keydown(function(event){
	  switch(event.key){
		case 'e': $('#act_dobj_edit_flag').trigger('click'); break;
	    case 'm': if($('#act_dobj_edit_flag').prop('checked')) dobj_mask_function_flag = true ;  break;
	    case 'd': if($('#act_dobj_edit_flag').prop('checked')) $('#act_del_mask').trigger('click');  break;
	    case 's': dobj_mouse_wheel_scale_function_flag = true; break;
		case 'l': console.log(canvas.toObject()); break; 
	  }
	});
	
	$(document).keyup(function(event){
	  switch(event.key){
		case 'm': dobj_mask_function_flag = false; break;
		case 's': dobj_mouse_wheel_scale_function_flag = false; break;
	  }
	});
  	
	//-- 設定影像大小
	function set_active_object_scale(NewObjSize){
		 
	  var scale = NewObjSize/100;
	  
	  if (!canvas.getActiveObject()) {
        return false;
      }
	  
	  var obj_w = canvas.getActiveObject().width;
	  var scale_w = parseInt(obj_w*scale);
	  var obj_cneter = dobj_default_center - parseInt(scale_w/2);
	  
	  canvas.getActiveObject().scale(scale);
	  canvas.getActiveObject().set({'left':obj_cneter});
	  canvas.requestRenderAll();
	    
	  $('#scale_info').text( Number( parseFloat(NewObjSize /100).toFixed(1) , 2 ) );
	  $('#scale_set').val(NewObjSize);	
	  
	}
	  
    //設定scale
	$('#scale_set').mousemove(function(){
	   set_active_object_scale($(this).val());
	});
	
	
	$('.canvas-container').mousewheel(function(event, delta){
	  
	  if(!dobj_mouse_wheel_scale_function_flag){
		return false  
	  }
	  
	  var ImgRate = parseInt($('#scale_set').val());
	  ImgRate = (delta<0) ? ImgRate-10 : ImgRate+10;
	  set_active_object_scale(ImgRate);
	  
	});
	
	
	
	
	var canvas_main;      // 主要物件
	
	var img;
	var now_image = '';
	
	var set_img_w = 0;  // 最後設定影像寬
	var set_img_h = 0;  // 最後設定影像長
	
	var set_left  = 0;  // 數位物件預設位置 left
	var set_top   = 50; // 數位物件預設位置  top
	
	var page_scale = 1;
	
	var page_config = {};
	
	var img_move_flag = false;
	var img_move = {'x':0,'y':0};
	var img_object={'lx':0,'ly':0};
	
	
	
	function page_loading(){
	  if($('#main_page_loading').is(':visible')){
		$('#main_page_loading').css('display','none');  
	  }else{
		$('#main_page_loading').css('display','flex');
	  }	
	}
	
	
	function load_page(PageName){
	  	
	  $('#tmp_canvas').remove();
	  now_image = PageName;	
      
	  /*
	  img = new Image();
	  img.onload = function(){
		
		orl_img_w = img.width;
		orl_img_h = img.height;
		
		//$('.data_dobj_continer').append("<canvas id='tmp_canvas' width='"+orl_img_w+"' height='"+orl_img_h+"'/>");
	    
		// set size 
		//if(orl_img_h > orl_img_w){
		//}
		
		
		//--  scale 現在由 library 執行
		//var scale_img   = parseFloat($('.page_selecter').attr('scale')); 
		//var scale_img_h = set_img_h * scale_img;
		//var scale_img_w = set_img_w * scale_img;
		
		// count location
		//var view_area_w = container_w - 600;
		
		//img_object.lx = 600 + parseInt(view_area_w/2 - scale_img_w/2);
		//img_object.ly = 0;
		
		// draw page
		//ctp = document.getElementById('tmp_canvas').getContext('2d');
		//ctp.drawImage(img,0,0);
		//ctx.drawImage(document.getElementById('tmp_canvas'),img_object.lx,img_object.ly,scale_img_w,scale_img_h);
		
	  };
	  
	  img.src = 'dobj.php?src='+root+'browse/'+folder+'/'+PageName;
	  */
	  
	  // 標示縮圖
	  $('.thumb.atpage').removeClass('atpage');
	  $('.thumb[p="'+PageName+'"]').addClass('atpage');
	  var prev_count = $('.thumb[p="'+PageName+'"]').prevAll().length;
	  var thumb_dim_h= $('.thumb[p="'+PageName+'"]').height()+9
	  var scrolltop  = prev_count*thumb_dim_h;
	  var scroll_container = $('.dobj_thumb_block').height();
	  
	  var padding_hold = parseInt(scroll_container/2)-50;
	  var scroll_to = (padding_hold < scrolltop) ? scrolltop-padding_hold : 0;
	  $('.dobj_thumb_block').scrollTop(scroll_to);
	  
	}
	
	//-- load do to canvas
	function draw_page(PageName){
	  
	  load_page(PageName);  
	  
	  var root =  $('.dobj_control_block').data('root');
	  var folder =  $('.dobj_control_block').data('folder');
	  
	  // input image to canvas
	  canvas.clear();
	  page_loading();
	  
	  fabric.Image.fromURL('dobj.php?src='+root+'browse/'+folder+'/'+PageName, function(oImg) {
		  
		  // 預設填滿頁高
		  set_img_h = container_h - set_top - 20  ;
          set_img_w = parseInt(oImg.width * set_img_h / oImg.height);
		
		  // 計算預設x軸位置
		  set_left = dobj_default_center - parseInt(set_img_w/2);
		
		  //計算scale 
		  page_scale = Number( parseFloat(set_img_w/oImg.width).toFixed(1) , 2 );
		  
		  oImg.set({
			left: set_left,
			top: set_top,//fabric.util.getRandomInt(0, 500),
			angle: 0//fabric.util.getRandomInt(0, 90)
		  });    
		  
		  oImg.perPixelTargetFind = true;
		  oImg.targetFindTolerance = 4;
		  oImg.hasControls = oImg.hasBorders = true;     
		  oImg.scale(page_scale);  //fabric.util.getRandomInt(50, 100) / 100
	      
		  if(typeof page_config[PageName] != 'undefined'){
			var objects = page_config[PageName];
			canvas.loadFromJSON(objects);
		  
		  }else if( typeof dobjectconf[PageName] !='undefined' && dobjectconf[PageName]['mode']=='edit' ){
			
			var group = new fabric.Group([oImg], {
			  left: set_left,
			  top: set_top
			});
			
			$.each( dobjectconf[PageName]['conf'],function(i,conf){
			  group.addWithUpdate(new fabric.Rect({
                left: group.get('left') + conf['left']*page_scale,
				top:  group.get('top') + conf['top']*page_scale,
			    height:conf['height']*page_scale,
				width:conf['width']*page_scale,
			    fill: 'rgba(0,0,0,0.3)',
				selectable: true,
				hasControls: true,
			  }));
			
			});
			canvas.add(group);
		    console.log(canvas.toObject());
		  
		  }else{
			canvas_main = oImg;  
	        canvas.add(canvas_main);  
		  }
		  
		  var sel = new fabric.ActiveSelection(canvas.getObjects(), {
			canvas: canvas,
		  });
		  
		  canvas.setActiveObject(sel);
		  set_active_object_scale($('#scale_set').val());
		  
		  //canvas.requestRenderAll();
		  
	  });
	  page_loading();
	  
	  /*
	  
	  ctx.clearRect(0,0,container_w,container_h);
	  
	  if(PageName != now_image){
		load_page(PageName);  
	  }else{  
		var scale_img   = parseFloat($('.page_selecter').attr('scale')); 
		var scale_img_h = set_img_h * scale_img;
		var scale_img_w = set_img_w * scale_img;
		
		// count location
		var view_area_w = container_w - 600;
		if(!img_object.lx){
		  img_object.lx = 600 + parseInt(view_area_w/2 - scale_img_w/2);	
		}
		
		// draw page
		ctx.drawImage(document.getElementById('tmp_canvas'),img_object.lx,img_object.ly,scale_img_w,scale_img_h);  
	  }
	  
	  */ 
	   
	}
	
	
	
	
	
	
	// open image edit mode
	var new_mask = [];
	
	$('#act_dobj_edit_flag').change(function(){
	  
	  if(!$('._target').length){
		system_message_alert('','尚未選擇資料');  
	    return false;
	  }
	  
	  var data_no  = $('._target').attr('no');
	  var page_id  = $('.page_selecter').val();
	  
	  var edit_flag = $(this).prop('checked') ? 1 : 0;
	  
	  
	  if(edit_flag){
		
		canvas.setActiveObject(canvas.item(0));
		  
		if (canvas.getActiveObject().type !== 'group') {
          canvas.item(0).set({'selectable':false});
		  canvas.discardActiveObject();
		  return;
        }
         
		canvas.getActiveObject().toActiveSelection();
		canvas.requestRenderAll();
		canvas.discardActiveObject();
		
	  }else{
		
		system_loading();
		
		canvas.discardActiveObject();
	    var sel = new fabric.ActiveSelection(canvas.getObjects(), {
		  canvas: canvas,
	    });
	    canvas.setActiveObject(sel);
	    canvas.requestRenderAll();
	    
		window.setTimeout( function(){
		  
		  if (!canvas.getActiveObject()) {
            return;
          }
          if (canvas.getActiveObject().type !== 'activeSelection') {
            return;
          }
          
		  if(canvas.getActiveObject()['_objects'].length > 1){
			canvas.getActiveObject().toGroup();  
		  }
		  
		  canvas.requestRenderAll();
		  
		  var page_object = canvas.toObject();
		  page_config[$('.page_selecter').val()] = page_object;
		  
		  // encode data
		  var passer_data  = encodeURIComponent(Base64M.encode(JSON.stringify(page_object)));
		  
		  // active ajax
		  $.ajax({
			url: 'index.php',
			type:'POST',
			dataType:'json',
			data: {act:'Meta/doedit/'+data_no+'/'+page_id+'/'+passer_data},
			beforeSend: function(){  system_loading(); },
			error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
			success: 	function(response) {
			  
			  /*
			  if(response.action){
				if($("tr._target").length){
				  $("tr._target").remove();		  
				}
				location.hash = '';
				$('#edit_close').trigger('click');
			  }else{
				system_message_alert('',response.info);
			  }
			  */
			},
			complete:	function(){  }
		  }).done(function(r) {  system_loading(); });  
		  
		  system_loading();
		}, 1000 );
		
	  } 
	  
	});
    
	
	
	
	
	//-- add new mask
	$('#act_add_mask').click(function(){
	  
	  if(!$('#act_dobj_edit_flag').prop('checked')){
		system_message_alert('','請先開啟編輯按鈕');
		return false;  
	  }
		
	  var rect = new fabric.Rect({
		width: 100,
		height: 200,
		left: dobj_default_center,
		top: 100,
		fill: 'rgba(0,0,0,0.3)',
		selectable: true,
		hasControls: true, 
	  });
	  canvas.add(rect);
	});	
	
	//-- del select mask
	$('#act_del_mask').click(function(){
      
	  if(!$('#act_dobj_edit_flag').prop('checked')){
		system_message_alert('','請先開啟編輯按鈕');
		return false;  
	  }
	  
	  
	  if (!canvas.getActiveObject()) {
        system_message_alert('',"尚未選擇標籤");
		return;
      }
      
	  if (canvas.getActiveObject().type !== 'activeSelection') {
        //return;
      } 	  
      
	  if(!confirm("確定要刪除所選的標籤??")){
		return false  
	  } 
	  canvas.remove(canvas.getActiveObject());
	});
	
	
	//-- bind Photo Display Set function
	$(document).on('click','#act_switch_view',function(){
	  
	  // initial	  
	  var data_no  = $('._target').length? $('._target').attr('no') : '';
	  var photo_id = $('.page_selecter').val();
	  var main_dom = $(this);
	  var display_flag = parseInt($(this).attr('display')) ? 0 : 1;
	  
	  
	  // check process data
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  } 
	  // check process data
	  if( !photo_id.length ){
	    system_message_alert('',"尚未選擇頁面");
	    return false;
	  } 
	  
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Meta/doview/'+data_no+'/'+photo_id+'/'+display_flag},
		beforeSend: function(){  window.stop(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			
			main_dom.attr('display',display_flag);
			
			var page_pager   = $("option.pager[value='"+response.data+"']");
			var page_display = page_pager.html();  
			
			if(display_flag){
			  page_display = page_display.replace(/-ｘ/,'');  
			}else{
			  page_display = page_display+'-ｘ';	
			}
			page_pager.text(page_display);  	
			
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  });
	  
	});
	
	
	
	
	
	
	
	
	
	
	
	
	// 跳頁
	$('.page_selecter').change(function(){
	  
	  if($('#act_dobj_edit_flag').prop('checked')){
		system_message_alert('','請先關閉編輯');
		return false;  
	  }		
	  
	  //cancel_pre_action();
	  
	  // set page view mode 
	  $('#act_switch_view').attr('display',$(this).find('option:selected').attr('display'));
	  draw_page($(this).val());
	  
	});
	
	// 換頁
	$('.page_switch').click(function(){
		
	  if($('#act_dobj_edit_flag').prop('checked')){
		system_message_alert('','請先關閉編輯');
		return false;  
	  }	
	  	
		
  	  var page_now 	= $('.page_selecter').val();
	  var pager_dom = '';
      var next_dom 	= '';
	  
	  if( !$('option.pager:selected').length ){
		system_message_alert('','影像尚未讀取');
		return false;  
	  }
      
	  pager_dom = $('option.pager:selected');
	  
	  switch($(this).attr('mode')){
		case 'next': next_dom = pager_dom.next('.pager'); break;
        case 'prev': next_dom = pager_dom.prev('.pager'); break;		
	    default: return false; break;
	  }
	  
	  if(!next_dom.length){
		system_message_alert('','影像已達端點');
		return false;    
	  }
	  
	  $('.page_selecter').val(next_dom.val()).trigger('change');
	  
	});
	
	
	/* == 縮圖調用函數 == */
	
	//-- Lazy Load
	$( ".dobj_thumb_block" ).scroll(function() {
	  //$(window).trigger('resize');	
	  $(this).lazyLoadXT();
	});
	
	$(".dobj_thumb_block").lazyLoadXT({edgeY:300});
	
	//-- Query Lazy Load 
	$('.thumb').on('lazyshow', function () {
        /*
		var slot  = $(this).attr('slot');
		var accno = $(this).attr('accno');
		
		if(!parseInt(accno)  || slot=='-' ){
		  return false;	
		}
		loadQueryResultToSystem(accno,slot);*/
    }).lazyLoadXT({visibleOnly: false});
	
	//-- click thumb
	$('.thumb').click(function(){
	  $('.page_selecter').val($(this).attr('p')).trigger('change');
	});
	
	
	// 投影顯示區  meta 卷軸
    if($('.dobj_thumb_block').length){
		// 切換重新設定 scroll	
		var setting = {
		  autoReinitialise: true,
		  showArrows: false
		}; 
		
		// 設定 jScrollPane
		$('.dobj_thumb_block ').scrollbar();	 
    }
	
	
	//-- get data to editer  // 從server取得資料並放入編輯區
	function insert_data_to_dobj_form(DObject){
      DObject.type; 
	  DObject.meta;
	  DObject.alert;
	 
	  var dom_record  = $('._target'); 
	  
	  $('#dobj_load_alert').html(' - '+DObject.alert);
	  
	  if(DObject.type=='photo'){
		  var promises = [];
		  
		  // built object list
		  $.each(DObject.meta,function(fname,fconf){
			
			if($("li[no='"+fname+"']").length){
			  return;	
			}
			
			if(!parseInt(fconf.exist)){
			  return; 
			}
			var $dom = $("<li/>").attr({'no':fname,'view':fconf.view,'index':fconf.index});
			var view_control    ="<div class='data_col '><label class='data_field '> 是否顯示 </label><div class='data_value'><label class='switch' ><input type='checkbox' class='_variable dobj_view' id='' "+(parseInt(fconf.view) ? "checked":"")+" ><div class='slider round'></div></label></div></div>";
			var index_control   ="<div class='data_col '><label class='data_field '> 設為封面 </label><div class='data_value'><input type='radio' class='_variable dobj_index' id='' name='dobj_json-index' "+(parseInt(fconf.index) ? "checked":"")+" ></div></div>";
			
			$dom.append("<img class='photo' src='theme/image/photo_loading.png' data-src='photo.php?src="+fconf.addr+"' alt='"+fconf.name+"' />") 
			$dom.append("<div class='field_set dobj_conf'>"+view_control+index_control+"</div>"); 
			$dom.append("<i class='option dobj_delete' title='刪除檔案' ><i class='fa fa-trash' aria-hidden='true'></i></i>"); 
			
			$dom.appendTo('.digital_objects');
		  });
		  
		  // load object content
		  $( ".digital_objects").find('img').each(function() {
			var image = $( this ),
				src = image.attr( "data-src" );
			if ( src ) {
				
				//-- 要確保所有影像讀取完畢才進行後續綁定
				promises.push(
					$.loadImage( src ).then(function() {
						image.attr( "src", src );
						
						// 影像載入後還需確認已放入DOM中才能知道影像實際長寬
						image.on('load',function(){ 
						  var ow = Math.round($(this).width());
						  var oh = Math.round($(this).height());
						  if(image.width() < 200 || image.height() < 200){
							var scal = 200/Math.min(ow, oh);
							image.width(Math.round(ow*scal));
							image.height(Math.round(oh*scal));
						  }
						});
						
					}, function() {
						image.attr( "src", "theme/image/photo_error.png" );
					})
				);
			}
		  }); 
		  
		  // bind action 
		  $.when.apply(null, promises).done(function() { 
			
			$('.digital_objects').viewer('destroy');
			
			$('.digital_objects').attr('id',DObject.type).viewer();
			$( ".digital_objects" ).sortable({
			  placeholder: "ui-state-highlight"
			});
			$( ".digital_objects" ).disableSelection();
		  });
	  
	  }else{
		
		// obj = movie
        
		var video = $("<video/>").addClass('mejs-player')
					   .css({'width':$('.digital_objects').width()-50,'height':'400px'})
					   .attr({'id':'myVideo', 'preload':'none'})
					   .prop('controls',true)
					   .data({'src':"",'point':'0'});
		
		
		$.each(DObject.meta,function(fname,fconf){
			
			if($("li[no='"+fname+"']").length){
			  return;	
			}
			
			if(!parseInt(fconf.exist)){
			  return; 
			}
			
			var $dom = $("<li/>").attr({'no':fname,'view':fconf.view,'index':fconf.index}).addClass('video');						 
			video.append("<source type='video/mp4' src='video.php?src="+fconf.addr+"' />");
			$dom.append(video); 
			
			var view_control    ="<div class='data_col '><label class='data_field '> 是否顯示於系統 </label><div class='data_value'><label class='switch' ><input type='checkbox' class='_variable dobj_view' id='' "+(parseInt(fconf.view) ? "checked":"")+" ><div class='slider round'></div></label></div></div>";
			$dom.append("<div class='field_set'>"+view_control+"</div>"); 
			$dom.append("<i class='option dobj_delete' title='刪除檔案' ><i class='fa fa-trash' aria-hidden='true'></i></i>"); 
			$dom.appendTo('.digital_objects');
			
			
			/*
			document.getElementById('myVideo').addEventListener('pause',myHandler,false);
            function myHandler(e) {
              //document.getElementById('myVideo').src = "";
            }
			document.getElementById("myVideo").onplaying = function() {
              console.log(this);
			  this.controls = true;
            };
			*/
			
			
		});
		
		
	  }
	  
	}
	
	
	
	
	//-- bind Photo Index Set function
	$(document).on('click','.dobj_index',function(){
	  
	  // initial	  
	  var data_no  =  $('._target').length? $('._target').attr('no') : '';
	  var main_dom =  $(this).parents('li');
	  var photo_id =  main_dom.attr('no');
	  
	  var modify_data = {};
	   
	  // check process data
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  } 
	  
	  modify_data['index'] = $(this).prop('checked') ? 1 : 0 ;
	  
	  // encode data
	  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(modify_data)));
      
      // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Meta/dobjconf/'+data_no+'/'+photo_id+'/'+passer_data},
		beforeSend: function(){ window.stop(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			$('.digital_objects').find('li').attr('index',0);
			main_dom.attr('index',modify_data['index']);
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  });
	  
	});
	
	//-- bind Photo Delete function
	$(document).on('click','.dobj_delete',function(){
	  
	  // initial	  
	  var data_no  =  $('._target').length? $('._target').attr('no') : '';
	  var main_dom =  $(this).parents('li');
	  var photo_id =  main_dom.attr('no');
	  
	  var modify_data = {};
	   
	  // check process data
	  if( !data_no.length ){
	    system_message_alert('',"尚未選擇資料");
	    return false;
	  } 
	  
	  modify_data['exist'] = 0;
	  
	  // encode data
	  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(modify_data)));
      
      // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Meta/dobjconf/'+data_no+'/'+photo_id+'/'+passer_data},
		beforeSend: function(){ 
		  if(main_dom.hasClass('video')){
			window.stop(); 
		    $('video').attr('src','');
	        $('video').load(); 
		  }
		  system_loading(); 
		},
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			main_dom.remove();
			system_message_alert('alert','檔案已刪除');
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) { system_loading(); });
	  
	});
	
	
	//-- create new staff data
	$('#act_post_new').click(function(){
	    
	  // initial page
	  $('#editor_reform').trigger('click');
	  
	  // create new record
	  $tr = $("<tr/>").addClass('data_record _data_read').attr('no','_addnew');
	  $tr.append(" <td field='uno'  > - </td>");
	  $tr.append(" <td field='post_type'  ></td>");
	  $tr.append(" <td field='post_from'  > </td>");
	  $tr.append(" <td field='post_to'  ></td>");
	  $tr.append(" <td field='post_level'  ></td>");
	  $tr.append(" <td field='post_title'  ></td>");
	  $tr.append(" <td field='post_time_start'  ></td>");
	  $tr.append(" <td field='post_time_end'  ></td>");
	  $tr.append(" <td ><i class='mark24 pic_post_display_0' title='預設關閉'></i></td>");
	  
	  // inseart to record table	
	  if(!$("tr.data_record[no='_addnew']").length){
	    $tr.prependTo('tbody.data_result').trigger('click');
	  }	
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
	    data: {act:'Post/'+execute_func+'/'+data_no},
		beforeSend: function(){  system_loading(); },
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  if(response.action){
			switch(execute_func){
			  case 'dele' : act_record_dele_after(response.data);break;
			  case 'show' : act_record_show_after(response.data);break; 
			  case 'mask' : act_record_mask_after(response.data);break;
			    
				break; 
			}
		  }else{
			system_message_alert('',response.info);
	      }
		  
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading(); });
	  $('#execute_function_selecter').val('');
	});
	
	// 執行資料刪除後的動作
	function act_record_dele_after(DataNo){
      $("tr._target[no='"+DataNo+"']").remove();
	  $('#editor_reform').trigger('click');
	  $('.record_view').trigger('change');
	}
	
	// 執行資料顯示後的動作
	function act_record_show_after(DataNo){
      $("tr._target[no='"+DataNo+"']").attr('status','').find('i.pic_post_display_0').toggleClass("pic_post_display_0 pic_post_display_1");
	}
	
	// 執行資料遮蔽後的動作
	function act_record_mask_after(DataNo){
       $("tr._target[no='"+DataNo+"']").attr('status','mask').find('i.pic_post_display_1').toggleClass("pic_post_display_1 pic_post_display_0");
	}
	
	
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
	
  }); /*** end of html load ***/
      
    
	
	//-- image load 輔助函數-1
	$.createCache = function( requestFunction ) {
		var cache = {};
		return function( key, callback ) {
			if ( !cache[ key ] ) {
				cache[ key ] = $.Deferred(function( defer ) {
					requestFunction( defer, key );
				}).promise();
			}
			return cache[ key ].done( callback );
		};
	};
	  
	//-- image load 輔助函數  -2
    $.loadImage = $.createCache(function( defer, url ) {
		var image = new Image();
		function cleanUp() {
			image.onload = image.onerror = null;
		}
		defer.then( cleanUp, cleanUp );
		image.onload = function() {
			defer.resolve( url );
		};
		image.onerror = defer.reject;
		image.src = url;
    });
	
	
	
	   
	
	
	
  