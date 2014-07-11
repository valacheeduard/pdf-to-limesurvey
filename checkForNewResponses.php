<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Lucian
 * Date: 10/06/14
 * Time: 13:03
 * To change this template use File | Settings | File Templates.
 */

require_once('header.php');

$db = new DB;


$QuestionsArray = getQuestionsArray($db);


$numberOfEntries; // number of entries in Query
getAnswerArray($db,0,$numberOfEntries); //call this function in order to get the Numbers of Entries

$arrayOfAnswersArray = array();

//for each arrayOfArrayAnswers get all answers of the $i entry
for ($i=0; $i < $numberOfEntries ; $i++) { 
	$arrayOfAnswersArray[$i] = getAnswerArray($db,$i,$numberOfEntries);
}

writeHtmlFiles($db,$numberOfEntries,$arrayOfAnswersArray);

?>