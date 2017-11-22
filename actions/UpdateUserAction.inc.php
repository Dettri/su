<?php

require_once("actions/Action.inc.php");

class UpdateUserAction extends Action {

	/**
	* Met à jour le mot de passe de l'utilisateur en procédant de la façon suivante :
	*
	* Si toutes les données du formulaire de modification de profil ont été postées
	* ($_POST['updatePassword'] et $_POST['updatePassword2']), on vérifie que
	* le mot de passe et la confirmation sont identiques.
	* S'ils le sont, on modifie le compte avec les méthodes de la classe 'Database'.
	*
	* Si une erreur se produit, le formulaire de modification de mot de passe
	* est affiché à nouveau avec un message d'erreur.
	*
	* Si aucune erreur n'est détectée, le message 'Modification enregistrée.'
	* est affiché à l'utilisateur.
	*
	* @see Action::run()
	*/
	public function run() {
		$user = $this->getSessionLogin();

		if (empty($_POST["updatePassword"])||empty($_POST["updatePassword2"])) {
			$message="Données manquantes";
		}
		else if($_POST["updatePassword"] == $_POST["updatePassword2"] && (!empty($_POST["updatePassword"]) && !empty($_POST["updatePassword2"])))
		{
			if($this->database->checkPassword($user,$_POST["updatePassword"]))
			{
				$message = "Le nouveau mot de passe est le même que l'ancien";
			}
			else
			{
				$message = $this->database->updateUser($user,$_POST["updatePassword"]);
			}
		}
		else
		{
			$message = "Les deux mots de passes rentrés sont différents";
		}

		$this->setUpdateUserFormView($message);
	}

	private function setUpdateUserFormView($message) {
		$this->setView(getViewByName("UpdateUserForm"));
		$this->getView()->setMessage($message, "alert-error");
	}

}

?>
