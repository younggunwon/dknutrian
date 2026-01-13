<?php
namespace EventMileage;

use Database\DB;
use Cafe24\Cafe24;
use Request;
use Page\Page;

class EventMileage
{
	public $db;
	public $page;

	public function __construct() {
		$this->db = new DB();
	}

	// 문자열 이스케이프 (SQL 인젝션 방지)
	private function escape($value) {
		if($value === null || $value === '') {
			return null;
		}
		// DB 연결을 통해 이스케이프 처리
		$db_conn = db_connect();
		return mysqli_real_escape_string($db_conn, $value);
	}

	// 캠페인 조회 (sno로)
	public function getCampaign($sno) {
		$sno = intval($sno);
		if($sno <= 0) {
			return null;
		}
		
		$sql = "SELECT * FROM wg_eventMileageCampaign WHERE sno = {$sno}";
		$result = $this->db->query_fetch($sql);
		
		return !empty($result) ? $result[0] : null;
	}

	// searchQuery만 업데이트 (자동 발급 지급 후)
	public function updateSearchQuery($sno, $searchQueryJson) {
		$sno = intval($sno);
		if($sno <= 0) {
			throw new \Exception('캠페인 번호가 없습니다.');
		}
		
		$searchQuery = null;
		if(!empty($searchQueryJson)) {
			$searchQuery = "'" . $this->escape($searchQueryJson) . "'";
		} else {
			$searchQuery = "NULL";
		}
		
		$sql = "UPDATE wg_eventMileageCampaign SET 
			searchQuery = {$searchQuery},
			modDt = now()
			WHERE sno = {$sno}";
		
		$this->db->query($sql);
		return $sno;
	}

	// 캠페인 저장 (등록/수정)
	public function saveCampaign() {
		$sno = !empty($_POST['sno']) ? intval($_POST['sno']) : 0;
		
		$campaignNm = $this->escape($_POST['campaignNm'] ?? '');
		$campaignDes = $this->escape($_POST['campaignDes'] ?? '');
		$eventMileage = intval($_POST['eventMileage'] ?? 0);
		$mileageLimit = floatval($_POST['mileageLimit'] ?? 0);
		$expiryDaysFl = $this->escape($_POST['expiryDaysFl'] ?? '');
		$expiryStartDate = !empty($_POST['expiryStartDate']) ? "'" . $this->escape($_POST['expiryStartDate']) . "'" : "NULL";
		$expiryDate = !empty($_POST['expiryDate']) ? "'" . $this->escape($_POST['expiryDate']) . "'" : "NULL";
		$expiryDays = !empty($_POST['expiryDays']) ? intval($_POST['expiryDays']) : "NULL";
		$payStartDate = $this->escape($_POST['payStartDate'] ?? '');
		$payEndDate = $this->escape($_POST['payEndDate'] ?? '');
		$paymentFl = $this->escape($_POST['paymentFl'] ?? '');
		//$addSmsTypeFl = $this->escape($_POST['addSmsTypeFl'] ?? 'n');
		//$expirySmsTypeFl = $this->escape($_POST['expirySmsTypeFl'] ?? 'n');
		$memberAlwaysExceptFl = $this->escape($_POST['memberAlwaysExceptFl'] ?? 'n');
		$memberAlwaysExceptLimitType = (isset($_POST['memberAlwaysExceptLimitType']) && $_POST['memberAlwaysExceptLimitType'] == 'y') ? "'y'" : "'n'";
		$memberAlwaysExceptLimit = !empty($_POST['memberAlwaysExceptLimit']) ? intval($_POST['memberAlwaysExceptLimit']) : "NULL";
		
		// 자동발급 관련 필드 (자동발급일 때만)
		$autoPayFl = $this->escape($_POST['autoPayFl'] ?? null);
		$autoPayFl = !empty($autoPayFl) ? "'{$autoPayFl}'" : "NULL";
		
		$weekDay = null;
		if(!empty($_POST['weekDay']) && is_array($_POST['weekDay'])) {
			// 중복 제거 (체크박스와 hidden input이 모두 전송될 수 있음)
			$weekDayArray = array_unique(array_filter($_POST['weekDay']));
			if(!empty($weekDayArray)) {
				$weekDay = "'" . $this->escape(implode(',', $weekDayArray)) . "'";
			} else {
				$weekDay = "NULL";
			}
		} else {
			$weekDay = "NULL";
		}
		
		$monthDay = !empty($_POST['monthDay']) ? intval($_POST['monthDay']) : "NULL";
		
		$yearDate = null;
		if(!empty($_POST['yearDateMonth']) && !empty($_POST['yearDateDay'])) {
			$yearDateMonth = str_pad(intval($_POST['yearDateMonth']), 2, '0', STR_PAD_LEFT);
			$yearDateDay = str_pad(intval($_POST['yearDateDay']), 2, '0', STR_PAD_LEFT);
			$yearDate = "'{$yearDateMonth}-{$yearDateDay}'";
		} else {
			$yearDate = "NULL";
		}
		
		$searchQuery = null;
		if(!empty($_POST['searchQuery'])) {
			$searchQuery = "'" . $this->escape($_POST['searchQuery']) . "'";
		} else {
			$searchQuery = "NULL";
		}
		
		//$input_reserve_time = !empty($_POST['input_reserve_time']) ? intval($_POST['input_reserve_time']) : "NULL";
		//$remove_reserve_day = !empty($_POST['remove_reserve_day']) ? intval($_POST['remove_reserve_day']) : "NULL";
		//$remove_reserve_time = !empty($_POST['remove_reserve_time']) ? intval($_POST['remove_reserve_time']) : "NULL";

		if($sno > 0) {
			// UPDATE
			$sql = "UPDATE wg_eventMileageCampaign SET
				campaignNm = '{$campaignNm}',
				campaignDes = '{$campaignDes}',
				eventMileage = {$eventMileage},
				mileageLimit = {$mileageLimit},
				expiryDaysFl = '{$expiryDaysFl}',
				expiryStartDate = {$expiryStartDate},
				expiryDate = {$expiryDate},
				expiryDays = {$expiryDays},
				payStartDate = '{$payStartDate}',
				payEndDate = '{$payEndDate}',
				paymentFl = '{$paymentFl}',
				autoPayFl = {$autoPayFl},
				weekDay = {$weekDay},
				monthDay = {$monthDay},
				yearDate = {$yearDate},
				searchQuery = {$searchQuery},
				memberAlwaysExceptFl = '{$memberAlwaysExceptFl}',
				memberAlwaysExceptLimitType = {$memberAlwaysExceptLimitType},
				memberAlwaysExceptLimit = {$memberAlwaysExceptLimit},
				modDt = now()
				WHERE sno = {$sno}";
			
			$this->db->query($sql);
			return $sno;
		} else {
			// INSERT
			// 캠페인 코드 생성 (현재 날짜/시간 기반)
			$campaignCode = date('ymdHis') . sprintf('%04d', rand(0, 9999));
			
			$sql = "INSERT INTO wg_eventMileageCampaign SET
				campaignNm = '{$campaignNm}',
				campaignDes = '{$campaignDes}',
				campaignCode = '{$campaignCode}',
				eventMileage = {$eventMileage},
				mileageLimit = {$mileageLimit},
				expiryDaysFl = '{$expiryDaysFl}',
				expiryStartDate = {$expiryStartDate},
				expiryDate = {$expiryDate},
				expiryDays = {$expiryDays},
				payStartDate = '{$payStartDate}',
				payEndDate = '{$payEndDate}',
				paymentFl = '{$paymentFl}',
				autoPayFl = {$autoPayFl},
				weekDay = {$weekDay},
				monthDay = {$monthDay},
				yearDate = {$yearDate},
				searchQuery = {$searchQuery},
				memberAlwaysExceptFl = '{$memberAlwaysExceptFl}',
				memberAlwaysExceptLimitType = {$memberAlwaysExceptLimitType},
				memberAlwaysExceptLimit = {$memberAlwaysExceptLimit},
				regDt = now()";

			$this->db->query($sql);
			return $campaignCode;
		}
	}

