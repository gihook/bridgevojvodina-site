<?php 
session_start();

if ($_SESSION['status'] == null) {
  header('location: administrator.php');
}

include 'connectdb.php';

$query = "select k.Id, k.Ime, k.Prezime, k.KlubId, klub.Naziv from igrac as k
      left join klub on k.KlubId = klub.Id";
$result = mysqli_query($conn, $query);

if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $query = "delete from igrac where Id = $id";
  mysqli_query($conn, $query);
  header('location: igraci.php'); 
}

?>

<head>
  <title>Igrači</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> 
</head>
<div class="container">
  <div class="jumbotron">  
  <h3 style="text-align: center; color: blue;">Igrači</h3><br> 
    <div class="table-responsive">          
    <table class="table">
      <thead>
        <tr>
          <th>Id</th>
          <th>Ime</th>
          <th>Prezime</th>
          <th>Klub</th>
        </tr>
      </thead>
      <tbody>
        
          <?php 
            while ($rez = mysqli_fetch_array($result)) {
              echo "<tr><td>".$rez['Id']."</td>";
              echo "<td>".$rez['Ime']."</td>";
              echo "<td>".$rez['Prezime']."</td>";  
              echo "<td>".$rez['Naziv']."</td>";

             if($_SESSION['klubId'] == $rez['KlubId'] || $_SESSION['status'] == 2) {
                echo "<td><a href=\"noviigrac.php?edit=". $rez['Id']."\" class=\"btn btn-info btn-xs\" 
                >Izmeni</a></td>";
                echo "<td><a onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" href=\"igraci.php?delete=". $rez['Id']."\" class=\"btn btn-danger btn-xs\">Briši</a></td>";
              } else {
                echo "<td><a href=\"noviigrac.php?edit=". $rez['Id']."\" class=\"btn btn-info btn-xs disabled\" 
                >Izmeni</a></td>";
                echo "<td><a onclick=\"return confirm('Sigurno želite da izbrišete zapis?')\" href=\"igraci.php?delete=". $rez['Id']."\" class=\"btn btn-danger btn-xs disabled\">Briši</a></td>";
              }       

            }
          ?>
       
      </tbody>
    </table>
  </div>
  <div style="text-align: center;">
    <a href="noviigrac.php" class="btn btn-primary" role="button">Novi igrač</a>
    <a href="administrator.php" class="btn btn-warning" role="button">Nazad</a>
  </div>
  </div>
</div>