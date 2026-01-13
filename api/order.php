<?php

header("Access-Control-Allow-Origin: *");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Database\DB;
use Order\Order;

$order = new Order();
set_time_limit(0);

switch($_GET['mode']) {
	case 'refundOrder':
		$order->refundOrder();
	break;

	case 'update' :
		$order->updateOrder();
		echo "<script>alert('주문 연동이 완료되었습니다.'); history.back();</script>";
		exit;
	break;
	case 'getOrderGoods' :
		$order->getOrderGoods();
	break;
	case 'updateOne' :
		// $result = ['success' => true, 'message' => '주문 연동이 완료되었습니다.'];
		// echo json_encode($result, JSON_UNESCAPED_UNICODE);
		// exit;
		$order->updateOrderOne();
	break;
	case 'cafe24ApiOrderOneTest':
		$order->cafe24ApiOrderOneTest($_GET['orderId']);
	break;

}