	// 캠페인 목록 조회
	public function getCampaignList($params = []) {
		$where = [];
		
		if(!empty($params['campaignNm'])) {
			$campaignNm = $this->escape($params['campaignNm']);
			$where[] = "campaignNm LIKE '%{$campaignNm}%'";
		}
		
		if(!empty($params['searchDate'][0]) && !empty($params['searchDate'][1])) {
			$startDate = $this->escape($params['searchDate'][0]);
			$endDate = $this->escape($params['searchDate'][1]);
			$where[] = "DATE(regDt) BETWEEN '{$startDate}' AND '{$endDate}'";
		}
		
		if(!empty($params['eventMileage'][0]) && !empty($params['eventMileage'][1])) {
			$startMileage = intval($params['eventMileage'][0]);
			$endMileage = intval($params['eventMileage'][1]);
			$where[] = "eventMileage BETWEEN {$startMileage} AND {$endMileage}";
		}
		
		if(!empty($params['paymentFl']) && $params['paymentFl'] != 'all') {
			$paymentFl = $this->escape($params['paymentFl']);
			$where[] = "paymentFl = '{$paymentFl}'";
		}
		
		// 유효기간 검색
		if(!empty($params['expiryDate'][0]) && !empty($params['expiryDate'][1])) {
			$startExpiryDate = $this->escape($params['expiryDate'][0]);
			$endExpiryDate = $this->escape($params['expiryDate'][1]);
			$where[] = "((expiryDaysFl = 'y' AND expiryDate BETWEEN '{$startExpiryDate}' AND '{$endExpiryDate}') OR expiryDaysFl != 'y')";
		}
		
		// 지급기간 검색
		if(!empty($params['payDate'][0]) && !empty($params['payDate'][1])) {
			$startPayDate = $this->escape($params['payDate'][0]);
			$endPayDate = $this->escape($params['payDate'][1]);
			$where[] = "payStartDate <= '{$endPayDate}' AND payEndDate >= '{$startPayDate}'";
		}
		
		$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
		
		$sql = "SELECT * FROM wg_eventMileageCampaign {$whereClause} ORDER BY sno DESC";
		$list = $this->db->query_fetch($sql);
		
		return $list;
	}

	// 캠페인 삭제
	public function deleteCampaign($campaignSnoArray) {
		if(empty($campaignSnoArray) || !is_array($campaignSnoArray)) {
			return false;
		}
		
		$snoList = implode(",", array_map('intval', $campaignSnoArray));
		$sql = "DELETE FROM wg_eventMileageCampaign WHERE sno IN ({$snoList})";
		$this->db->query($sql);
		
		return true;
	}

	// 회원 정보 조회 (memId로)
	private function getMemberInfo($memId) {
		$memId = $this->escape($memId);
		$sql = "SELECT * FROM wg_member WHERE memId = '{$memId}'";
		$result = $this->db->query_fetch($sql);
		return !empty($result) ? $result[0] : null;
	}

	// 유효기간 종료일 계산
	private function calculateExpiryDate($campaignData) {
		$expiryDaysFl = $campaignData['expiryDaysFl'] ?? '';
		$today = date('Y-m-d');
		
		switch($expiryDaysFl) {
			case 'dayDate':
				$expiryDays = intval($campaignData['expiryDays'] ?? 0);
				if($expiryDays > 0) {
					return date('Y-m-d', strtotime("+{$expiryDays} days"));
				}
				return $today;
				
			case 'monthEnd':
				$curYear = (int)date('Y');
				$curMonth = (int)date('m');
				return date("Y-m-d", mktime(0, 0, 0, $curMonth + 1, 0, $curYear));
				
			case 'yearEnd':
				return date('Y') . '-12-31';
				
			case 'y':
			default:
				return !empty($campaignData['expiryDate']) ? substr($campaignData['expiryDate'], 0, 10) : $today;
		}
	}

	// 유효기간 시작일 계산
	private function calculateExpiryStartDate($campaignData) {
		// 자동발급일 때는 지급기간의 시작일(payStartDate)을 사용
		if(isset($campaignData['paymentFl']) && $campaignData['paymentFl'] == 'auto') {
			if(!empty($campaignData['payStartDate'])) {
				return substr($campaignData['payStartDate'], 0, 10);
			}
			return date('Y-m-d');
		}
		
		// 수동/엑셀/직접발급일 때는 expiryStartDate 사용
		if(!empty($campaignData['expiryStartDate'])) {
			return substr($campaignData['expiryStartDate'], 0, 10);
		}
		return date('Y-m-d');
	}


	// 캠페인으로 이벤트 마일리지 지급
	public function campaignEventMileageAdd($memId, $campaignData) {
		$memId = $this->escape($memId);
		$campaignSno = intval($campaignData['sno'] ?? 0);
		if($campaignSno <= 0) {
			throw new \Exception('캠페인 번호가 없습니다.');
		}
		
		$eventMileage = intval($campaignData['eventMileage'] ?? 0);
		if($eventMileage <= 0) {
			throw new \Exception('지급 금액이 없습니다.');
		}
		
		$campaignNm = $this->escape($campaignData['campaignNm'] ?? '');
		$mileageLimit = floatval($campaignData['mileageLimit'] ?? 0);
		
		// 유효기간 계산
		$expiryStartDate = $this->calculateExpiryStartDate($campaignData);
		$expiryDate = $this->calculateExpiryDate($campaignData);
		
		// 자동 발급일 때는 INSERT 직전에 중복 체크 (동시성 문제 방지)
		if(isset($campaignData['paymentFl']) && $campaignData['paymentFl'] == 'auto') {
			$today = date('Y-m-d');
			$duplicateCheckSql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
				WHERE campaignSno = {$campaignSno} 
				AND memId = '{$memId}' 
				AND mode = 'add' 
				AND DATE(regDt) = '{$today}'";
			$duplicateResult = $this->db->query_fetch($duplicateCheckSql);
			if(!empty($duplicateResult) && $duplicateResult[0]['cnt'] > 0) {
				throw new \Exception('오늘 이미 지급된 회원입니다.');
			}
		}
		
		// 회원의 현재 유효한 이벤트 마일리지 합계 계산 (getUsableEventMileage 함수 활용)
		$beforeEventMileage = $this->getUsableEventMileage($memId);
		$afterEventMileage = $beforeEventMileage + $eventMileage; // 지급이므로 더함
		
		// 이벤트 마일리지 지급 내역 추가 (beforeEventMileage, afterEventMileage 포함)
		$sql = "INSERT INTO wg_eventMileage SET 
			memId='{$memId}', 
			eventMileage={$eventMileage},
			beforeEventMileage={$beforeEventMileage},
			afterEventMileage={$afterEventMileage},
			expiryStartDate='{$expiryStartDate}', 
			expiryDate='{$expiryDate}',
			mode='add', 
			expiryFl='y', 
			state='n',
			reasonCd='1029',
			contents='{$campaignNm}', 
			memberUpdateFl='n', 
			campaignSno={$campaignSno},
			mileageLimit={$mileageLimit},
			regDt=now()";
		
		$this->db->query($sql);
		$sno = $this->db->insert_id();
		
		// memberUpdateFl='y'로 업데이트
		$updateSql = "UPDATE wg_eventMileage SET memberUpdateFl='y', modDt=now() WHERE sno = {$sno}";
		$this->db->query($updateSql);
		
		return $sno;
	}

