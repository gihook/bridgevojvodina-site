<?php 
session_start();

if ($_SESSION['status'] == null) {
	header('location: administrator.php');
}

include 'connectdb.php';

$query = "select * from klub";
$result = mysqli_query($conn, $query);  

?>

<head>
	<title>Klubovi</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>	
</head>
<div class="container">  
  <div class="jumbotron">
  	<h3 style="text-align: center; color: blue;">Klubovi</h3><br>
  	<div class="table-responsive">          
	  <table class="table" style="font-size: small;">
	    <thead>
	      <tr>
	        <th>Id</th>
	        <th>Naziv kluba</th>
	        <th>Mesto</th>
	        <th>Adresa</th>
	        <th>Zastupnik</th>
	        <th>Email</th>
	        <th>Telefon</th>
	        <th>Link</th>
	        <th>Status</th>	        
	      </tr>
	    </thead>
	    <tbody>
	      
	      	<?php 
	      		while ($rez = mysqli_fetch_array($result)) {
	      		  echo "<tr><td>".$rez['Id']."</td>";
	      		  echo "<td>".$rez['Naziv']."</td>";
	      		  echo "<td>".$rez['Mesto']."</td>";	
	      		  echo "<td>".$rez['Adresa']."</td>";	
	      		  echo "<td>".$rez['Zastupnik']."</td>";
	      		  echo "<td>".$rez['Email']."</td>";
	      		  echo "<td>".$rez['Telefon']."</td>";	
	      		  echo "<td>".$rez['Link']."</td>";
				  echo "<td>".$rez['Status']."</td>";	

				  if($_SESSION['klubId'] == $rez['Id'] || $_SESSION['status'] == 2) {
	                echo "<td><a href=\"noviklub.php?edit=". $rez['Id']."\" class=\"btn btn-info btn-xs\" 
	                >Izmeni</a></td>";
	                echo "<td><a onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" href=\"klubovi.php?delete=". $rez['Id']."\" class=\"btn btn-danger btn-xs\">Briši</a></td>";
	              } else {
	                echo "<td><a href=\"noviklub.php?edit=". $rez['Id']."\" class=\"btn btn-info btn-xs disabled\" 
	                >Izmeni</a></td>";
	                echo "<td><a onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" href=\"klubovi.php?delete=". $rez['Id']."\" class=\"btn btn-danger btn-xs disabled\">Briši</a></td>";
	              }    

	      		}
	      	?> 
	     
	    </tbody>
	  </table>
	</div>
	<div style="text-align: center;">
		<?php if ($_SESSION['status'] == 2): ?>
			<a href="noviklub.php" class="btn btn-primary" role="button">Novi klub</a>
		<?php endif ?>
		<a href="administrator.php" class="btn btn-warning" role="button">Nazad</a>
	</div>
  </div>
</div>