<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use EventMileage\EventMileage;

$eventMileage = new EventMileage();

$_GET = array_merge($_GET, $_POST);

switch($_GET['mode']) {
	case 'eventMileagePay' :
		// 쇼핑지원금(이벤트 마일리지) 지급 처리
		// 이벤트 마일리지는 Cafe24 마일리지와 별도 관리되며, wg_eventMileage 테이블에만 기록됨
		// 자동발급의 경우 wg_eventMileageCampaign에서 paymentFl='auto'이고 지급기간 내인 캠페인만 처리
		set_time_limit(0);
		
		// 자동 발급 캠페인 처리 (지급 기간 내 자동 발급 캠페인)
		// processAutoCampaigns()에서 wg_eventMileageCampaign 조건만 확인하고 지급 처리
		try {
			$eventMileage->processAutoCampaigns();
			$result = [
				'success' => true, 
				'message' => "자동 발급 처리 완료"
			];
		} catch(\Exception $e) {
			$result = [
				'success' => false,
				'message' => "자동 발급 처리 중 오류: " . $e->getMessage()
			];
		}
		
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	break;

	case 'processExpired' :
		// 유효기간 만료 처리 (매일 실행)
		// 유효기간 종료일에 맞춰서 expiryFl과 state 변경
		set_time_limit(0);
		
		try {
			$processResult = $eventMileage->processExpiredEventMileage();
			$result = [
				'success' => true,
				'message' => "유효기간 만료 처리 완료",
				'processed' => $processResult['processed'],
				'totalDeducted' => $processResult['totalDeducted'],
				'memberCount' => $processResult['memberCount']
			];
		} catch(\Exception $e) {
			$result = [
				'success' => false,
				'message' => "유효기간 만료 처리 중 오류: " . $e->getMessage()
			];
		}
		
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	break;

	case 'eventMileageDownLink' :
		try {
			$campaignSno = !empty($_POST['campaignSno']) ? intval($_POST['campaignSno']) : 0;
			$memId = !empty($_POST['memId']) ? trim($_POST['memId']) : '';
			
			//$campaignSno = 21;
			//$memId = 'test123';

			// 직접발급 처리
			$eventMileage->processDirectCampaignPay($campaignSno, $memId);
			
			$result = [
				'success' => true,
				'msg' => '쇼핑지원금이 지급되었습니다.'
			];
		} catch(\Exception $e) {
			$result = [
				'success' => false,
				'msg' => $e->getMessage()
			];
		}
		
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	break;

	case 'useableEventMileage' :
		try {
			$memberId = !empty($_POST['member_id']) ? trim($_POST['member_id']) : '';
			$totalOrderPrice = !empty($_POST['totalOrderPrice']) ? intval($_POST['totalOrderPrice']) : 0;
			$useableEventMileage = $eventMileage->getUsableEventMileage($memberId, $totalOrderPrice);
			$result = [
				'code' => 200,
				'message' => 'OK',
				'data' => [
					'useableEventMileage' => $useableEventMileage
				]
			];
		} catch(\Exception $e) {
			$result = [
				'code' => 400,
				'message' => $e->getMessage(),
				'data' => null
			];
		}
		
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	break;

	case 'getHistory' :
		try {
			$params = [
				'memId' => !empty($_POST['member_id']) ? trim($_POST['member_id']) : ($_GET['member_id'] ?? ''),
				'page' => !empty($_POST['page']) ? intval($_POST['page']) : (!empty($_GET['page']) ? intval($_GET['page']) : 1),
				'limit' => !empty($_POST['limit']) ? intval($_POST['limit']) : (!empty($_GET['limit']) ? intval($_GET['limit']) : 10)
			];

			if (empty($params['memId'])) {
                throw new \Exception('회원 아이디가 필요합니다.');
            }

			$data = $eventMileage->getEventMileageHistory($params);
			$result = [
				'code' => 200,
				'message' => 'OK',
				'data' => $data
			];
		} catch(\Exception $e) {
			$result = [
				'code' => 400,
				'message' => $e->getMessage(),
				'data' => null
			];
		}

		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	break;
	case 'processPendingOrderMileage' : 
		$eventMileage->processPendingOrderMileage();
	break;
	case 'restoreMileageByRefund' :
		$eventMileage->restoreMileageByRefund();
	break;
}
