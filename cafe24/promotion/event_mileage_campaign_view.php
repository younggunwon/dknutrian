<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;
use Member\Member;

$campaign = new EventMileage();
$member = new Member();
$sno = !empty($_GET['sno']) ? intval($_GET['sno']) : 0;

if($sno <= 0) {
	echo "<script>alert('잘못된 요청입니다.'); parent.location.href='./event_mileage_campaign_list.php';</script>";
	exit;
}

$campaignData = $campaign->getCampaign($sno);

if(empty($campaignData)) {
	echo "<script>alert('캠페인을 찾을 수 없습니다.'); parent.location.href='./event_mileage_campaign_list.php';</script>";
	exit;
}

// campaignPayFl 필드가 없을 수 있으므로 기본값 설정 (지급 이력이 있으면 'y', 없으면 null 또는 'n')
$campaignData['campaignPayFl'] = $campaignData['campaignPayFl'] ?? 'n';

// checked 상태를 위한 배열 준비
$campaignChecked = [
	'expiryDaysFl' => [
		'y' => ($campaignData['expiryDaysFl'] == 'y') ? 'checked="checked"' : '',
		'dayDate' => ($campaignData['expiryDaysFl'] == 'dayDate') ? 'checked="checked"' : '',
		'monthEnd' => ($campaignData['expiryDaysFl'] == 'monthEnd') ? 'checked="checked"' : '',
		'yearEnd' => ($campaignData['expiryDaysFl'] == 'yearEnd') ? 'checked="checked"' : ''
	],
	'memberAlwaysExceptFl' => [
		'y' => ($campaignData['memberAlwaysExceptFl'] == 'y') ? 'checked="checked"' : '',
		'n' => ($campaignData['memberAlwaysExceptFl'] == 'n') ? 'checked="checked"' : ''
	],
	'memberAlwaysExceptLimitType' => [
		'y' => ($campaignData['memberAlwaysExceptLimitType'] == 'y') ? 'checked="checked"' : ''
	]
];

$paymentFl = $campaignData['paymentFl'] ?? '';

// 자동발급일 때 저장된 검색 조건 불러오기
if($paymentFl == 'auto' && !empty($campaignData['searchQuery'])) {
	$savedSearchParams = json_decode($campaignData['searchQuery'], true);
	if($savedSearchParams !== null && is_array($savedSearchParams)) {
		// GET 파라미터가 없을 때만 저장된 검색 조건 적용 (직접 검색한 경우 우선)
		if(empty($_GET['key']) && empty($_GET['keyword']) && empty($_GET['entryDt'])) {
			$_GET = array_merge($_GET, $savedSearchParams);
		}
	}
}

// 수동 발급일 때 회원 목록 조회
$memberListData = null;
$memberList = [];
$searchCnt = 0;
$totalCnt = 0;
$pageHtml = '';

if($paymentFl == 'passivity' || $paymentFl == 'auto') {
	// 회원 목록 조회 (getMemberList()에서 entryDt 조건 처리됨)
	$memberListData = $member->getMemberList();
	$memberList = $memberListData['memberList'] ?? [];
	$searchCnt = $memberListData['searchCnt'] ?? 0;
	$totalCnt = $memberListData['totalCnt'] ?? 0;
	$pageHtml = $memberListData['pageHtml'] ?? '';
}

?>

<?php require_once './../header.php'; ?>

<style>
.expiry_div{
	margin-top:10px;
}
.btn_box{
	text-align:center;
	margin-top:40px;
}
.btn_box input {
	width:70px;
}
.form-control{
	display:inline-block;
}
table td .red_text{
	color:red;
	margin-left:20px;
}
.script_copy{
	display:inline-block;
	background:#999999;
	padding:3px 10px;
	margin-left:10px;
	color:white;
	cursor:pointer;
}
.excel_down{
	display:inline-block;
	background-color: #BCBCBC;
	padding:3px 9px;
	color: #FFFFFF;
	vertical-align: middle;
	border-color: #888888;
	margin-right:10px;
	cursor:pointer;
}
.auto_pay_date div{
	margin-top:10px;
	vertical-align: top;
}
.table-header{
	border-top:0;
	padding-top:50px;
}
.btn-box{
	position: relative;
	border-top: 1px solid #888888;
	font-size:15px;
	padding-bottom: 15px;
}
.btn-box .btn-pay{
	position: absolute;
    right: 0;
	top:15px;
}
.btn-box .btn-pay input{
	margin-left:10px;
}
.btn-box .btn-red{
	padding:5px 20px;
}
.selected-btn-group {
    margin-top: 10px;
    padding: 7px 0 5px;
    display: none;
}
</style>

