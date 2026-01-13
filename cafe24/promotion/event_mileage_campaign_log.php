<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;
use Database\DB;
use Member\Member;
use Page\Page;

$db = new DB();
$campaign = new EventMileage();
$member = new Member();
$campaignSno = !empty($_GET['campaignSno']) ? intval($_GET['campaignSno']) : 0;

if($campaignSno <= 0) {
	echo "<script>alert('잘못된 요청입니다.'); location.href='./event_mileage_campaign_list.php';</script>";
	exit;
}

// 캠페인 정보 조회
$campaignData = $campaign->getCampaign($campaignSno);
if(empty($campaignData)) {
	echo "<script>alert('캠페인을 찾을 수 없습니다.'); location.href='./event_mileage_campaign_list.php';</script>";
	exit;
}

// 회원등급 목록 조회
$memberGroupList = $member->getGroup();

// 검색 조건 처리
$searchKeyword = !empty($_GET['keyword']) ? trim($_GET['keyword']) : '';
// groupNo[] 배열에서 빈 값과 0을 제거하고 유효한 값만 추출
$searchGroupNos = [];
if(!empty($_GET['groupNo']) && is_array($_GET['groupNo'])) {
	$searchGroupNos = array_filter(array_map('intval', $_GET['groupNo']), function($val) {
		return $val > 0;
	});
	$searchGroupNos = array_values($searchGroupNos); // 인덱스 재정렬
} else if(!empty($_GET['groupNo']) && !is_array($_GET['groupNo'])) {
	$groupNo = intval($_GET['groupNo']);
	if($groupNo > 0) {
		$searchGroupNos = [$groupNo];
	}
}

// 페이징 설정
$getValue['page'] = !empty($_GET['page']) ? intval($_GET['page']) : 1;
if(empty($_GET['pageNum'])) {
	$_GET['pageNum'] = '20';
}
$pageNum = intval($_GET['pageNum']);
$page = new Page($getValue['page']);
$page->page['list'] = $pageNum; // 페이지당 리스트 수
$page->block['cnt'] = 5; // 블록당 리스트 개수
$page->setUrl($_SERVER['QUERY_STRING']);

// 해당 캠페인의 이벤트 마일리지 내역 조회 (회원 정보 JOIN)
$whereConditions = ["E.campaignSno = {$campaignSno}"];

// 검색어 조건 (아이디, 이름, 휴대폰번호)
if(!empty($searchKeyword)) {
	$escapedKeyword = mysqli_real_escape_string(db_connect(), $searchKeyword);
	$whereConditions[] = "(E.memId LIKE '%{$escapedKeyword}%' OR M.memNm LIKE '%{$escapedKeyword}%' OR M.cellphone LIKE '%{$escapedKeyword}%')";
}

// 회원등급 조건
if(!empty($searchGroupNos)) {
	$groupNoList = implode(',', $searchGroupNos);
	$whereConditions[] = "M.groupNo IN({$groupNoList})";
}

$whereClause = implode(' AND ', $whereConditions);

// 전체 개수 조회
$countSql = "SELECT COUNT(*) as cnt 
	FROM wg_eventMileage E
	LEFT JOIN wg_member M ON E.memId = M.memId
	LEFT JOIN wg_memberGroup G ON M.groupNo = G.group_no
	WHERE {$whereClause}";
$totalResult = $db->query_fetch($countSql);
$totalCnt = intval($totalResult[0]['cnt'] ?? 0);

// 페이징 설정
$page->recode['total'] = $totalCnt;
$page->setPage();
$limit = 'LIMIT ' . $page->recode['start'] . ',' . $pageNum;

// 데이터 조회
$sql = "SELECT E.*, M.memNm, M.cellphone, M.groupNo, G.group_nm 
	FROM wg_eventMileage E
	LEFT JOIN wg_member M ON E.memId = M.memId
	LEFT JOIN wg_memberGroup G ON M.groupNo = G.group_no
	WHERE {$whereClause}
	ORDER BY E.regDt DESC
	{$limit}";
$mileageList = $db->query_fetch($sql);

// 페이징 HTML 생성
$currentPage = intval($page->page['now']);
$totalPages = intval($page->page['total']);
$startPage = intval($page->page['start']);
$endPage = intval($page->page['end']);

