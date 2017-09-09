  /* [ Client Archive Function Set ] */
  /* 檢索系統 JS Func */	
  
  
  $(window).on('load',function () {   //  || $(document).ready(function() {		
  
    
	$('#signin').click(function(){
	  location.href="index.php?act=Landing/account#signin";
	});
	
	
	$('#signout').click(function(){
	  if(!confirm("確定要登出系統?")){
	    return 0;
	  }    
      location.href="index.php?act=Landing/logout";
	});
	
	
	// --
	if($('.system_member_area').length){
	  $('.system_member_area').draggable();	
	}
	
	// 開關初始化
	$('.func_switch').each(function(){
	  var main_dom = $(this).parent();
      if(main_dom.next().is(":visible")){
		$(this).html('<i class="fa fa-minus-square" aria-hidden="true"></i>');  
	  }else{
		$(this).html('<i class="fa fa-plus-square" aria-hidden="true"></i>');  
	  }
	});
	
	// 功能開關
	$('.func_switch').click(function(){
	  var main_dom = $(this).parent();
      if(main_dom.next().is(":visible")){
		main_dom.next().hide();  
		$(this).html('<i class="fa fa-plus-square" aria-hidden="true"></i>');
	  }else{
		main_dom.next().show();  
	    $(this).html('<i class="fa fa-minus-square" aria-hidden="true"></i>');
	  }
	});
	
	// level switch
	$('li.level > .option').click(function(){
	  var dom = $(this).parent('li');
	  var option = (parseInt(dom.attr('switch')) ^ 1);
	  var code = dom.attr('id');
	  if(option){
		$("li.level[up='"+code+"']").show()    
	  }else{
		$("li.level[up^='"+code+"']").attr('switch', option ).hide(); 
	  }
	  dom.attr('switch', option );
	});
	
	// level search
	$('li.level > .name').click(function(){
      var dom = $(this).parent('li');
	  var code = dom.attr('id');
	  
	  /*
	  var level=[];
	  while(code.length){
        level.unshift($('#'+code).find('.name').text());
	    code = code.substr(0,(code.length-2));
	  }
	  */
	  var search = get_search_condition();
	  search['query'] = [];
	  search['zong']  = dom.data('set').match(/^檔案/) ? ['檔案'] : ['議事錄','公報','議事影音'];
	  search['query'][0] = {'field':'serial','value':dom.data('set')}
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	});
	
	// history more
	$('.list_more').click(function(){
      var list_dom = $(this).parent().prev();	  
	  var mode = list_dom.attr('mode');	
	  if(mode =='limit' ){
		list_dom.attr('mode','show');  
	  }else{
		list_dom.attr('mode','limit');    
	  }
	});
	
	
	// 搜尋模式開關
	$('li.mode_switch').click(function(){
	  $('.mode_switch.atthis').removeClass('atthis');
	  switch($(this).attr('id')){
	    case 'advance': $('#advance_search_block').css({'position':'relative','visibility': 'visible'});break;
	    case 'general': $('#advance_search_block').css({'position':'absolute','visibility': 'hidden'}); break;
		case 'initial': location.href='index.php?act=Archive/index'; return false; break;
	  }
	  $(this).addClass('atthis');
	});
	
	// 搜尋模式初始化
	if($('.mode_switch.atthis').length){
	  $('.mode_switch.atthis').trigger('click');
	}else{
	  $('.mode_switch:first').trigger('click');
	}
	
	
	// 全宗勾選
	$(".zselect").change(function(){
	  
	  var checked = $(this).prop('checked');
	  var zong_type = $(this).attr('name');
	  
	  if(checked){ //若勾選要將不同的類型排除
		$(".zselect[name!='"+zong_type+"']").map(function(){
		  $(this).prop('checked',false);  
		}); 
        
		// 第一個勾選 archive zong 要全選
        if(zong_type =='archive' && $(".zselect:checked").length==1){
		  $(".zselect[name='archive']").prop('checked',true);	
		} 		
	  }
	  
	  $(".zselect").map(function(){
		if($(this).prop('checked')){
		  $(this).next().addClass('selected');  
	    }else{
		  $(this).next().removeClass('selected');   
	    }  
	  });
	  
	});
	
	// 全宗單選
	$('.zname').click(function(){
	  $('.zname').removeClass('selected');
	  $(".zselect").prop('checked',false);
	  $(this).addClass('selected').prev().prop('checked',true);
	  
	  var zong = $(this).parent().attr('no');
      var search = get_search_condition();
	  if(!search['query'].length){
		search['query'] = [{'field':'zong','value':$(this).text(),'attr':'+'}];   
	  }
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	  
	});
   
	
	
	
	
	// 欄位搜尋模式變換
	$('#search_field').change(function(){
	  var search_input_mode = $(this).find('option:selected').attr('mode');
      $('#search_input_mode > li').css('display','none');
	  $('#search_input_mode').find('li#'+search_input_mode).css('display','flex');
	});
	
	// 夾贅詞變更
	$('.ap_input').change(function(){
	  $(this).parent().find('.term_focus').val('');
	});
	
	
	// 進階查詢外掛
	
	// 增加條件
	$('#add_search_term').click(function(){
	  var new_search = $('.additional_search._template').clone();
      new_search.removeClass('_template');
	  new_search.find('input').val('');  	  
      new_search.find('select').val('');  	  
      new_search.appendTo('#additional_continer'); 
	});
	
	// 刪除條件
	$(document).on('click','.delete_search_add',function(){
	  if(!$(this).parents('li').hasClass('_template')){
		$(this).parents('li').remove();	  
	  }else{
		$(this).parents('li').children().val('');
		system_message_alert('','不可以刪除第一個');  
	  }
	});
	
	// 日期範圍
	if($("#dateslider").length){
	  
	  $("#dateslider").width($('#search_input').outerWidth()+5)
	  var date_bound = [];
	  $('.date_bound').each(function(i){
		var date_string = $(this).data('search') ? $(this).data('search'):$(this).data('dateset');
		date_bound[i]   = new Date(date_string)  
		$(this).text($(this).data('dateset')); 
	  });
	  
	  $("#dateslider").dateRangeSlider({
		bounds: {min: new Date('1880-01-01') , max: new Date('2016-01-31') },
		defaultValues: {min: date_bound[0], max:date_bound[1]},
        wheelMode: "scroll",
		wheelSpeed: 30,
		enabled:$('#select_date_null').is(':checked') ? false:true,
        //step:{ years: 1}		
	  });
       
	  if($('#select_date_null').is(':checked')){
		$("#dateslider").css('opacity',0.3);    
	  }

	}
	
	// 設定僅搜尋無日期
	$('#select_date_null').change(function(){
	  if($(this).is(':checked')){
		$("#dateslider").dateRangeSlider("disable").css('opacity',0.3);  
	  }else{
		$("#dateslider").dateRangeSlider("enable").css('opacity',1);  ;    
	  }
	});
    
	// 重設日期範圍
	$('#reset_daterange_set').click(function(){
	  $('.date_bound').each(function(i){
		var date_string = $(this).data('dateset');
		date_bound[i]   = new Date(date_string)  
	  });	
	  $("#dateslider").dateRangeSlider("values", date_bound[0] , date_bound[1]);	
	});
	
	// 重設類型篩選
	$('#reset_format_sel').click(function(){
	  $("input[name='format']").prop('checked',false);	
	});
	
	
	
	
	// 取得檢索設定
	function get_search_condition(){
	  // 主檢索
	  var search = {};
	  
	  var search_field = $('#search_field').val();
	  
	  search['accnum'] = $("input[name='accnum']:checked").val();
	  search['query']  = [];
	  search['query'][0] = {'field':$('#search_field').val(),'value':''}
	  if( search_field == 'termpat'){
		search['query'][0]['attr']  = 't%';
		search['query'][0]['value'] = $('#termpat_search_input').val();
		search['query'][0]['value'] = $('#termpat_search_input_prev').val().length ? '{'+$('#termpat_search_input_prev').val()+'}'+search['query'][0]['value'] : search['query'][0]['value'];
		search['query'][0]['value'] = $('#termpat_search_input_back').val().length ? search['query'][0]['value']+'{'+$('#termpat_search_input_back').val()+'}': search['query'][0]['value'];
  	    search['query'][0]['value'] = $('#termpat_search_target').val().length ? search['query'][0]['value']+'@'+$('#termpat_search_target').val(): search['query'][0]['value']+'@';
	 }else if( search_field == 'clipterm'){
		search['query'][0]['attr']  = 'c%';
		search['query'][0]['value'] = $('#clipterm_search_input_prev').val()+'{'+$('#clipterm_search_input').val()+'}'+$('#clipterm_search_input_back').val();
		search['query'][0]['value'] = $('#clipterm_search_targte').val().length ? search['query'][0]['value']+'@'+$('#clipterm_search_targte').val(): search['query'][0]['value']+'@';
	  }else{
		search['query'][0]['value'] = $('#search_input').val();
	  }
	  
	  // 次檢索
      $('.additional_search').each(function(){
		var term = $(this).children('.term').val() ? $(this).children('.term').val() : '';
		if(!term){
		  return true;	
		}
		var attr = $(this).children('.attr').val() ? $(this).children('.attr').val() : '+';
		var field= $(this).children('.field').val() ? $(this).children('.field').val() : '_all';
		search['query'].push({'field':field,'value':term,'attr':attr})
	  });
      
	  // format
	  //if($("input[name='format']:checked").length){
	  //	search['format'] =  $("input[name='format']:checked").map(function(){return $(this).val();}).get();
	  //}
	  
	  // zong
	  if($("input.zselect:checked").length){
		search['zong'] =  $("input.zselect:checked").map(function(){return $(this).val();}).get();
	  }
	  
	  // date
	  /*
	  if($('#select_date_null:checked').length){
		search['yearrange'] = ['none'];  
	  }else{
		var dateValues = $("#dateslider").dateRangeSlider("values");
	    var min_month = (dateValues.min.getMonth() < 9) ? '0'+(dateValues.min.getMonth()+1) : (dateValues.min.getMonth()+1);
        var max_month = (dateValues.max.getMonth() < 9) ? '0'+(dateValues.max.getMonth()+1) : (dateValues.max.getMonth()+1);
        var min_day = (dateValues.min.getDate() < 10) ? '0'+dateValues.min.getDate() : dateValues.min.getDate();
        var max_dat = (dateValues.max.getDate() < 10) ? '0'+dateValues.max.getDate() : dateValues.max.getDate();
	    var min_date = dateValues.min.getFullYear()+'-'+min_month+'-'+min_day;
	    var max_date = dateValues.max.getFullYear()+'-'+max_month+'-'+max_dat;
	    if( $('#date_range_start').data('dateset')!=min_date || $('#date_range_end').data('dateset')!=max_date ){
		  search['dayrange']=[min_date,max_date]  
	    }  
	  }	
	  */
	  
	  // domconf
	  search['domconf'] = {};
	  if($('._domconf').length){
		$('._domconf').each(function(){
          if($(this).hasClass('_setval')){
			search['domconf'][$(this).attr('id')] = $(this).val();  
		  }else if($(this).hasClass('_setshow')){ 	
		    search['domconf'][$(this).attr('id')] = $(this).css('display');  
		  }	  
		});  
	  }
	  
      return search;		
	}
	
	
	
	
	// 送出查詢
	$('#search_submit').click(function(){
	  
	  var search_field = $('#search_field').val();
	  var search_check = true;
	  
	  // 檢查搜尋內容
	  switch(search_field){
		case 'termpat':
		  if(!$('#termpat_search_input').val().length){
			$('#termpat_search_input').focus();
			system_message_alert('error',"請輸入綴詞主體");	
		    search_check = false;
		  }
          
		  if( !$('#termpat_search_input_prev').val().length && !$('#termpat_search_input_back').val().length ){
			$('#termpat_search_input_prev').focus();
			system_message_alert('error',"請指定至少一個前後綴詞長度");	
			
		    search_check = false;
		  }
		  break;
		
		case 'clipterm':
		  if(!$('#clipterm_search_input_prev').val().length || !$('#clipterm_search_input_back').val().length){
			$('#clipterm_search_input_prev').focus();  
			system_message_alert('error',"夾詞前後條件都必須填寫");	
		    search_check = false;
		  }
		  break;
		
		default:
		  if(!$('#search_input').val().length ){
			system_message_alert('error',"請輸入搜尋條件");  
			search_check = false;
		  }
		  break;
	  }
	  
	  
	  if(!search_check){
		return false;  
	  }
	  
	  
      if($(this).prop('disabled')){
		system_message_alert('',"系統正在查詢中，請稍候..");
	    return false;    
	  }
	  $(this).prop('disabled',true);
	  
	  var search = get_search_condition();
	  
	  // 介面設定
	  //console.log(search);
	  //console.log(encodeURIComponent(Base64M.encode(JSON.stringify(search))));
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	
	});
	
	
	
	
	
	// 後分類快速導覽
	

	// 後分類切換
	$('.facets_select').change(function(){
	  $('.term_list._domconf').hide();
      $('ul#'+ $(this).val()).show();
	  
	  if($('#filter_queue').length){ 
	    $('#filter_queue').empty();
        $(".filter[name!='"+$(this).val()+"']:checked").each(function(){
		  var dom = $(this).parent().clone() ;
		  dom.find('.filter').removeClass('filter').addClass('quick');
		  dom.appendTo($('#filter_queue'))
	    });
	  }
	});
	$('.facets_select').trigger('change');
	//$('input.filter').prop('checked',false);
	
	// 快速篩選區移除
	$(document).on('click','.quick',function(){
	  $(".filter[value='"+$(this).val()+"']").trigger('click');
	});
	
	// 後分類單選
	$(document).on('click','.term_name',function(){
	  var dom = $(this).prev();	
	  if(dom.hasClass('capture')){   // term capture mode diference
		$('.term_focus').val(dom.val());
	    var search = get_search_condition();
	  }else{
		var search = get_search_condition();  
		search['filter'] = {};
	    search['filter'][dom.attr('name')]=[dom.val()];  
	  }
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	});
	
	
	// 後分類送出
	$(document).on('click','.filter',function(){
	  if($(this).prop('disabled')){
		system_message_alert('',"系統正在查詢中，請稍候..");
	    return false;    
	  }
	  //$(this).prop('disabled',true);
	  var search = get_search_condition();
	  
	  if($('.filter:checked').length){
        search['filter'] = {};
		
		$('#facetsby').find('option').each(function(){
		  var field = $(this).val();
		  if($(".filter[name='"+field+"']:checked").length){
			search['filter'][field] = $(".filter[name='"+field+"']:checked").map(function(){return $(this).val();}).get();  
		  }
		})
		/* 
		var field = $(this).attr('name');
        search['filter'][field] = $(".filter[name='"+field+"']:checked").map(function(){return $(this).val();}).get();
	    */ 
	  }
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	});
	
	// 相關詞彙多選
	$(document).on('click','.capture',function(){
	  if($(this).prop('disabled')){
		system_message_alert('',"系統正在查詢中，請稍候..");
	    return false;    
	  }
	  if($('.capture:checked').length){
		var term_focus = $(".capture:checked").map(function(){return $(this).val(); }).get();
		$('.term_focus').val(term_focus.join('|'));	 
	  }
	  var search = get_search_condition();
	  
	  location.href = 'index.php?act=Archive/search/'+encodeURIComponent(Base64M.encode(JSON.stringify(search)));
	});
	
	
	
	// 調閱單查詢
	$('.get_apply_records').click(function(){
	  var apply_code = $(this).attr('no');
      location.href = 'index.php?act=Archive/myapply/'+apply_code; 	 	
	});
	
	
	// 偵測是否出現重新設定
	var search_keyin;
	$('#search_input').bind( "mouseover focus keyup change", function(event) {
	  if($(this).val()){
		$('#act_reset_search').show() 
	  }else{
		$('#act_reset_search').hide()   
	  }	
	  
	  if($(this).val() && (search_keyin == $(this).val()) && event.keyCode==13){
		$('#search_submit').trigger('click');  
	  }else{
		search_keyin = $(this).val();  
	  }
	});
	
	/*
	$('#search_input').bind('focusout',function(event) {
	  $('#act_reset_search').hide();	
	});
	*/
	
	// 重新設定檢索條件
	$('#act_reset_search').click(function(){
	  $('.additional_search').each(function(){
		$(this).children('.term').val('');
		$(this).children('.attr').val('+');
		$(this).children('.field').val('_all');
		
	  });
      $('#reset_format_sel').trigger('click');	
      $('#reset_daterange_set').trigger('click');
	  $('#search_field').val('_all').trigger('change');
	  $('#search_input').val('').focus();
	  $('#reset_zongrange_set').trigger('click');	
	  $(this).hide();
	});
	
	
	
	// 跳頁
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
   	
	
	//- sortby
	$('#sortby').change(function(){
	  $('#search_submit').trigger('click');
	});
	
	
	//- paging
	$('#pageing').change(function(){
	  var new_paging='1-'+$(this).val();
	  var serch_set = location.search.split('/');	
      var last_attr = serch_set.pop();
	  if(last_attr.match(/\d+\-\d+/g)){
		serch_set.push(new_paging)  
	    location.search = serch_set.join('/');
	  }else{
		location.search+='/'+new_paging;
	  }
	});
	
	
	// 全選
	// select result all
	$('.result_selected_all').bind('click',function(){
	  var checkbox_state = $(this).prop("checked");
	  $('.result_selecter').prop("checked",checkbox_state);
	});
	
	
	// 匯出勾選
	$('#act_export_meta').click(function(){
	  
	  var export_type = $("input[name='user_select_target']:checked").val();
	  
	  
	  if(export_type=='page' ){
		if(!$('input.result_selecter:checked').length){
		  system_message_alert('','尚未選擇資料');  
	      return false;
		}  
		var metalist = $('input.result_selecter:checked').map(function(){return $(this).val();}).get();
	    window.open('index.php?act=Archive/export/page/'+encodeURIComponent(Base64.encode(JSON.stringify(metalist))));  
	  }else{
		
		if(!export_type){
		  system_message_alert('','查詢無結果');  
	      return false;  
	    }  
		window.open('index.php?act=Archive/export/result/'+encodeURIComponent(Base64.encode(export_type)));  
	  }
	  
	});
	
	
	// 線上閱覽功能啟動
	$('.online').click(function(){
	  var active_dom = $(this);	
	  var access_key = $(this).attr('acckey');
	  if(access_key.length != 32 ){
		system_message_alert('',"數位檔案讀取參數錯誤");  
	    return false;
	  }
	  
	  //-- 解決 click 後無法馬上open windows 造成 popout 被瀏覽器block的狀況
	  // reference : http://stackoverflow.com/questions/20822711/jquery-window-open-in-ajax-success-being-blocked
	  
	  newWindow = window.open("","_blank");
	     
	  $.ajax({
		  url: 'index.php',
		  type:'POST',
		  dataType:'json',
		  data: {act:'Display/initial/'+access_key},
		  beforeSend: 	function(){ system_loading();  },
		  error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		  success: 		function(response) {
			if(response.action){  
			  newWindow.location.href = 'index.php?act=Display/'+response.data.display+'/'+response.data.resouse;
			  //location.href='index.php?act=Display/'+response.data.display+'/'+response.data.resouse; // 只能在本地視窗開啟
			  //window.open('index.php?act=Display/'+response.data.display+'/'+response.data.resouse,'_blank'); // 非馬上開啟造成被block
			}else{
			  newWindow.close();
			  system_message_alert('',response.info);
			}
		  },
		  complete:		function(){   }
	  }).done(function() { system_loading();   });	
	  
	});
	
	
	
	// 圖表
	if($('#query_chart').length &&  $('#query_chart').data('chart')){
	  
	  //var response = JSON.parse($('#query_chart').data('chart'));
	  var response = $('#query_chart').data('chart');
	  
	  $('#query_chart').highcharts({
	    
		chart: {
	        renderTo: 'query_chart',
            spacingRight:20,
			alignTicks:false,
			backgroundColor:'rgba(255, 255, 255, 0.1)'
        },
        title: {
          text: ''
        },
        yAxis: {
	      min: 0,
		  lineWidth: 1,
	      gridLineDashStyle:'dash',
		  //minorGridLineDashStyle: 'dash',
          //minorTickInterval: 'auto',
		  tickInterval:response.tick,
          title: {
            text: '年分統計'
          }
        },
	    xAxis: {
	      lineWidth:1,
	      lineColor: '#888888',
	      showLastLabel:true,
	      categories:response.category,
          labels: {align:'center'},
	      tickInterval :10,
	      tickLength:5
	    },
		plotOptions: {
          series: {
            borderWidth: 0,
            borderColor: '#FFFFFF'
          },
		  area:{
			marker: {
			  enabled: false,
              symbol: 'circle',
			}  
			  
		  }
		  
	    },
	    legend: {
          enabled:true,             
  		  layout: 'vertical',
          align: 'right',
          verticalAlign: 'top',
          //x: 100,
          //y: 0,
          floating: true,
          shadow: false
        },
		series: [{  type: 'column',name: '總筆數', data:response.data_total },
		  {  type: 'area',name: '檔案', data:response.data_file ,color:"#e8383d",fillOpacity:0.5,lineWidth:0},
		  {  type: 'area',name: '會議', data:response.data_meet ,color:"#2cb4ad",fillOpacity:0.4,lineWidth:0}
		]
		
      });
	  
	  //$("text:contains('Highcharts.com')").css('opacity','0');	
	}
	
    
    //-- close member app
	$('.mbr_close').click(function(){
	  $('.viewed').removeClass('viewed');
	  $('.system_member_area').css('display','none');	
	});

    // member refer open 
	$('persona').click(function(){
	  
	  var member_name = $(this).text()
	   
	  $.ajax({
		  url: 'index.php',
		  type:'POST',
		  dataType:'json',
		  data: {act:'Archive/mbrapp/'+encodeURIComponent(member_name)},
		  beforeSend: 	function(){ system_loading();  },
		  error: 		function(xhr, ajaxOptions, thrownError) {  console.log( ajaxOptions+" / "+thrownError);},
		  success: 		function(response) {
			if(response.action){  
			  
			  $('#member_block').find('.mbr_title').html(response.data.meta['mbr_name'])
			  
			  if(typeof response.data.dobj.portrait != 'undefined'){
				switch(response.data.dobj.portrait.mode){
				  case 'base64'	: $('#mbr_photo').html("<img src='"+response.data.dobj.portrait.source+"' />"); break;
				  case 'link'	: $('#mbr_photo').html("<img src='"+response.data.dobj.portrait.source+"' />"); break;
				}  
			  }else{
				  
			  }
              
			  $('#mbr_name').html(response.data.meta['mbr_name']);
			  $('#mbr_year').html(response.data.meta['mbr_time']);
			  $('#mbr_staff').html(response.data.meta['mbr_staff']);
			  $('#mbr_experience').html(response.data.meta['mbr_offer']);
			  $('#mbr_history').html(response.data.meta['mbr_history']);
			  $('#mbr_reference').html(response.data.meta['mbr_refer']);
			  $('#member_block').find('.mbr_from').html(response.data.meta['_sourcefrom']) 
			  
			  if(typeof response.data.refer.statistics != 'undefined'){
    
				var mbr_chart_json = response.data.refer.statistics;
				
				chart = new Highcharts.Chart({
					chart: {
					  renderTo: 'mbr_statistic',
					  type: 'bar',
					  alignTicks:true,
					  spacingRight:100,
					  backgroundColor:'rgba(255, 255, 255, 0.1)'
					},
					title: {
					  text: ''
					},
					yAxis: {
					  min: 0,
					  max: mbr_chart_json.dtype_max,
					  lineWidth: 1,
					  gridLineDashStyle:'dash',
					  minorGridLineDashStyle: 'dash',
					  minorTickInterval: 'auto',
					  //tickInterval:response.tick,
					  title: {
						text: ''
					  }
					},
					
					xAxis: {
					  lineWidth:1,
					  lineColor: '#888888',
					  showLastLabel:true,
					  categories: ['提案', '質詢'],
					},
					plotOptions: {
					  series: {
						borderWidth: 0,
						borderColor: '#FFFFFF',
					  },
					  bar:{
						color:'#640125'
					  },
					  pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							visible:true,
							dataLabels: {
							  enabled: true,
							  distance: 13,
							  format: '<b>{point.name}</b>: {point.percentage:.1f}',
							  style: {
								color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
							  }
							}	
					  }
					},
					legend: {
					  enabled:false,             
					  layout: 'vertical',
					  backgroundColor: 'rgba(255,255,255,0)',
					  align: 'left',
					  verticalAlign: 'top',
					  x: 50,
					  y: 0,
					  floating: true,
					  shadow: true
					},
					series: [{
					  type: 'bar',
					  name: 'total',
					  data: mbr_chart_json.data_type
					},{
					  type: 'pie',
					  innerSize: '50%',
					  name: '資料比例',
					  data: mbr_chart_json.data_class,
					  center: [210, 0],
					  size: 40,
					  showInLegend: true,
					  dataLabels: {enabled: true}
					}]
				  });
			  }
			  
			  $('.system_member_area').show();	
			  
			}else{
			  system_message_alert('',response.info);
			}
		  },
		  complete:		function(){   }
	  }).done(function() { system_loading();   });	
	  
	});





	
	//-- key control function  for relic  
	$(document).keyup(function(event){
	  switch(event.keyCode){
	    case 27: //esc
		  if($('.system_relic_viewer').is(':visible')){
		    $('#act_close_relic_viewer').trigger('click');
		  }  
		  break;
		  
		case 37: // <-
		  if($('.system_relic_viewer').is(':visible')){
		    $('#toprev').trigger('click');
		  }  
		  break;

        case 39: // ->
		  if($('.system_relic_viewer').is(':visible')){
		    $('#tonext').trigger('click');
		  }  
		  break;		  
		  
	  }
	});
	
  
  });
  
  
  
  
  
  
  //***** ----- Search Field Check  ------ ******
  //測試是否有搜尋資料 以及防止多重送出
  function UserSearchSubmit(){
    $('#search_submit').trigger('click');
	return false;
  } 
  
  