--TEST--
test download api call
--FILE--
<?php
ini_set('memory_limit', '640M');
require_once('config.php');
require_once('GettyImages.php');
$_REQUEST = array("image_id" => '83454811');
$dir = dirname(__FILE__);
$req = new GettyImages\ReqData($_REQUEST);
$gettyAPI = new GettyImages\ApiCalls($getty_auth);
$imageFile = new GettyImages\ImageFile($req, $dir);
$resp = $gettyAPI->makeDownloadApiCall($req->image_id);
$payload = new GettyImages\CallResp($resp);
if ($payload->status === 200) {
    $imageFile->saveToFile($payload->body);
    $image = $gettyAPI->getImageInfo($req->image_id);
    unset($image['display_sizes']);
    print_r($image);
    if ($imageFile->saved) {
        echo "GettyImage $req->image_id succesfully Downloaded\n";
    } else {
        echo "There was a problem saving the file\n";
    }
} elseif ($payload->status === 404) {
    echo "Image not found.\n";
} else {
    echo "There was an unknown a problem {$status}\n";
}
?>
--CLEAN--
<?php
require_once('config.php');
require_once('GettyImages.php');
$_REQUEST = array("image_id" => '83454811');
$dir = dirname(__FILE__);
$req = new GettyImages\ReqData($_REQUEST);
$imageFile = new GettyImages\ImageFile($req, $dir);
unlink($imageFile->filepath);
?>
--EXPECT--


Array
(
    [id] => 83454811
    [artist] => Martin Poole
    [artist_title] => None
    [asset_family] => creative
    [caption] => 
    [copyright] => Martin Poole
    [credit_line] => Martin Poole
    [date_created] => 2008-11-27T00:00:00-08:00
    [date_submitted] => 2008-10-27T14:25:01-07:00
    [license_model] => RF
    [max_dimensions] => Array
        (
            [height] => 3541
            [width] => 4900
        )

    [title] => Kitten surrounded by feathers
)
GettyImage 83454811 succesfully Downloaded
