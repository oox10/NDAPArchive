/* [ Admin Meta Built Function Set ] */
	
  $(window).on('load',function () {   //  || $(document).ready(function() {		
	
	/***------------------------***/
	/*   [ TASK PAGE FUNCTION ]   */
	/***------------------------***/
	
	//-- data record filter
	$("input[type='radio'][name='record_type']").click(function(){
	  if($(this).prop('checked')){
		var record_flag = $(this).val();
		location.search='?act=Built/index/'+record_flag;
	  }	
	});
	
	//-- datepicker initial
	$("#post_time_start,#post_time_end").datepicker({
	    dateFormat: 'yy-mm-dd',
	    onClose: function(dateText, inst) { 
	      if(/\d{4}-\d{2}-\d{2}$/.test(dateText)){
		    $(this).val(dateText+' 00:00:01');
		  }
	    } 
	});
	
	//-- 重新設定搜尋
	$( ".search_input").unbind( "keydown keyup" );
    $(".act_search").click(function(){});
	
	//-- 設定分頁 資料超過上萬，分頁自訂
	$(document).off('click','.page_to');
	
	//-- 切頁換頁
	$('.page_to').click(function(){
	  if(!$(this).attr('page')){
	    return false;
	  }	
	  var link = location.search.replace('/#.*?$/','').split('/');
	  link[2] = $(this).attr('page');
	  location.search = link.join('/');
	});
	
	$('.page_jump').change(function(){
	  if(!$(this).val()){
	    return false;
	  }	
	  var link = location.search.replace('/#.*?$/','').split('/');
	  link[2] = $(this).val();
	  location.search = link.join('/');
	});
	
	//-- select collection
	$('.select_all').change(function(){
	  var member_class = $(this).attr('member');
	  $('.'+member_class).prop('checked',$(this).prop('checked'));
	});
	
	// zong select all
	$('#act_select_all_zong').click(function(){
	  $(".zselect").prop('checked',true);
	});
	
	// 全宗單選
	$('.zname').click(function(){
	  $('.zname').removeClass('selected');
	  $(".zselect").prop('checked',false);
	  $(this).addClass('selected').prev().prop('checked',true);
	  
	  /*
	  var zong = $(this).parent().attr('no');
      var search = get_search_condition();
	  search['query'] = [{'field':'zong_name','value':$(this).data('zname'),'attr':'+'}];
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	  */
	});
   
	
	
	// 取得檢索設定
	function get_search_condition(){
	  
	  // 搜尋參數
		/* 
		zongs = [ ]
		limit[  // 條件篩選
		  none : 不限制   
		  secret : 密件  private: 隱私  mask : 遮頁資料  close : 未開放  newest : 最新資料
		]
		search[  // 一般搜尋
		  date_start : 
		  date_end : 
          condition :
		]
		order[
		  modify_time
		  identifier
		  date_start
		]
		*/
	  
	  
	  // 檢索條件
	  var search = {};
	  
	  // 全宗
	  search['zongs'] = $("input[name='data_zong']:checked").map(function(){ return $(this).val(); }).get().join(';');
	  
	  // 搜尋
	  search['search'] = {};
	  if($('#filter_date_start').val()){
		search['search']['date_start'] = $('#search_date_start').val();
	  }
	  if($('#filter_date_end').val()){
		search['search']['date_end'] = $('#search_date_end').val();
	  }
	  if($('#filter_search_terms').val()){
		search['search']['condition'] = $('#filter_search_terms').val();
	  }
	  
	  search['limit'] = $('input.mlimit:checked').val();
	  
	  
	  return search;		
	}
	
	//-- search submit  : 搜尋資料
	$('#filter_submit').click(function(){
	   var search = get_search_condition();
	   if(search['zongs']==''){
		  system_message_alert('','尚未選擇全宗'); 
	      return false;
	   }
	   location.href = 'index.php?act=Meta/search/'+$('.record_pageing').val()+'/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	});
	
	//-- reset_filter
	$('#reset_filter').click(function(){
  	  $('.zselect').prop('checked',true);
	  $('.mlimit').prop('checked',false);
	  $('#filter_date_start,#filter_date_end,#filter_search_terms').val('');
	});
	
	
	//-- paging change
	$('.record_pageing').change(function(){
	  $('#filter_submit').trigger('click');	
	});
	
	
	
	
	
	
	//-- receive task  : 使用者受領工作 
	$('.act_task_receive').click(function(){
	  
	  var task_dom = $(this).parents('.data_record');
	  var data_no  = task_dom.attr('no');
	  
	  if(!data_no.length){
		system_message_alert('','尚未選擇資料');
        return false;		
	  }
	  
	  // active ajax
      $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Built/receive/'+data_no},
		beforeSend: function(){  system_loading() },  
        error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
	    success: 	function(response) {
		  //console.log(response);
	      if(response.action){
			
			var meta = response.data.record;
		    
			system_message_alert('alert','任務已領取');
			
			var record_type = $("input[name='record_type']:checked").val();
			if(record_type=='init'){
			  task_dom.remove();   	
			}else{
			  // update
			  task_dom.find("td[field='handler']").text(meta['handler']);
			  task_dom.find("td[field='_update_time']").text(meta['_update_time']);
			  task_dom.find('.task_option').attr('own',meta['@own']);
			}
			
			
		  }else{
			system_message_alert('',response.info);
	      }
	    },
		complete:	function(){  }
      }).done(function(r) {  system_loading()  });
	  
	});
	
	
	//-- record select all 全選本頁
	if($('.act_select_all').length){
	  $('.act_select_all').change(function(){
		$('.act_selector').prop('checked',$(this).prop('checked')); 
	  });
	}
	
	//-- record select one 單選本頁
	$('.act_selector').click(function(){
	  var select_all_fleg = $('.act_selector').length == $('.act_selector:checked').length ? true : false;
	  $('.act_select_all').prop('checked',select_all_fleg);  	
	});
	
	
	//-- select batch function
	$('#act_execute_batch').click(function(){
	  
	  if(!$('#act_record_batch_to').val()){
		system_message_alert('','尚未選擇執行工作');  
	    return false;
	  }
	  
	  var act_action = $('#act_record_batch_to').val();
	  var act_name   = $("#act_record_batch_to").find("option[value='"+act_action+"']").html();
	  var act_info   = $("#act_record_batch_to").find("option[value='"+act_action+"']").attr('title');
	  
	  
	  if(!$('.act_selector:checked').length){
		system_message_alert('','尚未選擇資料');  
	    return false;
	  }
	  var records    = $('.act_selector:checked').map(function(){return $(this).val(); }).get();
	  
	  // confirm to admin
	  if(!confirm("確定要對勾選 [ "+records.length+" ] 筆資料執行 : "+act_name+"?,\n這將會使得 "+act_info+"")){
	    return false;  
	  }
	  
	  var paser_data = encodeURIComponent(Base64M.encode(JSON.stringify(records)));
	  
	  if(act_action=='export'){
		
		//-- 解決 click 後無法馬上open windows 造成 popout 被瀏覽器block的狀況
	    newWindow = window.open("","_blank");
		$.ajax({
		  url: 'index.php',
		  type:'POST',
		  dataType:'json',
		  data: {act:'Meta/batchexport/'+paser_data},
		  beforeSend: 	function(){ system_loading();  },
		  error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		  success: 		function(response) {
			if(response.action){  
			  newWindow.location.href = 'index.php?act=Meta/getexport/'+response.data.batch.fname;
			}else{
			  newWindow.close();
			  system_message_alert('',response.info);
			}
		  },
		  complete:		function(){   }
	    }).done(function() { system_loading();   });	
		
	  }else{
		  // active ajax
		  $.ajax({
			url: 'index.php',
			type:'POST',
			dataType:'json',
			data: {act:'Meta/batch/'+paser_data+'/'+act_action},
			beforeSend: function(){  system_loading(); },
			error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
			success: 	function(response) {
			  if(response.action){
				system_message_alert('alert','已成功執行:'+act_name+' / '+response.data.batch+' 筆');
				$('#act_record_batch_to').val('');
				$('.act_selector').prop('checked',false);
				var batch_set = act_action.split('/');
				$.each(records,function(i,no){
				  $(".data_record[no='"+no+"']").find('.status._variable.'+batch_set[0]).attr('data-flag',batch_set[1]);	
				});
			  }else{
				system_message_alert('',response.info);
			  }
			},
			complete:	function(){  }
		  }).done(function(r) {  system_loading(); });  
		  
	  }
	  
	  
	});
	
	
	
	
	
	
	//-- execute task : 編輯資料 
	$('.act_meta_getin').click(function(){
	  
	  var task_dom = $(this).parents('.data_record');
	  var book_no  = task_dom.attr('collection');
	  var data_no  = task_dom.attr('no');
	  
	  if(!data_no.length){
		system_message_alert('','尚未選擇資料');
        return false;		
	  }
	  window.open('index.php?act=Meta/editor/'+book_no+'/'+data_no);
	});
	

	//-- execute task : 管理者檢視工作 
	$('.act_task_review').click(function(){
	  var task_dom = $(this).parents('.data_record');
	  var data_no  = task_dom.attr('no');
	  
	  if(!data_no.length){
		system_message_alert('','尚未選擇資料');
        return false;		
	  }
	  location.href='index.php?act=Built/review/'+data_no;
	});
	
	
	//-- export tasks
	$('#act_export_tasks').click(function(){
      if(!$('.selecter:checked').length){
		system_message_alert('','尚未勾選資料');  
	    return false;
	  }
	  var tasks = $('.selecter:checked').map(function(){return $(this).val(); }).get().join(';');
	  window.open('index.php?act=Built/export/'+tasks);
	});
	
	
	
	
	
  }); /*** end of html load ***/
	
	
	   
	
	
	
  