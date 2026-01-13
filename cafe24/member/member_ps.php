<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;
use Database\DB;
use Framework\Debug\Exception\AlertRedirectException;

try {
	$campaign = new EventMileage();
	
	$_GET = array_merge($_GET, $_POST);
	switch ($_GET['mode']) {
		case 'campaignRegister':
			try {
				$campaignCode = $campaign->registerCampaign();
				echo "<script>alert('등록되었습니다.'); parent.location.href='./event_mileage_campaign_list.php';</script>";
				exit;
			} catch (Exception $e) {
				echo "<script>alert('등록 중 오류가 발생했습니다: " . $e->getMessage() . "'); parent.location.reload();</script>";
				exit;
			}
		break;
		
		case 'event_delete':
			try {
				if(!empty($_POST['campaignSno']) && is_array($_POST['campaignSno'])) {
					$campaign->deleteCampaign($_POST['campaignSno']);
					echo "<script>alert('삭제되었습니다.'); parent.location.reload();</script>";
				} else {
					echo "<script>alert('선택된 항목이 없습니다.'); parent.location.reload();</script>";
				}
				exit;
			} catch (Exception $e) {
				echo "<script>alert('삭제 중 오류가 발생했습니다: " . $e->getMessage() . "'); parent.location.reload();</script>";
				exit;
			}
		break;
	}

	gd_debug($_POST);
	exit;
} catch(Exception $e) {
	echo "<script>alert('오류가 발생했습니다: " . $e->getMessage() . "'); parent.location.reload();</script>";
	exit;
}

?>

