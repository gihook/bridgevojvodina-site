<?php 
session_start();

if ($_SESSION['status'] != 2 && $_SESSION['status'] != 1) {
	header('location: administrator.php');
}

include 'connectdb.php';

$korisnikovKlubId = $_SESSION['klubId'];

$query = "select * from klub";
$result = mysqli_query($conn, $query);

$qry = "select * from klub where Id = $korisnikovKlubId";
$rslt = mysqli_query($conn, $qry);
$rz = mysqli_fetch_array($rslt);
$nazivKluba = $rz['Naziv'];

$ime = "";
$prezime = "";
$korime = "";
$lozinka = "";
$status = "";

$update = false;

if (isset($_POST['potvrda'])) {
	$ime = $_POST['ime'];
	$prezime = $_POST['prezime'];
	$klubId = $_POST['klubId'];
	$query = "insert into igrac (Ime, Prezime, KlubId) values ('$ime', '$prezime', $klubId)";
	$rez = mysqli_query($conn, $query);
	header('location: igraci.php');
}

if (isset($_GET['edit'])) {
	$id = $_GET['edit'];
	$update = true;
	$query = 	"select k.Id, k.Ime, k.Prezime, s.Naziv, s.Id as KId from igrac as k
				 left join klub as s on k.KlubId = s.Id
				 where k.Id = $id";
	$resedit = mysqli_query($conn, $query);
	$redit = mysqli_fetch_array($resedit);
	$ime = $redit['Ime'];
	$prezime = $redit['Prezime'];
	$kid = $redit['KId'];
	$klub = $redit['Naziv'];
}

if (isset($_POST['izmena'])) {
	$id = $_POST['id'];
	$ime = $_POST['ime'];
	$prezime = $_POST['prezime'];
	$klubid = $_POST['klubId'];	
	$query = "update igrac set Ime = '$ime', Prezime = '$prezime', KlubId = '$klubid' where Id = $id";
	$rez = mysqli_query($conn, $query);
	header('location: igraci.php');
}

?>
<head>
	<title>Novi igrač</title>
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
		Izmena igrača
		<?php else: ?>
	    Novi igrač
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
	    <label for="sel1">Izaberi klub</label>
	    <?php if($_SESSION['status'] == 2): ?>
	        <select class="form-control" id="sel1" name="klubId" required>	
	        	<?php if($update): ?>
	        	<option selected value=<?php echo $kid ?>><?php echo $klub ?></option>	
	        	<?php endif ?>
				<?php
				while($rez = mysqli_fetch_array($result)):
				?>			
				<option value=<?php echo $rez['Id'] ?>><?php echo $rez['Naziv'] ?></option>
				<?php endwhile ?>		
	        </select>
			<?php else: ?>    
				<select class="form-control" id="sel1" name="klubId" required>
				 <?php if($update): ?>
				  <option selected value=<?php echo $kid ?>><?php echo $klub ?></option>
				 <?php else: ?>
				  <option selected value=<?php echo $_SESSION['klubId']; ?>><?php echo $nazivKluba ?></option>
				 <?php endif ?>
				</select>
			<?php endif ?>         
	  </div>
	  <?php if($update): ?>
		<button type="submit" name="izmena" class="btn btn-info">Izmeni</button>
		<?php else: ?>
	    <button type="submit" name="potvrda" class="btn btn-primary">Sačuvaj</button>
	  <?php endif ?>
	  <a href="igraci.php" class="btn btn-warning" role="button">Odustani</a>
	</form>
  </div>
</div>