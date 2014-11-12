<?php namespace GettyImages;
/*
* Version: 1.5
* Author: aconway@inc.com
*/

class ApiCalls extends ApiCallsBase {

    public function __construct($auth_keys) {

        if ($auth_keys['grant_type'] === 'password') {
            list($client_key, $client_secret, $grant_type,
                $username, $password) = array_values($auth_keys);
        } else {
            list($client_key, $client_secret,
                $grant_type) = array_values($auth_keys);
            $username = "";
            $password = "";
        }

        parent::__construct($client_key, $client_secret,
            $grant_type, $username, $password);

    }

    public function getImageInfo($imageId, $queryParams=Array()) {
        $queryParams = (is_array($queryParams) ? $queryParams : Array());
        if (!array_key_exists('fields', $queryParams)) {
            $fields = array('id', 'artist', 'artist_title', 'display_set',
               'asset_family', 'caption', 'copyright', 'credit_line',
               'date_created', 'date_submitted', 'license_model',
               'max_dimensions', 'title');
            $queryParams = Array('fields' => implode(",", $fields));
        }
        $images = $this->getImagesInfo($imageId, $queryParams);
        if (is_array($images)) {
            foreach ($images as $image) {
                if (is_array($image)) {
                    if ($image['id'] === $imageId) {
                        return $image;
                    }
                }
            }
        }
    }

    public function getImagesInfo($imgIds, $queryParams=Array())  {
        $queryParams = (is_array($queryParams) ? $queryParams : Array());
        $resp = $this->makeMetaApiCall($imgIds, $queryParams);
        $payload = new CallResp($resp, TRUE);
        $body = $payload->body;
        if (array_key_exists('images', $body)) {
            return $body['images'];
        }
    }

    public function getSearchImages($req) {
        $queryParams = array(
            "phrase" => $req->phrase,
            "page" => $req->page,
            "page_size" => $req->page_size,
            "sort_order" => $req->sort_order,
            "file_types" => $req->file_types,
            "exclude_nudity" => 'true'
        );
        if (!empty($req->orientations)) {
            $queryParams['orientations'] = $req->orientations;
        }
        if (!empty($req->graphical_styles)) {
            $queryParams['graphical_styles'] = $req->graphical_styles;
        }
        $resp = $this->makeSearchApiCall($req->image_family, $queryParams, $req->notoken);
        $payload = new CallResp($resp, TRUE);
        $body = $payload->body;
        if (array_key_exists('result_count', $body)) {
            $paging = new Paging($req->page, $body['result_count'], $req->page_size);
            $body['prevpage'] = $paging->prevpage;
            $body['currentpage'] = $paging->currentpage;
            $body['lastpage'] = $paging->lastpage;
            $body['nextpage'] = $paging->nextpage;
        }
        return $body;
    }

    public function sendUsageBatches($asset_usages) {
        $id = "inc_" . time() . rand();
        $urlPath = "/v3/usage-batches/".$id;
        $putData = json_encode(array("asset_usages" => $asset_usages));
        return $this->putApiCall($urlPath, $putData);
    }

    public function makeMetaApiCall($imageIds, $queryParams=Array()) {
        $queryParams = (is_array($queryParams) ? $queryParams : Array());
        if (is_array($imageIds)) {
            $imageIds = implode(",", $imageIds);
        }
        if (!array_key_exists('fields', $queryParams)) {
            $queryParams["fields"] = "detail_set";
        }
        $urlPath = "/v3/images/".$imageIds;
        return $this->getApiCall($urlPath, $queryParams);
    }

    public function makeSearchApiCall($imgFam, $queryParams, $notoken) {
        $urlPath = "/v3/search/images/".$imgFam;
        if (!array_key_exists('fields', $queryParams)) {
            $queryParams["fields"] = "detail_set,preview,comp";
        }
        return $this->getApiCall($urlPath, $queryParams, $notoken);
    }

    public function makeDownloadApiCall($imageId, $height='') {
        $urlPath = "/v3/downloads/".$imageId;
        $height = preg_replace('#[^0-9]#i', '', $height);
        $queryParams = array(); #array('id' => $imageId);
        if (!empty($height)) {
            $queryParams['height'] = $height;
        }
        return $this->postApiCall($urlPath, $queryParams);
    }