	// 캠페인으로 이벤트 마일리지 차감
	public function campaignEventMileageRemove($memId, $campaignData) {
		$memId = $this->escape($memId);
		$campaignSno = intval($campaignData['sno'] ?? 0);
		$orderId = $campaignData['orderId'] ?? $campaignData['order_id'] ?? '';

		// 중복 차감 방지: 해당 주문번호로 이미 차감된 내역이 있는지 확인
		if (!empty($orderId)) {
			$checkSql = "SELECT COUNT(*) as cnt FROM wg_eventMileage WHERE orderId = '{$this->escape($orderId)}' AND mode = 'remove' LIMIT 1";
			$checkResult = $this->db->query_fetch($checkSql);
			if (intval($checkResult[0]['cnt'] ?? 0) > 0) {
				return 0; // 이미 처리됨
			}
		}

		if($campaignSno <= 0) {
			throw new \Exception('캠페인 번호가 없습니다.');
		}
		
		// 차감 금액을 양수로 저장 (mode='remove'로 차감임을 구분)
		$eventMileage = abs(intval($campaignData['eventMileage'] ?? 0)); // 절대값으로 변환하여 양수로 저장
		if($eventMileage <= 0) {
			throw new \Exception('차감 금액이 없습니다.');
		}
		
		$campaignNm = $this->escape($campaignData['campaignNm'] ?? '');
		$mileageLimit = floatval($campaignData['mileageLimit'] ?? 0);

		// 1. 해당 회원의 유효한 쇼핑지원금 지급(add) 내역 조회
		// (1순위: 동일 캠페인, 2순위: 최근 등록일순)
		$today = date('Y-m-d');
		$sql = "SELECT * FROM wg_eventMileage 
				WHERE memId = '{$memId}' 
				AND mode = 'add' 
				AND expiryFl = 'y' 
				AND expiryDate >= '{$today}'
				ORDER BY (campaignSno = {$campaignSno}) DESC, regDt DESC, sno DESC";
		$grants = $this->db->query_fetch($sql);

		$remainingToDeduct = $eventMileage;
		$lastSno = 0;

		foreach ($grants as $grant) {
			if ($remainingToDeduct <= 0) break;

			$grantSno = intval($grant['sno']);
			$grantedAmount = intval($grant['eventMileage']);
			
			// 해당 지급건의 현재 잔액 계산
			$usageSql = "SELECT SUM(eventMileage) as usedAmount FROM wg_eventMileage 
                         WHERE parentSno = {$grantSno} AND mode = 'remove'";
			$usageResult = $this->db->query_fetch($usageSql);
			$usedAmount = intval($usageResult[0]['usedAmount'] ?? 0);
			$grantBalance = $grantedAmount - $usedAmount;

			if ($grantBalance <= 0) continue;

			// 이 지급건에서 차감할 금액
			$deductAmountFromGrant = min($remainingToDeduct, $grantBalance);

			if ($deductAmountFromGrant > 0) {
				// 현재 전체 합계 계산 (getUsableEventMileage 함수 활용)
				$beforeTotal = $this->getUsableEventMileage($memId);
				$afterTotal = $beforeTotal - $deductAmountFromGrant;

				// 차감 내역 추가 (지급건과 연결 및 유효기간 동기화)
				$insertSql = "INSERT INTO wg_eventMileage SET 
					memId='{$memId}', 
					eventMileage={$deductAmountFromGrant},
					beforeEventMileage={$beforeTotal},
					afterEventMileage={$afterTotal},
					mode='remove', 
					parentSno={$grantSno}, 
					campaignSno={$campaignSno},
					orderId='{$this->escape($orderId)}',
					reasonCd='1029',
					contents='{$campaignNm} (캠페인 차감)' . ($orderId ? ' (' . $orderId . ')' : ''), 
					memberUpdateFl='y',
					expiryFl='y',
					expiryDate='{$grant['expiryDate']}',
					state='n',
					regDt=now()";
				
				$this->db->query($insertSql);
				$lastSno = $this->db->insert_id();
				$remainingToDeduct -= $deductAmountFromGrant;

				// 해당 지급건의 전액을 모두 사용했는지 확인하여 상태 변경
				if (($grantBalance - $deductAmountFromGrant) <= 0) {
					$completeSql = "UPDATE wg_eventMileage SET state = 'complete', modDt = now() WHERE sno = {$grantSno}";
					$this->db->query($completeSql);
				}
			}
		}

		// 만약 지급 내역이 없거나 부족해서 남은 차감 금액이 있다면, 연결 없이 단독 차감 레코드를 생성 (마이너스 마일리지 허용)
		if ($remainingToDeduct > 0) {
			// 현재 전체 합계 계산 (getUsableEventMileage 함수 활용)
			$beforeEventMileage = $this->getUsableEventMileage($memId);
			$afterEventMileage = $beforeEventMileage - $remainingToDeduct;

			$sql = "INSERT INTO wg_eventMileage SET 
				memId='{$memId}', 
				eventMileage={$remainingToDeduct},
				beforeEventMileage={$beforeEventMileage},
				afterEventMileage={$afterEventMileage},
				mode='remove', 
				orderId='{$this->escape($orderId)}',
				reasonCd='1029',
				contents='{$campaignNm} (잔여 차감분)' . ($orderId ? ' (' . $orderId . ')' : ''), 
				memberUpdateFl='y',
				expiryFl='y',
				expiryDate='2099-12-31',
				campaignSno={$campaignSno},
				regDt=now()";
			$this->db->query($sql);
			if (!$lastSno) $lastSno = $this->db->insert_id();
		}
		
		return $lastSno;
	}

	// 마일리지 지급 API 트리거 (비동기 호출)
	private function triggerMileagePayAPI() {
		// 비동기로 지급 API 호출 (즉시 지급이 필요한 경우)
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$url = $protocol . '://' . $host . '/api/member.php?mode=eventMileagePay';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 비동기 처리
		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		@curl_exec($ch);
		@curl_close($ch);
		
		return true;
	}

	// 캠페인 지급 여부 확인
	public function checkCampaign($campaignSno) {
		$campaignSno = intval($campaignSno);
		$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage WHERE campaignSno = {$campaignSno} AND mode = 'add'";
		$result = $this->db->query_fetch($sql);
		return (!empty($result) && $result[0]['cnt'] > 0) ? 'y' : 'n';
	}

	// 엑셀 데이터로 회원들에게 캠페인 지급 (회원별 금액이 다를 수 있음)
	public function checkCampaignMemberPayFromExcel($excelData, $campaignSno, $memberExceptFl = '') {
		if(empty($excelData) || !is_array($excelData)) {
			return ['totalCnt' => 0, 'realPayCnt' => 0, 'exceptCnt' => 0];
		}
		
		$campaignData = $this->getCampaign($campaignSno);
		if(!$campaignData) {
			throw new \Exception('캠페인을 찾을 수 없습니다.');
		}
		
		// 지급기간 확인
		$today = date('Y-m-d');
		if($campaignData['payStartDate'] > $today || $campaignData['payEndDate'] < $today) {
			throw new \Exception('지급기간이 아닙니다.');
		}
		
		$totalCnt = count($excelData);
		$exceptCnt = 0;
		$successMemIds = []; // 지급/차감 성공한 회원 ID 목록
		
		foreach($excelData as $data) {
			$memId = trim($data['memId'] ?? '');
			$customAmount = intval($data['amount'] ?? 0);
			
			if(empty($memId) || $customAmount == 0) {
				$exceptCnt++;
				continue; // 회원ID가 없거나 금액이 0이면 제외
			}
			
			// wg_member 테이블에 회원 존재 여부 확인 (가장 먼저 체크)
			$sql = "SELECT COUNT(*) as cnt FROM wg_member WHERE memId = '{$this->escape($memId)}'";
			$memberExistData = $this->db->query_fetch($sql);
			if(empty($memberExistData) || $memberExistData[0]['cnt'] == 0) {
				$exceptCnt++;
				continue; // 존재하지 않는 회원은 제외
			}
			
			// 음수면 차감, 양수면 지급
			$isDeduct = ($customAmount < 0);
			
			// 중복 체크 (지급일 때만 적용)
			if(!$isDeduct) {
				if($memberExceptFl == 'y') {
					if($campaignData['expiryDaysFl'] == 'dayDate' && !empty($campaignData['expiryDays'])) {
						$expiryDate = date('Y-m-d', strtotime("+{$campaignData['expiryDays']} days"));
					} else {
						$expiryDate = $this->calculateExpiryDate($campaignData);
					}
					$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
						WHERE campaignSno = {$campaignSno} AND expiryDate = '{$expiryDate}' AND memId = '{$this->escape($memId)}' AND mode = 'add'";
					$existData = $this->db->query_fetch($sql);
					if(!empty($existData) && $existData[0]['cnt'] > 0) {
						$exceptCnt++;
						continue;
					}
				}
				
				if($campaignData['memberAlwaysExceptFl'] == 'y') {
					$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
						WHERE campaignSno = {$campaignSno} AND memId = '{$this->escape($memId)}' AND mode = 'add'";
					$existData = $this->db->query_fetch($sql);
					if(!empty($existData) && $existData[0]['cnt'] > 0) {
						$exceptCnt++;
						continue;
					}
				}
				
				if($campaignData['memberAlwaysExceptLimitType'] == 'y' && $campaignData['memberAlwaysExceptLimit'] > 0) {
					$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
						WHERE campaignSno = {$campaignSno} AND memId = '{$this->escape($memId)}' AND mode = 'add'";
					$existData = $this->db->query_fetch($sql);
					if(!empty($existData) && $existData[0]['cnt'] >= $campaignData['memberAlwaysExceptLimit']) {
						$exceptCnt++;
						continue;
					}
				}
			}
			
			// 커스텀 금액으로 지급 또는 차감
			try {
				$campaignDataForPay = $campaignData;
				$campaignDataForPay['eventMileage'] = $customAmount; // 엑셀에서 지정한 금액 사용 (음수 포함)
				
				if($isDeduct) {
					// 차감 처리
					$this->campaignEventMileageRemove($memId, $campaignDataForPay);
				} else {
					// 지급 처리
					$this->campaignEventMileageAdd($memId, $campaignDataForPay);
				}
				$successMemIds[] = $memId; // 지급/차감 성공한 회원 ID 저장
			} catch(\Exception $e) {
				$exceptCnt++;
				continue;
			}
		}
		
		// campaignEventMileageAdd()와 campaignEventMileageRemove()에서 이미 memberUpdateFl='y' 업데이트를 처리함
		// 여기서는 추가 업데이트 불필요 (이미 연동 완료)
		
		// 캠페인 지급 여부 업데이트
		if($totalCnt - $exceptCnt > 0) {
			$this->db->query("UPDATE wg_eventMileageCampaign SET campaignPayFl = 'y' WHERE sno = {$campaignSno}");
		}
		
		return [
			'totalCnt' => $totalCnt,
			'realPayCnt' => $totalCnt - $exceptCnt,
			'exceptCnt' => $exceptCnt
		];
	}

