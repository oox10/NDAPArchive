<?php
  class Result_MEDIA extends View{
	
	public function fetch($ImageData = array()){
	
	}
	
	public function render(){
	  require_once(dirname(dirname(__FILE__)).'/lib/VideoStream.php');
	  $video_data = $this->vars['server']['data'];
	  //$file_name = pathinfo($video_data['address'],PATHINFO_BASENAME );
	  ob_clean();
	  $video = new VideoStream( $video_data['address'] );  // from https://gist.github.com/ranacseruet/9826293
	  $video->start(); 
	}
  
  }

?>