    public function getApiCall($urlPath, $queryParams=Array(), $notoken=0) {
        $orig_auth_header = $this->auth_header;
        if ($notoken === 1) {
            $this->auth_header = array("Api-Key:".$this->client_key);
        }
        $resp = parent::getApiCall($urlPath, $queryParams, $notoken);
        $this->auth_header = $orig_auth_header;
        return $resp;
    }

    private function executeCurl($curl) {
        $resp = parent::executeCurl($curl);
        $errmsg = "Api Request Failed: ";
        if (!empty($resp['curl_error'])) {
            $errmsg .= $resp['curl_error'];
            throw new Exception($errmsg);
        } elseif ($resp['http_code'] === 403) {
            $errmsg .= "API KEY INVALID";
            throw new Exception($errmsg);
        }
        return $resp;
    }

} // end class ApiCalls


class ApiCallsBase {

    public function __construct($client_key, $client_secret,
                                $grant_type,  $username="", $password="") {
        $this->client_key = $client_key;
        $this->client_secret = $client_secret;
        $this->username = $username;
        $this->password = $password;
        $this->grant_type = $grant_type;
        $this->api_domain = "https://connect.gettyimages.com";
        $this->auth_params = array(
            "client_id" => $this->client_key,
            "client_secret" => $this->client_secret,
            "grant_type" => $this->grant_type,
            "username" => $this->username,
            "password" => $this->password
        );
        $this->authorization = $this->getAuthorization();
        $this->auth_header = array("Api-Key:".$this->client_key,
                                   "Authorization: ".$this->authorization);
    }

    public function postApiCall($urlPath, $queryParams=Array()) {
        $queryParams = (is_array($queryParams) ? $queryParams : Array());
        $endpoint = $this->buildEndpointUrl($urlPath, $queryParams);
        $header = $this->auth_header;
        array_push($header, 'Content-Type: application/x-www-form-urlencoded');
        $headersToSend = array(
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_FOLLOWLOCATION => TRUE);
        $curl = $this->getCurlForPost($endpoint,$headersToSend);
        $this->setFormData($curl, $queryParams);
        return $this->executeCurl($curl);
    }

    public function getApiCall($urlPath, $queryParams=Array()) {
        $queryParams = (is_array($queryParams) ? $queryParams : Array());
        $endpoint = $this->buildEndpointUrl($urlPath, $queryParams);
        $header = $this->auth_header;
        array_push($header, 'Content-Type: application/x-www-form-urlencoded');
        $headersToSend = array(CURLOPT_HTTPHEADER => $header);
        $curl = $this->getCurl($endpoint, $headersToSend);
        return $this->executeCurl($curl);
    }

    public function putApiCall($urlPath, $putData) {
        $endpoint = $this->buildEndpointUrl($urlPath);
        $header = $this->auth_header;
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Content-Length: '. strlen($putData));
        $headersToSend = array(
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POSTFIELDS => $putData,
        );
        $curl = $this->getCurlForPut($endpoint,$headersToSend);
        return $this->executeCurl($curl);
    }

   private function getAuthorization(){
        $resp = $this->curlToken($this->auth_params);
        $body = json_decode($resp['body'],true);
        $token = array_key_exists('access_token', $body) ? $body['access_token'] : "";
        $tokenType = array_key_exists('token_type', $body) ? $body['token_type'] : "";
        return $tokenType." ".$token;
    }

    private function curlToken($authParams){
        $endpoint = $this->buildEndpointUrl('/oauth2/token/');
        $curl = $this->getCurlForFormPost($endpoint);
        $this->setFormData($curl, $authParams);
        return $this->executeCurl($curl);
    }

    private function buildEndpointUrl($urlPath, $queryParams=Array()) {
        $queryParams = (is_array($queryParams) ? $queryParams : Array());
        $apiDomain = rtrim($this->api_domain, "/");
        $urlPath = ltrim($urlPath, "/");
        $endpoint = $apiDomain."/".$urlPath;
        if (!empty($queryParams)) {
            $endpoint .= (strpos($endpoint, '?') === FALSE ? '?' : '');
            $endpoint .= http_build_query($queryParams);
        }
        return $endpoint;
    }