	// 회원들에게 캠페인 지급
	public function checkCampaignMemberPay($memIds, $campaignSno, $memberExceptFl = '') {
		if(!is_array($memIds) || empty($memIds)) {
			return ['totalCnt' => 0, 'realPayCnt' => 0, 'exceptCnt' => 0];
		}
		
		$campaignData = $this->getCampaign($campaignSno);
		if(!$campaignData) {
			throw new \Exception('캠페인을 찾을 수 없습니다.');
		}
		
		// 지급기간 확인 (자동 발급 제외)
		if($campaignData['paymentFl'] != 'auto') {
			$today = date('Y-m-d');
			if($campaignData['payStartDate'] > $today || $campaignData['payEndDate'] < $today) {
				throw new \Exception('지급기간이 아닙니다.');
			}
		}
		
		$totalCnt = count($memIds);
		$exceptCnt = 0;
		$successMemIds = []; // 지급 성공한 회원 ID 목록
		
		foreach($memIds as $memId) {
			$memId = trim($memId);
			if(empty($memId)) {
				continue;
			}
			
			// wg_member 테이블에 회원 존재 여부 확인 (가장 먼저 체크)
			// 자동발급일 때는 이미 wg_member에서 조회했으므로 스킵
			if($campaignData['paymentFl'] != 'auto') {
				$sql = "SELECT COUNT(*) as cnt FROM wg_member WHERE memId = '{$this->escape($memId)}'";
				$memberExistData = $this->db->query_fetch($sql);
				if(empty($memberExistData) || $memberExistData[0]['cnt'] == 0) {
					$exceptCnt++;
					continue; // 존재하지 않는 회원은 제외
				}
			}
			
			// 본 캠페인에서 기존에 지급받은 회원 제외(유효기간 만료일 다를땐 지급) - memberExceptFl
			if($memberExceptFl == 'y') {
				if($campaignData['expiryDaysFl'] == 'dayDate' && !empty($campaignData['expiryDays'])) {
					$expiryDate = date('Y-m-d', strtotime("+{$campaignData['expiryDays']} days"));
				} else {
					$expiryDate = $this->calculateExpiryDate($campaignData);
				}
				$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
					WHERE campaignSno = {$campaignSno} AND expiryDate = '{$expiryDate}' AND memId = '{$this->escape($memId)}' AND mode = 'add'";
				$existData = $this->db->query_fetch($sql);
				if(!empty($existData) && $existData[0]['cnt'] > 0) {
					$exceptCnt++;
					continue;
				}
			}
			
			// 본 캠페인에서 기존에 지급받은 회원 제외(유효기간 만료일 달라도 지급 제외) - memberAlwaysExceptFl
			if($campaignData['memberAlwaysExceptFl'] == 'y') {
				$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
					WHERE campaignSno = {$campaignSno} AND memId = '{$this->escape($memId)}' AND mode = 'add'";
				$existData = $this->db->query_fetch($sql);
				if(!empty($existData) && $existData[0]['cnt'] > 0) {
					$exceptCnt++;
					continue;
				}
			}
			
			// 자동 발급일 때는 오늘 날짜에 이미 지급된 내역이 있으면 중복 제외
			if($campaignData['paymentFl'] == 'auto') {
				$today = date('Y-m-d');
				$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
					WHERE campaignSno = {$campaignSno} 
					AND memId = '{$this->escape($memId)}' 
					AND mode = 'add' 
					AND DATE(regDt) = '{$today}'";
				$existData = $this->db->query_fetch($sql);
				if(!empty($existData) && $existData[0]['cnt'] > 0) {
					$exceptCnt++;
					continue;
				}
			}
			
			// 최대 지급 횟수 제한 체크
			if($campaignData['memberAlwaysExceptLimitType'] == 'y' && $campaignData['memberAlwaysExceptLimit'] > 0) {
				$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
					WHERE campaignSno = {$campaignSno} AND memId = '{$this->escape($memId)}' AND mode = 'add'";
				$existData = $this->db->query_fetch($sql);
				if(!empty($existData) && $existData[0]['cnt'] >= $campaignData['memberAlwaysExceptLimit']) {
					$exceptCnt++;
					continue;
				}
			}
			
			// 이벤트 마일리지 지급
			try {
				$this->campaignEventMileageAdd($memId, $campaignData);
				$successMemIds[] = $memId; // 지급 성공한 회원 ID 저장
			} catch(\Exception $e) {
				// 지급 실패 시 로그만 남기고 계속 진행
				$exceptCnt++;
				continue;
			}
		}
		
		// campaignEventMileageAdd()에서 이미 memberUpdateFl='y' 업데이트를 처리함
		// 여기서는 추가 업데이트 불필요 (이미 연동 완료)
		
		// 캠페인 지급 여부 업데이트
		if($totalCnt - $exceptCnt > 0) {
			$this->db->query("UPDATE wg_eventMileageCampaign SET campaignPayFl = 'y' WHERE sno = {$campaignSno}");
		}
		
		return [
			'totalCnt' => $totalCnt,
			'realPayCnt' => $totalCnt - $exceptCnt,
			'exceptCnt' => $exceptCnt
		];
	}

