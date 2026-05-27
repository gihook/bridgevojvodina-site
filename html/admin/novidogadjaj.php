<?php 
session_start();

if ($_SESSION['status'] != 2 && $_SESSION['status'] != 1) {
	header('location: administrator.php');
}

include 'connectdb.php';

$naziv = "";
$opis = "";
$datum = "";
$korisnikovKlubId = $_SESSION['klubId'];

$update = false;
$id = 0;

if (isset($_POST['potvrda'])) {
	$naziv = $_POST['naziv'];
	$opis = $_POST['opis'];
	$datum = $_POST['datum'];
	$query = "insert into dogadjaj (Naziv, Opis, Datum, KorisnikovKlubId) values ('$naziv', '$opis', '$datum', '$korisnikovKlubId')";
	$rez = mysqli_query($conn, $query);
	header('location: dogadjaji.php');
}

// izmenjeno
if (isset($_GET['delete'])) {
	$id = $_GET['delete'];
	$query = "delete from dogadjaj where Id = $id";
	$rez = mysqli_query($conn, $query);
	header('location: dogadjaji.php');
}

if (isset($_GET['edit'])) {
	$id = $_GET['edit'];
	$update = true;
	$query = "select * from dogadjaj where Id = $id";
	$result = mysqli_query($conn, $query);
	$rez = mysqli_fetch_array($result);
	$naziv = $rez['Naziv'];
	$opis = $rez['Opis'];
	$datum = $rez['Datum'];
}

if (isset($_POST['izmena'])) {
	$id = $_POST['id'];
	$naziv = $_POST['naziv'];
	$opis = $_POST['opis'];
	$adresa = $_POST['datum'];
	
	$query = "update dogadjaj set Naziv = '$naziv', Opis = '$opis', Datum = '$datum' where Id = $id";
	$rez = mysqli_query($conn, $query);
	header('location: dogadjaji.php');
}

?>
<head>
	<title>Novi događaj</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>	
</head>
<div class="container">
  <div class="jumbotron">
	<h3 style="text-align: center; color: blue;">
		<?php if($update): ?>
		Izmena događaja
		<?php else: ?>
	    Novi događaj
	  	<?php endif ?>
	</h3><br>

	<form action="#" method="post">
		<input type="hidden" name="id" value="<?php echo $id ?>">
	  <div class="form-group">
	    <label for="naziv">Naziv</label>
	    <input type="text" class="form-control" id="naziv" name="naziv" value="<?php echo $naziv ?>" placeholder="Unesi naziv događaja" required>
	  </div>
	  <div class="form-group">
	    <label for="opis">Kratak opis</label>
	    <textarea type="text" class="form-control" id="opis" name="opis" rows="4" value="" placeholder="Unesi opis" ><?php echo $opis ?></textarea>
	  </div>
	  <div class="form-group">
	    <label for="datum">Godina</label>
	    <input type="text" class="form-control" id="datum" name="datum" value="<?php echo $datum ?>" placeholder="Unesi godinu bez tačke" required>
	  </div>	  
	  
	  <?php 
	    if ($update): 		
	  ?>
		<button type="submit" name="izmena" class="btn btn-info">Izmeni</button>
		<?php else: ?>
	    <button type="submit" name="potvrda" class="btn btn-primary">Sačuvaj</button>
	  <?php endif ?>
	  <a href="dogadjaji.php" class="btn btn-warning" role="button">Odustani</a>
	</form>

  </div>
</div>