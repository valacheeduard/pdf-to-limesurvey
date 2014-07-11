<?php

require_once 'header.php';

function writeFile($name,$content){
    $file = $name . ".html";
    file_put_contents("htmlQueries/". $file,$content);
}

function writeHtmlFiles($db,$numberOfEntries,$arrayOfAnswersArray){

    for ($i=0; $i < $numberOfEntries; $i++) {

        $QuestionsArray = getQuestionsArray($db);

        foreach ($QuestionsArray as $arr ) {
            $arr->setAllAnswers($i,$arrayOfAnswersArray[$i],$QuestionsArray);
        }
            
        $T = new tpl;

        $htmlRepresentationForAll = "";

        foreach ($QuestionsArray as $arr){
            if($arr->parent_qid == 0){
                $htmlRepresentationForAll = $htmlRepresentationForAll . $arr->htmlRepresentation;
                $T->assign("htmlRepresentation",$htmlRepresentationForAll);
            }
        }


    $aux = $T->fetch('example.php');
    $dateString = date('m.d.y');
    writeFile($dateString . $i,$aux);

    }
}


function getQuestionsArray($db)  {

    $db->query("SELECT * FROM  `lime_questions` ORDER BY  `lime_questions`.`gid` ASC LIMIT 0 , 700");
    $QuestionsArray = array();

    while($db->nextRow()){
     array_push($QuestionsArray, $db->getObject('QuestionsWithAnswers'));
     //echo '<pre>' . print_r($db->getObject('QuestionsWithAnswers')) . '</pre><br>';
    }




    return $QuestionsArray;
}

function getAnswerArray($db,$personNumber,&$numberOfEntries){

    $AnswersArray = array();

    $date = date('Y-m-d H:i:s');
    $date24 = date("Y-m-d H:m:s", strtotime('-24 hours', time()));

    $db->query("SELECT * FROM `lime_survey_967128` WHERE submitdate BETWEEN '2014-06-11 00:00:00' AND '2014-6-29 15:32:00'");

    while($db->nextRow()){
        $AnswersArray[] = $db->getRow();
    }

    $numberOfEntries = count($AnswersArray);
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