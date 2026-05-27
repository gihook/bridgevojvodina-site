<?php  
session_start();
if (!isset($_SESSION['ime'])) {
  header('Location: index.php');
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
  <title>Administrator</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
   <!-- Angular CDN -->
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular.min.js"></script>    
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular-route.min.js"></script>    
  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <!-- jQuery library -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <!-- Latest compiled JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
  <div class="jumbotron">
  <div>
    <h3>Ulogovani ste kao <?php echo $_SESSION['ime']; ?> <?php echo $_SESSION['prezime']; ?></h3><br>
  </div>  
	<nav class="navbar navbar-default">
	  <div class="container-fluid">
	    <ul class="nav navbar-nav">
	      <li class="active"><a href="../index.php">Početna</a></li>
	      <li><a href="klubovi.php">Klubovi</a></li>
	      <li><a href="igraci.php">Igrači</a></li>
	      <li><a href="dogadjaji.php">Događaji</a></li>
        <?php  
        if($_SESSION['status'] == 2) {
          echo "<li><a href='korisnici.php'>Korisnici</a></li>";
        }
        ?>
	    </ul>
	  </div>
	</nav>
    <div>
     <h3>Na ovoj stranici možete unositi, menjati i brisati klubove i igrače, kao i rezultate turnira i najave događaja.</h3>
    </div>
    <br><br>
    <div>
      <a href="odjava.php" class="btn btn-primary" role="button">Odjavi se</a>
    </div>
  </div> 
</div>	
</body>
</html>
