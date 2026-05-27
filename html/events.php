<?php
include 'admin/connectdb.php';
$query = "select * from dogadjaj order by id desc";
$result = mysqli_query($conn, $query);  
?>			 		

<table class="table table-responsive table-borderless" id="events">
	<thead>
		<tr>
			<th></th>
			<th>Naziv događaja</th>
			<th>Opis</th>
		</tr>
	</thead>
	<tbody>		
		<?php
			$n = 1;
			while ($rez = mysqli_fetch_array($result)) {
			echo "<tr><td style='padding: 10px;'>". $n++ ."</td>";
			echo "<td style='padding: 10px; color: red;'><i>".$rez['Naziv']."</i></td>";
			echo "<td style='padding: 10px; color: black; font-size: 12px;'><textarea cols='100' wrap='hard'>".$rez['Opis']."</textarea></td></tr>";				        
			}
		?> 		
	</tbody>
</table>
	