// 페이지 범위 유효성 검사
if($startPage < 1) $startPage = 1;
if($endPage < 1) $endPage = 1;
if($totalPages < 1) $totalPages = 1;
if($endPage > $totalPages) $endPage = $totalPages;

$prevPage = ($currentPage > 1) ? ($currentPage - 1) : 1;
$nextPage = ($currentPage < $totalPages) ? ($currentPage + 1) : $totalPages;
$lastPage = $totalPages;

$pageHtml = '<div class="pagination" style="text-align: center; margin: 20px 0; width:100%;">';

// 첫 페이지/이전 페이지 버튼
if($currentPage > 1) {
	$pageHtml .= '<a onclick="move_page(1)" class="first" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&lt;&lt;</a>';
	$pageHtml .= '<a onclick="move_page(' . $prevPage . ')" class="prev" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&lt;</a>';
}

// 페이지 번호
for($i = $startPage; $i <= $endPage; $i++) {
	if($i == $currentPage) {
		$pageHtml .= '<span style="margin: 0 5px; padding: 5px 10px; display: inline-block; background-color: #007bff; color: white; border: 1px solid #007bff; border-radius: 3px;">' . $i . '</span>';
	} else {
		$pageHtml .= '<a onclick="move_page(' . $i . ')" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">' . $i . '</a>';
	}
}

// 다음 페이지/마지막 페이지 버튼
if($currentPage < $totalPages) {
	$pageHtml .= '<a onclick="move_page(' . $nextPage . ')" class="next" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&gt;</a>';
	$pageHtml .= '<a onclick="move_page(' . $lastPage . ')" class="last" style="margin: 0 5px; padding: 5px 10px; display: inline-block; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #333; cursor: pointer;">&gt;&gt;</a>';
}

$pageHtml .= '</div>';

// 통계 계산
$totalPayCnt = 0;
$totalPayAmount = 0;
$totalDeductCnt = 0;
$totalDeductAmount = 0;

foreach($mileageList as $item) {
	if($item['mode'] == 'add' && $item['state'] == 'n') {
		$totalPayCnt++;
		$totalPayAmount += $item['eventMileage'];
	} else if($item['mode'] == 'remove' && $item['state'] == 'n') {
		$totalDeductCnt++;
		$totalDeductAmount += $item['eventMileage'];
	}
}

// 구분 한글 변환
function getModeText($mode) {
	$texts = [
		'add' => '지급',
		'remove' => '차감'
	];
	return $texts[$mode] ?? $mode;
}

// 상태 한글 변환
function getStateText($state) {
	$texts = [
		'n' => '사용가능',
		'd' => '삭제',
		'complete' => '완료'
	];
	return $texts[$state] ?? $state;
}

?>

<?php require_once './../header.php'; ?>

<style>
.btn_box {
	text-align: right;
	margin-bottom: 20px;
}
.table-rows {
	width: 100%;
}
.center {
	text-align: center;
}
.right {
	text-align: right;
}
.dn{
	display:none;
}
.summary-box table tr{
    vertical-align: sub;
}
.summary-box {
	background: #f5f5f5;
	padding: 20px;
	margin-bottom: 20px;
	border-radius: 5px;
}
.summary-box table {
	width: 100%;
}
.summary-box td {
	color:#333;
	padding: 5px 10px;
}
.summary-box .label {
	font-weight: bold;
	width: 150px;
}
</style>

