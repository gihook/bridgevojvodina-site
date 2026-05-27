<?php
if(isset($_POST['submit'])){
$name = $_POST['name'];
$email = $_POST['email'];
$sender = 'info@bridgevojvodina.rs';
$recipient = 'bridge.savez.vojvodine@gmail.com';
$subject = "Poruka sa sajta";
$message = "Od: " . $name . "\nEmail: " . $email . "\n" . $_POST['message'];
$headers = 'From:'.$sender;
$rez = mail($recipient, $subject, $message, $headers);
}
?>				 
				
<div class="row">
	<div class="col-sm-6" id="contact">
	 <h4><?php echo $lang['kontakt'] ?>:</h4>	
	 <h4>Jovana Maričić</h4>
	 <h5><?php echo $lang['predsednik'] ?></h5>
	 <h4>Stevan Miškov</h4>
	 <h5><?php echo $lang['sekretar'] ?></h5>
		<form action="#" method="post">
		  <div class="form-group">
				<label for="name"><?php echo $lang['pošaljiMail']; ?></label>
				<input type="text" name="name" class="form-control" id="name" placeholder=<?php echo $lang['ime']; ?> required>
		  </div>
		  <div class="form-group">
				<input type="email" name="email" class="form-control" id="email" placeholder=<?php echo $lang['email']; ?> required>
		  </div>
		  <div class="form-group">
				<textarea type="text" name="message" class="form-control" rows="3" id="pwd" placeholder=<?php echo $lang['tekst']; ?> required></textarea>
		  </div>							  
		  <button type="submit" name="submit" class="btn btn-default"><?php echo $lang['pošalji']; ?></button>
		  <?php
				if (isset($_POST['submit'])) {
	        if($rez) {
	        echo $lang['porukaposlata'];
	        } else {
	          echo " Error: Message not accepted";
	        }
	       }
		  ?>
		</form> <br>		
	</div>
	<div class="col-sm-6" id="mapa">
		<iframe frameborder="1" height="430" scrolling="no" src="https://maps.google.com?saddr= Bridž savez Vojvodine, Danila Kiša 25, 21000 NoviSad&z=12&output=embed" width="100%"></iframe>
	</div>
</div>			
				