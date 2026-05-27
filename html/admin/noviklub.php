<?php 
session_start();

if ($_SESSION['status'] != 2 && $_SESSION['status'] != 1) {
	header('location: administrator.php');
}

include 'connectdb.php';

$naziv = "";
$mesto = "";
$adresa = "";
$zastupnik = "";
$email = "";
$telefon = "";
$link = "";
$status = "";

$update = false;
$id = 0;

if (isset($_POST['potvrda'])) {
	$naziv = $_POST['naziv'];
	$mesto = $_POST['mesto'];
	$adresa = $_POST['adresa'];
	$zastupnik = $_POST['zastupnik'];
	$email = $_POST['email'];
	$telefon = $_POST['telefon'];
	$link = $_POST['link'];
	$status = $_POST['status'];
	$query = "insert into klub (Naziv, Mesto, Adresa, Zastupnik, Email, Telefon, Link, Status) values ('$naziv', '$mesto', '$adresa', '$zastupnik', '$email', '$telefon', '$link', '$status')";
	$rez = mysqli_query($conn, $query);
	header('location: klubovi.php');
}

if (isset($_GET['delete'])) {
	$id = $_GET['delete'];
	$query = "delete from klub where Id = $id";
	$rez = mysqli_query($conn, $query);
	header('location: klubovi.php');
}

if (isset($_GET['edit'])) {
	$id = $_GET['edit'];
	$update = true;
	$query = "select * from klub where Id = $id";
	$result = mysqli_query($conn, $query);
	$rez = mysqli_fetch_array($result);
	$naziv = $rez['Naziv'];
	$mesto = $rez['Mesto'];
	$adresa = $rez['Adresa'];
	$zastupnik = $rez['Zastupnik'];
	$email = $rez['Email'];
	$telefon = $rez['Telefon'];
	$link = $rez['Link'];
	$status = $rez['Status'];
}

if (isset($_POST['izmena'])) {
	$id = $_POST['id'];
	$naziv = $_POST['naziv'];
	$mesto = $_POST['mesto'];
	$adresa = $_POST['adresa'];
	$zastupnik = $_POST['zastupnik'];
	$email = $_POST['email'];
	$telefon = $_POST['telefon'];
	$link = $_POST['link'];
	$status = $_POST['status'];
	$query = "update klub set Naziv = '$naziv', Mesto = '$mesto', Adresa = '$adresa', Zastupnik = '$zastupnik', Email = '$email', Telefon = '$telefon', Link = '$link', Status = '$status' where Id = $id";
	$rez = mysqli_query($conn, $query);
	header('location: klubovi.php');
}

?>
<head>
	<title>Novi klub</title>
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
		Izmena podataka o klubu
		<?php else: ?>
	    Novi klub
	  	<?php endif ?>
	</h3><br>

	<form action="#" method="post">
		<input type="hidden" name="id" value="<?php echo $id ?>">
	  <div class="form-group">
	    <label for="naziv">Naziv</label>
	    <input type="text" class="form-control" id="naziv" name="naziv" value="<?php echo $naziv ?>" placeholder="Unesi naziv kluba" required>
	  </div>
	  <div class="form-group">
	    <label for="mesto">Mesto</label>
	    <input type="text" class="form-control" id="mesto" name="mesto" value="<?php echo $mesto ?>" placeholder="Unesi mesto" required>
	  </div>
	  <div class="form-group">
	    <label for="adresa">Adresa</label>
	    <input type="text" class="form-control" id="adresa" name="adresa" value="<?php echo $adresa ?>" placeholder="Unesi adresu" required>
	  </div>
	  <div class="form-group">
	    <label for="zastupnik">Zastupnik</label>
	    <input type="text" class="form-control" id="zastupnik" name="zastupnik" value="<?php echo $zastupnik ?>" placeholder="Unesi ime i prezime zastupnika" required>
	  </div>
	  <div class="form-group">
	    <label for="email">Email</label>
	    <input type="text" class="form-control" id="email" name="email" value="<?php echo $email ?>" placeholder="Unesi email" required>
	  </div>
	  <div class="form-group">
	    <label for="telefon">Telefon</label>
	    <input type="text" class="form-control" id="telefon" name="telefon" value="<?php echo $telefon ?>" placeholder="Unesi broj telefona" required>
	  </div>
	  <div class="form-group">
	    <label for="telefon">Link</label>
	    <input type="text" class="form-control" id="link" name="link" value="<?php echo $link ?>" placeholder="Unesi link za sajt kluba">
	  </div>
	  <div class="form-group">
        <label for="sel1">Status</label>
        <select class="form-control" id="sel1" name="status" required>
          <option selected value=<?php echo $status ?>><?php echo $status; ?></option>
          <option value="Aktivan">Aktivan</option>
          <option value="Neaktivan">Neaktivan</option>
        </select>
  	  </div>

	  <?php 
	    if ($update): 		
	  ?>
		<button type="submit" name="izmena" class="btn btn-info">Izmeni</button>
		<?php else: ?>
	    <button type="submit" name="potvrda" class="btn btn-primary">Sačuvaj</button>
	  <?php endif ?>
	  <a href="klubovi.php" class="btn btn-warning" role="button">Odustani</a>
	</form>

  </div>
</div>