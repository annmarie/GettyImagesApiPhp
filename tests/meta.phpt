--TEST--
test meta api call
--FILE--
<?php
require_once('config.php');
require_once('GettyImages.php');
$gettyAPI = new GettyImages\ApiCalls($getty_auth);

echo "**********Images MetaData**********\n\n";
$imageIds = array('83454811');
$queryParams= Array('fields' => 'summary_set');
$images = $gettyAPI->getImagesInfo($imageIds, $queryParams);
foreach ($images as $image) {
    unset($image['download_sizes']);
}
print_r($images);

?>
--EXPECT--


**********Images MetaData**********

Array
(
    [0] => Array
        (
            [id] => 83454811
            [artist] => Martin Poole
            [asset_family] => creative
            [caption] => 
            [collection_code] => DV
            [collection_id] => 13
            [collection_name] => Digital Vision
            [license_model] => RF
            [max_dimensions] => Array
                (
                    [height] => 3541
                    [width] => 4900
                )

            [title] => Kitten surrounded by feathers
        )

)