	// 직접발급 캠페인 처리 (회원이 직접 요청)
	public function processDirectCampaignPay($campaignSno, $memId) {
		$campaignSno = intval($campaignSno);
		if($campaignSno <= 0) {
			throw new \Exception('캠페인 번호가 없습니다.');
		}
		
		$memId = trim($memId);
		if(empty($memId)) {
			throw new \Exception('회원 정보가 없습니다.');
		}
		
		// 캠페인 정보 조회
		$campaignData = $this->getCampaign($campaignSno);
		if(!$campaignData) {
			throw new \Exception('캠페인을 찾을 수 없습니다.');
		}
		
		// 직접발급(paymentFl='down')인지 확인
		if($campaignData['paymentFl'] != 'down') {
			throw new \Exception('직접발급 캠페인이 아닙니다.');
		}
		
		// 지급기간 확인
		$today = date('Y-m-d');
		if($campaignData['payStartDate'] > $today || $campaignData['payEndDate'] < $today) {
			throw new \Exception('지급기간이 아닙니다.');
		}
		
		// 회원 존재 여부 확인
		$sql = "SELECT COUNT(*) as cnt FROM wg_member WHERE memId = '{$this->escape($memId)}'";
		$memberExistData = $this->db->query_fetch($sql);
		if(empty($memberExistData) || $memberExistData[0]['cnt'] == 0) {
			throw new \Exception('존재하지 않는 회원입니다.');
		}
		
		// 중복 지급 체크 (memberAlwaysExceptFl)
		if($campaignData['memberAlwaysExceptFl'] == 'y') {
			$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
				WHERE campaignSno = {$campaignSno} AND memId = '{$this->escape($memId)}' AND mode = 'add'";
			$existData = $this->db->query_fetch($sql);
			if(!empty($existData) && $existData[0]['cnt'] > 0) {
				throw new \Exception('이미 지급받은 캠페인입니다.');
			}
		}
		
		// 최대 지급 횟수 제한 체크
		if($campaignData['memberAlwaysExceptLimitType'] == 'y' && $campaignData['memberAlwaysExceptLimit'] > 0) {
			$sql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
				WHERE campaignSno = {$campaignSno} AND memId = '{$this->escape($memId)}' AND mode = 'add'";
			$existData = $this->db->query_fetch($sql);
			if(!empty($existData) && $existData[0]['cnt'] >= $campaignData['memberAlwaysExceptLimit']) {
				throw new \Exception('최대 지급 횟수를 초과했습니다.');
			}
		}
		
		// 이벤트 마일리지 지급
		$this->campaignEventMileageAdd($memId, $campaignData);
		
		// 캠페인 지급 여부 업데이트
		$this->db->query("UPDATE wg_eventMileageCampaign SET campaignPayFl = 'y' WHERE sno = {$campaignSno}");
		
		return true;
	}

	// 자동 발급 캠페인 처리 (API에서 호출)
	public function processAutoCampaigns($sno = null) {
		$day = date('Y-m-d');
		$yoil = array('su','mo','tu','we','th','fr','sa');
		$todayYoil = $yoil[date('w', strtotime($day))]; //오늘 요일
		$todayD = date('d'); //오늘 일자
		$todaymd = date('m-d'); //오늘 월-일

		//자동이고 지급기간이 안끝난 캠페인들 가져오기 (날짜만 비교)
		$today = date('Y-m-d');
		$where = "paymentFl = 'auto' AND DATE(payStartDate) <= '{$today}' AND DATE(payEndDate) >= '{$today}'";
		if($sno) {
			$where .= " AND sno = " . intval($sno);
		}
		
		$sql = "SELECT * FROM wg_eventMileageCampaign WHERE {$where}";
		$campaignDataList = $this->db->query_fetch($sql);

		if(empty($campaignDataList)) {
			return;
		}

		//검색설정한대로 회원 가져오기 (searchQuery가 없으면 전체 회원)
		$member = new \Member\Member();
		foreach($campaignDataList as $campaign) {
			//회원 검색 설정 가져와서 회원검색 (searchQuery가 없으면 빈 배열로 처리하여 전체 회원 조회)
			$params = json_decode($campaign['searchQuery'], true);
			if($params === null) {
				$params = [];
			}
			
			// WHERE 조건 구성
			$arrWhere = [];
			
			// 검색어 검색
			if(!empty($params['keyword'])) {
				$keyword = $this->escape($params['keyword']);
				if(strpos($params['keyword'], ',') !== false) {
					$keywords = explode(',', $params['keyword']);
					$keywords = array_map(function($k) {
						return "'" . $this->escape(trim($k)) . "'";
					}, $keywords);
					if(empty($params['key'])) {
						$keywordList = implode(',', $keywords);
						$arrWhere[] = "(memId IN({$keywordList}) OR memNm IN({$keywordList}) OR cellPhone IN({$keywordList}))";
					} else {
						$key = $this->escape($params['key']);
						$arrWhere[] = $key . ' IN(' . implode(',', $keywords) . ')';
					}
				} else {
					if(empty($params['key'])) {
						$arrWhere[] = "(memId LIKE '%{$keyword}%' OR memNm LIKE '%{$keyword}%' OR cellPhone LIKE '%{$keyword}%')";
					} else {
						$key = $this->escape($params['key']);
						$arrWhere[] = "{$key} LIKE '%{$keyword}%'";
					}
				}
			}
			
			// 회원가입일 검색
			if(!empty($params['entryDt']) && is_array($params['entryDt'])) {
				if(!empty($params['entryDt'][0]) && !empty($params['entryDt'][1])) {
					$entryDtStart = $this->escape($params['entryDt'][0]) . ' 00:00:00';
					$entryDtEnd = $this->escape($params['entryDt'][1]) . ' 23:59:59';
					$arrWhere[] = "(joinDt BETWEEN '{$entryDtStart}' AND '{$entryDtEnd}' OR (joinDt IS NULL AND regDt BETWEEN '{$entryDtStart}' AND '{$entryDtEnd}'))";
				} else if(!empty($params['entryDt'][0])) {
					$entryDtStart = $this->escape($params['entryDt'][0]) . ' 00:00:00';
					$arrWhere[] = "(joinDt >= '{$entryDtStart}' OR (joinDt IS NULL AND regDt >= '{$entryDtStart}'))";
				} else if(!empty($params['entryDt'][1])) {
					$entryDtEnd = $this->escape($params['entryDt'][1]) . ' 23:59:59';
					$arrWhere[] = "(joinDt <= '{$entryDtEnd}' OR (joinDt IS NULL AND regDt <= '{$entryDtEnd}'))";
				}
			}

			// 주문일 검색
			if(!empty($params['orderDt']) && is_array($params['orderDt'])) {
				if(!empty($params['orderDt'][0]) && !empty($params['orderDt'][1])) {
					$orderDtStart = $this->escape($params['orderDt'][0]) . ' 00:00:00';
					$orderDtEnd = $this->escape($params['orderDt'][1]) . ' 23:59:59';
					$arrWhere[] = "(joinDt BETWEEN '{$orderDtStart}' AND '{$orderDtEnd}' OR (joinDt IS NULL AND regDt BETWEEN '{$orderDtStart}' AND '{$orderDtEnd}'))";
				} else if(!empty($params['orderDt'][0])) {
					$orderDtStart = $this->escape($params['orderDt'][0]) . ' 00:00:00';
					$arrWhere[] = "(joinDt >= '{$orderDtStart}' OR (joinDt IS NULL AND regDt >= '{$orderDtStart}'))";
				} else if(!empty($params['orderDt'][1])) {
					$orderDtEnd = $this->escape($params['orderDt'][1]) . ' 23:59:59';
					$arrWhere[] = "(joinDt <= '{$orderDtEnd}' OR (joinDt IS NULL AND regDt <= '{$orderDtEnd}'))";
				}
			}
			
			// 회원등급 검색
			if(!empty($params['groupNo']) && is_array($params['groupNo'])) {
				$groupNos = array_filter(array_map('intval', $params['groupNo']), function($val) {
					return $val > 0;
				});
				if(!empty($groupNos)) {
					$groupNoList = implode(',', $groupNos);
					$arrWhere[] = "groupNo IN({$groupNoList})";
				}
			}
			
			if(count($arrWhere) == 0) {
				$arrWhere[] = '1=1';
			}
			
			// 자동발급 주기 확인 (지급 여부 결정)
			$shouldPay = false;
			
			//요일
			if($campaign['autoPayFl'] == 'weekDay' && !empty($campaign['weekDay'])) {
				$weekDays = is_array($campaign['weekDay']) ? $campaign['weekDay'] : explode(',', $campaign['weekDay']);
				if(in_array($todayYoil, $weekDays)) {
					$shouldPay = true;
				}
			} else if($campaign['autoPayFl'] == 'monthDay' && !empty($campaign['monthDay'])) {
				//월
				if($campaign['monthDay'] == $todayD) {
					$shouldPay = true;
				}
			} else if($campaign['autoPayFl'] == 'yearDate' && !empty($campaign['yearDate'])) {
				//년 (yearDate는 'MM-DD' 형식으로 저장됨)
				if($campaign['yearDate'] == $todaymd) {
					$shouldPay = true;
				}
			}
			
			// 지급 조건이 맞지 않으면 스킵
			if(!$shouldPay) {
				continue;
			}
			
			// 배치 처리: 대량 회원 처리 시 부하 분산
			// 전체 회원 수 조회
			$countSql = "SELECT COUNT(DISTINCT memId) as total FROM wg_member WHERE " . implode(' AND ', $arrWhere);
			$totalResult = $this->db->query_fetch($countSql);
			$totalMembers = intval($totalResult[0]['total'] ?? 0);
			
			if($totalMembers == 0) {
				continue;
			}
			
			// 배치 크기 설정 (1000명씩 처리)
			$batchSize = 1000;
			$totalBatches = ceil($totalMembers / $batchSize);
			
			// 배치 단위로 회원 조회 및 지급 처리
			for($batch = 0; $batch < $totalBatches; $batch++) {
				$offset = $batch * $batchSize;
				
				// 배치 단위로 회원 ID 조회 (메모리 절약)
				$sql = "SELECT DISTINCT memId FROM wg_member 
						WHERE " . implode(' AND ', $arrWhere) . " 
						ORDER BY regDt DESC 
						LIMIT {$batchSize} OFFSET {$offset}";
				$memberList = $this->db->query_fetch($sql);
				
				if(empty($memberList) || count($memberList) == 0) {
					break;
				}
				
				$memIds = array_unique(array_column($memberList, 'memId')); // 중복 제거
				
				// 회원에게 쇼핑지원금 지급
				// 자동 발급일 때는 memberExceptFl을 무조건 'y'로 설정
				try {
					$this->checkCampaignMemberPay($memIds, $campaign['sno'], 'y');
				} catch(\Exception $e) {
					// 에러 로그만 남기고 계속 진행 (다음 배치 처리)
					continue;
				}
				
				// 메모리 정리
				unset($memberList, $memIds);
			}
		}
	}

