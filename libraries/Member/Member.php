<?php
namespace Member;

use Database\DB;
use Cafe24\Cafe24;
use Request;
use Storage\Storage;
use Page\Page;
use Board\Board;
use Coupon\Coupon;
use Log\Log;

class Member
{
	public $db;
	public $memberGroupInfo;
	public $coupon;

	public function __construct() {
		$this->db = new DB();
		$this->log = new Log();
	}

	public function getMemberInfo($memId, $field) {
		if(!$field) {
			$field = '*';
		}

		$sql = "SELECT {$field} FROM wg_member WHERE memId = '{$memId}'";
		$memberInfo = $this->db->query_fetch($sql)[0];	

		return $memberInfo;
	}


	public function updateMember($years = null, $date = null) {
		$cafe24 = new Cafe24();
		if(!$years) {
			// 현재 년도에서 -5년까지 현재 년도 포함
			$currentYear = (int)date('Y');
			$startYear = $currentYear - 5;
			$years = array_map('strval', range($startYear, $currentYear));
		}

		if($date == 'today') {
			$years = [(int)date('Y')];
		}

		$mallid = CAFE24_MALL_ID;
		$access_token = $cafe24->getToken();
		$version = CAFE24_API_VERSION;
		foreach($years as $year) {
			for($month = 1; $month <= 12; $month++) {
				if($date == 'today' && $month != (int)date('m')) {
					continue;
				}

				$daysInMonth = (int)date('t', strtotime("$year-$month-01"));

				if($month < 10) {
					$month = '0' . $month;
				}
				$startDate = $year . '-' . $month . '-01';
				$endDate = gd_date_format('Y-m-t', $startDate); // 하루 단위라 동일
				$offset = 0;
				
				$url = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy/count?search_type=customer_info'
					. '&date_type=join'
					. '&start_date=' . $startDate
					. '&end_date=' . $endDate
					. '&limit=1000'
					. '&offset=' . $offset;

				$result = $cafe24->simpleCafe24Api(['url' => $url]);
				$this->log->info($url);
				$this->log->info($result['count']);
				$this->apiMemberCnt[$startDate.'-'.$endDate] = $result['count'];
				if($result['count'] <= 9000) {
					foreach(range(0, 8) as $offset) {
						$url = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy?search_type=customer_info'
								. '&fields=cellphone,name,member_id,group_no,recommend_id,member_type,company_type,created_date'
								. '&date_type=join'
								. '&start_date=' . $startDate
								. '&end_date=' . $endDate
								. '&limit=1000'
								. '&offset=' . $offset * 1000;
						$result = $cafe24->simpleCafe24Api(['url' => $url]);
						$this->apiMemberCnt[$startDate.'-'.$endDate . '-' . $offset] = count($result['customersprivacy']);

						if($result['customersprivacy'] && count($result['customersprivacy']) > 0) {
							$this->joinDt = $startDate;
							$this->updateMemberInfo($result);
						} else {
							break;
						}
					}
				} else {
					// 월의 마지막 날짜를 동적으로 계산
					$lastDayOfMonth = (int)date('t', strtotime("$year-$month-01"));
					
					$periods = [
						['start' => 1, 'end' => 10],
						['start' => 11, 'end' => 20], 
						['start' => 21, 'end' => $lastDayOfMonth]
					];
					
					foreach($periods as $period) {
						$periodStartDate = sprintf('%04d-%02d-%02d', $year, $month, $period['start']);
						$periodEndDate = sprintf('%04d-%02d-%02d', $year, $month, $period['end']);
						
						// 기간별 회원 수 조회
						$url = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy/count?search_type=customer_info'
							. '&date_type=join'
							. '&start_date=' . $periodStartDate
							. '&end_date=' . $periodEndDate
							. '&limit=1000'
							. '&offset=' . $offset;
						$result = $cafe24->simpleCafe24Api(['url' => $url]);
						$this->log->info($url);
						$this->log->info($result['count']);
						$this->apiMemberCnt[$periodStartDate.'-'.$periodEndDate . '-' . $offset] = $result['count'];
						
						if($result['count'] <= 9999) {
							// 9999 이하면 해당 기간 전체 조회
							foreach(range(0, 8) as $offset) {
								$url = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy?search_type=customer_info'
										. '&fields=cellphone,name,member_id,group_no,recommend_id,member_type,company_type'
										. '&date_type=join'
										. '&start_date=' . $periodStartDate
										. '&end_date=' . $periodEndDate
										. '&limit=1000'
										. '&offset=' . $offset * 1000;
								$result = $cafe24->simpleCafe24Api(['url' => $url]);
								if($result['customersprivacy'] && count($result['customersprivacy']) > 0) {
									$this->joinDt = $periodStartDate;
									$this->updateMemberInfo($result);
								} else {
									break;
								}
							}
						} else {
							// 9999 초과하면 1일씩 조회
							for($day = $period['start']; $day <= $period['end']; $day++) {
								$dayStartDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
								$dayEndDate = $dayStartDate;
								
								// 일 단위 카운트 확인
								$dayCountUrl = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy/count?search_type=customer_info'
									. '&date_type=join'
									. '&start_date=' . $dayStartDate
									. '&end_date=' . $dayEndDate
									. '&limit=1000'
									. '&offset=0';
								$dayCntRes = $cafe24->simpleCafe24Api(['url' => $dayCountUrl]);
								$this->log->info($dayCountUrl);
								$this->log->info($dayCntRes['count']);
								$this->apiMemberCnt[$dayStartDate.'-'.$dayEndDate] = $dayCntRes['count'];

								if((int)$dayCntRes['count'] <= 9000) {
									foreach(range(0, 8) as $offset) {
										$url = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy?search_type=customer_info'
											. '&fields=cellphone,name,member_id,group_no,recommend_id,member_type,company_type'
											. '&date_type=join'
											. '&start_date=' . $dayStartDate
											. '&end_date=' . $dayEndDate
											. '&limit=1000'
											. '&offset=' . $offset * 1000;
										$result = $cafe24->simpleCafe24Api(['url' => $url]);
										$this->log->info($url);
										$this->log->info(count($result['customersprivacy']));
										$this->apiMemberCnt[$dayStartDate.'-'.$dayEndDate . '-' . $offset] = count($result['customersprivacy']);
										if($result['customersprivacy'] && count($result['customersprivacy']) > 0) {
											$this->joinDt = $dayStartDate;
											$this->updateMemberInfo($result);
										} else {
											break;
										}
									}
								} else {
									// 시간 단위로 분할 조회
									for($hour = 0; $hour <= 23; $hour++) {
										$hourStart = $dayStartDate . ' ' . str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00:00';
										$hourEnd = $dayStartDate . ' ' . str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':59:59';
										// $hourCountUrl = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy/count?search_type=customer_info'
										// 	. '&date_type=join'
										// 	. '&start_date=' . urlencode($hourStart)
										// 	. '&end_date=' . urlencode($hourEnd)
										// 	. '&limit=1000'
										// 	. '&offset=0';
										// $hourCntRes = $cafe24->simpleCafe24Api(['url' => $hourCountUrl]);
										// $this->log->info($hourCountUrl);
										// $this->log->info($hourCntRes['count']);
										// $this->apiMemberCnt[$hourStart.'-'.$hourEnd] = $hourCntRes['count'];

										// if((int)$hourCntRes['count'] > 0) {
											foreach(range(0, 8) as $offset) {
												$url = 'https://' . $mallid . '.cafe24api.com/api/v2/admin/customersprivacy?search_type=customer_info'
													. '&fields=cellphone,name,member_id,group_no,recommend_id,member_type,company_type'
													. '&date_type=join'
													. '&start_date=' . urlencode($hourStart)
													. '&end_date=' . urlencode($hourEnd)
													. '&limit=1000'
													. '&offset=' . $offset * 1000;
												$result = $cafe24->simpleCafe24Api(['url' => $url]);
												$this->log->info($url);
												$this->log->info(count($result['customersprivacy']));
												$this->apiMemberCnt[$hourStart.'-'.$hourEnd . '-' . $offset] = count($result['customersprivacy']);
												if($result['customersprivacy'] && count($result['customersprivacy']) > 0) {
													$this->joinDt = $hourStart;
													$this->updateMemberInfo($result);
												} else {
													break;
												}
											}
										// }
									}
								}
							}
						}
					}
				}
			}
		}
		//gd_debug($this->apiMemberCnt);

	}