    private function executeCurl($curl) {
        $response = curl_exec($curl);
        $request = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        $error = curl_error($curl);
        $result = array( 'header' => '',
                         'body' => '',
                         'curl_error' => '',
                         'http_code' => '',
                         'last_url' => '');
        if ( $error != "" ) {
            $result['curl_error'] = $error;
            return $result;
        }
        $header_size = curl_getinfo($curl,CURLINFO_HEADER_SIZE);
        $result['header'] = substr($response, 0, $header_size);
        $result['body'] = substr( $response, $header_size );
        $result['http_code'] = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        $result['last_url'] = curl_getinfo($curl,CURLINFO_EFFECTIVE_URL);
        curl_close($curl);
        return $result;
    }

    private function getCurl($url, array $options = null) {
        $defaults = array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_HEADER => 1,
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0
        );

        if(!$options) {
            $curlOptions = $defaults;
        } else {
            $curlOptions = $this->mergeCurlOptions($defaults, $options);
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);

        return $curl;
    }

    private function getCurlForFormPost($url) {
        $curlOptions = array(CURLOPT_HTTPHEADER =>
            array("Content-Type: application/x-www-form-urlencoded"));
        $curl = $this->getCurlForPost($url,$curlOptions);

        return $curl;
    }

    private function getCurlForPost($url,array $options = null) {
        $defaults = array( CURLOPT_POST => 1 );
        $curlOptions = $this->mergeCurlOptions($defaults,$options);
        return $this->getCurl($url, $curlOptions);
    }

    private function getCurlForPut($url,array $options = null) {
        $defaults = array(
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POST => 1
          );
        $curlOptions = $this->mergeCurlOptions($defaults,$options);
        return $this->getCurl($url, $curlOptions);
    }

    private function mergeCurlOptions(array $defaults, array $optionsToAdd) {
        if(!$optionsToAdd) {
            return $defaults;
        }
        if(array_key_exists(CURLOPT_HTTPHEADER,$optionsToAdd)) {
          if(array_key_exists(CURLOPT_HTTPHEADER, $defaults)) {
            $defaultHeaders = $defaults[CURLOPT_HTTPHEADER];
            $additionalHeaders = $optionsToAdd[CURLOPT_HTTPHEADER];
            $mergedHeaders = $defaultHeaders + $additionalHeaders;
            $defaults[CURLOPT_HTTPHEADER] = $mergedHeaders;
            unset($optionsToAdd[CURLOPT_HTTPHEADER]);
          }
        }
        $mergedOptions = $defaults + $optionsToAdd;
        return $mergedOptions;
    }

    private function setFormData($curl,$params) {
        $params = http_build_query($params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        return $curl;
    }

} // end class ApiCallsBase


class Search {

    public function __construct($req, $auth) {
        if (empty($req->phrase)) {
            $this->hasSearchResults = FALSE;
            return;
        }
        $this->doSearch($req, $auth);
    }

    private function doSearch($req, $auth) {
        $api = new ApiCalls($auth);
        $resp = $api->getSearchImages($req);
        $payload = new SearchImagesResp($resp);
        $this->addToClassObj($payload);
        $this->hasSearchResults = TRUE;
    }

    private function addToClassObj($add) {
        foreach ($add as $k => $v) {
            $this->$k = $v;
        }
    }

} // end class Search


class SaveFile {

    public function __construct($req, $getty_auth, $dir) {
        if (empty($req->image_id)) {
            $this->error = TRUE;
            return;
        }
        $imgF = new ImageFile($req, $dir);
        $this->image_id = $req->image_id;
        $api = new ApiCalls($getty_auth);
        $this->status = $this->doSave($imgF, $api);
        $imgF->crop($req, 1);
        $this->image = $this->getMetaData($api);
        if ($imgF->cropped) {
            $this->filename = $imgF->cropped_fn;
            $this->filepath = $imgF->cropped_fp;
            $this->file_saved = $imgF->crop_saved;
        } else {
            $this->filename = $imgF->filename;
            $this->filepath = $imgF->filepath;
            $this->file_saved = $imgF->saved;
        }
    }

