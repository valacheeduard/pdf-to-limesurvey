<?php

require_once 'header.php';

function writeFile($name,$content){
    $file = $name . ".html";
    file_put_contents("htmlQueries/". $file,$content);
}

function writeHtmlFiles($numberOfEntries,$QuestionsArray,$arrayOfAnswersArray){

    for ($i=0; $i < $numberOfEntries; $i++) { 
        $QuestionsArray = getQuestionsArray();

        foreach ($QuestionsArray as $arr ) {
            $arr->setAllAnswers($i,$arrayOfAnswersArray[$i],$QuestionsArray);
        }
            
        $T = new tpl;

        foreach ($QuestionsArray as $arr){
            switch ($arr->qid) {
                case 380:
                    $T->assign('personal_surname',$arr->answer);
                    break;
                case 381:
                    $T->assign('personal_forename',$arr->answer);
                    break;
                case 382:
                    $arr->answer = str_replace('Street first line', "Street: ",$arr->answer);
                    $arr->answer = str_replace('<br/>Street second line', ", ", $arr->answer);
                    $arr->answer = str_replace('<br/>', ';  ', $arr->answer);
                    $T->assign('personal_address',$arr->answer);
                    break;
                case 377:
                    $T->assign('position_applied_for',$arr->answer);
                    break;

                case 378:
                    $T->assign('learn_vacancy',$arr->answer);
                    break;

                case 384:
                    $arr->answer = str_replace('<br/>', "; " , $arr->answer);
                    $arr->answer = str_replace('.0000000000',"",$arr->answer);
                    $T->assign('personal_telephone',$arr->answer);
                    break;

                case 385:
                    $arr->answer = str_replace('<br/>', "", $arr->answer);
                    $arr->answer = str_replace('.0000000000',"",$arr->answer);
                    $T->assign('personal_mobile',$arr->answer);
                    break;

                case 383:
                    $T->assign('personal_email',$arr->answer);
                    break;

                case 409:
                    $T->assign('personal_insurance_number',$arr->answer);
                    break;

                case 410:
                    $T->assign('nationality',$arr->answer);
                    break;

                case 411:
                    $T->assign('own_a_car',$arr->answer);
                    break;

                case 414:
                    $T->assign('classes_entitled_drive',$arr->answer);
                    break;

                case 405:
                    $T->assign("level_of_experience",$arr->answer);
                    $T->assign("level_of_experience_comment",$arr->comment);

                case 406:
                    $T->assign('voluntary_work',$arr->answer);
                    break;



                case 424:
                    $T->assign('continue_to_work',$arr->answer);
                    break;

                case 407:
                    $T->assign('convicted',$arr->answer);
                    break;

                case 403:
                    $T->assign('recomandation1',$arr->answer);
                    break;

                case 417:
                    $T->assign('disabilities_details',$arr->answer);
                    break;

                case 425:
                    $T->assign('recomandation2',$arr->answer);
                    break;

                case 426:
                    $T->assign('supplementary',$arr->answer);
                    break;

                case 428:
                    $arr->answer = strstr($arr->answer,'Date of birth:');
                    $arr->answer = str_replace("Date of birth:", "", $arr->answer);
                    $T->assign('birthdate',$arr->answer);
                    break;

                case 429:
                    $T->assign('gender',$arr->answer);
                    break;

                case 430:
                    $T->assign('ethnic_group',$arr->answer);
                    break;

                case 388:
                    $T->assign('endorsements',$arr->answer);
                    break;
                case 395:
                    $T->assign('foreign_languages',$arr->answer);
                    break;

                case 432:
                    $T->assign('disabilities',$arr->answer);
                    break;

                case 433:
                    $T->assign('reasonable_adjustments_1',$arr->answer);
                    break;

                case 434:
                    $T->assign('reasonable_adjustments_2',$arr->answer);
                    break;

                case 437:
                    $arr->answer = str_replace(' ',", ",$arr->answer);
                    $T->assign('wich_office',$arr->answer);
                    break;

                case 444;
                    $T->assign('consent',$arr->answer);
                    break;
            }
        }


    $aux = $T->fetch('example.php');
    writeFile("ceva-".$i,$aux);
    }
}


