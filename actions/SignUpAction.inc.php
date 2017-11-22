<?php

require_once("actions/Action.inc.php");

class SignUpAction extends Action {

	/**
	* Traite les données envoyées par le formulaire d'inscription
	* ($_POST['signUpLogin'], $_POST['signUpPassword'], $_POST['signUpPassword2']).
	*
	* Le compte est crée à l'aide de la méthode 'addUser' de la classe Database.
	*
	* Si la fonction 'addUser' retourne une erreur ou si le mot de passe et sa confirmation
	* sont différents, on envoie l'utilisateur vers la vue 'SignUpForm' contenant
	* le message retourné par 'addUser' ou la chaîne "Le mot de passe et sa confirmation
	* sont différents.";
	*
	* Si l'inscription est validée, le visiteur est envoyé vers la vue 'MessageView' avec
	* un message confirmant son inscription.
	*
	* @see Action::run()
	*/
	public function run() {
		// On vérifie si tous les champs ont été remplis
		if (!empty($_POST["signUpLogin"]) && !empty($_POST["signUpPassword"]) && !empty($_POST["signUpPassword2"]))
		{
			if ($_POST["signUpPassword"] != $_POST["signUpPassword2"]) {
				$message = "Les mots de passe sont différents";
			}
			else {
				$isRegistrationSuccessful = $this->database->addUser($_POST["signUpLogin"], $_POST["signUpPassword"]);

				if ($isRegistrationSuccessful === true) {
					$this->setView(getViewByName("Message"));
					$this->getView()->setMessage("Enregistrement effectué.");
					return;
				}
				else {
					$message = $isRegistrationSuccessful;
				}
			}
		}
		else {
			$message = "Des données sont manquantes.";
		}
		$this->setSignUpFormView($message, "alert-error");
	}

	private function setSignUpFormView($message, $style = "") {
		$this->setView(getViewByName("SignUpForm"));
		$this->getView()->setMessage($message, $style);
	}

}


?>
