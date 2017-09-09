

/***** ----- 系統讀愈載關函數 -----*****/
var ImgOrlW ,ImgOrlH ;
 
$(window).on('load',function () {
 
  // 顯示圖片區  讀取圖片
  if($('#image_display').length > 0 && location.search.length){
    Built_Image_Area('img');
  }
  
  // 影片區  
  if($('#video_display').length > 0 && location.search.length){
    Built_Image_Area('mp4');
  }
  
  // 顯示列印區影像，跳轉
  if($('#act_image_print').length){
	$('#act_image_print').click(function(){
      $('#ImageObject').error(function() {
        system_message_alert('影像錯誤');
        return false;
	  });  
	  var image_address = $('#ImageObject').attr('src');
	  window.open('index.php?act=Display/ciprint/'+Base64M.encode(image_address));
	});
  }

  
  if($('#opt_size_slider').length > 0){
   
    var ImgBoxPosT,ImgBoxPosL,ImgBoxW,ImgBoxH; 
   
    $( "#opt_size_slider" ).slider({
		value:100,
		min: 100,
		max: 300,
		step: 10,
		create: function(event, ui) {
		   ImgBoxPosT = $( ".obj_view" ).offset().top;
           ImgBoxPosL = $( ".obj_view" ).offset().left;
	       ImgBoxW    = $( ".obj_view" ).width();
	       ImgBoxH    = $( ".obj_view" ).height();
		},
		slide: function( event, ui ) {
		  $( ".ImageObject" ).width(  parseInt(ImgOrlW * ui.value /100)).height( parseInt(ImgOrlH * ui.value /100));
		  if((ui.value/100).toString().length == 1){
			$( ".opt_size_info" ).html( "x" + ui.value/100 +".0" );
		  }else{
			$( ".opt_size_info" ).html( "x" + ui.value/100 );
		  }
		  
		  //修正如果圖片 跑出影像框  則定位於 框左上角
	      var ImgPosNowT = $( ".ImageObject" ).offset().top;
	      var ImgPosNowL = $( ".ImageObject" ).offset().left;
          var ImgNewW    = $( ".ImageObject" ).width()  
		  var ImgNewH    = $( ".ImageObject" ).height()
		  if( (ImgBoxPosT-ImgPosNowT)>ImgNewH  ||  (ImgBoxPosL-ImgPosNowL)>ImgNewW || ui.value==100){
		    $( ".ImageObject" ).offset( {top:ImgBoxPosT,left:ImgBoxPosL} ); 
	      } 
		},
		stop: function( event, ui ) {
		  //傳遞圖片大小　用來固定比率
		  //alert($(".ImageObject" ).offset().top+':'+$(".ImageObject" ).offset().left);  
		   
		  //$(".system_footer_area").text("BOX:"+ImgBoxPosT+":"+ImgBoxPosL+",IMG:"+ImgPosNowT+":"+ImgPosNowL);  
		}
		
	});
  }
  
  
  $('#image_display').mousewheel(function(event, delta){
	  /*
	   distend - :往下捲  右移
 	   distend + :往上捲  左移
	  */
	  
	    var distend = (delta<0) ? 'dw' :'up';
		
		
		var ImgRate = $('#opt_size_slider').slider("option","value");
		
		ImgRate = (delta<0) ? ImgRate-10 : ImgRate+10;
		if(ImgRate>=300) ImgRate = 300;
		if(ImgRate<=100) ImgRate = 100;
		
		$( "#ImageObject" ).css({'width':parseInt(ImgOrlW * ImgRate / 100)+'px','height':parseInt(ImgOrlH * ImgRate / 100)+'px'});
		
		$('#opt_size_slider').slider({value:ImgRate});
		
		if((ImgRate/100).toString().length == 1){
		  $( ".opt_size_info" ).html( "x" + ImgRate/100 +".0" );
		}else{
		  $( ".opt_size_info" ).html( "x" + ImgRate/100 );
		}
		
		//修正如果圖片 跑出影像框  則定位於 框左上角
	    var ImgPosNowT = $( "#ImageObject" ).offset().top;
	    var ImgPosNowL = $( "#ImageObject" ).offset().left;
        var ImgNewW    = $( "#ImageObject" ).width()  
		var ImgNewH    = $( "#ImageObject" ).height()
		
		
		if( (ImgBoxPosT-ImgPosNowT)>ImgNewH  ||  (ImgBoxPosL-ImgPosNowL)>ImgNewW || ImgRate==100){
		  $( "#ImageObject" ).offset( {top:ImgBoxPosT,left:ImgBoxPosL+ImgNewW/2} ); 
	    } 
	  
   }); 
  
  // reference select
  if($('input#reference_string').length >0 ){
    $('input#reference_string').mouseenter(function(){
	  $(this).focus().select();
	});
  }
  
  // 投影顯示區  meta 卷軸
  if($('#Project_Mode_Meta').length){
	// 切換重新設定 scroll	
	var setting = {
      autoReinitialise: true,
      showArrows: false
    }; 
	
	// 設定 jScrollPane
	$('#Project_Mode_Meta').jScrollPane(setting);	 
  }

  
  // app 區卷軸
  if($('.app_body_block').length){
    var setting = {
      autoReinitialise: true,
      showArrows: false
    }; 
	$('.app_body_block').jScrollPane(setting);	 
  }
  
  //-- change video
  $(document).on('click','.media_phase',function(){
	var media_code = $(this).data('code');
	var source = document.getElementById(media_code);
	var video  = document.getElementById('myVideo');
	video.pause();	
	video.src = source.src
	video.load();  
    video.play(); 
  });
  
});

