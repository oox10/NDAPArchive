<!DOCTYPE HTML>
<html>
  <head>
    <meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<!-- PHP DATA -->
	<?php
	$newbase64 = isset($this->vars['server']['data']) ? $this->vars['server']['data'] : ''; 
	?>
	<script type="text/javascript" >
	  parent.reloadportrait('<?php echo $newbase64?>');
	</script>
  </head>
  
  <body></body>
</html>
