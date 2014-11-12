<?php
require_once('config.php');
require_once('GettyImages.php');

$gettyAPI = new GettyImages\ApiCalls($getty_auth);

$stats = Array();
$stats[] =  array(
    "asset_id" => '455900688',
    "quantity" => 1,
    "usage_date" => "YYYY-MM-DD"
);

$resp = $gettyAPI->makeUsageBatches($stats);
$payload = new GettyImages\CallResp($resp);

echo "status: $payload->status" . PHP_EOL;
print_r($payload->body) . PHP_EOL;

?>