    private function getMetaData($api) {
        return $api->getImageInfo($this->image_id);
    }

    private function doSave($imgF, $api) {
        if ($imgF->doesFileExists()) {
            return 200;
        } else {
            return $this->saveIt($imgF, $api);
        }
    }

    public function saveIt($imgF, $api) {
        $resp = $api->makeDownloadApiCall($this->image_id);
        $payload = new CallResp($resp, FALSE);
        if ($payload->status === 200) {
            $body = $payload->body;
            $imgF->saveToFile($body);
        }
        return $payload->status;
    }

    public function getAdminFileName($admin_id) {
        $lastdot = strrpos($this->filename,".");
        $filename = substr($this->filename,0,$lastdot);
        $filename .= "_".$admin_id.substr($this->filename,$lastdot);
        return $filename;
    }

} // end class SaveFile


class Crop extends SaveFile {
    public function __construct($req, $getty_auth, $dir) {
        if (empty($req->image_id)) {
            $this->error = TRUE;
            return;
        }
        $imgF = new ImageFile($req, $dir);
        $this->image_id = $req->image_id;
        $this->status = $this->doSave($imgF, $getty_auth);
        $this->doCrop($req, $imgF);
        $this->crop_target_src = $imgF->filepath;
        $this->cropped_src = "";
        $this->cropped_fn = "";
        $this->have_crop = FALSE;
        if ($imgF->cropped)  {
            $this->have_crop = TRUE;
            $this->cropped_src = 'data:image/jpeg;base64, ';
            $this->cropped_src .= base64_encode( $imgF->cropped_data );
            $this->cropped_fn = $imgF->cropped_fn;
        }

    }

    private function doCrop($req, $imgF) {
        if ($this->status === 200) {
            $imgF->crop($req);
        }
    }

    private function doSave($imgF, $getty_auth) {
        if ($imgF->doesFileExists()) {
            return 200;
        } else {
            $api = new ApiCalls($getty_auth);
            return $this->saveIt($imgF, $api);
        }
    }

} // end class Crop


class Paging {

    public function __construct($page, $result_count, $page_size) {
        $this->currentpage = $this->clean_page_val($page);
        $this->result_count = $result_count;
        $this->page_size = $page_size;
        $this->nextpage = $this->currentpage + 1;
        $this->prevpage = $this->currentpage - 1;
        $this->parse_page_range();
    }

    public function clean_page_val($page) {
        $currentpage = 1;
        if (isset($page)) {
            $currentpage = preg_replace('#[^0-9]#i', '', $page);
        }
        return (int)$currentpage;
    }

    public function parse_page_range() {
        if ($this->result_count < $this->page_size) {
            $this->lastpage = 1;
            $this->nextpage = 1;
            $this->prevpage = 1;
        } else {
            $lastpage = (int)ceil($this->result_count / $this->page_size);
            $this->lastpage = (int)$lastpage;
        }

        if ($this->currentpage <= 1) {
            $this->currentpage = 1;
            $this->prevpage = $this->lastpage;
        } elseif ($this->currentpage >= $this->lastpage) {
            $this->currentpage = $this->lastpage;
            $this->nextpage = 1;
        }

    }

} // end class Paging


class ReqData extends CleanVals {

