<?php  
session_start();

//include 'connectdb.php';
require_once('connectdb.php');

if (isset($_POST['log'])) {
	$korime = $_POST['korIme'];
	$lozinka = $_POST['lozinka'];
	$query = "select * from korisnik where KorIme = '$korime' and Lozinka = '$lozinka'";
	$result = mysqli_query($conn, $query);
	if (mysqli_num_rows($result) == 1) {
		$rezultat = mysqli_fetch_array($result);
		$_SESSION['ime'] = $rezultat['Ime'];
		$_SESSION['prezime'] = $rezultat['Prezime'];
		$_SESSION['status'] = $rezultat['StatusId'];
		$_SESSION['klubId'] = $rezultat['KlubId'];
		header('location: administrator.php');
	} else {
		header('location: index.php');		
	}
}

?>