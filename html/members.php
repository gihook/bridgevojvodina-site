<?php
include 'admin/connectdb.php';
$query = "select * from klub";
$result = mysqli_query($conn, $query);  
?>

<table class="table table-responsive" id="members">
	<thead>
		<tr>
			<th></th>
			<th>Naziv kluba</th>
			<th>Mesto</th>
			<th>Adresa</th>
			<th>Zastupnik</th>
			<th>E-mail</th>
		</tr>
	</thead>
	<tbody>		
		<?php
			$n = 1;
			while ($rez = mysqli_fetch_array($result)) {
		    if($rez['Status'] == 'Aktivan'){
				echo "<tr><td>". $n++ ."</td>";
				echo "<td><a href='".$rez['Link']."' target='_blank' style='color: white'>".$rez['Naziv']."</a></td>";
				echo "<td>".$rez['Mesto']."</td>";	
				echo "<td>".$rez['Adresa']."</td>";	
				echo "<td>".$rez['Zastupnik']."</td>";
				echo "<td>".$rez['Email']."</td></tr>";			
		    }
			}
		?> 		
	</tbody>
</table>