<div id="content" class="body">
	<div class="page-header js-affix">
		<h3>쇼핑지원금 지급/차감 내역</h3>
		<div class="btn_box">
			<button type="button" class="btn btn-gray" onclick="location.href='./event_mileage_campaign_list.php'">목록</button>
		</div>
	</div>

	<!-- 캠페인 정보 -->
	<div class="table-title">캠페인 정보</div>
	<table class="table table-cols">
		<colgroup>
			<col style="width: 15%;">
			<col style="width: 35%;">
			<col style="width: 15%;">
			<col style="width: 35%;">
		</colgroup>
		<tbody>
			<tr>
				<th>캠페인명</th>
				<td><?= htmlspecialchars($campaignData['campaignNm'] ?? '') ?></td>
				<th>캠페인 코드</th>
				<td><?= htmlspecialchars($campaignData['campaignCode'] ?? '') ?></td>
			</tr>
			<tr>
				<th>혜택 금액</th>
				<td><?= number_format($campaignData['eventMileage'] ?? 0) ?>원</td>
				<th>등록일</th>
				<td><?= $campaignData['regDt'] ? date('Y-m-d H:i:s', strtotime($campaignData['regDt'])) : '' ?></td>
			</tr>
		</tbody>
	</table>

	<!-- 검색 영역 -->
	<form id="frmSearch" method="get" action="./event_mileage_campaign_log.php">
		<input type="hidden" name="campaignSno" value="<?= $campaignSno ?>">
		<div class="table-title">검색 조건</div>
		<table class="table table-cols">
			<colgroup>
				<col style="width: 15%;">
				<col style="width: 35%;">
				<col style="width: 15%;">
				<col style="width: 35%;">
			</colgroup>
			<tbody>
				<tr>
					<th>검색어</th>
					<td>
						<input type="text" name="keyword" value="<?= htmlspecialchars($searchKeyword) ?>" class="form-control" placeholder="아이디, 이름, 휴대폰번호">
					</td>
					<th>회원등급</th>
					<td>
						<label style="margin-right: 15px; font-weight: normal;">
							<input type="checkbox" name="groupNo[]" value="" class="js-group-all" <?= empty($searchGroupNos) ? 'checked="checked"' : '' ?>> 전체
						</label>
						<?php foreach($memberGroupList as $group): ?>
							<label style="margin-right: 15px; font-weight: normal;">
								<input type="checkbox" name="groupNo[]" value="<?= $group['group_no'] ?>" class="js-group-item" <?= in_array($group['group_no'], $searchGroupNos) ? 'checked="checked"' : '' ?>>
								<?= htmlspecialchars($group['group_nm']) ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="btn_box" style="text-align: center; margin-top: 10px;">
			<button type="submit" class="btn btn-primary">검색</button>
			<button type="button" class="btn btn-gray" onclick="location.href='./event_mileage_campaign_log.php?campaignSno=<?= $campaignSno ?>'">초기화</button>
		</div>
	</form>

	<!-- 통계 요약 -->
	<div class="summary-box dn">
		<table>
			<tr>
				<td class="label">지급 건수</td>
				<td><?= number_format($totalPayCnt) ?>건</td>
				<td class="label">지급 금액</td>
				<td><?= number_format($totalPayAmount) ?>원</td>
			</tr>
			<tr>
				<td class="label">차감 건수</td>
				<td><?= number_format($totalDeductCnt) ?>건</td>
				<td class="label">차감 금액</td>
				<td><?= number_format($totalDeductAmount) ?>원</td>
			</tr>
			<tr>
				<td class="label">순 지급 금액</td>
				<td colspan="3" style="font-weight: bold; font-size: 16px;">
					<?= number_format($totalPayAmount - $totalDeductAmount) ?>원
				</td>
			</tr>
		</table>
	</div>

	<!-- 내역 목록 -->
	<div class="table-title">지급/차감 내역</div>
	<table class="table table-rows">
		<thead>
			<tr>
				<th class="center" style="width: 4%;">번호</th>
				<th class="center" style="width: 6%;">구분</th>
				<th class="center" style="width: 10%;">회원 ID</th>
				<th class="center" style="width: 8%;">이름</th>
				<th class="center" style="width: 10%;">휴대폰</th>
				<th class="center" style="width: 8%;">회원등급</th>
				<th class="right" style="width: 8%;">금액</th>
				<th class="right" style="width: 8%;">이전 금액</th>
				<th class="right" style="width: 8%;">이후 금액</th>
				<th class="center" style="width: 10%;">유효기간</th>
				<th class="center" style="width: 6%;">상태</th>
				<th class="center" style="width: 12%;">등록일시</th>
			</tr>
		</thead>
		<tbody>
			<?php if(!empty($mileageList)): ?>
				<?php 
				$no = $totalCnt - ($currentPage - 1) * $pageNum;
				foreach($mileageList as $item): 
					$regDate = $item['regDt'] ? date('Y-m-d H:i:s', strtotime($item['regDt'])) : '';
					$expiryDate = '';
					if(!empty($item['expiryStartDate']) && !empty($item['expiryDate'])) {
						$expiryDate = date('Y-m-d', strtotime($item['expiryStartDate'])) . ' ~ ' . date('Y-m-d', strtotime($item['expiryDate']));
					} else if(!empty($item['expiryDate'])) {
						$expiryDate = date('Y-m-d', strtotime($item['expiryDate']));
					}
				?>
				<tr>
					<td class="center"><?= $no-- ?></td>
					<td class="center">
						<?php if($item['mode'] == 'add'): ?>
							<span style="color: blue;"><?= getModeText($item['mode']) ?></span>
						<?php else: ?>
							<span style="color: red;"><?= getModeText($item['mode']) ?></span>
						<?php endif; ?>
					</td>
					<td class="center"><?= htmlspecialchars($item['memId']) ?></td>
					<td class="center"><?= htmlspecialchars($item['memNm'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($item['cellphone'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($item['group_nm'] ?? '') ?></td>
					<td class="right">
						<?php if($item['mode'] == 'add'): ?>
							<span style="color: blue;">+<?= number_format($item['eventMileage']) ?></span>
						<?php else: ?>
							<span style="color: red;">-<?= number_format($item['eventMileage']) ?></span>
						<?php endif; ?>
					</td>
					<td class="right"><?= number_format($item['beforeEventMileage'] ?? 0) ?></td>
					<td class="right"><?= number_format($item['afterEventMileage'] ?? 0) ?></td>
					<td class="center"><?= htmlspecialchars($expiryDate) ?></td>
					<td class="center"><?= getStateText($item['state']) ?></td>
					<td class="center"><?= htmlspecialchars($regDate) ?></td>
				</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="13" class="center empty_table">내역이 없습니다.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	
	<?php if(!empty($pageHtml) && $totalPages > 1): ?>
		<?= $pageHtml ?>
	<?php endif; ?>
</div>

<script type="text/javascript">
$(document).ready(function () {
	// 회원등급 체크박스 전체 선택/해제
	$('.js-group-all').change(function(){
		if($(this).is(':checked')) {
			$('.js-group-item').prop('checked', false);
		}
	});
	$('.js-group-item').change(function(){
		if($(this).is(':checked')) {
			$('.js-group-all').prop('checked', false);
		}
		// 모든 개별 체크박스가 해제되면 전체 체크
		if($('.js-group-item:checked').length == 0) {
			$('.js-group-all').prop('checked', true);
		}
	});

	// 검색 폼 제출 시 전체가 체크되어 있으면 모든 groupNo[] 제거 (전체 포함)
	$('#frmSearch').submit(function(){
		if($('.js-group-all').is(':checked')) {
			$('#frmSearch input[name="groupNo[]"]').remove();
		}
	});
});

// 페이징 함수
function move_page(page) {
	var form = document.createElement('form');
	form.method = 'GET';
	form.action = './event_mileage_campaign_log.php';
	
	// campaignSno 추가
	var campaignSnoInput = document.createElement('input');
	campaignSnoInput.type = 'hidden';
	campaignSnoInput.name = 'campaignSno';
	campaignSnoInput.value = '<?= $campaignSno ?>';
	form.appendChild(campaignSnoInput);
	
	// page 추가
	var pageInput = document.createElement('input');
	pageInput.type = 'hidden';
	pageInput.name = 'page';
	pageInput.value = page;
	form.appendChild(pageInput);
	
	// 검색 조건 유지
	<?php if(!empty($searchKeyword)): ?>
	var keywordInput = document.createElement('input');
	keywordInput.type = 'hidden';
	keywordInput.name = 'keyword';
	keywordInput.value = '<?= htmlspecialchars($searchKeyword, ENT_QUOTES) ?>';
	form.appendChild(keywordInput);
	<?php endif; ?>
	
	<?php if(!empty($searchGroupNos)): ?>
		<?php foreach($searchGroupNos as $groupNo): ?>
	var groupNoInput<?= $groupNo ?> = document.createElement('input');
	groupNoInput<?= $groupNo ?>.type = 'hidden';
	groupNoInput<?= $groupNo ?>.name = 'groupNo[]';
	groupNoInput<?= $groupNo ?>.value = '<?= $groupNo ?>';
	form.appendChild(groupNoInput<?= $groupNo ?>);
		<?php endforeach; ?>
	<?php endif; ?>
	
	document.body.appendChild(form);
	form.submit();
}
</script>

<?php require_once './../footer.php'; ?>