    /**
     * 쇼핑지원금 내역 조회 (필터 및 페이징 지원)
     */
    public function getEventMileageHistory($params = [], $page = 1, $limit = 10) {
        if (!is_array($params)) {
            $memId = $params;
            $params = [
                'memId' => $memId,
                'page' => $page,
                'limit' => $limit
            ];
        }

        $page = intval($params['page'] ?? $page);
        $limit = intval($params['limit'] ?? $limit);
        $offset = ($page - 1) * $limit;

        $where = [];
        if (!empty($params['memId'])) {
            $where[] = "memId = '" . $this->escape($params['memId']) . "'";
        }
        
        // 검색 필터 (관리자용 등 확장)
        if (!empty($params['keyword']) && !empty($params['key'])) {
            $keyword = $this->escape($params['keyword']);
            $key = $params['key'];
            if (in_array($key, ['memId', 'orderId', 'contents'])) {
                $where[] = "{$key} LIKE '%{$keyword}%'";
            }
        }
        
        if (!empty($params['mode'])) {
            $where[] = "mode = '" . $this->escape($params['mode']) . "'";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "WHERE 1=1";

        // 1. 전체 내역 조회 (최근 순)
        $sql = "SELECT * FROM wg_eventMileage 
                {$whereSql}
                ORDER BY regDt DESC, sno DESC 
                LIMIT {$offset}, {$limit}";
        $list = $this->db->query_fetch($sql);

        // 2. 각 내역 보완 (지급 건의 경우 현재 잔액 계산)
        foreach ($list as &$item) {
            if ($item['mode'] == 'add') {
                $sno = intval($item['sno']);
                $usageSql = "SELECT SUM(eventMileage) as usedAmount FROM wg_eventMileage 
                             WHERE parentSno = {$sno} AND mode = 'remove'";
                $usageResult = $this->db->query_fetch($usageSql);
                $usedAmount = intval($usageResult[0]['usedAmount'] ?? 0);
                $item['balance'] = intval($item['eventMileage']) - $usedAmount;
            } else {
                $item['balance'] = 0;
            }
        }

        // 3. 필터링된 전체 개수 조회
        $countSql = "SELECT COUNT(*) as cnt FROM wg_eventMileage {$whereSql}";
        $totalCount = $this->db->query_fetch($countSql)[0]['cnt'] ?? 0;

        // 4. 테이블의 전체 데이터 개수 조회
        $totalSql = "SELECT COUNT(*) as cnt FROM wg_eventMileage";
        $totalCountAbsolute = $this->db->query_fetch($totalSql)[0]['cnt'] ?? 0;

        // 5. 현재 사용 가능한 총 잔액 계산 (memId가 있을 때만)
        $totalUsable = 0;
        if (!empty($params['memId'])) {
            $totalUsable = $this->getUsableEventMileage($params['memId']);
        }

        return [
            'list' => $list,
            'totalCount' => (int)$totalCount, // 필터링된 개수
            'totalCountAbsolute' => (int)$totalCountAbsolute, // 전체 개수
            'totalUsable' => (int)$totalUsable,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'totalPage' => ceil($totalCount / $limit)
            ]
        ];
    }

    public function getUsableEventMileage($memId, $totalOrderPrice = 0) {
        $today = date('Y-m-d');
        // 1. 유효한 지급(add) 건들 가져오기 (할인율이 높은 순서로 정렬)
        $sql = "SELECT * FROM wg_eventMileage 
                WHERE memId = '{$this->escape($memId)}' 
                AND mode = 'add' 
                AND expiryFl = 'y' 
                AND expiryDate >= '{$today}'
                ORDER BY mileageLimit DESC, regDt ASC, sno ASC";
        $grants = $this->db->query_fetch($sql);
        
        $totalUsableMileage = 0;
        $remainingProductPrice = floatval($totalOrderPrice); // 혜택이 적용되지 않은 남은 상품 금액
        
        foreach ($grants as $grant) {
            $grantSno = intval($grant['sno']);
            $grantedAmount = intval($grant['eventMileage']);
            $mileageLimitRate = floatval($grant['mileageLimit'] ?? 0); // 혜택사용율 (%)

            // 2. 해당 지급건에 대해 사용된(remove) 금액 계산
            $usageSql = "SELECT SUM(eventMileage) as usedAmount FROM wg_eventMileage 
                         WHERE parentSno = {$grantSno} AND mode = 'remove'";
            $usageResult = $this->db->query_fetch($usageSql);
            $usedAmount = intval($usageResult[0]['usedAmount'] ?? 0);
            
            // 3. 남은 마일리지 계산
            $remainingMileage = $grantedAmount - $usedAmount;
            if ($remainingMileage <= 0) continue;

            // 4. 할인율 기반 사용 가능 금액 계산
            if ($totalOrderPrice > 0 && $mileageLimitRate > 0) {
                if ($remainingProductPrice <= 0) {
                    $usableAmount = 0;
                } else {
                    $rate = $mileageLimitRate / 100;
                    // 현재 남은 상품 금액에 대해 이 캠페인의 할인율을 적용한 최대 마일리지
                    $maxMileageForThisGrant = floor($remainingProductPrice * $rate);
                    
                    // 실제 사용 가능한 마일리지 (잔액과 한도 중 최소값)
                    $usableAmount = min($remainingMileage, $maxMileageForThisGrant);
                    
                    // 이 마일리지가 커버하는 상품 금액만큼 남은 상품 금액에서 차감
                    if ($usableAmount > 0) {
                        $coveredProductValue = $usableAmount / $rate;
                        $remainingProductPrice -= $coveredProductValue;
                    }
                }
            } else {
                $usableAmount = $remainingMileage;
            }

            if ($usableAmount > 0) {
                $totalUsableMileage += $usableAmount;
            }

            if ($totalOrderPrice > 0 && $remainingProductPrice <= 0) break;
        }

        // 5. 부모가 없는(standalone) 차감 내역 반영
        $today = date('Y-m-d');
        $standaloneSql = "SELECT SUM(eventMileage) as standaloneUsed FROM wg_eventMileage 
                          WHERE memId = '{$this->escape($memId)}' 
                          AND (parentSno IS NULL OR parentSno = 0) 
                          AND mode = 'remove'
                          AND (expiryDate IS NULL OR expiryDate >= '{$today}')";
        $standaloneResult = $this->db->query_fetch($standaloneSql);
        $standaloneUsed = intval($standaloneResult[0]['standaloneUsed'] ?? 0);
        
        $totalUsableMileage -= $standaloneUsed;

        return max(0, $totalUsableMileage);
    }

