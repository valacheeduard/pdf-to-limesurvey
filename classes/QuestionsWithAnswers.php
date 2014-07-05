<?php 



class QuestionsWithAnswers{

	public $qid,$parent_qid,$gid,$type,$title,$question,$answer;
	private $answerIdentifier;
	public $comment;

	public function __construct(){
		$this->question = strip_tags($this->question);
	}

	public function getAllDetails(){
		echo 'qid = ' . ($this->qid) , '<br/>';
		echo 'parent_qid =' . $this->parent_qid, '<br/>';
		echo 'gid =' . ($this->gid), '<br/>';
		echo 'title = ' . $this->title, '<br/>';
		echo 'type = ' . $this->type,  '<br/>';
		echo 'question = ' . $this->question, '<br/>';
		echo 'answer = ' . $this->answer,'<br/> <br/>';

	}


	public function setAllAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions){

		if(($this->type == 'S')||($this->type == 'N')||($this->type == 'D')||($this->type == 'T')||($this->type == 'U')){
			self::setSNDTUtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions);
		}

		if($this->type == 'M'){
			self::setMtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions);
		}

		if((($this->type == 'K') || ($this->type == 'Q'))&&($this->parent_qid == 0)){
			self::setKQtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions);
		}

		if(($this->type == 'Y')||($this->type == 'X')){
			self::setYXtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions);	
		}

		if (($this->type == ';') && ($this->parent_qid == 0)) {

		}

		if(($this->type == '!')||($this->type == "O")||($this->type == "L")){
			self::setOLandExclamationMarkTypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions);
		}

	}


	private function setSNDTUtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions){
		$this->answerIdentifier = '967128X' . $this->gid  . 'X' . $this->qid;
		
		foreach ($arrayOfAnswers as $key1 => $value1) {
			if($key1 == $this->answerIdentifier)
				{
					$this->answer = $this->answer . $value1;
				}
			}
	}

	private function setMtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions){

			$variantsPossible = array();
			$variantsChecked = array();
			foreach ($arrayOfQuestions as $arr){
				if($arr->parent_qid == $this->qid)
				{
					array_push($variantsPossible, $arr->question);
				}
			}

			$answerVariantIndentifiers = '967128X' . $this->gid  . 'X' . $this->qid;
			

				foreach ($arrayOfAnswers as $key => $value) {
					if(substr($key, 0,strlen($answerVariantIndentifiers)) == $answerVariantIndentifiers)
						if($value == NULL){
							array_push($variantsChecked, 0);
						}
						else{
							array_push($variantsChecked,1);
						}
					}


			for ($i=0; $i < count($variantsPossible); $i++) { 
				if($variantsChecked[$i] == 1){
					$this->answer = $this->answer . $variantsPossible[$i] ." " ;
				}
			}	


	}

	private function setKQtypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions){
			
			$answerVariantIndentifier = '967128X' . $this->gid  . 'X' . $this->qid;
			foreach ($arrayOfQuestions as $arr){
				if($arr->parent_qid == $this->qid)
				{
					$answerVariantIndentifier = '967128X' . $arr->gid  . 'X' . $arr->parent_qid . $arr->title;
					foreach ($arrayOfAnswers as $key => $value) {

						if($key == $answerVariantIndentifier)
						{
							$this->answer = $this->answer . $arr->question . " ";
							$this->answer = $this->answer . $value;
							$this->answer = $this->answer . '<br/>';
						}
					}
					
				}
			}


	}

	private function setYXtypeAnswers($personNumber, $arrayOfAnswers,$arrayOfQuestions){
		//$this->answer = $arrayOfAnswers[0][$this->answerIdentifier];

			$this->answerIdentifier = '967128X' . $this->gid  . 'X' . $this->qid;
				foreach ($arrayOfAnswers as $key1 => $value1) {
					if($key1 == $this->answerIdentifier)
					{
						if($value1 != NULL){
							$this->answer = 'Yes';
						}else{
							$this->answer = 'No';
						}
					}
				}

	}

	private function setOLandExclamationMarkTypeAnswers($personNumber,$arrayOfAnswers,$arrayOfQuestions){
					$this->answerIdentifier = '967128X' . $this->gid . 'X'.$this->qid;

			$listOfAnswer= array();
			$answerComment = NULL; //if exists

			switch ($this->qid) {
				case 379:  //Question is : title
					$listOfAnswer = array("Mr.","Mrs.","Miss.","Ms.","Dr.");
					break;

				case 405: //experience
					$listOfAnswer = array("None","Less than 6 months","6 months - 2 years", "2 - 5 years", "5 years and above" );
					break;

				case 440: // when to approach
					$listOfAnswer = array("Before job offer made","After job offer made");
					break;

				case 429: //gender
					$listOfAnswer = array("Male","Female","Transsexual","Undergone, or undergoing, male to female gender reassignment","Undergone, or undergoing, male to female gender reassignment"," Prefer not to say");
					break;

				case 442: // marital status
					$listOfAnswer = array("Married","Single","In a civil partnership","Perefer not to say",);
					break;

				case 430: // ethnic group
					$listOfAnswer = array("White English","White Scottish","White Welsh","White Irish","White British","White Other (please specify)","Mixed - White and Black Caribbean","Mixed - White & Black African","Mixed - White & Black British","Mixed - White & Asian","Mixed - Other (please specify)","Indian","Pakistani","Bangladeshi","Asian British","Asian Other (please specify)","Black Caribbean","Black African","Black British","Black Other (please specify)","Chinese","Any other background (please specify)");
					break;

				case 443: //religion
					$listOfAnswer = array("Christian (please specify which denomination)","Jewish","Sikh","Muslim","Hindu","Buddhist","Rastafarian","Baha’i faith","Shinto","Chinese folk religion","Non-religious/non-believer","Prefer not to say");
					$answerComment = 1; //exists
					break;

				case 378: //learned of this vacancy
					$listOfAnswer = array("Company facebook","Company website", "Company Linkedin","BAJR","JIS","Internal","Referral");
					break;

				case 416:
					$listOfAnswer = array('Provisional',"Full","PSV","No answer");
				default:
					#
					break;
			}

				foreach ($arrayOfAnswers as $key1 => $value1) {
					if($key1 == $this->answerIdentifier)
					{
						$selection = $value1;
						$selection = str_replace("A", "", $selection);
						$this->answer = $listOfAnswer[(int)$selection - 1];		
					}

					if($answerComment == 1) //exists	
					{
						$commentIdentifier = $this->answerIdentifier . "comment";
						if($key1 == $commentIdentifier){
							$this->comment = $value1;
							echo $this->comment;
						}
					}	
				}
		
		}

}

 ?>