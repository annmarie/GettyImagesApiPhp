--TEST--
test search api call
--FILE--
<?php
require_once('config.php');
require_once('GettyImages.php');
$_REQUEST = array(
    "phrase" => "orange",
    "sort_order" => "newest",
    "image_family" => "creative", 
    "orientation" => "",
    "page_size" => 3,
    "page" => 1,
);
echo "********** Search Images **********\n";
$req = new GettyImages\ReqData($_REQUEST);
$out = new GettyImages\Search($req, $getty_auth);
$images = $out->images;
echo count($images);
echo "\n";
foreach ($out as $k => $v) { echo "{$k}\n"; }
?>
--EXPECT--

********** Search Images **********
3
result_count
images
prevpage
currentpage
lastpage
nextpage
hasSearchResults

