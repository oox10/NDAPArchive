/* [ Admin Meta Media Type Built Function Set ] */
	
  $(window).on('load',function () {   //  || $(document).ready(function() {		
	
	
	/***-------------------------***/
	/* [ BUILT CONTENTS FUNCTION ] */
	/***-------------------------***/
	
	//-- initial video tag
	if( $('video').length){
  	  var vwidth = $('video').width();
	  $('video').css('height',parseInt(vwidth/4*3));
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
	
	//-- get data to editer  // 放入區段設定
	function insert_refer_to_meta_form(meta_dobjconfig){ 
	  // 設定影片切頁
	  var dom_record  = $('._target');
	  
	  $('#meta_tags_queue').empty();
	  if(typeof meta_dobjconfig.position != 'undefined'){
		$.each(meta_dobjconfig.position,function(SerialNum,TagConfig){
		  var tag = $('#meta_tag_template').find('tr.tag_record').clone();
		  tag.addClass('meta_segment').attr({'src':TagConfig.file,'play':0,'stime':TagConfig.pointer.stime,'etime':TagConfig.pointer.etime,'no':SerialNum});
		  tag.find('.tag_num').text( (SerialNum)+'.');
		  tag.find('.tti.stime').attr('src','thumb.php?src='+meta_dobjconfig['dopath']+'thumb/'+dom_record.attr('collection')+'/'+TagConfig.file+'-'+TagConfig.pointer.stime.replace(/:/g,'')+'.jpg');
		  tag.find('.tti.etime').attr('src','thumb.php?src='+meta_dobjconfig['dopath']+'thumb/'+dom_record.attr('collection')+'/'+TagConfig.file+'-'+TagConfig.pointer.etime.replace(/:/g,'')+'.jpg');
		  tag.find('.tag_fname').text(TagConfig.file);
		  tag.find('.pointer.stime').val(TagConfig.pointer.stime);
		  tag.find('.pointer.etime').val(TagConfig.pointer.etime);
		  tag.appendTo($('#meta_tags_queue'));			  
		});    
	  }
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
			  $('#system_breadcrumbs').find('li#target_record').remove().end().append("<li class='breadcrumb' id='target_record' >"+data_load['META-StoreNo']+" (編輯中) </li>");
			  //active_header_footprint_option('record_selecter',dataObj.identifier,'_return_list');
			  
			  
			  
			  // 處理數位檔案
			  data_dobj= response.data.meta.dobj;
			  var meta_dobjconfig = data_dobj;
			  insert_refer_to_meta_form(meta_dobjconfig)
			 
			  
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
	
	
	
	
	/* == 多媒體檔案函數 == */
	
	//-- load video from thumb
	$('.mfile').click(function(event, timeplay ){
	  var collection = $('._target').attr('collection');
      var media_file = $(this).data('file');	  
	  if( !media_file ){
		return false;		
	  }	
	  
	  
	  var media_link = media_file;
	  
	  // 處理起迄時間
	  if(timeplay){
		var t = timeplay.split(':');
        var start_second = parseInt(t[0])*3600 + parseInt(t[1])*60 + parseInt(t[2]);		 
	  }
	  
	  
	  var active_src = 'video.php?src=MEDIA/browse/'+collection+'/'+media_link;  //將要播放位置
	  
	  var video = document.getElementById('meta_media_tv');
	  video.pause();	
	  
	  if( video.currentSrc.split('/').pop() != active_src.split('/').pop() ){
		$('#meta_media_play').attr('src',active_src);  
		video.load();  
	  
	    $('#mediaqueue')
	  
	  
	  }
	  
	  video.currentTime = start_second;
	  video.play();//.prop('autoplay',true);		
	  
	});
	
	//-- segment play & pause
	$(document).on('click','.segment_play',function(){
      
	  var main_dom = $(this).parents('tr.tag_record');	  
      var play_media = main_dom.attr('src');
	  var play_start = main_dom.attr('stime');
	  var play_end   = main_dom.attr('etime');
	  
	  var play_status= parseInt(main_dom.attr('play'));
	  
	  var video = document.getElementById('meta_media_tv');
	  
	  // reset all segment
	  $('tr.tag_record').attr('play','0');
	  
	  if(play_status){
		video.pause();  		
	  }else{
		// 確認影片是否存在folder queue中
	    if(!$(".mfile[data-file='"+play_media+"']").length){
		  system_message_alert('','影片不存在');  
	      return false;
	    }
		
	    $(".mfile[data-file='"+play_media+"']").trigger('click',[play_start]);
		main_dom.attr('play','1');  
	  }
	  
		
	});
	
	//-- segment quick pointer
	$(document).on('click','.pointer',function(){
	  var main_dom = $(this).parents('tr.tag_record');	
	  var play_media = main_dom.attr('src');
	  var play_start = $(this).val();
	  
	  // 確認影片是否存在folder queue中
	  if(!$(".mfile[data-file='"+play_media+"']").length){
	    system_message_alert('','影片不存在');  
	    return false;
	  }
	
	  $(".mfile[data-file='"+play_media+"']").trigger('click',[play_start]);
	  main_dom.attr('play','1');  
	  
	});
	
	
	
	
	
	//-- segment play & pause
	$(document).on('click','.segment_edit',function(){
      
	  var main_dom = $(this).parents('tr.tag_record');	  
      
	  
	  var toedit = parseInt(main_dom.attr('edit')) ? false : true;
	  
	  if(!toedit){  // 關閉編輯代表要儲存
		if(!save_video_segments()){
		  return false;	
		}
	  }
	  
	  main_dom.find('.tag_time').children('input').prop('disabled',(toedit ? false:true ));
	  main_dom.find('.segment_dele').prop('disabled',(toedit ? false:true ));
	  main_dom.attr('fail',0);
	  main_dom.attr('edit',(toedit ? 1 : 0));
	  
	})
	
	//-- segment create
	$(document).on('click','#act_create_segment',function(){
     
	  //檢查是否還有新增區段未完成
	  if($(".meta_segment[no='_new']").length){
		system_message_alert('','同時間僅能新增一個區段，請先儲存後再新增');  
	    return false;
	  }
	  
	  var tag = $('#meta_tag_template').find('tr.tag_record').clone(); 
	  tag.addClass('meta_segment').attr({'src':'','edit':1,'play':0,'stime':'','etime':'','no':'_new'});
	  tag.find('input').prop('disabled',false);
	  tag.find('.segment_dele').prop('disabled',false);
	  tag.appendTo($('#meta_tags_queue'));			  
	
	});
	
	
	
	
	
	//-- segment insert time
	$(document).on('click','.act_set_time',function(){
	  
	  var timeform = $(this).prev();
	  var timetype = '';
	  var main_dom = $(this).parents('tr.tag_record');
      var video = document.getElementById('meta_media_tv');
	  
	  //暫停影片 
	  video.pause();
	  $('tr.tag_record').attr('play','0');
	  
	  
	  if(!video.currentSrc){
		system_message_alert('','目前尚未設定影片');
        return false;		
	  }
	  
	  var video_path = video.currentSrc.split('/');
	  var video_file = video_path.pop().replace(/\#.*?$/,'');
	  var now_time   = parseInt(video.currentTime);
	  var now_t_set  = [];
	  
	  if(timeform.hasClass('stime')){
		timetype = 'stime';  
	  }else{
		timetype = 'etime';  
	  }
	  
	  // second to time format
	  now_t_set[0] = parseInt(now_time/3600).toString().padStart(2,'0'); 
	  now_t_set[1] = parseInt(now_time%3600/60).toString().padStart(2,'0'); 
	  now_t_set[2] = parseInt(now_time%60).toString().padStart(2,'0'); 
	  
	  main_dom.attr({'src':video_file});
	  main_dom.find('.tag_fname').text(video_file)
	  main_dom.attr(timetype,now_t_set.join(':'));
	  timeform.val(now_t_set.join(':'));
	  main_dom.find('.tag_num').text('new');
	  
	  // get screenshot
	  var canvas = document.getElementById('video_screenshot');
      canvas.width = $(".mfile[data-file='"+video_file+"']").data('vw');
      canvas.height = $(".mfile[data-file='"+video_file+"']").data('vh');
      var ctx = canvas.getContext('2d');
      ctx.globalCompositeOperation = 'copy';
	  
	  
	  // if you want to preview the captured image,
      // attach the canvas to the DOM somewhere you can see it.
	  
	  //draw image to canvas. scale to target dimensions
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
      //convert to desired file format
      var dataURI = canvas.toDataURL('image/jpeg'); // can also use 'image/png'
      main_dom.find('img.tti.'+timetype).attr('src',dataURI);	
		
	});
	
	//-- segment delete
	$(document).on('click','.segment_dele',function(){
	  
	  var main_dom = $(this).parents('tr.tag_record');
      
	  // stop video 
	  var video = document.getElementById('meta_media_tv');
	  video.pause();
	  $('tr.tag_record').attr('play','0');
	  
	  
	  if(main_dom.attr('no') == '_new'){
		main_dom.remove();  
	    return true;
	  }
	  
	  if(!confirm("確定要刪除標記!?")){
		return false;  
	  }
	  
	  main_dom.remove();  
	  save_video_segments();
	});
	
	
	
	
	
	
	
	function save_video_segments(){
	  
	  // save segment
	  
	  // get target meta
	  var data_no    = $('._target').attr('no');
	  
	  if(!data_no.length){
		system_message_alert('','尚未選擇資料');  
	    return false;
	  }
	  
	  var checker = false;
	  var video_segment = [];
	  
	  // pass segment
	  $('tr.tag_record.meta_segment').each(function(){
		
		var segment_no    = $(this).attr('no');
		var segment_file  = $(this).attr('src');
		var segment_stime = $(this).attr('stime');
		var segment_etime = $(this).attr('etime');
		
		if(!segment_file || !$(".mfile[data-file='"+segment_file+"']").length ){
		  $(this).attr('fail','1');
		  system_message_alert('','標記檔案不存在，請重新標記');
          checker = false;		  
		  return false;
		}
		
		if(!segment_stime || !segment_etime ){
		  $(this).attr('fail','1');
		  system_message_alert('','標記時間設定不完整');	
		  checker = false;	
		  return false;
		}
		
		var vsegment = {
		  'file':segment_file,
          'pointer':{
			'stime':segment_stime,
            'etime':segment_etime			
		  }		  
		};
		
		var sthumb_src = $(this).find('img.tti.stime').attr('src');
		if(sthumb_src.match(/^data:image/)){
		  var base64data = sthumb_src.split(',')
		  vsegment['sthumb'] = base64data[1];	
		}
		
		var ethumb_src = $(this).find('img.tti.etime').attr('src');
		if(ethumb_src.match(/^data:image/)){
		  var base64data = ethumb_src.split(',')
		  vsegment['ethumb'] = base64data[1];	
		}
		
		video_segment.push(vsegment)
		checker = true;
	  
	  });
	  
	  // check if is change 
	  if(checker && JSON.stringify(video_segment) != JSON.stringify(data_dobj.position)){
		var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(video_segment)));
		// active ajax
		$.ajax({
			url: 'index.php',
			type:'POST',
			dataType:'json',
			data: {act:'Meta/media/'+data_no+'/'+passer_data},
			beforeSend: function(){  system_loading(); },
			error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
			success: 	function(response) {
			  
			  if(response.action){
			    data_dobj = response.data
			    insert_refer_to_meta_form(data_dobj);
				
			  }else{
			    system_message_alert('',response.info);
	          } 
			  
			},
			complete:	function(){  }
		}).done(function(r) {  system_loading(); }); 
	  }
	  
	  return checker;
	  
	}
	
	
	
	
	
	
	
	
	
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
	
	
	
	   
	
	
	
  