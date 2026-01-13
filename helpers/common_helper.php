<?php
// 타임존 설정을 서울로 셋팅
date_default_timezone_set('Asia/Seoul');
//error_reporting(E_ALL);
error_reporting(E_ERROR);

ini_set("display_errors", 1);

define('CAFE24_CLIENT_ID', 'rQfkxGAOEKTaTGbA4Q2MTG');
define('CAFE24_CLIENT_SECRET', '6usbUsxKN5eFdjiNWpfujC');
define('CAFE24_SERVICE_KEY', 'hfLozIfPjIG6zYBADkrO/+O5Vcr143XAEHSuZ1cYgk4=');
define('CAFE24_REDIRECT_URL', 'https://dknutri02.mycafe24.com/api/redirect.php');
define('CAFE24_MALL_ID', 'dknutrition');
define('CAFE24_API_VERSION', '2025-12-01');
define('CAFE24_REFFERER_URL', 'https://dknutrition.cafe24.com/');

define('DB_USER', 'dknutri02');
define('DB_PASSWORD', 'dongkook1@');

// Composer autoload (PhpSpreadsheet 등 vendor 라이브러리용)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

# autoLoad : 클래스를 선언할 때 해당 클래스의 위치에 있는 파일을 자동으로 include 해준다.
spl_autoload_register(function($className) {
    include str_replace('helpers', '', __DIR__) . '/libraries/' . str_replace('\\', '/', $className) . '.php';
});

?>