    public function __construct($REQ) {
        $this->phrase = $this->clean_string_val($REQ, 'phrase');
        $this->sort_order = $this->clean_string_val($REQ, 'sort_order', 'most_popular');
        $this->image_family = $this->clean_string_val($REQ, 'image_family', 'creative');
        $this->orientations = $this->clean_string_val($REQ, 'orientations');
        $this->file_types = $this->clean_string_val($REQ, 'file_types', 'jpg');
        $this->graphical_styles = $this->clean_string_val($REQ, 'graphical_styles', 'photography');
        $this->page_size = $this->clean_page_size_val($REQ, 100);
        $this->page = $this->clean_num_val($REQ, 'page', 1);
        $this->image_id = $this->clean_image_id_val($REQ);
        $this->notoken = $this->clean_num_val($REQ, 'notoken', 0);
        $this->x = $this->clean_num_val($REQ, 'x');
        $this->y = $this->clean_num_val($REQ, 'y');
        $this->h = $this->clean_num_val($REQ, 'h');
        $this->w = $this->clean_num_val($REQ, 'w');
        $this->img_h = $this->clean_num_val($REQ, 'img_h');
        $this->img_w = $this->clean_num_val($REQ, 'img_w');
        $this->proxy_type = $this->clean_proxy_type_val($REQ);
        $this->filename = $this->get_filename_val();
    }

    public function get_crop_dim_jsonblob() {
        if (!is_numeric($this->x)) {
            return;
        }
        if (!is_numeric($this->y)) {
            return;
        }
        if (!is_numeric($this->w)) {
            return;
        }
        if (!is_numeric($this->h)) {
            return;
        }
        if (!is_numeric($this->img_w)) {
            return;
        }
        if (!is_numeric($this->img_h)) {
            return;
        }
        return json_encode(array(
            "x" => $this->x,
            "y" => $this->y,
            "h" => $this->h,
            "w" => $this->w,
            "img_h" => $this->img_h,
            "img_w" => $this->img_w
        ));
    }

    private function clean_image_id_val($REQ) {
        return $this->clean_string_val($REQ, 'image_id');
    }

    private function clean_page_size_val($REQ, $default) {
        $page_size = $this->clean_num_val($REQ, 'page_size', $default);
        $page_size = ($page_size > 100) ? 100 : $page_size;
        return $page_size;
    }

    private function clean_proxy_type_val($REQ) {
        $proxy_type = $this->clean_string_val($REQ, 'proxy_type');
        $proxy_type = strtolower($proxy_type);

        if ($proxy_type === 'search') {
            if (empty($this->phrase)) {
                $proxy_type = "";
            }
        }
        if ($proxy_type === 'download') {
            if (empty($this->image_id)) {
                $proxy_type = "";
            }
        }
        if ($proxy_type === 'get') {
            if (empty($this->image_id)) {
                $proxy_type = "";
            }
        }
        if ($proxy_type === 'crop') {
            if (empty($this->image_id)) {
                $proxy_type = "";
            }
        }
        return $proxy_type;
    }

    private function get_filename_val() {
        if ($this->image_id) {
            $imgF  = new ImageFile($this, "");
            return (($imgF->crop_valid) ? $imgF->cropped_fn : $imgF->filename);
        }
        return "";
    }

} // end class ReqData


class UsageReqData extends CleanVals {

    public function __construct($REQ) {
        list($year, $month) = explode("-", date('Y-m', strtotime('last month')));
        $this->year = $this->clean_num_val($REQ, 'year', $year);
        $this->month = $this->clean_num_val($REQ, 'month', $month);
        $this->usage_type = $this->clean_usage_type_val($REQ);
    }

    private function clean_usage_type_val($REQ) {
        $usage_type = $this->clean_string_val($REQ, 'usage_type');
        $usage_type = strtolower($usage_type);
        return $usage_type;
    }

} // end UsageReqData


class SearchImagesResp extends CleanVals {

    public function __construct($RESP) {
        $this->result_count = $this->clean_num_val($RESP, 'result_count', -1);
        $this->images = $this->get_array_val($RESP, 'images');
        $this->prevpage = $this->clean_num_val($RESP, 'prevpage', 0);
        $this->currentpage = $this->clean_num_val($RESP, 'currentpage', 0);
        $this->lastpage = $this->clean_num_val($RESP, 'lastpage', 0);
        $this->nextpage = $this->clean_num_val($RESP, 'nextpage', 0);
    }

} // end class SearchImagesResp


class CallResp extends CleanVals {

    public function __construct($RESP, $isBodyJson=FALSE) {
        $this->header = $this->clean_string_val($RESP, 'header');
        $this->status = $this->clean_string_val($RESP, 'http_code');
        $this->last_url = $this->clean_string_val($RESP, 'last_url');
        $this->body = $this->clean_body_val($RESP, $isBodyJson);
    }