	// 유효기간 만료 처리 (매일 실행)
	// 유효기간 종료일에 맞춰서 expiryFl과 state 변경
	public function processExpiredEventMileage() {
		$today = date('Y-m-d');
		
		// 유효기간이 지난 지급 건(mode='add') 조회
		// expiryFl='y'이고 state='n'인 것만 처리 (이미 처리된 것은 제외)
		$sql = "SELECT * 
			FROM wg_eventMileage 
			WHERE mode = 'add' 
			AND expiryFl = 'y' 
			AND expiryDate < '{$today}' 
			AND state = 'n' 
			AND memberUpdateFl = 'y'";
		$expiredGrants = $this->db->query_fetch($sql);
		
		if(empty($expiredGrants)) {
			return ['processed' => 0, 'totalDeducted' => 0, 'memberCount' => 0];
		}
		
		$processedCount = 0;
		$totalDeducted = 0;
		$memberDeductions = []; // 회원별 차감 금액 누적
		
		foreach($expiredGrants as $grant) {
			$grantSno = intval($grant['sno']);
			$memId = $this->escape($grant['memId']);
			$grantedAmount = intval($grant['eventMileage']);
			
			// 해당 지급 건에 대해 사용된(remove) 금액 계산 (기존 restore는 사용하지 않음)
			$usageSql = "SELECT SUM(eventMileage) as usedAmount FROM wg_eventMileage 
                         WHERE parentSno = {$grantSno} AND mode = 'remove'";
			$usageResult = $this->db->query_fetch($usageSql);
			$usedAmount = intval($usageResult[0]['usedAmount'] ?? 0);
			
			// 남은 금액 계산 (원금 - 차감액)
			$remainingAmount = $grantedAmount - $usedAmount;
			
			if($remainingAmount > 0) {
				// 1. 잔액만큼 실제 차감(remove) 레코드 생성
                $beforeTotal = $this->getUsableEventMileage($memId);
                $afterTotal = $beforeTotal - $remainingAmount;

                $insertSql = "INSERT INTO wg_eventMileage SET 
                    memId='{$memId}', 
                    eventMileage={$remainingAmount},
                    beforeEventMileage={$beforeTotal},
                    afterEventMileage={$afterTotal},
                    mode='remove',
                    parentSno={$grantSno},
                    campaignSno=" . intval($grant['campaignSno']) . ",
                    contents='유효기간 만료 차감 (원천번호: {$grantSno})',
                    memberUpdateFl='y',
                    state='n',
                    regDt=now()";
                $this->db->query($insertSql);

				if(!isset($memberDeductions[$memId])) {
					$memberDeductions[$memId] = 0;
				}
				$memberDeductions[$memId] += $remainingAmount;
                $totalDeducted += $remainingAmount;
			}
			
			// 유효기간 만료 처리: 원천 지급 건 상태 변경
			$updateSql = "UPDATE wg_eventMileage SET 
				state = 'expired', 
				modDt = now() 
				WHERE sno = {$grantSno}";
			$this->db->query($updateSql);
			
			$processedCount++;
		}
		
		return [
			'processed' => $processedCount,
			'totalDeducted' => $totalDeducted,
			'memberCount' => count($memberDeductions)
		];
	}

    /**
     * 주문 데이터를 기반으로 쇼핑지원금을 차감 처리 (API 호출 없이 전달받은 데이터로 처리)
     * @param array $orderData 주문 데이터 (order_id, member_id, useEventMileage, totalProductPrice 포함)
     */
    public function deductMileageByOrderData($orderData) {
        $orderId = $orderData['order_id'] ?? '';
        $memId = $orderData['member_id'] ?? '';
        $useMileage = intval($orderData['useEventMileage'] ?? 0);
        $totalProductPrice = floatval($orderData['totalProductPrice'] ?? 0);
        
        if (empty($orderId) || empty($memId)) {
            return;
        }

        // totalProductPrice가 없는 경우 wg_order에서 가져오기
        if ($totalProductPrice <= 0) {
            $sql = "SELECT total_goods_price FROM wg_order WHERE order_id = '{$this->escape($orderId)}'";
            $orderRow = $this->db->query_fetch($sql);
            $totalProductPrice = floatval($orderRow[0]['total_goods_price'] ?? 0);
        }

        // 그래도 0원인 경우 주문상품(wg_orderGoods)에서 합산하여 가져오기
        if ($totalProductPrice <= 0) {
            $sql = "SELECT SUM((goodsPrice + optionPrice) * goodsCnt) as total_price FROM wg_orderGoods WHERE order_id = '{$this->escape($orderId)}'";
            $goodsRow = $this->db->query_fetch($sql);
            $totalProductPrice = floatval($goodsRow[0]['total_price'] ?? 0);
        }

        if ($useMileage <= 0) return;

        // 중복 차감 방지: 해당 주문번호로 이미 차감된 내역이 있는지 확인
        $checkSql = "SELECT COUNT(*) as cnt FROM wg_eventMileage WHERE orderId = '{$this->escape($orderId)}' AND mode = 'remove' LIMIT 1";
        $checkResult = $this->db->query_fetch($checkSql);
		
        if (intval($checkResult[0]['cnt'] ?? 0) > 0) {
            return; // 이미 처리됨
        }

        // 전체 금액에 대해 쇼핑지원금 차감 처리 (지급건별로)
        $remainingToDeduct = $useMileage;
        $remainingProductPrice = $totalProductPrice; // 혜택이 적용되지 않은 남은 상품 금액
        $today = date('Y-m-d');
        
        // 현재 시점의 총 잔액 한 번만 조회
        $currentTotalBalance = $this->getUsableEventMileage($memId);

        $sql = "SELECT * FROM wg_eventMileage 
                WHERE memId = '{$this->escape($memId)}' 
                AND mode = 'add' 
                AND expiryFl = 'y' 
                AND expiryDate >= '{$today}'
                ORDER BY mileageLimit DESC, regDt ASC, sno ASC";
        $grants = $this->db->query_fetch($sql);

        foreach ($grants as $grant) {
            if ($remainingToDeduct <= 0) break;

            $grantSno = intval($grant['sno']);
            $grantedAmount = intval($grant['eventMileage']);
            $campaignSno = intval($grant['campaignSno']);
            $limitRate = floatval($grant['mileageLimit'] ?? 0);

            // 해당 지급건의 현재 잔액 계산
            $usageSql = "SELECT SUM(eventMileage) as usedAmount FROM wg_eventMileage 
                         WHERE parentSno = {$grantSno} AND mode = 'remove'";
            $usageResult = $this->db->query_fetch($usageSql);
            $usedAmount = intval($usageResult[0]['usedAmount'] ?? 0);
            $grantBalance = $grantedAmount - $usedAmount;

            if ($grantBalance <= 0) continue;

            // 할인율 기반 차감 가능 한도 계산
            if ($totalProductPrice > 0 && $limitRate > 0) {
                $rate = $limitRate / 100;
                $maxMileageForThisGrant = floor($remainingProductPrice * $rate);
                $deductLimit = min($grantBalance, $maxMileageForThisGrant);
            } else if ($limitRate <= 0) {
                // 한도 제한이 없는 경우 잔액 전체를 한도로 잡음
                $deductLimit = $grantBalance;
            } else {
                $deductLimit = 0;
            }

            // 실제 차감할 금액
            $deductAmount = min($remainingToDeduct, $deductLimit);

            if ($deductAmount > 0) {
                $beforeEventMileage = $currentTotalBalance;
                $afterEventMileage = $beforeEventMileage - $deductAmount;
                $currentTotalBalance = $afterEventMileage; // 다음 루프를 위해 업데이트

                $insertSql = "INSERT INTO wg_eventMileage SET 
                    memId='{$this->escape($memId)}', 
                    eventMileage={$deductAmount},
                    beforeEventMileage={$beforeEventMileage},
                    afterEventMileage={$afterEventMileage},
                    mode='remove', 
                    parentSno={$grantSno},
                    campaignSno={$campaignSno},
                    orderId='{$this->escape($orderId)}',
                    reasonCd='1029',
                    contents='주문 차감 ({$orderId})', 
                    memberUpdateFl='y',
                    expiryFl='y',
                    expiryDate='{$grant['expiryDate']}',
                    state='n',
                    regDt=now()";
                
                $this->db->query($insertSql);
                $remainingToDeduct -= $deductAmount;

                // 이 마일리지가 커버하는 상품 금액만큼 남은 상품 금액에서 차감
                if ($limitRate > 0 && $totalProductPrice > 0) {
                    $rate = $limitRate / 100;
                    $coveredProductValue = $deductAmount / $rate;
                    $remainingProductPrice -= $coveredProductValue;
                }

                if (($grantBalance - $deductAmount) <= 0) {
                    $completeSql = "UPDATE wg_eventMileage SET state = 'complete', modDt = now() WHERE sno = {$grantSno}";
                    $this->db->query($completeSql);
                }
            }
        }

        // 루프를 돌았음에도 차감할 금액이 남은 경우 (잔액 부족 시)
        if ($remainingToDeduct > 0) {
            $beforeEventMileage = $currentTotalBalance;
            $afterEventMileage = $beforeEventMileage - $remainingToDeduct;
            
            $insertSql = "INSERT INTO wg_eventMileage SET 
                memId='{$this->escape($memId)}', 
                eventMileage={$remainingToDeduct},
                beforeEventMileage={$beforeEventMileage},
                afterEventMileage={$afterEventMileage},
                mode='remove', 
                orderId='{$this->escape($orderId)}',
                reasonCd='1029',
                contents='주문 차감 (잔여 차감분) ({$orderId})', 
                memberUpdateFl='y',
                expiryFl='y',
                expiryDate='2099-12-31',
                regDt=now()";
            $this->db->query($insertSql);
        }

        return true;
    }

