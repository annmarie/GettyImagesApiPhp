<!DOCTYPE html>
<?php
/*
* Version: 1.0
*/
require_once('config.php');
require_once('GettyImages.php');

$req = new GettyImages\ReqData($_REQUEST);
$data = new GettyImages\Search($req, $getty_auth);
$tpl = new setTemplateVals($req, $data, $_SERVER);

?>
<html>
<head>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<meta charset="UTF-8">
<title>Search GettyImages</title>

<link rel="stylesheet" type="text/css" href="css/GettyImages.css">

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="js/GettyImages.js"></script>

<script>

// start document ready
$(document).ready(function () {

    var httpHost = window.location.host;
    var saveUrl = 'http://'+httpHost+'<?php echo $saveApiUrl; ?>';
    (new GettyImages.SearchItems).LoadOnReady(saveUrl);

}); // end document ready

</script>

</head>
<body>

<div id="mainContent">

<h1>Search GettyImages</h1>

<form method="get" action="<?php echo $tpl->php_self; ?>">
<input type="text" name="phrase" value="<?php echo htmlentities($tpl->phrase) ?>" placeholder="Search">
<select id="image_family" name="image_family">
    <option <?php if($tpl->image_family=="editorial"){echo "selected='selected'"; } ?> value="editorial">Editorial (news-oriented)</option> 
    <option <?php if($tpl->image_family=="creative"){echo "selected='selected'"; } ?> value="creative">Creative (evergreen topics)</option>
</select>
<select id="sort_order" name="sort_order">
    <option <?php if($tpl->sort_order=="most_popular"){echo "selected='selected'"; } ?> value="most_popular">Most Popular</option>
    <option <?php if($tpl->sort_order=="best"){echo "selected='selected'"; } ?> value="best">Most Relevant</option>
    <option <?php if($tpl->sort_order=="newest"){echo "selected='selected'"; } ?> value="newest">Most Recent</option>
</select>
<select id="orientations" name="orientations">
    <option <?php if($tpl->orientations==""){echo "selected='selected'"; } ?> value="">All Orientations</option>
    <option <?php if($tpl->orientations=="horizontal"){echo "selected='selected'"; } ?> value="horizontal">Horizontal</option>
    <option <?php if($tpl->orientations=="panoramic_horizontal"){echo "selected='selected'"; } ?> value="panoramic_horizontal">Panoramic Horizontal</option>
    <option <?php if($tpl->orientations=="square"){echo "selected='selected'"; } ?> value="square">Square</option>
    <option <?php if($tpl->orientations=="vertical"){echo "selected='selected'"; } ?> value="vertical">Vertical</option>
    <option <?php if($tpl->orientations=="panoramic_vertical"){echo "selected='selected'"; } ?> value="panoramic_vertical">Panoramic Vertical</option>
</select>
<input type="submit" value="Search">
</form>
<br />

<div id="overlay" class="hidden overlay_col"></div>

<?php
if ($tpl->hasSearchResults) {
    echo '<div id="searchresults">';
    if ($tpl->result_count > 0) {
        echo '<hr /><div class="paging">' . "\n";
        echo ' <a href="'.$tpl->scripturl.'&page='.$tpl->prevpage.'">&lt;</a> ';
        echo ' [<span style="font-weight:bold">'.$tpl->currentpage.' of '.$tpl->lastpage.'</span>] ';
        echo ' <a href="'.$tpl->scripturl.'&page='.$tpl->nextpage.'">&gt;</a> ';
        echo '</div><hr />';
        foreach ($tpl->images as $image) {
            $img = new DisplayImageItem($image);
            echo '<div id="paditem">';
            echo ' <div id="item" name="'.$img->id.'">';
            echo '   <div id="spacer" ></div>';
            echo '   <div id="title" class="desc">'. $img->desc. '</div>';
            echo '   <img src="'.$img->url.'" baksrc="'.$img->url_thumb.'" alt="'.$img->desc.'"/>';
            echo '   <div id="image_id" class="desc">'. $img->id . '</div>';
            echo '   <div id="caption">'. $img->caption .'</div>';
            echo '   <button id="downloadbutton" class="pointer">DOWNLOAD IMAGE</button>';
            echo '   <div id="spacer" ></div>';
            echo ' </div><!-- end #item -->';
            echo '</div><!-- end #paditem -->'."\n";
        }
        echo '<hr /><div class="paging">' . "\n";
        echo ' <a href="'.$tpl->scripturl.'&page='.$tpl->prevpage.'">&lt;</a> ';
        echo ' [<span style="font-weight:bold">'.$tpl->currentpage.' of '.$tpl->lastpage.'</span>] ';
        echo ' <a href="'.$tpl->scripturl.'&page='.$tpl->nextpage.'">&gt;</a> ';
        echo '</div><hr />';

    } else {
        echo '<div id="error">No images found</div>'."\n";
    }
    echo '</div><!-- end #searchresults -->'."\n";
}

?>

</div>
</body>
</html>

<?php

exit();

class setTemplateVals {

    public function __construct($req, $data, $server) {
        $this::addToClassObj($req);
        $this::addToClassObj($data);
        $this->query_string = $this->getArrayVal($server, 'QUERY_STRING');
        $this->php_self =  $this->getArrayVal($server, 'PHP_SELF');
        $this->scripturl = $this->getScriptUrl();
    }

    private function addToClassObj($add) {
        foreach ($add as $k => $v) {
            $this->$k = $v;
        }
    }

    private function getQueryStringMinus($key) {
        $qvars = array();
        if ($this->query_string) {
            foreach (explode("&", $this->query_string) as $tmp_arr_param) {
                $split_param = explode("=", $tmp_arr_param);
                if ($split_param[0] != $key) {
                    $qvars[$split_param[0]] = urldecode($split_param[1]);
                }
            }
        }
        return http_build_query($qvars);
    }

    private function getScriptUrl() {
        return $this->php_self."?".$this->getQueryStringMinus('page');
    }

    private function getArrayVal($array, $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
    }

} // end setTemplateVals


class DisplayImageItem {

    public function __construct($image) {
        $this->image = $image;
        $this->id = $image['id'];
        $this->desc = htmlspecialchars($image['title']);
        $this->caption = htmlspecialchars($image['caption']);
        $this->getImageUrls();
    }

    private function getImageUrls() {
        foreach ($this->image['display_sizes'] as $array) {
            if (is_array($array)) {
                if ($array['name'] === 'preview') {
                    $this->url = $array['uri'];
                } elseif ($array['name'] === 'thumb') {
                    $this->url_thumb = $array['uri'];
                }
            }
        }
    }

} // end DisplayImageItems

?>

