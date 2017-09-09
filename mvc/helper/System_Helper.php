<?php
  
  /*
  *   [RCDH10 System Module] - Helper Object 
  *   System Use Tool Function 
  *  
  *   2016-01-01 ed.  
  */
  
  class System_Helper{
    /*****************************************************
        取得使用者ip
	    來源：http://www.jaceju.net/blog/archives/1913/
    *****************************************************/
    public static function get_client_ip(){
        foreach (array(
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR') as $key) {
        if (array_key_exists($key, $_SERVER)) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if ((bool) filter_var($ip, FILTER_VALIDATE_IP,
				                FILTER_FLAG_IPV4 |
                                FILTER_FLAG_NO_RES_RANGE)) {  //FILTER_FLAG_NO_PRIV_RANGE |
                    return $ip;
                }
            }
        }
      }
      return '0.0.0.0';
    }
	
	
	/******************************************
	  取得取得client介面
	  
	    參數   
		  1. $agent  Null
		
		回傳
		  Array (
            [browser] => firefox
            [version] => 3.5
          )
         
        提醒  
	
	******************************************/
	
	public static function browser_info($agent=null) {
      
	  $known = array('msie', 'firefox', 'safari', 'opera', 'netscape','chrome');
      $agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
      $pattern = '#(' . join('|', $known) .')[/ ]+([0-9]+(?:\.[0-9]+)?)#';

      // Find all phrases (or return empty array if none found)
      //if (!preg_match($pattern, $agent, $matches)) return array();
      
	  //return array('browser' => $matches[1],'version' => $matches[2]);
      return array('browser' => 'chrome','version' => 'new');
	}
	
	public static function formatBytes($bytes, $precision = 2) { 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 
		return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 
	
	public static function byteConvert($bytes)
    {
        $s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes)/log(1024));
      
        return sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
    }
	
	
	public static function generator_password($StringLength){
      $password_len = intval($StringLength);
      $password = '';

      // remove o,0,1,l
      $word = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ23456789';
      $len = strlen($word);

      for ($i = 0; $i < $password_len; $i++) {
        $password .= $word[rand() % $len];
      }
      return $password;
    }
	
	
	/**
	* @version $Id: str_split.php 10381 2008-06-01 03:35:53Z pasamio $
	* @package utf8
	* @subpackage strings
	*/
	public static function utf8_str_split($str, $split_len = 1)
	{
		if (!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1)
			return FALSE;
 
		$len = mb_strlen($str, 'UTF-8');
		if ($len <= $split_len)
			return array($str);
 
		preg_match_all('/.{'.$split_len.'}|[^\x00]{1,'.$split_len.'}$/us', $str, $ar);
 
		return $ar[0];
	}
	
	
	/**
	文字顯示固定長度
     
	**/
	
	public static function short_string_utf8($string,$width){
	  $string_view = '';
	  
	  if(mb_strwidth($string,'UTF-8')>$width){
        $string_view = "<i title='".$string."' >".mb_strimwidth($string,0,$width,'…','UTF-8')."</i>"; 
	  }else{
	    $string_view = $string;
	  }
      return $string_view; 
	}
	
	
	/****-----
	  計算影像 DPI
	*********/
	public static function get_dpi($filename){
      $a = fopen($filename,'r');
      $string = fread($a,20);
      fclose($a);

      $data = bin2hex(substr($string,14,4));
      $x = substr($data,0,4);
      $y = substr($data,0,4);

      return array(hexdec($x),hexdec($y));
	} 
	
	
	
	//-- IS IP in RANGE ?  
	// [input] : Client Ip  :  User IP String ;
	// [input] : IP RANGE   :  IP Range String ;
	public static function check_ip_in_limit( $TargetIp , $IPRange ){
	  
	  function iptolong($ip){
        list($a, $b, $c, $d) = explode('.', $ip);
        return (($a * 256 + $b) * 256 + $c) * 256 + $d;
      }
	  
	  // 檢查受測IP	
	  
	  $ip2int = sprintf("%u", ip2long($TargetIp));
	  if($ip2int==="0"){  return false; }
	  
	  //完整IP
	  if( preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',$IPRange) && filter_var($IPRange, FILTER_VALIDATE_IP) ){
		return (iptolong($TargetIp)===iptolong($IPRange)) ? true : false;
		exit(1);
	  }
	  
	  //完整區段IP
	  if( preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\-(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/',$IPRange,$ipmatch) && filter_var($ipmatch[1], FILTER_VALIDATE_IP) && filter_var($ipmatch[2], FILTER_VALIDATE_IP)){
		return  (   iptolong(max($ipmatch[1],$ipmatch[2])) >= iptolong($TargetIp) && iptolong(min($ipmatch[1],$ipmatch[2])) <= iptolong($TargetIp)) ?  true : false;
	    exit(1);
	  }
	  
	  //底層區段 IP range
	  if( preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.)(\d{1,3}\-\d{1,3})$/',$IPRange,$ipmatch) ){  
		$range  = explode('-',$ipmatch[2]);
		$ipfrom = $ipmatch[1].$range[0];
		$ipto   = $ipmatch[1].$range[1];
 		if(filter_var($ipfrom, FILTER_VALIDATE_IP) && filter_var($ipto, FILTER_VALIDATE_IP)  ){
		  return  ( iptolong(max($ipfrom,$ipto)) >= iptolong($TargetIp) && iptolong(min($ipfrom,$ipto)) <= iptolong($TargetIp)) ?  true : false;
		  exit(1);
		}
		return false;
	  }
	}
	
	
	
	/******************************************
	*******************************************
	  預覽回報內容
	    
		參數 :
		  3. $EncodeMeta  = rawuriencode json array
		回傳 :
		  array('success'=>true,'meta'=>array(),'object'=>'')

        提醒 :
	      
	
	******************************************	  
	******************************************/
	public static function Preview_Modify_Field($MetaOrl='',$MetaMdf=''){ 
	  
	  mb_internal_encoding("UTF-8");
	  mb_regex_encoding("UTF-8");
	
	  if( mb_strlen($MetaOrl)>0 ){
		
		$meta_orl = $MetaOrl;
		$meta_mdf = $MetaMdf;
		
		// 測試CODE
        /*
		$meta_orl = '秘書處、組織部、調查部、會計部、總務部、交際部、上海工會組織統一委員會調解部、護工部、宣傳部。';
        $meta_mdf = '秘書處、組kjkjlkjhkjlh調查部、際部、織統一委ugu會計部、總務部、交解部、護工部、宣傳部。'; 
  
        echo "Target String:<br/>";
		echo $meta_orl."<br/><br/>";
		echo "mod String:<br/>";
		echo $meta_mdf."<br/><br/>";
        */
		
		
        if($meta_orl !== $meta_mdf){
		
          $meta_orlArray = self::utf8_str_split($meta_orl,1); // UTF8 切字
          
		  array_unshift($meta_orlArray,'');
		  array_push($meta_orlArray,'$');
		  
          $orl_word_count = count($meta_orlArray);
		  $string_longst  = array();
		  
		  $now_string_num = 0;
		 
		  
          for($i=0 ; $i<($orl_word_count-2); $i++){

			$base_array = $meta_orlArray;
			
			
			for($j=0 ; $j<=$i; $j++){
			  array_shift($base_array );
			}
			
			$longstString = '';
			$MatchStringPattern = array();
			
			$start_point = 0;
			
			while(count($base_array)){
			  $Word = array_shift($base_array);
			  $MatchStringPattern[]=$Word ;
			 
			  $match_point = mb_strpos($meta_mdf,join('',$MatchStringPattern));
			  if(count($MatchStringPattern) && $match_point!==false){
			  
			    
				$longstString = join('',$MatchStringPattern);
		        
				$start_point = $match_point;
			    
			  }else{
			    if($longstString){
				  $string_longst[] = array('start'=>$i,'num'=>mb_strlen($longstString),'point'=>$start_point ,'string'=>$longstString);
				  $i+=(mb_strlen($longstString)-1);	  
				  $now_string_num = $start_point+mb_strlen($longstString);
				}else{
				  $string_longst[] = array('start'=>$i,'num'=>mb_strlen($Word),'point'=>'-' ,'string'=>$Word );
				}
				break;
			  }
			}
		  }
		 
		 // echo $longstString.'/'.$now_string_num." / ".mb_substr($meta_mdf,$now_string_num)."<br/>";
		 
		  //修正重疊字串
		  $sort_list = array();
		  foreach($string_longst as $key => $longstring){
			if($longstring['point'] !== '-'){
			  $sort_list[$key] = $longstring['num'];
		    }
		  }
		  
		  arsort($sort_list);
		  $check_list = array();
		  foreach($sort_list as $key => $num){
			//echo $string_longst[$key]['string']."<br/>"; 
			
			for($i = $string_longst[$key]['point'] ; $i< ($string_longst[$key]['point']+$string_longst[$key]['num']); $i++){
			   if(isset($check_list[$i]) && $check_list[$i]){
				 unset($string_longst[$key]);
				 break;
			   }else{
			     $check_list[$i]=1;
			   }
			}
		  }
		  
		  
		  //修正重複字誤認為存在狀況
		  
		  $point = 0;
		  $pointkey = 0;
		  $final_set = array();
		  
		  
		  foreach($string_longst as $key => $longstring){
			
			if($longstring['point'] !== '-'){
			  
			  if( $point > $longstring['point']){
				
				$target_set = array_pop($final_set);
				$string     = $target_set['string'];
				
				$i=0;
				do{
				  $i++;
				  $substring = mb_substr($string,$i);
				  
				  if($substring){
				    $subpoint  = mb_strpos($meta_mdf,$substring);
				  }
				}
				while( $i<mb_strlen($string)  &&  $subpoint > $point);
				
				if( $i === mb_strlen($string)){
				  $string_longst[$pointkey]['point'] = '-';
				  array_push($final_set,$string_longst[$pointkey]);
				}else{
				  
				  $string_longst[$pointkey]['point'] = '-';
				  $string_longst[$pointkey]['num']   = $i;
				  $string_longst[$pointkey]['string']=mb_substr($string,0,$i);
				  
				  $add_set['start']  = $string_longst[$pointkey]['start']+$i;
				  $add_set['point']  = $subpoint;
				  $add_set['num']    = mb_strlen($substring);
				  $add_set['string'] = $substring;
				  
				  array_push($final_set,$string_longst[$pointkey],$add_set);
				  
				}
			  }
			  $point=$longstring['point'];
			  $pointkey = $key ;
			}
			
			array_push($final_set,$longstring);
		  }
		  
		  
		  /*
		  echo "<table>";
		  foreach($final_set as $key => $longstring){
			echo "<tr><td>".$longstring['start'].':'.$longstring['point'].':'.$longstring['num'].':'.$longstring['string']."</td>";
		    echo "<td>".mb_ereg_replace($longstring['string'] , "<span style='color:green;font-weight:600;'>".$longstring['string']."</span>" , $meta_mdf)."</td></tr>";
		  }
		  echo "</table>";
		  */
		  
		  // 建立
		  $nowpoint = 0;
		  $del_point=0;
		  $output = '';
		  
		  foreach($final_set as $key => $longstring){
		    
			//補上不連續刪除的字串 , 可能是被重複字串去除
			if( isset($longstring['strat']) && $longstring['strat']=== $del_point ){
			  
			}else{
			  $output .= "<del class='DelWord'>".mb_substr($meta_orl,$del_point,($longstring['start']-$del_point))."</del>";
			}
		
			if('-' === $longstring['point']){
			   $output .= "<del class='DelWord'>".mb_substr($meta_orl,$longstring['start'],$longstring['num'])."</del>";
			}else{   
			  if($nowpoint === $longstring['point']){
			    $output .= "<span class='SameWord'>".mb_substr($meta_mdf,$nowpoint,$longstring['num'])."</span>";
				$nowpoint+=$longstring['num']; 
			  }else{
				$output .= "<span class='AddWord'>".mb_substr($meta_mdf,$nowpoint,($longstring['point']-$nowpoint))."</span>";
			    $nowpoint = $longstring['point'];
				$output .= "<span class='SameWord'>".mb_substr($meta_mdf,$nowpoint,$longstring['num'])."</span>";
			    $nowpoint+=$longstring['num']; 
			  }
			}
		    $del_point = $longstring['start']+$longstring['num'];
		  } 
		  
		  if($nowpoint != mb_strlen($meta_mdf)){
		   $output .=  "<span class='AddWord'>".mb_substr($meta_mdf,$nowpoint)."</span>";
		  }
		  
		  return $output;
		  
		}else{
		  return $MetaOrl;
		}
	  
	  }else{
	    return "<span class='AddWord'>".$MetaMdf."</span>";
	  }
		
	}
    
	
	// Function to remove folders and files 
	public static   function rrmdir($dir) {
		if (is_dir($dir)) {
			$files = scandir($dir);
				foreach ($files as $file) if ($file != "." && $file != "..") self::rrmdir("$dir/$file");
				rmdir($dir);
		}else if (file_exists($dir)) unlink($dir);
	  }
	  
	  // Function to Copy folders and files       
	public static function rcopy($src, $dst) {
		if (file_exists ( $dst ))  self::rrmdir ( $dst );
		
		if (is_dir ( $src )) {
			mkdir ( $dst );
			$files = scandir ( $src );
			foreach ( $files as $file )
			   if ($file != "." && $file != "..") self::rcopy ( "$src/$file", "$dst/$file" );
		} else if (file_exists ( $src ))  copy ( $src, $dst );
	}
	////rcopy($source , $destination );  
	  
	  
	//-- 磁區大小顯示轉換  refer:http://php.net/manual/en/function.disk-total-space.php   2016/02/22
	public static function getSymbolByQuantity($bytes , $unit=0 ) {
      $symbols = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	  $exp = intval($unit) ? $unit : floor(log($bytes)/log(1024));
      return sprintf('%.2f '.$symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }  
	  
	  
	
	/*
	*  文字簡繁轉換
    *  use: Mediawiki zhconverter
	*  reference : 
	*		1. 使用 Mediawiki zhconverter 進行 PHP 網頁簡繁互轉 http://cw1057.blogspot.tw/2012/06/mediawiki-zhconverter-php.html
	*		2. mediawiki-zhconverter https://code.google.com/p/mediawiki-zhconverter/
	*		3. PHP繁簡轉換 http://xyz.cinc.biz/2013/03/php.html
	*   PS: mediawiki-zhconverter.inc.php 目前最多只能使用  1.15.5 版本  其他更高版本會有錯誤訊息
	*/
	
    public static function word_translate($word,$code='zh-tw'){
	  
	  define("MEDIAWIKI_PATH", _SYSTEM_ROOT_PATH."systemOption\\mediawiki-1.15.5\\");
      
	  /* Include our helper class */
      require_once _SYSTEM_ROOT_PATH."systemOption\\mediawiki-zhconverter.inc.php";

      /* Convert it, valid variants such as zh, zh-cn, zh-tw, zh-sg & zh-hk */
	  //echo MediaWikiZhConverter::convert("雪糕", "zh-tw") , ",";
	  //echo MediaWikiZhConverter::convert("記憶體", "zh-cn"), ",";
	  //echo MediaWikiZhConverter::convert("大卫·贝克汉姆", "zh-hk");
	  
	  return MediaWikiZhConverter::convert($word, $code);
	  
	}
	
	/**
    有tag string 切截
	**/
	public static function short_string_width_tags($string,$width=1){
	  
	  mb_internal_encoding("UTF-8");
	  
	  if(preg_match_all('/<.*?>.*?<\/.*?>/',$string,$tags,PREG_PATTERN_ORDER)){
        $maps = array_unique($tags[0]);
	    
		$change_pattern = array();
		$change_replace = array();
		$revarse_pattern = array();
		$revarse_replace = array();
	    
	    foreach($maps as $key => $tag_string){
		  $map_key = self::shiftSpace(chr($key+65),'full');
		  $change_pattern[] = '@'.$tag_string.'@u';
		  $change_replace[] = $map_key;
		  $revarse_pattern[] = '@'.$map_key.'@u';
		  $revarse_replace[] = $tag_string;
		}
		$encode_string = preg_replace($change_pattern ,$change_replace,$string);
		$short_string  = mb_substr($encode_string,0,$width);
		$decode_string = preg_replace($revarse_pattern ,$revarse_replace,$short_string);
		if(mb_strlen($encode_string) > mb_strlen($short_string)) $decode_string.='…';
		return $decode_string;
	  }else{
	    $string_view = mb_substr($string,0,$width);
	    if(mb_strlen($string) > mb_strlen($string_view)) $string_view.='…';
	    return $string_view;
	  }
	}
	
	
	/**
	 * ASCII 字元自動全形/半形轉換 (字碼補位法)
	 *
	 * @authro LIAO SAN-KAI
	 * 
	 * @param string $char 欲轉換的 ASCII 字元
	 * @param string $width 字形模式 half|full|auto (半形|全形|自動)
	 * @return string 轉換後的對應字元
	 */
	public static function shiftSpace($char=null, $width='auto') {

		//取得當前字元的16進位值
		$charHex = hexdec(bin2hex($char));

		//判斷當前字元為半形或全形
		$charWidth = ($char == '　' or ($charHex >= hexdec(bin2hex('！')) and $charHex <= hexdec(bin2hex ('～')))) ? 'full' : 'half';

		//如果字元字形與指定字形一樣，就直接回傳
		if($charWidth == $width) {
			return $char;
		}

		//如果是空白字元就直接比對轉換回傳
		if($char === '　' ) {
			return ' ';
		} elseif($char === ' ') {
			return '　';
		}

		//計算 ASCII 字元16進位的unicode差值
		$diff = abs(hexdec(bin2hex ('！')) - hexdec(bin2hex ('!')));

		//計算字元"_"之後的半形字元修正值(192)
		$fix = abs(hexdec(bin2hex ('＿')) - hexdec(bin2hex ('｀'))) - 1;

		//全形/半形轉換
		if($charWidth == 'full'){
			$charHex = $charHex - (($charHex > hexdec(bin2hex('＿'))) ? $diff + $fix : $diff); 
		} else {
			$charHex = $charHex + (($charHex > hexdec(bin2hex('_'))) ? $diff + $fix : $diff); 
		}

		return hex2bin(dechex($charHex));
	}
    
	
	//-- 將 MD5 長碼轉自訂短碼
	public static function md5_string_to_short_code($MD5String){
	    
		$hax = base64_encode(md5($MD5String.time(),true));
		$clean_hax = str_split(strtr($hax, array('+'=>'-','/'=>'_')));
		shuffle($clean_hax); 
		return substr(join('',$clean_hax),rand(0,2)*7,7);
		
		/*
		$base64 = str_split('OlJFu0Gt1Hs2Ir3TgUfAz5By6Cx7Dq4KmVeNjRiShPkQpLoMnw8Ev9WdXcYbZa_-');
		shuffle($base64);
		$hash = $MD5String; 
        $output = array(); 
        */
		
	}
    
	//-- CRC32 MAP TO 62CHR
	/**
	* Small sample convert crc32 to character map
	* Based upon http://www.php.net/manual/en/function.crc32.php#105703
	* (Modified to now use all characters from $map)
	* (Modified to be 32-bit PHP safe)
	*/
	public static function khashCRC32($data)
	{
		static $map = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$hash = bcadd(sprintf('%u',crc32($data)) , 0x100000000);
		$str = "";
		do
		{
			$str = $map[bcmod($hash, 62) ] . $str;
			$hash = bcdiv($hash, 62);
		}
		while ($hash >= 1);
		return $str;
	}
  
  }
  
  

?>