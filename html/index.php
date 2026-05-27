<?php
include 'config.php';
?>

<!DOCTYPE html>
<html lang="sr">
<head>
  <title>Bridž savez Vojvodine</title>
  <link rel="icon" type = 'image/png' href="slike/logo.png" sizes="36x48">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="bridž savez vojvodine, bridge, contract bridge, bridž, vojvodina">

  <!-- Angular CDN -->
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular.min.js"></script>    
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular-route.min.js"></script>    
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<!-- Latest compiled JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

  <link rel="stylesheet" href="stil/stil.css">
</head>
<body>

		<nav class="navbar navbar-inverse">
	  <div class="container">
	    <div class="navbar-header">
	      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span> 
	      </button>
	    </div>
	    <div class="collapse navbar-collapse" id="myNavbar">
	      <ul class="nav navbar-nav navbar-left">
          <li><a href="index.php?opcija=pocetna"><?php echo $lang['početna'] ?></a></li>
	        <li><a href="index.php?opcija=events"><?php echo $lang['događaji'] ?></a></li>
	        <li><a href="index.php?opcija=members"><?php echo $lang['članovi'] ?></a></li>
	        <li><a href="index.php?opcija=igraci"><?php echo $lang['igraci'] ?></a></li>
	        <li><a href="index.php?opcija=contact"><?php echo $lang['kontakt'] ?></a></li>
	      </ul>
        <ul  class="nav navbar-nav navbar-right">
          <li><a href="https://www.facebook.com/bridgesavezvojvodine/"><img src="slike/facebook_logo.png" width="24" height="24"></a></li>
          <li><a href="index.php?lang=sr"><img src="slike/srzastava.png" width="24px" height="16px"></a></li>
          <li><a href="index.php?lang=en"><img src="slike/enzastava.png" width="24px" height="16px"></a></li>
        </ul>
	    </div>
	  </div>
	</nav>				 

	<div class="container" id="mainDiv">
		<div class="col-sm-2">
			<img class="img-responsive" src="slike/logo.png" alt="LogoBSV" id="logo">
		</div>
		<div class="col-sm-10">
		  <?php
	    	if (isset($_GET['opcija'])) {	      
		      	$fajl = $_GET['opcija'].".php";
		      	if (file_exists($fajl)) {
		           include_once($fajl);
			    } else {
			   	?>
		        <div class="alert alert-warning">
		          <h3>Greška!</h3>
		          <p>Stranica ne postoji.</p>
		        </div>
			   	<?php
		      }  		      
	    	} else {
	      	include_once("pocetna.php");
	    	}		    
	      ?>
		</div>
	</div>

<footer class="container-fluid navbar-fixed-bottom text-center">
	BSV
</footer>

</body>
</html>

