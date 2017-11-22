<!DOCTYPE html>
<html lang="en">
  <head>
		<meta charset="utf-8">
		<title>OTOME</title>
        <link rel="stylesheet" href="css/cnnction.css">
        <link href="https://fonts.googleapis.com/css?family=PT+Sans" rel="stylesheet">


  </head>

<body>
	<div class="">
		<div class="">
			<div class="">

            <a class="" href="<?php echo $_SERVER['PHP_SELF']; ?>" >Accueil</a>

				<?php
					if ($this->login===null) $this->displayLoginForm();
					else $this->displayLogoutForm();
				?>
			</div>
		</div>
	</div>

<?php
	$this->displayBody();
?>


</body>
<br />
<br />
<br />
<br />
<br />
<br />
