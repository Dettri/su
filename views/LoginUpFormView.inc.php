<?php
require_once("views/View.inc.php");

class LoginUpFormView extends View {
	
	/**
	 * Affiche le formulaire d'inscription.
	 *
	 * @see View::displayBody()
	 */
	public function displayBody() {
		require("templates/loginupform.inc.php");
	}

}
?>