<div id="content" class="body">
	<div class="page-header">
		<h3>쇼핑지원금 캠페인 상세</h3>
		<a href="./event_mileage_campaign_log.php?campaignSno=<?= $sno ?>">
			<div class="btn btn-gray" style="float: right; background: #999999; color:white; line-height: 38px;">지급·사용내역/차감</div>
		</a>
	</div>

	<form id="frmCampaignRegist" action="./event_mileage_campaign_ps.php" method="post" novalidate="novalidate">
		<input type="hidden" name="mode" value="campaignRegister">
		<input type="hidden" name="sno" value="<?= $sno ?>">
		
		<div class="table-title">기본설정</div>
		<table class="table table-cols">
			<colgroup>
				<col class="width-sm" style="width: 159px !important; ">
				<col class="width-3xl">
				<col class="width-sm">
				<col class="">
			</colgroup>
			
			<tbody>
				<tr>
					<th class="require">캠페인명</th>
					<td colspan="3"><input type="text" name="campaignNm" value="<?= htmlspecialchars($campaignData['campaignNm'] ?? '') ?>" class="form-control width-2xl"><span class="red_text">* 마이페이지&gt;쇼핑지원금 항에 표시됨</span></td>
				</tr>
				<tr>
					<th class="require">캠페인 설명</th>
					<td colspan="3"><input type="text" name="campaignDes" value="<?= htmlspecialchars($campaignData['campaignDes'] ?? '') ?>" class="form-control width-3xl"><span class="red_text">* 사용자에게 보이지 않습니다.</span></td>
				</tr>
				<tr>
					<th>캠페인 코드</th>
					<td colspan="3"><?= htmlspecialchars($campaignData['campaignCode'] ?? '') ?></td>
				</tr>
				<tr>
					<th class="require">혜택금액</th>
					<td><input type="text" name="eventMileage" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" value="<?= htmlspecialchars($campaignData['eventMileage'] ?? '') ?>" class="form-control width-sm" style="display:inline-block;"> <span>원</span></td>

					<th class="require">혜택사용율</th>
					<td>구매금액의 <input type="input" name="mileageLimit" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" value="<?= htmlspecialchars($campaignData['mileageLimit'] ?? '') ?>" class="form-control width-sm" style="display:inline-block;"> <span>% 사용</span> <span class="red_text">* 주문서의 총 판매가 합계 기준.</span></td>
				</tr>
				<tr>
					<th class="require">유효기간</th>
					<td colspan="3">
						<?php if($paymentFl != 'auto'): ?>
						<div class="form-inline expiry_div">
							<label class="radio-inline">
								<input type="radio" name="expiryDaysFl" value="y" <?= $campaignChecked['expiryDaysFl']['y'] ?>>
								기간선택
								<div class="input-group js-datepicker">
									<input type="text" name="expiryStartDate" value="<?= htmlspecialchars($campaignData['expiryStartDate'] ?? '') ?>" class="form-control width-md" placeholder="">
									<span class="input-group-addon">
										<span class="btn-icon-calendar">
										</span>
									</span>
								</div>
								~
								<div class="input-group js-datepicker">
									<input type="text" name="expiryDate" value="<?= htmlspecialchars($campaignData['expiryDate'] ?? '') ?>" class="form-control width-md" placeholder="">
									<span class="input-group-addon">
										<span class="btn-icon-calendar">
										</span>
									</span>
								</div>
							</label>
						</div>
						<?php endif; ?>

						<div class="form-inline expiry_div">
							<label class="radio-inline">
								<input type="radio" name="expiryDaysFl" value="dayDate" <?= $campaignChecked['expiryDaysFl']['dayDate'] ?>>
								발급일로부터
								<input type="text" name="expiryDays" value="<?= htmlspecialchars($campaignData['expiryDays'] ?? '') ?>" class="form-control width-md" placeholder="" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');">
								일까지<br>
							</label>
						</div>
						<div class="form-inline expiry_div">
							<label class="radio-inline">
								<input type="radio" name="expiryDaysFl" value="monthEnd" <?= $campaignChecked['expiryDaysFl']['monthEnd'] ?>>
								발급월말 말일까지
							</label>
							<label class="radio-inline">
								<input type="radio" name="expiryDaysFl" value="yearEnd" <?= $campaignChecked['expiryDaysFl']['yearEnd'] ?>>
								발급년도 말일까지
							</label>
						</div>
					</td>
				</tr>
				<tr>
					<th class="require">지급기간</th>
					<td colspan="3">
						<div class="form-inline">
							<div class="input-group js-datepicker">
								<input type="text" name="payStartDate" value="<?= htmlspecialchars($campaignData['payStartDate'] ?? '') ?>" class="form-control width-md" placeholder="">
								<span class="input-group-addon">
									<span class="btn-icon-calendar">
									</span>
								</span>
							</div>
							~
							<div class="input-group js-datepicker">
								<input type="text" name="payEndDate" value="<?= htmlspecialchars($campaignData['payEndDate'] ?? '') ?>" class="form-control width-md" placeholder="">
								<span class="input-group-addon">
									<span class="btn-icon-calendar">
									</span>
								</span>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<th class="require">지급방식</th>
					<td colspan="3">
						<div class="form-inline">
							<label class="radio-inline">
								<input type="radio" name="paymentFl" value="passivity" <?= ($paymentFl == 'passivity') ? 'checked="checked"' : '' ?> <?= ($campaignData['campaignPayFl'] == 'y') ? 'disabled' : '' ?>>
								수동발급
							</label>
							<label class="radio-inline">
								<input type="radio" name="paymentFl" value="excelUpload" <?= ($paymentFl == 'excelUpload') ? 'checked="checked"' : '' ?> <?= ($campaignData['campaignPayFl'] == 'y') ? 'disabled' : '' ?>>
								엑셀업로드
							</label>
							<label class="radio-inline">
								<input type="radio" name="paymentFl" value="auto" <?= ($paymentFl == 'auto') ? 'checked="checked"' : '' ?> <?= ($campaignData['campaignPayFl'] == 'y') ? 'disabled' : '' ?>>
								자동발급
							</label>
							<label class="radio-inline">
								<input type="radio" name="paymentFl" value="down" <?= ($paymentFl == 'down') ? 'checked="checked"' : '' ?> <?= ($campaignData['campaignPayFl'] == 'y') ? 'disabled' : '' ?>>
								회원이 직접발급
							</label>
							<span class="red_text">* 수동발급/자동발급인 경우 캠페인 신규 등록 이후 대상자를 선택하고 발급함.</span>
						</div>
					</td>
				</tr>

				<?php if($paymentFl == 'down'): ?>
				<!-- 회원이 직접발급인 경우 -->
				<tr>
					<th>발급스크립트</th>
					<td colspan="3">
						javascript:eventMileageLinkDown('<?= $sno ?>');
						<div class="script_copy js-clipboard" title="<?= htmlspecialchars($campaignData['campaignNm']) ?>" data-clipboard-text="javascript:eventMileageLinkDown('<?= $sno ?>');">
							복사
						</div>
					</td>
				</tr>
				<?php endif; ?>

				<?php if($paymentFl == 'excelUpload'): ?>
				<!-- 엑셀업로드인 경우 -->
				<tr class="excel-upload">
					<th>엑셀업로드</th>
					<td colspan="3">
						<div class="form-inline">
							<a href="/excelMemberSample.csv" target="_blank" class="excel_down">
								엑셀양식 다운로드
							  </a>
							<input type="file" name="memberExcel" class="" accept=".csv">
							<span class="searched-member" id="excelMemberCount">업로드된 회원 : <span style="color:red;">0</span> 명</span>
						</div>
						<div style="margin-top: 10px; font-size: 12px; color: #666;">
							* 파일 형식: CSV<br>
							* 파일 구조: 첫 번째 행은 헤더(회원ID, 금액), 두 번째 행부터 데이터 입력
						</div>
					</td>
				</tr>
				<?php endif; ?>


				<?php if($paymentFl == 'auto'): ?>
				<!-- 자동발급인 경우 -->
				<?php 
				// 자동발급 주기 설정 불러오기
				$autoPayFl = $campaignData['autoPayFl'] ?? '';
				$weekDay = $campaignData['weekDay'] ?? '';
				$weekDayArray = !empty($weekDay) ? explode(',', $weekDay) : [];
				$monthDay = $campaignData['monthDay'] ?? '';
				$yearDate = $campaignData['yearDate'] ?? '';
				$yearDateMonth = '';
				$yearDateDay = '';
				if(!empty($yearDate) && preg_match('/^(\d{2})-(\d{2})$/', $yearDate, $matches)) {
					$yearDateMonth = $matches[1];
					$yearDateDay = $matches[2];
				}
				?>
				<tr>
					<th class="require">지급주기</th>
					<td colspan="3" class="auto_pay_date">
						<div>
							<label class="radio-inline">
								<input type="radio" name="autoPayFl" value="weekDay" <?= ($autoPayFl == 'weekDay') ? 'checked="checked"' : '' ?>>
								매 요일마다 
							</label>
							<label><input type="checkbox" name="weekDay[]" value="mo" <?= in_array('mo', $weekDayArray) ? 'checked="checked"' : '' ?>>월</label>
							<label><input type="checkbox" name="weekDay[]" value="tu" <?= in_array('tu', $weekDayArray) ? 'checked="checked"' : '' ?>>화</label>
							<label><input type="checkbox" name="weekDay[]" value="we" <?= in_array('we', $weekDayArray) ? 'checked="checked"' : '' ?>>수</label>
							<label><input type="checkbox" name="weekDay[]" value="th" <?= in_array('th', $weekDayArray) ? 'checked="checked"' : '' ?>>목</label>
							<label><input type="checkbox" name="weekDay[]" value="fr" <?= in_array('fr', $weekDayArray) ? 'checked="checked"' : '' ?>>금</label>
							<label><input type="checkbox" name="weekDay[]" value="sa" <?= in_array('sa', $weekDayArray) ? 'checked="checked"' : '' ?>>토</label>
							<label><input type="checkbox" name="weekDay[]" value="su" <?= in_array('su', $weekDayArray) ? 'checked="checked"' : '' ?>>일</label>
						</div>
						<div>
							<label class="radio-inline">
								<input type="radio" name="autoPayFl" value="monthDay" <?= ($autoPayFl == 'monthDay') ? 'checked="checked"' : '' ?>>
								매월
								<input type="text" name="monthDay" class="form-control width-sm" value="<?= htmlspecialchars($monthDay) ?>">
								일마다
							</label>
						</div>
						<div>
							<label class="radio-inline">
								<input type="radio" name="autoPayFl" value="yearDate" <?= ($autoPayFl == 'yearDate') ? 'checked="checked"' : '' ?>>
								매년 
								<input type="text" name="yearDateMonth" value="<?= htmlspecialchars($yearDateMonth) ?>" class="form-control width-2xs" placeholder="">
								월
								<input type="text" name="yearDateDay" value="<?= htmlspecialchars($yearDateDay) ?>" class="form-control width-2xs" placeholder="">
								일
								마다
							</label>
						</div>
					</td>
				</tr>
				<?php endif; ?>

				<tr>
					<th>중복지급제한</th>
					<td colspan="3">
						<dl class="dl-horizontal">
							<dt style="width:230px;">같은 아이디에 대해 같은 쇼핑지원금을</dt>
							<dd style="margin-left:230px;">
								<div class="radio mgt0">
									<label class="radio-inline">
										<input type="radio" name="memberAlwaysExceptFl" value="y" <?= $campaignChecked['memberAlwaysExceptFl']['y'] ?>>재지급하지 않음 (유효기간 만료일 달라도 지급 제외)
									</label>
								</div>
								<div class="radio mgb0 form-inline" style="position: relative;">
									<label class="radio-inline">
										<input type="radio" name="memberAlwaysExceptFl" value="n" <?= $campaignChecked['memberAlwaysExceptFl']['n'] ?>>재지급함
									</label>
									<label class="checkbox-inline mgl10">
										<input type="checkbox" name="memberAlwaysExceptLimitType" value="y" <?= $campaignChecked['memberAlwaysExceptLimitType']['y'] ?>>최대
									</label>
									<input type="text" name="memberAlwaysExceptLimit" value="<?= htmlspecialchars($campaignData['memberAlwaysExceptLimit'] ?? '') ?>" class="form-control js-number-only width-xs" maxlength="8">번
								</div>
							</dd>
						</dl>
					</td>
				</tr>
			</tbody>
		</table>

		<?php if($campaignData['campaignPayFl'] != 'y' && $paymentFl != 'auto'): ?>
		<div class="btn_box">
			<input type="button" value="저장" class="btn btn-register btn-gray">
			<input type="button" value="취소" class="btn btn-white" onclick="parent.location.href='./event_mileage_campaign_list.php';" style="margin-left:10px;">
		</div>
		<?php endif; ?>
		<?php if($campaignData['campaignPayFl'] != 'y' && $paymentFl == 'auto'): ?>
		<div class="btn_box">
			<input type="button" value="취소" class="btn btn-white" onclick="parent.location.href='./event_mileage_campaign_list.php';">
		</div>
		<?php endif; ?>
	</form>

	<?php if($paymentFl == 'excelUpload'): ?>
	<!-- 엑셀업로드일 때 지급 버튼 -->
	<div class="btn-box" style="position: relative; border-top: 1px solid #888888; padding-top: 15px; margin-top: 30px;">
		<div class="btn-pay" style="position: absolute; right: 0; top: 15px;">
			<label for="memberExceptFlExcel" style="font-weight: normal; margin-right: 10px;">
				<input id="memberExceptFlExcel" type="checkbox" name="memberExceptFl" value="y" <?= ($campaignData['memberAlwaysExceptFl'] == 'y') ? 'checked="checked"' : '' ?>> 본 캠페인에서 기존에 지급받은 회원 제외
			</label>
			<input type="button" value="쇼핑지원금 지급" class="btn btn-red excel_member_pay" style="padding:5px 20px;">
		</div>
	</div>
	<?php endif; ?>

	<?php if($paymentFl == 'passivity' || $paymentFl == 'auto'): ?>
	<!-- 수동발급 또는 자동발급일 때 회원 검색 및 목록 표시 -->
	<form id="frmSearchBase" method="get" class="js-search-form js-form-enter-submit" action="./event_mileage_campaign_view.php">
		<input type="hidden" name="sno" value="<?= $sno ?>">
		<input type="hidden" name="searchFl" value="y">
		<input type="hidden" name="campaignSno" value="<?= $sno ?>">
		
		<div class="table-title" style="margin-top: 50px;">지급 대상 검색</div>
		<div class="search-detail-box">
			<table class="table table-cols">
				<colgroup>
					<col class="width-md">
					<col>
					<col class="width-md">
					<col>
				</colgroup>
				<tbody>
					<tr>
						<th>검색어</th>
						<td>
							<select name="key" class="form-control">
								<option value="" <?= (empty($_GET['key'])) ? 'selected="selected"' : '' ?>>전체</option>
								<option value="memId" <?= (!empty($_GET['key']) && $_GET['key'] == 'memId') ? 'selected="selected"' : '' ?>>회원아이디</option>
								<option value="memNm" <?= (!empty($_GET['key']) && $_GET['key'] == 'memNm') ? 'selected="selected"' : '' ?>>회원이름</option>
								<option value="cellphone" <?= (!empty($_GET['key']) && $_GET['key'] == 'cellphone') ? 'selected="selected"' : '' ?>>휴대폰</option>

							</select>
							<input type="text" name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" class="form-control">
						</td>
						<th>회원가입일</th>
						<td>
							<div class="form-inline">
								<div class="input-group js-datepicker">
									<input type="text" class="form-control width-xs" name="entryDt[]" value="<?= htmlspecialchars($_GET['entryDt'][0] ?? '') ?>">
									<span class="input-group-addon">
										<span class="btn-icon-calendar"></span>
									</span>
								</div>
								~
								<div class="input-group js-datepicker">
									<input type="text" class="form-control width-xs" name="entryDt[]" value="<?= htmlspecialchars($_GET['entryDt'][1] ?? '') ?>">
									<span class="input-group-addon">
										<span class="btn-icon-calendar"></span>
									</span>
								</div>
							</div>
						</td>
						<th>주문일</th>
						<td>
							<div class="form-inline">
								<div class="input-group js-datepicker">
									<input type="text" class="form-control width-xs" name="orderDt[]" value="<?= htmlspecialchars($_GET['orderDt'][0] ?? '') ?>">
									<span class="input-group-addon">
										<span class="btn-icon-calendar"></span>
									</span>
								</div>
								~
								<div class="input-group js-datepicker">
									<input type="text" class="form-control width-xs" name="orderDt[]" value="<?= htmlspecialchars($_GET['orderDt'][1] ?? '') ?>">
									<span class="input-group-addon">
										<span class="btn-icon-calendar"></span>
									</span>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<th>회원등급</th>
						<td colspan="3">
							<?php 
							$memberGroupList = $member->getGroup();
							$selectedGroupNos = !empty($_GET['groupNo']) && is_array($_GET['groupNo']) ? array_map('intval', $_GET['groupNo']) : (!empty($_GET['groupNo']) ? [intval($_GET['groupNo'])] : []);
							?>
							<label style="margin-right: 15px; font-weight: normal;">
								<input type="checkbox" name="groupNo[]" value="" class="js-group-all" <?= empty($selectedGroupNos) ? 'checked="checked"' : '' ?>> 전체
							</label>
							<?php foreach($memberGroupList as $group): ?>
								<label style="margin-right: 15px; font-weight: normal;">
									<input type="checkbox" name="groupNo[]" value="<?= $group['group_no'] ?>" class="js-group-item" <?= in_array($group['group_no'], $selectedGroupNos) ? 'checked="checked"' : '' ?>>
									<?= htmlspecialchars($group['group_nm']) ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="table-btn">
			<input type="submit" value="검색" class="btn btn-lg btn-black js-search-button">
		</div>
	</form>

	<?php if($paymentFl == 'passivity' || $paymentFl == 'auto'): ?>
	<div class="btn-box" style="position: relative; border-top: 1px solid #888888; padding-top: 15px; margin-top: 30px;">
		<div class="btn-pay" style="position: absolute; right: 0; top: 15px;">
			<?php if($paymentFl != 'down'): ?>
			<label for="memberExceptFl">
				<input id="memberExceptFl" type="checkbox" name="memberExceptFl" value="y" <?= ($paymentFl == 'auto') ? 'checked="checked" disabled="disabled"' : (($campaignData['memberAlwaysExceptFl'] == 'y') ? 'checked="checked"' : '') ?>> 본 캠페인에서 기존에 지급받은 회원 제외(자동지급일 경우 필수)
			</label>
			<?php endif; ?>
			<?php if($paymentFl == 'passivity'): ?>
			<input type="button" value="체크 회원만 지급" class="btn btn-red check_member_pay" style="margin-left:10px; padding:5px 20px;">
			<input type="button" value="쇼핑지원금 지급" class="btn btn-red search_member_pay" style="margin-left:10px; padding:5px 20px;">
			<?php endif; ?>
			<?php if($paymentFl == 'auto'): ?>
			<input type="button" value="설정 저장" class="btn btn-red auto_check_member_pay" style="margin-left:10px; padding:5px 20px;">
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if($paymentFl == 'passivity' || $paymentFl == 'auto'): ?>
	<!-- 검색 실행 시 회원 목록 표시 -->
	<form id="frmList" action="" method="get">
		<input type="hidden" name="sno" value="<?= $sno ?>">
		<input type="hidden" name="searchFl" value="y">
		<?php 
		// 검색 조건 유지
		if(!empty($_GET['key'])): ?>
			<input type="hidden" name="key" value="<?= htmlspecialchars($_GET['key']) ?>">
		<?php endif; ?>
		<?php if(!empty($_GET['keyword'])): ?>
			<input type="hidden" name="keyword" value="<?= htmlspecialchars($_GET['keyword']) ?>">
		<?php endif; ?>
		<?php if(!empty($_GET['entryDt']) && is_array($_GET['entryDt'])): ?>
			<?php foreach($_GET['entryDt'] as $idx => $entryDt): ?>
				<input type="hidden" name="entryDt[]" value="<?= htmlspecialchars($entryDt) ?>">
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if(!empty($_GET['groupNo']) && is_array($_GET['groupNo'])): ?>
			<?php foreach($_GET['groupNo'] as $groupNo): ?>
				<input type="hidden" name="groupNo[]" value="<?= htmlspecialchars($groupNo) ?>">
			<?php endforeach; ?>
		<?php endif; ?>
		
		<div class="table-header" style="margin-top: 30px;">
			<div class="pull-left">
				검색 <strong><?= number_format($searchCnt) ?></strong>명 / 전체 <strong><?= number_format($totalCnt) ?></strong>명
			</div>
		</div>

		<table class="table table-rows">
			<thead>
				<tr>
					<?php if($paymentFl != 'auto'): ?>
					<th class="width2p">
						<input type="checkbox" class="js-checkall" data-target-name="chk">
					</th>
					<?php endif; ?>
					<th>번호</th>
					<th>아이디</th>
					<th>이름</th>
					<th>회원등급</th>
					<th>회원가입일</th>
				</tr>
			</thead>
			<tbody>
				<?php if(!empty($memberList)): ?>
					<?php 
					$pageNum = !empty($_GET['pageNum']) ? intval($_GET['pageNum']) : 50;
					$currentPage = !empty($_GET['page']) ? intval($_GET['page']) : 1;
					$startNum = ($currentPage - 1) * $pageNum;
					foreach($memberList as $idx => $memberItem): 
						$rowNum = $startNum + $idx + 1;
						$memId = htmlspecialchars($memberItem['memId'] ?? '');
						$memNm = htmlspecialchars($memberItem['memNm'] ?? '');
						$groupNm = htmlspecialchars($memberItem['group_nm'] ?? '');
						$joinDt = $memberItem['joinDt'] ?? $memberItem['regDt'] ?? '';
						$joinDate = $joinDt ? substr($joinDt, 0, 10) : '';
					?>
					<tr>
						<?php if($paymentFl != 'auto'): ?>
						<td class="center">
							<input type="checkbox" name="chk[]" value="<?= $memId ?>">
						</td>
						<?php endif; ?>
						<td class="center"><?= $rowNum ?></td>
						<td class="center"><?= $memId ?></td>
						<td class="center"><?= $memNm ?></td>
						<td class="center"><?= $groupNm ?></td>
						<td class="center"><?= $joinDate ?></td>
					</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="<?= $paymentFl != 'auto' ? '7' : '6' ?>" class="center empty_table">검색된 회원이 없습니다.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		
		<?php if(!empty($pageHtml)): ?>
			<?= $pageHtml ?>
		<?php endif; ?>
	</form>
	<?php endif; ?>
	<?php endif; ?>
