<?php
/*
* Version: 1.5
* Author: aconway@inc.com
*/
ini_set('memory_limit', '640M');
require_once('GettyImages.php');
require_once('conf/config.php');

$urls = array(
    array("^/?$", "search"),
    array("^/image/?$", "image"),
    array("^/image-api/?$", "image_api")
);

$views = new Views($urls);
$views->render_page();

exit();


class Views {

    public function __construct($urls) {
       $this->pgObj = new PathParser($urls);
    }

    public function render_page() {
        $view = $this->pgObj->view;
        $this->$view();
    }

    public function search() {
        global $getty_auth;
        global $imageApiUrl;
        $req = new GettyImages\ReqData($_REQUEST);
        $data = new GettyImages\Search($req, $getty_auth);
        $this->tpl = new setTemplateVals($req, $data, $_SERVER);
        $this->tpl->imageApiUrl = $imageApiUrl;
        $this->render_tmpl("tmpl/search.php");
    }

    public function image() {
        global $getty_auth;
        global $imageApiUrl;
        $this->tpl = new GettyImages\ReqData($_REQUEST);
        $this->tpl->imageApiUrl = $imageApiUrl;
        $this->render_tmpl("tmpl/image.php");
    }

    public function image_api() {
        global $getty_auth;
        global $imgDir;

        $req = new GettyImages\ReqData($_REQUEST);

        switch(@$req->proxy_type) {

          case "search":
            $tpl = new GettyImages\Search($req, $getty_auth);
            break;

          case "save":
            $tpl = new GettyImages\SaveFile($req, $getty_auth, $imgDir);
            break;

          case "crop":
            $tpl = new GettyImages\Crop($req, $getty_auth, $imgDir);
            break;

          default:
            $tpl = array("error" => "invalid request");
            break;
        }
        $this->render_json($tpl);
    }

    private function render_tmpl($filepath) {
        $vars = array("path" => $this->pgObj, "tpl" => $this->tpl);
        extract($vars);
        ob_start();
        require($filepath);
        $contents = ob_get_contents();
        ob_end_clean();
        echo $contents;
        return;
    }

    public function error() {
        header('HTTP/1.0 404 Not Found');
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        return;
    }

    public function api_error() {
        $tpl = array("error" => "invalid request");
        echo $this->render_json($tpl);
        return;
    }

    private function render_json($tpl) {
        header('Content-type: application/json; charset=utf-8');
        header("Cache-Control: max-age=0, s-maxage=0, no-cache, no-store, must-revalidate, post-check=0, pre-check=0, private");
        header('Access-Control-Allow-Origin: *');
        echo json_encode($tpl);
        return;
    }

} // end class Views


class PathParser {

    function __construct($urls, $default='error') {
        $this->view = $default;
        $this->setView($urls);
    }

    public function setView($urls) {
        $requri = strtolower(strtok($_SERVER['REQUEST_URI'], "?"));
        foreach($urls as $urlinfo) {
            list($path, $view) = $urlinfo;
            if (preg_match("#$path#", $requri, $matches)) {
                foreach ($matches as $k => $v) {
                    if(!is_numeric($k)) $this->$k = $v;
                }
                $this->view = $view;
                return;
            }
        }
    }

} // end class PathParser


class setTemplateVals {

    public function __construct($req, $data, $server) {
        $this::addToClassObj($req);
        $this::addToClassObj($data);
        $this->query_string = $this->getArrayVal($server, 'QUERY_STRING');
        $this->php_self = "";
        $this->scripturl = $this->getScriptUrl();
    }

    private function getScriptUrl() {
        return $this->php_self."?".$this->getQueryStringMinus('page');
    }

    private function addToClassObj($add) {
        foreach ($add as $k => $v) { $this->$k = $v; }
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

    private function getArrayVal($array, $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
    }

} // end setTemplateVals


