<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;
use Database\DB;
use Member\Member;
use Framework\Debug\Exception\AlertRedirectException;

try {
	$campaign = new EventMileage();
	
	$_GET = array_merge($_GET, $_POST);

	switch ($_GET['mode']) {
		case 'campaignRegister':
			try {
				// 자동 발급 모드일 때 searchQuery가 POST에 없으면 기존 데이터에서 가져와서 보존
				$paymentFl = $_POST['paymentFl'] ?? '';
				if($paymentFl == 'auto' && empty($_POST['searchQuery'])) {
					$sno = !empty($_POST['sno']) ? intval($_POST['sno']) : 0;
					if($sno > 0) {
						// 기존 캠페인 데이터 조회
						$existingCampaign = $campaign->getCampaign($sno);
						if($existingCampaign && !empty($existingCampaign['searchQuery'])) {
							// 기존 searchQuery를 POST에 추가하여 보존
							$_POST['searchQuery'] = $existingCampaign['searchQuery'];
						}
					}
				}
				
				$result = $campaign->saveCampaign();
				$message = !empty($_POST['sno']) ? '수정되었습니다.' : '등록되었습니다.';
				$messageEscaped = addslashes($message);
				if($_GET['sno']) {
					echo "<script>alert('{$messageEscaped}'); parent.location.href='./event_mileage_campaign_view.php?sno=".$_GET['sno']."';</script>";
				}else {
					echo "<script>alert('{$messageEscaped}'); parent.location.href='./event_mileage_campaign_list.php';</script>";
				}
				exit;
			} catch (Exception $e) {
				$message = !empty($_POST['sno']) ? '수정 중 오류가 발생했습니다: ' : '등록 중 오류가 발생했습니다: ';
				$errorMessage = addslashes($message . $e->getMessage());
				echo "<script>alert('{$errorMessage}'); parent.location.reload();</script>";
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
				$errorMessage = addslashes('삭제 중 오류가 발생했습니다: ' . $e->getMessage());
				echo "<script>alert('{$errorMessage}'); parent.location.reload();</script>";
				exit;
			}
		break;

		case 'searchMemberPay':
			try {
				$member = new Member();
				$campaignSno = intval($_GET['campaignSno'] ?? $_POST['campaignSno'] ?? 0);
				$memberExceptFl = $_GET['memberExceptFl'] ?? $_POST['memberExceptFl'] ?? '';
				
				if($campaignSno <= 0) {
					throw new Exception('캠페인 번호가 없습니다.');
				}
				
				// 회원 검색 (검색 조건으로)
				$memberResult = $member->getMemberList();
				$memberList = $memberResult['memberList'] ?? [];
				
				// memId 배열 추출
				$memIds = array_column($memberList, 'memId');
				
				if(empty($memIds)) {
					echo "<script>alert('검색된 회원이 없습니다.'); parent.location.reload();</script>";
					exit;
				}
				
				// 지급 처리
				$memCntData = $campaign->checkCampaignMemberPay($memIds, $campaignSno, $memberExceptFl);
				
				if($memberExceptFl == 'y') {
					$message = "대상 회원중 {$memCntData['exceptCnt']}명을 제외한 {$memCntData['realPayCnt']}명에게 지급되었습니다.";
				} else {
					$message = "{$memCntData['realPayCnt']}명에게 지급되었습니다.";
				}
				
				echo "<script>alert('{$message}'); parent.location.href='./event_mileage_campaign_list.php';</script>";
				exit;
			} catch (Exception $e) {
				echo "<script>alert('지급 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "'); parent.location.reload();</script>";
				exit;
			}
		break;

		case 'checkMemberPay':
			try {
				$campaignSno = intval($_GET['campaignSno'] ?? $_POST['campaignSno'] ?? 0);
				$memberExceptFl = $_GET['memberExceptFl'] ?? $_POST['memberExceptFl'] ?? '';
				$chk = $_GET['chk'] ?? $_POST['chk'] ?? [];
				
				if($campaignSno <= 0) {
					throw new Exception('캠페인 번호가 없습니다.');
				}
				
				if(empty($chk) || !is_array($chk)) {
					echo "<script>alert('선택된 회원이 없습니다.'); parent.location.reload();</script>";
					exit;
				}
				
				// 지급 처리
				$memCntData = $campaign->checkCampaignMemberPay($chk, $campaignSno, $memberExceptFl);
				
				if($memberExceptFl == 'y') {
					$message = "대상 회원중 {$memCntData['exceptCnt']}명을 제외한 {$memCntData['realPayCnt']}명에게 지급되었습니다.";
				} else {
					$message = "{$memCntData['realPayCnt']}명에게 지급되었습니다.";
				}
				
				echo "<script>alert('{$message}'); parent.location.href='./event_mileage_campaign_list.php';</script>";
				exit;
			} catch (Exception $e) {
				echo "<script>alert('지급 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "'); parent.location.reload();</script>";
				exit;
			}
		break;

		case 'excelUploadParse':
			// 엑셀 파일 파싱 (회원ID와 금액 추출)
			try {
				if(empty($_FILES['memberExcel']['tmp_name'])) {
					echo json_encode(['success' => false, 'message' => '파일이 업로드되지 않았습니다.']);
					exit;
				}
				
				$file = $_FILES['memberExcel'];
				$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
				
				// 파일 정보 확인
				if(!file_exists($file['tmp_name'])) {
					echo json_encode(['success' => false, 'message' => '임시 파일이 존재하지 않습니다.']);
					exit;
				}
				
				$excelData = [];
				
				// CSV 파일 처리
				if($fileExtension == 'csv') {
					$handle = fopen($file['tmp_name'], 'r');
					if($handle === false) {
						echo json_encode(['success' => false, 'message' => '파일을 열 수 없습니다.']);
						exit;
					}
					
					// UTF-8 BOM 제거
					$bom = fread($handle, 3);
					if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
						rewind($handle);
					}
					
					// 헤더 건너뛰기
					$header = fgetcsv($handle);
					
					while (($row = fgetcsv($handle)) !== FALSE) {
						if(count($row) >= 2) {
							$memId = trim($row[0]);
							$amount = trim($row[1]);
							
							// 숫자와 음수 기호만 추출 (쉼표, 원화 기호 등 제거, 음수 기호는 보존)
							$isNegative = (strpos($amount, '-') !== false);
							$amount = preg_replace('/[^0-9]/', '', $amount);
							$amount = intval($amount);
							if($isNegative && $amount > 0) {
								$amount = -$amount; // 음수로 변환
							}
							
							if(!empty($memId) && $amount != 0) {
								$excelData[] = [
									'memId' => $memId,
									'amount' => $amount
								];
							}
						}
					}
					
					fclose($handle);
				} 
				// 엑셀 파일(.xls, .xlsx) 처리 - PhpSpreadsheet 사용
				else if(in_array($fileExtension, ['xls', 'xlsx'])) {
					// PhpSpreadsheet 라이브러리 확인
					if(class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
						$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
						$worksheet = $spreadsheet->getActiveSheet();
						$highestRow = $worksheet->getHighestRow();
						
						// 헤더 건너뛰고 2번째 행부터 읽기
						for($row = 2; $row <= $highestRow; $row++) {
							$memId = trim($worksheet->getCell('A' . $row)->getValue());
							$amount = $worksheet->getCell('B' . $row)->getValue();
							
							// 숫자와 음수 기호만 추출 (문자열로 변환 후 처리, 음수 기호는 보존)
							$amountStr = (string)$amount;
							$isNegative = (strpos($amountStr, '-') !== false);
							$amount = preg_replace('/[^0-9]/', '', $amountStr);
							$amount = intval($amount);
							if($isNegative && $amount > 0) {
								$amount = -$amount; // 음수로 변환
							}
							
							if(!empty($memId) && $amount != 0) {
								$excelData[] = [
									'memId' => $memId,
									'amount' => $amount
								];
							}
						}
					} else {
						// PhpSpreadsheet가 없는 경우 CSV 변환 안내
						echo json_encode([
							'success' => false, 
							'message' => '엑셀 파일(.xls, .xlsx)을 읽을 수 없습니다. PhpSpreadsheet 라이브러리가 필요합니다. Composer를 실행하여 설치해주세요: composer install'
						]);
						exit;
					}
				} else {
					echo json_encode(['success' => false, 'message' => '지원하지 않는 파일 형식입니다. CSV 또는 엑셀 파일(.xls, .xlsx)만 업로드 가능합니다.']);
					exit;
				}
				
				if(empty($excelData)) {
					echo json_encode([
						'success' => false, 
						'message' => '파일에서 회원 정보를 읽을 수 없습니다. 파일 형식을 확인해주세요. (첫 번째 행은 헤더, 두 번째 행부터 회원ID와 금액 입력)'
					]);
					exit;
				}
				
				echo json_encode(['success' => true, 'data' => $excelData, 'count' => count($excelData)]);
				exit;
			} catch (Exception $e) {
				echo json_encode(['success' => false, 'message' => '파일 처리 중 오류: ' . $e->getMessage()]);
				exit;
			}
		break;

		case 'excelUploadPay':
			try {
				$campaignSno = intval($_POST['campaignSno'] ?? $_POST['sno'] ?? 0);
				$memberExceptFl = $_POST['memberExceptFl'] ?? '';
				$excelDataJson = $_POST['excelData'] ?? '';
				
				if($campaignSno <= 0) {
					throw new Exception('캠페인 번호가 없습니다.');
				}
				
				if(empty($excelDataJson)) {
					echo "<script>alert('엑셀 데이터가 없습니다.'); parent.location.reload();</script>";
					exit;
				}
				
				$excelData = json_decode($excelDataJson, true);
				if(empty($excelData) || !is_array($excelData)) {
					echo "<script>alert('엑셀 데이터 형식이 올바르지 않습니다.'); parent.location.reload();</script>";
					exit;
				}
				
				// 지급 처리 (회원별 금액이 다를 수 있음)
				$memCntData = $campaign->checkCampaignMemberPayFromExcel($excelData, $campaignSno, $memberExceptFl);
				
				if($memberExceptFl == 'y') {
					$message = "대상 회원중 {$memCntData['exceptCnt']}명을 제외한 {$memCntData['realPayCnt']}명에게 지급되었습니다.";
				} else {
					$message = "{$memCntData['realPayCnt']}명에게 지급되었습니다.";
				}
				
				echo "<script>alert('{$message}'); parent.location.href='./event_mileage_campaign_view.php?sno={$campaignSno}';</script>";
				exit;
			} catch (Exception $e) {
				echo "<script>alert('지급 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "'); parent.location.reload();</script>";
				exit;
			}
		break;

		case 'autoCheckMemberPay':
			try {
				// 자동 발급 설정 저장
				$campaignSno = intval($_POST['sno'] ?? 0);
				if($campaignSno <= 0) {
					throw new Exception('캠페인 번호가 없습니다.');
				}
				
				// 기존 캠페인 데이터 조회
				$existingCampaign = $campaign->getCampaign($campaignSno);
				if(!$existingCampaign) {
					throw new Exception('캠페인을 찾을 수 없습니다.');
				}
				
				// 지급 여부 확인
				$campaignPayFl = $existingCampaign['campaignPayFl'] ?? 'n';
				$isPaid = ($campaignPayFl == 'y');
				
				// 회원 검색 조건을 JSON으로 변환 (frmSearchBase 폼의 데이터 사용)
				// getMemberList()에서 사용하는 검색 조건들을 저장 (limit 제외)
				$searchParams = [];
				
				// key (검색 필드)
				if(isset($_POST['key'])) {
					$searchParams['key'] = $_POST['key'];
				}
				
				// keyword (검색어)
				if(isset($_POST['keyword']) && trim($_POST['keyword']) !== '') {
					$searchParams['keyword'] = trim($_POST['keyword']);
				}
				
				// entryDt (회원가입일)
				if(isset($_POST['entryDt']) && is_array($_POST['entryDt'])) {
					$entryDtFiltered = array_filter($_POST['entryDt'], function($value) {
						return trim($value) !== '';
					});
					if(!empty($entryDtFiltered)) {
						$searchParams['entryDt'] = array_values($entryDtFiltered);
					}
				}

				// orderDt (주문일)
				if(isset($_POST['orderDt']) && is_array($_POST['orderDt'])) {
					$orderDtFiltered = array_filter($_POST['orderDt'], function($value) {
						return trim($value) !== '';
					});
					if(!empty($orderDtFiltered)) {
						$searchParams['orderDt'] = array_values($orderDtFiltered);
					}
				}
				
				// groupNo (회원등급)
				if(isset($_POST['groupNo']) && is_array($_POST['groupNo'])) {
					$groupNosFiltered = array_filter(array_map('intval', $_POST['groupNo']), function($val) {
						return $val > 0;
					});
					if(!empty($groupNosFiltered)) {
						$searchParams['groupNo'] = array_values($groupNosFiltered);
					}
				}
				
				// 검색 조건이 없어도 빈 배열로 저장 (전체 회원 대상)
				// JSON으로 인코딩하여 searchQuery에 저장
				$searchQueryJson = json_encode($searchParams, JSON_UNESCAPED_UNICODE);
				
				if($isPaid) {
					// 지급 후에는 searchQuery만 업데이트
					$campaign->updateSearchQuery($campaignSno, $searchQueryJson);
				} else {
					// 지급 전에는 모든 설정 저장
					// 자동 발급일 때는 memberExceptFl을 무조건 'y'로 설정
					$_POST['memberExceptFl'] = 'y';
					$_POST['searchQuery'] = $searchQueryJson;
					$result = $campaign->saveCampaign();
				}
				
				echo "<script>alert('설정 저장이 완료되었습니다.'); parent.location.href='./event_mileage_campaign_view.php?sno={$campaignSno}';</script>";
				exit;
			} catch (Exception $e) {
				echo "<script>alert('설정 저장 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "'); parent.location.reload();</script>";
				exit;
			}
		break;
	}

	gd_debug($_POST);
	exit;
} catch(Exception $e) {
	$errorMessage = addslashes('오류가 발생했습니다: ' . $e->getMessage());
	echo "<script>alert('{$errorMessage}'); parent.location.reload();</script>";
	exit;
}

?>

