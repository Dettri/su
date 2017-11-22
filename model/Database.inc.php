<?php

class Database {

	private $connection;

	/**
	 * Ouvre la base de données. Si la base n'existe pas elle
	 * est créée à l'aide de la méthode createDataBase().
	 */
	public function __construct() {
		$dbHost = "localhost";
		$dbBd = "sondages";
		$dbPass = "";
		$dbLogin = "root";
		$url = 'mysql:host='.$dbHost.';dbname='.$dbBd;
		//$url = 'sqlite:database.sqlite';
		$this->connection = new PDO($url, $dbLogin, $dbPass);
		if (!$this->connection) die("impossible d'ouvrir la base de données");
		$this->createDataBase();
	}


	/**
	 * Initialise la base de données ouverte dans la variable $connection.
	 * Cette méthode crée, si elles n'existent pas, les trois tables :
	 * - une table users(nickname char(20), password char(50));
	 * - une table surveys(id integer primary key autoincrement,
	 *						owner char(20), question char(255));
	 * - une table responses(id integer primary key autoincrement,
	 *		id_survey integer,
	 *		title char(255),
	 *		count integer);
	 */
	private function createDataBase() {
		$this->connection->exec(
			"CREATE TABLE IF NOT EXISTS users(
				nickname VARCHAR(20) NOT NULL PRIMARY KEY,
				password VARCHAR(50) NOT NULL);

			CREATE TABLE IF NOT EXISTS surveys(
				id INT AUTO_INCREMENT PRIMARY KEY,
				owner VARCHAR(20) NOT NULL,
				question VARCHAR(255) NOT NULL);

			CREATE TABLE IF NOT EXISTS responses(
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_survey INT NOT NULL,
				title VARCHAR(255) NOT NULL,
				count INT NOT NULL);

			CREATE TABLE IF NOT EXISTS comments(
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_survey INT NOT NULL,
				owner VARCHAR(20) NOT NULL,
				message VARCHAR(255) NOT NULL);"
		);
	}

	/**
	 * Vérifie si un pseudonyme est valide, c'est-à-dire,
	 * s'il contient entre 3 et 10 caractères et uniquement des lettres.
	 *
	 * @param string $nickname Pseudonyme à vérifier.
	 * @return boolean True si le pseudonyme est valide, false sinon.
	 */
	private function checkNicknameValidity($nickname) {
		if (ctype_alpha($nickname) && strlen($nickname) >= 3 && strlen($nickname) <= 10) {
			return true;
		}
		return false;
	}

	/**
	 * Vérifie si un mot de passe est valide, c'est-à-dire,
	 * s'il contient entre 3 et 10 caractères.
	 *
	 * @param string $password Mot de passe à vérifier.
	 * @return boolean True si le mot de passe est valide, false sinon.
	 */
	private function checkPasswordValidity($password) {
		if (strlen($password) >= 3 && strlen($password) <= 10) {
			return true;
		}
		return false;
	}

	/**
	 * Vérifie la disponibilité d'un pseudonyme.
	 *
	 * @param string $nickname Pseudonyme à vérifier.
	 * @return boolean True si le pseudonyme est disponible, false sinon.
	 */
	private function checkNicknameAvailability($nickname) {
		$verif = $this->connection->prepare("SELECT * FROM users WHERE nickname = :nickname");
		$verif->execute(array(':nickname' => $nickname));
		$boolValue = $verif->rowCount() == 0 ? true : false;

		return $boolValue;
	}

	/**
	 * Vérifie qu'un couple (pseudonyme, mot de passe) est correct.
	 *
	 * @param string $nickname Pseudonyme.
	 * @param string $password Mot de passe.
	 * @return boolean True si le couple est correct, false sinon.
	 */
	public function checkPassword($nickname, $password) {
		$verif = $this->connection->prepare("SELECT * FROM users WHERE nickname = :nickname AND password = :password");
		$verif->execute(array(':nickname' => $nickname, ':password' => hash("sha1", $password)));
		$boolValue = $verif->rowCount() == 1 ? true : false;

		return $boolValue;
	}

	/**
	 * Ajoute un nouveau compte utilisateur si le pseudonyme est valide et disponible et
	 * si le mot de passe est valide. La méthode peut retourner un des messages d'erreur qui suivent :
	 * - "Le pseudo doit contenir entre 3 et 10 lettres.";
	 * - "Le mot de passe doit contenir entre 3 et 10 caractères.";
	 * - "Le pseudo existe déjà.".
	 *
	 * @param string $nickname Pseudonyme.
	 * @param string $password Mot de passe.
	 * @return boolean|string True si le couple a été ajouté avec succès, un message d'erreur sinon.
	 */
	public function addUser($nickname, $password) {
	  if (!$this->checkNicknameValidity($nickname)) {
	  	return "Le pseudo doit contenir 3 à 10 lettres.";
	  }
	  elseif (!$this->checkPasswordValidity($password)) {
	  	return "Le mot de passe doit contenir 3 à 10 caractères.";
	  }
	  elseif (!$this->checkNicknameAvailability($nickname)) {
	  	return "Le pseudo existe déjà.";
	  }

	  $hash = hash("sha1", $password);
	  $query = $this->connection->prepare("INSERT INTO users(nickname, password) VALUES(:nickname, :password)");
	  $query->execute(array(
		  "nickname" => $nickname,
		  "password" => $hash
	  ));

	  return true;
	}

	/**
	 * Change le mot de passe d'un utilisateur.
	 * La fonction vérifie si le mot de passe est valide. S'il ne l'est pas,
	 * la fonction retourne le texte 'Le mot de passe doit contenir entre 3 et 10 caractères.'.
	 * Sinon, le mot de passe est modifié en base de données et la fonction retourne true.
	 *
	 * @param string $nickname Pseudonyme de l'utilisateur.
	 * @param string $password Nouveau mot de passe.
	 * @return boolean|string True si le mot de passe a été modifié, un message d'erreur sinon.
	 */
	public function updateUser($nickname, $password) {
		if (!$this->checkPasswordValidity($password)) {
			return "Le mot de passe doit contenir 3 à 10 caractères.";
		}

		$query = $this->connection->prepare("UPDATE users SET password = :password where nickname = :nickname");
		$query->execute(array(
			"password" => hash("sha1", $password),
			"nickname" => $nickname
		));
		return "Mot de passe modifié avec succès.";
	}

	/**
	 * Sauvegarde un sondage dans la base de donnée et met à jour les indentifiants
	 * du sondage et des réponses.
	 *
	 * @param Survey $survey Sondage à sauvegarder.
	 * @return boolean True si la sauvegarde a été réalisée avec succès, false sinon.
	 */
	public function saveSurvey($survey) {
		$query = $this->connection->prepare("INSERT INTO surveys(owner, question) VALUES(:owner, :question)");
		$query->execute(array(
			"owner" => $survey->getOwner(),
			"question" => $survey->getQuestion()
		));
		$survey->setId($this->connection->lastInsertId());

		foreach ($survey->getResponses() as $response) {
			$this->saveResponse($response);
		}

		return true;
	}

	public function updateSurvey($survey) {
		$query = $this->connection->prepare("UPDATE surveys SET question = :question where id = :id");
		$query->execute(array(
			":question" => $survey->getQuestion(),
			":id" => $survey->getId()
		));
		foreach ($survey->getResponses() as $response) {
			$this->saveResponse($response);
		}
		return "Merci, nous avons edité votre sondage.";
	}

	/**
	 * Sauvegarde une réponse dans la base de donnée et met à jour son indentifiant.
	 *
	 * @param Response $response Réponse à sauvegarder.
	 * @return boolean True si la sauvegarde a été réalisée avec succès, false sinon.
	 */
	private function saveResponse($response) {
		$query = $this->connection->prepare("INSERT INTO responses(id_survey, title, count) VALUES(:id_survey, :title, :count)");
		$query->execute(array(
			"id_survey" => $response->getSurvey()->getId(),
			"title" => $response->getTitle(),
			"count" => $response->getCount()
		));

		return true;
	}

	/**
	 * Charge l'ensemble des sondages créés.
	 *
	 * @return array(Survey)|boolean Sondages trouvés par la fonction ou false si une erreur s'est produite.
	 */
	public function loadAllSurveys() {
		$result = $this->connection->prepare("SELECT * FROM surveys");
		$result->execute();
		return $this->loadSurveys($result);
	}

	/**
	 * Charge l'ensemble des sondages créés par un utilisateur.
	 *
	 * @param string $owner Pseudonyme de l'utilisateur.
	 * @return array(Survey)|boolean Sondages trouvés par la fonction ou false si une erreur s'est produite.
	 */
	public function loadSurveysByOwner($owner) {
		$result = $this->connection->prepare("SELECT * FROM surveys WHERE owner = :owner");
		$result->execute(array(':owner' => $owner));
		//Si la requête est vide, on retourne un tableau vide
		$surveys = [];
		if($result->rowCount() == 0)
			return $surveys;

		foreach ($result as $row) {
			$resultResponse = $this->connection->prepare("SELECT * FROM responses WHERE id_survey = :id_survey");
			$resultResponse->execute(array(':id_survey' => $row["id"]));

			$survey = new Survey($row["owner"], $row["question"]);
			$survey->setId($row["id"]);
			$survey->setResponses($this->loadResponses($survey, $resultResponse->fetchAll()));
			$surveys[] = $survey;
		}
		return $surveys;
	}

	/**
	 * Charge l'ensemble des sondages dont la question contient un mot clé.
	 *
	 * @param string $keyword Mot clé à chercher.
	 * @return array(Survey)|boolean Sondages trouvés par la fonction ou false si une erreur s'est produite.
	 */
	public function loadSurveysByKeyword($keyword) {
		$result = $this->connection->prepare("SELECT * FROM surveys WHERE question LIKE :keyword");
		$result->execute(array(':keyword' => '%'.$keyword.'%'));
		//Si la requête est vide, on retourne un tableau vide
		$surveys = [];
		var_dump($result);
		if($result->rowCount() == 0)
			return $surveys;

		foreach ($result as $row) {
			$resultResponse = $this->connection->prepare("SELECT * FROM responses WHERE id_survey = :id_survey");
			$resultResponse->execute(array(':id_survey' => $row["id"]));

			$survey = new Survey($row["owner"], $row["question"]);
			$survey->setId($row["id"]);
			$survey->setResponses($this->loadResponses($survey, $resultResponse->fetchAll()));
			$surveys[] = $survey;
		}
		return $surveys;
	}

	/**
	 * Charge le sondage trouvé grâce à l'id
	 *
	 * @param int $id Id de l'article à renvoyer.
	 * @return Survey|boolean Sondage trouvé par la fonction ou false si une erreur s'est produite.
	 */
	public function loadSurvey($id) {
		$result = $this->connection->prepare("SELECT * FROM surveys WHERE id = :id");
		$result->execute(array(':id' => $id));
		//Si la requête est vide, on retourne un tableau vide
		$surveys = [];
		if($result->rowCount() == 0)
			return $surveys;

		$row = $result->fetch();
		$survey = new Survey($row["owner"], $row["question"]);
		$survey->setId($row["id"]);

		$resultResponse = $this->connection->prepare("SELECT * FROM responses WHERE id_survey = :id_survey");
		$resultResponse->execute(array(':id_survey' => $row["id"]));
		$survey->setResponses($this->loadResponses($survey, $resultResponse->fetchAll()));

		$resultComment = $this->connection->prepare("SELECT * FROM comments WHERE id_survey = :id_survey");
		$resultComment->execute(array(':id_survey' => $row["id"]));
		$survey->setComments($this->loadComments($survey, $resultComment->fetchAll()));

		$surveys[] = $survey;
		return $surveys;
	}

	/**
	 * Supprime un sondage en fonction de l'id $id.
	 *
	 * @param int $id Identifiant du sondage.
	 * @param string $owner Propriétaire du sondage.
	 * @return boolean True si le sondage a été supprimé, false sinon.
	 */
	public function deleteSurvey($id, $owner) {
		$result = $this->connection->prepare("DELETE FROM surveys WHERE id = :id_survey AND owner = :owner");
		$result->execute(array(':id_survey' => $id, ':owner' => $owner));
		$bool = $result->rowCount() != 0 ? true : false;
		return $bool;
	}

	/**
	 * Enregistre le vote d'un utilisateur pour la réponse d'identifiant $id.
	 *
	 * @param int $id Identifiant de la réponse.
	 * @return boolean True si le vote a été enregistré, false sinon.
	 */
	public function vote($id) {
		$result = $this->connection->prepare("SELECT * FROM responses WHERE id = :id");
		$result->execute(array(':id' => $id));
		$row = $result->fetch();
		$newCount = $row["count"] + 1;
		$result = $this->connection->prepare("UPDATE responses SET count = :count WHERE id = :id");
		$test = $result->execute(array(':count' => $newCount, ':id' => $id));
	}

	/**
	 * Ajoute un commentaire
	 *
	 * @param text $comment Message du commentaire
	 * @param text $pseudo Pseudo de l'auteur du message
	 * @param int $id Identifiant du sondage dans lequel rajouté le message.
	 * @return boolean True si le vote a été enregistré, false sinon.
	 */
	public function addComment($comment, $pseudo, $id)
	{
		$query = $this->connection->prepare("INSERT INTO comments(id_survey, owner, message) VALUES(:id_survey, :owner, :message)");
		$query->execute(array(
			"id_survey" => $id,
			"owner" => $pseudo,
			"message" => $comment
		));
		return true;
	}

	/**
	 * Construit un tableau de sondages à partir d'un tableau de ligne de la table 'surveys'.
	 * Ce tableau a été obtenu à l'aide de la méthode fetchAll() de PDO.
	 *
	 * @param array $arraySurveys Tableau de lignes.
	 * @return array(Survey)|boolean Le tableau de sondages ou false si une erreur s'est produite.
	 */
	private function loadSurveys($arraySurveys) {
		$surveys = array();

		if($arraySurveys->rowCount() == 0)
			return $surveys;

		foreach ($arraySurveys as $row) {
			$resultResponse = $this->connection->prepare("SELECT * FROM responses WHERE id_survey = :id_survey");
			$resultResponse->execute(array(':id_survey' => $row["id"]));

			$survey = new Survey($row["owner"], $row["question"]);
			$survey->setId($row["id"]);
			$survey->setResponses($this->loadResponses($survey, $resultResponse->fetchAll()));
			$surveys[] = $survey;
		}
		return $surveys;
	}

	/**
	 * Construit un tableau de réponses à partir d'un tableau de ligne de la table 'responses'.
	 * Ce tableau a été obtenu à l'aide de la méthode fetchAll() de PDO.
	 *
	 * @param Survey $survey Le sondage.
	 * @param array $arraySurveys Tableau de lignes.
	 * @return array(Response)|boolean Le tableau de réponses ou false si une erreur s'est produite.
	 */
	private function loadResponses($survey, $arrayResponses) {
		$responses = array();
		foreach ($arrayResponses as $responsePDO) {
			$response = new Response($survey, $responsePDO["title"], $responsePDO["count"]);
			$response->setId($responsePDO["id"]);
			$responses[] = $response;
		}
		return $responses;
	}

	/**
	 * Construit un tableau de comments à partir d'un tableau de ligne de la table 'comments'.
	 *
	 * @param Survey $survey Le sondage.
	 * @param array $arraySurveys Tableau de lignes.
	 * @return array(Response)|boolean Le tableau de commentaires ou false si une erreur s'est produite.
	 */
	private function loadComments($survey, $arrayResponses) {
		$responses = array();

		foreach ($arrayResponses as $responsePDO) {
			$response = new Comment($survey, $responsePDO["owner"], $responsePDO["message"]);
			$response->setId($responsePDO["id"]);
			$responses[] = $response;
		}
		return $responses;
	}

	public function deleteResponse($response)
	{
		$result = $this->connection->prepare("DELETE FROM responses WHERE id = :id");
		$result->execute(array(':id' => $response->getId()));
	}

}

?>
