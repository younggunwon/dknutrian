<?php

header("Access-Control-Allow-Origin: *");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Database\DB;
use Goods\Goods;

$goods = new Goods();

set_time_limit(0);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);

// $_GET이 비어있을 때 $_POST를 $_GET으로 설정
if (empty($_GET) && !empty($_POST)) {
    $_GET = $_POST;
}

switch($_GET['mode']) {
	case 'checkRegistrationNumberDuplicate': //등록번호 중복검사
		$registerNo = $_GET['registerNo'] ?? '';
		$result = $goods->checkRegistrationNumberDuplicate($registerNo);
		echo json_encode($result);
	break;
	case 'warrantApply': //보증신청
		$warrant = $goods->warrantApply($_GET);
		echo json_encode($warrant);
	break;
	case 'getRegisteredWarrants': //등록된 보증서 가져오기
		$warrantsList = $goods->getRegisteredWarrants($_GET);
		echo json_encode($warrantsList);
	break;
	case 'getWarrantyGoodsNmList': //보증서 특정제품가져오기
		$goodsList = $goods->getWarrantyGoodsNmList($_GET['keyword'], $_GET['page']);
		echo json_encode($goodsList);
	break;
	case 'registerWarrant': // 보증서 등록
		$result = $goods->registerWarrant($_GET);
		echo json_encode($result);
	break;
	case 'update' :
		$goods->updateGoods();
	break;

	case 'getAddImages' :
		$addImages = $goods->getGoodsAddImage($_GET['product_no']);
		echo json_encode($addImages);
	break;
	
	case 'getGoodsList' :
		$result = [];
		$result['result'] = true;
		
		$goodsListData = $goods->getGoodsList();
		$result['goodsListData'] = $goodsListData;
		echo json_encode($result);
	break;
	case 'getCategory' :
		$result = [];
		$result['result'] = true;
		
		$category = $goods->getCategory('', 'tree');
		$result['category'] = $category;
		echo json_encode($result);
	break;
	case 'test' :
		$goods->selectCategoryGoods();
	break;
}
