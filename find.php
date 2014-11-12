<?php
/*
* Version: 1.0
*/
require_once("config.php");
require_once('GettyImages.php');

if (!headers_sent()) {
    header('Content-type: application/json; charset=utf-8');
    header('Cache-Control: max-age=0, s-maxage=0, store, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Access-Control-Allow-Origin: *');
}

$req = new GettyImages\ReqData($_REQUEST);
$tpl = new GettyImages\Search($req, $getty_auth);
echo json_encode($tpl);

exit();

?>

