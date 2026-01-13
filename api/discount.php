<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Discount\Discount;
use Cafe24\Cafe24;

// $cafe24 = new Cafe24();
// $cafe24->setScript();
// exit;

$discount = new Discount();
$discount->setDiscount();
exit;

?>