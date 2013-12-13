<?php
ob_start();
function tpl_header($title='Full-Text RSS Admin Area') {
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo $title; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	
	<meta name="robots" content="noindex, nofollow" />
	<link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css" media="screen" />
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/bootstrap-tooltip.js"></script>
	<script type="text/javascript" src="../js/bootstrap-popover.js"></script>
	<script type="text/javascript" src="../js/bootstrap-tab.js"></script>
  <style>
	html, body { background-color: #eee; }
	body { margin: 0; line-height: 1.4em; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; }
	label, input, select, textarea { font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; }
	.actions { padding-left: 220px; }
	.popover .inner { width: 200px; }
	#main-container { width: 800px; padding-bottom: 60px; }
	.page-header { padding-bottom: 0; }
  </style>
  </head>
  <body> 
  <div class="container" id="main-container">
  <div class="navbar">
    <div class="navbar-inner">
      <div class="container">
	  <a class="brand" href="../">Full-Text RSS</a>
		<ul class="nav">
		  <li class="active"><a href="update.php">Update site patterns</a></li>
		  <li><a href="index.php?logout">Logout</a></li>
		</ul>
      </div>
    </div>
   </div>  
  <div class="page-header"><h1><?php echo $title; ?></h1></div>
<?php
}

function tpl_footer() {
?>

  </div> <!-- close container -->
  </body>
</html>
<?php
}

register_shutdown_function('tpl_footer');