<?php 
session_start();

if ($_SESSION['status'] != 2) {
	header('location: administrator.php');
}

include 'connectdb.php';

$query = "select * from status";
$result = mysqli_query($conn, $query);

$query = "select * from klub";
$result_klub = mysqli_query($conn, $query);

$ime = "";
$prezime = "";
$korime = "";
$lozinka = "";
$status = "";

$update = false;

if (isset($_POST['potvrda'])) {
	$ime = $_POST['ime'];
	$prezime = $_POST['prezime'];
	$korime = $_POST['korime'];
	$lozinka = $_POST['lozinka'];
	$status = $_POST['status'];
	$klubid = $_POST['klubId'];
	$query = "insert into korisnik (Ime, Prezime, KorIme, Lozinka, StatusId, KlubId) values ('$ime', '$prezime', '$korime', '$lozinka', $status, $klubid)";
	$rez = mysqli_query($conn, $query);
	header('location: korisnici.php');
}

if (isset($_GET['edit'])) {
	$id = $_GET['edit'];
	$update = true;
	$query = 	"select k.Id, k.Ime, k.Prezime, k.KorIme, k.Lozinka, s.Naziv, s.Id as SId, klub.Id as klubId, 
				 klub.Naziv as Ime_Kluba from korisnik as k
				 left join status as s on k.StatusId = s.Id
				 left join klub on k.KlubId = klub.Id
				 where k.Id = $id";
	$resedit = mysqli_query($conn, $query);
	$redit = mysqli_fetch_array($resedit);
	$ime = $redit['Ime'];
	$prezime = $redit['Prezime'];
	$korime = $redit['KorIme'];
	$lozinka = $redit['Lozinka'];
	$sid = $redit['SId'];
	$status = $redit['Naziv'];
	$kid = $redit['klubId'];
	$klub = $redit['Ime_Kluba'];
}

if (isset($_POST['izmena'])) {
	$id = $_POST['id'];
	$ime = $_POST['ime'];
	$prezime = $_POST['prezime'];
	$korime = $_POST['korime'];
	$lozinka = $_POST['lozinka'];
	$statusid = $_POST['status'];	
	$klubid = $_POST['klubId'];
	$query = "update korisnik set Ime = '$ime', Prezime = '$prezime', KorIme = '$korime', Lozinka = '$lozinka', StatusId = '$statusid', KlubId = '$klubid' where Id = $id";
	$rez = mysqli_query($conn, $query);
	header('location: korisnici.php');
}

?>
<head>
	<title>Novi korisnik</title>
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
		Izmena korisnika
		<?php else: ?>
	    Novi korisnik
	  	<?php endif ?>
	</h3><br>

	<form action="#" method="post">
	    <input type="hidden" name="id" value="<?php echo $id ?>"> 
	  <div class="form-group">
	    <label for="ime">Ime</label>
	    <input type="text" class="form-control" id="ime" name="ime" value="<?php echo $ime ?>" placeholder="Unesi ime" required>
	  </div>
	  <div class="form-group">
	    <label for="prezime">Prezime</label>
	    <input type="text" class="form-control" id="prezime" name="prezime" value="<?php echo $prezime ?>" placeholder="Unesi prezime" required>
	  </div>
	  <div class="form-group">
	    <label for="korime">Korisničko ime</label>
	    <input type="text" class="form-control" id="korime" name="korime" value="<?php echo $korime ?>" placeholder="Unesi korisničko ime" required>
	  </div>
	  <div class="form-group">
	    <label for="lozinka">Lozinka</label>
	    <input type="text" class="form-control" id="lozinka" name="lozinka" value="<?php echo $lozinka ?>" placeholder="Unesi lozinku" required>
	  </div>
	  <div class="form-group">
	    <label for="sel1">Izaberi status</label>
        <select class="form-control" id="sel1" name="status" required>	
          	<?php if($update): ?>
        	<option selected value=<?php echo $sid ?>><?php echo $status ?></option>
          	<?php endif ?>
			<?php
			while($rez = mysqli_fetch_array($result)):
			?>						
			<option value=<?php echo $rez['Id'] ?>><?php echo $rez['Naziv'] ?></option>
			<?php endwhile ?>		
        </select>
	  </div>
	  <div class="form-group">
	    <label for="sel1">Izaberi klub</label>
        <select class="form-control" id="sel1" name="klubId" required>	
        	<?php if($update): ?>
        	<option selected value=<?php echo $kid ?>><?php echo $klub ?></option>	
        	<?php endif ?>
			<?php
			while($rez = mysqli_fetch_array($result_klub)):
			?>			
			<option value=<?php echo $rez['Id'] ?>><?php echo $rez['Naziv'] ?></option>
			<?php endwhile ?>		
        </select>
	  </div>
	  <?php if($update): ?>
		<button type="submit" name="izmena" class="btn btn-info">Izmeni</button>
		<?php else: ?>
	    <button type="submit" name="potvrda" class="btn btn-primary">Sačuvaj</button>
	  <?php endif ?>
	  <a href="korisnici.php" class="btn btn-warning" role="button">Odustani</a>
	</form>
  </div>
</div>