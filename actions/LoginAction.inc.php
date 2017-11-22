<?php

require_once("actions/Action.inc.php");

class LoginAction extends Action
{

    /**
     * Traite les données envoyées par le visiteur via le formulaire de connexion
     * (variables $_POST['nickname'] et $_POST['password']).
     * Le mot de passe est vérifié en utilisant les méthodes de la classe Database.
     * Si le mot de passe n'est pas correct, on affiche le message "Pseudo ou mot de passe incorrect."
     * Si la vérification est réussie, le pseudo est affecté à la variable de session.
     *
     * @see Action::run()
     */
    public function run()
    {
        $style = "alert-warning";
        if (!empty($_POST["nickname"]) && !empty($_POST["password"])) {
            if ($this->database->checkPassword($_POST["nickname"], $_POST["password"])) {
                $this->setSessionLogin($_POST["nickname"]);
                $message = "Connexion réussie.";
                $style = "alert-success";
            } else {
                $message = "Pseudo ou mot de passe incorrect.";
            }
        } else {
            $message = "Veuillez entrer votre pseudo et mot de passe.";
        }
        $this->setView(getViewByName("Message"));
        $this->getView()->setMessage($message, $style);
    }

    private function setLoginUpFormView($message, $style = "")
    {
        $this->setView(getViewByName("LoginUpForm"));
        $this->getView()->setMessage($message, $style);
    }

}
?>
