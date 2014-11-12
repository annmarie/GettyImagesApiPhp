<?php
date_default_timezone_set('America/New_York');

require('settings.php');

$getty_auth = array(
  "client_key" => $env->getty_key,
  "client_secret" => $env->getty_secret,
  "grant_type" => "password",
  "username" => $env->getty_username,
  "password" => $env->getty_password
);

$urlPath = rtrim($env->urlpath, "/");

$imgDir = rtrim($env->imgdir, "/") . "/";

$imageApiUrl= rtrim($env->urlpath, "/") . '/image-api';

$webroot = rtrim($env->webroot, "/") . "/";


