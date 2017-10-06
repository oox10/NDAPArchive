/* [ Admin Record Function Set ] */
	
  $(window).on('load',function () { 
	
	/* [ System Logs Function Set ] */
	
	//--date picker
	if($('.record_filter').length){
	  $(".record_filter").datepicker({
		dateFormat: 'yy-mm-dd'
	    //defaultDate: new Date(1985, 00, 01)
	  });
	}
	
	//-- mode initial
	$('li.mode_switch').click(function(){  	
	  location.href = 'index.php?act=Record/'+$(this).data('mode');  	
	});
	
	
	//-- record type mark // 紀錄類型按鈕標示
	$('.record_selecter').each(function(){
	  if( $('#'+$(this).attr('id')+'_block').is(':visible') ){
		$(this).addClass('active');  
	  }else{
		$(this).removeClass('active');    
	  }
	});
	
	
	// 查詢日期範圍
	$('#search_by_date').click(function(){
	  var act = $('.func_activate.inthis').attr('id')
	  location.href = 'index.php?act='+act+'/'+$('.mode_switch.atthis').data('mode')+'/'+$('#date_start').val()+'/'+$('#date_end').val();
	});
	
	
    // 匯出搜尋logs
	if($('#act_record_export_search_logs').length){
	  $('#act_record_export_search_logs').click(function(){
		window.open('index.php?act=Record/logssearch/'+$('#date_start').val()+'/'+$('#date_end').val());
	  });
	}
	
	// 匯出系統logs
	if($('#act_record_export_system_logs').length){
	  $('#act_record_export_system_logs').click(function(){
		window.open('index.php?act=Record/logssystem/'+$('#date_start').val()+'/'+$('#date_end').val());
	  });
	}
	
	
	
	// 搜尋統計圖表
	if($('#client_search_chart').length &&  $('#client_search_chart').data('chart')){
	  
	  var response   = $('#client_search_chart').data('chart');
	  var rangetags  = $('#client_chart_config').data('rangetags');
	  var rangeposition  = $('#client_chart_config').data('rangeposition');
	  
	  $('#client_search_chart').highcharts({
	    
		chart: {
	        renderTo: 'client_search_chart',
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
		  //tickInterval:response.tick,
          title: {
            text: '搜尋統計'
          }
        },
	    xAxis: {
	      lineWidth:1,
	      lineColor: '#888888',
	      showLastLabel:true,
	      categories:rangetags,
          labels: {align:'center'},
	      tickInterval :null,
	      tickLength:10,
	      tickPositions:rangeposition
		
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
		series: [{  type: 'column',name: '總搜尋', data:response.totalchart },
		  {  type: 'area',name: '會員搜尋', data:response.memberchart ,color:"#e8383d",fillOpacity:0.5,lineWidth:0},
		  {  type: 'area',name: '訪客搜尋', data:response.guestschart ,color:"#2cb4ad",fillOpacity:0.4,lineWidth:0}
		]
		
      });
	}
	
	// 搜尋統計圖表
	if($('#client_search_member_major').length &&  $('#client_search_member_major').data('chart')){
	  
	  var response   = $('#client_search_member_major').data('chart');
	  
	  $('#client_search_member_major').highcharts({
	    
		chart: {
          renderTo: 'client_search_member_major',
		  plotBackgroundColor: null,
          plotBorderWidth: null,
          plotShadow: false,
          type: 'pie'
		},
		title: {
			text: '會員主修統計'
		},
		tooltip: {
			pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: true,
					format: '<b>{point.name}</b>: {point.percentage:.1f} %',
					style: {
						color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
					}
				}
			}
		},
		series: [{
			name: '比例',
			//center: [50, 0],
			colorByPoint: true,
			//showInLegend: true,
			data: response 
		}]
		
      });
	}
	
	// 搜尋統計圖表
	if($('#client_search_member_staff').length &&  $('#client_search_member_staff').data('chart')){
	  
	  var response   = $('#client_search_member_staff').data('chart');
	  
	  $('#client_search_member_staff').highcharts({
	    
		chart: {
          renderTo: 'client_search_member_staff',
		  plotBackgroundColor: null,
          plotBorderWidth: null,
          plotShadow: false,
          type: 'pie'
		},
		title: {
			text: '會員職業統計'
		},
		tooltip: {
			pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: true,
					format: '<b>{point.name}</b>: {point.percentage:.1f} %',
					style: {
						color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
					}
				}
			}
		},
		series: [{
			name: '比例',
			colorByPoint: true,
			data: response 
		}]
		
      });
	}
	
	// 搜尋統計圖表
	if($('#client_search_member_education').length &&  $('#client_search_member_education').data('chart')){
	  
	  var response   = $('#client_search_member_education').data('chart');
	  
	  $('#client_search_member_education').highcharts({
	    
		chart: {
          renderTo: 'client_search_member_education',
		  plotBackgroundColor: null,
          plotBorderWidth: null,
          plotShadow: false,
          type: 'pie'
		},
		title: {
			text: '會員教育統計'
		},
		tooltip: {
			pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: true,
					format: '<b>{point.name}</b>: {point.percentage:.1f} %',
					style: {
						color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
					}
				}
			}
		},
		series: [{
			name: '比例',
			colorByPoint: true,
			data: response 
		}]
		
      });
	}
	
	
	// 全宗搜尋統計
	
	// 搜尋統計圖表
	if($('#client_zong_chart').length &&  $('#client_zong_chart').data('chart')){
	  
	  var response   = $('#client_zong_chart').data('chart');
	  console.log(response);
	  
	  $('#client_zong_chart').highcharts({
	    
		chart: {
          renderTo: 'client_zong_chart',
		  plotBackgroundColor: null,
          plotBorderWidth: null,
          plotShadow: false,
          type: 'bar'
		},
		title: {
          text: ''
		},
		xAxis: {
			categories: ['檔案', '公報', '議事錄', '議事影音', '議員傳記','活動照片'],
			title: {
				text: null
			}
		},
		yAxis: {
			min: 0,
			title: {
				text: '搜尋次數',
				align: 'high'
			},
			labels: {
				overflow: 'justify'
			}
		},
		tooltip: {
			valueSuffix: ' 次'
		},
		plotOptions: {
			bar: {
				dataLabels: {
					enabled: true
				}
			}
		},
		legend: {
			layout: 'vertical',
			align: 'right',
			verticalAlign: 'top',
			x: -40,
			y: 80,
			floating: true,
			borderWidth: 1,
			backgroundColor: ((Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'),
			shadow: true
		},
		credits: {
			enabled: false
		},
		series: [{
		  'name':"紀錄範圍",
          'data':response		  
		}]
		
      });
	}
	
	
	// 存取統計圖表
	if($('#client_access_chart').length &&  $('#client_access_chart').data('chart')){
	  
	  var response   = $('#client_access_chart').data('chart');
	  var rangetags  = $('#client_chart_config').data('rangetags');
	  var rangeposition  = $('#client_chart_config').data('rangeposition');
	  
	  $('#client_access_chart').highcharts({
	    
		chart: {
	        renderTo: 'client_access_chart',
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
		  //tickInterval:response.tick,
          title: {
            text: '讀取統計'
          }
        },
	    xAxis: {
	      lineWidth:1,
	      lineColor: '#888888',
	      showLastLabel:true,
	      categories:rangetags,
          labels: {align:'center'},
	      tickInterval :null,
	      tickLength:10,
	      tickPositions:rangeposition
		
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
		series: [{  type: 'column',name: '總存取', data:response.totalchart },
		  {  type: 'column',name: '檔案', data:response['檔案'] ,color:"#e8383d",fillOpacity:0.5,lineWidth:0},
		  {  type: 'column',name: '公報', data:response['公報'] ,fillOpacity:0.4,lineWidth:5},
		  {  type: 'column',name: '議事錄', data:response['議事錄'] ,fillOpacity:0.4,lineWidth:5},
		  {  type: 'column',name: '影音', data:response['影音'] ,fillOpacity:0.4,lineWidth:5},
		  {  type: 'column',name: '照片', data:response['照片'] ,fillOpacity:0.4,lineWidth:5}
		  
		  
		  
		]
		
      });
	}
	
	
	
	
	
	
	
    
	//-- admin record active data print //列印函數
	(function() {
    var beforePrint = function() {
		//$('.page_print_container').empty();
		//$('.main_content:visible').clone().appendTo('.page_print_container');
		
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
	
	
	//-- 帳號篩選 
	$('#account_filter').change(function(){
	  if($(this).val()){
		$(".act_filter").hide();
		$(".act_filter[no='"+$(this).val()+"']").show();
	  }else{
		$(".act_filter").show();  
	  }	
	});
	
	//-- upload logs filter
	if($('#act_upload_filter').length){
	  $('#act_upload_filter').click(function(){
		// get date range
		var filter = {};
		filter.dateStart = $('#filter_start').val();
		filter.dateEnd   = $('#filter_end').val();
		var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(filter)));
		location.href = "index.php?act=Record/upload/"+passer_data;
	  });	
	}
	
	
	
	//-- filter doclogs
	if($('#act_export_filter').length){
		$('#act_export_filter').click(function(){
		  var filter = {};
		  filter.dateStart = $('#filter_start').val();
		  filter.dateEnd   = $('#filter_end').val();
		  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(filter)));
		  location.href = "index.php?act=Record/export/"+passer_data;	
		});
	}
    
	
    //-- filter doclogs
	$('#act_account_filter').click(function(){
	  
	  var data_no    = $('#doclog_records').attr('no');
	   // active ajax
	  if( ! data_no ){
	    system_message_alert('',"資料錯誤");
		return false;
	  }
	  
	  var filter = {};
	  filter.dateStart = $('#actlog_start').val();
	  filter.dateEnd   = $('#actlog_end').val();
      var account      = $('#actlog_account').val();
	  var passer_data  = encodeURIComponent(Base64.encode(JSON.stringify(filter)));
	  location.href = "index.php?act=Record/account/"+account+"/"+passer_data;	
    });
    
	
	
	
  });	