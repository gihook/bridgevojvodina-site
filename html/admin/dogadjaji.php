<?php 
session_start();

if ($_SESSION['status'] == null) {
  header('location: administrator.php');
}
$ovlascen = false;

include 'connectdb.php';

$query = "select * from dogadjaj";
$result = mysqli_query($conn, $query);  

?>

<head>
  <title>Događaji</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> 
</head>
<div class="container">  
  <div class="jumbotron">
    <h3 style="text-align: center; color: blue;">Događaji</h3><br>
    <div class="table-responsive">          
    <table class="table" style="font-size: small;">
      <thead>
        <tr>
          <th>Id</th>
          <th>Naziv događaja</th>
          <th>Opis</th>
          <th>Godina</th>  
          <th></th>                 
        </tr>
      </thead>
      <tbody>
        
          <?php 
            while ($rez = mysqli_fetch_array($result)) {
              echo "<tr><td>".$rez['Id']."</td>";
              echo "<td>".$rez['Naziv']."</td>";
              echo "<td>".$rez['Opis']."</td>";  
              echo "<td>".$rez['Datum']."</td>";
              if($_SESSION['klubId'] == $rez['KorisnikovKlubId'] || $_SESSION['status'] == 2) {
                echo "<td><a href=\"novidogadjaj.php?edit=". $rez['Id']."\" class=\"btn btn-info btn-xs\" 
                >Izmeni</a></td>";
                echo "<td><a onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" href=\"novidogadjaj.php?delete=". $rez['Id']."\" class=\"btn btn-danger btn-xs\">Briši</a></td>";
              } else {
                echo "<td><a href=\"novidogadjaj.php?edit=". $rez['Id']."\" class=\"btn btn-info btn-xs disabled\" 
                >Izmeni</a></td>";
                echo "<td><a onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" href=\"novidogadjaj.php?delete=". $rez['Id']."\" class=\"btn btn-danger btn-xs disabled\">Briši</a></td>";
              }         
            }
          ?> 
       
      </tbody>
    </table>
  </div>
  <div style="text-align: center;">
    <a href="novidogadjaj.php" class="btn btn-primary" role="button">Novi događaj</a>
    <a href="administrator.php" class="btn btn-warning" role="button">Nazad</a>
  </div>
  </div>
</div>