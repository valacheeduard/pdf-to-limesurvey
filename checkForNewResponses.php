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