</div>

<script type="text/javascript">
$(document).ready(function () {
	//본 캠페인이 이벤트 마일리지 지급했을때 수정 불가
	<?php if($campaignData['campaignPayFl'] == 'y'): ?>
		<?php if($paymentFl != 'auto'): ?>
			// 자동 발급이 아닐 때는 모든 필드 수정 불가
			$('#frmCampaignRegist input').prop('readonly', true);
			$('#frmCampaignRegist input[type="checkbox"]').attr('onclick', 'return false;');
			$('#frmCampaignRegist input[type="radio"]').attr('onclick', 'return false;');
			$('#frmCampaignRegist select').prop('disabled', true);
		<?php else: ?>
			// 자동 발급일 때는 검색 조건만 수정 가능, 나머지는 수정 불가
			$('#frmCampaignRegist input').not('#frmSearchBase input').prop('readonly', true);
			$('#frmCampaignRegist input[type="checkbox"]').not('#frmSearchBase input').attr('onclick', 'return false;');
			$('#frmCampaignRegist input[type="radio"]').not('#frmSearchBase input').attr('onclick', 'return false;');
			$('#frmCampaignRegist select').not('#frmSearchBase select').prop('disabled', true);
		<?php endif; ?>
	<?php endif; ?>

	$('#frmCampaignRegist').validate({
		submitHandler: function(form) {
			// 캠페인명 검사
			if(! $('input[name="campaignNm"]').val()) {
				alert('캠페인명을 입력해주세요.');
				return false;
			}

			// 캠페인 설명 검사
			if(! $('input[name="campaignDes"]').val()) {
				alert('캠페인설명을 입력해주세요.');
				return false;
			}

			// 유효기간검사
			var expiryDaysFl = $('input[name="expiryDaysFl"]:checked').val();
			
			// 기간선택(y)일 때: expiryStartDate와 expiryDate 필수
			if(expiryDaysFl == 'y') {
				if(!$('input[name="expiryStartDate"]').val() || !$('input[name="expiryDate"]').val()) {
					alert('기간선택의 시작일과 종료일을 입력해주세요.');
					return false;
				}
				if($('input[name="expiryStartDate"]').val() > $('input[name="expiryDate"]').val()) {
					alert('기간선택의 시작일과 종료일을 확인해주세요.');
					return false;
				}
			}
			
			// 발급일로부터(dayDate)일 때: expiryDays 필수
			if(expiryDaysFl == 'dayDate') {
				if(!$('input[name="expiryDays"]').val() || parseInt($('input[name="expiryDays"]').val()) <= 0) {
					alert('발급일로부터 일수를 입력해주세요.');
					return false;
				}
			}

			// 혜택금액 검사
			if(! $('input[name="eventMileage"]').val()) {
				alert('혜택금액을 설정해주세요.');
				return false;
			}

			// 혜택사용율 검사
			if(! $('input[name="mileageLimit"]').val()) {
				alert('혜택사용율을 설정해주세요.');
				return false;
			}

			// 지급기간 검사
			if(! $('input[name="payStartDate"]').val() || ! $('input[name="payEndDate"]').val()) {
				alert('지급기간을 설정해주세요.');
				return false;
			}

			if($('input[name="payStartDate"]').val() > $('input[name="payEndDate"]').val()) {
				alert('지급기간을 확인해주세요.');
				return false;
			}

			form.submit();
		}
	});

	$('.btn-register').click(function (e) {
		e.preventDefault();
		
		// 수동 검증
		if(! $('input[name="campaignNm"]').val()) {
			alert('캠페인명을 입력해주세요.');
			return false;
		}
		if(! $('input[name="campaignDes"]').val()) {
			alert('캠페인설명을 입력해주세요.');
			return false;
		}
		// 유효기간검사
		var expiryDaysFl = $('input[name="expiryDaysFl"]:checked').val();
		
		// 기간선택(y)일 때: expiryStartDate와 expiryDate 필수
		if(expiryDaysFl == 'y') {
			if(!$('input[name="expiryStartDate"]').val() || !$('input[name="expiryDate"]').val()) {
				alert('기간선택의 시작일과 종료일을 입력해주세요.');
				return false;
			}
			if($('input[name="expiryStartDate"]').val() > $('input[name="expiryDate"]').val()) {
				alert('기간선택의 시작일과 종료일을 확인해주세요.');
				return false;
			}
		}
		
		// 발급일로부터(dayDate)일 때: expiryDays 필수
		if(expiryDaysFl == 'dayDate') {
			if(!$('input[name="expiryDays"]').val() || parseInt($('input[name="expiryDays"]').val()) <= 0) {
				alert('발급일로부터 일수를 입력해주세요.');
				return false;
			}
		}
		if(! $('input[name="eventMileage"]').val()) {
			alert('혜택금액을 설정해주세요.');
			return false;
		}
		if(! $('input[name="mileageLimit"]').val()) {
			alert('혜택사용율을 설정해주세요.');
			return false;
		}
		if(! $('input[name="payStartDate"]').val() || ! $('input[name="payEndDate"]').val()) {
			alert('지급기간을 설정해주세요.');
			return false;
		}
		if($('input[name="payStartDate"]').val() > $('input[name="payEndDate"]').val()) {
			alert('지급기간을 확인해주세요.');
			return false;
		}

		// AJAX로 처리하여 처리중 모달이 뜨지 않도록 함
		var formData = $("#frmCampaignRegist").serialize();
		$.ajax({
			url: './event_mileage_campaign_ps.php',
			type: 'POST',
			data: formData,
			success: function(response) {
				// 서버에서 보낸 스크립트 실행 (alert 및 리다이렉트)
				var iframe = document.createElement('iframe');
				iframe.style.display = 'none';
				document.body.appendChild(iframe);
				iframe.contentWindow.document.write(response);
				setTimeout(function() {
					document.body.removeChild(iframe);
				}, 100);
			},
			error: function() {
				alert('저장 중 오류가 발생했습니다.');
			}
		});
	});

	// 유효기간 구분 변경 시 다른 필드 초기화
	$('input[name="expiryDaysFl"]').change(function(){
		var selectedExpiryDaysFl = $(this).val();
		
		if(selectedExpiryDaysFl == 'y') {
			// 기간선택 선택 시: expiryDays 초기화
			$('input[name="expiryDays"]').val('');
		} else if(selectedExpiryDaysFl == 'dayDate') {
			// 발급일로부터 선택 시: expiryStartDate, expiryDate 초기화
			$('input[name="expiryStartDate"]').val('');
			$('input[name="expiryDate"]').val('');
		} else {
			// 발급월말, 발급년도말 선택 시: expiryDays, expiryStartDate, expiryDate 초기화
			$('input[name="expiryDays"]').val('');
			$('input[name="expiryStartDate"]').val('');
			$('input[name="expiryDate"]').val('');
		}
	});

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

	// 회원 검색
	$('#frmSearchBase .js-search-button').click(function(){
		// 전체가 체크되어 있으면 모든 groupNo[] 제거 (전체 포함)
		if($('.js-group-all').is(':checked')) {
			$('#frmSearchBase input[name="groupNo[]"]').remove();
		}
		$('#frmSearchBase').attr('target', '');
		$('#frmSearchBase').submit();
	});

	// 지급방식별 처리
	var paymentFl = '<?= $paymentFl ?>';
	
	// 쇼핑지원금 지급 (검색 결과 기준)
	$('.search_member_pay').click(function(){
		if(paymentFl == 'passivity') { //수동일때
			$('#frmSearchBase').attr('target', 'ifrmProcess');
			$('#frmSearchBase').attr('action', './event_mileage_campaign_ps.php');
			$('#frmSearchBase input[name="campaignSno"]').remove();
			$('#frmSearchBase').append('<input type="hidden" name="campaignSno" value="<?= $sno ?>">');
			$('#frmSearchBase input[name="mode"]').remove();
			$('#frmSearchBase').append('<input type="hidden" name="mode" value="searchMemberPay">');
			
			if($('input[name="memberExceptFl"]:checked').val() == 'y') {
				$('#frmSearchBase input[name="memberExceptFl"]').remove();
				$('#frmSearchBase').append('<input type="hidden" name="memberExceptFl" value="y">');
			} else {
				$('#frmSearchBase input[name="memberExceptFl"]').remove();
			}
			$('#frmSearchBase').submit();
		} else if(paymentFl == 'excelUpload') { //엑셀업로드일때
			if(!$('input[name="excelMemNo"]').val()){
				alert('엑셀양식을 확인해주세요');
				return false;
			}
			
			if(!$('input[name="memberExcel"]').val()) {
				alert('업로드 파일이 빈값입니다.');
				return false;
			}

			if($('input[name="memberExceptFl"]:checked').val() == 'y') {
				$('#frmCampaignRegist input[name="memberExceptFl"]').remove();
				$('#frmCampaignRegist').append('<input type="hidden" name="memberExceptFl" value="y">');
			} else {
				$('#frmCampaignRegist input[name="memberExceptFl"]').remove();
			}

			$('#frmCampaignRegist').attr('target', 'ifrmProcess');
			$('#frmCampaignRegist input[name="mode"]').val('excelUploadPay');
			$('#frmCampaignRegist').submit();
		}
	});

	// 체크 회원만 지급 (수동발급)
	$('.check_member_pay').click(function(){
		if($('#frmList input[name="chk[]"]:checked').length == 0) {
			alert('선택된 회원이 없습니다.');
			return false;
		}
		
		// 체크된 회원의 memId를 배열로 수집
		var checkedMemIds = [];
		$('#frmList input[name="chk[]"]:checked').each(function(){
			checkedMemIds.push($(this).val());
		});
		
		var memberExceptFl = ($('#memberExceptFl').length > 0 && $('#memberExceptFl').is(':checked')) ? 'y' : 'n';
		
		// AJAX로 지급 처리
		$.ajax({
			url: './event_mileage_campaign_ps.php',
			type: 'POST',
			data: {
				mode: 'checkMemberPay',
				campaignSno: '<?= $sno ?>',
				memberExceptFl: memberExceptFl,
				chk: checkedMemIds
			},
			success: function(response) {
				// 서버에서 보낸 스크립트 실행 (alert 및 리다이렉트)
				var iframe = document.createElement('iframe');
				iframe.style.display = 'none';
				document.body.appendChild(iframe);
				iframe.contentWindow.document.write(response);
				setTimeout(function() {
					document.body.removeChild(iframe);
				}, 100);
			},
			error: function() {
				alert('지급 처리 중 오류가 발생했습니다.');
			}
		});
	});
	
	// 페이징 함수
	window.move_page = function(page) {
		$('#frmList input[name="page"]').remove();
		$('#frmList').append('<input type="hidden" name="page" value="' + page + '">');
		$('#frmList').submit();
	}

	// 자동발급 주기 변경 시 다른 옵션 초기화
	$('input[name="autoPayFl"]').change(function(){
		var selectedAutoPayFl = $(this).val();
		
		if(selectedAutoPayFl == 'weekDay') {
			// weekDay 선택 시: monthDay, yearDate 초기화
			$('input[name="monthDay"]').val('');
			$('input[name="yearDateMonth"]').val('');
			$('input[name="yearDateDay"]').val('');
		} else if(selectedAutoPayFl == 'monthDay') {
			// monthDay 선택 시: weekDay[], yearDate 초기화
			$('input[name="weekDay[]"]').prop('checked', false);
			$('input[name="yearDateMonth"]').val('');
			$('input[name="yearDateDay"]').val('');
		} else if(selectedAutoPayFl == 'yearDate') {
			// yearDate 선택 시: weekDay[], monthDay 초기화
			$('input[name="weekDay[]"]').prop('checked', false);
			$('input[name="monthDay"]').val('');
		}
	});

	// 설정 저장 (자동발급)
	$('.auto_check_member_pay').click(function(){
		// 자동발급 주기 설정 검증
		var autoPayFl = $('input[name="autoPayFl"]:checked').val();
		if(!autoPayFl) {
			alert('지급주기를 선택해주세요.');
			return false;
		}
		
		if(autoPayFl == 'weekDay') {
			if($('input[name="weekDay[]"]:checked').length == 0) {
				alert('요일을 선택해주세요.');
				return false;
			}
		} else if(autoPayFl == 'monthDay') {
			if(!$('input[name="monthDay"]').val() || parseInt($('input[name="monthDay"]').val()) < 1 || parseInt($('input[name="monthDay"]').val()) > 31) {
				alert('매월 지급일을 올바르게 입력해주세요. (1-31)');
				return false;
			}
		} else if(autoPayFl == 'yearDate') {
			if(!$('input[name="yearDateMonth"]').val() || !$('input[name="yearDateDay"]').val()) {
				alert('매년 지급일을 입력해주세요.');
				return false;
			}
			var month = parseInt($('input[name="yearDateMonth"]').val());
			var day = parseInt($('input[name="yearDateDay"]').val());
			if(month < 1 || month > 12 || day < 1 || day > 31) {
				alert('매년 지급일을 올바르게 입력해주세요.');
				return false;
			}
		}
		
		// 자동발급 주기 설정과 회원 검색 조건을 frmCampaignRegist에 포함
		// frmSearchBase의 검색 조건 가져오기
		var searchKey = $('#frmSearchBase select[name="key"]').val() || '';
		var searchKeyword = $('#frmSearchBase input[name="keyword"]').val() || '';
		var searchEntryDt = [];
		$('#frmSearchBase input[name="entryDt[]"]').each(function(){
			var entryDtVal = $(this).val() || '';
			if(entryDtVal.trim() !== '') {
				searchEntryDt.push(entryDtVal);
			}
		});
		
		// 회원등급(groupNo) 가져오기
		var searchGroupNos = [];
		$('#frmSearchBase input[name="groupNo[]"].js-group-item:checked').each(function(){
			var groupNoVal = $(this).val();
			if(groupNoVal && groupNoVal.trim() !== '') {
				searchGroupNos.push(groupNoVal);
			}
		});
		
		// 검색 조건 확인 (keyword가 입력되어 있거나, entryDt가 하나라도 입력되어 있거나, groupNo가 선택되어 있으면 검색 조건 있음)
		var hasSearchCondition = false;
		// keyword가 입력되어 있으면 검색 조건 있음
		if(searchKeyword && searchKeyword.trim() !== '') {
			hasSearchCondition = true;
		}
		// entryDt가 하나라도 입력되어 있으면 검색 조건 있음
		if(searchEntryDt.length > 0 && searchEntryDt.some(function(val) { return val.trim() !== ''; })) {
			hasSearchCondition = true;
		}
		// groupNo가 선택되어 있으면 검색 조건 있음
		if(searchGroupNos.length > 0) {
			hasSearchCondition = true;
		}
		
		if(!hasSearchCondition) {
			if(!confirm('검색 조건이 없습니다. 전체 회원을 대상으로 저장할까요?')) {
				return false;
			}
		}
		
		// hidden input으로 추가 (기존 hidden input만 제거)
		$('#frmCampaignRegist input[type="hidden"][name="autoPayFl"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="weekDay[]"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="monthDay"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="yearDateMonth"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="yearDateDay"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="key"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="keyword"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="entryDt[]"]').remove();
		$('#frmCampaignRegist input[type="hidden"][name="groupNo[]"]').remove();
		
		// 체크박스와 input을 serialize에서 제외하기 위해 disabled 처리 (나중에 복원)
		var weekDayCheckboxes = $('#frmCampaignRegist input[name="weekDay[]"]');
		var monthDayInput = $('#frmCampaignRegist input[name="monthDay"]');
		var yearDateMonthInput = $('#frmCampaignRegist input[name="yearDateMonth"]');
		var yearDateDayInput = $('#frmCampaignRegist input[name="yearDateDay"]');
		var autoPayFlRadios = $('#frmCampaignRegist input[name="autoPayFl"]');
		
		weekDayCheckboxes.prop('disabled', true);
		monthDayInput.prop('disabled', true);
		yearDateMonthInput.prop('disabled', true);
		yearDateDayInput.prop('disabled', true);
		autoPayFlRadios.prop('disabled', true);
		
		// 자동발급 주기 설정 추가
		$('#frmCampaignRegist').append('<input type="hidden" name="autoPayFl" value="' + autoPayFl + '">');
		if(autoPayFl == 'weekDay') {
			$('input[name="weekDay[]"]:checked').each(function(){
				$('#frmCampaignRegist').append('<input type="hidden" name="weekDay[]" value="' + $(this).val() + '">');
			});
		} else if(autoPayFl == 'monthDay') {
			var monthDayVal = $('input[name="monthDay"]').val();
			if(monthDayVal) {
				$('#frmCampaignRegist').append('<input type="hidden" name="monthDay" value="' + monthDayVal + '">');
			}
		} else if(autoPayFl == 'yearDate') {
			var yearDateMonthVal = $('input[name="yearDateMonth"]').val();
			var yearDateDayVal = $('input[name="yearDateDay"]').val();
			if(yearDateMonthVal && yearDateDayVal) {
				$('#frmCampaignRegist').append('<input type="hidden" name="yearDateMonth" value="' + yearDateMonthVal + '">');
				$('#frmCampaignRegist').append('<input type="hidden" name="yearDateDay" value="' + yearDateDayVal + '">');
			}
		}
		
		// 회원 검색 조건 추가
		if(searchKey) {
			$('#frmCampaignRegist').append('<input type="hidden" name="key" value="' + searchKey + '">');
		}
		if(searchKeyword) {
			$('#frmCampaignRegist').append('<input type="hidden" name="keyword" value="' + searchKeyword + '">');
		}
		$.each(searchEntryDt, function(index, value){
			if(value) {
				$('#frmCampaignRegist').append('<input type="hidden" name="entryDt[]" value="' + value + '">');
			}
		});
		
		// 회원등급 추가
		$.each(searchGroupNos, function(index, value){
			if(value) {
				$('#frmCampaignRegist').append('<input type="hidden" name="groupNo[]" value="' + value + '">');
			}
		});
		
		// 자동 발급일 때는 memberExceptFl을 무조건 'y'로 설정
		$('#frmCampaignRegist input[type="hidden"][name="memberExceptFl"]').remove();
		$('#frmCampaignRegist').append('<input type="hidden" name="memberExceptFl" value="y">');
		
		// 지급 여부 확인
		var isPaid = <?= ($campaignData['campaignPayFl'] == 'y') ? 'true' : 'false' ?>;
		
		if(!isPaid) {
			// 지급 전에는 모든 설정 저장
			// 기존 hidden input 제거 (중복 방지)
			$('#frmCampaignRegist input[type="hidden"][name="campaignNm"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="campaignDes"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="eventMileage"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="mileageLimit"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="expiryDaysFl"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="expiryStartDate"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="expiryDate"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="expiryDays"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="payStartDate"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="payEndDate"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="paymentFl"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="memberAlwaysExceptFl"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="memberAlwaysExceptLimitType"]').remove();
			$('#frmCampaignRegist input[type="hidden"][name="memberAlwaysExceptLimit"]').remove();
			
			// readonly/disabled 필드의 값 직접 읽어서 추가
			$('#frmCampaignRegist').append('<input type="hidden" name="campaignNm" value="' + ($('#frmCampaignRegist input[name="campaignNm"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="campaignDes" value="' + ($('#frmCampaignRegist input[name="campaignDes"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="eventMileage" value="' + ($('#frmCampaignRegist input[name="eventMileage"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="mileageLimit" value="' + ($('#frmCampaignRegist input[name="mileageLimit"]').val() || '') + '">');
			var expiryDaysFlVal = $('#frmCampaignRegist input[name="expiryDaysFl"]:checked').val() || '';
			if(expiryDaysFlVal) {
				$('#frmCampaignRegist').append('<input type="hidden" name="expiryDaysFl" value="' + expiryDaysFlVal + '">');
			}
			$('#frmCampaignRegist').append('<input type="hidden" name="expiryStartDate" value="' + ($('#frmCampaignRegist input[name="expiryStartDate"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="expiryDate" value="' + ($('#frmCampaignRegist input[name="expiryDate"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="expiryDays" value="' + ($('#frmCampaignRegist input[name="expiryDays"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="payStartDate" value="' + ($('#frmCampaignRegist input[name="payStartDate"]').val() || '') + '">');
			$('#frmCampaignRegist').append('<input type="hidden" name="payEndDate" value="' + ($('#frmCampaignRegist input[name="payEndDate"]').val() || '') + '">');
			var paymentFlVal = $('#frmCampaignRegist input[name="paymentFl"]:checked').val() || 'auto';
			$('#frmCampaignRegist').append('<input type="hidden" name="paymentFl" value="' + paymentFlVal + '">');
			var memberAlwaysExceptFlVal = $('#frmCampaignRegist input[name="memberAlwaysExceptFl"]:checked').val() || 'n';
			$('#frmCampaignRegist').append('<input type="hidden" name="memberAlwaysExceptFl" value="' + memberAlwaysExceptFlVal + '">');
			var memberAlwaysExceptLimitTypeVal = $('#frmCampaignRegist input[name="memberAlwaysExceptLimitType"]:checked').val() || '';
			if(memberAlwaysExceptLimitTypeVal) {
				$('#frmCampaignRegist').append('<input type="hidden" name="memberAlwaysExceptLimitType" value="' + memberAlwaysExceptLimitTypeVal + '">');
			}
			$('#frmCampaignRegist').append('<input type="hidden" name="memberAlwaysExceptLimit" value="' + ($('#frmCampaignRegist input[name="memberAlwaysExceptLimit"]').val() || '') + '">');
		}
		// 지급 후에는 검색 조건만 전송 (sno, key, keyword, entryDt만 필요)
		
		// AJAX로 처리
		var formData = $("#frmCampaignRegist").serialize();
		formData += '&mode=autoCheckMemberPay';
		
		// disabled 해제 (원래 상태로 복원)
		weekDayCheckboxes.prop('disabled', false);
		monthDayInput.prop('disabled', false);
		yearDateMonthInput.prop('disabled', false);
		yearDateDayInput.prop('disabled', false);
		autoPayFlRadios.prop('disabled', false);
		
		$.ajax({
			url: './event_mileage_campaign_ps.php',
			type: 'POST',
			data: formData,
			success: function(response) {
				// 서버에서 보낸 스크립트 실행 (alert 및 리다이렉트)
				var iframe = document.createElement('iframe');
				iframe.style.display = 'none';
				document.body.appendChild(iframe);
				iframe.contentWindow.document.write(response);
				setTimeout(function() {
					document.body.removeChild(iframe);
				}, 100);
			},
			error: function() {
				alert('설정 저장 중 오류가 발생했습니다.');
			}
		});
	});

	// 엑셀 파일 선택 시 미리보기
	$('input[name="memberExcel"]').change(function(){
		var fileInput = this;
		if(!fileInput.files || fileInput.files.length == 0) {
			$('#excelMemberCount span').text('0');
			return;
		}

		// 파일 미리 파싱하여 회원 수 표시
		var formData = new FormData();
		formData.append('memberExcel', fileInput.files[0]);
		formData.append('mode', 'excelUploadParse');

		$.ajax({
			url: './event_mileage_campaign_ps.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				try {
					var result = JSON.parse(response);
					if(result.success) {
						$('#excelMemberCount span').text(result.count || 0);
						// 파싱된 데이터를 나중에 사용하기 위해 저장
						$('input[name="memberExcel"]').data('excelData', result.data);
					} else {
						$('#excelMemberCount span').text('0');
						alert(result.message || '엑셀 파일을 확인할 수 없습니다.');
					}
				} catch(e) {
					$('#excelMemberCount span').text('0');
				}
			},
			error: function() {
				$('#excelMemberCount span').text('0');
			}
		});
	});

	// 엑셀업로드 지급 처리
	$('.excel_member_pay').click(function(){
		var fileInput = $('input[name="memberExcel"]')[0];
		if(!fileInput.files || fileInput.files.length == 0) {
			alert('엑셀 파일을 선택해주세요.');
			return false;
		}

		var excelData = $('input[name="memberExcel"]').data('excelData');
		if(!excelData || excelData.length == 0) {
			alert('엑셀 파일을 다시 선택해주세요.');
			return false;
		}

		if(!confirm('총 ' + excelData.length + '명에게 쇼핑지원금을 지급하시겠습니까?')) {
			return false;
		}

		// 지급 처리 (엑셀업로드용 체크박스 또는 일반 체크박스 확인)
		var memberExceptFl = ($('#memberExceptFlExcel').length > 0 && $('#memberExceptFlExcel').is(':checked')) || ($('#memberExceptFl').length > 0 && $('#memberExceptFl').is(':checked')) ? 'y' : 'n';
		var payFormData = new FormData();
		payFormData.append('mode', 'excelUploadPay');
		payFormData.append('campaignSno', '<?= $sno ?>');
		payFormData.append('memberExceptFl', memberExceptFl);
		payFormData.append('excelData', JSON.stringify(excelData));

		$.ajax({
			url: './event_mileage_campaign_ps.php',
			type: 'POST',
			data: payFormData,
			processData: false,
			contentType: false,
			success: function(payResponse) {
				// 결과는 ps.php에서 alert로 처리됨
				var iframe = document.createElement('iframe');
				iframe.name = 'ifrmProcess';
				iframe.style.display = 'none';
				document.body.appendChild(iframe);
				iframe.contentWindow.document.write(payResponse);
			},
			error: function() {
				alert('지급 처리 중 오류가 발생했습니다.');
			}
		});
	});
});
</script>

<?php require_once './../footer.php'; ?>