    private function clean_body_val($RESP, $isBodyJson) {
        $body = $this->get_array_val($RESP, 'body');
        if ($isBodyJson) {
            $body = json_decode($body,TRUE);
            $body = $this->clean_images($body);
        }
        return $body;
    }

    private function clean_images($body) {
        $clean_data = function ($img) {
            if (array_key_exists('caption', $img)) {
                $img['caption'] = preg_replace('/^[^:]*:\s*/', '', $img['caption']);
            }
            return $img;
        };
        if (is_array($body)) {
            if (array_key_exists('images', $body)) {
                $body['images'] = array_map($clean_data, $body['images']);
            }
        }
        return $body;
    }

} // end class CallResp


class CleanVals {

    public function clean_string_val($array, $key, $default="") {
        $string_val = $default;
        if (isset($array[$key])) {
            $string_val = $array[$key];
        }
        return $string_val;
    }

    public function clean_num_val($array, $key, $default="") {
        $num_val = (!empty($default) ? $default : "");
        if (isset($array[$key])) {
            $num_val = $array[$key];
        }
        $num_val = preg_replace('#[^0-9]#i', '', $num_val);
        if (strlen($num_val) > 0) {
            return (int)$num_val;
        }
    }

    public function get_array_val($array, $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
    }

} // end class CleanVals


class ImageFile {

    public function __construct($req, $dir="") {
        $this->image_id = $req->image_id;
        $this->filedir = rtrim($dir, "/") . "/";
        $this->filename = $this->getFileName();
        $this->filepath = $this->getFilePath();
        $this->target_w = $this->parseCropDimentions($req, "img_w");
        $this->target_h = $this->parseCropDimentions($req, "img_h");
        $this->cropped_w = $this->parseCropDimentions($req, "w");
        $this->cropped_h = $this->parseCropDimentions($req, "h");
        $this->cropped_x = $this->parseCropDimentions($req, "x");
        $this->cropped_y = $this->parseCropDimentions($req, "y");
        $this->cropped_fn = $this->getCroppedFileName();
        $this->cropped_fp = $this->getCroppedFilePath();
        $this->crop_valid = $this->validateCrop();
        $this->saved = FALSE;
        $this->cropped = FALSE;
    }

    public function saveToFile($payload) {
        @unlink($this->filepath);
        if (@touch($this->filepath)) {
            if (@is_writable($this->filepath)) {
                if ($handle = fopen($this->filepath, 'w')) {
                    if (fwrite($handle, $payload) === FALSE) {
                        $this->saved = FALSE;
                    } else {
                        $this->filesize = filesize($this->filepath);
                        $this->saved = TRUE;
                    }
                    fclose($handle);
                }
            }
        }
    }

    public function saveCropToFile() {
        if (empty($this->cropped_data)) {
            $this->crop_saved = FALSE;
            return;
        }
        @unlink($this->cropped_fp);
        if (touch($this->cropped_fp)) {
            if (is_writable($this->cropped_fp)) {
                if ($handle = fopen($this->cropped_fp, 'w')) {
                    if (fwrite($handle, $this->cropped_data) === FALSE) {
                        $this->crop_saved = FALSE;
                    } else {
                        $this->crop_filesize = filesize($this->cropped_fp);
                        $this->crop_saved = TRUE;
                    }
                    fclose($handle);
                }
            }
        }
    }

    public function doesFileExists() {
        $hasFile = (file_exists($this->filepath));
        if ($hasFile) {
            // create time was in the last 24 hours
            $hasFile = (filemtime($this->filepath) > time() - 86400);
        }
        if ($hasFile) $this->saved = TRUE;
        return $hasFile;
    }

    public function crop($req, $save_it=0) {
        $this->getMaxDimentions();
        $this->doCropping();
        if ($save_it) {
            $this->saveCropToFile();
        }
    }

    private function getFileName() {
        if (!empty($this->image_id)) {
            return "getty_".strtolower($this->image_id).".jpg";
        } else {
            return "";
        }
    }

