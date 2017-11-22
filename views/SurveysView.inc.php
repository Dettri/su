<?php
require_once("views/View.inc.php");

class SurveysView extends View {

	private $surveys;

	public function displayBody() {

		if (count($this->surveys)===0) {
			echo '<div class="container"><br><br><br><br><div style="text-align:center" class="alert"></div></div>';
			return;
		}

		require("templates/surveys.inc.php");
	}


	 public function setSurveys($surveys) {
	 	 $this->surveys = $surveys;
	 }
	
}
?>
