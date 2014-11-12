<?php
/*
* Version: 1.0
*/
ini_set('memory_limit', '640M');
require_once("config.php");
require_once("GettyImages.php");

if (!headers_sent()) {
    header('Content-type: application/json; charset=utf-8');
    header('Cache-Control: max-age=0, s-maxage=0, store, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
}

$req = new GettyImages\ReqData($_REQUEST);
$tpl = new GettyImages\SaveFile($req, $getty_auth, $imgDir);
echo json_encode($tpl);

exit();

?>