    private function getFilePath() {
        if (empty($this->filename)) {
            return "";
        }
        return $this->filedir . $this->filename;
    }

    private function getMaxDimentions() {
        if (file_exists($this->filepath)) {
            list($max_w, $max_h) = getimagesize($this->filepath);
            $this->max_w = $max_w;
            $this->max_h = $max_h;
        }
    }

    private function getCroppedFileName() {
        if (empty($this->filename)) {
            return "";
        }
        $cid = $this->target_w;
        $cid .= $this->target_h;
        $cid .= $this->cropped_w;
        $cid .= $this->cropped_h;
        $cid .= $this->cropped_x;
        $cid .= $this->cropped_y;
        $filename = $this->filename;
        $lastdot = strrpos($filename,".");
        $cropped_fn = substr($filename,0,$lastdot);
        $cropped_fn .= "_".$cid.substr($filename,$lastdot);
        return $cropped_fn;
    }

    private function getCroppedFilePath(){
        if (empty($this->cropped_fn)) {
            return "";
        }
        return $this->filedir . $this->cropped_fn;
    }

    private function validateCrop() {
        $crop_valid = TRUE;
        $n = array('target_w',
                   'target_h',
                   'cropped_w',
                   'cropped_h',
                   'cropped_x',
                   'cropped_y');
        foreach($n as $key) {
            if (!is_int($this->$key)) {
                $crop_valid = FALSE;
            }
        }
        $s = array('filepath');
        foreach($s as $key) {
            if (empty($this->$key)) {
                $crop_valid = FALSE;
            }
        }
        return $crop_valid;
    }

    private function doCropping() {
        $haveCrop = $this->doesFileExists();
        $haveCrop = $this->crop_valid;
        if (!$haveCrop) {
            return;
        }
        if (($this->max_h === $this->target_h) && ($this->max_w === $this->target_w)) {
            $cropped_img_r = imagecreatefromjpeg($this->filepath);
        } else {
            $resized_img_r = imagecreatefromjpeg($this->filepath);
            $resized_dst_r = ImageCreateTrueColor( $this->target_w, $this->target_h );
            imagecopyresampled($resized_dst_r,$resized_img_r,0,0,0,0,
                $this->target_w, $this->target_h, $this->max_w, $this->max_h);
            ob_start();
            imagejpeg($resized_dst_r,null,100);
            $resized_img_data = ob_get_contents();
            ob_end_clean();
            $cropped_img_r = imagecreatefromstring($resized_img_data);
            unset($resized_img_r);
            unset($resized_dst_r);
            unset($resized_img_data);
        }
        $cropped_dst_r = ImageCreateTrueColor( $this->cropped_w, $this->cropped_h );
        imagecopyresampled($cropped_dst_r,$cropped_img_r,0,0,$this->cropped_x,$this->cropped_y,
            $this->cropped_w,$this->cropped_h,$this->cropped_w,$this->cropped_h);
        ob_start();
        imagejpeg($cropped_dst_r,null,100);
        $cropped_img_data = ob_get_contents();
        ob_end_clean();
        $this->cropped_data = $cropped_img_data;
        $this->cropped = TRUE;
        unset($cropped_img_r);
        unset($cropped_dst_r);
        unset($cropped_img_data);
    }

    private function parseCropDimentions($req, $key, $default="") {
        $num_val = (!empty($default) ? $default : "");
        if (isset($req->$key)) {
            $num_val = $req->$key;
        }
        $num_val = preg_replace('#[^0-9]#i', '', $num_val);
        if (strlen($num_val) > 0) {
            return (int)$num_val;
        }
    }

} // end class ImageFile


class Logger {

    public function __construct($fn, $dir="") {
        $this->filename = $fn;
        $this->dir = rtrim($dir, "/");
        $this->filepath = $this->dir ."/". $this->filename;
    }

    public function logMsg($msg) {
        if ($fh = @fopen($this->filepath, "a")) {
            $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
            fwrite($fh, $str . "\n");
            fclose($fh);
            return TRUE;
        } else {
            return FALSE;
        }
   }

} // end class Logger



