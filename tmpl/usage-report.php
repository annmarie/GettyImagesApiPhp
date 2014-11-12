<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<meta charset="UTF-8">
<title>GettyImages Usage Report</title>

<link rel="stylesheet" type="text/css" href="/css/GettyImages.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="/js/GettyImages.js"></script>

<script>

// start document ready
$(document).ready(function () {
    var httpHost = window.location.host;
    var usageApiUrl = 'https://'+httpHost+'<?php echo $tpl->usageApiUrl; ?>';

    console.log("usageApiUrl: " + usageApiUrl);
    (new GettyImages.UsageReport).LoadOnReady(usageApiUrl);

}); // end document ready

</script>

</head>
<body>

<div id="mainContent">

    <h1>GettyImages Usage Report</h1>

    <div id="usage_report_form">
     <input type="text" id="usage_report_year" name="year" value="<?php echo $tpl->year; ?>">
     <input type="text" id="usage_report_month" name="month" value="<?php echo $tpl->month; ?>">
     <input type="button" id="usage_report_submit" name="usage_report_submit"  onmouseover="this.className='btnhov'" onmouseout="this.className=''" value="Send Usage Report to Getty">
    </div>

    <div id="usage_report_results"></div>

    <div id="usage_report_logdata"></div>

</div>

</body>
</html>