/*****------以上為 document load 後加載js ----- *******/
/******************************************************/
  
  
 
  
  
  /******* ----- 讀取影像資料 ----- *******/
  function Built_Image_Area(obj_type){
  
    obj_type = obj_type ? obj_type : 'img';
   
    var Link_Part = location.search.match(/act=Display\/(.*?)\/(\d+[a-zA-Z0-9\_\-=\+]{7,7})/);
       
    if((Link_Part[1] == 'image'  || Link_Part[1] == 'print' || Link_Part[1] == 'video') && Link_Part[2].length){
   
      var ObjectCode = Link_Part[2];
	  var PageCode   = location.hash.replace('#','')
  
	  $.ajax({
        url: 'index.php',
	    type:'POST',
	    dataType:'json',
	    data: {act:'Display/built/'+ObjectCode+'/'+PageCode},
        beforeSend:function(){ page_loading() },
	    error: function(xhr, ajaxOptions, thrownError) {
		  console.log(thrownError)
		},
		success: function(response) {
		  //console.log(response)
		  if(response.action){
		    
			
			
 		    var object_built = response.data;
			
			switch(obj_type){
		      case 'img':
				
				if(!parseInt(object_built.print_option)){
				  $('#act_image_print').hide();
				}
				
				$('#img_num').html(object_built.page_count);
				$('#image_access_count').html(object_built.image_access_count);
				//$('#ref_address').text(location.href);
				$('#image_display').empty().append("<img id='ImageObject' class='ImageObject' src='index.php?act=Display/loadimg/"+object_built.page_code_now+"' style='' galleryimg='no' drag='no' onContextMenu='return false' />").
					
					children('img').on('load',function(){
					  
					  var BlockW = $('#image_display').width();
					  var BlockH = $('#image_display').height();
					  
					  ImgOrlW = this.width;
					  ImgOrlH = this.height;
					  
					  
					  if($(".ImageObject" ).height() > $(".ImageObject" ).width()){
						$(".ImageObject" ).css({'width':'','height':BlockH+'px'});
					  }else{
						
						var default_rate = 1;
						var diff = 0;
						
						do{
						  var tmp_h = parseInt(ImgOrlH * BlockW * (default_rate-(0.01*diff)) / ImgOrlW);	
						  diff++;
						}while(tmp_h > BlockH);
						$(".ImageObject" ).css({'width':parseInt(BlockW*(default_rate-(0.01*diff)))+'px','height':''});
					    
					  }
					  
					  if($("#opt_size_slider").slider('value')>100){
					    $(".ImageObject" ).width(  parseInt(ImgOrlW * $("#opt_size_slider").slider('value') /100)).height( parseInt(ImgOrlH * $("#opt_size_slider").slider('value') /100)); 
					  }
					  
					  //$(".ImageObject" ).show();
					  $(".ImageObject" ).animate({opacity:'1'},300,function(){});
					  
					  
					  $('#btinfo_pageload').stop(true, true).css({opacity:0});
					  
					  
					}).draggable({
						stop: function(event, ui) {
						//alert($(".ImageObject" ).offset().top+':'+$(".ImageObject" ).offset().left);
						}
					}).click(function(event){
						var mouse = {};
						var offset = $(this).offset();
						
					    mouse.mx = event.pageX - offset.left;
						mouse.my = event.pageY - offset.top;
						mouse.px = parseInt(mouse.mx/$(this).width()*100);
						mouse.py = parseInt(mouse.my/$(this).height()*100);
						
						if(parseInt(object_built.page_access_lock)){
						  
						  $.ajax({
							url: 'index.php',
							type:'POST',
							dataType:'json',
							data: {act:'Display/unlock/'+mouse.px+'/'+mouse.py},
							beforeSend:function(){
							  page_loading()
							},
							error: function(xhr, ajaxOptions, thrownError) {
							  console.log(thrownError)
							},
						    success: function(response) {
							  if(!response.action){
								alert(response.info)  
							  }
							  $(location).one('load',Built_Image_Area()).triggerHandler('load');
							}
						  });
						
						}
						
					});
		        
				$('#img_jump').empty().unbind();
				for (page in object_built.page_list){
					var page_target = (object_built.page_list[page] == object_built.page_code_now) ? 'selected' :'' ;
					$('#img_jump').append("<option value='"+object_built.page_list[page]+"' "+page_target+" > P."+page+" </option>");
				}
		  
				$('#img_jump').one('change', function() {
					$(location).attr('hash','#'+$(this).val()).
					one('load',Built_Image_Area()).
					triggerHandler('load');
				});
		
				$('.img_botton').unbind();
				if(object_built.page_code_up != object_built.page_code_now){
					$('#img_up').one('click', function() {
					$(location).attr('hash','#'+object_built.page_code_up).
						one('load',Built_Image_Area()).
						triggerHandler('load');
					});
		  
					$('#onimg_up').one('click', function() {
					$(location).attr('hash','#'+object_built.page_code_up).
						one('load',Built_Image_Area()).
						triggerHandler('load');
					});
				}
		
				if(object_built.page_code_dw != object_built.page_code_now){
					$('#img_dw').one('click', function() {
					$(location).attr('hash','#'+object_built.page_code_dw).
						one('load',Built_Image_Area()).
						triggerHandler('load');
					//window.location.reload(true);
					});
		  
					$('#onimg_dw').one('click', function() {
					$(location).attr('hash','#'+object_built.page_code_dw).
						one('load',Built_Image_Area()).
						triggerHandler('load');
					//window.location.reload(true);
					});
				}
				
				
				$(location).attr('hash','#'+object_built.page_code_now)		
				
				break; 
				
		      case 'mp4':
			    
				$('.obj_function').hide();
				var video = $("<video/>").addClass('mejs-player')
						   .css({'width':'600px','height':'400px'})
						   .attr({'id':'myVideo', 'preload':'none'})
						   .prop('controls',true)
						   .prop('autoplay',false)
						   .data({'src':"",'point':'0'});
				
				if(object_built.media.length){
				  $.each(object_built.media,function(order,dobj){
					video.append("<source type='video/mp4' src='index.php?act=Display/loadmp4/"+dobj.code+"#t="+dobj.stime+","+dobj.etime+"' id='v-"+dobj.code+"' />");  
				    var thumb = $("<div/>").addClass('media_phase').attr('data-code','v-'+dobj.code);
				    thumb.append("<img src='screen.php?src="+dobj.thumb+"'  />")
				    thumb.append('<h2> <i class="fa fa-video-camera" aria-hidden="true"></i>'+dobj.file+"</h2>")
				    thumb.append('<span> <i class="fa fa-clock-o" aria-hidden="true"></i> '+dobj.stime+' - '+dobj.etime+"</span>")
				    thumb.appendTo('#mediaqueue'); 
				  });	
				
				}
				
				$('#video_display').empty().append(video);
					
			    break;
		  
		    }
		  }else{
		    $('.obj_display').empty().append("<img id='ImageObject' class='ImageObject' src='application.php?act=object_load&imgcode="+object_built.result+"' style='' galleryimg='no' drag='no' onContextMenu='return false' />").
					children('img').load(function(){
					  
					  var BlockW = $('#image_display').width();
					  var BlockH = $('#image_display').height();
					  
					  ImgOrlW = this.width;
					  ImgOrlH = this.height;
					  
					  
					  if($(".ImageObject" ).height() > $(".ImageObject" ).width()){
						$(".ImageObject" ).css({'width':'','height':BlockH+'px'});
					  }else{
						
						var default_rate = 1;
						var diff = 0;
						
						do{
						  var tmp_h = parseInt(ImgOrlH * BlockW * (default_rate-(0.1*diff)) / ImgOrlW);	
						  diff++;
						}while(tmp_h > BlockH);
						$(".ImageObject" ).css({'width':parseInt(BlockW*(default_rate-(0.1*diff)))+'px','height':''});
					    
					  }
					  
					  //$(".ImageObject" ).show();
					  $(".ImageObject" ).animate({opacity:'1'},300,function(){});
					  $('#btinfo_pageload').stop(true, true).css({opacity:0});
					  
					});
		  }  
	    
		
		} // end of ajax.success
      }).done(function(){
		page_loading()  
	  }); // end of ajax
    }
  }
  
  
  // image page loading
  function page_loading(){
    if($('#main_page_loading').is(':visible')){
	  $('#main_page_loading').css('display','none');  
    }else{
	  $('#main_page_loading').css('display','flex');
    }	
  }
  
  
  
  
  //-- sample open new windows and print
  /*
  function showimage() {
	var img = new Image();
	img.src = $('.ImageObject').attr('src');
	//if (typeof img== 'object') img = img.src;
    window.win = open(img.src);
    setTimeout('win.document.execCommand("Print")', 500);
  }
  
  //-- sample regist print function
  (function() {
    var beforePrint = function() {
	  //console.log('Functionality to run before printing.');
    };
    var afterPrint = function() {
      //console.log('Functionality to run after printing');
    };

    if (window.matchMedia) {
        var mediaQueryList = window.matchMedia('print');
        mediaQueryList.addListener(function(mql) {
			if ( mql.matches ) {
                beforePrint();
            } else {
                afterPrint();
            }
        });
    }
    window.onbeforeprint = beforePrint;
    window.onafterprint = afterPrint;
  }());
  */	
   
  
  
  
  
