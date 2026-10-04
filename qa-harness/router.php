<?php
$root = "/var/www/realstate/public";
$uri  = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
if ($uri !== "/" && file_exists($root.$uri) && ! is_dir($root.$uri)) { return false; }
$_SERVER["SCRIPT_NAME"] = "/index.php";
require_once $root."/index.php";
