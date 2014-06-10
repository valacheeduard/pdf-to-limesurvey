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
$db->query('SELECT * FROM lime_surveys');
while($db->nextRow()){
    echo $db->getElement('sid').'<br/>';

}


/**
 * Exemplu de trimis e-mail cu atasament
 */
//echo $list;
$list = chunk_split(base64_encode($list));
$messageHtml = '<html><head>
    <title>List of Subscribers</title>
    </head><body>Find attached the list of subscribers since '.$date.'.</body></html>';

$uid = md5(uniqid(time()));
$header = "From: TwoTen <hello@twoten.is>\r\n";
$header .= "MIME-Version: 1.0\r\n";
$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
$header .= "This is a multi-part message in MIME format.\r\n";
$header .= "--".$uid."\r\n";
$header .= "Content-type:text/html; charset=iso-8859-1\r\n";
$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$header .= $messageHtml."\r\n\r\n";
$header .= "--".$uid."\r\n";
$header .= "Content-Type: text/csv; name=\"subscribers.csv\"\r\n";
$header .= "Content-Transfer-Encoding: base64\r\n";
$header .= "Content-Disposition: attachment; filename=\"subscribers.csv\"\r\n\r\n";
$header .= $list."\r\n\r\n";
$header .= "--".$uid."--";

sendEmail('lucian.pricop@yahoo.co.uk','List of subscribers','',$messageHtml,$header);