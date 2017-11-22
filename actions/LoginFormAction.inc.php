<?php

require_once("actions/Action.inc.php");

class LoginUpFormAction extends Action {

	/**
	 * Dirige l'utilisateur vers le formulaire de connexion.
	 *
	 * @see Action::run()
	 */	
	public function run() {
		$this->setView(getViewByName("LoginUpForm"));
	}

}

?>
