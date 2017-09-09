<?php
  class Result_IMAGE extends View{
    
	private $WordSource = _SYSTEM_IMAGE_PATH.'msjh.ttf';
	
	
	public function fetch($ImageData = array()){
	  ini_set('memory_limit', '500M');
	  
	  $image_data = $ImageData;
	  
	  $img_temp = _SYSTEM_DIGITAL_FILE_TEMP.md5($image_data['address'].time()).'.jpg';
	  file_put_contents($img_temp,file_get_contents($image_data['address']));
	  
	  // 加入浮水印
	  list($img_w,$img_h) = getimagesize($img_temp);
      list($wtr_w,$wtr_h) = array(_SYSTEM_VALUE_WM_WIDTH,_SYSTEM_VALUE_WM_HEIGHT);
	
	  $image_p = imagecreatefromjpeg($img_temp);
	  $image_w = imagecreatefrompng(_SYSTEM_IMAGE_PATH.'wartermark.png');
	  
	  if(count($image_data['warterm'][2])){
		
        foreach($image_data['warterm'][2] as $conf){
		  switch($conf['shap']){
			case 'rect':  
              
			  $mask_w = $conf['width'];			  
              $mask_h = $conf['height'];
			  $wm_board = imagecreatetruecolor($mask_w, $mask_h);          //建立浮水印圖框  但預設為黑色
			  $wm_obj   = imagecolorallocatealpha($wm_board , 0 , 0 , 0 , 0); // 建立一透明單元
			  imagealphablending($wm_board , false);  // 關閉混合模式，以便透明色能覆蓋原框架
			  imagefill($wm_board , 0 , 0 , $wm_obj);   // 填充
			  imagesavealpha($wm_board , true);  // 設置保留透明通道訊息
		      imagecopy($image_p, $wm_board, $conf['left'], $conf['top'],0,0, $mask_w, $mask_h);
	          imagedestroy($wm_board);  
			  break;  
			  
			default: break;  
		  }	
		}
	  }
	  
	  
	  
	  
	  
	  // 縮圖
	  $max_width = 1400; 
	  if( $img_w > $max_width ){   //&& ($_SESSION['User_Name']=='admin' || $_SESSION['User_Name']=='ntudigital')
		$new_width = intval($img_w / 2);
		$new_height= intval($new_width * $img_h / $img_w );
		$image_new = imagecreatetruecolor($new_width, $new_height);
	    imagecopyresampled($image_new, $image_p, 0, 0, 0, 0, $new_width, $new_height, $img_w, $img_h);
	    $image_p=$image_new;
	    $img_w = $new_width;
	    $img_h = $new_height;  
	  }
	  
	  
	  if($img_w >= $img_h){  // 橫幅影像
	    
		$wtr_new_w = intval($img_w/4);
		$wtr_new_h = intval($wtr_new_w * $wtr_h / $wtr_w);
		
		$wtr_loc_w = ($img_w - $wtr_new_w)-10;
		$wtr_loc_h = ($img_h - $wtr_new_h)-10;
		
		$wm_board = imagecreatetruecolor($wtr_new_w, $wtr_new_h);          //建立浮水印圖框  但預設為黑色
		$wm_obj   = imagecolorallocatealpha($wm_board , 0 , 0 , 0 , 127); // 建立一透明單元
		imagealphablending($wm_board , false);  // 關閉混合模式，以便透明色能覆蓋原框架
		imagefill($wm_board , 0 , 0 , $wm_obj);   // 填充
		imagesavealpha($wm_board , true);  // 設置保留透明通道訊息
		imagecopyresized($wm_board, $image_w, 0, 0, 0, 0, $wtr_new_w, $wtr_new_h, $wtr_w, $wtr_h); // 浮水印複製並縮小到新圖框中
        
		
		
		
	  }else{  // 直幅影像
	    
		$wtr_new_h = intval($img_h/10);
		$wtr_new_w = intval($wtr_new_h * $wtr_w / $wtr_h);
		
		$wtr_loc_w = ($img_w - $wtr_new_w)-10;
		$wtr_loc_h = ($img_h - $wtr_new_h)-10;
		
		$wm_board = imagecreatetruecolor($wtr_new_w, $wtr_new_h);          //建立浮水印圖框  但預設為黑色
		$wm_obj   = imagecolorallocatealpha($wm_board , 0 , 0 , 0 , 127); // 建立一透明單元
		imagealphablending($wm_board , false);  // 關閉混合模式，以便透明色能覆蓋原框架
		imagefill($wm_board , 0 , 0 , $wm_obj);   // 填充
		imagesavealpha($wm_board , true);  // 設置保留透明通道訊息
		imagecopyresized($wm_board, $image_w, 0, 0, 0, 0, $wtr_new_w, $wtr_new_h, $wtr_w, $wtr_h); // 浮水印複製並縮小到新圖框中
	  }
	  
	  imagecopy($image_p, $wm_board, $wtr_loc_w, $wtr_loc_h,0,0, $wtr_new_w, $wtr_new_h);
	  imagedestroy($wm_board);  
	  
	  
	  // 左上角登入標注
	  $textcolor = imagecolorallocatealpha($image_p,50,50,50,110);
	  imagestring($image_p, 5, ($img_w/50),($img_h/50), $image_data['warterm'][0].' '.date('Y-m-d H:i'), $textcolor); 	  
	  
	  if(!preg_match('/'.substr(_SYSTEM_ERROR_IMAGE_NAME,0,25).'/',$image_data['address'])){ // 無影像則不需要附加浮水印

	    // 使用者帳號標注
	    $text_string = $image_data['warterm'][0];
	    $text_size   = 30;
	    do{
	      $box = imagettfbbox($text_size,0,$this->WordSource,$text_string);
	      $text1_w = abs($box[0] - $box[2]);//w
	      $text1_h = abs($box[1] - $box[7]);//h
		  $text_size--;
		  if($text_size < 0) break;
	    }while($text1_w > $img_w);
	    imagettftext ( $image_p, $text_size, 0, (($img_w-$text1_w)/2), (($img_h-$text1_h)/2), $textcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string);
	  
	    $text_string = $image_data['warterm'][1]; //iconv('big5','utf-8',);
	    $text_size   = 30;
	    do{
	      $box = imagettfbbox($text_size,0,$this->WordSource,$text_string);
	      $text2_w = abs($box[0] - $box[2]);//w
	      $text2_h = abs($box[1] - $box[7]);//h
		  $text_size--;
		  if($text_size < 0) break;
	    }while($text2_w > $img_w);
	    imagettftext ( $image_p, $text_size, 0, (($img_w-$text2_w)/2), (($img_h-$text2_h)/2 + $text1_h + 10), $textcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string);
	    
		
		
		$alertcolor = imagecolorallocatealpha($image_p,255,50,50,80);
		if($image_data['warterm'][3]){
		  $text_string = $image_data['warterm'][3]; //iconv('big5','utf-8',);
	      $text_size   = 30;
	      do{
	        $box = imagettfbbox($text_size,0,$this->WordSource,$text_string);
	        $text2_w = abs($box[0] - $box[2]);//w
	        $text2_h = abs($box[1] - $box[7]);//h
		    $text_size--;
		    if($text_size < 0) break;
	      }while($text2_w > $img_w);
		  imagettftext ( $image_p, $text_size, 0, (($img_w-$text2_w)/2), (($img_h-$text2_h)/2 + $text1_h + 60), $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string);
	    }
		
	  }
	  
	  
	  
	  
	  
	  
	  
	  
	  
	  
	  /* 用 pack 修改DPI   PHP7.2後可使用 imageresolution()
	  imagejpeg($image_p,$img_temp,75);
	  $image_print = file_get_contents($img_temp);
	  print substr_replace($image_print, pack("cnn", 1, 300, 300), 13, 5);
	  */
	  
	  $check_mode = 'animal';//'shap'; // 'animal'
	  
	  
	  if(isset($_SESSION['AHAS']['CLIENT']['ACCESS_LOCK']) && $_SESSION['AHAS']['CLIENT']['ACCESS_LOCK'] ){
		  
		if($check_mode=='shap'){
			
		  $check_target_eng = ['Square','Round','Star'];
		  $check_target_cht = ['方形','圓形','星形'];
		  $check_index      = rand(0,2);
		  
		  $check_pos_x  = rand(10,90);
		  $check_pos_y  = rand(10,90);
		  $check_loc_x  = intval($img_w*$check_pos_x/100);
		  $check_loc_y  = intval($img_h*$check_pos_y/100);
		  $recaptuer  = strtoupper(System_Helper::khashCRC32(time().'＃＠'.rand(0,99)));
		  
		  $_SESSION['AHAS']['CLIENT']['ACCESS_TEST']=[
			'point_x' => $check_pos_x,
			'point_y' => $check_pos_y,
			'recapture' => $recaptuer
		  ];
		  
		  $chaos_pos_x  = rand(10,90);
		  $chaos_pos_y  = rand(10,90);
		  $chaos_loc_x  = intval($img_w*$chaos_pos_x/100);
		  $chaos_loc_y  = intval($img_h*$chaos_pos_y/100);
		  
		  
		  $checker = imagecreatetruecolor(30, 30); 
		  $check_color = imagecolorallocatealpha($checker , 0 , 0 , 100 , 60); // 建立一透明單元
		  $check_color = imagecolorallocatealpha($checker ,255,50,50,20); // 建立一透明單元
		  $check_color = imagecolorallocatealpha($checker , 0 , 100 , 0 , 60); // 建立一透明單元
		  
		  $points = array( 15,0,11,6,0,6,5,15,0,24,11,24,15,30,18,24,30,24,24,15,30,6,18,6);
          $star  = imagecreatetruecolor(30, 30);
		  $star_color   = imagecolorallocatealpha($star , 0 , 0 , 0 , 127); // 建立一透明單元
		  imagealphablending($star , false);  // 關閉混合模式，以便透明色能覆蓋原框架
		  imagefill($star , 0 , 0 , $star_color);   // 填充
		  imagesavealpha($star , true);  // 設置保留透明通道訊息
		  imagefilledpolygon($star, $points, 12, $check_color); // draw a polygon by point list
		 
		  switch($check_target_eng[$check_index]){
			case 'Square':
              
			  imagefilledrectangle($image_p, $check_loc_x, $check_loc_y, $check_loc_x+30, $check_loc_y+30, $check_color); // 綠方塊
			  for($i=1;$i<5;$i++){
			    imagefilledellipse($image_p, intval($img_w*rand(10,90)/100), intval($img_h*rand(10,90)/100) , 30, 30, $check_color ); // 綠色圓點
			    imagecopy($image_p, $star, intval($img_w*rand(10,90)/100), intval($img_h*rand(10,90)/100) ,0,0, 30, 30); // 星形
			  }
			  
			  break;
			  
			case 'Round':
              imagefilledellipse($image_p, $check_loc_x, $check_loc_y, 30, 30, $check_color ); // 綠色圓點
		      
			  for($i=1;$i<5;$i++){
			    $chaos_pos_x  = rand(10,90);
				$chaos_pos_y  = rand(10,90);
				$chaos_loc_x  = intval($img_w*$chaos_pos_x/100);
				$chaos_loc_y  = intval($img_h*$chaos_pos_y/100);
				imagefilledrectangle($image_p, $chaos_loc_x, $chaos_loc_y, ($chaos_loc_x+30), ($chaos_loc_y+30), $check_color); // 綠方塊
			    imagecopy($image_p, $star, intval($img_w*rand(10,90)/100), intval($img_h*rand(10,90)/100) ,0,0, 30, 30); // 星形
			  }
			  
			  break;			  
		  
		    case 'Star':
			  imagecopy($image_p, $star, $check_loc_x, $check_loc_y ,0,0, 30, 30); // 星形
			  for($i=1;$i<5;$i++){
			    $chaos_pos_x  = rand(10,90);
			    $chaos_pos_y  = rand(10,90);
			    $chaos_loc_x  = intval($img_w*$chaos_pos_x/100);
			    $chaos_loc_y  = intval($img_h*$chaos_pos_y/100);
				imagefilledrectangle($image_p, $chaos_loc_x, $chaos_loc_y, ($chaos_loc_x+30), ($chaos_loc_y+30), $check_color); // 綠方塊
				imagefilledellipse($image_p, intval($img_w*rand(10,90)/100), intval($img_h*rand(10,90)/100), 30, 30, $check_color ); // 綠色圓點
			  }
			  
			  
			  break;
		  }
		  
	    }else{
		  
		  $check_target_eng = ['Fox','Pig','Cow','Bear','Hipo'];
		  $check_target_cht = ['狐','豬','牛','熊','河馬'];
		  $check_index      = rand(0,4);
		  
		  
		  $check_mark = imagecreatefrompng(_SYSTEM_IMAGE_PATH.'check_mark-'.strtolower($check_target_eng[$check_index]).'.png');
		  
		  
		  $check_pos_x  = rand(10,90);
		  $check_pos_y  = rand(10,90);
		  $check_loc_x  = intval($img_w*$check_pos_x/100)-intval(imagesx($check_mark)/2);
		  $check_loc_y  = intval($img_h*$check_pos_y/100)-intval(imagesy($check_mark)/2);
		  $recaptuer  = strtoupper(System_Helper::khashCRC32(time().'＃＠'.rand(0,99)));
		  
		  $_SESSION['AHAS']['CLIENT']['ACCESS_TEST']=[
			'point_x' => $check_pos_x,
			'point_y' => $check_pos_y,
			'recapture' => $recaptuer
		  ];
		  
		  //正確
		  imagecopy($image_p, $check_mark, $check_loc_x, $check_loc_y, 0, 0, imagesx($check_mark), imagesy($check_mark));
		  
		  // 弄亂
		  for($i=0;$i<10;$i++){
			if($i%5===$check_index){
			  continue;
			}
			$chaos_mark = imagecreatefrompng(_SYSTEM_IMAGE_PATH.'check_mark-'.strtolower($check_target_eng[$i%5]).'.png');
			imagecopy($image_p, $chaos_mark, intval($img_h*rand(10,90)/100), intval($img_h*rand(10,90)/100), 0, 0, imagesx($chaos_mark), imagesy($chaos_mark));
		  }
		  
		}
		
		// 網址驗證碼
		$alertcolor = imagecolorallocatealpha($image_p,255,50,50,20); //
		if($recaptuer){
		  $text_string1 = '訪客閱覽影像已鎖定';
		  $text_string2 = "請登入帳號或點擊「".$check_target_cht[$check_index]."」形圖案以解除圖像鎖定";
		  $text_string3 = "Please login or click on the ''".$check_target_eng[$check_index]."'' icon to remove the lock picture.";
			
		  //$text_string3 = '@'.$recaptuer.'　後按解鎖按鈕';
		  $text_size   = 40;
		  $text_angel  = rand(-15,15);
			
		  do{
			  $box = imagettfbbox($text_size,0,$this->WordSource,$text_string3);
			  $text3_w = abs($box[0] - $box[2]);//w
			  $text3_h = abs($box[1] - $box[7]);//h
			  $text_size--;
			  if($text_size < 0) break;
		  }while($text3_w > $img_w);
			
		  $box01 = imagettfbbox($text_size,0,$this->WordSource,$text_string1);
		  $box02 = imagettfbbox($text_size,0,$this->WordSource,$text_string2);
			
		  if($text_angel < 0){
			  imagettftext( $image_p, $text_size, $text_angel, (($img_w-abs($box01[0] - $box01[2]))/2)+10, ($img_h/3), $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string1);
			  imagettftext( $image_p, $text_size, $text_angel, (($img_w-abs($box02[0] - $box02[2]))/2)+10, ($img_h/3)+(20+$text_size)*1, $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string2);
			  imagettftext( $image_p, $text_size, $text_angel, (($img_w-$text3_w)/2)+10, ($img_h/3)+(70+$text_size)*1, $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string3);
		  }else{
			  imagettftext( $image_p, $text_size, $text_angel, (($img_w-abs($box01[0] - $box01[2]))/2)+10, ($img_h/2), $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string1);
			  imagettftext( $image_p, $text_size, $text_angel, (($img_w-abs($box02[0] - $box02[2]))/2)+10, ($img_h/2)-(20+$text_size)*1, $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string2);
			  imagettftext( $image_p, $text_size, $text_angel, (($img_w-$text3_w)/2)+10, ($img_h/2)-(70+$text_size)*1, $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string3);
		  }
			
		  //imagettftext( $image_p, intval($text_size/2), 0 , $check_loc_x, $check_loc_y, imagecolorallocatealpha($image_p,254,254,254,0) , _SYSTEM_IMAGE_PATH.'msjh.ttf', '1');
		  //imagettftext( $image_p, $text_size, 0, (($img_w-$text3_w)/2), 130+(10+$text_size)*2, $alertcolor , _SYSTEM_IMAGE_PATH.'msjh.ttf', $text_string3);
		}
		
		
		//  蓋版浮水印圖
		$wm_mark = imagecreatetruecolor(200, 100);          //建立浮水印圖框  但預設為黑色
		$wm_fill = imagecolorallocatealpha($wm_mark , 0 , 0 , 0 , 127 ); // 建立一透明單元
		imagealphablending($wm_mark , false);  // 關閉混合模式，以便透明色能覆蓋原框架
		imagefill($wm_mark , 0 , 0 , $wm_fill);   // 填充
		imagecopyresized($wm_mark, $image_w, 0, 0, 0, 0, 200, 100, $wtr_w, $wtr_h); // 浮水印複製並縮小到新圖框中
		imagefilter($wm_mark, IMG_FILTER_COLORIZE, 0,0,0,50);  //50
		imagesettile($image_p, $wm_mark);
		imagefilledrectangle($image_p, 0, 0, $img_w, $img_h, IMG_COLOR_TILED);
		 
	  }
	  
	  //file_put_contents('check.logs','x:'.$check_pos_x.',y:'.$check_pos_y.',s:'.$recaptuer);
	  
	  
	  imagejpeg($image_p,NULL,75);
	  imagedestroy($image_p);
	  unlink($img_temp);
	}
	
	public function render(){
	  $image_data = $this->vars['server']['data'];
	  $file_name  = $this->vars['server']['data']['filename'];
	  
	  ob_clean();
	  header('Content-Type: image/jpeg');
	  header('Content-Disposition: attachment; filename="'.$file_name.'"');	
	  $this->fetch($image_data);
	}
  }



?>