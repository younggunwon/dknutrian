<?php

header("Access-Control-Allow-Origin: *");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Database\DB;

$eventMileage = new \EventMileage\EventMileage();
$eventMileage->restoreMileageByRefund('20260107-0001236');

