<!DOCTYPE html>
<?php
/*
* Version: 1.0
*/
require_once("config.php");
require_once('GettyImages.php');

$tpl = new GettyImages\ReqData($_REQUEST);

?>
<html>
<head>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta charset="UTF-8">
  <title>ImageTool</title>

<link rel="stylesheet" type="text/css" href="css/GettyImages.css">
<link rel="stylesheet" type="text/css" href="css/jquery.Jcrop.min.css">

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="js/jquery.Jcrop.min.js"></script>
<script src="js/GettyImages.js"></script>

<script>

// document ready
$(document).ready(function() {
    var httpHost = window.location.host;
    var imagetoolUrl = 'http://'+httpHost+'<?php echo $cropApiUrl; ?>';
    (new GettyImages.ImageTool).LoadOnReady(imagetoolUrl);
}); // end document ready

</script>

<head>
<body>
<div id="mainContent">

    <div id="croppedresults">
        <div id="toolbar" class="tophead">
            <div id="resetbutton" class="tophead">
                <a href="?image_id=<?php echo $tpl->image_id ?>"><button>reset</button></a>
            </div>
            <span id="imgdimentions" class="tophead"></span>
        </div>
        <div id="croploadingmsg" class='tophead'></div>
        <hr />
        <div id="croppedtarget"><a href="" download=""><img src="" /></a></div>
       <hr />
    </div>
    <div id="cropimagebox">
        <div id='selecttoolbar' class='tophead'>
            <button id="imgdefault" name="imagedefault">reset</button>
            <button id="selectbutton" name="select">select</button>
            <button id="imgdown" name="imgdown">-</button>
            <button id="imgup" name="imgup">+</button>
            <button id="imgactual" name="imgactual">actual size</button>
            <span id="imgdimentions" class="tophead"></span>
        </div>
        <div id='croptoolbar' class="tophead">
            <button id="cropcancelbutton" name="cropcancel" class="tophead">reset</button>
            <form method="get" class="tophead">
            <input type="hidden" id="x" name="x" />
            <input type="hidden" id="y" name="y" />
            <input type="hidden" id="w" name="w" />
            <input type="hidden" id="h" name="h" />
            <input type="hidden" id="img_w" name="img_w" />
            <input type="hidden" id="img_h" name="img_h" />
            <input type="hidden" id="image_id" name="image_id" value="<?php echo $tpl->image_id ?>" />
            <input id="cropbutton" type="submit" value="crop">
            <span id="imgdimentions" class="tophead"></span>
            </form>
        </div>
        <div id="imgloadingmsg" class='tophead'></div>
        <hr />
        <div id="croptarget">
            <img class="pointer" src="" />
        </div>
        <hr />
    </div>
</div>
</body>
</html>