function getQuestionsArray()  {
    try{
        $handler = new PDO('mysql:host=localhost;dbname=localhost_limesurvey','root','');
        $handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
        echo $e->getMessage();
        die();
    }

    $query = $handler->query("SELECT * FROM  `lime_questions` ORDER BY  `lime_questions`.`gid` ASC LIMIT 0 , 700");

    $query->setFetchMode(PDO::FETCH_CLASS,'QuestionsWithAnswers');

    $QuestionsArray = array();

    while($r = $query->fetch()){
            array_push($QuestionsArray, $r);
    }

    return $QuestionsArray;
}

function getAnswerArray($personNumber,&$numberOfEntries){
        try{
        $handler = new PDO('mysql:host=localhost;dbname=localhost_limesurvey','root','');
        $handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
        echo $e->getMessage();
        die();
    }

    $date = date('Y-m-d H:i:s');
    $date24 = date("Y-m-d H:m:s", strtotime('-24 hours', time()));


    $query = $handler->query("SELECT * FROM `lime_survey_967128`
                                WHERE submitdate BETWEEN '2014-06-11 00:00:00' AND '2014-6-29 15:32:00'");

    $AnswersArray = array();
    $times = 0;

    while($r = $query->fetch(PDO::FETCH_ASSOC)){
            $AnswersArray[]=$r;
    }

    $numberOfEntries = count($AnswersArray);

    // echo $AnswersArray[0]['967128X23X378'];
     //echo '<pre>' , print_r($AnswersArray), '</pre>';

    return $AnswersArray[$personNumber];   
}


function autoload($class_name) {
	if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
		$path = 'classes/'.$class_name.'.php';
	}else{
 		$path = $_SERVER['DOCUMENT_ROOT'].DOCROOT.'classes/'.$class_name.'.php';
	}
	if(file_exists($path)){
		include_once $path;
	}
	else{
		echo "<b>ERROR: </b> File ".$path." could not be found. Check the path.";
		exit;
	}
}
spl_autoload_register("autoload");

function sendEmail($emailAddress,$content,$htmlContent=null,$subject=null,$hideAddresses = false,$header=null,$footer=null,$replyTo = ''){
    $addresses = explode(';',$emailAddress);
    $pattern = '/^([A-Za-z0-9])(([\\-]|[\.]|[_]+)?([A-Za-z0-9]+))*(@)([A-Za-z0-9])((([-]+)?([A-Za-z0-9]+))?)*((.[A-Za-z]{2,3})?(.[A-Za-z]{2,6}))$/';
    foreach($addresses as $index => $email){
        $email = trim($email);
        if(preg_match($pattern,$email)==0){
            array_splice($addresses,$index,1);
        }
    }
    if(count($addresses) == 0){
        return null;//invalid e-mail address(es)
    }
	if($subject === null){
		$subject = 'Automatic email';
	}
	if($header === null){
		$header = 'This is an automated e-mail report from '.EMAIL_FROM.'. ';
	}
	if($footer === null){
		$footer = 'Regards, ';
        $content .= "\n\n".$footer;
	}
    $content = $header."\n\n".$content;
    if($htmlContent === null){
        $htmlContent = nl2br($content);
    }
    $random_hash = md5(date('r', time()));
    $message = '
--PHP-alt-'.$random_hash.'
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 7bit

'.$content.'

--PHP-alt-'.$random_hash.'
Content-Type: text/html; charset="UTF-8"
Content-Transfer-Encoding: 7bit

'.$htmlContent.'

--PHP-alt-'.$random_hash.'--';

    //using PEAR:Mail on windows servers
    require_once "Mail.php";

    $headersTo = implode(',',$addresses);
    $sendTo = $headersTo;
    if($hideAddresses){
        $sendTo = implode(',',$addresses);
        $headersTo = 'Undisclosed recipients';
    }
    $headers = array ('Content-Type' => 'multipart/alternative; boundary="PHP-alt-'.$random_hash.'"',
        'From' => EMAIL_FROM,
        'To' => $headersTo,
        'Reply-To' => $replyTo,
        'Subject' => $subject);
    $smtp = Mail::factory('smtp',
        array ('host' => EMAIL_HOST,
            'port' => EMAIL_PORT,
            'auth' => EMAIL_AUTH,
            'username' => EMAIL_USR,
            'password' => EMAIL_PASS));
    $mail = $smtp->send($sendTo, $headers, $message);

    if(PEAR::isError($mail)){
        echo '<p>'.$mail->getMessage().'</p>';
    };
    return !PEAR::isError($mail);
}

?>