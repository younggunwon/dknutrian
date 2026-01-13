<?php

header("Access-Control-Allow-Origin: *");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Database\DB;
use Member\Member;
use EventMileage\EventMileage;

$member = new Member();

switch($_GET['mode']) {
	case 'saveGroup':
        $result = $member->saveGroup();
        echo json_encode($result);
    break;

	case 'update':
		$member->updateMember($_GET['year'], $_GET['date']);
	break;
	case 'check_recommend' :
		$result = [];
		$result['result'] = true;
		$result['data'] = $member->checkRecommend($_GET['memId'], $_GET['recommendId']);
		
		echo json_encode($result);
	break;

	case 'get_my_freiend' :
		$result = [];
		$result['result'] = true;
		$result['friendCnt'] = $member->getMyFreind($_GET['memId']);
		
		echo json_encode($result);
	break;

	case 'saveRecommSetting' :
		$member->saveRecommSetting();
	break;


	case 'checkBoardRewardMember' :
		echo $member->checkBoardRewardMember($_GET['board_no'], $_GET['no']);
	break;

	case 'boardReward' :
		$member->boardReward();
	break;

	case 'updateMemberGrade' :
		$result = $member->updateMemberGradeWebhook();
		echo json_encode($result);
	break;

	case 'registMember' :
		$result = $member->registMemberWebhook();
		echo json_encode($result);
	break;

	/*
	case 'getBoardReward' :
		$result = $member->getBoardReward($memberId);
		echo json_encode($result, true);
	break;
	*/
}
