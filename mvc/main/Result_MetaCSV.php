<?php
  
  class Result_MetaCSV extends View{
    
	public function fetch(){
	    
	    $data_list = isset( $this->vars['server']['data']['record'] ) ?  $this->vars['server']['data']['record'] : array();
	    
	    $csv_doc  = '';
		$csv_doc  = "\xEF\xBB\xBF";
        $csv_doc .= _SYSTEM_HTML_TITLE."-資料目錄\n";
		$csv_doc .= "輸出數量：,".(count($data_list)-1)."\n";
		$csv_doc .= "\n";
        
		// 輸出資料 // 第一行為欄位
		foreach($data_list as $meta_record){ 
		  $csv_doc .='"'.join('",="',$meta_record).'"'."\n";
		}
	    return $csv_doc;
	}  
    
	public function render(){
	  $fine_name = isset( $this->vars['server']['data']['fname'] ) ?  $this->vars['server']['data']['fname'] : _SYSTEM_NAME_SHORT.'_export_'.date('YmdHis');
	  $output_content  = $this->fetch(); 
	  $output_filename = $fine_name.'.csv';
	  
	  ob_clean();
	  header('Pragma: public');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');      
	  header('Cache-Control: private', false);
      header("Content-Description: File Transfer");
	  header('Content-Type: text/csv;charset=utf-8');
	  header('Content-Length: ' . strlen($output_content));
      header('Content-Disposition: attachment; filename="' .$output_filename. '";');
      header('Content-Transfer-Encoding: binary');
      echo $output_content;
	}
  }



?>