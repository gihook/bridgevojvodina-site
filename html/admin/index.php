<!DOCTYPE html>
<html lang="sr">
<head>
  <title>Administrator</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">  
   <!-- Angular CDN -->
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular.min.js"></script>    
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular-route.min.js"></script>    
  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <!-- jQuery library -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <!-- Latest compiled JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
  <h3>Prijavite se:</h3>
  <form action="login.php" method="post">
    <div class="form-group">
      <label for="korIme">Korisničko ime:</label>
      <input type="text" class="form-control" id="korIme" placeholder="Unesite vaše korisničko ime" name="korIme" required>
    </div>
    <div class="form-group">
      <label for="lozinka">Lozinka:</label>
      <input type="password" class="form-control" id="lozinka" placeholder="Unesite vašu lozinku" name="lozinka" required>
    </div>    
    <button type="submit" class="btn btn-info" name="log">OK</button>
  </form>
</div>	
</body>
</html>
