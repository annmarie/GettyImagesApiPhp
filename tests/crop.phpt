--TEST--
test crop request 
--FILE--
<?php
ini_set('memory_limit', '640M');
require_once('config.php');
require_once('GettyImages.php');
$_REQUEST = array( 
    "image_id" => "457764814", 
    "x" => 0, 
    "y" => 0, 
    "h" => 970, 
    "w" => 450, 
    "img_w" => 970, 
    "img_h" => 647
);
$dir = dirname(__FILE__);
$req = new GettyImages\ReqData($_REQUEST);
$res = new GettyImages\Crop($req, $getty_auth, $dir);
echo $res->status;
?>
--CLEAN--
<?php
require_once('config.php');
require_once('GettyImages.php');
$_REQUEST = array("image_id" => '457764814');
$dir = dirname(__FILE__);
$req = new GettyImages\ReqData($_REQUEST);
$imageFile = new GettyImages\ImageFile($req, $dir);
unlink($imageFile->filepath);
?>
--EXPECT--
200

