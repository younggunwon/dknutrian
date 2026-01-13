<?php

header("Access-Control-Allow-Origin: *");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Database\DB;
use Board\Board;

$board = new Board();
if(!$_GET) {
	$_GET = $_POST;
}

switch($_GET['mode']) {
	case 'getFrontQnaList':
		$qnaList = $board->getFrontQnaList();
		echo json_encode(['success' => true, 'data' => $qnaList]);
	break;

	case 'update' :
		$board->updateBoard();
	break;
	case 'insert' :
		$result = $board->insertBoard();
		echo json_encode($result);
		exit;
	break;
	case 'getEstimateList' :
		$result = [];
		$result['result'] = true;
		$EstimateListData = $board->getBoardList();

		if($_GET['mypageWriterId']) {
			foreach($EstimateListData['boardList'] as $key => $val) {
				if($EstimateListData['boardList'][$key]['adminUploadFile']) {
					$EstimateListData['boardList'][$key]['anserFl'] = true;
				}
			}
		}else {
			foreach($EstimateListData['boardList'] as $key => $val) {
				if($EstimateListData['boardList'][$key]['adminUploadFile']) {
					$EstimateListData['boardList'][$key]['anserFl'] = true;
				}
				if($val['writerId'] != $_GET['writerId']) {
					$EstimateListData['boardList'][$key]['boardSno'] = '';
					$EstimateListData['boardList'][$key]['contents'] = '';
					$EstimateListData['boardList'][$key]['uploadFileUrl'] = '';
					$EstimateListData['boardList'][$key]['writerName'] = mb_substr($val['writerName'], 0, 1) . '**';
					$EstimateListData['boardList'][$key]['writerId'] = '';
					$EstimateListData['boardList'][$key]['writerEmail'] = '';
					$EstimateListData['boardList'][$key]['writerPhone'] = '';
					$EstimateListData['boardList'][$key]['adminUploadFile'] = '';
				}
			}
		}

		$result['EstimateListData'] = $EstimateListData;
		echo json_encode($result);
		exit;
	break;

	case 'getEstimateView' :
		$db = new DB();
		$result = [];
		$result['result'] = true;
		$boardView = $board->getBoardView();
		foreach($boardView['selectProduct'] as $key => $val) {
			$sql = "SELECT price FROM wg_goods WHERE product_no = '{$val['productNo']}' OR product_name = '{$val['productName']}'";
			
			$price = $db->query_fetch($sql)[0]['price'];
			$boardView['selectProduct'][$key]['price'] = $price;
		}
		$result['boardView'] = $boardView;

		if($_GET['writerId'] != $boardView['writerId']) {
			$result['boardView'] = [];
			$result['result'] = false;
			$result['message'] = '잘못된 요청입니다.';
		}

		echo json_encode($result);
		exit;
	break;

	case 'uploadPdf' :
		$result = [];
		$result['result'] = true;
		try {
			$result['pdfUrl'] = $board->uploadPdf();
		} catch(\Exception $e) {
			$message = $e->getMessage();
			$result['result'] = false;
			$result['message'] = $message;
		}

		echo json_encode($result);
		exit;
	break;
	case 'test' :
		$board->sendSms();
	break;
	case 'uploadWriteImage' :
		$result = $board->uploadWriteImage();

		echo json_encode($result);
		exit;
	break;

	case 'uploadWriteVideo' :
		$result = $board->uploadWriteVideo();
		header('Content-Type: application/json');

		echo json_encode($result, JSON_UNESCAPED_SLASHES);
		exit;
	break;

}
