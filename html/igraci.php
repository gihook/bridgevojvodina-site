<?php
include 'admin/connectdb.php';
$query = "select i.Ime, i.Prezime, klub.Naziv from igrac as i
		  left join klub on i.KlubId = klub.Id order by KlubId, Prezime, Ime";
$result = mysqli_query($conn, $query);  
?>

<table class="table table-responsive table-borderless" id="members">
	<thead>
		<tr>
			<th></th>
			<th><?php echo $lang['ime1'] ?></th>						
			<th><?php echo $lang['prezime'] ?></th>
			<th><?php echo $lang['klub'] ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$n = 1;
			while ($rez = mysqli_fetch_array($result)) {
			echo "<tr><td>". $n++ ."</td>";
			echo "<td>".$rez['Ime']."</td>";
			echo "<td>".$rez['Prezime']."</td>";	
			echo "<td>".$rez['Naziv']."</td></tr>";
		}
		?>
	</tbody>
</table>
					