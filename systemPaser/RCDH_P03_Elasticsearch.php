<?php
  
  
  define('ROOT',dirname(dirname(__FILE__)).'/');
  ob_start();
  
  require ROOT.'mvc/lib/vendor/autoload.php';
  ini_set("memory_limit", "2048M");
  
  require_once(dirname(dirname(__FILE__)).'/conf/server_config.php');
  require_once(dirname(dirname(__FILE__)).'/mvc/core/DBModule.php');   
  
  
  $hosts = [
    '127.0.0.1:9200',         // IP + Port
    //'192.168.1.2',              // Just IP
    //'mydomain.server.com:9201', // Domain + Port
    //'mydomain2.server.com',     // Just Domain
    //'https://localhost',        // SSL to localhost
    //'https://192.168.1.3:9200'  // SSL to IP + Port
  ];
 
  $defaultHandler = Elasticsearch\ClientBuilder::defaultHandler();
  $singleHandler  = Elasticsearch\ClientBuilder::singleHandler();
  $multiHandler   = Elasticsearch\ClientBuilder::multiHandler();
  //$customHandler  = new MyCustomHandler();
  
  
  $client = Elasticsearch\ClientBuilder::create()
              ->setHandler($defaultHandler)
              ->setHosts($hosts)
			  ->setRetries(0)
			  ->build();
  
  
  //-- load meta assist from db 
  $db = new DBModule;
  $db->db_connect('PDO'); 
  
  $db_search = $db->DBLink->prepare("SELECT count(*) FROM metadata WHERE _keep=1;");
  if(!$db_search->execute()){	
    throw new Exception('insert fail');
  }	
  $total = $db_search->fetchColumn();
  
  $inedx_mode = 'bulk';
  
  
  echo "START INDEX [ndap] : ".$total."..\n";
  
  
  $response = $client->indices()->delete(array('index' => 'ndap'));
  $response = $client->indices()->create(
    [  'index' => 'ndap',
	   'body'  => [
	     //'settings' => [
		    //'index.cache.query.enable' => true,
            //'indices.cache.query.size' => '2%' 
		 //],
		 'mappings'=>[
		   '_default_'=>[
		     "date_detection"=>false
		    ],
			'my_type'=>[
			  'properties' => [
                    'data_type' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'zong'=> [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'serial'=> [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'collection'=> [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ], 
					'identifier'=> [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'meeting_level' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'category_level' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'collection_name' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'date_string' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'date_start' => [
                        'type' => 'date',
						//'index' => 'not_analyzed'
                    ],
					'date_end' => [
                        'type' => 'date',
						//'index' => 'not_analyzed'
                    ],
					'abstract' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'abstract_mask' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'fulltext' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'chairman' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'main_mamber' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'docno' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'reference' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'pageinfo' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'yearrange' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'list_member' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'list_organ' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'list_location' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'list_subject' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
					'_flag_secret' => [
                        'type' => 'boolean',
						
                    ],
					'_flag_privacy' => [
                        'type' => 'boolean',
						
                    ],
					'_flag_open' => [
                        'type' => 'boolean',
						
                    ],
					'_flag_mask' => [
                        'type' => 'boolean',
						
                    ],
					'_flag_update' => [
                        'type' => 'boolean',
						
                    ],
					'_flag_view' => [
                        'type' => 'string',
						'index' => 'not_analyzed'
                    ],
               ]
			]
		  ]
		]
    ]);	
  
  
  for( $i=0 ; $i<$total ; $i+=10000 ){
	  
    $db_search = $db->DBLink->prepare("SELECT * FROM metadata WHERE _keep=1 ORDER BY system_id ASC LIMIT ".$i.",10000;");
    if(!$db_search->execute()){	
      throw new Exception('insert fail');
    }	
  
    ob_flush();
    flush();
	
	
	$params = ['body' => []];
	$counter= $i+1;
	while($tmp = $db_search->fetch(PDO::FETCH_ASSOC)){
		
		if(!count($params['body'])) echo "\n START:".$tmp['system_id'];
		
		if($inedx_mode=='bulk'){
		  //-- bulk index
		  $params['body'][] = [
			'index' => [
				'_index' => 'ndap',
				'_type'  => 'search',
				'_id'    => $tmp['system_id'],
			]
          ];
		
		  $search_array = json_decode($tmp['search_json'],true);
		  $params['body'][] = $search_array;
		  $db->DBLink->query('UPDATE metadata SET _index=1 WHERE system_id='.$tmp['system_id'].';');
		  
		}else{
		  //-- singel index 
		  $params = array(
		    'index' => 'ndap',
		    'type'  => 'search',
		    'id'    => $tmp['system_id'],
		    'body'  => json_decode($tmp['search_json'],true)
		  );
        
		  $response = $client->index($params);	
		  ob_flush();
		  flush();
		
		  echo "\n".$tmp['system_id'].": ";
		
		  if(!$response['_shards']['successful']){
		    var_dump($tmp['search_json']);
		    exit(1);
		  }
		  echo "success.";	
		  $db->DBLink->query('UPDATE metadata SET _index=1 WHERE system_id='.$tmp['system_id'].';');
		
		}
		
		$counter = $tmp['system_id'];		
	}
	
	
	if($inedx_mode=='bulk'){ 
	  //-- bulk index
	
	  echo "- END:".$counter.": ";	
	  $responses = $client->bulk($params);
    
      if($responses['errors']){
	    var_dump($responses['total']);
	    exit(1);
      }
    
	  echo "success.";	
	  // erase the old bulk request
      $params = ['body' => []];
	  
	  // unset the bulk response when you are done to save memory
      unset($responses);
	}
	
	
	
  }
  
  // Send the last batch if it exists
  if (!empty($params['body'])) {
    $responses = $client->bulk($params);
  }
  
  
?>