    /**
     * wg_order 테이블에서 apply_event_mileage가 'n'인 주문들을 가져와 일괄 마일리지 차감 처리
     */
    public function processPendingOrderMileage($orderId = '') {
        // 1. 미처리 주문 조회 (주문번호가 있는 경우 해당 주문만, 없는 경우 전체 n인 건 조회)
        $where = "apply_event_mileage = 'n'";
        if (!empty($orderId)) {
            $where .= " AND order_id = '" . addslashes($orderId) . "'";
        }
        
        $sql = "SELECT order_id, member_id, use_event_mileage, total_goods_price FROM wg_order WHERE {$where} LIMIT 1000";
        $pendingOrders = $this->db->query_fetch($sql);

        if (empty($pendingOrders)) {
            return 0;
        }

        $processedCount = 0;
        foreach ($pendingOrders as $order) {
            // 2. 마일리지 차감 처리 호출 (기존 함수 활용)
            // use_event_mileage가 0이어도 상태 업데이트를 위해 진행
            if ($order['use_event_mileage'] > 0) {
                $this->deductMileageByOrderData([
                    'order_id' => $order['order_id'],
                    'member_id' => $order['member_id'],
                    'useEventMileage' => $order['use_event_mileage'],
                    'totalProductPrice' => $order['total_goods_price']
                ]);
            }

            // 3. 주문 상태 업데이트 (apply_event_mileage = 'y')
            $updateSql = "UPDATE wg_order SET apply_event_mileage = 'y' WHERE order_id = '" . addslashes($order['order_id']) . "'";
            $this->db->query($updateSql);

            $processedCount++;
        }

        return $processedCount;
    }

    /**
     * 주문 취소/환불 시 차감되었던 쇼핑지원금 복구 (신규 add 내역으로 생성)
     * @param string $orderId 주문번호 (비어있을 경우 미처리된 전체 'n' 건 처리)
     */
    public function restoreMileageByRefund($orderId = '') {
        // 1. 복구 대상 주문 조회 (n 상태인 건들)
        $where = "o.use_event_mileage > 0 AND og.apply_event_mileage_refund = 'n'";
        if (!empty($orderId)) {
            $where .= " AND o.order_id = '" . addslashes($orderId) . "'";
        }
        
        $sql = "SELECT o.order_id, o.member_id, o.use_event_mileage FROM wg_order o LEFT JOIN wg_orderGoods og ON o.order_id = og.order_id WHERE {$where} GROUP BY o.order_id";
        $pendingOrders = $this->db->query_fetch($sql);

        if (empty($pendingOrders)) return 0;

        $totalRestoredCount = 0;
        foreach ($pendingOrders as $order) {
            $oid = $order['order_id'];
            $memId = $order['member_id'];

            // 주문의 모든 상품 정보 및 상태 조회
            $itemsSql = "SELECT og.sno, og.order_item_code, og.order_status, og.use_event_mileage, og.apply_event_mileage_refund FROM wg_orderGoods og WHERE og.order_id = '{$this->escape($oid)}'";
            $allOrderItems = $this->db->query_fetch($itemsSql);
            
            // 모든 상품이 취소/환불 상태인지 확인
            $refundedStatuses = ['C40', 'C47', 'C48', 'C49', 'R40'];
            $allRefunded = true;
            foreach ($allOrderItems as $item) {
                if (!in_array($item['order_status'], $refundedStatuses)) {
                    $allRefunded = false;
                    break;
                }
            }

            // 모든 상품이 취소/환불 상태일 때만 처리
            if (!$allRefunded) {
                continue;
            }

            // 2. 중복 복구 방지 (이미 해당 주문번호로 add 된 복구 내역이 있는지 확인)
            $checkSql = "SELECT COUNT(*) as cnt FROM wg_eventMileage 
                         WHERE orderId = '{$this->escape($oid)}' 
                         AND mode = 'add' AND contents LIKE '%환불 복구%' LIMIT 1";
            $checkResult = $this->db->query_fetch($checkSql);

            if (intval($checkResult[0]['cnt'] ?? 0) == 0) {
                // 기존 차감 내역들을 찾아 모두 복구
                $logSql = "SELECT * FROM wg_eventMileage 
                           WHERE orderId = '{$this->escape($oid)}' 
                           AND mode = 'remove' 
                           ORDER BY sno ASC";
                $deductLogs = $this->db->query_fetch($logSql);

                if (!empty($deductLogs)) {
                    foreach ($deductLogs as $log) {
                        $restoreAmount = intval($log['eventMileage']);
                        $campaignSno = intval($log['campaignSno']);
                        $originalExpiryDate = $log['expiryDate']; // 기존 차감 내역의 만료일
                        
                        if ($restoreAmount > 0) {
                            $beforeTotal = $this->getUsableEventMileage($memId);
                            $afterTotal = $beforeTotal + $restoreAmount;

                            // 신규 add 레코드로 복구 처리
                            $insertSql = "INSERT INTO wg_eventMileage SET 
                                memId='{$this->escape($memId)}', 
                                eventMileage={$restoreAmount},
                                beforeEventMileage={$beforeTotal},
                                afterEventMileage={$afterTotal},
                                mode='add', 
                                orderId='{$this->escape($oid)}',
                                campaignSno={$campaignSno},
                                expiryStartDate=now(),
                                expiryDate='{$this->escape($originalExpiryDate)}',
                                expiryFl='y',
                                state='n',
                                contents='주문 환불 복구 ({$oid})',
                                memberUpdateFl='y',
                                regDt=now()";
                            $this->db->query($insertSql);
                            
                            $totalRestoredCount++;
                        }
                    }
                }
            }

            // 3. 주문 상품들의 환불 처리 상태 업데이트
            $this->db->query("UPDATE wg_orderGoods SET apply_event_mileage_refund = 'y' WHERE order_id = '" . addslashes($oid) . "'");
        }
        
        return $totalRestoredCount;
    }
}