	public function updateMemberInfo($result) {
		foreach($result['customersprivacy'] as $member) {
			if($member['member_id']) {
				$member['created_date'] = gd_date_format('Y-m-d H:i:s', $member['created_date']);
				$sql = "
					INSERT INTO wg_member(memId, groupNo, memNm, cellphone, memberType, companyType, regDt, joinDt) 
					VALUES('{$member['member_id']}','{$member['group_no']}','{$member['name']}','{$member['cellphone']}','{$member['member_type']}', '{$member['company_type']}', now(), '{$member['created_date']}')
					ON DUPLICATE KEY UPDATE
					groupNo = '{$member['group_no']}',
					memNm = '{$member['name']}',
					cellphone = '{$member['cellphone']}',
					memberType = '{$member['member_type']}',
					companyType = '{$member['company_type']}',
					joinDt = '{$member['created_date']}',
					modDt = now()
				";
				$result = $this->db->query($sql);
			}
		}
	}

	public function getMemberList() {
		//페이징
		$getValue['page'] = gd_isset($_GET['page'], 1);
		if(!$_GET['pageNum']) {
			$_GET['pageNum'] = '50';
		}
		$pageNum = $_GET['pageNum'];
		$this->page = new Page($getValue['page']);
		$this->page->page['list'] = $pageNum; // 페이지당 리스트 수
		$this->page->block['cnt'] = 5; // 블록당 리스트 개수
		$this->page->setPage();
		$this->page->setUrl($_SERVER['QUERY_STRING']);
		//관리자 페이징
		if($_GET['mode'] != 'searchStore' && $_GET['wgMode'] != 'memberExcelDown') { //엑셀 다운로드시 limit삭제
			$limit = 'LIMIT '.$this->page->recode['start'] . ',' . $pageNum;
		}
		$limit = gd_isset($limit,'');

		// WHERE 조건 배열 초기화
		$arrWhere = [];

		//검색어 검색
		if(!empty($_GET['keyword'])) {
			$keyword = $_GET['keyword'];
			if(strpos($_GET['keyword'], ',') !== false) {
				$keyword = explode(',', $keyword);
				if(!$_GET['key']){
					$arrWhere[] = 'm.memId IN(\''.implode('\',\'', $keyword).'\')';
				}else {
					$arrWhere[] = 'm.'.$_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
				}
			} else {
				if(!$_GET['key']){
					$arrWhere[] = 'm.memId LIKE "%'.$_GET['keyword'].'%"';
				}else {
					$arrWhere[] = 'm.'.$_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
				}
			}
		}

		// 회원가입일(entryDt) 조건 추가
		if(!empty($_GET['entryDt']) && is_array($_GET['entryDt'])) {
			if(!empty($_GET['entryDt'][0]) && !empty($_GET['entryDt'][1])) {
				$entryDtStart = $_GET['entryDt'][0] . ' 00:00:00';
				$entryDtEnd = $_GET['entryDt'][1] . ' 23:59:59';
				$arrWhere[] = "(m.joinDt BETWEEN '{$entryDtStart}' AND '{$entryDtEnd}' OR (m.joinDt IS NULL AND m.regDt BETWEEN '{$entryDtStart}' AND '{$entryDtEnd}'))";
			} else if(!empty($_GET['entryDt'][0])) {
				$entryDtStart = $_GET['entryDt'][0] . ' 00:00:00';
				$arrWhere[] = "(m.joinDt >= '{$entryDtStart}' OR (m.joinDt IS NULL AND m.regDt >= '{$entryDtStart}'))";
			} else if(!empty($_GET['entryDt'][1])) {
				$entryDtEnd = $_GET['entryDt'][1] . ' 23:59:59';
				$arrWhere[] = "(m.joinDt <= '{$entryDtEnd}' OR (m.joinDt IS NULL AND m.regDt <= '{$entryDtEnd}'))";
			}
		}

		// 주문일 조건 추가
		if(!empty($_GET['orderDt']) && is_array($_GET['orderDt'])) {
			if(!empty($_GET['orderDt'][0]) && !empty($_GET['orderDt'][1])) {
				$orderDtStart = $_GET['orderDt'][0] . ' 00:00:00';
				$orderDtEnd = $_GET['orderDt'][1] . ' 23:59:59';
				$arrWhere[] = "(m.joinDt BETWEEN '{$orderDtStart}' AND '{$orderDtEnd}' OR (m.joinDt IS NULL AND m.regDt BETWEEN '{$orderDtStart}' AND '{$orderDtEnd}'))";
			} else if(!empty($_GET['orderDt'][0])) {
				$orderDtStart = $_GET['orderDt'][0] . ' 00:00:00';
				$arrWhere[] = "(m.joinDt >= '{$orderDtStart}' OR (m.joinDt IS NULL AND m.regDt >= '{$orderDtStart}'))";
			} else if(!empty($_GET['orderDt'][1])) {
				$orderDtEnd = $_GET['orderDt'][1] . ' 23:59:59';
				$arrWhere[] = "(m.joinDt <= '{$orderDtEnd}' OR (m.joinDt IS NULL AND m.regDt <= '{$orderDtEnd}'))";
			}
		}

		// 회원등급(groupNo) 조건 추가
		if(!empty($_GET['groupNo']) && is_array($_GET['groupNo'])) {
			$groupNos = array_filter(array_map('intval', $_GET['groupNo']));
			if(!empty($groupNos)) {
				$groupNoList = implode(',', $groupNos);
				$arrWhere[] = "m.groupNo IN({$groupNoList})";
			}
		} else if(!empty($_GET['groupNo']) && !is_array($_GET['groupNo'])) {
			$groupNo = intval($_GET['groupNo']);
			if($groupNo > 0) {
				$arrWhere[] = "m.groupNo = {$groupNo}";
			}
		}

		//$arrWhere[] = 'm.hackoutFl = \'n\'';

		if(count($arrWhere) == 0) {
			$arrWhere[] = ' 1=1 ';
		}
		
		if($_GET['field']) {
			$field = $_GET['field'];
		} else {
			$field = 'm.*, G.group_nm';
		}
		
		if($_GET['sort']) {
			$sort = $_GET['sort'];
		} else {
			$sort = 'm.regDt DESC';
		}
		
		
		$sql = '	
				SELECT 
					'.$field.'
				FROM 
					wg_member m
				LEFT JOIN wg_memberGroup G ON m.groupNo = G.group_no
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					'.$sort.'
				'.$limit.'
		';
		
		$memberList = $this->db->query_fetch($sql);
		$result['memberList'] = $memberList;

		// 검색된 레코드 수
		$sql = '	
				SELECT 
					count(m.memId) as cnt
				FROM 
					wg_member m
				LEFT JOIN wg_memberGroup G ON m.groupNo = G.group_no
				WHERE 
					'.implode(' AND ', $arrWhere).'
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$this->page->recode['total'] = $searchCnt['cnt']; //검색 레코드 수
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		//전체갯수
		$sql = '
				SELECT 
					count(memId) as cnt
				FROM 
					wg_member m
				WHERE 
				1 = 1
		';

		//m.hackoutFl = \'n\'
		$totalCnt = $this->db->query_fetch($sql)[0];
		$result['totalCnt'] = $totalCnt['cnt'];

		// 이전/다음 페이지 계산
		$currentPage = intval($this->page->page['now']);
		$totalPages = intval($this->page->page['total']);
		$startPage = intval($this->page->page['start']);
		$endPage = intval($this->page->page['end']);
		
		// 페이지 범위 유효성 검사 (1 이상이어야 함)
		if($startPage < 1) $startPage = 1;
		if($endPage < 1) $endPage = 1;
		if($totalPages < 1) $totalPages = 1;
		if($endPage > $totalPages) $endPage = $totalPages;
		
		$prevPage = ($currentPage > 1) ? ($currentPage - 1) : 1;
		$nextPage = ($currentPage < $totalPages) ? ($currentPage + 1) : $totalPages;
		$lastPage = $totalPages;
		
		$pageHtml = '<div class="pagination" style="text-align: center; margin: 20px 0; width:100%;">';
		
		// 첫 페이지/이전 페이지 버튼 (현재 페이지가 1보다 클 때만 표시)
		if($currentPage > 1) {
			$pageHtml .= '<a onclick="move_page(1)" class="first" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&lt;&lt;</a>';
			$pageHtml .= '<a onclick="move_page('.$prevPage.')" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&lt;</a>';
		}
		
		$pageHtml .= '<ol style="display: inline-block; margin: 0; padding: 0; list-style: none;">';
		
		// 페이지 번호 표시 (1 이상인 페이지만 표시)
		if($startPage >= 1 && $endPage >= 1 && $startPage <= $endPage) {
			for($val = $startPage; $val <= $endPage; $val++) {
				if($val < 1) continue; // 1 미만인 페이지는 건너뛰기
				
				if($currentPage == $val) {
					$class = 'this';
					$style = 'margin: 0 3px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #337ab7; background-color: #337ab7; color: white; border-radius: 3px; cursor: pointer;';
				} else {
					$class = '';
					$style = 'margin: 0 3px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;';
				}
				$pageHtml .= '<li class="xans-record-" style="display: inline-block;"><a onclick="move_page('.$val.')" class="'.$class.'" style="'.$style.'">'.$val.'</a></li>';
			}
		}

		$pageHtml .= '</ol>';
		
		// 다음 페이지/마지막 페이지 버튼 (현재 페이지가 마지막 페이지보다 작을 때만 표시)
		if($currentPage < $totalPages) {
			$pageHtml .= '<a onclick="move_page('.$nextPage.')" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&gt;</a>';
			$pageHtml .= '<a onclick="move_page('.$lastPage.')" class="last" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&gt;&gt;</a>';
		}
		
		$pageHtml .= '</div>';

		$result['pageHtml'] = $pageHtml;

		return $result;
	}

	public function memberExcelDownList($memberListData)
	{
		header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"example.xls\"");
		
		echo "\xEF\xBB\xBF";

		// 테이블 상단 만들기
		echo "
			<table border='1'>
			<tr>  
			   <td>아이디</td>  
			   <td>이름</td>  
			   <td>등록일</td>
			</tr>
		";

		foreach($memberListData as $key => $val) {
			echo "  
				<tr>  
					<td>".$val['memId']."</td>  
					<td>".$val['memNm']."</td>  
					<td>".$val['regDt']."</td>  
			   </tr>  
			   "; 
		}
		echo "</table>"; 
		exit;
	}

	public function saveGroupAutoCouponConfig() {
		$sql = "UPDATE wg_groupAutoCouponConfig SET couponNo = {$_POST['couponNo']}, modDt = now()";
		$this->db->query($sql);
	}

	public function getGroupAutoCouponConfig() {
		$sql = "SELECT * FROM wg_groupAutoCouponConfig";
		$data = $this->db->query_fetch($sql);
		return $data[0];
	}

	public function checkGroupAutoCoupon($memId, $groupNo) {
		// 회원 등급이 변경(상향)된 회원은 쿠폰 지급 - 상향인지 하향인지 구분 필요
		if($this->memberGroupInfo[$memId] != $groupNo) {
			$result = $this->coupon->addCoupon($memId, $this->getGroupAutoCouponConfig()['couponNo']);
			return $result;
		} else {
			return true;
		}
	}

	// 웹훅으로 회원 등급 변경 처리
	public function updateMemberGradeWebhook() {
		// API Key 인증 체크
		$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
		if ($apiKey !== '23c92e8d-778e-4600-bccb-50eb58d556bf') {
			header('HTTP/1.1 401 Unauthorized');
			echo json_encode(['success' => false, 'message' => 'Invalid API Key']);
			exit;
		}

		// 웹훅 바디 읽기
		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);

		// 로그 적재
		$this->db->query("INSERT INTO wg_apiLog(apiType, requestData, responseData, regDt) VALUES('updateMemberGrade', '".addslashes($raw)."', '', NOW())");

		// 유효성 체크
		if (!isset($data['resource']['member_id']) || !isset($data['resource']['after_member_group_name'])) {
			$this->db->query("INSERT INTO wg_apiLog(apiType, requestData, responseData, regDt) VALUES('updateMemberGradeError', '".addslashes($raw)."', 'invalid payload', NOW())");
			return ['success' => false, 'message' => 'invalid payload'];
		}

		// member_id 여러 건 쉼표 구분
		$memberIds = array_filter(array_map('trim', explode(',', $data['resource']['member_id'])));
		$afterGroupName = trim($data['resource']['after_member_group_name']);

		// group_name -> group_no 매핑: wg_memberGrade에 존재한다고 가정
		$grade = $this->db->query_fetch("SELECT gradeNo FROM wg_memberGrade WHERE gradeName='".addslashes($afterGroupName)."' LIMIT 1");
		$groupNo = (int)($grade[0]['gradeNo'] ?? 0);

		if ($groupNo <= 0) {
			// 등급명이 매핑되지 않으면 로그만 남기고 종료
			$this->db->query("INSERT INTO wg_apiLog(apiType, requestData, responseData, regDt) VALUES('updateMemberGradeError', '".addslashes($raw)."', 'grade not found', NOW())");
			return ['success' => false, 'message' => 'grade not found'];
		}

		// 일괄 업데이트
		if (!empty($memberIds)) {
			$in = array_map(function($id){ return "'".addslashes($id)."'"; }, $memberIds);
			$sql = "UPDATE wg_member SET groupNo = {$groupNo}, modDt = NOW() WHERE memId IN (".implode(',', $in).")";
			$this->db->query($sql);
		}

		return ['success' => true, 'updated' => count($memberIds), 'groupNo' => $groupNo];
	}

	// 웹훅으로 회원 가입 처리
	public function registMemberWebhook() {
		// API Key 인증 체크
		$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
		if ($apiKey !== '23c92e8d-778e-4600-bccb-50eb58d556bf') {
			header('HTTP/1.1 401 Unauthorized');
			echo json_encode(['success' => false, 'message' => 'Invalid API Key']);
			exit;
		}

		// 웹훅 바디 읽기
		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);

		// 로그 적재
		$this->db->query("INSERT INTO wg_apiLog(apiType, requestData, responseData, regDt) VALUES('registMember', '".addslashes($raw)."', '', NOW())");

		// 유효성 체크
		if (!isset($data['resource']['member_id'])) {
			$this->db->query("INSERT INTO wg_apiLog(apiType, requestData, responseData, regDt) VALUES('registMemberError', '".addslashes($raw)."', 'member_id missing', NOW())");
			return ['success' => false, 'message' => 'member_id missing'];
		}

		$resource = $data['resource'];
		$memberId = addslashes(trim($resource['member_id']));
		$groupNo = (int)($resource['group_no'] ?? 0);
		$name = addslashes(trim($resource['name'] ?? ''));
		$cellphone = addslashes(trim($resource['cellphone'] ?? ''));
		$memberType = addslashes(trim($resource['member_type'] ?? ''));
		$companyType = ''; // 웹훅 데이터에 company_type이 없으므로 기본값
		$joinDt = isset($resource['created_date']) ? date('Y-m-d H:i:s', strtotime($resource['created_date'])) : date('Y-m-d H:i:s');

		// 기존 updateMemberInfo와 비슷한 형식으로 upsert
		$sql = "
			INSERT INTO wg_member(memId, groupNo, memNm, cellphone, memberType, companyType, regDt, joinDt, modDt) 
			VALUES('{$memberId}', '{$groupNo}', '{$name}', '{$cellphone}', '{$memberType}', '{$companyType}', NOW(), '{$joinDt}', NOW())
			ON DUPLICATE KEY UPDATE
			groupNo = '{$groupNo}',
			memNm = '{$name}',
			cellphone = '{$cellphone}',
			memberType = '{$memberType}',
			modDt = NOW()
		";

		$result = $this->db->query($sql);
		if (!$result) {
			$this->log->error($sql);
			$this->db->query("INSERT INTO wg_apiLog(apiType, requestData, responseData, regDt) VALUES('registMemberError', '".addslashes($raw)."', 'database error', NOW())");
			return ['success' => false, 'message' => 'database error'];
		}

		return ['success' => true, 'member_id' => $memberId, 'group_no' => $groupNo];
	}

	public function saveGroup() {
		$cafe24 = new Cafe24();
		$access_token = $cafe24->getToken();

		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://'.CAFE24_MALL_ID.'.cafe24api.com/api/v2/admin/customergroups',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => array(
			"Authorization: Bearer {$access_token}",
			"Content-Type: application/json",
			"X-Cafe24-Api-Version: ".CAFE24_API_VERSION
		),
		));
		$response = curl_exec($curl);
		$response = json_decode($response, 1);

		$sql = "SELECT group_no FROM wg_memberGroup";
		$existGroup = $this->db->query_fetch($sql);
		$existGroup = array_column($existGroup, 'group_no');
		
		foreach($response['customergroups'] as $key => $val) {
			$group_no = $val['group_no'];
			$group_nm = $val['group_name'];
			
			// wg_memberGroup 테이블에 저장
			if(!in_array($group_no, $existGroup)) {	
				$sql = "INSERT INTO wg_memberGroup (group_no, group_nm) VALUES ('{$group_no}', '{$group_nm}')";
				$this->db->query($sql);
			} else {
				$sql = "UPDATE wg_memberGroup SET group_nm = '{$group_nm}', mod_date = NOW() WHERE group_no = '{$group_no}'";
				$this->db->query($sql);
			}
		}
	}

	public function getGroup() {
		$sql = "SELECT group_no, group_nm FROM wg_memberGroup";
		$result = $this->db->query_fetch($sql);
		return $result;
	}

	public function getMemberGroupNo($member_id) {
		$sql = "SELECT group_no FROM wg_member WHERE member_id = '{$member_id}'";
		$result = $this->db->query_fetch($sql);
		return $result['group_no'];
	}
}